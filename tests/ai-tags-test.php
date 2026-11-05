<?php
/** 运行：php tests/ai-tags-test.php。WordPress 桩环境下执行真实整理函数及 AJAX。 */
const HOUR_IN_SECONDS = 3600;
class WP_Error {
	public $code;
	private $message;
	function __construct($code, $message) { $this->code = $code; $this->message = $message; }
	function get_error_message() { return $this->message; }
}
class TagJsonResponse extends Exception {
	public $success;
	public $data;
	public $status;
	function __construct($success, $data, $status) { $this->success = $success; $this->data = $data; $this->status = $status; }
}
$terms = $relationships = $posts = $options = $transients = $writes = $ai_calls = array();
$ai_response = '{"merges":[]}';
$fail_write = $fail_read = $fail_delete = $noop_write = false;
$can_manage = $nonce_valid = true;
$user_id = 1;
$failures = array();
function check($condition, $message) {
	global $failures;
	if (!$condition) { $failures[] = $message; fwrite(STDERR, "FAIL: $message\n"); }
}
function __($message, $domain = '') { return $message; }
function add_action(...$args) {}
function is_wp_error($value) { return $value instanceof WP_Error; }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_json_encode($value) { return json_encode($value, JSON_UNESCAPED_UNICODE); }
function wp_unslash($value) { return $value; }
function get_current_user_id() { global $user_id; return $user_id; }
function current_user_can($cap) { global $can_manage; return $can_manage; }
function get_taxonomy($taxonomy) {
	return (object) array('cap' => (object) array('manage_terms' => 'manage_categories', 'delete_terms' => 'delete_categories', 'assign_terms' => 'edit_posts'));
}
function check_ajax_referer($action, $field) {
	global $nonce_valid;
	if (!$nonce_valid) wp_send_json_error(array('message' => 'bad nonce'), 403);
}
function wp_send_json_success($data) { throw new TagJsonResponse(true, $data, 200); }
function wp_send_json_error($data, $status = 200) { throw new TagJsonResponse(false, $data, $status); }
function wp_generate_uuid4() { return sprintf('12345678-1234-1234-1234-%012x', random_int(1, PHP_INT_MAX >> 16)); }
function get_option($key, $default = false) { global $options; return $options[$key] ?? $default; }
function add_option($key, $value, $deprecated = '', $autoload = null) {
	global $options;
	if (isset($options[$key])) return false;
	$options[$key] = $value;
	return true;
}
function delete_option($key) { global $options; unset($options[$key]); return true; }
function maybe_serialize($value) { return serialize($value); }
function wp_cache_delete(...$args) {}
class TagTestDatabase {
	public $options = 'wp_options';
	function delete($table, $where) {
		global $options;
		if (isset($options[$where['option_name']]) && serialize($options[$where['option_name']]) === $where['option_value']) {
			unset($options[$where['option_name']]);
			return 1;
		}
		return 0;
	}
}
$wpdb = new TagTestDatabase();
function get_transient($key) { global $transients; return $transients[$key] ?? false; }
function set_transient($key, $value, $ttl) { global $transients; $transients[$key] = $value; return true; }
function get_terms($args) {
	global $terms;
	check($args['taxonomy'] === 'post_tag' && $args['hide_empty'] === false, '目录必须限制文章标签并包含空标签');
	$result = array_values($terms);
	usort($result, function ($a, $b) { return ($b->count <=> $a->count) ?: ($a->term_id <=> $b->term_id); });
	return $result;
}
function get_term($id, $taxonomy) {
	global $terms;
	check($taxonomy === 'post_tag', '只能读取 post_tag');
	return isset($terms[$id]) ? clone $terms[$id] : null;
}
function get_posts($args) {
	global $posts;
	check($args['post_status'] === 'publish' && $args['post_type'] === 'post', '只向模型发送公开文章材料');
	return array_slice(array_values(array_filter($posts, function ($post) use ($args) {
		return $post->post_status === $args['post_status'] && $post->tag_id === $args['tag_id'];
	})), 0, $args['numberposts']);
}
function get_objects_in_term($id, $taxonomy) {
	global $relationships, $fail_read;
	check(is_int($id) && $taxonomy === 'post_tag', '读取关系须使用整数标签 ID');
	if ($fail_read) return new WP_Error('read', 'read failure');
	$result = array();
	foreach ($relationships as $post_id => $ids) if (in_array($id, $ids, true)) $result[] = (string) $post_id;
	return $result;
}
function wp_set_object_terms($object_id, $ids, $taxonomy, $append) {
	global $relationships, $writes, $fail_write, $noop_write;
	check(is_int($object_id) && count($ids) === 1 && is_int($ids[0]) && $append === true && $taxonomy === 'post_tag', '迁移必须按整数 ID 追加，不能替换其他标签或创建新标签');
	$writes[] = array($object_id, $ids);
	if ($fail_write) return new WP_Error('write', 'write failure');
	if (!$noop_write) $relationships[$object_id] = array_values(array_unique(array_merge($relationships[$object_id], $ids)));
	return $ids;
}
function wp_delete_term($id, $taxonomy, $args) {
	global $terms, $relationships, $fail_delete;
	check($taxonomy === 'post_tag' && $args['force_default'] === true && is_int($args['default']), '删除时需指定已有目标标签作为强制替代');
	foreach ($relationships as $ids) if (in_array($id, $ids, true)) check(in_array($args['default'], $ids, true), '全部关联成功迁移后才允许删除');
	if ($fail_delete) return new WP_Error('delete', 'delete failure');
	unset($terms[$id]);
	foreach ($relationships as &$ids) $ids = array_values(array_diff($ids, array($id)));
	return true;
}
function argon_ai_query($scenario, $prompt, $content, $context) {
	global $ai_response, $ai_calls;
	check($scenario === 'seo', '整理应复用 SEO API');
	$ai_calls[] = json_decode($content, true);
	return $ai_response;
}
function argon_decode_ai_json_response($raw, $expect_array = false) { return json_decode($raw, true); }
function argon_clean_post_content_for_seo($content, $max) { return mb_substr(strip_tags($content), 0, $max); }
require dirname(__DIR__) . '/inc/ai-tags.php';

