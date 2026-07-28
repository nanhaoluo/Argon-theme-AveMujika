import {
  assertSafePublicUrl,
  buildCandidates,
  cleanText,
  isPublicIpAddress,
  isSameCandidateSite,
  normalizeComparableHostname,
  splitTitle
} from './domain';

const SITE_RESPONSE_LIMIT = 384 * 1024;
const WORDPRESS_RESPONSE_LIMIT = 128 * 1024;
const BACKLINK_RESPONSE_LIMIT = 512 * 1024;
const MAX_REDIRECTS = 2;

export type WorkerFetcher = (input: string, init?: RequestInit) => Promise<Response>;
export type HostResolver = (hostname: string, signal?: AbortSignal) => Promise<string[]>;
export type DetectionState = 'ok' | 'unknown';
export type BacklinkState = 'found' | 'missing' | 'unknown';

export interface DetectionOptions {
  fetcher?: WorkerFetcher;
  resolver?: HostResolver;
  timeoutMs?: number;
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
  source: 'wordpress' | 'html' | 'unknown';
  attempts: Array<{ url: string; accessible: boolean; readable: boolean }>;
}

export interface BacklinkDetection {
  state: BacklinkState;
  checkedUrl: string;
}

interface CandidateResult extends Omit<SiteDetection, 'state' | 'attempts'> {
  score: number;
}

