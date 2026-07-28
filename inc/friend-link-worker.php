<?php

/**
 * Cloudflare Worker 友链检测桥接。
 *
 * 配置优先级：wp-config.php 常量 > 同名环境变量 > WordPress 后台选项。
 * 后台保存的密钥不自动加载，也绝不进入前端脚本。
 */

if (!defined('ABSPATH')) {
    exit;
}

function argon_friend_link_worker_option_name($name) {
    $options = array(
        'ARGON_FRIEND_LINK_WORKER_ENABLED' => 'argon_friend_link_worker_enabled',
        'ARGON_FRIEND_LINK_WORKER_URL' => 'argon_friend_link_worker_url',
        'ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET' => 'argon_friend_link_submit_hmac_secret',
        'ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET' => 'argon_friend_link_callback_hmac_secret',
    );
    return isset($options[$name]) ? $options[$name] : '';
}

function argon_friend_link_worker_external_config($name) {
    if (defined($name)) {
        return array('source' => 'constant', 'value' => trim((string)constant($name)));
    }

    $value = getenv($name);
    if (is_string($value) && trim($value) !== '') {
        return array('source' => 'environment', 'value' => trim($value));
    }
    return null;
}

function argon_friend_link_worker_config_source($name) {
    $external = argon_friend_link_worker_external_config($name);
    if ($external !== null) return $external['source'];

    $option_name = argon_friend_link_worker_option_name($name);
    if ($option_name !== '' && get_option($option_name, null) !== null) return 'option';
    return 'default';
}

function argon_friend_link_worker_config_value($name, $default = '') {
    $external = argon_friend_link_worker_external_config($name);
    if ($external !== null) return $external['value'];

    $option_name = argon_friend_link_worker_option_name($name);
    if ($option_name !== '') {
        $value = get_option($option_name, null);
        if ($value !== null) return trim((string)$value);
    }
    return $default;
}

function argon_friend_link_worker_boolean_value($value) {
    return in_array(strtolower(trim((string)$value)), array('1', 'true', 'yes', 'on'), true);
}

function argon_friend_link_worker_url() {
    $url = argon_friend_link_worker_config_value('ARGON_FRIEND_LINK_WORKER_URL');
    return rtrim(esc_url_raw($url), '/');
}

function argon_friend_link_worker_submit_secret() {
    return argon_friend_link_worker_config_value('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET');
}

function argon_friend_link_worker_callback_secret() {
    return argon_friend_link_worker_config_value('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET');
}

function argon_friend_link_worker_enabled() {
    $enabled_external = argon_friend_link_worker_external_config('ARGON_FRIEND_LINK_WORKER_ENABLED');
    if ($enabled_external !== null) {
        return argon_friend_link_worker_boolean_value($enabled_external['value']);
    }

    $enabled_option = get_option('argon_friend_link_worker_enabled', null);
    if ($enabled_option !== null) {
        return argon_friend_link_worker_boolean_value($enabled_option);
    }

    // 兼容已使用旧版文档、仅在 wp-config.php 或环境变量中配置三项连接信息的站点。
    foreach (array(
        'ARGON_FRIEND_LINK_WORKER_URL',
        'ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET',
        'ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET',
    ) as $legacy_name) {
        $source = argon_friend_link_worker_config_source($legacy_name);
        if (!in_array($source, array('constant', 'environment'), true)
            || argon_friend_link_worker_config_value($legacy_name) === '') {
            return false;
        }
    }
    return true;
}

function argon_friend_link_worker_is_configured() {
    return argon_friend_link_worker_enabled()
        && argon_friend_link_worker_url() !== ''
        && argon_friend_link_worker_submit_secret() !== ''
        && argon_friend_link_worker_callback_secret() !== '';
}

function argon_friend_link_worker_rest_error($code, $message, $status) {
    return new WP_REST_Response(array(
        'success' => false,
        'error' => $code,
        'message' => $message,
    ), $status);
}

