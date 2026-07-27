<?php

/**
 * 友链 Worker 后台配置与敏感信息处理契约测试。
 *
 * 运行：php tests/friend-link-worker-settings-test.php
 */

$root = dirname(__DIR__);
$settings_source = file_get_contents($root . '/settings.php');
if ($settings_source === false) {
    fwrite(STDERR, "无法读取 settings.php。\n");
    exit(1);
}

$failures = array();
function argon_worker_settings_assert($condition, $message) {
    global $failures;
    if (!$condition) $failures[] = $message;
}

foreach (array(
    'argon_friend_link_worker_enabled',
    'argon_friend_link_worker_url',
) as $field_name) {
    argon_worker_settings_assert(
        strpos($settings_source, 'name="' . $field_name . '"') !== false,
        '后台缺少 Worker 配置字段：' . $field_name
    );
}

argon_worker_settings_assert(
    strpos($settings_source, 'id="argon-test-friend-link-worker"') !== false
    && strpos($settings_source, "add_action('wp_ajax_argon_test_friend_link_worker'") !== false
    && strpos($settings_source, "wp_safe_remote_get(\$health_url") !== false
    && strpos($settings_source, "\$payload['service'] !== 'friend-link-checker'") !== false,
    '后台缺少 Worker /health 安全连通性测试。'
);

foreach (array(
    'argon_friend_link_submit_hmac_secret',
    'argon_friend_link_callback_hmac_secret',
) as $field_name) {
    $matched = preg_match(
        '/<input\b(?=[^>]*type="password")(?=[^>]*name="' . preg_quote($field_name, '/') . '")[^>]*>/i',
        $settings_source,
        $matches
    );
    argon_worker_settings_assert($matched === 1, '后台密钥字段不是 password：' . $field_name);
    if ($matched === 1) {
        argon_worker_settings_assert(stripos($matches[0], 'value=') === false, '后台回显了已有密钥：' . $field_name);
        argon_worker_settings_assert(stripos($matches[0], 'data-argon-sensitive') !== false, '后台密钥字段未标记为敏感：' . $field_name);
    }
}

argon_worker_settings_assert(
    strpos($settings_source, ':not([data-argon-sensitive])') !== false
    && strpos($settings_source, '.is("[data-argon-sensitive]")') !== false,
    '设置导出或导入没有排除敏感字段。'
);

define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('DAY_IN_SECONDS', 86400);

$GLOBALS['argon_worker_settings_options'] = array();
$GLOBALS['argon_worker_settings_updates'] = array();
$GLOBALS['argon_worker_settings_transients'] = array();
$GLOBALS['argon_worker_health_response'] = array(
    'response' => array('code' => 200),
    'body' => '{"ok":true,"service":"friend-link-checker"}',
);
$GLOBALS['argon_worker_health_requested_url'] = '';

class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
function __($text, $domain = null) { return $text; }
function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['argon_worker_settings_options'])
        ? $GLOBALS['argon_worker_settings_options'][$name]
        : $default;
}
function update_option($name, $value, $autoload = null) {
    $GLOBALS['argon_worker_settings_options'][$name] = $value;
    $GLOBALS['argon_worker_settings_updates'][] = array($name, $value, $autoload);
    return true;
}
function delete_option($name) {
    unset($GLOBALS['argon_worker_settings_options'][$name]);
    return true;
}
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
function esc_url_raw($value) { return filter_var((string)$value, FILTER_VALIDATE_URL) ? (string)$value : ''; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function argon_get_friend_link($id) { return $id === 'fl_settings' ? array('id' => $id, 'url' => 'https://friend.example/') : null; }
function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
    return substr(str_repeat('PublicDispatchToken', 4), 0, $length);
}
function set_transient($key, $value, $expiration) {
    $GLOBALS['argon_worker_settings_transients'][$key] = $value;
    return true;
}
function wp_http_validate_url($url) {
    if (strpos($url, 'https://') !== 0) return false;
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') return false;
    return $url;
}
function wp_safe_remote_get($url, $args = array()) {
    $GLOBALS['argon_worker_health_requested_url'] = $url;
    return $GLOBALS['argon_worker_health_response'];
}
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function is_wp_error($value) { return $value instanceof WP_Error; }

require $root . '/settings.php';
require $root . '/inc/friend-link-worker.php';

$health = argon_test_friend_link_worker_health('https://worker.example.net/');
argon_worker_settings_assert(
    is_array($health)
    && $health['service'] === 'friend-link-checker'
    && $GLOBALS['argon_worker_health_requested_url'] === 'https://worker.example.net/health',
    'Worker 健康检查没有验证预期的 HTTPS /health 响应。'
);
argon_worker_settings_assert(
    is_wp_error(argon_test_friend_link_worker_health('http://worker.example.net')),
    'Worker 健康检查允许了非 HTTPS 地址。'
);
$GLOBALS['argon_worker_health_requested_url'] = '';
argon_worker_settings_assert(
    is_wp_error(argon_test_friend_link_worker_health('https://127.0.0.1'))
    && $GLOBALS['argon_worker_health_requested_url'] === '',
    'Worker 健康检查没有在发起请求前拒绝回环地址。'
);

