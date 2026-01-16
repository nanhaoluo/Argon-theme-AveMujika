<!DOCTYPE html>

<?php

	$htmlclasses = "";

	if (get_option('argon_page_layout') == "single"){

		$htmlclasses .= "single-column ";

	}

	if (get_option('argon_page_layout') == "triple"){

		$htmlclasses .= "triple-column ";

	}

	if (get_option('argon_page_layout') == "double-reverse"){

		$htmlclasses .= "double-column-reverse ";

	}

	if (get_option('argon_enable_immersion_color') == "true"){

		$htmlclasses .= "immersion-color ";

	}

	if (get_option('argon_enable_amoled_dark') == "true"){

		$htmlclasses .= "amoled-dark ";

	}

	if (get_option('argon_card_shadow') == 'big'){

		$htmlclasses .= 'use-big-shadow ';

	}

	if (get_option('argon_font') == 'serif'){

		$htmlclasses .= 'use-serif ';

	}

	if (get_option('argon_disable_codeblock_style') == 'true'){

		$htmlclasses .= 'disable-codeblock-style ';

	}

	if (get_option('argon_enable_headroom') == 'absolute'){

		$htmlclasses .= 'navbar-absolute ';

	}

	$banner_size = get_option('argon_banner_size', 'full');

	if ($banner_size != 'full'){

		if ($banner_size == 'mini'){

			$htmlclasses .= 'banner-mini ';

		}else if ($banner_size == 'hide'){

			$htmlclasses .= 'no-banner ';

		}else if ($banner_size == 'fullscreen'){

			$htmlclasses .= 'banner-as-cover ';

		}

	}

	if (get_option('argon_toolbar_blur', 'false') == 'true'){

		$htmlclasses .= 'toolbar-blur ';

	}

	// 顶栏自定义样式
	$toolbar_style = get_option('argon_toolbar_style', 'default');
	if ($toolbar_style == 'gradient') {
		$htmlclasses .= 'toolbar-gradient ';
	} else if ($toolbar_style == 'glass') {
		$htmlclasses .= 'toolbar-glass ';
	}

	if (get_option('argon_toolbar_shadow', 'false') == 'true') {
		$htmlclasses .= 'toolbar-shadow ';
	}

	if (get_option('argon_toolbar_border', 'false') == 'true') {
		$htmlclasses .= 'toolbar-border ';
	}

	if (get_option('argon_toolbar_compact', 'false') == 'true') {
		$htmlclasses .= 'toolbar-compact ';
	}

	if (get_option('argon_toolbar_center', 'false') == 'true') {
		$htmlclasses .= 'toolbar-center ';
	}

	$htmlclasses .= get_option('argon_article_header_style', 'article-header-style-default') . ' ';

	if(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false){

		$htmlclasses .= ' using-safari';

	}

?>

<html <?php language_attributes(); ?> class="no-js <?php echo $htmlclasses;?>">

<?php

	$themecolor = get_option("argon_theme_color", "#5e72e4");

	$themecolor_origin = $themecolor;

	if (isset($_COOKIE["argon_custom_theme_color"])){

		if (checkHEX($_COOKIE["argon_custom_theme_color"]) && get_option('argon_show_customize_theme_color_picker') != 'false'){

			$themecolor = $_COOKIE["argon_custom_theme_color"];

		}

	}

	if (hex2gray($themecolor) < 50){

		echo '<script>document.getElementsByTagName("html")[0].classList.add("themecolor-toodark");</script>';

	}

?>

<?php

	$cardradius = get_option('argon_card_radius');

	if ($cardradius == ""){

		$cardradius = "4";

	}

	$cardradius_origin = $cardradius;

	if (isset($_COOKIE["argon_card_radius"]) && $_COOKIE["argon_card_radius"] != ""){

		$cardradius = $_COOKIE["argon_card_radius"];

	}

?>

