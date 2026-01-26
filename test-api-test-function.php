<?php
/**
 * 测试 API 测试功能
 * 访问: http://your-site.com/wp-content/themes/argon/test-api-test-function.php
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
	wp_die('权限不足');
}

echo '<h1>API 测试功能诊断</h1>';

// 1. 检查函数是否存在
echo '<h2>1. 函数存在性检查</h2>';
$functions = [
	'argon_ajax_test_unified_api',
	'argon_get_api_by_id',
	'argon_get_all_apis'
];

foreach ($functions as $func) {
	if (function_exists($func)) {
		echo '<p style="color: green;">✓ ' . $func . ' 存在</p>';
	} else {
		echo '<p style="color: red;">✗ ' . $func . ' 不存在</p>';
	}
}

// 2. 检查是否有 API 配置
echo '<h2>2. API 配置检查</h2>';
$all_apis = argon_get_all_apis();
if (empty($all_apis)) {
	echo '<p style="color: orange;">⚠ 没有配置任何 API</p>';
} else {
	echo '<p style="color: green;">✓ 已配置 ' . count($all_apis) . ' 个 API</p>';
	foreach ($all_apis as $api) {
		echo '<div style="margin: 10px 0; padding: 10px; background: #f5f5f5; border-left: 4px solid #5e72e4;">';
		echo '<strong>' . esc_html($api['name']) . '</strong><br>';
		echo 'ID: ' . esc_html($api['id']) . '<br>';
		echo '提供商: ' . esc_html($api['provider']) . '<br>';
		echo '模型: ' . esc_html($api['model']) . '<br>';
		echo '</div>';
	}
}

// 3. 检查 AJAX action 是否注册
echo '<h2>3. AJAX Action 注册检查</h2>';
global $wp_filter;
if (isset($wp_filter['wp_ajax_argon_ajax_test_unified_api'])) {
	echo '<p style="color: green;">✓ wp_ajax_argon_ajax_test_unified_api 已注册</p>';
} else {
	echo '<p style="color: red;">✗ wp_ajax_argon_ajax_test_unified_api 未注册</p>';
}

// 4. 测试 nonce 生成
echo '<h2>4. Nonce 生成测试</h2>';
$nonce = wp_create_nonce('argon_test_unified_api');
echo '<p>生成的 nonce: <code>' . $nonce . '</code></p>';
echo '<p>验证结果: ';
if (wp_verify_nonce($nonce, 'argon_test_unified_api')) {
	echo '<span style="color: green;">✓ 验证通过</span></p>';
} else {
	echo '<span style="color: red;">✗ 验证失败</span></p>';
}

// 5. 模拟 AJAX 请求测试
if (!empty($all_apis)) {
	echo '<h2>5. 模拟 AJAX 请求测试</h2>';
	$test_api = $all_apis[0];
	echo '<p>测试 API: ' . esc_html($test_api['name']) . '</p>';
	
	// 模拟 $_POST 数据
	$_POST['api_id'] = $test_api['id'];
	$_POST['nonce'] = wp_create_nonce('argon_test_unified_api');
	
	echo '<p>开始测试...</p>';
	
	// 获取 API 配置
	$api = argon_get_api_by_id($test_api['id']);
	if (!$api) {
		echo '<p style="color: red;">✗ 无法获取 API 配置</p>';
	} else {
		echo '<p style="color: green;">✓ 成功获取 API 配置</p>';
		
		// 获取默认端点
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
		
		$api_endpoint = !empty($api['api_endpoint']) ? $api['api_endpoint'] : $default_endpoints[$api['provider']];
		$model = !empty($api['model']) ? $api['model'] : 'gpt-4o-mini';
		
		echo '<p>API 端点: <code>' . esc_html($api_endpoint) . '</code></p>';
		echo '<p>模型: <code>' . esc_html($model) . '</code></p>';
		
		// 构建测试请求
		$data = [
			'model' => $model,
			'messages' => [
				[
					'role' => 'user',
					'content' => '你好，这是一个测试。请回复"测试成功"。'
				]
			],
			'max_tokens' => 50
		];
		
		echo '<p>发送测试请求...</p>';
		$start_time = microtime(true);
		
		$response = wp_remote_post($api_endpoint, [
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $api['api_key']
			],
			'body' => json_encode($data),
			'timeout' => 30
		]);
		
		$response_time = round((microtime(true) - $start_time) * 1000);
		
		if (is_wp_error($response)) {
			echo '<p style="color: red;">✗ 请求失败: ' . $response->get_error_message() . '</p>';
		} else {
			$status_code = wp_remote_retrieve_response_code($response);
			$body = wp_remote_retrieve_body($response);
			
			echo '<p>HTTP 状态码: <code>' . $status_code . '</code></p>';
			echo '<p>响应时间: <code>' . $response_time . 'ms</code></p>';
			
			if ($status_code === 200) {
				$result = json_decode($body, true);
				if (isset($result['choices'][0]['message']['content']) || isset($result['content'])) {
					echo '<p style="color: green;">✓ API 测试成功！</p>';
					if (isset($result['choices'][0]['message']['content'])) {
						echo '<p>AI 回复: ' . esc_html($result['choices'][0]['message']['content']) . '</p>';
					}
				} else {
					echo '<p style="color: red;">✗ API 返回格式异常</p>';
					echo '<pre>' . htmlspecialchars($body) . '</pre>';
				}
			} else {
				echo '<p style="color: red;">✗ API 返回错误</p>';
				echo '<pre>' . htmlspecialchars($body) . '</pre>';
			}
		}
	}
}

echo '<hr>';
echo '<h2>测试完成</h2>';
echo '<p><a href="' . admin_url('themes.php?page=argon-theme-options') . '">返回设置页</a></p>';
?>