$GLOBALS['argon_worker_settings_options'] = array(
    'argon_friend_link_submit_hmac_secret' => 'stored-submit-secret',
    'argon_friend_link_callback_hmac_secret' => 'stored-callback-secret',
);
$_POST = array(
    'argon_friend_link_worker_enabled' => 'true',
    'argon_friend_link_worker_url' => 'https://worker.example.net/',
    'argon_friend_link_submit_hmac_secret' => '',
    'argon_friend_link_callback_hmac_secret' => '',
);
argon_update_friend_link_worker_settings();
argon_worker_settings_assert(
    get_option('argon_friend_link_submit_hmac_secret') === 'stored-submit-secret'
    && get_option('argon_friend_link_callback_hmac_secret') === 'stored-callback-secret',
    '留空保存时清除了已有 Worker 密钥。'
);

$_POST = array(
    'argon_friend_link_worker_enabled' => 'true',
    'argon_friend_link_worker_url' => 'https://worker.example.net',
    'argon_friend_link_submit_hmac_secret' => 'rotated-submit-secret',
    'argon_friend_link_callback_hmac_secret' => 'rotated-callback-secret',
);
argon_update_friend_link_worker_settings();
$secret_updates_disable_autoload = true;
foreach ($GLOBALS['argon_worker_settings_updates'] as $update) {
    if (in_array($update[0], array('argon_friend_link_submit_hmac_secret', 'argon_friend_link_callback_hmac_secret'), true)) {
        $secret_updates_disable_autoload = $secret_updates_disable_autoload && $update[2] === false;
    }
}
argon_worker_settings_assert($secret_updates_disable_autoload, '数据库密钥选项仍会自动加载。');

$_POST['argon_friend_link_worker_clear_secrets'] = 'true';
argon_update_friend_link_worker_settings();
argon_worker_settings_assert(
    get_option('argon_friend_link_submit_hmac_secret', null) === null
    && get_option('argon_friend_link_callback_hmac_secret', null) === null,
    '显式清除没有删除数据库中的 Worker 密钥。'
);

$GLOBALS['argon_worker_settings_options'] = array(
    'argon_friend_link_worker_enabled' => 'true',
    'argon_friend_link_worker_url' => 'https://option-worker.example',
    'argon_friend_link_submit_hmac_secret' => 'option-submit-secret',
    'argon_friend_link_callback_hmac_secret' => 'option-callback-secret',
);
argon_worker_settings_assert(argon_friend_link_worker_is_configured(), '纯后台选项无法启用 Worker。');
argon_worker_settings_assert(
    argon_friend_link_worker_url() === 'https://option-worker.example',
    'Worker URL 没有读取后台选项。'
);

$GLOBALS['argon_worker_settings_options']['argon_friend_link_worker_enabled'] = 'false';
$GLOBALS['argon_worker_settings_transients'] = array();
argon_worker_settings_assert(
    argon_friend_link_worker_create_dispatch_token('fl_settings') === ''
    && empty($GLOBALS['argon_worker_settings_transients']),
    'Worker 未启用时仍签发了一次性派发令牌。'
);

putenv('ARGON_FRIEND_LINK_WORKER_ENABLED=true');
putenv('ARGON_FRIEND_LINK_WORKER_URL=https://env-worker.example');
putenv('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET=env-submit-secret');
putenv('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET=env-callback-secret');
argon_worker_settings_assert(
    argon_friend_link_worker_is_configured()
    && argon_friend_link_worker_url() === 'https://env-worker.example'
    && argon_friend_link_worker_submit_secret() === 'env-submit-secret',
    '环境变量没有优先于后台选项。'
);

define('ARGON_FRIEND_LINK_WORKER_URL', 'https://constant-worker.example');
define('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET', 'constant-submit-secret');
define('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET', 'constant-callback-secret');
unset($GLOBALS['argon_worker_settings_options']['argon_friend_link_worker_enabled']);
putenv('ARGON_FRIEND_LINK_WORKER_ENABLED');
argon_worker_settings_assert(
    argon_friend_link_worker_enabled()
    && argon_friend_link_worker_url() === 'https://constant-worker.example'
    && argon_friend_link_worker_submit_secret() === 'constant-submit-secret',
    '旧版常量配置没有保持自动启用，或常量未优先于环境变量。'
);

$GLOBALS['argon_worker_settings_options']['argon_friend_link_worker_enabled'] = 'false';
argon_worker_settings_assert(!argon_friend_link_worker_enabled(), '后台显式关闭没有覆盖旧版自动启用。');

if ($failures) {
    fwrite(STDERR, "友链 Worker 设置测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "友链 Worker 设置测试通过。\n";
