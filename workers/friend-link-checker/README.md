# Argon 友链检测 Worker

这个 Worker 将友链抓取从 WordPress 源站移到 Cloudflare Queue 消费者，自动识别网站主标题、副标题、描述、头像、对方友链页、可访问性和反链状态，同时避免由博客服务器直接访问目标网站。检测不限于 WordPress，也支持自建框架、纯静态站、宣传页和论坛等普通 HTML 页面；头像按作者头像、网站 Logo、声明图片、有效内容图和站点图标依次降级。

```text
WordPress 服务端非阻塞 POST /v1/jobs -> D1 + Queue
                                                  |
                                         Queue 消费者访问目标站
                                                  |
                                      WordPress <- 签名回调
```

> 上传或更新 WordPress 主题不会更新 Cloudflare Worker。只要修改了本目录下的 Worker 源码，就必须在本目录重新执行 `npm run check` 和 `npm run deploy`，线上检测逻辑才会生效。

接单请求只校验签名并入队，不抓取目标站。Queue 会在 30～180 秒随机延迟后处理任务，降低提交动作与探测请求之间的时间关联。

## 准备工作

- 一个 Cloudflare 账号；个人博客流量通常可以从 Free 套餐开始。
- Node.js 22 LTS 或更新的受支持版本，以及 npm。
- 已安装并启用本仓库的 Argon 主题。
- WordPress 的固定公开地址，例如 `https://blog.example.com`。

下面命令都在本目录执行：

```powershell
cd path\to\Argon-theme-AveMujika\workers\friend-link-checker
npm install
npx wrangler login
```

## 一、创建自己的配置

生产配置包含账号资源 ID 和站点地址，因此仓库只跟踪通用模板。本地复制一份：

```powershell
Copy-Item wrangler.example.jsonc wrangler.jsonc
```

macOS/Linux 使用：

```bash
cp wrangler.example.jsonc wrangler.jsonc
```

`wrangler.jsonc` 已加入 `.gitignore`，不会随手提交到公开仓库。随后编辑其中四处配置：

```jsonc
{
  "name": "friend-link-checker",
  "vars": {
    "ALLOWED_ORIGIN": "https://blog.example.com",
    "SITE_URL": "https://blog.example.com",
    "CALLBACK_URL": "https://blog.example.com/wp-json/argon/v1/friend-link-result"
  },
  "d1_databases": [
    {
      "database_id": "REPLACE_WITH_D1_DATABASE_ID"
    }
  ]
}
```

- `name`：Worker 名称，可换成自己账号内未占用的中性名称。
- `ALLOWED_ORIGIN`：WordPress 站点的公开 Origin，用于配置校验与拒绝错误来源的浏览器请求；必须是“协议 + 域名”，末尾不要加 `/`。正常任务由 WordPress 服务端直接提交。
- `SITE_URL`：对方友链页中应出现的本站规范地址。
- `CALLBACK_URL`：主题接收检测结果的固定 REST 地址，不能由提交端临时指定。
- `database_id`：下一步创建 D1 后由 Wrangler 返回的 ID。

如果修改了示例中的 Queue、D1 名称，也要同步修改后续命令和 `wrangler.jsonc`。

## 二、创建 Queue 和 D1

```powershell
npx wrangler queues create friend-link-checks
npx wrangler d1 create friend-links
```

第二条命令会输出 D1 的 `database_id`。将它替换进本地 `wrangler.jsonc`，然后创建数据表：

```powershell
npm run db:migrate:remote
```

默认 Cron 是 `17 * * * *`，即每个 UTC 小时的第 17 分钟投递到期复检任务。

## 三、创建三个不同的 Secret

每个 Secret 都应使用独立随机值，不要为了省事设成相同内容。Windows PowerShell 可用下面三行生成一次随机值：

```powershell
$secretBytes = New-Object byte[] 32
$randomSource = [Security.Cryptography.RandomNumberGenerator]::Create()
$randomSource.GetBytes($secretBytes); [Convert]::ToBase64String($secretBytes)
```

macOS/Linux 可运行 `openssl rand -base64 32`。无论使用哪种方式，都要分别生成三次。

每生成一个新值，就执行对应命令并在安全提示中粘贴：

```powershell
npx wrangler secret put SUBMIT_HMAC_SECRET
npx wrangler secret put CALLBACK_HMAC_SECRET
npx wrangler secret put ADMIN_READ_TOKEN
```

- `SUBMIT_HMAC_SECRET`：WordPress 签发任务时使用。
- `CALLBACK_HMAC_SECRET`：Worker 回写结果时使用。
- `ADMIN_READ_TOKEN`：仅供管理员调用 Worker 查询接口，不需要写入 WordPress。

密钥不能写入 JavaScript、`wrangler.jsonc`、Git、截图或聊天记录。`wrangler secret list` 只能确认名称，无法读回 Secret 原文；遗失后直接重新设置，并同步更新 WordPress。

## 四、检查并部署

```powershell
npm run check
npm run deploy
```

Wrangler 会在成功信息中给出 `https://<worker-name>.<account-subdomain>.workers.dev`。这就是默认 Worker 基础 URL。检查健康状态：

```powershell
Invoke-RestMethod "https://<worker-name>.<account-subdomain>.workers.dev/health"
```

macOS/Linux 可运行：

```bash
curl "https://<worker-name>.<account-subdomain>.workers.dev/health"
```

预期返回：

```json
{"ok":true,"service":"friend-link-checker"}
```

