(() => {
	'use strict';
	const sky = document.querySelector('.argon-aurora');
	if (!sky) return;

	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	const between = (min, max) => min + Math.random() * (max - min);
	const population = () => Math.floor(between(5, 10));
	const mix = (a, b, t) => a.map((v, i) => v + (b[i] - v) * t);
	const chroma = rgb => Math.max(...rgb) - Math.min(...rgb);
	const rgbValue = rgb => 'rgb(' + rgb.map(Math.round).join(',') + ')';
	// Ease only opacity at entry, peak and exit. Translation uses its own two linear keyframes.
	const fade = Array.from({ length: 17 }, (_, i) => (1 - Math.cos(2 * Math.PI * i / 16)) / 2);
	const lights = new Map();
	const probe = document.createElement('canvas');
	probe.width = probe.height = 1;
	const pixel = probe.getContext('2d', { willReadFrequently: true });
	let paletteKey, base, ground, lastTint;
	let birthTimer, tintTimer, targetCount, animated;

	function readColor(value) {
		pixel.fillStyle = value;
		pixel.fillRect(0, 0, 1, 1);
		return Array.from(pixel.getImageData(0, 0, 1, 1).data.slice(0, 3));
	}

	function sampleTint() {
		// Rotate around the neutral RGB axis: a grey theme naturally yields shades of grey.
		const mean = (base[0] + base[1] + base[2]) / 3;
		const angle = between(-55, 55) * Math.PI / 180;
		const gain = between(0.80, 1.12);
		const lift = between(0.04, 0.20);
		return base.map((value, i) => {
			const side = (base[(i + 1) % 3] - base[(i + 2) % 3]) / Math.sqrt(3);
			const rotated = mean + gain * ((value - mean) * Math.cos(angle) + side * Math.sin(angle));
			return Math.max(0, Math.min(255, rotated)) * (1 - lift) + 255 * lift;
		});
	}

	function updateTints() {
		if (!pixel) return;
		const css = getComputedStyle(sky);
		const accent = css.getPropertyValue('--aurora-color').trim();
		const key = accent + '/' + css.backgroundColor;
		const changed = key !== paletteKey;
		if (changed) {
			base = readColor(accent);
			ground = readColor(css.backgroundColor);
			lastTint = null;
			paletteKey = key;
		}

		// Sample layout and opacity together so every light sees the same instant in the flight.
		const scene = Array.from(lights, ([node, light]) => {
			if (changed || !light.choices) {
				light.choices = Array.from({ length: 6 }, sampleTint).concat([base]);
			}
			const rect = node.getBoundingClientRect();
			const style = getComputedStyle(node);
			// Other lights see the colour actually on screen, including an unfinished transition.
			const rgb = animated ? readColor(style.color) : light.choices[0];
			const strength = Number(style.opacity);
			return { node, light, rgb, x: rect.left + rect.width / 2, y: rect.top + rect.height / 2,
				width: rect.width, height: rect.height, alpha: strength * (light.diffuse ? 0.24 : 0.48) };
		});
		for (const item of scene) {
			let under = ground, expectedChroma = chroma(ground);
			const entering = animated && !item.light.painted;
			let entryTint = lastTint || base;
			for (const other of scene) {
				if (other === item) continue;
				const dx = (item.x - other.x) / ((item.width + other.width) * 0.36);
				const dy = (item.y - other.y) / ((item.height + other.height) * 0.36);
				const alpha = Math.max(0, 1 - dx * dx - dy * dy) * other.alpha;
				under = mix(under, other.rgb, alpha);
				if (entering) entryTint = mix(entryTint, other.rgb, alpha);
				expectedChroma += (chroma(other.rgb) - expectedChroma) * alpha;
			}
			// Compare the chroma left by source-over mixing to the chroma contributed by its layers.
			// The small floor avoids rejecting intentionally neutral colours for tiny rounding differences.
			let best = item.light.choices[0], leastLoss = Infinity;
			for (const tint of item.light.choices) {
				const coverage = Math.max(0.15, item.alpha);
				const expected = expectedChroma + (chroma(tint) - expectedChroma) * coverage;
				const loss = (expected - chroma(mix(under, tint, coverage))) / (expected + 6);
				if (loss < leastLoss) { best = tint; leastLoss = loss; }
				if (loss <= 0.075) break;
			}
			if (entering) {
				// Establish the outgoing/local hue before enabling the first native colour transition.
				item.node.style.setProperty('--stream-tint', rgbValue(entryTint));
				getComputedStyle(item.node).color;
				item.node.classList.add('has-tint');
			}
			const value = rgbValue(best);
			if (value !== item.light.painted) {
				item.node.style.setProperty('--stream-tint', value);
				item.light.painted = value;
			}
		}
	}

	function scheduleBirth() {
		if (!animated || document.hidden || birthTimer || lights.size >= targetCount) return;
		birthTimer = setTimeout(() => {
			birthTimer = null;
			if (lights.size < targetCount) addStream();
			scheduleBirth();
		}, between(650, 1100));
	}

	function addStream() {
		const stream = document.createElement('span');
		stream.className = 'argon-aurora-stream';
		const diffuse = !sky.querySelector('.is-diffuse');
		if (diffuse) stream.classList.add('is-diffuse');
		const angle = between(0, Math.PI * 2);
		const distance = between(6, 13);
		const x = Math.cos(angle) * distance;
		const y = Math.sin(angle) * distance;
		let left = between(0, 100), top = between(0, 100);
		if (animated && lights.size >= 3 && Math.random() < 0.3) {
			const other = sky.children[Math.floor(Math.random() * lights.size)].getBoundingClientRect();
			const halfStep = Math.min(innerWidth, innerHeight) / 200;
			left = (other.left + other.width / 2 - x * halfStep) / innerWidth * 100;
			top = (other.top + other.height / 2 - y * halfStep) / innerHeight * 100;
		}
		stream.style.left = Math.max(0, Math.min(100, left)) + '%';
		stream.style.top = Math.max(0, Math.min(100, top)) + '%';
		stream.style.setProperty('--stream-width', diffuse ? between(1.32, 1.55) : between(0.92, 1.22));
		stream.style.setProperty('--stream-height', diffuse ? between(1.12, 1.34) : between(0.84, 1.10));
		stream.style.setProperty('--stream-tilt', between(-36, 18) + 'deg');
		stream.style.setProperty('--stream-facing', Math.random() < 0.5 ? -1 : 1);
		sky.appendChild(stream);
		const light = { diffuse };
		lights.set(stream, light);
		if (!animated) return;

		const duration = between(5000, 10000);
		if (!diffuse) stream.style.setProperty('--wave-motion', 'argon-aurora-wave ' + duration + 'ms ease-in-out both');
		const flight = stream.animate({
			transform: ['translate(0, 0)', 'translate(' + x + 'vmin, ' + y + 'vmin)'],
			opacity: fade
		}, { id: 'argon-aurora-flight', duration, easing: 'linear', fill: 'both' });
		light.flight = flight;
		if (document.hidden) flight.pause();
		updateTints();
		flight.onfinish = () => {
			if (!lights.delete(stream)) return;
			if (pixel) lastTint = readColor(getComputedStyle(stream).color);
			flight.cancel();
			stream.remove();
			targetCount = population();
			if (diffuse || lights.size < 5) addStream();
			scheduleBirth();
		};
	}

	function stopTimers() {
		clearTimeout(birthTimer);
		clearInterval(tintTimer);
		birthTimer = tintTimer = null;
	}

	function resumeTimers() {
		if (!animated || document.hidden) return;
		scheduleBirth();
		tintTimer = setInterval(updateTints, 250);
	}

	function render() {
		stopTimers();
		lastTint = null;
		for (const light of lights.values()) light.flight?.cancel();
		lights.clear();
		sky.replaceChildren();
		animated = !reducedMotion.matches && typeof sky.animate === 'function';
		targetCount = population();
		if (animated) {
			addStream();
			resumeTimers();
		} else {
			for (let i = 0; i < targetCount; i++) addStream();
			updateTints();
		}
	}

	function syncWaveVisibility() {
		sky.style.setProperty('--wave-play-state', document.hidden ? 'paused' : 'running');
	}
	syncWaveVisibility();
	render();
	reducedMotion.addEventListener('change', render);
	document.addEventListener('visibilitychange', () => {
		syncWaveVisibility();
		stopTimers();
		for (const effect of sky.getAnimations?.({ subtree: true }) || []) {
			if (effect.id !== 'argon-aurora-flight' && effect.transitionProperty !== 'color') continue;
			if (document.hidden) effect.pause();
			else if (effect.playState === 'paused') effect.play();
		}
		resumeTimers();
	});
})();
