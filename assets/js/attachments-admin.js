/* Argon 附件管理（文章编辑页 meta box） */
jQuery(function ($) {
	"use strict";

	var config = window.argonAttachmentsAdmin || {};
	var i18n = config.i18n || {};

	function getManager() {
		return $("#argon-attachments-manager");
	}

	function getPostId() {
		var $m = getManager();
		var id = $m.data("post-id");
		if (id) {
			return id;
		}
		if (window.wp && wp.data && wp.data.select && wp.data.select("core/editor")) {
			var editorPostId = wp.data.select("core/editor").getCurrentPostId();
			if (editorPostId) {
				return editorPostId;
			}
		}
		return 0;
	}

	function getList() {
		return $("#argon-attachments-admin-list");
	}

	function showInlineNotice(type, message) {
		var $notice = $(".argon-attachments-admin-notice");
		if (!$notice.length) {
			return;
		}
		var typeClass = type === "error" ? "notice-error" : (type === "success" ? "notice-success" : "notice-info");
		$notice.removeClass("notice-error notice-success notice-info")
			.addClass(typeClass)
			.text(message)
			.stop(true, true)
			.slideDown(150);

		if (type === "success") {
			setTimeout(function () {
				$notice.slideUp(200);
			}, 4000);
		}
	}

	function clearInlineNotice() {
		var $notice = $(".argon-attachments-admin-notice");
		if ($notice.length) {
			$notice.stop(true, true).slideUp(100).empty();
		}
	}

	function notify(type, message) {
		// 先在 meta box 内部显式展示就地提示，避免古腾堡下通知在视口角落容易被忽视
		showInlineNotice(type, message);

		try {
			if (window.wp && wp.data && wp.data.dispatch) {
				var dispatcher = wp.data.dispatch("core/notices");
				if (dispatcher && typeof dispatcher.createNotice === "function") {
					dispatcher.createNotice(type, message, { type: "snackbar", isDismissible: true });
					return;
				}
			}
		} catch (e) {
			// 经典编辑器下 core/notices store 未注册会抛错
		}
		// 若未能展示任何就地通知，则降级使用 alert
		if (!$(".argon-attachments-admin-notice").length && type === "error") {
			alert(message);
		}
	}

	// AJAX 失败时尽量给出可操作的原因：服务端校验失败（HTTP 4xx/5xx + JSON message）
	// 原样透传；nonce 失效时 admin-ajax 直接输出 -1，提示刷新页面重建 nonce
	function ajaxErrorMessage(xhr, fallback) {
		if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
			return xhr.responseJSON.data.message;
		}
		if (xhr && (xhr.responseText === "-1" || xhr.status === 403)) {
			return i18n.sessionExpired || fallback;
		}
		return fallback;
	}

	function formatSize(bytes) {
		if (bytes >= 1048576) {
			return (Math.round(bytes / 104857.6) / 10) + " MB";
		}
		if (bytes >= 1024) {
			return (Math.round(bytes / 102.4) / 10) + " KB";
		}
		return bytes + " B";
	}

	function post(action, data, done, fail) {
		data.action = action;
		data.nonce = config.nonce;
		data.post_id = getPostId();
		$.ajax({
			url: config.ajaxurl,
			type: "POST",
			data: data,
			success: function (resp) {
				if (resp && resp.success) {
					done(resp.data || {});
				} else {
					fail((resp && resp.data && resp.data.message) || i18n.saveFailed);
				}
			},
			error: function (xhr) {
				fail(ajaxErrorMessage(xhr, i18n.saveFailed));
			}
		});
	}

	function collectState() {
		var order = [];
		var names = {};
		getList().find(".argon-att-admin-row").each(function () {
			var uuid = $(this).data("uuid");
			order.push(uuid);
			names[uuid] = $(this).find(".argon-att-admin-name").val();
		});
		return { order: order, names: names };
	}

	function syncState() {
		var state = collectState();
		post("argon_attachment_update", state, function (data) {
			// 服务端对同名展示名自动追加 -N 后缀，把最终名回写到输入框
			if (data && data.names) {
				var renamed = [];
				getList().find(".argon-att-admin-row").each(function () {
					var uuid = $(this).data("uuid");
					if (data.names[uuid] !== undefined) {
						$(this).find(".argon-att-admin-name").val(data.names[uuid]);
						renamed.push(data.names[uuid]);
					}
				});
				if (renamed.length) {
					notify("info", (i18n.renamedTo || "Renamed: %s").replace("%s", renamed.join("、")));
				}
			}
		}, function (msg) {
			notify("error", msg || i18n.saveFailed);
		});
	}

	function buildRow(att) {
		var $row = $('<div class="argon-att-admin-row"></div>').attr("data-uuid", att.uuid);
		$row.append($('<span class="argon-att-admin-drag"><i class="fa fa-bars" aria-hidden="true"></i></span>').attr("title", i18n.dragTitle || ""));
		$row.append($('<input type="text" class="argon-att-admin-name" />').val(att.name).attr("placeholder", i18n.nameHolder || ""));
		$row.append($('<span class="argon-att-admin-size"></span>').text(att.size));
		$row.append($('<span class="argon-att-admin-count"></span>').text("0 " + (i18n.downloadTimes || "")));
		$row.append($('<button type="button" class="button argon-att-admin-insert"></button>').attr("data-style", "card").text(i18n.insertCard || "Insert card"));
		$row.append($('<button type="button" class="button argon-att-admin-insert"></button>').attr("data-style", "link").text(i18n.insertLink || "Insert link"));
		$row.append($('<button type="button" class="button argon-att-admin-copy"></button>').attr("data-url", att.download_url).text(i18n.copyLink || "Copy"));
		$row.append($('<button type="button" class="button argon-att-admin-delete"></button>').text(i18n.deleteText || "Delete"));
		return $row;
	}

	function refreshEmptyState() {
		var $l = getList();
		$l.find(".argon-attachments-admin-empty").toggle($l.find(".argon-att-admin-row").length === 0);
	}

	function initSortable() {
		if ($.fn.sortable) {
			var $l = getList();
			if ($l.length && !$l.data("ui-sortable")) {
				$l.sortable({
					items: ".argon-att-admin-row",
					handle: ".argon-att-admin-drag",
					axis: "y",
					stop: function () {
						syncState();
					}
				});
			}
		}
	}

	/* 事件委托：确保 Gutenberg 异步加载或组件重新渲染时不丢失事件绑定 */

	// 1. 点击上传按钮：重置 input 并打开系统文件选择框
	$(document).on("click", "#argon-attachment-upload-btn", function (e) {
		e.preventDefault();
		clearInlineNotice();
		var input = document.getElementById("argon-attachment-file-input");
		if (input) {
			input.value = "";
			input.click();
		}
	});

	// 2. 选择文件后的 change 事件
	$(document).on("change", "#argon-attachment-file-input", function () {
		var file = this.files && this.files[0];
		if (!file) {
			return;
		}

		clearInlineNotice();

		var ext = (file.name.split(".").pop() || "").toLowerCase();
		var isAllowed = !config.allowedExt ||
			config.allowedExt.length === 0 ||
			config.allowedExt.indexOf("*") !== -1 ||
			config.allowedExt.indexOf("all") !== -1 ||
			config.allowedExt.indexOf(ext) !== -1;

		if (!isAllowed) {
			notify("error", i18n.badExt || "不允许上传该格式的文件");
			this.value = "";
			return;
		}

		if (config.maxSize && config.maxSize > 0 && file.size > config.maxSize) {
			var tooBigMsg = i18n.tooBig || "";
			if (tooBigMsg.indexOf("%s") !== -1) {
				tooBigMsg = tooBigMsg.replace("%s", formatSize(config.maxSize));
			}
			notify("error", tooBigMsg || "文件超过大小上限");
			this.value = "";
			return;
		}

		var postId = getPostId();
		var formData = new FormData();
		formData.append("action", "argon_attachment_upload");
		formData.append("nonce", config.nonce);
		formData.append("post_id", postId);
		formData.append("file", file);

		var $uploadBtn = $("#argon-attachment-upload-btn");
		var $progress = $(".argon-attachments-admin-progress");
		var $progressBar = $(".argon-attachments-admin-progress-bar");

		if (!$uploadBtn.data("label")) {
			$uploadBtn.data("label", $uploadBtn.html());
		}
		$uploadBtn.prop("disabled", true).text(i18n.uploading);
		$progress.show();
		$progressBar.css("width", "0%");

		$.ajax({
			url: config.ajaxurl,
			type: "POST",
			data: formData,
			processData: false,
			contentType: false,
			xhr: function () {
				var xhr = new XMLHttpRequest();
				xhr.upload.addEventListener("progress", function (e) {
					if (e.lengthComputable) {
						$progressBar.css("width", Math.round(e.loaded / e.total * 100) + "%");
					}
				});
				return xhr;
			},
			success: function (resp) {
				if (resp && resp.success && resp.data) {
					getList().append(buildRow(resp.data));
					refreshEmptyState();
					initSortable();
					notify("success", (resp.data.name || "") + " ✓");
				} else {
					notify("error", (resp && resp.data && resp.data.message) || i18n.uploadFailed);
				}
			},
			error: function (xhr) {
				notify("error", ajaxErrorMessage(xhr, i18n.uploadFailed));
			},
			complete: function () {
				$uploadBtn.prop("disabled", false).html($uploadBtn.data("label"));
				$progress.hide();
				var input = document.getElementById("argon-attachment-file-input");
				if (input) {
					input.value = "";
				}
			}
		});
	});

	// 3. 删除
	$(document).on("click", ".argon-att-admin-delete", function () {
		var $row = $(this).closest(".argon-att-admin-row");
		if (!window.confirm(i18n.deleteConfirm)) {
			return;
		}
		var $btn = $(this).prop("disabled", true);
		post("argon_attachment_delete", { uuid: $row.data("uuid") }, function () {
			$row.remove();
			refreshEmptyState();
		}, function (msg) {
			$btn.prop("disabled", false);
			notify("error", msg || i18n.deleteFailed);
		});
	});

	// 4. 改名（失焦保存）
	var nameTimer = null;
	$(document).on("change input", ".argon-att-admin-name", function () {
		clearTimeout(nameTimer);
		nameTimer = setTimeout(syncState, 600);
	});

	// 5. 复制文本（下载链接与短代码兜底共用）
	function copyText(text, successMsg) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function () {
				notify("success", successMsg);
			}, function () {
				notify("error", i18n.copyFailed);
			});
		} else {
			var $tmp = $('<input type="text" style="position:absolute;left:-9999px;"/>').val(text).appendTo("body");
			$tmp[0].select();
			try {
				document.execCommand("copy");
				notify("success", successMsg);
			} catch (e) {
				notify("error", i18n.copyFailed);
			}
			$tmp.remove();
		}
	}

	// 6. 复制下载链接
	$(document).on("click", ".argon-att-admin-copy", function () {
		copyText($(this).data("url"), i18n.copied);
	});

	// 7. 插入附件短代码到编辑器：Gutenberg → TinyMCE → 文本域光标 → 兜底复制
	function insertAttachment(uuid, style) {
		var shortcode = '[attachment id="' + uuid + '"' + (style === "link" ? ' style="link"' : "") + "]";
		if (window.wp && wp.data && wp.data.dispatch && wp.blocks && wp.blocks.createBlock
			&& wp.data.select("core/block-editor")) {
			wp.data.dispatch("core/block-editor").insertBlocks(wp.blocks.createBlock("core/shortcode", { text: shortcode }));
			notify("success", i18n.inserted);
			return;
		}
		if (window.tinymce && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
			tinymce.activeEditor.insertContent(shortcode);
			notify("success", i18n.inserted);
			return;
		}
		var textarea = document.getElementById("content");
		if (textarea) {
			var start = textarea.selectionStart || 0;
			var end = textarea.selectionEnd || 0;
			textarea.value = textarea.value.slice(0, start) + shortcode + textarea.value.slice(end);
			textarea.selectionStart = textarea.selectionEnd = start + shortcode.length;
			textarea.focus();
			notify("success", i18n.inserted);
			return;
		}
		copyText(shortcode, i18n.insertCopied);
	}

	$(document).on("click", ".argon-att-admin-insert", function () {
		var $row = $(this).closest(".argon-att-admin-row");
		insertAttachment($row.data("uuid"), $(this).data("style"));
	});

	// 初始化排序
	initSortable();
});
