<?php

/**
 * 友链 Worker 的 WordPress REST 桥接契约测试。
 *
 * 运行：php tests/friend-link-worker-test.php
 */

define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('DAY_IN_SECONDS', 86400);
define('ARGON_FRIEND_LINK_WORKER_URL', 'https://worker.sample-blog.dev');
define('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET', 'submit-secret-for-contract-tests');
define('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET', 'callback-secret-for-contract-tests');

$GLOBALS['argon_worker_test_links'] = array(
    'fl_contract' => array(
        'id' => 'fl_contract',
        'name' => '旧标题',
        'auto_name' => true,
        'name_source' => 'worker',
        'display_name' => '',
        'user_display_name' => false,
        'display_name_source' => 'worker',
        'url' => 'https://sample-blog.dev/',
        'links_page' => '',
        'links_page_source' => 'worker',
        'avatar' => '',
        'user_avatar' => false,
        'avatar_source' => 'worker',
        'description' => '',
        'auto_description' => '',
        'description_source' => 'worker',
        'has_backlink' => null,
    ),
);
$GLOBALS['argon_worker_test_transients'] = array();
$GLOBALS['argon_worker_test_routes'] = array();
$GLOBALS['argon_worker_test_is_admin'] = true;
$GLOBALS['argon_worker_test_remote_posts'] = array();
$GLOBALS['argon_worker_test_next_remote_response'] = null;
$GLOBALS['argon_worker_test_uuid_counter'] = 0;
$GLOBALS['argon_worker_test_options'] = array();

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
function add_filter($hook, $callback) {}
function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['argon_worker_test_options'])
        ? $GLOBALS['argon_worker_test_options'][$name]
        : $default;
}
function add_option($name, $value = '', $deprecated = '', $autoload = null) {
    if (array_key_exists($name, $GLOBALS['argon_worker_test_options'])) return false;
    $GLOBALS['argon_worker_test_options'][$name] = $value;
    return true;
}
function delete_option($name) {
    unset($GLOBALS['argon_worker_test_options'][$name]);
    return true;
}
function __return_true() { return true; }
function register_rest_route($namespace, $route, $args) {
    $GLOBALS['argon_worker_test_routes'][$namespace . $route] = $args;
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function rest_url($path = '') { return 'https://blog.sample-blog.dev/wp-json/' . ltrim($path, '/'); }
function home_url($path = '') { return 'https://blog.sample-blog.dev/' . ltrim($path, '/'); }
function current_user_can($capability) { return !empty($GLOBALS['argon_worker_test_is_admin']); }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid-admin-nonce' && $action === 'argon_friend_link_worker_ticket'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function esc_url_raw($value) { return filter_var((string)$value, FILTER_VALIDATE_URL) ? (string)$value : ''; }
function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
function wp_unslash($value) { return $value; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function wp_generate_uuid4() {
    $GLOBALS['argon_worker_test_uuid_counter']++;
    return sprintf('12345678-1234-4abc-8def-%012d', $GLOBALS['argon_worker_test_uuid_counter']);
}
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
function wp_remote_post($url, $args = array()) {
    $GLOBALS['argon_worker_test_remote_posts'][] = array('url' => $url, 'args' => $args);
    if (is_array($GLOBALS['argon_worker_test_next_remote_response'])) {
        $response = $GLOBALS['argon_worker_test_next_remote_response'];
        $GLOBALS['argon_worker_test_next_remote_response'] = null;
        return $response;
    }
    return array('response' => array('code' => false));
}
function wp_remote_retrieve_response_code($response) {
    return is_array($response) && isset($response['response']['code']) ? intval($response['response']['code']) : 0;
}
function wp_remote_retrieve_body($response) {
    return is_array($response) && isset($response['body']) ? (string)$response['body'] : '';
}
function wp_next_scheduled($hook, $args = array()) { return false; }
function wp_schedule_single_event($timestamp, $hook, $args = array()) { return true; }
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
    !isset($GLOBALS['argon_worker_test_routes']['argon/v1/friend-link-ticket'])
    && isset($GLOBALS['argon_worker_test_routes']['argon/v1/friend-link-result']),
    '浏览器 ticket 路由仍存在，或 Worker 回调路由未注册。'
);

$GLOBALS['argon_worker_test_links']['fl_browser'] = array(
    'id' => 'fl_browser',
    'name' => '管理员标题',
    'name_source' => 'admin',
    'auto_name' => false,
    'display_name' => '',
    'display_name_source' => 'worker',
    'user_display_name' => false,
    'url' => 'https://www.wgzdy.top/',
    'links_page' => 'https://www.wgzdy.top/links/',
    'links_page_source' => 'admin',
    'avatar' => 'https://www.wgzdy.top/assets/site-logo.png',
    'avatar_source' => 'worker',
    'user_avatar' => false,
    'description' => '',
    'auto_description' => '',
    'description_source' => 'worker',
    'has_backlink' => true,
    'backlink_check_result' => 'found',
    'worker_status' => 'complete',
    'check_source' => 'cloudflare_worker',
);
$browser_unknown_result = argon_friend_link_apply_browser_result('fl_browser', array(
    'mode' => 'backlink',
    'target_url' => 'https://www.wgzdy.top/',
    'checked_url' => 'https://www.wgzdy.top/links/',
    'readable' => false,
    'accessible' => false,
    'links_page' => 'https://www.wgzdy.top/links/',
    'links_page_source' => 'admin',
    'backlink_state' => 'unknown',
    'browser_error' => 'cors_unreadable',
));
$browser_unknown_link = argon_get_friend_link('fl_browser');
argon_worker_assert(
    !is_wp_error($browser_unknown_result)
    && $browser_unknown_link['has_backlink'] === true
    && $browser_unknown_link['backlink_check_result'] === 'found'
    && $browser_unknown_link['browser_backlink_result'] === 'unknown'
    && $browser_unknown_link['links_page'] === 'https://www.wgzdy.top/links/'
    && $browser_unknown_link['worker_status'] === 'complete'
    && $browser_unknown_link['check_source'] === 'cloudflare_worker'
    && $browser_unknown_link['browser_backlink_source'] === 'admin_browser',
    '浏览器跨域 unknown 覆盖了已有互链结论/来源、接受了跨站页面，或伪造了 Worker 状态。'
);

$stale_browser_target = argon_friend_link_apply_browser_result('fl_browser', array(
    'mode' => 'info',
    'target_url' => 'https://www.wgzdy.top/new-home/',
    'readable' => true,
    'accessible' => true,
    'detection_source' => 'html',
    'primary' => '错误站点标题',
));
argon_worker_assert(
    is_wp_error($stale_browser_target)
    && $stale_browser_target->get_error_code() === 'stale_browser_target'
    && argon_get_friend_link('fl_browser')['name'] === '管理员标题',
    '浏览器检测结果没有精确绑定检测开始时的友链地址。'
);

$cross_site_browser_result = argon_friend_link_apply_browser_result('fl_browser', array(
    'mode' => 'backlink',
    'target_url' => 'https://www.wgzdy.top/',
    'checked_url' => 'https://attacker.example/links/',
    'readable' => true,
    'accessible' => true,
    'links_page' => 'https://www.wgzdy.top/links/',
    'links_page_source' => 'admin',
    'backlink_state' => 'missing',
));
argon_worker_assert(
    is_wp_error($cross_site_browser_result)
    && $cross_site_browser_result->get_error_code() === 'invalid_browser_checked_url'
    && argon_get_friend_link('fl_browser')['has_backlink'] === true,
    '浏览器跨站实际检查页仍能覆盖当前友链的互链结论。'
);

$missing_links_page_result = argon_friend_link_apply_browser_result('fl_browser', array(
    'mode' => 'backlink',
    'target_url' => 'https://www.wgzdy.top/',
    'checked_url' => 'https://www.wgzdy.top/',
    'readable' => true,
    'accessible' => true,
    'links_page' => '',
    'links_page_source' => 'worker',
    'backlink_state' => 'missing',
));
argon_worker_assert(
    is_wp_error($missing_links_page_result)
    && $missing_links_page_result->get_error_code() === 'browser_links_page_missing'
    && argon_get_friend_link('fl_browser')['has_backlink'] === true,
    '浏览器未发现友链页时仍能用首页的结果落库 missing。'
);

$missing_links_page_unknown = argon_friend_link_apply_browser_result('fl_browser', array(
    'mode' => 'backlink',
    'target_url' => 'https://www.wgzdy.top/',
    'checked_url' => '',
    'readable' => false,
    'accessible' => false,
    'links_page' => '',
    'links_page_source' => 'worker',
    'backlink_state' => 'unknown',
    'browser_error' => 'links_page_not_found',
));
argon_worker_assert(
    !is_wp_error($missing_links_page_unknown)
    && argon_get_friend_link('fl_browser')['has_backlink'] === true
    && argon_get_friend_link('fl_browser')['check_source'] === 'cloudflare_worker',
    '浏览器未发现友链页时无法记录 unknown 尝试，或污染了既有结论来源。'
);

$browser_info_result = argon_friend_link_apply_browser_result('fl_browser', array(
    'mode' => 'info',
    'target_url' => 'https://www.wgzdy.top/',
    'readable' => false,
    'accessible' => true,
    'detected_url' => 'https://www.wgzdy.top/',
    'detection_source' => 'wordpress',
    'primary' => '浏览器自动标题',
    'subtitle' => '浏览器副标题',
    'description' => '浏览器公开接口描述',
    'avatar' => 'https://www.wgzdy.top/favicon.ico',
    'links_page' => 'https://www.wgzdy.top/new-links/',
    'links_page_source' => 'worker',
));
$browser_info_link = argon_get_friend_link('fl_browser');
argon_worker_assert(
    !is_wp_error($browser_info_result)
    && $browser_info_link['name'] === '管理员标题'
    && $browser_info_link['display_name'] === '浏览器副标题'
    && $browser_info_link['avatar'] === 'https://www.wgzdy.top/assets/site-logo.png'
    && $browser_info_link['links_page'] === 'https://www.wgzdy.top/links/'
    && $browser_info_link['auto_description'] === '浏览器公开接口描述'
    && $browser_info_link['browser_info_source'] === 'admin_browser'
    && $browser_info_link['browser_info_detection_source'] === 'wordpress'
    && $browser_info_link['check_source'] === 'cloudflare_worker',
    '浏览器资料更新覆盖了管理员字段、用 favicon 降级头像、覆盖手填友链页或改写互链来源。'
);

$GLOBALS['argon_worker_test_links']['fl_browser_media'] = array(
    'id' => 'fl_browser_media',
    'name' => '高质量自动标题',
    'name_source' => 'worker',
    'auto_name' => true,
    'display_name' => '',
    'display_name_source' => 'worker',
    'user_display_name' => false,
    'url' => 'https://www.wgzdy.top/',
    'links_page' => '',
    'links_page_source' => 'worker',
    'avatar' => 'https://www.wgzdy.top/favicon.ico',
    'avatar_source' => 'worker',
    'user_avatar' => false,
    'description' => '',
    'auto_description' => '',
    'description_source' => 'worker',
    'has_backlink' => null,
    'backlink_check_result' => 'unknown',
    'worker_status' => 'unknown',
    'check_source' => 'cloudflare_worker',
);
argon_friend_link_apply_browser_result('fl_browser_media', array(
    'mode' => 'info',
    'target_url' => 'https://www.wgzdy.top/',
    'readable' => true,
    'accessible' => true,
    'detection_source' => 'html',
    'primary' => 'HTML 首页标题',
));
$browser_html_link = argon_get_friend_link('fl_browser_media');
argon_worker_assert(
    $browser_html_link['name'] === 'HTML 首页标题'
    && $browser_html_link['browser_info_detection_source'] === 'html',
    '浏览器 HTML 首选来源未按多策略契约保存。'
);
argon_friend_link_apply_browser_result('fl_browser_media', array(
    'mode' => 'info',
    'target_url' => 'https://www.wgzdy.top/',
    'readable' => false,
    'accessible' => true,
    'detection_source' => 'opaque',
    'primary' => 'wgzdy.top',
));
$browser_opaque_link = argon_get_friend_link('fl_browser_media');
argon_worker_assert(
    $browser_opaque_link['name'] === 'HTML 首页标题'
    && $browser_opaque_link['browser_info_detection_source'] === 'opaque',
    '浏览器 opaque 主机名回退覆盖了已有自动资料。'
);
argon_friend_link_apply_browser_result('fl_browser_media', array(
    'mode' => 'info',
    'target_url' => 'https://www.wgzdy.top/',
    'readable' => false,
    'accessible' => true,
    'primary' => 'wgzdy.top',
));
$browser_legacy_partial_link = argon_get_friend_link('fl_browser_media');
argon_worker_assert(
    $browser_legacy_partial_link['name'] === 'HTML 首页标题'
    && $browser_legacy_partial_link['browser_info_detection_source'] === 'opaque',
    '缺少质量来源的旧浏览器结果仍会覆盖已有自动资料。'
);
argon_friend_link_apply_browser_result('fl_browser_media', array(
    'mode' => 'info',
    'target_url' => 'https://www.wgzdy.top/',
    'readable' => false,
    'accessible' => true,
    'detection_source' => 'argon',
    'avatar' => 'https://vercel.cdn.wgzdy.top/avater.webp',
    'links_page' => 'https://www.wgzdy.top/links/',
    'links_page_source' => 'worker',
));
$browser_media_link = argon_get_friend_link('fl_browser_media');
argon_worker_assert(
    $browser_media_link['avatar'] === 'https://vercel.cdn.wgzdy.top/avater.webp'
    && $browser_media_link['links_page'] === 'https://www.wgzdy.top/links/'
    && $browser_media_link['browser_info_detection_source'] === 'argon'
    && $browser_media_link['worker_status'] === 'unknown'
    && $browser_media_link['check_source'] === 'cloudflare_worker',
    '浏览器媒体发现结果没有替换 favicon，或手动资料更新伪造了 Worker 状态。'
);

$GLOBALS['argon_worker_test_links']['fl_browser_probe'] = array(
    'id' => 'fl_browser_probe',
    'name' => '同步探测旧标题',
    'name_source' => 'worker',
    'auto_name' => true,
    'display_name' => '',
    'display_name_source' => 'worker',
    'user_display_name' => false,
    'url' => 'https://www.wgzdy.top/',
    'links_page' => '',
    'links_page_source' => 'worker',
    'avatar' => 'https://www.wgzdy.top/favicon.ico',
    'avatar_source' => 'worker',
    'user_avatar' => false,
    'description' => '',
    'auto_description' => '',
    'description_source' => 'worker',
    'has_backlink' => null,
    'backlink_check_result' => 'unknown',
    'worker_status' => 'complete',
    'check_source' => 'cloudflare_worker',
);
$GLOBALS['argon_worker_test_next_remote_response'] = array(
    'response' => array('code' => 200),
    'body' => json_encode(array(
        'success' => true,
        'version' => 1,
        'checked_at' => time(),
        'result' => array(
            'state' => 'complete',
            'accessible' => true,
            'readable' => true,
            'detected_url' => 'https://www.wgzdy.top/',
            'primary' => '同步 Argon 标题',
            'subtitle' => '同步副标题',
            'description' => '同步探测描述',
            'avatar' => 'https://www.wgzdy.top/wp-content/uploads/avater.webp',
            'links_page' => 'https://www.wgzdy.top/links/',
            'checked_url' => '',
            'backlink_state' => 'unknown',
            'source' => 'cloudflare_worker',
            'detection_source' => 'argon',
            'attempts' => 1,
            'error' => '',
        ),
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
);
$browser_probe_result = argon_friend_link_worker_probe_and_apply('fl_browser_probe', array(
    'mode' => 'info',
    'target_url' => 'https://www.wgzdy.top/',
));
$browser_probe_request = end($GLOBALS['argon_worker_test_remote_posts']);
$browser_probe_link = argon_get_friend_link('fl_browser_probe');
$browser_probe_timestamp = isset($browser_probe_request['args']['headers']['X-Argon-Timestamp'])
    ? $browser_probe_request['args']['headers']['X-Argon-Timestamp']
    : '';
$browser_probe_nonce = isset($browser_probe_request['args']['headers']['X-Argon-Nonce'])
    ? $browser_probe_request['args']['headers']['X-Argon-Nonce']
    : '';
$browser_probe_signature = isset($browser_probe_request['args']['headers']['X-Argon-Signature'])
    ? $browser_probe_request['args']['headers']['X-Argon-Signature']
    : '';
$expected_browser_probe_signature = 'sha256=' . hash_hmac(
    'sha256',
    $browser_probe_timestamp . '.' . $browser_probe_nonce . '.' . $browser_probe_request['args']['body'],
    ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET
);
$browser_probe_body = json_decode($browser_probe_request['args']['body'], true);
argon_worker_assert(
    !is_wp_error($browser_probe_result)
    && $browser_probe_link['name'] === '同步 Argon 标题'
    && $browser_probe_link['display_name'] === '同步副标题'
    && $browser_probe_link['auto_description'] === '同步探测描述'
    && $browser_probe_link['avatar'] === 'https://www.wgzdy.top/wp-content/uploads/avater.webp'
    && $browser_probe_link['links_page'] === 'https://www.wgzdy.top/links/'
    && $browser_probe_link['browser_info_detection_source'] === 'argon'
    && $browser_probe_link['browser_transport'] === 'worker_sync'
    && $browser_probe_link['worker_status'] === 'complete'
    && $browser_probe_link['check_source'] === 'cloudflare_worker',
    '浏览器同步 Worker 探测结果未按来源契约落库，或污染了自动 Worker 状态。'
);
argon_worker_assert(
    $browser_probe_request['url'] === 'https://worker.sample-blog.dev/v1/probe'
    && isset($browser_probe_request['args']['blocking'])
    && $browser_probe_request['args']['blocking'] === true
    && isset($browser_probe_request['args']['limit_response_size'])
    && $browser_probe_request['args']['limit_response_size'] === 512 * 1024
    && isset($browser_probe_body['action'])
    && $browser_probe_body['action'] === 'probe'
    && $browser_probe_body['mode'] === 'info'
    && $browser_probe_body['site_url'] === 'https://blog.sample-blog.dev/'
    && hash_equals($expected_browser_probe_signature, $browser_probe_signature),
    '浏览器同步探测未调用固定 /v1/probe、未阻塞等待结果或未使用提交密钥签名。'
);

$dispatched = argon_friend_link_worker_dispatch_async('fl_contract');
$remote_post = !empty($GLOBALS['argon_worker_test_remote_posts']) ? end($GLOBALS['argon_worker_test_remote_posts']) : array();
$ticket = array(
    'url' => isset($remote_post['url']) ? $remote_post['url'] : '',
    'body' => isset($remote_post['args']['body']) ? $remote_post['args']['body'] : '',
    'headers' => isset($remote_post['args']['headers']) ? $remote_post['args']['headers'] : array(),
);
argon_worker_assert($dispatched === true, '服务端没有成功发起非阻塞 Worker 请求。');
argon_worker_assert(
    isset($remote_post['args']['blocking'])
    && $remote_post['args']['blocking'] === false,
    'Worker 请求不是非阻塞模式。'
);
$timestamp = isset($ticket['headers']['X-Argon-Timestamp']) ? $ticket['headers']['X-Argon-Timestamp'] : '';
$nonce = isset($ticket['headers']['X-Argon-Nonce']) ? $ticket['headers']['X-Argon-Nonce'] : '';
$signature = isset($ticket['headers']['X-Argon-Signature']) ? $ticket['headers']['X-Argon-Signature'] : '';
$expected_signature = 'sha256=' . hash_hmac(
    'sha256',
    $timestamp . '.' . $nonce . '.' . (isset($ticket['body']) ? $ticket['body'] : ''),
    ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET
);
argon_worker_assert(isset($ticket['url']) && $ticket['url'] === 'https://worker.sample-blog.dev/v1/jobs', '签发目标不是固定 Worker 地址。');
argon_worker_assert($signature !== '' && hash_equals($expected_signature, $signature), '提交签名没有覆盖时间戳、随机数和原始 JSON。');
argon_worker_assert(
    isset($GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'])
    && $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'] === $nonce,
    '签发后没有保存当前任务代际。'
);

$inflight_generation = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
$inflight_attempts = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_dispatch_attempts'];
$inflight_request_count = count($GLOBALS['argon_worker_test_remote_posts']);
$duplicate_dispatch = argon_friend_link_worker_dispatch_async('fl_contract');
argon_worker_assert(
    $duplicate_dispatch === true
    && $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'] === $inflight_generation
    && $GLOBALS['argon_worker_test_links']['fl_contract']['worker_dispatch_attempts'] === $inflight_attempts
    && count($GLOBALS['argon_worker_test_remote_posts']) === $inflight_request_count,
    '在途 Worker 任务被重复派发，可能导致 WordPress 与 D1 的任务代际错位。'
);

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
        'detected_url' => 'https://blog.sample-blog.dev/',
        'primary' => '编程小记',
        'subtitle' => '其实我也不知道建这个站拿来干嘛=-=',
        'description' => '自动识别描述',
        'avatar' => 'https://blog.sample-blog.dev/favicon.ico',
        'links_page' => 'https://blog.sample-blog.dev/friends/',
        'checked_url' => 'https://blog.sample-blog.dev/friends/',
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
    && $updated_link['links_page'] === 'https://blog.sample-blog.dev/friends/'
    && $updated_link['avatar'] === 'https://blog.sample-blog.dev/favicon.ico'
    && $updated_link['auto_description'] === '自动识别描述'
    && $updated_link['name_source'] === 'worker'
    && $updated_link['display_name_source'] === 'worker'
    && $updated_link['links_page_source'] === 'worker'
    && $updated_link['avatar_source'] === 'worker'
    && $updated_link['description_source'] === 'worker'
    && $updated_link['has_backlink'] === true
    && $updated_link['check_source'] === 'cloudflare_worker',
    '合法 Worker 回调没有更新自动字段和互链状态。'
);

$cross_site_worker_payload = $callback_payload;
$cross_site_worker_payload['result']['checked_url'] = 'https://attacker.example/links/';
$cross_site_worker_payload['result']['backlink_state'] = 'missing';
$cross_site_worker_result = argon_friend_link_worker_apply_result_payload($cross_site_worker_payload);
argon_worker_assert(
    is_wp_error($cross_site_worker_result)
    && $cross_site_worker_result->get_error_code() === 'invalid_worker_checked_url'
    && argon_get_friend_link('fl_contract')['has_backlink'] === true,
    'Worker 跨站实际检查页仍能覆盖当前友链的互链结论。'
);

argon_update_friend_link('fl_contract', array('avatar' => '', 'avatar_source' => 'worker'));
argon_friend_link_worker_dispatch_async('fl_contract');
$favicon_payload = $callback_payload;
$favicon_payload['generation'] = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
$favicon_payload['result']['avatar'] = 'https://blog.sample-blog.dev/favicon.ico';
$favicon_body = json_encode($favicon_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$favicon_nonce = 'callback_nonce_favicon_12345';
$favicon_signature = 'sha256=' . hash_hmac('sha256', $callback_timestamp . '.' . $favicon_nonce . '.' . $favicon_body, ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET);
$favicon_response = argon_friend_link_worker_result_rest(new WP_REST_Request($favicon_body, array(
    'X-Argon-Timestamp' => $callback_timestamp,
    'X-Argon-Nonce' => $favicon_nonce,
    'X-Argon-Signature' => $favicon_signature,
)));
argon_worker_assert(
    $favicon_response instanceof WP_REST_Response
    && $favicon_response->get_status() === 200
    && argon_get_friend_link('fl_contract')['avatar'] === 'https://blog.sample-blog.dev/favicon.ico',
    '测试前置失败：Worker favicon 回调未被处理。'
);

argon_update_friend_link('fl_contract', array(
    'avatar' => 'https://blog.sample-blog.dev/assets/site-logo.png',
    'avatar_source' => 'worker',
));
argon_friend_link_worker_dispatch_async('fl_contract');
$degrade_payload = $callback_payload;
$degrade_payload['generation'] = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
$degrade_payload['result']['avatar'] = 'https://blog.sample-blog.dev/favicon.ico';
$degrade_body = json_encode($degrade_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$degrade_nonce = 'callback_nonce_degrade_12345';
$degrade_signature = 'sha256=' . hash_hmac('sha256', $callback_timestamp . '.' . $degrade_nonce . '.' . $degrade_body, ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET);
$degrade_response = argon_friend_link_worker_result_rest(new WP_REST_Request($degrade_body, array(
    'X-Argon-Timestamp' => $callback_timestamp,
    'X-Argon-Nonce' => $degrade_nonce,
    'X-Argon-Signature' => $degrade_signature,
)));
argon_worker_assert(
    $degrade_response instanceof WP_REST_Response
    && $degrade_response->get_status() === 200
    && argon_get_friend_link('fl_contract')['avatar'] === 'https://blog.sample-blog.dev/assets/site-logo.png',
    '通用 favicon.ico 降级覆盖了已有的智能头像。'
);

argon_update_friend_link('fl_contract', array(
    'name' => '访客名称',
    'name_source' => 'visitor',
    'display_name' => '访客副标题',
    'display_name_source' => 'visitor',
    'avatar' => 'https://sample-blog.dev/visitor.png',
    'avatar_source' => 'visitor',
    'description' => '访客描述',
    'description_source' => 'visitor',
    'links_page' => 'https://sample-blog.dev/visitor-links/',
    'links_page_source' => 'visitor',
));
argon_friend_link_worker_dispatch_async('fl_contract');
$visitor_payload = $callback_payload;
$visitor_payload['generation'] = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
$visitor_payload['result']['primary'] = 'Worker 新名称';
$visitor_payload['result']['subtitle'] = 'Worker 新副标题';
$visitor_payload['result']['avatar'] = 'https://sample-blog.dev/worker.png';
$visitor_payload['result']['description'] = 'Worker 新描述';
$visitor_payload['result']['links_page'] = 'https://sample-blog.dev/worker-links/';
$visitor_body = json_encode($visitor_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$visitor_nonce = 'callback_nonce_visitor_12345';
$visitor_signature = 'sha256=' . hash_hmac('sha256', $callback_timestamp . '.' . $visitor_nonce . '.' . $visitor_body, ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET);
$visitor_response = argon_friend_link_worker_result_rest(new WP_REST_Request($visitor_body, array(
    'X-Argon-Timestamp' => $callback_timestamp,
    'X-Argon-Nonce' => $visitor_nonce,
    'X-Argon-Signature' => $visitor_signature,
)));
$visitor_link = argon_get_friend_link('fl_contract');
argon_worker_assert(
    $visitor_response instanceof WP_REST_Response
    && $visitor_response->get_status() === 200
    && $visitor_link['name'] === '访客名称'
    && $visitor_link['display_name'] === '访客副标题'
    && $visitor_link['avatar'] === 'https://sample-blog.dev/visitor.png'
    && $visitor_link['description'] === '访客描述'
    && $visitor_link['links_page'] === 'https://sample-blog.dev/visitor-links/',
    'Worker 自动结果覆盖了访客主动填写的字段。'
);

argon_update_friend_link('fl_contract', array(
    'name' => '管理员名称',
    'name_source' => 'admin',
    'display_name' => '管理员副标题',
    'display_name_source' => 'admin',
    'avatar' => 'https://sample-blog.dev/admin.png',
    'avatar_source' => 'admin',
    'description' => '管理员描述',
    'description_source' => 'admin',
    'links_page' => 'https://sample-blog.dev/admin-links/',
    'links_page_source' => 'admin',
));
argon_friend_link_worker_dispatch_async('fl_contract');
$admin_payload = $callback_payload;
$admin_payload['generation'] = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
$admin_body = json_encode($admin_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$admin_nonce = 'callback_nonce_admin_123456';
$admin_signature = 'sha256=' . hash_hmac('sha256', $callback_timestamp . '.' . $admin_nonce . '.' . $admin_body, ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET);
$admin_priority_response = argon_friend_link_worker_result_rest(new WP_REST_Request($admin_body, array(
    'X-Argon-Timestamp' => $callback_timestamp,
    'X-Argon-Nonce' => $admin_nonce,
    'X-Argon-Signature' => $admin_signature,
)));
$admin_link = argon_get_friend_link('fl_contract');
argon_worker_assert(
    $admin_priority_response instanceof WP_REST_Response
    && $admin_priority_response->get_status() === 200
    && $admin_link['name'] === '管理员名称'
    && $admin_link['display_name'] === '管理员副标题'
    && $admin_link['avatar'] === 'https://sample-blog.dev/admin.png'
    && $admin_link['description'] === '管理员描述'
    && $admin_link['links_page'] === 'https://sample-blog.dev/admin-links/',
    'Worker 自动结果覆盖了管理员字段。'
);

$replay_response = argon_friend_link_worker_result_rest($callback_request);
argon_worker_assert($replay_response instanceof WP_REST_Response && $replay_response->get_status() === 409, '相同回调随机数可以重放。');

$unknown_payload = $callback_payload;
$unknown_payload['generation'] = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
$unknown_payload['result']['backlink_state'] = 'unknown';
$unknown_payload['result']['state'] = 'unknown';
$unknown_payload['result']['accessible'] = false;
$unknown_payload['result']['readable'] = false;
$unknown_payload['result']['error'] = 'target_unreadable';
$unknown_payload['result']['checked_url'] = '';
$GLOBALS['argon_worker_test_links']['fl_contract']['check_source'] = 'admin_browser';
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
    && $unknown_updated_link['backlink_check_result'] === 'found'
    && $unknown_updated_link['check_source'] === 'admin_browser'
    && $unknown_updated_link['worker_error'] === 'target_unreadable',
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

argon_worker_assert(
    function_exists('argon_friend_link_worker_sync_result')
    && function_exists('argon_friend_link_worker_apply_result_payload'),
    'WordPress 未提供 Worker 结果主动拉取与共用落库函数。'
);
if (function_exists('argon_friend_link_worker_sync_result')) {
    argon_update_friend_link('fl_contract', array(
        'name' => '旧自动标题',
        'name_source' => 'worker',
        'auto_name' => true,
        'avatar' => 'https://blog.sample-blog.dev/favicon.ico',
        'avatar_source' => 'worker',
        'user_avatar' => false,
        'backlink_check_result' => 'unknown',
        'has_backlink' => null,
    ));
    argon_friend_link_worker_dispatch_async('fl_contract');
    $pull_generation = $GLOBALS['argon_worker_test_links']['fl_contract']['worker_generation'];
    $pull_payload = $callback_payload;
    $pull_payload['generation'] = $pull_generation;
    $pull_payload['result']['primary'] = '主动拉取标题';
    $pull_payload['result']['avatar'] = 'https://blog.sample-blog.dev/author.png';
    $pull_payload['result']['backlink_state'] = 'found';
    $GLOBALS['argon_worker_test_next_remote_response'] = array(
        'response' => array('code' => 200),
        'body' => json_encode($pull_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    );

    $pull_result = argon_friend_link_worker_sync_result('fl_contract', true);
    $pulled_link = argon_get_friend_link('fl_contract');
    $pull_request = end($GLOBALS['argon_worker_test_remote_posts']);
    $pull_timestamp = isset($pull_request['args']['headers']['X-Argon-Timestamp']) ? $pull_request['args']['headers']['X-Argon-Timestamp'] : '';
    $pull_nonce = isset($pull_request['args']['headers']['X-Argon-Nonce']) ? $pull_request['args']['headers']['X-Argon-Nonce'] : '';
    $pull_signature = isset($pull_request['args']['headers']['X-Argon-Signature']) ? $pull_request['args']['headers']['X-Argon-Signature'] : '';
    $expected_pull_signature = 'sha256=' . hash_hmac(
        'sha256',
        $pull_timestamp . '.' . $pull_nonce . '.' . $pull_request['args']['body'],
        ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET
    );
    argon_worker_assert(
        !is_wp_error($pull_result)
        && isset($pull_result['success'])
        && $pull_result['success'] === true
        && $pulled_link['worker_status'] === 'complete'
        && $pulled_link['name'] === '主动拉取标题'
        && $pulled_link['avatar'] === 'https://blog.sample-blog.dev/author.png'
        && $pulled_link['has_backlink'] === true,
        'Worker 回调缺失时，WordPress 主动拉取结果没有完成落库。'
    );
    argon_worker_assert(
        $pull_request['url'] === 'https://worker.sample-blog.dev/v1/jobs/fl_contract/result'
        && isset($pull_request['args']['blocking'])
        && $pull_request['args']['blocking'] === true
        && hash_equals($expected_pull_signature, $pull_signature),
        'WordPress 主动拉取请求未使用固定 Worker 地址、阻塞响应或提交密钥签名。'
    );

    argon_friend_link_worker_dispatch_async('fl_contract');
    $stale_local_generation = argon_get_friend_link('fl_contract')['worker_generation'];
    $stale_sync_request_count = count($GLOBALS['argon_worker_test_remote_posts']);
    $GLOBALS['argon_worker_test_next_remote_response'] = array(
        'response' => array('code' => 409),
        'body' => json_encode(array('success' => false, 'error' => 'stale_generation')),
    );
    $stale_sync_result = argon_friend_link_worker_sync_result('fl_contract', true);
    $recovered_link = argon_get_friend_link('fl_contract');
    argon_worker_assert(
        !is_wp_error($stale_sync_result)
        && isset($stale_sync_result['status'])
        && $stale_sync_result['status'] === 'redispatched'
        && $recovered_link['worker_status'] === 'submitted'
        && $recovered_link['worker_generation'] !== $stale_local_generation
        && $recovered_link['worker_dispatch_attempts'] === 1
        && count($GLOBALS['argon_worker_test_remote_posts']) === $stale_sync_request_count + 2,
        'Worker 主动拉取发现任务代际错位后没有自动废弃旧代际并重新派发。'
    );
}

if ($failures) {
    fwrite(STDERR, "友链 Worker 桥接测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "友链 Worker 桥接测试通过。\n";