<head>

	<meta charset="<?php bloginfo( 'charset' ); ?>">

	<?php if (get_option('argon_enable_mobile_scale') != 'true'){ ?>

		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

	<?php }else{ ?>

		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

	<?php } ?>

	<meta property="og:title" content="<?php echo wp_get_document_title();?>">

	<meta property="og:type" content="article">

	<meta property="og:url" content="<?php echo home_url(add_query_arg(array(),$wp->request));?>">

	<?php

		$seo_description = get_seo_description();

		if ($seo_description != ''){ ?>

			<meta name="description" content="<?php echo $seo_description?>">

			<meta property="og:description" content="<?php echo $seo_description?>">

	<?php } ?>



	<?php

		$seo_keywords = get_seo_keywords();

		if ($seo_keywords != ''){ ?>

			<meta name="keywords" content="<?php echo get_seo_keywords();?>">

	<?php } ?>



	<?php

		if (is_single() || is_page()){

			$og_image = get_og_image();

			if ($og_image != ''){ ?>

				<meta property="og:image" content="<?php echo $og_image?>" />

	<?php 	} else { ?>

				<meta property="og:image" content="https://blog.cartol.top/favicon.ico" />

	<?php 	}

		} else { ?>

			<meta property="og:image" content="https://blog.cartol.top/favicon.ico" />

	<?php } ?>



	<meta name="theme-color" content="<?php echo $themecolor; ?>">

	<meta name="theme-color-rgb" content="<?php echo hex2str($themecolor); ?>">

	<meta name="theme-color-origin" content="<?php echo $themecolor_origin; ?>">

	<meta name="argon-enable-custom-theme-color" content="<?php echo (get_option('argon_show_customize_theme_color_picker') != 'false' ? 'true' : 'false'); ?>">





	<meta name="theme-card-radius" content="<?php echo $cardradius; ?>">

	<meta name="theme-card-radius-origin" content="<?php echo $cardradius_origin; ?>">



	<meta name="theme-version" content="<?php echo $GLOBALS['theme_version']; ?>">



	<link rel="profile" href="http://gmpg.org/xfn/11">

	<?php if ( is_singular() && pings_open( get_queried_object() ) ) : ?>

	<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>">

	<?php endif; ?>

	<?php
		// 获取资源版本号（支持强制刷新）
		$assets_version = function_exists('argon_get_assets_version') ? argon_get_assets_version() : $GLOBALS['theme_version'];
		
		// CSS 加载 - 添加 media 属性确保移动端正确加载
		wp_enqueue_style("argon_css_merged", $GLOBALS['assets_path'] . "/assets/argon_css_merged.css", array(), $assets_version, 'all');
		wp_enqueue_style("style", $GLOBALS['assets_path'] . "/style.css", array('argon_css_merged'), $assets_version, 'all');
		
		// 集成外部资源备用机制
		wp_enqueue_script("resource_loader", $GLOBALS['assets_path'] . "/assets/vendor/external/resource-loader.js", array(), $assets_version, false);

		if (get_option('argon_disable_googlefont') != 'true') {
			// 使用备用机制加载Google Fonts
			echo '<script>
				document.addEventListener("DOMContentLoaded", function() {
					if (typeof ArgonResourceLoader !== "undefined") {
						ArgonResourceLoader.smartLoad("//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Serif+SC:300,600&display=swap", "css");
					}
				});
			</script>';
		}

		// 加载 argon_js_merged（包含 jQuery 和其他库）- 在头部同步加载以确保后续脚本可用
		wp_enqueue_script("argon_js_merged", $GLOBALS['assets_path'] . "/assets/argon_js_merged.js", array(), $assets_version, false);
	?>
	
	<?php wp_head(); ?>
	
	<?php if (function_exists('argon_is_force_refresh_enabled') && argon_is_force_refresh_enabled()): ?>
	<!-- 强制刷新缓存 - 清除浏览器和 Service Worker 缓存 -->
	<script>
	(function() {
		var forceRefreshKey = 'argon_force_refresh_version';
		var currentVersion = '<?php echo get_option('argon_force_refresh_enabled_time', 0); ?>';
		var lastVersion = localStorage.getItem(forceRefreshKey);
		
		// 版本变化时清除所有缓存
		if (lastVersion !== currentVersion) {
			localStorage.setItem(forceRefreshKey, currentVersion);
			
			// 清除 Service Worker 缓存
			if ('caches' in window) {
				caches.keys().then(function(names) {
					names.forEach(function(name) {
						caches.delete(name);
					});
				});
			}
			
			// 注销所有 Service Worker
			if ('serviceWorker' in navigator) {
				navigator.serviceWorker.getRegistrations().then(function(registrations) {
					registrations.forEach(function(registration) {
						registration.unregister();
					});
				});
			}
			
			// 如果不是首次访问，强制硬刷新
			if (lastVersion !== null) {
				// 清除 sessionStorage
				try { sessionStorage.clear(); } catch(e) {}
				
				// 强制硬刷新（绕过缓存）
				if (window.location.reload) {
					window.location.reload(true);
				}
			}
		}
	})();
	</script>
	<?php endif; ?>
	
	<!-- 移动端资源加载修复 - 确保 CSS 完全应用 -->
	<script>
		(function() {
			// 检测移动端并强制重新计算样式
			if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
				// 确保 CSS 已加载
				var checkCSS = function() {
					var testEl = document.createElement('div');
					testEl.className = 'container';
					testEl.style.cssText = 'position:absolute;visibility:hidden;';
					document.body.appendChild(testEl);
					var computed = window.getComputedStyle(testEl);
					var isLoaded = computed.width !== 'auto' && computed.width !== '';
					document.body.removeChild(testEl);
					return isLoaded;
				};
				
				// 如果 CSS 未完全加载，强制重新加载
				document.addEventListener('DOMContentLoaded', function() {
					if (!checkCSS()) {
						// 触发重排以应用样式
						document.body.style.display = 'none';
						document.body.offsetHeight; // 强制重排
						document.body.style.display = '';
					}
				});
			}
		})();
		
		// 用户角色检测 - 传递给前端JavaScript
		window.argonUserRole = '<?php echo current_user_can('administrator') ? 'administrator' : (is_user_logged_in() ? 'user' : 'guest'); ?>';
	</script>
	
	<!-- Argon 修复补丁 - 必须在 wp_head() 之后立即执行 -->
	<script src="<?php echo get_template_directory_uri(); ?>/assets/js/argon.min.js?ver=<?php echo $assets_version; ?>"></script>

	<?php $GLOBALS['wp_path'] = get_option('argon_wp_path') == '' ? '/' : get_option('argon_wp_path'); ?>

	<script>

		document.documentElement.classList.remove("no-js");

		var argonConfig = {

			wp_path: "<?php echo $GLOBALS['wp_path']; ?>",

			language: "<?php echo argon_get_locate(); ?>",

			dateFormat: "<?php echo get_option('argon_dateformat', 'YMD'); ?>",

			<?php if (get_option('argon_enable_zoomify') == 'true'){ ?>

				zoomify: {

					duration: <?php echo get_option('argon_zoomify_duration', 200); ?>,

					easing: "<?php echo get_option('argon_zoomify_easing', 'cubic-bezier(0.4,0,0,1)'); ?>",

					scale: <?php echo get_option('argon_zoomify_scale', 0.9); ?>

				},

			<?php } else { ?>

				zoomify: false,

			<?php } ?>

			pangu: "<?php echo get_option('argon_enable_pangu', 'false'); ?>",

			// 懒加载配置
			lazyload: <?php echo (get_option('argon_enable_lazyload') !== 'false' ? 'true' : 'false'); ?>,
			lazyload_effect: "<?php echo get_option('argon_lazyload_effect', 'fadeIn'); ?>",
			lazyload_threshold: <?php echo (get_option('argon_lazyload_threshold') == '' ? '800' : get_option('argon_lazyload_threshold')); ?>,

			fold_long_comments: <?php echo get_option('argon_fold_long_comments', 'false'); ?>,

			fold_long_shuoshuo: <?php echo get_option('argon_fold_long_shuoshuo', 'false'); ?>,

			disable_pjax: <?php echo get_option('argon_pjax_disabled', 'false'); ?>,

			pjax_animation_durtion: <?php echo (get_option("argon_disable_pjax_animation") == 'true' ? '0' : '600'); ?>,

			headroom: "<?php echo get_option('argon_enable_headroom', 'false'); ?>",

			waterflow_columns: "<?php echo get_option('argon_article_list_waterflow', '1'); ?>",

			article_list_layout_mobile: "<?php echo get_option('argon_article_list_layout_mobile', '1'); ?>",

			code_highlight: {

				enable: <?php echo get_option('argon_enable_code_highlight', 'false'); ?>,

				hide_linenumber: <?php echo get_option('argon_code_highlight_hide_linenumber', 'false'); ?>,

				transparent_linenumber: <?php echo get_option('argon_code_highlight_transparent_linenumber', 'false'); ?>,

				break_line: <?php echo get_option('argon_code_highlight_break_line', 'false'); ?>

			}

		}

	</script>

	<script>

		var darkmodeAutoSwitch = "<?php echo (get_option("argon_darkmode_autoswitch") == '' ? 'false' : get_option("argon_darkmode_autoswitch"));?>";

		function setDarkmode(enable){

			// 添加过渡类以启用平滑切换动画
			document.documentElement.classList.add("theme-transitioning");

			if (enable == true){

				document.documentElement.classList.add("darkmode");

			}else{

				document.documentElement.classList.remove("darkmode");

			}

			// 过渡完成后移除过渡类
			setTimeout(function() {
				document.documentElement.classList.remove("theme-transitioning");
			}, 300);

			// 延迟触发 scroll 事件，等待 jQuery 加载
			if (typeof jQuery !== 'undefined') {

				jQuery(window).trigger("scroll");

			} else {

				window.dispatchEvent(new Event('scroll'));

			}

		}

		function toggleDarkmode(){

			if (document.documentElement.classList.contains("darkmode")){

				setDarkmode(false);

				sessionStorage.setItem("Argon_Enable_Dark_Mode", "false");

			}else{

				setDarkmode(true);

				sessionStorage.setItem("Argon_Enable_Dark_Mode", "true");

			}

		}

		if (sessionStorage.getItem("Argon_Enable_Dark_Mode") == "true"){

			setDarkmode(true);

		}

		function toggleDarkmodeByPrefersColorScheme(media){

			if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){

				return;

			}

			if (media.matches){

				setDarkmode(true);

			}else{

				setDarkmode(false);

			}

		}

		function toggleDarkmodeByTime(){

			if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){

				return;

			}

			let hour = new Date().getHours();

			if (<?php echo apply_filters("argon_darkmode_time_check", "hour < 7 || hour >= 22")?>){

				setDarkmode(true);

			}else{

				setDarkmode(false);

			}

		}

		if (darkmodeAutoSwitch == 'system'){

			var darkmodeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");

			darkmodeMediaQuery.addListener(toggleDarkmodeByPrefersColorScheme);

			toggleDarkmodeByPrefersColorScheme(darkmodeMediaQuery);

		}

		if (darkmodeAutoSwitch == 'time'){

			toggleDarkmodeByTime();

		}

		if (darkmodeAutoSwitch == 'alwayson'){

			setDarkmode(true);

		}



		function toggleAmoledDarkMode(){

			document.documentElement.classList.toggle("amoled-dark");

			if (document.documentElement.classList.contains("amoled-dark")){

				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "true");

			}else{

				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "false");

			}

		}

		if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "true"){

			document.documentElement.classList.add("amoled-dark");

		}else if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "false"){

			document.documentElement.classList.remove("amoled-dark");

		}

	</script>

	<script>

		if (navigator.userAgent.indexOf("Safari") !== -1 && navigator.userAgent.indexOf("Chrome") === -1){

			document.documentElement.classList.add("using-safari");

		}

	</script>



	<?php if (get_option('argon_enable_smoothscroll_type') == '2') { /*平滑滚动*/?>

		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll2.js"></script>

	<?php }else if (get_option('argon_enable_smoothscroll_type') == '3'){?>

		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll3.min.js"></script>

	<?php }else if (get_option('argon_enable_smoothscroll_type') == '1_pulse'){?>

		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll1_pulse.js"></script>

	<?php }else if (get_option('argon_enable_smoothscroll_type') != 'disabled'){?>

		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll1.js"></script>

	<?php }?>