## 五、在 Argon 主题中连接 Worker

进入 WordPress 后台的“Argon 主题设置 → 友情链接”，填写：

1. `Worker 基础 URL`：上一步得到的 `workers.dev` 或自定义域名，不含 `/v1/jobs`。
2. `提交 HMAC 密钥`：与 Cloudflare 的 `SUBMIT_HMAC_SECRET` 完全一致。
3. `回调 HMAC 密钥`：与 Cloudflare 的 `CALLBACK_HMAC_SECRET` 完全一致。
4. 将“自动友链检测 Worker”设为“启用”，保存设置。

密码框不会回显已有值，留空保存表示保持原值；主题设置的 JSON 导入、导出也会排除这些字段。只有勾选“清除后台密钥”并保存时，数据库中的两个 HMAC 值才会被删除。

如果不希望密钥进入 WordPress 数据库，可以改在 `wp-config.php` 的 `/* That's all, stop editing! */` 之前配置：

```php
define('ARGON_FRIEND_LINK_WORKER_URL', 'https://friend-link-checker.example-account.workers.dev');
define('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET', '与 Worker 中 SUBMIT_HMAC_SECRET 相同的值');
define('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET', '与 Worker 中 CALLBACK_HMAC_SECRET 相同的值');
```

也可以设置同名服务器环境变量。生效优先级为：

```text
wp-config.php 常量 > 环境变量 > WordPress 后台选项
```

旧版本已经配置上述三个常量的站点，无需额外保存启用开关。若想强制停用，可在后台选择“停用”，或设置 `ARGON_FRIEND_LINK_WORKER_ENABLED=false`；外部常量/环境变量仍优先于后台值。

## 可选：使用自定义域名

直接使用中性 `workers.dev` 地址最简单。如果需要 `worker.example.com`：

1. 域名必须位于当前 Cloudflare 账号中，并已激活该 Zone。
2. 打开 Cloudflare Dashboard → Workers & Pages → 对应 Worker → Settings → Domains & Routes。
3. 选择 Add → Custom Domain，输入完整子域名。

Cloudflare 会为 Worker Custom Domain 自动创建所需 DNS 记录，不要再手工添加指向源服务器的 A/AAAA 记录。也可以在本地 `wrangler.jsonc` 中加入：

```jsonc
"routes": [
  {
    "pattern": "worker.example.com",
    "custom_domain": true
  }
]
```

重新 `npm run deploy` 后，将 WordPress 中的 Worker 基础 URL 改为新地址。Custom Domain 本身不会暴露 WordPress 源站 IP，但域名可能让目标站推断 Worker 与博客的关系；更重视关联隔离时，应选择中性名称，必要时使用独立 Cloudflare 账号。

## 本地开发

本地命令使用被忽略的 `wrangler.jsonc`：

```powershell
npm run db:migrate:local
npm run dev
```

公开仓库的类型生成和检查固定读取 `wrangler.example.jsonc`，因此克隆者尚未建立个人配置时也能运行：

```powershell
npm run types
npm run typecheck
npm test
```

如果需要按自己的本地生产配置重新生成绑定类型，可运行 `npm run types:local`，但提交前应再运行 `npm run types`，避免把个人域名写进 `worker-configuration.d.ts`。

## API 与安全边界

- `POST /v1/jobs`：校验 WordPress 的 HMAC、5 分钟时间窗和随机数防重放，只写 D1/Queue，返回 `202`。
- `GET /v1/jobs/<job_id>`：需要 `Authorization: Bearer <ADMIN_READ_TOKEN>`，不要从公开页面调用。
- `POST CALLBACK_URL`：Worker 使用独立回调 HMAC；WordPress 校验签名、时间窗、随机数和任务代际。
- Queue 消费者会检查目标及每次重定向的 DNS 结果，拒绝私网、回环、保留和不可解析地址，避免 SSRF。
- `backlink_state=unknown` 表示超时、地区限制、真实验证页或内容不可读，不会被当成“确认未上链”；普通页面仅加载验证码脚本不会触发该状态。
- 相同友链重新提交时会生成新 `generation`，旧 Queue 消息和旧回调不能覆盖新结果。

这套方案隔离的是“友链探测请求的出口”，不会自动消除历史 DNS、直连子域名、邮件头或静态资源中的源站泄露。博客本身仍应开启 Cloudflare 代理，并限制源站只接受 Cloudflare IP，或使用 Cloudflare Tunnel。

## 常见问题

- WordPress 显示“已启用但配置不完整”：检查 Worker URL 和两把 HMAC；`ADMIN_READ_TOKEN` 不算 WordPress 配置项。
- 手工从浏览器调用 Worker 返回 `403 origin_not_allowed`：`ALLOWED_ORIGIN` 必须与地址栏的协议、域名和端口完全一致；主题的正常服务端派发不依赖浏览器 Origin。
- Worker 返回签名错误：确认两端使用对应的同一把密钥，并检查 WordPress 与 Cloudflare 的时间差。
- Queue 或 D1 binding 不存在：确认创建的资源名称与 `wrangler.jsonc` 一致，并已填写正确 `database_id`。
- 自定义域名打不开：检查它是否作为 Worker Custom Domain 添加；不要只创建普通 DNS 记录或只创建一条没有绑定脚本的 Route。
- 国外访问目标站缓慢或被屏蔽：结果会保持 `unknown` 并退避重试，不会误判为缺失反链。若站点长期限制 Cloudflare 出口，只能人工确认或增加部署在目标地区的独立探测节点。
