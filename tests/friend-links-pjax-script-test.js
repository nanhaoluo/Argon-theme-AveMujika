'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'argontheme.js'), 'utf8');
const friendLinksPage = fs.readFileSync(path.join(__dirname, '..', 'friend-links.php'), 'utf8');
const start = source.indexOf('function runArgonPjaxInitTasks');
const end = source.indexOf('\nfunction renderPjaxMath', start);
assert.ok(start >= 0 && end > start, 'PJAX 初始化任务调度器不存在');
assert.ok(
    source.indexOf('function () { return executePjaxScripts(pjaxContainers); }') >= 0,
    'PJAX 页面初始化没有使用可等待的脚本执行器'
);
assert.match(
    friendLinksPage,
    /friend-links-browser\.js\?ver=.*argon_get_asset_file_version\('assets\/js\/friend-links-browser\.js'\)/,
    '友情链接浏览器检测脚本缺少文件版本参数'
);

const context = {
    window: {
        performance: { now: function() { return Date.now(); } },
        setTimeout: setTimeout,
        requestAnimationFrame: function(callback) { return setTimeout(callback, 0); }
    },
    ArgonDebug: {
        error: function(error) { throw error; }
    },
    setTimeout: setTimeout,
    Date: Date,
    Promise: Promise,
    Math: Math
};
vm.runInNewContext(source.slice(start, end) + '\nthis.runArgonPjaxInitTasks = runArgonPjaxInitTasks;', context);

function run() {
    return new Promise(function(resolve, reject) {
        const order = [];
        context.runArgonPjaxInitTasks([
            function() {
                return new Promise(function(resolveTask) {
                    setTimeout(function() {
                        order.push('friend-links-browser.js');
                        resolveTask();
                    }, 20);
                });
            },
            function() {
                order.push('friend-links-inline-init');
            }
        ], function() {
            try {
                assert.deepStrictEqual(order, [
                    'friend-links-browser.js',
                    'friend-links-inline-init'
                ], 'PJAX 不应在浏览器检测模块加载完成前执行友情链接内联初始化');
                console.log('友情链接 PJAX 脚本顺序测试通过。');
                resolve();
            } catch (error) {
                reject(error);
            }
        });
    });
}

run().catch(function(error) {
    console.error(error.stack || error.message || error);
    process.exitCode = 1;
});
