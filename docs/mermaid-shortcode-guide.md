# Mermaid Shortcode 使用指南

## 为什么使用 Shortcode？

在 WP-Markdown 环境下，使用 Shortcode 是最可靠的 Mermaid 图表标记方式：

### 优点 ✅

1. **不依赖 WP-Markdown 的处理方式**
   - 不会被 WordPress 自动格式化破坏
   - 不会将 `-->` 转换为 `–>`
   - 不会丢失换行符

2. **在原生编辑器中清晰可见**
   - 经典编辑器：文本模式下直接可见
   - Gutenberg 编辑器：使用"短代码"块
   - 易于编辑和维护

3. **支持参数配置**
   - 可以设置主题（theme）
   - 可以设置宽度（width）
   - 可以设置高度（height）
   - 可以设置对齐方式（align）

4. **完全兼容**
   - 与其他 Shortcode 一样使用
   - 不需要额外插件
   - 不需要修改 WP-Markdown

### 对比其他方式

| 方式 | 优点 | 缺点 |
|------|------|------|
| **Shortcode** ⭐ | 可靠、易用、支持参数 | 需要记住语法 |
| 容器语法 `::: mermaid` | 符合 Markdown 规范 | 被 WP 格式化破坏 |
| 代码块 ` ```mermaid ` | 通用 | 被代码高亮干扰 |
| HTML `<div class="mermaid">` | 灵活 | 编辑不便 |

---

## 基本用法

### 语法

```
[mermaid]
flowchart TD
    A[开始] --> B[处理]
    B --> C[结束]
[/mermaid]
```

### 在经典编辑器中使用

1. 切换到"文本"模式（不是"可视化"模式）
2. 输入 Shortcode：
   ```
   [mermaid]
   你的 Mermaid 代码
   [/mermaid]
   ```
3. 保存并预览

### 在 Gutenberg 编辑器中使用

1. 添加"短代码"块（Shortcode Block）
2. 输入 Shortcode：
   ```
   [mermaid]
   你的 Mermaid 代码
   [/mermaid]
   ```
3. 保存并预览

---

## 参数说明

### theme - 主题

设置图表主题，支持以下值：
- `default` - 默认主题（浅色）
- `dark` - 深色主题
- `forest` - 森林主题
- `neutral` - 中性主题

**示例**：
```
[mermaid theme="dark"]
flowchart TD
    A --> B
[/mermaid]
```

### width - 宽度

设置图表容器宽度，支持：
- 百分比：`100%`, `80%`, `50%`
- 像素值：`800px`, `600px`
- 自动：`auto`

**示例**：
```
[mermaid width="80%"]
flowchart TD
    A --> B
[/mermaid]
```

### height - 高度

设置图表容器高度，支持：
- 像素值：`600px`, `400px`
- 自动：`auto`（默认）

**示例**：
```
[mermaid height="500px"]
flowchart TD
    A --> B
[/mermaid]
```

### align - 对齐方式

设置图表对齐方式，支持：
- `left` - 左对齐
- `center` - 居中（默认）
- `right` - 右对齐

**示例**：
```
[mermaid align="left"]
flowchart TD
    A --> B
[/mermaid]
```

### 组合使用

```
[mermaid theme="dark" width="80%" height="500px" align="center"]
flowchart TD
    A[开始] --> B[处理]
    B --> C[结束]
[/mermaid]
```

---

## 使用示例

### 示例 1: 简单流程图

```
[mermaid]
flowchart TD
    Start([开始]) --> Process[处理数据]
    Process --> Decision{是否成功?}
    Decision -->|是| Success[显示成功]
    Decision -->|否| Error[显示错误]
    Success --> End([结束])
    Error --> End
[/mermaid]
```

### 示例 2: 时序图

```
[mermaid]
sequenceDiagram
    participant User as 用户
    participant Server as 服务器
    participant DB as 数据库
    
    User->>Server: 发送请求
    Server->>DB: 查询数据
    DB-->>Server: 返回数据
    Server-->>User: 返回响应
