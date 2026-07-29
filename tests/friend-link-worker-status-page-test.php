<?php

/**
 * 管理员 Worker 实时状态页与同源代理契约测试。
 *
 * 运行：php tests/friend-link-worker-status-page-test.php
 */

$root = dirname(__DIR__);
$functions_source = file_get_contents($root . '/functions.php');
$bridge_source = file_get_contents($root . '/inc/friend-link-worker.php');
$friends_page_source = file_get_contents($root . '/friend-links.php');
$feedback_page_source = file_get_contents($root . '/feedback.php');
$surface_source = is_file($root . '/inc/special-page-surface.php')
    ? file_get_contents($root . '/inc/special-page-surface.php')
    : false;
$page_source = is_file($root . '/worker.php') ? file_get_contents($root . '/worker.php') : false;
$failures = array();

$worker_404_source = false;
$worker_route_source = false;
if ($functions_source !== false) {
    $worker_404_start = strpos($functions_source, 'function argon_worker_status_render_404()');
    $worker_route_hook = strpos($functions_source, "add_action('template_redirect', 'argon_worker_status_template'");
    if ($worker_404_start !== false && $worker_route_hook !== false && $worker_route_hook > $worker_404_start) {
        $worker_404_source = substr($functions_source, $worker_404_start, $worker_route_hook - $worker_404_start);
    }

    $worker_route_start = strpos($functions_source, 'function argon_worker_status_template()');
    $feedback_route_hook = strpos($functions_source, "add_action('template_redirect', 'argon_feedback_template')");
    if ($worker_route_start !== false && $feedback_route_hook !== false && $feedback_route_hook > $worker_route_start) {
        $worker_route_source = substr($functions_source, $worker_route_start, $feedback_route_hook - $worker_route_start);
    }
}

function argon_worker_status_assert($condition, $message) {
    global $failures;
    if (!$condition) $failures[] = $message;
}

