import { assertSafePublicUrl, isSameCandidateSite } from './domain';
import { constantTimeEqual, verifySignedPayload } from './security';
import { friendLinkResultPayload, isTerminalJob, jobResultPayload } from './result';
import { schedulePolicyPayload } from './schedule';
import { recordRuntimeState, safeRuntimeError } from './runtime';
import type {
  FriendLinkJob,
  JobStore,
  QueueMessage,
  QueueProducer,
  WorkerConfiguration
} from './types';
import {
  checkBacklink,
  detectSite,
  discoverAndCheckBacklink,
  type HostResolver,
  type WorkerFetcher
} from './detector';

const MAX_REQUEST_BODY_BYTES = 16 * 1024;
const JOB_ID_PATTERN = /^[A-Za-z0-9_-]{1,96}$/;
const PROBE_DEADLINE_MS = 18_000;

export interface RuntimeServices {
  config: WorkerConfiguration;
  store: JobStore;
  queue: QueueProducer;
  fetcher: WorkerFetcher;
  resolver: HostResolver;
  now: () => number;
  queueDelaySeconds: () => number;
  recheckDelaySeconds: (job: FriendLinkJob, succeeded: boolean) => number;
}

function jsonResponse(value: unknown, status: number, origin = ''): Response {
  const headers = new Headers({
    'content-type': 'application/json; charset=utf-8',
    'cache-control': 'no-store',
    'x-content-type-options': 'nosniff'
  });
  if (origin) {
    headers.set('access-control-allow-origin', origin);
    headers.set('vary', 'Origin');
  }
  return new Response(JSON.stringify(value), { status, headers });
}

function configuredOrigin(configured: string): string {
  try {
    return new URL(configured).origin;
  } catch {
    return '';
  }
}

function requestOriginAllowed(request: Request, configured: string): boolean {
  const origin = request.headers.get('origin');
  if (!origin) return true;
  return origin === configuredOrigin(configured);
}

function configurationReady(config: WorkerConfiguration): boolean {
  if (!config.submitSecret || !config.callbackSecret || !config.adminReadToken) return false;
  try {
    assertSafePublicUrl(config.allowedOrigin);
    assertSafePublicUrl(config.siteUrl);
    assertSafePublicUrl(config.callbackUrl);
    return configuredOrigin(config.allowedOrigin) === config.allowedOrigin.replace(/\/$/, '');
  } catch {
    return false;
  }
}

function safeStatusError(_error: unknown, fallback: string): string {
  return fallback.slice(0, 80);
}

function safeStatusUrl(value: string): string {
  try {
    const url = assertSafePublicUrl(value);
    url.username = '';
    url.password = '';
    url.search = '';
    url.hash = '';
    return url.toString().slice(0, 2_048);
  } catch {
    return '';
  }
}

function safeStatusText(value: string, maxCodeUnits: number): string {
  return String(value || '').slice(0, maxCodeUnits);
}

function statusJobPayload(job: FriendLinkJob): Record<string, unknown> {
  return {
    id: safeStatusText(job.id, 96),
    generation: safeStatusText(job.generation, 128),
    url: safeStatusUrl(job.url),
    hostname: safeStatusText(job.hostname, 253),
    links_page: safeStatusUrl(job.linksPage),
    checked_url: safeStatusUrl(job.checkedUrl),
    status: job.status,
    accessible: job.accessible,
    readable: job.readable,
    detected_url: safeStatusUrl(job.detectedUrl),
    primary: safeStatusText(job.primary, 200),
    subtitle: safeStatusText(job.subtitle, 300),
    description: safeStatusText(job.description, 1_000),
    avatar: safeStatusUrl(job.avatar),
    backlink_state: job.backlinkState,
    checked_at: job.checkedAt,
    next_check_at: job.nextCheckAt,
    failure_count: job.failureCount,
    attempts: job.attempts,
    last_error: safeStatusText(job.lastError, 300),
    callback_status: job.callbackStatus,
    callback_attempts: job.callbackAttempts,
    callback_next_attempt_at: job.callbackNextAttemptAt,
    callback_last_error: safeStatusText(job.callbackLastError, 300),
    diagnostics: job.diagnostics.slice(0, 64).map((entry) => ({
      phase: entry.phase,
      url: safeStatusUrl(entry.url),
      code: safeStatusText(entry.code, 80),
      reachable: entry.reachable,
      http_status: entry.httpStatus,
      duration_ms: entry.durationMs
    })),
    created_at: job.createdAt,
    updated_at: job.updatedAt
  };
}

