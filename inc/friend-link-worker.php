<?php

/**
 * Cloudflare Worker 友链检测桥接。
 *
 * 密钥必须通过 wp-config.php 常量或同名环境变量提供，绝不进入主题设置或前端脚本。
 */

if (!defined('ABSPATH')) {
    exit;
}

function argon_friend_link_worker_config_value($name, $default = '') {
    if (defined($name)) {
        return trim((string)constant($name));
    }

    $value = getenv($name);
    return is_string($value) && trim($value) !== '' ? trim($value) : $default;
}

function argon_friend_link_worker_url() {
    $url = argon_friend_link_worker_config_value('ARGON_FRIEND_LINK_WORKER_URL', 'https://worker.cartol.top');
    return rtrim(esc_url_raw($url), '/');
}

function argon_friend_link_worker_submit_secret() {
    return argon_friend_link_worker_config_value('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET');
}

function argon_friend_link_worker_callback_secret() {
    return argon_friend_link_worker_config_value('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET');
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

function argon_friend_link_worker_create_dispatch_token($link_id) {
    $link_id = sanitize_text_field($link_id);
    if ($link_id === '' || !argon_get_friend_link($link_id)) {
        return '';
    }

    $token = wp_generate_password(48, false, false);
    set_transient('argon_flink_dispatch_' . hash('sha256', $token), $link_id, 10 * MINUTE_IN_SECONDS);
    return $token;
}

function argon_friend_link_worker_create_ticket($link) {
    $worker_url = argon_friend_link_worker_url();
    $secret = argon_friend_link_worker_submit_secret();
    if ($worker_url === '' || $secret === '') {
        return new WP_Error('worker_not_configured', '友链检测 Worker 尚未完成服务端密钥配置', array('status' => 503));
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
    $saved = argon_update_friend_link($job_id, array(
        'worker_generation' => $nonce,
        'worker_status' => 'dispatching',
        'worker_submitted_at' => $timestamp,
        'worker_error' => '',
        'next_browser_check_at' => $timestamp + 10 * MINUTE_IN_SECONDS,
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

function argon_friend_link_worker_ticket_rest($request) {
    $raw_body = (string)$request->get_body();
    $payload = json_decode($raw_body, true);
    if (!is_array($payload)) {
        return argon_friend_link_worker_rest_error('invalid_json', '请求格式无效', 400);
    }

    $job_id = isset($payload['job_id']) ? sanitize_text_field($payload['job_id']) : '';
    $nonce = isset($payload['nonce']) ? sanitize_text_field($payload['nonce']) : '';
    $is_admin = current_user_can('manage_options')
        && $nonce !== ''
        && wp_verify_nonce($nonce, 'argon_friend_link_worker_ticket');

    $dispatch_key = '';
    if (!$is_admin) {
        $dispatch_token = isset($payload['dispatch_token']) ? sanitize_text_field($payload['dispatch_token']) : '';
        if ($dispatch_token === '') {
            return argon_friend_link_worker_rest_error('forbidden', '无权签发友链检测任务', 403);
        }
        $dispatch_key = 'argon_flink_dispatch_' . hash('sha256', $dispatch_token);
        $authorized_job_id = get_transient($dispatch_key);
        if (!is_string($authorized_job_id) || $authorized_job_id === '') {
            return argon_friend_link_worker_rest_error('forbidden', '友链检测令牌无效或已使用', 403);
        }
        $job_id = sanitize_text_field($authorized_job_id);
    }

    $link = $job_id !== '' ? argon_get_friend_link($job_id) : null;
    if (!$link) {
        return argon_friend_link_worker_rest_error('not_found', '友链不存在', 404);
    }

    $ticket = argon_friend_link_worker_create_ticket($link);
    if (is_wp_error($ticket)) {
        return argon_friend_link_worker_rest_error(
            $ticket->get_error_code(),
            $ticket->get_error_message(),
            argon_friend_link_worker_error_status($ticket)
        );
    }

    if ($dispatch_key !== '') {
        delete_transient($dispatch_key);
    }
    return new WP_REST_Response(array('success' => true, 'ticket' => $ticket), 200);
}

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
        'worker_error' => isset($result['error']) ? sanitize_text_field($result['error']) : '',
        'check_source' => 'cloudflare_worker',
        'last_check' => $checked_at,
        'next_browser_check_at' => $checked_at + DAY_IN_SECONDS,
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
    if ($primary !== '' && (!empty($link['auto_name']) || empty($link['name']))) {
        $update['name'] = $primary;
    }
    $subtitle = isset($result['subtitle']) ? sanitize_text_field($result['subtitle']) : '';
    if ($subtitle !== '' && empty($link['user_display_name'])) {
        $update['display_name'] = $subtitle;
    }
    $description = isset($result['description']) ? sanitize_text_field($result['description']) : '';
    if ($description !== '') {
        $update['auto_description'] = $description;
    }
    $avatar = isset($result['avatar']) ? esc_url_raw($result['avatar']) : '';
    if ($avatar !== '' && empty($link['user_avatar'])) {
        $update['avatar'] = $avatar;
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
    register_rest_route('argon/v1', '/friend-link-ticket', array(
        'methods' => 'POST',
        'callback' => 'argon_friend_link_worker_ticket_rest',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('argon/v1', '/friend-link-result', array(
        'methods' => 'POST',
        'callback' => 'argon_friend_link_worker_result_rest',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'argon_register_friend_link_worker_routes');
