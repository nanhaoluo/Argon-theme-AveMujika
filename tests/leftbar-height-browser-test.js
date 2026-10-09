'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const script = fs.readFileSync(path.join(root, 'argontheme.js'), 'utf8');
const allocation = script.slice(script.indexOf('function computeLeftbarHeightAllocation('), script.indexOf('/*左侧栏随页面滚动浮动*/'));
const sticky = script.slice(script.indexOf('/*左侧栏随页面滚动浮动*/'), script.indexOf('/*左侧栏滚轮焦点切换*/'));

(async function () {
 const browser = await chromium.launch({ headless: true, channel: 'chrome' });
 try {
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.setContent('<!doctype html><title>Sidebar height regression</title><div id="content"><aside id="leftbar"><div class="leftbar-desktop-content"><div id="leftbar_part1" style="height:40px"></div><div id="leftbar_part2" class="card"><div id="leftbar_part2_inner" class="card-body"><div class="nav-wrapper">文章目录 / 概览</div><div><div class="tab-content" style="padding:10px 10px 0"><div id="leftbar_tab_catalog" class="tab-pane active show"><div id="leftbar_catalog"></div></div><div id="leftbar_tab_overview" class="tab-pane">概览</div></div></div></div></div><div id="leftbar_part3" class="card"><div class="card-body"><h3>TODO</h3><div id="todo-list"></div></div></div></div></aside></div>');
  for (const file of ['assets/css/argon.css', 'style.css']) await page.addStyleTag({ path: path.join(root, file) });
  await page.addStyleTag({ content: 'body { min-height:3000px; } #leftbar { width:240px; } #leftbar .nav-wrapper { min-height:40px; }' });
  await page.evaluate(() => {
   document.getElementById('leftbar_catalog').innerHTML = '<div style="height:32px">章节</div>'.repeat(40);
   document.getElementById('todo-list').innerHTML = '<div style="height:40px">任务</div>'.repeat(4);
  });
  await page.addScriptTag({ path: path.join(root, 'assets/vendor/jquery/jquery.min.js') });
  await page.addScriptTag({ content: 'var argonConfig = { headroom: "fixed" }; var argonEventManager = null;' + allocation + sticky });
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
   await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
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
  assert.deepStrictEqual(errors, [], 'all layout updates must remain free of browser errors');
  console.log('leftbar-height-browser-test: all assertions passed');
 } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
