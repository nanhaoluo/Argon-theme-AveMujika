<?php
/**
 * 小米 Mimo API 测试脚本
 * 用于诊断 API 请求失败的问题
 */

// 加载 WordPress
require_once('../../wp-load.php');

// 测试配置
$test_api_key = 'YOUR_API_KEY_HERE'; // 替换为实际的 API 密钥
$test_endpoint = 'https://api.mimo.xiaomi.com/v1/chat/completions';
$test_model = 'MiMo-V2-Flash';

echo "<h2>小米 Mimo API 测试</h2>\n";
echo "<pre>\n";

// 测试 1: 基本请求
echo "=== 测试 1: 基本 API 请求 ===\n";
$data = [
	'model' => $test_model,
	'messages' => [
		['role' => 'system', 'content' => '你是一个有帮助的助手。'],
		['role' => 'user', 'content' => '你好，请用一句话介绍你自己。']
	],
	'temperature' => 0.7,
	'max_tokens' => 100
];

echo "请求端点: $test_endpoint\n";
echo "模型: $test_model\n";
echo "请求数据:\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$response = wp_remote_post($test_endpoint, [
	'headers' => [
		'Content-Type' => 'application/json',
		'Authorization' => 'Bearer ' . $test_api_key
	],
	'body' => json_encode($data),
	'timeout' => 30
]);

if (is_wp_error($response)) {
	echo "❌ 请求失败: " . $response->get_error_message() . "\n";
} else {
	$status_code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$headers = wp_remote_retrieve_headers($response);
	
	echo "状态码: $status_code\n";
	echo "响应头:\n";
	foreach ($headers as $key => $value) {
		echo "  $key: $value\n";
	}
	echo "\n响应体:\n";
	
	$json_body = json_decode($body, true);
	if ($json_body) {
		echo json_encode($json_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
	} else {
		echo $body . "\n";
	}
}

echo "\n";

// 测试 2: 不同的请求格式
echo "=== 测试 2: 简化的请求格式 ===\n";
$data2 = [
	'model' => $test_model,
	'messages' => [
		['role' => 'user', 'content' => '你好']
	]
];

echo "请求数据:\n";
echo json_encode($data2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$response2 = wp_remote_post($test_endpoint, [
	'headers' => [
		'Content-Type' => 'application/json',
		'Authorization' => 'Bearer ' . $test_api_key
	],
	'body' => json_encode($data2),
	'timeout' => 30
]);

if (is_wp_error($response2)) {
	echo "❌ 请求失败: " . $response2->get_error_message() . "\n";
} else {
	$status_code2 = wp_remote_retrieve_response_code($response2);
	$body2 = wp_remote_retrieve_body($response2);
	
	echo "状态码: $status_code2\n";
	echo "响应体:\n";
	
	$json_body2 = json_decode($body2, true);
	if ($json_body2) {
		echo json_encode($json_body2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
	} else {
		echo $body2 . "\n";
	}
}

echo "\n";

// 测试 3: 获取模型列表
echo "=== 测试 3: 获取模型列表 ===\n";
$models_endpoint = 'https://api.mimo.xiaomi.com/v1/models';
echo "请求端点: $models_endpoint\n\n";

$response3 = wp_remote_get($models_endpoint, [
	'headers' => [
		'Authorization' => 'Bearer ' . $test_api_key
	],
	'timeout' => 30
]);

if (is_wp_error($response3)) {
	echo "❌ 请求失败: " . $response3->get_error_message() . "\n";
} else {
	$status_code3 = wp_remote_retrieve_response_code($response3);
	$body3 = wp_remote_retrieve_body($response3);
	
	echo "状态码: $status_code3\n";
	echo "响应体:\n";
	
	$json_body3 = json_decode($body3, true);
	if ($json_body3) {
		echo json_encode($json_body3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
	} else {
		echo $body3 . "\n";
	}
}

echo "\n";

// 测试 4: 使用 cURL 直接测试
echo "=== 测试 4: cURL 命令示例 ===\n";
echo "你可以在命令行中运行以下命令来测试 API:\n\n";
echo "curl -X POST https://api.mimo.xiaomi.com/v1/chat/completions \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_API_KEY\" \\\n";
echo "  -d '{\n";
echo "    \"model\": \"MiMo-V2-Flash\",\n";
echo "    \"messages\": [\n";
echo "      {\"role\": \"user\", \"content\": \"你好\"}\n";
echo "    ]\n";
echo "  }'\n";

echo "</pre>\n";
