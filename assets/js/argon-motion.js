/*
 * Shared motion primitives for Argon frontend surfaces.
 */
(function (window, document) {
	'use strict';

	var frame = window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : function (callback) {
		return window.setTimeout(function () { callback(Date.now()); }, 16);
	};
	var cancelFrame = window.cancelAnimationFrame ? window.cancelAnimationFrame.bind(window) : window.clearTimeout.bind(window);
	var pointerDownTarget = null;
	var pointerDownSurface = null;
	var pointerResetTimer = 0;
	var popupSurfaceSelector = [
		'.dropdown-menu',
		'#navbar_global',
		'#leftbar',
		'#share_container',
		'.site-rss',
		'#fabtn_blog_settings_popup',
		'.modal-dialog',
		'.feedback-modal',
		'.flink-modal',
		'.ai-modal-content',
		'.git-easter-egg-modal',
		'.pcr-app',
		'.argon-image-viewer'
	].join(',');
	var durationTokens = {
		fast: ['--motion-fast-duration', 150],
		popup: ['--motion-fast-duration', 150],
		layer: ['--motion-fast-duration', 150],
		scroll: ['--motion-scroll-duration', 420],
		scrollCompact: ['--motion-scroll-compact-duration', 260],
		zoom: ['--motion-zoom-duration', 360]
	};

	function prefersReducedMotion() {
		return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	}

	function targetElement(target) {
		if (!target) return null;
		return target.nodeType === 1 ? target : target.parentElement;
	}

	function resolveElement(target) {
		if (typeof target === 'string') {
			try {
				return document.querySelector(target);
			} catch (error) {
				return null;
			}
		}
		if (target && target.jquery) target = target[0];
		return targetElement(target);
	}

	function syncAttribute(target, name, value) {
		if (!target) return;
		var elements = [];
		if (typeof target === 'string' && document.querySelectorAll) {
			try {
				elements = Array.prototype.slice.call(document.querySelectorAll(target));
			} catch (error) {
				return;
			}
		} else {
			var element = resolveElement(target);
			if (element) elements = [element];
		}
		elements.forEach(function (element) {
			if (element && element.setAttribute) element.setAttribute(name, value);
		});
	}

	/* Shared state toggle for fast popup, drawer and menu transitions. */
	function fast(target, isOpen, options) {
		options = options || {};
		var element = resolveElement(target);
		if (!element || !element.classList) return false;
		var className = options.className || 'show';
		var open = isOpen == null ? !element.classList.contains(className) : !!isOpen;
		element.classList.toggle(className, open);
		if (options.ariaHiddenTarget) syncAttribute(options.ariaHiddenTarget, 'aria-hidden', open ? 'false' : 'true');
		if (options.ariaExpandedTarget) syncAttribute(options.ariaExpandedTarget, 'aria-expanded', open ? 'true' : 'false');
		return open;
	}

	function closestPopupSurface(target) {
		var element = targetElement(target);
		if (!element || !element.closest) return null;
		return element.closest(popupSurfaceSelector);
	}

	function clearPointerOrigin() {
		pointerDownTarget = null;
		pointerDownSurface = null;
		pointerResetTimer = 0;
	}

	function rememberPointerOrigin(event) {
		if (pointerResetTimer) {
			window.clearTimeout(pointerResetTimer);
			pointerResetTimer = 0;
		}
		pointerDownTarget = targetElement(event.target);
		pointerDownSurface = closestPopupSurface(pointerDownTarget);
	}

	function schedulePointerReset() {
		if (pointerResetTimer) window.clearTimeout(pointerResetTimer);
		pointerResetTimer = window.setTimeout(clearPointerOrigin, 0);
	}

	function isOutsideClick(event, surface) {
		if (!event || !surface) return false;
		var clickTarget = targetElement(event.target);
		var downTarget = event.__argonPointerDownTarget || pointerDownTarget;
		return !!(clickTarget && !surface.contains(clickTarget) && (!downTarget || !surface.contains(downTarget)));
	}

	function guardDraggedOutsideClick(event) {
		var clickTarget = targetElement(event.target);
		event.__argonPointerDownTarget = pointerDownTarget;
		var draggedOutside = !!(pointerDownSurface && clickTarget && !pointerDownSurface.contains(clickTarget));
		clearPointerOrigin();
		if (!draggedOutside) return;
		event.__argonDraggedOutsidePopup = true;
		event.stopImmediatePropagation();
		event.stopPropagation();
	}

	if (window.PointerEvent) {
		document.addEventListener('pointerdown', rememberPointerOrigin, true);
		document.addEventListener('pointerup', schedulePointerReset, true);
		document.addEventListener('pointercancel', clearPointerOrigin, true);
	} else {
		document.addEventListener('mousedown', rememberPointerOrigin, true);
		document.addEventListener('mouseup', schedulePointerReset, true);
		document.addEventListener('touchstart', rememberPointerOrigin, true);
		document.addEventListener('touchend', schedulePointerReset, true);
		document.addEventListener('touchcancel', clearPointerOrigin, true);
	}
	document.addEventListener('click', guardDraggedOutsideClick, true);

	function rootStyleValue(propertyName) {
		if (!document.documentElement || !window.getComputedStyle) return '';
		return window.getComputedStyle(document.documentElement).getPropertyValue(propertyName).trim();
	}

	function parseTime(value, fallback) {
		var amount = parseFloat(value);
		if (!isFinite(amount)) return fallback;
		return value.indexOf('ms') !== -1 ? amount : amount * 1000;
	}

	function duration(name) {
		if (prefersReducedMotion()) return 0;
		var token = durationTokens[name] || durationTokens.scroll;
		return parseTime(rootStyleValue(token[0]), token[1]);
	}

	function easing(name) {
		var propertyName = name === 'exit' ? '--motion-ease-exit' : '--motion-ease-enter';
		return rootStyleValue(propertyName) || (name === 'exit' ? 'cubic-bezier(.4, 0, 1, 1)' : 'cubic-bezier(.16, 1, .3, 1)');
	}

	function resolveScroller(container) {
		if (container && container.jquery) container = container[0];
		if (!container || container === window || container === document || container === document.body || container === document.documentElement) {
			return document.scrollingElement || document.documentElement;
		}
		return container;
	}

	function scrollPosition(scroller) {
		return scroller === document.scrollingElement || scroller === document.documentElement || scroller === document.body
			? (window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0)
			: scroller.scrollTop;
	}

	function setScrollPosition(scroller, top) {
		if (scroller === document.scrollingElement || scroller === document.documentElement || scroller === document.body) {
			window.scrollTo(window.pageXOffset || 0, top);
		} else {
			scroller.scrollTop = top;
		}
	}

	function easeOutQuart(progress) {
		return 1 - Math.pow(1 - progress, 4);
	}

	function scrollTo(targetTop, options) {
		options = options || {};
		var scroller = resolveScroller(options.container);
		var start = scrollPosition(scroller);
		var target = Math.max(0, Number(targetTop) || 0);
		var animationDuration = options.duration == null
			? duration(options.compact ? 'scrollCompact' : 'scroll')
			: Math.max(0, Number(options.duration) || 0);

		if (scroller.__argonMotionScrollFrame) {
			cancelFrame(scroller.__argonMotionScrollFrame);
			scroller.__argonMotionScrollFrame = 0;
			if (scroller.__argonMotionScrollResolve) scroller.__argonMotionScrollResolve(false);
		}

		return new Promise(function (resolve) {
			scroller.__argonMotionScrollResolve = resolve;
			if (animationDuration === 0 || Math.abs(target - start) < 1) {
				setScrollPosition(scroller, target);
				scroller.__argonMotionScrollResolve = null;
				resolve(true);
				return;
			}

			var startedAt = null;
			var distance = target - start;
			function tick(timestamp) {
				if (startedAt === null) startedAt = timestamp;
				var progress = Math.min(1, (timestamp - startedAt) / animationDuration);
				setScrollPosition(scroller, start + distance * easeOutQuart(progress));
				if (progress < 1) {
					scroller.__argonMotionScrollFrame = frame(tick);
					return;
				}
				scroller.__argonMotionScrollFrame = 0;
				scroller.__argonMotionScrollResolve = null;
				resolve(true);
			}
			scroller.__argonMotionScrollFrame = frame(tick);
		});
	}

	window.ArgonMotion = {
		duration: duration,
		easing: easing,
		fast: fast,
		isOutsideClick: isOutsideClick,
		prefersReducedMotion: prefersReducedMotion,
		scrollTo: scrollTo,
		nextFrame: function (callback) {
			frame(function () { frame(callback); });
		}
	};
})(window, document);
