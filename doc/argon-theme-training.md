# Argon 主题开发文档

## 项目概述

Argon 是一款基于 WordPress 的现代化博客主题，采用 Material Design 设计语言，提供丰富的功能和高度的可定制性。

### 核心特性

- Material Design 风格界面
- 响应式布局，支持多种页面布局模式（单栏/双栏/三栏）
- 夜间模式与 AMOLED 暗黑模式
- 沉浸式主题色系统
- PJAX 无刷新页面加载
- 瀑布流文章列表布局
- 完整的评论系统（支持 Markdown、表情、点赞、置顶）
- 说说功能（类似微博的短内容发布）
- AI 摘要生成
- 代码高亮与数学公式渲染
- 友链管理系统
- 反馈系统
- 性能优化模块

### 技术栈

- PHP 7.0+
- WordPress 4.4+
- jQuery 3.x
- Bootstrap 4 (Argon Design System)
- Highlight.js (代码高亮)
- MathJax/KaTeX (数学公式)
- Headroom.js (顶栏自动隐藏)

## 项目文件结构

### 核心文件

```
argon/
├── style.css                    # 主题样式表 (核心样式定义，包含配色、排版、组件样式)
├── argontheme.js               # 主题核心 JavaScript (负责 PJAX、界面交互、功能初始化)
├── functions.php               # WordPress 主题函数 (功能入口、Hook 挂载、核心逻辑实现)
├── settings.php                # 后台设置页面 (提供外观、功能开关等配置选项)
├── header.php                  # 页面头部模板 (HTML Head、顶部导航栏、全局变量输出)
├── footer.php                  # 页面底部模板 (页脚区域、JavaScript 脚本引入)
├── index.php                   # 首页模板 (文章列表展示)
├── single.php                  # 文章页模板 (单篇文章内容展示)
├── page.php                    # 页面模板 (独立页面展示)
├── archive.php                 # 归档页模板 (分类、标签、日期归档)
├── search.php                  # 搜索结果页模板
├── 404.php                     # 404 错误页面模板
├── comments.php                # 评论区模板 (评论列表与发表评论表单)
├── sidebar.php                 # 侧边栏模板 (个人信息卡片、小工具挂载点)
├── admin.css                   # 后台设置页面样式
└── languages/                  # 多语言包目录 (.po/.mo 文件)
```

### 功能模块文件


```
argon/
├── shuoshuo.php                # 说说页面模板
├── timeline.php                # 时间线页面模板
├── msgboard.php                # 留言板页面模板
├── friend-links.php            # 友链页面模板
├── feedback.php                # 反馈系统
├── emotions.php                # 表情系统
├── parsedown.php               # Markdown 解析器
├── useragent-parser.php        # User Agent 解析
├── ai-summary-query.php        # AI 摘要查询接口
├── unsubscribe-comment-mailnotice.php  # 评论邮件退订
└── argon-performance.js        # 性能优化模块
```

### 模板片段

```
template-parts/
├── content-single.php          # 单篇文章内容
├── content-page.php            # 页面内容
├── content-preview-1.php       # 文章预览样式 1
├── content-preview-2.php       # 文章预览样式 2
├── content-preview-3.php       # 文章预览样式 3
├── content-shuoshuo.php        # 说说内容
├── content-shuoshuo-preview.php # 说说预览
├── content-shuoshuo-details.php # 说说详情
├── content-timeline.php        # 时间线内容
├── content-none-search.php     # 搜索无结果
├── content-none-tag.php        # 标签无结果
├── ai-summary.php              # AI 摘要组件
├── emotion-keyboard.php        # 表情键盘
├── post-actions.php            # 文章操作按钮
└── shuoshuo-operations.php     # 说说操作按钮
```

### 资源文件

```
assets/
├── css/
│   ├── argon.css              # Argon Design System 样式
│   └── bootstrap/             # Bootstrap 样式
├── js/
│   ├── argon.js               # Argon Design System 脚本
│   └── easter-egg.js          # 彩蛋功能
├── img/                       # 图片资源
├── icons/                     # 图标资源
├── vendor/                    # 第三方库
├── tinymce_assets/            # TinyMCE 编辑器资源
├── argon_css_merged.css       # 合并后的 CSS
└── argon_js_merged.js         # 合并后的 JS
```

### 邮件模板

```
email-templates/
├── base.php                   # 邮件模板基类
├── comment-notify.php         # 评论通知
├── reply-notify.php           # 回复通知
├── feedback-notify.php        # 反馈通知
├── spam-notify.php            # 垃圾评论通知
├── blacklist-spam-notify.php  # 黑名单垃圾评论通知
└── username-change-notify.php # 用户名变更通知
```


## 代码规范

### CSS 规范

#### 格式化规则

- 使用 Tab 缩进（1 Tab = 4 空格宽度）
- 每个属性独占一行
- 属性之间不要有空行
- 规则块之间保留一个空行
- 选择器与 `{` 之间有一个空格
- 属性值后的 `;` 前不要有空格

#### 示例

```css
/* 正确 */
.selector {
	property: value;
	another-property: value;
}

.another-selector {
	property: value;
}

/* 错误 - 属性之间有空行 */
.selector {

	property: value;

	another-property: value;

}
```

#### 注释规范

```css
/* ==========================================================================
   大区块标题
   ========================================================================== */

/* ---------- 小区块标题 ---------- */

/* 普通注释 */
```

### JavaScript 规范

#### 格式化规则

- 使用 Tab 缩进
- 字符串优先使用单引号 `'`
- 比较运算符使用严格相等 `===` 和 `!==`
- 语句末尾必须有分号 `;`
- 函数名与括号之间无空格
- 关键字后有空格（if, for, while, function 等）

#### 变量声明

- 优先使用 `let` 和 `const`
- 避免使用 `var`（除非需要函数作用域或全局变量）

#### 全局变量（需保留 var）

以下全局变量必须使用 `var` 声明以确保跨作用域访问：

- `argonConfig` - 主题配置对象
- `translation` - 多语言翻译表
- `pjaxLoading` - PJAX 加载状态
- `headroom` - Headroom 实例

#### 注释规范

```javascript
// ==========================================================================
// 大区块标题
// ==========================================================================

// ---------- 小区块标题 ----------

/**
 * 函数说明
 * @param {string} param - 参数说明
 * @returns {boolean} 返回值说明
 */
function functionName(param) {
	// 单行注释
	if (param === 'value') {
		return true;
	}
	return false;
}
```

### PHP 规范

#### 格式化规则

- 使用 Tab 缩进
- 字符串优先使用单引号
- 数组使用短语法 `[]`
- 类名使用 PascalCase
- 函数名使用 snake_case（遵循 WordPress 规范）
- 箭头操作符 `->` 前后不要有空格

#### WordPress 特定

- 使用 `esc_html()`, `esc_attr()` 等函数转义输出
- 使用 `wp_nonce_field()` 进行安全验证
- 遵循 WordPress Coding Standards

#### 示例

```php
// 正确
$theme->Version
get_option('argon_theme_color')

// 错误
$theme -> Version
```


## 核心架构

### 主题初始化流程

主题的初始化分为服务器端（PHP）和客户端（JavaScript）两个阶段。

#### 服务器端初始化

1. **functions.php 加载**（WordPress 加载主题时执行）

```php
// functions.php
// 检查 WordPress 版本兼容性
if (version_compare($GLOBALS['wp_version'], '4.4-alpha', '<')) {
	echo "<div style='...'>Argon 主题不支持 Wordpress 4.4 以下版本</div>";
}

// 设置主题支持功能
function theme_slug_setup() {
	add_theme_support('title-tag');        // 标题标签
	add_theme_support('post-thumbnails');  // 特色图片
	load_theme_textdomain('argon', get_template_directory() . '/languages');  // 多语言
}
add_action('after_setup_theme','theme_slug_setup');

// 设置全局变量
$argon_version = !(wp_get_theme()->Template) ? wp_get_theme()->Version : wp_get_theme(wp_get_theme()->Template)->Version;
$GLOBALS['theme_version'] = $argon_version;
$GLOBALS['assets_path'] = get_bloginfo('template_url');
```

2. **导航菜单注册**
	
	主题通过 `init` 挂钩注册了四个核心菜单位置，用于定义页面的不同导航区域：

	```php
	// functions.php
	function init_nav_menus(){
		register_nav_menus( array(
			'toolbar_menu' => __('顶部导航', 'argon'),             // 顶部工具栏菜单
			'leftbar_menu' => __('左侧栏菜单', 'argon'),           // 左侧主要导航菜单
			'leftbar_author_links' => __('左侧栏作者个人链接', 'argon'), // 左侧作者信息下方的图标链接
			'leftbar_friend_links' => __('左侧栏友情链接', 'argon')  // 左侧底部的友情链接区域
		));
	}
	add_action('init', 'init_nav_menus');
	```

	3. **header.php 渲染**（输出 HTML 头部）

```php
// header.php
// 1. 生成 HTML 类名（根据后台设置）
$htmlclasses = "";
if (get_option('argon_page_layout') == "single"){
	$htmlclasses .= "single-column ";
}
if (get_option('argon_enable_immersion_color') == "true"){
	$htmlclasses .= "immersion-color ";
}
// ... 更多类名

// 2. 获取主题色配置
$themecolor = get_option("argon_theme_color", "#5e72e4");
if (isset($_COOKIE["argon_custom_theme_color"])){
	if (checkHEX($_COOKIE["argon_custom_theme_color"]) && 
	    get_option('argon_show_customize_theme_color_picker') != 'false'){
		$themecolor = $_COOKIE["argon_custom_theme_color"];
	}
}

// 3. 输出 HTML 文档头
?>
<html <?php language_attributes(); ?> class="no-js <?php echo $htmlclasses;?>">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta name="theme-color" content="<?php echo $themecolor; ?>">
	
	<!-- 预加载用户样式设置 - 避免样式跳变 -->
	<script>
	(function() {
		var html = document.documentElement;
		var ls = localStorage;
		
		// 字体设置
		if (ls.getItem('Argon_Use_Serif') === 'true') {
			html.classList.add('use-serif');
		}
		// 阴影设置
		if (ls.getItem('Argon_Use_Big_Shadow') === 'true') {
			html.classList.add('use-big-shadow');
		}
		// ... 更多设置
	})();
	</script>
	
	<?php
	// 4. 加载 CSS 和 JavaScript
	$assets_version = argon_get_assets_version();
	wp_enqueue_style("argon_css_merged", $GLOBALS['assets_path'] . "/assets/argon_css_merged.css", array(), $assets_version, 'all');
	wp_enqueue_style("style", $GLOBALS['assets_path'] . "/style.css", array('argon_css_merged'), $assets_version, 'all');
	wp_enqueue_script("argon_js_merged", $GLOBALS['assets_path'] . "/assets/argon_js_merged.js", array(), $assets_version, false);
	?>
	
	<?php wp_head(); ?>
	
	<!-- 5. 输出动态 CSS 变量 -->
	<style id="themecolor_css">
	:root{
		--themecolor: <?php echo $themecolor; ?>;
		--themecolor-R: <?php echo $RGB['R']; ?>;
		--themecolor-G: <?php echo $RGB['G']; ?>;
		--themecolor-B: <?php echo $RGB['B']; ?>;
		--themecolor-H: <?php echo $HSL['H']; ?>;
		--themecolor-S: <?php echo $HSL['S']; ?>;
		--themecolor-L: <?php echo $HSL['L']; ?>;
	}
	</style>
	
	<!-- 6. 输出全局配置对象 -->
	<script>
	document.documentElement.classList.remove("no-js");
	var argonConfig = {
		wp_path: "<?php echo $GLOBALS['wp_path']; ?>",
		language: "<?php echo argon_get_locate(); ?>",
		// ... 更多配置
	};
	</script>
	
	<!-- 7. 夜间模式初始化脚本 -->
	<script>
	var darkmodeAutoSwitch = "<?php echo get_option("argon_darkmode_autoswitch");?>";
	function setDarkmode(enable){ /* ... */ }
	function toggleDarkmode(){ /* ... */ }
	// ... 夜间模式逻辑
	</script>
</head>
```

#### 客户端初始化

1. **兼容性修复**（argontheme.js 开头）

```javascript
// argontheme.js
// 确保 Prism 存在
if (typeof window.Prism === 'undefined') {
	window.Prism = { 
		highlightAll: function() {},
		highlightElement: function() {},
		plugins: {}
	};
}

// 确保 jQuery 插件存在
if (typeof jQuery !== 'undefined') {
	(function($) {
		// 修复空选择器问题
		if (!$.fn._argonInit) {
			$.fn._argonInit = $.fn.init;
			$.fn.init = function(selector, context, root) {
				if (typeof selector === 'string') {
					let trimmed = selector.trim();
					if (trimmed === '' || trimmed === '#') {
						return new $.fn._argonInit();
					}
				}
				return $.fn._argonInit.call(this, selector, context, root);
			};
			$.fn.init.prototype = $.fn;
		}
	})(jQuery);
}
```

2. **工具函数定义**

