<?php

function themeoptions_page(){

	/*主题选项*/

?>

<script src="<?php bloginfo('template_url'); ?>/assets/vendor/jquery/jquery.min.js"></script>

	<script src="<?php bloginfo('template_url'); ?>/assets/vendor/headindex/headindex.js"></script>

	<script>!function(n){"function"==typeof define&&define.amd?define(["jquery"],function(e){return n(e)}):"object"==typeof module&&"object"==typeof module.exports?module.exports=n(require("jquery")):n(jQuery)}(function(n){function e(n){var e=7.5625,t=2.75;return n<1/t?e*n*n:n<2/t?e*(n-=1.5/t)*n+.75:n<2.5/t?e*(n-=2.25/t)*n+.9375:e*(n-=2.625/t)*n+.984375}void 0!==n.easing&&(n.easing.jswing=n.easing.swing);var t=Math.pow,u=Math.sqrt,r=Math.sin,i=Math.cos,a=Math.PI,o=1.70158,c=1.525*o,s=2*a/3,f=2*a/4.5;return n.extend(n.easing,{def:"easeOutQuad",swing:function(e){return n.easing[n.easing.def](e)},easeInQuad:function(n){return n*n},easeOutQuad:function(n){return 1-(1-n)*(1-n)},easeInOutQuad:function(n){return n<.5?2*n*n:1-t(-2*n+2,2)/2},easeInCubic:function(n){return n*n*n},easeOutCubic:function(n){return 1-t(1-n,3)},easeInOutCubic:function(n){return n<.5?4*n*n*n:1-t(-2*n+2,3)/2},easeInQuart:function(n){return n*n*n*n},easeOutQuart:function(n){return 1-t(1-n,4)},easeInOutQuart:function(n){return n<.5?8*n*n*n*n:1-t(-2*n+2,4)/2},easeInQuint:function(n){return n*n*n*n*n},easeOutQuint:function(n){return 1-t(1-n,5)},easeInOutQuint:function(n){return n<.5?16*n*n*n*n*n:1-t(-2*n+2,5)/2},easeInSine:function(n){return 1-i(n*a/2)},easeOutSine:function(n){return r(n*a/2)},easeInOutSine:function(n){return-(i(a*n)-1)/2},easeInExpo:function(n){return 0===n?0:t(2,10*n-10)},easeOutExpo:function(n){return 1===n?1:1-t(2,-10*n)},easeInOutExpo:function(n){return 0===n?0:1===n?1:n<.5?t(2,20*n-10)/2:(2-t(2,-20*n+10))/2},easeInCirc:function(n){return 1-u(1-t(n,2))},easeOutCirc:function(n){return u(1-t(n-1,2))},easeInOutCirc:function(n){return n<.5?(1-u(1-t(2*n,2)))/2:(u(1-t(-2*n+2,2))+1)/2},easeInElastic:function(n){return 0===n?0:1===n?1:-t(2,10*n-10)*r((10*n-10.75)*s)},easeOutElastic:function(n){return 0===n?0:1===n?1:t(2,-10*n)*r((10*n-.75)*s)+1},easeInOutElastic:function(n){return 0===n?0:1===n?1:n<.5?-t(2,20*n-10)*r((20*n-11.125)*f)/2:t(2,-20*n+10)*r((20*n-11.125)*f)/2+1},easeInBack:function(n){return 2.70158*n*n*n-o*n*n},easeOutBack:function(n){return 1+2.70158*t(n-1,3)+o*t(n-1,2)},easeInOutBack:function(n){return n<.5?t(2*n,2)*(7.189819*n-c)/2:(t(2*n-2,2)*((c+1)*(2*n-2)+c)+2)/2},easeInBounce:function(n){return 1-e(1-n)},easeOutBounce:e,easeInOutBounce:function(n){return n<.5?(1-e(1-2*n))/2:(1+e(2*n-1))/2}}),n});</script>

	<script src="<?php bloginfo('template_url'); ?>/assets/vendor/dragula/dragula.min.js"></script>

	<div>

		<style type="text/css">

			h2{

				font-size: 25px;

			}

			h2:before {

				content: '';

				background: #000;

				height: 16px;

				width: 6px;

				display: inline-block;

				border-radius: 15px;

				margin-right: 15px;

			}

			h3{

				font-size: 18px;

			}

			th.subtitle {

				padding: 0;

			}

			.gu-mirror{position:fixed!important;margin:0!important;z-index:9999!important;opacity:.8;-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=80)";filter:alpha(opacity=80)}.gu-hide{display:none!important}.gu-unselectable{-webkit-user-select:none!important;-moz-user-select:none!important;-ms-user-select:none!important;user-select:none!important}.gu-transit{opacity:.2;-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=20)";filter:alpha(opacity=20)}

		</style>

		<svg width="300" style="margin-top: 20px;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="673.92 415.2 510.83 151.8" enable-background="new 0 0 1920 1080" xml:space="preserve"><g><g><path fill="rgb(94, 114, 228, 0)" stroke="#5E72E4" stroke-width="3" stroke-dasharray="402" stroke-dashoffset="402" d="M811.38,450.13c-2.2-3.81-7.6-6.93-12-6.93h-52.59c-4.4,0-9.8,3.12-12,6.93l-26.29,45.54c-2.2,3.81-2.2,10.05,0,13.86l26.29,45.54c2.2,3.81,7.6,6.93,12,6.93h52.59c4.4,0,9.8-3.12,12-6.93l26.29-45.54c2.2-3.81,2.2-10.05,0-13.86L811.38,450.13z"><animate attributeName="stroke-width" begin="1s" values="3; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.5s" values="402; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="fill" begin="1s" values="rgb(94, 114, 228, 0); rgb(94, 114, 228, 0.3)" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/></path></g><g><path fill="rgb(94, 114, 228, 0)" d="M783.65,422.13c-2.2-3.81-7.6-6.93-12-6.93H715.6c-4.4,0-9.8,3.12-12,6.93l-28.03,48.54c-2.2,3.81-2.2,10.05,0,13.86l28.03,48.54c2.2,3.81,7.6,6.93,12,6.93h56.05c4.4,0,9.8-3.12,12-6.93l28.03-48.54c2.2-3.81,2.2-10.05,0-13.86L783.65,422.13z"><animateTransform attributeName="transform" type="translate" begin="1.5s" values="27.73,28; 0,0" dur="1.1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="fill" begin="1.5s" values="rgb(94, 114, 228, 0); rgb(94, 114, 228, 0.8)" dur="1.1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/></path></g></g><g><g><clipPath id="clipPath_1"><rect x="887.47" y="441.31" width="68.76" height="83.07"/></clipPath><path clip-path="url(#clipPath_1)" fill="none" stroke="#5E72E4" stroke-width="0" stroke-linecap="square" stroke-linejoin="bevel" stroke-dasharray="190" d="M893.52,533.63l28.71-90.3l31.52,90.31"><animate attributeName="stroke-width" begin="1s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.5s" values="190; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="0.5s" /></path><line clip-path="url(#clipPath_1)" fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="45" x1="940.44" y1="495.5" x2="905" y2="495.5"><animate attributeName="stroke-width" begin="1s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.5s" values="-37; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="0.5s" /></line></g><g><path fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="56" d="M976.86,469.29v55.09"><animate attributeName="stroke-width" begin="1.15s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.65s" values="56; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="0.65s" /></path><path fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="38" d="M976.86,489.77c0-9.68,7.85-17.52,17.52-17.52c3.5,0,6.76,1.03,9.5,2.8"><animate attributeName="stroke-width" begin="1.15s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.65s" values="38; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="0.65s" /></path></g><g><path fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="124" d="M1057.86,492.08c0,10.94-8.87,19.81-19.81,19.81c-10.94,0-19.81-8.87-19.81-19.81s8.87-19.81,19.81-19.81C1048.99,472.27,1057.86,481.14,1057.86,492.08z"><animate attributeName="stroke-width" begin="1.3s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.8s" values="-124; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="0.8s" /></path><path fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="110" d="M1057.84,467.27v54.05c0,10.94-8.87,19.81-19.81,19.81c-8.36,0-15.51-5.18-18.42-12.5"><animate attributeName="stroke-width" begin="1.3s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.8s" values="110; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="0.8s" /></path></g><g><path fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="140" d="M1121.83,495.46c0,12.81-9.45,23.19-21.11,23.19s-21.11-10.38-21.11-23.19c0-12.81,9.45-23.19,21.11-23.19S1121.83,482.65,1121.83,495.46z"><animate attributeName="stroke-width" begin="1.45s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="0.95s" values="-140; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="0.95s" /></path></g><g><path fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="57" d="M1143.78,524.38v-55.71"><animate attributeName="stroke-width" begin="1.6s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="1.1s" values="-57; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="1.1s" /></path><path fill="none" stroke="#5E72E4" stroke-width="0" stroke-miterlimit="10" stroke-dasharray="90" d="M1143.95,490.15c0-9.88,8.01-17.9,17.9-17.9c9.88,0,17.9,8.01,17.9,17.9v34.23"><animate attributeName="stroke-width" begin="1.6s" values="3; 10" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><animate attributeName="stroke-dashoffset" begin="1.1s" values="90; 0" dur="1s" fill="freeze" calcMode="spline" keySplines="0.8 0 0.2 1"/><set attributeName="stroke-width" to="3" begin="1.1s" /></path></g></g></svg>

		<p style="margin-top: 20px;">

			<a href="https://github.com/solstice23/argon-theme/" target="_blank" style="box-shadow: none;text-decoration: none;">

				<svg width="30" height="30" viewBox="0 0 1024 1024" fill="none" xmlns="http://www.w3.org/2000/svg">

					<path fill-rule="evenodd" clip-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8C0 11.54 2.29 14.53 5.47 15.59C5.87 15.66 6.02 15.42 6.02 15.21C6.02 15.02 6.01 14.39 6.01 13.72C4 14.09 3.48 13.23 3.32 12.78C3.23 12.55 2.84 11.84 2.5 11.65C2.22 11.5 1.82 11.13 2.49 11.12C3.12 11.11 3.57 11.7 3.72 11.94C4.44 13.15 5.59 12.81 6.05 12.6C6.12 12.08 6.33 11.73 6.56 11.53C4.78 11.33 2.92 10.64 2.92 7.58C2.92 6.71 3.23 5.99 3.74 5.43C3.66 5.23 3.38 4.41 3.82 3.31C3.82 3.31 4.49 3.1 6.02 4.13C6.66 3.95 7.34 3.86 8.02 3.86C8.7 3.86 9.38 3.95 10.02 4.13C11.55 3.09 12.22 3.31 12.22 3.31C12.66 4.41 12.38 5.23 12.3 5.43C12.81 5.99 13.12 6.7 13.12 7.58C13.12 10.65 11.25 11.33 9.47 11.53C9.76 11.78 10.01 12.26 10.01 13.01C10.01 14.08 10 14.94 10 15.21C10 15.42 10.15 15.67 10.55 15.59C13.71 14.53 16 11.53 16 8C16 3.58 12.42 0 8 0Z" transform="scale(64)" fill="#1B1F23"/>

					</svg>

				<span style="font-size: 20px;transform: translate(5px,-9px);display: inline-block;">solstice23/argon-theme</span>

			</a>

		</p>

		<h1 style="color: #5e72e4;"><?php _e("Argon 主题设置", 'argon'); ?></h1>

		<p><?php _e("按下", 'argon'); ?> <kbd style="font-family: sans-serif;">Ctrl + F</kbd> <?php _e("或在右侧目录中来查找设置", 'argon'); ?></p>

		<form method="POST" action="" id="main_form">

			<input type="hidden" name="update_themeoptions" value="true" />

			<?php wp_nonce_field("argon_update_themeoptions", "argon_update_themeoptions_nonce");?>

			<table class="form-table">

				<tbody>

					<!-- ========== 1. 基础设置 ========== -->
					<tr><th class="subtitle"><h2 id="section-basic"><?php _e("基础设置", 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-theme-color"><?php _e("主题色", 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e("主题颜色", 'argon');?></label></th>

						<td>

							<input type="color" class="regular-text" name="argon_theme_color" value="<?php echo get_option('argon_theme_color') == "" ? "#5e72e4" : get_option('argon_theme_color'); ?>" style="height:40px;width: 80px;cursor: pointer;"/>

							<input type="text" readonly name="argon_theme_color_hex_preview" value="<?php echo get_option('argon_theme_color') == "" ? "#5e72e4" : get_option('argon_theme_color'); ?>" style="height: 40px;width: 80px;vertical-align: bottom;background: #fff;cursor: pointer;" onclick="$('input[name=\'argon_theme_color\']').click()"/></p>

							<p class="description"><div style="margin-top: 15px;"><?php _e("选择预置颜色 或", 'argon');?> <span onclick="$('input[name=\'argon_theme_color\']').click()" style="text-decoration: underline;cursor: pointer;"><?php _e("自定义色值", 'argon');?></span>

								<br/><br/><?php _e("预置颜色：", 'argon');?></div>

								<div class="themecolor-preview-container">

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#5e72e4;" color="#5e72e4"></div><div class="themecolor-name">Argon (<?php _e("默认", 'argon');?>)</div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#fa7298;" color="#fa7298"></div><div class="themecolor-name"><?php _e("粉", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#009688;" color="#009688"></div><div class="themecolor-name"><?php _e("水鸭青", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#607d8b;" color="#607d8b"></div><div class="themecolor-name"><?php _e("蓝灰", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#2196f3;" color="#2196f3"></div><div class="themecolor-name"><?php _e("天蓝", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#3f51b5;" color="#3f51b5"></div><div class="themecolor-name"><?php _e("靛蓝", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#ff9700;" color="#ff9700"></div><div class="themecolor-name"><?php _e("橙", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#109d58;" color="#109d58"></div><div class="themecolor-name"><?php _e("绿", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#dc4437;" color="#dc4437"></div><div class="themecolor-name"><?php _e("红", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#673bb7;" color="#673bb7"></div><div class="themecolor-name"><?php _e("紫", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#212121;" color="#212121"></div><div class="themecolor-name"><?php _e("黑", 'argon');?></div></div>

									<div class="themecolor-preview-box"><div class="themecolor-preview" style="background:#795547;" color="#795547"></div><div class="themecolor-name"><?php _e("棕", 'argon');?></div></div>

								</div>

								<br/><?php _e('主题色与 "Banner 渐变背景样式" 选项搭配使用效果更佳', 'argon');?>

								<script>

									$("input[name='argon_theme_color']").on("change" , function(){

										$("input[name='argon_theme_color_hex_preview']").val($("input[name='argon_theme_color']").val());

									});

									$(".themecolor-preview").on("click" , function(){

										$("input[name='argon_theme_color']").val($(this).attr("color"));

										$("input[name='argon_theme_color']").trigger("change");

									});

								</script>

								<style>

									.themecolor-name{width: 100px;text-align: center;}

									.themecolor-preview{width: 50px;height: 50px;margin: 20px 25px 5px 25px;line-height: 50px;color: #fff;margin-right: 0px;font-size: 15px;text-align: center;display: inline-block;border-radius: 50px;transition: all .3s ease;cursor: pointer;}

									.themecolor-preview-box{width: max-content;width: -moz-max-content;display: inline-block;}

									div.themecolor-preview:hover{transform: scale(1.1);}

									div.themecolor-preview:active{transform: scale(1.2);}

									.themecolor-preview-container{

										max-width: calc(100% - 180px);

									}

									@media screen and (max-width:960px){

										.themecolor-preview-container{

											max-width: unset;

										}

									}

								</style>



								<?php $argon_show_customize_theme_color_picker = get_option('argon_show_customize_theme_color_picker');?>

								<div style="margin-top: 15px;">

									<label>

										<input type="checkbox" name="argon_show_customize_theme_color_picker" value="true" <?php if ($argon_show_customize_theme_color_picker!='false'){echo 'checked';}?>/> <?php _e('允许用户自定义主题色（位于博客浮动操作栏设置菜单中）', 'argon');?>

									</label>

								</div>

							</p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('沉浸式主题色', 'argon');?></label></th>

						<td>

							<select name="argon_enable_immersion_color">

								<?php $argon_enable_immersion_color = get_option('argon_enable_immersion_color', 'false'); ?>

								<option value="true" <?php if ($argon_enable_immersion_color=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

								<option value="false" <?php if ($argon_enable_immersion_color=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，主题色将会全局沉浸。<br/>页面背景、卡片及页面上的其它元素会变为沉浸式主题色（气氛色）。类似 Material You。', 'argon');?><br/></p>

							<div style="display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;margin-top:15px;">

								<div class="immersion-color-example" style="background: #f4f5f7;"><div class="immersion-color-example-card" style="background: #fff;"></div></div>

								<div class="immersion-color-example-arrow"><span class="dashicons dashicons-arrow-right-alt"></span></div>

								<div class="immersion-color-example" style="background: #e8ebfb;"><div class="immersion-color-example-card" style="background: #f2f4fd;"></div></div>

							<div>

							<style>.immersion-color-example {width: 250px;height: 150px;border-radius: 4px;display: inline-block;position: relative;box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);}.immersion-color-example-arrow {margin-left: 20px;margin-right: 20px;color: #646970;}.immersion-color-example-card {position: absolute;left: 40px;right: 40px;top: 35px;bottom: 35px;background: #fff;box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);}</style>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-dark-mode"><?php _e('夜间模式', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('夜间模式切换方案', 'argon');?></label></th>

						<td>

							<select name="argon_darkmode_autoswitch">

								<?php $argon_darkmode_autoswitch = get_option('argon_darkmode_autoswitch'); ?>

								<option value="false" <?php if ($argon_darkmode_autoswitch=='false'){echo 'selected';} ?>><?php _e('默认使用日间模式', 'argon');?></option>

								<option value="alwayson" <?php if ($argon_darkmode_autoswitch=='alwayson'){echo 'selected';} ?>><?php _e('默认使用夜间模式', 'argon');?></option>

								<option value="system" <?php if ($argon_darkmode_autoswitch=='system'){echo 'selected';} ?>><?php _e('跟随系统夜间模式', 'argon');?></option>

								<option value="time" <?php if ($argon_darkmode_autoswitch=='time'){echo 'selected';} ?>><?php _e('根据时间切换夜间模式 (22:00 ~ 7:00)', 'argon');?></option>

							</select>

							<p class="description"><?php _e('Argon 主题会根据这里的选项来决定是否默认使用夜间模式。', 'argon');?><br/><?php _e('用户也可以手动切换夜间模式，用户的设置将保留到标签页关闭为止。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('夜间模式颜色方案', 'argon');?></label></th>

						<td>

							<select name="argon_enable_amoled_dark">

								<?php $argon_enable_amoled_dark = get_option('argon_enable_amoled_dark'); ?>

								<option value="false" <?php if ($argon_enable_amoled_dark=='false'){echo 'selected';} ?>><?php _e('灰黑', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_amoled_dark=='true'){echo 'selected';} ?>><?php _e('暗黑 (AMOLED Black)', 'argon');?></option>

							</select>

							<p class="description"><?php _e('夜间模式默认的配色方案。', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 2. 外观样式 ========== -->
					<tr><th class="subtitle"><h2 id="section-appearance"><?php _e('外观样式', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-card-style"><?php _e('卡片样式', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('卡片圆角大小', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_card_radius" min="0" max="30" step="0.5" value="<?php echo (get_option('argon_card_radius') == '' ? '4' : get_option('argon_card_radius')); ?>"/>	px

							<p class="description"><?php _e('卡片的圆角大小，默认为', 'argon');?> <code>4px</code><?php _e('。建议设置为', 'argon');?> <code>2px</code> - <code>15px</code></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('卡片阴影', 'argon');?></label></th>

						<td>

							<div class="radio-h">

								<?php $argon_card_shadow = (get_option('argon_card_shadow') == '' ? 'default' : get_option('argon_card_shadow')); ?>

								<label>

									<input name="argon_card_shadow" type="radio" value="default" <?php if ($argon_card_shadow=='default'){echo 'checked';} ?>>

									<?php _e('浅阴影', 'argon');?>

								</label>

								<label>

									<input name="argon_card_shadow" type="radio" value="big" <?php if ($argon_card_shadow=='big'){echo 'checked';} ?>>

									<?php _e('深阴影', 'argon');?>

								</label>

							</div>

							<p class="description"><?php _e('卡片默认阴影大小。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-font"><?php _e('字体', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('默认字体', 'argon');?></label></th>

						<td>

							<div class="radio-h">

								<?php $argon_font = (get_option('argon_font') == '' ? 'sans-serif' : get_option('argon_font')); ?>

								<label>

									<input name="argon_font" type="radio" value="sans-serif" <?php if ($argon_font=='sans-serif'){echo 'checked';} ?>>

									Sans Serif

								</label>

								<label>

									<input name="argon_font" type="radio" value="serif" <?php if ($argon_font=='serif'){echo 'checked';} ?>>

									Serif

								</label>

							</div>

							<p class="description"><?php _e('默认使用无衬线字体/衬线字体。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-animation"><?php _e('动画效果', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('平滑滚动', 'argon');?></label></th>

						<td>

							<select name="argon_enable_smoothscroll_type">

								<?php $enable_smoothscroll_type = get_option('argon_enable_smoothscroll_type'); ?>

								<option value="1" <?php if ($enable_smoothscroll_type=='1'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 1 (平滑) (推荐)', 'argon');?></option>

								<option value="1_pulse" <?php if ($enable_smoothscroll_type=='1_pulse'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 1 (脉冲式滚动) (仿 Edge) (推荐)', 'argon');?></option>

								<option value="2" <?php if ($enable_smoothscroll_type=='2'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 2 (较稳)', 'argon');?></option>

								<option value="3" <?php if ($enable_smoothscroll_type=='3'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 3', 'argon');?></option>

								<option value="disabled" <?php if ($enable_smoothscroll_type=='disabled'){echo 'selected';} ?>><?php _e('不使用平滑滚动', 'argon');?></option>

							</select>

							<p class="description"><?php _e('能增强浏览体验，但可能出现一些小问题，如果有问题请切换方案或关闭平滑滚动', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('进入文章动画', 'argon');?></label></th>

						<td>

							<select name="argon_enable_into_article_animation">

								<?php $argon_enable_into_article_animation = get_option('argon_enable_into_article_animation'); ?>

								<option value="false" <?php if ($argon_enable_into_article_animation=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_into_article_animation=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('从首页或分类目录进入文章时，使用平滑过渡（可能影响加载文章时的性能）', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('Pjax 滚动动画', 'argon');?></label></th>

						<td>

							<select name="argon_disable_pjax_animation">

								<?php $argon_disable_pjax_animation = get_option('argon_disable_pjax_animation'); ?>

								<option value="false" <?php if ($argon_disable_pjax_animation=='false'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="true" <?php if ($argon_disable_pjax_animation=='true'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('Pjax 替换页面内容后会平滑滚动到页面顶部，如果你不喜欢，可以禁用这个选项', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 3. 页面布局 ========== -->
					<tr><th class="subtitle"><h2 id="section-layout"><?php _e('页面布局', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-page-layout"><?php _e('整体布局', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('页面布局', 'argon');?></label></th>

						<td>

							<div class="radio-with-img">

								<?php $argon_page_layout = get_option('argon_page_layout', 'double'); ?>

								<div class="radio-img">

									<svg width="250" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080"><rect width="1920" height="1080" style="fill:#e6e6e6"/><g style="opacity:0.5"><rect width="1920" height="381" style="fill:#5e72e4"/></g><rect x="388.5" y="256" width="258" height="179" style="fill:#5e72e4"/><rect x="388.5" y="470" width="258" height="485" style="fill:#fff"/><rect x="689.5" y="256.5" width="842" height="250" style="fill:#fff"/><rect x="689.5" y="536.5" width="842" height="250" style="fill:#fff"/><rect x="689.5" y="817" width="842" height="250" style="fill:#fff"/></svg>

								</div>

								<label><input name="argon_page_layout" type="radio" value="double" <?php if ($argon_page_layout=='double'){echo 'checked';} ?>> <?php _e('双栏', 'argon');?></label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="250" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080"><rect width="1920" height="1080" style="fill:#e6e6e6"/><g style="opacity:0.5"><rect width="1920" height="381" style="fill:#5e72e4"/></g><rect x="428.25" y="256.5" width="1063.5" height="250" style="fill:#fff"/><rect x="428.25" y="536.5" width="1063.5" height="250" style="fill:#fff"/><rect x="428.25" y="817" width="1063.5" height="250" style="fill:#fff"/></svg>

								</div>

								<label><input name="argon_page_layout" type="radio" value="single" <?php if ($argon_page_layout=='single'){echo 'checked';} ?>> <?php _e('单栏', 'argon');?></label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="250" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080"><rect width="1920" height="1080" style="fill:#e6e6e6"/><g style="opacity:0.5"><rect width="1920" height="381" style="fill:#5e72e4"/></g><rect x="237.5" y="256" width="258" height="179" style="fill:#5e72e4"/><rect x="237.5" y="470" width="258" height="485" style="fill:#fff"/><rect x="538.5" y="256.5" width="842" height="250" style="fill:#fff"/><rect x="538.5" y="536.5" width="842" height="250" style="fill:#fff"/><rect x="538.5" y="817" width="842" height="250" style="fill:#fff"/><rect x="1424" y="256" width="258" height="811" style="fill:#fff"/></svg>

								</div>

								<label><input name="argon_page_layout" type="radio" value="triple" <?php if ($argon_page_layout=='triple'){echo 'checked';} ?>> <?php _e('三栏', 'argon');?></label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="250" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080"><rect width="1920" height="1080" style="fill:#e6e6e6"/><g style="opacity:0.5"><rect width="1920" height="381" style="fill:#5e72e4"/></g><rect x="1273.5" y="256" width="258" height="179" style="fill:#5e72e4"/><rect x="1273.5" y="470" width="258" height="485" style="fill:#fff"/><rect x="388.5" y="256.5" width="842" height="250" style="fill:#fff"/><rect x="388.5" y="536.5" width="842" height="250" style="fill:#fff"/><rect x="388.5" y="817" width="842" height="250" style="fill:#fff"/></svg>

								</div>

								<label><input name="argon_page_layout" type="radio" value="double-reverse" <?php if ($argon_page_layout=='double-reverse'){echo 'checked';} ?>> <?php _e('双栏(反转)', 'argon');?></label>

							</div>

							<p class="description" style="margin-top: 15px;"><?php _e('使用单栏时，关于左侧栏的设置将失效。', 'argon');?><br/><?php _e('使用三栏时，请前往 "外观-小工具" 设置页面配置右侧栏内容。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('文章列表布局', 'argon');?></label></th>

						<td>

							<div class="radio-with-img">

								<?php $argon_article_list_waterflow = get_option('argon_article_list_waterflow', '1'); ?>

								<div class="radio-img">

									<svg width="200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1880.72 1340.71"><rect width="1880.72" height="1340.71" style="fill:#f7f8f8"/><rect x="46.34" y="46.48" width="1785.73" height="412.09" style="fill:#abb7ff"/><rect x="46.34" y="496.66" width="1785.73" height="326.05" style="fill:#abb7ff"/><rect x="46.34" y="860.8" width="1785.73" height="350.87" style="fill:#abb7ff"/><rect x="46.34" y="1249.76" width="1785.73" height="90.94" style="fill:#abb7ff"/></svg>

								</div>

								<label><input name="argon_article_list_waterflow" type="radio" value="1" <?php if ($argon_article_list_waterflow=='1'){echo 'checked';} ?>> <?php _e('单列', 'argon');?></label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1880.72 1340.71"><rect width="1880.72" height="1340.71" style="fill:#f7f8f8"/><rect x="46.34" y="46.48" width="873.88" height="590.33" style="fill:#abb7ff"/><rect x="961.62" y="46.48" width="873.88" height="390.85" style="fill:#abb7ff"/><rect x="961.62" y="480.65" width="873.88" height="492.96" style="fill:#abb7ff"/><rect x="46.34" y="681.35" width="873.88" height="426.32" style="fill:#abb7ff"/><rect x="961.62" y="1016.92" width="873.88" height="323.79" style="fill:#abb7ff"/><rect x="46.34" y="1152.22" width="873.88" height="188.49" style="fill:#abb7ff"/></svg>

								</div>

								<label><input name="argon_article_list_waterflow" type="radio" value="2" <?php if ($argon_article_list_waterflow=='2'){echo 'checked';} ?>> <?php _e('瀑布流 (2 列)', 'argon');?></label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1880.72 1340.71"><rect width="1880.72" height="1340.71" style="fill:#f7f8f8"/><rect x="46.34" y="46.48" width="568.6" height="531.27" style="fill:#abb7ff"/><rect x="656.62" y="46.48" width="568.6" height="400.51" style="fill:#abb7ff"/><rect x="1266.9" y="46.48" width="568.6" height="604.09" style="fill:#abb7ff"/><rect x="656.62" y="485.07" width="568.6" height="428.67" style="fill:#abb7ff"/><rect x="46.34" y="615.82" width="568.6" height="407.16" style="fill:#abb7ff"/><rect x="656.62" y="951.83" width="568.6" height="388.87" style="fill:#abb7ff"/><rect x="1266.9" y="695.24" width="568.6" height="400.53" style="fill:#abb7ff"/><rect x="1266.9" y="1140.44" width="568.6" height="200.26" style="fill:#abb7ff"/><rect x="46.34" y="1061.06" width="568.6" height="279.64" style="fill:#abb7ff"/></svg>

								</div>

								<label><input name="argon_article_list_waterflow" type="radio" value="3" <?php if ($argon_article_list_waterflow=='3'){echo 'checked';} ?>> <?php _e('瀑布流 (3 列)', 'argon');?></label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="200" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1930.85 1340.71"><defs><clipPath id="a" transform="translate(18.64)"><rect x="-385.62" y="718.3" width="2290.76" height="1028.76" transform="translate(599.83 -206.47) rotate(25.31)" style="fill:none"/></clipPath><clipPath id="b" transform="translate(18.64)"><rect x="2.1" y="252.4" width="1878.62" height="991.45" style="fill:none"/></clipPath></defs><rect x="18.64" width="1880.72" height="1340.71" style="fill:#f7f8f8"/><rect x="64.98" y="46.48" width="568.6" height="531.27" style="fill:#abb7ff"/><rect x="675.26" y="46.48" width="568.6" height="400.51" style="fill:#abb7ff"/><rect x="1285.55" y="46.48" width="568.6" height="604.09" style="fill:#abb7ff"/><rect x="675.26" y="485.07" width="568.6" height="428.67" style="fill:#abb7ff"/><rect x="64.98" y="615.82" width="568.6" height="407.16" style="fill:#abb7ff"/><rect x="675.26" y="951.83" width="568.6" height="388.87" style="fill:#abb7ff"/><rect x="1285.55" y="695.24" width="568.6" height="400.53" style="fill:#abb7ff"/><rect x="1285.55" y="1140.44" width="568.6" height="200.26" style="fill:#abb7ff"/><rect x="64.98" y="1061.06" width="568.6" height="279.64" style="fill:#abb7ff"/><g style="clip-path:url(#a)"><rect x="18.64" width="1880.72" height="1340.71" style="fill:#f7f8f8"/><rect x="64.98" y="46.48" width="873.88" height="590.33" style="fill:#abb7ff"/><rect x="980.27" y="46.48" width="873.88" height="390.85" style="fill:#abb7ff"/><rect x="980.27" y="480.65" width="873.88" height="492.96" style="fill:#abb7ff"/><rect x="64.98" y="681.35" width="873.88" height="426.32" style="fill:#abb7ff"/><rect x="980.27" y="1016.92" width="873.88" height="323.79" style="fill:#abb7ff"/><rect x="64.98" y="1152.22" width="873.88" height="188.49" style="fill:#abb7ff"/></g><g style="clip-path:url(#b)"><line x1="18.64" y1="304.46" x2="1912.21" y2="1199.81" style="fill:none;stroke:#f7f8f8;stroke-linecap:square;stroke-miterlimit:10;stroke-width:28px"/></g></svg>

								</div>

								<label><input name="argon_article_list_waterflow" type="radio" value="2and3" <?php if ($argon_article_list_waterflow=='2and3'){echo 'checked';} ?>> <?php _e('瀑布流 (列数自适应)', 'argon');?></label>

							</div>

							<p class="description" style="margin-top: 15px;"><?php _e('列数自适应的瀑布流会根据可视区宽度自动调整瀑布流列数。', 'argon');?><br/><?php _e('建议只有使用单栏页面布局时才开启 3 列瀑布流。', 'argon');?><br/><?php _e('所有瀑布流布局都会在屏幕宽度过小时变为单列布局。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('文章列表卡片布局', 'argon');?></label></th>

						<td>

							<div class="radio-with-img">

								<?php $argon_article_list_layout = get_option('argon_article_list_layout', '1'); ?>

								<div class="radio-img">

									<svg width="250" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1921 871"><rect x="0.5" y="0.5" width="1920" height="870" style="fill:#f7f8f8;stroke:#231815;stroke-miterlimit:10"/><rect x="0.5" y="0.5" width="1920" height="538.05" style="fill:#abb7ff"/><rect x="48.5" y="613.55" width="1806" height="35" rx="4" style="fill:#efefef"/><rect x="48.5" y="663.05" width="1806" height="35" rx="4" style="fill:#efefef"/><rect x="48.5" y="712.55" width="1806" height="35" rx="4" style="fill:#efefef"/><rect x="48.5" y="792.52" width="116.97" height="38.07" rx="4" style="fill:#dcdddd"/><rect x="178.95" y="792.52" width="97.38" height="38.07" rx="4" style="fill:#dcdddd"/><rect x="288.4" y="792.52" width="125.79" height="38.07" rx="4" style="fill:#dcdddd"/><g style="opacity:0.66"><rect x="432.78" y="320.9" width="1055.43" height="55.93" rx="4" style="fill:#f7f8f8"/></g><g style="opacity:0.31"><rect x="734.76" y="411.73" width="451.48" height="25.08" rx="4" style="fill:#fff"/></g><g style="opacity:0.31"><rect x="734.76" y="453.24" width="451.48" height="25.08" rx="4" style="fill:#fff"/></g></svg>

								</div>

								<label><input name="argon_article_list_layout" type="radio" value="1" <?php if ($argon_article_list_layout=='1'){echo 'checked';} ?>> <?php _e('布局', 'argon');?> 1</label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="250" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 870"><rect width="1920" height="870" style="fill:#f7f8f8;stroke: #231815;stroke-miterlimit: 10;"/><rect width="630.03" height="870" style="fill:#abb7ff"/><rect x="689.57" y="174.16" width="1144.6" height="35" rx="4" style="fill:#efefef"/><rect x="689.57" y="238.66" width="1144.6" height="35" rx="4" style="fill:#efefef"/><rect x="689.57" y="303.16" width="1144.6" height="35" rx="4" style="fill:#efefef"/><rect x="689.57" y="792.02" width="116.97" height="38.07" rx="4" style="fill:#dcdddd"/><rect x="820.02" y="792.02" width="97.38" height="38.07" rx="4" style="fill:#dcdddd"/><rect x="929.47" y="792.02" width="125.79" height="38.07" rx="4" style="fill:#dcdddd"/><g style="opacity:0.23"><rect x="689.57" y="52.26" width="1055.43" height="55.93" rx="4" style="fill:#5e72e4"/></g><rect x="689.57" y="677.09" width="451.48" height="25.08" rx="4" style="fill:#efefef"/><rect x="689.57" y="718.6" width="451.48" height="25.08" rx="4" style="fill:#efefef"/><rect x="689.57" y="363.63" width="1144.6" height="35" rx="4" style="fill:#efefef"/><rect x="689.57" y="426.13" width="1144.6" height="35" rx="4" style="fill:#efefef"/><rect x="689.57" y="492.63" width="1144.6" height="35" rx="4" style="fill:#efefef"/></svg>

								</div>

								<label><input name="argon_article_list_layout" type="radio" value="2" <?php if ($argon_article_list_layout=='2'){echo 'checked';} ?>> <?php _e('布局', 'argon');?> 2</label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="250" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1921 871"><rect x="0.5" y="0.5" width="1920" height="870" style="fill:#f7f8f8;stroke:#231815;stroke-miterlimit:10"/><rect x="0.5" y="0.5" width="1920" height="363.36" style="fill:#abb7ff"/><rect x="48.5" y="613.55" width="1806" height="35" rx="4" style="fill:#efefef"/><rect x="48.5" y="663.05" width="1806" height="35" rx="4" style="fill:#efefef"/><rect x="48.5" y="712.55" width="1806" height="35" rx="4" style="fill:#efefef"/><rect x="48.5" y="792.52" width="116.97" height="38.07" rx="4" style="fill:#dcdddd"/><rect x="178.95" y="792.52" width="97.38" height="38.07" rx="4" style="fill:#dcdddd"/><rect x="288.4" y="792.52" width="125.79" height="38.07" rx="4" style="fill:#dcdddd"/><g style="opacity:0.23"><rect x="48.5" y="410.53" width="1055.43" height="55.93" rx="4" style="fill:#5e72e4"/></g><rect x="48.2" y="500.22" width="451.48" height="25.08" rx="4" style="fill:#efefef"/><rect x="48.2" y="541.72" width="451.48" height="25.08" rx="4" style="fill:#efefef"/></svg>

								</div>

								<label><input name="argon_article_list_layout" type="radio" value="3" <?php if ($argon_article_list_layout=='3'){echo 'checked';} ?>> <?php _e('布局', 'argon');?> 3</label>

							</div>
							<p class="description"><?php _e('此设置仅对桌面端生效', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('移动端文章列表卡片布局', 'argon');?></label></th>

						<td>

							<div class="radio-with-img">

								<?php $argon_article_list_layout_mobile = get_option('argon_article_list_layout_mobile', '1'); ?>

								<div class="radio-img">

									<svg width="150" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 700"><rect width="400" height="700" rx="20" style="fill:#f7f8f8;stroke:#231815;stroke-miterlimit:10"/><rect x="20" y="20" width="360" height="280" rx="8" style="fill:#abb7ff"/><rect x="20" y="320" width="360" height="25" rx="4" style="fill:#efefef"/><rect x="20" y="360" width="360" height="25" rx="4" style="fill:#efefef"/><rect x="20" y="400" width="360" height="25" rx="4" style="fill:#efefef"/><rect x="20" y="450" width="200" height="20" rx="4" style="fill:#dcdddd"/></svg>

								</div>

								<label><input name="argon_article_list_layout_mobile" type="radio" value="1" <?php if ($argon_article_list_layout_mobile=='1'){echo 'checked';} ?>> <?php _e('布局', 'argon');?> 1 (<?php _e('大图', 'argon');?>)</label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="150" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 700"><rect width="400" height="700" rx="20" style="fill:#f7f8f8;stroke:#231815;stroke-miterlimit:10"/><rect x="20" y="20" width="360" height="180" rx="8" style="fill:#abb7ff"/><rect x="20" y="220" width="360" height="20" rx="4" style="fill:#efefef"/><rect x="20" y="250" width="360" height="20" rx="4" style="fill:#efefef"/><rect x="20" y="280" width="360" height="20" rx="4" style="fill:#efefef"/><rect x="20" y="320" width="150" height="16" rx="4" style="fill:#dcdddd"/></svg>

								</div>

								<label><input name="argon_article_list_layout_mobile" type="radio" value="2" <?php if ($argon_article_list_layout_mobile=='2'){echo 'checked';} ?>> <?php _e('布局', 'argon');?> 2 (<?php _e('紧凑', 'argon');?>)</label>

							</div>

							<div class="radio-with-img">

								<div class="radio-img">

									<svg width="150" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 700"><rect width="400" height="700" rx="20" style="fill:#f7f8f8;stroke:#231815;stroke-miterlimit:10"/><rect x="20" y="20" width="120" height="100" rx="8" style="fill:#abb7ff"/><rect x="160" y="30" width="220" height="18" rx="4" style="fill:#efefef"/><rect x="160" y="60" width="220" height="14" rx="4" style="fill:#efefef"/><rect x="160" y="85" width="150" height="12" rx="4" style="fill:#dcdddd"/></svg>

								</div>

								<label><input name="argon_article_list_layout_mobile" type="radio" value="3" <?php if ($argon_article_list_layout_mobile=='3'){echo 'checked';} ?>> <?php _e('布局', 'argon');?> 3 (<?php _e('横向', 'argon');?>)</label>

							</div>

							<p class="description"><?php _e('此设置仅对移动端生效，与桌面端布局独立', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 4. 顶栏设置 ========== -->
					<tr><th class="subtitle"><h2 id="section-toolbar"><?php _e('顶栏设置', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-toolbar-basic"><?php _e('基本设置', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('顶栏显示状态', 'argon');?></label></th>

						<td>

							<select name="argon_enable_headroom">

								<?php $argon_enable_headroom = get_option('argon_enable_headroom'); ?>

								<option value="false" <?php if ($argon_enable_headroom=='false'){echo 'selected';} ?>><?php _e('始终固定悬浮', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_headroom=='true'){echo 'selected';} ?>><?php _e('滚动时自动折叠', 'argon');?></option>

								<option value="absolute" <?php if ($argon_enable_headroom=='absolute'){echo 'selected';} ?>><?php _e('不固定', 'argon');?></option>

							</select>

							<p class="description"><?php _e('始终固定悬浮: 永远固定悬浮在页面最上方', 'argon');?><br/><?php _e('滚动时自动折叠: 在页面向下滚动时隐藏顶栏，向上滚动时显示顶栏', 'argon');?><br/><?php _e('不固定: 只有在滚动到页面最顶端时才显示顶栏', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-toolbar-title"><?php _e('顶栏标题', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('顶栏标题', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_toolbar_title" value="<?php echo get_option('argon_toolbar_title'); ?>"/></p>

							<p class="description"><?php _e('留空则显示博客名称，输入 <code>--hidden--</code> 可以隐藏标题', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-toolbar-icon"><?php _e('顶栏图标', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('图标地址', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_toolbar_icon" value="<?php echo get_option('argon_toolbar_icon'); ?>"/>

							<p class="description"><?php _e('图片地址，留空则不显示', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('图标链接', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_toolbar_icon_link" value="<?php echo get_option('argon_toolbar_icon_link'); ?>"/>

							<p class="description"><?php _e('点击图标后会跳转到的链接，留空则不跳转', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-toolbar-appearance"><?php _e('顶栏外观', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('顶栏毛玻璃效果', 'argon');?></label></th>

						<td>

							<select name="argon_toolbar_blur">

								<?php $argon_toolbar_blur = get_option('argon_toolbar_blur'); ?>

								<option value="false" <?php if ($argon_toolbar_blur=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="true" <?php if ($argon_toolbar_blur=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启会带来微小的性能损失。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('顶栏样式', 'argon');?></label></th>

						<td>

							<select name="argon_toolbar_style">

								<?php $argon_toolbar_style = get_option('argon_toolbar_style', 'default'); ?>

								<option value="default" <?php if ($argon_toolbar_style=='default'){echo 'selected';} ?>><?php _e('默认', 'argon');?></option>

								<option value="gradient" <?php if ($argon_toolbar_style=='gradient'){echo 'selected';} ?>><?php _e('渐变背景', 'argon');?></option>

								<option value="glass" <?php if ($argon_toolbar_style=='glass'){echo 'selected';} ?>><?php _e('玻璃态', 'argon');?></option>

							</select>

							<p class="description"><?php _e('选择顶栏的视觉风格。渐变背景会使用主题色渐变，玻璃态会呈现半透明毛玻璃效果。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('顶栏阴影', 'argon');?></label></th>

						<td>

							<select name="argon_toolbar_shadow">

								<?php $argon_toolbar_shadow = get_option('argon_toolbar_shadow', 'false'); ?>

								<option value="false" <?php if ($argon_toolbar_shadow=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="true" <?php if ($argon_toolbar_shadow=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<p class="description"><?php _e('为顶栏添加底部阴影效果。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('顶栏底部边框', 'argon');?></label></th>

						<td>

							<select name="argon_toolbar_border">

								<?php $argon_toolbar_border = get_option('argon_toolbar_border', 'false'); ?>

								<option value="false" <?php if ($argon_toolbar_border=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="true" <?php if ($argon_toolbar_border=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<p class="description"><?php _e('为顶栏添加底部细边框。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('顶栏紧凑模式', 'argon');?></label></th>

						<td>

							<select name="argon_toolbar_compact">

								<?php $argon_toolbar_compact = get_option('argon_toolbar_compact', 'false'); ?>

								<option value="false" <?php if ($argon_toolbar_compact=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="true" <?php if ($argon_toolbar_compact=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<p class="description"><?php _e('减小顶栏的高度，使页面更加紧凑。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('顶栏标题居中', 'argon');?></label></th>

						<td>

							<select name="argon_toolbar_center">

								<?php $argon_toolbar_center = get_option('argon_toolbar_center', 'false'); ?>

								<option value="false" <?php if ($argon_toolbar_center=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="true" <?php if ($argon_toolbar_center=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<p class="description"><?php _e('将顶栏标题/图标居中显示（桌面端）。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-toolbar-links"><?php _e('自定义链接', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('顶栏左侧自定义链接', 'argon');?></label></th>

						<td>

							<textarea class="large-text" name="argon_toolbar_links_left" rows="3" placeholder="<?php _e('文字|链接|图标(可选)', 'argon'); ?>"><?php echo esc_textarea(get_option('argon_toolbar_links_left', '')); ?></textarea>

							<p class="description"><?php _e('每行一个，格式：文字|链接地址|图标类名。例如：GitHub|https://github.com|fa fa-github', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('顶栏右侧自定义链接', 'argon');?></label></th>

						<td>

							<textarea class="large-text" name="argon_toolbar_links_right" rows="3" placeholder="<?php _e('文字|链接|图标(可选)', 'argon'); ?>"><?php echo esc_textarea(get_option('argon_toolbar_links_right', '')); ?></textarea>

							<p class="description"><?php _e('每行一个，显示在搜索框左侧', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 5. Banner 设置 ========== -->
					<tr><th class="subtitle"><h2 id="section-banner"><?php _e('Banner 设置', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-banner-content"><?php _e('Banner 内容', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('Banner 标题', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_banner_title" value="<?php echo get_option('argon_banner_title'); ?>"/>

							<p class="description"><?php _e('留空则显示博客名称', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('Banner 副标题', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_banner_subtitle" value="<?php echo get_option('argon_banner_subtitle'); ?>"/>

							<p class="description"><?php _e('显示在 Banner 标题下，留空则不显示', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-banner-appearance"><?php _e('Banner 外观', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('Banner 显示状态', 'argon');?></label></th>

						<td>

							<select name="argon_banner_size">

							<?php $argon_banner_size = get_option('argon_banner_size', 'full'); ?>

								<option value="full" <?php if ($argon_banner_size=='full'){echo 'selected';} ?>><?php _e('完整', 'argon');?></option>

								<option value="mini" <?php if ($argon_banner_size=='mini'){echo 'selected';} ?>><?php _e('迷你', 'argon');?></option>

								<option value="fullscreen" <?php if ($argon_banner_size=='fullscreen'){echo 'selected';} ?>><?php _e('全屏', 'argon');?></option>

								<option value="hide" <?php if ($argon_banner_size=='hide'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>

							</select>

							<p class="description"><?php _e('完整: Banner 高度占用半屏', 'argon');?><br/><?php _e('迷你: 减小 Banner 的内边距', 'argon');?><br/><?php _e('全屏: Banner 占用全屏作为封面（仅在首页生效）', 'argon');?><br/><?php _e('隐藏: 完全隐藏 Banner', 'argon');?><br/></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('Banner 透明化', 'argon');?></label></th>

						<td>

							<select name="argon_page_background_banner_style">

								<?php $argon_page_background_banner_style = get_option('argon_page_background_banner_style'); ?>

								<option value="false" <?php if ($argon_page_background_banner_style=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="transparent" <?php if ($argon_page_background_banner_style=='transparent' || ($argon_page_background_banner_style!='' && $argon_page_background_banner_style!='false')){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<div style="margin-top: 15px;margin-bottom: 15px;">

								<label>

									<?php $argon_show_toolbar_mask = get_option('argon_show_toolbar_mask');?>

									<input type="checkbox" name="argon_show_toolbar_mask" value="true" <?php if ($argon_show_toolbar_mask=='true'){echo 'checked';}?>/>	<?php _e('在顶栏添加浅色遮罩，Banner 标题添加阴影（当背景过亮影响文字阅读时勾选）', 'argon');?>

								</label>

							</div>

							<p class="description"><?php _e('Banner 透明化可以使博客背景沉浸。建议在设置背景时开启此选项。该选项仅会在设置页面背景时生效。', 'argon');?><br/><?php _e('开启后，Banner 背景图和渐变背景选项将失效。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('Banner 背景图 (地址)', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_banner_background_url" value="<?php echo get_option('argon_banner_background_url'); ?>"/>

							<p class="description"><?php _e('需带上 http(s) ，留空则显示默认背景', 'argon');?><br/><?php _e('输入', 'argon');?> <code>--bing--</code> <?php _e('调用必应每日一图', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('Banner 渐变背景样式', 'argon');?></label></th>

						<td>

							<select name="argon_banner_background_color_type">

								<?php $color_type = get_option('argon_banner_background_color_type'); ?>

								<option value="shape-primary" <?php if ($color_type=='shape-primary'){echo 'selected';} ?>><?php _e('样式', 'argon');?> 1</option>

								<option value="shape-default" <?php if ($color_type=='shape-default'){echo 'selected';} ?>><?php _e('样式', 'argon');?> 2</option>

								<option value="shape-dark" <?php if ($color_type=='shape-dark'){echo 'selected';} ?>><?php _e('样式', 'argon');?> 3</option>

								<option value="bg-gradient-success" <?php if ($color_type=='bg-gradient-success'){echo 'selected';} ?>><?php _e('样式', 'argon');?> 4</option>

								<option value="bg-gradient-info" <?php if ($color_type=='bg-gradient-info'){echo 'selected';} ?>><?php _e('样式', 'argon');?> 5</option>

								<option value="bg-gradient-warning" <?php if ($color_type=='bg-gradient-warning'){echo 'selected';} ?>><?php _e('样式', 'argon');?> 6</option>

								<option value="bg-gradient-danger" <?php if ($color_type=='bg-gradient-danger'){echo 'selected';} ?>><?php _e('样式', 'argon');?> 7</option>

							</select>

							<?php $hide_shapes = get_option('argon_banner_background_hide_shapes'); ?>

							<label>

								<input type="checkbox" name="argon_banner_background_hide_shapes" value="true" <?php if ($hide_shapes=='true'){echo 'checked';}?>/>	<?php _e('隐藏背景半透明圆', 'argon');?>

							</label>

							<p class="description"><strong><?php _e('如果设置了背景图则不生效', 'argon');?></strong>

								<br/><div style="margin-top: 15px;"><?php _e('样式预览 (推荐选择前三个样式)', 'argon');?></div>

								<div style="margin-top: 10px;">

									<div class="banner-background-color-type-preview" style="background:linear-gradient(150deg,#281483 15%,#8f6ed5 70%,#d782d9 94%);"><?php _e('样式', 'argon');?> 1</div>

									<div class="banner-background-color-type-preview" style="background:linear-gradient(150deg,#7795f8 15%,#6772e5 70%,#555abf 94%);"><?php _e('样式', 'argon');?> 2</div>

									<div class="banner-background-color-type-preview" style="background:linear-gradient(150deg,#32325d 15%,#32325d 70%,#32325d 94%);"><?php _e('样式', 'argon');?> 3</div>

									<div class="banner-background-color-type-preview" style="background:linear-gradient(87deg,#2dce89 0,#2dcecc 100%);"><?php _e('样式', 'argon');?> 4</div>

									<div class="banner-background-color-type-preview" style="background:linear-gradient(87deg,#11cdef 0,#1171ef 100%);"><?php _e('样式', 'argon');?> 5</div>

									<div class="banner-background-color-type-preview" style="background:linear-gradient(87deg,#fb6340 0,#fbb140 100%);"><?php _e('样式', 'argon');?> 6</div>

									<div class="banner-background-color-type-preview" style="background:linear-gradient(87deg,#f5365c 0,#f56036 100%);"><?php _e('样式', 'argon');?> 7</div>

								</div>

								<style>

									div.banner-background-color-type-preview{width:100px;height:50px;line-height:50px;color:#fff;margin-right:0px;font-size:15px;text-align:center;display:inline-block;border-radius:5px;transition:all .3s ease;}

									div.banner-background-color-type-preview:hover{transform: scale(1.2);}

								</style>

							</p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-banner-animation"><?php _e('Banner 动画', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('Banner 标题打字动画', 'argon');?></label></th>

						<td>

							<select name="argon_enable_banner_title_typing_effect">

							<?php $argon_enable_banner_title_typing_effect = get_option('argon_enable_banner_title_typing_effect'); ?>

								<option value="false" <?php if ($argon_enable_banner_title_typing_effect=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_banner_title_typing_effect=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('启用后 Banner 标题会以打字的形式出现。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('Banner 标题打字动画时长', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_banner_typing_effect_interval" min="1" max="10000"  value="<?php echo (get_option('argon_banner_typing_effect_interval') == '' ? '100' : get_option('argon_banner_typing_effect_interval')); ?>"/> <?php _e('ms/字', 'argon');?>

							<p class="description"></p>

						</td>

					</tr>

					<!-- ========== 6. 页面背景 ========== -->
					<tr><th class="subtitle"><h2 id="section-background"><?php _e('页面背景', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-background-image"><?php _e('背景图片', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('页面背景', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_page_background_url" value="<?php echo get_option('argon_page_background_url'); ?>"/>

							<p class="description"><?php _e('页面背景的地址，需带上 http(s)。留空则不设置页面背景。如果设置了背景，推荐开启 Banner 透明化。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('页面背景（夜间模式时）', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_page_background_dark_url" value="<?php echo get_option('argon_page_background_dark_url'); ?>"/>

							<p class="description"><?php _e('夜间模式时页面背景的地址，需带上 http(s)。设置后日间模式和夜间模式会使用不同的背景。留空则跟随日间模式背景。该选项仅在设置了日间模式背景时生效。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-transparency"><?php _e('透明度与毛玻璃', 'argon');?></h3></th></tr>

					<tr>

						<td colspan="2" style="padding: 20px 10px;">

							<div style="display: flex; gap: 40px; align-items: flex-start;">
								<!-- 左侧：设置项 -->
								<div style="flex: 1; max-width: 450px;">
									<!-- 背景不透明度 -->
									<div style="margin-bottom: 20px;">
										<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('背景不透明度', 'argon');?></label>
										<div style="display: flex; align-items: center; gap: 12px;">
											<input type="range" name="argon_page_background_opacity" id="argon_page_bg_opacity_slider" min="0" max="1" step="0.01" value="<?php echo (get_option('argon_page_background_opacity') == '' ? '1' : get_option('argon_page_background_opacity')); ?>" style="flex: 1; cursor: pointer; height: 6px;">
											<span id="argon_page_bg_opacity_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo (get_option('argon_page_background_opacity') == '' ? '1.00' : number_format(floatval(get_option('argon_page_background_opacity')), 2)); ?></span>
										</div>
										<p style="margin: 6px 0 0; font-size: 12px; color: #646970;"><?php _e('控制页面背景图片的不透明度', 'argon');?></p>
									</div>

									<!-- 卡片透明度 -->
									<div style="margin-bottom: 20px;">
										<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('卡片透明度', 'argon');?></label>
										<div style="display: flex; align-items: center; gap: 12px;">
											<input type="range" name="argon_post_background_opacity" id="argon_post_bg_opacity_slider" min="0" max="1" step="0.01" value="<?php echo (get_option('argon_post_background_opacity') == '' ? '0.7' : get_option('argon_post_background_opacity')); ?>" style="flex: 1; cursor: pointer; height: 6px;">
											<span id="argon_post_bg_opacity_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo (get_option('argon_post_background_opacity') == '' ? '0.70' : number_format(floatval(get_option('argon_post_background_opacity')), 2)); ?></span>
										</div>
										<p style="margin: 6px 0 0; font-size: 12px; color: #646970;"><?php _e('控制文章、评论等卡片的背景透明度，推荐 0.7', 'argon');?></p>
									</div>

									<!-- 毛玻璃模糊 -->
									<div style="margin-bottom: 20px;">
										<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('毛玻璃模糊', 'argon');?></label>
										<div style="display: flex; align-items: center; gap: 12px;">
											<input type="range" name="argon_card_blur" min="0" max="30" step="1" value="<?php echo (get_option('argon_card_blur', '20')); ?>" id="argon_card_blur_slider" style="flex: 1; cursor: pointer; height: 6px;">
											<span id="argon_card_blur_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo (get_option('argon_card_blur', '20')); ?>px</span>
										</div>
										<p style="margin: 6px 0 0; font-size: 12px; color: #646970;"><?php _e('为卡片添加毛玻璃模糊效果，推荐 20px', 'argon');?></p>
									</div>

									<!-- 毛玻璃饱和度 -->
									<div style="margin-bottom: 10px;">
										<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('毛玻璃饱和度', 'argon');?></label>
										<div style="display: flex; align-items: center; gap: 12px;">
											<input type="range" name="argon_card_saturate" min="100" max="250" step="10" value="<?php echo (get_option('argon_card_saturate', '180')); ?>" id="argon_card_saturate_slider" style="flex: 1; cursor: pointer; height: 6px;">
											<span id="argon_card_saturate_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo (get_option('argon_card_saturate', '180')); ?>%</span>
										</div>
										<p style="margin: 6px 0 0; font-size: 12px; color: #646970;"><?php _e('毛玻璃背景的色彩饱和度，推荐 180%', 'argon');?></p>
									</div>
								</div>

								<!-- 右侧：实时预览 -->
								<div style="flex-shrink: 0;">
									<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('效果预览', 'argon');?></label>
									<?php $page_bg_url = get_option('argon_page_background_url'); ?>
									<div id="effect_preview_box" style="position: relative; width: 200px; height: 140px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.12); background: #f4f5f7;">
										<?php if (!empty($page_bg_url)) { ?>
										<img id="effect_preview_bg" src="<?php echo esc_url($page_bg_url); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: <?php echo get_option('argon_page_background_opacity', '1'); ?>;">
										<?php } ?>
										<div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
											<div id="blur_preview_card" style="width: 140px; height: 85px; background: rgba(255,255,255,<?php echo get_option('argon_post_background_opacity', '0.7'); ?>); border-radius: 10px; backdrop-filter: blur(<?php echo get_option('argon_card_blur', '20'); ?>px) saturate(<?php echo get_option('argon_card_saturate', '180'); ?>%); -webkit-backdrop-filter: blur(<?php echo get_option('argon_card_blur', '20'); ?>px) saturate(<?php echo get_option('argon_card_saturate', '180'); ?>%); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid rgba(255,255,255,0.4);">
												<span style="font-size: 12px; font-weight: 500; color: #333;"><?php _e('卡片效果', 'argon'); ?></span>
												<span id="blur_preview_info" style="font-size: 10px; color: #666; margin-top: 4px;"></span>
											</div>
										</div>
									</div>
									<p style="margin: 8px 0 0; font-size: 11px; color: #888; text-align: center;">
										<?php if (empty($page_bg_url)) { _e('请先设置页面背景', 'argon'); } else { _e('拖动滑块查看效果', 'argon'); } ?>
									</p>
								</div>
							</div>

							<script>
							(function(){
								var pageBgSlider = document.getElementById('argon_page_bg_opacity_slider');
								var pageBgValue = document.getElementById('argon_page_bg_opacity_value');
								var postBgSlider = document.getElementById('argon_post_bg_opacity_slider');
								var postBgValue = document.getElementById('argon_post_bg_opacity_value');
								var blurSlider = document.getElementById('argon_card_blur_slider');
								var blurValue = document.getElementById('argon_card_blur_value');
								var saturateSlider = document.getElementById('argon_card_saturate_slider');
								var saturateValue = document.getElementById('argon_card_saturate_value');
								
								var previewBg = document.getElementById('effect_preview_bg');
								var previewCard = document.getElementById('blur_preview_card');
								var previewInfo = document.getElementById('blur_preview_info');
								
								function updatePreview() {
									var pageBgOpacity = pageBgSlider ? pageBgSlider.value : 1;
									var postBgOpacity = postBgSlider ? postBgSlider.value : 0.7;
									var blur = blurSlider ? blurSlider.value : 20;
									var saturate = saturateSlider ? saturateSlider.value : 180;
									
									if (previewBg) {
										previewBg.style.opacity = pageBgOpacity;
									}
									if (previewCard) {
										previewCard.style.background = 'rgba(255,255,255,' + postBgOpacity + ')';
										previewCard.style.backdropFilter = 'blur(' + blur + 'px) saturate(' + saturate + '%)';
										previewCard.style.webkitBackdropFilter = 'blur(' + blur + 'px) saturate(' + saturate + '%)';
									}
									if (previewInfo) {
										previewInfo.textContent = '<?php _e("透明度", "argon"); ?> ' + parseFloat(postBgOpacity).toFixed(2) + ' / <?php _e("模糊", "argon"); ?> ' + blur + 'px';
									}
								}
								
								if (pageBgSlider) {
									pageBgSlider.addEventListener('input', function() {
										pageBgValue.textContent = parseFloat(this.value).toFixed(2);
										updatePreview();
									});
								}
								
								if (postBgSlider) {
									postBgSlider.addEventListener('input', function() {
										postBgValue.textContent = parseFloat(this.value).toFixed(2);
										updatePreview();
									});
								}
								
								if (blurSlider) {
									blurSlider.addEventListener('input', function() {
										blurValue.textContent = this.value + 'px';
										updatePreview();
									});
								}
								
								if (saturateSlider) {
									saturateSlider.addEventListener('input', function() {
										saturateValue.textContent = this.value + '%';
										updatePreview();
									});
								}
								
								updatePreview();
							})();
							</script>

						</td>

					</tr>

					<!-- ========== 7. 侧边栏 ========== -->
					<tr><th class="subtitle"><h2 id="section-sidebar"><?php _e('侧边栏', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-author-info"><?php _e('作者信息', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('左侧栏标题', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_sidebar_banner_title" value="<?php echo get_option('argon_sidebar_banner_title'); ?>"/>

							<p class="description"><?php _e('留空则显示博客名称', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('左侧栏子标题（格言）', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_sidebar_banner_subtitle" value="<?php echo get_option('argon_sidebar_banner_subtitle'); ?>"/>

							<p class="description"><?php _e('留空则不显示', 'argon');?><br/><?php _e('输入', 'argon');?> <code>--hitokoto--</code> <?php _e('调用一言 API', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('左侧栏作者名称', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_sidebar_auther_name" value="<?php echo get_option('argon_sidebar_auther_name'); ?>"/>

							<p class="description"><?php _e('留空则显示博客名', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('左侧栏作者头像地址', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_sidebar_auther_image" value="<?php echo get_option('argon_sidebar_auther_image'); ?>"/>

							<p class="description"><?php _e('需带上 http(s) 开头', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('左侧栏作者简介', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_sidebar_author_description" value="<?php echo get_option('argon_sidebar_author_description'); ?>"/>

							<p class="description"><?php _e('留空则不显示', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-sidebar-features"><?php _e('扩展功能', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('显示 TODO 列表', 'argon');?></label></th>

						<td>

							<select name="argon_show_todo_list">

								<?php $argon_show_todo_list = get_option('argon_show_todo_list', 'true'); ?>

								<option value="true" <?php if ($argon_show_todo_list=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

								<option value="false" <?php if ($argon_show_todo_list=='false'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>

							</select>

							<p class="description"><?php _e('在左侧栏显示作者的 TODO 列表。作者登录后可添加和完成 TODO，访客可以点击催促作者完成。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('显示多邻国连胜', 'argon');?></label></th>

						<td>

							<select name="argon_show_duolingo_streak">

								<?php $argon_show_duolingo_streak = get_option('argon_show_duolingo_streak', 'false'); ?>

								<option value="false" <?php if ($argon_show_duolingo_streak=='false'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>

								<option value="true" <?php if ($argon_show_duolingo_streak=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('在作者名称旁显示多邻国连胜天数徽章', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('多邻国用户名', 'argon');?></label></th>

						<td>

							<input name="argon_duolingo_username" type="text" class="regular-text" value="<?php echo get_option('argon_duolingo_username', ''); ?>" placeholder="<?php _e('输入多邻国用户名', 'argon');?>">

							<p class="description"><?php _e('填写你的多邻国用户名以自动获取连胜天数（需要在多邻国隐私设置中开启公开资料）', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-announcement"><?php _e('博客公告', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('公告内容', 'argon');?></label></th>

						<td>

							<textarea type="text" rows="5" cols="50" name="argon_sidebar_announcement"><?php echo htmlspecialchars(get_option('argon_sidebar_announcement')); ?></textarea>

							<p class="description"><?php _e('显示在左侧栏顶部，留空则不显示，支持 HTML 标签', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 8. 浮动按钮 ========== -->
					<tr><th class="subtitle"><h2 id="section-fab"><?php _e('浮动按钮', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-fab"><?php _e('浮动操作按钮', 'argon');?></h3></th></tr>

					<tr><th class="subtitle"><p class="description"><?php _e('浮动操作按钮位于页面右下角（或左下角）', 'argon');?></p></th></tr>

					<tr>

						<th><label><?php _e('显示设置按钮', 'argon');?></label></th>

						<td>

							<select name="argon_fab_show_settings_button">

							<?php $argon_fab_show_settings_button = get_option('argon_fab_show_settings_button'); ?>

								<option value="true" <?php if ($argon_fab_show_settings_button=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

								<option value="false" <?php if ($argon_fab_show_settings_button=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('是否在浮动操作按钮栏中显示设置按钮。点击设置按钮可以唤出设置菜单修改夜间模式/字体/滤镜等外观选项。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('显示夜间模式切换按钮', 'argon');?></label></th>

						<td>

							<select name="argon_fab_show_darkmode_button">

							<?php $argon_fab_show_darkmode_button = get_option('argon_fab_show_darkmode_button'); ?>

								<option value="false" <?php if ($argon_fab_show_darkmode_button=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

								<option value="true" <?php if ($argon_fab_show_darkmode_button=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('如果开启了设置按钮显示，建议关闭此选项。（夜间模式选项在设置菜单中已经存在）', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('显示跳转到评论按钮', 'argon');?></label></th>

						<td>

							<select name="argon_fab_show_gotocomment_button">

							<?php $argon_fab_show_gotocomment_button = get_option('argon_fab_show_gotocomment_button'); ?>

								<option value="false" <?php if ($argon_fab_show_gotocomment_button=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

								<option value="true" <?php if ($argon_fab_show_gotocomment_button=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('仅在允许评论的文章中显示', 'argon');?></p>

						</td>

					</tr>


					<!-- ========== 9. 页脚设置 ========== -->
					<tr><th class="subtitle"><h2 id="section-footer"><?php _e('页脚设置', 'argon');?></h2></th></tr>

					<tr>

						<th><label><?php _e('归档页面链接', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_archives_timeline_url" value="<?php echo get_option('argon_archives_timeline_url'); ?>"/>

							<p class="description"><?php _e('归档页面的 URL。点击左侧栏 "博客概览" 中的 "博文总数" 一栏时可跳转到该地址。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('页脚内容', 'argon');?></label></th>

						<td>

							<textarea type="text" rows="15" cols="100" name="argon_footer_html"><?php echo htmlspecialchars(get_option('argon_footer_html')); ?></textarea>

							<p class="description"><?php _e('HTML , 支持 script 等标签', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 10. SEO与性能 ========== -->
					<tr><th class="subtitle"><h2 id="section-seo-performance"><?php _e('SEO与性能', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-seo">SEO</h3></th></tr>

					<tr>

						<th><label><?php _e('网站描述 (Description Meta 标签)', 'argon');?></label></th>

						<td>

							<textarea type="text" rows="5" cols="100" name="argon_seo_description"><?php echo htmlspecialchars(get_option('argon_seo_description')); ?></textarea>

							<p class="description"><?php _e('设置针对搜索引擎的 Description Meta 标签内容。', 'argon');?><br/><?php _e('在文章中，Argon 会自动根据文章内容生成描述。在其他页面中，Argon 将使用这里设置的内容。如不填，Argon 将不会在其他页面输出 Description Meta 标签。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('搜索引擎关键词（Keywords Meta 标签）', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_seo_keywords" value="<?php echo get_option('argon_seo_keywords'); ?>"/>

							<p class="description"><?php _e('设置针对搜索引擎使用的关键词（Keywords Meta 标签内容）。用英文逗号隔开。不设置则不输出该 Meta 标签。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-cdn"><?php _e('CDN 加速', 'argon');?></h3></th></tr>

					<tr>

						<th><label>CDN</label></th>

						<td>

							<select name="argon_assets_path">

								<?php $argon_assets_path = get_option('argon_assets_path'); ?>

								<option value="default" <?php if ($argon_assets_path=='default'){echo 'selected';} ?>><?php _e('不使用', 'argon');?></option>

								<option value="jsdelivr" <?php if ($argon_assets_path=='jsdelivr'){echo 'selected';} ?>>Jsdelivr</option>

								<option value="fastgit" <?php if ($argon_assets_path=='fastgit'){echo 'selected';} ?>>Fastgit</option>

								<option value="sourcegcdn" <?php if ($argon_assets_path=='sourcegcdn'){echo 'selected';} ?>>Source Global CDN</option>

								<option value="jsdelivr_gcore" <?php if ($argon_assets_path=='jsdelivr_gcore'){echo 'selected';} ?>>Jsdelivr (gcore)</option>

								<option value="jsdelivr_fastly" <?php if ($argon_assets_path=='jsdelivr_fastly'){echo 'selected';} ?>>Jsdelivr (fastly)</option>

								<option value="jsdelivr_cf" <?php if ($argon_assets_path=='jsdelivr_cf'){echo 'selected';} ?>>Jsdelivr (cf)</option>

								<option value="custom" <?php if ($argon_assets_path=='custom'){echo 'selected';} ?>><?php _e('自定义...', 'argon');?></option>

							</select>

							<input type="text" class="regular-text" name="argon_custom_assets_path" placeholder="https://" value="<?php echo get_option('argon_custom_assets_path', ''); ?>" autocomplete="off">

							<p class="description"><?php _e('选择主题资源文件的引用地址。使用 CDN 可以加速资源文件的访问并减少服务器压力。', 'argon');?></p>

							<p class="description custom-assets-path-desctiption"><?php _e('在自定义路径中使用 <code>%theme_version%</code> 来表示主题版本号。', 'argon');?></p>

						</td>

						<script>

							$("select[name='argon_assets_path']").change(function(){

								if ($(this).val() == 'custom') {

									$("input[name='argon_custom_assets_path']").css('display', '');

									$(".custom-assets-path-desctiption").css('display', '');

								} else {

									$("input[name='argon_custom_assets_path']").css('display', 'none');

									$(".custom-assets-path-desctiption").css('display', 'none');

								}

							}).change();

						</script>

					</tr>

					<tr>

						<th><label><?php _e('WordPress 安装目录', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_wp_path" value="<?php echo get_option('argon_wp_path', '/'); ?>"/>

							<p class="description"><?php _e('如果 WordPress 安装在子目录中，请在此填写子目录地址（例如', 'argon');?> <code>/blog/</code><?php _e('），注意前后各有一个斜杠。默认为', 'argon');?> <code>/</code> <?php _e('。', 'argon');?><br/><?php _e('如果不清楚该选项的用处，请保持默认。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-date-format"><?php _e('日期格式', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('日期格式', 'argon');?></label></th>

						<td>

							<select name="argon_dateformat">

								<?php $argon_dateformat = get_option('argon_dateformat'); ?>

								<option value="YMD" <?php if ($argon_dateformat=='YMD'){echo 'selected';} ?>>Y-M-D</option>

								<option value="DMY" <?php if ($argon_dateformat=='DMY'){echo 'selected';} ?>>D-M-Y</option>

								<option value="MDY" <?php if ($argon_dateformat=='MDY'){echo 'selected';} ?>>M-D-Y</option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<!-- ========== 11. 文章显示 ========== -->
					<tr><th class="subtitle"><h2 id="section-post-display"><?php _e('文章显示', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-post-meta"><?php _e('Meta 信息', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('第一行', 'argon');?></label></th>

						<style>

							.article-meta-container {

								margin-top: 10px;

								margin-bottom: 15px;

								width: calc(100% - 250px);

							}

							@media screen and (max-width:960px){

								.article-meta-container {

									width: 100%;

								}

							}

							#article_meta_active, #article_meta_inactive {

								background: rgba(0, 0, 0, .05);

								padding: 10px 15px;

								margin-top: 10px;

								border-radius: 5px;

								padding-bottom: 0;

								min-height: 48px;

								box-sizing: border-box;

							}

							.article-meta-item {

								background: #fafafa;

								width: max-content !important;

								height: max-content !important;

								border-radius: 100px;

								padding: 5px 15px;

								cursor: move;

								display: inline-block;

								margin-right: 8px;

								margin-bottom: 10px;

							}

						</style>

						<td>

							<input type="text" class="regular-text" name="argon_article_meta" value="<?php echo get_option('argon_article_meta', 'time|views|comments|categories'); ?>" style="display: none;"/>

							<?php _e('拖动来自定义文章 Meta 信息的显示和顺序', 'argon');?>

							<div class="article-meta-container">

								<?php _e('显示', 'argon');?>

								<div id="article_meta_active"></div>

							</div>

							<div class="article-meta-container">

								<?php _e('不显示', 'argon');?>

								<div id="article_meta_inactive">

									<div class="article-meta-item" meta-name="time"><?php _e('发布时间', 'argon');?></div>

									<div class="article-meta-item" meta-name="edittime"><?php _e('修改时间', 'argon');?></div>

									<div class="article-meta-item" meta-name="views"><?php _e('浏览量', 'argon');?></div>

									<div class="article-meta-item" meta-name="comments"><?php _e('评论数', 'argon');?></div>

									<div class="article-meta-item" meta-name="categories"><?php _e('所属分类', 'argon');?></div>

									<div class="article-meta-item" meta-name="author"><?php _e('作者', 'argon');?></div>

								</div>

							</div>

						</td>

						<script>

							!function(){

								let articleMeta = $("input[name='argon_article_meta']").val().split("|");

								for (metaName of articleMeta){

									let itemDiv = $("#article_meta_inactive .article-meta-item[meta-name='"+ metaName + "']");

									$("#article_meta_active").append(itemDiv.prop("outerHTML"));

									itemDiv.remove();

								}

							}();

							dragula(

								[document.querySelector('#article_meta_active'), document.querySelector('#article_meta_inactive')],

								{

									direction: 'vertical'

								}

							).on('dragend', function(){

								let articleMeta = "";

								$("#article_meta_active .article-meta-item").each(function(index, item) {

									if (index != 0){

										articleMeta += "|";

									}

									articleMeta += item.getAttribute("meta-name");

								});

								$("input[name='argon_article_meta']").val(articleMeta);

							});

						</script>

					</tr>

					<tr><th class="subtitle"><h4><?php _e('第二行', 'argon');?></h4></th></tr>

					<tr>

						<th><label><?php _e('显示字数和预计阅读时间', 'argon');?></label></th>

						<td>

							<select name="argon_show_readingtime">

								<?php $argon_show_readingtime = get_option('argon_show_readingtime'); ?>

								<option value="true" <?php if ($argon_show_readingtime=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

								<option value="false" <?php if ($argon_show_readingtime=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

							</select>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('每分钟阅读字数（中文）', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_reading_speed" min="1" max="5000"  value="<?php echo (get_option('argon_reading_speed') == '' ? '300' : get_option('argon_reading_speed')); ?>"/>

							<?php _e('字/分钟', 'argon');?>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('每分钟阅读单词数（英文）', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_reading_speed_en" min="1" max="5000"  value="<?php echo (get_option('argon_reading_speed_en') == '' ? '160' : get_option('argon_reading_speed_en')); ?>"/>

							<?php _e('单词/分钟', 'argon');?>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('每分钟阅读代码行数', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_reading_speed_code" min="1" max="5000"  value="<?php echo (get_option('argon_reading_speed_code') == '' ? '20' : get_option('argon_reading_speed_code')); ?>"/>

							<?php _e('行/分钟', 'argon');?>

							<p class="description"><?php _e('预计阅读时间由每分钟阅读字数计算', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-thumbnail"><?php _e('文章头图', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('文章头图的位置', 'argon');?></label></th>

						<td>

							<select name="argon_show_thumbnail_in_banner_in_content_page">

								<?php $argon_show_thumbnail_in_banner_in_content_page = get_option('argon_show_thumbnail_in_banner_in_content_page'); ?>

								<option value="false" <?php if ($argon_show_thumbnail_in_banner_in_content_page=='false'){echo 'selected';} ?>><?php _e('文章卡片顶端', 'argon');?></option>

								<option value="true" <?php if ($argon_show_thumbnail_in_banner_in_content_page=='true'){echo 'selected';} ?>><?php _e('Banner (顶部背景)', 'argon');?></option>

							</select>

							<p class="description"><?php _e('阅读界面中文章头图的位置', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('默认使用文章中第一张图作为头图', 'argon');?></label></th>

						<td>

							<select name="argon_first_image_as_thumbnail_by_default">

								<?php $argon_first_image_as_thumbnail_by_default = get_option('argon_first_image_as_thumbnail_by_default'); ?>

								<option value="false" <?php if ($argon_first_image_as_thumbnail_by_default=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

								<option value="true" <?php if ($argon_first_image_as_thumbnail_by_default=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('也可以针对每篇文章单独设置', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 12. 文章功能 ========== -->
					<tr><th class="subtitle"><h2 id="section-post-features"><?php _e('文章功能', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-ai-summary"><?php _e('AI 文章摘要', 'argon');?></h3></th></tr>

					<tr>
						<th><label><?php _e('启用 AI 文章摘要', 'argon');?></label></th>
						<td>
							<select name="argon_ai_summary_enable">
								<?php $argon_ai_summary_enable = get_option('argon_ai_summary_enable', 'false'); ?>
								<option value="true" <?php if ($argon_ai_summary_enable=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
								<option value="false" <?php if ($argon_ai_summary_enable=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
							</select>
							<p class="description"><?php _e('开启后，文章开头会显示 AI 生成的摘要。每篇文章只生成一次，结果会缓存到服务器。', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('AI 服务商', 'argon');?></label></th>
						<td>
							<select name="argon_ai_summary_provider">
								<?php $argon_ai_summary_provider = get_option('argon_ai_summary_provider', 'openai'); ?>
								<option value="openai" <?php if ($argon_ai_summary_provider=='openai'){echo 'selected';} ?>>OpenAI (ChatGPT)</option>
								<option value="anthropic" <?php if ($argon_ai_summary_provider=='anthropic'){echo 'selected';} ?>>Anthropic (Claude)</option>
								<option value="deepseek" <?php if ($argon_ai_summary_provider=='deepseek'){echo 'selected';} ?>>DeepSeek</option>
								<option value="qianwen" <?php if ($argon_ai_summary_provider=='qianwen'){echo 'selected';} ?>><?php _e('通义千问', 'argon');?></option>
								<option value="wenxin" <?php if ($argon_ai_summary_provider=='wenxin'){echo 'selected';} ?>><?php _e('文心一言', 'argon');?></option>
								<option value="doubao" <?php if ($argon_ai_summary_provider=='doubao'){echo 'selected';} ?>><?php _e('豆包 (火山引擎)', 'argon');?></option>
								<option value="kimi" <?php if ($argon_ai_summary_provider=='kimi'){echo 'selected';} ?>>Kimi (Moonshot)</option>
								<option value="zhipu" <?php if ($argon_ai_summary_provider=='zhipu'){echo 'selected';} ?>><?php _e('智谱 AI (GLM)', 'argon');?></option>
								<option value="siliconflow" <?php if ($argon_ai_summary_provider=='siliconflow'){echo 'selected';} ?>><?php _e('硅基流动 (SiliconFlow)', 'argon');?></option>
							</select>
							<p class="description"><?php _e('选择 AI 服务提供商', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('API 密钥', 'argon');?></label></th>
						<td>
							<input type="password" class="regular-text" name="argon_ai_summary_api_key" value="<?php echo get_option('argon_ai_summary_api_key', ''); ?>" placeholder="sk-..."/>
							<p class="description"><?php _e('填写对应服务商的 API Key', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('API 端点 (可选)', 'argon');?></label></th>
						<td>
							<input type="text" class="regular-text" name="argon_ai_summary_api_endpoint" value="<?php echo get_option('argon_ai_summary_api_endpoint', ''); ?>" placeholder="<?php _e('留空使用默认端点', 'argon');?>"/>
							<p class="description"><?php _e('自定义 API 端点地址，留空则使用官方默认地址。适用于使用代理或第三方兼容接口。', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('AI 模型', 'argon');?></label></th>
						<td>
							<select name="argon_ai_summary_model" id="argon_ai_summary_model" class="regular-text">
								<option value=""><?php _e('使用默认模型', 'argon');?></option>
								<?php
								$current_model = get_option('argon_ai_summary_model', '');
								if (!empty($current_model)) {
									echo '<option value="' . esc_attr($current_model) . '" selected>' . esc_html($current_model) . '</option>';
								}
								?>
							</select>
							<button type="button" class="button" id="argon_refresh_models" style="margin-left: 10px;">
								<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> <?php _e('刷新模型列表', 'argon');?>
							</button>
							<span id="argon_model_loading" style="display:none;margin-left:10px;color:#666;">
								<span class="dashicons dashicons-update spin" style="margin-top:3px;"></span> <?php _e('加载中...', 'argon');?>
							</span>
							<p class="description">
								<?php _e('选择要使用的模型。点击"刷新模型列表"可从服务商获取最新可用模型。', 'argon');?><br/>
								<?php _e('默认模型: OpenAI (gpt-4o-mini) | Claude (claude-3-5-haiku-20241022) | DeepSeek (deepseek-chat)', 'argon');?><br/>
								<?php _e('通义千问 (qwen-turbo) | 文心一言 (ernie-4.0-turbo-8k) | 豆包 (doubao-pro-32k)', 'argon');?><br/>
								<?php _e('Kimi (moonshot-v1-8k) | 智谱 (glm-4-flash) | 硅基流动 (Qwen/Qwen2.5-7B-Instruct)', 'argon');?>
							</p>
							<style>
								@keyframes spin {
									from { transform: rotate(0deg); }
									to { transform: rotate(360deg); }
								}
								.dashicons.spin {
									animation: spin 1s linear infinite;
								}
							</style>
							<script>
							jQuery(document).ready(function($) {
								var currentProvider = $('select[name="argon_ai_summary_provider"]').val();
								
								// 监听服务商变化
								$('select[name="argon_ai_summary_provider"]').on('change', function() {
									currentProvider = $(this).val();
									$('#argon_ai_summary_model').html('<option value=""><?php _e('使用默认模型', 'argon');?></option>');
								});
								
								// 刷新模型列表
								$('#argon_refresh_models').on('click', function() {
									var btn = $(this);
									var loading = $('#argon_model_loading');
									var select = $('#argon_ai_summary_model');
									var apiKey = $('input[name="argon_ai_summary_api_key"]').val();
									var apiEndpoint = $('input[name="argon_ai_summary_api_endpoint"]').val();
									
									if (!apiKey) {
										alert('<?php _e('请先填写 API 密钥', 'argon');?>');
										return;
									}
									
									btn.prop('disabled', true);
									loading.show();
									
									$.post(ajaxurl, {
										action: 'argon_get_ai_models',
										nonce: '<?php echo wp_create_nonce('argon_get_ai_models'); ?>',
										provider: currentProvider,
										api_key: apiKey,
										api_endpoint: apiEndpoint
									}, function(response) {
										btn.prop('disabled', false);
										loading.hide();
										
										if (response.success && response.data.models) {
											var currentValue = select.val();
											select.html('<option value=""><?php _e('使用默认模型', 'argon');?></option>');
											
											$.each(response.data.models, function(i, model) {
												var selected = model.id === currentValue ? ' selected' : '';
												select.append('<option value="' + model.id + '"' + selected + '>' + model.name + '</option>');
											});
											
											if (response.data.models.length > 0) {
												alert('<?php _e('成功加载', 'argon');?> ' + response.data.models.length + ' <?php _e('个模型', 'argon');?>');
											} else {
												alert('<?php _e('未找到可用模型', 'argon');?>');
											}
										} else {
											alert('<?php _e('获取模型列表失败', 'argon');?>: ' + (response.data || '<?php _e('未知错误', 'argon');?>'));
										}
									}).fail(function() {
										btn.prop('disabled', false);
										loading.hide();
										alert('<?php _e('请求失败，请检查网络连接', 'argon');?>');
									});
								});
							});
							</script>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('摘要提示词', 'argon');?></label></th>
						<td>
							<textarea type="text" rows="4" cols="70" name="argon_ai_summary_prompt"><?php echo get_option('argon_ai_summary_prompt', '你是一个专业的内容摘要助手。请仔细阅读以下文章内容，用简洁、准确的语言总结文章的核心观点和主要内容。要求：1) 控制在 100-150 字以内；2) 突出文章的关键信息和亮点；3) 使用通俗易懂的语言；4) 保持客观中立的语气。'); ?></textarea>
							<p class="description"><?php _e('自定义 AI 生成摘要时使用的提示词', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('排除文章 ID', 'argon');?></label></th>
						<td>
							<input type="text" class="regular-text" name="argon_ai_summary_exclude_ids" value="<?php echo get_option('argon_ai_summary_exclude_ids', ''); ?>" placeholder="1,2,3"/>
							<p class="description"><?php _e('不显示 AI 摘要的文章 ID，多个用英文逗号分隔', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('手动重新生成', 'argon');?></label></th>
						<td>
							<button type="button" class="button" id="argon-clear-ai-summaries"><?php _e('清除所有 AI 摘要缓存', 'argon');?></button>
							<p class="description"><?php _e('清除后，所有文章的 AI 摘要将在下次访问时重新生成', 'argon');?></p>
							<script>
							jQuery('#argon-clear-ai-summaries').on('click', function() {
								if (!confirm('<?php _e('确定要清除所有 AI 摘要缓存吗？', 'argon');?>')) return;
								var btn = jQuery(this);
								btn.prop('disabled', true).text('<?php _e('清除中...', 'argon');?>');
								jQuery.post(ajaxurl, {
									action: 'argon_clear_ai_summaries',
									nonce: '<?php echo wp_create_nonce('argon_clear_ai_summaries'); ?>'
								}, function(response) {
									if (response.success) {
										alert('<?php _e('清除成功', 'argon');?>');
									} else {
										alert('<?php _e('清除失败', 'argon');?>: ' + (response.data || ''));
									}
									btn.prop('disabled', false).text('<?php _e('清除所有 AI 摘要缓存', 'argon');?>');
								});
							});
							</script>
						</td>
					</tr>

					<tr><th class="subtitle"><h3 id="subsection-footnote"><?php _e('脚注引用', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('脚注列表标题', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_reference_list_title" value="<?php echo (get_option('argon_reference_list_title') == "" ? __('参考', 'argon') : get_option('argon_reference_list_title')); ?>"/>

							<p class="description"><?php _e('脚注列表显示在文末，在文章中有脚注的时候会显示。<br/>使用 <code>ref</code> 短代码可以在文中插入脚注。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-share"><?php _e('分享', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('显示文章分享按钮', 'argon');?></label></th>

						<td>

							<select name="argon_show_sharebtn">

								<?php $argon_show_sharebtn = get_option('argon_show_sharebtn'); ?>

								<option value="true" <?php if ($argon_show_sharebtn=='true'){echo 'selected';} ?>><?php _e('显示全部社交媒体', 'argon');?></option>

								<option value="domestic" <?php if ($argon_show_sharebtn=='domestic'){echo 'selected';} ?>><?php _e('显示国内社交媒体', 'argon');?></option>

								<option value="abroad" <?php if ($argon_show_sharebtn=='abroad'){echo 'selected';} ?>><?php _e('显示国外社交媒体', 'argon');?></option>

								<option value="false" <?php if ($argon_show_sharebtn=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-toc"><?php _e('文章目录', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('在目录中显示序号', 'argon');?></label></th>

						<td>

							<select name="argon_show_headindex_number">

								<?php $argon_show_headindex_number = get_option('argon_show_headindex_number'); ?>

								<option value="false" <?php if ($argon_show_headindex_number=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

								<option value="true" <?php if ($argon_show_headindex_number=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('例：3.2.5', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-donate"><?php _e('赞赏', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('赞赏二维码图片链接', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_donate_qrcode_url" value="<?php echo get_option('argon_donate_qrcode_url'); ?>"/>

							<p class="description"><?php _e('赞赏二维码图片链接，填写后会在文章最后显示赞赏按钮，留空则不显示赞赏按钮', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-post-footer"><?php _e('文末内容', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('文末附加内容', 'argon');?></label></th>

						<td>

							<textarea type="text" rows="5" cols="100" name="argon_additional_content_after_post"><?php echo htmlspecialchars(get_option('argon_additional_content_after_post')); ?></textarea>

							<p class="description"><?php _e('将会显示在每篇文章末尾，支持 HTML 标签，留空则不显示。', 'argon');?><br/><?php _e('使用 <code>%url%</code> 来代替当前页面 URL，<code>%link%</code> 来代替当前页面链接，<code>%title%</code> 来代替当前文章标题，<code>%author%</code> 来代替当前文章作者。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-related-posts"><?php _e('相似文章推荐', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('相似文章推荐', 'argon');?></label></th>

						<td>

							<select name="argon_related_post">

								<?php $argon_related_post = get_option('argon_related_post'); ?>

								<option value="disabled" <?php if ($argon_related_post=='disabled'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="category" <?php if ($argon_related_post=='category'){echo 'selected';} ?>><?php _e('根据分类推荐', 'argon');?></option>

								<option value="tag" <?php if ($argon_related_post=='tag'){echo 'selected';} ?>><?php _e('根据标签推荐', 'argon');?></option>

								<option value="category,tag" <?php if ($argon_related_post=='category,tag'){echo 'selected';} ?>><?php _e('根据分类和标签推荐', 'argon');?></option>

							<p class="description"><?php _e('显示在文章卡片后', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('排序依据', 'argon');?></label></th>

						<td>

							<select name="argon_related_post_sort_orderby">

								<?php $argon_related_post_sort_orderby = get_option('argon_related_post_sort_orderby'); ?>

								<option value="date" <?php if ($argon_related_post_sort_orderby=='date'){echo 'selected';} ?>><?php _e('发布时间', 'argon');?></option>

								<option value="modified" <?php if ($argon_related_post_sort_orderby=='modified'){echo 'selected';} ?>><?php _e('修改时间', 'argon');?></option>

								<option value="meta_value_num" <?php if ($argon_related_post_sort_orderby=='meta_value_num'){echo 'selected';} ?>><?php _e('阅读量', 'argon');?></option>

								<option value="ID" <?php if ($argon_related_post_sort_orderby=='ID'){echo 'selected';} ?>>ID</option>

								<option value="title" <?php if ($argon_related_post_sort_orderby=='title'){echo 'selected';} ?>><?php _e('标题', 'argon');?></option>

								<option value="author" <?php if ($argon_related_post_sort_orderby=='author'){echo 'selected';} ?>><?php _e('作者', 'argon');?></option>

								<option value="rand" <?php if ($argon_related_post_sort_orderby=='rand'){echo 'selected';} ?>><?php _e('随机', 'argon');?></option>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('顺序', 'argon');?></label></th>

						<td>

							<select name="argon_related_post_sort_order">

								<?php $argon_related_post_sort_order = get_option('argon_related_post_sort_order'); ?>

								<option value="DESC" <?php if ($argon_related_post_sort_order=='DESC'){echo 'selected';} ?>><?php _e('倒序', 'argon');?></option>

								<option value="ASC" <?php if ($argon_related_post_sort_order=='ASC'){echo 'selected';} ?>><?php _e('正序', 'argon');?></option>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('推荐文章数', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_related_post_limit" min="1" max="100" value="<?php echo get_option('argon_related_post_limit' , '10'); ?>"/>

							<p class="description"><?php _e('最多推荐多少篇文章', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-title-style"><?php _e('标题样式', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('文章内标题样式', 'argon');?></label></th>

						<td>

							<select name="argon_article_header_style">

								<?php $argon_article_header_style = get_option('argon_article_header_style'); ?>

								<option value="article-header-style-default" <?php if ($argon_article_header_style=='article-header-style-default'){echo 'selected';} ?>><?php _e('默认样式', 'argon');?></option>

								<option value="article-header-style-1" <?php if ($argon_article_header_style=='article-header-style-1'){echo 'selected';} ?>><?php _e('样式 1', 'argon');?></option>

								<option value="article-header-style-2" <?php if ($argon_article_header_style=='article-header-style-2'){echo 'selected';} ?>><?php _e('样式 2', 'argon');?></option>

							</select>

							<p class="description"><?php _e('样式预览', 'argon');?> :<br/>

								<div class="article-header-style-preview style-default"><?php _e('默认样式', 'argon');?></div>

								<div class="article-header-style-preview style-1"><?php _e('样式 1', 'argon');?></div>

								<div class="article-header-style-preview style-2"><?php _e('样式 2', 'argon');?></div>

								<style>

									.article-header-style-preview{

										font-size: 26px;

										position: relative;

									}

									.article-header-style-preview.style-1:after {

										content: '';

										display: block;

										position: absolute;

										background: #5e72e4;

										opacity: .25;

										pointer-events: none;

										border-radius: 15px;

										left: -2px;

										bottom: 0px;

										width: 45px;

										height: 13px;

									}

									.article-header-style-preview.style-2:before {

										content: '';

										display: inline-block;

										background: #5e72e4;

										opacity: 1;

										pointer-events: none;

										border-radius: 15px;

										width: 6px;

										vertical-align: middle;

										margin-right: 12px;

										height: 20px;

										transform: translateY(-1px);

									}

								</style>

							</p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-post-other"><?php _e('过时提示', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('文章过时信息显示', 'argon');?></label></th>

						<td>

							<?php _e('当一篇文章的', 'argon');?>

							<select name="argon_outdated_info_time_type">

								<?php $argon_outdated_info_time_type = get_option('argon_outdated_info_time_type'); ?>

								<option value="modifiedtime" <?php if ($argon_outdated_info_time_type=='modifiedtime'){echo 'selected';} ?>><?php _e('最后修改时间', 'argon');?></option>

								<option value="createdtime" <?php if ($argon_outdated_info_time_type=='createdtime'){echo 'selected';} ?>><?php _e('发布时间', 'argon');?></option>

							</select>

							<?php _e('距离现在超过', 'argon');?>

							<input type="number" name="argon_outdated_info_days" min="-1" max="99999"  value="<?php echo (get_option('argon_outdated_info_days') == '' ? '-1' : get_option('argon_outdated_info_days')); ?>"/>

							<?php _e('天时，用', 'argon');?>

							<select name="argon_outdated_info_tip_type">

								<?php $argon_outdated_info_tip_type = get_option('argon_outdated_info_tip_type'); ?>

								<option value="inpost" <?php if ($argon_outdated_info_tip_type=='inpost'){echo 'selected';} ?>><?php _e('在文章顶部显示信息条', 'argon');?></option>

								<option value="toast" <?php if ($argon_outdated_info_tip_type=='toast'){echo 'selected';} ?>><?php _e('在页面右上角弹出提示条', 'argon');?></option>

							</select>

							<?php _e('的方式提示', 'argon');?>

							<br/>

							<textarea type="text" name="argon_outdated_info_tip_content" rows="3" cols="100" style="margin-top: 15px;"><?php echo get_option('argon_outdated_info_tip_content') == '' ? __('本文最后更新于 %date_delta% 天前，其中的信息可能已经有所发展或是发生改变。', 'argpm') : get_option('argon_outdated_info_tip_content'); ?></textarea>

							<p class="description"><?php _e('天数为 -1 表示永不提示。', 'argon');?><br/><code>%date_delta%</code> <?php _e('表示文章发布/修改时间与当前时间的差距，', 'argon');?><code>%post_date_delta%</code> <?php _e('表示文章发布时间与当前时间的差距，', 'argon');?><code>%modify_date_delta%</code> <?php _e('表示文章修改时间与当前时间的差距（单位: 天）。', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 13. 特殊页面 ========== -->
					<tr><th class="subtitle"><h2 id="section-special-pages"><?php _e('特殊页面', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-archive"><?php _e('归档页面', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('介绍', 'argon');?></label></th>

						<td>

							<p class="description"><?php _e('新建一个页面，并将其模板设为 "归档时间轴"，即可创建一个归档页面。归档页面会按照时间顺序在时间轴上列出博客的所有文章。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('在时间轴上显示月份', 'argon');?></label></th>

						<td>

							<select name="argon_archives_timeline_show_month">

								<?php $argon_archives_timeline_show_month = get_option('argon_archives_timeline_show_month'); ?>

								<option value="true" <?php if ($argon_archives_timeline_show_month=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

								<option value="false" <?php if ($argon_archives_timeline_show_month=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('关闭后，时间轴只会按年份分节', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 14. 功能增强 ========== -->
					<tr><th class="subtitle"><h2 id="section-enhancements"><?php _e('功能增强', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-code-highlight"><?php _e('代码高亮', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('启用 Highlight.js 代码高亮', 'argon');?></label></th>

						<td>

							<select name="argon_enable_code_highlight">

								<?php $argon_enable_code_highlight = get_option('argon_enable_code_highlight'); ?>

								<option value="false" <?php if ($argon_enable_code_highlight=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_code_highlight=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('所有 pre 下的 code 标签会被自动解析', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('高亮配色方案（主题）', 'argon');?></label></th>

						<td>

							<select name="argon_code_theme">

								<?php

								$argon_code_themes_list = array("a11y-dark", "a11y-light", "agate", "an-old-hope", "androidstudio", "arduino-light", "arta", "ascetic", "atelier-cave-dark", "atelier-cave-light", "atelier-dune-dark", "atelier-dune-light", "atelier-estuary-dark", "atelier-estuary-light", "atelier-forest-dark", "atelier-forest-light", "atelier-heath-dark", "atelier-heath-light", "atelier-lakeside-dark", "atelier-lakeside-light", "atelier-plateau-dark", "atelier-plateau-light", "atelier-savanna-dark", "atelier-savanna-light", "atelier-seaside-dark", "atelier-seaside-light", "atelier-sulphurpool-dark", "atelier-sulphurpool-light", "atom-one-dark-reasonable", "atom-one-dark", "atom-one-light", "brown-paper", "codepen-embed", "color-brewer", "darcula", "dark", "darkula", "default", "devibeans", "docco", "dracula", "far", "felipec", "foundation", "github-dark-dimmed", "github-dark", "github-gist", "github", "gml", "googlecode", "gradient-dark", "gradient-light", "grayscale", "gruvbox-dark", "gruvbox-light", "hopscotch", "hybrid", "idea", "intellij-light", "ir-black", "isbl-editor-dark", "isbl-editor-light", "kimbie-dark", "kimbie-light", "kimbie.dark", "kimbie.light", "lightfair", "lioshi", "magula", "mono-blue", "monokai-sublime", "monokai", "night-owl", "nnfx-dark", "nnfx-light", "nnfx", "nord", "obsidian", "ocean", "onedark", "paraiso-dark", "paraiso-light", "pojoaque", "pojoaque.jpg", "purebasic", "qtcreator-dark", "qtcreator-light", "railscasts", "rainbow", "routeros", "school-book", "shades-of-purple", "solarized-dark", "solarized-light", "srcery", "stackoverflow-dark", "stackoverflow-light", "sunburst", "tokyo-night-dark", "tomorrow-night-blue", "tomorrow-night-bright", "tomorrow-night-eighties", "tomorrow-night", "tomorrow", "vs", "vs2015", "xcode", "xt256", "zenburn", "base16/3024", "base16/apathy", "base16/apprentice", "base16/ashes", "base16/atelier-cave-light", "base16/atelier-cave", "base16/atelier-dune-light", "base16/atelier-dune", "base16/atelier-estuary-light", "base16/atelier-estuary", "base16/atelier-forest-light", "base16/atelier-forest", "base16/atelier-heath-light", "base16/atelier-heath", "base16/atelier-lakeside-light", "base16/atelier-lakeside", "base16/atelier-plateau-light", "base16/atelier-plateau", "base16/atelier-savanna-light", "base16/atelier-savanna", "base16/atelier-seaside-light", "base16/atelier-seaside", "base16/atelier-sulphurpool-light", "base16/atelier-sulphurpool", "base16/atlas", "base16/bespin", "base16/black-metal-bathory", "base16/black-metal-burzum", "base16/black-metal-dark-funeral", "base16/black-metal-gorgoroth", "base16/black-metal-immortal", "base16/black-metal-khold", "base16/black-metal-marduk", "base16/black-metal-mayhem", "base16/black-metal-nile", "base16/black-metal-venom", "base16/black-metal", "base16/brewer", "base16/bright", "base16/brogrammer", "base16/brush-trees-dark", "base16/brush-trees", "base16/chalk", "base16/circus", "base16/classic-dark", "base16/classic-light", "base16/codeschool", "base16/colors", "base16/cupcake", "base16/cupertino", "base16/danqing", "base16/darcula", "base16/dark-violet", "base16/darkmoss", "base16/darktooth", "base16/decaf", "base16/default-dark", "base16/default-light", "base16/dirtysea", "base16/dracula", "base16/edge-dark", "base16/edge-light", "base16/eighties", "base16/embers", "base16/equilibrium-dark", "base16/equilibrium-gray-dark", "base16/equilibrium-gray-light", "base16/equilibrium-light", "base16/espresso", "base16/eva-dim", "base16/eva", "base16/flat", "base16/framer", "base16/fruit-soda", "base16/gigavolt", "base16/github", "base16/google-dark", "base16/google-light", "base16/grayscale-dark", "base16/grayscale-light", "base16/green-screen", "base16/gruvbox-dark-hard", "base16/gruvbox-dark-medium", "base16/gruvbox-dark-pale", "base16/gruvbox-dark-soft", "base16/gruvbox-light-hard", "base16/gruvbox-light-medium", "base16/gruvbox-light-soft", "base16/hardcore", "base16/harmonic16-dark", "base16/harmonic16-light", "base16/heetch-dark", "base16/heetch-light", "base16/helios", "base16/hopscotch", "base16/horizon-dark", "base16/horizon-light", "base16/humanoid-dark", "base16/humanoid-light", "base16/ia-dark", "base16/ia-light", "base16/icy-dark", "base16/ir-black", "base16/isotope", "base16/kimber", "base16/london-tube", "base16/macintosh", "base16/marrakesh", "base16/materia", "base16/material-darker", "base16/material-lighter", "base16/material-palenight", "base16/material-vivid", "base16/material", "base16/mellow-purple", "base16/mexico-light", "base16/mocha", "base16/monokai", "base16/nebula", "base16/nord", "base16/nova", "base16/ocean", "base16/oceanicnext", "base16/one-light", "base16/onedark", "base16/outrun-dark", "base16/papercolor-dark", "base16/papercolor-light", "base16/paraiso", "base16/pasque", "base16/phd", "base16/pico", "base16/pop", "base16/porple", "base16/qualia", "base16/railscasts", "base16/rebecca", "base16/ros-pine-dawn", "base16/ros-pine-moon", "base16/ros-pine", "base16/sagelight", "base16/sandcastle", "base16/seti-ui", "base16/shapeshifter", "base16/silk-dark", "base16/silk-light", "base16/snazzy", "base16/solar-flare-light", "base16/solar-flare", "base16/solarized-dark", "base16/solarized-light", "base16/spacemacs", "base16/summercamp", "base16/summerfruit-dark", "base16/summerfruit-light", "base16/synth-midnight-terminal-dark", "base16/synth-midnight-terminal-light", "base16/tango", "base16/tender", "base16/tomorrow-night", "base16/tomorrow", "base16/twilight", "base16/unikitty-dark", "base16/unikitty-light", "base16/vulcan", "base16/windows-10-light", "base16/windows-10", "base16/windows-95-light", "base16/windows-95", "base16/windows-high-contrast-light", "base16/windows-high-contrast", "base16/windows-nt-light", "base16/windows-nt", "base16/woodland", "base16/xcode-dusk", "base16/zenburn");

								$argon_code_theme = get_option('argon_code_theme');

								if ($argon_code_theme == ''){

									$argon_code_theme = "vs2015";

								}

								foreach ($argon_code_themes_list as $code_theme){

									if ($argon_code_theme == $code_theme){

										echo "<option value='" . $code_theme . "' selected>" . $code_theme . "</option>";

									}else{

										echo "<option value='" . $code_theme . "'>" . $code_theme . "</option>";

									}

								}

								?>

							</select>

							<p class="description"><a href="https://highlightjs.org/static/demo/" target="_blank"><?php _e('查看所有主题预览', 'argon');?></a></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('默认显示行号', 'argon');?></label></th>

						<td>

							<select name="argon_code_highlight_hide_linenumber">

								<?php $argon_code_highlight_hide_linenumber = get_option('argon_code_highlight_hide_linenumber'); ?>

								<option value="false" <?php if ($argon_code_highlight_hide_linenumber=='false'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

								<option value="true" <?php if ($argon_code_highlight_hide_linenumber=='true'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

							</select>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('默认启用自动折行', 'argon');?></label></th>

						<td>

							<select name="argon_code_highlight_break_line">

								<?php $argon_code_highlight_break_line = get_option('argon_code_highlight_break_line'); ?>

								<option value="false" <?php if ($argon_code_highlight_break_line=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

								<option value="true" <?php if ($argon_code_highlight_break_line=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('行号背景透明', 'argon');?></label></th>

						<td>

							<select name="argon_code_highlight_transparent_linenumber">

								<?php $argon_code_highlight_transparent_linenumber = get_option('argon_code_highlight_transparent_linenumber', 'false'); ?>

								<option value="false" <?php if ($argon_code_highlight_transparent_linenumber=='false'){echo 'selected';} ?>><?php _e('不透明', 'argon');?></option>

								<option value="true" <?php if ($argon_code_highlight_transparent_linenumber=='true'){echo 'selected';} ?>><?php _e('透明', 'argon');?></option>

							</select>

							<p class="description"><?php _e('适用于某些背景渐变的高亮主题', 'argon');?></p>

						</td>

					</tr>

					<tr style="opacity: 0.5;">

						<th><label><?php _e('如果您想使用其他代码高亮插件，而非 Argon 自带高亮，请前往 "杂项" 打开 "禁用 Argon 代码块样式" 来防止样式冲突', 'argon');?></label></th>

						<td>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-math"><?php _e('数学公式', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('数学公式渲染方案', 'argon');?></label></th>

						<td>

							<table class="form-table form-table-dense form-table-mathrender">

								<tbody>

									<?php $argon_math_render = (get_option('argon_math_render') == '' ? 'none' : get_option('argon_math_render')); ?>

									<tr>

										<th>

											<label>

												<input name="argon_math_render" type="radio" value="none" <?php if ($argon_math_render=='none'){echo 'checked';} ?>>

												<?php _e('不启用', 'argon');?>

											</label>

										</th>

									</tr>

									<tr>

										<th>

											<label>

												<input name="argon_math_render" type="radio" value="mathjax3" <?php if ($argon_math_render=='mathjax3'){echo 'checked';} ?>>

												Mathjax 3

												<div>

													<label style="display: block; margin-bottom: 10px;">
														<input type="checkbox" name="argon_mathjax3_use_local" value="true" <?php if (get_option('argon_mathjax3_use_local')=='true'){echo 'checked';}?>/> 
														<?php _e('使用本地镜像', 'argon');?>
													</label>

													Mathjax 3 CDN <?php _e('地址', 'argon');?>:

													<input type="text" class="regular-text" name="argon_mathjax_cdn_url" value="<?php echo get_option('argon_mathjax_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js' : get_option('argon_mathjax_cdn_url'); ?>"/>

													<p class="description">Mathjax 3.0+<?php _e('，默认为', 'argon');?> <code>//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js</code><br/><?php _e('勾选"使用本地镜像"后将优先使用主题内置的完整版本', 'argon');?></p>

												</div>

											</label>

										</th>

									</tr>

									<tr>

										<th>

											<label>

												<input name="argon_math_render" type="radio" value="mathjax2" <?php if ($argon_math_render=='mathjax2'){echo 'checked';} ?>>

												Mathjax 2

												<div>

													<label style="display: block; margin-bottom: 10px;">
														<input type="checkbox" name="argon_mathjax2_use_local" value="true" <?php if (get_option('argon_mathjax2_use_local')=='true'){echo 'checked';}?>/> 
														<?php _e('使用本地镜像', 'argon');?>
													</label>

													Mathjax 2 CDN <?php _e('地址', 'argon');?>:

													<input type="text" class="regular-text" name="argon_mathjax_v2_cdn_url" value="<?php echo get_option('argon_mathjax_v2_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js?config=TeX-AMS_HTML' : get_option('argon_mathjax_v2_cdn_url'); ?>"/>

													<p class="description">Mathjax 2.0+<?php _e('，默认为', 'argon');?> <code>//cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js?config=TeX-AMS_HTML</code><br/><?php _e('勾选"使用本地镜像"后将优先使用主题内置的完整版本', 'argon');?></p>

												</div>

											</label>

										</th>

									</tr>

									<tr>

										<th>

											<label>

												<input name="argon_math_render" type="radio" value="katex" <?php if ($argon_math_render=='katex'){echo 'checked';} ?>>

												Katex

												<div>

													<label style="display: block; margin-bottom: 10px;">
														<input type="checkbox" name="argon_katex_use_local" value="true" <?php if (get_option('argon_katex_use_local')=='true'){echo 'checked';}?>/> 
														<?php _e('使用本地镜像', 'argon');?>
													</label>

													Katex CDN <?php _e('地址', 'argon');?>:

													<input type="text" class="regular-text" name="argon_katex_cdn_url" value="<?php echo get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url'); ?>"/>

													<p class="description"><?php _e('Argon 会同时引用', 'argon');?> <code>katex.min.css</code> <?php _e('和', 'argon');?> <code>katex.min.js</code> <?php _e('两个文件，所以在此填写的是上层的路径，而不是具体的文件。注意路径后要带一个斜杠。', 'argon');?><br/><?php _e('默认为', 'argon');?> <code>//cdn.jsdelivr.net/npm/katex@0.11.1/dist/</code><br/><?php _e('勾选"使用本地镜像"后将优先使用主题内置的完整版本', 'argon');?></p>

												</div>

											</label>

										</th>

									</tr>

								</tbody>

							</table>

							<p class="description"></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-lazyload">Lazyload</h3></th></tr>

					<tr>

						<th><label><?php _e('是否启用 Lazyload', 'argon');?></label></th>

						<td>

							<select name="argon_enable_lazyload">

								<?php $argon_enable_lazyload = get_option('argon_enable_lazyload'); ?>

								<option value="true" <?php if ($argon_enable_lazyload=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($argon_enable_lazyload=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('是否启用 Lazyload 加载文章内图片', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('提前加载阈值', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_lazyload_threshold" min="0" max="2500"  value="<?php echo (get_option('argon_lazyload_threshold') == '' ? '800' : get_option('argon_lazyload_threshold')); ?>"/>

							<p class="description"><?php _e('图片距离页面底部还有多少距离就开始提前加载', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('LazyLoad 图片加载完成过渡', 'argon');?></label></th>

						<td>

							<select name="argon_lazyload_effect">

								<?php $argon_lazyload_effect = get_option('argon_lazyload_effect'); ?>

								<option value="fadeIn" <?php if ($argon_lazyload_effect=='fadeIn'){echo 'selected';} ?>>fadeIn</option>

								<option value="slideDown" <?php if ($argon_lazyload_effect=='slideDown'){echo 'selected';} ?>>slideDown</option>

								<option value="none" <?php if ($argon_lazyload_effect=='none'){echo 'selected';} ?>><?php _e('不使用过渡', 'argon');?></option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('LazyLoad 图片加载动效', 'argon');?></label></th>

						<td>

							<select name="argon_lazyload_loading_style">

								<?php $argon_lazyload_loading_style = get_option('argon_lazyload_loading_style'); ?>

								<option value="1" <?php if ($argon_lazyload_loading_style=='1'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 1</option>

								<option value="2" <?php if ($argon_lazyload_loading_style=='2'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 2</option>

								<option value="3" <?php if ($argon_lazyload_loading_style=='3'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 3</option>

								<option value="4" <?php if ($argon_lazyload_loading_style=='4'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 4</option>

								<option value="5" <?php if ($argon_lazyload_loading_style=='5'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 5</option>

								<option value="6" <?php if ($argon_lazyload_loading_style=='6'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 6</option>

								<option value="7" <?php if ($argon_lazyload_loading_style=='7'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 7</option>

								<option value="8" <?php if ($argon_lazyload_loading_style=='8'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 8</option>

								<option value="9" <?php if ($argon_lazyload_loading_style=='9'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 9</option>

								<option value="10" <?php if ($argon_lazyload_loading_style=='10'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 10</option>

								<option value="11" <?php if ($argon_lazyload_loading_style=='11'){echo 'selected';} ?>><?php _e('加载动画', 'argon');?> 11</option>

								<option value="none" <?php if ($argon_lazyload_loading_style=='none'){echo 'selected';} ?>><?php _e('不使用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('在图片被加载之前显示的加载效果', 'argon');?> , <a target="_blank" href="<?php bloginfo('template_url'); ?>/assets/vendor/svg-loaders"><?php _e('预览所有效果', 'argon');?></a></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-lightbox"><?php _e('图片放大浏览', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('是否启用图片放大浏览 (Fancybox)', 'argon');?></label></th>

						<td>

							<select name="argon_enable_fancybox" onchange="if (this.value == 'true'){setInputValue('argon_enable_zoomify','false');}">

								<?php $argon_enable_fancybox = get_option('argon_enable_fancybox'); ?>

								<option value="true" <?php if ($argon_enable_fancybox=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($argon_enable_fancybox=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，文章中图片被单击时会放大预览', 'argon');?></p>

						</td>

					</tr>

					<tr style="opacity: 0.5;" onclick="$(this).remove();$('.zoomify-old-settings').fadeIn(500);">

						<th><label><?php _e('展开旧版图片放大浏览 (Zoomify) 设置 ▼', 'argon');?></label></th>

						<td>

						</td>

					</tr>

					<style>

						.zoomify-old-settings{

							opacity: 0.65;

						}

						.zoomify-old-settings:hover{

							opacity: 1;

						}

					</style>

					<tr class="zoomify-old-settings" style="display: none;">

						<th><label><?php _e('是否启用旧版图片放大浏览 (Zoomify)', 'argon');?></label></th>

						<td>

							<select name="argon_enable_zoomify" onchange="if (this.value == 'true'){setInputValue('argon_enable_fancybox','false');}">

								<?php $argon_enable_zoomify = get_option('argon_enable_zoomify'); ?>

								<option value="true" <?php if ($argon_enable_zoomify=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($argon_enable_zoomify=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('自 Argon 1.1.0 版本后，图片缩放预览库由 Zoomify 更换为 Fancybox，如果您还想使用旧版图片预览，请开启此选项。注意: Zoomify 和 Fancybox 不能同时开启。', 'argon');?></p>

						</td>

					</tr>

					<tr class="zoomify-old-settings" style="display: none;">

						<th><label><?php _e('缩放动画长度', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_zoomify_duration" min="0" max="10000" value="<?php echo (get_option('argon_zoomify_duration') == '' ? '200' : get_option('argon_zoomify_duration')); ?>"/>	ms

							<p class="description"><?php _e('图片被单击后缩放到全屏动画的时间长度', 'argon');?></p>

						</td>

					</tr>

					<tr class="zoomify-old-settings" style="display: none;">

						<th><label><?php _e('缩放动画曲线', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_zoomify_easing" value="<?php echo (get_option('argon_zoomify_easing') == '' ? 'cubic-bezier(0.4,0,0,1)' : get_option('argon_zoomify_easing')); ?>"/>

							<p class="description">

								<?php _e('例：', 'argon');?> <code>ease</code> , <code>ease-in-out</code> , <code>ease-out</code> , <code>linear</code> , <code>cubic-bezier(0.4,0,0,1)</code><br/><?php _e('如果你不知道这是什么，参考', 'argon');?><a href="https://www.w3school.com.cn/cssref/pr_animation-timing-function.asp" target="_blank"><?php _e('这里', 'argon');?></a>

							</p>

						</td>

					</tr>

					<tr class="zoomify-old-settings" style="display: none;">

						<th><label><?php _e('图片最大缩放比例', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_zoomify_scale" min="0.01" max="1" step="0.01" value="<?php echo (get_option('argon_zoomify_scale') == '' ? '0.9' : get_option('argon_zoomify_scale')); ?>"/>

							<p class="description"><?php _e('图片相对于页面的最大缩放比例 (0 ~ 1 的小数)', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-pangu">Pangu.js</h3></th></tr>

					<tr>

						<th><label><?php _e('启用 Pangu.js (自动在中英文之间添加空格)', 'argon');?></label></th>

						<td>

							<select name="argon_enable_pangu">

								<?php $argon_enable_pangu = get_option('argon_enable_pangu'); ?>

								<option value="false" <?php if ($argon_enable_pangu=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

								<option value="article" <?php if ($argon_enable_pangu=='article'){echo 'selected';} ?>><?php _e('格式化文章内容', 'argon');?></option>

								<option value="shuoshuo" <?php if ($argon_enable_pangu=='shuoshuo'){echo 'selected';} ?>><?php _e('格式化说说', 'argon');?></option>

								<option value="comment" <?php if ($argon_enable_pangu=='comment'){echo 'selected';} ?>><?php _e('格式化评论区', 'argon');?></option>

								<option value="article|comment" <?php if ($argon_enable_pangu=='article|comment'){echo 'selected';} ?>><?php _e('格式化文章内容和评论区', 'argon');?></option>

								<option value="article|shuoshuo" <?php if ($argon_enable_pangu=='article|shuoshuo'){echo 'selected';} ?>><?php _e('格式化文章内容和说说', 'argon');?></option>

								<option value="shuoshuo|comment" <?php if ($argon_enable_pangu=='shuoshuo|comment'){echo 'selected';} ?>><?php _e('格式化说说和评论区', 'argon');?></option>

								<option value="article|shuoshuo|comment" <?php if ($argon_enable_pangu=='article|shuoshuo|comment'){echo 'selected';} ?>><?php _e('格式化文章内容、说说和评论区', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，会自动在中文和英文之间添加空格', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 15. 高级设置 ========== -->
				<tr><th class="subtitle"><h2 id="section-advanced"><?php _e('高级设置', 'argon');?></h2></th></tr>

				<tr><th class="subtitle"><h3 id="subsection-scripts"><?php _e('自定义脚本', 'argon');?></h3></th></tr>

					<tr>

						<th><label><strong style="color:#ff0000;"><?php _e('注意', 'argon');?></strong></label></th>

						<td>

							<p class="description"><strong style="color:#ff0000;"><?php _e('Argon 使用 pjax 方式加载页面 (无刷新加载) , 所以除非页面手动刷新，否则您的脚本只会被执行一次。', 'argon');?><br/>

							<?php _e('如果您想让每次页面跳转(加载新页面)时都执行脚本，请将脚本写入', 'argon');?> <code>window.pjaxLoaded</code> <?php _e('中', 'argon');?></strong> ，<?php _e('示例写法', 'argon');?>:

							<pre>

window.pjaxLoaded = function(){

	//<?php _e('页面每次跳转都会执行这里的代码', 'argon');?>

	//do something...

}

							</pre>

							<strong style="color:#ff0000;"><?php _e('当页面第一次载入时，', 'argon');?><code>window.pjaxLoaded</code> <?php _e('中的脚本不会执行，所以您可以手动执行', 'argon');?> <code>window.pjaxLoaded();</code> <?php _e('来让页面初次加载时也执行脚本', 'argon');?></strong></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('页头脚本', 'argon');?></label></th>

						<td>

							<textarea type="text" rows="15" cols="100" name="argon_custom_html_head"><?php echo htmlspecialchars(get_option('argon_custom_html_head')); ?></textarea>

							<p class="description"><?php _e('HTML , 支持 script 等标签', 'argon');?><br/><?php _e('插入到 body 之前', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('页尾脚本', 'argon');?></label></th>

						<td>

							<textarea type="text" rows="15" cols="100" name="argon_custom_html_foot"><?php echo htmlspecialchars(get_option('argon_custom_html_foot')); ?></textarea>

							<p class="description"><?php _e('HTML , 支持 script 等标签', 'argon');?><br/><?php _e('插入到 body 之后', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 16. 评论设置 ========== -->
					<tr><th class="subtitle"><h2 id="section-comment"><?php _e('评论设置', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-comment-pagination"><?php _e('评论分页', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('评论分页方式', 'argon');?></label></th>

						<td>

							<select name="argon_comment_pagination_type">

								<?php $argon_comment_pagination_type = get_option('argon_comment_pagination_type'); ?>

								<option value="feed" <?php if ($argon_comment_pagination_type=='feed'){echo 'selected';} ?>><?php _e('无限加载', 'argon');?></option>

								<option value="page" <?php if ($argon_comment_pagination_type=='page'){echo 'selected';} ?>><?php _e('页码', 'argon');?></option>

							</select>

							<p class="description">

								<?php _e('无限加载：点击 "加载更多" 按钮来加载更多评论。', 'argon');?><br/>

								<?php _e('页码：显示页码来分页。', 'argon');?><br/>

								<span class="go-to-wp-comment-settings"><?php _e('选择"无限加载"时，如果开启了评论分页，请将 Wordpress 的讨论设置设为 "默认显示<b>最后</b>一页，在每个页面顶部显示<b>新的</b>评论"。', 'argon');?> <a href="./options-discussion.php" target="_blank"><?php _e('去设置', 'argon');?>&gt;&gt;&gt;</a></span>

								<?php if (get_option('page_comments') == '1' && get_option('default_comments_page') != 'newest' && get_option('comment_order') != 'desc') {

									echo '<script>$(".go-to-wp-comment-settings").addClass("wrong-options");</script>';

								};?>

								<script>

									$("select[name='argon_comment_pagination_type']").change(function(){

										if ($(this).val() == 'feed') {

											$(".go-to-wp-comment-settings").addClass("using-feed");

										} else {

											$(".go-to-wp-comment-settings").removeClass("using-feed");

										}

									}).change();

								</script>

								<style>

									.go-to-wp-comment-settings a{

										display: none;

									}

									.go-to-wp-comment-settings.wrong-options.using-feed a{

										display: inline-block;

									}

									.go-to-wp-comment-settings.wrong-options.using-feed{

										color: #f00;

									}

								</style>

							</p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-comment-submit"><?php _e('发送评论', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('评论表情面板', 'argon');?></label></th>

						<td>

							<select name="argon_comment_emotion_keyboard">

								<?php $argon_comment_emotion_keyboard = get_option('argon_comment_emotion_keyboard'); ?>

								<option value="true" <?php if ($argon_comment_emotion_keyboard=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($argon_comment_emotion_keyboard=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后评论支持插入表情，会在评论输入框下显示表情键盘按钮。', 'argon');?><br/><a href="https://argon-docs.solstice23.top/#/emotions" target="_blank"><?php _e('如何添加新的表情或修改已有表情列表？', 'argon');?></a></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否隐藏 "昵称"、"邮箱"、"网站" 输入框', 'argon');?></label></th>

						<td>

							<select name="argon_hide_name_email_site_input">

								<?php $argon_hide_name_email_site_input = get_option('argon_hide_name_email_site_input'); ?>

								<option value="false" <?php if ($argon_hide_name_email_site_input=='false'){echo 'selected';} ?>><?php _e('不隐藏', 'argon');?></option>

								<option value="true" <?php if ($argon_hide_name_email_site_input=='true'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>

							</select>

							<p class="description"><?php _e('选项仅在 "设置-评论-评论作者必须填入姓名和电子邮件地址" 选项未勾选的前提下生效。如勾选了 "评论作者必须填入姓名和电子邮件地址"，则只有 "网站" 输入框会被隐藏。', 'argon');?>该</p>

						</td>

					</tr>

					<!-- ========== 17. 验证码设置 ========== -->
					<tr><th class="subtitle"><h2 id="section-captcha"><?php _e('验证码设置', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-captcha"><?php _e('验证码配置', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('全局验证码开关', 'argon');?></label></th>

						<td>

							<select name="argon_need_captcha">

								<?php $argon_need_captcha = get_option('argon_need_captcha', get_option('argon_comment_need_captcha', 'true')); ?>

								<option value="true" <?php if ($argon_need_captcha=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($argon_need_captcha=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('全局验证码开关，当各场景设置为"使用全局设置"时生效', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('验证码类型', 'argon');?></label></th>

						<td>

							<select name="argon_captcha_type">

								<?php $argon_captcha_type = get_option('argon_captcha_type', 'math'); ?>

								<option value="math" <?php if ($argon_captcha_type=='math'){echo 'selected';} ?>><?php _e('数学运算', 'argon');?></option>

								<option value="geetest" <?php if ($argon_captcha_type=='geetest'){echo 'selected';} ?>><?php _e('极验验证码', 'argon');?></option>

							</select>

							<p class="description"><?php _e('选择验证码类型。极验验证码需要配置相关参数。', 'argon');?></p>

						</td>

					</tr>

					<tr id="geetest_settings" style="<?php echo ($argon_captcha_type != 'geetest') ? 'display:none;' : ''; ?>">

						<th><label><?php _e('极验验证码 ID', 'argon');?></label></th>

						<td>

							<input type="text" name="argon_geetest_captcha_id" value="<?php echo esc_attr(get_option('argon_geetest_captcha_id', '')); ?>" class="regular-text" required />

							<p class="description"><?php _e('在极验后台获取的验证码 ID，必填项', 'argon');?></p>

						</td>

					</tr>

					<tr id="geetest_key_settings" style="<?php echo ($argon_captcha_type != 'geetest') ? 'display:none;' : ''; ?>">

						<th><label><?php _e('极验验证码 Key', 'argon');?></label></th>

						<td>

							<input type="text" name="argon_geetest_captcha_key" value="<?php echo esc_attr(get_option('argon_geetest_captcha_key', '')); ?>" class="regular-text" required />

							<p class="description"><?php _e('在极验后台获取的验证码私钥，必填项', 'argon');?></p>

						</td>

					</tr>

					<tr id="geetest_server_settings" style="<?php echo ($argon_captcha_type != 'geetest') ? 'display:none;' : ''; ?>">

						<th><label><?php _e('极验 API 服务器', 'argon');?></label></th>

						<td>

							<input type="url" name="argon_geetest_api_server" value="<?php echo esc_attr(get_option('argon_geetest_api_server', 'https://gcaptcha4.geetest.com')); ?>" class="regular-text" />

							<p class="description"><?php _e('极验 API 服务器地址，默认为官方服务器。请确保URL格式正确', 'argon');?></p>

						</td>

					</tr>

					<script>

						jQuery(document).ready(function($) {

							// 验证Geetest配置

							function validateGeetestConfig() {

								var captchaType = $('select[name="argon_captcha_type"]').val();

								if (captchaType === 'geetest') {

									var captchaId = $('input[name="argon_geetest_captcha_id"]').val().trim();

									var captchaKey = $('input[name="argon_geetest_captcha_key"]').val().trim();

									var apiServer = $('input[name="argon_geetest_api_server"]').val().trim();

									

									if (!captchaId || !captchaKey) {

										alert('<?php _e('使用极验验证码时，验证码 ID 和 Key 为必填项', 'argon'); ?>');

										return false;

									}

									

									// 验证API服务器URL格式

									if (apiServer && !apiServer.match(/^https?:\/\/.+/)) {

										alert('<?php _e('API 服务器地址格式不正确，请输入完整的URL', 'argon'); ?>');

										return false;

									}

								}

								return true;

							}

							

							$('select[name="argon_captcha_type"]').change(function() {

								if ($(this).val() === 'geetest') {

									$('#geetest_settings, #geetest_key_settings, #geetest_server_settings').show();

								} else {

									$('#geetest_settings, #geetest_key_settings, #geetest_server_settings').hide();

								}

							});

							

							// 表单提交时验证

							$('form').submit(function(e) {

								if (!validateGeetestConfig()) {

									e.preventDefault();

									return false;

								}

							});

						});

					</script>

					<tr>

						<th><label><?php _e('使用 Ajax 获取验证码', 'argon');?></label></th>

						<td>

							<select name="argon_get_captcha_by_ajax">

							<?php $argon_get_captcha_by_ajax = get_option('argon_get_captcha_by_ajax'); ?>

							<option value="false" <?php if ($argon_get_captcha_by_ajax=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							<option value="true" <?php if ($argon_get_captcha_by_ajax=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

						</select>

						<p class="description"><?php _e('如果使用了 CDN 缓存，验证码不会刷新，请开启此选项，否则请不要开启。', 'argon');?></p>

					</td>

				</tr>

					<tr><th class="subtitle"><h3 id="subsection-captcha-scenes"><?php _e('场景验证码', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('评论验证码', 'argon');?></label></th>

						<td>

							<select name="argon_comment_captcha_mode">

								<?php $argon_comment_captcha_mode = get_option('argon_comment_captcha_mode', 'global'); ?>

								<option value="global" <?php if ($argon_comment_captcha_mode=='global'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>

								<option value="enabled" <?php if ($argon_comment_captcha_mode=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="disabled" <?php if ($argon_comment_captcha_mode=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('评论是否需要验证码', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('TODO 催促验证码', 'argon');?></label></th>

						<td>

							<select name="argon_todo_captcha_mode">

								<?php $argon_todo_captcha_mode = get_option('argon_todo_captcha_mode', 'global'); ?>

								<option value="global" <?php if ($argon_todo_captcha_mode=='global'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>

								<option value="enabled" <?php if ($argon_todo_captcha_mode=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="disabled" <?php if ($argon_todo_captcha_mode=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('访客催促 TODO 时是否需要验证码', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('友链申请验证码', 'argon');?></label></th>

						<td>

							<select name="argon_friend_link_captcha">

								<?php $flink_captcha = get_option('argon_friend_link_captcha', 'global'); ?>

								<option value="global" <?php if ($flink_captcha=='global' || $flink_captcha=='same'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>

								<option value="enabled" <?php if ($flink_captcha=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="disabled" <?php if ($flink_captcha=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('友链申请表单是否需要验证码', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('反馈提交验证码', 'argon');?></label></th>

						<td>

							<select name="argon_feedback_captcha_mode">

								<?php $feedback_captcha = get_option('argon_feedback_captcha_mode', 'global'); ?>

								<option value="global" <?php if ($feedback_captcha=='global'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>

								<option value="enabled" <?php if ($feedback_captcha=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="disabled" <?php if ($feedback_captcha=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('问题反馈页面提交时是否需要验证码', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 18. 反馈与安全 ========== -->
					<tr><th class="subtitle"><h2 id="section-feedback-security"><?php _e('反馈与安全', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-feedback"><?php _e('反馈设置', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('新反馈通知管理员', 'argon');?></label></th>

						<td>

							<select name="argon_feedback_notify_admin">

								<?php $feedback_notify_admin = get_option('argon_feedback_notify_admin', 'true'); ?>

								<option value="true" <?php if ($feedback_notify_admin=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($feedback_notify_admin=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('收到新反馈时是否发送邮件通知管理员', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('反馈回复/完结通知用户', 'argon');?></label></th>

						<td>

							<select name="argon_feedback_notify_user">

								<?php $feedback_notify_user = get_option('argon_feedback_notify_user', 'true'); ?>

								<option value="true" <?php if ($feedback_notify_user=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($feedback_notify_user=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('反馈收到回复或被标记为已解决时，是否发送邮件通知用户。邮件中包含授权链接，用户可通过链接查看反馈详情（无论反馈是否公开）', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-rate-limit"><?php _e('速率限制', 'argon');?></h3></th></tr>

				<tr>

					<th><label><?php _e('全局 IP 黑名单', 'argon');?></label></th>

					<td>

						<textarea name="argon_global_blocked_ips" rows="8" cols="70" placeholder="<?php _e('每行一个 IP 地址', 'argon');?>"><?php echo get_option('argon_global_blocked_ips', ''); ?></textarea>

						<p class="description">
							<?php _e('被屏蔽的 IP 地址将无法提交评论、反馈、友链申请等操作。支持以下格式：', 'argon');?><br>
							• <?php _e('精确匹配：192.168.1.100', 'argon');?><br>
							• <?php _e('通配符：192.168.1.*', 'argon');?><br>
							• <?php _e('CIDR 格式：192.168.1.0/24', 'argon');?>
						</p>

					</td>

				</tr>

				<tr>

					<th><label><?php _e('友链申请频率限制', 'argon');?></label></th>

					<td>

						<input type="number" name="argon_flink_apply_limit" min="1" max="50" value="<?php echo get_option('argon_flink_apply_limit', '3'); ?>" style="width:80px;"/> <?php _e('次', 'argon');?> / 
						<input type="number" name="argon_flink_apply_period" min="60" max="86400" value="<?php echo get_option('argon_flink_apply_period', '3600'); ?>" style="width:100px;"/> <?php _e('秒', 'argon');?>

						<p class="description"><?php _e('限制单个用户在指定时间内提交友链申请的次数。默认：1小时内最多3次', 'argon');?></p>

					</td>

				</tr>

				<tr>

					<th><label><?php _e('反馈提交频率限制', 'argon');?></label></th>

					<td>

						<input type="number" name="argon_feedback_submit_limit" min="1" max="100" value="<?php echo get_option('argon_feedback_submit_limit', '5'); ?>" style="width:80px;"/> <?php _e('次', 'argon');?> / 
						<input type="number" name="argon_feedback_submit_period" min="60" max="86400" value="<?php echo get_option('argon_feedback_submit_period', '3600'); ?>" style="width:100px;"/> <?php _e('秒', 'argon');?>

						<p class="description"><?php _e('限制单个用户在指定时间内提交反馈的次数，防止恶意刷屏。默认：1小时内最多5次', 'argon');?></p>

					</td>

				</tr>

				<tr>

					<th><label><?php _e('反馈图片上传频率限制', 'argon');?></label></th>

					<td>

						<input type="number" name="argon_feedback_upload_limit" min="1" max="100" value="<?php echo get_option('argon_feedback_upload_limit', '20'); ?>" style="width:80px;"/> <?php _e('张', 'argon');?> / 
						<input type="number" name="argon_feedback_upload_period" min="60" max="86400" value="<?php echo get_option('argon_feedback_upload_period', '3600'); ?>" style="width:100px;"/> <?php _e('秒', 'argon');?>

						<p class="description"><?php _e('限制单个用户在指定时间内上传图片的数量。默认：1小时内最多20张', 'argon');?></p>

					</td>

				</tr>

				<tr>

					<th><label><?php _e('评论速率限制', 'argon');?></label></th>

					<td>

						<select name="argon_rate_limit_enable">

							<?php $argon_rate_limit_enable = get_option('argon_rate_limit_enable', 'true'); ?>

							<option value="false" <?php if ($argon_rate_limit_enable=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							<option value="true" <?php if ($argon_rate_limit_enable=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

						</select>

						<p class="description"><?php _e('按 IP 进行速率限制，防止短时间内频繁评论。', 'argon');?></p>

					</td>

				</tr>

				<tr>

					<th><label><?php _e('速率窗口（秒）', 'argon');?></label></th>

					<td>

						<input type="number" name="argon_rate_limit_window" value="<?php echo esc_attr(get_option('argon_rate_limit_window', 300)); ?>" class="regular-text" min="30" step="1" />

						<p class="description"><?php _e('统计窗口长度，建议不小于 30 秒。', 'argon');?></p>

					</td>

				</tr>

				<tr>

					<th><label><?php _e('窗口内最大次数', 'argon');?></label></th>

					<td>

						<input type="number" name="argon_rate_limit_max_count" value="<?php echo esc_attr(get_option('argon_rate_limit_max_count', 5)); ?>" class="regular-text" min="1" step="1" />

						<p class="description"><?php _e('在一个统计窗口内允许的最大评论次数。', 'argon');?></p>

					</td>

				</tr>

				<tr>

					<th><label><?php _e('两次最小间隔（秒）', 'argon');?></label></th>

					<td>

						<input type="number" name="argon_rate_limit_min_interval" value="<?php echo esc_attr(get_option('argon_rate_limit_min_interval', 10)); ?>" class="regular-text" min="0" step="1" />

						<p class="description"><?php _e('两次评论之间的最短时间间隔。', 'argon');?></p>

					</td>

				</tr>

					<!-- ========== 返回评论系统 ========== -->
					<tr><th class="subtitle"><h3 id="subsection-comment-features"><?php _e('评论功能', 'argon');?></h3></th></tr>

				<tr>

						<th><label><?php _e('是否允许在评论中使用 Markdown 语法', 'argon');?></label></th>

						<td>

							<select name="argon_comment_allow_markdown">

								<?php $argon_comment_allow_markdown = get_option('argon_comment_allow_markdown'); ?>

								<option value="true" <?php if ($argon_comment_allow_markdown=='true'){echo 'selected';} ?>><?php _e('允许', 'argon');?></option>

								<option value="false" <?php if ($argon_comment_allow_markdown=='false'){echo 'selected';} ?>><?php _e('不允许', 'argon');?></option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否允许评论者再次编辑评论', 'argon');?></label></th>

						<td>

							<select name="argon_comment_allow_editing">

								<?php $argon_comment_allow_editing = get_option('argon_comment_allow_editing'); ?>

								<option value="true" <?php if ($argon_comment_allow_editing=='true'){echo 'selected';} ?>><?php _e('允许', 'argon');?></option>

								<option value="false" <?php if ($argon_comment_allow_editing=='false'){echo 'selected';} ?>><?php _e('不允许', 'argon');?></option>

							</select>

							<p class="description"><?php _e('同一个评论者可以再次编辑评论。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否允许评论者使用悄悄话模式', 'argon');?></label></th>

						<td>

							<select name="argon_comment_allow_privatemode">

								<?php $argon_comment_allow_privatemode = get_option('argon_comment_allow_privatemode'); ?>

								<option value="false" <?php if ($argon_comment_allow_privatemode=='false'){echo 'selected';} ?>><?php _e('不允许', 'argon');?></option>

								<option value="true" <?php if ($argon_comment_allow_privatemode=='true'){echo 'selected';} ?>><?php _e('允许', 'argon');?></option>

							</select>

							<p class="description"><?php _e('评论者使用悄悄话模式发送的评论和其下的所有回复只有发送者和博主能看到。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否允许评论者接收评论回复邮件提醒', 'argon');?></label></th>

						<td>

							<select name="argon_comment_allow_mailnotice">

								<?php $argon_comment_allow_mailnotice = get_option('argon_comment_allow_mailnotice'); ?>

								<option value="false" <?php if ($argon_comment_allow_mailnotice=='false'){echo 'selected';} ?>><?php _e('不允许', 'argon');?></option>

								<option value="true" <?php if ($argon_comment_allow_mailnotice=='true'){echo 'selected';} ?>><?php _e('允许', 'argon');?></option>

							</select>

							<div style="margin-top: 15px;margin-bottom: 15px;">

								<label>

									<?php $argon_comment_mailnotice_checkbox_checked = get_option('argon_comment_mailnotice_checkbox_checked');?>

									<input type="checkbox" name="argon_comment_mailnotice_checkbox_checked" value="true" <?php if ($argon_comment_mailnotice_checkbox_checked=='true'){echo 'checked';}?>/>	<?php _e('评论时默认勾选 "启用邮件通知" 复选框', 'argon');?>

								</label>

							</div>

							<p class="description"><?php _e('评论者开启邮件提醒后，其评论有回复时会有邮件通知。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('允许评论者使用 QQ 头像', 'argon');?></label></th>

						<td>

							<select name="argon_comment_enable_qq_avatar">

								<?php $argon_comment_enable_qq_avatar = get_option('argon_comment_enable_qq_avatar'); ?>

								<option value="false" <?php if ($argon_comment_enable_qq_avatar=='false'){echo 'selected';} ?>><?php _e('不允许', 'argon');?></option>

								<option value="true" <?php if ($argon_comment_enable_qq_avatar=='true'){echo 'selected';} ?>><?php _e('允许', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，评论者可以使用 QQ 号代替邮箱输入，头像会根据评论者的 QQ 号获取。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-comment-spam-detection"><?php _e('AI 垃圾评论识别', 'argon');?></h3></th></tr>

					<!-- 基础设置 -->
					<tr>
						<th><label><?php _e('启用 AI 识别', 'argon');?></label></th>
						<td>
							<?php $argon_comment_spam_detection_enable = get_option('argon_comment_spam_detection_enable', 'false'); ?>
							<label>
								<input type="checkbox" name="argon_comment_spam_detection_enable" value="true" <?php if ($argon_comment_spam_detection_enable=='true'){echo 'checked';}?>/> 
								<?php _e('启用 AI 自动识别垃圾评论', 'argon');?>
							</label>
							<p class="description">
								<?php _e('开启后，将使用 AI 自动识别广告、反动、违法等垃圾评论。', 'argon');?><br/>
								<?php _e('注意：需要先在 "文章功能 - AI 文章摘要" 中配置 AI 服务商和 API 密钥。', 'argon');?>
							</p>
						</td>
					</tr>

					<!-- 检测策略 -->
					<tr>
						<th><label><?php _e('检测策略', 'argon');?></label></th>
						<td>
							<div style="margin-bottom: 15px;">
								<label style="display: block; margin-bottom: 10px; font-weight: 600;">
									<?php _e('实时检测模式', 'argon');?>
								</label>
								<select name="argon_comment_spam_detection_mode" style="width: 100%; max-width: 400px;">
									<?php $argon_comment_spam_detection_mode = get_option('argon_comment_spam_detection_mode', 'manual'); ?>
									<option value="manual" <?php if ($argon_comment_spam_detection_mode=='manual'){echo 'selected';} ?>><?php _e('关闭实时检测', 'argon');?></option>
									<option value="keyword" <?php if ($argon_comment_spam_detection_mode=='keyword'){echo 'selected';} ?>><?php _e('关键字必查', 'argon');?></option>
									<option value="sample" <?php if ($argon_comment_spam_detection_mode=='sample'){echo 'selected';} ?>><?php _e('智能抽查（推荐）', 'argon');?></option>
									<option value="all" <?php if ($argon_comment_spam_detection_mode=='all'){echo 'selected';} ?>><?php _e('全量检测', 'argon');?></option>
								</select>
								<p class="description" style="margin-top: 8px;">
									<strong><?php _e('关闭实时检测', 'argon');?>：</strong><?php _e('只能使用手动批量扫描', 'argon');?><br/>
									<strong><?php _e('关键字必查', 'argon');?>：</strong><?php _e('只检测触发关键字的评论，精准防护，成本最低', 'argon');?><br/>
									<strong><?php _e('智能抽查', 'argon');?>：</strong><?php _e('根据用户信誉动态调整检测概率，平衡成本与效果', 'argon');?><br/>
									<strong><?php _e('全量检测', 'argon');?>：</strong><?php _e('每条新评论都检测，防护最严密但 API 消耗最高', 'argon');?>
								</p>
							</div>

							<div>
								<label style="display: block; margin-bottom: 10px; font-weight: 600;">
									<?php _e('智能抽查概率', 'argon');?>
								</label>
								<input type="number" name="argon_comment_spam_detection_sample_rate" min="1" max="100" value="<?php echo get_option('argon_comment_spam_detection_sample_rate', '20'); ?>" style="width: 80px;"/> %
								<p class="description" style="margin-top: 8px;">
									<?php _e('智能抽查模式的基础概率，默认 20%。系统会根据用户历史通过率自动调整：', 'argon');?><br/>
									<?php _e('• 信誉好的用户降低检测（最低 5%）', 'argon');?><br/>
									<?php _e('• 信誉差的用户提高检测（最高 80%）', 'argon');?><br/>
									<?php _e('• 关键字必查模式下此设置无效', 'argon');?>
								</p>
							</div>
						</td>
					</tr>

					<!-- 关键字管理 -->
					<tr>
						<th><label><?php _e('关键字管理', 'argon');?></label></th>
						<td>
							<div style="margin-bottom: 15px;">
								<?php $argon_comment_spam_detection_ai_learn = get_option('argon_comment_spam_detection_ai_learn', 'false'); ?>
								<label style="display: block; margin-bottom: 10px;">
									<input type="checkbox" name="argon_comment_spam_detection_ai_learn" value="true" <?php if ($argon_comment_spam_detection_ai_learn=='true'){echo 'checked';}?>/> 
									<strong><?php _e('启用 AI 主动学习关键字', 'argon');?></strong>
								</label>
								<p class="description" style="margin: 0 0 15px 24px;">
									<?php _e('开启后，系统会分析管理员的审核决策，自动提取并优化关键字：', 'argon');?><br/>
									<?php _e('• 当 AI 判断与管理员决策不一致时，使用 AI 提取关键词', 'argon');?><br/>
									<?php _e('• 统计关键词在垃圾评论和正常评论中的出现频率', 'argon');?><br/>
									<?php _e('• 置信度 > 70% 且出现 >= 3 次的关键词自动加入下方列表', 'argon');?>
								</p>
							</div>
							
							<label style="display: block; margin-bottom: 8px; font-weight: 600;">
								<?php _e('手动添加关键字', 'argon');?>
							</label>
							<textarea rows="8" cols="70" name="argon_comment_spam_detection_keywords" placeholder="<?php _e('每行一个关键字或短语', 'argon');?>" style="width: 100%; max-width: 800px;"><?php echo get_option('argon_comment_spam_detection_keywords', ''); ?></textarea>
							<p class="description">
								<?php _e('每行一个关键字或短语，支持中英文。当评论的用户名或内容包含这些关键字时：', 'argon');?><br/>
								<?php _e('• 关键字必查模式：立即进行 AI 检测', 'argon');?><br/>
								<?php _e('• 智能抽查/全量检测模式：提高检测优先级', 'argon');?><br/>
								<?php _e('常见示例：', 'argon');?> <code>加微信</code>, <code>联系QQ</code>, <code>点击链接</code>, <code>免费领取</code>, <code>刷单</code>, <code>兼职</code>
							</p>
							
							<?php 
							$learned_keywords = get_option('argon_comment_spam_learned_keywords', []);
							if (!empty($learned_keywords) && is_array($learned_keywords)) {
								echo '<div style="margin-top: 20px; padding: 15px; background: #f7fafc; border-radius: 4px; border-left: 4px solid var(--themecolor, #5e72e4);">';
								echo '<h4 style="margin: 0 0 10px 0; color: #2d3748;">' . __('AI 学习到的关键字', 'argon') . '</h4>';
								echo '<div style="max-height: 200px; overflow-y: auto;">';
								echo '<table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 4px; overflow: hidden;">';
								echo '<thead><tr style="background: #edf2f7;">';
								echo '<th style="text-align: left; padding: 10px; font-weight: 600;">' . __('关键字', 'argon') . '</th>';
								echo '<th style="text-align: center; padding: 10px; font-weight: 600;">' . __('垃圾', 'argon') . '</th>';
								echo '<th style="text-align: center; padding: 10px; font-weight: 600;">' . __('正常', 'argon') . '</th>';
								echo '<th style="text-align: center; padding: 10px; font-weight: 600;">' . __('置信度', 'argon') . '</th>';
								echo '<th style="text-align: center; padding: 10px; font-weight: 600;">' . __('状态', 'argon') . '</th>';
								echo '</tr></thead><tbody>';
								
								// 按置信度排序
								uasort($learned_keywords, function($a, $b) {
									return $b['confidence'] <=> $a['confidence'];
								});
								
								$current_keywords_array = array_filter(array_map('trim', explode("\n", get_option('argon_comment_spam_detection_keywords', ''))));
								
								foreach ($learned_keywords as $keyword => $stats) {
									$confidence = round($stats['confidence'] * 100);
									$color = $confidence >= 70 ? '#48bb78' : ($confidence >= 50 ? '#ed8936' : '#a0aec0');
									$is_added = in_array($keyword, $current_keywords_array);
									
									echo '<tr style="border-bottom: 1px solid #e2e8f0;">';
									echo '<td style="padding: 10px;"><code style="background: #edf2f7; padding: 2px 6px; border-radius: 3px;">' . esc_html($keyword) . '</code></td>';
									echo '<td style="text-align: center; padding: 10px;">' . $stats['spam_count'] . '</td>';
									echo '<td style="text-align: center; padding: 10px;">' . $stats['normal_count'] . '</td>';
									echo '<td style="text-align: center; padding: 10px;"><span style="color: ' . $color . '; font-weight: 600;">' . $confidence . '%</span></td>';
									echo '<td style="text-align: center; padding: 10px;">';
									if ($is_added) {
										echo '<span style="color: #48bb78; font-weight: 600;">✓ ' . __('已添加', 'argon') . '</span>';
									} elseif ($confidence >= 70 && $stats['spam_count'] >= 3) {
										echo '<span style="color: #ed8936; font-weight: 600;">⚠ ' . __('待添加', 'argon') . '</span>';
									} else {
										echo '<span style="color: #a0aec0;">○ ' . __('观察中', 'argon') . '</span>';
									}
									echo '</td>';
									echo '</tr>';
								}
								echo '</tbody></table></div>';
								echo '<p class="description" style="margin: 10px 0 0 0; font-size: 12px;">';
								echo __('• 绿色：置信度 ≥ 70%，已自动添加到关键字列表', 'argon') . '<br/>';
								echo __('• 橙色：置信度 50-70%，观察中', 'argon') . '<br/>';
								echo __('• 灰色：置信度 < 50%，数据不足', 'argon');
								echo '</p>';
								echo '</div>';
							}
							?>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('抽查基础概率', 'argon');?></label></th>
						<td>
							<input type="number" name="argon_comment_spam_detection_sample_rate" min="1" max="100" value="<?php echo get_option('argon_comment_spam_detection_sample_rate', '20'); ?>" style="width: 80px;"/> %
							<p class="description">
								<?php _e('智能抽查模式的基础概率，默认 20%。系统会根据用户历史通过率自动调整：', 'argon');?><br/>
								<?php _e('• 信誉好的用户降低检测（最低 5%）', 'argon');?><br/>
								<?php _e('• 信誉差的用户提高检测（最高 80%）', 'argon');?><br/>
								<?php _e('注意：关键字必查模式下此设置无效', 'argon');?>
							</p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('自动处理方式', 'argon');?></label></th>
						<td>
							<select name="argon_comment_spam_detection_auto_action">
								<?php $argon_comment_spam_detection_auto_action = get_option('argon_comment_spam_detection_auto_action', 'trash'); ?>
								<option value="trash" <?php if ($argon_comment_spam_detection_auto_action=='trash'){echo 'selected';} ?>><?php _e('自动移入回收站', 'argon');?></option>
								<option value="hold" <?php if ($argon_comment_spam_detection_auto_action=='hold'){echo 'selected';} ?>><?php _e('标记为待审核', 'argon');?></option>
								<option value="mark" <?php if ($argon_comment_spam_detection_auto_action=='mark'){echo 'selected';} ?>><?php _e('仅标记不处理', 'argon');?></option>
							</select>
							<p class="description">
								<strong><?php _e('自动移入回收站', 'argon');?>：</strong><?php _e('检测到垃圾评论立即移入回收站（推荐）', 'argon');?><br/>
								<strong><?php _e('标记为待审核', 'argon');?>：</strong><?php _e('将垃圾评论状态改为待审核，需要管理员手动审核', 'argon');?><br/>
								<strong><?php _e('仅标记不处理', 'argon');?>：</strong><?php _e('只在评论元数据中标记，不改变评论状态', 'argon');?>
							</p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('检测范围', 'argon');?></label></th>
						<td>
							<?php $argon_comment_spam_detection_exclude_logged_in = get_option('argon_comment_spam_detection_exclude_logged_in', 'true'); ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="argon_comment_spam_detection_exclude_logged_in" value="true" <?php if ($argon_comment_spam_detection_exclude_logged_in=='true'){echo 'checked';}?>/> 
								<?php _e('跳过已登录用户的评论', 'argon');?>
							</label>
							<p class="description"><?php _e('开启后，已登录用户（包括管理员、编辑等）的评论不会被检测。', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('白名单', 'argon');?></label></th>
						<td>
							<textarea rows="4" cols="70" name="argon_comment_spam_detection_whitelist" placeholder="<?php _e('每行一个邮箱或 IP 地址', 'argon');?>"><?php echo get_option('argon_comment_spam_detection_whitelist', ''); ?></textarea>
							<p class="description">
								<?php _e('白名单中的邮箱或 IP 地址的评论不会被检测，每行一个。', 'argon');?><br/>
								<?php _e('示例：user@example.com 或 192.168.1.1', 'argon');?>
							</p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('Prompt 模式', 'argon');?></label></th>
						<td>
							<select name="argon_comment_spam_detection_prompt_mode" id="argon-prompt-mode-select">
								<?php $prompt_mode = get_option('argon_comment_spam_detection_prompt_mode', 'standard'); ?>
								<option value="minimal" <?php if ($prompt_mode=='minimal'){echo 'selected';} ?>><?php _e('极简模式', 'argon');?></option>
								<option value="standard" <?php if ($prompt_mode=='standard'){echo 'selected';} ?>><?php _e('标准模式（推荐）', 'argon');?></option>
								<option value="enhanced" <?php if ($prompt_mode=='enhanced'){echo 'selected';} ?>><?php _e('增强模式', 'argon');?></option>
								<option value="custom" <?php if ($prompt_mode=='custom'){echo 'selected';} ?>><?php _e('自定义 Prompt', 'argon');?></option>
							</select>
							<p class="description">
								<strong><?php _e('极简模式', 'argon');?></strong>: <?php _e('省 token，快速检测，理由简短（约 100-150 tokens）', 'argon');?><br/>
								<strong><?php _e('标准模式', 'argon');?></strong>: <?php _e('平衡准确性和成本，包含置信度和处理建议（约 200-300 tokens）', 'argon');?><br/>
								<strong><?php _e('增强模式', 'argon');?></strong>: <?php _e('多维度分析，更准确，包含综合分析（约 300-500 tokens）', 'argon');?><br/>
								<strong><?php _e('自定义 Prompt', 'argon');?></strong>: <?php _e('使用下方自定义的 Prompt', 'argon');?>
							</p>
							
							<div id="argon-custom-prompt-container" style="margin-top: 15px; <?php echo ($prompt_mode !== 'custom') ? 'display: none;' : ''; ?>">
								<label style="display: block; margin-bottom: 8px; font-weight: 600;">
									<?php _e('自定义检测提示词', 'argon');?>
								</label>
								<select id="argon-spam-prompt-template" style="margin-bottom: 10px; width: 100%; max-width: 400px;">
									<option value=""><?php _e('选择预设模板...', 'argon');?></option>
									<option value="default"><?php _e('默认模板（平衡）', 'argon');?></option>
									<option value="strict"><?php _e('严格模式', 'argon');?></option>
									<option value="loose"><?php _e('宽松模式', 'argon');?></option>
									<option value="minimal"><?php _e('极简模式（省 token）', 'argon');?></option>
								</select>
								<textarea rows="6" cols="70" id="argon-spam-prompt-textarea" name="argon_comment_spam_detection_prompt" style="width: 100%; max-width: 800px;"><?php echo get_option('argon_comment_spam_detection_prompt', '你是一个专业的内容审核助手。请判断以下评论是否为垃圾评论。垃圾评论包括但不限于：广告推广、反动言论、错误政治观点、时政敏感内容、违法信息、色情暴力、恶意攻击等。

请仅返回 JSON 格式：{"is_spam": true/false, "reason": "理由(25字以内)"}

如果是正常评论，reason 填写 "正常"。如果是垃圾评论，简要说明原因。'); ?></textarea>
								<p class="description"><?php _e('自定义 AI 识别垃圾评论时使用的提示词。建议保持简洁以降低 token 消耗。', 'argon');?></p>
							</div>
							
							<script>
							jQuery(document).ready(function($) {
								// Prompt 模式切换
								$('#argon-prompt-mode-select').on('change', function() {
									const mode = $(this).val();
									if (mode === 'custom') {
										$('#argon-custom-prompt-container').slideDown(200);
									} else {
										$('#argon-custom-prompt-container').slideUp(200);
									}
								});
								
								// 预设模板
								const templates = {
									'default': '你是一个专业的内容审核助手。请判断以下评论是否为垃圾评论。垃圾评论包括但不限于：广告推广、反动言论、错误政治观点、时政敏感内容、违法信息、色情暴力、恶意攻击等。\n\n请仅返回 JSON 格式：{"is_spam": true/false, "reason": "理由(25字以内)"}\n\n如果是正常评论，reason 填写 "正常"。如果是垃圾评论，简要说明原因。',
									'strict': '你是严格的内容审核助手。判断评论是否为垃圾评论，包括：广告、推广、营销、反动、政治敏感、违法、色情、暴力、恶意攻击、无意义灌水、重复内容等。\n\n返回 JSON：{"is_spam": true/false, "reason": "理由(25字内)"}\n\n有任何可疑迹象都标记为垃圾。',
									'loose': '判断评论是否为明显的垃圾评论：广告推广、违法内容、色情暴力。\n\n返回 JSON：{"is_spam": true/false, "reason": "理由(25字内)"}\n\n只标记明显的垃圾评论，对正常讨论保持宽容。',
									'minimal': '判断是否垃圾评论（广告/违法/色情/暴力）。返回：{"is_spam": true/false, "reason": "理由(20字内)"}'
								};
								
								$('#argon-spam-prompt-template').on('change', function() {
									const template = $(this).val();
									if (template && templates[template]) {
										$('#argon-spam-prompt-textarea').val(templates[template]);
									}
								});
							});
							</script>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('自动处理阈值', 'argon');?></label></th>
						<td>
							<label style="display: block; margin-bottom: 8px;">
								<?php _e('置信度阈值', 'argon');?>: 
								<input type="number" name="argon_comment_spam_detection_confidence_threshold" min="0" max="100" step="5" value="<?php echo get_option('argon_comment_spam_detection_confidence_threshold', '85'); ?>" style="width: 80px;"/> %
							</label>
							<p class="description">
								<?php _e('只有 AI 判断的置信度高于此阈值时，才会自动处理垃圾评论。', 'argon');?><br/>
								<?php _e('建议值：小型博客 90%，中型博客 85%，大型博客 80%', 'argon');?><br/>
								<?php _e('置信度低于阈值的评论会被标记为待审核，由管理员人工判断', 'argon');?>
							</p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('手动批量检测', 'argon');?></label></th>
						<td>
							<div style="margin-bottom: 15px;">
								<button type="button" class="button button-primary" id="argon-spam-detection-scan-all"><?php _e('全站评论扫描', 'argon');?></button>
								<button type="button" class="button" id="argon-spam-detection-scan-pending" style="margin-left: 10px;"><?php _e('仅扫描待审核评论', 'argon');?></button>
								<span id="argon-spam-detection-status" style="margin-left: 15px; color: #666;"></span>
							</div>
							<div id="argon-spam-detection-progress" style="display: none; margin-bottom: 15px;">
								<div style="background: #f0f0f1; border-radius: 4px; height: 24px; position: relative; overflow: hidden;">
									<div id="argon-spam-detection-progress-bar" style="background: var(--themecolor, #5e72e4); height: 100%; width: 0%; transition: width 0.3s;"></div>
									<span id="argon-spam-detection-progress-text" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: 600; color: #333;"></span>
								</div>
							</div>
							<div id="argon-spam-detection-results" style="display: none; margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto;">
								<div id="argon-spam-detection-results-content"></div>
							</div>
							<p class="description">
								<?php _e('手动扫描现有评论，识别垃圾评论。扫描不包括已在回收站的评论。', 'argon');?><br/>
								<?php _e('检测到的垃圾评论会被标记，您可以选择性地移入回收站。', 'argon');?>
							</p>
							<script>
							jQuery(document).ready(function($) {
								let isScanning = false;
								
								function startScan(scanType) {
									if (isScanning) {
										alert('<?php _e('正在扫描中，请稍候', 'argon');?>');
										return;
									}
									
									if (!confirm('<?php _e('确定要开始扫描吗？这将一次性检测所有评论，可能需要较长时间。', 'argon');?>')) {
										return;
									}
									
									isScanning = true;
									$('#argon-spam-detection-scan-all, #argon-spam-detection-scan-pending').prop('disabled', true);
									$('#argon-spam-detection-status').text('<?php _e('正在扫描...请耐心等待', 'argon');?>');
									$('#argon-spam-detection-progress').show();
									$('#argon-spam-detection-results').hide();
									$('#argon-spam-detection-progress-bar').css('width', '50%');
									$('#argon-spam-detection-progress-text').text('<?php _e('处理中...', 'argon');?>');
									
									$.post(ajaxurl, {
										action: 'argon_spam_detection_scan',
										nonce: '<?php echo wp_create_nonce('argon_spam_detection_scan'); ?>',
										scan_type: scanType
									}, function(response) {
										isScanning = false;
										$('#argon-spam-detection-scan-all, #argon-spam-detection-scan-pending').prop('disabled', false);
										$('#argon-spam-detection-progress-bar').css('width', '100%');
										$('#argon-spam-detection-progress-text').text('100%');
										
										if (response.success) {
											const data = response.data;
											$('#argon-spam-detection-status').text('<?php _e('扫描完成', 'argon');?> - <?php _e('共检测', 'argon');?> ' + data.total + ' <?php _e('条评论', 'argon');?>');
											displayResults(data.results);
										} else {
											$('#argon-spam-detection-status').text('<?php _e('扫描失败', 'argon');?>: ' + (response.data || ''));
											$('#argon-spam-detection-progress').hide();
										}
									}).fail(function(xhr, status, error) {
										isScanning = false;
										$('#argon-spam-detection-scan-all, #argon-spam-detection-scan-pending').prop('disabled', false);
										$('#argon-spam-detection-status').text('<?php _e('请求失败', 'argon');?>: ' + error);
										$('#argon-spam-detection-progress').hide();
									});
								}
								
								function displayResults(results) {
									if (!results || results.length === 0) {
										$('#argon-spam-detection-results-content').html('<p style="color: #46b450; font-weight: 600;"><span class="dashicons dashicons-yes-alt" style="font-size: 20px; vertical-align: middle;"></span> <?php _e('未发现垃圾评论，所有评论都是正常的！', 'argon');?></p>');
										$('#argon-spam-detection-results').show();
										return;
									}
									
									let html = '<p style="color: #dc3232; font-weight: 600; margin-bottom: 15px;"><span class="dashicons dashicons-warning" style="font-size: 20px; vertical-align: middle;"></span> <?php _e('发现', 'argon');?> ' + results.length + ' <?php _e('条疑似垃圾评论', 'argon');?></p>';
									html += '<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;"><thead><tr><th style="width: 80px;"><?php _e('评论 ID', 'argon');?></th><th style="width: 120px;"><?php _e('作者', 'argon');?></th><th><?php _e('内容', 'argon');?></th><th style="width: 150px;"><?php _e('识别理由', 'argon');?></th><th style="width: 100px;"><?php _e('操作', 'argon');?></th></tr></thead><tbody>';
									
									results.forEach(function(item) {
										html += '<tr data-comment-id="' + item.comment_id + '">';
										html += '<td>' + item.comment_id + '</td>';
										html += '<td>' + $('<div>').text(item.author).html() + '</td>';
										html += '<td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + $('<div>').text(item.content).html() + '">' + $('<div>').text(item.content).html() + '</td>';
										html += '<td>' + $('<div>').text(item.reason).html() + '</td>';
										html += '<td><button type="button" class="button button-small argon-spam-trash-btn" data-comment-id="' + item.comment_id + '"><?php _e('移入回收站', 'argon');?></button></td>';
										html += '</tr>';
									});
									
									html += '</tbody></table>';
									$('#argon-spam-detection-results-content').html(html);
									$('#argon-spam-detection-results').show();
								}
								
								$(document).on('click', '.argon-spam-trash-btn', function() {
									const btn = $(this);
									const commentId = btn.data('comment-id');
									
									if (!confirm('<?php _e('确定要将此评论移入回收站吗？', 'argon');?>')) {
										return;
									}
									
									btn.prop('disabled', true).text('<?php _e('处理中...', 'argon');?>');
									
									$.post(ajaxurl, {
										action: 'argon_spam_detection_trash_comment',
										nonce: '<?php echo wp_create_nonce('argon_spam_detection_trash_comment'); ?>',
										comment_id: commentId
									}, function(response) {
										if (response.success) {
											btn.closest('tr').fadeOut(300, function() {
												$(this).remove();
												if ($('#argon-spam-detection-results-content tbody tr').length === 0) {
													$('#argon-spam-detection-results-content').html('<p style="color: #46b450; font-weight: 600;"><span class="dashicons dashicons-yes-alt" style="font-size: 20px; vertical-align: middle;"></span> <?php _e('所有标记的垃圾评论已处理', 'argon');?></p>');
												}
											});
										} else {
											alert('<?php _e('操作失败', 'argon');?>: ' + (response.data || ''));
											btn.prop('disabled', false).text('<?php _e('移入回收站', 'argon');?>');
										}
									});
								});
								
								$('#argon-spam-detection-scan-all').on('click', function() {
									startScan('all');
								});
								
								$('#argon-spam-detection-scan-pending').on('click', function() {
									startScan('pending');
								});
							});
							</script>
						</td>
					</tr>

					<tr><th class="subtitle"><h3 id="subsection-comment-appearance"><?php _e('评论区外观', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('评论头像垂直位置', 'argon');?></label></th>

						<td>

							<select name="argon_comment_avatar_vcenter">

								<?php $argon_comment_avatar_vcenter = get_option('argon_comment_avatar_vcenter'); ?>

								<option value="false" <?php if ($argon_comment_avatar_vcenter=='false'){echo 'selected';} ?>><?php _e('居上', 'argon');?></option>

								<option value="true" <?php if ($argon_comment_avatar_vcenter=='true'){echo 'selected';} ?>><?php _e('居中', 'argon');?></option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('谁可以查看评论编辑记录', 'argon');?></label></th>

						<td>

							<select name="argon_who_can_visit_comment_edit_history">

								<?php $argon_who_can_visit_comment_edit_history = get_option('argon_who_can_visit_comment_edit_history'); ?>

								<option value="admin" <?php if ($argon_who_can_visit_comment_edit_history=='admin'){echo 'selected';} ?>><?php _e('只有博主', 'argon');?></option>

								<option value="commentsender" <?php if ($argon_who_can_visit_comment_edit_history=='commentsender'){echo 'selected';} ?>><?php _e('评论发送者和博主', 'argon');?></option>

								<option value="everyone" <?php if ($argon_who_can_visit_comment_edit_history=='everyone'){echo 'selected';} ?>><?php _e('任何人', 'argon');?></option>

							</select>

							<p class="description"><?php _e('点击评论右侧的 "已编辑" 标记来查看编辑记录', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('开启评论置顶功能', 'argon');?></label></th>

						<td>

							<select name="argon_enable_comment_pinning">

								<?php $argon_enable_comment_pinning = get_option('argon_enable_comment_pinning'); ?>

								<option value="false" <?php if ($argon_enable_comment_pinning=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_comment_pinning=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，博主将可以置顶评论。已置顶的评论将会在评论区顶部显示。如果关闭，评论将以正常顺序显示。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('评论点赞', 'argon');?></label></th>

						<td>

							<select name="argon_enable_comment_upvote">

								<?php $argon_enable_comment_upvote = get_option('argon_enable_comment_upvote'); ?>

								<option value="false" <?php if ($argon_enable_comment_upvote=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_comment_upvote=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，每一条评论的头像下方会出现点赞按钮', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('评论者 UA 显示', 'argon');?></label></th>

						<td>

							<select name="argon_comment_ua">

								<?php $argon_comment_ua = get_option('argon_comment_ua'); ?>

								<option value="hidden" <?php if ($argon_comment_ua=='hidden'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

								<option value="browser" <?php if ($argon_comment_ua=='browser'){echo 'selected';} ?>><?php _e('浏览器', 'argon');?></option>

								<option value="browser,version" <?php if ($argon_comment_ua=='browser,version'){echo 'selected';} ?>><?php _e('浏览器+版本号', 'argon');?></option>

								<option value="platform,browser,version" <?php if ($argon_comment_ua=='platform,browser,version'){echo 'selected';} ?>><?php _e('平台+浏览器+版本号', 'argon');?></option>

								<option value="platform,browser" <?php if ($argon_comment_ua=='platform,browser'){echo 'selected';} ?>><?php _e('平台+浏览器', 'argon');?></option>

								<option value="platform" <?php if ($argon_comment_ua=='platform'){echo 'selected';} ?>><?php _e('平台', 'argon');?></option>

							</select>

							<p class="description"><?php _e('设置是否在评论区显示评论者 UA 及显示哪些部分', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('在子评论中显示被回复者用户名', 'argon');?></label></th>

						<td>

							<select name="argon_show_comment_parent_info">

								<?php $argon_show_comment_parent_info = get_option('argon_show_comment_parent_info'); ?>

								<option value="true" <?php if ($argon_show_comment_parent_info=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

								<option value="false" <?php if ($argon_show_comment_parent_info=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，被回复的评论者昵称会显示在子评论中，鼠标移上后会高亮被回复的评论', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('折叠过长评论', 'argon');?></label></th>

						<td>

							<select name="argon_fold_long_comments">

								<?php $argon_fold_long_comments = get_option('argon_fold_long_comments'); ?>

								<option value="false" <?php if ($argon_fold_long_comments=='false'){echo 'selected';} ?>><?php _e('不折叠', 'argon');?></option>

								<option value="true" <?php if ($argon_fold_long_comments=='true'){echo 'selected';} ?>><?php _e('折叠', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，过长的评论会被折叠，需要手动展开', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label>Gravatar CDN</label></th>

						<td>

							<input type="text" class="regular-text" name="argon_gravatar_cdn" value="<?php echo get_option('argon_gravatar_cdn' , ''); ?>"/>

							<p class="description"><?php _e('使用 CDN 来加速 Gravatar 在某些地区的访问，填写 CDN 地址，留空则不使用。', 'argon');?><br/><?php _e('在中国速度较快的一些 CDN :', 'argon');?><code onclick="$('input[name=\'argon_gravatar_cdn\']').val(this.innerText);" style="cursor: pointer;">gravatar.pho.ink/avatar/</code> , <code onclick="$('input[name=\'argon_gravatar_cdn\']').val(this.innerText);" style="cursor: pointer;">cdn.v2ex.com/gravatar/</code> , <code onclick="$('input[name=\'argon_gravatar_cdn\']').val(this.innerText);" style="cursor: pointer;">dn-qiniu-avatar.qbox.me/avatar/</code></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('评论文字头像', 'argon');?></label></th>

						<td>

							<select name="argon_text_gravatar">

								<?php $argon_text_gravatar = get_option('argon_text_gravatar'); ?>

								<option value="false" <?php if ($argon_text_gravatar=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

								<option value="true" <?php if ($argon_text_gravatar=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('在评论者没有设置 Gravatar 时自动生成文字头像，头像颜色由邮箱哈希计算。生成时会在 Console 中抛出 404 错误，但没有影响。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-search"><?php _e('搜索', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('启用过滤器', 'argon');?></label></th>

						<td>	

							<select name="argon_enable_search_filters">

								<?php $argon_enable_search_filters = get_option('argon_enable_search_filters', 'true'); ?>

								<option value="true" <?php if ($argon_enable_search_filters=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="false" <?php if ($argon_enable_search_filters=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，将会在搜索结果界面显示一个过滤器，支持搜索说说及其他类型文章', 'argon');?></p>

						</td>

					</tr>

					<script>	

						$("select[name='argon_enable_search_filters']").change(function(){

							if ($(this).val() == 'true') {

								$(".argon-search-filters-type").css('display', '');

							} else {

								$(".argon-search-filters-type").css('display', 'none');

							}

						}).change();

					</script>

					<tr class="argon-search-filters-type">

						<th><label><?php _e('过滤器类型', 'argon');?></label></th>

						<style>

							.search-filters-container {

								margin-top: 10px;

								margin-bottom: 15px;

								width: calc(100% - 250px);

							}

							@media screen and (max-width:960px){

								.search-filters-container {

									width: 100%;

								}

							}

							#search_filters_active, #search_filters_inactive {

								background: rgba(0, 0, 0, .05);

								padding: 10px 15px;

								margin-top: 10px;

								border-radius: 5px;

								padding-bottom: 0;

								min-height: 48px;

								box-sizing: border-box;

							}

							.search-filter-item {

								background: #fafafa;

								width: max-content !important;

								height: max-content !important;

								border-radius: 100px;

								padding: 5px 15px;

								cursor: move;

								display: inline-block;

								margin-right: 8px;

								margin-bottom: 10px;

							}

							#search_filters_active .search-filter-item:before {

								content: '⬜ ';

							}

							#search_filters_active .search-filter-item.active:before {

								content: '☑️ ';

							}

						</style>

						<td>

							<input type="text" class="regular-text" name="argon_search_filters_type" value="<?php echo get_option('argon_search_filters_type', '*post,*page,shuoshuo'); ?>" style="display: none;"/>

							<?php _e('拖动来自定义启用的过滤器，单击来切换默认勾选状态', 'argon');?>

							<div class="search-filters-container">

								<?php _e('启用', 'argon');?>

								<div id="search_filters_active"></div>

							</div>

							<div class="search-filters-container">

								<?php _e('不启用', 'argon');?>

								<div id="search_filters_inactive">

									<?php 

										$all_post_types= get_post_types(array(

											'public'   => true,

										), 'objects');

										foreach ($all_post_types as $post_type) {

											if ($post_type -> name == 'attachment'){

												continue;

											}

											echo '<div class="search-filter-item" filter-name="'. $post_type -> name .'">'. $post_type -> label .'</div>';

										}

									?>

								</div>

							</div>

						</td>

						<script>

							function updateSearchFilters(){

								let searchFilters = "";

								$("#search_filters_active .search-filter-item").each(function(index, item) {

									if (index != 0){ searchFilters += ",";}

									if ($(item).hasClass('active')){ searchFilters += "*"; }

									searchFilters += item.getAttribute("filter-name");

								});

								$("input[name='argon_search_filters_type']").val(searchFilters);

							}

							!function(){

								let searchFilters = $("input[name='argon_search_filters_type']").val().split(",");

								for (let filter of searchFilters){

									if (filter[0] == "*"){

										$(".search-filter-item[filter-name='"+ filter.substring(1) +"']").addClass('active');

										filter = filter.substring(1);

									}

									let itemDiv = $("#search_filters_inactive .search-filter-item[filter-name='"+ filter + "']");

									$("#search_filters_active").append(itemDiv.prop("outerHTML"));

									itemDiv.remove();

								}

							}();

							$(document).on("click", "#search_filters_active .search-filter-item", function(){

								$(this).toggleClass("active");

								updateSearchFilters();

							});

							dragula(

								[document.querySelector('#search_filters_active'), document.querySelector('#search_filters_inactive')],

								{

									direction: 'vertical'

								}

							).on('dragend', function(){

								updateSearchFilters();

							});

						</script>

					</tr>

					<!-- ========== 邮件模板设置 ========== -->
					<tr><th class="subtitle"><h3 id="subsection-email-template"><?php _e('邮件模板', 'argon');?></h3></th></tr>

					<tr>
						<th><label><?php _e('邮件主题色', 'argon');?></label></th>
						<td>
							<input type="text" class="regular-text" name="argon_email_theme_color" value="<?php echo esc_attr(get_option('argon_email_theme_color', '#5e72e4')); ?>" style="width: 100px;" />
							<input type="color" value="<?php echo esc_attr(get_option('argon_email_theme_color', '#5e72e4')); ?>" onchange="document.querySelector('input[name=argon_email_theme_color]').value = this.value;" style="vertical-align: middle; cursor: pointer;" />
							<p class="description"><?php _e('邮件中按钮、链接和强调色的颜色', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('邮件 Logo', 'argon');?></label></th>
						<td>
							<input type="text" class="regular-text" name="argon_email_logo_url" value="<?php echo esc_attr(get_option('argon_email_logo_url', '')); ?>" placeholder="https://example.com/logo.png" />
							<button type="button" class="button" onclick="argonUploadEmailLogo();"><?php _e('上传图片', 'argon');?></button>
							<p class="description"><?php _e('邮件页眉显示的 Logo 图片 URL，留空则显示博客名称文字', 'argon');?></p>
							<script>
								function argonUploadEmailLogo() {
									var mediaUploader = wp.media({
										title: '<?php _e('选择 Logo 图片', 'argon');?>',
										button: { text: '<?php _e('使用此图片', 'argon');?>' },
										multiple: false
									});
									mediaUploader.on('select', function() {
										var attachment = mediaUploader.state().get('selection').first().toJSON();
										document.querySelector('input[name=argon_email_logo_url]').value = attachment.url;
									});
									mediaUploader.open();
								}
							</script>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('邮件显示名称', 'argon');?></label></th>
						<td>
							<input type="text" class="regular-text" name="argon_email_blog_name" value="<?php echo esc_attr(get_option('argon_email_blog_name', '')); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" />
							<p class="description"><?php _e('邮件中显示的博客名称，留空则使用站点名称', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('页脚版权信息', 'argon');?></label></th>
						<td>
							<input type="text" class="regular-text" name="argon_email_footer_text" value="<?php echo esc_attr(get_option('argon_email_footer_text', '')); ?>" placeholder="© <?php echo date('Y'); ?> <?php echo esc_attr(get_bloginfo('name')); ?>. All rights reserved." />
							<p class="description"><?php _e('邮件页脚显示的版权信息，留空则使用默认格式', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('社交链接', 'argon');?></label></th>
						<td>
							<?php $social_links = get_option('argon_email_social_links', array()); ?>
							<div style="margin-bottom: 8px;">
								<label style="display: inline-block; width: 80px;">Twitter:</label>
								<input type="text" name="argon_email_social_twitter" value="<?php echo esc_attr(isset($social_links['twitter']) ? $social_links['twitter'] : ''); ?>" placeholder="username" style="width: 300px;" />
							</div>
							<div style="margin-bottom: 8px;">
								<label style="display: inline-block; width: 80px;">GitHub:</label>
								<input type="text" name="argon_email_social_github" value="<?php echo esc_attr(isset($social_links['github']) ? $social_links['github'] : ''); ?>" placeholder="username" style="width: 300px;" />
							</div>
							<div style="margin-bottom: 8px;">
								<label style="display: inline-block; width: 80px;"><?php _e('微博', 'argon');?>:</label>
								<input type="text" name="argon_email_social_weibo" value="<?php echo esc_attr(isset($social_links['weibo']) ? $social_links['weibo'] : ''); ?>" placeholder="username" style="width: 300px;" />
							</div>
							<div style="margin-bottom: 8px;">
								<label style="display: inline-block; width: 80px;">Bilibili:</label>
								<input type="text" name="argon_email_social_bilibili" value="<?php echo esc_attr(isset($social_links['bilibili']) ? $social_links['bilibili'] : ''); ?>" placeholder="UID" style="width: 300px;" />
							</div>
							<p class="description"><?php _e('邮件页脚显示的社交链接，留空则不显示。支持填写完整 URL 或仅填写用户名/UID（将自动补全为完整链接）', 'argon');?></p>
						</td>
					</tr>

					<tr><th class="subtitle"><h3 id="subsection-email-templates-custom"><?php _e('邮件内容模板', 'argon');?></h3></th></tr>

					<tr>
						<th><label><?php _e('模板编辑', 'argon');?></label></th>
						<td>
							<?php 
							$email_types = argon_get_email_types();
							?>
							<div class="argon-email-template-editor">
								<div style="margin-bottom: 16px;">
									<label><?php _e('选择邮件类型', 'argon');?>：</label>
									<select id="argon_email_template_type" onchange="argonSwitchEmailTemplate(this.value);" style="min-width: 200px;">
										<?php foreach ($email_types as $type_key => $type_info): ?>
										<option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_info['name']); ?></option>
										<?php endforeach; ?>
									</select>
									<span id="argon_email_template_desc" style="color: #666; margin-left: 10px;"><?php echo esc_html(reset($email_types)['description']); ?></span>
								</div>

								<?php foreach ($email_types as $type_key => $type_info): 
									$config = argon_get_email_template_config($type_key);
								?>
								<div id="argon_email_template_<?php echo esc_attr($type_key); ?>" class="argon-email-template-panel" style="<?php echo $type_key !== 'comment_notify' ? 'display:none;' : ''; ?>">
									<div style="margin-bottom: 16px;">
										<label>
											<?php $enabled = get_option('argon_email_template_' . $type_key . '_enabled', 'true'); ?>
											<input type="checkbox" name="argon_email_template_<?php echo esc_attr($type_key); ?>_enabled" value="true" <?php if ($enabled === 'true') echo 'checked'; ?> />
											<?php _e('启用此类型邮件', 'argon'); ?>
										</label>
									</div>

									<div style="margin-bottom: 16px;">
										<label style="display: block; margin-bottom: 4px; font-weight: 500;"><?php _e('邮件主题', 'argon');?>：</label>
										<input type="text" name="argon_email_template_<?php echo esc_attr($type_key); ?>_subject" 
									value="<?php echo esc_attr(get_option('argon_email_template_' . $type_key . '_subject', '')); ?>" 
									placeholder="<?php printf(__('默认：%s', 'argon'), esc_attr($config['default_subject'])); ?>"
											style="width: 100%;" />
										<p class="description"><?php _e('留空则使用默认主题', 'argon');?></p>
									</div>

									<div style="margin-bottom: 16px;">
										<label style="display: block; margin-bottom: 4px; font-weight: 500;"><?php _e('邮件内容', 'argon');?>：</label>
										<textarea name="argon_email_template_<?php echo esc_attr($type_key); ?>_content" 
									placeholder="<?php _e('留空则使用默认模板。支持 HTML 标签，可使用下方占位符', 'argon'); ?>"><?php echo esc_textarea(get_option('argon_email_template_' . $type_key . '_content', '')); ?></textarea>
										<p class="description"><?php _e('支持 HTML 标签，可使用下方占位符自定义邮件内容', 'argon');?></p>
									</div>

									<div style="margin-bottom: 16px;">
										<label style="display: block; margin-bottom: 8px; font-weight: 500;"><?php _e('可用占位符', 'argon');?>：</label>
										<div style="background: #f6f7f7; padding: 12px; border-radius: 4px; display: flex; flex-wrap: wrap; gap: 8px;">
											<?php foreach ($config['placeholders'] as $placeholder => $desc): ?>
											<code style="background: #fff; padding: 4px 8px; border-radius: 3px; cursor: pointer; border: 1px solid #ddd; display: inline-flex; align-items: center;" 
												onclick="argonInsertPlaceholder('argon_email_template_<?php echo esc_attr($type_key); ?>_content', '{{<?php echo esc_attr($placeholder); ?>}}')" 
												title="<?php _e('点击插入', 'argon'); ?>: <?php echo esc_attr($desc); ?>">
												<span style="color: #d63384;">{{<?php echo esc_html($placeholder); ?>}}</span>
												<span style="color: #6c757d; font-size: 11px; margin-left: 4px;">- <?php echo esc_html($desc); ?></span>
											</code>
											<?php endforeach; ?>
										</div>
										<p class="description" style="margin-top: 8px;"><?php _e('点击占位符可插入到内容编辑框中，占位符会在发送时被替换为实际数据', 'argon');?></p>
									</div>

									<div style="margin-bottom: 16px; padding: 12px; background: #e7f3ff; border-left: 4px solid #2271b1; border-radius: 4px;">
										<strong><?php _e('默认模板预览', 'argon');?>：</strong>
										<div style="margin-top: 8px; padding: 8px; background: #fff; border-radius: 4px; max-height: 200px; overflow-y: auto;">
											<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><strong><?php _e('主题', 'argon');?>:</strong> <?php echo esc_html($config['default_subject']); ?></div>
											<div style="font-size: 12px; color: #666;"><strong><?php _e('内容', 'argon');?>:</strong></div>
											<div style="font-size: 11px; color: #888; font-family: monospace; white-space: pre-wrap; margin-top: 4px;"><?php echo esc_html($config['default_content']); ?></div>
										</div>
									</div>

									<div style="display: flex; gap: 8px;">
										<button type="button" class="button" onclick="argonPreviewEmail('<?php echo esc_attr($type_key); ?>');">
											<?php _e('预览邮件', 'argon');?>
										</button>
										<button type="button" class="button" onclick="argonResetEmailTemplate('<?php echo esc_attr($type_key); ?>');">
											<?php _e('恢复默认', 'argon');?>
										</button>
									</div>
								</div>
								<?php endforeach; ?>
							</div>

							<!-- 邮件预览弹窗 -->
							<div id="argon_email_preview_modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000;">
								<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 8px; width: 90%; max-width: 700px; max-height: 90vh; overflow: auto;">
									<div style="padding: 16px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
										<strong><?php _e('邮件预览', 'argon');?></strong>
										<button type="button" onclick="document.getElementById('argon_email_preview_modal').style.display='none';" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
									</div>
									<iframe id="argon_email_preview_iframe" style="width: 100%; height: 500px; border: none;"></iframe>
								</div>
							</div>

							<script>
								var argonEmailTypes = <?php echo json_encode($email_types); ?>;
								var argonEmailDefaults = <?php 
									$defaults = array();
									foreach ($email_types as $type_key => $type_info) {
										$defaults[$type_key] = array(
											'subject' => $type_info['default_subject'],
											'content' => $type_info['default_content'],
										);
									}
									echo json_encode($defaults);
								?>;

								function argonSwitchEmailTemplate(type) {
									document.querySelectorAll('.argon-email-template-panel').forEach(function(panel) {
										panel.style.display = 'none';
									});
									document.getElementById('argon_email_template_' + type).style.display = 'block';
									document.getElementById('argon_email_template_desc').textContent = argonEmailTypes[type].description;
								}

								function argonInsertPlaceholder(textareaName, placeholder) {
									var textarea = document.querySelector('textarea[name="' + textareaName + '"]');
									if (textarea) {
										var start = textarea.selectionStart;
										var end = textarea.selectionEnd;
										var text = textarea.value;
										textarea.value = text.substring(0, start) + placeholder + text.substring(end);
										textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
										textarea.focus();
									}
								}

								function argonResetEmailTemplate(type) {
									if (confirm('<?php _e('确定要恢复此邮件类型的默认模板吗？', 'argon');?>')) {
										var defaults = argonEmailDefaults[type];
										document.querySelector('input[name="argon_email_template_' + type + '_subject"]').value = defaults.subject;
										document.querySelector('textarea[name="argon_email_template_' + type + '_content"]').value = defaults.content;
									}
								}

								function argonPreviewEmail(type) {
									var formData = new FormData();
									formData.append('action', 'argon_preview_email');
									formData.append('type', type);
									formData.append('nonce', '<?php echo wp_create_nonce('argon_preview_email'); ?>');
									
									fetch(ajaxurl, {
										method: 'POST',
										body: formData
									})
									.then(function(response) { return response.text(); })
									.then(function(html) {
										var iframe = document.getElementById('argon_email_preview_iframe');
										iframe.srcdoc = html;
										document.getElementById('argon_email_preview_modal').style.display = 'block';
									});
								}
							</script>
						</td>
					</tr>

					<tr><th class="subtitle"><h3 id="subsection-friend-links"><?php _e('友情链接', 'argon');?></h3></th></tr>

					<tr>
						<th><label><?php _e('页面标语', 'argon');?></label></th>
						<td>
							<input type="text" name="argon_friend_link_slogan" class="regular-text" value="<?php echo esc_attr(get_option('argon_friend_link_slogan', '海内存知己，天涯若比邻')); ?>">
							<p class="description"><?php _e('显示在友链页面标题下方的标语', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('欢迎文本', 'argon');?></label></th>
						<td>
							<textarea name="argon_friend_link_welcome" rows="3" cols="60" class="large-text"><?php echo esc_textarea(get_option('argon_friend_link_welcome', '欢迎与我交换友链！如果您的网站已添加本站链接，填写友链页面地址可自动通过审核。')); ?></textarea>
							<p class="description"><?php _e('显示在申请表单上方的欢迎文字', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('允许访客申请友链', 'argon');?></label></th>
						<td>
							<select name="argon_friend_link_allow_apply">
								<?php $allow_apply = get_option('argon_friend_link_allow_apply', 'true'); ?>
								<option value="true" <?php if ($allow_apply=='true'){echo 'selected';} ?>><?php _e('允许', 'argon');?></option>
								<option value="false" <?php if ($allow_apply=='false'){echo 'selected';} ?>><?php _e('不允许', 'argon');?></option>
							</select>
							<p class="description"><?php _e('开启后，友链页面会显示申请表单', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('申请要求说明', 'argon');?></label></th>
						<td>
							<textarea name="argon_friend_link_requirements" rows="4" cols="60" class="large-text"><?php echo esc_textarea(get_option('argon_friend_link_requirements', '')); ?></textarea>
							<p class="description"><?php _e('显示在申请表单上方的说明文字，支持 HTML', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('未上友链时隐藏', 'argon');?></label></th>
						<td>
							<select name="argon_friend_link_hide_no_backlink">
								<?php $hide_no_backlink = get_option('argon_friend_link_hide_no_backlink', 'true'); ?>
								<option value="true" <?php if ($hide_no_backlink=='true'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>
								<option value="false" <?php if ($hide_no_backlink=='false'){echo 'selected';} ?>><?php _e('仍然显示', 'argon');?></option>
							</select>
							<p class="description"><?php _e('检测到对方未上本站友链或网站失效时，是否在公开页面隐藏（管理员始终可见并有标记）', 'argon');?></p>
						</td>
					</tr>

					<tr>
						<th><label><?php _e('友链管理', 'argon');?></label></th>
						<td>
							<p class="description"><?php _e('友链管理已移至友情链接页面，管理员登录后访问友链页面即可管理。', 'argon');?></p>
							<a href="<?php echo home_url('/friends/'); ?>" class="button" target="_blank"><?php _e('前往友链页面', 'argon');?></a>
						</td>
					</tr>

					<tr><th class="subtitle"><h3 id="subsection-misc"><?php _e('杂项', 'argon');?></h3></th></tr>

					<tr>						<th><label><?php _e('是否启用 Pjax', 'argon');?></label></th>

						<td>

							<select name="argon_pjax_disabled">

								<?php $argon_pjax_disabled = get_option('argon_pjax_disabled'); ?>

								<option value="false" <?php if ($argon_pjax_disabled=='false'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

								<option value="true" <?php if ($argon_pjax_disabled=='true'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('Pjax 可以增强页面的跳转体验', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('首页隐藏特定 分类/Tag 下的文章', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_hide_categories" value="<?php echo get_option('argon_hide_categories'); ?>"/>

							<p class="description"><?php _e('输入要隐藏的 分类/Tag 的 ID，用英文逗号分隔，留空则不隐藏', 'argon');?><br/><a onclick="$('#id_of_categories_and_tags').slideDown(500);" style="cursor: pointer;"><?php _e('点此查看', 'argon');?></a><?php _e('所有分类和 Tag 的 ID', 'argon');?>

								<?php

									echo "<div id='id_of_categories_and_tags' style='display: none;'><div style='font-size: 22px;margin-bottom: 10px;margin-top: 10px;'>分类</div>";

									$categories = get_categories(array(

										'hide_empty' => 0,

										'hierarchical' => 0,

										'taxonomy' => 'category'

									));

									foreach($categories as $category) {

										echo "<span>".$category -> name ." -> ". $category -> term_id ."</span>";

									}

									echo "<div style='font-size: 22px;margin-bottom: 10px;'>Tag</div>";

									$categories = get_categories(array(

										'hide_empty' => 0,

										'hierarchical' => 0,

										'taxonomy' => 'post_tag'

									));

									foreach($categories as $category) {

										echo "<span>".$category -> name ." -> ". $category -> term_id ."</span>";

									}

									echo "</div>";

								?>

								<style>

									#id_of_categories_and_tags > span {

										display: inline-block;

										background: rgba(0, 0, 0, .08);

										border-radius: 2px;

										margin-right: 5px;

										margin-bottom: 8px;

										padding: 5px 10px;

									}

								</style>

							</p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('美化登录界面', 'argon');?></label></th>

						<td>

							<select name="argon_enable_login_css">

								<?php $argon_enable_login_css = get_option('argon_enable_login_css'); ?>

								<option value="false" <?php if ($argon_enable_login_css=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_login_css=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('使用 Argon Design 风格的登录界面', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('美化后台界面', 'argon');?></label></th>

						<td>

							<p class="description">

								<?php _e('使用 Argon Design 风格的后台界面', 'argon');?><br>

								<?php echo sprintf(__('前往<a href="%s" target="_blank">个人资料</a>页面将 "管理界面配色方案" 设为 "Argon" 即可开启。', 'argon'), admin_url('profile.php'));?>

							</p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('博客首页是否显示说说', 'argon');?></label></th>

						<td>

							<select name="argon_home_show_shuoshuo">

								<?php $argon_home_show_shuoshuo = get_option('argon_home_show_shuoshuo'); ?>

								<option value="false" <?php if ($argon_home_show_shuoshuo=='false'){echo 'selected';} ?>><?php _e('不显示', 'argon');?></option>

								<option value="true" <?php if ($argon_home_show_shuoshuo=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，博客首页文章和说说穿插显示', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('折叠长说说', 'argon');?></label></th>

						<td>

							<select name="argon_fold_long_shuoshuo">

								<?php $argon_fold_long_shuoshuo = get_option('argon_fold_long_shuoshuo'); ?>

								<option value="false" <?php if ($argon_fold_long_shuoshuo=='false'){echo 'selected';} ?>><?php _e('不折叠', 'argon');?></option>

								<option value="true" <?php if ($argon_fold_long_shuoshuo=='true'){echo 'selected';} ?>><?php _e('折叠', 'argon');?></option>

							</select>

							<p class="description"><?php _e('开启后，长说说在预览状态下会被折叠，需要手动展开', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否修正时区错误', 'argon');?></label></th>

						<td>

							<select name="argon_enable_timezone_fix">

								<?php $argon_enable_timezone_fix = get_option('argon_enable_timezone_fix'); ?>

								<option value="false" <?php if ($argon_enable_timezone_fix=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_timezone_fix=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>

							</select>

							<p class="description"><?php _e('如遇到时区错误（例如一条刚发的评论显示 8 小时前），这个选项', 'argon');?><strong><?php _e('可能', 'argon');?></strong><?php _e('可以修复这个问题', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否在文章列表内容预览中隐藏短代码', 'argon');?></label></th>

						<td>

							<select name="argon_hide_shortcode_in_preview">

								<?php $argon_hide_shortcode_in_preview = get_option('argon_hide_shortcode_in_preview'); ?>

								<option value="false" <?php if ($argon_hide_shortcode_in_preview=='false'){echo 'selected';} ?>><?php _e('否', 'argon');?></option>

								<option value="true" <?php if ($argon_hide_shortcode_in_preview=='true'){echo 'selected';} ?>><?php _e('是', 'argon');?></option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('文章内容预览截取字数', 'argon');?></label></th>

						<td>

							<input type="number" name="argon_trim_words_count" min="0" max="1000" value="<?php echo get_option('argon_trim_words_count', 175); ?>"/>

							<p class="description"><?php _e('设为 0 来隐藏文章内容预览', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否允许移动端缩放页面', 'argon');?></label></th>

						<td>

							<select name="argon_enable_mobile_scale">

								<?php $argon_enable_mobile_scale = get_option('argon_enable_mobile_scale'); ?>

								<option value="false" <?php if ($argon_enable_mobile_scale=='false'){echo 'selected';} ?>><?php _e('否', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_mobile_scale=='true'){echo 'selected';} ?>><?php _e('是', 'argon');?></option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('禁用 Google 字体', 'argon');?></label></th>

						<td>

							<select name="argon_disable_googlefont">

								<?php $argon_disable_googlefont = get_option('argon_disable_googlefont'); ?>

								<option value="false" <?php if ($argon_disable_googlefont=='false'){echo 'selected';} ?>><?php _e('不禁用', 'argon');?></option>

								<option value="true" <?php if ($argon_disable_googlefont=='true'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('Google 字体在中国大陆访问可能会阻塞，禁用可以解决页面加载被阻塞的问题。禁用后，Serif 字体将失效。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('禁用 Argon 代码块样式', 'argon');?></label></th>

						<td>

							<select name="argon_disable_codeblock_style">

								<?php $argon_disable_codeblock_style = get_option('argon_disable_codeblock_style'); ?>

								<option value="false" <?php if ($argon_disable_codeblock_style=='false'){echo 'selected';} ?>><?php _e('不禁用', 'argon');?></option>

								<option value="true" <?php if ($argon_disable_codeblock_style=='true'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('如果您启用了其他代码高亮插件，发现代码块样式被 Argon 覆盖，出现了显示错误，请将此选项设为禁用', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('检测更新源', 'argon');?></label></th>

						<td>

							<select name="argon_update_source">

								<?php $argon_update_source = get_option('argon_update_source'); ?>

								<option value="github" <?php if ($argon_update_source=='github'){echo 'selected';} ?>>Github</option>

								<option value="fastgit" <?php if ($argon_update_source=='fastgit'){echo 'selected';} ?>>Fastgit</option>

								<option value="cfworker" <?php if ($argon_update_source=='cfworker'){echo 'selected';} ?>>CF Worker</option>

								<option value="solstice23top" <?php if ($argon_update_source=='solstice23top'){echo 'selected';} ?>>solstice23.top</option>

								<option value="stop" <?php if ($argon_update_source=='stop'){echo 'selected';} ?>><?php _e('暂停更新 (不推荐)', 'argon');?></option>

							</select>

							<p class="description"><?php _e('如更新主题速度较慢，可考虑更换更新源。', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('热更新', 'argon');?></label></th>

						<td>

							<?php $argon_enable_hot_reload = get_option('argon_enable_hot_reload', 'false'); ?>

							<label>

								<input type="checkbox" name="argon_enable_hot_reload" value="true" <?php if ($argon_enable_hot_reload=='true'){echo 'checked';}?>/> 

								<?php _e('启用热更新功能', 'argon');?>

							</label>

							<p class="description"><?php _e('启用后，当主题文件被更新时，将自动清理所有缓存并显示更新通知。', 'argon');?></p>

							

							<?php $argon_hot_reload_auto_refresh = get_option('argon_hot_reload_auto_refresh', 'false'); ?>

							<label style="display:block;margin-top:10px;">

								<input type="checkbox" name="argon_hot_reload_auto_refresh" value="true" <?php if ($argon_hot_reload_auto_refresh=='true'){echo 'checked';}?>/> 

								<?php _e('自动刷新页面（检测到版本更新时自动刷新用户浏览器）', 'argon');?>

							</label>

							<p class="description" style="margin-left:24px;"><?php _e('推荐开启。用户访问时如检测到主题版本变化，将自动刷新页面以加载最新功能。', 'argon');?></p>

							

							<?php $argon_hot_reload_frontend_notice = get_option('argon_hot_reload_frontend_notice', 'false'); ?>

							<label style="display:block;margin-top:10px;">

								<input type="checkbox" name="argon_hot_reload_frontend_notice" value="true" <?php if ($argon_hot_reload_frontend_notice=='true'){echo 'checked';}?>/> 

								<?php _e('在前台显示更新通知（仅管理员可见）', 'argon');?>

							</label>

							

							<div style="margin-top:15px;">

								<button type="button" class="button" id="argon_clear_cache_btn"><?php _e('手动清理缓存', 'argon');?></button>

								<span id="argon_clear_cache_result" style="margin-left:10px;color:#46b450;display:none;"><?php _e('缓存已清理', 'argon');?></span>

							</div>

							<script>

							jQuery(document).ready(function($) {

								$('#argon_clear_cache_btn').on('click', function() {

									var btn = $(this);

									btn.prop('disabled', true).text('<?php _e('清理中...', 'argon');?>');

									$.post(ajaxurl, {

										action: 'argon_clear_cache',

										nonce: '<?php echo wp_create_nonce('argon_clear_cache'); ?>'

									}, function(response) {

										btn.prop('disabled', false).text('<?php _e('手动清理缓存', 'argon');?>');

										if (response.success) {

											$('#argon_clear_cache_result').fadeIn().delay(2000).fadeOut();

										} else {

											alert(response.data || '<?php _e('清理失败', 'argon');?>');

										}

									});

								});

							});

							</script>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('强制刷新缓存', 'argon');?></label></th>

						<td>

							<?php 
							$force_refresh_enabled = function_exists('argon_is_force_refresh_enabled') ? argon_is_force_refresh_enabled() : false;
							$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
							$remaining = 0;
							if ($force_refresh_enabled && $enabled_time > 0) {
								$remaining = max(0, 3600 - (time() - $enabled_time));
							}
							?>

							<div id="argon_force_refresh_container">

								<div id="argon_force_refresh_status" style="margin-bottom:15px;padding:12px 15px;border-radius:6px;<?php echo $force_refresh_enabled ? 'background:#d4edda;border:1px solid #c3e6cb;' : 'background:#f8f9fa;border:1px solid #e9ecef;'; ?>">

									<span id="argon_force_refresh_status_text">

										<?php if ($force_refresh_enabled): ?>

											<span style="color:#155724;">✓ <?php _e('强制刷新已启用', 'argon'); ?></span>

											<span id="argon_force_refresh_countdown" style="margin-left:10px;color:#666;"><?php echo sprintf(__('剩余 %s', 'argon'), '<span id="argon_countdown_time">' . floor($remaining / 60) . ':' . str_pad($remaining % 60, 2, '0', STR_PAD_LEFT) . '</span>'); ?></span>

										<?php else: ?>

											<span style="color:#666;"><?php _e('强制刷新未启用', 'argon'); ?></span>

										<?php endif; ?>

									</span>

								</div>

								<button type="button" class="button <?php echo $force_refresh_enabled ? 'button-secondary' : 'button-primary'; ?>" id="argon_force_refresh_btn">

									<?php echo $force_refresh_enabled ? __('关闭强制刷新', 'argon') : __('启用强制刷新 (1小时)', 'argon'); ?>

								</button>

							</div>

							<p class="description" style="margin-top:10px;">

								<?php _e('启用后，所有访客在刷新页面时将强制重新获取所有资源文件（CSS、JS等），解决手机端缓存导致的样式或功能异常问题。', 'argon'); ?><br/>

								<?php _e('该功能会在 1 小时后自动关闭，以避免对服务器造成持续压力。', 'argon'); ?>

							</p>

							<script>

							jQuery(document).ready(function($) {

								var isEnabled = <?php echo $force_refresh_enabled ? 'true' : 'false'; ?>;

								var remainingSeconds = <?php echo $remaining; ?>;

								var countdownTimer = null;

								

								function updateCountdown() {

									if (remainingSeconds <= 0) {

										clearInterval(countdownTimer);

										isEnabled = false;

										updateUI();

										return;

									}

									remainingSeconds--;

									var mins = Math.floor(remainingSeconds / 60);

									var secs = remainingSeconds % 60;

									$('#argon_countdown_time').text(mins + ':' + (secs < 10 ? '0' : '') + secs);

								}

								

								function updateUI() {

									var statusDiv = $('#argon_force_refresh_status');

									var statusText = $('#argon_force_refresh_status_text');

									var btn = $('#argon_force_refresh_btn');

									

									if (isEnabled) {

										statusDiv.css({'background': '#d4edda', 'border': '1px solid #c3e6cb'});

										var mins = Math.floor(remainingSeconds / 60);

										var secs = remainingSeconds % 60;

										statusText.html('<span style="color:#155724;">✓ <?php _e('强制刷新已启用', 'argon'); ?></span><span id="argon_force_refresh_countdown" style="margin-left:10px;color:#666;"><?php _e('剩余', 'argon'); ?> <span id="argon_countdown_time">' + mins + ':' + (secs < 10 ? '0' : '') + secs + '</span></span>');

										btn.removeClass('button-primary').addClass('button-secondary').text('<?php _e('关闭强制刷新', 'argon'); ?>');

										if (!countdownTimer) {

											countdownTimer = setInterval(updateCountdown, 1000);

										}

									} else {

										statusDiv.css({'background': '#f8f9fa', 'border': '1px solid #e9ecef'});

										statusText.html('<span style="color:#666;"><?php _e('强制刷新未启用', 'argon'); ?></span>');

										btn.removeClass('button-secondary').addClass('button-primary').text('<?php _e('启用强制刷新 (1小时)', 'argon'); ?>');

										if (countdownTimer) {

											clearInterval(countdownTimer);

											countdownTimer = null;

										}

									}

								}

								

								// 初始化倒计时

								if (isEnabled && remainingSeconds > 0) {

									countdownTimer = setInterval(updateCountdown, 1000);

								}

								

								$('#argon_force_refresh_btn').on('click', function() {

									var btn = $(this);

									btn.prop('disabled', true);

									

									var action = isEnabled ? 'argon_disable_force_refresh' : 'argon_enable_force_refresh';

									

									$.post(ajaxurl, {

										action: action,

										nonce: '<?php echo wp_create_nonce('argon_force_refresh'); ?>'

									}, function(response) {

										btn.prop('disabled', false);

										if (response.success) {

											isEnabled = !isEnabled;

											if (isEnabled) {

												remainingSeconds = 3600;

											} else {

												remainingSeconds = 0;

											}

											updateUI();

										} else {

											alert(response.data || '<?php _e('操作失败', 'argon'); ?>');

										}

									});

								});

							});

							</script>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('调试控制台', 'argon');?></label></th>

						<td>

							<?php $argon_enable_debug_console = get_option('argon_enable_debug_console', 'false'); ?>

							<label>

								<input type="checkbox" name="argon_enable_debug_console" value="true" <?php if ($argon_enable_debug_console=='true'){echo 'checked';}?>/> 

								<?php _e('启用前端调试控制台', 'argon');?>

							</label>

							<p class="description"><?php _e('启用后，管理员可在前台看到调试按钮，点击打开控制台查看错误信息。普通用户遇到错误时会显示红色通知提示联系管理员。', 'argon');?></p>

							

							<?php $muted_errors = get_option('argon_muted_errors', array()); ?>

							<?php if (!empty($muted_errors)): ?>

							<div style="margin-top:15px;padding:15px;background:#f8f9fa;border-radius:6px;">

								<strong style="display:block;margin-bottom:10px;"><?php _e('已屏蔽的错误', 'argon'); ?> (<?php echo count($muted_errors); ?>)</strong>

								<div style="max-height:200px;overflow-y:auto;">

								<?php foreach ($muted_errors as $hash => $error): ?>

									<div style="padding:8px 10px;margin-bottom:6px;background:#fff;border-radius:4px;border-left:3px solid #f5365c;font-size:12px;position:relative;">

										<div style="color:#333;word-break:break-all;"><?php echo esc_html(mb_substr($error['message'], 0, 100)); ?><?php echo mb_strlen($error['message']) > 100 ? '...' : ''; ?></div>

										<div style="color:#888;font-size:11px;margin-top:4px;">

											<?php echo sprintf(__('由 %s 于 %s 屏蔽', 'argon'), esc_html($error['muted_by']), date('Y-m-d H:i', $error['muted_at'])); ?>

										</div>

										<button type="button" class="button button-small" style="position:absolute;top:8px;right:8px;" onclick="argonUnmuteError('<?php echo esc_attr($hash); ?>', this)"><?php _e('取消屏蔽', 'argon'); ?></button>

									</div>

								<?php endforeach; ?>

								</div>

								<button type="button" class="button" style="margin-top:10px;" id="argon_clear_muted_errors_btn"><?php _e('清空所有屏蔽', 'argon'); ?></button>

							</div>

							<script>

							function argonUnmuteError(hash, btn) {

								if (!confirm('<?php _e('确定取消屏蔽此错误？', 'argon'); ?>')) return;

								jQuery.post(ajaxurl, {

									action: 'argon_unmute_error',

									nonce: '<?php echo wp_create_nonce('argon_debug_console'); ?>',

									error_hash: hash

								}, function(response) {

									if (response.success) {

										jQuery(btn).closest('div[style*="border-left"]').fadeOut(300, function() { jQuery(this).remove(); });

									}

								});

							}

							jQuery('#argon_clear_muted_errors_btn').on('click', function() {

								if (!confirm('<?php _e('确定清空所有已屏蔽的错误？', 'argon'); ?>')) return;

								var btn = jQuery(this);

								btn.prop('disabled', true);

								jQuery.post(ajaxurl, {

									action: 'argon_clear_muted_errors',

									nonce: '<?php echo wp_create_nonce('argon_debug_console'); ?>'

								}, function(response) {

									if (response.success) {

										btn.closest('div[style*="background:#f8f9fa"]').fadeOut(300);

									}

								});

							});

							</script>

							<?php endif; ?>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('页脚附加内容', 'argon');?></label></th>

						<td>

							<select name="argon_hide_footer_author">

								<?php $argon_hide_footer_author = get_option('argon_hide_footer_author'); ?>

								<option value="false" <?php if ($argon_hide_footer_author=='false'){echo 'selected';} ?>>Theme Argon By solstice23</option>

								<option value="true" <?php if ($argon_hide_footer_author=='true'){echo 'selected';} ?>>Theme Argon</option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

				</tbody>

			</table>

			<p class="submit">

				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('保存更改', 'argon');?>">

				<a class="button button-secondary" style="margin-left: 8px;" onclick="importSettings()"><?php _e('导入设置', 'argon');?></a>

				<a class="button button-secondary" style="margin-left: 5px;" onclick="exportSettings()"><?php _e('导出设置', 'argon');?></a>

			</p>

		</form>

	</div>

	<div id="headindex_box">

		<button id="headindex_toggler" onclick="$('#headindex_box').toggleClass('folded');"><?php _e('收起', 'argon');?></button>

		<div id="headindex"></div>

	</div>

	<div id="scroll_navigation"><button onclick="$('body,html').animate({scrollTop: 0}, 300);"><?php _e('到顶部', 'argon');?></button><button onclick="$('body,html').animate({scrollTop: $(document).height()-$(window).height()+10}, 300);"><?php _e('到底部', 'argon');?></button></div>

	<div id="exported_settings_json_box" class="closed"><div><?php _e('请复制并保存导出后的 JSON', 'argon');?></div><textarea id="exported_settings_json" readonly="true" onclick="$(this).select();"></textarea><div style="width: 100%;margin: auto;margin-top: 15px;cursor: pointer;user-select: none;" onclick="$('#exported_settings_json_box').addClass('closed');"><?php _e('确定', 'argon');?></div></div>

	<style>

		.radio-with-img {

			display: inline-block;

			margin-right: 15px;

			margin-bottom: 20px;

			text-align: center;

		}

		.radio-with-img > .radio-img {

			cursor: pointer;

		}

		.radio-with-img > label {

			display: inline-block;

			margin-top: 10px;

		}

		.radio-h {

			padding-bottom: 10px;

		}

		.radio-h > label {

			margin-right: 15px;

		}

		#headindex_box {

			position: fixed;

			right: 10px;

			top: 50px;

			max-width: 180px;

			max-height: calc(100vh - 100px);

			opacity: .8;

			transition: all .3s ease;

			background: #fff;

			box-shadow: 0 1px 1px rgba(0,0,0,.04);

			padding: 6px 30px 6px 20px;

			overflow-y: auto;

		}

		.index-subItem-box {

			margin-left: 20px;

			margin-top: 10px;

			display: block !important;

		}

		.index-link {

			color: #23282d;

			text-decoration: unset;

			transition: all .3s ease;

			box-shadow: none !important;

		}

		.index-item {

			padding: 1px 0;

		}

		.index-item.current > a {

			color: #0073aa;

			font-weight: 600;

			box-shadow: none !important;

		}

		#headindex_toggler{

			position: absolute;

			right: 5px;

			top: 5px;

			color: #555;

			background: #f7f7f7;

			box-shadow: 0 1px 0 #ccc;

			outline: none !important;

			border: 1px solid #ccc;

			border-radius: 2px;

			cursor: pointer;

			width: 40px;

			height: 25px;

			font-size: 12px;

		}

		#headindex_box.folded {

			right: -185px;

		}

		#headindex_box.folded #headindex_toggler{

			position: fixed;

			right: 15px;

			top: 55px;

			font-size: 0px;

		}

		#headindex_box.folded #headindex_toggler:before{

			content: '<?php _e('展开', 'argon');?>';

			font-size: 12px;

		}

		@media screen and (max-width:960px){

			#headindex_box {

				display: none;

			}

		}

		#scroll_navigation {

			position: fixed;

			right: 10px;

			bottom: 10px;

			z-index: 99;

			user-select: none;

		}

		#scroll_navigation button {

			color: #555;

			background: #fff;

			box-shadow: 0 1px 0 #ccc;

			outline: none !important;

			border: 1px solid #ccc;

			border-radius: 2px;

			cursor: pointer;

			font-size: 14px;

		}

		#exported_settings_json_box{

			position: fixed;

			z-index: 99999;

			left: calc(50vw - 400px);

			right: calc(50vw - 400px);

			top: 50px;

			width: 800px;

			height: 500px;

			max-width: 100vw;

			max-height: calc(100vh - 50px);

			background: #fff;

			padding: 25px;

			border-radius: 5px;

			box-shadow: 0 5px 10px rgba(0, 0, 0, .1);

			text-align: center;

			font-size: 20px;

			transition: all .3s ease;

		}

		#exported_settings_json{

			width: 100%;

			height: calc(100% - 70px);

			margin-top: 25px;

			font-size: 18px;

			background: #fff;

			resize: none;

		}

		#exported_settings_json::selection{

			background: #cce2ff;

		}

		#exported_settings_json_box.closed{

			transform: translateY(-30px) scale(.9);

			opacity: 0;

			pointer-events: none;

		}

		@media screen and (max-width:800px){

			#exported_settings_json_box{

				left: 0;

				right: 0;

				top: 0;

				width: calc(100vw - 50px);

			}

		}



		.form-table > tbody > tr:first-child > th{

			padding-top: 0 !important;

		}

		.form-table.form-table-dense > tbody > tr > th{

			padding-top: 10px;

			padding-bottom: 10px;

		}



		.form-table-mathrender > tbody > tr > th > label > div {

			margin-top: 10px;

			padding-left: 24px;

			opacity: .75;

			transition: all .3s ease;

		}

		.form-table-mathrender > tbody > tr > th > label:hover > div {

			opacity: 1;

		}

		.form-table-mathrender > tbody > tr > th > label > input:not(:checked) + div {

			display: none;

		}



		#main_form > .form-table{

			max-width: calc(100% - 180px);

		}

	</style>

	<script type="text/javascript">

		$(document).on("click" , ".radio-with-img .radio-img" , function(){

			$("input", this.parentNode).click();

		});

		// 防止数学公式 CDN 输入框点击时触发 radio 选中
		$(document).on("click", ".form-table-mathrender input[type='text']", function(e){
			e.stopPropagation();
		});

		$(function () {

			$(document).headIndex({

				articleWrapSelector: '#main_form',

				indexBoxSelector: '#headindex',

				subItemBoxClass: "index-subItem-box",

				itemClass: "index-item",

				linkClass: "index-link",

				offset: 80,

				defaultExpanded: true

			});

			// 确保设置页目录框默认展开
			$('#headindex_box').removeClass('folded');

			// 展开所有子目录
			$('#headindex .index-subItem-box').addClass('open').show();

		});

		function setInputValue(name, value){

			let input = $("*[name='" + name + "']");

			let inputType = input.attr("type");

			if (inputType == "checkbox"){

				if (value == "true"){

					value = true;

				}else if (value == "false"){

					value = false;

				}

				input[0].checked = value;

			}else if (inputType == "radio"){

				$("input[name='" + name + "'][value='" + value + "']").click();

			}else{

				input.val(value);

			}

		}

		function getInputValue(input){

			let inputType = input.attr("type");

			if (inputType == "checkbox"){

				return input[0].checked;

			}else if (inputType == "radio"){

				let name = input.attr("name");

				let value;

				$("input[name='" + name + "']").each(function(){

					if (this.checked){

						value = $(this).attr("value");

					}

				});

				return value;

			}else{

				return input.val();

			}

		}

		function exportArgonSettings(){

			let json = {};

			let pushIntoJson = function (){

				name = $(this).attr("name");

				value = getInputValue($(this));

				json[name] = value;

			};

			$("#main_form > .form-table input:not([name='submit']) , #main_form > .form-table select , #main_form > .form-table textarea").each(function(){

				name = $(this).attr("name");

				value = getInputValue($(this));

				json[name] = value;

			});

			return JSON.stringify(json);

		}

		function importArgonSettings(json){

			if (typeof(json) == "string"){

				json = JSON.parse(json);

			}

			let info = "";

			for (let name in json){

				try{

					if ($("*[name='" + name + "']").length == 0){

						throw "Input Not Found";

					}

					setInputValue(name, json[name]);

				}catch{

					info += name + " <?php _e('字段导入失败', 'argon');?>\n";

				}

			}

			return info;

		}

		function exportSettings(){

			$("#exported_settings_json").val(exportArgonSettings());

			$("#exported_settings_json").select();

			$("#exported_settings_json_box").removeClass("closed");

		}

		function importSettings(){

			let json = prompt("<?php _e('请输入要导入的备份 JSON', 'argon');?>");

			if (json){

				let res = importArgonSettings(json);

				alert("<?php _e('已导入，请保存更改', 'argon');?>\n" + res)

			}

		}

	</script>

<?php

}

add_action('admin_menu', 'themeoptions_admin_menu');

function argon_update_option($name){

	update_option($name, htmlspecialchars(stripslashes($_POST[$name])));

}

function argon_update_option_allow_tags($name){

	update_option($name, stripslashes($_POST[$name]));

}

function argon_update_option_checkbox($name){

	if (isset($_POST[$name]) && $_POST[$name] == 'true'){

		update_option($name, 'true');

	}else{

		update_option($name, 'false');

	}

}

function argon_update_themeoptions(){

	if (!isset($_POST['update_themeoptions'])){

		return;

	}

	if ($_POST['update_themeoptions'] == 'true'){

		if (!isset($_POST['argon_update_themeoptions_nonce'])){

			return;

		}

		$nonce = $_POST['argon_update_themeoptions_nonce'];

		if (!wp_verify_nonce($nonce, 'argon_update_themeoptions')){

			return;

		}

		//配置项

		argon_update_option('argon_toolbar_icon');

		argon_update_option('argon_toolbar_icon_link');

		argon_update_option('argon_toolbar_title');

		argon_update_option('argon_sidebar_banner_title');

		argon_update_option('argon_sidebar_banner_subtitle');

		argon_update_option('argon_sidebar_auther_name');

		argon_update_option('argon_sidebar_auther_image');

		argon_update_option('argon_sidebar_author_description');

		argon_update_option('argon_show_todo_list');

		argon_update_option('argon_show_duolingo_streak');
		argon_update_option('argon_duolingo_username');

		argon_update_option('argon_banner_title');

		argon_update_option('argon_banner_subtitle');

		argon_update_option('argon_banner_background_url');

		argon_update_option('argon_banner_background_color_type');

		argon_update_option_checkbox('argon_banner_background_hide_shapes');

		argon_update_option('argon_enable_smoothscroll_type');

		argon_update_option('argon_gravatar_cdn');

		argon_update_option_allow_tags('argon_footer_html');

		argon_update_option('argon_show_readingtime');

		argon_update_option('argon_reading_speed');

		argon_update_option('argon_reading_speed_en');

		argon_update_option('argon_reading_speed_code');

		argon_update_option('argon_show_sharebtn');

		argon_update_option('argon_enable_timezone_fix');

		argon_update_option('argon_donate_qrcode_url');

		argon_update_option('argon_hide_shortcode_in_preview');

		argon_update_option('argon_show_thumbnail_in_banner_in_content_page');

		argon_update_option('argon_update_source');

		argon_update_option_checkbox('argon_enable_hot_reload');

		argon_update_option_checkbox('argon_hot_reload_auto_refresh');

		argon_update_option_checkbox('argon_hot_reload_frontend_notice');

		argon_update_option_checkbox('argon_enable_debug_console');

		argon_update_option('argon_enable_into_article_animation');

		argon_update_option('argon_disable_pjax_animation');

		// 动画效果相关配置

		argon_update_option('argon_fab_show_darkmode_button');

		argon_update_option('argon_fab_show_settings_button');

		argon_update_option('argon_fab_show_gotocomment_button');

		argon_update_option('argon_show_headindex_number');

		argon_update_option('argon_theme_color');

		argon_update_option_checkbox('argon_show_customize_theme_color_picker');

		argon_update_option_allow_tags('argon_seo_description');

		argon_update_option('argon_seo_keywords');

		argon_update_option('argon_enable_mobile_scale');

		argon_update_option('argon_page_background_url');

		argon_update_option('argon_page_background_dark_url');

		argon_update_option('argon_page_background_opacity');

		argon_update_option('argon_post_background_opacity');

		argon_update_option('argon_card_blur');

		argon_update_option('argon_card_saturate');

		argon_update_option('argon_page_background_banner_style');

		argon_update_option('argon_hide_name_email_site_input');

		argon_update_option('argon_comment_captcha_mode');

		argon_update_option('argon_todo_captcha_mode');

		argon_update_option('argon_need_captcha');

		argon_update_option('argon_captcha_type');

		argon_update_option('argon_geetest_captcha_id');

		argon_update_option('argon_geetest_captcha_key');

		argon_update_option('argon_geetest_api_server');

		argon_update_option('argon_get_captcha_by_ajax');

		// 评论速率限制相关配置

		argon_update_option_checkbox('argon_rate_limit_enable');

		argon_update_option('argon_rate_limit_window');

		argon_update_option('argon_rate_limit_max_count');

		argon_update_option('argon_rate_limit_min_interval');

		// 友情链接相关配置
		argon_update_option('argon_friend_link_slogan');
		argon_update_option('argon_friend_link_welcome');
		argon_update_option('argon_friend_link_allow_apply');
		argon_update_option('argon_friend_link_captcha');
		argon_update_option('argon_feedback_captcha_mode');
		argon_update_option('argon_feedback_notify_admin');
		argon_update_option('argon_feedback_notify_user');
		argon_update_option('argon_global_blocked_ips');
		argon_update_option('argon_flink_apply_limit');
		argon_update_option('argon_flink_apply_period');
		argon_update_option('argon_feedback_submit_limit');
		argon_update_option('argon_feedback_submit_period');
		argon_update_option('argon_feedback_upload_limit');
		argon_update_option('argon_feedback_upload_period');
		argon_update_option('argon_friend_link_hide_no_backlink');
		argon_update_option_allow_tags('argon_friend_link_requirements');

		// 邮件模板相关配置
		argon_update_option('argon_email_theme_color');
		argon_update_option('argon_email_logo_url');
		argon_update_option('argon_email_blog_name');
		argon_update_option('argon_email_footer_text');
		
		// 保存社交链接为数组（自动补全完整 URL）
		$social_links = array(
			'twitter' => argon_normalize_social_url('twitter', isset($_POST['argon_email_social_twitter']) ? sanitize_text_field($_POST['argon_email_social_twitter']) : ''),
			'github' => argon_normalize_social_url('github', isset($_POST['argon_email_social_github']) ? sanitize_text_field($_POST['argon_email_social_github']) : ''),
			'weibo' => argon_normalize_social_url('weibo', isset($_POST['argon_email_social_weibo']) ? sanitize_text_field($_POST['argon_email_social_weibo']) : ''),
			'bilibili' => argon_normalize_social_url('bilibili', isset($_POST['argon_email_social_bilibili']) ? sanitize_text_field($_POST['argon_email_social_bilibili']) : '')
		);
		update_option('argon_email_social_links', $social_links);

		// 保存邮件模板配置
		$email_types = array('comment_notify', 'spam_notify', 'reply_notify', 'todo_urge');
		foreach ($email_types as $type) {
			// 保存启用状态
			argon_update_option_checkbox('argon_email_template_' . $type . '_enabled');
			// 保存主题
			argon_update_option('argon_email_template_' . $type . '_subject');
			// 保存内容（允许 HTML）
			argon_update_option_allow_tags('argon_email_template_' . $type . '_content');
		}

		argon_update_option('argon_hide_footer_author');

		argon_update_option('argon_card_radius');

		argon_update_option('argon_comment_avatar_vcenter');

		argon_update_option('argon_pjax_disabled');

		argon_update_option('argon_comment_allow_markdown');

		argon_update_option('argon_comment_allow_editing');

		argon_update_option('argon_comment_allow_privatemode');

		argon_update_option('argon_comment_allow_mailnotice');

		argon_update_option_checkbox('argon_comment_mailnotice_checkbox_checked');

		argon_update_option('argon_comment_pagination_type');

		argon_update_option('argon_who_can_visit_comment_edit_history');

		argon_update_option('argon_home_show_shuoshuo');

		argon_update_option('argon_enable_search_filters');

		argon_update_option('argon_search_filters_type');

		argon_update_option('argon_darkmode_autoswitch');

		argon_update_option('argon_enable_amoled_dark');

		argon_update_option('argon_outdated_info_time_type');

		argon_update_option('argon_outdated_info_days');

		argon_update_option('argon_outdated_info_tip_type');

		argon_update_option('argon_outdated_info_tip_content');

		argon_update_option_checkbox('argon_show_toolbar_mask');

		argon_update_option('argon_enable_banner_title_typing_effect');

		argon_update_option('argon_banner_typing_effect_interval');

		argon_update_option('argon_page_layout');

		argon_update_option('argon_article_list_layout');

		argon_update_option('argon_article_list_layout_mobile');

		argon_update_option('argon_enable_pangu');

		argon_update_option('argon_assets_path');

		argon_update_option('argon_custom_assets_path');

		argon_update_option('argon_comment_ua');

		argon_update_option('argon_wp_path');

		argon_update_option('argon_dateformat');

		argon_update_option('argon_font');

		argon_update_option('argon_card_shadow');

		argon_update_option('argon_enable_code_highlight');

		argon_update_option('argon_code_highlight_hide_linenumber');

		argon_update_option('argon_code_highlight_transparent_linenumber');

		argon_update_option('argon_code_highlight_break_line');

		argon_update_option('argon_code_theme');

		argon_update_option('argon_comment_enable_qq_avatar');

		argon_update_option('argon_enable_login_css');

		argon_update_option('argon_hide_categories');

		argon_update_option('argon_article_meta');

		argon_update_option('argon_fold_long_comments');

		argon_update_option('argon_fold_long_shuoshuo');

		argon_update_option('argon_first_image_as_thumbnail_by_default');

		argon_update_option('argon_enable_headroom');

		argon_update_option('argon_comment_emotion_keyboard');

		argon_update_option_allow_tags('argon_additional_content_after_post');

		argon_update_option('argon_related_post');

		argon_update_option('argon_related_post_sort_orderby');

		argon_update_option('argon_related_post_sort_order');

		argon_update_option('argon_related_post_limit');

		argon_update_option('argon_article_header_style');

		argon_update_option('argon_text_gravatar');

		argon_update_option('argon_disable_googlefont');

		argon_update_option('argon_disable_codeblock_style');

		argon_update_option('argon_reference_list_title');

		argon_update_option('argon_trim_words_count');

		argon_update_option('argon_enable_comment_upvote');

		argon_update_option('argon_article_list_waterflow');

		argon_update_option('argon_banner_size');

		argon_update_option('argon_toolbar_blur');

		argon_update_option('argon_toolbar_style');

		argon_update_option('argon_toolbar_shadow');

		argon_update_option('argon_toolbar_border');

		argon_update_option('argon_toolbar_compact');

		argon_update_option('argon_toolbar_center');

		argon_update_option('argon_toolbar_links_left');

		argon_update_option('argon_toolbar_links_right');

		argon_update_option('argon_archives_timeline_show_month');

		argon_update_option('argon_archives_timeline_url');

		argon_update_option('argon_enable_immersion_color');

		argon_update_option('argon_enable_comment_pinning');

		argon_update_option('argon_show_comment_parent_info');



		//LazyLoad 相关

		argon_update_option('argon_enable_lazyload');

		argon_update_option('argon_lazyload_effect');

		argon_update_option('argon_lazyload_threshold');

		argon_update_option('argon_lazyload_loading_style');



		//图片缩放预览相关

		argon_update_option('argon_enable_fancybox');

		argon_update_option('argon_enable_zoomify');

		argon_update_option('argon_zoomify_duration');

		argon_update_option('argon_zoomify_easing');

		argon_update_option('argon_zoomify_scale');



		//数学公式相关配置项

		argon_update_option('argon_math_render');

		argon_update_option('argon_mathjax_cdn_url');

		argon_update_option('argon_mathjax_v2_cdn_url');

		argon_update_option('argon_katex_cdn_url');

		argon_update_option_checkbox('argon_mathjax3_use_local');

		argon_update_option_checkbox('argon_mathjax2_use_local');

		argon_update_option_checkbox('argon_katex_use_local');



		//页头页尾脚本

		argon_update_option_allow_tags('argon_custom_html_head');

		argon_update_option_allow_tags('argon_custom_html_foot');



		//公告

		argon_update_option_allow_tags('argon_sidebar_announcement');

	//AI 摘要
	argon_update_option_checkbox('argon_ai_summary_enable');
	argon_update_option('argon_ai_summary_provider');
	argon_update_option('argon_ai_summary_api_key');
	argon_update_option('argon_ai_summary_api_endpoint');
	argon_update_option('argon_ai_summary_model');
	argon_update_option('argon_ai_summary_prompt');
	argon_update_option('argon_ai_summary_exclude_ids');

	//AI 垃圾评论识别
	argon_update_option_checkbox('argon_comment_spam_detection_enable');
	argon_update_option('argon_comment_spam_detection_prompt_mode');
	argon_update_option('argon_comment_spam_detection_confidence_threshold');
	argon_update_option('argon_comment_spam_detection_prompt');
	argon_update_option('argon_comment_spam_detection_mode');
	argon_update_option('argon_comment_spam_detection_sample_rate');
	argon_update_option('argon_comment_spam_detection_auto_action');
	argon_update_option('argon_comment_spam_detection_whitelist');
	argon_update_option_checkbox('argon_comment_spam_detection_exclude_logged_in');
	argon_update_option('argon_comment_spam_detection_keywords');
	argon_update_option_checkbox('argon_comment_spam_detection_ai_learn');

	}

}

argon_update_themeoptions();

?>
