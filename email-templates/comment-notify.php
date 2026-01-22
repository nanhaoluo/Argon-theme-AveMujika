<?php
/**
 * Argon 评论通知邮件
 * 
 * 当博客收到新评论时发送给管理员的通知邮件
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 发送评论通知邮件给管理员
 * 
 * @param WP_Comment $comment 评论对象
 * @return bool 发送是否成功
 */
function argon_send_comment_notify_email($comment) {
    // 获取文章信息
    $post = get_post($comment->comment_post_ID);
    if (!$post) {
        return false;
    }
    
    // 获取管理员邮箱
    $admin_email = get_option('admin_email');
    if (empty($admin_email)) {
        return false;
    }
    
    // 不给自己发通知
    $comment_author_email = strtolower($comment->comment_author_email);
    if ($comment_author_email === strtolower($admin_email)) {
        return false;
    }
    
    // 准备模板变量
    $vars = array(
        'post_title' => $post->post_title,
        'post_url' => get_permalink($post->ID),
        'commenter_name' => $comment->comment_author,
        'commenter_email' => $comment->comment_author_email,
        'comment_content' => $comment->comment_content,
        'comment_url' => get_comment_link($comment),
        'comment_date' => get_comment_date(get_option('date_format') . ' ' . get_option('time_format'), $comment),
    );
    
    // 检查是否有 AI 审核信息
    $spam_detection_result = get_comment_meta($comment->comment_ID, '_argon_spam_detection_result', true);
    if (!empty($spam_detection_result) && is_array($spam_detection_result)) {
        $vars['ai_spam_check'] = true;
        $vars['ai_is_spam'] = $spam_detection_result['is_spam'];
        $vars['ai_spam_reason'] = isset($spam_detection_result['reason']) ? $spam_detection_result['reason'] : '';
        $vars['ai_spam_action'] = isset($spam_detection_result['action']) ? $spam_detection_result['action'] : '';
        
        // 生成内容识别码（评论 ID + 时间戳的 MD5）
        $detection_time = get_comment_meta($comment->comment_ID, '_argon_spam_detection_time', true);
        $vars['ai_detection_code'] = strtoupper(substr(md5($comment->comment_ID . '-' . $detection_time), 0, 8));
    } else {
        $vars['ai_spam_check'] = false;
    }
    
    // 发送邮件
    return argon_send_email($admin_email, 'comment_notify', $vars);
}
