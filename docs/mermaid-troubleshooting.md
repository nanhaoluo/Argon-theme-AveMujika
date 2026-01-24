# Mermaid 图表故障排查指南

## 常见错误及解决方案

### 1. 语法错误：`Parse error on line 1`

#### 错误示例
```
Parse error on line 1: flowchart TD Sta
^
Expecting 'NEWLINE', 'SPACE', 'GRAPH', got 'ALPHA'
```

#### 原因分析
- 代码块中有多余的空格或缩进
- WordPress 或插件添加了额外的格式
- 代码提取不完整

#### 解决方案

**方案 1：检查代码格式**

确保 Mermaid 代码格式正确：

```markdown
三个反引号mermaid
flowchart TD
    A[开始] --> B[结束]
三个反引号
```

**注意**：
- 第一行只有 `flowchart TD`，后面不要有其他内容
- 每行开头不要有多余的空格（除了必要的缩进）
- 使用 Tab 或 4 个空格作为缩进

**方案 2：使用容器语法**

如果代码块格式有问题，尝试使用容器语法：

```markdown
::: mermaid
flowchart TD
    A[开始] --> B[结束]
:::
```

**方案 3：使用 Shortcode**

最稳定的方式是使用 Shortcode：

```
[mermaid]
flowchart TD
    A[开始] --> B[结束]
[/mermaid]
```

### 2. API 错误：`window.mermaid.render(...).then is not a function`

#### 错误示例
```
window.mermaid.render(...).then is not a function
```

#### 原因分析
- Mermaid 库版本过旧（< 10.0）
- Mermaid 库未正确加载
- CDN 加载失败

#### 解决方案

**方案 1：检查 Mermaid 版本**

在浏览器控制台中运行：

```javascript
console.log(mermaid.version);
```

如果版本 < 10.0，需要更新 CDN 或使用兼容模式。

**方案 2：检查 CDN 加载**

在浏览器控制台中运行：

```javascript
console.log(typeof window.mermaid);
console.log(typeof window.mermaid.render);
```

如果输出 `undefined`，说明 Mermaid 库未加载。

**解决步骤**：
1. WordPress 后台 → 外观 → Argon 主题选项
2. 找到"Mermaid 图表"设置
3. 检查"启用 Mermaid 支持"是否开启
4. 尝试切换不同的 CDN 源
5. 清除浏览器缓存后刷新

**方案 3：使用兼容模式**

主题已内置 Mermaid 8.x 和 10.x 的兼容代码，会自动检测并使用合适的 API。

如果仍然报错，在浏览器控制台查看详细日志：

```javascript
// 启用调试模式
localStorage.setItem('argon_mermaid_debug', 'true');
location.reload();
```

### 3. 渲染错误：图表显示不完整或错位

#### 原因分析
- CSS 样式冲突
- 容器宽度限制
- 主题切换问题

#### 解决方案

**方案 1：检查容器宽度**

在浏览器开发者工具中检查 `.mermaid-container` 的宽度。

如果宽度过小，添加自定义 CSS：

```css
.mermaid-container {
    max-width: 100%;
    overflow-x: auto;
}
```

**方案 2：检查主题模式**

Mermaid 图表会根据主题模式（日间/夜间）自动切换颜色。

如果颜色不正确：
1. 切换主题模式（日间 ↔ 夜间）
2. 刷新页面
3. 检查主题设置中的 Mermaid 主题配置

**方案 3：强制重新渲染**

在浏览器控制台中运行：

```javascript
// 清除已渲染标记
document.querySelectorAll('.mermaid-container').forEach(el => {
    el.remove();
});

// 重新渲染
if (typeof MermaidRenderer !== 'undefined') {
    MermaidRenderer.renderAllCharts();
}
```

### 4. PJAX 切换后图表消失

#### 原因分析
- PJAX 切换后未重新渲染
- 代码块转换未执行

#### 解决方案

**方案 1：检查 PJAX 配置**

确保主题的 PJAX 功能已启用：
1. WordPress 后台 → 外观 → Argon 主题选项
2. 找到"PJAX"设置
3. 确认已启用

**方案 2：手动触发渲染**

在浏览器控制台中运行：

```javascript
// 监听 PJAX 完成事件
$(document).on('pjax:complete', function() {
    console.log('[调试] PJAX 完成，重新渲染 Mermaid');
    if (typeof MermaidRenderer !== 'undefined') {
        MermaidRenderer.renderAllCharts();
    }
});
```

### 5. 代码块被代码高亮处理

