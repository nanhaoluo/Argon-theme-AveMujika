<?php
/**
 * 附件预览快照渲染器（服务端生成静态快照，可经 CDN 缓存）。
 *
 * 快照写入 uploads/argon-attachments/_preview-cache/{uuid}.{指纹}.html，
 * 指纹由源文件大小与修改时间决定，源文件变化后自动失效并重建。
 * docx/xlsx/pptx 本质是 zip + XML，用 ZipArchive + DOM 直接解析，无外部依赖；
 * md 复用主题自带 Parsedown（安全模式）；zip 存目录树片段，前台 modal 注入后交互。
 */

if (!defined('ABSPATH')) {
	exit;
}

define('ARGON_ATTACHMENT_PREVIEW_VERSION', 'v3');     // 快照版本号，升级快照结构时自然失效旧缓存
define('ARGON_ATTACHMENT_PREVIEW_DEPTH', 3);            // zip 目录树最大深度
define('ARGON_ATTACHMENT_PREVIEW_MAX_SOURCE', 30 * 1024 * 1024); // 超过 30MB 不生成快照

function argon_attachment_previewable_extensions() {
	return array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'md', 'zip', 'rar', '7z', 'tar', 'gz', 'tgz');
}

// 能实际生成/展示预览的扩展名（doc/ppt 为老二进制格式，无法解析）
function argon_attachment_preview_renderable($ext) {
	return in_array($ext, array('pdf', 'docx', 'xlsx', 'pptx', 'md', 'zip', 'rar', '7z', 'tar', 'gz', 'tgz'), true);
}

function argon_attachment_is_previewable($att) {
	if (!in_array($att['ext'], argon_attachment_previewable_extensions(), true)) {
		return false;
	}
	if (isset($att['size']) && intval($att['size']) > ARGON_ATTACHMENT_PREVIEW_MAX_SOURCE) {
		return false;
	}
	return true;
}

/* ---------------------------------------------------------------------------
 * 快照缓存
 * ------------------------------------------------------------------------- */

function argon_attachment_snapshot_dir() {
	$uploads = argon_attachments_uploads();
	if ($uploads === false) {
		return false;
	}
	$dir = trailingslashit($uploads['basedir']) . 'argon-attachments/_preview-cache';
	if (!is_dir($dir) && !argon_attachments_ensure_dir($dir)) {
		return false;
	}
	return $dir;
}

function argon_attachment_snapshot_url_base() {
	$uploads = argon_attachments_uploads();
	if ($uploads === false) {
		return false;
	}
	$baseurl = $uploads['baseurl'];
	if (function_exists('set_url_scheme')) {
		$baseurl = set_url_scheme($baseurl);
	}
	return trailingslashit($baseurl) . 'argon-attachments/_preview-cache';
}

// 指纹含版本号、源文件大小与 mtime：版本或源文件变了指纹就变，旧快照自然失效
function argon_attachment_snapshot_fingerprint($file_path) {
	if (!is_file($file_path)) {
		return false;
	}
	return substr(md5(ARGON_ATTACHMENT_PREVIEW_VERSION . '|' . $file_path . '|' . filesize($file_path) . '|' . filemtime($file_path)), 0, 12);
}

// 快照磁盘路径 → 公网静态 URL（CDN 可缓存）
function argon_attachment_snapshot_public_url($snapshot_path) {
	$dir = argon_attachment_snapshot_dir();
	$base = argon_attachment_snapshot_url_base();
	if ($dir === false || $base === false || strpos($snapshot_path, trailingslashit($dir)) !== 0) {
		return '';
	}
	$url = trailingslashit($base) . rawurlencode(basename($snapshot_path));
	if (function_exists('set_url_scheme')) {
		$url = set_url_scheme($url);
	}
	return $url;
}

/**
 * 获取快照的磁盘路径；不存在则调用 $generator 生成。
 * 返回 false 表示生成失败。并发下重复生成的代价可接受（最后一次写入胜出）。
 */
function argon_attachment_snapshot_get($uuid, $file_path, $generator, $force_recreate = false) {
	$dir = argon_attachment_snapshot_dir();
	$fingerprint = argon_attachment_snapshot_fingerprint($file_path);
	if ($dir === false || $fingerprint === false) {
		return false;
	}
	$target = trailingslashit($dir) . $uuid . '.' . $fingerprint . '.html';
	if (!$force_recreate && is_file($target)) {
		return $target;
	}
	$html = call_user_func($generator);
	if ($html === false) {
		return false;
	}
	// 重建前清掉该 uuid 的旧快照与旧媒体目录
	argon_attachment_snapshot_purge($uuid, $fingerprint);
	$tmp = $target . '.tmp-' . wp_rand(1000, 999999);
	if (@file_put_contents($tmp, $html, LOCK_EX) === false) {
		return false;
	}
	if (!@rename($tmp, $target)) {
		@unlink($tmp);
		return is_file($target) ? $target : false;
	}
	return $target;
}

// 清理某 uuid 的快照（$keep_fingerprint 非空时保留当前指纹那份）
function argon_attachment_snapshot_purge($uuid, $keep_fingerprint = '') {
	$dir = argon_attachment_snapshot_dir();
	if ($dir === false) {
		return;
	}
	$uuid = preg_replace('/[^0-9a-zA-Z\-]/', '', $uuid);
	foreach ((array) glob(trailingslashit($dir) . $uuid . '.*') as $path) {
		if ($keep_fingerprint !== '' && strpos(basename($path), $uuid . '.' . $keep_fingerprint) === 0) {
			continue;
		}
		if (is_dir($path)) {
			argon_attachments_remove_dir($path);
		} else {
			@unlink($path);
		}
	}
}

// 快照引用的媒体目录（docx/pptx 内嵌图片落盘处）
function argon_attachment_snapshot_media_dir($uuid, $fingerprint) {
	$dir = argon_attachment_snapshot_dir();
	if ($dir === false) {
		return false;
	}
	$media = trailingslashit($dir) . $uuid . '.' . $fingerprint;
	if (!wp_mkdir_p($media)) {
		return false;
	}
	return $media;
}

