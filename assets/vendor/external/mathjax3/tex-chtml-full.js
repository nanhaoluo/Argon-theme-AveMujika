/* MathJax 3 - Local Fallback */
(function() {
    'use strict';
    
    console.warn('MathJax 3 本地备用版本 - 功能受限');
    
    // 创建简化的 MathJax 对象
    window.MathJax = window.MathJax || {
        tex: {
            inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],
            displayMath: [['$$','$$']],
            processEscapes: true,
            packages: {'[+]': ['noerrors']}
        },
        options: {
            skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
            ignoreHtmlClass: 'tex2jax_ignore',
            processHtmlClass: 'tex2jax_process'
        },
        loader: {
            load: ['[tex]/noerrors']
        },
        startup: {
            ready: function() {
                console.log('MathJax 3 备用版本已就绪');
                MathJax.startup.defaultReady();
            },
            defaultReady: function() {
                // 简化的渲染函数
                MathJax.typesetPromise = function(elements) {
                    return new Promise(function(resolve) {
                        console.warn('MathJax 渲染功能不可用 - 使用备用版本');
                        resolve();
                    });
                };
            }
        }
    };
    
    // 简化的渲染函数
    window.MathJax.typesetPromise = function(elements) {
        return new Promise(function(resolve) {
            console.warn('数学公式渲染服务不可用');
            // 尝试简单的文本替换
            var mathElements = document.querySelectorAll('.tex2jax_process, .MathJax');
            mathElements.forEach(function(el) {
                if (el.textContent.includes('$')) {
                    el.style.fontStyle = 'italic';
                    el.style.color = '#666';
                    el.title = '数学公式渲染服务不可用';
                }
            });
            resolve();
        });
    };
    
    // 触发启动
    if (MathJax.startup && MathJax.startup.ready) {
        MathJax.startup.ready();
    }
})();