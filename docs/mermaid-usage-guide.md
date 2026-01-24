# Argon 主题 Mermaid 图表使用指南

## 推荐的标记方式

### 1. 标准 Markdown 代码块（推荐）⭐

```markdown
三个反引号mermaid
flowchart TD
    A[开始] --> B[处理]
    B --> C[结束]
三个反引号
```

**优点**：
- ✅ 符合标准 Markdown 语法
- ✅ 在所有 Markdown 编辑器中都能正确显示
- ✅ 支持语法高亮（编辑器层面）
- ✅ 易于迁移到其他平台（GitHub、GitLab、Typora 等）
- ✅ 主题自动拦截处理，避免代码高亮干扰
- ✅ 支持所有 Mermaid 图表类型

**工作原理**：
- 主题在代码高亮之前拦截 mermaid 代码块
- 自动转换为 Mermaid 渲染容器
- 完全绕过代码高亮和 WordPress 格式化

### 2. Markdown 容器语法（备选）

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

### 3. Shortcode 格式（兼容）

```
[mermaid]
flowchart TD
    A[开始] --> B[处理]
    B --> C[结束]
[/mermaid]
```

**优点**：
- ✅ WordPress 原生支持
- ✅ 不会被任何插件干扰
- ✅ 兼容性最好

**缺点**：
- ❌ 不符合 Markdown 标准
- ❌ 在其他平台无法使用
- ❌ 编辑器中不显示为代码块

### 为什么推荐标准 Markdown 代码块？

**标准 Markdown 代码块** (` ```mermaid `) 是最通用的方式：

- **GitHub** 使用这种语法
- **GitLab** 使用这种语法
- **Typora** 使用这种语法
- **VS Code** 使用这种语法
- **符合 CommonMark 规范**

Argon 主题通过**代码块魔改**技术，在代码高亮之前拦截并转换 mermaid 代码块，因此：
- 不会被代码高亮处理（无行号、无控制按钮）
- 不会有字符转义问题（`-->` 保持不变）
- 不会有嵌套结构问题
- 完全符合标准 Markdown 语法

---

## 使用示例

### 流程图

**标准 Markdown 代码块：**
```markdown
三个反引号mermaid
flowchart LR
    A[用户] --> B{登录?}
    B -->|是| C[显示首页]
    B -->|否| D[跳转登录页]
三个反引号
```

**容器语法：**
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
三个反引号mermaid
sequenceDiagram
    Alice->>Bob: Hello Bob!
    Bob-->>Alice: Hi Alice!
    Alice->>Bob: How are you?
三个反引号
```

### 类图

```markdown
三个反引号mermaid
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
三个反引号
```

### 甘特图

```markdown
三个反引号mermaid
gantt
    title 项目进度
    dateFormat YYYY-MM-DD
    section 设计
    需求分析 :a1, 2024-01-01, 7d
    UI设计 :a2, after a1, 5d
    section 开发
    前端开发 :b1, after a2, 10d
    后端开发 :b2, after a2, 12d
三个反引号
```

### 状态图

```markdown
三个反引号mermaid
stateDiagram-v2
    [*] --> 待审核
    待审核 --> 已通过: 审核通过
    待审核 --> 已拒绝: 审核拒绝
    已通过 --> [*]
    已拒绝 --> [*]
三个反引号
```

### 饼图

```markdown
三个反引号mermaid
pie title 宠物分布
    "狗" : 386
    "猫" : 85
    "兔子" : 15
三个反引号
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

### 1. 使用标准 Markdown 代码块

**推荐：**
```markdown
三个反引号mermaid
flowchart TD
    A --> B
三个反引号
```

**也可以使用容器语法：**
```markdown
::: mermaid
flowchart TD
    A --> B
:::
```

**不推荐（Shortcode）：**
```
[mermaid]
flowchart TD
    A --> B
[/mermaid]
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

1. ` ```mermaid ... ``` ` - 标准 Markdown 代码块 ⭐（推荐）
2. `::: mermaid ... :::` - Markdown 容器语法（备选）
3. `[mermaid]...[/mermaid]` - Shortcode 格式（兼容）
4. `<div class="mermaid">` - 标准格式（WPMD 生成）
5. `<pre><code class="language-mermaid">` - Markdown 格式（降级）
6. `<pre data-lang="mermaid">` - 自定义属性格式
7. `<code class="mermaid">` - 简化格式

### 代码块魔改技术

**工作原理**：

1. **拦截阶段**（在代码高亮之前）
   - 查找所有 `<pre><code class="language-mermaid">` 元素
   - 提取纯文本代码（使用 `textContent`）
   - 创建 `<div class="mermaid-from-codeblock">` 容器
   - 替换原始代码块元素

2. **代码高亮阶段**
   - 处理其他代码块
   - 跳过 mermaid 相关的元素

3. **Mermaid 渲染阶段**
   - 检测所有 Mermaid 容器（包括新的 `mermaid-from-codeblock`）
   - 提取代码并渲染为 SVG 图表

**优势**：
- ✅ 完全绕过代码高亮干扰
- ✅ 特殊字符不被转换（`-->` 保持不变）
- ✅ 换行符正确保留
- ✅ 支持 PJAX 页面切换
- ✅ 性能无明显影响

### 代码提取逻辑

1. **检测代码块**
   - CSS 选择器查找标准格式
   - TreeWalker 查找容器语法
   - 代码块魔改：在代码高亮前拦截

2. **提取代码**
   - 代码块魔改格式：直接提取 `textContent`
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
- ✅ **新增代码块魔改支持**：支持标准 Markdown 代码块 (` ```mermaid `)
- ✅ 在代码高亮之前拦截并转换 mermaid 代码块
- ✅ 完全绕过代码高亮和 WordPress 格式化
- ✅ 特殊字符不被转换（`-->` 保持不变）
- ✅ 换行符正确保留
- ✅ 支持 PJAX 页面切换
- ✅ 添加 Markdown 容器语法支持（`::: mermaid ... :::`）
- ✅ 修复 WP-Markdown 格式的代码提取问题
- ✅ 改进正则表达式，支持多行代码
- ✅ 添加降级方案和详细日志
- ✅ 修复代码高亮干扰 mermaid 渲染的问题（排除 mermaid 代码块）
- ✅ 修复容器语法中空行导致内容被截断的问题
- ✅ 修复 WP-Markdown 的 document.write 重复输出问题
- ✅ 改进容器语法检测，支持跨多个元素的内容收集
- ✅ 修复换行符丢失问题（将 `<br>` 标签正确转换为换行符）
