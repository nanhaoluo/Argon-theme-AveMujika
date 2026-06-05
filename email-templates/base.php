<?php
/**
 * Argon 邮件模板系统
 * 
 * 支持多种邮件类型的自定义模板和占位符替换
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 获取所有支持的邮件类型及其默认配置
 */
function argon_get_email_types() {
    $types = array(
        'comment_notify' => array(
            'name' => __('评论通知', 'argon'),
            'description' => __('当博客收到新评论时发送给管理员', 'argon'),
            'default_subject' => '[{{blog_name}}] 文章收到新评论',
            'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">文章收到新评论</h2>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">用户 <strong>{{commenter_name}}</strong> 在文章《<a href="{{post_url}}" style="color: {{theme_color}}; text-decoration: none;">{{post_title}}</a>》中发表了评论：</p>
<div style="background: #f6f9fc; border-left: 4px solid {{theme_color}}; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #525f7f; line-height: 1.6;">{{comment_content}}</p>
</div>
{{#ai_spam_check}}
<div style="background: {{#ai_is_spam}}#fff5f5{{/ai_is_spam}}{{^ai_is_spam}}#f0fdf4{{/ai_is_spam}}; border-left: 4px solid {{#ai_is_spam}}#f56565{{/ai_is_spam}}{{^ai_is_spam}}#48bb78{{/ai_is_spam}}; padding: 12px 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #2d3748; font-size: 14px; font-weight: 600;">AI 内容审核</p>
    <p style="margin: 0 0 4px 0; color: #4a5568; font-size: 13px;">识别结果：{{#ai_is_spam}}<span style="color: #e53e3e; font-weight: 600;">疑似垃圾评论</span>{{/ai_is_spam}}{{^ai_is_spam}}<span style="color: #38a169; font-weight: 600;">正常评论</span>{{/ai_is_spam}}</p>
    {{#ai_spam_reason}}<p style="margin: 0 0 4px 0; color: #4a5568; font-size: 13px;">识别理由：{{ai_spam_reason}}</p>{{/ai_spam_reason}}
    {{#ai_detection_code}}<p style="margin: 0; color: #718096; font-size: 12px;">识别码：{{ai_detection_code}}</p>{{/ai_detection_code}}
</div>
{{/ai_spam_check}}
<p style="margin: 0;">
    <a href="{{comment_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查看评论</a>
</p>',
            'placeholders' => array(
                'blog_name' => __('博客名称', 'argon'),
                'post_title' => __('文章标题', 'argon'),
                'post_url' => __('文章链接', 'argon'),
                'commenter_name' => __('评论者名称', 'argon'),
                'commenter_email' => __('评论者邮箱', 'argon'),
                'comment_content' => __('评论内容', 'argon'),
                'comment_url' => __('评论链接', 'argon'),
                'comment_date' => __('评论时间', 'argon'),
                'ai_spam_check' => __('是否进行了 AI 审核', 'argon'),
                'ai_is_spam' => __('AI 判断是否为垃圾评论', 'argon'),
                'ai_spam_reason' => __('AI 识别理由', 'argon'),
                'ai_detection_code' => __('AI 识别码', 'argon'),
                'theme_color' => __('主题色', 'argon'),
            ),
        ),
        'spam_notify' => array(
            'name' => __('垃圾评论通知', 'argon'),
            'description' => __('当评论被 AI 识别为垃圾评论时发送给评论者', 'argon'),
            'default_subject' => '[{{blog_name}}] 您的评论未通过审核',
            'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">评论审核通知</h2>
<p style="margin: 0 0 16px 0; color: #525f7f; line-height: 1.6;">尊敬的用户 <strong>{{commenter_name}}</strong>：</p>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">您在文章《<a href="{{post_url}}" style="color: {{theme_color}}; text-decoration: none;">{{post_title}}</a>》中发表的评论未通过系统审核。</p>
<div style="background: #fff5f5; border-left: 4px solid #f56565; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #2d3748; font-size: 14px; font-weight: 600;">您的评论内容</p>
    <p style="margin: 0; color: #4a5568; line-height: 1.6;">{{comment_content}}</p>
</div>
<div style="background: #f6f9fc; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #2d3748; font-size: 14px; font-weight: 600;">审核信息</p>
    <p style="margin: 0 0 4px 0; color: #4a5568; font-size: 13px;">识别结果：<span style="color: #e53e3e; font-weight: 600;">疑似不当内容</span></p>
    <p style="margin: 0 0 4px 0; color: #4a5568; font-size: 13px;">识别理由：{{ai_spam_reason}}</p>
    <p style="margin: 0; color: #718096; font-size: 12px;">识别码：{{ai_detection_code}}</p>
</div>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">如果您认为这是误判，请通过识别码查询详细信息或联系网站管理员申诉。</p>
<p style="margin: 0;">
    <a href="{{query_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查询识别详情</a>
</p>',
            'placeholders' => array(
                'blog_name' => __('博客名称', 'argon'),
                'post_title' => __('文章标题', 'argon'),
                'post_url' => __('文章链接', 'argon'),
                'commenter_name' => __('评论者名称', 'argon'),
                'comment_content' => __('评论内容', 'argon'),
                'ai_spam_reason' => __('AI 识别理由', 'argon'),
                'ai_detection_code' => __('AI 识别码', 'argon'),
                'query_url' => __('查询链接', 'argon'),
                'unsubscribe_url' => __('退订链接', 'argon'),
                'theme_color' => __('主题色', 'argon'),
            ),
        ),
        'panic_mode_notify' => array(
            'name' => __('怕死模式审核通知', 'argon'),
            'description' => __('当评论在怕死模式下未通过审核时发送给评论者', 'argon'),
            'default_subject' => '[{{blog_name}}] 您的评论未通过审核',
            'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">评论审核通知</h2>
<p style="margin: 0 0 16px 0; color: #525f7f; line-height: 1.6;">尊敬的用户 <strong>{{commenter_name}}</strong>：</p>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">您在文章《<a href="{{post_url}}" style="color: {{theme_color}}; text-decoration: none;">{{post_title}}</a>》中发表的评论未通过系统审核。</p>
<div style="background: #fff5f5; border-left: 4px solid #f56565; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #2d3748; font-size: 14px; font-weight: 600;">您的评论内容</p>
    <p style="margin: 0; color: #4a5568; line-height: 1.6;">{{comment_content}}</p>
</div>
<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #92400e; font-size: 14px; font-weight: 600;">审核说明</p>
    <p style="margin: 0; color: #78350f; line-height: 1.6;">很抱歉，由于该文章当前处于特殊审核状态，评论内容需要经过更严格的审查。您的评论未能通过此次审核，建议您修改后重新提交。</p>
</div>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">如果您认为这是误判，请联系网站管理员申诉。</p>
<p style="margin: 0;">
    <a href="{{query_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查询识别详情</a>
</p>',
            'placeholders' => array(
                'blog_name' => __('博客名称', 'argon'),
                'post_title' => __('文章标题', 'argon'),
                'post_url' => __('文章链接', 'argon'),
                'commenter_name' => __('评论者名称', 'argon'),
                'comment_content' => __('评论内容', 'argon'),
                'ai_spam_reason' => __('AI 识别理由', 'argon'),
                'ai_detection_code' => __('AI 识别码', 'argon'),
                'query_url' => __('查询链接', 'argon'),
                'unsubscribe_url' => __('退订链接', 'argon'),
                'theme_color' => __('主题色', 'argon'),
            ),
        ),
        'reply_notify' => array(
            'name' => __('回复通知', 'argon'),
            'description' => __('当评论收到回复时发送给原评论者', 'argon'),
            'default_subject' => '[{{blog_name}}] 您的评论收到了回复',
            'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">您的评论收到了回复</h2>
<p style="margin: 0 0 12px 0; color: #8898aa; font-size: 14px;">您在文章《<a href="{{post_url}}" style="color: {{theme_color}}; text-decoration: none;">{{post_title}}</a>》中的评论：</p>
<div style="background: #f6f9fc; padding: 12px 16px; border-radius: 4px; margin: 0 0 16px 0;">
    <p style="margin: 0; color: #8898aa; font-size: 14px;">{{original_comment}}</p>
</div>
<p style="margin: 0 0 12px 0; color: #525f7f;"><strong>{{replier_name}}</strong> 回复了您：</p>
<div style="background: #f6f9fc; border-left: 4px solid {{theme_color}}; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #525f7f; line-height: 1.6;">{{reply_content}}</p>
</div>
<p style="margin: 0;">
    <a href="{{comment_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查看回复</a>
</p>',
            'placeholders' => array(
                'blog_name' => __('博客名称', 'argon'),
                'post_title' => __('文章标题', 'argon'),
                'post_url' => __('文章链接', 'argon'),
                'original_comment' => __('原评论内容', 'argon'),
                'replier_name' => __('回复者名称', 'argon'),
                'replier_email' => __('回复者邮箱', 'argon'),
                'reply_content' => __('回复内容', 'argon'),
                'comment_url' => __('评论链接', 'argon'),
                'reply_date' => __('回复时间', 'argon'),
                'theme_color' => __('主题色', 'argon'),
            ),
        ),
        'todo_urge' => array(
            'name' => __('TODO 提醒', 'argon'),
            'description' => __('访客催促作者完成 TODO 时发送的邮件', 'argon'),
            'default_subject' => '[{{blog_name}}] 有访客催促您完成 TODO',
            'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">有访客催促您完成 TODO</h2>
<p style="margin: 0 0 12px 0; color: #525f7f; line-height: 1.6;">以下任务被访客催促完成：</p>
<div style="background: #f6f9fc; border-left: 4px solid {{theme_color}}; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #525f7f; line-height: 1.6;">{{todo_content}}</p>
</div>
<p style="margin: 0;">
    <a href="{{blog_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">前往博客</a>
</p>',
            'placeholders' => array(
                'blog_name' => __('博客名称', 'argon'),
                'blog_url' => __('博客链接', 'argon'),
                'todo_content' => __('TODO 内容', 'argon'),
                'todo_id' => __('TODO ID', 'argon'),
                'urge_time' => __('提醒时间', 'argon'),
                'theme_color' => __('主题色', 'argon'),
            ),
        ),
        'blacklist_spam_notify' => array(
            'name' => __('黑名单拦截通知', 'argon'),
            'description' => __('当评论被黑名单关键字拦截时发送给评论者', 'argon'),
            'default_subject' => '[{{blog_name}}] 您的评论被系统拦截',
            'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">评论拦截通知</h2>
<p style="margin: 0 0 16px 0; color: #525f7f; line-height: 1.6;">您好，<strong>{{commenter_name}}</strong>！</p>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">您在文章《<a href="{{post_url}}" style="color: {{theme_color}}; text-decoration: none;">{{post_title}}</a>》中的评论因触发关键字过滤规则而被系统自动拦截。</p>
<div style="background: #fff5f5; border-left: 4px solid #f56565; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #2d3748; font-size: 14px; font-weight: 600;">您的评论内容</p>
    <p style="margin: 0; color: #4a5568; line-height: 1.6;">{{comment_content}}</p>
</div>
<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0; color: #92400e; font-size: 14px;"><strong>触发的关键字：</strong>{{blacklist_keywords}}</p>
</div>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">如果您认为这是一次误判，可以点击下方按钮申请 AI 复审。</p>
<p style="margin: 0;">
    <a href="{{feedback_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">申请 AI 复审</a>
</p>',
            'placeholders' => array(
                'blog_name' => __('博客名称', 'argon'),
                'post_title' => __('文章标题', 'argon'),
                'post_url' => __('文章链接', 'argon'),
                'commenter_name' => __('评论者名称', 'argon'),
                'comment_content' => __('评论内容', 'argon'),
                'blacklist_keywords' => __('触发的关键字', 'argon'),
                'feedback_url' => __('申诉链接', 'argon'),
                'theme_color' => __('主题色', 'argon'),
            ),
        ),
        'username_change_notify' => array(
            'name' => __('用户名变更通知', 'argon'),
            'description' => __('当评论用户名被 AI 识别为不合规并自动修改时发送给评论者', 'argon'),
            'default_subject' => '[{{blog_name}}] 您的评论用户名已被修改',
            'default_content' => '<h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: {{theme_color}};">用户名变更通知</h2>
<p style="margin: 0 0 16px 0; color: #525f7f; line-height: 1.6;">您好，<strong>{{original_username}}</strong>！</p>
<p style="margin: 0 0 20px 0; color: #525f7f; line-height: 1.6;">您在文章《<a href="{{post_url}}" style="color: {{theme_color}}; text-decoration: none;">{{post_title}}</a>》发表的评论已成功提交，但系统检测到您的用户名可能不符合规范。</p>
<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #92400e; font-size: 14px; font-weight: 600;">AI 检测结果</p>
    <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;">{{ai_reason}}</p>
</div>
<div style="background: #f6f9fc; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #8898aa; font-size: 14px;">原用户名：<del>{{original_username}}</del></p>
    <p style="margin: 0; color: {{theme_color}}; font-size: 14px; font-weight: 600;">新用户名：{{new_username}}</p>
</div>
<div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 16px; border-radius: 4px; margin: 0 0 20px 0;">
    <p style="margin: 0 0 8px 0; color: #1565c0; font-size: 14px; font-weight: 600;">温馨提示</p>
    <p style="margin: 0; color: #1976d2; font-size: 14px; line-height: 1.6;">您的评论内容正常，仅用户名被自动修改。今后发表评论时，请使用符合规范的用户名。</p>
</div>
<p style="margin: 0;">
    <a href="{{comment_url}}" style="display: inline-block; background: {{theme_color}}; color: #ffffff; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 14px;">查看您的评论</a>
</p>',
            'placeholders' => array(
                'blog_name' => __('博客名称', 'argon'),
                'post_title' => __('文章标题', 'argon'),
                'post_url' => __('文章链接', 'argon'),
                'original_username' => __('原用户名', 'argon'),
                'new_username' => __('新用户名', 'argon'),
                'ai_reason' => __('AI 判断理由', 'argon'),
                'ai_detection_code' => __('识别码', 'argon'),
                'comment_url' => __('评论链接', 'argon'),
                'theme_color' => __('主题色', 'argon'),
            ),
        ),
    );
    
    // 允许通过 filter 扩展邮件类型
    return apply_filters('argon_email_types', $types);
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
 * 获取指定邮件类型的模板配置
 */
function argon_get_email_template_config($type) {
    $types = argon_get_email_types();
    $settings = argon_get_email_settings();
    
    if (!isset($types[$type])) {
        $type = 'general';
    }
    
    $default = $types[$type];
    
    // 获取自定义模板，如果没有则使用默认
    $subject = get_option('argon_email_template_' . $type . '_subject', '');
    $content = get_option('argon_email_template_' . $type . '_content', '');
    $enabled = get_option('argon_email_template_' . $type . '_enabled', 'true');
    
    return array(
        'type' => $type,
        'name' => $default['name'],
        'description' => $default['description'],
        'subject' => !empty($subject) ? $subject : $default['default_subject'],
        'content' => !empty($content) ? $content : $default['default_content'],
        'default_subject' => $default['default_subject'],
        'default_content' => $default['default_content'],
        'placeholders' => $default['placeholders'],
        'enabled' => $enabled === 'true',
    );
}


/**
 * 获取邮件基础模板 HTML
 */
function argon_get_email_base_template($include_unsubscribe = false) {
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
            'bilibili' => 'Bilibili',
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
    
    // 退订链接部分
    $unsubscribe_html = '';
    if ($include_unsubscribe) {
        $unsubscribe_html = '<p style="margin: 12px 0 0 0; font-size: 12px; color: #8898aa;">
            <a href="{{unsubscribe_url}}" style="color: #8898aa; text-decoration: none;">退订邮件通知</a>
        </p>';
    }
    
    $template = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{email_subject}}</title>
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
                                        {{email_content}}
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
                            ' . $unsubscribe_html . '
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
 * 替换模板中的占位符
 * 
 * @param string $template 模板字符串
 * @param array $vars 变量数组
 * @param bool $escape 是否转义 HTML
 * @return string 替换后的字符串
 */
function argon_replace_placeholders($template, $vars, $escape = false) {
    $settings = argon_get_email_settings();
    
    // 添加全局变量
    $global_vars = array(
        'blog_name' => $settings['blog_name'],
        'blog_url' => home_url(),
        'theme_color' => $settings['theme_color'],
        'current_year' => date('Y'),
        'current_date' => date_i18n(get_option('date_format')),
        'current_time' => date_i18n(get_option('time_format')),
    );
    
    $vars = array_merge($global_vars, $vars);
    
    // 处理 Mustache 风格的条件语句 {{#variable}}...{{/variable}}
    $template = preg_replace_callback('/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s', function($matches) use ($vars) {
        $var_name = $matches[1];
        $content = $matches[2];
        // 如果变量存在且为真值，返回内容
        if (isset($vars[$var_name]) && $vars[$var_name]) {
            return $content;
        }
        return '';
    }, $template);
    
    // 处理 Mustache 风格的反向条件语句 {{^variable}}...{{/variable}}
    $template = preg_replace_callback('/\{\{\^(\w+)\}\}(.*?)\{\{\/\1\}\}/s', function($matches) use ($vars) {
        $var_name = $matches[1];
        $content = $matches[2];
        // 如果变量不存在或为假值，返回内容
        if (!isset($vars[$var_name]) || !$vars[$var_name]) {
            return $content;
        }
        return '';
    }, $template);
    
    // 替换普通占位符
    foreach ($vars as $key => $value) {
        if ($escape && !in_array($key, array('theme_color', 'blog_url', 'post_url', 'comment_url', 'reset_url', 'login_url', 'feedback_manage_url', 'feedback_view_url'))) {
            $value = esc_html($value);
        }
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    
    return $template;
}

/**
 * 渲染完整邮件
 * 
 * @param string $type 邮件类型
 * @param array $vars 模板变量
 * @return array 包含 subject 和 html 的数组
 */
function argon_render_email_template($type, $vars = array()) {
    $config = argon_get_email_template_config($type);
    
    // 替换主题中的占位符
    $subject = argon_replace_placeholders($config['subject'], $vars, true);
    
    // 替换内容中的占位符（内容中的 URL 不转义）
    $content = argon_replace_placeholders($config['content'], $vars, false);
    
    // 获取基础模板并替换
    $base_template = argon_get_email_base_template();
    $html = str_replace('{{email_subject}}', esc_html($subject), $base_template);
    $html = str_replace('{{email_content}}', $content, $html);
    
    // 替换剩余的全局变量
    $html = argon_replace_placeholders($html, $vars, false);
    
    return array(
        'subject' => $subject,
        'html' => $html,
        'enabled' => $config['enabled'],
    );
}

/**
 * 发送邮件
 * 
 * @param string $to 收件人邮箱
 * @param string $type 邮件类型
 * @param array $vars 模板变量
 * @return bool 发送是否成功
 */
function argon_send_email($to, $type, $vars = array()) {
    $rendered = argon_render_email_template($type, $vars);
    
    // 检查该类型邮件是否启用
    if (!$rendered['enabled']) {
        return false;
    }
    
    $settings = argon_get_email_settings();
    
    // 设置邮件头
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $settings['blog_name'] . ' <' . get_option('admin_email') . '>'
    );
    
    // 发送邮件
    $result = wp_mail($to, $rendered['subject'], $rendered['html'], $headers);
    
    if (!$result) {
        error_log('Argon Email: Failed to send ' . $type . ' email to ' . $to);
    }
    
    return $result;
}

/**
 * 兼容旧版 API - 渲染邮件（已废弃，保留向后兼容）
 */
function argon_render_email($content, $vars = array()) {
    $include_unsubscribe = isset($vars['unsubscribe_url']) && !empty($vars['unsubscribe_url']);
    $base_template = argon_get_email_base_template($include_unsubscribe);
    $html = str_replace('{{email_content}}', $content, $base_template);
    $html = str_replace('{{email_subject}}', isset($vars['subject']) ? esc_html($vars['subject']) : '', $html);
    $html = argon_replace_placeholders($html, $vars, false);
    return $html;
}

/**
 * AJAX 邮件预览接口
 */
add_action('wp_ajax_argon_preview_email', 'argon_preview_email_handler');
function argon_preview_email_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'argon_preview_email')) {
        wp_die('Invalid nonce');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }
    
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'comment_notify';
    
    // 生成示例数据
    $sample_vars = argon_get_sample_email_vars($type);
    
    $rendered = argon_render_email_template($type, $sample_vars);
    
    echo $rendered['html'];
    wp_die();
}

/**
 * 获取示例邮件变量
 */
function argon_get_sample_email_vars($type) {
    $settings = argon_get_email_settings();
    
    $common = array(
        'blog_name' => $settings['blog_name'],
        'theme_color' => $settings['theme_color'],
    );
    
    switch ($type) {
        case 'comment_notify':
            return array_merge($common, array(
                'post_title' => '示例文章标题',
                'post_url' => home_url('/sample-post/'),
                'commenter_name' => '评论者',
                'commenter_email' => 'commenter@example.com',
                'comment_content' => '这是评论内容的示例文本，展示了邮件模板中评论通知的样式效果。非常感谢您的精彩文章！',
                'comment_url' => home_url('/sample-post/#comment-1'),
                'comment_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            ));
            
        case 'reply_notify':
            return array_merge($common, array(
                'post_title' => '示例文章标题',
                'post_url' => home_url('/sample-post/'),
                'original_comment' => '这是原始评论的内容，用于展示邮件模板效果。',
                'replier_name' => '回复者',
                'replier_email' => 'replier@example.com',
                'reply_content' => '这是回复内容的示例文本，展示了邮件模板中回复通知的样式效果。感谢您的评论！',
                'comment_url' => home_url('/sample-post/#comment-2'),
                'reply_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            ));
            
        case 'todo_urge':
            return array_merge($common, array(
                'todo_content' => '这是一个示例 TODO 任务内容',
                'todo_id' => 'sample_todo_id',
                'urge_time' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            ));
            
        default:
            return $common;
    }
}

/**
 * 兼容旧版 API - 生成评论通知邮件内容（已废弃）
 */
function argon_get_comment_notify_content($vars) {
    $config = argon_get_email_template_config('comment_notify');
    return argon_replace_placeholders($config['content'], $vars, false);
}

/**
 * 兼容旧版 API - 生成回复通知邮件内容（已废弃）
 */
function argon_get_reply_notify_content($vars) {
    $config = argon_get_email_template_config('reply_notify');
    return argon_replace_placeholders($config['content'], $vars, false);
}