</head>



<?php echo get_option('argon_custom_html_head'); ?>



<style id="themecolor_css">

	<?php

		$themecolor_rgbstr = hex2str($themecolor);

		$RGB = hexstr2rgb($themecolor);

		$HSL = rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']);

	?>

	:root{

		--themecolor: <?php echo $themecolor; ?>;

		--themecolor-R: <?php echo $RGB['R']; ?>;

		--themecolor-G: <?php echo $RGB['G']; ?>;

		--themecolor-B: <?php echo $RGB['B']; ?>;

		--themecolor-H: <?php echo $HSL['H']; ?>;

		--themecolor-S: <?php echo $HSL['S']; ?>;

		--themecolor-L: <?php echo $HSL['L']; ?>;

	}

</style>

<style id="theme_cardradius_css">

	:root{

		--card-radius: <?php echo $cardradius; ?>px;

	}

</style>

<?php
// 卡片毛玻璃效果设置
$card_opacity = get_option('argon_post_background_opacity', '0.7');
$card_blur = get_option('argon_card_blur', '20');
$card_saturate = get_option('argon_card_saturate', '180');

// 如果透明度为空或为1，使用推荐默认值
if ($card_opacity == '' || $card_opacity == '1') {
	$card_opacity = '0.7';
}
?>