function term_fixture($id, $name, $count) {
	global $terms;
	$terms[$id] = (object) array('term_id' => $id, 'name' => $name, 'slug' => 'tag-' . $id, 'count' => $count);
}
function merge_fixture() {
	global $terms, $relationships, $writes, $fail_write, $fail_read, $fail_delete, $noop_write;
	$terms = $relationships = $writes = array();
	$fail_write = $fail_read = $fail_delete = $noop_write = false;
	term_fixture(1, 'WordPress优化笔记', 1);
	term_fixture(100, 'WordPress', 8);
	term_fixture(7, '独立主题', 2);
	return argon_ai_tags_create_plan(3)['catalog'];
}
function ajax($callback, $data) {
	$_POST = $data;
	try { $callback(); } catch (TagJsonResponse $result) { return $result; }
	throw new Exception('AJAX did not send response');
}

echo ">>> 分批分析、完整目录与公开标题上下文\n";
for ($id = 1; $id <= 26; $id++) term_fixture($id, '专属标签' . $id, 1);
term_fixture(100, '常用主题', 10);
term_fixture(101, '空标签', 0);
$plan = argon_ai_tags_create_plan(1);
check(count($plan['candidates']) === 27 && count($plan['catalog']) === 28, '低频源与完整目标目录应分别筛选');
$posts = array(
	(object) array('post_status' => 'publish', 'post_password' => '', 'post_title' => '<b>公开标题</b>', 'tag_id' => 1),
	(object) array('post_status' => 'private', 'post_password' => '', 'post_title' => '私密标题', 'tag_id' => 1),
	(object) array('post_status' => 'publish', 'post_password' => 'secret', 'post_title' => '密码标题', 'tag_id' => 1),
);
$ai_response = '{"merges":[{"source_id":1,"target_id":100,"reason":"同义"}]}';
$plan = argon_ai_tags_analyze_batch($plan);
check(!is_wp_error($plan) && $plan['offset'] === 25 && !$plan['ready'], '每次只处理 25 个源');
check(count($ai_calls[0]['catalog']) === 28, '目标目录不得随源批次截断');
$input = wp_json_encode($ai_calls[0]);
check(strpos($input, '公开标题') !== false && strpos($input, '私密标题') === false && strpos($input, '密码标题') === false, '私密及密码标题不得发送到 AI');
$ai_response = '{"merges":[{"source_id":26,"target_id":100,"reason":"同义"}]}';
$plan = argon_ai_tags_analyze_batch($plan);
check($plan['ready'] && count($plan['merges']) === 2 && !$writes, '分析完成应只生成预览且不改文章');
check(count($ai_calls[1]['previous_merges']) === 1, '跨批建议需作为上下文发送');