argon_worker_status_assert(
    $functions_source !== false
    && strpos($functions_source, "add_rewrite_rule('^worker/?$'") !== false
    && strpos($functions_source, "\$vars[] = 'argon_worker_status';") !== false
    && strpos($functions_source, "get_query_var('argon_worker_status')") !== false
    && strpos($functions_source, "'/worker.php'") !== false,
    '/worker 没有完整注册 rewrite、query var 和模板入口。'
);
argon_worker_status_assert(
    $functions_source !== false
    && strpos($functions_source, "argon_worker_status_maybe_flush_rewrite_rules', 99") !== false
    && strpos($functions_source, "get_option('argon_worker_status_rewrite_version')") !== false
    && strpos($functions_source, "flush_rewrite_rules(false)") !== false
    && strpos($functions_source, "update_option('argon_worker_status_rewrite_version'") !== false,
    '直接覆盖主题文件后，/worker rewrite 没有一次性刷新机制。'
);
argon_worker_status_assert(
    $worker_404_source !== false
    && strpos($worker_404_source, 'global $wp_query;') !== false
    && strpos($worker_404_source, '$wp_query->set_404();') !== false
    && strpos($worker_404_source, 'status_header(404);') !== false
    && strpos($worker_404_source, 'nocache_headers();') !== false
    && strpos($worker_404_source, 'get_404_template();') !== false
    && strpos($worker_404_source, 'include $template;') !== false,
    '/worker 无权限响应没有使用 WordPress 404 查询状态和标准 404 模板。'
);
argon_worker_status_assert(
    $functions_source !== false
    && strpos($functions_source, "add_action('template_redirect', 'argon_worker_status_template', 0);") !== false,
    '/worker 权限处理没有早于 redirect_canonical，匿名无斜杠请求仍可能先被重定向。'
);
argon_worker_status_assert(
    $worker_route_source !== false
    && preg_match(
        "~if\\s*\\(\\s*!is_user_logged_in\\(\\)\\s*\\|\\|\\s*!current_user_can\\(\\s*'manage_options'\\s*\\)\\s*\\)\\s*\\{\\s*argon_worker_status_render_404\\(\\);\\s*exit;\\s*\\}~s",
        $worker_route_source
    ) === 1
    && strpos($worker_route_source, 'auth_redirect(') === false
    && strpos($worker_route_source, 'status_header(403)') === false
    && strpos($worker_route_source, 'wp_die(') === false
    && strpos($worker_route_source, 'nocache_headers()') !== false
    && strpos($worker_route_source, "header('X-Robots-Tag: noindex, nofollow'") !== false,
    '/worker 模板入口没有让匿名与无 manage_options 权限请求统一返回 404，或仍会登录跳转/返回 403。'
);
argon_worker_status_assert(
    $bridge_source !== false
    && strpos($bridge_source, "'/v1/status'") !== false
    && strpos($bridge_source, "'action' => 'status'") !== false
    && strpos($bridge_source, "'site_url' => home_url('/')") !== false
    && strpos($bridge_source, "hash_hmac('sha256'") !== false
    && strpos($bridge_source, "register_rest_route('argon/v1', '/friend-link-worker-status'") !== false
    && strpos($bridge_source, "'permission_callback' => 'argon_friend_link_worker_status_permission'") !== false,
    'WordPress 没有通过现有 HMAC 约定代理 Worker /v1/status，或 REST 路由缺少管理员权限回调。'
);
argon_worker_status_assert($page_source !== false, '缺少 worker.php 管理员实时状态页。');
if ($page_source !== false) {
    argon_worker_status_assert(
        $surface_source !== false
        && $friends_page_source !== false
        && $feedback_page_source !== false
        && strpos($friends_page_source, "'/inc/special-page-surface.php'") !== false
        && strpos($feedback_page_source, "'/inc/special-page-surface.php'") !== false
        && strpos($page_source, "'/inc/special-page-surface.php'") !== false
        && strpos($friends_page_source, 'argon_special_page_surface_open(') !== false
        && strpos($feedback_page_source, 'argon_special_page_surface_open(') !== false
        && strpos($page_source, 'argon_special_page_surface_open(') !== false
        && strpos($friends_page_source, 'argon_special_page_surface_header(') !== false
        && strpos($feedback_page_source, 'argon_special_page_surface_header(') !== false
        && strpos($page_source, 'argon_special_page_surface_header(') !== false
        && strpos($friends_page_source, 'argon_special_page_surface_close(') !== false
        && strpos($feedback_page_source, 'argon_special_page_surface_close(') !== false
        && strpos($page_source, 'argon_special_page_surface_close(') !== false
        && strrpos($friends_page_source, 'argon_special_page_surface_close(') > strrpos($friends_page_source, '</script>')
        && strrpos($feedback_page_source, 'argon_special_page_surface_close(') > strrpos($feedback_page_source, '</script>')
        && strrpos($page_source, 'argon_special_page_surface_close(') > strrpos($page_source, '</script>'),
        '/friends/、/feedback 与 /worker 没有复用同一个页面骨架和头部模板。'
    );
    argon_worker_status_assert(
        $surface_source !== false
        && strpos($surface_source, 'id="argon-special-page-surface-style"') !== false
        && strpos($surface_source, '.argon-special-page-card') !== false
        && strpos($surface_source, '.argon-special-page-badge') !== false
        && strpos($surface_source, '.argon-special-page-table') !== false
        && strpos($surface_source, '.argon-special-page-empty') !== false
        && strpos($surface_source, 'flinks-') === false
        && strpos($surface_source, 'html.darkmode body.argon-special-page') !== false
        && strpos($surface_source, 'body.argon-special-page #footer') !== false
        && strpos($surface_source, 'max-width: calc(var(--argon-special-page-width, 1040px) - 40px) !important;') !== false
        && strpos($surface_source, 'data-argon-special-page') !== false
        && strpos($surface_source, "getAttribute('data-argon-special-page-class')") !== false
        && strpos($page_source, 'id="argon-worker-status-style"') === false,
        '/worker 仍维护独立 CSS，或共享的卡片、徽章、表格、空状态、明暗主题规则不完整。'
    );
    argon_worker_status_assert(
        strpos($page_source, "argon_special_page_surface_open('worker-page')") !== false
        && strpos($page_source, 'argon-special-page-card') !== false
        && strpos($page_source, 'argon-special-page-badge') !== false
        && strpos($page_source, 'argon-special-page-table') !== false
        && strpos($page_source, 'argon-special-page-empty') !== false
        && strpos($page_source, 'flinks-') === false,
        '/worker 没有实际使用三个自定义功能页共享的卡片、徽章、表格或空状态组件。'
    );
    $worker_header_position = strpos($page_source, 'argon_special_page_surface_header(array(');
    $worker_list_position = strpos($page_source, '<div id="argon-worker-status-page" class="argon-special-page-list">');
    argon_worker_status_assert(
        $worker_header_position !== false
        && $worker_list_position !== false
        && $worker_header_position < $worker_list_position
        && preg_match(
            '~<article class="[^"]*argon-special-page-card argon-special-page-section"[^>]*aria-labelledby="argon-worker-summary-title">.*?<div class="argon-special-page-section-header">.*?<section class="argon-special-page-grid" id="argon-worker-summary"~s',
            $page_source
        ) === 1,
        '/worker 页头仍嵌套在列表中，或状态汇总没有复用三页共享的分区卡片结构。'
    );
    argon_worker_status_assert(
        strpos($feedback_page_source, 'argon-special-page-card') !== false
        && strpos($feedback_page_source, 'argon-special-page-badge') !== false
        && strpos($feedback_page_source, 'argon-special-page-empty') !== false
        && strpos($feedback_page_source, 'flinks-') === false,
        '/feedback 没有实际使用三页共享的卡片、徽章或空状态组件。'
    );
    argon_worker_status_assert(
        strpos($friends_page_source, 'argon-special-page-list') !== false
        && strpos($friends_page_source, 'argon-special-page-card') !== false
        && strpos($friends_page_source, 'argon-special-page-section') !== false
        && strpos($friends_page_source, 'argon-special-page-grid') !== false,
        '/friends/ 没有实际使用三个自定义功能页的共享列表、卡片、分区或网格组件。'
    );
    argon_worker_status_assert(
        strpos($feedback_page_source, '.feedback-header-card') === false
        && strpos($feedback_page_source, 'body.feedback-page #primary') === false
        && strpos($feedback_page_source, "document.body.classList.add('feedback-page')") === false
        && strpos($feedback_page_source, 'feedback-badge argon-special-page-badge') !== false
        && strpos($feedback_page_source, 'feedback-empty argon-special-page-empty') !== false
        && strpos($feedback_page_source, 'feedback-empty-filter') !== false,
        '/feedback 仍复制页头、布局或页面生命周期，或共享徽章/静态空状态破坏了筛选空状态。'
    );
    argon_worker_status_assert(
        strpos($page_source, 'argon-worker-status-header') === false
        && strpos($page_source, 'argon-worker-status-panel') === false
        && strpos($page_source, 'argon-worker-status-card') === false,
        '/worker 仍残留独立的 header、panel 或 card 视觉体系。'
    );
    argon_worker_status_assert(
        preg_match(
            "~if\\s*\\(\\s*!is_user_logged_in\\(\\)\\s*\\|\\|\\s*!current_user_can\\(\\s*'manage_options'\\s*\\)\\s*\\)\\s*\\{\\s*argon_worker_status_render_404\\(\\);\\s*return;\\s*\\}~s",
            $page_source
        ) === 1
        && strpos($page_source, 'auth_redirect(') === false
        && strpos($page_source, 'status_header(403)') === false
        && strpos($page_source, 'wp_die(') === false
        && strpos($page_source, 'nocache_headers()') !== false
        && strpos($page_source, "header('X-Robots-Tag: noindex, nofollow'") !== false,
        'worker.php 没有让匿名与无 manage_options 权限请求统一返回 404，或仍会登录跳转/返回 403。'
    );
    argon_worker_status_assert(
        strpos($page_source, "rest_url('argon/v1/friend-link-worker-status')") !== false
        && strpos($page_source, "wp_create_nonce('wp_rest')") !== false
        && strpos($page_source, "credentials: 'same-origin'") !== false
        && strpos($page_source, "cache: 'no-store'") !== false,
        '状态页没有使用 WP REST nonce 通过同源凭据、禁缓存轮询。'
    );
    argon_worker_status_assert(
        strpos($page_source, 'setInterval(') === false
        && strpos($page_source, 'setTimeout(poll, 500)') !== false
        && strpos($page_source, 'if (stopped || inFlight || document.hidden || !page.isConnected) return;') !== false
        && strpos($page_source, 'document.hidden') !== false
        && strpos($page_source, "visibilitychange") !== false,
        '状态页不是每 500ms 递归非重叠轮询，或隐藏页面时没有暂停。'
    );
    argon_worker_status_assert(
        strpos($page_source, "'runtime.cron'") !== false
        && strpos($page_source, "'runtime.queue'") !== false
        && strpos($page_source, "'runtime.probe'") !== false
        && strpos($page_source, 'slice(0, 12)') === false,
        '顶部汇总没有固定展示 cron、queue、probe 实时状态，或仍会截断关键卡片。'
    );
    argon_worker_status_assert(
        strpos($page_source, 'ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET') === false
        && strpos($page_source, 'ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET') === false
        && strpos($page_source, 'ADMIN_READ_TOKEN') === false,
        '状态页前端泄露了 Worker Secret 名称或读取令牌。'
    );
}

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!defined('ARGON_FRIEND_LINK_WORKER_ENABLED')) define('ARGON_FRIEND_LINK_WORKER_ENABLED', 'true');
if (!defined('ARGON_FRIEND_LINK_WORKER_URL')) define('ARGON_FRIEND_LINK_WORKER_URL', 'https://worker.example.net');
if (!defined('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET')) define('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET', 'status-submit-secret');
if (!defined('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET')) define('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET', 'status-callback-secret');

