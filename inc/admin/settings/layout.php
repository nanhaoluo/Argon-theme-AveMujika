<tr><th class="subtitle" colspan="2"><h3 id="section-page-layout"><?php _e('整体布局', 'argon');?></h3></th></tr>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-toolbar"><?php _e('顶栏设置', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-toolbar-basic"><?php _e('基本设置', 'argon');?></h4></th></tr>
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-toolbar-title"><?php _e('顶栏标题', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('顶栏标题', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_toolbar_title" value="<?php echo esc_attr(get_option('argon_toolbar_title')); ?>"/></p>
		<p class="description"><?php _e('留空则显示博客名称，输入 <code>--hidden--</code> 可以隐藏标题', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-toolbar-icon"><?php _e('顶栏图标', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('图标地址', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_toolbar_icon" value="<?php echo esc_attr(get_option('argon_toolbar_icon')); ?>"/>
		<p class="description"><?php _e('图片地址，留空则不显示', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('图标链接', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_toolbar_icon_link" value="<?php echo esc_attr(get_option('argon_toolbar_icon_link')); ?>"/>
		<p class="description"><?php _e('点击图标后会跳转到的链接，留空则不跳转', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-toolbar-appearance"><?php _e('顶栏外观', 'argon');?></h4></th></tr>
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-toolbar-links"><?php _e('自定义链接', 'argon');?></h4></th></tr>
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

<tr><th class="subtitle" colspan="2"><h3 id="section-banner"><?php _e('Banner 设置', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-banner-content"><?php _e('Banner 内容', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('Banner 标题', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_banner_title" value="<?php echo esc_attr(get_option('argon_banner_title')); ?>"/>
		<p class="description"><?php _e('留空则显示博客名称', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('Banner 副标题', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_banner_subtitle" value="<?php echo esc_attr(get_option('argon_banner_subtitle')); ?>"/>
		<p class="description"><?php _e('显示在 Banner 标题下，留空则不显示', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-banner-appearance"><?php _e('Banner 外观', 'argon');?></h4></th></tr>
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
		<input type="text" class="regular-text" name="argon_banner_background_url" value="<?php echo esc_attr(get_option('argon_banner_background_url')); ?>"/>
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-banner-animation"><?php _e('Banner 动画', 'argon');?></h4></th></tr>
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
		<input type="number" name="argon_banner_typing_effect_interval" min="1" max="10000"  value="<?php echo esc_attr(get_option('argon_banner_typing_effect_interval') == '' ? '100' : get_option('argon_banner_typing_effect_interval')); ?>"/> <?php _e('ms/字', 'argon');?>
		<p class="description"></p>
	</td>
</tr>

<tr><th class="subtitle" colspan="2"><h3 id="section-author-info"><?php _e('作者信息', 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e('左侧栏标题', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_sidebar_banner_title" value="<?php echo esc_attr(get_option('argon_sidebar_banner_title')); ?>"/>
		<p class="description"><?php _e('留空则显示博客名称', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('左侧栏子标题（格言）', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_sidebar_banner_subtitle" value="<?php echo esc_attr(get_option('argon_sidebar_banner_subtitle')); ?>"/>
		<p class="description"><?php _e('留空则不显示', 'argon');?><br/><?php _e('输入', 'argon');?> <code>--hitokoto--</code> <?php _e('调用一言 API', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('左侧栏作者名称', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_sidebar_auther_name" value="<?php echo esc_attr(get_option('argon_sidebar_auther_name')); ?>"/>
		<p class="description"><?php _e('留空则显示博客名', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('左侧栏作者头像地址', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_sidebar_auther_image" value="<?php echo esc_attr(get_option('argon_sidebar_auther_image')); ?>"/>
		<p class="description"><?php _e('需带上 http(s) 开头', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('左侧栏作者简介', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_sidebar_author_description" value="<?php echo esc_attr(get_option('argon_sidebar_author_description')); ?>"/>
		<p class="description"><?php _e('留空则不显示', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h3 id="section-sidebar-features"><?php _e('扩展功能', 'argon');?></h3></th></tr>
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
		<input name="argon_duolingo_username" type="text" class="regular-text" value="<?php echo esc_attr(get_option('argon_duolingo_username', '')); ?>">
		<p class="description"><?php _e('填写你的多邻国用户名以自动获取连胜天数（需要在多邻国隐私设置中开启公开资料）', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('启用追番页', 'argon');?></label></th>
	<td>
		<select name="argon_bangumi_enabled">
			<?php $argon_bangumi_enabled = get_option('argon_bangumi_enabled', 'false'); ?>
			<option value="false" <?php selected($argon_bangumi_enabled, 'false'); ?>><?php _e('关闭', 'argon');?></option>
			<option value="true" <?php selected($argon_bangumi_enabled, 'true'); ?>><?php _e('启用', 'argon');?></option>
		</select>
		<a href="<?php echo esc_url(home_url('/bangumi/')); ?>" class="button" target="_blank" rel="noopener noreferrer"><?php _e('打开追番页面', 'argon');?></a>
		<p class="description"><?php _e('启用后可直接访问 /bangumi/，集中展示本地化的 B 站追番数据，无需新建页面。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('B 站 UID', 'argon');?></label></th>
	<td>
		<input name="argon_bangumi_uid" type="text" class="regular-text" inputmode="numeric" value="<?php echo esc_attr(get_option('argon_bangumi_uid', '349546967')); ?>">
		<p class="description"><?php _e('填写要展示的 B 站用户 UID，默认值为 349546967。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('显示 RSS 订阅链接', 'argon');?></label></th>
	<td>
		<select name="argon_show_rss_link">
			<?php $argon_show_rss_link = get_option('argon_show_rss_link', 'false'); ?>
			<option value="false" <?php if ($argon_show_rss_link=='false'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>
			<option value="true" <?php if ($argon_show_rss_link=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>
		</select>
		<p class="description"><?php _e('在左侧栏博客概览下方显示 RSS 订阅链接', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h3 id="section-announcement"><?php _e('博客公告', 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e('公告内容', 'argon');?></label></th>
	<td>
		<textarea type="text" rows="5" cols="50" name="argon_sidebar_announcement"><?php echo htmlspecialchars(get_option('argon_sidebar_announcement')); ?></textarea>
		<p class="description"><?php _e('显示在左侧栏顶部，留空则不显示，支持 HTML 标签', 'argon');?></p>
	</td>
</tr>

<tr><th class="subtitle" colspan="2"><h3 id="section-fab"><?php _e('浮动按钮', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-fab"><?php _e('浮动操作按钮', 'argon');?></h4></th></tr>
<tr><th class="subtitle" colspan="2"><p class="description"><?php _e('浮动操作按钮位于页面右下角（或左下角）', 'argon');?></p></th></tr>
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

<tr><th class="subtitle" colspan="2"><h3 id="section-footer"><?php _e('页脚设置', 'argon');?></h3></th></tr>

<tr>
	<th><label><?php _e('页脚内容', 'argon');?></label></th>
	<td>
		<textarea type="text" rows="15" cols="100" name="argon_footer_html"><?php echo htmlspecialchars(get_option('argon_footer_html')); ?></textarea>
		<p class="description"><?php _e('HTML , 支持 script 等标签', 'argon');?></p>
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
<tr>
	<th><label><?php _e('Git 徽章', 'argon');?></label></th>
	<td>
		<select name="argon_show_git_badge">
			<?php $argon_show_git_badge = get_option('argon_show_git_badge', 'true'); ?>
			<option value="true" <?php if ($argon_show_git_badge=='true'){echo 'selected';} ?>><?php _e('显示', 'argon');?></option>
			<option value="false" <?php if ($argon_show_git_badge=='false'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>
		</select>
		<p class="description"><?php _e('控制页脚 Git 版本徽章是否显示；显示时点击徽章会跳转到 GitHub 仓库。', 'argon');?></p>
	</td>
</tr>
