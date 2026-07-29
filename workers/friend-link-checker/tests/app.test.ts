import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { handleFetch, type RuntimeServices } from '../src/app';
import { signHmac } from '../src/security';
import type {
  FriendLinkJob,
  JobStore,
  QueueMessage,
  RuntimeStateRecord,
  WorkerStatusSnapshot
} from '../src/types';

class MemoryStore implements JobStore {
  jobs = new Map<string, FriendLinkJob>();
  nonces = new Set<string>();
  runtime: WorkerStatusSnapshot['runtime'] = {};

  async acceptSubmission(job: FriendLinkJob, nonce: string): Promise<void> {
    if (this.nonces.has(nonce)) throw new Error('submission_replayed');
    this.nonces.add(nonce);
    this.jobs.set(job.id, job);
  }

  async consumeNonce(nonce: string): Promise<void> {
    if (this.nonces.has(nonce)) throw new Error('submission_replayed');
    this.nonces.add(nonce);
  }

  async getJob(id: string): Promise<FriendLinkJob | null> {
    return this.jobs.get(id) ?? null;
  }

  async claimQueuedJob(): Promise<FriendLinkJob | null> {
    throw new Error('not used by this test');
  }

  async saveDetection(): Promise<void> {
    throw new Error('not used by this test');
  }

  async markCallbackDelivered(id: string, generation: string): Promise<void> {
    const job = this.jobs.get(id);
    if (job?.generation === generation) this.jobs.set(id, { ...job, callbackStatus: 'delivered' });
  }

  async deferCallback(): Promise<void> {
    throw new Error('not used by this test');
  }

  async listDueJobs(): Promise<FriendLinkJob[]> {
    return [];
  }

  async markJobsQueued(): Promise<void> {}

  async deleteExpiredNonces(): Promise<void> {}

  async setRuntimeState(state: RuntimeStateRecord): Promise<void> {
    this.runtime[state.kind] = state;
  }

  async getStatusSnapshot(now: number): Promise<WorkerStatusSnapshot> {
    const jobs = [...this.jobs.values()].sort((left, right) => right.updatedAt - left.updatedAt);
    return {
      aggregate: {
        total: jobs.length,
        queued: jobs.filter((job) => job.status === 'queued').length,
        running: jobs.filter((job) => job.status === 'running').length,
        complete: jobs.filter((job) => job.status === 'complete').length,
        unknown: jobs.filter((job) => job.status === 'unknown').length,
        error: jobs.filter((job) => job.status === 'error').length,
        callbackPending: jobs.filter((job) => job.callbackStatus === 'pending').length,
        callbackDelivered: jobs.filter((job) => job.callbackStatus === 'delivered').length,
        dueChecks: jobs.filter((job) => job.callbackStatus !== 'pending' && job.nextCheckAt <= now).length,
        dueCallbacks: jobs.filter((job) => job.callbackStatus === 'pending' && job.callbackNextAttemptAt <= now).length
      },
      recentJobs: jobs,
      runtime: this.runtime
    };
  }
}

const NOW = 1_800_000_000;
const SUBMIT_SECRET = 'test-submit-secret-that-is-long-enough';

function makeServices(): RuntimeServices & { store: MemoryStore; sent: QueueMessage[] } {
  const store = new MemoryStore();
  const sent: QueueMessage[] = [];
  return {
    config: {
      allowedOrigin: 'https://blog.sample-blog.dev',
      siteUrl: 'https://blog.sample-blog.dev',
      callbackUrl: 'https://blog.sample-blog.dev/wp-json/argon/v1/friend-link-result',
      submitSecret: SUBMIT_SECRET,
      callbackSecret: 'test-callback-secret-that-is-long-enough',
      adminReadToken: 'read-token'
    },
    store,
    sent,
    queue: {
      async send(message) {
        sent.push(message);
      },
      async sendBatch(messages) {
        sent.push(...messages.map((message) => message.body));
      },
      async metrics() {
        return { backlogCount: 3, backlogBytes: 512, oldestMessageTimestamp: NOW * 1000 };
      }
    },
    fetcher: vi.fn(),
    resolver: async () => ['93.184.216.34'],
    now: () => NOW,
    queueDelaySeconds: () => 45,
    recheckDelaySeconds: () => 86_400
  };
}

async function signedSubmission(
  bodyValue: Record<string, unknown>,
  overrides: { timestamp?: number; nonce?: string; origin?: string } = {}
): Promise<Request> {
  const body = JSON.stringify(bodyValue);
  const timestamp = overrides.timestamp ?? NOW;
  const nonce = overrides.nonce ?? 'nonce-1234567890';
  const signature = await signHmac(SUBMIT_SECRET, `${timestamp}.${nonce}.${body}`);
  return new Request('https://neutral-checker.workers.dev/v1/jobs', {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      origin: overrides.origin ?? 'https://blog.sample-blog.dev',
      'x-argon-timestamp': String(timestamp),
      'x-argon-nonce': nonce,
      'x-argon-signature': `sha256=${signature}`
    },
    body
  });
}

