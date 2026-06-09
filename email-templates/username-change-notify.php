<?php
/**
 * Argon 用户名变更通知邮件
 * 
 * 当评论用户名被 AI 识别为不合规并自动修改时发送给评论者
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * 发送用户名变更通知邮件给评论者
 * 
 * @param WP_Comment $comment 评论对象
 * @param string $original_username 原始用户名
 * @param string $new_username 新用户名
 * @param string $reason AI 判断理由
 * @param string $detection_code 识别码
 * @return bool 发送是否成功
 */
function argon_send_username_change_notify_email($comment, $original_username, $new_username, $reason, $detection_code) {
	// 检查评论者是否留了邮箱
	if (empty($comment->comment_author_email)) {
		return false;
	}
	
	// 获取文章信息
	$post = get_post($comment->comment_post_ID);
	if (!$post) {
		return false;
	}
	
	// 准备模板变量
	$vars = array(
		'post_title' => $post->post_title,
		'post_url' => get_permalink($post->ID),
		'original_username' => $original_username,
		'new_username' => $new_username,
		'ai_reason' => $reason,
		'ai_detection_code' => $detection_code,
		'comment_url' => get_comment_link($comment),
	);
	
	// 发送邮件
	return argon_send_email($comment->comment_author_email, 'username_change_notify', $vars);
}