```javascript
// argontheme.js
// Cookie 操作
function setCookie(cname, cvalue, exdays) { /* ... */ }
function getCookie(cname) { /* ... */ }

// 多语言翻译
var translation = {};
translation['en_US'] = { /* ... */ };
translation['ru_RU'] = { /* ... */ };
function __(text) {
	if (typeof translation[argonConfig.language] !== 'undefined' && 
	    typeof translation[argonConfig.language][text] !== 'undefined') {
		return translation[argonConfig.language][text];
	}
	return text;
}
```

3. **DOM 加载完成后初始化**

```javascript
// argontheme.js
$(document).ready(function() {
	// 初始化顶栏
	initNavbar();
	
	// 初始化侧边栏
	initSidebar();
	
	// 初始化瀑布流
	waterflowInit();
	
	// 初始化懒加载
	if (argonConfig.lazyload) {
		initLazyload();
	}
	
	// 初始化图片缩放
	if (argonConfig.zoomify) {
		$('.post-content img').zoomify(argonConfig.zoomify);
	}
	
	// 初始化代码高亮
	if (argonConfig.code_highlight.enable) {
		initCodeHighlight();
	}
	
	// 初始化评论系统
	initCommentSystem();
	
	// 初始化浮动按钮
	initFloatButtons();
	
	// 初始化 PJAX
	if (!argonConfig.disable_pjax) {
		initPjax();
	}
	
	// 移除加载类
	$('#float_action_buttons').removeClass('fabtns-unloaded');
});
```

#### 资源加载策略

1. **CSS 加载顺序**

```php
// header.php
// 1. 合并的 CSS（包含 Bootstrap 和 Argon Design System）
wp_enqueue_style("argon_css_merged", ".../argon_css_merged.css");

// 2. 主题样式（依赖合并的 CSS）
wp_enqueue_style("style", ".../style.css", array('argon_css_merged'));
```

2. **JavaScript 加载顺序**

```php
// header.php
// 1. 资源加载器（用于备用资源加载）
wp_enqueue_script("resource_loader", ".../resource-loader.js");

// 2. 合并的 JS（包含 jQuery 和其他库）- 在头部同步加载
wp_enqueue_script("argon_js_merged", ".../argon_js_merged.js", array(), $assets_version, false);

// 3. Argon 修复补丁（必须在 wp_head() 之后）
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/argon.min.js?ver=<?php echo $assets_version; ?>"></script>

// 4. 主题核心脚本（在 footer.php 中加载）
<?php $assets_version = function_exists('argon_get_assets_version') ? argon_get_assets_version() : $GLOBALS['theme_version']; ?>
<script src="<?php echo $GLOBALS['assets_path']; ?>/argontheme.js?v=<?php echo $assets_version; ?>"></script>
```

3. **按需加载**

```javascript
// argontheme.js
// Google Fonts 按需加载
if (typeof ArgonResourceLoader !== "undefined") {
	ArgonResourceLoader.smartLoad("//fonts.googleapis.com/css?family=...", "css");
}

// 数学公式渲染库在 footer.php 中按设置输出（get_option('argon_math_render')）
// 可选：mathjax3 / mathjax2 / katex / disabled
```

#### 强制刷新机制

当主题更新后，可能需要清除客户端缓存：

```php
// functions.php
function argon_is_force_refresh_enabled() {
	$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
	if ($enabled_time == 0) {
		return false;
	}
	// 1 小时后自动关闭
	if (time() - $enabled_time > 3600) {
		update_option('argon_force_refresh_enabled_time', 0);
		return false;
	}
	return true;
}

function argon_get_assets_version() {
	if (argon_is_force_refresh_enabled()) {
		$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
		return $GLOBALS['theme_version'] . '.r' . $enabled_time;
	}
	return $GLOBALS['theme_version'];
}
```

强制刷新时，header.php 会输出清除缓存的脚本：

```javascript
// header.php
<?php if (argon_is_force_refresh_enabled()): ?>
<script>
(function() {
	var forceRefreshKey = 'argon_force_refresh_version';
	var currentVersion = '<?php echo get_option('argon_force_refresh_enabled_time', 0); ?>';
	var lastVersion = localStorage.getItem(forceRefreshKey);
	
	if (lastVersion !== currentVersion) {
		localStorage.setItem(forceRefreshKey, currentVersion);
		if (lastVersion !== null) {
			// 清除所有缓存
			if ('caches' in window) {
				caches.keys().then(function(names) {
					return Promise.all(names.map(function(name) {
						return caches.delete(name);
					}));
				});
			}
			// 重新加载页面
			window.location.reload();
		}
	}
})();
</script>
<?php endif; ?>
```

### 全局配置对象

`argonConfig` 是主题的核心配置对象，在 header.php 中通过 PHP 动态生成并输出到页面：

```javascript
var argonConfig = {
	wp_path: "/",                    // WordPress 安装路径
	language: "zh_CN",               // 当前语言代码
	dateFormat: "YMD",               // 日期显示格式
	
	// 图片缩放配置（Zoomify）
	zoomify: {
		duration: 200,               // 缩放动画时长（毫秒）
		easing: "cubic-bezier(0.4,0,0,1)", // 缓动函数
		scale: 0.9                   // 缩放比例
	},
	// 如果禁用则为 false
	
	pangu: "false",                  // 盘古之白（中英文间自动加空格）
	
	// 懒加载配置
	lazyload: true,                  // 是否启用懒加载
	lazyload_effect: "fadeIn",       // 懒加载显示效果
	lazyload_threshold: 800,         // 提前加载阈值（像素）
	
	fold_long_comments: false,       // 是否折叠长评论
	fold_long_shuoshuo: false,       // 是否折叠长说说
	
	// PJAX 配置
	disable_pjax: false,             // 是否禁用 PJAX
	pjax_animation_durtion: 600,     // PJAX 切换动画时长（毫秒）
	
	headroom: "false",               // 顶栏自动隐藏模式（false/true/absolute）
	
	// 文章列表布局
	waterflow_columns: "1",          // 瀑布流列数（1/2/3）
	article_list_layout_mobile: "1", // 移动端文章列表布局样式
	
	// 代码高亮配置
	code_highlight: {
		enable: false,               // 是否启用代码高亮
		hide_linenumber: false,      // 是否隐藏行号
		transparent_linenumber: false, // 行号是否透明
		break_line: false            // 是否自动折行
	}
};
```

配置对象的值从 WordPress 后台设置中读取，通过 PHP 的 `get_option()` 函数获取。例如：

```php
// header.php
lazyload: <?php echo (get_option('argon_enable_lazyload', 'true') == 'false' ? 'false' : 'true'); ?>,
```

### CSS 变量系统

主题使用 CSS 自定义属性（CSS Variables）实现动态主题色和样式配置。这些变量在 header.php 中通过 PHP 动态生成。

#### 主题色变量

```php
// header.php - 获取主题色
$themecolor = get_option("argon_theme_color", "#5e72e4");
// 支持用户自定义主题色（通过 Cookie）
if (isset($_COOKIE["argon_custom_theme_color"])) {
	if (checkHEX($_COOKIE["argon_custom_theme_color"]) && 
	    get_option('argon_show_customize_theme_color_picker') != 'false') {
		$themecolor = $_COOKIE["argon_custom_theme_color"];
	}
}

// 转换为 RGB 和 HSL 值
$RGB = hexstr2rgb($themecolor);
$HSL = rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']);
```

生成的 CSS 变量：

```css
:root {
	/* 主题色 - 十六进制 */
	--themecolor: #5e72e4;
	
	/* 主题色 - RGB 分量（用于 rgba() 函数）*/
	--themecolor-R: 94;
	--themecolor-G: 114;
	--themecolor-B: 228;
	
	/* 主题色 - HSL 分量（用于生成色调变体）*/
	--themecolor-H: 231;
	--themecolor-S: 71;
	--themecolor-L: 63;
}
```

#### 动画系统变量

```css
:root {
	/* 动画时长 - 遵循 Material Design 3 规范 */
	--animation-fast: 150ms;      // 快速动画（按钮点击等）
	--animation-normal: 250ms;    // 标准动画（卡片展开等）
	--animation-slow: 400ms;      // 慢速动画（页面切换等）
	
	/* 缓动函数 - 融合 Material 3 + Apple 风格 */
	--ease-standard: cubic-bezier(0.25, 0.1, 0.25, 1);  // 标准缓动，流畅自然
	--ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);   // 弹性缓动，有活力
}
```

#### 卡片样式变量

```css
:root {
	--card-radius: 4px;           // 卡片圆角
	--card-opacity: 0.7;          // 卡片透明度
	--card-blur: 20px;            // 毛玻璃模糊度
	--card-saturate: 180%;        // 饱和度增强
	--toolbar-blur: 12px;         // 顶栏模糊度（卡片的 60%）
	--page-background-opacity: 1; // 页面背景透明度
}
```

这些值可以通过后台设置动态调整：

```php
// header.php
$card_opacity = get_option('argon_post_background_opacity', '0.7');
$card_blur = get_option('argon_card_blur', '20');
$card_saturate = get_option('argon_card_saturate', '180');
```

#### 颜色变量

```css
:root {
	/* 日间模式颜色 */
	--color-background: #f4f5f7;   // 页面背景色
	--color-foreground: #fff;      // 卡片背景色
	--color-widgets: #fff;         // 小工具背景色
	--color-border: #dce0e5;       // 边框颜色
	--color-text-deeper: #212529;  // 深色文本
}
```

夜间模式通过 `html.darkmode` 类切换颜色变量：

```css
html.darkmode body {
	--color-background: #282828;   // 深色背景
	--color-foreground: #424242;   // 深色卡片
	--color-widgets: #555;         // 深色小工具
	--color-text-deeper: #eee;     // 浅色文本
}
```

AMOLED 暗黑模式提供更深的黑色：

```css
html.darkmode.amoled-dark body {
	--color-background: #111;      // 纯黑背景
	--color-foreground: #000;      // 纯黑卡片
	--color-widgets: #151515;      // 深黑小工具
}
```



### 后台设置框架

Argon 主题不依赖第三方设置框架（如 Redux 或 Codestar），而是使用原生 PHP + HTML 实现了一个轻量级的设置页面。

#### 1. 设置页面结构

设置页面位于 `settings.php`，通过 `functions.php` 中的 `add_theme_page` 挂载：

```php
// functions.php
function themeoptions_admin_menu() {
    add_theme_page("Argon 主题设置", "Argon 主题选项", 'edit_themes', basename(__FILE__), 'themeoptions_page');
}
add_action('admin_menu', 'themeoptions_admin_menu');
```

页面内容主要由 `themeoptions_page()` 函数输出，采用 HTML Table 布局，包含多个选项卡（如“全局设置”、“侧栏设置”等）。

#### 2. 选项存储与更新

设置数据的保存逻辑集成在 `settings.php` 顶部：

```php
// settings.php
if (isset($_POST['update_themeoptions']) && $_POST['update_themeoptions'] == 'true') {
    // 1. 验证 Nonce 安全令牌
    check_admin_referer('argon_update_themeoptions', 'argon_update_themeoptions_nonce');
    
    // 2. 处理复选框（未选中的 checkbox 不会提交，需要手动设为 false）
    if (!isset($_POST['argon_enable_lazyload'])) $_POST['argon_enable_lazyload'] = 'false';
    
    // 3. 循环更新所有选项
    foreach ($_POST as $key => $value) {
        if (isset($value) && $value != '') {
            update_option($key, $value);
        } else {
            delete_option($key); // 空值则删除选项
        }
    }
    
    // 4. 提示保存成功
    echo '<div id="message" class="updated fade"><p><strong>设置已保存</strong></p></div>';
}
```

#### 3. 添加新选项指南

要在主题中添加一个新的设置项，需遵循以下步骤：

1.  在 `settings.php` 的对应表格行中添加 HTML 输入控件。
2.  `name` 属性必须以 `argon_` 开头（这是约定，方便管理）。
3.  使用 `get_option('option_name')` 获取当前值回显。

示例：添加一个“显示版权信息”的开关

```php
<tr>
    <th><label><?php _e('显示版权信息', 'argon');?></label></th>
    <td>
        <select name="argon_show_copyright">
            <?php $show_copyright = get_option('argon_show_copyright', 'true'); ?>
            <option value="true" <?php selected($show_copyright, 'true'); ?>>开启</option>
            <option value="false" <?php selected($show_copyright, 'false'); ?>>关闭</option>
        </select>
    </td>
</tr>
```

### 前端交互架构 (PJAX & SPA)

Argon 主题采用 PJAX (PushState + AJAX) 技术实现单页应用 (SPA) 体验，核心逻辑位于 `assets/js/argontheme.js`。

#### 1. PJAX 工作原理

1.  **拦截点击**：监听所有 `a` 标签点击事件。
2.  **获取内容**：通过 AJAX 请求目标页面 URL。
3.  **解析响应**：从响应 HTML 中提取 `#content` 容器内容。
4.  **替换容器**：将当前页面的 `#content` 替换为新内容。
5.  **更新状态**：使用 `history.pushState` 更新浏览器 URL。
6.  **重载资源**：重新执行内联脚本，重置事件监听器。

