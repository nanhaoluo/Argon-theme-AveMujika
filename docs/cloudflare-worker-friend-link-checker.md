# Cloudflare Worker 友链检测部署指南

> 核对日期：2026-07-27。本文只引用 Cloudflare 官方资料；免费额度以后可能调整，部署前请重新打开链接确认。

## 结论

Cloudflare Workers Free 足以承担个人博客的友链检测，而且 Cloudflare 的 Workers 产品页明确写明可以免费开始、**无需信用卡**。本项目推荐使用：

```text
浏览器/WordPress -> Worker 接单接口 -> Queue -> Worker 消费者访问目标站
                                      ^
                               Cron 定时投递
                                      |
                                  D1 保存状态
```

仓库中已经提供可部署实现：[workers/friend-link-checker](../workers/friend-link-checker/README.md)。下面的命令以该目录为准，不需要重新运行项目脚手架。

当前实现先交付 Worker 侧；主题中的签名签发和回调接收端将在下一步接入，且无需修改后台 `settings.php`。完成主题对接前可以部署并测试 `/health`，但现有友链页面不会自动向 Worker 投递任务。

关键点不是单纯“把 `fetch()` 放进 Worker”，而是必须让接单和访问目标站分成两次独立调用。WordPress 请求到达 Worker 时只校验并写入 Queue，然后立即返回 `202`；真正访问目标博客只能在 Queue 消费者或 Cron 任务中执行。否则，同一次入站请求触发的 Worker 子请求可能通过 `CF-Connecting-IP` 把调用方 IP 带给非 Cloudflare 目标站，仍可能暴露源站出口 IP。

此外，Cloudflare 会在 Worker 的所有 `fetch()` 子请求中加入 `CF-Worker`，其值用于标识拥有该 Worker 的 zone。不要把检测 Worker 挂在博客主域名所在的 zone；应使用名称中不含博客信息的 `workers.dev` 地址，最好使用独立 Cloudflare 账号或中性域名，并在上线前用自己控制的测试站检查实际请求头。

## 免费额度与限制

| 产品 | Workers Free 当前额度 | 对个人博客友链检测的含义 |
| --- | --- | --- |
| Workers | 100,000 次入站请求/天；每次 10 ms CPU；128 MB 内存；每次调用最多 50 个子请求、6 个同时等待响应的出站连接；每账号 5 个 Cron Trigger | 网络等待不计入 CPU 时间，但 HTML 解析、签名和业务代码计入；一次不要检测大量网站 |
| Cron Triggers | 包含在 Workers 中；Free 每账号最多 5 个；每次 10 ms CPU；最长墙钟时间 15 分钟 | 本实现每小时只扫描并投递少量到期任务；时间使用 UTC；配置变更最多约 15 分钟生效 |
| Queues | 10,000 个标准操作/天；Free 消息固定保留 24 小时 | 一条正常送达的小消息通常计写入、读取、删除共 3 次操作，即理论上约 3,333 条/天；重试会额外计读取操作 |
| Queues 单条/批次 | 单条消息最大 128 KB；消费者批次最大 100 条；单队列吞吐 5,000 条/秒 | 友链任务只放域名、任务 ID 和时间戳，不要放整页 HTML；消费者要支持幂等和重试 |
| KV | 100,000 次读/天；1,000 次写/天；1,000 次删/天；1,000 次 list/天；1 GB | 可做短期缓存，但本方案无需同时启用 KV |
| D1 | 5,000,000 行读取/天；100,000 行写入/天；总存储 5 GB | 足够存任务、目标域名、检测结果和下次检测时间 |
| D1 数据库限制 | Free 每账号 10 个数据库；单库最大 500 MB；总计最大 5 GB；每次 Free Worker 调用最多 50 条 D1 查询 | 只建一个库，并给 `hostname`、`next_check_at` 建索引，避免全表扫描 |

Workers、KV 和 D1 的免费日额度均在 UTC 00:00 重置。Free 达到额度后不是自动收费，而是相关请求或操作失败；不要把“免费额度”理解成无限量。

## 最小部署步骤

### 1. 登录并准备 Worker

准备 Node.js，然后从仓库进入 Worker 目录：

```powershell
cd E:\project\Argon-theme-AveMujika\workers\friend-link-checker
npm install
npx wrangler login
```

项目使用中性名称 `neutral-link-checker` 和 `workers.dev` 地址，并且不绑定博客主域名。Cloudflare 默认给用户 Workers Free plan，官方产品页说明免费开始不需要信用卡。

### 2. 创建 Queue 和 D1

```powershell
npx wrangler queues create friend-link-checks
npx wrangler d1 create friend-links
```

