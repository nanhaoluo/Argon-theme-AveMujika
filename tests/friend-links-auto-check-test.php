<?php

/**
 * 自动友链检测与博客信息抓取的轻量契约测试。
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

require_once $root . '/inc/friend-link-algorithms.php';
$first_window = argon_friend_link_scan_window(5, 0, 3);
$second_window = argon_friend_link_scan_window(5, $first_window['next_cursor'], 3);
argon_flink_test_assert(
    $first_window === array('start' => 0, 'count' => 3, 'next_cursor' => 3, 'complete' => false),
    '首轮候选窗口计算错误。'
);
argon_flink_test_assert(
    $second_window === array('start' => 3, 'count' => 2, 'next_cursor' => 0, 'complete' => true),
    '尾轮候选窗口发生回绕、重复或遗漏。'
);

foreach (array(
    'argon_fetch_site_info',
    'argon_check_backlink',
    'argon_check_friend_link_backlink',
    'argon_sync_friend_link',
    'argon_process_friend_link_check_batch'
) as $function_name) {
    argon_flink_test_assert(
        strpos($functions, 'function ' . $function_name . '(') !== false,
        '缺少共享服务函数：' . $function_name
    );
}

argon_flink_test_assert(
    strpos($functions, "'argon_friend_links_6h'") !== false
    && strpos($functions, "'argon_friend_links_auto_check'") !== false,
    '自动检测任务没有按 6 小时间隔注册。'
);
argon_flink_test_assert(
    strpos($functions, 'get_transient($lock_key)') !== false
    && strpos($functions, 'set_transient($lock_key') !== false
    && strpos($functions, 'add_option($lock_option') !== false,
    '分批检测缺少并发锁。'
);
argon_flink_test_assert(
    strpos($functions, 'argon_friend_link_next_check_delay') !== false
    && strpos($functions, 'pow(2, $exponent)') !== false
    && strpos($functions, "'next_check_at'") !== false,
    '失败重试没有采用指数退避。'
);
argon_flink_test_assert(
    strpos($functions, 'argon_friend_link_info_refresh_interval') !== false
    && strpos($functions, '$refresh_site_info') !== false,
    '站点资料抓取与状态检测没有独立刷新周期。'
);
argon_flink_test_assert(
    strpos($functions, "'backlink_candidate_cursor'") !== false
    && strpos($functions, "'backlink_scan_successes'") !== false
    && strpos($functions, "'backlink_targets_hash'") !== false,
    '候选页分轮状态没有持久化。'
);
argon_flink_test_assert(
    strpos($functions, 'argon_friend_link_info_cache_key') !== false
    && strpos($functions, "'argon_flink_back_'") !== false,
    '站点信息或互链检测缺少缓存。'
);
argon_flink_test_assert(
    strpos($functions, "array('/friends/', '/links/', '/friend-links/', '/links.html')") !== false,
    '自动互链检测缺少常见友链页候选。'
);

foreach (array('author_name', 'site_logo', 'canonical_url', 'feed_url', 'language', 'keywords', 'theme_color') as $field) {
    argon_flink_test_assert(substr_count($functions, "'" . $field . "'") >= 2, '博客资料字段未被抓取并保存：' . $field);
}

argon_flink_test_assert(
    strpos($page, 'argon_process_friend_link_check_batch($batch_size, true)') !== false,
    '手动批量检测没有复用后台检测服务。'
);
$application_start = strpos($functions, 'function argon_handle_link_application_v3(');
$application_end = strpos($functions, 'function argon_process_friend_link_application_check(', $application_start);
$application_section = substr($functions, $application_start, $application_end - $application_start);
argon_flink_test_assert(
    strpos($application_section, 'argon_fetch_site_info(') === false
    && strpos($application_section, 'argon_check_backlink(') === false
    && strpos($application_section, "wp_schedule_single_event(time() + 30, 'argon_friend_link_application_check'") !== false,
    '访客申请仍会同步抓站或未排入后台检测。'
);
argon_flink_test_assert(
    strpos($page, 'argon_fetch_site_info($url, true)') !== false,
    '手动刷新未绕过缓存获取最新资料。'
);
argon_flink_test_assert(
    strpos($page, '系统每 6 小时自动检测一批友链') !== false,
    '友链管理入口没有说明自动检测频率。'
);

if ($failures) {
    fwrite(STDERR, "自动友链检测测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "自动友链检测测试通过。\n";
