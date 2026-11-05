'use strict';
// Real jQuery + bundled PJAX transport fixture; not full WordPress integration QA.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'argontheme.js'), 'utf8');
const start = source.indexOf('var ArgonPjaxPrefetch = (function () {');
const end = source.indexOf('\n})();', start) + 6;
const pjax = fs.readFileSync(path.join(root, 'assets/argon_js_merged.js'), 'utf8').split('\n').find(line => line.startsWith('!function(t){function e(e,r,a)'));
assert.ok(pjax, 'bundled PJAX plugin missing');
(async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage();
        const counts = {}, errors = [];
        let releaseArticle;
        const articleGate = new Promise(resolve => { releaseArticle = resolve; });
        page.on('pageerror', error => errors.push(error.message));
        await page.route('https://fixture.test/**', async route => {
            const pathname = new URL(route.request().url()).pathname;
            counts[pathname] = (counts[pathname] || 0) + 1;
            if (pathname === '/article' && counts[pathname] === 1) await articleGate;
            await route.fulfill({ contentType: 'text/html', body: '<!doctype html><title>Fixture</title><div id="primary">' + pathname + ' response ' + counts[pathname] + '</div>' });
        });
        await page.goto('https://fixture.test/current');
        await page.addScriptTag({ path: path.join(root, 'assets/vendor/jquery/jquery.min.js') });
        await page.addScriptTag({ content: 'var pjaxScrollTop=0; var argonConfig={pjax_animation_durtion:0};' + pjax });
        await page.addScriptTag({ content: 'var pjaxContainers=["#primary"]; var pjaxLoading=true;' + source.slice(start, end) });
        const requestStarted = page.waitForRequest(request => new URL(request.url()).pathname === '/article');
        await page.evaluate(() => { ArgonPjaxPrefetch.prefetch('/article'); });
        await requestStarted;
        await page.evaluate(() => {
            window.navigationDone = new Promise(resolve => jQuery(document).one('pjax:complete', resolve));
            jQuery.pjax({ url: '/article', container: ['#primary'], fragment: ['#primary'], timeout: 10000 });
        });
        releaseArticle();
        await page.evaluate(() => window.navigationDone.then(() => true));
        assert.strictEqual(counts['/article'], 1, 'hover + actual PJAX uses one network GET');
        assert.strictEqual(await page.locator('#primary').textContent(), '/article response 1');
        await page.evaluate(() => new Promise(resolve => {
            jQuery(document).one('pjax:complete', () => resolve(true));
            jQuery.pjax.reload({ container: ['#primary'], fragment: ['#primary'] });
        }));
        assert.strictEqual(counts['/article'], 2, 'PJAX reload bypasses speculative cache');
        assert.strictEqual(await page.locator('#primary').textContent(), '/article response 2');
        await page.evaluate(() => new Promise((resolve, reject) => {
            ArgonPjaxPrefetch.prefetch('/other').done(() => resolve(true)).fail(reject);
        }));
        await page.evaluate(() => new Promise((resolve, reject) => {
            jQuery.ajax({ url: '/other', dataType: 'html' }).done(() => resolve(true)).fail(reject);
        }));
        assert.strictEqual(counts['/other'], 2, 'ordinary AJAX does not consume PJAX cache');
        assert.deepStrictEqual(errors, []);
        console.log('Real browser PJAX fixture passed: hover/click 1 GET, reload fresh GET, ordinary AJAX bypass.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
