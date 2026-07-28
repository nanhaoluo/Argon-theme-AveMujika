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
                            site_icon_url: ''
                        });
                    }
                });
            }
            if (url === 'https://blog.sample-blog.dev/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() {
                        return Promise.resolve(
                            '<html><head><link rel="icon" href="/icon.png"></head>' +
                            '<body><a href="/friends/">友情链接</a></body></html>'
                        );
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
    assert.strictEqual(detected.info.description, '一份编程与生活记录');
    assert.strictEqual(detected.info.avatar, 'https://blog.sample-blog.dev/icon.png');
    assert.strictEqual(detected.info.linksPage, 'https://blog.sample-blog.dev/friends/');

    const smartAvatar = browser.extractHtmlInfo(
        '<html><head>' +
        '<link rel="icon" href="/favicon.ico">' +
        '<meta property="og:image" content="/cover.jpg">' +
        '<script type="application/ld+json">' +
        '{"@type":"WebSite","logo":{"url":"/assets/site-logo.png"}}' +
        '</script></head><body></body></html>',
        'https://sample-blog.dev/'
    );
    assert.strictEqual(smartAvatar.avatar, 'https://sample-blog.dev/assets/site-logo.png');

    const authorAvatar = browser.extractHtmlInfo(
        '<html><head>' +
        '<link rel="icon" href="/favicon.ico">' +
        '<script type="application/ld+json">' +
        '{"@graph":[' +
        '{"@type":"WebSite","logo":{"url":"/assets/site-logo.png"}},' +
        '{"@type":"Person","image":{"url":"/images/author.jpg"}}' +
        ']}' +
        '</script></head><body></body></html>',
        'https://sample-blog.dev/'
    );
    assert.strictEqual(authorAvatar.avatar, 'https://sample-blog.dev/images/author.jpg');

    const staticLanding = browser.extractHtmlInfo(
        '<html><head><title>银河宣传页</title><link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png"></head>' +
        '<body><main><img src="/assets/hero.webp" width="1200" height="630" alt="产品主视觉"></main></body></html>',
        'https://landing.example/'
    );
    assert.strictEqual(staticLanding.title, '银河宣传页');
    assert.strictEqual(staticLanding.avatar, 'https://landing.example/assets/hero.webp');

    const customForum = browser.extractHtmlInfo(
        '<html><head></head><body>' +
        '<a class="navbar-brand"><img src="/assets/community-logo.svg" alt="星海论坛"></a>' +
        '</body></html>',
        'https://forum.example/'
    );
    assert.strictEqual(customForum.siteName, '星海论坛');
    assert.strictEqual(customForum.avatar, 'https://forum.example/assets/community-logo.svg');

    const separatedDescription = await browser.detectSite('separate.example', {
        timeout: 1000,
        fetch: function(url) {
            if (url === 'https://separate.example/wp-json/') {
                return Promise.resolve({
                    ok: true,
                    json: function() {
                        return Promise.resolve({
                            name: '独立标题',
                            description: '这是网站描述，不是副标题',
                            home: 'https://separate.example/'
                        });
                    }
                });
            }
            if (url === 'https://separate.example/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() { return Promise.resolve('<html><head></head><body></body></html>'); }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(separatedDescription.info.subtitle, '');
    assert.strictEqual(separatedDescription.info.description, '这是网站描述，不是副标题');

    const dedicatedSiteIcon = await browser.detectSite('icon-priority.dev', {
        timeout: 1000,
        fetch: function(url) {
            if (url === 'https://icon-priority.dev/wp-json/') {
                return Promise.resolve({
                    ok: true,
                    json: function() {
                        return Promise.resolve({
                            name: '图标优先站点',
                            home: 'https://icon-priority.dev/',
                            site_icon_url: 'https://icon-priority.dev/uploads/site-icon-512x512.png'
                        });
                    }
                });
            }
            if (url === 'https://icon-priority.dev/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() {
                        return Promise.resolve('<html><head></head><body><img src="/hero.jpg" width="1200" height="630"></body></html>');
                    }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(dedicatedSiteIcon.info.avatar, 'https://icon-priority.dev/uploads/site-icon-512x512.png');

    const structuredSiteName = browser.extractHtmlInfo(
        '<html><head><title>文章标题 – 正确站名</title>' +
        '<meta property="og:title" content="文章标题">' +
        '<script type="application/ld+json">{"@type":"WebSite","name":"正确站名"}</script>' +
        '</head><body></body></html>',
        'https://structured-name.dev/'
    );
    assert.strictEqual(structuredSiteName.siteName, '正确站名');

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
