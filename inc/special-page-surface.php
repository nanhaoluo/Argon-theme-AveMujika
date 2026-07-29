<?php
/**
 * 自定义功能页的共享页面骨架与组件。
 *
 * `/friends/`、`/feedback` 与管理员 `/worker` 都从这里取得页面外壳、
 * 头部、卡片、徽章、表格、空状态及明暗主题规则。这些页面不是 Argon
 * 原生功能；这里只复用 Argon 的通用 card 外壳和本主题已有变量。
 *
 * @package Argon
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('argon_special_page_surface_open')) {
	/**
	 * 输出友链相关页面共用的页面外壳，并打开主内容区域。
	 */
	function argon_special_page_surface_open($page_class = 'flinks-page') {
		$page_class = sanitize_html_class($page_class);
		if ($page_class === '') $page_class = 'flinks-page';
		?>
<div class="page-information-card-container"></div>
		<?php get_sidebar(); ?>
<div id="primary" class="content-area" data-argon-special-page data-page-class="<?php echo esc_attr($page_class); ?>">
<style id="argon-special-page-surface-style">
@media screen and (min-width: 901px) {
	body.argon-special-page #leftbar_part,
	body.argon-special-page #leftbar { display: none !important; }
}
@media screen and (max-width: 900px) {
	body.argon-special-page #leftbar {
		display: block;
		position: fixed;
		background: var(--color-foreground);
		top: 0;
		left: -300px;
		height: 100vh;
		width: 280px;
		padding: 0;
		overflow-y: auto;
		overflow-x: hidden;
		z-index: 1002;
		box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07) !important;
		transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
	}
	html.leftbar-opened body.argon-special-page #leftbar { left: 0; }
	body.argon-special-page .leftbar-mobile-nav { display: flex; flex-direction: column; min-height: 100%; }
	body.argon-special-page .leftbar-desktop-content { display: none !important; }
	body.argon-special-page .leftbar-mobile-footer {
		margin-top: auto;
		position: sticky;
		bottom: 0;
		background: var(--color-foreground);
		z-index: 10;
	}
}
body.argon-special-page #primary {
	width: 100% !important;
	max-width: var(--argon-special-page-width, 1040px) !important;
	margin: 0 auto !important;
	float: none !important;
}
body.argon-special-page #content { margin-top: -50vh !important; }
body.argon-special-page #main {
	max-width: var(--argon-special-page-width, 1040px) !important;
	margin: 0 auto !important;
}
body.argon-special-page {
	--flinks-text-main: var(--color-text-deeper);
	--flinks-text-muted: #677181;
	--flinks-text-soft: #8a93a3;
	--flinks-text-faint: #a4adba;
	--flinks-surface-soft: rgba(var(--themecolor-rgbstr), 0.05);
	--flinks-surface-hover: rgba(var(--themecolor-rgbstr), 0.08);
	--flinks-input-bg: rgba(var(--themecolor-rgbstr), 0.05);
	--flinks-input-text: var(--color-text-deeper);
	--flinks-placeholder: #99a2b1;
	--flinks-success-bg: rgba(34, 197, 94, 0.12);
	--flinks-success-text: #15803d;
	--flinks-danger-bg: rgba(239, 68, 68, 0.12);
	--flinks-danger-text: #dc2626;
	--flinks-warning-bg: rgba(245, 158, 11, 0.14);
	--flinks-warning-text: #b45309;
}
html.darkmode body.argon-special-page {
	--flinks-text-muted: #c5c9d1;
	--flinks-text-soft: #a4aab5;
	--flinks-text-faint: #878e9b;
	--flinks-surface-soft: rgba(255, 255, 255, 0.05);
	--flinks-surface-hover: rgba(255, 255, 255, 0.08);
	--flinks-input-bg: rgba(255, 255, 255, 0.06);
	--flinks-placeholder: #858c99;
	--flinks-success-bg: rgba(74, 222, 128, 0.13);
	--flinks-success-text: #86efac;
	--flinks-danger-bg: rgba(248, 113, 113, 0.14);
	--flinks-danger-text: #fca5a5;
	--flinks-warning-bg: rgba(251, 191, 36, 0.14);
	--flinks-warning-text: #facc15;
}
.flinks-header-card {
	text-align: center;
	padding: 40px 24px 30px;
	margin-bottom: 16px;
	background: transparent !important;
	box-shadow: none !important;
}
.flinks-header-icon {
	width: 64px;
	height: 64px;
	margin: 0 auto 20px;
	background: var(--themecolor-gradient);
	border-radius: var(--card-radius);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28px;
	color: #fff;
	box-shadow: 0 4px 12px rgba(var(--themecolor-rgbstr), 0.2);
	transition: transform var(--animation-fast) var(--ease-standard);
}
.flinks-header-icon:hover { transform: translateY(-2px); }
.flinks-title {
	font-size: 24px;
	font-weight: 600;
	margin: 0 0 12px;
	color: var(--color-text-deeper);
	background: none !important;
}
.flinks-title::before,
.flinks-title::after { display: none !important; }
.flinks-subtitle {
	font-size: 14px;
	color: var(--flinks-text-muted);
	margin: 0 0 20px;
	line-height: 1.6;
}
.flinks-stats { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
.flinks-stat {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 8px 16px;
	background: rgba(var(--themecolor-rgbstr), 0.08);
	border-radius: var(--card-radius-sm);
	font-size: 13px;
	font-weight: 500;
	color: var(--themecolor);
	transition: all var(--animation-fast) var(--ease-standard);
}
.flinks-stat:hover { background: rgba(var(--themecolor-rgbstr), 0.12); }
.flinks-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px; }
.flinks-surface-card { margin-bottom: 16px; }
.flinks-category-card { padding: 0; overflow: hidden; }
.flinks-category-header {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 18px 24px;
	margin-bottom: 0;
	border-bottom: 1px solid var(--color-border-on-foreground);
	background: var(--flinks-surface-soft);
}
.flinks-category-title {
	font-size: 17px;
	font-weight: 600;
	margin: 0;
	color: var(--color-text-deeper);
	display: flex;
	align-items: center;
	gap: 8px;
	background: none !important;
}
.flinks-category-title::before,
.flinks-category-title::after { display: none !important; }
.flinks-category-title i { color: var(--themecolor); font-size: 16px; }
.flinks-category-count {
	margin-left: auto;
	display: inline-flex;
	align-items: center;
	gap: 5px;
	font-size: 12px;
	font-weight: 600;
	color: var(--themecolor);
	background: rgba(var(--themecolor-rgbstr), 0.1);
	padding: 5px 12px;
	border-radius: var(--card-radius-sm);
	white-space: nowrap;
}
.flinks-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
	gap: 4px;
	padding: 14px;
}
.flinks-badge {
	display: inline-flex;
	align-items: center;
	gap: 7px;
	padding: 6px 10px;
	border-radius: var(--card-radius-sm);
	background: var(--flinks-surface-hover);
	color: var(--flinks-text-muted);
	font-size: 12px;
	font-weight: 600;
}
.flinks-badge.is-online,
.flinks-badge.is-ok,
.flinks-badge.is-complete { background: var(--flinks-success-bg); color: var(--flinks-success-text); }
.flinks-badge.is-warning,
.flinks-badge.is-running,
.flinks-badge.is-deferred,
.flinks-badge.is-degraded { background: var(--flinks-warning-bg); color: var(--flinks-warning-text); }
.flinks-badge.is-error { background: var(--flinks-danger-bg); color: var(--flinks-danger-text); }
.flinks-worker-live i { font-size: 9px; }
.flinks-surface-meta { justify-content: flex-start; margin: 0 0 16px; }
.flinks-surface-meta .flinks-stat { color: var(--flinks-text-muted); }
.flinks-metric-card {
	min-height: 96px;
	padding: 16px 18px;
	border-radius: var(--card-radius-sm);
	background: var(--flinks-surface-soft);
	border: 1px solid transparent;
	transition: all var(--animation-fast) var(--ease-standard);
}
.flinks-metric-card:hover {
	background: var(--flinks-surface-hover);
	border-color: rgba(var(--themecolor-rgbstr), 0.16);
	transform: translateY(-2px);
}
.flinks-metric-label { color: var(--flinks-text-muted); font-size: 12px; overflow-wrap: anywhere; }
.flinks-metric-value { margin-top: 9px; color: var(--color-text-deeper); font-size: 24px; font-weight: 700; overflow-wrap: anywhere; }
.flinks-metric-card[data-state="ok"] .flinks-metric-value { color: var(--flinks-success-text); }
.flinks-metric-card[data-state="running"] .flinks-metric-value,
.flinks-metric-card[data-state="deferred"] .flinks-metric-value,
.flinks-metric-card[data-state="degraded"] .flinks-metric-value { color: var(--flinks-warning-text); }
.flinks-metric-card[data-state="error"] .flinks-metric-value { color: var(--flinks-danger-text); }
.flinks-table-wrap { width: 100%; overflow: auto; }
.flinks-table { width: 100%; min-width: 880px; border-collapse: collapse; font-size: 13px; }
.flinks-table th,
.flinks-table td {
	padding: 11px 12px;
	border-bottom: 1px solid var(--color-border-on-foreground);
	text-align: left;
	vertical-align: top;
}
.flinks-table th {
	position: sticky;
	top: 0;
	background: var(--flinks-surface-soft);
	color: var(--flinks-text-muted);
	font-weight: 600;
}
.flinks-table tr:last-child td { border-bottom: 0; }
.flinks-table td { max-width: 260px; color: var(--flinks-text-main); overflow-wrap: anywhere; }
.flinks-empty { padding: 60px 24px; text-align: center; }
.flinks-empty i { display: block; margin-bottom: 16px; color: var(--color-border); font-size: 48px; }
.flinks-empty p { margin: 0; color: var(--flinks-text-muted); font-size: 15px; }
.flinks-table .flinks-empty { padding: 24px; color: var(--flinks-text-muted); }
.flinks-surface-details {
	max-height: 520px;
	margin: 0;
	padding: 16px;
	overflow: auto;
	background: var(--flinks-surface-soft);
	color: var(--flinks-text-main);
	font: 12px/1.6 Consolas, Monaco, monospace;
	white-space: pre-wrap;
	overflow-wrap: anywhere;
}
html.darkmode body.argon-special-page .flinks-surface-details {
	background: rgba(0, 0, 0, 0.18);
	color: #dbeafe;
}
@media (max-width: 768px) {
	.flinks-header-card { padding: 30px 16px 24px; }
	.flinks-list { gap: 12px; }
	.flinks-category-header { padding: 15px 16px; }
	.flinks-category-title { min-width: 0; font-size: 16px; }
	.flinks-grid { grid-template-columns: 1fr; }
	.flinks-surface-meta { justify-content: center; }
}
</style>
<script data-pjax>
(function() {
	'use strict';
	var rootSelector = '[data-argon-special-page]';
	var namespace = '.argonSpecialPageSurface';
	function syncSurfaceClass() {
		var root = document.querySelector(rootSelector);
		var previousClass = document.body.getAttribute('data-argon-special-page-class');
		if (previousClass) document.body.classList.remove(previousClass);
		document.body.classList.toggle('argon-special-page', !!root);
		if (root) {
			var pageClass = root.getAttribute('data-page-class');
			if (pageClass) {
				document.body.classList.add(pageClass);
				document.body.setAttribute('data-argon-special-page-class', pageClass);
			}
		} else {
			document.body.removeAttribute('data-argon-special-page-class');
		}
	}
	syncSurfaceClass();
	if (typeof jQuery !== 'undefined') {
		var $ = jQuery;
		$(document).off('pjax:complete' + namespace + ' pjax:end' + namespace + ' pjax:popstate' + namespace);
		$(document).on('pjax:complete' + namespace + ' pjax:end' + namespace, function() {
			syncSurfaceClass();
			if (!document.querySelector(rootSelector)) {
				$(document).off('pjax:complete' + namespace + ' pjax:end' + namespace + ' pjax:popstate' + namespace);
			}
		});
		$(document).on('pjax:popstate' + namespace, function() {
			setTimeout(syncSurfaceClass, 50);
		});
	}
})();
</script>
	<main id="main" class="site-main" role="main">
		<?php
	}
}

