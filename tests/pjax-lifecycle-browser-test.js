'use strict';

// Real jQuery + bundled PJAX + theme lifecycle, with feature initializers stubbed.
// This fixture verifies navigation behavior; it is not full WordPress integration QA.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const themeRoot = process.env.ARGON_THEME_ROOT || root;
const source = fs.readFileSync(path.join(themeRoot, 'argontheme.js'), 'utf8');
function section(start, end) {
    const first = source.indexOf(start);
    const last = source.indexOf(end, first);
    assert.ok(first >= 0 && last > first, 'missing source section: ' + start);
    return source.slice(first, last);
}
const plugin = fs.readFileSync(path.join(themeRoot, 'assets/argon_js_merged.js'), 'utf8')
    .split('\n').find(line => line.startsWith('!function(t){function e(e,r,a)'));
const featureNames = [
    'syncArgonPjaxPageClasses', 'syncPjaxPageInfoClasses', 'syncPjaxPrimaryClasses',
    'initArgonLongContentVirtualizer', 'waterflowInit', 'initArgonSpecialText',
    'lazyloadInit', 'highlightJsRender', 'panguInit', 'clampInit', 'tippyInit',
    'getGithubInfoCardContent', 'showPostOutdateToast', 'calcHumanTimesOnPage',
    'foldLongComments', 'foldLongShuoshuo', 'handleHashNavigation', 'initArticleSkeletons',
    'initPostFullBlur', 'argonInitCommentEditor', 'initArgonPostActions', 'initArgonTodoFallbacks',
    'cleanupPageSpecificState', 'resetGT4Captcha'
];
const setup = `
    var argonConfig = { disable_pjax: false, pjax_animation_durtion: 0 };
    var pjaxScrollTop = 0, pjaxLoading = false, pjaxCompleteRan = false;
    var argonPjaxPageGeneration = 0, argonPjaxRequestId = 0;
    var argonPjaxDomReplaced = false, argonPjaxCancelInit = null;
    var pjaxContainers = ['#primary', '#leftbar_part2_inner', '#rightbar'];
    var argonPjaxLinkSelector = 'a[href]:not([no-pjax])';
    window.fixtureCalls = []; window.fixtureReady = {}; window.fixtureWarnings = []; window.sidebarRuns = 0;
    window.originalDocumentAddEventListener = document.addEventListener;
    var ArgonDebug = { log() {}, error() { throw new Error(Array.from(arguments).join(' ')); },
        warn() { fixtureWarnings.push(Array.from(arguments).join(' ')); } };
    var NProgress = { status: null, configure() {}, remove() {}, start() { this.status = 0.1; },
        set(n) { this.status = n; }, done() { this.status = null; fixtureCalls.push(['progressDone', location.pathname]); } };
    function cleanupPjaxResources() {
        fixtureCalls.push(['cleanup', location.pathname]);
        if (typeof cancelPjaxScriptLoads === 'function') cancelPjaxScriptLoads();
        document.querySelectorAll('script[data-dynamic="true"]').forEach(node => node.remove());
    }
    ${featureNames.map(name => `function ${name}() { fixtureCalls.push(['${name}', location.pathname]); }`).join('\n')}
    window.pjaxLoaded = function () {
        fixtureReady[location.pathname] = (fixtureReady[location.pathname] || 0) + 1;
    };
`;

