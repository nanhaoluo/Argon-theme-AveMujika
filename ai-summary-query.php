<?php
/**
 * AI 内容验证与查询页面
 * @package Argon
 */
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) $wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
require_once($wp_load_path);

// ==========================================================================
// 安全防护：IP 访问限制
// ==========================================================================

/**
 * 获取客户端真实 IP
 */
function argon_ai_query_get_client_ip() {
	$ip = '';
	if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
	} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
		$ip = $_SERVER['HTTP_X_REAL_IP'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : '';
}

/**
 * 检查 IP 访问频率限制
 * @return bool|string true 表示允许访问，字符串表示错误信息
 */
function argon_ai_query_check_rate_limit() {
	$client_ip = argon_ai_query_get_client_ip();
	if (empty($client_ip)) {
		return __('无法获取客户端 IP', 'argon');
	}
	
	$transient_key = 'ai_query_lock_' . md5($client_ip);
	$rate_limit_key = 'ai_query_rate_' . md5($client_ip);
	
	// 检查是否有正在进行的查询（单线程限制）
	if (get_transient($transient_key)) {
		return __('请等待上一次查询完成', 'argon');
	}
	
	// 检查访问频率（60秒内最多10次）
	$access_count = get_transient($rate_limit_key);
	if ($access_count === false) {
		set_transient($rate_limit_key, 1, 60);
	} else {
		if ($access_count >= 10) {
			return __('访问过于频繁，请稍后再试', 'argon');
		}
		set_transient($rate_limit_key, $access_count + 1, 60);
	}
	
	// 设置查询锁（3秒超时）
	set_transient($transient_key, 1, 3);
	
	return true;
}

// 执行访问限制检查
$rate_limit_check = argon_ai_query_check_rate_limit();
if ($rate_limit_check !== true) {
	// 访问受限，显示错误页面
	get_header();
	?>
	<div class="page-information-card-container"></div>
	<?php get_sidebar(); ?>
	<div id="primary" class="content-area">
	<style id="ai-verify-page-style">
	@media screen and (min-width: 901px) {
		body.ai-verify-page #leftbar_part, body.ai-verify-page #leftbar { display: none !important; }
	}
	body.ai-verify-page #primary { width: 100% !important; max-width: 1000px !important; margin: 0 auto !important; float: none !important; }
	body.ai-verify-page #content { margin-top: -50vh !important; }
	body.ai-verify-page .site-footer { max-width: 1000px !important; margin: 0 auto !important; width: 100% !important; float: none !important; }
	</style>
	<script data-pjax>
	document.body.classList.add('ai-verify-page');
	</script>
	<main id="main" class="site-main" role="main">
	<article class="post card shadow-sm bg-white border-0" style="padding: 60px 40px; text-align: center; margin-bottom: 16px;">
		<div style="font-size: 48px; color: #f59e0b; margin-bottom: 20px;"><i class="fa fa-exclamation-triangle"></i></div>
		<h2 style="font-size: 20px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 12px;"><?php _e('访问受限', 'argon'); ?></h2>
		<p style="color: var(--color-text-muted); margin-bottom: 24px;"><?php echo esc_html($rate_limit_check); ?></p>
		<a href="<?php echo home_url('/ai-query'); ?>" class="btn btn-primary"><?php _e('返回查询页面', 'argon'); ?></a>
	</article>
	</main>
	<?php
	get_footer();
	exit;
}

// ==========================================================================
// 查询逻辑
// ==========================================================================

$query_code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
if (empty($query_code)) {
	$query_code = get_query_var('code') ? sanitize_text_field(get_query_var('code')) : '';
}
$result = null;
$error = '';
$from_cache = false;

if (!empty($query_code)) {
	if (strlen($query_code) !== 8) {
		$error = __('识别码格式无效', 'argon');
	} else {
		// 尝试从缓存获取
		$cache_key = 'ai_query_result_' . $query_code;
		$cached_result = get_transient($cache_key);
		
		if ($cached_result !== false) {
			$result = $cached_result;
			$from_cache = true;
		} else {
			// 从数据库查询
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
						'model' => get_post_meta($post_id, '_argon_ai_summary_model', true),
						'provider' => get_post_meta($post_id, '_argon_ai_summary_provider', true),
						'generated_time' => get_post_meta($post_id, '_argon_ai_summary_time', true),
						'code' => $query_code
					];
					
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
					
					// 缓存结果（1小时）
					set_transient($cache_key, $result, 3600);
				} else {
					$error = __('文章不存在或未发布', 'argon');
				}
			} else {
				$error = __('未找到对应的 AI 生成内容记录', 'argon');
			}
		}
	}
}

// 释放查询锁
$client_ip = argon_ai_query_get_client_ip();
if (!empty($client_ip)) {
	delete_transient('ai_query_lock_' . md5($client_ip));
}

get_header();
?>

