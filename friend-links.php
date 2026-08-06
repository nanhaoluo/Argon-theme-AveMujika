<?php
/**
 * 友情链接页面
 * @package Argon
 */
$argon_flink_ajax_buffer_level = null;
if (isset($_POST['flink_action'])) {
    $argon_flink_ajax_buffer_level = ob_get_level();
    ob_start();
}

$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) $wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
require_once($wp_load_path);

if (!function_exists('argon_flink_send_json_response')) {
    function argon_flink_send_json_response($response, $status_code = 200) {
        global $argon_flink_ajax_buffer_level;

        $unexpected_output = '';
        if ($argon_flink_ajax_buffer_level !== null) {
            while (ob_get_level() > $argon_flink_ajax_buffer_level) {
                $unexpected_output = ob_get_clean() . $unexpected_output;
            }
        }

        if ($unexpected_output !== '') {
            error_log('Argon friend links AJAX unexpected output: ' . trim(wp_strip_all_tags($unexpected_output)));
        }

        if (!headers_sent()) {
            status_header($status_code);
            header('Content-Type: application/json; charset=' . get_option('blog_charset', 'UTF-8'), true);
            nocache_headers();
        }

        echo wp_json_encode($response);
        exit;
    }
}

if (function_exists('argon_deduplicate_friend_links')) argon_deduplicate_friend_links();

// 权限检查
$is_admin = current_user_can('manage_options');

// AJAX 处理友链管理
if (isset($_POST['flink_action'])) {
    $response = array('success' => false, 'message' => '');
    $action = sanitize_text_field(wp_unslash($_POST['flink_action']));
    
    // 以下操作需要管理员权限
    if (!$is_admin) {
        argon_flink_send_json_response(array('success' => false, 'message' => '权限不足'), 403);
    }
    
    if (!isset($_POST['flink_nonce']) || !wp_verify_nonce($_POST['flink_nonce'], 'argon_flink_manage')) {
        argon_flink_send_json_response(array('success' => false, 'message' => '安全验证失败'), 403);
    }
    
    switch ($action) {
        case 'add':
            $id = argon_add_friend_link(array(
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'auto_name' => isset($_POST['auto_name']) && $_POST['auto_name'] === '1',
                'name_source' => isset($_POST['name_source']) ? $_POST['name_source'] : 'admin',
                'display_name' => isset($_POST['display_name']) ? $_POST['display_name'] : '',
                'user_display_name' => isset($_POST['user_display_name']) && $_POST['user_display_name'] === '1',
                'display_name_source' => isset($_POST['display_name_source']) ? $_POST['display_name_source'] : 'admin',
                'url' => isset($_POST['url']) ? $_POST['url'] : '',
                'browser_detected_url' => isset($_POST['browser_detected_url']) ? $_POST['browser_detected_url'] : '',
                'links_page' => isset($_POST['links_page']) ? $_POST['links_page'] : '',
                'links_page_source' => isset($_POST['links_page_source']) ? $_POST['links_page_source'] : 'admin',
                'avatar' => isset($_POST['avatar']) ? $_POST['avatar'] : '',
                'user_avatar' => isset($_POST['user_avatar']) && $_POST['user_avatar'] === '1',
                'avatar_source' => isset($_POST['avatar_source']) ? $_POST['avatar_source'] : 'admin',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'auto_description' => isset($_POST['auto_description']) ? $_POST['auto_description'] : '',
                'description_source' => isset($_POST['description_source']) ? $_POST['description_source'] : 'admin',
                'category' => isset($_POST['category']) ? $_POST['category'] : '',
                'status' => 'approved'
            ));
            $response = array(
                'success' => $id !== false,
                'id' => $id,
                'message' => $id !== false ? __('已添加友链', 'argon') : __('添加失败，可能已存在相同域名的友链', 'argon')
            );
            break;
            
        case 'delete':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            if (empty($id)) {
                $response = array('success' => false, 'message' => __('友链 ID 不能为空', 'argon'));
                break;
            }
            $existing_link = argon_get_friend_link($id);
            if (!$existing_link) {
                $response = array('success' => false, 'message' => __('友链不存在或已被删除，请刷新页面后重试', 'argon'));
                break;
            }
            $deleted = argon_delete_friend_link($id);
            $response = array('success' => $deleted, 'message' => $deleted ? __('已删除友链', 'argon') : __('友链不存在', 'argon'));
            break;
            
        case 'approve':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $result = argon_update_friend_link($id, array('status' => 'approved'));
            if ($result) {
                $link = argon_get_friend_link($id);
                if ($link && !empty($link['email'])) {
                    $subject = sprintf('[%s] 您的友链申请已通过', get_bloginfo('name'));
                    $message = sprintf("您好！\n\n您申请的友链已通过审核。\n\n网站名称：%s\n网站地址：%s\n\n感谢您的支持！", $link['name'], $link['url']);
                    wp_mail($link['email'], $subject, $message);
                }
            }
            $response = array('success' => $result);
            break;
            
        case 'reject':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $response = array('success' => argon_delete_friend_link($id));
            break;
            
        case 'update':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            if (empty($id)) {
                $response = array('success' => false, 'message' => __('友链 ID 不能为空', 'argon'));
                break;
            }
            $existing_link = argon_get_friend_link($id);
            if (!$existing_link) {
                $response = array('success' => false, 'message' => __('友链不存在或已被删除，请刷新页面后重试', 'argon'));
                break;
            }
            $data = array();
            foreach (array('name', 'display_name', 'url', 'links_page', 'avatar', 'description', 'auto_description', 'category', 'hide_no_backlink', 'auto_approved') as $f) {
                if (isset($_POST[$f])) $data[$f] = $_POST[$f];
            }
            foreach (array('name_source', 'display_name_source', 'links_page_source', 'avatar_source', 'description_source') as $source_field) {
                if (isset($_POST[$source_field])) {
                    $data[$source_field] = argon_friend_link_normalize_source($_POST[$source_field], 'admin');
                }
            }
            if (isset($_POST['user_avatar'])) {
                $data['user_avatar'] = $_POST['user_avatar'] === '1';
            } elseif (array_key_exists('avatar', $data)) {
                $avatar = trim((string)$data['avatar']);
                $existing_avatar = isset($existing_link['avatar']) ? trim((string)$existing_link['avatar']) : '';
                $data['user_avatar'] = $avatar !== '' && ($avatar !== $existing_avatar || !empty($existing_link['user_avatar']));
            }
            if (array_key_exists('display_name', $data)) {
                if (isset($_POST['user_display_name'])) {
                    $data['user_display_name'] = $_POST['user_display_name'] === '1';
                } else {
                    $display_name = trim((string)$data['display_name']);
                    $existing_display_name = isset($existing_link['display_name']) ? trim((string)$existing_link['display_name']) : '';
                    $data['user_display_name'] = $display_name !== '' && ($display_name !== $existing_display_name || !empty($existing_link['user_display_name']));
                }
            }
            if (isset($_POST['auto_name'])) $data['auto_name'] = $_POST['auto_name'] === '1';
            if (empty($data)) {
                $response = array('success' => true, 'message' => __('没有需要保存的变化', 'argon'));
                break;
            }
            $worker_refresh_required = false;
            foreach (array('url', 'links_page') as $worker_target_field) {
                if (!array_key_exists($worker_target_field, $data)) continue;
                $new_worker_target = esc_url_raw(wp_unslash($data[$worker_target_field]));
                $old_worker_target = isset($existing_link[$worker_target_field]) ? (string)$existing_link[$worker_target_field] : '';
                if ($new_worker_target !== $old_worker_target) {
                    $worker_refresh_required = true;
                    break;
                }
            }
            $updated = argon_update_friend_link($id, $data);
            $worker_dispatched = null;
            if ($updated && $worker_refresh_required && function_exists('argon_friend_link_worker_dispatch_async')) {
                $worker_dispatched = !is_wp_error(argon_friend_link_worker_dispatch_async($id));
            }
            $response = array(
                'success' => $updated,
                'worker_refresh_required' => $updated && $worker_refresh_required,
                'worker_dispatched' => $worker_dispatched,
                'message' => $updated ? __('已保存友链信息', 'argon') : __('保存失败，请刷新页面后重试', 'argon')
            );
            break;
            
        case 'get':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $link = argon_get_friend_link($id);
            $should_sync_worker = isset($_POST['sync_worker_result']) && $_POST['sync_worker_result'] === '1';
            if (
                $link
                && $should_sync_worker
                && function_exists('argon_friend_link_worker_sync_result')
                && in_array(isset($link['worker_status']) ? $link['worker_status'] : '', array('dispatching', 'submitted'), true)
            ) {
                argon_friend_link_worker_sync_result($id);
                $link = argon_get_friend_link($id);
            }
            $response = $link ? array('success' => true, 'data' => $link) : array('success' => false);
            break;
            
        case 'dispatch_worker':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            if ($id === '' || !argon_get_friend_link($id)) {
                $response = array('success' => false, 'message' => __('友链不存在', 'argon'));
                break;
            }
            $dispatch = function_exists('argon_friend_link_worker_dispatch_async')
                ? argon_friend_link_worker_dispatch_async($id)
                : new WP_Error('worker_bridge_missing', __('Worker 桥接未加载', 'argon'));
            $response = is_wp_error($dispatch)
                ? array('success' => false, 'message' => $dispatch->get_error_message())
                : array('success' => true, 'message' => __('已提交 Cloudflare Worker 异步检测', 'argon'));
            break;

        case 'browser_result':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $browser_result = function_exists('argon_friend_link_apply_browser_result')
                ? argon_friend_link_apply_browser_result($id, $_POST)
                : new WP_Error('browser_bridge_missing', __('浏览器检测结果桥接未加载', 'argon'));
            $response = is_wp_error($browser_result)
                ? array('success' => false, 'message' => $browser_result->get_error_message())
                : $browser_result;
            break;

        case 'browser_probe':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $probe_result = function_exists('argon_friend_link_worker_probe_and_apply')
                ? argon_friend_link_worker_probe_and_apply($id, $_POST)
                : new WP_Error('worker_probe_missing', __('Worker 同步探测桥接未加载', 'argon'));
            $response = is_wp_error($probe_result)
                ? array('success' => false, 'message' => $probe_result->get_error_message())
                : $probe_result;
            break;

        case 'browser_probe_preview':
            $preview_payload = $_POST;
            $preview_payload['mode'] = 'info';
            $preview = function_exists('argon_friend_link_worker_probe')
                ? argon_friend_link_worker_probe($preview_payload)
                : new WP_Error('worker_probe_missing', __('Worker 同步探测桥接未加载', 'argon'));
            if (is_wp_error($preview)) {
                $response = array('success' => false, 'message' => $preview->get_error_message());
                break;
            }
            $preview_result = isset($preview['result']) && is_array($preview['result'])
                ? $preview['result']
                : array();
            $preview_source = isset($preview_result['detection_source'])
                ? sanitize_text_field($preview_result['detection_source'])
                : 'html';
            if (!in_array($preview_source, array('argon', 'wordpress', 'html'), true)) $preview_source = 'html';
            $response = array(
                'success' => true,
                'readable' => !empty($preview_result['readable']),
                'url' => isset($preview_result['detected_url']) ? esc_url_raw($preview_result['detected_url']) : '',
                'info' => array(
                    'primary' => isset($preview_result['primary']) ? sanitize_text_field($preview_result['primary']) : '',
                    'subtitle' => isset($preview_result['subtitle']) ? sanitize_text_field($preview_result['subtitle']) : '',
                    'description' => isset($preview_result['description']) ? sanitize_text_field($preview_result['description']) : '',
                    'avatar' => isset($preview_result['avatar']) ? esc_url_raw($preview_result['avatar']) : '',
                    'linksPage' => isset($preview_result['links_page']) ? esc_url_raw($preview_result['links_page']) : '',
                    'source' => $preview_source,
                ),
                'transport' => 'worker_sync',
            );
            break;

        case 'check_backlink':
        case 'fetch_info':
        case 'refresh_info':
            $response = array('success' => false, 'message' => __('站点抓取统一由 Cloudflare Worker 执行', 'argon'));
            break;

        case 'check_all':
            $batch = function_exists('argon_friend_link_worker_dispatch_batch')
                ? argon_friend_link_worker_dispatch_batch(3)
                : array('submitted' => 0, 'failed' => 1);
            $response = array(
                'success' => $batch['submitted'] > 0,
                'submitted' => $batch['submitted'],
                'failed' => $batch['failed'],
                'message' => $batch['submitted'] > 0
                    ? sprintf(__('已提交 %d 条 Worker 检测任务', 'argon'), $batch['submitted'])
                    : __('没有可提交的友链，或 Worker 尚未配置', 'argon'),
            );
            break;
            
        default:
            $response = array('success' => false, 'message' => '未知操作');
    }
    
    argon_flink_send_json_response($response);
}

// 处理访客申请
$apply_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['argon_link_apply'])) {
    $apply_result = argon_handle_link_application_v3($_POST);
    
    // PRG 模式：成功后重定向避免表单重复提交
    if ($apply_result) {
        $redirect_url = remove_query_arg(array('apply_success', 'apply_error'), $_SERVER['REQUEST_URI']);
        if ($apply_result['success']) {
            wp_redirect($redirect_url . (strpos($redirect_url, '?') !== false ? '&' : '?') . 'apply_success=1');
            exit;
        } else {
            // 失败时保存错误信息到 session
            if (!session_id()) session_start();
            $_SESSION['flink_apply_error'] = $apply_result['message'];
            wp_redirect($redirect_url . (strpos($redirect_url, '?') !== false ? '&' : '?') . 'apply_error=1');
            exit;
        }
    }
}

