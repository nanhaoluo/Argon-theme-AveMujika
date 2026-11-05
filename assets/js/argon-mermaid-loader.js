/* Load diagram support only when a page actually contains a diagram. */
(function (window, document) {
	'use strict';
	var config = window.argonMermaidLoaderConfig || {};
	var pending = null;
	var initialized = null;
	var currentScript = document.currentScript;
	var nonce = currentScript && currentScript.nonce;

	function initialize(library) {
		if (initialized !== library) {
			library.initialize(Object.assign({}, config.options || {}, { startOnLoad: false }));
			initialized = library;
		}
		return library;
	}

	function load() {
		if (window.mermaid && typeof window.mermaid.initialize === 'function') {
			return Promise.resolve().then(function () { return initialize(window.mermaid); });
		}
		if (pending) return pending;
		pending = new Promise(function (resolve, reject) {
			var script = document.createElement('script');
			var settled = false;
			function finish(error) {
				if (settled) return;
				settled = true;
				window.clearTimeout(timer);
				script.onload = script.onerror = null;
				if (error) {
					script.remove();
					reject(error);
				} else {
					try { resolve(initialize(window.mermaid)); } catch (err) { script.remove(); reject(err); }
				}
			}
			var timer = window.setTimeout(function () { finish(new Error('Diagram script timed out')); }, 10000);
			script.src = config.src;
			script.async = true;
			if (nonce) script.nonce = nonce;
			script.onload = function () { finish(); };
			script.onerror = function () { finish(new Error('Diagram script failed to load')); };
			document.head.appendChild(script);
		});
		pending = pending.catch(function (error) { pending = null; throw error; });
		return pending;
	}

	function render(root) {
		root = root || document;
		var nodes = Array.from(root.querySelectorAll('.mermaid:not([data-processed="true"])'));
		if (!nodes.length) return Promise.resolve();
		return load().then(function (library) {
			// A delayed dependency must not render a page already replaced by PJAX.
			nodes = nodes.filter(function (node) { return node.isConnected && node.getAttribute('data-processed') !== 'true'; });
			if (!nodes.length) return;
			nodes.forEach(function (node) { node.querySelectorAll('script').forEach(function (script) { script.remove(); }); });
			if (typeof library.run === 'function') return library.run({ nodes: nodes });
			return library.init(undefined, nodes);
		}).catch(function (error) {
			// Keep the source text readable, and allow a later visit to retry.
			if (window.console) window.console.warn('[Argon] Diagram rendering failed:', error);
		});
	}

	window.ArgonMermaidLoader = { render: render };
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { render(document); }, { once: true });
	} else {
		render(document);
	}
})(window, document);