async function readRequestTextLimited(request: Request): Promise<string> {
  const declaredLength = Number(request.headers.get('content-length') || 0);
  if (declaredLength > MAX_REQUEST_BODY_BYTES) throw new Error('body_too_large');
  if (!request.body) return '';

  const reader = request.body.getReader();
  const decoder = new TextDecoder();
  let received = 0;
  let text = '';
  try {
    while (true) {
      const { value, done } = await reader.read();
      if (done) break;
      received += value.byteLength;
      if (received > MAX_REQUEST_BODY_BYTES) throw new Error('body_too_large');
      text += decoder.decode(value, { stream: true });
    }
    return text + decoder.decode();
  } finally {
    if (received > MAX_REQUEST_BODY_BYTES) await reader.cancel('body_too_large');
    reader.releaseLock();
  }
}

function createQueuedJob(id: string, generation: string, url: URL, linksPage: string, now: number): FriendLinkJob {
  return {
    id,
    generation,
    url: url.toString(),
    hostname: url.hostname.toLowerCase(),
    linksPage,
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
    nextCheckAt: now,
    failureCount: 0,
    attempts: 0,
    lastError: '',
    callbackStatus: 'not_ready',
    callbackAttempts: 0,
    callbackNextAttemptAt: 0,
    callbackLastError: '',
    diagnostics: [],
    createdAt: now,
    updatedAt: now
  };
}

async function handleSubmission(request: Request, services: RuntimeServices): Promise<Response> {
  const allowedOrigin = configuredOrigin(services.config.allowedOrigin);
  if (!configurationReady(services.config)) {
    return jsonResponse({ success: false, error: 'worker_not_configured' }, 503);
  }
  if (!allowedOrigin || !requestOriginAllowed(request, services.config.allowedOrigin)) {
    return jsonResponse({ success: false, error: 'origin_not_allowed' }, 403);
  }
  let rawBody: string;
  try {
    rawBody = await readRequestTextLimited(request);
  } catch {
    return jsonResponse({ success: false, error: 'body_too_large' }, 413, allowedOrigin);
  }
  const now = services.now();
  const verified = await verifySignedPayload(request.headers, rawBody, services.config.submitSecret, now);
  if (!verified) return jsonResponse({ success: false, error: 'invalid_signature' }, 401, allowedOrigin);

  let payload: Record<string, unknown>;
  try {
    payload = JSON.parse(rawBody) as Record<string, unknown>;
  } catch {
    return jsonResponse({ success: false, error: 'invalid_json' }, 400, allowedOrigin);
  }

  const jobId = String(payload.job_id || '');
  if (!JOB_ID_PATTERN.test(jobId)) {
    return jsonResponse({ success: false, error: 'invalid_job_id' }, 400, allowedOrigin);
  }

  let targetUrl: URL;
  let linksPage = '';
  try {
    targetUrl = assertSafePublicUrl(String(payload.url || ''));
    if (payload.links_page) {
      const parsedLinksPage = assertSafePublicUrl(String(payload.links_page));
      if (!isSameCandidateSite(targetUrl.toString(), parsedLinksPage.toString())) {
        throw new Error('links_page_site_mismatch');
      }
      linksPage = parsedLinksPage.toString();
    }
  } catch (error) {
    return jsonResponse(
      { success: false, error: error instanceof Error ? error.message : 'invalid_target' },
      400,
      allowedOrigin
    );
  }

  const job = createQueuedJob(jobId, verified.nonce, targetUrl, linksPage, now);
  try {
    await services.store.acceptSubmission(job, verified.nonce, verified.timestamp + 300);
  } catch (error) {
    if (error instanceof Error && error.message === 'submission_replayed') {
      return jsonResponse({ success: false, error: 'submission_replayed' }, 409, allowedOrigin);
    }
    throw error;
  }

  try {
    await services.queue.send({ jobId, generation: job.generation }, { delaySeconds: services.queueDelaySeconds() });
  } catch {
    // The due job remains in D1 and the next Cron run will enqueue it again.
  }
  return jsonResponse({ success: true, job_id: jobId, status: 'queued' }, 202, allowedOrigin);
}

