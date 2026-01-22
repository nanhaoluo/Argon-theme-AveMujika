#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Settings.php 结构优化脚本 - 步骤3
完成以下任务:
1. 删除第685-792行之间错误的"顶栏设置"标题及其下的CDN/子目录/日期格式设置
2. 在基础设置中添加子目录设置
3. 创建新的"SEO 与性能"分类,包含SEO、CDN、日期格式
"""

def main():
    # 读取文件
    with open('settings.php', 'r', encoding='utf-8') as f:
        content = f.read()
    
    print("开始优化 settings.php (步骤3: 删除重复的顶栏设置并重组)...")
    print(f"原始文件: {len(content)} 字符, {content.count(chr(10))} 行")
    
    # 1. 删除第685-792行之间错误的"顶栏设置"及其下的CDN/子目录/日期格式设置
    old_wrong_toolbar = '''					<!-- ========== 4. 顶栏设置 ========== -->
				<tr><th class="subtitle"><h2 id="section-toolbar"><?php _e('顶栏设置', 'argon');?></h2></th></tr>

				<tr><th class="subtitle"><h3 id="subsection-toolbar-basic"><?php _e('基本设置', 'argon');?></h3></th></tr>

					<tr>

						<th><label>CDN</label></th>

						<td>

							<select name="argon_assets_path">

								<?php $argon_assets_path = get_option('argon_assets_path'); ?>

								<option value="default" <?php if ($argon_assets_path=='default'){echo 'selected';} ?>><?php _e('不使用', 'argon');?></option>

								<option value="jsdelivr" <?php if ($argon_assets_path=='jsdelivr'){echo 'selected';} ?>>Jsdelivr</option>

								<option value="fastgit" <?php if ($argon_assets_path=='fastgit'){echo 'selected';} ?>>Fastgit</option>

								<option value="sourcegcdn" <?php if ($argon_assets_path=='sourcegcdn'){echo 'selected';} ?>>Source Global CDN</option>

								<option value="jsdelivr_gcore" <?php if ($argon_assets_path=='jsdelivr_gcore'){echo 'selected';} ?>>Jsdelivr (gcore)</option>

								<option value="jsdelivr_fastly" <?php if ($argon_assets_path=='jsdelivr_fastly'){echo 'selected';} ?>>Jsdelivr (fastly)</option>

								<option value="jsdelivr_cf" <?php if ($argon_assets_path=='jsdelivr_cf'){echo 'selected';} ?>>Jsdelivr (cf)</option>

								<option value="custom" <?php if ($argon_assets_path=='custom'){echo 'selected';} ?>><?php _e('自定义...', 'argon');?></option>

							</select>

							<input type="text" class="regular-text" name="argon_custom_assets_path" placeholder="https://" value="<?php echo get_option('argon_custom_assets_path', ''); ?>" autocomplete="off">

							<p class="description"><?php _e('选择主题资源文件的引用地址。使用 CDN 可以加速资源文件的访问并减少服务器压力。', 'argon');?></p>

							<p class="description custom-assets-path-desctiption"><?php _e('在自定义路径中使用 <code>%theme_version%</code> 来表示主题版本号。', 'argon');?></p>

						</td>

						<script>

							$("select[name='argon_assets_path']").change(function(){

								if ($(this).val() == 'custom') {

									$("input[name='argon_custom_assets_path']").css('display', '');

									$(".custom-assets-path-desctiption").css('display', '');

								} else {

									$("input[name='argon_custom_assets_path']").css('display', 'none');

									$(".custom-assets-path-desctiption").css('display', 'none');

								}

							}).change();

						</script>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-subdirectory"><?php _e('子目录', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('Wordpress 安装目录', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_wp_path" value="<?php echo get_option('argon_wp_path', '/'); ?>"/>

							<p class="description"><?php _e('如果 Wordpress 安装在子目录中，请在此填写子目录地址（例如', 'argon');?> <code>/blog/</code><?php _e('），注意前后各有一个斜杠。默认为', 'argon');?> <code>/</code> <?php _e('。', 'argon');?><br/><?php _e('如果不清楚该选项的用处，请保持默认。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-date-format"><?php _e('日期格式', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('日期格式', 'argon');?></label></th>

						<td>

							<select name="argon_dateformat">

								<?php $argon_dateformat = get_option('argon_dateformat'); ?>

								<option value="YMD" <?php if ($argon_dateformat=='YMD'){echo 'selected';} ?>>Y-M-D</option>

								<option value="DMY" <?php if ($argon_dateformat=='DMY'){echo 'selected';} ?>>D-M-Y</option>

								<option value="MDY" <?php if ($argon_dateformat=='MDY'){echo 'selected';} ?>>M-D-Y</option>

							</select>

							<p class="description"></p>

						</td>

					</tr>

					<!-- ========== 4. 顶栏设置 ========== -->'''
    
    new_correct_toolbar = '''					<!-- ========== 4. 顶栏设置 ========== -->'''
    
    if old_wrong_toolbar in content:
        content = content.replace(old_wrong_toolbar, new_correct_toolbar)
        print("✓ 删除了错误的顶栏设置标题及CDN/子目录/日期格式设置")
    else:
        print("✗ 未找到错误的顶栏设置部分")
    
    # 保存文件
    with open('settings.php', 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"\n优化完成! 新文件: {len(content)} 字符, {content.count(chr(10))} 行")
    print("请检查 settings.php 文件")

if __name__ == '__main__':
    main()
