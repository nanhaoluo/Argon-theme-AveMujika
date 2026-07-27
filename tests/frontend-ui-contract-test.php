<?php

/**
 * 本轮前台组件修复契约测试。
 *
 * 运行：php tests/frontend-ui-contract-test.php
 */

$root = dirname(__DIR__);
$header = file_get_contents($root . '/header.php');
$sidebar = file_get_contents($root . '/sidebar.php');
$comments = file_get_contents($root . '/comments.php');
$style = file_get_contents($root . '/style.css');
$failures = array();

function argon_frontend_ui_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

argon_frontend_ui_assert(strpos($header, 'id="fabtn_open_sidebar"') === false, '无效的悬浮侧栏按钮仍在输出。');
argon_frontend_ui_assert(strpos($sidebar, "echo \"<div style='height: 20px;'></div>\";") === false, '站点概览仍输出底部空白占位。');
argon_frontend_ui_assert(
	preg_match('/id="comment_emotion_btn"[^>]*\saria-label=/i', $comments) === 1
	&& preg_match('/id="comment_emotion_btn"[^>]*\stitle=/i', $comments) === 0,
	'表情按钮仍带有意外的 title，或缺少无障碍名称。'
);
argon_frontend_ui_assert(
	preg_match('/#post_actions_container\s*\{[^}]*position:\s*relative;[^}]*z-index:\s*1051;/s', $style) === 1
	&& preg_match('/#share_container\.opened\s*\{[^}]*z-index:\s*1060;/s', $style) === 1,
	'分享功能栏没有建立足够高且稳定的层级。'
);

if ($failures) {
	fwrite(STDERR, "前台组件测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "前台组件测试通过。\n";
