<?php
/**
 * 简单的 Mermaid 配置管理测试脚本
 * 不依赖 PHPUnit，直接运行测试
 */

// 模拟 WordPress 环境
if (!function_exists('get_option')) {
	$_test_options = [];
	
	function get_option($option, $default = false) {
		global $_test_options;
		return isset($_test_options[$option]) ? $_test_options[$option] : $default;
	}
	
	function update_option($option, $value) {
		global $_test_options;
		$_test_options[$option] = $value;
		return true;
	}
	
	function delete_option($option) {
		global $_test_options;
		unset($_test_options[$option]);
		return true;
	}
	
	function __($text, $domain = 'default') {
		return $text;
	}
	
	function esc_url($url) {
		return $url;
	}
}

// 加载 functions.php 中的 Mermaid 配置函数
require_once __DIR__ . '/../functions.php';

// 测试计数器
$tests_run = 0;
$tests_passed = 0;
$tests_failed = 0;

// 测试辅助函数
function test_assert($condition, $test_name) {
	global $tests_run, $tests_passed, $tests_failed;
	$tests_run++;
	
	if ($condition) {
		$tests_passed++;
		echo "✓ {$test_name}\n";
		return true;
	} else {
		$tests_failed++;
		echo "✗ {$test_name}\n";
		return false;
	}
}

function test_assert_equals($expected, $actual, $test_name) {
	return test_assert($expected === $actual, $test_name . " (expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")");
}

function clear_mermaid_options() {
	$options = [
		'argon_enable_mermaid',
		'argon_mermaid_cdn_source',
		'argon_mermaid_cdn_custom_url',
		'argon_mermaid_theme',
		'argon_mermaid_use_local',
		'argon_mermaid_debug_mode'
	];
	
	foreach ($options as $option) {
		delete_option($option);
	}
}

echo "=== Mermaid 配置管理测试 ===\n\n";

// 测试 1: 获取配置选项（使用简短名称）
clear_mermaid_options();
update_option('argon_enable_mermaid', true);
test_assert_equals(true, argon_get_mermaid_option('enabled'), "测试 1: 获取配置选项（简短名称）");

// 测试 2: 获取配置选项（使用完整名称）
clear_mermaid_options();
update_option('argon_enable_mermaid', true);
test_assert_equals(true, argon_get_mermaid_option('argon_enable_mermaid'), "测试 2: 获取配置选项（完整名称）");

// 测试 3: 获取不存在的配置选项（返回默认值）
clear_mermaid_options();
test_assert_equals('default_value', argon_get_mermaid_option('non_existent', 'default_value'), "测试 3: 获取不存在的配置（返回默认值）");

// 测试 4: 保存配置选项（使用简短名称）
clear_mermaid_options();
argon_update_mermaid_option('enabled', true);
test_assert_equals(true, get_option('argon_enable_mermaid'), "测试 4: 保存配置选项（简短名称）");

// 测试 5: 保存配置选项（使用完整名称）
clear_mermaid_options();
argon_update_mermaid_option('argon_enable_mermaid', true);
test_assert_equals(true, get_option('argon_enable_mermaid'), "测试 5: 保存配置选项（完整名称）");

// 测试 6: 验证有效的 CDN URL
$valid_url = 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js';
test_assert(argon_validate_mermaid_cdn_url($valid_url), "测试 6: 验证有效的 CDN URL");

// 测试 7: 验证无效的 CDN URL（不是 URL）
test_assert(!argon_validate_mermaid_cdn_url('not-a-valid-url'), "测试 7: 验证无效的 CDN URL（不是 URL）");

// 测试 8: 验证无效的 CDN URL（不以 .js 结尾）
test_assert(!argon_validate_mermaid_cdn_url('https://example.com/mermaid.css'), "测试 8: 验证无效的 CDN URL（不以 .js 结尾）");

// 测试 9: 验证空 CDN URL
test_assert(!argon_validate_mermaid_cdn_url(''), "测试 9: 验证空 CDN URL");

// 测试 10: 验证无效协议的 CDN URL
test_assert(!argon_validate_mermaid_cdn_url('ftp://example.com/mermaid.min.js'), "测试 10: 验证无效协议的 CDN URL");

// 测试 11: 获取 Mermaid 主题（配置为 default）
clear_mermaid_options();
update_option('argon_mermaid_theme', 'default');
test_assert_equals('default', argon_get_mermaid_theme(), "测试 11: 获取 Mermaid 主题（default）");

// 测试 12: 获取 Mermaid 主题（配置为 dark）
clear_mermaid_options();
update_option('argon_mermaid_theme', 'dark');
test_assert_equals('dark', argon_get_mermaid_theme(), "测试 12: 获取 Mermaid 主题（dark）");

// 测试 13: 获取 Mermaid 主题（配置为 auto）
clear_mermaid_options();
update_option('argon_mermaid_theme', 'auto');
test_assert_equals('default', argon_get_mermaid_theme(), "测试 13: 获取 Mermaid 主题（auto 模式返回 default）");

