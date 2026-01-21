// ==========================================================================
// Argon 主题性能优化模块
// ==========================================================================
// 本模块提供系统性的性能优化功能，包括：
// - DOM 缓存系统
// - 事件节流和防抖
// - 资源按需加载
// - 渲染优化
// - 内存管理
// - 性能监控
// ==========================================================================

/**
 * 全局性能配置对象
 * 包含所有性能优化相关的配置参数
 */
const ArgonPerformanceConfig = {
	// 事件节流配置
	throttle: {
		scroll: 16,      // 滚动事件节流间隔（毫秒）
		resize: 16,      // resize 事件节流间隔（毫秒）
		mousemove: 16    // 鼠标移动事件节流间隔（毫秒）
	},
	
	// 事件防抖配置
	debounce: {
		resize: 150,     // resize 事件防抖延迟（毫秒）
		input: 300,      // 输入事件防抖延迟（毫秒）
		search: 500      // 搜索事件防抖延迟（毫秒）
	},
	
	// 资源加载配置
	lazyLoad: {
		prism: true,     // 按需加载 Prism
		zoomify: true,   // 按需加载 Zoomify
		tippy: true      // 按需加载 Tippy
	},
	
	// 缓存配置
	cache: {
		maxSize: 100,    // 最大缓存数量
		ttl: 300000      // 缓存过期时间（毫秒）
	},
	
	// 性能监控配置
	monitor: {
		enabled: false,  // 是否启用监控（默认关闭，可通过 argonConfig.debug_mode 启用）
		reportInterval: 60000  // 报告间隔（毫秒）
	}
};

// ==========================================================================
// DOM 缓存模块
// ==========================================================================

/**
 * DOM 缓存类
 * 缓存频繁访问的 DOM 元素，避免重复查询，提升性能
 * 
 * @class ArgonDOMCache
 */
class ArgonDOMCache {
	/**
	 * 构造函数
	 * 初始化缓存存储结构
	 */
	constructor() {
		this.cache = new Map();
		this.initialized = false;
	}
	
	/**
	 * 初始化缓存
	 * 缓存所有频繁访问的 DOM 元素
	 * 
	 * @returns {void}
	 */
	init() {
		this.cache.clear();
		
		// 缓存常用元素
		this.set('toolbar', document.querySelector('.navbar'));
		this.set('content', document.getElementById('content'));
		this.set('leftbar', document.getElementById('leftbar'));
		this.set('sidebar', document.getElementById('sidebar'));
		this.set('backToTopBtn', document.getElementById('fabtn_back_to_top'));
		this.set('readingProgress', document.getElementById('fabtn_reading_progress'));
		this.set('comments', document.getElementById('comments'));
		this.set('postComment', document.getElementById('post_comment'));
		
		this.initialized = true;
	}
	
	/**
	 * 获取缓存的元素
	 * 
	 * @param {string} key - 元素键名
	 * @returns {Element|null} DOM 元素或 null（如果不存在）
	 */
	get(key) {
		if (!this.cache.has(key)) {
			return null;
		}
		return this.cache.get(key);
	}
	
	/**
	 * 设置缓存
	 * 
	 * @param {string} key - 元素键名
	 * @param {Element|null} element - DOM 元素
	 * @returns {void}
	 */
	set(key, element) {
		this.cache.set(key, element);
	}
	
	/**
	 * 清空缓存
	 * 通常在 PJAX 页面切换时调用
	 * 
	 * @returns {void}
	 */
	clear() {
		this.cache.clear();
		this.initialized = false;
	}
}

// ==========================================================================
// 事件管理模块
// ==========================================================================

/**
 * 事件管理类
 * 提供事件节流、防抖和监听器生命周期管理功能
 * 
 * @class ArgonEventManager
 */
class ArgonEventManager {
	/**
	 * 构造函数
	 * 初始化监听器注册表
	 */
	constructor() {
		this.listeners = new Map();
	}
	
