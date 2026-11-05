'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const errorContent = fs.readFileSync(path.join(root, '404.php'), 'utf8').match(/<main\b[\s\S]*?<\/main>/)[0]
	.replace(/<\?php _e\("([^"]+)", "argon"\); \?>/g, '$1').replace(/<\?php[\s\S]*?\?>/g, '');

(async () => {
	const browser = await chromium.launch();
	try {
		const page = await browser.newPage({ reducedMotion: 'reduce' });
		const sky = page.locator('.argon-aurora');
		const staticLayout = () => sky.evaluate(el => [...el.children].map(stream => {
			const { left, top, width, height } = getComputedStyle(stream);
			return [left, top, width, height];
		}));
		await page.route('https://aurora.test/**', route => {
			const file = new URL(route.request().url()).pathname;
			if (file === '/') return route.fulfill({ contentType: 'text/html', body: `
				<link rel="stylesheet" href="/assets/css/aurora-background.css"><style>:root{--themecolor:#197e50}</style>
				<body class="argon-404"><div class="argon-aurora" aria-hidden="true"></div>
				${errorContent}<script src="/assets/js/aurora-background.js"></script>` });
			return route.fulfill({ path: path.join(root, file) });
		});
		await page.goto('https://aurora.test/');
		const first = await staticLayout();
		assert.ok(first.length >= 5 && first.length <= 9);
		await page.reload();
		const next = await staticLayout();
		assert.ok(next.length >= 5 && next.length <= 9);
		assert.notDeepEqual(next[0].slice(0, 2), first[0].slice(0, 2), 'positions vary on refresh');
		assert.notDeepEqual(next[0].slice(2), first[0].slice(2), 'sizes vary on refresh');
		assert.equal(await sky.evaluate(el => el.getAnimations({ subtree: true }).length), 0);
		assert.equal(await sky.locator('.is-diffuse').count(), 1);
		const ribbon = sky.locator('.argon-aurora-stream:not(.is-diffuse)').first();
		const mask = await ribbon.evaluate(n => getComputedStyle(n, '::after').maskImage);
		assert.ok(mask.includes('aurora-wave.svg'), 'the static aurora keeps its curved silhouette');
		assert.ok(await sky.evaluate(el => new Set([...el.children].map(n => n.style.getPropertyValue('--stream-tint'))).size > 1));
		for (const width of [1100, 240]) {
			await page.setViewportSize({ width, height: 700 });
			const layout = await page.evaluate(() => {
				const box = selector => document.querySelector(selector).getBoundingClientRect();
				const textStart = selector => {
					const range = document.createRange();
					range.selectNodeContents(document.querySelector(selector).firstChild);
					return range.getBoundingClientRect().left;
				};
				const copy = box('.argon-error-copy'), buttons = [...document.querySelectorAll('.argon-error-actions a')].map(n => n.getBoundingClientRect());
				const rows = buttons[0].top === buttons[1].top ? [(buttons[0].left + buttons[1].right) / 2] : buttons.map(r => (r.left + r.right) / 2);
				return { left: box('main').left, mid: (copy.left + copy.right) / 2, rows, heading: textStart('h1'), message: textStart('.argon-error-message') };
			});
			assert.equal(layout.left, 0);
			if (width === 1100) assert.ok(layout.mid < width / 2);
			assert.ok(Math.abs(layout.heading - layout.message) < 1, 'heading and message share a left edge');
			assert.equal(layout.rows.length, width === 1100 ? 1 : 2);
			assert.ok(layout.rows.every(mid => Math.abs(mid - layout.mid) < 1), 'each button row centres under the copy');
		}
		await page.setViewportSize({ width: 1100, height: 700 });
		await page.locator('.argon-error-actions a').first().click({ trial: true });
		await sky.evaluate(el => {
			window.birthTimes = [];
			new MutationObserver(records => {
				for (const record of records) for (const node of record.addedNodes) window.birthTimes.push(performance.now());
			}).observe(el, {childList:true});
		});

		await page.evaluate(() => {
			const random = Math.random;
			Math.random = () => { Math.random = random; return 0; };
		});
		await page.emulateMedia({ reducedMotion: 'no-preference' });
		await page.waitForFunction(() => window.birthTimes.length >= 1);
		assert.equal(await sky.locator('span').count(), 1, 'enter with just one fading light');
		await page.waitForFunction(() => window.birthTimes.length >= 5);
		const starts = await page.evaluate(() => window.birthTimes.slice(0, 5));
		assert.ok(starts.slice(1).every((v, i) => v - starts[i] >= 600), 'separate visible entries');
		await sky.evaluate(el => el.getAnimations({subtree:true}).forEach(f => f.pause()));
		const wave = await ribbon.evaluate(n => {
			const fold = n.getAnimations({ subtree: true }).find(a => a.animationName === 'argon-aurora-wave');
			const flight = n.getAnimations().find(a => a.id === 'argon-aurora-flight');
			const duration = flight.effect.getTiming().duration;
			fold.currentTime = duration * 0.2;
			const early = getComputedStyle(n, '::after').transform;
			fold.currentTime = duration * 0.6;
			return { sameDuration: duration === fold.effect.getTiming().duration, early, later: getComputedStyle(n, '::after').transform };
		});
		assert.ok(wave.sameDuration && wave.early !== wave.later, 'wave changes shape during the flight');
		const samples = await sky.evaluate(el => {
			const flight = el.firstElementChild.getAnimations().find(a => a.id === 'argon-aurora-flight');
			flight.pause();
			const { delay, duration } = flight.effect.getTiming();
			return [0, 0.25, 0.5, 0.75, 0.99].map(t => {
				flight.currentTime = delay + t * duration;
				const style = getComputedStyle(flight.effect.target);
				const m = new DOMMatrix(style.transform);
				return { x: m.m41, y: m.m42, opacity: Number(style.opacity) };
			});
		});
		for (const axis of ['x', 'y']) {
			for (let i = 2; i < 4; i++) {
				assert.ok(Math.abs((samples[i][axis] - samples[i-1][axis]) - (samples[1][axis] - samples[0][axis])) < 0.01, 'equal distance in equal time');
			}
		}
		assert.ok(Math.hypot(samples[3].x, samples[3].y) > 0);
		assert.ok(samples[0].opacity === 0 && samples[2].opacity === 1 && samples[4].opacity < 0.01, 'last fraction of the flight fades out gently');

		// Freeze the same five flights at peak opacity. Only their overlap changes.
		const separated = await sky.evaluate(async el => {
			const originalRandom = Math.random;
			let seed = 1;
			Math.random = () => {
				seed = (Math.imul(1664525, seed) + 1013904223) >>> 0;
				return seed / 2 ** 32;
			};
			try {
				[...el.children].forEach((node, index) => {
					const flight = node.getAnimations().find(a => a.id === 'argon-aurora-flight');
					flight.pause();
					flight.currentTime = flight.effect.getTiming().duration / 2;
					node.style.left = index * 170 + '%';
					node.style.top = '50%';
				});
				document.documentElement.style.setProperty('--themecolor', '#fa7296');
				await new Promise(resolve => setTimeout(resolve, 2900));
				return [...el.children].map(n => n.style.getPropertyValue('--stream-tint'));
			} finally { Math.random = originalRandom; }
		});
		await sky.evaluate(el => { for (const n of el.children) n.style.left = '50%'; });
		await page.waitForFunction(before => [...document.querySelectorAll('.argon-aurora-stream')]
			.some((n, i) => n.style.getPropertyValue('--stream-tint') !== before[i]), separated, {timeout: 3000});

		// Inspect the colour actually displayed by both halo and wave during a collision.
		const colours = await sky.evaluate(el => {
			const transition = el.getAnimations({ subtree: true }).find(a => a.transitionProperty === 'color');
			const node = transition.effect.target;
			const { duration } = transition.effect.getTiming();
			transition.pause();
			const samples = [0, 0.25, 0.5, 0.75, 1].map(t => {
				transition.currentTime = t * duration;
				return [getComputedStyle(node).color, getComputedStyle(node, '::before').color, getComputedStyle(node, '::after').color];
			});
			transition.play();
			return { duration, samples };
		});
		assert.ok(colours.duration >= 2000);
		assert.equal(new Set(colours.samples.map(([colour]) => colour)).size, 5, 'collision renders intermediate hues');
		assert.ok(colours.samples.every(([colour, halo, wave]) => colour === halo && colour === wave));

		// Force a target of nine, allow its staggered births to occur, then force five.
		await page.evaluate(() => { window.originalRandom = Math.random; Math.random = () => 0.999; });
		async function finishRegular() {
			const state = await sky.evaluate(async el => {
				const stream = el.querySelector('span:not(.is-diffuse)');
				const flight = stream.getAnimations().find(a => a.id === 'argon-aurora-flight');
				const { duration } = flight.effect.getTiming();
				const finished = new Promise(resolve => flight.addEventListener('finish', resolve, { once: true }));
				flight.finish();
				await finished;
				return { count: el.childElementCount, duration, removed: !stream.isConnected };
			});
			assert.ok(state.count >= 5 && state.count <= 9 && state.removed);
			assert.ok(state.duration >= 5000 && state.duration <= 10000);
			return state.count;
		}
		await finishRegular();
		await page.waitForFunction(() => document.querySelector('.argon-aurora').childElementCount === 9);
		await page.evaluate(() => { Math.random = () => 0; });
		for (let i = 0; i < 5; i++) await finishRegular();
		assert.equal(await sky.locator('span').count(), 5);
		assert.equal(await sky.locator('.is-diffuse').count(), 1);
		await page.evaluate(() => { Math.random = window.originalRandom; });
		await page.emulateMedia({ reducedMotion: 'reduce' });
		await page.waitForFunction(() => document.getAnimations().length === 0);
		const still = await staticLayout();
		await page.waitForTimeout(10100);
		assert.deepEqual(await staticLayout(), still, 'static lights stay in place beyond a full flight');
		console.log('[PASS] Aurora population, straight uniform travel, fades, lifetime and randomized static layouts');
	} finally {
		await browser.close();
	}
})().catch(error => { console.error(error); process.exitCode = 1; });