echo ">>> 拒绝非法模型输出及跨批链/循环\n";
$catalog = merge_fixture();
$valid = array('source_id' => 1, 'target_id' => 100, 'reason' => '同义');
check(!is_wp_error(argon_ai_tags_validate_merges(array($valid), array(1), $catalog)), '合法建议应通过');
$invalid_rows = array(
	'not-array', array(array('source_id' => '1', 'target_id' => 100, 'reason' => 'bad')),
	array(array('source_id' => 1, 'target_id' => 999, 'reason' => 'bad')),
	array(array('source_id' => 7, 'target_id' => 100, 'reason' => 'bad')),
	array(array('source_id' => 1, 'target_id' => 1, 'reason' => 'bad')),
	array($valid, $valid),
	array($valid, array('source_id' => 100, 'target_id' => 7, 'reason' => 'chain')),
	array($valid, array('source_id' => 100, 'target_id' => 1, 'reason' => 'cycle')),
);
foreach ($invalid_rows as $rows) check(is_wp_error(argon_ai_tags_validate_merges($rows, array(1, 100), $catalog)), '必须拒绝越界、类型错误、重复、自合并、链和环');
check(is_wp_error(argon_ai_tags_validate_merges(array(array('source_id' => 100, 'target_id' => 7, 'reason' => 'chain')), array(100), $catalog, array(1 => $valid))), '必须拒绝跨批合并链');
$ai_response = 'not json';
check(is_wp_error(argon_ai_tags_analyze_batch(argon_ai_tags_create_plan(3))), '无效 JSON 应可恢复地失败');
$ai_response = false;
check(is_wp_error(argon_ai_tags_analyze_batch(argon_ai_tags_create_plan(3))), 'AI 请求失败不应生成计划');

echo ">>> 批量迁移所有状态文章，保留其他标签，完成后删除源\n";
$catalog = merge_fixture();
for ($id = 1; $id <= 205; $id++) $relationships[$id] = array(1, 7);
$result = argon_ai_tags_merge_batch($valid, $catalog);
check(!$result['done'] && $result['remaining'] === 105 && isset($terms[1]) && count($writes) === 100, '首批需保留源标签且不超过 100 次写入');
$result = argon_ai_tags_merge_batch($valid, $catalog);
check(!$result['done'] && $result['remaining'] === 5, '第二批应跳过已迁移关联');
$relationships[206] = array(1, 7); // 模拟预览之后新增的关联，不依赖公开文章 count。
$result = argon_ai_tags_merge_batch($valid, $catalog);
check($result['done'] && !isset($terms[1]) && count($writes) === 206, '所有状态和新增加的关联都应迁移');
foreach ($relationships as $ids) check($ids === array(7, 100), '独立主题标签必须保留');