	/**
	 * 节流函数
	 * 限制函数执行频率，使用 requestAnimationFrame 优化性能
	 * 
	 * @param {Function} func - 要节流的函数
	 * @param {number} wait - 等待时间（毫秒），默认 16ms（约 60fps）
	 * @returns {Function} 节流后的函数
	 */
	throttle(func, wait = 16) {
		let lastTime = 0;
		let timeoutId = null;
		
		return function(...args) {
			const now = Date.now();
			const remaining = wait - (now - lastTime);
			
			if (remaining <= 0) {
				// 如果距离上次执行已超过等待时间，立即执行
				if (timeoutId) {
					cancelAnimationFrame(timeoutId);
					timeoutId = null;
				}
				lastTime = now;
				func.apply(this, args);
			} else if (!timeoutId) {
				// 否则使用 requestAnimationFrame 在下一帧执行
				timeoutId = requestAnimationFrame(() => {
					lastTime = Date.now();
					timeoutId = null;
					func.apply(this, args);
				});
			}
		};
	}
	
	/**
	 * 防抖函数
	 * 延迟执行函数，直到事件停止触发指定时间后才执行
	 * 如果在等待期间再次触发，则重新计时
	 * 
	 * @param {Function} func - 要防抖的函数
	 * @param {number} wait - 等待时间（毫秒），默认 150ms
	 * @returns {Function} 防抖后的函数，包含 cancel 方法用于取消待执行任务
	 */
	debounce(func, wait = 150) {
		let timeoutId = null;
		
		const debounced = function(...args) {
			// 取消之前的待执行任务
			if (timeoutId) {
				clearTimeout(timeoutId);
			}
			
			// 设置新的延迟执行任务
			timeoutId = setTimeout(() => {
				timeoutId = null;
				func.apply(this, args);
			}, wait);
		};
		
		// 提供取消方法，允许手动取消待执行任务
		debounced.cancel = function() {
			if (timeoutId) {
				clearTimeout(timeoutId);
				timeoutId = null;
			}
		};
		
		return debounced;
	}
	
	/**
	 * 添加事件监听器
	 * 自动跟踪监听器以便后续清理
	 * 
	 * @param {Element|Window|Document} element - DOM 元素、Window 或 Document 对象
	 * @param {string} event - 事件名称（如 'scroll', 'resize', 'click'）
	 * @param {Function} handler - 事件处理函数
	 * @param {Object} options - 事件监听器选项（如 { passive: true }）
	 * @returns {void}
	 */
	on(element, event, handler, options = {}) {
		if (!element) return;
		
		const key = this._getKey(element, event);
		element.addEventListener(event, handler, options);
		
		if (!this.listeners.has(key)) {
			this.listeners.set(key, []);
		}
		this.listeners.get(key).push({ element, handler, options });
	}
	
	/**
	 * 移除事件监听器
	 * 移除指定元素上的指定事件的所有监听器
	 * 
	 * @param {Element|Window|Document} element - DOM 元素、Window 或 Document 对象
	 * @param {string} event - 事件名称
	 * @returns {void}
	 */
	off(element, event) {
		if (!element) return;
		
		const key = this._getKey(element, event);
		const handlers = this.listeners.get(key);
		
		if (handlers) {
			handlers.forEach(({ handler, options }) => {
				element.removeEventListener(event, handler, options);
			});
			this.listeners.delete(key);
		}
	}
	
	/**
	 * 清除所有事件监听器
	 * 通常在 PJAX 页面切换或组件销毁时调用
	 * 
	 * @returns {void}
	 */
	clear() {
		this.listeners.forEach((handlers, key) => {
			// 从 key 中提取事件类型
			const eventType = key.split('_').pop();
			handlers.forEach(({ element, handler, options }) => {
				if (element) {
					try {
						element.removeEventListener(eventType, handler, options);
					} catch (e) {
						// 静默失败，元素可能已被移除
					}
				}
			});
		});
		this.listeners.clear();
	}
	
