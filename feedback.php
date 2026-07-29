<?php
/**
 * 问题反馈页面
 * @package Argon
 */

// 在加载 WordPress 之前初始化 session
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) $wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
require_once($wp_load_path);

// 引入反馈邮件模板
require_once(get_template_directory() . '/email-templates/feedback-notify.php');

// 权限检查
$is_admin = current_user_can('manage_options');

// 处理验证码刷新请求（AJAX）
if (isset($_GET['action']) && $_GET['action'] === 'refresh_captcha') {
	header('Content-Type: application/json; charset=utf-8');
	if (function_exists('get_comment_captcha_seed')) {
		get_comment_captcha_seed(true);
	}
	$captcha = function_exists('get_comment_captcha') ? get_comment_captcha() : '';
	echo json_encode(['success' => true, 'captcha' => $captcha]);
	exit;
}

// 授权链接访问检查
$auth_view_id = null;
$auth_view_token = null;
$is_auth_view = false;
$can_view_private = false; // 是否可以查看私密留言

if (isset($_GET['id']) && isset($_GET['token'])) {
	$auth_view_id = sanitize_text_field($_GET['id']);
	$auth_view_token = sanitize_text_field($_GET['token']);
	if (argon_verify_feedback_token($auth_view_id, $auth_view_token)) {
		$is_auth_view = true;
		$can_view_private = true; // 授权链接可以查看私密留言
	}
}

// 管理员也可以查看私密留言
if ($is_admin) {
	$can_view_private = true;
}

// AJAX 处理图片上传
if (isset($_POST['feedback_action']) && $_POST['feedback_action'] === 'upload_image') {
	header('Content-Type: application/json; charset=utf-8');
	
	// 验证 nonce（防止 CSRF 攻击）
	if (!isset($_POST['feedback_upload_nonce']) || !wp_verify_nonce($_POST['feedback_upload_nonce'], 'argon_feedback_upload')) {
		echo json_encode(['success' => false, 'message' => '安全验证失败']);
		exit;
	}
	
	// IP 黑名单检查
	if (argon_is_ip_blocked_global()) {
		echo json_encode(['success' => false, 'message' => '您的 IP 已被限制访问']);
		exit;
	}
	
	// 频率限制（防止滥用）
	$user_identifier = argon_get_feedback_user_identifier();
	$rate_limit_key = 'feedback_upload_' . $user_identifier;
	$upload_count = get_transient($rate_limit_key);
	$max_uploads = intval(get_option('argon_feedback_upload_limit', 20));
	if ($upload_count !== false && $upload_count >= $max_uploads) {
		echo json_encode(['success' => false, 'message' => '上传过于频繁，请稍后再试']);
		exit;
	}
	
	$image_data = isset($_POST['image_data']) ? wp_unslash($_POST['image_data']) : '';
	if (empty($image_data)) {
		echo json_encode(['success' => false, 'message' => '图片数据为空']);
		exit;
	}
	
	$upload_error = '';
	$saved_url = argon_save_feedback_image($image_data, $upload_error);
	if ($saved_url) {
		// 更新频率限制计数
		$limit_period = intval(get_option('argon_feedback_upload_period', 3600));
		if ($upload_count === false) {
			set_transient($rate_limit_key, 1, $limit_period);
		} else {
			set_transient($rate_limit_key, $upload_count + 1, $limit_period);
		}
		
		$image_settings = argon_get_feedback_image_settings();
		echo json_encode([
			'success' => true,
			'url' => $saved_url,
			'max_count' => $image_settings['max_count'],
			'max_size' => $image_settings['max_bytes'],
		]);
	} else {
		echo json_encode(['success' => false, 'message' => $upload_error ?: '图片保存失败']);
	}
	exit;
}

// AJAX 处理反馈管理
if (isset($_POST['feedback_action'])) {
	header('Content-Type: application/json; charset=utf-8');
	$response = ['success' => false, 'message' => ''];
	$action = sanitize_text_field($_POST['feedback_action']);
	
	// 管理员操作
	if (in_array($action, ['delete', 'toggle_public', 'reply', 'update_status', 'get', 'reply_and_resolve'])) {
		if (!$is_admin) {
			echo json_encode(['success' => false, 'message' => '权限不足']);
			exit;
		}
		if (!isset($_POST['feedback_nonce']) || !wp_verify_nonce($_POST['feedback_nonce'], 'argon_feedback_manage')) {
			echo json_encode(['success' => false, 'message' => '安全验证失败']);
			exit;
		}
	}
	
	switch ($action) {
		case 'submit':
			$response = argon_handle_feedback_submit($_POST);
			break;
		case 'delete':
			$id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
			$response = ['success' => argon_delete_feedback($id)];
			break;
		case 'toggle_public':
			$id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
			$response = argon_toggle_feedback_public($id);
			break;
		case 'reply':
			$id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
			$reply = isset($_POST['reply']) ? sanitize_textarea_field($_POST['reply']) : '';
			$is_private = !empty($_POST['is_private']);
			$response = argon_reply_feedback($id, $reply, false, $is_private);
			break;
		case 'reply_and_resolve':
			$id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
			$reply = isset($_POST['reply']) ? sanitize_textarea_field($_POST['reply']) : '';
			$is_private = !empty($_POST['is_private']);
			$response = argon_reply_feedback($id, $reply, true, $is_private);
			break;
		case 'update_status':
			$id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
			$status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
			$response = argon_update_feedback_status($id, $status, false);
			break;
		case 'get':
			$id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
			$feedback = argon_get_feedback($id);
			if ($feedback) {
				// 如果是未查看状态，自动改为已查看
				if ($feedback['status'] === 'pending') {
					argon_update_feedback_status($id, 'viewed', false);
					$feedback['status'] = 'viewed';
				}
				// 确保 images 字段存在（兼容旧数据）
				if (!isset($feedback['images'])) {
					$feedback['images'] = [];
				}
				$response = ['success' => true, 'data' => $feedback];
			} else {
				$response = ['success' => false, 'message' => '反馈不存在'];
			}
			break;
	}
	echo json_encode($response);
	exit;
}

// ==================== 反馈数据操作函数 ====================

function argon_get_feedback_user_identifier() {
	if (is_user_logged_in()) {
		return 'user_' . get_current_user_id();
	}
	$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
	if (strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]);
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
	return substr(hash('sha256', $ip . '|' . $user_agent), 0, 16);
}

function argon_get_feedbacks($filter = 'all') {
	$feedbacks = get_option('argon_feedbacks', []);
	if (!is_array($feedbacks)) $feedbacks = [];
	usort($feedbacks, function($a, $b) { return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0); });
	if ($filter === 'public') {
		return array_values(array_filter($feedbacks, function($f) { return !empty($f['is_public']); }));
	}
	return $feedbacks;
}

function argon_get_feedback($id) {
	foreach (argon_get_feedbacks() as $f) {
		if ($f['id'] === $id) return $f;
	}
	return null;
}

function argon_get_feedback_stats() {
	$feedbacks = argon_get_feedbacks();
	$stats = ['total' => count($feedbacks), 'pending' => 0, 'viewed' => 0, 'processing' => 0, 'resolved' => 0, 'closed' => 0];
	foreach ($feedbacks as $f) {
		$status = $f['status'] ?? 'pending';
		if (isset($stats[$status])) $stats[$status]++;
	}
	return $stats;
}

function argon_add_feedback($data) {
	$feedbacks = get_option('argon_feedbacks', []);
	if (!is_array($feedbacks)) $feedbacks = [];
	$id = 'fb_' . uniqid();
	// 处理图片上传
	$images = [];
	if (!empty($data['feedback_images']) && is_array($data['feedback_images'])) {
		$image_settings = argon_get_feedback_image_settings();
		$upload_dir = wp_upload_dir();
		$feedback_url_prefix = $upload_dir['baseurl'] . '/feedback-images/';
		foreach ($data['feedback_images'] as $image_url) {
			if (!empty($image_url) && count($images) < $image_settings['max_count']) {
				// 验证是否是本站上传的图片 URL（已经通过 upload_image 接口上传）
				if (strpos($image_url, $feedback_url_prefix) === 0) {
					$images[] = esc_url_raw($image_url);
				}
			}
		}
	}
	
	$feedback = [
		'id' => $id,
		'title' => sanitize_text_field($data['title'] ?? ''),
		'content' => sanitize_textarea_field($data['content'] ?? ''),
		'type' => sanitize_text_field($data['type'] ?? 'suggestion'),
		'name' => sanitize_text_field($data['name'] ?? ''),
		'email' => sanitize_email($data['email'] ?? ''),
		'url' => esc_url_raw($data['url'] ?? ''),
		'is_public' => !empty($data['is_public']),
		'status' => 'pending',
		'user_identifier' => argon_get_feedback_user_identifier(),
		'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
		'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
		'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
		'created_at' => time(),
		'replies' => [],
		'images' => $images,
	];
	$feedbacks[] = $feedback;
	update_option('argon_feedbacks', $feedbacks);
	argon_send_feedback_new_notify($feedback);
	return $id;
}

function argon_delete_feedback($id) {
	$feedbacks = get_option('argon_feedbacks', []);
	if (!is_array($feedbacks)) return false;
	foreach ($feedbacks as $key => $f) {
		if ($f['id'] === $id) {
			unset($feedbacks[$key]);
			update_option('argon_feedbacks', array_values($feedbacks));
			return true;
		}
	}
	return false;
}

function argon_toggle_feedback_public($id) {
	$feedbacks = get_option('argon_feedbacks', []);
	if (!is_array($feedbacks)) return ['success' => false];
	foreach ($feedbacks as &$f) {
		if ($f['id'] === $id) {
			$f['is_public'] = !$f['is_public'];
			update_option('argon_feedbacks', $feedbacks);
			return ['success' => true, 'is_public' => $f['is_public']];
		}
	}
	return ['success' => false];
}

function argon_reply_feedback($id, $reply, $resolve = false, $is_private = false) {
	$feedbacks = get_option('argon_feedbacks', []);
	if (!is_array($feedbacks)) return ['success' => false];
	foreach ($feedbacks as &$f) {
		if ($f['id'] === $id) {
			// 检查是否已完结，已完结的反馈不允许修改状态，只能追加留言
			$is_archived = in_array($f['status'], ['resolved', 'closed']);
			
			// 初始化 replies 数组（兼容旧数据）
			if (!isset($f['replies'])) {
				$f['replies'] = [];
				// 迁移旧的 reply 字段
				if (!empty($f['reply'])) {
					$f['replies'][] = [
						'content' => $f['reply'],
						'time' => $f['reply_at'] ?? time(),
						'is_private' => false,
					];
				}
			}
			
			// 添加新留言
			if (!empty($reply)) {
				$new_reply = [
					'content' => $reply,
					'time' => time(),
					'is_private' => $is_private,
				];
				$f['replies'][] = $new_reply;
				
				// 只有未完结的反馈才能修改状态
				if (!$is_archived) {
					if ($resolve) {
						$f['status'] = 'resolved';
					} elseif ($f['status'] === 'pending' || $f['status'] === 'viewed') {
						$f['status'] = 'processing';
					}
				}
				
				update_option('argon_feedbacks', $feedbacks);
				
				// 发送邮件通知
				if ($resolve) {
					argon_send_feedback_resolved_notify($f);
				} elseif (!$is_private) {
					// 只有非私密留言才发送通知
					if ($is_archived) {
						// 已完结的反馈发送留言追加通知
						argon_send_feedback_reply_append_notify($f, $reply);
					} else {
						// 未完结的反馈发送普通回复通知
						argon_send_feedback_reply_notify($f, $reply);
					}
				}
			} else {
				// 没有留言内容，只修改状态
				if (!$is_archived && $resolve) {
					$f['status'] = 'resolved';
					update_option('argon_feedbacks', $feedbacks);
					argon_send_feedback_resolved_notify($f);
				}
			}
			
			return ['success' => true, 'is_archived' => $is_archived];
		}
	}
	return ['success' => false];
}

function argon_update_feedback_status($id, $status, $send_notify = true) {
	$valid_statuses = ['pending', 'viewed', 'processing', 'resolved', 'closed'];
	if (!in_array($status, $valid_statuses)) return ['success' => false, 'message' => '无效状态'];
	$feedbacks = get_option('argon_feedbacks', []);
	if (!is_array($feedbacks)) return ['success' => false];
	foreach ($feedbacks as &$f) {
		if ($f['id'] === $id) {
			// 检查是否已完结
			$is_archived = in_array($f['status'], ['resolved', 'closed']);
			if ($is_archived) {
				return ['success' => false, 'message' => '已完结的反馈不允许修改状态'];
			}
			
			$old_status = $f['status'];
			$f['status'] = $status;
			update_option('argon_feedbacks', $feedbacks);
			
			// 只有明确要求发送通知且状态变为 resolved 时才发送
			if ($send_notify && $status === 'resolved' && $old_status !== 'resolved') {
				argon_send_feedback_resolved_notify($f);
			}
			return ['success' => true];
		}
	}
	return ['success' => false];
}

function argon_is_feedback_captcha_enabled() {
	$mode = get_option('argon_feedback_captcha_mode', 'global');
	if ($mode === 'enabled') return true;
	if ($mode === 'disabled') return false;
	return function_exists('argon_is_captcha_enabled') && argon_is_captcha_enabled();
}

function argon_get_feedback_image_settings() {
	$settings = [
		'max_count' => intval(get_option('argon_feedback_image_max_count', 10)),
		'max_bytes' => intval(get_option('argon_feedback_image_max_size_kb', 250)) * 1024,
		'max_source_bytes' => intval(get_option('argon_feedback_image_source_max_mb', 12)) * 1024 * 1024,
		'max_dimension' => intval(get_option('argon_feedback_image_max_dimension', 2048)),
		'allowed_mimes' => [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
		],
	];
	$settings['max_count'] = max(1, min(10, $settings['max_count']));
	$settings['max_bytes'] = max(80 * 1024, min(2 * 1024 * 1024, $settings['max_bytes']));
	$settings['max_source_bytes'] = max(1024 * 1024, min(32 * 1024 * 1024, $settings['max_source_bytes']));
	$settings['max_dimension'] = max(640, min(4096, $settings['max_dimension']));
	return apply_filters('argon_feedback_image_settings', $settings);
}

function argon_save_feedback_image($base64_data, &$error_message = '') {
	$image_settings = argon_get_feedback_image_settings();
	$base64_data = trim((string)$base64_data);

	if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/i', $base64_data, $matches)) {
		$error_message = __('图片格式不支持', 'argon');
		return false;
	}
	
	$data_parts = explode(',', $base64_data);
	if (count($data_parts) !== 2) {
		$error_message = __('图片数据格式错误', 'argon');
		return false;
	}
	
	$image_data = base64_decode($data_parts[1], true);
	if ($image_data === false) {
		$error_message = __('图片数据解析失败', 'argon');
		return false;
	}
	
	if (strlen($image_data) > $image_settings['max_bytes']) {
		$error_message = sprintf(__('图片不能超过 %s', 'argon'), size_format($image_settings['max_bytes']));
		return false;
	}

	$image_info = function_exists('getimagesizefromstring') ? @getimagesizefromstring($image_data) : false;
	if ($image_info === false || empty($image_info['mime']) || !isset($image_settings['allowed_mimes'][$image_info['mime']])) {
		$error_message = __('图片文件校验失败', 'argon');
		return false;
	}
	if (($image_info[0] ?? 0) > $image_settings['max_dimension'] || ($image_info[1] ?? 0) > $image_settings['max_dimension']) {
		$error_message = sprintf(__('图片尺寸不能超过 %1$dx%1$d', 'argon'), $image_settings['max_dimension']);
		return false;
	}
	
	$upload_dir = wp_upload_dir();
	$feedback_dir = $upload_dir['basedir'] . '/feedback-images';
	if (!file_exists($feedback_dir)) {
		wp_mkdir_p($feedback_dir);
	}
	
	$ext = $image_settings['allowed_mimes'][$image_info['mime']];
	$uuid = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('', true);
	$filename = wp_unique_filename($feedback_dir, 'fb_' . $uuid . '.' . $ext);
	$filepath = $feedback_dir . '/' . $filename;
	
	if (file_put_contents($filepath, $image_data) === false) {
		$error_message = __('图片保存失败', 'argon');
		return false;
	}
	@chmod($filepath, 0644);
	
	return $upload_dir['baseurl'] . '/feedback-images/' . $filename;
}

