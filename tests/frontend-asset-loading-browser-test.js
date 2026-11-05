'use strict';
// Browser fixtures for delayed optional CSS and real Mermaid rendering after PJAX.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const loader = fs.readFileSync(path.join(root, 'assets/js/argon-mermaid-loader.js'), 'utf8');
const theme = fs.readFileSync(path.join(root, 'argontheme.js'), 'utf8');
const animations = theme.slice(theme.indexOf('/* ========== Modern UI Enhancements'), theme.indexOf('/* ========== End of Modern UI Enhancements'));
const pageLoaderStart = theme.indexOf('var PageLoader = (function () {');
const pageLoader = theme.slice(pageLoaderStart, theme.indexOf('\n})();', pageLoaderStart) + 6);
const pageLoaderBootstrap = theme.slice(pageLoaderStart, theme.indexOf('var pageTransitionTimer', pageLoaderStart));
const library = process.env.ARGON_MERMAID_LIBRARY || path.join(root, 'assets/vendor/mermaid/mermaid.min.js');
const originalStyle = '<link rel="stylesheet" href="https://fixture.test/optional.css" media="all">';
const php = `function add_filter(){} function add_action(){} function is_admin(){return false;}
    function is_home(){return true;} function is_archive(){return false;} function is_search(){return false;}
    require ${JSON.stringify(path.join(root, 'inc/frontend-assets.php'))};
    echo argon_nonblocking_listing_stylesheet(${JSON.stringify(originalStyle)}, 'Katex');`;
const optionalStyle = execFileSync(process.env.ARGON_PHP || 'php', ['-n', '-r', php], { encoding: 'utf8' });