把命令返回的 D1 `database_id` 写入 `workers/friend-link-checker/wrangler.jsonc`。Queue、D1 和每小时第 17 分钟执行的 Cron 已经配置好，不需要重新抄写配置。

`17 * * * *` 表示每个 UTC 小时的第 17 分钟执行，特意避开整点。它每次只投递最多 25 个到期任务，不会每小时全量抓取。

### 3. 建最小数据表

```powershell
npm run db:migrate:remote
```

如需保留每次检测历史，再单独建历史表；第一版只保存最新状态最省额度。

### 4. 设置密钥

```powershell
npx wrangler secret put SUBMIT_HMAC_SECRET
npx wrangler secret put CALLBACK_HMAC_SECRET
npx wrangler secret put ADMIN_READ_TOKEN
```

- `SUBMIT_HMAC_SECRET`：验证 WordPress 签发的短期任务签名，避免公开接口被滥用。
- `CALLBACK_HMAC_SECRET`：Worker 回写检测结果时签名；WordPress 校验时间戳、随机数和 HMAC，并防重放。
- 不要把密钥写进前端 JavaScript、Git 或 `wrangler.jsonc`。

### 5. Worker 只实现三个职责

1. `fetch()` 接单：标准化 hostname，验证签名、时间戳和随机数重放，只调用 `env.CHECK_QUEUE.send(...)`，返回 `202`；**这里禁止访问目标站**。
2. `queue()` 消费：每批只处理 1 条任务；通过 DNS-over-HTTPS 检查初始地址和每次重定向的公网解析结果；使用 Public Suffix List 限定主域、`www`、`blog` 的站点边界；每个请求最多跟随 2 次重定向，限制响应大小和超时，解析标题/副标题/头像/反链并写入 D1，保证单次调用不突破 Free 的 50 个外部子请求限制。
3. `scheduled()` 定时：只查询到期任务并分批投递 Queue，不在一个 Cron 调用内全量抓站。

每次提交还会生成不可变 `generation`；Queue 认领、D1 保存和回调都按 `友链 ID + generation` 比较，旧任务不能覆盖同一 ID 的新提交。这样即使 WordPress 服务器负责签发任务，它的请求也会在 Queue 处终止；目标站只会收到后续 Queue 消费者产生的请求。

### 6. 本地测试并部署

```powershell
npm run check
npm run deploy
npx wrangler tail
```

首次部署后还要做两项验证：

1. 用自己控制的目标站记录源 IP 和全部请求头，确认没有博客源站 IP、博客域名、`Referer` 或自定义来源标识；重点查看 `CF-Connecting-IP`、`X-Real-IP` 和 `CF-Worker`。
2. 模拟超时、重定向环、重复消息、目标站返回超大 HTML、Queue 重试和免费额度耗尽，确保状态变为“未知/稍后重试”，而不是误判“未上链”。

## 源站保护边界

这套方案隐藏的是“友链探测请求的源站出口”，不自动修复历史 DNS 或其他旁路泄露。博客本身还应开启 Cloudflare 代理，并让源站防火墙只接受 Cloudflare IP，或者使用 Cloudflare Tunnel；同时排查历史 A/AAAA 记录、邮件头、直连子域名和资源 URL。

## 官方资料

- [Workers 产品页：Start building for free — no credit card required](https://www.cloudflare.com/developer-platform/products/workers/)
- [Workers 定价](https://developers.cloudflare.com/workers/platform/pricing/)
- [Workers 平台限制](https://developers.cloudflare.com/workers/platform/limits/)
- [Workers 入门](https://developers.cloudflare.com/workers/get-started/guide/)
- [Cron Triggers](https://developers.cloudflare.com/workers/configuration/cron-triggers/)
- [Queues 定价](https://developers.cloudflare.com/queues/platform/pricing/)
- [Queues 限制](https://developers.cloudflare.com/queues/platform/limits/)
- [Queues 配置](https://developers.cloudflare.com/queues/configuration/configure-queues/)
- [KV 定价](https://developers.cloudflare.com/kv/platform/pricing/)
- [KV 限制](https://developers.cloudflare.com/kv/platform/limits/)
- [D1 定价](https://developers.cloudflare.com/d1/platform/pricing/)
- [D1 限制](https://developers.cloudflare.com/d1/platform/limits/)
- [Worker 子请求中的 `CF-Connecting-IP` 与 `CF-Worker`](https://developers.cloudflare.com/fundamentals/reference/http-request-headers/)
- [Wrangler secrets](https://developers.cloudflare.com/workers/configuration/secrets/)
- [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/)
