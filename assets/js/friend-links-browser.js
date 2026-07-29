(function(root, factory) {
    var api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.ArgonFriendLinkBrowser = api;
})(typeof window !== 'undefined' ? window : null, function() {
    'use strict';

    var MULTI_LEVEL_SUFFIXES = {
        'com.cn': true, 'net.cn': true, 'org.cn': true, 'gov.cn': true, 'edu.cn': true,
        'com.hk': true, 'com.tw': true, 'co.jp': true, 'co.kr': true, 'co.uk': true,
        'org.uk': true, 'com.au': true, 'net.au': true, 'co.nz': true
    };
    var HTML_RESPONSE_LIMIT = 1024 * 1024;
    var WORDPRESS_RESPONSE_LIMIT = 256 * 1024;
    function decodeNumericEntity(match, value, radix) {
        var codePoint = parseInt(value, radix);
        return codePoint >= 0 && codePoint <= 0x10FFFF ? String.fromCodePoint(codePoint) : match;
    }

    function cleanText(value) {
        return String(value || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/&nbsp;/gi, ' ')
            .replace(/&amp;/gi, '&')
            .replace(/&quot;/gi, '"')
            .replace(/&#39;|&apos;/gi, "'")
            .replace(/&ndash;/gi, '–')
            .replace(/&mdash;/gi, '—')
            .replace(/&#x([0-9a-f]+);/gi, function(match, value) { return decodeNumericEntity(match, value, 16); })
            .replace(/&#(\d+);/g, function(match, value) { return decodeNumericEntity(match, value, 10); })
            .replace(/\s+/g, ' ')
            .trim();
    }

    function parseInput(value) {
        var input = String(value || '').trim();
        if (!input) return null;
        if (/^[a-z][a-z\d+.-]*:/i.test(input) && !/^https?:\/\//i.test(input)) return null;
        if (input.indexOf('//') === 0) input = 'https:' + input;
        if (!/^https?:\/\//i.test(input)) input = 'https://' + input;
        try {
            var parsed = new URL(input);
            if (!/^https?:$/.test(parsed.protocol) || !parsed.hostname) return null;
            return parsed;
        } catch (error) {
            return null;
        }
    }

    function isIpAddress(hostname) {
        return /^\d{1,3}(?:\.\d{1,3}){3}$/.test(hostname) || hostname.indexOf(':') !== -1;
    }

    function normalizeHostname(value) {
        var parsed = parseInput(value);
        return parsed ? parsed.hostname.toLowerCase().replace(/^www\./, '').replace(/\.$/, '') : '';
    }

    function getRootDomain(hostname) {
        var host = String(hostname || '').toLowerCase().replace(/^www\./, '').replace(/\.$/, '');
        if (!host || host === 'localhost' || isIpAddress(host)) return host;
        var parts = host.split('.').filter(Boolean);
        if (parts.length <= 2) return host;
        var suffix = parts.slice(-2).join('.');
        return MULTI_LEVEL_SUFFIXES[suffix] && parts.length >= 3
            ? parts.slice(-3).join('.')
            : parts.slice(-2).join('.');
    }

    function getCandidateBaseDomain(hostname) {
        return String(hostname || '').toLowerCase().replace(/\.$/, '').replace(/^(?:www|blog)\./, '');
    }

    function isSameCandidateSiteUrl(left, right) {
        var leftUrl = parseInput(left);
        var rightUrl = parseInput(right);
        if (!leftUrl || !rightUrl) return false;
        return getCandidateBaseDomain(leftUrl.hostname) === getCandidateBaseDomain(rightUrl.hostname);
    }

    function buildCandidates(value) {
        var parsed = parseInput(value);
        if (!parsed) return [];
        var hostname = parsed.hostname.toLowerCase();
        var rootDomain = getCandidateBaseDomain(hostname);
        var hosts = [hostname];
        if (rootDomain && !isIpAddress(rootDomain) && rootDomain !== 'localhost') {
            hosts.push(rootDomain, 'www.' + rootDomain, 'blog.' + rootDomain);
        }
        var seen = {};
        return hosts.reduce(function(candidates, host) {
            var port = host === hostname && parsed.port ? ':' + parsed.port : '';
            var candidate = parsed.protocol + '//' + host + port + '/';
            var key = candidate.toLowerCase();
            if (!seen[key]) {
                seen[key] = true;
                candidates.push(candidate);
            }
            return candidates;
        }, []);
    }

    function stripSiteName(fullTitle, siteName) {
        var title = cleanText(fullTitle);
        var site = cleanText(siteName);
        if (!title || !site) return title;
        var escaped = site.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return title
            .replace(new RegExp('^' + escaped + '\\s*(?:[-–—|｜·•»:：]+)\\s*', 'i'), '')
            .replace(new RegExp('\\s*(?:[-–—|｜·•»:：]+)\\s*' + escaped + '$', 'i'), '')
            .trim();
    }

    function splitTitle(fullTitle, siteName) {
        var title = cleanText(fullTitle);
        var site = cleanText(siteName);
        if (!title && !site) return { primary: '', subtitle: '' };
        if (site) {
            var remainder = stripSiteName(title, site);
            if (remainder && remainder.toLowerCase() !== site.toLowerCase() && remainder.toLowerCase() !== title.toLowerCase()) {
                return { primary: site, subtitle: remainder };
            }
            if (!title || title.toLowerCase() === site.toLowerCase()) return { primary: site, subtitle: '' };
        }

        var parts = title.split(/\s+[-|]\s+|\s*[–—｜·•»]+\s*|\s*(?:::|：：)\s*/).map(cleanText).filter(Boolean);
        if (parts.length > 1) {
            return { primary: site || parts[0], subtitle: parts.slice(site ? 0 : 1).join(' – ') };
        }
        return { primary: site || title, subtitle: site && title !== site ? title : '' };
    }

    function absoluteUrl(value, baseUrl) {
        if (!value) return '';
        try {
            var parsed = new URL(value, baseUrl);
            return /^https?:$/.test(parsed.protocol) ? parsed.toString() : '';
        } catch (error) { return ''; }
    }

    function findMetaInHtml(html, key) {
        var tags = String(html || '').match(/<meta\b[^>]*>/gi) || [];
        for (var i = 0; i < tags.length; i++) {
            var tag = tags[i];
            var nameMatch = tag.match(/(?:name|property|itemprop)=["']([^"']+)["']/i);
            var contentMatch = tag.match(/content=["']([^"']*)["']/i);
            if (nameMatch && contentMatch && nameMatch[1].toLowerCase() === key.toLowerCase()) return cleanText(contentMatch[1]);
        }
        return '';
    }

    function findLinkInHtmlByRel(html, relName, baseUrl) {
        var tags = String(html || '').match(/<link\b[^>]*>/gi) || [];
        for (var i = 0; i < tags.length; i++) {
            var relMatch = tags[i].match(/\brel\s*=\s*["']([^"']+)["']/i);
            var hrefMatch = tags[i].match(/\bhref\s*=\s*["']([^"']+)["']/i);
            var rel = relMatch ? relMatch[1].toLowerCase().split(/\s+/) : [];
            if (hrefMatch && rel.indexOf(relName) !== -1) return absoluteUrl(hrefMatch[1], baseUrl);
        }
        return '';
    }

    function getHtmlTagAttribute(tag, attribute) {
        var escaped = attribute.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var match = String(tag || '').match(new RegExp('(?:^|\\s)' + escaped + '\\s*=\\s*(?:"([^"]*)"|\'([^\']*)\'|([^\\s>]+))', 'i'));
        return cleanText(match ? (match[1] || match[2] || match[3] || '') : '');
    }

    function findJsonLdSiteName(value) {
        if (!value || typeof value !== 'object') return '';
        if (Array.isArray(value)) {
            for (var i = 0; i < value.length; i++) {
                var arrayName = findJsonLdSiteName(value[i]);
                if (arrayName) return arrayName;
            }
            return '';
        }
        var typeValue = Array.isArray(value['@type']) ? value['@type'].join(' ') : value['@type'];
        if (/website|organization|blog|forum|onlinestore/i.test(cleanText(typeValue))) {
            var ownName = cleanText(value.name || value.alternateName || value.headline);
            if (ownName) return ownName;
        }
        var keys = Object.keys(value);
        for (var j = 0; j < keys.length; j++) {
            if (!value[keys[j]] || typeof value[keys[j]] !== 'object') continue;
            var childName = findJsonLdSiteName(value[keys[j]]);
            if (childName) return childName;
        }
        return '';
    }

    function findDeclaredSiteName(html) {
        var metaName = findMetaInHtml(html, 'og:site_name')
            || findMetaInHtml(html, 'application-name');
        if (metaName) return metaName;

        var jsonMatcher = /<script\b[^>]*type\s*=\s*["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi;
        var jsonMatch;
        while ((jsonMatch = jsonMatcher.exec(String(html || ''))) !== null) {
            try {
                var structuredName = findJsonLdSiteName(JSON.parse(jsonMatch[1]));
                if (structuredName) return structuredName;
            } catch (error) {}
        }

        var elementMatcher = /<(h1|a|div|span)\b([^>]*)>([\s\S]*?)<\/\1>/gi;
        var elementMatch;
        while ((elementMatch = elementMatcher.exec(String(html || ''))) !== null) {
            var identity = getHtmlTagAttribute('<' + elementMatch[1] + ' ' + elementMatch[2] + '>', 'class') + ' '
                + getHtmlTagAttribute('<' + elementMatch[1] + ' ' + elementMatch[2] + '>', 'id');
            if (!/(?:site|blog|forum)[-_ ]?(?:title|name)|(?:brand|navbar-brand)|(?:^|\s)logo(?:\s|$)/i.test(identity)) continue;
            var elementName = cleanText(elementMatch[3]);
            if (!elementName) {
                var imageTag = (elementMatch[3].match(/<img\b[^>]*>/i) || [])[0];
                elementName = imageTag ? getHtmlTagAttribute(imageTag, 'alt') : '';
            }
            if (elementName && elementName.length <= 120) return elementName;
        }
        return '';
    }

    function fallbackSiteName(url) {
        var parsed = parseInput(url);
        return parsed ? parsed.hostname.toLowerCase().replace(/^(?:www|blog)\./, '') : '';
    }

    function avatarUrlBonus(url) {
        var value = String(url || '').toLowerCase();
        var score = 0;
        if (/favicon\.ico(?:[?#]|$)/.test(value)) score -= 80;
        if (/site[-_]?logo|blog[-_]?logo|brand[-_]?logo|\/logo[._/-]/.test(value)) score += 35;
        if (/avatar|author|profile|headimg|portrait|userpic|user-pic/.test(value)) score += 30;
        if (/apple-touch|mask-icon|fluid-icon/.test(value)) score += 20;
        if (/(?:192|180|152|144|128|96|64)(?:x(?:192|180|152|144|128|96|64))?/.test(value)) score += 12;
        if (/\.svg(?:[?#]|$)/.test(value)) score += 8;
        return score;
    }

    function isGenericFaviconUrl(url) {
        return /\/favicon\.ico(?:[?#]|$)/i.test(String(url || ''));
    }

    function avatarTierFromScore(score) {
        if (score >= 380) return 5;
        if (score >= 280) return 4;
        if (score >= 190) return 3;
        if (score >= 150) return 2;
        if (score > 1) return 1;
        return 0;
    }

    function addAvatarCandidate(candidates, value, baseUrl, score) {
        var url = absoluteUrl(value, baseUrl);
        if (!url) return;
        var weightedScore = score + avatarUrlBonus(url);
        var tier = isGenericFaviconUrl(url) ? 0 : avatarTierFromScore(score);
        for (var i = 0; i < candidates.length; i++) {
            if (candidates[i].url === url) {
                candidates[i].score = Math.max(candidates[i].score, weightedScore);
                candidates[i].tier = Math.max(candidates[i].tier, tier);
                return;
            }
        }
        candidates.push({ url: url, score: weightedScore, tier: tier });
    }

    function addJsonLdAvatarCandidates(value, candidates, baseUrl, context) {
        if (!value || typeof value !== 'object') return;
        if (Array.isArray(value)) {
            value.forEach(function(item) { addJsonLdAvatarCandidates(item, candidates, baseUrl, context); });
            return;
        }
        var typeValue = Array.isArray(value['@type']) ? value['@type'].join(' ') : value['@type'];
        var type = cleanText(typeValue).toLowerCase();
        var nextContext = context || '';
        if (/website|organization|blog|publisher/.test(type)) nextContext = 'site';
        else if (/person|author/.test(type)) nextContext = 'author';

        function addImage(image, score) {
            if (typeof image === 'string') addAvatarCandidate(candidates, image, baseUrl, score);
            else if (image && typeof image === 'object') addAvatarCandidate(candidates, image.url || image.contentUrl || image['@id'], baseUrl, score);
        }
        if (nextContext === 'site') {
            addImage(value.logo, 300);
            addImage(value.image, 220);
        } else if (nextContext === 'author') {
            addImage(value.image || value.logo, 400);
        }
        Object.keys(value).forEach(function(key) {
            var child = value[key];
            if (!child || typeof child !== 'object') return;
            var lowerKey = key.toLowerCase();
            var childContext = /author|creator/.test(lowerKey)
                ? 'author'
                : (/publisher|provider|organization/.test(lowerKey) ? 'site' : nextContext);
            addJsonLdAvatarCandidates(child, candidates, baseUrl, childContext);
        });
    }

    function findBestAvatar(html, baseUrl) {
        var candidates = [];
        [
            ['author:image', 400], ['author:avatar', 400], ['profile:image', 390], ['profile:avatar', 390],
            ['og:logo', 300], ['logo', 295], ['application-logo', 290], ['msapplication-tileimage', 110],
            ['og:image', 220], ['og:image:secure_url', 220], ['twitter:image', 210], ['twitter:image:src', 210],
            ['image', 200], ['thumbnail', 190]
        ].forEach(function(item) { addAvatarCandidate(candidates, findMetaInHtml(html, item[0]), baseUrl, item[1]); });

        var jsonMatcher = /<script\b[^>]*type\s*=\s*["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi;
        var jsonMatch;
        while ((jsonMatch = jsonMatcher.exec(String(html || ''))) !== null) {
            try { addJsonLdAvatarCandidates(JSON.parse(jsonMatch[1]), candidates, baseUrl, ''); } catch (error) {}
        }

        var htmlText = String(html || '');
        var imageMatcher = /<img\b[^>]*>/gi;
        var imageMatch;
        while ((imageMatch = imageMatcher.exec(htmlText)) !== null) {
            var tag = imageMatch[0];
            var context = htmlText.slice(Math.max(0, imageMatch.index - 280), imageMatch.index + tag.length).toLowerCase();
            var srcset = getHtmlTagAttribute(tag, 'data-srcset') || getHtmlTagAttribute(tag, 'srcset');
            var source = getHtmlTagAttribute(tag, 'data-src')
                || getHtmlTagAttribute(tag, 'data-original')
                || getHtmlTagAttribute(tag, 'data-lazy-src')
                || getHtmlTagAttribute(tag, 'src')
                || (srcset ? srcset.split(',')[0].trim().split(/\s+/)[0] : '');
            var identity = [
                getHtmlTagAttribute(tag, 'class'), getHtmlTagAttribute(tag, 'id'), getHtmlTagAttribute(tag, 'alt'),
                getHtmlTagAttribute(tag, 'title'), getHtmlTagAttribute(tag, 'itemprop'), source
            ].join(' ').toLowerCase();
            if (/comment|reply|review|testimonial/.test(identity)) continue;
            var width = parseInt(getHtmlTagAttribute(tag, 'width'), 10) || 0;
            var height = parseInt(getHtmlTagAttribute(tag, 'height'), 10) || 0;
            var hasAvatarIdentity = /avatar|headimg|portrait|userpic|user-pic/.test(identity);
            var hasExplicitAuthorIdentity = /author|profile/.test(identity);
            var hasSiteProfileContext = /header-tou|focusinfo|site[-_ ]?author|author[-_ ]?card|profile[-_ ]?card|owner[-_ ]?avatar|about[-_ ]?author/.test(context);
            var hasListAvatarContext = /post[-_ ]?(?:list|meta|card)[-_ ]?avatar|comment[-_ ]?(?:author|avatar|item)|review[-_ ]?avatar|testimonial[-_ ]?avatar/.test(context);
            var hasLoginPlaceholderContext = /wp-login\.php|no-logged|login-link|header-user-avatar/.test(context)
                && (/topavatar|default[-_ ]?avatar|guest[-_ ]?avatar/.test(identity) || (width > 0 && width <= 32) || (height > 0 && height <= 32));
            var isTinyAvatar = hasAvatarIdentity && !hasSiteProfileContext
                && ((width > 0 && width <= 32) || (height > 0 && height <= 32));
            if (hasSiteProfileContext && (hasAvatarIdentity || hasExplicitAuthorIdentity)) {
                addAvatarCandidate(candidates, source, baseUrl, 400);
            } else if (hasListAvatarContext || hasLoginPlaceholderContext || isTinyAvatar) {
                addAvatarCandidate(candidates, source, baseUrl, 80);
            } else if (hasExplicitAuthorIdentity) {
                addAvatarCandidate(candidates, source, baseUrl, 400);
            } else if (hasAvatarIdentity) {
                addAvatarCandidate(candidates, source, baseUrl, 200);
            } else if (/site[-_ ]?logo|site[-_ ]?icon|brand[-_ ]?logo|blog[-_ ]?logo|header[-_ ]?logo|(?:^|[\/_. -])logo(?:[._\/ -]|$)/.test(identity)) {
                addAvatarCandidate(candidates, source, baseUrl, 300);
            } else if (source && !/emoji|sprite|tracking|pixel|spacer|advert|captcha|qrcode|qr-code/.test(identity)) {
                if (!((width && width <= 32) || (height && height <= 32))) {
                    addAvatarCandidate(candidates, source, baseUrl, 160 + (width >= 128 || height >= 128 ? 20 : 0));
                }
            }
        }

        (String(html || '').match(/<link\b[^>]*>/gi) || []).forEach(function(tag) {
            var rel = getHtmlTagAttribute(tag, 'rel').toLowerCase();
            var href = getHtmlTagAttribute(tag, 'href');
            var sizes = getHtmlTagAttribute(tag, 'sizes');
            var score = 0;
            if (/apple-touch-icon/.test(rel)) score = 68;
            else if (/mask-icon|fluid-icon/.test(rel)) score = 62;
            else if (/(?:^|\s)(?:shortcut\s+)?icon(?:\s|$)/.test(rel)) score = 52;
            if (score > 0) addAvatarCandidate(candidates, href, baseUrl, score + (/(?:128|144|152|180|192|256|512)/.test(sizes) ? 15 : 0));
        });
        candidates.sort(function(left, right) { return right.tier - left.tier || right.score - left.score; });
        return candidates.length ? candidates[0] : { url: '', score: 0, tier: 0 };
    }

    function friendLinksPageScore(url, label, baseUrl) {
        if (!url || !isSameCandidateSiteUrl(url, baseUrl)) return 0;
        var parsed = parseInput(url);
        if (!parsed) return 0;
        var path = (parsed.pathname || '/').toLowerCase();
        var text = cleanText(label).toLowerCase();
        var score = 0;
        if (/友情链接|友链|友人帐|友人账|朋友们|friend\s*links?|blogroll/.test(text)) score += 80;
        else if (/^(?:links?|friends?)$/.test(text)) score += 60;
        if (/(?:^|\/)(?:friends?|friend[-_]?links?|flinks?|links?|blogroll)(?:[\/.\-_]|$)/.test(path)) score += 50;
        return score;
    }

    function findFriendLinksPage(html, baseUrl) {
        var bestUrl = '';
        var bestScore = 0;
        function consider(href, label) {
            var url = absoluteUrl(href, baseUrl);
            var score = friendLinksPageScore(url, label, baseUrl);
            if (score > bestScore) {
                bestScore = score;
                bestUrl = url;
            }
        }

        if (typeof DOMParser !== 'undefined') {
            var doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
            Array.prototype.forEach.call(doc.querySelectorAll('a[href]'), function(anchor) {
                consider(anchor.getAttribute('href'), anchor.textContent || anchor.getAttribute('title') || '');
            });
            return bestUrl;
        }

        var matcher = /<a\b([^>]*)>([\s\S]*?)<\/a>/gi;
        var match;
        while ((match = matcher.exec(String(html || ''))) !== null) {
            var hrefMatch = match[1].match(/\bhref\s*=\s*["']([^"']+)["']/i);
            if (hrefMatch) consider(hrefMatch[1], match[2]);
        }
        return bestUrl;
    }

    function extractHtmlInfo(html, baseUrl) {
        var result = { title: '', siteName: '', description: '', avatar: '', avatarTier: 0, canonicalUrl: '', linksPage: '' };
        if (typeof DOMParser !== 'undefined') {
            var doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
            var meta = function(selector) {
                var element = doc.querySelector(selector);
                return element ? cleanText(element.getAttribute('content')) : '';
            };
            result.title = cleanText(doc.title || meta('meta[property="og:title"]'));
            result.siteName = meta('meta[property="og:site_name"]') || meta('meta[name="application-name"]') || findDeclaredSiteName(html);
            result.description = meta('meta[name="description"]') || meta('meta[property="og:description"]');
            var canonical = doc.querySelector('link[rel~="canonical"]');
            var domAvatar = findBestAvatar(html, baseUrl);
            result.avatar = domAvatar.url;
            result.avatarTier = domAvatar.tier;
            result.canonicalUrl = absoluteUrl(canonical ? canonical.getAttribute('href') : '', baseUrl);
            result.linksPage = findFriendLinksPage(html, baseUrl);
            return result;
        }

        var titleMatch = String(html || '').match(/<title\b[^>]*>([\s\S]*?)<\/title>/i);
        result.title = cleanText(titleMatch ? titleMatch[1] : findMetaInHtml(html, 'og:title'));
        result.siteName = findDeclaredSiteName(html);
        result.description = findMetaInHtml(html, 'description') || findMetaInHtml(html, 'og:description');
        var fallbackAvatar = findBestAvatar(html, baseUrl);
        result.avatar = fallbackAvatar.url;
        result.avatarTier = fallbackAvatar.tier;
        result.canonicalUrl = findLinkInHtmlByRel(html, 'canonical', baseUrl);
        result.linksPage = findFriendLinksPage(html, baseUrl);
        return result;
    }

    function findElementByAttribute(html, attribute, value) {
        var source = String(html || '');
        var matcher = /<([a-z][\w:-]*)\b[^>]*>/gi;
        var match;
        while ((match = matcher.exec(source)) !== null) {
            var openingTag = match[0];
            var attributeValue = getHtmlTagAttribute(openingTag, attribute);
            var matches = attribute === 'class'
                ? attributeValue.split(/\s+/).indexOf(value) !== -1
                : attributeValue === value;
            if (!matches) continue;
            var tail = source.slice(matcher.lastIndex);
            var closingMatch = tail.match(new RegExp('<\\/' + match[1] + '\\s*>', 'i'));
            var content = closingMatch ? tail.slice(0, closingMatch.index) : '';
            var text = cleanText(content) || cleanText(getHtmlTagAttribute(openingTag, 'data-text'));
            return { openingTag: openingTag, content: content, text: text };
        }
        return null;
    }

    function findImageUrlInElement(element, baseUrl) {
        if (!element) return '';
        var imageTag = (String(element.content || '').match(/<img\b[^>]*>/i) || [])[0];
        if (!imageTag) return '';
        var srcset = getHtmlTagAttribute(imageTag, 'data-srcset') || getHtmlTagAttribute(imageTag, 'srcset');
        return absoluteUrl(
            getHtmlTagAttribute(imageTag, 'data-src')
                || getHtmlTagAttribute(imageTag, 'data-original')
                || getHtmlTagAttribute(imageTag, 'data-lazy-src')
                || getHtmlTagAttribute(imageTag, 'src')
                || (srcset ? srcset.split(',')[0].trim().split(/\s+/)[0] : ''),
            baseUrl
        );
    }

    function cssBackgroundImageUrl(style, baseUrl) {
        var match = String(style || '').match(/background(?:-image)?\s*:\s*url\(\s*(["']?)(.*?)\1\s*\)/i);
        return absoluteUrl(match ? match[2] : '', baseUrl);
    }

    function isArgonHomepage(html) {
        return /(?:\bid=["']argon_pjax_state["']|\bvar\s+argonConfig\s*=|argon[_-]css[_-]merged|\/assets\/js\/argon(?:\.min)?\.js|\bname=["']argon-enable-)/i.test(String(html || ''));
    }

    function extractArgonInfo(html, baseUrl) {
        if (!isArgonHomepage(html)) return null;
        var bannerTitle = findElementByAttribute(html, 'class', 'banner-title-inner');
        var sidebarTitle = findElementByAttribute(html, 'class', 'leftbar-banner-title');
        var authorName = findElementByAttribute(html, 'id', 'leftbar_overview_author_name');
        var bannerSubtitle = findElementByAttribute(html, 'class', 'banner-subtitle');
        var sidebarSubtitle = findElementByAttribute(html, 'class', 'leftbar-banner-subtitle');
        var authorDescription = findElementByAttribute(html, 'id', 'leftbar_overview_author_description');
        var primary = cleanText(
            (bannerTitle && bannerTitle.text)
                || (sidebarTitle && sidebarTitle.text)
                || (authorName && authorName.text)
        );
        var subtitle = cleanText(
            (bannerSubtitle && bannerSubtitle.text)
                || (sidebarSubtitle && sidebarSubtitle.text)
        );
        var description = cleanText(authorDescription && authorDescription.text);
        var avatarCandidates = [];
        var overviewAvatar = findElementByAttribute(html, 'id', 'leftbar_overview_author_image');
        if (overviewAvatar) {
            addAvatarCandidate(
                avatarCandidates,
                cssBackgroundImageUrl(getHtmlTagAttribute(overviewAvatar.openingTag, 'style'), baseUrl),
                baseUrl,
                420
            );
        }
        addAvatarCandidate(
            avatarCandidates,
            findImageUrlInElement(findElementByAttribute(html, 'class', 'leftbar-mobile-avatar'), baseUrl),
            baseUrl,
            410
        );
        avatarCandidates.sort(function(left, right) { return right.tier - left.tier || right.score - left.score; });
        var avatar = avatarCandidates.length ? avatarCandidates[0] : { url: '', tier: 0 };
        return {
            primary: primary,
            subtitle: subtitle,
            description: description,
            avatar: avatar.url,
            avatarTier: avatar.tier,
            linksPage: '',
            nameTier: primary ? ((bannerTitle || sidebarTitle) ? 5 : 4) : 0,
            subtitleTier: subtitle ? 5 : 0,
            descriptionTier: description ? 5 : 0,
            linksPageTier: 0,
            source: 'argon'
        };
    }

    function findWordPressRestUrl(html, baseUrl) {
        var tags = String(html || '').match(/<link\b[^>]*>/gi) || [];
        for (var i = 0; i < tags.length; i++) {
            var rel = getHtmlTagAttribute(tags[i], 'rel').toLowerCase();
            var type = getHtmlTagAttribute(tags[i], 'type').toLowerCase();
            var href = getHtmlTagAttribute(tags[i], 'href');
            var isRestDiscovery = /(?:^|\s)https?:\/\/api\.w\.org\/?(?:\s|$)/.test(rel)
                || (/application\/json/.test(type) && /\/wp-json(?:\/|$)/i.test(href));
            if (!isRestDiscovery) continue;
            var restUrl = absoluteUrl(href, baseUrl);
            if (restUrl && isSameCandidateSiteUrl(baseUrl, restUrl)) return restUrl;
        }
        return '';
    }

    function mergeInfoCandidates(candidates) {
        var fields = {
            primary: { value: '', tier: -1, source: 'html' },
            subtitle: { value: '', tier: -1, source: 'html' },
            description: { value: '', tier: -1, source: 'html' },
            avatar: { value: '', tier: -1, source: 'html' },
            linksPage: { value: '', tier: -1, source: 'html' }
        };
        var tierFields = {
            primary: 'nameTier',
            subtitle: 'subtitleTier',
            description: 'descriptionTier',
            avatar: 'avatarTier',
            linksPage: 'linksPageTier'
        };
        candidates.forEach(function(candidate) {
            if (!candidate) return;
            Object.keys(fields).forEach(function(field) {
                var value = candidate[field] || '';
                if (!value) return;
                var tier = Math.max(0, Number(candidate[tierFields[field]]) || 0);
                if (!fields[field].value || tier > fields[field].tier) {
                    fields[field] = { value: value, tier: tier, source: candidate.source || 'html' };
                }
            });
        });

        var sourceScores = { html: 0, wordpress: 0, argon: 0 };
        var fieldWeights = { primary: 4, subtitle: 1, description: 2, avatar: 4, linksPage: 1 };
        Object.keys(fields).forEach(function(field) {
            var source = fields[field].source;
            if (Object.prototype.hasOwnProperty.call(sourceScores, source) && fields[field].value) {
                sourceScores[source] += fieldWeights[field];
            }
        });
        var source = 'html';
        ['wordpress', 'argon'].forEach(function(candidateSource) {
            if (sourceScores[candidateSource] > sourceScores[source]) source = candidateSource;
        });
        return {
            primary: fields.primary.value,
            subtitle: fields.subtitle.value,
            description: fields.description.value,
            avatar: fields.avatar.value,
            avatarTier: Math.max(0, fields.avatar.tier),
            linksPage: fields.linksPage.value,
            nameTier: Math.max(0, fields.primary.tier),
            subtitleTier: Math.max(0, fields.subtitle.tier),
            descriptionTier: Math.max(0, fields.description.tier),
            linksPageTier: Math.max(0, fields.linksPage.tier),
            source: source
        };
    }

    function needsWordPressFallback(info) {
        return !info
            || !info.primary
            || (Number(info.nameTier) || 0) <= 0
            || !info.description
            || !info.avatar
            || (Number(info.avatarTier) || 0) <= 0;
    }

    function mergedInfoScore(info) {
        return 40
            + (Number(info.nameTier) || 0) * 14
            + (Number(info.avatarTier) || 0) * 10
            + (info.description ? 10 : 0)
            + (info.subtitle ? 4 : 0)
            + (info.linksPage ? 8 : 0);
    }

    function fetchWithTimeout(fetchImpl, url, options, timeout, consumeResponse) {
        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var requestOptions = Object.assign({}, options || {});
        if (controller) requestOptions.signal = controller.signal;
        var timer;
        var timeoutPromise = new Promise(function(resolve, reject) {
            timer = setTimeout(function() {
                if (controller) controller.abort();
                reject(new Error('timeout'));
            }, timeout);
        });
        var requestPromise = Promise.resolve().then(function() {
            return fetchImpl(url, requestOptions);
        }).then(function(response) {
            if (typeof consumeResponse !== 'function') return response;
            return Promise.resolve(consumeResponse(response)).then(function(body) {
                return { response: response, body: body };
            });
        });
        return Promise.race([requestPromise, timeoutPromise]).then(function(response) {
            clearTimeout(timer);
            return response;
        }, function(error) {
            clearTimeout(timer);
            throw error;
        });
    }

    function declaredResponseLength(response) {
        if (!response || !response.headers || typeof response.headers.get !== 'function') return 0;
        var value = Number(response.headers.get('content-length') || 0);
        return isFinite(value) && value > 0 ? value : 0;
    }

    function textByteLength(text) {
        if (typeof TextEncoder !== 'undefined') return new TextEncoder().encode(String(text || '')).byteLength;
        return unescape(encodeURIComponent(String(text || ''))).length;
    }

    function readResponseTextLimited(response, maxBytes) {
        if (declaredResponseLength(response) > maxBytes) {
            var cancellation = response && response.body && typeof response.body.cancel === 'function'
                ? Promise.resolve(response.body.cancel('response_too_large')).catch(function() {})
                : Promise.resolve();
            return cancellation.then(function() { throw new Error('response_too_large'); });
        }
        if (response && response.body && typeof response.body.getReader === 'function' && typeof TextDecoder !== 'undefined') {
            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var received = 0;
            var text = '';
            var pump = function() {
                return reader.read().then(function(part) {
                    if (part.done) return text + decoder.decode();
                    received += part.value.byteLength;
                    if (received > maxBytes) throw new Error('response_too_large');
                    text += decoder.decode(part.value, { stream: true });
                    return pump();
                });
            };
            return pump().then(function(result) {
                reader.releaseLock();
                return result;
            }, function(error) {
                var cancellation = typeof reader.cancel === 'function'
                    ? Promise.resolve(reader.cancel(error && error.message ? error.message : 'read_failed')).catch(function() {})
                    : Promise.resolve();
                return cancellation.then(function() {
                    reader.releaseLock();
                    throw error;
                });
            });
        }
        if (!response || typeof response.text !== 'function') return Promise.reject(new Error('unreadable_response'));
        return Promise.resolve(response.text()).then(function(text) {
            if (textByteLength(text) > maxBytes) throw new Error('response_too_large');
            return text;
        });
    }

    function readResponseJsonLimited(response, maxBytes) {
        if (declaredResponseLength(response) > maxBytes) return Promise.reject(new Error('response_too_large'));
        if (response && (response.body || typeof response.text === 'function')) {
            return readResponseTextLimited(response, maxBytes).then(function(text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    throw new Error('invalid_wordpress_response');
                }
            });
        }
        if (!response || typeof response.json !== 'function') return Promise.reject(new Error('invalid_wordpress_response'));
        return Promise.resolve(response.json());
    }

    function inspectWordPressRest(restUrl, candidate, fetchImpl, timeout) {
        return fetchWithTimeout(fetchImpl, restUrl, {
            method: 'GET', mode: 'cors', credentials: 'omit', redirect: 'follow',
            cache: 'no-store', referrerPolicy: 'no-referrer',
            headers: { 'Accept': 'application/json' }
        }, timeout, function(response) {
            if (!response || !response.ok) throw new Error('invalid_wordpress_response');
            if (response.url && !isSameCandidateSiteUrl(candidate, response.url)) throw new Error('cross_site_redirect');
            return readResponseJsonLimited(response, WORDPRESS_RESPONSE_LIMIT);
        }).then(function(consumed) {
            var data = consumed.body;
            if (!data || typeof data !== 'object') throw new Error('invalid_wordpress_response');
            var name = cleanText(data.name);
            var description = cleanText(data.description);
            var avatar = absoluteUrl(data.site_icon_url || '', candidate);
            if (!name && !description && !avatar) throw new Error('invalid_wordpress_response');
            var names = splitTitle(name, '');
            return {
                primary: names.primary,
                subtitle: names.subtitle,
                description: description,
                avatar: avatar,
                avatarTier: avatar ? (isGenericFaviconUrl(avatar) ? 0 : 3) : 0,
                linksPage: '',
                nameTier: names.primary ? 3 : 0,
                subtitleTier: names.subtitle ? 3 : 0,
                descriptionTier: description ? 3 : 0,
                linksPageTier: 0,
                source: 'wordpress'
            };
        });
    }

    function isConfirmableHtmlPage(html) {
        var raw = String(html || '');
        var rawLower = raw.toLowerCase();
        var normalized = cleanText(raw).toLowerCase();
        var hasDocumentStructure = /(?:<!doctype\s+html|<html\b|<body\b|<a\b)/i.test(raw);
        var hasMeaningfulContent = Boolean(normalized)
            || /<img\b[^>]*(?:src|srcset|data-src|alt)\s*=/i.test(raw)
            || /<meta\b[^>]*content\s*=/i.test(raw);
        if (!hasDocumentStructure || !hasMeaningfulContent) return false;

        var hardChallenge = [
            'just a moment',
            'checking your browser',
            'attention required',
            'cloudflare ray id',
            'cf-chl-',
            'enable javascript and cookies'
        ].some(function(marker) {
            return normalized.indexOf(marker) !== -1 || rawLower.indexOf(marker) !== -1;
        });
        if (hardChallenge) return false;

        var titleMatch = raw.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i);
        var headingMatch = raw.match(/<h1\b[^>]*>([\s\S]*?)<\/h1>/i);
        var isVerificationHeading = function(value) {
            return /^(?:captcha|人机验证|访问验证|安全验证|请完成验证|验证码)(?:\s*[-–—|:：]\s*[^-–—|:：]{1,80})?$/i.test(cleanText(value));
        };
        return !isVerificationHeading(titleMatch ? titleMatch[1] : '')
            && !isVerificationHeading(headingMatch ? headingMatch[1] : '');
    }

    function inspectHtml(candidate, fetchImpl, timeout) {
        return fetchWithTimeout(fetchImpl, candidate, {
            method: 'GET', mode: 'cors', credentials: 'omit', redirect: 'follow',
            cache: 'no-store', referrerPolicy: 'no-referrer',
            headers: { 'Accept': 'text/html,application/xhtml+xml' }
        }, timeout, function(response) {
            if (!response || !response.ok) throw new Error('unreadable_html');
            return readResponseTextLimited(response, HTML_RESPONSE_LIMIT);
        }).then(function(consumed) {
            var response = consumed.response;
            var html = consumed.body;
            var responseUrl = response.url || candidate;
            if (!isSameCandidateSiteUrl(candidate, responseUrl)) throw new Error('cross_site_redirect');
            if (!isConfirmableHtmlPage(html)) throw new Error('challenge_or_invalid_html');
            var info = extractHtmlInfo(html, responseUrl);
            var names = splitTitle(info.title, info.siteName);
            var reportedUrl = info.canonicalUrl || responseUrl;
            return {
                success: true,
                readable: true,
                partial: false,
                score: names.primary ? 100 : 60,
                url: isSameCandidateSiteUrl(candidate, reportedUrl) ? reportedUrl : candidate,
                html: html,
                responseUrl: responseUrl,
                restUrl: findWordPressRestUrl(html, responseUrl),
                info: {
                    primary: names.primary || fallbackSiteName(responseUrl),
                    subtitle: names.subtitle,
                    description: info.description,
                    avatar: info.avatar,
                    avatarTier: info.avatarTier,
                    linksPage: info.linksPage,
                    nameTier: names.primary ? (info.siteName ? 4 : 2) : 0,
                    subtitleTier: names.subtitle ? 2 : 0,
                    descriptionTier: info.description ? 4 : 0,
                    linksPageTier: info.linksPage ? 4 : 0,
                    source: 'html'
                }
            };
        });
    }

    function inspectCandidate(candidate, fetchImpl, timeout) {
        return inspectHtml(candidate, fetchImpl, timeout).then(function(htmlResult) {
            var candidates = [htmlResult.info];
            var argonInfo = extractArgonInfo(htmlResult.html, htmlResult.responseUrl || candidate);
            if (argonInfo) candidates.push(argonInfo);
            var merged = mergeInfoCandidates(candidates);
            var finish = function() {
                htmlResult.info = merged;
                htmlResult.score = mergedInfoScore(merged);
                htmlResult.partial = needsWordPressFallback(merged);
                delete htmlResult.html;
                delete htmlResult.responseUrl;
                delete htmlResult.restUrl;
                return htmlResult;
            };
            if (!htmlResult.restUrl || !needsWordPressFallback(merged)) return finish();
            return inspectWordPressRest(htmlResult.restUrl, candidate, fetchImpl, timeout).then(function(wordpressInfo) {
                candidates.push(wordpressInfo);
                merged = mergeInfoCandidates(candidates);
                return finish();
            }).catch(function() {
                return finish();
            });
        }).catch(function(error) {
            return {
                success: false,
                readable: false,
                needsProxy: true,
                score: 0,
                url: candidate,
                error: error && error.message ? error.message : 'html_unreadable',
                info: {}
            };
        });
    }

    function detectSite(value, options) {
        var candidates = buildCandidates(value);
        var opts = options || {};
        var fetchImpl = opts.fetch || (typeof fetch === 'function' ? fetch.bind(typeof window !== 'undefined' ? window : null) : null);
        var timeout = Math.max(500, Number(opts.timeout) || 4500);
        if (!fetchImpl || !candidates.length) {
            return Promise.resolve({ success: false, needsProxy: Boolean(fetchImpl && candidates.length), candidates: candidates, info: {} });
        }

        return Promise.all(candidates.map(function(candidate, index) {
            return inspectCandidate(candidate, fetchImpl, timeout).then(function(result) {
                result.index = index;
                return result;
            });
        })).then(function(results) {
            var available = results.filter(function(result) { return result.success; });
            available.sort(function(a, b) { return b.score - a.score || a.index - b.index; });
            if (!available.length) {
                return { success: false, readable: false, needsProxy: true, candidates: candidates, attempts: results, info: {} };
            }
            var best = available[0];
            return {
                success: true,
                readable: true,
                partial: Boolean(best.partial),
                needsProxy: false,
                url: best.url,
                info: best.info,
                candidates: candidates,
                attempts: results
            };
        });
    }

    function extractLinks(html, baseUrl) {
        var links = [];
        if (typeof DOMParser !== 'undefined') {
            var doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
            Array.prototype.forEach.call(doc.querySelectorAll('a[href]'), function(anchor) {
                var href = absoluteUrl(anchor.getAttribute('href'), baseUrl);
                if (href) links.push(href);
            });
            return links;
        }

        var matcher = /<a\b[^>]*\bhref\s*=\s*["']([^"']+)["'][^>]*>/gi;
        var match;
        while ((match = matcher.exec(String(html || ''))) !== null) {
            var resolved = absoluteUrl(match[1], baseUrl);
            if (resolved) links.push(resolved);
        }
        return links;
    }

    function htmlContainsSite(html, baseUrl, localSiteUrl) {
        var hostname = normalizeHostname(localSiteUrl);
        var links = extractLinks(html, baseUrl);
        for (var i = 0; i < links.length; i++) {
            if (normalizeHostname(links[i]) === hostname || isSameCandidateSiteUrl(links[i], localSiteUrl)) return true;
        }
        return false;
    }

    function checkBacklinkDetailed(linksPage, localSiteUrl, options) {
        var page = parseInput(linksPage);
        var localHost = normalizeHostname(localSiteUrl);
        var opts = options || {};
        var fetchImpl = opts.fetch || (typeof fetch === 'function' ? fetch.bind(typeof window !== 'undefined' ? window : null) : null);
        var timeout = Math.max(500, Number(opts.timeout) || 6000);
        if (!page || !localHost || !fetchImpl) {
            return Promise.resolve({
                state: 'unknown',
                checkedUrl: '',
                needsProxy: false,
                error: 'invalid_backlink_request'
            });
        }

        return fetchWithTimeout(fetchImpl, page.toString(), {
            method: 'GET', mode: 'cors', credentials: 'omit', redirect: 'follow',
            cache: 'no-store', referrerPolicy: 'no-referrer',
            headers: { 'Accept': 'text/html,application/xhtml+xml' }
        }, timeout, function(response) {
            if (!response || !response.ok) throw new Error('unreadable_html');
            return readResponseTextLimited(response, HTML_RESPONSE_LIMIT);
        }).then(function(consumed) {
            var response = consumed.response;
            var html = consumed.body;
            var baseUrl = response.url || page.toString();
            if (!isSameCandidateSiteUrl(page.toString(), baseUrl)) {
                return { state: 'unknown', checkedUrl: '', needsProxy: false, error: 'cross_site_redirect' };
            }
            if (!isConfirmableHtmlPage(html)) throw new Error('challenge_or_invalid_html');
            return {
                state: htmlContainsSite(html, baseUrl, localSiteUrl) ? 'found' : 'missing',
                checkedUrl: baseUrl,
                needsProxy: false,
                error: ''
            };
        }).catch(function(error) {
            return {
                state: 'unknown',
                checkedUrl: page.toString(),
                needsProxy: true,
                error: error && error.message ? error.message : 'html_unreadable'
            };
        });
    }

    function checkBacklink(linksPage, localSiteUrl, options) {
        return checkBacklinkDetailed(linksPage, localSiteUrl, options).then(function(result) {
            return result.state === 'found' ? true : (result.state === 'missing' ? false : null);
        });
    }

    return {
        parseInput: parseInput,
        normalizeHostname: normalizeHostname,
        getRootDomain: getRootDomain,
        getCandidateBaseDomain: getCandidateBaseDomain,
        isSameCandidateSiteUrl: isSameCandidateSiteUrl,
        buildCandidates: buildCandidates,
        splitTitle: splitTitle,
        extractHtmlInfo: extractHtmlInfo,
        findFriendLinksPage: findFriendLinksPage,
        detectSite: detectSite,
        checkBacklinkDetailed: checkBacklinkDetailed,
        checkBacklink: checkBacklink
    };
});