(async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        for (const deferred of [false, true]) {
            const page = await browser.newPage();
            let release;
            const gate = new Promise(resolve => { release = resolve; });
            await page.route('https://fixture.test/**', async route => {
                if (new URL(route.request().url()).pathname === '/optional.css') {
                    await gate;
                    await route.fulfill({ contentType: 'text/css', body: 'h1 { color: rgb(11,22,33); }' });
                } else {
                    await route.fulfill({ contentType: 'text/html', body: '<!doctype html><head>' +
                        (deferred ? optionalStyle : originalStyle) +
                        '<script>window.fixtureParserContinued=true;</script></head><body><h1>Visible content</h1></body>' });
                }
            });
            try {
                const started = page.waitForRequest('https://fixture.test/optional.css');
                const navigation = page.goto('https://fixture.test/', { waitUntil: 'domcontentloaded' });
                await started;
                if (deferred) {
                    await navigation;
                    await page.waitForFunction(() => performance.getEntriesByType('paint').some(e => e.name === 'first-contentful-paint'));
                    assert.strictEqual(await page.evaluate(() => window.fixtureParserContinued), true, 'slow optional CSS must not stop parsing or painting');
                    await page.addScriptTag({ content: pageLoader + ';PageLoader.show(document);PageLoader.hide();' });
                    await page.waitForFunction(() => !document.getElementById('page-loader').classList.contains('is-visible'));
                } else {
                    assert.strictEqual(await page.evaluate(() => !!window.fixtureParserContinued), false, 'baseline must reproduce stylesheet blocking');
                }
                release();
                await navigation;
                await page.waitForFunction(() => getComputedStyle(document.querySelector('h1')).color === 'rgb(11, 22, 33)');
            } finally { release(); await page.close(); }
        }

        // Optional footer scripts may still be pending when the theme DOM can paint.
        {
            const page = await browser.newPage();
            let release;
            const gate = new Promise(resolve => { release = resolve; });
            await page.route('https://fixture.test/**', async route => {
                if (new URL(route.request().url()).pathname === '/blocked.js') {
                    await gate;
                    await route.fulfill({ contentType: 'application/javascript', body: '' });
                } else await route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><style>.post{opacity:0;min-height:200px}.post.animate-in{opacity:1}</style><main class="article-list"><article class="post">Article visible before optional scripts</article></main><script>' + pageLoaderBootstrap + animations + '</script><script src="/blocked.js"></script>' });
            });
            try {
                const started = page.waitForRequest('https://fixture.test/blocked.js');
                const navigation = page.goto('https://fixture.test/', { waitUntil: 'domcontentloaded' });
                await started;
                await page.waitForFunction(() => document.querySelector('.post').classList.contains('animate-in'));
                assert.strictEqual(await page.evaluate(() => document.readyState), 'loading', 'fixture must keep the footer script pending');
                assert.strictEqual(await page.locator('.post').evaluate(node => getComputedStyle(node).opacity), '1', 'cards must become visible without waiting for optional footer scripts');
                await page.waitForFunction(() => !document.getElementById('page-loader').classList.contains('is-visible'));
                release();
                await navigation;
            } finally { release(); await page.close(); }
        }

        for (const scenario of ['render', 'stale', 'retry']) {
            const page = await browser.newPage();
            page.setDefaultTimeout(5000);
            const warnings = [];
            page.on('console', message => { if (message.type() === 'warning') warnings.push(message.text()); });
            const errors = []; let requests = 0, release;
            const gate = new Promise(resolve => { release = resolve; });
            page.on('pageerror', e => { errors.push(e.message); console.error(scenario + ': ' + e.message); });
            await page.route('https://fixture.test/**', async route => {
                if (new URL(route.request().url()).pathname === '/diagram.js') {
                    requests++;
                    if (scenario === 'stale') await gate;
                    if (scenario === 'retry' && requests === 1) await route.fulfill({ status: 503, body: 'unavailable' });
                    else await route.fulfill({ contentType: 'application/javascript; charset=utf-8', path: library });
                } else await route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<!doctype html><main id="primary">Plain home page</main>' });
            });
            try {
                await page.goto('https://fixture.test/');
                await page.addScriptTag({ content: 'window.argonMermaidLoaderConfig={src:"https://fixture.test/diagram.js",options:{theme:"default"}};' + loader });
                assert.strictEqual(requests, 0, 'plain homepage must not request any diagram library');
                if (scenario === 'stale') {
                    const started = page.waitForRequest('https://fixture.test/diagram.js');
                    await page.evaluate(() => {
                        document.querySelector('#primary').innerHTML = '<pre class="mermaid">graph TD\nA-->B</pre>';
                        window.rendering = ArgonMermaidLoader.render(document.querySelector('#primary'));
                    });
                    await started;
                    await page.evaluate(() => { document.querySelector('#primary').innerHTML = 'Next page'; });
                    release();
                    await page.evaluate(() => window.rendering);
                    assert.strictEqual(await page.locator('#primary').textContent(), 'Next page');
                } else {
                    async function render() {
                        await page.evaluate(() => {
                            document.querySelector('#primary').innerHTML = '<pre class="mermaid">graph TD\nA[Start]-->B[Finish]</pre>';
                            return ArgonMermaidLoader.render(document.querySelector('#primary'));
                        });
                    }
                    await render();
                    if (scenario === 'retry') {
                        assert.strictEqual(await page.locator('.mermaid svg').count(), 0);
                        await render();
                    }
                    await page.waitForSelector('.mermaid svg');
                    assert.strictEqual(requests, scenario === 'retry' ? 2 : 1);
                    await render();
                    await page.waitForSelector('.mermaid svg');
                    assert.strictEqual(requests, scenario === 'retry' ? 2 : 1, 'later pages must reuse the loaded dependency');
                }
                assert.deepStrictEqual(errors, [], scenario + ': browser errors');
                assert.strictEqual(warnings.length, scenario === 'retry' ? 1 : 0, scenario + ': unexpected rendering warning');
            } finally { release(); await page.close(); }
        }
        console.log('Frontend asset browser fixtures passed: slow CSS cannot block first paint, cards show before optional scripts, homepage skips diagrams, PJAX diagrams render, obsolete DOM is skipped and failed loads retry.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