function html(pathname) {
    const scripts = pathname === '/scripts' ? `
        <script data-pjax>window.scriptRuns = (window.scriptRuns || 0) + 1;</script>
        <script src="/dependency.js" data-pjax></script>
        <script src="/dependency.js" data-pjax></script>
        <script src="/legacy.js" nomodule></script>
        <script data-pjax>
            if (window.dependencyRuns !== 1) throw new Error('dependency missing or executed twice');
            window.inlineRuns = (window.inlineRuns || 0) + 1;
            window.moduleReady = false;
        </script>
        <script type="module" data-pjax>
            await new Promise(resolve => setTimeout(resolve, 25));
            window.moduleReady = true;
        </script>
        <script>
            if (!window.moduleReady) throw new Error('inline module was not awaited');
            window.unmarkedRuns = (window.unmarkedRuns || 0) + 1;
        </script>
        <script type="application/json" id="fixture-data">{"preserved":true}</script>
    ` : pathname === '/slow' ? `
        <script src="/slow.js" data-pjax></script>
        <script data-pjax>window.staleInlineRan = true;</script>
    ` : pathname === '/flaky' ? '<script src="/flaky.js" data-pjax></script>' : '';
    return `<!doctype html><title>${pathname}</title>
        <div id="primary" data-page="${pathname}"><article>${pathname}</article>${scripts}</div>
        <div id="leftbar_part2_inner"><span>${pathname} catalog</span></div>
        <div id="rightbar"><span>${pathname} sidebar</span><script data-pjax>
            window.sidebarRuns = (window.sidebarRuns || 0) + 1;
            if (!document.querySelector('#leftbar_part2_inner').textContent.includes(${JSON.stringify(pathname)}))
                throw new Error('lifecycle ran before all containers were restored');
        </script></div>`;
}

