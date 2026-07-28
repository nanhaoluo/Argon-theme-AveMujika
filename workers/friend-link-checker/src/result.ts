import type { FriendLinkJob } from './types';

const RESULT_FIELD_LIMITS = {
  url: 2_048,
  primary: 200,
  subtitle: 300,
  description: 1_000,
  error: 300
} as const;

function truncateResultText(value: string, maxCodeUnits: number): string {
  if (value.length <= maxCodeUnits) return value;
  let truncated = value.slice(0, maxCodeUnits);
  const lastCodeUnit = truncated.charCodeAt(truncated.length - 1);
  if (lastCodeUnit >= 0xd800 && lastCodeUnit <= 0xdbff) truncated = truncated.slice(0, -1);
  return truncated;
}

export function isTerminalJob(job: FriendLinkJob): boolean {
  return job.status === 'complete' || job.status === 'unknown' || job.status === 'error';
}

export function jobResultPayload(job: FriendLinkJob): Record<string, unknown> {
  return {
    version: 1,
    job_id: job.id,
    generation: job.generation,
    checked_at: job.checkedAt,
    result: {
      state: job.status,
      accessible: job.accessible,
      readable: job.readable,
      detected_url: truncateResultText(job.detectedUrl, RESULT_FIELD_LIMITS.url),
      primary: truncateResultText(job.primary, RESULT_FIELD_LIMITS.primary),
      subtitle: truncateResultText(job.subtitle, RESULT_FIELD_LIMITS.subtitle),
      description: truncateResultText(job.description, RESULT_FIELD_LIMITS.description),
      avatar: truncateResultText(job.avatar, RESULT_FIELD_LIMITS.url),
      links_page: truncateResultText(job.linksPage, RESULT_FIELD_LIMITS.url),
      backlink_state: job.backlinkState,
      source: 'cloudflare_worker',
      attempts: job.attempts,
      error: truncateResultText(job.lastError, RESULT_FIELD_LIMITS.error)
    }
  };
}
