# Argon 主题 Mermaid 图表使用指南

## 推荐的标记方式

### Markdown 容器语法（推荐）⭐

```markdown
::: mermaid
flowchart TD
    A[开始] --> B[处理]
    B --> C[结束]
:::
```

**优点**：
- ✅ 符合 Markdown 扩展规范（VuePress、Docusaurus 等使用相同语法）
- ✅ 不会被 WP-Markdown 当作代码块处理，避免嵌套问题
- ✅ 语法简洁，易于编写和阅读
- ✅ 支持所有 Mermaid 图表类型
- ✅ 在纯文本编辑器中也很清晰
- ✅ 易于迁移到其他平台

### 为什么使用容器语法？

**Markdown 容器语法** (`::: mermaid ... :::`) 是现代 Markdown 扩展的标准方式：

- **VuePress** 使用这种语法
- **Docusaurus** 使用这种语法
- **MkDocs** 使用类似语法
- **符合 CommonMark 扩展规范**

这种语法不会被 WP-Markdown 插件当作代码块处理，因此：
- 不会被套上代码高亮窗口
- 不会有嵌套结构问题
- 不会有字符转义问题

---

## 使用示例

### 流程图

```markdown
::: mermaid
flowchart LR
    A[用户] --> B{登录?}
    B -->|是| C[显示首页]
    B -->|否| D[跳转登录页]
:::
```

### 时序图

```markdown
::: mermaid
sequenceDiagram
    Alice->>Bob: Hello Bob!
    Bob-->>Alice: Hi Alice!
    Alice->>Bob: How are you?
:::
```

### 类图

```markdown
::: mermaid
classDiagram
    class Animal {
        +String name
        +int age
        +makeSound()
    }
    class Dog {
        +String breed
        +bark()
    }
    Animal <|-- Dog
:::
```

### 甘特图

```markdown
::: mermaid
gantt
    title 项目进度
    dateFormat YYYY-MM-DD
    section 设计
    需求分析 :a1, 2024-01-01, 7d
    UI设计 :a2, after a1, 5d
    section 开发
    前端开发 :b1, after a2, 10d
    后端开发 :b2, after a2, 12d
:::
```

### 状态图

```markdown
::: mermaid
stateDiagram-v2
    [*] --> 待审核
    待审核 --> 已通过: 审核通过
    待审核 --> 已拒绝: 审核拒绝
    已通过 --> [*]
    已拒绝 --> [*]
:::
```

### 饼图

```markdown
::: mermaid
pie title 宠物分布
    "狗" : 386
    "猫" : 85
    "兔子" : 15
:::
```

---

## 常见问题

### 1. 如何在 WordPress 编辑器中使用？

**在经典编辑器中：**
1. 切换到"文本"模式（不是"可视化"模式）
2. 直接输入容器语法
3. 保存并预览

**在 Gutenberg 编辑器中：**
1. 添加"自定义 HTML"块或"代码"块
2. 输入容器语法
3. 保存并预览

### 2. 已有文章如何迁移？

如果你的文章使用了传统的 Markdown 代码块，可以批量替换：

**查找：**
```
三个反引号mermaid
```

**替换为：**
```
::: mermaid
```

**查找：**
```
三个反引号（代码块结束）
```

**替换为：**
```
:::
```

### 3. 容器语法不生效怎么办？

**可能的原因：**
- 其他插件或主题处理了容器语法
- WP-Markdown 插件版本过旧

**解决方案：**
1. 检查是否有其他 Markdown 插件冲突
2. 更新 WP-Markdown 插件到最新版本
3. 查看浏览器控制台的错误信息

### 4. 渲染失败怎么办？

**排查步骤：**