async function signedResultRead(
  jobId: string,
  generation: string,
  nonce = 'result-nonce-123456'
): Promise<Request> {
  const body = JSON.stringify({ generation });
  const signature = await signHmac(SUBMIT_SECRET, `${NOW}.${nonce}.${body}`);
  return new Request(`https://neutral-checker.workers.dev/v1/jobs/${jobId}/result`, {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-argon-timestamp': String(NOW),
      'x-argon-nonce': nonce,
      'x-argon-signature': `sha256=${signature}`
    },
    body
  });
}

async function signedStatus(
  bodyValue: Record<string, unknown> = { site_url: 'https://blog.sample-blog.dev', action: 'status' },
  nonce = 'status-nonce-123456'
): Promise<Request> {
  const body = JSON.stringify(bodyValue);
  const signature = await signHmac(SUBMIT_SECRET, `${NOW}.${nonce}.${body}`);
  return new Request('https://neutral-checker.workers.dev/v1/status', {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-argon-timestamp': String(NOW),
      'x-argon-nonce': nonce,
      'x-argon-signature': `sha256=${signature}`
    },
    body
  });
}

async function signedProbe(
  bodyValue: Record<string, unknown> = {
    action: 'probe',
    mode: 'backlink',
    site_url: 'https://blog.sample-blog.dev',
    url: 'https://sample-blog.dev/'
  },
  nonce = 'probe-nonce-123456'
): Promise<Request> {
  const body = JSON.stringify(bodyValue);
  const signature = await signHmac(SUBMIT_SECRET, `${NOW}.${nonce}.${body}`);
  return new Request('https://neutral-checker.workers.dev/v1/probe', {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-argon-timestamp': String(NOW),
      'x-argon-nonce': nonce,
      'x-argon-signature': `sha256=${signature}`
    },
    body
  });
}

