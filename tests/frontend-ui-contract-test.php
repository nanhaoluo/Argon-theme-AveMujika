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
$functionsSource = file_get_contents($root . '/functions.php');
foreach (array('template-parts/content-preview-1.php', 'template-parts/content-preview-2.php', 'template-parts/content-preview-3.php') as $tpl) {
	$previewSource = file_get_contents($root . '/' . $tpl);
	argon_frontend_ui_assert(
		strpos($previewSource, "argon_preview_plain_text(get_the_content('...'))") !== false,
		$tpl . ' 摘要生成没有走 argon_preview_plain_text，短代码原文或粘连文本会混成卡片正文的一坨。'
	);
}
argon_frontend_ui_assert(
	strpos($functionsSource, 'function argon_preview_plain_text') !== false
	&& strpos($functionsSource, 'strip_shortcodes') !== false
	&& strpos($functionsSource, '/>\s*</') !== false
	&& strpos($functionsSource, 'argon_attachments_locate') !== false
	&& strpos($functionsSource, '/\[attachment\s+') !== false
	&& strpos($functionsSource, '[argon_attachment') === false,
	'argon_preview_plain_text 缺少短代码剥离、标签边界补空格，或未将无前缀 [attachment] 短代码（含 name 软引用）降级为文件名。'
);

// 多语言代码块高亮契约测试
argon_frontend_ui_assert(
	strpos($themejs, 'var argonLanguageAliases = {') !== false
	&& strpos($themejs, '"x86asm": "nasm"') !== false
	&& strpos($themejs, '"c++": "cpp"') !== false
	&& strpos($themejs, '"proto": "protobuf"') !== false
	&& strpos($themejs, '"golang": "go"') !== false
	&& strpos($themejs, '"shell": "bash"') !== false
	&& strpos($themejs, '"zsh": "bash"') !== false,
	'语言别名映射表缺少常见语言规范化（如 x86asm/c++/proto/go/bash/zsh）。'
);

argon_frontend_ui_assert(
	strpos($themejs, 'autoloader.languages_path') !== false
	&& strpos($themejs, 'Prism.plugins.autoloader') !== false
	&& strpos($themejs, 'argon-code-language') !== false,
	'代码块渲染函数缺少 Autoloader 路径加固或语言徽标注入逻辑。'
);

argon_frontend_ui_assert(
	preg_match('/article pre code,\s*article pre code \*\s*\{[^}]*background:\s*transparent\s*!important;/s', $style) === 1,
	'style.css 缺少代码块内行内代码残留背景与边框的全局重置。'
);

argon_frontend_ui_assert(
	preg_match('/html\s*\{[^}]*overflow-y:\s*scroll;/s', $style) === 1
	&& preg_match('/html\.argon-modal-locked\s+body\s*,\s*body\.modal-open[^}]*padding-right:\s*0\s*!important/s', $style) === 1,
	'模态窗口打开时缺少 html 滚动条轨道常驻与禁止 padding-right 导致的背景卡片左移补偿保护。'
);

argon_frontend_ui_assert(
	preg_match('/\.modal-backdrop\s*\{[^}]*backdrop-filter:\s*blur\(var\(--card-blur[^}]*opacity:\s*0;/s', $style) === 1
	&& preg_match('/\.modal-backdrop\.show\s*\{[^}]*opacity:\s*1\s*!important;/s', $style) === 1,
	'模态窗口遮罩层缺少完整的 backdrop-filter 模糊规范或不透明度被默认淡化覆盖。'
);

$emotionKeyboardTpl = file_get_contents($root . '/template-parts/emotion-keyboard.php');
argon_frontend_ui_assert(
	strpos($emotionKeyboardTpl, 'id="emotion_keyboard"') !== false
	&& preg_match('/<div[^>]*id="emotion_keyboard"[^>]*class="[^"]*\bmodal\b/i', $emotionKeyboardTpl) === 0,
	'表情键盘错误使用了模态窗口类，应作为局部弹出式菜单处理。'
);

argon_frontend_ui_assert(
	preg_match('/article\.post-preview-layout-2 \.post-header\.post-header-with-thumbnail\s*\{[^}]*height:\s*auto;[^}]*min-height:\s*200px;[^}]*align-self:\s*stretch;/s', $style) === 1
	&& preg_match('/article\.post-preview-layout-2 \.post-thumbnail\s*\{[^}]*height:\s*100%\s*!important;[^}]*max-height:\s*none\s*!important;/s', $style) === 1,
	'layout-2 预览卡片头图未随卡片高度拉伸：header 需 height:auto + min-height:200px + align-self:stretch，缩略图需 height:100%。'
);

argon_frontend_ui_assert(
	preg_match('/html\.mobile-layout-2 article\.post-preview-layout-2 \.post-header\.post-header-with-thumbnail\s*\{[^}]*min-height:\s*0;/s', $style) === 1
	&& preg_match('/html\.mobile-layout-3 article\.post-preview-layout-2 \.post-header\.post-header-with-thumbnail\s*\{[^}]*min-height:\s*0;/s', $style) === 1
	&& preg_match('/html\.mobile-layout-3 article\.post-preview-layout-2\s*\{[^}]*min-height:\s*0;/s', $style) === 1,
	'移动端 layout-2 卡片未重置 min-height，紧凑/横向模式会被桌面端 200px 高度下限撑破。'
);

if ($failures) {
	fwrite(STDERR, "前台组件测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "前台组件测试通过。\n";
