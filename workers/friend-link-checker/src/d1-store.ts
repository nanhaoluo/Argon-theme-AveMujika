import type { BacklinkState } from './detector';
import type {
  DetectionDiagnostic,
  DetectionSave,
  FriendLinkJob,
  JobStatus,
  JobStore,
  RuntimeKind,
  RuntimeStateRecord,
  WorkerStatusSnapshot
} from './types';

type JobRow = Record<string, unknown>;

function asString(value: unknown): string {
  return value == null ? '' : String(value);
}

function asNumber(value: unknown): number {
  const number = Number(value || 0);
  return Number.isFinite(number) ? number : 0;
}

function parseJsonRecord(value: unknown): Record<string, unknown> {
  try {
    const parsed = JSON.parse(asString(value));
    return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
      ? parsed as Record<string, unknown>
      : {};
  } catch {
    return {};
  }
}

function parseDiagnostics(value: unknown): DetectionDiagnostic[] {
  try {
    const parsed = JSON.parse(asString(value));
    if (!Array.isArray(parsed)) return [];
    return parsed.slice(0, 64).flatMap((entry): DetectionDiagnostic[] => {
      if (!entry || typeof entry !== 'object') return [];
      const row = entry as Record<string, unknown>;
      const phase = asString(row.phase);
      if (phase !== 'wordpress' && phase !== 'html' && phase !== 'backlink') return [];
      return [{
        phase,
        url: asString(row.url).slice(0, 2_048),
        code: asString(row.code).slice(0, 80),
        reachable: row.reachable === true,
        httpStatus: asNumber(row.httpStatus),
        durationMs: asNumber(row.durationMs)
      }];
    });
  } catch {
    return [];
  }
}

function serializeDiagnostics(value: DetectionDiagnostic[]): string {
  const bounded: Array<Record<string, unknown>> = [];
  for (const entry of value.slice(0, 64)) {
    const candidate = {
      phase: entry.phase,
      url: entry.url.slice(0, 2_048),
      code: entry.code.slice(0, 80),
      reachable: entry.reachable,
      httpStatus: entry.httpStatus,
      durationMs: entry.durationMs
    };
    if (JSON.stringify([...bounded, candidate]).length > 16 * 1024) break;
    bounded.push(candidate);
  }
  return JSON.stringify(bounded);
}

function rowToJob(row: JobRow): FriendLinkJob {
  return {
    id: asString(row.id),
    generation: asString(row.generation),
    url: asString(row.url),
    hostname: asString(row.hostname),
    linksPage: asString(row.links_page),
    checkedUrl: asString(row.checked_url),
    status: asString(row.status) as JobStatus,
    accessible: asNumber(row.accessible) === 1,
    readable: asNumber(row.readable) === 1,
    detectedUrl: asString(row.detected_url),
    primary: asString(row.primary_title),
    subtitle: asString(row.subtitle),
    description: asString(row.description),
    avatar: asString(row.avatar_url),
    backlinkState: asString(row.backlink_state) as BacklinkState,
    checkedAt: asNumber(row.checked_at),
    nextCheckAt: asNumber(row.next_check_at),
    failureCount: asNumber(row.failure_count),
    attempts: asNumber(row.attempts),
    lastError: asString(row.last_error),
    callbackStatus: asString(row.callback_status) as FriendLinkJob['callbackStatus'],
    callbackAttempts: asNumber(row.callback_attempts),
    callbackNextAttemptAt: asNumber(row.callback_next_attempt_at),
    callbackLastError: asString(row.callback_last_error),
    diagnostics: parseDiagnostics(row.diagnostics_json),
    createdAt: asNumber(row.created_at),
    updatedAt: asNumber(row.updated_at)
  };
}