async function handleJobRead(request: Request, jobId: string, services: RuntimeServices): Promise<Response> {
  const suppliedToken = (request.headers.get('authorization') || '').replace(/^Bearer\s+/i, '');
  if (!services.config.adminReadToken || !constantTimeEqual(suppliedToken, services.config.adminReadToken)) {
    return jsonResponse({ success: false, error: 'unauthorized' }, 401);
  }
  if (!JOB_ID_PATTERN.test(jobId)) return jsonResponse({ success: false, error: 'invalid_job_id' }, 400);
  const job = await services.store.getJob(jobId);
  return job ? jsonResponse(job, 200) : jsonResponse({ success: false, error: 'not_found' }, 404);
}

async function handleSignedResultRead(request: Request, jobId: string, services: RuntimeServices): Promise<Response> {
  if (!services.config.submitSecret) {
    return jsonResponse({ success: false, error: 'worker_not_configured' }, 503);
  }
  let rawBody: string;
  try {
    rawBody = await readRequestTextLimited(request);
  } catch {
    return jsonResponse({ success: false, error: 'body_too_large' }, 413);
  }
  const verified = await verifySignedPayload(request.headers, rawBody, services.config.submitSecret, services.now());
  if (!verified) return jsonResponse({ success: false, error: 'invalid_signature' }, 401);

  let payload: Record<string, unknown>;
  try {
    payload = JSON.parse(rawBody) as Record<string, unknown>;
  } catch {
    return jsonResponse({ success: false, error: 'invalid_json' }, 400);
  }
  const generation = String(payload.generation || '');
  if (!JOB_ID_PATTERN.test(jobId) || !/^[A-Za-z0-9_-]{16,128}$/.test(generation)) {
    return jsonResponse({ success: false, error: 'invalid_job_reference' }, 400);
  }

  const job = await services.store.getJob(jobId);
  if (!job) return jsonResponse({ success: false, error: 'not_found' }, 404);
  if (!constantTimeEqual(job.generation, generation)) {
    return jsonResponse({ success: false, error: 'stale_generation' }, 409);
  }
  if (!isTerminalJob(job)) {
    return jsonResponse({
      success: true,
      job_id: job.id,
      generation: job.generation,
      status: job.status
    }, 202);
  }

  await services.store.markCallbackDelivered(job.id, job.generation, services.now());
  return jsonResponse(jobResultPayload(job), 200);
}

