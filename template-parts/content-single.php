<article class="post post-full card bg-white shadow-sm border-0" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php $show_thumbnail_in_banner = get_option('argon_show_thumbnail_in_banner_in_content_page', 'false'); ?>
	<header class="post-header text-center<?php if (argon_has_post_thumbnail() && $show_thumbnail_in_banner != 'true'){echo " post-header-with-thumbnail";}?>">
		<?php
			if (argon_has_post_thumbnail() && $show_thumbnail_in_banner != 'true'){
				$thumbnail_url = argon_get_post_thumbnail();
				echo "<img class='post-thumbnail' src='" . $thumbnail_url . "'></img>";
				echo "<div class='post-header-text-container'>";
			}
			if (argon_has_post_thumbnail() && $show_thumbnail_in_banner == 'true'){
				$thumbnail_url = argon_get_post_thumbnail();
				echo "
				<style>
					body section.banner {
						background-image: url(" . $thumbnail_url . ") !important;
					}
				</style>";
			}
		?>
		<a class="post-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		<div class="post-meta">
			<?php
				$metaList = explode('|', get_option('argon_article_meta', 'time|views|comments|categories'));
				if (is_sticky() && is_home() && ! is_paged()){
					array_unshift($metaList, "sticky");
				}
				if (post_password_required()){
					array_unshift($metaList, "needpassword");
				}
				if (is_meta_simple()){
					array_remove($metaList, "time");
					array_remove($metaList, "edittime");
					array_remove($metaList, "categories");
					array_remove($metaList, "author");
				}
				for ($i = 0; $i < count($metaList); $i++){
					if ($i > 0){
						echo ' <div class="post-meta-devide">|</div> ';
					}
					echo get_article_meta($metaList[$i]);
				}
			?>
			<?php if (!post_password_required() && get_option("argon_show_readingtime") != "false" && is_readingtime_meta_hidden() == False) {
				echo get_article_reading_time_meta(get_the_content());
			} ?>
			<!-- 优雅内置的沉浸式阅读按钮 -->
			<div class="post-meta-devide">|</div>
			<div class="post-meta-detail post-meta-detail-immersive" id="immersive_read_toggle" title="<?php _e('沉浸阅读', 'argon'); ?>" style="cursor: pointer; transition: color 0.3s ease;">
				<i class="fa fa-television immersive-read-icon-enter" aria-hidden="true"></i>
				<span class="immersive-read-label"><?php _e('沉浸', 'argon'); ?></span>
			</div>
		</div>
		<?php
			if (has_post_thumbnail() && $show_thumbnail_in_banner != 'true'){
				echo "</div>";
			}
		?>
	</header>

	<!-- 简约的顶部退出按钮 -->
	<div id="immersive_exit_btn" class="immersive-exit-btn-container">
		<button class="immersive-exit-btn-fast">
			<i class="fa fa-compress" aria-hidden="true"></i> <?php _e('退出沉浸模式', 'argon'); ?>
		</button>
	</div>

	<!-- 沉浸式阅读 JS -->
	<script>
	(function() {
		var toggleBtn = document.getElementById('immersive_read_toggle');
		var exitTopBtn = document.getElementById('immersive_exit_btn');
		if (!toggleBtn) return;

		var isActive = false;

		function enterImmersive() {
			if (isActive) return;
			isActive = true;

			document.documentElement.classList.add('immersive-read');
			toggleBtn.classList.add('active');
			toggleBtn.title = '<?php _e("退出沉浸", "argon"); ?>';

			var icon = toggleBtn.querySelector('i.fa');
			if (icon) {
				icon.classList.remove('fa-television');
				icon.classList.add('fa-compress');
			}
			var label = toggleBtn.querySelector('.immersive-read-label');
			if (label) {
				label.innerText = '<?php _e("退出", "argon"); ?>';
			}

			// 快速滚动到文章头部
			var postContent = document.getElementById('post_content');
			if (postContent) {
				var rect = postContent.getBoundingClientRect();
				var headerOffset = 20;
				var scrollTarget = window.scrollY + rect.top - headerOffset;
				window.scrollTo({ top: scrollTarget, behavior: 'smooth' });
			}

			if (exitTopBtn) {
				exitTopBtn.classList.add('visible');
			}
		}

		function exitImmersive() {
			if (!isActive) return;
			isActive = false;

			document.documentElement.classList.remove('immersive-read');

			toggleBtn.classList.remove('active');
			toggleBtn.title = '<?php _e("沉浸阅读", "argon"); ?>';

			var icon = toggleBtn.querySelector('i.fa');
			if (icon) {
				icon.classList.remove('fa-compress');
				icon.classList.add('fa-television');
			}
			var label = toggleBtn.querySelector('.immersive-read-label');
			if (label) {
				label.innerText = '<?php _e("沉浸", "argon"); ?>';
			}

			if (exitTopBtn) {
				exitTopBtn.classList.remove('visible');
			}
		}

		toggleBtn.addEventListener('click', function(e) {
			e.preventDefault();
			isActive ? exitImmersive() : enterImmersive();
		});

		if (exitTopBtn) {
			exitTopBtn.querySelector('button').addEventListener('click', function(e) {
				e.preventDefault();
				exitImmersive();
			});
		}

		// Esc 快捷键支持
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && isActive) exitImmersive();
		});

		// 滚动检测: 控制顶部退出按钮透明度防遮挡
		var ticking = false;
		window.addEventListener('scroll', function() {
			if (!isActive || !exitTopBtn) return;
			if (!ticking) {
				ticking = true;
				requestAnimationFrame(function() {
					// 当滚动超过一定距离时，使其半透明，鼠标悬浮时恢复
					exitTopBtn.classList.toggle('dimmed', window.scrollY > 200);
					ticking = false;
				});
			}
		}, { passive: true });
	})();
	</script>

	<?php get_template_part('template-parts/ai-summary'); ?>

	<div class="post-content" id="post_content">
		<?php if (post_password_required()){ ?>
			<div class="text-center container">
				<form action="<?php echo $GLOBALS['wp_path']; ?>wp-login.php?action=postpass" class="post-password-form" method="post">
					<div class="post-password-form-text"><?php _e('这是一篇受密码保护的文章，您需要提供访问密码', 'argon');?></div>
					<div class="row">
						<div class="form-group col-lg-6 col-md-8 col-sm-10 col-xs-12 post-password-form-input">
							<div class="input-group input-group-alternative">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fa fa-key"></i></span>
								</div>
								<input name="post_password" class="form-control" placeholder="<?php _e('密码', 'argon');?>" type="password" value="<?php if (current_user_can('level_7')){global $post;if (isset($post -> post_password)){echo esc_attr($post->post_password);}} ?>">
							</div>
							<?php
								$post_password_hint = get_post_meta(get_the_ID(), 'password_hint', true);
								if (!empty($post_password_hint)){
									echo '<div class="post-password-hint">' . $post_password_hint . '</div>';
								}
							?>
						</div>
					</div>
					<input class="btn btn-primary" type="submit" name="Submit" value="<?php _e('确认', 'argon');?>">
				</form>
			</div>
		<?php
			}else{
				echo argon_get_post_outdated_info();

				global $post_references, $post_reference_keys_first_index, $post_reference_contents_first_index;
				$post_references = array();
				$post_reference_keys_first_index = array();
				$post_reference_contents_first_index = array();

				the_content();
			}
		?>
	</div>

	<?php
		$referenceList = get_reference_list();
		if ($referenceList != ""){
			echo $referenceList;
		}
	?>

	<?php if (get_option("argon_donate_qrcode_url") != '') { ?>
		<div class="post-donate">
			<button class="btn donate-btn btn-danger"><?php _e('赞赏', 'argon');?></button>
			<div class="donate-qrcode card shadow-sm bg-white">
				<img src="<?php echo get_option("argon_donate_qrcode_url"); ?>">
			</div>
		</div>
	<?php } ?>

	<?php
		$additionalContentAfterPost = get_additional_content_after_post();
		if ($additionalContentAfterPost != ""){
			echo "<div class='additional-content-after-post'>" . $additionalContentAfterPost . "</div>";
		}
	?>

	<?php if (has_tag()) { ?>
		<div class="post-tags">
			<i class="fa fa-tags" aria-hidden="true"></i>
			<?php
				$tags = get_the_tags();
				foreach ($tags as $tag) {
					echo "<a href='" . get_category_link($tag -> term_id) . "' target='_blank' class='tag badge badge-secondary post-meta-detail-tag'>" . $tag -> name . "</a>";
				}
			?>
		</div>
	<?php } ?>
</article>
