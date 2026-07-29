import type { BacklinkState } from './detector';
import type { FriendLinkJob, JobStatus } from './types';

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

export interface FriendLinkResultInput {
  state: JobStatus;
  accessible: boolean;
  readable: boolean;
  detectedUrl: string;
  primary: string;
  subtitle: string;
  description: string;
  avatar: string;
  linksPage: string;
  checkedUrl: string;
  backlinkState: BacklinkState;
  attempts: number;
  error: string;
}

export function friendLinkResultPayload(result: FriendLinkResultInput): Record<string, unknown> {
  return {
    state: result.state,
    accessible: result.accessible,
    readable: result.readable,
    detected_url: truncateResultText(result.detectedUrl, RESULT_FIELD_LIMITS.url),
    primary: truncateResultText(result.primary, RESULT_FIELD_LIMITS.primary),
    subtitle: truncateResultText(result.subtitle, RESULT_FIELD_LIMITS.subtitle),
    description: truncateResultText(result.description, RESULT_FIELD_LIMITS.description),
    avatar: truncateResultText(result.avatar, RESULT_FIELD_LIMITS.url),
    links_page: truncateResultText(result.linksPage, RESULT_FIELD_LIMITS.url),
    checked_url: truncateResultText(result.checkedUrl, RESULT_FIELD_LIMITS.url),
    backlink_state: result.backlinkState,
    source: 'cloudflare_worker',
    attempts: result.attempts,
    error: truncateResultText(result.error, RESULT_FIELD_LIMITS.error)
  };
}

export function jobResultPayload(job: FriendLinkJob): Record<string, unknown> {
  return {
    version: 1,
    job_id: job.id,
    generation: job.generation,
    checked_at: job.checkedAt,
    result: friendLinkResultPayload({
      state: job.status,
      accessible: job.accessible,
      readable: job.readable,
      detectedUrl: job.detectedUrl,
      primary: job.primary,
      subtitle: job.subtitle,
      description: job.description,
      avatar: job.avatar,
      linksPage: job.linksPage,
      checkedUrl: job.checkedUrl,
      backlinkState: job.backlinkState,
      attempts: job.attempts,
      error: job.lastError
    })
  };
}
