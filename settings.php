<?php
require_once __DIR__ . '/inc/admin/settings-layout.php';

function themeoptions_page() {
	$sections = argon_settings_sections();
	?>
	<div class="wrap argon-settings" id="argon-settings">
		<header class="argon-settings-header">
			<div><p class="argon-settings-eyebrow">ARGON THEME</p><h1><?php esc_html_e('主题设置', 'argon'); ?></h1><p class="description"><?php esc_html_e('按功能查找设置，修改后在当前项目保存或撤销。', 'argon'); ?></p></div>
			<div class="argon-settings-tools">
				<button type="button" class="button" id="argon-settings-export"><?php esc_html_e('导出设置', 'argon'); ?></button>
				<button type="button" class="button" id="argon-settings-import"><?php esc_html_e('导入设置', 'argon'); ?></button>
			</div>
		</header>
		<div class="argon-settings-toolbar">
			<div class="argon-settings-search-box">
				<label for="argon-settings-search"><?php esc_html_e('搜索设置', 'argon'); ?></label>
				<input type="search" id="argon-settings-search" placeholder="<?php esc_attr_e('搜索名称、功能说明或配置名…', 'argon'); ?>" autocomplete="off" aria-controls="argon-settings-table">
				<button type="button" class="button" id="argon-settings-clear" hidden><?php esc_html_e('清除', 'argon'); ?></button>
			</div>
			<div class="argon-settings-save-state">
				<span id="argon-settings-dirty-status" role="status" aria-live="polite"></span>
				<button type="button" class="button" id="argon-settings-show-dirty" aria-pressed="false" disabled><?php esc_html_e('查看待保存', 'argon'); ?></button>
			</div>
		</div>
		<div class="argon-settings-layout">
			<nav class="argon-settings-nav" aria-label="<?php esc_attr_e('设置分类', 'argon'); ?>">
				<a href="#all" data-settings-category="all"><?php esc_html_e('全部设置', 'argon'); ?><span class="argon-settings-count"></span></a>
				<?php foreach ($sections as $section_key => $section): ?>
					<a href="#<?php echo esc_attr($section['anchor']); ?>" data-settings-category="<?php echo esc_attr($section_key); ?>"><?php echo esc_html($section['title']); ?><span class="argon-settings-count"></span><span class="argon-settings-dirty-count" hidden></span></a>
				<?php endforeach; ?>
				<p class="description"><?php esc_html_e('搜索覆盖全部分类。切换分类会保留尚未保存的修改。', 'argon'); ?></p>
			</nav>
			<div class="argon-settings-content">
				<div class="argon-settings-context"><strong id="argon-settings-view-title"><?php esc_html_e('全部设置', 'argon'); ?></strong><span id="argon-settings-result-count" role="status" aria-live="polite"></span></div>
				<nav id="argon-settings-outline" aria-label="<?php esc_attr_e('当前分类目录', 'argon'); ?>"></nav>
				<p class="argon-settings-empty" id="argon-settings-empty" hidden><?php esc_html_e('没有匹配的设置。可尝试更短的关键词，或清除搜索与待保存筛选。', 'argon'); ?></p>
				<form method="POST" action="" id="main_form">
					<input type="hidden" name="update_themeoptions" value="true">
					<?php wp_nonce_field('argon_update_themeoptions', 'argon_update_themeoptions_nonce'); ?>
					<table class="form-table" id="argon-settings-table">
						<colgroup><col class="argon-settings-label-column"><col></colgroup>
						<?php foreach ($sections as $section_key => $section): ?>
							<tbody class="argon-settings-section" data-settings-section="<?php echo esc_attr($section_key); ?>" aria-labelledby="<?php echo esc_attr($section['anchor']); ?>">
								<tr class="argon-settings-section-heading"><th class="subtitle" colspan="2"><h2 id="<?php echo esc_attr($section['anchor']); ?>"><?php echo esc_html($section['title']); ?></h2><p><?php echo esc_html($section['description']); ?></p></th></tr>
								<?php require __DIR__ . '/inc/admin/settings/' . $section_key . '.php'; ?>
							</tbody>
						<?php endforeach; ?>
					</table>
					<noscript><p><?php esc_html_e('启用 JavaScript 可使用分类、搜索及逐项保存。', 'argon'); ?></p><button type="submit" class="button button-primary"><?php esc_html_e('保存设置', 'argon'); ?></button></noscript>
				</form>
			</div>
		</div>
		<dialog id="argon-settings-export-dialog" aria-labelledby="argon-settings-export-title">
			<h2 id="argon-settings-export-title"><?php esc_html_e('导出设置', 'argon'); ?></h2>
			<p><?php esc_html_e('复制并保存下方 JSON。API 密钥等敏感字段不会导出。', 'argon'); ?></p>
			<textarea id="exported_settings_json" rows="14" readonly aria-label="<?php esc_attr_e('设置 JSON', 'argon'); ?>"></textarea>
			<button type="button" class="button" id="argon-settings-export-close"><?php esc_html_e('关闭', 'argon'); ?></button>
		</dialog>
	</div>
	<?php
}

