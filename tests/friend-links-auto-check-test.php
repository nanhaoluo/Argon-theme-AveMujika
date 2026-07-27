<?php

/**
 * 浏览器友链检测与源站保护的轻量契约测试。
 *
 * 运行：php tests/friend-links-auto-check-test.php
 */

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.php');
$page = file_get_contents($root . '/friend-links.php');

if ($functions === false || $page === false) {
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
    && strpos($page, 'detector.detectSite') !== false
    && strpos($page, 'detector.checkBacklink') !== false,
    '友链页没有完整接入浏览器检测模块。'
);
argon_flink_test_assert(
    strpos($page, 'id="apply-display-name"') !== false
    && strpos($page, 'name="apply_display_name"') !== false,
    '访客申请缺少自动识别副标题字段。'
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