<style id="theme_card_effect_css">
	:root {
		--card-opacity: <?php echo $card_opacity; ?>;
		--card-blur: <?php echo $card_blur; ?>px;
		--card-saturate: <?php echo $card_saturate; ?>%;
	}
</style>

<?php
// 动画效果设置
$animation_style = get_option('argon_animation_style', 'material3');
$animation_speed = get_option('argon_animation_speed', 'normal');
$card_hover_effect = get_option('argon_card_hover_effect', 'lift');
$button_click_effect = get_option('argon_button_click_effect', 'both');

// 根据动画速度设置时间变量
$speed_multiplier = 1;
if ($animation_speed == 'fast') {
	$speed_multiplier = 0.6;
} else if ($animation_speed == 'slow') {
	$speed_multiplier = 1.5;
}

$animation_fast = round(150 * $speed_multiplier);
$animation_normal = round(250 * $speed_multiplier);
$animation_slow = round(400 * $speed_multiplier);

// 根据动画风格设置缓动函数
$ease_standard = 'cubic-bezier(0.4, 0, 0.2, 1)';
$ease_spring = 'cubic-bezier(0.34, 1.56, 0.64, 1)';

if ($animation_style == 'apple') {
	$ease_standard = 'cubic-bezier(0.25, 0.1, 0.25, 1)';
	$ease_spring = 'cubic-bezier(0.175, 0.885, 0.32, 1.275)';
} else if ($animation_style == 'minimal') {
	$ease_standard = 'ease';
	$ease_spring = 'ease-out';
}
?>

<style id="theme_animation_css">
	:root {
		--animation-fast: <?php echo $animation_fast; ?>ms;
		--animation-normal: <?php echo $animation_normal; ?>ms;
		--animation-slow: <?php echo $animation_slow; ?>ms;
		--ease-standard: <?php echo $ease_standard; ?>;
		--ease-spring: <?php echo $ease_spring; ?>;
	}
	
	<?php if ($animation_style == 'disabled'): ?>
	*, *::before, *::after {
		animation-duration: 0.01ms !important;
		transition-duration: 0.01ms !important;
	}
	<?php endif; ?>
	
	<?php if ($card_hover_effect == 'none'): ?>
	.post-item:hover, article.card:hover {
		transform: none !important;
		box-shadow: inherit !important;
	}
	<?php elseif ($card_hover_effect == 'glow'): ?>
	.post-item:hover, article.card:hover {
		transform: none !important;
		box-shadow: 0 0 20px rgba(var(--themecolor-rgbstr), 0.3) !important;
	}
	<?php elseif ($card_hover_effect == 'scale'): ?>
	.post-item:hover, article.card:hover {
		transform: scale(1.02) !important;
	}
	<?php endif; ?>
	
	<?php if ($button_click_effect == 'none'): ?>
	.btn:active, button:active:not(:disabled) {
		transform: none !important;
	}
	<?php elseif ($button_click_effect == 'scale'): ?>
	.btn:active, button:active:not(:disabled) {
		transform: scale(0.95) !important;
	}
	<?php endif; ?>
</style>



<body <?php body_class(); ?>>

<?php /*wp_body_open();*/ ?>

