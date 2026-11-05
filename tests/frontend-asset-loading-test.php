<?php
namespace EditormdApp {
	class Mermaid { public function mermaid_wp_footer_script() {} }
}
namespace EditormdUtils {
	class Config {
		public static function get_option($key, $group) { return '{"theme":"dark","startOnLoad":true,"class":{}}'; }
	}
}
namespace {
	class WP_Block_Type_Registry {
		public static function get_instance() { return new self(); }
		public function get_registered($name) {
			return (object) array('script_handles' => array('mermaid', 'merpress-block'), 'view_script_handles' => array('merpress-init'));
		}
	}
	$context = 'home';
	$calls = array();
	function is_admin() { global $context; return $context === 'admin'; }
	function is_home() { global $context; return $context === 'home'; }
	function is_archive() { global $context; return $context === 'archive'; }
	function is_search() { global $context; return $context === 'search'; }
	function add_filter(...$args) {}
	function add_action(...$args) {}
	function remove_action(...$args) { global $calls; $calls[] = array('remove', $args); }
	function wp_dequeue_script($handle) { global $calls; $calls[] = array('dequeue', $handle); }
	function wp_enqueue_script(...$args) { global $calls; $calls[] = array('enqueue', $args); }
	function wp_add_inline_script(...$args) { global $calls; $calls[] = array('inline', $args); }
	function add_query_arg($key, $value, $url) { return $url . '?' . $key . '=' . $value; }
	function get_template_directory_uri() { return 'https://example.test/wp-content/themes/argon'; }
	function argon_get_asset_file_version($file) { return 'fixture-version'; }
	function wp_json_encode($value) { return json_encode($value); }
	function check($value, $message) { if (!$value) throw new \RuntimeException($message); }
	require dirname(__DIR__) . '/inc/frontend-assets.php';

	$tag = '<link rel="stylesheet" id="Katex-css" href="https://cdn.example/katex.css?x=1&amp;y=2" media="all" />';
	foreach (array('home', 'archive', 'search') as $context) {
		$result = argon_nonblocking_listing_stylesheet($tag, 'Katex');
		check(strpos($result, 'media="print"') !== false, $context . ': optional CSS still blocks rendering');
		check(strpos($result, 'onload="this.media=\'all\'"') !== false, 'stylesheet never activates after loading');
		check(strpos($result, '<noscript>' . $tag . '</noscript>') !== false, 'no-JavaScript fallback lost');
		check(strpos($result, 'x=1&amp;y=2') !== false, 'stylesheet URL changed');
		check(argon_nonblocking_listing_stylesheet($tag, 'style') === $tag, 'theme CSS must remain render blocking');
		$print = str_replace('media="all"', 'media="print"', $tag);
		check(argon_nonblocking_listing_stylesheet($print, 'Katex', '', 'print') === $print, 'print CSS must stay print-only');
		$custom = str_replace('<link ', '<link onload="custom()" ', $tag);
		check(argon_nonblocking_listing_stylesheet($custom, 'Katex') === $custom, 'custom load handler overwritten');
	}
	foreach (array('single', 'admin') as $context) {
		check(argon_nonblocking_listing_stylesheet($tag, 'Katex') === $tag, $context . ': stylesheet strategy unexpectedly changed');
	}

	$context = 'home';
	$controller = new \EditormdApp\Mermaid();
	$wp_scripts = (object) array('registered' => array('Mermaid' => (object) array('src' => 'https://cdn.example/wp-editormd/assets/Mermaid/mermaid.min.js', 'ver' => '10.2.1')));
	$wp_filter = array('wp_print_footer_scripts' => (object) array('callbacks' => array(10 => array(array('function' => array($controller, 'mermaid_wp_footer_script'))))));
	argon_defer_editormd_mermaid();
	check(in_array(array('dequeue', 'Mermaid'), $calls, true), 'unconditional Editor.md library remains queued');
	check(!in_array(array('dequeue', 'mermaid'), $calls, true), 'MerPress block dependency was removed');
	$inline = array_values(array_filter($calls, function ($call) { return $call[0] === 'inline'; }));
	check(count($inline) === 1 && strpos($inline[0][1][1], 'mermaid.min.js?ver=10.2.1') !== false, 'original library URL/version not passed to lazy loader');
	check(strpos($inline[0][1][1], '"theme":"dark"') !== false, 'diagram configuration lost');
	check(strpos($inline[0][1][1], '"class":{}') !== false, 'empty configuration objects must retain their object type');
	check($inline[0][1][2] === 'before', 'loader configuration must execute before the loader');
	check(count(array_filter($calls, function ($call) { return $call[0] === 'remove'; })) === 1, 'original footer initializer remains active');
	$calls = array();
	$wp_scripts->registered['mermaid'] = (object) array('src' => 'https://example.test/wp-content/plugins/merpress/public/mermaid.min.js', 'ver' => '11.9.0');
	argon_defer_editormd_mermaid();
	$inline = array_values(array_filter($calls, function ($call) { return $call[0] === 'inline'; }));
	check(strpos($inline[0][1][1], 'merpress\/public\/mermaid.min.js?ver=11.9.0') !== false, 'PJAX must retain the renderer needed by modern MerPress diagrams');
	$calls = array(); $wp_filter = array();
	argon_defer_editormd_mermaid();
	check($calls === array(), 'unknown plugin initializer must retain its existing library');
	$context = 'single';
	argon_skip_listing_diagram_block_scripts();
	check($calls === array(), 'article page must retain MerPress block dependencies');
	$context = 'home';
	argon_skip_listing_diagram_block_scripts();
	foreach (array('mermaid', 'merpress-block', 'merpress-init') as $handle) {
		check(in_array(array('dequeue', $handle), $calls, true), 'unused block dependency remains on home page: ' . $handle);
	}
	$header = file_get_contents(dirname(__DIR__) . '/header.php');
	check(strpos($header, 'wp_enqueue_script("mermaid"') === false, 'code highlighting still unconditionally loads diagrams');
	check(strpos($header, "argon_get_asset_file_version('assets/js/argon-motion.js')") !== false
		&& strpos($header, '"/assets/js/argon-motion.js", array(), $motion_js_version, false)') !== false,
		'scroll engine updates must invalidate the cached motion asset together with theme handlers');
	echo "Frontend asset loading contracts passed.\n";
}
