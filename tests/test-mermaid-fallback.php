<?php
/**
 * Mermaid 库加载失败降级处理测试
 * 
 * 测试场景：
 * 1. 主 CDN 加载失败时，自动尝试备用 CDN
 * 2. 所有 CDN 都失败时，显示友好的错误提示
 * 3. 备用 CDN 加载成功后，正常初始化渲染引擎
 */

// 加载 WordPress 环境
require_once dirname(__FILE__) . '/../../../wp-load.php';

// 加载主题函数
require_once get_template_directory() . '/functions.php';

/**
 * 测试 1: 验证备用 CDN URL 列表
 */
function test_fallback_urls() {
	echo "测试 1: 验证备用 CDN URL 列表\n";
	echo str_repeat('-', 50) . "\n";
	
	$fallback_urls = argon_get_mermaid_fallback_urls();
	
	// 验证返回的是数组
	if (!is_array($fallback_urls)) {
		echo "❌ 失败: 返回值不是数组\n";
		return false;
	}
	
	// 验证至少有 3 个备用 URL
	if (count($fallback_urls) < 3) {
		echo "❌ 失败: 备用 URL 数量少于 3 个\n";
		return false;
	}
	
	// 验证每个 URL 都是有效的
	foreach ($fallback_urls as $index => $url) {
		if (empty($url)) {
			echo "❌ 失败: URL #{$index} 为空\n";
			return false;
		}
		
		// 验证 URL 格式
		if (!preg_match('/^https?:\/\/.+\.js$/', $url) && !preg_match('/\/mermaid\.min\.js$/', $url)) {
			echo "❌ 失败: URL #{$index} 格式无效: {$url}\n";
			return false;
		}
		
		echo "✓ URL #{$index}: {$url}\n";
	}
	
	echo "✅ 通过: 备用 CDN URL 列表验证成功\n\n";
	return true;
}

/**
 * 测试 2: 验证降级处理脚本生成
 */
function test_fallback_script_generation() {
	echo "测试 2: 验证降级处理脚本生成\n";
	echo str_repeat('-', 50) . "\n";
	
	// 启用 Mermaid
	update_option('argon_mermaid_enabled', true);
	
	// 创建一个包含 Mermaid 代码块的测试文章
	global $post;
	$post = (object) [
		'ID' => 1,
		'post_content' => '<div class="mermaid">flowchart TD\nA-->B</div>'
	];
	
	// 捕获输出
	ob_start();
	argon_add_mermaid_fallback_script();
	$output = ob_get_clean();
	
	// 验证输出包含必要的脚本
	$checks = [
		'argonMermaidLoadFallback' => '降级处理函数',
		'loadMermaidWithFallback' => '递归加载函数',
		'showGlobalError' => '错误提示函数',
		'fallbackUrls' => '备用 URL 列表',
		'script.onerror' => '错误处理',
		'script.onload' => '加载成功处理'
	];
	
	$all_passed = true;
	foreach ($checks as $keyword => $description) {
		if (strpos($output, $keyword) !== false) {
			echo "✓ 包含 {$description}\n";
		} else {
			echo "❌ 缺少 {$description}\n";
			$all_passed = false;
		}
	}
	
	if ($all_passed) {
		echo "✅ 通过: 降级处理脚本生成正确\n\n";
		return true;
	} else {
		echo "❌ 失败: 降级处理脚本不完整\n\n";
		return false;
	}
}

/**
 * 测试 3: 验证 onerror 属性添加
 */
function test_onerror_attribute() {
	echo "测试 3: 验证 onerror 属性添加\n";
	echo str_repeat('-', 50) . "\n";
	
	// 模拟脚本标签
	$original_tag = '<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>';
	
	// 调用函数添加属性
	$modified_tag = argon_add_mermaid_async_attribute($original_tag, 'mermaid');
	
	// 验证包含 async 属性
	if (strpos($modified_tag, 'async') === false) {
		echo "❌ 失败: 缺少 async 属性\n";
		return false;
	}
	echo "✓ 包含 async 属性\n";
	
	// 验证包含 onerror 属性
	if (strpos($modified_tag, 'onerror') === false) {
		echo "❌ 失败: 缺少 onerror 属性\n";
		return false;
	}
	echo "✓ 包含 onerror 属性\n";
	
	// 验证 onerror 调用正确的函数
	if (strpos($modified_tag, 'argonMermaidLoadFallback()') === false) {
		echo "❌ 失败: onerror 函数名不正确\n";
		return false;
	}
	echo "✓ onerror 调用正确的函数\n";
	
	echo "修改后的标签: {$modified_tag}\n";
	echo "✅ 通过: onerror 属性添加正确\n\n";
	return true;
}

/**
 * 测试 4: 验证非 Mermaid 脚本不受影响
 */
function test_other_scripts_unaffected() {
	echo "测试 4: 验证非 Mermaid 脚本不受影响\n";
	echo str_repeat('-', 50) . "\n";
	
	// 模拟其他脚本标签
	$original_tag = '<script src="https://example.com/other-script.js"></script>';
	
	// 调用函数
	$modified_tag = argon_add_mermaid_async_attribute($original_tag, 'other-script');
	
	// 验证标签未被修改
	if ($original_tag === $modified_tag) {
		echo "✓ 非 Mermaid 脚本未被修改\n";
		echo "✅ 通过: 其他脚本不受影响\n\n";
		return true;
	} else {
		echo "❌ 失败: 非 Mermaid 脚本被错误修改\n";
		echo "原始: {$original_tag}\n";
		echo "修改: {$modified_tag}\n\n";
		return false;
	}
}

/**
 * 测试 5: 验证 JSON 编码的备用 URL
 */
function test_json_encoded_urls() {
	echo "测试 5: 验证 JSON 编码的备用 URL\n";
	echo str_repeat('-', 50) . "\n";
	
	$fallback_urls = argon_get_mermaid_fallback_urls();
	$json = json_encode($fallback_urls);
	
	// 验证 JSON 编码成功
	if ($json === false) {
		echo "❌ 失败: JSON 编码失败\n";
		return false;
	}
	echo "✓ JSON 编码成功\n";
	
	// 验证可以解码回数组
	$decoded = json_decode($json, true);
	if (!is_array($decoded)) {
		echo "❌ 失败: JSON 解码失败\n";
		return false;
	}
	echo "✓ JSON 解码成功\n";
	
	// 验证解码后的数组与原数组一致
	if ($decoded !== $fallback_urls) {
		echo "❌ 失败: 解码后的数组与原数组不一致\n";
		return false;
	}
	echo "✓ 解码后的数组与原数组一致\n";
	
	echo "JSON: {$json}\n";
	echo "✅ 通过: JSON 编码验证成功\n\n";
	return true;
}

// 运行所有测试
echo "\n";
echo "=================================================\n";
echo "Mermaid 库加载失败降级处理测试\n";
echo "=================================================\n\n";

$tests = [
	'test_fallback_urls',
	'test_fallback_script_generation',
	'test_onerror_attribute',
	'test_other_scripts_unaffected',
	'test_json_encoded_urls'
];

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
	if ($test()) {
		$passed++;
	} else {
		$failed++;
	}
}

// 输出测试总结
echo "=================================================\n";
echo "测试总结\n";
echo "=================================================\n";
echo "通过: {$passed} 个测试\n";
echo "失败: {$failed} 个测试\n";

if ($failed === 0) {
	echo "\n✅ 所有测试通过！\n";
	exit(0);
} else {
	echo "\n❌ 部分测试失败，请检查实现。\n";
	exit(1);
}
