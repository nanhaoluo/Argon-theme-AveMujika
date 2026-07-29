<?php

/**
 * 表情搜索、浮层层级与统一输入样式契约。
 *
 * 运行：php tests/emotion-search-input-style-test.php
 */

$root = dirname(__DIR__);
$style = file_get_contents($root . '/style.css');
$themejs = file_get_contents($root . '/argontheme.js');
$template = file_get_contents($root . '/template-parts/emotion-keyboard.php');
$header = file_get_contents($root . '/header.php');
$sidebar = file_get_contents($root . '/sidebar.php');
$friendLinks = file_get_contents($root . '/friend-links.php');
$specialPageSurface = file_get_contents($root . '/inc/special-page-surface.php');
$failures = array();

function argon_emotion_search_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

argon_emotion_search_assert(
	strpos($style, '.argon-text-input,') !== false
	&& strpos($style, '#post_comment_content_editor {') !== false
	&& strpos($specialPageSurface, '--argon-special-page-input-bg: rgba(var(--themecolor-rgbstr), 0.05)') !== false
	&& strpos($style, '--argon-text-input-bg: rgba(var(--themecolor-rgbstr), 0.05)') !== false
	&& strpos($style, 'background: var(--argon-text-input-bg) !important;') !== false
	&& preg_match('/\.argon-text-input:focus\s*,[\s\S]*?#post_comment_content_editor:focus-visible\s*\{[^}]*border-color:\s*rgba\(var\(--themecolor-rgbstr\),\s*0\.45\)[^}]*box-shadow:\s*0 0 0 2px/s', $style) === 1,
	'公共输入样式没有复用自定义功能页共享输入令牌，或缺少统一聚焦状态。'
);

argon_emotion_search_assert(
	preg_match('/#navbar_search_input_container \.argon-text-input-group\s*,\s*#navbar_search_input_container\.open \.argon-text-input-group\s*\{[^}]*border-radius:\s*25px\s*!important;/s', $style) === 1,
	'顶栏搜索框没有保留原有的 25px 大圆角。'
);

argon_emotion_search_assert(
	strpos($style, '#navbar_search_input_container.open.has-text .argon-text-input-group') !== false
	&& preg_match('/#navbar_search_input_container\.open \.argon-text-input-group\s*\{[^}]*background:\s*rgba\(255,\s*255,\s*255,\s*0\.72\)\s*!important;[^}]*backdrop-filter:\s*blur\(18px\) saturate\(150%\)/s', $style) === 1
	&& preg_match('/#navbar_search_input_container\.open \.argon-text-input-group:hover\s*,[\s\S]*?background:\s*rgba\(255,\s*255,\s*255,\s*0\.86\)\s*!important;/s', $style) === 1
	&& strpos($style, '--argon-text-input-bg: rgba(255, 255, 255, 0.94)') === false
	&& strpos($style, '--argon-text-input-bg-hover: #fff') === false,
	'顶栏搜索框没有使用独立于 topbar 的白色半透明毛玻璃表面。'
);

argon_emotion_search_assert(
	strpos($sidebar, 'id="leftbar_search_input"') !== false
	&& preg_match('/id="leftbar_search_input"[^\r\n]*\breadonly\b/i', $sidebar) === 1
	&& preg_match('/id="leftbar_search_input"[^\r\n]*tabindex="-1"[^\r\n]*aria-hidden="true"/i', $sidebar) === 1
	&& preg_match('/<\/button>\s*<input id="leftbar_search_input"/s', $sidebar) === 1
	&& strpos($themejs, 'function setLeftbarSearchOpen(isOpen)') !== false
	&& strpos($themejs, '.attr("tabindex", isOpen ? "0" : "-1")') !== false
	&& strpos($themejs, '.attr("aria-hidden", isOpen ? "false" : "true")') !== false
	&& strpos($themejs, '$(document).on("focusout", ".leftbar-search-button"') !== false
	&& preg_match('/\.leftbar-search-button\.open #leftbar_search_container\s*\{[^}]*border-radius:\s*var\(--m3-shape-xl,\s*25px\)\s*!important;[^}]*background:\s*transparent\s*!important;[^}]*color:\s*transparent\s*!important;/s', $style) === 1
	&& preg_match('/\.leftbar-search-button\.open #leftbar_search_input\s*\{[^}]*border-radius:\s*var\(--m3-shape-xl,\s*25px\)\s*!important;[^}]*background:\s*var\(--argon-text-input-bg\)\s*!important;/s', $style) === 1,
	'侧栏搜索的收起状态仍可聚焦，或展开时仍叠加显示未激活按钮表面。'
);

foreach (array(
	array($header, 'navbar_search_input'),
	array($sidebar, 'leftbar_search_input'),
	array($sidebar, 'todo-input'),
	array($friendLinks, 'add-url'),
	array($friendLinks, 'apply-url'),
) as $surface) {
	argon_emotion_search_assert(
		preg_match('/id="' . preg_quote($surface[1], '/') . '"[^\r\n]*argon-text-input/i', $surface[0]) === 1,
		$surface[1] . ' 没有接入公共输入样式。'
	);
}

argon_emotion_search_assert(
	strpos($template, 'id="emotion_search_input" class="argon-text-input"') !== false
	&& strpos($template, "data-search='") !== false
	&& strpos($template, 'emotion-keyboard-search-clear') !== false
	&& strpos($template, 'emotion-keyboard-empty d-none') !== false,
	'表情面板缺少可搜索数据、清空按钮或空结果状态。'
);

argon_emotion_search_assert(
	strpos($themejs, 'function filterEmotionKeyboard(value)') !== false
	&& strpos($themejs, 'function resetEmotionKeyboardSearch(reposition)') !== false
	&& strpos($themejs, 'toLocaleLowerCase()') !== false
	&& strpos($themejs, 'groupMatches ||') !== false
	&& strpos($themejs, '$(document).on("input", "#emotion_search_input"') !== false,
	'表情搜索没有实现跨分类的名称/代码过滤与清空恢复。'
);

argon_emotion_search_assert(
	preg_match('/#comment_emotion_btn\s*\{[^}]*background:\s*var\(--themecolor\)\s*!important;[^}]*color:\s*#fff\s*!important;[^}]*border:\s*1px solid rgba\(255,\s*255,\s*255,\s*0\.58\)/s', $style) === 1,
	'表情按钮没有提供不依赖页面背景的实色高对比表面。'
);

argon_emotion_search_assert(
	preg_match('/\.headroom--pinned\s*\{[^}]*z-index:\s*100;/s', $style) === 1
	&& preg_match('/#post_actions_container\s*\{[^}]*z-index:\s*1;/s', $style) === 1
	&& preg_match('/#emotion_keyboard\.emotion-keyboard-root-layer\s*\{[^}]*z-index:\s*1;/s', $style) === 1
	&& strpos($themejs, 'document.body.appendChild(keyboard)') !== false
	&& strpos($themejs, 'Math.ceil(toolbarRect.bottom) + 10') !== false
	&& strpos($themejs, '$(document).on("pjax:beforeReplace"') !== false,
	'表情面板没有脱离裁剪上下文、避开顶栏，或 PJAX 后仍会遗留旧节点。'
);

if ($failures) {
	fwrite(STDERR, "表情搜索与输入样式测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo "表情搜索与输入样式测试通过。\n";
