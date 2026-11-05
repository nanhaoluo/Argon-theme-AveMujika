<tr><th class="subtitle" colspan="2"><h3 id="section-comment"><?php _e('评论设置', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-comment-pagination"><?php _e('评论分页', 'argon');?></h4></th></tr>
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
				echo '<script>jQuery(".go-to-wp-comment-settings").addClass("wrong-options");</script>';
			};?>
			<script>
				jQuery("select[name='argon_comment_pagination_type']").change(function(){
					if (jQuery(this).val() == 'feed') {
						jQuery(".go-to-wp-comment-settings").addClass("using-feed");
					} else {
						jQuery(".go-to-wp-comment-settings").removeClass("using-feed");
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
<tr><th class="subtitle" colspan="2"><h4 id="subsection-comment-submit"><?php _e('发送评论', 'argon');?></h4></th></tr>
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

<tr><th class="subtitle" colspan="2"><h3 id="section-comment-features"><span id="subsection-comment-features"></span><?php _e('评论功能', 'argon');?></h3></th></tr>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-comment-appearance"><span id="subsection-comment-appearance"></span><?php _e('评论区外观', 'argon');?></h3></th></tr>
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
		<input type="text" class="regular-text" name="argon_gravatar_cdn" value="<?php echo esc_attr(get_option('argon_gravatar_cdn' , '')); ?>"/>
		<p class="description"><?php _e('使用 CDN 来加速 Gravatar 在某些地区的访问，填写 CDN 地址，留空则不使用。', 'argon');?><br/><?php _e('在中国速度较快的一些 CDN :', 'argon');?><code onclick="jQuery('input[name=\'argon_gravatar_cdn\']').val(this.innerText);" style="cursor: pointer;">gravatar.pho.ink/avatar/</code> , <code onclick="jQuery('input[name=\'argon_gravatar_cdn\']').val(this.innerText);" style="cursor: pointer;">cdn.v2ex.com/gravatar/</code> , <code onclick="jQuery('input[name=\'argon_gravatar_cdn\']').val(this.innerText);" style="cursor: pointer;">dn-qiniu-avatar.qbox.me/avatar/</code></p>
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
<tr><th class="subtitle" colspan="2"><h3 id="section-feedback"><span id="subsection-feedback"></span><?php _e('反馈设置', 'argon');?></h3></th></tr>
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
