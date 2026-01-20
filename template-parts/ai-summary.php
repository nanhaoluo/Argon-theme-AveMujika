<?php
/**
 * AI 文章摘要模板
 */

if (get_option('argon_ai_summary_enable', 'false') !== 'true') {
	return;
}

$post_id = get_the_ID();
$api_key = get_option('argon_ai_summary_api_key', '');

if (empty($api_key)) {
	return;
}

// 检查排除列表
$exclude_ids = get_option('argon_ai_summary_exclude_ids', '');
if (!empty($exclude_ids)) {
	$exclude_array = array_map('trim', explode(',', $exclude_ids));
	if (in_array($post_id, $exclude_array)) {
		return;
	}
}

$summary = argon_get_ai_summary($post_id);
$is_generating = empty($summary);

// 获取模型信息和识别码
$model = get_post_meta($post_id, '_argon_ai_summary_model', true);
$provider = get_post_meta($post_id, '_argon_ai_summary_provider', true);
$summary_code = get_post_meta($post_id, '_argon_ai_summary_code', true);

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

$provider_display = isset($provider_names[$provider]) ? $provider_names[$provider] : $provider;
$model_display = !empty($model) ? $model : __('未知模型', 'argon');
?>

<div class="ai-summary-wrapper" data-post-id="<?php echo esc_attr($post_id); ?>">
	<div class="ai-summary-header">
		<svg class="ai-summary-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			<path d="M12 2L2 7l10 5 10-5-10-5z"></path>
			<path d="M2 17l10 5 10-5"></path>
			<path d="M2 12l10 5 10-5"></path>
		</svg>
		<span class="ai-summary-title"><?php _e('摘要', 'argon'); ?></span>
	</div>
	<div class="ai-summary-content">
		<?php if ($is_generating): ?>
			<div class="ai-summary-loading">
				<div class="spinner-border spinner-border-sm text-primary" role="status">
					<span class="sr-only">Loading...</span>
				</div>
				<span><?php _e('摘要生成中，请稍候...', 'argon'); ?></span>
			</div>
		<?php else: ?>
			<p><?php echo esc_html($summary); ?></p>
		<?php endif; ?>
	</div>
	<div class="ai-summary-footer">
		<span class="ai-summary-disclaimer"><i class="fa fa-info-circle"></i> <?php _e('内容由AI生成，请注意甄别', 'argon'); ?></span>
		<?php if (!$is_generating): ?>
			<?php if (!empty($model)): ?>
			<span class="ai-summary-model"><?php _e('使用模型', 'argon'); ?>: <?php echo esc_html($model_display); ?></span>
			<?php endif; ?>
			<?php if (!empty($summary_code)): ?>
			<span class="ai-summary-code"><?php _e('识别码', 'argon'); ?>: <a href="<?php echo get_template_directory_uri(); ?>/ai-summary-query.php?code=<?php echo esc_attr($summary_code); ?>"><?php echo esc_html($summary_code); ?></a></span>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<?php if ($is_generating): ?>
<script>
(function() {
	let postId = <?php echo $post_id; ?>;
	let checkCount = 0;
	let maxChecks = 60;
	
	function checkSummary() {
		if (checkCount >= maxChecks) {
			let loadingEl = document.querySelector('.ai-summary-loading');
			if (loadingEl) {
				loadingEl.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> 摘要生成超时，请刷新页面重试</span>';
			}
			return;
		}
		
		checkCount++;
		
		fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: 'action=argon_check_ai_summary&post_id=' + postId + '&nonce=<?php echo wp_create_nonce('argon_check_ai_summary'); ?>'
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data && data.data.summary) {
				let contentDiv = document.querySelector('.ai-summary-content');
				let footerDiv = document.querySelector('.ai-summary-footer');
				if (contentDiv) {
					contentDiv.innerHTML = '<p>' + data.data.summary + '</p>';
				}
				if (footerDiv) {
					let footerHTML = '<span class="ai-summary-disclaimer"><i class="fa fa-info-circle"></i> <?php _e('内容由AI生成，请注意甄别', 'argon'); ?></span>';
					if (data.data.model) {
						footerHTML += '<span class="ai-summary-model"><?php _e('使用模型', 'argon'); ?>: ' + data.data.model + '</span>';
					}
					if (data.data.code) {
						footerHTML += '<span class="ai-summary-code"><?php _e('识别码', 'argon'); ?>: <a href="<?php echo get_template_directory_uri(); ?>/ai-summary-query.php?code=' + data.data.code + '">' + data.data.code + '</a></span>';
					}
					footerDiv.innerHTML = footerHTML;
				}
			} else if (data.success === false) {
				let loadingEl = document.querySelector('.ai-summary-loading');
				if (loadingEl) {
					let errorMsg = (data.data && data.data.message) ? data.data.message : '未知错误';
					loadingEl.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> 摘要生成失败: ' + errorMsg + '</span>';
				}
			} else {
				setTimeout(checkSummary, 5000);
			}
		})
		.catch(error => {
			setTimeout(checkSummary, 5000);
		});
	}
	
	setTimeout(checkSummary, 5000);
})();
</script>
<?php endif; ?>

