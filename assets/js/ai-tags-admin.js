(function ($) {
	'use strict';
	$(function () {
		var config = window.argonAiTags;
		if (!config || !$('#argon-ai-tags').length) return;
		var token = '';
		var busy = false;
		var $rows = $('#argon-ai-tags-rows');
		var $status = $('#argon-ai-tags-status');
		var $apply = $('#argon-ai-tags-apply');

		function updateControls() {
			$('#argon-ai-tags-preview, #argon-ai-tags-frequency, #argon-ai-tags-select-all').prop('disabled', busy);
			$rows.find('input').prop('disabled', busy);
			$apply.prop('disabled', busy || !$rows.find('input:checked').length);
		}
		function request(action, data) {
			return new Promise(function (resolve, reject) {
				$.ajax({
					url: config.url, type: 'POST', timeout: 180000,
					data: $.extend({action: action, nonce: config.nonce}, data),
					success: function (response) {
						if (response && response.success && response.data) resolve(response.data);
						else reject(new Error(response && response.data && response.data.message || config.i18n.failed));
					},
					error: function (xhr) {
						reject(new Error(xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ||
							(xhr.status === 403 || String(xhr.responseText).trim() === '-1' ? config.i18n.expired : config.i18n.failed)));
					}
				});
			});
		}
		$('#argon-ai-tags-preview').on('click', async function () {
			if (busy) return;
			busy = true;
			token = '';
			$rows.empty();
			$('#argon-ai-tags-results').prop('hidden', true);
			$('#argon-ai-tags-select-all').prop('checked', false);
			updateControls();
			$status.text(config.i18n.analyzing);
			try {
				var response;
				do {
					response = await request('argon_ai_tags_preview', {token: token, max_count: $('#argon-ai-tags-frequency').val()});
					token = response.token;
					$status.text(config.i18n.analyzing + response.processed + ' / ' + response.total);
				} while (!response.done);
				response.merges.forEach(function (merge) {
					var $row = $('<tr>');
					$('<td>').append($('<input>', {type: 'checkbox', value: merge.source_id, 'aria-label': config.i18n.select})).appendTo($row);
					$('<td>').text(merge.source.name + ' / ' + merge.source.count).appendTo($row);
					$('<td>').text(merge.target.name + ' / ' + merge.target.count).appendTo($row);
					$('<td>').text(merge.reason).appendTo($row);
					$rows.append($row);
				});
				$('#argon-ai-tags-results').prop('hidden', !response.merges.length);
				$status.text(response.merges.length ? config.i18n.ready : config.i18n.empty);
			} catch (error) {
				$status.text(error.message);
			} finally {
				busy = false;
				updateControls();
			}
		});
		$('#argon-ai-tags-select-all').on('change', function () {
			$rows.find('input').prop('checked', this.checked);
			updateControls();
		});
		$rows.on('change', 'input', function () {
			$('#argon-ai-tags-select-all').prop('checked', $rows.find('input:checked').length === $rows.find('input').length);
			updateControls();
		});
		$apply.on('click', async function () {
			if (busy || !window.confirm(config.i18n.confirm)) return;
			var selected = $rows.find('input:checked').toArray();
			busy = true;
			updateControls();
			try {
				for (var i = 0; i < selected.length; i++) {
					var response;
					do {
						$status.text(config.i18n.merging + (i + 1) + ' / ' + selected.length);
						response = await request('argon_ai_tags_apply', {token: token, source_id: selected[i].value});
					} while (!response.done);
					$(selected[i]).closest('tr').remove();
				}
				$status.text(config.i18n.done);
			} catch (error) {
				$status.text(error.message + ' ' + config.i18n.partial);
			} finally {
				busy = false;
				$('#argon-ai-tags-select-all').prop('checked', false);
				updateControls();
			}
		});
	});
})(jQuery);
