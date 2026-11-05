'use strict';

// Actual motion, wheel/FAB handlers and virtualizer; fixture QA, not full WordPress QA.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const http = require('http');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const themeRoot = process.env.ARGON_THEME_ROOT || root;
const source = fs.readFileSync(path.join(themeRoot, 'argontheme.js'), 'utf8');
const motion = fs.readFileSync(path.join(themeRoot, 'assets/js/argon-motion.js'), 'utf8');
const section = (start, end) => {
    const first = source.indexOf(start), last = source.indexOf(end, first);
    assert.ok(first >= 0 && last > first, 'Missing source section: ' + start);
    return source.slice(first, last);
};
const wheel = section('/*左侧栏滚轮焦点切换*/', '/*Headroom*/');
const virtualizer = section('function getVirtualScrollDirection(', 'function patchHeadIndexForVirtualContent()');
const settle = page => page.waitForFunction(() => !window.ArgonMotion.isScrolling());

async function setup(page, markup, css) {
    await page.setContent('<!doctype html><html><body>' + markup + '</body></html>');
    await page.addStyleTag({ content: 'body { margin:0; font:16px/1.5 sans-serif; } :root { --motion-fast-duration:150ms; } ' + css });
    await page.addScriptTag({ path: path.join(root, 'assets/vendor/jquery/jquery.min.js') });
    await page.addScriptTag({ content: motion });
}
async function sample(page, getPosition, frames = 20) {
    return page.evaluate(async ({ getPosition, frames }) => {
        const position = new Function('return (' + getPosition + ')');
        const points = [];
        for (let i = 0; i < frames; i++) {
            await new Promise(resolve => requestAnimationFrame(resolve));
            points.push(position());
        }
        return points;
    }, { getPosition, frames });
}

