/* JavaScript 功能备用系统 - 确保基本功能始终可用 */
(function() {
    'use strict';
    
    // 检查并提供基本的 JavaScript 功能
    window.ArgonFallback = {
        // 初始化备用系统
        init: function() {
            this.checkDependencies();
            this.provideFallbacks();
            this.bindEvents();
            console.log('JavaScript 备用系统已启动');
        },
        
        // 检查依赖项
        checkDependencies: function() {
            var missing = [];
            
            // 检查 jQuery
            if (typeof jQuery === 'undefined') {
                missing.push('jQuery');
                this.provideJQueryFallback();
            }
            
            // 检查其他关键库
            if (typeof bootstrap === 'undefined') {
                missing.push('Bootstrap');
            }
            
            if (missing.length > 0) {
                console.warn('缺少依赖项:', missing.join(', '));
            }
        },
        
        // 提供 jQuery 基本功能备用
        provideJQueryFallback: function() {
            window.$ = window.jQuery = function(selector) {
                return new jQueryFallback(selector);
            };
            
            // 扩展 jQuery 对象
            $.fn = jQueryFallback.prototype;
            $.extend = function(target) {
                for (var i = 1; i < arguments.length; i++) {
                    var source = arguments[i];
                    for (var key in source) {
                        if (source.hasOwnProperty(key)) {
                            target[key] = source[key];
                        }
                    }
                }
                return target;
            };
            
            console.log('jQuery 备用版本已加载');
        },
        
        // 提供其他备用功能
        provideFallbacks: function() {
            // 模态框功能
            this.provideModalFallback();
            
            // 工具提示功能
            this.provideTooltipFallback();
            
            // 下拉菜单功能
            this.provideDropdownFallback();
        },
        
        // 模态框备用
        provideModalFallback: function() {
            window.showModal = function(content, title) {
                var modal = document.createElement('div');
                modal.className = 'modal show';
                modal.innerHTML = 
                    '<div class="modal-dialog">' +
                        '<div class="modal-header">' +
                            '<h5>' + (title || '提示') + '</h5>' +
                            '<button type="button" class="close" onclick="this.closest(\'.modal\').remove()">&times;</button>' +
                        '</div>' +
                        '<div class="modal-body">' + content + '</div>' +
                        '<div class="modal-footer">' +
                            '<button type="button" class="btn" onclick="this.closest(\'.modal\').remove()">关闭</button>' +
                        '</div>' +
                    '</div>';
                document.body.appendChild(modal);
                
                // 点击背景关闭
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
            };
        },
        
        // 工具提示备用
        provideTooltipFallback: function() {
            document.addEventListener('mouseover', function(e) {
                var element = e.target;
                var tooltip = element.getAttribute('title') || element.getAttribute('data-tooltip');
                
                if (tooltip && !element.querySelector('.tooltip-fallback')) {
                    var tooltipEl = document.createElement('div');
                    tooltipEl.className = 'tooltip-fallback';
                    tooltipEl.textContent = tooltip;
                    tooltipEl.style.cssText = 
                        'position: absolute; background: #333; color: #fff; padding: 4px 8px; ' +
                        'border-radius: 4px; font-size: 12px; z-index: 1000; pointer-events: none;';
                    
                    document.body.appendChild(tooltipEl);
                    
                    var rect = element.getBoundingClientRect();
                    tooltipEl.style.left = rect.left + 'px';
                    tooltipEl.style.top = (rect.top - tooltipEl.offsetHeight - 5) + 'px';
                    
                    element.addEventListener('mouseleave', function() {
                        if (tooltipEl.parentNode) {
                            tooltipEl.parentNode.removeChild(tooltipEl);
                        }
                    });
                }
            });
        },
        
        // 下拉菜单备用
        provideDropdownFallback: function() {
            document.addEventListener('click', function(e) {
                var toggle = e.target.closest('[data-toggle="dropdown"]');
                if (toggle) {
                    e.preventDefault();
                    var menu = toggle.nextElementSibling;
                    if (menu && menu.classList.contains('dropdown-menu')) {
                        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                    }
                }
                
                // 关闭其他下拉菜单
                var openMenus = document.querySelectorAll('.dropdown-menu[style*="block"]');
                openMenus.forEach(function(menu) {
                    if (!menu.contains(e.target) && !menu.previousElementSibling.contains(e.target)) {
                        menu.style.display = 'none';
                    }
                });
            });
        },
        
        // 绑定事件
        bindEvents: function() {
            var self = this;
            
            // 监听错误事件
            window.addEventListener('error', function(e) {
                console.warn('JavaScript 错误:', e.message);
                self.handleError(e);
            });
            
            // 监听未处理的 Promise 拒绝
            window.addEventListener('unhandledrejection', function(e) {
                console.warn('未处理的 Promise 拒绝:', e.reason);
            });
        },
        
        // 错误处理
        handleError: function(error) {
            // 尝试恢复基本功能
            if (error.message.includes('jQuery') || error.message.includes('$')) {
                this.provideJQueryFallback();
            }
        }
    };
    
    // jQuery 备用实现
    function jQueryFallback(selector) {
        this.elements = [];
        
        if (typeof selector === 'string') {
            this.elements = Array.from(document.querySelectorAll(selector));
        } else if (selector && selector.nodeType) {
            this.elements = [selector];
        } else if (selector && typeof selector === 'object' && selector.length !== undefined) {
            this.elements = Array.from(selector);
        }
        
        this.length = this.elements.length;
        return this;
    }
    
    // jQuery 方法实现
    jQueryFallback.prototype = {
        each: function(callback) {
            this.elements.forEach(callback);
            return this;
        },
        
        on: function(event, handler) {
            this.elements.forEach(function(el) {
                el.addEventListener(event, handler);
            });
            return this;
        },
        
        off: function(event, handler) {
            this.elements.forEach(function(el) {
                el.removeEventListener(event, handler);
            });
            return this;
        },
        
        click: function(handler) {
            if (handler) {
                return this.on('click', handler);
            } else {
                this.elements.forEach(function(el) {
                    el.click();
                });
                return this;
            }
        },
        
        addClass: function(className) {
            this.elements.forEach(function(el) {
                el.classList.add(className);
            });
            return this;
        },
        
        removeClass: function(className) {
            this.elements.forEach(function(el) {
                el.classList.remove(className);
            });
            return this;
        },
        
        toggleClass: function(className) {
            this.elements.forEach(function(el) {
                el.classList.toggle(className);
            });
            return this;
        },
        
        hasClass: function(className) {
            return this.elements.some(function(el) {
                return el.classList.contains(className);
            });
        },
        
        html: function(content) {
            if (content !== undefined) {
                this.elements.forEach(function(el) {
                    el.innerHTML = content;
                });
                return this;
            } else {
                return this.elements[0] ? this.elements[0].innerHTML : '';
            }
        },
        
        text: function(content) {
            if (content !== undefined) {
                this.elements.forEach(function(el) {
                    el.textContent = content;
                });
                return this;
            } else {
                return this.elements[0] ? this.elements[0].textContent : '';
            }
        },
        
        hide: function() {
            this.elements.forEach(function(el) {
                el.style.display = 'none';
            });
            return this;
        },
        
        show: function() {
            this.elements.forEach(function(el) {
                el.style.display = '';
            });
            return this;
        },
        
        fadeIn: function(duration) {
            this.elements.forEach(function(el) {
                el.style.opacity = '0';
                el.style.display = '';
                var start = Date.now();
                var timer = setInterval(function() {
                    var progress = (Date.now() - start) / (duration || 400);
                    if (progress >= 1) {
                        el.style.opacity = '1';
                        clearInterval(timer);
                    } else {
                        el.style.opacity = progress;
                    }
                }, 16);
            });
            return this;
        },
        
        fadeOut: function(duration) {
            this.elements.forEach(function(el) {
                var start = Date.now();
                var startOpacity = parseFloat(getComputedStyle(el).opacity);
                var timer = setInterval(function() {
                    var progress = (Date.now() - start) / (duration || 400);
                    if (progress >= 1) {
                        el.style.display = 'none';
                        clearInterval(timer);
                    } else {
                        el.style.opacity = startOpacity * (1 - progress);
                    }
                }, 16);
            });
            return this;
        }
    };
    
    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ArgonFallback.init();
        });
    } else {
        ArgonFallback.init();
    }
})();