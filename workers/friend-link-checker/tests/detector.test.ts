import { describe, expect, it, vi } from 'vitest';
import { checkBacklink, detectSite, type WorkerFetcher } from '../src/detector';

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

describe('site detection', () => {
  it('selects readable WordPress metadata from the blog candidate', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://blog.sample-blog.dev/wp-json/') {
        return jsonResponse({
          name: '编程小记',
          description: '其实我也不知道建这个站拿来干嘛=-=',
          home: 'https://blog.sample-blog.dev/',
          site_icon_url: ''
        }, url);
      }
      if (url === 'https://blog.sample-blog.dev/') {
        return htmlResponse(`
          <!doctype html><html><head><link rel="icon" href="/icon.png"></head>
          <body><a href="/friends/">友情链接</a></body></html>
        `);
      }
      return htmlResponse('not found', 404);
    });

    const result = await detectSite('sample-blog.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.detectedUrl).toBe('https://blog.sample-blog.dev/');
    expect(result.primary).toBe('编程小记');
    expect(result.subtitle).toBe('');
    expect(result.description).toBe('其实我也不知道建这个站拿来干嘛=-=');
    expect(result.avatar).toBe('https://blog.sample-blog.dev/icon.png');
    expect(result.linksPage).toBe('https://blog.sample-blog.dev/friends/');
    expect(result.source).toBe('wordpress');
  });

  it('falls back to HTML and refuses an off-family canonical URL', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.endsWith('/wp-json/')) return htmlResponse('missing', 404);
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
      if (url === 'https://sample-blog.dev/wp-json/') {
        return jsonResponse({
          name: '示例博客',
          home: 'https://sample-blog.dev/',
          site_icon_url: 'https://sample-blog.dev/favicon.ico'
        }, url);
      }
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

  it('prefers an author avatar over the website logo and generic favicon', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.endsWith('/wp-json/')) return htmlResponse('missing', 404);
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

  it('keeps a dedicated WordPress site icon over an arbitrary HTML hero image', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://icon-priority.dev/wp-json/') {
        return jsonResponse({
          name: '图标优先站点',
          home: 'https://icon-priority.dev/',
          site_icon_url: 'https://icon-priority.dev/uploads/site-icon-512x512.png'
        }, url);
      }
      if (url === 'https://icon-priority.dev/') {
        return htmlResponse('<html><head></head><body><img src="/hero.jpg" width="1200" height="630"></body></html>');
      }
      return htmlResponse('missing', 404);
    });

    const result = await detectSite('icon-priority.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.avatar).toBe('https://icon-priority.dev/uploads/site-icon-512x512.png');
  });

  it('uses structured WebSite.name instead of treating og:title as the site name', async () => {
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.endsWith('/wp-json/')) return htmlResponse('missing', 404);
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
      if (url.endsWith('/wp-json/')) return htmlResponse('missing', 404);
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
    const fetcher: WorkerFetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url.endsWith('/wp-json/')) return htmlResponse('missing', 404);
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
  });

  it('keeps blocked or unreachable candidates unknown instead of calling them offline', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse('forbidden', 403));
    const result = await detectSite('sample-blog.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('unknown');
    expect(result.accessible).toBe(true);
    expect(result.readable).toBe(false);
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
