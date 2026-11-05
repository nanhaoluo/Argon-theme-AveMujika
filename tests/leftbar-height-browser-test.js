'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const themeRoot = process.env.ARGON_THEME_ROOT || root;
const script = fs.readFileSync(path.join(themeRoot, 'argontheme.js'), 'utf8');
const allocation = script.slice(script.indexOf('function computeLeftbarHeightAllocation('), script.indexOf('/*左侧栏随页面滚动浮动*/'));
const sticky = script.slice(script.indexOf('/*左侧栏随页面滚动浮动*/'), script.indexOf('/*左侧栏滚轮焦点切换*/'));
const wheel = script.slice(script.indexOf('/*左侧栏滚轮焦点切换*/'), script.indexOf('/*Headroom*/'));

(async function () {
 const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
 try {
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.setContent('<!doctype html><title>Sidebar height regression</title><div id="content"><aside id="leftbar"><div class="leftbar-desktop-content"><div id="leftbar_part1" style="height:40px"></div><div id="leftbar_part2" class="card"><div id="leftbar_part2_inner" class="card-body"><div class="nav-wrapper">文章目录 / 概览</div><div><div class="tab-content" style="padding:10px 10px 0"><div id="leftbar_tab_catalog" class="tab-pane active show"><div id="leftbar_catalog"></div></div><div id="leftbar_tab_overview" class="tab-pane">概览</div></div></div></div></div><div id="leftbar_part3" class="card"><div class="card-body"><h3>TODO</h3><div id="todo-list"></div></div></div></div></aside></div>');
  await page.addStyleTag({ path: path.join(root, 'assets/css/argon.css') });
  await page.addStyleTag({ path: path.join(themeRoot, 'style.css') });
  await page.addStyleTag({ content: 'body { min-height:3000px; } #leftbar { width:240px; } #leftbar .nav-wrapper { min-height:40px; }' });
  await page.evaluate(() => {
   document.getElementById('leftbar_catalog').innerHTML = '<div style="height:32px">章节</div>'.repeat(40);
   document.getElementById('todo-list').innerHTML = '<div style="height:40px">任务</div>'.repeat(4);
  });
  await page.addScriptTag({ path: path.join(root, 'assets/vendor/jquery/jquery.min.js') });
  await page.addScriptTag({ path: path.join(root, 'assets/js/argon-motion.js') });
  await page.addScriptTag({ content: 'var argonConfig = { headroom: "fixed" }; var argonEventManager = null;' + allocation +
   'var fixtureLayoutPasses = 0; var fixtureAllocate = computeLeftbarHeightAllocation; computeLeftbarHeightAllocation = function (options) { fixtureLayoutPasses++; return fixtureAllocate(options); };' + sticky + wheel });
  async function settle() {
   await page.evaluate(async () => {
    const frame = () => new Promise(resolve => requestAnimationFrame(resolve));
    for (let i = 0; i < 5; i++) await frame();
    await Promise.all(document.getAnimations().map(animation => animation.finished.catch(() => {})));
    for (let i = 0; i < 3; i++) await frame();
   });
  }
  async function geometry() {
   return page.evaluate(() => {
    const rect = node => { const r = node.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, height: r.height, width: r.width }; };
    const pixels = (value, fallback) => Number.isFinite(parseFloat(value)) ? parseFloat(value) : fallback;
    const p2 = document.getElementById('leftbar_part2');
    const p3 = document.getElementById('leftbar_part3');
    const inner = document.getElementById('leftbar_part2_inner');
    const pane = p2.querySelector('.tab-pane.active');
    const body = p3.querySelector('.card-body');
    const bar = document.getElementById('leftbar');
    const style = getComputedStyle(bar);
    return { p2: rect(p2), p3: rect(p3), inner: rect(inner), pane: rect(pane),
     width: bar.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight),
     gap: pixels(style.getPropertyValue('--leftbar-sticky-gap'), 10),
     bottom: pixels(style.getPropertyValue('--leftbar-sticky-bottom'), 20),
     catalogActive: pane.id === 'leftbar_tab_catalog',
     compact: p2.classList.contains('leftbar-pane-compact'),
     innerOverflow: inner.scrollHeight - inner.clientHeight,
     paneOverflow: pane.scrollHeight - pane.clientHeight,
     bodyOverflow: body.scrollHeight - body.clientHeight,
     unclamped: document.documentElement.style.getPropertyValue('--leftbar-part2-inner-height') === 'none',
     allocations: fixtureLayoutPasses };
   });
  }
  async function assertLayout(name, height) {
   await settle();
   const r = await geometry();
   assert.ok(r.p3.bottom <= height - r.bottom + 0.1, name + ': cards exceed viewport budget ' + JSON.stringify(r));
   assert.ok(Math.abs(r.p3.top - r.p2.bottom - r.gap) < 0.1, name + ': inconsistent card gap ' + JSON.stringify(r));
   assert.ok(Math.abs(r.p2.width - r.width) < 0.1 && Math.abs(r.p3.width - r.width) < 0.1, name + ': sticky widths differ from sidebar');
   if (r.catalogActive && !r.compact) assert.ok(r.pane.bottom <= r.inner.bottom + 0.1, name + ': catalog leaks out of the allocated card');
   await settle();
   assert.strictEqual((await geometry()).allocations, r.allocations, name + ': layout observer did not settle');
   return r;
  }
  await page.evaluate(() => { window.scrollTo(0, 500); window.argonRequestLeftbarStickyStatusUpdate(); });
  await page.waitForFunction(() => document.getElementById('leftbar_part2').classList.contains('sticky'));
  await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
  const metrics = await page.evaluate(() => {
   const rect = id => { const r = document.getElementById(id).getBoundingClientRect(); return { top:r.top, bottom:r.bottom, height:r.height }; };
   return { part2:rect('leftbar_part2'), part3:rect('leftbar_part3'), catalog:rect('leftbar_tab_catalog'), todo:rect('todo-list'), p2:document.documentElement.style.getPropertyValue('--leftbar-part2-inner-height'), p3:document.documentElement.style.getPropertyValue('--leftbar-part3-max-height') };
  });
  console.log(metrics);
  assert.deepStrictEqual(errors, [], 'sidebar must initialize without errors');
  assert.ok(metrics.part3.bottom <= 780, 'TODO card must remain inside the viewport budget');
  assert.ok(metrics.part3.height >= metrics.todo.height + 24, 'a short TODO list must not be squeezed by a long catalog');
  if (process.env.SIDEBAR_QA_SCREENSHOT) await page.screenshot({ path: process.env.SIDEBAR_QA_SCREENSHOT });

  // Content replacement follows the same refresh entry as PJAX and TODO updates.
  for (const scenario of [
   { name: 'short catalog / long TODO', catalogRows: 2, todoRows: 20, height: 800 },
   { name: 'both short after clamp', catalogRows: 2, todoRows: 2, height: 800 },
   { name: 'both long / shorter viewport', catalogRows: 40, todoRows: 20, height: 600 },
   { name: 'both long / headroom unpinned', catalogRows: 40, todoRows: 20, height: 600, unpinned: true }
  ]) {
   await page.setViewportSize({ width: 1280, height: scenario.height });
   await page.evaluate(s => {
    document.getElementById('leftbar_catalog').innerHTML = '<div style="height:32px">章节</div>'.repeat(s.catalogRows);
    document.getElementById('todo-list').innerHTML = '<div style="height:40px">任务</div>'.repeat(s.todoRows);
    document.body.classList.toggle('headroom---unpinned', !!s.unpinned);
    window.argonRequestLeftbarStickyStatusUpdate();
   }, scenario);
   await settle();
   const result = await page.evaluate(() => {
    const p2 = document.getElementById('leftbar_part2').getBoundingClientRect();
    const p3 = document.getElementById('leftbar_part3').getBoundingClientRect();
    const pane = document.getElementById('leftbar_tab_catalog');
    return { p2Height: p2.height, p3Height: p3.height, bottom: p3.bottom, gap: p3.top - p2.bottom,
     catalogOverflow: pane.scrollHeight > pane.clientHeight,
     unclamped: document.documentElement.style.getPropertyValue('--leftbar-part2-inner-height') === 'none' };
   });
   assert.ok(result.bottom <= scenario.height - 20 + 1, scenario.name + ': cards exceed viewport budget');
   assert.ok(result.gap >= 9, scenario.name + ': cards overlap');
   if (scenario.catalogRows === 2) assert.ok(result.p2Height < 200, scenario.name + ': short catalog must shrink naturally');
   if (scenario.todoRows === 20) assert.ok(result.p3Height > 100, scenario.name + ': long TODO must retain usable space');
   if (scenario.todoRows === 2) assert.ok(result.unclamped && !result.catalogOverflow, scenario.name + ': old clamps must be released');
   console.log(scenario.name + ': passed');
  }

  // Real styles, padding, borders and line wrapping must feed the measured budget.
  await page.setViewportSize({ width: 1280, height: 600 });
  await page.addStyleTag({ content: `
   #leftbar_part2_inner { padding: 17.25px 12px 22.5px; }
   #leftbar_part2_inner .tab-content { padding: 19.5px 10px 8.25px !important; }
   #leftbar .nav-wrapper { height: 67.5px; min-height: 67.5px; }
   #leftbar_part2 { padding: 2.25px 0 3.5px; border: 1.5px solid transparent; }
   #leftbar_part3 { padding: 3.5px 0 2.25px; border: 1.5px solid transparent; margin-top: 17.5px; }
  ` });
  await page.evaluate(() => document.body.classList.remove('headroom---unpinned'));
  let result = await assertLayout('fractional padding / card chrome', 600);
  assert.ok(!result.compact && result.paneOverflow > 0, 'normal-height catalog keeps its own scroller');

  // Height invalidation must preserve the position in BOTH independently scrolled cards.
  await page.evaluate(() => {
   document.getElementById('leftbar_tab_catalog').scrollTop = 170;
   document.querySelector('#leftbar_part3 .card-body').scrollTop = 120;
   document.body.classList.add('headroom---unpinned');
  });
  await assertLayout('headroom / scroll position preservation', 600);
  assert.deepStrictEqual(await page.evaluate(() => [document.getElementById('leftbar_tab_catalog').scrollTop, document.querySelector('#leftbar_part3 .card-body').scrollTop]),
   [170, 120], 'temporary natural-height measurement must preserve scroll positions');
  await page.evaluate(() => {
   document.body.classList.remove('headroom---unpinned');
   document.documentElement.classList.add('navbar-absolute');
   argonConfig.headroom = 'absolute';
  });
  result = await assertLayout('absolute navbar', 600);
  assert.strictEqual(result.p2.top, 20);

  // Narrow desktop used to have an extra translateY; both cards share one origin now.
  for (const width of [950, 901, 1100]) {
   await page.setViewportSize({ width, height: 600 });
   await page.evaluate(() => document.documentElement.classList.add('triple-column'));
   result = await assertLayout('desktop width ' + width, 600);
   assert.strictEqual(result.p2.top, 20, 'responsive styles must not displace the sticky origin');
  }

  // At tiny heights, allow the tab controls to scroll rather than overflow the card.
  await page.setViewportSize({ width: 1280, height: 280 });
  await page.evaluate(() => {
   document.documentElement.classList.remove('navbar-absolute', 'triple-column');
   argonConfig.headroom = 'fixed';
  });
  result = await assertLayout('compact catalog', 280);
  assert.ok(result.compact && result.innerOverflow > 0, 'tiny viewport must scroll the entire part2 inner');
  await page.evaluate(() => {
   document.getElementById('leftbar_part2_inner').scrollTop = 0;
   document.querySelector('#leftbar_part2 .nav-wrapper').dispatchEvent(new WheelEvent('wheel', { deltaY: 50, bubbles: true, cancelable: true }));
  });
  await page.waitForFunction(() => !window.ArgonMotion.isScrolling(document.getElementById('leftbar_part2_inner')));
  assert.strictEqual(await page.locator('#leftbar_part2_inner').evaluate(node => node.scrollTop), 50, 'compact wheel focus must target inner');

  // Overview/tools tabs use an outer scroller, including wheel input over the tab header.
  await page.setViewportSize({ width: 1280, height: 600 });
  await page.evaluate(() => {
   document.getElementById('leftbar_tab_catalog').classList.remove('active', 'show');
   const pane = document.getElementById('leftbar_tab_overview');
   pane.classList.add('active', 'show');
   pane.innerHTML = '<div style="height:38px">概览项目</div>'.repeat(30);
  });
  result = await assertLayout('long overview', 600);
  assert.ok(result.innerOverflow > 0, 'long overview must have an inner scroller');
  await page.evaluate(() => {
   document.getElementById('leftbar_part2_inner').scrollTop = 0;
   document.querySelector('#leftbar_part2 .nav-wrapper').dispatchEvent(new WheelEvent('wheel', { deltaY: 65, bubbles: true, cancelable: true }));
  });
  await page.waitForFunction(() => !window.ArgonMotion.isScrolling(document.getElementById('leftbar_part2_inner')));
  assert.strictEqual(await page.locator('#leftbar_part2_inner').evaluate(node => node.scrollTop), 65, 'overview wheel focus must target inner');
  await page.evaluate(() => { document.getElementById('leftbar_part2').style.paddingTop = '7.75px'; });
  await assertLayout('card style change', 600);
  assert.strictEqual(await page.locator('#leftbar_part2_inner').evaluate(node => node.scrollTop), 65, 'overview reading position must survive remeasurement');

  // PJAX replaces inner contents, and TODO rows can collapse without an explicit refresh.
  await page.evaluate(() => {
   document.getElementById('leftbar_part2_inner').innerHTML = '<div class="nav-wrapper" style="display:none"></div><div><div class="tab-content"><div id="leftbar_tab_catalog" class="tab-pane active show"><div id="leftbar_catalog">短目录</div></div></div></div>';
   document.getElementById('leftbar_part3').innerHTML = '<div class="card-body"><h6 class="todo-title">TODO</h6><ul id="todo-list" class="todo-list">' +
    '<li class="todo-item"><span>可折行的待办事项，包括较长的文本内容，检查卡片高度是否计算准确。</span></li>'.repeat(12) + '</ul></div>';
   window.argonRequestLeftbarStickyStatusUpdate();
  });
  result = await assertLayout('PJAX replacement / wrapped TODO', 600);
  assert.ok(result.p2.height < 150 && result.bodyOverflow > 0, 'short catalog must give its unused space to TODO');
  await page.evaluate(() => document.querySelectorAll('#todo-list .todo-item').forEach((node, index) => { if (index > 0) node.classList.add('todo-completed', 'collapsed'); }));
  result = await assertLayout('collapsed TODO / clamp release', 600);
  assert.ok(result.unclamped && result.paneOverflow <= 1 && result.bodyOverflow <= 1, 'short content must release old clamps without phantom overflow');

  // Hiding TODO and explicit zero-valued spacing must also reallocate automatically.
  await page.evaluate(() => {
   document.getElementById('leftbar_catalog').innerHTML = '<div style="height:32px">章节</div>'.repeat(40);
   document.getElementById('leftbar_part3').hidden = true;
  });
  await settle();
  result = await geometry();
  assert.ok(Math.abs(result.p2.bottom - 580) < 0.1, 'catalog must use the available budget when TODO is hidden');
  for (const mode of ['style', 'class']) {
   await page.evaluate(mode => {
    const card = document.getElementById('leftbar_part3');
    card.hidden = false;
    if (mode === 'style') card.style.display = 'none';
    else { card.style.display = ''; card.classList.add('d-none'); }
   }, mode);
   await settle();
   assert.ok(Math.abs((await geometry()).p2.bottom - 580) < 0.1, mode + ': invisible TODO must not reserve space');
  }
  await page.evaluate(() => {
   document.getElementById('leftbar_part3').classList.remove('d-none');
   document.getElementById('leftbar').style.setProperty('--leftbar-sticky-gap', '0px');
   document.getElementById('leftbar').style.setProperty('--leftbar-sticky-bottom', '0px');
  });
  await assertLayout('zero gap / bottom spacing', 600);

  // Mobile must release desktop layout, and returning to desktop must remeasure.
  await page.setViewportSize({ width: 900, height: 600 });
  await settle();
  assert.strictEqual(await page.locator('#leftbar_part2').evaluate(node => node.classList.contains('sticky')), false);
  assert.strictEqual(await page.evaluate(() => document.documentElement.style.getPropertyValue('--leftbar-part2-inner-height')), 'none');
  await page.setViewportSize({ width: 1280, height: 600 });
  await assertLayout('desktop after mobile', 600);

  // Ordinary page scroll uses cached layout instead of remeasuring each frame.
  const beforeScroll = (await geometry()).allocations;
  await page.evaluate(() => window.scrollBy(0, 100));
  await settle();
  assert.strictEqual((await geometry()).allocations, beforeScroll, 'page scroll must reuse the height allocation');
  assert.deepStrictEqual(errors, [], 'all layout updates must remain free of browser errors');
  console.log('leftbar-height-browser-test: all assertions passed');
 } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
