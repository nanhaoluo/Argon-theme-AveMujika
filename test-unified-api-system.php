<?php
/**
 * 测试统一 API 管理系统
 * 
 * 访问方式：将此文件放在主题根目录，然后访问：
 * https://your-site.com/wp-content/themes/argon/test-unified-api-system.php
 */

// 加载 WordPress
require_once('../../../wp-load.php');

// 检查权限
if (!current_user_can('manage_options')) {
	wp_die('权限不足');
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>统一 API 系统测试</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			max-width: 1200px;
			margin: 40px auto;
			padding: 20px;
			background: #f5f5f5;
		}
		.test-section {
			background: #fff;
			padding: 20px;
			margin-bottom: 20px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		h1 {
			color: #5e72e4;
			border-bottom: 3px solid #5e72e4;
			padding-bottom: 10px;
		}
		h2 {
			color: #333;
			margin-top: 0;
		}
		.success {
			color: #4caf50;
			font-weight: bold;
		}
		.error {
			color: #f44336;
			font-weight: bold;
		}
		.info {
			color: #2196f3;
		}
		pre {
			background: #f5f5f5;
			padding: 15px;
			border-radius: 4px;
			overflow-x: auto;
			border-left: 4px solid #5e72e4;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 10px;
		}
		th, td {
			padding: 12px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}
		th {
			background: #5e72e4;
			color: white;
		}
		.badge {
			display: inline-block;
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 12px;
			font-weight: bold;
		}
		.badge-success {
			background: #4caf50;
			color: white;
		}
		.badge-warning {
			background: #ff9800;
			color: white;
		}
		.badge-info {
			background: #2196f3;
			color: white;
		}
	</style>
</head>
<body>
	<h1>🧪 统一 API 管理系统测试</h1>
	
	<!-- 测试 1: 检查函数是否存在 -->
	<div class="test-section">
		<h2>1️⃣ 函数存在性检查</h2>
		<?php
		$functions = [
			'argon_migrate_ai_apis',
			'argon_get_all_apis',
			'argon_get_api_by_id',
			'argon_add_api',
			'argon_update_api',
			'argon_delete_api',
			'argon_set_active_api_for_scenario',
			'argon_get_active_api_config'
		];
		
		$all_exist = true;
		echo '<table>';
		echo '<tr><th>函数名</th><th>状态</th></tr>';
		foreach ($functions as $func) {
			$exists = function_exists($func);
			$all_exist = $all_exist && $exists;
			echo '<tr>';
			echo '<td><code>' . esc_html($func) . '</code></td>';
			echo '<td>' . ($exists ? '<span class="success">✓ 存在</span>' : '<span class="error">✗ 不存在</span>') . '</td>';
			echo '</tr>';
		}
		echo '</table>';
		
		if ($all_exist) {
			echo '<p class="success">✓ 所有核心函数都已正确加载</p>';
		} else {
			echo '<p class="error">✗ 部分函数缺失，请检查 functions.php</p>';
		}
		?>
	</div>
	
	<!-- 测试 2: 检查数据迁移状态 -->
	<div class="test-section">
		<h2>2️⃣ 数据迁移状态</h2>
		<?php
		$migrated = get_option('argon_ai_apis_migrated', 'false');
		echo '<p><strong>迁移状态：</strong> ';
		if ($migrated === 'true') {
			echo '<span class="success">✓ 已迁移</span>';
		} else {
			echo '<span class="info">ℹ 未迁移（将在首次访问时自动迁移）</span>';
		}
		echo '</p>';
		
		// 手动触发迁移
		if ($migrated !== 'true') {
			echo '<p class="info">正在执行数据迁移...</p>';
			argon_migrate_ai_apis();
			$migrated = get_option('argon_ai_apis_migrated', 'false');
			if ($migrated === 'true') {
				echo '<p class="success">✓ 数据迁移成功</p>';
			}
		}
		?>
	</div>
	
	<!-- 测试 3: 查看所有 API 配置 -->
	<div class="test-section">
		<h2>3️⃣ 当前 API 配置</h2>
		<?php
		$all_apis = argon_get_all_apis();
		$summary_active = get_option('argon_ai_summary_active_api', '');
		$spam_active = get_option('argon_ai_spam_active_api', '');
		
		if (empty($all_apis)) {
			echo '<p class="info">ℹ 暂无 API 配置</p>';
			echo '<p>这可能是因为：</p>';
			echo '<ul>';
			echo '<li>您还没有配置任何 AI API</li>';
			echo '<li>旧数据迁移未找到任何配置</li>';
			echo '</ul>';
		} else {
			echo '<p class="success">✓ 找到 ' . count($all_apis) . ' 个 API 配置</p>';
			echo '<table>';
			echo '<tr><th>ID</th><th>名称</th><th>提供商</th><th>模型</th><th>密钥</th><th>使用场景</th></tr>';
			foreach ($all_apis as $api) {
				echo '<tr>';
				echo '<td><code>' . esc_html($api['id']) . '</code></td>';
				echo '<td>' . esc_html($api['name']) . '</td>';
				echo '<td>' . esc_html($api['provider']) . '</td>';
				echo '<td>' . esc_html($api['model'] ?: '默认') . '</td>';
				echo '<td><code>' . esc_html(substr($api['api_key'], 0, 12)) . '...</code></td>';
				echo '<td>';
				if ($api['id'] === $summary_active) {
					echo '<span class="badge badge-success">文章摘要</span> ';
				}
				if ($api['id'] === $spam_active) {
					echo '<span class="badge badge-warning">评论审核</span>';
				}
				if ($api['id'] !== $summary_active && $api['id'] !== $spam_active) {
					echo '<span class="badge badge-info">未使用</span>';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
		?>
	</div>
	
	<!-- 测试 4: 检查旧数据 -->
	<div class="test-section">
		<h2>4️⃣ 旧数据检查</h2>
		<?php
		$providers = ['openai', 'anthropic', 'deepseek', 'xiaomi', 'qianwen', 'wenxin', 'doubao', 'kimi', 'zhipu', 'siliconflow'];
		$old_data_found = false;
		
		echo '<table>';
		echo '<tr><th>提供商</th><th>旧数据状态</th><th>API 数量</th></tr>';
		foreach ($providers as $provider) {
			$old_apis = get_option("argon_ai_{$provider}_apis", []);
			$has_data = !empty($old_apis) && is_array($old_apis);
			if ($has_data) {
				$old_data_found = true;
			}
			echo '<tr>';
			echo '<td>' . esc_html($provider) . '</td>';
			echo '<td>' . ($has_data ? '<span class="info">有数据</span>' : '<span>无数据</span>') . '</td>';
			echo '<td>' . ($has_data ? count($old_apis) : 0) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
		
		if ($old_data_found) {
			echo '<p class="info">ℹ 发现旧数据，已保留作为备份</p>';
		} else {
			echo '<p>未发现旧数据</p>';
		}
		?>
	</div>
	
	<!-- 测试 5: 测试场景化 API 获取 -->
	<div class="test-section">
		<h2>5️⃣ 场景化 API 配置测试</h2>
		<?php
		echo '<h3>文章摘要场景</h3>';
		$summary_config = argon_get_active_api_config('summary');
		if (!empty($summary_config['api_key'])) {
			echo '<p class="success">✓ 成功获取文章摘要 API 配置</p>';
			echo '<pre>' . print_r($summary_config, true) . '</pre>';
		} else {
			echo '<p class="error">✗ 未配置文章摘要 API</p>';
		}
		
		echo '<h3>评论审核场景</h3>';
		$spam_config = argon_get_active_api_config('spam');
		if (!empty($spam_config['api_key'])) {
			echo '<p class="success">✓ 成功获取评论审核 API 配置</p>';
			echo '<pre>' . print_r($spam_config, true) . '</pre>';
		} else {
			echo '<p class="error">✗ 未配置评论审核 API</p>';
		}
		?>
	</div>
	
	<!-- 测试 6: 向后兼容性测试 -->
	<div class="test-section">
		<h2>6️⃣ 向后兼容性测试</h2>
		<?php
		$test_provider = 'openai';
		$old_config = argon_get_ai_provider_config($test_provider);
		
		echo '<p>测试 <code>argon_get_ai_provider_config(\'' . $test_provider . '\')</code></p>';
		if (!empty($old_config['api_key'])) {
			echo '<p class="success">✓ 向后兼容函数正常工作</p>';
			echo '<pre>' . print_r($old_config, true) . '</pre>';
		} else {
			echo '<p class="info">ℹ 未找到 ' . $test_provider . ' 的配置（这是正常的，如果您没有配置该提供商）</p>';
		}
		?>
	</div>
	
	<!-- 测试总结 -->
	<div class="test-section">
		<h2>📊 测试总结</h2>
		<?php
		$all_apis = argon_get_all_apis();
		$has_apis = !empty($all_apis);
		$functions_ok = $all_exist;
		$migrated_ok = get_option('argon_ai_apis_migrated', 'false') === 'true';
		
		echo '<ul>';
		echo '<li>' . ($functions_ok ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . ' 核心函数加载</li>';
		echo '<li>' . ($migrated_ok ? '<span class="success">✓</span>' : '<span class="info">ℹ</span>') . ' 数据迁移</li>';
		echo '<li>' . ($has_apis ? '<span class="success">✓</span>' : '<span class="info">ℹ</span>') . ' API 配置</li>';
		echo '</ul>';
		
		if ($functions_ok && $migrated_ok) {
			echo '<p class="success"><strong>✓ 统一 API 管理系统已就绪！</strong></p>';
			if (!$has_apis) {
				echo '<p class="info">提示：您可以在主题设置页面添加 API 配置</p>';
			}
		} else {
			echo '<p class="error"><strong>✗ 系统未完全就绪，请检查上述问题</strong></p>';
		}
		?>
	</div>
	
	<div class="test-section">
		<h2>🔗 相关链接</h2>
		<ul>
			<li><a href="<?php echo admin_url('themes.php?page=argon-theme-options'); ?>">主题设置页面</a></li>
			<li><a href="<?php echo home_url('/ai-summary-query/'); ?>">AI 查询页面</a></li>
		</ul>
	</div>
</body>
</html>