<div class="page-information-card-container"></div>
<?php get_sidebar(); ?>

<div id="primary" class="content-area">

<style id="ai-verify-page-style">
@media screen and (min-width: 901px) {
	body.ai-verify-page #leftbar_part, body.ai-verify-page #leftbar { display: none !important; }
}
@media screen and (max-width: 900px) {
	body.ai-verify-page #leftbar {
		display: block;
		position: fixed;
		background: var(--color-foreground);
		top: 0;
		left: -300px;
		height: 100vh;
		width: 280px;
		padding: 0;
		overflow-y: auto;
		overflow-x: hidden;
		z-index: 1002;
		box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07) !important;
		transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
	}
	html.leftbar-opened body.ai-verify-page #leftbar { left: 0px; }
	body.ai-verify-page .leftbar-mobile-nav { display: flex; flex-direction: column; min-height: 100%; }
	body.ai-verify-page .leftbar-desktop-content { display: none !important; }
	body.ai-verify-page .leftbar-mobile-footer {
		margin-top: auto;
		position: sticky;
		bottom: 0;
		background: var(--color-foreground);
		z-index: 10;
	}
}
body.ai-verify-page #primary { width: 100% !important; max-width: 1000px !important; margin: 0 auto !important; float: none !important; }
body.ai-verify-page #content { margin-top: -50vh !important; }
body.ai-verify-page .site-footer { max-width: 1000px !important; margin: 0 auto !important; width: 100% !important; float: none !important; }

.ai-verify-header-card { text-align: center; padding: 40px 24px 32px; background: transparent !important; box-shadow: none !important; }
.ai-verify-header-icon { width: 64px; height: 64px; margin: 0 auto 20px; background: var(--themecolor-gradient); border-radius: var(--card-radius); display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; box-shadow: 0 4px 12px rgba(var(--themecolor-rgbstr), 0.2); transition: transform var(--animation-fast) var(--ease-standard); }
.ai-verify-header-icon:hover { transform: translateY(-2px); }
.ai-verify-title { font-size: 24px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper); background: none !important; }
.ai-verify-title::before, .ai-verify-title::after { display: none !important; }
.ai-verify-subtitle { font-size: 14px; color: var(--color-text-muted); margin: 0; line-height: 1.6; }
html.darkmode .ai-verify-subtitle { color: #aaa; }

.ai-verify-card { padding: 24px 28px; margin-bottom: 16px; }
.ai-verify-section-title { font-size: 17px; font-weight: 600; margin: 0 0 16px; color: var(--color-text-deeper); display: flex; align-items: center; gap: 8px; background: none !important; }
.ai-verify-section-title::before, .ai-verify-section-title::after { display: none !important; }
.ai-verify-section-title i { color: var(--themecolor); font-size: 16px; }

.ai-query-form { margin-bottom: 20px; }
.ai-query-form form { display: flex; gap: 12px; flex-wrap: wrap; }
.ai-query-input { flex: 1; min-width: 200px; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: var(--card-radius); font-family: 'Consolas', 'Monaco', monospace; font-size: 15px; letter-spacing: 1px; background: var(--color-foreground); color: var(--color-text); transition: all var(--animation-fast) var(--ease-standard); }
.ai-query-input:focus { outline: none; border-color: var(--themecolor); box-shadow: 0 0 0 3px rgba(var(--themecolor-rgbstr), 0.1); }

.ai-alert { padding: 14px 16px; border-radius: var(--card-radius); margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px; line-height: 1.6; }
.ai-alert-warning { background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: var(--color-text); }
.ai-alert-info { background: rgba(var(--themecolor-rgbstr), 0.08); border: 1px solid rgba(var(--themecolor-rgbstr), 0.2); color: var(--color-text); }
.ai-alert-icon { flex-shrink: 0; font-size: 16px; margin-top: 2px; }

.ai-code-display { font-family: 'Consolas', 'Monaco', monospace; font-size: 28px; letter-spacing: 3px; color: var(--themecolor); font-weight: 600; padding: 20px; background: rgba(var(--themecolor-rgbstr), 0.05); border-radius: var(--card-radius); text-align: center; margin-bottom: 20px; }

.ai-info-grid { display: grid; gap: 12px; }
.ai-info-item { display: flex; align-items: baseline; gap: 8px; padding: 8px 0; border-bottom: 1px solid var(--color-border-on-foreground); }
.ai-info-item:last-child { border-bottom: none; }
.ai-info-label { color: var(--color-text-muted); font-size: 14px; min-width: 100px; flex-shrink: 0; }
.ai-info-value { color: var(--color-text); flex: 1; word-break: break-word; }
.ai-info-value-mono { font-family: 'Consolas', 'Monaco', monospace; }
.ai-info-value-link { color: var(--themecolor); text-decoration: none; font-weight: 500; transition: opacity var(--animation-fast) var(--ease-standard); }
.ai-info-value-link:hover { opacity: 0.8; }

.ai-status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 500; }
.ai-status-valid { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.2); }
.ai-status-modified { background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }

