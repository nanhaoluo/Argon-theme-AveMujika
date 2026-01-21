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
	window.initArgonPerformance = initArgonPerformance;
}
