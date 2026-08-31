<?php
/**
 * 文章附件下载模块
 *
 * 存储：uploads/argon-attachments/{post_id}/，磁盘文件名随机混淆，目录禁止脚本执行。
 * 数据：post meta argon_post_attachments（uuid => 附件信息），全局索引 option
 *       argon_attachment_index（uuid => post_id）用于下载端点 O(1) 定位。
 * 下载：?download=uuid&expires=TS&sign=HMAC 验签 + 同 IP 计数节流，302 跳转混淆直链，
 *       直链带真实扩展名以便 CDN 按静态资源缓存；前端链接用 download 属性保留原始文件名。
 */

if (!defined('ABSPATH')) {
	exit;
}

define('ARGON_ATTACHMENTS_META_KEY', 'argon_post_attachments');
define('ARGON_ATTACHMENTS_INDEX_OPTION', 'argon_attachment_index');
define('ARGON_ATTACHMENTS_URL_TTL', 2 * HOUR_IN_SECONDS);        // 前台下载链接有效期
define('ARGON_ATTACHMENTS_COPY_URL_TTL', 30 * DAY_IN_SECONDS);   // 后台复制链接有效期
define('ARGON_ATTACHMENTS_COUNT_WINDOW', HOUR_IN_SECONDS);       // 同 IP 计数节流窗口

// 预览快照渲染器（依赖本文件的存储/索引助手）
require_once get_template_directory() . '/inc/attachment-preview.php';

/* ---------------------------------------------------------------------------
 * 配置项
 * ------------------------------------------------------------------------- */

function argon_attachments_allowed_extensions() {
	$raw = get_option('argon_attachment_allowed_extensions', '');
	if ($raw === '') {
		$raw = 'zip,rar,7z,tar,gz,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,apk,ipsw,mp3,flac,mp4,mkv,jpg,jpeg,png,gif,webp,epub,mobi';
	}
	$list = array_filter(array_map('trim', explode(',', strtolower($raw))));
	return array_values(array_unique($list));
}

function argon_attachments_max_size() {
	$mb = absint(get_option('argon_attachment_max_size', 50));
	if ($mb <= 0) {
		$mb = 50;
	}
	return $mb * 1024 * 1024;
}

function argon_attachment_is_captcha_enabled() {
	$mode = get_option('argon_attachment_captcha_mode', 'disabled');
	if ($mode === 'enabled') {
		return true;
	}
	if ($mode === 'global') {
		return function_exists('argon_is_captcha_enabled') && argon_is_captcha_enabled();
	}
	return false;
}

/* ---------------------------------------------------------------------------
 * 存储
 * ------------------------------------------------------------------------- */

function argon_attachments_uploads() {
	$uploads = wp_upload_dir();
	if (!empty($uploads['error']) || empty($uploads['basedir']) || empty($uploads['baseurl'])) {
		return false;
	}
	return $uploads;
}

function argon_attachments_post_dir($post_id) {
	$uploads = argon_attachments_uploads();
	if ($uploads === false) {
		return false;
	}
	return trailingslashit($uploads['basedir']) . 'argon-attachments/' . absint($post_id);
}

function argon_attachments_post_url($post_id) {
	$uploads = argon_attachments_uploads();
	if ($uploads === false) {
		return false;
	}
	$baseurl = $uploads['baseurl'];
	if (function_exists('set_url_scheme')) {
		$baseurl = set_url_scheme($baseurl);
	}
	return trailingslashit($baseurl) . 'argon-attachments/' . absint($post_id);
}

// 无论白名单如何配置都禁止上传的格式：可被服务器执行或可被浏览器同域渲染（XSS）的类型
function argon_attachments_denied_extensions() {
	return array('php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pl', 'py', 'cgi', 'asp', 'aspx', 'jsp', 'sh', 'bash', 'htaccess', 'html', 'htm', 'js', 'svg', 'svgz');
}

// 创建目录并写入防护文件：允许静态读取（CDN 可缓存），禁止脚本执行与目录列表。
// 部分主机 AllowOverride 不含 Options，.htaccess 会导致整目录 500，
// 因此写入后做一次回环请求验证，失败则删除 .htaccess（防护降级，功能不受影响）。
function argon_attachments_ensure_dir($dir) {
	if (!wp_mkdir_p($dir)) {
		return false;
	}
	$index = trailingslashit($dir) . 'index.php';
	if (!file_exists($index)) {
		@file_put_contents($index, "<?php\n// Silence is golden.\n");
	}
	$htaccess = trailingslashit($dir) . '.htaccess';
	if (!file_exists($htaccess)) {
		$rules  = "Options -Indexes\n";
		$rules .= "<IfModule mod_php.c>\n\tphp_flag engine off\n</IfModule>\n";
		$rules .= "<IfModule mod_php7.c>\n\tphp_flag engine off\n</IfModule>\n";
		$rules .= "<IfModule mod_php8.c>\n\tphp_flag engine off\n</IfModule>\n";
		$rules .= "<FilesMatch \"(?i)\\.(php[0-9]?|phtml|phar|pl|py|cgi|asp|aspx|jsp|sh)$\">\n";
		$rules .= "\t<IfModule mod_authz_core.c>\n\t\tRequire all denied\n\t</IfModule>\n";
		$rules .= "\t<IfModule !mod_authz_core.c>\n\t\tOrder allow,deny\n\t\tDeny from all\n\t</IfModule>\n";
		$rules .= "</FilesMatch>\n";
		if (@file_put_contents($htaccess, $rules) !== false) {
			argon_attachments_verify_htaccess($dir, $htaccess);
		}
	}
	return true;
}

function argon_attachments_verify_htaccess($dir, $htaccess) {
	$uploads = argon_attachments_uploads();
	if ($uploads === false) {
		return;
	}
	$base = trailingslashit($uploads['basedir']) . 'argon-attachments';
	$base_url = trailingslashit($uploads['baseurl']) . 'argon-attachments';
	if (strpos($dir, $base) !== 0) {
		return;
	}
	$probe = $base_url . substr($dir, strlen($base)) . '/index.php';
	$response = wp_remote_head($probe, array('timeout' => 5, 'redirection' => 0, 'sslverify' => false));
	if (is_wp_error($response)) {
		// 回环不可达时无法验证，保守起见移除，避免潜在 500
		@unlink($htaccess);
		return;
	}
	$code = wp_remote_retrieve_response_code($response);
	if ($code >= 500 || $code === 0) {
		@unlink($htaccess);
	}
}

