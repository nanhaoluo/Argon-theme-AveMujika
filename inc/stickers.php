<?php

/**
 * 表情图片清单与目录加载器。
 *
 * 清单只维护“分类 -> 文件夹”，图片由目录自动发现。这里刻意排除 SVG，
 * 避免把可执行标记当作评论图片输出。
 */

function argon_sticker_allowed_extensions() {
	return array('avif', 'gif', 'jpeg', 'jpg', 'png', 'webp');
}

function argon_sticker_is_animated_extension($extension) {
	return in_array(strtolower($extension), array('gif', 'webp', 'avif'), true);
}

function argon_sticker_directory_is_valid($directory) {
	return is_string($directory)
		&& preg_match('/^[a-z0-9][a-z0-9._-]{0,63}$/i', $directory) === 1
		&& $directory !== '.'
		&& $directory !== '..';
}

function argon_sticker_path_is_inside($path, $root) {
	$path = rtrim(str_replace('\\', '/', $path), '/') . '/';
	$root = rtrim(str_replace('\\', '/', $root), '/') . '/';
	return strpos($path, $root) === 0;
}

function argon_read_sticker_manifest($manifest_path) {
	if (!is_file($manifest_path)) {
		return array();
	}

	$size = @filesize($manifest_path);
	if ($size === false || $size > 65536) {
		return array();
	}

	$content = @file_get_contents($manifest_path);
	if ($content === false) {
		return array();
	}

	$manifest = json_decode($content, true);
	if (!is_array($manifest) || !isset($manifest['categories']) || !is_array($manifest['categories'])) {
		return array();
	}

	$default_filename_as_title = isset($manifest['use_filename_as_title'])
		&& $manifest['use_filename_as_title'] === true;
	$categories = array();
	$seen_directories = array();
	foreach ($manifest['categories'] as $manifest_index => $category) {
		if (!is_array($category) || empty($category['name']) || empty($category['directory'])) {
			continue;
		}

		$name = trim((string) $category['name']);
		$directory = trim((string) $category['directory']);
		if ($name === '' || !argon_sticker_directory_is_valid($directory) || isset($seen_directories[$directory])) {
			continue;
		}

		$normalized = array(
			'name' => $name,
			'directory' => $directory,
			'order' => isset($category['order']) && is_numeric($category['order'])
				? intval($category['order'])
				: intval($manifest_index),
			'_manifest_index' => intval($manifest_index),
			'use_filename_as_title' => array_key_exists('use_filename_as_title', $category)
				? $category['use_filename_as_title'] === true
				: $default_filename_as_title,
		);
		if (!empty($category['description'])) {
			$normalized['description'] = trim((string) $category['description']);
		}
		if (!empty($category['source'])) {
			$normalized['source'] = trim((string) $category['source']);
		}

		$categories[] = $normalized;
		$seen_directories[$directory] = true;
	}

	usort($categories, function($left, $right) {
		if ($left['order'] === $right['order']) {
			return $left['_manifest_index'] <=> $right['_manifest_index'];
		}
		return $left['order'] <=> $right['order'];
	});
	foreach ($categories as &$category) unset($category['_manifest_index']);
	unset($category);

	return $categories;
}

function argon_sticker_legacy_code($directory, $filename) {
	$legacy_codes = array(
		'dinosaur' => array(
			'1.jpg' => 'dinosaur-shy',
			'2.jpg' => 'dinosaur-daze',
			'3.jpg' => 'dinosaur-sweat',
			'4.jpg' => 'dinosaur-proud',
			'5.jpg' => 'dinosaur-powerless',
			'6.jpg' => 'dinosaur-pouting',
			'7.jpg' => 'dinosaur-eating',
			'8.jpg' => 'dinosaur-ok',
			'9.jpg' => 'dinosaur-doubt',
			'10.jpg' => 'dinosaur-depressed',
			'11.jpg' => 'dinosaur-close-eyes',
			'12.jpg' => 'dinosaur-sleeping',
			'13.jpg' => 'dinosaur-puzzled',
			'14.jpg' => 'dinosaur-agree',
			'15.jpg' => 'dinosaur-crazy',
			'16.jpg' => 'dinosaur-angry',
		),
		'flower' => array(
			'1.jpg' => 'flower-flower',
			'2.jpg' => 'flower-grass',
			'3.jpg' => 'flower-leaf',
			'4.jpg' => 'flower-star',
			'5.jpg' => 'flower-sun',
			'6.jpg' => 'flower-moon',
			'7.jpg' => 'flower-water',
			'8.jpg' => 'flower-heihei',
			'9.jpg' => 'flower-lemon',
			'10.jpg' => 'flower-birthday',
			'11.jpg' => 'flower-sea',
			'12.jpg' => 'flower-vegetable',
			'13.jpg' => 'flower-tile',
			'14.jpg' => 'flower-utf',
		),
	);

	return isset($legacy_codes[$directory][$filename]) ? $legacy_codes[$directory][$filename] : '';
}

