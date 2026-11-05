'use strict';

// Actual template controls and theme CSS; local browser fixture, not WordPress integration QA.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const template = fs.readFileSync(path.join(root, 'comments.php'), 'utf8').replace(/<\?[\s\S]*?\?>/g, '');

(async () => {
    const browser = await chromium.launch({ headless:true, channel:process.env.PLAYWRIGHT_CHANNEL || undefined });
    try {
        const page = await browser.newPage({ viewport:{width:1280,height:900} });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<!doctype html><body id="fixture-body"><section id="post_comment" class="card"><h2>发送评论</h2><form></form></section><input id="other-field" class="argon-text-input"></body>');
        await page.evaluate(markup => {
            const parsed = new DOMParser().parseFromString(markup, 'text/html');
            const form = document.querySelector('#post_comment form');
            const editor = parsed.getElementById('post_comment_content_editor');
            editor.dataset.placeholder = '评论内容'; form.appendChild(editor);
            const row = document.createElement('div'); row.className = 'row'; form.appendChild(row);
            for (const [id, label] of [['post_comment_name','昵称'],['post_comment_email','邮箱 / QQ 号'],['post_comment_captcha','验证码'],['post_comment_link','网站']]) {
                const input = parsed.getElementById(id); input.placeholder = label;
                const column = document.createElement('div'); column.className = 'col-md-6'; row.appendChild(column);
                column.appendChild(input.closest('.input-group'));
            }
        }, template);
        for (const file of ['assets/argon_css_merged.css','style.css']) await page.addStyleTag({path:path.join(root,file)});
        await page.addStyleTag({content: ':root { --themecolor:#fa7298; --themecolor-R:250; --themecolor-G:114; --themecolor-B:152; --card-radius:8px; --card-blur:20px; } body#fixture-body { margin:0; min-height:100vh; padding:24px; background:repeating-linear-gradient(35deg,#78aef3 0 45px,#f2b6cc 45px 90px,#b9edf5 90px 135px)!important; } #post_comment { max-width:1100px;margin:auto;padding:24px;background:rgba(255,255,255,.5)!important;backdrop-filter:blur(20px); } html.darkmode #post_comment { background:rgba(45,45,45,.6)!important; } #other-field { position:fixed;left:-1000px; }' });
        async function settle() {
            await page.evaluate(async () => {
                await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
                await Promise.all(document.getAnimations().map(animation => animation.finished.catch(() => {})));
            });
        }
        async function surfaces() {
            return page.evaluate(() => {
                const canvas = document.createElement('canvas'); canvas.width = canvas.height = 1;
                const ctx = canvas.getContext('2d');
                const inspect = node => {
                    const s = getComputedStyle(node); ctx.clearRect(0,0,1,1); ctx.fillStyle=s.backgroundColor;ctx.fillRect(0,0,1,1);
                    return { background:s.backgroundColor, alpha:ctx.getImageData(0,0,1,1).data[3]/255, radius:s.borderRadius, border:s.borderTopColor, borderWidth:s.borderTopWidth, backdrop:s.backdropFilter, shadow:s.boxShadow, color:s.color };
                };
                const editor = document.getElementById('post_comment_content_editor');
                const name = document.getElementById('post_comment_name');
                const email = document.getElementById('post_comment_email');
                const probe = document.createElement('span');probe.style.color='var(--color-text-deeper)';document.getElementById('post_comment').appendChild(probe);
                const themeText=getComputedStyle(probe).color;probe.remove();
                return { editor:inspect(editor), name:inspect(name.closest('.input-group')), email:inspect(email.closest('.input-group')),
                    embedded:inspect(name), icon:inspect(name.closest('.input-group').querySelector('.input-group-text')),
                    unrelated:inspect(document.getElementById('other-field')), themeText,
                    overflow:document.documentElement.scrollWidth-document.documentElement.clientWidth };
            });
        }
        // Computed alpha alone misses the white veil caused by nested backdrop
        // compositing. Compare painted, empty areas against the same card glass.
        async function paintedSurfaces() {
            const samples = [];
            for (const selector of ['#post_comment_content_editor', '#post_comment_name']) {
                const node = selector.endsWith('_name') ? page.locator(selector).locator('..') : page.locator(selector);
                const png = await node.screenshot();
                samples.push(await page.evaluate(async base64 => {
                    const image = new Image(); image.src = 'data:image/png;base64,' + base64; await image.decode();
                    const canvas = document.createElement('canvas'); canvas.width = image.width; canvas.height = image.height;
                    const ctx = canvas.getContext('2d'); ctx.drawImage(image, 0, 0);
                    const pixels = ctx.getImageData(image.width - 48, Math.floor(image.height / 2) - 4, 16, 8).data;
                    const rgb = [0, 0, 0];
                    for (let i = 0; i < pixels.length; i += 4) for (let c = 0; c < 3; c++) rgb[c] += pixels[i + c] / (pixels.length / 4);
                    return rgb;
                }, png.toString('base64')));
            }
            return samples;
        }
        await page.mouse.move(0, 0); await settle();
        const paintedGlass = await paintedSurfaces();
        const referenceStyle = await page.addStyleTag({content:'#post_comment { --argon-text-input-backdrop:none; }'});
        const paintedCard = await paintedSurfaces();
        await referenceStyle.evaluate(node => node.remove());
        assert.ok(paintedCard.some(rgb => Math.min(...rgb) < 230), 'visual reference must contain a coloured backdrop');
        assert.ok(paintedGlass.every((rgb, i) => rgb.every((value, c) => Math.abs(value - paintedCard[i][c]) <= 12)),
            'nested glass must preserve background colour instead of painting a white veil: ' + JSON.stringify({paintedGlass, paintedCard}));
        for (const mode of ['', 'darkmode', 'darkmode amoled-dark', 'darkmode immersion-color']) {
            await page.evaluate(mode => { document.activeElement.blur(); document.documentElement.className = mode; }, mode);
            for (const width of [1280,390,320]) {
                await page.setViewportSize({width,height:900}); await page.mouse.move(0,0); await settle();
                const r=await surfaces(), label=(mode||'light')+'/'+width;
                assert.strictEqual(r.name.background,r.editor.background,label+': nickname surface differs from editor');
                assert.strictEqual(r.email.background,r.editor.background,label+': email surface differs from editor');
                assert.strictEqual(r.name.radius,r.editor.radius,label+': radii differ');
                assert.strictEqual(r.name.backdrop,r.editor.backdrop,label+': blur differs');
                assert.ok(r.editor.alpha>=.07 && r.editor.alpha<=.15 && r.editor.backdrop.includes('blur(36px)'),label+': frosted input must stay translucent with a strong background blur');
                assert.ok(parseFloat(r.editor.radius)>=14,label+': a small card radius must not produce square field corners');
                assert.strictEqual(r.embedded.alpha,0,label+': nested input must not add an opaque second surface');
                assert.strictEqual(r.embedded.backdrop,'none',label+': blur must be applied once to the whole group');
                assert.strictEqual(r.embedded.borderWidth,'0px',label+': nested input must not add an inner border');
                assert.strictEqual(r.icon.alpha,0,label+': icon surface must be transparent');
                assert.strictEqual(r.embedded.color,r.themeText,label+': text must follow the active theme');
                assert.ok(r.overflow<=1,label+': form overflows viewport');
                assert.ok(r.unrelated.alpha<.1,label+': scoped input changes leaked to unrelated controls');
                if (process.env.COMMENT_INPUT_QA_DIR && width===1280 && ['', 'darkmode'].includes(mode)) {
                    fs.mkdirSync(process.env.COMMENT_INPUT_QA_DIR,{recursive:true});
                    await page.locator('#post_comment').screenshot({path:path.join(process.env.COMMENT_INPUT_QA_DIR,'comment-'+(mode||'light')+'.png')});
                }
            }
            await page.locator('#post_comment_name').focus(); await settle();
            const focusedGroup=(await surfaces()).name;
            await page.locator('#post_comment_content_editor').focus(); await settle();
            const focusedEditor=(await surfaces()).editor;
            assert.strictEqual(focusedGroup.background,focusedEditor.background,mode+': focus backgrounds differ');
            assert.strictEqual(focusedGroup.shadow,focusedEditor.shadow,mode+': focus rings differ');
            assert.ok(focusedEditor.alpha>=.12 && focusedEditor.alpha<=.16,mode+': focused input must retain its translucent glass surface');
        }
        assert.deepStrictEqual(errors,[]);
        console.log('Comment input surfaces passed: matching editor/identity fields, glass opacity/blur, focus, mobile and theme palettes.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error);process.exitCode=1; });
