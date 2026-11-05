<?php
/** 合并模块化设置页的模板与静态资源，用于已有契约测试。 */
function argon_test_settings_source($root) {
	$files = array_merge(array($root . '/settings.php', $root . '/inc/admin/settings-layout.php'), glob($root . '/inc/admin/settings/*.php'), array($root . '/assets/js/settings-admin.js', $root . '/assets/css/settings-admin.css'));
	return implode("\n", array_map('file_get_contents', $files));
}
