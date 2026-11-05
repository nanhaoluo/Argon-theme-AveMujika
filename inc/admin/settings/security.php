<tr><th class="subtitle" colspan="2"><h3 id="section-captcha"><?php _e('验证码设置', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-captcha"><?php _e('验证码配置', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('全局验证码开关', 'argon');?></label></th>
	<td>
		<select name="argon_need_captcha">
			<?php $argon_need_captcha = get_option('argon_need_captcha', get_option('argon_comment_need_captcha', 'true')); ?>
			<option value="true" <?php if ($argon_need_captcha=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
			<option value="false" <?php if ($argon_need_captcha=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		</select>
		<p class="description"><?php _e('全局验证码开关，当各场景设置为"使用全局设置"时生效', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('验证码类型', 'argon');?></label></th>
	<td>
		<select name="argon_captcha_type">
			<?php $argon_captcha_type = get_option('argon_captcha_type', 'math'); ?>
			<option value="math" <?php if ($argon_captcha_type=='math'){echo 'selected';} ?>><?php _e('数学运算', 'argon');?></option>
			<option value="geetest" <?php if ($argon_captcha_type=='geetest'){echo 'selected';} ?>><?php _e('极验验证码', 'argon');?></option>
		</select>
		<p class="description"><?php _e('选择验证码类型。极验验证码需要配置相关参数。', 'argon');?></p>
	</td>
</tr>
<tr id="geetest_settings" style="<?php echo ($argon_captcha_type != 'geetest') ? 'display:none;' : ''; ?>">
	<th><label><?php _e('极验验证码 ID', 'argon');?></label></th>
	<td>
		<input type="text" name="argon_geetest_captcha_id" value="<?php echo esc_attr(get_option('argon_geetest_captcha_id', '')); ?>" class="regular-text" required />
		<p class="description"><?php _e('在极验后台获取的验证码 ID，必填项', 'argon');?></p>
	</td>
</tr>
<tr id="geetest_key_settings" style="<?php echo ($argon_captcha_type != 'geetest') ? 'display:none;' : ''; ?>">
	<th><label><?php _e('极验验证码 Key', 'argon');?></label></th>
	<td>
		<input type="text" name="argon_geetest_captcha_key" value="<?php echo esc_attr(get_option('argon_geetest_captcha_key', '')); ?>" class="regular-text" required />
		<p class="description"><?php _e('在极验后台获取的验证码私钥，必填项', 'argon');?></p>
	</td>
</tr>
<tr id="geetest_server_settings" style="<?php echo ($argon_captcha_type != 'geetest') ? 'display:none;' : ''; ?>">
	<th><label><?php _e('极验 API 服务器', 'argon');?></label></th>
	<td>
		<input type="url" name="argon_geetest_api_server" value="<?php echo esc_attr(get_option('argon_geetest_api_server', 'https://gcaptcha4.geetest.com')); ?>" class="regular-text" />
		<p class="description"><?php _e('极验 API 服务器地址，默认为官方服务器。请确保URL格式正确', 'argon');?></p>
	</td>
</tr>
<script>
	jQuery(document).ready(function($) {
		// 验证Geetest配置
		function validateGeetestConfig() {
			var captchaType = jQuery('select[name="argon_captcha_type"]').val();
			if (captchaType === 'geetest') {
				var captchaId = jQuery('input[name="argon_geetest_captcha_id"]').val().trim();
				var captchaKey = jQuery('input[name="argon_geetest_captcha_key"]').val().trim();
				var apiServer = jQuery('input[name="argon_geetest_api_server"]').val().trim();
				
				if (!captchaId || !captchaKey) {
					alert('<?php _e('使用极验验证码时，验证码 ID 和 Key 为必填项', 'argon'); ?>');
					return false;
				}
				
				// 验证API服务器URL格式
				if (apiServer && !apiServer.match(/^https?:\/\/.+/)) {
					alert('<?php _e('API 服务器地址格式不正确，请输入完整的URL', 'argon'); ?>');
					return false;
				}
			}
			return true;
		}
		
		jQuery('select[name="argon_captcha_type"]').change(function() {
			if (jQuery(this).val() === 'geetest') {
				jQuery('#geetest_settings, #geetest_key_settings, #geetest_server_settings').show();
			} else {
				jQuery('#geetest_settings, #geetest_key_settings, #geetest_server_settings').hide();
			}
		});
		
		// 表单提交时验证
		jQuery('form').submit(function(e) {
			if (!validateGeetestConfig()) {
				e.preventDefault();
				return false;
			}
		});
	});
</script>
<tr>
	<th><label><?php _e('使用 Ajax 获取验证码', 'argon');?></label></th>
	<td>
		<select name="argon_get_captcha_by_ajax">
		<?php $argon_get_captcha_by_ajax = get_option('argon_get_captcha_by_ajax'); ?>
		<option value="false" <?php if ($argon_get_captcha_by_ajax=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		<option value="true" <?php if ($argon_get_captcha_by_ajax=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
	</select>
	<p class="description"><?php _e('如果使用了 CDN 缓存，验证码不会刷新，请开启此选项，否则请不要开启。', 'argon');?></p>
</td>
				</tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-captcha-scenes"><?php _e('场景验证码', 'argon');?></h4></th></tr>
<tr>
	<th><label><?php _e('评论验证码', 'argon');?></label></th>
	<td>
		<select name="argon_comment_captcha_mode">
			<?php $argon_comment_captcha_mode = get_option('argon_comment_captcha_mode', 'global'); ?>
			<option value="global" <?php if ($argon_comment_captcha_mode=='global'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>
			<option value="enabled" <?php if ($argon_comment_captcha_mode=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
			<option value="disabled" <?php if ($argon_comment_captcha_mode=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		</select>
		<p class="description"><?php _e('评论是否需要验证码', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('TODO 催促验证码', 'argon');?></label></th>
	<td>
		<select name="argon_todo_captcha_mode">
			<?php $argon_todo_captcha_mode = get_option('argon_todo_captcha_mode', 'global'); ?>
			<option value="global" <?php if ($argon_todo_captcha_mode=='global'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>
			<option value="enabled" <?php if ($argon_todo_captcha_mode=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
			<option value="disabled" <?php if ($argon_todo_captcha_mode=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		</select>
		<p class="description"><?php _e('访客催促 TODO 时是否需要验证码', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('友链申请验证码', 'argon');?></label></th>
	<td>
		<select name="argon_friend_link_captcha">
			<?php $flink_captcha = get_option('argon_friend_link_captcha', 'global'); ?>
			<option value="global" <?php if ($flink_captcha=='global' || $flink_captcha=='same'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>
			<option value="enabled" <?php if ($flink_captcha=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
			<option value="disabled" <?php if ($flink_captcha=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		</select>
		<p class="description"><?php _e('友链申请表单是否需要验证码', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('反馈提交验证码', 'argon');?></label></th>
	<td>
		<select name="argon_feedback_captcha_mode">
			<?php $feedback_captcha = get_option('argon_feedback_captcha_mode', 'global'); ?>
			<option value="global" <?php if ($feedback_captcha=='global'){echo 'selected';} ?>><?php _e('使用全局设置', 'argon');?></option>
			<option value="enabled" <?php if ($feedback_captcha=='enabled'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
			<option value="disabled" <?php if ($feedback_captcha=='disabled'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		</select>
		<p class="description"><?php _e('问题反馈页面提交时是否需要验证码', 'argon');?></p>
	</td>
</tr>


<tr><th class="subtitle" colspan="2"><h3 id="section-rate-limit"><span id="subsection-rate-limit"></span><?php _e('速率限制', 'argon');?></h3></th></tr>
				<tr>
<th><label><?php _e('全局 IP 黑名单', 'argon');?></label></th>
<td>
	<textarea name="argon_global_blocked_ips" rows="8" cols="70" placeholder="<?php _e('每行一个 IP 地址', 'argon');?>"><?php echo esc_textarea(get_option('argon_global_blocked_ips', '')); ?></textarea>
	<p class="description">
		<?php _e('被屏蔽的 IP 地址将无法提交评论、反馈、友链申请等操作。支持以下格式：', 'argon');?><br>
		• <?php _e('精确匹配：192.168.1.100', 'argon');?><br>
		• <?php _e('通配符：192.168.1.*', 'argon');?><br>
		• <?php _e('CIDR 格式：192.168.1.0/24', 'argon');?>
	</p>
</td>
				</tr>
				<tr>
<th><label><?php _e('友链申请频率限制', 'argon');?></label></th>
<td>
	<input type="number" name="argon_flink_apply_limit" min="1" max="50" value="<?php echo esc_attr(get_option('argon_flink_apply_limit', '3')); ?>" style="width:80px;"/> <?php _e('次', 'argon');?> / 
<input type="number" name="argon_flink_apply_period" min="60" max="86400" value="<?php echo esc_attr(get_option('argon_flink_apply_period', '3600')); ?>" style="width:100px;"/> <?php _e('秒', 'argon');?>
	<p class="description"><?php _e('限制单个用户在指定时间内提交友链申请的次数。默认：1小时内最多3次', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('反馈提交频率限制', 'argon');?></label></th>
<td>
	<input type="number" name="argon_feedback_submit_limit" min="1" max="100" value="<?php echo esc_attr(get_option('argon_feedback_submit_limit', '5')); ?>" style="width:80px;"/> <?php _e('次', 'argon');?> / 
	<input type="number" name="argon_feedback_submit_period" min="60" max="86400" value="<?php echo esc_attr(get_option('argon_feedback_submit_period', '3600')); ?>" style="width:100px;"/> <?php _e('秒', 'argon');?>
	<p class="description"><?php _e('限制单个用户在指定时间内提交反馈的次数，防止恶意刷屏。默认：1小时内最多5次', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('反馈图片上传频率限制', 'argon');?></label></th>
<td>
	<input type="number" name="argon_feedback_upload_limit" min="1" max="100" value="<?php echo esc_attr(get_option('argon_feedback_upload_limit', '20')); ?>" style="width:80px;"/> <?php _e('张', 'argon');?> / 
	<input type="number" name="argon_feedback_upload_period" min="60" max="86400" value="<?php echo esc_attr(get_option('argon_feedback_upload_period', '3600')); ?>" style="width:100px;"/> <?php _e('秒', 'argon');?>
	<p class="description"><?php _e('限制单个用户在指定时间内上传图片的数量。默认：1小时内最多20张', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('反馈图片提交设置', 'argon');?></label></th>
<td>
	<input type="number" name="argon_feedback_image_max_count" min="1" max="10" value="<?php echo esc_attr(get_option('argon_feedback_image_max_count', '10')); ?>" style="width:80px;"/> <?php _e('张 / 次提交', 'argon');?>&nbsp;&nbsp;
	<input type="number" name="argon_feedback_image_max_size_kb" min="80" max="2048" value="<?php echo esc_attr(get_option('argon_feedback_image_max_size_kb', '250')); ?>" style="width:90px;"/> KB <?php _e('/ 张', 'argon');?>&nbsp;&nbsp;
	<input type="number" name="argon_feedback_image_max_dimension" min="640" max="4096" value="<?php echo esc_attr(get_option('argon_feedback_image_max_dimension', '2048')); ?>" style="width:90px;"/> px&nbsp;&nbsp;
<input type="number" name="argon_feedback_image_source_max_mb" min="1" max="32" value="<?php echo esc_attr(get_option('argon_feedback_image_source_max_mb', '12')); ?>" style="width:80px;"/> MB <?php _e('原图', 'argon');?>
	<p class="description"><?php _e('控制提点建议页单次提交的图片数量、压缩后的单张大小、最长边尺寸和原图读取上限。前端会按此配置压缩，后端会再次校验。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('评论速率限制', 'argon');?></label></th>
<td>
	<select name="argon_rate_limit_enable">
		<?php $argon_rate_limit_enable = get_option('argon_rate_limit_enable', 'true'); ?>
		<option value="false" <?php if ($argon_rate_limit_enable=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
		<option value="true" <?php if ($argon_rate_limit_enable=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
	</select>
	<p class="description"><?php _e('按 IP 进行速率限制，防止短时间内频繁评论。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('速率窗口（秒）', 'argon');?></label></th>
<td>
	<input type="number" name="argon_rate_limit_window" value="<?php echo esc_attr(get_option('argon_rate_limit_window', 300)); ?>" class="regular-text" min="30" step="1" />
	<p class="description"><?php _e('统计窗口长度，建议不小于 30 秒。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('窗口内最大次数', 'argon');?></label></th>
<td>
	<input type="number" name="argon_rate_limit_max_count" value="<?php echo esc_attr(get_option('argon_rate_limit_max_count', 5)); ?>" class="regular-text" min="1" step="1" />
	<p class="description"><?php _e('在一个统计窗口内允许的最大评论次数。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('两次最小间隔（秒）', 'argon');?></label></th>
<td>
	<input type="number" name="argon_rate_limit_min_interval" value="<?php echo esc_attr(get_option('argon_rate_limit_min_interval', 10)); ?>" class="regular-text" min="0" step="1" />
	<p class="description"><?php _e('两次评论之间的最短时间间隔。', 'argon');?></p>
</td>
				</tr>
