<?php

$root = dirname(__DIR__);
$surface = file_get_contents($root . '/inc/special-page-surface.php');
$bangumi = file_get_contents($root . '/page-bangumi.php');
$ai = file_get_contents($root . '/ai-summary-query.php');
$friends = file_get_contents($root . '/friend-links.php');
$feedback = file_get_contents($root . '/feedback.php');
$bangumiJs = file_get_contents($root . '/assets/js/bangumi.js');
$failures = array();

function argon_special_surface_assert($condition, $message) {
    global $failures;
    if (!$condition) $failures[] = $message;
}

argon_special_surface_assert(
    $surface !== false
    && strpos($surface, 'overflow-wrap: break-word; word-break: normal;') !== false
    && strpos($surface, 'overflow-wrap: anywhere') === false
    && strpos($surface, '$value_id =') !== false
    && strpos($surface, '$title_id =') !== false,
    'shared surface must provide non-destructive wrapping and reusable header slots'
);

argon_special_surface_assert(
    $bangumi !== false
    && strpos($bangumi, "require_once get_template_directory() . '/inc/special-page-surface.php';") !== false
    && strpos($bangumi, "argon_special_page_surface_open('bangumi-page')") !== false
    && strpos($bangumi, 'argon_special_page_surface_header(array(') !== false
    && strpos($bangumi, "'value_id' => 'bangumi-stat-total'") !== false
    && strpos($bangumi, 'argon_special_page_surface_close();') !== false,
    'bangumi page must use the shared surface and dynamic statistics'
);

argon_special_surface_assert(
    $ai !== false
    && strpos($ai, "require_once get_template_directory() . '/inc/special-page-surface.php';") !== false
    && substr_count($ai, "argon_special_page_surface_open('ai-verify-page')") === 2
    && substr_count($ai, 'argon_special_page_surface_close();') === 2
    && strpos($ai, '.ai-records-table td:nth-child(2), .ai-record-code { white-space: nowrap;') !== false
    && strpos($ai, 'overflow-wrap: anywhere') === false
    && strpos($ai, '<div class="page-information-card-container"></div>') === false
    && strpos($ai, '<main id="main"') === false,
    'AI query branches must use the shared surface and keep record codes on one line'
);

argon_special_surface_assert(
    $friends !== false
    && $feedback !== false
    && strpos($friends, "argon_special_page_surface_open('flinks-page')") !== false
    && strpos($feedback, "argon_special_page_surface_open('feedback-page')") !== false
    && strpos($friends, 'word-break: break-all') === false
    && strpos($feedback, 'word-break: break-all') === false,
    'friend and feedback pages must keep long text wrapping natural'
);

argon_special_surface_assert(
    $bangumiJs !== false
    && strpos($bangumiJs, "getElementById('bangumi-stat-' + key)") !== false,
    'bangumi client must update shared header statistic values'
);

if ($failures) {
    fwrite(STDERR, "Special page surface contract test failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Special page surface contract test passed.\n";
