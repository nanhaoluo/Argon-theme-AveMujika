<?php
	$shuoshuo_preview_classes = 'shuoshuo-preview-container shuoshuo-foldable card bg-white shadow-sm border-0';
	if (get_option('argon_enable_into_article_animation') == 'true'){
		$shuoshuo_preview_classes .= ' post-preview';
	}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class($shuoshuo_preview_classes); ?>>
	<div class="shuoshuo-preview-body">
		<header class="shuoshuo-preview-header">
			<div class="shuoshuo-preview-type">
				<i class="fa fa-quote-left" aria-hidden="true"></i>
			</div>
			<?php if ( get_the_title() != '' ) : ?>
				<a class="shuoshuo-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			<?php endif; ?>
		</header>

		<div class="shuoshuo-content">
			<?php the_content(); ?>
		</div>

		<div class="shuoshuo-preview-footer">
			<div class="shuoshuo-preview-meta">
				<span class="post-meta-detail post-meta-detail-words">
					<i class="fa fa-calendar-o" aria-hidden="true"></i>
					<span class="shuoshuo-date-month"><?php echo get_the_time('n')?></span> <?php _e('月', 'argon');?>
					<span class="shuoshuo-date-date"><?php echo get_the_time('d')?></span> <?php _e('日', 'argon');?>
					<span class="shuoshuo-date-year"><?php echo get_the_time('Y')?></span>
				</span>
				<div class="post-meta-devide">|</div>
				<span class="post-meta-detail post-meta-detail-words">
					<i class="fa fa-clock-o" aria-hidden="true"></i>
					<span class="shuoshuo-date-time"><?php echo get_the_time('G:i')?></span>
				</span>
				<?php if ( is_sticky() ) : ?>
					<div class="post-meta-devide">|</div>
					<span class="post-meta-detail post-meta-detail-words">
						<i class="fa fa-thumb-tack" aria-hidden="true"></i>
						<?php _ex('置顶', 'pinned', 'argon');?>
					</span>
				<?php endif; ?>
				<?php
					$upvote_count = get_shuoshuo_upvotes(get_the_ID());
					$comment_count = get_post(get_the_ID()) -> comment_count;
				?>
				<?php if ( $upvote_count > 0 ) : ?>
					<div class="post-meta-devide">|</div>
					<span class="post-meta-detail post-meta-detail-words">
						<i class="fa fa-thumbs-o-up" aria-hidden="true"></i>
						<?php echo $upvote_count; ?>
					</span>
				<?php endif; ?>
				<?php if ( $comment_count > 0 ) : ?>
					<div class="post-meta-devide">|</div>
					<span class="post-meta-detail post-meta-detail-words">
						<i class="fa fa-comments-o" aria-hidden="true"></i>
						<?php echo $comment_count; ?>
					</span>
				<?php endif; ?>
			</div>
			<a class="shuoshuo-preview-link btn btn-outline-primary btn-icon-only rounded-circle" href="<?php the_permalink(); ?>" aria-label="<?php esc_attr_e('查看说说', 'argon'); ?>">
				<span class="btn-inner--icon"><i class="fa fa-arrow-right"></i></span>
			</a>
		</div>
	</div>
	<?php echo get_article_skeleton('1'); ?>
</article>
