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
                'display_name' => isset($_POST['display_name']) ? $_POST['display_name'] : '',
                'url' => isset($_POST['url']) ? $_POST['url'] : '',
                'avatar' => isset($_POST['avatar']) ? $_POST['avatar'] : '',
                'user_avatar' => isset($_POST['user_avatar']) && $_POST['user_avatar'] === '1',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'auto_description' => isset($_POST['auto_description']) ? $_POST['auto_description'] : '',
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
            if (!argon_get_friend_link($id)) {
                $response = array('success' => false, 'message' => __('友链不存在或已被删除，请刷新页面后重试', 'argon'));
                break;
            }
            $data = array();
            foreach (array('name', 'display_name', 'url', 'links_page', 'avatar', 'description', 'auto_description', 'category', 'hide_no_backlink', 'auto_approved') as $f) {
                if (isset($_POST[$f])) $data[$f] = $_POST[$f];
            }
            if (isset($_POST['user_avatar'])) {
                $data['user_avatar'] = $_POST['user_avatar'] === '1';
            } elseif (array_key_exists('avatar', $data)) {
                $avatar = trim((string)$data['avatar']);
                $existing_avatar = isset($existing_link['avatar']) ? trim((string)$existing_link['avatar']) : '';
                $data['user_avatar'] = $avatar !== '' && ($avatar !== $existing_avatar || !empty($existing_link['user_avatar']));
            }
            if (empty($data)) {
                $response = array('success' => true, 'message' => __('没有需要保存的变化', 'argon'));
                break;
            }
            $updated = argon_update_friend_link($id, $data);
            $response = array('success' => $updated, 'message' => $updated ? __('已保存友链信息', 'argon') : __('保存失败，请刷新页面后重试', 'argon'));
            break;
            
        case 'get':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $link = argon_get_friend_link($id);
            $response = $link ? array('success' => true, 'data' => $link) : array('success' => false);
            break;
            
        case 'check_backlink':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $link = argon_get_friend_link($id);
            if ($link) {
                $raw_links_page = isset($_POST['links_page']) ? trim(wp_unslash($_POST['links_page'])) : '';
                if ($raw_links_page !== '' && !preg_match('/^https?:\/\//i', $raw_links_page)) {
                    $raw_links_page = 'https://' . $raw_links_page;
                }
                $links_page = $raw_links_page !== '' ? esc_url_raw($raw_links_page) : (!empty($link['links_page']) ? $link['links_page'] : '');
                if (empty($links_page)) {
                    $response = array('success' => false, 'message' => __('请先填写对方的友链页面地址', 'argon'));
                    break;
                }
                $raw_url = isset($_POST['url']) ? trim(wp_unslash($_POST['url'])) : '';
                if ($raw_url !== '' && !preg_match('/^https?:\/\//i', $raw_url)) {
                    $raw_url = 'https://' . $raw_url;
                }
                $url = $raw_url !== '' ? esc_url_raw($raw_url) : $link['url'];
                $has_backlink = $links_page ? argon_check_backlink($links_page) : null;
                $accessible = argon_check_site_accessible($url);
                argon_update_friend_link($id, array('url' => $url, 'links_page' => $links_page, 'has_backlink' => $has_backlink, 'accessible' => $accessible, 'last_check' => time()));
                $response = array('success' => true, 'has_backlink' => $has_backlink, 'accessible' => $accessible, 'links_page' => $links_page);
            } else {
                $response = array('success' => false, 'message' => __('友链不存在或已被删除，请刷新页面后重试', 'argon'));
            }
            break;
            
        case 'check_all':
            $links = argon_get_friend_links_raw('approved');
            $results = array();
            foreach ($links as $link) {
                $links_page = !empty($link['links_page']) ? $link['links_page'] : '';
                $has_backlink = $links_page ? argon_check_backlink($links_page) : null;
                $site_info = argon_fetch_site_info($link['url']);
                $best_image = argon_select_friend_link_image($site_info);
                $update_data = array(
                    'has_backlink' => $has_backlink,
                    'accessible' => !empty($site_info['accessible']),
                    'blocked_by_waf' => !empty($site_info['blocked_by_waf']),
                    'error_reason' => !empty($site_info['error_reason']) ? $site_info['error_reason'] : '',
                    'last_check' => time(),
                    'last_info_update' => time()
                );
                foreach (array('author_avatar', 'favicon', 'site_image', 'image') as $field) {
                    if (!empty($site_info[$field])) $update_data[$field] = $site_info[$field];
                }
                if ($best_image && empty($link['user_avatar'])) $update_data['avatar'] = $best_image;
                if (!empty($site_info['description'])) $update_data['auto_description'] = $site_info['description'];
                argon_update_friend_link($link['id'], $update_data);
                $results[$link['id']] = array(
                    'has_backlink' => $has_backlink,
                    'accessible' => !empty($site_info['accessible']),
                    'avatar' => $best_image
                );
            }
            $response = array('success' => true, 'results' => $results);
            break;
            
        case 'fetch_info':
            $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
            if (empty($url)) {
                $response = array('success' => false, 'message' => __('请输入网址', 'argon'));
            } else {
                $info = argon_fetch_site_info($url);
                $response = array('success' => true, 'data' => $info);
            }
            break;

        case 'refresh_info':
            $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
            $link = $id ? argon_get_friend_link($id) : null;
            if (!$link) {
                $response = array('success' => false, 'message' => __('友链不存在', 'argon'));
                break;
            }

            $raw_url = isset($_POST['url']) ? trim(wp_unslash($_POST['url'])) : '';
            if ($raw_url === '') {
                $raw_url = isset($link['url']) ? $link['url'] : '';
            }
            if ($raw_url !== '' && !preg_match('/^https?:\/\//i', $raw_url)) {
                $raw_url = 'https://' . $raw_url;
            }
            $url = esc_url_raw($raw_url);
            if (empty($url)) {
                $response = array('success' => false, 'message' => __('请输入网址', 'argon'));
                break;
            }

            $info = argon_fetch_site_info($url);
            $best_image = function_exists('argon_select_friend_link_image') ? argon_select_friend_link_image($info) : '';
            $update_data = array(
                'url' => $url,
                'last_info_update' => time(),
                'last_check' => time(),
                'accessible' => !empty($info['accessible']),
                'blocked_by_waf' => !empty($info['blocked_by_waf']),
                'is_wordpress' => !empty($info['is_wordpress']),
                'error_reason' => !empty($info['error_reason']) ? $info['error_reason'] : ''
            );

            if (!empty($info['title'])) {
                $update_data['display_name'] = $info['title'];
            }
            if (!empty($info['description'])) {
                $description = wp_strip_all_tags($info['description']);
                $update_data['description'] = function_exists('mb_substr') ? mb_substr($description, 0, 100) : substr($description, 0, 100);
            }
            foreach (array('author_avatar', 'favicon', 'site_image', 'image') as $field) {
                if (!empty($info[$field])) {
                    $update_data[$field] = $info[$field];
                }
            }
            if (!empty($info['favicon'])) {
                $update_data['auto_favicon'] = $info['favicon'];
            }
            if (!empty($best_image) && empty($link['user_avatar'])) {
                $update_data['avatar'] = $best_image;
                $update_data['image'] = $best_image;
                $update_data['user_avatar'] = false;
            }

            $updated = argon_update_friend_link($id, $update_data);
            if ($updated) {
                $info['url'] = $url;
                $info['avatar'] = !empty($update_data['avatar']) ? $update_data['avatar'] : (isset($link['avatar']) ? $link['avatar'] : '');
                $info['user_avatar'] = !empty($link['user_avatar']);
                $info['last_info_update'] = $update_data['last_info_update'];
                $info['last_check'] = $update_data['last_check'];
                $response = array('success' => true, 'message' => __('已更新并保存', 'argon'), 'data' => $info);
            } else {
                $response = array('success' => false, 'message' => __('保存失败', 'argon'), 'data' => $info);
            }
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
    $apply_result = array('success' => true, 'message' => __('申请已提交，请等待审核', 'argon'));
} elseif (isset($_GET['apply_error'])) {
    if (!session_id()) session_start();
    $error_msg = isset($_SESSION['flink_apply_error']) ? $_SESSION['flink_apply_error'] : __('申请失败', 'argon');
    unset($_SESSION['flink_apply_error']);
    $apply_result = array('success' => false, 'message' => $error_msg);
}

