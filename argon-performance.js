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
 * 自定义调试控制台
 * 输出日志到管理员调试控制台（如果启用）或浏览器控制台
 * 为不同模块添加标识前缀
 */
const ArgonDebug = {
	enabled: false,
	useAdminConsole: false,
	
	init() {
		if (typeof argonConfig !== 'undefined' && argonConfig.debug_mode) {
			this.enabled = true;
		}
		// 检查是否有管理员调试控制台
		if (typeof window.argonDebug !== 'undefined' && typeof window.argonDebug.log === 'function') {
			this.useAdminConsole = true;
		}
	},
	
	/**
	 * 输出日志到管理员控制台或浏览器控制台
	 * @param {string} level - 日志级别 (log/warn/error/info)
	 * @param {string} module - 模块名称
	 * @param {...*} args - 日志内容
	 */
	_output(level, module, ...args) {
		if (!this.enabled) return;
		
		const prefix = module ? `[${module}]` : '[Argon]';
		const message = args.map(arg => {
			if (typeof arg === 'object') {
				try {
					return JSON.stringify(arg, null, 2);
				} catch (e) {
					return String(arg);
				}
			}
			return String(arg);
		}).join(' ');
		
		// 如果有管理员控制台，优先使用
		if (this.useAdminConsole && window.argonDebug) {
			// 管理员控制台会自动处理日志级别和格式
			console[level](`${prefix} ${message}`);
		} else {
			// 否则输出到浏览器控制台
			console[level](`${prefix} ${message}`);
		}
	},
	
	log(module, ...args) {
		this._output('log', module, ...args);
	},
	
	warn(module, ...args) {
		this._output('warn', module, ...args);
	},
	
	error(module, ...args) {
		this._output('error', module, ...args);
	},
	
	info(module, ...args) {
		this._output('info', module, ...args);
	},
	
	group(label) {
		if (this.enabled) console.group(label);
	},
	
	groupEnd() {
		if (this.enabled) console.groupEnd();
	},
	
	table(data) {
		if (this.enabled) console.table(data);
	}
};

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
 * 支持 LRU（最近最少使用）缓存淘汰策略
 * 
 * @class ArgonDOMCache
 */
class ArgonDOMCache {
	/**
	 * 构造函数
	 * 初始化缓存存储结构
	 * 
	 * @param {number} maxSize - 最大缓存数量，默认从配置读取
	 */
	constructor(maxSize = null) {
		this.cache = new Map();
		this.initialized = false;
		this.performanceMonitor = null;  // 性能监控器引用
		
		// LRU 缓存配置
		this.maxSize = maxSize || (ArgonPerformanceConfig && ArgonPerformanceConfig.cache ? ArgonPerformanceConfig.cache.maxSize : 100);
		this.accessTimes = new Map();  // 记录每个缓存项的最后访问时间
		this.accessOrder = [];  // 记录访问顺序，用于 LRU 淘汰
	}
	
