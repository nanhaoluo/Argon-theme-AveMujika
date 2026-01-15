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
    
    // 发送邮件
    return argon_send_email($admin_email, 'comment_notify', $vars);
}
