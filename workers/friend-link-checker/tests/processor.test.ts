import { beforeEach, describe, expect, it, vi } from 'vitest';
import { handleScheduled, processQueueMessage, type RuntimeServices } from '../src/processor';
import { verifySignedPayload } from '../src/security';
import type {
  DetectionSave,
  FriendLinkJob,
  JobStore,
  QueueMessage,
  RuntimeStateRecord,
  WorkerStatusSnapshot
} from '../src/types';

const NOW = 1_800_000_000;
const CALLBACK_SECRET = 'test-callback-secret-that-is-long-enough';

function queuedJob(overrides: Partial<FriendLinkJob> = {}): FriendLinkJob {
  return {
    id: 'link_123',
    generation: 'generation-123456',
    url: 'https://friend-site.top/',
    hostname: 'friend-site.top',
    linksPage: 'https://friend-site.top/friends',
    checkedUrl: '',
    status: 'queued',
    accessible: false,
    readable: false,
    detectedUrl: '',
    primary: '',
    subtitle: '',
    description: '',
    avatar: '',
    backlinkState: 'unknown',
    checkedAt: 0,
    nextCheckAt: NOW,
    failureCount: 0,
    attempts: 0,
    lastError: '',
    callbackStatus: 'not_ready',
    callbackAttempts: 0,
    callbackNextAttemptAt: 0,
    callbackLastError: '',
    createdAt: NOW,
    updatedAt: NOW,
    ...overrides,
    diagnostics: overrides.diagnostics ?? []
  };
}

class ProcessingStore implements JobStore {
  jobs = new Map<string, FriendLinkJob>();
  expiredNoncesDeletedAt = 0;
  runtime = new Map<string, RuntimeStateRecord>();

  constructor(...jobs: FriendLinkJob[]) {
    for (const job of jobs) this.jobs.set(job.id, job);
  }

  async acceptSubmission(): Promise<void> {
    throw new Error('not used by this test');
  }

  async consumeNonce(): Promise<void> {
    throw new Error('not used by this test');
  }

  async getJob(id: string): Promise<FriendLinkJob | null> {
    return this.jobs.get(id) ?? null;
  }

  async claimQueuedJob(id: string, generation: string): Promise<FriendLinkJob | null> {
    const job = this.jobs.get(id);
    if (!job || job.generation !== generation || job.status !== 'queued') return null;
    const running = { ...job, status: 'running' as const, updatedAt: NOW };
    this.jobs.set(id, running);
    return running;
  }

  async saveDetection(id: string, generation: string, detection?: DetectionSave): Promise<void> {
    const job = this.jobs.get(id);
    if (!job || job.generation !== generation || !detection) return;
    this.jobs.set(id, {
      ...job,
      status: detection.site.state === 'ok' ? 'complete' : 'unknown',
      accessible: detection.site.accessible,
      readable: detection.site.readable,
      detectedUrl: detection.site.detectedUrl,
      primary: detection.site.primary,
      subtitle: detection.site.subtitle,
      description: detection.site.description,
      avatar: detection.site.avatar,
      linksPage: job.linksPage || detection.site.linksPage,
      checkedUrl: detection.checkedUrl,
      backlinkState: detection.backlinkState,
      checkedAt: detection.checkedAt,
      nextCheckAt: detection.nextCheckAt,
      attempts: job.attempts + 1,
      failureCount: detection.error === '' ? 0 : job.failureCount + 1,
      lastError: detection.error,
      callbackStatus: 'pending',
      callbackAttempts: 0,
      callbackNextAttemptAt: detection.checkedAt,
      callbackLastError: '',
      diagnostics: detection.diagnostics,
      updatedAt: detection.checkedAt
    });
  }

  async markCallbackDelivered(id: string, generation: string): Promise<void> {
    const job = this.jobs.get(id);
    if (job?.generation === generation) {
      this.jobs.set(id, {
        ...job,
        callbackStatus: 'delivered',
        callbackNextAttemptAt: 0,
        callbackLastError: ''
      });
    }
  }

  async deferCallback(id: string, generation: string, nextAttemptAt: number, _now?: number, error = ''): Promise<void> {
    const job = this.jobs.get(id);
    if (job?.generation === generation) {
      this.jobs.set(id, {
        ...job,
        callbackAttempts: job.callbackAttempts + 1,
        callbackNextAttemptAt: nextAttemptAt,
        callbackLastError: error
      });
    }
  }

