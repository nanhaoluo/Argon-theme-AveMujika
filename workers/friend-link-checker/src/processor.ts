import type { RuntimeServices } from './app';
import { assertSafePublicUrl } from './domain';
import { checkBacklink, detectSite } from './detector';
import { signHmac } from './security';
import { toQueueMessages } from './app';
import type { FriendLinkJob, QueueMessage } from './types';

const SUCCESS_RECHECK_SECONDS = 24 * 60 * 60;
const INITIAL_FAILURE_RECHECK_SECONDS = 6 * 60 * 60;
const MAX_FAILURE_RECHECK_SECONDS = 7 * 24 * 60 * 60;
const CALLBACK_TIMEOUT_MS = 8_000;
const CALLBACK_RETRY_BASE_SECONDS = 5 * 60;
const CALLBACK_RETRY_MAX_SECONDS = 6 * 60 * 60;
const CRON_BATCH_SIZE = 25;
const CALLBACK_FIELD_LIMITS = {
  url: 2_048,
  primary: 200,
  subtitle: 300,
  description: 1_000,
  error: 300
} as const;

export type { RuntimeServices } from './app';

function nextCheckAt(job: FriendLinkJob, succeeded: boolean, now: number): number {
  if (succeeded) return now + SUCCESS_RECHECK_SECONDS;
  const multiplier = 2 ** Math.min(job.failureCount, 5);
  return now + Math.min(INITIAL_FAILURE_RECHECK_SECONDS * multiplier, MAX_FAILURE_RECHECK_SECONDS);
}

function truncateCallbackText(value: string, maxCodeUnits: number): string {
  if (value.length <= maxCodeUnits) return value;
  let truncated = value.slice(0, maxCodeUnits);
  const lastCodeUnit = truncated.charCodeAt(truncated.length - 1);
  if (lastCodeUnit >= 0xd800 && lastCodeUnit <= 0xdbff) truncated = truncated.slice(0, -1);
  return truncated;
}

function callbackPayload(job: FriendLinkJob): Record<string, unknown> {
  return {
    version: 1,
    job_id: job.id,
    generation: job.generation,
    checked_at: job.checkedAt,
    result: {
      state: job.status,
      accessible: job.accessible,
      readable: job.readable,
      detected_url: truncateCallbackText(job.detectedUrl, CALLBACK_FIELD_LIMITS.url),
      primary: truncateCallbackText(job.primary, CALLBACK_FIELD_LIMITS.primary),
      subtitle: truncateCallbackText(job.subtitle, CALLBACK_FIELD_LIMITS.subtitle),
      description: truncateCallbackText(job.description, CALLBACK_FIELD_LIMITS.description),
      avatar: truncateCallbackText(job.avatar, CALLBACK_FIELD_LIMITS.url),
      links_page: truncateCallbackText(job.linksPage, CALLBACK_FIELD_LIMITS.url),
      backlink_state: job.backlinkState,
      source: 'cloudflare_worker',
      attempts: job.attempts,
      error: truncateCallbackText(job.lastError, CALLBACK_FIELD_LIMITS.error)
    }
  };
}

async function deliverCallback(job: FriendLinkJob, services: RuntimeServices): Promise<void> {
  const callbackUrl = assertSafePublicUrl(services.config.callbackUrl).toString();
  if (!services.config.callbackSecret) throw new Error('callback_secret_missing');
  const timestamp = services.now();
  const nonce = crypto.randomUUID();
  const rawBody = JSON.stringify(callbackPayload(job));
  const signature = await signHmac(
    services.config.callbackSecret,
    `${timestamp}.${nonce}.${rawBody}`
  );
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort('callback_timeout'), CALLBACK_TIMEOUT_MS);

  try {
    const response = await services.fetcher(callbackUrl, {
      method: 'POST',
      redirect: 'manual',
      signal: controller.signal,
      headers: {
        'content-type': 'application/json',
        'x-argon-timestamp': String(timestamp),
        'x-argon-nonce': nonce,
        'x-argon-signature': `sha256=${signature}`
      },
      body: rawBody
    });
    await response.body?.cancel();
    if (!response.ok) throw new Error(`callback_http_${response.status}`);
    await services.store.markCallbackDelivered(job.id, job.generation, timestamp);
  } catch (error) {
    const retryDelay = Math.min(
      CALLBACK_RETRY_BASE_SECONDS * (2 ** Math.min(job.callbackAttempts, 6)),
      CALLBACK_RETRY_MAX_SECONDS
    );
    await services.store.deferCallback(job.id, job.generation, timestamp + retryDelay, timestamp);
    throw error;
  } finally {
    clearTimeout(timer);
  }
}

export async function processQueueMessage(message: QueueMessage, services: RuntimeServices): Promise<void> {
  const existing = await services.store.getJob(message.jobId);
  if (!existing) return;
  if (existing.generation !== message.generation) return;

  if (
    (existing.status === 'complete' || existing.status === 'unknown' || existing.status === 'error') &&
    existing.callbackStatus === 'pending'
  ) {
    if (existing.callbackNextAttemptAt > services.now()) return;
    await deliverCallback(existing, services);
    return;
  }
  if (existing.status !== 'queued') return;

  const claimed = await services.store.claimQueuedJob(existing.id, existing.generation, services.now());
  if (!claimed) return;
  const checkedAt = services.now();
  const site = await detectSite(claimed.url, { fetcher: services.fetcher, resolver: services.resolver });
  const linksPage = claimed.linksPage || site.linksPage;
  const backlink = linksPage
    ? await checkBacklink(linksPage, claimed.url, services.config.siteUrl, {
        fetcher: services.fetcher,
        resolver: services.resolver
      })
    : { state: 'unknown' as const, checkedUrl: '' };

  await services.store.saveDetection(claimed.id, claimed.generation, {
    site,
    backlinkState: backlink.state,
    checkedAt,
    nextCheckAt: nextCheckAt(claimed, site.state === 'ok', checkedAt),
    error: site.state === 'ok' ? '' : 'target_unreadable'
  });

  const saved = await services.store.getJob(claimed.id);
  if (!saved) throw new Error('job_disappeared_after_detection');
  if (saved.generation !== claimed.generation) return;
  await deliverCallback(saved, services);
}

export async function handleScheduled(services: RuntimeServices): Promise<void> {
  const now = services.now();
  await services.store.deleteExpiredNonces(now);
  const dueJobs = await services.store.listDueJobs(now, CRON_BATCH_SIZE);
  if (!dueJobs.length) return;

  const messages = toQueueMessages(dueJobs);
  await services.store.markJobsQueued(messages, now);
  await services.queue.sendBatch(messages);
}
