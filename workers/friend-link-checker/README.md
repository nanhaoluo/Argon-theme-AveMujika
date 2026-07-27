# Argon 友链检测 Worker

这个 Worker 把友链抓取从 WordPress 源站移到 Cloudflare 的异步消费者：

```text
浏览器携带 WordPress 签名 -> POST /v1/jobs -> D1 + Queue
                                               |
                                      Queue 消费者访问目标站
                                               |
                                  D1 <- 签名回写 WordPress
                                               ^
                                      Cron 投递到期任务
```

接单请求本身绝不抓取目标网站。任务会随机延迟 30～180 秒，再由 Queue 消费者发起请求，避免把提交者 IP 带进目标请求并降低时间关联。

> 当前目录已经完成 Worker、Queue、D1、Cron、WordPress 签名签发器和回调接收端。主题接入不修改 `settings.php`，密钥只从 `wp-config.php` 常量或同名环境变量读取。

## 1. 准备中性 Worker

最强关联隔离应使用独立 Cloudflare 账号和中性的 `workers.dev` 子域名。当前部署选择 `worker.cartol.top` Custom Domain：它不会暴露 WordPress 源站 IP，但目标站可能通过 Cloudflare 的 `CF-Worker` 标识判断请求来自 `cartol.top` 所在 zone。

进入本目录安装依赖：

```powershell
cd E:\project\Argon-theme-AveMujika\workers\friend-link-checker
npm install
npx wrangler login
```

## 2. 创建 Queue 和 D1

```powershell
npx wrangler queues create friend-link-checks
npx wrangler d1 create friend-links
```

第二条命令会返回 D1 `database_id`。把它填入 [wrangler.jsonc](./wrangler.jsonc)，并把三个示例地址替换成真实博客地址：

```jsonc
"vars": {
  "ALLOWED_ORIGIN": "https://blog.cartol.top",
  "SITE_URL": "https://blog.cartol.top",
  "CALLBACK_URL": "https://blog.cartol.top/wp-json/argon/v1/friend-link-result"
}
```

- `ALLOWED_ORIGIN`：允许浏览器提交任务的唯一 Origin。
- `SITE_URL`：反链页面中需要寻找的本站地址。
- `CALLBACK_URL`：WordPress 接收签名结果的固定接口，不能由任务提交者指定。

应用数据库迁移：

```powershell
npm run db:migrate:remote
```

## 3. 配置密钥

生成三个不同的随机值。PowerShell 示例：

```powershell
$secretBytes = [byte[]]::new(32)
$rng = [Security.Cryptography.RandomNumberGenerator]::Create()
$rng.GetBytes($secretBytes)
[Convert]::ToBase64String($secretBytes)
```

每生成一次就执行对应命令并粘贴值：

```powershell
npx wrangler secret put SUBMIT_HMAC_SECRET
npx wrangler secret put CALLBACK_HMAC_SECRET
npx wrangler secret put ADMIN_READ_TOKEN
```

- `SUBMIT_HMAC_SECRET` 和 `CALLBACK_HMAC_SECRET` 还需要分别写入 WordPress 服务端配置。
- `ADMIN_READ_TOKEN` 只用于管理员读取 Worker 中的任务状态。
- 密钥不能进入前端 JavaScript、Git、`wrangler.jsonc` 或聊天记录。

## 4. 配置 WordPress 服务端

在服务器的 `wp-config.php` 中、`/* That's all, stop editing! */` 之前加入：

```php
define('ARGON_FRIEND_LINK_WORKER_URL', 'https://worker.cartol.top');
define('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET', '粘贴 Worker 中 SUBMIT_HMAC_SECRET 的值');
define('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET', '粘贴 Worker 中 CALLBACK_HMAC_SECRET 的值');
```

两个 HMAC 值必须与 Cloudflare Worker 中对应的 Secret 完全一致，且彼此不同。`ADMIN_READ_TOKEN` 不需要写入 WordPress。主题只把服务器签好的固定 JSON 交给浏览器转发，浏览器永远拿不到 HMAC 密钥。

## 5. 部署

```powershell
npm run check
npm run deploy
```

部署后检查：

```powershell
Invoke-RestMethod "https://worker.cartol.top/health"
```

预期返回：

```json
{"ok":true,"service":"friend-link-checker"}
```

Cron 在每个 UTC 小时的第 17 分钟运行，每次最多投递 25 个任务，并为正常复检保留容量，避免故障回调挤占全部队列。Queue 消费者每批只处理 1 条消息，单个请求最多跟随 2 次重定向，确保主域、`www`、`blog`、双协议、DNS 与回调合计不突破 Workers Free 的 50 个外部子请求限制。成功任务 24 小时后复检；失败任务从 6 小时开始指数退避，最多 7 天；回调失败同样独立退避。

## API 契约

### 提交任务

```http
POST /v1/jobs
Content-Type: application/json
Origin: https://blog.cartol.top
X-Argon-Timestamp: 1800000000
X-Argon-Nonce: 至少16位随机字符串
X-Argon-Signature: sha256=<64位十六进制HMAC>
```

请求体：

```json
{
  "job_id": "WordPress中的友链ID",
  "url": "friend-site.top",
  "links_page": "https://blog.friend-site.top/friends"
}
```

签名原文必须是未经改写的原始 JSON：

```text
<timestamp>.<nonce>.<raw JSON body>
```

使用 `SUBMIT_HMAC_SECRET` 计算 HMAC-SHA256。正确流程是 WordPress 生成原始 JSON、时间戳、随机数和签名，再让浏览器原样转发；浏览器永远拿不到密钥。签名有效期 5 分钟，D1 会拒绝随机数重放。

Worker 会把本次随机数作为不可变 `generation` 写入 Queue、D1 和回调。相同友链 ID 被重新提交后，旧消费者无法认领或覆盖新任务；WordPress 回调端也必须只接受当前 `generation`。

### 查询任务

```http
GET /v1/jobs/<job_id>
Authorization: Bearer <ADMIN_READ_TOKEN>
```

该接口用于部署调试，不应把管理令牌放进公开页面。

### Worker 回调

Worker 固定向 `CALLBACK_URL` 发送 JSON，并使用下面三个请求头和 `CALLBACK_HMAC_SECRET` 按同样规则签名：

```text
X-Argon-Timestamp
X-Argon-Nonce
X-Argon-Signature
```

WordPress 必须校验时间差、随机数重放和原始请求体 HMAC。回调结果的 `backlink_state` 只有三种值：

- `found`：成功读取反链页并找到本站 hostname。
- `missing`：成功读取反链页但没有找到本站 hostname。
- `unknown`：未提供反链页、超时、403、验证码或内容无法读取。

`unknown` 不能作为拒绝友链或隐藏友链的依据。

所有初始目标和重定向都会先通过 Cloudflare DNS-over-HTTPS 解析，并拒绝任何私网、回环、保留或无法解析的地址。域名候选使用 Public Suffix List（包含 `github.io` 等私有后缀）限定站点边界，避免把共享后缀误当成目标一级域。

## 本地开发

```powershell
npm run db:migrate:local
npm run dev
npm run typecheck
npm test
```

当前测试覆盖域名候选、Public Suffix 边界、DNS 私网地址拒绝、标题识别、WordPress/HTML 探测、验证码未知态、反链三态、签名接单、防重放、任务代际隔离、回调退避、Cron 公平调度和真实 D1 迁移。
