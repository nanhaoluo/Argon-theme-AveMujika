'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const browser = require('../assets/js/friend-links-browser.js');

async function run() {
    assert.deepStrictEqual(browser.buildCandidates('sample-blog.dev'), [
        'https://sample-blog.dev/',
        'https://www.sample-blog.dev/',
        'https://blog.sample-blog.dev/'
    ]);
    assert.deepStrictEqual(browser.buildCandidates('blog.sample-blog.dev'), [
        'https://blog.sample-blog.dev/',
        'https://sample-blog.dev/',
        'https://www.sample-blog.dev/'
    ]);
    assert.strictEqual(browser.getRootDomain('blog.example.com.cn'), 'example.com.cn');
    assert.strictEqual(browser.isSameCandidateSiteUrl('https://sample-blog.dev/', 'https://blog.sample-blog.dev/'), true);
    assert.strictEqual(browser.isSameCandidateSiteUrl('https://foo.co.uk/', 'https://evil.co.uk/'), false);
    assert.strictEqual(browser.isSameCandidateSiteUrl('https://foo.github.io/', 'https://evil.github.io/'), false);
    assert.deepStrictEqual(
        browser.splitTitle('编程小记 – 其实我也不知道建这个站拿来干嘛=-='),
        { primary: '编程小记', subtitle: '其实我也不知道建这个站拿来干嘛=-=' }
    );
    assert.deepStrictEqual(
        browser.splitTitle('编程小记 &#8211; 其实我也不知道建这个站拿来干嘛=-='),
        { primary: '编程小记', subtitle: '其实我也不知道建这个站拿来干嘛=-=' }
    );

    const detected = await browser.detectSite('sample-blog.dev', {
        timeout: 1000,
        fetch: function(url) {
            if (url === 'https://blog.sample-blog.dev/wp-json/') {
                return Promise.resolve({
                    ok: true,
                    json: function() {
                        return Promise.resolve({
                            name: '编程小记 – 其实我也不知道建这个站拿来干嘛=-=',
                            description: '一份编程与生活记录',
                            home: 'https://blog.sample-blog.dev/',
                            site_icon_url: 'https://blog.sample-blog.dev/icon.png'
                        });
                    }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(detected.success, true);
    assert.strictEqual(detected.url, 'https://blog.sample-blog.dev/');
    assert.strictEqual(detected.info.primary, '编程小记');
    assert.strictEqual(detected.info.subtitle, '其实我也不知道建这个站拿来干嘛=-=');

    const constrained = await browser.detectSite('sample-blog.dev', {
        timeout: 1000,
        fetch: function(url) {
            if (url === 'https://sample-blog.dev/wp-json/') {
                return Promise.resolve({
                    ok: true,
                    json: function() {
                        return Promise.resolve({ name: '编程小记', home: 'https://attacker.example/' });
                    }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(constrained.url, 'https://sample-blog.dev/');

    const opaque = await browser.detectSite('opaque.example', {
        timeout: 1000,
        fetch: function(url, options) {
            if (options.mode === 'no-cors') return Promise.resolve({ type: 'opaque' });
            return Promise.reject(new TypeError('CORS blocked'));
        }
    });
    assert.strictEqual(opaque.success, true);
    assert.strictEqual(opaque.readable, false);

    const backlink = await browser.checkBacklink('https://friend.example/links/', 'https://www.sample-blog.dev/', {
        timeout: 1000,
        fetch: function() {
            return Promise.resolve({
                ok: true,
                url: 'https://friend.example/links/',
                text: function() {
                    return Promise.resolve('<a href="https://sample-blog.dev/about">编程小记</a>');
                }
            });
        }
    });
    assert.strictEqual(backlink, true);

    const noBacklink = await browser.checkBacklink('https://friend.example/links/', 'https://sample-blog.dev/', {
        timeout: 1000,
        fetch: function() {
            return Promise.resolve({
                ok: true,
                url: 'https://friend.example/links/',
                text: function() { return Promise.resolve('<a href="https://another-blog.dev/">另一个网站</a>'); }
            });
        }
    });
    assert.strictEqual(noBacklink, false);

    const unknown = await browser.checkBacklink('https://friend.example/links/', 'https://sample-blog.dev/', {
        timeout: 1000,
        fetch: function() { return Promise.reject(new TypeError('CORS blocked')); }
    });
    assert.strictEqual(unknown, null);

    const page = fs.readFileSync(path.join(__dirname, '..', 'friend-links.php'), 'utf8');
    const inlineScripts = Array.from(page.matchAll(/<script data-pjax>([\s\S]*?)<\/script>/g));
    assert.ok(inlineScripts.length > 0, '友链页缺少内联交互脚本');
    const inlineScript = inlineScripts[inlineScripts.length - 1][1].replace(/<\?php[\s\S]*?\?>/g, '0');
    assert.doesNotThrow(function() { new Function(inlineScript); });
}

run().then(function() {
    console.log('浏览器友链探测测试通过。');
}).catch(function(error) {
    console.error(error);
    process.exit(1);
});
