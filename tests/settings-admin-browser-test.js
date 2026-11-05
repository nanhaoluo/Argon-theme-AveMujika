/** Real PHP templates + real JS/CSS, WordPress functions and AJAX fixture only. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const outputDir = path.join(require('node:os').tmpdir(), 'argon-settings-qa');
fs.mkdirSync(outputDir, {recursive:true});
const php = process.env.ARGON_PHP || 'php';
const phpArgs = process.env.ARGON_PHP_ARGS ? JSON.parse(process.env.ARGON_PHP_ARGS) : [];
const rendered = spawnSync(php, [...phpArgs, 'tests/fixtures/settings-page.php'], { cwd: root, encoding: 'utf8', env: process.env });
assert.equal(rendered.status, 0, rendered.stderr || String(rendered.error));
assert.doesNotMatch(rendered.stdout, /Warning:|Fatal error:/, 'PHP template must render cleanly');

async function main() {
 const browser = await chromium.launch({headless: true});
 const errors = [];
 try {
  const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
  page.on('pageerror', error => errors.push(error.message));
  await page.route('https://settings.test/**', route => {
   const pathname = new URL(route.request().url()).pathname;
   if (pathname === '/settings') return route.fulfill({contentType: 'text/html', body: rendered.stdout});
   if (pathname.startsWith('/theme/')) {
    const file = path.resolve(root, pathname.slice('/theme/'.length));
    if (file.startsWith(root + path.sep) && fs.existsSync(file) && fs.statSync(file).isFile()) return route.fulfill({path: file});
   }
   return route.fulfill({contentType: 'application/json', body: '{"success":true,"data":{}}'});
  });
  await page.goto('https://settings.test/settings');
  await page.waitForSelector('.argon-settings-ready');
  assert.deepEqual(errors, [], 'No initialization errors');
  const fields = await page.locator('#main_form [name]').evaluateAll(nodes => nodes.map(node => node.name).sort());
  assert.deepEqual(fields, JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures/settings-field-names.json'), 'utf8')), 'Every original field must survive reorganization exactly once');
  assert.equal(await page.locator('.argon-settings-section').count(), 12);
  const associations = {argon_enable_comment_upvote: 'comments', argon_friend_link_worker_url: 'friends', argon_enable_search_filters: 'content', argon_email_theme_color: 'mail', argon_enable_hot_reload: 'advanced', argon_pjax_disabled: 'performance'};
  for (const [name, section] of Object.entries(associations)) assert.equal(await page.locator(`[name="${name}"]`).evaluate(node => node.closest('[data-settings-section]').dataset.settingsSection), section);
  for (const category of ['appearance', 'layout', 'content', 'comments', 'ai', 'security', 'mail', 'friends', 'extensions', 'performance', 'advanced']) {
   await page.click(`[data-settings-category="${category}"]`);
   assert.equal(await page.locator('.argon-settings-section:not([hidden])').count(), 1);
   assert.equal(await page.locator('.argon-settings-section:not([hidden])').getAttribute('data-settings-section'), category);
   assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), category + ' desktop overflow');
  }
  // Cross-category search, empty state and preserved deep links.
  await page.fill('#argon-settings-search', 'Gravatar');
  await page.waitForFunction(() => document.querySelector('[data-settings-section="comments"]').hidden === false);
  assert.equal(await page.locator('.argon-settings-section:not([hidden])').getAttribute('data-settings-section'), 'comments');
  await page.fill('#argon-settings-search', 'no-such-option-xyz');
  await page.waitForSelector('#argon-settings-empty:visible');
  await page.click('#argon-settings-clear');
  await page.evaluate(() => { location.hash = 'ai-seo'; });
  await page.waitForFunction(() => !document.querySelector('[data-settings-section="ai"]').hidden);
  assert.ok(await page.locator('#ai-seo').isVisible());
  // Toggle category preserves edits, dirty filter finds them, undo restores values.
  await page.click('[data-settings-category="layout"]');
  const title = page.locator('[name="argon_toolbar_title"]');
  await title.fill('新的顶栏标题');
  await page.click('[data-settings-category="general"]');
  await page.click('#argon-settings-show-dirty');
  assert.equal(await page.locator('.argon-setting-row.is-dirty:visible').count(), 1);
  assert.equal(await title.inputValue(), '新的顶栏标题');
  await page.locator('.argon-setting-row.is-dirty .argon-row-undo').click();
  assert.equal(await title.inputValue(), '');
  await page.click('#argon-settings-clear');
  // Captured FormData must only include current row. Edits made during an in-flight save stay dirty.
  await page.evaluate(() => {
   window.__requests = [];
   jQuery.ajax = options => { const deferred = jQuery.Deferred(); window.__requests.push({options, deferred, entries: Object.fromEntries(options.data.entries())}); return deferred.promise(); };
  });
  await page.click('[data-settings-category="layout"]');
  await title.fill('提交值');
  const titleRow = title.locator('xpath=ancestor::tr[contains(@class,"argon-setting-row")]');
  await titleRow.locator('.argon-row-save').click();
  await title.fill('请求期间的新值');
  await page.evaluate(() => window.__requests[0].deferred.resolve({success: true}));
  assert.equal(await title.inputValue(), '请求期间的新值');
  assert.match(await titleRow.getAttribute('class'), /is-dirty/);
  assert.deepEqual(await page.evaluate(() => Object.keys(window.__requests[0].entries).sort()), ['action', 'argon_toolbar_title', 'nonce']);
  await titleRow.locator('.argon-row-undo').click();
  assert.equal(await title.inputValue(), '提交值');
  // HTTP errors expose the backend message, retaining the unsaved edit.
  await title.fill('失败值');
  await titleRow.locator('.argon-row-save').click();
  await page.evaluate(() => { window.__requests[1].deferred.reject({status: 409, responseJSON: {data: {message: '服务端拒绝'}}}); });
  assert.match(await titleRow.locator('.argon-row-status').textContent(), /服务端拒绝/);
  assert.match(await titleRow.getAttribute('class'), /is-dirty/);
  await titleRow.locator('.argon-row-undo').click();
  // Search filter controls and email placeholder helpers must notify the save state.
  await page.click('[data-settings-category="content"]');
  await page.locator('#search_filters_active .search-filter-item').first().click();
  assert.equal(await page.locator('[name="argon_search_filters_type"]').evaluate(node => node.closest('tr').classList.contains('is-dirty')), true);
  await page.locator('[name="argon_search_filters_type"]').locator('xpath=ancestor::tr').locator('.argon-row-undo').click();
  await page.click('[data-settings-category="mail"]');
  await page.evaluate(() => argonInsertPlaceholder('argon_email_template_comment_notify_content', '{{site_name}}'));
  assert.equal(await page.locator('[name="argon_email_template_comment_notify_content"]').evaluate(node => node.closest('tr').classList.contains('is-dirty')), true);
  // Import has no selector interpolation; all types including checkbox arrays round trip.
  await page.evaluate(() => {
   const row = document.querySelector('[name="argon_toolbar_title"]').closest('tr');
   const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.name = 'fixture_list[]'; checkbox.value = 'a'; row.querySelector('td').append(checkbox);
   window.__skipped = importArgonSettings({'fixture_list[]': ['a'], argon_friend_link_submit_hmac_secret: 'should-not-import', 'bad\"]input': 'x'});
  });
  assert.equal(await page.evaluate(() => window.__skipped), 2);
  const backup = await page.evaluate(() => JSON.parse(exportArgonSettings()));
  assert.deepEqual(backup['fixture_list[]'], ['a']);
  assert.ok(!Object.hasOwn(backup, 'argon_friend_link_submit_hmac_secret'));
  assert.ok(!Object.hasOwn(backup, 'argon_update_themeoptions_nonce'));
  // Empty dynamic date rows remain saveable; backups restore lists and clearing sends the sentinel.
  await page.evaluate(() => importArgonSettings({'panic_date_name[]':['测试日期'], 'panic_date_start[]':['2026-11-01'], 'panic_date_end[]':['2026-11-03']}));
  assert.deepEqual(await page.evaluate(() => JSON.parse(exportArgonSettings())['panic_date_name[]']), ['测试日期']);
  await page.click('#argon-settings-show-dirty');
  const dateRow = page.locator('[name="argon_panic_mode_date_ranges_present"]').locator('xpath=ancestor::tr');
  assert.equal(await dateRow.isVisible(), true, 'Changed dependent settings must be reviewable even when the feature is off');
  await dateRow.locator('.argon-row-save').click();
  const dateRequest = await page.evaluate(() => window.__requests.at(-1).entries);
  assert.equal(dateRequest.argon_panic_mode_date_ranges_present, 'true');
  assert.equal(dateRequest['panic_date_name[]'], '测试日期');
  await page.evaluate(() => { window.__requests.at(-1).deferred.resolve({success:true}); });
  await page.click('#argon-settings-clear');
  await page.click('[data-settings-category="ai"]');
  await page.locator('[name="argon_panic_mode_enable"]').check();
  await dateRow.locator('.remove-panic-date-range').click();
  await dateRow.locator('.argon-row-save').click();
  const emptyDateRequest = await page.evaluate(() => window.__requests.at(-1).entries);
  assert.equal(emptyDateRequest.argon_panic_mode_date_ranges_present, 'true');
  assert.ok(!Object.hasOwn(emptyDateRequest, 'panic_date_name[]'));
  await page.evaluate(() => { window.__requests.at(-1).deferred.resolve({success:true}); });
  // Native dialog supports keyboard dismissal and restores focus.
  await page.click('#argon-settings-export');
  assert.equal(await page.locator('#argon-settings-export-dialog').evaluate(node => node.open), true);
  await page.keyboard.press('Escape');
  await page.waitForFunction(() => !document.querySelector('#argon-settings-export-dialog').open);
  assert.equal(await page.evaluate(() => document.activeElement.id), 'argon-settings-export');
  // Every category remains usable at phone widths.
  await page.setViewportSize({width: 390, height: 844});
  for (const category of ['general', 'appearance', 'layout', 'content', 'comments', 'ai', 'security', 'mail', 'friends', 'extensions', 'performance', 'advanced']) {
   await page.locator(`[data-settings-category="${category}"]`).click();
   assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), category + ' mobile overflow ' + JSON.stringify(await page.evaluate(() => Array.from(document.querySelectorAll('.argon-settings-content *')).filter(n => n.getBoundingClientRect().right > innerWidth + 1 && n.getBoundingClientRect().width).slice(0, 10).map(n => [n.tagName, n.id, n.className, n.getBoundingClientRect().right]))));
   const bad = await page.locator(`.argon-settings-section:not([hidden]) > tr:not([hidden]) > td`).evaluateAll(nodes => nodes.filter(node => node.getBoundingClientRect().height > 0 && node.getBoundingClientRect().width < 320).length);
   assert.equal(bad, 0, category + ' mobile controls must use full width');
  }
  assert.deepEqual(errors, []);
  await page.screenshot({path: path.join(outputDir, 'settings-mobile.png')});
  await page.setViewportSize({width: 1440, height: 1000});
  await page.click('[data-settings-category="ai"]');
  await page.screenshot({path: path.join(outputDir, 'settings-ai.png')});
  console.log('[PASS] Real settings templates: field inventory, 12 categories, search, deep links, dirty state, saving races/errors, dynamic controls, backups and mobile layout');
 } finally { await browser.close(); }
}
main().catch(error => {console.error(error); process.exitCode = 1;});
