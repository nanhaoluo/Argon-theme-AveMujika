/* Geetest GT4 - Local Fallback */
(function() {
	'use strict';
	
	// 简化的 Geetest 备用实现
	window.initGeetest4 = function(config, callback) {
		console.warn('Geetest GT4 本地备用版本 - 功能受限');
		
		// 创建一个简单的验证码替代
		var captcha = {
			appendTo: function(selector) {
				var container = document.querySelector(selector);
				if (container) {
					container.innerHTML = '<div style="padding: 20px; border: 1px solid #ddd; text-align: center; background: #f9f9f9;">' +
						'<p>验证码服务暂时不可用，请稍后重试</p>' +
						'<button type="button" onclick="this.parentNode.parentNode.style.display=\'none\'" style="padding: 5px 15px;">跳过验证</button>' +
						'</div>';
				}
				return this;
			},
			onReady: function(fn) {
				setTimeout(fn, 100);
				return this;
			},
			onSuccess: function(fn) {
				// 模拟成功回调
				return this;
			},
			onError: function(fn) {
				return this;
			},
			onClose: function(fn) {
				// 备用版本不支持关闭事件，但提供空实现避免错误
				return this;
			},
			getValidate: function() {
				return {
					lot_number: 'fallback_' + Date.now(),
					captcha_output: 'fallback_output',
					pass_token: 'fallback_token',
					gen_time: Date.now()
				};
			}
		};
		
		if (typeof callback === 'function') {
			callback(captcha);
		}
	};
})();