# Argon 主题优化计划

## 概述

本计划涵盖10个优化任务，涉及设置页、AI功能、前端交互、样式优化和功能修复。

## 任务分组

### 模块A：设置页和后台优化
- 任务#1：移除CDN加速选项
- 任务#3：整理设置页目录
- 任务#4：优化邮件模板

### 模块B：AI功能简化
- 任务#2：简化AI功能，移除未闭环功能

### 模块C：首页和样式优化
- 任务#5：首页卡片整体可点击
- 任务#6：优化特殊样式（黑幕、糊话）
- 任务#7：优化代码块样式并移除全屏按钮

### 模块D：友情链接和反馈页
- 任务#8：修复友情链接头像和PJAX显示
- 任务#9：统一反馈页样式
- 任务#10：实现反馈页图片预览

---

## 详细实施方案

### 任务#1：移除设置页 CDN 加速选项

**位置**：`settings.php:213-245`

**问题**：CDN 加速选项与最初的资源不一致，需要移除。

**实施步骤**：
1. 定位 CDN section（第213行：`<h3 id="section-cdn">`）
2. 移除整个 CDN 配置的 `<tr>` 块（包括标题行和选项行）
3. 移除后端保存逻辑中的 CDN 选项处理
4. 检查 `functions.php` 中是否有依赖 `argon_assets_path` 选项的代码，需要调整为使用默认本地路径

**注意事项**：
- 保留 Mathjax/Katex/Gravatar 的 CDN 设置（这些是独立的）
- 需要处理已有用户的设置迁移（设为默认值）

**影响范围**：settings.php, functions.php（资源加载部分）

---

### 任务#2：简化 AI 功能，移除未闭环功能

**位置**：
- `ai-summary-query.php`
- `template-parts/ai-summary.php`
- `functions.php`（AI 相关函数）

**需要探查**：
1. 阅读 `ai-summary-query.php` 完整实现，了解 AI 功能的前后端交互
2. 检查 `template-parts/ai-summary.php` 的前端展示
3. 识别哪些 UI 元素没有对应的后端实现
4. 识别哪些功能不完整或存在逻辑问题

**实施原则**：
- 保留已完整闭环的核心 AI 摘要功能
- 移除只有前端展示但无后端支持的功能
- 确保删除后不影响文章显示

**验证**：访问启用了 AI 摘要的文章，检查功能是否正常

---

### 任务#3：整理后台设置页目录

**位置**：`settings.php`

**当前状态**：设置页有多个 section，需要查看整体结构

**实施步骤**：
1. 使用 Grep 提取所有 section 标题（`<h3 id="section-*">`）
2. 绘制当前目录结构
3. 重新分组和排序，使相关设置项聚合
4. 更新左侧导航（如果有）

**预期改进**：
- 将相关功能分组（如：外观、评论、社交、高级）
- 按使用频率和重要性排序
- 添加更清晰的分组标题

---


### 任务#4：优化邮件内容默认模板

**位置**：`email-templates/` 目录

**实施步骤**：
1. 阅读每个模板文件，了解当前的邮件格式
2. 识别需要优化的点：语气、排版、信息完整性
3. 统一邮件风格，确保友好、清晰

---

### 任务#5：首页文章卡片整体可点击

**位置**：`template-parts/content-preview-2.php`, `style.css`, `argontheme.js`

**当前状态**：
- 第1行：`<article class="post-preview-layout-2">`
- 第21行：`<a class="post-title">` - 仅标题可点击

**实施方案（推荐）**：在 article 上添加点击事件
```javascript
$(document).on('click', '.post-preview-layout-2', function(e) {
    if (!$(e.target).is('a') && !$(e.target).closest('a').length) {
        window.location.href = $(this).find('.post-title').attr('href');
    }
});
```

**移除 post-title hover 扩张效果**：
- 在 `style.css` 中搜索 `.post-title:hover`
- 移除 transform scale 等扩张动画

---

### 任务#6：优化特殊样式（黑幕、糊话）

**位置**：`style.css`, `functions.php`

**需要探查**：
1. 查找 `argon_apply_comment_macros` 函数定义
2. 查找黑幕相关的 CSS 类
3. 测试当前效果与主题风格的契合度

**优化方向**：
- 统一视觉设计语言
- 确保深色/浅色模式下都有良好表现
- 优化动画和交互


---

### 任务#7：优化代码块样式并移除全屏按钮

**位置**：
- `argontheme.js:8198-8201` - 全屏按钮HTML
- `argontheme.js:8355-8360` - 全屏按钮事件
- `style.css` - 代码块样式

**实施步骤**：

1. **移除全屏按钮**：
   - 删除 argontheme.js:8198-8200 的按钮HTML生成
   - 删除 argontheme.js:8355-8363 的点击事件
   - 移除相关CSS样式

2. **优化行号显示**：
   - 查找 `.line-numbers` 相关样式
   - 调整 padding/margin，使行号左移贴近代码块
   - 确保行号与代码对齐

3. **优化整体样式**：
   - 检查边框、圆角、阴影
   - 优化背景色和对比度

---

### 任务#8：修复友情链接头像和PJAX显示

**位置**：
- `friend-links.php` - 友情链接逻辑
- `argontheme.js` - PJAX生命周期

**问题分析**：
1. 自动更新无法获取正确的站点/站长头像
2. PJAX跳转后图片不显示，只显示首字母
3. 页面刷新后才能显示图片

**实施步骤**：

