# 安全性、辅助功能、兼容性和性能修复计划

## 1. CSS 兼容性修复

### 需要修复的属性顺序问题
- `backdrop-filter` 应该在 `-webkit-backdrop-filter` 之后
- `user-select` 应该在 `-ms-user-select` 之后
- `width: max-content` 应该在 `width: -moz-max-content` 之后
- `height: max-content` 应该在 `height: -moz-max-content` 之后

### 需要添加的浏览器前缀
- `text-size-adjust` 需要添加 `-webkit-` 前缀
- 所有 `user-select` 需要完整的前缀支持

## 2. 安全性修复（需要后端配置）

### HTTP 头部修复
这些需要在服务器配置或 PHP 中修复：

```php
// functions.php 中添加
function argon_security_headers() {
	// 移除 Pragma 头（已废弃）
	header_remove('Pragma');
	
	// 简化 Server 头
	header('Server: Argon');
	
	// 使用 CSP 替代 X-Frame-Options
	header("Content-Security-Policy: frame-ancestors 'self'");
	header_remove('X-Frame-Options');
	
	// 设置正确的字符集
	header('Content-Type: text/html; charset=utf-8');
	
	// 缓存控制
	if (!is_admin()) {
		header('Cache-Control: public, max-age=31536000, immutable');
	}
}
add_action('send_headers', 'argon_security_headers');
```

## 3. 辅助功能修复

### 需要修复的 HTML 结构
1. 按钮缺少可识别文本 - 添加 `aria-label` 或 `title`
2. ARIA 角色层级问题 - 修复 tab/tablist 结构
3. 表单元素缺少标签 - 添加 `<label>` 或 `aria-label`
4. 图片缺少 alt 文本 - 添加 `alt` 属性
5. `<li>` 元素必须在 `<ul>` 或 `<ol>` 中
6. 交互控件嵌套问题 - 重构 HTML 结构
7. 链接缺少可识别文本 - 添加文本或 `aria-label`

### 需要添加的属性
- `autocomplete` 属性用于表单字段
- `title` 属性用于按钮和图片
- `aria-label` 用于无文本的交互元素

## 4. 性能优化

### 静态资源缓存
需要在 `.htaccess` 或 Nginx 配置中添加：

```apache
# Apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
  <FilesMatch "\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>
</IfModule>
```

### CSS 动画性能
- 避免在 @keyframes 中使用 `left` 属性
- 使用 `transform: translateX()` 替代

## 5. 其他修复

### Meta 标签
- 移除 `viewport` 中的 `maximum-scale`
- 添加 Firefox 不支持的功能的降级方案

## 实施优先级

### 高优先级（影响安全和可访问性）
1. 修复 CSP 头部（替代 X-Frame-Options）
2. 修复表单标签和 ARIA 属性
3. 修复按钮和链接的可识别文本

### 中优先级（影响兼容性）
1. 修复 CSS 属性顺序
2. 添加浏览器前缀
3. 修复动画性能问题

### 低优先级（优化建议）
1. 静态资源缓存配置
2. Meta 标签优化
3. 移除不必要的 HTTP 头部

## 注意事项

- CSS 修复可以直接在 style.css 中完成
- HTTP 头部需要在 functions.php 中配置
- 服务器配置需要访问 .htaccess 或 Nginx 配置
- HTML 结构修复需要修改模板文件
