<?php

/**
 * 附件下载模块契约与行为测试。
 *
 * 运行：php tests/attachments-download-flow-test.php
 */

$root = dirname(__DIR__);
$attachments_source = file_get_contents($root . '/inc/attachments.php');
$functions_source = file_get_contents($root . '/functions.php');
$single_source = file_get_contents($root . '/template-parts/content-single.php');
$settings_source = file_get_contents($root . '/settings.php');
$failures = array();

function argon_attachments_test_assert($condition, $message) {
	global $failures;
	if (!$condition) $failures[] = $message;
}

/* ---------------------------------------------------------------------------
 * 第一部分：源码契约断言
 * ------------------------------------------------------------------------- */

argon_attachments_test_assert(
	strpos($functions_source, "require_once get_template_directory() . '/inc/attachments.php';") !== false,
	'functions.php 没有引入 inc/attachments.php。'
);
argon_attachments_test_assert(
	strpos($single_source, 'argon_render_post_attachments') !== false,
	'content-single.php 没有调用文底附件渲染。'
);
argon_attachments_test_assert(
	strpos($settings_source, "name=\"argon_attachment_allowed_extensions\"") !== false
	&& strpos($settings_source, "name=\"argon_attachment_max_size\"") !== false
	&& strpos($settings_source, "name=\"argon_attachment_captcha_mode\"") !== false
	&& strpos($settings_source, "argon_update_option('argon_attachment_allowed_extensions')") !== false
	&& strpos($settings_source, "argon_update_option('argon_attachment_max_size')") !== false
	&& strpos($settings_source, "argon_update_option('argon_attachment_captcha_mode')") !== false,
	'settings.php 缺少附件白名单/大小上限/验证码设置字段或保存调用。'
);
argon_attachments_test_assert(
	strpos($attachments_source, 'argon_attachment_is_captcha_enabled') !== false,
	'attachments.php 缺少 argon_attachment_is_captcha_enabled 判断函数。'
);

// 下载端点：验签、计数节流、no-store、302 跳直链
argon_attachments_test_assert(
	strpos($attachments_source, "add_action('template_redirect', 'argon_attachment_download_endpoint'") !== false,
	'下载端点没有挂到 template_redirect。'
);
argon_attachments_test_assert(
	strpos($attachments_source, "hash_hmac('sha256'") !== false
	&& strpos($attachments_source, 'hash_equals') !== false,
	'下载链接缺少 HMAC 签名或常数时间比较。'
);
argon_attachments_test_assert(
	strpos($attachments_source, 'get_transient($throttle_key)') !== false
	&& strpos($attachments_source, 'set_transient($throttle_key') !== false,
	'下载端点缺少同 IP 计数节流。'
);
argon_attachments_test_assert(
	strpos($attachments_source, 'no-store') !== false
	&& strpos($attachments_source, 'readfile(') !== false,
	'下载端点缺少 no-store 头或直接文件下发。'
);

// 安全：硬性黑名单、目录防护、回环验证
argon_attachments_test_assert(
	strpos($attachments_source, 'argon_attachments_denied_extensions') !== false
	&& strpos($attachments_source, "'svg'") !== false
	&& strpos($attachments_source, "'php'") !== false,
	'上传缺少可执行/可渲染格式的硬性黑名单。'
);
argon_attachments_test_assert(
	strpos($attachments_source, 'argon_attachments_verify_htaccess') !== false,
	'.htaccess 写入后缺少回环验证（AllowOverride 受限主机会整目录 500）。'
);

// 数据：全局索引、清理（必须挂在 before_delete_post，此时 meta 尚未删除）
argon_attachments_test_assert(
	strpos($attachments_source, 'ARGON_ATTACHMENTS_INDEX_OPTION') !== false
	&& strpos($attachments_source, "add_action('before_delete_post', 'argon_attachments_cleanup_post')") !== false
	&& strpos($attachments_source, "add_action('delete_post', 'argon_attachments_cleanup_post')") === false,
	'缺少 uuid 全局索引，或清理钩子挂错位置（delete_post 时 meta 已删除，索引会残留）。'
);

// 录入与展示
argon_attachments_test_assert(
	strpos($attachments_source, "add_shortcode('attachment'") !== false
	&& strpos($attachments_source, "add_shortcode('attachments'") !== false
	&& strpos($attachments_source, "add_shortcode('argon_attachment'") === false
	&& strpos($attachments_source, "add_shortcode('argon_attachments'") === false
	&& strpos($attachments_source, "add_meta_box(") !== false,
	'短代码应注册为无前缀的 [attachment]/[attachments]（对齐 WP 核心惯例），不再保留 argon_ 前缀注册。'
);

