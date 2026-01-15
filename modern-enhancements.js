/**
 * Argon Theme - Modern UI Enhancements JavaScript
 * 现代化交互动画增强
 */

(function() {
    'use strict';

    // ========== 1. 触摸涟漪效果 ==========
    function createRipple(event, element) {
        // 只在移动端或触摸设备上启用
        if (window.matchMedia('(hover: hover)').matches) return;
        
        const rect = element.getBoundingClientRect();
        const ripple = document.createElement('span');
        const size = Math.max(rect.width, rect.height);
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (event.clientX || event.touches[0].clientX) - rect.left - size / 2 + 'px';
        ripple.style.top = (event.clientY || event.touches[0].clientY) - rect.top - size / 2 + 'px';
        ripple.className = 'touch-ripple';
        
        // 移除旧的涟漪
        const oldRipple = element.querySelector('.touch-ripple');
        if (oldRipple) oldRipple.remove();
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        // 动画结束后移除
        setTimeout(() => ripple.remove(), 600);
    }

    // 为可点击元素添加涟漪效果
    function initRippleEffect() {
        const rippleElements = document.querySelectorAll(
            '.btn, .card, .nav-link, .dropdown-item, .page-link, ' +
            '.leftbar-mobile-menu-item > a, .leftbar-mobile-action, ' +
            '.fabtn, .comment-reply, .tag, .badge'
        );
        
        rippleElements.forEach(el => {
            if (el.dataset.rippleInit) return;
            el.dataset.rippleInit = 'true';
            
            el.addEventListener('touchstart', function(e) {
                createRipple(e, this);
            }, { passive: true });
        });
    }

    // ========== 2. 图片加载动画 ==========
    function initImageLoadAnimation() {
        const images = document.querySelectorAll('article img[loading="lazy"], .post-thumbnail img');
        
        images.forEach(img => {
            if (img.dataset.loadAnimInit) return;
            img.dataset.loadAnimInit = 'true';
            
            if (img.complete) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', function() {
                    this.classList.add('loaded');
                });
            }
        });
    }

    // ========== 3. 滚动入场动画 (Intersection Observer) ==========
    function initScrollAnimations() {
        if (!('IntersectionObserver' in window)) return;
        
        const animatedElements = document.querySelectorAll(
            '.article-list article.post, .comment-item, .timeline-item, ' +
            '.friend-link-item, #leftbar .card, #rightbar .card'
        );
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        animatedElements.forEach(el => {
            if (!el.classList.contains('animate-in')) {
                observer.observe(el);
            }
        });
    }

    // ========== 4. 平滑滚动增强 ==========
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            if (anchor.dataset.smoothScrollInit) return;
            anchor.dataset.smoothScrollInit = 'true';
            
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // ========== 5. 页面加载进度条 ==========
    function initLoadingBar() {
        // 检查是否已存在
        if (document.getElementById('page-loading-bar')) return;
        
        const bar = document.createElement('div');
        bar.id = 'page-loading-bar';
        bar.style.width = '0%';
        document.body.appendChild(bar);
        
        // 模拟加载进度
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress >= 90) {
                clearInterval(interval);
                progress = 90;
            }
            bar.style.width = progress + '%';
        }, 100);
        
        // 页面加载完成
        window.addEventListener('load', () => {
            clearInterval(interval);
            bar.style.width = '100%';
            setTimeout(() => {
                bar.style.opacity = '0';
                setTimeout(() => bar.remove(), 300);
            }, 200);
        });
    }

    // ========== 6. PJAX 加载动画 ==========
    function initPjaxAnimations() {
        if (typeof jQuery === 'undefined') return;
        
        jQuery(document).on('pjax:start', function() {
            jQuery('#primary').addClass('pjax-loading');
            
            // 显示加载进度条
            let bar = document.getElementById('page-loading-bar');
            if (!bar) {
                bar = document.createElement('div');
                bar.id = 'page-loading-bar';
                document.body.appendChild(bar);
            }
            bar.style.opacity = '1';
            bar.style.width = '30%';
            
            setTimeout(() => bar.style.width = '60%', 200);
        });
        
        jQuery(document).on('pjax:end', function() {
            jQuery('#primary').removeClass('pjax-loading');
            
            const bar = document.getElementById('page-loading-bar');
            if (bar) {
                bar.style.width = '100%';
                setTimeout(() => {
                    bar.style.opacity = '0';
                    setTimeout(() => bar.remove(), 300);
                }, 200);
            }
            
            // 重新初始化动画
            setTimeout(() => {
                initRippleEffect();
                initImageLoadAnimation();
                initScrollAnimations();
                initSmoothScroll();
            }, 100);
        });
    }

    // ========== 7. 卡片倾斜效果 (桌面端) ==========
    function initCardTiltEffect() {
        if (!window.matchMedia('(hover: hover)').matches) return;
        
        const cards = document.querySelectorAll('article.post.card');
        
        cards.forEach(card => {
            if (card.dataset.tiltInit) return;
            card.dataset.tiltInit = 'true';
            
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    }

    // ========== 8. 浮动按钮显示/隐藏动画 ==========
    function initFabAnimation() {
        const fab = document.getElementById('float_action_buttons');
        if (!fab) return;
        
        let lastScrollY = window.scrollY;
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const currentScrollY = window.scrollY;
                    
                    // 向下滚动时隐藏，向上滚动时显示
                    if (currentScrollY > lastScrollY && currentScrollY > 300) {
                        fab.style.transform = 'translateY(100px)';
                        fab.style.opacity = '0';
                    } else {
                        fab.style.transform = '';
                        fab.style.opacity = '';
                    }
                    
                    lastScrollY = currentScrollY;
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }

    // ========== 9. 主题切换动画增强 ==========
    function initThemeTransition() {
        // 监听暗色模式切换
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const html = document.documentElement;
                    if (html.classList.contains('darkmode') || !html.classList.contains('darkmode')) {
                        // 添加过渡类
                        html.classList.add('theme-transitioning');
                        setTimeout(() => {
                            html.classList.remove('theme-transitioning');
                        }, 300);
                    }
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // ========== 10. 减少动画偏好检测 ==========
    function checkReducedMotion() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('reduced-motion');
        }
    }

    // ========== 初始化 ==========
    function init() {
        checkReducedMotion();
        initRippleEffect();
        initImageLoadAnimation();
        initScrollAnimations();
        initSmoothScroll();
        initPjaxAnimations();
        initThemeTransition();
        
        // 桌面端特效
        if (window.matchMedia('(hover: hover)').matches) {
            // initCardTiltEffect(); // 可选：卡片倾斜效果
        }
        
        // 移动端特效
        if (!window.matchMedia('(hover: hover)').matches) {
            // initFabAnimation(); // 可选：浮动按钮滚动隐藏
        }
    }

    // DOM 加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // 首次加载时显示进度条
    if (document.readyState !== 'complete') {
        initLoadingBar();
    }

})();