function rowToRuntimeState(row: JobRow): RuntimeStateRecord | null {
  const kind = asString(row.kind);
  const status = asString(row.status);
  if (kind !== 'cron' && kind !== 'queue' && kind !== 'probe') return null;
  if (status !== 'running' && status !== 'ok' && status !== 'deferred' && status !== 'error') return null;
  return {
    kind,
    status,
    startedAt: asNumber(row.started_at),
    finishedAt: asNumber(row.finished_at),
    jobId: asString(row.job_id),
    generation: asString(row.generation),
    detail: parseJsonRecord(row.detail_json),
    updatedAt: asNumber(row.updated_at)
  };
}

export class D1JobStore implements JobStore {
  constructor(private readonly database: D1Database) {}

  async acceptSubmission(job: FriendLinkJob, nonce: string, nonceExpiresAt = job.updatedAt + 300): Promise<void> {
    try {
      await this.database.batch([
        this.database
          .prepare('INSERT INTO submission_nonces (nonce, expires_at) VALUES (?, ?)')
          .bind(nonce, nonceExpiresAt),
        this.database.prepare(`
          INSERT INTO jobs (
            id, generation, url, hostname, links_page, status, accessible, readable, detected_url,
            primary_title, subtitle, description, avatar_url, backlink_state, checked_at,
            next_check_at, failure_count, attempts, last_error, callback_status,
            callback_attempts, callback_next_attempt_at, diagnostics_json, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, 'queued', 0, 0, '', '', '', '', '', 'unknown', 0, ?, 0, 0, '', 'not_ready', 0, 0, '[]', ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            generation = excluded.generation,
            url = excluded.url,
            hostname = excluded.hostname,
            links_page = excluded.links_page,
            status = 'queued',
            accessible = 0,
            readable = 0,
            detected_url = '',
            primary_title = '',
            subtitle = '',
            description = '',
            avatar_url = '',
            backlink_state = 'unknown',
            checked_at = 0,
            next_check_at = excluded.next_check_at,
            failure_count = 0,
            attempts = 0,
            last_error = '',
            callback_status = 'not_ready',
            callback_attempts = 0,
            callback_next_attempt_at = 0,
            callback_last_error = '',
            diagnostics_json = '[]',
            checked_url = '',
            updated_at = excluded.updated_at
        `).bind(
          job.id,
          job.generation,
          job.url,
          job.hostname,
          job.linksPage,
          job.nextCheckAt,
          job.createdAt,
          job.updatedAt
        )
      ]);
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      if (/UNIQUE constraint failed:\s*submission_nonces\.nonce/i.test(message)) {
        throw new Error('submission_replayed');
      }
      throw error;
    }
  }

  async consumeNonce(nonce: string, nonceExpiresAt: number): Promise<void> {
    try {
      await this.database
        .prepare('INSERT INTO submission_nonces (nonce, expires_at) VALUES (?, ?)')
        .bind(nonce, nonceExpiresAt)
        .run();
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      if (/UNIQUE constraint failed:\s*submission_nonces\.nonce/i.test(message)) {
        throw new Error('submission_replayed');
      }
      throw error;
    }
  }

  async getJob(id: string): Promise<FriendLinkJob | null> {
    const row = await this.database.prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1').bind(id).first<JobRow>();
    return row ? rowToJob(row) : null;
  }

  async claimQueuedJob(
    id: string,
    generation: string,
    now = Math.floor(Date.now() / 1000)
  ): Promise<FriendLinkJob | null> {
    const row = await this.database
      .prepare(`
        UPDATE jobs
        SET status = 'running', updated_at = ?
        WHERE id = ? AND generation = ? AND status = 'queued'
        RETURNING *
      `)
      .bind(now, id, generation)
      .first<JobRow>();
    return row ? rowToJob(row) : null;
  }

