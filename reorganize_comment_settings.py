#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
将评论相关设置从验证码与安全移到评论设置分类
"""

def find_line_with_text(lines, text):
	"""查找包含指定文本的行号"""
	for i, line in enumerate(lines):
		if text in line:
			return i
	return -1

def reorganize():
	with open('settings.php', 'r', encoding='utf-8') as f:
		lines = f.readlines()
	
	print(f"原始文件: {len(lines)} 行")
	
	# 1. 找到关键位置
	comment_section_start = find_line_with_text(lines, '<!-- ========== 16. 评论设置 ==========')
	comment_submit_end = -1
	
	# 找到"发送评论"子分类的结束位置
	for i in range(comment_section_start, len(lines)):
		if '<!-- ========== 16. 验证码与安全 ==========' in lines[i]:
			comment_submit_end = i
			break
	
	# 找到"返回评论系统"的位置（评论功能开始）
	comment_features_start = find_line_with_text(lines, '<!-- ========== 返回评论系统 ==========')
	
	# 找到文件结束位置（保存按钮之前）
	file_end = find_line_with_text(lines, '<input type="submit"')
	
	print(f"评论设置开始: {comment_section_start + 1}")
	print(f"发送评论结束: {comment_submit_end + 1}")
	print(f"评论功能开始: {comment_features_start + 1}")
	print(f"文件结束: {file_end + 1}")
	
	# 2. 提取评论功能、AI垃圾评论识别、评论区外观部分
	comment_features_content = lines[comment_features_start:file_end]
	
	# 3. 删除"返回评论系统"注释，改为正常的子分类标题
	new_comment_features = []
	for line in comment_features_content:
		if '<!-- ========== 返回评论系统 ==========' in line:
			continue  # 跳过这个注释
		new_comment_features.append(line)
	
	# 4. 重新组织文件
	new_lines = []
	
	# 添加评论设置之前的所有内容
	new_lines.extend(lines[:comment_submit_end])
	
	# 添加评论功能、AI垃圾评论识别、评论区外观
	new_lines.extend(new_comment_features)
	
	# 添加验证码与安全及之后的内容
	new_lines.extend(lines[comment_submit_end:comment_features_start])
	
	# 添加文件结束部分
	new_lines.extend(lines[file_end:])
	
	print(f"重组后: {len(new_lines)} 行")
	
	# 写入文件
	with open('settings.php', 'w', encoding='utf-8') as f:
		f.writelines(new_lines)
	
	print("✓ 完成：将评论功能、AI垃圾评论识别、评论区外观移到评论设置分类")

if __name__ == '__main__':
	reorganize()
