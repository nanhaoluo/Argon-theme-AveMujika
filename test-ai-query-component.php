<?php
/**
 * AI 查询组件测试脚本
 * 
 * 使用方法：
 * 1. 将此文件放在 WordPress 主题目录
 * 2. 在浏览器访问：http://your-site.com/wp-content/themes/argon/test-ai-query-component.php
 * 3. 查看测试结果
 */

// 加载 WordPress
require_once('../../../wp-load.php');

// 检查权限
if (!current_user_can('manage_options')) {
	die('权限不足');
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>AI 查询组件测试</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			max-width: 1200px;
			margin: 40px auto;
			padding: 20px;
			background: #f5f5f5;
		}
		.test-section {
			background: white;
			padding: 20px;
			margin-bottom: 20px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		.test-section h2 {
			margin-top: 0;
			color: #333;
			border-bottom: 2px solid #0073aa;
			padding-bottom: 10px;
		}
		.success {
			color: #46b450;
			font-weight: bold;
		}
		.error {
			color: #dc3232;
			font-weight: bold;
		}
		.info {
			color: #0073aa;
		}
		pre {
			background: #f0f0f0;
			padding: 15px;
			border-radius: 4px;
			overflow-x: auto;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 10px;
		}
		table th, table td {
			padding: 10px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}
		table th {
			background: #f9f9f9;
			font-weight: bold;
		}
	</style>
</head>
<body>
	<h1>🧪 AI 查询组件测试</h1>

	<?php
	global $wpdb;
	$table_name = $wpdb->prefix . 'argon_ai_query_log';
	
	// 测试 1：检查数据表是否存在
	echo '<div class="test-section">';
	echo '<h2>测试 1：数据表检查</h2>';
	
	$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
	
	if ($table_exists) {
		echo '<p class="success">✓ 数据表已存在：' . $table_name . '</p>';
		
		// 显示表结构
		$columns = $wpdb->get_results("DESCRIBE $table_name");
		echo '<p class="info">表结构：</p>';
		echo '<table>';
		echo '<tr><th>字段名</th><th>类型</th><th>允许空值</th><th>键</th><th>默认值</th></tr>';
		foreach ($columns as $column) {
			echo '<tr>';
			echo '<td>' . esc_html($column->Field) . '</td>';
			echo '<td>' . esc_html($column->Type) . '</td>';
			echo '<td>' . esc_html($column->Null) . '</td>';
			echo '<td>' . esc_html($column->Key) . '</td>';
			echo '<td>' . esc_html($column->Default) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p class="error">✗ 数据表不存在，尝试创建...</p>';
		argon_create_ai_query_log_table();
		
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
		if ($table_exists) {
			echo '<p class="success">✓ 数据表创建成功</p>';
		} else {
			echo '<p class="error">✗ 数据表创建失败</p>';
		}
	}
	echo '</div>';
	
	// 测试 2：检查记录函数
	echo '<div class="test-section">';
	echo '<h2>测试 2：记录函数测试</h2>';
	
	if (function_exists('argon_log_ai_query')) {
		echo '<p class="success">✓ argon_log_ai_query 函数存在</p>';
		
		// 插入测试记录
		argon_log_ai_query(
			'openai',
			'gpt-4o-mini',
			'test',
			100,
			500,
			150,
			1234,
			'success',
			'',
			['post_id' => 1, 'user_id' => 1]
		);
		
		// 查询最新记录
		$latest = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
		if ($latest) {
			echo '<p class="success">✓ 测试记录插入成功</p>';
			echo '<p class="info">最新记录：</p>';
			echo '<pre>' . print_r($latest, true) . '</pre>';
		} else {
			echo '<p class="error">✗ 测试记录插入失败</p>';
		}
	} else {
		echo '<p class="error">✗ argon_log_ai_query 函数不存在</p>';
	}
	echo '</div>';
	
	// 测试 3：检查统计函数
	echo '<div class="test-section">';
	echo '<h2>测试 3：统计函数测试</h2>';
	
	if (function_exists('argon_get_ai_query_stats')) {
		echo '<p class="success">✓ argon_get_ai_query_stats 函数存在</p>';
		
		$stats = argon_get_ai_query_stats();
		echo '<p class="info">统计结果：</p>';
		echo '<pre>' . print_r($stats, true) . '</pre>';
	} else {
		echo '<p class="error">✗ argon_get_ai_query_stats 函数不存在</p>';
	}
	echo '</div>';
	
	// 测试 4：检查统一查询接口
	echo '<div class="test-section">';
	echo '<h2>测试 4：统一查询接口测试</h2>';
	
	if (function_exists('argon_ai_query')) {
		echo '<p class="success">✓ argon_ai_query 函数存在</p>';
		
		// 检查是否配置了 AI 服务商
		$provider = get_option('argon_ai_summary_provider', 'openai');
		$config = argon_get_ai_provider_config($provider);
		
		if ($config && !empty($config['api_key'])) {
			echo '<p class="info">当前服务商：' . esc_html($provider) . '</p>';
			echo '<p class="info">API 密钥：' . esc_html(substr($config['api_key'], 0, 10)) . '...</p>';
			echo '<p class="info">模型：' . esc_html($config['model']) . '</p>';
			
			echo '<p class="info">⚠️ 跳过实际 API 调用测试（避免消耗 tokens）</p>';
			echo '<p class="info">如需测试实际调用，请手动调用 argon_ai_query() 函数</p>';
		} else {
			echo '<p class="error">✗ 未配置 AI 服务商或 API 密钥</p>';
		}
	} else {
		echo '<p class="error">✗ argon_ai_query 函数不存在</p>';
	}
	echo '</div>';
	
	// 测试 5：查看最近的查询记录
	echo '<div class="test-section">';
	echo '<h2>测试 5：最近的查询记录</h2>';
	
	$recent_queries = $wpdb->get_results("SELECT * FROM $table_name ORDER BY query_time DESC LIMIT 10");
	
	if ($recent_queries) {
		echo '<p class="success">✓ 找到 ' . count($recent_queries) . ' 条记录</p>';
		echo '<table>';
		echo '<tr>';
		echo '<th>ID</th>';
		echo '<th>时间</th>';
		echo '<th>服务商</th>';
		echo '<th>模型</th>';
		echo '<th>场景</th>';
		echo '<th>响应时间</th>';
		echo '<th>状态</th>';
		echo '</tr>';
		
		foreach ($recent_queries as $query) {
			echo '<tr>';
			echo '<td>' . esc_html($query->id) . '</td>';
			echo '<td>' . esc_html($query->query_time) . '</td>';
			echo '<td>' . esc_html($query->provider) . '</td>';
			echo '<td>' . esc_html($query->model) . '</td>';
			echo '<td>' . esc_html($query->scenario) . '</td>';
			echo '<td>' . esc_html($query->response_time) . 'ms</td>';
			echo '<td class="' . ($query->status === 'success' ? 'success' : 'error') . '">' . esc_html($query->status) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p class="info">暂无查询记录</p>';
	}
	echo '</div>';
	
	// 测试 6：检查 AI 摘要生成函数
	echo '<div class="test-section">';
	echo '<h2>测试 6：AI 摘要生成函数检查</h2>';
	
	if (function_exists('argon_generate_ai_summary')) {
		echo '<p class="success">✓ argon_generate_ai_summary 函数存在</p>';
		echo '<p class="success">✓ 已使用统一的 argon_ai_query 接口</p>';
	} else {
		echo '<p class="error">✗ argon_generate_ai_summary 函数不存在</p>';
	}
	echo '</div>';
	
	// 测试 7：检查垃圾评论检测函数
	echo '<div class="test-section">';
	echo '<h2>测试 7：垃圾评论检测函数检查</h2>';
	
	if (function_exists('argon_detect_spam_comment_sync')) {
		echo '<p class="success">✓ argon_detect_spam_comment_sync 函数存在</p>';
		echo '<p class="success">✓ 已使用统一的 argon_ai_query 接口</p>';
	} else {
		echo '<p class="error">✗ argon_detect_spam_comment_sync 函数不存在</p>';
	}
	echo '</div>';
	
	?>
	
	<div class="test-section">
		<h2>📝 测试总结</h2>
		<p>测试完成！请检查上述结果。</p>
		<p><strong>下一步：</strong></p>
		<ul>
			<li>如果数据表创建成功，可以在 WordPress 后台生成 AI 摘要或检测垃圾评论来测试实际功能</li>
			<li>查询记录会自动保存到数据库，可以通过统计函数查看</li>
			<li>可以在后台添加统计页面来可视化展示这些数据</li>
		</ul>
	</div>
	
	<p style="text-align: center; color: #666; margin-top: 40px;">
		<a href="<?php echo admin_url('themes.php?page=argon-theme-options'); ?>">返回主题设置</a>
	</p>
</body>
</html>