#### 2. 初始化与重载机制

由于 PJAX 会替换页面的部分 DOM，依赖旧 DOM 的事件绑定与第三方实例需要在 PJAX 完成后重新初始化。Argon 的实现采用“首屏最小初始化 + PJAX 完整重初始化”的策略。

首屏加载完成后的初始化入口位于 `argontheme.js` 的 `$(document).ready(...)`，主要执行性能优化模块初始化（如已加载）以及基础渲染：

```javascript
$(document).ready(function(){
	if (typeof initArgonPerformance === 'function') {
		initArgonPerformance();
	}
	highlightJsRender();
	waterflowInit();
});
```

PJAX 主流程通过 `$(document).pjax(...)` 建立，并在 `pjax:beforeReplace` 统一清理旧页面资源，在 `pjax:complete` 完整初始化新页面模块（包含数学公式渲染、脚本执行与各功能模块的错误隔离初始化）。

#### 3. 脚本执行器 (Inline Script Executor)

PJAX 替换的 DOM 片段中可能包含内联 `<script>`。Argon 使用“创建新 script 节点并插入 head”的方式执行内联脚本，同时为每个脚本标记 `data-pjax-executed`，避免重复执行：

```javascript
function executeInlineScripts(container) {
	const scripts = container.querySelectorAll('script');
	scripts.forEach((script) => {
		if (script.getAttribute('data-pjax-executed') === 'true') return;
		if (script.src) return;
		if (!script.textContent || script.textContent.trim() === '') return;
		const newScript = document.createElement('script');
		newScript.text = script.textContent;
		document.head.appendChild(newScript);
		document.head.removeChild(newScript);
		script.setAttribute('data-pjax-executed', 'true');
	});
}
```

**开发注意**：如果你在文章或页面中编写了自定义 JavaScript，确保它们不依赖 `document.ready`，或者在 PJAX 重载时能被正确处理。

## 核心功能模块

### 1. 主题色系统

#### 实现原理

主题色通过 PHP 动态生成 CSS 变量，支持用户自定义：

```php
// header.php
$themecolor = get_option("argon_theme_color", "#5e72e4");
if (isset($_COOKIE["argon_custom_theme_color"])) {
	$themecolor = $_COOKIE["argon_custom_theme_color"];
}
$RGB = hexstr2rgb($themecolor);
$HSL = rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']);
```

生成的 CSS 变量：

```css
:root {
	--themecolor: #5e72e4;
	--themecolor-R: 94;
	--themecolor-G: 114;
	--themecolor-B: 228;
	--themecolor-H: 231;
	--themecolor-S: 71;
	--themecolor-L: 63;
}
```

#### 沉浸式主题色

开启沉浸式主题色后，页面背景和卡片会使用主题色的浅色变体：

```css
html.immersion-color body {
	--color-background: rgb(var(--color-tint-86));
	--color-foreground: rgb(var(--color-tint-92));
}
```

### 2. 夜间模式

#### 切换方案

主题支持四种夜间模式切换方案，通过后台设置 `argon_darkmode_autoswitch` 选项控制：

1. **false（默认）** - 手动切换，不自动切换
2. **system** - 跟随系统，通过 `prefers-color-scheme` 媒体查询
3. **time** - 根据时间自动切换（22:00-7:00）
4. **alwayson** - 始终开启夜间模式

#### 实现代码

夜间模式的核心实现在 header.php 中：

```javascript
// header.php
var darkmodeAutoSwitch = "<?php echo (get_option("argon_darkmode_autoswitch") == '' ? 'false' : get_option("argon_darkmode_autoswitch"));?>";

// 设置夜间模式
function setDarkmode(enable){
	// 添加过渡类以启用平滑切换动画
	document.documentElement.classList.add("theme-transitioning");
	
	if (enable == true){
		document.documentElement.classList.add("darkmode");
	}else{
		document.documentElement.classList.remove("darkmode");
	}
	
	// 过渡完成后移除过渡类（300ms 后）
	setTimeout(function() {
		document.documentElement.classList.remove("theme-transitioning");
	}, 300);
	
	// 触发滚动事件以更新顶栏状态
	if (typeof jQuery !== 'undefined') {
		jQuery(window).trigger("scroll");
	} else {
		window.dispatchEvent(new Event('scroll'));
	}
	
	// 触发自定义事件，供其他模块监听
	document.dispatchEvent(new CustomEvent('argon:theme-switched', {
		detail: { darkmode: enable }
	}));
}

// 切换夜间模式
function toggleDarkmode(){
	if (document.documentElement.classList.contains("darkmode")){
		setDarkmode(false);
		sessionStorage.setItem("Argon_Enable_Dark_Mode", "false");
	}else{
		setDarkmode(true);
		sessionStorage.setItem("Argon_Enable_Dark_Mode", "true");
	}
}
```

#### 跟随系统方案

使用 `matchMedia` API 监听系统主题变化：

```javascript
// header.php
function toggleDarkmodeByPrefersColorScheme(media){
	// 如果用户手动设置过，则不自动切换
	if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || 
	    sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){
		return;
	}
	
	if (media.matches){
		setDarkmode(true);
	}else{
		setDarkmode(false);
	}
}

if (darkmodeAutoSwitch == 'system'){
	var darkmodeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
	darkmodeMediaQuery.addListener(toggleDarkmodeByPrefersColorScheme);
	toggleDarkmodeByPrefersColorScheme(darkmodeMediaQuery);
}
```

#### 根据时间方案

```javascript
// header.php
function toggleDarkmodeByTime(){
	// 如果用户手动设置过，则不自动切换
	if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || 
	    sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){
		return;
	}
	
	let hour = new Date().getHours();
	
	// 默认：22:00-7:00 开启夜间模式
	// 可通过过滤器 argon_darkmode_time_check 自定义时间范围
	if (<?php echo apply_filters("argon_darkmode_time_check", "hour < 7 || hour >= 22")?>){
		setDarkmode(true);
	}else{
		setDarkmode(false);
	}
}

if (darkmodeAutoSwitch == 'time'){
	toggleDarkmodeByTime();
}
```

#### AMOLED 暗黑模式

AMOLED 模式提供更深的黑色背景，适合 AMOLED 屏幕节省电量：

```javascript
// header.php
function toggleAmoledDarkMode(){
	document.documentElement.classList.toggle("amoled-dark");
	
	if (document.documentElement.classList.contains("amoled-dark")){
		localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "true");
	}else{
		localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "false");
	}
}

// 页面加载时恢复 AMOLED 模式设置
if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "true"){
	document.documentElement.classList.add("amoled-dark");
}else if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "false"){
	document.documentElement.classList.remove("amoled-dark");
}
```

#### 用户偏好存储

夜间模式的用户选择存储在 `sessionStorage` 中，AMOLED 模式存储在 `localStorage` 中：

- `sessionStorage.Argon_Enable_Dark_Mode` - 夜间模式开关（会话级别）
- `localStorage.Argon_Enable_Amoled_Dark_Mode` - AMOLED 模式开关（持久化）

这样设计的原因：
- 夜间模式使用 sessionStorage，每次打开浏览器都会根据设置重新判断
- AMOLED 模式使用 localStorage，用户设置会永久保存

### 3. PJAX 无刷新加载

#### 配置与初始化

PJAX 使用 jquery-pjax 库实现页面无刷新加载。配置在 argontheme.js 中：

```javascript
// argontheme.js
$.pjax.defaults.timeout = 10000;  // 超时时间 10 秒
var pjaxContainerSelectors = ['#primary', '#leftbar_part1_menu', '#leftbar_part2_inner', '.page-information-card-container', '#rightbar', '#wpadminbar'];
var pjaxContainers = pjaxContainerSelectors.filter(function(selector) {
	return document.querySelector(selector);
});
$.pjax.defaults.container = pjaxContainers;  // 要替换的容器
$.pjax.defaults.fragment = pjaxContainers;   // 从响应中提取的片段
```

#### 链接拦截

主题会自动拦截站内链接的点击事件：

```javascript
// argontheme.js
$(document).pjax(
	"a[href]:not([no-pjax]):not(.no-pjax):not([target='_blank']):not([download]):not(.reference-link):not(.reference-list-backlink):not([href^='#'])",
	pjaxContainers.length ? pjaxContainers[0] : '#primary',
	{ fragment: (pjaxContainers.length ? pjaxContainers : ['#primary']), timeout: $.pjax.defaults.timeout }
);
```

#### 页面切换流程

PJAX 加载过程中会触发一系列事件：

```javascript
// argontheme.js

// 1. 开始发送请求
$(document).on('pjax:send', function() {
	// 设置加载状态
	pjaxLoading = true;
	
	// 显示加载动画
	$('#content').addClass('pjax-loading');
	
	// 添加淡出效果
	$('#content, #leftbar, #rightbar').css('opacity', '0');
});

// 2. 请求成功，内容已替换
$(document).on('pjax:complete', function() {
	// 清除加载状态
	pjaxLoading = false;
	
	// 移除加载动画
	$('#content').removeClass('pjax-loading');
	
	// 添加淡入效果
	$('#content, #leftbar, #rightbar').animate({
		opacity: 1
	}, argonConfig.pjax_animation_durtion);
	
	// 滚动到顶部
	$('html, body').animate({
		scrollTop: 0
	}, argonConfig.pjax_animation_durtion);
	
	// 重新初始化各功能模块（主题在 pjax:complete 内集中处理，如 waterflowInit / lazyloadInit / zoomifyInit / highlightJsRender 等）
});

// 3. 请求失败
$(document).on('pjax:error', function(xhr, textStatus, error) {
	console.error('PJAX 加载失败:', error);
	// 失败时会自动回退到普通页面跳转
});

// 4. 超时
$(document).on('pjax:timeout', function(event) {
	// 阻止默认的超时处理（会中断请求）
	event.preventDefault();
});
```

#### 重新初始化

PJAX 加载完成后，需要重新初始化页面功能：

```javascript
// argontheme.js
// 主题在 pjax:complete 事件里集中完成“功能重置/再初始化”（单个模块失败不会影响其他模块）
try { waterflowInit(); } catch (err) {}
try { lazyloadInit(); } catch (err) {}
try { zoomifyInit(); } catch (err) {}
try { highlightJsRender(); } catch (err) {}
try { panguInit(); } catch (err) {}
try { clampInit(); } catch (err) {}
try { tippyInit(); } catch (err) {}
try { getGithubInfoCardContent(); } catch (err) {}
try { showPostOutdateToast(); } catch (err) {}
try { calcHumanTimesOnPage(); } catch (err) {}
try { foldLongComments(); } catch (err) {}
try { foldLongShuoshuo(); } catch (err) {}
try { handleHashNavigation(); } catch (err) {}
```

#### 禁用 PJAX

可以通过以下方式禁用 PJAX：

1. 后台设置中禁用（`argon_pjax_disabled` 选项）
2. 给链接添加 `no-pjax` 属性：

```html
<a href="/page" no-pjax>普通跳转</a>
```

3. 给链接添加 `target` 属性：

```html
<a href="/page" target="_blank">新窗口打开</a>
```

#### 浏览器历史记录

PJAX 会自动更新浏览器历史记录，支持前进后退按钮：

```javascript
// argontheme.js
$(window).on('popstate', function() {
	// 浏览器前进/后退时，PJAX 会自动加载对应页面
});
```

#### 性能优化

PJAX 加载时的性能优化措施：

1. 只替换必要的容器（content、leftbar、rightbar）
2. 使用 CSS 动画而非 JavaScript 动画
3. 延迟初始化非关键功能
4. 复用已加载的资源（CSS、JS）

```javascript
// argontheme.js
// 动画时长可配置，设为 0 可禁用动画
pjax_animation_durtion: <?php echo (get_option("argon_disable_pjax_animation") == 'true' ? '0' : '600'); ?>
```

### 4. 瀑布流布局

#### 实现原理

瀑布流布局通过 JavaScript 动态计算每个文章卡片的位置，实现类似 Pinterest 的多列布局。

#### 核心算法