// 获取友链数据
$all_approved_links = argon_get_friend_links_raw('approved');
$pending_links = $is_admin ? argon_get_friend_links_raw('pending') : array();
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

get_header();
?>

<div class="page-information-card-container"></div>
<?php get_sidebar(); ?>

<div id="primary" class="content-area">

<style id="flinks-page-style">
/* 桌面端隐藏侧边栏 */
@media screen and (min-width: 901px) {
    body.flinks-page #leftbar_part, body.flinks-page #leftbar { display: none !important; }
}
/* 移动端侧边栏支持 - 使用全局样式 */
@media screen and (max-width: 900px) {
    body.flinks-page #leftbar {
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
    html.leftbar-opened body.flinks-page #leftbar { left: 0px; }
    body.flinks-page .leftbar-mobile-nav { display: flex; flex-direction: column; min-height: 100%; }
    body.flinks-page .leftbar-desktop-content { display: none !important; }
    
    /* 底部操作区固定在底部 */
    body.flinks-page .leftbar-mobile-footer {
        margin-top: auto;
        position: sticky;
        bottom: 0;
        background: var(--color-foreground);
        z-index: 10;
    }
}
body.flinks-page #primary { width: 100% !important; max-width: var(--argon-special-page-width, 1040px) !important; margin: 0 auto !important; float: none !important; }
body.flinks-page #content { margin-top: -50vh !important; }
body.flinks-page #main { max-width: var(--argon-special-page-width, 1040px) !important; margin: 0 auto !important; }
body.flinks-page {
    --flinks-text-main: var(--color-text-deeper);
    --flinks-text-muted: #677181;
    --flinks-text-soft: #8a93a3;
    --flinks-text-faint: #a4adba;
    --flinks-surface-soft: rgba(var(--themecolor-rgbstr), 0.05);
    --flinks-surface-hover: rgba(var(--themecolor-rgbstr), 0.08);
    --flinks-input-bg: rgba(var(--themecolor-rgbstr), 0.05);
    --flinks-input-text: var(--color-text-deeper);
    --flinks-placeholder: #99a2b1;
    --flinks-success-bg: rgba(34, 197, 94, 0.12);
    --flinks-success-text: #15803d;
    --flinks-danger-bg: rgba(239, 68, 68, 0.12);
    --flinks-danger-text: #dc2626;
    --flinks-warning-bg: rgba(245, 158, 11, 0.14);
    --flinks-warning-text: #b45309;
}
html.darkmode body.flinks-page {
    --flinks-text-muted: #c5c9d1;
    --flinks-text-soft: #a4aab5;
    --flinks-text-faint: #878e9b;
    --flinks-surface-soft: rgba(255, 255, 255, 0.05);
    --flinks-surface-hover: rgba(255, 255, 255, 0.08);
    --flinks-input-bg: rgba(255, 255, 255, 0.06);
    --flinks-placeholder: #858c99;
    --flinks-success-bg: rgba(74, 222, 128, 0.13);
    --flinks-success-text: #86efac;
    --flinks-danger-bg: rgba(248, 113, 113, 0.14);
    --flinks-danger-text: #fca5a5;
    --flinks-warning-bg: rgba(251, 191, 36, 0.14);
    --flinks-warning-text: #facc15;
}

