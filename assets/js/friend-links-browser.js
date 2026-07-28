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
        var tier = avatarTierFromScore(score);
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

        (String(html || '').match(/<img\b[^>]*>/gi) || []).forEach(function(tag) {
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
            if (/comment|reply|review|testimonial/.test(identity)) return;
            if (/avatar|author|profile|headimg|portrait|userpic|user-pic/.test(identity)) {
                addAvatarCandidate(candidates, source, baseUrl, 400);
            } else if (/site[-_ ]?logo|site[-_ ]?icon|brand[-_ ]?logo|blog[-_ ]?logo|header[-_ ]?logo|(?:^|[\/_. -])logo(?:[._\/ -]|$)/.test(identity)) {
                addAvatarCandidate(candidates, source, baseUrl, 300);
            } else if (source && !/emoji|sprite|tracking|pixel|spacer|advert|captcha|qrcode|qr-code/.test(identity)) {
                var width = parseInt(getHtmlTagAttribute(tag, 'width'), 10) || 0;
                var height = parseInt(getHtmlTagAttribute(tag, 'height'), 10) || 0;
                if (!((width && width <= 32) || (height && height <= 32))) {
                    addAvatarCandidate(candidates, source, baseUrl, 160 + (width >= 128 || height >= 128 ? 20 : 0));
                }
            }
        });

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
        addAvatarCandidate(candidates, '/favicon.ico', baseUrl, 1);
        candidates.sort(function(left, right) { return right.tier - left.tier || right.score - left.score; });
        return candidates.length ? candidates[0] : { url: '', score: 0, tier: 0 };
    }

    function preferAvatar(primary, primaryTier, secondary, secondaryTier) {
        if (!primary) return { url: secondary, tier: secondaryTier || 0 };
        if (!secondary) return { url: primary, tier: primaryTier || 0 };
        return (secondaryTier || 0) >= (primaryTier || 0)
            ? { url: secondary, tier: secondaryTier || 0 }
            : { url: primary, tier: primaryTier || 0 };
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

    function fetchWithTimeout(fetchImpl, url, options, timeout) {
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
        return Promise.race([fetchImpl(url, requestOptions), timeoutPromise]).then(function(response) {
            clearTimeout(timer);
            return response;
        }, function(error) {
            clearTimeout(timer);
            throw error;
        });
    }

    function inspectWordPress(candidate, fetchImpl, timeout) {
        var restUrl = absoluteUrl('wp-json/', candidate);
        return fetchWithTimeout(fetchImpl, restUrl, {
            method: 'GET', mode: 'cors', credentials: 'omit', redirect: 'follow',
            cache: 'no-store', referrerPolicy: 'no-referrer',
            headers: { 'Accept': 'application/json' }
        }, timeout).then(function(response) {
            if (!response || !response.ok) throw new Error('not_wordpress');
            return response.json();
        }).then(function(data) {
            if (!data || typeof data !== 'object' || !cleanText(data.name)) throw new Error('invalid_wordpress');
            var names = splitTitle(data.name, '');
            var description = cleanText(data.description);
            var reportedUrl = absoluteUrl(data.home || data.url || candidate, candidate) || candidate;
            var wordpressAvatar = absoluteUrl(data.site_icon_url || '', candidate);
            var wordpressResult = {
                success: true,
                readable: true,
                score: 120,
                url: isSameCandidateSiteUrl(candidate, reportedUrl) ? reportedUrl : candidate,
                info: {
                    primary: names.primary,
                    subtitle: names.subtitle,
                    description: description,
                    avatar: wordpressAvatar,
                    avatarTier: wordpressAvatar && !/favicon\.ico(?:[?#]|$)/i.test(wordpressAvatar) ? 3 : 0,
                    linksPage: '',
                    source: 'wordpress'
                }
            };
            return inspectHtml(wordpressResult.url, fetchImpl, timeout).then(function(htmlResult) {
                wordpressResult.info.description = wordpressResult.info.description || htmlResult.info.description;
                var preferredAvatar = preferAvatar(
                    wordpressResult.info.avatar,
                    wordpressResult.info.avatarTier,
                    htmlResult.info.avatar,
                    htmlResult.info.avatarTier
                );
                wordpressResult.info.avatar = preferredAvatar.url;
                wordpressResult.info.avatarTier = preferredAvatar.tier;
                wordpressResult.info.linksPage = htmlResult.info.linksPage || '';
                return wordpressResult;
            }).catch(function() {
                wordpressResult.info.avatar = wordpressResult.info.avatar || absoluteUrl('/favicon.ico', wordpressResult.url);
                if (!wordpressResult.info.avatarTier) wordpressResult.info.avatarTier = 0;
                return wordpressResult;
            });
        });
    }

    function inspectHtml(candidate, fetchImpl, timeout) {
        return fetchWithTimeout(fetchImpl, candidate, {
            method: 'GET', mode: 'cors', credentials: 'omit', redirect: 'follow',
            cache: 'no-store', referrerPolicy: 'no-referrer',
            headers: { 'Accept': 'text/html,application/xhtml+xml' }
        }, timeout).then(function(response) {
            if (!response || !response.ok) throw new Error('unreadable_html');
            return response.text().then(function(html) {
                var responseUrl = response.url || candidate;
                var info = extractHtmlInfo(html, responseUrl);
                var names = splitTitle(info.title, info.siteName);
                var reportedUrl = info.canonicalUrl || responseUrl;
                return {
                    success: true,
                    readable: true,
                    score: names.primary ? 100 : 60,
                    url: isSameCandidateSiteUrl(candidate, reportedUrl) ? reportedUrl : candidate,
                    info: {
                        primary: names.primary || fallbackSiteName(responseUrl),
                        subtitle: names.subtitle,
                        description: info.description,
                        avatar: info.avatar,
                        avatarTier: info.avatarTier,
                        linksPage: info.linksPage,
                        source: 'html'
                    }
                };
            });
        });
    }

    function probeOpaque(candidate, fetchImpl, timeout) {
        return fetchWithTimeout(fetchImpl, candidate, {
            method: 'GET', mode: 'no-cors', credentials: 'omit', redirect: 'follow',
            cache: 'no-store', referrerPolicy: 'no-referrer'
        }, timeout).then(function() {
            return {
                success: true,
                readable: false,
                score: 10,
                url: candidate,
                info: { primary: '', subtitle: '', description: '', avatar: '', linksPage: '', source: 'opaque' }
            };
        });
    }

    function inspectCandidate(candidate, fetchImpl, timeout) {
        return inspectWordPress(candidate, fetchImpl, timeout)
            .catch(function() { return inspectHtml(candidate, fetchImpl, timeout); })
            .catch(function() { return probeOpaque(candidate, fetchImpl, timeout); })
            .catch(function() { return { success: false, readable: false, score: 0, url: candidate, info: {} }; });
    }

    function detectSite(value, options) {
        var candidates = buildCandidates(value);
        var opts = options || {};
        var fetchImpl = opts.fetch || (typeof fetch === 'function' ? fetch.bind(typeof window !== 'undefined' ? window : null) : null);
        var timeout = Math.max(500, Number(opts.timeout) || 4500);
        if (!fetchImpl || !candidates.length) return Promise.resolve({ success: false, candidates: candidates, info: {} });

        return Promise.all(candidates.map(function(candidate, index) {
            return inspectCandidate(candidate, fetchImpl, timeout).then(function(result) {
                result.index = index;
                return result;
            });
        })).then(function(results) {
            var available = results.filter(function(result) { return result.success; });
            available.sort(function(a, b) { return b.score - a.score || a.index - b.index; });
            if (!available.length) return { success: false, candidates: candidates, attempts: results, info: {} };
            var best = available[0];
            return {
                success: true,
                readable: best.readable,
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

    function checkBacklink(linksPage, localSiteUrl, options) {
        var page = parseInput(linksPage);
        var localHost = normalizeHostname(localSiteUrl);
        var opts = options || {};
        var fetchImpl = opts.fetch || (typeof fetch === 'function' ? fetch.bind(typeof window !== 'undefined' ? window : null) : null);
        var timeout = Math.max(500, Number(opts.timeout) || 6000);
        if (!page || !localHost || !fetchImpl) return Promise.resolve(null);

        return fetchWithTimeout(fetchImpl, page.toString(), {
            method: 'GET', mode: 'cors', credentials: 'omit', redirect: 'follow',
            cache: 'no-store', referrerPolicy: 'no-referrer',
            headers: { 'Accept': 'text/html,application/xhtml+xml' }
        }, timeout).then(function(response) {
            if (!response || !response.ok) return null;
            return response.text().then(function(html) {
                var baseUrl = response.url || page.toString();
                var links = extractLinks(html, baseUrl);
                for (var i = 0; i < links.length; i++) {
                    if (normalizeHostname(links[i]) === localHost) return true;
                }
                return false;
            });
        }).catch(function() {
            return null;
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
        checkBacklink: checkBacklink
    };
});