class FetchProblem extends Error {
  constructor(message: string, readonly reachable: boolean) {
    super(message);
  }
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
    const current = assertSafePublicUrl(currentUrl);
    const addresses = await (options.resolver ?? createPublicDnsResolver(fetcher))(current.hostname, signal);
    if (!addresses.length || addresses.some((address) => !isPublicIpAddress(address))) {
      throw new FetchProblem('unsafe_or_unresolved_dns', false);
    }
    let response: Response;
    try {
      response = await fetcher(currentUrl, {
        method: 'GET',
        redirect: 'manual',
        signal,
        headers: { Accept: accept }
      });
    } catch (error) {
      throw new FetchProblem(error instanceof Error ? error.message : 'network_error', false);
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
  if (declaredLength > maxBytes) throw new FetchProblem('response_too_large', true);
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
  const timer = setTimeout(() => controller.abort('timeout'), timeoutMs);
  try {
    const { response, finalUrl } = await fetchWithSafeRedirects(url, accept, options, controller.signal);
    if (!response.ok) {
      await cancelBody(response);
      throw new FetchProblem(`http_${response.status}`, true);
    }
    return { text: await readTextLimited(response, maxBytes), finalUrl };
  } finally {
    clearTimeout(timer);
  }
}

export function createPublicDnsResolver(fetcher: WorkerFetcher): HostResolver {
  const cache = new Map<string, Promise<string[]>>();
  return async (hostname: string, signal?: AbortSignal): Promise<string[]> => {
    const normalized = hostname.toLowerCase().replace(/\.$/, '').replace(/^\[|\]$/g, '');
    if (isPublicIpAddress(normalized)) return [normalized];
    const cached = cache.get(normalized);
    if (cached) return cached;

    const lookup = Promise.all(['A', 'AAAA'].map(async (type) => {
      const url = `https://cloudflare-dns.com/dns-query?name=${encodeURIComponent(normalized)}&type=${type}`;
      const response = await fetcher(url, {
        method: 'GET',
        redirect: 'error',
        signal,
        headers: { Accept: 'application/dns-json' }
      });
      if (!response.ok) throw new Error(`dns_http_${response.status}`);
      const data = await response.json() as { Status?: number; Answer?: Array<{ type?: number; data?: string }> };
      if (data.Status !== 0 && data.Status !== undefined) return [];
      const wantedType = type === 'A' ? 1 : 28;
      return (data.Answer ?? [])
        .filter((answer) => answer.type === wantedType && typeof answer.data === 'string')
        .map((answer) => String(answer.data));
    })).then((groups) => {
      const addresses = groups.flat();
      if (!addresses.length) throw new Error('dns_no_public_address');
      if (addresses.some((address) => !isPublicIpAddress(address))) throw new Error('dns_private_address');
      return addresses;
    });
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

function findLinkByRel(html: string, relName: string, baseUrl: string): string {
  for (const tag of html.match(/<link\b[^>]*>/gi) ?? []) {
    const rel = getTagAttribute(tag, 'rel').toLowerCase().split(/\s+/);
    if (rel.includes(relName)) return absoluteUrl(getTagAttribute(tag, 'href'), baseUrl);
  }
  return '';
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

function extractHtmlInfo(html: string, baseUrl: string): {
  title: string;
  siteName: string;
  description: string;
  avatar: string;
  canonicalUrl: string;
  linksPage: string;
} {
  const titleMatch = html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i);
  return {
    title: cleanText(titleMatch?.[1] || findMeta(html, 'og:title')),
    siteName: findMeta(html, 'og:site_name') || findMeta(html, 'application-name'),
    description: findMeta(html, 'description') || findMeta(html, 'og:description'),
    avatar:
      findLinkByRel(html, 'apple-touch-icon', baseUrl) ||
      findLinkByRel(html, 'icon', baseUrl) ||
      absoluteUrl('/favicon.ico', baseUrl),
    canonicalUrl: findLinkByRel(html, 'canonical', baseUrl),
    linksPage: findFriendLinksPage(html, baseUrl)
  };
}

async function inspectWordPress(candidate: string, options: DetectionOptions): Promise<CandidateResult> {
  const restUrl = new URL('/wp-json/', candidate).toString();
  const { text, finalUrl } = await fetchReadableText(
    restUrl,
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
  if (!name) throw new FetchProblem('invalid_wordpress_response', true);
  const description = cleanText(data.description);
  const names = splitTitle(name, '');
  const reportedUrl = absoluteUrl(data.home || data.url, finalUrl);
  return {
    score: 120,
    accessible: true,
    readable: true,
    detectedUrl: reportedUrl && isSameCandidateSite(candidate, reportedUrl) ? reportedUrl : candidate,
    primary: names.primary,
    subtitle: names.subtitle || description,
    description,
    avatar: absoluteUrl(data.site_icon_url, candidate),
    linksPage: '',
    source: 'wordpress'
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
  const names = splitTitle(info.title, info.siteName);
  const canonicalUrl = info.canonicalUrl && isSameCandidateSite(candidate, info.canonicalUrl)
    ? info.canonicalUrl
    : finalUrl;
  return {
    score: names.primary ? 100 : 60,
    accessible: true,
    readable: true,
    detectedUrl: canonicalUrl,
    primary: names.primary,
    subtitle: names.subtitle,
    description: info.description,
    avatar: info.avatar,
    linksPage: info.linksPage,
    source: 'html'
  };
}

async function inspectCandidate(candidate: string, options: DetectionOptions): Promise<CandidateResult> {
  let accessible = false;
  try {
    const wordpress = await inspectWordPress(candidate, options);
    try {
      const html = await inspectHtml(wordpress.detectedUrl || candidate, options);
      return {
        ...wordpress,
        description: wordpress.description || html.description,
        avatar: wordpress.avatar || html.avatar,
        linksPage: html.linksPage
      };
    } catch {
      return {
        ...wordpress,
        avatar: wordpress.avatar || absoluteUrl('/favicon.ico', wordpress.detectedUrl || candidate)
      };
    }
  } catch (error) {
    accessible ||= error instanceof FetchProblem && error.reachable;
  }
  try {
    return await inspectHtml(candidate, options);
  } catch (error) {
    accessible ||= error instanceof FetchProblem && error.reachable;
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
    source: 'unknown'
  };
}

export async function detectSite(value: string, options: DetectionOptions = {}): Promise<SiteDetection> {
  const candidates = buildCandidates(value);
  const fetcher = resolveFetcher(options);
  const resolvedOptions = {
    ...options,
    fetcher,
    resolver: options.resolver ?? createPublicDnsResolver(fetcher)
  };
  const attempts: SiteDetection['attempts'] = [];
  const readable: CandidateResult[] = [];
  let anyAccessible = false;

  for (const candidate of candidates) {
    const result = await inspectCandidate(candidate, resolvedOptions);
    anyAccessible ||= result.accessible;
    attempts.push({ url: candidate, accessible: result.accessible, readable: result.readable });
    if (result.readable) readable.push(result);
  }

  readable.sort((left, right) => right.score - left.score);
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
      source: 'unknown',
      attempts
    };
  }
  return { state: 'ok', ...best, attempts };
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
    if (!isSameCandidateSite(targetSiteUrl, page.toString())) return { state: 'unknown', checkedUrl: '' };
  } catch {
    return { state: 'unknown', checkedUrl: '' };
  }

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
    if (!isConfirmableHtmlPage(text)) return { state: 'unknown', checkedUrl: finalUrl };
    const localHost = normalizeComparableHostname(localSiteUrl);
    const found = extractLinks(text, finalUrl).some((url) => normalizeComparableHostname(url) === localHost);
    return { state: found ? 'found' : 'missing', checkedUrl: finalUrl };
  } catch {
    return { state: 'unknown', checkedUrl: page.toString() };
  }
}

function isConfirmableHtmlPage(html: string): boolean {
  const rawLower = html.toLowerCase();
  const normalized = cleanText(html).toLowerCase();
  if (!normalized || !/(?:<!doctype\s+html|<html\b|<body\b|<a\b)/i.test(html)) return false;
  return ![
    'just a moment',
    'checking your browser',
    'attention required',
    'cloudflare ray id',
    'cf-chl-',
    'captcha',
    'enable javascript and cookies',
    '人机验证',
    '访问验证',
    '安全验证',
    '请完成验证',
    '验证码'
  ].some((marker) => normalized.includes(marker) || rawLower.includes(marker));
}
