<tr><th class="subtitle" colspan="2"><h3 id="section-email-template"><span id="subsection-email-template"></span><?php _e('邮件模板', 'argon');?></h3></th></tr>
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
					jQuery('[name=argon_email_logo_url]').trigger('change');
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
<tr><th class="subtitle" colspan="2"><h3 id="section-email-templates-custom"><span id="subsection-email-templates-custom"></span><?php _e('邮件内容模板', 'argon');?></h3></th></tr>
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
					textarea.dispatchEvent(new Event('input', {bubbles: true}));
				}
			}
			function argonResetEmailTemplate(type) {
				if (confirm('<?php _e('确定要恢复此邮件类型的默认模板吗？', 'argon');?>')) {
					var defaults = argonEmailDefaults[type];
					document.querySelector('input[name="argon_email_template_' + type + '_subject"]').value = defaults.subject;
					document.querySelector('textarea[name="argon_email_template_' + type + '_content"]').value = defaults.content;
					jQuery('[name="argon_email_template_' + type + '_subject"], [name="argon_email_template_' + type + '_content"]').trigger('change');
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