/* ---------------------------------------------------------------------------
 * 数据
 * ------------------------------------------------------------------------- */

function argon_get_post_attachments($post_id) {
	$attachments = get_post_meta($post_id, ARGON_ATTACHMENTS_META_KEY, true);
	if (!is_array($attachments)) {
		return array();
	}
	foreach ($attachments as $uuid => $att) {
		if (!is_array($att) || empty($att['file'])) {
			unset($attachments[$uuid]);
			continue;
		}
		$attachments[$uuid] = wp_parse_args($att, array(
			'name'      => $att['file'],
			'file'      => '',
			'size'      => 0,
			'ext'       => '',
			'downloads' => 0,
			'time'      => 0,
		));
	}
	return $attachments;
}

function argon_save_post_attachments($post_id, $attachments) {
	if (empty($attachments)) {
		delete_post_meta($post_id, ARGON_ATTACHMENTS_META_KEY);
		return;
	}
	update_post_meta($post_id, ARGON_ATTACHMENTS_META_KEY, $attachments);
}

function argon_attachments_index() {
	$index = get_option(ARGON_ATTACHMENTS_INDEX_OPTION, array());
	return is_array($index) ? $index : array();
}

function argon_attachments_index_add($uuid, $post_id) {
	$index = argon_attachments_index();
	$index[$uuid] = absint($post_id);
	update_option(ARGON_ATTACHMENTS_INDEX_OPTION, $index, false);
}

function argon_attachments_index_remove($uuid) {
	$index = argon_attachments_index();
	if (isset($index[$uuid])) {
		unset($index[$uuid]);
		update_option(ARGON_ATTACHMENTS_INDEX_OPTION, $index, false);
	}
}

// 按 uuid 定位附件，返回 array(post_id, attachment) 或 null；索引过期时顺手清理
function argon_attachments_locate($uuid) {
	$index = argon_attachments_index();
	if (!isset($index[$uuid])) {
		return null;
	}
	$post_id = absint($index[$uuid]);
	$attachments = argon_get_post_attachments($post_id);
	if (!isset($attachments[$uuid])) {
		argon_attachments_index_remove($uuid);
		return null;
	}
	return array($post_id, $attachments[$uuid], $attachments);
}

// 同文章内展示名去重（对齐 WP 核心 wp_unique_filename 行为）：
// report.pdf 冲突时依次尝试 report-1.pdf、report-2.pdf；比较大小写不敏感。
function argon_attachments_unique_name($existing_names, $desired) {
	$desired = trim((string) $desired);
	if ($desired === '') {
		return $desired;
	}
	$taken = array();
	foreach ($existing_names as $name) {
		$taken[strtolower((string) $name)] = true;
	}
	if (!isset($taken[strtolower($desired)])) {
		return $desired;
	}
	$ext = pathinfo($desired, PATHINFO_EXTENSION);
	$ext = is_string($ext) ? $ext : '';
	$base = $ext !== '' ? substr($desired, 0, -strlen($ext) - 1) : $desired;
	$suffix = 1;
	do {
		$candidate = $base . '-' . $suffix . ($ext !== '' ? '.' . $ext : '');
		$suffix++;
	} while (isset($taken[strtolower($candidate)]));
	return $candidate;
}

// 按展示名在指定文章的附件中定位（短代码 name 软引用用）：
// 大小写不敏感；重名时按列表顺序取第 N 个，name 可带 "#2" 后缀指定（默认第 1 个）。
// 返回 array(uuid, attachment) 或 null。
function argon_attachments_find_by_name($post_id, $name) {
	$name = trim((string) $name);
	if ($name === '' || !$post_id) {
		return null;
	}
	$position = 1;
	if (preg_match('/^(.*)#(\d+)$/s', $name, $m)) {
		$name = trim($m[1]);
		$position = max(1, intval($m[2]));
	}
	if ($name === '') {
		return null;
	}
	$needle = strtolower($name);
	$found = 0;
	foreach (argon_get_post_attachments($post_id) as $uuid => $att) {
		if (strtolower((string) $att['name']) !== $needle) {
			continue;
		}
		$found++;
		if ($found === $position) {
			return array($uuid, $att);
		}
	}
	return null;
}

/* ---------------------------------------------------------------------------
 * 签名与下载承载页链接
 * ------------------------------------------------------------------------- */

function argon_attachment_sign($uuid, $expires) {
	return hash_hmac('sha256', $uuid . '|' . $expires, wp_salt('auth'));
}

// 统一返回独立下载承载页地址：?download=uuid
function argon_attachment_download_url($uuid) {
	return add_query_arg(array('download' => $uuid), home_url('/'));
}

// 带签名的直链下发地址（供特定离线场景使用）
function argon_attachment_signed_download_url($uuid, $ttl = null) {
	if ($ttl === null) {
		$ttl = ARGON_ATTACHMENTS_URL_TTL;
	}
	$expires = time() + $ttl;
	return add_query_arg(array(
		'download' => $uuid,
		'expires'  => $expires,
		'sign'     => argon_attachment_sign($uuid, $expires),
	), home_url('/'));
}

function argon_attachment_verify_url($uuid, $expires, $sign) {
	if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
		return false;
	}
	$expires = absint($expires);
	if ($expires < time()) {
		return false;
	}
	return hash_equals(argon_attachment_sign($uuid, $expires), (string) $sign);
}

/* ---------------------------------------------------------------------------
 * 后台 AJAX：上传 / 删除 / 改名与排序
 * ------------------------------------------------------------------------- */

function argon_attachments_ajax_guard() {
	check_ajax_referer('argon_attachment_manage', 'nonce');
	$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
	if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
		wp_send_json_error(array('message' => __('没有权限操作该文章的附件', 'argon')), 403);
	}
	if (!in_array(get_post_type($post_id), array('post', 'page'), true)) {
		wp_send_json_error(array('message' => __('该内容类型不支持附件', 'argon')), 400);
	}
	return $post_id;
}