  async listDueJobs(now: number, limit = 25): Promise<FriendLinkJob[]> {
    return [...this.jobs.values()]
      .filter((job) => (
        (job.callbackStatus === 'pending' && job.callbackNextAttemptAt <= now) ||
        (job.callbackStatus !== 'pending' && job.nextCheckAt <= now)
      ))
      .slice(0, limit);
  }

  async markJobsQueued(messages: QueueMessage[]): Promise<void> {
    for (const message of messages) {
      const job = this.jobs.get(message.jobId);
      if (job?.generation === message.generation && job.callbackStatus !== 'pending') {
        this.jobs.set(message.jobId, { ...job, status: 'queued', callbackStatus: 'not_ready' });
      }
    }
  }

  async deleteExpiredNonces(now: number): Promise<void> {
    this.expiredNoncesDeletedAt = now;
  }

  async setRuntimeState(state: RuntimeStateRecord): Promise<void> {
    this.runtime.set(state.kind, state);
  }

  async getStatusSnapshot(): Promise<WorkerStatusSnapshot> {
    throw new Error('not used by this test');
  }
}

function makeServices(store: ProcessingStore): RuntimeServices & {
  sent: QueueMessage[];
  sentDelays: number[];
  callbacks: Request[];
} {
  const sent: QueueMessage[] = [];
  const sentDelays: number[] = [];
  const callbacks: Request[] = [];
  const fetcher = vi.fn(async (input: string, init?: RequestInit) => {
    if (input === 'https://blog.sample-blog.dev/wp-json/argon/v1/friend-link-result') {
      callbacks.push(new Request(input, init));
      return new Response(null, { status: 204 });
    }
    if (input === 'https://blog.friend-site.top/') {
      return new Response(`
        <!doctype html><html><head>
          <title>编程小记</title>
          <meta name="description" content="其实我也不知道建这个站拿来干嘛=-=">
        </head><body></body></html>
      `, { status: 200, headers: { 'content-type': 'text/html' } });
    }
    if (input === 'https://friend-site.top/friends' || input === 'https://blog.friend-site.top/friends') {
      return new Response('<a href="https://blog.sample-blog.dev/">本站</a>', { status: 200 });
    }
    return new Response('missing', { status: 404 });
  });

  return {
    config: {
      allowedOrigin: 'https://blog.sample-blog.dev',
      siteUrl: 'https://blog.sample-blog.dev',
      callbackUrl: 'https://blog.sample-blog.dev/wp-json/argon/v1/friend-link-result',
      submitSecret: 'test-submit-secret-that-is-long-enough',
      callbackSecret: CALLBACK_SECRET,
      adminReadToken: 'read-token'
    },
    store,
    sent,
    sentDelays,
    callbacks,
    queue: {
      async send(message) {
        sent.push(message);
      },
      async sendBatch(messages) {
        sent.push(...messages.map((message) => message.body));
        sentDelays.push(...messages.map((message) => message.delaySeconds ?? 0));
      },
      async metrics() {
        return { backlogCount: 0, backlogBytes: 0, oldestMessageTimestamp: 0 };
      }
    },
    fetcher,
    resolver: async () => ['93.184.216.34'],
    now: () => NOW,
    queueDelaySeconds: () => 45,
    recheckDelaySeconds: (_job, succeeded) => succeeded ? 86_400 : 21_600
  };
}

