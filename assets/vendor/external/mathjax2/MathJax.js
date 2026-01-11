/* MathJax 2 - Local Fallback */
(function() {
    'use strict';
    
    console.warn('MathJax 2 本地备用版本 - 功能受限');
    
    // 创建简化的 MathJax 对象
    window.MathJax = window.MathJax || {};
    
    window.MathJax.Hub = {
        Config: function(config) {
            console.log('MathJax 2 配置已设置（备用版本）');
            this.config = config;
        },
        
        Queue: function(callback) {
            if (typeof callback === 'function') {
                setTimeout(callback, 100);
            }
            return this;
        },
        
        Typeset: function(element) {
            console.warn('MathJax 2 渲染功能不可用 - 使用备用版本');
            // 简单的样式处理
            var mathElements = element ? 
                element.querySelectorAll('script[type*="math"]') : 
                document.querySelectorAll('script[type*="math"]');
                
            mathElements.forEach(function(script) {
                var span = document.createElement('span');
                span.textContent = script.textContent;
                span.style.fontStyle = 'italic';
                span.style.color = '#666';
                span.title = '数学公式渲染服务不可用';
                script.parentNode.insertBefore(span, script);
                script.style.display = 'none';
            });
        },
        
        Register: {
            StartupHook: function(hook, callback) {
                if (typeof callback === 'function') {
                    setTimeout(callback, 100);
                }
            }
        }
    };
    
    // 自动配置
    if (window.MathJax.Hub.Config) {
        window.MathJax.Hub.Config({
            messageStyle: "none",
            tex2jax: {
                inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],
                displayMath: [['$$','$$']],
                processEscapes: true,
                skipTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
            }
        });
    }
})();