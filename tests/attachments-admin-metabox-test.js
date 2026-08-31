/**
 * 附件 meta box（文章编辑页）上传链路回归测试。
 *
 * 背景：服务端校验失败统一走 wp_send_json_error(..., 400/403)（HTTP 错误状态），
 * 旧版 attachments-admin.js 的 error 回调不读 xhr.responseJSON，用户只能看到笼统的
 * 「上传失败」，无法得知真实原因（扩展名 / 大小 / nonce 失效）；nonce 失效时 admin-ajax
 * 返回 "-1"，同样需要可操作的提示。本测试用 Playwright + 真实 jQuery 完整跑通
 * 「点击上传按钮 → 文件选择框 → change → FormData AJAX → 结果反馈」链路。
 *
 * 运行：node tests/attachments-admin-metabox-test.js
 */

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '..');
const jqueryPath = path.join(root, 'assets', 'vendor', 'jquery', 'jquery.min.js');
const adminScriptPath = path.join(root, 'assets', 'js', 'attachments-admin.js');

function pageMarkup(config) {
	return `<!doctype html>
	<html><head><meta charset="utf-8"></head><body>
		<div id="argon-attachments-manager" data-post-id="123">
			<div id="argon-attachments-admin-list" class="argon-attachments-admin-list">
				<p class="argon-attachments-admin-empty">还没有附件，点击下方按钮上传。</p>
			</div>
			<div class="argon-attachments-admin-upload">
				<input type="file" id="argon-attachment-file-input" style="display:none;"/>
				<button type="button" class="button button-primary" id="argon-attachment-upload-btn">上传附件</button>
				<div class="argon-attachments-admin-progress" style="display:none;"><div class="argon-attachments-admin-progress-bar"></div></div>
			</div>
		</div>
		<script>
			window.__notices = [];
			window.__alerts = [];
			window.wp = { data: { dispatch: function () {
				return { createNotice: function (type, message) { window.__notices.push({ type: type, message: message }); } };
			} } };
			window.alert = function (message) { window.__alerts.push(String(message)); };
			window.argonAttachmentsAdmin = ${JSON.stringify(config)};
		</script>
	</body></html>`;
}

function baseConfig(overrides) {
	return Object.assign({
		ajaxurl: 'https://example.test/wp-admin/admin-ajax.php',
		nonce: 'test-nonce',
		maxSize: 50 * 1024 * 1024,
		allowedExt: ['zip', 'pdf'],
		i18n: {
			uploading: '正在上传…',
			uploadFailed: '上传失败',
			deleteConfirm: '确定删除？',
			deleteFailed: '删除失败',
			saveFailed: '保存失败',
			badExt: '不允许上传该格式的文件',
			tooBig: '文件超过大小上限（%s）',
			sessionExpired: '登录状态已过期，请刷新页面后重试',
			copied: '已复制',
			downloadTimes: '次下载',
		},
	}, overrides || {});
}

async function createPage(browser, config) {
	const page = await browser.newPage();
	page.on('dialog', (dialog) => dialog.accept());
	await page.setContent(pageMarkup(config));
	await page.addScriptTag({ path: jqueryPath });
	await page.addScriptTag({ path: adminScriptPath });
	// 在脚本加载后替换 $.ajax：调用时才查找，故可安全捕获并程序化响应
	await page.evaluate(() => {
		window.__ajaxCalls = [];
		window.__ajaxResponder = null;
		window.jQuery.ajax = (opts) => {
			const call = { url: opts.url, type: opts.type, entries: {} };
			if (opts.data instanceof FormData) {
				call.isFormData = true;
				for (const [key, value] of opts.data.entries()) {
					call.entries[key] = value instanceof File ? { fileName: value.name, size: value.size } : value;
				}
			} else if (opts.data && typeof opts.data === 'object') {
				call.entries = Object.assign({}, opts.data);
			}
			window.__ajaxCalls.push(call);
			setTimeout(() => {
				const responder = window.__ajaxResponder;
				if (!responder) { return; }
				const result = responder(call);
				if (result.ok) {
					opts.success && opts.success(result.body, 'success', { status: 200, responseJSON: result.body });
				} else {
					opts.error && opts.error({
						status: result.status,
						responseJSON: typeof result.body === 'object' ? result.body : undefined,
						responseText: typeof result.body === 'string' ? result.body : JSON.stringify(result.body),
					});
				}
				opts.complete && opts.complete();
			}, 0);
			return { abort() {} };
		};
	});
	return page;
}

async function uploadFile(page, name, size) {
	const [chooser] = await Promise.all([
		page.waitForEvent('filechooser'),
		page.click('#argon-attachment-upload-btn'),
	]);
	await chooser.setFiles({ name, mimeType: 'application/zip', buffer: Buffer.alloc(size, 1) });
}

const getNotices = (page) => page.evaluate(() => window.__notices);
const getCalls = (page) => page.evaluate(() => window.__ajaxCalls);
const setResponder = (page, responder) => page.evaluate((fn) => {
	window.__ajaxResponder = eval('(' + fn + ')');
}, responder.toString());

