<?php

define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);

$GLOBALS['argon_bangumi_test_transients'] = array();
$GLOBALS['argon_bangumi_test_reads'] = array();
$GLOBALS['argon_bangumi_test_failures'] = array();

function get_option($key, $default = false) {
	return $key === 'argon_bangumi_uid' ? '123456' : $default;
}

function absint($value) {
	return abs((int)$value);
}

function esc_url_raw($value) {
	return (string)$value;
}

function wp_strip_all_tags($value) {
	return strip_tags((string)$value);
}

function wp_upload_dir() {
	return array(
		'basedir' => sys_get_temp_dir() . '/argon-bangumi-test-missing',
		'baseurl' => 'https://example.test/uploads',
		'error' => false,
	);
}

function trailingslashit($value) {
	return rtrim((string)$value, '/\\') . '/';
}

function set_url_scheme($value) {
	return (string)$value;
}

function get_transient($key) {
	$GLOBALS['argon_bangumi_test_reads'][] = $key;
	return array_key_exists($key, $GLOBALS['argon_bangumi_test_transients'])
		? $GLOBALS['argon_bangumi_test_transients'][$key]
		: false;
}

function set_transient($key, $value, $expiration) {
	$GLOBALS['argon_bangumi_test_transients'][$key] = $value;
	return true;
}

function delete_transient($key) {
	unset($GLOBALS['argon_bangumi_test_transients'][$key]);
	return true;
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
	return true;
}

function __($text, $domain = 'default') {
	return $text;
}

function argon_bangumi_test_assert($condition, $message) {
	if (!$condition) $GLOBALS['argon_bangumi_test_failures'][] = $message;
}

require dirname(__DIR__) . '/inc/bangumi.php';

function argon_bangumi_test_items($type, $offset) {
	$items = array();
	for ($index = 1; $index <= 30; $index++) {
		$items[] = array(
			'season_id' => $offset + $index,
			'season_type' => $type,
			'title' => 'Item ' . ($offset + $index),
			'cover' => 'https://example.test/' . ($offset + $index) . '.jpg',
			'follow_status' => 2,
		);
	}
	return $items;
}

$type_one_key = argon_bangumi_cache_key(1, 2, 1);
$type_four_key = argon_bangumi_cache_key(4, 2, 1);
$GLOBALS['argon_bangumi_test_transients'][$type_one_key] = array(
	'items' => argon_bangumi_test_items(1, 1000),
	'total' => 60,
	'page' => 1,
	'per_page' => 30,
);
$GLOBALS['argon_bangumi_test_transients'][$type_four_key] = array(
	'items' => argon_bangumi_test_items(4, 4000),
	'total' => 60,
	'page' => 1,
	'per_page' => 30,
);

$first_page = argon_bangumi_get_combined_page('all', 2, 1, false, false, true);
argon_bangumi_test_assert(count($first_page['items']) === 30, 'first merged page should contain 30 items');
argon_bangumi_test_assert($first_page['items'][0]['season_id'] === 1001, 'merged items should start with the first bangumi item');
argon_bangumi_test_assert($first_page['items'][1]['season_id'] === 4001, 'merged items should interleave the first domestic item');
argon_bangumi_test_assert(empty($first_page['needs_refresh']), 'complete first-page caches should not request a refresh');

$GLOBALS['argon_bangumi_test_reads'] = array();
$second_page = argon_bangumi_get_combined_page('all', 2, 2, false, false, true);
argon_bangumi_test_assert(count($second_page['items']) === 30, 'second merged page should contain the remaining cached items');
argon_bangumi_test_assert($second_page['items'][0]['season_id'] === 1016, 'second merged page should continue at the correct interleaved offset');
argon_bangumi_test_assert(!in_array(argon_bangumi_cache_key(1, 2, 2), $GLOBALS['argon_bangumi_test_reads'], true), 'second merged page should not read an unnecessary source page');
argon_bangumi_test_assert(!in_array(argon_bangumi_cache_key(4, 2, 2), $GLOBALS['argon_bangumi_test_reads'], true), 'second merged page should stop once the requested slice is available');

unset($GLOBALS['argon_bangumi_test_transients'][$type_four_key]);
$partial_page = argon_bangumi_get_combined_page('all', 2, 1, false, false, true);
argon_bangumi_test_assert(!empty($partial_page['needs_refresh']), 'partial source caches should request an asynchronous refresh');
argon_bangumi_test_assert(count($partial_page['items']) === 30, 'partial cached data should remain immediately renderable');

unset($GLOBALS['argon_bangumi_test_transients'][$type_one_key]);
$empty_page = argon_bangumi_get_combined_page('all', 2, 1, false, false, true);
argon_bangumi_test_assert(!empty($empty_page['needs_refresh']), 'an empty cache should request an asynchronous refresh');
argon_bangumi_test_assert($empty_page['items'] === array(), 'an empty cache should return immediately without remote data');

if ($GLOBALS['argon_bangumi_test_failures']) {
	fwrite(STDERR, "Bangumi data flow test failed:\n- " . implode("\n- ", $GLOBALS['argon_bangumi_test_failures']) . "\n");
	exit(1);
}

echo "Bangumi data flow test passed.\n";
