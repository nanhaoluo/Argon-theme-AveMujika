<?php
/** 设置页分类和资源注册；字段模板按功能保存在 settings/。 */
function argon_settings_sections() {
	return array(
		'general' => array('title' => __('站点基础', 'argon'), 'description' => __('设置站点搜索描述和日期显示。', 'argon'), 'anchor' => 'category-1'),
		'appearance' => array('title' => __('外观与配色', 'argon'), 'description' => __('主题色、夜间模式、字体、背景和界面样式。', 'argon'), 'anchor' => 'category-2'),
		'layout' => array('title' => __('布局与导航', 'argon'), 'description' => __('页面布局、顶栏、横幅、侧栏与页脚。', 'argon'), 'anchor' => 'category-layout'),
		'content' => array('title' => __('内容与阅读', 'argon'), 'description' => __('文章列表、内容预览、文章功能、说说和站内搜索。', 'argon'), 'anchor' => 'category-3'),
		'comments' => array('title' => __('评论与互动', 'argon'), 'description' => __('评论分页与提交、编辑权限、头像和反馈。', 'argon'), 'anchor' => 'category-4'),
		'ai' => array('title' => __('AI 与智能化', 'argon'), 'description' => __('统一 API 配置、文章摘要、SEO 与标签，以及评论智能审核。', 'argon'), 'anchor' => 'category-6'),
		'security' => array('title' => __('安全与验证', 'argon'), 'description' => __('验证码、适用场景和提交频率限制。', 'argon'), 'anchor' => 'category-7'),
		'mail' => array('title' => __('邮件通知', 'argon'), 'description' => __('邮件配色、社交链接和各类通知模板。', 'argon'), 'anchor' => 'category-mail'),
		'friends' => array('title' => __('友情链接', 'argon'), 'description' => __('友链页面、申请要求与 Worker 自动检测。', 'argon'), 'anchor' => 'category-friends'),
		'extensions' => array('title' => __('扩展与媒体', 'argon'), 'description' => __('代码高亮、数学公式、图片浏览、附件和彩蛋。', 'argon'), 'anchor' => 'category-5'),
		'performance' => array('title' => __('性能与加载', 'argon'), 'description' => __('页面局部刷新和图片懒加载。', 'argon'), 'anchor' => 'category-performance'),
		'advanced' => array('title' => __('高级与维护', 'argon'), 'description' => __('主题更新、缓存维护、调试、自定义脚本与兼容设置。', 'argon'), 'anchor' => 'category-8'),
	);
}

function argon_settings_admin_assets($hook) {
	if ($hook !== 'toplevel_page_functions.php') return;
	$uri = get_template_directory_uri();
	$dir = get_template_directory();
	wp_enqueue_script('jquery');
	wp_enqueue_media();
	wp_enqueue_script('argon-settings-dragula', $uri . '/assets/vendor/dragula/dragula.min.js', array('jquery'), '3.7.2', false);
	wp_enqueue_script('argon-motion', $uri . '/assets/js/argon-motion.js', array(), filemtime($dir . '/assets/js/argon-motion.js'), false);
	wp_enqueue_style('argon-settings', $uri . '/assets/css/settings-admin.css', array(), filemtime($dir . '/assets/css/settings-admin.css'));
	wp_enqueue_script('argon-settings', $uri . '/assets/js/settings-admin.js', array('jquery', 'argon-settings-dragula', 'argon-motion'), filemtime($dir . '/assets/js/settings-admin.js'), true);
	wp_localize_script('argon-settings', 'argonSettings', array(
		'i18n' => array(
			'save' => __('保存', 'argon'), 'undo' => __('撤销', 'argon'),
			'saving' => __('保存中…', 'argon'), 'saved' => __('已保存', 'argon'),
			'failed' => __('保存失败，请重试。', 'argon'), 'network' => __('网络请求失败，请重试。', 'argon'),
			'expired' => __('登录或安全校验已过期，请刷新页面后重试。', 'argon'),
			'changed' => __('已保存提交的内容，仍有新修改待保存。', 'argon'),
			'clean' => __('所有修改均已保存', 'argon'), 'dirty' => __('项修改待保存', 'argon'),
			'results' => __('项设置', 'argon'), 'all' => __('全部设置', 'argon'), 'pending' => __('待保存的设置', 'argon'),
			'importPrompt' => __('请输入导出的设置 JSON。导入后需逐项保存。', 'argon'),
			'imported' => __('设置已填入，尚未保存。请检查待保存的设置。', 'argon'),
			'importInvalid' => __('导入失败：请提供有效的 JSON 对象。', 'argon'),
			'importSkipped' => __('个字段未导入（未知字段、敏感字段或值无效）。', 'argon'),
			'testing' => __('测试中…', 'argon'), 'invalidWorker' => __('Worker 返回了无效响应', 'argon'),
			'workerFailed' => __('连通性测试请求失败', 'argon'),
		),
	));
}
add_action('admin_enqueue_scripts', 'argon_settings_admin_assets');
