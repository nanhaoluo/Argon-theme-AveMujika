# Cloudflare Worker 友链检测说明

> 核对日期：2026-07-28。Cloudflare 免费额度和产品限制可能调整，部署前请以官方文档为准。

## 项目状态

主题已经接入 Worker 的服务端非阻塞派发、签名回调和 WordPress 服务端主动取回兜底，并在“Argon 主题设置 → 友情链接”提供启用开关、Worker URL 与两把 HMAC 密钥配置。

公开仓库的完整部署步骤、Wrangler 示例、Custom Domain 配置和故障排查统一维护在 [Worker README](../workers/friend-link-checker/README.md)。本页只解释架构选择和源站保护边界，避免两份命令清单发生漂移。

## 为什么使用 Queue

```text
WordPress 服务端签名并非阻塞提交 -> Worker 接单 -> D1 + Queue
                                              |
                                     Queue 消费者访问目标站
                                              |
                    签名回调（快速路径）或 WordPress 签名主动拉取结果
```

WordPress 只生成固定任务内容和 HMAC，并以非阻塞请求直接交给 Worker；失败任务由服务端定时补派。接单 Worker 只校验并入队，真正访问目标博客的是稍后运行的 Queue 消费者，不是 WordPress 服务器，也不是访客或管理员浏览器。结果优先走签名回调；若源站 WAF 拦截 Worker 回调，WordPress 会在管理轮询和 5 分钟 WP-Cron 中使用当前 `generation` 主动取回同一份结果。

这种拆分同时解决三个问题：

1. 目标站不会看到 WordPress 源站的出站 IP。
2. 浏览器拿不到任务 ticket、HMAC 密钥，也不能指定任意回调地址。
3. 随机延迟、重试、任务代际和 D1 状态都在异步层处理，不阻塞友链申请。

## 安全边界

- 初始目标与每次重定向都会先解析 DNS，并拒绝私网、回环、保留和无法解析的地址，降低 SSRF 风险。
- Public Suffix List 用于限定主域、`www`、`blog` 候选的站点边界，避免把共享托管后缀误当成同一网站。
- 任务提交和结果回调使用两把不同的 HMAC Secret，并校验 5 分钟时间窗与随机数防重放。
- 每次重新提交都会更新不可变 `generation`；旧 Queue 消息与旧回调不能覆盖新任务。
- 主动查询结果同样校验提交 HMAC、5 分钟时间窗和 `generation`，只能读取当前友链任务；成功取回后与回调共用同一套字段优先级和落库逻辑。
- `unknown` 表示地区网络、超时、403、真实验证页或内容不可读，不会被自动当成“未上友链”；普通页面加载评论验证码脚本不会被误判。
- 管理查询令牌只保存在 Cloudflare Secret 中，不进入 WordPress 后台。

Worker 只能隐藏友链探测请求的源站出口。博客仍需处理历史 A/AAAA 记录、直连子域名、邮件头和静态资源 URL 等旁路泄露；建议为博客开启 Cloudflare 代理，并让源站防火墙只接受 Cloudflare IP，或使用 Cloudflare Tunnel。

## 公开仓库约定

- Git 只跟踪 `wrangler.example.jsonc`，不跟踪个人 `wrangler.jsonc`。
- 示例配置只使用 `example.com` 和 D1 占位符，不包含部署者域名或资源 ID。
- Secret 只能通过 `wrangler secret put` 设置，不能写入 JSONC、JavaScript 或 Git。
- WordPress 密钥输入框不回显，且设置 JSON 导入、导出会跳过敏感字段。
- `wp-config.php` 常量和同名环境变量优先于后台数据库选项，便于对安全要求更高的部署者使用。

## 官方资料

- [Workers 定价](https://developers.cloudflare.com/workers/platform/pricing/)
- [Workers 平台限制](https://developers.cloudflare.com/workers/platform/limits/)
- [Workers 最佳实践](https://developers.cloudflare.com/workers/best-practices/workers-best-practices/)
- [Wrangler 配置](https://developers.cloudflare.com/workers/wrangler/configuration/)
- [Wrangler Secrets](https://developers.cloudflare.com/workers/configuration/secrets/)
- [Queues 定价](https://developers.cloudflare.com/queues/platform/pricing/)
- [Queues 限制](https://developers.cloudflare.com/queues/platform/limits/)
- [D1 定价](https://developers.cloudflare.com/d1/platform/pricing/)
- [D1 限制](https://developers.cloudflare.com/d1/platform/limits/)
- [Worker Custom Domains](https://developers.cloudflare.com/workers/configuration/routing/custom-domains/)
- [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/)
