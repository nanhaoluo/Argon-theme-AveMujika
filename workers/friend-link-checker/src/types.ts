import type { BacklinkState, SiteDetection } from './detector';

export type JobStatus = 'queued' | 'running' | 'complete' | 'unknown' | 'error';
export type CallbackStatus = 'not_ready' | 'pending' | 'delivered';
export type RuntimeKind = 'cron' | 'queue' | 'probe';
export type RuntimeStatus = 'running' | 'ok' | 'deferred' | 'error';

export interface DetectionDiagnostic {
  phase: 'wordpress' | 'html' | 'backlink';
  url: string;
  code: string;
  reachable: boolean;
  httpStatus: number;
  durationMs: number;
}

export interface FriendLinkJob {
  id: string;
  generation: string;
  url: string;
  hostname: string;
  linksPage: string;
  checkedUrl: string;
  status: JobStatus;
  accessible: boolean;
  readable: boolean;
  detectedUrl: string;
  primary: string;
  subtitle: string;
  description: string;
  avatar: string;
  backlinkState: BacklinkState;
  checkedAt: number;
  nextCheckAt: number;
  failureCount: number;
  attempts: number;
  lastError: string;
  callbackStatus: CallbackStatus;
  callbackAttempts: number;
  callbackNextAttemptAt: number;
  callbackLastError: string;
  diagnostics: DetectionDiagnostic[];
  createdAt: number;
  updatedAt: number;
}

export interface QueueMessage {
  jobId: string;
  generation: string;
}

export interface DetectionSave {
  site: SiteDetection;
  backlinkState: BacklinkState;
  checkedUrl: string;
  diagnostics: DetectionDiagnostic[];
  checkedAt: number;
  nextCheckAt: number;
  error: string;
}

export interface RuntimeStateRecord {
  kind: RuntimeKind;
  status: RuntimeStatus;
  startedAt: number;
  finishedAt: number;
  jobId: string;
  generation: string;
  detail: Record<string, unknown>;
  updatedAt: number;
}

export interface JobStatusAggregate {
  total: number;
  queued: number;
  running: number;
  complete: number;
  unknown: number;
  error: number;
  callbackPending: number;
  callbackDelivered: number;
  dueChecks: number;
  dueCallbacks: number;
}

export interface WorkerStatusSnapshot {
  aggregate: JobStatusAggregate;
  recentJobs: FriendLinkJob[];
  runtime: Partial<Record<RuntimeKind, RuntimeStateRecord>>;
}

export interface JobStore {
  acceptSubmission(job: FriendLinkJob, nonce: string, nonceExpiresAt?: number): Promise<void>;
  consumeNonce(nonce: string, nonceExpiresAt: number): Promise<void>;
  getJob(id: string): Promise<FriendLinkJob | null>;
  claimQueuedJob(id: string, generation: string, now?: number): Promise<FriendLinkJob | null>;
  saveDetection(id: string, generation: string, detection?: DetectionSave): Promise<void>;
  markCallbackDelivered(id: string, generation: string, now?: number): Promise<void>;
  deferCallback(id: string, generation: string, nextAttemptAt: number, now?: number, error?: string): Promise<void>;
  listDueJobs(now: number, limit?: number): Promise<FriendLinkJob[]>;
  markJobsQueued(messages: QueueMessage[], now?: number): Promise<void>;
  deleteExpiredNonces(now: number): Promise<void>;
  setRuntimeState(state: RuntimeStateRecord): Promise<void>;
  getStatusSnapshot(now: number, limit?: number): Promise<WorkerStatusSnapshot>;
}

export interface QueueBatchMessage {
  body: QueueMessage;
  delaySeconds?: number;
}

export interface QueueMetricsSnapshot {
  backlogCount: number;
  backlogBytes: number;
  oldestMessageTimestamp: number;
}

export interface QueueProducer {
  send(message: QueueMessage, options?: { delaySeconds?: number }): Promise<void>;
  sendBatch(messages: QueueBatchMessage[]): Promise<void>;
  metrics(): Promise<QueueMetricsSnapshot>;
}

export interface WorkerConfiguration {
  allowedOrigin: string;
  siteUrl: string;
  callbackUrl: string;
  submitSecret: string;
  callbackSecret: string;
  adminReadToken: string;
}
