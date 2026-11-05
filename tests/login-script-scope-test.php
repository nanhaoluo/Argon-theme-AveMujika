<?php
/** Login-only hook cleanup must keep core and authentication callbacks intact. */
$registered = array();
$removed = array();
function add_action($hook, $callback, $priority = 10) { global $registered; $registered[$hook][] = $callback; }
function add_filter($hook, $callback) { add_action($hook, $callback); }
function remove_action($hook, $callback, $priority) { global $removed; $removed[] = array($hook, get_class($callback[0]), $callback[1], $priority); }
function check($condition, $message) { if (!$condition) throw new Exception($message); }
eval('namespace EditormdApp; class Renderer {}');
eval('namespace AuthenticationPlugin; class Renderer {}');
require dirname(__DIR__) . '/inc/login-page.php';
check(in_array('argon_login_remove_article_renderers', $registered['login_init'], true), 'Cleanup must run on the login lifecycle only');
check(!isset($registered['wp_enqueue_scripts'], $registered['admin_enqueue_scripts']), 'Frontend and editor hooks must stay unchanged');
$editor = new EditormdApp\Renderer();
$auth = new AuthenticationPlugin\Renderer();
$entries = array();
foreach (array('katex_wp_footer_scripts', 'mermaid_wp_footer_script', 'mindmap_wp_footer_script', 'emoji_enqueue_footer_js', 'prism_wp_footer_scripts') as $method) {
	$entries[] = array('function' => array($editor, $method));
}
$entries[] = array('function' => '_wp_footer_scripts');
$entries[] = array('function' => array($editor, 'authentication_assets'));
$entries[] = array('function' => array($auth, 'prism_wp_footer_scripts'));
$wp_filter = array('wp_print_footer_scripts' => (object) array('callbacks' => array(10 => $entries)));
argon_login_remove_article_renderers();
check(count($removed) === 5, 'Only the five article renderers may be removed');
foreach ($removed as $item) check($item[0] === 'wp_print_footer_scripts' && $item[1] === 'EditormdApp\\Renderer' && $item[3] === 10, 'Cleanup must preserve callback identity and priority');
$wp_filter = array();
argon_login_remove_article_renderers();
check(count($removed) === 5, 'Plugin absence must be harmless');
echo "[PASS] Login renderer scope preserves core, authentication and unrelated plugins\n";
