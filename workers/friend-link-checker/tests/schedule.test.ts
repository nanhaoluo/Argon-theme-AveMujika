import { describe, expect, it } from 'vitest';
import {
  randomQueueDelaySeconds,
  randomRecheckDelaySeconds,
  SCHEDULE_POLICY
} from '../src/schedule';
import type { FriendLinkJob } from '../src/types';

function job(failureCount = 0): FriendLinkJob {
  return {
    id: 'link_123',
    generation: 'generation-123456',
    url: 'https://sample-blog.dev/',
    hostname: 'sample-blog.dev',
    linksPage: '',
    checkedUrl: '',
    status: 'complete',
    accessible: true,
    readable: true,
    detectedUrl: 'https://sample-blog.dev/',
    primary: '示例博客',
    subtitle: '',
    description: '',
    avatar: '',
    backlinkState: 'unknown',
    checkedAt: 1_800_000_000,
    nextCheckAt: 1_800_086_400,
    failureCount,
    attempts: 1,
    lastError: '',
    callbackStatus: 'delivered',
    callbackAttempts: 0,
    callbackNextAttemptAt: 0,
    callbackLastError: '',
    diagnostics: [],
    createdAt: 1_800_000_000,
    updatedAt: 1_800_000_000
  };
}

describe('random Worker scheduling policy', () => {
  it('keeps every Queue delay within 30 to 180 seconds', () => {
    expect(randomQueueDelaySeconds(() => 0)).toBe(30);
    expect(randomQueueDelaySeconds(() => 150)).toBe(180);
  });

  it('jitters successful rechecks between 20 and 28 hours', () => {
    const { min, max } = SCHEDULE_POLICY.successRecheckSeconds;
    expect(randomRecheckDelaySeconds(job(), true, () => 0)).toBe(min);
    expect(randomRecheckDelaySeconds(job(), true, () => max - min)).toBe(max);
  });

  it('adds bounded jitter to failure backoff without exceeding seven days', () => {
    const firstMinimum = Math.floor(SCHEDULE_POLICY.failureRecheckSeconds.base * 0.8);
    const firstMaximum = Math.ceil(SCHEDULE_POLICY.failureRecheckSeconds.base * 1.2);
    expect(randomRecheckDelaySeconds(job(0), false, () => 0)).toBe(firstMinimum);
    expect(randomRecheckDelaySeconds(job(0), false, () => firstMaximum - firstMinimum)).toBe(firstMaximum);

    const capped = randomRecheckDelaySeconds(job(20), false, () => 0xffffffff);
    expect(capped).toBeLessThanOrEqual(SCHEDULE_POLICY.failureRecheckSeconds.max);
    expect(capped).toBeGreaterThanOrEqual(Math.floor(SCHEDULE_POLICY.failureRecheckSeconds.max * 0.8));
  });
});
