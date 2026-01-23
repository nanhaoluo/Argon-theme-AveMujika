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

						<?php 
						// 调试控制台按钮（放在页脚底部）
						if (function_exists('argon_debug_console_footer_button')) {
							argon_debug_console_footer_button();
						}
						?>

					</footer>

				</main>

			</div>

		</div>

		<!-- 加载主题核心 JS -->
		<?php $assets_version = function_exists('argon_get_assets_version') ? argon_get_assets_version() : $GLOBALS['theme_version']; ?>
		<script src="<?php echo $GLOBALS['assets_path']; ?>/argontheme.js?v=<?php echo $assets_version; ?>"></script>

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

			<script src="<?php echo get_option('argon_mathjax_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js' : get_option('argon_mathjax_cdn_url'); ?>" id="MathJax-script" async></script>

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

			<script src="<?php echo get_option('argon_mathjax_v2_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js?config=TeX-AMS_HTML' : get_option('argon_mathjax_v2_cdn_url'); ?>"></script>

		<?php }?>

		<?php if (get_option('argon_math_render') == 'katex') { /*Katex*/?>

			<link rel="stylesheet" href="<?php echo get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url'); ?>katex.min.css">

			<script src="<?php echo get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url'); ?>katex.min.js"></script>

			<script src="<?php echo get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url'); ?>contrib/auto-render.min.js" onload="window.katexAutoRenderLoaded=true"></script>

			<script>
				document.addEventListener("DOMContentLoaded", function() {
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
						} else {
							setTimeout(tryRenderMath, 100);
						}
					}
					tryRenderMath();
				});
			</script>

		<?php }?>

		<?php if (get_option('argon_enable_mermaid') == 'true') { /*Mermaid*/?>

			<?php

				// 判断是否使用本地镜像

				$mermaid_url = get_option('argon_mermaid_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js' : get_option('argon_mermaid_cdn_url');

				if (get_option('argon_mermaid_use_local') == 'true') {

					$mermaid_url = $GLOBALS['assets_path'] . '/assets/vendor/external/mermaid/mermaid.min.js';

				}

			?>

			<script src="<?php echo $mermaid_url; ?>"></script>

			<script>

				document.addEventListener("DOMContentLoaded", function() {

					mermaid.initialize({

						startOnLoad: false,

						theme: '<?php echo get_option('argon_mermaid_theme', 'default'); ?>',

						securityLevel: 'loose',

						flowchart: {

							useMaxWidth: true,

							htmlLabels: true

						}

					});

					// 处理 WP-Markdown 生成的特殊格式：<div class="mermaid"><scr'+'ipt>document.write("...")</scr'+'ipt>...</div>

					document.querySelectorAll('div.mermaid script[type="text/javascript"]').forEach(function(scriptElement) {

						let mermaidDiv = scriptElement.parentElement;

						

						// 检查是否已经处理过

						if (mermaidDiv.classList.contains('mermaid-processed')) {

							return;

						}

						mermaidDiv.classList.add('mermaid-processed');

						

						// 从 script 标签中提取代码

						let scriptContent = scriptElement.textContent;

						console.log('=== WP-Markdown Debug ===');

						console.log('Script content (first 300 chars):', scriptContent.substring(0, 300));

						

						let match = scriptContent.match(/document\.write\("(.*)"\)/s);

						

						if (match && match[1]) {

							let rawCode = match[1];

							console.log('Raw code (first 300 chars):', rawCode.substring(0, 300));

							console.log('Contains literal backslash-n:', rawCode.includes('\\n'));

							console.log('Contains actual newline:', rawCode.includes('\n'));

							

							// 解码转义字符

							let code = rawCode

								.replace(/\\n/g, '\n')

								.replace(/\\"/g, '"')

								.replace(/\\'/g, "'")

								.replace(/&lt;/g, '<')

								.replace(/&gt;/g, '>')

								.replace(/&amp;/g, '&');

							

							console.log('After decode (first 300 chars):', code.substring(0, 300));

							console.log('After decode contains newline:', code.includes('\n'));

							

							// 检查前20个字符的详细信息

							console.log('First 20 chars with codes:');

							for (let i = 0; i < Math.min(20, code.length); i++) {

								console.log('  [' + i + '] "' + code[i] + '" (code: ' + code.charCodeAt(i) + ')');

							}

							

							console.log('WP-Markdown Mermaid code found (length: ' + code.length + ')');

							console.log('Contains newlines:', code.indexOf('\n') !== -1);

							console.log('First 100 chars:', code.substring(0, 100));

							

							// 清空 div 内容并设置新内容

							mermaidDiv.innerHTML = '';

							// 使用 pre 标签包裹以保留换行符和空格

							let pre = document.createElement('pre');

							pre.textContent = code;

							pre.style.display = 'none';

							mermaidDiv.appendChild(pre);

							

							// 同时设置为 data 属性

							mermaidDiv.setAttribute('data-mermaid-code', code);

							

							console.log('Code set to div, checking...');

							console.log('div.textContent (first 100):', mermaidDiv.textContent.substring(0, 100));

							console.log('div.textContent contains newline:', mermaidDiv.textContent.includes('\n'));

							

							// 再次验证代码字符串

							console.log('About to render, code char 12:', code.charCodeAt(12), '(should be 10 for newline)');

							console.log('Code split by newline, first 3 lines:', code.split('\n').slice(0, 3));

							

							// 立即渲染这个图表

							try {

								// 直接使用 mermaid.render 而不是 init

								let id = 'mermaid-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

								mermaid.render(id, code).then(function(result) {

									mermaidDiv.innerHTML = result.svg;

									console.log('Mermaid diagram rendered successfully');

								}).catch(function(e) {

									console.error('Mermaid rendering error:', e);

									console.error('Code that failed:', code.substring(0, 200));

								});

							} catch (e) {

								console.error('Mermaid rendering error:', e);

								console.error('Code that failed:', code.substring(0, 200));

							}

						}

					});

					// 递归获取所有文本节点，保留换行符

					function getTextWithLineBreaks(element) {

						let text = '';

						for (let node of element.childNodes) {

							if (node.nodeType === Node.TEXT_NODE) {

								text += node.textContent;

							} else if (node.nodeType === Node.ELEMENT_NODE) {

								if (node.tagName === 'BR') {

									text += '\n';

								} else {

									text += getTextWithLineBreaks(node);

								}

							}

						}

						return text;

					}

					// 处理标准格式的 mermaid 代码块

					document.querySelectorAll('pre code.language-mermaid, pre code.mermaid').forEach(function(element) {

						// 检查是否已经处理过

						if (element.classList.contains('mermaid-processed')) {

							return;

						}

						element.classList.add('mermaid-processed');

						let pre = element.parentElement;

						// 优先从 data-mermaid-code 属性获取代码

						let code = element.getAttribute('data-mermaid-code');

						

						// 如果没有 data 属性，尝试从内容获取

						if (!code) {

							code = getTextWithLineBreaks(element);

						}

						// 去除首尾空白

						code = code.trim();

						// 验证是否为有效的 Mermaid 代码

						let validKeywords = ['graph', 'flowchart', 'sequenceDiagram', 'classDiagram', 'stateDiagram', 'erDiagram', 'journey', 'gantt', 'pie', 'gitGraph', 'mindmap', 'timeline', 'quadrantChart'];

						let isValid = false;

						for (let keyword of validKeywords) {

							if (code.toLowerCase().startsWith(keyword.toLowerCase())) {

								isValid = true;

								break;

							}

						}

						// 如果不是有效的 Mermaid 代码，跳过

						if (!isValid) {

							console.log('Skipping invalid Mermaid code:', code.substring(0, 50));

							return;

						}

						// 调试：输出代码内容和换行符

						console.log('Standard Mermaid code found (length: ' + code.length + ')');

						console.log('Contains newlines:', code.indexOf('\n') !== -1);

						console.log('First 100 chars:', code.substring(0, 100));

						// 创建 Mermaid 容器

						let mermaidDiv = document.createElement('div');

						mermaidDiv.className = 'mermaid';

						mermaidDiv.textContent = code;

						// 替换原来的 pre 元素

						pre.parentNode.replaceChild(mermaidDiv, pre);

						

						// 立即渲染

						try {

							mermaid.init(undefined, mermaidDiv);

							console.log('Standard Mermaid diagram rendered successfully');

						} catch (e) {

							console.error('Mermaid rendering error:', e);

							console.error('Code that failed:', code.substring(0, 200));

						}

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
