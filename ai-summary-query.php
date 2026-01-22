<?php
/**
 * AI 内容查询页面
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
 * 优先级：CF-Connecting-IP > X-Real-IP > X-Forwarded-For > REMOTE_ADDR
 */
function argon_ai_query_get_client_ip() {
	$ip = '';
	
	// Cloudflare
	if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
	}
	// Nginx proxy_pass 或其他反向代理
	elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
		$ip = $_SERVER['HTTP_X_REAL_IP'];
	}
	// 通过代理转发（取第一个 IP）
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
	}
	// 直连 IP
	else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	
	$ip = trim($ip);
	
	// 验证 IP 格式
	if (!filter_var($ip, FILTER_VALIDATE_IP)) {
		return '';
	}
	
	// 如果是内网 IP 或 CDN IP，尝试从其他头获取
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
		// 内网 IP，尝试从 X-Forwarded-For 获取真实公网 IP
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
			foreach ($ips as $forwarded_ip) {
				if (filter_var($forwarded_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					return $forwarded_ip;
				}
			}
		}
	}
	
	return $ip;
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
$query_type = ''; // 'summary' 或 'spam_detection'

if (!empty($query_code)) {
	if (strlen($query_code) !== 8) {
		$error = __('识别码格式无效', 'argon');
	} else {
		// 尝试从缓存获取
		$cache_key = 'ai_query_result_' . $query_code;
		$cached_result = get_transient($cache_key);
		
		if ($cached_result !== false) {
			$result = $cached_result;
			$query_type = $result['type'];
			$from_cache = true;
		} else {
			// 先查询 AI 摘要
			global $wpdb;
			$post_id = $wpdb->get_var($wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_argon_ai_summary_code' AND meta_value = %s",
				$query_code
			));
			
			if ($post_id) {
				$post = get_post($post_id);
				if ($post && $post->post_status === 'publish') {
					$query_type = 'summary';
					$result = [
						'type' => 'summary',
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
				// 查询垃圾评论检测记录
				$comment_id = $wpdb->get_var($wpdb->prepare(
					"SELECT comment_id FROM {$wpdb->commentmeta} 
					WHERE meta_key = '_argon_spam_detection_code' AND meta_value = %s",
					$query_code
				));
				
				if ($comment_id) {
					$comment = get_comment($comment_id);
					if ($comment) {
						$query_type = 'spam_detection';
						$detection_result = get_comment_meta($comment_id, '_argon_spam_detection_result', true);
						$detection_time = get_comment_meta($comment_id, '_argon_spam_detection_time', true);
						
						$result = [
							'type' => 'spam_detection',
							'comment_id' => $comment_id,
							'comment_author' => $comment->comment_author,
							'comment_email' => $comment->comment_author_email,
							'comment_content' => $comment->comment_content,
							'comment_date' => $comment->comment_date,
							'comment_status' => wp_get_comment_status($comment_id),
							'post_id' => $comment->comment_post_ID,
							'post_title' => get_the_title($comment->comment_post_ID),
							'post_url' => get_permalink($comment->comment_post_ID),
							'comment_url' => get_comment_link($comment),
							'is_spam' => isset($detection_result['is_spam']) ? $detection_result['is_spam'] : false,
							'reason' => isset($detection_result['reason']) ? $detection_result['reason'] : '',
							'action' => isset($detection_result['action']) ? $detection_result['action'] : '',
							'detection_time' => $detection_time,
							'code' => $query_code
						];
						
						// 获取 AI 配置信息
						$result['provider'] = get_option('argon_ai_summary_provider', 'openai');
						$result['model'] = get_option('argon_ai_summary_model', '');
						
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
						$error = __('评论不存在', 'argon');
					}
				} else {
					$error = __('未找到对应的 AI 生成内容记录', 'argon');
				}
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
	<?php elseif ($result && $query_type === 'summary'): ?>
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
	<?php elseif ($result && $query_type === 'spam_detection'): ?>
		<div class="ai-code-display"><?php echo esc_html($result['code']); ?></div>
		
		<div class="ai-alert <?php echo $result['is_spam'] ? 'ai-alert-warning' : 'ai-alert-info'; ?>" style="margin-bottom: 20px;">
			<span class="ai-alert-icon"><?php echo $result['is_spam'] ? '⚠' : '✓'; ?></span>
			<span>
				<strong><?php _e('AI 识别结果', 'argon'); ?>：</strong>
				<?php echo $result['is_spam'] ? __('疑似垃圾评论', 'argon') : __('正常评论', 'argon'); ?>
			</span>
		</div>
		
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('评论信息', 'argon'); ?></h4>
		<div class="ai-info-grid" style="margin-bottom: 24px;">
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('评论 ID', 'argon'); ?></span>
				<span class="ai-info-value ai-info-value-mono"><?php echo esc_html($result['comment_id']); ?></span>
			</div>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('评论者', 'argon'); ?></span>
				<span class="ai-info-value"><?php echo esc_html($result['comment_author']); ?></span>
			</div>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('评论时间', 'argon'); ?></span>
				<span class="ai-info-value"><?php echo esc_html($result['comment_date']); ?></span>
			</div>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('评论状态', 'argon'); ?></span>
				<span class="ai-info-value">
					<?php 
					$status_labels = [
						'approved' => __('已通过', 'argon'),
						'hold' => __('待审核', 'argon'),
						'trash' => __('回收站', 'argon'),
						'spam' => __('垃圾评论', 'argon')
					];
					echo isset($status_labels[$result['comment_status']]) ? esc_html($status_labels[$result['comment_status']]) : esc_html($result['comment_status']);
					?>
				</span>
			</div>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('所属文章', 'argon'); ?></span>
				<a href="<?php echo esc_url($result['post_url']); ?>" class="ai-info-value ai-info-value-link">
					<?php echo esc_html($result['post_title']); ?>
				</a>
			</div>
		</div>
		
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('评论内容', 'argon'); ?></h4>
		<div class="ai-content-box">
			<?php echo esc_html($result['comment_content']); ?>
		</div>
		
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('AI 检测结果', 'argon'); ?></h4>
		<div class="ai-info-grid" style="margin-bottom: 24px;">
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('识别结果', 'argon'); ?></span>
				<span class="ai-info-value">
					<span class="ai-status-badge <?php echo $result['is_spam'] ? 'ai-status-modified' : 'ai-status-valid'; ?>">
						<?php echo $result['is_spam'] ? __('疑似垃圾评论', 'argon') : __('正常评论', 'argon'); ?>
					</span>
				</span>
			</div>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('识别理由', 'argon'); ?></span>
				<span class="ai-info-value"><?php echo esc_html($result['reason']); ?></span>
			</div>
			<?php if (!empty($result['action'])): ?>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('自动处理', 'argon'); ?></span>
				<span class="ai-info-value">
					<?php 
					$action_labels = [
						'trash' => __('已移入回收站', 'argon'),
						'hold' => __('已标记为待审核', 'argon'),
						'mark' => __('仅标记', 'argon')
					];
					echo isset($action_labels[$result['action']]) ? esc_html($action_labels[$result['action']]) : esc_html($result['action']);
					?>
				</span>
			</div>
			<?php endif; ?>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('AI 提供商', 'argon'); ?></span>
				<span class="ai-info-value"><?php echo esc_html($result['provider_display']); ?></span>
			</div>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('使用模型', 'argon'); ?></span>
				<span class="ai-info-value ai-info-value-mono"><?php echo esc_html($result['model']); ?></span>
			</div>
			<div class="ai-info-item">
				<span class="ai-info-label"><?php _e('检测时间', 'argon'); ?></span>
				<span class="ai-info-value"><?php echo esc_html(date('Y-m-d H:i:s', $result['detection_time'])); ?></span>
			</div>
		</div>
		
		<div class="ai-actions">
			<a href="<?php echo esc_url($result['comment_url']); ?>" class="btn btn-primary">
				<?php _e('查看评论', 'argon'); ?>
			</a>
			<a href="<?php echo esc_url($result['post_url']); ?>" class="btn btn-outline-primary">
				<?php _e('查看文章', 'argon'); ?>
			</a>
			<a href="<?php echo home_url('/ai-query'); ?>" class="btn btn-outline-secondary">
				<?php _e('查询其他识别码', 'argon'); ?>
			</a>
		</div>
	<?php elseif (empty($query_code)): ?>
		<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('功能说明', 'argon'); ?></h4>
		<ul class="ai-help-list">
			<li><?php _e('每个 AI 生成内容都有唯一的 8 位识别码，用于内容溯源', 'argon'); ?></li>
			<li><?php _e('支持查询 AI 文章摘要和 AI 垃圾评论检测记录', 'argon'); ?></li>
			<li><?php _e('通过识别码可以查询 AI 内容的生成信息和关联内容', 'argon'); ?></li>
			<li><?php _e('识别码由数字和大写字母组成，不包含易混淆字符（I、O）', 'argon'); ?></li>
		</ul>
	<?php endif; ?>
</article>

<?php
// ==========================================================================
// 管理员查看全部 AI 请求记录
// ==========================================================================
if (current_user_can('manage_options')): 
	global $wpdb;
	
	// 获取所有 AI 摘要记录
	$summaries = $wpdb->get_results("
		SELECT pm.post_id, pm.meta_value as code, pm2.meta_value as time
		FROM {$wpdb->postmeta} pm
		LEFT JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id AND pm2.meta_key = '_argon_ai_summary_time'
		WHERE pm.meta_key = '_argon_ai_summary_code'
		ORDER BY pm2.meta_value DESC
		LIMIT 50
	");
	
	// 获取所有垃圾评论检测记录
	$spam_detections = $wpdb->get_results("
		SELECT cm.comment_id, cm.meta_value as code, cm2.meta_value as time
		FROM {$wpdb->commentmeta} cm
		LEFT JOIN {$wpdb->commentmeta} cm2 ON cm.comment_id = cm2.comment_id AND cm2.meta_key = '_argon_spam_detection_time'
		WHERE cm.meta_key = '_argon_spam_detection_code'
		ORDER BY cm2.meta_value DESC
		LIMIT 50
	");
	
	// 合并并按时间排序
	$all_records = [];
	
	foreach ($summaries as $summary) {
		$post = get_post($summary->post_id);
		if ($post) {
			$all_records[] = [
				'type' => 'summary',
				'code' => $summary->code,
				'time' => $summary->time ? intval($summary->time) : 0,
				'title' => get_the_title($summary->post_id),
				'url' => get_permalink($summary->post_id),
				'id' => $summary->post_id,
				'data' => [
					'post_id' => $summary->post_id,
					'post_title' => get_the_title($summary->post_id),
					'post_url' => get_permalink($summary->post_id),
					'summary' => get_post_meta($summary->post_id, '_argon_ai_summary', true),
					'model' => get_post_meta($summary->post_id, '_argon_ai_summary_model', true),
					'provider' => get_post_meta($summary->post_id, '_argon_ai_summary_provider', true),
					'generated_time' => $summary->time
				]
			];
		}
	}
	
	foreach ($spam_detections as $detection) {
		$comment = get_comment($detection->comment_id);
		if ($comment) {
			$detection_result = get_comment_meta($detection->comment_id, '_argon_spam_detection_result', true);
			$all_records[] = [
				'type' => 'spam_detection',
				'code' => $detection->code,
				'time' => $detection->time ? intval($detection->time) : 0,
				'title' => sprintf(__('评论 #%d - %s', 'argon'), $detection->comment_id, $comment->comment_author),
				'url' => get_comment_link($comment),
				'id' => $detection->comment_id,
				'data' => [
					'comment_id' => $detection->comment_id,
					'comment_author' => $comment->comment_author,
					'comment_content' => $comment->comment_content,
					'comment_date' => $comment->comment_date,
					'post_title' => get_the_title($comment->comment_post_ID),
					'post_url' => get_permalink($comment->comment_post_ID),
					'is_spam' => isset($detection_result['is_spam']) ? $detection_result['is_spam'] : false,
					'reason' => isset($detection_result['reason']) ? $detection_result['reason'] : '',
					'action' => isset($detection_result['action']) ? $detection_result['action'] : '',
					'detection_time' => $detection->time
				]
			];
		}
	}
	
	// 按时间倒序排序
	usort($all_records, function($a, $b) {
		return $b['time'] - $a['time'];
	});
	
	// 只保留前50条
	$all_records = array_slice($all_records, 0, 50);
	
	if (!empty($all_records)):
?>

<article class="post card shadow-sm bg-white border-0 ai-verify-card" style="margin-top: 16px;">
	<h3 class="ai-verify-section-title">
		<i class="fa fa-database"></i><?php _e('AI 请求历史记录', 'argon'); ?>
		<span style="font-size: 13px; font-weight: 400; color: var(--color-text-muted); margin-left: 8px;">
			(<?php _e('仅管理员可见', 'argon'); ?>)
		</span>
	</h3>
	
	<div class="ai-alert ai-alert-info" style="margin-bottom: 20px;">
		<span class="ai-alert-icon">ℹ</span>
		<span><?php _e('显示最近 50 条 AI 请求记录，点击查看详情', 'argon'); ?></span>
	</div>
	
	<style>
	.ai-records-table { width: 100%; border-collapse: collapse; }
	.ai-records-table th { background: rgba(var(--themecolor-rgbstr), 0.05); padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: var(--color-text-deeper); border-bottom: 2px solid var(--color-border-on-foreground); }
	.ai-records-table td { padding: 12px; border-bottom: 1px solid var(--color-border-on-foreground); font-size: 14px; color: var(--color-text); }
	.ai-records-table tbody tr { cursor: pointer; transition: background var(--animation-fast) var(--ease-standard); }
	.ai-records-table tbody tr:hover { background: rgba(var(--themecolor-rgbstr), 0.03); }
	.ai-record-type-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
	.ai-record-type-summary { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
	.ai-record-type-spam { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
	.ai-record-code { font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; color: var(--themecolor); font-weight: 600; }
	.ai-record-time { color: var(--color-text-muted); font-size: 13px; }
	
	.ai-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); overflow: auto; }
	.ai-modal.active { display: flex; align-items: center; justify-content: center; }
	.ai-modal-content { background: var(--color-foreground); border-radius: var(--card-radius); max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
	.ai-modal-header { padding: 20px 24px; border-bottom: 1px solid var(--color-border-on-foreground); display: flex; align-items: center; justify-content: space-between; }
	.ai-modal-title { font-size: 18px; font-weight: 600; color: var(--color-text-deeper); margin: 0; }
	.ai-modal-close { background: none; border: none; font-size: 24px; color: var(--color-text-muted); cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: all var(--animation-fast) var(--ease-standard); }
	.ai-modal-close:hover { background: rgba(var(--themecolor-rgbstr), 0.1); color: var(--themecolor); }
	.ai-modal-body { padding: 24px; }
	
	@media (max-width: 768px) {
		.ai-records-table { display: block; overflow-x: auto; }
		.ai-records-table th, .ai-records-table td { white-space: nowrap; }
		.ai-modal-content { width: 95%; max-height: 95vh; }
		.ai-modal-header { padding: 16px; }
		.ai-modal-body { padding: 16px; }
	}
	</style>
	
	<div style="overflow-x: auto;">
		<table class="ai-records-table">
			<thead>
				<tr>
					<th style="width: 100px;"><?php _e('类型', 'argon'); ?></th>
					<th style="width: 120px;"><?php _e('识别码', 'argon'); ?></th>
					<th><?php _e('标题', 'argon'); ?></th>
					<th style="width: 160px;"><?php _e('时间', 'argon'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($all_records as $record): ?>
				<tr class="ai-record-row" data-record='<?php echo esc_attr(json_encode($record)); ?>'>
					<td>
						<span class="ai-record-type-badge ai-record-type-<?php echo esc_attr($record['type']); ?>">
							<?php echo $record['type'] === 'summary' ? __('文章摘要', 'argon') : __('垃圾评论', 'argon'); ?>
						</span>
					</td>
					<td><span class="ai-record-code"><?php echo esc_html($record['code']); ?></span></td>
					<td><?php echo esc_html($record['title']); ?></td>
					<td class="ai-record-time">
						<?php echo $record['time'] ? date('Y-m-d H:i:s', $record['time']) : __('未知', 'argon'); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</article>

<!-- 详情弹窗 -->
<div id="aiRecordModal" class="ai-modal">
	<div class="ai-modal-content">
		<div class="ai-modal-header">
			<h4 class="ai-modal-title"><?php _e('AI 请求详情', 'argon'); ?></h4>
			<button class="ai-modal-close" onclick="closeAIModal()">&times;</button>
		</div>
		<div class="ai-modal-body" id="aiModalBody">
			<!-- 动态内容 -->
		</div>
	</div>
</div>

<script data-pjax>
(function() {
	const modal = document.getElementById('aiRecordModal');
	const modalBody = document.getElementById('aiModalBody');
	
	window.closeAIModal = function() {
		modal.classList.remove('active');
	};
	
	// 点击背景关闭
	modal.addEventListener('click', function(e) {
		if (e.target === modal) {
			closeAIModal();
		}
	});
	
	// ESC 键关闭
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal.classList.contains('active')) {
			closeAIModal();
		}
	});
	
	// 行点击事件
	document.querySelectorAll('.ai-record-row').forEach(function(row) {
		row.addEventListener('click', function() {
			const record = JSON.parse(this.getAttribute('data-record'));
			showRecordDetail(record);
		});
	});
	
	function showRecordDetail(record) {
		let html = '';
		
		if (record.type === 'summary') {
			html = `
				<div class="ai-code-display">${escapeHtml(record.code)}</div>
				
				<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('关联文章', 'argon'); ?></h4>
				<div class="ai-info-grid" style="margin-bottom: 24px;">
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('文章标题', 'argon'); ?></span>
						<a href="${escapeHtml(record.data.post_url)}" class="ai-info-value ai-info-value-link" target="_blank">
							${escapeHtml(record.data.post_title)}
						</a>
					</div>
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('文章 ID', 'argon'); ?></span>
						<span class="ai-info-value ai-info-value-mono">${escapeHtml(record.data.post_id)}</span>
					</div>
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('生成时间', 'argon'); ?></span>
						<span class="ai-info-value">${record.data.generated_time ? new Date(record.data.generated_time * 1000).toLocaleString('zh-CN') : '<?php _e('未知', 'argon'); ?>'}</span>
					</div>
				</div>
				
				<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('AI 生成内容', 'argon'); ?></h4>
				<div class="ai-content-box">
					${escapeHtml(record.data.summary)}
				</div>
				
				<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('生成信息', 'argon'); ?></h4>
				<div class="ai-info-grid">
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('AI 提供商', 'argon'); ?></span>
						<span class="ai-info-value">${escapeHtml(record.data.provider)}</span>
					</div>
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('使用模型', 'argon'); ?></span>
						<span class="ai-info-value ai-info-value-mono">${escapeHtml(record.data.model)}</span>
					</div>
				</div>
				
				<div class="ai-actions">
					<a href="${escapeHtml(record.data.post_url)}" class="btn btn-primary" target="_blank">
						<?php _e('查看原文', 'argon'); ?>
					</a>
					<a href="<?php echo home_url('/ai-query'); ?>?code=${escapeHtml(record.code)}" class="btn btn-outline-primary">
						<?php _e('公开查询页面', 'argon'); ?>
					</a>
				</div>
			`;
		} else {
			const isSpam = record.data.is_spam;
			html = `
				<div class="ai-code-display">${escapeHtml(record.code)}</div>
				
				<div class="ai-alert ${isSpam ? 'ai-alert-warning' : 'ai-alert-info'}" style="margin-bottom: 20px;">
					<span class="ai-alert-icon">${isSpam ? '⚠' : '✓'}</span>
					<span>
						<strong><?php _e('AI 识别结果', 'argon'); ?>：</strong>
						${isSpam ? '<?php _e('疑似垃圾评论', 'argon'); ?>' : '<?php _e('正常评论', 'argon'); ?>'}
					</span>
				</div>
				
				<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('评论信息', 'argon'); ?></h4>
				<div class="ai-info-grid" style="margin-bottom: 24px;">
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('评论 ID', 'argon'); ?></span>
						<span class="ai-info-value ai-info-value-mono">${escapeHtml(record.data.comment_id)}</span>
					</div>
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('评论者', 'argon'); ?></span>
						<span class="ai-info-value">${escapeHtml(record.data.comment_author)}</span>
					</div>
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('评论时间', 'argon'); ?></span>
						<span class="ai-info-value">${escapeHtml(record.data.comment_date)}</span>
					</div>
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('所属文章', 'argon'); ?></span>
						<a href="${escapeHtml(record.data.post_url)}" class="ai-info-value ai-info-value-link" target="_blank">
							${escapeHtml(record.data.post_title)}
						</a>
					</div>
				</div>
				
				<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('评论内容', 'argon'); ?></h4>
				<div class="ai-content-box">
					${escapeHtml(record.data.comment_content)}
				</div>
				
				<h4 style="font-size: 15px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper);"><?php _e('AI 检测结果', 'argon'); ?></h4>
				<div class="ai-info-grid" style="margin-bottom: 24px;">
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('识别结果', 'argon'); ?></span>
						<span class="ai-info-value">
							<span class="ai-status-badge ${isSpam ? 'ai-status-modified' : 'ai-status-valid'}">
								${isSpam ? '<?php _e('疑似垃圾评论', 'argon'); ?>' : '<?php _e('正常评论', 'argon'); ?>'}
							</span>
						</span>
					</div>
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('识别理由', 'argon'); ?></span>
						<span class="ai-info-value">${escapeHtml(record.data.reason)}</span>
					</div>
					${record.data.action ? `
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('自动处理', 'argon'); ?></span>
						<span class="ai-info-value">${escapeHtml(record.data.action)}</span>
					</div>
					` : ''}
					<div class="ai-info-item">
						<span class="ai-info-label"><?php _e('检测时间', 'argon'); ?></span>
						<span class="ai-info-value">${record.data.detection_time ? new Date(record.data.detection_time * 1000).toLocaleString('zh-CN') : '<?php _e('未知', 'argon'); ?>'}</span>
					</div>
				</div>
				
				<div class="ai-actions">
					<a href="${escapeHtml(record.url)}" class="btn btn-primary" target="_blank">
						<?php _e('查看评论', 'argon'); ?>
					</a>
					<a href="${escapeHtml(record.data.post_url)}" class="btn btn-outline-primary" target="_blank">
						<?php _e('查看文章', 'argon'); ?>
					</a>
					<a href="<?php echo home_url('/ai-query'); ?>?code=${escapeHtml(record.code)}" class="btn btn-outline-secondary">
						<?php _e('公开查询页面', 'argon'); ?>
					</a>
				</div>
			`;
		}
		
		modalBody.innerHTML = html;
		modal.classList.add('active');
	}
	
	function escapeHtml(text) {
		if (!text) return '';
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
})();
</script>

<?php
	endif; // !empty($all_records)
endif; // current_user_can('manage_options')
?>

</main>

<?php
get_footer();
?>
