<?php 
$is_single_column = get_option('argon_page_layout', 'double') == 'single';
$leftbar_class = 'leftbar widget-area' . ($is_single_column ? ' single-column-sidebar' : '');

// 获取作者信息
$author_name = get_option('argon_sidebar_auther_name');
if (empty($author_name)) {
	$author_name = get_bloginfo('name');
}
$author_image = get_option('argon_sidebar_auther_image');
$author_desc = get_option('argon_sidebar_author_description');
?>

<div id="sidebar_mask"></div>

<aside id="leftbar" class="<?php echo $leftbar_class; ?>" role="complementary">

		<!-- 移动端侧边栏 - 重新设计的布局 -->
		<div id="leftbar_mobile_nav" class="leftbar-mobile-nav">
			<!-- 顶部用户信息区 -->
			<div class="leftbar-mobile-profile">
				<div class="leftbar-mobile-close" id="leftbar_close_btn">
					<i class="fa fa-times"></i>
				</div>
				<?php if (!empty($author_image)) : ?>
				<div class="leftbar-mobile-avatar">
					<img src="<?php echo esc_url($author_image); ?>" alt="avatar">
				</div>
				<?php endif; ?>
				<div class="leftbar-mobile-user-info">
					<div class="leftbar-mobile-user-name">
						<?php echo esc_html($author_name); ?>
						<?php 
						$show_duo = get_option('argon_show_duolingo_streak', 'false');
						$duo_username = get_option('argon_duolingo_username', '');
						if ($show_duo == 'true' && !empty($duo_username)) : 
							$duo_data = argon_get_duolingo_data();
							if ($duo_data !== false) : 
								$is_today_done = isset($duo_data['today']) && $duo_data['today'];
							?>
						<span class="duolingo-streak<?php echo $is_today_done ? '' : ' not-done'; ?>">
							<img src="<?php echo get_template_directory_uri(); ?>/assets/icons/duolingo-streak<?php echo $is_today_done ? '' : '-empty'; ?>.svg" class="duolingo-flame" alt="streak">
							<?php echo $duo_data['streak']; ?>
						</span>
						<?php endif; endif; ?>
					</div>
					<?php if (!empty($author_desc)) : ?>
					<div class="leftbar-mobile-user-desc"><?php echo esc_html($author_desc); ?></div>
					<?php endif; ?>
				</div>
				<!-- 统计数据 -->
				<div class="leftbar-mobile-stats">
					<div class="leftbar-mobile-stat">
						<span class="stat-num"><?php echo wp_count_posts()->publish; ?></span>
						<span class="stat-label"><?php _e('文章', 'argon'); ?></span>
					</div>
					<div class="leftbar-mobile-stat">
						<span class="stat-num"><?php echo wp_count_terms('category'); ?></span>
						<span class="stat-label"><?php _e('分类', 'argon'); ?></span>
					</div>
					<div class="leftbar-mobile-stat">
						<span class="stat-num"><?php echo wp_count_terms('post_tag'); ?></span>
						<span class="stat-label"><?php _e('标签', 'argon'); ?></span>
					</div>
				</div>
			</div>
			
			<!-- 搜索框 -->
			<div class="leftbar-mobile-search">
				<div class="leftbar-mobile-search-inner">
					<i class="fa fa-search"></i>
					<input type="text" id="leftbar_mobile_search_input" placeholder="<?php _e('搜索...', 'argon'); ?>" autocomplete="off">
				</div>
			</div>
			
			<!-- 导航菜单 -->
			<?php if ( has_nav_menu('toolbar_menu') ) : ?>
			<div class="leftbar-mobile-menu-section">
				<div class="leftbar-mobile-section-title"><?php _e('导航', 'argon'); ?></div>
				<ul class="leftbar-mobile-menu">
				<?php
				// 使用简化的 Walker 输出顶栏菜单
				if (!class_exists('mobileToolbarMenuWalker')) {
					class mobileToolbarMenuWalker extends Walker_Nav_Menu {
						public function start_lvl( &$output, $depth = 0, $args = array() ) {
							$output .= '<ul class="leftbar-mobile-submenu">';
						}
						public function end_lvl( &$output, $depth = 0, $args = array() ) {
							$output .= '</ul>';
						}
						public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
							$has_children = $args->walker->has_children;
							$is_current = $object->current;
							$output .= '<li class="leftbar-mobile-menu-item' . ($has_children ? ' has-children' : '') . ($is_current ? ' current' : '') . '">';
							$output .= '<a href="' . esc_url($object->url) . '"' . ($has_children ? ' class="has-submenu"' : '') . '>';
							$output .= '<span class="menu-text">' . esc_html($object->title) . '</span>';
							if ($has_children) {
								$output .= '<i class="fa fa-angle-down submenu-arrow"></i>';
							}
							$output .= '</a>';
						}
						public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
							$output .= '</li>';
						}
					}
				}
				
				wp_nav_menu( array(
					'container'  => '',
					'theme_location'  => 'toolbar_menu',
					'items_wrap'  => '%3$s',
					'depth' => 2,
					'walker' => new mobileToolbarMenuWalker()
				) );
				?>
				</ul>
			</div>
			<?php endif; ?>
			
			<!-- 侧边栏菜单 -->
			<?php if ( has_nav_menu('leftbar_menu') ) : ?>
			<div class="leftbar-mobile-menu-section">
				<div class="leftbar-mobile-section-title"><?php _e('更多', 'argon'); ?></div>
				<ul class="leftbar-mobile-menu">
				<?php
				wp_nav_menu( array(
					'container'  => '',
					'theme_location'  => 'leftbar_menu',
					'items_wrap'  => '%3$s',
					'depth' => 2,
					'walker' => new mobileToolbarMenuWalker()
				) );
				?>
				</ul>
			</div>
			<?php endif; ?>
			
			<!-- 文章目录折叠面板 (仅在文章页显示) -->
			<?php if (have_catalog()) : ?>
			<div class="leftbar-mobile-collapse-section">
				<div class="leftbar-mobile-collapse-header" id="mobile_catalog_toggle">
					<div class="collapse-header-left">
						<i class="fa fa-list-ol collapse-icon"></i>
						<span class="collapse-title"><?php _e('文章目录', 'argon'); ?></span>
					</div>
					<i class="fa fa-chevron-down collapse-arrow"></i>
				</div>
				<div class="leftbar-mobile-collapse-content" id="mobile_catalog_content">
					<div id="leftbar_mobile_catalog" class="mobile-catalog-container"></div>
				</div>
			</div>
			<?php endif; ?>
			
			<!-- TODO折叠面板 -->
			<?php if (get_option('argon_show_todo_list', 'true') == 'true') : ?>
			<?php
				$mobile_todos = argon_get_todo_list();
				$mobile_is_author = current_user_can('publish_posts');
				$mobile_todo_nonce = wp_create_nonce('argon_todo_nonce');
				$mobile_uncompleted_count = count(array_filter($mobile_todos, function($t) { return empty($t['completed']); }));
			?>
			<div class="leftbar-mobile-collapse-section">
				<div class="leftbar-mobile-collapse-header" id="mobile_todo_toggle">
					<div class="collapse-header-left">
						<i class="fa fa-check-square-o collapse-icon"></i>
						<span class="collapse-title"><?php _e('TODO', 'argon'); ?></span>
						<span class="collapse-badge" id="mobile_todo_count"><?php echo $mobile_uncompleted_count; ?></span>
					</div>
					<i class="fa fa-chevron-down collapse-arrow"></i>
				</div>
				<div class="leftbar-mobile-collapse-content" id="mobile_todo_content">
					<div class="mobile-todo-container">
						<?php if ($mobile_is_author) : ?>
						<div class="mobile-todo-add-form">
							<input type="text" id="mobile-todo-input" placeholder="<?php _e('添加新的待办事项...', 'argon'); ?>" maxlength="100">
							<button type="button" id="mobile-todo-add-btn">
								<i class="fa fa-plus"></i>
							</button>
						</div>
						<?php endif; ?>
						<ul class="mobile-todo-list" id="mobile-todo-list">
							<?php if (empty($mobile_todos)) : ?>
								<li class="mobile-todo-empty"><?php _e('暂无待办事项', 'argon'); ?></li>
							<?php else : ?>
								<?php foreach ($mobile_todos as $todo) : 
									$is_completed = isset($todo['completed']) && $todo['completed'];
									$is_urged = !$mobile_is_author && !$is_completed && argon_check_todo_urged($todo['id']);
								?>
								<li class="mobile-todo-item <?php echo $is_completed ? 'todo-completed' : ''; ?>" data-id="<?php echo esc_attr($todo['id']); ?>" data-urged="<?php echo $is_urged ? '1' : '0'; ?>">
									<span class="mobile-todo-content"><?php echo esc_html($todo['content']); ?></span>
									<?php if ($mobile_is_author) : ?>
										<?php if ($is_completed) : ?>
										<button class="mobile-todo-delete-btn" title="<?php _e('删除', 'argon'); ?>">
											<i class="fa fa-trash"></i>
										</button>
										<?php else : ?>
										<button class="mobile-todo-complete-btn" title="<?php _e('完成', 'argon'); ?>">
											<i class="fa fa-check"></i>
										</button>
										<?php endif; ?>
									<?php else : ?>
										<?php if ($is_completed) : ?>
										<span class="mobile-todo-done-mark">
											<i class="fa fa-check"></i>
										</span>
										<?php elseif ($is_urged) : ?>
										<button class="mobile-todo-urge-btn urged" disabled>
											<i class="fa fa-check"></i>
										</button>
										<?php else : ?>
										<button class="mobile-todo-urge-btn" title="<?php _e('催一下', 'argon'); ?>">
											<i class="fa fa-bell"></i>
										</button>
										<?php endif; ?>
									<?php endif; ?>
								</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
						<?php 
						// 移动端验证码容器
						if (!$mobile_is_author && argon_is_todo_captcha_enabled()) :
							$mobile_captcha_type = get_option('argon_captcha_type', 'math');
						?>
						<div class="mobile-todo-captcha-container" style="display:none; margin-top: 10px; padding: 10px; background: var(--color-foreground); border-radius: 10px;">
							<?php if ($mobile_captcha_type == 'geetest') : ?>
							<p style="font-size: 12px; color: #666; margin: 0 0 8px;"><?php _e('请完成验证', 'argon'); ?></p>
							<?php else : ?>
							<div style="display: flex; align-items: center; gap: 8px;">
								<span class="mobile-todo-captcha-text" style="font-family: monospace; font-size: 14px; background: #f0f0f0; padding: 6px 10px; border-radius: 6px; letter-spacing: 2px;"><?php echo argon_generate_captcha(); ?></span>
								<input type="text" id="mobile-todo-captcha-input" placeholder="<?php _e('验证码', 'argon'); ?>" maxlength="10" style="flex: 1; padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 13px; background: var(--color-background);">
								<button type="button" id="mobile-todo-captcha-submit" class="btn btn-sm btn-primary" style="padding: 8px 12px; border-radius: 8px; font-size: 12px;">
									<?php _e('确认', 'argon'); ?>
								</button>
							</div>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<script>
			(function() {
				var mobileNonce = '<?php echo $mobile_todo_nonce; ?>';
				var mobileAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
				var mobileIsAuthor = <?php echo $mobile_is_author ? 'true' : 'false'; ?>;
				var mobileNeedCaptcha = <?php echo (!$mobile_is_author && argon_is_todo_captcha_enabled()) ? 'true' : 'false'; ?>;
				var mobileCaptchaType = '<?php echo get_option('argon_captcha_type', 'math'); ?>';
				var mobileGeetestId = '<?php echo get_option('argon_geetest_captcha_id', ''); ?>';
				window.mobileTodoConfig = {
					nonce: mobileNonce,
					ajaxUrl: mobileAjaxUrl,
					isAuthor: mobileIsAuthor,
					needCaptcha: mobileNeedCaptcha,
					captchaType: mobileCaptchaType,
					geetestId: mobileGeetestId
				};
			})();
			</script>
			<?php endif; ?>
			
			<!-- 顶栏自定义链接 -->
			<?php 
			$toolbar_links_left = get_option('argon_toolbar_links_left', '');
			$toolbar_links_right = get_option('argon_toolbar_links_right', '');
			$has_custom_links = !empty(trim($toolbar_links_left)) || !empty(trim($toolbar_links_right));
			if ($has_custom_links) : 
			?>
			<div class="leftbar-mobile-menu-section">
				<div class="leftbar-mobile-section-title"><?php _e('链接', 'argon'); ?></div>
				<ul class="leftbar-mobile-menu">
				<?php
				// 输出左侧自定义链接
				if (!empty(trim($toolbar_links_left))) {
					foreach (explode("\n", $toolbar_links_left) as $line) {
						$line = trim($line);
						if (empty($line)) continue;
						$parts = array_map('trim', explode('|', $line));
						if (!empty($parts[0])) {
							$url = isset($parts[1]) ? $parts[1] : '#';
							$icon = isset($parts[2]) ? '<i class="' . esc_attr($parts[2]) . '"></i> ' : '';
							echo '<li class="leftbar-mobile-menu-item">';
							echo '<a href="' . esc_url($url) . '">';
							echo $icon . '<span class="menu-text">' . esc_html($parts[0]) . '</span>';
							echo '</a></li>';
						}
					}
				}
				// 输出右侧自定义链接
				if (!empty(trim($toolbar_links_right))) {
					foreach (explode("\n", $toolbar_links_right) as $line) {
						$line = trim($line);
						if (empty($line)) continue;
						$parts = array_map('trim', explode('|', $line));
						if (!empty($parts[0])) {
							$url = isset($parts[1]) ? $parts[1] : '#';
							$icon = isset($parts[2]) ? '<i class="' . esc_attr($parts[2]) . '"></i> ' : '';
							echo '<li class="leftbar-mobile-menu-item">';
							echo '<a href="' . esc_url($url) . '">';
							echo $icon . '<span class="menu-text">' . esc_html($parts[0]) . '</span>';
							echo '</a></li>';
						}
					}
				}
				?>
				</ul>
			</div>
			<?php endif; ?>
			
			<!-- 底部操作区 -->
			<div class="leftbar-mobile-footer">
				<div class="leftbar-mobile-actions">
					<button class="leftbar-mobile-action" onclick="toggleDarkmode()">
						<i class="fa fa-moon-o"></i>
						<span><?php _e('夜间模式', 'argon'); ?></span>
					</button>
					<button class="leftbar-mobile-action" data-toggle="modal" data-target="#blog_categories">
						<i class="fa fa-folder-o"></i>
						<span><?php _e('分类', 'argon'); ?></span>
					</button>
					<button class="leftbar-mobile-action" data-toggle="modal" data-target="#blog_tags">
						<i class="fa fa-tags"></i>
						<span><?php _e('标签', 'argon'); ?></span>
					</button>
				</div>
			</div>
		</div>

		<!-- 桌面端原有侧边栏内容 -->
		<div class="leftbar-desktop-content">
		<?php if (get_option('argon_sidebar_announcement') != '') { ?>

			<div id="leftbar_announcement" class="card bg-white shadow-sm border-0">

				<div class="leftbar-announcement-body">

					<div class="leftbar-announcement-title text-white"><?php _e('公告', 'argon');?></div>

					<div class="leftbar-announcement-content text-white"><?php echo get_option('argon_sidebar_announcement'); ?></div>

				</div>

			</div>

		<?php } ?>

		<div id="leftbar_part1" class="widget widget_search card bg-white shadow-sm border-0">

			<div class="leftbar-banner card-body">

				<span class="leftbar-banner-title text-white"><?php echo get_option('argon_sidebar_banner_title') == '' ? bloginfo('name') : get_option('argon_sidebar_banner_title'); ?></span>



				<?php 

					$sidebar_subtitle = get_option('argon_sidebar_banner_subtitle'); 

					if ($sidebar_subtitle == "--hitokoto--"){

						$sidebar_subtitle = "<span class='hitokoto'></span>";

					}

				?>

				<?php if ($sidebar_subtitle != '') { /*左侧栏子标题/格言(如果选项中开启)*/?>

					<span class="leftbar-banner-subtitle text-white"><?php echo $sidebar_subtitle; ?></span>

				<?php } /*顶栏标题*/?>



			</div>



			<?php

				/*侧栏上部菜单*/

				class leftbarMenuWalker extends Walker_Nav_Menu{

					public function start_lvl( &$output, $depth = 0, $args = array() ) {

						$indent = str_repeat("\t", $depth);

						$output .= "\n$indent<ul class=\"leftbar-menu-item leftbar-menu-subitem shadow-sm\">\n";

					}

					public function end_lvl( &$output, $depth = 0, $args = array() ) {

						$indent = str_repeat("\t", $depth);

						$output .= "\n$indent</ul>\n";

					}

					public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

						$output .= "\n

						<li class='leftbar-menu-item" . ( $args -> walker -> has_children == 1 ? " leftbar-menu-item-haschildren" : "" ) . ( $object -> current == 1 ? " current" : "" ) . "'>

							<a href='" . $object -> url . "'" . ( $args -> walker -> has_children == 1 ? " no-pjax onclick='return false;'" : "" ) . " target='" . $object -> target . "'>". $object -> title . "</a>";

					}

					public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

						//if ($depth == 0){

							$output .= "</li>";

						//}

					}

				}

				echo "<ul id='leftbar_part1_menu' class='leftbar-menu'>";

				if ( has_nav_menu('leftbar_menu') ){

					wp_nav_menu( array(

						'container'  => '',

						'theme_location'  => 'leftbar_menu',

						'items_wrap'  => '%3$s',

						'depth' => 0,

						'walker' => new leftbarMenuWalker()

					) );

				}

				echo "</ul>";

			?>

			<div class="card-body text-center leftbar-search-button">

				<button id="leftbar_search_container" class="btn btn-secondary btn-lg active btn-sm btn-block border-0" role="button">

					<i class="menu-item-icon fa fa-search mr-0"></i> <?php _e('搜索', 'argon');?>

					<input id="leftbar_search_input" type="text" placeholder="<?php _e('搜索什么...', 'argon');?>" class="form-control form-control-alternative" autocomplete="off">

				</button>

			</div>

		</div>

		<div id="leftbar_part2" class="widget widget_search card bg-white shadow-sm border-0">

			<div id="leftbar_part2_inner" class="card-body">

				<?php

					$nowActiveTab = 1;/*默认激活的标签*/

					if (have_catalog()){

						$nowActiveTab = 0;

					}

				?>

				<div class="nav-wrapper" style="padding-top: 5px;<?php if (!have_catalog() && !is_active_sidebar('leftbar-tools')) { echo ' display:none;'; }?>">

	                <ul class="nav nav-pills nav-fill" role="tablist">

						<?php if (have_catalog()) { ?>

							<li class="nav-item sidebar-tab-switcher">

								<a class="<?php if ($nowActiveTab == 0) { echo 'active show'; }?>" id="leftbar_tab_catalog_btn" data-toggle="tab" href="#leftbar_tab_catalog" role="tab" aria-controls="leftbar_tab_catalog" no-pjax><?php _e('文章目录', 'argon');?></a>

							</li>

						<?php } ?>

						<li class="nav-item sidebar-tab-switcher">

							<a class="<?php if ($nowActiveTab == 1) { echo 'active show'; }?>" id="leftbar_tab_overview_btn" data-toggle="tab" href="#leftbar_tab_overview" role="tab" aria-controls="leftbar_tab_overview" no-pjax><?php _e('站点概览', 'argon');?></a>

						</li>

						<?php if ( is_active_sidebar( 'leftbar-tools' ) ){?>

							<li class="nav-item sidebar-tab-switcher">

								<a class="<?php if ($nowActiveTab == 2) { echo 'active show'; }?>" id="leftbar_tab_tools_btn" data-toggle="tab" href="#leftbar_tab_tools" role="tab" aria-controls="leftbar_tab_tools" no-pjax><?php _e('功能', 'argon');?></a>

							</li>

						<?php }?>

	                </ul>

				</div>

				<div>

					<div class="tab-content" style="padding: 10px 10px 0 10px;">

						<?php if (have_catalog()) { ?>

							<div class="tab-pane fade<?php if ($nowActiveTab == 0) { echo ' active show'; }?>" id="leftbar_tab_catalog" role="tabpanel" aria-labelledby="leftbar_tab_catalog_btn">

								<div id="leftbar_catalog"></div>

								<script type="text/javascript">
									// 桌面端目录初始化 - 等待 jQuery 和 headIndex 插件加载完成
									(function initDesktopCatalog() {
										// 检查 jQuery 和 headIndex 是否都已加载
										if (typeof jQuery !== 'undefined' && typeof jQuery.fn.headIndex === 'function') {
											jQuery(document).ready(function($) {
												$(document).headIndex({
													articleWrapSelector: '#post_content',
													indexBoxSelector: '#leftbar_catalog',
													subItemBoxClass: "index-subItem-box",
													itemClass: "index-item",
													linkClass: "index-link",
													offset: 80,
												});
											});
										} else {
											// 如果还没加载完成，等待后重试
											setTimeout(initDesktopCatalog, 50);
										}
									})();
								</script>

								<?php if (get_option('argon_show_headindex_number') == 'true') {?>

									<style>

										#leftbar_catalog ul {

											counter-reset: blog_catalog_number;

										}

										#leftbar_catalog li.index-item > a:before {

											content: counters(blog_catalog_number, '.') " ";

											counter-increment: blog_catalog_number;

										}

									</style>

								<?php }?>

							</div>

						<?php } ?>

						<div class="tab-pane fade text-center<?php if ($nowActiveTab == 1) { echo ' active show'; }?>" id="leftbar_tab_overview" role="tabpanel" aria-labelledby="leftbar_tab_overview_btn">

							<div id="leftbar_overview_author_image" style="background-image: url(<?php echo get_option('argon_sidebar_auther_image') == '' ? 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCAxMDAgMTAwIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cmVjdCBmaWxsPSIjNUU3MkU0MjIiIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIi8+PGc+PGcgb3BhY2l0eT0iMC4zIj48cGF0aCBmaWxsPSIjNUU3MkU0IiBkPSJNNzQuMzksMzIuODZjLTAuOTgtMS43LTMuMzktMy4wOS01LjM1LTMuMDlINDUuNjJjLTEuOTYsMC00LjM3LDEuMzktNS4zNSwzLjA5TDI4LjU3LDUzLjE1Yy0wLjk4LDEuNy0wLjk4LDQuNDgsMCw2LjE3bDExLjcxLDIwLjI5YzAuOTgsMS43LDMuMzksMy4wOSw1LjM1LDMuMDloMjMuNDNjMS45NiwwLDQuMzctMS4zOSw1LjM1LTMuMDlMODYuMSw1OS4zMmMwLjk4LTEuNywwLjk4LTQuNDgsMC02LjE3TDc0LjM5LDMyLjg2eiIvPjwvZz48ZyBvcGFjaXR5PSIwLjgiPjxwYXRoIGZpbGw9IiM1RTcyRTQiIGQ9Ik02Mi4wNCwyMC4zOWMtMC45OC0xLjctMy4zOS0zLjA5LTUuMzUtMy4wOUgzMS43M2MtMS45NiwwLTQuMzcsMS4zOS01LjM1LDMuMDlMMTMuOSw0Mi4wMWMtMC45OCwxLjctMC45OCw0LjQ4LDAsNi4xN2wxMi40OSwyMS42MmMwLjk4LDEuNywzLjM5LDMuMDksNS4zNSwzLjA5aDI0Ljk3YzEuOTYsMCw0LjM3LTEuMzksNS4zNS0zLjA5bDEyLjQ5LTIxLjYyYzAuOTgtMS43LDAuOTgtNC40OCwwLTYuMTdMNjIuMDQsMjAuMzl6Ii8+PC9nPjwvZz48L3N2Zz4=' : get_option('argon_sidebar_auther_image'); ?>)" class="rounded-circle shadow-sm" alt="avatar"></div>

							<h6 id="leftbar_overview_author_name">
								<?php echo get_option('argon_sidebar_auther_name') == '' ? bloginfo('name') : get_option('argon_sidebar_auther_name'); ?>
								<?php 
								$show_duo = get_option('argon_show_duolingo_streak', 'false');
								$duo_username = get_option('argon_duolingo_username', '');
								if ($show_duo == 'true' && !empty($duo_username)) : 
									$duo_data = argon_get_duolingo_data();
									if ($duo_data !== false) : 
										$is_today_done = isset($duo_data['today']) && $duo_data['today'];
									?>
								<span class="duolingo-streak<?php echo $is_today_done ? '' : ' not-done'; ?>">
									<img src="<?php echo get_template_directory_uri(); ?>/assets/icons/duolingo-streak<?php echo $is_today_done ? '' : '-empty'; ?>.svg" class="duolingo-flame" alt="streak">
									<?php echo $duo_data['streak']; ?>
								</span>
								<?php endif; endif; ?>
							</h6>

							<?php $author_desctiption = get_option('argon_sidebar_author_description'); if (!empty($author_desctiption)) {echo '<h6 id="leftbar_overview_author_description">'. $author_desctiption .'</h6>';}?>

							<nav class="site-state">

								<div class="site-state-item site-state-posts">

									<a <?php $archives_page_url = get_option('argon_archives_timeline_url'); echo (empty($archives_page_url) ? ' style="cursor: default;"' : 'href="' . $archives_page_url . '"');?>>

										<span class="site-state-item-count"><?php echo wp_count_posts() -> publish; ?></span>

										<span class="site-state-item-name"><?php _e('文章', 'argon');?></span>

									</a>

								</div>

								<div class="site-state-item site-state-categories">

									<a data-toggle="modal" data-target="#blog_categories">

										<span class="site-state-item-count"><?php echo wp_count_terms('category'); ?></span>

										<span class="site-state-item-name"><?php _e('分类', 'argon');?></span>

									</a>

								</div>      

								<div class="site-state-item site-state-tags">

									<a data-toggle="modal" data-target="#blog_tags">

										<span class="site-state-item-count"><?php echo wp_count_terms('post_tag'); ?></span>

										<span class="site-state-item-name"><?php _e('标签', 'argon');?></span>

									</a>

								</div>

							</nav>

							<?php

								/*侧栏作者链接*/

								class leftbarAuthorLinksWalker extends Walker_Nav_Menu{

									public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

										if ($depth == 0){

											$output .= "\n

											<div class='site-author-links-item'>

												<a href='" . $object -> url . "' rel='noopener' target='_blank'>". $object -> title . "</a>";

										}

									}

									public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

										if ($depth == 0){

											$output .= "</div>";

										}

									}

								}



								if ( has_nav_menu('leftbar_author_links') ){

									echo "<div class='site-author-links'>";

									wp_nav_menu( array(

										'container'  => '',

										'theme_location'  => 'leftbar_author_links',

										'items_wrap'  => '%3$s',

										'depth' => 0,

										'walker' => new leftbarAuthorLinksWalker()

									) );

									echo "</div>";

								}

							?>

							<?php

								/*侧栏友情链接*/

								class leftbarFriendLinksWalker extends Walker_Nav_Menu{

									public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

										if ($depth == 0){

											$output .= "\n

											<li class='site-friend-links-item'>

												<a href='" . $object -> url . "' rel='noopener' target='_blank'>". $object -> title . "</a>";

										}

									}

									public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

										if ($depth == 0){

											$output .= "</li>";

										}

									}

								}



								if ( has_nav_menu('leftbar_friend_links') ){

									echo "<div class='site-friend-links'>

											<div class='site-friend-links-title'><i class='fa fa-fw fa-link'></i> Links</div>

											<ul class='site-friend-links-ul'>";

									wp_nav_menu( array(

										'container'  => '',

									    'theme_location'  => 'leftbar_friend_links',

										'items_wrap'  => '%3$s',

									    'depth' => 0,

										'walker' => new leftbarFriendLinksWalker()

									) );

									echo "</ul></div>";

								}else{

									echo "<div style='height: 20px;'></div>";

								}

							?>

							<?php if ( is_active_sidebar( 'leftbar-siteinfo-extra-tools' ) ){?>

								<div id="leftbar_siteinfo_extra_tools">

									<?php dynamic_sidebar( 'leftbar-siteinfo-extra-tools' ); ?>

								</div>

							<?php }?>

						</div>

						<?php if ( is_active_sidebar( 'leftbar-tools' ) ){?>

							<div class="tab-pane fade<?php if ($nowActiveTab == 2) { echo ' active show'; }?>" id="leftbar_tab_tools" role="tabpanel" aria-labelledby="leftbar_tab_tools_btn">

								<?php dynamic_sidebar( 'leftbar-tools' ); ?>

							</div>

						<?php }?>

					</div>

				</div>

			</div>

		</div>

		<?php if (get_option('argon_show_todo_list', 'true') == 'true') : ?>
		<?php
			$todos = argon_get_todo_list();
			$is_author = current_user_can('publish_posts');
			$todo_nonce = wp_create_nonce('argon_todo_nonce');
		?>
		<div id="leftbar_part3" class="widget card bg-white shadow-sm border-0">
			<div class="card-body">
				<h6 class="font-weight-bold text-black todo-title">
					<i class="fa fa-list-ul"></i> <?php _e('TODO', 'argon'); ?>
					<?php 
					$uncompleted_count = count(array_filter($todos, function($t) { return empty($t['completed']); }));
					?>
					<span class="todo-count" title="<?php echo $is_author ? __('未完成', 'argon') : __('待办事项', 'argon'); ?>"><?php echo $uncompleted_count; ?></span>
				</h6>
				
				<?php if ($is_author) : ?>
				<div class="todo-add-form">
					<input type="text" id="todo-input" class="form-control form-control-sm" placeholder="<?php _e('添加新的待办事项...', 'argon'); ?>" maxlength="100">
					<button type="button" id="todo-add-btn" class="btn btn-sm btn-primary" title="<?php _e('添加', 'argon'); ?>">
						<i class="fa fa-plus"></i>
					</button>
				</div>
				<?php endif; ?>
				
				<ul class="todo-list <?php echo count($todos) > 4 ? 'todo-list-scroll' : ''; ?>" id="todo-list">
					<?php if (empty($todos)) : ?>
						<li class="todo-empty"><?php _e('暂无待办事项', 'argon'); ?></li>
					<?php else : ?>
						<?php foreach ($todos as $todo) : 
							$is_completed = isset($todo['completed']) && $todo['completed'];
							$is_urged = !$is_author && !$is_completed && argon_check_todo_urged($todo['id']);
						?>
						<li class="todo-item <?php echo $is_completed ? 'todo-completed' : ''; ?>" data-id="<?php echo esc_attr($todo['id']); ?>" data-urged="<?php echo $is_urged ? '1' : '0'; ?>">
							<span class="todo-content"><?php echo esc_html($todo['content']); ?></span>
							<?php if ($is_author) : ?>
								<?php if ($is_completed) : ?>
								<button class="todo-delete-btn" title="<?php _e('删除', 'argon'); ?>">
									<i class="fa fa-trash"></i>
								</button>
								<?php else : ?>
								<button class="todo-complete-btn" title="<?php _e('完成', 'argon'); ?>">
									<i class="fa fa-check"></i>
								</button>
								<?php endif; ?>
							<?php else : ?>
								<?php if ($is_completed) : ?>
								<span class="todo-done-mark" title="<?php _e('已完成', 'argon'); ?>">
									<i class="fa fa-check"></i>
								</span>
								<?php elseif ($is_urged) : ?>
								<button class="todo-urge-btn urged" disabled title="<?php _e('已提醒', 'argon'); ?>">
									<i class="fa fa-check"></i>
								</button>
								<?php else : ?>
								<button class="todo-urge-btn" title="<?php _e('催一下', 'argon'); ?>">
									<i class="fa fa-bell"></i>
								</button>
								<?php endif; ?>
							<?php endif; ?>
						</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
				<?php if (!$is_author && argon_is_todo_captcha_enabled()) : 
					$captcha_type = get_option('argon_captcha_type', 'math');
				?>
				<div class="todo-captcha-container" style="display:none; margin-top: 10px;">
					<?php if ($captcha_type == 'geetest') : ?>
					<script>
						// TODO Geetest 备用加载
						function loadTodoGeetest() {
							if (typeof ArgonResourceLoader !== 'undefined') {
								ArgonResourceLoader.smartLoad('https://static.geetest.com/v4/gt4.js', 'js');
							} else {
								var script = document.createElement('script');
								script.src = '<?php echo get_template_directory_uri(); ?>/assets/vendor/external/geetest/gt4.js';
								document.head.appendChild(script);
							}
						}
						loadTodoGeetest();
					</script>
					<div id="todo-geetest-captcha"></div>
					<?php else : ?>
					<div class="input-group input-group-sm">
						<input type="text" id="todo-captcha-input" class="form-control" placeholder="<?php _e('验证码', 'argon'); ?>">
						<div class="input-group-append">
							<span class="input-group-text todo-captcha-text"><?php echo get_comment_captcha(); ?></span>
							<button type="button" id="todo-captcha-submit" class="btn btn-primary btn-sm"><?php _e('提交', 'argon'); ?></button>
						</div>
					</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<script>
		(function() {
			var nonce = '<?php echo $todo_nonce; ?>';
			var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
			var isAuthor = <?php echo $is_author ? 'true' : 'false'; ?>;
			var needCaptcha = <?php echo (!$is_author && argon_is_todo_captcha_enabled()) ? 'true' : 'false'; ?>;
			var captchaType = '<?php echo get_option('argon_captcha_type', 'math'); ?>';
			var pendingUrgeBtn = null;
			var geetestCaptchaObj = null;
			
			function updateCount() {
				var count = document.querySelectorAll('.todo-item:not(.todo-completed)').length;
				var countEl = document.querySelector('.todo-count');
				if (countEl) countEl.textContent = count;
				
				var totalCount = document.querySelectorAll('.todo-item').length;
				var list = document.getElementById('todo-list');
				if (totalCount > 4) {
					list.classList.add('todo-list-scroll');
				} else {
					list.classList.remove('todo-list-scroll');
				}
				
				// 显示/隐藏空状态
				var emptyEl = list.querySelector('.todo-empty');
				if (totalCount === 0 && !emptyEl) {
					list.innerHTML = '<li class="todo-empty"><?php _e('暂无待办事项', 'argon'); ?></li>';
				} else if (totalCount > 0 && emptyEl) {
					emptyEl.remove();
				}
			}
			
			// 添加 TODO
			if (isAuthor) {
				var input = document.getElementById('todo-input');
				var addBtn = document.getElementById('todo-add-btn');
				
				function addTodo() {
					var content = input.value.trim();
					if (!content) return;
					
					addBtn.disabled = true;
					
					var formData = new FormData();
					formData.append('action', 'argon_add_todo');
					formData.append('nonce', nonce);
					formData.append('content', content);
					
					fetch(ajaxUrl, { method: 'POST', body: formData })
						.then(function(r) { return r.json(); })
						.then(function(res) {
							if (res.success) {
								var list = document.getElementById('todo-list');
								var emptyEl = list.querySelector('.todo-empty');
								if (emptyEl) emptyEl.remove();
								
								var li = document.createElement('li');
								li.className = 'todo-item';
								li.dataset.id = res.data.id;
								li.innerHTML = '<span class="todo-content">' + content + '</span>' +
									'<button class="todo-complete-btn" title="<?php _e('完成', 'argon'); ?>"><i class="fa fa-check"></i></button>';
								list.insertBefore(li, list.firstChild);
								
								input.value = '';
								updateCount();
							}
							addBtn.disabled = false;
						});
				}
				
				addBtn.addEventListener('click', addTodo);
				input.addEventListener('keypress', function(e) {
					if (e.key === 'Enter') addTodo();
				});
			}
			
			// 完成/催促 TODO
			document.getElementById('todo-list').addEventListener('click', function(e) {
				var btn = e.target.closest('.todo-complete-btn, .todo-urge-btn, .todo-delete-btn');
				if (!btn) return;
				
				var item = btn.closest('.todo-item');
				var id = item.dataset.id;
				
				if (btn.classList.contains('todo-complete-btn')) {
					btn.disabled = true;
					var formData = new FormData();
					formData.append('action', 'argon_complete_todo');
					formData.append('nonce', nonce);
					formData.append('id', id);
					
					fetch(ajaxUrl, { method: 'POST', body: formData })
						.then(function(r) { return r.json(); })
						.then(function(res) {
							if (res.success) {
								item.classList.add('todo-completed');
								var newBtn = document.createElement('button');
								newBtn.className = 'todo-delete-btn';
								newBtn.title = '<?php _e('删除', 'argon'); ?>';
								newBtn.innerHTML = '<i class="fa fa-trash"></i>';
								btn.parentNode.replaceChild(newBtn, btn);
								updateCount();
							} else {
								btn.disabled = false;
							}
						});
				} else if (btn.classList.contains('todo-delete-btn')) {
					btn.disabled = true;
					var formData = new FormData();
					formData.append('action', 'argon_delete_todo');
					formData.append('nonce', nonce);
					formData.append('id', id);
					
					fetch(ajaxUrl, { method: 'POST', body: formData })
						.then(function(r) { return r.json(); })
						.then(function(res) {
							if (res.success) {
								item.classList.add('todo-completing');
								setTimeout(function() {
									item.remove();
									updateCount();
								}, 300);
							}
						});
				} else if (btn.classList.contains('todo-urge-btn')) {
					if (btn.classList.contains('urged')) return;
					
					if (needCaptcha) {
						var captchaContainer = document.querySelector('.todo-captcha-container');
						if (captchaContainer) {
							captchaContainer.style.display = 'block';
							pendingUrgeBtn = btn;
							
							if (captchaType === 'geetest') {
								if (!geetestCaptchaObj && typeof initGeetest4 === 'function') {
									initGeetest4({
										captchaId: '<?php echo get_option('argon_geetest_captcha_id', ''); ?>',
										product: 'bind'
									}, function(captcha) {
										geetestCaptchaObj = captcha;
										captcha.onReady(function() {
											captcha.showCaptcha();
										}).onSuccess(function() {
											var result = captcha.getValidate();
											if (result) {
												var id = pendingUrgeBtn.closest('.todo-item').dataset.id;
												doUrgeGeetest(pendingUrgeBtn, id, result);
											}
										}).onError(function(err) {
											captchaContainer.style.display = 'none';
											pendingUrgeBtn = null;
										});
									});
								} else if (geetestCaptchaObj) {
									geetestCaptchaObj.showCaptcha();
								} else {
									if (typeof iziToast !== 'undefined') {
										iziToast.show({
											title: '<?php _e('加载失败', 'argon'); ?>',
											message: '<?php _e('验证码加载失败，请刷新页面', 'argon'); ?>',
											class: 'shadow-sm',
											position: 'topRight',
											backgroundColor: '#f5365c',
											titleColor: '#ffffff',
											messageColor: '#ffffff',
											iconColor: '#ffffff',
											progressBarColor: '#ffffff',
											icon: 'fa fa-close',
											timeout: 5000
										});
									}
									captchaContainer.style.display = 'none';
									pendingUrgeBtn = null;
								}
							} else {
								var captchaInput = document.getElementById('todo-captcha-input');
								if (captchaInput) captchaInput.focus();
							}
							return;
						}
					}
					
					doUrge(btn, id, '');
				}
			});
			
			// 执行催促
			function doUrge(btn, id, captcha) {
				btn.disabled = true;
				btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
				var formData = new FormData();
				formData.append('action', 'argon_urge_todo');
				formData.append('nonce', nonce);
				formData.append('id', id);
				formData.append('comment_captcha', captcha);
				
				fetch(ajaxUrl, { method: 'POST', body: formData })
					.then(function(r) { return r.json(); })
					.then(function(res) { handleUrgeResponse(btn, res); })
					.catch(function(err) {
						btn.innerHTML = '<i class="fa fa-bell"></i>';
						btn.disabled = false;
					});
			}
			
			// 极验验证码催促
			function doUrgeGeetest(btn, id, geetestResult) {
				btn.disabled = true;
				btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
				var formData = new FormData();
				formData.append('action', 'argon_urge_todo');
				formData.append('nonce', nonce);
				formData.append('id', id);
				formData.append('lot_number', geetestResult.lot_number);
				formData.append('captcha_output', geetestResult.captcha_output);
				formData.append('pass_token', geetestResult.pass_token);
				formData.append('gen_time', geetestResult.gen_time);
				
				fetch(ajaxUrl, { method: 'POST', body: formData })
					.then(function(r) { return r.json(); })
					.then(function(res) {
						handleUrgeResponse(btn, res);
						if (geetestCaptchaObj) geetestCaptchaObj.reset();
					})
					.catch(function(err) {
						btn.innerHTML = '<i class="fa fa-bell"></i>';
						btn.disabled = false;
					});
			}
			
			// 处理催促响应
			function handleUrgeResponse(btn, res) {
				var captchaContainer = document.querySelector('.todo-captcha-container');
				if (res.success) {
					btn.innerHTML = '<i class="fa fa-check"></i>';
					btn.classList.add('urged');
					btn.title = '<?php _e('已提醒', 'argon'); ?>';
					if (res.data && res.data.captcha) {
						var captchaText = document.querySelector('.todo-captcha-text');
						if (captchaText) captchaText.textContent = res.data.captcha;
					}
					if (captchaContainer) {
						captchaContainer.style.display = 'none';
						var captchaInput = document.getElementById('todo-captcha-input');
						if (captchaInput) captchaInput.value = '';
					}
					pendingUrgeBtn = null;
				} else {
					btn.innerHTML = '<i class="fa fa-bell"></i>';
					btn.disabled = false;
					var captchaInput = document.getElementById('todo-captcha-input');
					if (captchaInput) {
						captchaInput.value = '';
						captchaInput.focus();
					}
					if (typeof iziToast !== 'undefined') {
						iziToast.show({
							title: '<?php _e('提醒失败', 'argon'); ?>',
							message: res.data || '<?php _e('稍后再试', 'argon'); ?>',
							class: 'shadow-sm',
							position: 'topRight',
							backgroundColor: '#f5365c',
							titleColor: '#ffffff',
							messageColor: '#ffffff',
							iconColor: '#ffffff',
							progressBarColor: '#ffffff',
							icon: 'fa fa-close',
							timeout: 5000
						});
					}
				}
			}
			
			// 验证码输入处理
			if (needCaptcha && captchaType !== 'geetest') {
				var captchaInput = document.getElementById('todo-captcha-input');
				var captchaSubmit = document.getElementById('todo-captcha-submit');
				
				function submitCaptcha() {
					if (pendingUrgeBtn && captchaInput) {
						var id = pendingUrgeBtn.closest('.todo-item').dataset.id;
						doUrge(pendingUrgeBtn, id, captchaInput.value);
					}
				}
				
				if (captchaInput) {
					captchaInput.addEventListener('keypress', function(e) {
						if (e.key === 'Enter') submitCaptcha();
					});
				}
				
				if (captchaSubmit) {
					captchaSubmit.addEventListener('click', submitCaptcha);
				}
			}
		})();
		</script>
		<?php endif; ?>

		</div><!-- .leftbar-desktop-content -->