async function handleStatusRead(request: Request, services: RuntimeServices): Promise<Response> {
  if (!services.config.submitSecret) {
    return jsonResponse({ success: false, error: 'worker_not_configured' }, 503);
  }
  let rawBody: string;
  try {
    rawBody = await readRequestTextLimited(request);
  } catch {
    return jsonResponse({ success: false, error: 'body_too_large' }, 413);
  }
  const now = services.now();
  const verified = await verifySignedPayload(request.headers, rawBody, services.config.submitSecret, now);
  if (!verified) return jsonResponse({ success: false, error: 'invalid_signature' }, 401);

  let payload: Record<string, unknown>;
  try {
    payload = JSON.parse(rawBody) as Record<string, unknown>;
  } catch {
    return jsonResponse({ success: false, error: 'invalid_json' }, 400);
  }
  if (payload.action !== 'status') {
    return jsonResponse({ success: false, error: 'invalid_status_action' }, 400);
  }
  let requestedOrigin = '';
  try {
    requestedOrigin = assertSafePublicUrl(String(payload.site_url || '')).origin;
  } catch {
    return jsonResponse({ success: false, error: 'invalid_site_url' }, 400);
  }
  if (!requestedOrigin || requestedOrigin !== configuredOrigin(services.config.siteUrl)) {
    return jsonResponse({ success: false, error: 'site_origin_mismatch' }, 403);
  }

  let queue: Record<string, unknown>;
  try {
    const metrics = await services.queue.metrics();
    queue = {
      available: true,
      backlog_count: metrics.backlogCount,
      backlog_bytes: metrics.backlogBytes,
      oldest_message_timestamp_ms: metrics.oldestMessageTimestamp
    };
  } catch (error) {
    queue = {
      available: false,
      error: safeStatusError(error, 'queue_metrics_unavailable')
    };
  }

  let database: Record<string, unknown>;
  let aggregate: Record<string, unknown> | null = null;
  let recentJobs: Array<Record<string, unknown>> = [];
  let runtime: Record<string, unknown> = {};
  try {
    const snapshot = await services.store.getStatusSnapshot(now, 50);
    database = { available: true };
    aggregate = {
      total: snapshot.aggregate.total,
      queued: snapshot.aggregate.queued,
      running: snapshot.aggregate.running,
      complete: snapshot.aggregate.complete,
      unknown: snapshot.aggregate.unknown,
      error: snapshot.aggregate.error,
      callback_pending: snapshot.aggregate.callbackPending,
      callback_delivered: snapshot.aggregate.callbackDelivered,
      due_checks: snapshot.aggregate.dueChecks,
      due_callbacks: snapshot.aggregate.dueCallbacks
    };
    recentJobs = snapshot.recentJobs.map(statusJobPayload);
    runtime = Object.fromEntries(Object.entries(snapshot.runtime).map(([kind, state]) => [kind, state ? {
      status: state.status,
      started_at: state.startedAt,
      finished_at: state.finishedAt,
      job_id: state.jobId,
      generation: state.generation,
      detail: state.detail,
      updated_at: state.updatedAt
    } : null]));
  } catch (error) {
    database = {
      available: false,
      error: safeStatusError(error, 'database_status_unavailable')
    };
  }

  const degraded = queue.available !== true || database.available !== true;
  return jsonResponse({
    success: true,
    server_time: now,
    service: {
      name: 'friend-link-checker',
      status: degraded ? 'degraded' : 'ok'
    },
    config: {
      ready: configurationReady(services.config),
      allowed_origin: configuredOrigin(services.config.allowedOrigin),
      site_url: safeStatusUrl(services.config.siteUrl),
      callback_url: safeStatusUrl(services.config.callbackUrl),
      secrets: {
        submit_hmac: Boolean(services.config.submitSecret),
        callback_hmac: Boolean(services.config.callbackSecret),
        admin_read_token: Boolean(services.config.adminReadToken)
      }
    },
    queue,
    database,
    aggregate,
    recent_jobs: recentJobs,
    runtime,
    random_schedule: schedulePolicyPayload()
  }, 200);
}