// 测试 14: 获取默认配置
$defaults = argon_get_mermaid_default_config();
test_assert(is_array($defaults), "测试 14: 获取默认配置（返回数组）");
test_assert(isset($defaults['enabled']), "测试 14a: 默认配置包含 enabled");
test_assert_equals('jsdelivr', $defaults['cdn_source'], "测试 14b: 默认 CDN 来源为 jsdelivr");
test_assert_equals('auto', $defaults['theme'], "测试 14c: 默认主题为 auto");

// 测试 15: 验证有效的配置
$valid_settings = [
	'enabled' => true,
	'cdn_source' => 'jsdelivr',
	'theme' => 'default',
	'use_local' => false,
	'debug_mode' => false
];
$errors = argon_validate_mermaid_settings($valid_settings);
test_assert(empty($errors), "测试 15: 验证有效的配置（无错误）");

// 测试 16: 验证无效的 CDN 来源
$invalid_settings = ['cdn_source' => 'invalid_source'];
$errors = argon_validate_mermaid_settings($invalid_settings);
test_assert(!empty($errors), "测试 16: 验证无效的 CDN 来源（有错误）");

// 测试 17: 验证自定义 CDN 但 URL 为空
$invalid_settings = [
	'cdn_source' => 'custom',
	'custom_cdn_url' => ''
];
$errors = argon_validate_mermaid_settings($invalid_settings);
test_assert(!empty($errors), "测试 17: 验证自定义 CDN 但 URL 为空（有错误）");

// 测试 18: 验证自定义 CDN 但 URL 无效
$invalid_settings = [
	'cdn_source' => 'custom',
	'custom_cdn_url' => 'not-a-url'
];
$errors = argon_validate_mermaid_settings($invalid_settings);
test_assert(!empty($errors), "测试 18: 验证自定义 CDN 但 URL 无效（有错误）");

// 测试 19: 验证无效的主题名称
$invalid_settings = ['theme' => 'invalid_theme'];
$errors = argon_validate_mermaid_settings($invalid_settings);
test_assert(!empty($errors), "测试 19: 验证无效的主题名称（有错误）");

// 测试 20: 初始化默认配置
clear_mermaid_options();
argon_init_mermaid_config();
test_assert_equals(false, get_option('argon_enable_mermaid'), "测试 20: 初始化默认配置（enabled 为 false）");
test_assert_equals('jsdelivr', get_option('argon_mermaid_cdn_source'), "测试 20a: 初始化默认配置（cdn_source 为 jsdelivr）");

// 测试 21: 初始化不覆盖已有配置
clear_mermaid_options();
update_option('argon_enable_mermaid', true);
argon_init_mermaid_config();
test_assert_equals(true, get_option('argon_enable_mermaid'), "测试 21: 初始化不覆盖已有配置");

// 测试 22: 获取所有配置选项
clear_mermaid_options();
update_option('argon_enable_mermaid', true);
update_option('argon_mermaid_cdn_source', 'unpkg');
$options = argon_get_all_mermaid_options();
test_assert(is_array($options), "测试 22: 获取所有配置选项（返回数组）");
test_assert_equals(true, $options['enabled'], "测试 22a: 获取所有配置（enabled 正确）");
test_assert_equals('unpkg', $options['cdn_source'], "测试 22b: 获取所有配置（cdn_source 正确）");

// 测试 23: 批量更新配置（成功）
clear_mermaid_options();
$settings = [
	'enabled' => true,
	'cdn_source' => 'jsdelivr',
	'theme' => 'dark'
];
$result = argon_update_mermaid_settings($settings);
test_assert($result['success'], "测试 23: 批量更新配置（成功）");
test_assert(empty($result['errors']), "测试 23a: 批量更新配置（无错误）");
test_assert_equals(true, get_option('argon_enable_mermaid'), "测试 23b: 批量更新配置（enabled 已保存）");
test_assert_equals('dark', get_option('argon_mermaid_theme'), "测试 23c: 批量更新配置（theme 已保存）");

// 测试 24: 批量更新配置（失败）
clear_mermaid_options();
$settings = ['cdn_source' => 'invalid_source'];
$result = argon_update_mermaid_settings($settings);
test_assert(!$result['success'], "测试 24: 批量更新配置（失败）");
test_assert(!empty($result['errors']), "测试 24a: 批量更新配置（有错误信息）");

// 输出测试结果
echo "\n=== 测试结果 ===\n";
echo "总计: {$tests_run} 个测试\n";
echo "通过: {$tests_passed} 个\n";
echo "失败: {$tests_failed} 个\n";

if ($tests_failed === 0) {
	echo "\n✓ 所有测试通过！\n";
	exit(0);
} else {
	echo "\n✗ 有测试失败！\n";
	exit(1);
}
