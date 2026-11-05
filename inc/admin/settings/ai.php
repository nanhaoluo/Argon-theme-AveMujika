<tr><th class="subtitle" colspan="2"><h3 id="section-ai"><?php _e('AI 功能', 'argon');?></h3></th></tr>
				<tr>
<th><label><?php _e('说明', 'argon');?></label></th>
<td>
	<p class="description"><?php _e('统一管理所有 AI 服务商的 API 配置，并配置 AI 文章摘要和评论审核功能', 'argon');?></p>
</td>
				</tr>
				
				<tr><th class="subtitle" colspan="2"><h3 id="ai-api-management"><?php _e('API 管理', 'argon');?></h3></th></tr>
				<tr>
<th><label><?php _e('已配置的 API', 'argon');?></label></th>
<td>
	<?php
	// 获取所有 API
	$all_apis = argon_get_all_apis();
	$summary_active_api = get_option('argon_ai_summary_active_api', '');
	$spam_active_api = get_option('argon_ai_spam_active_api', '');
	$seo_active_api = get_option('argon_ai_seo_active_api', '');
	// 提供商列表
	$providers = [
		'openai_compatible' => __('OpenAI 兼容', 'argon'),
		'anthropic_compatible' => __('Anthropic 兼容', 'argon')
	];
	?>
	<div id="argon-unified-api-list">
		<?php if (!empty($all_apis)): ?>
			<table class="widefat striped argon-ai-api-table">
				<thead>
					<tr>
						<th><?php _e('名称', 'argon'); ?></th>
						<th><?php _e('提供商', 'argon'); ?></th>
						<th><?php _e('模型', 'argon'); ?></th>
						<th><?php _e('密钥', 'argon'); ?></th>
						<th><?php _e('端点', 'argon'); ?></th>
						<th><?php _e('用途', 'argon'); ?></th>
						<th><?php _e('操作', 'argon'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($all_apis as $api): ?>
						<tr data-api-id="<?php echo esc_attr($api['id']); ?>">
							<td><strong><?php echo esc_html($api['name']); ?></strong></td>
							<?php $provider_key = argon_normalize_ai_provider(isset($api['provider']) ? $api['provider'] : 'openai_compatible'); ?>
							<td><?php echo esc_html(isset($providers[$provider_key]) ? $providers[$provider_key] : argon_get_ai_provider_label($provider_key)); ?></td>
							<td><?php echo !empty($api['model']) ? '<code>' . esc_html($api['model']) . '</code>' : ''; ?></td>
							<td><code><?php echo esc_html(substr($api['api_key'], 0, 12)); ?>...</code></td>
							<td><?php echo !empty($api['api_endpoint']) ? '<code>' . esc_html($api['api_endpoint']) . '</code>' : ''; ?></td>
							<td>
								<?php
								$usages = [];
								if ($api['id'] === $summary_active_api) {
									$usages[] = __('文章摘要', 'argon');
								}
								if ($api['id'] === $spam_active_api) {
									$usages[] = __('评论审核', 'argon');
								}
								if ($api['id'] === $seo_active_api) {
									$usages[] = __('SEO 优化', 'argon');
								}
								echo esc_html(implode(' | ', $usages));
								?>
							</td>
							<td>
								<button type="button" class="button button-small argon-test-unified-api" data-api-id="<?php echo esc_attr($api['id']); ?>">
									<?php _e('测试', 'argon'); ?>
								</button>
								<button type="button" class="button button-small argon-edit-unified-api" data-api-id="<?php echo esc_attr($api['id']); ?>">
									<?php _e('编辑', 'argon'); ?>
								</button>
								<button type="button" class="button button-small argon-delete-unified-api" data-api-id="<?php echo esc_attr($api['id']); ?>">
									<?php _e('删除', 'argon'); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p class="description"><?php _e('暂无配置的 API，请点击下方按钮添加', 'argon'); ?></p>
		<?php endif; ?>
		<div class="argon-unified-api-status" id="argon-unified-api-status"></div>
	</div>
	<button type="button" class="button button-primary" id="argon-add-unified-api-btn">
		<?php _e('添加新 API', 'argon'); ?>
	</button>
	<!-- API 配置表单（隐藏） -->
	<div id="argon-unified-api-form" style="display:none;">
		<h4><?php _e('API 配置', 'argon'); ?></h4>
		<input type="hidden" id="argon-unified-api-form-id" value="" />
		<p>
			<label>
				<strong><?php _e('配置名称:', 'argon'); ?></strong> *<br>
				<input type="text" id="argon-unified-api-form-name" class="regular-text" placeholder="<?php _e('例如: 主 OpenAI API', 'argon'); ?>" />
			</label>
		</p>
		<p>
			<label for="argon-unified-api-form-key">
				<strong><?php _e('API 密钥:', 'argon'); ?></strong> *
			</label><br>
			<input type="password" id="argon-unified-api-form-key" class="regular-text" placeholder="sk-..." />
			<button type="button" class="button" id="argon-toggle-unified-password" style="margin-left: 5px;">
				<span class="dashicons dashicons-visibility"></span>
			</button>
		</p>
		<p>
			<label>
				<strong><?php _e('提供商:', 'argon'); ?></strong> *<br>
				<select id="argon-unified-api-form-provider" class="regular-text">
					<option value=""><?php _e('请选择提供商', 'argon'); ?></option>
					<?php foreach ($providers as $key => $name): ?>
						<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<p>
			<label>
				<strong><?php _e('API 端点:', 'argon'); ?></strong> <small>(<?php _e('可选', 'argon'); ?>)</small><br>
				<input type="text" id="argon-unified-api-form-endpoint" class="regular-text" placeholder="<?php _e('留空使用默认端点', 'argon'); ?>" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php _e('模型:', 'argon'); ?></strong> <small>(<?php _e('可选', 'argon'); ?>)</small><br>
				<input type="text" id="argon-unified-api-form-model" class="regular-text" placeholder="<?php _e('留空使用默认模型', 'argon'); ?>" />
				<button type="button" class="button" id="argon-refresh-unified-models">
					<span class="dashicons dashicons-update"></span> <?php _e('刷新', 'argon'); ?>
				</button>
			</label>
			<div id="argon-unified-models-list" style="display:none;">
				<p class="description"><?php _e('加载中...', 'argon'); ?></p>
			</div>
		</p>
		<p>
			<button type="button" class="button button-primary" id="argon-save-unified-api">
				<?php _e('保存', 'argon'); ?>
			</button>
			<button type="button" class="button" id="argon-cancel-unified-api">
				<?php _e('取消', 'argon'); ?>
			</button>
		</p>
	</div>
	<p class="description">
		<?php _e('统一管理所有 AI 服务商的 API 配置。不同功能可以使用不同的 API（在下方的文章摘要和评论审核设置中选择）', 'argon');?>
	</p>
</td>
				</tr>
				
				<tr><th class="subtitle" colspan="2"><h3 id="ai-summary"><?php _e('文章摘要', 'argon');?></h3></th></tr>
				<tr>
<th><label><?php _e('启用 AI 摘要', 'argon');?></label></th>
<td>
	<select name="argon_ai_summary_enable">
		<?php $argon_ai_summary_enable = get_option('argon_ai_summary_enable', 'false'); ?>
		<option value="true" <?php if ($argon_ai_summary_enable=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
		<option value="false" <?php if ($argon_ai_summary_enable=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
	</select>
	<p class="description"><?php _e('开启后，文章开头会显示 AI 生成的摘要。每篇文章只生成一次，结果会缓存到服务器。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('默认使用 API', 'argon');?></label></th>
<td>
	<select name="argon_ai_summary_active_api">
		<option value=""><?php _e('请选择 API', 'argon'); ?></option>
		<?php foreach ($all_apis as $api): ?>
			<option value="<?php echo esc_attr($api['id']); ?>" <?php selected($summary_active_api, $api['id']); ?>>
				<?php $provider_key = argon_normalize_ai_provider(isset($api['provider']) ? $api['provider'] : 'openai_compatible'); ?>
				<?php echo esc_html($api['name']); ?> (<?php echo esc_html(isset($providers[$provider_key]) ? $providers[$provider_key] : argon_get_ai_provider_label($provider_key)); ?>)
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php _e('选择用于生成文章摘要的 API 配置', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('摘要提示词', 'argon');?></label></th>
<td>
	<textarea name="argon_ai_summary_prompt" rows="12" cols="70" style="font-family: monospace;"><?php echo esc_textarea(function_exists('argon_get_ai_summary_prompt') ? argon_get_ai_summary_prompt() : get_option('argon_ai_summary_prompt', '')); ?></textarea>
	<p class="description"><?php _e('AI 生成摘要时使用的提示词。文章内容会自动附加在提示词后面。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('摘要思考模式', 'argon');?></label></th>
<td>
	<?php $argon_ai_summary_thinking_mode = get_option('argon_ai_summary_thinking_mode', 'disabled'); ?>
	<select name="argon_ai_summary_thinking_mode">
		<option value="disabled" <?php selected($argon_ai_summary_thinking_mode, 'disabled'); ?>><?php _e('关闭', 'argon');?></option>
		<option value="auto" <?php selected($argon_ai_summary_thinking_mode, 'auto'); ?>><?php _e('自动', 'argon');?></option>
		<option value="enabled" <?php selected($argon_ai_summary_thinking_mode, 'enabled'); ?>><?php _e('启用', 'argon');?></option>
	</select>
	<p class="description"><?php _e('控制摘要提示词中的思考模式约束。无论选择哪项，前台都会自动隐藏模型返回的思考过程。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('排除文章 ID', 'argon');?></label></th>
<td>
	<input type="text" class="regular-text" name="argon_ai_summary_exclude_posts" value="<?php echo esc_attr(get_option('argon_ai_summary_exclude_posts', '')); ?>" placeholder="1,2,3"/>
	<p class="description"><?php _e('不生成 AI 摘要的文章 ID，多个 ID 用英文逗号分隔', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('清除缓存', 'argon');?></label></th>
<td>
	<button type="button" class="button" id="argon-clear-ai-summary-cache">
		<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
		<?php _e('清除所有文章的 AI 摘要缓存', 'argon'); ?>
	</button>
	<p class="description"><?php _e('清除后，所有文章的 AI 摘要将在下次访问时重新生成', 'argon');?></p>
</td>
				</tr>
				
				<tr><th class="subtitle" colspan="2"><h3 id="ai-seo"><?php _e('文章 SEO 优化', 'argon');?></h3></th></tr>
				<tr>
<th><label><?php _e('启用 AI SEO 优化', 'argon');?></label></th>
<td>
	<select name="argon_ai_seo_enable">
		<?php $argon_ai_seo_enable = get_option('argon_ai_seo_enable', 'false'); ?>
		<option value="true" <?php if ($argon_ai_seo_enable=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>
		<option value="false" <?php if ($argon_ai_seo_enable=='false'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>
	</select>
	<p class="description"><?php _e('开启后，可使用 AI 为文章自动分析生成针对搜索引擎优化的描述（Description）和关键词（Keywords），并在文章编辑页提供独立卡片进行一键优化与微调。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('默认使用 API', 'argon');?></label></th>
<td>
	<select name="argon_ai_seo_active_api">
		<option value=""><?php _e('复用文章摘要 API（默认）', 'argon'); ?></option>
		<?php foreach ($all_apis as $api): ?>
			<option value="<?php echo esc_attr($api['id']); ?>" <?php selected($seo_active_api, $api['id']); ?>>
				<?php $provider_key = argon_normalize_ai_provider(isset($api['provider']) ? $api['provider'] : 'openai_compatible'); ?>
				<?php echo esc_html($api['name']); ?> (<?php echo esc_html(isset($providers[$provider_key]) ? $providers[$provider_key] : argon_get_ai_provider_label($provider_key)); ?>)
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php _e('选择用于文章 SEO 优化的 API 配置。留空时自动复用文章摘要的活动 API。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('自动生成时机', 'argon');?></label></th>
<td>
	<?php $argon_ai_seo_auto_mode = get_option('argon_ai_seo_auto_mode', 'empty_only'); ?>
	<select name="argon_ai_seo_auto_mode">
		<option value="empty_only" <?php selected($argon_ai_seo_auto_mode, 'empty_only'); ?>><?php _e('发布/保存时若未填写 SEO 则自动补充（推荐）', 'argon');?></option>
		<option value="always" <?php selected($argon_ai_seo_auto_mode, 'always'); ?>><?php _e('发布/保存时始终自动重新生成（覆盖已有）', 'argon');?></option>
		<option value="manual" <?php selected($argon_ai_seo_auto_mode, 'manual'); ?>><?php _e('仅在编辑页手动点击生成（不自动触发）', 'argon');?></option>
	</select>
	<p class="description"><?php _e('控制在后台保存或发布公开文章时，是否由系统自动调用 AI 补全 SEO 信息。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('自动联动与补充', 'argon');?></label></th>
<td>
	<?php 
	$argon_ai_seo_sync_excerpt = get_option('argon_ai_seo_sync_excerpt', 'true');
	$argon_ai_seo_sync_tags = get_option('argon_ai_seo_sync_tags', 'false');
	?>
	<label style="display: block; margin-bottom: 8px;">
		<input type="checkbox" name="argon_ai_seo_sync_excerpt" value="true" <?php if ($argon_ai_seo_sync_excerpt=='true'){echo 'checked';}?>/>
		<?php _e('文章未填写摘要时，自动同步 SEO 描述为文章摘要（Excerpt）', 'argon');?>
	</label>
	<label style="display: block;">
		<input type="checkbox" name="argon_ai_seo_sync_tags" value="true" <?php if ($argon_ai_seo_sync_tags=='true'){echo 'checked';}?>/>
		<?php _e('自动同步相关的文章标签（Tags）', 'argon');?>
	</label>
	<p class="description"><?php _e('可选择同步到 WordPress 摘要与标签；标签是否允许新建由下方同步策略决定。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label for="argon_ai_seo_tag_mode"><?php _e('标签同步策略', 'argon');?></label></th>
<td>
	<select name="argon_ai_seo_tag_mode" id="argon_ai_seo_tag_mode">
		<option value="existing_only" <?php selected(get_option('argon_ai_seo_tag_mode', 'existing_only'), 'existing_only'); ?>><?php _e('仅复用已有标签（推荐）', 'argon');?></option>
		<option value="allow_new" <?php selected(get_option('argon_ai_seo_tag_mode', 'existing_only'), 'allow_new'); ?>><?php _e('允许将 SEO 关键词新建为标签', 'argon');?></option>
	</select>
	<p class="description"><?php _e('启用标签同步后，默认从已有标签中选择最多 3 个相关主题，不匹配时不添加；SEO 关键词仍单独保存。允许新建标签会增加单篇专属标签。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><?php _e('AI 整理标签', 'argon');?></th>
<td>
	<a class="button" href="<?php echo esc_url(admin_url('edit.php?page=argon-ai-tags')); ?>"><?php _e('分析并合并相似标签', 'argon');?></a>
	<p class="description"><?php _e('扫描已有标签，预览同义词、拼写变体和过细标签的合并建议，勾选后迁移文章关联。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('关键词数量上限', 'argon');?></label></th>
<td>
	<input type="number" min="1" max="15" step="1" class="small-text" name="argon_ai_seo_max_keywords" value="<?php echo esc_attr(get_option('argon_ai_seo_max_keywords', '5')); ?>"/>
	<p class="description"><?php _e('AI 生成的 SEO 关键词数量（建议 3-8 个之间）。', 'argon');?></p>
</td>
				</tr>
				<tr>
<th><label><?php _e('SEO 提示词模板', 'argon');?></label></th>
<td>
	<textarea name="argon_ai_seo_custom_prompt" rows="12" cols="70" style="font-family: monospace;"><?php echo esc_textarea(function_exists('argon_get_ai_seo_prompt') ? argon_get_ai_seo_prompt() : get_option('argon_ai_seo_custom_prompt', '')); ?></textarea>
	<p class="description"><?php _e('AI 生成 SEO 描述与关键词时使用的提示词。系统会自动将标题、文章内容及分类作为上下文拼接到提示词中。留空可使用主题内置的针对性 SEO 提示词。', 'argon');?></p>
</td>
				</tr>
				
				<tr><th class="subtitle" colspan="2"><h3 id="ai-spam-detection"><?php _e('评论审核', 'argon');?></h3></th></tr>
				<tr class="argon-spam-section-heading" id="argon-spam-section-api">
<th colspan="2">
	<h4><?php _e('1. AI 检测总开关与 API', 'argon');?></h4>
	<p><?php _e('控制评论 AI 审核是否启用，并选择专用 API。', 'argon');?></p>
</th>
				</tr>
				<tr>
<th><label><?php _e('启用 AI 识别', 'argon');?></label></th>
<td>
	<?php $argon_comment_spam_detection_enable = get_option('argon_comment_spam_detection_enable', 'false'); ?>
	<label>
		<input type="checkbox" name="argon_comment_spam_detection_enable" value="true" <?php if ($argon_comment_spam_detection_enable=='true'){echo 'checked';}?>/> 
		<?php _e('启用 AI 自动识别垃圾评论', 'argon');?>
	</label>
	<p class="description">
		<?php _e('开启后，将使用 AI 自动识别广告、反动、违法等垃圾评论。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row">
<th><label><?php _e('默认使用 API', 'argon');?></label></th>
<td>
	<select name="argon_ai_spam_active_api">
		<option value=""><?php _e('请选择 API', 'argon'); ?></option>
		<?php foreach ($all_apis as $api): ?>
			<option value="<?php echo esc_attr($api['id']); ?>" <?php selected($spam_active_api, $api['id']); ?>>
				<?php $provider_key = argon_normalize_ai_provider(isset($api['provider']) ? $api['provider'] : 'openai_compatible'); ?>
				<?php echo esc_html($api['name']); ?> (<?php echo esc_html(isset($providers[$provider_key]) ? $providers[$provider_key] : argon_get_ai_provider_label($provider_key)); ?>)
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php _e('选择用于垃圾评论检测的 API 配置', 'argon');?></p>
</td>
				</tr>
				<tr class="argon-spam-section-heading argon-spam-dependent-row" id="argon-spam-section-strategy">
<th colspan="2">
	<h4><?php _e('2. 新评论检测策略', 'argon');?></h4>
	<p><?php _e('决定哪些新评论需要调用 AI；无效参数会随模式自动隐藏。', 'argon');?></p>
</th>
				</tr>
				<tr class="argon-spam-dependent-row">
<th><label><?php _e('实时检测模式', 'argon');?></label></th>
<td>
	<select name="argon_comment_spam_detection_mode">
		<?php $argon_comment_spam_detection_mode = get_option('argon_comment_spam_detection_mode', 'sample'); ?>
		<option value="manual" <?php if ($argon_comment_spam_detection_mode=='manual'){echo 'selected';} ?>><?php _e('关闭实时检测', 'argon');?></option>
		<option value="keyword" <?php if ($argon_comment_spam_detection_mode=='keyword'){echo 'selected';} ?>><?php _e('关键字必查', 'argon');?></option>
		<option value="sample" <?php if ($argon_comment_spam_detection_mode=='sample'){echo 'selected';} ?>><?php _e('智能抽查（推荐）', 'argon');?></option>
		<option value="all" <?php if ($argon_comment_spam_detection_mode=='all'){echo 'selected';} ?>><?php _e('全量检测', 'argon');?></option>
	</select>
	<p class="description">
		<strong><?php _e('关闭实时检测', 'argon');?>：</strong><?php _e('只能使用下方的手动批量扫描', 'argon');?><br/>
		<strong><?php _e('关键字必查', 'argon');?>：</strong><?php _e('仅检测触发关键字的评论，节省 API 额度', 'argon');?><br/>
		<strong><?php _e('智能抽查', 'argon');?>：</strong><?php _e('根据用户信誉动态调整检测概率，平衡成本与效果', 'argon');?><br/>
		<strong><?php _e('全量检测', 'argon');?>：</strong><?php _e('每条新评论都检测，防护最严密但 API 消耗最高', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-sample-row">
<th><label><?php _e('抽查基础概率', 'argon');?></label></th>
<td>
	<input type="number" name="argon_comment_spam_detection_sample_rate" min="1" max="100" value="<?php echo esc_attr(get_option('argon_comment_spam_detection_sample_rate', '20')); ?>" style="width: 80px;"/> %
	<p class="description">
		<?php _e('智能抽查模式的基础概率，默认 20%。系统会根据用户历史通过率自动调整：', 'argon');?><br/>
		<?php _e('• 信誉好的用户降低检测（最低 5%）', 'argon');?><br/>
		<?php _e('• 信誉差的用户提高检测（最高 80%）', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-section-heading argon-spam-dependent-row" id="argon-spam-section-rules">
<th colspan="2">
	<h4><?php _e('3. 关键词与本地规则', 'argon');?></h4>
	<p><?php _e('触发词、人工黑名单与自动学习规则分开管理，规则来源互不覆盖。', 'argon');?></p>
</th>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-rules-row argon-spam-trigger-row">
<th><label><?php _e('触发关键字', 'argon');?></label></th>
<td>
	<textarea name="argon_comment_spam_detection_trigger_keywords" rows="4" cols="70" placeholder="<?php _e('每行一个关键字', 'argon');?>"><?php echo esc_textarea(get_option('argon_comment_spam_detection_trigger_keywords', '')); ?></textarea>
	<p class="description">
		<?php _e('包含这些关键字的评论会触发 AI 检测（关键字必查模式）或提高检测概率（智能抽查模式）。每行一个关键字。', 'argon');?><br/>
		<?php _e('示例：广告、推广、加微信、点击链接等', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-rules-row">
<th><label><?php _e('手动黑名单', 'argon');?></label></th>
<td>
	<textarea name="argon_comment_spam_detection_keywords" rows="4" cols="70" placeholder="<?php _e('每行一个关键字', 'argon');?>"><?php echo esc_textarea(get_option('argon_comment_spam_detection_keywords', '')); ?></textarea>
	<p class="description">
		<?php _e('由管理员维护的确定性规则；命中后直接拦截，不调用 AI。每行一个关键字。', 'argon');?><br/>
		<?php _e('自动学习规则不会写入此列表，便于追踪每条规则的来源。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-section-heading argon-spam-dependent-row" id="argon-spam-section-exclusions">
<th colspan="2">
	<h4><?php _e('4. 检测豁免', 'argon');?></h4>
	<p><?php _e('按登录状态、邮箱或 IP 跳过可信评论者。', 'argon');?></p>
</th>
				</tr>
				<tr class="argon-spam-dependent-row">
<th><label><?php _e('检测范围', 'argon');?></label></th>
<td>
	<?php $argon_comment_spam_detection_exclude_logged_in = get_option('argon_comment_spam_detection_exclude_logged_in', 'true'); ?>
	<label style="display: block; margin-bottom: 8px;">
		<input type="checkbox" name="argon_comment_spam_detection_exclude_logged_in" value="true" <?php if ($argon_comment_spam_detection_exclude_logged_in=='true'){echo 'checked';}?>/> 
		<?php _e('跳过已登录用户的评论', 'argon');?>
	</label>
	<p class="description"><?php _e('开启后，已登录用户（包括管理员、编辑等）的评论不会被检测。', 'argon');?></p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row">
<th><label><?php _e('白名单', 'argon');?></label></th>
<td>
	<textarea rows="4" cols="70" name="argon_comment_spam_detection_whitelist" placeholder="<?php _e('每行一个邮箱或 IP 地址', 'argon');?>"><?php echo esc_textarea(get_option('argon_comment_spam_detection_whitelist', '')); ?></textarea>
	<p class="description">
		<?php _e('白名单中的邮箱或 IP 地址的评论不会被检测，每行一个。', 'argon');?><br/>
		<?php _e('示例：user@example.com 或 192.168.1.1', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-section-heading argon-spam-dependent-row" id="argon-spam-section-decision">
<th colspan="2">
	<h4><?php _e('5. 判定阈值与处理动作', 'argon');?></h4>
	<p><?php _e('配置 AI 判断强度，以及高、低置信度结果的处理方式。', 'argon');?></p>
</th>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-decision-row">
<th><label><?php _e('高置信度处理', 'argon');?></label></th>
<td>
	<select name="argon_comment_spam_detection_auto_action">
		<?php $argon_comment_spam_detection_auto_action = get_option('argon_comment_spam_detection_auto_action', 'trash'); ?>
		<option value="trash" <?php if ($argon_comment_spam_detection_auto_action=='trash'){echo 'selected';} ?>><?php _e('自动移入回收站', 'argon');?></option>
		<option value="hold" <?php if ($argon_comment_spam_detection_auto_action=='hold'){echo 'selected';} ?>><?php _e('标记为待审核', 'argon');?></option>
		<option value="mark" <?php if ($argon_comment_spam_detection_auto_action=='mark'){echo 'selected';} ?>><?php _e('仅标记不处理', 'argon');?></option>
	</select>
	<p class="description"><?php _e('仅当 AI 判为垃圾且置信度达到下方阈值时执行。低于阈值或 AI 建议复核的评论统一转为待审核。', 'argon');?></p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-decision-row">
<th><label><?php _e('自动处理阈值', 'argon');?></label></th>
<td>
	<label style="display: block; margin-bottom: 8px;">
		<?php _e('置信度阈值', 'argon');?>: 
		<input type="number" name="argon_comment_spam_detection_confidence_threshold" min="0" max="100" step="5" value="<?php echo esc_attr(get_option('argon_comment_spam_detection_confidence_threshold', '85')); ?>" style="width: 80px;"/> %
	</label>
	<p class="description">
		<?php _e('达到阈值才执行高置信度处理；建议值 85%。证据不足时保留给管理员复核。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-rules-row">
<th><label><?php _e('黑名单拦截通知', 'argon');?></label></th>
<td>
	<?php $argon_comment_spam_blacklist_notify = get_option('argon_comment_spam_blacklist_notify', 'true'); ?>
	<label>
		<input type="checkbox" name="argon_comment_spam_blacklist_notify" value="true" <?php if ($argon_comment_spam_blacklist_notify=='true'){echo 'checked';}?>/> 
		<?php _e('向评论者发送拦截通知邮件（带 AI 复审按钮）', 'argon');?>
	</label>
	<p class="description">
		<?php _e('当评论被黑名单关键字拦截时，向评论者发送通知邮件。评论者可以申请 AI 复审，如果 AI 判定无误，将自动恢复评论并优化关键字规则。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-rules-row">
<th><label><?php _e('关键字优化日志', 'argon');?></label></th>
<td>
	<?php
	$optimization_log = get_option('argon_spam_keyword_optimization_log', []);
	if (!empty($optimization_log)):
		// 倒序显示，最新的在前
		$optimization_log = array_reverse($optimization_log);
	?>
		<details class="argon-spam-rule-card">
			<summary style="cursor: pointer; font-weight: 600;">
				<?php printf(__('查看 %d 条误判复审优化记录', 'argon'), count($optimization_log)); ?>
			</summary>
		<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: #f9f9f9;">
			<?php foreach ($optimization_log as $log): ?>
				<div style="background: white; padding: 12px; margin-bottom: 10px; border-left: 3px solid #5e72e4; border-radius: 3px;">
					<p style="margin: 0 0 8px 0; color: #666; font-size: 12px;">
						<strong><?php echo date('Y-m-d H:i:s', $log['time']); ?></strong>
						<?php if (!empty($log['comment_id'])): ?>
							| 评论 #<?php echo $log['comment_id']; ?>
						<?php endif; ?>
					</p>
					<?php if (!empty($log['removed_keywords'])): ?>
						<p style="margin: 0 0 5px 0; color: #333;">
							<span style="color: #dc3545;">✗ 移除关键字：</span>
							<code style="background: #f8d7da; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
								<?php echo esc_html(implode('、', $log['removed_keywords'])); ?>
							</code>
						</p>
					<?php endif; ?>
					<?php if (!empty($log['modified_suggestions'])): ?>
						<p style="margin: 0 0 5px 0; color: #333;">
							<span style="color: #ffc107;">⚠ 优化建议：</span>
						</p>
						<?php foreach ($log['modified_suggestions'] as $suggestion): ?>
							<p style="margin: 0 0 5px 0; padding-left: 20px; font-size: 12px; color: #666;">
								<code style="background: #fff3cd; padding: 2px 6px; border-radius: 3px;">
									<?php echo esc_html($suggestion['original']); ?>
								</code>
								→ <?php echo esc_html($suggestion['suggestion']); ?>
							</p>
						<?php endforeach; ?>
					<?php endif; ?>
					<?php if (!empty($log['comment_author']) && !empty($log['comment_content'])): ?>
						<p style="margin: 5px 0 0 0; padding-top: 8px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
							<strong><?php echo esc_html($log['comment_author']); ?>：</strong>
							<?php echo esc_html($log['comment_content']); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="description" style="margin-top: 10px;">
			<?php printf(__('共 %d 条优化记录（最多保留 50 条）', 'argon'), count($optimization_log)); ?>
			<button type="button" class="button" id="argon-clear-keyword-optimization-log" style="margin-left: 10px;">
				<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
				<?php _e('清除日志', 'argon'); ?>
			</button>
		</p>
		</details>
	<?php else: ?>
		<p class="description"><?php _e('暂无优化记录。当用户反馈黑名单误判并通过 AI 复审后，系统会自动优化关键字并记录在此。', 'argon');?></p>
	<?php endif; ?>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-decision-row">
<th><label><?php _e('Prompt 模式', 'argon');?></label></th>
<td>
	<select name="argon_comment_spam_detection_prompt_mode">
		<?php $argon_comment_spam_detection_prompt_mode = get_option('argon_comment_spam_detection_prompt_mode', 'standard'); ?>
		<option value="minimal" <?php if ($argon_comment_spam_detection_prompt_mode=='minimal'){echo 'selected';} ?>><?php _e('极简模式（约 1k 内，最低开销）', 'argon');?></option>
		<option value="standard" <?php if ($argon_comment_spam_detection_prompt_mode=='standard'){echo 'selected';} ?>><?php _e('标准模式（约 2–3k，推荐）', 'argon');?></option>
		<option value="enhanced" <?php if ($argon_comment_spam_detection_prompt_mode=='enhanced'){echo 'selected';} ?>><?php _e('增强模式（约 4–6k，深度核验）', 'argon');?></option>
		<option value="custom" <?php if ($argon_comment_spam_detection_prompt_mode=='custom'){echo 'selected';} ?>><?php _e('自定义 Prompt', 'argon');?></option>
	</select>
	<p class="description">
		<?php _e('三档均可独立完成审核，但指令深度和开销明显递增；长度为参考值，会因批量输出协议略有浮动。AI 只返回风险参考值，最终置信度由服务器计算。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-decision-row argon-custom-prompt-row" style="<?php echo ($argon_comment_spam_detection_prompt_mode !== 'custom') ? 'display:none;' : ''; ?>">
<th><label><?php _e('自定义 Prompt', 'argon');?></label></th>
<td>
	<textarea name="argon_comment_spam_detection_custom_prompt" rows="8" cols="70" style="font-family: monospace;"><?php echo esc_textarea(get_option('argon_comment_spam_detection_custom_prompt', '')); ?></textarea>
	<p class="description">
		<?php _e('这里填写额外的业务规则。系统会在其后强制追加完整审核边界、语境判断流程、违规原文关键词、AI 风险参考值和 JSON 输出契约；自定义内容与评论都不能覆盖这些规则。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="argon-spam-dependent-row argon-spam-rules-row">
<th><label><?php _e('自动学习规则', 'argon');?></label></th>
<td>
	<?php
	$learned_keywords = get_option('argon_comment_spam_learned_keywords', []);
	$keyword_history = [];
	$high_confidence_count = 0;
	if (!empty($learned_keywords)) {
		foreach ($learned_keywords as $k => $v) {
			if (argon_is_learned_spam_rule_ready($v)) {
				$high_confidence_count++;
			}
		}
	}
	?>
	<div class="argon-keyword-cache-status argon-spam-rule-card">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;">
			<strong style="font-size: 14px; color: #1d2327;"><?php _e('本地学习规则', 'argon'); ?></strong>
		</div>
		<p style="margin: 0; font-size: 13px;">
			<?php printf(__('已学习 %d 条候选规则，其中 %d 条满足高置信度快速拦截条件。', 'argon'), count($learned_keywords), $high_confidence_count); ?>
		</p>
		<?php if (!empty($learned_keywords)): ?>
		<div style="margin-top: 10px;">
			<button type="button" class="button button-small toggle-keyword-list">
				<span class="dashicons dashicons-list-view" style="margin-top: 2px; font-size: 14px;"></span> <?php _e('查看关键词列表', 'argon'); ?>
			</button>
			<button type="button" class="button button-small" id="argon-clear-learned-keywords" style="margin-left: 5px;">
				<?php _e('清空学习规则', 'argon'); ?>
			</button>
		</div>
		<div class="keyword-list-display" id="keyword-list-display" style="display: none; margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto;">
			<table class="wp-list-table widefat fixed striped" style="font-size: 12px;">
				<thead>
					<tr>
						<th style="width: 25%;"><?php _e('评论原文关键词', 'argon'); ?></th>
						<th style="width: 15%;"><?php _e('服务端置信度', 'argon'); ?></th>
						<th style="width: 15%;"><?php _e('AI 参考均值', 'argon'); ?></th>
						<th style="width: 15%;"><?php _e('垃圾次数', 'argon'); ?></th>
						<th style="width: 15%;"><?php _e('正常次数', 'argon'); ?></th>
						<th style="width: 15%;"><?php _e('操作', 'argon'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$sorted_keywords = $learned_keywords;
					uasort($sorted_keywords, function($a, $b) {
						$conf_a = argon_calculate_keyword_confidence($a);
						$conf_b = argon_calculate_keyword_confidence($b);
						return $conf_b <=> $conf_a;
					});
					foreach ($sorted_keywords as $keyword => $stats): 
						$confidence = round(argon_calculate_keyword_confidence($stats) * 100, 1);
						$spam_count = isset($stats['spam_count']) ? $stats['spam_count'] : 0;
						$normal_count = isset($stats['normal_count']) ? $stats['normal_count'] : 0;
						$reference_count = isset($stats['reference_count']) ? intval($stats['reference_count']) : 0;
						$reference_sum = isset($stats['reference_sum']) ? floatval($stats['reference_sum']) : 0;
						$reference_average = $reference_count > 0
							? round(($reference_sum / $reference_count) * 100, 1)
							: null;
						$is_high = argon_is_learned_spam_rule_ready($stats);
					?>
					<tr>
						<td>
							<span style="font-weight: 600;"><?php echo esc_html($keyword); ?></span>
							<?php if ($is_high): ?>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 14px;" title="<?php _e('高置信度', 'argon'); ?>"></span>
							<?php endif; ?>
						</td>
						<td>
							<span style="color: <?php echo $confidence > 80 ? '#46b450' : ($confidence > 50 ? '#ffb900' : '#dc3232'); ?>;">
								<?php echo $confidence; ?>%
							</span>
						</td>
						<td><?php echo $reference_average === null ? '—' : esc_html($reference_average . '%'); ?></td>
						<td><?php echo intval($spam_count); ?></td>
						<td><?php echo intval($normal_count); ?></td>
						<td>
							<button type="button" class="button button-small edit-keyword-btn" data-keyword="<?php echo esc_attr($keyword); ?>" data-spam="<?php echo esc_attr($spam_count); ?>" data-normal="<?php echo esc_attr($normal_count); ?>">
								<?php _e('编辑', 'argon'); ?>
							</button>
							<button type="button" class="button button-small delete-keyword-btn" data-keyword="<?php echo esc_attr($keyword); ?>" style="color: #dc3232;">
								<?php _e('删除', 'argon'); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php else: ?>
		<div style="margin-top: 10px;">
			<button type="button" class="button button-small" id="argon-clear-learned-keywords">
				<?php _e('清空学习规则', 'argon'); ?>
			</button>
		</div>
		<?php endif; ?>
	<p class="description" style="margin-top: 10px;">
		<?php _e('AI 只提交评论正文中的违规原文片段和风险参考值。服务端结合垃圾次数、正常次数与历史参考均值计算置信度；短词、变体和证据不足的命中始终回送 AI，只有充分验证的完整短语才可本地快速处理。', 'argon');?>
	</p>
	<!-- 关键词编辑模态框 -->
	<div id="argon-keyword-edit-modal" style="display: none;">
			<div class="argon-modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); z-index: 100000;"></div>
		<div class="argon-modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; z-index: 100001; box-shadow: 0 5px 30px rgba(0,0,0,0.3);">
			<h3 style="margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">
				<?php _e('编辑关键词', 'argon'); ?>
			</h3>
			<input type="hidden" id="edit-keyword-original" value="">
			<table class="form-table" style="margin: 0;">
				<tr>
					<th style="padding: 5px 10px 5px 0;"><?php _e('关键词', 'argon'); ?></th>
					<td><input type="text" id="edit-keyword-text" class="regular-text" value=""></td>
				</tr>
				<tr>
					<th style="padding: 5px 10px 5px 0;"><?php _e('垃圾次数', 'argon'); ?></th>
					<td><input type="number" id="edit-keyword-spam" min="0" value=""></td>
				</tr>
				<tr>
					<th style="padding: 5px 10px 5px 0;"><?php _e('正常次数', 'argon'); ?></th>
					<td><input type="number" id="edit-keyword-normal" min="0" value=""></td>
				</tr>
			</table>
			<p class="description" style="margin: 10px 0;">
				<?php _e('修改关键词或样本次数后，服务端会重新计算置信度并记录为“管理员干预”。', 'argon'); ?>
			</p>
			<div style="margin-top: 15px; display: flex; gap: 10px;">
				<button type="button" class="button button-primary" id="save-keyword-edit">
					<?php _e('保存修改', 'argon'); ?>
				</button>
				<button type="button" class="button" id="cancel-keyword-edit">
					<?php _e('取消', 'argon'); ?>
				</button>
			</div>
		</div>
	</div>
</td>
				</tr>
				<tr class="argon-spam-section-heading argon-spam-dependent-row" id="argon-spam-section-history">
<th colspan="2">
	<h4><?php _e('6. 历史评论扫描', 'argon');?></h4>
	<p><?php _e('按需检查尚未经过 AI 审核的已有评论。', 'argon');?></p>
</th>
				</tr>
				<tr class="argon-spam-dependent-row">
<th><label><?php _e('历史评论扫描', 'argon');?></label></th>
<td>
	<button type="button" class="button button-primary" id="argon-spam-scan-all">
		<span class="dashicons dashicons-search" style="margin-top: 3px;"></span>
		<?php _e('扫描所有评论', 'argon'); ?>
	</button>
	<button type="button" class="button" id="argon-spam-scan-pending" style="margin-left: 10px;">
		<span class="dashicons dashicons-filter" style="margin-top: 3px;"></span>
		<?php _e('仅扫描待审核', 'argon'); ?>
	</button>
	<span id="argon-spam-scan-status" style="margin-left: 15px; color: #666;"></span>
	<div id="argon-spam-scan-progress" style="display: none; margin: 15px 0;">
		<div style="background: #f0f0f1; border-radius: 4px; height: 24px; position: relative; overflow: hidden;">
			<div id="argon-spam-scan-progress-bar" style="background: var(--themecolor, #5e72e4); height: 100%; width: 0%; transition: width 0.3s;"></div>
			<span id="argon-spam-scan-progress-text" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: 600; color: #333;"></span>
		</div>
	</div>
	<div id="argon-spam-scan-results" style="display: none; margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto;">
		<div id="argon-spam-scan-results-content"></div>
	</div>
	<p class="description">
		<?php _e('自动跳过已有 AI 审核记录的评论，避免重复消耗 API。扫描结果先集中展示，再由管理员选择处理。', 'argon');?>
	</p>
</td>
				</tr>
				<script>
				jQuery(document).ready(function($) {
// ========== API 管理交互 ==========
// 添加 API 按钮
jQuery('#argon-add-unified-api-btn').on('click', function() {
	jQuery('#argon-unified-api-form-id').val('');
	jQuery('#argon-unified-api-form-name').val('');
	jQuery('#argon-unified-api-form-key').val('');
	jQuery('#argon-unified-api-form-provider').val('');
	jQuery('#argon-unified-api-form-endpoint').val('');
	jQuery('#argon-unified-api-form-model').val('');
	jQuery('#argon-unified-models-list').hide();
	jQuery('#argon-unified-api-form').slideDown();
});
// 取消按钮
jQuery('#argon-cancel-unified-api').on('click', function() {
	jQuery('#argon-unified-api-form').slideUp();
});
// 显示/隐藏密码
jQuery('#argon-toggle-unified-password').on('click', function() {
	let $input = jQuery('#argon-unified-api-form-key');
	let $icon = jQuery(this).find('.dashicons');
	if ($input.attr('type') === 'password') {
		$input.attr('type', 'text');
		$icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
	} else {
		$input.attr('type', 'password');
		$icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
	}
});
// 保存 API
jQuery('#argon-save-unified-api').on('click', function() {
	let apiId = jQuery('#argon-unified-api-form-id').val();
	let name = jQuery('#argon-unified-api-form-name').val().trim();
	let apiKey = jQuery('#argon-unified-api-form-key').val().trim();
	let provider = jQuery('#argon-unified-api-form-provider').val();
	let endpoint = jQuery('#argon-unified-api-form-endpoint').val().trim();
	let model = jQuery('#argon-unified-api-form-model').val().trim();
	// 验证
	if (!name) {
		alert('<?php _e('请输入配置名称', 'argon'); ?>');
		return;
	}
	if (!apiKey) {
		alert('<?php _e('请输入 API 密钥', 'argon'); ?>');
		return;
	}
	if (!provider) {
		alert('<?php _e('请选择提供商', 'argon'); ?>');
		return;
	}
	let $btn = jQuery(this);
	$btn.prop('disabled', true).text('<?php _e('保存中...', 'argon'); ?>');
	let action = apiId ? 'argon_update_unified_api' : 'argon_add_unified_api';
	let data = {
		action: action,
		nonce: '<?php echo wp_create_nonce('argon_manage_unified_apis'); ?>',
		name: name,
		api_key: apiKey,
		provider: provider,
		api_endpoint: endpoint,
		model: model
	};
	if (apiId) {
		data.api_id = apiId;
	}
	jQuery.post(ajaxurl, data, function(response) {
		if (response.success) {
			alert(response.data.message);
			location.reload();
		} else {
			let msg = (response.data && response.data.message) ? response.data.message : response.data;
			alert('<?php _e('错误:', 'argon'); ?> ' + msg);
			$btn.prop('disabled', false).text('<?php _e('保存', 'argon'); ?>');
		}
	}).fail(function() {
		alert('<?php _e('请求失败，请重试', 'argon'); ?>');
		$btn.prop('disabled', false).text('<?php _e('保存', 'argon'); ?>');
	});
});
// 编辑 API
jQuery(document).on('click', '.argon-edit-unified-api', function() {
	let apiId = jQuery(this).data('api-id');
	jQuery.post(ajaxurl, {
		action: 'argon_get_unified_api',
		nonce: '<?php echo wp_create_nonce('argon_manage_unified_apis'); ?>',
		api_id: apiId
	}, function(response) {
		if (response.success) {
			let api = response.data;
			jQuery('#argon-unified-api-form-id').val(api.id);
			jQuery('#argon-unified-api-form-name').val(api.name);
			jQuery('#argon-unified-api-form-key').val(api.api_key);
			jQuery('#argon-unified-api-form-provider').val(api.provider);
			jQuery('#argon-unified-api-form-endpoint').val(api.api_endpoint || '');
			jQuery('#argon-unified-api-form-model').val(api.model || '');
			jQuery('#argon-unified-models-list').hide();
			jQuery('#argon-unified-api-form').slideDown();
			jQuery('html, body').animate({
				scrollTop: jQuery('#argon-unified-api-form').offset().top - 100
			}, 500);
		} else {
			let msg = (response.data && response.data.message) ? response.data.message : response.data;
			alert('<?php _e('错误:', 'argon'); ?> ' + msg);
		}
	});
});
// 删除 API
jQuery(document).on('click', '.argon-delete-unified-api', function() {
	if (!confirm('<?php _e('确定要删除这个 API 配置吗？', 'argon'); ?>')) {
		return;
	}
	let apiId = jQuery(this).data('api-id');
	let $btn = jQuery(this);
	$btn.prop('disabled', true);
	jQuery.post(ajaxurl, {
		action: 'argon_delete_unified_api',
		nonce: '<?php echo wp_create_nonce('argon_manage_unified_apis'); ?>',
		api_id: apiId
	}, function(response) {
		if (response.success) {
			alert(response.data.message);
			location.reload();
		} else {
			let msg = (response.data && response.data.message) ? response.data.message : response.data;
			alert('<?php _e('错误:', 'argon'); ?> ' + msg);
			$btn.prop('disabled', false);
		}
	}).fail(function() {
		alert('<?php _e('请求失败，请重试', 'argon'); ?>');
		$btn.prop('disabled', false);
	});
});
// 测试 API
jQuery(document).on('click', '.argon-test-unified-api', function() {
	let apiId = jQuery(this).data('api-id');
	let $btn = jQuery(this);
	let originalHtml = $btn.html();
	let $msg = jQuery('#argon-unified-api-status');
	$btn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> <?php _e('测试中...', 'argon'); ?>');
	$msg.html('<span style="color: #666;"><?php _e('正在连接 API...', 'argon'); ?></span>');
	jQuery.post(ajaxurl, {
		action: 'argon_test_unified_api',
		nonce: '<?php echo wp_create_nonce('argon_test_unified_api'); ?>',
		api_id: apiId
	}, function(response) {
		if (response.success) {
			$msg.html('<span style="color: #4caf50; font-weight: 500;"><span class="dashicons dashicons-yes" style="font-size: 18px; width: 18px; height: 18px; vertical-align: middle;"></span> ' + response.data.message + '</span>');
		} else {
			let errorMsg = (response.data && response.data.message) ? response.data.message : '<?php _e('未知错误', 'argon'); ?>';
			let detailHtml = '';
			if (response.data && response.data.debug) {
				let debugInfo = JSON.stringify(response.data.debug, null, 2);
				detailHtml = '<div style="margin-top: 5px;"><a href="javascript:void(0);" onclick="jQuery(this).next().toggle();" style="font-size: 12px; color: #d63638; text-decoration: underline;"><?php _e('查看详细信息', 'argon'); ?></a><pre style="display: none; background: #fff; padding: 10px; margin-top: 5px; overflow-x: auto; color: #333; border: 1px solid #ddd; border-left: 3px solid #d63638; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-wrap: break-word;">' + debugInfo + '</pre></div>';
			}
			$msg.html('<span style="color: #d63638; font-weight: 500;"><span class="dashicons dashicons-no" style="font-size: 18px; width: 18px; height: 18px; vertical-align: middle;"></span> <?php _e('测试失败:', 'argon'); ?> ' + errorMsg + '</span>' + detailHtml);
		}
		$btn.prop('disabled', false).html(originalHtml);
	}).fail(function(xhr, status, error) {
		let failMsg = xhr.responseText || error || '<?php _e('网络错误', 'argon'); ?>';
		// 尝试解析 JSON 错误
		try {
			let jsonResp = JSON.parse(xhr.responseText);
			if (jsonResp && jsonResp.data && jsonResp.data.message) {
				failMsg = jsonResp.data.message;
			}
		} catch(e) {}
		$msg.html('<span style="color: #d63638; font-weight: 500;"><span class="dashicons dashicons-no" style="font-size: 18px; width: 18px; height: 18px; vertical-align: middle;"></span> <?php _e('请求失败:', 'argon'); ?> ' + failMsg + '</span>');
		$btn.prop('disabled', false).html(originalHtml);
	});
});
// 刷新模型列表
jQuery('#argon-refresh-unified-models').on('click', function() {
	let apiKey = jQuery('#argon-unified-api-form-key').val().trim();
	let provider = jQuery('#argon-unified-api-form-provider').val();
	let endpoint = jQuery('#argon-unified-api-form-endpoint').val().trim();
	if (!apiKey) {
		alert('<?php _e('请先输入 API 密钥', 'argon'); ?>');
		return;
	}
	if (!provider) {
		alert('<?php _e('请先选择提供商', 'argon'); ?>');
		return;
	}
	let $btn = jQuery(this);
	let $list = jQuery('#argon-unified-models-list');
	let originalHtml = $btn.html();
	$btn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> <?php _e('加载中...', 'argon'); ?>');
	$list.show().html('<p style="margin: 0; color: #666;"><?php _e('加载中...', 'argon'); ?></p>');
	jQuery.post(ajaxurl, {
		action: 'argon_get_ai_models',
		nonce: '<?php echo wp_create_nonce('argon_get_ai_models'); ?>',
		provider: provider,
		api_key: apiKey,
		api_endpoint: endpoint
	}, function(response) {
		if (response.success && response.data.models && response.data.models.length > 0) {
			let html = '<p style="margin: 0 0 10px 0; font-weight: 600;"><?php _e('可用模型:', 'argon'); ?></p>';
			html += '<div style="display: flex; flex-wrap: wrap; gap: 5px;">';
			response.data.models.forEach(function(model) {
				let modelId = '';
				let modelLabel = '';
				if (typeof model === 'string') {
					modelId = model;
					modelLabel = model;
				} else if (model && typeof model === 'object') {
					modelId = model.id || model.name || '';
					modelLabel = model.name || model.id || '';
				}
				if (!modelId) {
					return;
				}
				html += '<button type="button" class="button button-small argon-select-model" data-model="' + modelId + '" style="margin: 0;">' + modelLabel + '</button>';
			});
			html += '</div>';
			$list.html(html);
		} else {
			$list.html('<p style="margin: 0; color: #d32f2f;"><?php _e('无法获取模型列表', 'argon'); ?></p>');
		}
		$btn.prop('disabled', false).html(originalHtml);
	}).fail(function() {
		$list.html('<p style="margin: 0; color: #d32f2f;"><?php _e('请求失败', 'argon'); ?></p>');
		$btn.prop('disabled', false).html(originalHtml);
	});
});
// 选择模型
jQuery(document).on('click', '.argon-select-model', function() {
	let model = jQuery(this).data('model');
	jQuery('#argon-unified-api-form-model').val(model);
	jQuery('#argon-unified-models-list').slideUp();
});
// ========== 清除 AI 摘要缓存 ==========
jQuery('#argon-clear-ai-summary-cache').on('click', function() {
	if (!confirm('<?php _e('确定要清除所有 AI 摘要缓存吗？', 'argon'); ?>')) {
		return;
	}
	let $btn = jQuery(this);
	let originalHtml = $btn.html();
	$btn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> <?php _e('清除中...', 'argon'); ?>');
	jQuery.post(ajaxurl, {
		action: 'argon_clear_ai_summaries',
		nonce: '<?php echo wp_create_nonce('argon_clear_ai_summaries'); ?>'
	}, function(response) {
		if (response.success) {
			let msg = (response.data && response.data.message) ? response.data.message : response.data;
			alert('✓ ' + msg);
		} else {
			let msg = (response.data && response.data.message) ? response.data.message : response.data;
			alert('✗ <?php _e('清除失败:', 'argon'); ?> ' + msg);
		}
		$btn.prop('disabled', false).html(originalHtml);
	}).fail(function() {
		alert('<?php _e('请求失败，请重试', 'argon'); ?>');
		$btn.prop('disabled', false).html(originalHtml);
	});
});
// ========== 清除关键字优化日志 ==========
jQuery('#argon-clear-keyword-optimization-log').on('click', function() {
	if (!confirm('<?php _e('确定要清除所有关键字优化日志吗？', 'argon'); ?>')) {
		return;
	}
	let $btn = jQuery(this);
	let originalHtml = $btn.html();
	$btn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> <?php _e('清除中...', 'argon'); ?>');
	jQuery.post(ajaxurl, {
		action: 'argon_clear_keyword_optimization_log',
		nonce: '<?php echo wp_create_nonce('argon_clear_keyword_optimization_log'); ?>'
	}, function(response) {
		if (response.success) {
			alert('✓ ' + response.data.message);
			location.reload();
		} else {
			alert('✗ <?php _e('清除失败:', 'argon'); ?> ' + response.data.message);
		}
		$btn.prop('disabled', false).html(originalHtml);
	});
});
// ========== 评论审核分区与条件字段 ==========
// 将同属“关键词与本地规则”的兼容旧字段归拢到第三分区。
jQuery('.argon-spam-rules-row').insertBefore(jQuery('#argon-spam-section-exclusions'));
function toggleSpamReviewRow($row, visible, animate) {
	$row.stop(true, true);
	if (!animate) {
		$row.toggle(visible);
		return;
	}
	if (visible) {
		$row.slideDown(200);
	} else {
		$row.slideUp(200);
	}
}
function syncSpamReviewSettings(animate) {
	let enabled = jQuery('input[name="argon_comment_spam_detection_enable"]').is(':checked');
	let mode = jQuery('select[name="argon_comment_spam_detection_mode"]').val();
	let promptMode = jQuery('select[name="argon_comment_spam_detection_prompt_mode"]').val();
	jQuery('.argon-spam-dependent-row')
		.toggleClass('is-disabled', !enabled)
		.attr('aria-disabled', enabled ? 'false' : 'true')
		.find('input, select, textarea, button').not('.argon-row-save, .argon-row-undo')
		.prop('disabled', !enabled);
	toggleSpamReviewRow(jQuery('.argon-spam-sample-row'), enabled && mode === 'sample', animate);
	toggleSpamReviewRow(jQuery('.argon-spam-trigger-row'), enabled && (mode === 'keyword' || mode === 'sample'), animate);
	toggleSpamReviewRow(jQuery('.argon-custom-prompt-row'), enabled && promptMode === 'custom', animate);
}
jQuery('input[name="argon_comment_spam_detection_enable"], select[name="argon_comment_spam_detection_mode"], select[name="argon_comment_spam_detection_prompt_mode"]').on('change', function() {
	syncSpamReviewSettings(true);
});
jQuery('#main_form').on('submit', function() {
	// 提交前恢复字段，避免关闭总开关时把已保存的从属配置误清空。
	jQuery('.argon-spam-dependent-row').find('input, select, textarea, button').not('.argon-row-save, .argon-row-undo').prop('disabled', false);
});
syncSpamReviewSettings(false);
// ========== 历史评论扫描 ==========
let scanInProgress = false;
function startSpamScan(scanType) {
	if (scanInProgress) {
		alert('<?php _e('扫描正在进行中，请稍候...', 'argon'); ?>');
		return;
	}
	if (!confirm('<?php _e('确定要开始扫描吗？这可能需要一些时间。', 'argon'); ?>')) {
		return;
	}
	scanInProgress = true;
	// 禁用按钮
	jQuery('#argon-spam-scan-all, #argon-spam-scan-pending').prop('disabled', true);
	// 显示进度条
	jQuery('#argon-spam-scan-progress').show();
	jQuery('#argon-spam-scan-progress-bar').css('width', '0%');
	jQuery('#argon-spam-scan-progress-text').text('0%');
	jQuery('#argon-spam-scan-status').text('<?php _e('正在初始化...', 'argon'); ?>');
	// 隐藏之前的结果
	jQuery('#argon-spam-scan-results').hide();
	// 开始扫描
	scanBatch(scanType, 0, 0, 0, 0, []);
}
function scanBatch(scanType, offset, totalScanned, totalSkipped, totalSpam, spamComments) {
	jQuery.post(ajaxurl, {
		action: 'argon_spam_scan_comments',
		nonce: '<?php echo wp_create_nonce('argon_spam_scan_comments'); ?>',
		scan_type: scanType,
		offset: offset,
		batch_size: 10
	}, function(response) {
		if (response.success) {
			let data = response.data;
			totalScanned += data.scanned;
			totalSkipped += data.skipped;
			totalSpam += data.spam_found;
			// 合并垃圾评论列表
			if (data.spam_comments && data.spam_comments.length > 0) {
				spamComments = spamComments.concat(data.spam_comments);
			}
			// 更新进度
			let progress = data.progress || 0;
			jQuery('#argon-spam-scan-progress-bar').css('width', progress + '%');
			jQuery('#argon-spam-scan-progress-text').text(progress + '%');
			jQuery('#argon-spam-scan-status').text('<?php _e('已扫描', 'argon'); ?> ' + totalScanned + ' <?php _e('条，跳过', 'argon'); ?> ' + totalSkipped + ' <?php _e('条，发现垃圾评论', 'argon'); ?> ' + totalSpam + ' <?php _e('条', 'argon'); ?>');
			// 检查是否完成
			if (data.completed) {
				// 扫描完成
				scanInProgress = false;
				jQuery('#argon-spam-scan-all, #argon-spam-scan-pending').prop('disabled', false);
				jQuery('#argon-spam-scan-status').html('<span style="color: #4caf50; font-weight: 600;">✓ <?php _e('扫描完成！', 'argon'); ?></span>');
				// 显示结果
				displayScanResults(totalScanned, totalSkipped, totalSpam, spamComments);
			} else {
				// 继续下一批
				scanBatch(scanType, offset + 10, totalScanned, totalSkipped, totalSpam, spamComments);
			}
		} else {
			// 错误处理
			scanInProgress = false;
			jQuery('#argon-spam-scan-all, #argon-spam-scan-pending').prop('disabled', false);
			let errorMsg = (response.data && response.data.message) ? response.data.message : '<?php _e('未知错误', 'argon'); ?>';
			jQuery('#argon-spam-scan-status').html('<span style="color: #d63638; font-weight: 600;">✗ <?php _e('扫描失败:', 'argon'); ?> ' + errorMsg + '</span>');
		}
	}).fail(function(xhr, status, error) {
		scanInProgress = false;
		jQuery('#argon-spam-scan-all, #argon-spam-scan-pending').prop('disabled', false);
		jQuery('#argon-spam-scan-status').html('<span style="color: #d63638; font-weight: 600;">✗ <?php _e('请求失败:', 'argon'); ?> ' + error + '</span>');
	});
}
function displayScanResults(totalScanned, totalSkipped, totalSpam, spamComments) {
	let html = '<h3 style="margin-top: 0; color: #333;"><?php _e('扫描结果', 'argon'); ?></h3>';
	html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">';
	html += '<div style="background: #e3f2fd; padding: 15px; border-radius: 4px; text-align: center;">';
	html += '<div style="font-size: 28px; font-weight: 700; color: #1976d2;">' + totalScanned + '</div>';
	html += '<div style="color: #666; margin-top: 5px;"><?php _e('已扫描', 'argon'); ?></div>';
	html += '</div>';
	html += '<div style="background: #fff3e0; padding: 15px; border-radius: 4px; text-align: center;">';
	html += '<div style="font-size: 28px; font-weight: 700; color: #f57c00;">' + totalSkipped + '</div>';
	html += '<div style="color: #666; margin-top: 5px;"><?php _e('已跳过', 'argon'); ?></div>';
	html += '</div>';
	html += '<div style="background: #ffebee; padding: 15px; border-radius: 4px; text-align: center;">';
	html += '<div style="font-size: 28px; font-weight: 700; color: #d32f2f;">' + totalSpam + '</div>';
	html += '<div style="color: #666; margin-top: 5px;"><?php _e('垃圾评论', 'argon'); ?></div>';
	html += '</div>';
	html += '</div>';
	if (totalSpam > 0 && spamComments.length > 0) {
		html += '<h4 style="margin: 20px 0 10px 0; color: #333;"><?php _e('发现的垃圾评论', 'argon'); ?></h4>';
		html += '<div style="max-height: 300px; overflow-y: auto;">';
		spamComments.forEach(function(comment) {
			let confidenceColor = comment.confidence >= 90 ? '#d32f2f' : (comment.confidence >= 70 ? '#f57c00' : '#fbc02d');
			html += '<div style="background: #fff; border: 1px solid #ddd; border-left: 3px solid ' + confidenceColor + '; padding: 12px; margin-bottom: 10px; border-radius: 3px;">';
			html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
			html += '<div>';
			html += '<strong style="color: #333;">' + comment.author + '</strong>';
			html += '<span style="color: #999; margin-left: 10px; font-size: 12px;">' + comment.date + '</span>';
			html += '</div>';
			html += '<div>';
			html += '<span style="background: ' + confidenceColor + '; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;"><?php _e('置信度', 'argon'); ?> ' + comment.confidence + '%</span>';
			html += '</div>';
			html += '</div>';
			html += '<div style="color: #666; font-size: 14px; line-height: 1.6;">' + comment.content + '</div>';
			if (comment.reason) {
				html += '<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee; color: #999; font-size: 12px;">';
				html += '<strong><?php _e('原因:', 'argon'); ?></strong> ' + comment.reason;
				html += '</div>';
			}
			if (Array.isArray(comment.violation_keywords) && comment.violation_keywords.length) {
				html += '<div style="margin-top: 6px; color: #999; font-size: 12px;">';
				html += '<strong><?php _e('AI 识别的违规原文:', 'argon'); ?></strong> ' + comment.violation_keywords.join('、');
				html += '</div>';
			}
			html += '<div style="margin-top: 10px;">';
			html += '<a href="' + comment.edit_link + '" target="_blank" class="button button-small"><?php _e('查看', 'argon'); ?></a>';
			html += '</div>';
			html += '</div>';
		});
		html += '</div>';
		html += '<p style="margin-top: 15px; color: #666; font-size: 13px;">';
		html += '<?php _e('提示：您可以在评论管理页面查看和处理这些垃圾评论。', 'argon'); ?>';
		html += '</p>';
	} else if (totalScanned > 0) {
		html += '<div style="background: #e8f5e9; padding: 20px; border-radius: 4px; text-align: center; color: #2e7d32;">';
		html += '<span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px;"></span>';
		html += '<p style="margin: 10px 0 0 0; font-size: 16px; font-weight: 600;"><?php _e('太棒了！没有发现垃圾评论。', 'argon'); ?></p>';
		html += '</div>';
	}
	jQuery('#argon-spam-scan-results-content').html(html);
	jQuery('#argon-spam-scan-results').slideDown();
}
// 绑定扫描按钮事件
jQuery('#argon-spam-scan-all').on('click', function() {
	startSpamScan('all');
});
jQuery('#argon-spam-scan-pending').on('click', function() {
	startSpamScan('pending');
});
// 展开/折叠Prompt内容 - 统一交互
jQuery(document).on('click', '.toggle-prompt-content', function() {
	let mode = jQuery(this).data('mode');
	let $content = jQuery('#prompt-content-' + mode);
	let $icon = jQuery(this).find('.dashicons');
	let $btnText = jQuery(this).contents().filter(function() { return this.nodeType === 3; });
	if ($content.is(':visible')) {
		$content.slideUp(200);
		$icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
		$btnText.last().replaceWith(' <?php _e('查看', 'argon'); ?>');
	} else {
		$content.slideDown(200);
		$icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
		$btnText.last().replaceWith(' <?php _e('隐藏', 'argon'); ?>');
	}
});
// 展开/折叠版本历史
jQuery(document).on('click', '.toggle-history', function() {
	let mode = jQuery(this).data('mode');
	let $history = jQuery('#prompt-history-' + mode);
	if ($history.is(':visible')) {
		$history.slideUp();
	} else {
		$history.slideDown();
	}
});
// 展开/折叠关键词列表
jQuery(document).on('click', '.toggle-keyword-list', function() {
	let $list = jQuery('#keyword-list-display');
	if ($list.is(':visible')) {
		$list.slideUp();
	} else {
		$list.slideDown();
	}
});
// 打开编辑关键词模态框
jQuery(document).on('click', '.edit-keyword-btn', function() {
	let keyword = jQuery(this).data('keyword');
	let spam = jQuery(this).data('spam');
	let normal = jQuery(this).data('normal');
	jQuery('#edit-keyword-original').val(keyword);
	jQuery('#edit-keyword-text').val(keyword);
	jQuery('#edit-keyword-spam').val(spam);
	jQuery('#edit-keyword-normal').val(normal);
	jQuery('#argon-keyword-edit-modal').fadeIn(300, function() {
		jQuery('#edit-keyword-text').focus();
	});
});
// 关闭关键词编辑模态框
jQuery(document).on('click', '#cancel-keyword-edit, #argon-keyword-edit-modal .argon-modal-overlay', function() {
	jQuery('#argon-keyword-edit-modal').fadeOut();
});
// 保存关键词编辑
jQuery(document).on('click', '#save-keyword-edit', function() {
	let $btn = jQuery(this);
	let original = jQuery('#edit-keyword-original').val();
	let keyword = jQuery('#edit-keyword-text').val().trim();
	let spam = parseInt(jQuery('#edit-keyword-spam').val()) || 0;
	let normal = parseInt(jQuery('#edit-keyword-normal').val()) || 0;
	if (!keyword) {
		alert('<?php _e('关键词不能为空', 'argon'); ?>');
		return;
	}
	$btn.prop('disabled', true).text('<?php _e('保存中...', 'argon'); ?>');
	jQuery.post(ajaxurl, {
		action: 'argon_edit_keyword',
		nonce: '<?php echo wp_create_nonce('argon_manage_unified_apis'); ?>',
		original: original,
		keyword: keyword,
		spam_count: spam,
		normal_count: normal
	}, function(response) {
		if (response.success) {
			alert(response.data.message);
			location.reload();
		} else {
			alert(response.data || '<?php _e('保存失败', 'argon'); ?>');
			$btn.prop('disabled', false).text('<?php _e('保存修改', 'argon'); ?>');
		}
	}).fail(function() {
		alert('<?php _e('请求失败，请稍后重试', 'argon'); ?>');
		$btn.prop('disabled', false).text('<?php _e('保存修改', 'argon'); ?>');
	});
});
// 删除关键词
jQuery(document).on('click', '.delete-keyword-btn', function() {
	if (!confirm('<?php _e('确定要删除这个关键词吗？', 'argon'); ?>')) {
		return;
	}
	let $btn = jQuery(this);
	let keyword = $btn.data('keyword');
	$btn.prop('disabled', true);
	jQuery.post(ajaxurl, {
		action: 'argon_delete_keyword',
		nonce: '<?php echo wp_create_nonce('argon_manage_unified_apis'); ?>',
		keyword: keyword
	}, function(response) {
		if (response.success) {
			alert(response.data.message);
			location.reload();
		} else {
			alert(response.data || '<?php _e('删除失败', 'argon'); ?>');
			$btn.prop('disabled', false);
		}
	}).fail(function() {
		alert('<?php _e('请求失败，请稍后重试', 'argon'); ?>');
		$btn.prop('disabled', false);
	});
});
// 清空自动学习规则
jQuery(document).on('click', '#argon-clear-learned-keywords', function() {
	if (!confirm('<?php _e('确定要清空所有自动学习规则吗？之后系统需要重新通过 AI 积累特征。', 'argon'); ?>')) {
		return;
	}
	let $btn = jQuery(this);
	$btn.prop('disabled', true).text('<?php _e('正在清空...', 'argon'); ?>');
	jQuery.post(ajaxurl, {
		action: 'argon_clear_learned_keywords',
		nonce: '<?php echo wp_create_nonce('argon_manage_unified_apis'); ?>'
	}, function(response) {
		if (response.success) {
			alert(response.data.message);
			location.reload();
		} else {
			alert(response.data);
			$btn.prop('disabled', false).text('<?php _e('清空学习规则', 'argon'); ?>');
		}
	});
});
				});
				</script>
</td></tr>
				
				<tr><th class="subtitle" colspan="2"><h3 id="ai-panic-mode"><?php _e('怕死模式', 'argon');?></h3></th></tr>
				<tr>
<th><label><?php _e('启用怕死模式', 'argon');?></label></th>
<td>
	<?php $argon_panic_mode_enable = get_option('argon_panic_mode_enable', 'false'); ?>
	<label>
		<input type="checkbox" name="argon_panic_mode_enable" value="true" <?php if ($argon_panic_mode_enable=='true'){echo 'checked';}?>/> 
		<?php _e('启用怕死模式（敏感时期加强审查）', 'argon');?>
	</label>
	<p class="description">
		<?php _e('开启后，在特定日期范围或特定标签的文章下，评论审核将更加严格。适用于全国两会、重大会议等敏感时期。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="panic-mode-settings" style="<?php echo ($argon_panic_mode_enable !== 'true') ? 'display:none;' : ''; ?>">
<th><label><?php _e('触发标签', 'argon');?></label></th>
<td>
	<textarea name="argon_panic_mode_tags" rows="4" cols="70" placeholder="<?php _e('每行一个标签', 'argon');?>"><?php echo esc_textarea(get_option('argon_panic_mode_tags', '')); ?></textarea>
	<p class="description">
		<?php _e('包含这些标签的文章会自动触发怕死模式。每行一个标签。', 'argon');?><br/>
		<?php _e('示例：政治、时政、社会热点等', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="panic-mode-settings" style="<?php echo ($argon_panic_mode_enable !== 'true') ? 'display:none;' : ''; ?>">
<th><label><?php _e('敏感日期范围', 'argon');?></label></th>
<td>
	<input type="hidden" name="argon_panic_mode_date_ranges_present" value="true" data-argon-internal>
	<div id="panic-mode-date-ranges" data-argon-dynamic-container>
		<?php 
		$date_ranges = get_option('argon_panic_mode_date_ranges', '[]');
		$date_ranges = json_decode($date_ranges, true);
		if (!is_array($date_ranges)) $date_ranges = array();
		?>
		<?php foreach ($date_ranges as $index => $range): ?>
		<div class="panic-date-range-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
			<input type="text" name="panic_date_name[]" value="<?php echo esc_attr($range['name']); ?>" placeholder="<?php _e('名称（如：全国两会）', 'argon');?>" style="width: 200px;">
			<input type="date" name="panic_date_start[]" value="<?php echo esc_attr($range['start']); ?>" style="width: 140px;">
			<span><?php _e('至', 'argon');?></span>
			<input type="date" name="panic_date_end[]" value="<?php echo esc_attr($range['end']); ?>" style="width: 140px;">
			<button type="button" class="button remove-panic-date-range" style="color: #d63638;">×</button>
		</div>
		<?php endforeach; ?>
	</div>
	<div style="display: flex; gap: 10px; align-items: center; margin-top: 5px;">
		<button type="button" class="button" id="add-panic-date-range"><?php _e('添加日期范围', 'argon');?></button>
		<select id="panic-date-preset" style="width: 200px;">
			<option value=""><?php _e('快速添加预置...', 'argon');?></option>
			<option value="lianghui"><?php _e('全国两会 (3/5-3/15)', 'argon');?></option>
			<option value="liusi"><?php _e('六四纪念 (6/3-6/5)', 'argon');?></option>
			<option value="guoqing"><?php _e('国庆节 (9/30-10/7)', 'argon');?></option>
			<option value="jiandang"><?php _e('建党节 (6/30-7/2)', 'argon');?></option>
			<option value="xinhai"><?php _e('辛亥革命纪念 (10/9-10/11)', 'argon');?></option>
			<option value="wusi"><?php _e('五四青年节 (5/3-5/5)', 'argon');?></option>
		</select>
	</div>
	<p class="description">
		<?php _e('在这些日期范围内，所有文章的评论都会触发怕死模式。', 'argon');?><br/>
		<?php _e('可以手动添加或从预置选项快速添加常见敏感日期。', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="panic-mode-settings" style="<?php echo ($argon_panic_mode_enable !== 'true') ? 'display:none;' : ''; ?>">
<th><label><?php _e('审查强度', 'argon');?></label></th>
<td>
	<select name="argon_panic_mode_intensity">
		<?php $argon_panic_mode_intensity = get_option('argon_panic_mode_intensity', 'strict'); ?>
		<option value="moderate" <?php if ($argon_panic_mode_intensity=='moderate'){echo 'selected';} ?>><?php _e('加强模式', 'argon');?></option>
		<option value="strict" <?php if ($argon_panic_mode_intensity=='strict'){echo 'selected';} ?>><?php _e('严格模式', 'argon');?></option>
		<option value="extreme" <?php if ($argon_panic_mode_intensity=='extreme'){echo 'selected';} ?>><?php _e('极端模式', 'argon');?></option>
	</select>
	<p class="description">
		<strong><?php _e('加强模式', 'argon');?>：</strong><?php _e('抽查升级为必查，置信度阈值降低 10%', 'argon');?><br/>
		<strong><?php _e('严格模式', 'argon');?>：</strong><?php _e('全量检测，置信度阈值降低 20%，使用专项政治内容审查', 'argon');?><br/>
		<strong><?php _e('极端模式', 'argon');?>：</strong><?php _e('全量检测，置信度阈值降低 30%，所有评论需人工审核', 'argon');?>
	</p>
</td>
				</tr>
				<tr class="panic-mode-settings" style="<?php echo ($argon_panic_mode_enable !== 'true') ? 'display:none;' : ''; ?>">
<th><label><?php _e('专项审查方向', 'argon');?></label></th>
<td>
	<?php $argon_panic_mode_focus = get_option('argon_panic_mode_focus', array('political')); ?>
	<?php $focus_options = array(
		'political' => __('政治敏感内容', 'argon'),
		'social' => __('社会热点话题', 'argon'),
		'media' => __('媒体舆论导向', 'argon'),
		'all' => __('全部方向', 'argon')
	); ?>
	<?php foreach ($focus_options as $key => $label): ?>
	<label style="display: inline-block; margin-right: 20px;">
		<input type="checkbox" name="argon_panic_mode_focus[]" value="<?php echo esc_attr($key); ?>" <?php if (in_array($key, $argon_panic_mode_focus)){echo 'checked';}?>/> 
		<?php echo esc_html($label); ?>
	</label>
	<?php endforeach; ?>
	<p class="description">
		<?php _e('选择怕死模式专项审查的重点方向。AI 会针对选定方向进行更精准的识别。', 'argon');?>
	</p>
</td>
				</tr>
				<script>
				jQuery(document).ready(function($) {
// 怕死模式开关
jQuery('input[name="argon_panic_mode_enable"]').on('change', function() {
	if (jQuery(this).is(':checked')) {
		jQuery('.panic-mode-settings').slideDown();
	} else {
		jQuery('.panic-mode-settings').slideUp();
	}
});
// 添加日期范围
jQuery('#add-panic-date-range').on('click', function() {
	var html = '<div class="panic-date-range-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">' +
		'<input type="text" name="panic_date_name[]" placeholder="<?php _e('名称（如：全国两会）', 'argon');?>" style="width: 200px;">' +
		'<input type="date" name="panic_date_start[]" style="width: 140px;">' +
		'<span><?php _e('至', 'argon');?></span>' +
		'<input type="date" name="panic_date_end[]" style="width: 140px;">' +
		'<button type="button" class="button remove-panic-date-range" style="color: #d63638;">×</button>' +
		'</div>';
	jQuery('#panic-mode-date-ranges').append(html);
	jQuery(this).closest('tr').find('input[name="argon_panic_mode_date_ranges_present"]').trigger('change');
});
// 预置日期选择
jQuery('#panic-date-preset').on('change', function() {
	var preset = jQuery(this).val();
	if (!preset) return;
	var year = new Date().getFullYear();
	var presets = {
		'lianghui': {name: '<?php _e('全国两会', 'argon');?>', start: year + '-03-05', end: year + '-03-15'},
		'liusi': {name: '<?php _e('六四纪念', 'argon');?>', start: year + '-06-03', end: year + '-06-05'},
		'guoqing': {name: '<?php _e('国庆节', 'argon');?>', start: year + '-09-30', end: year + '-10-07'},
		'jiandang': {name: '<?php _e('建党节', 'argon');?>', start: year + '-06-30', end: year + '-07-02'},
		'xinhai': {name: '<?php _e('辛亥革命纪念', 'argon');?>', start: year + '-10-09', end: year + '-10-11'},
		'wusi': {name: '<?php _e('五四青年节', 'argon');?>', start: year + '-05-03', end: year + '-05-05'}
	};
	if (presets[preset]) {
		var p = presets[preset];
		var html = '<div class="panic-date-range-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">' +
			'<input type="text" name="panic_date_name[]" value="' + p.name + '" style="width: 200px;">' +
			'<input type="date" name="panic_date_start[]" value="' + p.start + '" style="width: 140px;">' +
			'<span><?php _e('至', 'argon');?></span>' +
			'<input type="date" name="panic_date_end[]" value="' + p.end + '" style="width: 140px;">' +
			'<button type="button" class="button remove-panic-date-range" style="color: #d63638;">×</button>' +
			'</div>';
		jQuery('#panic-mode-date-ranges').append(html);
		jQuery(this).closest('tr').find('input[name="argon_panic_mode_date_ranges_present"]').trigger('change');
	}
	jQuery(this).val('');
});
// 删除日期范围
jQuery(document).on('click', '.remove-panic-date-range', function() {
	var $row = jQuery(this).closest('tr');
	jQuery(this).closest('.panic-date-range-item').remove();
	$row.find('input[name="argon_panic_mode_date_ranges_present"]').trigger('change');
});
				});
				</script>
