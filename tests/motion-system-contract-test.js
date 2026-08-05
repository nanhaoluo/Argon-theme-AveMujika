'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const motionScript = read('assets/js/argon-motion.js');
const stylesheet = read('style.css');
const themeScript = read('argontheme.js');

assert.match(motionScript, /function fast\(target, isOpen, options\)/, 'ArgonMotion.fast is missing');
assert.match(motionScript, /fast:\s*fast,/, 'ArgonMotion.fast is not exported');
assert.match(motionScript, /fast:\s*\['--motion-fast-duration',\s*150\]/, 'fast duration token is not defined');
assert.match(motionScript, /popup:\s*\['--motion-fast-duration',\s*150\]/, 'popup duration is not routed through fast');
assert.match(motionScript, /layer:\s*\['--motion-fast-duration',\s*150\]/, 'layer duration is not routed through fast');
assert.match(stylesheet, /--motion-fast-duration:\s*150ms;/, 'shared fast CSS token is missing');
assert.match(stylesheet, /--motion-popup-duration:\s*var\(--motion-fast-duration\);/, 'popup CSS token is not aliased to fast');
assert.match(stylesheet, /--motion-layer-duration:\s*var\(--motion-fast-duration\);/, 'layer CSS token is not aliased to fast');
assert.match(themeScript, /window\.ArgonMotion\.fast\(document\.documentElement, open,/, 'mobile sidebar does not use ArgonMotion.fast');
assert.match(stylesheet, /animation: argonDrawerContentIn var\(--motion-fast-duration\)/, 'drawer content does not use fast animation');
assert.match(themeScript, /#comment_edit_history \.modal-body\"\)\.fadeIn\(window\.ArgonMotion\.duration\('fast'\)\)/, 'comment history content still uses an uncoordinated popup duration');
assert.doesNotMatch(read('ai-summary-query.php'), /transition:\s*left\s+0\.35s/, 'AI verification sidebar still uses the old slow left transition');
assert.doesNotMatch(read('inc/special-page-surface.php'), /transition:\s*left\s+0\.35s/, 'special page sidebar still uses the old slow left transition');

['friend-links.php', 'feedback.php', 'ai-summary-query.php', 'assets/js/easter-egg.js'].forEach((file) => {
	assert.match(read(file), /ArgonMotion\.fast\(/, `${file} does not use the shared popup state method`);
});

const classNames = new Set();
const attributes = {};
const target = {
	nodeType: 1,
	classList: {
		contains: (name) => classNames.has(name),
		toggle: (name, force) => {
			if (force) classNames.add(name);
			else classNames.delete(name);
		}
	},
	setAttribute: (name, value) => { attributes[name] = value; }
};
const trigger = { nodeType: 1, setAttribute: (name, value) => { attributes[`trigger:${name}`] = value; } };
const styles = { '--motion-fast-duration': '150ms' };
const documentStub = {
	documentElement: target,
	querySelector: (selector) => selector === '#panel' ? target : selector === '#trigger' ? trigger : null,
	querySelectorAll: (selector) => selector === '#panel' ? [target] : selector === '#trigger' ? [trigger] : [],
	addEventListener: () => {},
	scrollingElement: target
};
const windowStub = {
	document: documentStub,
	PointerEvent: null,
	matchMedia: () => ({ matches: false }),
	getComputedStyle: () => ({ getPropertyValue: (name) => styles[name] || '' }),
	setTimeout,
	clearTimeout,
	requestAnimationFrame: (callback) => setTimeout(() => callback(Date.now()), 0),
	cancelAnimationFrame: clearTimeout
};
vm.runInNewContext(motionScript, { window: windowStub, document: documentStub, isFinite });

assert.strictEqual(windowStub.ArgonMotion.duration('fast'), 150, 'fast duration should resolve to 150ms');
assert.strictEqual(windowStub.ArgonMotion.fast(target, true, {
	className: 'active',
	ariaHiddenTarget: '#panel',
	ariaExpandedTarget: '#trigger'
}), true, 'fast should open the target');
assert.ok(classNames.has('active'), 'fast should add the active class');
assert.strictEqual(attributes['aria-hidden'], 'false', 'fast should reveal the ARIA target');
assert.strictEqual(attributes['trigger:aria-expanded'], 'true', 'fast should expand the trigger');
assert.strictEqual(windowStub.ArgonMotion.fast(target, false, {
	className: 'active',
	ariaHiddenTarget: '#panel',
	ariaExpandedTarget: '#trigger'
}), false, 'fast should close the target');
assert.ok(!classNames.has('active'), 'fast should remove the active class');
assert.strictEqual(attributes['aria-hidden'], 'true', 'fast should hide the ARIA target');
assert.strictEqual(attributes['trigger:aria-expanded'], 'false', 'fast should collapse the trigger');

process.stdout.write('motion system contract test passed.\n');
