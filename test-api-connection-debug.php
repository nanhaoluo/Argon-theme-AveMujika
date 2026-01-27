<?php
/**
 * 测试 API 连通性调试脚本
 */

// 加载 WordPress
require_once('../../../wp-load.php');

// 检查是否登录且有管理员权限
if (!is_user_logged_in() || !current_user_can('manage_options')) {
	die('需要管理员权限');
}

echo '<h1>API 连通性调试</h1>';
echo '<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:4px;}</style>';

// 1. 检查是否有 API 配置
echo '<h2>1. 检查 API 配置</h2>';
$apis = get_option('argon_ai_apis', []);
if (empty($apis)) {
	echo '<p class="error">✗ 没有配置任何 API</p>';
	die();
} else {
	echo '<p class="success">✓ 找到 ' . count($apis) . ' 个 API 配置</p>';
	echo '<pre>' . print_r($apis, true) . '</pre>';
}

// 2. 获取第一个 API 进行测试
$test_api = $apis[0];
$api_id = $test_api['id'];
echo '<h2>2. 测试 API: ' . esc_html($test_api['name']) . '</h2>';
echo '<p class="info">API ID: ' . esc_html($api_id) . '</p>';
echo '<p class="info">提供商: ' . esc_html($test_api['provider']) . '</p>';
echo '<p class="info">模型: ' . esc_html($test_api['model']) . '</p>';

// 3. 测试 argon_get_api_by_id 函数
echo '<h2>3. 测试 argon_get_api_by_id 函数</h2>';
$api = argon_get_api_by_id($api_id);
if ($api) {
	echo '<p class="success">✓ 成功获取 API 配置</p>';
	echo '<pre>' . print_r($api, true) . '</pre>';
} else {
	echo '<p class="error">✗ 无法获取 API 配置</p>';
	die();
}

// 4. 获取默认端点
echo '<h2>4. 获取 API 端点</h2>';
$default_endpoints = [
	'openai' => 'https://api.openai.com/v1/chat/completions',
	'anthropic' => 'https://api.anthropic.com/v1/messages',
	'deepseek' => 'https://api.deepseek.com/v1/chat/completions',
	'xiaomi' => 'https://api.mimo.xiaomi.com/v1/chat/completions',
	'qianwen' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
	'wenxin' => 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions',
	'doubao' => 'https://ark.cn-beijing.volces.com/api/v3/chat/completions',
	'kimi' => 'https://api.moonshot.cn/v1/chat/completions',
	'zhipu' => 'https://open.bigmodel.cn/api/paas/v4/chat/completions',
	'siliconflow' => 'https://api.siliconflow.cn/v1/chat/completions'
];

$api_endpoint = !empty($api['api_endpoint']) ? $api['api_endpoint'] : (isset($default_endpoints[$api['provider']]) ? $default_endpoints[$api['provider']] : '');

if (empty($api_endpoint)) {
	echo '<p class="error">✗ 未配置 API 端点</p>';
	die();
} else {
	echo '<p class="success">✓ API 端点: ' . esc_html($api_endpoint) . '</p>';
}

// 5. 构建测试请求
echo '<h2>5. 构建测试请求</h2>';
$model = !empty($api['model']) ? $api['model'] : 'gpt-4o-mini';
$data = [
	'model' => $model,
	'messages' => [
		[
			'role' => 'user',
			'content' => '你好，这是一个测试。请回复"测试成功"。'
		]
	],
	'max_tokens' => 50,
	'stream' => false
];

echo '<p class="info">请求数据:</p>';
echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';

// 6. 发送请求
echo '<h2>6. 发送 API 请求</h2>';
$start_time = microtime(true);

$response = wp_remote_post($api_endpoint, [
	'headers' => [
		'Content-Type' => 'application/json',
		'Authorization' => 'Bearer ' . $api['api_key'],
		'Accept' => 'application/json'
	],
	'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
	'timeout' => 30,
	'sslverify' => true
]);

$response_time = round((microtime(true) - $start_time) * 1000);
echo '<p class="info">响应时间: ' . $response_time . 'ms</p>';

// 7. 检查响应
echo '<h2>7. 检查响应</h2>';
if (is_wp_error($response)) {
	echo '<p class="error">✗ 请求失败: ' . $response->get_error_message() . '</p>';
	die();
}

$status_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo '<p class="info">HTTP 状态码: ' . $status_code . '</p>';
echo '<p class="info">响应内容:</p>';
echo '<pre>' . esc_html($body) . '</pre>';

// 8. 解析响应
echo '<h2>8. 解析响应</h2>';
if ($status_code === 200) {
	$result = json_decode($body, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		echo '<p class="error">✗ JSON 解析失败: ' . json_last_error_msg() . '</p>';
	} else {
		echo '<p class="success">✓ JSON 解析成功</p>';
		echo '<pre>' . print_r($result, true) . '</pre>';
		
		if (isset($result['choices'][0]['message']['content']) || isset($result['content'])) {
			echo '<p class="success">✓✓✓ API 测试成功！</p>';
		} else {
			echo '<p class="error">✗ API 返回格式异常（缺少 content 字段）</p>';
		}
	}
} else {
	echo '<p class="error">✗ HTTP 状态码错误: ' . $status_code . '</p>';
	$result = json_decode($body, true);
	if ($result && isset($result['error'])) {
		echo '<p class="error">错误信息: ' . print_r($result['error'], true) . '</p>';
	}
}

// 9. 模拟 AJAX 调用
echo '<h2>9. 模拟 AJAX 函数调用</h2>';
echo '<p class="info">现在模拟调用 argon_ajax_test_unified_api 函数...</p>';

// 设置 POST 数据
$_POST['api_id'] = $api_id;
$_POST['nonce'] = wp_create_nonce('argon_test_unified_api');

// 捕获输出
ob_start();
try {
	argon_ajax_test_unified_api();
} catch (Exception $e) {
	echo '<p class="error">✗ 函数执行异常: ' . $e->getMessage() . '</p>';
}
$ajax_output = ob_get_clean();

echo '<p class="info">AJAX 函数输出:</p>';
echo '<pre>' . esc_html($ajax_output) . '</pre>';

// 尝试解析 JSON
$ajax_result = json_decode($ajax_output, true);
if (json_last_error() === JSON_ERROR_NONE) {
	echo '<p class="success">✓ AJAX 返回有效 JSON</p>';
	echo '<pre>' . print_r($ajax_result, true) . '</pre>';
} else {
	echo '<p class="error">✗ AJAX 返回不是有效 JSON: ' . json_last_error_msg() . '</p>';
}