```javascript
// argontheme.js
function waterflowInit() {
	// 如果设置为单列，直接返回
	if (argonConfig.waterflow_columns == "1") {
		return;
	}
	
	// 监听图片加载，加载完成后重新计算布局
	$("#main.article-list img").each(function(index, ele){
		ele.onload = function(){
			waterflowInit();
		}
	});
	
	// 确定列数
	let columns;
	if (argonConfig.waterflow_columns == "2and3") {
		// 自适应 2-3 列
		if ($("#main").outerWidth() > 1000) {
			columns = 3;
		} else {
			columns = 2;
		}
	}else{
		columns = parseInt(argonConfig.waterflow_columns);
	}
	
	// 响应式适配：窄屏幕时强制单列
	if ($("#main").outerWidth() < 650 && columns == 2) {
		columns = 1;
	}else if ($("#main").outerWidth() < 800 && columns == 3) {
		columns = 1;
	}
	
	// 记录每列的高度
	let heights = [0, 0, 0];
	
	// 获取当前最矮列的索引
	function getMinHeightPosition(){
		let res = 0, minn = 2147483647;
		for (var i = 0; i < columns; i++) {
			if (heights[i] < minn) {
				minn = heights[i];
				res = i;
			}
		}
		return res;
	}
	
	// 获取最高列的高度
	function getMaxHeight(){
		let res = 0;
		for (let i in heights) {
			res = Math.max(res, heights[i]);
		}
		return res;
	}
	
	// 添加瀑布流类
	$("#primary").css("transition", "none").addClass("waterflow");
	
	let $container = $("#main.article-list");
	if (!$container.length){
		return;
	}
	
	// 获取所有文章卡片
	let $items = $container.find("article.post:not(.no-results), .shuoshuo-preview-container");
	
	// 列数不能超过文章数量
	columns = Math.max(Math.min(columns, $items.length), 1);
	
	if (columns == 1) {
		// 单列模式：移除瀑布流样式
		$container.removeClass("waterflow");
		$items.css("transition", "")
			.css("position", "")
			.css("width", "")
			.css("top", "")
			.css("left", "")
			.css("margin", "");
		$(".waterflow-placeholder").remove();
	}else{
		// 多列模式：计算每个卡片的位置
		$container.addClass("waterflow");
		$items.each(function(index, item) {
			let $item = $(item);
			
			// 设置卡片宽度（平分容器宽度，减去间距）
			$item.css("transition", "none")
				.css("position", "absolute")
				.css("width", "calc(" + (100 / columns) + "% - " + (10 * (columns - 1) / columns) + "px)")
				.css("margin", 0);
			
			// 计算卡片高度（包含 10px 间距）
			let itemHeight = $item.outerHeight() + 10;
			
			// 找到最矮的列
			let pos = getMinHeightPosition();
			
			// 设置卡片位置
			$item.css("top", heights[pos] + "px")
				.css("left", (pos * $item.outerWidth() + 10 * pos) + "px");
			
			// 更新该列的高度
			heights[pos] += itemHeight;
		});
	}
	
	// 创建占位元素，撑开容器高度
	if ($(".waterflow-placeholder").length) {
		$(".waterflow-placeholder").css("height", getMaxHeight() + "px");
	}else{
		$container.prepend("<div class='waterflow-placeholder' style='height: " + getMaxHeight() +"px;'></div>");
	}
}
```

#### 初始化与监听

```javascript
// argontheme.js
// 页面加载时初始化
waterflowInit();

// 非单列模式下监听窗口大小变化
if (argonConfig.waterflow_columns != "1") {
	// 窗口大小改变时重新计算
	$(window).resize(function(){
		waterflowInit();
	});
	
	// 监听 DOM 变化（如 PJAX 加载新内容）
	new MutationObserver(function(mutations, observer){
		waterflowInit();
	}).observe(document.querySelector("#primary"), {
		'childList': true
	});
}
```

#### 移动端布局切换

移动端可以使用不同的文章列表布局样式：

```javascript
// argontheme.js
!function(){
	var mobileLayout = argonConfig.article_list_layout_mobile || "1";
	var isMobile = window.innerWidth <= 900;
	
	function applyMobileLayout() {
		var nowMobile = window.innerWidth <= 900;
		if (nowMobile) {
			// 添加移动端布局类
			$("html").addClass("mobile-layout-" + mobileLayout);
		} else {
			// 移除移动端布局类
			$("html").removeClass("mobile-layout-1 mobile-layout-2 mobile-layout-3");
		}
	}
	
	applyMobileLayout();
	$(window).resize(applyMobileLayout);
}();
```

#### 配置选项

瀑布流列数通过后台设置 `argon_article_list_waterflow` 控制：

- `"1"` - 单列（默认）
- `"2"` - 双列
- `"3"` - 三列
- `"2and3"` - 自适应 2-3 列（宽屏 3 列，窄屏 2 列）

#### 性能优化

1. 图片加载完成后才计算布局，避免高度计算错误
2. 使用 `MutationObserver` 监听 DOM 变化，而非轮询
3. 窗口大小改变时使用防抖，避免频繁计算
4. 单列模式下不监听事件，减少性能开销

#### CSS 配合

```css
/* style.css */
#main.article-list.waterflow {
	position: relative;
}

#main.article-list.waterflow article.post {
	position: absolute;
	transition: top 0.3s ease, left 0.3s ease;
}

.waterflow-placeholder {
	width: 100%;
	pointer-events: none;
}
```


### 5. 评论系统

#### 数据库结构

评论数据存储在 WordPress 标准的 `wp_comments` 表中，扩展字段通过 `wp_commentmeta` 表存储：

**标准字段（wp_comments 表）：**
- `comment_ID` - 评论 ID
- `comment_post_ID` - 所属文章 ID
- `comment_author` - 评论者昵称
- `comment_author_email` - 评论者邮箱
- `comment_author_url` - 评论者网站
- `comment_content` - 评论内容（HTML）
- `comment_parent` - 父评论 ID（用于回复）
- `user_id` - 用户 ID（登录用户）
- `comment_date` - 评论时间

**扩展字段（wp_commentmeta 表）：**
- `comment_upvote` - 点赞数
- `comment_is_private` - 是否私密评论
- `comment_use_markdown` - 是否使用 Markdown
- `comment_content_source` - Markdown 源码
- `comment_edit_history` - 编辑历史（JSON 格式）
- `comment_pinned` - 是否置顶
- `qq` - QQ 号（用于 QQ 头像）

#### AJAX 发送评论

前端通过 AJAX 提交评论，避免页面刷新：

```javascript
// argontheme.js
function postComment() {
	let formData = {
		action: 'ajax_post_comment',
		comment_post_ID: postID,
		author: $('#comment_author').val(),
		email: $('#comment_email').val(),
		url: $('#comment_url').val(),
		comment: $('#comment_content').val(),
		comment_parent: replyID,
		use_markdown: $('#use_markdown').is(':checked') ? 'true' : 'false',
		is_private: $('#is_private').is(':checked') ? 'true' : 'false',
		argon_nonce: $('#argon_comment_nonce').val(),
		// 验证码相关字段
		captcha_seed: $('#captcha_seed').val(),
		captcha_answer: $('#captcha_answer').val()
	};
	
	$.ajax({
		type: 'POST',
		url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
		dataType: "json",
		data: formData,
		beforeSend: function() {
			$('#comment_submit').text(__('发送中'));
			$('#comment_submit').prop('disabled', true);
		},
		success: function(result) {
			if (result.status == "success") {
				// 插入新评论到页面
				insertComment(result.html, result.parentID, result.commentOrder);
				// 清空表单
				$('#comment_content').val('');
				// 更新验证码
				updateCaptcha(result.newCaptchaSeed, result.newCaptcha);
			} else {
				alert(result.msg);
			}
		},
		error: function() {
			alert(__('评论发送失败'));
		},
		complete: function() {
			$('#comment_submit').text(__('发送'));
			$('#comment_submit').prop('disabled', false);
		}
	});
}
```

#### PHP 处理函数

服务器端处理评论提交：

```php
// functions.php
function ajax_post_comment(){
	// 1. IP 黑名单检查
	if (argon_is_ip_blocked_global()) {
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('您的 IP 已被限制访问', 'argon'),
			'isAdmin' => current_user_can('level_7')
		)));
	}
	
	// 2. 私密评论权限检查
	$parentID = $_POST['comment_parent'];
	if (is_comment_private_mode($parentID)){
		if (!user_can_view_comment($parentID)){
			exit(json_encode(array(
				'status' => 'failed',
				'msg' =>  __('不能回复其他人的悄悄话评论', 'argon'),
				'isAdmin' => current_user_can('level_7')
			)));
		}
	}
	
	// 3. QQ 邮箱处理
	if (get_option('argon_comment_enable_qq_avatar') == 'true'){
		if (check_qqnumber($_POST['email'])){
			$_POST['qq'] = $_POST['email'];
			$_POST['email'] .= "@qq.com";
		}
	}
	
	// 4. CSRF nonce 校验
	if (!isset($_POST['argon_nonce']) || !wp_verify_nonce($_POST['argon_nonce'], 'argon_comment')) {
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('请求已失效，请刷新页面后重试', 'argon'),
			'isAdmin' => current_user_can('level_7')
		)));
	}
	
	// 5. Honeypot 反垃圾检查
	if (!empty($_POST['argon_comment_honeypot'])) {
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('Spam detected', 'argon'),
			'isAdmin' => current_user_can('level_7')
		)));
	}
	
	// 6. 速率限制（防止刷评论）
	$rate_enable = get_option('argon_rate_limit_enable', 'true');
	if ($rate_enable === 'true') {
		$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR'];
		$ip = sanitize_text_field($ip);
		$rate_key = 'argon_rate_cmt_' . md5($ip);
		$state = get_transient($rate_key);
		$now = time();
		$window = intval(get_option('argon_rate_limit_window', 300)); // 窗口秒数（默认 5 分钟）
		$max_count = intval(get_option('argon_rate_limit_max_count', 5)); // 窗口内最大次数
		$min_interval = intval(get_option('argon_rate_limit_min_interval', 10)); // 两次最小间隔（秒）
		
		// 检查最小间隔
		if (!is_array($state)) {
			$state = array('count' => 0, 'start' => $now, 'last' => 0);
		}
		if ($state['last'] > 0 && ($now - intval($state['last'])) < $min_interval) {
			exit(json_encode(array(
				'status' => 'failed',
				'msg' => __('您评论过快，请稍后再试', 'argon'),
				'isAdmin' => current_user_can('level_7')
			)));
		}
		
		// 检查窗口内次数
		if (($now - intval($state['start'])) < $window && intval($state['count']) >= $max_count) {
			exit(json_encode(array(
				'status' => 'failed',
				'msg' => __('操作过于频繁，请稍后再试', 'argon'),
				'isAdmin' => current_user_can('level_7')
			)));
		}
		
		// 更新速率状态
		if (($now - intval($state['start'])) >= $window) {
			$state['start'] = $now;
			$state['count'] = 0;
		}
		$state['count'] = intval($state['count']) + 1;
		$state['last'] = $now;
		set_transient($rate_key, $state, $window);
	}
	
	// 7. 提交评论
	$comment = wp_handle_comment_submission(wp_unslash($_POST));
	if (is_wp_error($comment)){
		$msg = $comment->get_error_message();
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => $msg,
			'isAdmin' => current_user_can('level_7')
		)));
	}
	
	// 8. 设置 Cookie
	$user = wp_get_current_user();
	do_action('set_comment_cookies', $comment, $user);
	
	// 9. 生成评论 HTML
	$html = wp_list_comments(
		array(
			'type'      => 'comment',
			'callback'  => 'argon_comment_format',
			'echo'      => false
		),
		array($comment)
	);
	
	// 10. 生成新验证码
	$newCaptchaSeed = get_comment_captcha_seed(true);
	$newCaptcha = get_comment_captcha($newCaptchaSeed);
	
	// 11. 返回结果
	exit(json_encode(array(
		'status' => 'success',
		'html' => $html,
		'id' => $comment->comment_ID,
		'parentID' => $comment->comment_parent,
		'commentOrder' => (get_option("comment_order") == "" ? "desc" : get_option("comment_order")),
		'newCaptchaSeed' => $newCaptchaSeed,
		'newCaptcha' => $newCaptcha,
		'isAdmin' => current_user_can('level_7'),
		'isLogin' => is_user_logged_in()
	)));
}
add_action('wp_ajax_ajax_post_comment', 'ajax_post_comment');
add_action('wp_ajax_nopriv_ajax_post_comment', 'ajax_post_comment');
```

#### Markdown 解析

评论支持 Markdown 语法，使用 Parsedown 库解析：

```php
// functions.php
require_once(get_template_directory() . '/parsedown.php');

function comment_markdown_parse($comment_content){
	// 允许评论中额外的 HTML 标签
	global $allowedtags;
	$allowedtags['pre'] = array('class' => array());
	$allowedtags['i'] = array('class' => array(), 'aria-hidden' => array());
	$allowedtags['img'] = array('src' => array(), 'alt' => array(), 'class' => array());
	$allowedtags['ol'] = array();
	$allowedtags['ul'] = array();
	$allowedtags['li'] = array();
	$allowedtags['span'] = array('class' => array(), 'style' => array(), 'title' => array());
	$allowedtags['a']['class'] = array();
	$allowedtags['a']['data-src'] = array();
	$allowedtags['a']['target'] = array();
	$allowedtags['h1'] = $allowedtags['h2'] = $allowedtags['h3'] = $allowedtags['h4'] = $allowedtags['h5'] = $allowedtags['h6'] = array();
	
	// 解析 Markdown
	$parsedown = new _Parsedown();
	$res = $parsedown->text($comment_content);
	
	// 为链接添加 target="_blank"
	$res = preg_replace(
		'/<a (.*?)>(.*?)<\/a>/',
		'<a $1 target="_blank">$2</a>',
		$res
	);
	
	return $res;
}

// 评论发送前处理
function post_comment_preprocessing($comment){
	// 保存评论未经 Markdown 解析的源码
	$_POST['comment_content_source'] = $comment['comment_content'];
	
	// 应用评论宏（黑幕、彩幕等）
	$comment['comment_content'] = argon_apply_comment_macros($comment['comment_content']);
	
	// Markdown 解析
	if ($_POST['use_markdown'] == 'true' && get_option("argon_comment_allow_markdown") != "false"){
		$comment['comment_content'] = comment_markdown_parse($comment['comment_content']);
	}
	
	return $comment;
}
add_filter('preprocess_comment', 'post_comment_preprocessing');
```