<div id="toolbar">

	<header class="header-global">

		<nav id="navbar-main" class="navbar navbar-main navbar-expand-lg navbar-transparent navbar-light bg-primary headroom--not-bottom headroom--not-top headroom--pinned">

			<div class="container">

				<button id="open_sidebar" class="navbar-toggler" type="button" aria-expanded="false" aria-label="Toggle sidebar">

					<span class="navbar-toggler-icon"></span>

				</button>

				<div class="navbar-brand mr-0">

					<?php if (get_option('argon_toolbar_icon') != '') { /*顶栏ICON(如果选项中开启)*/?>

						<a class="navbar-brand navbar-icon mr-lg-5" href="<?php echo get_option('argon_toolbar_icon_link'); ?>">

							<img src="<?php echo get_option('argon_toolbar_icon'); ?>">

						</a>

					<?php }?>

					<?php

						//顶栏标题

						$toolbar_title = get_option('argon_toolbar_title') == '' ? get_bloginfo('name') : get_option('argon_toolbar_title');

						if ($toolbar_title == '--hidden--'){

							$toolbar_title = '';

						}

					?>

					<a class="navbar-brand navbar-title" href="<?php bloginfo('url'); ?>"><?php echo $toolbar_title;?></a>

				</div>

				<div class="navbar-collapse collapse" id="navbar_global">

					<div class="navbar-collapse-header">

						<div class="row" style="display: none;">

							<div class="col-6 collapse-brand"></div>

							<div class="col-6 collapse-close">

								<button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbar_global" aria-controls="navbar_global" aria-expanded="false" aria-label="Toggle navigation">

									<span></span>

									<span></span>

								</button>

							</div>

						</div>

						<div class="input-group input-group-alternative">

							<div class="input-group-prepend">

								<span class="input-group-text"><i class="fa fa-search"></i></span>

							</div>

							<input id="navbar_search_input_mobile" class="form-control" placeholder="搜索什么..." type="text" autocomplete="off">

						</div>

					</div>

					<?php

						/*顶栏菜单*/

						class toolbarMenuWalker extends Walker_Nav_Menu{

							public function start_lvl( &$output, $depth = 0, $args = array() ) {

								$indent = str_repeat("\t", $depth);

								$output .= "\n$indent<div class=\"dropdown-menu\">\n";

							}

							public function end_lvl( &$output, $depth = 0, $args = array() ) {

								$indent = str_repeat("\t", $depth);

								$output .= "\n$indent</div>\n";

							}

							public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

								if ($depth == 0){

									if ($args -> walker -> has_children == 1){

										$output .= "\n

										<li class='nav-item dropdown'>

											<a href='" . $object -> url . "' class='nav-link' data-toggle='dropdown' no-pjax onclick='return false;' title='" . $object -> description . "'>

										  		<i class='ni ni-book-bookmark d-lg-none'></i>

												<span class='nav-link-inner--text'>" . $object -> title . "</span>

										  </a>";

									}else{

										$output .= "\n

										<li class='nav-item'>

											<a href='" . $object -> url . "' class='nav-link' target='" . $object -> target . "' title='" . $object -> description . "'>

										  		<i class='ni ni-book-bookmark d-lg-none'></i>

												<span class='nav-link-inner--text'>" . $object -> title . "</span>

										  </a>";

									}

								}else if ($depth == 1){

									$output .= "<a href='" . $object -> url . "' class='dropdown-item' target='" . $object -> target . "' title='" . $object -> description . "'>" . $object -> title . "</a>";

								}

							}

							public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

								if ($depth == 0){

									$output .= "\n</li>";

								}

							}

						}

						if ( has_nav_menu('toolbar_menu') ){

							echo "<ul class='navbar-nav navbar-nav-hover align-items-lg-center'>";

							wp_nav_menu( array(

								'container'  => '',

								'theme_location'  => 'toolbar_menu',

								'items_wrap'  => '%3$s',

								'depth' => 0,

								'walker' => new toolbarMenuWalker()

							) );

							echo "</ul>";

						}

						// 左侧自定义链接
						$toolbar_links_left = get_option('argon_toolbar_links_left', '');
						if (!empty(trim($toolbar_links_left))) {
							echo '<div class="navbar-custom-links navbar-custom-links-left">';
							foreach (explode("\n", $toolbar_links_left) as $line) {
								$line = trim($line);
								if (empty($line)) continue;
								$parts = array_map('trim', explode('|', $line));
								if (!empty($parts[0])) {
									$url = isset($parts[1]) ? $parts[1] : '#';
									$icon = isset($parts[2]) ? '<i class="' . esc_attr($parts[2]) . '"></i>' : '';
									echo '<a href="' . esc_url($url) . '">' . $icon . esc_html($parts[0]) . '</a>';
								}
							}
							echo '</div>';
						}

					?>

					<ul class="navbar-nav align-items-lg-center ml-lg-auto">

						<?php
						// 右侧自定义链接
						$toolbar_links_right = get_option('argon_toolbar_links_right', '');
						if (!empty(trim($toolbar_links_right))) {
							echo '<li class="nav-item"><div class="navbar-custom-links navbar-custom-links-right">';
							foreach (explode("\n", $toolbar_links_right) as $line) {
								$line = trim($line);
								if (empty($line)) continue;
								$parts = array_map('trim', explode('|', $line));
								if (!empty($parts[0])) {
									$url = isset($parts[1]) ? $parts[1] : '#';
									$icon = isset($parts[2]) ? '<i class="' . esc_attr($parts[2]) . '"></i>' : '';
									echo '<a href="' . esc_url($url) . '">' . $icon . esc_html($parts[0]) . '</a>';
								}
							}
							echo '</div></li>';
						}
						?>

						<li id="navbar_search_container" class="nav-item" data-toggle="modal">

							<div id="navbar_search_input_container">

								<div class="input-group input-group-alternative">

									<div class="input-group-prepend">

										<span class="input-group-text"><i class="fa fa-search"></i></span>

									</div>

									<input id="navbar_search_input" class="form-control" placeholder="<?php _e('搜索什么...', 'argon');?>" type="text" autocomplete="off">

								</div>

							</div>

						</li>

					</ul>

				</div>

				<div id="navbar_menu_mask" data-toggle="collapse" data-target="#navbar_global"></div>

				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar_global" aria-controls="navbar_global" aria-expanded="false" aria-label="Toggle navigation">

					<span class="navbar-toggler-icon navbar-toggler-searcg-icon"></span>

				</button>

			</div>

		</nav>

	</header>

