<?php
/**
 * 问题反馈页面
 * @package Argon
 */
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) $wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
require_once($wp_load_path);

// 权限检查
$is_admin = current_user_can('manage_options');

// AJAX 处理反馈管理
if (isset($_POST['feedback_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => ''];
    $action = sanitize_text_field($_POST['feedback_action']);
    
    // 管理员操作
    if (in_array($action, ['delete', 'toggle_public', 'reply', 'update_status'])) {
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
            $response = argon_reply_feedback($id, $reply);
            break;
        case 'update_status':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
            $response = argon_update_feedback_status($id, $status);
            break;
    }
    echo json_encode($response);
    exit;
}

// ==================== 反馈数据操作函数 ====================

/**
 * 获取用户唯一标识
 */
function argon_get_feedback_user_identifier() {
    if (is_user_logged_in()) {
        return 'user_' . get_current_user_id();
    }
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip_list[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    return substr(hash('sha256', $ip . '|' . $user_agent), 0, 16);
}

/**
 * 获取所有反馈
 */
function argon_get_feedbacks($filter = 'all') {
    $feedbacks = get_option('argon_feedbacks', []);
    if (!is_array($feedbacks)) $feedbacks = [];
    
    // 按时间倒序
    usort($feedbacks, function($a, $b) {
        return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
    });
    
    if ($filter === 'public') {
        return array_filter($feedbacks, function($f) {
            return !empty($f['is_public']);
        });
    }
    return $feedbacks;
}

/**
 * 获取单个反馈
 */
function argon_get_feedback($id) {
    $feedbacks = argon_get_feedbacks();
    foreach ($feedbacks as $f) {
        if ($f['id'] === $id) return $f;
    }
    return null;
}


/**
 * 添加反馈
 */
function argon_add_feedback($data) {
    $feedbacks = get_option('argon_feedbacks', []);
    if (!is_array($feedbacks)) $feedbacks = [];
    
    $id = 'fb_' . uniqid();
    $feedback = [
        'id' => $id,
        'title' => sanitize_text_field($data['title'] ?? ''),
        'content' => sanitize_textarea_field($data['content'] ?? ''),
        'type' => sanitize_text_field($data['type'] ?? 'suggestion'),
        'name' => sanitize_text_field($data['name'] ?? ''),
        'email' => sanitize_email($data['email'] ?? ''),
        'url' => esc_url_raw($data['url'] ?? ''),
        'is_public' => !empty($data['is_public']),
        'status' => 'pending', // pending, processing, resolved, closed
        'user_identifier' => argon_get_feedback_user_identifier(),
        'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => time(),
        'reply' => '',
        'reply_at' => 0,
    ];
    
    $feedbacks[] = $feedback;
    update_option('argon_feedbacks', $feedbacks);
    
    return $id;
}

/**
 * 删除反馈
 */
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

/**
 * 切换公开状态
 */
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


/**
 * 回复反馈
 */
function argon_reply_feedback($id, $reply) {
    $feedbacks = get_option('argon_feedbacks', []);
    if (!is_array($feedbacks)) return ['success' => false];
    
    foreach ($feedbacks as &$f) {
        if ($f['id'] === $id) {
            $f['reply'] = $reply;
            $f['reply_at'] = time();
            if ($f['status'] === 'pending') {
                $f['status'] = 'processing';
            }
            update_option('argon_feedbacks', $feedbacks);
            
            // 发送邮件通知
            if (!empty($f['email']) && !empty($reply)) {
                $subject = sprintf('[%s] 您的反馈已收到回复', get_bloginfo('name'));
                $message = sprintf(
                    "您好 %s！\n\n您提交的反馈「%s」已收到回复：\n\n%s\n\n感谢您的反馈！\n\n%s",
                    $f['name'],
                    $f['title'],
                    $reply,
                    home_url()
                );
                wp_mail($f['email'], $subject, $message);
            }
            
            return ['success' => true];
        }
    }
    return ['success' => false];
}

/**
 * 更新反馈状态
 */
function argon_update_feedback_status($id, $status) {
    $valid_statuses = ['pending', 'processing', 'resolved', 'closed'];
    if (!in_array($status, $valid_statuses)) {
        return ['success' => false, 'message' => '无效状态'];
    }
    
    $feedbacks = get_option('argon_feedbacks', []);
    if (!is_array($feedbacks)) return ['success' => false];
    
    foreach ($feedbacks as &$f) {
        if ($f['id'] === $id) {
            $f['status'] = $status;
            update_option('argon_feedbacks', $feedbacks);
            return ['success' => true];
        }
    }
    return ['success' => false];
}

/**
 * 处理反馈提交
 */
function argon_handle_feedback_submit($data) {
    // 验证必填字段
    if (empty($data['feedback_email'])) {
        return ['success' => false, 'message' => __('请填写邮箱', 'argon')];
    }
    if (empty($data['feedback_name'])) {
        return ['success' => false, 'message' => __('请填写昵称', 'argon')];
    }
    if (empty($data['feedback_content'])) {
        return ['success' => false, 'message' => __('请填写反馈内容', 'argon')];
    }
    
    // 验证邮箱格式
    if (!is_email($data['feedback_email'])) {
        return ['success' => false, 'message' => __('邮箱格式错误', 'argon')];
    }
    
    // 验证码检查
    $captcha_enabled = get_option('argon_feedback_captcha', 'true') === 'true';
    if ($captcha_enabled && function_exists('check_comment_captcha')) {
        $captcha_input = $data['feedback_captcha'] ?? '';
        if (!check_comment_captcha($captcha_input)) {
            return ['success' => false, 'message' => __('验证码错误', 'argon')];
        }
    }
    
    // 添加反馈
    $id = argon_add_feedback([
        'title' => $data['feedback_title'] ?? '',
        'content' => $data['feedback_content'],
        'type' => $data['feedback_type'] ?? 'suggestion',
        'name' => $data['feedback_name'],
        'email' => $data['feedback_email'],
        'url' => $data['feedback_url'] ?? '',
        'is_public' => !empty($data['feedback_public']),
    ]);
    
    if ($id) {
        return ['success' => true, 'message' => __('反馈提交成功，感谢您的建议！', 'argon')];
    }
    return ['success' => false, 'message' => __('提交失败，请稍后重试', 'argon')];
}


// ==================== 页面渲染 ====================

// 获取反馈数据
$all_feedbacks = argon_get_feedbacks();
$public_feedbacks = argon_get_feedbacks('public');
$pending_count = count(array_filter($all_feedbacks, function($f) { return $f['status'] === 'pending'; }));

// 验证码设置
$captcha_enabled = get_option('argon_feedback_captcha', 'true') === 'true';

// 从 Cookie 获取用户信息
$saved_name = isset($_COOKIE['comment_author_' . COOKIEHASH]) ? $_COOKIE['comment_author_' . COOKIEHASH] : '';
$saved_email = isset($_COOKIE['comment_author_email_' . COOKIEHASH]) ? $_COOKIE['comment_author_email_' . COOKIEHASH] : '';
$saved_url = isset($_COOKIE['comment_author_url_' . COOKIEHASH]) ? $_COOKIE['comment_author_url_' . COOKIEHASH] : '';

// 如果已登录，使用用户信息
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $saved_name = $current_user->display_name;
    $saved_email = $current_user->user_email;
    $saved_url = $current_user->user_url;
}

get_header();
?>

<div class="page-information-card-container"></div>
<?php get_sidebar(); ?>

<div id="primary" class="content-area">

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
body.feedback-page #primary { width: 100% !important; max-width: 900px !important; margin: 0 auto !important; float: none !important; }
body.feedback-page #content { margin-top: -50vh !important; }

/* 头部卡片 */
.feedback-header-card { text-align: center; padding: 24px 24px 20px; background: transparent !important; box-shadow: none !important; }
.feedback-header-icon { width: 56px; height: 56px; margin: 0 auto 12px; background: var(--themecolor-gradient); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; box-shadow: 0 4px 16px rgba(var(--themecolor-rgbstr), 0.25); }
.feedback-title { font-size: 22px; font-weight: 700; margin: 0 0 6px; color: var(--color-text-deeper); background: none !important; }
.feedback-title::before, .feedback-title::after { display: none !important; }
.feedback-subtitle { font-size: 13px; color: #888; margin: 0 0 12px; }
.feedback-stats { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
.feedback-stat { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; background: rgba(var(--themecolor-rgbstr), 0.1); border-radius: 16px; font-size: 12px; font-weight: 500; color: var(--themecolor); }
</style>

<style>
/* 表单卡片 */
.feedback-form-card { padding: 20px 24px; }
.feedback-form-title { font-size: 16px; font-weight: 600; margin: 0 0 16px; color: var(--color-text-deeper); display: flex; align-items: center; gap: 8px; }
.feedback-form-title i { color: var(--themecolor); }
.feedback-form .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px; }
.feedback-form .form-row.full { grid-template-columns: 1fr; }
.feedback-form .form-group { margin-bottom: 0; }
.feedback-form label { font-size: 13px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 4px; display: block; }
.feedback-form label .required { color: #f5365c; }
.feedback-form input, .feedback-form textarea, .feedback-form select {
    width: 100%; padding: 10px 12px; font-size: 14px;
    border: 1px solid var(--color-border); border-radius: calc(var(--card-radius) * 0.6);
    background: var(--color-widgets); color: var(--color-text-deeper);
    font-family: var(--font); box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s;
}
.feedback-form input:focus, .feedback-form textarea:focus, .feedback-form select:focus {
    border-color: var(--themecolor); box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.1); outline: none;
}
.feedback-form textarea { resize: vertical; min-height: 120px; }
.feedback-form .char-count { font-size: 11px; color: #999; float: right; }

/* 公开选项 */
.feedback-public-option { display: flex; align-items: center; gap: 8px; margin: 16px 0; padding: 12px 16px; background: rgba(var(--themecolor-rgbstr), 0.05); border-radius: var(--card-radius); border: 1px solid rgba(var(--themecolor-rgbstr), 0.1); }
.feedback-public-option input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--themecolor); cursor: pointer; }
.feedback-public-option label { font-size: 13px; color: var(--color-text-deeper); cursor: pointer; margin: 0; font-weight: 500; }
.feedback-public-option .hint { font-size: 12px; color: #888; margin-left: auto; }

/* 验证码 */
.feedback-captcha { margin-top: 12px; }
.feedback-captcha .input-group { max-width: 280px; }
.feedback-captcha-text { background: var(--color-background); font-family: monospace; font-size: 14px; letter-spacing: 2px; cursor: pointer; user-select: none; padding: 8px 16px; }

/* 提交按钮 */
.feedback-submit { margin-top: 16px; text-align: center; }
.feedback-submit .btn { padding: 10px 32px; font-size: 14px; font-weight: 600; }

/* 反馈列表 */
.feedback-list-card { padding: 20px 24px; }
.feedback-list-title { font-size: 16px; font-weight: 600; margin: 0 0 16px; color: var(--color-text-deeper); display: flex; align-items: center; gap: 8px; }
.feedback-list-title i { color: var(--themecolor); }
.feedback-list-title .count { margin-left: auto; font-size: 12px; font-weight: 500; color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.1); padding: 3px 12px; border-radius: 12px; }

.feedback-item { padding: 16px; background: var(--color-background); border-radius: var(--card-radius); margin-bottom: 12px; position: relative; }
.feedback-item:last-child { margin-bottom: 0; }
.feedback-item-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
.feedback-item-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--themecolor-gradient); display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; color: #fff; flex-shrink: 0; overflow: hidden; }
.feedback-item-avatar img { width: 100%; height: 100%; object-fit: cover; }
.feedback-item-meta { flex: 1; min-width: 0; }
.feedback-item-name { font-size: 14px; font-weight: 600; color: var(--color-text-deeper); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.feedback-item-time { font-size: 12px; color: #999; }
.feedback-item-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.feedback-badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 500; }
.feedback-badge.type-bug { background: #fee2e2; color: #dc2626; }
.feedback-badge.type-suggestion { background: #dbeafe; color: #2563eb; }
.feedback-badge.type-question { background: #fef3c7; color: #d97706; }
.feedback-badge.type-other { background: #e5e7eb; color: #6b7280; }
.feedback-badge.status-pending { background: #fef3c7; color: #d97706; }
.feedback-badge.status-processing { background: #dbeafe; color: #2563eb; }
.feedback-badge.status-resolved { background: #d1fae5; color: #059669; }
.feedback-badge.status-closed { background: #e5e7eb; color: #6b7280; }

.feedback-item-title { font-size: 15px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 8px; }
.feedback-item-content { font-size: 14px; color: #666; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }

.feedback-item-reply { margin-top: 12px; padding: 12px; background: rgba(var(--themecolor-rgbstr), 0.05); border-radius: calc(var(--card-radius) * 0.6); border-left: 3px solid var(--themecolor); }
.feedback-item-reply-header { font-size: 12px; font-weight: 600; color: var(--themecolor); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.feedback-item-reply-content { font-size: 13px; color: #666; line-height: 1.6; white-space: pre-wrap; }

/* 管理员操作 */
.feedback-admin-actions { position: absolute; top: 12px; right: 12px; display: flex; gap: 6px; }
.feedback-admin-btn { width: 28px; height: 28px; border-radius: 6px; background: rgba(var(--themecolor-rgbstr), 0.1); color: var(--themecolor); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; transition: background 0.2s; }
.feedback-admin-btn:hover { background: rgba(var(--themecolor-rgbstr), 0.2); }
.feedback-admin-btn.danger { background: rgba(245, 54, 92, 0.1); color: #f5365c; }
.feedback-admin-btn.danger:hover { background: rgba(245, 54, 92, 0.2); }

/* 空状态 */
.feedback-empty { padding: 60px 24px; text-align: center; }
.feedback-empty i { font-size: 48px; color: var(--color-border); margin-bottom: 16px; display: block; }
.feedback-empty p { font-size: 15px; color: #888; margin: 0; }

/* 管理面板 */
.feedback-admin-card { padding: 16px 20px; }
.feedback-admin-title { font-size: 15px; font-weight: 600; color: var(--themecolor); margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }

/* 回复弹窗 */
.feedback-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; display: none; align-items: center; justify-content: center; }
.feedback-modal-overlay.show { display: flex; }
.feedback-modal { background: var(--color-foreground); border-radius: var(--card-radius); padding: 20px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.feedback-modal-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
.feedback-modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #999; padding: 0; line-height: 1; }
.feedback-modal .form-group { margin-bottom: 12px; }
.feedback-modal .form-group label { font-size: 12px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 4px; display: block; }
.feedback-modal .form-group textarea { width: 100%; padding: 10px; font-size: 13px; border: 1px solid var(--color-border); border-radius: calc(var(--card-radius) * 0.6); min-height: 100px; resize: vertical; box-sizing: border-box; }
.feedback-modal .form-group select { width: 100%; padding: 8px 10px; font-size: 13px; border: 1px solid var(--color-border); border-radius: calc(var(--card-radius) * 0.6); }
.feedback-modal .form-actions { margin-top: 16px; display: flex; gap: 8px; justify-content: flex-end; }

@media (max-width: 768px) {
    .feedback-form .form-row { grid-template-columns: 1fr; }
    .feedback-public-option { flex-wrap: wrap; }
    .feedback-public-option .hint { width: 100%; margin-left: 26px; margin-top: 4px; }
}

html.darkmode .feedback-item { background: rgba(255, 255, 255, 0.03); }
html.darkmode .feedback-modal { background: var(--color-background); }
</style>

<script data-pjax>
(function() {
    function cleanupFeedbackPage() {
        document.body.classList.remove('feedback-page');
        var s = document.getElementById('feedback-page-style');
        if (s) s.remove();
    }
    if (!document.getElementById('feedback-page-style')) {
        document.body.classList.remove('feedback-page');
    }
    document.body.classList.add('feedback-page');
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        $(document).off('pjax:start.feedback pjax:popstate.feedback');
        $(document).on('pjax:start.feedback', function() {
            cleanupFeedbackPage();
            $(document).off('pjax:start.feedback pjax:popstate.feedback');
        });
        $(document).on('pjax:popstate.feedback', function() {
            setTimeout(function() {
                if (!document.getElementById('feedback-page-style')) {
                    document.body.classList.remove('feedback-page');
                }
            }, 50);
        });
    }
})();
</script>

<main id="main" class="site-main" role="main">

<!-- 头部卡片 -->
<div class="feedback-header-card" style="margin-bottom: 16px;">
    <div class="feedback-header-icon"><i class="fa fa-commenting"></i></div>
    <h1 class="feedback-title"><?php _e('问题反馈', 'argon'); ?></h1>
    <p class="feedback-subtitle"><?php _e('有任何建议或问题？欢迎告诉我！', 'argon'); ?></p>
    <div class="feedback-stats">
        <div class="feedback-stat"><i class="fa fa-comments"></i><span><?php echo count($public_feedbacks); ?> <?php _e('条公开反馈', 'argon'); ?></span></div>
        <?php if ($is_admin && $pending_count > 0) : ?>
        <div class="feedback-stat" style="background:rgba(245,54,92,0.1);color:#f5365c;"><i class="fa fa-clock-o"></i><span><?php echo $pending_count; ?> <?php _e('条待处理', 'argon'); ?></span></div>
        <?php endif; ?>
    </div>
</div>

<!-- 提交反馈表单 -->
<article class="post card shadow-sm bg-white border-0 feedback-form-card" style="margin-bottom: 16px;">
    <h2 class="feedback-form-title"><i class="fa fa-pencil-square-o"></i><?php _e('提交反馈', 'argon'); ?></h2>
    <form class="feedback-form" id="feedback-form">
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
                <label><?php _e('网站', 'argon'); ?></label>
                <input type="url" name="feedback_url" id="feedback-url" placeholder="<?php _e('选填', 'argon'); ?>" value="<?php echo esc_attr($saved_url); ?>">
            </div>
            <div class="form-group">
                <label><?php _e('反馈类型', 'argon'); ?></label>
                <select name="feedback_type" id="feedback-type">
                    <option value="suggestion"><?php _e('💡 建议', 'argon'); ?></option>
                    <option value="bug"><?php _e('🐛 Bug 报告', 'argon'); ?></option>
                    <option value="question"><?php _e('❓ 问题咨询', 'argon'); ?></option>
                    <option value="other"><?php _e('📝 其他', 'argon'); ?></option>
                </select>
            </div>
        </div>
        <div class="form-row full">
            <div class="form-group">
                <label><?php _e('标题', 'argon'); ?></label>
                <input type="text" name="feedback_title" id="feedback-title" placeholder="<?php _e('简要描述您的反馈（选填）', 'argon'); ?>" maxlength="100">
            </div>
        </div>
        <div class="form-row full">
            <div class="form-group">
                <label><?php _e('详细内容', 'argon'); ?> <span class="required">*</span> <span class="char-count"><span id="content-count">0</span>/2000</span></label>
                <textarea name="feedback_content" id="feedback-content" required placeholder="<?php _e('请详细描述您的建议或遇到的问题...', 'argon'); ?>" maxlength="2000" oninput="document.getElementById('content-count').textContent=this.value.length"></textarea>
            </div>
        </div>
        
        <div class="feedback-public-option">
            <input type="checkbox" name="feedback_public" id="feedback-public" value="1">
            <label for="feedback-public"><?php _e('公开此反馈', 'argon'); ?></label>
            <span class="hint"><?php _e('公开后其他访客可以看到', 'argon'); ?></span>
        </div>
        
        <?php if ($captcha_enabled) : ?>
        <div class="feedback-captcha">
            <div class="input-group input-group-sm">
                <input type="text" name="feedback_captcha" id="feedback-captcha" class="form-control" placeholder="<?php _e('验证码', 'argon'); ?>" required style="max-width:120px;">
                <div class="input-group-append">
                    <span class="input-group-text feedback-captcha-text" id="feedback-captcha-text" onclick="refreshFeedbackCaptcha()" title="<?php _e('点击刷新', 'argon'); ?>"><?php echo function_exists('get_comment_captcha') ? get_comment_captcha() : ''; ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="feedback-submit">
            <button type="submit" class="btn btn-primary" id="feedback-submit-btn"><i class="fa fa-paper-plane"></i> <?php _e('提交反馈', 'argon'); ?></button>
        </div>
    </form>
</article>

<?php if ($is_admin && !empty($all_feedbacks)) : ?>
<!-- 管理员面板 - 所有反馈 -->
<article class="post card shadow-sm bg-white border-0 feedback-admin-card" style="margin-bottom: 16px;">
    <h3 class="feedback-admin-title"><i class="fa fa-cog"></i><?php _e('反馈管理', 'argon'); ?> (<?php echo count($all_feedbacks); ?>)</h3>
    <?php foreach ($all_feedbacks as $fb) : 
        $type_labels = ['bug' => __('Bug', 'argon'), 'suggestion' => __('建议', 'argon'), 'question' => __('问题', 'argon'), 'other' => __('其他', 'argon')];
        $status_labels = ['pending' => __('待处理', 'argon'), 'processing' => __('处理中', 'argon'), 'resolved' => __('已解决', 'argon'), 'closed' => __('已关闭', 'argon')];
        $type = $fb['type'] ?? 'other';
        $status = $fb['status'] ?? 'pending';
        $avatar_url = get_avatar_url($fb['email'], ['size' => 80]);
    ?>
    <div class="feedback-item" data-id="<?php echo esc_attr($fb['id']); ?>">
        <div class="feedback-admin-actions">
            <button class="feedback-admin-btn" onclick="openReplyModal('<?php echo esc_attr($fb['id']); ?>')" title="<?php _e('回复', 'argon'); ?>"><i class="fa fa-reply"></i></button>
            <button class="feedback-admin-btn" onclick="togglePublic('<?php echo esc_attr($fb['id']); ?>')" title="<?php echo $fb['is_public'] ? __('设为私密', 'argon') : __('设为公开', 'argon'); ?>"><i class="fa fa-<?php echo $fb['is_public'] ? 'eye' : 'eye-slash'; ?>"></i></button>
            <button class="feedback-admin-btn danger" onclick="deleteFeedback('<?php echo esc_attr($fb['id']); ?>')" title="<?php _e('删除', 'argon'); ?>"><i class="fa fa-trash"></i></button>
        </div>
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
                    <?php if ($fb['is_public']) : ?><span class="feedback-badge" style="background:#d1fae5;color:#059669;"><?php _e('公开', 'argon'); ?></span><?php endif; ?>
                </div>
                <div style="font-size:11px;color:#999;margin-top:4px;">
                    <?php echo esc_html($fb['email']); ?> | IP: <?php echo esc_html($fb['ip']); ?>
                </div>
            </div>
        </div>
        <?php if (!empty($fb['title'])) : ?>
        <div class="feedback-item-title"><?php echo esc_html($fb['title']); ?></div>
        <?php endif; ?>
        <div class="feedback-item-content"><?php echo esc_html($fb['content']); ?></div>
        <?php if (!empty($fb['reply'])) : ?>
        <div class="feedback-item-reply">
            <div class="feedback-item-reply-header"><i class="fa fa-reply"></i><?php _e('博主回复', 'argon'); ?> · <?php echo date('Y-m-d H:i', $fb['reply_at']); ?></div>
            <div class="feedback-item-reply-content"><?php echo esc_html($fb['reply']); ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</article>
<?php endif; ?>

<?php if (!$is_admin) : ?>
<!-- 公开反馈列表（访客视图） -->
<article class="post card shadow-sm bg-white border-0 feedback-list-card" style="margin-bottom: 16px;">
    <h2 class="feedback-list-title"><i class="fa fa-comments-o"></i><?php _e('公开反馈', 'argon'); ?><span class="count"><?php echo count($public_feedbacks); ?></span></h2>
    <?php if (!empty($public_feedbacks)) : ?>
        <?php foreach ($public_feedbacks as $fb) : 
            $type_labels = ['bug' => __('Bug', 'argon'), 'suggestion' => __('建议', 'argon'), 'question' => __('问题', 'argon'), 'other' => __('其他', 'argon')];
            $status_labels = ['pending' => __('待处理', 'argon'), 'processing' => __('处理中', 'argon'), 'resolved' => __('已解决', 'argon'), 'closed' => __('已关闭', 'argon')];
            $type = $fb['type'] ?? 'other';
            $status = $fb['status'] ?? 'pending';
            $avatar_url = get_avatar_url($fb['email'], ['size' => 80]);
        ?>
        <div class="feedback-item">
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
            <?php if (!empty($fb['reply'])) : ?>
            <div class="feedback-item-reply">
                <div class="feedback-item-reply-header"><i class="fa fa-reply"></i><?php _e('博主回复', 'argon'); ?> · <?php echo date('Y-m-d H:i', $fb['reply_at']); ?></div>
                <div class="feedback-item-reply-content"><?php echo esc_html($fb['reply']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="feedback-empty">
            <i class="fa fa-inbox"></i>
            <p><?php _e('暂无公开反馈', 'argon'); ?></p>
        </div>
    <?php endif; ?>
</article>
<?php endif; ?>

<?php if ($is_admin) : ?>
<!-- 回复弹窗 -->
<div class="feedback-modal-overlay" id="reply-modal">
    <div class="feedback-modal">
        <div class="feedback-modal-title">
            <span><i class="fa fa-reply"></i> <?php _e('回复反馈', 'argon'); ?></span>
            <button type="button" class="feedback-modal-close" onclick="closeReplyModal()">&times;</button>
        </div>
        <input type="hidden" id="reply-feedback-id">
        <div class="form-group">
            <label><?php _e('状态', 'argon'); ?></label>
            <select id="reply-status">
                <option value="pending"><?php _e('待处理', 'argon'); ?></option>
                <option value="processing"><?php _e('处理中', 'argon'); ?></option>
                <option value="resolved"><?php _e('已解决', 'argon'); ?></option>
                <option value="closed"><?php _e('已关闭', 'argon'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label><?php _e('回复内容', 'argon'); ?></label>
            <textarea id="reply-content" placeholder="<?php _e('输入回复内容，将通过邮件通知用户', 'argon'); ?>"></textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeReplyModal()"><?php _e('取消', 'argon'); ?></button>
            <button type="button" class="btn btn-primary" onclick="submitReply()"><?php _e('保存', 'argon'); ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<script data-pjax>
var feedbackNonce = '<?php echo wp_create_nonce('argon_feedback_manage'); ?>';
var feedbackAjaxUrl = '<?php echo esc_url($_SERVER['REQUEST_URI']); ?>';

// 刷新验证码
function refreshFeedbackCaptcha() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=argon_get_captcha')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.captcha) {
                document.getElementById('feedback-captcha-text').textContent = res.captcha;
            }
        });
}

// 提交反馈
document.getElementById('feedback-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('feedback-submit-btn');
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?php _e('提交中...', 'argon'); ?>';
    btn.disabled = true;
    
    var formData = new FormData(this);
    formData.append('feedback_action', 'submit');
    
    fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btn.innerHTML = originalText;
            btn.disabled = false;
            if (res.success) {
                if (typeof iziToast !== 'undefined') {
                    iziToast.success({ title: res.message, position: 'topRight', timeout: 3000 });
                } else {
                    alert(res.message);
                }
                document.getElementById('feedback-form').reset();
                document.getElementById('content-count').textContent = '0';
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({ title: res.message, position: 'topRight', timeout: 5000 });
                } else {
                    alert(res.message);
                }
                refreshFeedbackCaptcha();
            }
        })
        .catch(function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('<?php _e('提交失败，请稍后重试', 'argon'); ?>');
        });
});

<?php if ($is_admin) : ?>
// 管理员功能
function openReplyModal(id) {
    document.getElementById('reply-feedback-id').value = id;
    document.getElementById('reply-modal').classList.add('show');
}
function closeReplyModal() {
    document.getElementById('reply-modal').classList.remove('show');
    document.getElementById('reply-content').value = '';
}
function submitReply() {
    var id = document.getElementById('reply-feedback-id').value;
    var reply = document.getElementById('reply-content').value;
    var status = document.getElementById('reply-status').value;
    
    var formData = new FormData();
    formData.append('feedback_action', 'reply');
    formData.append('feedback_nonce', feedbackNonce);
    formData.append('id', id);
    formData.append('reply', reply);
    
    fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                // 更新状态
                var statusData = new FormData();
                statusData.append('feedback_action', 'update_status');
                statusData.append('feedback_nonce', feedbackNonce);
                statusData.append('id', id);
                statusData.append('status', status);
                fetch(feedbackAjaxUrl, { method: 'POST', body: statusData });
                
                closeReplyModal();
                location.reload();
            }
        });
}
function togglePublic(id) {
    var formData = new FormData();
    formData.append('feedback_action', 'toggle_public');
    formData.append('feedback_nonce', feedbackNonce);
    formData.append('id', id);
    fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(res) { if (res.success) location.reload(); });
}
function deleteFeedback(id) {
    if (!confirm('<?php _e('确定要删除这条反馈吗？', 'argon'); ?>')) return;
    var formData = new FormData();
    formData.append('feedback_action', 'delete');
    formData.append('feedback_nonce', feedbackNonce);
    formData.append('id', id);
    fetch(feedbackAjaxUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(res) { if (res.success) location.reload(); });
}
<?php endif; ?>
</script>

</main>
</div>

<?php get_footer(); ?>
