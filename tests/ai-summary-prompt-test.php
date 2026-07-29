<?php

$GLOBALS['argon_ai_summary_test_options'] = array();

function get_option($name, $default = false) {
	return array_key_exists($name, $GLOBALS['argon_ai_summary_test_options'])
		? $GLOBALS['argon_ai_summary_test_options'][$name]
		: $default;
}

function argon_ai_summary_test_assert($condition, $message) {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

require_once dirname(__DIR__) . '/inc/ai-summary-prompt.php';

$prompt = argon_get_default_ai_summary_prompt();
$prompt_length = mb_strlen($prompt, 'UTF-8');
argon_ai_summary_test_assert($prompt_length >= 5000 && $prompt_length < 10000, '默认提示词应在 5000–9999 字符之间');
argon_ai_summary_test_assert(strpos($prompt, '程序会在本提示词末尾附加“程序长度约束”') !== false, '提示词应声明程序长度约束为权威值');

$profiles = array(
	array(199, 40, 80),
	array(200, 80, 140),
	array(599, 80, 140),
	array(600, 140, 220),
	array(1499, 140, 220),
	array(1500, 220, 300),
	array(3999, 220, 300),
	array(4000, 300, 380),
	array(7999, 300, 380),
	array(8000, 380, 450),
	array(14999, 380, 450),
	array(15000, 430, 500),
);
foreach ($profiles as $expected) {
	$actual = argon_get_ai_summary_length_profile(str_repeat('中', $expected[0]));
	argon_ai_summary_test_assert($actual === $expected, '正文长度分档不匹配：' . $expected[0]);
}

$combined_prompt = argon_apply_ai_summary_hard_boundary($prompt, str_repeat('中', 1500));
argon_ai_summary_test_assert(mb_strlen($combined_prompt, 'UTF-8') < 10000, '默认提示词与程序边界合计不得达到 10000 字符');
argon_ai_summary_test_assert(
	strpos($combined_prompt, '程序长度约束：服务端已计算本次实际传入正文为 1500 字，并将摘要目标区间确定为 220–300 字') !== false,
	'请求提交前应写入程序计算的唯一目标区间'
);

$normalized = argon_normalize_ai_summary_to_profile(str_repeat('内容', 100), str_repeat('正文', 100));
argon_ai_summary_test_assert($normalized['target_min'] === 80 && $normalized['target_max'] === 140, '程序应选中 200 字正文对应的 80–140 字摘要区间');
argon_ai_summary_test_assert($normalized['was_truncated'], '超过目标上限的摘要应由程序截断');
argon_ai_summary_test_assert($normalized['normalized_length'] <= 140, '程序截断后不得超过目标上限');
argon_ai_summary_test_assert(mb_substr($normalized['summary'], -1, 1, 'UTF-8') === '。', '程序截断后应以完整句号收束');

$short = argon_normalize_ai_summary_to_profile('信息有限。', str_repeat('正文', 100));
argon_ai_summary_test_assert($short['is_below_target'], '低于目标下限的摘要应被程序识别');
argon_ai_summary_test_assert($short['summary'] === '信息有限。', '低于下限时不得由程序编造内容补齐');

$GLOBALS['argon_ai_summary_test_options']['argon_ai_summary_prompt'] = '自定义摘要提示词';
argon_ai_summary_test_assert(argon_get_ai_summary_prompt() === '自定义摘要提示词', '真实自定义提示词不应被默认值覆盖');

echo "AI_SUMMARY_PROMPT_TEST_OK prompt={$prompt_length} combined=" . mb_strlen($combined_prompt, 'UTF-8') . PHP_EOL;
