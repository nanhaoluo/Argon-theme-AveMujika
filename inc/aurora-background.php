<?php
/** 404 和原生登录页共用的背景和控件主题色。 */
function argon_enqueue_aurora_background() {
	$base = get_template_directory_uri();
	$css = 'assets/css/aurora-background.css';
	$js = 'assets/js/aurora-background.js';
	wp_enqueue_style('argon-aurora', $base . '/' . $css, array(), argon_get_asset_file_version($css));
	wp_enqueue_script('argon-aurora', $base . '/' . $js, array(), argon_get_asset_file_version($js), true);

	$color = get_option('argon_theme_color', '#5e72e4');
	$color = is_string($color) ? sanitize_hex_color($color) : '';
	$custom = isset($_COOKIE['argon_custom_theme_color']) ? $_COOKIE['argon_custom_theme_color'] : '';
	if (get_option('argon_show_customize_theme_color_picker') !== 'false' && is_string($custom)) {
		$color = sanitize_hex_color($custom) ?: $color;
	}
	$color = $color ?: '#5e72e4';
	// Pick a readable label even when the visitor selects a very light or dark accent.
	$hex = substr($color, 1);
	if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	$linear = array_map(static function ($channel) {
		$value = hexdec($channel) / 255;
		return $value <= 0.04045 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
	}, str_split($hex, 2));
	$luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
	$label = $luminance > 0.179 ? '#000' : '#fff';
	wp_add_inline_style('argon-aurora', ":root { --themecolor: $color; --argon-on-accent: $label; }");
}

function argon_render_aurora_background() {
	echo '<div class="argon-aurora" aria-hidden="true"></div>';
}
