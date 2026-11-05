'use strict';
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '..', 'argontheme.js'), 'utf8');
function deferred() {
    let status = 'pending', args;
    const callbacks = { done: [], fail: [], always: [] };
    const request = {};
    for (const name of Object.keys(callbacks)) {
        request[name] = function (callback) {
            callbacks[name].push(callback);
            if (status !== 'pending' && (name === status || name === 'always')) callback(...args);
            return request;
        };
    }
    function settle(next, values) {
        if (status !== 'pending') return;
        status = next; args = values;
        callbacks[next].forEach(fn => fn(...values));
        callbacks.always.forEach(fn => fn(...values));
    }
    request.resolve = (...values) => settle('done', values);
    request.reject = (...values) => settle('fail', values);
    request.abort = () => request.reject({ status: 0 }, 'abort');
    request.promise = () => request;
    return request;
}
function environment() {
    let transport, timerId = 0;
    const timers = new Map(), requests = [], navigations = [];
    const window = {
        location: { href: 'https://example.test/current', protocol: 'https:', hostname: 'example.test', port: '' },
        navigator: {}, innerHeight: 800,
        setTimeout(fn) { timers.set(++timerId, fn); return timerId; },
        clearTimeout(id) { timers.delete(id); }
    };
    const jq = function () { return { trigger() {} }; };
    jq.ajaxTransport = (_, factory) => { transport = factory; };
    jq.ajax = options => { const req = deferred(); requests.push({ options, req }); return req; };
    jq.Deferred = deferred;
    jq.extend = Object.assign;
    jq.Event = () => ({ isDefaultPrevented: () => false });
    jq.pjax = options => navigations.push(options);
    const context = { window, jQuery: jq, URL, Date, Object, document: {}, pjaxContainers: ['#primary'], pjaxLoading: true };
    const start = source.indexOf('var ArgonPjaxPrefetch = (function () {');
    const end = source.indexOf('\n})();', start) + 6;
    assert.ok(start >= 0 && end > start);
    vm.runInNewContext(source.slice(start, end), context);
    return { context, window, requests, navigations, timers, factory: options => transport(options) };
}
const response = { status: 200, statusText: 'OK', getAllResponseHeaders: () => 'Content-Type: text/html' };
function options(url = 'https://example.test/article') {
    return { url, type: 'GET', container: ['#primary'], fragment: ['#primary'] };
}
{
    const e = environment();
    const first = e.context.ArgonPjaxPrefetch.prefetch('/article');
    assert.strictEqual(first, e.context.ArgonPjaxPrefetch.prefetch('/article'));
    const transport = e.factory(options());
    let result;
    transport.send({}, (...args) => { result = args; });
    assert.strictEqual(e.requests.length, 1, 'click during hover must reuse the single GET');
    first.resolve('<main>fresh</main>', 'success', response);
    assert.strictEqual(result[2].text, '<main>fresh</main>');
    assert.ok(e.factory(options()), 'completed response is reusable');
    for (const extra of [{ push: false }, { replace: true }, { cache: false }, { type: 'POST' }, { container: ['#other'] }, { fragment: ['#other'] }]) {
        assert.strictEqual(e.factory(Object.assign(options(), extra)), undefined, 'refresh and incompatible requests must bypass cache');
    }
    assert.strictEqual(e.factory({ url: options().url, type: 'GET' }), undefined, 'ordinary HTML AJAX must bypass cache');
    e.window.location.href = options().url;
    assert.strictEqual(e.factory(options()), undefined, 'same-page refresh must fetch fresh content');
}
{
    const e = environment();
    assert.strictEqual(e.context.ArgonPjaxPrefetch.navigate('/cold', { target: {} }), true);
    assert.strictEqual(e.requests.length, 0, 'cold navigation must not start a speculative request');
    assert.strictEqual(e.navigations.length, 1);
}
{
    const e = environment();
    const request = e.context.ArgonPjaxPrefetch.prefetch('/article');
    const transport = e.factory(options());
    let completions = 0;
    transport.send({}, () => completions++);
    transport.abort();
    request.resolve('late', 'success', response);
    assert.strictEqual(completions, 0, 'aborted navigation cannot deliver stale content');
    assert.ok(e.factory(options()), 'aborting navigation must not cancel shared speculation');
    const cached = e.factory(options());
    cached.send({}, () => completions++);
    cached.abort();
    assert.strictEqual(e.timers.size, 0, 'cached delivery timer must be cancelled');
}
{
    const e = environment();
    const request = e.context.ArgonPjaxPrefetch.prefetch('/article');
    let result;
    e.factory(options()).send({ 'X-PJAX': 'true' }, (...args) => { result = args; });
    request.reject({ status: 0 }, 'timeout');
    assert.strictEqual(e.requests.length, 2, 'failed prefetch retries through real AJAX');
    const fallback = e.requests[1];
    assert.strictEqual(fallback.options.argonSkipPrefetch, true);
    assert.strictEqual(fallback.options.complete, null, 'fallback must not duplicate PJAX lifecycle callbacks');
    assert.strictEqual(e.factory(fallback.options), undefined);
    fallback.req.resolve('retry', 'success', response);
    assert.strictEqual(result[2].text, 'retry');
}
{
    const e = environment();
    for (const url of ['/current', '/?s=search', '/wp-admin/', '/wp-login.php', '/download/file', '/file.pdf', 'https://other.test/article']) {
        assert.strictEqual(e.context.ArgonPjaxPrefetch.prefetch(url), null, url);
    }
    e.window.navigator.connection = { saveData: true };
    assert.strictEqual(e.context.ArgonPjaxPrefetch.prefetch('/article'), null);
    e.window.navigator.connection = { effectiveType: 'slow-2g' };
    assert.strictEqual(e.context.ArgonPjaxPrefetch.prefetch('/article'), null);
    e.window.navigator.connection = {};
    e.context.ArgonPjaxPrefetch.prefetch('/one');
    e.context.ArgonPjaxPrefetch.prefetch('/two');
    assert.strictEqual(e.context.ArgonPjaxPrefetch.prefetch('/three'), null, 'speculation concurrency is bounded');
    e.requests[0].req.reject({ status: 0 }, 'error');
    assert.ok(e.context.ArgonPjaxPrefetch.prefetch('/three'), 'request slot released after failure');
}
{
    const timers = new Map(); let id = 0;
    const element = { classList: { contains: () => true, add() {}, remove() {} }, querySelector: () => ({ style: {} }), getAttribute: () => null };
    function image(top, lazy = false) {
        const listeners = new Map();
        return { complete: false, listeners,
            getAttribute: name => name === 'loading' && lazy ? 'lazy' : null,
            getBoundingClientRect: () => ({ top, bottom: top + 100, width: 100, height: 100 }),
            setAttribute() {}, addEventListener: (name, fn) => listeners.set(name, fn),
            removeEventListener: name => listeners.delete(name) };
    }
    const visible = image(10), below = image(1000), lazy = image(10, true);
    const root = { querySelectorAll: () => [visible, below, lazy] };
    const context = { document: { getElementById: () => element, querySelectorAll: () => [], documentElement: { classList: { contains: () => false } } },
        window: { innerHeight: 800 }, Date, Image: function () {},
        setTimeout: fn => { timers.set(++id, fn); return id; }, clearTimeout: key => timers.delete(key) };
    const start = source.indexOf('var PageLoader = (function () {');
    const end = source.indexOf('\n})();', start) + 6;
    vm.runInNewContext(source.slice(start, end), context);
    context.PageLoader.show(root);
    assert.strictEqual(visible.listeners.size, 2);
    assert.strictEqual(below.listeners.size, 0, 'below-fold eager image does not delay progress');
    assert.strictEqual(lazy.listeners.size, 0);
    context.PageLoader.trackResources({ querySelectorAll: () => [] });
    assert.strictEqual(visible.listeners.size, 0, 'retracking removes stale event listeners');
    assert.strictEqual(timers.size, 0, 'retracking removes stale resource timeout');
    context.PageLoader.hide();
    const hide = Array.from(timers.values())[0]; timers.clear(); hide();
    assert.strictEqual(timers.size, 1);
    context.PageLoader.show(root);
    assert.strictEqual(timers.size, 1, 'new navigation cancels previous fade timer');
    context.PageLoader.show({ querySelectorAll() { throw new Error('old page must not be scanned'); } }, false);
    assert.strictEqual(timers.size, 0, 'request progress clears old resource timeouts without scanning old content');
    assert.strictEqual(visible.listeners.size, 0, 'request progress removes old resource listeners');
    element.getAttribute = name => name === 'data-page-bg' ? 'https://images.test/random' : null;
    context.Image = function () { throw new Error('decorative background must not be fetched by the loader'); };
    context.PageLoader.show({ querySelectorAll: () => [] });
    assert.strictEqual(timers.size, 0, 'decorative background must not hold page progress');
    context.PageLoader.destroy();
    assert.strictEqual(timers.size, 0);
    assert.strictEqual(visible.listeners.size, 0);
}
{
    // Pending styles must still be tracked after DOMContentLoaded retracks resources.
    const listeners = new Map(); let timers = new Map(), id = 0;
    const style = { tagName: 'LINK', rel: 'stylesheet', sheet: null,
        getAttribute: () => 'true', setAttribute() {},
        addEventListener: (name, fn) => listeners.set(name, fn), removeEventListener: name => listeners.delete(name) };
    const element = { classList: { contains: () => true, add() {}, remove() {} }, querySelector: () => ({ style: {} }), getAttribute: () => null };
    const context = { document: { getElementById: () => element, querySelectorAll: () => [style], documentElement: { classList: { contains: () => false } } },
        window: {}, Date, Image: function () {},
        setTimeout: fn => { timers.set(++id, fn); return id; }, clearTimeout: key => timers.delete(key) };
    const start = source.indexOf('var PageLoader = (function () {');
    const end = source.indexOf('\n})();', start) + 6;
    vm.runInNewContext(source.slice(start, end), context);
    const root = { querySelectorAll: () => [] };
    context.PageLoader.show(root);
    context.PageLoader.trackResources(root);
    context.PageLoader.hide();
    assert.strictEqual(timers.size, 1, 'pending stylesheet retains only its resource timeout');
    assert.strictEqual(listeners.size, 2, 'retracking must keep waiting for a pending stylesheet');
    listeners.get('load')();
    assert.strictEqual(listeners.size, 0);
    assert.strictEqual(timers.size, 1, 'stylesheet completion releases hide timer');
    context.PageLoader.destroy();
    assert.strictEqual(timers.size, 0);
}
assert.ok(source.includes("document.addEventListener('DOMContentLoaded', function () {\n\t\tPageLoader.trackResources(document);"));
console.log('PJAX performance regression tests passed: request reuse, refresh bypass, abort, fallback, speculation limits, first-screen tracking and cleanup.');