(async () => {
    const allowed = ['argontheme.js', 'assets/vendor/jquery/jquery.min.js', 'tests/long-content-scroll-fixture.html', 'tests/long-content-middle-visibility-fixture.html', 'tests/long-content-comment-boundary-fixture.html'];
    const server = http.createServer((req, res) => {
        const name = new URL(req.url, 'http://localhost').pathname.slice(1);
        if (!allowed.includes(name)) { res.writeHead(404); res.end(); return; }
        const file = name === 'argontheme.js' ? path.join(themeRoot, name) : path.join(root, name);
        res.setHeader('Content-Type', name.endsWith('.js') ? 'application/javascript' : 'text/html; charset=utf-8');
        res.end(fs.readFileSync(file));
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        page.setDefaultTimeout(15000);

        await setup(page, '<aside id="leftbar_part2" class="sticky"><div id="leftbar_part2_inner"><div class="nav-wrapper">目录</div><div class="tab-pane active" id="catalog">' + '<p>章节</p>'.repeat(100) + '</div></div></aside><aside id="leftbar_part3"><div class="card-body">' + '<p>待办</p>'.repeat(100) + '</div></aside>', 'body { height:4000px; } aside { position:fixed; width:240px; left:20px; } #leftbar_part2 { top:20px; } #leftbar_part3 { top:260px; } .tab-pane,.card-body { height:180px;overflow-y:auto; }');
        await page.addScriptTag({ content: wheel });
        for (const selector of ['#catalog', '#leftbar_part3 .card-body']) {
            const immediate = await page.locator(selector).evaluate(node => { node.scrollTop = 0; node.dispatchEvent(new WheelEvent('wheel', { deltaY:120, bubbles:true, cancelable:true })); return node.scrollTop; });
            assert.strictEqual(immediate, 0, selector + ': wheel must not jump synchronously');
            const points = await sample(page, 'document.querySelector(' + JSON.stringify(selector) + ').scrollTop');
            assert.ok(new Set(points).size > 4, selector + ': wheel must have multiple intermediate frames');
            assert.ok(points.slice(1).every((value, i) => value >= points[i]), selector + ': wheel reversed unexpectedly');
            await page.waitForFunction(selector => !ArgonMotion.isScrolling(document.querySelector(selector)), selector);
            assert.strictEqual(await page.locator(selector).evaluate(node => node.scrollTop), 120);
        }
        await page.locator('#catalog').evaluate(node => {
            node.scrollTop = 0;
            for (let i = 0; i < 4; i++) node.dispatchEvent(new WheelEvent('wheel', { deltaY:120, bubbles:true, cancelable:true }));
        });
        await page.waitForFunction(() => !ArgonMotion.isScrolling(document.getElementById('catalog')));
        assert.strictEqual(await page.locator('#catalog').evaluate(node => node.scrollTop), 480, 'rapid wheel inputs must accumulate');
        await page.locator('#catalog').evaluate(node => {
            node.dispatchEvent(new WheelEvent('wheel', { deltaY:300, bubbles:true, cancelable:true }));
        });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        const reverseStart = await page.locator('#catalog').evaluate(node => {
            const start = node.scrollTop;
            node.dispatchEvent(new WheelEvent('wheel', { deltaY:-120, bubbles:true, cancelable:true }));
            return start;
        });
        await page.waitForFunction(() => !ArgonMotion.isScrolling(document.getElementById('catalog')));
        assert.ok(Math.abs(await page.locator('#catalog').evaluate(node => node.scrollTop) - (reverseStart - 120)) <= 1, 'reversal must discard forward momentum');
        assert.strictEqual(await page.locator('#catalog').evaluate(node => {
            const event = new WheelEvent('wheel', { deltaY:120, ctrlKey:true, bubbles:true, cancelable:true });
            node.dispatchEvent(event); return event.defaultPrevented;
        }), false, 'pinch zoom must not be intercepted');
        await page.locator('#catalog').evaluate(node => {
            node.scrollTop = 0;
            node.dispatchEvent(new WheelEvent('wheel', { deltaY:120,bubbles:true,cancelable:true }));
            node.dispatchEvent(new WheelEvent('wheel', { deltaY:-120,bubbles:true,cancelable:true }));
        });
        await page.waitForTimeout(200);
        assert.strictEqual(await page.locator('#catalog').evaluate(node => node.scrollTop), 0, 'reversing before the first frame must cancel pending momentum');
        console.log('Sidebar wheel: smooth frames, accumulated input, reversal and pinch zoom passed.');

        await setup(page, '<div id="spacer" style="height:4000px"></div><div id="target">目标</div><div style="height:1600px"></div>', 'html { scroll-behavior:smooth; }');
        await page.evaluate(() => { window.done = ArgonMotion.scrollTo(document.getElementById('target'), { offset:80, duration:260 }); });
        await page.waitForTimeout(80);
        await page.locator('#spacer').evaluate(node => { node.style.height = '4400px'; });
        assert.strictEqual(await page.evaluate(() => window.done), true);
        assert.ok(Math.abs(await page.locator('#target').evaluate(node => node.getBoundingClientRect().top) - 80) <= 1, 'moving target must finish at the correct offset');
        assert.strictEqual(await page.evaluate(() => document.documentElement.style.getPropertyValue('scroll-behavior')), '', 'CSS smooth behavior must be restored');
        await page.evaluate(() => { window.done = ArgonMotion.scrollTo(0, { duration:500 }); });
        await page.waitForTimeout(80);
        await page.evaluate(() => document.dispatchEvent(new WheelEvent('wheel', { deltaY:30, bubbles:true })));
        assert.strictEqual(await page.evaluate(() => window.done), false, 'manual scrolling must cancel navigation');
        await page.emulateMedia({ reducedMotion:'reduce' });
        assert.strictEqual(await page.evaluate(() => ArgonMotion.scrollTo(0, { duration:500 })), true);
        assert.strictEqual(await page.evaluate(() => scrollY), 0, 'reduced motion must be instant even with an explicit duration');
        await page.emulateMedia({ reducedMotion:'no-preference' });
        console.log('Motion: dynamic target, native CSS compatibility, cancellation and reduced motion passed.');

        const commentsMarkup = '<article><main id="post_content"></main></article><button id="comments_toggle">展开评论</button><section id="comments" class="comments-collapsed"><div style="height:1600px">评论列表</div></section><section id="post_comment" class="comments-collapsed"><textarea id="post_comment_content"></textarea><div style="height:900px"></div></section><div id="float_action_buttons"><button id="fabtn_back_to_top">顶部</button><button id="fabtn_go_to_comment">评论</button></div><footer style="height:600px"></footer>';
        await setup(page, commentsMarkup, '#post_content { max-width:720px; margin:auto; } .fixture {height:360px;margin:0} #comments,#post_comment { overflow:hidden;max-height:3000px;transition:max-height .3s; } .comments-collapsed { max-height:0!important; } .comments-expand-done { max-height:none!important; } #float_action_buttons {position:fixed;right:0;top:0;}');
        await page.addScriptTag({ content: 'var ArgonDebug={warn(){},log(){}}; var highlightObserver=null; var prepareCount=0; var lazyloadInit=function(){}; var highlightJsRender=function(){prepareCount++;}; var tippyInit=function(){}; var patchHeadIndexForVirtualContent=function(){}; var escapeJqueryId=function(id){return id;}; var __=function(x){return x;};' + virtualizer });
        await page.evaluate(() => {
            const sourceSections = Array.from({length:300}, (_, i) => ({html:'<div class="fixture"><h2 id="heading-'+i+'">章节 '+i+'</h2>正文内容 '+i+'</div>',headers:[{id:'heading-'+i,level:2,text:'章节 '+i}],nodeIds:['heading-'+i],textCount:120,estimatedHeight:360}));
            window.ArgonLongContentVirtualizer = createArgonLongContentVirtualizer(document.getElementById('post_content'), { sourceSections, keepRange:2, viewportBuffer:900 });
            ArgonLongContentVirtualizer.init();
        });
        await page.waitForTimeout(250);
        const targetMetrics = await page.evaluate(() => {
            let reads = 0; const original = Element.prototype.getBoundingClientRect;
            Element.prototype.getBoundingClientRect = function() { if (this.classList.contains('argon-virtual-section')) reads++; return original.call(this); };
            ArgonLongContentVirtualizer.renderTarget('heading-240');
            Element.prototype.getBoundingClientRect = original;
            return { mounted:document.querySelectorAll('.argon-virtual-section-mounted').length, reads };
        });
        assert.ok(targetMetrics.mounted < 30, 'distant anchors must not mount the entire intervening article: ' + JSON.stringify(targetMetrics));
        assert.ok(targetMetrics.reads < 250, 'section lookup must avoid full geometry scans: ' + JSON.stringify(targetMetrics));
        await page.addScriptTag({ content: section('function argonMarkCommentsExpandDone(', 'var argonPostFullBlurState') });
        await page.addScriptTag({ content: 'var $backToTopBtn=jQuery("#fabtn_back_to_top"),$goToComment=jQuery("#fabtn_go_to_comment");' + section('$backToTopBtn.on("click"', '\n\t$toggleDarkmode.on') + section('$goToComment.on("click"', '\n\tif (localStorage') });
        await page.locator('#fabtn_go_to_comment').click();
        await settle(page);
        assert.strictEqual(await page.evaluate(() => document.activeElement.id), 'post_comment_content');
        assert.ok(Math.abs(await page.locator('#post_comment').evaluate(node => node.getBoundingClientRect().top) - 80) <= 2, 'focus must not pull the page away from the requested comment target');
        await page.locator('#fabtn_back_to_top').click();
        const upward = await sample(page, 'window.scrollY', 45);
        await settle(page);
        assert.strictEqual(await page.evaluate(() => scrollY), 0);
        assert.ok(upward.slice(1).every((value, i) => value <= upward[i] + 2), 'return-to-top must not bounce against virtual tail locking');
        assert.ok(await page.locator('#post_content').evaluate(node => node.innerText.includes('正文内容')), 'returned viewport must contain actual mounted text');
        console.log('Virtual/FAB: bounded distant mounting, comment focus, tail-to-top animation and visible text passed.', targetMetrics);

        await page.evaluate(() => {
            ArgonLongContentVirtualizer.destroy(false);
            const content = document.getElementById('post_content');
            content.innerHTML = Array.from({length:35}, (_, i) => '<div id="block-'+i+'" style="height:360px">段落 '+i+(i===4?'<button id="retained-button">交互</button>':i===5?'<input id="retained-input">':'')+'</div>').join('');
            window.retainedButton = document.getElementById('retained-button'); window.retainedInput = document.getElementById('retained-input'); window.clicks=0;
            retainedButton.addEventListener('click',()=>{clicks++;}); retainedInput.value='未提交的草稿';
            window.ArgonLongContentVirtualizer=createArgonLongContentVirtualizer(content,{keepRange:2,maxNodesPerSection:1});
            ArgonLongContentVirtualizer.init();
            ArgonLongContentVirtualizer.renderTarget('block-4');
            retainedButton.click();
            ArgonLongContentVirtualizer.renderTarget('block-30');
            scrollTo({top:10000,behavior:'instant'});
        });
        await page.waitForTimeout(300);
        await page.evaluate(() => { ArgonLongContentVirtualizer.renderTarget('block-4'); retainedButton.click(); ArgonLongContentVirtualizer.destroy(true); });
        assert.deepStrictEqual(await page.evaluate(() => ({ sameButton:retainedButton===document.getElementById('retained-button'),sameInput:retainedInput===document.getElementById('retained-input'),value:retainedInput.value,clicks })), { sameButton:true,sameInput:true,value:'未提交的草稿',clicks:2 });
        console.log('Virtual compatibility: DOM identity, native listeners and input state survive restore.');

        for (const fixture of ['long-content-scroll-fixture.html','long-content-middle-visibility-fixture.html','long-content-comment-boundary-fixture.html']) {
            await page.goto('http://127.0.0.1:' + server.address().port + '/tests/' + fixture);
            await page.waitForFunction(() => window.__probeResult, null, { timeout:30000 });
            const result = await page.evaluate(() => window.__probeResult);
            assert.ok(result.pass, fixture + ': ' + JSON.stringify(result));
            console.log(fixture + ': passed');
        }
        await page.addInitScript(() => { delete window.IntersectionObserver; delete window.ResizeObserver; });
        for (const fixture of ['long-content-middle-visibility-fixture.html','long-content-comment-boundary-fixture.html']) {
            await page.goto('http://127.0.0.1:' + server.address().port + '/tests/' + fixture);
            await page.waitForFunction(() => window.__probeResult, null, { timeout:30000 });
            const result = await page.evaluate(() => window.__probeResult);
            assert.ok(result.pass, 'Observer fallback / ' + fixture + ': ' + JSON.stringify(result));
            console.log('No IntersectionObserver/ResizeObserver: ' + fixture + ' passed');
        }
        assert.deepStrictEqual(errors, [], 'all scroll flows must remain free of browser errors');
    } finally { await browser.close(); await new Promise(resolve => server.close(resolve)); }
})().catch(error => { console.error(error); process.exitCode = 1; });
