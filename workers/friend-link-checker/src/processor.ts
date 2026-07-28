import type { RuntimeServices } from './app';
import { assertSafePublicUrl } from './domain';
import { checkBacklink, detectSite } from './detector';
import { signHmac } from './security';
import { toQueueMessages } from './app';
import { isTerminalJob, jobResultPayload } from './result';
import type { FriendLinkJob, QueueMessage } from './types';

const SUCCESS_RECHECK_SECONDS = 24 * 60 * 60;
const INITIAL_FAILURE_RECHECK_SECONDS = 6 * 60 * 60;
const MAX_FAILURE_RECHECK_SECONDS = 7 * 24 * 60 * 60;
const CALLBACK_TIMEOUT_MS = 8_000;
const CALLBACK_RETRY_BASE_SECONDS = 5 * 60;
const CALLBACK_RETRY_MAX_SECONDS = 6 * 60 * 60;
const CRON_BATCH_SIZE = 25;

export type { RuntimeServices } from './app';

export type QueueProcessingResult =
  | { disposition: 'ack' }
  | { disposition: 'retry'; delaySeconds: number; error: string };

const ACK_RESULT: QueueProcessingResult = { disposition: 'ack' };

function nextCheckAt(job: FriendLinkJob, succeeded: boolean, now: number): number {
  if (succeeded) return now + SUCCESS_RECHECK_SECONDS;
  const multiplier = 2 ** Math.min(job.failureCount, 5);
  return now + Math.min(INITIAL_FAILURE_RECHECK_SECONDS * multiplier, MAX_FAILURE_RECHECK_SECONDS);
}

async function deliverCallback(job: FriendLinkJob, services: RuntimeServices): Promise<QueueProcessingResult> {
  const timestamp = services.now();
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort('callback_timeout'), CALLBACK_TIMEOUT_MS);

  try {
    const callbackUrl = assertSafePublicUrl(services.config.callbackUrl).toString();
    if (!services.config.callbackSecret) throw new Error('callback_secret_missing');
    const nonce = crypto.randomUUID();
    const rawBody = JSON.stringify(jobResultPayload(job));
    const signature = await signHmac(
      services.config.callbackSecret,
      `${timestamp}.${nonce}.${rawBody}`
    );
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
    return ACK_RESULT;
  } catch (error) {
    const callbackError = String(controller.signal.aborted
      ? 'callback_timeout'
      : (error instanceof Error && error.message ? error.message : 'callback_failed')).slice(0, 300);
    const retryDelay = Math.min(
      CALLBACK_RETRY_BASE_SECONDS * (2 ** Math.min(job.callbackAttempts, 6)),
      CALLBACK_RETRY_MAX_SECONDS
    );
    await services.store.deferCallback(
      job.id,
      job.generation,
      timestamp + retryDelay,
      timestamp,
      callbackError
    );
    return { disposition: 'retry', delaySeconds: retryDelay, error: callbackError };
  } finally {
    clearTimeout(timer);
  }
}

export async function processQueueMessage(
  message: QueueMessage,
  services: RuntimeServices
): Promise<QueueProcessingResult> {
  const existing = await services.store.getJob(message.jobId);
  if (!existing) return ACK_RESULT;
  if (existing.generation !== message.generation) return ACK_RESULT;

  if (
    isTerminalJob(existing) &&
    existing.callbackStatus === 'pending'
  ) {
    const now = services.now();
    if (existing.callbackNextAttemptAt > now) {
      return {
        disposition: 'retry',
        delaySeconds: Math.max(1, existing.callbackNextAttemptAt - now),
        error: existing.callbackLastError || 'callback_retry_deferred'
      };
    }
    return deliverCallback(existing, services);
  }
  if (existing.status !== 'queued') return ACK_RESULT;

  const claimed = await services.store.claimQueuedJob(existing.id, existing.generation, services.now());
  if (!claimed) return ACK_RESULT;
  const checkedAt = services.now();
  const site = await detectSite(claimed.url, { fetcher: services.fetcher, resolver: services.resolver });
  const linksPage = claimed.linksPage || site.linksPage;
  const backlink = linksPage
    ? await checkBacklink(linksPage, site.detectedUrl || claimed.url, services.config.siteUrl, {
        fetcher: services.fetcher,
        resolver: services.resolver
      })
    : { state: 'unknown' as const, checkedUrl: '' };
  const detectionError = site.state !== 'ok'
    ? 'target_unreadable'
    : !linksPage
      ? 'links_page_not_found'
      : backlink.state === 'unknown'
        ? 'links_page_unreadable'
        : '';

  await services.store.saveDetection(claimed.id, claimed.generation, {
    site,
    backlinkState: backlink.state,
    checkedAt,
    nextCheckAt: nextCheckAt(claimed, site.state === 'ok', checkedAt),
    error: detectionError
  });

  const saved = await services.store.getJob(claimed.id);
  if (!saved) throw new Error('job_disappeared_after_detection');
  if (saved.generation !== claimed.generation) return ACK_RESULT;
  return deliverCallback(saved, services);
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