#### 评论宏

主题支持多种评论宏，用于实现特殊效果：

```php
// functions.php
function argon_apply_comment_macros($text){
	// 黑幕：{{黑幕|内容}} 或 {{黑幕|内容|提示}}
	$text = preg_replace_callback('/\{\{黑幕\|([\s\S]*?)(?:\|([\s\S]*?))?\}\}/u', function($m){
		$content = trim($m[1]);
		$title = isset($m[2]) ? trim($m[2]) : '你知道的太多了';
		return '<span class="heimu"' . (strlen($title) ? ' title="' . htmlspecialchars($title, ENT_QUOTES) . '"' : '') . '>' . htmlspecialchars($content) . '</span>';
	}, $text);
	
	// 彩幕：{{彩幕|内容|背景色|提示|前景色}}
	$text = preg_replace_callback('/\{\{彩幕\|([\s\S]*?)\}\}/u', function($m){
		$parts = explode('|', $m[1]);
		$content = isset($parts[0]) ? trim($parts[0]) : '';
		$bg = isset($parts[1]) ? trim($parts[1]) : '#252525';
		$tip = isset($parts[2]) ? trim($parts[2]) : '你知道的太多了';
		$fg = isset($parts[3]) ? trim($parts[3]) : '';
		
		// 如果没有指定前景色，根据背景色亮度自动计算
		if (empty($fg)) {
			$hex = ltrim($bg, '#');
			if (strlen($hex) == 6) {
				$r = hexdec(substr($hex, 0, 2));
				$g = hexdec(substr($hex, 2, 2));
				$b = hexdec(substr($hex, 4, 2));
				$luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
				$fg = ($luma >= 180) ? '#000' : '#fff';
			} else {
				$fg = '#fff';
			}
		}
		
		$style = '--curtain-bg: ' . htmlspecialchars($bg, ENT_QUOTES) . '; --curtain-fg: ' . htmlspecialchars($fg, ENT_QUOTES) . ';';
		return '<span class="color-curtain"' . (strlen($tip) ? ' title="' . htmlspecialchars($tip, ENT_QUOTES) . '"' : '') . ' style="' . $style . '">' . htmlspecialchars($content) . '</span>';
	}, $text);
	
	// 文字模糊：{{文字模糊|内容|提示|颜色|时间}}
	$text = preg_replace_callback('/\{\{文字模糊\|([\s\S]*?)\}\}/u', function($m){
		$parts = explode('|', $m[1]);
		$content = isset($parts[0]) ? trim($parts[0]) : '';
		$tip = isset($parts[1]) ? trim($parts[1]) : '你知道的太多了';
		$color = isset($parts[2]) ? trim($parts[2]) : '';
		$time = isset($parts[3]) ? trim($parts[3]) : '0.2';
		$style = '--text-blur-transition-time: ' . preg_replace('/[^0-9\.]/', '', $time) . 's;';
		if (strlen($color) > 0) {
			$style .= ' --text-blur-color: ' . htmlspecialchars($color, ENT_QUOTES) . ';';
		}
		return '<span class="text-blur"' . (strlen($tip) ? ' title="' . htmlspecialchars($tip, ENT_QUOTES) . '"' : '') . ' style="' . $style . '">' . htmlspecialchars($content) . '</span>';
	}, $text);
	
	return $text;
}
add_filter('comment_text', 'argon_comment_text_render', 9);
```

使用示例：
- `{{黑幕|剧透内容}}` - 鼠标悬停显示
- `{{彩幕|彩色文字|#ff0000}}` - 彩色背景
- `{{文字模糊|模糊文字}}` - 鼠标悬停清晰


### 6. 说说功能

#### 自定义文章类型

说说是一个独立的自定义文章类型，类似微博的短内容发布功能：

```php
// functions.php
add_action('init', 'init_shuoshuo');
function init_shuoshuo(){
	$labels = array(
		'name' => __('说说', 'argon'),
		'singular_name' => __('说说', 'argon'),
		'add_new' => __('发表说说', 'argon'),
		'add_new_item' => __('发表说说', 'argon'),
		'edit_item' => __('编辑说说', 'argon'),
		'new_item' => __('新说说', 'argon'),
		'view_item' => __('查看说说', 'argon'),
		'search_items' => __('搜索说说', 'argon'),
		'not_found' => __('暂无说说', 'argon'),
		'not_found_in_trash' => __('没有已遗弃的说说', 'argon'),
		'parent_item_colon' => '',
		'menu_name' => __('说说', 'argon')
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'exclude_from_search' => true,
		'query_var' => true,
		'rewrite' => array(
			'slug' => 'shuoshuo',
			'with_front' => false
		),
		'capability_type' => 'post',
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => null,
		'menu_icon' => 'dashicons-format-quote',
		'supports' => array('editor', 'author', 'title', 'custom-fields', 'comments')
	);
	register_post_type('shuoshuo', $args);
}
```

#### 页面模板

说说页面使用独立的模板文件 `shuoshuo.php`：

```php
// shuoshuo.php
/*
Template Name: 说说
*/
query_posts("post_type=shuoshuo&post_status=publish&posts_per_page=-1");
```


#### 说说内容模板

说说内容通过 `template-parts/content-shuoshuo.php` 渲染：

```php
<div class="shuoshuo-item card">
	<div class="card-body">
		<div class="shuoshuo-author">
			<?php echo get_avatar(get_the_author_meta('ID'), 40); ?>
			<span class="shuoshuo-author-name">
				<?php the_author(); ?>
			</span>
		</div>
		<div class="shuoshuo-content">
			<?php the_content(); ?>
		</div>
		<div class="shuoshuo-meta">
			<span class="shuoshuo-time">
				<?php echo get_the_date('Y-m-d H:i'); ?>
			</span>
			<span class="shuoshuo-comment-count">
				<?php comments_number('0 条评论', '1 条评论', '% 条评论'); ?>
			</span>
		</div>
	</div>
</div>
```

#### 说说操作按钮

支持点赞、评论、分享等操作，通过 `template-parts/shuoshuo-operations.php` 实现。

#### 折叠长说说

当说说内容超过一定长度时自动折叠：

```javascript
// argontheme.js
if (argonConfig.fold_long_shuoshuo) {
	$('.shuoshuo-content').each(function() {
		let $content = $(this);
		if ($content.height() > 300) {
			$content.addClass('folded');
			$content.after('<a class="unfold-btn">展开</a>');
		}
	});
}
```


### 7. AI 摘要功能

#### 功能概述

AI 摘要功能允许为文章自动生成摘要，支持多种 AI 服务提供商。

#### 查询接口

通过独立的 PHP 文件 `ai-summary-query.php` 处理 AI 摘要请求：

```php
// ai-summary-query.php
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) $wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
require_once($wp_load_path);
```

#### 安全防护

实现了完善的 IP 访问限制机制：

```php
/**
 * 获取客户端真实 IP
 * 优先级：CF-Connecting-IP > X-Real-IP > X-Forwarded-For > REMOTE_ADDR
 */
function argon_ai_query_get_client_ip() {
	$ip = '';
	
	// Cloudflare
	if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
	}
	// Nginx proxy_pass 或其他反向代理
	elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
		$ip = $_SERVER['HTTP_X_REAL_IP'];
	}
	// 通过代理转发（取第一个 IP）
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
	}
	// 直连 IP
	else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	
	return trim($ip);
}
```


#### 访问频率限制

```php
/**
 * 检查 IP 访问频率限制
 * @return bool|string true 表示允许访问，字符串表示错误信息
 */
function argon_ai_query_check_rate_limit() {
	$client_ip = argon_ai_query_get_client_ip();
	if (empty($client_ip)) {
		return __('无法获取客户端 IP', 'argon');
	}
	
	$transient_key = 'ai_query_lock_' . md5($client_ip);
	$rate_limit_key = 'ai_query_rate_' . md5($client_ip);
	
	// 检查是否有正在进行的查询（单线程限制）
	if (get_transient($transient_key)) {
		return __('请等待上一次查询完成', 'argon');
	}
	
	// 检查访问频率（60秒内最多10次）
	$access_count = get_transient($rate_limit_key);
	if ($access_count === false) {
		set_transient($rate_limit_key, 1, 60);
	} else {
		if ($access_count >= 10) {
			return __('访问过于频繁，请稍后再试', 'argon');
		}
		set_transient($rate_limit_key, $access_count + 1, 60);
	}
	
	// 设置查询锁（3秒超时）
	set_transient($transient_key, 1, 3);
	
	return true;
}
```

#### AI 服务配置

在后台设置页面配置 AI 服务：

- API 端点 URL
- API 密钥
- 模型名称
- 请求参数（温度、最大 token 数等）
- 超时设置

#### 前端调用

```javascript
// argontheme.js
function loadAISummary(postId) {
	$.ajax({
		url: argonConfig.wp_path + '?argon_ai_query=1',
		type: 'POST',
		data: {
			post_id: postId
		},
		success: function(response) {
			$('#ai-summary-content').html(response.summary);
		}
	});
}
```


### 8. 后台设置系统

#### 设置页面结构

`settings.php` 是主题的后台设置页面，包含 7000+ 行代码（当前仓库约 7273 行），提供完整的主题配置界面。

#### 设置分类

设置页面分为多个选项卡：

1. 外观设置 - 主题色、布局、卡片样式
2. 功能设置 - PJAX、懒加载、代码高亮
3. 文章设置 - 文章列表样式、摘要长度
4. 评论设置 - 评论验证码、邮件通知
5. 说说设置 - 说说页面配置
6. AI 设置 - AI 摘要服务配置
7. 性能优化 - 资源合并、缓存控制
8. 高级设置 - 自定义 CSS/JS、SEO

#### 选项存储

所有设置通过 WordPress Options API 存储：

```php
// 保存设置
update_option('argon_theme_color', $_POST['argon_theme_color']);

// 读取设置
$theme_color = get_option('argon_theme_color', '#5e72e4');
```

#### 设置验证

提交设置时进行安全验证：

```php
// settings.php
if (isset($_POST['save'])) {
	// 验证 nonce
	check_admin_referer('argon_theme_options');
	
	// 验证权限
	if (!current_user_can('manage_options')) {
		wp_die(__('权限不足', 'argon'));
	}
	
	// 保存设置
	foreach ($_POST as $key => $value) {
		if (strpos($key, 'argon_') === 0) {
			update_option($key, sanitize_text_field($value));
		}
	}
	
	echo '<div class="updated"><p>设置已保存</p></div>';
}
```


#### 动态设置界面

设置页面使用 jQuery 实现动态交互：

```javascript
// settings.php 内嵌脚本
$(document).ready(function() {
	// 选项卡切换
	$('.nav-tab').click(function() {
		let target = $(this).data('target');
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('.settings-section').hide();
		$('#' + target).show();
	});
	
	// 颜色选择器
	$('.color-picker').wpColorPicker();
	
	// 拖拽排序
	dragula([$('#sortable-list')[0]]);
});
```

#### AI API 配置表格

支持配置多个 AI 服务提供商：

```php
<table class="argon-ai-api-table">
	<thead>
		<tr>
			<th>服务名称</th>
			<th>API 端点</th>
			<th>API 密钥</th>
			<th>模型</th>
			<th>状态</th>
			<th>操作</th>
		</tr>
	</thead>
	<tbody id="argon-unified-api-list">
		<?php
		$api_list = get_option('argon_ai_api_list', []);
		foreach ($api_list as $api) {
			echo '<tr>';
			echo '<td>' . esc_html($api['name']) . '</td>';
			echo '<td>' . esc_html($api['endpoint']) . '</td>';
			echo '<td>***</td>';
			echo '<td>' . esc_html($api['model']) . '</td>';
			echo '<td>' . ($api['enabled'] ? '启用' : '禁用') . '</td>';
			echo '<td><button class="edit-api">编辑</button></td>';
			echo '</tr>';
		}
		?>
	</tbody>
</table>
```


### 9. 性能优化

#### 资源合并

主题支持将多个 CSS 和 JS 文件合并为单个文件：

```php
// functions.php
function argon_merge_assets() {
	$css_files = [
		'assets/css/bootstrap/bootstrap.min.css',
		'assets/css/argon.min.css',
		'style.css'
	];
	
	$merged_css = '';
	foreach ($css_files as $file) {
		$merged_css .= file_get_contents(get_template_directory() . '/' . $file);
	}
	
	file_put_contents(
		get_template_directory() . '/assets/argon_css_merged.css',
		$merged_css
	);
}
```

#### 懒加载

图片懒加载通过 Lazy Load 库实现：

```javascript
// argontheme.js
function initLazyload() {
	if (!argonConfig.lazyload) return;
	
	$('img[data-src]').lazyload({
		effect: argonConfig.lazyload_effect,
		threshold: argonConfig.lazyload_threshold,
		failure_limit: 10
	});
}
```

