<?php

/**
 * B 站追番公开访问与旧凭证清理契约测试。
 *
 * 运行：php tests/bangumi-public-access-test.php
 */

$root = dirname(__DIR__);
$settings_source = file_get_contents($root . '/settings.php');
$bangumi_source = file_get_contents($root . '/inc/bangumi.php');
$functions_source = file_get_contents($root . '/functions.php');
$failures = array();

function argon_bangumi_public_access_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

foreach (array(
	'argon_bangumi_sessdata',
	'argon_bangumi_clear_sessdata',
	'argon_update_bangumi_sessdata',
	'argon-sessdata-guide',
	'如何获取 B 站 SESSDATA',
) as $removed_setting) {
	argon_bangumi_public_access_assert(
		strpos($settings_source, $removed_setting) === false,
		'设置页仍包含已移除的 SESSDATA 配置：' . $removed_setting
	);
}

foreach (array(
	"get_option('argon_bangumi_sessdata'",
	"'Cookie' =>",
	"\$headers['Cookie']",
	'SESSDATA=',
) as $removed_request_code) {
	argon_bangumi_public_access_assert(
		strpos($bangumi_source, $removed_request_code) === false,
		'追番请求仍会读取或发送登录凭证：' . $removed_request_code
	);
}

argon_bangumi_public_access_assert(
	strpos($bangumi_source, "'Accept' => 'application/json'") !== false
	&& strpos($bangumi_source, "'User-Agent' =>") !== false,
	'公开追番请求应继续携带基础请求头。'
);

argon_bangumi_public_access_assert(
	strpos($bangumi_source, "return 'argon_bangumi_v2_' . argon_bangumi_uid()") !== false,
	'移除登录态请求后必须升级缓存命名空间，避免继续读取旧缓存。'
);

argon_bangumi_public_access_assert(
	strpos($functions_source, "add_action('init', 'argon_cleanup_removed_bangumi_credentials', 5)") !== false
	&& strpos($functions_source, "get_option('argon_bangumi_credentials_cleanup_version')") !== false
	&& strpos($functions_source, "delete_option('argon_bangumi_sessdata')") !== false
	&& strpos($functions_source, "update_option('argon_bangumi_credentials_cleanup_version', \$cleanup_version, true)") !== false,
	'缺少旧 SESSDATA 数据的一次性升级清理。'
);

argon_bangumi_public_access_assert(
	substr_count($settings_source . $bangumi_source . $functions_source, 'argon_bangumi_sessdata') === 1,
	'旧 SESSDATA 选项只能在一次性删除迁移中出现。'
);

$cleanup_matches = array();
$cleanup_matched = preg_match(
	'/function argon_cleanup_removed_bangumi_credentials\(\) \{\R(.*?)\R\}/s',
	$functions_source,
	$cleanup_matches
);
argon_bangumi_public_access_assert($cleanup_matched === 1, '无法提取旧凭证清理函数进行行为测试。');

if ($cleanup_matched === 1) {
	$GLOBALS['argon_bangumi_cleanup_options'] = array('argon_bangumi_sessdata' => 'legacy-secret');
	$GLOBALS['argon_bangumi_cleanup_calls'] = array();

	function get_option($name, $default = false) {
		return array_key_exists($name, $GLOBALS['argon_bangumi_cleanup_options'])
			? $GLOBALS['argon_bangumi_cleanup_options'][$name]
			: $default;
	}
	function delete_option($name) {
		$GLOBALS['argon_bangumi_cleanup_calls'][] = array('delete', $name);
		unset($GLOBALS['argon_bangumi_cleanup_options'][$name]);
		return true;
	}
	function update_option($name, $value, $autoload = null) {
		$GLOBALS['argon_bangumi_cleanup_calls'][] = array('update', $name, $value, $autoload);
		$GLOBALS['argon_bangumi_cleanup_options'][$name] = $value;
		return true;
	}

	eval("function argon_bangumi_cleanup_under_test() {\n" . $cleanup_matches[1] . "\n}");
	argon_bangumi_cleanup_under_test();
	$first_run_calls = $GLOBALS['argon_bangumi_cleanup_calls'];
	argon_bangumi_cleanup_under_test();

	argon_bangumi_public_access_assert(
		!isset($GLOBALS['argon_bangumi_cleanup_options']['argon_bangumi_sessdata'])
		&& get_option('argon_bangumi_credentials_cleanup_version') === '1',
		'清理函数没有删除旧凭证或写入清理版本。'
	);
	argon_bangumi_public_access_assert(
		$GLOBALS['argon_bangumi_cleanup_calls'] === $first_run_calls
		&& $first_run_calls === array(
			array('delete', 'argon_bangumi_sessdata'),
			array('update', 'argon_bangumi_credentials_cleanup_version', '1', true),
		),
		'清理函数不是幂等的，或执行了凭证删除之外的批量操作。'
	);
}

if ($failures) {
	fwrite(STDERR, "B 站追番公开访问测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "B 站追番公开访问测试通过。\n";