function argon_attachment_admin_payload($post_id, $uuid, $att) {
	return array(
		'uuid'         => $uuid,
		'name'         => $att['name'],
		'size'         => size_format($att['size'], 1),
		'downloads'    => intval($att['downloads']),
		'download_url' => argon_attachment_download_url($uuid),
	);
}

add_action('wp_ajax_argon_attachment_upload', 'argon_attachment_ajax_upload');
function argon_attachment_ajax_upload() {
	// post_max_size 超限时 PHP 静默丢弃整个请求体（$_POST/$_FILES 全空，连 nonce 都不存在），
	// 只能在 nonce 校验前据 Content-Length 识别，否则用户只会看到无意义的「校验失败」
	if (empty($_FILES) && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
		wp_send_json_error(array('message' => sprintf(
			__('文件超过服务器 PHP 上传限制（post_max_size = %s），请改用更小的文件或联系管理员调大限制', 'argon'),
			ini_get('post_max_size')
		)), 400);
	}
	$post_id = argon_attachments_ajax_guard();

	if (empty($_FILES['file']) || !isset($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
		wp_send_json_error(array('message' => __('没有接收到文件', 'argon')), 400);
	}
	$file = $_FILES['file'];
	if (!empty($file['error'])) {
		$message = __('文件上传失败，错误码：', 'argon') . intval($file['error']);
		if (intval($file['error']) === UPLOAD_ERR_INI_SIZE || intval($file['error']) === UPLOAD_ERR_FORM_SIZE) {
			$message = sprintf(__('文件超过服务器 PHP 上传限制（upload_max_filesize = %s）', 'argon'), ini_get('upload_max_filesize'));
		}
		wp_send_json_error(array('message' => $message), 400);
	}

	$original_name = sanitize_text_field($file['name']);
	$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
	if ($ext === '' || !in_array($ext, argon_attachments_allowed_extensions(), true)) {
		wp_send_json_error(array('message' => sprintf(__('不允许上传 .%s 格式的文件', 'argon'), $ext)), 400);
	}
	if (in_array($ext, argon_attachments_denied_extensions(), true)) {
		wp_send_json_error(array('message' => sprintf(__('.%s 格式存在安全风险，禁止作为附件上传', 'argon'), $ext)), 400);
	}
	if ($file['size'] > argon_attachments_max_size()) {
		wp_send_json_error(array('message' => sprintf(__('文件超过大小上限 %s', 'argon'), size_format(argon_attachments_max_size(), 0))), 400);
	}

	$dir = argon_attachments_post_dir($post_id);
	if ($dir === false || !argon_attachments_ensure_dir($dir)) {
		wp_send_json_error(array('message' => __('附件目录创建失败', 'argon')), 500);
	}

	$disk_name = bin2hex(random_bytes(16)) . '.' . $ext;
	$target = trailingslashit($dir) . $disk_name;
	if (!@move_uploaded_file($file['tmp_name'], $target)) {
		wp_send_json_error(array('message' => __('文件写入失败', 'argon')), 500);
	}
	@chmod($target, 0644);

	$uuid = wp_generate_uuid4();
	$attachments = argon_get_post_attachments($post_id);
	// 同文章内展示名去重：name 是短代码软引用的寻址标识，必须唯一
	$existing_names = array();
	foreach ($attachments as $existing) {
		$existing_names[] = $existing['name'];
	}
	$display_name = argon_attachments_unique_name($existing_names, $original_name);
	$attachments[$uuid] = array(
		'name'      => $display_name,
		'file'      => $disk_name,
		'size'      => intval($file['size']),
		'ext'       => $ext,
		'downloads' => 0,
		'time'      => time(),
	);
	argon_save_post_attachments($post_id, $attachments);
	argon_attachments_index_add($uuid, $post_id);

	wp_send_json_success(argon_attachment_admin_payload($post_id, $uuid, $attachments[$uuid]));
}

add_action('wp_ajax_argon_attachment_delete', 'argon_attachment_ajax_delete');
function argon_attachment_ajax_delete() {
	$post_id = argon_attachments_ajax_guard();
	$uuid = isset($_POST['uuid']) ? sanitize_text_field($_POST['uuid']) : '';

	$attachments = argon_get_post_attachments($post_id);
	if (!isset($attachments[$uuid])) {
		wp_send_json_error(array('message' => __('附件不存在', 'argon')), 404);
	}

	$dir = argon_attachments_post_dir($post_id);
	if ($dir !== false) {
		$path = trailingslashit($dir) . $attachments[$uuid]['file'];
		if (file_exists($path)) {
			@unlink($path);
		}
	}
	if (function_exists('argon_attachment_snapshot_purge')) {
		argon_attachment_snapshot_purge($uuid);
	}
	unset($attachments[$uuid]);
	argon_save_post_attachments($post_id, $attachments);
	argon_attachments_index_remove($uuid);

	wp_send_json_success(array('uuid' => $uuid));
}

// 改名与排序：客户端提交完整顺序的 uuid 列表及各 uuid 的展示名
add_action('wp_ajax_argon_attachment_update', 'argon_attachment_ajax_update');
function argon_attachment_ajax_update() {
	$post_id = argon_attachments_ajax_guard();
	$order = isset($_POST['order']) && is_array($_POST['order']) ? array_map('sanitize_text_field', $_POST['order']) : array();
	$names = isset($_POST['names']) && is_array($_POST['names']) ? array_map('sanitize_text_field', $_POST['names']) : array();

	$attachments = argon_get_post_attachments($post_id);
	$rebuilt = array();
	foreach ($order as $uuid) {
		if (!isset($attachments[$uuid])) {
			continue;
		}
		if (isset($names[$uuid]) && $names[$uuid] !== '') {
			$attachments[$uuid]['name'] = $names[$uuid];
		}
		$rebuilt[$uuid] = $attachments[$uuid];
	}
	// 防御：客户端漏传的 uuid 追加到末尾，避免误删
	foreach ($attachments as $uuid => $att) {
		if (!isset($rebuilt[$uuid])) {
			$rebuilt[$uuid] = $att;
		}
	}
	// 展示名按最终顺序去重（同名自动追加 -N 后缀），保证 [attachment name="..."] 软引用可寻址；
	// 被改动的名字回传给客户端同步输入框（顺带修复历史遗留的同名单）
	$used_names = array();
	$renamed = array();
	foreach ($rebuilt as $uuid => $att) {
		$unique = argon_attachments_unique_name($used_names, $att['name']);
		if ($unique !== $att['name']) {
			$rebuilt[$uuid]['name'] = $unique;
			$renamed[$uuid] = $unique;
		}
		$used_names[] = $unique;
	}
	argon_save_post_attachments($post_id, $rebuilt);

	wp_send_json_success(array('count' => count($rebuilt), 'names' => $renamed));
}

/* ---------------------------------------------------------------------------
 * 前台 AJAX：签发一次性下载凭证
 *
 * 页面不内嵌任何下载 URL。用户点击后由 JS 请求本端点，返回带一次性 token 的
 * 下载地址（120 秒内有效，用一次即废），从源头防止直链外泄与复用。
 * ------------------------------------------------------------------------- */

add_action('wp_ajax_argon_attachment_issue', 'argon_attachment_ajax_issue');
add_action('wp_ajax_nopriv_argon_attachment_issue', 'argon_attachment_ajax_issue');
function argon_attachment_ajax_issue() {
	$uuid = isset($_POST['uuid']) ? sanitize_text_field(wp_unslash($_POST['uuid'])) : '';
	if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
		wp_send_json_error(array('message' => __('附件标识无效', 'argon')), 400);
	}

	// 验证码检查（若设置开启）
	if (argon_attachment_is_captcha_enabled()) {
		if (function_exists('argon_verify_captcha')) {
			$captcha_res = argon_verify_captcha('attachment');
			if (empty($captcha_res['success'])) {
				$msg = !empty($captcha_res['error']) ? $captcha_res['error'] : __('验证码验证失败，请重试', 'argon');
				wp_send_json_error(array('message' => $msg, 'need_captcha' => true), 403);
			}
		}
	}

	// 签发限流：每 IP 每分钟最多 30 次，防止签发端点本身成为写入放大入口
	$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$rate_key = 'argon_dl_issue_' . md5($ip);
	$issued = intval(get_transient($rate_key));
	if ($issued >= 30) {
		wp_send_json_error(array('message' => __('请求过于频繁，请稍后再试', 'argon')), 429);
	}
	set_transient($rate_key, $issued + 1, MINUTE_IN_SECONDS);

	$located = argon_attachments_locate($uuid);
	if ($located === null) {
		wp_send_json_error(array('message' => __('附件不存在或已被删除', 'argon')), 404);
	}
	list($post_id, $attachment) = $located;

	$dir = argon_attachments_post_dir($post_id);
	if ($dir === false || !file_exists(trailingslashit($dir) . $attachment['file'])) {
		wp_send_json_error(array('message' => __('附件文件已丢失', 'argon')), 404);
	}

	$token = wp_generate_password(32, false, false);
	set_transient('argon_dl_token_' . $token, $uuid, 120);

	wp_send_json_success(array(
		'url'  => add_query_arg(array('download' => $uuid, 'token' => $token), home_url('/')),
		'name' => $attachment['name'],
	));
}

