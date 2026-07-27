CREATE TABLE IF NOT EXISTS submission_nonces (
    nonce TEXT PRIMARY KEY,
    expires_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_submission_nonces_expires
    ON submission_nonces (expires_at);

CREATE TABLE IF NOT EXISTS jobs (
    id TEXT PRIMARY KEY,
    generation TEXT NOT NULL,
    url TEXT NOT NULL,
    hostname TEXT NOT NULL,
    links_page TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'queued'
        CHECK (status IN ('queued', 'running', 'complete', 'unknown', 'error')),
    accessible INTEGER NOT NULL DEFAULT 0 CHECK (accessible IN (0, 1)),
    readable INTEGER NOT NULL DEFAULT 0 CHECK (readable IN (0, 1)),
    detected_url TEXT NOT NULL DEFAULT '',
    primary_title TEXT NOT NULL DEFAULT '',
    subtitle TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    avatar_url TEXT NOT NULL DEFAULT '',
    backlink_state TEXT NOT NULL DEFAULT 'unknown'
        CHECK (backlink_state IN ('found', 'missing', 'unknown')),
    checked_at INTEGER NOT NULL DEFAULT 0,
    next_check_at INTEGER NOT NULL DEFAULT 0,
    failure_count INTEGER NOT NULL DEFAULT 0,
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT NOT NULL DEFAULT '',
    callback_status TEXT NOT NULL DEFAULT 'not_ready'
        CHECK (callback_status IN ('not_ready', 'pending', 'delivered')),
    callback_attempts INTEGER NOT NULL DEFAULT 0,
    callback_next_attempt_at INTEGER NOT NULL DEFAULT 0,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_jobs_due
    ON jobs (next_check_at, status);

CREATE INDEX IF NOT EXISTS idx_jobs_hostname
    ON jobs (hostname);
