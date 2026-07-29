import {
  assertSafePublicUrl,
  buildCandidates,
  cleanText,
  isPublicIpAddress,
  isSameCandidateSite,
  normalizeComparableHostname,
  splitTitle
} from './domain';
import type { DetectionDiagnostic } from './types';

const SITE_RESPONSE_LIMIT = 384 * 1024;
const WORDPRESS_RESPONSE_LIMIT = 256 * 1024;
const BACKLINK_RESPONSE_LIMIT = 512 * 1024;
const DNS_RESPONSE_LIMIT = 64 * 1024;
const MAX_REDIRECTS = 2;
const DNS_QUERY_TIMEOUT_MS = 1_250;
const CANDIDATE_CONCURRENCY = 3;
const FRIEND_LINK_DISCOVERY_LIMIT = 8;
const FRIEND_LINK_DISCOVERY_PATHS = ['/links/', '/friends/', '/friend-links/', '/flinks/', '/blogroll/'] as const;
const REQUEST_USER_AGENT = 'Mozilla/5.0 (compatible; ArgonFriendLinkChecker/1.0; +https://github.com/nanhaoluo/Argon-theme-AveMujika)';
const DNS_PROVIDER_GROUPS = [
  ['cloudflare'],
  ['google'],
  ['alidns'],
  ['tencent']
] as const;
const SAFE_DIAGNOSTIC_CODES = new Set([
  'unsafe_or_unresolved_dns',
  'redirect_without_location',
  'too_many_redirects',
  'cross_site_redirect',
  'response_too_large',
  'timeout',
  'network_error',
  'invalid_json',
  'invalid_wordpress_response',
  'challenge_or_invalid_html',
  'links_page_not_confirmed'
]);

export type WorkerFetcher = (input: string, init?: RequestInit) => Promise<Response>;
export type HostResolver = (hostname: string, signal?: AbortSignal) => Promise<string[]>;
export type DetectionState = 'ok' | 'unknown';
export type BacklinkState = 'found' | 'missing' | 'unknown';

export interface DetectionOptions {
  fetcher?: WorkerFetcher;
  resolver?: HostResolver;
  timeoutMs?: number;
  signal?: AbortSignal;
}

export interface BacklinkDiscoveryOptions extends DetectionOptions {
  wordpressApiUrl?: string;
}

export interface SiteDetection {
  state: DetectionState;
  accessible: boolean;
  readable: boolean;
  detectedUrl: string;
  primary: string;
  subtitle: string;
  description: string;
  avatar: string;
  linksPage: string;
  wordpressApiUrl?: string;
  source: 'argon' | 'wordpress' | 'html' | 'unknown';
  attempts: Array<{
    url: string;
    accessible: boolean;
    readable: boolean;
    diagnostics: DetectionDiagnostic[];
  }>;
}

export interface BacklinkDetection {
  state: BacklinkState;
  checkedUrl: string;
  diagnostics: DetectionDiagnostic[];
}

export interface DiscoveredBacklinkDetection extends BacklinkDetection {
  linksPage: string;
}

interface CandidateResult extends Omit<SiteDetection, 'state' | 'attempts'> {
  score: number;
  avatarTier?: number;
  nameTier?: number;
  diagnostics?: DetectionDiagnostic[];
}

class FetchProblem extends Error {
  constructor(
    readonly code: string,
    readonly reachable: boolean,
    readonly httpStatus = 0
  ) {
    super(code);
  }
}

function abortError(signal: AbortSignal): Error {
  if (signal.reason instanceof Error) return signal.reason;
  return new Error(typeof signal.reason === 'string' && signal.reason ? signal.reason : 'operation_aborted');
}

function throwIfAborted(signal?: AbortSignal): void {
  if (signal?.aborted) throw abortError(signal);
}

async function waitForBatch<T>(promises: Array<Promise<T>>): Promise<T[]> {
  const settled = await Promise.allSettled(promises);
  const values: T[] = [];
  for (const result of settled) {
    if (result.status === 'rejected') throw result.reason;
    values.push(result.value);
  }
  return values;
}

function safeDiagnosticCode(error: unknown, fallback = 'network_error'): string {
  const raw = error instanceof FetchProblem
    ? error.code
    : (error instanceof Error ? error.message : '');
  const normalized = String(raw || '').toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
  if (!normalized || normalized.length > 80) return fallback;
  if (/^(?:http_\d{3}|(?:cloudflare|google|alidns|tencent)_dns_(?:http_\d{3}|status_\d{1,3}|timeout|network_error|invalid_response|response_too_large|no_public_address)|dns_[a-z0-9_]+)$/.test(normalized)) {
    return normalized;
  }
  if (SAFE_DIAGNOSTIC_CODES.has(normalized)) return normalized;
  return fallback;
}

function safeDiagnosticUrl(value: string): string {
  try {
    const url = assertSafePublicUrl(value);
    url.username = '';
    url.password = '';
    url.search = '';
    url.hash = '';
    return url.toString().slice(0, 2_048);
  } catch {
    return '';
  }
}

function diagnostic(
  phase: DetectionDiagnostic['phase'],
  url: string,
  code: string,
  startedAt: number,
  error?: unknown
): DetectionDiagnostic {
  const problem = error instanceof FetchProblem ? error : null;
  return {
    phase,
    url: safeDiagnosticUrl(url),
    code: safeDiagnosticCode(error ?? code, code),
    reachable: error ? Boolean(problem?.reachable) : true,
    httpStatus: problem?.httpStatus ?? 0,
    durationMs: Math.max(0, Date.now() - startedAt)
  };
}

function resolveFetcher(options: DetectionOptions): WorkerFetcher {
  if (options.fetcher) return options.fetcher;
  return (input, init) => fetch(input, init);
}

async function cancelBody(response: Response): Promise<void> {
  try {
    await response.body?.cancel();
  } catch {
    // The body may already be closed by the runtime.
  }
}

async function fetchWithSafeRedirects(
  initialUrl: string,
  accept: string,
  options: DetectionOptions,
  signal: AbortSignal
): Promise<{ response: Response; finalUrl: string }> {
  const fetcher = resolveFetcher(options);
  let currentUrl = assertSafePublicUrl(initialUrl).toString();

  for (let redirectCount = 0; redirectCount <= MAX_REDIRECTS; redirectCount += 1) {
    throwIfAborted(options.signal);
    const current = assertSafePublicUrl(currentUrl);
    let addresses: string[];
    try {
      addresses = await (options.resolver ?? createPublicDnsResolver(fetcher))(current.hostname, signal);
    } catch (error) {
      throwIfAborted(options.signal);
      throw new FetchProblem(signal.aborted ? 'timeout' : safeDiagnosticCode(error, 'dns_error'), false);
    }
    throwIfAborted(options.signal);
    if (!addresses.length || addresses.some((address) => !isPublicIpAddress(address))) {
      throw new FetchProblem('unsafe_or_unresolved_dns', false);
    }
    let response: Response;
    try {
      response = await fetcher(currentUrl, {
        method: 'GET',
        redirect: 'manual',
        signal,
        headers: {
          Accept: accept,
          'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.7',
          'User-Agent': REQUEST_USER_AGENT
        }
      });
    } catch (error) {
      throwIfAborted(options.signal);
      throw new FetchProblem(signal.aborted ? 'timeout' : safeDiagnosticCode(error), false);
    }
    if (options.signal?.aborted) {
      await cancelBody(response);
      throw abortError(options.signal);
    }

    if (response.status < 300 || response.status >= 400) return { response, finalUrl: currentUrl };
    const location = response.headers.get('location');
    await cancelBody(response);
    if (!location) throw new FetchProblem('redirect_without_location', true);
    if (redirectCount === MAX_REDIRECTS) throw new FetchProblem('too_many_redirects', true);

    const redirectedUrl = assertSafePublicUrl(new URL(location, currentUrl).toString()).toString();
    if (!isSameCandidateSite(initialUrl, redirectedUrl)) {
      throw new FetchProblem('cross_site_redirect', true);
    }
    currentUrl = redirectedUrl;
  }
  throw new FetchProblem('too_many_redirects', true);
}

