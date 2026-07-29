import { describe, expect, it, vi } from 'vitest';
import {
  checkBacklink,
  checkBacklinkWithWordPressFallbacks,
  createPublicDnsResolver,
  detectSite,
  discoverAndCheckBacklink,
  type WorkerFetcher
} from '../src/detector';

const publicResolver = async (): Promise<string[]> => ['93.184.216.34'];

function jsonResponse(value: unknown, url: string, status = 200): Response {
  return new Response(JSON.stringify(value), {
    status,
    headers: { 'content-type': 'application/json', 'x-test-url': url }
  });
}

function htmlResponse(value: string, status = 200, headers: Record<string, string> = {}): Response {
  return new Response(value, {
    status,
    headers: { 'content-type': 'text/html; charset=utf-8', ...headers }
  });
}

describe('public DNS resolution', () => {
  it('falls back to the secondary DoH provider when Cloudflare DNS is unavailable', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.startsWith('https://cloudflare-dns.com/')) throw new Error('cloudflare_dns_unavailable');
      if (url.startsWith('https://dns.google/resolve') && url.includes('type=A')) {
        return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '93.184.216.34' }] }, url);
      }
      return jsonResponse({ Status: 0, Answer: [] }, url);
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev')).resolves.toEqual(['93.184.216.34']);
    for (const call of vi.mocked(fetcher).mock.calls) {
      expect(call[1]?.redirect).toBe('manual');
    }
  });

  it('accepts a public AAAA result when the A family has a definitive empty answer', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.includes('type=A&') || /[?&]type=A$/.test(url)) {
        return jsonResponse({ Status: 0, Answer: [] }, url);
      }
      return jsonResponse({ Status: 0, Answer: [{ type: 28, data: '2606:2800:220:1:248:1893:25c8:1946' }] }, url);
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev')).resolves.toEqual([
      '2606:2800:220:1:248:1893:25c8:1946'
    ]);
  });

  it('does not trust one public DNS family when the other family transport fails', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.includes('type=AAAA')) throw new Error('aaaa_lookup_failed');
      return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '93.184.216.34' }] }, url);
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev'))
      .rejects.toThrow(/dns_network_error$/);
  });

  it.each([2, 5])('does not treat DNS failure status %i as an authoritative empty family', async (status) => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.includes('type=AAAA')) return jsonResponse({ Status: status }, url);
      return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '93.184.216.34' }] }, url);
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev'))
      .rejects.toThrow(`tencent_dns_status_${status}`);
  });

  it.each([
    { Answer: [{ type: 1, data: '93.184.216.34' }] },
    { Status: '0', Answer: [{ type: 1, data: '93.184.216.34' }] },
    { Status: 0, Answer: { type: 1, data: '93.184.216.34' } }
  ])('rejects malformed DNS JSON instead of trusting public-looking answers', async (payload) => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => jsonResponse(payload, String(input)));

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev'))
      .rejects.toThrow('tencent_dns_invalid_response');
  });

  it('accepts an NXDOMAIN family response as an explicit empty result', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.includes('type=AAAA')) return jsonResponse({ Status: 3 }, url);
      return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '93.184.216.34' }] }, url);
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev'))
      .resolves.toEqual(['93.184.216.34']);
  });

  it('does not fall back past a private DNS answer', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.startsWith('https://dns.google/')) {
        return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '93.184.216.34' }] }, url);
      }
      if (url.includes('type=A')) {
        return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '127.0.0.1' }] }, url);
      }
      return jsonResponse({ Status: 0, Answer: [] }, url);
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev')).rejects.toThrow('dns_private_address');
    expect(fetcher).not.toHaveBeenCalledWith(expect.stringContaining('dns.alidns.com'), expect.anything());
    expect(fetcher).not.toHaveBeenCalledWith(expect.stringContaining('doh.pub'), expect.anything());
  });

  it('falls back to domestic DoH providers when Cloudflare and Google are unavailable', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.startsWith('https://cloudflare-dns.com/') || url.startsWith('https://dns.google/')) {
        throw new Error('blocked_doh_provider');
      }
      if (url.startsWith('https://dns.alidns.com/') && url.includes('type=A')) {
        return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '93.184.216.34' }] }, url);
      }
      return jsonResponse({ Status: 0, Answer: [] }, url);
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev')).resolves.toEqual(['93.184.216.34']);
    expect(fetcher).toHaveBeenCalledWith(expect.stringContaining('dns.alidns.com'), expect.anything());
    expect(fetcher).not.toHaveBeenCalledWith(expect.stringContaining('doh.pub'), expect.anything());
  });

  it('reaches Tencent DNS and the target page after three providers time out', async () => {
    vi.useFakeTimers();
    let active = 0;
    let maxActive = 0;
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input, init) => {
      const url = String(input);
      requestedUrls.push(url);
      active += 1;
      maxActive = Math.max(maxActive, active);
      try {
        if (/cloudflare-dns|dns\.google|dns\.alidns/.test(url)) {
          const signal = init?.signal;
          await new Promise<void>((_resolve, reject) => {
            if (signal?.aborted) reject(new Error('dns_aborted'));
            else signal?.addEventListener('abort', () => reject(new Error('dns_aborted')), { once: true });
          });
        }
        if (url.startsWith('https://doh.pub/') && url.includes('type=A')) {
          return jsonResponse({ Status: 0, Answer: [{ type: 1, data: '93.184.216.34' }] }, url);
        }
        if (url.startsWith('https://doh.pub/')) return jsonResponse({ Status: 0, Answer: [] }, url);
        if (url === 'https://www.sample-blog.dev/') {
          await new Promise<void>((resolve, reject) => {
            const timer = setTimeout(resolve, 1_500);
            init?.signal?.addEventListener('abort', () => {
              clearTimeout(timer);
              reject(new Error('target_aborted'));
            }, { once: true });
          });
          return htmlResponse('<!doctype html><html><head><title>Recovered Blog</title><meta name="description" content="Recovered through the final DNS provider"></head><body></body></html>');
        }
        return htmlResponse('not found', 404);
      } finally {
        active -= 1;
      }
    });
    try {
      const detection = detectSite('https://www.sample-blog.dev/', { fetcher, timeoutMs: 6_000 });
      await vi.advanceTimersByTimeAsync(20_000);
      const result = await detection;

      expect(result.state).toBe('ok');
      expect(result.primary).toBe('Recovered Blog');
      expect(requestedUrls.some((url) => url.startsWith('https://doh.pub/'))).toBe(true);
      expect(requestedUrls).toContain('https://www.sample-blog.dev/');
      expect(maxActive).toBeLessThanOrEqual(6);
      expect(active).toBe(0);
    } finally {
      vi.useRealTimers();
    }
  });

  it('keeps the final provider failure as a bounded diagnostic code', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => {
      throw new Error('socket details must not escape');
    });

    await expect(createPublicDnsResolver(fetcher)('sample-blog.dev'))
      .rejects.toThrow('tencent_dns_network_error');
  });

  it('keeps a definitive no-public-address DNS answer closed instead of fetching the target', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (/cloudflare-dns|dns\.google|dns\.alidns|doh\.pub/.test(url)) {
        return jsonResponse({ Status: 0, Answer: [] }, url);
      }
      return htmlResponse('<!doctype html><html><head><title>Must not load</title></head></html>');
    });

    const result = await detectSite('https://assets.sample-blog.dev/', { fetcher, timeoutMs: 100 });

    expect(result.state).toBe('unknown');
    expect(requestedUrls).not.toContain('https://assets.sample-blog.dev/');
  });
});

