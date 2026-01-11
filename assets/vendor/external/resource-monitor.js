/* 外部资源监控系统 - 实时检测和自动切换 */
(function() {
    'use strict';
    
    window.ArgonResourceMonitor = {
        // 监控配置
        config: {
            checkInterval: 30000, // 30秒检查一次
            timeout: 5000,        // 5秒超时
            retryCount: 3,        // 重试3次
            enableLogging: true   // 启用日志
        },
        
        // 资源状态
        resourceStatus: {},
        
        // 监控定时器
        monitorTimer: null,
        
        // 初始化监控
        init: function() {
            this.log('资源监控系统启动');
            this.startMonitoring();
            this.bindEvents();
        },
        
        // 开始监控
        startMonitoring: function() {
            var self = this;
            this.monitorTimer = setInterval(function() {
                self.checkAllResources();
            }, this.config.checkInterval);
        },
        
        // 停止监控
        stopMonitoring: function() {
            if (this.monitorTimer) {
                clearInterval(this.monitorTimer);
                this.monitorTimer = null;
            }
        },
        
        // 检查所有资源
        checkAllResources: function() {
            var self = this;
            var resources = [
                'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700',
                'https://static.geetest.com/v4/gt4.js',
                'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'
            ];
            
            resources.forEach(function(url) {
                self.checkResource(url);
            });
        },
        
        // 检查单个资源
        checkResource: function(url) {
            var self = this;
            var startTime = Date.now();
            
            // 创建测试请求
            var xhr = new XMLHttpRequest();
            xhr.timeout = this.config.timeout;
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    var responseTime = Date.now() - startTime;
                    var status = xhr.status === 200 ? 'available' : 'unavailable';
                    
                    self.updateResourceStatus(url, {
                        status: status,
                        responseTime: responseTime,
                        lastCheck: new Date().toISOString()
                    });
                }
            };
            
            xhr.onerror = function() {
                self.updateResourceStatus(url, {
                    status: 'unavailable',
                    responseTime: -1,
                    lastCheck: new Date().toISOString()
                });
            };
            
            xhr.ontimeout = function() {
                self.updateResourceStatus(url, {
                    status: 'timeout',
                    responseTime: self.config.timeout,
                    lastCheck: new Date().toISOString()
                });
            };
            
            try {
                xhr.open('HEAD', url, true);
                xhr.send();
            } catch (e) {
                this.updateResourceStatus(url, {
                    status: 'error',
                    responseTime: -1,
                    lastCheck: new Date().toISOString(),
                    error: e.message
                });
            }
        },
        
        // 更新资源状态
        updateResourceStatus: function(url, status) {
            this.resourceStatus[url] = status;
            this.log('资源状态更新: ' + url + ' - ' + status.status);
            
            // 如果资源不可用，触发备用机制
            if (status.status !== 'available') {
                this.triggerFallback(url);
            }
        },
        
        // 触发备用机制
        triggerFallback: function(url) {
            this.log('触发备用机制: ' + url);
            
            // 发送自定义事件
            var event = new CustomEvent('resourceUnavailable', {
                detail: { url: url, status: this.resourceStatus[url] }
            });
            document.dispatchEvent(event);
        },
        
        // 绑定事件
        bindEvents: function() {
            var self = this;
            
            // 监听页面可见性变化
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    self.checkAllResources();
                }
            });
            
            // 监听网络状态变化
            if ('onLine' in navigator) {
                window.addEventListener('online', function() {
                    self.log('网络连接恢复，重新检查资源');
                    self.checkAllResources();
                });
                
                window.addEventListener('offline', function() {
                    self.log('网络连接断开');
                });
            }
        },
        
        // 获取资源状态报告
        getStatusReport: function() {
            return {
                timestamp: new Date().toISOString(),
                resources: this.resourceStatus,
                summary: this.generateSummary()
            };
        },
        
        // 生成状态摘要
        generateSummary: function() {
            var total = Object.keys(this.resourceStatus).length;
            var available = 0;
            var unavailable = 0;
            
            for (var url in this.resourceStatus) {
                if (this.resourceStatus[url].status === 'available') {
                    available++;
                } else {
                    unavailable++;
                }
            }
            
            return {
                total: total,
                available: available,
                unavailable: unavailable,
                availability: total > 0 ? (available / total * 100).toFixed(1) + '%' : '0%'
            };
        },
        
        // 日志记录
        log: function(message) {
            if (this.config.enableLogging) {
                console.log('[ArgonResourceMonitor] ' + message);
            }
        }
    };
    
    // 页面加载完成后自动启动监控
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ArgonResourceMonitor.init();
        });
    } else {
        ArgonResourceMonitor.init();
    }
})();