1. **检查语法**
   - 访问 [Mermaid Live Editor](https://mermaid.live/) 验证语法
   - 确保图表类型正确

2. **查看控制台**
   - 按 F12 打开开发者工具
   - 查看 Console 标签页
   - 搜索 `[Argon Mermaid]` 日志

3. **检查主题设置**
   - WordPress 后台 → 外观 → Argon 主题选项
   - 找到"Mermaid 图表"分类
   - 确认"启用 Mermaid 支持"已开启

4. **测试 CDN 连接**
   - 访问测试页面验证 CDN 是否可用
   - 如果 CDN 失败，尝试切换到其他 CDN

---

## 最佳实践

### 1. 使用容器语法

**推荐：**
```markdown
::: mermaid
flowchart TD
    A --> B
:::
```

**不推荐：**
```
三个反引号mermaid
flowchart TD
    A --> B
三个反引号
```

### 2. 避免特殊字符

如果图表中包含特殊字符，使用引号包裹：

```markdown
::: mermaid
flowchart TD
    A["包含 <特殊> 字符"] --> B["使用引号包裹"]
:::
```

### 3. 测试复杂图表

对于复杂的图表：
1. 先在 [Mermaid Live Editor](https://mermaid.live/) 中测试
2. 确认语法正确后再粘贴到文章中
3. 发布前预览文章

### 4. 启用调试模式

如果遇到问题：
1. 主题设置 → Mermaid 图表 → 基本设置
2. 开启"调试模式"
3. 刷新页面查看控制台日志

### 5. 保持代码简洁

- 使用有意义的节点 ID
- 添加适当的注释
- 保持图表结构清晰

---

## 技术细节

### 支持的标记格式

Argon 主题支持以下格式（按优先级排序）：

1. `::: mermaid ... :::` - Markdown 容器语法 ⭐（推荐）
2. `<div class="mermaid">` - 标准格式（WPMD 生成）
3. `<pre><code class="language-mermaid">` - Markdown 格式
4. `<pre data-lang="mermaid">` - 自定义属性格式
5. `<code class="mermaid">` - 简化格式

### 代码提取逻辑

1. **检测代码块**
   - CSS 选择器查找标准格式
   - TreeWalker 查找容器语法

2. **提取代码**
   - 容器语法：移除 `::: mermaid` 和 `:::`
   - WPMD 格式：正则提取 `document.write()` 内容
   - 标准格式：直接提取文本内容

3. **解码处理**
   - 先解码 HTML 实体（`&lt;` → `<`）
   - 再解码转义字符（`\n` → 换行符）

4. **渲染图表**
   - 使用 Mermaid.js 库渲染为 SVG
   - 应用主题样式（夜间模式适配）
   - 错误时显示友好提示

---

## 故障排除

### 问题：容器语法被显示为普通文本

**症状**：`::: mermaid` 被显示在页面上

**原因**：可能被其他插件或主题处理为普通文本

**解决**：
- 检查是否有其他 Markdown 插件冲突
- 确认 Argon 主题已更新到最新版本
- 查看浏览器控制台是否有 JavaScript 错误

### 问题：只显示第一个单词

**症状**：图表渲染失败，错误信息显示 `"text": "flowchart"`

**原因**：代码提取不完整

**解决**：
- 确保使用最新版本的 Argon 主题
- 使用容器语法而不是传统代码块
- 查看控制台日志确认提取到的完整代码

### 问题：HTML 实体未解码

**症状**：图表中显示 `&lt;` 而不是 `<`

**原因**：HTML 实体解码失败

**解决**：
- Argon 主题会自动解码 HTML 实体
- 使用容器语法可避免此问题
- 在 Mermaid 代码中使用引号包裹特殊字符

---

## 相关资源

- [Mermaid 官方文档](https://mermaid.js.org/)
- [Mermaid Live Editor](https://mermaid.live/)
- [VuePress 容器语法](https://vuepress.vuejs.org/guide/markdown.html#custom-containers)
- [CommonMark 规范](https://commonmark.org/)

---

## 更新日志

### 2026-01-24
- ✅ 添加 Markdown 容器语法支持（`::: mermaid ... :::`）
- ✅ 修复 WP-Markdown 格式的代码提取问题
- ✅ 改进正则表达式，支持多行代码
- ✅ 添加降级方案和详细日志
- ✅ 简化标记方式，只保留容器语法作为推荐方式
- ✅ 修复代码高亮干扰 mermaid 渲染的问题（排除 mermaid 代码块）
- ✅ 修复容器语法中空行导致内容被截断的问题
- ✅ 修复 WP-Markdown 的 document.write 重复输出问题
- ✅ 改进容器语法检测，支持跨多个元素的内容收集
