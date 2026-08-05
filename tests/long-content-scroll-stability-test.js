'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const themeScript = fs.readFileSync(path.join(__dirname, '..', 'argontheme.js'), 'utf8');
const helperMatch = themeScript.match(
	/function shouldUnmountVirtualSection\(sectionIndex, keepStart, keepEnd, scrollDirection\) \{([\s\S]*?)\n\}\n\nfunction createArgonLongContentVirtualizer/
);

assert.ok(
    helperMatch,
    '长文章虚拟化缺少按滚动方向裁剪分段的判定函数。'
);

const shouldUnmountVirtualSection = new Function(
    'sectionIndex',
    'keepStart',
    'keepEnd',
    'scrollDirection',
    helperMatch[1]
);

// 线上复现轨迹：用户向上滚动时，215-220 是刚挂载、即将进入视口的上方分段，
// 旧逻辑会把它们立即裁掉，浏览器滚动锚定随后把页面反向推回约 1000px。
const upwardMountedSections = Array.from({ length: 11 }, function(_, index) {
    return 215 + index;
});
assert.deepStrictEqual(
    upwardMountedSections.filter(function(index) {
        return shouldUnmountVirtualSection(index, 221, 225, 'up');
    }),
    [],
    '向上滚动时不应裁掉视口上方、即将进入视口的已挂载分段。'
);

// 向下滚动时，只回收已经越过的上方分段，不能回收前进方向上的分段。
const downwardMountedSections = Array.from({ length: 11 }, function(_, index) {
    return index;
});
assert.deepStrictEqual(
    downwardMountedSections.filter(function(index) {
        return shouldUnmountVirtualSection(index, 2, 6, 'down');
    }),
    [0, 1],
    '向下滚动时应只裁掉视口上方已经越过的分段。'
);

// 初始化或 resize 尚无可靠方向时，仍允许按窗口两侧正常裁剪。
assert.deepStrictEqual(
    [0, 1, 2, 3, 4, 5, 6, 7].filter(function(index) {
        return shouldUnmountVirtualSection(index, 2, 5, 'none');
    }),
    [0, 1, 6, 7],
    '没有滚动方向时应保留原来的双侧裁剪行为。'
);

assert.ok(
    /scrollDirection\s*=\s*getVirtualScrollDirection\(lastScrollTop, currentScrollTop\)/.test(themeScript),
    '虚拟化滚动监听没有在裁剪前更新滚动方向。'
);
assert.ok(
    /shouldUnmountVirtualSection\(i, keepStart, keepEnd, scrollDirection\)/.test(themeScript),
    '虚拟化裁剪流程没有使用方向判定。'
);

assert.ok(
    /function activateVirtualTailLock\(\)/.test(themeScript) &&
        /tailLockActive/.test(themeScript) &&
        /content\.getBoundingClientRect\(\)\.bottom <= window\.innerHeight \+ getVirtualTailBuffer\(\)/.test(themeScript),
    '评论区边界缺少尾部虚拟化锁定，正文末尾仍可能继续扩张或裁剪。'
);
assert.ok(
    /section\.tailLockedHeight = Math\.max\(/.test(themeScript) &&
        /document\.documentElement/.test(themeScript) &&
        /overflowAnchor = "none"/.test(themeScript),
    '尾部锁定没有固定分段高度并抑制滚动锚定反馈。'
);

console.log('长文章虚拟化滚动稳定性测试通过。');
