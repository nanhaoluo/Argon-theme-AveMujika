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
          site_icon_url: 'https://blog.sample-blog.dev/icon.png'
        }, url);
      }
      return htmlResponse('not found', 404);
    });

    const result = await detectSite('sample-blog.dev', { fetcher, resolver: publicResolver, timeoutMs: 100 });

    expect(result.state).toBe('ok');
    expect(result.detectedUrl).toBe('https://blog.sample-blog.dev/');
    expect(result.primary).toBe('编程小记');
    expect(result.subtitle).toBe('其实我也不知道建这个站拿来干嘛=-=');
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
  it('finds a backlink after normalizing the local www hostname', async () => {
    const fetcher: WorkerFetcher = vi.fn(async () => htmlResponse(`
      <a href="https://www.my-blog.top/posts/hello">My Blog</a>
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
