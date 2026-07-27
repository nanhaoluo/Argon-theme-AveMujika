<?php

/**
 * 评论 AJAX 即时渲染契约测试。
 *
 * 运行：php tests/comment-immediate-render-test.php
 */

$root = dirname(__DIR__);
$php = file_get_contents($root . '/functions.php');
$javascript = file_get_contents($root . '/argontheme.js');
$failures = array();

function argon_comment_render_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

argon_comment_render_assert(
	strpos($php, "\$GLOBALS['argon_force_visible_comment_id'] = intval(\$comment -> comment_ID);") !== false
	&& strpos($php, 'argon_comment_is_forced_visible(get_comment_ID())') !== false
	&& strpos($php, "unset(\$GLOBALS['argon_force_visible_comment_id']);") !== false,
	'评论提交接口没有把刚创建的评论限定为本次渲染可见。'
);
argon_comment_render_assert(
	strpos($php, "isset(\$_COOKIE['argon_user_token'])") !== false,
	'悄悄话可见性检查仍会直接读取不存在的 Cookie。'
);
argon_comment_render_assert(
	strpos($javascript, 'replace(/<\\/?noscript\\b[^>]*>/gi, "")') !== false,
	'前端没有正确清理评论响应中的 noscript 标签。'
);

if ($failures) {
	fwrite(STDERR, "评论即时渲染测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "评论即时渲染测试通过。\n";
