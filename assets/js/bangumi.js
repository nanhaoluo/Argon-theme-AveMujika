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
	var cardPreview = null;
	var previewSource = null;
	var previewFrame = 0;
	var previewCloseTimer = 0;
	var statsPollTimer = 0;
	var statsPollAttempts = 0;
	var statsRequest = null;
	var itemIndex = {};
	var listeners = [];
	var imageObserver = null;
	var lifecycleId = 0;
	var initialized = false;
	var lastPointerWasTouch = false;
	var suppressNextFocusPreview = false;

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
		var cover = safeUrl(item.cover_local || item.cover);
		var url = safeUrl(item.url);
		var tags = Array.isArray(item.styles) ? item.styles.slice(0, 4) : [];
		var tagHtml = tags.map(function (tag) { return '<span class="bangumi-tag">' + escapeHtml(tag) + '</span>'; }).join('');
		var score = parseFloat(item.rating);
		var scoreHtml = score > 0 ? '<span class="bangumi-score"><i class="fa fa-star" aria-hidden="true"></i>' + score.toFixed(1) + '</span>' : '';
		var imageHtml = cover ? '<img src="' + escapeHtml(cover) + '" alt="" loading="lazy" decoding="async" fetchpriority="' + (index < 6 ? 'high' : 'auto') + '">' : '';
		var mediaClass = cover ? 'bangumi-card-media' : 'bangumi-card-media is-empty';
		var cardOpen = url ? '<a class="bangumi-card" role="listitem" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" data-season-id="' + escapeHtml(item.season_id) + '">' : '<article class="bangumi-card" role="listitem" tabindex="0" data-season-id="' + escapeHtml(item.season_id) + '">';
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

	function renderCardPreview(item) {
		var status = statusInfo(item.follow_status);
		var progress = progressInfo(item);
		var cover = safeUrl(item.cover_local || item.cover);
		var url = safeUrl(item.url);
		var tags = Array.isArray(item.styles) ? item.styles.slice(0, 6) : [];
		var tagHtml = tags.map(function (tag) { return '<span class="bangumi-tag">' + escapeHtml(tag) + '</span>'; }).join('');
		var score = parseFloat(item.rating);
		var scoreHtml = score > 0 ? '<span class="bangumi-score"><i class="fa fa-star" aria-hidden="true"></i>' + score.toFixed(1) + '</span>' : '';
		var areas = Array.isArray(item.areas) && item.areas.length ? '<span>' + escapeHtml(item.areas.slice(0, 2).join(' / ')) + '</span>' : '';
		var imageHtml = cover ? '<img src="' + escapeHtml(cover) + '" alt="" decoding="async">' : '';
		var openLink = url ? '<a class="bangumi-card-preview-open" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer"><span>打开详情</span><i class="fa fa-external-link" aria-hidden="true"></i></a>' : '';
		return '<button type="button" class="bangumi-card-preview-close" aria-label="关闭预览" title="关闭预览"><i class="fa fa-times" aria-hidden="true"></i></button>' +
			'<div class="bangumi-card-preview-media' + (cover ? '' : ' is-empty') + '">' + imageHtml + '<span class="bangumi-status-badge ' + status.className + '">' + status.label + '</span></div>' +
			'<div class="bangumi-card-preview-body">' +
				'<h2 class="bangumi-card-preview-title">' + escapeHtml(item.title || '未命名番剧') + '</h2>' +
				'<div class="bangumi-card-preview-meta"><span class="bangumi-type-label">' + escapeHtml(typeLabel(item)) + '</span>' + scoreHtml + areas + '</div>' +
				'<div class="bangumi-progress"><div class="bangumi-progress-text"><span>' + escapeHtml(progress.text) + '</span>' + (progress.hasProgress && item.total_count ? '<span>共 ' + escapeHtml(item.total_count) + ' 话</span>' : '') + '</div>' + progress.track + '</div>' +
				'<p class="bangumi-card-preview-summary">' + escapeHtml(item.summary || '暂无简介') + '</p>' +
				(tagHtml ? '<div class="bangumi-tags">' + tagHtml + '</div>' : '') +
				(openLink ? '<div class="bangumi-card-preview-actions">' + openLink + '</div>' : '') +
			'</div>';
	}

	function ensureCardPreview() {
		if (cardPreview && cardPreview.isConnected) return cardPreview;
		cardPreview = document.getElementById('bangumi-card-preview');
		if (!cardPreview && document.body) {
			cardPreview = document.createElement('div');
			cardPreview.id = 'bangumi-card-preview';
			cardPreview.className = 'bangumi-card-preview';
			cardPreview.setAttribute('role', 'dialog');
			cardPreview.setAttribute('aria-label', '番剧详情预览');
			cardPreview.setAttribute('aria-hidden', 'true');
			cardPreview.hidden = true;
			document.body.appendChild(cardPreview);
		}
		return cardPreview;
	}

	function positionCardPreview(source) {
		var preview = ensureCardPreview();
		if (!preview || !source || preview.hidden) return;
		var viewportWidth = document.documentElement.clientWidth || window.innerWidth;
		var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
		var margin = 12;
		var sourceRect = source.getBoundingClientRect();
		var width = Math.min(viewportWidth - margin * 2, viewportWidth <= 520 ? 360 : 440);
		preview.style.width = Math.max(280, width) + 'px';
		preview.style.minHeight = Math.min(viewportHeight - margin * 2, Math.max(480, sourceRect.height + 48)) + 'px';
		preview.style.left = margin + 'px';
		preview.style.top = margin + 'px';
		var previewRect = preview.getBoundingClientRect();
		var left = sourceRect.left + sourceRect.width / 2 - previewRect.width / 2;
		var top = sourceRect.top + sourceRect.height / 2 - previewRect.height / 2;
		left = Math.max(margin, Math.min(left, viewportWidth - previewRect.width - margin));
		top = Math.max(margin, Math.min(top, viewportHeight - previewRect.height - margin));
		preview.style.left = Math.round(left) + 'px';
		preview.style.top = Math.round(top) + 'px';
	}

	function closeCardPreview(immediate) {
		if (previewCloseTimer) window.clearTimeout(previewCloseTimer);
		if (previewFrame) window.cancelAnimationFrame(previewFrame);
		previewFrame = 0;
		if (previewSource) previewSource.classList.remove('is-preview-source');
		previewSource = null;
		if (!cardPreview) return;
		cardPreview.classList.remove('is-visible');
		cardPreview.setAttribute('aria-hidden', 'true');
		var finish = function () {
			if (previewSource || !cardPreview) return;
			cardPreview.hidden = true;
			cardPreview.innerHTML = '';
		};
		if (immediate) finish();
		else previewCloseTimer = window.setTimeout(finish, 180);
	}

	function showCardPreview(source, item) {
		var preview = ensureCardPreview();
		if (!preview || !source || !item) return;
		if (previewCloseTimer) window.clearTimeout(previewCloseTimer);
		if (previewSource && previewSource !== source) previewSource.classList.remove('is-preview-source');
		previewSource = source;
		previewSource.classList.add('is-preview-source');
		preview.innerHTML = renderCardPreview(item);
		preview.setAttribute('aria-hidden', 'false');
		preview.hidden = false;
		positionCardPreview(source);
		if (previewFrame) window.cancelAnimationFrame(previewFrame);
		previewFrame = window.requestAnimationFrame(function () {
			if (previewSource === source) preview.classList.add('is-visible');
		});
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

	function bind(element, type, handler, options) {
		if (!element || !element.addEventListener) return;
		element.addEventListener(type, handler, options);
		listeners.push({ element: element, type: type, handler: handler, options: options });
	}

	function unbindAll() {
		listeners.forEach(function (listener) {
			listener.element.removeEventListener(listener.type, listener.handler, listener.options);
		});
		listeners = [];
	}

	function closestCard(target) {
		if (!grid || !target || !target.closest) return null;
		var card = target.closest('.bangumi-card');
		return card && grid.contains(card) ? card : null;
	}

	function itemForCard(card) {
		return card ? itemIndex[String(card.getAttribute('data-season-id') || '')] : null;
	}

	function supportsHover() {
		return !window.matchMedia || window.matchMedia('(hover: hover) and (pointer: fine)').matches;
	}

	function clearPreviewCloseTimer() {
		if (!previewCloseTimer) return;
		window.clearTimeout(previewCloseTimer);
		previewCloseTimer = 0;
	}

	function onGridMouseOver(event) {
		if (!supportsHover()) return;
		var card = closestCard(event.target);
		if (!card || card.contains(event.relatedTarget)) return;
		showCardPreview(card, itemForCard(card));
	}

	function onGridMouseOut(event) {
		if (!supportsHover()) return;
		var card = closestCard(event.target);
		if (!card || card.contains(event.relatedTarget)) return;
		if (cardPreview && event.relatedTarget && cardPreview.contains(event.relatedTarget)) return;
		closeCardPreview(false);
	}

	function onGridFocusIn(event) {
		if (suppressNextFocusPreview) {
			suppressNextFocusPreview = false;
			return;
		}
		var card = closestCard(event.target);
		if (card) showCardPreview(card, itemForCard(card));
	}

	function onGridFocusOut(event) {
		var card = closestCard(event.target);
		if (!card || card.contains(event.relatedTarget)) return;
		closeCardPreview(false);
	}

	function onGridPointerDown(event) {
		lastPointerWasTouch = event.pointerType === 'touch' || event.pointerType === 'pen';
	}

	function onGridClick(event) {
		var card = closestCard(event.target);
		if (!card) return;
		var shouldOpenPreview = lastPointerWasTouch || !supportsHover();
		lastPointerWasTouch = false;
		if (!shouldOpenPreview) return;
		event.preventDefault();
		showCardPreview(card, itemForCard(card));
	}

	function onGridImageError(event) {
		var image = event.target;
		if (!image || image.tagName !== 'IMG') return;
		var media = image.parentNode;
		if (media) media.classList.add('is-empty');
		image.remove();
	}

	function onPreviewClick(event) {
		var closeButton = event.target.closest ? event.target.closest('.bangumi-card-preview-close') : null;
		if (!closeButton) return;
		var source = previewSource;
		closeCardPreview(true);
		if (source && source.focus) {
			suppressNextFocusPreview = true;
			source.focus({ preventScroll: true });
		}
	}

	function onDocumentPointerDown(event) {
		if (!previewSource || !cardPreview || cardPreview.hidden) return;
		if (cardPreview.contains(event.target) || previewSource.contains(event.target) || closestCard(event.target)) return;
		closeCardPreview(true);
	}

	function onDocumentKeyDown(event) {
		if (event.key === 'Escape') closeCardPreview(true);
	}

	function prioritizeVisibleImages() {
		if (imageObserver) imageObserver.disconnect();
		imageObserver = null;
		if (!grid || !window.IntersectionObserver) return;
		imageObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				var image = entry.target;
				image.fetchPriority = 'high';
				image.loading = 'eager';
				imageObserver.unobserve(image);
			});
		}, { rootMargin: '320px 0px' });
		Array.prototype.forEach.call(grid.querySelectorAll('img[loading="lazy"]'), function (image) { imageObserver.observe(image); });
	}

	function render(items) {
		if (!grid) return;
		closeCardPreview(true);
		var visibleItems = items || filteredItems();
		itemIndex = {};
		visibleItems.forEach(function (item) { itemIndex[String(item.season_id || '')] = item; });
		grid.innerHTML = visibleItems.map(renderCard).join('');
		if (empty) empty.hidden = visibleItems.length !== 0;
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

	function createRequestToken() {
		return {
			controller: window.AbortController ? new window.AbortController() : null,
			lifecycle: lifecycleId
		};
	}

	function isRequestCurrent(token) {
		return initialized && token && token.lifecycle === lifecycleId && grid && grid.isConnected;
	}

	function fetchOptions(body, token) {
		var options = {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		};
		if (token.controller) options.signal = token.controller.signal;
		return options;
	}

	function requestPage(status, page) {
		if (!config.ajaxUrl || state.pending[status]) return Promise.resolve(false);
		var requestToken = createRequestToken();
		state.pending[status] = requestToken;
		state.loading = true;
		if (state.status === String(status)) {
			setError('');
			setLive('正在读取追番数据…', 'loading');
			if (empty && !(state.datasets[status] || []).length) empty.hidden = true;
		}
		updateLoadButton();
		var body = new URLSearchParams();
		body.set('action', 'argon_bangumi_load');
		body.set('type', 'all');
		body.set('status', status);
		body.set('pn', page);
		if (config.nonce) body.set('nonce', config.nonce);
		return fetch(config.ajaxUrl, fetchOptions(body, requestToken))
			.then(function (response) { return response.json(); })
			.then(function (response) {
				if (!isRequestCurrent(requestToken)) return false;
				if (!response || !response.success) throw new Error(response && response.data && response.data.message ? response.data.message : '追番数据加载失败');
				var data = response.data || {};
				var responsePage = Number(data.page || page);
				state.datasets[status] = responsePage === 1 ? (data.items || []).slice() : dedupeMerge(state.datasets[status], data.items || []);
				state.pages[status] = responsePage;
				state.hasMore[status] = !!data.has_more;
				updateStats(data.stats);
				if (data.stats_ready === false) scheduleStatsPoll();
				if (state.status === String(status)) {
					setLive('');
					setError(data.error || '');
				}
				render();
				return true;
			})
			.catch(function (error) {
				if (!isRequestCurrent(requestToken) || (error && error.name === 'AbortError')) return false;
				if (state.status === String(status)) {
					setLive('');
					setError(error && error.message ? error.message : '追番数据加载失败');
				}
				return false;
			})
			.finally(function () {
				if (!isRequestCurrent(requestToken) || state.pending[status] !== requestToken) return;
				delete state.pending[status];
				state.loading = Object.keys(state.pending).length > 0;
				updateLoadButton();
			});
	}

	function scheduleStatsPoll() {
		if (!config.ajaxUrl || statsPollTimer || statsPollAttempts >= 3) return;
		var delays = [2000, 3000, 4000];
		var delay = delays[statsPollAttempts] || delays[delays.length - 1];
		statsPollAttempts += 1;
		statsPollTimer = window.setTimeout(function () {
			statsPollTimer = 0;
			requestStats();
		}, delay);
	}

	function requestStats() {
		if (!config.ajaxUrl || statsRequest) return Promise.resolve(false);
		var requestToken = createRequestToken();
		statsRequest = requestToken;
		var body = new URLSearchParams();
		body.set('action', 'argon_bangumi_stats');
		if (config.nonce) body.set('nonce', config.nonce);
		return fetch(config.ajaxUrl, fetchOptions(body, requestToken))
			.then(function (response) { return response.json(); })
			.then(function (response) {
				if (!isRequestCurrent(requestToken) || !response || !response.success) return false;
				var data = response.data || {};
				if (data.ready) {
					updateStats(data.stats);
					return true;
				}
				scheduleStatsPoll();
				return false;
			})
			.catch(function (error) {
				if (isRequestCurrent(requestToken) && (!error || error.name !== 'AbortError')) scheduleStatsPoll();
				return false;
			})
			.finally(function () {
				if (statsRequest === requestToken) statsRequest = null;
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

	function bindPageEvents() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-bangumi-status]'), function (button) {
			bind(button, 'click', function () { switchTab(button.getAttribute('data-bangumi-status')); });
		});
		Array.prototype.forEach.call(document.querySelectorAll('[data-bangumi-type]'), function (button) {
			bind(button, 'click', function () { switchType(button.getAttribute('data-bangumi-type')); });
		});
		bind(loadMoreButton, 'click', loadMore);
		bind(searchInput, 'input', function () { search(searchInput.value); });
		bind(grid, 'mouseover', onGridMouseOver);
		bind(grid, 'mouseout', onGridMouseOut);
		bind(grid, 'focusin', onGridFocusIn);
		bind(grid, 'focusout', onGridFocusOut);
		bind(grid, 'pointerdown', onGridPointerDown);
		bind(grid, 'click', onGridClick);
		bind(grid, 'error', onGridImageError, true);
		bind(cardPreview, 'mouseenter', clearPreviewCloseTimer);
		bind(cardPreview, 'mouseleave', function (event) {
			if (previewSource && event.relatedTarget && previewSource.contains(event.relatedTarget)) return;
			closeCardPreview(false);
		});
		bind(cardPreview, 'focusin', clearPreviewCloseTimer);
		bind(cardPreview, 'focusout', function (event) {
			if (cardPreview && cardPreview.contains(event.relatedTarget)) return;
			closeCardPreview(false);
		});
		bind(cardPreview, 'click', onPreviewClick);
		bind(document, 'pointerdown', onDocumentPointerDown, true);
		bind(document, 'keydown', onDocumentKeyDown);
		bind(window, 'scroll', function () { closeCardPreview(true); }, true);
		bind(window, 'resize', function () { closeCardPreview(true); });
		if (window.jQuery) {
			window.jQuery(document)
				.off('pjax:beforeReplace.argonBangumiPreview')
				.on('pjax:beforeReplace.argonBangumiPreview', destroy);
		}
	}

	function destroy() {
		lifecycleId += 1;
		initialized = false;
		if (statsPollTimer) window.clearTimeout(statsPollTimer);
		statsPollTimer = 0;
		statsPollAttempts = 0;
		if (statsRequest && statsRequest.controller) statsRequest.controller.abort();
		statsRequest = null;
		Object.keys(state.pending || {}).forEach(function (status) {
			var pending = state.pending[status];
			if (pending && pending.controller) pending.controller.abort();
		});
		state.pending = {};
		state.loading = false;
		if (imageObserver) imageObserver.disconnect();
		imageObserver = null;
		unbindAll();
		if (window.jQuery) window.jQuery(document).off('pjax:beforeReplace.argonBangumiPreview');
		if (grid) grid.removeAttribute('data-argon-bangumi-bound');
		closeCardPreview(true);
		if (cardPreview && cardPreview.parentNode) cardPreview.parentNode.removeChild(cardPreview);
		cardPreview = null;
		previewSource = null;
		itemIndex = {};
		grid = null;
		empty = null;
		loadMoreButton = null;
		liveStatus = null;
		errorBox = null;
		searchInput = null;
	}

	function init() {
		var nextGrid = document.getElementById('bangumi-grid');
		if (!nextGrid || nextGrid.getAttribute('data-argon-bangumi-bound') === 'true') return;
		if (initialized) destroy();
		readPageState();
		if (!grid) return;
		lifecycleId += 1;
		initialized = true;
		ensureStylesheet();
		grid.setAttribute('data-argon-bangumi-bound', 'true');
		var initialItems = Array.isArray(config.initialItems) ? config.initialItems : [];
		state.datasets[state.status] = initialItems;
		state.pages[state.status] = Number(config.initialPage || 1);
		state.hasMore[state.status] = !!config.initialHasMore;
		updateStats(config.stats);
		if (config.initialError) setError(config.initialError);
		ensureCardPreview();
		bindPageEvents();
		render();
		if (!config.enabled) return;
		if (!initialItems.length || config.initialNeedsRefresh) requestPage(state.status, 1);
		if (config.statsReady === false) scheduleStatsPoll();
	}

	if (window.ArgonBangumi && window.ArgonBangumi.__argonBangumiController) {
		window.ArgonBangumi.init();
		return;
	}
	window.ArgonBangumi = {
		__argonBangumiController: true,
		init: init,
		destroy: destroy,
		switchTab: switchTab,
		switchType: switchType,
		loadMore: loadMore,
		render: render,
		search: search
	};
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
}(window, document));