</aside>

<div class="modal fade" id="blog_categories" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">

	<div class="modal-dialog modal-dialog-centered">

		<div class="modal-content">

			<div class="modal-header">

				<h5 class="modal-title"><?php _e('分类', 'argon');?></h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">

					<span aria-hidden="true">&times;</span>

				</button>

			</div>

			<div class="modal-body">

				<?php

					$categories = get_categories(array(

						'child_of' => 0,

						'orderby' => 'name',

						'order' => 'ASC',

						'hide_empty' => 0,

						'hierarchical' => 0,

						'taxonomy' => 'category',

						'pad_counts' => false

					));

					foreach($categories as $category) {

						echo "<a href=" . get_category_link( $category -> term_id ) . " class='badge badge-secondary tag'>" . $category->name . " <span class='tag-num'>" . $category -> count . "</span></a>";

					}

				?>

			</div>

		</div>

	</div>

</div>

<div class="modal fade" id="blog_tags" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">

	<div class="modal-dialog modal-dialog-centered">

		<div class="modal-content">

			<div class="modal-header">

				<h5 class="modal-title"><?php _e('标签', 'argon');?></h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">

					<span aria-hidden="true">&times;</span>

				</button>

			</div>

			<div class="modal-body">

				<?php

					$categories = get_categories(array(

						'child_of' => 0,

						'orderby' => 'name',

						'order' => 'ASC',

						'hide_empty' => 0,

						'hierarchical' => 0,

						'taxonomy' => 'post_tag',

						'pad_counts' => false

					));

					foreach($categories as $category) {

						echo "<a href=" . get_category_link( $category -> term_id ) . " class='badge badge-secondary tag'>" . $category->name . " <span class='tag-num'>" . $category -> count . "</span></a>";

					}

				?>

			</div>

		</div>

	</div>

</div>

<?php

	if (get_option('argon_page_layout') == 'triple'){

		echo '<aside id="rightbar" class="rightbar widget-area" role="complementary">';

		dynamic_sidebar( 'rightbar-tools' );

		echo '</aside>';

	}

?>

