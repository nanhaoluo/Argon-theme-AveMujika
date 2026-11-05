<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="<?php bloginfo('template_url'); ?>/assets/vendor/external/fonts/open-sans.css" rel="stylesheet">
	<link href="<?php bloginfo('template_url'); ?>/assets/vendor/nucleo/css/nucleo.css" rel="stylesheet">
	<link href="<?php bloginfo('template_url'); ?>/assets/vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link type="text/css" href="<?php bloginfo('template_url'); ?>/assets/css/argon.min.css" rel="stylesheet">
	<?php
		argon_enqueue_aurora_background();
		wp_print_styles(array('argon-aurora'));
	?>
	<script src="<?php bloginfo('template_url'); ?>/assets/vendor/jquery/jquery.min.js"></script>
	<script src="<?php bloginfo('template_url'); ?>/assets/vendor/bootstrap/bootstrap.min.js"></script>
	<script src="<?php bloginfo('template_url'); ?>/assets/js/argon.min.js"></script>
	<title><?php _e("404 - 找不到页面", "argon"); ?></title>
	<script>
		var darkmodeAutoSwitch = "<?php echo (get_option("argon_darkmode_autoswitch") == '' ? 'false' : get_option("argon_darkmode_autoswitch"));?>";
		function setDarkmode(enable){
			// 添加过渡类以启用平滑切换动画
			$("html").addClass("theme-transitioning");
			if (enable == true){
				$("html").addClass("darkmode");
			}else{
				$("html").removeClass("darkmode");
			}
			// 过渡完成后移除过渡类
			setTimeout(function() {
				$("html").removeClass("theme-transitioning");
			}, 300);
			$(window).trigger("scroll");
		}
		function toggleDarkmode(){
			if ($("html").hasClass("darkmode")){
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
			if (hour < 7 || hour >= 22){
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
			$("html").toggleClass("amoled-dark");
			if ($("html").hasClass("amoled-dark")){
				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "true");
			}else{
				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "false");
			}
		}
		if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "true"){
			$("html").addClass("amoled-dark");
		}else if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "false"){
			$("html").removeClass("amoled-dark");
		}
	</script>
</head>
<body class="argon-404">
	<?php argon_render_aurora_background(); ?>
	<main class="argon-error-content">
		<div class="argon-error-group">
			<div class="argon-error-copy">
				<h1>404</h1>
				<p class="argon-error-message">Page not found.<br><?php _e("这个页面不见了:(", "argon"); ?></p>
			</div>
			<div class="argon-error-actions">
				<a href="javascript:window.history.back();" class="btn btn-primary">
					<i class="fa fa-chevron-left" aria-hidden="true"></i><?php _e("返回上一页", "argon"); ?>
				</a>
				<a href="<?php bloginfo('url'); ?>" class="btn btn-outline-primary">
					<i class="fa fa-home" aria-hidden="true"></i><?php _e("回到首页", "argon"); ?>
				</a>
			</div>
		</div>
		<?php echo apply_filters('argon_404page_extra_html', ''); ?>
	</main>
	<?php wp_print_scripts(array('argon-aurora')); ?>
</body>
</html>