#### 强制刷新缓存

提供强制刷新功能，用于更新后清除客户端缓存：

```php
// functions.php
function argon_is_force_refresh_enabled() {
	$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
	if ($enabled_time == 0) {
		return false;
	}
	// 检查是否超过 1 小时
	if (time() - $enabled_time > 3600) {
		// 自动关闭
		update_option('argon_force_refresh_enabled_time', 0);
		return false;
	}
	return true;
}

function argon_get_assets_version() {
	if (argon_is_force_refresh_enabled()) {
		$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
		return $GLOBALS['theme_version'] . '.r' . $enabled_time;
	}
	return $GLOBALS['theme_version'];
}
```


#### 缓存控制

针对不同类型的资源设置不同的缓存策略：

```php
// functions.php
function argon_static_resource_headers() {
	if (is_admin() || argon_is_force_refresh_enabled()) {
		return;
	}
	
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$static_extensions = array('.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.woff', '.woff2', '.ttf', '.eot', '.ico');
	
	$is_static = false;
	foreach ($static_extensions as $ext) {
		if (strpos($request_uri, $ext) !== false) {
			$is_static = true;
			break;
		}
	}
	
	if ($is_static && !headers_sent()) {
		// 静态资源缓存 1 年
		header('Cache-Control: public, max-age=31536000, immutable', true);
	}
}
add_action('send_headers', 'argon_static_resource_headers', 30);
```

#### 移动端缓存控制

禁止移动端浏览器缓存 HTML 页面，避免内容更新不及时：

```php
// functions.php
function argon_prevent_mobile_cache() {
	if (wp_is_mobile() && !is_admin()) {
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
	}
}
add_action('send_headers', 'argon_prevent_mobile_cache');
```


### 10. 安全机制

#### HTTP 安全头部

设置安全相关的 HTTP 头部：

```php
// functions.php
function argon_security_headers() {
	if (is_admin()) {
		return;
	}
	
	if (!headers_sent()) {
		// 使用 Content-Security-Policy 替代 X-Frame-Options
		// 允许同源嵌入，防止点击劫持
		header("Content-Security-Policy: frame-ancestors 'self'", false);
		
		// 移除 X-Frame-Options（如果存在）
		header_remove('X-Frame-Options');
	}
}
add_action('send_headers', 'argon_security_headers', 20);
```

#### AJAX 请求验证

所有 AJAX 请求都需要验证 nonce：

```php
// functions.php
function ajax_post_comment() {
	// 验证 nonce
	check_ajax_referer('post_comment', 'nonce');
	
	// 验证验证码
	if (argon_is_comment_captcha_enabled()) {
		verify_captcha();
	}
	
	// 处理评论...
}
add_action('wp_ajax_ajax_post_comment', 'ajax_post_comment');
add_action('wp_ajax_nopriv_ajax_post_comment', 'ajax_post_comment');
```

#### 内容过滤

使用 WordPress 内置函数过滤用户输入：

```php
// 过滤 HTML 标签
$comment_content = wp_filter_kses($_POST['comment']);

// 转义输出
echo esc_html($user_name);
echo esc_attr($user_email);
echo esc_url($user_url);
```


#### 权限检查

在执行敏感操作前检查用户权限：

```php
// functions.php
function user_edit_comment() {
	check_ajax_referer('edit_comment', 'nonce');
	
	$comment_id = intval($_POST['id']);
	$comment = get_comment($comment_id);
	
	// 权限检查：管理员或评论作者本人
	if (!current_user_can('edit_comment', $comment_id) && 
	    $comment->user_id != get_current_user_id()) {
		wp_send_json(array('status' => 'failed', 'reason' => '权限不足'));
	}
	
	// 执行编辑操作...
}
```

#### 验证码系统

评论与部分交互功能支持验证码验证，用于减少机器人提交与恶意请求。验证码启用逻辑采用“评论独立开关 + 全局开关”组合：

- 评论验证码开关：`argon_comment_captcha_mode`，取值为 `enabled` / `disabled` / `global`
- 全局验证码开关：`argon_need_captcha`（兼容旧选项 `argon_comment_need_captcha`）

当前版本的验证码实现包含：

- **算术验证码**：通过 `captcha_calculation` 生成挑战题，`get_comment_captcha()` 输出题目，`get_comment_captcha_answer()` 用于校验答案
- **极验验证码 (Geetest 4)**：通过 `geetest_validate(...)` 校验前端提交的四个字段（lot_number/captcha_output/pass_token/gen_time）

评论场景是否启用验证码由 `argon_is_comment_captcha_enabled()` 决定：

```php
function argon_is_comment_captcha_enabled() {
    $mode = get_option('argon_comment_captcha_mode', 'global');
    if ($mode === 'enabled') {
        return true;
    } elseif ($mode === 'disabled') {
        return false;
    }
    return argon_is_captcha_enabled();
}
```


### 11. 友链管理系统

#### 功能概述

Argon 主题内置了一套独立的友链管理系统，支持友链的申请、审核、分组和展示，无需依赖第三方插件。

#### 数据存储

友链数据不使用 WordPress 的 Links Manager API（已废弃），而是存储在 `argon_friend_links` 选项中，数据结构为 JSON 数组。该数组既包含展示所需字段，也包含审核与可用性检测的状态字段：

```php
$links = get_option('argon_friend_links', array());
// 结构示例：
// [
//     {
//         'id' => 'fl_5f...',
//         'name' => '站点名称',
//         'url' => 'https://example.com',
//         'avatar' => '...',
//         'description' => '...',
//         'category' => '',
//         'email' => '',
//         'message' => '',
//         'status' => 'approved',  // pending, approved
//         'verified' => false,
//         'is_wordpress' => false,
//         'accessible' => true,
//         'last_check' => 1700000000,
//         'created_at' => 1700000000,
//         'updated_at' => 1700000000
//     },
//     ...
// ]
```

#### 核心逻辑

主要逻辑在 `functions.php` 中实现：

- `argon_add_friend_link($data)`: 添加新友链
- `argon_update_friend_link($id, $data)`: 更新友链信息
- `argon_handle_link_application_v3($post_data)`: 处理友链申请

#### 自动获取站点信息

在申请友链和后台新增友链时，主题会自动获取目标网站的基础信息。实现入口为 `argon_fetch_site_info($url)`，其返回结构包含可用性与安全拦截相关的状态字段：

```php
$info = array(
    'favicon' => '',
    'title' => '',
    'description' => '',
    'author_avatar' => '',
    'is_wordpress' => false,
    'accessible' => false,
    'blocked_by_waf' => false,
    'error_reason' => ''
);
```

核心流程包括：

1. 使用模拟浏览器的请求头与 UA 进行 `wp_remote_get`（`timeout` 约 20 秒，允许最多 5 次重定向）
2. 根据 HTTP 状态码与页面内容特征识别 WAF 拦截（403/503/关键字命中），并写入 `blocked_by_waf` 与 `error_reason`
3. 在可访问的前提下解析 `<title>`、`meta description`，并通过多种 `<link rel="icon">` 规则获取 favicon，必要时回退到 `/favicon.ico`
4. 尝试检测站点是否为 WordPress（页面特征 + `/wp-json/` 探测），若为 WordPress 则进一步尝试获取作者头像信息

#### 申请频率限制与安全校验

为了防止滥用，申请入口 `argon_handle_link_application_v3($post_data)` 依次执行以下校验：

1. **IP 黑名单**：读取 `argon_global_blocked_ips`，支持精确匹配 / 通配符 / CIDR
2. **频率限制**：按“用户标识符”限制提交频率
   - 已登录用户：`user_{ID}`
   - 未登录用户：对 `IP + User-Agent` 计算哈希并截断（用于避免单纯按 IP 误伤共享网络）
   - 计数使用 transient：`flink_apply_{identifier}`
   - 默认限制来自选项：`argon_flink_apply_limit`（默认 3）与 `argon_flink_apply_period`（默认 3600 秒）
3. **Nonce 验证**：校验字段 `argon_link_apply_nonce`，action 为 `argon_link_apply`
4. **可选验证码**：当全局验证码开启时，会调用 `argon_verify_captcha('flink')` 对友链申请场景进行验证

## 12. 短代码与小工具系统

Argon 主题内置了丰富的小工具区域和短代码支持，增强了内容展示的灵活性。

### 小工具区域 (Sidebars)

主题在 `functions.php` 的 `argon_widgets_init` 函数中注册了三个主要的小工具区域：

1. **左侧栏小工具 (`leftbar-tools`)**
   - 显示在页面左侧边栏。
   - 如果设置了小工具，会在侧栏增加一个 Tab 标签页。
   
2. **右侧栏小工具 (`rightbar-tools`)**
   - 仅在 "Argon 主题选项" 中选择 "三栏布局" 时显示。
   - 位于页面右侧。

3. **站点概览额外内容 (`leftbar-siteinfo-extra-tools`)**
   - 用于在左侧栏站点概览卡片下方添加额外内容。

**注册代码示例：**

```php
function argon_widgets_init(){
    register_sidebar(
        array(
            'name'          => __('左侧栏小工具', 'argon'),
            'id'            => 'leftbar-tools',
            // ...
        )
    );
    // ...
}
add_action('widgets_init', 'argon_widgets_init');
```

### 短代码系统 (Shortcodes)

主题定义了大量自定义短代码，用于在文章中快速插入特殊样式或功能组件。所有短代码均在 `functions.php` 中通过 `add_shortcode` 注册。

#### 主要短代码列表

- **布局与样式**：`[br]`, `[label]`, `[progressbar]`, `[checkbox]`, `[collapse]/[fold]`
- **提示框**：`[alert]`, `[admonition]`
- **功能组件**：`[timeline]`, `[github]`, `[video]`
- **元数据**：`[post_time]`, `[post_modified_time]`, `[hide_reading_time]`
- **其他**：`[friendlinks]`, `[sfriendlinks]`, `[hidden]/[spoiler]`, `[ref]`

#### 实现示例：Alert 短代码

`[alert]` 短代码用于显示带颜色的提示框，支持 `color` 和 `icon` 参数。

```php
// 注册短代码
add_shortcode('alert','shortcode_alert');

// 回调函数实现
function shortcode_alert($attr, $content=""){
    // 预处理内容
    $content = shortcode_content_preprocess($attr, $content);
    
    // 构建 HTML
    $out = "<div class='alert";
    $color = isset( $attr['color'] ) ? $attr['color'] : 'indigo';
    
    // 根据颜色参数添加对应的 CSS 类
    switch ($color){
        case 'indigo': $out .= " alert-primary"; break;
        case 'green':  $out .= " alert-success"; break;
        // ...
    }
    
    $out .= "'>";
    // ...
    return $out;
}
```

## 13. 扩展接口与 Hooks

Argon 主题提供了一些自定义的 Action 和 Filter Hooks，方便开发者在不修改核心文件的情况下扩展主题功能。

### 主要 Hooks 列表

#### Filters (过滤器)

1. **`argon_banner_title_html`**
   - **作用**：过滤 Banner 标题的 HTML 内容。
   - **位置**：`header.php`
   - **参数**：`$banner_title` (当前标题)

2. **`argon_page_background_url`**
   - **作用**：过滤页面背景图片的 URL。
   - **位置**：`header.php`
   - **参数**：`$url` (设置中的背景 URL)

3. **`argon_email_types`**
   - **作用**：过滤邮件通知类型列表，可用于修改默认邮件模板或添加新类型。
   - **位置**：`email-templates/base.php`
   - **参数**：`$types` (包含邮件类型配置的数组)

#### Actions (动作)

1. **`argon_cache_cleared`**
   - **作用**：当主题缓存被清除时触发（如保存设置时）。
   - **位置**：`functions.php`

**使用示例：**

```php
// 修改 Banner 标题后缀
add_filter('argon_banner_title_html', function($title) {
    return $title . ' <small>(Dev Mode)</small>';
});

// 自定义背景图片逻辑
add_filter('argon_page_background_url', function($url) {
    if (is_page('special')) {
        return 'https://example.com/special-bg.jpg';
    }
    return $url;
});
```

## 14. 国际化与多语言支持

Argon 主题完全支持国际化（I18n），允许开发者轻松创建多语言版本。

### 核心配置

- **Text Domain**: `argon`
- **语言包路径**: `/languages`
- **加载逻辑**: `functions.php` 中的 `theme_slug_setup` 函数。

```php
function theme_slug_setup() {
    // ...
    load_theme_textdomain('argon', get_template_directory() . '/languages');
}
```

### 翻译指南

1. **PHP 文件中**：
   使用 WordPress 标准本地化函数：
   ```php
   // 返回翻译字符串
   $text = __('Hello World', 'argon');
   
   // 直接输出翻译字符串
   _e('Settings', 'argon');
   
   // 带有上下文的翻译
   _x('Post', 'noun', 'argon');
   ```

2. **创建语言包**：
   - 当前仓库未提供 `languages/argon.pot` 模板文件。
   - 可使用 Poedit 扫描主题源代码生成新的 `.po`（Text Domain 选择 `argon`），完成翻译后生成 `.mo`。
   - 将生成的 `.po/.mo` 文件放在 `languages/` 目录下即可被加载。