describe('site detection', () => {
  it('does not probe WordPress APIs when candidate homepage HTML is already sufficient', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/') {
        return htmlResponse(`
          <!doctype html><html><head>
            <title>Sample Blog</title>
            <meta name="description" content="A sample homepage">
            <link rel="icon" href="/icon.png">
          </head><body><a href="/friends/">Friend links</a></body></html>
        `);
      }
      return htmlResponse('not found', 404);
    });

    const result = await detectSite('sample-blog.dev', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 100
    });

    expect(result).toMatchObject({
      state: 'ok',
      primary: 'Sample Blog',
      description: 'A sample homepage',
      avatar: 'https://sample-blog.dev/icon.png',
      linksPage: 'https://sample-blog.dev/friends/',
      source: 'html'
    });
    expect(requestedUrls).not.toEqual(expect.arrayContaining([
      expect.stringMatching(/\/(?:wp-json|wp-admin|wp-content)\//i)
    ]));
    expect(requestedUrls.every((url) => new URL(url).pathname === '/')).toBe(true);
  });

  it('uses WordPress REST discovery only as a fallback for incomplete homepage metadata', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/') {
        return htmlResponse(`
          <!doctype html><html><head>
            <meta name="generator" content="WordPress 6.8">
            <link rel="https://api.w.org/" href="https://sample-blog.dev/wp-json/">
          </head><body></body></html>
        `);
      }
      if (url === 'https://sample-blog.dev/wp-json/') {
        return jsonResponse({
          name: 'WordPress Friend',
          description: 'REST fallback description',
          home: 'https://sample-blog.dev/',
          site_icon_url: 'https://sample-blog.dev/uploads/site-icon-512x512.png'
        }, url);
      }
      return htmlResponse('not found', 404);
    });

    const result = await detectSite('sample-blog.dev', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 100
    });

    expect(result).toMatchObject({
      state: 'ok',
      primary: 'WordPress Friend',
      description: 'REST fallback description',
      avatar: 'https://sample-blog.dev/uploads/site-icon-512x512.png',
      wordpressApiUrl: 'https://sample-blog.dev/wp-json/',
      source: 'wordpress'
    });
    expect(requestedUrls).toContain('https://sample-blog.dev/wp-json/');
  });

  it('uses Argon homepage identity fields without requiring a WordPress API request', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://argon-blog.dev/') {
        return htmlResponse(`
          <!doctype html><html><head><meta name="generator" content="WordPress 6.8"></head><body>
            <span class="leftbar-banner-title">Argon Friend</span>
            <span class="leftbar-banner-subtitle">An Argon subtitle</span>
            <div id="leftbar_overview_author_image" style="background-image:url('/uploads/argon-avatar.webp')"></div>
          </body></html>
        `);
      }
      return htmlResponse('not found', 404);
    });

    const result = await detectSite('argon-blog.dev', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 100
    });

    expect(result).toMatchObject({
      state: 'ok',
      primary: 'Argon Friend',
      subtitle: 'An Argon subtitle',
      avatar: 'https://argon-blog.dev/uploads/argon-avatar.webp',
      source: 'argon'
    });
    expect(requestedUrls).not.toContain('https://argon-blog.dev/wp-json/');
  });

  it('keeps an HTML logo over a WordPress favicon fallback', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/') {
        return htmlResponse(`
          <!doctype html><html><head>
            <meta name="generator" content="WordPress 6.8">
            <link rel="https://api.w.org/" href="https://sample-blog.dev/wp-json/">
          </head><body><img class="site-logo" src="/assets/site-logo.png"></body></html>
        `);
      }
      if (url === 'https://sample-blog.dev/wp-json/') {
        return jsonResponse({
          name: 'WordPress Friend',
          home: 'https://sample-blog.dev/',
          site_icon_url: 'https://sample-blog.dev/favicon.ico'
        }, url);
      }
      return htmlResponse('not found', 404);
    });

    const result = await detectSite('sample-blog.dev', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 100
    });

    expect(result.primary).toBe('WordPress Friend');
    expect(result.avatar).toBe('https://sample-blog.dev/assets/site-logo.png');
  });

  it('checks candidate hosts concurrently so one slow candidate cannot serialize the whole job', async () => {
    let activeRequests = 0;
    let maxActiveRequests = 0;
    const fetcher: WorkerFetcher = vi.fn(async () => {
      activeRequests += 1;
      maxActiveRequests = Math.max(maxActiveRequests, activeRequests);
      await new Promise((resolve) => setTimeout(resolve, 5));
      activeRequests -= 1;
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('sample-blog.dev', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 500
    });

    expect(result.state).toBe('unknown');
    expect(maxActiveRequests).toBeGreaterThan(1);
  });

  it('refuses an off-family canonical URL', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/') {
        return htmlResponse(`
          <html><head>
            <title>编程小记 – 其实我也不知道建这个站拿来干嘛=-=</title>
            <link rel="canonical" href="https://attacker.example/stolen">
            <link rel="icon" href="/favicon.ico">
            <meta content="站点介绍" name="description">
          </head></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('sample-blog.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.detectedUrl).toBe('https://sample-blog.dev/');
    expect(result.primary).toBe('编程小记');
    expect(result.subtitle).toBe('其实我也不知道建这个站拿来干嘛=-=');
    expect(result.description).toBe('站点介绍');
    expect(result.avatar).toBe('https://sample-blog.dev/favicon.ico');
  });

  it('prefers declared site identity images over a generic favicon.ico', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/') {
        return htmlResponse(`
          <html><head>
            <link rel="icon" href="/favicon.ico">
            <meta property="og:image" content="/cover.jpg">
            <script type="application/ld+json">
              {"@type":"WebSite","name":"示例博客","logo":{"url":"/assets/site-logo.png"}}
            </script>
          </head><body></body></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('sample-blog.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.avatar).toBe('https://sample-blog.dev/assets/site-logo.png');
  });

  it('does not invent a favicon URL when homepage HTML declares no image', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      if (String(input) === 'https://sample-blog.dev/') {
        return htmlResponse('<!doctype html><html><head><title>Sample Blog</title></head><body></body></html>');
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('sample-blog.dev', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 100
    });

    expect(result.state).toBe('ok');
    expect(result.avatar).toBe('');
  });

  it('prefers an author avatar over the website logo and generic favicon', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/') {
        return htmlResponse(`
          <html><head>
            <link rel="icon" href="/favicon.ico">
            <script type="application/ld+json">
              {"@graph":[
                {"@type":"WebSite","logo":{"url":"/assets/site-logo.png"}},
                {"@type":"Person","image":{"url":"/images/author.jpg"}}
              ]}
            </script>
          </head><body></body></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('sample-blog.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.avatar).toBe('https://sample-blog.dev/images/author.jpg');
  });

  it('uses structured WebSite.name instead of treating og:title as the site name', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://structured-name.dev/') {
        return htmlResponse(`
          <html><head>
            <title>文章标题 – 正确站名</title>
            <meta property="og:title" content="文章标题">
            <script type="application/ld+json">{"@type":"WebSite","name":"正确站名"}</script>
          </head><body></body></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('structured-name.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.primary).toBe('正确站名');
    expect(result.subtitle).toBe('文章标题');
  });

  it('extracts a name and meaningful image from a framework-free static landing page', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://landing-site.dev/') {
        return htmlResponse(`
          <html><head><title>银河宣传页</title>
            <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png">
          </head><body><main>
            <img src="/assets/hero.webp" width="1200" height="630" alt="产品主视觉">
          </main></body></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('landing-site.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.primary).toBe('银河宣传页');
    expect(result.avatar).toBe('https://landing-site.dev/assets/hero.webp');
  });

  it('uses a branded forum node when standard metadata is absent', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://community-forum.dev/') {
        return htmlResponse(`
          <html><head></head><body>
            <a class="navbar-brand"><img src="/assets/community-logo.svg" alt="星海论坛"></a>
          </body></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('community-forum.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.primary).toBe('星海论坛');
    expect(result.avatar).toBe('https://community-forum.dev/assets/community-logo.svg');
    expect(requestedUrls.some((url) => url.includes('/wp-json/'))).toBe(false);
  });

  it('ignores post-list avatars when a real navbar logo exists', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://dorazhang.cn/') {
        return htmlResponse(`
          <html><body>
            <a class="navbar-brand" href="https://dorazhang.cn/">
              <img class="logo" src="data:image/svg+xml,placeholder" alt="ZyxBlog" data-lazy-src="https://dorazhang.cn/wp-content/uploads/2025/03/logo-m.png">
            </a>
            <div class="post-list-avatar"><img src="/theme/loading.gif" data-src="https://q2.qlogo.cn/headimg_dl?dst_uin=2078170660&spec=100" alt="avatar" class="avatar lazy"></div>
            <div class="post-list-avatar"><img src="/theme/loading.gif" data-src="https://dn-qiniu-avatar.qbox.me/avatar/50e6d265" alt="avatar" class="avatar lazy"></div>
          </body></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('dorazhang.cn', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.avatar).toBe('https://dorazhang.cn/wp-content/uploads/2025/03/logo-m.png');
  });

  it('prefers the site profile avatar over a small theme login placeholder', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://wgzdy.top/') {
        return htmlResponse(`
          <html><body>
            <div class="header-user-avatar"><a href="https://wgzdy.top/wp-login.php">
              <img alt="header_user_avatar" src="https://s.nmxc.ltd/sakurairo_vision/@2.5/basic/topavatar.png" width="30" height="30">
            </a></div>
            <div class="focusinfo"><div class="header-tou"><a href="https://wgzdy.top/">
              <img alt="avatar" src=" https://wgzdy.top/wp-content/uploads/avater.webp">
            </a></div></div>
          </body></html>
        `);
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('wgzdy.top', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.avatar).toBe('https://wgzdy.top/wp-content/uploads/avater.webp');
  });

  it('uses a richer later candidate when the entered www candidate only yields a favicon', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://www.sample-blog.dev/') {
        return htmlResponse(`
          <!doctype html><html><head>
            <title>Sample Blog</title>
            <meta name="description" content="A complete homepage description">
            <link rel="icon" href="/favicon.ico">
          </head><body><a href="/friends/">Friend links</a></body></html>
        `);
      }
      if (url === 'https://sample-blog.dev/') {
        return htmlResponse('<html><body><img class="site-logo" src="/assets/site-logo.png"></body></html>');
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('https://www.sample-blog.dev/', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 100
    });

    expect(result.state).toBe('ok');
    expect(result.primary).toBe('Sample Blog');
    expect(result.description).toBe('A complete homepage description');
    expect(result.avatar).toBe('https://sample-blog.dev/assets/site-logo.png');
  });

  it('keeps blocked or unreachable candidates unknown instead of calling them offline', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      requestedUrls.push(String(input));
      return htmlResponse('forbidden', 403);
    });
    const result = await detectSite('sample-blog.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('unknown');
    expect(result.accessible).toBe(true);
    expect(result.readable).toBe(false);
    expect(result.attempts[0].diagnostics).toEqual(expect.arrayContaining([
      expect.objectContaining({ phase: 'html', code: 'http_403', httpStatus: 403 })
    ]));
    expect(result.attempts.flatMap((attempt) => attempt.diagnostics))
      .not.toEqual(expect.arrayContaining([expect.objectContaining({ phase: 'wordpress' })]));
    expect(requestedUrls.some((url) => url.includes('/wp-json/'))).toBe(false);
  });

  it('records DNS and response-size diagnostic codes without exposing exception text', async () => {
    const dnsFailure = await detectSite('assets.sample-blog.dev', {
      fetcher: vi.fn(),
      resolver: async () => { throw new Error('dns_timeout'); },
      timeoutMs: 100
    });
    expect(dnsFailure.attempts[0].diagnostics[0]).toMatchObject({
      phase: 'html',
      code: 'dns_timeout',
      reachable: false
    });

    const cancelOversizedBody = vi.fn();
    const oversized = await detectSite('assets.sample-blog.dev', {
      fetcher: vi.fn(async () => new Response(new ReadableStream({ cancel: cancelOversizedBody }), {
        status: 200,
        headers: { 'content-length': String(384 * 1024 + 1) }
      })),
      resolver: publicResolver,
      timeoutMs: 100
    });
    expect(oversized.attempts[0].diagnostics).toEqual(expect.arrayContaining([
      expect.objectContaining({ phase: 'html', code: 'response_too_large' })
    ]));
    expect(cancelOversizedBody).toHaveBeenCalled();
  });

  it('records a bounded timeout diagnostic code', async () => {
    const result = await detectSite('assets.sample-blog.dev', {
      fetcher: vi.fn(async (_input, init) => new Promise<Response>((_resolve, reject) => {
        init?.signal?.addEventListener('abort', () => reject(new Error('arbitrary_abort_text')), { once: true });
      })),
      resolver: publicResolver,
      timeoutMs: 500
    });

    expect(result.state).toBe('unknown');
    expect(result.attempts[0].diagnostics[0]).toMatchObject({ code: 'timeout', reachable: false });
  });

  it('does not fetch a hostname that resolves to a private address', async () => {
    const fetcher: WorkerFetcher = vi.fn();
    const result = await detectSite('sample-blog.dev', {
      fetcher,
      resolver: async () => ['127.0.0.1'],
      timeoutMs: 100
    });

    expect(result.state).toBe('unknown');
    expect(fetcher).not.toHaveBeenCalled();
  });

  it('does not use an anti-bot challenge title as detected site metadata', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse(`
      <!doctype html><html><head><title>Just a moment...</title></head>
      <body><div class="cf-chl-widget">Checking your browser</div></body></html>
    `));
    const result = await detectSite('sample-blog.dev', {
      fetcher,
      resolver: publicResolver,
      timeoutMs: 100
    });

    expect(result.state).toBe('unknown');
    expect(result.primary).toBe('');
  });
});

describe('backlink checks', () => {
  it('does not query WordPress fallbacks after the direct page proves found', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      return htmlResponse('<a href="https://my-blog.top/">My blog</a>');
    });

    const result = await checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result.state).toBe('found');
    expect(requestedUrls).toEqual(['https://sample-blog.dev/friends/']);
  });

  it('uses rendered WordPress page content before OPML when the public page is unreadable', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/friends/') {
        return htmlResponse('blocked', 403);
      }
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')) {
        return jsonResponse([{
          link: 'https://sample-blog.dev/friends/',
          slug: 'friends',
          title: { rendered: 'Friend links' },
          content: { rendered: '<a href="https://my-blog.top/">My blog</a>' }
        }], url);
      }
      return htmlResponse('not found', 404);
    });

    const result = await checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result).toMatchObject({
      state: 'found',
      checkedUrl: 'https://sample-blog.dev/friends/'
    });
    expect(requestedUrls).toContain('https://sample-blog.dev/wp-links-opml.php');
  });

  it('lets a WordPress source prove found after the readable page reports missing', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/friends/') {
        return htmlResponse('<a href="https://other.top/">Other</a>');
      }
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')) {
        return jsonResponse([{
          link: 'https://sample-blog.dev/friends/',
          slug: 'friends',
          content: { rendered: '<a href="https://my-blog.top/">My blog</a>' }
        }], url);
      }
      return htmlResponse('not found', 404);
    });

    const result = await checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result).toMatchObject({
      state: 'found',
      checkedUrl: 'https://sample-blog.dev/friends/'
    });
    expect(requestedUrls.some((url) => url.includes('/wp-json/wp/v2/pages?'))).toBe(true);
  });

  it('lets OPML prove found after the readable page reports missing', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/friends/') {
        return htmlResponse('<a href="https://other.top/">Other</a>');
      }
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')) return jsonResponse([], url);
      if (url === 'https://sample-blog.dev/wp-links-opml.php') {
        return new Response('<opml version="1.0"><body><outline htmlUrl="https://my-blog.top/" /></body></opml>', {
          headers: { 'content-type': 'text/xml' }
        });
      }
      return htmlResponse('not found', 404);
    });

    const result = await checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result).toMatchObject({
      state: 'found',
      checkedUrl: 'https://sample-blog.dev/wp-links-opml.php'
    });
  });

  it('preserves a readable direct missing result when WordPress sources are inconclusive', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/friends/') {
        return htmlResponse('<a href="https://other.top/">Other</a>');
      }
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')) {
        return jsonResponse([{
          link: 'https://sample-blog.dev/friends/',
          slug: 'friends',
          content: { rendered: '<a href="https://one.top/">One</a><a href="https://two.top/">Two</a>' }
        }], url);
      }
      if (url === 'https://sample-blog.dev/wp-links-opml.php') return htmlResponse('blocked', 403);
      return htmlResponse('not found', 404);
    });

    const result = await checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result).toMatchObject({
      state: 'missing',
      checkedUrl: 'https://sample-blog.dev/friends/'
    });
    expect(result.diagnostics.some((entry) => entry.code === 'wordpress_page_missing')).toBe(false);
  });

  it('does not accept a same-slug WordPress page at a different canonical path', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/friends/') return htmlResponse('blocked', 403);
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')) {
        return jsonResponse([{
          link: 'https://sample-blog.dev/archive/friends/',
          slug: 'friends',
          content: { rendered: '<a href="https://my-blog.top/">My blog</a>' }
        }], url);
      }
      if (url === 'https://sample-blog.dev/wp-links-opml.php') {
        return new Response('<opml version="1.0"><body /></opml>', {
          headers: { 'content-type': 'text/xml' }
        });
      }
      return htmlResponse('not found', 404);
    });

    const result = await checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result.state).toBe('unknown');
    expect(result.checkedUrl).toBe('');
  });

  it('keeps unrelated WordPress REST and OPML links inconclusive', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/friends/') return htmlResponse('blocked', 403);
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')) {
        return jsonResponse([{
          link: 'https://sample-blog.dev/friends/',
          slug: 'friends',
          content: { rendered: '<a href="https://one.top/">One</a><a href="https://two.top/">Two</a>' }
        }], url);
      }
      if (url === 'https://sample-blog.dev/wp-links-opml.php') {
        return new Response('<opml version="1.0"><body><outline htmlUrl="https://three.top/" /></body></opml>', {
          headers: { 'content-type': 'text/xml' }
        });
      }
      return htmlResponse('not found', 404);
    });

    const result = await checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result.state).toBe('unknown');
    expect(result.checkedUrl).toBe('');
  });

  it('starts WordPress REST and OPML fallbacks in parallel', async () => {
    const requestedUrls: string[] = [];
    let releaseFallbacks: (() => void) | undefined;
    const fallbackGate = new Promise<void>((resolve) => { releaseFallbacks = resolve; });
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/friends/') return htmlResponse('blocked', 403);
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')) {
        await fallbackGate;
        return jsonResponse([], url);
      }
      if (url === 'https://sample-blog.dev/wp-links-opml.php') {
        await fallbackGate;
        return new Response('<opml version="1.0"><body /></opml>', {
          headers: { 'content-type': 'text/xml' }
        });
      }
      return htmlResponse('not found', 404);
    });

    const resultPromise = checkBacklinkWithWordPressFallbacks(
      'https://sample-blog.dev/friends/',
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );
    for (let attempt = 0; attempt < 20 && requestedUrls.length < 3; attempt += 1) {
      await Promise.resolve();
    }
    const startedTogether = requestedUrls.some((url) => url.includes('/wp-json/wp/v2/pages?'))
      && requestedUrls.includes('https://sample-blog.dev/wp-links-opml.php');
    releaseFallbacks?.();
    await resultPromise;

    expect(startedTogether).toBe(true);
  });

  it('waits for both WordPress fallbacks to finish abort cleanup before rejecting', async () => {
    const realSetTimeout = globalThis.setTimeout;
    vi.useFakeTimers();
    try {
      const controller = new AbortController();
      let activeRequests = 0;
      const fetcher: WorkerFetcher = vi.fn(async (input, init) => {
        const url = String(input);
        if (url === 'https://sample-blog.dev/friends/') return htmlResponse('blocked', 403);
        if (
          url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages?')
          || url === 'https://sample-blog.dev/wp-links-opml.php'
        ) {
          const cleanupDelay = url.includes('/wp-json/') ? 25 : 100;
          return new Promise<Response>((_resolve, reject) => {
            const signal = init?.signal;
            if (!signal) {
              reject(new Error('missing_abort_signal'));
              return;
            }
            activeRequests += 1;
            const finishAbort = () => {
              setTimeout(() => {
                activeRequests -= 1;
                reject(signal.reason instanceof Error ? signal.reason : new Error('request_aborted'));
              }, cleanupDelay);
            };
            if (signal.aborted) finishAbort();
            else signal.addEventListener('abort', finishAbort, { once: true });
          });
        }
        return htmlResponse('not found', 404);
      });

      let resultSettled = false;
      const resultPromise = checkBacklinkWithWordPressFallbacks(
        'https://sample-blog.dev/friends/',
        'https://sample-blog.dev/',
        'https://my-blog.top/',
        {
          fetcher,
          resolver: publicResolver,
          timeoutMs: 500,
          signal: controller.signal,
          wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
        }
      );
      const outcomePromise = resultPromise.then(
        (value) => ({ value, error: null as Error | null }),
        (error: unknown) => ({ value: null, error: error instanceof Error ? error : new Error(String(error)) })
      );
      void outcomePromise.then(() => { resultSettled = true; });
      for (let attempt = 0; attempt < 50 && activeRequests < 2; attempt += 1) {
        await new Promise<void>((resolve) => realSetTimeout(resolve, 0));
      }
      expect(activeRequests).toBe(2);

      controller.abort(new Error('probe_timeout'));
      await vi.advanceTimersByTimeAsync(25);
      await new Promise<void>((resolve) => realSetTimeout(resolve, 0));
      const settledAfterFirstCleanup = resultSettled;
      const activeAfterFirstCleanup = activeRequests;
      await vi.advanceTimersByTimeAsync(75);
      const outcome = await outcomePromise;

      expect(settledAfterFirstCleanup).toBe(false);
      expect(activeAfterFirstCleanup).toBe(1);
      expect(activeRequests).toBe(0);
      expect(outcome.value).toBeNull();
      expect(outcome.error?.message).toBe('probe_timeout');
    } finally {
      vi.useRealTimers();
    }
  });

  it('prefers a discovered candidate that contains the backlink over an earlier missing candidate', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages')) return jsonResponse([], url);
      if (url === 'https://sample-blog.dev/links/') {
        return htmlResponse('<html><head><title>Friend links</title></head><body><a href="https://other.top/">Other</a></body></html>');
      }
      if (url === 'https://sample-blog.dev/friends/') {
        return htmlResponse('<html><head><title>Friends</title></head><body><a href="https://my-blog.top/">My Blog</a></body></html>');
      }
      return htmlResponse('not found', 404);
    });

    const result = await discoverAndCheckBacklink(
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      { fetcher, resolver: publicResolver, timeoutMs: 100 }
    );

    expect(result).toMatchObject({
      state: 'found',
      linksPage: 'https://sample-blog.dev/friends/',
      checkedUrl: 'https://sample-blog.dev/friends/'
    });
    expect(requestedUrls).toEqual(expect.arrayContaining([
      'https://sample-blog.dev/links/',
      'https://sample-blog.dev/friends/'
    ]));
    expect(requestedUrls.some((url) => url.includes('/wp-json/'))).toBe(false);
  });

  it('keeps discovery unknown when common paths only return soft 404 pages', async () => {
    const requestedUrls: string[] = [];
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages')) return jsonResponse([], url);
      return htmlResponse('<!doctype html><html><head><title>Sample Blog</title></head><body>Page not found</body></html>');
    });

    const result = await discoverAndCheckBacklink(
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      { fetcher, resolver: publicResolver, timeoutMs: 100 }
    );

    expect(result).toMatchObject({
      state: 'unknown',
      linksPage: '',
      checkedUrl: ''
    });
    expect(requestedUrls.some((url) => url.includes('/wp-json/'))).toBe(false);
  });

  it('uses a bounded WordPress Pages fallback to discover a custom friend-links slug', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages')) {
        return jsonResponse([{
          link: 'https://sample-blog.dev/community/',
          slug: 'community',
          title: { rendered: '友情链接' }
        }], url);
      }
      if (url === 'https://sample-blog.dev/community/') {
        return htmlResponse('<html><head><title>友情链接</title></head><body><a href="https://my-blog.top/">My Blog</a></body></html>');
      }
      return htmlResponse('not found', 404);
    });

    const result = await discoverAndCheckBacklink(
      'https://sample-blog.dev/',
      'https://my-blog.top/',
      {
        fetcher,
        resolver: publicResolver,
        timeoutMs: 100,
        wordpressApiUrl: 'https://sample-blog.dev/wp-json/'
      }
    );

    expect(result).toMatchObject({
      state: 'found',
      linksPage: 'https://sample-blog.dev/community/',
      checkedUrl: 'https://sample-blog.dev/community/'
    });
  });

  it('finds a backlink after normalizing local www and blog hostnames', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse(`
      <a href="https://blog.my-blog.top/posts/hello">My Blog</a>
    `));

    const result = await checkBacklink(
      'https://sample-blog.dev/friends',
      'https://sample-blog.dev',
      'https://my-blog.top',
      { fetcher, resolver: publicResolver, timeoutMs: 100 }
    );

    expect(result.state).toBe('found');
  });

  it('does not mistake a normal friend-links page with captcha assets for a challenge page', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse(`
      <!doctype html><html><head><title>评论验证码接入教程</title><script src="/assets/comment-captcha.js"></script></head><body>
        <h1>评论验证码接入教程</h1>
        <p>评论验证码由站点统一提供</p>
        <a href="https://my-blog.top/">My Blog</a>
      </body></html>
    `));

    const result = await checkBacklink(
      'https://sample-blog.dev/friends',
      'https://sample-blog.dev',
      'https://my-blog.top',
      { fetcher, resolver: publicResolver, timeoutMs: 100 }
    );

    expect(result.state).toBe('found');
  });

  it('returns missing only after reading a successful backlink page', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse('<a href="https://other.top">Other</a>'));
    const result = await checkBacklink(
      'https://sample-blog.dev/friends',
      'https://sample-blog.dev',
      'https://my-blog.top',
      { fetcher, resolver: publicResolver, timeoutMs: 100 }
    );

    expect(result.state).toBe('missing');
  });

  it('returns unknown when the backlink page cannot be read', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse('blocked', 403));
    const result = await checkBacklink(
      'https://sample-blog.dev/friends',
      'https://sample-blog.dev',
      'https://my-blog.top',
      { fetcher, resolver: publicResolver, timeoutMs: 100 }
    );

    expect(result.state).toBe('unknown');
    expect(result.checkedUrl).toBe('');
  });

  it('treats a successful anti-bot challenge page as unknown', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse(`
      <!doctype html><html><body><h1>Just a moment...</h1><div class="cf-chl-widget">Checking your browser</div></body></html>
    `));
    const result = await checkBacklink(
      'https://sample-blog.dev/friends',
      'https://sample-blog.dev',
      'https://my-blog.top',
      { fetcher, resolver: publicResolver, timeoutMs: 100 }
    );

    expect(result.state).toBe('unknown');
  });
});
