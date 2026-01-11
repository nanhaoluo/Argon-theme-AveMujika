<?php
/**
 * Argon 邮件基础模板
 * 
 * 使用表格布局确保邮件客户端兼容性
 * 所有样式使用内联方式
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 获取邮件模板设置
 */
function argon_get_email_settings() {
    $theme_color = get_option('argon_email_theme_color', '#5e72e4');
    $logo_url = get_option('argon_email_logo_url', '');
    $blog_name = get_option('argon_email_blog_name', '');
    $footer_text = get_option('argon_email_footer_text', '');
    $social_links = get_option('argon_email_social_links', array());
    
    // 使用默认值
    if (empty($blog_name)) {
        $blog_name = get_bloginfo('name');
    }
    if (empty($footer_text)) {
        $footer_text = '© ' . date('Y') . ' ' . $blog_name . '. All rights reserved.';
    }
    
    return array(
        'theme_color' => $theme_color,
        'logo_url' => $logo_url,
        'blog_name' => $blog_name,
        'footer_text' => $footer_text,
        'social_links' => $social_links
    );
}

/**
 * 获取邮件基础模板 HTML
 */
function argon_get_email_template() {
    $settings = argon_get_email_settings();
    
    // 页眉部分
    $header_html = '';
    if (!empty($settings['logo_url'])) {
        $header_html = '<img src="' . esc_url($settings['logo_url']) . '" alt="' . esc_attr($settings['blog_name']) . '" style="max-height: 48px; max-width: 200px;">';
    } else {
        $header_html = '<h1 style="margin: 0; font-size: 24px; font-weight: 600; color: ' . esc_attr($settings['theme_color']) . ';">' . esc_html($settings['blog_name']) . '</h1>';
    }

    // 社交链接部分
    $social_html = '';
    if (!empty($settings['social_links']) && is_array($settings['social_links'])) {
        $social_items = array();
        $social_names = array(
            'twitter' => 'Twitter',
            'github' => 'GitHub',
            'weibo' => '微博',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram'
        );
        foreach ($settings['social_links'] as $key => $url) {
            if (!empty($url)) {
                $name = isset($social_names[$key]) ? $social_names[$key] : ucfirst($key);
                $social_items[] = '<a href="' . esc_url($url) . '" style="display: inline-block; margin: 0 8px; color: #8898aa; text-decoration: none;">' . esc_html($name) . '</a>';
            }
        }
        if (!empty($social_items)) {
            $social_html = '<p style="margin: 0 0 12px 0;">' . implode('', $social_items) . '</p>';
        }
    }
    
    $template = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f5f7; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%;">
                    <!-- 页眉 -->
                    <tr>
                        <td style="padding: 24px; text-align: center;">
                            ' . $header_html . '
                        </td>
                    </tr>
                    
                    <!-- 内容区 -->
                    <tr>
                        <td style="background: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 32px;">
                                        {{content}}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- 页脚 -->
                    <tr>
                        <td style="padding: 24px; text-align: center;">
                            ' . $social_html . '
                            <p style="margin: 0; font-size: 12px; color: #8898aa;">
                                ' . esc_html($settings['footer_text']) . '
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    return $template;
}


/**
 * 渲染邮件模板
 * 
 * @param string $content 邮件内容 HTML
 * @param array $vars 额外的模板变量
 * @return string 渲染后的完整邮件 HTML
 */
function argon_render_email($content, $vars = array()) {
    $settings = argon_get_email_settings();
    $template = argon_get_email_template();
    
    // 替换内容占位符
    $html = str_replace('{{content}}', $content, $template);
    
    // 替换主题色
    $html = str_replace('{{theme_color}}', esc_attr($settings['theme_color']), $html);
    
    // 替换其他变量
    if (!empty($vars)) {
        foreach ($vars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }
    }
    
    return $html;
}

/**
 * 发送统一格式的邮件
 * 
 * @param string $to 收件人邮箱
 * @param string $subject 邮件主题
 * @param string $content 邮件内容 HTML
 * @param string $type 邮件类型 (comment, reply, general)
 * @return bool 发送是否成功
 */
function argon_send_email($to, $subject, $content, $type = 'general') {
    $settings = argon_get_email_settings();
    
    // 渲染完整邮件
    $html = argon_render_email($content, array('subject' => $subject));
    
    // 设置邮件头
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $settings['blog_name'] . ' <' . get_option('admin_email') . '>'
    );
    
    // 发送邮件
    $result = wp_mail($to, $subject, $html, $headers);
    
    // 记录错误日志
    if (!$result) {
        error_log('Argon Email: Failed to send ' . $type . ' email to ' . $to);
    }
    
    return $result;
}