</div>

<div class="modal fade" id="argon_search_modal" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">

	<div class="modal-dialog modal-dialog-centered modal-sm" role="document">

		<div class="modal-content">

			<div class="modal-header">

				<h5 class="modal-title"><?php _e('搜索', 'argon');?></h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">

					<span aria-hidden="true">&times;</span>

				</button>

			</div>

			<div class="modal-body">

				<?php get_search_form(); ?>

			</div>

		</div>

	</div>

</div>

<!--Banner-->

<section id="banner" class="banner section section-lg section-shaped">

	<div class="shape <?php echo get_option('argon_banner_background_hide_shapes') == 'true' ? '' : 'shape-style-1' ?> <?php echo get_option('argon_banner_background_color_type') == '' ? 'shape-primary' : get_option('argon_banner_background_color_type'); ?>">

		<span></span>

		<span></span>

		<span></span>

		<span></span>

		<span></span>

		<span></span>

		<span></span>

		<span></span>

		<span></span>

	</div>



	<?php

		$banner_title = get_option('argon_banner_title') == '' ? get_bloginfo('name') : get_option('argon_banner_title');

		$enable_banner_title_typing_effect = get_option('argon_enable_banner_title_typing_effect') != 'true' ? "false" : get_option('argon_enable_banner_title_typing_effect');

	?>

	<div id="banner_container" class="banner-container container text-center">

		<?php if ($enable_banner_title_typing_effect != "true"){?>

			<div class="banner-title text-white"><span class="banner-title-inner"><?php echo apply_filters('argon_banner_title_html', $banner_title); ?></span>

			<?php echo get_option('argon_banner_subtitle') == '' ? '' : '<span class="banner-subtitle d-block">' . get_option('argon_banner_subtitle') . '</span>'; ?></div>

		<?php } else {?>

			<div class="banner-title text-white" data-interval="<?php echo get_option('argon_banner_typing_effect_interval', 100); ?>"><span data-text="<?php echo $banner_title; ?>" class="banner-title-inner">&nbsp;</span>

			<?php echo get_option('argon_banner_subtitle') == '' ? '' : '<span data-text="' . get_option('argon_banner_subtitle') . '" class="banner-subtitle d-block">&nbsp;</span>'; ?></div>

		<?php }?>

	</div>

	<?php if (get_option('argon_banner_background_url') != '') { ?>

		<style>

			section.banner{

				background-image: url(<?php echo get_banner_background_url(); ?>) !important;

			}

		</style>

	<?php } ?>

	<?php if ($banner_size == 'fullscreen') { ?>

		<div class="cover-scroll-down">

			<i class="fa fa-angle-down" aria-hidden="true"></i>

		</div>

	<?php } ?>

</section>



<?php if (apply_filters('argon_page_background_url', get_option('argon_page_background_url')) != '') { ?>

	<style>

		<?php if (get_option('argon_page_background_banner_style', 'false') == 'transparent') { ?>

			#banner, #banner .shape {

				background: transparent !important;

			}

		<?php } ?>

		#content:before {

			content: '';

			display: block;

			position: fixed;

			left: 0;

			right: 0;

			top: 0;

			bottom: 0;

			z-index: -2;

			background: url(<?php echo apply_filters('argon_page_background_url', get_option('argon_page_background_url'));?>);

			background-position: center;

			background-size: cover;

			background-repeat: no-repeat;

			opacity: <?php echo (get_option('argon_page_background_opacity') == '' ? '1' : get_option('argon_page_background_opacity')); ?>;

			transition: opacity .5s ease;

		}

		html.darkmode #content:before{

			filter: brightness(0.65);

		}

		<?php if (apply_filters('argon_page_background_dark_url', get_option('argon_page_background_dark_url')) != '') { ?>

			#content:after {

				content: '';

				display: block;

				position: fixed;

				left: 0;

				right: 0;

				top: 0;

				bottom: 0;

				z-index: -2;

				background: url(<?php echo apply_filters('argon_page_background_dark_url', get_option('argon_page_background_dark_url'));?>);

				background-position: center;

				background-size: cover;

				background-repeat: no-repeat;

				opacity: 0;

				transition: opacity .5s ease;

			}

			html.darkmode #content:after {

				opacity: <?php echo (get_option('argon_page_background_opacity') == '' ? '1' : get_option('argon_page_background_opacity')); ?>;

			}

			html.darkmode #content:before {

				opacity: 0;

			}

		<?php } ?>

		/* 卡片透明度和毛玻璃效果 - 使用 CSS 变量 */
		<?php 
		$post_bg_opacity_inline = get_option('argon_post_background_opacity', '0.7');
		$card_blur_inline = intval(get_option('argon_card_blur', '20'));
		$card_saturate_inline = intval(get_option('argon_card_saturate', '180'));
		
		// 如果透明度为空或为1，使用推荐默认值
		if ($post_bg_opacity_inline == '' || $post_bg_opacity_inline == '1') {
			$post_bg_opacity_inline = '0.7';
		}
		?>
		article.post.card,
		#comments.card,
		#post_comment.card,
		.post-navigation.card,
		.related-posts.card,
		.card.bg-white {
			background-color: rgba(255, 255, 255, <?php echo $post_bg_opacity_inline; ?>) !important;
			backdrop-filter: blur(<?php echo $card_blur_inline; ?>px) saturate(<?php echo $card_saturate_inline; ?>%);
			-webkit-backdrop-filter: blur(<?php echo $card_blur_inline; ?>px) saturate(<?php echo $card_saturate_inline; ?>%);
		}
		html.darkmode article.post.card,
		html.darkmode #comments.card,
		html.darkmode #post_comment.card,
		html.darkmode .post-navigation.card,
		html.darkmode .related-posts.card,
		html.darkmode .card.bg-white {
			background-color: rgba(66, 66, 66, <?php echo $post_bg_opacity_inline; ?>) !important;
		}
		#leftbar .card,
		#leftbar_part1,
		#leftbar_part2 {
			backdrop-filter: blur(<?php echo $card_blur_inline; ?>px) saturate(<?php echo $card_saturate_inline; ?>%);
			-webkit-backdrop-filter: blur(<?php echo $card_blur_inline; ?>px) saturate(<?php echo $card_saturate_inline; ?>%);
		}

	</style>

