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
	
	$image_data = $_POST['image_data'] ?? '';
	if (empty($image_data)) {
		echo json_encode(['success' => false, 'message' => '图片数据为空']);
		exit;
	}
	
	$saved_url = argon_save_feedback_image($image_data);
	if ($saved_url) {
		// 更新频率限制计数
		$limit_period = intval(get_option('argon_feedback_upload_period', 3600));
		if ($upload_count === false) {
			set_transient($rate_limit_key, 1, $limit_period);
		} else {
			set_transient($rate_limit_key, $upload_count + 1, $limit_period);
		}
		
		echo json_encode(['success' => true, 'url' => $saved_url]);
	} else {
		echo json_encode(['success' => false, 'message' => '图片保存失败']);
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
		$upload_dir = wp_upload_dir();
		$feedback_url_prefix = $upload_dir['baseurl'] . '/feedback-images/';
		foreach ($data['feedback_images'] as $image_url) {
			if (!empty($image_url) && count($images) < 10) {
				// 验证是否是本站上传的图片 URL（已经通过 upload_image 接口上传）
				if (strpos($image_url, $feedback_url_prefix) === 0) {
					$images[] = $image_url;
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

function argon_save_feedback_image($base64_data) {
	// 验证 base64 数据
	if (strpos($base64_data, 'data:image/') !== 0) {
		return false;
	}
	
	// 解析 base64
	$data_parts = explode(',', $base64_data);
	if (count($data_parts) !== 2) {
		return false;
	}
	
	$image_data = base64_decode($data_parts[1]);
	if ($image_data === false) {
		return false;
	}
	
	// 检查大小（250KB）
	if (strlen($image_data) > 250 * 1024) {
		return false;
	}
	
	// 获取 MIME 类型
	preg_match('/data:image\/(\w+);/', $data_parts[0], $matches);
	$ext = $matches[1] ?? 'jpg';
	if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
		$ext = 'jpg';
	}
	
	// 生成文件名
	$upload_dir = wp_upload_dir();
	$feedback_dir = $upload_dir['basedir'] . '/feedback-images';
	if (!file_exists($feedback_dir)) {
		wp_mkdir_p($feedback_dir);
	}
	
	$filename = 'fb_' . uniqid() . '_' . time() . '.' . $ext;
	$filepath = $feedback_dir . '/' . $filename;
	
	// 保存文件
	if (file_put_contents($filepath, $image_data) === false) {
		return false;
	}
	
	// 返回相对 URL
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

get_header();

$type_labels = ['bug' => __('Bug', 'argon'), 'suggestion' => __('建议', 'argon'), 'question' => __('问题', 'argon'), 'other' => __('其他', 'argon')];
$status_labels = ['pending' => __('未查看', 'argon'), 'viewed' => __('已查看', 'argon'), 'processing' => __('进行中', 'argon'), 'resolved' => __('已完成', 'argon'), 'closed' => __('已关闭', 'argon')];
?>

<div class="page-information-card-container"></div>
<?php get_sidebar(); ?>

<div id="primary" class="content-area">


<?php
// 获取全局毛玻璃设置
$feedback_card_blur = intval(get_option('argon_card_blur', '20'));
$feedback_card_saturate = intval(get_option('argon_card_saturate', '180'));
$feedback_card_opacity = get_option('argon_post_background_opacity', '0.7');
if ($feedback_card_opacity == '') {
	$feedback_card_opacity = '0.7';
}
?>

<style id="feedback-page-style">
/* 页面布局 */
@media screen and (min-width: 901px) {
	body.feedback-page #leftbar_part, body.feedback-page #leftbar { display: none !important; }
}
@media screen and (max-width: 900px) {
	body.feedback-page #leftbar {
		display: block; position: fixed; background: var(--color-foreground);
		top: 0; left: -300px; height: 100vh; width: 280px; padding: 0;
		overflow-y: auto; z-index: 1002;
		box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07) !important;
		transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
	}
	html.leftbar-opened body.feedback-page #leftbar { left: 0px; }
}
body.feedback-page #primary { width: 100% !important; max-width: 1040px !important; margin: 0 auto !important; float: none !important; }
body.feedback-page #content { margin-top: -50vh !important; }
body.feedback-page #main { max-width: 1040px !important; margin: 0 auto !important; }
body.feedback-page .site-footer { max-width: 1040px !important; margin: 0 auto !important; box-sizing: border-box !important; }

/* 头部卡片 */
.feedback-header-card { text-align: center; padding: 40px 24px 32px; background: transparent !important; box-shadow: none !important; border: none !important; }
.feedback-header-icon { width: 64px; height: 64px; margin: 0 auto 20px; background: var(--themecolor-gradient); border-radius: var(--card-radius); display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; box-shadow: 0 4px 12px rgba(var(--themecolor-rgbstr), 0.2); transition: transform var(--animation-fast) var(--ease-standard); }
.feedback-header-icon:hover { transform: translateY(-2px); }
.feedback-title { font-size: 24px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper); background: none !important; }
.feedback-title::before, .feedback-title::after { display: none !important; }
.feedback-subtitle { font-size: 14px; color: #888; margin: 0 0 20px; line-height: 1.6; }
.feedback-stats { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
.feedback-stat { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(var(--themecolor-rgbstr), 0.08); border-radius: var(--card-radius-sm); font-size: 13px; font-weight: 500; color: var(--themecolor); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-stat:hover { background: rgba(var(--themecolor-rgbstr), 0.12); }
html.darkmode .feedback-subtitle { color: #aaa; }

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
.feedback-image-upload { display: flex; flex-wrap: wrap; gap: 12px; padding: 12px; border: 1.5px dashed var(--color-border); border-radius: var(--card-radius-sm); background: rgba(var(--themecolor-rgbstr), 0.025); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-image-upload.dragover { border-color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.08); }
.feedback-image-preview { display: contents; }
.feedback-image-item { position: relative; width: 100px; height: 100px; border-radius: var(--card-radius-sm); overflow: hidden; border: 2px solid var(--color-border); transition: all var(--animation-fast) var(--ease-standard); }
.feedback-image-item:hover { border-color: var(--themecolor); transform: scale(1.05); }
.feedback-image-item img { width: 100%; height: 100%; object-fit: cover; }
.feedback-image-item .remove-btn { position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; background: rgba(245,54,92,0.9); color: #fff; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: all var(--animation-fast) var(--ease-standard); }
.feedback-image-item .remove-btn:hover { background: #f5365c; transform: scale(1.1); }
.feedback-image-add-btn { width: 100px; height: 100px; border: 2px dashed var(--color-border); border-radius: var(--card-radius-sm); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all var(--animation-fast) var(--ease-standard); color: #666; font-size: 13px; }
.feedback-image-add-btn:hover { border-color: var(--themecolor); color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.05); }
.feedback-image-add-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
.feedback-image-add-btn i { font-size: 24px; margin-bottom: 6px; }
.feedback-image-item.uploading { background: var(--color-border-on-foreground); display: flex; align-items: center; justify-content: center; }
.uploading-spinner { font-size: 24px; color: var(--themecolor); }
.feedback-images-display { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.feedback-images-display img { max-width: 120px; max-height: 120px; border-radius: var(--card-radius-sm); cursor: pointer; transition: all var(--animation-fast) var(--ease-standard); border: 2px solid var(--color-border); }
.feedback-images-display img:hover { transform: scale(1.05); border-color: var(--themecolor); }
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
.feedback-list-tools { display: grid; grid-template-columns: minmax(220px, 1fr) 150px 150px auto; gap: 10px; margin-bottom: 10px; align-items: center; }
.feedback-search-box { position: relative; }
.feedback-search-box i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #888; font-size: 13px; pointer-events: none; }
.feedback-search-box input,
.feedback-list-tools select { width: 100%; height: 42px; border: 1.5px solid var(--color-border); border-radius: var(--card-radius-sm); background: var(--color-widgets); color: var(--color-text-deeper); font-size: 13px; box-sizing: border-box; transition: all var(--animation-fast) var(--ease-standard); }
.feedback-search-box input { padding: 0 12px 0 36px; }
.feedback-list-tools select { padding: 0 10px; }
.feedback-search-box input:focus,
.feedback-list-tools select:focus { border-color: var(--themecolor); outline: none; box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.09); }
.feedback-list-clear { height: 42px; min-width: 42px; padding: 0 12px !important; border-radius: var(--card-radius-sm) !important; }
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
.feedback-empty { padding: 60px 24px; text-align: center; }
.feedback-empty i { font-size: 56px; color: var(--color-border); margin-bottom: 20px; display: block; opacity: 0.5; }
.feedback-empty p { font-size: 15px; color: #666; margin: 0; line-height: 1.6; }
.feedback-empty-filter { display: none; padding: 36px 16px; text-align: center; color: #666; border: 1px dashed var(--color-border); border-radius: var(--card-radius-sm); margin-top: 12px; }
.feedback-empty-filter.show { display: block; }
.feedback-empty-filter i { display: block; margin-bottom: 10px; font-size: 28px; color: var(--themecolor); opacity: 0.72; }
.feedback-admin-card { padding: 20px 24px; }
.feedback-admin-title { font-size: 16px; font-weight: 600; color: var(--themecolor); margin: 0 0 16px; display: flex; align-items: center; gap: 10px; }

/* 弹窗样式 */
.feedback-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 9998; display: none; align-items: center; justify-content: center; animation: fadeIn var(--animation-fast) var(--ease-standard); }
.feedback-modal-overlay.show { display: flex; }
.feedback-modal { background: var(--color-foreground); border-radius: var(--card-radius); padding: 0; width: 90%; max-width: 600px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.3), 0 8px 24px rgba(0,0,0,0.2); animation: modalSlideUp var(--animation-normal) var(--ease-emphasized-decelerate); }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes modalSlideUp { from { opacity: 0; transform: translateY(40px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
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
	.feedback-images-display img { max-width: 100px; max-height: 100px; }
}
@media (max-width: 420px) {
	.feedback-form-card { padding: 20px 18px; }
	.feedback-type-selector { grid-template-columns: 1fr; }
	.feedback-header-card { padding-left: 12px; padding-right: 12px; }
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
html.darkmode .feedback-subtitle { color: #aaa; }
html.darkmode .feedback-admin-stat .stat-label { color: #aaa; }
html.darkmode .feedback-form .char-count { color: #aaa; }
html.darkmode .feedback-public-option .hint { color: #aaa; }
html.darkmode .feedback-item-time { color: #aaa; }
html.darkmode .feedback-item-content { color: #bbb; }
html.darkmode .feedback-item-reply-content { color: #bbb; }
html.darkmode .feedback-empty p { color: #aaa; }
html.darkmode .feedback-detail-label { color: #aaa; }
html.darkmode .feedback-detail-meta-item .label { color: #aaa; }
html.darkmode .feedback-modal-close { color: #aaa; }
html.darkmode .feedback-image-item { border-color: #555; }
html.darkmode .feedback-image-add-btn { border-color: #555; color: #aaa; }
html.darkmode .feedback-images-display img { border-color: #555; }
</style>

<script data-pjax>
(function() {
	document.body.classList.add('feedback-page');
	if (typeof jQuery !== 'undefined') {
		let $ = jQuery;
		$(document).off('pjax:start.feedback');
		$(document).on('pjax:start.feedback', function() {
			document.body.classList.remove('feedback-page');
			let s = document.getElementById('feedback-page-style');
			if (s) s.remove();
			$(document).off('pjax:start.feedback');
		});
	}
})();
</script>

<main id="main" class="site-main" role="main">

<?php if ($is_auth_view && $single_feedback) : ?>
<!-- 授权链接查看单个反馈 -->
<div class="feedback-header-card" style="margin-bottom: 16px;">
	<div class="feedback-header-icon"><i class="fa fa-commenting"></i></div>
	<h1 class="feedback-title"><?php _e('反馈详情', 'argon'); ?></h1>
	<p class="feedback-subtitle"><?php _e('您可以在此查看反馈的详细信息和回复', 'argon'); ?></p>
</div>

<article class="post card shadow-sm bg-white border-0 feedback-auth-view" style="margin-bottom: 16px;">
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
					<span class="feedback-badge type-<?php echo $type; ?>"><?php echo $type_labels[$type] ?? $type; ?></span>
					<span class="feedback-badge status-<?php echo $status; ?>"><?php echo $status_labels[$status] ?? $status; ?></span>
				</div>
			</div>
		</div>
		<?php if (!empty($single_feedback['title'])) : ?>
		<div class="feedback-item-title"><?php echo esc_html($single_feedback['title']); ?></div>
		<?php endif; ?>
		<div class="feedback-item-content"><?php echo esc_html($single_feedback['content']); ?></div>
		<?php if (!empty($single_feedback['images'])) : ?>
		<div class="feedback-images-display">
			<?php foreach ($single_feedback['images'] as $img_url) : ?>
			<img src="<?php echo esc_url($img_url); ?>" alt="" onclick="window.open(this.src)">
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
<div class="feedback-header-card" style="margin-bottom: 16px;">
	<div class="feedback-header-icon"><i class="fa fa-commenting"></i></div>
	<h1 class="feedback-title"><?php _e('问题反馈', 'argon'); ?></h1>
	<p class="feedback-subtitle"><?php _e('有任何建议或问题？欢迎告诉我！', 'argon'); ?></p>
	<div class="feedback-stats">
		<div class="feedback-stat"><i class="fa fa-comments"></i><span><?php echo count($public_feedbacks); ?> <?php _e('条公开反馈', 'argon'); ?></span></div>
		<?php if ($is_admin && $stats['pending'] > 0) : ?>
		<div class="feedback-stat" style="background:rgba(245,54,92,0.1);color:#f5365c;"><i class="fa fa-clock-o"></i><span><?php echo $stats['pending']; ?> <?php _e('条待处理', 'argon'); ?></span></div>
		<?php endif; ?>
	</div>
</div>

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
<article class="post card shadow-sm bg-white border-0 feedback-form-card" style="margin-bottom: 0;">
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
				<label><?php _e('上传图片', 'argon'); ?> <span class="feedback-field-hint"><?php _e('（选填，最多10张，单张最大250KB）', 'argon'); ?></span></label>
				<div class="feedback-image-upload">
					<div class="feedback-image-preview" id="feedback-image-preview"></div>
					<label class="feedback-image-add-btn" id="feedback-image-add-btn" for="feedback-image-input">
						<i class="fa fa-plus"></i>
						<span><?php _e('添加图片', 'argon'); ?></span>
					</label>
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
<article class="post card shadow-sm bg-white border-0 feedback-form-card" style="margin-bottom: 16px;">
	<div class="feedback-card-heading">
		<h2 class="feedback-list-title"><i class="fa fa-comments-o"></i><?php _e('公开反馈', 'argon'); ?><span class="count" id="feedback-visible-count"><?php echo count($public_feedbacks); ?></span></h2>
	</div>
	<?php if (!empty($public_feedbacks)) : ?>
		<div class="feedback-list-tools" id="feedback-list-tools">
			<div class="feedback-search-box">
				<i class="fa fa-search"></i>
				<input type="search" id="feedback-search" placeholder="<?php esc_attr_e('搜索标题、内容或昵称', 'argon'); ?>" aria-label="<?php esc_attr_e('搜索公开反馈', 'argon'); ?>">
			</div>
			<select id="feedback-type-filter" aria-label="<?php esc_attr_e('筛选反馈类型', 'argon'); ?>">
				<option value="all"><?php _e('全部类型', 'argon'); ?></option>
				<option value="suggestion"><?php _e('建议', 'argon'); ?></option>
				<option value="bug"><?php _e('Bug', 'argon'); ?></option>
				<option value="question"><?php _e('问题', 'argon'); ?></option>
				<option value="other"><?php _e('其他', 'argon'); ?></option>
			</select>
			<select id="feedback-status-filter" aria-label="<?php esc_attr_e('筛选反馈状态', 'argon'); ?>">
				<option value="all"><?php _e('全部状态', 'argon'); ?></option>
				<option value="pending"><?php _e('未查看', 'argon'); ?></option>
				<option value="viewed"><?php _e('已查看', 'argon'); ?></option>
				<option value="processing"><?php _e('进行中', 'argon'); ?></option>
				<option value="resolved"><?php _e('已完成', 'argon'); ?></option>
				<option value="closed"><?php _e('已关闭', 'argon'); ?></option>
			</select>
			<button type="button" class="btn btn-secondary feedback-list-clear" id="feedback-filter-reset" aria-label="<?php esc_attr_e('重置筛选', 'argon'); ?>"><i class="fa fa-undo"></i></button>
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
						<span class="feedback-badge type-<?php echo $type; ?>"><?php echo $type_labels[$type] ?? $type; ?></span>
						<span class="feedback-badge status-<?php echo $status; ?>"><?php echo $status_labels[$status] ?? $status; ?></span>
					</div>
				</div>
			</div>
			<?php if (!empty($fb['title'])) : ?>
			<div class="feedback-item-title"><?php echo esc_html($fb['title']); ?></div>
			<?php endif; ?>
			<div class="feedback-item-content"><?php echo esc_html($fb['content']); ?></div>
			<?php if (!empty($fb['images'])) : ?>
			<div class="feedback-images-display">
				<?php foreach ($fb['images'] as $img_url) : ?>
				<img src="<?php echo esc_url($img_url); ?>" alt="" onclick="event.stopPropagation(); window.open(this.src)">
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
		<div class="feedback-empty">
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
<article class="post card shadow-sm bg-white border-0 feedback-admin-card" style="margin-bottom: 16px;">
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
					<span class="feedback-badge type-<?php echo $type; ?>"><?php echo $type_labels[$type] ?? $type; ?></span>
					<span class="feedback-badge status-<?php echo $status; ?>"><?php echo $status_labels[$status] ?? $status; ?></span>
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

<script data-pjax>
var feedbackNonce = '<?php echo wp_create_nonce('argon_feedback_manage'); ?>';
var feedbackUploadNonce = '<?php echo wp_create_nonce('argon_feedback_upload'); ?>';
var feedbackAjaxUrl = <?php echo wp_json_encode(esc_url_raw($_SERVER['REQUEST_URI'])); ?>;
var feedbackCaptchaType = '<?php echo esc_js($captcha_type); ?>';
var feedbackCaptchaEnabled = <?php echo $captcha_enabled ? 'true' : 'false'; ?>;
var currentFeedbackId = null;
var feedbackImages = []; // 存储已上传的图片 URL
var maxFeedbackImages = 10;
var maxImageSize = 250 * 1024; // 250KB
var maxImageDimension = 4096; // 最大分辨率
var uploadingCount = 0; // 正在上传的图片数量
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

function refreshFeedbackCaptcha() {
	fetch(getFeedbackActionUrl('refresh_captcha'))
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.captcha) {
				document.getElementById('feedback-captcha-text').textContent = res.captcha;
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
function compressAndUploadImage(file, callback) {
	let reader = new FileReader();
	reader.onload = function(e) {
		let img = new Image();
		img.onload = function() {
			// 检查分辨率
			if (img.width > maxImageDimension || img.height > maxImageDimension) {
				if (typeof iziToast !== 'undefined') {
					iziToast.error({ 
						title: '<?php _e('图片分辨率过大', 'argon'); ?>', 
						message: '<?php _e('图片分辨率不能超过 4096x4096，请使用较小的图片', 'argon'); ?>',
						position: 'topRight', 
						timeout: 5000 
					});
				} else {
					alert('<?php _e('图片分辨率不能超过 4096x4096', 'argon'); ?>');
				}
				callback(null);
				return;
			}
			
			let canvas = document.createElement('canvas');
			let ctx = canvas.getContext('2d');
			
			// 初始质量
			let quality = 0.9;
			let width = img.width;
			let height = img.height;
			
			// 如果图片太大，先缩小尺寸
			let maxDimension = 2048;
			if (width > maxDimension || height > maxDimension) {
				if (width > height) {
					height = (height / width) * maxDimension;
					width = maxDimension;
				} else {
					width = (width / height) * maxDimension;
					height = maxDimension;
				}
			}
			
			canvas.width = width;
			canvas.height = height;
			ctx.drawImage(img, 0, 0, width, height);
			
			// 尝试压缩
			let tryCompress = function(q) {
				let dataUrl = canvas.toDataURL('image/jpeg', q);
				let size = Math.round((dataUrl.length - 'data:image/jpeg;base64,'.length) * 0.75);
				
				if (size <= maxImageSize || q <= 0.1) {
					if (size > maxImageSize) {
						// 无法压缩到目标大小
						if (typeof iziToast !== 'undefined') {
							iziToast.error({ 
								title: '<?php _e('图片无法压缩', 'argon'); ?>', 
								message: '<?php _e('图片内容过于复杂，无法压缩到 250KB 以下，请使用更小的图片', 'argon'); ?>',
								position: 'topRight', 
								timeout: 5000 
							});
						} else {
							alert('<?php _e('图片无法压缩到 250KB 以下', 'argon'); ?>');
						}
						callback(null);
					} else {
						// 上传到服务器
						uploadingCount++;
						updateImagePreview();
						
						let formData = new FormData();
						formData.append('feedback_action', 'upload_image');
						formData.append('feedback_upload_nonce', feedbackUploadNonce);
						formData.append('image_data', dataUrl);
						
						fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
							.then(function(r) { return r.json(); })
							.then(function(res) {
								uploadingCount--;
								if (res.success) {
									callback(res.url);
								} else {
									if (typeof iziToast !== 'undefined') {
										iziToast.error({ 
											title: '<?php _e('上传失败', 'argon'); ?>', 
											message: res.message || '<?php _e('请稍后重试', 'argon'); ?>',
											position: 'topRight', 
											timeout: 5000 
										});
									} else {
										alert(res.message || '<?php _e('上传失败', 'argon'); ?>');
									}
									callback(null);
								}
								updateImagePreview();
							})
							.catch(function() {
								uploadingCount--;
								if (typeof iziToast !== 'undefined') {
									iziToast.error({ 
										title: '<?php _e('上传失败', 'argon'); ?>', 
										message: '<?php _e('网络错误，请稍后重试', 'argon'); ?>',
										position: 'topRight', 
										timeout: 5000 
									});
								} else {
									alert('<?php _e('上传失败', 'argon'); ?>');
								}
								callback(null);
								updateImagePreview();
							});
					}
				} else {
					// 继续降低质量
					tryCompress(q - 0.1);
				}
			};
			
			tryCompress(quality);
		};
		img.src = e.target.result;
	};
	reader.readAsDataURL(file);
}

function updateImagePreview() {
	let preview = document.getElementById('feedback-image-preview');
	let addBtn = document.getElementById('feedback-image-add-btn');
	
	preview.innerHTML = '';
	
	// 显示已上传的图片
	feedbackImages.forEach(function(img, index) {
		let item = document.createElement('div');
		item.className = 'feedback-image-item';
		item.innerHTML = '<img src="' + img + '" alt=""><button type="button" class="remove-btn" onclick="removeFeedbackImage(' + index + ')">&times;</button>';
		preview.appendChild(item);
	});
	
	// 显示上传中的占位符
	for (let i = 0; i < uploadingCount; i++) {
		let item = document.createElement('div');
		item.className = 'feedback-image-item uploading';
		item.innerHTML = '<div class="uploading-spinner"><i class="fa fa-spinner fa-spin"></i></div>';
		preview.appendChild(item);
	}
	
	if (feedbackImages.length + uploadingCount >= maxFeedbackImages) {
		addBtn.classList.add('disabled');
	} else {
		addBtn.classList.remove('disabled');
	}
}

function removeFeedbackImage(index) {
	feedbackImages.splice(index, 1);
	updateImagePreview();
}

function handleFeedbackImageFiles(fileList) {
	let files = Array.from(fileList || []);
	let remaining = maxFeedbackImages - feedbackImages.length - uploadingCount;
	if (remaining <= 0) {
		showFeedbackNotice('warning', '<?php _e('最多上传 10 张图片', 'argon'); ?>');
		return;
	}
	
	if (files.length > remaining) {
		showFeedbackNotice('warning', '<?php _e('最多上传 10 张图片', 'argon'); ?>');
		files = files.slice(0, remaining);
	}
	
	files.forEach(function(file) {
		if (!file.type.startsWith('image/')) {
			showFeedbackNotice('warning', '<?php _e('只能上传图片文件', 'argon'); ?>');
			return;
		}
		
		compressAndUploadImage(file, function(url) {
			if (url) {
				feedbackImages.push(url);
				updateImagePreview();
			}
		});
	});
}

var feedbackImageInput = document.getElementById('feedback-image-input');
if (feedbackImageInput) {
	feedbackImageInput.addEventListener('change', function(e) {
		handleFeedbackImageFiles(e.target.files);
		e.target.value = '';
	});
}

function initFeedbackDropUpload() {
	let upload = document.querySelector('.feedback-image-upload');
	if (!upload) return;
	['dragenter', 'dragover'].forEach(function(eventName) {
		upload.addEventListener(eventName, function(e) {
			e.preventDefault();
			e.stopPropagation();
			upload.classList.add('dragover');
		});
	});
	['dragleave', 'drop'].forEach(function(eventName) {
		upload.addEventListener(eventName, function(e) {
			e.preventDefault();
			e.stopPropagation();
			upload.classList.remove('dragover');
		});
	});
	upload.addEventListener('drop', function(e) {
		handleFeedbackImageFiles(e.dataTransfer.files);
	});
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
		feedbackImages = [];
		updateImagePreview();
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
	
	let formData = new FormData(form);
	formData.append('feedback_action', 'submit');
	
	// 添加已上传的图片 URL
	feedbackImages.forEach(function(img, index) {
		formData.append('feedback_images[' + index + ']', img);
	});
	
	fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
		.then(function(r) { return r.json(); })
		.then(function(res) {
			btn.innerHTML = originalText;
			btn.disabled = false;
			if (res.success) {
				showFeedbackNotice('success', res.message);
				form.reset();
				document.getElementById('content-count').textContent = '0';
				feedbackImages = [];
				updateImagePreview();
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
		.catch(function() {
			btn.innerHTML = originalText;
			btn.disabled = false;
			showFeedbackNotice('error', '<?php _e('提交失败，请稍后重试', 'argon'); ?>');
		});
}

var feedbackForm = document.getElementById('feedback-form');
if (feedbackForm) {
	feedbackForm.addEventListener('submit', function(e) {
		e.preventDefault();
		
		// 检查是否有图片正在上传
		if (uploadingCount > 0) {
			if (typeof iziToast !== 'undefined') {
				iziToast.warning({ title: '<?php _e('请等待图片上传完成', 'argon'); ?>', position: 'topRight', timeout: 3000 });
			} else {
				alert('<?php _e('请等待图片上传完成', 'argon'); ?>');
			}
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
	html += '<span class="feedback-badge type-' + data.type + '">' + (typeLabels[data.type] || data.type) + '</span>';
	html += '<span class="feedback-badge status-' + data.status + '">' + (statusLabels[data.status] || data.status) + '</span>';
	if (data.is_public) html += '<span class="feedback-badge" style="background:#d1fae5;color:#059669;"><?php _e('公开', 'argon'); ?></span>';
	else html += '<span class="feedback-badge" style="background:#e5e7eb;color:#6b7280;"><?php _e('私密', 'argon'); ?></span>';
	if (isArchived) html += '<span class="feedback-badge" style="background:#fef3c7;color:#d97706;"><?php _e('已存档', 'argon'); ?></span>';
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
		data.images.forEach(function(img) {
			html += '<img src="' + escapeHtml(img) + '" alt="" onclick="event.stopPropagation(); window.open(this.src)">';
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

<?php get_footer(); ?>