/**
 * AJAX 邮件预览接口
 */
add_action('wp_ajax_argon_preview_email', 'argon_preview_email_handler');
function argon_preview_email_handler() {
    // 验证 nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'argon_preview_email')) {
        wp_die('Invalid nonce');
    }
    
    // 检查权限
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }
    
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'comment';
    $settings = argon_get_email_settings();
    
    // 生成示例内容
    if ($type === 'reply') {
        $content = argon_get_reply_notify_content(array(
            'post_title' => '示例文章标题',
            'post_url' => home_url('/sample-post/'),
            'original_comment' => '这是原始评论的内容，用于展示邮件模板效果。',
            'replier_name' => '回复者',
            'reply_content' => '这是回复内容的示例文本，展示了邮件模板中回复通知的样式效果。感谢您的评论！',
            'comment_url' => home_url('/sample-post/#comment-1'),
            'theme_color' => $settings['theme_color']
        ));
    } else {
        $content = argon_get_comment_notify_content(array(
            'commenter_name' => '评论者',
            'post_title' => '示例文章标题',
            'post_url' => home_url('/sample-post/'),
            'comment_content' => '这是评论内容的示例文本，展示了邮件模板中评论通知的样式效果。非常感谢您的精彩文章！',
            'comment_url' => home_url('/sample-post/#comment-1'),
            'theme_color' => $settings['theme_color']
        ));
    }
    
    // 渲染完整邮件
    $html = argon_render_email($content, array('subject' => '邮件预览'));
    
    echo $html;
    wp_die();
}

/**
 * 生成评论通知邮件内容
 */
function argon_get_comment_notify_content($vars) {
    $theme_color = isset($vars['theme_color']) ? $vars['theme_color'] : '#5e72e4';
    
    return '<h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 600; color: #32325d;">
        您的文章收到了新评论
    </h2>
    <p style="margin: 0 0 24px 0; color: #525f7f; line-height: 1.6;">
        <strong>' . esc_html($vars['commenter_name']) . '</strong> 在《<a href="' . esc_url($vars['post_url']) . '" style="color: ' . esc_attr($theme_color) . '; text-decoration: none;">' . esc_html($vars['post_title']) . '</a>》中发表了评论：
    </p>
    <div style="background: #f6f9fc; border-left: 4px solid ' . esc_attr($theme_color) . '; padding: 16px; border-radius: 4px; margin: 0 0 24px 0;">
        <p style="margin: 0; color: #525f7f; line-height: 1.6;">' . esc_html($vars['comment_content']) . '</p>
    </div>
    <a href="' . esc_url($vars['comment_url']) . '" style="display: inline-block; background: ' . esc_attr($theme_color) . '; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 500;">
        查看评论
    </a>';
}

/**
 * 生成评论回复通知邮件内容
 */
function argon_get_reply_notify_content($vars) {
    $theme_color = isset($vars['theme_color']) ? $vars['theme_color'] : '#5e72e4';
    
    return '<h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 600; color: #32325d;">
        您的评论收到了回复
    </h2>
    <p style="margin: 0 0 16px 0; color: #8898aa; font-size: 14px;">
        您在《<a href="' . esc_url($vars['post_url']) . '" style="color: ' . esc_attr($theme_color) . '; text-decoration: none;">' . esc_html($vars['post_title']) . '</a>》的评论：
    </p>
    <div style="background: #f6f9fc; padding: 12px 16px; border-radius: 4px; margin: 0 0 16px 0;">
        <p style="margin: 0; color: #8898aa; font-size: 14px;">' . esc_html($vars['original_comment']) . '</p>
    </div>
    <p style="margin: 0 0 16px 0; color: #525f7f;">
        <strong>' . esc_html($vars['replier_name']) . '</strong> 回复了您：
    </p>
    <div style="background: #f6f9fc; border-left: 4px solid ' . esc_attr($theme_color) . '; padding: 16px; border-radius: 4px; margin: 0 0 24px 0;">
        <p style="margin: 0; color: #525f7f; line-height: 1.6;">' . esc_html($vars['reply_content']) . '</p>
    </div>
    <a href="' . esc_url($vars['comment_url']) . '" style="display: inline-block; background: ' . esc_attr($theme_color) . '; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 500;">
        查看回复
    </a>';
}