<?php } ?>

<!-- 毛玻璃效果 - 独立于背景图片设置（无背景图时也生效） -->
<?php 
$card_blur_standalone = intval(get_option('argon_card_blur', '20'));
$card_saturate_standalone = intval(get_option('argon_card_saturate', '180'));
$post_bg_opacity_standalone = get_option('argon_post_background_opacity', '0.7');

// 只有在没有背景图片时才输出毛玻璃样式（有背景图片时上面已经输出了）
if (apply_filters('argon_page_background_url', get_option('argon_page_background_url')) == ''): 
	// 如果透明度为空或为1，使用推荐默认值
	if ($post_bg_opacity_standalone == '' || $post_bg_opacity_standalone == '1') {
		$post_bg_opacity_standalone = '0.7';
	}
?>
<style id="theme_blur_css">
	/* 卡片透明度和毛玻璃效果 */
	article.post.card,
	#comments.card,
	#post_comment.card,
	.post-navigation.card,
	.related-posts.card,
	.card.bg-white {
		background-color: rgba(255, 255, 255, <?php echo $post_bg_opacity_standalone; ?>) !important;
		backdrop-filter: blur(<?php echo $card_blur_standalone; ?>px) saturate(<?php echo $card_saturate_standalone; ?>%);
		-webkit-backdrop-filter: blur(<?php echo $card_blur_standalone; ?>px) saturate(<?php echo $card_saturate_standalone; ?>%);
	}
	html.darkmode article.post.card,
	html.darkmode #comments.card,
	html.darkmode #post_comment.card,
	html.darkmode .post-navigation.card,
	html.darkmode .related-posts.card,
	html.darkmode .card.bg-white {
		background-color: rgba(66, 66, 66, <?php echo $post_bg_opacity_standalone; ?>) !important;
	}
	#leftbar .card,
	#leftbar_part1,
	#leftbar_part2 {
		backdrop-filter: blur(<?php echo $card_blur_standalone; ?>px) saturate(<?php echo $card_saturate_standalone; ?>%);
		-webkit-backdrop-filter: blur(<?php echo $card_blur_standalone; ?>px) saturate(<?php echo $card_saturate_standalone; ?>%);
	}
</style>
<?php endif; ?>


<?php if (get_option('argon_show_toolbar_mask') == 'true') { ?>

	<style>

		#banner:after {

			content: '';

			width: 100vw;

			position: absolute;

			left: 0;

			top: 0;

			height: 120px;

			background: linear-gradient(180deg, rgba(0,0,0,0.25) 0%, rgba(0,0,0,0.15) 35%, rgba(0,0,0,0) 100%);

			display: block;

			z-index: -1;

		}

		.banner-title {

			text-shadow: 0 5px 15px rgba(0, 0, 0, .2);

		}

	</style>

<?php } ?>



