const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '..');
const scriptPath = path.join(root, 'assets', 'js', 'bangumi.js');
const stylePath = path.join(root, 'assets', 'css', 'bangumi.css');

function pageMarkup(config) {
	return `<!doctype html>
	<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>
		:root {
			--themecolor: #d14d72;
			--themecolor-rgbstr: 209, 77, 114;
			--color-foreground: #fff;
			--color-text-deeper: #20242a;
			--color-border-on-foreground: #e6e8ec;
			--argon-special-page-text-main: #20242a;
			--argon-special-page-text-muted: #626873;
			--argon-special-page-text-soft: #7a818d;
			--argon-special-page-text-faint: #abb0b8;
			--card-radius: 6px;
		}
		* { box-sizing: border-box; }
		body { margin: 0; padding: 24px; background: #edf0f3; color: #20242a; font-family: Arial, sans-serif; }
		body > button, body > input, #bangumi-live-status, #bangumi-load-more { display: none; }
		.fa-times::before { content: 'X'; }
		.fa-external-link::before { content: '>'; }
	</style></head><body>
		<button type="button" data-bangumi-status="2">Watching</button>
		<button type="button" data-bangumi-status="3">Watched</button>
		<button type="button" data-bangumi-type="all">All</button>
		<input id="bangumi-search-input" type="search">
		<div id="bangumi-live-status"></div>
		<div id="bangumi-grid" class="bangumi-grid"></div>
		<div id="bangumi-empty" hidden></div>
		<button type="button" id="bangumi-load-more">More</button>
		<div id="bangumi-error" hidden></div>
		<script type="application/json" id="argon-bangumi-config">${JSON.stringify(config).replace(/</g, '\\u003c')}</script>
	</body></html>`;
}

async function firstRowCount(page) {
	const tops = await page.locator('.bangumi-card').evaluateAll((nodes) => nodes.map((node) => node.getBoundingClientRect().top));
	return tops.filter((top) => Math.abs(top - tops[0]) < 1).length;
}

