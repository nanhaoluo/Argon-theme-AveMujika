// 验证 ArgonImageViewer 的 resolveGallery / handleDocumentClick 逻辑
// 从 argontheme.js 中提取真实实现代码执行，不手抄副本
const fs = require('fs');
const src = fs.readFileSync(__dirname + '/../argontheme.js', 'utf8');

// ---- 提取模块内需要的代码段（从常量定义到 handleTriggerKeydown 结束）----
const startMark = '\tvar PLACEHOLDER_PREFIX';
const endMark = '\n\tfunction handleViewerKeydown';
const start = src.indexOf(startMark);
const end = src.indexOf(endMark);
if (start < 0 || end < 0 || end <= start) {
	console.error('FAIL: 无法提取查看器代码段');
	process.exit(1);
}
const slice = src.slice(start, end);

// ---- 最小 DOM stub ----
class El {
	constructor(tag, attrs = {}) {
		this.tagName = tag.toLowerCase();
		this.attrs = attrs;
		this.children = [];
		this.parent = null;
		this._id = attrs.id || '';
		this._cls = (attrs.class || '').split(/\s+/).filter(Boolean);
	}
	appendChild(c) { c.parent = this; this.children.push(c); return c; }
	getAttribute(name) {
		if (name === 'id') return this._id || null;
		if (name === 'class') return this.attrs.class ?? null;
		return name in this.attrs ? this.attrs[name] : null;
	}
	matches(sel) {
		sel = sel.trim();
		// 支持: #id / .cls / tag / [attr] / [data-x] 及组合 a.comment-image[href]
		const m = sel.match(/^([a-z0-9]+)?((?:[.#][\w-]+|\[[\w-]+(?:=[^\]]+)?\])*)$/i);
		if (!m) return false;
		if (m[1] && this.tagName !== m[1].toLowerCase()) return false;
		const parts = m[2].match(/[.#][\w-]+|\[[\w-]+(?:=[^\]]+)?\]/g) || [];
		for (const p of parts) {
			if (p[0] === '#') { if (this._id !== p.slice(1)) return false; }
			else if (p[0] === '.') { if (!this._cls.includes(p.slice(1))) return false; }
			else {
				const attr = p.slice(1, -1).split('=')[0];
				if (!(attr in this.attrs)) return false;
			}
		}
		return true;
	}
	closest(sel) {
		// 支持逗号选择器与简单后代选择器 "A B"（仅匹配本节点为 B 且祖先含 A）
		for (const part of sel.split(',')) {
			const p = part.trim();
			const spaceIdx = p.lastIndexOf(' ');
			if (spaceIdx > 0) {
				const ancestorSel = p.slice(0, spaceIdx), selfSel = p.slice(spaceIdx + 1);
				if (this.matches(selfSel)) {
					let node = this.parent;
					while (node) { if (node.matches(ancestorSel)) return this; node = node.parent; }
				}
				continue;
			}
			let node = this;
			while (node) { if (node.matches(p)) return node; node = node.parent; }
		}
		return null;
	}
	*walk() { yield this; for (const c of this.children) yield* c.walk(); }
	querySelectorAll(sel) {
		const out = [];
		for (const node of this.walk()) {
			if (node === this) continue;
			// 简单处理: 'img' / '#id' / '.cls' / 'a[href]' / 逗号
			for (const part of sel.split(',')) {
				const p = part.trim();
				const spaceIdx = p.lastIndexOf(' ');
				if (spaceIdx > 0) {
					const anc = p.slice(0, spaceIdx), self = p.slice(spaceIdx + 1);
					if (node.matches(self)) {
						let anc2 = node.parent;
						while (anc2 && anc2 !== this) { if (anc2.matches(anc)) { out.push(node); break; } anc2 = anc2.parent; }
						if (anc2 === this && this.matches(anc)) out.push(node);
					}
				} else if (node.matches(p)) out.push(node);
			}
		}
		return [...new Set(out)];
	}
	querySelector(sel) { return this.querySelectorAll(sel)[0] || null; }
	contains(node) { for (const n of this.walk()) if (n === node) return true; return false; }
}

function el(tag, attrs, ...children) {
	const e = new El(tag, attrs);
	children.forEach(c => e.appendChild(c));
	return e;
}

// ---- 构造测试 DOM ----
const IMG_URL = 'http://test.local/wp-content/uploads/2025/12/%E4%B8%8B%E8%BD%BD.png';
const img1 = el('img', { src: IMG_URL, alt: '测试图片一（带 alt）' });
const img2 = el('img', { src: IMG_URL, alt: '' });
const img3 = el('img', { src: IMG_URL, alt: '链接包裹的图片' });
const link = el('a', { href: IMG_URL }, img3);
const postContent = el('div', { id: 'post_content' },
	el('p', {}, img1),
	el('p', {}, img2),
	el('p', {}, link)
);
const plainP = postContent.children[0];

const commentLink = el('a', { href: 'http://test.local/c.png', class: 'comment-image' });
const commentItem = el('li', { class: 'comment-item' }, commentLink);

const fb1 = el('img', { src: 'http://test.local/f1.png', 'data-feedback-preview': '' });
const fb2 = el('img', { src: 'http://test.local/f2.png', 'data-feedback-preview': '' });
const fbWrap = el('div', { class: 'feedback-images-display' }, fb1, fb2);

// ---- 装载被测代码 ----
const factory = new Function('argonConfig', 'window', 'document', `
	var texts = { loading: '加载中', error: '失败', defaultName: '图片' };
	var opened = [];
	${slice}
	// 替换 openViewer 以观测调用（function 声明可重新赋值）
	openViewer = function (g) { opened.push(g); };
	return { resolveGallery, handleDocumentClick, handleTriggerKeydown, getOpened: () => opened };
`);
const documentStub = { documentElement: { setAttribute() {} } };
const viewer = factory({ imageViewer: true }, { location: { href: 'http://test.local/' } }, documentStub);

let pass = 0, fail = 0;
function check(name, cond) {
	if (cond) { pass++; console.log('[OK] ' + name); }
	else { fail++; console.log('[FAIL] ' + name); }
}

// 1. 点击普通图片
let g = viewer.resolveGallery(img1);
check('普通图片: 收集 3 张画廊', g && g.items.length === 3);
check('普通图片: 索引 0', g && g.index === 0);

// 2. 点击链接内的图片
g = viewer.resolveGallery(img3);
check('链接内图片: 索引 2', g && g.index === 2);
check('链接内图片: 使用链接 href 作为地址', g && g.items[2].src === IMG_URL);

// 3. 点击链接元素本身（本次修复的场景）
g = viewer.resolveGallery(link);
check('链接本体: 也能解析出画廊', g && g.index === 2);

// 4. 点击普通段落文字
g = viewer.resolveGallery(plainP);
check('普通段落: 返回 null', g === null);

// 5. 评论图片链接
g = viewer.resolveGallery(commentLink);
check('评论图片: 单张画廊且取 href', g && g.items.length === 1 && g.items[0].src === 'http://test.local/c.png');

// 6. 反馈附件图（画廊分组）
g = viewer.resolveGallery(fb2);
check('反馈图片: 组内 2 张、索引 1', g && g.items.length === 2 && g.index === 1);

// 7. handleDocumentClick: 链接点击应 preventDefault + stopPropagation 且打开查看器
let prevented = false, stopped = false;
viewer.handleDocumentClick({ target: link, preventDefault: () => { prevented = true; }, stopPropagation: () => { stopped = true; } });
check('链接点击: 阻止默认跳转', prevented);
check('链接点击: 阻止事件传播（PJAX 不接管）', stopped);
check('链接点击: 触发打开查看器', viewer.getOpened().length === 1 && viewer.getOpened()[0].index === 2);

// 8. 无链接图片点击不调用 preventDefault（无默认行为需要阻止，但也不应报错）
prevented = false;
viewer.handleDocumentClick({ target: img1, preventDefault: () => { prevented = true; }, stopPropagation: () => {} });
check('裸图点击: 打开查看器', viewer.getOpened().length === 2);

// 9. 键盘 Enter 于链接（焦点在 a 上）也能打开
let keyPrevented = false;
viewer.handleTriggerKeydown({ key: 'Enter', target: link, preventDefault: () => { keyPrevented = true; } });
check('链接 Enter: 打开查看器', keyPrevented && viewer.getOpened().length === 3);

console.log(`\n${pass} 通过, ${fail} 失败`);
process.exit(fail ? 1 : 0);
