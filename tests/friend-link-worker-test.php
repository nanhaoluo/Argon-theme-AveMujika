<?php

/**
 * 友链 Worker 的 WordPress REST 桥接契约测试。
 *
 * 运行：php tests/friend-link-worker-test.php
 */

define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('DAY_IN_SECONDS', 86400);
define('ARGON_FRIEND_LINK_WORKER_URL', 'https://worker.cartol.top');
define('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET', 'submit-secret-for-contract-tests');
define('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET', 'callback-secret-for-contract-tests');

$GLOBALS['argon_worker_test_links'] = array(
    'fl_contract' => array(
        'id' => 'fl_contract',
        'name' => '旧标题',
        'auto_name' => true,
        'display_name' => '',
        'user_display_name' => false,
        'url' => 'https://cartol.top/',
        'links_page' => 'https://cartol.top/friends/',
        'avatar' => '',
        'user_avatar' => false,
        'has_backlink' => null,
    ),
);
$GLOBALS['argon_worker_test_transients'] = array();
$GLOBALS['argon_worker_test_routes'] = array();
$GLOBALS['argon_worker_test_is_admin'] = true;

class WP_Error {
    public $code;
    public $message;
    public $data;
    public function __construct($code, $message = '', $data = null) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}

class WP_REST_Response {
    private $data;
    private $status;
    public function __construct($data = null, $status = 200) {
        $this->data = $data;
        $this->status = $status;
    }
    public function get_data() { return $this->data; }
    public function get_status() { return $this->status; }
}

