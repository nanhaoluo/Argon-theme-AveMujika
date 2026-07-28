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
const DNS_RESPONSE_LIMIT = 64 * 1024;
const MAX_REDIRECTS = 2;
const DNS_QUERY_TIMEOUT_MS = 2_000;
const CANDIDATE_CONCURRENCY = 3;

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
  avatarTier?: number;
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

  const queryProvider = async (
    hostname: string,
    provider: 'cloudflare' | 'google',
    parentSignal?: AbortSignal
  ): Promise<string[]> => {
    const query = async (type: 'A' | 'AAAA'): Promise<string[]> => {
      const controller = new AbortController();
      const abortFromParent = () => controller.abort(parentSignal?.reason || 'dns_parent_aborted');
      if (parentSignal?.aborted) abortFromParent();
      else parentSignal?.addEventListener('abort', abortFromParent, { once: true });
      const timer = setTimeout(() => controller.abort('dns_timeout'), DNS_QUERY_TIMEOUT_MS);
      const url = provider === 'cloudflare'
        ? `https://cloudflare-dns.com/dns-query?name=${encodeURIComponent(hostname)}&type=${type}`
        : `https://dns.google/resolve?name=${encodeURIComponent(hostname)}&type=${type}`;
      try {
        const response = await fetcher(url, {
          method: 'GET',
          redirect: 'error',
          signal: controller.signal,
          headers: { Accept: 'application/dns-json' }
        });
        if (!response.ok) {
          await cancelBody(response);
          throw new Error(`${provider}_dns_http_${response.status}`);
        }
        const data = JSON.parse(await readTextLimited(response, DNS_RESPONSE_LIMIT)) as {
          Status?: number;
          Answer?: Array<{ type?: number; data?: string }>;
        };
        if (data.Status !== 0 && data.Status !== undefined) return [];
        const wantedType = type === 'A' ? 1 : 28;
        return (data.Answer ?? [])
          .filter((answer) => answer.type === wantedType && typeof answer.data === 'string')
          .map((answer) => String(answer.data));
      } finally {
        clearTimeout(timer);
        parentSignal?.removeEventListener('abort', abortFromParent);
      }
    };

    const results = await Promise.allSettled([query('A'), query('AAAA')]);
    const addresses = results.flatMap((result) => result.status === 'fulfilled' ? result.value : []);
    if (addresses.some((address) => !isPublicIpAddress(address))) throw new Error('dns_private_address');
    if (addresses.length) return [...new Set(addresses)];

    const rejected = results.find((result): result is PromiseRejectedResult => result.status === 'rejected');
    if (rejected) throw rejected.reason;
    throw new Error('dns_no_public_address');
  };

  return async (hostname: string, signal?: AbortSignal): Promise<string[]> => {
    const normalized = hostname.toLowerCase().replace(/\.$/, '').replace(/^\[|\]$/g, '');
    if (isPublicIpAddress(normalized)) return [normalized];
    const cached = cache.get(normalized);
    if (cached) return cached;

    const lookup = (async () => {
      let lastError: unknown = new Error('dns_no_public_address');
      for (const provider of ['cloudflare', 'google'] as const) {
        try {
          return await queryProvider(normalized, provider, signal);
        } catch (error) {
          if (error instanceof Error && error.message === 'dns_private_address') throw error;
          lastError = error;
        }
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
  const tier = avatarTierFromScore(score);
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

  addAvatarCandidate(candidates, '/favicon.ico', baseUrl, 1);
  candidates.sort((left, right) => right.tier - left.tier || right.score - left.score);
  return candidates[0] || { url: '', score: 0, tier: 0 };
}

function preferAvatar(primary: string, primaryTier: number, secondary: string, secondaryTier: number): AvatarCandidate {
  if (!primary) return { url: secondary, score: 0, tier: secondaryTier };
  if (!secondary) return { url: primary, score: 0, tier: primaryTier };
  return secondaryTier >= primaryTier
    ? { url: secondary, score: 0, tier: secondaryTier }
    : { url: primary, score: 0, tier: primaryTier };
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
  avatarTier: number;
  canonicalUrl: string;
  linksPage: string;
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
  const wordpressAvatar = absoluteUrl(data.site_icon_url, candidate);
  return {
    score: 120,
    accessible: true,
    readable: true,
    detectedUrl: reportedUrl && isSameCandidateSite(candidate, reportedUrl) ? reportedUrl : candidate,
    primary: names.primary,
    subtitle: names.subtitle,
    description,
    avatar: wordpressAvatar,
    avatarTier: wordpressAvatar && !/favicon\.ico(?:[?#]|$)/i.test(wordpressAvatar) ? 3 : 0,
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
    primary: names.primary || fallbackSiteName(finalUrl),
    subtitle: names.subtitle,
    description: info.description,
    avatar: info.avatar,
    avatarTier: info.avatarTier,
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
      const preferredAvatar = preferAvatar(
        wordpress.avatar,
        wordpress.avatarTier || 0,
        html.avatar,
        html.avatarTier || 0
      );
      return {
        ...wordpress,
        description: wordpress.description || html.description,
        avatar: preferredAvatar.url,
        avatarTier: preferredAvatar.tier,
        linksPage: html.linksPage
      };
    } catch {
      const avatar = wordpress.avatar || absoluteUrl('/favicon.ico', wordpress.detectedUrl || candidate);
      return {
        ...wordpress,
        avatar,
        avatarTier: wordpress.avatar ? (wordpress.avatarTier || 0) : 0
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

  for (let offset = 0; offset < candidates.length; offset += CANDIDATE_CONCURRENCY) {
    const batch = candidates.slice(offset, offset + CANDIDATE_CONCURRENCY);
    const results = await Promise.all(batch.map((candidate) => inspectCandidate(candidate, resolvedOptions)));
    results.forEach((result, index) => {
      const candidate = batch[index];
      anyAccessible ||= result.accessible;
      attempts.push({ url: candidate, accessible: result.accessible, readable: result.readable });
      if (result.readable) readable.push(result);
    });
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
    const found = extractLinks(text, finalUrl).some((url) =>
      normalizeComparableHostname(url) === localHost || isSameCandidateSite(url, localSiteUrl)
    );
    return { state: found ? 'found' : 'missing', checkedUrl: finalUrl };
  } catch {
    return { state: 'unknown', checkedUrl: page.toString() };
  }
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