$GLOBALS['argon_worker_status_remote_request'] = null;
$GLOBALS['argon_worker_status_remote_mode'] = 'ok';
$GLOBALS['argon_worker_status_routes'] = array();
$GLOBALS['argon_worker_status_is_admin'] = true;

class WP_Error {
    private $code;
    private $message;
    private $data;
    public function __construct($code = '', $message = '', $data = null) {
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
    private $headers;
    public function __construct($data = null, $status = 200, $headers = array()) {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }
    public function get_data() { return $this->data; }
    public function get_status() { return $this->status; }
    public function get_headers() { return $this->headers; }
}

function sanitize_text_field($value) { return trim((string)$value); }
function esc_url_raw($value) { return filter_var((string)$value, FILTER_SANITIZE_URL); }
function get_option($name, $default = false) { return $default; }
function add_action($hook, $callback, $priority = 10, $args = 1) {}
function add_filter($hook, $callback, $priority = 10, $args = 1) {}
function register_rest_route($namespace, $route, $args) {
    $GLOBALS['argon_worker_status_routes'][$namespace . $route] = $args;
}
function wp_generate_uuid4() { return '12345678-1234-4234-8234-123456789012'; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function home_url($path = '') { return 'https://blog.example.com' . $path; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function current_user_can($capability) { return $GLOBALS['argon_worker_status_is_admin'] && $capability === 'manage_options'; }
function wp_remote_post($url, $args) {
    $GLOBALS['argon_worker_status_remote_request'] = array('url' => $url, 'args' => $args);
    if ($GLOBALS['argon_worker_status_remote_mode'] === 'transport_error') {
        return new WP_Error('http_request_failed', 'Connection timed out');
    }
    if ($GLOBALS['argon_worker_status_remote_mode'] === 'http_error') {
        return array(
            'response' => array('code' => 503),
            'body' => json_encode(array('error' => 'upstream_unavailable', 'message' => 'Unavailable')),
        );
    }
    if ($GLOBALS['argon_worker_status_remote_mode'] === 'invalid_json') {
        return array('response' => array('code' => 200), 'body' => '<html>not json</html>');
    }
    return array(
        'response' => array('code' => 200),
        'body' => json_encode(array(
            'ok' => true,
            'service' => 'friend-link-checker',
            'summary' => array('queued' => 2, 'running' => 1),
            'submit_secret' => 'must-not-reach-the-browser',
            'runtime' => array(
                'admin_read_token' => 'also-must-not-reach-the-browser',
                'last_scheduled_at' => 1785312000,
                'probe' => array(
                    'status' => 'ok',
                    'detail' => array(
                        'safe_count' => 2,
                        'nested' => array(
                            array(
                                'callback_hmac' => 'deep-secret',
                                'safe_message' => 'kept',
                            ),
                        ),
                    ),
                ),
            ),
        )),
    );
}
function wp_remote_retrieve_response_code($response) { return intval($response['response']['code']); }
function wp_remote_retrieve_body($response) { return (string)$response['body']; }

if ($bridge_source !== false) require_once $root . '/inc/friend-link-worker.php';

if (function_exists('argon_friend_link_worker_fetch_status')) {
    $status = argon_friend_link_worker_fetch_status();
    $request = $GLOBALS['argon_worker_status_remote_request'];
    $body = isset($request['args']['body']) ? json_decode($request['args']['body'], true) : null;
    $headers = isset($request['args']['headers']) ? $request['args']['headers'] : array();
    $timestamp = isset($headers['X-Argon-Timestamp']) ? $headers['X-Argon-Timestamp'] : '';
    $nonce = isset($headers['X-Argon-Nonce']) ? $headers['X-Argon-Nonce'] : '';
    $raw_body = isset($request['args']['body']) ? $request['args']['body'] : '';
    $expected_signature = 'sha256=' . hash_hmac(
        'sha256',
        $timestamp . '.' . $nonce . '.' . $raw_body,
        ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET
    );
    argon_worker_status_assert(
        !is_wp_error($status)
        && isset($status['service'])
        && $status['service'] === 'friend-link-checker'
        && isset($request['url'])
        && $request['url'] === 'https://worker.example.net/v1/status'
        && isset($request['args']['limit_response_size'])
        && $request['args']['limit_response_size'] === 512 * 1024
        && $body === array('action' => 'status', 'site_url' => 'https://blog.example.com/')
        && isset($headers['X-Argon-Signature'])
        && hash_equals($expected_signature, $headers['X-Argon-Signature'])
        && isset($request['args']['blocking'])
        && $request['args']['blocking'] === true,
        '状态代理没有按约定向 /v1/status 发送签名 JSON，或没有返回有效 Worker 数据。'
    );
} else {
    argon_worker_status_assert(false, '缺少 argon_friend_link_worker_fetch_status()。');
}

if (function_exists('argon_friend_link_worker_fetch_status')) {
    foreach (array('transport_error', 'http_error', 'invalid_json') as $remote_mode) {
        $GLOBALS['argon_worker_status_remote_mode'] = $remote_mode;
        $status_error = argon_friend_link_worker_fetch_status();
        argon_worker_status_assert(
            is_wp_error($status_error)
            && is_array($status_error->get_error_data())
            && intval($status_error->get_error_data()['status']) === 502,
            'Worker 状态代理没有把 ' . $remote_mode . ' 映射为 502 上游错误。'
        );
    }
    $GLOBALS['argon_worker_status_remote_mode'] = 'ok';
}

if (function_exists('argon_register_friend_link_worker_routes')) {
    argon_register_friend_link_worker_routes();
    $route = isset($GLOBALS['argon_worker_status_routes']['argon/v1/friend-link-worker-status'])
        ? $GLOBALS['argon_worker_status_routes']['argon/v1/friend-link-worker-status']
        : null;
    argon_worker_status_assert(
        is_array($route)
        && isset($route['methods'])
        && $route['methods'] === 'GET'
        && isset($route['callback'])
        && $route['callback'] === 'argon_friend_link_worker_status_rest',
        '管理员 Worker 状态 REST 路由未正确注册。'
    );
}

if (function_exists('argon_friend_link_worker_status_permission')) {
    $GLOBALS['argon_worker_status_is_admin'] = false;
    argon_worker_status_assert(!argon_friend_link_worker_status_permission(), '非管理员可以读取 Worker 状态。');
    $GLOBALS['argon_worker_status_is_admin'] = true;
    argon_worker_status_assert(argon_friend_link_worker_status_permission(), '管理员无法读取 Worker 状态。');
}

if (function_exists('argon_friend_link_worker_status_rest')) {
    $rest_response = argon_friend_link_worker_status_rest(null);
    $rest_data = $rest_response instanceof WP_REST_Response ? $rest_response->get_data() : array();
    $rest_headers = $rest_response instanceof WP_REST_Response ? $rest_response->get_headers() : array();
    $proxied_worker = isset($rest_data['worker']) && is_array($rest_data['worker']) ? $rest_data['worker'] : array();
    $nested_probe = isset($proxied_worker['runtime']['probe']) && is_array($proxied_worker['runtime']['probe'])
        ? $proxied_worker['runtime']['probe']
        : array();
    $nested_detail = isset($nested_probe['detail']) && is_array($nested_probe['detail'])
        ? $nested_probe['detail']
        : array();
    $nested_item = isset($nested_detail['nested'][0]) && is_array($nested_detail['nested'][0])
        ? $nested_detail['nested'][0]
        : array();
    argon_worker_status_assert(
        $rest_response instanceof WP_REST_Response
        && $rest_response->get_status() === 200
        && isset($rest_headers['Cache-Control'])
        && strpos($rest_headers['Cache-Control'], 'no-store') !== false
        && !array_key_exists('submit_secret', $proxied_worker)
        && isset($proxied_worker['runtime'])
        && !array_key_exists('admin_read_token', $proxied_worker['runtime'])
        && isset($proxied_worker['runtime']['last_scheduled_at'])
        && isset($nested_detail['safe_count'])
        && isset($nested_item['safe_message'])
        && !array_key_exists('callback_hmac', $nested_item),
        '同源状态代理没有禁用缓存，或没有在保留运行详情的同时递归剔除敏感字段。'
    );
}

if ($failures) {
    fwrite(STDERR, "Worker 状态页测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Worker 状态页测试通过。\n";
