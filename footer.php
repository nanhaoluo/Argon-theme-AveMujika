					<footer id="footer" class="site-footer card shadow-sm border-0">

						<?php

							echo get_option('argon_footer_html');

						?>

						<div>❤Theme <a href="https://github.com/solstice23/argon-theme" target="_blank"><strong>Argon</strong></a><?php if (get_option('argon_hide_footer_author') != 'true') {echo " By solstice23"; }?>❤<?php
						// 显示 Git 版本信息
						$git_info = argon_get_git_info();
						if ($git_info) :
						?> <span class="git-version-badge" id="git-badge"><svg class="git-icon" viewBox="0 0 448 512" fill="currentColor"><path d="M439.55 236.05L244 40.45a28.87 28.87 0 0 0-40.81 0l-40.66 40.63l51.52 51.52c27.06-9.14 52.68 16.77 43.39 43.68l49.66 49.66c34.23-11.8 61.18 31 35.47 56.69c-26.49 26.49-70.21-2.87-56-37.34L240.22 199v121.85c25.3 12.54 22.26 41.85 9.08 55a34.34 34.34 0 0 1-48.55 0c-17.57-17.6-11.07-46.91 11.25-56v-123c-20.8-8.51-24.6-30.74-18.64-45L142.57 101L8.45 235.14a28.86 28.86 0 0 0 0 40.81l195.61 195.6a28.86 28.86 0 0 0 40.8 0l194.69-194.69a28.86 28.86 0 0 0 0-40.81z"/></svg><?php echo esc_html($git_info['branch']); ?>@<?php echo esc_html($git_info['commit']); ?></span>
						<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/js/easter-egg.js"></script>
						<?php endif; ?></div>

					</footer>

				</main>

			</div>

		</div>

		<!-- Argon 修复补丁 - 在所有脚本加载后重新注册插件 -->
		<script>
		(function() {
			if (typeof jQuery !== 'undefined') {
				var $ = jQuery;
				
				// 强制重新注册 headIndex 插件
				$.fn.headIndex = function(options) {
					var defaults = {
						articleWrapSelector: '#post_content',
						indexBoxSelector: '#leftbar_catalog',
						subItemBoxClass: 'index-subItem-box',
						scrollOffset: 80,
						activeClass: 'active'
					};
					
					var settings = $.extend({}, defaults, options);
					var $articleWrap = $(settings.articleWrapSelector);
					var $indexBox = $(settings.indexBoxSelector);
					
					if (!$articleWrap.length || !$indexBox.length) {
						return this;
					}
					
					var $headings = $articleWrap.find('h1, h2, h3, h4, h5, h6');
					
					if ($headings.length === 0) {
						$indexBox.html('<div class="no-catalog">暂无目录</div>');
						return this;
					}
					
					// 生成目录 HTML（不带序号）
					var catalogHtml = '<ul class="catalog-list">';
					var minLevel = 6;
					
					$headings.each(function() {
						var level = parseInt(this.tagName.substring(1));
						if (level < minLevel) minLevel = level;
					});
					
					$headings.each(function(index) {
						var $heading = $(this);
						var level = parseInt(this.tagName.substring(1));
						var text = $heading.text().trim();
						var id = $heading.attr('id') || 'heading-' + index;
						
						if (!$heading.attr('id')) {
							$heading.attr('id', id);
						}
						
						var indent = (level - minLevel) * 15;
						catalogHtml += '<li style="padding-left: ' + indent + 'px;">' +
							'<a href="#' + id + '" class="catalog-link" data-target="' + id + '">' + 
							text + '</a></li>';
					});
					
					catalogHtml += '</ul>';
					$indexBox.html(catalogHtml);
					
					// 点击事件
					$indexBox.off('click.headIndex').on('click.headIndex', '.catalog-link', function(e) {
						e.preventDefault();
						var targetId = $(this).data('target');
						var $target = $('#' + targetId);
						
						if ($target.length) {
							$('html, body').animate({
								scrollTop: $target.offset().top - settings.scrollOffset
							}, 500);
							
							$indexBox.find('.catalog-link').removeClass(settings.activeClass);
							$(this).addClass(settings.activeClass);
						}
					});
					
					// 滚动高亮
					var throttleTimer = null;
					$(window).off('scroll.headIndex').on('scroll.headIndex', function() {
						if (throttleTimer) clearTimeout(throttleTimer);
						
						throttleTimer = setTimeout(function() {
							var scrollTop = $(window).scrollTop();
							var currentHeading = null;
							
							$headings.each(function() {
								var headingTop = $(this).offset().top - settings.scrollOffset - 50;
								if (scrollTop >= headingTop) {
									currentHeading = $(this).attr('id');
								}
							});
							
							if (currentHeading) {
								$indexBox.find('.catalog-link').removeClass(settings.activeClass);
								$indexBox.find('.catalog-link[data-target="' + currentHeading + '"]')
									.addClass(settings.activeClass);
							}
						}, 100);
					});
					
					return this;
				};
				
				window.argonPluginsReady = true;
			}
		})();
		</script>
		
		<!-- argontheme.js 需要在 jQuery 和插件加载后执行 -->
		<script>
		(function() {
			function loadArgonTheme() {
				// 检查 jQuery 和关键插件是否都已加载
				if (typeof jQuery !== 'undefined' && 
					typeof jQuery.fn.lazyload === 'function' &&
					window.argonPluginsReady === true) {
					var script = document.createElement('script');
					script.src = '<?php echo $GLOBALS['assets_path']; ?>/argontheme.js?v<?php echo $GLOBALS['theme_version']; ?>';
					document.body.appendChild(script);
				} else {
					setTimeout(loadArgonTheme, 50);
				}
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', loadArgonTheme);
			} else {
				loadArgonTheme();
			}
		})();
		</script>

		<?php if (get_option('argon_math_render') == 'mathjax3') { /*Mathjax V3*/?>

			<script>

				window.MathJax = {

					tex: {

						inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],

						displayMath: [['$$','$$']],

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

			<script src="<?php echo get_option('argon_mathjax_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js' : get_option('argon_mathjax_cdn_url'); ?>" id="MathJax-script" async></script>

		<?php }?>

		<?php if (get_option('argon_math_render') == 'mathjax2') { /*Mathjax V2*/?>

			<script type="text/x-mathjax-config" id="mathjax_v2_script">

				MathJax.Hub.Config({

					messageStyle: "none",

					tex2jax: {

						inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],

						displayMath: [['$$','$$']],

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

			<script src="<?php echo get_option('argon_mathjax_v2_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js?config=TeX-AMS_HTML' : get_option('argon_mathjax_v2_cdn_url'); ?>"></script>

		<?php }?>

		<?php if (get_option('argon_math_render') == 'katex') { /*Katex*/?>

			<link rel="stylesheet" href="<?php echo get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url'); ?>katex.min.css">

			<script src="<?php echo get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url'); ?>katex.min.js"></script>

			<script src="<?php echo get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url'); ?>contrib/auto-render.min.js"></script>

			<script>

				document.addEventListener("DOMContentLoaded", function() {

					renderMathInElement(document.body,{

						delimiters: [

							{left: "$$", right: "$$", display: true},

							{left: "$", right: "$", display: false},

							{left: "\\(", right: "\\)", display: false}

						]

					});

				});

			</script>

		<?php }?>



		<?php if (get_option('argon_enable_code_highlight') == 'true') { /*Highlight.js*/?>

			<link rel="stylesheet" href="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/highlight/styles/<?php echo get_option('argon_code_theme') == '' ? 'vs2015' : get_option('argon_code_theme'); ?>.css">

		<?php }?>



	</div>

</div>

<?php wp_footer(); ?>

</body>



<?php echo get_option('argon_custom_html_foot'); ?>



</html>