1. **修复头像获取逻辑**：
   - 查看 friend-links.php:213 的 `argon_select_friend_link_image` 函数
   - 分析头像获取API和选择逻辑
   - 改进头像URL的提取和验证

2. **修复PJAX显示问题**：
   - 在 argontheme.js 中查找PJAX生命周期事件
   - 在 `pjax:complete` 或 `pjax:end` 中添加友情链接初始化
   - 确保图片加载和首字母fallback正确触发

**验证**：访问友情链接页面，PJAX跳转后图片应正常显示


---

### 任务#9：统一反馈页样式

**位置**：`feedback.php`

**当前状态**：
- 第753行：`.feedback-list-tools` 样式定义
- 样式使用了 CSS 变量（如 `--color-border`, `--themecolor`）

**问题**：样式与主题全局风格不够一致

**实施步骤**：
1. 对比 `.feedback-list-tools` 与主题其他表单元素的样式
2. 识别不一致的地方（如：边框粗细、圆角大小、间距、字体）
3. 调整为与主题一致的设计规范
4. 确保深色模式下的适配

**参考元素**：
- 设置页的表单元素
- 评论表单的输入框
- 侧边栏的搜索框

---

### 任务#10：实现反馈页图片本页预览

**位置**：`feedback.php`, 新建 JS/CSS 模块

**当前状态**：
- 第730-732行：`.feedback-images-display` 样式
- 第731行：`cursor: pointer` - 图片可点击
- 当前点击图片跳转到新页面

**参考实现**：`E:\project\picapi\public\assets\js\admin\modals.js`
- 第16-21行：previewModal 模板
- 使用模态框实现本页预览

**实施方案**：

1. **创建图片预览模态框HTML**：
```html
<div id="feedback-image-modal" class="image-preview-modal">
    <div class="preview-close">&times;</div>
    <img id="feedback-preview-img" src="" alt="">
</div>
```

2. **添加样式**（参考主题设计语言）：
```css
.image-preview-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.9);
    animation: fadeIn 0.3s;
}
.image-preview-modal img {
    max-width: 90%; max-height: 90%;
    margin: auto;
    animation: zoomIn 0.3s;
}
```

3. **添加JS交互**：
```javascript
$(document).on('click', '.feedback-images-display img', function(e) {
    e.preventDefault();
    $('#feedback-preview-img').attr('src', $(this).attr('src'));
    $('#feedback-image-modal').fadeIn(300);
});
$(document).on('click', '#feedback-image-modal, .preview-close', function() {
    $('#feedback-image-modal').fadeOut(300);
});
```

4. **动画优化**：
- 进入：fadeIn + zoomIn
- 退出：fadeOut
- 使用主题的 `--animation-*` CSS 变量

**验证**：访问反馈页，点击图片应在本页弹出预览，点击背景或关闭按钮退出


---

## 实施顺序建议

### 第一阶段：简单且独立的任务
1. **任务#1** - 移除 CDN 加速（低风险，快速完成）
2. **任务#7** - 移除代码块全屏按钮（简单删除操作）
3. **任务#5** - 首页卡片整体可点击（前端交互优化）

### 第二阶段：需要审查和分析的任务
4. **任务#2** - 简化 AI 功能（需要仔细审查代码）
5. **任务#3** - 整理设置页目录（需要全局视角）
6. **任务#4** - 优化邮件模板（需要逐个审查模板）

### 第三阶段：需要深入调试的任务
7. **任务#8** - 修复友情链接（涉及PJAX和异步加载）
8. **任务#6** - 优化特殊样式（需要测试多种场景）

### 第四阶段：需要新开发的任务
9. **任务#9** - 统一反馈页样式（样式调整）
10. **任务#10** - 实现图片预览（新功能开发）

---

## 风险和注意事项

### 高风险项
- **任务#2（AI功能）**：删除不当可能导致前端报错
- **任务#8（友情链接）**：PJAX相关问题可能影响页面跳转

### 需要特别注意
- **编码问题**：多个文件存在GBK乱码注释，修改时不要触碰这些字节
- **精确匹配**：使用小片段精确匹配，避免大范围替换
- **Git管理**：仓库长期处于dirty状态，commit前必须核对暂存集合
- **验证方法**：每个任务完成后运行快速验证（语法检查、浏览器测试）

---

## 验证策略

### 语法验证
```bash
node --check argontheme.js
php -l functions.php
php -l settings.php
php -l feedback.php
```

### 功能验证清单
- [ ] 设置页正常加载，无CDN选项
- [ ] 首页卡片点击任意位置可跳转
- [ ] 代码块显示正常，无全屏按钮
- [ ] 友情链接页面图片正常显示（含PJAX跳转后）
- [ ] 反馈页样式一致，图片可本页预览
- [ ] AI功能（如果保留）正常工作
- [ ] 邮件发送正常（测试评论通知等）
- [ ] 特殊样式（黑幕、糊话）渲染正确

---

## 提交策略

按功能模块分别提交：
1. `fix: 移除设置页 CDN 加速选项并整理目录`
2. `refactor: 简化 AI 功能实现`
3. `feat: 首页文章卡片整体可点击`
4. `fix: 优化代码块样式并移除全屏按钮`
5. `fix: 修复友情链接头像获取和 PJAX 显示`
6. `feat: 实现反馈页图片本页预览功能`
7. `style: 优化特殊样式和反馈页样式一致性`
8. `improve: 优化邮件内容默认模板`
