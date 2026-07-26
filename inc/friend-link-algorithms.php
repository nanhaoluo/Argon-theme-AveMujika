<?php

/**
 * 计算本轮反链候选扫描窗口。
 * 窗口不会在单轮内回绕，避免目标数量不能整除批量大小时重复扫描或永久遗漏。
 */
function argon_friend_link_scan_window($target_count, $cursor, $limit) {
    $target_count = max(0, intval($target_count));
    $limit = max(1, intval($limit));
    if ($target_count === 0) {
        return array('start' => 0, 'count' => 0, 'next_cursor' => 0, 'complete' => true);
    }

    $start = max(0, intval($cursor));
    if ($start >= $target_count) $start = 0;
    $count = min($limit, $target_count - $start);
    $next = $start + $count;
    $complete = $next >= $target_count;
    return array(
        'start' => $start,
        'count' => $count,
        'next_cursor' => $complete ? 0 : $next,
        'complete' => $complete
    );
}