<style>
.ai-summary-wrapper {
	margin: 0.5rem 0 0.875rem;
	padding: 0.75rem 1rem;
	background: linear-gradient(135deg, rgba(var(--themecolor-rgbstr), 0.03) 0%, rgba(var(--themecolor-rgbstr), 0.01) 100%);
	border-radius: var(--card-radius);
	border: 1px solid rgba(var(--themecolor-rgbstr), 0.12);
	position: relative;
	transition: all var(--animation-fast) var(--ease-standard);
}
.ai-summary-wrapper:hover {
	border-color: rgba(var(--themecolor-rgbstr), 0.2);
	box-shadow: 0 2px 12px rgba(var(--themecolor-rgbstr), 0.08);
}
.ai-summary-header {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 0.5rem;
}
.ai-summary-icon {
	flex-shrink: 0;
	color: var(--themecolor);
	opacity: 0.9;
	width: 16px;
	height: 16px;
}
.ai-summary-title {
	flex: 1;
	font-size: 14px;
	font-weight: 600;
	color: var(--color-text-deeper);
	letter-spacing: 0.3px;
}
.ai-summary-content {
	line-height: 1.65;
	font-size: 14px;
	color: var(--color-text);
	margin-bottom: 0.5rem;
}
.ai-summary-content p {
	margin: 0;
}
.ai-summary-footer {
	display: flex;
	flex-direction: column;
	gap: 3px;
	font-size: 11px;
	color: var(--color-text-muted);
}
.ai-summary-disclaimer {
	opacity: 0.8;
	display: flex;
	align-items: center;
	gap: 4px;
}
.ai-summary-disclaimer i {
	font-size: 12px;
}
.ai-summary-model {
	opacity: 0.6;
	font-family: 'Consolas', 'Monaco', monospace;
}
.ai-summary-code {
	opacity: 0.7;
	font-family: 'Consolas', 'Monaco', monospace;
	letter-spacing: 0.5px;
}
.ai-summary-code a {
	color: inherit;
	text-decoration: none;
	border-bottom: 1px dashed currentColor;
	transition: all var(--animation-fast) var(--ease-standard);
}
.ai-summary-code a:hover {
	opacity: 1;
	border-bottom-style: solid;
}
.ai-summary-loading {
	display: flex;
	align-items: center;
	gap: 8px;
	color: var(--color-text);
	font-size: 13px;
}
html.darkmode .ai-summary-wrapper {
	background: linear-gradient(135deg, rgba(var(--themecolor-rgbstr), 0.08) 0%, rgba(var(--themecolor-rgbstr), 0.03) 100%);
	border-color: rgba(var(--themecolor-rgbstr), 0.2);
}
html.darkmode .ai-summary-wrapper:hover {
	border-color: rgba(var(--themecolor-rgbstr), 0.3);
	box-shadow: 0 2px 12px rgba(var(--themecolor-rgbstr), 0.15);
}
@media (max-width: 768px) {
	.ai-summary-wrapper {
		margin: 0.375rem 0 0.75rem;
		padding: 0.625rem 0.875rem;
	}
	.ai-summary-header {
		margin-bottom: 0.375rem;
		gap: 6px;
	}
	.ai-summary-icon {
		width: 14px;
		height: 14px;
	}
	.ai-summary-title {
		font-size: 13px;
	}
	.ai-summary-content {
		font-size: 13px;
		line-height: 1.6;
		margin-bottom: 0.375rem;
	}
	.ai-summary-footer {
		font-size: 10px;
		gap: 2px;
	}
}
</style>
