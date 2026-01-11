/* Argon 内部日志系统 - 仅管理员可见 */
(function() {
    'use strict';
    
    window.ArgonLogger = {
        // 配置
        config: {
            maxLogs: 1000,
            enableConsole: false, // 默认关闭控制台输出
            enableStorage: true,
            storageKey: 'argon_internal_logs'
        },
        
        // 日志级别
        levels: {
            DEBUG: 0,
            INFO: 1,
            WARN: 2,
            ERROR: 3,
            CRITICAL: 4
        },
        
        // 内部日志存储
        logs: [],
        
        // 初始化
        init: function() {
            this.checkAdminStatus();
            this.loadStoredLogs();
            this.bindEvents();
        },
        
        // 检查管理员状态
        checkAdminStatus: function() {
            // 检查是否为管理员（通过PHP传递的全局变量）
            if (typeof window.argonUserRole !== 'undefined' && window.argonUserRole === 'administrator') {
                this.config.enableConsole = true;
                this.log('管理员模式：控制台日志已启用', 'INFO');
            } else {
                this.config.enableConsole = false;
            }
        },
        
        // 加载存储的日志
        loadStoredLogs: function() {
            if (this.config.enableStorage && localStorage) {
                try {
                    var stored = localStorage.getItem(this.config.storageKey);
                    if (stored) {
                        this.logs = JSON.parse(stored);
                    }
                } catch (e) {
                    // 静默处理存储错误
                }
            }
        },
        
        // 保存日志到存储
        saveLogsToStorage: function() {
            if (this.config.enableStorage && localStorage) {
                try {
                    // 只保留最新的日志
                    var logsToSave = this.logs.slice(-this.config.maxLogs);
                    localStorage.setItem(this.config.storageKey, JSON.stringify(logsToSave));
                } catch (e) {
                    // 静默处理存储错误
                }
            }
        },
        
        // 记录日志
        log: function(message, level, data) {
            level = level || 'INFO';
            var timestamp = new Date().toISOString();
            var logEntry = {
                timestamp: timestamp,
                level: level,
                message: message,
                data: data || null,
                url: window.location.href,
                userAgent: navigator.userAgent.substring(0, 100) // 截取前100字符
            };
            
            // 添加到内部日志
            this.logs.push(logEntry);
            
            // 限制日志数量
            if (this.logs.length > this.config.maxLogs) {
                this.logs = this.logs.slice(-this.config.maxLogs);
            }
            
            // 保存到存储
            this.saveLogsToStorage();
            
            // 仅管理员显示控制台日志
            if (this.config.enableConsole) {
                var consoleMethod = this.getConsoleMethod(level);
                consoleMethod('[Argon] ' + message, data || '');
            }
            
            // 关键错误需要特殊处理
            if (level === 'CRITICAL') {
                this.handleCriticalError(logEntry);
            }
        },
        
        // 获取对应的控制台方法
        getConsoleMethod: function(level) {
            switch (level) {
                case 'DEBUG':
                    return console.debug || console.log;
                case 'INFO':
                    return console.info || console.log;
                case 'WARN':
                    return console.warn || console.log;
                case 'ERROR':
                case 'CRITICAL':
                    return console.error || console.log;
                default:
                    return console.log;
            }
        },
        
        // 处理关键错误
        handleCriticalError: function(logEntry) {
            // 关键错误需要通知用户联系站长
            if (this.config.enableConsole) {
                console.error('发生关键错误，请联系站长处理:', logEntry.message);
            }
            
            // 可以在这里添加错误上报逻辑
            this.reportCriticalError(logEntry);
        },
        
        // 上报关键错误
        reportCriticalError: function(logEntry) {
            // 这里可以实现错误上报到服务器的逻辑
            // 暂时只记录到本地存储
            try {
                var criticalLogs = JSON.parse(localStorage.getItem('argon_critical_errors') || '[]');
                criticalLogs.push(logEntry);
                localStorage.setItem('argon_critical_errors', JSON.stringify(criticalLogs.slice(-50)));
            } catch (e) {
                // 静默处理
            }
        },
        
        // 获取日志（仅管理员）
        getLogs: function(level, limit) {
            if (!this.config.enableConsole) {
                return []; // 非管理员返回空数组
            }
            
            var filteredLogs = this.logs;
            
            if (level) {
                filteredLogs = this.logs.filter(function(log) {
                    return log.level === level;
                });
            }
            
            if (limit) {
                filteredLogs = filteredLogs.slice(-limit);
            }
            
            return filteredLogs;
        },
        
        // 清除日志（仅管理员）
        clearLogs: function() {
            if (!this.config.enableConsole) {
                return false;
            }
            
            this.logs = [];
            if (localStorage) {
                localStorage.removeItem(this.config.storageKey);
                localStorage.removeItem('argon_critical_errors');
            }
            return true;
        },
        
        // 导出日志（仅管理员）
        exportLogs: function() {
            if (!this.config.enableConsole) {
                return null;
            }
            
            var exportData = {
                timestamp: new Date().toISOString(),
                site: window.location.origin,
                logs: this.logs,
                criticalErrors: JSON.parse(localStorage.getItem('argon_critical_errors') || '[]')
            };
            
            return JSON.stringify(exportData, null, 2);
        },
        
        // 绑定事件
        bindEvents: function() {
            var self = this;
            
            // 监听页面错误
            window.addEventListener('error', function(e) {
                self.log('JavaScript错误: ' + e.message, 'ERROR', {
                    filename: e.filename,
                    lineno: e.lineno,
                    colno: e.colno,
                    stack: e.error ? e.error.stack : null
                });
            });
            
            // 监听未处理的Promise拒绝
            window.addEventListener('unhandledrejection', function(e) {
                self.log('未处理的Promise拒绝: ' + e.reason, 'ERROR', {
                    reason: e.reason,
                    promise: e.promise
                });
            });
            
            // 监听资源加载错误
            window.addEventListener('error', function(e) {
                if (e.target && e.target !== window) {
                    var resourceType = e.target.tagName || 'unknown';
                    var resourceUrl = e.target.src || e.target.href || 'unknown';
                    self.log('资源加载失败: ' + resourceType + ' - ' + resourceUrl, 'WARN');
                }
            }, true);
        },
        
        // 便捷方法
        debug: function(message, data) {
            this.log(message, 'DEBUG', data);
        },
        
        info: function(message, data) {
            this.log(message, 'INFO', data);
        },
        
        warn: function(message, data) {
            this.log(message, 'WARN', data);
        },
        
        error: function(message, data) {
            this.log(message, 'ERROR', data);
        },
        
        critical: function(message, data) {
            this.log(message, 'CRITICAL', data);
        }
    };
    
    // 自动初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ArgonLogger.init();
        });
    } else {
        ArgonLogger.init();
    }
    
    // 为管理员提供全局访问
    if (typeof window.argonUserRole !== 'undefined' && window.argonUserRole === 'administrator') {
        window.ArgonLogs = ArgonLogger;
    }
})();