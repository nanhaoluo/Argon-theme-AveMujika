'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..');
const themeScript = fs.readFileSync(path.join(root, 'argontheme.js'), 'utf8');
const stylesheet = fs.readFileSync(path.join(root, 'style.css'), 'utf8');

function extractFunction(name) {
	const start = themeScript.indexOf(`function ${name}(`);
	if (start < 0) return null;
	const openBrace = themeScript.indexOf('{', start);
	let depth = 0;
	let quote = null;
	let escaped = false;
	for (let i = openBrace; i < themeScript.length; i += 1) {
		const char = themeScript[i];
		if (quote) {
			if (escaped) escaped = false;
			else if (char === '\\') escaped = true;
			else if (char === quote) quote = null;
			continue;
		}
		if (char === '"' || char === "'" || char === '`') {
			quote = char;
			continue;
		}
		if (char === '{') depth += 1;
		if (char === '}') {
			depth -= 1;
			if (depth === 0) return themeScript.slice(start, i + 1);
		}
	}
	return null;
}

const helperSource = extractFunction('scheduleEmotionKeyboardPosition');
assert.ok(helperSource, 'scroll position scheduler is missing');

let keyboardOpen = true;
let positionCalls = 0;
const pendingFrames = [];
const context = {
	emotionKeyboardPositionFrame: 0,
	positionEmotionKeyboard: () => { positionCalls += 1; },
	window: {
		requestAnimationFrame: (callback) => {
			pendingFrames.push(callback);
			return pendingFrames.length;
		}
	},
	$: (selector) => ({
		hasClass: (className) => selector === '#emotion_keyboard' && className === 'comment-emotion-keyboard-open' && keyboardOpen
	})
};
const schedule = vm.runInNewContext(`(${helperSource})`, context);

schedule();
schedule();
assert.strictEqual(pendingFrames.length, 1, 'scroll updates must be coalesced into one animation frame');
pendingFrames.shift()();
assert.strictEqual(positionCalls, 1, 'the queued frame must reposition the open keyboard');

keyboardOpen = false;
schedule();
assert.strictEqual(pendingFrames.length, 0, 'closed keyboard must not schedule a position update');

const positionSource = extractFunction('positionEmotionKeyboard');
assert.ok(positionSource, 'keyboard position function is missing');
const visibilitySource = extractFunction('isCommentEmotionButtonVisible');
assert.ok(visibilitySource, 'emotion button visibility check is missing');

const body = {};
const animationRects = [
	{ top: 300, left: 100 },
	{ top: 50, left: 70 }
];
let positionAnimation = null;
const keyboard = {
	parentElement: body,
	offsetHeight: 200,
	offsetWidth: 300,
	style: { top: '500px', left: '100px' },
	classList: {
		contains: (className) => className === 'comment-emotion-keyboard-open',
		add: () => {}
	},
	getBoundingClientRect: () => animationRects.shift(),
	animate: (frames, options) => {
		positionAnimation = { frames, options, cancel: () => {} };
		return positionAnimation;
	}
};
let buttonRect = { top: 100, right: 240, bottom: 140, left: 200, width: 40 };
const button = { getBoundingClientRect: () => buttonRect };
let closeCalls = 0;
const positionContext = {
	emotionKeyboardPositionAnimation: null,
	emotionKeyboardPositionDuration: 100,
	closeEmotionKeyboard: () => { closeCalls += 1; },
	document: {
		body,
		getElementById: (id) => {
			if (id === 'comment_emotion_btn') return button;
			if (id === 'emotion_keyboard') return keyboard;
			return null;
		}
	},
	window: {
		innerHeight: 800,
		innerWidth: 800,
		scrollX: 0,
		scrollY: 0,
		matchMedia: () => ({ matches: false })
	}
};
const position = vm.runInNewContext(`${visibilitySource}\n(${positionSource})`, positionContext);
position();
assert.ok(positionAnimation, 'a changed keyboard position must create an animation');
assert.strictEqual(positionAnimation.options.duration, 100, 'position animation must remain quick but visible');
assert.match(positionAnimation.frames[0].transform, /translate3d\(30px, 250px, 0\)/, 'animation must start from the previous visual position');
assert.strictEqual(positionAnimation.frames[1].transform, 'translate3d(0, 0, 0) scale(1)', 'animation must finish at the calculated position');

const positionedTop = keyboard.style.top;
buttonRect = { top: -60, right: 240, bottom: -20, left: 200, width: 40 };
position();
assert.strictEqual(closeCalls, 1, 'an open keyboard must close when its trigger leaves the viewport');
assert.strictEqual(keyboard.style.top, positionedTop, 'an invisible trigger must not reposition the keyboard');

assert.match(
	themeScript,
	/window\.addEventListener\(\s*["']scroll["']\s*,\s*scheduleEmotionKeyboardPosition/,
	'page scroll must drive the keyboard position scheduler'
);
assert.match(
	stylesheet,
	/\.emotion-keyboard\s*\{[^}]*transition:[^}]*top\s+var\(--emotion-keyboard-position-duration\)/s,
	'keyboard position transition must be declared in the theme stylesheet'
);
assert.match(
	stylesheet,
	/\.emotion-keyboard\s*\{[^}]*--emotion-keyboard-position-duration:\s*100ms;[^}]*transition:[^}]*left\s+var\(--emotion-keyboard-position-duration\)/s,
	'keyboard horizontal movement must use the fast position transition'
);
assert.match(
	themeScript,
	/keyboard\.animate\(\[[\s\S]*translate3d\([\s\S]*duration:\s*emotionKeyboardPositionDuration/,
	'keyboard position changes must use a visible transform animation'
);
assert.match(
	stylesheet,
	/\.emotion-keyboard\.emotion-keyboard-position-flip\s*\{[^}]*transition:[^}]*transform\s+0\.3s\s+ease/s,
	'the transform animation mode must suppress competing top and left transitions'
);

process.stdout.write('emotion keyboard scroll positioning test passed.\n');