	/**
	 * 生成元素和事件的唯一键
	 * 
	 * @private
	 * @param {Element|Window|Document} element - DOM 元素
	 * @param {string} event - 事件名称
	 * @returns {string} 唯一键
	 */
	_getKey(element, event) {
		// 为元素生成唯一标识
		if (!element._argonEventId) {
			element._argonEventId = `elem_${Math.random().toString(36).substr(2, 9)}`;
		}
		return `${element._argonEventId}_${event}`;
	}
}

// ==========================================================================
// 资源加载模块
// ==========================================================================

/**
 * 资源加载类
 * 提供第三方库按需加载和缓存管理功能
 * 
 * @class ArgonResourceLoader
 */
class ArgonResourceLoader {
	/**
	 * 构造函数
	 * 初始化加载状态管理
	 */
	constructor() {
		this.loaded = new Set();      // 已加载的资源集合
		this.loading = new Map();     // 正在加载的资源 Promise Map
	}
	
	/**
	 * 按需加载脚本
	 * 如果资源已加载或正在加载，则复用缓存
	 * 
	 * @param {string} name - 资源名称（如 'prism', 'zoomify', 'tippy'）
	 * @param {string} url - 资源 URL
	 * @returns {Promise<void>} 加载完成的 Promise
	 */
	async loadScript(name, url) {
		// 如果已加载，直接返回
		if (this.loaded.has(name)) {
			return Promise.resolve();
		}
		
		// 如果正在加载，返回现有的 Promise
		if (this.loading.has(name)) {
			return this.loading.get(name);
		}
		
		// 创建新的加载 Promise
		const promise = new Promise((resolve, reject) => {
			const script = document.createElement('script');
			script.src = url;
			script.async = true;
			
			script.onload = () => {
				this.loaded.add(name);
				this.loading.delete(name);
				resolve();
			};
			
			script.onerror = () => {
				this.loading.delete(name);
				reject(new Error(`Failed to load ${name} from ${url}`));
			};
			
			document.head.appendChild(script);
		});
		
		this.loading.set(name, promise);
		return promise;
	}
	
	/**
	 * 检查页面是否需要某个库
	 * 通过 CSS 选择器判断页面中是否存在需要该库的元素
	 * 
	 * @param {string} selector - CSS 选择器
	 * @returns {boolean} 如果页面包含匹配的元素则返回 true
	 */
	needsLibrary(selector) {
		return document.querySelector(selector) !== null;
	}
	
	/**
	 * 条件加载 Prism 代码高亮库
	 * 仅在页面包含代码块时加载
	 * 
	 * @returns {Promise<void>}
	 */
	async loadPrismIfNeeded() {
		// 检查是否需要加载
		if (!this.needsLibrary('pre code')) {
			return;
		}
		
		// 检查是否已经存在
		if (typeof window.Prism !== 'undefined' && window.Prism.highlightAll) {
			this.loaded.add('prism');
			return;
		}
		
		// 加载 Prism
		if (typeof argonConfig !== 'undefined' && argonConfig.prism_url) {
			await this.loadScript('prism', argonConfig.prism_url);
		}
	}
	
	/**
	 * 条件加载 Zoomify 图片放大库
	 * 仅在页面包含图片时加载
	 * 
	 * @returns {Promise<void>}
	 */
	async loadZoomifyIfNeeded() {
		// 检查是否需要加载
		if (!this.needsLibrary('.post-content img')) {
			return;
		}
		
		// 检查是否已经存在
		if (typeof window.Zoomify !== 'undefined') {
			this.loaded.add('zoomify');
			return;
		}
		
		// 加载 Zoomify
		if (typeof argonConfig !== 'undefined' && argonConfig.zoomify_url) {
			await this.loadScript('zoomify', argonConfig.zoomify_url);
		}
	}
	
	/**
	 * 条件加载 Tippy 提示框库
	 * 仅在页面包含提示框元素时加载
	 * 
	 * @returns {Promise<void>}
	 */
	async loadTippyIfNeeded() {
		// 检查是否需要加载
		if (!this.needsLibrary('[data-tippy-content]')) {
			return;
		}
		
		// 检查是否已经存在
		if (typeof window.tippy !== 'undefined') {
			this.loaded.add('tippy');
			return;
		}
		
		// 加载 Tippy
		if (typeof argonConfig !== 'undefined' && argonConfig.tippy_url) {
			await this.loadScript('tippy', argonConfig.tippy_url);
		}
	}
}

