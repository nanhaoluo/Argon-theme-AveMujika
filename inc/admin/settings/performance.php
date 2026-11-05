<tr><th class="subtitle" colspan="2"><h3 id="section-pjax">页面局部刷新</h3></th></tr>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-lazyload"><span id="subsection-lazyload"></span>Lazyload</h3></th></tr>
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
		<input type="number" name="argon_lazyload_threshold" min="0" max="2500"  value="<?php echo esc_attr(get_option('argon_lazyload_threshold') == '' ? '800' : get_option('argon_lazyload_threshold')); ?>"/>
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
