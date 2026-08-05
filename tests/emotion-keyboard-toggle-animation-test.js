'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..');
const themeScript = fs.readFileSync(path.join(root, 'argontheme.js'), 'utf8');
const stylesheet = fs.readFileSync(path.join(root, 'style.css'), 'utf8');
const comments = fs.readFileSync(path.join(root, 'comments.php'), 'utf8');
const keyboardTemplate = fs.readFileSync(path.join(root, 'template-parts', 'emotion-keyboard.php'), 'utf8');

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

function createClassList(initial = []) {
	const values = new Set(initial);
	return {
		add: (...names) => names.forEach((name) => values.add(name)),
		remove: (...names) => names.forEach((name) => values.delete(name)),
		contains: (name) => values.has(name)
	};
}

function createElement(initialClasses = []) {
	const attributes = {};
	return {
		classList: createClassList(initialClasses),
		setAttribute: (name, value) => { attributes[name] = value; },
		getAttribute: (name) => attributes[name]
	};
}

const button = createElement();
const keyboard = createElement();
const frames = new Map();
const timers = new Map();
let nextFrameId = 1;
let nextTimerId = 1;
let reducedMotion = false;
let positionCalls = 0;
let resetCalls = 0;
let suspendCalls = 0;

const context = {
	emotionKeyboardOpenFrame: 0,
	emotionKeyboardCloseTimer: 0,
	emotionKeyboardCloseToken: 0,
	emotionKeyboardCloseDuration: 150,
	emotionKeyboardPositionAnimation: null,
	lazyloadStickers: () => {},
	argonBuildStickerMap: () => {},
	positionEmotionKeyboard: () => { positionCalls += 1; },
	updateEmotionKeyboardScrollButtons: () => {},
	resetEmotionKeyboardSearch: () => { resetCalls += 1; },
	suspendAnimatedStickers: () => { suspendCalls += 1; },
	document: {
		getElementById: (id) => {
			if (id === 'comment_emotion_btn') return button;
			if (id === 'emotion_keyboard') return keyboard;
			return null;
		}
	},
	window: {
		matchMedia: () => ({ matches: reducedMotion }),
		ArgonMotion: {
			duration: () => 150,
			fast: (target, isOpen, options = {}) => {
				const className = options.className || 'show';
				if (isOpen) target.classList.add(className);
				else target.classList.remove(className);
				return !!isOpen;
			}
		},
		requestAnimationFrame: (callback) => {
			const id = nextFrameId++;
			frames.set(id, callback);
			return id;
		},
		cancelAnimationFrame: (id) => frames.delete(id),
		setTimeout: (callback) => {
			const id = nextTimerId++;
			timers.set(id, callback);
			return id;
		},
		clearTimeout: (id) => timers.delete(id)
	}
};

const functionNames = [
	'argonPrefersReducedMotion',
	'finishEmotionKeyboardClose',
	'closeEmotionKeyboard',
	'openEmotionKeyboard'
];
const toggleSource = functionNames.map((name) => {
	const source = extractFunction(name);
	assert.ok(source, `${name} is missing`);
	return source;
}).join('\n');
vm.runInNewContext(toggleSource, context);

context.openEmotionKeyboard();
assert.strictEqual(positionCalls, 1, 'opening must position the panel before revealing it');
assert.ok(keyboard.classList.contains('emotion-keyboard-opening'), 'opening must keep the panel measurable but initially hidden');
assert.ok(!keyboard.classList.contains('comment-emotion-keyboard-open'), 'the visible state must wait for the next frame');
assert.strictEqual(button.getAttribute('aria-expanded'), 'true');
assert.strictEqual(keyboard.getAttribute('aria-hidden'), 'false');

const reveal = frames.values().next().value;
assert.ok(reveal, 'opening must schedule one reveal frame');
reveal();
frames.clear();
assert.ok(keyboard.classList.contains('comment-emotion-keyboard-open'), 'the reveal frame must enter the open state');

context.closeEmotionKeyboard();
assert.ok(keyboard.classList.contains('emotion-keyboard-closing'), 'closing must keep the panel rendered for its exit transition');
assert.ok(!keyboard.classList.contains('comment-emotion-keyboard-open'), 'closing must remove the interactive open state immediately');
assert.strictEqual(button.getAttribute('aria-expanded'), 'false');
assert.strictEqual(keyboard.getAttribute('aria-hidden'), 'true');
assert.strictEqual(resetCalls, 0, 'panel contents must remain stable during the exit transition');

const finishClose = timers.values().next().value;
assert.ok(finishClose, 'closing must have a bounded fallback timer');
finishClose();
assert.ok(!keyboard.classList.contains('emotion-keyboard-closing'), 'the fallback must hide the panel after the exit transition');
assert.strictEqual(resetCalls, 1, 'the hidden panel must reset its search state');
assert.strictEqual(suspendCalls, 1, 'the hidden panel must suspend animated stickers');

context.openEmotionKeyboard();
frames.values().next().value();
frames.clear();
context.closeEmotionKeyboard();
const staleClose = timers.values().next().value;
context.openEmotionKeyboard();
frames.values().next().value();
frames.clear();
staleClose();
assert.ok(keyboard.classList.contains('comment-emotion-keyboard-open'), 'a stale close callback must not hide a reopened panel');

reducedMotion = true;
context.closeEmotionKeyboard();
assert.ok(!keyboard.classList.contains('emotion-keyboard-closing'), 'reduced motion must close without retaining an animated state');
context.openEmotionKeyboard();
assert.ok(keyboard.classList.contains('comment-emotion-keyboard-open'), 'reduced motion must reveal without waiting for an animation frame');
context.closeEmotionKeyboard();

assert.match(stylesheet, /--motion-fast-duration:\s*150ms;/, 'the shared fast duration must remain brief');
assert.match(stylesheet, /\.emotion-keyboard\s*\{[^}]*--emotion-keyboard-visibility-duration:\s*var\(--motion-fast-duration\);/s, 'the default close transition must use the shared fast duration');
assert.match(stylesheet, /\.emotion-keyboard\.comment-emotion-keyboard-open\s*\{[^}]*--emotion-keyboard-visibility-duration:\s*var\(--motion-fast-duration\);/s, 'the open transition must use the shared fast duration');
assert.match(stylesheet, /\.emotion-keyboard\.emotion-keyboard-closing\s*\{[^}]*opacity:\s*0;[^}]*translate3d\(0, 3px, 0\) scale\(0\.995\)/s, 'the close state must use restrained movement and scale');
assert.match(comments, /id="comment_emotion_btn"[^\r\n]*aria-controls="emotion_keyboard"[^\r\n]*aria-expanded="false"/, 'the trigger must expose its collapsed state');
assert.match(keyboardTemplate, /id="emotion_keyboard"[^>]*aria-hidden="true"/, 'the panel must start hidden from assistive technology');

process.stdout.write('emotion keyboard toggle animation test passed.\n');
