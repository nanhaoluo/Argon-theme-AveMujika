				</main>

			<?php
				$footer_layout = get_option('argon_page_layout', 'double');
				$footer_classes = array('site-footer', 'card', 'shadow-sm', 'border-0');
				if ($footer_layout == 'single') {
					$footer_classes[] = 'site-footer-no-sidebar';
				} else {
					$footer_classes[] = 'site-footer-with-sidebar';
					if ($footer_layout == 'triple') {
						$footer_classes[] = 'site-footer-triple-sidebar';
					} else if ($footer_layout == 'double-reverse') {
						$footer_classes[] = 'site-footer-sidebar-right';
					} else {
						$footer_classes[] = 'site-footer-sidebar-left';
					}
				}
			?>

			<footer id="footer" class="<?php echo esc_attr(implode(' ', $footer_classes)); ?>">

						<?php

							echo get_option('argon_footer_html');

						?>

						<div>❤Theme <a href="https://github.com/solstice23/argon-theme" target="_blank"><strong>Argon</strong></a><?php if (get_option('argon_hide_footer_author') != 'true') {echo " By solstice23"; }?>❤<?php
						// 显示 Git 版本信息
						$git_info = get_option('argon_show_git_badge', 'true') != 'false' ? argon_get_git_info() : false;
						if ($git_info) :
						?> <a class="git-version-badge" id="git-badge" href="https://github.com/nanhaoluo/Argon-theme-AveMujika" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr__('查看 GitHub 仓库', 'argon'); ?>"><svg class="git-icon" viewBox="0 0 448 512" fill="currentColor"><path d="M439.55 236.05L244 40.45a28.87 28.87 0 0 0-40.81 0l-40.66 40.63l51.52 51.52c27.06-9.14 52.68 16.77 43.39 43.68l49.66 49.66c34.23-11.8 61.18 31 35.47 56.69c-26.49 26.49-70.21-2.87-56-37.34L240.22 199v121.85c25.3 12.54 22.26 41.85 9.08 55a34.34 34.34 0 0 1-48.55 0c-17.57-17.6-11.07-46.91 11.25-56v-123c-20.8-8.51-24.6-30.74-18.64-45L142.57 101L8.45 235.14a28.86 28.86 0 0 0 0 40.81l195.61 195.6a28.86 28.86 0 0 0 40.8 0l194.69-194.69a28.86 28.86 0 0 0 0-40.81z"/></svg><?php echo esc_html($git_info['branch']); ?>@<?php echo esc_html($git_info['commit']); ?></a>
						<?php endif; ?></div>

						<?php 
						// 调试控制台按钮（放在页脚底部）
						if (function_exists('argon_debug_console_footer_button')) {
							argon_debug_console_footer_button();
						}
						?>

			</footer>

			</div>

		</div>

		<!-- 统一图片查看器（文章 / 说说 / 评论 / 反馈页共用，位于 PJAX 容器之外） -->
		<div class="argon-image-viewer" id="argon-image-viewer" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php esc_attr_e('图片预览', 'argon'); ?>"
			data-text-loading="<?php esc_attr_e('图片加载中…', 'argon'); ?>"
			data-text-error="<?php esc_attr_e('图片加载失败', 'argon'); ?>"
			data-text-default-name="<?php esc_attr_e('图片', 'argon'); ?>">
			<div class="argon-image-viewer-topbar">
				<div class="argon-image-viewer-title">
					<i class="fa fa-picture-o"></i>
					<div class="argon-image-viewer-details">
						<span class="argon-image-viewer-name" id="argon-image-viewer-name"></span>
						<span class="argon-image-viewer-meta"><span id="argon-image-viewer-counter">1 / 1</span><span class="argon-image-viewer-meta-separator">·</span><span id="argon-image-viewer-resolution">—</span></span>
					</div>
				</div>
				<button type="button" class="argon-image-viewer-close" id="argon-image-viewer-close" aria-label="<?php esc_attr_e('关闭预览', 'argon'); ?>"><i class="fa fa-times"></i></button>
			</div>
			<div class="argon-image-viewer-stage" id="argon-image-viewer-stage">
				<button type="button" class="argon-image-viewer-nav argon-image-viewer-prev" id="argon-image-viewer-prev" aria-label="<?php esc_attr_e('上一张', 'argon'); ?>"><i class="fa fa-angle-left"></i></button>
				<div class="argon-image-viewer-loading" id="argon-image-viewer-loading"><i class="fa fa-spinner fa-spin"></i><span></span></div>
				<img class="argon-image-viewer-image" id="argon-image-viewer-image" alt="" draggable="false">
				<button type="button" class="argon-image-viewer-nav argon-image-viewer-next" id="argon-image-viewer-next" aria-label="<?php esc_attr_e('下一张', 'argon'); ?>"><i class="fa fa-angle-right"></i></button>
			</div>
			<div class="argon-image-viewer-controls" aria-label="<?php esc_attr_e('图片缩放控件', 'argon'); ?>">
				<button type="button" class="argon-image-viewer-control" id="argon-image-viewer-zoom-out" aria-label="<?php esc_attr_e('缩小', 'argon'); ?>"><i class="fa fa-search-minus"></i></button>
				<button type="button" class="argon-image-viewer-control" id="argon-image-viewer-reset" aria-label="<?php esc_attr_e('重置缩放', 'argon'); ?>"><i class="fa fa-refresh"></i></button>
				<span class="argon-image-viewer-zoom" id="argon-image-viewer-zoom">100%</span>
				<button type="button" class="argon-image-viewer-control" id="argon-image-viewer-zoom-in" aria-label="<?php esc_attr_e('放大', 'argon'); ?>"><i class="fa fa-search-plus"></i></button>
			</div>
		</div>

				<!-- 加载主题核心 JS -->
				<?php if (get_option('argon_enable_code_highlight') == 'true') : ?>
					<script id="prism_core-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/prism.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/prism.min.js')); ?>"></script>
					<script id="prism_line_numbers-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/plugins/line-numbers/prism-line-numbers.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/plugins/line-numbers/prism-line-numbers.min.js')); ?>"></script>
					<script id="prism_autoloader-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/plugins/autoloader/prism-autoloader.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/plugins/autoloader/prism-autoloader.min.js')); ?>"></script>
					<script id="prism_lang_clike-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-clike.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-clike.min.js')); ?>"></script>
					<script id="prism_lang_c-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-c.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-c.min.js')); ?>"></script>
					<script id="prism_lang_cpp-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-cpp.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-cpp.min.js')); ?>"></script>
					<script id="prism_lang_csharp-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-csharp.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-csharp.min.js')); ?>"></script>
					<script id="prism_lang_bash-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-bash.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-bash.min.js')); ?>"></script>
					<script id="prism_lang_python-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-python.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-python.min.js')); ?>"></script>
					<script id="prism_lang_go-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-go.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-go.min.js')); ?>"></script>
					<script id="prism_lang_rust-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-rust.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-rust.min.js')); ?>"></script>
					<script id="prism_lang_nasm-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-nasm.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-nasm.min.js')); ?>"></script>
					<script id="prism_lang_protobuf-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-protobuf.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-protobuf.min.js')); ?>"></script>
					<script id="prism_lang_zig-js" src="<?php echo esc_url($GLOBALS['assets_path'] . '/assets/vendor/prism/components/prism-zig.min.js?ver=' . argon_get_asset_file_version('assets/vendor/prism/components/prism-zig.min.js')); ?>"></script>
				<?php endif; ?>
				<script src="<?php echo esc_url($GLOBALS['assets_path'] . '/argontheme.js?v=' . argon_get_asset_file_version('argontheme.js')); ?>"></script>

		<?php if (get_option('argon_math_render') == 'mathjax3') { /*Mathjax V3*/?>

			<script>

				window.MathJax = {

					tex: {

						inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],

						displayMath: [['$','$']],

						processEscapes: true,

						packages: {'[+]': ['noerrors']}

					},

					options: {

						skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],

						ignoreHtmlClass: 'tex2jax_ignore',

						processHtmlClass: 'tex2jax_process'

					},

					loader: {

						load: ['[tex]/noerrors']

					}

				};

			</script>

			<script src="<?php echo esc_url(get_option('argon_mathjax_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js' : get_option('argon_mathjax_cdn_url')); ?>" id="MathJax-script" async></script>

		<?php }?>

		<?php if (get_option('argon_math_render') == 'mathjax2') { /*Mathjax V2*/?>

			<script type="text/x-mathjax-config" id="mathjax_v2_script">

				MathJax.Hub.Config({

					messageStyle: "none",

					tex2jax: {

						inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],

						displayMath: [['$','$']],

						processEscapes: true,

						skipTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']

					},

					menuSettings: {

						zoom: "Hover",

						zscale: "200%"

					},

					"HTML-CSS": {

						showMathMenu: "false"

					}

				});

			</script>

			<script src="<?php echo esc_url(get_option('argon_mathjax_v2_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js?config=TeX-AMS_HTML' : get_option('argon_mathjax_v2_cdn_url')); ?>"></script>

		<?php }?>

		<?php if (get_option('argon_math_render') == 'katex') { /*Katex*/?>

			<link rel="stylesheet" href="<?php echo esc_url(get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url')); ?>katex.min.css">

		<script src="<?php echo esc_url(get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url')); ?>katex.min.js"></script>

		<script src="<?php echo esc_url(get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url')); ?>contrib/auto-render.min.js" onload="window.katexAutoRenderLoaded=true"></script>

			<script>
				document.addEventListener("DOMContentLoaded", function() {
					var katexWaitStart = Date.now();
					var katexWaitTimeout = 10000;
					function tryRenderMath() {
						if (typeof renderMathInElement === 'function') {
							renderMathInElement(document.body, {
								delimiters: [
									{left: "$$", right: "$$", display: true},
									{left: "$", right: "$", display: false},
									{left: "\\(", right: "\\)", display: false},
									{left: "\\[", right: "\\]", display: true}
								]
							});
						} else if (Date.now() - katexWaitStart <= katexWaitTimeout) {
							setTimeout(tryRenderMath, 100);
						}
					}
					tryRenderMath();
				});
			</script>

		<?php }?>


	</div>

</div>

<?php wp_footer(); ?>

</body>



<?php echo get_option('argon_custom_html_foot'); ?>



</html>
