/* Settings navigation, editing state and backups. Feature-specific controls live with their PHP section. */
(function ($) {
	'use strict';
	var text = window.argonSettings.i18n;
	var $root = $('#argon-settings');
	var category = 'general';
	var pendingOnly = false;
	var rows = [];
	var sections = [];
	var renderTimer;
	var dialogTrigger;
	function controls(row) {
		return $(row).find('input[name], select[name], textarea[name]').filter(function () {
			return !/^(button|submit|reset|file)$/.test(this.type);
		});
	}
	function capture(row) {
		var occurrences = Object.create(null);
		var state = [];
		controls(row).each(function () {
			var occurrence = occurrences[this.name] || 0;
			occurrences[this.name] = occurrence + 1;
			var item = { name: this.name, occurrence: occurrence, value: $(this).val() };
			if (/^(checkbox|radio)$/.test(this.type)) item.checked = this.checked;
			state.push(item);
		});
		return JSON.stringify(state);
	}
	function dynamicSnapshot(row) {
		return $(row).find('[data-argon-dynamic-container]').toArray().map(function (node) { return node.innerHTML; });
	}
	function notify() { $root.trigger('argon:settings-state'); }
	function dirty(row, clearStatus) {
		if ($(row).data('argonRestoring')) return;
		$(row).toggleClass('is-dirty', capture(row) !== $(row).data('argonInitialState'));
		if (clearStatus && !$(row).data('argonSaving')) $(row).find('.argon-row-status').text('').removeClass('is-error is-success');
		notify();
	}
	function restore(row) {
		var $row = $(row);
		if ($row.data('argonSaving')) return;
		$row.data('argonRestoring', true);
		$row.find('[data-argon-dynamic-container]').each(function (index) { this.innerHTML = $row.data('argonInitialHtml')[index]; });
		var byName = Object.create(null);
		controls(row).each(function () { (byName[this.name] || (byName[this.name] = [])).push(this); });
		JSON.parse($row.data('argonInitialState')).forEach(function (item) {
			var node = byName[item.name] && byName[item.name][item.occurrence];
			if (!node) return;
			if (/^(checkbox|radio)$/.test(node.type)) node.checked = item.checked;
			else $(node).val(item.value);
		});
		controls(row).trigger('change');
		$row.data('argonRestoring', false);
		dirty(row, true);
	}
	function formData(row) {
		var data = new FormData();
		var arrayNames = Object.create(null);
		controls(row).each(function () {
			var name = this.name;
			if (/\[\]$/.test(name)) arrayNames[name.slice(0, -2)] = true;
			if (/^(checkbox|radio)$/.test(this.type)) {
				if (this.checked) data.append(name, this.value || 'true');
				else if (this.type === 'checkbox' && !/\[\]$/.test(name)) data.append(name, 'false');
			} else {
				var value = $(this).val();
				if (Array.isArray(value)) value.forEach(function (entry) { data.append(name, entry); });
				else data.append(name, value == null ? '' : value);
			}
		});
		Object.keys(arrayNames).forEach(function (name) { if (!data.has(name + '[]')) data.append(name, ''); });
		data.append('action', 'argon_save_themeoption_row');
		data.append('nonce', $('#main_form [name="argon_update_themeoptions_nonce"]').val());
		return data;
	}
	function requestError(xhr, fallback) {
		return xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ||
			(xhr.status === 403 || String(xhr.responseText).trim() === '-1' ? text.expired : fallback);
	}
	function save(row) {
		var $row = $(row);
		if ($row.data('argonSaving')) return;
		var invalid = controls(row).toArray().find(function (node) { return !node.disabled && node.checkValidity && !node.checkValidity(); });
		if (invalid) { invalid.reportValidity(); return; }
		var submitted = capture(row);
		var submittedHtml = dynamicSnapshot(row);
		var data = formData(row);
		var $buttons = $row.find('> td > .argon-row-actions button');
		var $status = $row.find('> td > .argon-row-actions .argon-row-status');
		$row.data('argonSaving', true).attr('aria-busy', 'true');
		$buttons.prop('disabled', true);
		$status.text(text.saving).removeClass('is-success is-error');
		$.ajax({url: window.ajaxurl, type: 'POST', data: data, processData: false, contentType: false, dataType: 'json', timeout: 30000})
			.done(function (response) {
				if (!response || !response.success) {
					$status.text(response && response.data && response.data.message || text.failed).addClass('is-error');
					return;
				}
				// 仅清除本次已提交的敏感值，保存过程中继续输入的内容仍保留为待保存。
				var state = JSON.parse(submitted);
				var occurrences = Object.create(null);
				controls(row).each(function () {
					var index = occurrences[this.name] || 0;
					occurrences[this.name] = index + 1;
					if (!this.hasAttribute('data-argon-sensitive')) return;
					var item = state.find(function (entry) { return entry.name === this.name && entry.occurrence === index; }, this);
					if (/^(checkbox|radio)$/.test(this.type)) { if (this.checked === item.checked) this.checked = false; item.checked = false; }
					else { if ($(this).val() === item.value) $(this).val(''); item.value = ''; }
				});
				$row.data('argonInitialState', JSON.stringify(state)).data('argonInitialHtml', submittedHtml);
				dirty(row, false);
				$status.text($row.hasClass('is-dirty') ? text.changed : text.saved).addClass('is-success');
			}).fail(function (xhr) { $status.text(requestError(xhr, text.network)).addClass('is-error'); })
			.always(function () { $row.data('argonSaving', false).removeAttr('aria-busy'); $buttons.prop('disabled', false); notify(); });
	}
	function normalized(value) { return String(value).normalize('NFKC').toLocaleLowerCase().replace(/\s+/g, ' ').trim(); }
	function initializeRows() {
		$('#main_form > .form-table > tbody > tr').each(function () {
			var $row = $(this);
			if (!$row.children('td').length) return;
			var $fields = controls(this);
			if ($fields.length && !$row.is('[data-argon-no-row-save]')) {
				$row.addClass('argon-setting-row');
				var $actions = $('<div>', {class: 'argon-row-actions'}).append($('<span>', {class: 'argon-row-status', role: 'status', 'aria-live': 'polite'}));
				$actions.append($('<button>', {type: 'button', class: 'button button-primary argon-row-save', text: text.save}));
				$actions.append($('<button>', {type: 'button', class: 'button argon-row-undo', text: text.undo}));
				$row.children('td').last().append($actions);
				$row.data('argonInitialState', capture(this)).data('argonInitialHtml', dynamicSnapshot(this));
			}
			// 搜索不读取密码、输入值或脚本代码。
			var $copy = $row.clone();
			$copy.find('script, style, input, textarea, .argon-row-actions, pre').remove();
			$row.data('argonSearchText', normalized($copy.text() + ' ' + $fields.filter(':not([data-argon-sensitive])').toArray().map(function (node) { return node.name; }).join(' ')));
			var $label = $row.children('th').find('label').first();
			var field = $fields.filter(':not([type=hidden])').first()[0];
			if (field && !$label.attr('for') && !$label.find('input,select,textarea').length) {
				if (!field.id) field.id = 'argon-setting-' + rows.length;
				$label.attr('for', field.id);
			}
			rows.push(this);
		});
		sections = $('.argon-settings-section').toArray();
		sections.forEach(function (section) {
			var count = $(section).children('.argon-setting-row').length;
			$('[data-settings-category="' + section.dataset.settingsSection + '"] .argon-settings-count').text(count);
		});
		$('[data-settings-category="all"] .argon-settings-count').text($('.argon-setting-row').length);
	}
	function filter(animate) {
		var query = normalized($('#argon-settings-search').val());
		$root.toggleClass('argon-settings-pending-filter', pendingOnly);
		var terms = query.split(' ').filter(Boolean);
		var before = new Map();
		var motion = window.ArgonMotion;
		if (animate && motion && !motion.prefersReducedMotion()) rows.forEach(function (row) {
			var rect = row.getBoundingClientRect();
			if (rect.height && rect.top < window.innerHeight && rect.bottom > 0) before.set(row, rect.top);
		});
		var total = 0;
		sections.forEach(function (section) {
			var selected = query || pendingOnly || category === 'all' || category === section.dataset.settingsSection;
			var count = 0;
			$(section).children('tr').has('> td').each(function () {
				var eligible = this.style.display !== 'none' || this.classList.contains('is-dirty');
				var show = selected && (!pendingOnly || this.classList.contains('is-dirty')) && terms.every(function (term) { return $(this).data('argonSearchText').includes(term); }, this);
				this.hidden = !show;
				if (show && eligible) count++;
			});
			section.hidden = !count;
			total += count;
			// 按原有层级判断标题下是否仍有结果，不留下空标题。
			var children = $(section).children('tr').toArray();
			children.forEach(function (row, index) {
				var heading = row.querySelector('th.subtitle h3, th.subtitle h4');
				if (!heading) return;
				var hasResult = false;
				for (var next = index + 1; next < children.length; next++) {
					var nextHeading = children[next].querySelector('th.subtitle h3, th.subtitle h4');
					if (nextHeading && nextHeading.tagName <= heading.tagName) break;
					if (children[next].querySelector(':scope > td') && !children[next].hidden && (children[next].style.display !== 'none' || pendingOnly && children[next].classList.contains('is-dirty'))) hasResult = true;
				}
				row.hidden = !hasResult;
			});
		});
		before.forEach(function (top, row) {
			var rect = row.getBoundingClientRect();
			var delta = top - rect.top;
			if (rect.height && delta && Math.abs(delta) < window.innerHeight && row.animate) row.animate([{transform: 'translateY(' + delta + 'px)'}, {transform: 'translateY(0)'}], {duration: motion.duration('fast'), easing: motion.easing('enter')});
		});
		$('#argon-settings-clear').prop('hidden', !query && !pendingOnly);
		$('#argon-settings-empty').prop('hidden', total > 0);
		$('#argon-settings-result-count').text(total + ' ' + text.results);
		var activeCategory = query || pendingOnly ? 'all' : category;
		$('[data-settings-category]').each(function () {
			var active = this.dataset.settingsCategory === activeCategory;
			$(this).toggleClass('is-active', active).attr('aria-current', active ? 'page' : null);
		});
		$('#argon-settings-view-title').text(pendingOnly ? text.pending : query ? text.all : $('[data-settings-category="' + category + '"]').clone().children().remove().end().text());
		var $outline = $('#argon-settings-outline').empty();
		if (!query && !pendingOnly && category !== 'all') {
			$('.argon-settings-section:not([hidden]) > tr:not([hidden]) > th.subtitle > h3[id]').each(function () { $('<a>', {href: '#' + this.id, text: this.textContent}).appendTo($outline); });
		}
	}
	function scheduleFilter() { clearTimeout(renderTimer); renderTimer = setTimeout(function () { filter(true); }, 100); }
	function status() {
		var dirtyCount = $('.argon-setting-row.is-dirty').length;
		$('#argon-settings-dirty-status').text(dirtyCount ? dirtyCount + ' ' + text.dirty : text.clean);
		$('#argon-settings-show-dirty').prop('disabled', !dirtyCount && !pendingOnly).attr('aria-pressed', pendingOnly ? 'true' : 'false');
		sections.forEach(function (section) {
			var count = $(section).children('.is-dirty').length;
			$('[data-settings-category="' + section.dataset.settingsSection + '"] .argon-settings-dirty-count').text(count).prop('hidden', !count);
		});
		if (pendingOnly) scheduleFilter();
	}
	function revealHash(scroll) {
		var hash;
		try { hash = decodeURIComponent(window.location.hash.slice(1)); } catch (_) { return; }
		if (hash === 'all') { category = 'all'; filter(false); return; }
		var node = document.getElementById(hash);
		var section = node && node.closest('[data-settings-section]');
		if (!section) return;
		category = section.dataset.settingsSection;
		$('#argon-settings-search').val('');
		pendingOnly = false;
		filter(false);
		status();
		if (scroll && node.getBoundingClientRect().height) node.scrollIntoView({block: 'start'});
	}
	function setValue(nodes, value) {
		var $nodes = $(nodes);
		if ($nodes.is(':radio')) {
			var matching = nodes.find(function (node) { return node.value === String(value); });
			if (!matching) throw new Error('invalid radio');
			$nodes.prop('checked', false); matching.checked = true;
		} else if ($nodes.is(':checkbox')) {
			if (/\[\]$/.test(nodes[0].name)) {
				if (!Array.isArray(value)) value = value === '' ? [] : [String(value)];
				$nodes.each(function () { this.checked = value.map(String).includes(this.value); });
			} else {
				if (![true, false, 'true', 'false', '1', '0', 1, 0].includes(value)) throw new Error('invalid checkbox');
				$nodes.prop('checked', value === true || value === 'true' || value === '1' || value === 1);
			}
		} else {
			if (/\[\]$/.test(nodes[0].name)) {
				if (!Array.isArray(value) || value.length !== nodes.length || value.some(function (entry) { return typeof entry !== 'string'; })) throw new Error('invalid list');
				nodes.forEach(function (node, index) { node.value = value[index]; });
				$nodes.trigger('change'); return;
			}
			if (value !== null && typeof value === 'object') throw new Error('invalid scalar');
			if ($nodes.is('select') && !$nodes.find('option').toArray().some(function (option) { return option.value === String(value); })) throw new Error('invalid option');
			$nodes.val(value == null ? '' : value);
		}
		$nodes.trigger('change');
	}
	function exportSettings() {
		var result = Object.create(null);
		['panic_date_name[]', 'panic_date_start[]', 'panic_date_end[]'].forEach(function (name) { result[name] = []; });
		$('#main_form .argon-setting-row').each(function () {
			var groups = Object.create(null);
			controls(this).filter(':not([data-argon-sensitive]):not([data-argon-internal])').each(function () { (groups[this.name] || (groups[this.name] = [])).push(this); });
			Object.keys(groups).forEach(function (name) {
				var nodes = groups[name];
				if (/\[\]$/.test(name)) result[name] = nodes.filter(function (node) { return !/^(checkbox|radio)$/.test(node.type) || node.checked; }).map(function (node) { return node.value; });
				else if (nodes[0].type === 'radio') result[name] = ($(nodes).filter(':checked').val() || '');
				else if (nodes[0].type === 'checkbox') result[name] = nodes[0].checked;
				else if (nodes.length === 1) result[name] = $(nodes[0]).val();
			});
		});
		return JSON.stringify(result, null, 2);
	}
	function importSettings(json) {
		var parsed = typeof json === 'string' ? JSON.parse(json) : json;
		if (!parsed || Array.isArray(parsed) || typeof parsed !== 'object') throw new Error(text.importInvalid);
		var dateKeys = ['panic_date_name[]', 'panic_date_start[]', 'panic_date_end[]'];
		if (dateKeys.some(function (key) { return Object.hasOwn(parsed, key); })) {
			if (!dateKeys.every(function (key) { return Array.isArray(parsed[key]) && parsed[key].length === parsed[dateKeys[0]].length && parsed[key].every(function (value) { return typeof value === 'string'; }); })) throw new Error(text.importInvalid);
			var $dates = $('#panic-mode-date-ranges').empty();
			parsed[dateKeys[0]].forEach(function (_, index) {
				var $item = $('<div>', {class: 'panic-date-range-item'});
				dateKeys.forEach(function (name, part) { $item.append($('<input>', {type: part ? 'date' : 'text', name: name, value: parsed[name][index]})); });
				$item.append($('<button>', {type: 'button', class: 'button remove-panic-date-range', text: '×'}));
				$dates.append($item);
			});
			$dates.closest('tr').find('[data-argon-internal]').trigger('change');
		}
		var groups = Object.create(null);
		$('#main_form .argon-setting-row').each(function () { controls(this).each(function () { (groups[this.name] || (groups[this.name] = [])).push(this); }); });
		var skipped = 0;
		Object.keys(parsed).forEach(function (name) {
			var nodes = groups[name];
			if (!nodes && dateKeys.includes(name) && parsed[name].length === 0) return;
			if (!nodes || $(nodes).is('[data-argon-sensitive], [data-argon-internal]')) { skipped++; return; }
			try { setValue(nodes, parsed[name]); } catch (_) { skipped++; }
		});
		return skipped;
	}
	window.exportArgonSettings = exportSettings;
	window.importArgonSettings = importSettings;
	$(function () {
		if (!$root.length) return;
		$('#main_form table.widefat').wrap('<div class="argon-settings-table-scroll"></div>');
		initializeRows();
		$root.addClass('argon-settings-ready');
		filter(false);
		revealHash(true);
		status();
		var conditionalRows = new MutationObserver(scheduleFilter);
		rows.forEach(function (row) { conditionalRows.observe(row, {attributes: true, attributeFilter: ['style']}); });
		$root.on('argon:settings-state', status);
		$('#main_form').on('submit', function (event) { event.preventDefault(); });
		$('#main_form').on('change', '[name="argon_article_meta"], [name="argon_search_filters_type"]', function () {
			var meta = this.name === 'argon_article_meta';
			var active = document.getElementById(meta ? 'article_meta_active' : 'search_filters_active');
			var inactive = document.getElementById(meta ? 'article_meta_inactive' : 'search_filters_inactive');
			var selector = meta ? '.article-meta-item' : '.search-filter-item';
			var attribute = meta ? 'meta-name' : 'filter-name';
			var items = Array.from(active.querySelectorAll(selector)).concat(Array.from(inactive.querySelectorAll(selector)));
			items.forEach(function (item) { inactive.appendChild(item); });
			this.value.split(meta ? '|' : ',').forEach(function (value) {
				var checked = !meta && value.startsWith('*');
				var name = checked ? value.slice(1) : value;
				var item = items.find(function (entry) { return entry.getAttribute(attribute) === name; });
				if (item) { if (!meta) item.classList.toggle('active', checked); active.appendChild(item); }
			});
		});
		$root.on('input change', '.argon-setting-row input, .argon-setting-row select, .argon-setting-row textarea', function () { dirty(this.closest('.argon-setting-row'), true); scheduleFilter(); });
		$root.on('click', '.argon-row-save', function () { save(this.closest('.argon-setting-row')); });
		$root.on('click', '.argon-row-undo', function () { restore(this.closest('.argon-setting-row')); });
		$('#argon-settings-search').on('input', scheduleFilter);
		$('#argon-settings-clear').on('click', function () { $('#argon-settings-search').val('').trigger('focus'); pendingOnly = false; status(); filter(true); });
		$('#argon-settings-show-dirty').on('click', function () { pendingOnly = !pendingOnly; status(); filter(true); });
		$root.on('click', '[data-settings-category]', function (event) {
			event.preventDefault();
			category = this.dataset.settingsCategory;
			pendingOnly = false;
			$('#argon-settings-search').val('');
			window.history.replaceState(null, '', this.hash);
			filter(false); status();
			$('#argon-settings-view-title')[0].scrollIntoView({block: 'start'});
		});
		$root.on('click', '#argon-settings-outline a', function (event) { event.preventDefault(); window.history.replaceState(null, '', this.hash); revealHash(true); });
		$(window).on('hashchange', function () { revealHash(true); });
		$(window).on('beforeunload', function (event) { if ($('.argon-setting-row.is-dirty').length) { event.preventDefault(); event.originalEvent.returnValue = ''; } });
		$('#argon-settings-export').on('click', function () {
			dialogTrigger = this;
			$('#exported_settings_json').val(exportSettings());
			var dialog = $('#argon-settings-export-dialog')[0];
			if (window.ArgonMotion) window.ArgonMotion.fast(dialog, true, {className: 'is-open'});
			dialog.showModal(); $('#exported_settings_json')[0].select();
		});
		$('#argon-settings-export-close').on('click', function () { $('#argon-settings-export-dialog')[0].close(); });
		$('#argon-settings-export-dialog').on('close', function () { if (window.ArgonMotion) window.ArgonMotion.fast(this, false, {className: 'is-open'}); if (dialogTrigger) dialogTrigger.focus(); });
		$('#argon-settings-import').on('click', function () {
			var json = window.prompt(text.importPrompt);
			if (!json) return;
			try { var skipped = importSettings(json); pendingOnly = true; $('#argon-settings-search').val(''); status(); filter(false); window.alert(text.imported + (skipped ? '\n' + skipped + ' ' + text.importSkipped : '')); }
			catch (_) { window.alert(text.importInvalid); }
		});
		$('#argon-test-friend-link-worker').on('click', function () {
			var $button = $(this).prop('disabled', true);
			var $status = $('#argon-friend-link-worker-test-status').text(text.testing).removeClass('is-success is-error');
			$.post(window.ajaxurl, {action: 'argon_test_friend_link_worker', nonce: $('#main_form [name="argon_update_themeoptions_nonce"]').val(), worker_url: $('[name="argon_friend_link_worker_url"]').val()})
				.done(function (response) { $status.text(response && response.data && response.data.message || text.invalidWorker).toggleClass('is-success', !!(response && response.success)).toggleClass('is-error', !(response && response.success)); })
				.fail(function (xhr) { $status.text(requestError(xhr, text.workerFailed)).addClass('is-error'); }).always(function () { $button.prop('disabled', false); });
	});
	});
})(jQuery);
