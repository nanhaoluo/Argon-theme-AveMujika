<?php
/**
 * Bilibili bangumi data access, caching and cover localization.
 *
 * Cached local covers are preferred. Until background localization finishes,
 * the original cover URL is retained so the browser can display it directly.
 *
 * @package Argon
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!defined('ARGON_BANGUMI_API_URL')) {
	define('ARGON_BANGUMI_API_URL', 'https://api.bilibili.com/x/space/bangumi/follow/list');
}

if (!function_exists('argon_bangumi_is_enabled')) {
	function argon_bangumi_is_enabled() {
		return get_option('argon_bangumi_enabled', 'false') === 'true'
			&& preg_match('/^\d+$/', (string)get_option('argon_bangumi_uid', '349546967'));
	}
}

if (!function_exists('argon_bangumi_uid')) {
	function argon_bangumi_uid() {
		$uid = preg_replace('/\D+/', '', (string)get_option('argon_bangumi_uid', '349546967'));
		return $uid !== '' ? $uid : '349546967';
	}
}

if (!function_exists('argon_bangumi_cache_key')) {
	function argon_bangumi_cache_key($type, $status, $pn) {
		return 'argon_bangumi_' . argon_bangumi_uid() . '_' . absint($type) . '_' . absint($status) . '_' . absint($pn);
	}
}

if (!function_exists('argon_bangumi_normalize_status')) {
	function argon_bangumi_normalize_status($status) {
		$status = absint($status);
		return in_array($status, array(1, 2, 3), true) ? $status : 2;
	}
}

if (!function_exists('argon_bangumi_normalize_type')) {
	function argon_bangumi_normalize_type($type) {
		$type = strtolower(trim((string)$type));
		return in_array($type, array('1', '4'), true) ? $type : 'all';
	}
}

if (!function_exists('argon_bangumi_extract_names')) {
	function argon_bangumi_extract_names($values) {
		$names = array();
		if (is_string($values)) {
			$values = preg_split('/\s*[,，、]\s*/u', $values, -1, PREG_SPLIT_NO_EMPTY);
		}
		foreach ((array)$values as $value) {
			if (is_array($value)) {
				$value = isset($value['name']) ? $value['name'] : (isset($value['title']) ? $value['title'] : '');
			}
			$value = trim(wp_strip_all_tags((string)$value));
			if ($value !== '' && !in_array($value, $names, true)) {
				$names[] = $value;
			}
		}
		return $names;
	}
}

if (!function_exists('argon_bangumi_local_cover_url')) {
	function argon_bangumi_local_cover_url($season_id) {
		$season_id = absint($season_id);
		if ($season_id <= 0) return '';
		$uploads = wp_upload_dir();
		if (!empty($uploads['error']) || empty($uploads['basedir']) || empty($uploads['baseurl'])) return '';
		$file = trailingslashit($uploads['basedir']) . 'bangumi-cache/' . $season_id . '.jpg';
		if (!file_exists($file)) return '';
		return set_url_scheme(trailingslashit($uploads['baseurl']) . 'bangumi-cache/' . $season_id . '.jpg');
	}
}

