<?php

$root = dirname(__DIR__);
$surface = file_get_contents($root . '/inc/special-page-surface.php');
$bangumi = file_get_contents($root . '/page-bangumi.php');
$ai = file_get_contents($root . '/ai-summary-query.php');
$friends = file_get_contents($root . '/friend-links.php');
$feedback = file_get_contents($root . '/feedback.php');
$bangumiJs = file_get_contents($root . '/assets/js/bangumi.js');
$bangumiCss = file_get_contents($root . '/assets/css/bangumi.css');
$bangumiBackend = file_get_contents($root . '/inc/bangumi.php');
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
    && strpos($surface, '$title_id =') !== false
    && strpos($surface, 'class="argon-special-page-header-copy"') !== false
    && strpos($surface, 'argon-special-page-header-icon') === false
    && strpos($surface, '$stat_icon =') === false,
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
    $bangumi !== false
    && strpos($bangumi, "'value_id' => 'bangumi-stat-want'") === false
    && strpos($bangumi, "'label' => __('想看', 'argon')") === false,
    'bangumi page header must omit the want-to-watch statistic'
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
    && strpos($bangumiCss, 'grid-template-columns: repeat(auto-fill, minmax(min(100%, 156px), 1fr));') !== false
    && strpos($bangumiCss, 'align-items: start;') !== false
    && strpos($bangumiCss, '.bangumi-grid { display: flex;') === false
    && strpos($bangumiCss, '@media screen and (min-width: 1200px)') === false
    && strpos($bangumiCss, 'height: 190px;') !== false
    && strpos($bangumiCss, 'font-size: 14px; font-weight: 600; line-height: 1.45;') !== false,
    'bangumi grid must derive responsive columns while keeping base cards at a uniform size'
);

argon_special_surface_assert(
    $bangumi !== false
    && $bangumiJs !== false
    && $bangumiBackend !== false
    && strpos($bangumi, "argon_bangumi_get_combined_page('all', 2, 1, false, false, true)") !== false
    && strpos($bangumi, "wp_enqueue_style('argon-bangumi'") !== false
    && strpos($bangumiJs, 'item.cover_local || item.cover') !== false
    && strpos($bangumiJs, 'responsePage === 1 ? (data.items || []).slice()') !== false
    && strpos($bangumiJs, 'requestPage(state.status, 1);') !== false
    && strpos($bangumiBackend, 'function argon_bangumi_get_cached_page(') !== false
    && strpos($bangumiBackend, 'argon_bangumi_schedule_cover_localization($page[\'items\']);') !== false
    && strpos($bangumiBackend, "'stats' => argon_bangumi_get_stats(\$page, \$status)") !== false,
    'bangumi loading must render cached data first, fetch missing data asynchronously, and defer cover localization'
);

$bangumi_title_rule = array();
$bangumi_title_rule_found = $bangumiCss !== false && preg_match('/\.bangumi-card-title\s*\{[^}]*\}/', $bangumiCss, $bangumi_title_rule) === 1;

argon_special_surface_assert(
    $bangumiJs !== false
    && $bangumiCss !== false
    && strpos($bangumiJs, 'function renderCardPreview(') !== false
    && strpos($bangumiJs, 'function positionCardPreview(') !== false
    && strpos($bangumiJs, 'Math.max(480, sourceRect.height + 48)') !== false
    && strpos($bangumiJs, 'pjax:beforeReplace.argonBangumiPreview') !== false
    && strpos($bangumiJs, 'bangumi-card-preview') !== false
    && strpos($bangumiCss, '.bangumi-card-preview {') !== false
    && strpos($bangumiCss, 'position: fixed;') !== false
    && strpos($bangumiCss, 'grid-template-columns: minmax(128px, 0.78fr) minmax(0, 1.22fr);') !== false
    && strpos($bangumiCss, 'min-height: min(480px, calc(100vh - 24px));') !== false
    && $bangumi_title_rule_found
    && strpos($bangumi_title_rule[0], '-webkit-line-clamp: 2') !== false,
    'bangumi cards must keep a uniform compact layout and open a larger two-dimensional preview'
);

argon_special_surface_assert(
    $bangumi !== false
    && strpos($bangumi, "'title' => __('追番', 'argon')") !== false
    && strpos($bangumi, "'subtitle' =>") === false
    && strpos($bangumi, "'icon' =>") === false,
    'bangumi page header must use the direct title without a descriptive subtitle'
);

if ($failures) {
    fwrite(STDERR, "Special page surface contract test failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Special page surface contract test passed.\n";
