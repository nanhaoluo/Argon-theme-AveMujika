/**
 * GPU 加速管理功能测试
 * 测试 ArgonRenderOptimizer 的 GPU 加速和动画限制功能
 */

// 模拟浏览器环境
global.window = global;
global.document = {
	createElement: () => ({
		style: {},
		addEventListener: () => {},
		appendChild: () => {}
	}),
	querySelector: () => null,
	getElementById: () => null,
	head: {
		appendChild: () => {}
	}
};

global.requestAnimationFrame = (callback) => {
	return setTimeout(callback, 16);
};

global.cancelAnimationFrame = (id) => {
	clearTimeout(id);
};

// 加载性能优化模块
require('./argon-performance.js');

const { ArgonRenderOptimizer } = global;

// 测试套件
console.log('='.repeat(60));
console.log('GPU 加速管理功能测试');
console.log('='.repeat(60));

// 测试 1: enableGPU() 方法
console.log('\n测试 1: enableGPU() 方法');
console.log('-'.repeat(60));

const renderOptimizer = new ArgonRenderOptimizer();
const testElement1 = { style: {} };

renderOptimizer.enableGPU(testElement1);

if (testElement1.style.willChange === 'transform, opacity') {
	console.log('✓ enableGPU() 正确设置 will-change 属性');
} else {
	console.error('✗ enableGPU() 未正确设置 will-change 属性');
	console.error(`  期望: "transform, opacity"`);
	console.error(`  实际: "${testElement1.style.willChange}"`);
	process.exit(1);
}

// 测试 null 元素
renderOptimizer.enableGPU(null);
console.log('✓ enableGPU() 正确处理 null 元素');

// 测试 2: disableGPU() 方法
console.log('\n测试 2: disableGPU() 方法');
console.log('-'.repeat(60));

const testElement2 = { style: { willChange: 'transform, opacity' } };

renderOptimizer.disableGPU(testElement2);

if (testElement2.style.willChange === 'auto') {
	console.log('✓ disableGPU() 正确移除 will-change 属性');
} else {
	console.error('✗ disableGPU() 未正确移除 will-change 属性');
	console.error(`  期望: "auto"`);
	console.error(`  实际: "${testElement2.style.willChange}"`);
	process.exit(1);
}

// 测试 null 元素
renderOptimizer.disableGPU(null);
console.log('✓ disableGPU() 正确处理 null 元素');

// 测试 3: 动画数量限制
console.log('\n测试 3: 动画数量限制（最多 3 个）');
console.log('-'.repeat(60));

const renderOptimizer2 = new ArgonRenderOptimizer();
const elements = [];
for (let i = 0; i < 5; i++) {
	elements.push({ style: {}, id: `element-${i}` });
}

// 启动 5 个动画
const results = [];
elements.forEach((element, index) => {
	const started = renderOptimizer2.startAnimation(element, (el) => {
		// 模拟动画
	});
	results.push(started);
});

// 验证前 3 个立即启动，后 2 个进入队列
const immediateStarts = results.filter(r => r === true).length;
const queued = results.filter(r => r === false).length;

if (immediateStarts === 3) {
	console.log(`✓ 前 3 个动画立即启动`);
} else {
	console.error(`✗ 立即启动的动画数量错误`);
	console.error(`  期望: 3`);
	console.error(`  实际: ${immediateStarts}`);
	process.exit(1);
}

if (queued === 2) {
	console.log(`✓ 后 2 个动画进入等待队列`);
} else {
	console.error(`✗ 等待队列的动画数量错误`);
	console.error(`  期望: 2`);
	console.error(`  实际: ${queued}`);
	process.exit(1);
}

// 验证活动动画数量
const activeCount = renderOptimizer2.getActiveAnimationCount();
if (activeCount === 3) {
	console.log(`✓ 活动动画数量: ${activeCount}`);
} else {
	console.error(`✗ 活动动画数量错误`);
	console.error(`  期望: 3`);
	console.error(`  实际: ${activeCount}`);
	process.exit(1);
}

// 验证等待队列数量
const queuedCount = renderOptimizer2.getQueuedAnimationCount();
if (queuedCount === 2) {
	console.log(`✓ 等待队列数量: ${queuedCount}`);
} else {
	console.error(`✗ 等待队列数量错误`);
	console.error(`  期望: 2`);
	console.error(`  实际: ${queuedCount}`);
	process.exit(1);
}

// 测试 4: 动画完成后自动启动队列中的下一个
console.log('\n测试 4: 动画队列自动管理');
console.log('-'.repeat(60));

// 结束第一个动画
renderOptimizer2.endAnimation(elements[0]);

// 验证活动动画数量仍为 3（队列中的一个自动启动）
const activeAfterEnd = renderOptimizer2.getActiveAnimationCount();
if (activeAfterEnd === 3) {
	console.log(`✓ 动画结束后，队列中的动画自动启动`);
	console.log(`  活动动画数量保持: ${activeAfterEnd}`);
} else {
	console.error(`✗ 动画队列自动管理失败`);
	console.error(`  期望活动动画数量: 3`);
	console.error(`  实际活动动画数量: ${activeAfterEnd}`);
	process.exit(1);
}

