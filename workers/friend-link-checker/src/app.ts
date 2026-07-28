import { assertSafePublicUrl, isSameCandidateSite } from './domain';
import { constantTimeEqual, verifySignedPayload } from './security';
import type {
  FriendLinkJob,
  JobStore,
  QueueMessage,
  QueueProducer,
  WorkerConfiguration
} from './types';
import type { HostResolver, WorkerFetcher } from './detector';

const MAX_REQUEST_BODY_BYTES = 16 * 1024;
const JOB_ID_PATTERN = /^[A-Za-z0-9_-]{1,96}$/;

export interface RuntimeServices {
  config: WorkerConfiguration;
  store: JobStore;
  queue: QueueProducer;
  fetcher: WorkerFetcher;
  resolver: HostResolver;
  now: () => number;
  queueDelaySeconds: () => number;
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

  const jobMatch = url.pathname.match(/^\/v1\/jobs\/([A-Za-z0-9_-]{1,96})$/);
  if (request.method === 'GET' && jobMatch) {
    return handleJobRead(request, jobMatch[1], services);
  }
  return jsonResponse({ success: false, error: 'not_found' }, 404);
}

export function toQueueMessages(jobs: FriendLinkJob[]): QueueMessage[] {
  return jobs.map((job) => ({ jobId: job.id, generation: job.generation }));
}