describe('Worker HTTP API', () => {
  let services: ReturnType<typeof makeServices>;

  beforeEach(() => {
    services = makeServices();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('reports a stable public health response', async () => {
    const response = await handleFetch(
      new Request('https://neutral-checker.workers.dev/health'),
      services
    );

    expect(response.status).toBe(200);
    expect(response.headers.get('cache-control')).toBe('no-store');
    await expect(response.json()).resolves.toEqual({ ok: true, service: 'friend-link-checker' });
  });

  it('accepts a valid signed task and only queues its job ID', async () => {
    const request = await signedSubmission({
      job_id: 'link_123',
      url: 'sample-blog.dev',
      links_page: 'https://blog.sample-blog.dev/friends'
    });

    const response = await handleFetch(request, services);

    expect(response.status).toBe(202);
    await expect(response.json()).resolves.toMatchObject({ success: true, job_id: 'link_123' });
    expect(services.sent).toEqual([{ jobId: 'link_123', generation: 'nonce-1234567890' }]);
    expect(services.store.jobs.get('link_123')).toMatchObject({
      generation: 'nonce-1234567890',
      url: 'https://sample-blog.dev/',
      linksPage: 'https://blog.sample-blog.dev/friends',
      status: 'queued'
    });
  });

  it('rejects invalid signatures before storing or queueing a task', async () => {
    const request = new Request('https://neutral-checker.workers.dev/v1/jobs', {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        origin: 'https://blog.sample-blog.dev',
        'x-argon-timestamp': String(NOW),
        'x-argon-nonce': 'nonce-1234567890',
        'x-argon-signature': 'sha256=deadbeef'
      },
      body: JSON.stringify({ job_id: 'link_123', url: 'sample-blog.dev' })
    });

    const response = await handleFetch(request, services);

    expect(response.status).toBe(401);
    expect(services.sent).toEqual([]);
    expect(services.store.jobs.size).toBe(0);
  });

  it('rejects replayed nonces and backlink pages outside the target family', async () => {
    const first = await signedSubmission({ job_id: 'link_123', url: 'sample-blog.dev' });
    expect((await handleFetch(first, services)).status).toBe(202);

    const replay = await signedSubmission({ job_id: 'link_456', url: 'other.top' });
    expect((await handleFetch(replay, services)).status).toBe(409);

    const foreignPage = await signedSubmission(
      { job_id: 'link_789', url: 'sample-blog.dev', links_page: 'https://attacker.top/friends' },
      { nonce: 'nonce-abcdefghijkl' }
    );
    expect((await handleFetch(foreignPage, services)).status).toBe(400);
  });

  it('blocks browser submissions from an unconfigured origin', async () => {
    const request = await signedSubmission(
      { job_id: 'link_123', url: 'sample-blog.dev' },
      { origin: 'https://attacker.top' }
    );
    const response = await handleFetch(request, services);
    expect(response.status).toBe(403);
  });

  it('refuses submissions while deployment placeholders remain configured', async () => {
    services.config.callbackUrl = 'https://blog.example.com/wp-json/argon/v1/friend-link-result';
    const request = await signedSubmission({ job_id: 'link_123', url: 'sample-blog.dev' });

    const response = await handleFetch(request, services);

    expect(response.status).toBe(503);
    expect(services.sent).toEqual([]);
  });

  it('requires the admin token before returning a job result', async () => {
    const submit = await signedSubmission({ job_id: 'link_123', url: 'sample-blog.dev' });
    await handleFetch(submit, services);

    const unauthorized = await handleFetch(
      new Request('https://neutral-checker.workers.dev/v1/jobs/link_123'),
      services
    );
    expect(unauthorized.status).toBe(401);

    const authorized = await handleFetch(
      new Request('https://neutral-checker.workers.dev/v1/jobs/link_123', {
        headers: { authorization: 'Bearer read-token' }
      }),
      services
    );
    expect(authorized.status).toBe(200);
    await expect(authorized.json()).resolves.toMatchObject({ id: 'link_123', status: 'queued' });
  });

  it('lets WordPress pull a terminal result with its signed generation when callback delivery is blocked', async () => {
    const submit = await signedSubmission({ job_id: 'link_123', url: 'sample-blog.dev' });
    await handleFetch(submit, services);
    const queued = services.store.jobs.get('link_123')!;
    services.store.jobs.set('link_123', {
      ...queued,
      status: 'complete',
      accessible: true,
      readable: true,
      detectedUrl: 'https://blog.sample-blog.dev/',
      primary: '编程小记',
      avatar: 'https://blog.sample-blog.dev/author.png',
      backlinkState: 'found',
      checkedAt: NOW,
      callbackStatus: 'pending',
      callbackLastError: 'callback_http_403'
    });

    const response = await handleFetch(
      await signedResultRead('link_123', 'nonce-1234567890'),
      services
    );

    expect(response.status).toBe(200);
    await expect(response.json()).resolves.toMatchObject({
      version: 1,
      job_id: 'link_123',
      generation: 'nonce-1234567890',
      result: {
        state: 'complete',
        primary: '编程小记',
        avatar: 'https://blog.sample-blog.dev/author.png',
        backlink_state: 'found',
        source: 'cloudflare_worker'
      }
    });
    expect(services.store.jobs.get('link_123')).toMatchObject({ callbackStatus: 'delivered' });
  });

  it('does not expose a result for a stale generation and reports queued work as pending', async () => {
    const submit = await signedSubmission({ job_id: 'link_123', url: 'sample-blog.dev' });
    await handleFetch(submit, services);

    const stale = await handleFetch(
      await signedResultRead('link_123', 'stale-generation-123', 'result-nonce-stale-1'),
      services
    );
    expect(stale.status).toBe(409);

    const pending = await handleFetch(
      await signedResultRead('link_123', 'nonce-1234567890', 'result-nonce-pending-1'),
      services
    );
    expect(pending.status).toBe(202);
    await expect(pending.json()).resolves.toMatchObject({ success: true, status: 'queued' });
  });

  it('returns an authenticated no-store Worker status snapshot without exposing secrets', async () => {
    await handleFetch(
      await signedSubmission({ job_id: 'link_123', url: 'sample-blog.dev' }),
      services
    );

    const response = await handleFetch(await signedStatus(), services);
    const rawBody = await response.text();
    const payload = JSON.parse(rawBody);

    expect(response.status).toBe(200);
    expect(response.headers.get('cache-control')).toBe('no-store');
    expect(payload).toMatchObject({
      success: true,
      server_time: NOW,
      service: { name: 'friend-link-checker', status: 'ok' },
      queue: { available: true, backlog_count: 3, backlog_bytes: 512 },
      database: { available: true },
      aggregate: { total: 1, queued: 1 },
      random_schedule: {
        cron: '*/5 * * * *',
        queue_delay_seconds: { min: 30, max: 180, per_job: true }
      }
    });
    expect(payload.recent_jobs).toHaveLength(1);
    expect(payload.recent_jobs[0]).toMatchObject({ id: 'link_123', status: 'queued', diagnostics: [] });
    expect(rawBody).not.toContain(SUBMIT_SECRET);
    expect(rawBody).not.toContain('test-callback-secret-that-is-long-enough');
    expect(rawBody).not.toContain('read-token');
  });

  it('rejects status reads for a different site origin or action', async () => {
    const wrongSite = await handleFetch(
      await signedStatus({ site_url: 'https://attacker.top', action: 'status' }, 'status-nonce-wrong-site'),
      services
    );
    expect(wrongSite.status).toBe(403);

    const wrongAction = await handleFetch(
      await signedStatus({ site_url: 'https://blog.sample-blog.dev', action: 'delete' }, 'status-nonce-wrong-action'),
      services
    );
    expect(wrongAction.status).toBe(400);
  });

  it('runs a signed synchronous HTML probe without creating or queueing a job', async () => {
    services.fetcher = vi.fn(async (input) => {
      const url = String(input);
      if (url === 'https://sample-blog.dev/') {
        return new Response(`
          <!doctype html><html><head><title>Sample Friend</title><link rel="icon" href="/avatar.png"></head>
          <body><a href="/friends/">Friend links</a></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url === 'https://sample-blog.dev/friends/') {
        return new Response('<a href="https://blog.sample-blog.dev/">Local site</a>', {
          status: 200,
          headers: { 'content-type': 'text/html' }
        });
      }
      return new Response('not found', { status: 404 });
    });

    const response = await handleFetch(await signedProbe(), services);

    expect(response.status).toBe(200);
    expect(response.headers.get('cache-control')).toBe('no-store');
    await expect(response.json()).resolves.toMatchObject({
      success: true,
      version: 1,
      checked_at: NOW,
      result: {
        state: 'complete',
        detected_url: 'https://sample-blog.dev/',
        primary: 'Sample Friend',
        avatar: 'https://sample-blog.dev/avatar.png',
        links_page: 'https://sample-blog.dev/friends/',
        checked_url: 'https://sample-blog.dev/friends/',
        backlink_state: 'found',
        detection_source: 'html',
        source: 'cloudflare_worker',
        error: ''
      }
    });
    expect(services.store.jobs.size).toBe(0);
    expect(services.sent).toEqual([]);

    const statusResponse = await handleFetch(
      await signedStatus(undefined, 'status-after-probe-1'),
      services
    );
    const statusPayload = await statusResponse.json() as { runtime: Record<string, unknown> };
    expect(statusPayload.runtime).toMatchObject({
      probe: {
        status: 'ok',
        started_at: NOW,
        finished_at: NOW,
        detail: { result_state: 'complete', backlink_state: 'found', error: '' }
      }
    });
  });

  it('starts the backlink check as soon as a sufficient HTTPS candidate batch succeeds', async () => {
    const requestedUrls: string[] = [];
    let releaseSlowFallbacks: (() => void) | undefined;
    const slowFallbacks = new Promise<void>((resolve) => {
      releaseSlowFallbacks = resolve;
    });
    services.fetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://www.sample-blog.dev/') {
        return new Response(`
          <!doctype html><html><head>
            <title>Sample Friend</title>
            <meta name="description" content="A complete homepage">
            <link rel="icon" href="/avatar.png">
          </head><body><a href="/friends/">Friend links</a></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url === 'https://www.sample-blog.dev/friends/') {
        return new Response('<a href="https://blog.sample-blog.dev/">Local site</a>', {
          status: 200,
          headers: { 'content-type': 'text/html' }
        });
      }
      if (url.startsWith('http://')) {
        await slowFallbacks;
      }
      return new Response('not found', { status: 404 });
    });

    let responseSettled = false;
    const responsePromise = handleFetch(
      await signedProbe({
        action: 'probe',
        mode: 'backlink',
        site_url: 'https://blog.sample-blog.dev',
        url: 'https://www.sample-blog.dev/'
      }, 'probe-fast-https-batch-1'),
      services
    );
    void responsePromise.then(() => { responseSettled = true; });

    const realSetTimeout = globalThis.setTimeout;
    for (let attempt = 0; attempt < 20 && !responseSettled && !requestedUrls.some((url) => url.startsWith('http://')); attempt += 1) {
      await new Promise<void>((resolve) => realSetTimeout(resolve, 0));
    }
    const reachedBacklinkBeforeSlowFallbacks = requestedUrls.includes('https://www.sample-blog.dev/friends/');
    const requestedSlowFallbackBeforeBacklink = requestedUrls.some((url) => url.startsWith('http://'));
    releaseSlowFallbacks?.();

    const response = await responsePromise;
    expect(response.status).toBe(200);
    await expect(response.json()).resolves.toMatchObject({
      success: true,
      result: {
        state: 'complete',
        avatar: 'https://www.sample-blog.dev/avatar.png',
        links_page: 'https://www.sample-blog.dev/friends/',
        checked_url: 'https://www.sample-blog.dev/friends/',
        backlink_state: 'found',
        error: ''
      }
    });
    expect(reachedBacklinkBeforeSlowFallbacks).toBe(true);
    expect(requestedSlowFallbackBeforeBacklink).toBe(false);
  });

  it('uses WordPress page and OPML fallbacks when a known friend-links page is unreadable', async () => {
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://www.sample-blog.dev/') {
        return new Response(`
          <!doctype html><html><head>
            <title>WordPress Friend</title>
            <meta name="description" content="A complete homepage">
            <link rel="https://api.w.org/" href="https://www.sample-blog.dev/wp-json/">
            <link rel="icon" href="/avatar.png">
          </head><body><a href="/links/">友情链接</a></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url === 'https://www.sample-blog.dev/links/') {
        return new Response('temporarily blocked', { status: 403 });
      }
      if (url.startsWith('https://www.sample-blog.dev/wp-json/wp/v2/pages?')) {
        return new Response(JSON.stringify([{
          link: 'https://www.sample-blog.dev/links/',
          slug: 'links',
          title: { rendered: '友情链接' },
          content: { rendered: '<p>以下友链由主题动态输出，申请规则见 <a href="https://link-rules.top/">说明</a>。</p>' }
        }]), { status: 200, headers: { 'content-type': 'application/json' } });
      }
      if (url === 'https://www.sample-blog.dev/wp-links-opml.php') {
        return new Response(`<?xml version="1.0"?>
          <opml version="1.0"><body>
            <outline text="Another friend" type="link" htmlUrl="https://another-friend.top/" />
          </body></opml>`, { status: 200, headers: { 'content-type': 'text/xml' } });
      }
      return new Response('not found', { status: 404 });
    });

    const response = await handleFetch(
      await signedProbe({
        action: 'probe',
        mode: 'backlink',
        site_url: 'https://blog.sample-blog.dev',
        url: 'https://www.sample-blog.dev/'
      }, 'probe-wordpress-opml-fallback-1'),
      services
    );

    expect(response.status).toBe(200);
    await expect(response.json()).resolves.toMatchObject({
      success: true,
      result: {
        state: 'complete',
        avatar: 'https://www.sample-blog.dev/avatar.png',
        links_page: 'https://www.sample-blog.dev/links/',
        checked_url: 'https://www.sample-blog.dev/links/',
        backlink_state: 'missing',
        error: ''
      }
    });
    expect(requestedUrls.some((url) => url.includes('/wp-json/wp/v2/pages?'))).toBe(true);
    expect(requestedUrls).toContain('https://www.sample-blog.dev/wp-links-opml.php');
  });

  it('finishes the slow WordPress fallback path within the synchronous probe budget', async () => {
    const request = await signedProbe({
      action: 'probe',
      mode: 'backlink',
      site_url: 'https://blog.sample-blog.dev',
      url: 'https://www.sample-blog.dev/'
    }, 'probe-wordpress-budget-1');
    const realSetTimeout = globalThis.setTimeout;
    vi.useFakeTimers();
    const requestedUrls: string[] = [];
    const delayedResponse = (
      delayMs: number,
      response: Response,
      signal?: AbortSignal | null
    ): Promise<Response> => new Promise((resolve, reject) => {
      let settled = false;
      const finish = (callback: () => void) => {
        if (settled) return;
        settled = true;
        clearTimeout(timer);
        signal?.removeEventListener('abort', abort);
        callback();
      };
      const abort = () => finish(() => reject(
        signal?.reason instanceof Error ? signal.reason : new Error('request_aborted')
      ));
      const timer = setTimeout(() => finish(() => resolve(response)), delayMs);
      if (signal?.aborted) abort();
      else signal?.addEventListener('abort', abort, { once: true });
    });
    services.fetcher = vi.fn(async (input, init) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://www.sample-blog.dev/') {
        return delayedResponse(5_500, new Response(`
          <!doctype html><html><head>
            <title>Budgeted WordPress Friend</title>
            <meta name="description" content="A complete homepage">
            <link rel="https://api.w.org/" href="https://www.sample-blog.dev/wp-json/">
            <link rel="icon" href="/avatar.png">
          </head><body><a href="/links/">Friend links</a></body></html>
        `, { headers: { 'content-type': 'text/html' } }), init?.signal);
      }
      if (url === 'https://www.sample-blog.dev/links/') {
        return delayedResponse(5_500, new Response('temporarily blocked', { status: 403 }), init?.signal);
      }
      if (url.startsWith('https://www.sample-blog.dev/wp-json/wp/v2/pages?')) {
        return delayedResponse(1_900, new Response('[]', {
          headers: { 'content-type': 'application/json' }
        }), init?.signal);
      }
      if (url === 'https://www.sample-blog.dev/wp-links-opml.php') {
        return delayedResponse(1_900, new Response('<opml version="1.0"><body><outline htmlUrl="https://blog.sample-blog.dev/" /></body></opml>', {
          headers: { 'content-type': 'text/xml' }
        }), init?.signal);
      }
      return new Response('not found', { status: 404 });
    });

    let responseSettled = false;
    const responsePromise = handleFetch(request, services);
    void responsePromise.then(() => { responseSettled = true; });
    const waitFor = async (condition: () => boolean) => {
      for (let attempt = 0; attempt < 50 && !condition(); attempt += 1) {
        await new Promise<void>((resolve) => realSetTimeout(resolve, 0));
      }
      return condition();
    };

    expect(await waitFor(() => requestedUrls.includes('https://www.sample-blog.dev/'))).toBe(true);
    await vi.advanceTimersByTimeAsync(5_500);
    expect(await waitFor(() => requestedUrls.includes('https://www.sample-blog.dev/links/'))).toBe(true);
    await vi.advanceTimersByTimeAsync(5_500);
    expect(await waitFor(() => requestedUrls.some((url) => url.includes('/wp-json/wp/v2/pages?')))).toBe(true);
    const fallbacksStartedTogether = requestedUrls.includes('https://www.sample-blog.dev/wp-links-opml.php');
    await vi.advanceTimersByTimeAsync(1_900);
    await waitFor(() => responseSettled);
    const settledWithinBudget = responseSettled;
    if (!responseSettled) {
      await waitFor(() => requestedUrls.includes('https://www.sample-blog.dev/wp-links-opml.php'));
      await vi.advanceTimersByTimeAsync(2_000);
      await waitFor(() => responseSettled);
    }
    const response = await responsePromise;

    expect(fallbacksStartedTogether).toBe(true);
    expect(settledWithinBudget).toBe(true);
    expect(response.status).toBe(200);
    await expect(response.json()).resolves.toMatchObject({
      success: true,
      result: {
        state: 'complete',
        avatar: 'https://www.sample-blog.dev/avatar.png',
        links_page: 'https://www.sample-blog.dev/links/',
        checked_url: 'https://www.sample-blog.dev/wp-links-opml.php',
        backlink_state: 'found',
        error: ''
      }
    });
    expect(requestedUrls.some((url) => url.includes('/wp-json/wp/v2/pages?'))).toBe(true);
    expect(requestedUrls).toContain('https://www.sample-blog.dev/wp-links-opml.php');
  });

  it('discovers and returns the checked links page for a backlink probe without homepage navigation', async () => {
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/') {
        return new Response(`
          <!doctype html><html><head>
            <title>Sample Friend</title>
            <meta name="description" content="Homepage without friend-links navigation">
          </head><body><main>Recent posts</main></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url === 'https://sample-blog.dev/links/') {
        return new Response(`
          <!doctype html><html><head><title>友情链接</title></head>
          <body><div class="friend-links"><a href="https://blog.sample-blog.dev/">Local site</a></div></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      return new Response('not found', { status: 404 });
    });

    const response = await handleFetch(await signedProbe(undefined, 'probe-discovered-links-1'), services);
    const payload = await response.json() as { result: Record<string, unknown> };

    expect(response.status).toBe(200);
    expect(payload.result).toMatchObject({
      state: 'complete',
      links_page: 'https://sample-blog.dev/links/',
      checked_url: 'https://sample-blog.dev/links/',
      backlink_state: 'found',
      detection_source: 'html',
      error: ''
    });
    expect(requestedUrls.some((url) => url.includes('/wp-json/'))).toBe(false);
  });

  it('uses WordPress Pages discovery only when a readable homepage exposes REST evidence', async () => {
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/') {
        return new Response(`
          <!doctype html><html><head>
            <title>WordPress Friend</title>
            <meta name="description" content="Homepage with explicit REST discovery">
            <link rel="https://api.w.org/" href="https://sample-blog.dev/wp-json/">
          </head><body><main>Recent posts</main></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages')) {
        return new Response(JSON.stringify([{
          link: 'https://sample-blog.dev/community/',
          slug: 'community',
          title: { rendered: '友情链接' }
        }]), { status: 200, headers: { 'content-type': 'application/json' } });
      }
      if (url === 'https://sample-blog.dev/community/') {
        return new Response(`
          <!doctype html><html><head><title>友情链接</title></head>
          <body><div class="friend-links"><a href="https://blog.sample-blog.dev/">Local site</a></div></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      return new Response('not found', { status: 404 });
    });

    const response = await handleFetch(await signedProbe(undefined, 'probe-wordpress-pages-1'), services);
    const payload = await response.json() as { result: Record<string, unknown> };

    expect(response.status).toBe(200);
    expect(payload.result).toMatchObject({
      state: 'complete',
      links_page: 'https://sample-blog.dev/community/',
      checked_url: 'https://sample-blog.dev/community/',
      backlink_state: 'found'
    });
    expect(requestedUrls.some((url) => url.startsWith('https://sample-blog.dev/wp-json/wp/v2/pages'))).toBe(true);
    expect(requestedUrls).not.toContain('https://sample-blog.dev/wp-json/');
  });

  it('rejects unsigned, cross-site and unsafe probe requests before fetching targets', async () => {
    const unsigned = new Request('https://neutral-checker.workers.dev/v1/probe', {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        'x-argon-timestamp': String(NOW),
        'x-argon-nonce': 'probe-unsigned-1234',
        'x-argon-signature': 'sha256=deadbeef'
      },
      body: JSON.stringify({
        action: 'probe',
        mode: 'backlink',
        site_url: 'https://blog.sample-blog.dev',
        url: 'https://sample-blog.dev/'
      })
    });
    expect((await handleFetch(unsigned, services)).status).toBe(401);

    const missingMode = await signedProbe({
      action: 'probe',
      site_url: 'https://blog.sample-blog.dev',
      url: 'https://sample-blog.dev/'
    }, 'probe-missing-mode-1');
    const missingModeResponse = await handleFetch(missingMode, services);
    expect(missingModeResponse.status).toBe(400);
    await expect(missingModeResponse.json()).resolves.toEqual({
      success: false,
      error: 'invalid_probe_mode'
    });

    const invalidMode = await signedProbe({
      action: 'probe',
      mode: 'metadata_and_backlink',
      site_url: 'https://blog.sample-blog.dev',
      url: 'https://sample-blog.dev/'
    }, 'probe-invalid-mode-1');
    expect((await handleFetch(invalidMode, services)).status).toBe(400);

    const wrongSite = await signedProbe({
      action: 'probe',
      mode: 'backlink',
      site_url: 'https://attacker.top',
      url: 'https://sample-blog.dev/'
    }, 'probe-wrong-site-1');
    expect((await handleFetch(wrongSite, services)).status).toBe(403);

    const privateTarget = await signedProbe({
      action: 'probe',
      mode: 'backlink',
      site_url: 'https://blog.sample-blog.dev',
      url: 'http://127.0.0.1/'
    }, 'probe-private-target-1');
    expect((await handleFetch(privateTarget, services)).status).toBe(400);

    const foreignLinksPage = await signedProbe({
      action: 'probe',
      mode: 'backlink',
      site_url: 'https://blog.sample-blog.dev',
      url: 'https://sample-blog.dev/',
      links_page: 'https://attacker.top/friends'
    }, 'probe-foreign-links-1');
    expect((await handleFetch(foreignLinksPage, services)).status).toBe(400);

    expect(services.fetcher).not.toHaveBeenCalled();
    expect(services.store.runtime.probe).toBeUndefined();
  });

  it('keeps info probes on homepage HTML and never reads the discovered friend-links page', async () => {
    const requestedUrls: string[] = [];
    services.fetcher = vi.fn(async (input) => {
      const url = String(input);
      requestedUrls.push(url);
      if (url === 'https://sample-blog.dev/') {
        return new Response(`
          <!doctype html><html><head><title>Sample Friend</title></head>
          <body><a href="/friends/">Friend links</a></body></html>
        `, { status: 200, headers: { 'content-type': 'text/html' } });
      }
      if (url === 'https://sample-blog.dev/friends/') {
        throw new Error('info_probe_must_not_read_friend_links');
      }
      return new Response('not found', { status: 404 });
    });

    const response = await handleFetch(await signedProbe({
      action: 'probe',
      mode: 'info',
      site_url: 'https://blog.sample-blog.dev',
      url: 'https://sample-blog.dev/'
    }, 'probe-info-only-1'), services);
    const payload = await response.json() as { result: Record<string, unknown> };

    expect(response.status).toBe(200);
    expect(payload.result).toMatchObject({
      state: 'complete',
      links_page: 'https://sample-blog.dev/friends/',
      checked_url: '',
      backlink_state: 'unknown',
      error: ''
    });
    expect(requestedUrls).not.toContain('https://sample-blog.dev/friends/');
  });

  it('consumes the signed probe nonce before fetching so a replay cannot trigger another probe', async () => {
    services.fetcher = vi.fn(async (input) => String(input) === 'https://sample-blog.dev/'
      ? new Response('<!doctype html><html><head><title>Sample Friend</title></head><body></body></html>')
      : new Response('not found', { status: 404 }));
    const body = {
      action: 'probe',
      mode: 'info',
      site_url: 'https://blog.sample-blog.dev',
      url: 'https://sample-blog.dev/'
    };

    const first = await handleFetch(
      await signedProbe(body, 'probe-replay-nonce-1'),
      services
    );
    expect(first.status).toBe(200);
    const fetchCount = vi.mocked(services.fetcher).mock.calls.length;

    const replay = await handleFetch(
      await signedProbe(body, 'probe-replay-nonce-1'),
      services
    );
    expect(replay.status).toBe(409);
    await expect(replay.json()).resolves.toEqual({ success: false, error: 'probe_replayed' });
    expect(vi.mocked(services.fetcher).mock.calls).toHaveLength(fetchCount);
  });

  it('aborts and settles every active fetch before returning the synchronous probe deadline', async () => {
    const request = await signedProbe(undefined, 'probe-deadline-nonce-1');
    const realSetTimeout = globalThis.setTimeout;
    vi.useFakeTimers();
    let activeRequests = 0;
    let abortedRequests = 0;
    let responseSettled = false;
    services.fetcher = vi.fn((_input, init) => new Promise<Response>((_resolve, reject) => {
      const signal = init?.signal;
      if (!signal) {
        reject(new Error('missing_abort_signal'));
        return;
      }
      activeRequests += 1;
      const settleAbort = () => {
        abortedRequests += 1;
        setTimeout(() => {
          activeRequests -= 1;
          reject(signal.reason instanceof Error ? signal.reason : new Error(String(signal.reason || 'aborted')));
        }, 25);
      };
      if (signal.aborted) settleAbort();
      else signal.addEventListener('abort', settleAbort, { once: true });
    }));

    const responsePromise = handleFetch(request, services);
    void responsePromise.then(() => { responseSettled = true; });
    for (let attempt = 0; attempt < 10 && activeRequests === 0; attempt += 1) {
      await new Promise<void>((resolve) => realSetTimeout(resolve, 0));
    }
    expect(activeRequests).toBeGreaterThan(0);

    await vi.advanceTimersByTimeAsync(17_999);
    expect(responseSettled).toBe(false);
    expect(activeRequests).toBeGreaterThan(0);

    await vi.advanceTimersByTimeAsync(1);
    expect(responseSettled).toBe(false);
    expect(activeRequests).toBeGreaterThan(0);

    await vi.advanceTimersByTimeAsync(24);
    expect(responseSettled).toBe(false);
    expect(activeRequests).toBeGreaterThan(0);

    await vi.advanceTimersByTimeAsync(1);
    const response = await responsePromise;
    expect(response.status).toBe(504);
    await expect(response.json()).resolves.toEqual({ success: false, error: 'probe_timeout' });
    expect(abortedRequests).toBeGreaterThan(0);
    expect(activeRequests).toBe(0);
    expect(responseSettled).toBe(true);
    expect(services.store.runtime.probe).toMatchObject({
      status: 'error',
      detail: { result_state: 'error', error: 'probe_timeout' }
    });
  });

  it('exposes only a safe code when a synchronous probe fails unexpectedly', async () => {
    services.fetcher = vi.fn(async (input) => {
      if (String(input) === 'https://sample-blog.dev/') {
        return new Response('<html><head><title>Sample Friend</title></head><body></body></html>');
      }
      return new Response('not found', { status: 404 });
    });
    let nowCalls = 0;
    services.now = () => {
      nowCalls += 1;
      if (nowCalls === 2) throw new Error('database password must never escape');
      return NOW;
    };

    const response = await handleFetch(
      await signedProbe(undefined, 'probe-internal-error-1'),
      services
    );
    const rawBody = await response.text();

    expect(response.status).toBe(500);
    expect(JSON.parse(rawBody)).toEqual({ success: false, error: 'internal_error' });
    expect(rawBody).not.toContain('database password');

    const statusResponse = await handleFetch(
      await signedStatus(undefined, 'status-after-probe-error-1'),
      services
    );
    const statusBody = await statusResponse.text();
    expect(JSON.parse(statusBody).runtime.probe).toMatchObject({
      status: 'error',
      detail: { result_state: 'error', error: 'internal_error' }
    });
    expect(statusBody).not.toContain('database password');
  });

  it('marks the status degraded when Queue metrics are unavailable', async () => {
    services.queue.metrics = vi.fn(async () => {
      throw new Error('metrics_unavailable');
    });

    const response = await handleFetch(await signedStatus(), services);
    const payload = await response.json() as {
      service: { status: string };
      queue: Record<string, unknown>;
      database: { available: boolean };
    };

    expect(response.status).toBe(200);
    expect(payload.service.status).toBe('degraded');
    expect(payload.queue).toEqual({ available: false, error: 'queue_metrics_unavailable' });
    expect(payload.database.available).toBe(true);
  });
});