/* ---------------------------------------------------------------------------
 * 快照文档骨架：自包含 HTML + 内联样式，在 modal 的 iframe 中独立渲染
 * ------------------------------------------------------------------------- */

		function argon_attachment_snapshot_document($title, $body_html, $extra_class = '') {
			$css = '
				* { box-sizing: border-box; }
				:root {
					--bg: #ffffff;
					--text: #2c3e50;
					--text-muted: #8898aa;
					--border: #e2e8f0;
					--code-bg: #f6f8fa;
					--th-bg: #f8fafc;
				}
				html.darkmode,
				body.darkmode,
				:root.darkmode {
					--bg: #1e1e2e !important;
					--text: #e2e8f0 !important;
					--text-muted: #94a3b8 !important;
					--border: #334155 !important;
					--code-bg: #0f172a !important;
					--th-bg: #0f172a !important;
				}
				html.darkmode.amoled-dark,
				body.darkmode.amoled-dark,
				:root.darkmode.amoled-dark {
					--bg: #000000 !important;
					--text: #f1f5f9 !important;
					--text-muted: #94a3b8 !important;
					--border: #222222 !important;
					--code-bg: #111111 !important;
					--th-bg: #111111 !important;
				}
				@media (prefers-color-scheme: dark) {
					:root:not(.lightmode) {
						--bg: #1e1e2e;
						--text: #e2e8f0;
						--text-muted: #94a3b8;
						--border: #334155;
						--code-bg: #0f172a;
						--th-bg: #0f172a;
					}
				}
				html, body {
					min-height: 100%;
					background: var(--bg) !important;
					color: var(--text) !important;
				}
				body {
					margin: 0;
					padding: 28px 34px;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "PingFang SC", "Microsoft YaHei", sans-serif;
					font-size: 15px;
					line-height: 1.75;
					word-wrap: break-word;
				}
				h1, h2, h3, h4, h5, h6 {
					line-height: 1.4;
					margin: 1.1em 0 0.5em;
					color: var(--text) !important;
				}
				h1 { font-size: 1.7em; } h2 { font-size: 1.45em; } h3 { font-size: 1.2em; } h4 { font-size: 1.05em; }
				p, div, span, li, td, th {
					color: var(--text) !important;
				}
				p { margin: 0.55em 0; }
				img { max-width: 100%; height: auto; border-radius: 4px; }
				table { border-collapse: collapse; margin: 0.8em 0; max-width: 100%; color: var(--text); }
				td, th { border: 1px solid var(--border); padding: 7px 14px; font-size: 0.93em; text-align: left; }
				th { background: var(--th-bg); font-weight: 600; }
				pre { background: var(--code-bg); padding: 12px 16px; border-radius: 6px; overflow: auto; font-size: 0.88em; border: 1px solid var(--border); }
				code { background: var(--code-bg); padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
				pre code { background: none; padding: 0; border: 0; }
				blockquote { margin: 0.8em 0; padding: 6px 16px; border-left: 4px solid var(--border); color: var(--text-muted) !important; }
				a { color: #5e72e4; text-decoration: none; transition: color 0.15s ease; }
				a:hover { text-decoration: underline; }
				html.darkmode a, body.darkmode a { color: #8a98eb !important; }
				html.darkmode a:hover, body.darkmode a:hover { color: #a5b4fc !important; }
				hr { border: 0; border-top: 1px solid var(--border); margin: 1.5em 0; }
				ul, ol { padding-left: 1.6em; }
				.argon-snap-note { color: var(--text-muted) !important; font-size: 0.85em; border-top: 1px dashed var(--border); margin-top: 20px; padding-top: 10px; }
				.argon-slide { border: 1px solid var(--border); border-radius: 8px; padding: 18px 22px; margin: 0 0 16px; background: rgba(255, 255, 255, 0.02); }
				html.darkmode .argon-slide { background: rgba(255, 255, 255, 0.04); }
				.argon-slide-no { color: var(--text-muted) !important; font-size: 0.8em; margin-bottom: 6px; }
				.argon-sheet-title { font-weight: 600; margin: 20px 0 8px; color: var(--text) !important; }
				.argon-sheet-table-wrap { overflow-x: auto; }
				::-webkit-scrollbar { width: 6px; height: 6px; }
				::-webkit-scrollbar-track { background: transparent; }
				::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.18); border-radius: 3px; }
				html.darkmode ::-webkit-scrollbar-thumb, body.darkmode ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.18); }
			';
			$js = '<script>
				function syncTheme(e) {
					try {
						var isDark = false;
						var isAmoled = false;
						if (e && e.data && typeof e.data.isDark === "boolean") {
							isDark = e.data.isDark;
							isAmoled = !!e.data.isAmoled;
						} else if (window.parent && window.parent.document && window.parent.document.documentElement) {
							var parentEl = window.parent.document.documentElement;
							isDark = parentEl.classList.contains("darkmode");
							isAmoled = parentEl.classList.contains("amoled-dark");
						} else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
							isDark = true;
						}
						if (isDark) {
							document.documentElement.classList.add("darkmode");
							document.body.classList.add("darkmode");
							if (isAmoled) {
								document.documentElement.classList.add("amoled-dark");
								document.body.classList.add("amoled-dark");
							} else {
								document.documentElement.classList.remove("amoled-dark");
								document.body.classList.remove("amoled-dark");
							}
						} else {
							document.documentElement.classList.remove("darkmode", "amoled-dark");
							document.body.classList.remove("darkmode", "amoled-dark");
						}
					} catch (err) {}
				}
				syncTheme();
				window.addEventListener("DOMContentLoaded", syncTheme);
				window.addEventListener("message", function(e) {
					if (e && e.data && (e.data.type === "argon_theme_change" || e.data.type === "argon:theme-switched")) {
						syncTheme(e);
					}
				});
			</script>';
			return '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"/>'
				. '<meta name="viewport" content="width=device-width, initial-scale=1"/>'
				. '<meta name="robots" content="noindex,nofollow"/>'
				. '<title>' . esc_html($title) . '</title>'
				. '<style>' . $css . '</style>'
				. $js
				. '</head><body class="' . esc_attr($extra_class) . '">'
				. $body_html
				. '</body></html>';
		}

/* ---------------------------------------------------------------------------
 * zip：目录树片段（最深 3 层，更深层折叠为计数；前台 modal 注入后点击展开）
 * ------------------------------------------------------------------------- */

// 字符集安全转换（兼容 GB18030/GBK/CP936/Big5/CP437 的 Windows 压缩包，避免非 UTF-8 导致 json_encode 失败或乱码）
function argon_attachment_normalize_utf8($str) {
	if ($str === null || $str === '') {
		return '';
	}
	if (function_exists('mb_check_encoding') && mb_check_encoding($str, 'UTF-8')) {
		return $str;
	}
	if (function_exists('mb_convert_encoding')) {
		$converted = @mb_convert_encoding($str, 'UTF-8', array('GB18030', 'CP936', 'BIG5', 'ISO-8859-1', 'Windows-1252'));
		if ($converted !== false && $converted !== '') {
			return $converted;
		}
	}
	if (function_exists('iconv')) {
		$converted = @iconv('GB18030//IGNORE', 'UTF-8', $str);
		if ($converted !== false && $converted !== '') {
			return $converted;
		}
		$converted = @iconv('CP437//IGNORE', 'UTF-8', $str);
		if ($converted !== false && $converted !== '') {
			return $converted;
		}
	}
	return function_exists('utf8_encode') ? @utf8_encode($str) : $str;
}