class WP_REST_Request {
    private $body;
    private $headers;
    public function __construct($body = '', $headers = array()) {
        $this->body = $body;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }
    public function get_body() { return $this->body; }
    public function get_header($name) {
        $name = strtolower($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : '';
    }
}

function add_action($hook, $callback) {}
function __return_true() { return true; }
function register_rest_route($namespace, $route, $args) {
    $GLOBALS['argon_worker_test_routes'][$namespace . $route] = $args;
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function rest_url($path = '') { return 'https://blog.cartol.top/wp-json/' . ltrim($path, '/'); }
function current_user_can($capability) { return !empty($GLOBALS['argon_worker_test_is_admin']); }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid-admin-nonce' && $action === 'argon_friend_link_worker_ticket'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function esc_url_raw($value) { return filter_var((string)$value, FILTER_VALIDATE_URL) ? (string)$value : ''; }
function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
function wp_unslash($value) { return $value; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function wp_generate_uuid4() { return '12345678-1234-4abc-8def-1234567890ab'; }
function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
    return substr(str_repeat('PublicDispatchToken', 4), 0, $length);
}
function set_transient($key, $value, $expiration) {
    $GLOBALS['argon_worker_test_transients'][$key] = $value;
    return true;
}
function get_transient($key) {
    return array_key_exists($key, $GLOBALS['argon_worker_test_transients'])
        ? $GLOBALS['argon_worker_test_transients'][$key]
        : false;
}
function delete_transient($key) {
    unset($GLOBALS['argon_worker_test_transients'][$key]);
    return true;
}
function argon_get_friend_link($id) {
    return isset($GLOBALS['argon_worker_test_links'][$id]) ? $GLOBALS['argon_worker_test_links'][$id] : null;
}
function argon_update_friend_link($id, $data) {
    if (!isset($GLOBALS['argon_worker_test_links'][$id])) return false;
    $GLOBALS['argon_worker_test_links'][$id] = array_merge($GLOBALS['argon_worker_test_links'][$id], $data);
    return true;
}

require dirname(__DIR__) . '/inc/friend-link-worker.php';

$failures = array();
function argon_worker_assert($condition, $message) {
    global $failures;
    if (!$condition) $failures[] = $message;
}

argon_register_friend_link_worker_routes();
argon_worker_assert(
    isset($GLOBALS['argon_worker_test_routes']['argon/v1/friend-link-ticket'])
    && isset($GLOBALS['argon_worker_test_routes']['argon/v1/friend-link-result']),
    'REST 签发或回调路由未注册。'
);

$admin_request = new WP_REST_Request(json_encode(array(
    'job_id' => 'fl_contract',
    'nonce' => 'valid-admin-nonce',
)));
$admin_response = argon_friend_link_worker_ticket_rest($admin_request);
$admin_data = $admin_response instanceof WP_REST_Response ? $admin_response->get_data() : array();
argon_worker_assert($admin_response instanceof WP_REST_Response && $admin_response->get_status() === 200, '管理员签发请求失败。');
argon_worker_assert(!empty($admin_data['ticket']['body']) && !empty($admin_data['ticket']['headers']), '签发响应缺少原始请求体或签名头。');

$ticket = isset($admin_data['ticket']) ? $admin_data['ticket'] : array();
$timestamp = isset($ticket['headers']['X-Argon-Timestamp']) ? $ticket['headers']['X-Argon-Timestamp'] : '';
$nonce = isset($ticket['headers']['X-Argon-Nonce']) ? $ticket['headers']['X-Argon-Nonce'] : '';
$signature = isset($ticket['headers']['X-Argon-Signature']) ? $ticket['headers']['X-Argon-Signature'] : '';
$expected_signature = 'sha256=' . hash_hmac(
    'sha256',
    $timestamp . '.' . $nonce . '.' . (isset($ticket['body']) ? $ticket['body'] : ''),
    ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET
);
argon_worker_assert(isset($ticket['url']) && $ticket['url'] === 'https://worker.cartol.top/v1/jobs', '签发目标不是固定 Worker 地址。');
argon_worker_assert($signature !== '' && hash_equals($expected_signature, $signature), '提交签名没有覆盖时间戳、随机数和原始 JSON。');
argon_worker_assert(
    isset($GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'])
    && $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'] === $nonce,
    '签发后没有保存当前任务代际。'
);

$GLOBALS['argon_worker_test_is_admin'] = false;
$dispatch_token = argon_friend_link_worker_create_dispatch_token('fl_contract');
$public_request = new WP_REST_Request(json_encode(array('dispatch_token' => $dispatch_token)));
$public_response = argon_friend_link_worker_ticket_rest($public_request);
argon_worker_assert($public_response instanceof WP_REST_Response && $public_response->get_status() === 200, '一次性访客令牌无法签发任务。');
$replayed_ticket = argon_friend_link_worker_ticket_rest($public_request);
argon_worker_assert($replayed_ticket instanceof WP_REST_Response && $replayed_ticket->get_status() === 403, '一次性访客令牌可以重复使用。');

$generation = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
$callback_payload = array(
    'version' => 1,
    'job_id' => 'fl_contract',
    'generation' => $generation,
    'checked_at' => time(),
    'result' => array(
        'state' => 'complete',
        'accessible' => true,
        'readable' => true,
        'detected_url' => 'https://blog.cartol.top/',
        'primary' => '编程小记',
        'subtitle' => '其实我也不知道建这个站拿来干嘛=-=',
        'description' => '自动识别描述',
        'avatar' => 'https://blog.cartol.top/favicon.ico',
        'backlink_state' => 'found',
        'source' => 'cloudflare_worker',
        'attempts' => 1,
        'error' => '',
    ),
);
$callback_body = json_encode($callback_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$callback_timestamp = (string)time();
$callback_nonce = 'callback_nonce_1234567890';
$callback_signature = 'sha256=' . hash_hmac(
    'sha256',
    $callback_timestamp . '.' . $callback_nonce . '.' . $callback_body,
    ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET
);
$callback_request = new WP_REST_Request($callback_body, array(
    'X-Argon-Timestamp' => $callback_timestamp,
    'X-Argon-Nonce' => $callback_nonce,
    'X-Argon-Signature' => $callback_signature,
));
$callback_response = argon_friend_link_worker_result_rest($callback_request);
$updated_link = argon_get_friend_link('fl_contract');
argon_worker_assert($callback_response instanceof WP_REST_Response && $callback_response->get_status() === 200, '合法 Worker 回调未被接受。');
argon_worker_assert(
    $updated_link['name'] === '编程小记'
    && $updated_link['display_name'] === '其实我也不知道建这个站拿来干嘛=-='
    && $updated_link['has_backlink'] === true
    && $updated_link['check_source'] === 'cloudflare_worker',
    '合法 Worker 回调没有更新自动字段和互链状态。'
);

$replay_response = argon_friend_link_worker_result_rest($callback_request);
argon_worker_assert($replay_response instanceof WP_REST_Response && $replay_response->get_status() === 409, '相同回调随机数可以重放。');

$unknown_payload = $callback_payload;
$unknown_payload['result']['backlink_state'] = 'unknown';
$unknown_payload['result']['state'] = 'unknown';
$unknown_payload['result']['accessible'] = false;
$unknown_payload['result']['readable'] = false;
$unknown_body = json_encode($unknown_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$unknown_nonce = 'callback_nonce_unknown_12345';
$unknown_signature = 'sha256=' . hash_hmac(
    'sha256',
    $callback_timestamp . '.' . $unknown_nonce . '.' . $unknown_body,
    ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET
);
$unknown_response = argon_friend_link_worker_result_rest(new WP_REST_Request($unknown_body, array(
    'X-Argon-Timestamp' => $callback_timestamp,
    'X-Argon-Nonce' => $unknown_nonce,
    'X-Argon-Signature' => $unknown_signature,
)));
$unknown_updated_link = argon_get_friend_link('fl_contract');
argon_worker_assert(
    $unknown_response instanceof WP_REST_Response
    && $unknown_response->get_status() === 200
    && $unknown_updated_link['accessible'] === true
    && $unknown_updated_link['has_backlink'] === true
    && $unknown_updated_link['backlink_check_result'] === 'found',
    'unknown 回调覆盖了已有互链结论。'
);

$stale_payload = $callback_payload;
$stale_payload['generation'] = 'stale_generation_123456';
$stale_body = json_encode($stale_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$stale_nonce = 'callback_nonce_stale_12345';
$stale_signature = 'sha256=' . hash_hmac(
    'sha256',
    $callback_timestamp . '.' . $stale_nonce . '.' . $stale_body,
    ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET
);
$stale_response = argon_friend_link_worker_result_rest(new WP_REST_Request($stale_body, array(
    'X-Argon-Timestamp' => $callback_timestamp,
    'X-Argon-Nonce' => $stale_nonce,
    'X-Argon-Signature' => $stale_signature,
)));
$stale_data = $stale_response instanceof WP_REST_Response ? $stale_response->get_data() : array();
argon_worker_assert(
    $stale_response instanceof WP_REST_Response
    && $stale_response->get_status() === 200
    && isset($stale_data['ignored'])
    && $stale_data['ignored'] === 'stale_generation',
    '旧任务代际没有被安全丢弃。'
);

if ($failures) {
    fwrite(STDERR, "友链 Worker 桥接测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "友链 Worker 桥接测试通过。\n";
