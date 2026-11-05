<tr><th class="subtitle" colspan="2"><h3 id="section-enhancements"><?php _e('功能增强', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-code-highlight"><?php _e('代码高亮', 'argon');?></h4></th></tr>
	<tr>
		<th><label><?php _e('启用 Prism.js 代码高亮', 'argon');?></label></th>
		<td>
			<select name="argon_enable_code_highlight">
				<?php $argon_enable_code_highlight = get_option('argon_enable_code_highlight'); ?>
				<option value="false" <?php if ($argon_enable_code_highlight=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>
				<option value="true" <?php if ($argon_enable_code_highlight=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
			</select>
			<p class="description"><?php _e('所有 pre 下的 code 标签会被自动解析，内置 290+ 种主流编程语言与标记语言支持并自动按需加载', 'argon');?></p>
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
			<p class="description"><?php _e('代码高亮基于 Prism.js，主题采用高对比深色 Okaidia，并支持行号、折行与一键复制', 'argon');?></p>
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-math"><?php _e('数学公式', 'argon');?></h4></th></tr>
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
								<input type="text" class="regular-text" name="argon_mathjax_cdn_url" value="<?php echo esc_attr(get_option('argon_mathjax_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js' : get_option('argon_mathjax_cdn_url')); ?>"/>
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
								<input type="text" class="regular-text" name="argon_mathjax_v2_cdn_url" value="<?php echo esc_attr(get_option('argon_mathjax_v2_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js?config=TeX-AMS_HTML' : get_option('argon_mathjax_v2_cdn_url')); ?>"/>
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
								<input type="text" class="regular-text" name="argon_katex_cdn_url" value="<?php echo esc_attr(get_option('argon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('argon_katex_cdn_url')); ?>"/>
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-lightbox"><?php _e('图片放大浏览', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('是否启用图片查看器', 'argon');?></label></th>
	<td>
		<select name="argon_enable_fancybox">
			<?php $argon_enable_fancybox = get_option('argon_enable_fancybox'); ?>
			<option value="true" <?php if ($argon_enable_fancybox=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
			<option value="false" <?php if ($argon_enable_fancybox=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		</select>
		<p class="description"><?php _e('开启后，单击文章、说说正文中的图片可放大预览，支持左右切换、滚轮缩放、拖拽平移与键盘操作（Esc 关闭、方向键切换）', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-pangu">Pangu.js</h4></th></tr>
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

<tr><th class="subtitle" colspan="2"><h3 id="section-attachments"><?php _e('附件下载', 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e('允许上传的附件格式', 'argon');?></label></th>
	<td>
		<input type="text" class="large-text" name="argon_attachment_allowed_extensions" value="<?php echo esc_attr(get_option('argon_attachment_allowed_extensions', '')); ?>" placeholder="*,zip,rar,7z,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,apk,mp3,flac,mp4,mkv,jpg,jpeg,png,gif,webp,epub,mobi"/>
		<p class="description"><?php _e('逗号分隔的扩展名白名单（不含点）。包含 * 或留空表示不设格式限制（支持任意非可执行风险的文件格式）。php、html、js 等脚本格式存在安全风险，始终禁止直接作为脚本上传。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('单文件大小上限', 'argon');?></label></th>
	<td>
		<input type="number" name="argon_attachment_max_size" min="0" max="10240" value="<?php echo esc_attr(get_option('argon_attachment_max_size', '0')); ?>"/> MB
		<p class="description"><?php _e('单文件大小上限（MB），填 0 或留空表示不设主题上限（仅受服务器 PHP 的 upload_max_filesize / post_max_size 物理限制）。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('附件下载验证码', 'argon');?></label></th>
	<td>
		<?php $argon_att_captcha = get_option('argon_attachment_captcha_mode', 'disabled'); ?>
		<select name="argon_attachment_captcha_mode">
			<option value="disabled" <?php if ($argon_att_captcha == 'disabled'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>
			<option value="enabled" <?php if ($argon_att_captcha == 'enabled'){echo 'selected';} ?>><?php _e('强制启用（使用全局验证码配置）', 'argon');?></option>
			<option value="global" <?php if ($argon_att_captcha == 'global'){echo 'selected';} ?>><?php _e('跟随全局验证码开关', 'argon');?></option>
		</select>
		<p class="description"><?php _e('开启后，用户在下载页面需完成人机验证校验后方可获取文件（支持数学验证码或极验）。', 'argon');?></p>
	</td>
</tr>

<tr><th class="subtitle" colspan="2"><h3 id="section-easter-eggs"><?php _e('彩蛋系统', 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e('彩蛋管理', 'argon');?></label></th>
	<td>
		<?php
		$available_eggs = argon_get_available_easter_eggs();
		if (empty($available_eggs)) {
			echo '<p>' . __('未找到可用的彩蛋效果文件', 'argon') . '</p>';
		} else {
			echo '<table class="widefat" style="margin-top:10px;">';
			echo '<thead><tr>';
			echo '<th>' . __('彩蛋名称', 'argon') . '</th>';
			echo '<th>' . __('描述', 'argon') . '</th>';
			echo '<th>' . __('启用', 'argon') . '</th>';
			echo '<th>' . __('日期', 'argon') . '</th>';
			echo '<th>' . __('类型', 'argon') . '</th>';
			echo '<th>' . __('持续天数', 'argon') . '</th>';
			echo '</tr></thead><tbody>';
			foreach ($available_eggs as $egg_id => $egg_data) {
				$config = argon_get_easter_egg_config($egg_id);
				$enabled = isset($config['enabled']) ? $config['enabled'] : false;
				$date = isset($config['date']) ? $config['date'] : $egg_data['default_date'];
				$type = isset($config['type']) ? $config['type'] : $egg_data['default_type'];
				$duration = isset($config['duration']) ? $config['duration'] : 1;
				echo '<tr>';
				echo '<td><strong>' . esc_html($egg_data['name']) . '</strong></td>';
				echo '<td>' . esc_html($egg_data['description']) . '</td>';
				echo '<td><input type="checkbox" name="argon_easter_egg_' . $egg_id . '_enabled" value="1" ' . checked($enabled, true, false) . ' /></td>';
				echo '<td><input type="text" name="argon_easter_egg_' . $egg_id . '_date" value="' . esc_attr($date) . '" placeholder="MM-DD" style="width:80px;" /></td>';
				echo '<td><select name="argon_easter_egg_' . $egg_id . '_type">';
				echo '<option value="solar" ' . selected($type, 'solar', false) . '>' . __('阳历', 'argon') . '</option>';
				echo '<option value="lunar" ' . selected($type, 'lunar', false) . '>' . __('农历', 'argon') . '</option>';
				echo '</select></td>';
				echo '<td><input type="number" name="argon_easter_egg_' . $egg_id . '_duration" value="' . esc_attr($duration) . '" min="1" max="365" style="width:60px;" /></td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		?>
		<p class="description"><?php _e('彩蛋效果文件存放在 easter-egg/ 目录，可自行添加新的彩蛋效果', 'argon');?></p>
	</td>
</tr>