/* ---------------------------------------------------------------------------
 * 下载端点与独立下载承载页分流：
 * 1. 携带 token 或 sign：验证通过后 302 重定向到 CDN 静态文件。
 * 2. 仅携带 ?download=uuid：加载并渲染独立下载中转/承载页（page-download.php）。
 * ------------------------------------------------------------------------- */

add_action('template_redirect', 'argon_attachment_download_endpoint', 0);
function argon_attachment_download_endpoint() {
	if (!isset($_GET['download'])) {
		return;
	}

	$uuid    = sanitize_text_field(wp_unslash($_GET['download']));
	$token   = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
	$sign    = isset($_GET['sign']) ? sanitize_text_field(wp_unslash($_GET['sign'])) : '';
	$expires = isset($_GET['expires']) ? absint($_GET['expires']) : 0;

	// 若未附带 token 与 sign，直接渲染独立下载中转/承载页
	if ($token === '' && $sign === '') {
		include get_template_directory() . '/page-download.php';
		exit;
	}

	if ($token !== '') {
		// 一次性凭证：验证通过立即作废
		$token_key = 'argon_dl_token_' . $token;
		$token_uuid = get_transient($token_key);
		if ($token_uuid === false || !hash_equals((string) $token_uuid, $uuid)) {
			wp_die(__('下载链接无效或已被使用，请回到下载页面重新获取。', 'argon'), __('下载失败', 'argon'), array('response' => 403));
		}
		delete_transient($token_key);
	} else {
		// 签名链接（离线/直接下发场景）
		if (!argon_attachment_verify_url($uuid, $expires, $sign)) {
			wp_die(__('下载链接无效或已过期，请回到下载页面重新获取。', 'argon'), __('下载失败', 'argon'), array('response' => 403));
		}
	}

	$located = argon_attachments_locate($uuid);
	if ($located === null) {
		wp_die(__('附件不存在或已被删除。', 'argon'), __('下载失败', 'argon'), array('response' => 404));
	}
	list($post_id, $attachment, $attachments) = $located;

	$dir = argon_attachments_post_dir($post_id);
	$url = argon_attachments_post_url($post_id);
	$path = $dir !== false ? trailingslashit($dir) . $attachment['file'] : '';
	if ($dir === false || $url === false || !file_exists($path)) {
		wp_die(__('附件文件已丢失。', 'argon'), __('下载失败', 'argon'), array('response' => 404));
	}

	// 同一 IP 在节流窗口内对同一附件只计一次，压制刷接口造成的写入放大
	$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$throttle_key = 'argon_dl_' . md5($ip . '|' . $uuid);
	if (!get_transient($throttle_key)) {
		$attachments[$uuid]['downloads'] = intval($attachments[$uuid]['downloads']) + 1;
		update_post_meta($post_id, ARGON_ATTACHMENTS_META_KEY, $attachments);
		set_transient($throttle_key, 1, ARGON_ATTACHMENTS_COUNT_WINDOW);
	}

	// 清理输出缓冲区，防止额外输出破坏二进制流
	if (!defined('ARGON_TESTING')) {
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
	}

	$filename = $attachment['name'];
	$ascii_fallback = preg_replace('/[^\x20-\x7e]/', '_', $filename);
	if ($ascii_fallback === '') {
		$ascii_fallback = 'attachment.' . $attachment['ext'];
	}
	$encoded_filename = rawurlencode($filename);

		if (!headers_sent()) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $ascii_fallback . '"; filename*=UTF-8\'\'' . $encoded_filename);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($path));
		}

	readfile($path);
	if (defined('ARGON_TESTING') && class_exists('Argon_Attachment_Test_Die')) {
		throw new Argon_Attachment_Test_Die('exit', 200);
	}
	exit;
}

