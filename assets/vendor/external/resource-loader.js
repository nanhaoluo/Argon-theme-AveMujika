/* 外部资源加载器 - 自动备用机制 */
(function() {
    'use strict';
    
    window.ArgonResourceLoader = {
        // 资源映射表
        resourceMap: {
            'fonts.googleapis.com/css?family=Open+Sans': '/wp-content/themes/argon/assets/vendor/external/fonts/open-sans.css',
            'fonts.googleapis.com/css?family=Noto+Serif+SC': '/wp-content/themes/argon/assets/vendor/external/fonts/noto-serif-sc.css',
            'static.geetest.com/v4/gt4.js': '/wp-content/themes/argon/assets/vendor/external/geetest/gt4.js',
            'cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js': '/wp-content/themes/argon/assets/vendor/external/qrcode/qrcode.min.js',
            'cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js': '/wp-content/themes/argon/assets/vendor/external/mathjax3/tex-chtml-full.js',
            'cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js': '/wp-content/themes/argon/assets/vendor/external/mathjax2/MathJax.js',
            'cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.css': '/wp-content/themes/argon/assets/vendor/external/katex/katex.min.css',
            'cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.js': '/wp-content/themes/argon/assets/vendor/external/katex/katex.min.js',
            'cdn.jsdelivr.net/npm/katex@0.11.1/dist/contrib/auto-render.min.js': '/wp-content/themes/argon/assets/vendor/external/katex/auto-render.min.js'
        },
        
        // 配置选项
        config: {
            timeout: 5000,
            retryCount: 2,
            enableLogging: true,
            enableFallbackCSS: true,
            enableFallbackJS: true
        },
        
        // 初始化
        init: function() {
            this.loadFallbackSystems();
            this.bindGlobalErrorHandlers();
            this.log('资源加载器已初始化');
        },
        
        // 加载备用系统
        loadFallbackSystems: function() {
            var self = this;
            
            if (this.config.enableFallbackCSS) {
                // 使用 silent 模式加载，失败时不抛出错误
                this.loadCSS('/wp-content/themes/argon/assets/vendor/external/css-fallback.css', null, true);
                this.loadCSS('/wp-content/themes/argon/assets/vendor/external/fonts/font-fallback.css', null, true);
            }
            
            if (this.config.enableFallbackJS) {
                this.loadJS('/wp-content/themes/argon/assets/vendor/external/js-fallback.js', null, true);
                this.loadJS('/wp-content/themes/argon/assets/vendor/external/resource-monitor.js', null, true);
            }
        },
        
        // 绑定全局错误处理
        bindGlobalErrorHandlers: function() {
            var self = this;
            
            // 监听资源加载错误
            window.addEventListener('error', function(e) {
                if (e.target && (e.target.tagName === 'SCRIPT' || e.target.tagName === 'LINK')) {
                    self.handleResourceError(e.target);
                }
            }, true);
            
            // 监听未处理的Promise拒绝
            window.addEventListener('unhandledrejection', function(e) {
                if (typeof ArgonLogger !== 'undefined') {
                    ArgonLogger.warn('未处理的Promise拒绝: ' + e.reason);
                }
            });
        },
        
        // 处理资源加载错误
        handleResourceError: function(element) {
            var url = element.src || element.href;
            if (url) {
                if (typeof ArgonLogger !== 'undefined') {
                    ArgonLogger.warn('资源加载失败: ' + url);
                }
                var fallbackUrl = this.getFallbackUrl(url);
                if (fallbackUrl) {
                    this.log('尝试加载备用资源: ' + fallbackUrl);
                    if (element.tagName === 'SCRIPT') {
                        this.loadJS(fallbackUrl);
                    } else if (element.tagName === 'LINK') {
                        this.loadCSS(fallbackUrl);
                    }
                }
            }
        },
        
        // 获取备用URL
        getFallbackUrl: function(url) {
            for (var key in this.resourceMap) {
                if (url.includes(key)) {
                    return this.resourceMap[key];
                }
            }
            return null;
        },
        
        // 加载CSS资源
        loadCSS: function(url, fallbackUrl, silent) {
            var self = this;
            return new Promise(function(resolve, reject) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = url;
                
                var handleFailure = function(reason) {
                    self.log('CSS资源加载' + reason + ': ' + url);
                    if (fallbackUrl) {
                        self.loadCSS(fallbackUrl, null, silent).then(resolve).catch(reject);
                    } else {
                        var autoFallback = self.getFallbackUrl(url);
                        if (autoFallback) {
                            self.loadCSS(autoFallback, null, silent).then(resolve).catch(reject);
                        } else if (silent) {
                            // 静默模式下不抛出错误
                            resolve();
                        } else {
                            reject(new Error('CSS加载失败且无备用资源: ' + url));
                        }
                    }
                };
                
                var timeout = setTimeout(function() {
                    handleFailure('超时');
                }, self.config.timeout);
                
                link.onload = function() {
                    clearTimeout(timeout);
                    self.log('CSS资源加载成功: ' + url);
                    resolve();
                };
                
                link.onerror = function() {
                    clearTimeout(timeout);
                    handleFailure('失败');
                };
                
                document.head.appendChild(link);
            });
        },
        
        // 加载JS资源
        loadJS: function(url, fallbackUrl, silent) {
            var self = this;
            return new Promise(function(resolve, reject) {
                var script = document.createElement('script');
                script.src = url;
                script.async = true;
                
                var handleFailure = function(reason) {
                    self.log('JS资源加载' + reason + ': ' + url);
                    if (fallbackUrl) {
                        self.loadJS(fallbackUrl, null, silent).then(resolve).catch(reject);
                    } else {
                        var autoFallback = self.getFallbackUrl(url);
                        if (autoFallback) {
                            self.loadJS(autoFallback, null, silent).then(resolve).catch(reject);
                        } else if (silent) {
                            // 静默模式下不抛出错误
                            resolve();
                        } else {
                            reject(new Error('JS加载失败且无备用资源: ' + url));
                        }
                    }
                };
                
                var timeout = setTimeout(function() {
                    handleFailure('超时');
                }, self.config.timeout);
                
                script.onload = function() {
                    clearTimeout(timeout);
                    self.log('JS资源加载成功: ' + url);
                    resolve();
                };
                
                script.onerror = function() {
                    clearTimeout(timeout);
                    handleFailure('失败');
                };
                
                document.head.appendChild(script);
            });
        },
        
        // 智能加载资源
        smartLoad: function(url, type) {
            if (type === 'css') {
                return this.loadCSS(url);
            } else if (type === 'js') {
                return this.loadJS(url);
            } else {
                return Promise.reject(new Error('不支持的资源类型: ' + type));
            }
        },
        
        // 批量加载资源
        loadMultiple: function(resources) {
            var self = this;
            var promises = resources.map(function(resource) {
                return self.smartLoad(resource.url, resource.type);
            });
            return Promise.all(promises);
        },
        
        // 日志记录
        log: function(message) {
            if (this.config.enableLogging) {
                if (typeof ArgonLogger !== 'undefined') {
                    ArgonLogger.info('[ResourceLoader] ' + message);
                } else {
                    // 仅在管理员模式下输出到控制台
                    if (typeof window.argonUserRole !== 'undefined' && window.argonUserRole === 'administrator') {
                        console.log('[ArgonResourceLoader] ' + message);
                    }
                }
            }
        }
    };
    
    // 自动初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ArgonResourceLoader.init();
        });
    } else {
        ArgonResourceLoader.init();
    }
})();