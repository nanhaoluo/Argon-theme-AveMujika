const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'argontheme.js'), 'utf8');
const footer = fs.readFileSync(path.join(root, 'footer.php'), 'utf8');

function fail(message) {
	process.stderr.write(`评论插入可见性测试失败：${message}\n`);
	process.exit(1);
}

function extractFunction(name) {
	const start = source.indexOf(`function ${name}(`);
	if (start < 0) return null;
	const openBrace = source.indexOf('{', start);
	let depth = 0;
	let quote = null;
	let escaped = false;
	for (let i = openBrace; i < source.length; i += 1) {
		const char = source[i];
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
			if (depth === 0) return source.slice(start, i + 1);
		}
	}
	return null;
}

const helperSource = extractFunction('argonRevealInsertedComments');
if (!helperSource) {
	fail('缺少按 DOM 差集识别并显示 AJAX 新评论的函数。');
}

const revealInsertedComments = vm.runInNewContext(`(${helperSource})`);
const oldClasses = new Set(['comment-item', 'animate-in']);
const newClasses = new Set(['comment-item']);
const oldComment = { id: 'comment-119', classList: { add: (name) => oldClasses.add(name) } };
const newComment = { id: 'comment-120', classList: { add: (name) => newClasses.add(name) } };
const rootNode = { querySelectorAll: () => [oldComment, newComment] };
const lazyloaded = [];

const inserted = revealInsertedComments(rootNode, [oldComment], (element) => lazyloaded.push(element.id));

if (inserted.length !== 1 || inserted[0] !== newComment) {
	fail('没有只识别出响应中新插入的 #comment-120。');
}
if (!newClasses.has('animate-in')) {
	fail('新插入的 #comment-120 仍缺少 animate-in。');
}
if (lazyloaded.length !== 1 || lazyloaded[0] !== 'comment-120') {
	fail('懒加载初始化没有限定到新插入的评论。');
}

const insertionStart = source.indexOf('// 插入新评论');
const snapshotIndex = source.indexOf('commentsBeforeInsert', insertionStart);
const revealIndex = source.indexOf('argonRevealInsertedComments(', snapshotIndex + 1);
const timeIndex = source.indexOf('calcHumanTimesOnPage()', snapshotIndex);
if (snapshotIndex < 0 || revealIndex < 0 || timeIndex < 0 || revealIndex > timeIndex) {
	fail('AJAX 回调没有在时间刷新之前显示 DOM 差集中的新评论。');
}

if (!/argontheme\.js\?v=.*argon_get_asset_file_version/.test(footer)) {
	fail('核心脚本 URL 没有使用文件级缓存版本，部署后可能继续命中旧脚本。');
}

process.stdout.write('评论插入可见性测试通过。\n');