/* ---------------------------------------------------------------------------
 * 文章删除时清理附件目录与索引
 * 注意必须用 before_delete_post：delete_post 触发时 post meta 已被删除，
 * 那时再读附件清单只能拿到空数组，索引会残留。
 * ------------------------------------------------------------------------- */

add_action('before_delete_post', 'argon_attachments_cleanup_post');
function argon_attachments_cleanup_post($post_id) {
	$attachments = argon_get_post_attachments($post_id);
	if (!empty($attachments)) {
		foreach (array_keys($attachments) as $uuid) {
			argon_attachments_index_remove($uuid);
			if (function_exists('argon_attachment_snapshot_purge')) {
				argon_attachment_snapshot_purge($uuid);
			}
		}
	}
	$dir = argon_attachments_post_dir($post_id);
	if ($dir !== false && is_dir($dir)) {
		argon_attachments_remove_dir($dir);
	}
}

function argon_attachments_remove_dir($dir) {
	$items = scandir($dir);
	if ($items === false) {
		return;
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$path = trailingslashit($dir) . $item;
		if (is_dir($path)) {
			argon_attachments_remove_dir($path);
		} else {
			@unlink($path);
		}
	}
	@rmdir($dir);
}

/* ---------------------------------------------------------------------------
 * 前端渲染
 * ------------------------------------------------------------------------- */

function argon_attachment_icon_class($ext) {
	$map = array(
		'zip' => 'fa-file-archive-o', 'rar' => 'fa-file-archive-o', '7z' => 'fa-file-archive-o',
		'tar' => 'fa-file-archive-o', 'gz' => 'fa-file-archive-o',
		'pdf' => 'fa-file-pdf-o',
		'doc' => 'fa-file-word-o', 'docx' => 'fa-file-word-o',
		'xls' => 'fa-file-excel-o', 'xlsx' => 'fa-file-excel-o',
		'ppt' => 'fa-file-powerpoint-o', 'pptx' => 'fa-file-powerpoint-o',
		'jpg' => 'fa-file-image-o', 'jpeg' => 'fa-file-image-o', 'png' => 'fa-file-image-o',
		'gif' => 'fa-file-image-o', 'webp' => 'fa-file-image-o', 'svg' => 'fa-file-image-o',
		'mp3' => 'fa-file-audio-o', 'flac' => 'fa-file-audio-o',
		'mp4' => 'fa-file-video-o', 'mkv' => 'fa-file-video-o',
		'txt' => 'fa-file-text-o', 'md' => 'fa-file-text-o', 'epub' => 'fa-file-text-o', 'mobi' => 'fa-file-text-o',
	);
	return isset($map[$ext]) ? $map[$ext] : 'fa-file-o';
}

// 单个附件卡片（紧凑款，短代码与文底列表共用）。
// 点击下载按钮在新窗口打开独立下载中转/承载页（?download=uuid）进行鉴权与下载，不走 PJAX。
// 全格式卡片支持点击打开预览弹窗（支持格式直接预览，超限或不支持格式展示友好理由并引导下载）。
function argon_attachment_card_html($uuid, $att) {
	$icon = argon_attachment_icon_class($att['ext']);
	$download_page_url = argon_attachment_download_url($uuid);
	$html  = '<div class="argon-attachment-item argon-attachment-previewable" data-uuid="' . esc_attr($uuid) . '" data-ext="' . esc_attr($att['ext']) . '" title="' . esc_attr__('点击预览', 'argon') . '">';
	$html .= '<div class="argon-attachment-icon"><i class="fa ' . esc_attr($icon) . '" aria-hidden="true"></i></div>';
	$html .= '<div class="argon-attachment-info">';
	$html .= '<div class="argon-attachment-name" title="' . esc_attr($att['name']) . '">' . esc_html($att['name']) . '</div>';
	$html .= '<div class="argon-attachment-meta">' . esc_html(size_format($att['size'], 1)) . ' · <span class="argon-attachment-downloads" data-count="' . intval($att['downloads']) . '">' . intval($att['downloads']) . '</span> ' . __('次下载', 'argon') . '</div>';
	$html .= '</div>';
	$html .= '<button type="button" class="argon-attachment-download" data-uuid="' . esc_attr($uuid) . '" data-name="' . esc_attr($att['name']) . '"><i class="fa fa-download" aria-hidden="true"></i><span>' . __('下载', 'argon') . '</span></button>';
	$html .= '</div>';
	return $html;
}

// 附件组（文底列表与 [attachments] 短代码共用）
function argon_attachments_group_html($attachments, $show_header = true) {
	if (empty($attachments)) {
		return '';
	}
	$html = argon_attachments_assets_html();
	$html .= '<div class="argon-attachments">';
	if ($show_header) {
		$html .= '<div class="argon-attachments-header"><i class="fa fa-paperclip" aria-hidden="true"></i> ' . __('附件下载', 'argon') . '<span class="argon-attachments-count">' . sprintf(__('%d 个文件', 'argon'), count($attachments)) . '</span></div>';
	}
	$html .= '<div class="argon-attachments-list">';
	foreach ($attachments as $uuid => $att) {
		$html .= argon_attachment_card_html($uuid, $att);
	}
	$html .= '</div></div>';
	return $html;
}

// 文底自动渲染入口（content-single.php 调用）
function argon_render_post_attachments($post_id) {
	if (post_password_required($post_id)) {
		return;
	}
	echo argon_attachments_group_html(argon_get_post_attachments($post_id));
}

// 行内链接样式：[attachment id="xxx" style="link"]
function argon_attachment_inline_link_html($uuid, $att) {
	return '<button type="button" class="argon-attachment-inline-link argon-attachment-download" data-uuid="' . esc_attr($uuid) . '" data-name="' . esc_attr($att['name']) . '"><i class="fa ' . esc_attr(argon_attachment_icon_class($att['ext'])) . '" aria-hidden="true"></i> ' . esc_html($att['name']) . ' <span class="argon-attachment-inline-size">(' . esc_html(size_format($att['size'], 1)) . ')</span></button>';
}