describe('Queue consumer', () => {
  let store: ProcessingStore;
  let services: ReturnType<typeof makeServices>;

  beforeEach(() => {
    store = new ProcessingStore(queuedJob());
    services = makeServices(store);
  });

  it('detects the site and backlink, then sends a signed callback', async () => {
    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      status: 'complete',
      detectedUrl: 'https://blog.friend-site.top/',
      primary: '编程小记',
      backlinkState: 'found',
      callbackStatus: 'delivered',
      nextCheckAt: NOW + 86_400
    });
    expect(services.callbacks).toHaveLength(1);

    const callback = services.callbacks[0];
    const rawBody = await callback.text();
    const verified = await verifySignedPayload(callback.headers, rawBody, CALLBACK_SECRET, NOW);
    expect(verified).not.toBeNull();
    expect(JSON.parse(rawBody)).toMatchObject({
      version: 1,
      job_id: 'link_123',
      generation: 'generation-123456',
      result: {
        backlink_state: 'found',
        checked_url: 'https://friend-site.top/friends',
        source: 'cloudflare_worker'
      }
    });
    expect(store.runtime.get('queue')).toMatchObject({
      status: 'ok',
      jobId: 'link_123',
      detail: { disposition: 'ack' }
    });
  });

  it('discovers a missing friend-links page and checks it through the worker', async () => {
    store = new ProcessingStore(queuedJob({ linksPage: '' }));
    services = makeServices(store);
    const baseFetcher = services.fetcher;
    services.fetcher = vi.fn(async (input: string, init?: RequestInit) => {
      if (input === 'https://blog.friend-site.top/') {
        return new Response(`
          <!doctype html><html><head><link rel="icon" href="/icon.png"></head>
          <body><a href="/friends">友情链接</a></body></html>
        `, { status: 200 });
      }
      return baseFetcher(input, init);
    });

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      linksPage: 'https://blog.friend-site.top/friends',
      avatar: 'https://blog.friend-site.top/icon.png',
      backlinkState: 'found'
    });
    const callback = services.callbacks[0];
    const payload = JSON.parse(await callback.text());
    expect(payload.result.links_page).toBe('https://blog.friend-site.top/friends');
    expect(payload.result.checked_url).toBe('https://blog.friend-site.top/friends');
  });

  it('discovers and persists a common links page when the homepage has no friend-links navigation', async () => {
    store = new ProcessingStore(queuedJob({ linksPage: '' }));
    services = makeServices(store);
    const baseFetcher = services.fetcher;
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input: string, init?: RequestInit) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://blog.friend-site.top/') {
        return new Response(`
          <!doctype html><html><head>
            <title>Friend Site</title>
            <meta name="description" content="Homepage without a friend-links navigation item">
          </head><body><main>Recent posts</main></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url === 'https://blog.friend-site.top/links/') {
        return new Response(`
          <!doctype html><html><head><title>友情链接</title></head>
          <body><main class="friend-links"><a href="https://blog.sample-blog.dev/">本站</a></main></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      return baseFetcher(input, init);
    });

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      status: 'complete',
      linksPage: 'https://blog.friend-site.top/links/',
      checkedUrl: 'https://blog.friend-site.top/links/',
      backlinkState: 'found',
      callbackStatus: 'delivered'
    });
    const payload = JSON.parse(await services.callbacks[0].text());
    expect(payload.result).toMatchObject({
      links_page: 'https://blog.friend-site.top/links/',
      checked_url: 'https://blog.friend-site.top/links/',
      backlink_state: 'found'
    });
    expect(requestedUrls.some((url) => url.includes('friend-site.top/wp-json/'))).toBe(false);
  });

  it('passes explicit homepage REST evidence into WordPress Pages discovery', async () => {
    store = new ProcessingStore(queuedJob({ linksPage: '' }));
    services = makeServices(store);
    const baseFetcher = services.fetcher;
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input: string, init?: RequestInit) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://blog.friend-site.top/') {
        return new Response(`
          <!doctype html><html><head>
            <title>WordPress Friend</title>
            <meta name="description" content="Homepage with REST discovery evidence">
            <link rel="https://api.w.org/" href="https://blog.friend-site.top/wp-json/">
          </head><body><main>Recent posts</main></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url.startsWith('https://blog.friend-site.top/wp-json/wp/v2/pages')) {
        return new Response(JSON.stringify([{
          link: 'https://blog.friend-site.top/community/',
          slug: 'community',
          title: { rendered: '友情链接' }
        }]), { status: 200, headers: { 'content-type': 'application/json' } });
      }
      if (url === 'https://blog.friend-site.top/community/') {
        return new Response(`
          <!doctype html><html><head><title>友情链接</title></head>
          <body><main class="friend-links"><a href="https://blog.sample-blog.dev/">本站</a></main></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      return baseFetcher(input, init);
    });

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      status: 'complete',
      linksPage: 'https://blog.friend-site.top/community/',
      checkedUrl: 'https://blog.friend-site.top/community/',
      backlinkState: 'found',
      callbackStatus: 'delivered'
    });
    expect(requestedUrls.some((url) => url.startsWith('https://blog.friend-site.top/wp-json/wp/v2/pages'))).toBe(true);
    expect(requestedUrls).not.toContain('https://blog.friend-site.top/wp-json/');
  });

  it('explains when the site is readable but no friend-links page can be discovered', async () => {
    store = new ProcessingStore(queuedJob({ linksPage: '' }));
    services = makeServices(store);

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      status: 'complete',
      linksPage: '',
      backlinkState: 'unknown',
      lastError: 'links_page_not_found',
      callbackStatus: 'delivered',
      nextCheckAt: NOW + 21_600
    });
    const payload = JSON.parse(await services.callbacks[0].text());
    expect(payload.result.error).toBe('links_page_not_found');
  });

  it('distinguishes an unreadable friend-links page from an unreadable target site', async () => {
    const baseFetcher = services.fetcher;
    services.fetcher = vi.fn(async (input: string, init?: RequestInit) => {
      if (input === 'https://friend-site.top/friends' || input === 'https://blog.friend-site.top/friends') {
        return new Response('blocked', { status: 403 });
      }
      return baseFetcher(input, init);
    });

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      status: 'complete',
      backlinkState: 'unknown',
      lastError: 'links_page_unreadable',
      callbackStatus: 'delivered',
      nextCheckAt: NOW + 21_600
    });
  });

  it('uses WordPress OPML fallback for automatic queue checks when the page is unreadable', async () => {
    const baseFetcher = services.fetcher;
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input: string, init?: RequestInit) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://blog.friend-site.top/') {
        return new Response(`
          <!doctype html><html><head>
            <title>WordPress Friend</title>
            <meta name="description" content="A complete homepage">
            <link rel="https://api.w.org/" href="https://blog.friend-site.top/wp-json/">
          </head><body></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url === 'https://friend-site.top/friends' || url === 'https://blog.friend-site.top/friends') {
        return new Response('temporarily blocked', { status: 403 });
      }
      if (url.startsWith('https://blog.friend-site.top/wp-json/wp/v2/pages?')) {
        return new Response(JSON.stringify([{
          link: 'https://blog.friend-site.top/friends',
          slug: 'friends',
          title: { rendered: '友情链接' },
          content: { rendered: '<p>Friend links are rendered by the theme.</p>' }
        }]), { status: 200, headers: { 'content-type': 'application/json' } });
      }
      if (url === 'https://blog.friend-site.top/wp-links-opml.php') {
        return new Response(`<?xml version="1.0"?>
          <opml version="1.0"><body>
            <outline text="Local site" type="link" htmlUrl="https://blog.sample-blog.dev/" />
          </body></opml>`, { status: 200, headers: { 'content-type': 'text/xml' } });
      }
      return baseFetcher(input, init);
    });

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      status: 'complete',
      checkedUrl: 'https://blog.friend-site.top/wp-links-opml.php',
      backlinkState: 'found',
      lastError: '',
      callbackStatus: 'delivered',
      nextCheckAt: NOW + 86_400
    });
    expect(requestedUrls.some((url) => url.includes('/wp-json/wp/v2/pages?'))).toBe(true);
    expect(requestedUrls).toContain('https://blog.friend-site.top/wp-links-opml.php');
  });

  it('keeps blocked targets unknown and never reports a missing backlink from failure', async () => {
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input: string, init?: RequestInit) => {
      requestedUrls.push(String(input));
      if (input === services.config.callbackUrl) {
        services.callbacks.push(new Request(input, init));
        return new Response(null, { status: 204 });
      }
      return new Response('blocked', { status: 403 });
    });

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(store.jobs.get('link_123')).toMatchObject({
      status: 'unknown',
      accessible: true,
      readable: false,
      backlinkState: 'unknown',
      lastError: 'target_unreadable',
      callbackStatus: 'delivered'
    });
    expect(requestedUrls.some((url) => url.includes('friend-site.top/wp-json/'))).toBe(false);
  });

  it('ignores an old generation after the same WordPress link ID is resubmitted', async () => {
    store.jobs.set('link_123', queuedJob({ generation: 'generation-new-456' }));

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-old-123' }, services);

    expect(services.fetcher).not.toHaveBeenCalled();
    expect(store.jobs.get('link_123')).toMatchObject({
      generation: 'generation-new-456',
      status: 'queued'
    });
  });

  it('backs off a failed callback instead of retrying it in a tight loop', async () => {
    store.jobs.set('link_123', queuedJob({
      status: 'complete',
      callbackStatus: 'pending',
      callbackNextAttemptAt: NOW
    }));
    services.fetcher = vi.fn(async () => new Response('unavailable', { status: 503 }));

    await expect(
      processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services)
    ).resolves.toMatchObject({ disposition: 'retry', delaySeconds: 300, error: 'callback_http_503' });
    expect(store.jobs.get('link_123')).toMatchObject({
      callbackAttempts: 1,
      callbackNextAttemptAt: NOW + 300,
      callbackLastError: 'callback_http_503'
    });

    await expect(
      processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services)
    ).resolves.toMatchObject({ disposition: 'retry', delaySeconds: 300 });
    expect(services.fetcher).toHaveBeenCalledTimes(1);
    expect(store.runtime.get('queue')).toMatchObject({
      status: 'deferred',
      detail: { disposition: 'retry', error: 'callback_http_503' }
    });
  });

  it('bounds callback metadata to the WordPress request limit', async () => {
    const oversized = '编'.repeat(20_000);
    store.jobs.set('link_123', queuedJob({
      status: 'complete',
      callbackStatus: 'pending',
      callbackNextAttemptAt: NOW,
      checkedAt: NOW,
      detectedUrl: `https://friend-site.top/${'a'.repeat(20_000)}`,
      primary: oversized,
      subtitle: oversized,
      description: oversized,
      avatar: `https://friend-site.top/${'b'.repeat(20_000)}`,
      lastError: oversized
    }));

    await processQueueMessage({ jobId: 'link_123', generation: 'generation-123456' }, services);

    expect(services.callbacks).toHaveLength(1);
    const rawBody = await services.callbacks[0].text();
    const payload = JSON.parse(rawBody);
    expect(new TextEncoder().encode(rawBody).byteLength).toBeLessThanOrEqual(16 * 1024);
    expect(payload.result.primary).toHaveLength(200);
    expect(payload.result.subtitle).toHaveLength(300);
    expect(payload.result.description).toHaveLength(1_000);
    expect(payload.result.detected_url.length).toBeLessThanOrEqual(2_048);
    expect(payload.result.avatar.length).toBeLessThanOrEqual(2_048);
    expect(payload.result.error).toHaveLength(300);
  });
});

