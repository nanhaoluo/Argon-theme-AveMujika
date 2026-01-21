/**
 * ArgonMemoryManager 单元测试
 * 测试内存管理模块的定时器和动画帧跟踪功能
 */

// 模拟浏览器环境
if (typeof window === 'undefined') {
	global.window = global;
	global.setTimeout = setTimeout;
	global.clearTimeout = clearTimeout;
	global.setInterval = setInterval;
	global.clearInterval = clearInterval;
	global.requestAnimationFrame = (callback) => setTimeout(callback, 16);
	global.cancelAnimationFrame = clearTimeout;
}

// 加载模块
require('./argon-performance.js');

const ArgonMemoryManager = window.ArgonMemoryManager;

describe('ArgonMemoryManager', () => {
	let memoryManager;
	
	beforeEach(() => {
		memoryManager = new ArgonMemoryManager();
	});
	
	afterEach(() => {
		// 清理所有定时器
		memoryManager.clearAll();
	});
	
	// Feature: resource-cpu-optimization, Example: setTimeout 跟踪
	test('should track setTimeout and auto-remove after execution', (done) => {
		let executed = false;
		
		const id = memoryManager.setTimeout(() => {
			executed = true;
		}, 10);
		
		// 验证 ID 被跟踪
		expect(memoryManager.timers.has(id)).toBe(true);
		expect(memoryManager.getStats().timers).toBe(1);
		
		// 等待执行完成
		setTimeout(() => {
			expect(executed).toBe(true);
			// 验证执行后自动移除
			expect(memoryManager.timers.has(id)).toBe(false);
			expect(memoryManager.getStats().timers).toBe(0);
			done();
		}, 50);
	});
	
	// Feature: resource-cpu-optimization, Example: setInterval 跟踪
	test('should track setInterval', (done) => {
		let count = 0;
		
		const id = memoryManager.setInterval(() => {
			count++;
		}, 10);
		
		// 验证 ID 被跟踪
		expect(memoryManager.intervals.has(id)).toBe(true);
		expect(memoryManager.getStats().intervals).toBe(1);
		
		// 等待执行几次
		setTimeout(() => {
			expect(count).toBeGreaterThan(0);
			// 手动清除
			memoryManager.clearInterval(id);
			expect(memoryManager.intervals.has(id)).toBe(false);
			expect(memoryManager.getStats().intervals).toBe(0);
			done();
		}, 50);
	});
	
	// Feature: resource-cpu-optimization, Example: requestAnimationFrame 跟踪
	test('should track requestAnimationFrame and auto-remove after execution', (done) => {
		let executed = false;
		
		const id = memoryManager.requestAnimationFrame(() => {
			executed = true;
		});
		
		// 验证 ID 被跟踪
		expect(memoryManager.frames.has(id)).toBe(true);
		expect(memoryManager.getStats().frames).toBe(1);
		
		// 等待执行完成
		setTimeout(() => {
			expect(executed).toBe(true);
			// 验证执行后自动移除
			expect(memoryManager.frames.has(id)).toBe(false);
			expect(memoryManager.getStats().frames).toBe(0);
			done();
		}, 50);
	});
	
	// Feature: resource-cpu-optimization, Example: clearTimeout 移除跟踪
	test('should remove timer from tracking when cleared', () => {
		const id = memoryManager.setTimeout(() => {}, 1000);
		
		expect(memoryManager.timers.has(id)).toBe(true);
		
		memoryManager.clearTimeout(id);
		
		expect(memoryManager.timers.has(id)).toBe(false);
	});
	
	// Feature: resource-cpu-optimization, Example: cancelAnimationFrame 移除跟踪
	test('should remove frame from tracking when cancelled', () => {
		const id = memoryManager.requestAnimationFrame(() => {});
		
		expect(memoryManager.frames.has(id)).toBe(true);
		
		memoryManager.cancelAnimationFrame(id);
		
		expect(memoryManager.frames.has(id)).toBe(false);
	});
	
	// Feature: resource-cpu-optimization, Example: clearAll 清理所有定时器
	test('should clear all timers, intervals and frames', (done) => {
		// 创建多个定时器
		memoryManager.setTimeout(() => {}, 1000);
		memoryManager.setTimeout(() => {}, 2000);
		memoryManager.setInterval(() => {}, 100);
		memoryManager.setInterval(() => {}, 200);
		memoryManager.requestAnimationFrame(() => {});
		memoryManager.requestAnimationFrame(() => {});
		
		const stats = memoryManager.getStats();
		expect(stats.timers).toBe(2);
		expect(stats.intervals).toBe(2);
		expect(stats.frames).toBe(2);
		expect(stats.total).toBe(6);
		
		// 清理所有
		memoryManager.clearAll();
		
		const statsAfter = memoryManager.getStats();
		expect(statsAfter.timers).toBe(0);
		expect(statsAfter.intervals).toBe(0);
		expect(statsAfter.frames).toBe(0);
		expect(statsAfter.total).toBe(0);
		
		done();
	});
	
	// Feature: resource-cpu-optimization, Example: 多个定时器跟踪
	test('should track multiple timers independently', () => {
		const id1 = memoryManager.setTimeout(() => {}, 1000);
		const id2 = memoryManager.setTimeout(() => {}, 2000);
		const id3 = memoryManager.setTimeout(() => {}, 3000);
		
		expect(memoryManager.getStats().timers).toBe(3);
		
		memoryManager.clearTimeout(id2);
		
		expect(memoryManager.getStats().timers).toBe(2);
		expect(memoryManager.timers.has(id1)).toBe(true);
		expect(memoryManager.timers.has(id2)).toBe(false);
		expect(memoryManager.timers.has(id3)).toBe(true);
	});
	
	// Feature: resource-cpu-optimization, Example: 传递参数给回调函数
	test('should pass arguments to setTimeout callback', (done) => {
		let receivedArgs = null;
		
		memoryManager.setTimeout((a, b, c) => {
			receivedArgs = [a, b, c];
		}, 10, 'arg1', 'arg2', 'arg3');
		
		setTimeout(() => {
			expect(receivedArgs).toEqual(['arg1', 'arg2', 'arg3']);
			done();
		}, 50);
	});
	
	// Feature: resource-cpu-optimization, Example: 传递参数给 setInterval 回调函数
	test('should pass arguments to setInterval callback', (done) => {
		let receivedArgs = null;
		
		const id = memoryManager.setInterval((a, b) => {
			receivedArgs = [a, b];
		}, 10, 'test1', 'test2');
		
		setTimeout(() => {
			expect(receivedArgs).toEqual(['test1', 'test2']);
			memoryManager.clearInterval(id);
			done();
		}, 50);
	});
	
	// Feature: resource-cpu-optimization, Example: getStats 返回正确的统计信息
	test('should return correct stats', () => {
		expect(memoryManager.getStats()).toEqual({
			timers: 0,
			intervals: 0,
			frames: 0,
			total: 0
		});
		
		memoryManager.setTimeout(() => {}, 1000);
		memoryManager.setInterval(() => {}, 1000);
		memoryManager.requestAnimationFrame(() => {});
		
		expect(memoryManager.getStats()).toEqual({
			timers: 1,
			intervals: 1,
			frames: 1,
			total: 3
		});
	});
});