/* ---------------------------------------------------------------------------
 * 短代码
 *
 * 外显标识对齐 WP 核心惯例（无前缀语义化命名）：
 *   [attachment id="uuid"]          稳定引用（后台「插入卡片/插入链接」按钮写入，改名不失效）
 *   [attachment name="文件名.pdf"]   手写软引用，命中当前文章同名附件中排序最靠前的一个
 *   [attachment name="a.pdf#2"]     同名时取第 2 个（#N 消歧后缀）
 *   [attachment ... style="link"]   行内链接样式（默认卡片）
 *   [attachments]                   当前文章全部附件列表（id 属性可指定文章 ID）
 * ------------------------------------------------------------------------- */

add_shortcode('attachment', 'argon_attachment_shortcode');
function argon_attachment_shortcode($attr) {
	$attr = shortcode_atts(array('id' => '', 'name' => '', 'style' => 'card'), $attr);
	$uuid = sanitize_text_field($attr['id']);
	$att = null;
	if ($uuid !== '') {
		// id 优先于 name：UUID 是不受改名影响的稳定引用
		$located = argon_attachments_locate($uuid);
		if ($located !== null) {
			$att = $located[1];
		}
	} else {
		$name = sanitize_text_field($attr['name']);
		if ($name === '') {
			return '';
		}
		$found = argon_attachments_find_by_name(get_the_ID(), $name);
		if ($found !== null) {
			$uuid = $found[0];
			$att = $found[1];
		}
	}
	if ($att === null) {
		$label = $uuid !== '' ? $uuid : sanitize_text_field($attr['name']);
		return current_user_can('edit_posts') ? '<span class="argon-attachment-missing">[' . sprintf(__('附件 %s 不存在', 'argon'), esc_html($label)) . ']</span>' : '';
	}
	if ($attr['style'] === 'link') {
		return argon_attachments_assets_html() . argon_attachment_inline_link_html($uuid, $att);
	}
	return argon_attachments_assets_html() . '<div class="argon-attachments argon-attachments-single"><div class="argon-attachments-list">' . argon_attachment_card_html($uuid, $att) . '</div></div>';
}

add_shortcode('attachments', 'argon_attachments_shortcode');
function argon_attachments_shortcode($attr) {
	$attr = shortcode_atts(array('id' => ''), $attr);
	$post_id = $attr['id'] !== '' ? absint($attr['id']) : get_the_ID();
	if (!$post_id) {
		return '';
	}
	if (post_password_required($post_id)) {
		return '';
	}
	return argon_attachments_group_html(argon_get_post_attachments($post_id));
}

/* ---------------------------------------------------------------------------
 * 后台：meta box 与资源
 * ------------------------------------------------------------------------- */

add_action('add_meta_boxes', 'argon_attachments_add_meta_box');
function argon_attachments_add_meta_box() {
	add_meta_box(
		'argon_post_attachments',
		__('附件下载', 'argon'),
		'argon_attachments_meta_box_html',
		array('post', 'page'),
		'normal',
		'default'
	);
}

function argon_attachments_meta_box_html($post) {
	$attachments = argon_get_post_attachments($post->ID);
	// 重名检测：name 短代码软引用命中排序最靠前的一个，提示作者改名消除歧义
	$name_counts = array();
	foreach ($attachments as $att) {
		$key = strtolower((string) $att['name']);
		$name_counts[$key] = isset($name_counts[$key]) ? $name_counts[$key] + 1 : 1;
	}
	?>
	<div id="argon-attachments-manager" data-post-id="<?php echo esc_attr($post->ID); ?>">
		<div id="argon-attachments-admin-list" class="argon-attachments-admin-list">
			<?php foreach ($attachments as $uuid => $att) { ?>
				<?php $is_dup = $name_counts[strtolower((string) $att['name'])] > 1; ?>
				<div class="argon-att-admin-row<?php echo $is_dup ? ' argon-att-admin-dup' : ''; ?>" data-uuid="<?php echo esc_attr($uuid); ?>"<?php echo $is_dup ? ' title="' . esc_attr__('存在同名附件：[attachment name="..."] 引用将指向排序最靠前的一个，建议改名消除歧义', 'argon') . '"' : ''; ?>>
					<span class="argon-att-admin-drag" title="<?php esc_attr_e('拖动排序', 'argon'); ?>"><i class="fa fa-bars" aria-hidden="true"></i></span>
					<input type="text" class="argon-att-admin-name" value="<?php echo esc_attr($att['name']); ?>" placeholder="<?php esc_attr_e('展示文件名', 'argon'); ?>"/>
					<span class="argon-att-admin-size"><?php echo esc_html(size_format($att['size'], 1)); ?></span>
					<span class="argon-att-admin-count"><?php printf(__('%d 次下载', 'argon'), intval($att['downloads'])); ?></span>
					<button type="button" class="button argon-att-admin-insert" data-style="card"><?php _e('插入卡片', 'argon'); ?></button>
					<button type="button" class="button argon-att-admin-insert" data-style="link"><?php _e('插入链接', 'argon'); ?></button>
					<button type="button" class="button argon-att-admin-copy" data-url="<?php echo esc_attr(argon_attachment_download_url($uuid)); ?>"><?php _e('复制链接', 'argon'); ?></button>
					<button type="button" class="button argon-att-admin-delete"><?php _e('删除', 'argon'); ?></button>
				</div>
			<?php } ?>
			<p class="argon-attachments-admin-empty" <?php if (!empty($attachments)) { echo 'style="display:none;"'; } ?>><?php _e('还没有附件，点击下方按钮上传。', 'argon'); ?></p>
		</div>
		<div class="argon-attachments-admin-upload">
			<input type="file" id="argon-attachment-file-input" style="display:none;"/>
			<button type="button" class="button button-primary" id="argon-attachment-upload-btn"><i class="fa fa-upload" aria-hidden="true"></i> <?php _e('上传附件', 'argon'); ?></button>
			<span class="argon-attachments-admin-hint">
				<?php printf(__('允许格式：%s', 'argon'), esc_html(implode(', ', argon_attachments_allowed_extensions()))); ?>
				<?php
				$argon_att_php_max = function_exists('wp_max_upload_size') ? wp_max_upload_size() : 0;
				$argon_att_effective_max = $argon_att_php_max > 0 ? min(argon_attachments_max_size(), $argon_att_php_max) : argon_attachments_max_size();
				printf(__('单文件上限：%s', 'argon'), esc_html(size_format($argon_att_effective_max, 0)));
				if ($argon_att_php_max > 0 && $argon_att_php_max < argon_attachments_max_size()) {
					echo esc_html__('（受服务器 PHP 上传限制约束）', 'argon');
				}
				?>
			</span>
			<div class="argon-attachments-admin-progress" style="display:none;"><div class="argon-attachments-admin-progress-bar"></div></div>
		</div>
		<p class="description"><?php _e('附件在文章页文底自动展示；点「插入卡片」「插入链接」可把附件嵌入正文光标处，无需关心附件 ID。也支持手写短代码 <code>[attachment name="文件名.pdf"]</code>（重名时命中排序最靠前的一个，可用 <code>#2</code> 后缀指定第二个；name 是软引用，附件改名后需同步修改），<code>[attachments]</code> 输出本文全部附件列表。「复制链接」得到的是站内下载页地址。', 'argon'); ?></p>
	</div>
	<?php
}