if (!function_exists('argon_special_page_surface_header')) {
	/**
	 * 输出共享页头。
	 *
	 * @param array $args icon、title、subtitle 与 stats。
	 */
	function argon_special_page_surface_header($args = array()) {
		$icon = isset($args['icon']) ? sanitize_html_class($args['icon']) : 'link';
		$title = isset($args['title']) ? (string)$args['title'] : '';
		$subtitle = isset($args['subtitle']) ? (string)$args['subtitle'] : '';
		$stats = isset($args['stats']) && is_array($args['stats']) ? $args['stats'] : array();
		?>
<header class="flinks-header-card">
	<div class="flinks-header-icon"><i class="fa fa-<?php echo esc_attr($icon); ?>" aria-hidden="true"></i></div>
	<h1 class="flinks-title"><?php echo esc_html($title); ?></h1>
	<?php if ($subtitle !== '') : ?><p class="flinks-subtitle"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
	<?php if ($stats) : ?>
	<div class="flinks-stats">
		<?php foreach ($stats as $stat) :
			$stat = is_array($stat) ? $stat : array('text' => $stat);
			$stat_icon = isset($stat['icon']) ? sanitize_html_class($stat['icon']) : '';
			$stat_id = isset($stat['id']) ? sanitize_html_class($stat['id']) : '';
			$text_id = isset($stat['text_id']) ? sanitize_html_class($stat['text_id']) : '';
			$classes = array('flinks-stat');
			if (!empty($stat['class'])) {
				foreach (preg_split('/\s+/', (string)$stat['class']) as $class_name) {
					$class_name = sanitize_html_class($class_name);
					if ($class_name !== '') $classes[] = $class_name;
				}
			}
			?>
		<div class="<?php echo esc_attr(implode(' ', array_unique($classes))); ?>"<?php if ($stat_id !== '') : ?> id="<?php echo esc_attr($stat_id); ?>"<?php endif; ?><?php if (!empty($stat['aria_live'])) : ?> role="status" aria-live="<?php echo esc_attr($stat['aria_live']); ?>"<?php endif; ?>>
			<?php if ($stat_icon !== '') : ?><i class="fa fa-<?php echo esc_attr($stat_icon); ?>" aria-hidden="true"></i><?php endif; ?>
			<span<?php if ($text_id !== '') : ?> id="<?php echo esc_attr($text_id); ?>"<?php endif; ?>><?php echo esc_html(isset($stat['text']) ? $stat['text'] : ''); ?></span>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</header>
		<?php
	}
}

if (!function_exists('argon_special_page_surface_empty')) {
	/**
	 * 输出共用空状态卡片。
	 */
	function argon_special_page_surface_empty($message, $icon = 'inbox', $attributes = array()) {
		$id = isset($attributes['id']) ? sanitize_html_class($attributes['id']) : '';
		?>
<article class="post card shadow-sm bg-white border-0 flinks-surface-card flinks-empty"<?php if ($id !== '') : ?> id="<?php echo esc_attr($id); ?>"<?php endif; ?>>
	<i class="fa fa-<?php echo esc_attr(sanitize_html_class($icon)); ?>" aria-hidden="true"></i>
	<p><?php echo esc_html($message); ?></p>
</article>
		<?php
	}
}

if (!function_exists('argon_special_page_surface_close')) {
	/**
	 * 关闭共享主内容区域。
	 */
	function argon_special_page_surface_close() {
		?>
	</main>
</div>
		<?php
	}
}