// 纯函数：条目列表 → 树（便于测试）。条目为 array('name' => 路径, 'size' => 字节)
function argon_attachment_zip_build_tree($entries) {
	$tree = array('dirs' => array(), 'files' => array(), 'overflow' => 0);
	foreach ((array) $entries as $entry) {
		$raw = isset($entry['name']) ? argon_attachment_normalize_utf8((string) $entry['name']) : '';
		$size = isset($entry['size']) ? intval($entry['size']) : 0;
		$is_dir = substr($raw, -1) === '/';
		$path = trim(str_replace('\\', '/', $raw), '/');
		if ($path === '' || strpos($path, '__MACOSX') === 0 || substr($path, -9) === '.DS_Store') {
			continue;
		}
		$parts = explode('/', $path);
		if ($is_dir) {
			array_pop($parts);
		}
		if (empty($parts)) {
			continue;
		}
		if (count($parts) > ARGON_ATTACHMENT_PREVIEW_DEPTH) {
			// 超出深度：记入第 N 层祖先目录的溢出计数
			$node = &$tree;
			for ($i = 0; $i < ARGON_ATTACHMENT_PREVIEW_DEPTH; $i++) {
				$seg = $parts[$i];
				if (!isset($node['dirs'][$seg])) {
					$node['dirs'][$seg] = array('dirs' => array(), 'files' => array(), 'overflow' => 0);
				}
				if ($i === ARGON_ATTACHMENT_PREVIEW_DEPTH - 1) {
					$node['dirs'][$seg]['overflow']++;
				} else {
					$node = &$node['dirs'][$seg];
				}
			}
			unset($node);
			continue;
		}
		$node = &$tree;
		$last = count($parts) - 1;
		foreach ($parts as $i => $seg) {
			if ($i === $last) {
				if ($is_dir) {
					if (!isset($node['dirs'][$seg])) {
						$node['dirs'][$seg] = array('dirs' => array(), 'files' => array(), 'overflow' => 0);
					}
				} else {
					$node['files'][] = array('name' => $seg, 'size' => $size);
				}
			} else {
				if (!isset($node['dirs'][$seg])) {
					$node['dirs'][$seg] = array('dirs' => array(), 'files' => array(), 'overflow' => 0);
				}
				$node = &$node['dirs'][$seg];
			}
		}
		unset($node);
	}
	return $tree;
}

// 树 → 现代文件管理器结构（包含初始根目录清单与全树 JSON 数据，支持前端钻入与面包屑导航）
function argon_attachment_zip_tree_html($tree, $entries_count = 0) {
	$json_data = json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
	$html  = '<div class="argon-zip-explorer" data-tree="' . esc_attr($json_data) . '">';
	$html .= '<div class="argon-zip-toolbar">';
	$html .= '<button type="button" class="btn btn-sm btn-outline-primary argon-zip-up" disabled title="' . esc_attr__('返回上一级', 'argon') . '"><i class="fa fa-level-up" aria-hidden="true"></i> <span>' . __('返回上一级', 'argon') . '</span></button>';
	$html .= '<div class="argon-zip-breadcrumb"><span class="argon-zip-crumb" data-path="">' . __('根目录', 'argon') . '</span></div>';
	$html .= '</div>';
	$html .= '<div class="argon-zip-body">';
	$html .= '<div class="argon-zip-items"></div>';
	$html .= '</div>';
	$html .= '<div class="argon-zip-footer">';
	$html .= '<span class="argon-zip-summary">' . sprintf(esc_html__('共 %d 个条目', 'argon'), $entries_count) . '</span>';
	$html .= '<span class="argon-zip-notice">' . esc_html__('压缩包内文件不提供单独下载', 'argon') . '</span>';
	$html .= '</div>';
	$html .= '</div>';
	return $html;
}

// Pure PHP RAR 解析器（支持 RAR 4.x 与 RAR 5.0+ 格式，无需 PECL 扩展即可列出文件清单）
function argon_attachment_rar_entries($file_path) {
	if (class_exists('RarArchive')) {
		$rar = @RarArchive::open($file_path);
		if ($rar !== false) {
			$entries = array();
			$rar_entries = @$rar->getEntries();
			if (is_array($rar_entries)) {
				foreach ($rar_entries as $entry) {
					$is_dir = $entry->isDirectory();
					$name = argon_attachment_normalize_utf8($entry->getName());
					$entries[] = array(
						'name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''),
						'size' => $is_dir ? 0 : intval($entry->getUnpackedSize()),
					);
				}
				$rar->close();
				return $entries;
			}
			$rar->close();
		}
	}

	$fp = @fopen($file_path, 'rb');
	if (!$fp) {
		return false;
	}
	$magic = fread($fp, 7);
	if ($magic === "Rar!\x1a\x07\x00") { // RAR 4.x
		$entries = array();
		while (!feof($fp)) {
			$head = fread($fp, 7);
			if (strlen($head) < 7) {
				break;
			}
			$crc = unpack('v', substr($head, 0, 2))[1];
			$type = ord($head[2]);
			$flags = unpack('v', substr($head, 3, 2))[1];
			$size = unpack('v', substr($head, 5, 2))[1];
			$add_size = 0;
			if ($flags & 0x8000) {
				$add_bytes = fread($fp, 4);
				if (strlen($add_bytes) === 4) {
					$add_size = unpack('V', $add_bytes)[1];
				}
			}
			if ($type === 0x74) { // FILE_HEAD
				$fhead = fread($fp, 21);
				if (strlen($fhead) >= 21) {
					$unp_size = unpack('V', substr($fhead, 0, 4))[1];
					$name_size = unpack('v', substr($fhead, 15, 2))[1];
					$attr = unpack('V', substr($fhead, 17, 4))[1];
					if ($flags & 0x0100) {
						fread($fp, 8); // high pack & unp size
					}
					$raw_name = fread($fp, $name_size);
					$name = argon_attachment_normalize_utf8($raw_name);
					$is_dir = (($attr & 0x10) === 0x10) || (($flags & 0x00e0) === 0x00e0);
					$entries[] = array(
						'name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''),
						'size' => $is_dir ? 0 : intval($unp_size),
					);
				}
			}
			if ($add_size > 0) {
				fseek($fp, $add_size, SEEK_CUR);
			}
		}
		fclose($fp);
		return !empty($entries) ? $entries : false;
	} elseif ($magic . fread($fp, 1) === "Rar!\x1a\x07\x01\x00") { // RAR 5.x
		$entries = array();
		while (!feof($fp)) {
			$crc = fread($fp, 4);
			if (strlen($crc) < 4) {
				break;
			}
			// 读取 vint 变长整数
			$read_vint = function ($handle) {
				$val = 0; $shift = 0;
				while (!feof($handle)) {
					$b = ord(fread($handle, 1));
					$val |= ($b & 0x7f) << $shift;
					$shift += 7;
					if (($b & 0x80) === 0) break;
				}
				return $val;
			};
			$hdr_size = $read_vint($fp);
			$hdr_start = ftell($fp);
			$hdr_type = $read_vint($fp);
			$hdr_flags = $read_vint($fp);
			$extra_size = ($hdr_flags & 0x0001) ? $read_vint($fp) : 0;
			$data_size = ($hdr_flags & 0x0002) ? $read_vint($fp) : 0;
			if ($hdr_type === 2) { // File Header
				$file_flags = $read_vint($fp);
				$unp_size = $read_vint($fp);
				$attr = $read_vint($fp);
				if ($file_flags & 0x0002) fread($fp, 4); // mtime
				if ($file_flags & 0x0004) fread($fp, 4); // crc
				$comp = $read_vint($fp);
				$host_os = $read_vint($fp);
				$name_len = $read_vint($fp);
				$raw_name = fread($fp, $name_len);
				$name = argon_attachment_normalize_utf8($raw_name);
				$is_dir = ($file_flags & 0x0001) !== 0;
				$entries[] = array(
					'name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''),
					'size' => $is_dir ? 0 : intval($unp_size),
				);
			}
			fseek($fp, $hdr_start + $hdr_size + $data_size);
		}
		fclose($fp);
		return !empty($entries) ? $entries : false;
	}
	fclose($fp);
	return false;
}