function argon_friend_link_worker_nonce() {
    return str_replace('-', '_', wp_generate_uuid4());
}

if (!function_exists('argon_friend_link_normalize_source')) {
    function argon_friend_link_normalize_source($source, $fallback = 'worker') {
        $source = sanitize_text_field((string)$source);
        return in_array($source, array('worker', 'visitor', 'admin'), true) ? $source : $fallback;
    }
}

if (!function_exists('argon_friend_link_field_source')) {
    function argon_friend_link_field_source($link, $field) {
        $source_key = $field . '_source';
        if (isset($link[$source_key]) && in_array($link[$source_key], array('worker', 'visitor', 'admin'), true)) {
            return $link[$source_key];
        }

        if ($field === 'name') return !empty($link['auto_name']) ? 'worker' : (!empty($link['name']) ? 'visitor' : 'worker');
        if ($field === 'display_name') return !empty($link['user_display_name']) ? 'visitor' : 'worker';
        if ($field === 'avatar') return !empty($link['user_avatar']) ? 'visitor' : 'worker';
        if ($field === 'description') return !empty($link['description']) ? 'visitor' : 'worker';
        if ($field === 'links_page') return !empty($link['links_page']) ? 'visitor' : 'worker';
        return 'worker';
    }
}

function argon_friend_link_worker_can_update_field($link, $field) {
    if (argon_friend_link_field_source($link, $field) === 'worker') return true;
    if ($field === 'description') {
        return empty($link['description']) && empty($link['auto_description']);
    }
    return empty($link[$field]);
}

function argon_friend_link_worker_avatar_is_better($current, $candidate) {
    $current = (string)$current;
    $candidate = (string)$candidate;
    if ($candidate === '' || $candidate === $current) return $candidate !== '';
    if ($current === '') return true;
    $candidate_is_generic = preg_match('/\/favicon\.ico(?:[?#]|$)/i', $candidate) === 1;
    $current_is_generic = preg_match('/\/favicon\.ico(?:[?#]|$)/i', $current) === 1;
    return !$candidate_is_generic || $current_is_generic;
}

function argon_friend_link_worker_create_ticket($link) {
    $worker_url = argon_friend_link_worker_url();
    $secret = argon_friend_link_worker_submit_secret();
    if (!argon_friend_link_worker_is_configured()) {
        return new WP_Error('worker_not_configured', '友链检测 Worker 未启用或配置不完整', array('status' => 503));
    }
    if (!is_array($link) || empty($link['id']) || empty($link['url'])) {
        return new WP_Error('invalid_friend_link', '友链记录不完整', array('status' => 400));
    }

    $job_id = sanitize_text_field($link['id']);
    $url = esc_url_raw($link['url']);
    $links_page = !empty($link['links_page']) ? esc_url_raw($link['links_page']) : '';
    if (!preg_match('/^[A-Za-z0-9_-]{1,96}$/', $job_id) || $url === '') {
        return new WP_Error('invalid_friend_link', '友链 ID 或地址无效', array('status' => 400));
    }

    $payload = array(
        'job_id' => $job_id,
        'url' => $url,
        'links_page' => $links_page,
    );
    $body = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($body) || $body === '') {
        return new WP_Error('ticket_encode_failed', '无法生成友链检测任务', array('status' => 500));
    }

    $timestamp = time();
    $nonce = argon_friend_link_worker_nonce();
    $signature = hash_hmac('sha256', $timestamp . '.' . $nonce . '.' . $body, $secret);
    $dispatch_attempts = max(0, intval(isset($link['worker_dispatch_attempts']) ? $link['worker_dispatch_attempts'] : 0)) + 1;
    $saved = argon_update_friend_link($job_id, array(
        'worker_generation' => $nonce,
        'worker_status' => 'dispatching',
        'worker_submitted_at' => $timestamp,
        'worker_dispatch_attempts' => $dispatch_attempts,
        'worker_error' => '',
        'next_worker_dispatch_at' => $timestamp + 10 * MINUTE_IN_SECONDS,
    ));
    if (!$saved) {
        return new WP_Error('ticket_state_failed', '无法保存友链检测任务代际', array('status' => 500));
    }

    return array(
        'url' => $worker_url . '/v1/jobs',
        'body' => $body,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Argon-Timestamp' => (string)$timestamp,
            'X-Argon-Nonce' => $nonce,
            'X-Argon-Signature' => 'sha256=' . $signature,
        ),
    );
}

