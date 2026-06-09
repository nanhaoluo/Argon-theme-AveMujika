<?php get_header(); ?>

<?php
	global $wp_query;

	$archive_title = get_the_archive_title();
	$archive_description = get_the_archive_description();
	$archive_posts_count = isset($wp_query -> found_posts) ? (int)$wp_query -> found_posts : 0;
	$archive_type_label = __('归档', 'argon');
	$archive_icon = 'archive';

	if (is_category()) {
		$archive_type_label = __('分类归档', 'argon');
		$archive_icon = 'folder-open';
	} elseif (is_tag()) {
		$archive_type_label = __('标签归档', 'argon');
		$archive_icon = 'tags';
	} elseif (is_author()) {
		$archive_type_label = __('作者归档', 'argon');
		$archive_icon = 'user';
	} elseif (is_date()) {
		$archive_type_label = __('时间归档', 'argon');
		$archive_icon = 'calendar-o';
	}
?>

<div class="page-information-card-container archive-information-card-container">
	<div class="page-information-card archive-information-card card bg-white shadow-sm border-0">
		<div class="card-body archive-information-card-body">
			<div class="archive-information-icon" aria-hidden="true">
				<i class="fa fa-<?php echo esc_attr($archive_icon); ?>"></i>
			</div>
			<div class="archive-information-content">
				<div class="archive-information-kicker text-black"><?php echo esc_html($archive_type_label); ?></div>
				<h3 class="archive-information-title text-black"><?php echo wp_kses_post($archive_title); ?></h3>
				<?php if ($archive_description != ''){ ?>
					<div class="archive-information-description text-black">
						<?php echo wp_kses_post($archive_description); ?>
					</div>
				<?php } ?>
				<p class="archive-information-count text-black">
					<i class="fa fa-file-o mr-1"></i>
					<?php echo number_format_i18n($archive_posts_count); ?> <?php _e('篇文章', 'argon');?>
				</p>
			</div>
		</div>
	</div>
</div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main article-list archive-result-list" role="main">
	<?php if ( have_posts() ) : ?>
		<?php
			while ( have_posts() ) :
				the_post();
				if (get_post_type() == 'shuoshuo'){
					get_template_part( 'template-parts/content-shuoshuo-preview' );
				}else{
					get_template_part( 'template-parts/content-preview', get_option('argon_article_list_layout', '1'));
				}
			endwhile;
		?>
		<?php
			echo get_argon_formatted_paginate_links_for_all_platforms();
		?>
		<?php
	else :
		get_template_part( 'template-parts/content', 'none-tag' );
	endif;
	?>

<?php get_footer(); ?>
