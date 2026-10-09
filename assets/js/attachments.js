/* Argon 附件前端脚本：原位静默下载 + 居中验证码弹窗 + 弹窗预览。
 *
 * 下载：点击下载链接后在当前页面完成鉴权与下发，页面不跳转，不新建标签页；
 *       若开启了验证码，在页面正中央弹出极简验证码卡片，通过后直接触发下载。
 * 预览：支持预览的格式（pdf/docx/xlsx/pptx/md/zip）点击卡片或眼睛按钮，
 *       打开共享弹窗；服务端首次生成静态快照（CDN 可缓存）。
 * 全部使用事件委托，PJAX 导航后无需重新初始化。
 */
(function () {
	"use strict";

	if (window.__argonAttachmentsBound) {
		return;
	}
	window.__argonAttachmentsBound = true;

	function ajaxUrl() {
		if (window.argonConfig && window.argonConfig.wp_path) {
			return window.argonConfig.wp_path + "wp-admin/admin-ajax.php";
		}
		return "/wp-admin/admin-ajax.php";
	}

	function post(action, data) {
		var body = "action=" + encodeURIComponent(action);
		Object.keys(data || {}).forEach(function (key) {
			body += "&" + encodeURIComponent(key) + "=" + encodeURIComponent(data[key]);
		});
		return fetch(ajaxUrl(), {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
			credentials: "same-origin",
			body: body
		}).then(function (response) { return response.json(); });
	}

	/* ---------------- 下载 ---------------- */

	function bumpCounter(btn) {
		var card = btn.closest ? btn.closest(".argon-attachment-item") : null;
		var counter = card ? card.querySelector(".argon-attachment-downloads") : null;
		if (!counter) {
			return;
		}
		var current = parseInt(counter.getAttribute("data-count") || counter.textContent, 10);
		if (isNaN(current)) {
			return;
		}
		counter.setAttribute("data-count", current + 1);
		counter.textContent = current + 1;
	}

	function downloadUrlForUuid(uuid) {
		var base = (window.argonConfig && window.argonConfig.wp_path) ? window.argonConfig.wp_path : "/";
		return base + "?download=" + encodeURIComponent(uuid);
	}

	function handleDownload(btn) {
		var uuid = btn ? btn.getAttribute("data-uuid") : "";
		if (!uuid) return;

		// 1. 本地计数乐观 +1
		bumpCounter(btn);

		// 2. 在同步用户交互事件栈中立即打开中转页（0ms 延迟，不被浏览器弹窗拦截，主页面毫无卡顿）
		var targetUrl = downloadUrlForUuid(uuid);
		window.open(targetUrl, "_blank");
	}

	/* ---------------- 居中验证码弹窗 ---------------- */

	function getCaptchaModal() {
		var modal = document.querySelector(".argon-attachment-captcha-modal");
		if (modal && modal.parentElement !== document.body) {
			document.body.appendChild(modal);
		}
		return modal;
	}

	function openCaptchaModal(msg) {
		var modal = getCaptchaModal();
		if (!modal) return;
		var errEl = modal.querySelector(".argon-att-captcha-error");
		if (errEl) {
			errEl.hidden = !msg;
			errEl.textContent = msg || "";
		}
		var input = modal.querySelector(".argon-att-math-input");
		if (input) {
			input.value = "";
		}
		modal.hidden = false;
		void modal.offsetWidth;
		modal.classList.add("is-open");
		document.documentElement.classList.add("argon-modal-locked");
		if (input) {
			setTimeout(function() { input.focus(); }, 100);
		}
	}

	function closeCaptchaModal() {
		var modal = getCaptchaModal();
		if (!modal || modal.hidden) return;
		modal.classList.remove("is-open");
		if (!document.querySelector(".argon-attachment-preview-modal.is-open") && !document.querySelector(".modal.show")) {
			document.documentElement.classList.remove("argon-modal-locked");
		}
		setTimeout(function() {
			modal.hidden = true;
		}, 150);
	}

	function showCaptchaError(msg) {
		var modal = getCaptchaModal();
		if (!modal) return;
		var errEl = modal.querySelector(".argon-att-captcha-error");
		if (errEl) {
			errEl.hidden = false;
			errEl.textContent = msg;
		}
	}

	/* ---------------- 预览弹窗 ---------------- */

		var currentZipTree = null;
		var currentZipPath = [];

		function renderZipExplorer(container) {
			if (!currentZipTree || !container) return;
			var explorer = container.querySelector(".argon-zip-explorer");
			if (!explorer) {
				// 若外部只注入了 raw innerHTML，包裹成完整 explorer
				var rawData = container.getAttribute("data-tree");
				if (rawData) {
					try { currentZipTree = JSON.parse(rawData); } catch (e) {}
				}
			}
			if (!explorer) return;

			var node = currentZipTree;
			for (var i = 0; i < currentZipPath.length; i++) {
				var seg = currentZipPath[i];
				if (node && node.dirs && node.dirs[seg]) {
					node = node.dirs[seg];
				} else {
					currentZipPath = currentZipPath.slice(0, i);
					break;
				}
			}

			// 渲染面包屑
			var breadcrumb = explorer.querySelector(".argon-zip-breadcrumb");
			if (breadcrumb) {
				var crumbsHtml = '<span class="argon-zip-crumb" data-index="-1"><i class="fa fa-home"></i> 根目录</span>';
				for (var c = 0; c < currentZipPath.length; c++) {
					crumbsHtml += '<span class="argon-zip-crumb-sep">/</span><span class="argon-zip-crumb" data-index="' + c + '">' + argonEscapeHtml(currentZipPath[c]) + '</span>';
				}
				breadcrumb.innerHTML = crumbsHtml;
			}

			// 返回上一级按钮状态
			var upBtn = explorer.querySelector(".argon-zip-up");
			if (upBtn) {
				upBtn.disabled = currentZipPath.length === 0;
			}

			// 渲染当前目录清单
			var itemsContainer = explorer.querySelector(".argon-zip-items");
			if (itemsContainer) {
				var itemsHtml = '';
				var dirs = (node && node.dirs) ? Object.keys(node.dirs).sort(function (a, b) { return a.localeCompare(b); }) : [];
				var files = (node && node.files) ? node.files.slice().sort(function (a, b) { return (a.name || '').localeCompare(b.name || ''); }) : [];

				if (dirs.length === 0 && files.length === 0) {
					itemsHtml = '<div class="argon-zip-empty"><i class="fa fa-folder-open-o fa-2x mb-2 d-block text-muted"></i>当前文件夹为空</div>';
				} else {
					for (var d = 0; d < dirs.length; d++) {
						var dName = dirs[d];
						var dNode = node.dirs[dName];
						var subCount = ((dNode.dirs ? Object.keys(dNode.dirs).length : 0) + (dNode.files ? dNode.files.length : 0));
						var metaText = subCount + ' 个项目';
						if (dNode.overflow) metaText += '（深层 +' + dNode.overflow + '）';

						itemsHtml += '<div class="argon-zip-item is-dir" data-dir="' + argonEscapeAttribute(dName) + '">'
							+ '<span class="argon-zip-item-icon"><i class="fa fa-folder"></i></span>'
							+ '<span class="argon-zip-item-name">' + argonEscapeHtml(dName) + '</span>'
							+ '<span class="argon-zip-item-meta">' + argonEscapeHtml(metaText) + '</span>'
							+ '</div>';
					}
					for (var f = 0; f < files.length; f++) {
						var file = files[f];
						var ext = (file.name || '').split('.').pop().toLowerCase();
						var fileIcon = 'fa-file-o';
						if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(ext) !== -1) fileIcon = 'fa-file-image-o';
						else if (['mp3', 'flac', 'wav'].indexOf(ext) !== -1) fileIcon = 'fa-file-audio-o';
						else if (['mp4', 'mkv', 'avi'].indexOf(ext) !== -1) fileIcon = 'fa-file-video-o';
						else if (['txt', 'md', 'json', 'js', 'css', 'html', 'php', 'py', 'c', 'cpp'].indexOf(ext) !== -1) fileIcon = 'fa-file-code-o';
						else if (['zip', 'rar', '7z', 'tar', 'gz'].indexOf(ext) !== -1) fileIcon = 'fa-file-archive-o';
						else if (['pdf'].indexOf(ext) !== -1) fileIcon = 'fa-file-pdf-o';

						itemsHtml += '<div class="argon-zip-item is-file">'
							+ '<span class="argon-zip-item-icon"><i class="fa ' + fileIcon + '"></i></span>'
							+ '<span class="argon-zip-item-name">' + argonEscapeHtml(file.name || '') + '</span>'
							+ '<span class="argon-zip-item-meta">' + argonFormatSize(file.size || 0) + '</span>'
							+ '</div>';
					}
				}
				itemsContainer.innerHTML = itemsHtml;
			}
		}

		function argonEscapeHtml(str) {
			return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}

		function argonEscapeAttribute(str) {
			return (str || '').replace(/"/g, '&quot;');
		}

		function argonFormatSize(bytes) {
			if (!bytes || bytes <= 0) return '0 B';
			var units = ['B', 'KB', 'MB', 'GB'];
			var i = Math.floor(Math.log(bytes) / Math.log(1024));
			if (i >= units.length) i = units.length - 1;
			return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
		}

		function getModal() {
			var modal = document.getElementById("argon_attachment_preview_modal") || document.querySelector(".argon-attachment-preview-modal");
			if (modal && modal.parentElement !== document.body) {
				// 将 modal 移到 body 直属子节点，脱离文章卡片的 stacking context
				document.body.appendChild(modal);
			}
			return modal;
		}

		function modalParts(modal) {
			return {
				icon: modal.querySelector(".argon-attachment-preview-icon i"),
				title: modal.querySelector(".argon-attachment-preview-title"),
				download: modal.querySelector(".argon-attachment-preview-download"),
				loading: modal.querySelector(".argon-attachment-preview-loading"),
				frame: modal.querySelector(".argon-attachment-preview-frame"),
				pdf: modal.querySelector(".argon-attachment-preview-pdf"),
				canvas: modal.querySelector(".argon-pdf-canvas"),
				pageCurrent: modal.querySelector(".argon-pdf-current"),
				pageTotal: modal.querySelector(".argon-pdf-total"),
				scaleText: modal.querySelector(".argon-pdf-scale-text"),
				zip: modal.querySelector(".argon-attachment-preview-zip"),
				unsupported: modal.querySelector(".argon-attachment-preview-unsupported")
			};
		}

		function showPanel(parts, which) {
			parts.loading.hidden = which !== "loading";
			parts.frame.hidden = which !== "frame";
			parts.pdf.hidden = which !== "pdf";
			parts.zip.hidden = which !== "zip";
			parts.unsupported.hidden = which !== "unsupported";
		}

		function ensurePdfJs() {
			if (window.pdfjsLib) {
				if (!window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
					window.pdfjsLib.GlobalWorkerOptions.workerSrc = "https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/pdf.js/2.12.313/pdf.worker.min.js";
				}
				return Promise.resolve(window.pdfjsLib);
			}
			return new Promise(function (resolve, reject) {
				var script = document.createElement("script");
				script.src = "https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/pdf.js/2.12.313/pdf.min.js";
				script.onload = function () {
					if (window.pdfjsLib) {
						window.pdfjsLib.GlobalWorkerOptions.workerSrc = "https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/pdf.js/2.12.313/pdf.worker.min.js";
						resolve(window.pdfjsLib);
					} else {
						reject(new Error("pdfjsLib not available"));
					}
				};
				script.onerror = function () {
					reject(new Error("pdf.js load failed"));
				};
				document.head.appendChild(script);
			});
		}

		function renderPdfPage(parts, pageNum) {
			if (!currentPdfDoc || isPdfRendering) return;
			isPdfRendering = true;
			currentPdfDoc.getPage(pageNum).then(function (page) {
				var canvas = parts.canvas;
				var ctx = canvas.getContext("2d");
				var dpr = window.devicePixelRatio || 1;
				var viewport = page.getViewport({ scale: currentPdfScale * dpr });

				canvas.width = viewport.width;
				canvas.height = viewport.height;
				canvas.style.width = (viewport.width / dpr) + "px";
				canvas.style.height = (viewport.height / dpr) + "px";

				var renderContext = {
					canvasContext: ctx,
					viewport: viewport
				};
				return page.render(renderContext).promise;
			}).then(function () {
				isPdfRendering = false;
				parts.pageCurrent.textContent = currentPdfPage;
				parts.pageTotal.textContent = currentPdfTotal;
				parts.scaleText.textContent = Math.round(currentPdfScale * 100) + "%";
			}).catch(function (err) {
				isPdfRendering = false;
			});
		}

		function closePreview() {
			var modal = getModal();
			if (!modal) {
				return;
			}
			if (window.jQuery && typeof window.jQuery.fn.modal === "function") {
				window.jQuery(modal).modal("hide");
			} else {
				modal.classList.remove("show");
				modal.style.display = "none";
				document.documentElement.classList.remove("argon-modal-locked");
			}
			var parts = modalParts(modal);
			if (parts.frame) parts.frame.src = "about:blank";
			if (parts.zip) parts.zip.innerHTML = "";
			currentPdfDoc = null;
		}

		function openPreview(item) {
			var modal = getModal();
			if (!modal) {
				return;
			}
			var uuid = item.getAttribute("data-uuid");
			if (!uuid) {
				return;
			}
			var downloadBtn = item.querySelector(".argon-attachment-download");
			var name = downloadBtn ? downloadBtn.getAttribute("data-name") : "";
			var downloadHref = downloadBtn ? downloadBtn.getAttribute("href") : "#";
			var iconClass = item.querySelector(".argon-attachment-icon i");
			var parts = modalParts(modal);

			parts.title.textContent = name || "";
			parts.icon.className = iconClass ? iconClass.className : "fa fa-file-o";
			parts.download.setAttribute("data-uuid", uuid);
			parts.download.setAttribute("data-name", name || "");
			parts.download.setAttribute("href", downloadHref);
			showPanel(parts, "loading");

			if (window.jQuery && typeof window.jQuery.fn.modal === "function") {
				window.jQuery(modal).modal("show");
			} else {
				modal.style.display = "block";
				modal.classList.add("show");
				document.documentElement.classList.add("argon-modal-locked");
			}

			post("argon_attachment_preview_open", { uuid: uuid })
				.then(function (resp) {
					if (!resp || !resp.success || !resp.data) {
						throw new Error((resp && resp.data && resp.data.message) || "preview failed");
					}
					var data = resp.data;
						if (data.type === "unsupported") {
							if (data.message && parts.unsupported) {
								parts.unsupported.innerHTML = '<i class="fa fa-info-circle text-info" aria-hidden="true"></i> <span>' + argonEscapeHtml(data.message) + '</span>';
							}
							showPanel(parts, "unsupported");
							return;
						}
						if (data.type === "pdf") {
							var pdfUrl = data.url;
							if (location.protocol === "https:" && pdfUrl.indexOf("http:") === 0) {
								pdfUrl = "https:" + pdfUrl.substring(5);
							}
							ensurePdfJs()
								.then(function (pdfjs) {
									var loadingTask = pdfjs.getDocument({
										url: pdfUrl,
										cMapUrl: "https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/pdf.js/2.12.313/cmaps/",
										cMapPacked: true
									});
									return loadingTask.promise;
								})
								.then(function (pdf) {
									currentPdfDoc = pdf;
									currentPdfPage = 1;
									currentPdfTotal = pdf.numPages;
									currentPdfScale = 1.25;
									showPanel(parts, "pdf");
									renderPdfPage(parts, 1);
								})
								.catch(function (pdfErr) {
									console.error("PDF.js render error:", pdfErr);
									// 若 fetch 模式被拦截，回退为以 #toolbar=0 精简模式内嵌
									parts.frame.src = pdfUrl + "#toolbar=0&navpanes=0&scrollbar=1";
									showPanel(parts, "frame");
								});
							return;
						}
						if (data.type === "archive" || data.type === "zip") {
							function applyZipHtml(html) {
							parts.zip.innerHTML = html;
							var explorer = parts.zip.querySelector(".argon-zip-explorer");
							if (explorer) {
								var treeData = explorer.getAttribute("data-tree");
								if (treeData) {
									try { currentZipTree = JSON.parse(treeData); } catch (e) { currentZipTree = null; }
								}
								currentZipPath = [];
								renderZipExplorer(parts.zip);
							}
							showPanel(parts, "zip");
						}

						if (data.html && data.html.trim() !== "") {
							applyZipHtml(data.html);
							return;
						}

						var zipUrl = data.url;
						if (location.protocol === "https:" && zipUrl.indexOf("http:") === 0) {
							zipUrl = "https:" + zipUrl.substring(5);
						}
						if (zipUrl.indexOf("?") === -1) {
							zipUrl += "?_t=" + Date.now();
						} else {
							zipUrl += "&_t=" + Date.now();
						}
						fetch(zipUrl, { credentials: "same-origin" })
							.then(function (r) {
								if (!r.ok) throw new Error("zip snapshot " + r.status);
								return r.text();
							})
							.then(function (html) {
								if (!html || html.trim() === "") {
									throw new Error("empty zip html");
								}
								applyZipHtml(html);
							})
							.catch(function (err) {
								console.error("ZIP snapshot load error:", err);
								parts.frame.src = data.url;
								showPanel(parts, "frame");
							});
						return;
					}
						// html 快照：iframe 加载静态 URL（CDN 可缓存）
						function syncIframeDarkmode() {
							try {
								var isDark = document.documentElement.classList.contains("darkmode");
								var isAmoled = document.documentElement.classList.contains("amoled-dark");
								if (parts.frame.contentWindow && parts.frame.contentWindow.document) {
									var frameDoc = parts.frame.contentWindow.document;
									if (isDark) {
										frameDoc.documentElement.classList.add("darkmode");
										if (frameDoc.body) frameDoc.body.classList.add("darkmode");
										if (isAmoled) {
											frameDoc.documentElement.classList.add("amoled-dark");
											if (frameDoc.body) frameDoc.body.classList.add("amoled-dark");
										} else {
											frameDoc.documentElement.classList.remove("amoled-dark");
											if (frameDoc.body) frameDoc.body.classList.remove("amoled-dark");
										}
									} else {
										frameDoc.documentElement.classList.remove("darkmode", "amoled-dark");
										if (frameDoc.body) frameDoc.body.classList.remove("darkmode", "amoled-dark");
									}
								}
								if (parts.frame.contentWindow && parts.frame.contentWindow.postMessage) {
									parts.frame.contentWindow.postMessage({ type: "argon_theme_change", isDark: isDark, isAmoled: isAmoled }, "*");
								}
							} catch (e) {}
						}
						parts.frame.onload = syncIframeDarkmode;
						parts.frame.src = data.url;
						showPanel(parts, "frame");
						// 预先派发一次以应对部分快速载入缓存
						setTimeout(syncIframeDarkmode, 100);
				})
				.catch(function (err) {
					closePreview();
					if (window.confirm((err && err.message ? err.message + "\n" : "") + "预览加载失败，是否直接下载该文件？")) {
						handleDownload(parts.download);
					}
				});
		}

	/* ---------------- 事件委托 ---------------- */

	document.addEventListener("click", function (event) {
		var target = event.target;
		if (!target.closest) {
			return;
		}

		// 预览弹窗关闭：遮罩 / 关闭按钮
		if (target.closest(".argon-attachment-preview-mask") || target.closest(".argon-attachment-preview-close")) {
			closePreview();
			return;
		}

		// 验证码弹窗关闭
		if (target.closest(".argon-attachment-captcha-mask") || target.closest(".argon-attachment-captcha-close")) {
			closeCaptchaModal();
			pendingDownloadTask = null;
			return;
		}

		// 数学验证码提交
		var submitBtn = target.closest(".argon-att-captcha-submit");
		if (submitBtn) {
			event.preventDefault();
			var modal = getCaptchaModal();
			var input = modal ? modal.querySelector(".argon-att-math-input") : null;
			var seedInput = modal ? modal.querySelector(".argon-att-math-seed") : null;
			var answer = input ? input.value.trim() : "";
			var seed = seedInput ? seedInput.value : "";
			if (!answer) {
				showCaptchaError("请输入验证码答案");
				return;
			}
			handleDownload(pendingDownloadTask ? pendingDownloadTask.btn : null, {
				comment_captcha: answer,
				comment_captcha_seed: seed
			});
			return;
		}

		// 下载按钮：点击时请求鉴权下发并在新窗口中触发下载
		var downloadBtn = target.closest(".argon-attachment-download");
		if (downloadBtn && downloadBtn.getAttribute("data-uuid")) {
			event.preventDefault();
			handleDownload(downloadBtn);
			return;
		}

			// PDF 预览工具栏交互
			var pdfPrev = target.closest(".argon-pdf-prev");
			if (pdfPrev) {
				if (currentPdfDoc && currentPdfPage > 1) {
					currentPdfPage--;
					renderPdfPage(modalParts(getModal()), currentPdfPage);
				}
				return;
			}
			var pdfNext = target.closest(".argon-pdf-next");
			if (pdfNext) {
				if (currentPdfDoc && currentPdfPage < currentPdfTotal) {
					currentPdfPage++;
					renderPdfPage(modalParts(getModal()), currentPdfPage);
				}
				return;
			}
			var pdfZoomIn = target.closest(".argon-pdf-zoom-in");
			if (pdfZoomIn) {
				if (currentPdfDoc && currentPdfScale < 3.0) {
					currentPdfScale = Math.min(3.0, currentPdfScale + 0.25);
					renderPdfPage(modalParts(getModal()), currentPdfPage);
				}
				return;
			}
			var pdfZoomOut = target.closest(".argon-pdf-zoom-out");
			if (pdfZoomOut) {
				if (currentPdfDoc && currentPdfScale > 0.5) {
					currentPdfScale = Math.max(0.5, currentPdfScale - 0.25);
					renderPdfPage(modalParts(getModal()), currentPdfPage);
				}
				return;
			}
			var pdfFit = target.closest(".argon-pdf-fit");
			if (pdfFit) {
				if (currentPdfDoc) {
					currentPdfScale = 1.25;
					renderPdfPage(modalParts(getModal()), currentPdfPage);
				}
				return;
			}

			// 管理员刷新快照
			var refreshBtn = target.closest(".argon-attachment-refresh-snapshot");
			if (refreshBtn) {
				event.preventDefault();
				var modal = getModal();
				var downloadBtnInModal = modal ? modal.querySelector(".argon-attachment-preview-download") : null;
				var currentUuid = downloadBtnInModal ? downloadBtnInModal.getAttribute("data-uuid") : "";
				if (!currentUuid) return;

				var icon = refreshBtn.querySelector("i");
				if (icon) icon.className = "fa fa-refresh fa-spin";
				refreshBtn.disabled = true;

				post("argon_attachment_preview_open", { uuid: currentUuid, force_refresh: "1" })
					.then(function (resp) {
						if (!resp || !resp.success || !resp.data) {
							throw new Error((resp && resp.data && resp.data.message) || "refresh failed");
						}
						var itemEl = document.querySelector('.argon-attachment-item[data-uuid="' + currentUuid + '"]');
						if (itemEl) {
							openPreview(itemEl);
						}
					})
					.catch(function (err) {
						alert("刷新快照失败：" + (err && err.message ? err.message : "未知错误"));
					})
					.finally(function () {
						if (icon) icon.className = "fa fa-refresh";
						refreshBtn.disabled = false;
					});
				return;
			}

			// ZIP 文件管理器交互：点击文件夹钻入
			var zipDirItem = target.closest(".argon-zip-item.is-dir");
			if (zipDirItem) {
				var dirName = zipDirItem.getAttribute("data-dir");
				if (dirName) {
					currentZipPath.push(dirName);
					renderZipExplorer(document.querySelector(".argon-attachment-preview-zip"));
				}
				return;
			}

			// ZIP 文件管理器交互：点击返回上一级
			var zipUpBtn = target.closest(".argon-zip-up");
			if (zipUpBtn && !zipUpBtn.disabled) {
				if (currentZipPath.length > 0) {
					currentZipPath.pop();
					renderZipExplorer(document.querySelector(".argon-attachment-preview-zip"));
				}
				return;
			}

			// ZIP 文件管理器交互：点击面包屑导航
			var zipCrumb = target.closest(".argon-zip-crumb");
			if (zipCrumb) {
				var idx = parseInt(zipCrumb.getAttribute("data-index"), 10);
				if (idx === -1) {
					currentZipPath = [];
					renderZipExplorer(document.querySelector(".argon-attachment-preview-zip"));
				} else if (!isNaN(idx) && idx < currentZipPath.length - 1) {
					currentZipPath = currentZipPath.slice(0, idx + 1);
					renderZipExplorer(document.querySelector(".argon-attachment-preview-zip"));
				}
				return;
			}

			// 预览入口：整张卡片可点击预览
		var item = target.closest(".argon-attachment-item.argon-attachment-previewable");
		if (item) {
			openPreview(item);
		}
	});

	// 数学验证码回车提交
	document.addEventListener("keydown", function(event) {
		if (event.key === "Enter") {
			var modal = getCaptchaModal();
			if (modal && !modal.hidden && modal.classList.contains("is-open")) {
				var submitBtn = modal.querySelector(".argon-att-captcha-submit");
				if (submitBtn) {
					event.preventDefault();
					submitBtn.click();
				}
			}
		}
	});

		// Esc 关闭 + PJAX 导航前关闭 + 动态主题切换同步
		if (!window.__argonAttachmentPreviewGlobalBound) {
			window.__argonAttachmentPreviewGlobalBound = true;
			document.addEventListener("keydown", function (event) {
				if (event.key === "Escape") {
					closePreview();
					closeCaptchaModal();
				}
			});
			document.addEventListener("pjax:send", function () {
				closePreview();
				closeCaptchaModal();
			});
			document.addEventListener("argon:theme-switched", function () {
				var modal = getModal();
				if (!modal) return;
				var parts = modalParts(modal);
				if (parts.frame && !parts.frame.hidden && parts.frame.contentWindow) {
					try {
						var isDark = document.documentElement.classList.contains("darkmode");
						var isAmoled = document.documentElement.classList.contains("amoled-dark");
						if (parts.frame.contentWindow.document) {
							var fDoc = parts.frame.contentWindow.document;
							if (isDark) {
								fDoc.documentElement.classList.add("darkmode");
								if (fDoc.body) fDoc.body.classList.add("darkmode");
								if (isAmoled) {
									fDoc.documentElement.classList.add("amoled-dark");
									if (fDoc.body) fDoc.body.classList.add("amoled-dark");
								} else {
									fDoc.documentElement.classList.remove("amoled-dark");
									if (fDoc.body) fDoc.body.classList.remove("amoled-dark");
								}
							} else {
								fDoc.documentElement.classList.remove("darkmode", "amoled-dark");
								if (fDoc.body) fDoc.body.classList.remove("darkmode", "amoled-dark");
							}
						}
						if (parts.frame.contentWindow.postMessage) {
							parts.frame.contentWindow.postMessage({ type: "argon_theme_change", isDark: isDark, isAmoled: isAmoled }, "*");
						}
					} catch (err) {}
				}
			});
		}
	})();