async function readTextLimited(response: Response, maxBytes: number): Promise<string> {
  const declaredLength = Number(response.headers.get('content-length') || 0);
  if (declaredLength > maxBytes) {
    await cancelBody(response);
    throw new FetchProblem('response_too_large', true);
  }
  if (!response.body) return '';

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let received = 0;
  let text = '';
  try {
    while (true) {
      const { value, done } = await reader.read();
      if (done) break;
      received += value.byteLength;
      if (received > maxBytes) throw new FetchProblem('response_too_large', true);
      text += decoder.decode(value, { stream: true });
    }
    return text + decoder.decode();
  } finally {
    if (received > maxBytes) await reader.cancel('response_too_large');
    reader.releaseLock();
  }
}

async function fetchReadableText(
  url: string,
  accept: string,
  maxBytes: number,
  options: DetectionOptions
): Promise<{ text: string; finalUrl: string }> {
  const timeoutMs = Math.max(500, options.timeoutMs ?? 6_000);
  const controller = new AbortController();
  const parentSignal = options.signal;
  const abortFromParent = () => controller.abort(parentSignal?.reason || 'operation_aborted');
  if (parentSignal?.aborted) abortFromParent();
  else parentSignal?.addEventListener('abort', abortFromParent, { once: true });
  const timer = setTimeout(() => controller.abort('timeout'), timeoutMs);
  try {
    throwIfAborted(parentSignal);
    const { response, finalUrl } = await fetchWithSafeRedirects(url, accept, options, controller.signal);
    if (parentSignal?.aborted) {
      await cancelBody(response);
      throw abortError(parentSignal);
    }
    if (!response.ok) {
      await cancelBody(response);
      throw new FetchProblem(`http_${response.status}`, true, response.status);
    }
    const text = await readTextLimited(response, maxBytes);
    throwIfAborted(parentSignal);
    return { text, finalUrl };
  } catch (error) {
    throwIfAborted(parentSignal);
    if (controller.signal.aborted) throw new FetchProblem('timeout', false);
    throw error;
  } finally {
    clearTimeout(timer);
    parentSignal?.removeEventListener('abort', abortFromParent);
  }
}

export function createPublicDnsResolver(fetcher: WorkerFetcher): HostResolver {
  const cache = new Map<string, Promise<string[]>>();

  const queryProvider = async (
    hostname: string,
    provider: 'cloudflare' | 'google' | 'alidns' | 'tencent',
    parentSignal?: AbortSignal
  ): Promise<string[]> => {
    throwIfAborted(parentSignal);
    const query = async (type: 'A' | 'AAAA'): Promise<string[]> => {
      const controller = new AbortController();
      const abortFromParent = () => controller.abort(parentSignal?.reason || 'dns_parent_aborted');
      if (parentSignal?.aborted) abortFromParent();
      else parentSignal?.addEventListener('abort', abortFromParent, { once: true });
      const timer = setTimeout(() => controller.abort('dns_timeout'), DNS_QUERY_TIMEOUT_MS);
      const queryString = `name=${encodeURIComponent(hostname)}&type=${type}`;
      const url = provider === 'cloudflare'
        ? `https://cloudflare-dns.com/dns-query?${queryString}`
        : provider === 'google'
          ? `https://dns.google/resolve?${queryString}`
          : provider === 'alidns'
            ? `https://dns.alidns.com/resolve?${queryString}`
            : `https://doh.pub/dns-query?${queryString}`;
      try {
        const response = await fetcher(url, {
          method: 'GET',
          redirect: 'manual',
          signal: controller.signal,
          headers: { Accept: 'application/dns-json' }
        });
        if (!response.ok) {
          await cancelBody(response);
          throw new Error(`${provider}_dns_http_${response.status}`);
        }
        const parsed = JSON.parse(await readTextLimited(response, DNS_RESPONSE_LIMIT)) as unknown;
        if (!parsed || typeof parsed !== 'object') throw new Error(`${provider}_dns_invalid_response`);
        const data = parsed as { Status?: unknown; Answer?: unknown };
        if (typeof data.Status !== 'number' || !Number.isInteger(data.Status) || data.Status < 0) {
          throw new Error(`${provider}_dns_invalid_response`);
        }
        if (data.Status === 3) return [];
        if (data.Status !== 0) throw new Error(`${provider}_dns_status_${data.Status}`);
        if (data.Answer !== undefined && !Array.isArray(data.Answer)) {
          throw new Error(`${provider}_dns_invalid_response`);
        }
        const wantedType = type === 'A' ? 1 : 28;
        return (data.Answer ?? []).flatMap((answer) => {
          if (!answer || typeof answer !== 'object') return [];
          const record = answer as { type?: unknown; data?: unknown };
          return record.type === wantedType && typeof record.data === 'string' ? [record.data] : [];
        });
      } catch (error) {
        throwIfAborted(parentSignal);
        const message = error instanceof Error ? error.message : '';
        if (new RegExp(`^${provider}_dns_(?:http_\\d{3}|status_\\d{1,3}|invalid_response)$`).test(message)) throw error;
        if (controller.signal.aborted) throw new Error(`${provider}_dns_timeout`);
        if (error instanceof FetchProblem && error.code === 'response_too_large') {
          throw new Error(`${provider}_dns_response_too_large`);
        }
        if (error instanceof SyntaxError) throw new Error(`${provider}_dns_invalid_response`);
        throw new Error(`${provider}_dns_network_error`);
      } finally {
        clearTimeout(timer);
        parentSignal?.removeEventListener('abort', abortFromParent);
      }
    };

    const results = await Promise.allSettled([query('A'), query('AAAA')]);
    throwIfAborted(parentSignal);
    const addresses = results.flatMap((result) => result.status === 'fulfilled' ? result.value : []);
    if (addresses.some((address) => !isPublicIpAddress(address))) throw new Error('dns_private_address');
    const rejected = results.find((result): result is PromiseRejectedResult => result.status === 'rejected');
    if (rejected) throw rejected.reason;
    if (addresses.length) return [...new Set(addresses)];
    throw new Error(`${provider}_dns_no_public_address`);
  };

  return async (hostname: string, signal?: AbortSignal): Promise<string[]> => {
    throwIfAborted(signal);
    const normalized = hostname.toLowerCase().replace(/\.$/, '').replace(/^\[|\]$/g, '');
    if (isPublicIpAddress(normalized)) return [normalized];
    const cached = cache.get(normalized);
    if (cached) return cached;

    const lookup = (async () => {
      let lastError: unknown = new Error('dns_all_providers_failed');
      for (const group of DNS_PROVIDER_GROUPS) {
        throwIfAborted(signal);
        const results = await Promise.allSettled(group.map((provider) => queryProvider(normalized, provider, signal)));
        throwIfAborted(signal);
        const privateResult = results.find((result): result is PromiseRejectedResult =>
          result.status === 'rejected' && result.reason instanceof Error && result.reason.message === 'dns_private_address'
        );
        if (privateResult) throw privateResult.reason;
        const addresses = results.flatMap((result) => result.status === 'fulfilled' ? result.value : []);
        if (addresses.length) return [...new Set(addresses)];
        const rejected = results.filter((result): result is PromiseRejectedResult => result.status === 'rejected');
        if (rejected.length) lastError = rejected[rejected.length - 1].reason;
      }
      throw lastError;
    })();
    cache.set(normalized, lookup);
    void lookup.catch(() => {
      if (cache.get(normalized) === lookup) cache.delete(normalized);
    });
    return lookup;
  };
}