if (!function_exists('argon_bangumi_normalize_item')) {
	function argon_bangumi_normalize_item($item, $fallback_status = 2, $download_cover = true) {
		if (!is_array($item)) {
			return false;
		}

		$season_id = isset($item['season_id']) ? absint($item['season_id']) : 0;
		if ($season_id <= 0) {
			return false;
		}

		$new_ep = isset($item['new_ep']) && is_array($item['new_ep']) ? $item['new_ep'] : array();
		$rating = isset($item['rating']) && is_array($item['rating']) ? $item['rating'] : array();
		$cover = isset($item['cover']) ? trim((string)$item['cover']) : '';
		if (strpos($cover, '//') === 0) {
			$cover = 'https:' . $cover;
		}
		$cover = esc_url_raw($cover);
		$local_cover = isset($item['cover_local']) ? esc_url_raw((string)$item['cover_local']) : '';
		if ($local_cover === '') {
			$local_cover = argon_bangumi_local_cover_url($season_id);
		}
		if ($download_cover && $cover !== '') {
			$downloaded_cover = argon_download_bangumi_cover($cover, $season_id);
			if ($downloaded_cover !== '') $local_cover = $downloaded_cover;
		}

		$status = isset($item['follow_status']) ? absint($item['follow_status']) : absint($fallback_status);
		$status = argon_bangumi_normalize_status($status);
		$progress = isset($item['progress']) && !is_array($item['progress']) ? trim((string)$item['progress']) : '';
		$index_show = isset($item['index_show']) ? (string)$item['index_show'] : (isset($new_ep['index_show']) ? (string)$new_ep['index_show'] : '');
		$score = is_array(isset($item['rating']) ? $item['rating'] : null)
			? (isset($rating['score']) ? (float)$rating['score'] : 0)
			: (isset($item['rating']) ? (float)$item['rating'] : 0);

		return array(
			'season_id' => $season_id,
			'season_type' => isset($item['season_type']) ? absint($item['season_type']) : 0,
			'title' => isset($item['title']) ? wp_strip_all_tags((string)$item['title']) : '',
			'cover' => $cover,
			'cover_local' => $local_cover,
			'season_type_name' => isset($item['season_type_name']) ? wp_strip_all_tags((string)$item['season_type_name']) : '',
			'follow_status' => $status,
			'progress' => $progress,
			'index_show' => wp_strip_all_tags($index_show),
			'rating' => $score > 0 ? round($score, 1) : 0,
			'areas' => argon_bangumi_extract_names(isset($item['areas']) ? $item['areas'] : array()),
			'styles' => argon_bangumi_extract_names(isset($item['styles']) ? $item['styles'] : array()),
			'url' => isset($item['url']) ? esc_url_raw((string)$item['url']) : '',
			'summary' => isset($item['summary']) ? wp_strip_all_tags((string)$item['summary']) : '',
			'total_count' => isset($item['total_count']) ? absint($item['total_count']) : 0,
		);
	}
}

if (!function_exists('argon_bangumi_schedule_cover_localization')) {
	function argon_bangumi_schedule_cover_localization($items) {
		if (!function_exists('wp_schedule_single_event')) return;
		$covers = array();
		foreach ((array)$items as $item) {
			if (!is_array($item)) continue;
			$season_id = isset($item['season_id']) ? absint($item['season_id']) : 0;
			$cover = isset($item['cover']) ? esc_url_raw((string)$item['cover']) : '';
			if ($season_id <= 0 || $cover === '' || argon_bangumi_local_cover_url($season_id) !== '') continue;
			$covers[$season_id] = $cover;
		}
		if (!$covers) return;
		ksort($covers);
		$args = array($covers);
		if (!function_exists('wp_next_scheduled') || !wp_next_scheduled('argon_bangumi_localize_covers', $args)) {
			wp_schedule_single_event(time() + 5, 'argon_bangumi_localize_covers', $args);
		}
	}
}

if (!function_exists('argon_bangumi_localize_covers')) {
	function argon_bangumi_localize_covers($covers) {
		foreach ((array)$covers as $season_id => $cover) {
			argon_download_bangumi_cover($cover, $season_id);
		}
	}
}

if (!function_exists('argon_bangumi_hydrate_items')) {
	function argon_bangumi_hydrate_items($items, $fallback_status, $download_cover = true) {
		$normalized = array();
		foreach ((array)$items as $item) {
			$normalized_item = argon_bangumi_normalize_item($item, $fallback_status, $download_cover);
			if ($normalized_item !== false) {
				$normalized[] = $normalized_item;
			}
		}
		return $normalized;
	}
}

