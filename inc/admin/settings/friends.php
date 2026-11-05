<tr><th class="subtitle" colspan="2"><h3 id="section-friend-links"><span id="subsection-friend-links"></span><?php _e('友情链接', 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e('页面标语', 'argon');?></label></th>
	<td>
		<input type="text" name="argon_friend_link_slogan" class="regular-text" value="<?php echo esc_attr(get_option('argon_friend_link_slogan', '海内存知己，天涯若比邻')); ?>">
		<p class="description"><?php _e('显示在友链页面标题下方的标语', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('欢迎文本', 'argon');?></label></th>
	<td>
		<textarea name="argon_friend_link_welcome" rows="3" cols="60" class="large-text"><?php echo esc_textarea(get_option('argon_friend_link_welcome', '欢迎与我交换友链！如果您的网站已添加本站链接，填写友链页面地址可自动通过审核。')); ?></textarea>
		<p class="description"><?php _e('显示在申请表单上方的欢迎文字', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('允许访客申请友链', 'argon');?></label></th>
	<td>
		<select name="argon_friend_link_allow_apply">
			<?php $allow_apply = get_option('argon_friend_link_allow_apply', 'true'); ?>
			<option value="true" <?php if ($allow_apply=='true'){echo 'selected';} ?>><?php _e('允许', 'argon');?></option>
			<option value="false" <?php if ($allow_apply=='false'){echo 'selected';} ?>><?php _e('不允许', 'argon');?></option>
		</select>
		<p class="description"><?php _e('开启后，友链页面会显示申请表单', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('申请要求说明', 'argon');?></label></th>
	<td>
		<textarea name="argon_friend_link_requirements" rows="4" cols="60" class="large-text"><?php echo esc_textarea(get_option('argon_friend_link_requirements', '')); ?></textarea>
		<p class="description"><?php _e('显示在申请表单上方的说明文字，支持 HTML', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('未上友链时隐藏', 'argon');?></label></th>
	<td>
		<select name="argon_friend_link_hide_no_backlink">
			<?php $hide_no_backlink = get_option('argon_friend_link_hide_no_backlink', 'true'); ?>
			<option value="true" <?php if ($hide_no_backlink=='true'){echo 'selected';} ?>><?php _e('隐藏', 'argon');?></option>
			<option value="false" <?php if ($hide_no_backlink=='false'){echo 'selected';} ?>><?php _e('仍然显示', 'argon');?></option>
		</select>
		<p class="description"><?php _e('检测到对方未上本站友链或网站失效时，是否在公开页面隐藏（管理员始终可见并有标记）', 'argon');?></p>
	</td>
</tr>
<?php
$friend_link_worker_enabled = function_exists('argon_friend_link_worker_enabled')
	? argon_friend_link_worker_enabled()
	: false;
$friend_link_worker_configured = function_exists('argon_friend_link_worker_is_configured')
	? argon_friend_link_worker_is_configured()
	: false;
$friend_link_worker_url = function_exists('argon_friend_link_worker_url')
	? argon_friend_link_worker_url()
	: get_option('argon_friend_link_worker_url', '');
$friend_link_worker_sources = array(
	'submit' => function_exists('argon_friend_link_worker_config_source')
		? argon_friend_link_worker_config_source('ARGON_FRIEND_LINK_SUBMIT_HMAC_SECRET')
		: 'default',
	'callback' => function_exists('argon_friend_link_worker_config_source')
		? argon_friend_link_worker_config_source('ARGON_FRIEND_LINK_CALLBACK_HMAC_SECRET')
		: 'default',
);
$friend_link_worker_source_labels = array(
	'constant' => __('wp-config.php 常量', 'argon'),
	'environment' => __('环境变量', 'argon'),
	'option' => __('后台数据库', 'argon'),
	'default' => __('未配置', 'argon'),
);
?>
<tr>
	<th><label><?php _e('自动友链检测 Worker', 'argon');?></label></th>
	<td>
		<select name="argon_friend_link_worker_enabled">
			<option value="true" <?php selected($friend_link_worker_enabled, true); ?>><?php _e('启用', 'argon');?></option>
			<option value="false" <?php selected($friend_link_worker_enabled, false); ?>><?php _e('停用', 'argon');?></option>
		</select>
		<p class="description">
			<?php
			if ($friend_link_worker_configured) {
				_e('配置完整，友链申请和管理员批量复检会通过 Worker 异步处理。', 'argon');
			} elseif ($friend_link_worker_enabled) {
				_e('已启用但配置不完整，不会自动派发检测任务。', 'argon');
			} else {
				_e('当前停用，不会自动派发检测任务。', 'argon');
			}
			?>
		</p>
	</td>
</tr>
<tr>
	<th><label><?php _e('Worker 基础 URL', 'argon');?></label></th>
	<td>
		<input type="url" name="argon_friend_link_worker_url" class="regular-text" value="<?php echo esc_attr($friend_link_worker_url); ?>" placeholder="https://your-worker.example.workers.dev">
		<button type="button" class="button" id="argon-test-friend-link-worker"><?php _e('测试连通性', 'argon');?></button>
		<span id="argon-friend-link-worker-test-status" class="argon-inline-test-status" role="status" aria-live="polite"></span>
		<p class="description"><?php _e('填写 Worker 的 HTTPS 地址，不含 /v1/jobs。wp-config.php 常量和环境变量优先于后台值。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('提交 HMAC 密钥', 'argon');?></label></th>
	<td>
		<input type="password" name="argon_friend_link_submit_hmac_secret" class="regular-text" autocomplete="new-password" data-argon-sensitive placeholder="<?php esc_attr_e('留空保持现有值', 'argon'); ?>">
		<p class="description">
			<?php printf(__('当前来源：%s。必须与 Worker 的 SUBMIT_HMAC_SECRET 完全一致。', 'argon'), esc_html($friend_link_worker_source_labels[$friend_link_worker_sources['submit']])); ?>
		</p>
	</td>
</tr>
<tr>
	<th><label><?php _e('回调 HMAC 密钥', 'argon');?></label></th>
	<td>
		<input type="password" name="argon_friend_link_callback_hmac_secret" class="regular-text" autocomplete="new-password" data-argon-sensitive placeholder="<?php esc_attr_e('留空保持现有值', 'argon'); ?>">
		<p class="description">
			<?php printf(__('当前来源：%s。必须与 Worker 的 CALLBACK_HMAC_SECRET 完全一致。', 'argon'), esc_html($friend_link_worker_source_labels[$friend_link_worker_sources['callback']])); ?>
		</p>
	</td>
</tr>
<tr>
	<th><label><?php _e('清除后台密钥', 'argon');?></label></th>
	<td>
		<label><input type="checkbox" name="argon_friend_link_worker_clear_secrets" value="true" data-argon-sensitive> <?php _e('保存时删除数据库中的两个 HMAC 密钥', 'argon');?></label>
		<p class="description"><?php _e('不会删除 wp-config.php 常量、环境变量或 Cloudflare Worker Secret。ADMIN_READ_TOKEN 只在 Cloudflare 中配置。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('友链管理', 'argon');?></label></th>
	<td>
		<p class="description"><?php _e('友链管理已移至友情链接页面，管理员登录后访问友链页面即可管理。', 'argon');?></p>
		<a href="<?php echo home_url('/friends/'); ?>" class="button" target="_blank"><?php _e('前往友链页面', 'argon');?></a>
	</td>
</tr>