<div id="float_action_buttons" class="float-action-buttons fabtns-unloaded">

	<button id="fabtn_toggle_sides" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-hidden="true" tooltip-move-to-left="<?php _e('移至左侧', 'argon'); ?>" tooltip-move-to-right="<?php _e('移至右侧', 'argon'); ?>">

		<span class="btn-inner--icon fabtn-show-on-right"><i class="fa fa-caret-left"></i></span>

		<span class="btn-inner--icon fabtn-show-on-left"><i class="fa fa-caret-right"></i></span>

	</button>

	<button id="fabtn_back_to_top" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-label="Back To Top" tooltip="<?php _e('回到顶部', 'argon'); ?>">

		<span class="btn-inner--icon"><i class="fa fa-angle-up"></i></span>

	</button>

	<button id="fabtn_go_to_comment" class="btn btn-icon btn-neutral fabtn shadow-sm fabtn-hidden" type="button" <?php if (get_option('argon_fab_show_gotocomment_button') != 'true') echo " style='display: none !important;'";?> aria-label="Comment" tooltip="<?php _e('评论', 'argon'); ?>">

		<span class="btn-inner--icon"><i class="fa fa-comment-o"></i></span>

	</button>

	<button id="fabtn_toggle_darkmode" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" <?php if (get_option('argon_fab_show_darkmode_button') != 'true') echo " style='display: none;'";?> aria-label="Toggle Darkmode" tooltip-darkmode="<?php _e('夜间模式', 'argon'); ?>" tooltip-blackmode="<?php _e('暗黑模式', 'argon'); ?>" tooltip-lightmode="<?php _e('日间模式', 'argon'); ?>">

		<span class="btn-inner--icon"><i class="fa fa-moon-o"></i><i class='fa fa-lightbulb-o'></i></span>

	</button>

	<button id="fabtn_toggle_blog_settings_popup" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" <?php if (get_option('argon_fab_show_settings_button') != 'true') echo " style='display: none !important;'";?> aria-label="Open Blog Settings Menu" tooltip="<?php _e('设置', 'argon'); ?>">

		<span class="btn-inner--icon"><i class="fa fa-cog"></i></span>

	</button>

	<div id="fabtn_blog_settings_popup" class="card shadow-sm" style="opacity: 0;" aria-hidden="true">

		<div id="close_blog_settings"><i class="fa fa-close"></i></div>

		<div class="blog-setting-item mt-3">

			<div style="transform: translateY(-4px);"><div id="blog_setting_toggle_darkmode_and_amoledarkmode" tooltip-switch-to-darkmode="<?php _e('切换到夜间模式', 'argon'); ?>" tooltip-switch-to-blackmode="<?php _e('切换到暗黑模式', 'argon'); ?>"><span><?php _e('夜间模式', 'argon');?></span><span><?php _e('暗黑模式', 'argon');?></span></div></div>

			<div style="flex: 1;"></div>

			<label id="blog_setting_darkmode_switch" class="custom-toggle">

				<span class="custom-toggle-slider rounded-circle"></span>

			</label>

		</div>

		<div class="blog-setting-item mt-3">

			<div style="flex: 1;"><?php _e('字体', 'argon');?></div>

			<div>

				<button id="blog_setting_font_sans_serif" type="button" class="blog-setting-font btn btn-outline-primary blog-setting-selector-left">Sans Serif</button><button id="blog_setting_font_serif" type="button" class="blog-setting-font btn btn-outline-primary blog-setting-selector-right">Serif</button>

			</div>

		</div>

		<div class="blog-setting-item mt-3">

			<div style="flex: 1;"><?php _e('阴影', 'argon');?></div>

			<div>

				<button id="blog_setting_shadow_small" type="button" class="blog-setting-shadow btn btn-outline-primary blog-setting-selector-left"><?php _e('浅阴影', 'argon');?></button><button id="blog_setting_shadow_big" type="button" class="blog-setting-shadow btn btn-outline-primary blog-setting-selector-right"><?php _e('深阴影', 'argon');?></button>

			</div>

		</div>

		<div class="blog-setting-item mt-3 mb-3">

			<div style="flex: 1;"><?php _e('滤镜', 'argon');?></div>

			<div id="blog_setting_filters" class="ml-3">

				<button id="blog_setting_filter_off" type="button" class="blog-setting-filter-btn ml-0" filter-name="off"><?php _e('关闭', 'argon');?></button>

				<button id="blog_setting_filter_sunset" type="button" class="blog-setting-filter-btn" filter-name="sunset"><?php _e('日落', 'argon');?></button>

				<button id="blog_setting_filter_darkness" type="button" class="blog-setting-filter-btn" filter-name="darkness"><?php _e('暗化', 'argon');?></button>

				<button id="blog_setting_filter_grayscale" type="button" class="blog-setting-filter-btn" filter-name="grayscale"><?php _e('灰度', 'argon');?></button>

			</div>

		</div>

		<div class="blog-setting-item mt-3 mb-3">

			<div style="flex: 1;"><?php _e('风格', 'argon');?></div>

			<div id="blog_setting_styles" class="ml-3">

				<button type="button" class="blog-setting-style-btn ml-0" style-name="default"><?php _e('默认', 'argon');?></button>

				<button type="button" class="blog-setting-style-btn" style-name="glass"><?php _e('玻璃', 'argon');?></button>

				<button type="button" class="blog-setting-style-btn" style-name="neumorphism"><?php _e('新拟态', 'argon');?></button>

			</div>

		</div>

		<div class="blog-setting-item mb-3">

			<div id="blog_setting_card_radius_to_default" style="cursor: pointer;" tooltip="<?php _e('恢复默认', 'argon'); ?>"><?php _e('圆角', 'argon');?></div>

			<div style="flex: 1;margin-left: 20px;margin-right: 8px;transform: translateY(2px);">

				<div id="blog_setting_card_radius"></div>

			</div>

		</div>

		<?php if (get_option('argon_show_customize_theme_color_picker') != 'false') {?>

			<div class="blog-setting-item mt-1 mb-3">

				<div style="flex: 1;"><?php _e('主题色', 'argon');?></div>

				<div id="theme-color-picker" class="ml-3"></div>

			</div>

		<?php }?>

	</div>

	<button id="fabtn_reading_progress" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-hidden="true" tooltip="<?php _e('阅读进度', 'argon'); ?>">

		<div id="fabtn_reading_progress_bar" style="width: 0%;"></div>

		<span id="fabtn_reading_progress_details">0%</span>

	</button>

</div>



<div id="content" class="site-content">

