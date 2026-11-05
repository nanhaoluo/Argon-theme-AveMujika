<tr><th class="subtitle" colspan="2"><h3 id="section-article-list"><?php _e('文章列表', 'argon');?></h3></th></tr>
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

<tr><th class="subtitle" colspan="2"><h3 id="section-content-preview">首页筛选与内容预览</h3></th></tr>
<tr>
	<th><label><?php _e('首页隐藏特定 分类/Tag 下的文章', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_hide_categories" value="<?php echo esc_attr(get_option('argon_hide_categories')); ?>"/>
		<p class="description"><?php _e('输入要隐藏的 分类/Tag 的 ID，用英文逗号分隔，留空则不隐藏', 'argon');?><br/><a onclick="jQuery('#id_of_categories_and_tags').slideDown(500);" style="cursor: pointer;"><?php _e('点此查看', 'argon');?></a><?php _e('所有分类和 Tag 的 ID', 'argon');?>
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
		<input type="number" name="argon_trim_words_count" min="0" max="1000" value="<?php echo esc_attr(get_option('argon_trim_words_count', 175)); ?>"/>
		<p class="description"><?php _e('设为 0 来隐藏文章内容预览', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h3 id="section-post-meta"><?php _e('Meta 信息', 'argon');?></h3></th></tr>
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
		<input type="text" class="regular-text" name="argon_article_meta" value="<?php echo esc_attr(get_option('argon_article_meta', 'time|views|comments|categories')); ?>" style="display: none;"/>
		<?php _e('拖动来自定义文章 Meta 信息的显示和顺序', 'argon');?>
		<div class="article-meta-container">
			<?php _e('显示', 'argon');?>
			<div id="article_meta_active" data-argon-dynamic-container></div>
		</div>
		<div class="article-meta-container">
			<?php _e('不显示', 'argon');?>
			<div id="article_meta_inactive" data-argon-dynamic-container>
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
			let articleMeta = jQuery("input[name='argon_article_meta']").val().split("|");
			for (let metaName of articleMeta){
				let itemDiv = jQuery("#article_meta_inactive .article-meta-item[meta-name='"+ metaName + "']");
				jQuery("#article_meta_active").append(itemDiv.prop("outerHTML"));
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
			jQuery("#article_meta_active .article-meta-item").each(function(index, item) {
				if (index != 0){
					articleMeta += "|";
				}
				articleMeta += item.getAttribute("meta-name");
			});
			jQuery("input[name='argon_article_meta']").val(articleMeta).trigger('change');
		});
	</script>
</tr>
<tr><th class="subtitle" colspan="2"><h4><?php _e('第二行', 'argon');?></h4></th></tr>
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
		<input type="number" name="argon_reading_speed" min="1" max="5000"  value="<?php echo esc_attr(get_option('argon_reading_speed') == '' ? '300' : get_option('argon_reading_speed')); ?>"/>
		<?php _e('字/分钟', 'argon');?>
		<p class="description"></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('每分钟阅读单词数（英文）', 'argon');?></label></th>
	<td>
		<input type="number" name="argon_reading_speed_en" min="1" max="5000"  value="<?php echo esc_attr(get_option('argon_reading_speed_en') == '' ? '160' : get_option('argon_reading_speed_en')); ?>"/>
		<?php _e('单词/分钟', 'argon');?>
	</td>
</tr>
<tr>
	<th><label><?php _e('每分钟阅读代码行数', 'argon');?></label></th>
	<td>
		<input type="number" name="argon_reading_speed_code" min="1" max="5000"  value="<?php echo esc_attr(get_option('argon_reading_speed_code') == '' ? '20' : get_option('argon_reading_speed_code')); ?>"/>
		<?php _e('行/分钟', 'argon');?>
		<p class="description"><?php _e('预计阅读时间由每分钟阅读字数计算', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h3 id="section-thumbnail"><?php _e('文章头图', 'argon');?></h3></th></tr>
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
				
<tr><th class="subtitle" colspan="2"><h3 id="section-post-features"><?php _e('文章功能', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-footnote"><?php _e('脚注引用', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('脚注列表标题', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_reference_list_title" value="<?php echo esc_attr(get_option('argon_reference_list_title') == "" ? __('参考', 'argon') : get_option('argon_reference_list_title')); ?>"/>
		<p class="description"><?php _e('脚注列表显示在文末，在文章中有脚注的时候会显示。<br/>使用 <code>ref</code> 短代码可以在文中插入脚注。', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-share"><?php _e('分享', 'argon');?></h4></th></tr>
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-toc"><?php _e('文章目录', 'argon');?></h4></th></tr>
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-donate"><?php _e('赞赏', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('赞赏二维码图片链接', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_donate_qrcode_url" value="<?php echo esc_attr(get_option('argon_donate_qrcode_url')); ?>"/>
		<p class="description"><?php _e('赞赏二维码图片链接，填写后会在文章最后显示赞赏按钮，留空则不显示赞赏按钮', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-post-footer"><?php _e('文末内容', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('文末附加内容', 'argon');?></label></th>
	<td>
		<textarea type="text" rows="5" cols="100" name="argon_additional_content_after_post"><?php echo htmlspecialchars(get_option('argon_additional_content_after_post')); ?></textarea>
		<p class="description"><?php _e('将会显示在每篇文章末尾，支持 HTML 标签，留空则不显示。', 'argon');?><br/><?php _e('使用 <code>%url%</code> 来代替当前页面 URL，<code>%link%</code> 来代替当前页面链接，<code>%title%</code> 来代替当前文章标题，<code>%author%</code> 来代替当前文章作者。', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-related-posts"><?php _e('相似文章推荐', 'argon');?></h4></th></tr>
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
		<input type="number" name="argon_related_post_limit" min="1" max="100" value="<?php echo esc_attr(get_option('argon_related_post_limit' , '10')); ?>"/>
		<p class="description"><?php _e('最多推荐多少篇文章', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-post-other"><?php _e('过时提示', 'argon');?></h4></th></tr>
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
		<input type="number" name="argon_outdated_info_days" min="-1" max="99999"  value="<?php echo esc_attr(get_option('argon_outdated_info_days') == '' ? '-1' : get_option('argon_outdated_info_days')); ?>"/>
		<?php _e('天时，用', 'argon');?>
		<select name="argon_outdated_info_tip_type">
			<?php $argon_outdated_info_tip_type = get_option('argon_outdated_info_tip_type'); ?>
			<option value="inpost" <?php if ($argon_outdated_info_tip_type=='inpost'){echo 'selected';} ?>><?php _e('在文章顶部显示信息条', 'argon');?></option>
			<option value="toast" <?php if ($argon_outdated_info_tip_type=='toast'){echo 'selected';} ?>><?php _e('在页面右上角弹出提示条', 'argon');?></option>
		</select>
		<?php _e('的方式提示', 'argon');?>
		<br/>
		<textarea type="text" name="argon_outdated_info_tip_content" rows="3" cols="100" style="margin-top: 15px;"><?php echo esc_textarea(get_option('argon_outdated_info_tip_content') == '' ? __('本文最后更新于 %date_delta% 天前，其中的信息可能已经有所发展或是发生改变。', 'argpm') : get_option('argon_outdated_info_tip_content')); ?></textarea>
		<p class="description"><?php _e('天数为 -1 表示永不提示。', 'argon');?><br/><code>%date_delta%</code> <?php _e('表示文章发布/修改时间与当前时间的差距，', 'argon');?><code>%post_date_delta%</code> <?php _e('表示文章发布时间与当前时间的差距，', 'argon');?><code>%modify_date_delta%</code> <?php _e('表示文章修改时间与当前时间的差距（单位: 天）。', 'argon');?></p>
	</td>
</tr>


<tr><th class="subtitle" colspan="2"><h3 id="section-shuoshuo">说说展示</h3></th></tr>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-search"><span id="subsection-search"></span><?php _e('搜索', 'argon');?></h3></th></tr>
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
	jQuery("select[name='argon_enable_search_filters']").change(function(){
		if (jQuery(this).val() == 'true') {
			jQuery(".argon-search-filters-type").css('display', '');
		} else {
			jQuery(".argon-search-filters-type").css('display', 'none');
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
		<input type="text" class="regular-text" name="argon_search_filters_type" value="<?php echo esc_attr(get_option('argon_search_filters_type', '*post,*page,shuoshuo')); ?>" style="display: none;"/>
		<?php _e('拖动来自定义启用的过滤器，单击来切换默认勾选状态', 'argon');?>
		<div class="search-filters-container">
			<?php _e('启用', 'argon');?>
			<div id="search_filters_active" data-argon-dynamic-container></div>
		</div>
		<div class="search-filters-container">
			<?php _e('不启用', 'argon');?>
			<div id="search_filters_inactive" data-argon-dynamic-container>
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
			jQuery("#search_filters_active .search-filter-item").each(function(index, item) {
				if (index != 0){ searchFilters += ",";}
				if (jQuery(item).hasClass('active')){ searchFilters += "*"; }
				searchFilters += item.getAttribute("filter-name");
			});
			jQuery("input[name='argon_search_filters_type']").val(searchFilters).trigger('change');
		}
		!function(){
			let searchFilters = jQuery("input[name='argon_search_filters_type']").val().split(",");
			for (let filter of searchFilters){
				if (filter[0] == "*"){
					jQuery(".search-filter-item[filter-name='"+ filter.substring(1) +"']").addClass('active');
					filter = filter.substring(1);
				}
				let itemDiv = jQuery("#search_filters_inactive .search-filter-item[filter-name='"+ filter + "']");
				jQuery("#search_filters_active").append(itemDiv.prop("outerHTML"));
				itemDiv.remove();
			}
		}();
		jQuery(document).on("click", "#search_filters_active .search-filter-item", function(){
			jQuery(this).toggleClass("active");
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

<tr><th class="subtitle" colspan="2"><h3 id="section-special-pages"><?php _e('特殊页面', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-archive"><?php _e('归档页面', 'argon');?></h4></th></tr>
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

<tr>
	<th><label><?php _e('归档页面链接', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_archives_timeline_url" value="<?php echo esc_attr(get_option('argon_archives_timeline_url')); ?>"/>
		<p class="description"><?php _e('归档页面的 URL。点击左侧栏 "博客概览" 中的 "博文总数" 一栏时可跳转到该地址。', 'argon');?></p>
	</td>
</tr>
