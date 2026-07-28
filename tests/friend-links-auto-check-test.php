<?php

/**
 * 浏览器友链检测与源站保护的轻量契约测试。
 *
 * 运行：php tests/friend-links-auto-check-test.php
 */

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.php');
$page = file_get_contents($root . '/friend-links.php');
$worker_bridge = file_get_contents($root . '/inc/friend-link-worker.php');

if ($functions === false || $page === false || $worker_bridge === false) {
    fwrite(STDERR, "无法读取友链相关文件。\n");
    exit(1);
}

$failures = array();
function argon_flink_test_assert($condition, $message) {
    global $failures;
    if (!$condition) $failures[] = $message;
}

argon_flink_test_assert(
    strpos($page, '/assets/js/friend-links-browser.js') !== false
    && strpos($page, 'detector.detectSite') !== false,
    '友链页没有接入浏览器站点资料检测模块。'
);
argon_flink_test_assert(
    strpos($functions, "require_once get_template_directory() . '/inc/friend-link-worker.php';") !== false
    && strpos($worker_bridge, "register_rest_route('argon/v1', '/friend-link-ticket'") !== false
    && strpos($worker_bridge, "register_rest_route('argon/v1', '/friend-link-result'") !== false,
    '主题没有注册 Worker 签发与回调桥接。'
);
argon_flink_test_assert(
    strpos($page, 'dispatchFriendLinkWorkerJob') !== false
    && strpos($page, "rest_url('argon/v1/friend-link-ticket')") !== false
    && strpos($page, "wp_create_nonce('wp_rest')") !== false
    && strpos($page, "requestHeaders['X-WP-Nonce']") !== false
    && strpos($page, 'detector.detectSite(link.url)') === false,
    '批量复检没有使用完整 REST Cookie 认证转发 Worker 异步任务。'
);
$edit_check_start = strpos($page, "var editCheckBtn = document.getElementById('edit-check-btn');");
$edit_check_end = strpos($page, '// 保存编辑', $edit_check_start);
$edit_check_section = substr($page, $edit_check_start, $edit_check_end - $edit_check_start);
argon_flink_test_assert(
    strpos($edit_check_section, "flinkAction('update'") !== false
    && strpos($edit_check_section, 'dispatchFriendLinkWorkerJob(id') !== false
    && strpos($edit_check_section, 'detector.checkBacklink') === false,
    '编辑窗口的互链检测没有先保存页面地址再调用 Cloudflare Worker。'
);
argon_flink_test_assert(
    strpos($page, "checkAllBtn.onclick = function()") !== false
    && strpos($page, "checkAllBtn.addEventListener('click'") === false
    && strpos($page, "adminPanel.dataset.argonFriendLinksBound === 'true'") !== false,
    '批量检测按钮不是幂等绑定，PJAX 重执行时会累计确认弹窗。'
);
argon_flink_test_assert(
    strpos($page, 'id="check-all-btn"') !== false
    && strpos($page, 'aria-label="<?php echo esc_attr($friend_link_worker_ready') !== false
    && strpos($page, '<i class="fa fa-refresh" aria-hidden="true"></i></button>') !== false,
    '批量检测按钮仍显示冗余文字，而不是图标和无障碍名称。'
);
argon_flink_test_assert(
    strpos($worker_bridge, 'ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET') !== false
    && strpos($worker_bridge, 'ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET') !== false
    && strpos($worker_bridge, "hash_hmac('sha256'") !== false
    && strpos($worker_bridge, 'hash_equals(') !== false,
    'Worker 桥接没有使用独立 HMAC 密钥和恒定时间比较。'
);
argon_flink_test_assert(
    strpos($worker_bridge, 'function argon_friend_link_worker_is_configured()') !== false
    && strpos($worker_bridge, 'if (!argon_friend_link_worker_is_configured())') !== false
    && strpos($page, 'if ($is_admin && $friend_link_worker_ready)') !== false
    && strpos($page, 'var argonFriendLinkWorkerReady =') !== false
    && strpos($page, 'if (!argonFriendLinkWorkerReady)') !== false,
    'Worker 未配置时仍可能创建令牌、自动批量派发或从管理操作派发。'
);
argon_flink_test_assert(
    strpos($functions, '$worker_target_changed = false;') !== false
    && strpos($functions, "['worker_generation'] = '';") !== false
    && strpos($page, "'worker_refresh_required' => \$updated && \$worker_refresh_required") !== false,
    '编辑目标地址后没有废弃旧 Worker 代际并触发新任务。'
);
argon_flink_test_assert(
    strpos($worker_bridge, "!in_array(\$known_backlink_state, array('found', 'missing'), true)") !== false,
    'Worker 的 unknown 回调仍可能覆盖已知互链结论。'
);
argon_flink_test_assert(
    strpos($page, 'id="apply-display-name"') !== false
    && strpos($page, 'name="apply_display_name"') !== false,
    '访客申请缺少自动识别副标题字段。'
);
argon_flink_test_assert(
    strpos($page, 'id="apply-links-page"') !== false
    && strpos($page, "key: 'linksPage'") !== false
    && strpos($page, 'id="add-links-page"') !== false,
    '自动资料识别没有覆盖访客和管理员的对方友链页面字段。'
);
argon_flink_test_assert(
    strpos($page, 'class="flink-avatar has-image') === false
    && substr_count($page, 'alt="" aria-hidden="true" decoding="async"') >= 2,
    '友链头像在加载成功前隐藏了字母回退，或仍可能显示 alt 文本。'
);
argon_flink_test_assert(
    strpos($page, 'flink-verified') === false
    && strpos($page, 'fa fa-check-circle flink-verified') === false,
    '访客可见的友链互链认证图标仍在输出。'
);
argon_flink_test_assert(
    strpos($page, "<?php _e('填写可自动通过', 'argon'); ?>") !== false
    && strpos($page, '便于管理员浏览器检测') === false,
    '友链页面提示文案没有恢复为原有自动通过说明。'
);

