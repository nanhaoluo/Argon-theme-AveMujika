'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const themeScript = fs.readFileSync(path.join(__dirname, '..', 'argontheme.js'), 'utf8');

assert.ok(
	/if \(!virtualUpdateInProgress \|\| tailLockActive\)/.test(themeScript),
	'Tail lock must preserve the upward direction of a fast jump back into the article.'
);

assert.ok(
	/if \(tailLockActive && scrollDirection === "up"\)[\s\S]*?updateVirtualTailLock\(\);[\s\S]*?if \(!tailLockActive\) \{[\s\S]*?scheduleUpdate\(\);/.test(themeScript),
	'The virtualizer must recheck tail lock and render the middle after its update freeze.'
);

console.log('Long-content middle visibility contract passed.');
