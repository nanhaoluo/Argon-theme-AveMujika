# Argon-theme-AveMujika - 基于 Ave Mujika 视效与深度定制的 WordPress Argon 主题

本项目是基于开源经典 WordPress 主题 `solstice23/argon` 的个人深度定制重构分支。主题融合了《BanG Dream! It's MyGO!!!!! / Ave Mujika》（若叶睦 / 三角初华 / 祥子等角色）的黑暗优雅哥特艺术美学，并在前端交互动效、PJAX 无刷新加载稳定性、友链健康自动巡检以及 AI 文章智能摘要等方面进行了系统级工程改造与性能调优。

## 技术栈与运行环境
- **运行环境**：WordPress 5.8+ (已在 WordPress 6.x / PHP 8.1 稳定测试)
- **核心语言**：PHP 7.4 / 8.0 / 8.1, JavaScript (ES6+), CSS3 / Sass
- **云原生扩展**：Cloudflare Workers (`workers/friend-link-checker/`)
- **端到端测试**：Playwright 自动化巡检套件 (`playwright.config.ts`)

## 核心特性与工程改造
- **Ave Mujika 专属哥特视效定制**：
  - 重构全局配色系统，采用 Ave Mujika 标志性的午夜黑、冷灰、暗紫与金属银高光。
  - 定制加载骨架屏、毛玻璃卡片悬浮阻尼动效与哥特字体衬线排版。
- **Cloudflare Worker 友链全自动健康巡检**：
  - 内置 `workers/friend-link-checker`，无服务器定时探测博客全量友链（Friend Links）存活状态。
  - 自动识别失联站、SSL 证书过期与域名迁移，并在前台页面（`friend-links.php`）智能标注失联状态。
- **AI 文章核心要点智能摘要 (`ai-summary-query.php`)**：
  - 接入大语言模型 API，在文章页面渲染前或通过异步请求即时生成 150 字精华提炼并以打字机动效呈现。
- **AI 标签整理与复用**：
  - 后台 **文章 → AI 整理标签**，或 **Argon 主题设置 → 文章 SEO 优化 → AI 整理标签** 进入；复用 SEO API，未指定时使用摘要 API。
  - 默认分析使用次数不超过 3 次的标签，也可选择全部标签。每批参考完整标签目录、使用次数与最多 3 个公开文章标题，预览同义词、拼写变体和过细标签的合并建议。
  - 勾选后按批迁移全部文章关联（含草稿、私密文章），成功后删除旧标签，保留其他标签；失败可重试。旧标签归档地址随删除失效。单次目录超过 120 KB 时会提示先手动整理，避免静默漏扫。
  - SEO 标签同步默认仅复用已有标签，每次最多追加 3 个；SEO 关键词单独保存。可在“标签同步策略”中显式允许新建标签。
- **设置页分类与逐项保存**：
  - 12 个功能分类提供全局搜索、分类目录和待保存筛选；切换分类保留修改，支持逐项撤销与离开提醒。
  - 字段模板位于 `inc/admin/settings/`，分类与资源注册位于 `inc/admin/settings-layout.php`，公共交互和样式位于 `assets/js/settings-admin.js`、`assets/css/settings-admin.css`。
  - JSON 备份排除敏感字段，支持复选框数组和动态日期范围；导入只填入界面，确认后逐项保存。
- **登录与错误页面**：
  - 登录、注册和找回密码保留 WordPress 原生流程，提供站点名称、明暗配色、键盘焦点和手机布局。
  - 宝塔站点若以服务器错误页替换 WordPress 404，可参考 `config/nginx-wordpress-404.conf.example` 在该站点覆盖 FastCGI 错误拦截；修改后先校验 Nginx 配置，再核对响应内容和 HTTP 状态码。
- **PJAX 页面无刷新与事件深度保活**：
  - 彻底攻克传统 PJAX 页面切换时 Prism/Highlight.js 代码高亮失效、MathJax 数学公式不重绘、Aplayer 音乐播放中断的顽疾。
  - 优化说说评论区预览机制（`comments-shuoshuo-preview.php`），确保局部刷新时不丢失输入框状态。
- **全站性能监控与优化 (`argon-performance.js`)**：
  - 移除陈旧第三方冗余 CDN 阻塞脚本，针对静态资源进行动态 WebP 转码与懒加载（LazyLoad）。

## 目录结构
```text
Argon-theme-AveMujika/
├── workers/
│   └── friend-link-checker/     # Cloudflare Worker 友链巡检无服务器源码
├── assets/                      # 前端脚本、样式表与 SVG 资源
├── ai-summary-query.php         # AI 智能文章摘要服务接口
├── argon-performance.js         # 前端性能监控与加载调优脚本
├── comments-shuoshuo-preview.php# 说说评论实时预览
├── friend-links.php             # 友链模板与健康状态渲染
├── functions.php                # 主题核心功能挂载与 WordPress Filter
├── header.php / footer.php      # 统一页头页脚模板
├── style.css                    # 主题元数据与核心样式表
└── README.md
```

## 安装与部署
1. 下载或将本项目目录打包为 zip 文件。
2. 登录 WordPress 管理后台，进入 **外观 -> 主题 -> 安装主题 -> 上传主题**。
3. 选择压缩包上传并点击“启用”。
4. 在“Argon 主题设置”后台面板中根据需要开启 Ave Mujika 特效、AI 摘要 API Key 及友链检测服务。
