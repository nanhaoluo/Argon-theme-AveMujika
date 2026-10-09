'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const themeScript = read('argontheme.js');
const stylesheet = read('style.css');

// ===== 提取纯函数 computeLeftbarHeightAllocation 并求值（数值断言用） =====
function extractFunctionSource(source, functionName) {
	const startMarker = 'function ' + functionName + '(';
	const start = source.indexOf(startMarker);
	assert.notStrictEqual(start, -1, functionName + ' is missing from argontheme.js');
	const bodyStart = source.indexOf('{', start);
	assert.notStrictEqual(bodyStart, -1, functionName + ' body is missing');
	let depth = 0;
	for (let i = bodyStart; i < source.length; i++) {
		const ch = source[i];
		if (ch === '{') depth++;
		else if (ch === '}') {
			depth--;
			if (depth === 0) {
				return source.slice(start, i + 1);
			}
		}
	}
	throw new Error(functionName + ' body is not closed');
}

const allocSource = extractFunctionSource(themeScript, 'computeLeftbarHeightAllocation');
const computeLeftbarHeightAllocation = new Function(
	allocSource + '\nreturn computeLeftbarHeightAllocation;'
)();

const allocate = (budget, p2n, p3n) => computeLeftbarHeightAllocation({
	budget: budget,
	part2NaturalHeight: p2n,
	part3NaturalHeight: p3n,
	minPart2Height: 150,
	minPart3Height: 100
});

// ===== 数值断言：分配策略 =====

// 空间足够：两者都按自然高度，不钳制
{
	const r = allocate(680, 200, 300);
	assert.strictEqual(r.clamp, false, 'fits-budget case must not clamp');
	assert.strictEqual(r.part2Height, 200);
	assert.strictEqual(r.part3MaxHeight, 300);
}

// 两侧都长：目录不能把 TODO 挤到固定 100px，TODO 保留合理份额。
{
	const r = allocate(680, 800, 400);
	assert.strictEqual(r.clamp, true);
	assert.strictEqual(r.part3MaxHeight, 272, 'TODO keeps a usable share of the budget');
	assert.strictEqual(r.part2Height, 408);
}

// part2 自然放得下、part3 超长：part2 保持自然高度，part3 拿剩余
{
	const r = allocate(680, 250, 600);
	assert.strictEqual(r.part2Height, 250, 'part2 keeps natural height when it fits');
	assert.strictEqual(r.part3MaxHeight, 430);
}

// TODO 较短：保持自然高度，不能为了长目录人为缩到 100px。
{
	const r = allocate(680, 700, 120);
	assert.strictEqual(r.part3MaxHeight, 120);
	assert.strictEqual(r.part2Height, 560);
	assert.ok(r.part2Height + r.part3MaxHeight <= 680);
}

// TODO 内容低于保底：按自然高度，不被保底值撑大
{
	const r = allocate(680, 700, 50);
	assert.strictEqual(r.part3MaxHeight, 50);
	assert.strictEqual(r.part2Height, 630);
	assert.ok(r.part2Height + r.part3MaxHeight <= 680);
}

// 超矮视口（budget 小于两个保底之和）：最小高度钳制不允许突破预算
{
	const r = allocate(120, 800, 300);
	assert.ok(r.part2Height <= 120, 'part2 must not exceed a tiny budget');
	assert.strictEqual(r.part2Height, 72);
	assert.strictEqual(r.part3MaxHeight, 48, 'TODO must remain accessible in a tiny viewport');
	assert.ok(r.part2Height + r.part3MaxHeight <= 120);
}

// 没有 TODO 时，目录可以使用全部预算；短目录则给长 TODO 让出剩余。
assert.deepStrictEqual(allocate(680, 800, 0), { part2Height: 680, part3MaxHeight: 0, clamp: true });
assert.deepStrictEqual(allocate(680, 80, 900), { part2Height: 80, part3MaxHeight: 600, clamp: true });

// 不变量扫描：任意输入组合下 p2 <= budget 且 p2 + p3 <= budget
for (let budget = 0; budget <= 1200; budget += 37) {
	for (let p2n = 0; p2n <= 1500; p2n += 211) {
		for (let p3n = 0; p3n <= 1200; p3n += 173) {
			const r = allocate(budget, p2n, p3n);
			assert.ok(r.part2Height >= 0 && r.part3MaxHeight >= 0,
				`negative allocation at budget=${budget} p2n=${p2n} p3n=${p3n}`);
			assert.ok(r.part2Height <= budget,
				`part2 exceeds budget at budget=${budget} p2n=${p2n} p3n=${p3n}`);
			assert.ok(r.part2Height + r.part3MaxHeight <= budget,
				`allocation breaks budget at budget=${budget} p2n=${p2n} p3n=${p3n}`);
			if (p2n + p3n <= budget) {
				assert.strictEqual(r.clamp, false,
					`fits-budget input must not clamp at budget=${budget} p2n=${p2n} p3n=${p3n}`);
			}
		}
	}
}

// ===== 源码契约断言 =====

// 可用空间必须感知 Headroom 收起状态（收起后 sticky 卡片 top 80→10，预算随之放大）
assert.match(themeScript, /document\.body\.classList\.contains\('leftbar-can-headroom'\)\s*&&\s*document\.body\.classList\.contains\('headroom---unpinned'\)/,
	'topOffset is not aware of the headroom-unpinned state');
assert.match(themeScript, /topOffset = \(argonConfig\.headroom === 'absolute' \|\| headroomUnpinned\) \? 20 : 90/,
	'topOffset does not follow the real sticky top offset');

// 空间足够时输出 'none'（不钳制），避免取整误差造成目录假性滚动条
assert.match(themeScript, /setLeftbarRootProperty\('--leftbar-part2-inner-height', 'none'\)/,
	'fits-budget branch must release the part2 clamp');
assert.match(themeScript, /setLeftbarRootProperty\('--leftbar-part3-max-height', 'none'\)/,
	'fits-budget branch must release the part3 clamp');

// 旧的 60/40 预分配与无消费者的冗余变量已移除
assert.doesNotMatch(themeScript, /sharedHeight \* 0\.6/, 'obsolete 60/40 pre-split still exists');
assert.doesNotMatch(themeScript, /--leftbar-part3-height/, 'redundant --leftbar-part3-height output should be removed');

// 非目录标签页 sticky 时也按分配结果钳制并内部滚动（不再无上限生长顶飞 part3）
assert.match(stylesheet, /#leftbar_part2\.sticky:not\(\.leftbar-tab-catalog-active\) #leftbar_part2_inner\s*\{[^}]*max-height:\s*var\(--leftbar-part2-inner-height, none\)[^}]*overflow-y:\s*auto/s,
	'non-catalog sticky inner must be clamped by the allocation variable and scroll internally');

// 目录 tab 的既有钳制链路保持不变
assert.match(stylesheet, /#leftbar_part2\.sticky\.leftbar-tab-catalog-active #leftbar_tab_catalog\s*\{[^}]*max-height:\s*calc\(var\(--leftbar-part2-inner-height, 100vh\) - var\(--leftbar-tabs-height, 0px\) - 30px\)/s,
	'catalog pane clamp chain must stay intact');
assert.match(stylesheet, /#leftbar_part3\.sticky \.card-body\s*\{[^}]*max-height:\s*var\(--leftbar-part3-max-height, 50vh\)/s,
	'part3 card-body clamp must stay intact');

console.log('leftbar-height-allocation-test: all assertions passed');
