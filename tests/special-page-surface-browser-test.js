'use strict';

// Real theme CSS with representative template markup. This is component/fixture QA,
// not a full WordPress render or a test of live feedback/Worker endpoints.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const themeRoot = process.env.ARGON_THEME_ROOT || root;
const read = name => fs.readFileSync(path.join(themeRoot, name), 'utf8');
function style(file, id) {
    const match = read(file).match(new RegExp('<style id="' + id + '">([\\s\\S]*?)</style>'));
    assert.ok(match, id + ' must exist');
    return match[1];
}
const shared = style('inc/special-page-surface.php', 'argon-special-page-surface-style');
const header = title => `<header class="argon-special-page-header"><div class="argon-special-page-header-copy"><h1 class="argon-special-page-title">${title}</h1></div><div class="argon-special-page-stats">${['全部', '已完成', '待解决'].map(label => `<div class="argon-special-page-stat"><span>${label}</span><strong class="argon-special-page-stat-value">12</strong></div>`).join('')}</div></header>`;
const field = (label, id) => `<div class="form-group"><label>${label}</label><input id="${id}" class="form-control argon-text-input" placeholder="${label}"></div>`;
// Include real controls so a missing shared class cannot be hidden by the fixture.
const feedbackTemplate = read('feedback.php').replace(/<\?[\s\S]*?\?>/g, '');
const feedbackField = (label, id) => {
    const input = feedbackTemplate.match(new RegExp('<input[^>]*id="' + id + '"[^>]*>'));
    assert.ok(input, id + ' must exist in the feedback template');
    return `<div class="form-group"><label>${label}</label>${input[0].replace('placeholder=""', `placeholder="${label}"`)}</div>`;
};
const feedback = `${header('问题反馈')}
    <article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-panel feedback-form-card">
        <div class="feedback-card-heading"><h2 class="feedback-form-title">提交反馈</h2><span class="feedback-draft-state show">草稿已保存</span></div>
        <form class="feedback-form"><div class="form-row">${feedbackField('昵称', 'feedback-name')}${feedbackField('邮箱', 'feedback-email')}</div>
        <div class="form-row">${feedbackField('标题', 'feedback-title')}${feedbackField('网站', 'feedback-url')}</div>
        <div class="form-row full"><div class="form-group"><label>反馈类型</label><div class="feedback-type-selector">${['建议', 'Bug', '问题', '其他'].map((label, i) => `<label class="feedback-type-option"><input type="radio" name="type" ${i === 0 ? 'checked' : ''}><span>${label}</span></label>`).join('')}</div></div></div>
        <div class="form-row full"><div class="form-group"><label>详细内容</label><textarea class="argon-text-input" rows="4" placeholder="请详细描述您的建议或遇到的问题"></textarea></div></div>
        <div class="feedback-public-option"><input type="checkbox" id="public"><label for="public">公开此反馈</label><span class="hint">公开后其他访客可以看到</span></div>
        <div class="feedback-submit"><button class="btn btn-primary" type="button">提交反馈</button></div></form>
    </article>
    <article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-panel feedback-form-card">
        <h2 class="feedback-list-title">公开反馈</h2><div class="feedback-list-tools"><div class="feedback-search-box"><i class="fa fa-search"></i><input class="argon-text-input" placeholder="搜索标题、内容或昵称"></div>${['全部类型', '全部状态'].map(label => `<label class="feedback-filter-control"><i class="fa fa-tag"></i><select class="argon-text-input"><option>${label}</option></select><i class="fa fa-angle-down feedback-select-arrow"></i></label>`).join('')}<button class="btn btn-secondary feedback-list-clear">重置</button></div>
        <div class="feedback-item clickable"><div class="feedback-item-header"><div class="feedback-item-avatar">访</div><div class="feedback-item-meta"><div class="feedback-item-name">访客<span class="feedback-item-time">2026-11-05</span></div><div class="feedback-item-badges"><span class="feedback-badge argon-special-page-badge type-bug">Bug</span><span class="feedback-badge argon-special-page-badge status-pending">待解决</span><span class="feedback-badge argon-special-page-badge status-resolved">已解决</span></div></div></div><div class="feedback-item-content">页面显示问题的具体描述，检查长内容在手机上的换行与正文颜色。</div><div class="feedback-item-reply"><div class="feedback-item-reply-header">管理员回复</div><div class="feedback-item-reply-content">已修复，请刷新后重试。</div></div></div>
    </article>
    <div class="feedback-modal-overlay" id="fixture-dialog"><div class="feedback-modal argon-special-page-dialog"><div class="feedback-modal-header"><h3 class="feedback-modal-title">反馈详情</h3><button class="feedback-modal-close">×</button></div><div class="feedback-modal-body">${'<p>详细反馈内容</p>'.repeat(20)}<textarea class="feedback-reply-input argon-text-input" placeholder="输入新留言"></textarea></div><div class="feedback-modal-footer"><button class="btn btn-primary btn-sm">回复并完成</button><button class="btn btn-secondary btn-sm">取消</button></div></div></div>`;
