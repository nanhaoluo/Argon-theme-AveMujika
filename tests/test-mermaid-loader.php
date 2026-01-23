<?php
/**
 * Mermaid 库加载器单元测试
 * 
 * 测试 argon_has_mermaid_content() 和 argon_get_mermaid_library_url() 函数
 */

// 加载 WordPress 测试环境
require_once dirname(__FILE__) . '/../../../wp-load.php';
require_once dirname(__FILE__) . '/../functions.php';

/**
 * 测试辅助函数
 */
function test_assert($condition, $message) {
	if ($condition) {
		echo "✓ {$message}\n";
		return true;
	} else {
		echo "✗ {$message}\n";
		return false;
	}
}

function test_assert_equals($expected, $actual, $message) {
	if ($expected === $actual) {
		echo "✓ {$message}\n";
		return true;
	} else {
		echo "✗ {$message}\n";
		echo "  期望值: " . var_export($expected, true) . "\n";
		echo "  实际值: " . var_export($actual, true) . "\n";
		return false;
	}
}

function test_assert_contains($needle, $haystack, $message) {
	if (strpos($haystack, $needle) !== false) {
		echo "✓ {$message}\n";
		return true;
	} else {
		echo "✗ {$message}\n";
		echo "  在字符串中未找到: {$needle}\n";
		return false;
	}
}

echo "=== Mermaid 库加载器单元测试 ===\n\n";

// 测试 1: 检测 div class="mermaid" 格式
$content1 = '<div class="mermaid">flowchart TD\nA-->B</div>';
test_assert(argon_has_mermaid_content($content1), "测试 1: 检测 div class=\"mermaid\" 格式");

// 测试 2: 检测 code class="language-mermaid" 格式
$content2 = '<pre><code class="language-mermaid">graph LR\nA-->B</code></pre>';
test_assert(argon_has_mermaid_content($content2), "测试 2: 检测 code class=\"language-mermaid\" 格式");

// 测试 3: 检测 pre data-lang="mermaid" 格式
$content3 = '<pre data-lang="mermaid">sequenceDiagram\nA->>B: Hello</pre>';
test_assert(argon_has_mermaid_content($content3), "测试 3: 检测 pre data-lang=\"mermaid\" 格式");

// 测试 4: 检测 code class="mermaid" 格式
$content4 = '<code class="mermaid">pie title Pets\n"Dogs" : 386</code>';
test_assert(argon_has_mermaid_content($content4), "测试 4: 检测 code class=\"mermaid\" 格式");

// 测试 5: 不包含 Mermaid 代码块
$content5 = '<p>This is a regular paragraph</p><code class="language-javascript">console.log("hello")</code>';
test_assert(!argon_has_mermaid_content($content5), "测试 5: 不包含 Mermaid 代码块");

// 测试 6: 空内容
test_assert(!argon_has_mermaid_content(''), "测试 6: 空内容返回 false");

// 测试 7: 检测多个 class 的情况
$content7 = '<div class="code-block mermaid highlight">flowchart TD</div>';
test_assert(argon_has_mermaid_content($content7), "测试 7: 检测多个 class 的情况");

// 测试 8: 大小写不敏感
$content8 = '<div class="MERMAID">flowchart TD</div>';
test_assert(argon_has_mermaid_content($content8), "测试 8: 大小写不敏感");

echo "\n=== 测试 argon_get_mermaid_library_url() ===\n\n";

// 测试 9: jsdelivr CDN
update_option('argon_mermaid_cdn_source', 'jsdelivr');
update_option('argon_mermaid_use_local', false);
$url9 = argon_get_mermaid_library_url();
test_assert_contains('cdn.jsdelivr.net', $url9, "测试 9: jsdelivr CDN URL");

// 测试 10: unpkg CDN
update_option('argon_mermaid_cdn_source', 'unpkg');
update_option('argon_mermaid_use_local', false);
$url10 = argon_get_mermaid_library_url();
test_assert_contains('unpkg.com', $url10, "测试 10: unpkg CDN URL");

// 测试 11: 本地镜像
update_option('argon_mermaid_use_local', true);
$url11 = argon_get_mermaid_library_url();
test_assert_contains('/assets/vendor/mermaid/', $url11, "测试 11: 本地镜像 URL");

// 测试 12: 自定义 CDN（有效 URL）
update_option('argon_mermaid_cdn_source', 'custom');
update_option('argon_mermaid_cdn_custom_url', 'https://example.com/mermaid.min.js');
update_option('argon_mermaid_use_local', false);
$url12 = argon_get_mermaid_library_url();
test_assert_equals('https://example.com/mermaid.min.js', $url12, "测试 12: 自定义 CDN URL");

// 测试 13: 自定义 CDN（无效 URL，降级到 jsdelivr）
update_option('argon_mermaid_cdn_source', 'custom');
update_option('argon_mermaid_cdn_custom_url', 'invalid-url');
update_option('argon_mermaid_use_local', false);
$url13 = argon_get_mermaid_library_url();
test_assert_contains('cdn.jsdelivr.net', $url13, "测试 13: 无效自定义 URL 降级到 jsdelivr");

// 测试 14: 本地镜像优先级最高
update_option('argon_mermaid_cdn_source', 'jsdelivr');
update_option('argon_mermaid_use_local', true);
$url14 = argon_get_mermaid_library_url();
test_assert_contains('/assets/vendor/mermaid/', $url14, "测试 14: 本地镜像优先级最高");

// 测试 15: 未知 CDN 来源降级到 jsdelivr
update_option('argon_mermaid_cdn_source', 'unknown-source');
update_option('argon_mermaid_use_local', false);
$url15 = argon_get_mermaid_library_url();
test_assert_contains('cdn.jsdelivr.net', $url15, "测试 15: 未知 CDN 来源降级到 jsdelivr");

echo "\n=== 测试 argon_get_mermaid_fallback_urls() ===\n\n";

// 测试 16: 备用 URL 列表
$fallback_urls = argon_get_mermaid_fallback_urls();
test_assert(is_array($fallback_urls), "测试 16: 返回数组");
test_assert(count($fallback_urls) === 3, "测试 17: 包含 3 个备用 URL");
test_assert_contains('cdn.jsdelivr.net', $fallback_urls[0], "测试 18: 第一个备用 URL 是 jsdelivr");
test_assert_contains('unpkg.com', $fallback_urls[1], "测试 19: 第二个备用 URL 是 unpkg");
test_assert_contains('/assets/vendor/mermaid/', $fallback_urls[2], "测试 20: 第三个备用 URL 是本地");

echo "\n=== 所有测试完成 ===\n";
