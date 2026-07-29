<?php

/**
 * 评论提交与 AI 审核解耦契约测试。
 *
 * 运行：php tests/comment-async-review-test.php
 */

$root = dirname(__DIR__);
$php = file_get_contents($root . '/functions.php');
$comments_template = file_get_contents($root . '/comments.php');
$javascript = file_get_contents($root . '/argontheme.js');
$failures = array();

function argon_async_review_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

function argon_extract_php_function($source, $name) {
	$start = strpos($source, 'function ' . $name . '(');
	if ($start === false) return '';
	$open = strpos($source, '{', $start);
	$depth = 0;
	$length = strlen($source);
	for ($i = $open; $i < $length; $i++) {
		if ($source[$i] === '{') $depth++;
		if ($source[$i] === '}') {
			$depth--;
			if ($depth === 0) return substr($source, $start, $i - $start + 1);
		}
	}
	return '';
}

$scheduler = argon_extract_php_function($php, 'argon_auto_detect_spam_on_comment');
argon_async_review_assert($scheduler !== '', '缺少新评论 AI 审核调度函数。');
argon_async_review_assert(
	strpos($scheduler, 'argon_async_spam_detection_handler($comment_id)') === false,
	'评论提交钩子仍会同步等待 AI 审核。'
);
argon_async_review_assert(
	strpos($scheduler, 'wp_schedule_single_event') !== false,
	'评论提交钩子没有写入后台审核任务。'
);
argon_async_review_assert(
	strpos($php, "add_action('wp_ajax_nopriv_argon_process_comment_review'") !== false,
	'访客评论缺少独立的后台审核触发接口。'
);
argon_async_review_assert(
	strpos($php, "'reviewToken'") !== false && strpos($php, "'reviewPending'") !== false,
	'评论提交响应没有携带一次性后台审核凭据。'
);
argon_async_review_assert(
	strpos($javascript, 'function triggerAsyncCommentReview') !== false
	&& strpos($javascript, 'fetch(') !== false
	&& strpos($javascript, 'keepalive: true') !== false,
	'浏览器没有在评论提交成功后异步触发 AI 审核。'
);
argon_async_review_assert(
	strpos($javascript, '.comment-ai-train-submit') !== false
	&& strpos($javascript, 'argon_train_comment_with_ai') !== false
	&& strpos($comments_template, 'comment_ai_train_dialog') !== false,
	'管理员 AI 训练按钮缺少浏览器异步提交和说明弹窗。'
);

if ($failures) {
	fwrite(STDERR, "评论异步审核测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "评论异步审核测试通过。\n";
