<?php
/**
 * AI 文章 SEO 自动优化功能的全面契约与单元测试
 * 运行方式: php tests/ai-seo-test.php
 */

$test_options = [];
$test_post_meta = [];
$test_posts = [];
$test_tags = [];

function get_option($name, $default = false) {
    global $test_options;
    return array_key_exists($name, $test_options) ? $test_options[$name] : $default;
}

function update_option($name, $value) {
    global $test_options;
    $test_options[$name] = $value;
    return true;
}

function get_post_meta($post_id, $key, $single = false) {
    global $test_post_meta;
    if (isset($test_post_meta[$post_id][$key])) {
        return $test_post_meta[$post_id][$key];
    }
    return $single ? '' : [];
}

function update_post_meta($post_id, $key, $value) {
    global $test_post_meta;
    if (!isset($test_post_meta[$post_id])) {
        $test_post_meta[$post_id] = [];
    }
    $test_post_meta[$post_id][$key] = $value;
    return true;
}

function get_post($post_id) {
    global $test_posts;
    return isset($test_posts[$post_id]) ? (object) $test_posts[$post_id] : null;
}

function wp_update_post($postarr) {
    global $test_posts;
    $id = $postarr['ID'];
    if (isset($test_posts[$id])) {
        foreach ($postarr as $k => $v) {
            $test_posts[$id][$k] = $v;
        }
    }
    return $id;
}

function get_post_type($post_id) {
    $post = get_post($post_id);
    return $post ? $post->post_type : 'post';
}

function wp_set_post_tags($post_id, $tags, $append = false) {
    global $test_tags;
    if (!isset($test_tags[$post_id]) || !$append) {
        $test_tags[$post_id] = [];
    }
    foreach ((array) $tags as $t) {
        if (!in_array($t, $test_tags[$post_id], true)) {
            $test_tags[$post_id][] = $t;
        }
    }
}

function sanitize_text_field($str) {
    return trim(strip_tags((string) $str));
}

function sanitize_textarea_field($str) {
    return trim(strip_tags((string) $str));
}

function wp_unslash($val) {
    return is_string($val) ? stripslashes($val) : $val;
}