add_action('admin_menu', 'themeoptions_admin_menu');

function argon_is_partial_themeoptions_update() {
	return isset($_POST['argon_partial_themeoptions'])
		&& wp_unslash($_POST['argon_partial_themeoptions']) === 'true';
}

function argon_themeoptions_request_has_any($names) {
	foreach ((array)$names as $name) {
		if (array_key_exists($name, $_POST)) return true;
	}
	return false;
}

function argon_update_option($name){
	if (argon_is_partial_themeoptions_update() && !array_key_exists($name, $_POST)) return false;

	update_option($name, htmlspecialchars(stripslashes(isset($_POST[$name]) ? $_POST[$name] : '')));
	return true;

}

function argon_update_option_allow_tags($name){
	if (argon_is_partial_themeoptions_update() && !array_key_exists($name, $_POST)) return false;

	update_option($name, stripslashes(isset($_POST[$name]) ? $_POST[$name] : ''));
	return true;

}

function argon_update_option_checkbox($name){
	if (argon_is_partial_themeoptions_update() && !array_key_exists($name, $_POST)) return false;

	if (isset($_POST[$name]) && $_POST[$name] == 'true'){

		update_option($name, 'true');

	}else{

		update_option($name, 'false');

	}
	return true;

}

function argon_update_friend_link_worker_settings(){
	$partial = argon_is_partial_themeoptions_update();

	if (!$partial || array_key_exists('argon_friend_link_worker_enabled', $_POST)) {
		$enabled = isset($_POST['argon_friend_link_worker_enabled'])
			&& wp_unslash($_POST['argon_friend_link_worker_enabled']) === 'true'
			? 'true'
			: 'false';
		update_option('argon_friend_link_worker_enabled', $enabled);
	}

	if (!$partial || array_key_exists('argon_friend_link_worker_url', $_POST)) {
		$worker_url = isset($_POST['argon_friend_link_worker_url'])
			? esc_url_raw(trim(wp_unslash($_POST['argon_friend_link_worker_url'])))
			: '';
		update_option('argon_friend_link_worker_url', rtrim($worker_url, '/'));
	}

	$clear_secrets = isset($_POST['argon_friend_link_worker_clear_secrets'])
		&& wp_unslash($_POST['argon_friend_link_worker_clear_secrets']) === 'true';
	$secret_options = array(
		'argon_friend_link_submit_hmac_secret',
		'argon_friend_link_callback_hmac_secret',
	);
	if ($clear_secrets) {
		foreach ($secret_options as $option_name) delete_option($option_name);
		return;
	}

	foreach ($secret_options as $option_name) {
		if (!isset($_POST[$option_name])) continue;
		$value = sanitize_text_field(wp_unslash($_POST[$option_name]));
		if ($value !== '') update_option($option_name, $value, false);
	}

}

