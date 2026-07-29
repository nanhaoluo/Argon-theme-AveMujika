import type { JobStore, RuntimeStateRecord } from './types';

const SAFE_RUNTIME_ERRORS = new Set([
  'callback_timeout',
  'callback_secret_missing',
  'job_disappeared_after_detection',
  'detection_result_missing',
  'probe_timeout'
]);

export function safeRuntimeError(error: unknown): string {
  const raw = error instanceof Error
    ? error.message
    : (typeof error === 'string' ? error : 'unknown_error');
  const normalized = raw.toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
  if (/^callback_http_\d{3}$/.test(normalized)) return normalized;
  if (SAFE_RUNTIME_ERRORS.has(normalized)) return normalized;
  return 'internal_error';
}

export async function recordRuntimeState(store: JobStore, state: RuntimeStateRecord): Promise<void> {
  try {
    await store.setRuntimeState(state);
  } catch {
    console.error(JSON.stringify({
      message: 'friend-link-checker runtime state write failed',
      kind: state.kind,
      status: state.status
    }));
  }
}