async function run() {
	const browserCandidates = [
		process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE,
		'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
		'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
	].filter(Boolean);
	const executablePath = browserCandidates.find((candidate) => fs.existsSync(candidate));
	const browser = await chromium.launch({ headless: true, executablePath });

	try {
		// 1. 完整链路：点击按钮弹文件框 → 选择文件 → FormData 携带 action/nonce/post_id → 成功后追加行
		{
			const page = await createPage(browser, baseConfig());
			await setResponder(page, `function () {
				return { ok: true, body: { success: true, data: {
					uuid: 'u-1', name: '报告.zip', size: '1.0 MB', downloads: 0,
					download_url: 'https://example.test/?download=u-1'
				} } };
			}`);
			await uploadFile(page, '报告.zip', 1024);
			await page.waitForFunction(() => window.__ajaxCalls.length === 1);

			const calls = await getCalls(page);
			assert.equal(calls[0].isFormData, true, '上传应使用 FormData');
			assert.equal(calls[0].entries.action, 'argon_attachment_upload');
			assert.equal(calls[0].entries.nonce, 'test-nonce');
			assert.equal(String(calls[0].entries.post_id), '123');
			assert.equal(calls[0].entries.file.fileName, '报告.zip');

			await page.waitForSelector('.argon-att-admin-row');
			const rowName = await page.$eval('.argon-att-admin-row .argon-att-admin-name', (el) => el.value);
			assert.equal(rowName, '报告.zip', '上传成功后应追加附件行');
			const emptyVisible = await page.$eval('.argon-attachments-admin-empty', (el) => el.offsetParent !== null || getComputedStyle(el).display !== 'none');
			assert.equal(emptyVisible, false, '有附件后空提示应隐藏');
			const notices = await getNotices(page);
			assert.ok(notices.some((n) => n.type === 'success'), '成功应有成功提示');
			await page.close();
		}

		// 2. 服务端校验失败（HTTP 400 + JSON message）：必须透传服务端具体原因，而非笼统的「上传失败」
		{
			const page = await createPage(browser, baseConfig());
			await setResponder(page, `function () {
				return { ok: false, status: 400, body: { success: false, data: { message: '文件超过大小上限 50 MB' } } };
			}`);
			await uploadFile(page, '大文件.zip', 1024);
			await page.waitForFunction(() => window.__notices.length > 0);

			const notices = await getNotices(page);
			assert.ok(
				notices.some((n) => n.type === 'error' && n.message.includes('文件超过大小上限 50 MB')),
				'HTTP 400 时应展示服务端返回的具体失败原因，实际：' + JSON.stringify(notices)
			);
			await page.close();
		}

		// 3. nonce 失效（HTTP 403 + 响应体 "-1"）：给出可操作的刷新提示
		{
			const page = await createPage(browser, baseConfig());
			await setResponder(page, `function () {
				return { ok: false, status: 403, body: '-1' };
			}`);
			await uploadFile(page, '报告.zip', 1024);
			await page.waitForFunction(() => window.__notices.length > 0);

			const notices = await getNotices(page);
			assert.ok(
				notices.some((n) => n.type === 'error' && n.message.includes('刷新页面')),
				'nonce 失效时应提示刷新页面，实际：' + JSON.stringify(notices)
			);
			await page.close();
		}

		// 4. 删除链路：confirm 通过 → AJAX 携带 action/uuid → 成功后移除行
		{
			const page = await createPage(browser, baseConfig());
			await page.evaluate(() => {
				window.jQuery('#argon-attachments-admin-list').append(
					window.jQuery('<div class="argon-att-admin-row" data-uuid="u-del"></div>')
						.append('<button type="button" class="button argon-att-admin-delete">删除</button>')
				);
			});
			await setResponder(page, `function () {
				return { ok: true, body: { success: true, data: { uuid: 'u-del' } } };
			}`);
			await page.click('.argon-att-admin-delete');
			await page.waitForFunction(() => window.__ajaxCalls.length === 1);

			const calls = await getCalls(page);
			assert.equal(calls[0].entries.action, 'argon_attachment_delete');
			assert.equal(calls[0].entries.uuid, 'u-del');
			assert.equal(calls[0].entries.post_id, 123);
			await page.waitForFunction(() => !document.querySelector('.argon-att-admin-row'));
			await page.close();
		}

		// 5. 客户端大小预检：config.maxSize 为「主题与 PHP 的生效上限」，超限时不发请求并给出含上限的提示
		{
			const page = await createPage(browser, baseConfig({ maxSize: 2 * 1024 * 1024 }));
			await uploadFile(page, '报告.zip', 3 * 1024 * 1024);
			await page.waitForFunction(() => window.__notices.length > 0);

			const calls = await getCalls(page);
			assert.equal(calls.length, 0, '超限文件不应发起上传请求');
			const notices = await getNotices(page);
			assert.ok(
				notices.some((n) => n.type === 'error' && n.message.includes('2')),
				'超限提示应包含生效上限，实际：' + JSON.stringify(notices)
			);
			await page.close();
		}

		console.log('OK：附件 meta box 上传链路回归测试全部通过。');
	} finally {
		await browser.close();
	}
}

run().catch((error) => {
	console.error('测试失败：' + (error && error.stack ? error.stack : error));
	process.exit(1);
});
