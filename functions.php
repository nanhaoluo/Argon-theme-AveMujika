<?php
/**
 * Argon Theme Functions
 * 
 * 基于 Argon 主题二次开发
 * 原作者: solstice23 (https://solstice23.top/)
 * 
 * @license GPL-3.0-or-later
 * @link https://www.gnu.org/licenses/gpl-3.0.html
 */

// 禁止移动端浏览器缓存 HTML 页面
function argon_prevent_mobile_cache() {
	if (wp_is_mobile() && !is_admin()) {
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
	}
}
add_action('send_headers', 'argon_prevent_mobile_cache');

if (version_compare( $GLOBALS['wp_version'], '4.4-alpha', '<' )) {
	echo "<div style='background: #5e72e4;color: #fff;font-size: 30px;padding: 50px 30px;position: fixed;width: 100%;left: 0;right: 0;bottom: 0;z-index: 2147483647;'>" . __("Argon 主题不支持 Wordpress 4.4 以下版本，请更新 Wordpress", 'argon') . "</div>";
}
function theme_slug_setup() {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	load_theme_textdomain('argon', get_template_directory() . '/languages');
}
add_action('after_setup_theme','theme_slug_setup');

$argon_version = !(wp_get_theme() -> Template) ? wp_get_theme() -> Version : wp_get_theme(wp_get_theme() -> Template) -> Version;
$GLOBALS['theme_version'] = $argon_version;

// 强制使用本地资源，避免 CDN 加载问题
$GLOBALS['assets_path'] = get_bloginfo('template_url');

// ==================== 强制刷新缓存功能 ====================

/**
 * 检查强制刷新缓存是否启用
 * 启用后 1 小时自动关闭
 */
function argon_is_force_refresh_enabled() {
	$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
	if ($enabled_time == 0) {
		return false;
	}
	// 检查是否超过 1 小时
	if (time() - $enabled_time > 3600) {
		// 自动关闭
		update_option('argon_force_refresh_enabled_time', 0);
		return false;
	}
	return true;
}

/**
 * 获取资源版本号
 * 如果启用了强制刷新，返回当前时间戳
 */
function argon_get_assets_version() {
	if (argon_is_force_refresh_enabled()) {
		// 使用启用时间作为版本号，确保同一小时内版本一致但与之前不同
		$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
		return $GLOBALS['theme_version'] . '.r' . $enabled_time;
	}
	return $GLOBALS['theme_version'];
}

/**
 * 强制刷新时发送禁止缓存的 HTTP 头
 */
function argon_force_refresh_headers() {
	if (!is_admin() && argon_is_force_refresh_enabled()) {
		header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('X-Argon-Force-Refresh: enabled');
	}
}
add_action('send_headers', 'argon_force_refresh_headers');

/**
 * 启用强制刷新缓存
 */
function argon_enable_force_refresh() {
	check_ajax_referer('argon_force_refresh', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error(__('权限不足', 'argon'));
	}
	
	update_option('argon_force_refresh_enabled_time', time());
	wp_send_json_success(array(
		'message' => __('强制刷新已启用，将在 1 小时后自动关闭', 'argon'),
		'expires_at' => time() + 3600
	));
}
add_action('wp_ajax_argon_enable_force_refresh', 'argon_enable_force_refresh');

/**
 * 关闭强制刷新缓存
 */
function argon_disable_force_refresh() {
	check_ajax_referer('argon_force_refresh', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error(__('权限不足', 'argon'));
	}
	
	update_option('argon_force_refresh_enabled_time', 0);
	wp_send_json_success(array(
		'message' => __('强制刷新已关闭', 'argon')
	));
}
add_action('wp_ajax_argon_disable_force_refresh', 'argon_disable_force_refresh');

/**
 * 获取强制刷新状态
 */
function argon_get_force_refresh_status() {
	check_ajax_referer('argon_force_refresh', 'nonce');
	
	$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
	$is_enabled = argon_is_force_refresh_enabled();
	$remaining = 0;
	
	if ($is_enabled && $enabled_time > 0) {
		$remaining = max(0, 3600 - (time() - $enabled_time));
	}
	
	wp_send_json_success(array(
		'enabled' => $is_enabled,
		'remaining' => $remaining
	));
}
add_action('wp_ajax_argon_get_force_refresh_status', 'argon_get_force_refresh_status');

//翻译 Hook
function argon_locate_filter($locate){
	if (substr($locate, 0, 2) == 'zh'){
		if ($locate == 'zh_TW'){
			return $locate;
		}
		return 'zh_CN';
	}
	if (substr($locate, 0, 2) == 'en'){
		return 'en_US';
	}
	if (substr($locate, 0, 2) == 'ru'){
		return 'ru_RU';
	}
	return 'en_US';
}
function argon_get_locate(){
	if (function_exists("determine_locale")){
		return argon_locate_filter(determine_locale());
	}
	$determined_locale = get_locale();
	if (is_admin()){
		$determined_locale = get_user_locale();
	}
	return argon_locate_filter($determined_locale);
}
function theme_locale_hook($locate, $domain){
	if ($domain == 'argon'){
		return argon_locate_filter($locate);
	}
	return $locate;
}
add_filter('theme_locale', 'theme_locale_hook', 10, 2);

//更新主题版本后的兼容
$argon_last_version = get_option("argon_last_version");
if ($argon_last_version == ""){
	$argon_last_version = "0.0";
}
if (version_compare($argon_last_version, $GLOBALS['theme_version'], '<' )){
	if (version_compare($argon_last_version, '0.940', '<')){
		if (get_option('argon_mathjax_v2_enable') == 'true' && get_option('argon_mathjax_enable') != 'true'){
			update_option("argon_math_render", 'mathjax2');
		}
		if (get_option('argon_mathjax_enable') == 'true'){
			update_option("argon_math_render", 'mathjax3');
		}
	}
	if (version_compare($argon_last_version, '0.970', '<')){
		if (get_option('argon_show_author') == 'true'){
			update_option("argon_article_meta", 'time|views|comments|categories|author');
		}
	}
	if (version_compare($argon_last_version, '1.1.0', '<')){
		if (get_option('argon_enable_zoomify') != 'false'){
			update_option("argon_enable_fancybox", 'true');
			update_option("argon_enable_zoomify", 'false');
		}
	}
	if (version_compare($argon_last_version, '1.3.4', '<')){
		switch (get_option('argon_search_post_filter', 'post,page')){
			case 'post,page':
				update_option("argon_enable_search_filters", 'true');
				update_option("argon_search_filters_type", '*post,*page,shuoshuo');
				break;
			case 'post,page,shuoshuo':
				update_option("argon_enable_search_filters", 'true');
				update_option("argon_search_filters_type", '*post,*page,*shuoshuo');
				break;
			case 'post,page,hide_shuoshuo':
				update_option("argon_enable_search_filters", 'true');
				update_option("argon_search_filters_type", '*post,*page');
				break;
			case 'off':
			default:
				update_option("argon_enable_search_filters", 'false');
				break;
		}		
	}
	update_option("argon_last_version", $GLOBALS['theme_version']);
}


//引入邮件模板系统
require_once(get_template_directory() . '/email-templates/base.php');
require_once(get_template_directory() . '/email-templates/comment-notify.php');
require_once(get_template_directory() . '/email-templates/reply-notify.php');

//检测更新
require_once(get_template_directory() . '/theme-update-checker/plugin-update-checker.php');
$argon_update_source = get_option('argon_update_source');
switch ($argon_update_source) {
	case "stop":
		break;
    case "fastgit":
	    $argonThemeUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://api.solstice23.top/argon/info.json?source=fastgit',
			get_template_directory() . '/functions.php',
			'argon'
		);
        break;
    case "cfworker":
	    $argonThemeUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://api.solstice23.top/argon/info.json?source=cfworker',
			get_template_directory() . '/functions.php',
			'argon'
		);
        break;
	case "solstice23top":
		$argonThemeUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://api.solstice23.top/argon/info.json?source=0',
			get_template_directory() . '/functions.php',
			'argon'
		);
		break;
	case "github":
    default:
		$argonThemeUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://raw.githubusercontent.com/solstice23/argon-theme/master/info.json',
			get_template_directory() . '/functions.php',
			'argon'
		);
}

//热更新功能
function argon_hot_reload_init() {
	// 检查是否启用热更新
	if (get_option('argon_enable_hot_reload', 'false') != 'true') {
		return;
	}
	
	// 记录当前主题版本
	$current_version = $GLOBALS['theme_version'];
	$last_known_version = get_option('argon_hot_reload_last_version', '');
	
	// 如果版本发生变化，清理缓存并记录更新
	if (!empty($last_known_version) && $last_known_version !== $current_version) {
		argon_clear_all_caches();
		argon_record_hot_reload_update($last_known_version, $current_version);
	}
	
	// 更新记录的版本
	update_option('argon_hot_reload_last_version', $current_version);
}

// 清理所有缓存
function argon_clear_all_caches() {
	// 清理 WordPress 对象缓存
	if (function_exists('wp_cache_flush')) {
		wp_cache_flush();
	}
	
	// 清理主题更新检查器缓存
	delete_site_transient('update_themes');
	delete_transient('argon_update_info');
	
	// 清理可能存在的其他主题相关缓存
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_argon_%'");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_argon_%'");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_puc_%'");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_puc_%'");
	
	// 触发缓存清理钩子，允许其他插件响应
	do_action('argon_cache_cleared');
}

// 记录热更新
function argon_record_hot_reload_update($old_version, $new_version) {
	$update_history = get_option('argon_hot_reload_history', array());
	
	// 添加新的更新记录
	$update_history[] = array(
		'old_version' => $old_version,
		'new_version' => $new_version,
		'time' => current_time('timestamp'),
		'dismissed' => false
	);
	
	// 只保留最近 10 条记录
	if (count($update_history) > 10) {
		$update_history = array_slice($update_history, -10);
	}
	
	update_option('argon_hot_reload_history', $update_history);
}

// 获取未读的更新通知
function argon_get_pending_update_notices() {
	$update_history = get_option('argon_hot_reload_history', array());
	$pending = array();
	
	foreach ($update_history as $index => $update) {
		if (empty($update['dismissed'])) {
			$pending[$index] = $update;
		}
	}
	
	return $pending;
}

// 标记更新通知为已读
function argon_dismiss_update_notice() {
	check_ajax_referer('argon_dismiss_update_notice', 'nonce');
	
	$index = isset($_POST['index']) ? intval($_POST['index']) : -1;
	$update_history = get_option('argon_hot_reload_history', array());
	
	if ($index === -1) {
		// 标记所有为已读
		foreach ($update_history as &$update) {
			$update['dismissed'] = true;
		}
	} else if (isset($update_history[$index])) {
		$update_history[$index]['dismissed'] = true;
	}
	
	update_option('argon_hot_reload_history', $update_history);
	wp_send_json_success();
}
add_action('wp_ajax_argon_dismiss_update_notice', 'argon_dismiss_update_notice');

// 手动触发缓存清理
function argon_manual_clear_cache() {
	check_ajax_referer('argon_clear_cache', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error(__('权限不足', 'argon'));
	}
	
	argon_clear_all_caches();
	wp_send_json_success(__('缓存已清理', 'argon'));
}
add_action('wp_ajax_argon_clear_cache', 'argon_manual_clear_cache');

// 在后台显示更新通知
function argon_admin_hot_reload_notice() {
	if (get_option('argon_enable_hot_reload', 'false') != 'true') {
		return;
	}
	
	$pending_notices = argon_get_pending_update_notices();
	if (empty($pending_notices)) {
		return;
	}
	
	$latest = end($pending_notices);
	$latest_index = key($pending_notices);
	?>
	<div class="notice notice-success is-dismissible argon-hot-reload-notice" data-index="<?php echo $latest_index; ?>">
		<p>
			<strong><?php _e('Argon 主题已热更新', 'argon'); ?></strong>
			<?php echo sprintf(
				__('主题已从 %s 更新到 %s，所有缓存已自动清理。', 'argon'),
				'<code>' . esc_html($latest['old_version']) . '</code>',
				'<code>' . esc_html($latest['new_version']) . '</code>'
			); ?>
		</p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('.argon-hot-reload-notice').on('click', '.notice-dismiss', function() {
			$.post(ajaxurl, {
				action: 'argon_dismiss_update_notice',
				nonce: '<?php echo wp_create_nonce('argon_dismiss_update_notice'); ?>',
				index: $(this).closest('.argon-hot-reload-notice').data('index')
			});
		});
	});
	</script>
	<?php
}
add_action('admin_notices', 'argon_admin_hot_reload_notice');

// 在前台显示更新通知（可选）
function argon_frontend_hot_reload_notice() {
	if (get_option('argon_enable_hot_reload', 'false') != 'true') {
		return;
	}
	if (get_option('argon_hot_reload_frontend_notice', 'false') != 'true') {
		return;
	}
	if (!is_user_logged_in() || !current_user_can('manage_options')) {
		return;
	}
	
	$pending_notices = argon_get_pending_update_notices();
	if (empty($pending_notices)) {
		return;
	}
	
	$latest = end($pending_notices);
	?>
	<div id="argon-hot-reload-frontend-notice" style="position:fixed;bottom:20px;right:20px;background:#fff;padding:15px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;max-width:350px;">
		<div style="display:flex;align-items:center;margin-bottom:8px;">
			<span style="background:var(--themecolor,#5e72e4);color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;margin-right:8px;"><?php _e('热更新', 'argon'); ?></span>
			<strong><?php _e('主题已更新', 'argon'); ?></strong>
			<span onclick="document.getElementById('argon-hot-reload-frontend-notice').remove();jQuery.post(ajaxurl,{action:'argon_dismiss_update_notice',nonce:'<?php echo wp_create_nonce('argon_dismiss_update_notice'); ?>',index:-1});" style="margin-left:auto;cursor:pointer;opacity:0.5;font-size:18px;">&times;</span>
		</div>
		<p style="margin:0;color:#666;font-size:14px;">
			<?php echo sprintf(
				__('%s → %s', 'argon'),
				'<code>' . esc_html($latest['old_version']) . '</code>',
				'<code>' . esc_html($latest['new_version']) . '</code>'
			); ?>
		</p>
	</div>
	<?php
}
add_action('wp_footer', 'argon_frontend_hot_reload_notice');

// 前端自动刷新脚本
function argon_hot_reload_auto_refresh_script() {
	if (get_option('argon_enable_hot_reload', 'false') != 'true') {
		return;
	}
	if (get_option('argon_hot_reload_auto_refresh', 'false') != 'true') {
		return;
	}
	
	$current_version = $GLOBALS['theme_version'];
	?>
	<script id="argon-hot-reload-checker">
	(function() {
		var currentVersion = '<?php echo esc_js($current_version); ?>';
		var storageKey = 'argon_theme_version';
		var lastRefreshKey = 'argon_last_refresh';
		var refreshCooldown = 5000; // 5秒冷却，防止刷新循环
		
		// 获取存储的版本
		var storedVersion = localStorage.getItem(storageKey);
		var lastRefresh = parseInt(localStorage.getItem(lastRefreshKey) || '0', 10);
		var now = Date.now();
		
		// 如果没有存储版本，先存储当前版本
		if (!storedVersion) {
			localStorage.setItem(storageKey, currentVersion);
			return;
		}
		
		// 版本不同且不在冷却期内，执行刷新
		if (storedVersion !== currentVersion && (now - lastRefresh) > refreshCooldown) {
			// 先更新存储，防止刷新循环
			localStorage.setItem(storageKey, currentVersion);
			localStorage.setItem(lastRefreshKey, now.toString());
			
			// 清理浏览器缓存后刷新
			if ('caches' in window) {
				caches.keys().then(function(names) {
					names.forEach(function(name) {
						caches.delete(name);
					});
				}).then(function() {
					location.reload(true);
				});
			} else {
				location.reload(true);
			}
		} else if (storedVersion !== currentVersion) {
			// 版本不同但在冷却期，只更新存储
			localStorage.setItem(storageKey, currentVersion);
		}
	})();
	</script>
	<?php
}
add_action('wp_head', 'argon_hot_reload_auto_refresh_script', 1);

// 初始化热更新检测
add_action('init', 'argon_hot_reload_init');

// ==================== 前端调试控制台功能 ====================

// 获取已屏蔽的错误列表
function argon_get_muted_errors() {
	return get_option('argon_muted_errors', array());
}

// 屏蔽错误
function argon_mute_error() {
	check_ajax_referer('argon_debug_console', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error(__('权限不足', 'argon'));
	}
	
	$error_hash = sanitize_text_field($_POST['error_hash']);
	$error_message = sanitize_text_field($_POST['error_message']);
	$error_source = sanitize_text_field($_POST['error_source']);
	
	$muted_errors = argon_get_muted_errors();
	$muted_errors[$error_hash] = array(
		'message' => $error_message,
		'source' => $error_source,
		'muted_at' => current_time('timestamp'),
		'muted_by' => wp_get_current_user()->display_name
	);
	
	update_option('argon_muted_errors', $muted_errors);
	wp_send_json_success();
}
add_action('wp_ajax_argon_mute_error', 'argon_mute_error');

// 取消屏蔽错误
function argon_unmute_error() {
	check_ajax_referer('argon_debug_console', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error(__('权限不足', 'argon'));
	}
	
	$error_hash = sanitize_text_field($_POST['error_hash']);
	$muted_errors = argon_get_muted_errors();
	
	if (isset($muted_errors[$error_hash])) {
		unset($muted_errors[$error_hash]);
		update_option('argon_muted_errors', $muted_errors);
	}
	
	wp_send_json_success();
}
add_action('wp_ajax_argon_unmute_error', 'argon_unmute_error');

// 批量删除屏蔽的错误
function argon_clear_muted_errors() {
	check_ajax_referer('argon_debug_console', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error(__('权限不足', 'argon'));
	}
	
	update_option('argon_muted_errors', array());
	wp_send_json_success();
}
add_action('wp_ajax_argon_clear_muted_errors', 'argon_clear_muted_errors');

// 在 footer 中输出调试按钮（放在页脚底部）
function argon_debug_console_footer_button() {
	if (get_option('argon_enable_debug_console', 'false') != 'true') {
		return;
	}
	?>
	<div id="argon-debug-footer-btn" style="text-align:center;padding:12px 0;border-top:1px solid rgba(0,0,0,0.05);margin-top:15px;">
		<button onclick="argonDebug.toggle()" style="background:transparent;border:1px solid var(--themecolor,#5e72e4);color:var(--themecolor,#5e72e4);padding:8px 20px;border-radius:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s;">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
			<?php _e('调试控制台', 'argon'); ?>
			<span id="argon-debug-error-count" style="background:#f5365c;color:#fff;padding:2px 8px;border-radius:10px;font-size:10px;display:none;">0</span>
		</button>
	</div>
	<?php
}

