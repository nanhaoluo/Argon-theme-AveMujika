/* 外部资源加载器 - 自动备用机制 */
(function() {
    'use strict';
    
    window.ArgonResourceLoader = {
        // 资源映射表
        resourceMap: {
            'fonts.googleapis.com/css?family=Open+Sans': '/wp-content/themes/argon/assets/vendor/external/fonts/open-sans.css',
            'fonts.googleapis.com/css?family=Noto+Serif+SC': '/wp-content/themes/argon/assets/vendor/external/fonts/noto-serif-sc.css',
            'static.geetest.com/v4/gt4.js': '/wp-content/themes/argon/assets/vendor/external/geetest/gt4.js',
            'cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js': '/wp-content/themes/argon/assets/vendor/external/qrcode/qrcode.min.js'
        },
        
        // 加载CSS资源
        loadCSS: function(url, fallbackUrl) {
            return new Promise(function(resolve, reject) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = url;
                
                var timeout = setTimeout(function() {
                    console.warn('CSS资源加载超时，使用本地备用:', url);
                    if (fallbackUrl) {
                        ArgonResourceLoader.loadCSS(fallbackUrl).then(resolve).catch(reject);
                    } else {
                        reject(new Error('CSS加载失败且无备用资源'));
                    }
                }, 5000);
                
                link.onload = function() {
                    clearTimeout(timeout);
                    resolve();
                };
                
                link.onerror = function() {
                    clearTimeout(timeout);
                    console.warn('CSS资源加载失败，使用本地备用:', url);
                    if (fallbackUrl) {
                        ArgonResourceLoader.loadCSS(fallbackUrl).then(resolve).catch(reject);
                    } else {
                        reject(new Error('CSS加载失败且无备用资源'));
                    }
                };
                
                document.head.appendChild(link);
            });
        },
        
        // 加载JS资源
        loadJS: function(url, fallbackUrl) {
            return new Promise(function(resolve, reject) {
                var script = document.createElement('script');
                script.src = url;
                script.async = true;
                
                var timeout = setTimeout(function() {
                    console.warn('JS资源加载超时，使用本地备用:', url);
                    if (fallbackUrl) {
                        ArgonResourceLoader.loadJS(fallbackUrl).then(resolve).catch(reject);
                    } else {
                        reject(new Error('JS加载失败且无备用资源'));
                    }
                }, 5000);
                
                script.onload = function() {
                    clearTimeout(timeout);
                    resolve();
                };
                
                script.onerror = function() {
                    clearTimeout(timeout);
                    console.warn('JS资源加载失败，使用本地备用:', url);
                    if (fallbackUrl) {
                        ArgonResourceLoader.loadJS(fallbackUrl).then(resolve).catch(reject);
                    } else {
                        reject(new Error('JS加载失败且无备用资源'));
                    }
                };
                
                document.head.appendChild(script);
            });
        },
        
        // 智能加载资源
        smartLoad: function(url, type) {
            var fallbackUrl = null;
            
            // 查找备用资源
            for (var key in this.resourceMap) {
                if (url.indexOf(key) !== -1) {
                    fallbackUrl = this.resourceMap[key];
                    break;
                }
            }
            
            if (type === 'css') {
                return this.loadCSS(url, fallbackUrl);
            } else if (type === 'js') {
                return this.loadJS(url, fallbackUrl);
            }
        }
    };
    
    // 自动替换现有的资源加载
    var originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        var element = originalCreateElement.call(document, tagName);
        
        if (tagName.toLowerCase() === 'script') {
            var originalSetAttribute = element.setAttribute;
            element.setAttribute = function(name, value) {
                if (name === 'src' && value) {
                    // 检查是否需要备用资源
                    for (var key in ArgonResourceLoader.resourceMap) {
                        if (value.indexOf(key) !== -1) {
                            console.log('检测到外部JS资源，准备备用方案:', value);
                            break;
                        }
                    }
                }
                return originalSetAttribute.call(this, name, value);
            };
        }
        
        return element;
    };
})();