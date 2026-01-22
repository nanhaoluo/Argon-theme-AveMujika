#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Settings.php 结构优化脚本 - 步骤2
完成以下任务:
1. 在外观样式中添加动画效果子分类
2. 删除杂项中重复的动画效果设置
"""

def main():
    # 读取文件
    with open('settings.php', 'r', encoding='utf-8') as f:
        content = f.read()
    
    print("开始优化 settings.php (步骤2: 动画效果重组)...")
    print(f"原始文件: {len(content)} 字符, {content.count(chr(10))} 行")
    
    # 1. 在外观样式的字体设置后添加动画效果子分类
    old_font_section = '''					</tr>

					<tr><th class="subtitle"><h3 id="subsection-font"><?php _e('字体', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('默认字体', 'argon');?></label></th>

						<td>

							<div class="radio-h">

								<?php $argon_font = (get_option('argon_font') == '' ? 'sans-serif' : get_option('argon_font')); ?>

								<label>

									<input name="argon_font" type="radio" value="sans-serif" <?php if ($argon_font=='sans-serif'){echo 'checked';} ?>>

									Sans Serif

								</label>

								<label>

									<input name="argon_font" type="radio" value="serif" <?php if ($argon_font=='serif'){echo 'checked';} ?>>

									Serif

								</label>

							</div>

							<p class="description"><?php _e('默认使用无衬线字体/衬线字体。', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 3. 页面布局 ========== -->'''
    
    new_font_with_animation = '''					</tr>

					<tr><th class="subtitle"><h3 id="subsection-font"><?php _e('字体', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('默认字体', 'argon');?></label></th>

						<td>

							<div class="radio-h">

								<?php $argon_font = (get_option('argon_font') == '' ? 'sans-serif' : get_option('argon_font')); ?>

								<label>

									<input name="argon_font" type="radio" value="sans-serif" <?php if ($argon_font=='sans-serif'){echo 'checked';} ?>>

									Sans Serif

								</label>

								<label>

									<input name="argon_font" type="radio" value="serif" <?php if ($argon_font=='serif'){echo 'checked';} ?>>

									Serif

								</label>

							</div>

							<p class="description"><?php _e('默认使用无衬线字体/衬线字体。', 'argon');?></p>

						</td>

					</tr>

					<tr><th class="subtitle"><h3 id="subsection-animation"><?php _e('动画效果', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('是否启用平滑滚动', 'argon');?></label></th>

						<td>

							<select name="argon_enable_smoothscroll_type">

								<?php $enable_smoothscroll_type = get_option('argon_enable_smoothscroll_type'); ?>

								<option value="1" <?php if ($enable_smoothscroll_type=='1'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 1 (平滑) (推荐)', 'argon');?></option>

								<option value="1_pulse" <?php if ($enable_smoothscroll_type=='1_pulse'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 1 (脉冲式滚动) (仿 Edge) (推荐)', 'argon');?></option>

								<option value="2" <?php if ($enable_smoothscroll_type=='2'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 2 (较稳)', 'argon');?></option>

								<option value="3" <?php if ($enable_smoothscroll_type=='3'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 3', 'argon');?></option>

								<option value="disabled" <?php if ($enable_smoothscroll_type=='disabled'){echo 'selected';} ?>><?php _e('不使用平滑滚动', 'argon');?></option>

							</select>

							<p class="description"><?php _e('能增强浏览体验，但可能出现一些小问题，如果有问题请切换方案或关闭平滑滚动', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否启用进入文章动画', 'argon');?></label></th>

						<td>

							<select name="argon_enable_into_article_animation">

								<?php $argon_enable_into_article_animation = get_option('argon_enable_into_article_animation'); ?>

								<option value="false" <?php if ($argon_enable_into_article_animation=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_into_article_animation=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('从首页或分类目录进入文章时，使用平滑过渡（可能影响加载文章时的性能）', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('禁用 Pjax 加载后的页面滚动动画', 'argon');?></label></th>

						<td>

							<select name="argon_disable_pjax_animation">

								<?php $argon_disable_pjax_animation = get_option('argon_disable_pjax_animation'); ?>

								<option value="false" <?php if ($argon_disable_pjax_animation=='false'){echo 'selected';} ?>><?php _e('不禁用', 'argon');?></option>

								<option value="true" <?php if ($argon_disable_pjax_animation=='true'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('Pjax 替换页面内容后会平滑滚动到页面顶部，如果你不喜欢，可以禁用这个选项', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 3. 页面布局 ========== -->'''
    
    if old_font_section in content:
        content = content.replace(old_font_section, new_font_with_animation)
        print("✓ 在外观样式中添加了动画效果子分类")
    else:
        print("✗ 未找到字体设置部分")
    
    # 2. 删除杂项中重复的动画效果设置
    old_misc_with_animation = '''					<tr><th class="subtitle"><h3 id="subsection-misc"><?php _e('杂项', 'argon');?></h3></th></tr>

					<tr>

						<th><label><?php _e('是否启用平滑滚动', 'argon');?></label></th>

						<td>

							<select name="argon_enable_smoothscroll_type">

								<?php $enable_smoothscroll_type = get_option('argon_enable_smoothscroll_type'); ?>

								<option value="1" <?php if ($enable_smoothscroll_type=='1'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 1 (平滑) (推荐)', 'argon');?></option>

								<option value="1_pulse" <?php if ($enable_smoothscroll_type=='1_pulse'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 1 (脉冲式滚动) (仿 Edge) (推荐)', 'argon');?></option>

								<option value="2" <?php if ($enable_smoothscroll_type=='2'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 2 (较稳)', 'argon');?></option>

								<option value="3" <?php if ($enable_smoothscroll_type=='3'){echo 'selected';} ?>><?php _e('使用平滑滚动方案 3', 'argon');?></option>

								<option value="disabled" <?php if ($enable_smoothscroll_type=='disabled'){echo 'selected';} ?>><?php _e('不使用平滑滚动', 'argon');?></option>

							</select>

							<p class="description"><?php _e('能增强浏览体验，但可能出现一些小问题，如果有问题请切换方案或关闭平滑滚动', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('是否启用进入文章动画', 'argon');?></label></th>

						<td>

							<select name="argon_enable_into_article_animation">

								<?php $argon_enable_into_article_animation = get_option('argon_enable_into_article_animation'); ?>

								<option value="false" <?php if ($argon_enable_into_article_animation=='false'){echo 'selected';} ?>><?php _e('不启用', 'argon');?></option>

								<option value="true" <?php if ($argon_enable_into_article_animation=='true'){echo 'selected';} ?>><?php _e('启用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('从首页或分类目录进入文章时，使用平滑过渡（可能影响加载文章时的性能）', 'argon');?></p>

						</td>

					</tr>

					<tr>

						<th><label><?php _e('禁用 Pjax 加载后的页面滚动动画', 'argon');?></label></th>

						<td>

							<select name="argon_disable_pjax_animation">

								<?php $argon_disable_pjax_animation = get_option('argon_disable_pjax_animation'); ?>

								<option value="false" <?php if ($argon_disable_pjax_animation=='false'){echo 'selected';} ?>><?php _e('不禁用', 'argon');?></option>

								<option value="true" <?php if ($argon_disable_pjax_animation=='true'){echo 'selected';} ?>><?php _e('禁用', 'argon');?></option>

							</select>

							<p class="description"><?php _e('Pjax 替换页面内容后会平滑滚动到页面顶部，如果你不喜欢，可以禁用这个选项', 'argon');?></p>

						</td>

					</tr>

					<!-- ========== 14. 评论设置 ========== -->'''
    
    new_misc_without_animation = '''					<tr><th class="subtitle"><h3 id="subsection-misc"><?php _e('杂项', 'argon');?></h3></th></tr>

					<!-- ========== 15. 评论设置 ========== -->'''
    
    if old_misc_with_animation in content:
        content = content.replace(old_misc_with_animation, new_misc_without_animation)
        print("✓ 删除了杂项中重复的动画效果设置")
    else:
        print("✗ 未找到杂项中的动画效果设置")
    
    # 保存文件
    with open('settings.php', 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"\n优化完成! 新文件: {len(content)} 字符, {content.count(chr(10))} 行")
    print("请检查 settings.php 文件")

if __name__ == '__main__':
    main()
