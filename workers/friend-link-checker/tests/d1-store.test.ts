import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { readdirSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { Miniflare } from 'miniflare';
import { D1JobStore } from '../src/d1-store';
import type { FriendLinkJob } from '../src/types';

const NOW = 1_800_000_000;

function queuedJob(overrides: Partial<FriendLinkJob> = {}): FriendLinkJob {
  return {
    id: 'link_123',
    generation: 'generation-123456',
    url: 'https://sample-blog.dev/',
    hostname: 'sample-blog.dev',
    linksPage: 'https://sample-blog.dev/friends',
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

describe('D1 job store', () => {
  let miniflare: Miniflare;
  let store: D1JobStore;

  beforeEach(async () => {
    miniflare = new Miniflare({
      modules: true,
      script: 'export default { fetch() { return new Response("ok") } }',
      d1Databases: ['DB']
    });
    const database = await miniflare.getD1Database('DB') as unknown as D1Database;
    const migrationsDirectory = fileURLToPath(new URL('../migrations/', import.meta.url));
    for (const filename of readdirSync(migrationsDirectory).filter((value) => value.endsWith('.sql')).sort()) {
      const migration = readFileSync(`${migrationsDirectory}/${filename}`, 'utf8');
      for (const statement of migration.split(';').map((value) => value.trim()).filter(Boolean)) {
        await database.prepare(statement).run();
      }
    }
    store = new D1JobStore(database);
  });

  afterEach(async () => {
    await miniflare.dispose();
  });

  it('stores, claims and completes a queued detection job', async () => {
    const job = queuedJob({ linksPage: '' });
    await store.acceptSubmission(job, 'nonce-1234567890', NOW + 300);

    await expect(store.getJob(job.id)).resolves.toMatchObject({
      id: job.id,
      status: 'queued',
      callbackLastError: ''
    });
    await expect(store.claimQueuedJob(job.id, job.generation, NOW + 1)).resolves.toMatchObject({ status: 'running' });

    await store.saveDetection(job.id, job.generation, {
      site: {
        state: 'ok',
        accessible: true,
        readable: true,
        detectedUrl: 'https://blog.sample-blog.dev/',
        primary: '编程小记',
        subtitle: '小记副标题',
        description: '站点介绍',
        avatar: 'https://blog.sample-blog.dev/favicon.ico',
        linksPage: 'https://blog.sample-blog.dev/friends/',
        source: 'wordpress',
        attempts: []
      },
      backlinkState: 'found',
      checkedAt: NOW + 2,
      nextCheckAt: NOW + 86_400,
      error: ''
    });

    await expect(store.getJob(job.id)).resolves.toMatchObject({
      status: 'complete',
      primary: '编程小记',
      linksPage: 'https://blog.sample-blog.dev/friends/',
      backlinkState: 'found',
      callbackStatus: 'pending',
      attempts: 1
    });
  });

  it('persists the latest callback delivery error separately from detection errors', async () => {
    const job = queuedJob();
    await store.acceptSubmission(job, 'nonce-callback-error', NOW + 300);
    await store.claimQueuedJob(job.id, job.generation, NOW);
    await store.saveDetection(job.id, job.generation, {
      site: {
        state: 'ok', accessible: true, readable: true, detectedUrl: job.url,
        primary: '示例博客', subtitle: '', description: '', avatar: '', linksPage: job.linksPage,
        source: 'html', attempts: []
      },
      backlinkState: 'unknown',
      checkedAt: NOW,
      nextCheckAt: NOW + 86_400,
      error: ''
    });
    await store.deferCallback(job.id, job.generation, NOW + 300, NOW, 'callback_http_503');

    await expect(store.getJob(job.id)).resolves.toMatchObject({
      lastError: '',
      callbackAttempts: 1,
      callbackLastError: 'callback_http_503'
    });

    await store.markCallbackDelivered(job.id, job.generation, NOW + 1);
    await expect(store.getJob(job.id)).resolves.toMatchObject({
      callbackStatus: 'delivered',
      callbackLastError: ''
    });
  });

  it('rejects a reused submission nonce transactionally', async () => {
    await store.acceptSubmission(queuedJob(), 'nonce-1234567890', NOW + 300);

    await expect(
      store.acceptSubmission(queuedJob({ id: 'link_456' }), 'nonce-1234567890', NOW + 300)
    ).rejects.toThrow('submission_replayed');
    await expect(store.getJob('link_456')).resolves.toBeNull();
  });

  it('returns due jobs and pending callbacks while leaving future completed jobs alone', async () => {
    await store.acceptSubmission(queuedJob({ id: 'due', nextCheckAt: NOW - 1 }), 'nonce-due-12345678', NOW + 300);
    await store.acceptSubmission(queuedJob({ id: 'future', nextCheckAt: NOW + 3_600 }), 'nonce-future-12345', NOW + 300);
    await store.claimQueuedJob('future', 'generation-123456', NOW);
    await store.saveDetection('future', 'generation-123456', {
      site: {
        state: 'ok', accessible: true, readable: true, detectedUrl: 'https://sample-blog.dev/',
        primary: '示例博客', subtitle: '', description: '', avatar: '', linksPage: '', source: 'html', attempts: []
      },
      backlinkState: 'unknown',
      checkedAt: NOW,
      nextCheckAt: NOW + 3_600,
      error: ''
    });

    await expect(store.listDueJobs(NOW, 25)).resolves.toEqual(expect.arrayContaining([
      expect.objectContaining({ id: 'due' }),
      expect.objectContaining({ id: 'future', callbackStatus: 'pending' })
    ]));

    await store.markJobsQueued([
      { jobId: 'due', generation: 'generation-123456' },
      { jobId: 'future', generation: 'generation-123456' }
    ], NOW);
    await expect(store.getJob('due')).resolves.toMatchObject({ status: 'queued', callbackStatus: 'not_ready' });
    await expect(store.getJob('future')).resolves.toMatchObject({ status: 'complete', callbackStatus: 'pending' });
  });

  it('uses generation compare-and-set so an old consumer cannot claim an overwritten task', async () => {
    await store.acceptSubmission(queuedJob({ generation: 'generation-old-123' }), 'nonce-old-12345678', NOW + 300);
    await store.acceptSubmission(queuedJob({ generation: 'generation-new-456' }), 'nonce-new-12345678', NOW + 300);

    await expect(store.claimQueuedJob('link_123', 'generation-old-123', NOW)).resolves.toBeNull();
    await expect(store.claimQueuedJob('link_123', 'generation-new-456', NOW)).resolves.toMatchObject({
      generation: 'generation-new-456',
      status: 'running'
    });
  });

  it('reserves scheduler capacity for due checks when callbacks are backed up', async () => {
    for (let index = 0; index < 11; index += 1) {
      const id = `pending_${index}`;
      const generation = `generation-pending-${index}`;
      await store.acceptSubmission(
        queuedJob({ id, generation, nextCheckAt: NOW + 86_400 }),
        `nonce-pending-${String(index).padStart(4, '0')}`,
        NOW + 300
      );
      await store.claimQueuedJob(id, generation, NOW);
      await store.saveDetection(id, generation, {
        site: {
          state: 'ok', accessible: true, readable: true, detectedUrl: 'https://sample-blog.dev/',
          primary: '示例博客', subtitle: '', description: '', avatar: '', linksPage: '', source: 'html', attempts: []
        },
        backlinkState: 'unknown',
        checkedAt: NOW,
        nextCheckAt: NOW + 86_400,
        error: ''
      });
    }
    await store.acceptSubmission(
      queuedJob({ id: 'due_check', generation: 'generation-due-check', nextCheckAt: NOW - 1 }),
      'nonce-due-check-1234',
      NOW + 300
    );

    const scheduled = await store.listDueJobs(NOW, 25);

    expect(scheduled.filter((job) => job.callbackStatus === 'pending')).toHaveLength(10);
    expect(scheduled).toEqual(expect.arrayContaining([expect.objectContaining({ id: 'due_check' })]));
  });
});