function argon_sticker_code($directory, $filename) {
	$legacy_code = argon_sticker_legacy_code($directory, $filename);
	if ($legacy_code !== '') {
		return $legacy_code;
	}

	$stem = pathinfo($filename, PATHINFO_FILENAME);
	$stem = preg_replace('/[^\p{L}\p{N}_.!~-]+/u', '-', $stem);
	$stem = trim((string) $stem, '-.');
	if ($stem === '') {
		$stem = substr(hash('sha256', $filename), 0, 12);
	}
	if (function_exists('mb_substr')) {
		$stem = mb_substr($stem, 0, 96, 'UTF-8');
	} else {
		$stem = substr($stem, 0, 96);
	}

	return $directory . '-' . $stem;
}

function argon_sticker_cache_key($categories, $stickers_root) {
	$assets_path = isset($GLOBALS['assets_path']) ? $GLOBALS['assets_path'] : '';
	$fingerprint = hash('sha256', json_encode($categories)) . ':' . $assets_path;
	foreach ($categories as $category) {
		$directory_path = $stickers_root . DIRECTORY_SEPARATOR . $category['directory'];
		$fingerprint .= '|' . $category['directory'] . ':' . (string) @filemtime($directory_path);
	}
	return 'argon_stickers_' . substr(hash('sha256', $fingerprint), 0, 32);
}

function argon_sticker_url($directory, $filename, $modified_time) {
	$assets_path = isset($GLOBALS['assets_path']) ? $GLOBALS['assets_path'] : '';
	return rtrim($assets_path, '/')
		. '/stickers/' . rawurlencode($directory)
		. '/' . rawurlencode($filename)
		. '?v=' . (int) $modified_time;
}

function argon_scan_sticker_group($category, $stickers_root) {
	$root_realpath = realpath($stickers_root);
	$directory_path = $stickers_root . DIRECTORY_SEPARATOR . $category['directory'];
	$directory_realpath = realpath($directory_path);
	if ($root_realpath === false || $directory_realpath === false || !is_dir($directory_realpath)) {
		return array();
	}
	if (!argon_sticker_path_is_inside($directory_realpath, $root_realpath)) {
		return array();
	}

	$files = @scandir($directory_realpath);
	if (!is_array($files)) {
		return array();
	}
	$files = array_values(array_filter($files, function($filename) use ($directory_realpath) {
		if ($filename === '' || $filename === '.' || $filename === '..') {
			return false;
		}
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (!in_array($extension, argon_sticker_allowed_extensions(), true)) {
			return false;
		}
		$file_realpath = realpath($directory_realpath . DIRECTORY_SEPARATOR . $filename);
		return $file_realpath !== false
			&& is_file($file_realpath)
			&& argon_sticker_path_is_inside($file_realpath, $directory_realpath);
	}));
	usort($files, 'strnatcasecmp');
	$list = array();
	$seen_codes = array();
	foreach ($files as $filename) {
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$code = argon_sticker_code($category['directory'], $filename);
		$code_key = function_exists('mb_strtolower') ? mb_strtolower($code, 'UTF-8') : strtolower($code);
		if (isset($seen_codes[$code_key])) {
			$code .= '-' . substr(hash('sha256', $filename), 0, 8);
			$code_key = function_exists('mb_strtolower') ? mb_strtolower($code, 'UTF-8') : strtolower($code);
		}
		$seen_codes[$code_key] = true;

		$file_path = $directory_realpath . DIRECTORY_SEPARATOR . $filename;
		$list[] = array(
			'type' => 'sticker',
			'code' => $code,
			'src' => argon_sticker_url($category['directory'], $filename, @filemtime($file_path)),
			'title' => !empty($category['use_filename_as_title']) ? pathinfo($filename, PATHINFO_FILENAME) : '',
			'animated' => argon_sticker_is_animated_extension($extension),
		);
	}

	return $list;
}

function argon_load_sticker_groups() {
	$stickers_root = get_template_directory() . '/stickers';
	$manifest_path = $stickers_root . '/manifest.json';
	$categories = argon_read_sticker_manifest($manifest_path);
	if (!$categories) {
		return array();
	}

	$cache_key = argon_sticker_cache_key($categories, $stickers_root);
	if (function_exists('get_transient')) {
		$cached_groups = get_transient($cache_key);
		if (is_array($cached_groups)) {
			return $cached_groups;
		}
	}

	$groups = array();
	foreach ($categories as $category) {
		$list = argon_scan_sticker_group($category, $stickers_root);
		if (!$list) {
			continue;
		}

		$group = array(
			'groupname' => $category['name'],
			'directory' => $category['directory'],
			'order' => $category['order'],
			'use_filename_as_title' => $category['use_filename_as_title'],
			'list' => $list,
		);
		if (isset($category['description'])) {
			$group['description'] = $category['description'];
		}
		if (isset($category['source'])) {
			$group['source'] = $category['source'];
		}
		$groups[] = $group;
	}

	if (function_exists('set_transient')) {
		$cache_duration = defined('HOUR_IN_SECONDS') ? HOUR_IN_SECONDS : 3600;
		set_transient($cache_key, $groups, $cache_duration);
	}
	return $groups;
}