function absoluteUrl(value: unknown, baseUrl: string): string {
  if (!value) return '';
  try {
    return assertSafePublicUrl(new URL(String(value), baseUrl).toString()).toString();
  } catch {
    return '';
  }
}

function getTagAttribute(tag: string, attribute: string): string {
  const escaped = attribute.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const expression = new RegExp(`(?:^|\\s)${escaped}\\s*=\\s*(?:"([^"]*)"|'([^']*)'|([^\\s>]+))`, 'i');
  const match = tag.match(expression);
  return cleanText(match ? match[1] ?? match[2] ?? match[3] ?? '' : '');
}

function findMeta(html: string, key: string): string {
  for (const tag of html.match(/<meta\b[^>]*>/gi) ?? []) {
    const name = getTagAttribute(tag, 'name') || getTagAttribute(tag, 'property') || getTagAttribute(tag, 'itemprop');
    if (name.toLowerCase() === key.toLowerCase()) return getTagAttribute(tag, 'content');
  }
  return '';
}

function findJsonLdSiteName(value: unknown): string {
  if (!value || typeof value !== 'object') return '';
  if (Array.isArray(value)) {
    for (const item of value) {
      const name = findJsonLdSiteName(item);
      if (name) return name;
    }
    return '';
  }
  const node = value as Record<string, unknown>;
  const type = cleanText(Array.isArray(node['@type']) ? node['@type'].join(' ') : node['@type']);
  if (/website|organization|blog|forum|onlinestore/i.test(type)) {
    const ownName = cleanText(node.name || node.alternateName || node.headline);
    if (ownName) return ownName;
  }
  for (const child of Object.values(node)) {
    if (!child || typeof child !== 'object') continue;
    const childName = findJsonLdSiteName(child);
    if (childName) return childName;
  }
  return '';
}