// Pure PHP POSIX ustar / GNU TAR 解析器（支持 .tar 与 .tar.gz / .tgz）
function argon_attachment_tar_entries($file_path, $is_gz = false) {
	$fp = $is_gz && function_exists('gzopen') ? @gzopen($file_path, 'rb') : @fopen($file_path, 'rb');
	if (!$fp) {
		return false;
	}
	$entries = array();
	$read_fn = $is_gz ? 'gzread' : 'fread';
	$seek_fn = $is_gz ? 'gzseek' : 'fseek';

	while (!feof($fp)) {
		$block = $read_fn($fp, 512);
		if (strlen($block) < 512) {
			break;
		}
		if (substr($block, 0, 512) === str_repeat("\0", 512)) {
			break;
		}
		$name = trim(substr($block, 0, 100), "\0 ");
		$prefix = trim(substr($block, 345, 155), "\0 ");
		if ($prefix !== '') {
			$name = $prefix . '/' . $name;
		}
		$size_oct = trim(substr($block, 124, 12), "\0 ");
		$size = octdec($size_oct);
		$type = substr($block, 156, 1);
		$is_dir = ($type === '5' || substr($name, -1) === '/');

		if ($name !== '') {
			$name = argon_attachment_normalize_utf8($name);
			$entries[] = array(
				'name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''),
				'size' => $is_dir ? 0 : intval($size),
			);
		}
		$skip = (int) (ceil($size / 512) * 512);
		if ($skip > 0) {
			if ($is_gz) {
				$to_read = $skip;
				while ($to_read > 0 && !feof($fp)) {
					$chunk = min($to_read, 8192);
					$read_fn($fp, $chunk);
					$to_read -= $chunk;
				}
			} else {
				$seek_fn($fp, $skip, SEEK_CUR);
			}
		}
	}
	if ($is_gz) {
		@gzclose($fp);
	} else {
		@fclose($fp);
	}
	return !empty($entries) ? $entries : false;
}

// 查找系统可用归档命令行工具（Windows 10/11 内置 bsdtar 支持 7z/rar/tar/zip）
function argon_attachment_find_archive_binary($name) {
	static $cache = array();
	if (isset($cache[$name])) {
		return $cache[$name];
	}
	if ($name === 'tar') {
		$candidates = array(
			'C:/Windows/System32/tar.exe',
			'C:/Windows/SysWOW64/tar.exe',
			'/usr/bin/tar',
			'/bin/tar',
			'tar.exe',
			'tar',
		);
	} elseif ($name === '7z') {
		$candidates = array(
			'C:/Program Files/7-Zip/7z.exe',
			'C:/Program Files (x86)/7-Zip/7z.exe',
			'C:/ProgramData/chocolatey/bin/7z.exe',
			'C:/Users/30759/scoop/shims/7z.exe',
			'/usr/bin/7z',
			'/usr/bin/7za',
			'7z.exe',
			'7za.exe',
			'7z',
			'7za',
		);
	} else {
		$candidates = array($name);
	}

	foreach ($candidates as $candidate) {
		if (file_exists($candidate)) {
			$cache[$name] = $candidate;
			return $candidate;
		}
		if (strpos($candidate, '/') === false && strpos($candidate, '\\') === false) {
			$cmd = (DIRECTORY_SEPARATOR === '\\' ? 'where ' : 'which ') . escapeshellarg($candidate) . ' 2>&1';
			@exec($cmd, $out, $code);
			if ($code === 0 && !empty($out) && file_exists(trim($out[0]))) {
				$cache[$name] = trim($out[0]);
				return $cache[$name];
			}
		}
	}
	$cache[$name] = false;
	return false;
}

