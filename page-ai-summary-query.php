<?php
/**
 * Template Name: AI 摘要查询
 * Description: 通过识别码查询 AI 摘要详细信息
 */

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
					'post_author' => get_the_author_meta('display_name', $post->post_author),
					'summary' => get_post_meta($post_id, '_argon_ai_summary', true),
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
			} else {
				$error = __('文章不存在或未发布', 'argon');
			}
		} else {
			$error = __('未找到对应的摘要记录', 'argon');
		}
	}
}
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<article class="post page">
			<div class="card">
				<div class="card-body" style="padding: 2rem;">
					<h1 class="page-title" style="margin-bottom: 1.5rem; font-size: 1.75rem;">
						<i class="fa fa-search" style="margin-right: 0.5rem; color: var(--themecolor);"></i>
						<?php _e('AI 摘要查询', 'argon'); ?>
					</h1>
					
					<!-- 查询表单 -->
					<div class="ai-query-form" style="margin-bottom: 2rem;">
						<form method="get" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
							<input type="text" 
								name="code" 
								value="<?php echo esc_attr($query_code); ?>" 
								placeholder="<?php _e('请输入 8 位识别码', 'argon'); ?>"
								maxlength="8"
								style="flex: 1; min-width: 200px; padding: 0.625rem 1rem; border: 1px solid rgba(var(--themecolor-rgbstr), 0.2); border-radius: var(--card-radius); font-family: 'Consolas', 'Monaco', monospace; font-size: 1rem; letter-spacing: 1px;"
								required>
							<button type="submit" 
								class="btn btn-primary"
								style="padding: 0.625rem 1.5rem; white-space: nowrap;">
								<i class="fa fa-search"></i> <?php _e('查询', 'argon'); ?>
							</button>
						</form>
					</div>
					
					<?php if (!empty($error)): ?>
						<!-- 错误信息 -->
						<div class="alert alert-warning" style="padding: 1rem; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: var(--card-radius); margin-bottom: 1.5rem;">
							<i class="fa fa-exclamation-triangle"></i> <?php echo esc_html($error); ?>
						</div>
					<?php elseif ($result): ?>
						<!-- 查询结果 -->
						<div class="ai-query-result">
							<!-- 识别码信息 -->
							<div class="result-section" style="margin-bottom: 2rem; padding: 1.25rem; background: linear-gradient(135deg, rgba(var(--themecolor-rgbstr), 0.05) 0%, rgba(var(--themecolor-rgbstr), 0.02) 100%); border-radius: var(--card-radius); border: 1px solid rgba(var(--themecolor-rgbstr), 0.15);">
								<h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; color: var(--color-text-deeper);">
									<i class="fa fa-barcode" style="margin-right: 0.5rem; color: var(--themecolor);"></i>
									<?php _e('识别码信息', 'argon'); ?>
								</h3>
								<div style="font-family: 'Consolas', 'Monaco', monospace; font-size: 1.5rem; letter-spacing: 2px; color: var(--themecolor); font-weight: 600;">
									<?php echo esc_html($result['code']); ?>
								</div>
							</div>
							
							<!-- 文章信息 -->
							<div class="result-section" style="margin-bottom: 2rem;">
								<h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; color: var(--color-text-deeper);">
									<i class="fa fa-file-text-o" style="margin-right: 0.5rem; color: var(--themecolor);"></i>
									<?php _e('文章信息', 'argon'); ?>
								</h3>
								<div class="info-grid" style="display: grid; gap: 0.75rem;">
									<div class="info-item">
										<span style="color: var(--color-text-muted); font-size: 0.875rem;"><?php _e('文章标题', 'argon'); ?>:</span>
										<a href="<?php echo esc_url($result['post_url']); ?>" style="color: var(--themecolor); text-decoration: none; font-weight: 500; margin-left: 0.5rem;">
											<?php echo esc_html($result['post_title']); ?>
										</a>
									</div>
									<div class="info-item">
										<span style="color: var(--color-text-muted); font-size: 0.875rem;"><?php _e('文章 ID', 'argon'); ?>:</span>
										<span style="margin-left: 0.5rem; font-family: 'Consolas', 'Monaco', monospace;"><?php echo esc_html($result['post_id']); ?></span>
									</div>
									<div class="info-item">
										<span style="color: var(--color-text-muted); font-size: 0.875rem;"><?php _e('发布时间', 'argon'); ?>:</span>
										<span style="margin-left: 0.5rem;"><?php echo esc_html($result['post_date']); ?></span>
									</div>
									<div class="info-item">
										<span style="color: var(--color-text-muted); font-size: 0.875rem;"><?php _e('作者', 'argon'); ?>:</span>
										<span style="margin-left: 0.5rem;"><?php echo esc_html($result['post_author']); ?></span>
									</div>
								</div>
							</div>
							
							<!-- AI 摘要内容 -->
							<div class="result-section" style="margin-bottom: 2rem;">
								<h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; color: var(--color-text-deeper);">
									<i class="fa fa-align-left" style="margin-right: 0.5rem; color: var(--themecolor);"></i>
									<?php _e('AI 摘要内容', 'argon'); ?>
								</h3>
								<div style="padding: 1rem; background: rgba(var(--themecolor-rgbstr), 0.03); border-radius: var(--card-radius); line-height: 1.7; color: var(--color-text);">
									<?php echo esc_html($result['summary']); ?>
								</div>
							</div>
							
							<!-- 生成信息 -->
							<div class="result-section">
								<h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; color: var(--color-text-deeper);">
									<i class="fa fa-cog" style="margin-right: 0.5rem; color: var(--themecolor);"></i>
									<?php _e('生成信息', 'argon'); ?>
								</h3>
								<div class="info-grid" style="display: grid; gap: 0.75rem;">
									<div class="info-item">
										<span style="color: var(--color-text-muted); font-size: 0.875rem;"><?php _e('AI 提供商', 'argon'); ?>:</span>
										<span style="margin-left: 0.5rem; font-weight: 500;"><?php echo esc_html($result['provider_display']); ?></span>
									</div>
									<div class="info-item">
										<span style="color: var(--color-text-muted); font-size: 0.875rem;"><?php _e('使用模型', 'argon'); ?>:</span>
										<span style="margin-left: 0.5rem; font-family: 'Consolas', 'Monaco', monospace;"><?php echo esc_html($result['model']); ?></span>
									</div>
									<div class="info-item">
										<span style="color: var(--color-text-muted); font-size: 0.875rem;"><?php _e('生成时间', 'argon'); ?>:</span>
										<span style="margin-left: 0.5rem;"><?php echo esc_html(date('Y-m-d H:i:s', $result['generated_time'])); ?></span>
									</div>
								</div>
							</div>
							
							<!-- 操作按钮 -->
							<div style="margin-top: 2rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
								<a href="<?php echo esc_url($result['post_url']); ?>" class="btn btn-primary">
									<i class="fa fa-external-link"></i> <?php _e('查看原文', 'argon'); ?>
								</a>
								<a href="<?php echo esc_url(home_url('/ai-summary-query/')); ?>" class="btn btn-outline-secondary">
									<i class="fa fa-search"></i> <?php _e('查询其他识别码', 'argon'); ?>
								</a>
							</div>
						</div>
					<?php elseif (empty($query_code)): ?>
						<!-- 使用说明 -->
						<div class="ai-query-help" style="color: var(--color-text-muted); line-height: 1.8;">
							<h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; color: var(--color-text-deeper);">
								<i class="fa fa-question-circle" style="margin-right: 0.5rem; color: var(--themecolor);"></i>
								<?php _e('使用说明', 'argon'); ?>
							</h3>
							<ul style="padding-left: 1.5rem; margin: 0;">
								<li style="margin-bottom: 0.5rem;"><?php _e('每篇文章的 AI 摘要都有一个唯一的 8 位识别码', 'argon'); ?></li>
								<li style="margin-bottom: 0.5rem;"><?php _e('识别码显示在文章页面的 AI 摘要底部', 'argon'); ?></li>
								<li style="margin-bottom: 0.5rem;"><?php _e('通过识别码可以查询摘要的详细信息，包括文章信息、生成模型、生成时间等', 'argon'); ?></li>
								<li style="margin-bottom: 0.5rem;"><?php _e('识别码由数字和大写字母组成，不包含易混淆的字符（I、O）', 'argon'); ?></li>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</article>
	</main>
</div>

<?php
get_footer();
?>
