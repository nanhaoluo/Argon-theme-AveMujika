import type { BacklinkState, SiteDetection } from './detector';

export type JobStatus = 'queued' | 'running' | 'complete' | 'unknown' | 'error';
export type CallbackStatus = 'not_ready' | 'pending' | 'delivered';

export interface FriendLinkJob {
  id: string;
  generation: string;
  url: string;
  hostname: string;
  linksPage: string;
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
  checkedAt: number;
  nextCheckAt: number;
  error: string;
}

export interface JobStore {
  acceptSubmission(job: FriendLinkJob, nonce: string, nonceExpiresAt?: number): Promise<void>;
  getJob(id: string): Promise<FriendLinkJob | null>;
  claimQueuedJob(id: string, generation: string, now?: number): Promise<FriendLinkJob | null>;
  saveDetection(id: string, generation: string, detection?: DetectionSave): Promise<void>;
  markCallbackDelivered(id: string, generation: string, now?: number): Promise<void>;
  deferCallback(id: string, generation: string, nextAttemptAt: number, now?: number, error?: string): Promise<void>;
  listDueJobs(now: number, limit?: number): Promise<FriendLinkJob[]>;
  markJobsQueued(messages: QueueMessage[], now?: number): Promise<void>;
  deleteExpiredNonces(now: number): Promise<void>;
}

export interface QueueProducer {
  send(message: QueueMessage, options?: { delaySeconds?: number }): Promise<void>;
  sendBatch(messages: QueueMessage[], options?: { delaySeconds?: number }): Promise<void>;
}

export interface WorkerConfiguration {
  allowedOrigin: string;
  siteUrl: string;
  callbackUrl: string;
  submitSecret: string;
  callbackSecret: string;
  adminReadToken: string;
}
