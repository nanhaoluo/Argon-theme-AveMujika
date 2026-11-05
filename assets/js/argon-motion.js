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

	var scrollAnimations = new Map();
	function isPageScroller(scroller) {
		return scroller === document.scrollingElement || scroller === document.documentElement || scroller === document.body;
	}
	function scrollLimit(scroller) {
		return Math.max(0, scroller.scrollHeight - (isPageScroller(scroller) ? window.innerHeight : scroller.clientHeight));
	}
	function clampScroll(scroller, value) {
		return Math.max(0, Math.min(scrollLimit(scroller), Number(value) || 0));
	}
	function finishScroll(state, completed) {
		if (scrollAnimations.get(state.scroller) !== state) return;
		scrollAnimations.delete(state.scroller);
		if (state.frame) cancelFrame(state.frame);
		if (state.virtualizer && state.virtualizer.endScroll) state.virtualizer.endScroll();
		if (completed && state.input !== 'wheel') {
			var target = state.target();
			if (target !== null) setScrollPosition(state.scroller, target);
		}
		state.scroller.style.setProperty('scroll-behavior', state.behavior, state.behaviorPriority);
		state.resolve(completed);
	}
	function cancelScroll(container) {
		if (arguments.length) {
			var state = scrollAnimations.get(resolveScroller(container));
			if (state) finishScroll(state, false);
		} else {
			Array.from(scrollAnimations.values()).forEach(function (state) { finishScroll(state, false); });
		}
	}
	function createScroll(scroller, input) {
		cancelScroll(scroller);
		var state = { scroller: scroller, input: input, frame: 0,
			behavior: scroller.style.getPropertyValue('scroll-behavior'),
			behaviorPriority: scroller.style.getPropertyPriority('scroll-behavior') };
		state.promise = new Promise(function (resolve) { state.resolve = resolve; });
		// A CSS/native smooth scroll must not ease each individual animation frame again.
		scroller.style.setProperty('scroll-behavior', 'auto', 'important');
		if (window.jQuery) window.jQuery(scroller).stop(true);
		scrollAnimations.set(scroller, state);
		return state;
	}
	function connected(scroller) {
		return isPageScroller(scroller) || scroller.isConnected !== false;
	}
	function preparePageScroll(state, target) {
		var virtualizer = isPageScroller(state.scroller) && window.ArgonLongContentVirtualizer;
		if (virtualizer && virtualizer.beginScroll) {
			state.virtualizer = virtualizer;
			virtualizer.beginScroll(target);
		}
	}

	function scrollTo(targetTop, options) {
		options = options || {};
		var scroller = resolveScroller(options.container);
		var state = createScroll(scroller, 'navigation');
		state.target = function () {
			var value = typeof targetTop === 'function' ? targetTop() : targetTop;
			var node = resolveElement(value);
			if (node) {
				if (node.isConnected === false) return null;
				value = scrollPosition(scroller) + node.getBoundingClientRect().top;
				if (!isPageScroller(scroller)) value -= scroller.getBoundingClientRect().top + scroller.clientTop;
			}
			return clampScroll(scroller, (Number(value) || 0) - (Number(options.offset) || 0));
		};
		var target = state.target();
		if (target === null) { finishScroll(state, false); return state.promise; }
		preparePageScroll(state, target);
		var start = scrollPosition(scroller);
		var animationDuration = prefersReducedMotion() ? 0 : options.duration == null
			? duration(options.compact ? 'scrollCompact' : 'scroll')
			: Math.max(0, Number(options.duration) || 0);
		if (options.duration == null && !options.compact && animationDuration) {
			animationDuration += Math.min(280, Math.sqrt(Math.abs(target - start)) * 2);
		}
		if (!animationDuration || Math.abs(target - start) < 1) {
			setScrollPosition(scroller, state.target());
			finishScroll(state, true);
			return state.promise;
		}
		var startedAt = null;
		function tick(timestamp) {
			if (!connected(scroller)) { finishScroll(state, false); return; }
			var currentTarget = state.target();
			if (currentTarget === null) { finishScroll(state, false); return; }
			if (startedAt === null) startedAt = timestamp;
			var progress = Math.min(1, (timestamp - startedAt) / animationDuration);
			// Gentle acceleration avoids the huge first-frame jump on very long articles.
			var eased = progress * progress * (3 - 2 * progress);
			setScrollPosition(scroller, start + (currentTarget - start) * eased);
			if (state.virtualizer && state.virtualizer.updateScroll) state.virtualizer.updateScroll();
			if (progress < 1) state.frame = frame(tick);
			else finishScroll(state, true);
		}
		state.frame = frame(tick);
		return state.promise;
	}

	function scrollBy(delta, options) {
		options = options || {};
		var scroller = resolveScroller(options.container);
		delta = Number(delta) || 0;
		var state = scrollAnimations.get(scroller);
		if (state && state.input === 'wheel') {
			var position = scrollPosition(scroller);
			state.destination = clampScroll(scroller, (delta * (state.destination - position) < 0 ? position : state.destination) + delta);
			return state.promise;
		}
		state = createScroll(scroller, 'wheel');
		state.destination = clampScroll(scroller, scrollPosition(scroller) + delta);
		if (prefersReducedMotion()) {
			setScrollPosition(scroller, state.destination); finishScroll(state, true); return state.promise;
		}
		var lastTime = null;
		var damping = Math.max(16, duration('fast') / 4);
		function tick(timestamp) {
			if (!connected(scroller)) { finishScroll(state, false); return; }
			state.destination = clampScroll(scroller, state.destination);
			var remaining = state.destination - scrollPosition(scroller);
			var elapsed = lastTime === null ? 16 : Math.min(64, Math.max(1, timestamp - lastTime));
			lastTime = timestamp;
			if (Math.abs(remaining) < 0.5) {
				setScrollPosition(scroller, state.destination); finishScroll(state, true); return;
			}
			var step = remaining * (1 - Math.exp(-elapsed / damping));
			// Integer scrollTop implementations must still make progress near the target.
			if (Math.abs(step) < 1) step = Math.sign(remaining) * Math.min(1, Math.abs(remaining));
			setScrollPosition(scroller, scrollPosition(scroller) + step);
			state.frame = frame(tick);
		}
		state.frame = frame(tick);
		return state.promise;
	}
	// Manual gestures interrupt a navigation; consecutive wheel events share one animation.
	document.addEventListener('wheel', function () {
		Array.from(scrollAnimations.values()).forEach(function (state) { if (state.input !== 'wheel') finishScroll(state, false); });
	}, { capture: true, passive: true });
	document.addEventListener('touchstart', function () { cancelScroll(); }, { capture: true, passive: true });
	document.addEventListener('pointerdown', function () { cancelScroll(); }, { capture: true, passive: true });
	document.addEventListener('keydown', function (event) {
		if (/^(ArrowUp|ArrowDown|PageUp|PageDown|Home|End| |Escape)$/.test(event.key)) cancelScroll();
	}, true);

	window.ArgonMotion = {
		duration: duration,
		easing: easing,
		fast: fast,
		isOutsideClick: isOutsideClick,
		prefersReducedMotion: prefersReducedMotion,
		scrollTo: scrollTo,
		scrollBy: scrollBy,
		cancelScroll: cancelScroll,
		isScrolling: function (container, input) {
			var state = scrollAnimations.get(resolveScroller(container));
			return !!(state && (!input || state.input === input));
		},
		nextFrame: function (callback) {
			frame(function () { frame(callback); });
		}
	};
})(window, document);
