<?php
/**
 * AI 内容验证与查询页面
 * @package Argon
 */

$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) $wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
require_once($wp_load_path);

get_header();

$query_code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
$result = null;
$error = '';

if (!empty($query_code)) {
	if (strlen($query_code) !== 8) {
		$error = __('识别码格式无效，应为 8 位字符', 'argon');
	} else {
		global $wpdb;
		$post_id = $wpdb->get_var($wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_argon_ai_summary_code' AND meta_value = %s",
			$query_code
		));
		
		if ($post_id) {
			$post = get_post($post_id);
			if ($post && $post->post_status === 'publish') {
				$result = [
					'post_id' => $post_id,
					'post_title' => get_the_title($post_id),
					'post_url' => get_permalink($post_id),
					'post_date' => get_the_date('Y-m-d H:i:s', $post_id),
					'post_modified' => get_the_modified_date('Y-m-d H:i:s', $post_id),
					'post_author' => get_the_author_meta('display_name', $post->post_author),
					'summary' => get_post_meta($post_id, '_argon_ai_summary', true),
					'summary_hash' => get_post_meta($post_id, '_argon_ai_summary_hash', true),
					'model' => get_post_meta($post_id, '_argon_ai_summary_model', true),
					'provider' => get_post_meta($post_id, '_argon_ai_summary_provider', true),
					'generated_time' => get_post_meta($post_id, '_argon_ai_summary_time', true),
					'code' => $query_code
				];
				
				// 提供商名称映射
				$provider_names = [
					'openai' => 'OpenAI',
					'anthropic' => 'Anthropic',
					'deepseek' => 'DeepSeek',
					'qianwen' => '通义千问',
					'wenxin' => '文心一言',
					'doubao' => '豆包',
					'kimi' => 'Kimi',
					'zhipu' => '智谱',
					'siliconflow' => 'SiliconFlow'
				];
				
				$result['provider_display'] = isset($provider_names[$result['provider']]) ? $provider_names[$result['provider']] : $result['provider'];
				
				// 检查内容是否被修改
				$current_hash = md5($post->post_content . $post->post_title);
				$result['is_modified'] = ($result['summary_hash'] !== $current_hash);
			} else {
				$error = __('文章不存在或未发布', 'argon');
			}
		} else {
			$error = __('未找到对应的 AI 生成内容记录', 'argon');
		}
	}
}
?>

<style>
.ai-verify-page {
	max-width: 900px;
	margin: 0 auto;
}
.ai-verify-card {
	background: var(--color-foreground);
	border-radius: var(--card-radius);
	box-shadow: var(--card-shadow);
	padding: 2rem;
	margin-bottom: 1.5rem;
}
.ai-verify-header {
	margin-bottom: 2rem;
	padding-bottom: 1.5rem;
	border-bottom: 1px solid var(--color-border);
}
.ai-verify-title {
	font-size: 1.75rem;
	font-weight: 600;
	color: var(--color-text-deeper);
	margin: 0 0 0.5rem 0;
}
.ai-verify-subtitle {
	font-size: 0.9375rem;
	color: var(--color-text-muted);
	margin: 0;
	line-height: 1.6;
}
.ai-query-form {
	margin-bottom: 2rem;
}
.ai-query-input {
	flex: 1;
	min-width: 200px;
	padding: 0.625rem 1rem;
	border: 1px solid var(--color-border);
	border-radius: var(--card-radius);
	font-family: 'Consolas', 'Monaco', monospace;
	font-size: 1rem;
	letter-spacing: 1px;
	background: var(--color-foreground);
	color: var(--color-text);
	transition: all var(--animation-fast) var(--ease-standard);
}
.ai-query-input:focus {
	outline: none;
	border-color: var(--themecolor);
	box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.1);
}
.ai-alert {
	padding: 1rem 1.25rem;
	border-radius: var(--card-radius);
	margin-bottom: 1.5rem;
	display: flex;
	align-items: flex-start;
	gap: 0.75rem;
}
.ai-alert-warning {
	background: rgba(255, 193, 7, 0.1);
	border: 1px solid rgba(255, 193, 7, 0.3);
	color: var(--color-text);
}
.ai-alert-info {
	background: rgba(var(--themecolor-rgbstr), 0.08);
	border: 1px solid rgba(var(--themecolor-rgbstr), 0.2);
	color: var(--color-text);
}
.ai-alert-icon {
	flex-shrink: 0;
	margin-top: 0.125rem;
}
.ai-section {
	margin-bottom: 2rem;
}
.ai-section:last-child {
	margin-bottom: 0;
}
.ai-section-title {
	font-size: 1.125rem;
	font-weight: 600;
	color: var(--color-text-deeper);
	margin: 0 0 1rem 0;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}
