const FORBIDDEN_HOST_SUFFIXES = [
  '.localhost',
  '.local',
  '.internal',
  '.home',
  '.lan',
  '.test',
  '.invalid',
  '.example',
  '.onion'
];
const DOCUMENTATION_DOMAINS = ['example.com', 'example.net', 'example.org'];

function parseHttpUrl(value: string): URL {
  let input = String(value || '').trim();
  if (!input) throw new Error('URL 不能为空');
  if (input.startsWith('//')) input = `https:${input}`;
  if (!/^[a-z][a-z\d+.-]*:\/\//i.test(input)) input = `https://${input}`;

  let parsed: URL;
  try {
    parsed = new URL(input);
  } catch {
    throw new Error('URL 格式无效');
  }
  if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
    throw new Error('只允许 HTTP 或 HTTPS URL');
  }
  return parsed;
}

export function isPublicIpAddress(value: string): boolean {
  if (!ipaddr.isValid(value)) return false;
  let address = ipaddr.parse(value);
  if (address.kind() === 'ipv6') {
    const ipv6Address = address as ipaddr.IPv6;
    if (ipv6Address.isIPv4MappedAddress()) address = ipv6Address.toIPv4Address();
  }
  return address.range() === 'unicast';
}

export function assertSafePublicUrl(value: string): URL {
  const parsed = parseHttpUrl(value);
  const hostname = parsed.hostname.toLowerCase().replace(/\.$/, '');

  if (parsed.username || parsed.password) throw new Error('URL 不能包含账号信息');
  if (parsed.port && parsed.port !== '80' && parsed.port !== '443') {
    throw new Error('只允许 80 或 443 端口');
  }
  if (!hostname || hostname === 'localhost') {
    throw new Error('不允许本地或 IPv6 字面量地址');
  }

  const literalAddress = hostname.replace(/^\[|\]$/g, '');
  if (ipaddr.isValid(literalAddress)) {
    if (!isPublicIpAddress(literalAddress)) throw new Error('不允许私网或保留地址');
    if (literalAddress.includes(':')) throw new Error('不接受 IPv6 字面量目标');
  } else {
    if (!hostname.includes('.')) throw new Error('目标必须使用公网域名');
    if (FORBIDDEN_HOST_SUFFIXES.some((suffix) => hostname.endsWith(suffix))) {
      throw new Error('不允许本地或保留域名');
    }
    if (DOCUMENTATION_DOMAINS.some((domain) => hostname === domain || hostname.endsWith(`.${domain}`))) {
      throw new Error('必须替换示例域名');
    }
  }
  return parsed;
}

export function getCandidateBaseHostname(hostname: string): string {
  const normalized = String(hostname || '').toLowerCase().replace(/\.$/, '');
  const registrableDomain = getDomain(normalized, { allowPrivateDomains: true });
  if (!registrableDomain) return normalized;
  if (
    normalized === registrableDomain ||
    normalized === `www.${registrableDomain}` ||
    normalized === `blog.${registrableDomain}`
  ) {
    return registrableDomain;
  }
  return normalized;
}

function isExpandableCandidateHostname(hostname: string): boolean {
  const normalized = String(hostname || '').toLowerCase().replace(/\.$/, '');
  const registrableDomain = getDomain(normalized, { allowPrivateDomains: true });
  return Boolean(
    registrableDomain && (
      normalized === registrableDomain ||
      normalized === `www.${registrableDomain}` ||
      normalized === `blog.${registrableDomain}`
    )
  );
}

export function buildCandidates(value: string): string[] {
  const parsed = assertSafePublicUrl(value);
  const enteredHost = parsed.hostname.toLowerCase().replace(/\.$/, '');
  const baseHost = getCandidateBaseHostname(enteredHost);
  const mayExpand = isExpandableCandidateHostname(enteredHost);
  const hosts = mayExpand
    ? Array.from(new Set([enteredHost, baseHost, `www.${baseHost}`, `blog.${baseHost}`]))
    : [enteredHost];
  const protocols = parsed.protocol === 'http:' ? ['http:', 'https:'] : ['https:', 'http:'];
  const candidates: string[] = [];

  for (const protocol of protocols) {
    for (const host of hosts) {
      const candidate = `${protocol}//${host}/`;
      assertSafePublicUrl(candidate);
      candidates.push(candidate);
    }
  }
  return candidates;
}

export function isSameCandidateSite(left: string, right: string): boolean {
  try {
    const leftUrl = assertSafePublicUrl(left);
    const rightUrl = assertSafePublicUrl(right);
    return getCandidateBaseHostname(leftUrl.hostname) === getCandidateBaseHostname(rightUrl.hostname);
  } catch {
    return false;
  }
}

export function normalizeComparableHostname(value: string): string {
  try {
    return assertSafePublicUrl(value).hostname.toLowerCase().replace(/^www\./, '').replace(/\.$/, '');
  } catch {
    return '';
  }
}

function decodeNumericEntity(match: string, value: string, radix: number): string {
  const codePoint = Number.parseInt(value, radix);
  return codePoint >= 0 && codePoint <= 0x10ffff ? String.fromCodePoint(codePoint) : match;
}

export function cleanText(value: unknown): string {
  return String(value ?? '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;|&apos;/gi, "'")
    .replace(/&ndash;/gi, '–')
    .replace(/&mdash;/gi, '—')
    .replace(/&#x([0-9a-f]+);/gi, (match, entity) => decodeNumericEntity(match, entity, 16))
    .replace(/&#(\d+);/g, (match, entity) => decodeNumericEntity(match, entity, 10))
    .replace(/\s+/g, ' ')
    .trim();
}

function stripSiteName(title: string, siteName: string): string {
  const escaped = siteName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return title
    .replace(new RegExp(`^${escaped}\\s*(?:[-–—|｜·•»:：]+)\\s*`, 'i'), '')
    .replace(new RegExp(`\\s*(?:[-–—|｜·•»:：]+)\\s*${escaped}$`, 'i'), '')
    .trim();
}

export function splitTitle(fullTitle: string, explicitSiteName: string): { primary: string; subtitle: string } {
  const title = cleanText(fullTitle);
  const siteName = cleanText(explicitSiteName);
  if (!title && !siteName) return { primary: '', subtitle: '' };

  if (siteName) {
    const remainder = stripSiteName(title, siteName);
    if (remainder && remainder.toLowerCase() !== siteName.toLowerCase() && remainder !== title) {
      return { primary: siteName, subtitle: remainder };
    }
    if (!title || title.toLowerCase() === siteName.toLowerCase()) {
      return { primary: siteName, subtitle: '' };
    }
  }

  const parts = title
    .split(/\s+[-|]\s+|\s*[–—｜·•»]+\s*|\s*(?:::|：：)\s*/)
    .map(cleanText)
    .filter(Boolean);
  if (parts.length > 1) {
    return {
      primary: siteName || parts[0],
      subtitle: parts.slice(siteName ? 0 : 1).join(' – ')
    };
  }
  return { primary: siteName || title, subtitle: siteName && title !== siteName ? title : '' };
}
import ipaddr from 'ipaddr.js';
import { getDomain } from 'tldts';