if (!function_exists('argon_download_bangumi_cover')) {
	/**
	 * Download one cover to uploads/bangumi-cache/{season_id}.jpg.
	 * Existing files are intentionally reused to keep pagination cheap.
	 */
	function argon_download_bangumi_cover($url, $season_id) {
		$season_id = absint($season_id);
		$url = esc_url_raw((string)$url);
		if ($season_id <= 0 || $url === '' || !preg_match('#^https?://#i', $url)) {
			return '';
		}

		$uploads = wp_upload_dir();
		if (!empty($uploads['error']) || empty($uploads['basedir']) || empty($uploads['baseurl'])) {
			return '';
		}
		$directory = trailingslashit($uploads['basedir']) . 'bangumi-cache';
		$file = trailingslashit($directory) . $season_id . '.jpg';
		$public_url = trailingslashit($uploads['baseurl']) . 'bangumi-cache/' . $season_id . '.jpg';
		if (file_exists($file)) {
			return set_url_scheme($public_url);
		}
		if (!wp_mkdir_p($directory)) {
			return '';
		}

		$response = wp_remote_get($url, array(
			'timeout' => 20,
			'redirection' => 3,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
			),
		));
		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return '';
		}
		$body = wp_remote_retrieve_body($response);
		if ($body === '') {
			return '';
		}

		$temp = $file . '.tmp-' . wp_rand(1000, 999999);
		if (@file_put_contents($temp, $body, LOCK_EX) === false) {
			return '';
		}
		if (!@rename($temp, $file)) {
			@unlink($temp);
			return file_exists($file) ? set_url_scheme($public_url) : '';
		}

		return set_url_scheme($public_url);
	}
}

if (!function_exists('argon_bangumi_remote_page')) {
	function argon_bangumi_remote_page($type, $status, $pn, $download_cover = true) {
		$url = add_query_arg(array(
			'vmid' => argon_bangumi_uid(),
			'type' => absint($type),
			'follow_status' => absint($status),
			'pn' => absint($pn),
			'ps' => 30,
		), ARGON_BANGUMI_API_URL);
		$sessdata = trim((string)get_option('argon_bangumi_sessdata', ''));
		$headers = array(
			'Accept' => 'application/json',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
		);
		if ($sessdata !== '') {
			$headers['Cookie'] = 'SESSDATA=' . $sessdata;
		}

		$response = wp_remote_get($url, array('timeout' => 20, 'headers' => $headers));
		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return new WP_Error('argon_bangumi_http_error', __('追番数据请求失败', 'argon'));
		}
		$payload = json_decode(wp_remote_retrieve_body($response), true);
		if (!is_array($payload) || (isset($payload['code']) && intval($payload['code']) !== 0)) {
			return new WP_Error('argon_bangumi_api_error', __('B 站追番数据返回异常', 'argon'));
		}

		$data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : array();
		$raw_items = isset($data['list']) && is_array($data['list']) ? $data['list'] : array();
		$items = argon_bangumi_hydrate_items($raw_items, $status, $download_cover);
		$total = isset($data['total']) ? absint($data['total']) : count($items);
		return array(
			'items' => $items,
			'total' => $total,
			'page' => absint($pn),
			'per_page' => 30,
		);
	}
}

if (!function_exists('argon_bangumi_get_page')) {
	function argon_bangumi_get_page($type, $status, $pn, $force_refresh = false, $download_cover = true) {
		$type = absint($type);
		$status = argon_bangumi_normalize_status($status);
		$pn = max(1, absint($pn));
		$cache_key = argon_bangumi_cache_key($type, $status, $pn);
		$cached = get_transient($cache_key);
		if (!$force_refresh && is_array($cached) && isset($cached['items'])) {
			$cached['items'] = argon_bangumi_hydrate_items($cached['items'], $status, $download_cover);
			return $cached;
		}

		$remote = argon_bangumi_remote_page($type, $status, $pn, $download_cover);
		if (is_wp_error($remote)) {
			if (is_array($cached) && isset($cached['items'])) {
				$cached['items'] = argon_bangumi_hydrate_items($cached['items'], $status, $download_cover);
				return $cached;
			}
			return array('items' => array(), 'total' => 0, 'page' => $pn, 'per_page' => 30, 'error' => $remote->get_error_message());
		}

		set_transient($cache_key, $remote, HOUR_IN_SECONDS);
		return $remote;
	}
}

if (!function_exists('argon_bangumi_get_cached_page')) {
	function argon_bangumi_get_cached_page($type, $status, $pn, $download_cover = false) {
		$type = absint($type);
		$status = argon_bangumi_normalize_status($status);
		$pn = max(1, absint($pn));
		$cached = get_transient(argon_bangumi_cache_key($type, $status, $pn));
		if (!is_array($cached) || !isset($cached['items'])) return false;
		$cached['items'] = argon_bangumi_hydrate_items($cached['items'], $status, $download_cover);
		return $cached;
	}
}