// ==========================================================================
// 渲染优化模块
// ==========================================================================

/**
 * 渲染优化类
 * 批量读写 DOM 避免布局抖动，管理 GPU 加速属性
 * 
 * @class ArgonRenderOptimizer
 */
class ArgonRenderOptimizer {
	/**
	 * 构造函数
	 * 初始化读写队列和调度状态
	 */
	constructor() {
		this.readQueue = [];      // DOM 读取操作队列
		this.writeQueue = [];     // DOM 写入操作队列
		this.scheduled = false;   // 是否已调度执行
		this.activeAnimations = new Set();  // 当前活动的动画元素集合
		this.maxAnimations = 3;   // 最大同时运行的动画数量
		this.animationQueue = []; // 等待执行的动画队列
	}
	
	/**
	 * 批量读取 DOM 属性
	 * 将读取操作加入队列，在下一帧统一执行
	 * 
	 * @param {Function} readFn - 读取函数，应该只包含 DOM 读取操作
	 * @returns {void}
	 */
	read(readFn) {
		this.readQueue.push(readFn);
		this._schedule();
	}
	
	/**
	 * 批量写入 DOM
	 * 将写入操作加入队列，在所有读取操作完成后统一执行
	 * 
	 * @param {Function} writeFn - 写入函数，应该只包含 DOM 写入操作
	 * @returns {void}
	 */
	write(writeFn) {
		this.writeQueue.push(writeFn);
		this._schedule();
	}
	
	/**
	 * 调度执行
	 * 使用 requestAnimationFrame 在下一帧执行队列中的操作
	 * 
	 * @private
	 * @returns {void}
	 */
	_schedule() {
		if (this.scheduled) return;
		
		this.scheduled = true;
		requestAnimationFrame(() => {
			this._flush();
		});
	}
	
	/**
	 * 执行队列
	 * 先执行所有读取操作，再执行所有写入操作
	 * 这样可以避免布局抖动（layout thrashing）
	 * 
	 * @private
	 * @returns {void}
	 */
	_flush() {
		// 先执行所有读取操作
		while (this.readQueue.length) {
			const readFn = this.readQueue.shift();
			try {
				readFn();
			} catch (error) {
				console.error('Error in read operation:', error);
			}
		}
		
		// 再执行所有写入操作
		while (this.writeQueue.length) {
			const writeFn = this.writeQueue.shift();
			try {
				writeFn();
			} catch (error) {
				console.error('Error in write operation:', error);
			}
		}
		
		this.scheduled = false;
	}
	
	/**
	 * 添加 GPU 加速提示
	 * 设置 will-change 属性，提示浏览器创建合成层
	 * 
	 * @param {Element} element - DOM 元素
	 * @returns {void}
	 */
	enableGPU(element) {
		if (!element) return;
		element.style.willChange = 'transform, opacity';
	}
	
	/**
	 * 移除 GPU 加速提示
	 * 移除 will-change 属性，释放资源
	 * 
	 * @param {Element} element - DOM 元素
	 * @returns {void}
	 */
	disableGPU(element) {
		if (!element) return;
		element.style.willChange = 'auto';
	}
	
	/**
	 * 开始动画
	 * 如果当前活动动画数量未达到上限，立即启动动画并启用 GPU 加速
	 * 否则将动画加入等待队列
	 * 
	 * @param {Element} element - 要动画的 DOM 元素
	 * @param {Function} animationFn - 动画函数，接收 element 作为参数
	 * @returns {boolean} 如果动画立即启动返回 true，否则返回 false
	 */
	startAnimation(element, animationFn) {
		if (!element) return false;
		
		// 如果当前活动动画数量未达到上限，立即启动
		if (this.activeAnimations.size < this.maxAnimations) {
			this._executeAnimation(element, animationFn);
			return true;
		}
		
		// 否则加入等待队列
		this.animationQueue.push({ element, animationFn });
		return false;
	}
	