// 验证等待队列减少
const queuedAfterEnd = renderOptimizer2.getQueuedAnimationCount();
if (queuedAfterEnd === 1) {
	console.log(`✓ 等待队列数量减少: ${queuedAfterEnd}`);
} else {
	console.error(`✗ 等待队列数量错误`);
	console.error(`  期望: 1`);
	console.error(`  实际: ${queuedAfterEnd}`);
	process.exit(1);
}

// 测试 5: GPU 加速生命周期
console.log('\n测试 5: GPU 加速生命周期');
console.log('-'.repeat(60));

const renderOptimizer3 = new ArgonRenderOptimizer();
const animElement = { style: {} };

// 启动动画应该启用 GPU 加速
renderOptimizer3.startAnimation(animElement, (el) => {
	// 动画函数
});

if (animElement.style.willChange === 'transform, opacity') {
	console.log('✓ 动画启动时自动启用 GPU 加速');
} else {
	console.error('✗ 动画启动时未启用 GPU 加速');
	process.exit(1);
}

// 结束动画应该禁用 GPU 加速
renderOptimizer3.endAnimation(animElement);

if (animElement.style.willChange === 'auto') {
	console.log('✓ 动画结束时自动禁用 GPU 加速');
} else {
	console.error('✗ 动画结束时未禁用 GPU 加速');
	process.exit(1);
}

// 测试 6: clearAllAnimations()
console.log('\n测试 6: clearAllAnimations() 方法');
console.log('-'.repeat(60));

const renderOptimizer4 = new ArgonRenderOptimizer();
const clearElements = [];
for (let i = 0; i < 5; i++) {
	const el = { style: {} };
	clearElements.push(el);
	renderOptimizer4.startAnimation(el, () => {});
}

// 清除所有动画
renderOptimizer4.clearAllAnimations();

// 验证所有动画都被清除
const activeAfterClear = renderOptimizer4.getActiveAnimationCount();
const queuedAfterClear = renderOptimizer4.getQueuedAnimationCount();

if (activeAfterClear === 0) {
	console.log('✓ 所有活动动画已清除');
} else {
	console.error(`✗ 活动动画未完全清除，剩余: ${activeAfterClear}`);
	process.exit(1);
}

if (queuedAfterClear === 0) {
	console.log('✓ 等待队列已清空');
} else {
	console.error(`✗ 等待队列未完全清空，剩余: ${queuedAfterClear}`);
	process.exit(1);
}

// 验证所有元素的 GPU 加速都被禁用
const allDisabled = clearElements.every(el => el.style.willChange === 'auto');
if (allDisabled) {
	console.log('✓ 所有元素的 GPU 加速已禁用');
} else {
	console.error('✗ 部分元素的 GPU 加速未禁用');
	process.exit(1);
}

// 测试 7: 错误处理
console.log('\n测试 7: 错误处理');
console.log('-'.repeat(60));

const renderOptimizer5 = new ArgonRenderOptimizer();
const errorElement = { style: {} };

// 测试动画函数抛出错误
let errorCaught = false;
renderOptimizer5.startAnimation(errorElement, (el) => {
	throw new Error('Test error');
});

// 验证错误不会导致程序崩溃，且动画被正确清理
setTimeout(() => {
	const activeAfterError = renderOptimizer5.getActiveAnimationCount();
	if (activeAfterError === 0) {
		console.log('✓ 动画函数错误被正确处理，动画已清理');
	} else {
		console.error('✗ 动画函数错误处理失败');
		process.exit(1);
	}
	
	if (errorElement.style.willChange === 'auto') {
		console.log('✓ 错误后 GPU 加速已禁用');
	} else {
		console.error('✗ 错误后 GPU 加速未禁用');
		process.exit(1);
	}
	
	// 所有测试通过
	console.log('\n' + '='.repeat(60));
	console.log('✓ 所有测试通过！');
	console.log('='.repeat(60));
	console.log('\n测试摘要:');
	console.log('  ✓ enableGPU() 方法正常工作');
	console.log('  ✓ disableGPU() 方法正常工作');
	console.log('  ✓ 动画数量限制为 3 个');
	console.log('  ✓ 动画队列自动管理');
	console.log('  ✓ GPU 加速生命周期管理');
	console.log('  ✓ clearAllAnimations() 方法正常工作');
	console.log('  ✓ 错误处理机制正常');
	console.log('\n需求验证:');
	console.log('  ✓ 需求 5.2: 动画时使用 will-change 提示浏览器创建合成层');
	console.log('  ✓ 需求 5.3: 动画完成时移除 will-change 属性释放资源');
	console.log('  ✓ 需求 5.5: 限制同时运行的动画数量不超过 3 个');
	
	process.exit(0);
}, 100);
