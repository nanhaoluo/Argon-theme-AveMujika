/** 运行：node tests/ai-tags-admin-browser-test.js。真实脚本 + jQuery，AJAX fixture。 */
const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');

async function fixture(browser) {
	const page = await browser.newPage({ viewport: { width: 1100, height: 800 } });
	await page.setContent(`<!doctype html><html lang="zh-CN"><meta charset="utf-8"><body>
		<div id="argon-ai-tags">
			<select id="argon-ai-tags-frequency"><option value="3">低频标签</option></select>
			<button id="argon-ai-tags-preview">分析</button>
			<p id="argon-ai-tags-status" role="status"></p>
			<div id="argon-ai-tags-results" hidden>
				<input type="checkbox" id="argon-ai-tags-select-all">
				<table><tbody id="argon-ai-tags-rows"></tbody></table>
				<button id="argon-ai-tags-apply" disabled>合并</button>
			</div>
		</div></body></html>`);
	await page.addScriptTag({ path: path.join(root, 'assets/vendor/jquery/jquery.min.js') });
	await page.evaluate(() => {
		window.argonAiTags = {
			url: '/wp-admin/admin-ajax.php', nonce: 'test-nonce',
			i18n: { analyzing: '分析：', ready: '分析完成', empty: '没有建议', confirm: '确认合并？', merging: '迁移：',
				done: '合并完成', failed: '请求失败', expired: '校验已过期', partial: '可再次点击继续', select: '选择' }
		};
		window.calls = [];
		window.responses = [];
		window.confirmed = true;
		window.confirm = () => window.confirmed;
		window.jQuery.ajax = (options) => {
			window.calls.push(options.data);
			const reply = window.responses.shift();
			setTimeout(() => {
				if (!reply) throw new Error('Unexpected AJAX call');
				if (reply.error) options.error(reply.error);
				else options.success(reply);
			}, 10);
		};
	});
	await page.addScriptTag({ path: path.join(root, 'assets/js/ai-tags-admin.js') });
	return page;
}
function preview(done, merges = []) {
	return { success: true, data: { token: 'a'.repeat(32), done, processed: done ? 27 : 25, total: 27, merges } };
}
function suggestion(id, name) {
	return { source_id: id, source: { name, count: 1 }, target: { name: 'WordPress', count: 8 }, reason: '<img src=x onerror="window.injected=1">同义词' };
}
async function responses(page, replies) { await page.evaluate((replies) => { window.responses = replies; }, replies); }
async function waitStatus(page, message) { await page.waitForFunction((message) => document.querySelector('#argon-ai-tags-status').textContent.includes(message), message); }

async function main() {
	const browser = await chromium.launch({ headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || undefined });
	try {
		const page = await fixture(browser);
		await responses(page, [preview(false), preview(true, [suggestion(1, '<script>bad()</script>'), suggestion(2, 'wp教程')])]);
		await page.click('#argon-ai-tags-preview');
		await waitStatus(page, '分析完成');
		assert.equal(await page.locator('#argon-ai-tags-rows tr').count(), 2);
		assert.equal(await page.locator('#argon-ai-tags-rows input:checked').count(), 0, '预览不应自动勾选执行');
		assert.equal(await page.locator('#argon-ai-tags-apply').isDisabled(), true);
		assert.equal(await page.locator('#argon-ai-tags-rows script, #argon-ai-tags-rows img').count(), 0, '标签和理由须以纯文本展示');
		let calls = await page.evaluate(() => window.calls);
		assert.equal(calls.length, 2);
		assert.equal(calls[0].max_count, '3');
		assert.equal(calls[1].token, 'a'.repeat(32));
		assert.ok(calls.every((call) => call.action === 'argon_ai_tags_preview' && call.nonce === 'test-nonce'));

		await page.check('#argon-ai-tags-select-all');
		await page.evaluate(() => { window.confirmed = false; });
		await page.click('#argon-ai-tags-apply');
		assert.equal(await page.evaluate(() => window.calls.length), 2, '取消确认不能执行合并');
		await page.evaluate(() => { window.confirmed = true; });
		await responses(page, [
			{ success: true, data: { done: false, remaining: 5 } },
			{ success: true, data: { done: true, remaining: 0 } },
			{ error: { status: 409, responseJSON: { data: { message: '原标签已保留' } } } }
		]);
		await page.click('#argon-ai-tags-apply');
		await waitStatus(page, '可再次点击继续');
		assert.equal(await page.locator('#argon-ai-tags-rows tr').count(), 1, '失败时仅移除已完成项');
		assert.equal(await page.locator('#argon-ai-tags-apply').isDisabled(), false, '失败后可继续剩余项');
		assert.ok((await page.locator('#argon-ai-tags-status').textContent()).includes('原标签已保留'), '应展示服务端错误');
		calls = await page.evaluate(() => window.calls);
		assert.deepEqual(calls.slice(2).map((call) => String(call.source_id)), ['1', '1', '2']);
		assert.ok(calls.slice(2).every((call) => !('target_id' in call)), '客户端只能提交源 ID，目标由服务端计划决定');
		await responses(page, [{ success: true, data: { done: true } }]);
		await page.click('#argon-ai-tags-apply');
		await waitStatus(page, '合并完成');
		assert.equal(await page.locator('#argon-ai-tags-rows tr').count(), 0);
		assert.equal(await page.locator('#argon-ai-tags-apply').isDisabled(), true);

		await responses(page, [{ error: { status: 403, responseText: '-1' } }]);
		await page.click('#argon-ai-tags-preview');
		await waitStatus(page, '校验已过期');
		assert.equal(await page.locator('#argon-ai-tags-preview').isDisabled(), false);
		assert.equal(await page.locator('#argon-ai-tags-results').isVisible(), false, '失败分析不得继续使用旧预览');
		await responses(page, [preview(true)]);
		await page.click('#argon-ai-tags-preview');
		await waitStatus(page, '没有建议');
		await page.close();
		console.log('[PASS] AI tag cleanup admin browser fixture');
	} finally {
		await browser.close();
	}
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