	/**
	 * 结束动画
	 * 移除 GPU 加速提示，从活动动画集合中移除元素
	 * 如果有等待的动画，启动队列中的下一个动画
	 * 
	 * @param {Element} element - 动画完成的 DOM 元素
	 * @returns {void}
	 */
	endAnimation(element) {
		if (!element) return;
		
		// 禁用 GPU 加速
		this.disableGPU(element);
		
		// 从活动动画集合中移除
		this.activeAnimations.delete(element);
		
		// 如果有等待的动画，启动下一个
		if (this.animationQueue.length > 0) {
			const { element: nextElement, animationFn } = this.animationQueue.shift();
			this._executeAnimation(nextElement, animationFn);
		}
	}
	
	/**
	 * 执行动画
	 * 启用 GPU 加速，将元素加入活动动画集合，执行动画函数
	 * 
	 * @private
	 * @param {Element} element - DOM 元素
	 * @param {Function} animationFn - 动画函数
	 * @returns {void}
	 */
	_executeAnimation(element, animationFn) {
		// 启用 GPU 加速
		this.enableGPU(element);
		
		// 加入活动动画集合
		this.activeAnimations.add(element);
		
		// 执行动画函数
		try {
			animationFn(element);
		} catch (error) {
			console.error('Error executing animation:', error);
			// 出错时也要清理
			this.endAnimation(element);
		}
	}
	
	/**
	 * 获取当前活动动画数量
	 * 
	 * @returns {number} 当前活动的动画数量
	 */
	getActiveAnimationCount() {
		return this.activeAnimations.size;
	}
	
	/**
	 * 获取等待队列中的动画数量
	 * 
	 * @returns {number} 等待队列中的动画数量
	 */
	getQueuedAnimationCount() {
		return this.animationQueue.length;
	}
	
	/**
	 * 清除所有动画
	 * 停止所有活动动画，清空等待队列
	 * 
	 * @returns {void}
	 */
	clearAllAnimations() {
		// 禁用所有活动动画的 GPU 加速
		this.activeAnimations.forEach(element => {
			this.disableGPU(element);
		});
		
		// 清空集合和队列
		this.activeAnimations.clear();
		this.animationQueue = [];
	}
}

// ==========================================================================
// 内存管理模块
// ==========================================================================

/**
 * 内存管理类
 * 跟踪和管理定时器、间隔定时器和动画帧，确保正确清理避免内存泄漏
 * 
 * @class ArgonMemoryManager
 */
class ArgonMemoryManager {
	/**
	 * 构造函数
	 * 初始化 ID 跟踪集合
	 */
	constructor() {
		this.timers = new Set();      // setTimeout ID 集合
		this.intervals = new Set();   // setInterval ID 集合
		this.frames = new Set();      // requestAnimationFrame ID 集合
	}
	
	/**
	 * 设置定时器并跟踪
	 * 包装原生 setTimeout，自动跟踪 timer ID
	 * 
	 * @param {Function} callback - 回调函数
	 * @param {number} delay - 延迟时间（毫秒）
	 * @param {...*} args - 传递给回调函数的参数
	 * @returns {number} timer ID，可用于手动清除
	 */
	setTimeout(callback, delay, ...args) {
		const id = setTimeout(() => {
			// 执行完成后自动从跟踪集合中移除
			this.timers.delete(id);
			callback(...args);
		}, delay);
		
		this.timers.add(id);
		return id;
	}
	
	/**
	 * 设置间隔定时器并跟踪
	 * 包装原生 setInterval，自动跟踪 interval ID
	 * 
	 * @param {Function} callback - 回调函数
	 * @param {number} interval - 间隔时间（毫秒）
	 * @param {...*} args - 传递给回调函数的参数
	 * @returns {number} interval ID，可用于手动清除
	 */
	setInterval(callback, interval, ...args) {
		const id = setInterval(() => {
			callback(...args);
		}, interval);
		
		this.intervals.add(id);
		return id;
	}
	