if (!function_exists('argon_get_bangumi_list')) {
	function argon_get_bangumi_list($type, $status, $pn, $force_refresh = false) {
		$page = argon_bangumi_get_page($type, $status, $pn, $force_refresh, true);
		return isset($page['items']) && is_array($page['items']) ? $page['items'] : array();
	}
}

if (!function_exists('argon_bangumi_interleave_items')) {
	function argon_bangumi_interleave_items($groups) {
		$merged = array();
		$max = 0;
		foreach ($groups as $group) {
			$max = max($max, count($group));
		}
		for ($index = 0; $index < $max; $index++) {
			foreach ($groups as $group) {
				if (isset($group[$index])) $merged[] = $group[$index];
			}
		}
		return $merged;
	}
}

if (!function_exists('argon_bangumi_get_combined_page')) {
	function argon_bangumi_get_combined_page($type, $status, $pn, $force_refresh = false, $download_cover = true, $cached_only = false) {
		$type = argon_bangumi_normalize_type($type);
		$types = $type === 'all' ? array(1, 4) : array(absint($type));
		$pn = max(1, absint($pn));
		$source_groups = array();
		$source_totals = array();
		$total = 0;
		$has_more = false;
		$needs_refresh = false;
		$errors = array();
		foreach ($types as $source_type) $source_groups[$source_type] = array();

		// Fetch only as many source pages as the requested merged slice needs.
		for ($source_pn = 1; $source_pn <= $pn; $source_pn++) {
			$loaded_source = false;
			foreach ($types as $source_type) {
				if (isset($source_totals[$source_type]) && count($source_groups[$source_type]) >= $source_totals[$source_type]) continue;
				$page = $cached_only
					? argon_bangumi_get_cached_page($source_type, $status, $source_pn, false)
					: argon_bangumi_get_page($source_type, $status, $source_pn, $force_refresh, false);
				if ($page === false) {
					$needs_refresh = true;
					continue;
				}
				$loaded_source = true;
				$source_groups[$source_type] = array_merge($source_groups[$source_type], isset($page['items']) && is_array($page['items']) ? $page['items'] : array());
				if ($source_pn === 1) {
					$source_totals[$source_type] = isset($page['total']) ? absint($page['total']) : count($page['items']);
					$total += $source_totals[$source_type];
				}
				if (!empty($page['error'])) $errors[] = $page['error'];
			}
			$available_items = count(argon_bangumi_interleave_items($source_groups));
			if (!$loaded_source || $available_items >= ($pn * 30) || ($total > 0 && $available_items >= $total)) break;
		}
		$merged_items = argon_bangumi_interleave_items($source_groups);
		$items = array_slice($merged_items, ($pn - 1) * 30, 30);
		if ($download_cover) $items = argon_bangumi_hydrate_items($items, $status, true);
		$has_more = $total > ($pn * 30);
		$result = array(
			'items' => $items,
			'total' => $total,
			'page' => $pn,
			'per_page' => 30,
			'has_more' => $has_more,
			'needs_refresh' => $cached_only && $needs_refresh,
			'error' => !empty($errors) ? implode(' ', array_unique($errors)) : '',
		);
		return $result;
	}
}

if (!function_exists('argon_bangumi_get_stats')) {
	function argon_bangumi_get_stats($current_page = array(), $current_status = 2) {
		$cache_key = 'argon_bangumi_stats_' . argon_bangumi_uid();
		$stats = get_transient($cache_key);
		$stats = is_array($stats) ? array_merge(array('total' => 0, 'watching' => 0, 'want' => 0, 'watched' => 0), $stats) : array('total' => 0, 'watching' => 0, 'want' => 0, 'watched' => 0);
		$status_labels = array(1 => 'want', 2 => 'watching', 3 => 'watched');
		$current_status = argon_bangumi_normalize_status($current_status);
		if (is_array($current_page) && array_key_exists('total', $current_page) && empty($current_page['needs_refresh'])) {
			$stats[$status_labels[$current_status]] = absint($current_page['total']);
		}
		$stats['total'] = $stats['watching'] + $stats['want'] + $stats['watched'];
		set_transient($cache_key, $stats, 6 * HOUR_IN_SECONDS);
		return $stats;
	}
}