.ai-section-icon {
	color: var(--themecolor);
	opacity: 0.9;
}
.ai-code-display {
	font-family: 'Consolas', 'Monaco', monospace;
	font-size: 1.5rem;
	letter-spacing: 2px;
	color: var(--themecolor);
	font-weight: 600;
	padding: 1rem;
	background: rgba(var(--themecolor-rgbstr), 0.05);
	border-radius: var(--card-radius);
	text-align: center;
}
.ai-info-grid {
	display: grid;
	gap: 0.875rem;
}
.ai-info-item {
	display: flex;
	align-items: baseline;
	gap: 0.5rem;
}
.ai-info-label {
	color: var(--color-text-muted);
	font-size: 0.875rem;
	min-width: 80px;
	flex-shrink: 0;
}
.ai-info-value {
	color: var(--color-text);
	flex: 1;
}
.ai-info-value-mono {
	font-family: 'Consolas', 'Monaco', monospace;
}
.ai-info-value-link {
	color: var(--themecolor);
	text-decoration: none;
	font-weight: 500;
	transition: opacity var(--animation-fast) var(--ease-standard);
}
.ai-info-value-link:hover {
	opacity: 0.8;
}
.ai-content-box {
	padding: 1.25rem;
	background: rgba(var(--themecolor-rgbstr), 0.03);
	border: 1px solid rgba(var(--themecolor-rgbstr), 0.1);
	border-radius: var(--card-radius);
	line-height: 1.7;
	color: var(--color-text);
}
.ai-status-badge {
	display: inline-flex;
	align-items: center;
	gap: 0.375rem;
	padding: 0.25rem 0.75rem;
	border-radius: 999px;
	font-size: 0.8125rem;
	font-weight: 500;
}
.ai-status-valid {
	background: rgba(40, 167, 69, 0.1);
	color: #28a745;
	border: 1px solid rgba(40, 167, 69, 0.2);
}
.ai-status-modified {
	background: rgba(255, 193, 7, 0.1);
	color: #ffc107;
	border: 1px solid rgba(255, 193, 7, 0.3);
}
.ai-actions {
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid var(--color-border);
	display: flex;
	gap: 0.75rem;
	flex-wrap: wrap;
}
.ai-help-list {
	padding-left: 1.5rem;
	margin: 0;
	color: var(--color-text-muted);
	line-height: 1.8;
}
.ai-help-list li {
	margin-bottom: 0.5rem;
}
@media (max-width: 768px) {
	.ai-verify-card {
		padding: 1.5rem;
	}
	.ai-verify-title {
		font-size: 1.5rem;
	}
	.ai-code-display {
		font-size: 1.25rem;
		letter-spacing: 1.5px;
	}
}
</style>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<div class="ai-verify-page">
			<div class="ai-verify-card">
				<div class="ai-verify-header">
					<h1 class="ai-verify-title"><?php _e('AI 内容验证与查询', 'argon'); ?></h1>
					<p class="ai-verify-subtitle"><?php _e('通过唯一识别码验证 AI 生成内容的真实性、完整性和时效性', 'argon'); ?></p>
				</div>
				
				<!-- 查询表单 -->
				<div class="ai-query-form">
					<form method="get" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
						<input type="text" 
							name="code" 
							value="<?php echo esc_attr($query_code); ?>" 
							placeholder="<?php _e('输入 8 位识别码', 'argon'); ?>"
							maxlength="8"
							class="ai-query-input"
							required>
						<button type="submit" class="btn btn-primary" style="padding: 0.625rem 1.5rem; white-space: nowrap;">
							<?php _e('查询验证', 'argon'); ?>
						</button>
					</form>
				</div>
				
				<?php if (!empty($error)): ?>
					<!-- 错误信息 -->
					<div class="ai-alert ai-alert-warning">
						<span class="ai-alert-icon">⚠</span>
						<span><?php echo esc_html($error); ?></span>
					</div>
				<?php elseif ($result): ?>
					<!-- 查询结果 -->
					<div class="ai-query-result">
						<!-- 验证状态 -->
						<?php if ($result['is_modified']): ?>
							<div class="ai-alert ai-alert-warning">
								<span class="ai-alert-icon">⚠</span>
								<div>
									<strong><?php _e('内容已变更', 'argon'); ?></strong><br>
									<?php _e('原文内容在 AI 摘要生成后发生了修改，当前摘要可能与最新文章内容不完全一致', 'argon'); ?>
								</div>
							</div>
						<?php else: ?>
							<div class="ai-alert ai-alert-info">
								<span class="ai-alert-icon">✓</span>
								<div>
									<strong><?php _e('验证通过', 'argon'); ?></strong><br>
									<?php _e('AI 生成内容与原文保持一致，未检测到内容变更', 'argon'); ?>
								</div>
							</div>
						<?php endif; ?>
						
						<!-- 识别码信息 -->
						<div class="ai-section">
							<h3 class="ai-section-title">
								<span class="ai-section-icon">🔖</span>
								<?php _e('识别码', 'argon'); ?>
							</h3>
							<div class="ai-code-display"><?php echo esc_html($result['code']); ?></div>
						</div>
						
						<!-- 关联文章 -->
						<div class="ai-section">
							<h3 class="ai-section-title">
								<span class="ai-section-icon">📄</span>
								<?php _e('关联文章', 'argon'); ?>
							</h3>
							<div class="ai-info-grid">
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('文章标题', 'argon'); ?></span>
									<a href="<?php echo esc_url($result['post_url']); ?>" class="ai-info-value ai-info-value-link">
										<?php echo esc_html($result['post_title']); ?>
									</a>
								</div>
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('文章 ID', 'argon'); ?></span>
									<span class="ai-info-value ai-info-value-mono"><?php echo esc_html($result['post_id']); ?></span>
								</div>
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('发布时间', 'argon'); ?></span>
									<span class="ai-info-value"><?php echo esc_html($result['post_date']); ?></span>
								</div>
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('最后修改', 'argon'); ?></span>
									<span class="ai-info-value"><?php echo esc_html($result['post_modified']); ?></span>
								</div>
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('作者', 'argon'); ?></span>
									<span class="ai-info-value"><?php echo esc_html($result['post_author']); ?></span>
								</div>
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('内容状态', 'argon'); ?></span>
									<span class="ai-info-value">
										<?php if ($result['is_modified']): ?>
											<span class="ai-status-badge ai-status-modified"><?php _e('已变更', 'argon'); ?></span>
										<?php else: ?>
											<span class="ai-status-badge ai-status-valid"><?php _e('一致', 'argon'); ?></span>
										<?php endif; ?>
									</span>
								</div>
							</div>
						</div>
						
						<!-- AI 生成内容 -->
						<div class="ai-section">
							<h3 class="ai-section-title">
								<span class="ai-section-icon">📝</span>
								<?php _e('AI 生成内容', 'argon'); ?>
							</h3>
							<div class="ai-content-box">
								<?php echo esc_html($result['summary']); ?>
							</div>
						</div>
						
						<!-- 生成信息 -->
						<div class="ai-section">
							<h3 class="ai-section-title">
								<span class="ai-section-icon">⚙</span>
								<?php _e('生成信息', 'argon'); ?>
							</h3>
							<div class="ai-info-grid">
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('AI 提供商', 'argon'); ?></span>
									<span class="ai-info-value"><?php echo esc_html($result['provider_display']); ?></span>
								</div>
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('使用模型', 'argon'); ?></span>
									<span class="ai-info-value ai-info-value-mono"><?php echo esc_html($result['model']); ?></span>
								</div>
								<div class="ai-info-item">
									<span class="ai-info-label"><?php _e('生成时间', 'argon'); ?></span>
									<span class="ai-info-value"><?php echo esc_html(date('Y-m-d H:i:s', $result['generated_time'])); ?></span>
								</div>
							</div>
						</div>
						
						<!-- 操作按钮 -->
						<div class="ai-actions">
							<a href="<?php echo esc_url($result['post_url']); ?>" class="btn btn-primary">
								<?php _e('查看原文', 'argon'); ?>
							</a>
							<a href="<?php echo esc_url(get_template_directory_uri() . '/ai-summary-query.php'); ?>" class="btn btn-outline-secondary">
								<?php _e('查询其他识别码', 'argon'); ?>
							</a>
						</div>
					</div>
				<?php elseif (empty($query_code)): ?>
					<!-- 使用说明 -->
					<div class="ai-section">
						<h3 class="ai-section-title">
							<span class="ai-section-icon">ℹ</span>
							<?php _e('功能说明', 'argon'); ?>
						</h3>
						<ul class="ai-help-list">
							<li><?php _e('每个 AI 生成内容都有唯一的 8 位识别码，用于内容溯源和验证', 'argon'); ?></li>
							<li><?php _e('识别码显示在文章页面的 AI 摘要底部，点击可直接跳转到本页面', 'argon'); ?></li>
							<li><?php _e('通过识别码可以查询 AI 内容的生成信息、关联文章和验证状态', 'argon'); ?></li>
							<li><?php _e('系统会自动检测原文是否在 AI 内容生成后发生变更', 'argon'); ?></li>
							<li><?php _e('识别码由数字和大写字母组成，不包含易混淆字符（I、O）', 'argon'); ?></li>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</main>
</div>

<?php
get_footer();
?>