	/**
	 * 请求动画帧并跟踪
	 * 包装原生 requestAnimationFrame，自动跟踪 frame ID
	 * 
	 * @param {Function} callback - 回调函数
	 * @returns {number} frame ID，可用于手动取消
	 */
	requestAnimationFrame(callback) {
		const id = requestAnimationFrame((timestamp) => {
			// 执行完成后自动从跟踪集合中移除
			this.frames.delete(id);
			callback(timestamp);
		});
		
		this.frames.add(id);
		return id;
	}
	
	/**
	 * 清除定时器
	 * 包装原生 clearTimeout，同时从跟踪集合中移除
	 * 
	 * @param {number} id - timer ID
	 * @returns {void}
	 */
	clearTimeout(id) {
		clearTimeout(id);
		this.timers.delete(id);
	}
	
	/**
	 * 清除间隔定时器
	 * 包装原生 clearInterval，同时从跟踪集合中移除
	 * 
	 * @param {number} id - interval ID
	 * @returns {void}
	 */
	clearInterval(id) {
		clearInterval(id);
		this.intervals.delete(id);
	}
	
	/**
	 * 取消动画帧
	 * 包装原生 cancelAnimationFrame，同时从跟踪集合中移除
	 * 
	 * @param {number} id - frame ID
	 * @returns {void}
	 */
	cancelAnimationFrame(id) {
		cancelAnimationFrame(id);
		this.frames.delete(id);
	}
	
	/**
	 * 清理所有定时器和动画帧
	 * 通常在 PJAX 页面切换或组件销毁时调用
	 * 确保所有待执行的回调都被取消，避免内存泄漏
	 * 
	 * @returns {void}
	 */
	clearAll() {
		// 清除所有 setTimeout
		this.timers.forEach(id => {
			try {
				clearTimeout(id);
			} catch (e) {
				// 静默失败，ID 可能已失效
			}
		});
		
		// 清除所有 setInterval
		this.intervals.forEach(id => {
			try {
				clearInterval(id);
			} catch (e) {
				// 静默失败，ID 可能已失效
			}
		});
		
		// 取消所有 requestAnimationFrame
		this.frames.forEach(id => {
			try {
				cancelAnimationFrame(id);
			} catch (e) {
				// 静默失败，ID 可能已失效
			}
		});
		
		// 清空所有集合
		this.timers.clear();
		this.intervals.clear();
		this.frames.clear();
	}
	
	/**
	 * 获取当前跟踪的定时器数量
	 * 用于调试和性能监控
	 * 
	 * @returns {Object} 包含各类定时器数量的对象
	 */
	getStats() {
		return {
			timers: this.timers.size,
			intervals: this.intervals.size,
			frames: this.frames.size,
			total: this.timers.size + this.intervals.size + this.frames.size
		};
	}
}

// ==========================================================================
// 模块导出和初始化接口
// ==========================================================================

/**
 * 性能优化模块初始化函数
 * 在主题加载时调用，初始化所有优化模块
 */
function initArgonPerformance() {
	// 根据调试模式设置性能监控
	if (typeof argonConfig !== 'undefined' && argonConfig.debug_mode) {
		ArgonPerformanceConfig.monitor.enabled = true;
	}
	
	// 初始化各个模块的代码将在后续任务中添加
	console.info('Argon Performance Optimization Module Loaded');
}

// 导出配置对象和类供其他模块使用
if (typeof window !== 'undefined') {
	window.ArgonPerformanceConfig = ArgonPerformanceConfig;
	window.ArgonDOMCache = ArgonDOMCache;
	window.ArgonEventManager = ArgonEventManager;
	window.ArgonResourceLoader = ArgonResourceLoader;
	window.ArgonRenderOptimizer = ArgonRenderOptimizer;
	window.ArgonMemoryManager = ArgonMemoryManager;
	window.initArgonPerformance = initArgonPerformance;
}