describe('Cron scheduler', () => {
  it('only enqueues due job IDs and removes expired submission nonces', async () => {
    const due = queuedJob({ id: 'due', nextCheckAt: NOW - 1, status: 'complete' });
    const future = queuedJob({ id: 'future', nextCheckAt: NOW + 60, status: 'complete' });
    const store = new ProcessingStore(due, future);
    const services = makeServices(store);

    await handleScheduled(services);

    expect(services.sent).toEqual([{ jobId: 'due', generation: 'generation-123456' }]);
    expect(store.jobs.get('due')).toMatchObject({ status: 'queued', callbackStatus: 'not_ready' });
    expect(store.jobs.get('future')).toMatchObject({ status: 'complete' });
    expect(store.expiredNoncesDeletedAt).toBe(NOW);
    expect(services.sentDelays).toEqual([45]);
    expect(store.runtime.get('cron')).toMatchObject({
      status: 'ok',
      detail: { due_count: 1, enqueued_count: 1 }
    });
  });

  it('assigns an independent random delay to every due job', async () => {
    const first = queuedJob({ id: 'first', nextCheckAt: NOW - 2, status: 'complete' });
    const second = queuedJob({ id: 'second', nextCheckAt: NOW - 1, status: 'complete' });
    const store = new ProcessingStore(first, second);
    const services = makeServices(store);
    const delays = [31, 179];
    services.queueDelaySeconds = () => delays.shift() ?? 60;

    await handleScheduled(services);

    expect(services.sent).toEqual([
      { jobId: 'first', generation: 'generation-123456' },
      { jobId: 'second', generation: 'generation-123456' }
    ]);
    expect(services.sentDelays).toEqual([31, 179]);
  });
});