// 从 URL 参数或 session 获取申请结果
if (isset($_GET['apply_success'])) {
    $apply_result = array('success' => true, 'message' => __('申请已提交，等待管理员审核', 'argon'));
} elseif (isset($_GET['apply_error'])) {
    if (!session_id()) session_start();
    $error_msg = isset($_SESSION['flink_apply_error']) ? $_SESSION['flink_apply_error'] : __('申请失败', 'argon');
    unset($_SESSION['flink_apply_error']);
    $apply_result = array('success' => false, 'message' => $error_msg);
}

// 获取友链数据
$all_approved_links = argon_get_friend_links_raw('approved');
$pending_links = $is_admin ? argon_get_friend_links_raw('pending') : array();
$friend_link_worker_ready = function_exists('argon_friend_link_worker_is_configured')
    && argon_friend_link_worker_is_configured();
$show_apply_form = get_option('argon_friend_link_allow_apply', 'true') === 'true';
$requirements = get_option('argon_friend_link_requirements', '');
$hide_no_backlink = get_option('argon_friend_link_hide_no_backlink', 'true') === 'true';

// 过滤友链：非管理员时根据设置隐藏未上友链/失效的
$visible_links = array();
foreach ($all_approved_links as $link) {
    $show = true;
    if (!$is_admin && $hide_no_backlink) {
        // 未填写友链页面的永不隐藏
        if (!empty($link['links_page'])) {
            $link_hide = $link['hide_no_backlink'] ?? '';
            if ($link_hide === 'hide' || ($link_hide !== 'show' && $hide_no_backlink)) {
                if (isset($link['has_backlink']) && $link['has_backlink'] === false) $show = false;
                if (isset($link['accessible']) && $link['accessible'] === false) $show = false;
            }
        }
    }
    if ($show) $visible_links[] = $link;
}

// 按分类分组
$links = array();
foreach ($visible_links as $link) {
    $cat = !empty($link['category']) ? $link['category'] : '';
    if (!isset($links[$cat])) $links[$cat] = array();
    $links[$cat][] = $link;
}

$total_links = count($visible_links);

// 验证码设置（支持独立配置）
$flink_captcha_setting = get_option('argon_friend_link_captcha', 'global');
if ($flink_captcha_setting === 'global' || $flink_captcha_setting === 'same') {
    // 使用全局设置
    $global_captcha = get_option('argon_need_captcha', get_option('argon_comment_need_captcha', 'true'));
    $captcha_enabled = ($global_captcha === 'true');
} elseif ($flink_captcha_setting === 'enabled') {
    $captcha_enabled = true;
} else {
    $captcha_enabled = false;
}
$captcha_type = get_option('argon_captcha_type', 'math');

require_once get_template_directory() . '/inc/special-page-surface.php';
get_header();
argon_special_page_surface_open('flinks-page');
?>

<style id="flinks-page-style">
.flinks-detect-status { display: block; min-height: 18px; margin-top: 5px; color: var(--argon-special-page-text-soft); font-size: 11px; line-height: 1.45; }
.flinks-apply-header:focus { outline: none; box-shadow: none; }
.flinks-apply-header:focus-visible, .flink-card:focus-visible { outline: 2px solid rgba(var(--themecolor-rgbstr), 0.28); outline-offset: 2px; }
.flink-modal input::placeholder { color: var(--argon-special-page-placeholder); }

.flinks-welcome-text { font-size: 14px; color: var(--argon-special-page-text-muted); line-height: 1.6; margin-bottom: 16px; padding: 12px 16px; background: var(--argon-special-page-surface-soft); border-radius: var(--card-radius-sm); border-left: 3px solid var(--themecolor); }

.flink-card { display: flex; align-items: flex-start; gap: 14px; padding: 14px; background: transparent; border-radius: var(--card-radius-sm); text-decoration: none !important; color: inherit; border: 1px solid transparent; transition: all var(--animation-fast) var(--ease-standard); min-height: 88px; position: relative; }
.flink-card:hover { 
	transform: translateY(-2px); 
	background: var(--argon-special-page-surface-hover);
	border-color: rgba(var(--themecolor-rgbstr), 0.16);
}
.flink-card.editable { cursor: default; border-radius: var(--card-radius); overflow: hidden; }
.flink-card .flink-edit-btn { position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; border-radius: var(--card-radius-sm); background: rgba(var(--themecolor-rgbstr), 0.1); color: var(--themecolor); border: none; cursor: pointer; display: none; align-items: center; justify-content: center; font-size: 12px; transition: all var(--animation-fast) var(--ease-standard); }
.flink-card:hover .flink-edit-btn { display: flex; }
.flink-card .flink-edit-btn:hover { background: rgba(var(--themecolor-rgbstr), 0.2); }
@media (hover: none) and (pointer: coarse) {
	.flink-card .flink-edit-btn { display: flex; opacity: 0.7; }
	.flink-card .flink-edit-btn:active { opacity: 1; background: rgba(var(--themecolor-rgbstr), 0.2); }
}