// 输出调试控制台脚本和面板
function argon_debug_console_script() {
	if (get_option('argon_enable_debug_console', 'false') != 'true') {
		return;
	}
	
	$is_admin = current_user_can('manage_options');
	$muted_errors = argon_get_muted_errors();
	$muted_hashes = array_keys($muted_errors);
	
	// 获取版本信息
	$theme_version = $GLOBALS['theme_version'];
	$assets_version = function_exists('argon_get_assets_version') ? argon_get_assets_version() : $theme_version;
	$wp_version = get_bloginfo('version');
	$php_version = phpversion();
	$git_info = function_exists('argon_get_git_info') ? argon_get_git_info() : null;
	$force_refresh_enabled = function_exists('argon_is_force_refresh_enabled') ? argon_is_force_refresh_enabled() : false;
	?>
	<style id="argon-debug-console-style">
	#argon-debug-console{position:fixed;bottom:20px;left:20px;width:520px;max-width:calc(100vw - 40px);height:480px;max-height:70vh;background:#1e1e1e;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.4);z-index:99999;display:none;flex-direction:column;font-family:Consolas,'Courier New',monospace;font-size:12px;overflow:hidden}
	#argon-debug-console.show{display:flex}
	#argon-debug-console-header{background:#2d2d2d;padding:12px 15px;display:flex;align-items:center;justify-content:space-between;color:#fff;font-weight:bold;border-radius:12px 12px 0 0;cursor:move;user-select:none}
	#argon-debug-console-header .title{display:flex;align-items:center;gap:8px;font-size:13px}
	#argon-debug-console-header .actions{display:flex;gap:6px}
	#argon-debug-console-header .actions button{background:#444;border:none;color:#ccc;cursor:pointer;padding:5px 12px;border-radius:4px;font-size:11px}
	#argon-debug-console-header .actions button:hover{background:#555;color:#fff}
	#argon-debug-console-header .actions .close-btn{background:#f5365c;color:#fff}
	#argon-debug-console-info{background:#252526;padding:10px 15px;font-size:11px;color:#888;border-bottom:1px solid #3c3c3c;display:flex;flex-wrap:wrap;gap:6px 12px}
	#argon-debug-console-info .label{color:#666}
	#argon-debug-console-info .value{color:#9cdcfe}
	#argon-debug-console-tabs{background:#252526;padding:0 10px;display:flex;gap:2px;border-bottom:1px solid #3c3c3c}
	#argon-debug-console-tabs button{background:transparent;border:none;color:#888;padding:10px 14px;cursor:pointer;font-size:11px;border-bottom:2px solid transparent}
	#argon-debug-console-tabs button.active{color:#fff;border-bottom-color:var(--themecolor,#5e72e4)}
	#argon-debug-console-tabs .count{background:#3c3c3c;padding:2px 6px;border-radius:8px;margin-left:5px;font-size:10px}
	#argon-debug-console-tabs button.active .count{background:var(--themecolor,#5e72e4)}
	#argon-debug-console-body{flex:1;overflow-y:auto;padding:10px}
	#argon-debug-console-body::-webkit-scrollbar{width:8px}
	#argon-debug-console-body::-webkit-scrollbar-thumb{background:#444;border-radius:4px}
	.debug-log-item{padding:8px 12px;margin-bottom:4px;border-radius:4px;background:#2d2d2d;color:#d4d4d4;word-break:break-all;position:relative;font-size:11px;line-height:1.6;user-select:text;-webkit-user-select:text}
	.debug-log-item.log{border-left:3px solid #6c757d}
	.debug-log-item.warn{border-left:3px solid #ffc107;background:#3d3520;color:#ffc107}
	.debug-log-item.error{border-left:3px solid #f5365c;background:#3d2020;color:#ff6b6b}
	.debug-log-item .time{color:#666;font-size:10px;margin-right:8px}
	.debug-log-item .source{color:#666;font-size:10px;display:block;margin-top:4px}
	.debug-log-item .mute-btn{position:absolute;top:6px;right:6px;background:#f5365c;color:#fff;border:none;padding:3px 10px;border-radius:4px;font-size:10px;cursor:pointer;opacity:0}
	.debug-log-item:hover .mute-btn{opacity:1}
	.debug-log-item.muted{opacity:0.5}
	.debug-log-item.copying{background:#3a5a3a !important}
	.debug-copy-toast{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.8);color:#fff;padding:10px 20px;border-radius:8px;z-index:100001;font-size:12px}
	#argon-error-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#f5365c;color:#fff;padding:12px 20px;border-radius:8px;box-shadow:0 4px 20px rgba(245,54,92,0.4);z-index:100000;display:none;align-items:center;gap:12px;max-width:90vw;animation:argonToastIn .3s ease}
	@keyframes argonToastIn{from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
	#argon-error-toast.show{display:flex}
	#argon-error-toast .close-btn{background:rgba(255,255,255,0.2);border:none;color:#fff;width:24px;height:24px;border-radius:50%;cursor:pointer}
	.debug-resource-item{padding:10px 12px;margin-bottom:6px;border-radius:4px;background:#2d2d2d;font-size:11px}
	.debug-resource-item .res-type{display:inline-block;padding:2px 8px;border-radius:3px;font-size:10px;margin-right:8px;font-weight:bold}
	.debug-resource-item .res-type.css{background:#264de4;color:#fff}
	.debug-resource-item .res-type.js{background:#f7df1e;color:#000}
	.debug-resource-item .res-type.font{background:#ff6b6b;color:#fff}
	.debug-resource-item .res-type.img{background:#4caf50;color:#fff}
	.debug-resource-item .res-type.other{background:#666;color:#fff}
	.debug-resource-item .res-name{color:#9cdcfe;word-break:break-all}
	.debug-resource-item .res-version{color:#4ec9b0;margin-left:8px}
	.debug-resource-item .res-size{color:#888;font-size:10px;margin-left:8px}
	.debug-resource-item .res-status{float:right;font-size:10px;padding:2px 6px;border-radius:3px}
	.debug-resource-item .res-status.cached{background:#3d3520;color:#ffc107}
	.debug-resource-item .res-status.fresh{background:#1e3a1e;color:#4caf50}
	.debug-resource-summary{padding:12px;background:#252526;border-radius:6px;margin-bottom:10px;display:flex;flex-wrap:wrap;gap:15px}
	.debug-resource-summary .summary-item{text-align:center}
	.debug-resource-summary .summary-value{font-size:18px;font-weight:bold;color:#9cdcfe}
	.debug-resource-summary .summary-label{font-size:10px;color:#888}
	.debug-force-refresh-status{padding:10px 12px;margin-bottom:10px;border-radius:4px;font-size:11px}
	.debug-force-refresh-status.enabled{background:#1e3a1e;border:1px solid #4caf50;color:#4caf50}
	.debug-force-refresh-status.disabled{background:#2d2d2d;border:1px solid #444;color:#888}
	@media(max-width:768px){#argon-debug-console{left:10px;right:10px;bottom:10px;width:auto;height:55vh}#argon-debug-console-header{cursor:default}}
	</style>
	
	<div id="argon-debug-console">
		<div id="argon-debug-console-header">
			<div class="title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg><?php _e('调试控制台', 'argon'); ?></div>
			<div class="actions"><button onclick="argonDebug.clear()"><?php _e('清空', 'argon'); ?></button><button class="close-btn" onclick="argonDebug.toggle()">×</button></div>
		</div>
		<div id="argon-debug-console-info">
			<span><span class="label">Theme:</span> <span class="value"><?php echo esc_html($theme_version); ?></span></span>
			<span><span class="label">Assets:</span> <span class="value"><?php echo esc_html($assets_version); ?></span></span>
			<span><span class="label">WP:</span> <span class="value"><?php echo esc_html($wp_version); ?></span></span>
			<span><span class="label">PHP:</span> <span class="value"><?php echo esc_html($php_version); ?></span></span>
			<?php if ($git_info): ?><span><span class="label">Git:</span> <span class="value"><?php echo esc_html($git_info['branch'] . '@' . $git_info['commit']); ?></span></span><?php endif; ?>
			<span><span class="label">UA:</span> <span class="value" id="argon-debug-ua" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span></span>
		</div>
		<div id="argon-debug-console-tabs">
			<button class="active" data-filter="all"><?php _e('全部', 'argon'); ?><span class="count" id="count-all">0</span></button>
			<button data-filter="log"><?php _e('日志', 'argon'); ?><span class="count" id="count-log">0</span></button>
			<button data-filter="warn"><?php _e('警告', 'argon'); ?><span class="count" id="count-warn">0</span></button>
			<button data-filter="error"><?php _e('错误', 'argon'); ?><span class="count" id="count-error">0</span></button>
			<button data-filter="resources"><?php _e('资源', 'argon'); ?><span class="count" id="count-resources">0</span></button>
		</div>
		<div id="argon-debug-console-body"></div>
	</div>
	
	<div id="argon-error-toast">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
		<span class="message"></span>
		<button class="close-btn" onclick="this.parentElement.classList.remove('show')">×</button>
	</div>
	
	<script id="argon-debug-console-script">
	(function() {
		var isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
		var mutedHashes = <?php echo json_encode($muted_hashes); ?>;
		var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
		var nonce = '<?php echo wp_create_nonce('argon_debug_console'); ?>';
		var forceRefreshEnabled = <?php echo $force_refresh_enabled ? 'true' : 'false'; ?>;
		var logs = [], counts = {all:0,log:0,warn:0,error:0,resources:0}, currentFilter = 'all';
		var resourcesData = [];
		var uaEl = document.getElementById('argon-debug-ua');
		if(uaEl){uaEl.textContent=navigator.userAgent;uaEl.title=navigator.userAgent;}
		
		// 收集页面资源信息
		function collectResources() {
			resourcesData = [];
			var entries = performance.getEntriesByType('resource');
			entries.forEach(function(entry) {
				var url = entry.name;
				var type = 'other';
				var name = url.split('/').pop().split('?')[0];
				var version = '';
				
				// 提取版本号
				var verMatch = url.match(/[?&]v(?:er)?=([^&]+)/);
				if (verMatch) version = verMatch[1];
				
				// 判断资源类型
				if (/\.css(\?|$)/i.test(url)) type = 'css';
				else if (/\.js(\?|$)/i.test(url)) type = 'js';
				else if (/\.(woff2?|ttf|eot|otf)(\?|$)/i.test(url)) type = 'font';
				else if (/\.(png|jpe?g|gif|svg|webp|ico)(\?|$)/i.test(url)) type = 'img';
				
				// 只显示主题相关资源或有版本号的资源
				var isThemeResource = url.indexOf('/themes/') !== -1 || url.indexOf('/argon') !== -1;
				if (type === 'css' || type === 'js' || isThemeResource) {
					resourcesData.push({
						type: type,
						name: name,
						url: url,
						version: version,
						size: entry.transferSize || 0,
						duration: Math.round(entry.duration),
						cached: entry.transferSize === 0 && entry.decodedBodySize > 0
					});
				}
			});
			counts.resources = resourcesData.length;
			document.getElementById('count-resources').textContent = counts.resources;
		}
		
		// 渲染资源列表
		function renderResources() {
			var body = document.getElementById('argon-debug-console-body');
			if (!body) return;
			
			var cssCount = 0, jsCount = 0, totalSize = 0, cachedCount = 0;
			resourcesData.forEach(function(r) {
				if (r.type === 'css') cssCount++;
				if (r.type === 'js') jsCount++;
				totalSize += r.size;
				if (r.cached) cachedCount++;
			});
			
			var html = '<div class="debug-force-refresh-status ' + (forceRefreshEnabled ? 'enabled' : 'disabled') + '">';
			html += forceRefreshEnabled ? '✓ <?php _e("强制刷新已启用 - 资源将绕过缓存加载", "argon"); ?>' : '<?php _e("强制刷新未启用 - 资源可能使用浏览器缓存", "argon"); ?>';
			html += '</div>';
			
			html += '<div class="debug-resource-summary">';
			html += '<div class="summary-item"><div class="summary-value">' + resourcesData.length + '</div><div class="summary-label"><?php _e("总资源", "argon"); ?></div></div>';
			html += '<div class="summary-item"><div class="summary-value">' + cssCount + '</div><div class="summary-label">CSS</div></div>';
			html += '<div class="summary-item"><div class="summary-value">' + jsCount + '</div><div class="summary-label">JS</div></div>';
			html += '<div class="summary-item"><div class="summary-value">' + formatSize(totalSize) + '</div><div class="summary-label"><?php _e("传输大小", "argon"); ?></div></div>';
			html += '<div class="summary-item"><div class="summary-value">' + cachedCount + '</div><div class="summary-label"><?php _e("缓存命中", "argon"); ?></div></div>';
			html += '</div>';
			
			resourcesData.forEach(function(r) {
				html += '<div class="debug-resource-item">';
				html += '<span class="res-type ' + r.type + '">' + r.type.toUpperCase() + '</span>';
				html += '<span class="res-name">' + r.name + '</span>';
				if (r.version) html += '<span class="res-version">v' + r.version + '</span>';
				if (r.size > 0) html += '<span class="res-size">' + formatSize(r.size) + '</span>';
				html += '<span class="res-status ' + (r.cached ? 'cached' : 'fresh') + '">' + (r.cached ? '<?php _e("缓存", "argon"); ?>' : '<?php _e("新加载", "argon"); ?>') + '</span>';
				html += '</div>';
			});
			
			body.innerHTML = html;
		}
		
		function formatSize(bytes) {
			if (bytes === 0) return '0 B';
			if (bytes < 1024) return bytes + ' B';
			if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
			return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
		}
		
		function hashError(m,s){var str=(m||'')+'|'+(s||''),h=0;for(var i=0;i<str.length;i++){h=((h<<5)-h)+str.charCodeAt(i);h=h&h;}return 'e'+Math.abs(h).toString(16);}
		function isMuted(h){return mutedHashes.indexOf(h)!==-1;}
		function showErrorToast(m,h){if(isMuted(h))return;var t=document.getElementById('argon-error-toast');if(!t)return;var dm=isAdmin?m:'<?php _e("页面发生错误，请联系管理员", "argon"); ?>';t.querySelector('.message').textContent=dm.length>80?dm.substring(0,80)+'...':dm;t.classList.add('show');setTimeout(function(){t.classList.remove('show');},5000);}
		function updateCounts(){document.getElementById('count-all').textContent=counts.all;document.getElementById('count-log').textContent=counts.log;document.getElementById('count-warn').textContent=counts.warn;document.getElementById('count-error').textContent=counts.error;var b=document.getElementById('argon-debug-error-count');if(b){b.textContent=counts.error;b.style.display=counts.error>0?'inline':'none';}}
		function addLog(type,args,source){var msg=Array.prototype.slice.call(args).map(function(a){if(typeof a==='object'){try{return JSON.stringify(a,null,2);}catch(e){return String(a);}}return String(a);}).join(' ');var hash=hashError(msg,source),time=new Date().toLocaleTimeString();logs.push({type:type,msg:msg,source:source,time:time,hash:hash});counts.all++;counts[type]=(counts[type]||0)+1;updateCounts();if(type==='error')showErrorToast(msg,hash);if(currentFilter!=='resources')renderLogs();}
		function renderLogs(){var body=document.getElementById('argon-debug-console-body');if(!body)return;var filtered=currentFilter==='all'?logs:logs.filter(function(l){return l.type===currentFilter;});body.innerHTML=filtered.map(function(log,idx){var muted=isMuted(log.hash);var muteBtn=isAdmin&&log.type==='error'?'<button class="mute-btn" onclick="event.stopPropagation();argonDebug.mute(\''+log.hash+'\',\''+encodeURIComponent(log.msg.substring(0,200))+'\',\''+encodeURIComponent(log.source||'')+'\')">'+(muted?'<?php _e("已屏蔽", "argon"); ?>':'<?php _e("屏蔽", "argon"); ?>')+'</button>':'';return '<div class="debug-log-item '+log.type+(muted?' muted':'')+'" data-log-idx="'+idx+'">'+muteBtn+'<span class="time">['+log.time+']</span> '+log.msg.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')+(log.source?'<span class="source">'+log.source+'</span>':'')+'</div>';}).join('');body.scrollTop=body.scrollHeight;}
		// 长按复制功能
		var longPressTimer=null,longPressTarget=null;
		document.addEventListener('touchstart',function(e){var item=e.target.closest('.debug-log-item');if(item){longPressTarget=item;longPressTimer=setTimeout(function(){var idx=parseInt(item.dataset.logIdx);if(!isNaN(idx)&&logs[idx]){var text='['+logs[idx].time+'] '+logs[idx].msg+(logs[idx].source?' ('+logs[idx].source+')':'');copyToClipboard(text);item.classList.add('copying');setTimeout(function(){item.classList.remove('copying');},300);}},500);}},{passive:true});
		document.addEventListener('touchend',function(){if(longPressTimer){clearTimeout(longPressTimer);longPressTimer=null;}},{passive:true});
		document.addEventListener('touchmove',function(){if(longPressTimer){clearTimeout(longPressTimer);longPressTimer=null;}},{passive:true});
		function copyToClipboard(text){if(navigator.clipboard){navigator.clipboard.writeText(text).then(function(){showCopyToast('<?php _e("已复制", "argon"); ?>');});}else{var ta=document.createElement('textarea');ta.value=text;ta.style.position='fixed';ta.style.opacity='0';document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);showCopyToast('<?php _e("已复制", "argon"); ?>');}}
		function showCopyToast(msg){var t=document.createElement('div');t.className='debug-copy-toast';t.textContent=msg;document.body.appendChild(t);setTimeout(function(){t.remove();},1000);}
		var oc={log:console.log,warn:console.warn,error:console.error,info:console.info};
		console.log=function(){addLog('log',arguments);};
		console.info=function(){addLog('log',arguments);};
		console.warn=function(){addLog('warn',arguments);};
		console.error=function(){addLog('error',arguments);};
		window.addEventListener('error',function(e){var src=e.filename?e.filename.split('/').pop()+':'+e.lineno:'';addLog('error',[e.message],src);});
		window.addEventListener('unhandledrejection',function(e){addLog('error',['Promise: '+(e.reason?(e.reason.message||e.reason):'Unknown')]);});
		var panel=document.getElementById('argon-debug-console'),header=document.getElementById('argon-debug-console-header');
		if(window.innerWidth>768&&header&&panel){var isDragging=false,startX,startY,startLeft,startTop;header.addEventListener('mousedown',function(e){if(e.target.tagName==='BUTTON')return;isDragging=true;startX=e.clientX;startY=e.clientY;var rect=panel.getBoundingClientRect();startLeft=rect.left;startTop=rect.top;panel.style.transition='none';});document.addEventListener('mousemove',function(e){if(!isDragging)return;var newLeft=Math.max(0,Math.min(startLeft+e.clientX-startX,window.innerWidth-panel.offsetWidth));var newTop=Math.max(0,Math.min(startTop+e.clientY-startY,window.innerHeight-panel.offsetHeight));panel.style.left=newLeft+'px';panel.style.top=newTop+'px';panel.style.bottom='auto';panel.style.right='auto';});document.addEventListener('mouseup',function(){isDragging=false;panel.style.transition='';});}
		document.querySelectorAll('#argon-debug-console-tabs button').forEach(function(btn){btn.addEventListener('click',function(){document.querySelectorAll('#argon-debug-console-tabs button').forEach(function(b){b.classList.remove('active');});this.classList.add('active');currentFilter=this.dataset.filter;if(currentFilter==='resources'){collectResources();renderResources();}else{renderLogs();}});});
		window.argonDebug={toggle:function(){panel.classList.toggle('show');if(panel.classList.contains('show')&&currentFilter==='resources'){collectResources();renderResources();}},clear:function(){logs=[];counts={all:0,log:0,warn:0,error:0,resources:counts.resources};updateCounts();renderLogs();},mute:function(hash,msg,source){if(!isAdmin)return;var xhr=new XMLHttpRequest();xhr.open('POST',ajaxUrl);xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');xhr.onload=function(){if(xhr.status===200){mutedHashes.push(hash);renderLogs();}};xhr.send('action=argon_mute_error&nonce='+nonce+'&error_hash='+hash+'&error_message='+msg+'&error_source='+source);}};
		
		// 页面加载完成后收集资源
		window.addEventListener('load', function() {
			setTimeout(collectResources, 100);
		});
		
		console.log('<?php _e("调试控制台已启动", "argon"); ?> - Theme v<?php echo esc_js($theme_version); ?>, Assets v<?php echo esc_js($assets_version); ?>');
	})();
	</script>
	<?php
}
add_action('wp_footer', 'argon_debug_console_script', 999);

//初次使用时发送安装量统计信息 (数据仅用于统计安装量)
function post_analytics_info(){
	if(function_exists('file_get_contents')){
		$contexts = stream_context_create(
			array(
				'http' => array(
					'method'=>"GET",
					'header'=>"User-Agent: ArgonTheme\r\n"
				)
			)
		);
		$result = @file_get_contents('https://api.solstice23.top/argon_analytics/index.php?domain=' . urlencode($_SERVER['HTTP_HOST']) . '&version='. urlencode($GLOBALS['theme_version']), false, $contexts);
		update_option('argon_has_inited', 'true');
		return $result;
	}else{
		update_option('argon_has_inited', 'true');
	}
}
if (get_option('argon_has_inited') != 'true'){
	post_analytics_info();
}
//时区修正
if (get_option('argon_enable_timezone_fix') == 'true'){
	date_default_timezone_set('UTC');
}
//注册小工具
function argon_widgets_init() {
	register_sidebar(
		array(
			'name'          => __('左侧栏小工具', 'argon'),
			'id'            => 'leftbar-tools',
			'description'   => __( '左侧栏小工具 (如果设置会在侧栏增加一个 Tab)', 'argon'),
			'before_widget' => '<div id="%1$s" class="widget %2$s card bg-white border-0">',
			'after_widget'  => '</div>',
			'before_title'  => '<h6 class="font-weight-bold text-black">',
			'after_title'   => '</h6>',
		)
	);
	register_sidebar(
		array(
			'name'          => __('右侧栏小工具', 'argon'),
			'id'            => 'rightbar-tools',
			'description'   => __( '右侧栏小工具 (在 "Argon 主题选项" 中选择 "三栏布局" 才会显示)', 'argon'),
			'before_widget' => '<div id="%1$s" class="widget %2$s card shadow-sm bg-white border-0">',
			'after_widget'  => '</div>',
			'before_title'  => '<h6 class="font-weight-bold text-black">',
			'after_title'   => '</h6>',
		)
	);
	register_sidebar(
		array(
			'name'          => __('站点概览额外内容', 'argon'),
			'id'            => 'leftbar-siteinfo-extra-tools',
			'description'   => __( '站点概览额外内容', 'argon'),
			'before_widget' => '<div id="%1$s" class="widget %2$s card bg-white border-0">',
			'after_widget'  => '</div>',
			'before_title'  => '<h6 class="font-weight-bold text-black">',
			'after_title'   => '</h6>',
		)
	);
}
add_action('widgets_init', 'argon_widgets_init');
//注册新后台主题配色方案
function argon_add_admin_color(){
	wp_admin_css_color(
		'argon',
		'Argon',
		get_bloginfo('template_directory') . "/admin.css",
		array("#5e72e4", "#324cdc", "#e8ebfb"),
		array('base' => '#525f7f', 'focus' => '#5e72e4', 'current' => '#fff')
	);
}
add_action('admin_init', 'argon_add_admin_color');
function argon_admin_themecolor_css(){
	$themecolor = get_option("argon_theme_color", "#5e72e4");
	$RGB = hexstr2rgb($themecolor);
	$HSL = rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']);
	echo "
		<style id='themecolor_css'>
			:root{
				--themecolor: {$themecolor} ;
				--themecolor-R: {$RGB['R']} ;
				--themecolor-G: {$RGB['G']} ;
				--themecolor-B: {$RGB['B']} ;
				--themecolor-H: {$HSL['H']} ;
				--themecolor-S: {$HSL['S']} ;
				--themecolor-L: {$HSL['L']} ;
			}
		</style>
	";
	if (get_option("argon_enable_immersion_color", "false") == "true"){
		echo "<script> document.documentElement.classList.add('immersion-color'); </script>";
	}
}
add_filter('admin_head', 'argon_admin_themecolor_css');
function array_remove(&$arr, $item){
	$pos = array_search($item, $arr);
	if ($pos !== false){
		array_splice($arr, $pos, 1);
	}
}
//数字格式化
function format_number_in_kilos($number) {
	if ($number < 1000){
		return $number;
	}
	if (1000 <= $number && $number < 1000000){
		if (1000 <= $number && $number < 10000){
			return round($number / 1000, 1) . "K";
		}else{
			return round($number / 1000, 0) . "K";
		}
	}
	if (1000000 <= $number && $number <= 10000000){
		return round($number / 1000000, 1) . "M";
	}else{
		return round($number / 1000000, 0) . "M";
	}
}
//表情包
require_once(get_template_directory() . '/emotions.php');
//文章特色图片
function argon_get_first_image_of_article(){
	global $post;
	if (post_password_required()){
		return false;
	}
	$post_content_full = apply_filters('the_content', preg_replace( '<!--more(.*?)-->', '', $post -> post_content));
	preg_match('/<img(.*?)(src|data-original)=[\"\']((http:|https:)?\/\/(.*?))[\"\'](.*?)\/?>/', $post_content_full, $match);
	if (isset($match[3])){
		return $match[3];
	}
	return false;
}
function argon_has_post_thumbnail($postID = 0){
	if ($postID == 0){
		global $post;
		$postID = $post -> ID;
	}
	if (has_post_thumbnail()){
		return true;
	}
	$argon_first_image_as_thumbnail = get_post_meta($postID, 'argon_first_image_as_thumbnail', true);
	if ($argon_first_image_as_thumbnail == ""){
		$argon_first_image_as_thumbnail = "default";
	}
	if ($argon_first_image_as_thumbnail == "true" || ($argon_first_image_as_thumbnail == "default" && get_option("argon_first_image_as_thumbnail_by_default", "false") == "true")){
		if (argon_get_first_image_of_article() != false){
			return true;
		}
	}
	return false;
}
function argon_get_post_thumbnail($postID = 0){
	if ($postID == 0){
		global $post;
		$postID = $post -> ID;
	}
	if (has_post_thumbnail()){
		return apply_filters("argon_post_thumbnail", wp_get_attachment_image_src(get_post_thumbnail_id($postID), "full")[0]);
	}
	return apply_filters("argon_post_thumbnail", argon_get_first_image_of_article());
}
//文末附加内容
function get_additional_content_after_post(){
	global $post;
	$postID = $post -> ID;
	$res = get_post_meta($post -> ID, 'argon_after_post', true);
	if ($res == "--none--"){
		return "";
	}
	if ($res == ""){
		$res = get_option("argon_additional_content_after_post");
	}
	$res = str_replace("\n", "</br>", $res);
	$res = str_replace("%url%", get_permalink($postID), $res);
	$res = str_replace("%link%", '<a href="' . get_permalink($postID) . '" target="_blank">' . get_permalink($postID) . '</a>', $res);
	$res = str_replace("%title%", get_the_title(), $res);
	$res = str_replace("%author%", get_the_author(), $res);
	return $res;
}
//输出分页页码
function get_argon_formatted_paginate_links($maxPageNumbers, $extraClasses = ''){
	$args = array(
		'prev_text' => '',
		'next_text' => '',
		'before_page_number' => '',
		'after_page_number' => '',
		'show_all' => True
	);
	$res = paginate_links($args);
	//单引号转双引号 & 去除上一页和下一页按钮
	$res = preg_replace(
		'/\'/',
		'"',
		$res
	);
	$res = preg_replace(
		'/<a class="prev page-numbers" href="(.*?)">(.*?)<\/a>/',
		'',
		$res
	);
	$res = preg_replace(
		'/<a class="next page-numbers" href="(.*?)">(.*?)<\/a>/',
		'',
		$res
	);
	//寻找所有页码标签
	preg_match_all('/<(.*?)>(.*?)<\/(.*?)>/' , $res , $pages);
	$total = count($pages[0]);
	$current = 0;
	$urls = array();
	for ($i = 0; $i < $total; $i++){
		if (preg_match('/<span(.*?)>(.*?)<\/span>/' , $pages[0][$i])){
			$current = $i + 1;
		}else{
			preg_match('/<a(.*?)href="(.*?)">(.*?)<\/a>/' , $pages[0][$i] , $tmp);
			$urls[$i + 1] = $tmp[2];
		}
	}

	if ($total == 0){
		return "";
	}

	//计算页码起始
	$from = max($current - ($maxPageNumbers - 1) / 2 , 1);
	$to = min($current + $maxPageNumbers - ( $current - $from + 1 ) , $total);
	if ($to - $from + 1 < $maxPageNumbers){
		$to = min($current + ($maxPageNumbers - 1) / 2 , $total);
		$from = max($current - ( $maxPageNumbers - ( $to - $current + 1 ) ) , 1);
	}
	//生成新页码
	$html = "";
	if ($from > 1){
		$html .= '<li class="page-item"><a aria-label="First Page" class="page-link" href="' . $urls[1] . '"><i class="fa fa-angle-double-left" aria-hidden="true"></i></a></li>';
	}
	if ($current > 1){
		$html .= '<li class="page-item"><a aria-label="Previous Page" class="page-link" href="' . $urls[$current - 1] . '"><i class="fa fa-angle-left" aria-hidden="true"></i></a></li>';
	}
	for ($i = $from; $i <= $to; $i++){
		if ($current == $i){
			$html .= '<li class="page-item active"><span class="page-link" style="cursor: default;">' . $i . '</span></li>';
		}else{
			$html .= '<li class="page-item"><a class="page-link" href="' . $urls[$i] . '">' . $i . '</a></li>';
		}
	}
	if ($current < $total){
		$html .= '<li class="page-item"><a aria-label="Next Page" class="page-link" href="' . $urls[$current + 1] . '"><i class="fa fa-angle-right" aria-hidden="true"></i></a></li>';
	}
	if ($to < $total){
		$html .= '<li class="page-item"><a aria-label="Last Page" class="page-link" href="' . $urls[$total] . '"><i class="fa fa-angle-double-right" aria-hidden="true"></i></a></li>';
	}
	return '<nav><ul class="pagination' . $extraClasses . '">' . $html . '</ul></nav>';
}
function get_argon_formatted_paginate_links_for_all_platforms(){
	return get_argon_formatted_paginate_links(7) . get_argon_formatted_paginate_links(5, " pagination-mobile");
}
//访问者 Token & Session
function get_random_token(){
	return md5(uniqid(microtime(true), true));
}
function set_user_token_cookie(){
    if (!isset($_COOKIE["argon_user_token"]) || strlen($_COOKIE["argon_user_token"]) != 32){
        $newToken = get_random_token();
        setcookie("argon_user_token", $newToken, array(
            'expires' => time() + 10 * 365 * 24 * 60 * 60,
            'path' => '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax'
        ));
        $_COOKIE["argon_user_token"] = $newToken;
    }
}
function session_init(){
    set_user_token_cookie();
    if (!session_id()){
        if (function_exists('session_set_cookie_params')){
            session_set_cookie_params(array(
                'lifetime' => 0,
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax'
            ));
        }
        session_start();
    }
}
session_init();
//add_action('init', 'session_init');
//页面 Description Meta
function get_seo_description(){
	global $post;
	if (is_single() || is_page()){
		if (get_the_excerpt() != ""){
			return preg_replace('/ \[&hellip;]$/', '&hellip;', get_the_excerpt());
		}
		if (!post_password_required()){
			return htmlspecialchars(mb_substr(str_replace("\n", '', strip_tags($post -> post_content)), 0, 50)) . "...";
		}else{
			return __("这是一个加密页面，需要密码来查看", 'argon');
		}
	}else{
		return get_option('argon_seo_description');
	}
}
//页面 Keywords
function get_seo_keywords(){
	if (is_single()){
		global $post;
		$tags = get_the_tags('', ',', '', $post -> ID);
		if ($tags != null){
			$res = "";
			foreach ($tags as $tag){
				if ($res != ""){
					$res .= ",";
				}
				$res .= $tag -> name;
			}
			return $res;
		}
	}
	if (is_category()){
		return single_cat_title('', false);
	}
	if (is_tag()){
		return single_tag_title('', false);
	}
	if (is_author()){
		return get_the_author();
	}
	if (is_post_type_archive()){
		return post_type_archive_title('', false);
	}
	if (is_tax()){
		return single_term_title('', false);
	}
	return get_option('argon_seo_keywords');
}
//页面分享预览图
function get_og_image(){
	global $post;
	$postID = $post -> ID;
	$argon_first_image_as_thumbnail = get_post_meta($postID, 'argon_first_image_as_thumbnail', 'true');
	if (has_post_thumbnail() || $argon_first_image_as_thumbnail == 'true'){
		return argon_get_post_thumbnail($postID);
	}
	return '';
}
//页面浏览量
function get_post_views($post_id){
	$count_key = 'views';
	$count = get_post_meta($post_id, $count_key, true);
	if ($count==''){
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '0');
		$count = '0';
	}
	return number_format_i18n($count);
}
function set_post_views(){
	if (!is_single() && !is_page()) {
		return;
	}
	if (!isset($post_id)){
		global $post;
		$post_id = $post -> ID;
	}
	if (post_password_required($post_id)){
		return;
	}
	if (isset($_GET['preview'])){
		if ($_GET['preview'] == 'true'){
			if (current_user_can('publish_posts')){
				return;
			}
		}
	}
	$noPostView = 'false';
	if (isset($_POST['no_post_view'])){
		$noPostView = $_POST['no_post_view'];
	}
	if ($noPostView == 'true'){
		return;
	}
	global $post;
	if (!isset($post -> ID)){
		return;
	}
	$post_id = $post -> ID;
	$count_key = 'views';
	$count = get_post_meta($post_id, $count_key, true);
	if (is_single() || is_page()) {
		if ($count==''){
			delete_post_meta($post_id, $count_key);
			add_post_meta($post_id, $count_key, '0');
		} else {
			update_post_meta($post_id, $count_key, $count + 1);
		}
	}
}
add_action('get_header', 'set_post_views');
//字数和预计阅读时间
function get_article_words($str){
	preg_match_all('/<pre(.*?)>[\S\s]*?<code(.*?)>([\S\s]*?)<\/code>[\S\s]*?<\/pre>/im', $str, $codeSegments, PREG_PATTERN_ORDER);
	$codeSegments = $codeSegments[3];
	$codeTotal = 0;
	foreach ($codeSegments as $codeSegment){
		$codeLines = preg_split('/\r\n|\n|\r/', $codeSegment);
		foreach ($codeLines as $line){
			if (strlen(trim($line)) > 0){
				$codeTotal++;
			}
		}
	}

	$str = preg_replace(
		'/<code(.*?)>[\S\s]*?<\/code>/im',
		'',
		$str
	);
	$str = preg_replace(
		'/<pre(.*?)>[\S\s]*?<\/pre>/im',
		'',
		$str
	);
	$str = preg_replace(
		'/<style(.*?)>[\S\s]*?<\/style>/im',
		'',
		$str
	);
	$str = preg_replace(
		'/<script(.*?)>[\S\s]*?<\/script>/im',
		'',
		$str
	);
	$str =  preg_replace('/<[^>]+?>/', ' ', $str);
	$str = html_entity_decode(strip_tags($str));
	preg_match_all('/[\x{4e00}-\x{9fa5}]/u' , $str , $cnRes);
	$cnTotal = count($cnRes[0]);
	$enRes = preg_replace('/[\x{4e00}-\x{9fa5}]/u', '', $str);
	preg_match_all('/[a-zA-Z0-9_\x{0392}-\x{03c9}\x{0400}-\x{04FF}]+|[\x{4E00}-\x{9FFF}\x{3400}-\x{4dbf}\x{f900}-\x{faff}\x{3040}-\x{309f}\x{ac00}-\x{d7af}\x{0400}-\x{04FF}]+|[\x{00E4}\x{00C4}\x{00E5}\x{00C5}\x{00F6}\x{00D6}]+|\w+/u' , $str , $enRes);
	$enTotal = count($enRes[0]);
	return array(
		'cn' => $cnTotal,
		'en' => $enTotal,
		'code' => $codeTotal,
	);
}
function get_article_words_total($str){
	$res = get_article_words($str);
	return $res['cn'] + $res['en'] + $res['code'];
}
function get_reading_time($len){
	$speedcn = get_option('argon_reading_speed', 300);
	$speeden = get_option('argon_reading_speed_en', 160);
	$speedcode = get_option('argon_reading_speed_code', 20);
	$reading_time = $len['cn'] / $speedcn + $len['en'] / $speeden + $len['code'] / $speedcode;
	if ($reading_time < 0.3){
		return __("几秒读完", 'argon');
	}
	if ($reading_time < 1){
		return __("1 分钟内", 'argon');
	}
	if ($reading_time < 60){
		return ceil($reading_time) . " " . __("分钟", 'argon');
	}
	return round($reading_time / 60 , 1) . " " . __("小时", 'argon');
}
//当前文章是否可以生成目录
function have_catalog(){
	if (!is_single() && !is_page()){
		return false;
	}
	if (post_password_required()){
		return false;
	}
	if (is_page() && is_page_template('timeline.php')){
		return true;
	}
	$content = get_post(get_the_ID()) -> post_content;
	// 检查 HTML 标题标签
	if (preg_match('/<h[1-6](.*?)>/i', $content)){
		return true;
	}
	// 检查 Gutenberg 标题块
	if (preg_match('/<!-- wp:heading/', $content)){
		return true;
	}
	// 检查 Markdown 格式标题（如果启用了 Markdown）
	if (preg_match('/^#{1,6}\s+/m', $content)){
		return true;
	}
	return false;
}
//获取文章 Meta
function get_article_meta($type){
	if ($type == 'sticky'){
		return '<div class="post-meta-detail post-meta-detail-stickey">
					<i class="fa fa-thumb-tack" aria-hidden="true"></i>
					' . _x('置顶', 'pinned', 'argon') . '
				</div>';
	}
	if ($type == 'needpassword'){
		return '<div class="post-meta-detail post-meta-detail-needpassword">
					<i class="fa fa-lock" aria-hidden="true"></i>
					' . __('需要密码', 'argon') . '
				</div>';
	}
	if ($type == 'time'){
		return '<div class="post-meta-detail post-meta-detail-time">
					<i class="fa fa-clock-o" aria-hidden="true"></i>
					<time title="' . __('发布于', 'argon') . ' ' . get_the_time('Y-n-d G:i:s') . ' | ' . __('编辑于', 'argon') . ' ' . get_the_modified_time('Y-n-d G:i:s') . '">' .
						get_the_time('Y-n-d G:i') . '
					</time>
				</div>';
	}
	if ($type == 'edittime'){
		return '<div class="post-meta-detail post-meta-detail-edittime">
					<i class="fa fa-clock-o" aria-hidden="true"></i>
					<time title="' . __('发布于', 'argon') . ' ' . get_the_time('Y-n-d G:i:s') . ' | ' . __('编辑于', 'argon') . ' ' . get_the_modified_time('Y-n-d G:i:s') . '">' .
						get_the_modified_time('Y-n-d G:i') . '
					</time>
				</div>';
	}
	if ($type == 'views'){
		if (function_exists('pvc_get_post_views')){
			$views = pvc_get_post_views(get_the_ID());
		}else{
			$views = get_post_views(get_the_ID());
		}
		return '<div class="post-meta-detail post-meta-detail-views">
					<i class="fa fa-eye" aria-hidden="true"></i> ' .
					$views .
				'</div>';
	}
	if ($type == 'comments'){
		return '<div class="post-meta-detail post-meta-detail-comments">
					<i class="fa fa-comments-o" aria-hidden="true"></i> ' .
					get_post(get_the_ID()) -> comment_count .
				'</div>';
	}
	if ($type == 'categories'){
		$res = '<div class="post-meta-detail post-meta-detail-categories">
				<i class="fa fa-bookmark-o" aria-hidden="true"></i> ';
		$categories = get_the_category();
		foreach ($categories as $index => $category){
			$res .= '<a href="' . get_category_link($category -> term_id) . '" target="_blank" class="post-meta-detail-catagory-link">' . $category -> cat_name . '</a>';
			if ($index != count($categories) - 1){
				$res .= '<span class="post-meta-detail-catagory-space">,</span>';
			}
		}
		$res .= '</div>';
		return $res;
	}
	if ($type == 'author'){
		$res = '<div class="post-meta-detail post-meta-detail-author">
					<i class="fa fa-user-circle-o" aria-hidden="true"></i> ';
					global $authordata;
		$res .= '<a href="' . get_author_posts_url($authordata -> ID, $authordata -> user_nicename) . '" target="_blank">' . get_the_author() . '</a>
				</div>';
		return $res;
	}
}
//获取文章字数统计和预计阅读时间
function get_article_reading_time_meta($post_content_full){
	$post_content_full = apply_filters("argon_html_before_wordcount", $post_content_full);
	$words = get_article_words($post_content_full);
	$res = '</br><div class="post-meta-detail post-meta-detail-words">
		<i class="fa fa-file-word-o" aria-hidden="true"></i>';
	if ($words['code'] > 0){
		$res .= '<span title="' . sprintf(__( '包含 %d 行代码', 'argon'), $words['code']) . '">';
	}else{
		$res .= '<span>';
	}
	$res .= ' ' . get_article_words_total($post_content_full) . " " . __("字", 'argon');
	$res .= '</span>
		</div>
		<div class="post-meta-devide">|</div>
		<div class="post-meta-detail post-meta-detail-words">
			<i class="fa fa-hourglass-end" aria-hidden="true"></i>
			' . get_reading_time(get_article_words($post_content_full)) . '
		</div>
	';
	return $res;
}
//当前文章是否隐藏 阅读时间 Meta
function is_readingtime_meta_hidden(){
	if (strpos(get_the_content() , "[hide_reading_time][/hide_reading_time]") !== False){
		return true;
	}
	global $post;
	if (get_post_meta($post -> ID, 'argon_hide_readingtime', true) == 'true'){
		return true;
	}
	return false;
}
//当前文章是否隐藏 发布时间和分类 (简洁 Meta)
function is_meta_simple(){
	global $post;
	if (get_post_meta($post -> ID, 'argon_meta_simple', true) == 'true'){
		return true;
	}
	return false;
}
//根据文章 id 获取标题
function get_post_title_by_id($id){
	return get_post($id) -> post_title;
}
//解析 UA 和相应图标
require_once(get_template_directory() . '/useragent-parser.php');
$argon_comment_ua = get_option("argon_comment_ua");
$argon_comment_show_ua = Array();
if (strpos($argon_comment_ua, 'platform') !== false){
	$argon_comment_show_ua['platform'] = true;
}
if (strpos($argon_comment_ua, 'browser') !== false){
	$argon_comment_show_ua['browser'] = true;
}
if (strpos($argon_comment_ua, 'version') !== false){
	$argon_comment_show_ua['version'] = true;
}
function parse_ua_and_icon($userAgent){
	global $argon_comment_ua;
	global $argon_comment_show_ua;
	if ($argon_comment_ua == "" || $argon_comment_ua == "hidden"){
		return "";
	}
	$parsed = argon_parse_user_agent($userAgent);
	$out = "<div class='comment-useragent'>";
	if (isset($argon_comment_show_ua['platform']) && $argon_comment_show_ua['platform'] == true){
		if (isset($GLOBALS['UA_ICON'][$parsed['platform']])){
			$out .= $GLOBALS['UA_ICON'][$parsed['platform']] . " ";
		}else{
			$out .= $GLOBALS['UA_ICON']['Unknown'] . " ";
		}
		$out .= $parsed['platform'];
	}
	if (isset($argon_comment_show_ua['browser']) && $argon_comment_show_ua['browser'] == true){
		if (isset($GLOBALS['UA_ICON'][$parsed['browser']])){
			$out .= " " . $GLOBALS['UA_ICON'][$parsed['browser']];
		}else{
			$out .= " " . $GLOBALS['UA_ICON']['Unknown'];
		}
		$out .= " " . $parsed['browser'];
		if (isset($argon_comment_show_ua['version']) && $argon_comment_show_ua['version'] == true){
			$out .= " " . $parsed['version'];
		}
	}
	$out .= "</div>";
	return apply_filters("argon_comment_ua_icon", $out);
}
//发送邮件
function send_mail($to, $subject, $content){
	wp_mail($to, $subject, $content, array('Content-Type: text/html; charset=UTF-8'));
}
function check_email_address($email){
	return (bool) preg_match( "/^\w+((-\w+)|(\.\w+))*@[A-Za-z0-9]+(([.\-])[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/", $email );
}
//检验评论 Token 和用户 Token 是否一致
function check_comment_token($id){
	if (strlen($_COOKIE['argon_user_token']) != 32){
		return false;
	}
	if ($_COOKIE['argon_user_token'] != get_comment_meta($id, "user_token", true)){
		return false;
	}
	return true;
}
//检验评论发送者 ID 和当前登录用户 ID 是否一致
function check_login_user_same($userid){
	if ($userid == 0){
		return false;
	}
	if ($userid != (wp_get_current_user() -> ID)){
		return false;
	}
	return true;
}
function get_comment_user_id_by_id($comment_ID){
	$comment = get_comment($comment_ID);
	return $comment -> user_id;
}
function check_comment_userid($id){
	if (!check_login_user_same(get_comment_user_id_by_id($id))){
		return false;
	}
	return true;
}
//悄悄话
function is_comment_private_mode($id){
	if (strlen(get_comment_meta($id, "private_mode", true)) != 32){
		return false;
	}
	return true;
}
function user_can_view_comment($id){
	if (!is_comment_private_mode($id)){
		return true;
	}
	if (current_user_can("manage_options")){
		return true;
	}
	if ($_COOKIE['argon_user_token'] == get_comment_meta($id, "private_mode", true)){
		return true;
	}
	return false;
}
//过滤 RSS 中悄悄话
function remove_rss_private_comment_title_and_author($str){
	global $comment;
	if (isset($comment -> comment_ID) && is_comment_private_mode($comment -> comment_ID)){
		return "***";
	}
	return $str;
}
add_filter('the_title_rss' , 'remove_rss_private_comment_title_and_author');
add_filter('comment_author_rss' , 'remove_rss_private_comment_title_and_author');
function remove_rss_private_comment_content($str){
	global $comment;
	if (is_comment_private_mode($comment -> comment_ID)){
		$comment -> comment_content = __('该评论为悄悄话', 'argon');
		return $comment -> comment_content;
	}
	return $str;
}
add_filter('comment_text_rss' , 'remove_rss_private_comment_content');
//评论回复信息
function get_comment_parent_info($comment){
	if (!$GLOBALS['argon_comment_options']['show_comment_parent_info']){
		return "";
	}
	if ($comment -> comment_parent == 0){
		return "";
	}
	$parent_comment = get_comment($comment -> comment_parent);
	return '<div class="comment-parent-info" data-parent-id=' . $parent_comment -> comment_ID . '><i class="fa fa-reply" aria-hidden="true"></i> ' . get_comment_author($parent_comment -> comment_ID) . '</div>';
}
//是否可以查看评论编辑记录
function can_visit_comment_edit_history($id){
	$who_can_visit_comment_edit_history = get_option("argon_who_can_visit_comment_edit_history");
	if ($who_can_visit_comment_edit_history == ""){
		$who_can_visit_comment_edit_history = "admin";
	}
	switch ($who_can_visit_comment_edit_history) {
		case 'everyone':
			return true;

		case 'commentsender':
			if (check_comment_token($id) || check_comment_userid($id)){
				return true;
			}
			return false;

		default:
			if (current_user_can("moderate_comments")){
				return true;
			}
			return false;
	}
}
//获取评论编辑记录
function get_comment_edit_history(){
	$id = $_POST['id'];
	if (!can_visit_comment_edit_history($id)){
		exit(json_encode(array(
			'id' => $_POST['id'],
			'history' => ""
		)));
	}
	$editHistory = json_decode(get_comment_meta($id, "comment_edit_history", true));
	$editHistory = array_reverse($editHistory);
	$res = "";
	$position = count($editHistory) + 1;
	date_default_timezone_set(get_option('timezone_string'));
	foreach ($editHistory as $edition){
		$position -= 1;
		$res .= "<div class='comment-edit-history-item'>
					<div class='comment-edit-history-title'>
						<div class='comment-edit-history-id'>
							#" . $position . "
						</div>
						" . ($edition -> isfirst ? "<span class='badge badge-primary badge-admin'>" . __("最初版本", 'argon') . "</span>" : "") . "
					</div>
					<div class='comment-edit-history-time'>" . date('Y-m-d H:i:s', $edition -> time) . "</div>
					<div class='comment-edit-history-content'>" . str_replace("\n", "</br>", $edition -> content) . "</div>
				</div>";
	}
	exit(json_encode(array(
		'id' => $_POST['id'],
		'history' => $res
	)));
}
add_action('wp_ajax_get_comment_edit_history', 'get_comment_edit_history');
add_action('wp_ajax_nopriv_get_comment_edit_history', 'get_comment_edit_history');
//是否可以置顶/取消置顶
function is_comment_pinable($id){
	if (get_comment($id) -> comment_approved != "1"){
		return false;
	}
	if (get_comment($id) -> comment_parent != 0){
		return false;
	}
	if (is_comment_private_mode($id)){
		return false;
	}
	return true;
}
//评论内容格式化
function argon_get_comment_text($comment_ID = 0, $args = array()) {
	$comment = get_comment($comment_ID);
	$comment_text = get_comment_text($comment, $args);
	$enableMarkdown = get_comment_meta(get_comment_ID(), "use_markdown", true);
	/*if ($enableMarkdown == false){
		return $comment_text;
	}*/
	//图片
	$comment_text = preg_replace(
		'/<a data-src="(.*?)" title="(.*?)" class="comment-image"(.*?)>([\w\W]*)<\/a>/',
		'<img src="$1" alt="$2" />',
		$comment_text
	);
	$comment_text = preg_replace(
		'/<img src="(.*?)" alt="(.*?)" \/>/',
		'<a href="$1" title="$2" data-fancybox="comment-' . $comment -> comment_ID . '-image" class="comment-image" rel="nofollow">
			<i class="fa fa-image" aria-hidden="true"></i>
			' . __('查看图片', 'argon') . '
			<img src="" alt="$2" class="comment-image-preview">
			<i class="comment-image-preview-mask"></i>
		</a>',
		$comment_text
	);
	//表情
	if (get_option("argon_comment_emotion_keyboard", "true") != "false"){
		global $emotionListDefault;
		$emotionList = apply_filters("argon_emotion_list", $emotionListDefault);
		foreach ($emotionList as $groupIndex => $group){ 
			foreach ($group['list'] as $index => $emotion){
				if ($emotion['type'] != 'sticker'){
					continue;
				}
				if (!isset($emotion['code']) || mb_strlen($emotion['code']) == 0){
					continue;
				}
				if (!isset($emotion['src']) || mb_strlen($emotion['src']) == 0){
					continue;
				}
				$comment_text = str_replace(':' . $emotion['code'] . ':', "<img class='comment-sticker' src='" . $emotion['src'] . "' loading='lazy'/>", $comment_text);
			}
		}
	}
	return apply_filters( 'comment_text', $comment_text, $comment, $args );
}
//评论点赞
function get_comment_upvotes($id) {
	$comment = get_comment($id);
	if ($comment == null){
		return 0;
	}
	$upvotes = get_comment_meta($comment -> comment_ID, "upvotes", true);
	if ($upvotes == null) {
		$upvotes = 0;
	}
	return $upvotes;
}
function set_comment_upvotes($id){
	$comment = get_comment($id);
	if ($comment == null){
		return 0;
	}
	$upvotes = get_comment_meta($comment -> comment_ID, "upvotes", true);
	if ($upvotes == null) {
		$upvotes = 0;
	}
	$upvotes++;
	update_comment_meta($comment -> comment_ID, "upvotes", $upvotes);
	return $upvotes;
}
function is_comment_upvoted($id){
	$upvotedList = isset( $_COOKIE['argon_comment_upvoted'] ) ? $_COOKIE['argon_comment_upvoted'] : '';
	if (in_array($id, explode(',', $upvotedList))){
		return true;
	}
	return false;
}
function upvote_comment(){
	if (get_option("argon_enable_comment_upvote", "false") != "true"){
		return;
	}
	header('Content-Type:application/json; charset=utf-8');
	$ID = $_POST["comment_id"];
	$comment = get_comment($ID);
	if ($comment == null){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('评论不存在', 'argon'),
			'total_upvote' => 0
		)));
	}
	$upvotedList = isset( $_COOKIE['argon_comment_upvoted'] ) ? $_COOKIE['argon_comment_upvoted'] : '';
	if (in_array($ID, explode(',', $upvotedList))){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('该评论已被赞过', 'argon'),
			'total_upvote' => get_comment_upvotes($ID)
		)));
	}
    set_comment_upvotes($ID);
    setcookie('argon_comment_upvoted', $upvotedList . $ID . "," , array(
        'expires' => time() + 3153600000,
        'path' => '/',
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax'
    ));
	exit(json_encode(array(
		'ID' => $ID,
		'status' => 'success',
		'msg' => __('点赞成功', 'argon'),
		'total_upvote' => format_number_in_kilos(get_comment_upvotes($ID))
	)));
}
add_action('wp_ajax_upvote_comment' , 'upvote_comment');
add_action('wp_ajax_nopriv_upvote_comment' , 'upvote_comment');
//评论样式格式化
$GLOBALS['argon_comment_options']['enable_upvote'] = (get_option("argon_enable_comment_upvote", "false") == "true");
$GLOBALS['argon_comment_options']['enable_pinning'] = (get_option("argon_enable_comment_pinning", "false") == "true");
$GLOBALS['argon_comment_options']['current_user_can_moderate_comments'] = current_user_can('moderate_comments');
$GLOBALS['argon_comment_options']['show_comment_parent_info'] = (get_option("argon_show_comment_parent_info", "true") == "true");
function argon_comment_format($comment, $args, $depth){
	global $comment_enable_upvote, $comment_enable_pinning;
	$GLOBALS['comment'] = $comment;
	if (!($comment -> placeholder) && user_can_view_comment(get_comment_ID())){
	?>
	<li class="comment-item" id="comment-<?php comment_ID(); ?>" data-id="<?php comment_ID(); ?>" data-use-markdown="<?php echo get_comment_meta(get_comment_ID(), "use_markdown", true);?>">
		<div class="comment-item-left-wrapper">
			<div class="comment-item-avatar">
				<?php if(function_exists('get_avatar') && get_option('show_avatars')){
					echo get_avatar($comment, 40);
				}?>
			</div>
			<?php if ($GLOBALS['argon_comment_options']['enable_upvote']){ ?>
				<button class="comment-upvote btn btn-icon btn-outline-primary btn-sm <?php echo (is_comment_upvoted(get_comment_ID()) ? 'upvoted' : ''); ?>" type="button" data-id="<?php comment_ID(); ?>">
					<span class="btn-inner--icon"><i class="fa fa-caret-up"></i></span>
					<span class="btn-inner--text">
						<span class="comment-upvote-num"><?php echo format_number_in_kilos(get_comment_upvotes(get_comment_ID())); ?></span>
					</span>
				</button>
			<?php } ?>
		</div>
		<div class="comment-item-inner" id="comment-inner-<?php comment_ID();?>">
			<div class="comment-item-title">
				<div class="comment-name">
					<div class="comment-author"><?php echo get_comment_author_link();?></div>
					<?php if (user_can($comment -> user_id , "update_core")){
						echo '<span class="badge badge-primary badge-admin">' . __('博主', 'argon') . '</span>';}
					?>
					<?php echo get_comment_parent_info($comment); ?>
					<?php if ($GLOBALS['argon_comment_options']['enable_pinning'] && get_comment_meta(get_comment_ID(), "pinned", true) == "true"){
						echo '<span class="badge badge-danger badge-pinned"><i class="fa fa-thumb-tack" aria-hidden="true"></i> ' . _x('置顶', 'pinned', 'argon') . '</span>';
					}?>
					<?php if (is_comment_private_mode(get_comment_ID()) && user_can_view_comment(get_comment_ID())){
						echo '<span class="badge badge-success badge-private-comment">' . __('悄悄话', 'argon') . '</span>';}
					?>
					<?php if ($comment -> comment_approved == 0){
						echo '<span class="badge badge-warning badge-unapproved">' . __('待审核', 'argon') . '</span>';}
					?>
					<?php
						echo parse_ua_and_icon($comment -> comment_agent);
					?>
				</div>
				<div class="comment-info">
					<?php if (get_comment_meta(get_comment_ID(), "edited", true) == "true") { ?>
						<div class="comment-edited<?php if (can_visit_comment_edit_history(get_comment_ID())){echo ' comment-edithistory-accessible';}?>">
							<i class="fa fa-pencil" aria-hidden="true"></i><?php _e('已编辑', 'argon')?>
						</div>
					<?php } ?>
					<div class="comment-time">
						<span class="human-time" data-time="<?php echo get_comment_time('U', true);?>"><?php echo human_time_diff(get_comment_time('U') , current_time('timestamp')) . __("前", "argon");?></span>
						<div class="comment-time-details"><?php echo get_comment_time('Y-n-d G:i:s');?></div>
					</div>
				</div>
			</div>
			<div class="comment-item-text">
				<?php echo argon_get_comment_text();?>
			</div>
			<div class="comment-item-source" style="display: none;" aria-hidden="true"><?php echo htmlspecialchars(get_comment_meta(get_comment_ID(), "comment_content_source", true));?></div>

			<div class="comment-operations">
				<?php if ($GLOBALS['argon_comment_options']['enable_pinning'] && $GLOBALS['argon_comment_options']['current_user_can_moderate_comments'] && is_comment_pinable(get_comment_ID())) {
					if (get_comment_meta(get_comment_ID(), "pinned", true) == "true") { ?>
						<button class="comment-unpin btn btn-sm btn-outline-primary" data-id="<?php comment_ID(); ?>" type="button" style="margin-right: 2px;"><?php _e('取消置顶', 'argon')?></button>
					<?php } else { ?>
						<button class="comment-pin btn btn-sm btn-outline-primary" data-id="<?php comment_ID(); ?>" type="button" style="margin-right: 2px;"><?php _ex('置顶', 'to pin', 'argon')?></button>
				<?php }
					} ?>
				<?php if ((check_comment_token(get_comment_ID()) || check_login_user_same($comment -> user_id)) && (get_option("argon_comment_allow_editing") != "false")) { ?>
					<button class="comment-edit btn btn-sm btn-outline-primary" data-id="<?php comment_ID(); ?>" type="button" style="margin-right: 2px;"><?php _e('编辑', 'argon')?></button>
				<?php } ?>
				<button class="comment-reply btn btn-sm btn-outline-primary" data-id="<?php comment_ID(); ?>" type="button"><?php _e('回复', 'argon')?></button>
			</div>
		</div>
	</li>
	<li class="comment-divider"></li>
	<li>
<?php }}
//评论样式格式化 (说说预览界面)
function argon_comment_shuoshuo_preview_format($comment, $args, $depth){
	$GLOBALS['comment'] = $comment;?>
	<li class="comment-item" id="comment-<?php comment_ID(); ?>">
		<div class="comment-item-inner " id="comment-inner-<?php comment_ID();?>">
			<span class="shuoshuo-comment-item-title">
				<?php echo get_comment_author_link();?>
				<?php if( user_can($comment -> user_id , "update_core") ){
					echo '<span class="badge badge-primary badge-admin">' . __('博主', 'argon') . '</span>';}
				?>
				<?php if( $comment -> comment_approved == 0 ){
					echo '<span class="badge badge-warning badge-unapproved">' . __('待审核', 'argon') . '</span>';}
				?>
				:
			</span>
			<span class="shuoshuo-comment-item-text">
				<?php echo strip_tags(get_comment_text());?>
			</span>
		</div>
	</li>
	<li>
<?php }
function comment_author_link_filter($html){
	return str_replace('href=', 'target="_blank" href=', $html);
}
add_filter('get_comment_author_link', 'comment_author_link_filter');
//评论验证码生成 & 验证
function get_comment_captcha_seed($refresh = false){
	if (isset($_SESSION['captchaSeed']) && !$refresh){
		$res = $_SESSION['captchaSeed'];
		if (empty($_POST)){
			session_write_close();
		}
		return $res;
	}
	$captchaSeed = rand(0 , 500000000);
	$_SESSION['captchaSeed'] = $captchaSeed;
	session_write_close();
	return $captchaSeed;
}
get_comment_captcha_seed();
class captcha_calculation{ //数字验证码
	var $captchaSeed;
	function __construct($seed) {
		$this -> captchaSeed = $seed;
	}
	function getChallenge(){
		mt_srand($this -> captchaSeed + 10007);
		$oper = mt_rand(1 , 4);
		$num1 = 0;
		$num2 = 0;
		switch ($oper){
			case 1:
				$num1 = mt_rand(1 , 20);
				$num2 = mt_rand(0 , 20 - $num1);
				return $num1 . " + " . $num2 . " = ";
				break;
			case 2:
				$num1 = mt_rand(10 , 20);
				$num2 = mt_rand(1 , $num1);
				return $num1 . " - " . $num2 . " = ";
				break;
			case 3:
				$num1 = mt_rand(3 , 9);
				$num2 = mt_rand(3 , 9);
				return $num1 . " * " . $num2 . " = ";
				break;
			case 4:
				$num2 = mt_rand(2 , 9);
				$num1 = $num2 * mt_rand(2 , 9);
				return $num1 . " / " . $num2 . " = ";
				break;
			default:
				break;
		}
	}
	function getAnswer(){
		mt_srand($this -> captchaSeed + 10007);
		$oper = mt_rand(1 , 4);
		$num1 = 0;
		$num2 = 0;
		switch ($oper){
			case 1:
				$num1 = mt_rand(1 , 20);
				$num2 = mt_rand(0 , 20 - $num1);
				return $num1 + $num2;
				break;
			case 2:
				$num1 = mt_rand(10 , 20);
				$num2 = mt_rand(1 , $num1);
				return $num1 - $num2;
				break;
			case 3:
				$num1 = mt_rand(3 , 9);
				$num2 = mt_rand(3 , 9);
				return $num1 * $num2;
				break;
			case 4:
				$num2 = mt_rand(2 , 9);
				$num1 = $num2 * mt_rand(2 , 9);
				return $num1 / $num2;
				break;
			default:
				break;
		}
		return "";
	}
	function check($answer){
		if ($answer == self::getAnswer()){
			return true;
		}
		return false;
	}
}
function wrong_captcha($msg = null){
	$message = $msg ? $msg : __('验证码错误', 'argon');
	exit(json_encode(array(
		'status' => 'failed',
		'msg' => $message,
		'isAdmin' => current_user_can('level_7')
	)));
	//wp_die('验证码错误，评论失败');
}
function get_comment_captcha(){
	$captcha = new captcha_calculation(get_comment_captcha_seed());
	return $captcha -> getChallenge();
}
function get_comment_captcha_answer(){
	$captcha = new captcha_calculation(get_comment_captcha_seed());
	return $captcha -> getAnswer();
}
// Geetest 验证码相关函数
function geetest_validate($lot_number, $captcha_output, $pass_token, $gen_time) {
	$captcha_id = get_option('argon_geetest_captcha_id', '');
	$captcha_key = get_option('argon_geetest_captcha_key', '');
	$api_server = get_option('argon_geetest_api_server', '');
	
	// 如果 api_server 为空，使用默认值
	if (empty($api_server)) {
		$api_server = 'https://gcaptcha4.geetest.com';
	}
	
	if (empty($captcha_id) || empty($captcha_key)) {
		return false;
	}
	
	// 验证必需参数
	if (empty($lot_number) || empty($captcha_output) || empty($pass_token) || empty($gen_time)) {
		return false;
	}
	
	// 生成签名 - 使用lot_number的字节进行HMAC-SHA256签名
	$sign_token = hash_hmac('sha256', $lot_number, $captcha_key);
	
	// 构建请求参数
	$query = array(
		"lot_number" => $lot_number,
		"captcha_output" => $captcha_output,
		"pass_token" => $pass_token,
		"gen_time" => $gen_time,
		"sign_token" => $sign_token
	);
	
	// 构建完整的URL，包含captcha_id参数
	$url = sprintf("%s/validate?captcha_id=%s", rtrim($api_server, '/'), $captcha_id);
	
	global $argon_geetest_last_reason;
	$argon_geetest_last_reason = '';
	
	$response = geetest_post_request($url, $query);
	
	if ($response === false) {
		// geetest_post_request 已经设置了具体原因
		if (empty($argon_geetest_last_reason)) {
			$argon_geetest_last_reason = 'http_request_failed';
		}
		return false;
	}
	
	$result = json_decode($response, true);
	
	// 检查JSON解析是否成功
	if (json_last_error() !== JSON_ERROR_NONE) {
		$argon_geetest_last_reason = 'json_decode_error: ' . json_last_error_msg();
		return false;
	}
	
	// 根据官方文档，检查返回结果（GT4 返回 result 与 reason）
	if (isset($result['result'])) {
		if ($result['result'] === 'success') {
			return true;
		}
		// 失败时记录原因，便于排查
		$argon_geetest_last_reason = isset($result['reason']) ? $result['reason'] : 'unknown';
		return false;
	}
	// 非预期返回结构
	$argon_geetest_last_reason = 'unexpected_response: ' . substr($response, 0, 200);
	return false;
}

function geetest_post_request($url, $postdata) {
	global $argon_geetest_last_reason;
	
	// 验证 URL 格式
	if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
		$argon_geetest_last_reason = 'invalid_url: ' . $url;
		return false;
	}
	
	// 使用 WordPress HTTP API，更可靠
	$response = wp_remote_post($url, array(
		'timeout' => 15,
		'body' => $postdata,
		'headers' => array(
			'Content-Type' => 'application/x-www-form-urlencoded',
			'User-Agent' => 'WordPress/Argon Theme Geetest Client'
		),
		'sslverify' => true
	));
	
	if (is_wp_error($response)) {
		global $argon_geetest_last_reason;
		$argon_geetest_last_reason = 'wp_error: ' . $response->get_error_message();
		return false;
	}
	
	$response_code = wp_remote_retrieve_response_code($response);
	if ($response_code !== 200) {
		global $argon_geetest_last_reason;
		$argon_geetest_last_reason = 'http_' . $response_code;
		return false;
	}
	
	return wp_remote_retrieve_body($response);
}

/**
 * 检查评论验证码是否启用
 * @return bool
 */
function argon_is_comment_captcha_enabled() {
    $mode = get_option('argon_comment_captcha_mode', 'global');
    if ($mode === 'enabled') {
        return true;
    } elseif ($mode === 'disabled') {
        return false;
    }
    // global: 使用全局设置
    return argon_is_captcha_enabled();
}

/**
 * 检查 TODO 验证码是否启用
 * @return bool
 */
function argon_is_todo_captcha_enabled() {
    $mode = get_option('argon_todo_captcha_mode', 'global');
    if ($mode === 'enabled') {
        return true;
    } elseif ($mode === 'disabled') {
        return false;
    }
    // global: 使用全局设置
    return argon_is_captcha_enabled();
}

/**
 * 获取全局验证码是否启用（兼容旧选项名）
 * @return bool
 */
function argon_is_captcha_enabled() {
    $enabled = get_option('argon_need_captcha', get_option('argon_comment_need_captcha', 'true'));
    return $enabled !== 'false';
}

/**
 * 通用验证码验证函数
 * @param string $context 验证场景: 'comment', 'todo' 或 'flink'
 * @return array ['success' => bool, 'error' => string|null]
 */
function argon_verify_captcha($context = 'comment') {
    // 根据场景检查是否需要验证码
    if ($context === 'todo') {
        if (!argon_is_todo_captcha_enabled()) {
            return array('success' => true, 'error' => null);
        }
    } elseif ($context === 'flink') {
        // 友链申请使用评论验证码设置
        if (!argon_is_captcha_enabled()) {
            return array('success' => true, 'error' => null);
        }
    } else {
        if (!argon_is_comment_captcha_enabled()) {
            return array('success' => true, 'error' => null);
        }
    }
    
    // 管理员跳过验证码
    if (current_user_can('level_7')) {
        return array('success' => true, 'error' => null);
    }
    
    $captcha_type = get_option('argon_captcha_type', 'math');
    
    if ($captcha_type === 'geetest') {
        // 极验验证码 - 检查配置
        $captcha_id = get_option('argon_geetest_captcha_id', '');
        $captcha_key = get_option('argon_geetest_captcha_key', '');
        
        if (empty($captcha_id) || empty($captcha_key)) {
            return array('success' => false, 'error' => __('服务端验证码配置异常，请稍后再试', 'argon'));
        }
        
        $lot_number = isset($_POST['lot_number']) ? trim($_POST['lot_number']) : '';
        $captcha_output = isset($_POST['captcha_output']) ? trim($_POST['captcha_output']) : '';
        $pass_token = isset($_POST['pass_token']) ? trim($_POST['pass_token']) : '';
        $gen_time = isset($_POST['gen_time']) ? trim($_POST['gen_time']) : '';
        
        if (empty($lot_number) || empty($captcha_output) || empty($pass_token) || empty($gen_time)) {
            return array('success' => false, 'error' => __('请完成验证码验证', 'argon'));
        }
        
        // 格式验证
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $lot_number) || strlen($lot_number) > 100) {
            return array('success' => false, 'error' => __('验证码参数格式错误', 'argon'));
        }
        if (!is_numeric($gen_time) || $gen_time <= 0) {
            return array('success' => false, 'error' => __('验证码时间参数错误', 'argon'));
        }
        if (strlen($captcha_output) == 0 || strlen($pass_token) == 0) {
            return array('success' => false, 'error' => __('验证码参数不完整', 'argon'));
        }
        if (strlen($captcha_output) > 1000 || strlen($pass_token) > 1000) {
            return array('success' => false, 'error' => __('验证码参数过长', 'argon'));
        }
        
        // 时间窗口校验
        $gen_ts = intval($gen_time);
        if ($gen_ts > 9999999999) {
            $gen_ts = intval($gen_ts / 1000);
        }
        $now_ts = time();
        if ($gen_ts <= 0 || abs($now_ts - $gen_ts) > 300) {
            return array('success' => false, 'error' => __('验证码已过期，请刷新后重试', 'argon'));
        }
        
        // 防重放检查
        $used_key = 'argon_geetest_used_' . md5($lot_number);
        if (get_transient($used_key)) {
            return array('success' => false, 'error' => __('验证码已使用，请刷新后重试', 'argon'));
        }
        
        // 调用极验验证
        if (!geetest_validate($lot_number, $captcha_output, $pass_token, $gen_time)) {
            global $argon_geetest_last_reason;
            $error_message = __('验证码验证失败', 'argon');
            if (!empty($argon_geetest_last_reason)) {
                $error_message .= ' (' . $argon_geetest_last_reason . ')';
            }
            return array('success' => false, 'error' => $error_message);
        }
        
        // 标记已使用
        set_transient($used_key, 1, 15 * MINUTE_IN_SECONDS);
        
    } else {
        // 数学验证码
        $answer = isset($_POST['comment_captcha']) ? trim($_POST['comment_captcha']) : '';
        if ($answer === '') {
            return array('success' => false, 'error' => __('请输入验证码', 'argon'));
        }
        $seed = get_comment_captcha_seed();
        $captcha = new captcha_calculation($seed);
        if (!$captcha->check($answer)) {
            return array('success' => false, 'error' => __('验证码错误', 'argon'));
        }
    }
    
    return array('success' => true, 'error' => null);
}

function check_comment_captcha($comment){
	$result = argon_verify_captcha('comment');
	if (!$result['success']) {
		wrong_captcha($result['error']);
	}
	return $comment;
}
add_filter('preprocess_comment' , 'check_comment_captcha');

function ajax_get_captcha(){
	if (get_option('argon_get_captcha_by_ajax', 'false') != 'true') {
		return;
	}
	exit(json_encode(array(
		'captcha' => get_comment_captcha(get_comment_captcha_seed())
	)));
}
add_action('wp_ajax_get_captcha', 'ajax_get_captcha');
add_action('wp_ajax_nopriv_get_captcha', 'ajax_get_captcha');
//Ajax 发送评论
function ajax_post_comment(){
	$parentID = $_POST['comment_parent'];
	if (is_comment_private_mode($parentID)){
		if (!user_can_view_comment($parentID)){
			//如果父级评论是悄悄话模式且当前 Token 与父级不相同则返回
			exit(json_encode(array(
				'status' => 'failed',
				'msg' =>  __('不能回复其他人的悄悄话评论', 'argon'),
				'isAdmin' => current_user_can('level_7')
			)));
		}
	}
	if (get_option('argon_comment_enable_qq_avatar') == 'true'){
		if (check_qqnumber($_POST['email'])){
			$_POST['qq'] = $_POST['email'];
			$_POST['email'] .= "@qq.com";
		}else{
			$_POST['qq'] = "";
		}
	}
	// CSRF nonce 校验
	if (!isset($_POST['argon_nonce']) || !wp_verify_nonce($_POST['argon_nonce'], 'argon_comment')) {
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('请求已失效，请刷新页面后重试', 'argon'),
			'isAdmin' => current_user_can('level_7')
		)));
	}
	// Honeypot check
	if (!empty($_POST['argon_comment_honeypot'])) {
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('Spam detected', 'argon'),
			'isAdmin' => current_user_can('level_7')
		)));
	}
	// 简单速率限制（按 IP 计数，可配置）
	$rate_enable = get_option('argon_rate_limit_enable', 'true');
	if ($rate_enable === 'true') {
		$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR'];
		$ip = sanitize_text_field($ip);
		$rate_key = 'argon_rate_cmt_' . md5($ip);
		$state = get_transient($rate_key);
		$now = time();
		$window = intval(get_option('argon_rate_limit_window', 300)); // 窗口秒数
		$max_count = intval(get_option('argon_rate_limit_max_count', 5)); // 窗口内最大次数
		$min_interval = intval(get_option('argon_rate_limit_min_interval', 10)); // 两次最小间隔
		// 合理边界
		$window = max(30, $window);
		$max_count = max(1, $max_count);
		$min_interval = max(0, $min_interval);
		if (!is_array($state)) {
			$state = array('count' => 0, 'start' => $now, 'last' => 0);
		}
		if ($state['last'] > 0 && ($now - intval($state['last'])) < $min_interval) {
			exit(json_encode(array(
				'status' => 'failed',
				'msg' => __('您评论过快，请稍后再试', 'argon'),
				'isAdmin' => current_user_can('level_7')
			)));
		}
		if (($now - intval($state['start'])) < $window && intval($state['count']) >= $max_count) {
			exit(json_encode(array(
				'status' => 'failed',
				'msg' => __('操作过于频繁，请稍后再试', 'argon'),
				'isAdmin' => current_user_can('level_7')
			)));
		}
		// 更新速率状态并设置有效期
		if (($now - intval($state['start'])) >= $window) {
			$state['start'] = $now;
			$state['count'] = 0;
		}
		$state['count'] = intval($state['count']) + 1;
		$state['last'] = $now;
		set_transient($rate_key, $state, $window);
	}
	$comment = wp_handle_comment_submission(wp_unslash($_POST));
	if (is_wp_error($comment)){
		$msg = $comment -> get_error_data();
		if (!empty($msg)){
			$msg = $comment -> get_error_message();
		}
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => $msg,
			'isAdmin' => current_user_can('level_7')
		)));
	}
	$user = wp_get_current_user();
	do_action('set_comment_cookies', $comment, $user);
	if (isset($_POST['qq'])){
		if (!empty($_POST['qq']) && get_option('argon_comment_enable_qq_avatar') == 'true'){
			$_comment = $comment;
			$_comment -> comment_author_email = $_POST['qq'] . "@avatarqq.com";
			do_action('set_comment_cookies', $_comment, $user);
		}
	}
	$html = wp_list_comments(
		array(
			'type'      => 'comment',
			'callback'  => 'argon_comment_format',
			'echo'      => false
		),
		array($comment)
	);
	$newCaptchaSeed = get_comment_captcha_seed(true);
	$newCaptcha = get_comment_captcha($newCaptchaSeed);
	if (current_user_can('level_7')){
		$newCaptchaAnswer = get_comment_captcha_answer($newCaptchaSeed);
	}else{
		$newCaptchaAnswer = "";
	}
	exit(json_encode(array(
		'status' => 'success',
		'html' => $html,
		'id' => $comment -> comment_ID,
		'parentID' => $comment -> comment_parent,
		'commentOrder' => (get_option("comment_order") == "" ? "desc" : get_option("comment_order")),
		'newCaptchaSeed' => $newCaptchaSeed,
		'newCaptcha' => $newCaptcha,
		'newCaptchaAnswer' => $newCaptchaAnswer,
		'isAdmin' => current_user_can('level_7'),
		'isLogin' => is_user_logged_in()
	)));
}
add_action('wp_ajax_ajax_post_comment', 'ajax_post_comment');
add_action('wp_ajax_nopriv_ajax_post_comment', 'ajax_post_comment');
//评论 Markdown 解析
require_once(get_template_directory() . '/parsedown.php');
function comment_markdown_parse($comment_content){
	//HTML 过滤
	global $allowedtags;
	//$comment_content = wp_kses($comment_content, $allowedtags);
	//允许评论中额外的 HTML Tag
	$allowedtags['pre'] = array('class' => array());
	$allowedtags['i'] = array('class' => array(), 'aria-hidden' => array());
	$allowedtags['img'] = array('src' => array(), 'alt' => array(), 'class' => array());
	$allowedtags['ol'] = array();
	$allowedtags['ul'] = array();
	$allowedtags['li'] = array();
	$allowedtags['span'] = array('class' => array(), 'style' => array(), 'title' => array());
	$allowedtags['a']['class'] = array();
	$allowedtags['a']['data-src'] = array();
	$allowedtags['a']['target'] = array();
	$allowedtags['h1'] = $allowedtags['h2'] = $allowedtags['h3'] = $allowedtags['h4'] = $allowedtags['h5'] = $allowedtags['h6'] = array();

	//解析 Markdown
	$parsedown = new _Parsedown();
	$res = $parsedown -> text($comment_content);
	/*$res = preg_replace(
		'/<code>([\s\S]*?)<\/code>/',
		'<pre>$1</pre>',
		$res
	);*/

	$res = preg_replace(
		'/<a (.*?)>(.*?)<\/a>/',
		'<a $1 target="_blank">$2</a>',
		$res
	);
	return $res;
}
function argon_apply_comment_macros($text){
    // 黑幕：{{黑幕|内容}} 或 {{黑幕|内容|提示}}
    $text = preg_replace_callback('/\{\{黑幕\|([\s\S]*?)(?:\|([\s\S]*?))?\}\}/u', function($m){
        $content = trim($m[1]);
        $title = isset($m[2]) ? trim($m[2]) : '你知道的太多了';
        return '<span class="heimu"' . (strlen($title) ? ' title="' . htmlspecialchars($title, ENT_QUOTES) . '"' : '') . '>' . htmlspecialchars($content) . '</span>';
    }, $text);
    // 胡话：{{胡话|内容}} 或 {{胡话|内容|提示}}
    $text = preg_replace_callback('/\{\{胡话\|([\s\S]*?)\}\}/u', function($m){
        $parts = explode('|', $m[1]);
        $content = isset($parts[0]) ? trim($parts[0]) : '';
        $tip = isset($parts[1]) ? trim($parts[1]) : '只为博君一笑，不必照单全收XD';
        return '<span class="huhua"' . (strlen($tip) ? ' title="' . htmlspecialchars($tip, ENT_QUOTES) . '"' : '') . '>' . htmlspecialchars($content) . '</span>';
    }, $text);
    // 文字模糊：{{文字模糊|内容|提示|颜色|时间}}
    $text = preg_replace_callback('/\{\{文字模糊\|([\s\S]*?)\}\}/u', function($m){
        $parts = explode('|', $m[1]);
        $content = isset($parts[0]) ? trim($parts[0]) : '';
        $tip = isset($parts[1]) ? trim($parts[1]) : '你知道的太多了';
        $color = isset($parts[2]) ? trim($parts[2]) : '';
        $time = isset($parts[3]) ? trim($parts[3]) : '0.2';
        $style = '--text-blur-transition-time: ' . preg_replace('/[^0-9\.]/', '', $time) . 's;';
        if (strlen($color) > 0) {
            $style .= ' --text-blur-color: ' . htmlspecialchars($color, ENT_QUOTES) . ';';
        }
        return '<span class="text-blur"' . (strlen($tip) ? ' title="' . htmlspecialchars($tip, ENT_QUOTES) . '"' : '') . ' style="' . $style . '">' . htmlspecialchars($content) . '</span>';
    }, $text);
    // 彩幕：{{彩幕|内容|背景色|提示|前景色}}
    $text = preg_replace_callback('/\{\{彩幕\|([\s\S]*?)\}\}/u', function($m){
        $parts = explode('|', $m[1]);
        $content = isset($parts[0]) ? trim($parts[0]) : '';
        $bg = isset($parts[1]) ? trim($parts[1]) : '#252525';
        $tip = isset($parts[2]) ? trim($parts[2]) : '你知道的太多了';
        $fg = isset($parts[3]) ? trim($parts[3]) : '';
        
        // 确保背景色有 # 前缀
        $bghex = (substr($bg, 0, 1) == '#') ? $bg : ('#' . $bg);
        
        // 如果没有指定前景色，根据背景色亮度自动计算
        if (empty($fg)) {
            $hex = ltrim($bghex, '#');
            if (strlen($hex) == 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            if (strlen($hex) == 6) {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
                $fg = ($luma >= 180) ? '#000' : '#fff';
            } else {
                $fg = '#fff';
            }
        }
        
        $style = '--curtain-bg: ' . htmlspecialchars($bghex, ENT_QUOTES) . '; --curtain-fg: ' . htmlspecialchars($fg, ENT_QUOTES) . ';';
        return '<span class="color-curtain"' . (strlen($tip) ? ' title="' . htmlspecialchars($tip, ENT_QUOTES) . '"' : '') . ' style="' . $style . '">' . htmlspecialchars($content) . '</span>';
    }, $text);
    return $text;
}
function argon_extend_comment_allowed_tags($tags, $context){
    if ($context !== 'comment') { return $tags; }
    $tags['span'] = array('class' => true, 'style' => true, 'title' => true);
    return $tags;
}
add_filter('wp_kses_allowed_html', 'argon_extend_comment_allowed_tags', 10, 2);
function argon_comment_text_render($text){
    return argon_apply_comment_macros($text);
}
add_filter('comment_text', 'argon_comment_text_render', 9);
add_filter('the_content', 'argon_comment_text_render', 9);

//评论发送处理
function post_comment_preprocessing($comment){
	//保存评论未经 Markdown 解析的源码
	$_POST['comment_content_source'] = $comment['comment_content'];
	$comment['comment_content'] = argon_apply_comment_macros($comment['comment_content']);
	//Markdown
	if ($_POST['use_markdown'] == 'true' && get_option("argon_comment_allow_markdown") != "false"){
		$comment['comment_content'] = comment_markdown_parse($comment['comment_content']);
	}
	return $comment;
}
add_filter('preprocess_comment' , 'post_comment_preprocessing');
//发送评论通知邮件
function comment_mail_notify($comment){
	if (get_option("argon_comment_allow_mailnotice") != "true"){
		return;
	}
	if ($comment == null){
		return;
	}
	$id = $comment -> comment_ID;
	$commentPostID = $comment -> comment_post_ID;
	$commentAuthor = $comment -> comment_author;
	$parentID = $comment -> comment_parent;
	if ($parentID == 0){
		return;
	}
	$parentComment = get_comment($parentID);
	$parentEmail =  $parentComment -> comment_author_email;
	$parentName = $parentComment -> comment_author;
	$emailTo = "$parentName <$parentEmail>";
	if (get_comment_meta($parentID, "enable_mailnotice", true) == "true"){
		if (check_email_address($parentEmail)){
			$title = __("您在", 'argon') . " 「" . wp_trim_words(get_post_title_by_id($commentPostID), 20) . "」 " . __("的评论有了新的回复", 'argon');
			$fullTitle = __("您在", 'argon') . " 「" . get_post_title_by_id($commentPostID) . "」 " . __("的评论有了新的回复", 'argon');
			$content = htmlspecialchars(get_comment_meta($id, "comment_content_source", true));
			$link = get_permalink($commentPostID) . "#comment-" . $id;
			$unsubscribeLink = site_url("unsubscribe-comment-mailnotice?comment=" . $parentID . "&token=" . get_comment_meta($parentID, "mailnotice_unsubscribe_key", true));
			
			// 使用新的邮件模板系统
			$settings = argon_get_email_settings();
			$post = get_post($commentPostID);
			
			// 生成新模板内容
			$email_content = argon_get_reply_notify_content(array(
				'post_title' => $post->post_title,
				'post_url' => get_permalink($post->ID),
				'original_comment' => wp_trim_words($parentComment->comment_content, 50, '...'),
				'replier_name' => $commentAuthor,
				'reply_content' => $content,
				'comment_url' => $link,
				'theme_color' => $settings['theme_color']
			));
			
			// 添加退订链接
			$email_content .= '<p style="margin-top: 24px; text-align: center;">
				<a href="' . esc_url($unsubscribeLink) . '" style="color: #8898aa; font-size: 12px; text-decoration: none;">' . __('退订该评论的邮件提醒', 'argon') . '</a>
			</p>';
			
			// 渲染完整邮件
			$html = argon_render_email($email_content, array('subject' => $title));
			
			$html = apply_filters("argon_comment_mail_notification_content", $html); 
			send_mail($emailTo, $title, $html);
		}
	}
}
function argon_async_comment_mail_notify_handler($comment_id){
	$comment = get_comment($comment_id);
	comment_mail_notify($comment);
}
add_action('argon_async_comment_mail_notify', 'argon_async_comment_mail_notify_handler');
//评论发送完成添加 Meta
function post_comment_updatemetas($id){
	$parentID = $_POST['comment_parent'];
	$comment = get_comment($id);
	$commentPostID = $comment -> comment_post_ID;
	$commentAuthor = $comment -> comment_author;
	$mailnoticeUnsubscribeKey = get_random_token();
	//评论 Markdown 源码
	update_comment_meta($id, "comment_content_source", $_POST['comment_content_source']);
	//评论者 Token
	set_user_token_cookie();
	update_comment_meta($id, "user_token", $_COOKIE["argon_user_token"]);
	//保存初次编辑记录
	$editHistory = array(array(
		'content' => $_POST['comment_content_source'],
		'time' => time(),
		'isfirst' => true
	));
	update_comment_meta($id, "comment_edit_history", addslashes(json_encode($editHistory, JSON_UNESCAPED_UNICODE)));
	//是否启用 Markdown
	if ($_POST['use_markdown'] == 'true' && get_option("argon_comment_allow_markdown") != "false"){
		update_comment_meta($id, "use_markdown", "true");
	}else{
		update_comment_meta($id, "use_markdown", "false");
	}
	//是否启用悄悄话模式
	if ($_POST['private_mode'] == 'true' && get_option("argon_comment_allow_privatemode") == "true"){
		update_comment_meta($id, "private_mode", $_COOKIE["argon_user_token"]);
	}else{
		update_comment_meta($id, "private_mode", "false");
	}
	if (is_comment_private_mode($parentID)){
		//如果父级评论是悄悄话模式则将当前评论可查看者的 Token 跟随父级评论者的 Token
		update_comment_meta($id, "private_mode", get_comment_meta($parentID, "private_mode", true));
	}
	if ($parentID!= 0 && !is_comment_private_mode($parentID)){
		//如果父级评论不是悄悄话模式则当前评论也不是悄悄话模式
		update_comment_meta($id, "private_mode", "false");
	}
	//是否启用邮件通知
	if ($_POST['enable_mailnotice'] == 'true' && get_option("argon_comment_allow_mailnotice") == "true"){
		update_comment_meta($id, "enable_mailnotice", "true");
		update_comment_meta($id, "mailnotice_unsubscribe_key", $mailnoticeUnsubscribeKey);
	}else{
		update_comment_meta($id, "enable_mailnotice", "false");
	}
	//向父级评论发送邮件
	if ($comment -> comment_approved == 1){
		wp_schedule_single_event(time() + 1, 'argon_async_comment_mail_notify', array($comment->comment_ID));
	}
	//保存 QQ 号
	if (get_option('argon_comment_enable_qq_avatar') == 'true'){
		if (!empty($_POST['qq'])){
			update_comment_meta($id, "qq_number", $_POST['qq']);
		}
	}
}
add_action('comment_post' , 'post_comment_updatemetas');
add_action('comment_unapproved_to_approved', 'comment_mail_notify');
add_rewrite_rule('^unsubscribe-comment-mailnotice/?(.*)$', '/wp-content/themes/argon/unsubscribe-comment-mailnotice.php$1', 'top');
//编辑评论
function user_edit_comment(){
	header('Content-Type:application/json; charset=utf-8');
	if (get_option("argon_comment_allow_editing") == "false"){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('博主关闭了编辑评论功能', 'argon')
		)));
	}
	$id = $_POST["id"];
	$content = $_POST["comment"];
	$contentSource = $content;
	if (!check_comment_token($id) && !check_login_user_same(get_comment_user_id_by_id($id))){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('您不是这条评论的作者或 Token 已过期', 'argon')
		)));
	}
	if ($_POST["comment"] == ""){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('新的评论为空', 'argon')
		)));
	}
    $content = argon_apply_comment_macros($content);
    if (get_comment_meta($id, "use_markdown", true) == "true"){
        $content = comment_markdown_parse($content);
    }
	$res = wp_update_comment(array(
		'comment_ID' => $id,
		'comment_content' => $content
	));
	if ($res == 1){
		update_comment_meta($id, "comment_content_source", $contentSource);
		update_comment_meta($id, "edited", "true");
		//保存编辑历史
		$editHistory = json_decode(get_comment_meta($id, "comment_edit_history", true));
		if (is_null($editHistory)){
			$editHistory = array();
		}
		array_push($editHistory, array(
			'content' => htmlspecialchars(stripslashes($contentSource)),
			'time' => time(),
			'isfirst' => false
		));
		update_comment_meta($id, "comment_edit_history", addslashes(json_encode($editHistory, JSON_UNESCAPED_UNICODE)));
		exit(json_encode(array(
			'status' => 'success',
			'msg' => __('编辑评论成功', 'argon'),
			'new_comment' => apply_filters('comment_text', argon_get_comment_text($id), $id),
			'new_comment_source' => htmlspecialchars(stripslashes($contentSource)),
			'can_visit_edit_history' => can_visit_comment_edit_history($id)
		)));
	}else{
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('编辑评论失败，可能原因: 与原评论相同', 'argon'),
		)));
	}
}
add_action('wp_ajax_user_edit_comment', 'user_edit_comment');
add_action('wp_ajax_nopriv_user_edit_comment', 'user_edit_comment');
//置顶评论
function pin_comment(){
	header('Content-Type:application/json; charset=utf-8');
	if (get_option("argon_enable_comment_pinning") == "false"){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('博主关闭了评论置顶功能', 'argon')
		)));
	}
	if (!current_user_can("moderate_comments")){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('您没有权限进行此操作', 'argon')
		)));
	}
	$id = $_POST["id"];
	$newPinnedStat = $_POST["pinned"] == "true";
	$origPinnedStat = get_comment_meta($id, "pinned", true) == "true";
	if ($newPinnedStat == $origPinnedStat){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => $newPinnedStat ? __('评论已经是置顶状态', 'argon') : __('评论已经是取消置顶状态', 'argon')
		)));
	}
	if (get_comment($id) -> comment_parent != 0){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('不能置顶子评论', 'argon')
		)));
	}
	if (is_comment_private_mode($id)){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('不能置顶悄悄话', 'argon')
		)));
	}
	update_comment_meta($id, "pinned", $newPinnedStat ? "true" : "false");
	exit(json_encode(array(
		'status' => 'success',
		'msg' => $newPinnedStat ? __('置顶评论成功', 'argon') : __('取消置顶成功', 'argon'),
	)));
}
add_action('wp_ajax_pin_comment', 'pin_comment');
add_action('wp_ajax_nopriv_pin_comment', 'pin_comment');
//输出评论分页页码
function get_argon_formatted_comment_paginate_links($maxPageNumbers, $extraClasses = ''){
	$args = array(
		'prev_text' => '',
		'next_text' => '',
		'before_page_number' => '',
		'after_page_number' => '',
		'show_all' => True,
		'echo' => False
	);
	$res = paginate_comments_links($args);
	//单引号转双引号 & 去除上一页和下一页按钮
	$res = preg_replace(
		'/\'/',
		'"',
		$res
	);
	$res = preg_replace(
		'/<a class="prev page-numbers" href="(.*?)">(.*?)<\/a>/',
		'',
		$res
	);
	$res = preg_replace(
		'/<a class="next page-numbers" href="(.*?)">(.*?)<\/a>/',
		'',
		$res
	);
	//寻找所有页码标签
	preg_match_all('/<(.*?)>(.*?)<\/(.*?)>/' , $res , $pages);
	$total = count($pages[0]);
	$current = 0;
	$urls = array();
	for ($i = 0; $i < $total; $i++){
		if (preg_match('/<span(.*?)>(.*?)<\/span>/' , $pages[0][$i])){
			$current = $i + 1;
		}else{
			preg_match('/<a(.*?)href="(.*?)">(.*?)<\/a>/' , $pages[0][$i] , $tmp);
			$urls[$i + 1] = $tmp[2];
		}
	}

	if ($total == 0){
		return "";
	}

	//计算页码起始
	$from = max($current - ($maxPageNumbers - 1) / 2 , 1);
	$to = min($current + $maxPageNumbers - ( $current - $from + 1 ) , $total);
	if ($to - $from + 1 < $maxPageNumbers){
		$to = min($current + ($maxPageNumbers - 1) / 2 , $total);
		$from = max($current - ( $maxPageNumbers - ( $to - $current + 1 ) ) , 1);
	}
	//生成新页码
	$html = "";
	if ($from > 1){
		$html .= '<li class="page-item"><div aria-label="First Page" class="page-link" href="' . $urls[1] . '"><i class="fa fa-angle-double-left" aria-hidden="true"></i></div></li>';
	}
	if ($current > 1){
		$html .= '<li class="page-item"><div aria-label="Previous Page" class="page-link" href="' . $urls[$current - 1] . '"><i class="fa fa-angle-left" aria-hidden="true"></i></div></li>';
	}
	for ($i = $from; $i <= $to; $i++){
		if ($current == $i){
			$html .= '<li class="page-item active"><span class="page-link" style="cursor: default;">' . $i . '</span></li>';
		}else{
			$html .= '<li class="page-item"><div class="page-link" href="' . $urls[$i] . '">' . $i . '</div></li>';
		}
	}
	if ($current < $total){
		$html .= '<li class="page-item"><div aria-label="Next Page" class="page-link" href="' . $urls[$current + 1] . '"><i class="fa fa-angle-right" aria-hidden="true"></i></div></li>';
	}
	if ($to < $total){
		$html .= '<li class="page-item"><div aria-label="Last Page" class="page-link" href="' . $urls[$total] . '"><i class="fa fa-angle-double-right" aria-hidden="true"></i></div></li>';
	}
	return '<nav id="comments_navigation" class="comments-navigation"><ul class="pagination' . $extraClasses . '">' . $html . '</ul></nav>';
}
function get_argon_formatted_comment_paginate_links_for_all_platforms(){
	return get_argon_formatted_comment_paginate_links(7) . get_argon_formatted_comment_paginate_links(5, " pagination-mobile");
}
function get_argon_comment_paginate_links_prev_url(){
	$args = array(
		'prev_text' => '',
		'next_text' => '',
		'before_page_number' => '',
		'after_page_number' => '',
		'show_all' => True,
		'echo' => False
	);
	$str = paginate_comments_links($args);
	//单引号转双引号
	$str = preg_replace(
		'/\'/',
		'"',
		$str
	);
	//获取上一页地址
	$url = "";
	preg_match(
		'/<a class="prev page-numbers" href="(.*?)">(.*?)<\/a>/',
		$str,
		$url
	);
	if (!isset($url[1])){
		return NULL;
	}
	
	if (isset($_GET['fill_first_page']) || strpos(parse_url($_SERVER['REQUEST_URI'])['path'], 'comment-page-') === false){
		$parsed_url = parse_url($url[1]);
		if (!isset($parsed_url['query'])){
			$parsed_url['query'] = 'fill_first_page=true';
		}else
			if (strpos($parsed_url['query'], 'fill_first_page=true') === false){
			$parsed_url['query'] .= '&fill_first_page=true';
		}
		return $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . '?' . $parsed_url['query'];
	}
	return $url[1];
}
//评论重排序（置顶优先）
$GLOBALS['comment_order'] = get_option('comment_order');
function argon_comment_cmp($a, $b){
	$a_pinned = get_comment_meta($a -> comment_ID, 'pinned', true);
	$b_pinned = get_comment_meta($b -> comment_ID, 'pinned', true);
	if ($a_pinned != "true"){
		$a_pinned = "false";
	}
	if ($b_pinned != "true"){
		$b_pinned = "false";
	}
	if ($a_pinned == $b_pinned){
		return ($a -> comment_date_gmt) > ($b -> comment_date_gmt);
	}else{
		if ($a_pinned == "true"){
			return ($GLOBALS['comment_order'] == 'desc');
		}else{
			return ($GLOBALS['comment_order'] != 'desc');
		}
	}
}
function argon_get_comments(){
	global $wp_query;
	/*$cpage = get_query_var('cpage') ?? 1;
	$maxiumPages = $wp_query -> max_num_pages;*/
	$args = array(
		'post__in'		 => array(get_the_ID()),
		'type'           => 'comment',
		'order'          => 'DESC',
		'orderby'        => 'comment_date_gmt',
		'status'         => 'approve'
	);
	if (is_user_logged_in()){
		$args['include_unapproved'] = array(get_current_user_id());
	} else {
		$unapproved_email = wp_get_unapproved_comment_author_email();
		if ($unapproved_email) {
			$args['include_unapproved'] = array($unapproved_email);
		}
	}

	$comment_query = new WP_Comment_Query;
	$comments = $comment_query -> query($args);
	
	if (get_option("argon_enable_comment_pinning", "false") == "true"){
		usort($comments, "argon_comment_cmp");
	}else{
		$comments = array_reverse($comments);
	}
	
	//向评论数组中填充 placeholder comments 以填满第一页
	if (get_option("argon_comment_pagination_type", "feed") == "page"){
		return $comments;
	}
	if (!isset($_GET['fill_first_page']) && strpos(parse_url($_SERVER['REQUEST_URI'])['path'], 'comment-page-') !== false){
		return $comments;
	}
	$comments_per_page = get_option('comments_per_page');
	$comments_count = 0; 
	foreach ($comments as $comment){
		if ($comment -> comment_parent == 0){
			$comments_count++;
		}
	}
	$comments_pages = ceil($comments_count / $comments_per_page);
	if ($comments_pages > 1){
		$placeholders_count = $comments_pages * $comments_per_page - $comments_count;
		while ($placeholders_count--){
			array_unshift($comments, new WP_Comment((object) array(
				"placeholder" => true
			)));
		}
	}
	return $comments;
}
//QQ Avatar 获取
function get_avatar_by_qqnumber($avatar){
	global $comment;
	if (!isset($comment) || !isset($comment -> comment_ID)){
		return $avatar;
	}
	$qqnumber = get_comment_meta($comment -> comment_ID, 'qq_number', true);
	if (!empty($qqnumber)){
		preg_match_all('/width=\'(.*?)\'/', $avatar, $preg_res);
		$size = $preg_res[1][0];
		return "<img src='https://q1.qlogo.cn/g?b=qq&s=640&nk=" . $qqnumber ."' class='avatar avatar-" . $size . " photo' width='" . $size . "' height='" . $size . "'>";
	}
	return $avatar;
}
add_filter('get_avatar', 'get_avatar_by_qqnumber');
//判断 QQ 号合法性
if (!function_exists('check_qqnumber')){
	function check_qqnumber($qqnumber){
		if (preg_match("/^[1-9][0-9]{4,10}$/", $qqnumber)){
			return true;
		} else {
			return false;
		}
	}
}
//获取顶部 Banner 背景图（用户指定或必应日图）
function get_banner_background_url(){
	$url = get_option("argon_banner_background_url");
	if ($url == "--bing--"){
		$lastUpdated = get_option("argon_bing_banner_background_last_updated_time");
		if ($lastUpdated == ""){
			$lastUpdated = 0;
		}
		$now = time();
		if ($now - $lastUpdated < 3600){
			return get_option("argon_bing_banner_background_last_updated_url");
		}else{
			$data = json_decode(@file_get_contents('https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1') , true);
			$url = "//bing.com" . $data['images'][0]['url'];
			update_option("argon_bing_banner_background_last_updated_time" , $now);
			update_option("argon_bing_banner_background_last_updated_url" , $url);
			return $url;
		}
	}else{
		return $url;
	}
}
//原生懒加载：对 <img> 标签添加 loading="lazy" 属性
function argon_lazyload($content){
	if(!is_feed() && !is_robots() && !is_home()){
		// 为没有 loading 属性的图片添加 loading="lazy"
		$content = preg_replace('/<img((?!loading=)[^>]*)>/i', '<img$1 loading="lazy">', $content);
	}
	return $content;
}
function argon_fancybox($content){
	if(!is_feed() && !is_robots() && !is_home()){
		$content = preg_replace('/<img(.*?)src=[\'"](.*?)[\'"](.*?)((\/>)|>|(<\/img>))/i',"<div class='fancybox-wrapper' data-fancybox='post-images' href='$2'>$0</div>" , $content);
	}
	return $content;
}
function the_content_filter($content){
	// 始终使用原生懒加载
	$content = argon_lazyload($content);
	if (get_option('argon_enable_fancybox') != 'false' && get_option('argon_enable_zoomify') == 'false'){
		$content = argon_fancybox($content);
	}
	global $post;
	$custom_css = get_post_meta($post -> ID, 'argon_custom_css', true);
	if (!empty($custom_css)){
		$content .= "<style>" . $custom_css . "</style>";
	}

	return $content;
}
add_filter('the_content' , 'the_content_filter',20);
//使用 CDN 加速 gravatar
function gravatar_cdn($url){
	$cdn = get_option('argon_gravatar_cdn', 'gravatar.pho.ink/avatar/');
	$cdn = str_replace("http://", "", $cdn);
	$cdn = str_replace("https://", "", $cdn);
	if (substr($cdn, -1) != '/'){
		$cdn .= "/";
	}
	$url = preg_replace("/\/\/(.*?).gravatar.com\/avatar\//", "//" . $cdn, $url);
	return $url;
}
if (get_option('argon_gravatar_cdn' , '') != ''){
	add_filter('get_avatar_url', 'gravatar_cdn');
}
function text_gravatar($url){
	$url = preg_replace("/[?&]d[^&]+/i", "" , $url);
	$url .= '&d=404';
	return $url;
}
if (get_option('argon_text_gravatar', 'false') == 'true' && !is_admin()){
	add_filter('get_avatar_url', 'text_gravatar');
}
//说说点赞
function get_shuoshuo_upvotes($ID){
	$count_key = 'upvotes';
	$count = get_post_meta($ID, $count_key, true);
	if ($count==''){
		delete_post_meta($ID, $count_key);
		add_post_meta($ID, $count_key, '0');
		$count = '0';
	}
	return number_format_i18n($count);
}
function set_shuoshuo_upvotes($ID){
	if (get_post_type($ID) != 'shuoshuo'){
		return;
	}
	$count_key = 'upvotes';
	$count = get_post_meta($ID, $count_key, true);
	if ($count==''){
		delete_post_meta($ID, $count_key);
		add_post_meta($ID, $count_key, '1');
	} else {
		update_post_meta($ID, $count_key, $count + 1);
	}
}
function upvote_shuoshuo(){
	header('Content-Type:application/json; charset=utf-8');
	$ID = $_POST["shuoshuo_id"];
	$upvotedList = isset( $_COOKIE['argon_shuoshuo_upvoted'] ) ? $_COOKIE['argon_shuoshuo_upvoted'] : '';
	if (in_array($ID, explode(',', $upvotedList))){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('该说说已被赞过', 'argon'),
			'total_upvote' => get_shuoshuo_upvotes($ID)
		)));
	}
    set_shuoshuo_upvotes($ID);
    setcookie('argon_shuoshuo_upvoted', $upvotedList . $ID . "," , array(
        'expires' => time() + 3153600000,
        'path' => '/',
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax'
    ));
	exit(json_encode(array(
		'ID' => $ID,
		'status' => 'success',
		'msg' => __('点赞成功', 'argon'),
		'total_upvote' => get_shuoshuo_upvotes($ID)
	)));
}
add_action('wp_ajax_upvote_shuoshuo' , 'upvote_shuoshuo');
add_action('wp_ajax_nopriv_upvote_shuoshuo' , 'upvote_shuoshuo');
//检测页面底部版权是否被修改
function alert_footer_copyright_changed(){ ?>
	<div class='notice notice-warning is-dismissible'>
		<p><?php _e("警告：你可能修改了 Argon 主题页脚的版权声明，Argon 主题要求你至少保留主题的 Github 链接或主题的发布文章链接。", 'argon');?></p>
	</div>
<?php }
function check_footer_copyright(){
	$footer = file_get_contents(get_theme_root() . "/" . wp_get_theme() -> template . "/footer.php");
	if ((strpos($footer, "github.com/solstice23/argon-theme") === false) && (strpos($footer, "solstice23.top") === false)){
		add_action('admin_notices', 'alert_footer_copyright_changed');
	}
}
check_footer_copyright();
//颜色计算
function rgb2hsl($R,$G,$B){
	$r = $R / 255;
	$g = $G / 255;
	$b = $B / 255;

	$var_Min = min($r, $g, $b);
	$var_Max = max($r, $g, $b);
	$del_Max = $var_Max - $var_Min;

	$L = ($var_Max + $var_Min) / 2;

	if ($del_Max == 0){
		$H = 0;
		$S = 0;
	}else{
		if ($L < 0.5){
			$S = $del_Max / ($var_Max + $var_Min);
		}else{
			$S = $del_Max / (2 - $var_Max - $var_Min);
		}

		$del_R = ((($var_Max - $r) / 6) + ($del_Max / 2)) / $del_Max;
		$del_G = ((($var_Max - $g) / 6) + ($del_Max / 2)) / $del_Max;
		$del_B = ((($var_Max - $b) / 6) + ($del_Max / 2)) / $del_Max;

		if ($r == $var_Max){
			$H = $del_B - $del_G;
		}
		else if ($g == $var_Max){
			$H = (1 / 3) + $del_R - $del_B;
		}
		else if ($b == $var_Max){
			$H = (2 / 3) + $del_G - $del_R;
		}
		if ($H < 0) $H += 1;
		if ($H > 1) $H -= 1;
	}
	return array(
		'h' => $H,//0~1
		's' => $S,
		'l' => $L,
		'H' => round($H * 360),//0~360
		'S' => round($S * 100),//0~100
		'L' => round($L * 100),//0~100
	);
}
function Hue_2_RGB($v1,$v2,$vH){
	if ($vH < 0) $vH += 1;
	if ($vH > 1) $vH -= 1;
	if ((6 * $vH) < 1) return ($v1 + ($v2 - $v1) * 6 * $vH);
	if ((2 * $vH) < 1) return $v2;
	if ((3 * $vH) < 2) return ($v1 + ($v2 - $v1) * ((2 / 3) - $vH) * 6);
	return $v1;
}
function hsl2rgb($h,$s,$l){
	if ($s == 0){
		$r = $l;
		$g = $l;
		$b = $l;
	}
	else{
		if ($l < 0.5){
			$var_2 = $l * (1 + $s);
		}
		else{
			$var_2 = ($l + $s) - ($s * $l);
		}
		$var_1 = 2 * $l - $var_2;
		$r = Hue_2_RGB($var_1, $var_2, $h + (1 / 3));
		$g = Hue_2_RGB($var_1, $var_2, $h);
		$b = Hue_2_RGB($var_1, $var_2, $h - (1 / 3));
	}
	return array(
		'R' => round($r * 255),//0~255
		'G' => round($g * 255),
		'B' => round($b * 255),
		'r' => $r,//0~1
		'g' => $g,
		'b' => $b
	);
}
function rgb2hex($r,$g,$b){
	$hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	$rh = "";
	$gh = "";
	$bh = "";
	while (strlen($rh) < 2){
		$rh = $hex[$r%16] . $rh;
		$r = floor($r / 16);
	}
	while (strlen($gh) < 2){
		$gh = $hex[$g%16] . $gh;
		$g = floor($g / 16);
	}
	while (strlen($bh) < 2){
		$bh = $hex[$b%16] . $bh;
		$b = floor($b / 16);
	}
	return "#".$rh.$gh.$bh;
}
function hexstr2rgb($hex){
	//$hex: #XXXXXX
	return array(
		'R' => hexdec(substr($hex,1,2)),//0~255
		'G' => hexdec(substr($hex,3,2)),
		'B' => hexdec(substr($hex,5,2)),
		'r' => hexdec(substr($hex,1,2)) / 255,//0~1
		'g' => hexdec(substr($hex,3,2)) / 255,
		'b' => hexdec(substr($hex,5,2)) / 255
	);
}
function rgb2str($rgb){
	return $rgb['R']. "," .$rgb['G']. "," .$rgb['B'];
}
function hex2str($hex){
	return rgb2str(hexstr2rgb($hex));
}
function rgb2gray($R,$G,$B){
	return round($R * 0.299 + $G * 0.587 + $B * 0.114);
}
function hex2gray($hex){
	$rgb_array = hexstr2rgb($hex);
	return rgb2gray($rgb_array['R'], $rgb_array['G'], $rgb_array['B']);
}
function checkHEX($hex){
	if (strlen($hex) != 7){
		return False;
	}
	if (substr($hex,0,1) != "#"){
		return False;
	}
	return True;
}
//编辑文章界面新增 Meta 编辑模块
function argon_meta_box_1(){
	wp_nonce_field("argon_meta_box_nonce_action", "argon_meta_box_nonce");
	global $post;
	?>
		<h4><?php _e("显示字数和预计阅读时间", 'argon');?></h4>
		<?php $argon_meta_hide_readingtime = get_post_meta($post->ID, "argon_hide_readingtime", true);?>
		<select name="argon_meta_hide_readingtime" id="argon_meta_hide_readingtime">
			<option value="false" <?php if ($argon_meta_hide_readingtime=='false'){echo 'selected';} ?>><?php _e("跟随全局设置", 'argon');?></option>
			<option value="true" <?php if ($argon_meta_hide_readingtime=='true'){echo 'selected';} ?>><?php _e("不显示", 'argon');?></option>
		</select>
		<p style="margin-top: 15px;"><?php _e("是否显示字数和预计阅读时间 Meta 信息", 'argon');?></p>
		<h4><?php _e("Meta 中隐藏发布时间和分类", 'argon');?></h4>
		<?php $argon_meta_simple = get_post_meta($post->ID, "argon_meta_simple", true);?>
		<select name="argon_meta_simple" id="argon_meta_simple">
			<option value="false" <?php if ($argon_meta_simple=='false'){echo 'selected';} ?>><?php _e("不隐藏", 'argon');?></option>
			<option value="true" <?php if ($argon_meta_simple=='true'){echo 'selected';} ?>><?php _e("隐藏", 'argon');?></option>
		</select>
		<p style="margin-top: 15px;"><?php _e("适合特定的页面，例如友链页面。开启后文章 Meta 的第一行只显示阅读数和评论数。", 'argon');?></p>
		<h4><?php _e("使用文章中第一张图作为头图", 'argon');?></h4>
		<?php $argon_first_image_as_thumbnail = get_post_meta($post->ID, "argon_first_image_as_thumbnail", true);?>
		<select name="argon_first_image_as_thumbnail" id="argon_first_image_as_thumbnail">
			<option value="default" <?php if ($argon_first_image_as_thumbnail=='default'){echo 'selected';} ?>><?php _e("跟随全局设置", 'argon');?></option>
			<option value="true" <?php if ($argon_first_image_as_thumbnail=='true'){echo 'selected';} ?>><?php _e("使用", 'argon');?></option>
			<option value="false" <?php if ($argon_first_image_as_thumbnail=='false'){echo 'selected';} ?>><?php _e("不使用", 'argon');?></option>
		</select>
		<h4><?php _e("显示文章过时信息", 'argon');?></h4>
		<?php $argon_show_post_outdated_info = get_post_meta($post->ID, "argon_show_post_outdated_info", true);?>
		<div style="display: flex;">
			<select name="argon_show_post_outdated_info" id="argon_show_post_outdated_info">
				<option value="default" <?php if ($argon_show_post_outdated_info=='default'){echo 'selected';} ?>><?php _e("跟随全局设置", 'argon');?></option>
				<option value="always" <?php if ($argon_show_post_outdated_info=='always'){echo 'selected';} ?>><?php _e("一直显示", 'argon');?></option>
				<option value="never" <?php if ($argon_show_post_outdated_info=='never'){echo 'selected';} ?>><?php _e("永不显示", 'argon');?></option>
			</select>
			<button id="apply_show_post_outdated_info" type="button" class="components-button is-primary" style="height: 22px; display: none;"><?php _e("应用", 'argon');?></button>
		</div>
		<p style="margin-top: 15px;"><?php _e("单独控制该文章的过时信息显示。", 'argon');?></p>
		<h4><?php _e("文末附加内容", 'argon');?></h4>
		<?php $argon_after_post = get_post_meta($post->ID, "argon_after_post", true);?>
		<textarea name="argon_after_post" id="argon_after_post" rows="3" cols="30" style="width:100%;"><?php if (!empty($argon_after_post)){echo $argon_after_post;} ?></textarea>
		<p style="margin-top: 15px;"><?php _e("给该文章设置单独的文末附加内容，留空则跟随全局，设为 <code>--none--</code> 则不显示。", 'argon');?></p>
		<h4><?php _e("自定义 CSS", 'argon');?></h4>
		<?php $argon_custom_css = get_post_meta($post->ID, "argon_custom_css", true);?>
		<textarea name="argon_custom_css" id="argon_custom_css" rows="5" cols="30" style="width:100%;"><?php if (!empty($argon_custom_css)){echo $argon_custom_css;} ?></textarea>
		<p style="margin-top: 15px;"><?php _e("给该文章添加单独的 CSS", 'argon');?></p>

		<script>$ = window.jQuery;</script>
		<script>
			function showAlert(type, message){
				if (!wp.data){
					alert(message);
					return;
				}
				wp.data.dispatch('core/notices').createNotice(
					type,
					message,
					{ type: "snackbar", isDismissible: true, }
				);
			}
			$("select[name=argon_show_post_outdated_info").change(function(){
				$("#apply_show_post_outdated_info").css("display", "");
			});
			$("#apply_show_post_outdated_info").click(function(){
				$("#apply_show_post_outdated_info").addClass("is-busy").attr("disabled", "disabled").css("opacity", "0.5");
				$("#argon_show_post_outdated_info").attr("disabled", "disabled");
				var data = {
					action: 'update_post_meta_ajax',
					argon_meta_box_nonce: $("#argon_meta_box_nonce").val(),
					post_id: <?php echo $post->ID; ?>,
					meta_key: 'argon_show_post_outdated_info',
					meta_value: $("select[name=argon_show_post_outdated_info]").val()
				};
				$.ajax({
					url: ajaxurl,
					type: 'post',
					data: data,
					success: function(response) {
						$("#apply_show_post_outdated_info").removeClass("is-busy").removeAttr("disabled").css("opacity", "1");
						$("#argon_show_post_outdated_info").removeAttr("disabled");
						if (response.status == "failed"){
							showAlert("failed", "<?php _e("应用失败", 'argon');?>");
							return;
						}
						$("#apply_show_post_outdated_info").css("display", "none");
						showAlert("success", "<?php _e("应用成功", 'argon');?>");
					},
					error: function(response) {
						$("#apply_show_post_outdated_info").removeClass("is-busy").removeAttr("disabled").css("opacity", "1");
						$("#argon_show_post_outdated_info").removeAttr("disabled");
						showAlert("failed", "<?php _e("应用失败", 'argon');?>");
					}
				});
			});
		</script>
	<?php
}
function argon_add_meta_boxes(){
	add_meta_box('argon_meta_box_1', __("文章设置", 'argon'), 'argon_meta_box_1', array('post', 'page'), 'side', 'low');
}
add_action('admin_menu', 'argon_add_meta_boxes');
function argon_save_meta_data($post_id){
	if (!isset($_POST['argon_meta_box_nonce'])){
		return $post_id;
	}
	$nonce = $_POST['argon_meta_box_nonce'];
	if (!wp_verify_nonce($nonce, 'argon_meta_box_nonce_action')){
		return $post_id;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
		return $post_id;
	}
	if ($_POST['post_type'] == 'post'){
		if (!current_user_can('edit_post', $post_id)){
			return $post_id;
		}
	}
	if ($_POST['post_type'] == 'page'){
		if (!current_user_can('edit_page', $post_id)){
			return $post_id;
		}
	}
	update_post_meta($post_id, 'argon_hide_readingtime', $_POST['argon_meta_hide_readingtime']);
	update_post_meta($post_id, 'argon_meta_simple', $_POST['argon_meta_simple']);
	update_post_meta($post_id, 'argon_first_image_as_thumbnail', $_POST['argon_first_image_as_thumbnail']);
	update_post_meta($post_id, 'argon_show_post_outdated_info', $_POST['argon_show_post_outdated_info']);
	update_post_meta($post_id, 'argon_after_post', $_POST['argon_after_post']);
	update_post_meta($post_id, 'argon_custom_css', $_POST['argon_custom_css']);
}
add_action('save_post', 'argon_save_meta_data');
function update_post_meta_ajax(){
	if (!isset($_POST['argon_meta_box_nonce'])){
		return;
	}
	$nonce = $_POST['argon_meta_box_nonce'];
	if (!wp_verify_nonce($nonce, 'argon_meta_box_nonce_action')){
		return;
	}
	header('Content-Type:application/json; charset=utf-8');
	$post_id = intval($_POST["post_id"]);
	$meta_key = $_POST["meta_key"];
	$meta_value = $_POST["meta_value"];

	if (get_post_meta($post_id, $meta_key, true) == $meta_value){
		exit(json_encode(array(
			'status' => 'success'
		)));
		return;
	}

	$result = update_post_meta($post_id, $meta_key, $meta_value);

	if ($result){
		exit(json_encode(array(
			'status' => 'success'
		)));
	}else{
		exit(json_encode(array(
			'status' => 'failed'
		)));
	}
}
add_action('wp_ajax_update_post_meta_ajax' , 'update_post_meta_ajax');
add_action('wp_ajax_nopriv_update_post_meta_ajax' , 'update_post_meta_ajax');
//首页显示说说
function argon_home_add_post_type_shuoshuo($query){
	if (is_home() && $query -> is_main_query()){
		$query -> set('post_type', array('post', 'shuoshuo'));
	}
	return $query;
}
if (get_option("argon_home_show_shuoshuo") == "true"){
	add_action('pre_get_posts', 'argon_home_add_post_type_shuoshuo');
}
//首页隐藏特定分类文章
function argon_home_hide_categories($query){
	if (is_home() && $query -> is_main_query()){
		$excludeCategories = explode(",", get_option("argon_hide_categories"));
		$excludeCategories = array_map(function($cat) { return -$cat; }, $excludeCategories);
		$query -> set('category__not_in', $excludeCategories);
		$query -> set('tag__not_in', $excludeCategories);
	}
	return $query;
}
if (get_option("argon_hide_categories") != ""){
	add_action('pre_get_posts', 'argon_home_hide_categories');
}
//文章过时信息显示
function argon_get_post_outdated_info(){
	global $post;
	$post_show_outdated_info_status = strval(get_post_meta($post -> ID, 'argon_show_post_outdated_info', true));
	if (get_option("argon_outdated_info_tip_type") == "toast"){
		$before = "<div id='post_outdate_toast' style='display:none;' data-text='";
		$after = "'></div>";
	}else{
		$before = "<div class='post-outdated-info'><i class='fa fa-info-circle' aria-hidden='true'></i>";
		$after = "</div>";
	}
	$content = get_option('argon_outdated_info_tip_content') == '' ? '本文最后更新于 %date_delta% 天前，其中的信息可能已经有所发展或是发生改变。' : get_option('argon_outdated_info_tip_content');
	$delta = get_option('argon_outdated_info_days') == '' ? (-1) : get_option('argon_outdated_info_days');
	if ($delta == -1){
		$delta = 2147483647;
	}
	$post_date_delta = floor((current_time('timestamp') - get_the_time("U")) / (60 * 60 * 24));
	$modify_date_delta = floor((current_time('timestamp') - get_the_modified_time("U")) / (60 * 60 * 24));
	if (get_option("argon_outdated_info_time_type") == "createdtime"){
		$date_delta = $post_date_delta;
	}else{
		$date_delta = $modify_date_delta;
	}
	if (($date_delta <= $delta && $post_show_outdated_info_status != 'always') || $post_show_outdated_info_status == 'never'){
		return "";
	}
	$content = str_replace("%date_delta%", $date_delta, $content);
	$content = str_replace("%modify_date_delta%", $modify_date_delta, $content);
	$content = str_replace("%post_date_delta%", $post_date_delta, $content);
	return $before . $content . $after;
}
//Gutenberg 编辑器区块
function argon_init_gutenberg_blocks() {
	wp_register_script(
		'argon-gutenberg-block-js',
		$GLOBALS['assets_path'].'/gutenberg/dist/blocks.build.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'),
		null,
		true
	);
	wp_register_style(
		'argon-gutenberg-block-backend-css',
		$GLOBALS['assets_path'].'/gutenberg/dist/blocks.editor.build.css',
		array('wp-edit-blocks'),
		filemtime(get_template_directory() . '/gutenberg/dist/blocks.editor.build.css')
	);
	register_block_type(
		'argon/argon-gutenberg-block', array(
			//'style'         => 'argon-gutenberg-block-frontend-css',
			'editor_script' => 'argon-gutenberg-block-js',
			'editor_style'  => 'argon-gutenberg-block-backend-css',
		)
	);
}
add_action('init', 'argon_init_gutenberg_blocks');
function argon_add_gutenberg_category($block_categories, $editor_context) {
	if (!empty($editor_context->post)){
		array_push(
			$block_categories,
			array(
				'slug'  => 'argon',
				'title' => 'Argon',
				'icon'  => null,
			)
		);
	}
	return $block_categories;
}
add_filter('block_categories_all', 'argon_add_gutenberg_category', 10, 2);
function argon_admin_i18n_info(){
	echo "<script>var argon_language = '" . argon_get_locate() . "';</script>";
}
add_filter('admin_head', 'argon_admin_i18n_info');
//主题文章短代码解析
function shortcode_content_preprocess($attr, $content = ""){
	if ( isset( $attr['nested'] ) ? $attr['nested'] : 'true' != 'false' ){
		return do_shortcode($content);
	}else{
		return $content;
	}	
}
add_shortcode('br','shortcode_br');
function shortcode_br($attr,$content=""){
	return "</br>";
}
add_shortcode('label','shortcode_label');
function shortcode_label($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$out = "<span class='badge";
	$color = isset( $attr['color'] ) ? $attr['color'] : 'indigo';
	switch ($color){
		case 'green':
			$out .= " badge-success";
			break;
		case 'red':
			$out .= " badge-danger";
			break;
		case 'orange':
			$out .= " badge-warning";
			break;
		case 'blue':
			$out .= " badge-info";
			break;
		case 'indigo':
		default:
			$out .= " badge-primary";
			break;
	}
	$shape = isset( $attr['shape'] ) ? $attr['shape'] : 'square';
	if ($shape=="round"){
		$out .= " badge-pill";
	}
	$out .= "'>" . $content . "</span>";
	return $out;
}
add_shortcode('progressbar','shortcode_progressbar');
function shortcode_progressbar($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$out = "<div class='progress-wrapper'><div class='progress-info'>";
	if ($content != ""){
		$out .= "<div class='progress-label'><span>" . $content . "</span></div>";
	}
	$progress = isset( $attr['progress'] ) ? $attr['progress'] : 100;
	$out .= "<div class='progress-percentage'><span>" . $progress . "%</span></div>";
	$out .= "</div><div class='progress'><div class='progress-bar";
	$color = isset( $attr['color'] ) ? $attr['color'] : 'indigo';
	switch ($color){
		case 'indigo':
			$out .= " bg-primary";
			break;
		case 'green':
			$out .= " bg-success";
			break;
		case 'red':
			$out .= " bg-danger";
			break;
		case 'orange':
			$out .= " bg-warning";
			break;
		case 'blue':
			$out .= " bg-info";
			break;
		default:
			$out .= " bg-primary";
			break;
	}
	$out .= "' style='width: " . $progress . "%;'></div></div></div>";
	return $out;
}
add_shortcode('checkbox','shortcode_checkbox');
function shortcode_checkbox($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$checked = isset( $attr['checked'] ) ? $attr['checked'] : 'false';
	$inline = isset($attr['inline']) ? $attr['checked'] : 'false';
	$out = "<div class='shortcode-todo custom-control custom-checkbox";
	if ($inline == 'true'){
		$out .= " inline";
	}
	$out .= "'>
				<input class='custom-control-input' type='checkbox'" . ($checked == 'true' ? ' checked' : '') . ">
				<label class='custom-control-label'>
					<span>" . $content . "</span>
				</label>
			</div>";
	return $out;
}
add_shortcode('alert','shortcode_alert');
function shortcode_alert($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$out = "<div class='alert";
	$color = isset( $attr['color'] ) ? $attr['color'] : 'indigo';
	switch ($color){
		case 'indigo':
			$out .= " alert-primary";
			break;
		case 'green':
			$out .= " alert-success";
			break;
		case 'red':
			$out .= " alert-danger";
			break;
		case 'orange':
			$out .= " alert-warning";
			break;
		case 'blue':
			$out .= " alert-info";
			break;
		case 'black':
			$out .= " alert-default";
			break;
		default:
			$out .= " alert-primary";
			break;
	}
	$out .= "'>";
	if (isset($attr['icon'])){
		$out .= "<span class='alert-inner--icon'><i class='fa fa-" . $attr['icon'] . "'></i></span>";
	}
	$out .= "<span class='alert-inner--text'>";
	if (isset($attr['title'])){
		$out .= "<strong>" . $attr['title'] . "</strong> ";
	}
	$out .= $content . "</span></div>";
	return $out;
}
add_shortcode('admonition','shortcode_admonition');
function shortcode_admonition($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$out = "<div class='admonition shadow-sm";
	$color = isset( $attr['color'] ) ? $attr['color'] : 'indigo';
	switch ($color){
		case 'indigo':
			$out .= " admonition-primary";
			break;
		case 'green':
			$out .= " admonition-success";
			break;
		case 'red':
			$out .= " admonition-danger";
			break;
		case 'orange':
			$out .= " admonition-warning";
			break;
		case 'blue':
			$out .= " admonition-info";
			break;
		case 'black':
			$out .= " admonition-default";
			break;
		case 'grey':
			$out .= " admonition-grey";
			break;
		default:
			$out .= " admonition-primary";
			break;
	}
	$out .= "'>";
	if (isset($attr['title'])){
		$out .= "<div class='admonition-title'>";
		if (isset($attr['icon'])){
			$out .= "<i class='fa fa-" . $attr['icon'] . "'></i> ";
		}
		$out .= $attr['title'] . "</div>";
	}
	if ($content != ''){
		$out .= "<div class='admonition-body'>" . $content . "</div>";
	}
	$out .= "</div>";
	return $out;
}
add_shortcode('collapse','shortcode_collapse_block');
add_shortcode('fold','shortcode_collapse_block');
function shortcode_collapse_block($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$collapsed = isset( $attr['collapsed'] ) ? $attr['collapsed'] : 'true';
	$show_border_left = isset( $attr['showleftborder'] ) ? $attr['showleftborder'] : 'false';
	$out = "<div " ;
	$out .= " class='collapse-block shadow-sm";
	$color = isset( $attr['color'] ) ? $attr['color'] : 'none';
	$title = isset( $attr['title'] ) ? $attr['title'] : '';
	switch ($color){
		case 'indigo':
			$out .= " collapse-block-primary";
			break;
		case 'green':
			$out .= " collapse-block-success";
			break;
		case 'red':
			$out .= " collapse-block-danger";
			break;
		case 'orange':
			$out .= " collapse-block-warning";
			break;
		case 'blue':
			$out .= " collapse-block-info";
			break;
		case 'black':
			$out .= " collapse-block-default";
			break;
		case 'grey':
			$out .= " collapse-block-grey";
			break;
		case 'none':
		default:
			$out .= " collapse-block-transparent";
			break;
	}
	if ($collapsed == 'true'){
		$out .= " collapsed";
	}
	if ($show_border_left != 'true'){
		$out .= " hide-border-left";
	}
	$out .= "'>";

	$out .= "<div class='collapse-block-title'>";
	if (isset($attr['icon'])){
		$out .= "<i class='fa fa-" . $attr['icon'] . "'></i> ";
	}
	$out .= "<span class='collapse-block-title-inner'>" . $title . "</span><i class='collapse-icon fa fa-angle-down'></i></div>";

	$out .= "<div class='collapse-block-body'";
	if ($collapsed != 'false'){
		$out .= " style='display:none;'";
	}
	$out .= ">" . $content . "</div>";
	$out .= "</div>";
	return $out;
}
add_shortcode('friendlinks','shortcode_friend_link');
function shortcode_friend_link($attr,$content=""){
	$sort = isset( $attr['sort'] ) ? $attr['sort'] : 'name';
	$order = isset( $attr['order'] ) ? $attr['order'] : 'ASC';
	$friendlinks = get_bookmarks( array(
		'orderby' => $sort ,
		'order'   => $order
	));
	$style = isset( $attr['style'] ) ? $attr['style'] : '1';
	switch ($style) {
		case '1':
			$class = "friend-links-style1";
			break;
		case '1-square':
			$class = "friend-links-style1 friend-links-style1-square";
			break;
		case '2':
			$class = "friend-links-style2";
			break;
		case '2-big':
			$class = "friend-links-style2 friend-links-style2-big";
			break;
		default:
			$class = "friend-links-style1";
			break;
	}
	$out = "<div class='friend-links " . $class . "'><div class='row'>";
	foreach ($friendlinks as $friendlink){
		$out .= "
			<div class='link mb-2 col-lg-6 col-md-6'>
				<div class='card shadow-sm friend-link-container" . ($friendlink -> link_image == "" ? " no-avatar" : "") . "'>";
		if ($friendlink -> link_image != ''){
			$out .= "
					<img src='" . $friendlink -> link_image . "' class='friend-link-avatar bg-gradient-secondary'>";
		}
		$out .= "	<div class='friend-link-content'>
						<div class='friend-link-title title text-primary'>
							<a target='_blank' href='" . esc_url($friendlink -> link_url) . "'>" . esc_html($friendlink -> link_name) . "</a>
						</div>
						<div class='friend-link-description'>" . esc_html($friendlink -> link_description) . "</div>";
		$out .= "		<div class='friend-link-links'>";
		foreach (explode("\n", $friendlink -> link_notes) as $line){
			$item = explode("|", trim($line));
			if(stripos($item[0], "fa-") !== 0){
				continue;
			}
			$out .= "<a href='" . esc_url($item[1]) . "' target='_blank'><i class='fa " . sanitize_html_class($item[0]) . "'></i></a>";
		}
		$out .= "<a href='" . esc_url($friendlink -> link_url) . "' target='_blank' style='float:right; margin-right: 10px;'><i class='fa fa-angle-right' style='font-weight: bold;'></i></a>";
		$out .= "
						</div>
					</div>
				</div>
			</div>";
	}
	$out .= "</div></div>";
	return $out;
}
add_shortcode('sfriendlinks','shortcode_friend_link_simple');
function shortcode_friend_link_simple($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$content = trim(strip_tags($content));
	$entries = explode("\n" , $content);

	$shuffle = isset( $attr['shuffle'] ) ? $attr['shuffle'] : 'false';
	if ($shuffle == "true"){
		mt_srand();
		$group_start = 0;
		foreach ($entries as $index => $value){
			$now = explode("|" , $value);
			if ($now[0] == 'category'){
				echo ($index-1).",".$group_start." | ";
				for ($i = $index - 1; $i >= $group_start; $i--){
					echo $i."#";
					$tar = mt_rand($group_start , $i);
					$tmp = $entries[$tar];
					$entries[$tar] = $entries[$i];
					$entries[$i] = $tmp;
				}
				$group_start = $index + 1;
			}
		}
		for ($i = count($entries) - 1; $i >= $group_start; $i--){
			$tar = mt_rand($group_start , $i);
			$tmp = $entries[$tar];
			$entries[$tar] = $entries[$i];
			$entries[$i] = $tmp;
		}
	}

	$row_tag_open = False;
	$out = "<div class='friend-links-simple'>";
	foreach($entries as $index => $value){
		$now = explode("|" , $value);
		if ($now[0] == 'category'){
			if ($row_tag_open == True){
				$row_tag_open = False;
				$out .= "</div>";
			}
			$out .= "<div class='friend-category-title text-black'>" . $now[1] . "</div>";
		}
		if ($now[0] == 'link'){
			if ($row_tag_open == False){
				$row_tag_open = True;
				$out .= "<div class='row'>";
			}
			$out .= "
			<div class='link mb-2 col-lg-4 col-md-6'>
				<div class='card shadow-sm'>
					<div class='d-flex'>
						<div class='friend-link-avatar'>
							<a target='_blank' href='" . $now[1] . "'>";
			if (!ctype_space($now[4]) && $now[4] != '' && isset($now[4])){
				$out .= "<img src='" . $now[4] . "' class='icon bg-gradient-secondary rounded-circle text-white' style='pointer-events: none;'>
						</img>";
			}else{
				$out .= "<div class='icon icon-shape bg-gradient-primary rounded-circle text-white'>" . mb_substr($now[2], 0, 1) . "
						</div>";
			}

			$out .= "		</a>
						</div>
						<div class='pl-3'>
							<div class='friend-link-title title text-primary'><a target='_blank' href='" . $now[1] . "'>" . $now[2] . "</a>
						</div>";
			if (!ctype_space($now[3]) && $now[3] != ''  && isset($now[3])){
				$out .= "<p class='friend-link-description'>" . $now[3] . "</p>";
			}else{
				/*$out .= "<p class='friend-link-description'>&nbsp;</p>";*/
			}
			$out .= "		<a target='_blank' href='" . $now[1] . "' class='text-primary opacity-8'>前往</a>
						</div>
					</div>
				</div>
			</div>";
		}
	}
	if ($row_tag_open == True){
		$row_tag_open = False;
		$out .= "</div>";
	}
	$out .= "</div>";
	return $out;
}
add_shortcode('timeline','shortcode_timeline');
function shortcode_timeline($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$content = trim(strip_tags($content));
	$entries = explode("\n" , $content);
	$out = "<div class='argon-timeline'>";
	foreach($entries as $index => $value){
		$now = explode("|" , $value);
		$now[0] = str_replace("/" , "</br>" , $now[0]);
		$out .= "<div class='argon-timeline-node'>
					<div class='argon-timeline-time'>" . $now[0] . "</div>
					<div class='argon-timeline-card card bg-gradient-secondary shadow-sm'>";
		if ($now[1] != ''){
			$out .= "	<div class='argon-timeline-title'>" . $now[1] . "</div>";
		}
		$out .= "		<div class='argon-timeline-content'>";
		foreach($now as $index => $value){
			if ($index < 2){
				continue;
			}
			if ($index > 2){
				$out .= "</br>";
			}
			$out .= $value;
		}
		$out .= "		</div>
					</div>
				</div>";
	}
	$out .= "</div>";
	return $out;
}
add_shortcode('hidden','shortcode_hidden');
add_shortcode('spoiler','shortcode_hidden');
function shortcode_hidden($attr,$content=""){
	$content = shortcode_content_preprocess($attr, $content);
	$out = "<span class='argon-hidden-text";
	$tip = isset( $attr['tip'] ) ? $attr['tip'] : '';
	$type = isset( $attr['type'] ) ? $attr['type'] : 'blur';
	if ($type == "background"){
		$out .= " argon-hidden-text-background";
	}else{
		$out .= " argon-hidden-text-blur";
	}
	$out .= "'";
	if ($tip != ''){
		$out .= " title='" . $tip ."'";
	}
	$out .= ">" . $content . "</span>";
	return $out;
}
add_shortcode('github','shortcode_github');
function shortcode_github($attr,$content=""){
	$github_info_card_id = mt_rand(1000000000 , 9999999999);
	$author = isset( $attr['author'] ) ? $attr['author'] : '';
	$project = isset( $attr['project'] ) ? $attr['project'] : '';
	$getdata = isset( $attr['getdata'] ) ? $attr['getdata'] : 'frontend';
	$size = isset( $attr['size'] ) ? $attr['size'] : 'full';

	$description = "";
	$stars = "";
	$forks = "";

	if ($getdata == "backend"){
		set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
			if (error_reporting() === 0) {
				return false;
			}
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		try{
			$contexts = stream_context_create(
				array(
					'http' => array(
						'method'=>"GET",
						'header'=>"User-Agent: ArgonTheme\r\n"
					)
				)
			);
			$json = file_get_contents("https://api.github.com/repos/" . $author . "/" . $project, false, $contexts);
			if (empty($json)){
				throw new Exception("");
			}
			$json = json_decode($json);
			$description = esc_html($json -> description);
			if (!empty($json -> homepage)){
				$description .= esc_html(" <a href='" . $json -> homepage . "' target='_blank' no-pjax>" . $json -> homepage . "</a>");
			}
			$stars = $json -> stargazers_count;
			$forks = $json -> forks_count;
		}catch (Exception $e){
			$getdata = "frontend";
		}
		restore_error_handler();
	}

	$out = "<div class='github-info-card github-info-card-" . $size . " card shadow-sm' data-author='" . $author . "' data-project='" . $project . "' githubinfo-card-id='" . $github_info_card_id . "' data-getdata='" . $getdata . "' data-description='" . $description . "' data-stars='" . $stars . "' data-forks='" . $forks . "'>";
	$out .= "<div class='github-info-card-header'><a href='https://github.com/' ref='nofollow' target='_blank' title='Github' no-pjax><span><i class='fa fa-github'></i>";
	if ($size != "mini"){
		$out .= " GitHub";
	}
	$out .= "</span></a></div>";
	$out .= "<div class='github-info-card-body'>
			<div class='github-info-card-name-a'>
				<a href='https://github.com/" . $author . "/" . $project . "' target='_blank' no-pjax>
					<span class='github-info-card-name'>" . $author . "/" . $project . "</span>
				</a>
				</div>
			<div class='github-info-card-description'></div>
		</div>";
	$out .= "<div class='github-info-card-bottom'>
				<span class='github-info-card-meta github-info-card-meta-stars'>
					<i class='fa fa-star'></i> <span class='github-info-card-stars'></span>
				</span>
				<span class='github-info-card-meta github-info-card-meta-forks'>
					<i class='fa fa-code-fork'></i> <span class='github-info-card-forks'></span>
				</span>
			</div>";
	$out .= "</div>";
	return $out;
}
add_shortcode('video','shortcode_video');
function shortcode_video($attr,$content=""){
	$url = isset( $attr['mp4'] ) ? $attr['mp4'] : '';
	$url = isset( $attr['url'] ) ? $attr['url'] : $url;
	$width = isset( $attr['width'] ) ? $attr['width'] : '';
	$height = isset( $attr['height'] ) ? $attr['height'] : '';
	$autoplay = isset( $attr['autoplay'] ) ? $attr['autoplay'] : 'false';
	$out = "<video";
	if ($width != ''){
		$out .= " width='" . $width . "'";
	}
	if ($height != ''){
		$out .= " height='" . $height . "'";
	}
	if ($autoplay == 'true'){
		$out .= " autoplay";
	}
	$out .= " controls>";
	$out .= "<source src='" . $url . "'>";
	$out .= "</video>";
	return $out;
}
add_shortcode('hide_reading_time','shortcode_hide_reading_time');
function shortcode_hide_reading_time($attr,$content=""){
	return "";
}
add_shortcode('post_time','shortcode_post_time');
function shortcode_post_time($attr,$content=""){
	$format = isset( $attr['format'] ) ? $attr['format'] : 'Y-n-d G:i:s';
	return get_the_time($format);
}
add_shortcode('post_modified_time','shortcode_post_modified_time');
function shortcode_post_modified_time($attr,$content=""){
	$format = isset( $attr['format'] ) ? $attr['format'] : 'Y-n-d G:i:s';
	return get_the_modified_time($format);
}
add_shortcode('noshortcode','shortcode_noshortcode');
function shortcode_noshortcode($attr,$content=""){
	return $content;
}
//Reference Footnote
add_shortcode('ref','shortcode_ref');
$post_references = array();
$post_reference_keys_first_index = array();
$post_reference_contents_first_index = array();
function argon_get_ref_html($content, $index, $subIndex){
	$index++;
	return "<sup class='reference' id='ref_" . $index . "_" . $subIndex . "' data-content='" . esc_attr($content) . "' tabindex='0'><a class='reference-link' href='#ref_" . $index . "'>[" . $index . "]</a></sup>";
}
function shortcode_ref($attr,$content=""){
	global $post_references;
	global $post_reference_keys_first_index;
	global $post_reference_contents_first_index;
	$content = preg_replace(
		'/<p>(.*?)<\/p>/is',
		'</br>$1',
		$content
	);
	$content = wp_kses($content, array(
		'a' => array(
			'href' => array(),
			'title' => array(),
			'target' => array()
		),
		'br' => array(),
		'em' => array(),
		'strong' => array(),
		'b' => array(),
		'sup' => array(),
		'sub' => array(),
		'small' => array()
	));
	if (isset($attr['id'])){
		if (isset($post_reference_keys_first_index[$attr['id']])){
			$post_references[$post_reference_keys_first_index[$attr['id']]]['count']++;
		}else{
			array_push($post_references, array('content' => $content, 'count' => 1));
			$post_reference_keys_first_index[$attr['id']] = count($post_references) - 1;
		}
		$index = $post_reference_keys_first_index[$attr['id']];
		return argon_get_ref_html($post_references[$index]['content'], $index, $post_references[$index]['count']);
	}else{
		if (isset($post_reference_contents_first_index[$content])){
			$post_references[$post_reference_contents_first_index[$content]]['count']++;
			$index = $post_reference_contents_first_index[$content];
			return argon_get_ref_html($post_references[$index]['content'], $index, $post_references[$index]['count']);
		}else{
			array_push($post_references, array('content' => $content, 'count' => 1));
			$post_reference_contents_first_index[$content] = count($post_references) - 1;
			$index = count($post_references) - 1;
			return argon_get_ref_html($post_references[$index]['content'], $index, $post_references[$index]['count']);
		}
	}
}
function get_reference_list(){
	global $post_references;
	if (count($post_references) == 0){
		return "";
	}
	$res = "<div class='reference-list-container'>";
	$res .= "<h3>" . (get_option('argon_reference_list_title') == "" ? __('参考', 'argon') : get_option('argon_reference_list_title')) . "</h3>";
	$res .= "<ol class='reference-list'>";
		foreach ($post_references as $index => $ref) {
			$res .= "<li id='ref_" . ($index + 1)  . "'><div>";
			if ($ref['count'] == 1){
				$res .= "<a class='reference-list-backlink' href='#ref_" . ($index + 1) . "_1' aria-label='back'>^</a>";
			}else{
				$res .= "<span class='reference-list-backlink'>^</span>";
				for ($i = 1, $j = 'a'; $i <= $ref['count']; $i++, $j++){
					$res .= "<sup><a class='reference-list-backlink' href='#ref_" . ($index + 1) . "_" . $i . "' aria-label='back'>" . $j . "</a></sup>";
				}
			}
			$res .= "<span>" . $ref['content'] . "</span>";
			$res .= "<div class='space' tabindex='-1'></div>";
			$res .= "</div></li>";
		}
	$res .= "</ol>";
	$res .= "</div>";
	return $res;
}
//TinyMce 按钮
function argon_tinymce_extra_buttons(){
	if(!current_user_can('edit_posts') && !current_user_can('edit_pages')){
		return;
	}
	if(get_user_option('rich_editing') == 'true'){
		add_filter('mce_external_plugins', 'argon_tinymce_add_plugin');
		add_filter('mce_buttons', 'argon_tinymce_register_button');
		add_editor_style($GLOBALS['assets_path'] . "/assets/tinymce_assets/tinymce_editor_codeblock.css");
	}
}
add_action('init', 'argon_tinymce_extra_buttons');
function argon_tinymce_register_button($buttons){
	array_push($buttons, "|", "codeblock");
	array_push($buttons, "|", "label");
	array_push($buttons, "", "checkbox");
	array_push($buttons, "", "progressbar");
	array_push($buttons, "", "alert");
	array_push($buttons, "", "admonition");
	array_push($buttons, "", "collapse");
	array_push($buttons, "", "timeline");
	array_push($buttons, "", "github");
	array_push($buttons, "", "video");
	array_push($buttons, "", "hiddentext");
	return $buttons;
}
function argon_tinymce_add_plugin($plugins){
	$plugins['codeblock'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['label'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['checkbox'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['progressbar'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['alert'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['admonition'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['collapse'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['timeline'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['github'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['video'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	$plugins['hiddentext'] = get_bloginfo('template_url') . '/assets/tinymce_assets/tinymce_btns.js';
	return $plugins;
}
//主题选项页面
function themeoptions_admin_menu(){
	/*后台管理面板侧栏添加选项*/
	add_menu_page(__("Argon 主题设置", 'argon'), __("Argon 主题选项", 'argon'), 'edit_theme_options', basename(__FILE__), 'themeoptions_page');
}
include_once(get_template_directory() . '/settings.php');
	
/*主题菜单*/
add_action('init', 'init_nav_menus');
function init_nav_menus(){
	register_nav_menus( array(
		'toolbar_menu' => __('顶部导航', 'argon'),
		'leftbar_menu' => __('左侧栏菜单', 'argon'),
		'leftbar_author_links' => __('左侧栏作者个人链接', 'argon'),
		'leftbar_friend_links' => __('左侧栏友情链接', 'argon')
	));
}

// 友情链接页面 URL 重写
add_action('init', 'argon_friend_links_rewrite');
function argon_friend_links_rewrite() {
    add_rewrite_rule('^friends/?$', 'index.php?argon_friend_links=1', 'top');
}

// 主题激活或更新时刷新重写规则
add_action('after_switch_theme', 'argon_flush_rewrite_rules');
add_action('upgrader_process_complete', 'argon_flush_rewrite_rules');
function argon_flush_rewrite_rules() {
    argon_friend_links_rewrite();
    flush_rewrite_rules();
}

add_filter('query_vars', 'argon_friend_links_query_vars');
function argon_friend_links_query_vars($vars) {
    $vars[] = 'argon_friend_links';
    return $vars;
}

add_action('template_redirect', 'argon_friend_links_template');
function argon_friend_links_template() {
    if (get_query_var('argon_friend_links')) {
        include(get_template_directory() . '/friend-links.php');
        exit;
    }
}

//隐藏 admin 管理条
//show_admin_bar(false);

/*说说*/
add_action('init', 'init_shuoshuo');
function init_shuoshuo(){
	$labels = array(
		'name' => __('说说', 'argon'),
		'singular_name' => __('说说', 'argon'),
		'add_new' => __('发表说说', 'argon'),
		'add_new_item' => __('发表说说', 'argon'),
		'edit_item' => __('编辑说说', 'argon'),
		'new_item' => __('新说说', 'argon'),
		'view_item' => __('查看说说', 'argon'),
		'search_items' => __('搜索说说', 'argon'),
		'not_found' => __('暂无说说', 'argon'),
		'not_found_in_trash' => __('没有已遗弃的说说', 'argon'),
		'parent_item_colon' => '',
		'menu_name' => __('说说', 'argon')
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'exclude_from_search' => true,
		'query_var' => true,
		'rewrite' => array(
			'slug' => 'shuoshuo',
			'with_front' => false
		),
		'capability_type' => 'post',
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => null,
		'menu_icon' => 'dashicons-format-quote',
		'supports' => array('editor', 'author', 'title', 'custom-fields', 'comments')
	);
	register_post_type('shuoshuo', $args);
}

function argon_get_search_post_type_array(){
	$search_filters_type = get_option("argon_search_filters_type", "*post,*page,shuoshuo");
	$search_filters_type = explode(',', $search_filters_type);
	if (!isset($_GET['post_type'])) {
		$default = array_filter($search_filters_type, function ($str) {	return $str[0] == '*'; });
		$default = array_map(function ($str) { return substr($str, 1) ;}, $default);
		return $default;
	}
	$search_filters_type = array_map(function ($str) { return $str[0] == '*' ? substr($str, 1) : $str; }, $search_filters_type);
	$post_type = explode(',', $_GET['post_type']);
	$arr = array();
	foreach ($search_filters_type as $type) {
		if (in_array($type, $post_type)) {
			array_push($arr, $type);
		}
	}
	if (count($arr) == 0) {
		array_push($arr, 'none');
	}
	return $arr;
}
function search_filter($query) {
	if (!$query -> is_search || is_admin()) {
		return $query;
	}
	if (get_option('argon_enable_search_filters', 'true') == 'false'){
		return $query;
	}
	$query -> set('post_type', argon_get_search_post_type_array());
	return $query;
}
add_filter('pre_get_posts', 'search_filter');

/*恢复链接管理器*/
add_filter('pre_option_link_manager_enabled', '__return_true');

/*登录界面 CSS*/
function argon_login_page_style() {
	wp_enqueue_style("argon_login_css", $GLOBALS['assets_path'] . "/login.css", null, $GLOBALS['theme_version']);
}
if (get_option('argon_enable_login_css') == 'true'){
	add_action('login_head', 'argon_login_page_style');
}

// 修复 wp_mail 的 Date 头：确保使用 WordPress 时区的本地时间
add_action('phpmailer_init', function($phpmailer) {
    // 使用 WordPress 的 current_time('U') 获取带时区偏移的时间戳
    $local_timestamp = current_time('U'); // 返回考虑 WordPress 时区的 Unix 时间戳
    $correct_date = date('D, d M Y H:i:s O', $local_timestamp);
    $phpmailer->MessageDate = $correct_date;
});

// 引入二维码库 QRCode.js
function argon_enqueue_qrcode_script() {
	if (is_single()) {
		// 使用备用机制加载QRCode
		wp_enqueue_script('resource_loader', get_template_directory_uri() . '/assets/vendor/external/resource-loader.js', array(), '1.4.0', true);
		wp_add_inline_script('resource_loader', '
			document.addEventListener("DOMContentLoaded", function() {
				if (typeof ArgonResourceLoader !== "undefined") {
					ArgonResourceLoader.smartLoad("https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js", "js")
						.then(function() {
							console.log("QRCode 加载成功");
						})
						.catch(function(error) {
							console.warn("QRCode 加载失败，已使用本地备用版本");
						});
				}
			});
		');
	}
}
add_action('wp_enqueue_scripts', 'argon_enqueue_qrcode_script');


// 获取 Git 版本信息
function argon_get_git_info() {
	$theme_dir = get_template_directory();
	$git_dir = $theme_dir . '/.git';
	$version_file = $theme_dir . '/version.json';
	
	// 优先从 version.json 读取（用于没有 .git 的服务器环境）
	if (file_exists($version_file)) {
		$version_data = json_decode(file_get_contents($version_file), true);
		if ($version_data && isset($version_data['branch']) && isset($version_data['commit'])) {
			return $version_data;
		}
	}
	
	// 检查是否存在 .git 目录
	if (!is_dir($git_dir)) {
		return false;
	}
	
	$branch = '';
	$commit = '';
	
	// 获取当前分支
	$head_file = $git_dir . '/HEAD';
	if (file_exists($head_file)) {
		$head_content = trim(file_get_contents($head_file));
		if (strpos($head_content, 'ref: ') === 0) {
			// 指向分支引用
			$ref = substr($head_content, 5);
			$branch = basename($ref);
			
			// 获取该分支的 commit hash
			$ref_file = $git_dir . '/' . $ref;
			if (file_exists($ref_file)) {
				$commit = substr(trim(file_get_contents($ref_file)), 0, 7);
			}
		} else {
			// detached HEAD，直接是 commit hash
			$branch = 'HEAD';
			$commit = substr($head_content, 0, 7);
		}
	}
	
	// 如果还没获取到 commit，尝试从 packed-refs 获取
	if (empty($commit) && !empty($branch)) {
		$packed_refs = $git_dir . '/packed-refs';
		if (file_exists($packed_refs)) {
			$refs_content = file_get_contents($packed_refs);
			if (preg_match('/([a-f0-9]{40})\s+refs\/heads\/' . preg_quote($branch, '/') . '/', $refs_content, $matches)) {
				$commit = substr($matches[1], 0, 7);
			}
		}
	}
	
	if (empty($branch) && empty($commit)) {
		return false;
	}
	
	return array(
		'branch' => $branch ?: 'unknown',
		'commit' => $commit ?: 'unknown'
	);
}


// ========== TODO 列表功能 ==========

// 获取 TODO 列表
function argon_get_todo_list() {
    $todos = get_option('argon_todo_list', array());
    if (!is_array($todos)) {
        $todos = array();
    }
    return $todos;
}

// 添加 TODO 项
function argon_ajax_add_todo() {
    // 验证权限
    if (!current_user_can('publish_posts')) {
        wp_send_json_error('无权限');
    }
    
    check_ajax_referer('argon_todo_nonce', 'nonce');
    
    $content = sanitize_text_field($_POST['content']);
    if (empty($content)) {
        wp_send_json_error('内容不能为空');
    }
    
    $todos = argon_get_todo_list();
    $new_todo = array(
        'id' => uniqid(),
        'content' => $content,
        'completed' => false,
        'created_at' => time()
    );
    
    array_unshift($todos, $new_todo);
    update_option('argon_todo_list', $todos);
    
    wp_send_json_success($new_todo);
}
add_action('wp_ajax_argon_add_todo', 'argon_ajax_add_todo');

// 完成 TODO 项（标记为完成，添加删除线）
function argon_ajax_complete_todo() {
    if (!current_user_can('publish_posts')) {
        wp_send_json_error('无权限');
    }
    
    check_ajax_referer('argon_todo_nonce', 'nonce');
    
    $id = sanitize_text_field($_POST['id']);
    $todos = argon_get_todo_list();
    
    foreach ($todos as $key => $todo) {
        if ($todo['id'] === $id) {
            $todos[$key]['completed'] = true;
            $todos[$key]['completed_at'] = time();
            break;
        }
    }
    
    update_option('argon_todo_list', $todos);
    
    wp_send_json_success();
}
add_action('wp_ajax_argon_complete_todo', 'argon_ajax_complete_todo');

// 催促作者完成 TODO
function argon_ajax_urge_todo() {
    check_ajax_referer('argon_todo_nonce', 'nonce');
    
    $captcha_result = argon_verify_captcha('todo');
    if (!$captcha_result['success']) {
        wp_send_json_error($captcha_result['error']);
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $id = sanitize_text_field($_POST['id']);
    
    // 检查该任务今天是否已被提醒过
    $task_urge_key = 'argon_todo_task_urged_' . md5($id);
    if (get_transient($task_urge_key)) {
        wp_send_json_error(__('该任务今天已被提醒过', 'argon'));
    }
    
    // 检查该 IP 一小时内是否已提醒过任务
    $ip_urge_key = 'argon_todo_ip_urged_' . md5($ip);
    if (get_transient($ip_urge_key)) {
        wp_send_json_error(__('一小时内只能提醒一次', 'argon'));
    }
    
    $todos = argon_get_todo_list();
    $todo_content = '';
    
    foreach ($todos as $todo) {
        if ($todo['id'] === $id) {
            $todo_content = $todo['content'];
            break;
        }
    }
    
    if (empty($todo_content)) {
        wp_send_json_error(__('TODO 不存在', 'argon'));
    }
    
    // 使用邮件模板系统发送
    $admin_email = get_option('admin_email');
    $vars = array(
        'todo_content' => $todo_content,
        'todo_id' => $id,
        'urge_time' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
    );
    
    argon_send_email($admin_email, 'todo_urge', $vars);
    
    // 该任务今天不能再被提醒（到明天0点）
    $tomorrow = strtotime('tomorrow');
    set_transient($task_urge_key, true, $tomorrow - time());
    // 该 IP 一小时内不能再提醒
    set_transient($ip_urge_key, true, HOUR_IN_SECONDS);
    
    // 刷新验证码
    $new_captcha = '';
    $captcha_type = get_option('argon_captcha_type', 'math');
    if (argon_is_captcha_enabled() && $captcha_type == 'math') {
        get_comment_captcha_seed(true);
        $new_captcha = get_comment_captcha();
    }
    
    wp_send_json_success(array('message' => __('已提醒作者', 'argon'), 'captcha' => $new_captcha));
}

// 检查 TODO 是否已被提醒
function argon_check_todo_urged($id) {
    $task_urge_key = 'argon_todo_task_urged_' . md5($id);
    return get_transient($task_urge_key) ? true : false;
}
add_action('wp_ajax_argon_urge_todo', 'argon_ajax_urge_todo');
add_action('wp_ajax_nopriv_argon_urge_todo', 'argon_ajax_urge_todo');

// 删除 TODO 项
function argon_ajax_delete_todo() {
    if (!current_user_can('publish_posts')) {
        wp_send_json_error('无权限');
    }
    
    check_ajax_referer('argon_todo_nonce', 'nonce');
    
    $id = sanitize_text_field($_POST['id']);
    $todos = argon_get_todo_list();
    
    foreach ($todos as $key => $todo) {
        if ($todo['id'] === $id) {
            unset($todos[$key]);
            break;
        }
    }
    
    $todos = array_values($todos);
    update_option('argon_todo_list', $todos);
    
    wp_send_json_success();
}
add_action('wp_ajax_argon_delete_todo', 'argon_ajax_delete_todo');

// ========== 多邻国连胜功能 ==========

// 获取多邻国连胜数据
function argon_get_duolingo_streak() {
    $data = argon_get_duolingo_data();
    return $data ? $data['streak'] : false;
}

// 获取多邻国完整数据（包含今日是否完成）
function argon_get_duolingo_data() {
    $username = get_option('argon_duolingo_username', '');
    if (empty($username)) {
        return false;
    }
    
    $cache_key = 'argon_duolingo_v2_' . md5($username);
    $cached = get_transient($cache_key);
    
    // 如果有缓存
    if ($cached !== false) {
        // 如果今日已完成，直接返回缓存（到第二天0点前不会变）
        if (isset($cached['today']) && $cached['today']) {
            // 检查是否还是同一天
            if (isset($cached['date']) && $cached['date'] === date('Y-m-d')) {
                return $cached;
            }
            // 已经是新的一天，需要重新请求
        } else {
            // 未完成时使用缓存（15分钟内）
            return $cached;
        }
    }
    
    $url = 'https://www.duolingo.com/2017-06-30/users?username=' . urlencode($username) . '&fields=streak,streakData%7BcurrentStreak,previousStreak%7D%7D';
    
    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        )
    ));
    
    if (is_wp_error($response)) {
        // 请求失败时返回旧缓存（如果有）
        return $cached !== false ? $cached : false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['users'][0])) {
        return $cached !== false ? $cached : false;
    }
    
    $user = $data['users'][0];
    $today = date('Y-m-d');
    
    // 获取连胜数
    $streak = max(
        isset($user['streak']) ? intval($user['streak']) : 0,
        isset($user['streakData']['currentStreak']['length']) ? intval($user['streakData']['currentStreak']['length']) : 0,
        isset($user['streakData']['previousStreak']['length']) ? intval($user['streakData']['previousStreak']['length']) : 0
    );
    
    // 判断今日是否完成（通过 currentStreak 的 endDate）
    $end_date = isset($user['streakData']['currentStreak']['endDate']) ? $user['streakData']['currentStreak']['endDate'] : '';
    $is_today_done = ($end_date === $today);
    
    $result = array(
        'streak' => $streak,
        'today' => $is_today_done,
        'date' => $today
    );
    
    // 如果今日已完成，缓存到明天0点；否则缓存15分钟
    if ($is_today_done) {
        $tomorrow = strtotime('tomorrow');
        $seconds_until_tomorrow = $tomorrow - time();
        set_transient($cache_key, $result, $seconds_until_tomorrow);
    } else {
        set_transient($cache_key, $result, 15 * MINUTE_IN_SECONDS);
    }
    
    return $result;
}

// ========== 友情链接功能 ==========

/**
 * 获取友情链接列表
 * @param string $status 状态筛选: all/approved/pending/rejected
 * @return array 按分类分组的链接数组
 */
function argon_get_friend_links($status = 'all') {
    $links = get_option('argon_friend_links', array());
    if (empty($links)) {
        return array();
    }
    
    // 自动去重（按域名）
    $seen_hosts = array();
    $unique_links = array();
    foreach ($links as $link) {
        $host = parse_url($link['url'], PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);
        if (!isset($seen_hosts[$host])) {
            $seen_hosts[$host] = true;
            $unique_links[] = $link;
        }
    }
    // 如果有重复，更新数据库
    if (count($unique_links) < count($links)) {
        update_option('argon_friend_links', $unique_links);
    }
    $links = $unique_links;
    
    // 状态筛选
    if ($status !== 'all') {
        $links = array_filter($links, function($link) use ($status) {
            $link_status = isset($link['status']) ? $link['status'] : 'approved';
            return $link_status === $status;
        });
    }
    
    // 按分类分组
    $grouped = array();
    foreach ($links as $link) {
        $category = isset($link['category']) ? $link['category'] : '';
        if (!isset($grouped[$category])) {
            $grouped[$category] = array();
        }
        $grouped[$category][] = $link;
    }
    
    return $grouped;
}

/**
 * 获取友链原始列表（不分组）
 */
function argon_get_friend_links_raw($status = 'all') {
    $links = get_option('argon_friend_links', array());
    if ($status === 'all') {
        return $links;
    }
    return array_filter($links, function($link) use ($status) {
        $link_status = isset($link['status']) ? $link['status'] : 'approved';
        return $link_status === $status;
    });
}

/**
 * 检查友链是否重复
 */
function argon_check_duplicate_link($url, $exclude_id = null) {
    $links = get_option('argon_friend_links', array());
    $new_host = parse_url($url, PHP_URL_HOST);
    $new_host = preg_replace('/^www\./', '', $new_host);
    
    foreach ($links as $link) {
        if ($exclude_id && $link['id'] === $exclude_id) continue;
        
        $existing_host = parse_url($link['url'], PHP_URL_HOST);
        $existing_host = preg_replace('/^www\./', '', $existing_host);
        
        if ($existing_host === $new_host) {
            return $link;
        }
    }
    return false;
}

/**
 * 通过代理获取网站信息（防止源站 IP 泄露）
 * 注意：由于服务器在国内，代理服务可能不可用，建议使用浏览器端获取
 */
function argon_fetch_site_info_proxy($url) {
    // 直接使用本地获取（浏览器端已处理）
    return argon_fetch_site_info($url);
}

/**
 * 自动获取网站信息
 */
function argon_fetch_site_info($url) {
    $info = array(
        'favicon' => '',
        'title' => '',
        'description' => '',
        'author_avatar' => '',
        'is_wordpress' => false,
        'accessible' => false,
        'blocked_by_waf' => false,
        'error_reason' => ''
    );
    
    // 模拟真实浏览器请求，避免被识别为爬虫
    $browser_headers = array(
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
        'Accept-Encoding' => 'gzip, deflate',
        'Cache-Control' => 'no-cache',
        'Pragma' => 'no-cache',
        'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'none',
        'Sec-Fetch-User' => '?1',
        'Upgrade-Insecure-Requests' => '1',
        'Referer' => $url
    );
    
    $response = wp_remote_get($url, array(
        'timeout' => 20,
        'sslverify' => false,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'headers' => $browser_headers,
        'redirection' => 5
    ));
    
    if (is_wp_error($response)) {
        $info['error_reason'] = $response->get_error_message();
        return $info;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // 检测 WAF 拦截（雷池、云锁、安全狗等）
    $waf_patterns = array(
        'safeline' => array('safeline', '雷池'),
        'yunsuo' => array('yunsuo', '云锁'),
        'safedog' => array('safedog', '安全狗'),
        'cloudflare' => array('cloudflare', 'cf-ray'),
        'generic' => array('access denied', 'forbidden', '访问被拒绝', '请求被拦截', 'blocked', 'waf')
    );
    
    $body_lower = strtolower($body);
    foreach ($waf_patterns as $waf_name => $patterns) {
        foreach ($patterns as $pattern) {
            if (strpos($body_lower, strtolower($pattern)) !== false && $code >= 400) {
                $info['blocked_by_waf'] = true;
                $info['error_reason'] = sprintf(__('被 WAF 拦截（%s）', 'argon'), $waf_name);
                break 2;
            }
        }
    }
    
    // 检测 403/503 等状态码
    if ($code == 403) {
        $info['blocked_by_waf'] = true;
        $info['error_reason'] = __('访问被拒绝 (403)', 'argon');
    } elseif ($code == 503) {
        $info['error_reason'] = __('服务暂时不可用 (503)', 'argon');
    }
    
    $info['accessible'] = ($code >= 200 && $code < 400);
    
    if (!$info['accessible']) {
        return $info;
    }
    
    $host = parse_url($url, PHP_URL_HOST);
    $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
    $base_url = $scheme . '://' . $host;
    
    // 检测是否为 WordPress（多种方式）
    $wp_indicators = array(
        'wp-content', 'wp-includes', 'wordpress', 'wp-json',
        'generator" content="WordPress', 'powered by WordPress'
    );
    foreach ($wp_indicators as $indicator) {
        if (stripos($body, $indicator) !== false) {
            $info['is_wordpress'] = true;
            break;
        }
    }
    
    // 如果页面检测不到，尝试访问 wp-json 端点确认
    if (!$info['is_wordpress']) {
        $wp_json_url = $base_url . '/wp-json/';
        $wp_check = wp_remote_head($wp_json_url, array(
            'timeout' => 5, 
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        if (!is_wp_error($wp_check) && wp_remote_retrieve_response_code($wp_check) == 200) {
            $info['is_wordpress'] = true;
        }
    }
    
    // 获取标题
    if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $body, $matches)) {
        $info['title'] = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }
    
    // 获取描述
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $body, $matches)) {
        $info['description'] = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\'][^>]*>/i', $body, $matches)) {
        $info['description'] = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }
    
    // 获取 favicon
    $favicon_patterns = array(
        '/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i',
        '/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\'](?:shortcut )?icon["\'][^>]*>/i',
        '/<link[^>]+rel=["\']apple-touch-icon["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i'
    );
    
    foreach ($favicon_patterns as $pattern) {
        if (preg_match($pattern, $body, $matches)) {
            $info['favicon'] = argon_normalize_url($matches[1], $scheme, $host);
            break;
        }
    }
    
    // 如果没找到 favicon，尝试默认路径
    if (empty($info['favicon'])) {
        $default_favicon = $base_url . '/favicon.ico';
        $favicon_check = wp_remote_head($default_favicon, array('timeout' => 5, 'sslverify' => false, 'user-agent' => 'Mozilla/5.0'));
        if (!is_wp_error($favicon_check) && wp_remote_retrieve_response_code($favicon_check) == 200) {
            $info['favicon'] = $default_favicon;
        }
    }
    
    // ========== 获取作者头像 ==========
    
    if ($info['is_wordpress']) {
        // WordPress 站点：通过 REST API 获取作者头像
        $api_url = rtrim($url, '/') . '/wp-json/wp/v2/users?per_page=1';
        $api_response = wp_remote_get($api_url, array(
            'timeout' => 15, 
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ));
        
        if (!is_wp_error($api_response)) {
            $api_code = wp_remote_retrieve_response_code($api_response);
            $api_body = wp_remote_retrieve_body($api_response);
            
            if ($api_code == 200) {
                $users = json_decode($api_body, true);
                if (is_array($users) && !empty($users[0]['avatar_urls'])) {
                    $avatar_urls = $users[0]['avatar_urls'];
                    // 优先取 96px
                    $avatar_url = $avatar_urls['96'] ?? $avatar_urls['48'] ?? $avatar_urls['24'] ?? '';
                    
                    if (!empty($avatar_url)) {
                        // 检查是否为默认头像（Gravatar/Weavatar/Cravatar 带 d=xxx 参数）
                        $is_default = preg_match('/[?&](d|default)=(mm|mp|identicon|monsterid|wavatar|retro|robohash|blank)/i', $avatar_url);
                        
                        if (!$is_default) {
                            // 处理协议相对 URL
                            if (strpos($avatar_url, '//') === 0) {
                                $avatar_url = $scheme . ':' . $avatar_url;
                            }
                            $info['author_avatar'] = $avatar_url;
                        }
                    }
                }
            }
        }
        
        // 如果 API 没有获取到头像，尝试从页面中提取
        if (empty($info['author_avatar'])) {
            $avatar_patterns = array(
                '/<img[^>]+class=["\'][^"\']*(?:avatar|author-avatar|blogger-avatar|profile-img)[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i',
                '/<img[^>]+src=["\']([^"\']+)["\'][^>]+class=["\'][^"\']*(?:avatar|author-avatar|blogger-avatar|profile-img)[^"\']*["\'][^>]*>/i'
            );
            foreach ($avatar_patterns as $pattern) {
                if (preg_match($pattern, $body, $matches)) {
                    $info['author_avatar'] = argon_normalize_url($matches[1], $scheme, $host);
                    break;
                }
            }
        }
        
        // 仍然没有，尝试常见头像路径
        if (empty($info['author_avatar'])) {
            $common_avatar_paths = array('/avatar.png', '/avatar.jpg', '/avatar.webp');
            foreach ($common_avatar_paths as $path) {
                $avatar_url = $base_url . $path;
                $check = wp_remote_head($avatar_url, array('timeout' => 3, 'sslverify' => false));
                if (!is_wp_error($check) && wp_remote_retrieve_response_code($check) == 200) {
                    $info['author_avatar'] = $avatar_url;
                    break;
                }
            }
        }
    } else {
        // 非 WordPress 站点：尝试多种方式获取头像
        
        // 1. 尝试获取 Open Graph 图片（og:image）
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $body, $matches)) {
            $og_image = argon_normalize_url($matches[1], $scheme, $host);
            // 检查是否为有效图片
            $img_check = wp_remote_head($og_image, array('timeout' => 5, 'sslverify' => false));
            if (!is_wp_error($img_check) && wp_remote_retrieve_response_code($img_check) == 200) {
                $info['author_avatar'] = $og_image;
            }
        }
        
        // 2. 尝试常见的头像路径
        if (empty($info['author_avatar'])) {
            $common_avatar_paths = array(
                '/avatar.png', '/avatar.jpg', '/avatar.webp',
                '/img/avatar.png', '/img/avatar.jpg',
                '/images/avatar.png', '/images/avatar.jpg',
                '/assets/avatar.png', '/assets/img/avatar.png'
            );
            foreach ($common_avatar_paths as $path) {
                $avatar_url = $base_url . $path;
                $check = wp_remote_head($avatar_url, array('timeout' => 3, 'sslverify' => false));
                if (!is_wp_error($check) && wp_remote_retrieve_response_code($check) == 200) {
                    $info['author_avatar'] = $avatar_url;
                    break;
                }
            }
        }
        
        // 3. 尝试从页面中提取作者头像（常见的 class 名）
        if (empty($info['author_avatar'])) {
            $avatar_patterns = array(
                '/<img[^>]+class=["\'][^"\']*(?:avatar|author-avatar|blogger-avatar|profile-img)[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i',
                '/<img[^>]+src=["\']([^"\']+)["\'][^>]+class=["\'][^"\']*(?:avatar|author-avatar|blogger-avatar|profile-img)[^"\']*["\'][^>]*>/i'
            );
            foreach ($avatar_patterns as $pattern) {
                if (preg_match($pattern, $body, $matches)) {
                    $info['author_avatar'] = argon_normalize_url($matches[1], $scheme, $host);
                    break;
                }
            }
        }
    }
    
    return $info;
}

/**
 * 规范化 URL（处理相对路径）
 */
function argon_normalize_url($url, $scheme, $host) {
    if (strpos($url, '//') === 0) {
        return $scheme . ':' . $url;
    } elseif (strpos($url, '/') === 0) {
        return $scheme . '://' . $host . $url;
    } elseif (strpos($url, 'http') !== 0) {
        return $scheme . '://' . $host . '/' . $url;
    }
    return $url;
}

/**
 * 去重友链数据
 */
function argon_deduplicate_friend_links() {
    $links = get_option('argon_friend_links', array());
    $seen_hosts = array();
    $unique_links = array();
    $removed = 0;
    
    foreach ($links as $link) {
        $host = parse_url($link['url'], PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);
        
        if (!isset($seen_hosts[$host])) {
            $seen_hosts[$host] = true;
            $unique_links[] = $link;
        } else {
            $removed++;
        }
    }
    
    if ($removed > 0) {
        update_option('argon_friend_links', $unique_links);
    }
    
    return $removed;
}

/**
 * 添加友情链接（带智能功能）
 */
function argon_add_friend_link($data) {
    $links = get_option('argon_friend_links', array());
    
    $url = esc_url_raw($data['url']);
    
    // 检查重复
    $duplicate = argon_check_duplicate_link($url);
    if ($duplicate) {
        return false; // 返回 false 表示重复
    }
    
    // 自动获取网站信息
    $site_info = array();
    if (empty($data['avatar']) || empty($data['description'])) {
        $site_info = argon_fetch_site_info($url);
    }
    
    $new_link = array(
        'id' => uniqid('fl_'),
        'name' => sanitize_text_field($data['name']),
        'url' => $url,
        'avatar' => !empty($data['avatar']) ? esc_url_raw($data['avatar']) : (!empty($site_info['favicon']) ? $site_info['favicon'] : (!empty($site_info['author_avatar']) ? $site_info['author_avatar'] : '')),
        'description' => !empty($data['description']) ? sanitize_text_field($data['description']) : (!empty($site_info['description']) ? mb_substr($site_info['description'], 0, 100) : ''),
        'category' => isset($data['category']) ? sanitize_text_field($data['category']) : '',
        'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
        'message' => isset($data['message']) ? sanitize_textarea_field($data['message']) : '',
        'status' => isset($data['status']) ? $data['status'] : 'approved',
        'verified' => false,
        'is_wordpress' => !empty($site_info['is_wordpress']),
        'accessible' => !empty($site_info['accessible']),
        'last_check' => time(),
        'created_at' => time()
    );
    
    $links[] = $new_link;
    update_option('argon_friend_links', $links);
    return $new_link['id'];
}

/**
 * 更新友情链接
 */
function argon_update_friend_link($id, $data) {
    $links = get_option('argon_friend_links', array());
    foreach ($links as $key => $link) {
        if ($link['id'] === $id) {
            foreach ($data as $field => $value) {
                if ($field === 'url' || $field === 'avatar') {
                    $links[$key][$field] = esc_url_raw($value);
                } elseif ($field === 'email') {
                    $links[$key][$field] = sanitize_email($value);
                } elseif ($field !== 'id' && $field !== 'created_at') {
                    $links[$key][$field] = is_bool($value) ? $value : sanitize_text_field($value);
                }
            }
            $links[$key]['updated_at'] = time();
            break;
        }
    }
    update_option('argon_friend_links', $links);
    return true;
}

/**
 * 删除友情链接
 */
function argon_delete_friend_link($id) {
    $links = get_option('argon_friend_links', array());
    foreach ($links as $key => $link) {
        if ($link['id'] === $id) {
            unset($links[$key]);
            break;
        }
    }
    update_option('argon_friend_links', array_values($links));
    return true;
}

/**
 * 获取单个友链
 */
function argon_get_friend_link($id) {
    $links = get_option('argon_friend_links', array());
    foreach ($links as $link) {
        if ($link['id'] === $id) {
            return $link;
        }
    }
    return null;
}

/**
 * 处理友链申请 V2（支持主标题/副标题）
 * 用户填写的作为主标题，自动获取的作为副标题
 */
function argon_handle_link_application_v2($post_data) {
    return argon_handle_link_application_v3($post_data);
}

/**
 * 处理友链申请 V3（服务器端获取网站信息）
 */
function argon_handle_link_application_v3($post_data) {
    // 验证 nonce
    if (!isset($post_data['argon_link_apply_nonce']) || 
        !wp_verify_nonce($post_data['argon_link_apply_nonce'], 'argon_link_apply')) {
        return array('success' => false, 'message' => __('安全验证失败，请刷新页面重试', 'argon'));
    }
    
    // 验证码检查
    if (function_exists('argon_is_captcha_enabled') && argon_is_captcha_enabled()) {
        $captcha_result = argon_verify_captcha('flink');
        if (!$captcha_result['success']) {
            return array('success' => false, 'message' => $captcha_result['error']);
        }
    }
    
    $url = isset($post_data['apply_url']) ? trim($post_data['apply_url']) : '';
    
    if (empty($url)) {
        return array('success' => false, 'message' => __('网站地址为必填项', 'argon'));
    }
    
    // 自动补全 URL 协议
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }
    
    // 检查 URL 格式
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return array('success' => false, 'message' => __('请输入有效的网站地址', 'argon'));
    }
    
    // 检查是否重复
    $duplicate = argon_check_duplicate_link($url);
    if ($duplicate) {
        $status_text = ($duplicate['status'] === 'pending') ? __('正在审核中', 'argon') : __('已在友链列表中', 'argon');
        return array('success' => false, 'message' => sprintf(__('该网站（%s）%s', 'argon'), $duplicate['name'], $status_text));
    }
    
    // 服务器端获取网站信息
    $site_info = argon_fetch_site_info($url);
    
    // 获取用户填写的内容
    $user_name = isset($post_data['apply_name']) ? trim($post_data['apply_name']) : '';
    $user_avatar = isset($post_data['apply_avatar']) ? trim($post_data['apply_avatar']) : '';
    $user_desc = isset($post_data['apply_description']) ? trim($post_data['apply_description']) : '';
    
    // 确定最终使用的值
    $final_name = !empty($user_name) ? $user_name : $site_info['title'];
    $display_name = '';
    
    // 如果用户填了名称，且自动获取的标题与用户填的相似度低，则标题作为副标题
    if (!empty($user_name) && !empty($site_info['title'])) {
        $similarity = argon_string_similarity($user_name, $site_info['title']);
        if ($similarity < 0.6) { // 相似度低于60%时显示副标题
            $display_name = $site_info['title'];
        }
    }
    
    // 头像优先级：用户填写 > 作者头像 > favicon
    $final_avatar = $user_avatar;
    if (empty($final_avatar) && !empty($site_info['author_avatar'])) {
        $final_avatar = $site_info['author_avatar'];
    }
    if (empty($final_avatar) && !empty($site_info['favicon'])) {
        $final_avatar = $site_info['favicon'];
    }
    
    // 保存用户是否自定义了头像（用于后续更新时判断）
    $user_custom_avatar = !empty($user_avatar);
    
    // 描述
    $final_desc = !empty($user_desc) ? $user_desc : '';
    $auto_description = $site_info['description'];
    
    if (empty($final_name)) {
        return array('success' => false, 'message' => __('无法获取网站标题，请手动填写网站名称', 'argon'));
    }
    
    $accessible = $site_info['accessible'];
    
    // 获取申请者填写的友链页面
    $links_page = isset($post_data['apply_links_page']) ? esc_url_raw($post_data['apply_links_page']) : '';
    
    // 如果填写了友链页面，检测是否已添加本站链接
    $has_backlink = null;
    $auto_approved = false;
    $status = 'pending';
    
    if (!empty($links_page)) {
        $has_backlink = argon_check_backlink($links_page);
        if ($has_backlink) {
            $status = 'approved';
            $auto_approved = true;
        }
    }
    
    // 添加友链
    $links = get_option('argon_friend_links', array());
    $new_link = array(
        'id' => uniqid('fl_'),
        'name' => sanitize_text_field($final_name),
        'display_name' => sanitize_text_field($display_name),
        'url' => esc_url_raw($url),
        'links_page' => $links_page,
        'avatar' => $final_avatar,
        'user_avatar' => $user_custom_avatar,
        'author_avatar' => $site_info['author_avatar'],
        'favicon' => $site_info['favicon'],
        'description' => sanitize_text_field($final_desc),
        'auto_description' => sanitize_text_field($auto_description),
        'category' => '',
        'email' => isset($post_data['apply_email']) ? sanitize_email($post_data['apply_email']) : '',
        'status' => $status,
        'has_backlink' => $has_backlink,
        'auto_approved' => $auto_approved,
        'accessible' => $accessible,
        'blocked_by_waf' => $site_info['blocked_by_waf'],
        'is_wordpress' => $site_info['is_wordpress'],
        'last_check' => time(),
        'created_at' => time()
    );
    
    $links[] = $new_link;
    update_option('argon_friend_links', $links);
    
    if ($auto_approved) {
        return array('success' => true, 'message' => __('检测到您已添加本站链接，友链已自动通过！', 'argon'));
    }
    
    // 发送通知邮件给管理员
    if (function_exists('argon_notify_admin_new_link_application')) {
        argon_notify_admin_new_link_application($new_link['id']);
    }
    
    return array('success' => true, 'message' => __('申请已提交，请等待审核。', 'argon'));
}

/**
 * 计算两个字符串的相似度
 */
function argon_string_similarity($str1, $str2) {
    $str1 = mb_strtolower(trim($str1));
    $str2 = mb_strtolower(trim($str2));
    
    if ($str1 === $str2) return 1.0;
    if (empty($str1) || empty($str2)) return 0.0;
    
    // 如果一个包含另一个
    if (mb_strpos($str1, $str2) !== false || mb_strpos($str2, $str1) !== false) {
        return 0.8;
    }
    
    // 使用 similar_text 计算相似度
    similar_text($str1, $str2, $percent);
    return $percent / 100;
}

/**
 * 检测网站是否可访问
 */
function argon_check_site_accessible($url) {
    $response = wp_remote_head($url, array(
        'timeout' => 10,
        'sslverify' => false,
        'user-agent' => 'Mozilla/5.0 (compatible; Argon Friend Link Checker)'
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    return ($code >= 200 && $code < 400);
}

/**
 * 处理友链申请（旧版本，保留兼容）
 */
function argon_handle_link_application($post_data) {
    return argon_handle_link_application_v2($post_data);
}

/**
 * 检查指定页面是否有本站链接
 * 
 * @param string $links_page 对方的友链页面地址
 * @return bool 是否找到本站链接
 */
function argon_check_backlink($links_page) {
    if (empty($links_page)) {
        return null;
    }
    
    $site_url = home_url();
    $site_host = parse_url($site_url, PHP_URL_HOST);
    $site_host_nowww = preg_replace('/^www\./i', '', $site_host);
    
    $response = wp_remote_get($links_page, array(
        'timeout' => 15,
        'sslverify' => false,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        return false;
    }
    
    // 提取所有 <a> 标签的 href，检查是否指向本站
    if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $body, $matches)) {
        foreach ($matches[1] as $href) {
            $href_host = parse_url($href, PHP_URL_HOST);
            if ($href_host) {
                $href_host_nowww = preg_replace('/^www\./i', '', $href_host);
                if (strcasecmp($href_host_nowww, $site_host_nowww) === 0) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * 验证所有友链的互链状态
 */
function argon_verify_all_friend_links() {
    $links = argon_get_friend_links_raw('approved');
    $results = array();
    
    foreach ($links as $link) {
        $has_backlink = argon_check_backlink($link['url']);
        argon_update_friend_link($link['id'], array(
            'verified' => $has_backlink,
            'last_check' => time()
        ));
        $results[$link['id']] = $has_backlink;
    }
    
    return $results;
}

/**
 * 通知管理员有新的友链申请
 */
function argon_notify_admin_new_link_application($link_id) {
    $link = argon_get_friend_link($link_id);
    if (!$link) return;
    
    $admin_email = get_option('admin_email');
    $subject = sprintf(__('[%s] 新的友链申请', 'argon'), get_bloginfo('name'));
    
    $message = sprintf(__("收到新的友链申请：\n\n网站名称：%s\n网站地址：%s\n描述：%s\n邮箱：%s\n留言：%s\n\n请前往后台审核。", 'argon'),
        $link['name'],
        $link['url'],
        $link['description'],
        $link['email'],
        $link['message']
    );
    
    wp_mail($admin_email, $subject, $message);
}

/**
 * AJAX: 管理友情链接
 */
function argon_ajax_manage_friend_link() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
    }
    if (!wp_verify_nonce($_POST['nonce'], 'argon_friend_link_nonce')) {
        wp_send_json_error('安全验证失败');
    }
    
    $action = isset($_POST['link_action']) ? $_POST['link_action'] : '';
    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    
    switch ($action) {
        case 'add':
            $link_id = argon_add_friend_link(array(
                'name' => $_POST['name'],
                'url' => $_POST['url'],
                'avatar' => isset($_POST['avatar']) ? $_POST['avatar'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'category' => isset($_POST['category']) ? $_POST['category'] : '',
                'status' => 'approved'
            ));
            wp_send_json_success(array('id' => $link_id));
            break;
            
        case 'update':
            if (empty($id)) wp_send_json_error('ID 不能为空');
            $update_data = array();
            foreach (array('name', 'url', 'avatar', 'description', 'category', 'status') as $field) {
                if (isset($_POST[$field])) {
                    $update_data[$field] = $_POST[$field];
                }
            }
            argon_update_friend_link($id, $update_data);
            wp_send_json_success();
            break;
            
        case 'delete':
            if (empty($id)) wp_send_json_error('ID 不能为空');
            argon_delete_friend_link($id);
            wp_send_json_success();
            break;
            
        case 'approve':
            if (empty($id)) wp_send_json_error('ID 不能为空');
            argon_update_friend_link($id, array('status' => 'approved'));
            // 发送通知邮件
            $link = argon_get_friend_link($id);
            if ($link && !empty($link['email'])) {
                $subject = sprintf(__('[%s] 您的友链申请已通过', 'argon'), get_bloginfo('name'));
                $message = sprintf(__("您好！\n\n您申请的友链已通过审核。\n\n网站名称：%s\n网站地址：%s\n\n感谢您的支持！", 'argon'),
                    $link['name'], $link['url']);
                wp_mail($link['email'], $subject, $message);
            }
            wp_send_json_success();
            break;
            
        case 'reject':
            if (empty($id)) wp_send_json_error('ID 不能为空');
            argon_update_friend_link($id, array('status' => 'rejected'));
            wp_send_json_success();
            break;
            
        case 'verify':
            if (empty($id)) {
                // 验证所有
                $results = argon_verify_all_friend_links();
                wp_send_json_success(array('results' => $results));
            } else {
                $link = argon_get_friend_link($id);
                if (!$link) wp_send_json_error('链接不存在');
                $has_backlink = argon_check_backlink($link['url']);
                argon_update_friend_link($id, array('verified' => $has_backlink, 'last_check' => time()));
                wp_send_json_success(array('verified' => $has_backlink));
            }
            break;
        
        case 'check_access':
            $results = argon_check_all_links_accessibility();
            wp_send_json_success(array('results' => $results));
            break;
            
        default:
            wp_send_json_error('未知操作');
    }
}
add_action('wp_ajax_argon_manage_friend_link', 'argon_ajax_manage_friend_link');

// 兼容旧的 AJAX 接口
add_action('wp_ajax_argon_add_friend_link', function() {
    $_POST['link_action'] = 'add';
    argon_ajax_manage_friend_link();
});
add_action('wp_ajax_argon_delete_friend_link', function() {
    $_POST['link_action'] = 'delete';
    argon_ajax_manage_friend_link();
});

// ========== 友链调试和高级功能 ==========

/**
 * 修复旧数据状态
 */
add_action('wp_ajax_argon_debug_fix_link_status', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('权限不足');
    if (!wp_verify_nonce($_POST['nonce'], 'argon_debug')) wp_send_json_error('安全验证失败');
    
    $links = get_option('argon_friend_links', array());
    $fixed = 0;
    
    foreach ($links as $key => $link) {
        if (!isset($link['status']) || empty($link['status'])) {
            $links[$key]['status'] = 'approved';
            $fixed++;
        }
        if (!isset($link['id']) || empty($link['id'])) {
            $links[$key]['id'] = uniqid('fl_');
            $fixed++;
        }
    }
    
    update_option('argon_friend_links', $links);
    wp_send_json_success(array('message' => "已修复 {$fixed} 条数据"));
});

/**
 * 添加测试友链
 */
add_action('wp_ajax_argon_debug_add_test_link', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('权限不足');
    if (!wp_verify_nonce($_POST['nonce'], 'argon_debug')) wp_send_json_error('安全验证失败');
    
    argon_add_friend_link(array(
        'name' => '测试友链 ' . date('H:i:s'),
        'url' => 'https://example.com',
        'avatar' => '',
        'description' => '这是一个测试友链',
        'category' => '测试',
        'status' => 'approved'
    ));
    
    wp_send_json_success();
});

/**
 * 清空所有友链
 */
add_action('wp_ajax_argon_debug_clear_links', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('权限不足');
    if (!wp_verify_nonce($_POST['nonce'], 'argon_debug')) wp_send_json_error('安全验证失败');
    
    update_option('argon_friend_links', array());
    wp_send_json_success();
});

/**
 * 去重友链
 */
add_action('wp_ajax_argon_debug_dedupe_links', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('权限不足');
    if (!wp_verify_nonce($_POST['nonce'], 'argon_debug')) wp_send_json_error('安全验证失败');
    
    $removed = argon_deduplicate_friend_links();
    wp_send_json_success(array('message' => sprintf('已移除 %d 条重复友链', $removed)));
});

/**
 * 导入友链
 */
add_action('wp_ajax_argon_debug_import_links', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('权限不足');
    if (!wp_verify_nonce($_POST['nonce'], 'argon_debug')) wp_send_json_error('安全验证失败');
    
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    $merge = isset($_POST['merge']) && $_POST['merge'] === '1';
    
    $import_links = json_decode(stripslashes($data), true);
    if (!is_array($import_links)) {
        wp_send_json_error('JSON 格式错误');
    }
    
    // 确保每条数据都有必要字段
    foreach ($import_links as $key => $link) {
        if (!isset($link['id'])) $import_links[$key]['id'] = uniqid('fl_');
        if (!isset($link['status'])) $import_links[$key]['status'] = 'approved';
        if (!isset($link['created_at'])) $import_links[$key]['created_at'] = time();
    }
    
    if ($merge) {
        $existing = get_option('argon_friend_links', array());
        $import_links = array_merge($existing, $import_links);
    }
    
    update_option('argon_friend_links', $import_links);
    wp_send_json_success(array('message' => '已导入 ' . count($import_links) . ' 条友链'));
});

/**
 * 检查友链是否可访问（失效检测）
 */
function argon_check_link_accessible($url) {
    $response = wp_remote_head($url, array(
        'timeout' => 10,
        'sslverify' => false,
        'redirection' => 3,
        'user-agent' => 'Mozilla/5.0 (compatible; Argon Link Checker)'
    ));
    
    if (is_wp_error($response)) {
        return array('accessible' => false, 'error' => $response->get_error_message());
    }
    
    $code = wp_remote_retrieve_response_code($response);
    return array(
        'accessible' => ($code >= 200 && $code < 400),
        'status_code' => $code
    );
}

/**
 * 检查所有友链的可访问性
 */
function argon_check_all_links_accessibility() {
    $links = argon_get_friend_links_raw('approved');
    $results = array();
    
    foreach ($links as $link) {
        $check = argon_check_link_accessible($link['url']);
        argon_update_friend_link($link['id'], array(
            'accessible' => $check['accessible'],
            'last_access_check' => time(),
            'access_status_code' => isset($check['status_code']) ? $check['status_code'] : 0
        ));
        $results[$link['id']] = $check;
        
        // 每次请求后随机延迟 2-5 秒，避免频繁访问
        sleep(rand(2, 5));
    }
    
    return $results;
}

/**
 * 定时检查友链（可通过 WP Cron 调用）
 * 每次只检查部分友链，分散压力
 */
function argon_scheduled_link_check() {
    // 每天只更新约 1/3 的友链，分散到多天完成全部更新
    argon_check_partial_links();
}

/**
 * 分批检查友链，每次只检查部分
 */
function argon_check_partial_links() {
    $links = get_option('argon_friend_links', array());
    $approved_links = array_filter($links, function($l) { return $l['status'] === 'approved'; });
    
    if (empty($approved_links)) return;
    
    // 按上次检查时间排序，优先检查最久未检查的
    usort($approved_links, function($a, $b) {
        $a_time = isset($a['last_info_update']) ? $a['last_info_update'] : 0;
        $b_time = isset($b['last_info_update']) ? $b['last_info_update'] : 0;
        return $a_time - $b_time;
    });
    
    // 每次最多检查 5 个
    $to_check = array_slice($approved_links, 0, 5);
    
    foreach ($to_check as $link) {
        // 随机延迟 3-10 秒
        sleep(rand(3, 10));
        
        $site_info = argon_fetch_site_info($link['url']);
        
        $update_data = array(
            'last_info_update' => time(),
            'accessible' => $site_info['accessible'],
            'blocked_by_waf' => $site_info['blocked_by_waf'],
            'error_reason' => $site_info['error_reason']
        );
        
        if ($site_info['accessible']) {
            // 更新 favicon
            if (!empty($site_info['favicon'])) {
                $update_data['auto_favicon'] = $site_info['favicon'];
                if (empty($link['user_avatar'])) {
                    $update_data['avatar'] = $site_info['favicon'];
                }
            }
            
            // 更新作者头像
            if (!empty($site_info['author_avatar'])) {
                $update_data['author_avatar'] = $site_info['author_avatar'];
            }
            
            // 更新描述
            if (!empty($site_info['description'])) {
                $update_data['auto_description'] = $site_info['description'];
            }
            
            // 更新标题（副标题）
            if (!empty($site_info['title']) && !empty($link['name'])) {
                $similarity = argon_string_similarity($link['name'], $site_info['title']);
                if ($similarity < 0.6) {
                    $update_data['display_name'] = $site_info['title'];
                }
            }
            
            // 检查回链
            $update_data['has_backlink'] = argon_check_backlink($link['url']);
        }
        
        argon_update_friend_link($link['id'], $update_data);
    }
}

// 注册定时任务
if (!wp_next_scheduled('argon_daily_link_check')) {
    wp_schedule_event(time(), 'daily', 'argon_daily_link_check');
}
add_action('argon_daily_link_check', 'argon_scheduled_link_check');
