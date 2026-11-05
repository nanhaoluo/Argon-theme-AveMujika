<tr><th class="subtitle" colspan="2"><h3 id="section-theme-color"><?php _e("主题色", 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e("主题颜色", 'argon');?></label></th>
	<td>
		<input type="color" class="regular-text" name="argon_theme_color" value="<?php echo esc_attr(get_option('argon_theme_color') == "" ? "#5e72e4" : get_option('argon_theme_color')); ?>" style="height:40px;width: 80px;cursor: pointer;"/>
		<input type="text" readonly name="argon_theme_color_hex_preview" value="<?php echo esc_attr(get_option('argon_theme_color') == "" ? "#5e72e4" : get_option('argon_theme_color')); ?>" style="height: 40px;width: 80px;vertical-align: bottom;background: #fff;cursor: pointer;" onclick="jQuery('input[name=\'argon_theme_color\']').click()"/></p>
		<p class="description"><div style="margin-top: 15px;"><?php _e("选择预置颜色 或", 'argon');?> <span onclick="jQuery('input[name=\'argon_theme_color\']').click()" style="text-decoration: underline;cursor: pointer;"><?php _e("自定义色值", 'argon');?></span>
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
				jQuery("input[name='argon_theme_color']").on("change" , function(){
					jQuery("input[name='argon_theme_color_hex_preview']").val(jQuery("input[name='argon_theme_color']").val());
				});
				jQuery(".themecolor-preview").on("click" , function(){
					jQuery("input[name='argon_theme_color']").val(jQuery(this).attr("color"));
					jQuery("input[name='argon_theme_color']").trigger("change");
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
<tr><th class="subtitle" colspan="2"><h3 id="section-dark-mode"><?php _e('夜间模式', 'argon');?></h3></th></tr>
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

<tr><th class="subtitle" colspan="2"><h3 id="section-card-style"><?php _e('卡片样式', 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e('卡片圆角大小', 'argon');?></label></th>
	<td>
		<input type="number" name="argon_card_radius" min="0" max="30" step="0.5" value="<?php echo esc_attr(get_option('argon_card_radius') == '' ? '4' : get_option('argon_card_radius')); ?>"/>	px
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
<tr><th class="subtitle" colspan="2"><h3 id="section-font"><?php _e('字体', 'argon');?></h3></th></tr>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-background"><?php _e('页面背景', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-background-image"><?php _e('背景图片', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('页面背景', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_page_background_url" value="<?php echo esc_attr(get_option('argon_page_background_url')); ?>"/>
		<p class="description"><?php _e('页面背景的地址，需带上 http(s)。留空则不设置页面背景。如果设置了背景，推荐开启 Banner 透明化。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('页面背景（夜间模式时）', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_page_background_dark_url" value="<?php echo esc_attr(get_option('argon_page_background_dark_url')); ?>"/>
		<p class="description"><?php _e('夜间模式时页面背景的地址，需带上 http(s)。设置后日间模式和夜间模式会使用不同的背景。留空则跟随日间模式背景。该选项仅在设置了日间模式背景时生效。', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-transparency"><?php _e('透明度与毛玻璃', 'argon');?></h4></th></tr>
<tr>
	<td colspan="2" style="padding: 20px 10px;">
		<div style="display: flex; gap: 40px; align-items: flex-start;">
			<!-- 左侧：设置项 -->
			<div style="flex: 1; max-width: 450px;">
				<!-- 背景不透明度 -->
				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('背景不透明度', 'argon');?></label>
					<div style="display: flex; align-items: center; gap: 12px;">
						<input type="range" name="argon_page_background_opacity" id="argon_page_bg_opacity_slider" min="0" max="1" step="0.01" value="<?php echo esc_attr(get_option('argon_page_background_opacity') == '' ? '1' : get_option('argon_page_background_opacity')); ?>" style="flex: 1; cursor: pointer; height: 6px;">
						<span id="argon_page_bg_opacity_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html(get_option('argon_page_background_opacity') == '' ? '1.00' : number_format(floatval(get_option('argon_page_background_opacity')), 2)); ?></span>
					</div>
					<p style="margin: 6px 0 0; font-size: 12px; color: #646970;"><?php _e('控制页面背景图片的不透明度', 'argon');?></p>
				</div>
				<!-- 卡片透明度 -->
				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('卡片透明度', 'argon');?></label>
					<div style="display: flex; align-items: center; gap: 12px;">
						<input type="range" name="argon_post_background_opacity" id="argon_post_bg_opacity_slider" min="0" max="1" step="0.01" value="<?php echo esc_attr(get_option('argon_post_background_opacity') == '' ? '0.7' : get_option('argon_post_background_opacity')); ?>" style="flex: 1; cursor: pointer; height: 6px;">
						<span id="argon_post_bg_opacity_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html(get_option('argon_post_background_opacity') == '' ? '0.70' : number_format(floatval(get_option('argon_post_background_opacity')), 2)); ?></span>
					</div>
					<p style="margin: 6px 0 0; font-size: 12px; color: #646970;"><?php _e('控制文章、评论等卡片的背景透明度，推荐 0.7', 'argon');?></p>
				</div>
				<!-- 毛玻璃模糊 -->
				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('毛玻璃模糊', 'argon');?></label>
					<div style="display: flex; align-items: center; gap: 12px;">
						<input type="range" name="argon_card_blur" min="0" max="30" step="1" value="<?php echo esc_attr(get_option('argon_card_blur', '20')); ?>" id="argon_card_blur_slider" style="flex: 1; cursor: pointer; height: 6px;">
						<span id="argon_card_blur_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html(get_option('argon_card_blur', '20')); ?>px</span>
					</div>
					<p style="margin: 6px 0 0; font-size: 12px; color: #646970;"><?php _e('为卡片添加毛玻璃模糊效果，推荐 20px', 'argon');?></p>
				</div>
				<!-- 毛玻璃饱和度 -->
				<div style="margin-bottom: 10px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327;"><?php _e('毛玻璃饱和度', 'argon');?></label>
					<div style="display: flex; align-items: center; gap: 12px;">
						<input type="range" name="argon_card_saturate" min="100" max="250" step="10" value="<?php echo esc_attr(get_option('argon_card_saturate', '180')); ?>" id="argon_card_saturate_slider" style="flex: 1; cursor: pointer; height: 6px;">
						<span id="argon_card_saturate_value" style="min-width: 50px; text-align: center; font-family: monospace; font-size: 13px; background: #f0f0f1; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html(get_option('argon_card_saturate', '180')); ?>%</span>
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
					<img id="effect_preview_bg" src="<?php echo esc_url($page_bg_url); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: <?php echo esc_attr(get_option('argon_page_background_opacity', '1')); ?>;">
					<?php } ?>
					<div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
						<div id="blur_preview_card" style="width: 140px; height: 85px; background: rgba(255,255,255,<?php echo esc_attr(get_option('argon_post_background_opacity', '0.7')); ?>); border-radius: 10px; backdrop-filter: blur(<?php echo esc_attr(get_option('argon_card_blur', '20')); ?>px) saturate(<?php echo esc_attr(get_option('argon_card_saturate', '180')); ?>%); -webkit-backdrop-filter: blur(<?php echo esc_attr(get_option('argon_card_blur', '20')); ?>px) saturate(<?php echo esc_attr(get_option('argon_card_saturate', '180')); ?>%); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid rgba(255,255,255,0.4);">
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

<tr><th class="subtitle" colspan="2"><h3 id="section-title-style"><span id="subsection-title-style"></span><?php _e('标题样式', 'argon');?></h3></th></tr>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-animation"><?php _e('动画效果', 'argon');?></h3></th></tr>
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

<tr><th class="subtitle" colspan="2"><h3 id="section-admin-appearance">登录与后台</h3></th></tr>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-mobile-accessibility">移动端显示</h3></th></tr>
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
