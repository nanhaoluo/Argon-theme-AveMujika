import { handleFetch, type RuntimeServices } from './app';
import { D1JobStore } from './d1-store';
import { createPublicDnsResolver } from './detector';
import { handleScheduled, processQueueMessage } from './processor';
import type { QueueMessage } from './types';

export interface Env {
  DB: D1Database;
  CHECK_QUEUE: Queue<QueueMessage>;
  ALLOWED_ORIGIN: string;
  SITE_URL: string;
  CALLBACK_URL: string;
  SUBMIT_HMAC_SECRET: string;
  CALLBACK_HMAC_SECRET: string;
  ADMIN_READ_TOKEN: string;
}

function randomQueueDelaySeconds(): number {
  const random = crypto.getRandomValues(new Uint16Array(1))[0];
  return 30 + (random % 151);
}

function createServices(env: Env): RuntimeServices {
  const fetcher = (input: string, init?: RequestInit) => fetch(input, init);
  return {
    config: {
      allowedOrigin: env.ALLOWED_ORIGIN,
      siteUrl: env.SITE_URL,
      callbackUrl: env.CALLBACK_URL,
      submitSecret: env.SUBMIT_HMAC_SECRET,
      callbackSecret: env.CALLBACK_HMAC_SECRET,
      adminReadToken: env.ADMIN_READ_TOKEN
    },
    store: new D1JobStore(env.DB),
    queue: {
      async send(message, options) {
        await env.CHECK_QUEUE.send(message, options);
      },
      async sendBatch(messages, options) {
        await env.CHECK_QUEUE.sendBatch(messages.map((body) => ({ body, ...options })));
      }
    },
    fetcher,
    resolver: createPublicDnsResolver(fetcher),
    now: () => Math.floor(Date.now() / 1000),
    queueDelaySeconds: randomQueueDelaySeconds
  };
}

const worker: ExportedHandler<Env, QueueMessage> = {
  async fetch(request, env) {
    try {
      return await handleFetch(request, createServices(env));
    } catch (error) {
      console.error('friend-link-checker request failed', error instanceof Error ? error.message : 'unknown_error');
      return new Response(JSON.stringify({ success: false, error: 'internal_error' }), {
        status: 500,
        headers: { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store' }
      });
    }
  },

  async queue(batch, env) {
    const services = createServices(env);
    for (const message of batch.messages) {
      try {
        await processQueueMessage(message.body, services);
        message.ack();
      } catch (error) {
        console.error('friend-link-checker queue task failed', error instanceof Error ? error.message : 'unknown_error');
        message.retry({ delaySeconds: 300 });
      }
    }
  },

  async scheduled(_controller, env) {
    await handleScheduled(createServices(env));
  }
};

export default worker;
