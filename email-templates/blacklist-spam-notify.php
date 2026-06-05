<?php
/**
 * Argon 黑名单关键字拦截通知邮件
 * 
 * 当评论被黑名单关键字拦截时发送给评论者
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * 发送黑名单拦截通知邮件给评论者
 * 
 * @param WP_Comment $comment 评论对象
 * @return bool 发送是否成功
 */
function argon_send_blacklist_spam_notify_email($comment) {
	// 检查评论者是否留了邮箱
	if (empty($comment->comment_author_email)) {
		return false;
	}
	
	// 获取文章信息
	$post = get_post($comment->comment_post_ID);
	if (!$post) {
		return false;
	}
	
	// 获取触发的关键字
	$blacklist_keywords = get_comment_meta($comment->comment_ID, '_argon_spam_blacklist_keywords', true);
	$keywords_array = json_decode($blacklist_keywords, true);
	$keywords_text = is_array($keywords_array) ? implode('、', $keywords_array) : '';
	
	// 生成反馈 token
	$feedback_token = wp_hash($comment->comment_ID . $comment->comment_author_email . wp_salt());
	update_comment_meta($comment->comment_ID, '_argon_spam_feedback_token', $feedback_token);
	
	// 生成反馈链接
	$feedback_url = add_query_arg([
		'action' => 'argon_spam_feedback',
		'comment_id' => $comment->comment_ID,
		'token' => $feedback_token
	], home_url());
	
	// 准备模板变量
	$vars = array(
		'post_title' => $post->post_title,
		'post_url' => get_permalink($post->ID),
		'commenter_name' => $comment->comment_author,
		'comment_content' => $comment->comment_content,
		'blacklist_keywords' => $keywords_text,
		'feedback_url' => $feedback_url,
	);
	
	// 发送邮件
	return argon_send_email($comment->comment_author_email, 'blacklist_spam_notify', $vars);
}