// 面向用户的可寻址性：name 软引用 + 重名去重 + 编辑器插入按钮
argon_attachments_test_assert(
	strpos($attachments_source, "'name' => ''") !== false
	&& strpos($attachments_source, 'function argon_attachments_find_by_name') !== false
	&& strpos($attachments_source, 'function argon_attachments_unique_name') !== false
	&& substr_count($attachments_source, 'argon_attachments_unique_name(') >= 3,
	'短代码缺少 name 软引用支持，或上传/改名路径没有接入展示名去重。'
);
argon_attachments_test_assert(
	strpos($attachments_source, 'argon-att-admin-insert') !== false
	&& strpos($attachments_source, 'data-style="card"') !== false
	&& strpos($attachments_source, 'argon-att-admin-dup') !== false,
	'附件 meta box 缺少「插入卡片/插入链接」按钮或重名警示标记。'
);

// PJAX：资源随标记内联输出，不走条件入队
argon_attachments_test_assert(
	strpos($attachments_source, 'argon_attachments_assets_html') !== false
	&& strpos($attachments_source, 'data-pjax') !== false
	&& strpos($attachments_source, "add_action('wp_enqueue_scripts', 'argon_enqueue_attachment_assets'") === false,
	'前端资源应随附件标记内联输出以兼容 PJAX。'
);

// 下载入口：页面不内置任何下载 URL，按钮携带 data-uuid，点击时由 JS 鉴权下发并在新窗口中触发下载
argon_attachments_test_assert(
	strpos($attachments_source, 'argon_attachment_download_url') !== false
	&& strpos($attachments_source, 'data-uuid="') !== false,
	'前端下载入口应生成携带 data-uuid 的无内置链接标准按钮。'
);
argon_attachments_test_assert(
	strpos($attachments_source, "add_action('wp_ajax_nopriv_argon_attachment_issue'") !== false
	&& strpos($attachments_source, 'delete_transient($token_key)') !== false
	&& strpos($attachments_source, 'argon_dl_issue_') !== false,
	'缺少一次性凭证签发端点（公开访问、用后作废、签发限流）。'
);
argon_attachments_test_assert(
	file_exists($root . '/page-download.php'),
	'缺少独立下载中转与承载页 page-download.php。'
);

// 预览：渲染器模块、modal、卡片属性、级联清理
$preview_source = file_get_contents($root . '/inc/attachment-preview.php');
$js_source = file_get_contents($root . '/assets/js/attachments.js');
argon_attachments_test_assert(
	strpos($attachments_source, "require_once get_template_directory() . '/inc/attachment-preview.php'") !== false,
	'attachments.php 没有引入预览渲染器模块。'
);
argon_attachments_test_assert(
	strpos($preview_source, "add_action('wp_ajax_nopriv_argon_attachment_preview_open'") !== false
	&& strpos($preview_source, 'filesize($file_path') !== false
	&& strpos($preview_source, 'filemtime($file_path') !== false,
	'预览打开端点或快照指纹（大小+mtime 失效）缺失。'
);
argon_attachments_test_assert(
	strpos($preview_source, 'setSafeMode(true)') !== false
	&& strpos($preview_source, 'setMarkupEscaped(true)') !== false,
	'Markdown 快照没有开启 Parsedown 安全模式。'
);
argon_attachments_test_assert(
	strpos($attachments_source, 'argon-attachment-preview-modal') !== false
	&& strpos($attachments_source, 'argon-attachment-previewable') !== false
	&& strpos($attachments_source, 'data-ext=') !== false,
	'卡片缺少预览入口或共享弹窗未随资源输出。'
);
argon_attachments_test_assert(
	substr_count($attachments_source, 'argon_attachment_snapshot_purge') >= 2,
	'附件删除/文章删除路径没有级联清理预览快照。'
);
argon_attachments_test_assert(
	strpos($js_source, 'argon_attachment_preview_open') !== false
	&& (strpos($js_source, '.argon-zip-item') !== false || strpos($js_source, '.dir-label') !== false),
	'前端缺少预览打开逻辑或目录树折叠交互。'
);

/* ---------------------------------------------------------------------------
 * 第二部分：最小 WordPress 桩环境下的行为测试
 * ------------------------------------------------------------------------- */

define('ABSPATH', __DIR__);
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);

