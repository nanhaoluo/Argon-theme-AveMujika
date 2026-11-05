<?php
/** Keep optional editor assets out of the first-screen rendering path. */

function argon_nonblocking_listing_stylesheet($html, $handle, $href = '', $media = 'all') {
	if (is_admin() || !(is_home() || is_archive() || is_search())) {
		return $html;
	}
	if (!in_array($handle, array('Katex', 'Emojify.js', 'argon_katex'), true)
		|| !in_array($media, array('', 'all', 'screen'), true)
		|| preg_match('/\bonload\s*=/i', $html)
		|| stripos($html, '<link') === false) {
		return $html;
	}
	$target_media = $media === '' ? 'all' : $media;
	$deferred = preg_replace('/\smedia\s*=\s*([\'"])[^\'"]*\1/i', '', $html, 1);
	$deferred = preg_replace('/<link\b/i', '<link media="print" onload="this.media=\'' . $target_media . '\'"', $deferred, 1);
	return $deferred . '<noscript>' . $html . '</noscript>';
}
add_filter('style_loader_tag', 'argon_nonblocking_listing_stylesheet', 20, 4);

/** Listing templates output plain excerpts, not MerPress blocks. */
function argon_skip_listing_diagram_block_scripts() {
	if (is_admin() || !(is_home() || is_archive() || is_search()) || !class_exists('WP_Block_Type_Registry')) return;
	$block = WP_Block_Type_Registry::get_instance()->get_registered('merpress/mermaidjs');
	if (!$block) return;
	$scripts = isset($block->script_handles) ? (array) $block->script_handles : array();
	$views = isset($block->view_script_handles) ? (array) $block->view_script_handles : array();
	foreach (array_merge($scripts, $views) as $handle) {
		wp_dequeue_script($handle);
	}
	// A block actually rendered later (for example in a widget) can enqueue its
	// own dependencies again; article/detail pages retain their normal block assets.
}
add_action('wp_enqueue_scripts', 'argon_skip_listing_diagram_block_scripts', 10001);

/**
 * WP Editor.md renders diagrams with a global footer initializer. Replace that
 * initializer and its unconditional library with a small loader that also runs
 * after PJAX. MerPress block dependencies retain their own registered scripts.
 */
function argon_defer_editormd_mermaid() {
	if (is_admin()) return;
	global $wp_scripts, $wp_filter;
	$dependency = isset($wp_scripts->registered['Mermaid']) ? $wp_scripts->registered['Mermaid'] : null;
	if (!$dependency || strpos($dependency->src, '/assets/Mermaid/mermaid.min.js') === false) return;

	$hook = isset($wp_filter['wp_print_footer_scripts']) ? $wp_filter['wp_print_footer_scripts'] : null;
	$found = false;
	if ($hook && isset($hook->callbacks)) {
		foreach ($hook->callbacks as $priority => $callbacks) {
			foreach ($callbacks as $callback) {
				$function = $callback['function'];
				if (is_array($function) && is_object($function[0])
					&& is_a($function[0], 'EditormdApp\\Mermaid')
					&& $function[1] === 'mermaid_wp_footer_script') {
					remove_action('wp_print_footer_scripts', $function, $priority);
					$found = true;
				}
			}
		}
	}
	// Unknown plugin versions keep their original initializer and dependency.
	if (!$found) return;
	wp_dequeue_script('Mermaid');
	// MerPress diagrams can use newer syntax. Reuse its registered renderer for
	// PJAX visits instead of loading Editor.md's older renderer over the same global.
	$modern = isset($wp_scripts->registered['mermaid']) ? $wp_scripts->registered['mermaid'] : null;
	if ($modern && strpos($modern->src, '/merpress/public/mermaid.min.js') !== false) $dependency = $modern;
	$source = $dependency->src;
	if (!empty($dependency->ver)) $source = add_query_arg('ver', $dependency->ver, $source);
	$config = (object) array();
	if (class_exists('EditormdUtils\\Config')) {
		$raw = \EditormdUtils\Config::get_option('mermaid_config', 'editor_mermaid');
		$decoded = is_string($raw) ? json_decode($raw) : null;
		if (is_object($decoded)) $config = $decoded;
	}
	wp_enqueue_script('argon_mermaid_loader', get_template_directory_uri() . '/assets/js/argon-mermaid-loader.js', array(), argon_get_asset_file_version('assets/js/argon-mermaid-loader.js'), true);
	wp_add_inline_script('argon_mermaid_loader', 'window.argonMermaidLoaderConfig=' . wp_json_encode(array('src' => $source, 'options' => $config)) . ';', 'before');
}
add_action('wp_enqueue_scripts', 'argon_defer_editormd_mermaid', 10000);
