<?php

$root = dirname(__DIR__);
$surface = file_get_contents($root . '/inc/special-page-surface.php');
$bangumi = file_get_contents($root . '/page-bangumi.php');
$ai = file_get_contents($root . '/ai-summary-query.php');
$friends = file_get_contents($root . '/friend-links.php');
$feedback = file_get_contents($root . '/feedback.php');
$bangumiJs = file_get_contents($root . '/assets/js/bangumi.js');
$bangumiCss = file_get_contents($root . '/assets/css/bangumi.css');
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

$bangumi_surface_markup = '<article class="post card shadow-sm bg-white border-0 argon-special-page-card bangumi-page-surface">';
$bangumi_surface_position = $bangumi !== false ? strpos($bangumi, $bangumi_surface_markup) : false;
$bangumi_toolbar_position = $bangumi !== false ? strpos($bangumi, 'class="bangumi-toolbar"') : false;
$bangumi_error_position = $bangumi !== false ? strpos($bangumi, 'id="bangumi-error"') : false;
$bangumi_surface_close_position = ($bangumi !== false && $bangumi_error_position !== false)
    ? strpos($bangumi, '</article>', $bangumi_error_position)
    : false;

argon_special_surface_assert(
    $bangumiJs !== false
    && $bangumiCss !== false
    && substr_count($bangumiJs, 'class="bangumi-card"') === 2
    && $bangumi_surface_position !== false
    && $bangumi_toolbar_position !== false
    && $bangumi_error_position !== false
    && $bangumi_surface_close_position !== false
    && $bangumi_surface_position < $bangumi_toolbar_position
    && $bangumi_toolbar_position < $bangumi_error_position
    && $bangumi_error_position < $bangumi_surface_close_position
    && strpos($bangumiCss, '.bangumi-page-surface { padding: 18px; }') !== false
    && strpos($bangumiCss, '.bangumi-page-surface { padding: 12px; }') !== false,
    'bangumi page content must use one shared page surface below the header without changing item-card ownership'
);

argon_special_surface_assert(
    $bangumiCss !== false
    && strpos($bangumiCss, '.bangumi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(172px, 1fr)); gap: 14px; }') !== false
    && strpos($bangumiCss, '@media screen and (min-width: 1200px)') === false
    && strpos($bangumiCss, '.bangumi-card-body { display: flex; min-width: 0; flex: 1; flex-direction: column; padding: 11px 12px 12px; }') !== false
    && strpos($bangumiCss, 'font-size: 14px; font-weight: 600; line-height: 1.45;') !== false,
    'bangumi desktop grid must derive dense card columns from its container width'
);

argon_special_surface_assert(
    $bangumi !== false
    && strpos($bangumi, "'subtitle' =>") === false,
    'bangumi page header must not render the removed descriptive subtitle'
);

if ($failures) {
    fwrite(STDERR, "Special page surface contract test failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Special page surface contract test passed.\n";