echo ">>> 写入、校验、删除故障均保留源；支持重试\n";
$catalog = merge_fixture();
$relationships = array(1 => array(1, 7), 2 => array(1, 100));
$fail_write = true;
check(is_wp_error(argon_ai_tags_merge_batch($valid, $catalog)) && isset($terms[1]) && $relationships[1] === array(1, 7), '写入失败不得删源');
$fail_write = false;
$noop_write = true;
check(!argon_ai_tags_merge_batch($valid, $catalog)['done'] && isset($terms[1]), '写入返回成功但未落库时校验必须阻止删除');
$noop_write = false;
$fail_read = true;
check(is_wp_error(argon_ai_tags_merge_batch($valid, $catalog)) && isset($terms[1]), '读取失败不得删源');
$fail_read = false;
$fail_delete = true;
check(is_wp_error(argon_ai_tags_merge_batch($valid, $catalog)) && isset($terms[1]) && in_array(100, $relationships[1], true), '删除失败需保留原关联与已迁移关联');
$fail_delete = false;
check(argon_ai_tags_merge_batch($valid, $catalog)['done'], '失败后重试应完成剩余工作');
$catalog = merge_fixture();
$terms[100]->name = '重命名';
check(is_wp_error(argon_ai_tags_merge_batch($valid, $catalog)) && !$writes, '目标已改名则旧预览不得执行');
$catalog = merge_fixture();
unset($terms[100]);
check(is_wp_error(argon_ai_tags_merge_batch($valid, $catalog)) && isset($terms[1]), '目标已删除则不得执行');

echo ">>> AJAX 权限、预览绑定、过期、串行锁和幂等重试\n";
$catalog = merge_fixture();
$can_manage = false;
check(ajax('argon_ajax_ai_tags_preview', array())->status === 403, '无权限不得分析');
check(ajax('argon_ajax_ai_tags_apply', array())->status === 403, '无权限不得执行');
$can_manage = true;
$nonce_valid = false;
check(ajax('argon_ajax_ai_tags_preview', array())->status === 403, 'nonce 失效不得调用 AI');
$nonce_valid = true;
$ai_response = wp_json_encode(array('merges' => array($valid)));
$reply = ajax('argon_ajax_ai_tags_preview', array('max_count' => 3));
check($reply->success && $reply->data['done'] && count($reply->data['merges']) === 1 && !$writes, '预览接口应返回可勾选建议，不写入');
$token = $reply->data['token'];
$request = array('token' => $token, 'source_id' => 1, 'target_id' => 7); // 客户端伪造目标必须被忽略。
$user_id = 2;
check(!ajax('argon_ajax_ai_tags_apply', $request)->success, '计划不能被其他管理员会话执行');
$user_id = 1;
check(!ajax('argon_ajax_ai_tags_apply', array('token' => $token, 'source_id' => 7))->success, '不能执行不在预览中的源');
$options['argon_ai_tags_merge_lock'] = array('token' => 'other', 'expires' => time() + 60);
check(!ajax('argon_ajax_ai_tags_apply', $request)->success && isset($terms[1]), '并发执行必须阻止');
$options['argon_ai_tags_merge_lock']['expires'] = time() - 1;
$relationships[999] = array(1, 7);
$reply = ajax('argon_ajax_ai_tags_apply', $request);
check($reply->success && $reply->data['done'] && $relationships[999] === array(7, 100), '过期锁可恢复且只能执行服务端目标');
check(!isset($options['argon_ai_tags_merge_lock']), '完成后必须释放锁');
$before = count($writes);
check(ajax('argon_ajax_ai_tags_apply', $request)->success && count($writes) === $before, '丢失响应后的重试必须幂等');
$transients = array();
check(!ajax('argon_ajax_ai_tags_apply', $request)->success && !isset($options['argon_ai_tags_merge_lock']), '过期计划拒绝执行并释放锁');

if ($failures) { fwrite(STDERR, count($failures) . " assertions failed\n"); exit(1); }
echo "[PASS] AI tag cleanup behavior and AJAX regression tests\n";
