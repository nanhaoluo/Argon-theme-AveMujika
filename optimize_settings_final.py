#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Settings.php 结构优化脚本 - 最终步骤
完成以下任务:
1. 在基础设置的夜间模式后添加子目录设置
2. 将第1541行的"高级设置"改为"SEO与性能",并添加CDN和日期格式设置
3. 重新编号所有一级分类(1-16)
"""

def main():
    # 读取文件
    with open('settings.php', 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    print("开始优化 settings.php (最终步骤: 添加子目录和SEO与性能分类)...")
    print(f"原始文件: {len(lines)} 行")
    
    content = ''.join(lines)
    
    # 1. 在基础设置的夜间模式后添加子目录设置
    old_basic_end = '''					</tr>

					<!-- ========== 2. 外观样式 ========== -->'''
    
    new_basic_with_subdirectory = '''					</tr>

					<tr><th class="subtitle"><h3 id="subsection-subdirectory"><?php _e('子目录', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('Wordpress 安装目录', 'argon');?></label></th>

						<td>

							<input type="text" class="regular-text" name="argon_wp_path" value="<?php echo get_option('argon_wp_path', '/'); ?>"/>

							<p class="description"><?php _e('如果 Wordpress 安装在子目录中，请在此填写子目录地址（例如', 'argon');?> <code>/blog/</code><?php _e('），注意前后各有一个斜杠。默认为', 'argon');?> <code>/</code> <?php _e('。', 'argon');?><br/><?php _e('如果不清楚该选项的用处，请保持默认。', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 2. 外观样式 ========== -->'''
    
    if old_basic_end in content:
        content = content.replace(old_basic_end, new_basic_with_subdirectory)
        print("✓ 在基础设置中添加了子目录设置")
    else:
        print("✗ 未找到基础设置结束位置")
    
    # 2. 将第1541行的"高级设置"改为"SEO与性能",并添加CDN和日期格式
    old_advanced_seo = '''					<!-- ========== 9. 高级设置 ========== -->
					<tr><th class="subtitle"><h2 id="section-advanced"><?php _e('高级设置', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-seo">SEO</h3></th></tr>'''
    
    new_seo_performance = '''					<!-- ========== 9. SEO 与性能 ========== -->
					<tr><th class="subtitle"><h2 id="section-seo-performance"><?php _e('SEO 与性能', 'argon');?></h2></th></tr>

					<tr><th class="subtitle"><h3 id="subsection-seo">SEO</h3></th></tr>'''
    
    if old_advanced_seo in content:
        content = content.replace(old_advanced_seo, new_seo_performance)
        print("✓ 将高级设置改为SEO与性能")
    else:
        print("✗ 未找到第一个高级设置标题")
    
    # 3. 在SEO设置后添加CDN和日期格式设置
    # 先找到SEO设置的结束位置(在"文章显示"之前)
    old_seo_end = '''					</tr>

					<!-- ========== 10. 文章显示 ========== -->'''
    
    new_seo_with_cdn_date = '''					</tr>

					<tr><th class="subtitle"><h3 id="subsection-cdn"><?php _e('CDN 加速', 'argon');?></h3></th></tr>

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

					<!-- ========== 10. 文章显示 ========== -->'''
    
    if old_seo_end in content:
        content = content.replace(old_seo_end, new_seo_with_cdn_date)
        print("✓ 在SEO与性能中添加了CDN加速和日期格式设置")
    else:
        print("✗ 未找到SEO设置结束位置")
    
    # 保存文件
    with open('settings.php', 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"\n优化完成! 新文件: {content.count(chr(10))} 行")
    print("请检查 settings.php 文件")

if __name__ == '__main__':
    main()
