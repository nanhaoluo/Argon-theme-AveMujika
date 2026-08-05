(function (window, document) {
	'use strict';

	var configNode = null;
	var config = {};
	var state = {
		status: '2',
		type: 'all',
		keyword: '',
		loading: false,
		datasets: {},
		pages: {},
		hasMore: {},
		pending: {},
		stats: { total: 0, watching: 0, want: 0, watched: 0 }
	};

	var grid = null;
	var empty = null;
	var loadMoreButton = null;
	var liveStatus = null;
	var errorBox = null;
	var searchInput = null;

	function readPageState() {
		configNode = document.getElementById('argon-bangumi-config');
		try {
			config = configNode ? JSON.parse(configNode.textContent || '{}') : {};
		} catch (error) {
			config = {};
		}
		state = {
			status: String(config.initialStatus || 2),
			type: String(config.initialType || 'all'),
			keyword: '',
			loading: false,
			datasets: {},
			pages: {},
			hasMore: {},
			pending: {},
			stats: config.stats || { total: 0, watching: 0, want: 0, watched: 0 }
		};
		grid = document.getElementById('bangumi-grid');
		empty = document.getElementById('bangumi-empty');
		loadMoreButton = document.getElementById('bangumi-load-more');
		liveStatus = document.getElementById('bangumi-live-status');
		errorBox = document.getElementById('bangumi-error');
		searchInput = document.getElementById('bangumi-search-input');
	}

	function ensureStylesheet() {
		if (!config.styleUrl || !document.head) return;
		var links = document.querySelectorAll('link[rel="stylesheet"]');
		for (var index = 0; index < links.length; index++) {
			if (links[index].href === config.styleUrl || links[index].href.indexOf('/assets/css/bangumi.css') !== -1) return;
		}
		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.href = config.styleUrl;
		link.setAttribute('data-argon-bangumi-style', 'true');
		document.head.appendChild(link);
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function safeUrl(value) {
		var url = String(value || '').trim();
		return /^https?:\/\//i.test(url) ? url : '';
	}

	function statusInfo(status) {
		var labels = {
			'1': { label: '想看', className: 'is-want' },
			'2': { label: '在看', className: 'is-watching' },
			'3': { label: '看过', className: 'is-watched' }
		};
		return labels[String(status)] || labels['2'];
	}

	function typeLabel(item) {
		return item.season_type_name || (String(item.season_type) === '4' ? '国创' : '番剧');
	}

	function progressInfo(item) {
		var progress = String(item.progress || '').trim();
		var total = parseInt(item.total_count, 10) || 0;
		if (progress) {
			var numberMatch = progress.match(/\d+(?:\.\d+)?/);
			var watched = numberMatch ? parseFloat(numberMatch[0]) : 0;
			var text = /^看到第/.test(progress) ? progress : '看到第 ' + progress + ' 话';
			var percent = total > 0 ? Math.min(100, Math.max(0, watched / total * 100)) : 0;
			return { text: text, hasProgress: true, track: '<div class="bangumi-progress-track" aria-hidden="true"><span style="width:' + percent.toFixed(1) + '%"></span></div>' };
		}
		return {
			text: item.index_show || (total > 0 ? '全 ' + total + ' 话' : '更新中'),
			hasProgress: false,
			track: ''
		};
	}

	function renderCard(item, index) {
		var status = statusInfo(item.follow_status);
		var progress = progressInfo(item);
		var title = escapeHtml(item.title || '未命名番剧');
		var cover = safeUrl(item.cover_local);
		var url = safeUrl(item.url);
		var tags = Array.isArray(item.styles) ? item.styles.slice(0, 4) : [];
		var tagHtml = tags.map(function (tag) { return '<span class="bangumi-tag">' + escapeHtml(tag) + '</span>'; }).join('');
		var score = parseFloat(item.rating);
		var scoreHtml = score > 0 ? '<span class="bangumi-score"><i class="fa fa-star" aria-hidden="true"></i>' + score.toFixed(1) + '</span>' : '';
		var imageHtml = cover ? '<img src="' + escapeHtml(cover) + '" alt="" loading="lazy" decoding="async" fetchpriority="' + (index < 6 ? 'high' : 'auto') + '">' : '';
		var mediaClass = cover ? 'bangumi-card-media' : 'bangumi-card-media is-empty';
		var cardOpen = url ? '<a class="bangumi-card card shadow-sm bg-white border-0" role="listitem" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" data-season-id="' + escapeHtml(item.season_id) + '">' : '<article class="bangumi-card card shadow-sm bg-white border-0" role="listitem" data-season-id="' + escapeHtml(item.season_id) + '">';
		var cardClose = url ? '</a>' : '</article>';
		return cardOpen +
			'<div class="' + mediaClass + '">' + imageHtml + '<span class="bangumi-status-badge ' + status.className + '">' + status.label + '</span></div>' +
			'<div class="bangumi-card-body">' +
				'<h2 class="bangumi-card-title">' + title + '</h2>' +
				'<div class="bangumi-card-meta"><span class="bangumi-type-label">' + escapeHtml(typeLabel(item)) + '</span>' + scoreHtml + (item.areas && item.areas.length ? '<span>' + escapeHtml(item.areas[0]) + '</span>' : '') + '</div>' +
				'<div class="bangumi-progress"><div class="bangumi-progress-text"><span>' + escapeHtml(progress.text) + '</span>' + (progress.hasProgress && item.total_count ? '<span>共 ' + escapeHtml(item.total_count) + ' 话</span>' : '') + '</div>' + progress.track + '</div>' +
				(item.summary ? '<p class="bangumi-card-summary">' + escapeHtml(item.summary) + '</p>' : '') +
				(tagHtml ? '<div class="bangumi-tags">' + tagHtml + '</div>' : '') +
			'</div>' + cardClose;
	}

	function dedupeMerge(existing, items) {
		var merged = (existing || []).slice();
		var indexes = {};
		merged.forEach(function (item, index) { indexes[String(item.season_id)] = index; });
		(items || []).forEach(function (item) {
			var key = String(item.season_id || '');
			if (!key) return;
			if (Object.prototype.hasOwnProperty.call(indexes, key)) merged[indexes[key]] = item;
			else { indexes[key] = merged.length; merged.push(item); }
		});
		return merged;
	}

	function filteredItems() {
		var items = state.datasets[state.status] || [];
		var keyword = state.keyword.trim().toLowerCase();
		return items.filter(function (item) {
			var matchesType = state.type === 'all' || String(item.season_type) === state.type || (state.type === '1' && item.season_type_name === '番剧') || (state.type === '4' && item.season_type_name === '国创');
			var matchesKeyword = !keyword || String(item.title || '').toLowerCase().indexOf(keyword) !== -1;
			return matchesType && matchesKeyword;
		});
	}

	function updateStats(stats) {
		if (!stats) return;
		state.stats = stats;
		Object.keys(stats).forEach(function (key) {
			var target = document.getElementById('bangumi-stat-' + key) || document.querySelector('[data-bangumi-stat="' + key + '"]');
			if (target) target.textContent = Number(stats[key] || 0).toLocaleString();
		});
	}

	function prioritizeVisibleImages() {
		if (!grid || !window.IntersectionObserver) return;
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				var image = entry.target;
				image.fetchPriority = 'high';
				image.loading = 'eager';
				observer.unobserve(image);
			});
		}, { rootMargin: '320px 0px' });
		Array.prototype.forEach.call(grid.querySelectorAll('img[loading="lazy"]'), function (image) { observer.observe(image); });
	}

	function render(items) {
		if (!grid) return;
		var visibleItems = items || filteredItems();
		grid.innerHTML = visibleItems.map(renderCard).join('');
		if (empty) empty.hidden = visibleItems.length !== 0;
		Array.prototype.forEach.call(grid.querySelectorAll('img'), function (image) {
			image.addEventListener('error', function () {
				var media = image.parentNode;
				if (media) media.classList.add('is-empty');
				image.remove();
			});
		});
		prioritizeVisibleImages();
		updateLoadButton();
	}

	function setLive(message, kind) {
		if (!liveStatus) return;
		liveStatus.textContent = message || '';
		liveStatus.className = 'bangumi-live-status' + (kind ? ' is-' + kind : '');
	}

	function setError(message) {
		if (!errorBox) return;
		errorBox.textContent = message || '';
		errorBox.hidden = !message;
	}

	function updateLoadButton() {
		if (!loadMoreButton) return;
		var hasMore = state.hasMore[state.status] !== false;
		loadMoreButton.classList.toggle('is-done', !hasMore);
		loadMoreButton.disabled = !config.enabled || state.loading || !hasMore;
		loadMoreButton.classList.toggle('is-loading', state.loading);
	}

	function requestPage(status, page) {
		if (!config.ajaxUrl || state.pending[status]) return Promise.resolve(false);
		state.pending[status] = true;
		state.loading = true;
		if (state.status === String(status)) {
			setError('');
			setLive('正在读取追番数据…', 'loading');
		}
		updateLoadButton();
		var body = new URLSearchParams();
		body.set('action', 'argon_bangumi_load');
		body.set('type', 'all');
		body.set('status', status);
		body.set('pn', page);
		if (config.nonce) body.set('nonce', config.nonce);
		return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
			.then(function (response) { return response.json(); })
			.then(function (response) {
				if (!response || !response.success) throw new Error(response && response.data && response.data.message ? response.data.message : '追番数据加载失败');
				var data = response.data || {};
				state.datasets[status] = dedupeMerge(state.datasets[status], data.items || []);
				state.pages[status] = Number(data.page || page);
				state.hasMore[status] = !!data.has_more;
				updateStats(data.stats);
				if (state.status === String(status)) {
					setLive('');
					setError(data.error || '');
				}
				render();
				return true;
			})
			.catch(function (error) {
				if (state.status === String(status)) {
					setLive('');
					setError(error && error.message ? error.message : '追番数据加载失败');
				}
				return false;
			})
			.finally(function () {
				delete state.pending[status];
				state.loading = Object.keys(state.pending).length > 0;
				updateLoadButton();
			});
	}

	function switchTab(status) {
		status = String(status);
		if (!['1', '2', '3'].includes(status)) return;
		state.status = status;
		Array.prototype.forEach.call(document.querySelectorAll('[data-bangumi-status]'), function (button) {
			var active = button.getAttribute('data-bangumi-status') === status;
			button.classList.toggle('is-active', active);
			button.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		if (!Object.prototype.hasOwnProperty.call(state.datasets, status)) {
			requestPage(status, 1);
		} else {
			render();
		}
	}

	function switchType(type) {
		type = String(type);
		if (!['all', '1', '4'].includes(type)) return;
		state.type = type;
		Array.prototype.forEach.call(document.querySelectorAll('[data-bangumi-type]'), function (button) {
			button.classList.toggle('is-active', button.getAttribute('data-bangumi-type') === type);
		});
		render();
	}

	function loadMore() {
		if (state.loading) return;
		var nextPage = (state.pages[state.status] || 0) + 1;
		if (state.pages[state.status] == null) nextPage = 1;
		if (state.hasMore[state.status] === false) return;
		requestPage(state.status, nextPage);
	}

	function search(keyword) {
		state.keyword = String(keyword || '');
		render();
	}

	function init() {
		readPageState();
		if (!grid) return;
		ensureStylesheet();
		if (grid.getAttribute('data-argon-bangumi-bound') === 'true') return;
		grid.setAttribute('data-argon-bangumi-bound', 'true');
		state.datasets[state.status] = Array.isArray(config.initialItems) ? config.initialItems : [];
		state.pages[state.status] = Number(config.initialPage || 1);
		state.hasMore[state.status] = !!config.initialHasMore;
		updateStats(config.stats);
		if (config.initialError) setError(config.initialError);
		render();
		if (!config.enabled) return;
		Array.prototype.forEach.call(document.querySelectorAll('[data-bangumi-status]'), function (button) { button.addEventListener('click', function () { switchTab(button.getAttribute('data-bangumi-status')); }); });
		Array.prototype.forEach.call(document.querySelectorAll('[data-bangumi-type]'), function (button) { button.addEventListener('click', function () { switchType(button.getAttribute('data-bangumi-type')); }); });
		if (loadMoreButton) loadMoreButton.addEventListener('click', loadMore);
		if (searchInput) searchInput.addEventListener('input', function () { search(searchInput.value); });
		window.ArgonBangumi = {
			init: init,
			switchTab: switchTab,
			switchType: switchType,
			loadMore: loadMore,
			render: render,
			search: search
		};
	}

	window.ArgonBangumi = window.ArgonBangumi || { init: init, switchTab: switchTab, switchType: switchType, loadMore: loadMore, render: render, search: search };
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
}(window, document));
