# Argon-theme-AveMujika 项目说明

WordPress 主题（Argon 二开）。核心前端逻辑在 `argontheme.js`，全站启用 PJAX 局部刷新；含追番卡片、表情键盘、友链可用性检查（Cloudflare Worker + D1 + WP-Cron）等功能。测试在 `tests/` 目录。

## 历史经验（导入自 Codex 记忆）

- **PJAX 脚本竞态**：PJAX 加载的外部脚本必须 await 执行完成（`executePjaxScripts`），否则会出现生命周期竞态。
- **动效规范**：可见的元素位置变化使用 FLIP 动画；动效统一走 `ArgonMotion.fast`，保持短促克制。
- **长内容虚拟滚动**：修改 `argontheme.js` 虚拟滚动时注意：校验节点挂载状态与 `innerText`；tail-lock 冻结期间保持向上方向；关注 `tailLockActive`、`overflowAnchor` 与评论区边界闪烁、`MIDDLE_SECTION_TEXT_MISSING` 类问题（参考 `tests/` 中 long-content-middle-visibility 相关用例）。
- **友链检查链路**：Worker 拉取结果 + generation 锁（`stale_generation`）+ WP-Cron 调度 + `version.json` 徽标；涉及生产环境探测与 SFTP 部署，属线上链路，改动需谨慎验证。
- **其他功能区**：`stickers/manifest.json` 静态清单分发、评论 AI 审核、搜索框交互、表情键盘。

## 验证要求

- 前端修复至少运行 `tests/` 中既有相关用例；fixture/测试证据不等于完整 WordPress 线上 QA，汇报时需注明验证层级。