function findDeclaredSiteName(html: string): string {
  const metaName = findMeta(html, 'og:site_name')
    || findMeta(html, 'application-name');
  if (metaName) return metaName;

  for (const match of html.matchAll(/<script\b[^>]*type\s*=\s*["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try {
      const structuredName = findJsonLdSiteName(JSON.parse(match[1]));
      if (structuredName) return structuredName;
    } catch {
      // Ignore malformed structured data and continue with visible brand elements.
    }
  }

  for (const match of html.matchAll(/<(h1|a|div|span)\b([^>]*)>([\s\S]*?)<\/\1>/gi)) {
    const openingTag = `<${match[1]} ${match[2]}>`;
    const identity = `${getTagAttribute(openingTag, 'class')} ${getTagAttribute(openingTag, 'id')}`;
    if (!/(?:site|blog|forum)[-_ ]?(?:title|name)|(?:brand|navbar-brand)|(?:^|\s)logo(?:\s|$)/i.test(identity)) continue;
    let name = cleanText(match[3]);
    if (!name) {
      const imageTag = match[3].match(/<img\b[^>]*>/i)?.[0] || '';
      name = getTagAttribute(imageTag, 'alt');
    }
    if (name && name.length <= 120) return name;
  }
  return '';
}

function fallbackSiteName(url: string): string {
  try {
    return new URL(url).hostname.toLowerCase().replace(/^(?:www|blog)\./, '');
  } catch {
    return '';
  }
}

function findLinkByRel(html: string, relName: string, baseUrl: string): string {
  for (const tag of html.match(/<link\b[^>]*>/gi) ?? []) {
    const rel = getTagAttribute(tag, 'rel').toLowerCase().split(/\s+/);
    if (rel.includes(relName)) return absoluteUrl(getTagAttribute(tag, 'href'), baseUrl);
  }
  return '';
}

interface AvatarCandidate {
  url: string;
  score: number;
  tier: number;
}

function avatarUrlBonus(url: string): number {
  const value = url.toLowerCase();
  let score = 0;
  if (/favicon\.ico(?:[?#]|$)/.test(value)) score -= 80;
  if (/site[-_]?logo|blog[-_]?logo|brand[-_]?logo|\/logo[._/-]/.test(value)) score += 35;
  if (/avatar|author|profile|headimg|portrait|userpic|user-pic/.test(value)) score += 30;
  if (/apple-touch|mask-icon|fluid-icon/.test(value)) score += 20;
  if (/(?:192|180|152|144|128|96|64)(?:x(?:192|180|152|144|128|96|64))?/.test(value)) score += 12;
  if (/\.svg(?:[?#]|$)/.test(value)) score += 8;
  return score;
}

function avatarTierFromScore(score: number): number {
  if (score >= 380) return 5;
  if (score >= 280) return 4;
  if (score >= 190) return 3;
  if (score >= 150) return 2;
  if (score > 1) return 1;
  return 0;
}

function addAvatarCandidate(candidates: AvatarCandidate[], value: unknown, baseUrl: string, score: number): void {
  const url = absoluteUrl(value, baseUrl);
  if (!url) return;
  const existing = candidates.find((candidate) => candidate.url === url);
  const weightedScore = score + avatarUrlBonus(url);
  const tier = /favicon\.ico(?:[?#]|$)/i.test(url) ? 0 : avatarTierFromScore(score);
  if (existing) {
    existing.score = Math.max(existing.score, weightedScore);
    existing.tier = Math.max(existing.tier, tier);
  } else candidates.push({ url, score: weightedScore, tier });
}

function addJsonLdAvatarCandidates(
  value: unknown,
  candidates: AvatarCandidate[],
  baseUrl: string,
  context = ''
): void {
  if (!value || typeof value !== 'object') return;
  if (Array.isArray(value)) {
    for (const item of value) addJsonLdAvatarCandidates(item, candidates, baseUrl, context);
    return;
  }
  const node = value as Record<string, unknown>;
  const type = cleanText(Array.isArray(node['@type']) ? node['@type'].join(' ') : node['@type']).toLowerCase();
  let nextContext = context;
  if (/website|organization|blog|publisher/.test(type)) nextContext = 'site';
  else if (/person|author/.test(type)) nextContext = 'author';

  const addImageValue = (image: unknown, score: number): void => {
    if (typeof image === 'string') addAvatarCandidate(candidates, image, baseUrl, score);
    else if (image && typeof image === 'object') {
      const record = image as Record<string, unknown>;
      addAvatarCandidate(candidates, record.url || record.contentUrl || record['@id'], baseUrl, score);
    }
  };
  if (nextContext === 'site') {
    addImageValue(node.logo, 300);
    addImageValue(node.image, 220);
  } else if (nextContext === 'author') {
    addImageValue(node.image || node.logo, 400);
  }
  for (const [key, child] of Object.entries(node)) {
    if (!child || typeof child !== 'object') continue;
    const childContext = /author|creator/.test(key.toLowerCase())
      ? 'author'
      : (/publisher|provider|organization/.test(key.toLowerCase()) ? 'site' : nextContext);
    addJsonLdAvatarCandidates(child, candidates, baseUrl, childContext);
  }
}

function findBestAvatar(html: string, baseUrl: string): AvatarCandidate {
  const candidates: AvatarCandidate[] = [];
  for (const [key, score] of [
    ['author:image', 400], ['author:avatar', 400], ['profile:image', 390], ['profile:avatar', 390],
    ['og:logo', 300], ['logo', 295], ['application-logo', 290], ['msapplication-tileimage', 110],
    ['og:image', 220], ['og:image:secure_url', 220], ['twitter:image', 210], ['twitter:image:src', 210],
    ['image', 200], ['thumbnail', 190]
  ] as Array<[string, number]>) {
    addAvatarCandidate(candidates, findMeta(html, key), baseUrl, score);
  }

  for (const match of html.matchAll(/<script\b[^>]*type\s*=\s*["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try {
      addJsonLdAvatarCandidates(JSON.parse(match[1]), candidates, baseUrl);
    } catch {
      // Ignore malformed structured data and continue with the other candidates.
    }
  }

  for (const match of html.matchAll(/<img\b[^>]*>/gi)) {
    const tag = match[0];
    const context = html.slice(Math.max(0, (match.index || 0) - 280), (match.index || 0) + tag.length).toLowerCase();
    const srcset = getTagAttribute(tag, 'data-srcset') || getTagAttribute(tag, 'srcset');
    const source = getTagAttribute(tag, 'data-src')
      || getTagAttribute(tag, 'data-original')
      || getTagAttribute(tag, 'data-lazy-src')
      || getTagAttribute(tag, 'src')
      || (srcset ? srcset.split(',')[0].trim().split(/\s+/)[0] : '');
    const identity = [
      getTagAttribute(tag, 'class'), getTagAttribute(tag, 'id'), getTagAttribute(tag, 'alt'),
      getTagAttribute(tag, 'title'), getTagAttribute(tag, 'itemprop'), source
    ].join(' ').toLowerCase();
    if (/comment|reply|review|testimonial/.test(identity)) continue;
    const width = Number.parseInt(getTagAttribute(tag, 'width'), 10) || 0;
    const height = Number.parseInt(getTagAttribute(tag, 'height'), 10) || 0;
    const hasAvatarIdentity = /avatar|headimg|portrait|userpic|user-pic/.test(identity);
    const hasExplicitAuthorIdentity = /author|profile/.test(identity);
    const hasSiteProfileContext = /header-tou|focusinfo|site[-_ ]?author|author[-_ ]?card|profile[-_ ]?card|owner[-_ ]?avatar|about[-_ ]?author/.test(context);
    const hasListAvatarContext = /post[-_ ]?(?:list|meta|card)[-_ ]?avatar|comment[-_ ]?(?:author|avatar|item)|review[-_ ]?avatar|testimonial[-_ ]?avatar/.test(context);
    const hasLoginPlaceholderContext = /wp-login\.php|no-logged|login-link|header-user-avatar/.test(context)
      && (/topavatar|default[-_ ]?avatar|guest[-_ ]?avatar/.test(identity) || (width > 0 && width <= 32) || (height > 0 && height <= 32));
    const isTinyAvatar = hasAvatarIdentity && !hasSiteProfileContext
      && ((width > 0 && width <= 32) || (height > 0 && height <= 32));
    if (hasSiteProfileContext && (hasAvatarIdentity || hasExplicitAuthorIdentity)) {
      addAvatarCandidate(candidates, source, baseUrl, 400);
    } else if (hasListAvatarContext || hasLoginPlaceholderContext || isTinyAvatar) {
      addAvatarCandidate(candidates, source, baseUrl, 80);
    } else if (hasExplicitAuthorIdentity) {
      addAvatarCandidate(candidates, source, baseUrl, 400);
    } else if (hasAvatarIdentity) {
      addAvatarCandidate(candidates, source, baseUrl, 200);
    } else if (/site[-_ ]?logo|site[-_ ]?icon|brand[-_ ]?logo|blog[-_ ]?logo|header[-_ ]?logo|(?:^|[\/_. -])logo(?:[._\/ -]|$)/.test(identity)) {
      addAvatarCandidate(candidates, source, baseUrl, 300);
    } else if (source && !/emoji|sprite|tracking|pixel|spacer|advert|captcha|qrcode|qr-code/.test(identity)) {
      if (!((width && width <= 32) || (height && height <= 32))) {
        addAvatarCandidate(candidates, source, baseUrl, 160 + (width >= 128 || height >= 128 ? 20 : 0));
      }
    }
  }

  for (const tag of html.match(/<link\b[^>]*>/gi) ?? []) {
    const rel = getTagAttribute(tag, 'rel').toLowerCase();
    const href = getTagAttribute(tag, 'href');
    const sizes = getTagAttribute(tag, 'sizes');
    if (!href) continue;
    let score = 0;
    if (/apple-touch-icon/.test(rel)) score = 68;
    else if (/mask-icon|fluid-icon/.test(rel)) score = 62;
    else if (/(?:^|\s)(?:shortcut\s+)?icon(?:\s|$)/.test(rel)) score = 52;
    if (score > 0) addAvatarCandidate(candidates, href, baseUrl, score + (/(?:128|144|152|180|192|256|512)/.test(sizes) ? 15 : 0));
  }

  candidates.sort((left, right) => right.tier - left.tier || right.score - left.score);
  return candidates[0] || { url: '', score: 0, tier: 0 };
}

function friendLinksPageScore(url: string, label: string, baseUrl: string): number {
  if (!url || !isSameCandidateSite(url, baseUrl)) return 0;
  let path = '';
  try {
    path = assertSafePublicUrl(url).pathname.toLowerCase();
  } catch {
    return 0;
  }
  const text = cleanText(label).toLowerCase();
  let score = 0;
  if (/友情链接|友链|友人帐|友人账|朋友们|friend\s*links?|blogroll/.test(text)) score += 80;
  else if (/^(?:links?|friends?)$/.test(text)) score += 60;
  if (/(?:^|\/)(?:friends?|friend[-_]?links?|flinks?|links?|blogroll)(?:[\/.\-_]|$)/.test(path)) score += 50;
  return score;
}

function findFriendLinksPage(html: string, baseUrl: string): string {
  let bestUrl = '';
  let bestScore = 0;
  for (const match of html.matchAll(/<a\b([^>]*)>([\s\S]*?)<\/a>/gi)) {
    const href = absoluteUrl(getTagAttribute(match[1], 'href'), baseUrl);
    const score = friendLinksPageScore(href, match[2], baseUrl);
    if (score > bestScore) {
      bestScore = score;
      bestUrl = href;
    }
  }
  return bestUrl;
}

function findElementTextByClass(html: string, classNames: string[]): string {
  for (const match of html.matchAll(/<([a-z][\w:-]*)\b[^>]*>/gi)) {
    const openingTag = match[0];
    const classes = getTagAttribute(openingTag, 'class').split(/\s+/).filter(Boolean);
    if (!classNames.some((className) => classes.includes(className))) continue;
    const dataText = cleanText(getTagAttribute(openingTag, 'data-text'));
    if (dataText) return dataText;
    const contentStart = (match.index || 0) + openingTag.length;
    const closing = new RegExp(`</${match[1]}\\s*>`, 'i').exec(html.slice(contentStart));
    if (!closing) continue;
    const content = cleanText(html.slice(contentStart, contentStart + closing.index));
    if (content) return content;
  }
  return '';
}

function findArgonBackgroundAvatar(html: string, baseUrl: string): string {
  for (const match of html.matchAll(/<[a-z][\w:-]*\b[^>]*>/gi)) {
    const tag = match[0];
    const identity = `${getTagAttribute(tag, 'id')} ${getTagAttribute(tag, 'class')}`;
    if (!/leftbar_overview_author_image|leftbar-(?:mobile-)?avatar/i.test(identity)) continue;
    const style = getTagAttribute(tag, 'style');
    const background = style.match(/background(?:-image)?\s*:\s*url\(\s*(['"]?)(.*?)\1\s*\)/i)?.[2] || '';
    const direct = getTagAttribute(tag, 'src') || getTagAttribute(tag, 'data-src');
    const avatar = absoluteUrl(background || direct, baseUrl);
    if (avatar) return avatar;
  }
  return '';
}

function extractArgonInfo(html: string, baseUrl: string): {
  detected: boolean;
  primary: string;
  subtitle: string;
  avatar: string;
} {
  const primary = findElementTextByClass(html, ['leftbar-banner-title', 'banner-title-inner']);
  const subtitle = findElementTextByClass(html, ['leftbar-banner-subtitle', 'banner-subtitle']);
  const avatar = findArgonBackgroundAvatar(html, baseUrl);
  const detected = Boolean(
    primary || subtitle || avatar
    || /wp-content\/themes\/argon(?:\/|[-_.])/i.test(html)
    || /class\s*=\s*["'][^"']*leftbar-banner/i.test(html)
  );
  return { detected, primary, subtitle, avatar };
}

function findWordPressApiRoot(html: string, baseUrl: string): string {
  for (const tag of html.match(/<link\b[^>]*>/gi) ?? []) {
    const rel = getTagAttribute(tag, 'rel');
    if (!/api\.w\.org/i.test(rel)) continue;
    const href = absoluteUrl(getTagAttribute(tag, 'href'), baseUrl);
    if (href && isSameCandidateSite(href, baseUrl)) return href;
  }
  const hasWordPressEvidence = /<meta\b[^>]*\bcontent\s*=\s*["'][^"']*wordpress/i.test(html)
    || /\bwp-(?:content|includes)\//i.test(html)
    || /wp-content\/themes\/argon(?:\/|[-_.])/i.test(html);
  return hasWordPressEvidence ? new URL('/wp-json/', baseUrl).toString() : '';
}

function extractHtmlInfo(html: string, baseUrl: string): {
  title: string;
  siteName: string;
  description: string;
  avatar: string;
  avatarTier: number;
  canonicalUrl: string;
  linksPage: string;
  argon: ReturnType<typeof extractArgonInfo>;
  wordpressApiUrl: string;
} {
  const titleMatch = html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i);
  const avatar = findBestAvatar(html, baseUrl);
  return {
    title: cleanText(titleMatch?.[1] || findMeta(html, 'og:title')),
    siteName: findDeclaredSiteName(html),
    description: findMeta(html, 'description') || findMeta(html, 'og:description'),
    avatar: avatar.url,
    avatarTier: avatar.tier,
    canonicalUrl: findLinkByRel(html, 'canonical', baseUrl),
    linksPage: findFriendLinksPage(html, baseUrl),
    argon: extractArgonInfo(html, baseUrl),
    wordpressApiUrl: findWordPressApiRoot(html, baseUrl)
  };
}

async function inspectHtml(candidate: string, options: DetectionOptions): Promise<CandidateResult> {
  const { text, finalUrl } = await fetchReadableText(
    candidate,
    'text/html,application/xhtml+xml',
    SITE_RESPONSE_LIMIT,
    options
  );
  if (!isConfirmableHtmlPage(text)) throw new FetchProblem('challenge_or_invalid_html', true);
  const info = extractHtmlInfo(text, finalUrl);
  const names = info.argon.primary
    ? { primary: info.argon.primary, subtitle: info.argon.subtitle }
    : splitTitle(info.title, info.siteName);
  const canonicalUrl = info.canonicalUrl && isSameCandidateSite(candidate, info.canonicalUrl)
    ? info.canonicalUrl
    : finalUrl;
  const avatar = info.argon.avatar || info.avatar;
  const avatarTier = info.argon.avatar ? 5 : info.avatarTier;
  const nameTier = info.argon.primary ? 4 : (info.siteName ? 3 : (info.title ? 2 : 0));
  return {
    score: 60 + (nameTier > 0 ? 40 : 0) + avatarTier * 2
      + (info.description ? 5 : 0) + (info.linksPage ? 5 : 0) + (info.argon.detected ? 10 : 0),
    accessible: true,
    readable: true,
    detectedUrl: canonicalUrl,
    primary: names.primary || fallbackSiteName(finalUrl),
    subtitle: names.subtitle,
    description: info.description,
    avatar,
    avatarTier,
    nameTier,
    linksPage: info.linksPage,
    source: info.argon.detected ? 'argon' : 'html',
    wordpressApiUrl: info.wordpressApiUrl
  };
}

async function inspectWordPressApi(
  candidate: string,
  apiUrl: string,
  options: DetectionOptions
): Promise<CandidateResult> {
  const { text, finalUrl } = await fetchReadableText(
    apiUrl,
    'application/json',
    WORDPRESS_RESPONSE_LIMIT,
    options
  );
  let data: Record<string, unknown>;
  try {
    data = JSON.parse(text) as Record<string, unknown>;
  } catch {
    throw new FetchProblem('invalid_json', true);
  }
  const name = cleanText(data.name);
  const description = cleanText(data.description);
  const avatar = absoluteUrl(data.site_icon_url, candidate);
  if (!name && !description && !avatar) throw new FetchProblem('invalid_wordpress_response', true);
  const names = splitTitle(name, '');
  const reportedUrl = absoluteUrl(data.home || data.url, finalUrl);
  const detectedUrl = reportedUrl && isSameCandidateSite(candidate, reportedUrl)
    ? reportedUrl
    : candidate;
  const avatarTier = avatar ? (/favicon\.ico(?:[?#]|$)/i.test(avatar) ? 0 : 3) : 0;
  return {
    score: 105 + avatarTier * 2 + (description ? 5 : 0),
    accessible: true,
    readable: true,
    detectedUrl,
    primary: names.primary || fallbackSiteName(detectedUrl),
    subtitle: names.subtitle,
    description,
    avatar,
    avatarTier,
    nameTier: name ? 3 : 0,
    linksPage: '',
    wordpressApiUrl: apiUrl,
    source: 'wordpress'
  };
}

function needsWordPressFallback(result: CandidateResult): boolean {
  return (result.nameTier || 0) === 0
    || (!result.description && (result.avatarTier || 0) < 2);
}

function mergeCandidateResults(html: CandidateResult, wordpress: CandidateResult): CandidateResult {
  const useWordPressName = (wordpress.nameTier || 0) > (html.nameTier || 0);
  const useWordPressAvatar = Boolean(wordpress.avatar)
    && (!html.avatar || (wordpress.avatarTier || 0) > (html.avatarTier || 0));
  const useWordPressDescription = !html.description && Boolean(wordpress.description);
  return {
    ...html,
    score: Math.max(html.score, wordpress.score)
      + (useWordPressName ? 5 : 0)
      + (useWordPressAvatar ? 3 : 0)
      + (useWordPressDescription ? 2 : 0),
    detectedUrl: html.detectedUrl || wordpress.detectedUrl,
    primary: useWordPressName ? wordpress.primary : html.primary,
    subtitle: useWordPressName ? (wordpress.subtitle || html.subtitle) : html.subtitle,
    description: useWordPressDescription ? wordpress.description : html.description,
    avatar: useWordPressAvatar ? wordpress.avatar : html.avatar,
    avatarTier: useWordPressAvatar ? wordpress.avatarTier : html.avatarTier,
    nameTier: Math.max(html.nameTier || 0, wordpress.nameTier || 0),
    source: html.source === 'argon'
      ? 'argon'
      : (useWordPressName || useWordPressAvatar || useWordPressDescription ? 'wordpress' : html.source)
  };
}

async function inspectCandidateHtml(candidate: string, options: DetectionOptions): Promise<CandidateResult> {
  let accessible = false;
  const diagnostics: DetectionDiagnostic[] = [];
  const htmlStartedAt = Date.now();
  try {
    const html = await inspectHtml(candidate, options);
    diagnostics.push(diagnostic('html', candidate, 'ok', htmlStartedAt));
    return { ...html, diagnostics };
  } catch (error) {
    throwIfAborted(options.signal);
    accessible ||= error instanceof FetchProblem && error.reachable;
    diagnostics.push(diagnostic('html', candidate, 'html_unreadable', htmlStartedAt, error));
  }
  return {
    score: 0,
    accessible,
    readable: false,
    detectedUrl: candidate,
    primary: '',
    subtitle: '',
    description: '',
    avatar: '',
    linksPage: '',
    wordpressApiUrl: '',
    source: 'unknown',
    diagnostics
  };
}

async function enrichCandidateWithWordPress(
  candidate: string,
  result: CandidateResult,
  options: DetectionOptions
): Promise<CandidateResult> {
  const wordpressApiUrl = result.wordpressApiUrl;
  if (!wordpressApiUrl || (result.readable && !needsWordPressFallback(result))) return result;

  const diagnostics = [...(result.diagnostics ?? [])];
  const wordpressStartedAt = Date.now();
  try {
    const wordpress = await inspectWordPressApi(candidate, wordpressApiUrl, options);
    diagnostics.push(diagnostic('wordpress', wordpressApiUrl, 'ok', wordpressStartedAt));
    return {
      ...(result.readable ? mergeCandidateResults(result, wordpress) : wordpress),
      diagnostics
    };
  } catch (error) {
    throwIfAborted(options.signal);
    diagnostics.push(diagnostic('wordpress', wordpressApiUrl, 'wordpress_unreadable', wordpressStartedAt, error));
    return {
      ...result,
      accessible: result.accessible || (error instanceof FetchProblem && error.reachable),
      diagnostics
    };
  }
}

export async function detectSite(value: string, options: DetectionOptions = {}): Promise<SiteDetection> {
  throwIfAborted(options.signal);
  const candidates = buildCandidates(value);
  const fetcher = resolveFetcher(options);
  const resolvedOptions = {
    ...options,
    fetcher,
    resolver: options.resolver ?? createPublicDnsResolver(fetcher)
  };
  let evaluated: CandidateResult[] = [];

  for (let offset = 0; offset < candidates.length; offset += CANDIDATE_CONCURRENCY) {
    throwIfAborted(options.signal);
    const batch = candidates.slice(offset, offset + CANDIDATE_CONCURRENCY);
    evaluated.push(...await waitForBatch(batch.map((candidate) => inspectCandidateHtml(candidate, resolvedOptions))));
    // Candidates are ordered by preferred protocol. Once a complete batch has
    // yielded sufficient HTML, slower protocol fallbacks cannot improve the
    // required metadata enough to justify delaying the backlink check.
    if (evaluated.some((result) => result.readable && !needsWordPressFallback(result))) break;
  }

  const hasSufficientHtml = evaluated.some((result) => result.readable && !needsWordPressFallback(result));
  if (!hasSufficientHtml) {
    const enriched: CandidateResult[] = [];
    for (let offset = 0; offset < candidates.length; offset += CANDIDATE_CONCURRENCY) {
      throwIfAborted(options.signal);
      const batchCandidates = candidates.slice(offset, offset + CANDIDATE_CONCURRENCY);
      const batchResults = evaluated.slice(offset, offset + CANDIDATE_CONCURRENCY);
      enriched.push(...await waitForBatch(batchResults.map((result, index) =>
        enrichCandidateWithWordPress(batchCandidates[index], result, resolvedOptions)
      )));
    }
    evaluated = enriched;
  }

  const attempts: SiteDetection['attempts'] = evaluated.map((result, index) => ({
    url: candidates[index],
    accessible: result.accessible,
    readable: result.readable,
    diagnostics: result.diagnostics ?? []
  }));
  const anyAccessible = evaluated.some((result) => result.accessible);
  const readable = evaluated.filter((result) => result.readable);
  readable.sort((left, right) =>
    right.score - left.score || (right.avatarTier || 0) - (left.avatarTier || 0)
  );
  const best = readable[0];
  if (!best) {
    return {
      state: 'unknown',
      accessible: anyAccessible,
      readable: false,
      detectedUrl: candidates[0],
      primary: '',
      subtitle: '',
      description: '',
      avatar: '',
      linksPage: '',
      wordpressApiUrl: '',
      source: 'unknown',
      attempts
    };
  }
  const bestAvatar = readable.reduce((selected, candidate) => {
    if (!candidate.avatar) return selected;
    if (!selected.avatar) return candidate;
    return (candidate.avatarTier || 0) > (selected.avatarTier || 0) ? candidate : selected;
  }, best);
  const wordpressApiUrl = readable.find((candidate) => candidate.wordpressApiUrl)?.wordpressApiUrl || '';
  return { state: 'ok', ...best, avatar: bestAvatar.avatar, wordpressApiUrl, attempts };
}

function extractLinks(html: string, baseUrl: string): string[] {
  const links: string[] = [];
  for (const tag of html.match(/<a\b[^>]*>/gi) ?? []) {
    const href = absoluteUrl(getTagAttribute(tag, 'href'), baseUrl);
    if (href) links.push(href);
  }
  return links;
}

export async function checkBacklink(
  linksPage: string,
  targetSiteUrl: string,
  localSiteUrl: string,
  options: DetectionOptions = {}
): Promise<BacklinkDetection> {
  let page: URL;
  try {
    page = assertSafePublicUrl(linksPage);
    assertSafePublicUrl(localSiteUrl);
    if (!isSameCandidateSite(targetSiteUrl, page.toString())) {
      return {
        state: 'unknown',
        checkedUrl: '',
        diagnostics: [{
          phase: 'backlink',
          url: safeDiagnosticUrl(linksPage),
          code: 'links_page_site_mismatch',
          reachable: false,
          httpStatus: 0,
          durationMs: 0
        }]
      };
    }
  } catch {
    return {
      state: 'unknown',
      checkedUrl: '',
      diagnostics: [{
        phase: 'backlink',
        url: safeDiagnosticUrl(linksPage),
        code: 'invalid_links_page',
        reachable: false,
        httpStatus: 0,
        durationMs: 0
      }]
    };
  }

  const startedAt = Date.now();
  try {
    const fetcher = resolveFetcher(options);
    const resolvedOptions = {
      ...options,
      fetcher,
      resolver: options.resolver ?? createPublicDnsResolver(fetcher)
    };
    const { text, finalUrl } = await fetchReadableText(
      page.toString(),
      'text/html,application/xhtml+xml',
      BACKLINK_RESPONSE_LIMIT,
      resolvedOptions
    );
    if (!isConfirmableHtmlPage(text)) {
      return {
        state: 'unknown',
        checkedUrl: '',
        diagnostics: [diagnostic('backlink', finalUrl, 'challenge_or_invalid_html', startedAt)]
      };
    }
    const localHost = normalizeComparableHostname(localSiteUrl);
    const found = extractLinks(text, finalUrl).some((url) =>
      normalizeComparableHostname(url) === localHost || isSameCandidateSite(url, localSiteUrl)
    );
    return {
      state: found ? 'found' : 'missing',
      checkedUrl: finalUrl,
      diagnostics: [diagnostic('backlink', finalUrl, found ? 'backlink_found' : 'backlink_missing', startedAt)]
    };
  } catch (error) {
    throwIfAborted(options.signal);
    return {
      state: 'unknown',
      checkedUrl: '',
      diagnostics: [diagnostic('backlink', page.toString(), 'links_page_unreadable', startedAt, error)]
    };
  }
}

function buildWordPressPagesApiUrl(targetSiteUrl: string, wordpressApiRoot: string): URL | null {
  try {
    const apiRoot = assertSafePublicUrl(wordpressApiRoot);
    if (!isSameCandidateSite(apiRoot.toString(), targetSiteUrl)) return null;
    if (apiRoot.searchParams.has('rest_route')) {
      apiRoot.searchParams.set('rest_route', '/wp/v2/pages');
      return apiRoot;
    }
    if (!apiRoot.pathname.endsWith('/')) apiRoot.pathname += '/';
    apiRoot.search = '';
    apiRoot.hash = '';
    return new URL('wp/v2/pages', apiRoot);
  } catch {
    return null;
  }
}

function normalizeComparablePagePath(value: string): string {
  try {
    let pathname = assertSafePublicUrl(value).pathname.replace(/\/{2,}/g, '/');
    try {
      pathname = decodeURIComponent(pathname);
    } catch {
      // Keep the encoded path when it is not valid percent-encoding.
    }
    return pathname === '/' ? '/' : pathname.replace(/\/+$/, '');
  } catch {
    return '';
  }
}

function isSameCanonicalPage(left: string, right: string): boolean {
  const leftPath = normalizeComparablePagePath(left);
  const rightPath = normalizeComparablePagePath(right);
  return Boolean(leftPath && rightPath && leftPath === rightPath && isSameCandidateSite(left, right));
}

function backlinkFromExtractedUrls(
  urls: string[],
  localSiteUrl: string,
  checkedUrl: string,
  phase: DetectionDiagnostic['phase'],
  codePrefix: string,
  startedAt: number
): BacklinkDetection | null {
  const localHost = normalizeComparableHostname(localSiteUrl);
  const found = urls.some((url) =>
    normalizeComparableHostname(url) === localHost || isSameCandidateSite(url, localSiteUrl)
  );
  if (!found) return null;
  return {
    state: 'found',
    checkedUrl,
    diagnostics: [diagnostic(phase, checkedUrl, `${codePrefix}_found`, startedAt)]
  };
}

async function checkWordPressPageContent(
  linksPage: string,
  targetSiteUrl: string,
  localSiteUrl: string,
  wordpressApiRoot: string,
  options: DetectionOptions
): Promise<BacklinkDetection> {
  const apiUrl = buildWordPressPagesApiUrl(targetSiteUrl, wordpressApiRoot);
  if (!apiUrl) return { state: 'unknown', checkedUrl: '', diagnostics: [] };
  let slug = '';
  try {
    const segments = assertSafePublicUrl(linksPage).pathname.split('/').filter(Boolean);
    slug = decodeURIComponent(segments[segments.length - 1] || '');
  } catch {
    return { state: 'unknown', checkedUrl: '', diagnostics: [] };
  }
  if (!slug) return { state: 'unknown', checkedUrl: '', diagnostics: [] };
  apiUrl.searchParams.set('slug', slug);
  apiUrl.searchParams.set('per_page', '3');
  apiUrl.searchParams.set('_fields', 'link,slug,title,content');
  const startedAt = Date.now();
  try {
    const { text, finalUrl } = await fetchReadableText(
      apiUrl.toString(),
      'application/json',
      WORDPRESS_RESPONSE_LIMIT,
      options
    );
    const parsed = JSON.parse(text) as unknown;
    if (!Array.isArray(parsed)) throw new FetchProblem('invalid_wordpress_response', true);
    for (const value of parsed.slice(0, 3)) {
      if (!value || typeof value !== 'object') continue;
      const page = value as Record<string, unknown>;
      const pageUrl = typeof page.link === 'string' ? absoluteUrl(page.link, targetSiteUrl) : '';
      if (!pageUrl || !isSameCanonicalPage(pageUrl, linksPage)) continue;
      const contentValue = page.content && typeof page.content === 'object'
        ? (page.content as Record<string, unknown>).rendered
        : page.content;
      const content = typeof contentValue === 'string' ? contentValue : '';
      const result = backlinkFromExtractedUrls(
        extractLinks(content, pageUrl),
        localSiteUrl,
        pageUrl,
        'wordpress',
        'wordpress_page',
        startedAt
      );
      if (result) return result;
    }
    return {
      state: 'unknown',
      checkedUrl: '',
      diagnostics: [diagnostic('wordpress', finalUrl, 'wordpress_page_inconclusive', startedAt)]
    };
  } catch (error) {
    throwIfAborted(options.signal);
    return {
      state: 'unknown',
      checkedUrl: '',
      diagnostics: [diagnostic('wordpress', apiUrl.toString(), 'wordpress_unreadable', startedAt, error)]
    };
  }
}

async function checkWordPressOpml(
  targetSiteUrl: string,
  localSiteUrl: string,
  options: DetectionOptions
): Promise<BacklinkDetection> {
  let opmlUrl = '';
  try {
    opmlUrl = new URL('/wp-links-opml.php', assertSafePublicUrl(targetSiteUrl)).toString();
  } catch {
    return { state: 'unknown', checkedUrl: '', diagnostics: [] };
  }
  const startedAt = Date.now();
  try {
    const { text, finalUrl } = await fetchReadableText(
      opmlUrl,
      'application/xml,text/xml;q=0.9,*/*;q=0.5',
      WORDPRESS_RESPONSE_LIMIT,
      options
    );
    if (!/<opml\b/i.test(text)) throw new FetchProblem('invalid_wordpress_response', true);
    const urls = (text.match(/<outline\b[^>]*>/gi) ?? [])
      .map((tag) => absoluteUrl(getTagAttribute(tag, 'htmlUrl'), targetSiteUrl))
      .filter(Boolean);
    const result = backlinkFromExtractedUrls(
      urls,
      localSiteUrl,
      finalUrl,
      'wordpress',
      'wordpress_opml',
      startedAt
    );
    if (result) return result;
    return {
      state: 'unknown',
      checkedUrl: '',
      diagnostics: [diagnostic('wordpress', finalUrl, 'wordpress_opml_inconclusive', startedAt)]
    };
  } catch (error) {
    throwIfAborted(options.signal);
    return {
      state: 'unknown',
      checkedUrl: '',
      diagnostics: [diagnostic('wordpress', opmlUrl, 'wordpress_unreadable', startedAt, error)]
    };
  }
}

export async function checkBacklinkWithWordPressFallbacks(
  linksPage: string,
  targetSiteUrl: string,
  localSiteUrl: string,
  options: BacklinkDiscoveryOptions = {}
): Promise<BacklinkDetection> {
  const direct = await checkBacklink(linksPage, targetSiteUrl, localSiteUrl, options);
  if (direct.state === 'found' || !options.wordpressApiUrl) return direct;

  const fallbackOptions = {
    ...options,
    timeoutMs: Math.min(options.timeoutMs ?? 6_000, 2_000)
  };
  const [page, opml] = await waitForBatch([
    checkWordPressPageContent(
      linksPage,
      targetSiteUrl,
      localSiteUrl,
      options.wordpressApiUrl,
      fallbackOptions
    ),
    checkWordPressOpml(targetSiteUrl, localSiteUrl, fallbackOptions)
  ]);
  const diagnostics = [...direct.diagnostics, ...page.diagnostics, ...opml.diagnostics].slice(0, 64);
  const found = [page, opml].find((result) => result.state === 'found');
  if (found) return { ...found, diagnostics };
  if (direct.state === 'missing') return { ...direct, diagnostics };
  return { state: 'unknown', checkedUrl: '', diagnostics };
}

function isConfirmedFriendLinksPage(html: string, finalUrl: string): boolean {
  if (!isConfirmableHtmlPage(html)) return false;
  const identity = [
    cleanText(html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i)?.[1] || ''),
    ...Array.from(html.matchAll(/<h[1-3]\b[^>]*>([\s\S]*?)<\/h[1-3]>/gi), (match) => cleanText(match[1]))
  ].filter(Boolean).join(' ');
  const semanticMarkup = /(?:class|id)\s*=\s*["'][^"']*(?:friend[-_ ]?links?|flinks?|blogroll)[^"']*["']/i.test(html);
  return semanticMarkup || friendLinksPageScore(finalUrl, identity, finalUrl) >= 80;
}

async function discoverWordPressPageCandidates(
  targetSiteUrl: string,
  wordpressApiRoot: string,
  options: DetectionOptions
): Promise<{ urls: string[]; diagnostics: DetectionDiagnostic[] }> {
  const apiUrl = buildWordPressPagesApiUrl(targetSiteUrl, wordpressApiRoot);
  if (!apiUrl) return { urls: [], diagnostics: [] };
  apiUrl.searchParams.set('per_page', '20');
  apiUrl.searchParams.set('_fields', 'link,slug,title');
  const startedAt = Date.now();
  try {
    const { text, finalUrl } = await fetchReadableText(
      apiUrl.toString(),
      'application/json',
      WORDPRESS_RESPONSE_LIMIT,
      options
    );
    const parsed = JSON.parse(text) as unknown;
    if (!Array.isArray(parsed)) throw new FetchProblem('invalid_wordpress_response', true);
    const scored: Array<{ url: string; score: number }> = [];
    for (const value of parsed.slice(0, 20)) {
      if (!value || typeof value !== 'object') continue;
      const page = value as Record<string, unknown>;
      const titleValue = page.title && typeof page.title === 'object'
        ? (page.title as Record<string, unknown>).rendered
        : page.title;
      const title = cleanText(titleValue);
      const slug = cleanText(page.slug);
      const pageUrl = absoluteUrl(page.link || (slug ? `/${slug}/` : ''), targetSiteUrl);
      if (!pageUrl || !isSameCandidateSite(pageUrl, targetSiteUrl)) continue;
      const score = friendLinksPageScore(pageUrl, `${title} ${slug}`, targetSiteUrl);
      if (score >= 80) scored.push({ url: pageUrl, score });
    }
    scored.sort((left, right) => right.score - left.score);
    return {
      urls: [...new Set(scored.map((candidate) => candidate.url))].slice(0, 3),
      diagnostics: [diagnostic('wordpress', finalUrl, 'ok', startedAt)]
    };
  } catch (error) {
    throwIfAborted(options.signal);
    return {
      urls: [],
      diagnostics: [diagnostic('wordpress', apiUrl.toString(), 'wordpress_unreadable', startedAt, error)]
    };
  }
}

async function inspectDiscoveredBacklinkCandidate(
  pageUrl: string,
  localSiteUrl: string,
  options: DetectionOptions
): Promise<DiscoveredBacklinkDetection> {
  const startedAt = Date.now();
  try {
    const { text, finalUrl } = await fetchReadableText(
      pageUrl,
      'text/html,application/xhtml+xml',
      BACKLINK_RESPONSE_LIMIT,
      options
    );
    if (!isConfirmedFriendLinksPage(text, finalUrl)) {
      return {
        state: 'unknown',
        linksPage: '',
        checkedUrl: '',
        diagnostics: [diagnostic('backlink', finalUrl, 'links_page_not_confirmed', startedAt)]
      };
    }
    const localHost = normalizeComparableHostname(localSiteUrl);
    const found = extractLinks(text, finalUrl).some((url) =>
      normalizeComparableHostname(url) === localHost || isSameCandidateSite(url, localSiteUrl)
    );
    return {
      state: found ? 'found' : 'missing',
      linksPage: finalUrl,
      checkedUrl: finalUrl,
      diagnostics: [diagnostic('backlink', finalUrl, found ? 'backlink_found' : 'backlink_missing', startedAt)]
    };
  } catch (error) {
    throwIfAborted(options.signal);
    return {
      state: 'unknown',
      linksPage: '',
      checkedUrl: '',
      diagnostics: [diagnostic('backlink', pageUrl, 'links_page_unreadable', startedAt, error)]
    };
  }
}

export async function discoverAndCheckBacklink(
  targetSiteUrl: string,
  localSiteUrl: string,
  options: BacklinkDiscoveryOptions = {}
): Promise<DiscoveredBacklinkDetection> {
  throwIfAborted(options.signal);
  let target: URL;
  try {
    target = assertSafePublicUrl(targetSiteUrl);
    assertSafePublicUrl(localSiteUrl);
  } catch {
    return { state: 'unknown', linksPage: '', checkedUrl: '', diagnostics: [] };
  }
  const fetcher = resolveFetcher(options);
  const resolvedOptions = {
    ...options,
    timeoutMs: Math.min(options.timeoutMs ?? 6_000, 4_000),
    fetcher,
    resolver: options.resolver ?? createPublicDnsResolver(fetcher)
  };
  const wordpress = options.wordpressApiUrl
    ? await discoverWordPressPageCandidates(target.toString(), options.wordpressApiUrl, resolvedOptions)
    : { urls: [], diagnostics: [] };
  throwIfAborted(options.signal);
  const common = FRIEND_LINK_DISCOVERY_PATHS.map((path) => new URL(path, target).toString());
  const candidates = [...new Set([...wordpress.urls, ...common])]
    .filter((url) => isSameCandidateSite(url, target.toString()))
    .slice(0, FRIEND_LINK_DISCOVERY_LIMIT);
  const diagnostics = [...wordpress.diagnostics];
  let missing: DiscoveredBacklinkDetection | null = null;

  for (let offset = 0; offset < candidates.length; offset += CANDIDATE_CONCURRENCY) {
    throwIfAborted(options.signal);
    const batch = candidates.slice(offset, offset + CANDIDATE_CONCURRENCY);
    const results = await waitForBatch(batch.map((url) =>
      inspectDiscoveredBacklinkCandidate(url, localSiteUrl, resolvedOptions)
    ));
    for (const result of results) {
      diagnostics.push(...result.diagnostics);
      if (result.state === 'found') {
        return { ...result, diagnostics: diagnostics.slice(0, 64) };
      }
      if (!missing && result.state === 'missing') missing = result;
    }
  }

  if (missing) return { ...missing, diagnostics: diagnostics.slice(0, 64) };
  return { state: 'unknown', linksPage: '', checkedUrl: '', diagnostics: diagnostics.slice(0, 64) };
}

function isConfirmableHtmlPage(html: string): boolean {
  const rawLower = html.toLowerCase();
  const normalized = cleanText(html).toLowerCase();
  const hasDocumentStructure = /(?:<!doctype\s+html|<html\b|<body\b|<a\b)/i.test(html);
  const hasMeaningfulContent = Boolean(normalized)
    || /<img\b[^>]*(?:src|srcset|data-src|alt)\s*=/i.test(html)
    || /<meta\b[^>]*(?:content)\s*=/i.test(html);
  if (!hasDocumentStructure || !hasMeaningfulContent) return false;

  const hardChallenge = [
    'just a moment',
    'checking your browser',
    'attention required',
    'cloudflare ray id',
    'cf-chl-',
    'enable javascript and cookies'
  ].some((marker) => normalized.includes(marker) || rawLower.includes(marker));
  if (hardChallenge) return false;

  const title = cleanText(html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i)?.[1] || '');
  const heading = cleanText(html.match(/<h1\b[^>]*>([\s\S]*?)<\/h1>/i)?.[1] || '');
  const isVerificationHeading = (value: string): boolean =>
    /^(?:captcha|人机验证|访问验证|安全验证|请完成验证|验证码)(?:\s*[-–—|:：]\s*[^-–—|:：]{1,80})?$/i.test(value);
  return !isVerificationHeading(title) && !isVerificationHeading(heading);
}