[/mermaid]
```

### 示例 3: 类图

```
[mermaid]
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
    class Cat {
        +String color
        +meow()
    }
    Animal <|-- Dog
    Animal <|-- Cat
[/mermaid]
```

### 示例 4: 甘特图

```
[mermaid]
gantt
    title 项目进度
    dateFormat YYYY-MM-DD
    section 设计
    需求分析 :a1, 2024-01-01, 7d
    UI设计 :a2, after a1, 5d
    section 开发
    前端开发 :b1, after a2, 10d
    后端开发 :b2, after a2, 12d
    section 测试
    功能测试 :c1, after b1, 5d
    性能测试 :c2, after b2, 3d
[/mermaid]
```

### 示例 5: 状态图

```
[mermaid]
stateDiagram-v2
    [*] --> 待审核
    待审核 --> 已通过: 审核通过
    待审核 --> 已拒绝: 审核拒绝
    已通过 --> [*]
    已拒绝 --> [*]
[/mermaid]
```

### 示例 6: 饼图

```
[mermaid]
pie title 编程语言使用占比
    "JavaScript" : 386
    "Python" : 285
    "Java" : 215
    "C++" : 115
    "其他" : 85
[/mermaid]
```

### 示例 7: ER 图

```
[mermaid]
erDiagram
    USER ||--o{ ORDER : places
    ORDER ||--|{ ORDER_ITEM : contains
    PRODUCT ||--o{ ORDER_ITEM : "ordered in"
    
    USER {
        int id PK
        string name
        string email
    }
    ORDER {
        int id PK
        int user_id FK
        date created_at
    }
    PRODUCT {
        int id PK
        string name
        decimal price
    }
[/mermaid]
```

### 示例 8: 带样式的流程图

```
[mermaid]
flowchart TD
    Start([用户提交评论]) --> PreProcess[预处理]
    PreProcess --> CheckEnabled{启用 AI 检测?}
    CheckEnabled -->|是| AICheck[AI 检测]
    CheckEnabled -->|否| Save[保存评论]
    AICheck --> Result{检测结果?}
    Result -->|垃圾评论| Trash[移入回收站]
    Result -->|正常评论| Save
    
    style Start fill:#e1f5e1,stroke:#2e7d32,stroke-width:2px
    style Trash fill:#ff6b6b,stroke:#c62828,stroke-width:2px
    style Save fill:#95e1d3,stroke:#2e7d32,stroke-width:2px
[/mermaid]
```

---

## 常见问题

### 1. Shortcode 不生效怎么办？

**可能原因**：
- 主题未更新到最新版本
- 使用了"可视化"模式编辑

**解决方案**：
1. 更新 Argon 主题到最新版本
2. 切换到"文本"模式编辑
3. 检查 Shortcode 语法是否正确

### 2. 图表渲染失败怎么办？

**排查步骤**：

1. **检查 Mermaid 语法**
   - 访问 [Mermaid Live Editor](https://mermaid.live/)
   - 粘贴你的代码验证语法

2. **查看浏览器控制台**
   - 按 F12 打开开发者工具
   - 查看 Console 标签页
   - 搜索错误信息

3. **检查主题设置**
   - WordPress 后台 → 外观 → Argon 主题选项
   - 找到"Mermaid 图表"分类
   - 确认"启用 Mermaid 支持"已开启

### 3. 如何迁移现有的容器语法？

如果你之前使用了 `::: mermaid ... :::` 语法，可以批量替换：

**查找**：
```
::: mermaid
```

**替换为**：
```
[mermaid]
```

**查找**：
```
:::
```

**替换为**：
```
[/mermaid]
```

### 4. 可以在评论中使用吗？

不可以。Shortcode 只能在文章和页面中使用，评论中不支持。

### 5. 可以嵌套使用吗？

不可以。Shortcode 不支持嵌套，每个图表需要独立的 `[mermaid]...[/mermaid]` 标签。

---

## 最佳实践

### 1. 使用有意义的节点 ID

**推荐**：
```
[mermaid]
flowchart TD
    UserSubmit([用户提交]) --> Validate[验证数据]
    Validate --> Save[保存数据]
[/mermaid]
```

**不推荐**：
```
[mermaid]
flowchart TD
    A --> B
    B --> C
[/mermaid]
```

### 2. 添加适当的注释

```
[mermaid]
flowchart TD
    %% 用户流程
    Start([开始]) --> Login[登录]
    
    %% 验证流程
    Login --> Check{验证成功?}
    Check -->|是| Dashboard[进入控制台]
    Check -->|否| Error[显示错误]
[/mermaid]
```

### 3. 使用样式定义

```
[mermaid]
flowchart TD
    Success[成功] --> End
    Error[错误] --> End
    
    style Success fill:#95e1d3,stroke:#2e7d32
    style Error fill:#ff6b6b,stroke:#c62828
[/mermaid]
```

### 4. 保持图表简洁

- 避免过多的节点（建议 < 20 个）
- 使用子图（subgraph）组织复杂流程
- 考虑拆分为多个图表

### 5. 测试后再发布

1. 先在 [Mermaid Live Editor](https://mermaid.live/) 中测试
2. 确认语法正确后再粘贴到文章中
3. 使用"预览"功能查看效果
4. 确认无误后再发布

---

## 技术细节

### Shortcode 实现

Argon 主题在 `functions.php` 中注册了 `mermaid` shortcode：

```php
add_shortcode('mermaid','shortcode_mermaid');
function shortcode_mermaid($attr,$content=""){
	// 预处理内容
	$content = shortcode_content_preprocess($attr, $content);
	
	// 获取参数
	$theme = isset( $attr['theme'] ) ? $attr['theme'] : 'default';
	$width = isset( $attr['width'] ) ? $attr['width'] : '100%';
	$height = isset( $attr['height'] ) ? $attr['height'] : 'auto';
	$align = isset( $attr['align'] ) ? $attr['align'] : 'center';
	
	// 生成 HTML
	$out = '<div class="mermaid-shortcode-container">';
	$out .= '<div class="mermaid-shortcode" ...>';
	$out .= esc_html($content);
	$out .= '</div></div>';
	
	return $out;
}
```

### JavaScript 检测

在 `argontheme.js` 中，Mermaid 渲染器会自动检测 `div.mermaid-shortcode` 元素：

```javascript
const selectors = [
	'div.mermaid-shortcode',  // Shortcode 格式（推荐）
	'div.mermaid',            // 标准格式
	// ...
];
```

### 安全性

- 使用 `esc_html()` 转义输出，防止 XSS 攻击
- 使用 `esc_attr()` 转义属性值
- 不执行任何用户提供的 JavaScript 代码

---

## 相关资源

- [Mermaid 官方文档](https://mermaid.js.org/)
- [Mermaid Live Editor](https://mermaid.live/)
- [WordPress Shortcode API](https://developer.wordpress.org/plugins/shortcodes/)
- [Argon 主题文档](https://argon-docs.solstice23.top/)

---

## 更新日志

### 2026-01-24
- ✅ 添加 Mermaid Shortcode 支持
- ✅ 支持 theme、width、height、align 参数
- ✅ 自动检测和渲染
- ✅ 完整的使用文档和示例

---

## 总结

使用 Shortcode 是在 WP-Markdown 环境下最可靠的 Mermaid 图表标记方式：

1. **简单易用**：`[mermaid]...[/mermaid]`
2. **功能强大**：支持主题、尺寸、对齐等参数
3. **完全兼容**：不需要额外插件或修改
4. **易于维护**：在编辑器中清晰可见

推荐所有用户使用 Shortcode 方式编写 Mermaid 图表！