function argon_handle_feedback_submit($data) {
	// IP 黑名单检查
	if (argon_is_ip_blocked_global()) {
		return ['success' => false, 'message' => __('您的 IP 已被限制访问', 'argon')];
	}
	
	// 提交频率限制
	$user_identifier = argon_get_feedback_user_identifier();
	$submit_limit_key = 'feedback_submit_' . $user_identifier;
	$submit_count = get_transient($submit_limit_key);
	$max_submits = intval(get_option('argon_feedback_submit_limit', 5));
	$limit_period = intval(get_option('argon_feedback_submit_period', 3600));
	if ($submit_count !== false && $submit_count >= $max_submits) {
		return ['success' => false, 'message' => __('提交过于频繁，请稍后再试', 'argon')];
	}

	if (empty($data['feedback_submit_nonce']) || !wp_verify_nonce($data['feedback_submit_nonce'], 'argon_feedback_submit')) {
		return ['success' => false, 'message' => __('安全验证失败，请刷新页面后重试', 'argon')];
	}
	if (!empty($data['feedback_homepage'])) {
		return ['success' => false, 'message' => __('提交失败，请稍后重试', 'argon')];
	}

	$data['feedback_name'] = trim((string)($data['feedback_name'] ?? ''));
	$data['feedback_email'] = trim((string)($data['feedback_email'] ?? ''));
	$data['feedback_title'] = trim((string)($data['feedback_title'] ?? ''));
	$data['feedback_content'] = trim((string)($data['feedback_content'] ?? ''));
	$valid_types = ['bug', 'suggestion', 'question', 'other'];
	if (!in_array(($data['feedback_type'] ?? 'suggestion'), $valid_types, true)) {
		$data['feedback_type'] = 'other';
	}

	if (empty($data['feedback_email'])) return ['success' => false, 'message' => __('请填写邮箱', 'argon')];
	if (empty($data['feedback_name'])) return ['success' => false, 'message' => __('请填写昵称', 'argon')];
	if (empty($data['feedback_content'])) return ['success' => false, 'message' => __('请填写反馈内容', 'argon')];
	if (!is_email($data['feedback_email'])) return ['success' => false, 'message' => __('邮箱格式错误', 'argon')];
	
	// 验证码检查
	if (argon_is_feedback_captcha_enabled()) {
		$captcha_type = get_option('argon_captcha_type', 'math');
		if ($captcha_type === 'geetest') {
			if (function_exists('geetest_validate')) {
				$lot_number = $data['geetest_lot_number'] ?? '';
				$captcha_output = $data['geetest_captcha_output'] ?? '';
				$pass_token = $data['geetest_pass_token'] ?? '';
				$gen_time = $data['geetest_gen_time'] ?? '';
				if (empty($lot_number) || empty($captcha_output)) {
					return ['success' => false, 'message' => __('请完成验证码验证', 'argon')];
				}
				$result = geetest_validate($lot_number, $captcha_output, $pass_token, $gen_time);
				if ($result !== true) return ['success' => false, 'message' => __('验证码验证失败', 'argon')];
			}
		} else {
			$captcha_input = $data['feedback_captcha'] ?? '';
			if (empty($captcha_input)) {
				return ['success' => false, 'message' => __('请输入验证码', 'argon')];
			}
			if (function_exists('get_comment_captcha_answer')) {
				$correct_answer = get_comment_captcha_answer();
				if ($captcha_input != $correct_answer) {
					if (function_exists('get_comment_captcha_seed')) get_comment_captcha_seed(true);
					return ['success' => false, 'message' => __('验证码错误', 'argon')];
				}
				if (function_exists('get_comment_captcha_seed')) get_comment_captcha_seed(true);
			}
		}
	}
	
	$id = argon_add_feedback([
		'title' => $data['feedback_title'] ?? '',
		'content' => $data['feedback_content'],
		'type' => $data['feedback_type'] ?? 'suggestion',
		'name' => $data['feedback_name'],
		'email' => $data['feedback_email'],
		'url' => $data['feedback_url'] ?? '',
		'is_public' => !empty($data['feedback_public']),
		'feedback_images' => $data['feedback_images'] ?? [],
	]);
	
	if ($id) {
		// 更新提交频率计数
		if ($submit_count === false) {
			set_transient($submit_limit_key, 1, $limit_period);
		} else {
			set_transient($submit_limit_key, $submit_count + 1, $limit_period);
		}
		return [
			'success' => true,
			'message' => __('反馈提交成功，感谢您的建议！', 'argon'),
			'id' => $id,
			'view_url' => function_exists('argon_get_feedback_auth_url') ? argon_get_feedback_auth_url($id) : '',
			'is_public' => !empty($data['feedback_public']),
		];
	}
	return ['success' => false, 'message' => __('提交失败，请稍后重试', 'argon')];
}

// ==================== 页面渲染 ====================
$all_feedbacks = argon_get_feedbacks();
$public_feedbacks = argon_get_feedbacks('public');
$stats = argon_get_feedback_stats();
$captcha_enabled = argon_is_feedback_captcha_enabled();
$captcha_type = get_option('argon_captcha_type', 'math');
$feedback_image_settings = argon_get_feedback_image_settings();

$saved_name = $_COOKIE['comment_author_' . COOKIEHASH] ?? '';
$saved_email = $_COOKIE['comment_author_email_' . COOKIEHASH] ?? '';
$saved_url = $_COOKIE['comment_author_url_' . COOKIEHASH] ?? '';

if (is_user_logged_in()) {
	$current_user = wp_get_current_user();
	$saved_name = $current_user->display_name;
	$saved_email = $current_user->user_email;
	$saved_url = $current_user->user_url;
}

$single_feedback = null;
if ($is_auth_view && $auth_view_id) {
	$single_feedback = argon_get_feedback($auth_view_id);
}

require_once get_template_directory() . '/inc/special-page-surface.php';
get_header();

$type_labels = ['bug' => __('Bug', 'argon'), 'suggestion' => __('建议', 'argon'), 'question' => __('问题', 'argon'), 'other' => __('其他', 'argon')];
$status_labels = ['pending' => __('未查看', 'argon'), 'viewed' => __('已查看', 'argon'), 'processing' => __('进行中', 'argon'), 'resolved' => __('已完成', 'argon'), 'closed' => __('已关闭', 'argon')];
argon_special_page_surface_open('feedback-page');
?>