async function handleProbe(request: Request, services: RuntimeServices): Promise<Response> {
  if (!services.config.submitSecret) {
    return jsonResponse({ success: false, error: 'worker_not_configured' }, 503);
  }
  let rawBody: string;
  try {
    rawBody = await readRequestTextLimited(request);
  } catch {
    return jsonResponse({ success: false, error: 'body_too_large' }, 413);
  }
  const checkedAt = services.now();
  const verified = await verifySignedPayload(request.headers, rawBody, services.config.submitSecret, checkedAt);
  if (!verified) return jsonResponse({ success: false, error: 'invalid_signature' }, 401);

  let payload: Record<string, unknown>;
  try {
    payload = JSON.parse(rawBody) as Record<string, unknown>;
  } catch {
    return jsonResponse({ success: false, error: 'invalid_json' }, 400);
  }
  if (payload.action !== 'probe') {
    return jsonResponse({ success: false, error: 'invalid_probe_action' }, 400);
  }
  const mode = String(payload.mode || '');
  if (mode !== 'info' && mode !== 'backlink') {
    return jsonResponse({ success: false, error: 'invalid_probe_mode' }, 400);
  }
  let requestedOrigin = '';
  try {
    requestedOrigin = assertSafePublicUrl(String(payload.site_url || '')).origin;
  } catch {
    return jsonResponse({ success: false, error: 'invalid_site_url' }, 400);
  }
  if (!requestedOrigin || requestedOrigin !== configuredOrigin(services.config.siteUrl)) {
    return jsonResponse({ success: false, error: 'site_origin_mismatch' }, 403);
  }

  let targetUrl: URL;
  let linksPage = '';
  try {
    targetUrl = assertSafePublicUrl(String(payload.url || ''));
    if (payload.links_page) {
      const parsedLinksPage = assertSafePublicUrl(String(payload.links_page));
      if (!isSameCandidateSite(targetUrl.toString(), parsedLinksPage.toString())) {
        return jsonResponse({ success: false, error: 'links_page_site_mismatch' }, 400);
      }
      linksPage = parsedLinksPage.toString();
    }
  } catch {
    return jsonResponse({ success: false, error: 'invalid_probe_target' }, 400);
  }

  try {
    await services.store.consumeNonce(verified.nonce, checkedAt + 300);
  } catch (error) {
    if (error instanceof Error && error.message === 'submission_replayed') {
      return jsonResponse({ success: false, error: 'probe_replayed' }, 409);
    }
    return jsonResponse({ success: false, error: 'internal_error' }, 500);
  }

  await recordRuntimeState(services.store, {
    kind: 'probe',
    status: 'running',
    startedAt: checkedAt,
    finishedAt: 0,
    jobId: '',
    generation: '',
    detail: { mode, result_state: 'running' },
    updatedAt: checkedAt
  });
  const controller = new AbortController();
  const deadlineTimer = setTimeout(() => controller.abort(new Error('probe_timeout')), PROBE_DEADLINE_MS);
  try {
    const detectionOptions = {
      fetcher: services.fetcher,
      resolver: services.resolver,
      signal: controller.signal
    };
    const site = await detectSite(targetUrl.toString(), detectionOptions);
    linksPage ||= site.linksPage;
    const backlink = mode === 'backlink'
      ? (linksPage
          ? await checkBacklink(
              linksPage,
              site.detectedUrl || targetUrl.toString(),
              services.config.siteUrl,
              detectionOptions
            )
          : await discoverAndCheckBacklink(
              site.detectedUrl || targetUrl.toString(),
              services.config.siteUrl,
              { ...detectionOptions, wordpressApiUrl: site.wordpressApiUrl }
            ))
      : { state: 'unknown' as const, checkedUrl: '', diagnostics: [] };
    if (controller.signal.aborted) {
      throw controller.signal.reason instanceof Error
        ? controller.signal.reason
        : new Error(String(controller.signal.reason || 'probe_timeout'));
    }
    if (!linksPage && 'linksPage' in backlink) {
      linksPage = typeof backlink.linksPage === 'string' ? backlink.linksPage : '';
    }
    const error = site.state !== 'ok'
      ? 'target_unreadable'
      : mode === 'info'
        ? ''
        : !linksPage
          ? 'links_page_not_found'
          : backlink.state === 'unknown'
            ? 'links_page_unreadable'
            : '';
    const resultState = site.state === 'ok' ? 'complete' : 'unknown';
    const diagnostics = [
      ...site.attempts.flatMap((attempt) => attempt.diagnostics),
      ...backlink.diagnostics
    ].slice(0, 64);
    const finishedAt = services.now();
    await recordRuntimeState(services.store, {
      kind: 'probe',
      status: 'ok',
      startedAt: checkedAt,
      finishedAt,
      jobId: '',
      generation: '',
      detail: {
        mode,
        result_state: resultState,
        backlink_state: backlink.state,
        error
      },
      updatedAt: finishedAt
    });

    return jsonResponse({
      success: true,
      version: 1,
      checked_at: checkedAt,
      result: {
        ...friendLinkResultPayload({
          state: resultState,
          accessible: site.accessible,
          readable: site.readable,
          detectedUrl: site.detectedUrl,
          primary: site.primary,
          subtitle: site.subtitle,
          description: site.description,
          avatar: site.avatar,
          linksPage,
          checkedUrl: backlink.checkedUrl,
          backlinkState: backlink.state,
          attempts: 1,
          error
        }),
        detection_source: site.source
      },
      diagnostics
    }, 200);
  } catch (error) {
    const finishedAt = services.now();
    const errorCode = safeRuntimeError(error);
    await recordRuntimeState(services.store, {
      kind: 'probe',
      status: 'error',
      startedAt: checkedAt,
      finishedAt,
      jobId: '',
      generation: '',
      detail: { mode, result_state: 'error', error: errorCode },
      updatedAt: finishedAt
    });
    return jsonResponse({ success: false, error: errorCode }, errorCode === 'probe_timeout' ? 504 : 500);
  } finally {
    clearTimeout(deadlineTimer);
  }
}