$root_dir_for_stub = $root;
$GLOBALS['argon_test_root'] = $root;

$GLOBALS['test_options'] = array();
$GLOBALS['test_post_meta'] = array();
$GLOBALS['test_transients'] = array();
$GLOBALS['test_uploads_tmp'] = sys_get_temp_dir() . '/argon-att-test-' . getmypid();

function add_action($hook, $callback, $priority = 10) {}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}
function add_shortcode($tag, $callback) {}
function get_option($name, $default = false) {
	return array_key_exists($name, $GLOBALS['test_options']) ? $GLOBALS['test_options'][$name] : $default;
}
function update_option($name, $value, $autoload = null) {
	$GLOBALS['test_options'][$name] = $value;
	return true;
}
function get_post_meta($post_id, $key, $single) {
	return isset($GLOBALS['test_post_meta'][$post_id][$key]) ? $GLOBALS['test_post_meta'][$post_id][$key] : '';
}
function update_post_meta($post_id, $key, $value) {
	$GLOBALS['test_post_meta'][$post_id][$key] = $value;
	return true;
}
function delete_post_meta($post_id, $key) {
	unset($GLOBALS['test_post_meta'][$post_id][$key]);
	return true;
}
function get_transient($name) {
	$record = isset($GLOBALS['test_transients'][$name]) ? $GLOBALS['test_transients'][$name] : null;
	if ($record === null || $record['expires'] < time()) return false;
	return $record['value'];
}
function set_transient($name, $value, $ttl) {
	$GLOBALS['test_transients'][$name] = array('value' => $value, 'expires' => time() + $ttl);
	return true;
}
function wp_salt($scheme = 'auth') { return 'test-salt-for-attachments'; }
function home_url($path = '/') { return 'https://example.test' . $path; }
function add_query_arg() {
	$args = func_get_args();
	if (count($args) === 2) {
		$param = $args[0];
		$url = $args[1];
		$query = is_array($param) ? http_build_query($param) : '';
	} elseif (count($args) === 3) {
		$query = http_build_query(array($args[0] => $args[1]));
		$url = $args[2];
	} else {
		return home_url('/');
	}
	return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
}
function esc_url($url) { return $url; }
function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_attr__($s, $d = null) { return $s; }
function esc_html__($s, $d = null) { return $s; }
function __($s, $d = null) { return $s; }
function sanitize_text_field($s) { return trim(strip_tags((string)$s)); }
function wp_unslash($v) { return $v; }
function absint($v) { return abs(intval($v)); }
function trailingslashit($s) { return rtrim($s, '/\\') . '/'; }
function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, (array)$args); }
function wp_generate_uuid4() { return '12345678-1234-4234-8234-123456789abc'; }
function size_format($bytes, $decimals = 1) { return round($bytes / 1048576, $decimals) . ' MB'; }
function wp_upload_dir() {
	return array('basedir' => $GLOBALS['test_uploads_tmp'], 'baseurl' => 'https://example.test/wp-content/uploads', 'error' => false);
}
function wp_mkdir_p($dir) { return is_dir($dir) || @mkdir($dir, 0777, true); }
function wp_rand($min = 0, $max = 0) { return mt_rand($min, $max); }
function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
	return 'tok_' . bin2hex(random_bytes(8));
}
function delete_transient($name) {
	unset($GLOBALS['test_transients'][$name]);
	return true;
}
class Argon_Attachment_Test_Json extends Exception {
	public $payload;
	public $status;
	public function __construct($payload, $status) {
		parent::__construct('json');
		$this->payload = $payload;
		$this->status = $status;
	}
}
// wp_send_json_* 正常会 exit，用异常模拟中断
function wp_send_json_success($data = null, $status = 200) {
	throw new Argon_Attachment_Test_Json(array('success' => true, 'data' => $data), $status);
}
function wp_send_json_error($data = null, $status = 200) {
	throw new Argon_Attachment_Test_Json(array('success' => false, 'data' => $data), $status);
}
function get_the_ID() { return 123; }
function post_password_required($post_id = null) { return false; }
function current_user_can($cap, $post_id = null) { return true; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/argon'; }
function get_template_directory() { return $GLOBALS['argon_test_root']; }
function argon_get_asset_file_version($path) { return '1.0'; }
function wp_remote_head($url, $args = array()) { return array('response' => array('code' => 200)); }
function wp_remote_retrieve_response_code($response) { return 200; }
function is_wp_error($thing) { return $thing instanceof WP_Error; }
class WP_Error {}
function check_ajax_referer($action, $query_arg = false, $die = true) { return true; }
function get_post_type($post_id = null) { return 'post'; }
function shortcode_atts($defaults, $attr) { return array_merge($defaults, (array)$attr); }

class Argon_Attachment_Test_Die extends Exception {
	public $status;
	public function __construct($message, $status) {
		parent::__construct($message);
		$this->status = $status;
	}
}
class Argon_Attachment_Test_Redirect extends Exception {
	public $url;
	public $status;
	public function __construct($url, $status) {
		parent::__construct($url);
		$this->url = $url;
		$this->status = $status;
	}
}
function wp_die($message = '', $title = '', $args = array()) {
	$status = is_array($args) && isset($args['response']) ? $args['response'] : 500;
	throw new Argon_Attachment_Test_Die($message, $status);
}
// 用异常模拟 302 之后的 exit，便于测试捕获
function wp_redirect($url, $status = 302) {
	throw new Argon_Attachment_Test_Redirect($url, $status);
}

if (!defined('ARGON_TESTING')) {
	define('ARGON_TESTING', true);
}
require_once $root . '/inc/attachments.php';

$uuid = '12345678-1234-1234-1234-123456789abc';

// 1. 标准承载页 URL 与签名直链 URL 闭环
$page_url = argon_attachment_download_url($uuid);
parse_str(parse_url($page_url, PHP_URL_QUERY), $page_query);
argon_attachments_test_assert(
	isset($page_query['download']) && $page_query['download'] === $uuid && !isset($page_query['sign']),
	'下载承载页 URL 必须是纯净的 ?download=uuid。'
);

$signed_url = argon_attachment_signed_download_url($uuid);
parse_str(parse_url($signed_url, PHP_URL_QUERY), $query);
argon_attachments_test_assert(
	isset($query['download'], $query['expires'], $query['sign'])
	&& $query['download'] === $uuid,
	'签名直链 URL 缺少 download/expires/sign 参数。'
);
argon_attachments_test_assert(
	argon_attachment_verify_url($query['download'], $query['expires'], $query['sign']) === true,
	'合法签名没有通过验签。'
);
argon_attachments_test_assert(
	argon_attachment_verify_url($query['download'], $query['expires'], strrev($query['sign'])) === false,
	'被篡改的签名居然通过了验签。'
);
argon_attachments_test_assert(
	argon_attachment_verify_url($uuid, time() - 10, argon_attachment_sign($uuid, time() - 10)) === false,
	'已过期的签名居然通过了验签。'
);
argon_attachments_test_assert(
	argon_attachment_verify_url('not-a-uuid', $query['expires'], $query['sign']) === false,
	'非法 uuid 格式居然通过了验签。'
);

// 2. 索引与数据闭环
$disk_name = 'abcdef0123456789abcdef0123456789.zip';
$dir = argon_attachments_post_dir(123);
mkdir($dir, 0777, true);
file_put_contents($dir . '/' . $disk_name, 'zip-bytes');

$GLOBALS['test_post_meta'][123][ARGON_ATTACHMENTS_META_KEY] = array(
	$uuid => array(
		'name' => '发布包 v1.2.zip',
		'file' => $disk_name,
		'size' => 1048576,
		'ext' => 'zip',
		'downloads' => 0,
		'time' => time(),
	),
);
argon_attachments_index_add($uuid, 123);

$located = argon_attachments_locate($uuid);
argon_attachments_test_assert(
	$located !== null && $located[0] === 123 && $located[1]['name'] === '发布包 v1.2.zip',
	'全局索引无法定位附件。'
);

// 3. 下载端点：携带签名或 token 时，发送 Content-Disposition 标头并直接输出文件流
$_GET = array('download' => $uuid, 'expires' => $query['expires'], 'sign' => $query['sign']);
$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

ob_start();
try {
	argon_attachment_download_endpoint();
} catch (Exception $e) {}
$downloaded_body = ob_get_clean();

argon_attachments_test_assert(
	$downloaded_body === 'zip-bytes',
	'下载端点未正确输出文件流内容。'
);

$meta = $GLOBALS['test_post_meta'][123][ARGON_ATTACHMENTS_META_KEY];
argon_attachments_test_assert($meta[$uuid]['downloads'] === 1, '首次下载没有计数。');

ob_start();
try {
	argon_attachment_download_endpoint();
} catch (Exception $e) {}
ob_end_clean();
$meta = $GLOBALS['test_post_meta'][123][ARGON_ATTACHMENTS_META_KEY];
argon_attachments_test_assert($meta[$uuid]['downloads'] === 1, '节流窗口内重复下载被重复计数。');
argon_attachments_test_assert($meta[$uuid]['downloads'] === 1, '节流窗口内重复下载被重复计数。');

// 4. 验签失败 → 403
$_GET['sign'] = strrev($query['sign']);
$died = null;
try {
	argon_attachment_download_endpoint();
} catch (Argon_Attachment_Test_Die $d) {
	$died = $d->status;
}
argon_attachments_test_assert($died === 403, '验签失败没有返回 403，实际：' . var_export($died, true));

// 4.5 一次性凭证：签发 → 使用 → 复用拒绝
$_POST = array('uuid' => $uuid);
$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
$issued = null;
try {
	argon_attachment_ajax_issue();
} catch (Argon_Attachment_Test_Json $j) {
	$issued = $j;
}
argon_attachments_test_assert(
	$issued !== null && $issued->payload['success'] === true && strpos($issued->payload['data']['url'], 'token=') !== false,
	'签发端点没有返回带一次性 token 的下载地址。'
);

	parse_str(parse_url($issued->payload['data']['url'], PHP_URL_QUERY), $issued_query);
	$_GET = array('download' => $uuid, 'token' => $issued_query['token']);
	ob_start();
	try {
		argon_attachment_download_endpoint();
	} catch (Exception $e) {}
	$token_downloaded_body = ob_get_clean();

	argon_attachments_test_assert(
		$token_downloaded_body === 'zip-bytes',
		'一次性凭证下载未正确下发文件流。'
	);
	// 与签名路径共用同 IP 节流窗口，计数仍应为 1
	$meta = $GLOBALS['test_post_meta'][123][ARGON_ATTACHMENTS_META_KEY];
	argon_attachments_test_assert($meta[$uuid]['downloads'] === 1, '节流窗口被一次性凭证路径绕过。');

// 同一 token 再次使用 → 403（一次有效）
$died = null;
try {
	argon_attachment_download_endpoint();
} catch (Argon_Attachment_Test_Die $d) {
	$died = $d->status;
}
argon_attachments_test_assert($died === 403, '一次性凭证被重复使用居然放行，实际：' . var_export($died, true));

// 签发限流：每分钟 30 次后 → 429
set_transient('argon_dl_issue_' . md5('203.0.113.10'), 30, 60);
$limited = null;
try {
	argon_attachment_ajax_issue();
} catch (Argon_Attachment_Test_Json $j) {
	$limited = $j;
}
argon_attachments_test_assert(
	$limited !== null && $limited->status === 429 && $limited->payload['success'] === false,
	'签发端点没有触发每 IP 限流。'
);
unset($GLOBALS['test_transients']['argon_dl_issue_' . md5('203.0.113.10')]);

// 5. 未知 uuid（签名合法）→ 404
$ghost = '99999999-9999-4999-8999-999999999999';
$ghost_expires = time() + 600;
$_GET = array('download' => $ghost, 'expires' => $ghost_expires, 'sign' => argon_attachment_sign($ghost, $ghost_expires));
$died = null;
try {
	argon_attachment_download_endpoint();
} catch (Argon_Attachment_Test_Die $d) {
	$died = $d->status;
}
argon_attachments_test_assert($died === 404, '未知附件没有返回 404，实际：' . var_export($died, true));

// 6. 文章删除清理：目录与索引同步移除
argon_attachments_cleanup_post(123);
argon_attachments_test_assert(!is_dir($dir), '删除文章后附件目录没有被清理。');
argon_attachments_test_assert(argon_attachments_locate($uuid) === null, '删除文章后 uuid 索引没有被清理。');

// 7. 白名单默认值不含 svg，硬性黑名单生效
$allowed = argon_attachments_allowed_extensions();
argon_attachments_test_assert(
	in_array('zip', $allowed, true) && !in_array('svg', $allowed, true),
	'默认白名单异常（应包含 zip、不含 svg）。'
);
argon_attachments_test_assert(
	in_array('php', argon_attachments_denied_extensions(), true)
	&& in_array('html', argon_attachments_denied_extensions(), true),
	'硬性黑名单缺少 php/html。'
);

// 验证不设限模式：常规扩展名（iso, torrent, whl, json, 任意格式）均允许，黑名单（php, svg）被拦截
argon_attachments_test_assert(
	argon_attachments_is_allowed_extension('iso')
	&& argon_attachments_is_allowed_extension('torrent')
	&& argon_attachments_is_allowed_extension('whl')
	&& argon_attachments_is_allowed_extension('json')
	&& argon_attachments_is_allowed_extension('customext'),
	'不设限模式下应允许任意常规格式。'
);
argon_attachments_test_assert(
	!argon_attachments_is_allowed_extension('php')
	&& !argon_attachments_is_allowed_extension('phtml')
	&& !argon_attachments_is_allowed_extension('svg'),
	'安全黑名单格式必须被拦截。'
);

// 验证 wp_check_filetype_and_ext 对任意格式的放行行为
$check_sample = argon_attachments_check_filetype_and_ext(
	array('ext' => false, 'type' => false, 'proper_filename' => false),
	'/tmp/test.iso',
	'test.iso',
	array()
);
argon_attachments_test_assert(
	$check_sample['ext'] === 'iso' && !empty($check_sample['type']),
	'wp_check_filetype_and_ext 应为 upload_files 用户放行常规任意格式。'
);
$check_php = argon_attachments_check_filetype_and_ext(
	array('ext' => false, 'type' => false, 'proper_filename' => false),
	'/tmp/bad.php',
	'bad.php',
	array()
);
argon_attachments_test_assert(
	empty($check_php['ext']),
	'wp_check_filetype_and_ext 不应放行 php 脚本。'
);

// 8. zip 目录树：深度限制、溢出计数、系统垃圾过滤
$tree = argon_attachment_zip_build_tree(array(
	array('name' => 'readme.txt', 'size' => 10),
	array('name' => 'src/', 'size' => 0),
	array('name' => 'src/app/', 'size' => 0),
	array('name' => 'src/app/deep/', 'size' => 0),
	array('name' => 'src/app/deep/buried.txt', 'size' => 5),   // 4 层 → 计入第 3 层溢出
	array('name' => 'src/app/deep/buried2.txt', 'size' => 6),
	array('name' => '__MACOSX/junk.txt', 'size' => 1),          // 过滤
	array('name' => 'src/tool.exe', 'size' => 100),
));
$deep_overflow = isset($tree['dirs']['src']['dirs']['app']['dirs']['deep']['overflow'])
	? $tree['dirs']['src']['dirs']['app']['dirs']['deep']['overflow'] : -1;
argon_attachments_test_assert($deep_overflow === 2, 'zip 树第 3 层溢出计数错误，实际：' . $deep_overflow);
argon_attachments_test_assert(
	!isset($tree['dirs']['__MACOSX']) && isset($tree['dirs']['src']),
	'zip 树没有过滤 __MACOSX。'
);
	$zip_html = argon_attachment_zip_tree_html($tree, 5);
	argon_attachments_test_assert(
		strpos($zip_html, 'argon-zip-explorer') !== false
		&& strpos($zip_html, 'data-tree=') !== false
		&& strpos($zip_html, '<html') === false
		&& strpos($zip_html, '共 5 个条目') !== false,
		'zip 快照应是现代文件资源管理器结构 HTML 片段（含 argon-zip-explorer、data-tree，不含完整文档骨架）。'
	);

	// 8.5 压缩包字符集编码与格式统一（ZIP/RAR/TAR/7Z 统一为文件资源管理器）
	$gbk_name = function_exists('iconv') ? @iconv('UTF-8', 'GBK', '中文目录/测试文档.txt') : '中文目录/测试文档.txt';
	$gbk_tree = argon_attachment_zip_build_tree(array(
		array('name' => $gbk_name, 'size' => 1024),
	));
	$gbk_json = json_encode($gbk_tree, JSON_UNESCAPED_UNICODE);
	argon_attachments_test_assert(
		$gbk_json !== false && strpos($gbk_json, '中文目录') !== false && strpos($gbk_json, '测试文档.txt') !== false,
		'GBK 编码 zip 文件名未成功转换为 UTF-8，json_encode 失败或乱码。'
	);

	// 8.6 统一压缩包 TAR 提取测试
	$tar_file = $GLOBALS['test_uploads_tmp'] . '/unit-test.tar';
	if (class_exists('PharData')) {
		@unlink($tar_file);
		$phar = new PharData($tar_file);
		$phar->addFromString('folder/doc.txt', 'file content');
		$tar_entries = argon_attachment_archive_extract_entries($tar_file, 'tar');
		argon_attachments_test_assert(
			is_array($tar_entries) && !empty($tar_entries) && strpos($tar_entries[0]['name'], 'folder/doc.txt') !== false,
			'TAR 压缩包条目提取失败。'
		);
		@unlink($tar_file);
	}

// 9. 快照缓存：生成 → 命中 → 源文件变化后重建并清除旧快照
$md_file = $GLOBALS['test_uploads_tmp'] . '/preview-src.md';
file_put_contents($md_file, "# 预览标题\n\n正文 **加粗** 内容。\n");
clearstatcache(true, $md_file);
$att_md = array('ext' => 'md', 'name' => 'demo.md');
$snap_uuid = '11111111-2222-4333-8444-555555555555';

$path1 = argon_attachment_generate_snapshot($snap_uuid, $md_file, $att_md);
argon_attachments_test_assert($path1 !== false && is_file($path1), 'md 快照没有生成。');
$content1 = $path1 !== false ? file_get_contents($path1) : '';
argon_attachments_test_assert(
	strpos($content1, '<h1') !== false && strpos($content1, '加粗') !== false,
	'md 快照内容渲染异常。'
);
$mtime1 = $path1 !== false ? filemtime($path1) : 0;
sleep(1);
$path2 = argon_attachment_generate_snapshot($snap_uuid, $md_file, $att_md);
argon_attachments_test_assert(
	$path2 === $path1 && filemtime($path2) === $mtime1,
	'第二次打开没有命中快照缓存（文件被重新生成）。'
);

file_put_contents($md_file, "# 预览标题 v2\n\n内容变了，长度也变了。\n");
clearstatcache(true, $md_file);
$path3 = argon_attachment_generate_snapshot($snap_uuid, $md_file, $att_md);
argon_attachments_test_assert(
	$path3 !== $path1 && is_file($path3) && !is_file($path1),
	'源文件变化后快照没有重建或旧快照没有清理。'
);

// 快照级联清理
argon_attachment_snapshot_purge($snap_uuid);
$leftover = glob($GLOBALS['test_uploads_tmp'] . '/argon-attachments/_preview-cache/' . $snap_uuid . '.*');
argon_attachments_test_assert(empty($leftover), '快照清理后仍有残留文件。');

// 10. 展示名去重 unique_name：无冲突原样、冲突 -1、递增、大小写不敏感、无扩展名
argon_attachments_test_assert(
	argon_attachments_unique_name(array('a.pdf'), 'b.pdf') === 'b.pdf',
	'unique_name：无冲突时应原样返回。'
);
argon_attachments_test_assert(
	argon_attachments_unique_name(array('report.pdf'), 'REPORT.pdf') === 'REPORT-1.pdf',
	'unique_name：大小写不敏感冲突应追加 -1 后缀。'
);
argon_attachments_test_assert(
	argon_attachments_unique_name(array('report.pdf', 'report-1.pdf'), 'report.pdf') === 'report-2.pdf',
	'unique_name：已有 -1 时应递增到 -2。'
);
argon_attachments_test_assert(
	argon_attachments_unique_name(array('README'), 'README') === 'README-1',
	'unique_name：无扩展名文件应在末尾直接追加后缀。'
);

// 11. 改名/排序去重：服务端自动追加后缀并在响应里回传最终名
$uuid_a = 'aaaaaaaa-0000-4000-8000-000000000001';
$uuid_b = 'bbbbbbbb-0000-4000-8000-000000000002';
$uuid_c = 'cccccccc-0000-4000-8000-000000000003';
$GLOBALS['test_post_meta'][123][ARGON_ATTACHMENTS_META_KEY] = array(
	$uuid_a => array('name' => '报告.pdf', 'file' => 'a.pdf', 'size' => 1, 'ext' => 'pdf', 'downloads' => 0, 'time' => time()),
	$uuid_b => array('name' => '数据.zip', 'file' => 'b.zip', 'size' => 1, 'ext' => 'zip', 'downloads' => 0, 'time' => time()),
);
$_POST = array(
	'post_id' => 123,
	'order' => array($uuid_a, $uuid_b),
	'names' => array($uuid_a => '报告.pdf', $uuid_b => '报告.pdf'),
);
$update_resp = null;
try {
	argon_attachment_ajax_update();
} catch (Argon_Attachment_Test_Json $j) {
	$update_resp = $j;
}
argon_attachments_test_assert(
	$update_resp !== null && $update_resp->payload['success'] === true
	&& isset($update_resp->payload['data']['names'][$uuid_b])
	&& $update_resp->payload['data']['names'][$uuid_b] === '报告-1.pdf',
	'同名改名没有被自动追加后缀，或响应未回传最终名。'
);
$meta = $GLOBALS['test_post_meta'][123][ARGON_ATTACHMENTS_META_KEY];
argon_attachments_test_assert(
	$meta[$uuid_a]['name'] === '报告.pdf' && $meta[$uuid_b]['name'] === '报告-1.pdf',
	'改名去重后的落库结果不正确。'
);

// 12. name 软引用定位：大小写不敏感、重名取第一、#N 消歧、越界 null
$GLOBALS['test_post_meta'][123][ARGON_ATTACHMENTS_META_KEY] = array(
	$uuid_a => array('name' => '报告.pdf', 'file' => 'a.pdf', 'size' => 1, 'ext' => 'pdf', 'downloads' => 0, 'time' => time()),
	$uuid_b => array('name' => '报告.pdf', 'file' => 'b.pdf', 'size' => 1, 'ext' => 'pdf', 'downloads' => 0, 'time' => time()),
	$uuid_c => array('name' => '数据.zip', 'file' => 'c.zip', 'size' => 1, 'ext' => 'zip', 'downloads' => 0, 'time' => time()),
);
$found = argon_attachments_find_by_name(123, '报告.PDF');
argon_attachments_test_assert(
	$found !== null && $found[0] === $uuid_a,
	'find_by_name：大小写不敏感命中或重名取第一失效。'
);
$found = argon_attachments_find_by_name(123, '报告.pdf#2');
argon_attachments_test_assert(
	$found !== null && $found[0] === $uuid_b,
	'find_by_name：#N 消歧应命中第 N 个同名附件。'
);
argon_attachments_test_assert(
	argon_attachments_find_by_name(123, '报告.pdf#3') === null
	&& argon_attachments_find_by_name(123, '不存在.txt') === null,
	'find_by_name：#N 越界或不存在的名字应返回 null。'
);

// 13. 短代码渲染：id 优先于 name；name 命中渲染卡片；找不到给出编辑者可见提示；复数短代码不受影响
argon_attachments_index_add($uuid_c, 123);
$by_id = argon_attachment_shortcode(array('id' => $uuid_c, 'name' => '报告.pdf'));
argon_attachments_test_assert(
	strpos($by_id, '数据.zip') !== false && strpos($by_id, '报告.pdf') === false,
	'短代码 id 与 name 同时存在时应优先使用 id。'
);
$by_name = argon_attachment_shortcode(array('name' => '报告.pdf#2'));
argon_attachments_test_assert(
	strpos($by_name, 'data-uuid="' . $uuid_b . '"') !== false,
	'短代码 name 软引用未命中预期附件。'
);
$link_html = argon_attachment_shortcode(array('name' => '数据.zip', 'style' => 'link'));
argon_attachments_test_assert(
	strpos($link_html, 'argon-attachment-inline-link') !== false,
	'style="link" 应渲染行内链接。'
);
$missing = argon_attachment_shortcode(array('name' => '不存在.txt'));
argon_attachments_test_assert(
	strpos($missing, 'argon-attachment-missing') !== false,
	'找不到的 name 应输出编辑者可见的缺失提示。'
);
$list_html = argon_attachments_shortcode(array());
argon_attachments_test_assert(
	strpos($list_html, 'argon-attachments-list') !== false
	&& strpos($list_html, '报告.pdf') !== false
	&& strpos($list_html, '数据.zip') !== false,
	'[attachments] 复数短代码应渲染当前文章全部附件。'
);

// 清理临时目录
foreach (glob($GLOBALS['test_uploads_tmp'] . '/argon-attachments/123/*') ?: array() as $f) { @unlink($f); }
foreach (glob($GLOBALS['test_uploads_tmp'] . '/argon-attachments/_preview-cache/*') ?: array() as $f) { @unlink($f); }
@rmdir($GLOBALS['test_uploads_tmp'] . '/argon-attachments/123');
@rmdir($GLOBALS['test_uploads_tmp'] . '/argon-attachments/_preview-cache');
@rmdir($GLOBALS['test_uploads_tmp'] . '/argon-attachments');
@unlink($GLOBALS['test_uploads_tmp'] . '/preview-src.md');
@rmdir($GLOBALS['test_uploads_tmp']);

/* ------------------------------------------------------------------------- */

if (empty($failures)) {
	echo "OK：附件下载模块全部契约与行为测试通过。\n";
	exit(0);
}
echo "失败 " . count($failures) . " 项：\n";
foreach ($failures as $failure) {
	echo " - " . $failure . "\n";
}
exit(1);