function argon_friend_link_worker_error_status($error) {
    $data = $error->get_error_data();
    return is_array($data) && !empty($data['status']) ? intval($data['status']) : 400;
}

function argon_friend_link_worker_dispatch_async($link_id, $schedule_retry = true) {
    if (!argon_friend_link_worker_is_configured()) {
        return new WP_Error('worker_not_configured', '友链检测 Worker 未启用或配置不完整', array('status' => 503));
    }

    $link_id = sanitize_text_field($link_id);
    $link = $link_id !== '' ? argon_get_friend_link($link_id) : null;
    if (!$link) {
        return new WP_Error('friend_link_not_found', '友链不存在', array('status' => 404));
    }

    $ticket = argon_friend_link_worker_create_ticket($link);
    if (is_wp_error($ticket)) return $ticket;

    $response = wp_remote_post($ticket['url'], array(
        'headers' => $ticket['headers'],
        'body' => $ticket['body'],
        'blocking' => false,
        'timeout' => 1,
        'redirection' => 0,
        'data_format' => 'body',
    ));
    if (is_wp_error($response)) {
        $current = argon_get_friend_link($link_id);
        $attempts = max(1, intval(isset($current['worker_dispatch_attempts']) ? $current['worker_dispatch_attempts'] : 1));
        argon_update_friend_link($link_id, array(
            'worker_status' => 'dispatch_failed',
            'worker_error' => $response->get_error_message(),
            'next_worker_dispatch_at' => time() + min(3600, 60 * (2 ** min($attempts, 5))),
        ));
        if ($schedule_retry && $attempts < 3 && function_exists('wp_schedule_single_event')) {
            $args = array($link_id);
            if (!function_exists('wp_next_scheduled') || !wp_next_scheduled('argon_friend_link_worker_retry_dispatch', $args)) {
                wp_schedule_single_event(time() + min(3600, 60 * (2 ** min($attempts, 5))), 'argon_friend_link_worker_retry_dispatch', $args);
            }
        }
        return $response;
    }

    argon_update_friend_link($link_id, array(
        'worker_status' => 'submitted',
        'worker_dispatched_at' => time(),
        'worker_error' => '',
        'next_worker_dispatch_at' => 0,
    ));
    return true;
}

function argon_friend_link_worker_retry_dispatch($link_id) {
    argon_friend_link_worker_dispatch_async($link_id, true);
}
add_action('argon_friend_link_worker_retry_dispatch', 'argon_friend_link_worker_retry_dispatch', 10, 1);

function argon_friend_link_worker_dispatch_batch($limit = 3) {
    $limit = max(1, min(20, intval($limit)));
    $links = get_option('argon_friend_links', array());
    if (!is_array($links)) $links = array();
    $candidates = array_values(array_filter($links, function($link) {
        return is_array($link)
            && !empty($link['id'])
            && !empty($link['url'])
            && (!isset($link['status']) || $link['status'] !== 'rejected');
    }));
    usort($candidates, function($left, $right) {
        $left_checked = intval(isset($left['worker_checked_at']) ? $left['worker_checked_at'] : 0);
        $right_checked = intval(isset($right['worker_checked_at']) ? $right['worker_checked_at'] : 0);
        if ($left_checked === $right_checked) {
            return intval(isset($left['worker_submitted_at']) ? $left['worker_submitted_at'] : 0)
                - intval(isset($right['worker_submitted_at']) ? $right['worker_submitted_at'] : 0);
        }
        return $left_checked - $right_checked;
    });

    $submitted = 0;
    $failed = 0;
    foreach (array_slice($candidates, 0, $limit) as $link) {
        $result = argon_friend_link_worker_dispatch_async($link['id']);
        if (is_wp_error($result)) $failed++;
        else $submitted++;
    }
    return array('submitted' => $submitted, 'failed' => $failed);
}

