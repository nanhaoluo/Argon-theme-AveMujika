import { beforeEach, describe, expect, it, vi } from 'vitest';
import { handleScheduled, processQueueMessage, type RuntimeServices } from '../src/processor';
import { verifySignedPayload } from '../src/security';
import type { DetectionSave, FriendLinkJob, JobStore, QueueMessage } from '../src/types';

const NOW = 1_800_000_000;
const CALLBACK_SECRET = 'test-callback-secret-that-is-long-enough';

function queuedJob(overrides: Partial<FriendLinkJob> = {}): FriendLinkJob {
  return {
    id: 'link_123',
    generation: 'generation-123456',
    url: 'https://friend-site.top/',
    hostname: 'friend-site.top',
    linksPage: 'https://friend-site.top/friends',
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
    ...overrides
  };
}

class ProcessingStore implements JobStore {
  jobs = new Map<string, FriendLinkJob>();
  expiredNoncesDeletedAt = 0;

  constructor(...jobs: FriendLinkJob[]) {
    for (const job of jobs) this.jobs.set(job.id, job);
  }

  async acceptSubmission(): Promise<void> {
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
      backlinkState: detection.backlinkState,
      checkedAt: detection.checkedAt,
      nextCheckAt: detection.nextCheckAt,
      attempts: job.attempts + 1,
      failureCount: detection.site.state === 'ok' ? 0 : job.failureCount + 1,
      lastError: detection.error,
      callbackStatus: 'pending',
      callbackAttempts: 0,
      callbackNextAttemptAt: detection.checkedAt,
      callbackLastError: '',
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
}

function makeServices(store: ProcessingStore): RuntimeServices & { sent: QueueMessage[]; callbacks: Request[] } {
  const sent: QueueMessage[] = [];
  const callbacks: Request[] = [];
  const fetcher = vi.fn(async (input: string, init?: RequestInit) => {
    if (input === 'https://blog.sample-blog.dev/wp-json/argon/v1/friend-link-result') {
      callbacks.push(new Request(input, init));
      return new Response(null, { status: 204 });
    }
    if (input.endsWith('/wp-json/')) {
      if (input.startsWith('https://blog.friend-site.top/')) {
        return new Response(JSON.stringify({
          name: '编程小记',
          description: '其实我也不知道建这个站拿来干嘛=-=',
          home: 'https://blog.friend-site.top/'
        }), { status: 200 });
      }
      return new Response('missing', { status: 404 });
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
    callbacks,
    queue: {
      async send(message) {
        sent.push(message);
      },
      async sendBatch(messages) {
        sent.push(...messages);
      }
    },
    fetcher,
    resolver: async () => ['93.184.216.34'],
    now: () => NOW,
    queueDelaySeconds: () => 45
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
      callbackStatus: 'delivered'
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
      result: { backlink_state: 'found', source: 'cloudflare_worker' }
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
      callbackStatus: 'delivered'
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
      callbackStatus: 'delivered'
    });
  });

  it('keeps blocked targets unknown and never reports a missing backlink from failure', async () => {
    services.fetcher = vi.fn(async (input: string, init?: RequestInit) => {
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
  });
});