// 命令行工具回退（7z / 7za / tar 命令）
function argon_attachment_cli_archive_entries($file_path) {
	if (!function_exists('exec')) {
		return false;
	}
	$escaped = escapeshellarg($file_path);

	// 1. 优先尝试 7z / 7za
	$bin_7z = argon_attachment_find_archive_binary('7z');
	if ($bin_7z !== false) {
		$cmd = escapeshellarg($bin_7z) . " l -slt $escaped 2>&1";
		@exec($cmd, $out, $code);
		if ($code === 0 && !empty($out)) {
			$entries = array();
			$cur = array();
			foreach ($out as $line) {
				if (trim($line) === '') {
					if (!empty($cur['Path'])) {
						$is_dir = !empty($cur['Attributes']) && strpos($cur['Attributes'], 'D') !== false;
						$size = isset($cur['Size']) ? intval($cur['Size']) : 0;
						$name = argon_attachment_normalize_utf8($cur['Path']);
						$entries[] = array('name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''), 'size' => $size);
					}
					$cur = array();
					continue;
				}
				if (strpos($line, ' = ') !== false) {
					list($k, $v) = explode(' = ', $line, 2);
					$cur[trim($k)] = trim($v);
				}
			}
			if (!empty($cur['Path'])) {
				$is_dir = !empty($cur['Attributes']) && strpos($cur['Attributes'], 'D') !== false;
				$size = isset($cur['Size']) ? intval($cur['Size']) : 0;
				$name = argon_attachment_normalize_utf8($cur['Path']);
				$entries[] = array('name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''), 'size' => $size);
			}
			if (!empty($entries)) {
				return $entries;
			}
		}
	}

	// 2. 尝试系统 tar 命令（Windows 10/11 内置 bsdtar 支持 7z/rar/tar.gz 等）
	$bin_tar = argon_attachment_find_archive_binary('tar');
	if ($bin_tar !== false) {
		$cmd = escapeshellarg($bin_tar) . " -tvf $escaped 2>&1";
		@exec($cmd, $out2, $code2);
		if ($code2 === 0 && !empty($out2)) {
			$entries = array();
			foreach ($out2 as $line) {
				$line = trim($line);
				if ($line === '') continue;
				if (preg_match('/^([d\-lcbspD][rwx\-stST]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+.*?\d{1,2}:\d{2}(?::\d{2})?\s+(.+)$/u', $line, $m)) {
					$is_dir = substr($m[1], 0, 1) === 'd';
					$size = intval($m[5]);
					$name = argon_attachment_normalize_utf8($m[6]);
					$entries[] = array('name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''), 'size' => $is_dir ? 0 : $size);
				} else {
					$parts = preg_split('/\s+/', $line, 6);
					if (count($parts) >= 6) {
						$is_dir = substr($parts[0], 0, 1) === 'd';
						$size = intval($parts[2]);
						$name = argon_attachment_normalize_utf8($parts[5]);
						$entries[] = array('name' => $name . ($is_dir && substr($name, -1) !== '/' ? '/' : ''), 'size' => $is_dir ? 0 : $size);
					}
				}
			}
			if (!empty($entries)) {
				return $entries;
			}
		}

		// -tvf 为空时尝试 -tf
		$cmd_simple = escapeshellarg($bin_tar) . " -tf $escaped 2>&1";
		@exec($cmd_simple, $out3, $code3);
		if ($code3 === 0 && !empty($out3)) {
			$entries = array();
			foreach ($out3 as $line) {
				$name = trim($line);
				if ($name === '') continue;
				$name = argon_attachment_normalize_utf8($name);
				$entries[] = array('name' => $name, 'size' => 0);
			}
			if (!empty($entries)) {
				return $entries;
			}
		}
	}
	return false;
}

// 统一压缩包条目提取调度入口（ZIP / RAR / 7Z / TAR / GZ）
function argon_attachment_archive_extract_entries($file_path, $ext) {
	$ext = strtolower($ext);
	if ($ext === 'zip') {
		if (!class_exists('ZipArchive')) {
			return false;
		}
		$zip = new ZipArchive();
		if ($zip->open($file_path) !== true) {
			return false;
		}
		$entries = array();
		$total = $zip->numFiles;
		for ($i = 0; $i < $total; $i++) {
			$stat = $zip->statIndex($i);
			if (!is_array($stat) || !isset($stat['name'])) {
				continue;
			}
			$name = argon_attachment_normalize_utf8($stat['name']);
			$entries[] = array('name' => $name, 'size' => isset($stat['size']) ? intval($stat['size']) : 0);
		}
		$zip->close();
		return $entries;
	} elseif ($ext === 'rar') {
		$rar_entries = argon_attachment_rar_entries($file_path);
		if ($rar_entries !== false) {
			return $rar_entries;
		}
		return argon_attachment_cli_archive_entries($file_path);
	} elseif ($ext === '7z') {
		$cli_entries = argon_attachment_cli_archive_entries($file_path);
		if ($cli_entries !== false) {
			return $cli_entries;
		}
		return false;
	} elseif ($ext === 'tar') {
		$tar_entries = argon_attachment_tar_entries($file_path, false);
		if ($tar_entries !== false && !empty($tar_entries)) {
			return $tar_entries;
		}
		return argon_attachment_cli_archive_entries($file_path);
	} elseif ($ext === 'gz' || $ext === 'tgz') {
		$tar_entries = argon_attachment_tar_entries($file_path, true);
		if ($tar_entries !== false && !empty($tar_entries)) {
			return $tar_entries;
		}
		return argon_attachment_cli_archive_entries($file_path);
	}
	return false;
}

// 统一压缩包快照生成入口（输出与 ZIP 统一的文件资源管理器结构）
function argon_attachment_snapshot_archive($file_path, $ext = 'zip') {
	$entries = argon_attachment_archive_extract_entries($file_path, $ext);
	if ($entries === false) {
		return false;
	}
	$tree = argon_attachment_zip_build_tree($entries);
	return argon_attachment_zip_tree_html($tree, count($entries));
}

function argon_attachment_snapshot_zip($file_path) {
	return argon_attachment_snapshot_archive($file_path, 'zip');
}

/* ---------------------------------------------------------------------------
 * md：Parsedown（安全模式）
 * ------------------------------------------------------------------------- */

function argon_attachment_snapshot_markdown($file_path, $title) {
	if (!class_exists('_Parsedown') && !class_exists('Parsedown')) {
		require_once get_template_directory() . '/parsedown.php';
	}
	if (!class_exists('_Parsedown') && !class_exists('Parsedown')) {
		return false;
	}
	$text = file_get_contents($file_path);
	if ($text === false) {
		return false;
	}
	if (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')) {
		$converted = @mb_convert_encoding($text, 'UTF-8', 'GB18030');
		if ($converted !== false && $converted !== '') {
			$text = $converted;
		}
	}
	// 主题自带版类名为 _Parsedown（防插件冲突），优先使用
	$parsedown = class_exists('_Parsedown') ? new _Parsedown() : new Parsedown();
	$parsedown->setSafeMode(true);
	$parsedown->setMarkupEscaped(true);
	$body = $parsedown->text($text);
	return argon_attachment_snapshot_document($title, $body, 'argon-snap-md');
}

/* ---------------------------------------------------------------------------
 * OOXML 公共工具（docx/xlsx/pptx 都是 zip + XML）
 * ------------------------------------------------------------------------- */

function argon_ooxml_open($file_path) {
	if (!class_exists('ZipArchive')) {
		return false;
	}
	$zip = new ZipArchive();
	if ($zip->open($file_path) !== true) {
		return false;
	}
	return $zip;
}

function argon_ooxml_read_xml($zip, $name) {
	$raw = $zip->getFromName($name);
	if ($raw === false) {
		return false;
	}
	if (!class_exists('DOMDocument')) {
		return false;
	}
	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	if (!@$dom->loadXML($raw, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET)) {
		return false;
	}
	return $dom;
}

// 读取 .rels 关系文件 → rId => target
function argon_ooxml_rels($zip, $rels_path) {
	$map = array();
	$dom = argon_ooxml_read_xml($zip, $rels_path);
	if ($dom === false || !($dom->documentElement instanceof DOMElement)) {
		return $map;
	}
	foreach ($dom->documentElement->childNodes as $rel) {
		if (!($rel instanceof DOMElement)) {
			continue;
		}
		$id = $rel->getAttribute('Id');
		$target = $rel->getAttribute('Target');
		if ($id !== '' && $target !== '') {
			$map[$id] = $target;
		}
	}
	return $map;
}

// 把 zip 内的媒体文件提取到快照媒体目录，返回公网 URL
function argon_ooxml_extract_media($zip, $zip_inner_path, $uuid, $fingerprint) {
	static $extracted = array();
	$key = $uuid . '|' . $zip_inner_path;
	if (isset($extracted[$key])) {
		return $extracted[$key];
	}
	$media_dir = argon_attachment_snapshot_media_dir($uuid, $fingerprint);
	$url_base = argon_attachment_snapshot_url_base();
	if ($media_dir === false || $url_base === false) {
		return '';
	}
	$basename = preg_replace('/[^0-9a-zA-Z\.\-_]/', '_', basename($zip_inner_path));
	$data = $zip->getFromName($zip_inner_path);
	if ($data === false || strlen($data) > 10 * 1024 * 1024) {
		return '';
	}
	@file_put_contents(trailingslashit($media_dir) . $basename, $data);
	$url = trailingslashit($url_base) . $uuid . '.' . $fingerprint . '/' . rawurlencode($basename);
	$extracted[$key] = $url;
	return $url;
}

/* ---------------------------------------------------------------------------
 * docx 渲染
 * ------------------------------------------------------------------------- */

function argon_docx_run_html(DOMElement $run, $xpath) {
	$html = '';
	foreach ($run->childNodes as $node) {
		if (!($node instanceof DOMElement)) {
			continue;
		}
		$local = $node->localName;
		if ($local === 't') {
			$html .= esc_html($node->textContent);
		} elseif ($local === 'br' || $local === 'cr') {
			$html .= '<br/>';
		} elseif ($local === 'tab') {
			$html .= '&emsp;';
		}
	}
	if ($html === '') {
		return '';
	}
	$rPr = $xpath->query('./w:rPr', $run)->item(0);
	if ($rPr instanceof DOMElement) {
		if ($xpath->query('./w:b', $rPr)->length) $html = '<strong>' . $html . '</strong>';
		if ($xpath->query('./w:i', $rPr)->length) $html = '<em>' . $html . '</em>';
		if ($xpath->query('./w:u', $rPr)->length) $html = '<u>' . $html . '</u>';
		if ($xpath->query('./w:strike', $rPr)->length) $html = '<s>' . $html . '</s>';
	}
	return $html;
}

function argon_docx_paragraph_html(DOMElement $p, $xpath) {
	$inner = '';
	foreach ($xpath->query('./w:r', $p) as $run) {
		$inner .= argon_docx_run_html($run, $xpath);
	}
	// 图片（drawing 引用的媒体）
	foreach ($xpath->query('.//a:blip', $p) as $blip) {
		$embed = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
		if ($embed !== '' && isset($GLOBALS['argon_docx_rels'][$embed])) {
			$target = $GLOBALS['argon_docx_rels'][$embed];
			$url = argon_ooxml_extract_media($GLOBALS['argon_docx_zip'], 'word/' . ltrim($target, '/'), $GLOBALS['argon_docx_uuid'], $GLOBALS['argon_docx_fp']);
			if ($url !== '') {
				$inner .= '<img src="' . esc_url($url) . '" alt=""/>';
			}
		}
	}
	if ($inner === '') {
		return '';
	}
	$tag = 'p';
	$pPr = $xpath->query('./w:pPr', $p)->item(0);
	if ($pPr instanceof DOMElement) {
		$style = $xpath->query('./w:pStyle', $pPr)->item(0);
		if ($style instanceof DOMElement) {
			$val = $style->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val');
			if ($val === '') {
				$val = $style->getAttribute('w:val');
			}
			if (preg_match('/^Heading([1-6])$/i', $val, $m)) {
				$level = min(4, intval($m[1]));
				$tag = 'h' . $level;
			}
		}
		if ($xpath->query('./w:numPr', $pPr)->length) {
			return '<ul><li>' . $inner . '</li></ul>';
		}
	}
	return '<' . $tag . '>' . $inner . '</' . $tag . '>';
}

function argon_docx_table_html(DOMElement $tbl, $xpath) {
	$html = '<table>';
	$row_index = 0;
	foreach ($xpath->query('./w:tr', $tbl) as $tr) {
		$html .= '<tr>';
		foreach ($xpath->query('./w:tc', $tr) as $tc) {
			$cell = '';
			foreach ($xpath->query('./w:p', $tc) as $p) {
				$cell_html = argon_docx_paragraph_html($p, $xpath);
				if ($cell_html !== '') {
					$cell .= ($cell === '' ? '' : ' ') . strip_tags($cell_html, '<strong><em><u><s><br><img>');
				}
			}
			$html .= ($row_index === 0 ? '<th>' : '<td>') . $cell . ($row_index === 0 ? '</th>' : '</td>');
		}
		$html .= '</tr>';
		$row_index++;
	}
	return $html . '</table>';
}

function argon_attachment_snapshot_docx($file_path, $title, $uuid, $fingerprint) {
	$zip = argon_ooxml_open($file_path);
	if ($zip === false) {
		return false;
	}
	$dom = argon_ooxml_read_xml($zip, 'word/document.xml');
	if ($dom === false) {
		$zip->close();
		return false;
	}
	$GLOBALS['argon_docx_zip'] = $zip;
	$GLOBALS['argon_docx_rels'] = argon_ooxml_rels($zip, 'word/_rels/document.xml.rels');
	$GLOBALS['argon_docx_uuid'] = $uuid;
	$GLOBALS['argon_docx_fp'] = $fingerprint;

	$xpath = new DOMXPath($dom);
	$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
	$xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

	$body = '';
	$body_node = $xpath->query('//w:body')->item(0);
	if ($body_node instanceof DOMElement) {
		foreach ($body_node->childNodes as $node) {
			if (!($node instanceof DOMElement)) {
				continue;
			}
			if ($node->localName === 'p') {
				$body .= argon_docx_paragraph_html($node, $xpath);
			} elseif ($node->localName === 'tbl') {
				$body .= argon_docx_table_html($node, $xpath);
			}
		}
	}
	$zip->close();
	if (trim($body) === '') {
		$body = '<p>' . esc_html__('文档没有可提取的正文内容。', 'argon') . '</p>';
	}
	return argon_attachment_snapshot_document($title, $body, 'argon-snap-docx');
}

/* ---------------------------------------------------------------------------
 * xlsx 渲染
 * ------------------------------------------------------------------------- */

function argon_xlsx_col_index($cell_ref) {
	if (!preg_match('/^([A-Z]+)/i', $cell_ref, $m)) {
		return 0;
	}
	$letters = strtoupper($m[1]);
	$index = 0;
	for ($i = 0; $i < strlen($letters); $i++) {
		$index = $index * 26 + (ord($letters[$i]) - 64);
	}
	return $index - 1;
}

function argon_attachment_snapshot_xlsx($file_path, $title) {
	$zip = argon_ooxml_open($file_path);
	if ($zip === false) {
		return false;
	}

	// 共享字符串
	$shared = array();
	$ss_dom = argon_ooxml_read_xml($zip, 'xl/sharedStrings.xml');
	if ($ss_dom !== false) {
		foreach ($ss_dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'si') as $si) {
			$text = '';
			foreach ($si->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't') as $t) {
				$text .= $t->textContent;
			}
			$shared[] = $text;
		}
	}

	// 工作表清单
	$wb = argon_ooxml_read_xml($zip, 'xl/workbook.xml');
	if ($wb === false) {
		$zip->close();
		return false;
	}
	$wb_rels = argon_ooxml_rels($zip, 'xl/_rels/workbook.xml.rels');
	$sheets = array();
	foreach ($wb->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'sheet') as $sheet) {
		$name = $sheet->getAttribute('name');
		$rid = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
		if (isset($wb_rels[$rid])) {
			$sheet_path = ltrim($wb_rels[$rid], '/');
			if (strpos($sheet_path, 'xl/') !== 0) {
				$sheet_path = 'xl/' . $sheet_path;
			}
			$sheets[] = array('name' => $name, 'path' => $sheet_path);
		}
	}

	$body = '';
	$max_rows = 300;
	$max_cols = 60;
	foreach ($sheets as $sheet) {
		$dom = argon_ooxml_read_xml($zip, $sheet['path']);
		if ($dom === false) {
			continue;
		}
		$rows_html = '';
		$row_count = 0;
		$truncated = false;
		foreach ($dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'row') as $row) {
			if ($row_count >= $max_rows) {
				$truncated = true;
				break;
			}
			$cells = array();
			foreach ($row->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c') as $c) {
				$col = argon_xlsx_col_index($c->getAttribute('r'));
				if ($col >= $max_cols) {
					continue;
				}
				$type = $c->getAttribute('t');
				$value = '';
				if ($type === 'inlineStr') {
					foreach ($c->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't') as $t) {
						$value .= $t->textContent;
					}
				} else {
					foreach ($c->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v') as $v) {
						$value = $v->textContent;
						break;
					}
					if ($type === 's' && $value !== '' && isset($shared[intval($value)])) {
						$value = $shared[intval($value)];
					}
				}
				$cells[$col] = $value;
			}
			if (empty($cells)) {
				continue;
			}
			$rows_html .= '<tr>';
			for ($col = 0; $col <= max(array_keys($cells)); $col++) {
				$rows_html .= ($row_count === 0 ? '<th>' : '<td>') . (isset($cells[$col]) ? esc_html($cells[$col]) : '') . ($row_count === 0 ? '</th>' : '</td>');
			}
			$rows_html .= '</tr>';
			$row_count++;
		}
		if ($rows_html === '') {
			continue;
		}
		$body .= '<div class="argon-sheet-title">' . esc_html($sheet['name']) . '</div>';
		$body .= '<div class="argon-sheet-table-wrap"><table>' . $rows_html . '</table></div>';
		if ($truncated) {
			$body .= '<p class="argon-snap-note">' . sprintf(esc_html__('工作表仅预览前 %d 行。', 'argon'), $max_rows) . '</p>';
		}
	}
	$zip->close();
	if ($body === '') {
		$body = '<p>' . esc_html__('表格没有可提取的内容。', 'argon') . '</p>';
	}
	return argon_attachment_snapshot_document($title, $body, 'argon-snap-xlsx');
}

