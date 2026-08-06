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
	 * 输出自定义功能页共用的页面外壳，并打开主内容区域。
	 */
	function argon_special_page_surface_open($page_class = 'argon-special-page-view') {
		$page_class = sanitize_html_class($page_class);
		if ($page_class === '') $page_class = 'argon-special-page-view';
		?>
<div class="page-information-card-container"></div>
		<?php get_sidebar(); ?>
<div id="primary" class="content-area" data-argon-special-page data-page-class="<?php echo esc_attr($page_class); ?>">
<style id="argon-special-page-surface-style">
@media screen and (min-width: 901px) {
	body.argon-special-page #leftbar_part,
	body.argon-special-page #leftbar { display: none !important; }
	body.argon-special-page #footer {
		width: calc(100% - 40px) !important;
		max-width: calc(var(--argon-special-page-width, 1040px) - 40px) !important;
		margin-left: auto !important;
		margin-right: auto !important;
	}
}
@media screen and (max-width: 900px) {
	body.argon-special-page #leftbar {
		display: block;
		position: fixed;
		background: var(--color-foreground);
		top: 0;
		left: 0;
		height: 100vh;
		width: 280px;
		padding: 0;
		overflow-y: auto;
		overflow-x: hidden;
		z-index: 1002;
		box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07) !important;
		transform: translateX(-100%);
		transition: transform var(--motion-fast-duration) var(--motion-ease-exit), box-shadow var(--motion-fast-duration) var(--motion-ease-exit);
	}
	html.leftbar-opened body.argon-special-page #leftbar {
		transform: translateX(0);
		transition-timing-function: var(--motion-ease-enter);
	}
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
	--argon-special-page-text-main: var(--color-text-deeper);
	--argon-special-page-text-muted: #677181;
	--argon-special-page-text-soft: #8a93a3;
	--argon-special-page-text-faint: #a4adba;
	--argon-special-page-surface-soft: rgba(var(--themecolor-rgbstr), 0.05);
	--argon-special-page-surface-hover: rgba(var(--themecolor-rgbstr), 0.08);
	--argon-special-page-input-bg: rgba(var(--themecolor-rgbstr), 0.05);
	--argon-special-page-input-text: var(--color-text-deeper);
	--argon-special-page-placeholder: #99a2b1;
	--argon-special-page-success-bg: rgba(34, 197, 94, 0.12);
	--argon-special-page-success-text: #15803d;
	--argon-special-page-danger-bg: rgba(239, 68, 68, 0.12);
	--argon-special-page-danger-text: #dc2626;
	--argon-special-page-warning-bg: rgba(245, 158, 11, 0.14);
	--argon-special-page-warning-text: #b45309;
}
html.darkmode body.argon-special-page {
	--argon-special-page-text-muted: #c5c9d1;
	--argon-special-page-text-soft: #a4aab5;
	--argon-special-page-text-faint: #878e9b;
	--argon-special-page-surface-soft: rgba(255, 255, 255, 0.05);
	--argon-special-page-surface-hover: rgba(255, 255, 255, 0.08);
	--argon-special-page-input-bg: rgba(255, 255, 255, 0.06);
	--argon-special-page-placeholder: #858c99;
	--argon-special-page-success-bg: rgba(74, 222, 128, 0.13);
	--argon-special-page-success-text: #86efac;
	--argon-special-page-danger-bg: rgba(248, 113, 113, 0.14);
	--argon-special-page-danger-text: #fca5a5;
	--argon-special-page-warning-bg: rgba(251, 191, 36, 0.14);
	--argon-special-page-warning-text: #facc15;
}
.argon-special-page-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 32px;
	padding: 22px 4px 20px;
	margin-bottom: 16px;
	border-bottom: 1px solid var(--color-border-on-foreground);
	background: transparent !important;
	box-shadow: none !important;
	text-align: left;
}
.argon-special-page-header-copy { min-width: 0; flex: 1; }
.argon-special-page-title {
	margin: 0;
	color: var(--color-text-deeper);
	font-size: 25px;
	font-weight: 650;
	line-height: 1.25;
	letter-spacing: 0;
	background: none !important;
}
.argon-special-page-title::before,
.argon-special-page-title::after { display: none !important; }
.argon-special-page-subtitle {
	max-width: 640px;
	margin: 6px 0 0;
	color: var(--argon-special-page-text-muted);
	font-size: 13px;
	line-height: 1.6;
}
.argon-special-page-stats { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
.argon-special-page-stat {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 8px 16px;
	background: rgba(var(--themecolor-rgbstr), 0.08);
	border-radius: var(--card-radius-sm);
	font-size: 13px;
	font-weight: 500;
	color: var(--themecolor);
	white-space: nowrap;
	transition: all var(--animation-fast) var(--ease-standard);
}
.argon-special-page-stat-value { font-size: 1.05em; font-weight: 700; }
.argon-special-page-stat:hover { background: rgba(var(--themecolor-rgbstr), 0.12); }
.argon-special-page-header .argon-special-page-stats { align-items: center; justify-content: flex-end; flex-shrink: 0; gap: 0; flex-wrap: nowrap; }
.argon-special-page-header .argon-special-page-stat {
	align-items: baseline;
	gap: 5px;
	padding: 0 16px;
	background: transparent;
	border-radius: 0;
	color: var(--argon-special-page-text-muted);
	transition: color var(--animation-fast) var(--ease-standard);
}
.argon-special-page-header .argon-special-page-stat:first-child { padding-left: 0; }
.argon-special-page-header .argon-special-page-stat:last-child { padding-right: 0; }
.argon-special-page-header .argon-special-page-stat + .argon-special-page-stat { border-left: 1px solid var(--color-border-on-foreground); }
.argon-special-page-header .argon-special-page-stat-value { color: var(--color-text-deeper); font-size: 18px; line-height: 1; }
.argon-special-page-header .argon-special-page-stat:hover { background: transparent; color: var(--color-text-deeper); }
.argon-special-page-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px; }
.argon-special-page-card { margin-bottom: 16px; }
.argon-special-page-section { padding: 0; overflow: hidden; }
.argon-special-page-section-header {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 18px 24px;
	margin-bottom: 0;
	border-bottom: 1px solid var(--color-border-on-foreground);
	background: var(--argon-special-page-surface-soft);
}
.argon-special-page-section-title {
	font-size: 17px;
	font-weight: 600;
	margin: 0;
	color: var(--color-text-deeper);
	display: flex;
	align-items: center;
	gap: 8px;
	background: none !important;
}
.argon-special-page-section-title::before,
.argon-special-page-section-title::after { display: none !important; }
.argon-special-page-section-title i { color: var(--themecolor); font-size: 16px; }
.argon-special-page-section-count {
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
.argon-special-page-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
	gap: 4px;
	padding: 14px;
}
.argon-special-page-badge {
	display: inline-flex;
	align-items: center;
	gap: 7px;
	padding: 6px 10px;
	border-radius: var(--card-radius-sm);
	background: var(--argon-special-page-surface-hover);
	color: var(--argon-special-page-text-muted);
	font-size: 12px;
	font-weight: 600;
}
.argon-special-page-badge.is-online,
.argon-special-page-badge.is-ok,
.argon-special-page-badge.is-complete { background: var(--argon-special-page-success-bg); color: var(--argon-special-page-success-text); }
.argon-special-page-badge.is-warning,
.argon-special-page-badge.is-running,
.argon-special-page-badge.is-deferred,
.argon-special-page-badge.is-degraded { background: var(--argon-special-page-warning-bg); color: var(--argon-special-page-warning-text); }
.argon-special-page-badge.is-error { background: var(--argon-special-page-danger-bg); color: var(--argon-special-page-danger-text); }
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-online,
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-ok,
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-complete { color: var(--argon-special-page-success-text); }
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-warning,
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-running,
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-deferred,
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-degraded { color: var(--argon-special-page-warning-text); }
.argon-special-page-header .argon-special-page-stat.argon-special-page-badge.is-error { color: var(--argon-special-page-danger-text); }
.argon-special-page-meta { justify-content: flex-start; margin: 0 0 16px; }
.argon-special-page-meta .argon-special-page-stat { color: var(--argon-special-page-text-muted); }
.argon-special-page-metric {
	min-height: 96px;
	padding: 16px 18px;
	border-radius: var(--card-radius-sm);
	background: var(--argon-special-page-surface-soft);
	border: 1px solid transparent;
	transition: all var(--animation-fast) var(--ease-standard);
}
.argon-special-page-metric:hover {
	background: var(--argon-special-page-surface-hover);
	border-color: rgba(var(--themecolor-rgbstr), 0.16);
	transform: translateY(-2px);
}
.argon-special-page-metric-label { color: var(--argon-special-page-text-muted); font-size: 12px; overflow-wrap: break-word; word-break: normal; }
.argon-special-page-metric-value { margin-top: 9px; color: var(--color-text-deeper); font-size: 24px; font-weight: 700; overflow-wrap: break-word; word-break: normal; }
.argon-special-page-metric[data-state="ok"] .argon-special-page-metric-value { color: var(--argon-special-page-success-text); }
.argon-special-page-metric[data-state="running"] .argon-special-page-metric-value,
.argon-special-page-metric[data-state="deferred"] .argon-special-page-metric-value,
.argon-special-page-metric[data-state="degraded"] .argon-special-page-metric-value { color: var(--argon-special-page-warning-text); }
.argon-special-page-metric[data-state="error"] .argon-special-page-metric-value { color: var(--argon-special-page-danger-text); }
.argon-special-page-table-wrap { width: 100%; overflow: auto; }
.argon-special-page-table { width: 100%; min-width: 880px; border-collapse: collapse; font-size: 13px; }
.argon-special-page-table th,
.argon-special-page-table td {
	padding: 11px 12px;
	border-bottom: 1px solid var(--color-border-on-foreground);
	text-align: left;
	vertical-align: top;
}
.argon-special-page-table th {
	position: sticky;
	top: 0;
	background: var(--argon-special-page-surface-soft);
	color: var(--argon-special-page-text-muted);
	font-weight: 600;
}
.argon-special-page-table tr:last-child td { border-bottom: 0; }
.argon-special-page-table td { max-width: 260px; color: var(--argon-special-page-text-main); overflow-wrap: break-word; word-break: normal; }
.argon-special-page-empty { padding: 60px 24px; text-align: center; }
.argon-special-page-empty i { display: block; margin-bottom: 16px; color: var(--color-border); font-size: 48px; }
.argon-special-page-empty p { margin: 0; color: var(--argon-special-page-text-muted); font-size: 15px; }
.argon-special-page-table .argon-special-page-empty { padding: 24px; color: var(--argon-special-page-text-muted); }
.argon-special-page-details {
	max-height: 520px;
	margin: 0;
	padding: 16px;
	overflow: auto;
	background: var(--argon-special-page-surface-soft);
	color: var(--argon-special-page-text-main);
	font: 12px/1.6 Consolas, Monaco, monospace;
	white-space: pre-wrap;
	overflow-wrap: break-word;
}
html.darkmode body.argon-special-page .argon-special-page-details {
	background: rgba(0, 0, 0, 0.18);
	color: #dbeafe;
}
@media (max-width: 768px) {
	.argon-special-page-header { display: block; padding: 18px 2px 16px; margin-bottom: 12px; }
	.argon-special-page-title { font-size: 22px; }
	.argon-special-page-subtitle { margin-top: 5px; font-size: 12px; }
	.argon-special-page-header .argon-special-page-stats { justify-content: flex-start; margin-top: 15px; }
	.argon-special-page-header .argon-special-page-stat { padding-right: 12px; padding-left: 12px; font-size: 12px; }
	.argon-special-page-header .argon-special-page-stat:first-child { padding-left: 0; }
	.argon-special-page-header .argon-special-page-stat:last-child { padding-right: 0; }
	.argon-special-page-header .argon-special-page-stat-value { font-size: 16px; }
	.argon-special-page-list { gap: 12px; }
	.argon-special-page-section-header { padding: 15px 16px; }
	.argon-special-page-section-title { min-width: 0; font-size: 16px; }
	.argon-special-page-grid { grid-template-columns: 1fr; }
	.argon-special-page-meta { justify-content: center; }
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
	 * @param array $args title、subtitle 与 stats。
	 */
	function argon_special_page_surface_header($args = array()) {
		$title = isset($args['title']) ? (string)$args['title'] : '';
		$subtitle = isset($args['subtitle']) ? (string)$args['subtitle'] : '';
		$title_id = isset($args['title_id']) ? sanitize_html_class($args['title_id']) : '';
		$stats = isset($args['stats']) && is_array($args['stats']) ? $args['stats'] : array();
		?>
<header class="argon-special-page-header">
	<div class="argon-special-page-header-copy">
		<h1 class="argon-special-page-title"<?php if ($title_id !== '') : ?> id="<?php echo esc_attr($title_id); ?>"<?php endif; ?>><?php echo esc_html($title); ?></h1>
		<?php if ($subtitle !== '') : ?><p class="argon-special-page-subtitle"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
	</div>
	<?php if ($stats) : ?>
	<div class="argon-special-page-stats">
		<?php foreach ($stats as $stat) :
			$stat = is_array($stat) ? $stat : array('text' => $stat);
			$stat_id = isset($stat['id']) ? sanitize_html_class($stat['id']) : '';
			$text_id = isset($stat['text_id']) ? sanitize_html_class($stat['text_id']) : '';
			$value_id = isset($stat['value_id']) ? sanitize_html_class($stat['value_id']) : '';
			$has_value = array_key_exists('value', $stat);
			$classes = array('argon-special-page-stat');
			if (!empty($stat['class'])) {
				foreach (preg_split('/\s+/', (string)$stat['class']) as $class_name) {
					$class_name = sanitize_html_class($class_name);
					if ($class_name !== '') $classes[] = $class_name;
				}
			}
			?>
		<div class="<?php echo esc_attr(implode(' ', array_unique($classes))); ?>"<?php if ($stat_id !== '') : ?> id="<?php echo esc_attr($stat_id); ?>"<?php endif; ?><?php if (!empty($stat['aria_live'])) : ?> role="status" aria-live="<?php echo esc_attr($stat['aria_live']); ?>"<?php endif; ?>>
			<?php if ($has_value) : ?>
				<?php if (isset($stat['label'])) : ?><span class="argon-special-page-stat-label"><?php echo esc_html($stat['label']); ?></span><?php endif; ?>
				<strong class="argon-special-page-stat-value"<?php if ($value_id !== '') : ?> id="<?php echo esc_attr($value_id); ?>"<?php endif; ?>><?php echo esc_html($stat['value']); ?></strong>
				<?php if (isset($stat['suffix'])) : ?><span class="argon-special-page-stat-suffix"><?php echo esc_html($stat['suffix']); ?></span><?php endif; ?>
			<?php else : ?>
				<span<?php if ($text_id !== '') : ?> id="<?php echo esc_attr($text_id); ?>"<?php endif; ?>><?php echo esc_html(isset($stat['text']) ? $stat['text'] : ''); ?></span>
			<?php endif; ?>
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
<article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-empty"<?php if ($id !== '') : ?> id="<?php echo esc_attr($id); ?>"<?php endif; ?>>
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