function argon_update_themeoptions(){

	if (!isset($_POST['update_themeoptions'])){

		return;

	}

	if ($_POST['update_themeoptions'] == 'true'){

		if (!isset($_POST['argon_update_themeoptions_nonce'])){

			return;

		}

		$nonce = $_POST['argon_update_themeoptions_nonce'];

		if (!wp_verify_nonce($nonce, 'argon_update_themeoptions')){

			return;

		}

		//配置项

			argon_update_option('argon_attachment_allowed_extensions');

			argon_update_option('argon_attachment_max_size');

			argon_update_option('argon_attachment_captcha_mode');

		argon_update_option('argon_toolbar_icon');

		argon_update_option('argon_toolbar_icon_link');

		argon_update_option('argon_toolbar_title');

		argon_update_option('argon_sidebar_banner_title');

		argon_update_option('argon_sidebar_banner_subtitle');

		argon_update_option('argon_sidebar_auther_name');

		argon_update_option('argon_sidebar_auther_image');

		argon_update_option('argon_sidebar_author_description');

		argon_update_option('argon_show_todo_list');

		argon_update_option('argon_show_duolingo_streak');
		argon_update_option('argon_duolingo_username');
		argon_update_option('argon_bangumi_enabled');
		argon_update_option('argon_bangumi_uid');
		argon_update_option('argon_show_rss_link');

		if (!argon_is_partial_themeoptions_update() || argon_themeoptions_request_has_any(array('argon_show_duolingo_streak', 'argon_duolingo_username'))) {
			delete_option('argon_duolingo_email');
			delete_transient('argon_duo_login_error');
		}

		argon_update_option('argon_banner_title');

		argon_update_option('argon_banner_background_url');

		argon_update_option('argon_banner_background_color_type');

		argon_update_option_checkbox('argon_banner_background_hide_shapes');

		argon_update_option('argon_enable_smoothscroll_type');

		argon_update_option('argon_gravatar_cdn');

		argon_update_option_allow_tags('argon_footer_html');

		argon_update_option('argon_show_readingtime');

		argon_update_option('argon_reading_speed');

		argon_update_option('argon_reading_speed_en');

		argon_update_option('argon_reading_speed_code');

		argon_update_option('argon_show_sharebtn');

		argon_update_option('argon_enable_timezone_fix');

		argon_update_option('argon_donate_qrcode_url');

		argon_update_option('argon_hide_shortcode_in_preview');

		argon_update_option('argon_show_thumbnail_in_banner_in_content_page');

		argon_update_option('argon_update_source');

		argon_update_option_checkbox('argon_enable_hot_reload');

		argon_update_option_checkbox('argon_hot_reload_auto_refresh');

		argon_update_option_checkbox('argon_hot_reload_frontend_notice');

		argon_update_option_checkbox('argon_enable_debug_console');

		argon_update_option('argon_enable_into_article_animation');

		argon_update_option('argon_disable_pjax_animation');

		// 动画效果相关配置

		argon_update_option('argon_fab_show_darkmode_button');

		argon_update_option('argon_fab_show_settings_button');

		argon_update_option('argon_fab_show_gotocomment_button');

		argon_update_option('argon_show_headindex_number');

		argon_update_option('argon_theme_color');

		argon_update_option_checkbox('argon_show_customize_theme_color_picker');

		argon_update_option_allow_tags('argon_seo_description');

		argon_update_option('argon_seo_keywords');

		argon_update_option('argon_enable_mobile_scale');

		argon_update_option('argon_page_background_url');

		argon_update_option('argon_page_background_dark_url');

		argon_update_option('argon_page_background_opacity');

		argon_update_option('argon_post_background_opacity');

		argon_update_option('argon_card_blur');

		argon_update_option('argon_card_saturate');

		argon_update_option('argon_page_background_banner_style');

		argon_update_option('argon_hide_name_email_site_input');

		argon_update_option('argon_comment_captcha_mode');

		argon_update_option('argon_todo_captcha_mode');

		argon_update_option('argon_need_captcha');

		argon_update_option('argon_captcha_type');

		argon_update_option('argon_geetest_captcha_id');

		argon_update_option('argon_geetest_captcha_key');

		argon_update_option('argon_geetest_api_server');

		argon_update_option('argon_get_captcha_by_ajax');

		// 评论速率限制相关配置

		argon_update_option_checkbox('argon_rate_limit_enable');

		argon_update_option('argon_rate_limit_window');

		argon_update_option('argon_rate_limit_max_count');

		argon_update_option('argon_rate_limit_min_interval');

		// 友情链接相关配置
		argon_update_option('argon_friend_link_slogan');
		argon_update_option('argon_friend_link_welcome');
		argon_update_option('argon_friend_link_allow_apply');
		argon_update_option('argon_friend_link_captcha');
		argon_update_option('argon_feedback_captcha_mode');
		argon_update_option('argon_feedback_notify_admin');
		argon_update_option('argon_feedback_notify_user');
		argon_update_option('argon_global_blocked_ips');
		argon_update_option('argon_flink_apply_limit');
		argon_update_option('argon_flink_apply_period');
		argon_update_option('argon_feedback_submit_limit');
		argon_update_option('argon_feedback_submit_period');
		argon_update_option('argon_feedback_upload_limit');
		argon_update_option('argon_feedback_upload_period');
		argon_update_option('argon_feedback_image_max_count');
		argon_update_option('argon_feedback_image_max_size_kb');
		argon_update_option('argon_feedback_image_max_dimension');
		argon_update_option('argon_feedback_image_source_max_mb');
		argon_update_option('argon_friend_link_hide_no_backlink');
		argon_update_option_allow_tags('argon_friend_link_requirements');
		argon_update_friend_link_worker_settings();

		// 邮件模板相关配置
		argon_update_option('argon_email_theme_color');
		argon_update_option('argon_email_logo_url');
		argon_update_option('argon_email_blog_name');
		argon_update_option('argon_email_footer_text');
		
		// 保存社交链接为数组（自动补全完整 URL）；逐项保存时保留本行未提交的平台。
		$email_social_fields = array(
			'twitter' => 'argon_email_social_twitter',
			'github' => 'argon_email_social_github',
			'weibo' => 'argon_email_social_weibo',
			'bilibili' => 'argon_email_social_bilibili',
		);
		if (!argon_is_partial_themeoptions_update() || argon_themeoptions_request_has_any(array_values($email_social_fields))) {
			$social_links = argon_is_partial_themeoptions_update() ? get_option('argon_email_social_links', array()) : array();
			if (!is_array($social_links)) $social_links = array();
			foreach ($email_social_fields as $network => $field_name) {
				if (!argon_is_partial_themeoptions_update() || array_key_exists($field_name, $_POST)) {
					$social_links[$network] = argon_normalize_social_url($network, isset($_POST[$field_name]) ? sanitize_text_field($_POST[$field_name]) : '');
				}
			}
			update_option('argon_email_social_links', $social_links);
		}

		// 保存邮件模板配置
		$email_types = array('comment_notify', 'spam_notify', 'reply_notify', 'todo_urge');
		foreach ($email_types as $type) {
			// 保存启用状态
			argon_update_option_checkbox('argon_email_template_' . $type . '_enabled');
			// 保存主题
			argon_update_option('argon_email_template_' . $type . '_subject');
			// 保存内容（允许 HTML）
			argon_update_option_allow_tags('argon_email_template_' . $type . '_content');
		}

		argon_update_option('argon_hide_footer_author');

		argon_update_option('argon_show_git_badge');

		argon_update_option('argon_card_radius');

		argon_update_option('argon_comment_avatar_vcenter');

		argon_update_option('argon_pjax_disabled');

		argon_update_option('argon_comment_allow_markdown');

		argon_update_option('argon_comment_allow_editing');

		argon_update_option('argon_comment_allow_privatemode');

		argon_update_option('argon_comment_allow_mailnotice');

		argon_update_option_checkbox('argon_comment_mailnotice_checkbox_checked');

		argon_update_option('argon_comment_pagination_type');

		argon_update_option('argon_who_can_visit_comment_edit_history');

		argon_update_option('argon_home_show_shuoshuo');

		argon_update_option('argon_enable_search_filters');

		argon_update_option('argon_search_filters_type');

		argon_update_option('argon_darkmode_autoswitch');

		argon_update_option('argon_enable_amoled_dark');

		argon_update_option('argon_outdated_info_time_type');

		argon_update_option('argon_outdated_info_days');

		argon_update_option('argon_outdated_info_tip_type');

		argon_update_option('argon_outdated_info_tip_content');

		argon_update_option_checkbox('argon_show_toolbar_mask');

		argon_update_option('argon_enable_banner_title_typing_effect');

		argon_update_option('argon_banner_typing_effect_interval');

		argon_update_option('argon_page_layout');

		argon_update_option('argon_article_list_layout');

		argon_update_option('argon_article_list_layout_mobile');

		argon_update_option('argon_enable_pangu');

		argon_update_option('argon_comment_ua');

		argon_update_option('argon_wp_path');

		argon_update_option('argon_dateformat');

		argon_update_option('argon_font');

		argon_update_option('argon_card_shadow');

		argon_update_option('argon_enable_code_highlight');

		argon_update_option('argon_code_highlight_hide_linenumber');

		argon_update_option('argon_code_highlight_transparent_linenumber');

		argon_update_option('argon_code_highlight_break_line');

		argon_update_option('argon_code_theme');

		argon_update_option('argon_comment_enable_qq_avatar');

		argon_update_option('argon_enable_login_css');

		argon_update_option('argon_hide_categories');

		argon_update_option('argon_article_meta');

		argon_update_option('argon_fold_long_comments');

		argon_update_option('argon_fold_long_shuoshuo');

		argon_update_option('argon_first_image_as_thumbnail_by_default');

		argon_update_option('argon_enable_headroom');

		argon_update_option('argon_comment_emotion_keyboard');

		argon_update_option_allow_tags('argon_additional_content_after_post');

		argon_update_option('argon_related_post');

		argon_update_option('argon_related_post_sort_orderby');

		argon_update_option('argon_related_post_sort_order');

		argon_update_option('argon_related_post_limit');

		argon_update_option('argon_article_header_style');

		argon_update_option('argon_text_gravatar');

		argon_update_option('argon_disable_googlefont');

		argon_update_option('argon_disable_codeblock_style');

		argon_update_option('argon_reference_list_title');

		argon_update_option('argon_trim_words_count');

		argon_update_option('argon_enable_comment_upvote');

		argon_update_option('argon_article_list_waterflow');

		argon_update_option('argon_banner_size');

		argon_update_option('argon_toolbar_blur');

		argon_update_option('argon_toolbar_style');

		argon_update_option('argon_toolbar_shadow');

		argon_update_option('argon_toolbar_border');

		argon_update_option('argon_toolbar_compact');

		argon_update_option('argon_toolbar_center');

		argon_update_option('argon_toolbar_links_left');

		argon_update_option('argon_toolbar_links_right');

		argon_update_option('argon_archives_timeline_show_month');

		argon_update_option('argon_archives_timeline_url');

		argon_update_option('argon_enable_immersion_color');

		argon_update_option('argon_enable_comment_pinning');

		argon_update_option('argon_show_comment_parent_info');



		//LazyLoad 相关

		argon_update_option('argon_enable_lazyload');

		argon_update_option('argon_lazyload_effect');

		argon_update_option('argon_lazyload_threshold');

		argon_update_option('argon_lazyload_loading_style');

		//图片缩放预览相关

		argon_update_option('argon_enable_fancybox');



		//数学公式相关配置项

		argon_update_option('argon_math_render');

		argon_update_option('argon_mathjax_cdn_url');

		argon_update_option('argon_mathjax_v2_cdn_url');

		argon_update_option('argon_katex_cdn_url');

		argon_update_option_checkbox('argon_mathjax3_use_local');

		argon_update_option_checkbox('argon_mathjax2_use_local');

		argon_update_option_checkbox('argon_katex_use_local');



		//页头页尾脚本

		argon_update_option_allow_tags('argon_custom_html_head');

		argon_update_option_allow_tags('argon_custom_html_foot');



		//公告

		argon_update_option_allow_tags('argon_sidebar_announcement');

	//AI 摘要
	argon_update_option_checkbox('argon_ai_summary_enable');
	argon_update_option('argon_ai_summary_active_api');
	argon_update_option('argon_ai_summary_prompt');
	argon_update_option('argon_ai_summary_thinking_mode');
	argon_update_option('argon_ai_summary_exclude_posts');

	//AI 文章 SEO 优化
	argon_update_option_checkbox('argon_ai_seo_enable');
	argon_update_option('argon_ai_seo_active_api');
	argon_update_option('argon_ai_seo_auto_mode');
	argon_update_option_checkbox('argon_ai_seo_sync_excerpt');
	argon_update_option_checkbox('argon_ai_seo_sync_tags');
	argon_update_option('argon_ai_seo_tag_mode');
	argon_update_option('argon_ai_seo_max_keywords');
	argon_update_option_allow_tags('argon_ai_seo_custom_prompt');
	
	//AI 垃圾评论识别
	argon_update_option_checkbox('argon_comment_spam_detection_enable');
	argon_update_option('argon_ai_spam_active_api');
	argon_update_option('argon_comment_spam_detection_mode');
	argon_update_option('argon_comment_spam_detection_sample_rate');
	argon_update_option('argon_comment_spam_detection_trigger_keywords');
	argon_update_option('argon_comment_spam_detection_keywords');
	argon_update_option_checkbox('argon_comment_spam_detection_exclude_logged_in');
	argon_update_option('argon_comment_spam_detection_whitelist');
	argon_update_option('argon_comment_spam_detection_auto_action');
	argon_update_option('argon_comment_spam_detection_confidence_threshold');
	argon_update_option('argon_comment_spam_detection_prompt_mode');
	argon_update_option('argon_comment_spam_detection_custom_prompt');
	argon_update_option_checkbox('argon_comment_spam_blacklist_notify');
	
	// 怕死模式设置
	argon_update_option_checkbox('argon_panic_mode_enable');
	argon_update_option('argon_panic_mode_tags');
	argon_update_option('argon_panic_mode_intensity');
	
	// 处理日期范围
	if (!argon_is_partial_themeoptions_update() || argon_themeoptions_request_has_any(array('argon_panic_mode_date_ranges_present', 'panic_date_name', 'panic_date_start', 'panic_date_end'))) {
		if (isset($_POST['panic_date_name']) && is_array($_POST['panic_date_name'])) {
			$date_ranges = array();
			$names = $_POST['panic_date_name'];
			$starts = isset($_POST['panic_date_start']) ? $_POST['panic_date_start'] : array();
			$ends = isset($_POST['panic_date_end']) ? $_POST['panic_date_end'] : array();

			foreach ($names as $index => $name) {
				if (!empty($name) && isset($starts[$index]) && isset($ends[$index])) {
					$date_ranges[] = array(
						'name' => sanitize_text_field($name),
						'start' => sanitize_text_field($starts[$index]),
						'end' => sanitize_text_field($ends[$index])
					);
				}
			}
			update_option('argon_panic_mode_date_ranges', json_encode($date_ranges));
		} else {
			update_option('argon_panic_mode_date_ranges', '[]');
		}
	}
	
	// 处理专项审查方向（多选）
	if (!argon_is_partial_themeoptions_update() || array_key_exists('argon_panic_mode_focus', $_POST)) {
		if (isset($_POST['argon_panic_mode_focus']) && is_array($_POST['argon_panic_mode_focus'])) {
			update_option('argon_panic_mode_focus', array_map('sanitize_text_field', $_POST['argon_panic_mode_focus']));
		} else {
			update_option('argon_panic_mode_focus', array());
		}
	}
	
	// 彩蛋系统配置保存
	$has_easter_egg_fields = !argon_is_partial_themeoptions_update();
	if (!$has_easter_egg_fields) {
		foreach (array_keys($_POST) as $post_key) {
			if (strpos($post_key, 'argon_easter_egg_') === 0) {
				$has_easter_egg_fields = true;
				break;
			}
		}
	}
	if ($has_easter_egg_fields) {
		$available_eggs = argon_get_available_easter_eggs();
		$configs = argon_is_partial_themeoptions_update() ? get_option('argon_easter_egg_configs', array()) : array();
		if (!is_array($configs)) $configs = array();
		foreach ($available_eggs as $egg_id => $egg_data) {
			$prefix = 'argon_easter_egg_' . $egg_id . '_';
			if (argon_is_partial_themeoptions_update() && !argon_themeoptions_request_has_any(array($prefix . 'enabled', $prefix . 'date', $prefix . 'type', $prefix . 'duration'))) continue;
			$enabled = isset($_POST[$prefix . 'enabled']) && $_POST[$prefix . 'enabled'] !== 'false';
			$date = isset($_POST[$prefix . 'date']) ? sanitize_text_field($_POST[$prefix . 'date']) : $egg_data['default_date'];
			$type = isset($_POST[$prefix . 'type']) ? sanitize_text_field($_POST[$prefix . 'type']) : $egg_data['default_type'];
			$duration = isset($_POST[$prefix . 'duration']) ? intval($_POST[$prefix . 'duration']) : 1;

			$configs[$egg_id] = array(
				'enabled' => $enabled,
				'date' => $date,
				'type' => $type,
				'duration' => $duration
			);
		}
		update_option('argon_easter_egg_configs', $configs);
		argon_clear_easter_egg_cache();
	}

	}

}