/* ---------------------------------------------------------------------------
 * pptx 渲染：逐页提取文本与图片
 * ------------------------------------------------------------------------- */

function argon_attachment_snapshot_pptx($file_path, $title, $uuid, $fingerprint) {
	$zip = argon_ooxml_open($file_path);
	if ($zip === false) {
		return false;
	}
	$slide_names = array();
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$name = $zip->getNameIndex($i);
		if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
			$slide_names[intval($m[1])] = $name;
		}
	}
	ksort($slide_names);
	if (empty($slide_names)) {
		$zip->close();
		return false;
	}

	$body = '';
	$slide_no = 0;
	$slide_total = count($slide_names);
	foreach ($slide_names as $name) {
		$slide_no++;
		$dom = argon_ooxml_read_xml($zip, $name);
		if ($dom === false) {
			continue;
		}
		$rels = argon_ooxml_rels($zip, 'ppt/slides/_rels/' . basename($name) . '.rels');

		$content = '';
		foreach ($dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'p') as $p) {
			$line = '';
			foreach ($p->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 't') as $t) {
				$line .= $t->textContent;
			}
			if (trim($line) !== '') {
				$content .= '<p>' . esc_html(trim($line)) . '</p>';
			}
		}
		foreach ($dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'blip') as $blip) {
			$embed = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
			if ($embed !== '' && isset($rels[$embed])) {
				$target = preg_replace('#^\.\./#', 'ppt/', $rels[$embed]);
				$url = argon_ooxml_extract_media($zip, $target, $uuid, $fingerprint);
				if ($url !== '') {
					$content .= '<img src="' . esc_url($url) . '" alt=""/>';
				}
			}
		}
		if ($content === '') {
			$content = '<p>' . esc_html__('（本页没有可提取的内容）', 'argon') . '</p>';
		}
		$body .= '<div class="argon-slide"><div class="argon-slide-no">' . sprintf(esc_html__('第 %d 页 / 共 %d 页', 'argon'), $slide_no, $slide_total) . '</div>' . $content . '</div>';
	}
	$zip->close();
	return argon_attachment_snapshot_document($title, $body, 'argon-snap-pptx');
}

