import type { FriendLinkJob } from './types';

export const SCHEDULE_POLICY = {
  cron: '*/5 * * * *',
  queueDelaySeconds: { min: 30, max: 180 },
  successRecheckSeconds: { min: 20 * 60 * 60, max: 28 * 60 * 60 },
  failureRecheckSeconds: {
    base: 6 * 60 * 60,
    max: 7 * 24 * 60 * 60,
    jitterRatio: 0.2
  }
} as const;

function secureUint32(): number {
  return crypto.getRandomValues(new Uint32Array(1))[0];
}

export function randomIntegerInclusive(
  min: number,
  max: number,
  randomUint32: () => number = secureUint32
): number {
  const lower = Math.ceil(min);
  const upper = Math.floor(max);
  if (!Number.isSafeInteger(lower) || !Number.isSafeInteger(upper) || upper < lower) {
    throw new Error('invalid_random_range');
  }
  const span = upper - lower + 1;
  return lower + (randomUint32() % span);
}

export function randomQueueDelaySeconds(randomUint32: () => number = secureUint32): number {
  return randomIntegerInclusive(
    SCHEDULE_POLICY.queueDelaySeconds.min,
    SCHEDULE_POLICY.queueDelaySeconds.max,
    randomUint32
  );
}

export function randomRecheckDelaySeconds(
  job: FriendLinkJob,
  succeeded: boolean,
  randomUint32: () => number = secureUint32
): number {
  if (succeeded) {
    return randomIntegerInclusive(
      SCHEDULE_POLICY.successRecheckSeconds.min,
      SCHEDULE_POLICY.successRecheckSeconds.max,
      randomUint32
    );
  }

  const baseDelay = Math.min(
    SCHEDULE_POLICY.failureRecheckSeconds.base * (2 ** Math.min(job.failureCount, 5)),
    SCHEDULE_POLICY.failureRecheckSeconds.max
  );
  const minimum = Math.max(
    SCHEDULE_POLICY.queueDelaySeconds.max,
    Math.floor(baseDelay * (1 - SCHEDULE_POLICY.failureRecheckSeconds.jitterRatio))
  );
  const maximum = Math.min(
    SCHEDULE_POLICY.failureRecheckSeconds.max,
    Math.ceil(baseDelay * (1 + SCHEDULE_POLICY.failureRecheckSeconds.jitterRatio))
  );
  return randomIntegerInclusive(minimum, Math.max(minimum, maximum), randomUint32);
}

export function schedulePolicyPayload(): Record<string, unknown> {
  return {
    cron: SCHEDULE_POLICY.cron,
    timezone: 'UTC',
    due_scan_interval_seconds: 5 * 60,
    queue_delay_seconds: {
      min: SCHEDULE_POLICY.queueDelaySeconds.min,
      max: SCHEDULE_POLICY.queueDelaySeconds.max,
      per_job: true
    },
    success_recheck_seconds: {
      min: SCHEDULE_POLICY.successRecheckSeconds.min,
      max: SCHEDULE_POLICY.successRecheckSeconds.max
    },
    failure_recheck_seconds: {
      base: SCHEDULE_POLICY.failureRecheckSeconds.base,
      max: SCHEDULE_POLICY.failureRecheckSeconds.max,
      jitter_ratio: SCHEDULE_POLICY.failureRecheckSeconds.jitterRatio
    }
  };
}