function argon_test_friend_link_worker_health($worker_url) {
	$worker_url = rtrim(esc_url_raw(trim((string)$worker_url)), '/');
	$scheme = strtolower((string)wp_parse_url($worker_url, PHP_URL_SCHEME));
	$host = (string)wp_parse_url($worker_url, PHP_URL_HOST);
	if ($worker_url === '' || $scheme !== 'https' || $host === '') {
		return new WP_Error('invalid_worker_url', __('Worker URL 必须是有效的 HTTPS 地址。', 'argon'));
	}

	$health_url = $worker_url . '/health';
	if (function_exists('wp_http_validate_url') && !wp_http_validate_url($health_url)) {
		return new WP_Error('unsafe_worker_url', __('Worker URL 未通过 WordPress 安全地址校验。', 'argon'));
	}

	$started_at = microtime(true);
	$response = wp_safe_remote_get($health_url, array(
		'timeout' => 8,
		'redirection' => 2,
		'limit_response_size' => 4096,
		'headers' => array('Accept' => 'application/json'),
	));
	$duration_ms = max(0, (int)round((microtime(true) - $started_at) * 1000));
	if (is_wp_error($response)) {
		return new WP_Error('worker_unreachable', sprintf(__('无法连接 Worker：%s', 'argon'), $response->get_error_message()));
	}

	$status = (int)wp_remote_retrieve_response_code($response);
	$payload = json_decode((string)wp_remote_retrieve_body($response), true);
	if ($status !== 200) {
		return new WP_Error('worker_http_error', sprintf(__('Worker /health 返回 HTTP %d。', 'argon'), $status));
	}
	if (!is_array($payload) || empty($payload['ok']) || !isset($payload['service']) || $payload['service'] !== 'friend-link-checker') {
		return new WP_Error('worker_invalid_health', __('Worker /health 响应格式或服务标识不正确。', 'argon'));
	}

	return array(
		'url' => $health_url,
		'duration_ms' => $duration_ms,
		'service' => $payload['service'],
	);
}

