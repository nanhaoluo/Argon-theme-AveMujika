<?php
/**
 * Argon 垃圾评论通知邮件
 * 
 * 当评论被 AI 识别为垃圾评论时发送给评论者
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 发送垃圾评论通知邮件给评论者
 * 
 * @param WP_Comment $comment 评论对象
 * @param array $detection_result AI 检测结果
 * @param string $detection_code 识别码
 * @return bool 发送是否成功
 */
function argon_send_spam_notify_email($comment, $detection_result, $detection_code) {
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
    
    // 生成退订链接（使用通用的邮件退订机制）
    $unsubscribe_token = md5($comment->comment_author_email . wp_salt());
    $unsubscribe_url = add_query_arg([
        'action' => 'unsubscribe_spam_notify',
        'email' => urlencode($comment->comment_author_email),
        'token' => $unsubscribe_token
    ], home_url());
    
    // 准备模板变量
    $vars = array(
        'commenter_name' => $comment->comment_author,
        'post_title' => $post->post_title,
        'post_url' => get_permalink($post->ID),
        'comment_content' => $comment->comment_content,
        'ai_spam_reason' => isset($detection_result['reason']) ? $detection_result['reason'] : '',
        'ai_model' => $model,
        'ai_provider' => $provider_display,
        'ai_detection_code' => $detection_code,
        'query_url' => home_url('/ai-query?code=' . $detection_code),
        'unsubscribe_url' => $unsubscribe_url,
    );
    
    // 发送邮件
    return argon_send_email($comment->comment_author_email, 'spam_notify', $vars);
}