/* ---------------------------------------------------------------------------
 * 快照生成入口：返回磁盘路径（false 表示失败）
 * ------------------------------------------------------------------------- */

function argon_attachment_generate_snapshot($uuid, $file_path, $att, $force_recreate = false) {
	if (!is_file($file_path) || filesize($file_path) > ARGON_ATTACHMENT_PREVIEW_MAX_SOURCE) {
		return false;
	}
	$ext = $att['ext'];
	$title = $att['name'];
	switch ($ext) {
		case 'md':
			return argon_attachment_snapshot_get($uuid, $file_path, function () use ($file_path, $title) {
				return argon_attachment_snapshot_markdown($file_path, $title);
			}, $force_recreate);
		case 'zip':
		case 'rar':
		case '7z':
		case 'tar':
		case 'gz':
		case 'tgz':
			return argon_attachment_snapshot_get($uuid, $file_path, function () use ($file_path, $ext) {
				return argon_attachment_snapshot_archive($file_path, $ext);
			}, $force_recreate);
		case 'docx':
			return argon_attachment_snapshot_get($uuid, $file_path, function () use ($file_path, $title, $uuid) {
				return argon_attachment_snapshot_docx($file_path, $title, $uuid, argon_attachment_snapshot_fingerprint($file_path));
			}, $force_recreate);
		case 'xlsx':
			return argon_attachment_snapshot_get($uuid, $file_path, function () use ($file_path, $title) {
				return argon_attachment_snapshot_xlsx($file_path, $title);
			}, $force_recreate);
		case 'pptx':
			return argon_attachment_snapshot_get($uuid, $file_path, function () use ($file_path, $title, $uuid) {
				return argon_attachment_snapshot_pptx($file_path, $title, $uuid, argon_attachment_snapshot_fingerprint($file_path));
			}, $force_recreate);
	}
	return false;
}