function argon_ajax_test_friend_link_worker() {
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => __('权限不足。', 'argon')), 403);
	}
	check_ajax_referer('argon_update_themeoptions', 'nonce');
	$worker_url = isset($_POST['worker_url']) ? wp_unslash($_POST['worker_url']) : '';
	$result = argon_test_friend_link_worker_health($worker_url);
	if (is_wp_error($result)) {
		wp_send_json_error(array('message' => $result->get_error_message()), 400);
	}
	wp_send_json_success(array(
		'message' => sprintf(__('连接正常（%d ms）', 'argon'), $result['duration_ms']),
		'health' => $result,
	));
}
add_action('wp_ajax_argon_test_friend_link_worker', 'argon_ajax_test_friend_link_worker');

function argon_ajax_save_themeoption_row() {
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => __('权限不足。', 'argon')), 403);
	}
	check_ajax_referer('argon_update_themeoptions', 'nonce');
	$_POST['update_themeoptions'] = 'true';
	$_POST['argon_update_themeoptions_nonce'] = isset($_POST['nonce']) ? $_POST['nonce'] : '';
	$_POST['argon_partial_themeoptions'] = 'true';
	argon_update_themeoptions();
	wp_send_json_success(array('message' => __('设置已保存。', 'argon')));
}
add_action('wp_ajax_argon_save_themeoption_row', 'argon_ajax_save_themeoption_row');

argon_update_themeoptions();

?>
