<?php
/** WordPress 桩环境：渲染真实设置模板供浏览器回归使用。 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL & ~E_DEPRECATED);
$fixture_root = dirname(__DIR__, 2);
$fixture_assets = array();
function __($value, $domain = '') { return $value; }
function _e($value, $domain = '') { echo $value; }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function esc_textarea($value) { return esc_attr($value); }
function esc_url($value) { return esc_attr($value); }
function esc_js($value) { return str_replace(array('\\', "'", '"', "\n", "\r"), array('\\\\', "\\'", '\\"', '\\n', ''), $value); }
function esc_html__($value, $domain = '') { return esc_html($value); }
function esc_attr_e($value, $domain = '') { echo esc_attr($value); }
function esc_html_e($value, $domain = '') { echo esc_html($value); }
function get_option($key, $default = '') { return $default; }
function bloginfo($key) { echo get_bloginfo($key); }
function get_bloginfo($key) { return $key === 'template_url' ? '/theme' : 'Fixture blog'; }
function get_template_directory() { global $fixture_root; return $fixture_root; }
function get_template_directory_uri() { return '/theme'; }
function admin_url($value = '') { return '/wp-admin/' . $value; }
function home_url($value = '') { return 'https://example.test' . $value; }
function wp_create_nonce($key) { return 'fixture-nonce'; }
function wp_nonce_field($action, $name) { echo '<input type="hidden" name="' . esc_attr($name) . '" value="fixture-nonce">'; }
function add_action(...$args) {}
function wp_enqueue_script($handle, $src = '', $deps = array(), $version = false, $footer = false) { global $fixture_assets; if ($src) $fixture_assets[] = '<script src="' . esc_attr($src) . '"></script>'; }
function wp_enqueue_media() {}
function wp_enqueue_style($handle, $src, ...$args) { global $fixture_assets; $fixture_assets[] = '<link rel="stylesheet" href="' . esc_attr($src) . '">'; }
function wp_localize_script($handle, $name, $data) { global $fixture_assets; $fixture_assets[] = '<script>window.' . $name . '=' . json_encode($data) . ';</script>'; }
function checked($value, $expected = true, $echo = true) { $result = (string)$value === (string)$expected ? 'checked' : ''; if ($echo) echo $result; return $result; }
function selected($value, $expected = true, $echo = true) { $result = (string)$value === (string)$expected ? 'selected' : ''; if ($echo) echo $result; return $result; }
function get_categories($args) { return array((object) array('name' => '示例分类', 'term_id' => 4)); }
function get_post_types($args, $output) { return array((object) array('name' => 'post', 'label' => '文章'), (object) array('name' => 'page', 'label' => '页面')); }
function argon_get_available_easter_eggs() { return array('fixture' => array('name' => '测试彩蛋', 'description' => '测试描述', 'default_date' => '04-01', 'default_type' => 'solar')); }
function argon_get_easter_egg_config($id) { return array(); }
function argon_get_all_apis() { return array(array('id' => 'fixture-api', 'name' => '测试 API', 'provider' => 'openai_compatible', 'model' => 'test-model', 'api_key' => 'test-key', 'api_endpoint' => 'https://example.test/v1')); }
function argon_normalize_ai_provider($provider) { return $provider; }
function argon_get_ai_provider_label($provider) { return 'OpenAI 兼容'; }
function argon_get_email_types() { return array('comment_notify' => array('name' => '评论通知', 'description' => '站长收到新评论', 'default_subject' => '新评论', 'default_content' => '<p>新评论</p>'), 'reply_notify' => array('name' => '回复通知', 'description' => '评论者收到回复', 'default_subject' => '新回复', 'default_content' => '<p>新回复</p>')); }
function argon_get_email_template_config($type) { return array('default_subject' => '通知', 'default_content' => '通知正文', 'placeholders' => array('{site_name}' => '站点名称')); }
require isset($argv[1]) ? $argv[1] : $fixture_root . '/settings.php';
if (function_exists('argon_settings_admin_assets')) argon_settings_admin_assets('toplevel_page_functions.php');
ob_start();
themeoptions_page();
$markup = ob_get_clean();
echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><title>Argon settings fixture</title><style>body{margin:0;background:#f0f0f1;color:#1d2327;font:14px/1.6 system-ui}#wpcontent{margin-left:160px;padding:24px}h1,h2,h3,h4{line-height:1.4}.form-table{border-collapse:collapse;width:100%}.form-table th{text-align:left;width:200px;padding:20px 10px}.form-table td{padding:15px 10px}.button{display:inline-block;padding:5px 12px;border:1px solid #8c8f94;border-radius:4px;background:#f6f7f7;cursor:pointer;color:#2271b1}.button-primary{color:white;background:#2271b1}.button:disabled{opacity:.5}input,select,textarea{font:inherit;padding:4px 8px;border:1px solid #8c8f94;border-radius:4px}input[type=checkbox],input[type=radio]{width:16px;height:16px}textarea{width:100%}.description{font-size:13px;color:#646970}.regular-text{width:25em}.widefat{width:100%} @media(max-width:782px){#wpcontent{margin-left:0;padding:12px}}</style><script src="/theme/assets/vendor/jquery/jquery.min.js"></script><script>var $=jQuery;var ajaxurl="/wp-admin/admin-ajax.php";</script>';
// 页脚脚本必须在本地化配置之后、模板之后执行。
$footer_scripts = array();
foreach ($fixture_assets as $asset) {
 if (strpos($asset, 'settings-admin.js') !== false) $footer_scripts[] = $asset;
 else echo $asset;
}
echo '</head><body><main id="wpcontent">' . $markup . '</main>' . implode('', $footer_scripts) . '</body></html>';
