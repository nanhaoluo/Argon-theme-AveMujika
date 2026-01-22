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
	
	// 获取 AI 配置信息
	$provider = get_option('argon_ai_summary_provider', 'openai');
	$model = get_option('argon_ai_summary_model', '');
	
	$provider_names = [
		'openai' => 'OpenAI',
		'anthropic' => 'Anthropic',
		'deepseek' => 'DeepSeek',
		'qianwen' => '通义千问',
		'wenxin' => '文心一言',
		'doubao' => '豆包',
		'kimi' => 'Kimi',
		'zhipu' => '智谱',
		'siliconflow' => 'SiliconFlow'
	];
	
	$provider_display = isset($provider_names[$provider]) ? $provider_names[$provider] : $provider;
	
	// 获取邮件设置
	$settings = argon_get_email_settings();
	
	// 构建邮件内容
	$email_subject = sprintf(__('您在「%s」的评论用户名已被修改', 'argon'), wp_trim_words($post->post_title, 20));
	
	$email_body = '
	<div style="background: #f8f9fa; padding: 32px 24px; border-radius: 8px; margin: 24px 0;">
		<h2 style="margin: 0 0 16px 0; color: #32325d; font-size: 20px; font-weight: 600;">
			' . __('用户名变更通知', 'argon') . '
		</h2>
		<p style="margin: 0 0 16px 0; color: #525f7f; font-size: 15px; line-height: 1.6;">
			' . sprintf(__('您好，%s！', 'argon'), '<strong>' . esc_html($original_username) . '</strong>') . '
		</p>
		<p style="margin: 0 0 16px 0; color: #525f7f; font-size: 15px; line-height: 1.6;">
			' . sprintf(
				__('您在文章「<a href="%s" style="color: %s; text-decoration: none;">%s</a>」发表的评论已成功提交，但系统检测到您的用户名可能不符合规范。', 'argon'),
				esc_url(get_permalink($post->ID)),
				$settings['theme_color'],
				esc_html($post->post_title)
			) . '
		</p>
	</div>
	
	<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px 20px; margin: 24px 0; border-radius: 4px;">
		<p style="margin: 0 0 8px 0; color: #856404; font-size: 14px; font-weight: 600;">
			' . __('AI 检测结果', 'argon') . '
		</p>
		<p style="margin: 0; color: #856404; font-size: 14px; line-height: 1.6;">
			' . esc_html($reason) . '
		</p>
	</div>
	
	<div style="background: #f8f9fa; padding: 24px; border-radius: 8px; margin: 24px 0;">
		<table style="width: 100%; border-collapse: collapse;">
			<tr>
				<td style="padding: 8px 0; color: #8898aa; font-size: 14px; width: 100px;">
					' . __('原用户名', 'argon') . '
				</td>
				<td style="padding: 8px 0; color: #32325d; font-size: 14px; font-weight: 500;">
					<del>' . esc_html($original_username) . '</del>
				</td>
			</tr>
			<tr>
				<td style="padding: 8px 0; color: #8898aa; font-size: 14px;">
					' . __('新用户名', 'argon') . '
				</td>
				<td style="padding: 8px 0; color: ' . $settings['theme_color'] . '; font-size: 14px; font-weight: 600;">
					' . esc_html($new_username) . '
				</td>
			</tr>
		</table>
	</div>
	
	<div style="background: #e3f2fd; padding: 16px 20px; margin: 24px 0; border-radius: 4px; border-left: 4px solid #2196f3;">
		<p style="margin: 0 0 8px 0; color: #1565c0; font-size: 14px; font-weight: 600;">
			' . __('温馨提示', 'argon') . '
		</p>
		<p style="margin: 0; color: #1976d2; font-size: 14px; line-height: 1.6;">
			' . __('您的评论内容正常，仅用户名被自动修改。今后发表评论时，请使用符合规范的用户名。', 'argon') . '
		</p>
	</div>
	
	<div style="margin: 32px 0; text-align: center;">
		<a href="' . esc_url(get_permalink($post->ID) . '#comment-' . $comment->comment_ID) . '" 
		   style="display: inline-block; padding: 12px 32px; background: ' . $settings['theme_color'] . '; color: #fff; text-decoration: none; border-radius: 4px; font-size: 15px; font-weight: 500;">
			' . __('查看您的评论', 'argon') . '
		</a>
	</div>
	
	<div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e3e8ee;">
		<p style="margin: 0 0 8px 0; color: #8898aa; font-size: 13px;">
			' . __('检测信息', 'argon') . '
		</p>
		<table style="width: 100%; font-size: 12px; color: #8898aa;">
			<tr>
				<td style="padding: 4px 0;">' . __('AI 模型', 'argon') . ':</td>
				<td style="padding: 4px 0;">' . esc_html($provider_display) . ' - ' . esc_html($model) . '</td>
			</tr>
			<tr>
				<td style="padding: 4px 0;">' . __('识别码', 'argon') . ':</td>
				<td style="padding: 4px 0; font-family: monospace;">' . esc_html($detection_code) . '</td>
			</tr>
			<tr>
				<td style="padding: 4px 0;">' . __('检测时间', 'argon') . ':</td>
				<td style="padding: 4px 0;">' . current_time('Y-m-d H:i:s') . '</td>
			</tr>
		</table>
	</div>
	';
	
	// 渲染完整邮件
	$html = argon_render_email($email_body, ['subject' => $email_subject]);
	
	// 发送邮件
	return send_mail($comment->comment_author_email, $email_subject, $html);
}