const friends = `${header('友情链接')}
    <div class="argon-special-page-list">${['朋友们', '技术博客'].map(category => `<article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-section"><div class="argon-special-page-section-header"><h2 class="argon-special-page-section-title">${category}</h2><span class="argon-special-page-section-count">3 个站点</span></div><div class="argon-special-page-grid">${['编程小记', '生活与摄影', '开源项目'].map(label => `<a class="flink-card" href="#"><div class="flink-avatar" data-letter="友"></div><div class="flink-info"><div class="flink-name">${label}</div><div class="flink-desc">记录编程、生活和有趣的事，保持简洁自然的阅读体验。</div><div class="flink-url"><span class="flink-url-text">blog.example.com</span></div></div></a>`).join('')}</div></article>`).join('')}</div>
    <article class="post card shadow-sm bg-white border-0 argon-special-page-card flinks-apply-card"><button class="flinks-apply-header" type="button"><span class="flinks-apply-title">申请友链</span><i class="flinks-apply-arrow"></i></button><div class="flinks-apply-content open"><form class="flinks-form"><div class="row">${['网站地址', '网站名称', '联系邮箱', '友链页面'].map((label, i) => `<div class="col-md-6">${field(label, 'apply-' + i)}</div>`).join('')}</div><div class="flinks-submit"><button class="btn btn-primary" type="button">提交申请</button></div></form></div></article>
    <div class="flink-modal-overlay" id="fixture-dialog"><div class="flink-modal argon-special-page-dialog"><div class="flink-modal-title">编辑友链<button class="flink-modal-close">×</button></div><div class="form-grid">${['名称', '地址', '邮箱', '描述'].map((label, i) => field(label, 'edit-' + i)).join('')}</div><div class="form-actions"><button class="btn btn-secondary">检测互链</button><button class="btn btn-primary">保存</button></div></div></div>`;
const worker = `${header('友链 Worker 实时状态')}<div class="argon-special-page-list"><article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-section"><div class="argon-special-page-section-header"><h2 class="argon-special-page-section-title">状态汇总</h2></div><div class="argon-special-page-grid">${['service', 'runtime.queue', 'callback_pending'].map(label => `<article class="argon-special-page-metric"><div class="argon-special-page-metric-label">${label}</div><div class="argon-special-page-metric-value">12</div></article>`).join('')}</div></article><article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-section"><div class="argon-special-page-section-header"><h2 class="argon-special-page-section-title">最近任务</h2></div><div class="argon-special-page-table-wrap"><table class="argon-special-page-table"><thead><tr>${['任务', '目标', '状态', '互链', '回调', '更新时间', '详细信息'].map(label => `<th>${label}</th>`).join('')}</tr></thead><tbody><tr>${['1', 'https://example.com/friends', 'complete', '是', '完成', '2026-11-05', '—'].map(value => `<td>${value}</td>`).join('')}</tr></tbody></table></div></article></div>`;

(async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        for (const [name, markup, css] of [
            ['feedback', feedback, style('feedback.php', 'feedback-page-style')],
            ['friends', friends, style('friend-links.php', 'flinks-page-style')],
            ['worker', worker, '']
        ]) {
            await page.setContent(`<!doctype html><html><body class="argon-special-page"><div id="content"><div id="primary"><main id="main">${markup}</main></div></div></body></html>`);
            await page.addStyleTag({ path: path.join(root, 'assets/argon_css_merged.css') });
            await page.addStyleTag({ path: path.join(root, 'style.css') });
            await page.addStyleTag({ content: shared + css });
            // The fixture omits the WordPress banner. Card settings mirror a normal theme setup.
            await page.addStyleTag({ content: ':root { --card-radius:8px; --card-opacity:1; --card-blur:0px; --card-saturate:100%; } body.argon-special-page #content { margin-top:0!important; }' });
            await page.evaluate(async () => {
                await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
                await Promise.all(document.getAnimations().map(animation => animation.finished.catch(() => {})));
            });
            for (const mode of ['', 'darkmode', 'darkmode amoled-dark', 'immersion-color darkmode']) {
                await page.evaluate(mode => { document.documentElement.className = mode; }, mode);
                await page.evaluate(async () => {
                    await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
                    await Promise.all(document.getAnimations().map(animation => animation.finished.catch(() => {})));
                });
                for (const width of [1280, 768, 390, 320]) {
                    await page.setViewportSize({ width, height: 900 });
                    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
                    const result = await page.evaluate(() => {
                        const main = document.getElementById('main');
                        const cards = Array.from(main.querySelectorAll('.argon-special-page-card'));
                        const mainRect = main.getBoundingClientRect();
                        const probe = document.createElement('span'); probe.style.color = 'var(--color-text-deeper)'; main.appendChild(probe);
                        const themeText = getComputedStyle(probe).color; probe.remove();
                        const canvas = document.createElement('canvas'); canvas.width = canvas.height = 1;
                        const context = canvas.getContext('2d');
                        return { overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
                            panelPadding: Array.from(main.querySelectorAll('.argon-special-page-panel')).map(node => parseFloat(getComputedStyle(node).paddingLeft)),
                            cardsContained: cards.every(node => { const r = node.getBoundingClientRect(); return r.left >= mainRect.left - 1 && r.right <= mainRect.right + 1; }),
                            fields: Array.from(main.querySelectorAll('input.argon-text-input, textarea.argon-text-input, select.argon-text-input, .feedback-type-option')).filter(node => !node.closest('.argon-special-page-dialog')).map(node => {
                                const s = getComputedStyle(node); context.clearRect(0,0,1,1); context.fillStyle = s.backgroundColor; context.fillRect(0,0,1,1);
                                return { height: node.getBoundingClientRect().height, alpha: context.getImageData(0,0,1,1).data[3] / 255,
                                    color: s.color, font: s.fontSize, radius: s.borderRadius, backdrop: s.backdropFilter };
                            }), themeText,
                            searchPadding: main.querySelector('.feedback-search-box input') && parseFloat(getComputedStyle(main.querySelector('.feedback-search-box input')).paddingLeft)
                        };
                    });
                    const label = name + ' / ' + (mode || 'light') + ' / ' + width;
                    assert.ok(result.overflow <= 1, label + ': horizontal page overflow ' + JSON.stringify(result));
                    assert.ok(result.cardsContained, label + ': a card escaped its container');
                    assert.ok(result.panelPadding.every(value => value === (width <= 768 ? 16 : 24)), label + ': inconsistent shared card padding');
                    assert.ok(result.fields.every(field => field.height >= 42 && field.font === '14px'), label + ': inconsistent field sizing ' + JSON.stringify(result.fields));
                    assert.ok(result.fields.every(field => field.alpha >= 0.07 && field.alpha <= 0.16 && /blur\((?:3[6-9]|[4-9]\d)(?:\.\d+)?px\)/.test(field.backdrop)), label + ': input must remain translucent with a strong background blur ' + JSON.stringify(result.fields));
                    assert.ok(result.fields.every(field => parseFloat(field.radius) >= 14), label + ': fields and type choices must retain soft corners with small card settings');
                    if (name === 'feedback') assert.ok(await page.locator('#feedback-url').evaluate(node => node.classList.contains('argon-text-input')), 'website field must use the shared input surface');
                    assert.ok(result.fields.every(field => field.color === result.themeText), label + ': input text must follow the active theme palette');
                    if (name === 'feedback') assert.ok(result.searchPadding >= 36, label + ': search text overlaps its icon');
                    if (process.env.SPECIAL_PAGE_QA_DIR && ['feedback', 'friends'].includes(name) && (width === 1280 || width === 390) && ['', 'darkmode'].includes(mode)) {
                        fs.mkdirSync(process.env.SPECIAL_PAGE_QA_DIR, { recursive: true });
                        await page.screenshot({ path: path.join(process.env.SPECIAL_PAGE_QA_DIR, name + '-' + (mode || 'light') + '-' + width + '.png'), fullPage: true });
                    }
                }
            }
            if (name === 'feedback' || name === 'friends') {
                await page.locator('#fixture-dialog').evaluate(node => node.classList.add('show'));
                await page.waitForFunction(() => getComputedStyle(document.getElementById('fixture-dialog')).opacity === '1');
                const dialog = await page.locator('.argon-special-page-dialog').evaluate(node => {
                    const r = node.getBoundingClientRect();
                    const probe = document.createElement('span'); probe.style.color = 'var(--color-foreground)'; document.body.appendChild(probe);
                    const foreground = getComputedStyle(probe).color; probe.remove();
                    return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, background: getComputedStyle(node).backgroundColor, foreground };
                });
                assert.ok(dialog.left >= 0 && dialog.right <= 320 && dialog.top >= 0 && dialog.bottom <= 900, name + ': mobile dialog escaped viewport');
                assert.strictEqual(dialog.background, dialog.foreground, name + ': dark dialog must use the global foreground surface');
            }
            if (name === 'friends') {
                await page.addScriptTag({ path: path.join(root, 'assets/js/argon-motion.js') });
                const source = read('friend-links.php');
                await page.addScriptTag({ content: source.slice(source.indexOf('function setApplyFormOpen('), source.indexOf('function copyText(')) });
                await page.evaluate(() => {
                    document.getElementById('fixture-dialog').classList.remove('show');
                    toggleApplyForm();
                });
                assert.strictEqual(await page.locator('.flinks-apply-content').evaluate(node => node.getBoundingClientRect().height), 0, 'collapsed application must hide its content');
                assert.strictEqual(await page.locator('.flinks-apply-header').getAttribute('aria-expanded'), 'false');
                await page.evaluate(() => {
                    const extra = document.createElement('div'); extra.style.height = '2800px';
                    document.querySelector('.flinks-apply-content').appendChild(extra);
                    openApplyForm();
                });
                assert.strictEqual(await page.locator('.flinks-apply-header').getAttribute('aria-expanded'), 'true');
                assert.ok(await page.locator('.flinks-apply-content').evaluate(node => node.clientHeight >= 2800 && node.scrollHeight <= node.clientHeight + 1), 'expanded application must not clip long requirements/forms');
            }
            console.log(name + ': responsive, light/dark/AMOLED/immersion styles passed');
        }
        assert.deepStrictEqual(errors, []);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
