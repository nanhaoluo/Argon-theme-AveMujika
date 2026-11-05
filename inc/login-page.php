<?php
/** 保持 WordPress 原生登录、重置密码、注册和插件认证流程。 */
function argon_login_page_style() {
	if (get_option('argon_enable_login_css') !== 'true') return;
	argon_enqueue_aurora_background();
	wp_enqueue_style('argon_login_css', get_template_directory_uri() . '/login.css', array('argon-aurora'), filemtime(get_template_directory() . '/login.css'));
}
add_action('login_enqueue_scripts', 'argon_login_page_style');

function argon_login_page_background() {
	if (get_option('argon_enable_login_css') === 'true') argon_render_aurora_background();
}
add_action('login_header', 'argon_login_page_background');

function argon_login_header_url($url) {
	return get_option('argon_enable_login_css') === 'true' ? home_url('/') : $url;
}
add_filter('login_headerurl', 'argon_login_header_url');

function argon_login_header_text($text) {
	return get_option('argon_enable_login_css') === 'true' ? get_bloginfo('name') : $text;
}
add_filter('login_headertext', 'argon_login_header_text');

/** 登录流程不需要文章正文渲染器，保留 WordPress 密码与认证插件的脚本。 */
function argon_login_remove_article_renderers() {
	global $wp_filter;
	$hook = 'wp_print_footer_scripts';
	if (!isset($wp_filter[$hook]) || !isset($wp_filter[$hook]->callbacks)) return;
	$renderers = array('katex_wp_footer_scripts', 'mermaid_wp_footer_script', 'mindmap_wp_footer_script', 'emoji_enqueue_footer_js', 'prism_wp_footer_scripts');
	foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
		foreach ($callbacks as $entry) {
			$callback = $entry['function'];
			if (!is_array($callback) || count($callback) !== 2 || !in_array($callback[1], $renderers, true)) continue;
			$class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
			if (is_string($class) && strpos(ltrim($class, '\\'), 'EditormdApp\\') === 0) {
				remove_action($hook, $callback, $priority);
			}
		}
	}
}
add_action('login_init', 'argon_login_remove_article_renderers', 100);