/* ---------------------------------------------------------------------------
 * 前台 AJAX：打开预览（首次触发快照生成，返回静态快照 URL）
 * ------------------------------------------------------------------------- */

add_action('wp_ajax_argon_attachment_preview_open', 'argon_attachment_ajax_preview_open');
add_action('wp_ajax_nopriv_argon_attachment_preview_open', 'argon_attachment_ajax_preview_open');
function argon_attachment_ajax_preview_open() {
	$uuid = isset($_POST['uuid']) ? sanitize_text_field(wp_unslash($_POST['uuid'])) : '';
	if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
		wp_send_json_error(array('message' => __('附件标识无效', 'argon')), 400);
	}

	// 预览打开限流：每 IP 每分钟最多 60 次（快照命中时只是磁盘读，开销极小）
	$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$rate_key = 'argon_pv_open_' . md5($ip);
	$opened = intval(get_transient($rate_key));
	if ($opened >= 60) {
		wp_send_json_error(array('message' => __('请求过于频繁，请稍后再试', 'argon')), 429);
	}
	set_transient($rate_key, $opened + 1, MINUTE_IN_SECONDS);

	$located = argon_attachments_locate($uuid);
	if ($located === null) {
		wp_send_json_error(array('message' => __('附件不存在或已被删除', 'argon')), 404);
	}
	list($post_id, $attachment) = $located;
	$ext = $attachment['ext'];

	// 非可渲染格式（doc/ppt 为老二进制，或其它媒体/执行档）：返回不支持并附带原因
	if (!argon_attachment_preview_renderable($ext)) {
		wp_send_json_success(array(
			'type'    => 'unsupported',
			'name'    => $attachment['name'],
			'message' => sprintf(__('.%s 格式暂不支持在线预览，请点击下方按钮直接下载。', 'argon'), strtoupper($ext)),
		));
	}

	$dir = argon_attachments_post_dir($post_id);
	$file_path = $dir !== false ? trailingslashit($dir) . $attachment['file'] : '';
	if ($dir === false || !is_file($file_path)) {
		wp_send_json_error(array('message' => __('附件文件已丢失', 'argon')), 404);
	}

	// 超过 30MB 的大文件不进行快照解析，提示具体理由
	$file_size = filesize($file_path);
	if ($file_size > ARGON_ATTACHMENT_PREVIEW_MAX_SOURCE) {
		wp_send_json_success(array(
			'type'    => 'unsupported',
			'name'    => $attachment['name'],
			'message' => sprintf(__('文件大小（%s）超过 %s 预览上限，暂不支持在线预览，请点击下方按钮直接下载。', 'argon'), size_format($file_size, 1), size_format(ARGON_ATTACHMENT_PREVIEW_MAX_SOURCE, 0)),
		));
	}

	// pdf 直接内嵌原文件（浏览器内核渲染，文件本身即 CDN 缓存对象）
	if ($ext === 'pdf') {
		$url_base = argon_attachments_post_url($post_id);
		wp_send_json_success(array(
			'type' => 'pdf',
			'url'  => trailingslashit($url_base) . rawurlencode($attachment['file']),
			'name' => $attachment['name'],
		));
	}

	$force_refresh = !empty($_POST['force_refresh']) && current_user_can('edit_posts');
	if ($force_refresh) {
		argon_attachment_snapshot_purge($uuid);
	}

	$snapshot = argon_attachment_generate_snapshot($uuid, $file_path, $attachment, $force_refresh);
	if ($snapshot === false) {
		wp_send_json_success(array(
			'type'    => 'unsupported',
			'name'    => $attachment['name'],
			'message' => __('该文件内容暂时无法在线解析预览，请点击下方按钮直接下载查看。', 'argon'),
		));
	}
	$url = argon_attachment_snapshot_public_url($snapshot);
	if ($url === '') {
		wp_send_json_success(array(
			'type'    => 'unsupported',
			'name'    => $attachment['name'],
			'message' => __('预览地址生成失败，请点击下方按钮直接下载查看。', 'argon'),
		));
	}
	$is_archive = in_array($ext, array('zip', 'rar', '7z', 'tar', 'gz', 'tgz'), true);
	$response_data = array(
		'type' => $is_archive ? 'archive' : 'html',
		'url'  => $url,
		'name' => $attachment['name'],
	);
	if ($is_archive) {
		$raw_html = @file_get_contents($snapshot);
		if ($raw_html !== false && $raw_html !== '') {
			$response_data['html'] = $raw_html;
		}
	}
	wp_send_json_success($response_data);
}
