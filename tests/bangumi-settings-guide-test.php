<?php

/**
 * B 站 SESSDATA 设置教程契约测试。
 *
 * 运行：php tests/bangumi-settings-guide-test.php
 */

$root = dirname(__DIR__);
$source = file_get_contents($root . '/settings.php');
$failures = array();

function argon_bangumi_settings_guide_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

argon_bangumi_settings_guide_assert(
	preg_match('/<input\b(?=[^>]*name="argon_bangumi_sessdata")(?=[^>]*type="password")(?=[^>]*data-argon-sensitive)[^>]*>/i', $source) === 1,
	'SESSDATA 输入框必须继续使用密码类型并标记为敏感字段。'
);

foreach (array(
	'如何获取 B 站 SESSDATA',
	'Application（应用）→ Storage（存储）→ Cookies',
	'Firefox 选择“Storage（存储）→ Cookies”',
	'<code>https://www.bilibili.com</code>',
	'名称（Name）为 SESSDATA',
	'双击该行的值（Value）并完整复制',
	'<code>SESSDATA=</code>',
	'不要解码内容',
	'保存成功后输入框会自动清空',
	'不要把它发到聊天、工单、截图、公开仓库或日志中',
	'Expires / Max-Age',
) as $required_text) {
	argon_bangumi_settings_guide_assert(
		strpos($source, $required_text) !== false,
		'SESSDATA 教程缺少必要说明：' . strip_tags($required_text)
	);
}

argon_bangumi_settings_guide_assert(
	strpos($source, 'SESSDATA 一般 30 天过期') === false,
	'SESSDATA 教程不应写死可能变化的 30 天有效期。'
);

argon_bangumi_settings_guide_assert(
	strpos($source, 'class="argon-field-guide argon-sessdata-guide"') !== false
	&& strpos($source, '.argon-field-guide > summary:focus-visible') !== false
	&& strpos($source, '@media screen and (max-width:960px)') !== false,
	'SESSDATA 教程缺少可访问的折叠结构或响应式样式。'
);

if ($failures) {
	fwrite(STDERR, "B 站 SESSDATA 设置教程测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "B 站 SESSDATA 设置教程测试通过。\n";
