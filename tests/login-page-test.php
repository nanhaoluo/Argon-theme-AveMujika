<?php
$options = array();
$styles = array();
$scripts = array();
$hooks = array();
function get_option($key, $default = '') { global $options; return $options[$key] ?? $default; }
function add_action($hook, $callback) { global $hooks; $hooks[$hook] = $callback; }
function add_filter($hook, $callback) { add_action($hook, $callback); }
function get_template_directory() { return dirname(__DIR__); }
function get_template_directory_uri() { return 'https://example.test/theme'; }
function home_url($path) { return 'https://example.test' . $path; }
function get_bloginfo($field) { return '编程小记'; }
function wp_enqueue_style($handle, ...$args) { global $styles; $styles[$handle] = $args; }
function wp_add_inline_style($handle, $value) { global $inline_styles; $inline_styles[$handle] = $value; }
function login_palette() { global $inline_styles; argon_login_page_style(); return $inline_styles['argon-aurora']; }
function wp_enqueue_script($handle, ...$args) { global $scripts; $scripts[$handle] = $args; }
function argon_get_asset_file_version($file) { return filemtime(get_template_directory() . '/' . $file); }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES); }
function sanitize_hex_color($value) { return preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/iD', $value) ? $value : ''; }
function check($result, $message) { if (!$result) throw new Exception($message); }
function login_background() {
	global $hooks;
	ob_start();
	call_user_func($hooks['login_header']);
	return ob_get_clean();
}
require dirname(__DIR__) . '/inc/aurora-background.php';
require dirname(__DIR__) . '/inc/login-page.php';
check($hooks['login_enqueue_scripts'] === 'argon_login_page_style', '登录样式应通过标准资源钩子加载');
argon_login_page_style();
check(!$styles && !$scripts && login_background() === '' && argon_login_header_url('original') === 'original' && argon_login_header_text('WordPress') === 'WordPress', '关闭美化后保留原行为');
$options['argon_enable_login_css'] = 'true';
argon_login_page_style();
check($styles['argon_login_css'][0] === 'https://example.test/theme/login.css' && is_int($styles['argon_login_css'][2]), '样式 URL 与缓存版本应有效');
check($styles['argon_login_css'][1] === array('argon-aurora')
	&& $styles['argon-aurora'][0] === 'https://example.test/theme/assets/css/aurora-background.css'
	&& $scripts['argon-aurora'][0] === 'https://example.test/theme/assets/js/aurora-background.js', '登录页应加载极光背景资源');
check(argon_login_header_url('original') === 'https://example.test/' && argon_login_header_text('WordPress') === '编程小记', '启用后 Logo 应显示站点名称并返回首页');
check(strpos(login_background(), 'class="argon-aurora"') !== false, '启用美化后应输出背景');
$options['argon_theme_color'] = '#197e50';
check(strpos(login_palette(), '--themecolor: #197e50') !== false, '背景应采用站点主题色');
$_COOKIE['argon_custom_theme_color'] = '#af3274';
check(strpos(login_palette(), '--themecolor: #af3274') !== false, '允许自定义时采用访客主题色');
$options['argon_show_customize_theme_color_picker'] = 'false';
check(strpos(login_palette(), '--themecolor: #197e50') !== false, '关闭自定义后应采用站点主题色');
unset($options['argon_show_customize_theme_color_picker']);
$_COOKIE['argon_custom_theme_color'] = '#000; background: red';
check(strpos(login_palette(), '--themecolor: #197e50') !== false, '无效访客颜色不得进入样式');
$options['argon_theme_color'] = '';
check(strpos(login_palette(), '--themecolor: #5e72e4') !== false, '无可用主题色时采用默认值');
echo "[PASS] Login enqueue and branding behavior\n";