/* 状态标记 */
.flink-status-badge { position: absolute; top: 6px; right: 6px; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 500; cursor: help; }
.flink-status-badge.no-backlink { background: #fef3c7; color: #d97706; }
.flink-status-badge.unknown { background: rgba(107, 114, 128, 0.14); color: var(--argon-special-page-text-muted); }
.flink-status-badge.inaccessible { background: #fee2e2; color: #dc2626; }
.flink-status-badge.waf-blocked { background: #fce7f3; color: #db2777; }
.flink-status-badge.auto-approved { background: #d1fae5; color: #059669; cursor: pointer; }
.flink-card.editable .flink-status-badge { right: 40px; }
.flink-card.editable .flink-status-badge.no-backlink { top: auto; right: auto; bottom: 6px; left: 6px; display: inline-flex; align-items: center; line-height: 1; padding: 4px 8px; border-radius: var(--card-radius) !important; overflow: hidden; }

.flink-avatar { width: var(--flink-avatar-width, 52px); max-width: 42%; height: 52px; flex-shrink: 0; border-radius: var(--card-radius); background: var(--themecolor-gradient); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 600; color: #fff; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(var(--themecolor-rgbstr), 0.2); transition: width var(--animation-normal) var(--ease-standard), transform var(--animation-fast) var(--ease-standard); }
.flink-card:hover .flink-avatar { transform: scale(1.05); }
.flink-avatar::before { content: attr(data-letter); }
.flink-avatar.has-image { background: transparent !important; box-shadow: none; }
.flink-avatar.has-image::before { display: none; }
.flink-avatar img { position: absolute; inset: 0; width: 100%; height: 100%; box-sizing: border-box; object-fit: contain; object-position: center; border-radius: var(--card-radius); opacity: 0; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .2)) drop-shadow(0 0 1px rgba(255, 255, 255, .22)); transition: opacity var(--animation-normal) var(--ease-standard); }
.flink-avatar img.is-loaded { opacity: 1; }

.flink-info { flex: 1; min-width: 0; padding-top: 2px; }
.flink-name { font-size: 15px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 4px; display: flex; align-items: center; flex-wrap: wrap; gap: 6px; line-height: 1.4; }
.flink-name .main-name { }
.flink-name .sub-name { font-size: 13px; font-weight: 400; color: var(--argon-special-page-text-soft); }
.flink-desc { font-size: 13px; color: var(--argon-special-page-text-muted); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: 4px; }
.flink-url { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 12px; color: var(--argon-special-page-text-faint); margin-top: 6px; white-space: nowrap; overflow: hidden; }
.flink-url-text { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
.flink-open-icon { flex-shrink: 0; opacity: 0; transform: translateX(-3px); transition: all var(--animation-fast) var(--ease-standard); color: var(--themecolor); }
.flink-card:hover .flink-open-icon { opacity: 0.85; transform: translateX(0); }

/* 申请区域 */
.flinks-apply-card { padding: 0; overflow: hidden; }
.flinks-apply-header { display: flex; align-items: center; justify-content: space-between; width: 100%; cursor: pointer; user-select: none; padding: 16px 22px; border: 0; background: var(--argon-special-page-surface-soft); font-family: var(--font); text-align: left; }
.flinks-apply-header:hover { background: var(--argon-special-page-surface-hover); }
.flinks-apply-title { display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 600; color: var(--themecolor); }
.flinks-apply-arrow { color: var(--themecolor); font-size: 14px; transition: transform var(--animation-normal) var(--ease-standard); }
.flinks-apply-arrow.open { transform: rotate(180deg); }
.flinks-apply-content { max-height: 0; overflow: hidden; padding: 0 22px; transition: max-height var(--animation-slow) var(--ease-standard), padding-bottom var(--animation-normal) var(--ease-standard); }
.flinks-apply-content.open { max-height: 2600px; padding-bottom: 18px; }

.flinks-requirements { background: rgba(var(--themecolor-rgbstr), 0.05); border-radius: var(--card-radius); padding: 10px 12px; margin: 10px 0 8px; border: none; border-left: 3px solid var(--themecolor); font-size: 13px; line-height: 1.5; }
.flinks-requirements, .flinks-requirements p, .flinks-requirements li { color: var(--argon-special-page-text-muted); }
.flinks-requirements p { margin: 0; }

.flinks-mysite-info { background: transparent; border-radius: var(--card-radius); padding: 10px 12px; margin: 8px 0; border: none; }
.flinks-mysite-title { font-size: 12px; font-weight: 600; color: var(--themecolor); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.flinks-mysite-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
.flinks-mysite-item { background: var(--argon-special-page-surface-soft); border-radius: calc(var(--card-radius) * 0.6); padding: 7px 10px; cursor: pointer; transition: all var(--animation-fast) var(--ease-standard); border: 1px solid transparent; }
.flinks-mysite-item:hover { background: var(--argon-special-page-surface-hover); border-color: rgba(var(--themecolor-rgbstr), 0.18); }
.flinks-mysite-item label { display: block; font-size: 10px; color: var(--argon-special-page-text-soft); margin-bottom: 1px; text-transform: uppercase; pointer-events: none; }
.flinks-mysite-item span { font-size: 12px; color: var(--color-text-deeper); overflow-wrap: break-word; word-break: normal; font-weight: 500; pointer-events: none; }

.flinks-form { margin-top: 8px; font-family: var(--font); }
.flinks-form .row { margin-left: -6px; margin-right: -6px; }
.flinks-form .row > [class*="col-"] { padding-left: 6px; padding-right: 6px; }
.flinks-form .form-group { margin-bottom: 8px; }
.flinks-form .form-control-label { font-size: 12px; font-weight: 600; margin-bottom: 3px; color: var(--color-text-deeper); display: block; font-family: var(--font); }
.flinks-form .form-control { padding: 7px 10px; font-size: 13px; }
.flinks-form .char-count { font-size: 11px; color: var(--argon-special-page-text-soft); float: right; }
.flinks-autopass-note { color: var(--argon-special-page-success-text); font-weight: normal; }
.flinks-submit { margin-top: 10px; text-align: center; }
.flinks-submit .btn { padding: 8px 24px; font-size: 13px; font-weight: 600; font-family: var(--font); }

/* 自动获取预览 */
.flinks-auto-preview { background: transparent; border-radius: var(--card-radius); padding: 12px; margin: 10px 0; display: none; border: none; }
.flinks-auto-preview.show { display: block; }
.flinks-auto-preview-title { font-size: 12px; font-weight: 600; color: var(--themecolor); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.flinks-auto-preview-content { display: flex; gap: 12px; align-items: flex-start; }
.flinks-auto-preview-avatar { width: 48px; height: 48px; border-radius: 10px; background: var(--color-border); flex-shrink: 0; overflow: hidden; }
.flinks-auto-preview-avatar img { width: 100%; height: 100%; object-fit: cover; }
.flinks-auto-preview-info { flex: 1; min-width: 0; }
.flinks-auto-preview-name { font-size: 14px; font-weight: 600; color: var(--color-text-deeper); }
.flinks-auto-preview-desc { font-size: 12px; color: var(--argon-special-page-text-muted); margin-top: 4px; }

/* 验证码 */
.flinks-captcha { margin-top: 10px; }
.flinks-captcha .input-group { max-width: 280px; }
.flinks-captcha-text { background: var(--color-background); color: var(--argon-special-page-text-main); border-color: var(--color-border); font-family: monospace; font-size: 14px; letter-spacing: 2px; cursor: pointer; user-select: none; }

/* 管理面板 */
.flinks-admin-card { padding: 16px 20px; }
.flinks-admin-title { font-size: 15px; font-weight: 600; color: var(--themecolor); margin: 0 0 12px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.flinks-admin-section { margin-bottom: 16px; }
.flinks-admin-section:last-child { margin-bottom: 0; }
.flinks-admin-section h4 { font-size: 13px; font-weight: 600; color: var(--color-text-deeper); margin: 0 0 8px; display: flex; align-items: center; gap: 6px; }

.flinks-pending-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: var(--argon-special-page-warning-bg); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: var(--card-radius); margin-bottom: 8px; }
.flinks-pending-avatar { width: 40px; height: 40px; border-radius: 8px; background: var(--color-border); flex-shrink: 0; overflow: hidden; }
.flinks-pending-avatar img { width: 100%; height: 100%; padding: 3px; box-sizing: border-box; object-fit: contain; object-position: center; opacity: 0; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .18)) drop-shadow(0 0 1px rgba(255, 255, 255, .2)); transition: opacity var(--animation-normal) var(--ease-standard); }
.flinks-pending-avatar img.is-loaded { opacity: 1; }
.flinks-pending-info { flex: 1; min-width: 0; }
.flinks-pending-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; color: var(--argon-special-page-text-main); }
.flinks-pending-name .sub-name { font-weight: 400; font-size: 12px; color: var(--argon-special-page-text-soft); margin-left: 6px; }
.flinks-pending-url { font-size: 12px; color: var(--argon-special-page-text-muted); margin-bottom: 4px; }
.flinks-pending-url a { color: var(--themecolor); }
.flinks-pending-desc { font-size: 12px; color: var(--argon-special-page-text-muted); }
.flinks-pending-actions { display: flex; gap: 6px; flex-shrink: 0; }
.flinks-pending-actions .btn { padding: 4px 12px; font-size: 12px; }

.flinks-add-form { background: transparent; border-radius: 0; padding: 0; border: 0; }
.flinks-add-form .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 8px; }
.flinks-add-form .form-row.full { grid-template-columns: 1fr; }
.flinks-add-form label { font-size: 12px; font-weight: 500; color: var(--color-text-deeper); margin-bottom: 3px; display: block; }
.flinks-add-form input { width: 100%; padding: 6px 10px; font-size: 13px; }
.flinks-add-form .input-with-btn { display: flex; gap: 6px; }
.flinks-add-form .input-with-btn input { flex: 1; min-width: 0; }
.flinks-add-form .input-with-btn .btn { flex-shrink: 0; padding: 6px 10px; font-size: 12px; }
.flinks-add-form .form-actions { margin-top: 10px; display: flex; gap: 8px; }
.flinks-add-form .form-actions .btn { display: inline-flex; align-items: center; justify-content: center; text-align: center; }

/* 编辑弹窗 */
.flink-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity var(--motion-popup-duration) var(--motion-ease-exit), visibility 0s linear var(--motion-popup-duration); }
.flink-modal-overlay.show { opacity: 1; visibility: visible; pointer-events: auto; transition-timing-function: var(--motion-ease-enter), linear; transition-delay: 0s; }
.flink-modal { background: var(--color-foreground); border-radius: var(--card-radius); padding: 20px; width: 90%; max-width: 560px; max-height: 90vh; overflow-y: auto; color: var(--argon-special-page-text-main); box-shadow: 0 24px 80px rgba(0,0,0,0.28); opacity: 0; transform: translateY(var(--motion-popup-offset)) scale(var(--motion-popup-scale)); transition: opacity var(--motion-popup-duration) var(--motion-ease-exit), transform var(--motion-popup-duration) var(--motion-ease-exit); }
.flink-modal-overlay.show .flink-modal { opacity: 1; transform: translateY(0) scale(1); transition-timing-function: var(--motion-ease-enter); }
.flink-modal-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; color: var(--argon-special-page-text-main); }
.flink-modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--argon-special-page-text-soft); padding: 0; line-height: 1; }
.flink-modal-close:hover { color: var(--argon-special-page-text-main); }
.flink-modal .form-group { margin-bottom: 12px; }
.flink-modal .form-group label { font-size: 12px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 4px; display: block; }
.flink-modal .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 12px; }
.flink-modal .form-group.full { grid-column: 1 / -1; }
.flink-modal .form-group input, .flink-modal .form-group select { width: 100%; padding: 8px 10px; font-size: 13px; border: 1px solid var(--color-border); border-radius: calc(var(--card-radius) * 0.6); font-family: var(--font); box-sizing: border-box; background: var(--argon-special-page-input-bg); color: var(--argon-special-page-input-text); }
.flink-modal .form-group input:focus, .flink-modal .form-group select:focus { border-color: var(--themecolor); outline: none; box-shadow: 0 0 0 2px rgba(var(--themecolor-rgbstr), 0.12); }
.flink-modal .form-actions { margin-top: 16px; display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
.flink-modal .form-actions .btn { display: inline-flex; align-items: center; justify-content: center; text-align: center; min-width: auto; }
.flink-modal .btn-danger { background: #ef4444; border-color: #ef4444; }
.flink-modal .btn-danger:hover { background: #dc2626; border-color: #dc2626; }
.flink-modal .status-info { font-size: 12px; color: var(--argon-special-page-text-muted); margin-top: 12px; padding: 12px; border: 1px solid var(--color-border-on-foreground); border-radius: var(--card-radius-sm); background: var(--argon-special-page-surface-soft); }
.flink-modal .status-info .badge { font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 6px; }
.flink-modal .status-info .badge-warning { background: var(--argon-special-page-warning-bg); color: var(--argon-special-page-warning-text); }
.flink-modal .status-info .badge-danger { background: var(--argon-special-page-danger-bg); color: var(--argon-special-page-danger-text); }
.flink-modal .status-info .badge-success { background: var(--argon-special-page-success-bg); color: var(--argon-special-page-success-text); }
.flink-modal .status-info .badge-secondary { background: var(--argon-special-page-surface-hover); color: var(--argon-special-page-text-muted); }
.flink-action-message { display: none; margin-top: 10px; padding: 9px 12px; border-radius: var(--card-radius-sm); font-size: 12px; line-height: 1.5; }
.flink-action-message.show { display: block; }
.flink-action-message.success { background: var(--argon-special-page-success-bg); color: var(--argon-special-page-success-text); }
.flink-action-message.error { background: var(--argon-special-page-danger-bg); color: var(--argon-special-page-danger-text); }
.flink-action-message.info { background: rgba(var(--themecolor-rgbstr), 0.1); color: var(--themecolor); }
.flink-copy-tip { position: fixed; top: 50%; left: 50%; z-index: 9999; padding: 10px 20px; border-radius: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 14px; opacity: 0; transform: translate(-50%, calc(-50% + var(--motion-popup-offset))) scale(var(--motion-popup-scale)); pointer-events: none; transition: opacity var(--motion-popup-duration) var(--motion-ease-exit), transform var(--motion-popup-duration) var(--motion-ease-exit); }
.flink-copy-tip.is-visible { opacity: 1; transform: translate(-50%, -50%) scale(1); transition-timing-function: var(--motion-ease-enter); }

@media (max-width: 768px) {
    .flinks-mysite-grid { grid-template-columns: 1fr; }
    .flinks-add-form .form-row { grid-template-columns: 1fr; }
    .flink-card { min-height: auto; }
    .flinks-apply-header { padding: 15px 16px; }
    .flinks-apply-content { padding-left: 16px; padding-right: 16px; }
    .flink-modal { width: calc(100% - 24px); padding: 16px; }
    .flink-modal .form-grid { grid-template-columns: 1fr; }
    .flink-modal .form-actions .btn { flex: 1 1 calc(50% - 4px); }
}

html.darkmode .flink-card { background: transparent; }
html.darkmode .flink-card:hover { background: rgba(255, 255, 255, 0.05); }
html.darkmode .flink-modal { background: var(--color-background); }
html.darkmode .flinks-add-form input { background: var(--color-background); }

.flink-avatar[data-color="1"] { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.flink-avatar[data-color="2"] { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.flink-avatar[data-color="3"] { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.flink-avatar[data-color="4"] { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.flink-avatar[data-color="5"] { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.flink-avatar[data-color="6"] { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
</style>

<script data-pjax>
(function() {
	window.argonInitFriendLinkAvatars = function(root) {
		var scope = root && root.querySelectorAll ? root : document;
		var images = scope.querySelectorAll('.flink-avatar img[data-avatar-src], .flinks-pending-avatar img[data-avatar-src]');
		images.forEach(function(img) {
			if (img.dataset.avatarLoading === 'true' || img.classList.contains('is-loaded')) return;
			var source = img.getAttribute('data-avatar-src');
			if (!source) return;
			img.dataset.avatarLoading = 'true';
			img.referrerPolicy = 'no-referrer';
			img.onload = function() {
				img.dataset.avatarLoading = 'false';
				img.classList.add('is-loaded');
				var avatar = img.parentElement && img.parentElement.classList.contains('flink-avatar') ? img.parentElement : null;
				if (avatar) {
					var aspectRatio = img.naturalHeight > 0 ? img.naturalWidth / img.naturalHeight : 1;
					var avatarWidth = Math.max(52, Math.min(144, 52 * aspectRatio));
					avatar.style.setProperty('--flink-avatar-width', avatarWidth.toFixed(2) + 'px');
					avatar.classList.add('has-image');
				}
			};
			img.onerror = function() {
				img.dataset.avatarLoading = 'false';
				img.classList.remove('is-loaded');
				var avatar = img.parentElement && img.parentElement.classList.contains('flink-avatar') ? img.parentElement : null;
				if (avatar) {
					avatar.classList.remove('has-image');
					avatar.style.removeProperty('--flink-avatar-width');
				}
				img.removeAttribute('src');
			};
			img.src = source;
			if (img.complete && img.naturalWidth > 0) img.onload();
		});
	};
	requestAnimationFrame(function() {
		window.argonInitFriendLinkAvatars(document);
	});
})();
</script>

<?php
// 获取自定义标语
$flink_slogan = get_option('argon_friend_link_slogan', __('海内存知己，天涯若比邻', 'argon'));
$flink_welcome = get_option('argon_friend_link_welcome', __('欢迎与我交换友链！如果您的网站已添加本站链接，填写友链页面地址可自动通过审核。', 'argon'));
$flink_header_stats = array(
    array(
        'text' => sprintf(__('%d 位朋友', 'argon'), $total_links),
    ),
);
if (count($links) > 1) {
    $flink_header_stats[] = array(
        'text' => sprintf(__('%d 个分类', 'argon'), count($links)),
    );
}
argon_special_page_surface_header(array(
    'title' => __('友情链接', 'argon'),
    'subtitle' => $flink_slogan,
    'stats' => $flink_header_stats,
));
?>

<!-- 管理面板（仅管理员可见） -->
<?php if ($is_admin) : ?>
<article class="post card shadow-sm bg-white border-0 flinks-admin-card" style="margin-bottom: 16px;" id="admin-panel">
    <div class="flinks-admin-title"><i class="fa fa-cog"></i><?php _e('友链管理', 'argon'); ?> <button class="btn btn-sm btn-secondary" style="margin-left:auto;font-size:11px;" id="check-all-btn" title="<?php echo esc_attr($friend_link_worker_ready ? __('由 Cloudflare Worker 异步检测最多 3 条友链', 'argon') : __('请先在 Argon 主题设置的友情链接区域配置 Worker', 'argon')); ?>" aria-label="<?php echo esc_attr($friend_link_worker_ready ? __('立即检测一批', 'argon') : __('Worker 未配置', 'argon')); ?>" <?php if (!$friend_link_worker_ready) echo 'disabled'; ?>><i class="fa fa-refresh" aria-hidden="true"></i></button></div>
    
    <?php if (!empty($pending_links)) : ?>
    <div class="flinks-admin-section">
        <h4><i class="fa fa-clock-o"></i> <?php _e('待审核', 'argon'); ?> (<?php echo count($pending_links); ?>)</h4>
        <?php foreach ($pending_links as $link) : 
            $main_name = esc_html($link['name']);
            $sub_name = !empty($link['display_name']) ? esc_html($link['display_name']) : '';
        ?>
        <div class="flinks-pending-item" data-id="<?php echo esc_attr($link['id']); ?>">
            <div class="flinks-pending-avatar">
                <?php if (!empty($link['avatar'])) : ?><img data-avatar-src="<?php echo esc_url($link['avatar']); ?>" alt="" aria-hidden="true" decoding="async"><?php endif; ?>
            </div>
            <div class="flinks-pending-info">
                <div class="flinks-pending-name"><?php echo $main_name; ?><?php if ($sub_name) : ?><span class="sub-name"><?php echo $sub_name; ?></span><?php endif; ?></div>
                <div class="flinks-pending-url"><a href="<?php echo esc_url($link['url']); ?>" target="_blank"><?php echo esc_html($link['url']); ?></a></div>
                <?php if (!empty($link['description'])) : ?><div class="flinks-pending-desc"><?php echo esc_html($link['description']); ?></div><?php endif; ?>
            </div>
            <div class="flinks-pending-actions">
                <button class="btn btn-sm btn-primary flink-approve" data-id="<?php echo esc_attr($link['id']); ?>"><?php _e('通过', 'argon'); ?></button>
                <button class="btn btn-sm btn-secondary flink-reject" data-id="<?php echo esc_attr($link['id']); ?>"><?php _e('拒绝', 'argon'); ?></button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="flinks-admin-section">
        <h4><i class="fa fa-plus"></i> <?php _e('添加友链', 'argon'); ?></h4>
        <div class="flinks-add-form">
            <div class="form-row">
                <div>
                    <label><?php _e('网站地址', 'argon'); ?> *</label>
                    <div class="input-with-btn">
                        <input type="url" id="add-url" class="argon-text-input" placeholder="https://example.com">
                        <button type="button" class="btn btn-secondary" id="fetch-info"><?php _e('获取', 'argon'); ?></button>
                    </div>
                    <small class="flinks-detect-status" id="add-detect-status" aria-live="polite"></small>
                </div>
                <div><label><?php _e('网站名称', 'argon'); ?> *</label><input type="text" id="add-name" class="argon-text-input" placeholder="<?php _e('主标题', 'argon'); ?>"></div>
            </div>
            <div class="form-row">
                <div><label><?php _e('副标题', 'argon'); ?></label><input type="text" id="add-display-name" class="argon-text-input" placeholder="<?php _e('自动获取的标题', 'argon'); ?>"></div>
                <div><label><?php _e('分类', 'argon'); ?></label><input type="text" id="add-category" class="argon-text-input" placeholder="<?php _e('留空为默认', 'argon'); ?>"></div>
            </div>
            <div class="form-row">
                <div><label><?php _e('头像', 'argon'); ?></label><input type="url" id="add-avatar" class="argon-text-input" placeholder="<?php _e('自动获取', 'argon'); ?>"></div>
                <div><label><?php _e('描述', 'argon'); ?></label><input type="text" id="add-desc" class="argon-text-input" maxlength="100" placeholder="<?php _e('主描述', 'argon'); ?>"></div>
            </div>
            <div class="form-row full">
                <div><label><?php _e('对方友链页面', 'argon'); ?></label><input type="url" id="add-links-page" class="argon-text-input" placeholder="<?php _e('自动获取或手动填写', 'argon'); ?>"></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-primary" id="add-link-btn"><?php _e('添加', 'argon'); ?></button>
            </div>
        </div>
    </div>
</article>
<?php endif; ?>

<!-- 友链列表 -->
<?php if (!empty($links)) : ?>
<div class="argon-special-page-list">
    <?php $color_index = 0; foreach ($links as $category => $category_links) : ?>
    <article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-section" data-category="<?php echo esc_attr($category); ?>">
        <div class="argon-special-page-section-header">
            <h2 class="argon-special-page-section-title">
                <i class="fa fa-<?php echo empty($category) ? 'star' : 'folder-open'; ?>"></i>
                <?php echo empty($category) ? __('友情链接', 'argon') : esc_html($category); ?>
            </h2>
            <span class="argon-special-page-section-count"><i class="fa fa-link"></i><?php echo count($category_links); ?> <?php _e('个站点', 'argon'); ?></span>
        </div>
        <div class="argon-special-page-grid">
            <?php foreach ($category_links as $link) : 
                $host = parse_url($link['url'], PHP_URL_HOST);
                $main_name = $link['name'];
                $sub_name = !empty($link['display_name']) ? $link['display_name'] : '';
                $first_letter = mb_substr($main_name, 0, 1);
                $color_index = ($color_index % 6) + 1;
                $desc = !empty($link['description']) ? $link['description'] : (!empty($link['auto_description']) ? $link['auto_description'] : __('暂无描述', 'argon'));
                
                // 状态检测
                $has_backlink = isset($link['has_backlink']) ? $link['has_backlink'] : null;
                $backlink_check_result = isset($link['backlink_check_result']) ? $link['backlink_check_result'] : '';
                $accessible = isset($link['accessible']) ? $link['accessible'] : null;
                $blocked_by_waf = isset($link['blocked_by_waf']) ? $link['blocked_by_waf'] : false;
                $error_reason = isset($link['error_reason']) ? $link['error_reason'] : '';
                $auto_approved = !empty($link['auto_approved']);
                $status_badge = '';
                if ($is_admin) {
                    if ($backlink_check_result === 'unknown') {
                        $status_badge = '<span class="flink-status-badge unknown">' . __('状态未知', 'argon') . '</span>';
                    } elseif ($blocked_by_waf) {
                        $status_badge = '<span class="flink-status-badge waf-blocked" title="' . esc_attr($error_reason) . '">' . __('WAF拦截', 'argon') . '</span>';
                    } elseif ($accessible === false) {
                        $title = $error_reason ? esc_attr($error_reason) : '';
                        $status_badge = '<span class="flink-status-badge inaccessible" title="' . $title . '">' . __('失效', 'argon') . '</span>';
                    } elseif ($has_backlink === false) {
                        $status_badge = '<span class="flink-status-badge no-backlink">' . __('未上链', 'argon') . '</span>';
                    } elseif ($auto_approved) {
                        $status_badge = '<span class="flink-status-badge auto-approved" data-id="' . esc_attr($link['id']) . '" onclick="event.preventDefault();event.stopPropagation();removeAutoTag(this)" title="' . __('点击移除标记', 'argon') . '">' . __('自动', 'argon') . '</span>';
                    }
                }
            ?>
            <a href="<?php echo esc_url($link['url']); ?>" class="flink-card <?php echo $is_admin ? 'editable' : ''; ?>" target="_blank" rel="noopener noreferrer" data-id="<?php echo esc_attr($link['id']); ?>">
                <?php echo $status_badge; ?>
                <?php if ($is_admin) : ?><button type="button" class="flink-edit-btn" onclick="event.preventDefault();event.stopPropagation();openEditModal('<?php echo esc_attr($link['id']); ?>')"><i class="fa fa-pencil"></i></button><?php endif; ?>
                <div class="flink-avatar" data-letter="<?php echo esc_attr($first_letter); ?>" data-color="<?php echo $color_index; ?>">
                    <?php if (!empty($link['avatar'])) : ?><img data-avatar-src="<?php echo esc_url($link['avatar']); ?>" alt="" aria-hidden="true" decoding="async"><?php endif; ?>
                </div>
                <div class="flink-info">
                    <div class="flink-name">
                        <span class="main-name"><?php echo esc_html($main_name); ?></span>
                        <?php if ($sub_name) : ?><span class="sub-name"><?php echo esc_html($sub_name); ?></span><?php endif; ?>
                    </div>
                    <div class="flink-desc" title="<?php echo esc_attr($desc); ?>"><?php echo esc_html($desc); ?></div>
                    <div class="flink-url">
                        <span class="flink-url-text"><i class="fa fa-link" style="margin-right:4px;font-size:10px;"></i><?php echo esc_html($host); ?></span>
                        <i class="fa fa-external-link flink-open-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php else : ?>
<?php argon_special_page_surface_empty(__('暂无友情链接', 'argon')); ?>
<?php endif; ?>

<!-- 申请友链 -->
<?php if ($show_apply_form && !$is_admin) : ?>
<article class="post card shadow-sm bg-white border-0 flinks-apply-card" style="margin-bottom: 16px;" id="apply-section">
    <button type="button" class="flinks-apply-header" onclick="toggleApplyForm()" aria-expanded="<?php echo $apply_result ? 'true' : 'false'; ?>">
        <div class="flinks-apply-title"><i class="fa fa-plus-circle"></i><span><?php _e('申请友链', 'argon'); ?></span></div>
        <i class="fa fa-chevron-down flinks-apply-arrow <?php echo $apply_result ? 'open' : ''; ?>"></i>
    </button>
    <div class="flinks-apply-content <?php echo $apply_result ? 'open' : ''; ?>">
        <?php if ($apply_result) : ?>
        <div class="alert alert-<?php echo $apply_result['success'] ? 'success' : 'danger'; ?>" style="margin-top:10px;padding:8px 12px;font-size:13px;"><?php echo esc_html($apply_result['message']); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($requirements)) : ?>
        <div class="flinks-requirements"><?php echo wp_kses_post($requirements); ?></div>
        <?php endif; ?>
        
        <div class="flinks-welcome-text">
            <?php echo wp_kses_post($flink_welcome); ?>
        </div>
        
        <div class="flinks-mysite-info">
            <div class="flinks-mysite-title"><i class="fa fa-bookmark"></i><?php _e('本站信息（点击复制）', 'argon'); ?></div>
            <div class="flinks-mysite-grid">
                <div class="flinks-mysite-item" onclick="copyText('<?php echo esc_js(get_bloginfo('name')); ?>')"><label><?php _e('名称', 'argon'); ?></label><span><?php echo get_bloginfo('name'); ?></span></div>
                <div class="flinks-mysite-item" onclick="copyText('<?php echo esc_js(home_url()); ?>')"><label><?php _e('地址', 'argon'); ?></label><span><?php echo home_url(); ?></span></div>
                <div class="flinks-mysite-item" onclick="copyText('<?php echo esc_js(get_bloginfo('description')); ?>')"><label><?php _e('描述', 'argon'); ?></label><span><?php echo get_bloginfo('description') ?: '-'; ?></span></div>
            </div>
        </div>
        
        <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" class="flinks-form" id="apply-form">
            <?php wp_nonce_field('argon_link_apply', 'argon_link_apply_nonce'); ?>
            <input type="hidden" name="argon_link_apply" value="1">
            <input type="hidden" name="apply_browser_detected" id="apply-browser-detected" value="0">
            <input type="hidden" name="apply_name_manual" id="apply-name-manual" value="1">
            <input type="hidden" name="apply_display_name_manual" id="apply-display-name-manual" value="1">
            <input type="hidden" name="apply_avatar_manual" id="apply-avatar-manual" value="1">
            <input type="hidden" name="apply_description_manual" id="apply-description-manual" value="1">
            <input type="hidden" name="apply_links_page_manual" id="apply-links-page-manual" value="1">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站地址', 'argon'); ?> <span class="text-danger">*</span></label>
                        <input type="url" name="apply_url" id="apply-url" class="form-control argon-text-input" required placeholder="https://example.com">
                        <small class="flinks-detect-status" id="apply-detect-status" aria-live="polite"></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站名称', 'argon'); ?></label>
                        <input type="text" name="apply_name" id="apply-name" class="form-control argon-text-input" placeholder="<?php _e('选填', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('副标题', 'argon'); ?></label>
                        <input type="text" name="apply_display_name" id="apply-display-name" class="form-control argon-text-input" placeholder="<?php _e('可由浏览器自动识别', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站图标', 'argon'); ?></label>
                        <input type="url" name="apply_avatar" id="apply-avatar" class="form-control argon-text-input" placeholder="<?php _e('选填', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('联系邮箱', 'argon'); ?></label>
                        <input type="email" name="apply_email" class="form-control argon-text-input" placeholder="<?php _e('选填', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('友链页面', 'argon'); ?> <small class="flinks-autopass-note"><?php _e('填写可自动通过', 'argon'); ?></small></label>
                        <input type="url" name="apply_links_page" id="apply-links-page" class="form-control argon-text-input" placeholder="<?php _e('您放置友链的页面地址', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站描述', 'argon'); ?> <span class="char-count"><span id="desc-count">0</span>/100</span></label>
                        <textarea name="apply_description" id="apply-desc" class="form-control argon-text-input" maxlength="100" rows="2" placeholder="<?php _e('选填', 'argon'); ?>" oninput="document.getElementById('desc-count').textContent=this.value.length" style="resize:vertical;min-height:60px;"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- 验证码 -->
            <?php if ($captcha_enabled) : ?>
            <div class="flinks-captcha">
                <?php if ($captcha_type === 'geetest') : ?>
                <div id="flink-geetest-captcha"></div>
                <input type="hidden" name="lot_number" id="flink-lot-number">
                <input type="hidden" name="captcha_output" id="flink-captcha-output">
                <input type="hidden" name="pass_token" id="flink-pass-token">
                <input type="hidden" name="gen_time" id="flink-gen-time">
                <?php else : ?>
                <div class="input-group input-group-sm">
                    <input type="text" name="comment_captcha" id="flink-captcha-input" class="form-control argon-text-input" placeholder="<?php _e('验证码', 'argon'); ?>" required style="max-width:120px;">
                    <div class="input-group-append">
                        <span class="input-group-text flinks-captcha-text" id="flink-captcha-text" onclick="refreshCaptcha()" title="<?php _e('点击刷新', 'argon'); ?>"><?php echo get_comment_captcha(); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="flinks-submit">
                <button type="submit" class="btn btn-primary" id="apply-submit-btn"><i class="fa fa-paper-plane"></i> <?php _e('提交申请', 'argon'); ?></button>
            </div>
        </form>
    </div>
</article>
<?php endif; ?>

<!-- 编辑弹窗 -->
<?php if ($is_admin) : ?>
<div class="flink-modal-overlay" id="edit-modal">
    <div class="flink-modal">
        <div class="flink-modal-title">
            <span><i class="fa fa-pencil"></i> <?php _e('编辑友链', 'argon'); ?></span>
            <button type="button" class="flink-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <input type="hidden" id="edit-id">
        <div class="form-grid">
            <div class="form-group">
                <label><?php _e('网站名称', 'argon'); ?> *</label>
                <input type="text" id="edit-name" placeholder="<?php _e('主标题', 'argon'); ?>">
            </div>
            <div class="form-group">
                <label><?php _e('副标题', 'argon'); ?></label>
                <input type="text" id="edit-display-name" placeholder="<?php _e('自动获取的标题', 'argon'); ?>">
            </div>
            <div class="form-group full">
                <label><?php _e('网站地址', 'argon'); ?> *</label>
                <input type="url" id="edit-url">
            </div>
            <div class="form-group full">
                <label><?php _e('友链页面', 'argon'); ?></label>
                <input type="url" id="edit-links-page" placeholder="<?php _e('对方放置友链的页面地址', 'argon'); ?>">
            </div>
            <div class="form-group full">
                <label><?php _e('头像地址', 'argon'); ?></label>
                <input type="url" id="edit-avatar">
            </div>
            <div class="form-group">
                <label><?php _e('描述', 'argon'); ?></label>
                <input type="text" id="edit-desc" maxlength="100">
            </div>
            <div class="form-group">
                <label><?php _e('分类', 'argon'); ?></label>
                <input type="text" id="edit-category">
            </div>
            <div class="form-group full">
                <label><?php _e('未上友链时', 'argon'); ?></label>
                <select id="edit-hide-no-backlink">
                    <option value=""><?php _e('跟随全局设置', 'argon'); ?></option>
                    <option value="hide"><?php _e('隐藏', 'argon'); ?></option>
                    <option value="show"><?php _e('仍然显示', 'argon'); ?></option>
                </select>
            </div>
        </div>
        <div class="status-info" id="edit-status-info"></div>
        <div class="flink-action-message" id="edit-action-message" aria-live="polite"></div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" id="edit-fetch-btn"><?php _e('更新信息', 'argon'); ?></button>
            <button type="button" class="btn btn-secondary" id="edit-check-btn"><?php _e('检测互链', 'argon'); ?></button>
            <button type="button" class="btn btn-danger" id="edit-delete-btn"><?php _e('删除', 'argon'); ?></button>
            <button type="button" class="btn btn-primary" id="edit-save-btn"><?php _e('保存', 'argon'); ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?php echo esc_url(get_template_directory_uri() . '/assets/js/friend-links-browser.js?ver=' . rawurlencode(argon_get_asset_file_version('assets/js/friend-links-browser.js'))); ?>" data-pjax></script>
<script data-pjax>
function toggleApplyForm() {
    var c = document.querySelector('.flinks-apply-content');
    var a = document.querySelector('.flinks-apply-arrow');
    var h = document.querySelector('.flinks-apply-header');
    if (c && a) {
        var isOpen = c.classList.toggle('open');
        a.classList.toggle('open', isOpen);
        if (h) h.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
}
function openApplyForm() {
    var c = document.querySelector('.flinks-apply-content');
    var a = document.querySelector('.flinks-apply-arrow');
    var h = document.querySelector('.flinks-apply-header');
    if (c) c.classList.add('open');
    if (a) a.classList.add('open');
    if (h) h.setAttribute('aria-expanded', 'true');
}
function copyText(t) {
    navigator.clipboard ? navigator.clipboard.writeText(t).then(showCopyTip) : (function() {
        var ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); showCopyTip();
    })();
}
function showCopyTip() {
    var tip = document.createElement('span');
	var hideDelay = 1200;
	var transitionDelay = window.ArgonMotion ? window.ArgonMotion.duration('popup') : 250;
    tip.textContent = '<?php _e('已复制', 'argon'); ?>';
	tip.className = 'flink-copy-tip';
    document.body.appendChild(tip);
	requestAnimationFrame(function() {
		requestAnimationFrame(function() { tip.classList.add('is-visible'); });
	});
	setTimeout(function() {
		tip.classList.remove('is-visible');
		setTimeout(function() { tip.remove(); }, transitionDelay);
	}, hideDelay);
}

// 刷新验证码
function refreshCaptcha() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=argon_get_captcha')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.captcha) {
                document.getElementById('flink-captcha-text').textContent = res.captcha;
            }
        });
}

var argonFriendLinkWorkerReady = <?php echo $friend_link_worker_ready ? 'true' : 'false'; ?>;
var argonFriendLinkHomeUrl = <?php echo wp_json_encode(home_url('/')); ?>;

<?php if ($apply_result) : ?>
document.addEventListener('DOMContentLoaded', function() {
    var s = document.getElementById('apply-section');
    openApplyForm();
    if (s && window.ArgonMotion) {
		window.ArgonMotion.scrollTo(s.getBoundingClientRect().top + window.pageYOffset - (window.innerHeight - s.offsetHeight) / 2);
	}
    
    // 清除 URL 中的申请结果参数
    if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.delete('apply_success');
        url.searchParams.delete('apply_error');
        window.history.replaceState({}, '', url.toString());
    }
});
<?php endif; ?>

<?php if ($captcha_enabled && $captcha_type === 'geetest') : ?>
// 极验验证码
if (typeof initGeetest4 === 'function') {
    initGeetest4({
        captchaId: '<?php echo get_option('argon_geetest_captcha_id', ''); ?>',
        product: 'bind'
    }, function(captcha) {
        captcha.appendTo('#flink-geetest-captcha');
        var applySubmitBtn = document.getElementById('apply-submit-btn');
        if (applySubmitBtn) {
            applySubmitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                captcha.showCaptcha();
            });
        }
        captcha.onSuccess(function() {
            var result = captcha.getValidate();
            document.getElementById('flink-lot-number').value = result.lot_number;
            document.getElementById('flink-captcha-output').value = result.captcha_output;
            document.getElementById('flink-pass-token').value = result.pass_token;
            document.getElementById('flink-gen-time').value = result.gen_time;
            document.getElementById('apply-form').submit();
        });
    });
}
<?php endif; ?>

// 访客申请的站点识别完全由当前浏览器执行，服务器只接收表单结果。
(function() {
    var detector = window.ArgonFriendLinkBrowser;
    var urlInput = document.getElementById('apply-url');
    if (!detector || !urlInput) return;

    var detectedInput = document.getElementById('apply-browser-detected');
    var status = document.getElementById('apply-detect-status');
    var fields = [
        { input: document.getElementById('apply-name'), manual: document.getElementById('apply-name-manual'), key: 'primary' },
        { input: document.getElementById('apply-display-name'), manual: document.getElementById('apply-display-name-manual'), key: 'subtitle' },
        { input: document.getElementById('apply-avatar'), manual: document.getElementById('apply-avatar-manual'), key: 'avatar' },
        { input: document.getElementById('apply-desc'), manual: document.getElementById('apply-description-manual'), key: 'description' },
        { input: document.getElementById('apply-links-page'), manual: document.getElementById('apply-links-page-manual'), key: 'linksPage' }
    ];
    var requestSequence = 0;
    var timer = null;

    fields.forEach(function(field) {
        if (!field.input || !field.manual) return;
        field.manual.value = '0';
        field.input.addEventListener('input', function() {
            field.manual.value = '1';
        });
    });

    function setDetectStatus(message) {
        if (status) status.textContent = message || '';
    }

    function detectApplicationSite() {
        var inputValue = urlInput.value.trim();
        if (!inputValue) {
            setDetectStatus('');
            return;
        }
        var sequence = ++requestSequence;
        if (detectedInput) detectedInput.value = '0';
        setDetectStatus('<?php _e('正在由浏览器探测主站、www 与 blog 域名...', 'argon'); ?>');
        detector.detectSite(inputValue).then(function(result) {
            if (sequence !== requestSequence) return;
            if (!result.success) {
                setDetectStatus('<?php _e('浏览器未能访问候选站点，请检查地址或手动填写资料', 'argon'); ?>');
                return;
            }
            if (result.url) urlInput.value = result.url;
            if (detectedInput) detectedInput.value = '1';
            fields.forEach(function(field) {
                var value = result.info && result.info[field.key] ? result.info[field.key] : '';
                if (value && field.input && field.input.maxLength > 0) value = value.slice(0, field.input.maxLength);
                if (field.input && field.manual && field.manual.value !== '1' && value) field.input.value = value;
            });
            var descCount = document.getElementById('desc-count');
            var descInput = document.getElementById('apply-desc');
            if (descCount && descInput) descCount.textContent = descInput.value.length;
            setDetectStatus(result.readable
                ? '<?php _e('已在浏览器中识别主标题、副标题与站点资料', 'argon'); ?>'
                : '<?php _e('已识别候选站点；提交后将由 Worker 异步检测并补全资料', 'argon'); ?>');
        }).catch(function() {
            if (sequence === requestSequence) setDetectStatus('<?php _e('浏览器探测失败，请手动填写资料', 'argon'); ?>');
        });
    }

    urlInput.addEventListener('input', function() {
        requestSequence++;
        if (detectedInput) detectedInput.value = '0';
        setDetectStatus('');
        fields.forEach(function(field) {
            if (field.input && field.manual && field.manual.value !== '1') field.input.value = '';
        });
        var descCount = document.getElementById('desc-count');
        if (descCount) descCount.textContent = document.getElementById('apply-desc').value.length;
        if (timer) clearTimeout(timer);
        timer = setTimeout(detectApplicationSite, 650);
    });
    urlInput.addEventListener('blur', function() {
        if (timer) clearTimeout(timer);
        detectApplicationSite();
    });
})();

<?php if ($is_admin) : ?>
(function() {
    var adminPanel = document.getElementById('admin-panel');
    if (!adminPanel || adminPanel.dataset.argonFriendLinksBound === 'true') return;
    adminPanel.dataset.argonFriendLinksBound = 'true';
    var nonce = '<?php echo wp_create_nonce('argon_flink_manage'); ?>';
    var ajaxUrl = <?php echo wp_json_encode(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))); ?>;
    var detector = window.ArgonFriendLinkBrowser;
    var addNameIsManual = false;
    var addDisplayNameIsManual = false;
    var addAvatarIsManual = false;
    var addDescriptionIsManual = false;
    var addLinksPageIsManual = false;
    var addBrowserResult = null;
    var addDetectSequence = 0;
    var editNameSource = 'worker';
    var editDisplayNameSource = 'worker';
    var editAvatarSource = 'worker';
    var editDescriptionSource = 'worker';
    var editLinksPageSource = 'worker';
    var editContextSequence = 0;
    var editDetectSequence = 0;
    var editBacklinkSequence = 0;
    var editWorkerPollTimer = null;
    var editFieldSnapshots = {};
    var editBrowserOperation = null;

    function setMessage(type, message) {
        var box = document.getElementById('edit-action-message');
        if (!box) return;
        box.className = 'flink-action-message show ' + (type || 'info');
        box.textContent = message || '';
    }

    function clearMessage() {
        var box = document.getElementById('edit-action-message');
        if (!box) return;
        box.className = 'flink-action-message';
        box.textContent = '';
    }

    function cancelWorkerPolling() {
        if (editWorkerPollTimer) clearTimeout(editWorkerPollTimer);
        editWorkerPollTimer = null;
    }

    function restoreEditBrowserOperation(operation) {
        if (!operation) return;
        document.getElementById('edit-url').readOnly = operation.urlReadOnly;
        document.getElementById('edit-links-page').readOnly = operation.linksPageReadOnly;
        Object.keys(operation.buttonStates).forEach(function(buttonId) {
            var button = document.getElementById(buttonId);
            if (button && operation.buttonStates[buttonId] === false) button.disabled = false;
        });
    }

    function beginEditBrowserOperation() {
        if (editBrowserOperation) return null;
        var operation = {
            contextSequence: editContextSequence,
            urlReadOnly: document.getElementById('edit-url').readOnly,
            linksPageReadOnly: document.getElementById('edit-links-page').readOnly,
            buttonStates: {}
        };
        ['edit-fetch-btn', 'edit-check-btn', 'edit-save-btn', 'edit-delete-btn'].forEach(function(buttonId) {
            var button = document.getElementById(buttonId);
            if (!button) return;
            operation.buttonStates[buttonId] = button.disabled;
            button.disabled = true;
        });
        document.getElementById('edit-url').readOnly = true;
        document.getElementById('edit-links-page').readOnly = true;
        document.getElementById('edit-save-btn').disabled = true;
        editBrowserOperation = operation;
        return operation;
    }

    function endEditBrowserOperation(operation) {
        if (!operation || editBrowserOperation !== operation) return;
        editBrowserOperation = null;
        if (operation.contextSequence === editContextSequence) restoreEditBrowserOperation(operation);
    }

    function cancelEditBrowserOperation() {
        if (!editBrowserOperation) return;
        var operation = editBrowserOperation;
        editBrowserOperation = null;
        restoreEditBrowserOperation(operation);
    }

    function rememberEditFieldSnapshots() {
        editFieldSnapshots = {
            name: document.getElementById('edit-name').value.trim(),
            display_name: document.getElementById('edit-display-name').value.trim(),
            links_page: document.getElementById('edit-links-page').value.trim(),
            avatar: document.getElementById('edit-avatar').value.trim(),
            description: document.getElementById('edit-desc').value.trim()
        };
    }

    function workerErrorMessage(link) {
        var error = link && link.worker_error ? String(link.worker_error) : '';
        if (error === 'target_unreadable') return '<?php _e('目标网站暂时不可读，Worker 未能继续检测互链', 'argon'); ?>';
        if (error === 'links_page_not_found') return '<?php _e('Worker 未发现友链页面，请填写准确地址后重新检测', 'argon'); ?>';
        if (error === 'links_page_unreadable') return '<?php _e('Worker 无法读取友链页面，当前状态未知', 'argon'); ?>';
        if (error) return '<?php _e('Worker 返回错误', 'argon'); ?>: ' + error;
        if (link && !link.links_page) return '<?php _e('Worker 未发现友链页面，请填写准确地址后重新检测', 'argon'); ?>';
        return '<?php _e('Worker 无法读取友链页面，当前状态未知', 'argon'); ?>';
    }

    function renderEditWorkerStatus(link) {
        if (!link) return;
        var statusHtml = '';
        var workerStatus = link.worker_status || '';
        var workerAttempts = parseInt(link.worker_dispatch_attempts || 0, 10) || 0;
        var workerStatusText = '';
        var workerStatusClass = 'badge-secondary';
        if (workerStatus === 'pending_dispatch') {
            workerStatusText = '<?php _e('等待服务端派发', 'argon'); ?>';
        } else if (workerStatus === 'dispatching') {
            workerStatusText = '<?php _e('正在向 Worker 提交', 'argon'); ?>';
            workerStatusClass = 'badge-info';
        } else if (workerStatus === 'submitted') {
            workerStatusText = '<?php _e('任务已发出，Queue 通常 30～180 秒开始检测', 'argon'); ?>';
            workerStatusClass = 'badge-info';
        } else if (workerStatus === 'complete') {
            workerStatusText = '<?php _e('Worker 检测完成并已保存', 'argon'); ?>';
            workerStatusClass = 'badge-success';
        } else if (workerStatus === 'unknown') {
            workerStatusText = '<?php _e('Worker 已完成，结论未知', 'argon'); ?>';
            workerStatusClass = 'badge-warning';
        } else if (workerStatus === 'dispatch_failed') {
            workerStatusText = '<?php _e('任务派发失败，等待自动重试', 'argon'); ?>';
            workerStatusClass = 'badge-danger';
        } else if (workerStatus === 'error') {
            workerStatusText = '<?php _e('Worker 检测失败', 'argon'); ?>';
            workerStatusClass = 'badge-danger';
        }
        if (workerStatusText) {
            statusHtml += '<?php _e('检测流程', 'argon'); ?>: <span class="badge ' + workerStatusClass + '">' + workerStatusText + '</span>';
            if (workerAttempts) statusHtml += ' <small>(<?php _e('派发次数', 'argon'); ?>: ' + workerAttempts + ')</small>';
            statusHtml += '<br>';
        }

        var lastSiteCheck = link.worker_checked_at || link.last_check || 0;
        if (lastSiteCheck) {
            var date = new Date(lastSiteCheck * 1000);
            statusHtml += '<?php _e('上次 Worker 检测', 'argon'); ?>: ' + date.toLocaleString() + '<br>';
        }
        if (link.last_browser_check) {
            var browserDate = new Date(link.last_browser_check * 1000);
            statusHtml += '<?php _e('上次浏览器检测', 'argon'); ?>: ' + browserDate.toLocaleString() + '<br>';
        }
        if (link.last_backlink_attempt) {
            var backlinkDate = new Date(link.last_backlink_attempt * 1000);
            statusHtml += '<?php _e('上次互链尝试', 'argon'); ?>: ' + backlinkDate.toLocaleString() + '<br>';
        }
        if (link.accessible === false) {
            statusHtml += '<?php _e('网站状态', 'argon'); ?>: <span class="badge badge-danger"><?php _e('无法访问', 'argon'); ?></span><br>';
        } else if (link.accessible === true) {
            statusHtml += '<?php _e('网站状态', 'argon'); ?>: <span class="badge badge-success"><?php _e('正常', 'argon'); ?></span><br>';
        }

        var workerActive = workerStatus === 'pending_dispatch' || workerStatus === 'dispatching' || workerStatus === 'submitted';
        var backlinkResult = link.backlink_check_result || '';
        var resultSource = link.check_source === 'admin_browser'
            ? ' <small>(<?php _e('管理员浏览器', 'argon'); ?>)</small>'
            : '';
        if (backlinkResult === 'found' || link.has_backlink === true) {
            statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-success"><?php _e('已上友链', 'argon'); ?></span>' + resultSource;
        } else if (backlinkResult === 'missing' || link.has_backlink === false) {
            statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-warning"><?php _e('未检测到本站链接', 'argon'); ?></span>' + resultSource;
        } else if (workerActive) {
            statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-info"><?php _e('等待 Worker 检测结果', 'argon'); ?></span>';
        } else if (link.browser_backlink_result === 'unknown') {
            statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-secondary"><?php _e('浏览器受跨域限制，结论未改变', 'argon'); ?></span>';
        } else if (link.worker_error === 'target_unreadable') {
            statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-secondary"><?php _e('目标网站暂时不可读', 'argon'); ?></span>';
        } else if (!link.links_page) {
            var missingPageText = workerStatus === 'complete' || workerStatus === 'unknown' || workerStatus === 'error'
                ? '<?php _e('Worker 未发现友链页面', 'argon'); ?>'
                : '<?php _e('未填写友链页面', 'argon'); ?>';
            statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-secondary">' + missingPageText + '</span>';
        } else if (link.backlink_check_result === 'unknown') {
            statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-secondary"><?php _e('Worker 无法读取友链页面', 'argon'); ?></span>';
        }
        document.getElementById('edit-status-info').innerHTML = statusHtml || '<?php _e('暂无检测记录', 'argon'); ?>';
    }

    function applyWorkerSnapshot(link, forceDetectedFields) {
        if (!link) return;
        var acceptDetectedFields = forceDetectedFields === true;
        if ((acceptDetectedFields || editNameSource === 'worker') && link.name_source === 'worker' && link.name) {
            document.getElementById('edit-name').value = link.name;
            editFieldSnapshots.name = link.name;
            if (acceptDetectedFields) editNameSource = 'worker';
        }
        if ((acceptDetectedFields || editDisplayNameSource === 'worker') && link.display_name_source === 'worker') {
            document.getElementById('edit-display-name').value = link.display_name || '';
            editFieldSnapshots.display_name = link.display_name || '';
            if (acceptDetectedFields) editDisplayNameSource = 'worker';
        }
        if ((acceptDetectedFields || editAvatarSource === 'worker') && link.avatar_source === 'worker') {
            document.getElementById('edit-avatar').value = link.avatar || '';
            editFieldSnapshots.avatar = link.avatar || '';
            if (acceptDetectedFields) editAvatarSource = 'worker';
        }
        if ((acceptDetectedFields || editDescriptionSource === 'worker') && link.description_source === 'worker') {
            document.getElementById('edit-desc').value = link.description || link.auto_description || '';
            editFieldSnapshots.description = link.description || link.auto_description || '';
            if (acceptDetectedFields) editDescriptionSource = 'worker';
        }
        if ((acceptDetectedFields || editLinksPageSource === 'worker') && link.links_page_source === 'worker') {
            document.getElementById('edit-links-page').value = link.links_page || '';
            editFieldSnapshots.links_page = link.links_page || '';
            if (acceptDetectedFields) editLinksPageSource = 'worker';
        }
    }

    function pollWorkerDetection(id, contextSequence, backlinkSequence, startedAt) {
        cancelWorkerPolling();
        var poll = function() {
            flinkAction('get', { id: id, sync_worker_result: '1' }, function(res) {
                if (contextSequence !== editContextSequence || backlinkSequence !== editBacklinkSequence || document.getElementById('edit-id').value !== id) return;
                var checkBtn = document.getElementById('edit-check-btn');
                if (!res.success || !res.data) {
                    if (checkBtn) { checkBtn.disabled = false; checkBtn.textContent = '<?php _e('检测互链', 'argon'); ?>'; }
                    setMessage('error', res.message || '<?php _e('无法读取 Worker 检测状态，请稍后重试', 'argon'); ?>');
                    return;
                }

                var link = res.data;
                applyWorkerSnapshot(link);
                renderEditWorkerStatus(link);
                var workerStatus = link.worker_status || '';
                var terminal = workerStatus === 'complete' || workerStatus === 'unknown' || workerStatus === 'error' || workerStatus === 'dispatch_failed';
                if (terminal) {
                    if (checkBtn) { checkBtn.disabled = false; checkBtn.textContent = '<?php _e('重新检测', 'argon'); ?>'; }
                    if (workerStatus === 'complete' && link.has_backlink === true) {
                        setMessage('success', '<?php _e('Worker 检测完成：已发现本站链接，结果已自动保存', 'argon'); ?>');
                    } else if (workerStatus === 'complete' && link.has_backlink === false) {
                        setMessage('info', '<?php _e('Worker 检测完成：未检测到本站链接，结果已自动保存', 'argon'); ?>');
                    } else {
                        setMessage(workerStatus === 'dispatch_failed' || workerStatus === 'error' ? 'error' : 'info', workerErrorMessage(link));
                    }
                    return;
                }
                if (Date.now() - startedAt >= 300000) {
                    if (checkBtn) { checkBtn.disabled = false; checkBtn.textContent = '<?php _e('重新检测', 'argon'); ?>'; }
                    var syncError = link.worker_result_sync_error ? String(link.worker_result_sync_error) : '';
                    setMessage('info', syncError
                        ? '<?php _e('Worker 结果暂未同步成功，后台会继续主动拉取', 'argon'); ?>: ' + syncError
                        : '<?php _e('Worker 任务仍在处理，后台会继续主动拉取结果', 'argon'); ?>');
                    return;
                }
                editWorkerPollTimer = setTimeout(poll, 1200);
            });
        };
        editWorkerPollTimer = setTimeout(poll, 500);
    }

    function normalizeUrlInput(value) {
        value = (value || '').trim();
        if (value && !/^https?:\/\//i.test(value)) value = 'https://' + value;
        return value;
    }

    var addNameInput = document.getElementById('add-name');
    var addDisplayNameInput = document.getElementById('add-display-name');
    var addAvatarInput = document.getElementById('add-avatar');
    var addDescriptionInput = document.getElementById('add-desc');
    var addLinksPageInput = document.getElementById('add-links-page');
    var editNameInput = document.getElementById('edit-name');
    var editDisplayNameInput = document.getElementById('edit-display-name');
    var editAvatarInput = document.getElementById('edit-avatar');
    var editDescriptionInput = document.getElementById('edit-desc');
    var editLinksPageInput = document.getElementById('edit-links-page');
    if (addNameInput) addNameInput.addEventListener('input', function() { addNameIsManual = this.value.trim() !== ''; });
    if (addDisplayNameInput) addDisplayNameInput.addEventListener('input', function() { addDisplayNameIsManual = this.value.trim() !== ''; });
    if (addAvatarInput) addAvatarInput.addEventListener('input', function() { addAvatarIsManual = this.value.trim() !== ''; });
    if (addDescriptionInput) addDescriptionInput.addEventListener('input', function() { addDescriptionIsManual = this.value.trim() !== ''; });
    if (addLinksPageInput) addLinksPageInput.addEventListener('input', function() { addLinksPageIsManual = this.value.trim() !== ''; });
    if (editNameInput) editNameInput.addEventListener('input', function() { editNameSource = this.value.trim() ? 'admin' : 'worker'; });
    if (editDisplayNameInput) editDisplayNameInput.addEventListener('input', function() { editDisplayNameSource = this.value.trim() ? 'admin' : 'worker'; });
    if (editAvatarInput) editAvatarInput.addEventListener('input', function() { editAvatarSource = this.value.trim() ? 'admin' : 'worker'; });
    if (editDescriptionInput) editDescriptionInput.addEventListener('input', function() { editDescriptionSource = this.value.trim() ? 'admin' : 'worker'; });
    if (editLinksPageInput) editLinksPageInput.addEventListener('input', function() { editLinksPageSource = this.value.trim() ? 'admin' : 'worker'; });
    
    function flinkAction(action, data, callback) {
        data.flink_action = action;
        data.flink_nonce = nonce;
        var fd = new FormData();
        for (var k in data) fd.append(k, data[k]);
        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) {
                return r.json().catch(function() {
                    throw new Error('<?php _e('服务器返回格式异常', 'argon'); ?>');
                });
            })
            .then(callback)
            .catch(function(e) {
                if (callback) callback({ success: false, message: e && e.message ? e.message : '<?php _e('操作失败', 'argon'); ?>' });
            });
    }

    function setAddDetectStatus(message) {
        var status = document.getElementById('add-detect-status');
        if (status) status.textContent = message || '';
    }

    function detectAddSite(button) {
        var urlInput = document.getElementById('add-url');
        var url = normalizeUrlInput(urlInput ? urlInput.value : '');
        if (!url) return Promise.resolve(false);
        if (urlInput) urlInput.value = url;
        if (!detector) {
            setAddDetectStatus('<?php _e('浏览器检测模块加载失败，请刷新页面重试', 'argon'); ?>');
            return Promise.resolve(false);
        }
        var sequence = ++addDetectSequence;
        if (button) { button.disabled = true; button.textContent = '...'; }
        setAddDetectStatus('<?php _e('正在由浏览器探测主站、www 与 blog 域名...', 'argon'); ?>');
        var requestWorkerPreview = function() {
            setAddDetectStatus('<?php _e('浏览器受跨域限制，正在由 Worker 直接探测...', 'argon'); ?>');
            return new Promise(function(resolve) {
                flinkAction('browser_probe_preview', { target_url: url }, function(result) {
                    resolve(result || { success: false });
                });
            });
        };
        var applyResult = function(result) {
            if (sequence !== addDetectSequence) return false;
            addBrowserResult = result && result.success && result.readable ? result : null;
            if (!addBrowserResult) {
                setAddDetectStatus(result && result.message
                    ? result.message
                    : '<?php _e('暂时无法读取候选站点，请检查地址或稍后重试', 'argon'); ?>');
                return false;
            }
            if (result.url) urlInput.value = result.url;
            var info = result.info || {};
            if (!addNameIsManual && info.primary) addNameInput.value = info.primary;
            if (!addDisplayNameIsManual && info.subtitle) addDisplayNameInput.value = info.subtitle;
            if (!addDescriptionIsManual && info.description) addDescriptionInput.value = info.description.slice(0, 100);
            if (!addAvatarIsManual && info.avatar) addAvatarInput.value = info.avatar;
            if (!addLinksPageIsManual && info.linksPage) addLinksPageInput.value = info.linksPage;
            var sourceLabel = info.source === 'argon'
                ? 'Argon'
                : (info.source === 'wordpress' ? 'WordPress' : '<?php _e('通用网页', 'argon'); ?>');
            setAddDetectStatus((result.transport === 'worker_sync'
                ? '<?php _e('Worker 已自动识别站点资料', 'argon'); ?>'
                : '<?php _e('浏览器已自动识别站点资料', 'argon'); ?>') + ' · ' + sourceLabel);
            return true;
        };
        return detector.detectSite(url).then(function(result) {
            if (sequence !== addDetectSequence) return false;
            if (result && result.success && result.readable) return result;
            return requestWorkerPreview();
        }).then(applyResult).catch(function(error) {
            if (sequence !== addDetectSequence) return false;
            return requestWorkerPreview().then(applyResult).catch(function() {
                addBrowserResult = null;
                setAddDetectStatus(error && error.message
                    ? error.message
                    : '<?php _e('站点探测失败，请稍后重试', 'argon'); ?>');
                return false;
            });
        }).then(function(success) {
            if (button) { button.disabled = false; button.textContent = '<?php _e('获取', 'argon'); ?>'; }
            return success;
        });
    }

    // 移除自动审核标记
    window.removeAutoTag = function(el) {
        if (!confirm('<?php _e('确定移除自动审核标记？', 'argon'); ?>')) return;
        var id = el.getAttribute('data-id');
        flinkAction('update', { id: id, auto_approved: '' }, function(res) {
            if (res.success) el.remove();
            else alert(res.message || '<?php _e('操作失败', 'argon'); ?>');
        });
    };
    
    // 获取网站信息（当前浏览器直连目标站）
    var fetchInfoBtn = document.getElementById('fetch-info');
    if (fetchInfoBtn) {
        fetchInfoBtn.addEventListener('click', function() {
            var url = document.getElementById('add-url').value.trim();
            if (!url) return alert('<?php _e('请输入网址', 'argon'); ?>');
            detectAddSite(this);
        });
    }

    var addUrlInput = document.getElementById('add-url');
    var addDetectTimer = null;
    if (addUrlInput) {
        addUrlInput.addEventListener('input', function() {
            addDetectSequence++;
            addBrowserResult = null;
            setAddDetectStatus('');
            if (!addNameIsManual) addNameInput.value = '';
            if (!addDisplayNameIsManual) addDisplayNameInput.value = '';
            if (!addAvatarIsManual) addAvatarInput.value = '';
            if (!addDescriptionIsManual) addDescriptionInput.value = '';
            if (!addLinksPageIsManual) addLinksPageInput.value = '';
            if (addDetectTimer) clearTimeout(addDetectTimer);
            addDetectTimer = setTimeout(function() { detectAddSite(null); }, 650);
        });
        addUrlInput.addEventListener('blur', function() {
            if (addDetectTimer) clearTimeout(addDetectTimer);
            detectAddSite(null);
        });
    }

    // 添加友链
    var addLinkBtn = document.getElementById('add-link-btn');
    if (addLinkBtn) {
        addLinkBtn.addEventListener('click', function() {
            var name = document.getElementById('add-name').value.trim();
            var nameSource = addNameIsManual ? 'admin' : 'worker';
            var url = normalizeUrlInput(document.getElementById('add-url').value);
            if (!url) return alert('<?php _e('请填写网址', 'argon'); ?>');
            document.getElementById('add-url').value = url;
            if (!name) {
                name = document.getElementById('add-display-name').value.trim();
                if (!name) return alert('<?php _e('请填写名称', 'argon'); ?>');
                if (addDisplayNameIsManual) nameSource = 'admin';
            }
            this.disabled = true; this.textContent = '<?php _e('添加中...', 'argon'); ?>';
            var btn = this;
            flinkAction('add', {
                name: name,
                auto_name: addNameIsManual ? '0' : '1',
                name_source: nameSource,
                url: url,
                display_name: document.getElementById('add-display-name').value.trim(),
                user_display_name: addDisplayNameIsManual ? '1' : '0',
                display_name_source: addDisplayNameIsManual ? 'admin' : 'worker',
                browser_detected_url: addBrowserResult && addBrowserResult.url ? addBrowserResult.url : '',
                links_page: document.getElementById('add-links-page').value.trim(),
                links_page_source: addLinksPageIsManual ? 'admin' : 'worker',
                avatar: document.getElementById('add-avatar').value.trim(),
                user_avatar: addAvatarIsManual ? '1' : '0',
                avatar_source: addAvatarIsManual ? 'admin' : 'worker',
                description: addDescriptionIsManual ? document.getElementById('add-desc').value.trim() : '',
                auto_description: addDescriptionIsManual ? '' : document.getElementById('add-desc').value.trim(),
                description_source: addDescriptionIsManual ? 'admin' : 'worker',
                category: document.getElementById('add-category').value.trim()
            }, function(res) {
                if (res.success && res.id) {
                    location.reload();
                }
                else { btn.disabled = false; btn.textContent = '<?php _e('添加', 'argon'); ?>'; alert(res.message || '<?php _e('添加失败', 'argon'); ?>'); }
            });
        });
    }

    // 审核操作
    document.querySelectorAll('.flink-approve').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var button = this;
            this.disabled = true;
            flinkAction('approve', { id: id }, function(res) {
                if (res.success) location.reload();
                else { button.disabled = false; alert(res.message || '<?php _e('操作失败', 'argon'); ?>'); }
            });
        });
    });
    document.querySelectorAll('.flink-reject').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('<?php _e('确定拒绝？', 'argon'); ?>')) return;
            var id = this.dataset.id;
            var button = this;
            this.disabled = true;
            flinkAction('reject', { id: id }, function(res) {
                if (res.success) location.reload();
                else { button.disabled = false; alert(res.message || '<?php _e('操作失败', 'argon'); ?>'); }
            });
        });
    });

    // 管理页只请求 WordPress；由服务端非阻塞提交到 Worker，页面不再转发签名任务。
    var checkAllBtn = document.getElementById('check-all-btn');
    if (checkAllBtn) {
        checkAllBtn.onclick = function() {
            if (!confirm('<?php _e('将通过 Cloudflare Worker 异步检测最多 3 条友链，确定继续？', 'argon'); ?>')) return;
            checkAllBtn.disabled = true;
            checkAllBtn.setAttribute('aria-busy', 'true');
            var checkAllIcon = checkAllBtn.querySelector('i');
            if (checkAllIcon) checkAllIcon.className = 'fa fa-refresh fa-spin';
            flinkAction('check_all', {}, function(res) {
                checkAllBtn.disabled = false;
                checkAllBtn.removeAttribute('aria-busy');
                if (checkAllIcon) checkAllIcon.className = 'fa fa-refresh';
                alert(res.message || (res.success ? '<?php _e('已提交 Worker 检测任务', 'argon'); ?>' : '<?php _e('提交失败', 'argon'); ?>'));
                if (res.success) location.reload();
            });
        };
    }

    // 编辑弹窗
    window.openEditModal = function(id) {
        cancelEditBrowserOperation();
        cancelWorkerPolling();
        var contextSequence = ++editContextSequence;
        editDetectSequence++;
        editBacklinkSequence++;
        var modal = document.getElementById('edit-modal');
        document.getElementById('edit-id').value = id;
        ['edit-name', 'edit-display-name', 'edit-url', 'edit-links-page', 'edit-avatar', 'edit-desc', 'edit-category'].forEach(function(fieldId) {
            var field = document.getElementById(fieldId);
            if (field) field.value = '';
        });
        document.getElementById('edit-status-info').textContent = '<?php _e('正在加载友链信息...', 'argon'); ?>';
        clearMessage();
        ['edit-fetch-btn', 'edit-check-btn', 'edit-delete-btn', 'edit-save-btn'].forEach(function(buttonId) {
            var button = document.getElementById(buttonId);
            if (button) button.disabled = true;
        });
        if (modal) window.ArgonMotion.fast(modal, true, { className: 'show' });
        flinkAction('get', { id: id }, function(res) {
            if (contextSequence !== editContextSequence) return;
            if (res.success && res.data) {
                var link = res.data;
                clearMessage();
                document.getElementById('edit-id').value = link.id;
                document.getElementById('edit-name').value = link.name || '';
                editNameSource = link.name_source || (link.auto_name ? 'worker' : 'visitor');
                document.getElementById('edit-display-name').value = link.display_name || '';
                editDisplayNameSource = link.display_name_source || (link.user_display_name ? 'visitor' : 'worker');
                document.getElementById('edit-url').value = link.url || '';
                document.getElementById('edit-links-page').value = link.links_page || '';
                editLinksPageSource = link.links_page_source || (link.links_page ? 'visitor' : 'worker');
                document.getElementById('edit-avatar').value = link.avatar || '';
                editAvatarSource = link.avatar_source || (link.user_avatar ? 'visitor' : 'worker');
                document.getElementById('edit-desc').value = link.description || link.auto_description || '';
                editDescriptionSource = link.description_source || (link.description ? 'visitor' : 'worker');
                document.getElementById('edit-category').value = link.category || '';
                document.getElementById('edit-hide-no-backlink').value = link.hide_no_backlink || '';
                rememberEditFieldSnapshots();
                document.getElementById('edit-fetch-btn').disabled = false;
                document.getElementById('edit-fetch-btn').textContent = '<?php _e('更新信息', 'argon'); ?>';
                document.getElementById('edit-check-btn').disabled = false;
                document.getElementById('edit-check-btn').textContent = '<?php _e('检测互链', 'argon'); ?>';
                document.getElementById('edit-delete-btn').disabled = false;
                document.getElementById('edit-save-btn').disabled = false;

                renderEditWorkerStatus(link);
                if (link.worker_status === 'pending_dispatch' || link.worker_status === 'dispatching' || link.worker_status === 'submitted') {
                    document.getElementById('edit-check-btn').disabled = true;
                    document.getElementById('edit-check-btn').textContent = '<?php _e('检测中...', 'argon'); ?>';
                    var workerStartedAt = (parseInt(link.worker_submitted_at || link.worker_dispatched_at || 0, 10) || 0) * 1000;
                    if (!workerStartedAt || workerStartedAt > Date.now()) workerStartedAt = Date.now();
                    pollWorkerDetection(id, contextSequence, editBacklinkSequence, workerStartedAt);
                }

            } else {
                document.getElementById('edit-status-info').textContent = '<?php _e('友链信息加载失败', 'argon'); ?>';
                setMessage('error', res.message || '<?php _e('友链不存在或已被删除，请刷新页面后重试', 'argon'); ?>');
            }
        });
    };
    
    window.closeEditModal = function() {
        cancelEditBrowserOperation();
        cancelWorkerPolling();
        editContextSequence++;
        editDetectSequence++;
        editBacklinkSequence++;
        window.ArgonMotion.fast(document.getElementById('edit-modal'), false, { className: 'show' });
        clearMessage();
    };

    var editModal = document.getElementById('edit-modal');
    if (editModal) {
        editModal.onclick = function(e) {
            if (e.target === this && window.ArgonMotion.isOutsideClick(e, this.querySelector('.flink-modal'))) {
                closeEditModal();
            }
        };
    }

    // 更新信息（当前浏览器探测并立即保存自动字段）
    var editFetchBtn = document.getElementById('edit-fetch-btn');
    if (editFetchBtn) {
        editFetchBtn.addEventListener('click', function() {
            var url = normalizeUrlInput(document.getElementById('edit-url').value);
            if (!url) return alert('<?php _e('请先填写网址', 'argon'); ?>');
            if (!detector) return setMessage('error', '<?php _e('浏览器检测模块加载失败，请刷新页面重试', 'argon'); ?>');
            clearMessage();
            var operation = beginEditBrowserOperation();
            if (!operation) return;
            this.disabled = true; this.textContent = '<?php _e('获取中...', 'argon'); ?>';
            var btn = this;
            var editId = document.getElementById('edit-id').value;
            var contextSequence = editContextSequence;
            var detectSequence = ++editDetectSequence;
            var finishInfoProbe = function(saveResponse, usedWorkerProxy) {
                if (contextSequence !== editContextSequence || detectSequence !== editDetectSequence || document.getElementById('edit-id').value !== editId) return;
                endEditBrowserOperation(operation);
                btn.disabled = false; btn.textContent = '<?php _e('更新信息', 'argon'); ?>';
                if (!saveResponse || !saveResponse.success) {
                    setMessage('error', saveResponse && saveResponse.message ? saveResponse.message : '<?php _e('站点资料探测或保存失败', 'argon'); ?>');
                    return;
                }
                if (saveResponse.data) {
                    applyWorkerSnapshot(saveResponse.data, true);
                    renderEditWorkerStatus(saveResponse.data);
                    rememberEditFieldSnapshots();
                }
                if (saveResponse.probe_readable === false) {
                    setMessage('info', '<?php _e('目标站暂时不可读，旧资料已保留且未被覆盖', 'argon'); ?>');
                    return;
                }
                setMessage('success', usedWorkerProxy
                    ? '<?php _e('目标站不允许浏览器跨域读取；Worker 已直接抓取首页并保存资料', 'argon'); ?>'
                    : '<?php _e('浏览器已直接读取首页并保存站点资料', 'argon'); ?>');
            };
            detector.detectSite(url).then(function(result) {
                if (contextSequence !== editContextSequence || detectSequence !== editDetectSequence || document.getElementById('edit-id').value !== editId) return;
                if (!result || !result.success || !result.readable) {
                    flinkAction('browser_probe', {
                        id: editId,
                        mode: 'info',
                        target_url: url
                    }, function(saveResponse) { finishInfoProbe(saveResponse, true); });
                    return;
                }
                var info = result.info || {};
                if (editNameSource === 'worker' && info.primary) document.getElementById('edit-name').value = info.primary;
                if (editDisplayNameSource === 'worker' && info.subtitle) document.getElementById('edit-display-name').value = info.subtitle;
                if (editAvatarSource === 'worker' && info.avatar) document.getElementById('edit-avatar').value = info.avatar;
                if (editDescriptionSource === 'worker' && info.description) document.getElementById('edit-desc').value = info.description.slice(0, 100);
                if (editLinksPageSource === 'worker' && info.linksPage) document.getElementById('edit-links-page').value = info.linksPage;
                flinkAction('browser_result', {
                    id: editId,
                    mode: 'info',
                    target_url: url,
                    accessible: result.success ? '1' : '0',
                    readable: result.readable ? '1' : '0',
                    detected_url: result.url || '',
                    primary: info.primary || '',
                    subtitle: info.subtitle || '',
                    description: info.description || '',
                    avatar: info.avatar || '',
                    links_page: info.linksPage || '',
                    links_page_source: 'worker',
                    detection_source: info.source === 'argon' || info.source === 'wordpress'
                        ? info.source
                        : 'html',
                    browser_error: '',
                    browser_transport: 'direct'
                }, function(saveResponse) { finishInfoProbe(saveResponse, false); });
            }).catch(function(error) {
                if (contextSequence !== editContextSequence || detectSequence !== editDetectSequence || document.getElementById('edit-id').value !== editId) return;
                endEditBrowserOperation(operation);
                btn.disabled = false; btn.textContent = '<?php _e('更新信息', 'argon'); ?>';
                setMessage('error', error && error.message ? error.message : '<?php _e('浏览器探测失败', 'argon'); ?>');
            });
        });
    }

    // 检测互链（浏览器直读优先，必要时由 Worker 同步兜底；自动巡检仍由 Worker 定时执行）
    var editCheckBtn = document.getElementById('edit-check-btn');
    if (editCheckBtn) {
        editCheckBtn.addEventListener('click', function() {
            var linksPage = normalizeUrlInput(document.getElementById('edit-links-page').value);
            if (linksPage) document.getElementById('edit-links-page').value = linksPage;
            var siteUrl = normalizeUrlInput(document.getElementById('edit-url').value);
            if (!siteUrl) return alert('<?php _e('请先填写网址', 'argon'); ?>');
            if (!detector) return setMessage('error', '<?php _e('浏览器检测模块加载失败，请刷新页面重试', 'argon'); ?>');
            var id = document.getElementById('edit-id').value;
            var contextSequence = editContextSequence;
            var linksPageSource = editLinksPageSource;
            clearMessage();
            var operation = beginEditBrowserOperation();
            if (!operation) return;
            var backlinkSequence = ++editBacklinkSequence;
            this.disabled = true; this.textContent = '<?php _e('浏览器检测中...', 'argon'); ?>';
            var btn = this;
            var finishBacklinkProbe = function(saveResponse, backlinkState, usedWorkerProxy, confirmedPage) {
                if (contextSequence !== editContextSequence || backlinkSequence !== editBacklinkSequence || document.getElementById('edit-id').value !== id) return;
                endEditBrowserOperation(operation);
                btn.disabled = false;
                btn.textContent = '<?php _e('重新检测', 'argon'); ?>';
                if (!saveResponse || !saveResponse.success) {
                    setMessage('error', saveResponse && saveResponse.message ? saveResponse.message : '<?php _e('互链结果探测或保存失败', 'argon'); ?>');
                    return;
                }
                if (saveResponse.data) {
                    applyWorkerSnapshot(saveResponse.data);
                    renderEditWorkerStatus(saveResponse.data);
                    rememberEditFieldSnapshots();
                    backlinkState = saveResponse.data.browser_backlink_result || backlinkState;
                }
                var transportLabel = usedWorkerProxy
                    ? '<?php _e('Worker 已直接读取对方页面。', 'argon'); ?>'
                    : '<?php _e('浏览器已直接读取对方页面。', 'argon'); ?>';
                if (backlinkState === 'found') {
                    setMessage('success', transportLabel + ' <?php _e('已发现本站链接，结果已保存', 'argon'); ?>');
                } else if (backlinkState === 'missing') {
                    setMessage('info', transportLabel + ' <?php _e('未检测到本站链接，结果已保存', 'argon'); ?>');
                } else if (!confirmedPage) {
                    setMessage('info', '<?php _e('未发现可确认的友链页面，已保留此前互链结论', 'argon'); ?>');
                } else {
                    setMessage('info', '<?php _e('对方页面暂时不可读，已保留此前互链结论', 'argon'); ?>');
                }
            };
            var targetPromise = linksPage
                ? Promise.resolve({ probeUrl: linksPage, linksPage: linksPage, confirmedPage: true, needsProxy: false })
                : detector.detectSite(siteUrl).then(function(siteResult) {
                    var detectedLinksPage = siteResult && siteResult.info ? siteResult.info.linksPage : '';
                    if (detectedLinksPage && editLinksPageSource === 'worker') {
                        document.getElementById('edit-links-page').value = detectedLinksPage;
                    }
                    return {
                        probeUrl: detectedLinksPage || '',
                        linksPage: detectedLinksPage || '',
                        confirmedPage: !!detectedLinksPage,
                        needsProxy: !siteResult || !siteResult.success || !siteResult.readable
                    };
                }).catch(function() { return { probeUrl: '', linksPage: '', confirmedPage: false, needsProxy: true }; });

            targetPromise.then(function(target) {
                if (target.needsProxy) {
                    return { useProxy: true, linksPage: target.linksPage || linksPage || '', confirmedPage: target.confirmedPage };
                }
                if (!target.confirmedPage) {
                    return {
                        useProxy: true,
                        linksPage: '',
                        confirmedPage: false,
                        error: 'links_page_not_found'
                    };
                }
                return detector.checkBacklinkDetailed(target.probeUrl, argonFriendLinkHomeUrl).then(function(result) {
                    return {
                        state: result.state,
                        linksPage: target.linksPage,
                        checkedUrl: result.checkedUrl || '',
                        confirmedPage: target.confirmedPage,
                        needsProxy: result.needsProxy,
                        error: result.error || ''
                    };
                });
            }).then(function(browserResult) {
                if (contextSequence !== editContextSequence || backlinkSequence !== editBacklinkSequence || document.getElementById('edit-id').value !== id) return;
                if (browserResult.useProxy || browserResult.needsProxy) {
                    var proxyLinksPage = browserResult.linksPage || linksPage || '';
                    flinkAction('browser_probe', {
                        id: id,
                        mode: 'backlink',
                        target_url: siteUrl,
                        links_page: proxyLinksPage,
                        links_page_source: linksPageSource
                    }, function(saveResponse) {
                        var savedState = saveResponse && saveResponse.data
                            ? (saveResponse.data.browser_backlink_result || 'unknown')
                            : 'unknown';
                        finishBacklinkProbe(saveResponse, savedState, true, Boolean(proxyLinksPage || (saveResponse && saveResponse.data && saveResponse.data.links_page)));
                    });
                    return;
                }
                var backlinkState = browserResult.state === 'found' || browserResult.state === 'missing'
                    ? browserResult.state
                    : 'unknown';
                flinkAction('browser_result', {
                    id: id,
                    mode: 'backlink',
                    target_url: siteUrl,
                    checked_url: browserResult.checkedUrl || '',
                    accessible: backlinkState === 'unknown' ? '0' : '1',
                    readable: backlinkState === 'unknown' ? '0' : '1',
                    links_page: browserResult.linksPage || '',
                    links_page_source: linksPageSource,
                    backlink_state: backlinkState,
                    browser_error: backlinkState === 'unknown' ? (browserResult.error || 'links_page_unreadable') : '',
                    browser_transport: 'direct'
                }, function(saveResponse) {
                    finishBacklinkProbe(saveResponse, backlinkState, false, browserResult.confirmedPage);
                });
            }).catch(function(error) {
                if (contextSequence !== editContextSequence || backlinkSequence !== editBacklinkSequence || document.getElementById('edit-id').value !== id) return;
                endEditBrowserOperation(operation);
                btn.disabled = false;
                btn.textContent = '<?php _e('检测互链', 'argon'); ?>';
                setMessage('error', error && error.message ? error.message : '<?php _e('浏览器互链检测失败', 'argon'); ?>');
            });
        });
    }

    // 保存编辑
    var editSaveBtn = document.getElementById('edit-save-btn');
    if (editSaveBtn) {
        editSaveBtn.addEventListener('click', function() {
            var id = document.getElementById('edit-id').value;
            var name = document.getElementById('edit-name').value.trim();
            var url = normalizeUrlInput(document.getElementById('edit-url').value);
            if (!name || !url) return alert('<?php _e('名称和网址不能为空', 'argon'); ?>');
            document.getElementById('edit-url').value = url;

            clearMessage();
            this.disabled = true; this.textContent = '<?php _e('保存中...', 'argon'); ?>';
            var btn = this;
            var displayName = document.getElementById('edit-display-name').value.trim();
            var linksPage = document.getElementById('edit-links-page').value.trim();
            var avatar = document.getElementById('edit-avatar').value.trim();
            var description = document.getElementById('edit-desc').value.trim();
            var updateData = {
                id: id,
                url: url,
                category: document.getElementById('edit-category').value.trim(),
                hide_no_backlink: document.getElementById('edit-hide-no-backlink').value
            };
            if (editNameSource !== 'worker' || name !== editFieldSnapshots.name) {
                updateData.name = name;
                updateData.auto_name = editNameSource === 'worker' ? '1' : '0';
                updateData.name_source = editNameSource;
            }
            if (editDisplayNameSource !== 'worker' || displayName !== editFieldSnapshots.display_name) {
                updateData.display_name = displayName;
                updateData.user_display_name = displayName && editDisplayNameSource !== 'worker' ? '1' : '0';
                updateData.display_name_source = displayName ? editDisplayNameSource : 'worker';
            }
            if (editLinksPageSource !== 'worker' || linksPage !== editFieldSnapshots.links_page) {
                updateData.links_page = linksPage;
                updateData.links_page_source = linksPage ? editLinksPageSource : 'worker';
            }
            if (editAvatarSource !== 'worker' || avatar !== editFieldSnapshots.avatar) {
                updateData.avatar = avatar;
                updateData.user_avatar = avatar && editAvatarSource !== 'worker' ? '1' : '0';
                updateData.avatar_source = avatar ? editAvatarSource : 'worker';
            }
            if (editDescriptionSource !== 'worker' || description !== editFieldSnapshots.description) {
                updateData.description = description && editDescriptionSource !== 'worker' ? description : '';
                updateData.auto_description = description && editDescriptionSource === 'worker' ? description : '';
                updateData.description_source = description ? editDescriptionSource : 'worker';
            }
            flinkAction('update', updateData, function(res) {
                btn.disabled = false; btn.textContent = '<?php _e('保存', 'argon'); ?>';
                if (res.success) {
                    if (res.worker_refresh_required && res.worker_dispatched === false) {
                        setMessage('info', '<?php _e('地址已保存；Worker 任务暂未送达，服务端会自动重试', 'argon'); ?>');
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        if (res.worker_refresh_required && !argonFriendLinkWorkerReady) {
                            res.message = '<?php _e('地址已保存；配置 Worker 后可重新检测', 'argon'); ?>';
                        }
                        setMessage('success', res.message || '<?php _e('已保存友链信息', 'argon'); ?>');
                        setTimeout(function() { location.reload(); }, 450);
                    }
                } else {
                    setMessage('error', res.message || '<?php _e('保存失败，请刷新页面后重试', 'argon'); ?>');
                }
            });
        });
    }

    // 删除
    var editDeleteBtn = document.getElementById('edit-delete-btn');
    if (editDeleteBtn) {
        editDeleteBtn.addEventListener('click', function() {
            if (!confirm('<?php _e('确定删除这个友链？', 'argon'); ?>')) return;
            var id = document.getElementById('edit-id').value;
            this.disabled = true; this.textContent = '<?php _e('删除中...', 'argon'); ?>';
            var btn = this;
            flinkAction('delete', { id: id }, function(res) {
                if (res.success) location.reload();
                else { btn.disabled = false; btn.textContent = '<?php _e('删除', 'argon'); ?>'; setMessage('error', res.message || '<?php _e('删除失败', 'argon'); ?>'); }
            });
        });
    }
})();
<?php endif; ?>
</script>

<?php
argon_special_page_surface_close();
get_footer();
?>