  async saveDetection(id: string, generation: string, detection?: DetectionSave): Promise<void> {
    if (!detection) throw new Error('detection_result_missing');
    const status: JobStatus = detection.site.state === 'ok' ? 'complete' : 'unknown';
    await this.database.prepare(`
      UPDATE jobs SET
        status = ?,
        accessible = ?,
        readable = ?,
        detected_url = ?,
        primary_title = ?,
        subtitle = ?,
        description = ?,
        avatar_url = ?,
        links_page = CASE WHEN links_page = '' THEN ? ELSE links_page END,
        backlink_state = ?,
        checked_url = ?,
        checked_at = ?,
        next_check_at = ?,
        failure_count = CASE WHEN ? = 1 THEN 0 ELSE failure_count + 1 END,
        attempts = attempts + 1,
        last_error = ?,
        callback_status = 'pending',
        callback_attempts = 0,
        callback_next_attempt_at = ?,
        callback_last_error = '',
        diagnostics_json = ?,
        updated_at = ?
      WHERE id = ? AND generation = ?
    `).bind(
      status,
      detection.site.accessible ? 1 : 0,
      detection.site.readable ? 1 : 0,
      detection.site.detectedUrl,
      detection.site.primary,
      detection.site.subtitle,
      detection.site.description,
      detection.site.avatar,
      detection.site.linksPage,
      detection.backlinkState,
      detection.checkedUrl,
      detection.checkedAt,
      detection.nextCheckAt,
      detection.error === '' ? 1 : 0,
      detection.error,
      detection.checkedAt,
      serializeDiagnostics(detection.diagnostics),
      detection.checkedAt,
      id,
      generation
    ).run();
  }

  async markCallbackDelivered(
    id: string,
    generation: string,
    now = Math.floor(Date.now() / 1000)
  ): Promise<void> {
    await this.database
      .prepare(`
        UPDATE jobs
        SET callback_status = 'delivered', callback_next_attempt_at = 0, callback_last_error = '', updated_at = ?
        WHERE id = ? AND generation = ?
      `)
      .bind(now, id, generation)
      .run();
  }

  async deferCallback(
    id: string,
    generation: string,
    nextAttemptAt: number,
    now = Math.floor(Date.now() / 1000),
    error = 'callback_failed'
  ): Promise<void> {
    await this.database.prepare(`
      UPDATE jobs
      SET callback_attempts = callback_attempts + 1, callback_next_attempt_at = ?, callback_last_error = ?, updated_at = ?
      WHERE id = ? AND generation = ? AND callback_status = 'pending'
    `).bind(nextAttemptAt, error, now, id, generation).run();
  }

  async listDueJobs(now: number, limit = 25): Promise<FriendLinkJob[]> {
    const boundedLimit = Math.max(1, Math.min(limit, 100));
    const callbackLimit = Math.min(10, Math.max(1, Math.floor(boundedLimit * 0.4)));
    const callbacks = await this.database.prepare(`
      SELECT * FROM jobs
      WHERE callback_status = 'pending' AND callback_next_attempt_at <= ?
      ORDER BY callback_next_attempt_at ASC
      LIMIT ?
    `).bind(now, callbackLimit).all<JobRow>();
    const remaining = boundedLimit - (callbacks.results || []).length;
    if (remaining <= 0) return (callbacks.results || []).map(rowToJob);

    const checks = await this.database.prepare(`
      SELECT * FROM jobs
      WHERE callback_status <> 'pending'
        AND next_check_at <= ?
        AND (status <> 'running' OR updated_at <= ?)
      ORDER BY next_check_at ASC
      LIMIT ?
    `).bind(now, now - 900, remaining).all<JobRow>();
    return [...(callbacks.results || []), ...(checks.results || [])].map(rowToJob);
  }

  async markJobsQueued(messages: Array<{ jobId: string; generation: string }>, now = Math.floor(Date.now() / 1000)): Promise<void> {
    if (!messages.length) return;
    const statements = messages.map((message) => this.database
      .prepare(`
        UPDATE jobs
        SET
          status = CASE WHEN callback_status = 'pending' THEN status ELSE 'queued' END,
          callback_status = CASE WHEN callback_status = 'pending' THEN 'pending' ELSE 'not_ready' END,
          updated_at = ?
        WHERE id = ? AND generation = ? AND (status <> 'running' OR updated_at <= ?)
      `)
      .bind(now, message.jobId, message.generation, now - 900));
    await this.database.batch(statements);
  }