.flinks-header-card { text-align: center; padding: 40px 24px 30px; background: transparent !important; box-shadow: none !important; }
.flinks-header-icon { width: 64px; height: 64px; margin: 0 auto 20px; background: var(--themecolor-gradient); border-radius: var(--card-radius); display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; box-shadow: 0 4px 12px rgba(var(--themecolor-rgbstr), 0.2); transition: transform var(--animation-fast) var(--ease-standard); }
.flinks-header-icon:hover { transform: translateY(-2px); }
.flinks-title { font-size: 24px; font-weight: 600; margin: 0 0 12px; color: var(--color-text-deeper); background: none !important; }
.flinks-title::before, .flinks-title::after { display: none !important; }
.flinks-subtitle { font-size: 14px; color: var(--flinks-text-muted); margin: 0 0 20px; line-height: 1.6; }
.flinks-stats { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
.flinks-stat { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(var(--themecolor-rgbstr), 0.08); border-radius: var(--card-radius-sm); font-size: 13px; font-weight: 500; color: var(--themecolor); transition: all var(--animation-fast) var(--ease-standard); }
.flinks-stat:hover { background: rgba(var(--themecolor-rgbstr), 0.12); }
.flinks-apply-header:focus { outline: none; box-shadow: none; }
.flinks-apply-header:focus-visible, .flink-card:focus-visible { outline: 2px solid rgba(var(--themecolor-rgbstr), 0.28); outline-offset: 2px; }
.flinks-add-form input::placeholder, .flink-modal input::placeholder, .flinks-form .form-control::placeholder { color: var(--flinks-placeholder); }

.flinks-welcome-text { font-size: 14px; color: var(--flinks-text-muted); line-height: 1.6; margin-bottom: 16px; padding: 12px 16px; background: var(--flinks-surface-soft); border-radius: var(--card-radius-sm); border-left: 3px solid var(--themecolor); }

.flinks-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px; }
.flinks-category-card { padding: 0; overflow: hidden; }
.flinks-category-header { display: flex; align-items: center; gap: 12px; padding: 18px 24px; margin-bottom: 0; border-bottom: 1px solid var(--color-border-on-foreground); background: var(--flinks-surface-soft); }
.flinks-category-title { font-size: 17px; font-weight: 600; margin: 0; color: var(--color-text-deeper); display: flex; align-items: center; gap: 8px; background: none !important; }
.flinks-category-title::before, .flinks-category-title::after { display: none !important; }
.flinks-category-title i { color: var(--themecolor); font-size: 16px; }
.flinks-category-count { margin-left: auto; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: var(--themecolor); background: rgba(var(--themecolor-rgbstr), 0.1); padding: 5px 12px; border-radius: var(--card-radius-sm); white-space: nowrap; }

