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
	
	// 邮件设置
	$settings = argon_get_email_settings();
	$site_name = get_bloginfo('name');
	$site_url = home_url();
	
	// 邮件主题
	$subject = sprintf('[%s] 您的评论被系统拦截', $site_name);
	
	// 邮件内容
	$message = argon_get_email_template('base', [
		'site_name' => $site_name,
		'site_url' => $site_url,
		'header_color' => $settings['header_color'],
		'content' => sprintf('
			<h2 style="color: #333; font-size: 20px; margin-bottom: 20px;">评论拦截通知</h2>
			<p style="color: #666; line-height: 1.8; margin-bottom: 15px;">
				您好，%s！
			</p>
			<p style="color: #666; line-height: 1.8; margin-bottom: 15px;">
				您在文章《<a href="%s" style="color: #5e72e4; text-decoration: none;">%s</a>》中的评论因触发关键字过滤规则而被系统自动拦截。
			</p>
			<div style="background: #f8f9fa; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
				<p style="color: #666; margin: 0 0 10px 0;"><strong>您的评论内容：</strong></p>
				<p style="color: #666; margin: 0; font-style: italic;">%s</p>
			</div>
			<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
				<p style="color: #856404; margin: 0 0 10px 0;"><strong>触发的关键字：</strong>%s</p>
			</div>
			<p style="color: #666; line-height: 1.8; margin-bottom: 15px;">
				如果您认为这是一次误判，可以点击下方按钮申请 AI 复审。我们的 AI 系统会重新评估您的评论，如果确认无误，将自动恢复您的评论并优化关键字规则。
			</p>
			<div style="text-align: center; margin: 30px 0;">
				<a href="%s" style="display: inline-block; padding: 12px 30px; background: #5e72e4; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold;">申请 AI 复审</a>
			</div>
			<p style="color: #999; font-size: 12px; line-height: 1.6; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
				此邮件由系统自动发送，请勿直接回复。如有疑问，请联系网站管理员。
			</p>
		',
			esc_html($comment->comment_author),
			esc_url(get_permalink($post->ID)),
			esc_html($post->post_title),
			esc_html($comment->comment_content),
			esc_html($keywords_text),
			esc_url($feedback_url)
		)
	]);
	
	// 发送邮件
	$headers = ['Content-Type: text/html; charset=UTF-8'];
	if (!empty($settings['from_email'])) {
		$headers[] = 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>';
	}
	
	return wp_mail($comment->comment_author_email, $subject, $message, $headers);
}