add_action('admin_enqueue_scripts', 'argon_attachments_admin_assets');
function argon_attachments_admin_assets($hook) {
	if ($hook !== 'post.php' && $hook !== 'post-new.php') {
		return;
	}
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || !in_array($screen->post_type, array('post', 'page'), true)) {
		return;
	}
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('argon-attachments-admin', get_template_directory_uri() . '/assets/js/attachments-admin.js', array('jquery', 'jquery-ui-sortable'), argon_get_asset_file_version('assets/js/attachments-admin.js'), true);
	// maxSize 取主题设置与服务器 PHP 限制（upload_max_filesize/post_max_size）的较小值，
	// 否则主题设 50MB 而 PHP 只允许 2MB 时，超限文件要到服务端才被拒，白白传一轮
	$php_max_size = function_exists('wp_max_upload_size') ? wp_max_upload_size() : 0;
	$effective_max_size = $php_max_size > 0 ? min(argon_attachments_max_size(), $php_max_size) : argon_attachments_max_size();
	wp_localize_script('argon-attachments-admin', 'argonAttachmentsAdmin', array(
		'ajaxurl'    => admin_url('admin-ajax.php'),
		'nonce'      => wp_create_nonce('argon_attachment_manage'),
		'maxSize'    => $effective_max_size,
		'allowedExt' => argon_attachments_allowed_extensions(),
		'i18n'       => array(
			'uploading'    => __('正在上传…', 'argon'),
			'uploadFailed' => __('上传失败', 'argon'),
			'deleteConfirm'=> __('确定删除该附件？文件将同时从服务器删除。', 'argon'),
			'deleteFailed' => __('删除失败', 'argon'),
			'saveFailed'   => __('保存失败', 'argon'),
			'copied'       => __('下载链接已复制', 'argon'),
			'copyFailed'   => __('复制失败，请手动复制', 'argon'),
			'badExt'       => __('不允许上传该格式的文件', 'argon'),
			'tooBig'       => sprintf(__('文件超过大小上限 %s', 'argon'), '%s'),
			'sessionExpired' => __('登录状态已过期，请刷新页面后重试', 'argon'),
			'downloadTimes'=> __('次下载', 'argon'),
			'copyLink'     => __('复制链接', 'argon'),
			'deleteText'   => __('删除', 'argon'),
			'dragTitle'    => __('拖动排序', 'argon'),
			'nameHolder'   => __('展示文件名', 'argon'),
			'insertCard'   => __('插入卡片', 'argon'),
			'insertLink'   => __('插入链接', 'argon'),
			'inserted'     => __('已插入到编辑器', 'argon'),
			'insertCopied' => __('编辑器不可用，短代码已复制，请粘贴到正文', 'argon'),
			'renamedTo'    => __('为避免重名，已自动改名为：%s', 'argon'),
		),
	));
	wp_enqueue_style('argon-attachments-admin', get_template_directory_uri() . '/assets/css/attachments-admin.css', array(), argon_get_asset_file_version('assets/css/attachments-admin.css'));
}

/* ---------------------------------------------------------------------------
 * 前端资源
 *
 * 主题 PJAX 不合并 <head>，条件入队在 PJAX 导航后会丢样式，
 * 因此与 bangumi 等页面一致：资源随附件标记内联输出（同页多次渲染只输出一次）。
 * ------------------------------------------------------------------------- */

function argon_attachments_assets_html() {
	static $done = false;
	if ($done) {
		return '';
	}
	$done = true;
	$css = add_query_arg('v', argon_get_asset_file_version('assets/css/attachments.css'), get_template_directory_uri() . '/assets/css/attachments.css');
	$js = add_query_arg('v', argon_get_asset_file_version('assets/js/attachments.js'), get_template_directory_uri() . '/assets/js/attachments.js');
	return '<link rel="stylesheet" href="' . esc_url($css) . '"/>' . "\n"
		. '<script src="https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/pdf.js/2.12.313/pdf.min.js"></script>' . "\n"
		. '<script src="' . esc_url($js) . '" data-pjax></script>' . "\n"
		. argon_attachments_preview_modal_html()
		. argon_attachments_captcha_modal_html();
}

