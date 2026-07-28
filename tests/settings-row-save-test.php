<?php

/**
 * 设置页逐项保存与层级契约测试。
 *
 * 运行：php tests/settings-row-save-test.php
 */

$root = dirname(__DIR__);
$source = file_get_contents($root . '/settings.php');
$failures = array();

function argon_settings_row_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

argon_settings_row_assert(strpos($source, 'class="submit argon-settings-submit"') === false, '设置页底部仍保留整页保存区。');
argon_settings_row_assert(
	strpos($source, 'argon-row-save') !== false
	&& strpos($source, 'argon-row-undo') !== false
	&& strpos($source, 'action", "argon_save_themeoption_row"') !== false,
	'设置项没有按行提供保存、撤销或 AJAX 请求。'
);
argon_settings_row_assert(
	strpos($source, "add_action('wp_ajax_argon_save_themeoption_row'") !== false
	&& strpos($source, "\$_POST['argon_partial_themeoptions'] = 'true';") !== false
	&& strpos($source, 'argon_is_partial_themeoptions_update()') !== false,
	'后端没有以部分更新模式处理逐项保存。'
);
argon_settings_row_assert(
	strpos($source, '$sensitiveControls.filter(":checkbox, :radio").prop("checked", false);') !== false
	&& strpos($source, '$sensitiveControls.not(":checkbox, :radio").val("");') !== false,
	'敏感字段保存后没有分别重置动作复选框和密码输入框。'
);
argon_settings_row_assert(substr_count($source, 'id="section-advanced"') === 1, '高级设置仍包含重复空白标题。');
argon_settings_row_assert(
	preg_match('/<h3\s+id="subsection-/i', $source) === 0
	&& preg_match('/<h4\s+id="subsection-/i', $source) === 1,
	'设置页 subsection 没有统一为三级 h4。'
);
argon_settings_row_assert(
	strpos($source, 'word-break: keep-all;') !== false
	&& strpos($source, 'white-space: nowrap;') !== false,
	'设置项目名称仍可能在单个词或中文字符中被强制换行。'
);
argon_settings_row_assert(
	strpos($source, 'margin: 10px 0 0 auto;') !== false
	&& strpos($source, 'justify-content: flex-end;') !== false,
	'窄屏保存与撤销按钮没有在当前设置项 td 内靠右。'
);
argon_settings_row_assert(
	strpos($source, 'name="argon_panic_mode_date_ranges_present"') !== false
	&& strpos($source, 'data-argon-dynamic-container') !== false
	&& strpos($source, 'argonInitialHtml') !== false
	&& substr_count($source, "trigger('change')") >= 3,
	'动态日期范围没有空提交哨兵、结构快照或脏状态通知。'
);

// 在最小 WordPress 桩环境中实际执行一次部分更新，确认未提交字段不会被写空。
$argon_settings_updates = array();
if (!function_exists('add_action')) {
	function add_action($hook, $callback) {}
}
if (!function_exists('wp_unslash')) {
	function wp_unslash($value) { return $value; }
}
if (!function_exists('wp_verify_nonce')) {
	function wp_verify_nonce($nonce, $action) { return true; }
}
if (!function_exists('sanitize_text_field')) {
	function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
}
if (!function_exists('update_option')) {
	function update_option($name, $value, $autoload = null) {
		global $argon_settings_updates;
		$argon_settings_updates[$name] = $value;
		return true;
	}
}

$_POST = array(
	'update_themeoptions' => 'true',
	'argon_update_themeoptions_nonce' => 'test-nonce',
	'argon_partial_themeoptions' => 'true',
	'argon_toolbar_title' => 'Changed title',
);
ob_start();
require $root . '/settings.php';
ob_end_clean();
argon_settings_row_assert(
	$argon_settings_updates === array('argon_toolbar_title' => 'Changed title'),
	'部分更新实际执行时改写了本行之外的设置：' . implode(', ', array_keys($argon_settings_updates))
);

$argon_settings_updates = array();
$_POST = array(
	'update_themeoptions' => 'true',
	'argon_update_themeoptions_nonce' => 'test-nonce',
	'argon_partial_themeoptions' => 'true',
	'argon_panic_mode_date_ranges_present' => 'true',
);
argon_update_themeoptions();
argon_settings_row_assert(
	$argon_settings_updates === array('argon_panic_mode_date_ranges' => '[]'),
	'清空全部动态日期范围时没有保存空数组。'
);

$argon_settings_updates = array();
$_POST['panic_date_name'] = array('测试日期');
$_POST['panic_date_start'] = array('2026-01-01');
$_POST['panic_date_end'] = array('2026-01-02');
argon_update_themeoptions();
$saved_ranges = isset($argon_settings_updates['argon_panic_mode_date_ranges'])
	? json_decode($argon_settings_updates['argon_panic_mode_date_ranges'], true)
	: null;
argon_settings_row_assert(
	is_array($saved_ranges)
	&& isset($saved_ranges[0]['name'])
	&& $saved_ranges[0]['name'] === '测试日期',
	'新增动态日期范围没有通过部分更新保存。'
);
$_POST = array();

if ($failures) {
	fwrite(STDERR, "设置页逐项保存测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "设置页逐项保存测试通过。\n";
