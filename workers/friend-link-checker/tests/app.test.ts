import { beforeEach, describe, expect, it, vi } from 'vitest';
import { handleFetch, type RuntimeServices } from '../src/app';
import { signHmac } from '../src/security';
import type { FriendLinkJob, JobStore, QueueMessage } from '../src/types';

class MemoryStore implements JobStore {
  jobs = new Map<string, FriendLinkJob>();
  nonces = new Set<string>();

  async acceptSubmission(job: FriendLinkJob, nonce: string): Promise<void> {
    if (this.nonces.has(nonce)) throw new Error('submission_replayed');
    this.nonces.add(nonce);
    this.jobs.set(job.id, job);
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
        sent.push(...messages);
      }
    },
    fetcher: vi.fn(),
    resolver: async () => ['93.184.216.34'],
    now: () => NOW,
    queueDelaySeconds: () => 45
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

describe('Worker HTTP API', () => {
  let services: ReturnType<typeof makeServices>;

  beforeEach(() => {
    services = makeServices();
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
});