.flinks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 4px; padding: 14px; }
.flink-card { display: flex; align-items: flex-start; gap: 14px; padding: 14px; background: transparent; border-radius: var(--card-radius-sm); text-decoration: none !important; color: inherit; border: 1px solid transparent; transition: all var(--animation-fast) var(--ease-standard); min-height: 88px; position: relative; }
.flink-card:hover { 
	transform: translateY(-2px); 
	background: var(--flinks-surface-hover);
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
.flink-name .sub-name { font-size: 13px; font-weight: 400; color: var(--flinks-text-soft); }
.flink-verified { color: #22c55e; font-size: 13px; flex-shrink: 0; }
.flink-desc { font-size: 13px; color: var(--flinks-text-muted); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: 4px; }
.flink-url { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 12px; color: var(--flinks-text-faint); margin-top: 6px; white-space: nowrap; overflow: hidden; }
.flink-url-text { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
.flink-open-icon { flex-shrink: 0; opacity: 0; transform: translateX(-3px); transition: all var(--animation-fast) var(--ease-standard); color: var(--themecolor); }
.flink-card:hover .flink-open-icon { opacity: 0.85; transform: translateX(0); }

.flinks-empty { padding: 60px 24px; text-align: center; }
.flinks-empty i { font-size: 48px; color: var(--color-border); margin-bottom: 16px; display: block; }
.flinks-empty p { font-size: 15px; color: var(--flinks-text-muted); margin: 0; }

/* 申请区域 */
.flinks-apply-card { padding: 0; overflow: hidden; }
.flinks-apply-header { display: flex; align-items: center; justify-content: space-between; width: 100%; cursor: pointer; user-select: none; padding: 16px 22px; border: 0; background: var(--flinks-surface-soft); font-family: var(--font); text-align: left; }
.flinks-apply-header:hover { background: var(--flinks-surface-hover); }
.flinks-apply-title { display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 600; color: var(--themecolor); }
.flinks-apply-arrow { color: var(--themecolor); font-size: 14px; transition: transform var(--animation-normal) var(--ease-standard); }
.flinks-apply-arrow.open { transform: rotate(180deg); }
.flinks-apply-content { max-height: 0; overflow: hidden; padding: 0 22px; transition: max-height var(--animation-slow) var(--ease-standard), padding-bottom var(--animation-normal) var(--ease-standard); }
.flinks-apply-content.open { max-height: 2600px; padding-bottom: 18px; }

.flinks-requirements { background: rgba(var(--themecolor-rgbstr), 0.05); border-radius: var(--card-radius); padding: 10px 12px; margin: 10px 0 8px; border: none; border-left: 3px solid var(--themecolor); font-size: 13px; line-height: 1.5; }
.flinks-requirements, .flinks-requirements p, .flinks-requirements li { color: var(--flinks-text-muted); }
.flinks-requirements p { margin: 0; }

.flinks-mysite-info { background: transparent; border-radius: var(--card-radius); padding: 10px 12px; margin: 8px 0; border: none; }
.flinks-mysite-title { font-size: 12px; font-weight: 600; color: var(--themecolor); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.flinks-mysite-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
.flinks-mysite-item { background: var(--flinks-surface-soft); border-radius: calc(var(--card-radius) * 0.6); padding: 7px 10px; cursor: pointer; transition: all var(--animation-fast) var(--ease-standard); border: 1px solid transparent; }
.flinks-mysite-item:hover { background: var(--flinks-surface-hover); border-color: rgba(var(--themecolor-rgbstr), 0.18); }
.flinks-mysite-item label { display: block; font-size: 10px; color: var(--flinks-text-soft); margin-bottom: 1px; text-transform: uppercase; pointer-events: none; }
.flinks-mysite-item span { font-size: 12px; color: var(--color-text-deeper); word-break: break-all; font-weight: 500; pointer-events: none; }

.flinks-form { margin-top: 8px; font-family: var(--font); }
.flinks-form .row { margin-left: -6px; margin-right: -6px; }
.flinks-form .row > [class*="col-"] { padding-left: 6px; padding-right: 6px; }
.flinks-form .form-group { margin-bottom: 8px; }
.flinks-form .form-control-label { font-size: 12px; font-weight: 600; margin-bottom: 3px; color: var(--color-text-deeper); display: block; font-family: var(--font); }
.flinks-form .form-control { border-radius: calc(var(--card-radius) * 0.6); border: 1px solid transparent; padding: 7px 10px; font-size: 13px; font-family: var(--font); background: var(--flinks-input-bg); color: var(--flinks-input-text); }
.flinks-form .form-control:focus { border-color: rgba(var(--themecolor-rgbstr), 0.45); box-shadow: 0 0 0 2px rgba(var(--themecolor-rgbstr), 0.15); outline: none; }
.flinks-form .char-count { font-size: 11px; color: var(--flinks-text-soft); float: right; }
.flinks-autopass-note { color: var(--flinks-success-text); font-weight: normal; }
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
.flinks-auto-preview-desc { font-size: 12px; color: var(--flinks-text-muted); margin-top: 4px; }

/* 验证码 */
.flinks-captcha { margin-top: 10px; }
.flinks-captcha .input-group { max-width: 280px; }
.flinks-captcha-text { background: var(--color-background); color: var(--flinks-text-main); border-color: var(--color-border); font-family: monospace; font-size: 14px; letter-spacing: 2px; cursor: pointer; user-select: none; }

/* 管理面板 */
.flinks-admin-card { padding: 16px 20px; }
.flinks-admin-title { font-size: 15px; font-weight: 600; color: var(--themecolor); margin: 0 0 12px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.flinks-admin-section { margin-bottom: 16px; }
.flinks-admin-section:last-child { margin-bottom: 0; }
.flinks-admin-section h4 { font-size: 13px; font-weight: 600; color: var(--color-text-deeper); margin: 0 0 8px; display: flex; align-items: center; gap: 6px; }

.flinks-pending-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: var(--flinks-warning-bg); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: var(--card-radius); margin-bottom: 8px; }
.flinks-pending-avatar { width: 40px; height: 40px; border-radius: 8px; background: var(--color-border); flex-shrink: 0; overflow: hidden; }
.flinks-pending-avatar img { width: 100%; height: 100%; padding: 3px; box-sizing: border-box; object-fit: contain; object-position: center; opacity: 0; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .18)) drop-shadow(0 0 1px rgba(255, 255, 255, .2)); transition: opacity var(--animation-normal) var(--ease-standard); }
.flinks-pending-avatar img.is-loaded { opacity: 1; }
.flinks-pending-info { flex: 1; min-width: 0; }
.flinks-pending-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; color: var(--flinks-text-main); }
.flinks-pending-name .sub-name { font-weight: 400; font-size: 12px; color: var(--flinks-text-soft); margin-left: 6px; }
.flinks-pending-url { font-size: 12px; color: var(--flinks-text-muted); margin-bottom: 4px; }
.flinks-pending-url a { color: var(--themecolor); }
.flinks-pending-desc { font-size: 12px; color: var(--flinks-text-muted); }
.flinks-pending-actions { display: flex; gap: 6px; flex-shrink: 0; }
.flinks-pending-actions .btn { padding: 4px 12px; font-size: 12px; }

.flinks-add-form { background: transparent; border-radius: 0; padding: 0; border: 0; }
.flinks-add-form .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 8px; }
.flinks-add-form .form-row.full { grid-template-columns: 1fr; }
.flinks-add-form label { font-size: 12px; font-weight: 500; color: var(--color-text-deeper); margin-bottom: 3px; display: block; }
.flinks-add-form input { width: 100%; padding: 6px 10px; font-size: 13px; border: 1px solid var(--color-border); border-radius: calc(var(--card-radius) * 0.6); font-family: var(--font); box-sizing: border-box; background: var(--color-foreground); color: var(--flinks-input-text); }
.flinks-add-form input:focus { border-color: var(--themecolor); outline: none; box-shadow: 0 0 0 2px rgba(var(--themecolor-rgbstr), 0.12); }
.flinks-add-form .input-with-btn { display: flex; gap: 6px; }
.flinks-add-form .input-with-btn input { flex: 1; min-width: 0; }
.flinks-add-form .input-with-btn .btn { flex-shrink: 0; padding: 6px 10px; font-size: 12px; }
.flinks-add-form .form-actions { margin-top: 10px; display: flex; gap: 8px; }
.flinks-add-form .form-actions .btn { display: inline-flex; align-items: center; justify-content: center; text-align: center; }

