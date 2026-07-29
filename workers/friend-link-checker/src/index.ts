/*
 * @Author: nanhaoluo 3075912108@qq.com
 * @Date: 2026-07-27 19:12:41
 * @LastEditors: nanhaoluo 3075912108@qq.com
 * @LastEditTime: 2026-07-28 12:32:02
 * @FilePath: \Argon-theme-AveMujika\workers\friend-link-checker\src\index.ts
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */
import { handleFetch, type RuntimeServices } from './app';
import { D1JobStore } from './d1-store';
import { createPublicDnsResolver } from './detector';
import { handleScheduled, processQueueMessage } from './processor';
import { randomQueueDelaySeconds, randomRecheckDelaySeconds } from './schedule';
import type { QueueMessage } from './types';

interface WorkerSecrets {
  SUBMIT_HMAC_SECRET: string;
  CALLBACK_HMAC_SECRET: string;
  ADMIN_READ_TOKEN: string;
}

type RuntimeEnv = Omit<WorkerBindings, 'CHECK_QUEUE'> & WorkerSecrets & {
  CHECK_QUEUE: Queue<QueueMessage>;
};

function createServices(env: RuntimeEnv): RuntimeServices {
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
      async sendBatch(messages) {
        await env.CHECK_QUEUE.sendBatch(messages);
      },
      async metrics() {
        const metrics = await env.CHECK_QUEUE.metrics();
        const oldest = metrics.oldestMessageTimestamp;
        return {
          backlogCount: metrics.backlogCount,
          backlogBytes: metrics.backlogBytes,
          oldestMessageTimestamp: oldest instanceof Date ? oldest.getTime() : Number(oldest || 0)
        };
      }
    },
    fetcher,
    resolver: createPublicDnsResolver(fetcher),
    now: () => Math.floor(Date.now() / 1000),
    queueDelaySeconds: randomQueueDelaySeconds,
    recheckDelaySeconds: randomRecheckDelaySeconds
  };
}

const worker = {
  async fetch(request, env) {
    try {
      return await handleFetch(request, createServices(env));
    } catch (error) {
      console.error(JSON.stringify({
        message: 'friend-link-checker request failed',
        error: error instanceof Error ? error.message : 'unknown_error'
      }));
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
        const result = await processQueueMessage(message.body, services);
        if (result.disposition === 'retry') {
          console.warn(JSON.stringify({
            message: 'friend-link-checker callback deferred',
            error: result.error,
            retry_after_seconds: result.delaySeconds
          }));
          message.retry({ delaySeconds: result.delaySeconds });
        } else {
          message.ack();
        }
      } catch (error) {
        console.error(JSON.stringify({
          message: 'friend-link-checker queue task failed',
          error: error instanceof Error ? error.message : 'unknown_error'
        }));
        message.retry({ delaySeconds: 300 });
      }
    }
  },

  async scheduled(controller, env) {
    await handleScheduled(createServices(env), {
      cron: controller.cron,
      scheduledTimeMs: controller.scheduledTime
    });
  }
} satisfies ExportedHandler<RuntimeEnv, QueueMessage>;

export default worker;
