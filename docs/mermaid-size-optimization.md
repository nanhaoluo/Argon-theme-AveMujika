# Mermaid 图表尺寸优化

## 问题描述

在之前的实现中，Mermaid 图表容器没有对 SVG 的高度进行限制，导致：

1. **低内容图表显示过大**：只有 2-3 个节点的简单流程图可能被渲染得非常大，占据整个屏幕
2. **用户体验不佳**：需要大量滚动才能看到后续内容
3. **视觉不协调**：简单图表与复杂图表的尺寸差异过大

## 优化方案

### 1. 最大高度限制

为 SVG 元素设置最大高度，避免图表显示过大：

```css
.mermaid-container svg {
	max-height: 600px;  /* 桌面端 */
}
```

### 2. 居中显示

使用 Flexbox 让图表在容器中居中显示：

```css
.mermaid-container {
	display: flex;
	justify-content: center;
	align-items: center;
	min-height: 100px;
}
```

### 3. 宽度自适应

使用 `width: auto !important` 让图表保持原始宽高比：

```css
.mermaid-container svg {
	max-width: 100%;
	width: auto !important;
	height: auto;
}
```

### 4. 响应式适配

针对不同屏幕尺寸设置不同的最大高度：

```css
/* 桌面端 */
.mermaid-container svg {
	max-height: 600px;
}

/* 平板（<768px） */
@media screen and (max-width: 768px) {
	.mermaid-container svg {
		max-height: 500px;
	}
}

/* 手机（<480px） */
@media screen and (max-width: 480px) {
	.mermaid-container svg {
		max-height: 400px;
	}
}
```

## 优化效果

### 优化前

- ❌ 简单图表（2-3 节点）可能显示超大，占据整个屏幕
- ❌ 图表尺寸不可控，用户体验差
- ❌ 需要大量滚动才能看到后续内容

### 优化后

- ✅ 简单图表显示合理大小，不会过大
- ✅ 图表在容器中居中显示，视觉协调
- ✅ 复杂图表高度限制在 600px，出现滚动条
- ✅ 响应式适配，移动端体验更好

## 测试方法

使用测试文件验证优化效果：

```bash
# 在浏览器中打开测试文件
tests/test-mermaid-size-optimization.html
```

### 测试检查清单

- [ ] 极简图表（2-3 节点）不会显示过大
- [ ] 图表在容器中居中显示
- [ ] 中等复杂度图表显示正常
- [ ] 复杂图表高度限制在 600px
- [ ] 横向图表宽度自适应
- [ ] 夜间模式下图表显示正常
- [ ] 移动端响应式适配正常

## 技术细节

### CSS 关键属性

```css
.mermaid-container {
	/* 使用 Flexbox 居中 */
	display: flex;
	justify-content: center;
	align-items: center;
	
	/* 最小高度，避免空白过小 */
	min-height: 100px;
	
	/* 其他样式 */
	background: var(--card-background);
	border-radius: var(--card-radius);
	padding: 20px;
	margin: 20px 0;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	overflow-x: auto;
	max-width: 100%;
	transition: opacity 0.3s ease-in;
}

.mermaid-container svg {
	/* 宽度限制 */
	max-width: 100%;
	width: auto !important;
	
	/* 高度限制 */
	max-height: 600px;
	height: auto;
	
	/* 居中显示 */
	display: block;
	margin: 0 auto;
}
```

### 为什么使用 `width: auto !important`

Mermaid 会自动设置 SVG 的 `width` 属性，可能导致图表过大。使用 `!important` 强制覆盖，让浏览器根据 `max-width` 和 `max-height` 自动计算宽度，保持原始宽高比。

### 为什么使用 Flexbox

使用 Flexbox 可以轻松实现图表在容器中的居中显示，无论图表大小如何变化，都能保持居中对齐。

## 兼容性

- ✅ 现代浏览器（Chrome, Firefox, Safari, Edge）
- ✅ 移动端浏览器
- ✅ 夜间模式
- ✅ 响应式布局

## 注意事项

1. **滚动条**：当图表高度超过最大高度时，容器会出现垂直滚动条
2. **宽高比**：图表会保持原始宽高比，不会变形
3. **性能**：优化不会影响 Mermaid 的渲染性能
4. **兼容性**：与现有的 Mermaid 功能完全兼容

## 相关文件

- `style.css` - Mermaid 容器样式定义
- `tests/test-mermaid-size-optimization.html` - 尺寸优化测试文件
- `docs/mermaid-usage-guide.md` - Mermaid 使用指南
- `docs/mermaid-troubleshooting.md` - Mermaid 故障排除

## 更新日志

### 2026-01-24

- ✅ 添加 SVG 最大高度限制（600px）
- ✅ 使用 Flexbox 实现图表居中
- ✅ 添加响应式适配（平板 500px，手机 400px）
- ✅ 添加最小高度限制（100px）
- ✅ 创建尺寸优化测试文件