if (!function_exists('argon_bangumi_schedule_stats_refresh')) {
	function argon_bangumi_schedule_stats_refresh() {
		if (!function_exists('wp_schedule_single_event')) return;
		$refresh_key = 'argon_bangumi_stats_refreshed_' . argon_bangumi_uid();
		$lock_key = 'argon_bangumi_stats_refresh_lock_' . argon_bangumi_uid();
		if (get_transient($refresh_key) !== false || get_transient($lock_key) !== false) return;
		set_transient($lock_key, 1, 5 * MINUTE_IN_SECONDS);
		wp_schedule_single_event(time() + 5, 'argon_bangumi_refresh_stats');
	}
}

if (!function_exists('argon_bangumi_refresh_stats')) {
	function argon_bangumi_refresh_stats() {
		$stats = array('total' => 0, 'watching' => 0, 'want' => 0, 'watched' => 0);
		foreach (array(1 => 'want', 2 => 'watching', 3 => 'watched') as $status => $label) {
			foreach (array(1, 4) as $type) {
				$page = argon_bangumi_get_page($type, $status, 1, false, false);
				$stats[$label] += isset($page['total']) ? absint($page['total']) : count($page['items']);
			}
		}
		$stats['total'] = $stats['watching'] + $stats['want'] + $stats['watched'];
		set_transient('argon_bangumi_stats_' . argon_bangumi_uid(), $stats, 6 * HOUR_IN_SECONDS);
		set_transient('argon_bangumi_stats_refreshed_' . argon_bangumi_uid(), 1, HOUR_IN_SECONDS);
		delete_transient('argon_bangumi_stats_refresh_lock_' . argon_bangumi_uid());
	}
}

if (!function_exists('argon_bangumi_ajax_is_rate_limited')) {
	function argon_bangumi_ajax_is_rate_limited() {
		$ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : 'unknown';
		$key = 'argon_bangumi_ajax_' . md5($ip);
		$count = get_transient($key);
		if ($count !== false && absint($count) >= 60) return true;
		set_transient($key, absint($count) + 1, MINUTE_IN_SECONDS);
		return false;
	}
}

if (!function_exists('argon_bangumi_ajax_handler')) {
	function argon_bangumi_ajax_handler() {
		if (!argon_bangumi_is_enabled()) {
			wp_send_json_error(array('message' => __('追番页面未启用或 UID 无效', 'argon')), 403);
		}
		if (argon_bangumi_ajax_is_rate_limited()) {
			wp_send_json_error(array('message' => __('请求过于频繁，请稍后再试', 'argon')), 429);
		}
		if (!isset($_POST['nonce']) || wp_unslash($_POST['nonce']) === '' || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'argon_bangumi_load')) {
			wp_send_json_error(array('message' => __('安全验证失败，请刷新页面后重试', 'argon')), 403);
		}
		$type = isset($_POST['type']) ? argon_bangumi_normalize_type(wp_unslash($_POST['type'])) : 'all';
		$status = isset($_POST['status']) ? argon_bangumi_normalize_status(wp_unslash($_POST['status'])) : 2;
		$pn = isset($_POST['pn']) ? min(30, max(1, absint($_POST['pn']))) : 1;
		$page = argon_bangumi_get_combined_page($type, $status, $pn, false, false);
		argon_bangumi_schedule_cover_localization($page['items']);
		argon_bangumi_schedule_stats_refresh();
		wp_send_json_success(array(
			'items' => $page['items'],
			'page' => $page['page'],
			'per_page' => $page['per_page'],
			'total' => $page['total'],
			'has_more' => $page['has_more'],
			'error' => $page['error'],
			'stats' => argon_bangumi_get_stats($page, $status),
		));
	}
}

if (!function_exists('argon_register_bangumi_ajax')) {
	function argon_register_bangumi_ajax() {
		add_action('wp_ajax_argon_bangumi_load', 'argon_bangumi_ajax_handler');
		add_action('wp_ajax_nopriv_argon_bangumi_load', 'argon_bangumi_ajax_handler');
		add_action('argon_bangumi_localize_covers', 'argon_bangumi_localize_covers', 10, 1);
		add_action('argon_bangumi_refresh_stats', 'argon_bangumi_refresh_stats');
	}
}
argon_register_bangumi_ajax();
