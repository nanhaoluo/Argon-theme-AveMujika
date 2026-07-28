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
$themejs = file_get_contents($root . '/argontheme.js');
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
	preg_match('/\.headroom--pinned\s*\{[^}]*z-index:\s*100;/s', $style) === 1
	&& preg_match('/#post_actions_container\s*\{[^}]*position:\s*relative;[^}]*z-index:\s*1;/s', $style) === 1
	&& preg_match('/#share_container\.opened\s*\{[^}]*z-index:\s*1060;/s', $style) === 1,
	'文章操作区没有建立低于顶栏、且内部分享面板仍可展开的层叠上下文。'
);
argon_frontend_ui_assert(
	preg_match('/#comments\s+\.comment-item:(?::)?before\b/', $style) === 0
	&& preg_match('/#comments\s+\.comment-item:target\s*\{[^}]*animation:\s*comment-focus-breath/s', $style) === 1,
	'评论高亮仍使用覆盖内容的伪元素遮罩。'
);
argon_frontend_ui_assert(
	strpos($themejs, 'function argonRevealInsertedComments') !== false
	&& strpos($themejs, 'comment.classList.add("animate-in")') !== false
	&& strpos($themejs, 'commentsBeforeInsert') !== false
	&& strpos($themejs, 'typeof lazyload === "function"') !== false,
	'动态插入的新评论没有按 DOM 差集解除入场隐藏状态，或没有登记其中的懒加载资源。'
);
argon_frontend_ui_assert(
	preg_match('/#comments:not\(\.comments-collapsed\)\.comments-expand-done\s*,\s*#post_comment:not\(\.comments-collapsed\)\.comments-expand-done\s*\{[^}]*max-height:\s*none/s', $style) === 1,
	'展开态仍被 2000px 高度上限裁断，长评论列表与"加载更多评论"按钮会被裁掉。'
);
argon_frontend_ui_assert(
	strpos($themejs, 'argonMarkCommentsExpandDone') !== false
	&& strpos($themejs, 'argonCollapseCommentsArea') !== false
	&& strpos($themejs, 'comments-expand-done') !== false,
	'评论展开后缺少解除 max-height 上限、折叠时缺少恢复过渡的机制。'
);
argon_frontend_ui_assert(
	preg_match('/#comments\s*,\s*#post_comment\s*\{[^}]*overflow-anchor:\s*none;/s', $style) === 1
	&& preg_match('/#comments:not\(\.comments-collapsed\):not\(\.comments-expand-done\)\s*,\s*#post_comment:not\(\.comments-collapsed\):not\(\.comments-expand-done\)\s*\{[^}]*overflow:\s*hidden;/s', $style) === 1
	&& strpos($themejs, '$commentsArea.css({"max-height": "none"') === false,
	'评论区高度过渡仍会暴露溢出内容、参与滚动锚定，或遗留覆盖后续折叠动画的内联高度。'
);

if ($failures) {
	fwrite(STDERR, "前台组件测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "前台组件测试通过。\n";