<style id="feedback-page-style">
/* 管理员统计 */
.feedback-admin-stats { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.feedback-admin-stat { flex: 1; min-width: 120px; padding: 20px; background-color: rgba(255, 255, 255, var(--card-opacity)); backdrop-filter: blur(var(--card-blur)) saturate(var(--card-saturate)); -webkit-backdrop-filter: blur(var(--card-blur)) saturate(var(--card-saturate)); border-radius: var(--card-radius); text-align: center; border: 1px solid var(--color-border); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-admin-stat:hover { transform: translateY(-2px); border-color: rgba(var(--themecolor-rgbstr), 0.3); box-shadow: 0 4px 12px rgba(var(--themecolor-rgbstr), 0.15); }
.feedback-admin-stat .stat-value { font-size: 32px; font-weight: 600; color: var(--themecolor); line-height: 1.2; }
.feedback-admin-stat .stat-label { font-size: 13px; color: #888; margin-top: 6px; }
.feedback-admin-stat.pending { border-color: rgba(217, 119, 6, 0.3); }
.feedback-admin-stat.pending .stat-value { color: #d97706; }
.feedback-admin-stat.resolved { border-color: rgba(5, 150, 105, 0.3); }
.feedback-admin-stat.resolved .stat-value { color: #059669; }
html.darkmode .feedback-admin-stat { background-color: rgba(66, 66, 66, var(--card-opacity)); }
html.darkmode .feedback-admin-stat .stat-label { color: #aaa; }

/* 表单样式 */
.feedback-form-card { padding: 24px 28px; }
.feedback-card-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
.feedback-card-heading .feedback-form-title,
.feedback-card-heading .feedback-list-title { margin-bottom: 0; }
.feedback-kicker { margin: 0 0 4px; font-size: 12px; font-weight: 700; color: var(--themecolor); text-transform: uppercase; }
.feedback-draft-state { display: none; align-items: center; gap: 6px; color: #059669; background: #d1fae5; border: 1px solid rgba(5, 150, 105, 0.16); border-radius: var(--card-radius-sm); padding: 6px 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
.feedback-draft-state.show { display: inline-flex; }
.feedback-form-intro { margin: -6px 0 20px; padding: 12px 14px; border-radius: var(--card-radius-sm); background: rgba(var(--themecolor-rgbstr), 0.06); color: #555; font-size: 13px; line-height: 1.65; }
.feedback-form-title { font-size: 17px; font-weight: 600; margin: 0 0 20px; color: var(--color-text-deeper); display: flex; align-items: center; gap: 10px; }
.feedback-form-title i { color: var(--themecolor); font-size: 18px; }
.feedback-form .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px; }
.feedback-form .form-row.full { grid-template-columns: 1fr; }
.feedback-form .form-group { margin-bottom: 0; }
.feedback-form label { font-size: 14px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 6px; display: block; }
.feedback-form label .required { color: #f5365c; margin-left: 2px; }
.feedback-field-hint { color: #666; font-size: 12px; font-weight: 400; }
.feedback-form input, .feedback-form textarea, .feedback-form select {
	width: 100%; padding: 12px 14px; font-size: 14px;
	border: 1.5px solid var(--color-border); border-radius: var(--card-radius-sm);
	background: var(--color-widgets); color: var(--color-text-deeper);
	font-family: var(--font); box-sizing: border-box; 
	transition: all var(--animation-fast) var(--ease-standard);
}
.feedback-form input:focus, .feedback-form textarea:focus, .feedback-form select:focus {
	border-color: var(--themecolor); 
	box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.1), 0 2px 8px rgba(var(--themecolor-rgbstr), 0.15); 
	outline: none;
	transform: translateY(-1px);
}
.feedback-form textarea { resize: vertical; min-height: 140px; line-height: 1.6; }
.feedback-form .char-count { font-size: 12px; color: #666; float: right; margin-top: 4px; }
.feedback-form .char-count.warning { color: #d97706; }
.feedback-form .char-count.danger { color: #dc2626; }
.feedback-honeypot { position: absolute; left: -9999px; top: auto; width: 1px; height: 1px; overflow: hidden; }
.feedback-type-selector { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.feedback-type-option { position: relative; margin: 0; display: flex !important; align-items: center; gap: 8px; padding: 11px 12px; border: 1.5px solid var(--color-border); border-radius: var(--card-radius-sm); background: var(--color-widgets); cursor: pointer; transition: all var(--animation-fast) var(--ease-standard); min-height: 46px; }
.feedback-type-option input { position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px; }
.feedback-type-option i { color: #777; width: 18px; text-align: center; transition: color var(--animation-fast) var(--ease-standard); }
.feedback-type-option span { font-size: 13px; font-weight: 700; color: var(--color-text-deeper); }
.feedback-type-option:hover { border-color: rgba(var(--themecolor-rgbstr), 0.42); background: rgba(var(--themecolor-rgbstr), 0.04); }
.feedback-type-option:has(input:checked) { border-color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.1); box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.08); }
.feedback-type-option:has(input:checked) i { color: var(--themecolor); }
.feedback-type-option.is-active { border-color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.1); box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.08); }
.feedback-type-option.is-active i { color: var(--themecolor); }
.feedback-public-option { display: flex; align-items: center; gap: 10px; margin: 20px 0; padding: 14px 18px; background: rgba(var(--themecolor-rgbstr), 0.06); border-radius: var(--card-radius-sm); border: 1.5px solid rgba(var(--themecolor-rgbstr), 0.12); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-public-option:hover { background: rgba(var(--themecolor-rgbstr), 0.08); border-color: rgba(var(--themecolor-rgbstr), 0.18); }
.feedback-public-option input[type="checkbox"] { width: 20px; height: 20px; accent-color: var(--themecolor); cursor: pointer; }
.feedback-public-option label { font-size: 14px; color: var(--color-text-deeper); cursor: pointer; margin: 0; font-weight: 500; }
.feedback-public-option .hint { font-size: 12px; color: #666; margin-left: auto; }
.feedback-image-upload { display: grid; gap: 12px; padding: 12px; border: 1.5px dashed var(--color-border); border-radius: var(--card-radius-sm); background: rgba(var(--themecolor-rgbstr), 0.025); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-image-upload.dragover { border-color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.08); }
.feedback-image-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 30px; }
.feedback-image-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: var(--card-radius-sm); background: rgba(var(--themecolor-rgbstr), 0.08); color: var(--themecolor); font-size: 12px; font-weight: 700; }
.feedback-image-clear-btn { display: none; padding: 5px 10px !important; font-size: 12px !important; border-radius: var(--card-radius-sm) !important; }
.feedback-image-clear-btn.show { display: inline-flex; align-items: center; gap: 5px; }
.feedback-image-preview { display: flex; flex-wrap: wrap; gap: 12px; min-height: 100px; }
.feedback-image-item { position: relative; width: 100px; height: 100px; border-radius: var(--card-radius-sm); overflow: hidden; border: 2px solid var(--color-border); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-image-item:hover { border-color: var(--themecolor); transform: scale(1.05); }
.feedback-image-item img { width: 100%; height: 100%; object-fit: cover; }
.feedback-image-item .remove-btn { position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; background: rgba(245,54,92,0.9); color: #fff; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: all var(--animation-fast) var(--ease-standard); }
.feedback-image-item .remove-btn:hover { background: #f5365c; transform: scale(1.1); }
.feedback-image-item .retry-btn { position: absolute; left: 6px; right: 6px; bottom: 6px; height: 26px; padding: 0; border: none; border-radius: var(--card-radius-sm); background: rgba(var(--themecolor-rgbstr), 0.92); color: #fff; font-size: 12px; font-weight: 700; cursor: pointer; }
.feedback-image-item .image-state { position: absolute; left: 6px; top: 6px; max-width: calc(100% - 38px); padding: 3px 7px; border-radius: 12px; background: rgba(0, 0, 0, 0.58); color: #fff; font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.feedback-image-item .image-progress { position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: rgba(0, 0, 0, 0.24); }
.feedback-image-item .image-progress span { display: block; height: 100%; width: 0%; background: var(--themecolor); transition: width 0.2s ease; }
.feedback-image-item.status-error { border-color: rgba(245, 54, 92, 0.7); }
.feedback-image-item.status-error img { filter: grayscale(0.7) brightness(0.68); }
.feedback-image-item.status-ready { border-color: rgba(var(--themecolor-rgbstr), 0.45); }
.feedback-image-item.status-done { border-color: rgba(5, 150, 105, 0.65); }
.feedback-image-add-btn { width: 100px; height: 100px; border: 2px dashed var(--color-border); border-radius: var(--card-radius-sm); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all var(--animation-fast) var(--ease-standard); color: #666; font-size: 13px; }
.feedback-image-add-btn:hover { border-color: var(--themecolor); color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.05); }
.feedback-image-add-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
.feedback-image-add-btn i { font-size: 24px; margin-bottom: 6px; }
.feedback-image-item.uploading { background: var(--color-border-on-foreground); display: flex; align-items: center; justify-content: center; }
.uploading-spinner { font-size: 24px; color: var(--themecolor); }
.feedback-images-display { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
.feedback-images-display img { width: 112px; height: 112px; object-fit: cover; border-radius: var(--card-radius-sm); cursor: zoom-in; transition: transform var(--animation-fast) var(--ease-standard), border-color var(--animation-fast) var(--ease-standard), box-shadow var(--animation-fast) var(--ease-standard); border: 2px solid var(--color-border); }
.feedback-images-display img:hover { transform: translateY(-2px); border-color: var(--themecolor); box-shadow: 0 8px 18px rgba(var(--themecolor-rgbstr), 0.16); }
.feedback-images-display img:focus-visible { outline: 2px solid rgba(var(--themecolor-rgbstr), .48); outline-offset: 3px; }
.feedback-captcha { margin-top: 16px; }
.feedback-captcha .input-group { max-width: 300px; }
.feedback-captcha-text { background: var(--color-background); font-family: monospace; font-size: 15px; letter-spacing: 3px; cursor: pointer; user-select: none; padding: 10px 18px; border-radius: 0 var(--card-radius-sm) var(--card-radius-sm) 0; font-weight: 600; transition: all var(--animation-fast) var(--ease-standard); }
.feedback-captcha-text:hover { background: var(--color-border-on-foreground); }
.feedback-submit { margin-top: 20px; text-align: center; }
.feedback-submit .btn { padding: 12px 40px; font-size: 15px; font-weight: 600; border-radius: var(--card-radius-sm); }
.feedback-submit-actions { display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; }
.feedback-submit .btn:disabled { cursor: not-allowed; opacity: 0.72; }
.feedback-submit-result { margin-top: 18px; padding: 14px 16px; border: 1.5px solid rgba(5, 150, 105, 0.24); background: #d1fae5; color: #065f46; border-radius: var(--card-radius-sm); text-align: left; }
.feedback-submit-result[hidden] { display: none !important; }
.feedback-submit-result-title { display: flex; align-items: center; gap: 8px; font-weight: 700; margin-bottom: 6px; }
.feedback-submit-result p { margin: 0 0 10px; font-size: 13px; line-height: 1.6; }
.feedback-submit-result-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.feedback-submit-result-actions .btn { padding: 8px 12px; font-size: 12px; }
.feedback-divider { height: 1px; background: linear-gradient(to right, transparent, var(--color-border), transparent); margin: 32px 0; }

/* 反馈列表 */
.feedback-list-title { font-size: 17px; font-weight: 600; margin: 0 0 20px; color: var(--color-text-deeper); display: flex; align-items: center; gap: 10px; }
.feedback-list-title i { color: var(--themecolor); font-size: 18px; }
.feedback-list-title .count { margin-left: auto; font-size: 13px; font-weight: 600; color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.1); padding: 4px 14px; border-radius: 14px; border: 1px solid rgba(var(--themecolor-rgbstr), 0.2); }
.feedback-list-tools { display: grid; grid-template-columns: minmax(240px, 1fr) repeat(2, minmax(145px, 180px)) auto; grid-auto-rows: 42px; gap: 8px; margin-bottom: 12px; padding: 0; align-items: stretch; background: transparent; border: 0; border-radius: 0; }
.feedback-list-tools > * { min-width: 0; }
.feedback-search-box,
.feedback-filter-control { position: relative; display: flex; align-items: center; width: 100%; height: 100%; min-width: 0; margin: 0; }
.feedback-search-box > i,
.feedback-filter-control > i:first-child { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--color-text-deeper); opacity: .55; font-size: 13px; pointer-events: none; z-index: 1; }
.feedback-filter-control .feedback-select-arrow { position: absolute; right: 12px; left: auto; color: var(--color-text-deeper); opacity: .55; font-size: 11px; pointer-events: none; }
.feedback-search-box input,
.feedback-list-tools select { width: 100%; height: 100%; border: 1px solid var(--color-border-on-foreground-deeper); border-radius: var(--card-radius-sm); background: var(--color-widgets); color: var(--color-text-deeper); font-family: var(--font); font-size: 13px; line-height: 1.2; box-sizing: border-box; transition: border-color var(--animation-fast) var(--ease-standard), box-shadow var(--animation-fast) var(--ease-standard), background-color var(--animation-fast) var(--ease-standard); }
.feedback-search-box input::placeholder { color: var(--color-text-deeper); opacity: .55; }
.feedback-search-box input { padding: 0 12px 0 36px; }
.feedback-list-tools select { padding: 0 34px 0 34px; appearance: none; -webkit-appearance: none; cursor: pointer; }
.feedback-search-box input:focus,
.feedback-list-tools select:focus { border-color: rgba(var(--themecolor-rgbstr), .55); outline: none; box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.09); background: var(--color-widgets); }
.feedback-list-clear { width: auto; height: 100%; min-width: 82px; margin: 0; padding: 0 14px !important; border: 1px solid var(--color-border-on-foreground-deeper) !important; border-radius: var(--card-radius-sm) !important; display: inline-flex; align-items: center; justify-content: center; gap: 7px; box-sizing: border-box; box-shadow: none !important; background: var(--color-widgets) !important; color: var(--color-text-deeper) !important; }
.feedback-list-clear:hover { border-color: rgba(var(--themecolor-rgbstr), .38) !important; background: var(--color-widgets-disabled) !important; color: var(--themecolor) !important; }
.feedback-list-clear:focus { border-color: rgba(var(--themecolor-rgbstr), .55) !important; outline: none; box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.09) !important; }
.feedback-list-summary { min-height: 20px; margin: 0 0 8px; color: #666; font-size: 12px; }
.feedback-item { padding: 20px 0; background: transparent; border-radius: 0; margin-bottom: 0; position: relative; cursor: default; transition: all var(--animation-fast) var(--ease-standard); border: none; border-bottom: 1px solid var(--color-border-on-foreground); }
.feedback-item:last-child { margin-bottom: 0; border-bottom: none; }
.feedback-item.clickable { cursor: pointer; }
.feedback-item.clickable:hover { 
	background: rgba(var(--themecolor-rgbstr), 0.03);
	padding-left: 12px;
	padding-right: 12px;
	margin-left: -12px;
	margin-right: -12px;
	border-radius: var(--card-radius-sm);
	border-bottom-color: transparent;
}
.feedback-item-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 12px; }
.feedback-item-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--themecolor-gradient); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 600; color: #fff; flex-shrink: 0; overflow: hidden; box-shadow: 0 2px 8px rgba(var(--themecolor-rgbstr), 0.2); transition: transform var(--animation-fast) var(--ease-standard); }
.feedback-item.clickable:hover .feedback-item-avatar { transform: scale(1.05); }
.feedback-item-avatar img { width: 100%; height: 100%; object-fit: cover; }
.feedback-item-meta { flex: 1; min-width: 0; }
.feedback-item-name { font-size: 15px; font-weight: 600; color: var(--color-text-deeper); display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.feedback-item-time { font-size: 13px; color: #666; }
.feedback-item-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.feedback-badge { font-size: 12px; padding: 3px 10px; border-radius: 12px; font-weight: 600; transition: all var(--animation-fast) var(--ease-standard); }
.feedback-badge.type-bug { background: #fee2e2; color: #dc2626; }
.feedback-badge.type-suggestion { background: #dbeafe; color: #2563eb; }
.feedback-badge.type-question { background: #fef3c7; color: #d97706; }
.feedback-badge.type-other { background: #e5e7eb; color: #6b7280; }
.feedback-badge.status-pending { background: #fef3c7; color: #d97706; }
.feedback-badge.status-viewed { background: #e0e7ff; color: #6366f1; }
.feedback-badge.status-processing { background: #dbeafe; color: #2563eb; }
.feedback-badge.status-resolved { background: #d1fae5; color: #059669; }
.feedback-badge.status-closed { background: #e5e7eb; color: #6b7280; }
.feedback-item-title { font-size: 16px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 10px; line-height: 1.5; }
.feedback-item-content { font-size: 14px; color: #555; line-height: 1.7; white-space: pre-wrap; word-break: break-word; }
.feedback-item-footer { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; color: #777; font-size: 12px; }
.feedback-item-footer span { display: inline-flex; align-items: center; gap: 5px; }
.feedback-item-reply { margin-top: 16px; padding: 14px 16px; background: rgba(var(--themecolor-rgbstr), 0.06); border-radius: var(--card-radius-sm); border-left: 3px solid var(--themecolor); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-item-reply:hover { background: rgba(var(--themecolor-rgbstr), 0.08); }
.feedback-item-reply-header { font-size: 13px; font-weight: 600; color: var(--themecolor); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
.feedback-item-reply-content { font-size: 14px; color: #555; line-height: 1.7; white-space: pre-wrap; }
.feedback-empty-filter { display: none; padding: 36px 16px; text-align: center; color: #666; border: 1px dashed var(--color-border); border-radius: var(--card-radius-sm); margin-top: 12px; }
.feedback-empty-filter.show { display: block; }
.feedback-empty-filter i { display: block; margin-bottom: 10px; font-size: 28px; color: var(--themecolor); opacity: 0.72; }
.feedback-admin-card { padding: 20px 24px; }
.feedback-admin-title { font-size: 16px; font-weight: 600; color: var(--themecolor); margin: 0 0 16px; display: flex; align-items: center; gap: 10px; }

/* 弹窗样式 */
.feedback-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 9998; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity var(--motion-layer-duration) var(--motion-ease-exit), visibility 0s linear var(--motion-layer-duration); }
.feedback-modal-overlay.show { opacity: 1; visibility: visible; pointer-events: auto; transition-timing-function: var(--motion-ease-enter), linear; transition-delay: 0s; }
.feedback-modal { background: var(--color-foreground); border-radius: var(--card-radius); padding: 0; width: 90%; max-width: 600px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.3), 0 8px 24px rgba(0,0,0,0.2); opacity: 0; transform: translateY(var(--motion-popup-offset)) scale(var(--motion-popup-scale)); transition: opacity var(--motion-layer-duration) var(--motion-ease-exit), transform var(--motion-layer-duration) var(--motion-ease-exit); }
.feedback-modal-overlay.show .feedback-modal { opacity: 1; transform: translateY(0) scale(1); transition-timing-function: var(--motion-ease-enter); }
.feedback-modal-header { padding: 20px 24px; border-bottom: 1.5px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; background: linear-gradient(to bottom, var(--color-foreground), rgba(var(--themecolor-rgbstr), 0.02)); }
.feedback-modal-title { font-size: 18px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 10px; color: var(--color-text-deeper); }
.feedback-modal-title i { color: var(--themecolor); }
.feedback-modal-close { background: none; border: none; font-size: 22px; cursor: pointer; color: #666; padding: 0; line-height: 1; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all var(--animation-fast) var(--ease-standard); }
.feedback-modal-close:hover { background: rgba(0,0,0,0.05); color: var(--color-text-deeper); transform: rotate(90deg); }
.feedback-modal-body { padding: 24px; overflow-y: auto; flex: 1; }
.feedback-modal-footer { padding: 16px 24px; border-top: 1.5px solid var(--color-border); display: flex; gap: 10px; justify-content: flex-end; background: rgba(var(--themecolor-rgbstr), 0.02); }
.feedback-modal-footer .btn { min-width: 90px; }

/* 详情弹窗内容 */
.feedback-detail-section { margin-bottom: 16px; }
.feedback-detail-section:last-child { margin-bottom: 0; }
.feedback-detail-label { font-size: 12px; font-weight: 600; color: #666; margin-bottom: 4px; }
.feedback-detail-value { font-size: 14px; color: var(--color-text-deeper); line-height: 1.6; }
.feedback-detail-value.content { white-space: pre-wrap; word-break: break-word; padding: 12px; background: var(--color-background); border-radius: calc(var(--card-radius) * 0.6); }
.feedback-detail-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 12px; background: var(--color-background); border-radius: calc(var(--card-radius) * 0.6); }
.feedback-detail-meta-item { font-size: 12px; }
.feedback-detail-meta-item .label { color: #666; }
.feedback-detail-meta-item .value { color: var(--color-text-deeper); word-break: break-all; }
.feedback-reply-input { width: 100%; padding: 10px 12px; font-size: 14px; border: 1px solid var(--color-border); border-radius: calc(var(--card-radius) * 0.6); min-height: 80px; resize: vertical; box-sizing: border-box; background: var(--color-widgets); color: var(--color-text-deeper); font-family: var(--font); }
.feedback-reply-input:focus { border-color: var(--themecolor); box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.1); outline: none; }

/* 授权链接查看 */
.feedback-auth-view { padding: 20px 24px; }
.feedback-auth-notice { padding: 12px 16px; background: rgba(var(--themecolor-rgbstr), 0.1); border-radius: var(--card-radius); margin-bottom: 16px; font-size: 13px; color: var(--themecolor); display: flex; align-items: center; gap: 8px; }

/* 本页图片预览 */
body.feedback-viewer-open { overflow: hidden !important; }
.feedback-image-viewer { --feedback-viewer-duration: var(--motion-zoom-duration, 360ms); --feedback-viewer-easing: var(--motion-ease-enter, cubic-bezier(.16, 1, .3, 1)); --feedback-viewer-glass: rgba(18, 23, 36, .52); --feedback-viewer-glass-hover: rgba(31, 38, 55, .68); position: fixed; inset: 0; z-index: 10050; display: block; padding: 0; background: rgba(8, 11, 18, .88); opacity: 0; visibility: hidden; pointer-events: none; transition: opacity var(--feedback-viewer-duration) var(--feedback-viewer-easing), visibility var(--feedback-viewer-duration) var(--feedback-viewer-easing); }
.feedback-image-viewer.is-open { opacity: 1; visibility: visible; pointer-events: auto; }
.feedback-image-viewer.is-closing { opacity: 0; pointer-events: none; }
.feedback-viewer-topbar { position: absolute; top: max(16px, env(safe-area-inset-top)); left: 16px; right: 16px; z-index: 4; margin: 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; color: #fff; pointer-events: none; transform: translateY(-10px); transition: transform var(--feedback-viewer-duration) var(--feedback-viewer-easing); }
.feedback-image-viewer.is-open .feedback-viewer-topbar { transform: translateY(0); }
.feedback-image-viewer.is-closing .feedback-viewer-topbar { transform: translateY(-10px); }
.feedback-viewer-title { min-width: 0; max-width: min(560px, calc(100vw - 88px)); min-height: 42px; padding: 8px 14px; display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; background: var(--feedback-viewer-glass); border: 1px solid rgba(255, 255, 255, .14); border-radius: var(--card-radius); box-shadow: 0 10px 28px rgba(0, 0, 0, .18); -webkit-backdrop-filter: blur(18px) saturate(150%); backdrop-filter: blur(18px) saturate(150%); pointer-events: auto; }
.feedback-viewer-title i { color: rgba(255, 255, 255, .9); }
.feedback-viewer-details { min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.feedback-viewer-name { overflow: hidden; color: #fff; line-height: 1.35; text-overflow: ellipsis; white-space: nowrap; }
.feedback-viewer-meta { display: flex; align-items: center; gap: 6px; color: rgba(255, 255, 255, .66); font-size: 11px; font-weight: 500; line-height: 1.35; }
.feedback-viewer-meta-separator { opacity: .55; }
.feedback-viewer-close,
.feedback-viewer-nav,
.feedback-viewer-control { width: 42px; height: 42px; padding: 0; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 42px; border: 1px solid rgba(255, 255, 255, .14); border-radius: 999px; color: #fff; background: var(--feedback-viewer-glass); -webkit-backdrop-filter: blur(18px) saturate(150%); backdrop-filter: blur(18px) saturate(150%); cursor: pointer; pointer-events: auto; transition: color 120ms ease, border-color 120ms ease, background-color 120ms ease, transform 120ms ease; }
.feedback-viewer-close:hover,
.feedback-viewer-nav:hover,
.feedback-viewer-control:hover { color: #fff; border-color: rgba(255, 255, 255, .28); background: var(--feedback-viewer-glass-hover); }
.feedback-viewer-close:active,
.feedback-viewer-nav:active,
.feedback-viewer-control:active { transform: scale(.94); }
.feedback-viewer-close:focus-visible,
.feedback-viewer-nav:focus-visible,
.feedback-viewer-control:focus-visible { outline: 2px solid rgba(var(--themecolor-rgbstr), .48); outline-offset: 2px; }
.feedback-viewer-stage { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; overflow: hidden; touch-action: none; }
.feedback-viewer-image { --feedback-viewer-x: 0px; --feedback-viewer-y: 0px; --feedback-viewer-scale: 1; display: block; max-width: 100vw; max-height: 100vh; object-fit: contain; border: 0; border-radius: 0; box-shadow: none; opacity: 0; transform: translate3d(var(--feedback-viewer-x), var(--feedback-viewer-y), 0) scale(var(--feedback-viewer-scale)) scale(.985); cursor: zoom-in; user-select: none; -webkit-user-drag: none; will-change: transform, opacity; transition: opacity var(--feedback-viewer-duration) var(--feedback-viewer-easing), transform var(--feedback-viewer-duration) var(--feedback-viewer-easing); }
.feedback-viewer-image.is-ready { opacity: 1; transform: translate3d(var(--feedback-viewer-x), var(--feedback-viewer-y), 0) scale(var(--feedback-viewer-scale)); }
.feedback-viewer-image.is-zoomed { cursor: grab; }
.feedback-viewer-image.is-dragging { cursor: grabbing; transition: none; }
.feedback-image-viewer.is-closing .feedback-viewer-image { transform: translate3d(var(--feedback-viewer-x), var(--feedback-viewer-y), 0) scale(var(--feedback-viewer-scale)) scale(.985); }
.feedback-viewer-loading { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: rgba(255, 255, 255, .78); font-size: 13px; opacity: 0; pointer-events: none; }
.feedback-viewer-loading.is-visible { opacity: 1; }
.feedback-viewer-loading i { font-size: 25px; color: var(--themecolor); }
.feedback-viewer-nav { position: absolute; top: 50%; z-index: 3; transform: translateY(-50%); }
.feedback-viewer-nav:active { transform: translateY(-50%) scale(.94); }
.feedback-viewer-prev { left: 8px; }
.feedback-viewer-next { right: 8px; }
.feedback-viewer-nav[hidden] { display: none !important; }
.feedback-viewer-controls { position: absolute; left: 50%; bottom: max(16px, env(safe-area-inset-bottom)); z-index: 4; width: max-content; max-width: calc(100vw - 32px); margin: 0; padding: 0; display: flex; align-items: center; gap: 7px; background: transparent; border: 0; box-shadow: none; transform: translate(-50%, 12px); transition: transform var(--feedback-viewer-duration) var(--feedback-viewer-easing); }
.feedback-image-viewer.is-open .feedback-viewer-controls { transform: translate(-50%, 0); }
.feedback-image-viewer.is-closing .feedback-viewer-controls { transform: translate(-50%, 12px); }
.feedback-viewer-zoom { min-width: 66px; height: 42px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; color: rgba(255, 255, 255, .82); background: var(--feedback-viewer-glass); border: 1px solid rgba(255, 255, 255, .14); border-radius: 999px; -webkit-backdrop-filter: blur(18px) saturate(150%); backdrop-filter: blur(18px) saturate(150%); font-size: 12px; text-align: center; font-variant-numeric: tabular-nums; }

/* 响应式 */
@media (max-width: 768px) {
	.feedback-form .form-row { grid-template-columns: 1fr; }
	.feedback-card-heading { flex-direction: column; align-items: stretch; }
	.feedback-draft-state { align-self: flex-start; }
	.feedback-type-selector { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	.feedback-list-tools { grid-template-columns: 1fr; }
	.feedback-list-clear { width: 100%; }
	.feedback-public-option { flex-wrap: wrap; }
	.feedback-public-option .hint { width: 100%; margin-left: 26px; margin-top: 4px; }
	.feedback-admin-stats { gap: 8px; }
	.feedback-admin-stat { min-width: 70px; padding: 12px 8px; }
	.feedback-admin-stat .stat-value { font-size: 20px; }
	.feedback-detail-meta { grid-template-columns: 1fr; }
	.feedback-modal-footer { flex-wrap: wrap; }
	.feedback-modal-footer .btn { flex: 1; min-width: 70px; }
	.feedback-image-item, .feedback-image-add-btn { width: 80px; height: 80px; }
	.feedback-images-display img { width: 88px; height: 88px; }
	.feedback-viewer-topbar { top: max(10px, env(safe-area-inset-top)); left: 10px; right: 10px; }
	.feedback-viewer-title { max-width: calc(100vw - 72px); }
	.feedback-viewer-image { max-width: 100vw; max-height: 100vh; }
	.feedback-viewer-nav { top: 50%; bottom: auto; }
	.feedback-viewer-prev { left: 8px; }
	.feedback-viewer-next { right: 8px; }
	.feedback-viewer-controls { bottom: max(10px, env(safe-area-inset-bottom)); max-width: calc(100vw - 20px); }
}
@media (max-width: 420px) {
	.feedback-form-card { padding: 20px 18px; }
	.feedback-type-selector { grid-template-columns: 1fr; }
}

/* 夜间模式 */
html.darkmode .feedback-item { background: rgba(255, 255, 255, 0.03); }
html.darkmode .feedback-modal { background: var(--color-background); }
html.darkmode .feedback-form-intro,
html.darkmode .feedback-list-summary,
html.darkmode .feedback-field-hint,
html.darkmode .feedback-item-footer,
html.darkmode .feedback-empty-filter { color: #aaa; }
html.darkmode .feedback-type-option,
html.darkmode .feedback-search-box input,
html.darkmode .feedback-list-tools select { background: var(--color-widgets); }
html.darkmode .feedback-type-option:hover,
html.darkmode .feedback-type-option:has(input:checked),
html.darkmode .feedback-type-option.is-active { background: rgba(var(--themecolor-rgbstr), 0.16); }
html.darkmode .feedback-submit-result { background: rgba(5, 150, 105, 0.18); color: #a7f3d0; border-color: rgba(5, 150, 105, 0.32); }
html.darkmode .feedback-draft-state { background: rgba(5, 150, 105, 0.18); color: #a7f3d0; border-color: rgba(5, 150, 105, 0.32); }
html.darkmode .feedback-badge.type-bug { background: rgba(220, 38, 38, 0.2); }
html.darkmode .feedback-badge.type-suggestion { background: rgba(37, 99, 235, 0.2); }
html.darkmode .feedback-badge.type-question { background: rgba(217, 119, 6, 0.2); }
html.darkmode .feedback-badge.type-other { background: rgba(107, 114, 128, 0.2); }
html.darkmode .feedback-badge.status-pending { background: rgba(217, 119, 6, 0.2); }
html.darkmode .feedback-badge.status-viewed { background: rgba(99, 102, 241, 0.2); }
html.darkmode .feedback-badge.status-processing { background: rgba(37, 99, 235, 0.2); }
html.darkmode .feedback-badge.status-resolved { background: rgba(5, 150, 105, 0.2); }
html.darkmode .feedback-badge.status-closed { background: rgba(107, 114, 128, 0.2); }
html.darkmode .feedback-admin-stat .stat-label { color: #aaa; }
html.darkmode .feedback-form .char-count { color: #aaa; }
html.darkmode .feedback-public-option .hint { color: #aaa; }
html.darkmode .feedback-item-time { color: #aaa; }
html.darkmode .feedback-item-content { color: #bbb; }
html.darkmode .feedback-item-reply-content { color: #bbb; }
html.darkmode .feedback-detail-label { color: #aaa; }
html.darkmode .feedback-detail-meta-item .label { color: #aaa; }
html.darkmode .feedback-modal-close { color: #aaa; }
html.darkmode .feedback-image-item { border-color: #555; }
html.darkmode .feedback-image-add-btn { border-color: #555; color: #aaa; }
html.darkmode .feedback-images-display img { border-color: #555; }
html.darkmode .feedback-list-tools { background: transparent; }
html.darkmode .feedback-image-viewer { --feedback-viewer-glass: rgba(16, 19, 29, .58); --feedback-viewer-glass-hover: rgba(40, 46, 63, .72); }

@media (prefers-reduced-motion: reduce) {
	.feedback-image-viewer,
	.feedback-viewer-topbar,
	.feedback-viewer-controls,
	.feedback-viewer-image { transition: none !important; }
}
</style>

<?php if ($is_auth_view && $single_feedback) : ?>
<!-- 授权链接查看单个反馈 -->
<?php
argon_special_page_surface_header(array(
	'icon' => 'commenting',
	'title' => __('反馈详情', 'argon'),
	'subtitle' => __('您可以在此查看反馈的详细信息和回复', 'argon'),
));
?>

<article class="post card shadow-sm bg-white border-0 flinks-surface-card feedback-auth-view">
	<div class="feedback-auth-notice">
		<i class="fa fa-lock"></i>
		<?php _e('这是您的专属链接，请妥善保管', 'argon'); ?>
	</div>
	<?php
	$type = $single_feedback['type'] ?? 'other';
	$status = $single_feedback['status'] ?? 'pending';
	$avatar_url = get_avatar_url($single_feedback['email'], ['size' => 80]);
	?>
	<div class="feedback-item" style="background: transparent; padding: 0;">
		<div class="feedback-item-header">
			<div class="feedback-item-avatar">
				<?php if ($avatar_url) : ?><img src="<?php echo esc_url($avatar_url); ?>" alt=""><?php else : echo mb_substr($single_feedback['name'], 0, 1); endif; ?>
			</div>
			<div class="feedback-item-meta">
				<div class="feedback-item-name">
					<?php echo esc_html($single_feedback['name']); ?>
					<span class="feedback-item-time"><?php echo date('Y-m-d H:i', $single_feedback['created_at']); ?></span>
				</div>
				<div class="feedback-item-badges">
					<span class="feedback-badge flinks-badge type-<?php echo $type; ?>"><?php echo $type_labels[$type] ?? $type; ?></span>
					<span class="feedback-badge flinks-badge status-<?php echo $status; ?>"><?php echo $status_labels[$status] ?? $status; ?></span>
				</div>
			</div>
		</div>
		<?php if (!empty($single_feedback['title'])) : ?>
		<div class="feedback-item-title"><?php echo esc_html($single_feedback['title']); ?></div>
		<?php endif; ?>
		<div class="feedback-item-content"><?php echo esc_html($single_feedback['content']); ?></div>
		<?php if (!empty($single_feedback['images'])) : ?>
		<div class="feedback-images-display">
			<?php foreach ($single_feedback['images'] as $image_index => $img_url) : ?>
			<img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(sprintf(__('反馈附件图片 %d', 'argon'), $image_index + 1)); ?>" data-feedback-preview tabindex="0" role="button">
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?php 
		// 兼容旧数据格式
		$replies = $single_feedback['replies'] ?? [];
		if (empty($replies) && !empty($single_feedback['reply'])) {
			$replies = [[
				'content' => $single_feedback['reply'],
				'time' => $single_feedback['reply_at'] ?? time(),
				'is_private' => false,
			]];
		}
		if (!empty($replies)) :
			foreach ($replies as $reply_item) :
				if (!empty($reply_item['is_private']) && !$can_view_private) : ?>
		<div class="feedback-item-reply" style="background:rgba(245,54,92,0.06);border-left-color:#f5365c;">
			<div class="feedback-item-reply-header"><i class="fa fa-lock"></i><?php _e('私密留言', 'argon'); ?> · <?php echo date('Y-m-d H:i', $reply_item['time']); ?></div>
			<div class="feedback-item-reply-content" style="color:#999;font-style:italic;"><?php _e('此留言仅反馈者和管理员可见', 'argon'); ?></div>
		</div>
				<?php elseif (!empty($reply_item['is_private']) && $can_view_private) : ?>
		<div class="feedback-item-reply" style="background:rgba(245,54,92,0.06);border-left-color:#f5365c;">
			<div class="feedback-item-reply-header"><i class="fa fa-lock"></i><?php _e('私密留言', 'argon'); ?> · <?php echo date('Y-m-d H:i', $reply_item['time']); ?></div>
			<div class="feedback-item-reply-content"><?php echo esc_html($reply_item['content']); ?></div>
		</div>
				<?php else : ?>
		<div class="feedback-item-reply">
			<div class="feedback-item-reply-header"><i class="fa fa-reply"></i><?php _e('博主回复', 'argon'); ?> · <?php echo date('Y-m-d H:i', $reply_item['time']); ?></div>
			<div class="feedback-item-reply-content"><?php echo esc_html($reply_item['content']); ?></div>
		</div>
				<?php endif;
			endforeach;
		endif; ?>
	</div>
	<div style="margin-top: 20px; text-align: center;">
		<a href="<?php echo home_url('/'); ?>?argon_feedback_view=1" class="btn btn-primary btn-sm">
			<i class="fa fa-arrow-left"></i> <?php _e('返回反馈页面', 'argon'); ?>
		</a>
	</div>
</article>

<?php else : ?>

<!-- 正常反馈页面 -->
<?php
$feedback_header_stats = array(
	array(
		'icon' => 'comments',
		'text' => sprintf(__('%d 条公开反馈', 'argon'), count($public_feedbacks)),
	),
);
if ($is_admin && $stats['pending'] > 0) {
	$feedback_header_stats[] = array(
		'icon' => 'clock-o',
		'text' => sprintf(__('%d 条待处理', 'argon'), $stats['pending']),
		'class' => 'flinks-badge is-warning',
	);
}
argon_special_page_surface_header(array(
	'icon' => 'commenting',
	'title' => __('问题反馈', 'argon'),
	'subtitle' => __('有任何建议或问题？欢迎告诉我！', 'argon'),
	'stats' => $feedback_header_stats,
));
?>

<?php if ($is_admin) : ?>
<div class="feedback-admin-stats">
	<div class="feedback-admin-stat">
		<div class="stat-value"><?php echo $stats['total']; ?></div>
		<div class="stat-label"><?php _e('总数', 'argon'); ?></div>
	</div>
	<div class="feedback-admin-stat resolved">
		<div class="stat-value"><?php echo $stats['resolved']; ?></div>
		<div class="stat-label"><?php _e('已完成', 'argon'); ?></div>
	</div>
	<div class="feedback-admin-stat pending">
		<div class="stat-value"><?php echo $stats['pending'] + $stats['processing']; ?></div>
		<div class="stat-label"><?php _e('待解决', 'argon'); ?></div>
	</div>
	<div class="feedback-admin-stat">
		<div class="stat-value"><?php echo $stats['closed']; ?></div>
		<div class="stat-label"><?php _e('已关闭', 'argon'); ?></div>
	</div>
</div>
<?php endif; ?>

<?php if (!$is_admin) : ?>
<!-- 访客提交反馈表单 -->
<article class="post card shadow-sm bg-white border-0 flinks-surface-card feedback-form-card" style="margin-bottom: 0;">
	<div class="feedback-card-heading">
		<div>
			<p class="feedback-kicker"><?php _e('Feedback', 'argon'); ?></p>
			<h2 class="feedback-form-title"><i class="fa fa-pencil-square-o"></i><?php _e('提交反馈', 'argon'); ?></h2>
		</div>
		<span class="feedback-draft-state" id="feedback-draft-state"><i class="fa fa-check-circle"></i><?php _e('草稿已保存', 'argon'); ?></span>
	</div>
	<p class="feedback-form-intro"><?php _e('描述越具体，越容易定位问题；截图会被压缩后上传，仅反馈管理员可查看私密内容。', 'argon'); ?></p>
	<form class="feedback-form" id="feedback-form">
		<input type="hidden" name="feedback_submit_nonce" value="<?php echo esc_attr(wp_create_nonce('argon_feedback_submit')); ?>">
		<div class="feedback-honeypot" aria-hidden="true">
			<label>Homepage <input type="text" name="feedback_homepage" tabindex="-1" autocomplete="off"></label>
		</div>
		<div class="form-row">
			<div class="form-group">
				<label><?php _e('昵称', 'argon'); ?> <span class="required">*</span></label>
				<input type="text" name="feedback_name" id="feedback-name" required placeholder="<?php _e('您的称呼', 'argon'); ?>" value="<?php echo esc_attr($saved_name); ?>">
			</div>
			<div class="form-group">
				<label><?php _e('邮箱', 'argon'); ?> <span class="required">*</span></label>
				<input type="email" name="feedback_email" id="feedback-email" required placeholder="<?php _e('用于接收回复通知', 'argon'); ?>" value="<?php echo esc_attr($saved_email); ?>">
			</div>
		</div>
		<div class="form-row">
			<div class="form-group">
				<label><?php _e('标题', 'argon'); ?></label>
				<input type="text" name="feedback_title" id="feedback-title" placeholder="<?php _e('简要描述您的反馈（选填）', 'argon'); ?>" maxlength="100">
			</div>
			<div class="form-group">
				<label><?php _e('网站', 'argon'); ?></label>
				<input type="url" name="feedback_url" id="feedback-url" placeholder="<?php _e('选填', 'argon'); ?>" value="<?php echo esc_attr($saved_url); ?>">
			</div>
		</div>
		<div class="form-row full">
			<div class="form-group">
				<label><?php _e('反馈类型', 'argon'); ?></label>
				<div class="feedback-type-selector" role="radiogroup" aria-label="<?php esc_attr_e('反馈类型', 'argon'); ?>">
					<label class="feedback-type-option">
						<input type="radio" name="feedback_type" value="suggestion" checked>
						<i class="fa fa-lightbulb-o"></i><span><?php _e('建议', 'argon'); ?></span>
					</label>
					<label class="feedback-type-option">
						<input type="radio" name="feedback_type" value="bug">
						<i class="fa fa-bug"></i><span><?php _e('Bug', 'argon'); ?></span>
					</label>
					<label class="feedback-type-option">
						<input type="radio" name="feedback_type" value="question">
						<i class="fa fa-question-circle"></i><span><?php _e('问题', 'argon'); ?></span>
					</label>
					<label class="feedback-type-option">
						<input type="radio" name="feedback_type" value="other">
						<i class="fa fa-ellipsis-h"></i><span><?php _e('其他', 'argon'); ?></span>
					</label>
				</div>
			</div>
		</div>
		<div class="form-row full">
			<div class="form-group">
				<label><?php _e('详细内容', 'argon'); ?> <span class="required">*</span> <span class="char-count"><span id="content-count">0</span>/2000</span></label>
				<textarea name="feedback_content" id="feedback-content" required placeholder="<?php _e('请详细描述您的建议或遇到的问题...', 'argon'); ?>" maxlength="2000" oninput="document.getElementById('content-count').textContent=this.value.length"></textarea>
			</div>
		</div>
		<div class="form-row full">
			<div class="form-group">
				<label><?php _e('上传图片', 'argon'); ?> <span class="feedback-field-hint"><?php printf(__('（选填，最多 %1$d 张，单张压缩后不超过 %2$s）', 'argon'), $feedback_image_settings['max_count'], size_format($feedback_image_settings['max_bytes'])); ?></span></label>
				<div class="feedback-image-upload">
					<div class="feedback-image-toolbar">
						<span class="feedback-image-status" id="feedback-image-status"><i class="fa fa-picture-o"></i><span><?php _e('尚未选择图片', 'argon'); ?></span></span>
						<button type="button" class="btn btn-secondary feedback-image-clear-btn" id="feedback-image-clear-btn"><i class="fa fa-trash-o"></i><?php _e('清空图片', 'argon'); ?></button>
					</div>
					<div class="feedback-image-preview" id="feedback-image-preview">
						<label class="feedback-image-add-btn" id="feedback-image-add-btn" for="feedback-image-input">
							<i class="fa fa-plus"></i>
							<span><?php _e('添加图片', 'argon'); ?></span>
						</label>
					</div>
					<input type="file" id="feedback-image-input" accept="image/*" multiple style="display:none;">
				</div>
			</div>
		</div>
		<div class="feedback-public-option">
			<input type="checkbox" name="feedback_public" id="feedback-public" value="1">
			<label for="feedback-public"><?php _e('公开此反馈', 'argon'); ?></label>
			<span class="hint"><?php _e('公开后其他访客可以看到', 'argon'); ?></span>
		</div>

		<?php if ($captcha_enabled) : ?>
		<?php if ($captcha_type === 'geetest') : ?>
		<div class="feedback-captcha">
			<input type="hidden" name="geetest_lot_number" id="geetest_lot_number">
			<input type="hidden" name="geetest_captcha_output" id="geetest_captcha_output">
			<input type="hidden" name="geetest_pass_token" id="geetest_pass_token">
			<input type="hidden" name="geetest_gen_time" id="geetest_gen_time">
		</div>
		<?php else : ?>
		<div class="feedback-captcha">
			<div class="input-group input-group-sm">
				<input type="text" name="feedback_captcha" id="feedback-captcha" class="form-control" placeholder="<?php _e('验证码', 'argon'); ?>" required style="max-width:120px;">
				<div class="input-group-append">
					<span class="input-group-text feedback-captcha-text" id="feedback-captcha-text" onclick="refreshFeedbackCaptcha()" title="<?php _e('点击刷新', 'argon'); ?>"><?php echo function_exists('get_comment_captcha') ? get_comment_captcha() : ''; ?></span>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<?php endif; ?>
		
		<div class="feedback-submit">
			<div class="feedback-submit-actions">
				<button type="submit" class="btn btn-primary" id="feedback-submit-btn"><i class="fa fa-paper-plane"></i> <?php _e('提交反馈', 'argon'); ?></button>
				<button type="button" class="btn btn-secondary" id="feedback-clear-draft-btn"><i class="fa fa-trash-o"></i> <?php _e('清空', 'argon'); ?></button>
			</div>
			<div class="feedback-submit-result" id="feedback-submit-result" hidden>
				<div class="feedback-submit-result-title"><i class="fa fa-check-circle"></i><span><?php _e('提交成功', 'argon'); ?></span></div>
				<p id="feedback-submit-result-message"></p>
				<div class="feedback-submit-result-actions">
					<a class="btn btn-sm btn-primary" id="feedback-view-link" href="#" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> <?php _e('查看详情', 'argon'); ?></a>
					<button type="button" class="btn btn-sm btn-secondary" id="feedback-copy-link"><i class="fa fa-link"></i> <?php _e('复制链接', 'argon'); ?></button>
				</div>
			</div>
		</div>
	</form>
</article>

<!-- 分割线 -->
<div class="feedback-divider"></div>
<?php endif; ?>

<!-- 公开反馈列表 -->
<article class="post card shadow-sm bg-white border-0 flinks-surface-card feedback-form-card" style="margin-bottom: 16px;">
	<div class="feedback-card-heading">
		<h2 class="feedback-list-title"><i class="fa fa-comments-o"></i><?php _e('公开反馈', 'argon'); ?><span class="count" id="feedback-visible-count"><?php echo count($public_feedbacks); ?></span></h2>
	</div>
	<?php if (!empty($public_feedbacks)) : ?>
		<div class="feedback-list-tools" id="feedback-list-tools">
			<div class="feedback-search-box">
				<i class="fa fa-search"></i>
				<input type="search" id="feedback-search" placeholder="<?php esc_attr_e('搜索标题、内容或昵称', 'argon'); ?>" aria-label="<?php esc_attr_e('搜索公开反馈', 'argon'); ?>">
			</div>
			<label class="feedback-filter-control">
				<i class="fa fa-tag"></i>
				<select id="feedback-type-filter" aria-label="<?php esc_attr_e('筛选反馈类型', 'argon'); ?>">
					<option value="all"><?php _e('全部类型', 'argon'); ?></option>
					<option value="suggestion"><?php _e('建议', 'argon'); ?></option>
					<option value="bug"><?php _e('Bug', 'argon'); ?></option>
					<option value="question"><?php _e('问题', 'argon'); ?></option>
					<option value="other"><?php _e('其他', 'argon'); ?></option>
				</select>
				<i class="fa fa-angle-down feedback-select-arrow"></i>
			</label>
			<label class="feedback-filter-control">
				<i class="fa fa-circle-o"></i>
				<select id="feedback-status-filter" aria-label="<?php esc_attr_e('筛选反馈状态', 'argon'); ?>">
					<option value="all"><?php _e('全部状态', 'argon'); ?></option>
					<option value="pending"><?php _e('未查看', 'argon'); ?></option>
					<option value="viewed"><?php _e('已查看', 'argon'); ?></option>
					<option value="processing"><?php _e('进行中', 'argon'); ?></option>
					<option value="resolved"><?php _e('已完成', 'argon'); ?></option>
					<option value="closed"><?php _e('已关闭', 'argon'); ?></option>
				</select>
				<i class="fa fa-angle-down feedback-select-arrow"></i>
			</label>
			<button type="button" class="btn btn-secondary feedback-list-clear" id="feedback-filter-reset" aria-label="<?php esc_attr_e('重置筛选', 'argon'); ?>"><i class="fa fa-undo"></i><span><?php _e('重置', 'argon'); ?></span></button>
		</div>
		<div class="feedback-list-summary" id="feedback-list-summary"><?php printf(__('显示全部 %d 条公开反馈', 'argon'), count($public_feedbacks)); ?></div>
		<?php foreach ($public_feedbacks as $fb) : 
			$type = $fb['type'] ?? 'other';
			$status = $fb['status'] ?? 'pending';
			$avatar_url = get_avatar_url($fb['email'], ['size' => 80]);
			$reply_count = 0;
			$public_reply_count = 0;
			$feedback_replies = $fb['replies'] ?? [];
			if (empty($feedback_replies) && !empty($fb['reply'])) {
				$feedback_replies = [[
					'content' => $fb['reply'],
					'time' => $fb['reply_at'] ?? time(),
					'is_private' => false,
				]];
			}
			if (!empty($feedback_replies)) {
				foreach ($feedback_replies as $reply_item_for_count) {
					$reply_count++;
					if (empty($reply_item_for_count['is_private']) || $can_view_private) {
						$public_reply_count++;
					}
				}
			}
		?>
		<div class="feedback-item <?php echo $is_admin ? 'clickable' : ''; ?>" data-id="<?php echo esc_attr($fb['id']); ?>" data-type="<?php echo esc_attr($type); ?>" data-status="<?php echo esc_attr($status); ?>" data-search="<?php echo esc_attr(strtolower(wp_strip_all_tags(($fb['name'] ?? '') . ' ' . ($fb['title'] ?? '') . ' ' . ($fb['content'] ?? '')))); ?>" <?php if ($is_admin) : ?>onclick="openFeedbackDetail('<?php echo esc_attr($fb['id']); ?>')"<?php endif; ?>>
			<div class="feedback-item-header">
				<div class="feedback-item-avatar">
					<?php if ($avatar_url) : ?><img src="<?php echo esc_url($avatar_url); ?>" alt=""><?php else : echo mb_substr($fb['name'], 0, 1); endif; ?>
				</div>
				<div class="feedback-item-meta">
					<div class="feedback-item-name">
						<?php echo esc_html($fb['name']); ?>
						<span class="feedback-item-time"><?php echo date('Y-m-d H:i', $fb['created_at']); ?></span>
					</div>
					<div class="feedback-item-badges">
						<span class="feedback-badge flinks-badge type-<?php echo $type; ?>"><?php echo $type_labels[$type] ?? $type; ?></span>
						<span class="feedback-badge flinks-badge status-<?php echo $status; ?>"><?php echo $status_labels[$status] ?? $status; ?></span>
					</div>
				</div>
			</div>
			<?php if (!empty($fb['title'])) : ?>
			<div class="feedback-item-title"><?php echo esc_html($fb['title']); ?></div>
			<?php endif; ?>
			<div class="feedback-item-content"><?php echo esc_html($fb['content']); ?></div>
			<?php if (!empty($fb['images'])) : ?>
			<div class="feedback-images-display">
				<?php foreach ($fb['images'] as $image_index => $img_url) : ?>
				<img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(sprintf(__('反馈附件图片 %d', 'argon'), $image_index + 1)); ?>" data-feedback-preview tabindex="0" role="button">
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<div class="feedback-item-footer">
				<span><i class="fa fa-picture-o"></i><?php echo !empty($fb['images']) ? count($fb['images']) : 0; ?></span>
				<span><i class="fa fa-reply"></i><?php echo $public_reply_count; ?></span>
				<?php if ($is_admin && $reply_count !== $public_reply_count) : ?>
				<span><i class="fa fa-lock"></i><?php echo $reply_count - $public_reply_count; ?></span>
				<?php endif; ?>
			</div>
			<?php 
			// 兼容旧数据格式
			$replies = $feedback_replies;
			if (!empty($replies)) :
				foreach ($replies as $reply_item) :
					if (!empty($reply_item['is_private']) && !$can_view_private) : ?>
			<div class="feedback-item-reply" style="background:rgba(245,54,92,0.06);border-left-color:#f5365c;">
				<div class="feedback-item-reply-header"><i class="fa fa-lock"></i><?php _e('私密留言', 'argon'); ?> · <?php echo date('Y-m-d H:i', $reply_item['time']); ?></div>
				<div class="feedback-item-reply-content" style="color:#999;font-style:italic;"><?php _e('此留言仅反馈者和管理员可见', 'argon'); ?></div>
			</div>
					<?php elseif (!empty($reply_item['is_private']) && $can_view_private) : ?>
			<div class="feedback-item-reply" style="background:rgba(245,54,92,0.06);border-left-color:#f5365c;">
				<div class="feedback-item-reply-header"><i class="fa fa-lock"></i><?php _e('私密留言', 'argon'); ?> · <?php echo date('Y-m-d H:i', $reply_item['time']); ?></div>
				<div class="feedback-item-reply-content"><?php echo esc_html($reply_item['content']); ?></div>
			</div>
					<?php else : ?>
			<div class="feedback-item-reply">
				<div class="feedback-item-reply-header"><i class="fa fa-reply"></i><?php _e('博主回复', 'argon'); ?> · <?php echo date('Y-m-d H:i', $reply_item['time']); ?></div>
				<div class="feedback-item-reply-content"><?php echo esc_html($reply_item['content']); ?></div>
			</div>
					<?php endif;
				endforeach;
			endif; ?>
		</div>
		<?php endforeach; ?>
		<div class="feedback-empty-filter" id="feedback-empty-filter">
			<i class="fa fa-filter"></i>
			<?php _e('没有符合筛选条件的公开反馈', 'argon'); ?>
		</div>
	<?php else : ?>
		<div class="feedback-empty flinks-empty">
			<i class="fa fa-inbox"></i>
			<p><?php _e('暂无公开反馈', 'argon'); ?></p>
		</div>
	<?php endif; ?>
</article>

<?php if ($is_admin) : 
	$private_feedbacks = array_filter($all_feedbacks, function($f) { return empty($f['is_public']); });
	if (!empty($private_feedbacks)) :
?>
<!-- 管理员：私密反馈 -->
<article class="post card shadow-sm bg-white border-0 flinks-surface-card feedback-admin-card" style="margin-bottom: 16px;">
	<h3 class="feedback-admin-title"><i class="fa fa-lock"></i><?php _e('私密反馈', 'argon'); ?> (<?php echo count($private_feedbacks); ?>)</h3>
	<?php foreach ($private_feedbacks as $fb) : 
		$type = $fb['type'] ?? 'other';
		$status = $fb['status'] ?? 'pending';
		$avatar_url = get_avatar_url($fb['email'], ['size' => 80]);
	?>
	<div class="feedback-item clickable" data-id="<?php echo esc_attr($fb['id']); ?>" onclick="openFeedbackDetail('<?php echo esc_attr($fb['id']); ?>')">
		<div class="feedback-item-header">
			<div class="feedback-item-avatar">
				<?php if ($avatar_url) : ?><img src="<?php echo esc_url($avatar_url); ?>" alt=""><?php else : echo mb_substr($fb['name'], 0, 1); endif; ?>
			</div>
			<div class="feedback-item-meta">
				<div class="feedback-item-name">
					<?php echo esc_html($fb['name']); ?>
					<span class="feedback-item-time"><?php echo date('Y-m-d H:i', $fb['created_at']); ?></span>
				</div>
				<div class="feedback-item-badges">
					<span class="feedback-badge flinks-badge type-<?php echo $type; ?>"><?php echo $type_labels[$type] ?? $type; ?></span>
					<span class="feedback-badge flinks-badge status-<?php echo $status; ?>"><?php echo $status_labels[$status] ?? $status; ?></span>
				</div>
			</div>
		</div>
		<?php if (!empty($fb['title'])) : ?>
		<div class="feedback-item-title"><?php echo esc_html($fb['title']); ?></div>
		<?php endif; ?>
		<div class="feedback-item-content"><?php echo esc_html(mb_substr($fb['content'], 0, 100)); ?><?php if (mb_strlen($fb['content']) > 100) echo '...'; ?></div>
	</div>
	<?php endforeach; ?>
</article>
<?php endif; ?>

<!-- 管理员详情弹窗 -->
<div class="feedback-modal-overlay" id="feedback-detail-modal">
	<div class="feedback-modal">
		<div class="feedback-modal-header">
			<h3 class="feedback-modal-title"><i class="fa fa-info-circle"></i> <?php _e('反馈详情', 'argon'); ?></h3>
			<button type="button" class="feedback-modal-close" onclick="closeFeedbackDetail()">&times;</button>
		</div>
		<div class="feedback-modal-body" id="feedback-detail-content">
			<!-- 动态填充 -->
		</div>
		<div class="feedback-modal-footer">
			<button type="button" class="btn btn-secondary btn-sm" onclick="setProcessing(this)"><i class="fa fa-tasks"></i> <?php _e('标记进行中', 'argon'); ?></button>
			<button type="button" class="btn btn-primary btn-sm" onclick="submitReplyAndResolve(this)"><i class="fa fa-check"></i> <?php _e('回复并完成', 'argon'); ?></button>
			<button type="button" class="btn btn-secondary btn-sm" onclick="closeFeedbackDetail()"><?php _e('取消', 'argon'); ?></button>
		</div>
	</div>
</div>
<?php endif; ?>

<?php endif; // end normal page ?>

<div class="feedback-image-viewer" id="feedback-image-viewer" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php esc_attr_e('图片预览', 'argon'); ?>">
	<div class="feedback-viewer-topbar">
		<div class="feedback-viewer-title">
			<i class="fa fa-picture-o"></i>
			<div class="feedback-viewer-details">
				<span class="feedback-viewer-name" id="feedback-viewer-name"><?php _e('反馈附件图片', 'argon'); ?></span>
				<span class="feedback-viewer-meta"><span id="feedback-viewer-counter">1 / 1</span><span class="feedback-viewer-meta-separator">·</span><span id="feedback-viewer-resolution">—</span></span>
			</div>
		</div>
		<button type="button" class="feedback-viewer-close" id="feedback-viewer-close" aria-label="<?php esc_attr_e('关闭预览', 'argon'); ?>"><i class="fa fa-times"></i></button>
	</div>
	<div class="feedback-viewer-stage" id="feedback-viewer-stage">
		<button type="button" class="feedback-viewer-nav feedback-viewer-prev" id="feedback-viewer-prev" aria-label="<?php esc_attr_e('上一张', 'argon'); ?>"><i class="fa fa-angle-left"></i></button>
		<div class="feedback-viewer-loading" id="feedback-viewer-loading"><i class="fa fa-spinner fa-spin"></i><span><?php _e('图片加载中…', 'argon'); ?></span></div>
		<img class="feedback-viewer-image" id="feedback-viewer-image" alt="" draggable="false">
		<button type="button" class="feedback-viewer-nav feedback-viewer-next" id="feedback-viewer-next" aria-label="<?php esc_attr_e('下一张', 'argon'); ?>"><i class="fa fa-angle-right"></i></button>
	</div>
	<div class="feedback-viewer-controls" aria-label="<?php esc_attr_e('图片缩放控件', 'argon'); ?>">
		<button type="button" class="feedback-viewer-control" id="feedback-viewer-zoom-out" aria-label="<?php esc_attr_e('缩小', 'argon'); ?>"><i class="fa fa-search-minus"></i></button>
		<button type="button" class="feedback-viewer-control" id="feedback-viewer-reset" aria-label="<?php esc_attr_e('重置缩放', 'argon'); ?>"><i class="fa fa-refresh"></i></button>
		<span class="feedback-viewer-zoom" id="feedback-viewer-zoom">100%</span>
		<button type="button" class="feedback-viewer-control" id="feedback-viewer-zoom-in" aria-label="<?php esc_attr_e('放大', 'argon'); ?>"><i class="fa fa-search-plus"></i></button>
	</div>
</div>

<script data-pjax>
var feedbackNonce = '<?php echo wp_create_nonce('argon_feedback_manage'); ?>';
var feedbackUploadNonce = '<?php echo wp_create_nonce('argon_feedback_upload'); ?>';
var feedbackAjaxUrl = <?php echo wp_json_encode(esc_url_raw($_SERVER['REQUEST_URI'])); ?>;
var feedbackCaptchaType = '<?php echo esc_js($captcha_type); ?>';
var feedbackCaptchaEnabled = <?php echo $captcha_enabled ? 'true' : 'false'; ?>;
var currentFeedbackId = null;
var feedbackImages = []; // 提交前实际上传成功的图片 URL
var feedbackImageQueue = [];
var feedbackImageNextId = 1;
var maxFeedbackImages = <?php echo intval($feedback_image_settings['max_count']); ?>;
var maxImageSize = <?php echo intval($feedback_image_settings['max_bytes']); ?>;
var maxImageDimension = <?php echo intval($feedback_image_settings['max_dimension']); ?>;
var maxSourceImageSize = <?php echo intval($feedback_image_settings['max_source_bytes']); ?>;
var uploadingCount = 0; // 正在处理/上传的图片数量
var feedbackDraftKey = 'argon_feedback_form_draft';

function getFeedbackActionUrl(action) {
	let url = new URL(feedbackAjaxUrl, window.location.origin);
	url.searchParams.set('action', action);
	return url.toString();
}

function showFeedbackNotice(type, title, message) {
	if (typeof iziToast !== 'undefined' && iziToast[type]) {
		iziToast[type]({
			title: title,
			message: message || '',
			position: 'topRight',
			timeout: 3500
		});
		return;
	}
	alert(message ? title + '\n' + message : title);
}

function initFeedbackImageViewer() {
	let viewer = document.getElementById('feedback-image-viewer');
	let main = document.getElementById('main');
	if (!viewer || !main) return;
	if (viewer.dataset.viewerReady === 'true' && window.__argonFeedbackViewerController) return;

	if (window.__argonFeedbackViewerController && typeof window.__argonFeedbackViewerController.destroy === 'function') {
		window.__argonFeedbackViewerController.destroy();
	}
	viewer.dataset.viewerReady = 'true';

	let stage = document.getElementById('feedback-viewer-stage');
	let image = document.getElementById('feedback-viewer-image');
	let loading = document.getElementById('feedback-viewer-loading');
	let loadingIcon = loading ? loading.querySelector('i') : null;
	let loadingText = loading ? loading.querySelector('span') : null;
	let counter = document.getElementById('feedback-viewer-counter');
	let imageName = document.getElementById('feedback-viewer-name');
	let resolution = document.getElementById('feedback-viewer-resolution');
	let zoomText = document.getElementById('feedback-viewer-zoom');
	let closeButton = document.getElementById('feedback-viewer-close');
	let previousButton = document.getElementById('feedback-viewer-prev');
	let nextButton = document.getElementById('feedback-viewer-next');
	let zoomInButton = document.getElementById('feedback-viewer-zoom-in');
	let zoomOutButton = document.getElementById('feedback-viewer-zoom-out');
	let resetButton = document.getElementById('feedback-viewer-reset');
	if (!stage || !image || !loading || !closeButton) return;

	let state = {
		items: [], index: 0, scale: 1, x: 0, y: 0,
		dragging: false, pointerId: null, startX: 0, startY: 0,
		loadId: 0, closeTimer: null, lastFocused: null
	};

	function setLoading(mode) {
		loading.classList.toggle('is-visible', mode !== 'done');
		if (!loadingIcon || !loadingText) return;
		if (mode === 'error') {
			loadingIcon.className = 'fa fa-exclamation-circle';
			loadingText.textContent = '<?php echo esc_js(__('图片加载失败', 'argon')); ?>';
		} else {
			loadingIcon.className = 'fa fa-spinner fa-spin';
			loadingText.textContent = '<?php echo esc_js(__('图片加载中…', 'argon')); ?>';
		}
	}

	function updateTransform() {
		if (state.scale <= 1) {
			state.scale = 1;
			state.x = 0;
			state.y = 0;
		}
		image.style.setProperty('--feedback-viewer-x', state.x + 'px');
		image.style.setProperty('--feedback-viewer-y', state.y + 'px');
		image.style.setProperty('--feedback-viewer-scale', state.scale);
		image.classList.toggle('is-zoomed', state.scale > 1);
		if (zoomText) zoomText.textContent = Math.round(state.scale * 100) + '%';
	}

	function resetTransform() {
		state.scale = 1;
		state.x = 0;
		state.y = 0;
		state.dragging = false;
		image.classList.remove('is-dragging', 'is-zoomed');
		updateTransform();
	}

	function collectItems(trigger) {
		let gallery = trigger.closest('.feedback-images-display, .feedback-image-preview');
		let nodes = gallery ? Array.from(gallery.querySelectorAll('[data-feedback-preview], .feedback-image-item img')) : [trigger];
		let items = nodes.map(function(node) {
			let src = node.currentSrc || node.getAttribute('src') || node.getAttribute('data-src') || '';
			let name = '';
			try {
				let pathname = new URL(src, window.location.href).pathname;
				name = decodeURIComponent(pathname.split('/').filter(Boolean).pop() || '');
			} catch (error) {}
			return {
				node: node,
				src: src,
				name: name || node.getAttribute('alt') || '<?php echo esc_js(__('反馈附件图片', 'argon')); ?>',
				alt: node.getAttribute('alt') || name || '<?php echo esc_js(__('反馈附件图片', 'argon')); ?>'
			};
		}).filter(function(item) { return item.src !== ''; });
		let index = items.findIndex(function(item) { return item.node === trigger; });
		return { items: items, index: index >= 0 ? index : 0 };
	}

	function renderCurrentImage() {
		if (!state.items.length) return;
		state.loadId++;
		let activeLoadId = state.loadId;
		let item = state.items[state.index];
		resetTransform();
		image.classList.remove('is-ready');
		image.removeAttribute('src');
		setLoading('loading');
		if (counter) counter.textContent = (state.index + 1) + ' / ' + state.items.length;
		if (imageName) imageName.textContent = item.name;
		if (resolution) resolution.textContent = '—';
		let hasMultiple = state.items.length > 1;
		if (previousButton) previousButton.hidden = !hasMultiple;
		if (nextButton) nextButton.hidden = !hasMultiple;

		let preload = new Image();
		preload.onload = function() {
			if (activeLoadId !== state.loadId) return;
			image.src = item.src;
			image.alt = item.alt;
			if (resolution) resolution.textContent = preload.naturalWidth + ' × ' + preload.naturalHeight;
			setLoading('done');
			requestAnimationFrame(function() {
				if (activeLoadId === state.loadId) image.classList.add('is-ready');
			});
		};
		preload.onerror = function() {
			if (activeLoadId !== state.loadId) return;
			if (resolution) resolution.textContent = '<?php echo esc_js(__('加载失败', 'argon')); ?>';
			setLoading('error');
		};
		preload.src = item.src;
	}

	function openViewer(trigger) {
		let gallery = collectItems(trigger);
		if (!gallery.items.length) return;
		clearTimeout(state.closeTimer);
		state.items = gallery.items;
		state.index = gallery.index;
		state.lastFocused = document.activeElement;
		viewer.classList.remove('is-closing');
		viewer.classList.add('is-open');
		viewer.setAttribute('aria-hidden', 'false');
		document.body.classList.add('feedback-viewer-open');
		renderCurrentImage();
		closeButton.focus({ preventScroll: true });
	}

	function getCloseDelay() {
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return 0;
		let rawDuration = getComputedStyle(viewer).getPropertyValue('--feedback-viewer-duration').trim();
		let duration = parseFloat(rawDuration);
		if (!Number.isFinite(duration)) return 360;
		return rawDuration.indexOf('ms') !== -1 ? duration : duration * 1000;
	}

	function closeViewer(immediate) {
		if (!viewer.classList.contains('is-open') && !viewer.classList.contains('is-closing')) return;
		state.loadId++;
		clearTimeout(state.closeTimer);
		document.body.classList.remove('feedback-viewer-open');
		viewer.setAttribute('aria-hidden', 'true');
		if (immediate) {
			viewer.classList.remove('is-open', 'is-closing');
			image.classList.remove('is-ready');
			image.removeAttribute('src');
			return;
		}
		viewer.classList.add('is-closing');
		state.closeTimer = setTimeout(function() {
			viewer.classList.remove('is-open', 'is-closing');
			image.classList.remove('is-ready');
			image.removeAttribute('src');
			if (state.lastFocused && document.contains(state.lastFocused)) state.lastFocused.focus();
		}, getCloseDelay());
	}

	function move(step) {
		if (state.items.length < 2) return;
		state.index = (state.index + step + state.items.length) % state.items.length;
		renderCurrentImage();
	}

	function zoom(multiplier) {
		state.scale = Math.max(1, Math.min(5, state.scale * multiplier));
		updateTransform();
	}

	function handleMainClick(event) {
		let trigger = event.target.closest('[data-feedback-preview], .feedback-image-item img');
		if (!trigger || !main.contains(trigger)) return;
		event.preventDefault();
		event.stopPropagation();
		openViewer(trigger);
	}

	function handleMainKeydown(event) {
		if (event.key !== 'Enter' && event.key !== ' ') return;
		let trigger = event.target.closest('[data-feedback-preview], .feedback-image-item img');
		if (!trigger || !main.contains(trigger)) return;
		event.preventDefault();
		event.stopPropagation();
		openViewer(trigger);
	}

	function handleDocumentKeydown(event) {
		if (!viewer.classList.contains('is-open')) return;
		if (event.key === 'Escape') closeViewer(false);
		else if (event.key === 'ArrowLeft') move(-1);
		else if (event.key === 'ArrowRight') move(1);
		else if (event.key === '+' || event.key === '=') zoom(1.2);
		else if (event.key === '-') zoom(1 / 1.2);
		else if (event.key === '0') resetTransform();
		else if (event.key === 'Tab') {
			let controls = Array.from(viewer.querySelectorAll('button:not([hidden])'));
			if (!controls.length) return;
			let first = controls[0];
			let last = controls[controls.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault(); last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault(); first.focus();
			}
		}
	}

	function handlePointerDown(event) {
		if (event.target !== image || state.scale <= 1) return;
		state.dragging = true;
		state.pointerId = event.pointerId;
		state.startX = event.clientX - state.x;
		state.startY = event.clientY - state.y;
		image.classList.add('is-dragging');
		image.setPointerCapture(event.pointerId);
	}

	function handlePointerMove(event) {
		if (!state.dragging || event.pointerId !== state.pointerId) return;
		state.x = event.clientX - state.startX;
		state.y = event.clientY - state.startY;
		updateTransform();
	}

	function handlePointerUp(event) {
		if (event.pointerId !== state.pointerId) return;
		state.dragging = false;
		state.pointerId = null;
		image.classList.remove('is-dragging');
	}

	function handlePjaxSend() {
		destroyController();
	}

	function destroyController() {
		closeViewer(true);
		document.removeEventListener('keydown', handleDocumentKeydown);
		if (typeof jQuery !== 'undefined') {
			jQuery(document).off('pjax:send.feedbackViewer', handlePjaxSend);
		} else {
			document.removeEventListener('pjax:send', handlePjaxSend);
		}
		main.removeEventListener('click', handleMainClick, true);
		main.removeEventListener('keydown', handleMainKeydown, true);
		clearTimeout(state.closeTimer);
		viewer.dataset.viewerReady = 'false';
		if (window.__argonFeedbackViewerController && window.__argonFeedbackViewerController.destroy === destroyController) {
			window.__argonFeedbackViewerController = null;
		}
	}

	main.addEventListener('click', handleMainClick, true);
	main.addEventListener('keydown', handleMainKeydown, true);
	document.addEventListener('keydown', handleDocumentKeydown);
	if (typeof jQuery !== 'undefined') {
		jQuery(document).off('pjax:send.feedbackViewer', handlePjaxSend).on('pjax:send.feedbackViewer', handlePjaxSend);
	} else {
		document.addEventListener('pjax:send', handlePjaxSend);
	}
	closeButton.addEventListener('click', function() { closeViewer(false); });
	if (previousButton) previousButton.addEventListener('click', function() { move(-1); });
	if (nextButton) nextButton.addEventListener('click', function() { move(1); });
	if (zoomInButton) zoomInButton.addEventListener('click', function() { zoom(1.2); });
	if (zoomOutButton) zoomOutButton.addEventListener('click', function() { zoom(1 / 1.2); });
	if (resetButton) resetButton.addEventListener('click', resetTransform);
	stage.addEventListener('click', function(event) {
		if (event.target === stage && window.ArgonMotion.isOutsideClick(event, image)) closeViewer(false);
	});
	stage.addEventListener('wheel', function(event) {
		event.preventDefault();
		zoom(event.deltaY < 0 ? 1.12 : 1 / 1.12);
	}, { passive: false });
	image.addEventListener('dblclick', function() {
		state.scale = state.scale > 1 ? 1 : 2;
		updateTransform();
	});
	image.addEventListener('pointerdown', handlePointerDown);
	image.addEventListener('pointermove', handlePointerMove);
	image.addEventListener('pointerup', handlePointerUp);
	image.addEventListener('pointercancel', handlePointerUp);

	window.__argonFeedbackViewerController = {
		close: closeViewer,
		destroy: destroyController
	};
}

function refreshFeedbackCaptcha() {
	fetch(getFeedbackActionUrl('refresh_captcha'))
		.then(function(r) { return r.json(); })
		.then(function(res) {
			let captchaText = document.getElementById('feedback-captcha-text');
			if (res.captcha && captchaText) {
				captchaText.textContent = res.captcha;
			}
		});
}

function initFeedbackListFilters() {
	let search = document.getElementById('feedback-search');
	let typeFilter = document.getElementById('feedback-type-filter');
	let statusFilter = document.getElementById('feedback-status-filter');
	let reset = document.getElementById('feedback-filter-reset');
	let count = document.getElementById('feedback-visible-count');
	let summary = document.getElementById('feedback-list-summary');
	let empty = document.getElementById('feedback-empty-filter');
	let items = Array.from(document.querySelectorAll('.feedback-item[data-search]'));
	if (!items.length || !search || !typeFilter || !statusFilter) return;

	function applyFilters() {
		let keyword = search.value.trim().toLowerCase();
		let selectedType = typeFilter.value;
		let selectedStatus = statusFilter.value;
		let visible = 0;
		items.forEach(function(item) {
			let matched = true;
			if (keyword && (item.getAttribute('data-search') || '').indexOf(keyword) === -1) matched = false;
			if (selectedType !== 'all' && item.getAttribute('data-type') !== selectedType) matched = false;
			if (selectedStatus !== 'all' && item.getAttribute('data-status') !== selectedStatus) matched = false;
			item.style.display = matched ? '' : 'none';
			if (matched) visible++;
		});
		if (count) count.textContent = visible;
		if (summary) {
			summary.textContent = keyword || selectedType !== 'all' || selectedStatus !== 'all'
				? '<?php _e('当前显示', 'argon'); ?> ' + visible + ' / ' + items.length + ' <?php _e('条公开反馈', 'argon'); ?>'
				: '<?php printf(__('显示全部 %d 条公开反馈', 'argon'), count($public_feedbacks)); ?>';
		}
		if (empty) empty.classList.toggle('show', visible === 0);
	}

	search.addEventListener('input', applyFilters);
	typeFilter.addEventListener('change', applyFilters);
	statusFilter.addEventListener('change', applyFilters);
	if (reset) {
		reset.addEventListener('click', function() {
			search.value = '';
			typeFilter.value = 'all';
			statusFilter.value = 'all';
			applyFilters();
		});
	}
	applyFilters();
}

<?php if ($captcha_enabled && $captcha_type === 'geetest' && !$is_admin) : ?>
let feedbackGeetestCaptcha = null;
let feedbackGeetestValidated = false;
let feedbackGeetestLoading = false;

function loadFeedbackGeetestScript(callback) {
	if (typeof initGeetest4 !== 'undefined') {
		if (callback) callback();
		return;
	}
	if (feedbackGeetestLoading) {
		let t0 = Date.now();
		(function wait() {
			if (typeof initGeetest4 !== 'undefined') {
				if (callback) callback();
				return;
			}
			if (Date.now() - t0 > 5000) {
				feedbackGeetestLoading = false;
				return;
			}
			setTimeout(wait, 100);
		})();
		return;
	}
	let existing = Array.prototype.slice.call(document.getElementsByTagName('script')).some(function(s) {
		return (s.src || '').indexOf('static.geetest.com/v4/gt4.js') !== -1;
	});
	if (!existing) {
		let s = document.createElement('script');
		s.src = 'https://static.geetest.com/v4/gt4.js';
		s.async = true;
		s.onload = function() {
			feedbackGeetestLoading = false;
			if (callback) callback();
		};
		s.onerror = function() {
			feedbackGeetestLoading = false;
		};
		document.head.appendChild(s);
		feedbackGeetestLoading = true;
	} else {
		feedbackGeetestLoading = true;
		let t1 = Date.now();
		(function wait2() {
			if (typeof initGeetest4 !== 'undefined') {
				feedbackGeetestLoading = false;
				if (callback) callback();
				return;
			}
			if (Date.now() - t1 > 5000) {
				feedbackGeetestLoading = false;
				return;
			}
			setTimeout(wait2, 100);
		})();
	}
}

function initFeedbackGeetestCore() {
	initGeetest4({
		captchaId: '<?php echo esc_js(get_option('argon_geetest_captcha_id', '')); ?>',
		product: 'bind'
	}, function(captcha) {
		feedbackGeetestCaptcha = captcha;
		captcha.onSuccess(function() {
			let result = captcha.getValidate();
			document.getElementById('geetest_lot_number').value = result.lot_number;
			document.getElementById('geetest_captcha_output').value = result.captcha_output;
			document.getElementById('geetest_pass_token').value = result.pass_token;
			document.getElementById('geetest_gen_time').value = result.gen_time;
			feedbackGeetestValidated = true;
			submitFeedbackForm();
		});
	});
}

function initFeedbackGeetest() {
	loadFeedbackGeetestScript(function() {
		initFeedbackGeetestCore();
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initFeedbackGeetest);
} else {
	initFeedbackGeetest();
}
<?php endif; ?>

<?php if (!$is_admin) : ?>
// ==================== 图片上传处理 ====================
var feedbackImageLabels = <?php echo wp_json_encode([
	'idle' => __('尚未选择图片', 'argon'),
	'count' => __('已选择 %1$d / %2$d 张', 'argon'),
	'processing' => __('正在处理 %d 张图片', 'argon'),
	'ready' => __('等待提交时上传', 'argon'),
	'done' => __('已上传', 'argon'),
	'compressing' => __('压缩中', 'argon'),
	'uploading' => __('上传中', 'argon'),
	'error' => __('失败', 'argon'),
	'retry' => __('重试', 'argon'),
	'remove' => __('删除图片', 'argon'),
	'formatError' => __('只能上传 JPG、PNG、GIF 或 WebP 图片', 'argon'),
	'tooMany' => __('最多上传 %d 张图片', 'argon'),
	'sourceTooLarge' => __('原图不能超过 %s', 'argon'),
	'compressFail' => __('图片无法压缩到 %s 以下，请换一张更小或内容更简单的图片', 'argon'),
	'readFail' => __('图片读取失败', 'argon'),
	'networkFail' => __('网络错误，请稍后重试', 'argon'),
	'waitProcessing' => __('请等待图片处理完成', 'argon'),
	'fixImages' => __('请先重试或删除失败的图片', 'argon'),
	'uploadFail' => __('图片上传失败', 'argon'),
	'clearImages' => __('图片已清空', 'argon'),
], JSON_UNESCAPED_UNICODE); ?>;
var feedbackImageConfig = {
	maxCount: maxFeedbackImages,
	maxSizeLabel: <?php echo wp_json_encode(size_format($feedback_image_settings['max_bytes'])); ?>,
	maxSourceSizeLabel: <?php echo wp_json_encode(size_format($feedback_image_settings['max_source_bytes'])); ?>
};

function formatFeedbackImageLabel(template, args) {
	var text = template || '';
	for (var i = 0; i < args.length; i++) {
		text = text.replace('%' + (i + 1) + '$d', args[i]);
		text = text.replace('%' + (i + 1) + '$s', args[i]);
		text = text.replace('%d', args[i]);
		text = text.replace('%s', args[i]);
	}
	return text;
}

function getFeedbackImageProcessingCount() {
	var count = 0;
	for (var i = 0; i < feedbackImageQueue.length; i++) {
		if (feedbackImageQueue[i].status === 'compressing' || feedbackImageQueue[i].status === 'uploading') {
			count++;
		}
	}
	return count;
}

function getFeedbackImageErrorCount() {
	var count = 0;
	for (var i = 0; i < feedbackImageQueue.length; i++) {
		if (feedbackImageQueue[i].status === 'error') {
			count++;
		}
	}
	return count;
}

function updateFeedbackImageStatus() {
	var status = document.getElementById('feedback-image-status');
	var clearBtn = document.getElementById('feedback-image-clear-btn');
	if (!status) return;
	var text = feedbackImageLabels.idle;
	var processing = getFeedbackImageProcessingCount();
	var errors = getFeedbackImageErrorCount();
	if (processing > 0) {
		text = formatFeedbackImageLabel(feedbackImageLabels.processing, [processing]);
	} else if (errors > 0) {
		text = feedbackImageLabels.error + ' ' + errors + ' / ' + feedbackImageQueue.length;
	} else if (feedbackImageQueue.length > 0) {
		text = formatFeedbackImageLabel(feedbackImageLabels.count, [feedbackImageQueue.length, maxFeedbackImages]);
	}
	var label = status.querySelector('span');
	if (label) label.textContent = text;
	if (clearBtn) clearBtn.classList.toggle('show', feedbackImageQueue.length > 0);
	uploadingCount = processing;
}

function estimateFeedbackImageDataUrlSize(dataUrl) {
	var marker = ';base64,';
	var index = dataUrl.indexOf(marker);
	var length = index === -1 ? dataUrl.length : dataUrl.length - index - marker.length;
	return Math.round(length * 0.75);
}

function escapeFeedbackImageHtml(text) {
	var div = document.createElement('div');
	div.textContent = text || '';
	return div.innerHTML;
}

function compressFeedbackImage(file, item) {
	return new Promise(function(resolve, reject) {
		if (!file || !file.type || !/^image\/(jpeg|jpg|png|gif|webp)$/i.test(file.type)) {
			reject(new Error(feedbackImageLabels.formatError));
			return;
		}
		if (file.size > maxSourceImageSize) {
			reject(new Error(formatFeedbackImageLabel(feedbackImageLabels.sourceTooLarge, [feedbackImageConfig.maxSourceSizeLabel])));
			return;
		}

		var reader = new FileReader();
		reader.onerror = function() {
			reject(new Error(feedbackImageLabels.readFail));
		};
		reader.onload = function(e) {
			var img = new Image();
			img.onerror = function() {
				reject(new Error(feedbackImageLabels.readFail));
			};
			img.onload = function() {
				var sourceWidth = img.naturalWidth || img.width;
				var sourceHeight = img.naturalHeight || img.height;
				var width = sourceWidth;
				var height = sourceHeight;
				var ratio = Math.min(1, maxImageDimension / Math.max(width, height));
				width = Math.max(1, Math.round(width * ratio));
				height = Math.max(1, Math.round(height * ratio));

				var canvas = document.createElement('canvas');
				var ctx = canvas.getContext('2d');
				var quality = 0.88;
				var minQuality = 0.42;
				var output = null;
				var attempts = 0;

				while (attempts < 32) {
					canvas.width = width;
					canvas.height = height;
					ctx.clearRect(0, 0, width, height);
					ctx.drawImage(img, 0, 0, width, height);
					var dataUrl = canvas.toDataURL('image/jpeg', quality);
					var size = estimateFeedbackImageDataUrlSize(dataUrl);
					output = { dataUrl: dataUrl, size: size, width: width, height: height };
					if (size <= maxImageSize) break;
					if (quality > minQuality) {
						quality = Math.max(minQuality, quality - 0.08);
					} else if (width > 640 || height > 640) {
						width = Math.max(1, Math.round(width * 0.86));
						height = Math.max(1, Math.round(height * 0.86));
						quality = 0.82;
					} else {
						break;
					}
					attempts++;
					if (item) {
						item.progress = Math.min(88, 18 + attempts * 5);
						updateImagePreview();
					}
				}

				if (!output || output.size > maxImageSize) {
					reject(new Error(formatFeedbackImageLabel(feedbackImageLabels.compressFail, [feedbackImageConfig.maxSizeLabel])));
					return;
				}
				resolve(output);
			};
			img.src = e.target.result;
		};
		reader.readAsDataURL(file);
	});
}

function prepareFeedbackImageItem(item) {
	item.status = 'compressing';
	item.error = '';
	item.url = '';
	item.progress = 8;
	updateImagePreview();
	compressFeedbackImage(item.file, item)
		.then(function(result) {
			item.dataUrl = result.dataUrl;
			item.previewUrl = result.dataUrl;
			item.size = result.size;
			item.width = result.width;
			item.height = result.height;
			item.status = 'ready';
			item.progress = 100;
			updateImagePreview();
		})
		.catch(function(err) {
			item.status = 'error';
			item.error = err && err.message ? err.message : feedbackImageLabels.compressFail;
			item.progress = 0;
			updateImagePreview();
			showFeedbackNotice('error', item.error);
		});
}

function uploadFeedbackImageItem(item) {
	return new Promise(function(resolve, reject) {
		if (item.status === 'done' && item.url) {
			resolve(item.url);
			return;
		}
		if (!item.dataUrl || item.status === 'error') {
			reject(new Error(item.error || feedbackImageLabels.fixImages));
			return;
		}

		item.status = 'uploading';
		item.progress = 18;
		updateImagePreview();

		var formData = new FormData();
		formData.append('feedback_action', 'upload_image');
		formData.append('feedback_upload_nonce', feedbackUploadNonce);
		formData.append('image_data', item.dataUrl);

		fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
			.then(function(r) {
				item.progress = 72;
				updateImagePreview();
				return r.json();
			})
			.then(function(res) {
				if (res.success && res.url) {
					item.status = 'done';
					item.url = res.url;
					item.progress = 100;
					updateImagePreview();
					resolve(res.url);
				} else {
					item.status = 'error';
					item.error = res.message || feedbackImageLabels.uploadFail;
					item.progress = 0;
					updateImagePreview();
					reject(new Error(item.error));
				}
			})
			.catch(function(err) {
				item.status = 'error';
				item.error = err && err.message ? err.message : feedbackImageLabels.networkFail;
				item.progress = 0;
				updateImagePreview();
				reject(new Error(item.error));
			});
	});
}

function uploadFeedbackQueuedImages() {
	var chain = Promise.resolve([]);
	feedbackImageQueue.forEach(function(item) {
		chain = chain.then(function(urls) {
			return uploadFeedbackImageItem(item).then(function(url) {
				urls.push(url);
				return urls;
			});
		});
	});
	return chain;
}

function updateImagePreview() {
	var preview = document.getElementById('feedback-image-preview');
	var addBtn = document.getElementById('feedback-image-add-btn');
	if (!preview || !addBtn) return;

	preview.innerHTML = '';
	for (var i = 0; i < feedbackImageQueue.length; i++) {
		var image = feedbackImageQueue[i];
		var item = document.createElement('div');
		var statusClass = image.status ? ' status-' + image.status : '';
		item.className = 'feedback-image-item' + statusClass + (image.status === 'uploading' || image.status === 'compressing' ? ' uploading' : '');
		var src = image.previewUrl || image.dataUrl || '';
		var stateText = image.error || feedbackImageLabels[image.status] || feedbackImageLabels.ready;
		var html = '';
		if (src) {
			html += '<img src="' + src + '" alt="<?php echo esc_js(__('待提交附件图片', 'argon')); ?> ' + (i + 1) + '" data-feedback-preview tabindex="0" role="button">';
		} else {
			html += '<div class="uploading-spinner"><i class="fa fa-spinner fa-spin"></i></div>';
		}
		html += '<span class="image-state">' + escapeFeedbackImageHtml(stateText) + '</span>';
		html += '<button type="button" class="remove-btn" onclick="removeFeedbackImage(' + image.id + ')" aria-label="' + escapeFeedbackImageHtml(feedbackImageLabels.remove) + '">&times;</button>';
		if (image.status === 'error') {
			html += '<button type="button" class="retry-btn" onclick="retryFeedbackImage(' + image.id + ')">' + escapeFeedbackImageHtml(feedbackImageLabels.retry) + '</button>';
		}
		if (image.status === 'compressing' || image.status === 'uploading') {
			html += '<div class="image-progress"><span style="width:' + Math.max(8, image.progress || 8) + '%"></span></div>';
		}
		item.innerHTML = html;
		preview.appendChild(item);
	}

	addBtn.classList.toggle('disabled', feedbackImageQueue.length >= maxFeedbackImages);
	preview.appendChild(addBtn);
	updateFeedbackImageStatus();
}

function findFeedbackImage(id) {
	for (var i = 0; i < feedbackImageQueue.length; i++) {
		if (feedbackImageQueue[i].id === id) return feedbackImageQueue[i];
	}
	return null;
}

function removeFeedbackImage(id) {
	for (var i = 0; i < feedbackImageQueue.length; i++) {
		if (feedbackImageQueue[i].id === id) {
			feedbackImageQueue.splice(i, 1);
			break;
		}
	}
	updateImagePreview();
}

function retryFeedbackImage(id) {
	var item = findFeedbackImage(id);
	if (!item) return;
	if (item.dataUrl) {
		item.status = 'ready';
		item.error = '';
		item.progress = 100;
		updateImagePreview();
		return;
	}
	prepareFeedbackImageItem(item);
}

function clearFeedbackImages() {
	feedbackImageQueue = [];
	feedbackImages = [];
	updateImagePreview();
}

function handleFeedbackImageFiles(fileList) {
	var files = Array.prototype.slice.call(fileList || []);
	var remaining = maxFeedbackImages - feedbackImageQueue.length;
	if (remaining <= 0) {
		showFeedbackNotice('warning', formatFeedbackImageLabel(feedbackImageLabels.tooMany, [maxFeedbackImages]));
		return;
	}

	if (files.length > remaining) {
		showFeedbackNotice('warning', formatFeedbackImageLabel(feedbackImageLabels.tooMany, [maxFeedbackImages]));
		files = files.slice(0, remaining);
	}

	for (var i = 0; i < files.length; i++) {
		var file = files[i];
		if (!file.type || !/^image\//i.test(file.type)) {
			showFeedbackNotice('warning', feedbackImageLabels.formatError);
			continue;
		}
		var item = {
			id: feedbackImageNextId++,
			file: file,
			name: file.name || '',
			status: 'compressing',
			progress: 0,
			error: '',
			dataUrl: '',
			previewUrl: '',
			url: ''
		};
		feedbackImageQueue.push(item);
		prepareFeedbackImageItem(item);
	}
	updateImagePreview();
}

var feedbackImageInput = document.getElementById('feedback-image-input');
if (feedbackImageInput) {
	feedbackImageInput.addEventListener('change', function(e) {
		handleFeedbackImageFiles(e.target.files);
		e.target.value = '';
	});
}

var feedbackImageClearBtn = document.getElementById('feedback-image-clear-btn');
if (feedbackImageClearBtn) {
	feedbackImageClearBtn.addEventListener('click', function() {
		clearFeedbackImages();
		showFeedbackNotice('success', feedbackImageLabels.clearImages);
	});
}

function initFeedbackDropUpload() {
	var upload = document.querySelector('.feedback-image-upload');
	if (!upload) return;
	var activateEvents = ['dragenter', 'dragover'];
	var deactivateEvents = ['dragleave', 'drop'];
	for (var i = 0; i < activateEvents.length; i++) {
		upload.addEventListener(activateEvents[i], function(e) {
			e.preventDefault();
			e.stopPropagation();
			upload.classList.add('dragover');
		});
	}
	for (var j = 0; j < deactivateEvents.length; j++) {
		upload.addEventListener(deactivateEvents[j], function(e) {
			e.preventDefault();
			e.stopPropagation();
			upload.classList.remove('dragover');
		});
	}
	upload.addEventListener('drop', function(e) {
		handleFeedbackImageFiles(e.dataTransfer.files);
	});
	updateImagePreview();
}

function updateFeedbackContentCount() {
	let content = document.getElementById('feedback-content');
	let counter = document.getElementById('content-count');
	let wrapper = counter ? counter.parentElement : null;
	if (!content || !counter) return;
	let length = content.value.length;
	counter.textContent = length;
	if (wrapper) {
		wrapper.classList.toggle('warning', length >= 1600 && length < 1900);
		wrapper.classList.toggle('danger', length >= 1900);
	}
}

function readFeedbackDraft() {
	try {
		return JSON.parse(localStorage.getItem(feedbackDraftKey) || '{}');
	} catch (e) {
		return {};
	}
}

function saveFeedbackDraft() {
	let form = document.getElementById('feedback-form');
	if (!form) return;
	let nameInput = document.getElementById('feedback-name');
	let emailInput = document.getElementById('feedback-email');
	let urlInput = document.getElementById('feedback-url');
	let titleInput = document.getElementById('feedback-title');
	let contentInput = document.getElementById('feedback-content');
	let typeInput = form.querySelector('input[name="feedback_type"]:checked');
	let publicInput = document.getElementById('feedback-public');
	let data = {
		name: nameInput ? nameInput.value : '',
		email: emailInput ? emailInput.value : '',
		url: urlInput ? urlInput.value : '',
		title: titleInput ? titleInput.value : '',
		content: contentInput ? contentInput.value : '',
		type: typeInput ? typeInput.value : 'suggestion',
		isPublic: publicInput ? publicInput.checked : false
	};
	if (!data.title && !data.content && !data.url) {
		try { localStorage.removeItem(feedbackDraftKey); } catch (e) {}
		return;
	}
	try {
		localStorage.setItem(feedbackDraftKey, JSON.stringify(data));
	} catch (e) {
		return;
	}
	let state = document.getElementById('feedback-draft-state');
	if (state) {
		state.classList.add('show');
		clearTimeout(state._timer);
		state._timer = setTimeout(function() { state.classList.remove('show'); }, 1600);
	}
}

function restoreFeedbackDraft() {
	let form = document.getElementById('feedback-form');
	if (!form) return;
	let data = readFeedbackDraft();
	if (!data || Object.keys(data).length === 0) return;
	let nameInput = document.getElementById('feedback-name');
	let emailInput = document.getElementById('feedback-email');
	let urlInput = document.getElementById('feedback-url');
	let titleInput = document.getElementById('feedback-title');
	let contentInput = document.getElementById('feedback-content');
	if (data.name && nameInput && !nameInput.value) nameInput.value = data.name;
	if (data.email && emailInput && !emailInput.value) emailInput.value = data.email;
	if (data.url && urlInput && !urlInput.value) urlInput.value = data.url;
	if (data.title && titleInput) titleInput.value = data.title;
	if (data.content && contentInput) contentInput.value = data.content;
	if (data.type) {
		let typeInput = form.querySelector('input[name="feedback_type"][value="' + data.type + '"]');
		if (typeInput) typeInput.checked = true;
	}
	if (document.getElementById('feedback-public')) document.getElementById('feedback-public').checked = !!data.isPublic;
	updateFeedbackContentCount();
}

function clearFeedbackDraft(resetForm) {
	try { localStorage.removeItem(feedbackDraftKey); } catch (e) {}
	if (resetForm) {
		let form = document.getElementById('feedback-form');
		if (form) form.reset();
		clearFeedbackImages();
		updateFeedbackContentCount();
		let result = document.getElementById('feedback-submit-result');
		if (result) result.hidden = true;
	}
	let state = document.getElementById('feedback-draft-state');
	if (state) state.classList.remove('show');
}

function initFeedbackDraft() {
	let form = document.getElementById('feedback-form');
	if (!form) return;
	restoreFeedbackDraft();
	function syncTypeActiveState() {
		form.querySelectorAll('.feedback-type-option').forEach(function(option) {
			let input = option.querySelector('input[type="radio"]');
			option.classList.toggle('is-active', !!input && input.checked);
		});
	}
	syncTypeActiveState();
	let timer = null;
	form.addEventListener('input', function() {
		updateFeedbackContentCount();
		clearTimeout(timer);
		timer = setTimeout(saveFeedbackDraft, 350);
	});
	form.addEventListener('change', function() {
		syncTypeActiveState();
		clearTimeout(timer);
		timer = setTimeout(saveFeedbackDraft, 120);
	});
	let clearDraftBtn = document.getElementById('feedback-clear-draft-btn');
	if (clearDraftBtn) {
		clearDraftBtn.addEventListener('click', function() {
			clearFeedbackDraft(true);
			showFeedbackNotice('success', '<?php _e('已清空', 'argon'); ?>');
		});
	}
}

function submitFeedbackForm() {
	let form = document.getElementById('feedback-form');
	let btn = document.getElementById('feedback-submit-btn');
	let originalText = btn.innerHTML;
	let resultBox = document.getElementById('feedback-submit-result');
	let resultMessage = document.getElementById('feedback-submit-result-message');
	let viewLink = document.getElementById('feedback-view-link');
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?php _e('提交中...', 'argon'); ?>';
	btn.disabled = true;
	if (resultBox) resultBox.hidden = true;

	uploadFeedbackQueuedImages()
		.then(function(uploadedImages) {
			feedbackImages = uploadedImages;
			btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?php _e('正在提交...', 'argon'); ?>';
			let formData = new FormData(form);
			formData.append('feedback_action', 'submit');
			feedbackImages.forEach(function(img, index) {
				formData.append('feedback_images[' + index + ']', img);
			});
			return fetch(feedbackAjaxUrl, { method: 'POST', body: formData });
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			btn.innerHTML = originalText;
			btn.disabled = false;
			if (res.success) {
				showFeedbackNotice('success', res.message);
				form.reset();
				document.getElementById('content-count').textContent = '0';
				clearFeedbackImages();
				clearFeedbackDraft(false);
				if (resultBox && resultMessage) {
					resultMessage.textContent = res.is_public
						? '<?php _e('这条反馈已公开，刷新页面后也会出现在公开反馈列表中。', 'argon'); ?>'
						: '<?php _e('这条反馈为私密内容，请保留专属链接以便查看后续回复。', 'argon'); ?>';
					if (viewLink && res.view_url) {
						viewLink.href = res.view_url;
						viewLink.style.display = '';
					} else if (viewLink) {
						viewLink.style.display = 'none';
					}
					resultBox.hidden = false;
				}
				<?php if ($captcha_enabled && $captcha_type === 'geetest') : ?>
				feedbackGeetestValidated = false;
				if (feedbackGeetestCaptcha) feedbackGeetestCaptcha.reset();
				<?php endif; ?>
				<?php if ($captcha_enabled && $captcha_type !== 'geetest') : ?>
				refreshFeedbackCaptcha();
				<?php endif; ?>
			} else {
				showFeedbackNotice('error', res.message || '<?php _e('提交失败，请稍后重试', 'argon'); ?>');
				<?php if ($captcha_enabled) : ?>
				if (feedbackCaptchaType === 'math') {
					refreshFeedbackCaptcha();
				} else if (typeof feedbackGeetestCaptcha !== 'undefined' && feedbackGeetestCaptcha) {
					feedbackGeetestValidated = false;
					feedbackGeetestCaptcha.reset();
				}
				<?php endif; ?>
			}
		})
		.catch(function(err) {
			btn.innerHTML = originalText;
			btn.disabled = false;
			showFeedbackNotice('error', err && err.message ? err.message : '<?php _e('提交失败，请稍后重试', 'argon'); ?>');
		});
}

var feedbackForm = document.getElementById('feedback-form');
if (feedbackForm) {
	feedbackForm.addEventListener('submit', function(e) {
		e.preventDefault();
		
		if (getFeedbackImageProcessingCount() > 0) {
			showFeedbackNotice('warning', feedbackImageLabels.waitProcessing);
			return;
		}
		if (getFeedbackImageErrorCount() > 0) {
			showFeedbackNotice('warning', feedbackImageLabels.fixImages);
			return;
		}
		
		let name = document.getElementById('feedback-name').value.trim();
		let email = document.getElementById('feedback-email').value.trim();
		let content = document.getElementById('feedback-content').value.trim();
		let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		
		if (!name || !email || !content) {
			showFeedbackNotice('warning', '<?php _e('请填写必填项', 'argon'); ?>');
			return;
		}
		if (!emailPattern.test(email)) {
			showFeedbackNotice('warning', '<?php _e('邮箱格式错误', 'argon'); ?>');
			return;
		}
		
		<?php if ($captcha_enabled && $captcha_type === 'geetest') : ?>
		if (!feedbackGeetestValidated) {
			if (feedbackGeetestCaptcha) {
				feedbackGeetestCaptcha.showCaptcha();
			} else {
				showFeedbackNotice('error', '<?php _e('验证码加载中，请稍后重试', 'argon'); ?>');
			}
			return;
		}
		<?php endif; ?>
		
		submitFeedbackForm();
	});
}

var feedbackCopyLink = document.getElementById('feedback-copy-link');
if (feedbackCopyLink) {
	feedbackCopyLink.addEventListener('click', function() {
		let viewLink = document.getElementById('feedback-view-link');
		let link = viewLink ? viewLink.href : '';
		if (!link || link === '#') return;
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(link).then(function() {
				showFeedbackNotice('success', '<?php _e('链接已复制', 'argon'); ?>');
			});
			return;
		}
		let input = document.createElement('input');
		input.value = link;
		document.body.appendChild(input);
		input.select();
		document.execCommand('copy');
		document.body.removeChild(input);
		showFeedbackNotice('success', '<?php _e('链接已复制', 'argon'); ?>');
	});
}

initFeedbackDraft();
initFeedbackDropUpload();
<?php endif; ?>

initFeedbackListFilters();
initFeedbackImageViewer();

<?php if ($is_admin) : ?>
var typeLabels = <?php echo json_encode($type_labels); ?>;
var statusLabels = <?php echo json_encode($status_labels); ?>;

function openFeedbackDetail(id) {
	currentFeedbackId = id;
	let formData = new FormData();
	formData.append('feedback_action', 'get');
	formData.append('feedback_nonce', feedbackNonce);
	formData.append('id', id);
	
	fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success) {
				renderFeedbackDetail(res.data);
				document.getElementById('feedback-detail-modal').classList.add('show');
			} else {
				showFeedbackNotice('error', res.message || '<?php _e('反馈不存在', 'argon'); ?>');
			}
		})
		.catch(function() {
			showFeedbackNotice('error', '<?php _e('加载失败，请稍后重试', 'argon'); ?>');
		});
}

function renderFeedbackDetail(data) {
	let html = '';
	let isArchived = data.status === 'resolved' || data.status === 'closed';
	
	// 基本信息
	html += '<div class="feedback-detail-section">';
	html += '<div class="feedback-item-badges" style="margin-bottom:12px;">';
	html += '<span class="feedback-badge flinks-badge type-' + data.type + '">' + (typeLabels[data.type] || data.type) + '</span>';
	html += '<span class="feedback-badge flinks-badge status-' + data.status + '">' + (statusLabels[data.status] || data.status) + '</span>';
	if (data.is_public) html += '<span class="feedback-badge flinks-badge" style="background:#d1fae5;color:#059669;"><?php _e('公开', 'argon'); ?></span>';
	else html += '<span class="feedback-badge flinks-badge" style="background:#e5e7eb;color:#6b7280;"><?php _e('私密', 'argon'); ?></span>';
	if (isArchived) html += '<span class="feedback-badge flinks-badge" style="background:#fef3c7;color:#d97706;"><?php _e('已存档', 'argon'); ?></span>';
	html += '</div>';
	html += '</div>';
	
	// 标题
	if (data.title) {
		html += '<div class="feedback-detail-section">';
		html += '<div class="feedback-detail-label"><?php _e('标题', 'argon'); ?></div>';
		html += '<div class="feedback-detail-value">' + escapeHtml(data.title) + '</div>';
		html += '</div>';
	}
	
	// 内容
	html += '<div class="feedback-detail-section">';
	html += '<div class="feedback-detail-label"><?php _e('反馈内容', 'argon'); ?></div>';
	html += '<div class="feedback-detail-value content">' + escapeHtml(data.content) + '</div>';
	html += '</div>';
	
	// 图片
	if (data.images && data.images.length > 0) {
		html += '<div class="feedback-detail-section">';
		html += '<div class="feedback-detail-label"><?php _e('附件图片', 'argon'); ?> (' + data.images.length + ')</div>';
		html += '<div class="feedback-images-display">';
		data.images.forEach(function(img, imageIndex) {
			html += '<img src="' + escapeHtml(img) + '" alt="<?php echo esc_js(__('反馈附件图片', 'argon')); ?> ' + (imageIndex + 1) + '" data-feedback-preview tabindex="0" role="button">';
		});
		html += '</div>';
		html += '</div>';
	}
	
	// 用户信息
	html += '<div class="feedback-detail-section">';
	html += '<div class="feedback-detail-label"><?php _e('提交者信息', 'argon'); ?></div>';
	html += '<div class="feedback-detail-meta">';
	html += '<div class="feedback-detail-meta-item"><span class="label"><?php _e('昵称', 'argon'); ?>: </span><span class="value">' + escapeHtml(data.name) + '</span></div>';
	html += '<div class="feedback-detail-meta-item"><span class="label"><?php _e('邮箱', 'argon'); ?>: </span><span class="value">' + escapeHtml(data.email) + '</span></div>';
	html += '<div class="feedback-detail-meta-item"><span class="label">IP: </span><span class="value">' + escapeHtml(data.ip || '-') + '</span></div>';
	html += '<div class="feedback-detail-meta-item"><span class="label"><?php _e('时间', 'argon'); ?>: </span><span class="value">' + formatTime(data.created_at) + '</span></div>';
	if (data.url) html += '<div class="feedback-detail-meta-item" style="grid-column:span 2;"><span class="label"><?php _e('网站', 'argon'); ?>: </span><span class="value"><a href="' + escapeHtml(data.url) + '" target="_blank">' + escapeHtml(data.url) + '</a></span></div>';
	html += '</div>';
	html += '</div>';
	
	// 显示已有留言
	let replies = data.replies || [];
	if (replies.length === 0 && data.reply) {
		replies = [{content: data.reply, time: data.reply_at || Date.now()/1000, is_private: false}];
	}
	if (replies.length > 0) {
		html += '<div class="feedback-detail-section">';
		html += '<div class="feedback-detail-label"><?php _e('留言记录', 'argon'); ?> (' + replies.length + ')</div>';
		html += '<div style="max-height:300px;overflow-y:auto;border:1px solid var(--color-border);border-radius:calc(var(--card-radius) * 0.6);padding:12px;background:var(--color-background);">';
		for (let i = 0; i < replies.length; i++) {
			let r = replies[i];
			let privateLabel = r.is_private ? ' <span style="color:#f5365c;font-size:11px;"><i class="fa fa-lock"></i> <?php _e('私密', 'argon'); ?></span>' : '';
			html += '<div style="padding:10px;background:var(--color-foreground);border-radius:calc(var(--card-radius) * 0.5);margin-bottom:8px;border-left:3px solid ' + (r.is_private ? '#f5365c' : 'var(--themecolor)') + ';">';
			html += '<div style="font-size:12px;color:#666;margin-bottom:6px;"><i class="fa fa-reply"></i> ' + formatTime(r.time) + privateLabel + '</div>';
			html += '<div style="font-size:14px;color:var(--color-text-deeper);white-space:pre-wrap;">' + escapeHtml(r.content) + '</div>';
			html += '</div>';
		}
		html += '</div>';
		html += '</div>';
	}
	
	// 新增留言区域
	html += '<div class="feedback-detail-section">';
	if (isArchived) {
		html += '<div class="feedback-detail-label"><?php _e('追加留言', 'argon'); ?> <span style="color:#d97706;font-size:12px;">(<?php _e('已完结，仅可追加留言', 'argon'); ?>)</span></div>';
	} else {
		html += '<div class="feedback-detail-label"><?php _e('新增留言', 'argon'); ?></div>';
	}
	html += '<textarea class="feedback-reply-input" id="detail-reply-content" placeholder="' + (isArchived ? '<?php _e('输入追加留言...', 'argon'); ?>' : '<?php _e('输入新留言...', 'argon'); ?>') + '"></textarea>';
	html += '<div style="margin-top:8px;"><label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;"><input type="checkbox" id="detail-reply-private" style="width:16px;height:16px;"> <?php _e('设为私密留言（用户不可见）', 'argon'); ?></label></div>';
	html += '</div>';
	
	// 操作按钮
	html += '<div class="feedback-detail-section" style="display:flex;gap:8px;flex-wrap:wrap;">';
	html += '<button type="button" class="btn btn-sm btn-secondary" onclick="togglePublicFromDetail()">';
	html += data.is_public ? '<?php _e('设为私密', 'argon'); ?>' : '<?php _e('设为公开', 'argon'); ?>';
	html += '</button>';
	html += '<button type="button" class="btn btn-sm btn-secondary" onclick="deleteFromDetail()"><?php _e('删除', 'argon'); ?></button>';
	html += '</div>';
	
	document.getElementById('feedback-detail-content').innerHTML = html;
}

function escapeHtml(text) {
	if (!text) return '';
	let div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

function formatTime(timestamp) {
	let d = new Date(timestamp * 1000);
	return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}

function closeFeedbackDetail() {
	document.getElementById('feedback-detail-modal').classList.remove('show');
	currentFeedbackId = null;
}

var feedbackDetailModal = document.getElementById('feedback-detail-modal');
if (feedbackDetailModal) {
	feedbackDetailModal.addEventListener('click', function(event) {
		if (event.target === this && window.ArgonMotion.isOutsideClick(event, this.querySelector('.feedback-modal'))) {
			closeFeedbackDetail();
		}
	});
}

function setProcessing(statusButton) {
	if (!currentFeedbackId) return;
	let reply = document.getElementById('detail-reply-content').value;
	let privateInput = document.getElementById('detail-reply-private');
	let isPrivate = privateInput ? privateInput.checked : false;
	if (statusButton) statusButton.disabled = true;
	
	// 先保存回复（如果有）
	let formData = new FormData();
	formData.append('feedback_action', 'reply');
	formData.append('feedback_nonce', feedbackNonce);
	formData.append('id', currentFeedbackId);
	formData.append('reply', reply);
	if (isPrivate) formData.append('is_private', '1');
	
	fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (!res.success) {
				throw new Error(res.message || '<?php _e('保存留言失败', 'argon'); ?>');
			}
			// 设置状态为进行中
			let statusData = new FormData();
			statusData.append('feedback_action', 'update_status');
			statusData.append('feedback_nonce', feedbackNonce);
			statusData.append('id', currentFeedbackId);
			statusData.append('status', 'processing');
			return fetch(feedbackAjaxUrl, { method: 'POST', body: statusData });
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (!res.success) {
				throw new Error(res.message || '<?php _e('状态更新失败', 'argon'); ?>');
			}
			closeFeedbackDetail();
			location.reload();
		})
		.catch(function(err) {
			if (statusButton) statusButton.disabled = false;
			showFeedbackNotice('error', err.message || '<?php _e('操作失败，请稍后重试', 'argon'); ?>');
		});
}

function submitReplyAndResolve(actionButton) {
	if (!currentFeedbackId) return;
	let reply = document.getElementById('detail-reply-content').value;
	let privateInput = document.getElementById('detail-reply-private');
	let isPrivate = privateInput ? privateInput.checked : false;
	if (actionButton) actionButton.disabled = true;
	
	let formData = new FormData();
	formData.append('feedback_action', 'reply_and_resolve');
	formData.append('feedback_nonce', feedbackNonce);
	formData.append('id', currentFeedbackId);
	formData.append('reply', reply);
	if (isPrivate) formData.append('is_private', '1');
	
	fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success) {
				closeFeedbackDetail();
				location.reload();
			} else {
				throw new Error(res.message || '<?php _e('操作失败，请稍后重试', 'argon'); ?>');
			}
		})
		.catch(function(err) {
			if (actionButton) actionButton.disabled = false;
			showFeedbackNotice('error', err.message || '<?php _e('操作失败，请稍后重试', 'argon'); ?>');
		});
}

function togglePublicFromDetail() {
	if (!currentFeedbackId) return;
	let formData = new FormData();
	formData.append('feedback_action', 'toggle_public');
	formData.append('feedback_nonce', feedbackNonce);
	formData.append('id', currentFeedbackId);
	
	fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success) {
				closeFeedbackDetail();
				location.reload();
			}
		});
}

function deleteFromDetail() {
	if (!currentFeedbackId) return;
	if (!confirm('<?php _e('确定要删除这条反馈吗？', 'argon'); ?>')) return;
	
	let formData = new FormData();
	formData.append('feedback_action', 'delete');
	formData.append('feedback_nonce', feedbackNonce);
	formData.append('id', currentFeedbackId);
	
	fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success) {
				closeFeedbackDetail();
				location.reload();
			}
		});
}
<?php endif; ?>
</script>

<?php
argon_special_page_surface_close();
get_footer();
?>