function argon_friend_link_worker_dispatch_pending($limit = 3) {
    if (!argon_friend_link_worker_is_configured()) return array('submitted' => 0, 'failed' => 0);
    $limit = max(1, min(20, intval($limit)));
    $now = time();
    $links = get_option('argon_friend_links', array());
    if (!is_array($links)) $links = array();
    $candidates = array_values(array_filter($links, function($link) use ($now) {
        if (!is_array($link) || empty($link['id']) || empty($link['url']) || (isset($link['status']) && $link['status'] === 'rejected')) {
            return false;
        }
        $generation = isset($link['worker_generation']) ? (string)$link['worker_generation'] : '';
        $status = isset($link['worker_status']) ? (string)$link['worker_status'] : '';
        $submitted_at = intval(isset($link['worker_submitted_at']) ? $link['worker_submitted_at'] : 0);
        $next_dispatch_at = intval(isset($link['next_worker_dispatch_at']) ? $link['next_worker_dispatch_at'] : 0);
        $attempts = intval(isset($link['worker_dispatch_attempts']) ? $link['worker_dispatch_attempts'] : 0);
        if ($next_dispatch_at > $now || $attempts >= 3) return false;
        if ($generation === '' || in_array($status, array('', 'pending_dispatch', 'dispatch_failed'), true)) return true;
        return in_array($status, array('dispatching', 'submitted'), true) && $submitted_at < $now - 30 * MINUTE_IN_SECONDS;
    }));
    usort($candidates, function($left, $right) {
        return intval(isset($left['next_worker_dispatch_at']) ? $left['next_worker_dispatch_at'] : 0)
            - intval(isset($right['next_worker_dispatch_at']) ? $right['next_worker_dispatch_at'] : 0);
    });

    $submitted = 0;
    $failed = 0;
    foreach (array_slice($candidates, 0, $limit) as $link) {
        $result = argon_friend_link_worker_dispatch_async($link['id']);
        if (is_wp_error($result)) $failed++;
        else $submitted++;
    }
    return array('submitted' => $submitted, 'failed' => $failed);
}

function argon_friend_link_worker_run_pending_dispatch() {
    argon_friend_link_worker_dispatch_pending(3);
}
add_action('argon_friend_link_worker_dispatch_pending', 'argon_friend_link_worker_run_pending_dispatch');

function argon_friend_link_worker_ensure_dispatch_schedule() {
    if (!argon_friend_link_worker_is_configured() || !function_exists('wp_schedule_event')) return;
    if (!wp_next_scheduled('argon_friend_link_worker_dispatch_pending')) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', 'argon_friend_link_worker_dispatch_pending');
    }
}
add_action('init', 'argon_friend_link_worker_ensure_dispatch_schedule');

function argon_friend_link_worker_verify_callback($request) {
    $secret = argon_friend_link_worker_callback_secret();
    if ($secret === '') {
        return new WP_Error('worker_not_configured', '友链检测回调密钥尚未配置', array('status' => 503));
    }

    $body = (string)$request->get_body();
    if ($body === '' || strlen($body) > 16384) {
        return new WP_Error('invalid_body', '回调请求体无效', array('status' => 400));
    }

    $timestamp = (string)$request->get_header('x-argon-timestamp');
    $nonce = (string)$request->get_header('x-argon-nonce');
    $signature = strtolower((string)$request->get_header('x-argon-signature'));
    if (
        !preg_match('/^\d{10}$/', $timestamp)
        || abs(time() - intval($timestamp)) > 300
        || !preg_match('/^[A-Za-z0-9_-]{16,128}$/', $nonce)
        || !preg_match('/^sha256=[a-f0-9]{64}$/', $signature)
    ) {
        return new WP_Error('invalid_signature', '回调签名无效', array('status' => 401));
    }

    $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $nonce . '.' . $body, $secret);
    if (!hash_equals($expected, $signature)) {
        return new WP_Error('invalid_signature', '回调签名无效', array('status' => 401));
    }

    $replay_key = 'argon_flink_callback_' . hash('sha256', $nonce);
    if (get_transient($replay_key) !== false) {
        return new WP_Error('callback_replayed', '回调随机数已使用', array('status' => 409));
    }
    set_transient($replay_key, 1, 10 * MINUTE_IN_SECONDS);

    return array('body' => $body, 'nonce' => $nonce, 'timestamp' => intval($timestamp));
}