## 开发指南

### 本地开发环境搭建

#### 环境要求

- PHP 7.0 或更高版本
- WordPress 4.4 或更高版本
- MySQL 5.6 或更高版本
- 支持 .htaccess 的 Web 服务器（Apache/Nginx）

#### 安装步骤

1. 安装 WordPress
2. 将主题文件夹放置到 `wp-content/themes/argon/`
3. 在 WordPress 后台启用主题
4. 访问主题设置页面进行配置

#### 开发工具推荐

- 代码编辑器：VS Code、PhpStorm
- 浏览器开发工具：Chrome DevTools、Firefox Developer Tools
- 本地服务器：XAMPP、MAMP、Local by Flywheel
- 版本控制：Git

### 调试技巧

#### 启用 WordPress 调试模式

在 `wp-config.php` 中添加：

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### 查看错误日志

错误日志位于 `wp-content/debug.log`。

#### 浏览器控制台

JavaScript 错误会显示在浏览器控制台中。使用 `console.log()` 输出调试信息：

```javascript
console.log('argonConfig:', argonConfig);
console.log('PJAX loading:', pjaxLoading);
```


#### 网络请求调试

使用浏览器开发工具的 Network 面板查看 AJAX 请求：

- 检查请求 URL 和参数
- 查看响应内容和状态码
- 分析请求耗时

#### PJAX 调试

监听 PJAX 事件输出调试信息：

```javascript
$(document).on('pjax:send', function() {
	console.log('PJAX: 开始加载');
});

$(document).on('pjax:complete', function() {
	console.log('PJAX: 加载完成');
});

$(document).on('pjax:error', function(xhr, textStatus, error) {
	console.error('PJAX 错误:', error);
});
```

### 常见开发任务

#### 添加新的主题选项

1. 在 `settings.php` 中添加表单字段：

```php
<tr>
	<th>新选项</th>
	<td>
		<input type="text" name="argon_new_option" 
		       value="<?php echo esc_attr(get_option('argon_new_option', '')); ?>" />
		<p class="description">选项说明</p>
	</td>
</tr>
```

2. 在需要使用的地方读取选项：

```php
$new_option = get_option('argon_new_option', '默认值');
```


#### 修改文章列表样式

文章列表有三种预览样式，对应三个模板文件：

- `template-parts/content-preview-1.php` - 标准卡片样式
- `template-parts/content-preview-2.php` - 大图样式
- `template-parts/content-preview-3.php` - 简洁列表样式

修改对应文件即可改变样式。

#### 添加自定义 CSS

在后台设置页面的"高级设置"选项卡中，有"自定义 CSS"文本框，输入的 CSS 会自动添加到页面中。

或者直接修改 `style.css` 文件。

#### 添加自定义 JavaScript

在后台设置页面的"高级设置"选项卡中，有"自定义 JavaScript"文本框。

或者在 `argontheme.js` 文件末尾添加代码。

#### 修改评论表单

评论表单在 `comments.php` 文件中定义。可以修改表单字段、布局和样式。

#### 创建新的页面模板

1. 创建新的 PHP 文件，例如 `custom-page.php`
2. 在文件开头添加模板声明：

```php
<?php
/*
Template Name: 自定义页面
*/
?>
```

3. 编写页面内容
4. 在 WordPress 后台编辑页面时，可以在"页面属性"中选择该模板


### 主题更新

#### 更新检查器

主题使用 `plugin-update-checker`（Puc v4）实现自动更新检查。更新源由选项 `argon_update_source` 控制，不同取值会切换到不同的元数据地址：

```php
// functions.php
require_once(get_template_directory() . '/theme-update-checker/plugin-update-checker.php');
$argon_update_source = get_option('argon_update_source');
switch ($argon_update_source) {
	case "stop":
		break;
	case "fastgit":
		Puc_v4_Factory::buildUpdateChecker('https://api.solstice23.top/argon/info.json?source=fastgit', get_template_directory() . '/functions.php', 'argon');
		break;
	case "cfworker":
		Puc_v4_Factory::buildUpdateChecker('https://api.solstice23.top/argon/info.json?source=cfworker', get_template_directory() . '/functions.php', 'argon');
		break;
	case "solstice23top":
		Puc_v4_Factory::buildUpdateChecker('https://api.solstice23.top/argon/info.json?source=0', get_template_directory() . '/functions.php', 'argon');
		break;
	case "github":
	default:
		Puc_v4_Factory::buildUpdateChecker('https://raw.githubusercontent.com/solstice23/argon-theme/master/info.json', get_template_directory() . '/functions.php', 'argon');
}
```

#### 更新流程

1. 主题通过更新检查器向 WordPress 更新系统注册可用版本信息
2. 在后台“仪表盘 → 更新”或“外观 → 主题”中显示更新提示
3. 管理员点击更新后，由 WordPress 负责下载并覆盖主题文件

#### 手动更新

1. 下载最新版本的主题文件
2. 备份当前主题文件和数据库
3. 删除旧的主题文件夹
4. 上传新的主题文件夹
5. 在后台重新激活主题
6. 检查设置是否正常

### 多语言支持

#### 翻译文件

主题支持多语言，翻译文件位于 `languages/` 目录：

- `zh_TW.po` / `zh_TW.mo` - 繁体中文
- `en_US.po` / `en_US.mo` - 英文
- `ru_RU.po` / `ru_RU.mo` - 俄文

#### 添加新语言

当前仓库未提供 `languages/argon.pot` 模板文件。新增语言时可采用两种方式之一：

1. 使用 Poedit 扫描主题源代码生成新的 `.po`（Text Domain 选择 `argon`），完成翻译后生成 `.mo`
2. 使用 WP-CLI i18n 工具自行生成 POT，再在 Poedit 中基于 POT 创建语言包

生成的 `.po/.mo` 文件放置到 `languages/` 目录即可被 `load_theme_textdomain('argon', ... )` 加载


#### 在代码中使用翻译

PHP 中使用 `__()` 函数：

```php
echo __('文本内容', 'argon');
```

JavaScript 中使用全局翻译函数：

```javascript
// header.php 中定义翻译表
var translation = {
	'确定': '<?php _e("确定", "argon"); ?>',
	'取消': '<?php _e("取消", "argon"); ?>'
};

// argontheme.js 中使用
function __(text) {
	return translation[text] || text;
}

alert(__('确定'));
```

## 常见问题

### 样式问题

#### 样式不生效

1. 检查浏览器缓存，强制刷新（Ctrl+F5）
2. 在后台设置中启用"强制刷新缓存"
3. 检查 CSS 选择器优先级
4. 使用浏览器开发工具检查元素样式

#### 夜间模式颜色异常

1. 检查 CSS 变量是否正确定义
2. 确认 `html.darkmode` 类是否正确添加
3. 检查自定义 CSS 是否覆盖了夜间模式样式

#### 响应式布局错乱

1. 检查媒体查询断点
2. 确认元素宽度设置正确
3. 使用浏览器开发工具的响应式模式测试


### 功能问题

#### PJAX 加载失败

1. 检查浏览器控制台是否有 JavaScript 错误
2. 确认目标页面的 HTML 结构正确
3. 检查 PJAX 容器选择器是否匹配
4. 尝试禁用 PJAX 功能排查问题

#### 评论发送失败

1. 检查 AJAX 请求是否成功
2. 确认 nonce 验证是否通过
3. 检查验证码是否正确
4. 查看 PHP 错误日志

#### 图片懒加载不工作

1. 确认图片标签使用了 `data-src` 属性
2. 检查 Lazy Load 库是否正确加载
3. 确认懒加载功能已在后台启用
4. 检查浏览器控制台是否有错误

#### 代码高亮显示异常

1. 确认 Highlight.js 已加载（浏览器控制台 `window.hljs` 存在）
2. 确认代码高亮已启用（`argon_enable_code_highlight` 为 true，且 `argonConfig.code_highlight.enable` 为 true）
3. 确认高亮主题 CSS 已加载（footer.php 会输出 highlight 样式链接）
4. PJAX 场景下检查 `pjax:complete` 是否触发 `highlightJsRender()`

### 性能问题

#### 页面加载缓慢

1. 启用资源合并功能
2. 开启图片懒加载
3. 使用 CDN 加速静态资源
4. 优化数据库查询
5. 启用服务器缓存（如 Redis、Memcached）
6. 压缩图片大小


#### 内存占用过高

1. 减少同时加载的文章数量
2. 优化图片尺寸
3. 清理无用的插件和主题
4. 增加 PHP 内存限制（在 `wp-config.php` 中设置 `WP_MEMORY_LIMIT`）

#### JavaScript 执行卡顿

1. 减少 DOM 操作频率
2. 使用事件委托代替多个事件监听器
3. 优化瀑布流布局计算
4. 使用 `requestAnimationFrame` 优化动画

### 兼容性问题

#### 插件冲突

1. 逐个禁用插件排查冲突源
2. 检查插件是否修改了主题依赖的 WordPress 核心功能
3. 查看插件和主题的 JavaScript 是否有命名冲突
4. 联系插件作者或主题作者寻求解决方案

#### 浏览器兼容性

主题主要支持现代浏览器：

- Chrome 60+
- Firefox 60+
- Safari 12+
- Edge 79+

不支持 IE 浏览器。如需支持旧版浏览器，需要添加 polyfill。

#### PHP 版本兼容性

主题要求 PHP 7.0+。如果服务器 PHP 版本过低，需要升级 PHP 版本。


## 最佳实践

### 代码组织

#### 模块化开发

将功能拆分为独立的模块，便于维护和复用：

```javascript
// 评论模块
let CommentModule = {
	init: function() {
		this.bindEvents();
	},
	bindEvents: function() {
		$('#comment-submit').on('click', this.submitComment);
	},
	submitComment: function() {
		// 提交评论逻辑
	}
};

// 初始化
CommentModule.init();
```

#### 避免全局污染

使用立即执行函数表达式（IIFE）封装代码：

```javascript
(function($) {
	'use strict';
	
	// 私有变量和函数
	let privateVar = 'value';
	
	function privateFunction() {
		// ...
	}
	
	// 公开接口
	window.MyModule = {
		publicMethod: function() {
			// ...
		}
	};
	
})(jQuery);
```

#### 使用命名空间

避免函数名冲突：

```javascript
let ArgonTheme = ArgonTheme || {};

ArgonTheme.Utils = {
	getCookie: function(name) {
		// ...
	},
	setCookie: function(name, value) {
		// ...
	}
};
```


### 性能优化

#### 减少 HTTP 请求

1. 合并 CSS 和 JavaScript 文件
2. 使用 CSS Sprites 合并小图标
3. 使用字体图标代替图片图标
4. 启用浏览器缓存

#### 优化资源加载

1. 异步加载非关键 JavaScript
2. 延迟加载图片和视频
3. 使用 WebP 格式图片
4. 压缩和混淆 JavaScript 代码

#### 数据库优化

1. 使用索引加速查询
2. 避免 N+1 查询问题
3. 使用对象缓存（如 Redis）
4. 定期清理无用数据

#### 前端优化

1. 减少 DOM 操作
2. 使用 CSS3 动画代替 JavaScript 动画
3. 避免强制同步布局
4. 使用虚拟滚动处理长列表

### 安全实践

#### 输入验证

1. 验证所有用户输入
2. 使用白名单而非黑名单
3. 限制输入长度和格式
4. 防止 SQL 注入和 XSS 攻击

#### 输出转义

1. 使用 `esc_html()` 转义 HTML 内容
2. 使用 `esc_attr()` 转义 HTML 属性
3. 使用 `esc_url()` 转义 URL
4. 使用 `esc_js()` 转义 JavaScript 字符串


#### 权限控制

1. 检查用户权限后再执行敏感操作
2. 使用 `current_user_can()` 验证权限
3. 为 AJAX 请求添加 nonce 验证
4. 限制文件上传类型和大小

#### 数据保护

1. 使用 HTTPS 加密传输
2. 敏感数据加密存储
3. 定期备份数据库
4. 设置合理的文件权限

### 可维护性

#### 代码注释

1. 为复杂逻辑添加注释
2. 使用 JSDoc 格式注释函数
3. 注释应说明"为什么"而非"是什么"
4. 保持注释与代码同步更新

#### 版本控制

1. 使用 Git 管理代码
2. 编写清晰的提交信息
3. 使用分支管理功能开发
4. 定期合并和清理分支

#### 文档编写

1. 维护完整的开发文档
2. 记录 API 接口和参数
3. 提供使用示例
4. 更新变更日志

#### 代码审查

1. 定期审查代码质量
2. 检查是否遵循编码规范
3. 识别潜在的性能问题
4. 确保代码可读性


## 扩展开发

### 创建子主题

如果需要大量自定义修改，建议创建子主题：

1. 创建子主题目录 `wp-content/themes/argon-child/`

2. 创建 `style.css`：

```css
/*
Theme Name: Argon Child
Template: argon
Version: 1.0.0
*/
```

3. 创建 `functions.php`：