$open_modal_start = strpos($page, 'window.openEditModal = function(id)');
$open_modal_end = strpos($page, 'window.closeEditModal = function()', $open_modal_start);
$open_modal_section = substr($page, $open_modal_start, $open_modal_end - $open_modal_start);
argon_flink_test_assert(
    strpos($open_modal_section, "classList.add('show')") !== false
    && strpos($open_modal_section, "classList.add('show')") < strpos($open_modal_section, "flinkAction('get'"),
    '编辑窗口仍等待网络请求完成后才打开。'
);
$overlay_rule_start = strpos($page, '.flink-modal-overlay {');
$overlay_rule_end = strpos($page, '.flink-modal-overlay.show', $overlay_rule_start);
$overlay_rule = substr($page, $overlay_rule_start, $overlay_rule_end - $overlay_rule_start);
argon_flink_test_assert(
    strpos($overlay_rule, 'var(--motion-popup-duration)') !== false
    && strpos($overlay_rule, 'var(--motion-layer-duration)') === false,
    '编辑窗口仍使用迟缓的层级动画时长。'
);
argon_flink_test_assert(
    strpos($page, "case 'browser_sync':") !== false
    && strpos($page, "case 'check_all':") !== false
    && strpos($page, '服务器外联检测已停用') !== false,
    '页面端旧服务器外联入口没有熔断。'
);

$add_start = strpos($functions, 'function argon_add_friend_link(');
$add_end = strpos($functions, 'function argon_update_friend_link(', $add_start);
$add_section = substr($functions, $add_start, $add_end - $add_start);
argon_flink_test_assert(
    strpos($add_section, 'argon_fetch_site_info(') === false,
    '新增友链仍会由服务器抓取目标站。'
);

$fetch_start = strpos($functions, 'function argon_fetch_site_info(');
$fetch_end = strpos($functions, 'function argon_deduplicate_friend_links(', $fetch_start);
$fetch_section = substr($functions, $fetch_start, $fetch_end - $fetch_start);
argon_flink_test_assert(
    strpos($fetch_section, 'wp_remote_get(') === false
    && strpos($fetch_section, 'wp_remote_head(') === false,
    '兼容资料抓取函数仍包含服务器外联实现。'
);

$application_start = strpos($functions, 'function argon_handle_link_application_v3(');
$application_end = strpos($functions, 'function argon_process_friend_link_application_check(', $application_start);
$application_section = substr($functions, $application_start, $application_end - $application_start);
argon_flink_test_assert(
    strpos($application_section, 'argon_fetch_site_info(') === false
    && strpos($application_section, 'argon_check_backlink(') === false
    && strpos($application_section, 'wp_schedule_single_event(') === false,
    '访客申请仍会抓站或调度服务器检测。'
);
argon_flink_test_assert(
    strpos($functions, "wp_unschedule_hook('argon_friend_links_auto_check')") !== false
    && strpos($functions, "wp_unschedule_hook('argon_daily_link_check')") !== false
    && strpos($functions, "wp_unschedule_hook('argon_friend_link_application_check')") !== false,
    '没有清理历史友链外联定时任务。'
);
argon_flink_test_assert(
    strpos($functions, "add_action('argon_friend_links_auto_check'") === false
    && strpos($functions, "add_action('argon_daily_link_check'") === false
    && strpos($functions, "add_action('argon_friend_link_application_check'") === false,
    '服务器友链外联任务仍处于注册状态。'
);
$checks_start = strpos($functions, 'function argon_check_site_accessible(');
$checks_end = strpos($functions, '// ==================== AI 查询组件', $checks_start);
$checks_section = substr($functions, $checks_start, $checks_end - $checks_start);
argon_flink_test_assert(
    strpos($checks_section, 'wp_remote_get(') === false
    && strpos($checks_section, 'wp_remote_head(') === false,
    '友链兼容检测函数仍包含服务器外联请求。'
);
argon_flink_test_assert(
    strpos($functions, "case 'verify':\n            wp_send_json_error('服务器外联检测已停用，请在友链页面使用浏览器检测');") !== false
    && strpos($functions, "case 'check_access':\n            wp_send_json_error('服务器外联检测已停用，请在友链页面使用浏览器检测');") !== false,
    '旧版 AJAX 仍可能触发服务器外联。'
);

if ($failures) {
    fwrite(STDERR, "自动友链检测测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "自动友链检测测试通过。\n";
