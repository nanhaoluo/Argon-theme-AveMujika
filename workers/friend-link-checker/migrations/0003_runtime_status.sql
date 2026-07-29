ALTER TABLE jobs ADD COLUMN diagnostics_json TEXT NOT NULL DEFAULT '[]';
ALTER TABLE jobs ADD COLUMN checked_url TEXT NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS runtime_state (
    kind TEXT PRIMARY KEY CHECK (kind IN ('cron', 'queue', 'probe')),
    status TEXT NOT NULL CHECK (status IN ('running', 'ok', 'deferred', 'error')),
    started_at INTEGER NOT NULL DEFAULT 0,
    finished_at INTEGER NOT NULL DEFAULT 0,
    job_id TEXT NOT NULL DEFAULT '',
    generation TEXT NOT NULL DEFAULT '',
    detail_json TEXT NOT NULL DEFAULT '{}',
    updated_at INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_runtime_state_updated
    ON runtime_state (updated_at DESC);
