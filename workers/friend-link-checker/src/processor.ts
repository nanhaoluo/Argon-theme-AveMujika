import type { RuntimeServices } from './app';
import { assertSafePublicUrl } from './domain';
import { checkBacklink, detectSite, discoverAndCheckBacklink } from './detector';
import { signHmac } from './security';
import { toQueueMessages } from './app';
import { isTerminalJob, jobResultPayload } from './result';
import { SCHEDULE_POLICY } from './schedule';
import { recordRuntimeState, safeRuntimeError } from './runtime';
import type { FriendLinkJob, QueueMessage } from './types';

const CALLBACK_TIMEOUT_MS = 8_000;
const CALLBACK_RETRY_BASE_SECONDS = 5 * 60;
const CALLBACK_RETRY_MAX_SECONDS = 6 * 60 * 60;
const CRON_BATCH_SIZE = 25;

export type { RuntimeServices } from './app';

export type QueueProcessingResult =
  | { disposition: 'ack' }
  | { disposition: 'retry'; delaySeconds: number; error: string };

const ACK_RESULT: QueueProcessingResult = { disposition: 'ack' };

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

async function processQueueMessageCore(
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
  let linksPage = claimed.linksPage || site.linksPage;
  const detectionOptions = { fetcher: services.fetcher, resolver: services.resolver };
  const backlink = linksPage
    ? await checkBacklink(
        linksPage,
        site.detectedUrl || claimed.url,
        services.config.siteUrl,
        detectionOptions
      )
    : await discoverAndCheckBacklink(
        site.detectedUrl || claimed.url,
        services.config.siteUrl,
        { ...detectionOptions, wordpressApiUrl: site.wordpressApiUrl }
      );
  if (!linksPage && 'linksPage' in backlink) {
    linksPage = typeof backlink.linksPage === 'string' ? backlink.linksPage : '';
    if (linksPage) site.linksPage = linksPage;
  }
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
    checkedUrl: backlink.checkedUrl,
    diagnostics: [
      ...site.attempts.flatMap((attempt) => attempt.diagnostics),
      ...backlink.diagnostics
    ],
    checkedAt,
    nextCheckAt: checkedAt + services.recheckDelaySeconds(claimed, detectionError === ''),
    error: detectionError
  });

  const saved = await services.store.getJob(claimed.id);
  if (!saved) throw new Error('job_disappeared_after_detection');
  if (saved.generation !== claimed.generation) return ACK_RESULT;
  return deliverCallback(saved, services);
}

export async function processQueueMessage(
  message: QueueMessage,
  services: RuntimeServices
): Promise<QueueProcessingResult> {
  const startedAt = services.now();
  await recordRuntimeState(services.store, {
    kind: 'queue',
    status: 'running',
    startedAt,
    finishedAt: 0,
    jobId: message.jobId,
    generation: message.generation,
    detail: { disposition: 'processing' },
    updatedAt: startedAt
  });
  try {
    const result = await processQueueMessageCore(message, services);
    const finishedAt = services.now();
    await recordRuntimeState(services.store, {
      kind: 'queue',
      status: result.disposition === 'retry' ? 'deferred' : 'ok',
      startedAt,
      finishedAt,
      jobId: message.jobId,
      generation: message.generation,
      detail: result.disposition === 'retry'
        ? { disposition: 'retry', delay_seconds: result.delaySeconds, error: safeRuntimeError(result.error) }
        : { disposition: 'ack' },
      updatedAt: finishedAt
    });
    return result;
  } catch (error) {
    const finishedAt = services.now();
    await recordRuntimeState(services.store, {
      kind: 'queue',
      status: 'error',
      startedAt,
      finishedAt,
      jobId: message.jobId,
      generation: message.generation,
      detail: { disposition: 'error', error: safeRuntimeError(error) },
      updatedAt: finishedAt
    });
    throw error;
  }
}

export async function handleScheduled(
  services: RuntimeServices,
  trigger: { cron: string; scheduledTimeMs: number } = {
    cron: SCHEDULE_POLICY.cron,
    scheduledTimeMs: services.now() * 1000
  }
): Promise<void> {
  const startedAt = services.now();
  await recordRuntimeState(services.store, {
    kind: 'cron',
    status: 'running',
    startedAt,
    finishedAt: 0,
    jobId: '',
    generation: '',
    detail: {
      cron: trigger.cron,
      scheduled_time_ms: trigger.scheduledTimeMs,
      due_count: 0,
      enqueued_count: 0
    },
    updatedAt: startedAt
  });
  try {
    await services.store.deleteExpiredNonces(startedAt);
    const dueJobs = await services.store.listDueJobs(startedAt, CRON_BATCH_SIZE);
    const messages = toQueueMessages(dueJobs);
    if (messages.length) {
      await services.store.markJobsQueued(messages, startedAt);
      await services.queue.sendBatch(messages.map((body) => ({
        body,
        delaySeconds: services.queueDelaySeconds()
      })));
    }
    const finishedAt = services.now();
    await recordRuntimeState(services.store, {
      kind: 'cron',
      status: 'ok',
      startedAt,
      finishedAt,
      jobId: '',
      generation: '',
      detail: {
        cron: trigger.cron,
        scheduled_time_ms: trigger.scheduledTimeMs,
        due_count: dueJobs.length,
        enqueued_count: messages.length
      },
      updatedAt: finishedAt
    });
  } catch (error) {
    const finishedAt = services.now();
    await recordRuntimeState(services.store, {
      kind: 'cron',
      status: 'error',
      startedAt,
      finishedAt,
      jobId: '',
      generation: '',
      detail: {
        cron: trigger.cron,
        scheduled_time_ms: trigger.scheduledTimeMs,
        error: safeRuntimeError(error)
      },
      updatedAt: finishedAt
    });
    throw error;
  }
}
