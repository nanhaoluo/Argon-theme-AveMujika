/**
 * Git 徽章彩蛋
 * 点击超过10次触发
 */
(function() {
    'use strict';
    
    var clickCount = 0;
    var isInit = false;
    var safeDistance = 80; // 安全距离，鼠标靠近这个距离就跑
    var checkInterval = null;
    
    function createModal() {
        if (document.getElementById('git-easter-egg-overlay')) return;
        
        var overlay = document.createElement('div');
        overlay.className = 'git-easter-egg-overlay';
        overlay.id = 'git-easter-egg-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.innerHTML = 
            '<div class="git-easter-egg-modal">' +
                '<div class="git-easter-egg-emoji">😈</div>' +
                '<div class="git-easter-egg-content">你点了这么多次点出了魔丸=v=</div>' +
                '<div class="git-easter-egg-buttons">' +
                    '<button class="git-easter-egg-btn btn-good">好</button>' +
                '</div>' +
            '</div>' +
            '<button class="git-easter-egg-btn btn-bad" id="runaway-btn">坏</button>';
        
        document.body.appendChild(overlay);
        return overlay;
    }
    
    var mouseX = 0, mouseY = 0;
    
    function showModal() {
        var overlay = document.getElementById('git-easter-egg-overlay') || createModal();
        var badBtn = document.getElementById('runaway-btn');
        var goodBtn = overlay.querySelector('.btn-good');
        
        // 获取好按钮位置，把坏按钮放在旁边
        setTimeout(function() {
            var goodRect = goodBtn.getBoundingClientRect();
            badBtn.style.transition = 'none';
            badBtn.style.left = (goodRect.right + 15) + 'px';
            badBtn.style.top = goodRect.top + 'px';
            badBtn.style.transform = 'translate(0, 0)';
            
            setTimeout(function() {
                badBtn.style.transition = '';
                document.documentElement.classList.add('argon-modal-locked');
                window.ArgonMotion.fast(overlay, true, { className: 'show' });
                overlay.setAttribute('aria-hidden', 'false');
                startTracking();
            }, 20);
        }, 10);
    }
    
    function hideModal() {
        var overlay = document.getElementById('git-easter-egg-overlay');
        if (overlay) {
            window.ArgonMotion.fast(overlay, false, { className: 'show' });
            overlay.setAttribute('aria-hidden', 'true');
        }
        document.documentElement.classList.remove('argon-modal-locked');
        stopTracking();
    }
    
    function startTracking() {
        if (checkInterval) return;
        checkInterval = setInterval(checkDistance, 30);
    }
    
    function stopTracking() {
        if (checkInterval) {
            clearInterval(checkInterval);
            checkInterval = null;
        }
    }
    
    function checkDistance() {
        var badBtn = document.getElementById('runaway-btn');
        var overlay = document.getElementById('git-easter-egg-overlay');
        if (!badBtn || !overlay || !overlay.classList.contains('show')) return;
        
        var rect = badBtn.getBoundingClientRect();
        var btnCenterX = rect.left + rect.width / 2;
        var btnCenterY = rect.top + rect.height / 2;
        
        var distance = Math.sqrt(
            Math.pow(mouseX - btnCenterX, 2) + 
            Math.pow(mouseY - btnCenterY, 2)
        );
        
        // 鼠标靠近就跑
        if (distance < safeDistance) {
            runAwayFromMouse(badBtn, rect);
        }
    }
    
    function runAwayFromMouse(btn, rect) {
        var btnCenterX = rect.left + rect.width / 2;
        var btnCenterY = rect.top + rect.height / 2;
        
        // 计算逃跑方向（远离鼠标）
        var angle = Math.atan2(btnCenterY - mouseY, btnCenterX - mouseX);
        
        // 逃跑距离
        var escapeDistance = 120 + Math.random() * 80;
        
        var newX = btnCenterX + Math.cos(angle) * escapeDistance - btn.offsetWidth / 2;
        var newY = btnCenterY + Math.sin(angle) * escapeDistance - btn.offsetHeight / 2;
        
        // 边界检测
        var padding = 20;
        var maxX = window.innerWidth - btn.offsetWidth - padding;
        var maxY = window.innerHeight - btn.offsetHeight - padding;
        
        // 如果跑出边界，换个方向
        if (newX < padding || newX > maxX || newY < padding || newY > maxY) {
            // 尝试跑向"好"按钮附近，引导用户点好
            var goodBtn = document.querySelector('.btn-good');
            if (goodBtn) {
                var goodRect = goodBtn.getBoundingClientRect();
                // 随机出现在好按钮周围
                var positions = [
                    { x: goodRect.left - btn.offsetWidth - 20, y: goodRect.top },
                    { x: goodRect.right + 20, y: goodRect.top },
                    { x: goodRect.left, y: goodRect.bottom + 20 },
                    { x: goodRect.left, y: goodRect.top - btn.offsetHeight - 20 }
                ];
                var pos = positions[Math.floor(Math.random() * positions.length)];
                newX = pos.x;
                newY = pos.y;
            }
        }
        
        // 确保在屏幕内
        newX = Math.max(padding, Math.min(maxX, newX));
        newY = Math.max(padding, Math.min(maxY, newY));
        
        btn.style.left = newX + 'px';
        btn.style.top = newY + 'px';
        
        // 随机轻微旋转
        var rotation = (Math.random() - 0.5) * 15;
        btn.style.transform = 'translate(0, 0) rotate(' + rotation + 'deg)';
    }
    
    function init() {
        if (isInit) return;
        isInit = true;
        
        var badge = document.getElementById('git-badge');
        if (!badge) return;
        
        createModal();
        
        var overlay = document.getElementById('git-easter-egg-overlay');
        var goodBtn = overlay.querySelector('.btn-good');
        var badBtn = document.getElementById('runaway-btn');
        
        // 追踪鼠标位置
        document.addEventListener('mousemove', function(e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });
        
        // 点击徽章
        badge.addEventListener('click', function(e) {
            e.preventDefault();
            clickCount++;
            if (clickCount > 10) {
                showModal();
                clickCount = 0;
            }
        });
        
        // 好按钮关闭
        goodBtn.addEventListener('click', function() {
            hideModal();
        });
        
        // 坏按钮 - 永远点不到，但以防万一
        badBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        
        // 移动端触摸追踪
        document.addEventListener('touchmove', function(e) {
            if (e.touches.length > 0) {
                mouseX = e.touches[0].clientX;
                mouseY = e.touches[0].clientY;
            }
        });
        
        badBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
        });
        
        // 禁止选中
        overlay.addEventListener('mousedown', function(e) {
            e.preventDefault();
        });
        
        overlay.addEventListener('selectstart', function(e) {
            e.preventDefault();
        });

        overlay.addEventListener('click', function(e) {
            var modal = overlay.querySelector('.git-easter-egg-modal');
            if (e.target === overlay && window.ArgonMotion.isOutsideClick(e, modal)) {
                hideModal();
            }
        });
    }
    
    // DOM 加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