  async deleteExpiredNonces(now: number): Promise<void> {
    await this.database.prepare('DELETE FROM submission_nonces WHERE expires_at < ?').bind(now).run();
  }

  async setRuntimeState(state: RuntimeStateRecord): Promise<void> {
    const serializedDetail = JSON.stringify(state.detail);
    const detail = serializedDetail.length <= 4 * 1024
      ? serializedDetail
      : JSON.stringify({ error: 'runtime_detail_too_large' });
    await this.database.prepare(`
      INSERT INTO runtime_state (
        kind, status, started_at, finished_at, job_id, generation, detail_json, updated_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ON CONFLICT(kind) DO UPDATE SET
        status = excluded.status,
        started_at = excluded.started_at,
        finished_at = excluded.finished_at,
        job_id = excluded.job_id,
        generation = excluded.generation,
        detail_json = excluded.detail_json,
        updated_at = excluded.updated_at
    `).bind(
      state.kind,
      state.status,
      state.startedAt,
      state.finishedAt,
      state.jobId.slice(0, 96),
      state.generation.slice(0, 128),
      detail,
      state.updatedAt
    ).run();
  }

  async getStatusSnapshot(now: number, limit = 50): Promise<WorkerStatusSnapshot> {
    const boundedLimit = Math.max(25, Math.min(limit, 100));
    const [aggregateResult, recentResult, runtimeResult] = await this.database.batch<JobRow>([
      this.database.prepare(`
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued,
          SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) AS running,
          SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) AS complete,
          SUM(CASE WHEN status = 'unknown' THEN 1 ELSE 0 END) AS unknown_count,
          SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS error_count,
          SUM(CASE WHEN callback_status = 'pending' THEN 1 ELSE 0 END) AS callback_pending,
          SUM(CASE WHEN callback_status = 'delivered' THEN 1 ELSE 0 END) AS callback_delivered,
          SUM(CASE WHEN callback_status <> 'pending' AND next_check_at <= ?
            AND (status <> 'running' OR updated_at <= ?) THEN 1 ELSE 0 END) AS due_checks,
          SUM(CASE WHEN callback_status = 'pending' AND callback_next_attempt_at <= ?
            THEN 1 ELSE 0 END) AS due_callbacks
        FROM jobs
      `).bind(now, now - 900, now),
      this.database.prepare('SELECT * FROM jobs ORDER BY updated_at DESC, id ASC LIMIT ?').bind(boundedLimit),
      this.database.prepare('SELECT * FROM runtime_state ORDER BY updated_at DESC')
    ]);

    const aggregateRow = (aggregateResult.results || [])[0] || {};
    const runtime: WorkerStatusSnapshot['runtime'] = {};
    for (const row of runtimeResult.results || []) {
      const state = rowToRuntimeState(row);
      if (state) runtime[state.kind as RuntimeKind] = state;
    }
    return {
      aggregate: {
        total: asNumber(aggregateRow.total),
        queued: asNumber(aggregateRow.queued),
        running: asNumber(aggregateRow.running),
        complete: asNumber(aggregateRow.complete),
        unknown: asNumber(aggregateRow.unknown_count),
        error: asNumber(aggregateRow.error_count),
        callbackPending: asNumber(aggregateRow.callback_pending),
        callbackDelivered: asNumber(aggregateRow.callback_delivered),
        dueChecks: asNumber(aggregateRow.due_checks),
        dueCallbacks: asNumber(aggregateRow.due_callbacks)
      },
      recentJobs: (recentResult.results || []).map(rowToJob),
      runtime
    };
  }
}