function esc_html($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function esc_attr($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function esc_textarea($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function __($str, $domain = 'default') {
    return $str;
}

function _e($str, $domain = 'default') {
    echo $str;
}

function esc_attr__($str, $domain = 'default') {
    return esc_attr($str);
}

function checked($checked, $current = true, $echo = true) {
    $res = ((string) $checked === (string) $current) ? ' checked="checked"' : '';
    if ($echo) {
        echo $res;
    }
    return $res;
}

function selected($selected, $current = true, $echo = true) {
    $res = ((string) $selected === (string) $current) ? ' selected="selected"' : '';
    if ($echo) {
        echo $res;
    }
    return $res;
}

function current_time($type) {
    return date('Y-m-d H:i:s');
}

function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
function remove_action($tag, $callback, $priority = 10) {}
function add_meta_box() {}
function wp_nonce_field() {}
function wp_verify_nonce() { return true; }
function current_user_can() { return true; }
function wp_is_post_revision() { return false; }
function wp_is_post_autosave() { return false; }

// 模拟 AI 请求接口
$mock_ai_response_payload = null;
function argon_ai_query($scenario, $prompt, $content, $context = []) {
    global $mock_ai_response_payload;
    return $mock_ai_response_payload;
}

function argon_decode_ai_json_response($response, $expect_array = false) {
    if (!is_string($response) || trim($response) === '') {
        return false;
    }
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $response, $matches)) {
        $json_str = trim($matches[1]);
        $data = json_decode($json_str, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }
    $data = json_decode(trim($response), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $data;
    }
    return false;
}

$failures = [];
function test_assert($condition, $message) {
    global $failures;
    if (!$condition) {
        $failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
    }
}

$root = dirname(__DIR__);
require_once $root . '/inc/ai-seo.php';

echo ">>> 1. 测试提示词生成与约束规则...\n";
$default_prompt = argon_get_default_ai_seo_prompt();
test_assert(!empty($default_prompt), '默认 SEO 提示词不应为空');
test_assert(strpos($default_prompt, '合法的 JSON 对象') !== false, '提示词必须要求输出合法 JSON 对象');
test_assert(strpos($default_prompt, 'description') !== false && strpos($default_prompt, 'keywords') !== false, '提示词应包含 description 与 keywords 规范');
test_assert(strpos($default_prompt, '严禁') !== false, '提示词应包含严格负向约束');
test_assert(strpos($default_prompt, '注入') !== false, '提示词应包含反注入与指令隔离说明');

// 测试自定义提示词生效
$test_options['argon_ai_seo_custom_prompt'] = '自定义专用 SEO 提示词';
test_assert(argon_get_ai_seo_prompt() === '自定义专用 SEO 提示词', '设置自定义提示词后应返回自定义内容');
$test_options['argon_ai_seo_custom_prompt'] = '';
test_assert(argon_get_ai_seo_prompt() === $default_prompt, '自定义提示词为空时应回退到默认提示词');

echo ">>> 2. 测试内容净化与安全截断...\n";
$dirty_html = '<div class="post-content"><h2>介绍</h2><script>alert("xss")</script><style>.bad{color:red;}</style>[shortcode id="123"]这是正文，包含 <b>HTML 标签</b> 与短代码，还有换行。\n\n\n多个空格    和链接 https://example.com/test</div>';
$cleaned = argon_clean_post_content_for_seo($dirty_html);
test_assert(strpos($cleaned, '<script>') === false, '净化后不得残留 script 标签');
test_assert(strpos($cleaned, 'alert("xss")') === false, '净化后不得残留 script 内部代码');
test_assert(strpos($cleaned, '<style>') === false, '净化后不得残留 style 标签');
test_assert(strpos($cleaned, '[shortcode') === false, '净化后不得残留 WordPress 短代码');
test_assert(strpos($cleaned, '这是正文') !== false, '净化后应保留正文主要文字');

$long_content = str_repeat('超长文本内容测试。', 2000);
$cleaned_long = argon_clean_post_content_for_seo($long_content, 500);
test_assert(mb_strlen($cleaned_long, 'UTF-8') <= 500, '净化后长度不得超过设定上限');

echo ">>> 3. 测试关键词提取与去重截断...\n";
$raw_kws = ['WordPress', 'SEO优化', '  wordpress  ', 'WordPress', 'A', '超长无效关键词超长无效关键词超长无效关键词超长无效关键词超长无效关键词', '标签一, 标签二；标签三 标签四'];
$normalized_kws = argon_normalize_seo_keywords($raw_kws, 5);
test_assert(count($normalized_kws) <= 5, '规范化后的关键词数量不得超过上限');
test_assert(in_array('WordPress', $normalized_kws) || in_array('wordpress', $normalized_kws), '应保留合法关键词');
test_assert(!in_array('A', $normalized_kws), '应过滤过短的单个字符');
test_assert(in_array('标签一', $normalized_kws), '应支持复合分隔符拆分');

echo ">>> 4. 测试 SEO 结果解析与持久化存储...\n";
$mock_post_id = 999;
$test_posts[$mock_post_id] = [
    'ID' => $mock_post_id,
    'post_title' => 'Ave Mujika 巡演回顾',
    'post_content' => str_repeat('这是一篇关于乐队演出详报的长文，包含精彩现场与曲目分析。', 10),
    'post_excerpt' => '',
    'post_type' => 'post',
    'post_status' => 'publish'
];

$seo_result = [
    'description' => '本文回顾 Ave Mujika 巡演现场的高光瞬间与舞台呈现，包含全曲目编曲解析与视听细节记录。',
    'keywords' => ['Ave Mujika', '现场巡演', '曲目分析', '二次元音乐'],
    'keywords_str' => 'Ave Mujika, 现场巡演, 曲目分析, 二次元音乐',
    'title_suggestion' => 'Ave Mujika 巡演全程回顾与音乐深度解析'
];

$test_options['argon_ai_seo_sync_excerpt'] = 'true';
$test_options['argon_ai_seo_sync_tags'] = 'true';

$saved = argon_save_post_ai_seo($mock_post_id, $seo_result, ['auto_excerpt' => true, 'auto_tags' => true]);
test_assert($saved === true, '保存 SEO 结果应返回 true');
test_assert(get_post_meta($mock_post_id, 'argon_post_seo_description', true) === $seo_result['description'], '文章 Meta 中描述应保存正确');
test_assert(get_post_meta($mock_post_id, 'argon_post_seo_keywords', true) === $seo_result['keywords_str'], '文章 Meta 中关键词应保存正确');
test_assert(!empty(get_post_meta($mock_post_id, '_argon_ai_seo_time', true)), '应记录生成时间戳');
test_assert($test_posts[$mock_post_id]['post_excerpt'] === $seo_result['description'], '当文章原摘要为空时应同步写入 Excerpt');
test_assert(isset($test_tags[$mock_post_id]) && in_array('Ave Mujika', $test_tags[$mock_post_id]), '应自动追加关键词为文章标签');

echo ">>> 5. 测试 AI 生成调用与 JSON 容错解析...\n";
$mock_ai_response_payload = "```json\n" . json_encode([
    'description' => 'AI 自动分析生成的专业描述',
    'keywords' => ['关键词A', '关键词B', '关键词C'],
    'title_suggestion' => '建议新标题'
], JSON_UNESCAPED_UNICODE) . "\n```";

$ai_gen = argon_generate_ai_seo($test_posts[$mock_post_id]);
test_assert($ai_gen['success'] === true, 'AI 生成调用应成功');
test_assert($ai_gen['description'] === 'AI 自动分析生成的专业描述', '应解析并提取 description');
test_assert(in_array('关键词A', $ai_gen['keywords']), '应解析并提取 keywords');
test_assert($ai_gen['title_suggestion'] === '建议新标题', '应解析并提取 title_suggestion');

echo ">>> 6. 测试 functions.php 契约（SEO 标签输出与 API 场景扩展）...\n";
$functions_source = file_get_contents($root . '/functions.php');

test_assert(strpos($functions_source, "require_once get_template_directory() . '/inc/ai-seo.php';") !== false, 'functions.php 必须引入 inc/ai-seo.php');
test_assert(strpos($functions_source, "argon_post_seo_description") !== false, 'get_seo_description 必须支持读取 argon_post_seo_description');
test_assert(strpos($functions_source, "argon_post_seo_keywords") !== false, 'get_seo_keywords 必须支持读取 argon_post_seo_keywords');
test_assert(strpos($functions_source, "\$scenario === 'seo'") !== false, 'argon_ai_query 必须支持 seo 场景');
test_assert(strpos($functions_source, "update_option('argon_ai_seo_active_api'") !== false, 'argon_set_active_api_for_scenario 必须支持 seo 场景');
test_assert(strpos($functions_source, "argon_ai_seo_active_api") !== false, 'argon_delete_api 必须保护正在使用的 seo API');
test_assert(strpos($functions_source, "'seo_active' => \$seo_active") !== false, 'argon_ajax_get_all_unified_apis 必须返回 seo_active');

echo ">>> 7. 测试 settings.php 契约（设置面板与保存逻辑）...\n";
$settings_source = file_get_contents($root . '/settings.php');

test_assert(strpos($settings_source, 'id="ai-seo"') !== false, 'settings.php 中必须包含 id="ai-seo" 标题');
test_assert(strpos($settings_source, 'name="argon_ai_seo_enable"') !== false, '必须包含启用开关');
test_assert(strpos($settings_source, 'name="argon_ai_seo_active_api"') !== false, '必须包含专用 API 选择器');
test_assert(strpos($settings_source, 'name="argon_ai_seo_auto_mode"') !== false, '必须包含自动生成模式选择器');
test_assert(strpos($settings_source, 'name="argon_ai_seo_sync_excerpt"') !== false, '必须包含摘要同步选项');
test_assert(strpos($settings_source, 'name="argon_ai_seo_sync_tags"') !== false, '必须包含标签同步选项');
test_assert(strpos($settings_source, 'name="argon_ai_seo_max_keywords"') !== false, '必须包含关键词数量上限设置');
test_assert(strpos($settings_source, 'name="argon_ai_seo_custom_prompt"') !== false, '必须包含自定义提示词输入框');
test_assert(strpos($settings_source, "argon_update_option_checkbox('argon_ai_seo_enable')") !== false, '设置保存函数必须保存 argon_ai_seo_enable');
test_assert(strpos($settings_source, "argon_update_option('argon_ai_seo_active_api')") !== false, '设置保存函数必须保存 argon_ai_seo_active_api');
test_assert(strpos($settings_source, "argon_update_option('argon_ai_seo_auto_mode')") !== false, '设置保存函数必须保存 argon_ai_seo_auto_mode');
test_assert(strpos($settings_source, "argon_update_option_checkbox('argon_ai_seo_sync_excerpt')") !== false, '设置保存函数必须保存 argon_ai_seo_sync_excerpt');
test_assert(strpos($settings_source, "argon_update_option_checkbox('argon_ai_seo_sync_tags')") !== false, '设置保存函数必须保存 argon_ai_seo_sync_tags');
test_assert(strpos($settings_source, "SEO 优化") !== false, 'API 列表必须在用途栏显示 SEO 优化');

if (empty($failures)) {
    echo "\n[ALL TESTS PASSED] AI 文章 SEO 自动优化所有 7 个维度的契约与单元测试全部通过！\n";
    exit(0);
} else {
    echo "\n[TESTS FAILED] 发现 " . count($failures) . " 处错误。\n";
    exit(1);
}
