<?php
/**
 * Argon 评论通知邮件模板
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
    
    $settings = argon_get_email_settings();
    
    // 准备邮件内容
    $content = argon_get_comment_notify_content(array(
        'commenter_name' => $comment->comment_author,
        'post_title' => $post->post_title,
        'post_url' => get_permalink($post->ID),
        'comment_content' => $comment->comment_content,
        'comment_url' => get_comment_link($comment),
        'theme_color' => $settings['theme_color']
    ));
    
    // 邮件主题
    $subject = sprintf(
        '[%s] 新评论：%s',
        $settings['blog_name'],
        $post->post_title
    );
    
    // 发送邮件
    return argon_send_email($admin_email, $subject, $content, 'comment');
}