// 居中验证码模态弹窗（仅在开启下载验证码时需要，用户完成验证后自动触发下发）
function argon_attachments_captcha_modal_html() {
	if (!argon_attachment_is_captcha_enabled()) {
		return '';
	}
	$captcha_type = get_option('argon_captcha_type', 'math');
	$commentCaptchaSeed = function_exists('get_comment_captcha_seed') ? get_comment_captcha_seed() : 0;
	$math_captcha_display = function_exists('get_comment_captcha') ? get_comment_captcha($commentCaptchaSeed) : '';

	$html = '<div class="argon-attachment-captcha-modal" hidden>'
		. '<div class="argon-attachment-captcha-mask"></div>'
		. '<div class="argon-attachment-captcha-dialog" role="dialog" aria-modal="true">'
		. '<div class="argon-attachment-captcha-header">'
		. '<span class="argon-attachment-captcha-title">' . __('安全验证', 'argon') . '</span>'
		. '<button type="button" class="argon-attachment-captcha-close" title="' . esc_attr__('关闭', 'argon') . '"><i class="fa fa-times" aria-hidden="true"></i></button>'
		. '</div>'
		. '<div class="argon-attachment-captcha-body">';

	if ($captcha_type === 'geetest') {
		$html .= '<div id="argon-att-geetest-container"></div>'
			. '<input type="hidden" id="argon_att_geetest_lot_number" value="">'
			. '<input type="hidden" id="argon_att_geetest_captcha_output" value="">'
			. '<input type="hidden" id="argon_att_geetest_pass_token" value="">'
			. '<input type="hidden" id="argon_att_geetest_gen_time" value="">';
	} else {
		$html .= '<p class="argon-att-captcha-tip">' . __('请完成简单算术以开始下载：', 'argon') . '</p>'
			. '<div class="argon-att-math-row">'
			. '<span class="argon-att-math-expr">' . esc_html($math_captcha_display) . '</span>'
			. '<input type="text" class="argon-att-math-input" placeholder="' . esc_attr__('答案', 'argon') . '" autocomplete="off" />'
			. '<input type="hidden" class="argon-att-math-seed" value="' . esc_attr($commentCaptchaSeed) . '" />'
			. '</div>'
			. '<button type="button" class="argon-att-captcha-submit btn btn-primary btn-block">' . __('确认并下载', 'argon') . '</button>';
	}

	$html .= '<div class="argon-att-captcha-error" hidden></div>'
		. '</div></div></div>';
	return $html;
}

// 预览弹窗（同一页面只输出一个，所有附件卡片共用，标准 Bootstrap Modal）
function argon_attachments_preview_modal_html() {
	return ''
		. '<div id="argon_attachment_preview_modal" class="modal fade argon-attachment-preview-modal" tabindex="-1" role="dialog" aria-modal="true">'
		. '<div class="modal-dialog modal-dialog-centered modal-lg argon-attachment-preview-dialog-wrap" role="document">'
		. '<div class="modal-content argon-attachment-preview-dialog">'
		. '<div class="modal-header argon-attachment-preview-header">'
		. '<div class="d-flex align-items-center" style="gap: 10px; min-width: 0; flex: 1;">'
		. '<span class="argon-attachment-preview-icon text-primary"><i class="fa fa-file-o" aria-hidden="true"></i></span>'
		. '<h4 class="modal-title argon-attachment-preview-title text-truncate" style="font-size: 18px; margin: 0;"></h4>'
		. '</div>'
		. '<button type="button" class="close argon-attachment-preview-close" data-dismiss="modal" aria-label="' . esc_attr__('关闭', 'argon') . '">'
		. '<span aria-hidden="true">&times;</span>'
		. '</button>'
		. '</div>'
			. '<div class="modal-body argon-attachment-preview-body">'
			. '<div class="argon-attachment-preview-loading"><i class="fa fa-circle-o-notch fa-spin text-primary" aria-hidden="true"></i> <span>' . __('正在加载预览…', 'argon') . '</span></div>'
			. '<iframe class="argon-attachment-preview-frame" hidden></iframe>'
			. '<div class="argon-attachment-preview-pdf" hidden>'
			. '<div class="argon-pdf-toolbar">'
			. '<div class="argon-pdf-toolbar-group">'
			. '<button type="button" class="btn btn-sm btn-outline-primary argon-pdf-prev" title="' . esc_attr__('上一页', 'argon') . '"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>'
			. '<span class="argon-pdf-page-num"><span class="argon-pdf-current">1</span> / <span class="argon-pdf-total">--</span></span>'
			. '<button type="button" class="btn btn-sm btn-outline-primary argon-pdf-next" title="' . esc_attr__('下一页', 'argon') . '"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>'
			. '</div>'
			. '<div class="argon-pdf-toolbar-group">'
			. '<button type="button" class="btn btn-sm btn-outline-secondary argon-pdf-zoom-out" title="' . esc_attr__('缩小', 'argon') . '"><i class="fa fa-search-minus" aria-hidden="true"></i></button>'
			. '<span class="argon-pdf-scale-text">100%</span>'
			. '<button type="button" class="btn btn-sm btn-outline-secondary argon-pdf-zoom-in" title="' . esc_attr__('放大', 'argon') . '"><i class="fa fa-search-plus" aria-hidden="true"></i></button>'
			. '<button type="button" class="btn btn-sm btn-outline-secondary argon-pdf-fit" title="' . esc_attr__('适应宽度', 'argon') . '"><i class="fa fa-arrows-h" aria-hidden="true"></i> ' . __('适应宽度', 'argon') . '</button>'
			. '</div>'
			. '</div>'
			. '<div class="argon-pdf-viewer-wrap">'
			. '<canvas class="argon-pdf-canvas"></canvas>'
			. '</div>'
			. '</div>'
			. '<div class="argon-attachment-preview-zip" hidden></div>'
			. '<div class="argon-attachment-preview-unsupported" hidden><i class="fa fa-info-circle text-info" aria-hidden="true"></i> ' . __('该格式暂不支持在线预览，请点击下方下载按钮获取文件。', 'argon') . '</div>'
			. '</div>'
				. '<div class="modal-footer argon-attachment-preview-footer">'
				. '<a class="btn btn-primary argon-attachment-download argon-attachment-preview-download no-pjax" href="#" target="_blank" rel="noopener noreferrer"><i class="fa fa-download" aria-hidden="true"></i> <span>' . __('下载文件', 'argon') . '</span></a>'
				. (current_user_can('edit_posts') ? '<button type="button" class="btn btn-outline-warning argon-attachment-refresh-snapshot" title="' . esc_attr__('重新解析生成当前文件快照', 'argon') . '"><i class="fa fa-refresh" aria-hidden="true"></i> <span>' . __('刷新快照', 'argon') . '</span></button>' : '')
				. '<button type="button" class="btn btn-secondary" data-dismiss="modal">' . __('关闭', 'argon') . '</button>'
				. '</div>'
		. '</div>'
		. '</div>'
		. '</div>';
}
