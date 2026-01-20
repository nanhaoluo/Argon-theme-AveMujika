<?php
/**
 * Argon 反馈通知邮件
 * 
 * 包含新反馈通知管理员和反馈回复/完结通知用户的邮件功能
 */

if (!defined('ABSPATH')) {
	exit;
}

// 注册反馈相关邮件类型
add_filter('argon_email_types', 'argon_register_feedback_email_types');
function argon_register_feedback_email_types($types) {
	$types['feedback_new'] = array(
		'name' => __('新反馈通知', 'argon'),
		'description' => __('收到新反馈时发送给管理员', 'argon'),
		'default_subject' => '[{{blog_name}}] 收到新的问题反馈',
		'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">收到新的问题反馈</h2>
<p style="margin: 0 0 12px 0; color: #525f7f; line-height: 1.6;"><strong>{{feedback_name}}</strong> ({{feedback_email}}) 提交了一条{{feedback_type_label}}：</p>
{{#if feedback_title}}
<p style="margin: 0 0 8px 0; color: #525f7f; font-weight: 600;">{{feedback_title}}</p>
{{/if}}
<div style="background: #f6f9fc; border-left: 4px solid {{theme_color}}; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #525f7f; line-height: 1.6; white-space: pre-wrap;">{{feedback_content}}</p>
</div>
{{#if feedback_images}}
<div style="margin: 0 0 20px 0;">
    {{feedback_images}}
</div>
{{/if}}
<p style="margin: 0 0 8px 0; color: #8898aa; font-size: 13px;">提交时间：{{feedback_time}}</p>
<p style="margin: 0;">
    <a href="{{feedback_manage_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">前往管理</a>
</p>',
		'placeholders' => array(
			'blog_name' => __('博客名称', 'argon'),
			'feedback_name' => __('反馈者昵称', 'argon'),
			'feedback_email' => __('反馈者邮箱', 'argon'),
			'feedback_title' => __('反馈标题', 'argon'),
			'feedback_content' => __('反馈内容', 'argon'),
			'feedback_type_label' => __('反馈类型', 'argon'),
			'feedback_time' => __('提交时间', 'argon'),
			'feedback_manage_url' => __('管理链接', 'argon'),
			'feedback_images' => __('附件图片（HTML）', 'argon'),
			'theme_color' => __('主题色', 'argon'),
		),
	);
	
	$types['feedback_reply'] = array(
		'name' => __('反馈回复通知', 'argon'),
		'description' => __('反馈收到回复时发送给用户', 'argon'),
		'default_subject' => '[{{blog_name}}] 您的反馈已收到回复',
		'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">您的反馈已收到回复</h2>
<p style="margin: 0 0 12px 0; color: #525f7f; line-height: 1.6;">您好 <strong>{{feedback_name}}</strong>！</p>
<p style="margin: 0 0 8px 0; color: #8898aa; font-size: 14px;">您提交的反馈：</p>
<div style="background: #f6f9fc; padding: 12px 16px; border-radius: 4px; margin: 0 0 16px 0;">
    <p style="margin: 0; color: #8898aa; font-size: 14px;">{{feedback_title_or_content}}</p>
</div>
<p style="margin: 0 0 8px 0; color: #525f7f;">博主回复了您：</p>
<div style="background: #f6f9fc; border-left: 4px solid {{theme_color}}; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #525f7f; line-height: 1.6; white-space: pre-wrap;">{{reply_content}}</p>
</div>
<p style="margin: 0;">
    <a href="{{feedback_view_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查看详情</a>
</p>
<p style="margin: 16px 0 0 0; color: #8898aa; font-size: 12px;">此链接为您的专属链接，请妥善保管。</p>',
		'placeholders' => array(
			'blog_name' => __('博客名称', 'argon'),
			'feedback_name' => __('反馈者昵称', 'argon'),
			'feedback_title_or_content' => __('反馈标题或内容', 'argon'),
			'reply_content' => __('回复内容', 'argon'),
			'feedback_view_url' => __('查看链接（授权链接）', 'argon'),
			'theme_color' => __('主题色', 'argon'),
		),
	);
	
	$types['feedback_resolved'] = array(
		'name' => __('反馈完结通知', 'argon'),
		'description' => __('反馈被标记为已解决时发送给用户', 'argon'),
		'default_subject' => '[{{blog_name}}] 您的反馈已处理完成',
		'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">您的反馈已处理完成</h2>
<p style="margin: 0 0 12px 0; color: #525f7f; line-height: 1.6;">您好 <strong>{{feedback_name}}</strong>！</p>
<p style="margin: 0 0 16px 0; color: #525f7f; line-height: 1.6;">您提交的反馈「{{feedback_title_or_content}}」已被标记为<span style="color: #2dce89; font-weight: 600;">已解决</span>。</p>
{{#if reply_content}}
<p style="margin: 0 0 8px 0; color: #8898aa; font-size: 14px;">博主的回复：</p>
<div style="background: #f6f9fc; border-left: 4px solid {{theme_color}}; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #525f7f; line-height: 1.6; white-space: pre-wrap;">{{reply_content}}</p>
</div>
{{/if}}
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">感谢您的反馈，它帮助我们变得更好！</p>
<p style="margin: 0;">
    <a href="{{feedback_view_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查看详情</a>
</p>
<p style="margin: 16px 0 0 0; color: #8898aa; font-size: 12px;">此链接可直接访问</p>',
		'placeholders' => array(
			'blog_name' => __('博客名称', 'argon'),
			'feedback_name' => __('反馈者昵称', 'argon'),
			'feedback_title_or_content' => __('反馈标题或内容', 'argon'),
			'reply_content' => __('回复内容（如有）', 'argon'),
			'feedback_view_url' => __('查看链接（授权链接）', 'argon'),
			'theme_color' => __('主题色', 'argon'),
		),
	);
	
	$types['feedback_reply_append'] = array(
		'name' => __('反馈留言追加通知', 'argon'),
		'description' => __('已完结的反馈收到新留言时发送给用户', 'argon'),
		'default_subject' => '[{{blog_name}}] 您的反馈收到新留言',
		'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">您的反馈收到新留言</h2>
<p style="margin: 0 0 12px 0; color: #525f7f; line-height: 1.6;">您好 <strong>{{feedback_name}}</strong>！</p>
<p style="margin: 0 0 8px 0; color: #8898aa; font-size: 14px;">您已完结的反馈「{{feedback_title_or_content}}」收到了新的留言：</p>
<div style="background: #f6f9fc; border-left: 4px solid {{theme_color}}; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #525f7f; line-height: 1.6; white-space: pre-wrap;">{{reply_content}}</p>
</div>
<p style="margin: 0;">
    <a href="{{feedback_view_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查看详情</a>
</p>
<p style="margin: 16px 0 0 0; color: #8898aa; font-size: 12px;">此链接为您的专属链接，请妥善保管。</p>',
		'placeholders' => array(
			'blog_name' => __('博客名称', 'argon'),
			'feedback_name' => __('反馈者昵称', 'argon'),
			'feedback_title_or_content' => __('反馈标题或内容', 'argon'),
			'reply_content' => __('新留言内容', 'argon'),
			'feedback_view_url' => __('查看链接（授权链接）', 'argon'),
			'theme_color' => __('主题色', 'argon'),
		),
	);
	
	return $types;
}

/**
 * 生成反馈授权 token
 * 
 * @param string $feedback_id 反馈 ID
 * @return string 授权 token
 */
function argon_generate_feedback_token($feedback_id) {
	$secret = wp_salt('auth');
	return substr(hash('sha256', $feedback_id . $secret), 0, 32);
}

/**
 * 验证反馈授权 token
 * 
 * @param string $feedback_id 反馈 ID
 * @param string $token 授权 token
 * @return bool 是否有效
 */
function argon_verify_feedback_token($feedback_id, $token) {
	return hash_equals(argon_generate_feedback_token($feedback_id), $token);
}

/**
 * 获取反馈授权查看链接
 * 
 * @param string $feedback_id 反馈 ID
 * @return string 授权链接
 */
function argon_get_feedback_auth_url($feedback_id) {
	$token = argon_generate_feedback_token($feedback_id);
	$feedback_page_url = get_template_directory_uri();
	// 使用主题目录下的 feedback.php
	$base_url = home_url('/') . '?argon_feedback_view=1';
	return add_query_arg(array(
		'id' => $feedback_id,
		'token' => $token
	), $base_url);
}

/**
 * 发送新反馈通知邮件给管理员
 * 
 * @param array $feedback 反馈数据
 * @return bool 发送是否成功
 */
function argon_send_feedback_new_notify($feedback) {
	// 检查是否启用邮件通知
	if (get_option('argon_feedback_notify_admin', 'true') !== 'true') {
		return false;
	}
	
	$type_labels = array(
		'bug' => __('Bug 报告', 'argon'),
		'suggestion' => __('建议', 'argon'),
		'question' => __('问题咨询', 'argon'),
		'other' => __('其他反馈', 'argon')
	);
	
	// 邮件中不显示图片，仅提示数量
	$images_html = '';
	if (!empty($feedback['images']) && is_array($feedback['images'])) {
		$count = count($feedback['images']);
		$images_html = '<p style="margin: 0; color: #8898aa; font-size: 13px;">' . sprintf(__('包含 %d 张图片（请在管理后台查看）', 'argon'), $count) . '</p>';
	}
	
	$vars = array(
		'feedback_name' => $feedback['name'],
		'feedback_email' => $feedback['email'],
		'feedback_title' => $feedback['title'],
		'feedback_content' => $feedback['content'],
		'feedback_type_label' => isset($type_labels[$feedback['type']]) ? $type_labels[$feedback['type']] : $type_labels['other'],
		'feedback_time' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $feedback['created_at']),
		'feedback_manage_url' => home_url('/') . '?argon_feedback_view=1',
		'feedback_images' => $images_html,
	);
	
	$admin_email = get_option('admin_email');
	return argon_send_email($admin_email, 'feedback_new', $vars);
}

/**
 * 发送反馈回复通知邮件给用户
 * 
 * @param array $feedback 反馈数据
 * @param string $reply 回复内容
 * @return bool 发送是否成功
 */
function argon_send_feedback_reply_notify($feedback, $reply) {
	if (empty($feedback['email']) || empty($reply)) {
		return false;
	}
	
	// 检查是否启用邮件通知
	if (get_option('argon_feedback_notify_user', 'true') !== 'true') {
		return false;
	}
	
	$title_or_content = !empty($feedback['title']) ? $feedback['title'] : mb_substr($feedback['content'], 0, 50) . '...';
	
	$vars = array(
		'feedback_name' => $feedback['name'],
		'feedback_title_or_content' => $title_or_content,
		'reply_content' => $reply,
		'feedback_view_url' => argon_get_feedback_auth_url($feedback['id']),
	);
	
	return argon_send_email($feedback['email'], 'feedback_reply', $vars);
}

/**
 * 发送反馈完结通知邮件给用户
 * 
 * @param array $feedback 反馈数据
 * @return bool 发送是否成功
 */
function argon_send_feedback_resolved_notify($feedback) {
	if (empty($feedback['email'])) {
		return false;
	}
	
	// 检查是否启用邮件通知
	if (get_option('argon_feedback_notify_user', 'true') !== 'true') {
		return false;
	}
	
	$title_or_content = !empty($feedback['title']) ? $feedback['title'] : mb_substr($feedback['content'], 0, 50) . '...';
	
	// 获取最后一条非私密留言
	$last_reply = '';
	$replies = $feedback['replies'] ?? [];
	if (empty($replies) && !empty($feedback['reply'])) {
		$last_reply = $feedback['reply'];
	} else {
		for ($i = count($replies) - 1; $i >= 0; $i--) {
			if (empty($replies[$i]['is_private'])) {
				$last_reply = $replies[$i]['content'];
				break;
			}
		}
	}
	
	$vars = array(
		'feedback_name' => $feedback['name'],
		'feedback_title_or_content' => $title_or_content,
		'reply_content' => $last_reply,
		'feedback_view_url' => argon_get_feedback_auth_url($feedback['id']),
	);
	
	return argon_send_email($feedback['email'], 'feedback_resolved', $vars);
}

/**
 * 发送反馈留言追加通知邮件给用户
 * 
 * @param array $feedback 反馈数据
 * @param string $reply 新留言内容
 * @return bool 发送是否成功
 */
function argon_send_feedback_reply_append_notify($feedback, $reply) {
	if (empty($feedback['email']) || empty($reply)) {
		return false;
	}
	
	// 检查是否启用邮件通知
	if (get_option('argon_feedback_notify_user', 'true') !== 'true') {
		return false;
	}
	
	$title_or_content = !empty($feedback['title']) ? $feedback['title'] : mb_substr($feedback['content'], 0, 50) . '...';
	
	$vars = array(
		'feedback_name' => $feedback['name'],
		'feedback_title_or_content' => $title_or_content,
		'reply_content' => $reply,
		'feedback_view_url' => argon_get_feedback_auth_url($feedback['id']),
	);
	
	return argon_send_email($feedback['email'], 'feedback_reply_append', $vars);
}
