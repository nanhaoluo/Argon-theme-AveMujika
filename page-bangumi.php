<?php
/**
 * Template Name: 追番展示页
 * Template Post Type: page
 *
 * @package Argon
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once get_template_directory() . '/inc/special-page-surface.php';

$bangumi_enabled = function_exists('argon_bangumi_is_enabled') && argon_bangumi_is_enabled();
$bangumi_initial = array(
	'items' => array(),
	'page' => 1,
	'has_more' => false,
	'error' => '',
);
$bangumi_stats = array('total' => 0, 'watching' => 0, 'want' => 0, 'watched' => 0);
if ($bangumi_enabled && function_exists('argon_bangumi_get_combined_page')) {
	$bangumi_initial = argon_bangumi_get_combined_page('all', 2, 1, false, false, true);
	$bangumi_stats = function_exists('argon_bangumi_get_stats') ? argon_bangumi_get_stats($bangumi_initial) : $bangumi_stats;
	if (function_exists('argon_bangumi_schedule_stats_refresh')) argon_bangumi_schedule_stats_refresh();
}
$bangumi_asset_version = function_exists('argon_get_asset_file_version') ? argon_get_asset_file_version('assets/js/bangumi.js') : $GLOBALS['theme_version'];
$bangumi_style_url = add_query_arg('ver', function_exists('argon_get_asset_file_version') ? argon_get_asset_file_version('assets/css/bangumi.css') : $GLOBALS['theme_version'], get_template_directory_uri() . '/assets/css/bangumi.css');
$bangumi_script_url = add_query_arg('ver', $bangumi_asset_version, get_template_directory_uri() . '/assets/js/bangumi.js');
wp_enqueue_style('argon-bangumi', $bangumi_style_url, array(), null);

$bangumi_config = array(
	'ajaxUrl' => admin_url('admin-ajax.php'),
	'styleUrl' => $bangumi_style_url,
	'nonce' => wp_create_nonce('argon_bangumi_load'),
	'enabled' => $bangumi_enabled,
	'initialStatus' => 2,
	'initialType' => 'all',
	'initialItems' => isset($bangumi_initial['items']) ? $bangumi_initial['items'] : array(),
	'initialPage' => isset($bangumi_initial['page']) ? absint($bangumi_initial['page']) : 1,
	'initialHasMore' => !empty($bangumi_initial['has_more']),
	'initialNeedsRefresh' => !empty($bangumi_initial['needs_refresh']),
	'initialError' => isset($bangumi_initial['error']) ? (string)$bangumi_initial['error'] : '',
	'stats' => $bangumi_stats,
);

get_header();
argon_special_page_surface_open('bangumi-page');
?>
	<section class="bangumi-page" aria-labelledby="bangumi-page-title">
		<?php
		$bangumi_header_stats = array(
			array(
				'label' => __('共', 'argon'),
				'value' => number_format_i18n((int)$bangumi_stats['total']),
				'suffix' => __('部', 'argon'),
				'value_id' => 'bangumi-stat-total',
			),
			array(
				'label' => __('在看', 'argon'),
				'value' => number_format_i18n((int)$bangumi_stats['watching']),
				'value_id' => 'bangumi-stat-watching',
			),
			array(
				'label' => __('看过', 'argon'),
				'value' => number_format_i18n((int)$bangumi_stats['watched']),
				'value_id' => 'bangumi-stat-watched',
			),
		);
		argon_special_page_surface_header(array(
			'title_id' => 'bangumi-page-title',
			'title' => __('追番', 'argon'),
			'stats' => $bangumi_header_stats,
		));
		?>

		<article class="post card shadow-sm bg-white border-0 argon-special-page-card bangumi-page-surface">
			<?php if (!$bangumi_enabled): ?>
				<div class="bangumi-notice" role="status">
					<i class="fa fa-sliders" aria-hidden="true"></i>
					<div><strong><?php esc_html_e('追番页尚未启用', 'argon'); ?></strong><p><?php esc_html_e('请在 Argon 主题设置的扩展功能中启用追番页并填写 B 站 UID。', 'argon'); ?></p></div>
				</div>
			<?php endif; ?>

			<div class="bangumi-toolbar"<?php echo $bangumi_enabled ? '' : ' aria-hidden="true"'; ?>>
				<div class="bangumi-tabs" role="tablist" aria-label="<?php esc_attr_e('追番状态', 'argon'); ?>">
					<button type="button" class="bangumi-tab is-active" role="tab" aria-selected="true" data-bangumi-status="2"><i class="fa fa-eye" aria-hidden="true"></i><?php esc_html_e('在看', 'argon'); ?></button>
					<button type="button" class="bangumi-tab" role="tab" aria-selected="false" data-bangumi-status="3"><i class="fa fa-check" aria-hidden="true"></i><?php esc_html_e('看过', 'argon'); ?></button>
				</div>
				<div class="bangumi-types" role="group" aria-label="<?php esc_attr_e('追番类型', 'argon'); ?>">
					<button type="button" class="bangumi-type is-active" data-bangumi-type="all"><?php esc_html_e('全部', 'argon'); ?></button>
					<button type="button" class="bangumi-type" data-bangumi-type="1"><?php esc_html_e('番剧', 'argon'); ?></button>
					<button type="button" class="bangumi-type" data-bangumi-type="4"><?php esc_html_e('国创', 'argon'); ?></button>
				</div>
				<label class="bangumi-search">
					<i class="fa fa-search" aria-hidden="true"></i>
					<span class="screen-reader-text"><?php esc_html_e('搜索追番标题', 'argon'); ?></span>
					<input id="bangumi-search-input" type="search" autocomplete="off" placeholder="<?php esc_attr_e('搜索标题', 'argon'); ?>">
				</label>
			</div>

			<div id="bangumi-live-status" class="bangumi-live-status" role="status" aria-live="polite"></div>
			<div id="bangumi-grid" class="bangumi-grid" role="list" aria-live="polite"></div>
			<div id="bangumi-empty" class="bangumi-empty" hidden>
				<i class="fa fa-inbox" aria-hidden="true"></i>
				<strong><?php esc_html_e('这里暂时没有符合条件的条目', 'argon'); ?></strong>
				<span><?php esc_html_e('换个筛选条件，或者稍后再来看看。', 'argon'); ?></span>
			</div>
			<button type="button" id="bangumi-load-more" class="bangumi-load-more"<?php echo $bangumi_enabled ? '' : ' disabled'; ?>><i class="fa fa-refresh" aria-hidden="true"></i><span><?php esc_html_e('加载更多', 'argon'); ?></span></button>
			<div id="bangumi-error" class="bangumi-error" role="alert" hidden></div>
		</article>
		</section>
	<script type="application/json" id="argon-bangumi-config"><?php echo wp_json_encode($bangumi_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
	<script src="<?php echo esc_url($bangumi_script_url); ?>" data-pjax></script>
	<script data-pjax>
		if (window.ArgonBangumi && typeof window.ArgonBangumi.init === 'function') window.ArgonBangumi.init();
	</script>
<?php
argon_special_page_surface_close();
get_footer();