#### 错误表现
- Mermaid 代码块显示为普通代码
- 有行号和复制按钮
- 无法渲染为图表

#### 原因分析
- 代码块转换未执行
- 代码高亮在转换之前执行

#### 解决方案

**方案 1：检查执行顺序**

在浏览器控制台中查看日志：

```
[Mermaid] 转换了 X 个代码块  ← 应该在代码高亮之前
```

如果没有看到这条日志，说明转换函数未执行。

**方案 2：手动转换**

在浏览器控制台中运行：

```javascript
// 手动执行转换
if (typeof convertMermaidCodeblocks === 'function') {
    convertMermaidCodeblocks();
    console.log('[调试] 手动转换完成');
}
```

**方案 3：使用其他标记方式**

如果代码块转换不生效，使用容器语法或 Shortcode：

```markdown
::: mermaid
flowchart TD
    A --> B
:::
```

或

```
[mermaid]
flowchart TD
    A --> B
[/mermaid]
```

## 调试工具

### 1. 启用调试模式

在浏览器控制台中运行：

```javascript
// 启用 Mermaid 调试
localStorage.setItem('argon_mermaid_debug', 'true');
location.reload();
```

### 2. 查看转换结果

在浏览器控制台中运行：

```javascript
// 查看所有转换后的容器
document.querySelectorAll('.mermaid-from-codeblock').forEach((el, i) => {
    console.log(`容器 ${i + 1}:`, el);
    console.log(`代码内容:`, el.textContent);
});
```

### 3. 查看 Mermaid 配置

在浏览器控制台中运行：

```javascript
// 查看 Mermaid 配置
console.log('Mermaid 版本:', mermaid.version);
console.log('Mermaid 配置:', mermaid.getConfig());
console.log('主题配置:', window.argonMermaidConfig);
```

### 4. 测试代码语法

访问 [Mermaid Live Editor](https://mermaid.live/) 测试你的 Mermaid 代码是否正确。

### 5. 检查网络请求

在浏览器开发者工具的 Network 标签中：
1. 刷新页面
2. 搜索 `mermaid`
3. 检查 Mermaid 库是否成功加载
4. 查看 HTTP 状态码（应该是 200）

## 常见问题 FAQ

### Q: 为什么有些图表能渲染，有些不能？

A: 可能原因：
1. 代码语法错误（使用 Mermaid Live Editor 验证）
2. 代码块格式不一致（检查缩进和空格）
3. 特殊字符被转义（使用容器语法或 Shortcode）

### Q: 如何查看详细的错误信息？

A: 打开浏览器控制台（F12），查看 Console 标签页，搜索 `[Mermaid]` 或 `[Argon Mermaid]`。

### Q: 代码块魔改功能如何工作？

A: 
1. 页面加载时，`convertMermaidCodeblocks()` 函数在代码高亮之前执行
2. 查找所有 `<pre><code class="language-mermaid">` 元素
3. 提取纯文本代码并清理缩进
4. 创建 `<div class="mermaid-from-codeblock">` 容器
5. 替换原始代码块元素
6. Mermaid 渲染引擎检测并渲染容器

### Q: 如何禁用代码块魔改功能？

A: 如果代码块魔改导致问题，可以使用容器语法或 Shortcode 代替。

### Q: 支持哪些 Mermaid 图表类型？

A: 支持所有 Mermaid 官方图表类型：
- flowchart / graph（流程图）
- sequenceDiagram（时序图）
- classDiagram（类图）
- stateDiagram（状态图）
- erDiagram（实体关系图）
- gantt（甘特图）
- pie（饼图）
- journey（用户旅程图）
- gitGraph（Git 图）

## 获取帮助

如果以上方法都无法解决问题：

1. **收集信息**：
   - 浏览器类型和版本
   - WordPress 版本
   - Argon 主题版本
   - 使用的插件列表
   - 完整的错误信息（控制台截图）
   - Mermaid 代码示例

2. **检查文档**：
   - [用户指南](mermaid-usage-guide.md)
   - [开发者指南](mermaid-developer-guide.md)
   - [FAQ](mermaid-faq.md)

3. **联系支持**：
   - GitHub Issues
   - 主题论坛
   - 技术支持邮箱

## 更新日志

### 2026-01-24
- ✅ 添加智能缩进清理功能
- ✅ 添加 Mermaid API 版本检测
- ✅ 支持 Mermaid 8.x 和 10.x
- ✅ 增强错误处理和调试日志
- ✅ 修复代码提取时的空格问题