	/**
	 * 设置性能监控器
	 * 允许 DOM 缓存向性能监控器报告查询次数
	 * 
	 * @param {ArgonPerformanceMonitor} monitor - 性能监控器实例
	 * @returns {void}
	 */
	setPerformanceMonitor(monitor) {
		this.performanceMonitor = monitor;
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
	 * 自动更新访问时间，用于 LRU 淘汰策略
	 * 
	 * @param {string} key - 元素键名
	 * @returns {Element|null} DOM 元素或 null（如果不存在）
	 */
	get(key) {
		if (!this.cache.has(key)) {
			return null;
		}
		
		// 更新访问时间
		this._updateAccessTime(key);
		
		return this.cache.get(key);
	}
	
	/**
	 * 设置缓存
	 * 如果缓存已满，使用 LRU 策略淘汰最少使用的项
	 * 
	 * @param {string} key - 元素键名
	 * @param {Element|null} element - DOM 元素
	 * @returns {void}
	 */
	set(key, element) {
		// 如果键已存在，先删除旧的访问记录
		if (this.cache.has(key)) {
			this._removeFromAccessOrder(key);
		} else {
			// 如果是新键且缓存已满，执行 LRU 淘汰
			if (this.cache.size >= this.maxSize) {
				this._evictLRU();
			}
		}
		
		// 设置缓存
		this.cache.set(key, element);
		
		// 更新访问时间和顺序
		this._updateAccessTime(key);
	}
	
	/**
	 * 清空缓存
	 * 通常在 PJAX 页面切换时调用
	 * 
	 * @returns {void}
	 */
	clear() {
		this.cache.clear();
		this.accessTimes.clear();
		this.accessOrder = [];
		this.initialized = false;
	}
	
	/**
	 * 查询 DOM 元素（带性能跟踪）
	 * 包装原生 querySelector，自动跟踪查询次数
	 * 
	 * @param {string} selector - CSS 选择器
	 * @param {Element|Document} context - 查询上下文，默认为 document
	 * @returns {Element|null} 匹配的元素或 null
	 */
	query(selector, context = document) {
		// 增加查询计数
		if (this.performanceMonitor) {
			this.performanceMonitor.incrementDOMQueryCount();
		}
		
		return context.querySelector(selector);
	}
	
	/**
	 * 查询所有匹配的 DOM 元素（带性能跟踪）
	 * 包装原生 querySelectorAll，自动跟踪查询次数
	 * 
	 * @param {string} selector - CSS 选择器
	 * @param {Element|Document} context - 查询上下文，默认为 document
	 * @returns {NodeList} 匹配的元素列表
	 */
	queryAll(selector, context = document) {
		// 增加查询计数
		if (this.performanceMonitor) {
			this.performanceMonitor.incrementDOMQueryCount();
		}
		
		return context.querySelectorAll(selector);
	}
	
	/**
	 * 通过 ID 查询元素（带性能跟踪）
	 * 包装原生 getElementById，自动跟踪查询次数
	 * 
	 * @param {string} id - 元素 ID
	 * @returns {Element|null} 匹配的元素或 null
	 */
	queryById(id) {
		// 增加查询计数
		if (this.performanceMonitor) {
			this.performanceMonitor.incrementDOMQueryCount();
		}
		
		return document.getElementById(id);
	}
	
	/**
	 * 更新访问时间
	 * 记录元素的最后访问时间，并更新访问顺序
	 * 
	 * @private
	 * @param {string} key - 元素键名
	 * @returns {void}
	 */
	_updateAccessTime(key) {
		// 记录当前时间戳
		this.accessTimes.set(key, Date.now());
		
		// 从访问顺序中移除旧位置
		this._removeFromAccessOrder(key);
		
		// 添加到访问顺序末尾（最近访问）
		this.accessOrder.push(key);
	}
	
	/**
	 * 从访问顺序中移除指定键
	 * 
	 * @private
	 * @param {string} key - 元素键名
	 * @returns {void}
	 */
	_removeFromAccessOrder(key) {
		const index = this.accessOrder.indexOf(key);
		if (index !== -1) {
			this.accessOrder.splice(index, 1);
		}
	}
	
	/**
	 * 执行 LRU 淘汰
	 * 移除最少最近使用的缓存项
	 * 
	 * @private
	 * @returns {void}
	 */
	_evictLRU() {
		// 如果访问顺序为空或缓存为空，无需淘汰
		if (this.accessOrder.length === 0 || this.cache.size === 0) {
			return;
		}
		
		// 获取最少最近使用的键（访问顺序的第一个）
		const lruKey = this.accessOrder[0];
		
		// 从缓存中删除
		this.cache.delete(lruKey);
		this.accessTimes.delete(lruKey);
		
		// 从访问顺序中移除
		this.accessOrder.shift();
		
		// 如果启用了调试模式，输出淘汰信息
		if (typeof argonConfig !== 'undefined' && argonConfig.debug_mode) {
			ArgonDebug.log('DOM缓存', `LRU 淘汰: ${lruKey}`);
		}
	}
	
	/**
	 * 获取缓存统计信息
	 * 用于调试和性能监控
	 * 
	 * @returns {Object} 缓存统计信息
	 */
	getStats() {
		return {
			size: this.cache.size,
			maxSize: this.maxSize,
			utilizationRate: ((this.cache.size / this.maxSize) * 100).toFixed(2) + '%',
			oldestAccess: this._getOldestAccessTime(),
			newestAccess: this._getNewestAccessTime()
		};
	}
	
	/**
	 * 获取最旧的访问时间
	 * 
	 * @private
	 * @returns {number|null} 最旧的访问时间戳，如果缓存为空则返回 null
	 */
	_getOldestAccessTime() {
		if (this.accessOrder.length === 0) {
			return null;
		}
		const oldestKey = this.accessOrder[0];
		return this.accessTimes.get(oldestKey);
	}
	
	/**
	 * 获取最新的访问时间
	 * 
	 * @private
	 * @returns {number|null} 最新的访问时间戳，如果缓存为空则返回 null
	 */
	_getNewestAccessTime() {
		if (this.accessOrder.length === 0) {
			return null;
		}
		const newestKey = this.accessOrder[this.accessOrder.length - 1];
		return this.accessTimes.get(newestKey);
	}
	
	/**
	 * 设置最大缓存大小
	 * 如果新的大小小于当前缓存数量，会触发 LRU 淘汰
	 * 
	 * @param {number} newMaxSize - 新的最大缓存数量
	 * @returns {void}
	 */
	setMaxSize(newMaxSize) {
		if (newMaxSize < 1) {
			ArgonDebug.warn('DOM缓存', 'DOM缓存最大大小必须大于 0');
			return;
		}
		
		this.maxSize = newMaxSize;
		
		// 如果当前缓存数量超过新的上限，执行淘汰
		while (this.cache.size > this.maxSize) {
			this._evictLRU();
		}
	}
	
	/**
	 * 删除指定的缓存项
	 * 
	 * @param {string} key - 元素键名
	 * @returns {boolean} 如果删除成功返回 true，否则返回 false
	 */
	delete(key) {
		if (!this.cache.has(key)) {
			return false;
		}
		
		// 从缓存中删除
		this.cache.delete(key);
		this.accessTimes.delete(key);
		this._removeFromAccessOrder(key);
		
		ArgonDebug.log('DOM缓存', `删除缓存项: ${key}`);
		return true;
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
		this.performanceMonitor = null;  // 性能监控器引用
	}
	
	/**
	 * 设置性能监控器
	 * 允许事件管理器向性能监控器报告监听器数量
	 * 
	 * @param {ArgonPerformanceMonitor} monitor - 性能监控器实例
	 * @returns {void}
	 */
	setPerformanceMonitor(monitor) {
		this.performanceMonitor = monitor;
	}
	
	/**
	 * 更新监听器计数
	 * 通知性能监控器当前的监听器数量
	 * 
	 * @private
	 * @returns {void}
	 */
	_updateListenerCount() {
		if (this.performanceMonitor) {
			let totalCount = 0;
			this.listeners.forEach(handlers => {
				totalCount += handlers.length;
			});
			this.performanceMonitor.setEventListenerCount(totalCount);
		}
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
		
		// 更新监听器计数
		this._updateListenerCount();
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
			
			// 更新监听器计数
			this._updateListenerCount();
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
		
		// 更新监听器计数
		this._updateListenerCount();
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
		
		const timeoutValue = (typeof argonConfig !== 'undefined' && argonConfig.resource_load_timeout) ? parseInt(argonConfig.resource_load_timeout, 10) : 8000;
		const timeout = Number.isFinite(timeoutValue) ? timeoutValue : 8000;
		
		const promise = new Promise((resolve, reject) => {
			const script = document.createElement('script');
			script.src = url;
			script.async = true;
			
			let finished = false;
			let timeoutId = null;
			
			const clearState = () => {
				if (timeoutId) {
					clearTimeout(timeoutId);
					timeoutId = null;
				}
				script.onload = null;
				script.onerror = null;
			};
			
			const resolveSuccess = () => {
				if (finished) return;
				finished = true;
				clearState();
				this.loaded.add(name);
				this.loading.delete(name);
				resolve();
			};
			
			const rejectFailure = (error) => {
				if (finished) return;
				finished = true;
				clearState();
				if (script.parentNode) {
					script.parentNode.removeChild(script);
				}
				this.loading.delete(name);
				reject(error);
			};
			
			script.onload = () => {
				resolveSuccess();
			};
			
			script.onerror = () => {
				rejectFailure(new Error(`Failed to load ${name} from ${url}`));
			};
			
			timeoutId = setTimeout(() => {
				rejectFailure(new Error(`Timeout loading ${name} from ${url}`));
			}, timeout);
			
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
				ArgonDebug.error('渲染优化', '读取操作错误:', error);
			}
		}
		
		// 再执行所有写入操作
		while (this.writeQueue.length) {
			const writeFn = this.writeQueue.shift();
			try {
				writeFn();
			} catch (error) {
				ArgonDebug.error('渲染优化', '写入操作错误:', error);
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
			ArgonDebug.error('渲染优化', '动画执行错误:', error);
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
// 性能监控模块
// ==========================================================================

/**
 * 性能监控类
 * 记录和分析页面性能指标，检测性能问题并提供优化建议
 * 
 * @class ArgonPerformanceMonitor
 */
class ArgonPerformanceMonitor {
	/**
	 * 构造函数
	 * 初始化性能指标存储和监控配置
	 */
	constructor() {
		this.metrics = {};  // 性能指标存储对象
		this.enabled = false;  // 监控是否启用
		this.domQueryCount = 0;  // DOM 查询计数器
		this.eventListenerCount = 0;  // 事件监听器计数器
		this.longTaskObserver = null;  // 长任务观察器
		this.longTasks = [];  // 长任务记录
		
		// 根据配置或调试模式决定是否启用监控
		if (typeof argonConfig !== 'undefined' && argonConfig.debug_mode) {
			this.enabled = true;
		} else if (ArgonPerformanceConfig && ArgonPerformanceConfig.monitor) {
			this.enabled = ArgonPerformanceConfig.monitor.enabled;
		}
		
		// 如果启用监控，初始化长任务观察器
		if (this.enabled) {
			this._initLongTaskObserver();
		}
	}
	
	/**
	 * 记录性能指标
	 * 使用 Performance API 获取页面加载的各项性能数据
	 * 包括 DNS 查询、TCP 连接、请求响应、DOM 解析、页面加载等时间
	 * 
	 * @returns {void}
	 */
	recordMetrics() {
		// 如果监控未启用或浏览器不支持 Performance API，直接返回
		if (!this.enabled || !window.performance) {
			return;
		}
		
		try {
			// 获取导航性能数据
			const perfData = performance.getEntriesByType('navigation')[0];
			
			// 如果没有性能数据，尝试使用旧版 API
			if (!perfData) {
				// 使用 performance.timing（已废弃但兼容性更好）
				if (performance.timing) {
					this._recordLegacyMetrics();
				}
				return;
			}
			
			// 记录各项性能指标（单位：毫秒）
			this.metrics = {
				// DNS 查询时间
				dns: Math.round(perfData.domainLookupEnd - perfData.domainLookupStart),
				
				// TCP 连接时间
				tcp: Math.round(perfData.connectEnd - perfData.connectStart),
				
				// 请求时间（从发送请求到开始接收响应）
				request: Math.round(perfData.responseStart - perfData.requestStart),
				
				// 响应时间（接收响应数据的时间）
				response: Math.round(perfData.responseEnd - perfData.responseStart),
				
				// DOM 解析时间
				dom: Math.round(perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart),
				
				// 页面加载事件时间
				load: Math.round(perfData.loadEventEnd - perfData.loadEventStart),
				
				// 总加载时间（从开始获取到加载完成）
				total: Math.round(perfData.loadEventEnd - perfData.fetchStart),
				
				// 首次内容绘制时间（FCP）
				fcp: this._getFirstContentfulPaint(),
				
				// 最大内容绘制时间（LCP）
				lcp: this._getLargestContentfulPaint()
			};
			
			// 记录时间戳
			this.metrics.timestamp = Date.now();
			
		} catch (error) {
			ArgonDebug.warn('性能监控', '性能指标记录失败:', error);
		}
	}
	
	/**
	 * 使用旧版 Performance API 记录指标
	 * 兼容不支持 PerformanceNavigationTiming 的旧浏览器
	 * 
	 * @private
	 * @returns {void}
	 */
	_recordLegacyMetrics() {
		const timing = performance.timing;
		
		this.metrics = {
			dns: timing.domainLookupEnd - timing.domainLookupStart,
			tcp: timing.connectEnd - timing.connectStart,
			request: timing.responseStart - timing.requestStart,
			response: timing.responseEnd - timing.responseStart,
			dom: timing.domContentLoadedEventEnd - timing.domContentLoadedEventStart,
			load: timing.loadEventEnd - timing.loadEventStart,
			total: timing.loadEventEnd - timing.fetchStart,
			fcp: null,  // 旧版 API 不支持
			lcp: null   // 旧版 API 不支持
		};
		
		this.metrics.timestamp = Date.now();
	}
	
	/**
	 * 获取首次内容绘制时间（FCP）
	 * 
	 * @private
	 * @returns {number|null} FCP 时间（毫秒），如果不可用则返回 null
	 */
	_getFirstContentfulPaint() {
		try {
			const paintEntries = performance.getEntriesByType('paint');
			const fcpEntry = paintEntries.find(entry => entry.name === 'first-contentful-paint');
			return fcpEntry ? Math.round(fcpEntry.startTime) : null;
		} catch (error) {
			return null;
		}
	}
	
	/**
	 * 获取最大内容绘制时间（LCP）
	 * 
	 * @private
	 * @returns {number|null} LCP 时间（毫秒），如果不可用则返回 null
	 */
	_getLargestContentfulPaint() {
		try {
			const lcpEntries = performance.getEntriesByType('largest-contentful-paint');
			if (lcpEntries.length > 0) {
				// 取最后一个 LCP 条目（最新的）
				const lcpEntry = lcpEntries[lcpEntries.length - 1];
				return Math.round(lcpEntry.startTime);
			}
			return null;
		} catch (error) {
			return null;
		}
	}
	
	/**
	 * 获取性能指标
	 * 
	 * @returns {Object} 性能指标对象
	 */
	getMetrics() {
		return this.metrics;
	}
	
	/**
	 * 初始化长任务观察器
	 * 使用 PerformanceObserver 监听长任务（执行时间超过 50ms 的任务）
	 * 
	 * @private
	 * @returns {void}
	 */
	_initLongTaskObserver() {
		// 检查浏览器是否支持 PerformanceObserver 和 longtask 类型
		if (typeof PerformanceObserver === 'undefined') {
			return;
		}
		
		try {
			// 创建长任务观察器
			this.longTaskObserver = new PerformanceObserver((list) => {
				for (const entry of list.getEntries()) {
					// 记录长任务信息
					this.longTasks.push({
						name: entry.name,
						duration: Math.round(entry.duration),
						startTime: Math.round(entry.startTime),
						timestamp: Date.now()
					});
					
					// 只保留最近 10 个长任务记录
					if (this.longTasks.length > 10) {
						this.longTasks.shift();
					}
				}
			});
			
			// 开始观察长任务
			this.longTaskObserver.observe({ entryTypes: ['longtask'] });
		} catch (error) {
			// 某些浏览器可能不支持 longtask 类型，静默失败
		}
	}
	
	/**
	 * 增加 DOM 查询计数
	 * 在执行 DOM 查询时调用此方法
	 * 
	 * @returns {void}
	 */
	incrementDOMQueryCount() {
		if (this.enabled) {
			this.domQueryCount++;
		}
	}
	
	/**
	 * 设置事件监听器数量
	 * 由 ArgonEventManager 调用以更新监听器数量
	 * 
	 * @param {number} count - 当前事件监听器数量
	 * @returns {void}
	 */
	setEventListenerCount(count) {
		if (this.enabled) {
			this.eventListenerCount = count;
		}
	}
	
	/**
	 * 检测性能问题
	 * 分析 DOM 查询频率、事件监听器数量和长任务
	 * 如果检测到问题，在控制台输出警告信息
	 * 
	 * @returns {Array<string>} 检测到的问题列表
	 */
	detectIssues() {
		if (!this.enabled) {
			return [];
		}
		
		const issues = [];
		
		// 检查 DOM 查询频率
		// 如果 DOM 查询次数超过 100 次，建议使用缓存
		if (this.domQueryCount > 100) {
			issues.push(`警告：DOM 查询次数过多（${this.domQueryCount} 次），建议使用 ArgonDOMCache 缓存频繁访问的元素`);
		}
		
		// 检查事件监听器数量
		// 如果事件监听器数量超过 50 个，可能存在内存泄漏
		if (this.eventListenerCount > 50) {
			issues.push(`警告：事件监听器数量过多（${this.eventListenerCount} 个），可能存在内存泄漏，请检查是否正确清理`);
		}
		
		// 检查长任务
		// 如果最近有长任务（执行时间超过 50ms），可能阻塞主线程
		if (this.longTasks.length > 0) {
			const recentLongTasks = this.longTasks.filter(task => {
				// 只检查最近 30 秒内的长任务
				return (Date.now() - task.timestamp) < 30000;
			});
			
			if (recentLongTasks.length > 0) {
				const maxDuration = Math.max(...recentLongTasks.map(t => t.duration));
				issues.push(`警告：检测到 ${recentLongTasks.length} 个长任务（最长 ${maxDuration}ms），可能阻塞主线程，建议使用 requestIdleCallback 或 Web Worker`);
			}
		}
		
		// 如果有问题，在控制台输出警告
		if (issues.length > 0) {
			ArgonDebug.group('性能问题检测');
			issues.forEach(issue => ArgonDebug.warn('性能监控', issue));
			ArgonDebug.groupEnd();
		}
		
		return issues;
	}
	
	/**
	 * 获取 DOM 查询计数
	 * 
	 * @returns {number} DOM 查询次数
	 */
	getDOMQueryCount() {
		return this.domQueryCount;
	}
	
	/**
	 * 获取事件监听器数量
	 * 
	 * @returns {number} 事件监听器数量
	 */
	getEventListenerCount() {
		return this.eventListenerCount;
	}
	
	/**
	 * 获取长任务列表
	 * 
	 * @returns {Array<Object>} 长任务记录数组
	 */
	getLongTasks() {
		return this.longTasks;
	}
	
	/**
	 * 重置计数器
	 * 清空 DOM 查询计数和长任务记录
	 * 
	 * @returns {void}
	 */
	resetCounters() {
		this.domQueryCount = 0;
		this.longTasks = [];
	}
	
	/**
	 * 输出性能报告
	 * 根据开发模式或生产模式输出不同详细程度的性能报告
	 * 开发模式：输出详细的性能分析数据和优化建议
	 * 生产模式：仅输出关键指标，避免影响性能
	 * 
	 * @param {boolean} [forceDetailed=false] - 是否强制输出详细报告（忽略模式设置）
	 * @returns {void}
	 */
	report(forceDetailed = false) {
		// 如果监控未启用，不输出报告
		if (!this.enabled) {
			return;
		}
		
		// 判断是否为开发模式
		const isDevelopmentMode = forceDetailed || 
			(typeof argonConfig !== 'undefined' && argonConfig.debug_mode);
		
		// 如果是生产模式，输出精简报告
		if (!isDevelopmentMode) {
			this._reportProduction();
			return;
		}
		
		// 开发模式：输出详细报告
		this._reportDevelopment();
	}
	
	/**
	 * 输出生产模式性能报告
	 * 仅输出关键指标，避免影响性能
	 * 
	 * @private
	 * @returns {void}
	 */
	_reportProduction() {
		console.group('Argon 性能报告（生产模式）');
		
		// 输出关键性能指标
		if (this.metrics && Object.keys(this.metrics).length > 0) {
			const keyMetrics = {
				'总加载时间': this.metrics.total ? `${this.metrics.total}ms` : 'N/A',
				'首次内容绘制': this.metrics.fcp ? `${this.metrics.fcp}ms` : 'N/A',
				'最大内容绘制': this.metrics.lcp ? `${this.metrics.lcp}ms` : 'N/A'
			};
			
			console.table(keyMetrics);
		} else {
			console.info('暂无性能指标数据');
		}
		
		// 检测并输出关键性能问题
		const issues = this.detectIssues();
		if (issues.length > 0) {
			console.warn('检测到性能问题，建议优化');
		}
		
		console.groupEnd();
	}
	
	/**
	 * 输出开发模式性能报告
	 * 提供详细的性能分析数据和优化建议
	 * 
	 * @private
	 * @returns {void}
	 */
	_reportDevelopment() {
		console.group('Argon 性能报告（开发模式）');
		
		// 1. 输出详细的性能指标
		if (this.metrics && Object.keys(this.metrics).length > 0) {
			console.group('📊 性能指标');
			
			const detailedMetrics = {
				'DNS 查询': this.metrics.dns ? `${this.metrics.dns}ms` : 'N/A',
				'TCP 连接': this.metrics.tcp ? `${this.metrics.tcp}ms` : 'N/A',
				'请求时间': this.metrics.request ? `${this.metrics.request}ms` : 'N/A',
				'响应时间': this.metrics.response ? `${this.metrics.response}ms` : 'N/A',
				'DOM 解析': this.metrics.dom ? `${this.metrics.dom}ms` : 'N/A',
				'页面加载': this.metrics.load ? `${this.metrics.load}ms` : 'N/A',
				'总加载时间': this.metrics.total ? `${this.metrics.total}ms` : 'N/A',
				'首次内容绘制 (FCP)': this.metrics.fcp ? `${this.metrics.fcp}ms` : 'N/A',
				'最大内容绘制 (LCP)': this.metrics.lcp ? `${this.metrics.lcp}ms` : 'N/A'
			};
			
			console.table(detailedMetrics);
			console.groupEnd();
		} else {
			console.info('暂无性能指标数据，请在页面加载完成后调用 recordMetrics()');
		}
		
		// 2. 输出资源使用统计
		console.group('📈 资源使用统计');
		console.log(`DOM 查询次数: ${this.domQueryCount}`);
		console.log(`事件监听器数量: ${this.eventListenerCount}`);
		console.log(`长任务记录数: ${this.longTasks.length}`);
		console.groupEnd();
		
		// 3. 输出长任务详情（如果有）
		if (this.longTasks.length > 0) {
			console.group('⏱️ 长任务详情');
			const longTasksTable = this.longTasks.map(task => ({
				'任务名称': task.name,
				'执行时间': `${task.duration}ms`,
				'开始时间': `${task.startTime}ms`,
				'记录时间': new Date(task.timestamp).toLocaleTimeString()
			}));
			console.table(longTasksTable);
			console.groupEnd();
		}
		
		// 4. 检测性能问题并提供优化建议
		const issues = this.detectIssues();
		
		// 5. 提供针对性的优化建议
		this._provideOptimizationSuggestions();
		
		console.groupEnd();
	}
	
	/**
	 * 提供优化建议
	 * 根据性能指标分析并提供针对性的优化建议
	 * 
	 * @private
	 * @returns {void}
	 */
	_provideOptimizationSuggestions() {
		ArgonDebug.group('💡 优化建议');
		
		const suggestions = [];
		
		// 根据总加载时间提供建议
		if (this.metrics.total) {
			if (this.metrics.total > 3000) {
				suggestions.push('⚠️ 总加载时间超过 3 秒，建议：');
				suggestions.push('  - 启用资源压缩和缓存');
				suggestions.push('  - 使用 CDN 加速静态资源');
				suggestions.push('  - 延迟加载非关键资源');
			} else if (this.metrics.total > 2000) {
				suggestions.push('⚡ 总加载时间可以进一步优化，建议：');
				suggestions.push('  - 优化图片大小和格式');
				suggestions.push('  - 减少 HTTP 请求数量');
			} else {
				suggestions.push('✅ 总加载时间表现良好（< 2 秒）');
			}
		}
		
		// 根据 FCP 提供建议
		if (this.metrics.fcp) {
			if (this.metrics.fcp > 1800) {
				suggestions.push('⚠️ 首次内容绘制时间较长，建议：');
				suggestions.push('  - 内联关键 CSS');
				suggestions.push('  - 减少阻塞渲染的资源');
				suggestions.push('  - 使用字体预加载');
			} else if (this.metrics.fcp > 1000) {
				suggestions.push('⚡ 首次内容绘制可以优化，建议：');
				suggestions.push('  - 优化关键渲染路径');
			} else {
				suggestions.push('✅ 首次内容绘制表现良好（< 1 秒）');
			}
		}
		
		// 根据 LCP 提供建议
		if (this.metrics.lcp) {
			if (this.metrics.lcp > 2500) {
				suggestions.push('⚠️ 最大内容绘制时间较长，建议：');
				suggestions.push('  - 优化最大内容元素（图片、视频等）');
				suggestions.push('  - 使用响应式图片和 WebP 格式');
				suggestions.push('  - 预加载关键资源');
			} else if (this.metrics.lcp > 1500) {
				suggestions.push('⚡ 最大内容绘制可以优化');
			} else {
				suggestions.push('✅ 最大内容绘制表现良好（< 1.5 秒）');
			}
		}
		
		// 根据 DOM 查询次数提供建议
		if (this.domQueryCount > 100) {
			suggestions.push('⚠️ DOM 查询次数过多，建议：');
			suggestions.push('  - 使用 ArgonDOMCache 缓存频繁访问的元素');
			suggestions.push('  - 避免在循环中重复查询 DOM');
		} else if (this.domQueryCount > 50) {
			suggestions.push('⚡ DOM 查询次数较多，考虑使用缓存优化');
		}
		
		// 根据事件监听器数量提供建议
		if (this.eventListenerCount > 50) {
			suggestions.push('⚠️ 事件监听器数量过多，建议：');
			suggestions.push('  - 检查是否存在内存泄漏');
			suggestions.push('  - 使用事件委托减少监听器数量');
			suggestions.push('  - 确保在 PJAX 切换时正确清理监听器');
		} else if (this.eventListenerCount > 30) {
			suggestions.push('⚡ 事件监听器数量较多，注意及时清理');
		}
		
		// 根据长任务提供建议
		if (this.longTasks.length > 0) {
			const recentLongTasks = this.longTasks.filter(task => {
				return (Date.now() - task.timestamp) < 30000;
			});
			
			if (recentLongTasks.length > 0) {
				suggestions.push('⚠️ 检测到长任务（执行时间 > 50ms），建议：');
				suggestions.push('  - 使用 requestIdleCallback 延迟非关键任务');
				suggestions.push('  - 将复杂计算移至 Web Worker');
				suggestions.push('  - 分批处理大量数据，避免阻塞主线程');
			}
		}
		
		// 输出建议
		if (suggestions.length > 0) {
			suggestions.forEach(suggestion => ArgonDebug.log('性能监控', suggestion));
		} else {
			ArgonDebug.log('性能监控', '✅ 未检测到明显的性能问题，继续保持！');
		}
		
		ArgonDebug.groupEnd();
	}
	
	/**
	 * 检查监控是否启用
	 * 
	 * @returns {boolean} 如果监控启用返回 true
	 */
	isEnabled() {
		return this.enabled;
	}
	
	/**
	 * 启用监控
	 * 
	 * @returns {void}
	 */
	enable() {
		if (!this.enabled) {
			this.enabled = true;
			this._initLongTaskObserver();
		}
	}
	
	/**
	 * 禁用监控
	 * 
	 * @returns {void}
	 */
	disable() {
		this.enabled = false;
		
		// 断开长任务观察器
		if (this.longTaskObserver) {
			try {
				this.longTaskObserver.disconnect();
			} catch (error) {
				// 静默失败
			}
			this.longTaskObserver = null;
		}
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
	
	// 初始化调试控制台
	ArgonDebug.init();
}

// 导出配置对象和类供其他模块使用
if (typeof window !== 'undefined') {
	window.ArgonPerformanceConfig = ArgonPerformanceConfig;
	window.ArgonDOMCache = ArgonDOMCache;
	window.ArgonEventManager = ArgonEventManager;
	window.ArgonResourceLoader = ArgonResourceLoader;
	window.ArgonRenderOptimizer = ArgonRenderOptimizer;
	window.ArgonMemoryManager = ArgonMemoryManager;
	window.ArgonPerformanceMonitor = ArgonPerformanceMonitor;
	window.initArgonPerformance = initArgonPerformance;
}
