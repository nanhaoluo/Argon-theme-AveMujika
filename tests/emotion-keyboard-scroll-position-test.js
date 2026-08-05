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
	/\.emotion-keyboard\s*\{[^}]*--emotion-keyboard-position-duration:\s*120ms;[^}]*transition:[^}]*left\s+var\(--emotion-keyboard-position-duration\)/s,
	'keyboard horizontal movement must use the fast position transition'
);

process.stdout.write('emotion keyboard scroll positioning test passed.\n');