/* 编辑弹窗 */
.flink-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; display: none; align-items: center; justify-content: center; }
.flink-modal-overlay.show { display: flex; }
.flink-modal { background: var(--color-foreground); border-radius: var(--card-radius); padding: 20px; width: 90%; max-width: 560px; max-height: 90vh; overflow-y: auto; color: var(--flinks-text-main); box-shadow: 0 24px 80px rgba(0,0,0,0.28); }
.flink-modal-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; color: var(--flinks-text-main); }
.flink-modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--flinks-text-soft); padding: 0; line-height: 1; }
.flink-modal-close:hover { color: var(--flinks-text-main); }
.flink-modal .form-group { margin-bottom: 12px; }
.flink-modal .form-group label { font-size: 12px; font-weight: 600; color: var(--color-text-deeper); margin-bottom: 4px; display: block; }
.flink-modal .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 12px; }
.flink-modal .form-group.full { grid-column: 1 / -1; }
.flink-modal .form-group input, .flink-modal .form-group select { width: 100%; padding: 8px 10px; font-size: 13px; border: 1px solid var(--color-border); border-radius: calc(var(--card-radius) * 0.6); font-family: var(--font); box-sizing: border-box; background: var(--flinks-input-bg); color: var(--flinks-input-text); }
.flink-modal .form-group input:focus, .flink-modal .form-group select:focus { border-color: var(--themecolor); outline: none; box-shadow: 0 0 0 2px rgba(var(--themecolor-rgbstr), 0.12); }
.flink-modal .form-actions { margin-top: 16px; display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
.flink-modal .form-actions .btn { display: inline-flex; align-items: center; justify-content: center; text-align: center; min-width: auto; }
.flink-modal .btn-danger { background: #ef4444; border-color: #ef4444; }
.flink-modal .btn-danger:hover { background: #dc2626; border-color: #dc2626; }
.flink-modal .status-info { font-size: 12px; color: var(--flinks-text-muted); margin-top: 12px; padding: 12px; border: 1px solid var(--color-border-on-foreground); border-radius: var(--card-radius-sm); background: var(--flinks-surface-soft); }
.flink-modal .status-info .badge { font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 6px; }
.flink-modal .status-info .badge-warning { background: var(--flinks-warning-bg); color: var(--flinks-warning-text); }
.flink-modal .status-info .badge-danger { background: var(--flinks-danger-bg); color: var(--flinks-danger-text); }
.flink-modal .status-info .badge-success { background: var(--flinks-success-bg); color: var(--flinks-success-text); }
.flink-modal .status-info .badge-secondary { background: var(--flinks-surface-hover); color: var(--flinks-text-muted); }
.flink-action-message { display: none; margin-top: 10px; padding: 9px 12px; border-radius: var(--card-radius-sm); font-size: 12px; line-height: 1.5; }
.flink-action-message.show { display: block; }
.flink-action-message.success { background: var(--flinks-success-bg); color: var(--flinks-success-text); }
.flink-action-message.error { background: var(--flinks-danger-bg); color: var(--flinks-danger-text); }
.flink-action-message.info { background: rgba(var(--themecolor-rgbstr), 0.1); color: var(--themecolor); }

@media (max-width: 768px) {
    .flinks-header-card { padding: 30px 16px 24px; }
    .flinks-list { gap: 12px; }
    .flinks-category-header { padding: 15px 16px; }
    .flinks-category-title { font-size: 16px; min-width: 0; }
    .flinks-grid { grid-template-columns: 1fr; }
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

    // 先清理可能残留的状态（处理后退情况）
    // 如果当前不是友链页面但有残留 class，清理它
    if (!document.getElementById('flinks-page-style')) {
        document.body.classList.remove('flinks-page');
    }
    
    // 添加页面标识 class
    document.body.classList.add('flinks-page');
    
    // PJAX 离开时清理
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        $(document).off('pjax:complete.flinks pjax:end.flinks pjax:popstate.flinks');
        
        // PJAX 完成后再判断是否仍在友链页，避免切换前提前移除当前页样式
        $(document).on('pjax:complete.flinks pjax:end.flinks', function() {
            if (document.getElementById('flinks-page-style')) {
                document.body.classList.add('flinks-page');
				window.argonInitFriendLinkAvatars(document);
            } else {
                document.body.classList.remove('flinks-page');
                $(document).off('pjax:complete.flinks pjax:end.flinks pjax:popstate.flinks');
            }
        });
        
        // 浏览器后退/前进
        $(document).on('pjax:popstate.flinks', function() {
            // 延迟检查，等 PJAX 完成内容替换
            setTimeout(function() {
                if (!document.getElementById('flinks-page-style')) {
                    document.body.classList.remove('flinks-page');
                }
            }, 50);
        });
    }

	requestAnimationFrame(function() {
		window.argonInitFriendLinkAvatars(document);
	});
})();
</script>

<main id="main" class="site-main" role="main">

<?php
// 获取自定义标语
$flink_slogan = get_option('argon_friend_link_slogan', __('海内存知己，天涯若比邻', 'argon'));
$flink_welcome = get_option('argon_friend_link_welcome', __('欢迎与我交换友链！如果您的网站已添加本站链接，填写友链页面地址可自动通过审核。', 'argon'));
?>

<!-- 头部卡片 -->
<div class="flinks-header-card" style="margin-bottom: 16px;">
    <div class="flinks-header-icon"><i class="fa fa-link"></i></div>
    <h1 class="flinks-title"><?php _e('友情链接', 'argon'); ?></h1>
    <p class="flinks-subtitle"><?php echo esc_html($flink_slogan); ?></p>
    <div class="flinks-stats">
        <div class="flinks-stat"><i class="fa fa-heart"></i><span><?php echo $total_links; ?> <?php _e('位朋友', 'argon'); ?></span></div>
        <?php if (count($links) > 1) : ?>
        <div class="flinks-stat"><i class="fa fa-folder-open"></i><span><?php echo count($links); ?> <?php _e('个分类', 'argon'); ?></span></div>
        <?php endif; ?>
    </div>
</div>

<!-- 管理面板（仅管理员可见） -->
<?php if ($is_admin) : ?>
<article class="post card shadow-sm bg-white border-0 flinks-admin-card" style="margin-bottom: 16px;" id="admin-panel">
    <div class="flinks-admin-title"><i class="fa fa-cog"></i><?php _e('友链管理', 'argon'); ?> <button class="btn btn-sm btn-secondary" style="margin-left:auto;font-size:11px;" id="check-all-btn"><?php _e('检测所有友链', 'argon'); ?></button></div>
    
    <?php if (!empty($pending_links)) : ?>
    <div class="flinks-admin-section">
        <h4><i class="fa fa-clock-o"></i> <?php _e('待审核', 'argon'); ?> (<?php echo count($pending_links); ?>)</h4>
        <?php foreach ($pending_links as $link) : 
            $main_name = esc_html($link['name']);
            $sub_name = !empty($link['display_name']) ? esc_html($link['display_name']) : '';
        ?>
        <div class="flinks-pending-item" data-id="<?php echo esc_attr($link['id']); ?>">
            <div class="flinks-pending-avatar">
                <?php if (!empty($link['avatar'])) : ?><img data-avatar-src="<?php echo esc_url($link['avatar']); ?>" alt="<?php echo esc_attr($link['name']); ?>" decoding="async"><?php endif; ?>
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
                        <input type="url" id="add-url" placeholder="https://example.com">
                        <button type="button" class="btn btn-secondary" id="fetch-info"><?php _e('获取', 'argon'); ?></button>
                    </div>
                </div>
                <div><label><?php _e('网站名称', 'argon'); ?> *</label><input type="text" id="add-name" placeholder="<?php _e('主标题', 'argon'); ?>"></div>
            </div>
            <div class="form-row">
                <div><label><?php _e('副标题', 'argon'); ?></label><input type="text" id="add-display-name" placeholder="<?php _e('自动获取的标题', 'argon'); ?>"></div>
                <div><label><?php _e('分类', 'argon'); ?></label><input type="text" id="add-category" placeholder="<?php _e('留空为默认', 'argon'); ?>"></div>
            </div>
            <div class="form-row">
                <div><label><?php _e('头像', 'argon'); ?></label><input type="url" id="add-avatar" placeholder="<?php _e('自动获取', 'argon'); ?>"></div>
                <div><label><?php _e('描述', 'argon'); ?></label><input type="text" id="add-desc" maxlength="100" placeholder="<?php _e('主描述', 'argon'); ?>"></div>
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
<div class="flinks-list">
    <?php $color_index = 0; foreach ($links as $category => $category_links) : ?>
    <article class="post card shadow-sm bg-white border-0 flinks-category-card" data-category="<?php echo esc_attr($category); ?>">
        <div class="flinks-category-header">
            <h2 class="flinks-category-title">
                <i class="fa fa-<?php echo empty($category) ? 'star' : 'folder-open'; ?>"></i>
                <?php echo empty($category) ? __('友情链接', 'argon') : esc_html($category); ?>
            </h2>
            <span class="flinks-category-count"><i class="fa fa-link"></i><?php echo count($category_links); ?> <?php _e('个站点', 'argon'); ?></span>
        </div>
        <div class="flinks-grid">
            <?php foreach ($category_links as $link) : 
                $host = parse_url($link['url'], PHP_URL_HOST);
                $main_name = $link['name'];
                $sub_name = !empty($link['display_name']) ? $link['display_name'] : '';
                $first_letter = mb_substr($main_name, 0, 1);
                $color_index = ($color_index % 6) + 1;
                $desc = !empty($link['description']) ? $link['description'] : (!empty($link['auto_description']) ? $link['auto_description'] : __('暂无描述', 'argon'));
                
                // 状态检测
                $has_backlink = isset($link['has_backlink']) ? $link['has_backlink'] : null;
                $accessible = isset($link['accessible']) ? $link['accessible'] : null;
                $blocked_by_waf = isset($link['blocked_by_waf']) ? $link['blocked_by_waf'] : false;
                $error_reason = isset($link['error_reason']) ? $link['error_reason'] : '';
                $auto_approved = !empty($link['auto_approved']);
                $status_badge = '';
                if ($is_admin) {
                    if ($blocked_by_waf) {
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
                <div class="flink-avatar <?php echo !empty($link['avatar']) ? 'has-image' : ''; ?>" data-letter="<?php echo esc_attr($first_letter); ?>" data-color="<?php echo $color_index; ?>">
                    <?php if (!empty($link['avatar'])) : ?><img data-avatar-src="<?php echo esc_url($link['avatar']); ?>" alt="<?php echo esc_attr($main_name); ?>" decoding="async"><?php endif; ?>
                </div>
                <div class="flink-info">
                    <div class="flink-name">
                        <span class="main-name"><?php echo esc_html($main_name); ?></span>
                        <?php if ($sub_name) : ?><span class="sub-name"><?php echo esc_html($sub_name); ?></span><?php endif; ?>
                        <?php if (!empty($link['verified'])) : ?><i class="fa fa-check-circle flink-verified"></i><?php endif; ?>
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
<article class="post card shadow-sm bg-white border-0 flinks-empty" style="margin-bottom: 16px;">
    <i class="fa fa-inbox"></i>
    <p><?php _e('暂无友情链接', 'argon'); ?></p>
</article>
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
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站地址', 'argon'); ?> <span class="text-danger">*</span></label>
                        <input type="url" name="apply_url" id="apply-url" class="form-control" required placeholder="https://example.com">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站名称', 'argon'); ?></label>
                        <input type="text" name="apply_name" id="apply-name" class="form-control" placeholder="<?php _e('选填', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站图标', 'argon'); ?></label>
                        <input type="url" name="apply_avatar" id="apply-avatar" class="form-control" placeholder="<?php _e('选填', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('联系邮箱', 'argon'); ?></label>
                        <input type="email" name="apply_email" class="form-control" placeholder="<?php _e('选填', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('友链页面', 'argon'); ?> <small class="flinks-autopass-note"><?php _e('填写可自动通过', 'argon'); ?></small></label>
                        <input type="url" name="apply_links_page" class="form-control" placeholder="<?php _e('您放置友链的页面地址', 'argon'); ?>">
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-control-label"><?php _e('网站描述', 'argon'); ?> <span class="char-count"><span id="desc-count">0</span>/100</span></label>
                        <textarea name="apply_description" id="apply-desc" class="form-control" maxlength="100" rows="2" placeholder="<?php _e('选填', 'argon'); ?>" oninput="document.getElementById('desc-count').textContent=this.value.length" style="resize:vertical;min-height:60px;"></textarea>
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
                    <input type="text" name="comment_captcha" id="flink-captcha-input" class="form-control" placeholder="<?php _e('验证码', 'argon'); ?>" required style="max-width:120px;">
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
    tip.textContent = '<?php _e('已复制', 'argon'); ?>';
    tip.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.75);color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;z-index:9999;';
    document.body.appendChild(tip);
    setTimeout(function() { tip.remove(); }, 1200);
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

<?php if ($apply_result) : ?>
document.addEventListener('DOMContentLoaded', function() {
    var s = document.getElementById('apply-section');
    openApplyForm();
    if (s) s.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
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

<?php if ($is_admin) : ?>
(function() {
    var nonce = '<?php echo wp_create_nonce('argon_flink_manage'); ?>';
    var ajaxUrl = <?php echo wp_json_encode(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))); ?>;
    var addAvatarIsManual = false;
    var editAvatarIsManual = false;

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

    function normalizeUrlInput(value) {
        value = (value || '').trim();
        if (value && !/^https?:\/\//i.test(value)) value = 'https://' + value;
        return value;
    }

    var addAvatarInput = document.getElementById('add-avatar');
    var editAvatarInput = document.getElementById('edit-avatar');
    if (addAvatarInput) {
        addAvatarInput.addEventListener('input', function() {
            addAvatarIsManual = this.value.trim() !== '';
        });
    }
    if (editAvatarInput) {
        editAvatarInput.addEventListener('input', function() {
            editAvatarIsManual = this.value.trim() !== '';
        });
    }
    
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

    // 移除自动审核标记
    window.removeAutoTag = function(el) {
        if (!confirm('<?php _e('确定移除自动审核标记？', 'argon'); ?>')) return;
        var id = el.getAttribute('data-id');
        flinkAction('update', { id: id, auto_approved: '' }, function(res) {
            if (res.success) el.remove();
            else alert(res.message || '<?php _e('操作失败', 'argon'); ?>');
        });
    };
    
    // 获取网站信息（服务器端）
    var fetchInfoBtn = document.getElementById('fetch-info');
    if (fetchInfoBtn) {
        fetchInfoBtn.addEventListener('click', function() {
            var url = normalizeUrlInput(document.getElementById('add-url').value);
            if (!url) return alert('<?php _e('请输入网址', 'argon'); ?>');
            document.getElementById('add-url').value = url;
            this.disabled = true; this.textContent = '...';
            var btn = this;

            flinkAction('fetch_info', { url: url }, function(res) {
                btn.disabled = false; btn.textContent = '<?php _e('获取', 'argon'); ?>';
                if (res.success && res.data) {
                    if (res.data.title) document.getElementById('add-display-name').value = res.data.title;
                    if (res.data.description) document.getElementById('add-desc').value = res.data.description;
                    var avatar = res.data.site_image || res.data.author_avatar || res.data.favicon || '';
                    if (avatar) document.getElementById('add-avatar').value = avatar;
                    addAvatarIsManual = false;
                } else {
                    alert(res.message || '<?php _e('获取失败', 'argon'); ?>');
                }
            });
        });
    }

    // 添加友链
    var addLinkBtn = document.getElementById('add-link-btn');
    if (addLinkBtn) {
        addLinkBtn.addEventListener('click', function() {
            var name = document.getElementById('add-name').value.trim();
            var url = normalizeUrlInput(document.getElementById('add-url').value);
            if (!url) return alert('<?php _e('请填写网址', 'argon'); ?>');
            document.getElementById('add-url').value = url;
            if (!name) {
                name = document.getElementById('add-display-name').value.trim();
                if (!name) return alert('<?php _e('请填写名称', 'argon'); ?>');
            }
            this.disabled = true; this.textContent = '<?php _e('添加中...', 'argon'); ?>';
            var btn = this;
            flinkAction('add', {
                name: name,
                url: url,
                display_name: document.getElementById('add-display-name').value.trim(),
                avatar: document.getElementById('add-avatar').value.trim(),
                user_avatar: addAvatarIsManual ? '1' : '0',
                description: document.getElementById('add-desc').value.trim(),
                category: document.getElementById('add-category').value.trim()
            }, function(res) {
                if (res.success) location.reload();
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

    // 检测所有友链
    var checkAllBtn = document.getElementById('check-all-btn');
    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', function() {
            if (!confirm('<?php _e('将检测所有友链的状态，可能需要一些时间，确定继续？', 'argon'); ?>')) return;
            this.disabled = true; this.textContent = '<?php _e('检测中...', 'argon'); ?>';
            flinkAction('check_all', {}, function(res) {
                if (res.success) location.reload();
                else { checkAllBtn.disabled = false; checkAllBtn.textContent = '<?php _e('检测所有友链', 'argon'); ?>'; alert(res.message || '<?php _e('检测失败', 'argon'); ?>'); }
            });
        });
    }

    // 编辑弹窗
    window.openEditModal = function(id) {
        flinkAction('get', { id: id }, function(res) {
            if (res.success && res.data) {
                var link = res.data;
                clearMessage();
                document.getElementById('edit-id').value = link.id;
                document.getElementById('edit-name').value = link.name || '';
                document.getElementById('edit-display-name').value = link.display_name || '';
                document.getElementById('edit-url').value = link.url || '';
                document.getElementById('edit-links-page').value = link.links_page || '';
                document.getElementById('edit-avatar').value = link.avatar || '';
                editAvatarIsManual = !!link.user_avatar;
                document.getElementById('edit-desc').value = link.description || '';
                document.getElementById('edit-category').value = link.category || '';
                document.getElementById('edit-hide-no-backlink').value = link.hide_no_backlink || '';

                // 显示状态信息
                var statusHtml = '';
                if (link.last_check) {
                    var date = new Date(link.last_check * 1000);
                    statusHtml += '<?php _e('上次检测', 'argon'); ?>: ' + date.toLocaleString() + '<br>';
                }
                if (link.accessible === false) {
                    statusHtml += '<?php _e('网站状态', 'argon'); ?>: <span class="badge badge-danger"><?php _e('无法访问', 'argon'); ?></span><br>';
                } else if (link.accessible === true) {
                    statusHtml += '<?php _e('网站状态', 'argon'); ?>: <span class="badge badge-success"><?php _e('正常', 'argon'); ?></span><br>';
                }
                if (link.has_backlink === false) {
                    statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-warning"><?php _e('未检测到本站链接', 'argon'); ?></span>';
                } else if (link.has_backlink === true) {
                    statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-success"><?php _e('已上友链', 'argon'); ?></span>';
                } else if (!link.links_page) {
                    statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge badge-secondary"><?php _e('未填写友链页面', 'argon'); ?></span>';
                }
                document.getElementById('edit-status-info').innerHTML = statusHtml || '<?php _e('暂无检测记录', 'argon'); ?>';

                document.getElementById('edit-modal').classList.add('show');
            } else {
                alert(res.message || '<?php _e('友链不存在或已被删除，请刷新页面后重试', 'argon'); ?>');
            }
        });
    };
    
    window.closeEditModal = function() {
        document.getElementById('edit-modal').classList.remove('show');
        clearMessage();
    };

    var editModal = document.getElementById('edit-modal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    }

    // 更新信息（从网站获取最新数据）
    var editFetchBtn = document.getElementById('edit-fetch-btn');
    if (editFetchBtn) {
        editFetchBtn.addEventListener('click', function() {
            var url = normalizeUrlInput(document.getElementById('edit-url').value);
            if (!url) return alert('<?php _e('请先填写网址', 'argon'); ?>');
            document.getElementById('edit-url').value = url;

            clearMessage();
            this.disabled = true; this.textContent = '<?php _e('获取中...', 'argon'); ?>';
            var btn = this;
                
            flinkAction('refresh_info', {
                id: document.getElementById('edit-id').value,
                url: url
            }, function(res) {
                btn.disabled = false; btn.textContent = '<?php _e('更新信息', 'argon'); ?>';
                if (res.success && res.data) {
                    var displayName = document.getElementById('edit-display-name');
                    var editUrl = document.getElementById('edit-url');
                    var avatar = document.getElementById('edit-avatar');
                    var desc = document.getElementById('edit-desc');

                    if (res.data.url) editUrl.value = res.data.url;
                    if (res.data.title) displayName.value = res.data.title;
                    var newAvatar = res.data.avatar || '';
                    if (newAvatar) avatar.value = newAvatar;
                    editAvatarIsManual = !!res.data.user_avatar;
                    if (res.data.description) desc.value = res.data.description;

                    var infoHtml = '<strong><?php _e('已更新并保存', 'argon'); ?></strong>';
                    if (res.data.blocked_by_waf) {
                        infoHtml += ' <span class="badge badge-warning"><?php _e('被 WAF 拦截', 'argon'); ?></span>';
                    } else if (res.data.accessible === false) {
                        infoHtml += ' <span class="badge badge-danger"><?php _e('无法访问', 'argon'); ?></span>';
                    }
                    if (res.data.last_info_update) {
                        var updatedAt = new Date(res.data.last_info_update * 1000);
                        infoHtml += '<br><?php _e('更新时间', 'argon'); ?>: ' + updatedAt.toLocaleString();
                    }
                    document.getElementById('edit-status-info').innerHTML = infoHtml;
                    setMessage('success', res.message || '<?php _e('站点信息已保存', 'argon'); ?>');
                } else {
                    setMessage('error', res.message || (res.data && res.data.error_reason) || '<?php _e('获取失败', 'argon'); ?>');
                }
            });
        });
    }

    // 检测互链
    var editCheckBtn = document.getElementById('edit-check-btn');
    if (editCheckBtn) {
        editCheckBtn.addEventListener('click', function() {
            var linksPage = document.getElementById('edit-links-page').value.trim();
            if (!linksPage) {
                return alert('<?php _e('请先填写对方的友链页面地址', 'argon'); ?>');
            }
            var id = document.getElementById('edit-id').value;
            clearMessage();
            this.disabled = true; this.textContent = '<?php _e('检测中...', 'argon'); ?>';
            var btn = this;
            flinkAction('check_backlink', {
                id: id,
                url: document.getElementById('edit-url').value.trim(),
                links_page: linksPage
            }, function(res) {
                btn.disabled = false; btn.textContent = '<?php _e('检测互链', 'argon'); ?>';
                if (res.success) {
                    var statusHtml = '<?php _e('互链页面', 'argon'); ?>: ' + (res.links_page || linksPage) + '<br>';
                    statusHtml += '<?php _e('网站状态', 'argon'); ?>: <span class="badge ' + (res.accessible ? 'badge-success' : 'badge-danger') + '">' + (res.accessible ? '<?php _e('正常', 'argon'); ?>' : '<?php _e('无法访问', 'argon'); ?>') + '</span><br>';
                    statusHtml += '<?php _e('互链状态', 'argon'); ?>: <span class="badge ' + (res.has_backlink ? 'badge-success' : 'badge-warning') + '">' + (res.has_backlink ? '<?php _e('已上友链', 'argon'); ?>' : '<?php _e('未检测到本站链接', 'argon'); ?>') + '</span>';
                    document.getElementById('edit-status-info').innerHTML = statusHtml;
                    setMessage(res.has_backlink ? 'success' : 'info', res.has_backlink ? '<?php _e('检测完成，已保存互链状态', 'argon'); ?>' : '<?php _e('检测完成，暂未发现本站链接', 'argon'); ?>');
                } else {
                    setMessage('error', res.message || '<?php _e('检测失败', 'argon'); ?>');
                }
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
            flinkAction('update', {
                id: id,
                name: name,
                display_name: document.getElementById('edit-display-name').value.trim(),
                url: url,
                links_page: document.getElementById('edit-links-page').value.trim(),
                avatar: document.getElementById('edit-avatar').value.trim(),
                user_avatar: editAvatarIsManual ? '1' : '0',
                description: document.getElementById('edit-desc').value.trim(),
                category: document.getElementById('edit-category').value.trim(),
                hide_no_backlink: document.getElementById('edit-hide-no-backlink').value
            }, function(res) {
                btn.disabled = false; btn.textContent = '<?php _e('保存', 'argon'); ?>';
                if (res.success) {
                    setMessage('success', res.message || '<?php _e('已保存友链信息', 'argon'); ?>');
                    setTimeout(function() { location.reload(); }, 450);
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

<?php get_footer(); ?>
