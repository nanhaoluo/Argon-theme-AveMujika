<?php

/**
 * 表情分类清单、动态图片和防盗链配置契约测试。
 *
 * 运行：php tests/sticker-manifest-test.php
 */

$root = dirname(__DIR__);
$GLOBALS['argon_sticker_test_root'] = $root;
$GLOBALS['assets_path'] = 'https://blog.example.com/wp-content/themes/argon';

if (!function_exists('__')) {
	function __($text, $domain = null) {
		return $text;
	}
}
if (!function_exists('apply_filters')) {
	function apply_filters($hook, $value) {
		if ($hook === 'argon_emotion_list' && !empty($GLOBALS['argon_sticker_test_filter_groups'])) {
			return array_merge($value, $GLOBALS['argon_sticker_test_filter_groups']);
		}
		return $value;
	}
}
if (!function_exists('get_template_directory')) {
	function get_template_directory() {
		return $GLOBALS['argon_sticker_test_root'];
	}
}
if (!function_exists('esc_attr')) {
	function esc_attr($value) {
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}
if (!function_exists('esc_html')) {
	function esc_html($value) {
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}
if (!function_exists('esc_url')) {
	function esc_url($value) {
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}

require_once $root . '/emotions.php';

$failures = array();
function argon_sticker_assert($condition, $message) {
	global $failures;
	if (!$condition) {
		$failures[] = $message;
	}
}

$manifest_path = $root . '/stickers/manifest.json';
$manifest_data = json_decode(file_get_contents($manifest_path), true);
$manifest_categories = argon_read_sticker_manifest($manifest_path);
$emotion_list = argon_get_emotion_list();
$sticker_groups = array_slice($emotion_list, 2);

argon_sticker_assert(
	isset($manifest_data['categories']) && count($manifest_categories) === count($manifest_data['categories']),
	'清单包含未通过校验的分类。'
);
argon_sticker_assert(
	array_key_exists('use_filename_as_title', $manifest_data) && $manifest_data['use_filename_as_title'] === false,
	'use_filename_as_title 必须在清单中声明，且默认值应为 false。'
);
argon_sticker_assert(count($sticker_groups) === count($manifest_categories), '清单分类没有全部生成表情组。');
argon_sticker_assert(!argon_sticker_directory_is_valid('../flower'), '目录校验允许了路径穿越。');
argon_sticker_assert(!argon_sticker_directory_is_valid('flower/child'), '目录校验允许了子目录。');

$allowed_extensions = argon_sticker_allowed_extensions();
$expected_total = 0;
$actual_total = 0;
$expected_animated_total = 0;
$animated_total = 0;
$codes = array();
$legacy_codes = array('dinosaur-shy' => false, 'flower-tile' => false);
$defaulted_title_category_found = false;
$orders = array();

foreach ($manifest_categories as $index => $category) {
	$group = isset($sticker_groups[$index]) ? $sticker_groups[$index] : array();
	$raw_category = $manifest_data['categories'][$index];
	$expected_filename_as_title = array_key_exists('use_filename_as_title', $raw_category)
		? $raw_category['use_filename_as_title'] === true
		: $manifest_data['use_filename_as_title'] === true;
	if (!array_key_exists('use_filename_as_title', $raw_category)) {
		$defaulted_title_category_found = true;
	}
	argon_sticker_assert(isset($group['groupname']) && $group['groupname'] === $category['name'], '分类显示名称没有按清单顺序加载：' . $category['name']);
	argon_sticker_assert(isset($group['directory']) && $group['directory'] === $category['directory'], '分类与文件夹映射错误：' . $category['directory']);
	argon_sticker_assert(isset($raw_category['order']) && is_int($raw_category['order']), 'manifest 分类缺少整数 order：' . $category['directory']);
	argon_sticker_assert(isset($group['order']) && $group['order'] === $category['order'], 'order 没有传入表情组：' . $category['directory']);
	argon_sticker_assert(!isset($orders[$category['order']]), 'manifest 出现重复 order：' . $category['order']);
	$orders[$category['order']] = true;
	argon_sticker_assert(isset($group['use_filename_as_title']) && $group['use_filename_as_title'] === $expected_filename_as_title, 'use_filename_as_title 没有按分类覆盖或继承默认值：' . $category['directory']);

	$directory_files = @scandir($root . '/stickers/' . $category['directory']);
	$expected_group_count = 0;
	foreach (is_array($directory_files) ? $directory_files : array() as $filename) {
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (in_array($extension, $allowed_extensions, true) && is_file($root . '/stickers/' . $category['directory'] . '/' . $filename)) {
			$expected_group_count++;
			if (argon_sticker_is_animated_extension($extension)) {
				$expected_animated_total++;
			}
		}
	}
	$expected_total += $expected_group_count;
	$group_list = isset($group['list']) && is_array($group['list']) ? $group['list'] : array();
	$actual_total += count($group_list);
	argon_sticker_assert(count($group_list) === $expected_group_count, '目录图片没有全部自动发现：' . $category['directory']);

	if (isset($category['description'])) {
		argon_sticker_assert(isset($group['description']) && $group['description'] === $category['description'], 'description 字段没有从清单传入表情组：' . $category['directory']);
	}

	foreach ($group_list as $sticker) {
		argon_sticker_assert(isset($sticker['type']) && $sticker['type'] === 'sticker', '目录条目没有被标记为 sticker。');
		argon_sticker_assert(isset($sticker['src']) && strpos($sticker['src'], $GLOBALS['assets_path'] . '/stickers/') === 0, '贴图 URL 不属于当前主题。');
		argon_sticker_assert(strpos($sticker['src'], '../') === false && strpos($sticker['src'], '?v=') !== false, '贴图 URL 未限制路径或缺少缓存版本。');
		argon_sticker_assert(!isset($codes[$sticker['code']]), '生成了重复表情代码：' . $sticker['code']);
		argon_sticker_assert(
			isset($sticker['title']) && ($expected_filename_as_title ? $sticker['title'] !== '' : $sticker['title'] === ''),
			'use_filename_as_title 与实际图片标题不一致：' . $sticker['code']
		);
		$codes[$sticker['code']] = true;
		if (isset($legacy_codes[$sticker['code']])) {
			$legacy_codes[$sticker['code']] = true;
		}
		if (!empty($sticker['animated'])) {
			$animated_total++;
		}
	}
}

argon_sticker_assert($expected_total > 0, '清单分类中没有发现任何图片。');
argon_sticker_assert($actual_total === $expected_total, '自动发现的图片总数与目录不一致。');
argon_sticker_assert($animated_total === $expected_animated_total, '动态图片识别数量与目录不一致。');
argon_sticker_assert(!in_array(false, $legacy_codes, true), '小恐龙或花表情的旧代码没有保留。');
argon_sticker_assert($defaulted_title_category_found, '清单中至少应有一个分类验证默认 use_filename_as_title=false。');
$sorted_orders = array_keys($orders);
$expected_sorted_orders = $sorted_orders;
sort($expected_sorted_orders, SORT_NUMERIC);
argon_sticker_assert($sorted_orders === $expected_sorted_orders, '表情分类没有按 manifest order 升序加载。');

$flower_group = null;
foreach ($sticker_groups as $group) {
	if (isset($group['directory']) && $group['directory'] === 'flower') {
		$flower_group = $group;
		break;
	}
}
argon_sticker_assert(
	is_array($flower_group)
	&& isset($flower_group['source'])
	&& $flower_group['source'] === 'Source: github.com/k4yt3x/flowerhd',
	'Flower 来源说明没有由 manifest source 字段提供。'
);

ob_start();
include $root . '/template-parts/emotion-keyboard.php';
$keyboard_html = ob_get_clean();
argon_sticker_assert(substr_count($keyboard_html, 'data-sticker-src=') === $expected_total, '表情面板没有为全部贴图输出延迟加载地址。');
argon_sticker_assert(substr_count($keyboard_html, 'emotion-item-animated') === $expected_animated_total, '动态贴图标记数量不正确。');
argon_sticker_assert(strpos($keyboard_html, 'Source: github.com/k4yt3x/flowerhd') !== false, '清单 source 没有渲染到 emotion-group-description。');
argon_sticker_assert(
	strpos($keyboard_html, 'emotion-keyboard-descriptions') !== false
	&& strpos($keyboard_html, 'emotion-item-title') !== false
	&& strpos($keyboard_html, "emotion-item-sticker' code=") !== false,
	'表情来源没有固定在底部区域，或图片标题没有改为图片下方文字。'
);

$animated_code = '';
foreach ($sticker_groups as $group) {
	foreach ($group['list'] as $sticker) {
		if (!empty($sticker['animated'])) {
			$animated_code = $sticker['code'];
			break 2;
		}
	}
}
$GLOBALS['argon_sticker_test_filter_groups'] = array(
	array(
		'groupname' => 'Custom',
		'list' => array(array('type' => 'sticker', 'code' => 'party+1', 'src' => 'https://blog.example.com/custom.png')),
	),
);
$rendered_comment = argon_replace_comment_stickers('A :dinosaur-shy: B :' . $animated_code . ': C :party+1: D :not-a-sticker:');
argon_sticker_assert(substr_count($rendered_comment, "class='comment-sticker'") === 3, '评论中真实出现的静态、动态或第三方自定义表情代码没有被替换。');
argon_sticker_assert(strpos($rendered_comment, ':not-a-sticker:') !== false, '未知的冒号代码不应被替换。');

$template = @file_get_contents($root . '/template-parts/emotion-keyboard.php');
$comment_renderer = @file_get_contents($root . '/functions.php');
$emotion_renderer = @file_get_contents($root . '/emotions.php');
$javascript = @file_get_contents($root . '/argontheme.js');
$stylesheet = @file_get_contents($root . '/style.css');
$apache = @file_get_contents($root . '/stickers/.htaccess');
$nginx = @file_get_contents($root . '/stickers/nginx-hotlink.conf.example');
$server_extension_pattern = '(?:' . implode('|', $allowed_extensions) . ')';

argon_sticker_assert(
	strpos($template, 'esc_url($emotion[\'src\'])') !== false
	&& strpos($template, "esc_html(implode(' · ', \$footerParts))") !== false
	&& strpos($template, "data-sticker-src") !== false,
	'表情面板没有安全输出 URL/来源说明，或没有按分类延迟加载。'
);
argon_sticker_assert(
	strpos($comment_renderer, 'argon_replace_comment_stickers') !== false
	&& strpos($emotion_renderer, "referrerpolicy='strict-origin-when-cross-origin'") !== false
	&& strpos($template, "referrerpolicy='strict-origin-when-cross-origin'") !== false,
	'评论贴图没有安全输出，或站点别名请求不会发送可校验的来源域名。'
);
argon_sticker_assert(
	strpos($javascript, 'suspendAnimatedStickers') !== false
	&& strpos($javascript, 'data-sticker-animated') !== false,
	'动态表情没有在分类切换或关闭面板后暂停。'
);
argon_sticker_assert(
	strpos($template, 'emotion-keyboard-scroll') !== false
	&& strpos($javascript, 'scrollEmotionKeyboardBar') !== false
	&& strpos($javascript, 'keyboard.offsetWidth') !== false
	&& strpos($javascript, 'keyboard.offsetHeight') !== false
	&& strpos($javascript, 'buttonCenter - keyboardWidth / 2') !== false,
	'放大的表情面板没有可操作的分类滚动菜单，或定位仍使用旧的固定尺寸。'
);
argon_sticker_assert(
	preg_match('/\.emotion-keyboard\s*\{[^}]*width:\s*min\(720px,/s', $stylesheet) === 1
	&& preg_match('/\.emotion-keyboard\s*\{[^}]*height:\s*auto;[^}]*max-height:\s*min\(440px,/s', $stylesheet) === 1
	&& strpos($stylesheet, 'grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));') !== false
	&& strpos($stylesheet, '.argon-text-input,') !== false
	&& strpos($stylesheet, '--argon-text-input-bg: var(--flinks-input-bg') !== false
	&& strpos($stylesheet, 'html.darkmode .todo-completed-divider.collapsed') !== false,
	'表情面板没有扩大，或文本输入面没有复用统一的半透明主题样式。'
);
argon_sticker_assert(
	strpos($apache, 'HTTP_REFERER') !== false
	&& strpos($apache, '[F,L]') !== false
	&& strpos($nginx, 'valid_referers server_names') !== false
	&& strpos($nginx, 'try_files $uri =404') !== false
	&& strpos($apache, 'always set Cache-Control') === false
	&& strpos($nginx, 'Cache-Control "public, max-age=31536000, immutable" always') === false
	&& substr_count($apache, $server_extension_pattern) === 2
	&& substr_count($nginx, $server_extension_pattern) === 1,
	'Apache 或 Nginx 防直链配置不完整。'
);
argon_sticker_assert(
	strpos($comment_renderer, 'readfile(') === false && strpos($emotion_renderer, 'readfile(') === false,
	'图片不应通过 PHP readfile 代理。'
);

$apache_same_origin_pattern = '~^https?://([^/:]+)(?::[0-9]+)?/.*\|\1(?::[0-9]+)?$~i';
argon_sticker_assert(
	preg_match($apache_same_origin_pattern, 'https://blog.cartol.top/post/1|blog.cartol.top') === 1,
	'Apache 规则会误伤同源表情请求。'
);
argon_sticker_assert(
	preg_match($apache_same_origin_pattern, '|blog.cartol.top') === 0
	&& preg_match($apache_same_origin_pattern, 'https://example.com/post/1|blog.cartol.top') === 0,
	'Apache 规则没有拒绝地址栏直开或跨站盗链。'
);

if ($failures) {
	fwrite(STDERR, "表情清单测试失败：\n- " . implode("\n- ", $failures) . "\n");
	exit(1);
}

echo '表情清单测试通过：' . count($sticker_groups) . ' 个分类，' . $actual_total . ' 张图片，其中 ' . $animated_total . " 张动态图片。\n";