function argon_friend_link_worker_detected_url_allowed($link, $detected_url) {
    $current_host = wp_parse_url(isset($link['url']) ? $link['url'] : '', PHP_URL_HOST);
    $detected_host = wp_parse_url($detected_url, PHP_URL_HOST);
    if (!$current_host || !$detected_host) return false;
    if (function_exists('argon_is_allowed_friend_link_detected_host')) {
        return argon_is_allowed_friend_link_detected_host($current_host, $detected_host);
    }
    $base_host = preg_replace('/^(?:www|blog)\./i', '', strtolower($current_host));
    return in_array(strtolower($detected_host), array($base_host, 'www.' . $base_host, 'blog.' . $base_host), true);
}

function argon_friend_link_worker_result_rest($request) {
    $verified = argon_friend_link_worker_verify_callback($request);
    if (is_wp_error($verified)) {
        return argon_friend_link_worker_rest_error(
            $verified->get_error_code(),
            $verified->get_error_message(),
            argon_friend_link_worker_error_status($verified)
        );
    }

    $payload = json_decode($verified['body'], true);
    if (!is_array($payload) || intval(isset($payload['version']) ? $payload['version'] : 0) !== 1 || !is_array(isset($payload['result']) ? $payload['result'] : null)) {
        return argon_friend_link_worker_rest_error('invalid_payload', '回调数据结构无效', 400);
    }

    $job_id = isset($payload['job_id']) ? sanitize_text_field($payload['job_id']) : '';
    $generation = isset($payload['generation']) ? sanitize_text_field($payload['generation']) : '';
    if (!preg_match('/^[A-Za-z0-9_-]{1,96}$/', $job_id) || !preg_match('/^[A-Za-z0-9_-]{16,128}$/', $generation)) {
        return argon_friend_link_worker_rest_error('invalid_payload', '任务标识无效', 400);
    }

    $link = argon_get_friend_link($job_id);
    if (!$link) {
        return new WP_REST_Response(array('success' => true, 'ignored' => 'link_missing'), 200);
    }
    $current_generation = isset($link['worker_generation']) ? (string)$link['worker_generation'] : '';
    if ($current_generation === '' || !hash_equals($current_generation, $generation)) {
        return new WP_REST_Response(array('success' => true, 'ignored' => 'stale_generation'), 200);
    }

    $result = $payload['result'];
    $state = isset($result['state']) ? sanitize_text_field($result['state']) : 'error';
    if (!in_array($state, array('complete', 'unknown', 'error'), true)) {
        return argon_friend_link_worker_rest_error('invalid_payload', '任务状态无效', 400);
    }
    if (isset($result['source']) && $result['source'] !== 'cloudflare_worker') {
        return argon_friend_link_worker_rest_error('invalid_payload', '任务来源无效', 400);
    }

    $checked_at = intval(isset($payload['checked_at']) ? $payload['checked_at'] : 0);
    if ($checked_at <= 0 || $checked_at > time() + 300) $checked_at = time();
    $update = array(
        'worker_status' => $state,
        'worker_checked_at' => $checked_at,
        'worker_callback_at' => time(),
        'worker_attempts' => max(0, intval(isset($result['attempts']) ? $result['attempts'] : 0)),
        'worker_dispatch_attempts' => 0,
        'worker_error' => isset($result['error']) ? sanitize_text_field($result['error']) : '',
        'check_source' => 'cloudflare_worker',
        'last_check' => $checked_at,
        'next_worker_dispatch_at' => 0,
    );
    if ($state === 'complete' || (isset($result['accessible']) && $result['accessible'] === true)) {
        // unknown/error 常由地区网络或验证页导致，不能把既有可访问状态误降为 false。
        $update['accessible'] = isset($result['accessible']) && $result['accessible'] === true;
    }

    $detected_url = isset($result['detected_url']) ? esc_url_raw($result['detected_url']) : '';
    if ($detected_url !== '' && argon_friend_link_worker_detected_url_allowed($link, $detected_url)) {
        $update['browser_detected_url'] = $detected_url;
    }
    $primary = isset($result['primary']) ? sanitize_text_field($result['primary']) : '';
    if ($primary !== '' && argon_friend_link_worker_can_update_field($link, 'name')) {
        $update['name'] = $primary;
        $update['name_source'] = 'worker';
        $update['auto_name'] = true;
    }
    $subtitle = isset($result['subtitle']) ? sanitize_text_field($result['subtitle']) : '';
    if ($subtitle !== '' && argon_friend_link_worker_can_update_field($link, 'display_name')) {
        $update['display_name'] = $subtitle;
        $update['display_name_source'] = 'worker';
        $update['user_display_name'] = false;
    }
    $description = isset($result['description']) ? sanitize_text_field($result['description']) : '';
    if ($description !== '' && argon_friend_link_worker_can_update_field($link, 'description')) {
        $update['auto_description'] = $description;
        $update['description_source'] = 'worker';
    }
    $avatar = isset($result['avatar']) ? esc_url_raw($result['avatar']) : '';
    if (
        $avatar !== ''
        && argon_friend_link_worker_can_update_field($link, 'avatar')
        && argon_friend_link_worker_avatar_is_better(isset($link['avatar']) ? $link['avatar'] : '', $avatar)
    ) {
        $update['avatar'] = $avatar;
        $update['avatar_source'] = 'worker';
        $update['user_avatar'] = false;
    }
    $links_page = isset($result['links_page']) ? esc_url_raw($result['links_page']) : '';
    if (
        $links_page !== ''
        && argon_friend_link_worker_can_update_field($link, 'links_page')
        && argon_friend_link_worker_detected_url_allowed($link, $links_page)
    ) {
        $update['links_page'] = $links_page;
        $update['links_page_source'] = 'worker';
    }

    $backlink_state = isset($result['backlink_state']) ? sanitize_text_field($result['backlink_state']) : 'unknown';
    if (!in_array($backlink_state, array('found', 'missing', 'unknown'), true)) {
        return argon_friend_link_worker_rest_error('invalid_payload', '互链状态无效', 400);
    }
    $update['last_backlink_attempt'] = $checked_at;
    if ($backlink_state === 'found' || $backlink_state === 'missing') {
        $update['backlink_check_result'] = $backlink_state;
        $update['has_backlink'] = $backlink_state === 'found';
        $update['verified'] = $backlink_state === 'found';
        $update['last_backlink_check'] = $checked_at;
    } else {
        $known_backlink_state = isset($link['backlink_check_result']) ? (string)$link['backlink_check_result'] : '';
        if (!in_array($known_backlink_state, array('found', 'missing'), true)) {
            $update['backlink_check_result'] = 'unknown';
        }
    }

    if (!argon_update_friend_link($job_id, $update)) {
        return argon_friend_link_worker_rest_error('update_failed', '无法保存友链检测结果', 500);
    }
    return new WP_REST_Response(array('success' => true), 200);
}

function argon_register_friend_link_worker_routes() {
    register_rest_route('argon/v1', '/friend-link-result', array(
        'methods' => 'POST',
        'callback' => 'argon_friend_link_worker_result_rest',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'argon_register_friend_link_worker_routes');
