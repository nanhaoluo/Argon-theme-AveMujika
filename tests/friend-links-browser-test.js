'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const browser = require('../assets/js/friend-links-browser.js');

async function resolveWithin(promise, timeout, message) {
    let timer;
    try {
        return await Promise.race([
            promise,
            new Promise(function(resolve, reject) {
                timer = setTimeout(function() { reject(new Error(message)); }, timeout);
            })
        ]);
    } finally {
        clearTimeout(timer);
    }
}

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
            if (url === 'https://blog.sample-blog.dev/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() {
                        return Promise.resolve(
                            '<html><head><title>编程小记 – 其实我也不知道建这个站拿来干嘛=-=</title>' +
                            '<meta name="description" content="一份编程与生活记录">' +
                            '<link rel="icon" href="/icon.png"></head>' +
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
    assert.strictEqual(detected.info.source, 'html');

    const sufficientHtmlRequests = [];
    const sufficientHtml = await browser.detectSite('sufficient-wp.example', {
        timeout: 1000,
        fetch: function(url) {
            sufficientHtmlRequests.push(url);
            if (url !== 'https://sufficient-wp.example/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<html><head><title>完整首页</title>' +
                        '<meta name="description" content="首页已经提供完整资料">' +
                        '<link rel="https://api.w.org/" href="/custom-rest/"></head>' +
                        '<body><img class="site-logo" src="/assets/site-logo.webp" width="512" height="512">' +
                        '<a href="/friends/">友情链接</a></body></html>');
                }
            });
        }
    });
    assert.strictEqual(sufficientHtml.info.source, 'html');
    assert.strictEqual(sufficientHtml.info.avatar, 'https://sufficient-wp.example/assets/site-logo.webp');
    assert.deepStrictEqual(sufficientHtmlRequests, [
        'https://sufficient-wp.example/',
        'https://www.sufficient-wp.example/',
        'https://blog.sufficient-wp.example/'
    ], '首页资料充分时不应请求已经发现的 WordPress REST');

    const wordpressFallbackRequests = [];
    const wordpressFallback = await browser.detectSite('wp-fallback.example', {
        timeout: 1000,
        fetch: function(url) {
            wordpressFallbackRequests.push(url);
            if (url === 'https://wp-fallback.example/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() {
                        return Promise.resolve('<html><head><link rel="https://api.w.org/" href="/custom-rest/"></head>' +
                            '<body><header><img class="site-logo" src="/assets/real-logo.webp" width="512" height="512"></header></body></html>');
                    }
                });
            }
            if (url === 'https://wp-fallback.example/custom-rest/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    json: function() {
                        return Promise.resolve({
                            name: 'WordPress 回退站点',
                            description: '来自公开 REST 根节点的描述',
                            home: 'https://wp-fallback.example/',
                            site_icon_url: 'https://wp-fallback.example/favicon.ico'
                        });
                    }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(wordpressFallback.info.source, 'wordpress');
    assert.strictEqual(wordpressFallback.info.primary, 'WordPress 回退站点');
    assert.strictEqual(wordpressFallback.info.description, '来自公开 REST 根节点的描述');
    assert.strictEqual(wordpressFallback.info.avatar, 'https://wp-fallback.example/assets/real-logo.webp');
    assert.ok(wordpressFallbackRequests.indexOf('https://wp-fallback.example/custom-rest/') !== -1, '信息不足时未使用首页声明的 REST 地址');
    assert.ok(wordpressFallbackRequests.every(function(url) {
        return url.indexOf('/wp-json/') === -1;
    }), '浏览器不应猜测首页未声明的 /wp-json/ 地址');

    const argonRequests = [];
    const argonDetected = await browser.detectSite('argon-special.example', {
        timeout: 1000,
        fetch: function(url) {
            argonRequests.push(url);
            if (url !== 'https://argon-special.example/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<html><head><link rel="https://api.w.org/" href="/wp-json/"></head><body>' +
                        '<script>var argonConfig = {lazyload:true};</script>' +
                        '<div class="banner-title"><span class="banner-title-inner">Argon 星站</span>' +
                        '<span class="banner-subtitle">记录星海与代码</span></div>' +
                        '<div id="leftbar_overview_author_image" style="background-image:url(\'/uploads/argon-author.webp\')"></div>' +
                        '<h6 id="leftbar_overview_author_description">一名旅行中的开发者</h6>' +
                        '<a href="/links/">友情链接</a></body></html>');
                }
            });
        }
    });
    assert.strictEqual(argonDetected.info.source, 'argon');
    assert.strictEqual(argonDetected.info.primary, 'Argon 星站');
    assert.strictEqual(argonDetected.info.subtitle, '记录星海与代码');
    assert.strictEqual(argonDetected.info.description, '一名旅行中的开发者');
    assert.strictEqual(argonDetected.info.avatar, 'https://argon-special.example/uploads/argon-author.webp');
    assert.strictEqual(argonDetected.info.linksPage, 'https://argon-special.example/links/');
    assert.ok(argonRequests.every(function(url) {
        return url.indexOf('/wp-json/') === -1;
    }), 'Argon 首页专项资料充分时不应再访问 REST');

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

    const doraZhangAvatar = browser.extractHtmlInfo(
        '<html><body>' +
        '<a class="navbar-brand" href="https://www.dorazhang.cn/">' +
        '<img class="logo" src="data:image/svg+xml,placeholder" alt="ZyxBlog" data-lazy-src="https://www.dorazhang.cn/wp-content/uploads/2025/03/logo-m.png"></a>' +
        '<div class="post-list-avatar"><img src="/theme/loading.gif" data-src="https://q2.qlogo.cn/headimg_dl?dst_uin=2078170660&spec=100" alt="avatar" class="avatar lazy"></div>' +
        '<div class="post-list-avatar"><img src="/theme/loading.gif" data-src="https://dn-qiniu-avatar.qbox.me/avatar/50e6d265" alt="avatar" class="avatar lazy"></div>' +
        '</body></html>',
        'https://www.dorazhang.cn/'
    );
    assert.strictEqual(doraZhangAvatar.avatar, 'https://www.dorazhang.cn/wp-content/uploads/2025/03/logo-m.png');

    const wgzdyAvatar = browser.extractHtmlInfo(
        '<html><body>' +
        '<div class="header-user-avatar"><a href="https://www.wgzdy.top/wp-login.php">' +
        '<img alt="header_user_avatar" src="https://s.nmxc.ltd/sakurairo_vision/@2.5/basic/topavatar.png" width="30" height="30"></a></div>' +
        '<div class="focusinfo"><div class="header-tou"><a href="https://www.wgzdy.top/">' +
        '<img alt="avatar" src=" https://www.wgzdy.top/wp-content/uploads/avater.webp"></a></div></div>' +
        '</body></html>',
        'https://www.wgzdy.top/'
    );
    assert.strictEqual(wgzdyAvatar.avatar, 'https://www.wgzdy.top/wp-content/uploads/avater.webp');

    const genericRequests = [];
    const corsBlocked = await browser.detectSite('https://www.wgzdy.top/', {
        timeout: 1000,
        fetch: function(url, options) {
            genericRequests.push({ url: url, mode: options && options.mode });
            return Promise.reject(new TypeError('CORS blocked'));
        }
    });
    assert.strictEqual(corsBlocked.success, false);
    assert.strictEqual(corsBlocked.readable, false);
    assert.strictEqual(corsBlocked.needsProxy, true);
    assert.ok(genericRequests.length >= 1);
    assert.ok(genericRequests.every(function(request) {
        return request.mode === 'cors' && request.url.indexOf('/wp-json/') === -1;
    }), '首页不可读时不得猜测 WordPress REST 地址');

    const wgzdyDirectHtml = await browser.detectSite('https://www.wgzdy.top/', {
        timeout: 1000,
        fetch: function(url) {
            if (url !== 'https://www.wgzdy.top/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<html><head><title>我鬼知道の博客</title><link rel="icon" href="/favicon.ico"></head>' +
                        '<body><div class="focusinfo"><div class="header-tou"><img alt="avatar" src="/wp-content/uploads/avater.webp"></div></div>' +
                        '<a href="/links/">友人帐</a></body></html>');
                }
            });
        }
    });
    assert.strictEqual(wgzdyDirectHtml.info.avatar, 'https://www.wgzdy.top/wp-content/uploads/avater.webp');
    assert.strictEqual(wgzdyDirectHtml.info.linksPage, 'https://www.wgzdy.top/links/');
    assert.strictEqual(wgzdyDirectHtml.info.source, 'html');

    const doraZhangDirectHtml = await browser.detectSite('https://www.dorazhang.cn/', {
        timeout: 1000,
        fetch: function(url) {
            if (url !== 'https://www.dorazhang.cn/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<html><head><title>ZyxBlog</title><link rel="icon" href="/favicon.ico"></head>' +
                        '<body><a class="navbar-brand"><img class="logo" data-lazy-src="/wp-content/uploads/2025/03/logo-m.png" alt="ZyxBlog"></a>' +
                        '<a href="/links">友情链接</a></body></html>');
                }
            });
        }
    });
    assert.strictEqual(doraZhangDirectHtml.info.avatar, 'https://www.dorazhang.cn/wp-content/uploads/2025/03/logo-m.png');
    assert.strictEqual(doraZhangDirectHtml.info.linksPage, 'https://www.dorazhang.cn/links');
    assert.strictEqual(doraZhangDirectHtml.info.source, 'html');

    const noInventedFavicon = await browser.detectSite('https://plain-static.dev/', {
        timeout: 1000,
        fetch: function(url) {
            if (url !== 'https://plain-static.dev/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() { return Promise.resolve('<html><head><title>Plain static</title></head><body>Hello</body></html>'); }
            });
        }
    });
    assert.strictEqual(noInventedFavicon.info.avatar, '');

    let oversizedHtmlRead = false;
    const oversizedHtml = await browser.detectSite('https://oversized-html.dev/', {
        timeout: 1000,
        fetch: function(url) {
            if (url !== 'https://oversized-html.dev/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                headers: { get: function(name) { return name === 'content-length' ? String(1024 * 1024 + 1) : ''; } },
                text: function() { oversizedHtmlRead = true; return Promise.resolve('<html></html>'); }
            });
        }
    });
    assert.strictEqual(oversizedHtml.success, false);
    assert.strictEqual(oversizedHtmlRead, false, '已声明超限的首页正文不应被缓冲');

    let oversizedWordPressRead = false;
    const oversizedWordPress = await browser.detectSite('https://oversized-wordpress.dev/', {
        timeout: 1000,
        fetch: function(url) {
            if (url === 'https://oversized-wordpress.dev/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() {
                        return Promise.resolve('<html><head><title>Oversized WordPress</title>' +
                            '<link rel="https://api.w.org/" href="/wp-json/"></head><body></body></html>');
                    }
                });
            }
            if (url === 'https://oversized-wordpress.dev/wp-json/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    headers: { get: function(name) { return name === 'content-length' ? String(256 * 1024 + 1) : ''; } },
                    json: function() { oversizedWordPressRead = true; return Promise.resolve({ name: 'Wrong' }); }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(oversizedWordPress.info.source, 'html');
    assert.strictEqual(oversizedWordPressRead, false, '已声明超限的 WordPress JSON 不应被缓冲');

    let oversizedBacklinkRead = false;
    const oversizedBacklink = await browser.checkBacklinkDetailed(
        'https://oversized-html.dev/links/',
        'https://blog.example.com/',
        {
            timeout: 1000,
            fetch: function(url) {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    headers: { get: function(name) { return name === 'content-length' ? String(1024 * 1024 + 1) : ''; } },
                    text: function() { oversizedBacklinkRead = true; return Promise.resolve('<html></html>'); }
                });
            }
        }
    );
    assert.strictEqual(oversizedBacklink.state, 'unknown');
    assert.strictEqual(oversizedBacklink.needsProxy, true);
    assert.strictEqual(oversizedBacklinkRead, false, '已声明超限的友链页正文不应被缓冲');

    const faviconCannotDegradeLogo = await browser.detectSite('https://favicon-priority.dev/', {
        timeout: 1000,
        fetch: function(url) {
            if (url !== 'https://favicon-priority.dev/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<html><head><title>Favicon priority</title><link rel="icon" href="/favicon.ico"></head>' +
                        '<body><header><img class="site-logo" src="/uploads/site-logo.png" width="512" height="512"></header></body></html>');
                }
            });
        }
    });
    assert.strictEqual(faviconCannotDegradeLogo.info.avatar, 'https://favicon-priority.dev/uploads/site-logo.png');

    const staticFriendPageDiscovery = await browser.detectSite('https://pages-only.dev/', {
        timeout: 1000,
        fetch: function(url) {
            if (url !== 'https://pages-only.dev/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<html><head><title>Static site</title></head><body><a href="/friends/">友情链接</a></body></html>');
                }
            });
        }
    });
    assert.strictEqual(staticFriendPageDiscovery.info.linksPage, 'https://pages-only.dev/friends/');
    assert.strictEqual(staticFriendPageDiscovery.readable, true);
    assert.strictEqual(staticFriendPageDiscovery.needsProxy, false);

    const candidateHomepageRequests = [];
    const candidateRace = await browser.detectSite('https://budget-race.dev/', {
        timeout: 1000,
        fetch: function(url) {
            candidateHomepageRequests.push(url);
            const parsed = new URL(url);
            if (parsed.host !== 'budget-race.dev') return Promise.reject(new TypeError('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<html><head><title>Budget race</title></head><body>' +
                        '<img class="site-avatar" src="/uploads/site-avatar.webp" width="512" height="512">' +
                        '<a href="/links/">Links</a></body></html>');
                }
            });
        }
    });
    assert.strictEqual(candidateRace.url, 'https://budget-race.dev/');
    assert.strictEqual(candidateRace.info.avatar, 'https://budget-race.dev/uploads/site-avatar.webp');
    assert.strictEqual(candidateRace.info.linksPage, 'https://budget-race.dev/links/');
    assert.ok(candidateHomepageRequests.every(function(url) {
        return new URL(url).pathname === '/' && url.indexOf('/wp-json/') === -1;
    }), '首页没有 REST discovery 时不得猜测框架接口');

    const separatedDescription = await browser.detectSite('separate.example', {
        timeout: 1000,
        fetch: function(url) {
            if (url === 'https://separate.example/') {
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() {
                        return Promise.resolve('<html><head><title>独立标题</title>' +
                            '<meta name="description" content="这是网站描述，不是副标题"></head><body>内容</body></html>');
                    }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(separatedDescription.info.subtitle, '');
    assert.strictEqual(separatedDescription.info.description, '这是网站描述，不是副标题');

    const structuredSiteName = browser.extractHtmlInfo(
        '<html><head><title>文章标题 – 正确站名</title>' +
        '<meta property="og:title" content="文章标题">' +
        '<script type="application/ld+json">{"@type":"WebSite","name":"正确站名"}</script>' +
        '</head><body></body></html>',
        'https://structured-name.dev/'
    );
    assert.strictEqual(structuredSiteName.siteName, '正确站名');

    const crossSiteInfoRedirect = await browser.detectSite('sample-blog.dev', {
        timeout: 1000,
        fetch: function(url) {
            if (url === 'https://sample-blog.dev/') {
                return Promise.resolve({
                    ok: true,
                    url: 'https://attacker.example/',
                    text: function() { return Promise.resolve('<html><head><title>Attacker</title></head><body>wrong</body></html>'); }
                });
            }
            return Promise.reject(new Error('unreachable'));
        }
    });
    assert.strictEqual(crossSiteInfoRedirect.success, false);
    assert.strictEqual(crossSiteInfoRedirect.needsProxy, true);
    assert.deepStrictEqual(crossSiteInfoRedirect.info, {});

    const opaque = await browser.detectSite('opaque.example', {
        timeout: 1000,
        fetch: function() {
            return Promise.reject(new TypeError('CORS blocked'));
        }
    });
    assert.strictEqual(opaque.success, false);
    assert.strictEqual(opaque.readable, false);
    assert.strictEqual(opaque.needsProxy, true);

    const challengePage = await browser.detectSite('challenge.example', {
        timeout: 1000,
        fetch: function(url) {
            if (url !== 'https://challenge.example/') return Promise.reject(new Error('unreachable'));
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() {
                    return Promise.resolve('<!doctype html><html><head><title>Just a moment...</title></head>' +
                        '<body><div class="cf-chl-widget">Checking your browser</div></body></html>');
                }
            });
        }
    });
    assert.strictEqual(challengePage.success, false);
    assert.strictEqual(challengePage.needsProxy, true);

    const stalledSiteSignals = [];
    const stalledSiteBody = await resolveWithin(browser.detectSite('stalled-body.example', {
        timeout: 500,
        fetch: function(url, options) {
            stalledSiteSignals.push(options && options.signal);
            return Promise.resolve({
                ok: true,
                url: url,
                text: function() { return new Promise(function() {}); }
            });
        }
    }), 1800, '站点响应体读取没有按时超时');
    assert.strictEqual(stalledSiteBody.success, false);
    assert.strictEqual(stalledSiteBody.needsProxy, true);
    assert.ok(stalledSiteBody.attempts.every(function(attempt) {
        return attempt.error === 'timeout';
    }), '响应头返回后卡住的首页正文应统一按 timeout 降级');
    assert.ok(stalledSiteSignals.length > 0 && stalledSiteSignals.every(function(signal) {
        return signal && signal.aborted;
    }), '首页正文超时后没有中止对应请求');

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

    const crossSiteRedirectBacklink = await browser.checkBacklink(
        'https://friend.example/links/',
        'https://sample-blog.dev/',
        {
            timeout: 1000,
            fetch: function() {
                return Promise.resolve({
                    ok: true,
                    url: 'https://redirected-attacker.example/links/',
                    text: function() { return Promise.resolve('<a href="https://sample-blog.dev/">伪造互链</a>'); }
                });
            }
        }
    );
    assert.strictEqual(crossSiteRedirectBacklink, null);

    const unknown = await browser.checkBacklink('https://friend.example/links/', 'https://sample-blog.dev/', {
        timeout: 1000,
        fetch: function() { return Promise.reject(new TypeError('CORS blocked')); }
    });
    assert.strictEqual(unknown, null);

    const redirectedDetailed = await browser.checkBacklinkDetailed(
        'https://friend.example/links/',
        'https://sample-blog.dev/',
        {
            timeout: 1000,
            fetch: function() {
                return Promise.resolve({
                    ok: true,
                    url: 'https://www.friend.example/friends/',
                    text: function() { return Promise.resolve('<html><body><a href="https://blog.sample-blog.dev/">Sample</a></body></html>'); }
                });
            }
        }
    );
    assert.strictEqual(redirectedDetailed.state, 'found');
    assert.strictEqual(redirectedDetailed.checkedUrl, 'https://www.friend.example/friends/');

    const apexParity = await browser.checkBacklinkDetailed(
        'https://friend.example/links/',
        'https://blog.cartol.top/',
        {
            timeout: 1000,
            fetch: function() {
                return Promise.resolve({
                    ok: true,
                    url: 'https://friend.example/links/',
                    text: function() { return Promise.resolve('<html><body><a href="https://cartol.top/about">Cartol</a></body></html>'); }
                });
            }
        }
    );
    assert.strictEqual(apexParity.state, 'found');

    const privateSuffixIsolation = await browser.checkBacklinkDetailed(
        'https://friend.example/links/',
        'https://foo.github.io/',
        {
            timeout: 1000,
            fetch: function() {
                return Promise.resolve({
                    ok: true,
                    url: 'https://friend.example/links/',
                    text: function() { return Promise.resolve('<html><body><a href="https://evil.github.io/">Evil</a></body></html>'); }
                });
            }
        }
    );
    assert.strictEqual(privateSuffixIsolation.state, 'missing');

    const challengeBacklink = await browser.checkBacklinkDetailed(
        'https://friend.example/links/',
        'https://sample-blog.dev/',
        {
            timeout: 1000,
            fetch: function() {
                return Promise.resolve({
                    ok: true,
                    url: 'https://friend.example/links/',
                    text: function() { return Promise.resolve('<html><head><title>Just a moment...</title></head><body class="cf-chl-widget">Checking your browser</body></html>'); }
                });
            }
        }
    );
    assert.strictEqual(challengeBacklink.state, 'unknown');
    assert.strictEqual(challengeBacklink.needsProxy, true);

    let stalledBacklinkSignal = null;
    const stalledBacklinkBody = await resolveWithin(browser.checkBacklinkDetailed(
        'https://friend.example/links/',
        'https://sample-blog.dev/',
        {
            timeout: 500,
            fetch: function(url, options) {
                stalledBacklinkSignal = options && options.signal;
                return Promise.resolve({
                    ok: true,
                    url: url,
                    text: function() { return new Promise(function() {}); }
                });
            }
        }
    ), 1800, '互链页响应体读取没有按时超时');
    assert.strictEqual(stalledBacklinkBody.state, 'unknown');
    assert.strictEqual(stalledBacklinkBody.needsProxy, true);
    assert.strictEqual(stalledBacklinkBody.error, 'timeout');
    assert.ok(stalledBacklinkSignal && stalledBacklinkSignal.aborted, '互链页正文超时后没有中止对应请求');

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
