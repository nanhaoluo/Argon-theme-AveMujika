<?php
/**
 * Argon 评论回复通知邮件模板
 * 
 * 当评论收到回复时发送给原评论者的通知邮件
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 发送评论回复通知邮件
 * 
 * @param WP_Comment $reply 回复评论对象
 * @param WP_Comment $parent 被回复的评论对象
 * @return bool 发送是否成功
 */
function argon_send_reply_notify_email($reply, $parent) {
    // 检查父评论是否存在
    if (!$parent || empty($parent->comment_author_email)) {
        return false;
    }
    
    // 获取文章信息
    $post = get_post($reply->comment_post_ID);
    if (!$post) {
        return false;
    }
    
    // 不给自己发通知
    $reply_author_email = strtolower($reply->comment_author_email);
    $parent_author_email = strtolower($parent->comment_author_email);
    if ($reply_author_email === $parent_author_email) {
        return false;
    }
    
    $settings = argon_get_email_settings();
    
    // 准备邮件内容
    $content = argon_get_reply_notify_content(array(
        'post_title' => $post->post_title,
        'post_url' => get_permalink($post->ID),
        'original_comment' => wp_trim_words($parent->comment_content, 50, '...'),
        'replier_name' => $reply->comment_author,
        'reply_content' => $reply->comment_content,
        'comment_url' => get_comment_link($reply),
        'theme_color' => $settings['theme_color']
    ));
    
    // 邮件主题
    $subject = sprintf(
        '[%s] 您的评论收到了回复',
        $settings['blog_name']
    );
    
    // 发送邮件
    return argon_send_email($parent->comment_author_email, $subject, $content, 'reply');
}