(async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const release = {};
    try {
        const page = await browser.newPage();
        page.setDefaultTimeout(10000);
        const errors = [], counts = {};
        page.on('pageerror', error => errors.push(error.message));
        await page.route('https://fixture.test/**', async route => {
            const pathname = new URL(route.request().url()).pathname;
            counts[pathname] = (counts[pathname] || 0) + 1;
            if (pathname === '/slow.js' || pathname === '/pending') {
                await new Promise(resolve => { release[pathname] = resolve; });
            }
            if (pathname.endsWith('.js')) {
                const body = pathname === '/dependency.js' ? 'window.dependencyRuns = (window.dependencyRuns || 0) + 1;'
                    : pathname === '/flaky.js' ? 'window.flakyLoaded = true;' : 'window.slowDependencyLoaded = true;';
                await route.fulfill({ status: pathname === '/flaky.js' && counts[pathname] === 1 ? 503 : 200,
                    contentType: 'application/javascript', body });
            } else {
                await route.fulfill({ status: pathname === '/error' ? 503 : 200,
                    contentType: 'text/html', body: html(pathname) });
            }
        });
        await page.goto('https://fixture.test/current');
        await page.addScriptTag({ path: path.join(root, 'assets/vendor/jquery/jquery.min.js') });
        await page.addScriptTag({ content: setup + plugin });
        await page.addScriptTag({ content:
            section('var argonPjaxReadyReplayDepth', '\nvar pjaxContainerSelectors') +
            section('function reActivatePageScripts()', '/**\n * 重置 GT4 验证码') +
            section('var ArgonPjaxPrefetch = (function () {', "\nwindow.addEventListener('hashchange'") });
        await page.evaluate(() => {
            window.fixtureLifecycle = [];
            jQuery(document).on('pjax:start pjax:beforeReplace pjax:end', e => fixtureLifecycle.push(e.type));
            // Retain the fixture on HTTP failures, as a site error handler can do.
            jQuery(document).on('pjax:error.fixture', e => e.preventDefault());
        });
        async function navigate(url) {
            await page.evaluate(url => { jQuery.pjax({ url, container: pjaxContainers, fragment: pjaxContainers }); }, url);
        }
        async function ready(url, count = 1) {
            try {
                await page.waitForFunction(({ url, count }) => fixtureReady[url] === count, { url, count });
            } catch (error) {
                console.error(JSON.stringify({ errors, state: await page.evaluate(() => ({
                    ready: fixtureReady, calls: fixtureCalls, warnings: fixtureWarnings,
                    loading: pjaxLoading, generation: argonPjaxPageGeneration,
                    scripts: Array.from(document.scripts).map(s => ({ src: s.src, type: s.type, executed: s.getAttribute('data-pjax-executed') }))
                })) }));
                throw error;
            }
        }
        await navigate('/scripts');
        await ready('/scripts');
        assert.deepStrictEqual(await page.evaluate(() => [scriptRuns, inlineRuns, unmarkedRuns, dependencyRuns, sidebarRuns]), [1, 1, 1, 1, 1]);
        assert.strictEqual(counts['/dependency.js'], 1, 'duplicate dependency placeholders load only once');
        assert.strictEqual(counts['/legacy.js'], undefined, 'nomodule fallback is skipped by module-capable browsers');
        assert.strictEqual(await page.locator('#fixture-data').textContent(), '{"preserved":true}');
        await navigate('/plain');
        await ready('/plain');
        await page.evaluate(() => { fixtureLifecycle = []; history.back(); });
        await ready('/scripts', 2);
        assert.deepStrictEqual(await page.evaluate(() => fixtureLifecycle), ['pjax:start', 'pjax:beforeReplace', 'pjax:end'], 'history navigation fires one lifecycle for all containers');
        assert.deepStrictEqual(await page.evaluate(() => [scriptRuns, inlineRuns, unmarkedRuns, dependencyRuns, sidebarRuns]), [2, 2, 2, 1, 3]);
        assert.strictEqual(counts['/scripts'], 1, 'back navigation restores the cached DOM');
        await page.evaluate(() => history.forward());
        await ready('/plain', 2);
        assert.strictEqual(await page.evaluate(() => fixtureCalls.filter(call => call[0] === 'lazyloadInit').length), 4, 'back/forward restores feature initialization');

        const slowStarted = page.waitForRequest('https://fixture.test/slow.js');
        await navigate('/slow');
        await slowStarted;
        await navigate('/fast');
        await ready('/fast');
        release['/slow.js']();
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        assert.strictEqual(await page.evaluate(() => !!window.staleInlineRan), false, 'obsolete script chains cannot initialize the next page');
        assert.strictEqual(await page.evaluate(() => fixtureReady['/slow'] || 0), 0, 'obsolete navigation cannot fire the completion callback');
        assert.strictEqual(await page.evaluate(() => document.addEventListener === originalDocumentAddEventListener), true, 'ready listener replay is restored after cancellation');
        assert.strictEqual(await page.evaluate(() => argonPjaxScriptCleanups.length), 0);
        assert.strictEqual(await page.evaluate(() => Object.keys(argonPjaxModuleCompletions).length), 0);

        const pendingStarted = page.waitForRequest(request => new URL(request.url()).pathname === '/pending');
        await navigate('/pending');
        await pendingStarted;
        await navigate('/after-abort');
        await ready('/after-abort');
        release['/pending']();
        assert.strictEqual(await page.evaluate(() => fixtureReady['/fast']), 1, 'aborted GET must not reinitialize the displayed page');
        const beforeError = await page.evaluate(() => JSON.stringify(fixtureReady));
        const failed = page.waitForResponse(response => new URL(response.url()).pathname === '/error');
        await navigate('/error');
        await failed;
        await page.waitForFunction(() => !pjaxLoading);
        assert.strictEqual(await page.evaluate(() => JSON.stringify(fixtureReady)), beforeError, 'HTTP error must not initialize old content');
        await navigate('/recover');
        await ready('/recover');
        await navigate('/flaky');
        await ready('/flaky');
        await navigate('/away');
        await ready('/away');
        await navigate('/flaky');
        await ready('/flaky', 2);
        assert.strictEqual(counts['/flaky.js'], 2, 'failed external dependency can retry on the next visit');
        assert.strictEqual(await page.evaluate(() => window.flakyLoaded), true);
        assert.deepStrictEqual(errors, [], 'all navigation paths must be free of browser errors');
        console.log('PJAX lifecycle browser fixture passed: script ordering/deduplication, modules, multi-container back/forward, stale initialization, abort/error and dependency retry.');
    } finally {
        Object.values(release).forEach(resolve => resolve());
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