.ai-content-box { padding: 16px; background: rgba(var(--themecolor-rgbstr), 0.03); border: 1px solid rgba(var(--themecolor-rgbstr), 0.1); border-radius: var(--card-radius); line-height: 1.8; color: var(--color-text); margin-bottom: 20px; }

.ai-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--color-border-on-foreground); display: flex; gap: 10px; flex-wrap: wrap; }

.ai-help-list { padding-left: 20px; margin: 0; color: var(--color-text-muted); line-height: 1.8; }
.ai-help-list li { margin-bottom: 8px; }

@media (max-width: 768px) {
	.ai-verify-card { padding: 20px; }
	.ai-verify-header-card { padding: 30px 20px 24px; }
	.ai-code-display { font-size: 22px; letter-spacing: 2px; padding: 16px; }
	.ai-info-label { min-width: 80px; font-size: 13px; }
	.ai-query-form form { flex-direction: column; }
	.ai-query-input { width: 100%; }
}
</style>

<script data-pjax>
(function() {
	function cleanupAIVerifyPage() {
		document.body.classList.remove('ai-verify-page');
		var s = document.getElementById('ai-verify-page-style');
		if (s) s.remove();
	}
	
	if (!document.getElementById('ai-verify-page-style')) {
		document.body.classList.remove('ai-verify-page');
	}
	
	document.body.classList.add('ai-verify-page');
	
	if (typeof jQuery !== 'undefined') {
		var $ = jQuery;
		$(document).off('pjax:start.aiverify pjax:popstate.aiverify');
		
		$(document).on('pjax:start.aiverify', function() {
			cleanupAIVerifyPage();
			$(document).off('pjax:start.aiverify pjax:popstate.aiverify');
		});
		
		$(document).on('pjax:popstate.aiverify', function() {
			setTimeout(function() {
				if (!document.getElementById('ai-verify-page-style')) {
					document.body.classList.remove('ai-verify-page');
				}
			}, 50);
		});
	}
})();
</script>

<main id="main" class="site-main" role="main">

<div class="ai-verify-header-card" style="margin-bottom: 16px;">
	<div class="ai-verify-header-icon"><i class="fa fa-shield"></i></div>
	<h1 class="ai-verify-title"><?php _e('AI 内容查询', 'argon'); ?></h1>
</div>

<article class="post card shadow-sm bg-white border-0 ai-verify-card">
	<h3 class="ai-verify-section-title"><i class="fa fa-search"></i><?php _e('查询验证', 'argon'); ?></h3>
	
	<div class="ai-query-form">
		<form method="get" action="">
			<input type="text" 
				name="code" 
				value="<?php echo esc_attr($query_code); ?>" 
				placeholder="<?php _e('输入 8 位识别码', 'argon'); ?>"
				maxlength="8"
				class="ai-query-input"
				required>
			<button type="submit" class="btn btn-primary" style="padding: 10px 24px; white-space: nowrap;">
				<?php _e('查询验证', 'argon'); ?>
			</button>
		</form>
	</div>
	
	<?php if (!empty($error)): ?>
		<div class="ai-alert ai-alert-warning">
			<span class="ai-alert-icon">⚠</span>
			<span><?php echo esc_html($error); ?></span>
		</div>
	<?php elseif ($result): ?>
		<div class="ai-code-display"><?php echo esc_html($result['code']); ?></div>
		
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('关联文章', 'argon'); ?></h4>
		<div class="ai-info-grid" style="margin-bottom: 24px;">
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
		</div>
		
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('AI 生成内容', 'argon'); ?></h4>
		<div class="ai-content-box">
			<?php echo esc_html($result['summary']); ?>
		</div>
		
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('生成信息', 'argon'); ?></h4>
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
		
		<div class="ai-actions">
			<a href="<?php echo esc_url($result['post_url']); ?>" class="btn btn-primary">
				<?php _e('查看原文', 'argon'); ?>
			</a>
			<a href="<?php echo home_url('/ai-query'); ?>" class="btn btn-outline-secondary">
				<?php _e('查询其他识别码', 'argon'); ?>
			</a>
		</div>
	<?php elseif (empty($query_code)): ?>
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('功能说明', 'argon'); ?></h4>
		<ul class="ai-help-list">
			<li><?php _e('每个 AI 生成内容都有唯一的 8 位识别码，用于内容溯源', 'argon'); ?></li>
			<li><?php _e('通过识别码可以查询 AI 内容的生成信息和关联文章', 'argon'); ?></li>
			<li><?php _e('识别码由数字和大写字母组成，不包含易混淆字符（I、O）', 'argon'); ?></li>
		</ul>
	<?php endif; ?>
</article>

<?php
get_footer();
?>
