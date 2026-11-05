<tr><th class="subtitle" colspan="2"><h3 id="section-theme-maintenance">更新与缓存</h3></th></tr>
<tr>
	<th><label><?php _e('检测更新源', 'argon');?></label></th>
	<td>
		<select name="argon_update_source">
			<?php $argon_update_source = get_option('argon_update_source'); ?>
			<option value="github" <?php if ($argon_update_source=='github'){echo 'selected';} ?>>Github</option>
			<option value="fastgit" <?php if ($argon_update_source=='fastgit'){echo 'selected';} ?>>Fastgit</option>
			<option value="cfworker" <?php if ($argon_update_source=='cfworker'){echo 'selected';} ?>>CF Worker</option>
			<option value="solstice23top" <?php if ($argon_update_source=='solstice23top'){echo 'selected';} ?>>solstice23.top</option>
			<option value="stop" <?php if ($argon_update_source=='stop'){echo 'selected';} ?>><?php _e('暂停更新 (不推荐)', 'argon');?></option>
		</select>
		<p class="description"><?php _e('如更新主题速度较慢，可考虑更换更新源。', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('热更新', 'argon');?></label></th>
	<td>
		<?php $argon_enable_hot_reload = get_option('argon_enable_hot_reload', 'false'); ?>
		<label>
			<input type="checkbox" name="argon_enable_hot_reload" value="true" <?php if ($argon_enable_hot_reload=='true'){echo 'checked';}?>/> 
			<?php _e('启用热更新功能', 'argon');?>
		</label>
		<p class="description"><?php _e('启用后，当主题文件被更新时，将自动清理所有缓存并显示更新通知。', 'argon');?></p>
		
		<?php $argon_hot_reload_auto_refresh = get_option('argon_hot_reload_auto_refresh', 'false'); ?>
		<label style="display:block;margin-top:10px;">
			<input type="checkbox" name="argon_hot_reload_auto_refresh" value="true" <?php if ($argon_hot_reload_auto_refresh=='true'){echo 'checked';}?>/> 
			<?php _e('自动刷新页面（检测到版本更新时自动刷新用户浏览器）', 'argon');?>
		</label>
		<p class="description" style="margin-left:24px;"><?php _e('推荐开启。用户访问时如检测到主题版本变化，将自动刷新页面以加载最新功能。', 'argon');?></p>
		
		<?php $argon_hot_reload_frontend_notice = get_option('argon_hot_reload_frontend_notice', 'false'); ?>
		<label style="display:block;margin-top:10px;">
			<input type="checkbox" name="argon_hot_reload_frontend_notice" value="true" <?php if ($argon_hot_reload_frontend_notice=='true'){echo 'checked';}?>/> 
			<?php _e('在前台显示更新通知（仅管理员可见）', 'argon');?>
		</label>
		
		<div style="margin-top:15px;">
			<button type="button" class="button" id="argon_clear_cache_btn"><?php _e('手动清理缓存', 'argon');?></button>
			<span id="argon_clear_cache_result" style="margin-left:10px;color:#46b450;display:none;"><?php _e('缓存已清理', 'argon');?></span>
		</div>
		<script>
		jQuery(document).ready(function($) {
			jQuery('#argon_clear_cache_btn').on('click', function() {
				var btn = jQuery(this);
				btn.prop('disabled', true).text('<?php _e('清理中...', 'argon');?>');
				jQuery.post(ajaxurl, {
					action: 'argon_clear_cache',
					nonce: '<?php echo wp_create_nonce('argon_clear_cache'); ?>'
				}, function(response) {
					btn.prop('disabled', false).text('<?php _e('手动清理缓存', 'argon');?>');
					if (response.success) {
						jQuery('#argon_clear_cache_result').fadeIn().delay(2000).fadeOut();
					} else {
						alert(response.data || '<?php _e('清理失败', 'argon');?>');
					}
				});
			});
		});
		</script>
	</td>
</tr>
<tr>
	<th><label><?php _e('强制刷新缓存', 'argon');?></label></th>
	<td>
		<?php 
		$force_refresh_enabled = function_exists('argon_is_force_refresh_enabled') ? argon_is_force_refresh_enabled() : false;
		$enabled_time = get_option('argon_force_refresh_enabled_time', 0);
		$remaining = 0;
		if ($force_refresh_enabled && $enabled_time > 0) {
			$remaining = max(0, 3600 - (time() - $enabled_time));
		}
		?>
		<div id="argon_force_refresh_container">
			<div id="argon_force_refresh_status" style="margin-bottom:15px;padding:12px 15px;border-radius:6px;<?php echo $force_refresh_enabled ? 'background:#d4edda;border:1px solid #c3e6cb;' : 'background:#f8f9fa;border:1px solid #e9ecef;'; ?>">
				<span id="argon_force_refresh_status_text">
					<?php if ($force_refresh_enabled): ?>
						<span style="color:#155724;">✓ <?php _e('强制刷新已启用', 'argon'); ?></span>
						<span id="argon_force_refresh_countdown" style="margin-left:10px;color:#666;"><?php echo sprintf(__('剩余 %s', 'argon'), '<span id="argon_countdown_time">' . floor($remaining / 60) . ':' . str_pad($remaining % 60, 2, '0', STR_PAD_LEFT) . '</span>'); ?></span>
					<?php else: ?>
						<span style="color:#666;"><?php _e('强制刷新未启用', 'argon'); ?></span>
					<?php endif; ?>
				</span>
			</div>
			<button type="button" class="button <?php echo $force_refresh_enabled ? 'button-secondary' : 'button-primary'; ?>" id="argon_force_refresh_btn">
				<?php echo $force_refresh_enabled ? __('关闭强制刷新', 'argon') : __('启用强制刷新 (1小时)', 'argon'); ?>
			</button>
		</div>
		<p class="description" style="margin-top:10px;">
			<?php _e('启用后，所有访客在刷新页面时将强制重新获取所有资源文件（CSS、JS等），解决手机端缓存导致的样式或功能异常问题。', 'argon'); ?><br/>
			<?php _e('该功能会在 1 小时后自动关闭，以避免对服务器造成持续压力。', 'argon'); ?>
		</p>
		<script>
		jQuery(document).ready(function($) {
			var isEnabled = <?php echo $force_refresh_enabled ? 'true' : 'false'; ?>;
			var remainingSeconds = <?php echo $remaining; ?>;
			var countdownTimer = null;
			
			function updateCountdown() {
				if (remainingSeconds <= 0) {
					clearInterval(countdownTimer);
					isEnabled = false;
					updateUI();
					return;
				}
				remainingSeconds--;
				var mins = Math.floor(remainingSeconds / 60);
				var secs = remainingSeconds % 60;
				jQuery('#argon_countdown_time').text(mins + ':' + (secs < 10 ? '0' : '') + secs);
			}
			
			function updateUI() {
				var statusDiv = jQuery('#argon_force_refresh_status');
				var statusText = jQuery('#argon_force_refresh_status_text');
				var btn = jQuery('#argon_force_refresh_btn');
				
				if (isEnabled) {
					statusDiv.css({'background': '#d4edda', 'border': '1px solid #c3e6cb'});
					var mins = Math.floor(remainingSeconds / 60);
					var secs = remainingSeconds % 60;
					statusText.html('<span style="color:#155724;">✓ <?php _e('强制刷新已启用', 'argon'); ?></span><span id="argon_force_refresh_countdown" style="margin-left:10px;color:#666;"><?php _e('剩余', 'argon'); ?> <span id="argon_countdown_time">' + mins + ':' + (secs < 10 ? '0' : '') + secs + '</span></span>');
					btn.removeClass('button-primary').addClass('button-secondary').text('<?php _e('关闭强制刷新', 'argon'); ?>');
					if (!countdownTimer) {
						countdownTimer = setInterval(updateCountdown, 1000);
					}
				} else {
					statusDiv.css({'background': '#f8f9fa', 'border': '1px solid #e9ecef'});
					statusText.html('<span style="color:#666;"><?php _e('强制刷新未启用', 'argon'); ?></span>');
					btn.removeClass('button-secondary').addClass('button-primary').text('<?php _e('启用强制刷新 (1小时)', 'argon'); ?>');
					if (countdownTimer) {
						clearInterval(countdownTimer);
						countdownTimer = null;
					}
				}
			}
			
			// 初始化倒计时
			if (isEnabled && remainingSeconds > 0) {
				countdownTimer = setInterval(updateCountdown, 1000);
			}
			
			jQuery('#argon_force_refresh_btn').on('click', function() {
				var btn = jQuery(this);
				btn.prop('disabled', true);
				
				var action = isEnabled ? 'argon_disable_force_refresh' : 'argon_enable_force_refresh';
				
				jQuery.post(ajaxurl, {
					action: action,
					nonce: '<?php echo wp_create_nonce('argon_force_refresh'); ?>'
				}, function(response) {
					btn.prop('disabled', false);
					if (response.success) {
						isEnabled = !isEnabled;
						if (isEnabled) {
							remainingSeconds = 3600;
						} else {
							remainingSeconds = 0;
						}
						updateUI();
					} else {
						alert(response.data || '<?php _e('操作失败', 'argon'); ?>');
					}
				});
			});
		});
		</script>
	</td>
</tr>
<tr>
	<th><label><?php _e('调试控制台', 'argon');?></label></th>
	<td>
		<?php $argon_enable_debug_console = get_option('argon_enable_debug_console', 'false'); ?>
		<label>
			<input type="checkbox" name="argon_enable_debug_console" value="true" <?php if ($argon_enable_debug_console=='true'){echo 'checked';}?>/> 
			<?php _e('启用前端调试控制台', 'argon');?>
		</label>
		<p class="description"><?php _e('启用后，管理员可在前台看到调试按钮，点击打开控制台查看错误信息。普通用户遇到错误时会显示红色通知提示联系管理员。', 'argon');?></p>
		
		<?php $muted_errors = get_option('argon_muted_errors', array()); ?>
		<?php if (!empty($muted_errors)): ?>
		<div style="margin-top:15px;padding:15px;background:#f8f9fa;border-radius:6px;">
			<strong style="display:block;margin-bottom:10px;"><?php _e('已屏蔽的错误', 'argon'); ?> (<?php echo count($muted_errors); ?>)</strong>
			<div style="max-height:200px;overflow-y:auto;">
			<?php foreach ($muted_errors as $hash => $error): ?>
				<div style="padding:8px 10px;margin-bottom:6px;background:#fff;border-radius:4px;border-left:3px solid #f5365c;font-size:12px;position:relative;">
					<div style="color:#333;word-break:break-all;"><?php echo esc_html(mb_substr($error['message'], 0, 100)); ?><?php echo mb_strlen($error['message']) > 100 ? '...' : ''; ?></div>
					<div style="color:#888;font-size:11px;margin-top:4px;">
						<?php echo sprintf(__('由 %s 于 %s 屏蔽', 'argon'), esc_html($error['muted_by']), date('Y-m-d H:i', $error['muted_at'])); ?>
					</div>
					<button type="button" class="button button-small" style="position:absolute;top:8px;right:8px;" onclick="argonUnmuteError('<?php echo esc_attr($hash); ?>', this)"><?php _e('取消屏蔽', 'argon'); ?></button>
				</div>
			<?php endforeach; ?>
			</div>
			<button type="button" class="button" style="margin-top:10px;" id="argon_clear_muted_errors_btn"><?php _e('清空所有屏蔽', 'argon'); ?></button>
		</div>
		<script>
		function argonUnmuteError(hash, btn) {
			if (!confirm('<?php _e('确定取消屏蔽此错误？', 'argon'); ?>')) return;
			jQuery.post(ajaxurl, {
				action: 'argon_unmute_error',
				nonce: '<?php echo wp_create_nonce('argon_debug_console'); ?>',
				error_hash: hash
			}, function(response) {
				if (response.success) {
					jQuery(btn).closest('div[style*="border-left"]').fadeOut(300, function() { jQuery(this).remove(); });
				}
			});
		}
		jQuery('#argon_clear_muted_errors_btn').on('click', function() {
			if (!confirm('<?php _e('确定清空所有已屏蔽的错误？', 'argon'); ?>')) return;
			var btn = jQuery(this);
			btn.prop('disabled', true);
			jQuery.post(ajaxurl, {
				action: 'argon_clear_muted_errors',
				nonce: '<?php echo wp_create_nonce('argon_debug_console'); ?>'
			}, function(response) {
				if (response.success) {
					btn.closest('div[style*="background:#f8f9fa"]').fadeOut(300);
				}
			});
		});
		</script>
		<?php endif; ?>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h3 id="section-wp-path"><?php _e('WordPress 安装目录', 'argon');?></h3></th></tr>
<tr>
	<th><label><?php _e('WordPress 安装目录', 'argon');?></label></th>
	<td>
		<input type="text" class="regular-text" name="argon_wp_path" value="<?php echo esc_attr(get_option('argon_wp_path', '/')); ?>"/>
		<p class="description"><?php _e('如果 WordPress 安装在子目录中，请在此填写子目录地址（例如', 'argon');?> <code>/blog/</code><?php _e('），注意前后各有一个斜杠。默认为', 'argon');?> <code>/</code> <?php _e('。', 'argon');?><br/><?php _e('如果不清楚该选项的用处，请保持默认。', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h3 id="section-timezone-compatibility">时区兼容</h3></th></tr>
<tr>
	<th><label><?php _e('是否修正时区错误', 'argon');?></label></th>
	<td>
		<select name="argon_enable_timezone_fix">
			<?php $argon_enable_timezone_fix = get_option('argon_enable_timezone_fix'); ?>
			<option value="false" <?php if ($argon_enable_timezone_fix=='false'){echo 'selected';} ?>><?php _e('关闭', 'argon');?></option>
			<option value="true" <?php if ($argon_enable_timezone_fix=='true'){echo 'selected';} ?>><?php _e('开启', 'argon');?></option>
		</select>
		<p class="description"><?php _e('如遇到时区错误（例如一条刚发的评论显示 8 小时前），这个选项', 'argon');?><strong><?php _e('可能', 'argon');?></strong><?php _e('可以修复这个问题', 'argon');?></p>
	</td>
</tr>
<tr><th class="subtitle" colspan="2"><h3 id="section-advanced"><?php _e('高级设置', 'argon');?></h3></th></tr>
<tr><th class="subtitle" colspan="2"><h4 id="subsection-scripts"><?php _e('自定义脚本', 'argon');?></h4></th></tr>
<tr>
	<th><label><strong style="color:#ff0000;"><?php _e('注意', 'argon');?></strong></label></th>
	<td>
		<p class="description"><strong style="color:#ff0000;"><?php _e('Argon 使用 pjax 方式加载页面 (无刷新加载) , 所以除非页面手动刷新，否则您的脚本只会被执行一次。', 'argon');?><br/>
		<?php _e('如果您想让每次页面跳转(加载新页面)时都执行脚本，请将脚本写入', 'argon');?> <code>window.pjaxLoaded</code> <?php _e('中', 'argon');?></strong> ，<?php _e('示例写法', 'argon');?>:
		<pre>
window.pjaxLoaded = function(){
	//<?php _e('页面每次跳转都会执行这里的代码', 'argon');?>
	//do something...
}
		</pre>
		<strong style="color:#ff0000;"><?php _e('当页面第一次载入时，', 'argon');?><code>window.pjaxLoaded</code> <?php _e('中的脚本不会执行，所以您可以手动执行', 'argon');?> <code>window.pjaxLoaded();</code> <?php _e('来让页面初次加载时也执行脚本', 'argon');?></strong></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('页头脚本', 'argon');?></label></th>
	<td>
		<textarea type="text" rows="15" cols="100" name="argon_custom_html_head"><?php echo htmlspecialchars(get_option('argon_custom_html_head')); ?></textarea>
		<p class="description"><?php _e('HTML , 支持 script 等标签', 'argon');?><br/><?php _e('插入到 body 之前', 'argon');?></p>
	</td>
</tr>
<tr>
	<th><label><?php _e('页尾脚本', 'argon');?></label></th>
	<td>
		<textarea type="text" rows="15" cols="100" name="argon_custom_html_foot"><?php echo htmlspecialchars(get_option('argon_custom_html_foot')); ?></textarea>
		<p class="description"><?php _e('HTML , 支持 script 等标签', 'argon');?><br/><?php _e('插入到 body 之后', 'argon');?></p>
	</td>
</tr>