export async function handleFetch(request: Request, services: RuntimeServices): Promise<Response> {
  const url = new URL(request.url);
  if (request.method === 'GET' && url.pathname === '/health') {
    return jsonResponse({ ok: true, service: 'friend-link-checker' }, 200);
  }

  if (request.method === 'OPTIONS' && url.pathname === '/v1/jobs') {
    const allowedOrigin = configuredOrigin(services.config.allowedOrigin);
    if (!allowedOrigin || !requestOriginAllowed(request, services.config.allowedOrigin)) {
      return new Response(null, { status: 403 });
    }
    return new Response(null, {
      status: 204,
      headers: {
        'access-control-allow-origin': allowedOrigin,
        'access-control-allow-methods': 'POST, OPTIONS',
        'access-control-allow-headers': 'Content-Type, X-Argon-Timestamp, X-Argon-Nonce, X-Argon-Signature',
        'access-control-max-age': '600',
        vary: 'Origin'
      }
    });
  }

  if (request.method === 'POST' && url.pathname === '/v1/jobs') {
    return handleSubmission(request, services);
  }

  if (request.method === 'POST' && url.pathname === '/v1/status') {
    return handleStatusRead(request, services);
  }

  if (request.method === 'POST' && url.pathname === '/v1/probe') {
    return handleProbe(request, services);
  }

  const resultMatch = url.pathname.match(/^\/v1\/jobs\/([A-Za-z0-9_-]{1,96})\/result$/);
  if (request.method === 'POST' && resultMatch) {
    return handleSignedResultRead(request, resultMatch[1], services);
  }

  const jobMatch = url.pathname.match(/^\/v1\/jobs\/([A-Za-z0-9_-]{1,96})$/);
  if (request.method === 'GET' && jobMatch) {
    return handleJobRead(request, jobMatch[1], services);
  }
  return jsonResponse({ success: false, error: 'not_found' }, 404);
}

export function toQueueMessages(jobs: FriendLinkJob[]): QueueMessage[] {
  return jobs.map((job) => ({ jobId: job.id, generation: job.generation }));
}
