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
    && strpos($worker_bridge, "register_rest_route('argon/v1', '/friend-link-result'") !== false,
    '主题没有注册 Worker 回调桥接。'
);
argon_flink_test_assert(
    strpos($worker_bridge, 'function argon_friend_link_worker_dispatch_async(') !== false
    && strpos($worker_bridge, 'wp_remote_post($ticket[\'url\']') !== false
    && strpos($worker_bridge, "'blocking' => false") !== false
    && strpos($worker_bridge, "add_action('argon_friend_link_worker_dispatch_pending'") !== false
    && strpos($worker_bridge, "wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly'") !== false
    && strpos($worker_bridge, "add_action('argon_friend_link_worker_sync_pending'") !== false
    && strpos($worker_bridge, "'argon_friend_link_worker_five_minutes', 'argon_friend_link_worker_sync_pending'") !== false
    && strpos($page, 'dispatchFriendLinkWorkerJob') === false
    && strpos($page, "rest_url('argon/v1/friend-link-ticket')") === false,
    '首次 Worker 任务仍依赖管理员浏览器，或服务端没有非阻塞派发与自动补派。'
);
$edit_check_start = strpos($page, "var editCheckBtn = document.getElementById('edit-check-btn');");
$edit_check_end = strpos($page, '// 保存编辑', $edit_check_start);
$edit_check_section = substr($page, $edit_check_start, $edit_check_end - $edit_check_start);
$worker_poll_start = strpos($page, 'function pollWorkerDetection(');
$worker_poll_end = strpos($page, 'function normalizeUrlInput(', $worker_poll_start);
$worker_poll_section = substr($page, $worker_poll_start, $worker_poll_end - $worker_poll_start);
argon_flink_test_assert(
    strpos($edit_check_section, 'detector.checkBacklink') !== false
    && strpos($edit_check_section, "flinkAction('browser_result'") !== false
    && strpos($edit_check_section, "backlink_state: backlinkState") !== false
    && strpos($edit_check_section, "backlinkState === 'unknown'") !== false
    && strpos($edit_check_section, "flinkAction('dispatch_worker'") === false
    && strpos($edit_check_section, 'pollWorkerDetection(') === false,
    '管理员手动互链检测没有在浏览器执行并保存，或仍错误派发 Worker。'
);
argon_flink_test_assert(
    strpos($page, 'function pollWorkerDetection(') !== false
    && strpos($page, "flinkAction('get', { id: id, sync_worker_result: '1' }") !== false
    && strpos($page, 'argon_friend_link_worker_sync_result($id)') !== false
    && strpos($page, "workerStatus === 'submitted'") !== false
    && strpos($page, "workerStatus === 'complete'") !== false
    && strpos($page, "workerStatus === 'unknown'") !== false
    && strpos($page, 'setTimeout(poll,') !== false,
    'Worker 检测提交后没有持续读取已保存状态，编辑窗口会永久停留在检测中。'
);
argon_flink_test_assert(
    strpos($worker_bridge, 'function argon_friend_link_worker_apply_result_payload(') !== false
    && strpos($worker_bridge, 'function argon_friend_link_worker_sync_result(') !== false
    && strpos($worker_bridge, "'/v1/jobs/' . rawurlencode(\$link_id) . '/result'") !== false
    && strpos($worker_bridge, "argon_friend_link_worker_apply_result_payload(\$payload, 'pull')") !== false,
    'Worker 回调失败时，WordPress 没有签名主动拉取并复用同一结果落库逻辑。'
);
argon_flink_test_assert(
    strpos($page, 'Date.now() - startedAt >= 300000') !== false
    && strpos($page, '30～180 秒') !== false
    && strpos($worker_poll_section, "document.getElementById('edit-save-btn').disabled = true") === false,
    '状态轮询没有覆盖 Queue 的随机延迟，或检测期间仍无必要地锁住保存按钮。'
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
    && strpos($page, 'var argonFriendLinkWorkerReady =') !== false
    && strpos($page, 'res.worker_refresh_required && !argonFriendLinkWorkerReady') !== false,
    'Worker 未配置时仍可能创建任务或从管理操作派发。'
);
argon_flink_test_assert(
    strpos($functions, '$worker_target_changed = false;') !== false
    && strpos($functions, "['worker_generation'] = '';") !== false
    && strpos($page, 'argon_friend_link_worker_dispatch_async($id)') !== false,
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
    && strpos($page, 'name="apply_links_page_manual"') !== false
    && strpos($page, "key: 'linksPage'") !== false
    && strpos($page, 'id="add-links-page"') !== false,
    '自动资料识别没有覆盖访客和管理员的对方友链页面字段。'
);
argon_flink_test_assert(
    strpos($functions, "'name_source' => \$name_is_manual ? 'visitor' : 'worker'") !== false
    && strpos($functions, "'links_page_source' => \$links_page_is_manual ? 'visitor' : 'worker'") !== false
    && strpos($page, "var nameSource = addNameIsManual ? 'admin' : 'worker'") !== false
    && strpos($worker_bridge, 'argon_friend_link_worker_can_update_field') !== false,
    '友链字段没有记录 Worker、访客和管理员三档来源优先级。'
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
    strpos($open_modal_section, "window.ArgonMotion.fast(modal, true, { className: 'show' })") !== false
    && strpos($open_modal_section, "window.ArgonMotion.fast(modal, true, { className: 'show' })") < strpos($open_modal_section, "flinkAction('get'"),
    '编辑窗口仍等待网络请求完成后才打开。'
);
argon_flink_test_assert(
    strpos($open_modal_section, "link.worker_status === 'submitted'") !== false
    && strpos($open_modal_section, 'pollWorkerDetection(id, contextSequence, editBacklinkSequence') !== false,
    '页面刷新后重新打开编辑窗口时，没有续接尚未完成的 Worker 状态轮询。'
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
    strpos($page, "case 'browser_result':") !== false
    && strpos($page, 'argon_friend_link_apply_browser_result($id, $_POST)') !== false
    && strpos($worker_bridge, "\$update['check_source'] = 'admin_browser';") !== false
    && strpos($worker_bridge, "\$update['browser_backlink_result'] = \$backlink_state;") !== false
    && strpos($worker_bridge, "in_array(\$backlink_state, array('found', 'missing'), true)") !== false
    && strpos($worker_bridge, "\$detection_source !== 'opaque'") !== false
    && strpos($page, "case 'check_all':") !== false
    && strpos($page, "case 'dispatch_worker':") !== false
    && strpos($page, 'browserBatchLinks') === false,
    '管理员浏览器结果没有独立落库，或批量自动检测边界被破坏。'
);
$apply_detect_start = strpos($page, 'function detectApplicationSite()');
$apply_detect_end = strpos($page, "urlInput.addEventListener('input'", $apply_detect_start);
$apply_detect_section = substr($page, $apply_detect_start, $apply_detect_end - $apply_detect_start);
$add_detect_start = strpos($page, 'function detectAddSite(button)');
$add_detect_end = strpos($page, '// 移除自动审核标记', $add_detect_start);
$add_detect_section = substr($page, $add_detect_start, $add_detect_end - $add_detect_start);
$edit_detect_start = strpos($page, "editFetchBtn.addEventListener('click'");
$edit_detect_end = strpos($page, '// 检测互链', $edit_detect_start);
$edit_detect_section = substr($page, $edit_detect_start, $edit_detect_end - $edit_detect_start);
$edit_save_start = strpos($page, "editSaveBtn.addEventListener('click'");
$edit_save_end = strpos($page, '// 删除', $edit_save_start);
$edit_save_section = substr($page, $edit_save_start, $edit_save_end - $edit_save_start);
argon_flink_test_assert(
    strpos($page, 'var editFieldSnapshots = {}') !== false
    && strpos($edit_save_section, "linksPage !== editFieldSnapshots.links_page") !== false
    && strpos($edit_save_section, "avatar !== editFieldSnapshots.avatar") !== false
    && strpos($edit_save_section, "description !== editFieldSnapshots.description") !== false,
    '保存编辑仍会用异步检测前的旧表单覆盖 Worker 已回写的资料。'
);
argon_flink_test_assert(
    strpos($worker_bridge, '不得覆盖此前已经确认的 found/missing 结论') !== false
    && strpos($apply_detect_section, '提交后将由 Worker 异步检测并补全资料') !== false
    && strpos($page, "case 'browser_probe_preview':") !== false
    && strpos($worker_bridge, 'function argon_friend_link_worker_probe($payload)') !== false
    && strpos($add_detect_section, "flinkAction('browser_probe_preview'") !== false
    && strpos($add_detect_section, 'if (result && result.success && result.readable) return result;') !== false
    && strpos($add_detect_section, 'return requestWorkerPreview();') !== false
    && strpos($add_detect_section, 'Worker 已自动识别站点资料') !== false
    && strpos($edit_detect_section, "flinkAction('browser_result'") !== false
    && strpos($edit_detect_section, "flinkAction('browser_probe'") !== false
    && strpos($edit_detect_section, '浏览器已直接读取首页并保存站点资料') !== false
    && strpos($edit_detect_section, 'Worker 已直接抓取首页并保存资料') !== false,
    '管理员新增/更新未按“浏览器直读优先、Worker 同步回退”执行，或未知结果会覆盖既有结论。'
);
argon_flink_test_assert(
    strpos($edit_detect_section, 'target_url: url') !== false
    && strpos($edit_check_section, 'target_url: siteUrl') !== false
    && strpos($worker_bridge, 'hash_equals($current_target_url, $target_url)') !== false,
    '管理员浏览器结果没有精确绑定检测开始时的友链地址。'
);
argon_flink_test_assert(
    strpos($edit_check_section, 'if (!target.confirmedPage)') !== false
    && strpos($edit_check_section, 'useProxy: true') !== false
    && strpos($edit_check_section, "linksPage: ''") !== false
    && strpos($edit_check_section, "flinkAction('browser_probe'") !== false
    && strpos($edit_check_section, 'probeUrl: detectedLinksPage || siteUrl') === false
    && strpos($edit_check_section, "'links_page_not_found'") !== false,
    '首页未暴露友链入口时没有交给 Worker 发现常见路径或 WordPress Pages。'
);
argon_flink_test_assert(
    strpos($edit_check_section, 'else if (!confirmedPage)') !== false
    && strpos($edit_check_section, '未发现可确认的友链页面，已保留此前互链结论') !== false
    && strpos($edit_check_section, '对方页面暂时不可读，已保留此前互链结论') !== false,
    '管理员手动检测未发现友链页面时仍错误提示为 CORS 不可读。'
);
argon_flink_test_assert(
    strpos($page, 'function beginEditBrowserOperation(') !== false
    && strpos($page, "document.getElementById('edit-url').readOnly = true") !== false
    && strpos($page, "document.getElementById('edit-links-page').readOnly = true") !== false
    && strpos($page, "document.getElementById('edit-save-btn').disabled = true") !== false,
    '管理员浏览器检测期间仍可修改目标或并发保存，旧结果可能写入新地址。'
);
$unknown_status_pos = strpos($page, "link.backlink_check_result === 'unknown'");
$missing_links_page_pos = strrpos(substr($page, 0, $unknown_status_pos), 'if (!link.links_page)');
argon_flink_test_assert(
    $missing_links_page_pos !== false
    && $missing_links_page_pos < $unknown_status_pos
    && strpos($page, 'Worker 未发现友链页面') !== false
    && strpos($page, 'Worker 无法读取友链页面') !== false
    && strpos($page, '目标网站暂时不可读') !== false,
    'Worker 的站点不可读、未发现友链页和友链页不可读仍被混为同一状态。'
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
    && strpos($application_section, "argon_friend_link_worker_dispatch_async(\$new_link['id'])") !== false
    && strpos($application_section, 'dispatch_token') === false,
    '访客申请没有由服务端异步派发 Worker，或仍依赖一次性浏览器令牌。'
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
    strpos($functions, "case 'verify':") !== false
    && strpos($functions, "case 'check_access':") !== false
    && strpos($functions, 'argon_friend_link_worker_dispatch_async($id)') !== false,
    '旧版 AJAX 没有改为派发 Cloudflare Worker。'
);

if ($failures) {
    fwrite(STDERR, "自动友链检测测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "自动友链检测测试通过。\n";