```php
<?php
function argon_child_enqueue_styles() {
	wp_enqueue_style('argon-parent-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'argon_child_enqueue_styles');
```

4. 在 WordPress 后台启用子主题

5. 在子主题中覆盖父主题的模板文件或添加新功能

### 开发插件扩展

为主题开发配套插件：

```php
<?php
/*
Plugin Name: Argon Extension
Description: Argon 主题扩展插件
Version: 1.0.0
*/

// 添加新功能
function argon_extension_feature() {
	// 功能代码
}
add_action('init', 'argon_extension_feature');

// 添加短代码
function argon_custom_shortcode($atts) {
	return '<div class="custom-content">自定义内容</div>';
}
add_shortcode('argon_custom', 'argon_custom_shortcode');
```


### 自定义 Widget

创建自定义侧边栏小工具：

```php
<?php
class Argon_Custom_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'argon_custom_widget',
			'Argon 自定义小工具',
			array('description' => '自定义小工具描述')
		);
	}
	
	public function widget($args, $instance) {
		echo $args['before_widget'];
		echo $args['before_title'];
		echo esc_html($instance['title']);
		echo $args['after_title'];
		
		// 小工具内容
		echo '<div class="custom-widget-content">';
		echo esc_html($instance['content']);
		echo '</div>';
		
		echo $args['after_widget'];
	}
	
	public function form($instance) {
		$title = !empty($instance['title']) ? $instance['title'] : '';
		$content = !empty($instance['content']) ? $instance['content'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">标题：</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
			       name="<?php echo $this->get_field_name('title'); ?>" 
			       type="text" value="<?php echo esc_attr($title); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('content'); ?>">内容：</label>
			<textarea class="widefat" id="<?php echo $this->get_field_id('content'); ?>" 
			          name="<?php echo $this->get_field_name('content'); ?>"><?php echo esc_textarea($content); ?></textarea>
		</p>
		<?php
	}
	
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['content'] = sanitize_text_field($new_instance['content']);
		return $instance;
	}
}

function register_argon_custom_widget() {
	register_widget('Argon_Custom_Widget');
}
add_action('widgets_init', 'register_argon_custom_widget');
```


### 添加自定义短代码

创建可在文章中使用的短代码：

```php
// 简单短代码
function argon_alert_shortcode($atts, $content = null) {
	$atts = shortcode_atts(array(
		'type' => 'info',
		'title' => ''
	), $atts);
	
	$output = '<div class="alert alert-' . esc_attr($atts['type']) . '">';
	if (!empty($atts['title'])) {
		$output .= '<h4>' . esc_html($atts['title']) . '</h4>';
	}
	$output .= do_shortcode($content);
	$output .= '</div>';
	
	return $output;
}
add_shortcode('alert', 'argon_alert_shortcode');

// 使用方式：
// [alert type="warning" title="注意"]这是警告内容[/alert]
```

### 自定义文章类型

除了说说，还可以创建其他自定义文章类型：

```php
function register_custom_post_type() {
	$args = array(
		'labels' => array(
			'name' => '作品集',
			'singular_name' => '作品'
		),
		'public' => true,
		'has_archive' => true,
		'rewrite' => array('slug' => 'portfolio'),
		'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
		'menu_icon' => 'dashicons-portfolio'
	);
	register_post_type('portfolio', $args);
}
add_action('init', 'register_custom_post_type');
```


### 添加自定义字段

为文章添加额外的元数据：

```php
// 添加元框
function argon_add_custom_meta_box() {
	add_meta_box(
		'argon_custom_meta',
		'自定义字段',
		'argon_custom_meta_box_callback',
		'post',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'argon_add_custom_meta_box');

// 元框内容
function argon_custom_meta_box_callback($post) {
	wp_nonce_field('argon_save_custom_meta', 'argon_custom_meta_nonce');
	$value = get_post_meta($post->ID, '_argon_custom_field', true);
	?>
	<label for="argon_custom_field">自定义字段：</label>
	<input type="text" id="argon_custom_field" name="argon_custom_field" 
	       value="<?php echo esc_attr($value); ?>" style="width: 100%;">
	<?php
}

// 保存元数据
function argon_save_custom_meta($post_id) {
	if (!isset($_POST['argon_custom_meta_nonce'])) {
		return;
	}
	if (!wp_verify_nonce($_POST['argon_custom_meta_nonce'], 'argon_save_custom_meta')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	
	if (isset($_POST['argon_custom_field'])) {
		update_post_meta(
			$post_id,
			'_argon_custom_field',
			sanitize_text_field($_POST['argon_custom_field'])
		);
	}
}
add_action('save_post', 'argon_save_custom_meta');
```


## 测试与部署

### 本地测试

#### 功能测试

1. 测试所有页面模板是否正常显示
2. 测试评论发送、回复、编辑功能
3. 测试说说发布和显示
4. 测试 PJAX 页面切换
5. 测试夜间模式切换
6. 测试响应式布局在不同设备上的表现
7. 测试表单验证和错误提示

#### 兼容性测试

1. 在不同浏览器中测试（Chrome、Firefox、Safari、Edge）
2. 测试移动端浏览器（iOS Safari、Android Chrome）
3. 测试不同屏幕尺寸的显示效果
4. 测试与常用插件的兼容性

#### 性能测试

1. 使用 Chrome DevTools 的 Lighthouse 进行性能评分
2. 检查页面加载时间
3. 分析资源加载瀑布图
4. 测试大量数据时的性能表现

### 部署流程

#### 准备工作

1. 备份当前网站数据和数据库
2. 检查服务器环境是否满足要求
3. 准备主题文件和配置

#### 上传主题

1. 通过 FTP 上传主题文件到 `wp-content/themes/argon/`
2. 或在 WordPress 后台上传主题 ZIP 包
3. 确认文件权限正确（目录 755，文件 644）


#### 配置主题

1. 在 WordPress 后台启用主题
2. 访问主题设置页面进行配置
3. 设置主题色、布局等基本选项
4. 配置评论系统和验证码
5. 设置 AI 摘要服务（如需要）
6. 配置性能优化选项

#### 数据迁移

如果从其他主题迁移：

1. 导出原主题的设置和数据
2. 手动迁移必要的配置
3. 检查文章格式是否正常
4. 重新设置小工具和菜单
5. 测试所有功能是否正常

#### 上线检查

1. 清除所有缓存（浏览器、CDN、服务器）
2. 检查首页和主要页面显示
3. 测试评论功能
4. 检查移动端显示
5. 验证 SEO 设置
6. 测试表单提交
7. 检查 HTTPS 证书

### 维护与监控

#### 日常维护

1. 定期备份网站数据和数据库
2. 及时更新主题版本
3. 更新 WordPress 核心和插件
4. 清理垃圾评论和无用数据
5. 优化数据库表

#### 性能监控

1. 监控网站加载速度
2. 检查服务器资源使用情况
3. 分析访问日志
4. 监控错误日志
5. 定期进行性能测试


#### 安全维护

1. 定期更新所有组件
2. 检查文件完整性
3. 监控异常登录尝试
4. 定期更改管理员密码
5. 检查用户权限设置
6. 扫描恶意代码

#### 故障排查

当出现问题时：

1. 查看 WordPress 调试日志
2. 检查 PHP 错误日志
3. 查看浏览器控制台错误
4. 检查服务器错误日志
5. 禁用插件逐个排查
6. 切换到默认主题测试
7. 检查数据库连接
8. 验证文件权限

## 参考资源

### 官方文档

- WordPress Codex：https://codex.wordpress.org/
- WordPress Developer Resources：https://developer.wordpress.org/
- Bootstrap 4 文档：https://getbootstrap.com/docs/4.6/
- Argon Design System：https://www.creative-tim.com/product/argon-design-system

### 开发工具

- WordPress Debug Bar：调试插件
- Query Monitor：性能监控插件
- Theme Check：主题检查插件
- WP-CLI：WordPress 命令行工具

### 学习资源

- WordPress 主题开发手册
- PHP 官方文档
- JavaScript MDN 文档
- CSS-Tricks 网站


### 社区支持

- GitHub Issues：https://github.com/solstice23/argon-theme/issues
- WordPress 中文论坛
- Stack Overflow

## 附录

### 常用 WordPress 函数

#### 文章相关

```php
the_title()              // 输出文章标题
the_content()            // 输出文章内容
the_excerpt()            // 输出文章摘要
the_permalink()          // 输出文章链接
the_post_thumbnail()     // 输出特色图片
get_the_date()           // 获取发布日期
get_the_author()         // 获取作者名称
```

#### 评论相关

```php
comments_number()        // 输出评论数量
wp_list_comments()       // 输出评论列表
comment_form()           // 输出评论表单
get_comment_author()     // 获取评论作者
comment_text()           // 输出评论内容
```

#### 选项相关

```php
get_option()             // 获取选项值
update_option()          // 更新选项值
delete_option()          // 删除选项
add_option()             // 添加选项
```

#### 用户相关

```php
is_user_logged_in()      // 检查用户是否登录
current_user_can()       // 检查用户权限
get_current_user_id()    // 获取当前用户 ID
wp_get_current_user()    // 获取当前用户对象
```


### 常用 jQuery 方法

```javascript
// 选择器
$('#id')                 // ID 选择器
$('.class')              // 类选择器
$('element')             // 元素选择器

// DOM 操作
.html()                  // 获取或设置 HTML 内容
.text()                  // 获取或设置文本内容
.val()                   // 获取或设置表单值
.attr()                  // 获取或设置属性
.css()                   // 获取或设置样式
.addClass()              // 添加类
.removeClass()           // 移除类
.toggleClass()           // 切换类

// 事件
.on()                    // 绑定事件
.off()                   // 解绑事件
.click()                 // 点击事件
.change()                // 改变事件
.submit()                // 提交事件

// AJAX
$.ajax()                 // AJAX 请求
$.get()                  // GET 请求
$.post()                 // POST 请求
```

### 常用 CSS 类

Argon 主题基于 Bootstrap 4，可以使用以下常用类：

```css
/* 布局 */
.container               // 容器
.row                     // 行
.col-*                   // 列

/* 间距 */
.m-*                     // margin
.p-*                     // padding
.mt-*, .mb-*, .ml-*, .mr-*  // 单边间距

/* 文本 */
.text-center             // 居中对齐
.text-left               // 左对齐
.text-right              // 右对齐
.text-primary            // 主色文本
.text-muted              // 灰色文本

/* 按钮 */
.btn                     // 按钮基类
.btn-primary             // 主按钮
.btn-secondary           // 次按钮
.btn-sm, .btn-lg         // 按钮尺寸

/* 卡片 */
.card                    // 卡片
.card-body               // 卡片内容
.card-header             // 卡片头部
.card-footer             // 卡片底部
```


### 主题钩子列表

Argon 主题提供的自定义钩子：

```php
// 动作钩子
do_action('argon_before_header')      // 头部之前
do_action('argon_after_header')       // 头部之后
do_action('argon_before_content')     // 内容之前
do_action('argon_after_content')      // 内容之后
do_action('argon_before_footer')      // 底部之前
do_action('argon_after_footer')       // 底部之后
do_action('argon_before_comment')     // 评论之前
do_action('argon_after_comment')      // 评论之后

// 过滤钩子
apply_filters('argon_post_content', $content)        // 文章内容
apply_filters('argon_comment_content', $content)     // 评论内容
apply_filters('argon_excerpt_length', 200)           // 摘要长度
apply_filters('argon_theme_color', $color)           // 主题色
```

使用示例：

```php
// 在头部之后添加内容
function custom_after_header() {
	echo '<div class="custom-notice">自定义通知</div>';
}
add_action('argon_after_header', 'custom_after_header');

// 修改摘要长度
function custom_excerpt_length($length) {
	return 150;
}
add_filter('argon_excerpt_length', 'custom_excerpt_length');
```

### 版本历史

- v1.5.0 - 当前版本
  - 添加 AI 摘要功能
  - 优化性能和缓存机制
  - 改进安全性
  - 修复已知问题

- v1.4.x - 功能增强
  - 添加说说功能
  - 改进评论系统
  - 优化移动端体验

- v1.3.x - 稳定版本
  - 完善 PJAX 功能
  - 添加多种文章列表样式
  - 改进夜间模式

- v1.2.x - 初始版本
  - 基础功能实现
  - Material Design 界面
  - 响应式布局


## 結語

本文檔用於內部開發協作，聚焦於主題架構、關鍵模組、開發規範與排查路徑，目標是讓開發者能以一致的方法理解、修改與驗證功能。

在日常開發與維護中，建議遵循以下原則：

1. 以實際程式碼為準，文件同步更新，避免規格漂移
2. 變更前先界定影響範圍（頁面類型、PJAX、設定項、相依資源）
3. 變更後完成基本回歸（切換頁面、文章頁、評論、行動端、暗色模式）
4. 優先保留相容性與可回退性，避免破壞既有站點行為
5. 對安全與效能保持敏感（輸出轉義、權限檢查、資源載入與快取）

若需討論或提案，請依內部既定流程提交（需求/缺陷、代碼審查、發布/回滾），並附上可重現步驟與必要的日誌或截圖。