async function run() {
	const browserCandidates = [
		process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE,
		'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
		'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
	].filter(Boolean);
	const executablePath = browserCandidates.find((candidate) => fs.existsSync(candidate));
	const browser = await chromium.launch({ headless: true, executablePath });
	const screenshotDir = process.env.BANGUMI_SCREENSHOT_DIR;
	if (screenshotDir) fs.mkdirSync(screenshotDir, { recursive: true });
	const context = await browser.newContext({
		viewport: { width: 390, height: 844 },
		hasTouch: true,
		isMobile: true,
	});
	const page = await context.newPage();
	let desktopContext = null;

	try {
		const items = Array.from({ length: 12 }, (_, index) => index + 1).map((id) => ({
			season_id: id,
			season_type: 1,
			season_type_name: 'Anime',
			title: `Bangumi ${id}`,
			cover: `https://example.test/cover-${id}.jpg`,
			url: `https://example.test/detail-${id}`,
			follow_status: 2,
			total_count: 12,
			progress: '3',
			rating: 8.6,
			summary: 'A long enough summary for the expanded preview card.',
			styles: ['Drama', 'Music'],
		}));

		await page.setContent(pageMarkup({
			enabled: true,
			ajaxUrl: 'https://example.test/admin-ajax.php',
			initialStatus: 2,
			initialType: 'all',
			initialItems: items,
			initialPage: 1,
			initialHasMore: false,
			initialNeedsRefresh: false,
			stats: { total: 12, watching: 12, want: 0, watched: 0 },
			statsReady: true,
		}));
		await page.addStyleTag({ path: stylePath });
		await page.evaluate(() => {
			document.addEventListener('click', (event) => {
				if (event.target.closest('.bangumi-card')) event.preventDefault();
			}, true);
		});
		await page.addScriptTag({ path: scriptPath });

		const cards = page.locator('.bangumi-card');
		await assert.doesNotReject(() => cards.nth(1).waitFor());
		const before = await cards.evaluateAll((nodes) => nodes.map((node) => {
			const rect = node.getBoundingClientRect();
			return { width: rect.width, height: rect.height };
		}));
		assert.ok(Math.abs(before[0].width - before[1].width) < 0.1, 'inactive cards should have equal widths');
		assert.ok(Math.abs(before[0].height - before[1].height) < 0.1, 'inactive cards should have equal heights');
		const mobileColumns = await firstRowCount(page);
		assert.equal(mobileColumns, 2, '390px touch view should use two card columns');

		await cards.first().dispatchEvent('click');
		await page.locator('#bangumi-card-preview.is-visible').waitFor({ timeout: 3000 });
		const preview = page.locator('#bangumi-card-preview');
		const geometry = await page.evaluate(() => {
			const cardRect = document.querySelector('.bangumi-card').getBoundingClientRect();
			const previewRect = document.querySelector('#bangumi-card-preview').getBoundingClientRect();
			return {
				card: { width: cardRect.width, height: cardRect.height },
				preview: { width: previewRect.width, height: previewRect.height },
			};
		});
		assert.ok(geometry.preview.width > geometry.card.width, 'preview should expand horizontally');
		assert.ok(geometry.preview.height > geometry.card.height, 'preview should expand vertically');
		const after = await cards.evaluateAll((nodes) => nodes.map((node) => {
			const rect = node.getBoundingClientRect();
			return { width: rect.width, height: rect.height };
		}));
		after.forEach((size, index) => {
			assert.ok(Math.abs(size.width - before[index].width) < 0.1, 'opening the preview must not resize inactive card widths');
			assert.ok(Math.abs(size.height - before[index].height) < 0.1, 'opening the preview must not resize inactive card heights');
		});
		assert.equal(await preview.getAttribute('aria-hidden'), 'false');
		assert.equal(await preview.locator('.bangumi-card-preview-open').getAttribute('href'), 'https://example.test/detail-1');
		if (screenshotDir) {
			await page.waitForTimeout(220);
			await page.screenshot({ path: path.join(screenshotDir, 'bangumi-mobile-preview.png'), fullPage: false });
		}
		await preview.locator('.bangumi-card-preview-close').click();
		assert.equal(await preview.isHidden(), true, 'close button should hide the preview');

		await page.evaluate(() => window.ArgonBangumi.destroy());
		await page.setContent(pageMarkup({
			enabled: true,
			ajaxUrl: 'https://example.test/admin-ajax.php',
			initialStatus: 2,
			initialType: 'all',
			initialItems: [],
			initialPage: 1,
			initialHasMore: true,
			initialNeedsRefresh: true,
			stats: { total: 0, watching: 0, want: 0, watched: 0 },
		}));
		await page.evaluate(() => {
			window.__bangumiRequestAborted = false;
			window.fetch = function (_url, options) {
				return new Promise((_resolve, reject) => {
					if (!options.signal) return;
					options.signal.addEventListener('abort', () => {
						window.__bangumiRequestAborted = true;
						reject(new DOMException('Aborted', 'AbortError'));
					});
				});
			};
			window.ArgonBangumi.init();
		});
		await page.waitForFunction(() => document.getElementById('bangumi-load-more').classList.contains('is-loading'));
		await page.evaluate(() => window.ArgonBangumi.destroy());
		await page.waitForFunction(() => window.__bangumiRequestAborted === true);

		await page.setContent(pageMarkup({
			enabled: true,
			ajaxUrl: 'https://example.test/admin-ajax.php',
			initialStatus: 2,
			initialType: 'all',
			initialItems: [],
			initialPage: 1,
			initialHasMore: true,
			initialNeedsRefresh: true,
			stats: { total: 0, watching: 0, want: 0, watched: 0 },
			statsReady: true,
		}));
		await page.evaluate(() => {
			window.fetch = function () {
				return new Promise((resolve) => { window.__resolveStaleBangumiRequest = resolve; });
			};
			window.ArgonBangumi.init();
		});
		await page.waitForFunction(() => typeof window.__resolveStaleBangumiRequest === 'function');
		await page.evaluate(() => window.ArgonBangumi.destroy());
		await page.setContent(pageMarkup({
			enabled: true,
			ajaxUrl: 'https://example.test/admin-ajax.php',
			initialStatus: 2,
			initialType: 'all',
			initialItems: [{
				season_id: 99,
				season_type: 1,
				title: 'Fresh page item',
				follow_status: 2,
			}],
			initialPage: 1,
			initialHasMore: false,
			initialNeedsRefresh: false,
			stats: { total: 1, watching: 1, want: 0, watched: 0 },
			statsReady: true,
		}));
		await page.evaluate(() => {
			window.ArgonBangumi.init();
			window.__resolveStaleBangumiRequest({
				json: () => Promise.resolve({
					success: true,
					data: {
						items: [{ season_id: 1, season_type: 1, title: 'Stale item', follow_status: 2 }],
						page: 1,
						has_more: false,
						stats: { total: 1, watching: 1, want: 0, watched: 0 },
						stats_ready: true,
					},
				}),
			});
		});
		await page.waitForTimeout(50);
		assert.equal(await page.locator('.bangumi-card').first().getAttribute('data-season-id'), '99', 'stale responses must not overwrite a new page lifecycle');
		await page.evaluate(() => window.ArgonBangumi.destroy());

		desktopContext = await browser.newContext({ viewport: { width: 1280, height: 800 } });
		const desktopPage = await desktopContext.newPage();
		await desktopPage.setContent(pageMarkup({
			enabled: true,
			ajaxUrl: 'https://example.test/admin-ajax.php',
			initialStatus: 2,
			initialType: 'all',
			initialItems: items,
			initialPage: 1,
			initialHasMore: false,
			initialNeedsRefresh: false,
			stats: { total: 12, watching: 12, want: 0, watched: 0 },
			statsReady: true,
		}));
		await desktopPage.addStyleTag({ path: stylePath });
		await desktopPage.addScriptTag({ path: scriptPath });
		await desktopPage.locator('.bangumi-card').nth(11).waitFor();
		const desktopColumns = await firstRowCount(desktopPage);
		assert.ok(desktopColumns > mobileColumns, 'wider viewports should fit more cards per row');
		assert.ok(desktopColumns >= 6, '1280px view should use at least six card columns');
		await desktopPage.locator('.bangumi-card').first().hover();
		await desktopPage.locator('#bangumi-card-preview.is-visible').waitFor({ timeout: 3000 });
		if (screenshotDir) {
			await desktopPage.waitForTimeout(220);
			await desktopPage.screenshot({ path: path.join(screenshotDir, 'bangumi-desktop-preview.png'), fullPage: false });
		}
		await desktopPage.evaluate(() => window.ArgonBangumi.destroy());
	} finally {
		if (desktopContext) await desktopContext.close();
		await context.close();
		await browser.close();
	}
}

run()
	.then(() => console.log('Bangumi preview interaction test passed.'))
	.catch((error) => {
		console.error(error);
		process.exit(1);
	});
