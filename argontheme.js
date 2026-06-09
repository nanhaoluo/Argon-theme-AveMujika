/*!
 * Argon 主题核心 JavaScript
 */

// ========== 兼容性修复 ==========
// 确保 Prism 和 autoloader 存在
if (typeof window.Prism === 'undefined') {
	window.Prism = {
		highlightAll: function () { },
		highlightElement: function () { },
		plugins: {}
	};
}
if (typeof window.Prism.plugins === 'undefined') {
	window.Prism.plugins = {};
}
if (typeof window.Prism.plugins.autoloader === 'undefined') {
	window.Prism.plugins.autoloader = {
		languages_path: '',
		use_minified: true
	};
}

// 确保 Zoomify 存在
if (typeof window.Zoomify === 'undefined') {
	window.Zoomify = function () { };
	window.Zoomify.DEFAULTS = {};
}

// 确保 jQuery 插件存在
if (typeof jQuery !== 'undefined') {
	(function ($) {
		// 确保 easing 函数存在（防止其他插件覆盖 jQuery 后丢失）
		if (typeof $.easing === 'undefined') {
			$.easing = {};
		}
		if (typeof $.easing.easeOutCirc === 'undefined') {
			$.easing.easeOutCirc = function (x) {
				return Math.sqrt(1 - Math.pow(x - 1, 2));
			};
		}
		if (typeof $.easing.easeOutExpo === 'undefined') {
			$.easing.easeOutExpo = function (x) {
				return x === 1 ? 1 : 1 - Math.pow(2, -10 * x);
			};
		}

		if (!$.fn._argonInit) {
			$.fn._argonInit = $.fn.init;
			$.fn.init = function (selector, context, root) {
				// 修复空选择器、特殊字符和中文标点导致的语法错误
				if (typeof selector === 'string') {
					let trimmed = selector.trim();
					// 检查是否为空或只有 # 符号
					if (trimmed === '' || trimmed === '#') {
						return new $.fn._argonInit();
					}
					// 检查是否包含中文标点（可能导致语法错误）
					if (/[；，。：！？]/.test(trimmed)) {
						console.warn('[Argon] Invalid selector with Chinese punctuation:', selector);
						return new $.fn._argonInit();
					}
					// 检查 ID 选择器是否有效（#后面必须有内容）
					if (/^#\s*$/.test(trimmed)) {
						return new $.fn._argonInit();
					}
				}
				return $.fn._argonInit.call(this, selector, context, root);
			};
			$.fn.init.prototype = $.fn;
		}

		// 确保 zoomify 插件存在
		if (typeof $.fn.zoomify === 'undefined') {
			$.fn.zoomify = function () { return this; };
		}

		// 确保 fancybox 存在
		if (typeof $.fancybox === 'undefined') {
			$.fancybox = {
				defaults: {
					transitionEffect: 'slide',
					buttons: [],
					lang: 'zh_CN',
					i18n: {}
				},
				open: function () { },
				close: function () { }
			};
		}
		if (typeof $.fancybox.defaults === 'undefined') {
			$.fancybox.defaults = {
				transitionEffect: 'slide',
				buttons: [],
				lang: 'zh_CN',
				i18n: {}
			};
		}

		// 确保 pjax 存在
		if (typeof $.pjax === 'undefined') {
			$.pjax = function () { };
			$.pjax.defaults = {
				timeout: 10000,
				container: [],
				fragment: []
			};
		}
		if (typeof $.pjax.defaults === 'undefined') {
			$.pjax.defaults = {
				timeout: 10000,
				container: [],
				fragment: []
			};
		}
	})(jQuery);
}

// ==========================================================================
// 性能优化模块引入
// ==========================================================================
// 注意：argon-performance.js 需要在此文件之前加载
// 在 functions.php 中通过 wp_enqueue_script 确保加载顺序
// 
// 提供的优化功能：
// - ArgonDOMCache: DOM 元素缓存系统
// - ArgonEventManager: 事件节流和防抖
// - ArgonResourceLoader: 资源按需加载
// - ArgonRenderOptimizer: 渲染优化和 GPU 加速
// - ArgonMemoryManager: 内存管理和清理
// - ArgonPerformanceMonitor: 性能监控和分析
// ==========================================================================

// ========== 原有代码 ==========
if (typeof (argonConfig) == "undefined") {
	var argonConfig = {};
}
if (typeof (argonConfig.wp_path) == "undefined") {
	argonConfig.wp_path = "/";
}

// ==========================================================================
// 性能优化模块实例（全局变量）
// ==========================================================================
var argonDOMCache = null;           // DOM 缓存实例
var argonEventManager = null;       // 事件管理实例
var argonResourceLoader = null;     // 资源加载实例
var argonRenderOptimizer = null;    // 渲染优化实例
var argonMemoryManager = null;      // 内存管理实例
var argonPerformanceMonitor = null; // 性能监控实例

// ==========================================================================
// 调试控制台（由 argon-performance.js 引入）
// ==========================================================================
// 如果 ArgonDebug 未定义，创建一个简单的替代实现
if (typeof ArgonDebug === 'undefined') {
	window.ArgonDebug = {
		enabled: false,
		init: function () {
			if (typeof argonConfig !== 'undefined' && argonConfig.debug_mode) {
				this.enabled = true;
			}
		},
		_output: function (level, args) {
			if (!this.enabled || typeof console === 'undefined' || !console[level]) {
				return;
			}
			var outputArgs = Array.prototype.slice.call(args);
			outputArgs.unshift('[Argon]');
			console[level].apply(console, outputArgs);
		},
		log: function () { this._output('log', arguments); },
		warn: function () { this._output('warn', arguments); },
		error: function () { this._output('error', arguments); },
		info: function () { this._output('info', arguments); }
	};
	ArgonDebug.init();
}
/*Cookies 操作*/
function setCookie(cname, cvalue, exdays) {
	let d = new Date();
	d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
	let expires = "expires=" + d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}
function getCookie(cname) {
	let name = cname + "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for (let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

/*多语言支持*/
var translation = {};
translation['en_US'] = {
	"确定": "OK",
	"清除": "Clear",
	"恢复博客默认": "Set To Default",
	"评论内容不能为空": "Comment content cannot be empty",
	"昵称不能为空": "Name cannot be empty",
	"邮箱或QQ号格式错误": "Incorrect email or QQ format",
	"邮箱格式错误": "Incorrect email format",
	"网站格式错误 (不是 http(s):// 开头)": "Website URL format error",
	"验证码未输入": "CAPTCHA cannot be empty",
	"验证码格式错误": "Incorrect CAPTCHA format",
	"请完成验证码验证": "Please complete CAPTCHA verification",
	"评论格式错误": "Comment format error",
	"发送中": "Sending",
	"正在发送": "Sending",
	"评论正在发送中...": "Comment is sending...",
	"发送": "Send",
	"评论发送失败": "Comment failed",
	"发送成功": "Success",
	"您的评论已发送": "Your comment has been sent",
	"评论": "Comments",
	"未知原因": "Unknown Error",
	"编辑中": "Editing",
	"正在编辑": "Editing",
	"评论正在编辑中...": "Comment is being edited...",
	"编辑": "Edit",
	"评论编辑失败": "Comment editing failed",
	"已编辑": "Edited",
	"编辑成功": "Success",
	"您的评论已编辑": "Your comment has been edited",
	"评论 #": "Comment #",
	"的编辑记录": "- Edit History",
	"加载失败": "Failed to load",
	"展开": "Show",
	"没有更多了": "No more comments",
	"找不到该 Repo": "Can't find the repository",
	"获取 Repo 信息失败": "Failed to get repository information",
	"点赞失败": "Vote failed",
	"Hitokoto 获取失败": "Failed to get Hitokoto",
	"复制成功": "Copied",
	"代码已复制到剪贴板": "Code has been copied to the clipboard",
	"复制失败": "Failed",
	"请手动复制代码": "Please copy the code manually",
	"刚刚": "Now",
	"分钟前": "minutes ago",
	"小时前": "hours ago",
	"昨天": "Yesterday",
	"前天": "The day before yesterday",
	"天前": "days ago",
	"隐藏行号": "Hide Line Numbers",
	"显示行号": "Show Line Numbers",
	"开启折行": "Enable Break Line",
	"关闭折行": "Disable Break Line",
	"复制": "Copy",
	"全屏": "Fullscreen",
	"退出全屏": "Exit Fullscreen",
	"置顶评论": "Pin Comment",
	"取消置顶评论": "Unpin Comment",
	"是否要取消置顶评论 #": "Do you want to unpin the comment #",
	"是否要置顶评论 #": "Do you want to pin the comment #",
	"确认": "Confirm",
	"取消": "Cancel",
	"置顶": "Pin",
	"取消置顶": "Unpin",
	"置顶成功": "Pinned",
	"取消置顶成功": "Unpinned",
	"该评论已置顶": "The comment has been pinned",
	"该评论已取消置顶": "The comment has been unpinned",
	"置顶失败": "Failed to pin",
	"取消置顶失败": "Failed to unpin",
};
translation['ru_RU'] = {
	"确定": "ОК",
	"清除": "Очистить",
	"恢复博客默认": "Восстановить по умолчанию",
	"评论内容不能为空": "Содержимое комментария не может быть пустым",
	"昵称不能为空": "Имя не может быть пустым",
	"邮箱或QQ号格式错误": "Неверный формат электронной почты или QQ",
	"邮箱格式错误": "Неправильный формат электронной почты",
	"网站格式错误 (不是 http(s):// 开头)": "Сайт ошибка формата URL-адреса ",
	"验证码未输入": "Вы не решили капчу",
	"验证码格式错误": "Ошибка проверки капчи",
	"评论格式错误": "Неправильный формат комментария",
	"发送中": "Отправка",
	"正在发送": "Отправка",
	"评论正在发送中...": "Комментарий отправляется...",
	"发送": "Отправить",
	"评论发送失败": "Не удалось отправить комментарий",
	"发送成功": "Комментарий отправлен",
	"您的评论已发送": "Ваш комментарий был отправлен",
	"评论": "Комментарии",
	"未知原因": "Неизвестная ошибка",
	"编辑中": "Редактируется",
	"正在编辑": "Редактируется",
	"评论正在编辑中...": "Комментарий редактируется",
	"编辑": "Редактировать",
	"评论编辑失败": "Не удалось отредактировать комментарий",
	"已编辑": "Изменено",
	"编辑成功": "Успешно",
	"您的评论已编辑": "Ваш комментарий был изменен",
	"评论 #": "Комментарий #",
	"的编辑记录": "- История изменений",
	"加载失败": "Ошибка загрузки",
	"展开": "Показать",
	"没有更多了": "Комментариев больше нет",
	"找不到该 Repo": "Невозможно найти репозиторий",
	"获取 Repo 信息失败": "Неудалось получить информацию репозитория",
	"点赞失败": "Ошибка голосования",
	"Hitokoto 获取失败": "Проблемы с вызовом Hitokoto",
	"复制成功": "Скопировано",
	"代码已复制到剪贴板": "Код скопирован в буфер обмена",
	"复制失败": "Неудалось",
	"请手动复制代码": "Скопируйте код вручную",
	"刚刚": "Сейчас",
	"分钟前": "минут назад",
	"小时前": "часов назад",
	"昨天": "Вчера",
	"前天": "Позавчера",
	"天前": "дней назад",
	"隐藏行号": "Скрыть номера строк",
	"显示行号": "Показать номера строк",
	"开启折行": "Включить перенос строк",
	"关闭折行": "Выключить перенос строк",
	"复制": "Скопировать",
	"全屏": "Полноэкранный режим",
	"退出全屏": "Выход из полноэкранного режима",
};
translation['zh_TW'] = {
	"确定": "確定",
	"清除": "清除",
	"恢复博客默认": "恢復博客默認",
	"评论内容不能为空": "評論內容不能為空",
	"昵称不能为空": "昵稱不能為空",
	"邮箱或QQ号格式错误": "郵箱或QQ號格式錯誤",
	"邮箱格式错误": "郵箱格式錯誤",
	"网站格式错误 (不是 http(s):// 开头)": "網站格式錯誤 (不是 http(s):// 開頭)",
	"验证码未输入": "驗證碼未輸入",
	"验证码格式错误": "驗證碼格式錯誤",
	"评论格式错误": "評論格式錯誤",
	"发送中": "發送中",
	"正在发送": "正在發送",
	"评论正在发送中...": "評論正在發送中...",
	"发送": "發送",
	"评论发送失败": "評論發送失敗",
	"发送成功": "發送成功",
	"您的评论已发送": "您的評論已發送",
	"评论": "評論",
	"未知原因": "未知原因",
	"编辑中": "編輯中",
	"正在编辑": "正在編輯",
	"评论正在编辑中...": "評論正在編輯中...",
	"编辑": "編輯",
	"评论编辑失败": "評論編輯失敗",
	"已编辑": "已編輯",
	"编辑成功": "編輯成功",
	"您的评论已编辑": "您的評論已編輯",
	"评论 #": "評論 #",
	"的编辑记录": "的編輯記錄",
	"加载失败": "加載失敗",
	"展开": "展開",
	"没有更多了": "沒有更多了",
	"找不到该 Repo": "找不到該 Repo",
	"获取 Repo 信息失败": "獲取 Repo 信息失敗",
	"点赞失败": "點贊失敗",
	"Hitokoto 获取失败": "Hitokoto 獲取失敗",
	"复制成功": "復制成功",
	"代码已复制到剪贴板": "代碼已復制到剪貼板",
	"复制失败": "復制失敗",
	"请手动复制代码": "請手動復制代碼",
	"刚刚": "剛剛",
	"分钟前": "分鐘前",
	"小时前": "小時前",
	"昨天": "昨天",
	"前天": "前天",
	"天前": "天前",
	"隐藏行号": "隱藏行號",
	"显示行号": "顯示行號",
	"开启折行": "開啟折行",
	"关闭折行": "關閉折行",
	"复制": "復制",
	"全屏": "全屏",
	"退出全屏": "退出全屏",
};
function __(text) {
	let lang = argonConfig.language;
	if (typeof (translation[lang]) == "undefined") {
		return text;
	}
	if (typeof (translation[lang][text]) == "undefined") {
		return text;
	}
	return translation[lang][text];
}

/*根据滚动高度改变顶栏透明度 */
!function () {
	let toolbar = document.getElementById("navbar-main");
	let $bannerContainer = $("#banner_container");
	let $content = $("#content");

	let startTransitionHeight;
	let endTransitionHeight;
	let maxOpacity = 0.85;

	startTransitionHeight = $bannerContainer.offset().top - 75;
	endTransitionHeight = $content.offset().top - 75;

	$(window).resize(function () {
		startTransitionHeight = $bannerContainer.offset().top - 75;
		endTransitionHeight = $content.offset().top - 75;
	});

	function changeToolbarTransparency() {
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		if (scrollTop < startTransitionHeight) {
			toolbar.style.setProperty('background-color', 'rgba(var(--toolbar-color), 0)', 'important');
			toolbar.style.setProperty('box-shadow', 'none');
			if (argonConfig.toolbar_blur) {
				toolbar.style.setProperty('backdrop-filter', 'blur(0px)');
			}
			toolbar.classList.add("navbar-ontop");
			return;
		}
		if (scrollTop > endTransitionHeight) {
			toolbar.style.setProperty('background-color', 'rgba(var(--toolbar-color), ' + maxOpacity + ')', 'important');
			toolbar.style.setProperty('box-shadow', '');
			if (argonConfig.toolbar_blur) {
				toolbar.style.setProperty('backdrop-filter', 'blur(16px)');
			}
			toolbar.classList.remove("navbar-ontop");
			return;
		}
		let transparency = (scrollTop - startTransitionHeight) / (endTransitionHeight - startTransitionHeight) * maxOpacity;
		toolbar.style.setProperty('background-color', 'rgba(var(--toolbar-color), ' + transparency, 'important');
		toolbar.style.setProperty('box-shadow', '');
		if (argonConfig.toolbar_blur) {
			if ((scrollTop - startTransitionHeight) / (endTransitionHeight - startTransitionHeight) > 0.3) {
				toolbar.style.setProperty('backdrop-filter', 'blur(16px)');
			} else {
				toolbar.style.setProperty('backdrop-filter', 'blur(0px)');
			}
		}
		toolbar.classList.remove("navbar-ontop");
	}
	function changeToolbarOnTopClass() {
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		if (scrollTop < 30) {
			toolbar.classList.add("navbar-no-blur");
		} else {
			toolbar.classList.remove("navbar-no-blur");
		}
	}
	if ($("html").hasClass("no-banner")) {
		changeToolbarOnTopClass();
		// 使用节流优化滚动事件性能
		const throttledChangeToolbarOnTopClass = argonEventManager ?
			argonEventManager.throttle(changeToolbarOnTopClass, 16) :
			changeToolbarOnTopClass;
		document.addEventListener("scroll", throttledChangeToolbarOnTopClass, { passive: true });
		return;
	}
	if (argonConfig.headroom == "absolute") {
		toolbar.classList.add("navbar-ontop");
		return;
	}
	if ($("html").hasClass("toolbar-blur")) {
		argonConfig.toolbar_blur = true;
		maxOpacity = 0.65;
	} else {
		argonConfig.toolbar_blur = false;
	}
	changeToolbarTransparency();
	// 使用节流优化滚动事件性能
	const throttledChangeToolbarTransparency = argonEventManager ?
		argonEventManager.throttle(changeToolbarTransparency, 16) :
		changeToolbarTransparency;
	document.addEventListener("scroll", throttledChangeToolbarTransparency, { passive: true });
}();

/*搜索*/
function searchPosts(word) {
	if ($(".search-result").length > 0) {
		let url = new URL(window.location.href);
		url.searchParams.set("s", word);
		$.pjax({
			url: url.href
		});
	} else {
		$.pjax({
			url: argonConfig.wp_path + "?s=" + encodeURI(word)
		});
	}
}
/*顶栏搜索*/
$(document).on("click", "#navbar_search_input_container", function () {
	$(this).addClass("open");
	$("#navbar_search_input").focus();
});
$(document).on("blur", "#navbar_search_input_container", function () {
	// 如果有文字则保持has-text类
	if ($("#navbar_search_input").val().trim() !== "") {
		$(this).addClass("has-text");
	} else {
		$(this).removeClass("has-text");
	}
	$(this).removeClass("open");
});
// 监听输入变化来切换has-text类
$(document).on("input", "#navbar_search_input", function () {
	var container = $("#navbar_search_input_container");
	if ($(this).val().trim() !== "") {
		container.addClass("has-text");
	} else {
		container.removeClass("has-text");
	}
});
$(document).on("keydown", "#navbar_search_input_container #navbar_search_input", function (e) {
	if (e.keyCode != 13) {
		return;
	}
	let word = $(this).val();
	if (word == "") {
		$("#navbar_search_input_container").blur();
		return;
	}
	let scrolltop = $(document).scrollTop();
	searchPosts(word);
});
/*顶栏搜索 (Mobile)*/
$(document).on("keydown", "#navbar_search_input_mobile", function (e) {
	if (e.keyCode != 13) {
		return;
	}
	let word = $(this).val();
	$("#navbar_global .collapse-close button").click();
	if (word == "") {
		return;
	}
	let scrolltop = $(document).scrollTop();
	searchPosts(word);
});
/*侧栏搜索*/
$(document).on("click", "#leftbar_search_container", function () {
	$(".leftbar-search-button").addClass("open");
	$("#leftbar_search_input").removeAttr("readonly").focus();
	$("#leftbar_search_input").focus();
	$("#leftbar_search_input").select();
	return false;
});
$(document).on("blur", "#leftbar_search_container", function () {
	$(".leftbar-search-button").removeClass("open");
	$("#leftbar_search_input").attr("readonly", "readonly");
});
$(document).on("keydown", "#leftbar_search_input", function (e) {
	if (e.keyCode != 13) {
		return;
	}
	let word = $(this).val();
	if (word == "") {
		$("#leftbar_search_container").blur();
		return;
	}
	$("html").removeClass("leftbar-opened");
	searchPosts(word);
});
/*搜索过滤器*/
$(document).on("change", ".search-filter", function (e) {
	if (pjaxLoading) {
		$(this).prop("checked", !$(this).prop("checked"));
		e.preventDefault();
		return;
	}
	pjaxLoading = true;
	let postTypes = [];
	$(".search-filter:checked").each(function () {
		postTypes.push($(this).attr("name"));
	});
	if (postTypes.length == 0) {
		postTypes = ["none"];
	}
	let url = new URL(document.location.href);
	url.searchParams.set("post_type", postTypes.join(","));
	url.pathname = url.pathname.replace(/\/page\/\d+$/, '');
	$.pjax({
		url: url.href
	});
});

/*左侧栏随页面滚动浮动*/
!function () {
	if ($("#leftbar").length == 0) {
		let contentOffsetTop = $('#content').offset().top;
		function changeLeftbarStickyStatusWithoutSidebar() {
			let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
			if (contentOffsetTop - 10 - scrollTop <= 20) {
				document.body.classList.add('leftbar-can-headroom');
			} else {
				document.body.classList.remove('leftbar-can-headroom');
			}
		}
		changeLeftbarStickyStatusWithoutSidebar();
		// 使用节流优化滚动事件性能
		const throttledChangeLeftbarStickyStatusWithoutSidebar = argonEventManager ?
			argonEventManager.throttle(changeLeftbarStickyStatusWithoutSidebar, 16) :
			changeLeftbarStickyStatusWithoutSidebar;
		document.addEventListener("scroll", throttledChangeLeftbarStickyStatusWithoutSidebar, { passive: true });
		$(window).resize(function () {
			contentOffsetTop = $('#content').offset().top;
			changeLeftbarStickyStatusWithoutSidebar();
		});
		return;
	}
	let $leftbarPart1 = $('#leftbar_part1');
	let $leftbarPart2 = $('#leftbar_part2');
	let leftbarPart1 = document.getElementById('leftbar_part1');
	let leftbarPart2 = document.getElementById('leftbar_part2');
	let leftbarPart3 = document.getElementById('leftbar_part3');

	let part1OffsetTop = $('#leftbar_part1').offset().top;
	let part1OuterHeight = $('#leftbar_part1').outerHeight();
	let leftbarStickyUpdateFrame = 0;
	let leftbarHeightObserver = null;
	let leftbarHeightObservedNodes = [];
	let leftbarHeightSignature = '';

	function setLeftbarRootProperty(name, value) {
		if (document.documentElement.style.getPropertyValue(name) !== value) {
			document.documentElement.style.setProperty(name, value);
		}
	}

	function isLeftbarCatalogActive() {
		let catalogTab = document.getElementById('leftbar_tab_catalog');
		return !!(catalogTab && catalogTab.classList.contains('active'));
	}

	function getNaturalScrollHeight(element) {
		if (!element) return 0;
		let previousMaxHeight = element.style.maxHeight;
		element.style.maxHeight = 'none';
		let height = element.scrollHeight;
		element.style.maxHeight = previousMaxHeight;
		return height;
	}

	function addLeftbarHeightTarget(targets, element) {
		if (element && targets.indexOf(element) === -1) {
			targets.push(element);
		}
	}

	function addLeftbarHeightTargetsFromSelector(targets, root, selector) {
		if (!root) return;
		let nodes = root.querySelectorAll(selector);
		for (let i = 0; i < nodes.length; i++) {
			addLeftbarHeightTarget(targets, nodes[i]);
		}
	}

	function getLeftbarHeightTargets() {
		let targets = [];
		addLeftbarHeightTargetsFromSelector(targets, leftbarPart2, '#leftbar_part2_inner > *, #leftbar_catalog, #leftbar_tab_overview, #leftbar_tab_tools');
		addLeftbarHeightTargetsFromSelector(targets, leftbarPart3, '.card-body > *');
		return targets;
	}

	function getLeftbarHeightSignature() {
		let targets = getLeftbarHeightTargets();
		let signature = [];
		for (let i = 0; i < targets.length; i++) {
			let element = targets[i];
			signature.push([
				element.id || element.className || element.tagName,
				element.scrollHeight,
				element.offsetHeight,
				element.childElementCount
			].join(':'));
		}
		return signature.join('|');
	}

	function requestLeftbarStickyStatusUpdate() {
		if (leftbarStickyUpdateFrame) return;
		let scheduleFrame = window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : function (callback) {
			return window.setTimeout(callback, 16);
		};
		leftbarStickyUpdateFrame = scheduleFrame(function () {
			leftbarStickyUpdateFrame = 0;
			changeLeftbarStickyStatus();
		});
	}

	function requestLeftbarStickyStatusUpdateOnHeightChange() {
		refreshLeftbarHeightObservers();
		let currentSignature = getLeftbarHeightSignature();
		if (currentSignature === leftbarHeightSignature) {
			return;
		}
		leftbarHeightSignature = currentSignature;
		requestLeftbarStickyStatusUpdate();
	}

	function refreshLeftbarHeightObservers() {
		if (typeof ResizeObserver === 'undefined') {
			return;
		}
		if (!leftbarHeightObserver) {
			leftbarHeightObserver = new ResizeObserver(requestLeftbarStickyStatusUpdateOnHeightChange);
		}
		let nextTargets = getLeftbarHeightTargets();
		for (let i = 0; i < leftbarHeightObservedNodes.length; i++) {
			if (nextTargets.indexOf(leftbarHeightObservedNodes[i]) === -1) {
				leftbarHeightObserver.unobserve(leftbarHeightObservedNodes[i]);
			}
		}
		for (let i = 0; i < nextTargets.length; i++) {
			if (leftbarHeightObservedNodes.indexOf(nextTargets[i]) === -1) {
				leftbarHeightObserver.observe(nextTargets[i]);
			}
		}
		leftbarHeightObservedNodes = nextTargets;
	}

	function changeLeftbarStickyStatus() {
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		if (part1OffsetTop + part1OuterHeight + 10 - scrollTop <= (argonConfig.headroom != "absolute" ? 90 : 18)) {
			//滚动条在页面中间浮动状态
			leftbarPart2.classList.add('sticky');
			if (leftbarPart3) {
				leftbarPart3.classList.add('sticky');
				// 计算可用空间并分配给 part2 和 part3
				let viewportHeight = window.innerHeight;
				let topOffset = argonConfig.headroom !== 'absolute' ? 90 : 20;
				let availableHeight = viewportHeight - topOffset - 20; // 20px 底部边距

				// 获取 part3 的自然高度（不受限制时的高度）
				leftbarPart3.style.maxHeight = 'none';
				let part3NaturalHeight = leftbarPart3.scrollHeight;
				leftbarPart3.style.maxHeight = '';

				// 最小高度限制
				let minPart2Height = 150;
				let minPart3Height = 100;

				// 计算分配
				let part2Height, part3MaxHeight;
				if (part3NaturalHeight + minPart2Height <= availableHeight) {
					// part3 可以完全显示
					part3MaxHeight = part3NaturalHeight;
					part2Height = availableHeight - part3NaturalHeight - 10;
				} else {
					// 需要按比例分配，part2 占 60%，part3 占 40%
					part2Height = Math.max(minPart2Height, availableHeight * 0.6);
					part3MaxHeight = Math.max(minPart3Height, availableHeight - part2Height - 10);
				}

				let gapBetweenParts = 10;
				let catalogActive = isLeftbarCatalogActive();
				let part3Body = leftbarPart3.querySelector('.card-body') || leftbarPart3;
				part3NaturalHeight = getNaturalScrollHeight(part3Body);
				leftbarPart2.classList.toggle('leftbar-tab-catalog-active', catalogActive);
				if (catalogActive) {
					let sharedHeight = availableHeight - gapBetweenParts;
					if (part3NaturalHeight + minPart2Height <= sharedHeight) {
						part3MaxHeight = part3NaturalHeight;
						part2Height = sharedHeight - part3NaturalHeight;
					} else {
						part2Height = Math.max(minPart2Height, sharedHeight * 0.6);
						part3MaxHeight = Math.max(minPart3Height, sharedHeight - part2Height);
					}
				} else {
					part2Height = leftbarPart2.offsetHeight;
					part3MaxHeight = Math.min(part3NaturalHeight, Math.max(minPart3Height, availableHeight - part2Height - gapBetweenParts));
				}

				setLeftbarRootProperty('--leftbar-part2-inner-height', catalogActive ? Math.round(part2Height) + 'px' : 'auto');
				setLeftbarRootProperty('--leftbar-part2-height', Math.round(part2Height + gapBetweenParts) + 'px');
				setLeftbarRootProperty('--leftbar-part3-height', Math.round(part3MaxHeight) + 'px');
				setLeftbarRootProperty('--leftbar-part3-max-height', Math.round(part3MaxHeight) + 'px');
			}
		} else {
			//滚动条在顶部 不浮动状态
			leftbarPart2.classList.remove('sticky');
			leftbarPart2.classList.remove('leftbar-tab-catalog-active');
			if (leftbarPart3) leftbarPart3.classList.remove('sticky');
		}
		if (part1OffsetTop + part1OuterHeight + 10 - scrollTop <= 20) {//侧栏下部分是否可以随 Headroom 一起向上移动
			document.body.classList.add('leftbar-can-headroom');
		} else {
			document.body.classList.remove('leftbar-can-headroom');
		}
		leftbarHeightSignature = getLeftbarHeightSignature();
	}
	changeLeftbarStickyStatus();
	// 使用节流优化滚动事件性能
	const throttledChangeLeftbarStickyStatus = argonEventManager ?
		argonEventManager.throttle(changeLeftbarStickyStatus, 16) :
		changeLeftbarStickyStatus;
	document.addEventListener("scroll", throttledChangeLeftbarStickyStatus, { passive: true });
	$(window).resize(function () {
		part1OffsetTop = $leftbarPart1.offset().top;
		part1OuterHeight = $leftbarPart1.outerHeight();
		changeLeftbarStickyStatus();
	});
	new MutationObserver(function () {
		part1OffsetTop = $leftbarPart1.offset().top;
		part1OuterHeight = $leftbarPart1.outerHeight();
		changeLeftbarStickyStatus();
	}).observe(leftbarPart1, { attributes: true, childList: true, subtree: true });
	// ?? part2/part3 ?????????? sticky ???????????????
	refreshLeftbarHeightObservers();
	if (typeof MutationObserver !== 'undefined') {
		let leftbarHeightMutationObserver = new MutationObserver(requestLeftbarStickyStatusUpdateOnHeightChange);
		if (leftbarPart2) leftbarHeightMutationObserver.observe(leftbarPart2, { childList: true, characterData: true, subtree: true });
		if (leftbarPart3) leftbarHeightMutationObserver.observe(leftbarPart3, { childList: true, characterData: true, subtree: true });
	}
	$(document).on('shown.bs.tab click', '#leftbar_part2 [data-toggle="tab"]', function () {
		refreshLeftbarHeightObservers();
		requestLeftbarStickyStatusUpdate();
		window.setTimeout(function () {
			refreshLeftbarHeightObservers();
			requestLeftbarStickyStatusUpdate();
		}, 180);
	});
	window.argonRequestLeftbarStickyStatusUpdate = function () {
		refreshLeftbarHeightObservers();
		requestLeftbarStickyStatusUpdate();
	};
}();

/*左侧栏滚轮焦点切换*/
!function () {
	if (window.argonLeftbarWheelFocusBound) return;
	window.argonLeftbarWheelFocusBound = true;

	function normalizeWheelDelta(event) {
		let deltaY = event.deltaY;
		if (deltaY === undefined) {
			deltaY = -event.wheelDelta || event.detail || 0;
		}
		if (event.deltaMode === 1) {
			deltaY *= 40;
		} else if (event.deltaMode === 2) {
			deltaY *= window.innerHeight;
		}
		return deltaY;
	}

	function getPageScroller() {
		return document.scrollingElement || document.documentElement || document.body;
	}

	function getMaxScrollTop(element) {
		return Math.max(0, element.scrollHeight - element.clientHeight);
	}

	function canScrollVertically(element) {
		return !!(element && getMaxScrollTop(element) > 1);
	}

	function canScrollBy(element, deltaY) {
		if (!canScrollVertically(element) || deltaY === 0) {
			return false;
		}
		let maxScrollTop = getMaxScrollTop(element);
		let scrollTop = element.scrollTop;
		return deltaY < 0 ? scrollTop > 0 : scrollTop < maxScrollTop - 1;
	}

	function scrollElementBy(element, deltaY) {
		if (!element || deltaY === 0) {
			return;
		}
		if (window.jQuery) {
			jQuery(element).stop(true);
		}
		let maxScrollTop = getMaxScrollTop(element);
		element.scrollTop = Math.max(0, Math.min(element.scrollTop + deltaY, maxScrollTop));
	}

	function getPart2ScrollTarget() {
		let leftbarPart2 = document.getElementById('leftbar_part2');
		if (!leftbarPart2 || !leftbarPart2.classList.contains('sticky')) {
			return null;
		}
		let activePane = leftbarPart2.querySelector('.tab-pane.active.show');
		if (!activePane) {
			activePane = leftbarPart2.querySelector('#leftbar_tab_catalog');
		}
		return canScrollVertically(activePane) ? activePane : null;
	}

	function getPart3ScrollTarget() {
		let leftbarPart3 = document.getElementById('leftbar_part3');
		if (!leftbarPart3) {
			return null;
		}
		let cardBody = leftbarPart3.querySelector('.card-body');
		return canScrollVertically(cardBody) ? cardBody : null;
	}

	function handleLeftbarWheel(event) {
		let target = event.target;
		if (!target || typeof target.closest !== 'function') {
			return;
		}
		let leftbarPart2 = target.closest('#leftbar_part2');
		let leftbarPart3 = target.closest('#leftbar_part3');
		if (!leftbarPart2 && !leftbarPart3) {
			return;
		}
		let deltaY = normalizeWheelDelta(event);
		if (!deltaY) {
			return;
		}
		let focusedScroller = leftbarPart2 ? getPart2ScrollTarget() : getPart3ScrollTarget();
		let pageScroller = getPageScroller();
		let scrollTarget = canScrollBy(focusedScroller, deltaY) ? focusedScroller : pageScroller;
		if (!canScrollBy(scrollTarget, deltaY)) {
			return;
		}
		event.preventDefault();
		event.stopPropagation();
		if (typeof event.stopImmediatePropagation === 'function') {
			event.stopImmediatePropagation();
		}
		scrollElementBy(scrollTarget, deltaY);
	}

	document.addEventListener('wheel', handleLeftbarWheel, { passive: false, capture: true });
	document.addEventListener('mousewheel', handleLeftbarWheel, { passive: false, capture: true });
}();

/*Headroom*/
if (argonConfig.headroom == "true") {
	var headroom = new Headroom(document.querySelector("body"), {
		"tolerance": {
			up: 0,
			down: 0
		},
		"offset": 0,
		"classes": {
			"initial": "with-headroom",
			"pinned": "headroom---pinned",
			"unpinned": "headroom---unpinned",
			"top": "headroom---top",
			"notTop": "headroom---not-top",
			"bottom": "headroom---bottom",
			"notBottom": "headroom---not-bottom",
			"frozen": "headroom---frozen"
		}
	}).init();
}

/*瀑布流布局*/
function waterflowInit() {
	if (argonConfig.waterflow_columns == "1") {
		return;
	}
	$("#main.article-list img").each(function (index, ele) {
		ele.onload = function () {
			waterflowInit();
		}
	});
	let columns;
	if (argonConfig.waterflow_columns == "2and3") {
		if ($("#main").outerWidth() > 1000) {
			columns = 3;
		} else {
			columns = 2;
		}
	} else {
		columns = parseInt(argonConfig.waterflow_columns);
	}
	if ($("#main").outerWidth() < 650 && columns == 2) {
		columns = 1;
	} else if ($("#main").outerWidth() < 800 && columns == 3) {
		columns = 1;
	}

	let heights = [0, 0, 0];
	function getMinHeightPosition() {
		let res = 0, minn = 2147483647;
		for (var i = 0; i < columns; i++) {
			if (heights[i] < minn) {
				minn = heights[i];
				res = i;
			}
		}
		return res;
	}
	function getMaxHeight() {
		let res = 0;
		for (let i in heights) {
			res = Math.max(res, heights[i]);
		}
		return res;
	}
	$("#primary").css("transition", "none")
		.addClass("waterflow");
	let $container = $("#main.article-list");
	if (!$container.length) {
		return;
	}
	let $items = $container.find("article.post:not(.no-results), .shuoshuo-preview-container");
	columns = Math.max(Math.min(columns, $items.length), 1);
	if (columns == 1) {
		$container.removeClass("waterflow");
		$items.css("transition", "").css("position", "").css("width", "").css("top", "").css("left", "").css("margin", "");
		$(".waterflow-placeholder").remove();
	} else {
		$container.addClass("waterflow");
		$items.each(function (index, item) {
			let $item = $(item);
			$item.css("transition", "none")
				.css("position", "absolute")
				.css("width", "calc(" + (100 / columns) + "% - " + (10 * (columns - 1) / columns) + "px)").css("margin", 0);
			let itemHeight = $item.outerHeight() + 10;
			let pos = getMinHeightPosition();
			$item.css("top", heights[getMinHeightPosition()] + "px")
				.css("left", (pos * $item.outerWidth() + 10 * pos) + "px");
			heights[pos] += itemHeight;
		});
	}
	if ($(".waterflow-placeholder").length) {
		$(".waterflow-placeholder").css("height", getMaxHeight() + "px");
	} else {
		$container.prepend("<div class='waterflow-placeholder' style='height: " + getMaxHeight() + "px;'></div>");
	}
}
waterflowInit();
if (argonConfig.waterflow_columns != "1") {
	$(window).resize(function () {
		waterflowInit();
	});
	new MutationObserver(function (mutations, observer) {
		waterflowInit();
	}).observe(document.querySelector("#primary"), {
		'childList': true
	});
}

/*移动端文章列表布局切换*/
!function () {
	var mobileLayout = argonConfig.article_list_layout_mobile || "1";
	var isMobile = window.innerWidth <= 900;

	function applyMobileLayout() {
		var nowMobile = window.innerWidth <= 900;
		if (nowMobile) {
			$("html").addClass("mobile-layout-" + mobileLayout);
		} else {
			$("html").removeClass("mobile-layout-1 mobile-layout-2 mobile-layout-3");
		}
	}

	applyMobileLayout();
	$(window).resize(function () {
		applyMobileLayout();
	});
}();

/*浮动按钮栏相关(回顶部)*/
function expandPostComments() {
	var commentsArea = $("#comments");
	var postCommentArea = $("#post_comment");
	var toggleBtn = $("#comments_toggle");
	commentsArea.removeClass("comments-collapsed");
	postCommentArea.removeClass("comments-collapsed");
	if (toggleBtn.length > 0) {
		toggleBtn.addClass("expanded");
		toggleBtn.find("i").removeClass("fa-comments").addClass("fa-comment-o");
		toggleBtn.find(".btn-inner--text").text(__("????"));
	}
}
window.expandPostComments = expandPostComments;

!function () {
	// 确保 DOM 和 jQuery 已加载
	if (typeof jQuery === 'undefined') {
		setTimeout(arguments.callee, 50);
		return;
	}

	let $fabtns = $('#float_action_buttons');
	if ($fabtns.length === 0) {
		setTimeout(arguments.callee, 50);
		return;
	}

	let $backToTopBtn = $('#fabtn_back_to_top');
	let $toggleSidesBtn = $('#fabtn_toggle_sides');
	let $toggleDarkmode = $('#fabtn_toggle_darkmode');
	let $toggleAmoledMode = $('#blog_setting_toggle_darkmode_and_amoledarkmode');
	let $toggleBlogSettings = $('#fabtn_toggle_blog_settings_popup');
	let $goToComment = $('#fabtn_go_to_comment');

	let $readingProgressBtn = $('#fabtn_reading_progress');
	let $readingProgressBar = $('#fabtn_reading_progress_bar');
	let $readingProgressDetails = $('#fabtn_reading_progress_details');

	$backToTopBtn.on("click", function () {
		$("body,html").stop().animate({
			scrollTop: 0
		}, 600);
	});

	$toggleDarkmode.on("click", function () {
		toggleDarkmode();
	});

	$toggleAmoledMode.on("click", function () {
		toggleAmoledDarkMode();
	})

	if ($("#post_comment").length > 0) {
		$("#fabtn_go_to_comment").removeClass("fabtn-hidden");
	} else {
		$("#fabtn_go_to_comment").addClass("fabtn-hidden");
	}
	$goToComment.on("click", function () {
		var commentsArea = $("#comments");
		var postCommentArea = $("#post_comment");
		var wasCollapsed = commentsArea.hasClass("comments-collapsed");
		var toggleBtn = $("#comments_toggle");

		if (wasCollapsed && toggleBtn.length > 0) {
			// 折叠状态：先滚动到评论切换按钮位置，再展开
			$("body,html").stop().animate({
				scrollTop: toggleBtn.offset().top - 80
			}, 600);
			toggleBtn.find("i").removeClass("fa-comments").addClass("fa-comment-o");
			toggleBtn.find(".btn-inner--text").text("折叠评论");
			toggleBtn.addClass("expanded");
			commentsArea.removeClass("comments-collapsed");
			setTimeout(function () {
				postCommentArea.removeClass("comments-collapsed");
				$("#post_comment_content").focus();
			}, 150);
		} else {
			// 已展开或无切换按钮：直接滚动到评论区
			$("body,html").stop().animate({
				scrollTop: postCommentArea.offset().top - 80
			}, 600);
			$("#post_comment_content").focus();
		}
	});

	if (localStorage['Argon_fabs_Floating_Status'] == "left") {
		$fabtns.addClass("fabtns-float-left");
	}
	$toggleSidesBtn.on("click", function () {
		$fabtns.addClass("fabtns-unloaded");
		setTimeout(function () {
			$fabtns.toggleClass("fabtns-float-left");
			if ($fabtns.hasClass("fabtns-float-left")) {
				localStorage['Argon_fabs_Floating_Status'] = "left";
			} else {
				localStorage['Argon_fabs_Floating_Status'] = "right";
			}
			$fabtns.removeClass("fabtns-unloaded");
		}, 300);
	});
	//博客设置
	$toggleBlogSettings.on("click", function () {
		$("#float_action_buttons").toggleClass("blog_settings_opened");
	});
	$("#close_blog_settings").on("click", function () {
		$("#float_action_buttons").removeClass("blog_settings_opened");
	});
	$("#blog_setting_darkmode_switch .custom-toggle-slider").on("click", function () {
		toggleDarkmode();
	});
	//字体
	$("#blog_setting_font_sans_serif").on("click", function () {
		$("html").removeClass("use-serif");
		localStorage['Argon_Use_Serif'] = "false";
	});
	$("#blog_setting_font_serif").on("click", function () {
		$("html").addClass("use-serif");
		localStorage['Argon_Use_Serif'] = "true";
	});
	// 字体设置已在 header.php 中预加载，此处无需重复应用

	//阴影
	$("#blog_setting_shadow_small").on("click", function () {
		$("html").removeClass("use-big-shadow");
		localStorage['Argon_Use_Big_Shadow'] = "false";
	});
	$("#blog_setting_shadow_big").on("click", function () {
		$("html").addClass("use-big-shadow");
		localStorage['Argon_Use_Big_Shadow'] = "true";
	});
	// 阴影设置已在 header.php 中预加载，此处无需重复应用
	//滤镜
	function setBlogFilter(name) {
		if (name == undefined || name == "") {
			name = "off";
		}
		if (!$("html").hasClass("filter-" + name)) {
			$("html").removeClass("filter-sunset filter-darkness filter-grayscale");
			if (name != "off") {
				$("html").addClass("filter-" + name);
			}
		}
		$("#blog_setting_filters .blog-setting-filter-btn").removeClass("active");
		$("#blog_setting_filters .blog-setting-filter-btn[filter-name='" + name + "']").addClass("active");
		localStorage['Argon_Filter'] = name;
	}
	// 滤镜设置已在 header.php 中预加载，此处只需设置按钮状态
	let currentFilter = localStorage['Argon_Filter'] || 'off';
	$("#blog_setting_filters .blog-setting-filter-btn[filter-name='" + currentFilter + "']").addClass("active");
	$(".blog-setting-filter-btn").on("click", function () {
		setBlogFilter(this.getAttribute("filter-name"));
	});

	//UI 样式切换 (玻璃拟态/新拟态)
	function setUIStyle(style) {
		if (style == undefined || style == "") {
			style = "default";
		}
		$("html").removeClass("style-glass style-neumorphism");
		if (style != "default") {
			$("html").addClass("style-" + style);
		}
		$(".blog-setting-style-btn").removeClass("active");
		$(".blog-setting-style-btn[style-name='" + style + "']").addClass("active");
		localStorage['Argon_UI_Style'] = style;
	}
	// UI 样式设置已在 header.php 中预加载，此处只需设置按钮状态
	let currentUIStyle = localStorage['Argon_UI_Style'] || 'default';
	$(".blog-setting-style-btn[style-name='" + currentUIStyle + "']").addClass("active");
	$(".blog-setting-style-btn").on("click", function () {
		setUIStyle(this.getAttribute("style-name"));
	});

	let $window = $(window);

	function changefabtnDisplayStatus() {
		//阅读进度
		function hideReadingProgress() {
			$readingProgressBtn.addClass("fabtn-hidden");
		}
		function setReadingProgress(percent) {
			$readingProgressBtn.removeClass("fabtn-hidden");
			$readingProgressDetails.html((percent * 100).toFixed(0) + "%");
			$readingProgressBar.css("width", (percent * 100).toFixed(0) + "%");
		}
		if ($("article.post.post-full").length == 0) {
			hideReadingProgress();
		} else {
			let a = $window.scrollTop() - ($("article.post.post-full").offset().top - 80);
			let b = $("article.post.post-full").outerHeight() + 50 - $window.height();
			if (b <= 0) {
				hideReadingProgress();
			} else {
				readingProgress = a / b;
				if (isNaN(readingProgress) || readingProgress < 0 || readingProgress > 1) {
					hideReadingProgress();
				} else {
					setReadingProgress(readingProgress);
				}
			}
		}
		//是否显示回顶
		if ($(window).scrollTop() >= 400) {
			$backToTopBtn.removeClass("fabtn-hidden");
		} else {
			$backToTopBtn.addClass("fabtn-hidden");
		}
	}
	changefabtnDisplayStatus();
	$(window).scroll(function () {
		changefabtnDisplayStatus();
	});
	$(window).resize(function () {
		changefabtnDisplayStatus();
	});
	$fabtns.removeClass("fabtns-unloaded");
}();

/*卡片圆角大小调整*/
!function () {
	function setCardRadius(radius, setcookie) {
		document.documentElement.style.setProperty('--card-radius', radius + "px");
		if (setcookie) {
			setCookie("argon_card_radius", radius, 365);
		}
	}
	let slider = document.getElementById('blog_setting_card_radius');
	noUiSlider.create(slider, {
		start: [$("meta[name='theme-card-radius']").attr("content")],
		step: 0.5,
		connect: [true, false],
		range: {
			'min': [0],
			'max': [30]
		}
	});
	slider.noUiSlider.on('update', function (values) {
		let value = values[0];
		setCardRadius(value, false);
	});
	slider.noUiSlider.on('set', function (values) {
		let value = values[0];
		setCardRadius(value, true);
	});
	$(document).on("click", "#blog_setting_card_radius_to_default", function () {
		slider.noUiSlider.set($("meta[name='theme-card-radius-origin']").attr("content"));
		setCardRadius($("meta[name='theme-card-radius-origin']").attr("content"), false);
		setCookie("argon_card_radius", $("meta[name='theme-card-radius-origin']").attr("content"), 0);
	});
}();

/*评论区 & 发送评论*/
!function () {
	//回复评论
	let replying = false, replyID = 0;

	/**
	 * 显示回复框
	 * @param {number} commentID - 评论ID
	 */
	function reply(commentID) {
		cancelEdit(false);
		replying = true;
		replyID = commentID;
		let nameEl = $("#comment-" + commentID + " .comment-item-title > .comment-name")[0];
		let textEl = $("#comment-" + commentID + " .comment-item-text")[0];
		let sourceEl = $("#comment-" + commentID + " .comment-item-source")[0];
		if (nameEl) {
			$("#post_comment_reply_name").text(nameEl.textContent);
		}
		let preview = textEl ? textEl.textContent : '';
		if (sourceEl && sourceEl.innerHTML !== '') {
			preview = sourceEl.textContent;
		}
		$("#post_comment_reply_preview").text(preview);
		if ($("#comment-" + commentID + " .comment-item-title .badge-private-comment").length > 0) {
			$("#post_comment").addClass("post-comment-force-privatemode-on");
		} else {
			$("#post_comment").addClass("post-comment-force-privatemode-off");
		}

		// 滚动到评论框（使用原生 scrollTo 避免 jQuery easing 依赖问题）
		let postComment = $('#post_comment');
		if (postComment.length > 0 && postComment.offset()) {
			let targetTop = postComment.offset().top - 100;
			window.scrollTo({
				top: targetTop,
				behavior: 'smooth'
			});
		}

		// 使用 CSS 动画显示回复框
		let replyInfo = $('#post_comment_reply_info');
		if (replyInfo.length > 0) {
			replyInfo.removeClass('reply-leaving').css('display', 'block');
			// 触发重排以确保动画生效
			replyInfo[0].offsetHeight;
			replyInfo.addClass('reply-entering');
		}

		setTimeout(function () {
			$("#post_comment_content").focus();
		}, 300);
	}

	/**
	 * 取消回复
	 */
	function cancelReply() {
		replying = false;
		replyID = 0;
		let replyInfo = $('#post_comment_reply_info');
		replyInfo.removeClass('reply-entering').addClass('reply-leaving');
		setTimeout(function () {
			replyInfo.css('display', 'none').removeClass('reply-leaving');
		}, 200);
		$("#post_comment").removeClass("post-comment-force-privatemode-on post-comment-force-privatemode-off");
	}

	$(document).on("click", ".comment-reply", function () {
		reply(this.getAttribute("data-id"));
	});
	$(document).on("click pjax:click", "#post_comment_reply_cancel", function () {
		cancelReply();
	});
	$(document).on("pjax:click", function () {
		replying = false;
		replyID = 0;
		$('#post_comment_reply_info').css("display", "none").removeClass('reply-entering reply-leaving');
		$("#post_comment").removeClass("post-comment-force-privatemode-on post-comment-force-privatemode-off");
	});
	//编辑评论
	let editing = false, editID = 0;
	function edit(commentID) {
		cancelReply();
		editing = true;
		editID = commentID;
		$('#post_comment').addClass("editing");
		$("#post_comment_content").val($("#comment-" + editID + " .comment-item-source").text());
		$("#post_comment_content").trigger("change");
		if ($("#comment-" + editID).data("use-markdown") == true && document.getElementById("comment_post_use_markdown") != null) {
			document.getElementById("comment_post_use_markdown").checked = true;
		} else {
			document.getElementById("comment_post_use_markdown").checked = false;
		}
		if ($("#comment-" + commentID + " .comment-item-title .badge-private-comment").length > 0) {
			$("#post_comment").addClass("post-comment-force-privatemode-on");
		} else {
			$("#post_comment").addClass("post-comment-force-privatemode-off");
		}
		// 使用原生 scrollTo 避免 jQuery easing 依赖问题
		let postCommentEl = document.getElementById('post_comment');
		if (postCommentEl) {
			window.scrollTo({
				top: postCommentEl.getBoundingClientRect().top + window.pageYOffset - 100,
				behavior: 'smooth'
			});
		}
		$("#post_comment_content").focus();
	}
	function cancelEdit(clear) {
		editing = false;
		editID = 0;
		$("#post_comment").removeClass("post-comment-force-privatemode-on post-comment-force-privatemode-off");
		if (clear == true) $("#post_comment_content").val("");
		$("#post_comment_content").trigger("change");
		$('#post_comment').removeClass("editing");
	}
	$(document).on("click", ".comment-edit", function () {
		edit(this.getAttribute("data-id"));
	});
	$(document).on("click", "#post_comment_edit_cancel", function () {
		// 使用原生 scrollTo 避免 jQuery easing 依赖问题
		let commentEl = document.getElementById("comment-" + editID);
		if (commentEl) {
			window.scrollTo({
				top: commentEl.getBoundingClientRect().top + window.pageYOffset - 100,
				behavior: 'smooth'
			});
		}
		cancelEdit(true);
	});
	$(document).on("pjax:click", function () {
		cancelEdit(true);
	});
	$(document).on("click", ".comment-pin, .comment-unpin", function () {
		toogleCommentPin(this.getAttribute("data-id"), !this.classList.contains("comment-pin"));
	});
	$(document).on("click", ".comment-delete", function () {
		deleteComment(this.getAttribute("data-id"));
	});
	$(document).on("mouseenter", ".comment-parent-info", function () {
		$("#comment-" + this.getAttribute("data-parent-id")).addClass("highlight");
	});
	$(document).on("mouseleave", ".comment-parent-info", function () {
		$("#comment-" + this.getAttribute("data-parent-id")).removeClass("highlight");
	});
	//切换评论置顶状态
	function toogleCommentPin(commentID, pinned) {
		$("#comment_pin_comfirm_dialog .modal-title").html(pinned ? __("取消置顶评论") : __("置顶评论"));
		$("#comment_pin_comfirm_dialog .modal-body").html(pinned ? __("是否要取消置顶评论 #") + commentID + "?" : __("是否要置顶评论 #") + commentID + "?");
		$("#comment_pin_comfirm_dialog .btn-comfirm").html(__("确认")).attr("disabled", false);
		$("#comment_pin_comfirm_dialog .btn-dismiss").html(__("取消")).attr("disabled", false);
		$("#comment_pin_comfirm_dialog .btn-comfirm").off("click").on("click", function () {
			$("#comment_pin_comfirm_dialog .btn-dismiss").attr("disabled", true)
			$("#comment_pin_comfirm_dialog .btn-comfirm").attr("disabled", true).prepend(__(`<span class="btn-inner--icon" style="margin-right: 10px;"><i class="fa fa-spinner fa-spin"></i></span>`));
			$.ajax({
				type: 'POST',
				url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
				dataType: "json",
				data: {
					action: "pin_comment",
					id: commentID,
					pinned: pinned ? "false" : "true"
				},
				success: function (result) {
					$("#comment_pin_comfirm_dialog").modal('hide');
					if (result.status == "success") {
						if (pinned) {
							$("#comment-" + commentID + " .comment-name .badge-pinned").remove();
							$("#comment-" + commentID + " .comment-unpin").removeClass("comment-unpin").addClass("comment-pin").html(__("置顶"));
						} else {
							$("#comment-" + commentID + " .comment-name").append(`<span class="badge badge-danger badge-pinned">${__("置顶")}</span>`);
							$("#comment-" + commentID + " .comment-pin").removeClass("comment-pin").addClass("comment-unpin").html(__("取消置顶"));
						}
						iziToast.show({
							title: pinned ? __("取消置顶成功") : __("置顶成功"),
							message: pinned ? __("该评论已取消置顶") : __("该评论已置顶"),
							class: 'shadow-sm',
							position: 'topRight',
							backgroundColor: '#2dce89',
							titleColor: '#ffffff',
							messageColor: '#ffffff',
							iconColor: '#ffffff',
							progressBarColor: '#ffffff',
							icon: 'fa fa-check',
							timeout: 5000
						});
					} else {
						iziToast.show({
							title: pinned ? __("取消置顶失败") : __("置顶失败"),
							message: result.msg,
							class: 'shadow-sm',
							position: 'topRight',
							backgroundColor: '#f5365c',
							titleColor: '#ffffff',
							messageColor: '#ffffff',
							iconColor: '#ffffff',
							progressBarColor: '#ffffff',
							icon: 'fa fa-close',
							timeout: 5000
						});
					}
				},
				error: function (result) {
					$("#comment_pin_comfirm_dialog").modal('hide');
					iziToast.show({
						title: pinned ? __("取消置顶失败") : __("置顶失败"),
						message: __("未知错误"),
						class: 'shadow-sm',
						position: 'topRight',
						backgroundColor: '#f5365c',
						titleColor: '#ffffff',
						messageColor: '#ffffff',
						iconColor: '#ffffff',
						progressBarColor: '#ffffff',
						icon: 'fa fa-close',
						timeout: 5000
					});
				}
			});
		});
		$("#comment_pin_comfirm_dialog").modal(null);
	}

	//删除评论
	function deleteComment(commentID) {
		if (!confirm(__('确定要删除评论 #') + commentID + '?')) {
			return;
		}

		$.ajax({
			type: 'POST',
			url: argonConfig.wp_path + 'wp-admin/admin-ajax.php',
			dataType: 'json',
			data: {
				action: 'frontend_delete_comment',
				id: commentID
			},
			success: function (result) {
				if (result.status === 'success') {
					// 移除评论元素
					$('#comment-' + commentID).fadeOut(300, function () {
						$(this).next('.comment-divider').remove();
						$(this).remove();
					});

					iziToast.show({
						title: __('删除成功'),
						message: result.msg,
						class: 'shadow-sm',
						position: 'topRight',
						backgroundColor: '#2dce89',
						titleColor: '#ffffff',
						messageColor: '#ffffff',
						iconColor: '#ffffff',
						progressBarColor: '#ffffff',
						icon: 'fa fa-check',
						timeout: 5000
					});
				} else {
					iziToast.show({
						title: __('删除失败'),
						message: result.msg,
						class: 'shadow-sm',
						position: 'topRight',
						backgroundColor: '#f5365c',
						titleColor: '#ffffff',
						messageColor: '#ffffff',
						iconColor: '#ffffff',
						progressBarColor: '#ffffff',
						icon: 'fa fa-close',
						timeout: 5000
					});
				}
			},
			error: function () {
				iziToast.show({
					title: __('删除失败'),
					message: __('未知错误'),
					class: 'shadow-sm',
					position: 'topRight',
					backgroundColor: '#f5365c',
					titleColor: '#ffffff',
					messageColor: '#ffffff',
					iconColor: '#ffffff',
					progressBarColor: '#ffffff',
					icon: 'fa fa-close',
					timeout: 5000
				});
			}
		});
	}


	//显示/隐藏额外输入框(评论者网址)
	$(document).on("click", "#post_comment_toggle_extra_input", function () {
		$("#post_comment").toggleClass("show-extra-input");
		if ($("#post_comment").hasClass("show-extra-input")) {
			$("#post_comment_extra_input").slideDown(250, 'easeOutCirc');
		} else {
			$("#post_comment_extra_input").slideUp(200, 'easeOutCirc');
		}
	});

	//输入框细节
	$(document).on("change input keydown keyup propertychange", "#post_comment_content", function () {
		$("#post_comment_content_hidden")[0].innerText = $("#post_comment_content").val() + "\n";
		$("#post_comment_content").css("height", $("#post_comment_content_hidden").outerHeight());
	});
	$(document).on("focus", "#post_comment_link", function () {
		$(".post-comment-link-container").addClass("active");
	});
	$(document).on("blur", "#post_comment_link", function () {
		$(".post-comment-link-container").removeClass("active");
	});
	$(document).on("focus", "#post_comment_captcha", function () {
		$(".post-comment-captcha-container").addClass("active");
	});
	$(document).on("blur", "#post_comment_captcha", function () {
		$(".post-comment-captcha-container").removeClass("active");
	});

	function restoreCommentSubmitControls(isAdmin) {
		$("#post_comment").removeClass("sending");
		$("#post_comment_content").removeAttr("disabled");
		$("#post_comment_name").removeAttr("disabled");
		$("#post_comment_email").removeAttr("disabled");
		$("#post_comment_link").removeAttr("disabled");
		$("#post_comment_send").removeAttr("disabled");
		$("#post_comment_reply_cancel").removeAttr("disabled");
		if (!isAdmin) {
			$("#post_comment_captcha").removeAttr("disabled");
		}
		$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-send'></i>");
		$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送"));
		window.geetestAutoSubmitting = false;
	}

	function resetGeetestCommentCaptcha() {
		if ($("#geetest-captcha").length == 0) {
			return;
		}
		window.geetestVerified = false;
		window.geetestAutoSubmitting = false;
		$("#geetest_lot_number").val("");
		$("#geetest_captcha_output").val("");
		$("#geetest_pass_token").val("");
		$("#geetest_gen_time").val("");
		if (window.geetestCaptcha) {
			try {
				window.geetestCaptcha.reset();
			} catch (e) {
				ArgonDebug.warn('Geetest reset error:', e);
			}
		}
	}

	//发送评论
	window.postComment = function postComment() {
		let commentContent = $("#post_comment_content").val();
		let commentName = $("#post_comment_name").val();
		let commentEmail = $("#post_comment_email").val();
		let commentLink = $("#post_comment_link").val();
		let commentCaptcha = $("#post_comment_captcha").val();
		let useMarkdown = false;
		let privateMode = false;
		let mailNotice = false;
		if ($("#comment_post_use_markdown").length > 0) {
			useMarkdown = $("#comment_post_use_markdown")[0].checked;
		}
		if ($("#comment_post_privatemode").length > 0) {
			privateMode = $("#comment_post_privatemode")[0].checked;
		}
		if ($("#comment_post_mailnotice").length > 0) {
			mailNotice = $("#comment_post_mailnotice")[0].checked;
		}

		let postID = $("#post_comment_post_id").val();
		let commentCaptchaSeed = $("#post_comment_captcha_seed").val();
		let needCaptcha = !$("#post_comment").hasClass("no-need-captcha");
		let useGeetestCaptcha = needCaptcha && $("#geetest-captcha").length > 0;

		let isError = false;
		let errorMsg = "";

		//检查表单合法性
		if (commentContent.match(/^\s*$/)) {
			isError = true;
			errorMsg += __("评论内容不能为空") + "</br>";
		}
		if (!$("#post_comment").hasClass("no-need-name-email")) {
			if (commentName.match(/^\s*$/)) {
				isError = true;
				errorMsg += __("昵称不能为空") + "</br>";
			}
			if ($("#post_comment").hasClass("enable-qq-avatar")) {
				if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)) {
					isError = true;
					errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
				}
			} else {
				if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)) {
					isError = true;
					errorMsg += __("邮箱格式错误") + "</br>";
				}
			}
		} else {
			if (commentEmail.length || (document.getElementById("comment_post_mailnotice") != null && document.getElementById("comment_post_mailnotice").checked == true)) {
				if ($("#post_comment").hasClass("enable-qq-avatar")) {
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)) {
						isError = true;
						errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
					}
				} else {
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)) {
						isError = true;
						errorMsg += __("邮箱格式错误") + "</br>";
					}
				}
			}
		}
		if (commentLink != "" && commentLink.indexOf('.') == -1) {
			isError = true;
			errorMsg += __("网站格式错误") + "</br>";
		}
		if (needCaptcha) {
			// 检查是否使用Geetest验证码
			if (useGeetestCaptcha) {
				// 检查Geetest库是否加载成功
				if (typeof window.geetestLoadFailed !== 'undefined' && window.geetestLoadFailed) {
					isError = true;
					errorMsg += __("验证码服务不可用，请刷新页面重试");
				} else if (typeof window.geetestCaptcha === 'undefined' || !window.geetestCaptcha) {
					isError = true;
					errorMsg += __("验证码未初始化，请稍后重试");;
				} else {
					// Geetest验证码检查
					let lotNumber = $("#geetest_lot_number").val();
					let captchaOutput = $("#geetest_captcha_output").val();
					let passToken = $("#geetest_pass_token").val();
					let genTime = $("#geetest_gen_time").val();

					if (!lotNumber || !captchaOutput || !passToken || !genTime) {
						isError = true;
						errorMsg += __("请完成验证码验证");
					}
				}
			} else {
				// 原有的数学验证码验证
				if (commentCaptcha == "") {
					isError = true;
					errorMsg += __("验证码未输入");
				}
				if (commentCaptcha != "" && !(/^[0-9]+$/).test(commentCaptcha)) {
					isError = true;
					errorMsg += __("验证码格式错误");
				}
			}
		}
		if (isError) {
			restoreCommentSubmitControls(false);

			iziToast.show({
				title: __("评论格式错误"),
				message: errorMsg,
				class: 'shadow-sm',
				position: 'topRight',
				backgroundColor: '#f5365c',
				titleColor: '#ffffff',
				messageColor: '#ffffff',
				iconColor: '#ffffff',
				progressBarColor: '#ffffff',
				icon: 'fa fa-close',
				timeout: 5000
			});
			return;
		}

		//增加 disabled 属性和其他的表单提示
		$("#post_comment").addClass("sending");
		$("#post_comment_content").attr("disabled", "disabled");
		$("#post_comment_name").attr("disabled", "disabled");
		$("#post_comment_email").attr("disabled", "disabled");
		$("#post_comment_captcha").attr("disabled", "disabled");
		$("#post_comment_link").attr("disabled", "disabled");
		$("#post_comment_send").attr("disabled", "disabled");
		$("#post_comment_reply_cancel").attr("disabled", "disabled");
		$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-spinner fa-spin'></i>");
		$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送中"));

		iziToast.show({
			title: __("正在发送"),
			message: __("评论正在发送中..."),
			class: 'shadow-sm iziToast-noprogressbar',
			position: 'topRight',
			backgroundColor: 'var(--themecolor)',
			titleColor: '#ffffff',
			messageColor: '#ffffff',
			iconColor: '#ffffff',
			progressBarColor: '#ffffff',
			icon: 'fa fa-spinner fa-spin',
			close: false,
			timeout: 999999999
		});

		// 准备数据
		var ajaxData = {
			action: "ajax_post_comment",
			comment: commentContent,
			author: commentName,
			email: commentEmail,
			url: (commentLink != "" && !(/^https?:\/\//).test(commentLink) && !(/^\/\//).test(commentLink)) ? ("http://" + commentLink) : commentLink,
			comment_post_ID: postID,
			comment_parent: replyID,
			"wp-comment-cookies-consent": "yes",
			use_markdown: useMarkdown ? "true" : "false",
			private_mode: privateMode ? "true" : "false",
			enable_mailnotice: mailNotice ? "true" : "false",
			argon_nonce: $("#argon_comment_nonce").val() || "",
			argon_comment_honeypot: $("#argon_comment_honeypot").val() || ""
		};
		ajaxData.comment_parent = replyID || 0;

		// 根据验证码类型添加相应参数
		if (useGeetestCaptcha) {
			// 检查Geetest库加载状态
			if (typeof window.geetestLoadFailed !== 'undefined' && window.geetestLoadFailed) {
				restoreCommentSubmitControls(false);

				setTimeout(function () {
					try {
						iziToast.destroy();
						iziToast.error({
							title: __('评论发送失败'),
							message: __('验证码服务不可用，请刷新页面重试'),
							position: 'topRight'
						});
					} catch (e) {
						ArgonDebug.warn('iziToast error:', e);
					}
				}, 0);
				return false;
			}

			// Geetest验证码参数 - 使用后端期望的参数名
			ajaxData.lot_number = $("#geetest_lot_number").val();
			ajaxData.captcha_output = $("#geetest_captcha_output").val();
			ajaxData.pass_token = $("#geetest_pass_token").val();
			ajaxData.gen_time = $("#geetest_gen_time").val();

			// 验证Geetest参数是否完整
			if (!ajaxData.lot_number || !ajaxData.captcha_output || !ajaxData.pass_token || !ajaxData.gen_time) {
				restoreCommentSubmitControls(false);
				resetGeetestCommentCaptcha();

				// 使用 setTimeout 确保 iziToast 操作在下一个事件循环中执行
				setTimeout(function () {
					try {
						iziToast.destroy();
						iziToast.error({
							title: __('评论发送失败'),
							message: __('请完成验证码验证'),
							position: 'topRight'
						});
					} catch (e) {
						ArgonDebug.warn('iziToast error:', e);
					}
				}, 0);
				return false;
			}
		} else if (needCaptcha) {
			// 原有数学验证码参数
			ajaxData.comment_captcha_seed = commentCaptchaSeed;
			ajaxData.comment_captcha = commentCaptcha;
		}

		$.ajax({
			type: 'POST',
			url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
			dataType: "json",
			data: ajaxData,
			success: function (result) {
				restoreCommentSubmitControls(result.isAdmin);
				$("#post_comment").removeClass("show-extra-input post-comment-force-privatemode-on post-comment-force-privatemode-off");

				//判断是否有错误
				if (result.status == "failed") {
					resetGeetestCommentCaptcha();
					// 使用 setTimeout 确保 iziToast 操作在下一个事件循环中执行
					setTimeout(function () {
						try {
							iziToast.destroy();
							iziToast.show({
								title: __("评论发送失败"),
								message: result.msg,
								class: 'shadow-sm',
								position: 'topRight',
								backgroundColor: '#f5365c',
								titleColor: '#ffffff',
								messageColor: '#ffffff',
								iconColor: '#ffffff',
								progressBarColor: '#ffffff',
								icon: 'fa fa-close',
								timeout: 5000
							});
						} catch (e) {
							ArgonDebug.warn('iziToast error:', e);
						}
					}, 0);
					return;
				}

				//发送成功
				// 先复位评论表单（确保无论后续操作是否成功都能重置表单）
				cancelReply();
				$("#post_comment_content").val("");

				// 重置数学验证码
				$("#post_comment_captcha_seed").val(result.newCaptchaSeed);
				$("#post_comment_captcha + style").html(".post-comment-captcha-container:before{content: '" + result.newCaptcha + "';}");
				$("#post_comment_captcha").val("");

				// 清空Geetest验证码隐藏字段并重置验证码实例
				if ($("#geetest-captcha").length > 0) {
					resetGeetestCommentCaptcha();
				}
				if (typeof window.expandPostComments === "function") {
					window.expandPostComments();
				} else {
					$("#comments, #post_comment").removeClass("comments-collapsed");
				}

				// 显示成功提示
				setTimeout(function () {
					try {
						iziToast.destroy();
						iziToast.show({
							title: __("发送成功"),
							message: __("您的评论已发送"),
							class: 'shadow-sm',
							position: 'topRight',
							backgroundColor: '#2dce89',
							titleColor: '#ffffff',
							messageColor: '#ffffff',
							iconColor: '#ffffff',
							progressBarColor: '#ffffff',
							icon: 'fa fa-check',
							timeout: 5000
						});
					} catch (e) {
						ArgonDebug.warn('iziToast error:', e);
					}
				}, 0);

				// 插入新评论
				try {
					result.html = String(result.html || "").replace(/<(\/).noscript>/g, "");
					let parentID = result.parentID;
					if (parentID == "" || parentID == null) {
						parentID = 0;
					}
					parentID = parseInt(parentID);
					if (parentID == 0) {
						if ($("#comments > .card-body > ol.comment-list").length == 0) {
							$("#comments > .card-body").html("<h2 class='comments-title'><i class='fa fa-comments'></i> " + __("评论") + "</h2><ol class='comment-list'></ol>");
						}
						if (result.commentOrder == "asc") {
							$("#comments > .card-body > ol.comment-list").append(result.html);
						} else {
							$("#comments > .card-body > ol.comment-list").prepend(result.html);
						}
					} else {
						if ($("#comment-" + parentID + " + .comment-divider + li > ul.children").length > 0) {
							$("#comment-" + parentID + " + .comment-divider + li > ul.children").append(result.html);
						} else {
							$("#comment-" + parentID + " + .comment-divider").after("<li><ul class='children'>" + result.html + "</ul></li>");
						}
					}
					calcHumanTimesOnPage();

					// ??????
					var newComment = $("#comment-" + result.id);
					if (newComment.length > 0) {
						$("body,html").animate({
							scrollTop: newComment.offset().top - 100
						}, 500, 'easeOutExpo');
					}
				} catch (e) {
					ArgonDebug.warn('Comment insertion error:', e);
				}
			},
			error: function (result) {
				restoreCommentSubmitControls(false);
				$("#post_comment").removeClass("show-extra-input post-comment-force-privatemode-on post-comment-force-privatemode-off");
				resetGeetestCommentCaptcha();

				// 安全地处理iziToast 操作，防止time 属性错误
				setTimeout(function () {
					try {
						iziToast.destroy();
						iziToast.show({
							title: __("评论发送失败"),
							message: __("未知原因"),
							class: 'shadow-sm',
							position: 'topRight',
							backgroundColor: '#f5365c',
							titleColor: '#ffffff',
							messageColor: '#ffffff',
							iconColor: '#ffffff',
							progressBarColor: '#ffffff',
							icon: 'fa fa-close',
							timeout: 5000
						});
					} catch (e) {
						ArgonDebug.warn('iziToast operation error:', e);
					}
				}, 10);
				return;
			}
		});
	}
	//编辑评论
	function editComment() {
		let commentContent = $("#post_comment_content").val();
		let isError = false;
		let errorMsg = "";
		if (commentContent.match(/^\s*$/)) {
			isError = true;
			errorMsg += __("评论内容不能为空") + "</br>";
		}
		if (isError) {
			iziToast.show({
				title: __("评论格式错误"),
				message: errorMsg,
				class: 'shadow-sm',
				position: 'topRight',
				backgroundColor: '#f5365c',
				titleColor: '#ffffff',
				messageColor: '#ffffff',
				iconColor: '#ffffff',
				progressBarColor: '#ffffff',
				icon: 'fa fa-close',
				timeout: 5000
			});
			return;
		}

		//增加 disabled 属性和其他的表单提交
		$("#post_comment_content").attr("disabled", "disabled");
		$("#post_comment_send").attr("disabled", "disabled");
		$("#post_comment_edit_cancel").attr("disabled", "disabled");
		$("#post_comment_send .btn-inner--icon.hide-on-comment-not-editing").html("<i class='fa fa-spinner fa-spin'></i>");
		$("#post_comment_send .btn-inner--text.hide-on-comment-not-editing").html(__("编辑中"));

		iziToast.show({
			title: __("正在编辑"),
			message: __("评论正在编辑中.."),
			class: 'shadow-sm iziToast-noprogressbar',
			position: 'topRight',
			backgroundColor: 'var(--themecolor)',
			titleColor: '#ffffff',
			messageColor: '#ffffff',
			iconColor: '#ffffff',
			progressBarColor: '#ffffff',
			icon: 'fa fa-spinner fa-spin',
			close: false,
			timeout: 999999999
		});

		$.ajax({
			type: 'POST',
			url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
			dataType: "json",
			data: {
				action: "user_edit_comment",
				comment: commentContent,
				id: editID
			},
			success: function (result) {
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_edit_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-not-editing").html("<i class='fa fa-pencil'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-not-editing").html(__("编辑"));

				//判断是否有错误
				if (result.status == "failed") {
					iziToast.destroy();
					iziToast.show({
						title: __("评论编辑失败"),
						message: result.msg,
						class: 'shadow-sm',
						position: 'topRight',
						backgroundColor: '#f5365c',
						titleColor: '#ffffff',
						messageColor: '#ffffff',
						iconColor: '#ffffff',
						progressBarColor: '#ffffff',
						icon: 'fa fa-close',
						timeout: 5000
					});
					return;
				}

				//发送成功，替换原评论
				result.new_comment = result.new_comment.replace(/<(\/).noscript>/g, "");
				$("#comment-" + editID + " .comment-item-text").html(result.new_comment);
				$("#comment-" + editID + " .comment-item-source").html(result.new_comment_source);
				if ($("#comment-" + editID + " .comment-info .comment-edited").length == 0) {
					$("#comment-" + editID + " .comment-info").prepend("<div class='comment-edited'><i class='fa fa-pencil' aria-hidden='true'></i>" + __("已编辑") + "</div>")
				}
				if (result.can_visit_edit_history) {
					$("#comment-" + editID + " .comment-info .comment-edited").addClass("comment-edithistory-accessible");
				}

				iziToast.destroy();
				iziToast.show({
					title: __("编辑成功"),
					message: __("您的评论已编辑"),
					class: 'shadow-sm',
					position: 'topRight',
					backgroundColor: '#2dce89',
					titleColor: '#ffffff',
					messageColor: '#ffffff',
					iconColor: '#ffffff',
					progressBarColor: '#ffffff',
					icon: 'fa fa-check',
					timeout: 5000
				});
				$("body,html").animate({
					scrollTop: $("#comment-" + editID).offset().top - 100
				}, 500, 'easeOutExpo');
				editing = false;
				editID = 0;
				$("#post_comment_content").val("");
				$('#post_comment').removeClass("editing post-comment-force-privatemode-on post-comment-force-privatemode-off");
				$("#post_comment_content").trigger("change");
			},
			error: function (result) {
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_edit_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-not-editing").html("<i class='fa fa-pencil'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-not-editing").html(__("编辑"));
				if (result.readyState != 4 || result.status == 0) {
					iziToast.destroy();
					iziToast.show({
						title: __("评论编辑失败"),
						message: __("未知原因"),
						class: 'shadow-sm',
						position: 'topRight',
						backgroundColor: '#f5365c',
						titleColor: '#ffffff',
						messageColor: '#ffffff',
						iconColor: '#ffffff',
						progressBarColor: '#ffffff',
						icon: 'fa fa-close',
						timeout: 5000
					});
					return;
				}
			}
		});
	}
	$(document).on("click", "#post_comment_send", function () {
		if ($("#post_comment").hasClass("editing")) {
			editComment();
		} else {
			// 首先进行基本的表单验证
			let commentContent = $("#post_comment_content").val();
			let commentName = $("#post_comment_name").val();
			let commentEmail = $("#post_comment_email").val();
			let commentLink = $("#post_comment_link").val();

			let isError = false;
			let errorMsg = "";

			// 检查表单合法性
			if (commentContent.match(/^\s*$/)) {
				isError = true;
				errorMsg += __("评论内容不能为空") + "</br>";
			}
			if (!$("#post_comment").hasClass("no-need-name-email")) {
				if (commentName.match(/^\s*$/)) {
					isError = true;
					errorMsg += __("昵称不能为空") + "</br>";
				}
				if ($("#post_comment").hasClass("enable-qq-avatar")) {
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)) {
						isError = true;
						errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
					}
				} else {
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)) {
						isError = true;
						errorMsg += __("邮箱格式错误") + "</br>";
					}
				}
			} else {
				if (commentEmail.length || (document.getElementById("comment_post_mailnotice") != null && document.getElementById("comment_post_mailnotice").checked == true)) {
					if ($("#post_comment").hasClass("enable-qq-avatar")) {
						if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)) {
							isError = true;
							errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
						}
					} else {
						if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)) {
							isError = true;
							errorMsg += __("邮箱格式错误") + "</br>";
						}
					}
				}
			}
			if (commentLink != "" && commentLink.indexOf('.') == -1) {
				isError = true;
				errorMsg += __("网站格式错误") + "</br>";
			}

			// 如果基本表单验证失败，显示错误信息并返回
			if (isError) {
				// 确保按钮和表单元素处于可用状态
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_name").removeAttr("disabled");
				$("#post_comment_email").removeAttr("disabled");
				$("#post_comment_link").removeAttr("disabled");
				$("#post_comment_captcha").removeAttr("disabled");
				$("#post_comment_reply_cancel").removeAttr("disabled");
				$("#post_comment").removeClass("sending");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-send'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送"));

				iziToast.show({
					title: __("评论格式错误"),
					message: errorMsg,
					class: 'shadow-sm',
					position: 'topRight',
					backgroundColor: '#f5365c',
					titleColor: '#ffffff',
					messageColor: '#ffffff',
					iconColor: '#ffffff',
					progressBarColor: '#ffffff',
					icon: 'fa fa-close',
					timeout: 5000
				});
				return;
			}

			// 基本验证通过后，检查验证码
			if ($("#geetest-captcha").length > 0) {
				// 检查Geetest 库是否加载失败
				if (window.geetestLoadFailed) {
					if (typeof loadGeetestScript === 'function' && typeof initGeetestCaptchaCore === 'function') {
						try {
							loadGeetestScript(function () {
								initGeetestCaptchaCore();
								setTimeout(function () {
									if (window.geetestCaptcha) { try { window.geetestCaptcha.showBox(); } catch (e) { } }
								}, 0);
							});
						} catch (e) { }
					}
					return;
				}

				// 如果正在自动提交中，防止重复点击
				if (window.geetestAutoSubmitting) {
					return;
				}

				// 如果使用Geetest，检查验证码是否已完成
				if (!window.geetestVerified) {
					// 验证码未完成，触发验证码显示
					if (window.geetestCaptcha) {
						// 重置验证码状态
						window.geetestCaptcha.reset();
						// 然后显示验证码
						window.geetestCaptcha.showBox();
					} else {
						if (typeof loadGeetestScript === 'function' && typeof initGeetestCaptchaCore === 'function') {
							try {
								loadGeetestScript(function () {
									initGeetestCaptchaCore();
									setTimeout(function () {
										if (window.geetestCaptcha) { try { window.geetestCaptcha.showBox(); } catch (e) { } }
									}, 0);
								});
							} catch (e) { }
						}
					}
					return;
				}
			}
			// 验证码已完成或不需要验证码，直接提交评论
			postComment();
		}
	});
}();
/*评论点赞*/
$(document).on('click', '.comment-upvote', function () {
	let $this = $(this);
	let ID = $this.attr('data-id');

	// 防止重复点击
	if ($this.hasClass('comment-upvoting')) {
		return;
	}

	$this.addClass('comment-upvoting');
	$.ajax({
		url: argonConfig.wp_path + 'wp-admin/admin-ajax.php',
		type: 'POST',
		dataType: 'json',
		data: {
			action: 'upvote_comment',
			comment_id: ID,
		},
		success: function (result) {
			$this.removeClass('comment-upvoting');
			if (result.status === 'success') {
				$('.comment-upvote-num', $this).html(result.total_upvote);
				if (result.upvoted) {
					$this.addClass('upvoted');
				} else {
					$this.removeClass('upvoted');
				}
			} else {
				$('.comment-upvote-num', $this).html(result.total_upvote);
				iziToast.show({
					title: result.msg,
					class: 'shadow-sm',
					position: 'topRight',
					backgroundColor: '#f5365c',
					titleColor: '#ffffff',
					messageColor: '#ffffff',
					iconColor: '#ffffff',
					progressBarColor: '#ffffff',
					icon: 'fa fa-close',
					timeout: 5000
				});
			}
		},
		error: function (xhr) {
			$this.removeClass('comment-upvoting');
			iziToast.show({
				title: __('点赞失败'),
				class: 'shadow-sm',
				position: 'topRight',
				backgroundColor: '#f5365c',
				titleColor: '#ffffff',
				messageColor: '#ffffff',
				iconColor: '#ffffff',
				progressBarColor: '#ffffff',
				icon: 'fa fa-close',
				timeout: 5000
			});
		}
	});
});
/*评论表情面板*/
function lazyloadStickers() {
	// 原生懒加载无需额外处理
}

function positionEmotionKeyboard() {
	var btn = document.getElementById("comment_emotion_btn");
	var keyboard = document.getElementById("emotion_keyboard");
	if (!btn || !keyboard) return;
	
	// Ensure keyboard is a child of body to avoid stacking context & clipping issues
	if (keyboard.parentElement !== document.body) {
		document.body.appendChild(keyboard);
	}
	
	var btnRect = btn.getBoundingClientRect();
	var keyboardHeight = 300;
	var keyboardWidth = Math.min(500, window.innerWidth - 40);
	
	var top = btnRect.top + window.scrollY - keyboardHeight - 10;
	var left = btnRect.right + window.scrollX - keyboardWidth;
	
	if (top < window.scrollY + 10) {
		top = btnRect.bottom + window.scrollY + 10;
	}
	if (left < window.scrollX + 10) {
		left = window.scrollX + 10;
	}
	if (left + keyboardWidth > window.scrollX + window.innerWidth - 10) {
		left = window.scrollX + window.innerWidth - keyboardWidth - 10;
	}
	
	keyboard.style.top = top + "px";
	keyboard.style.left = left + "px";
}

$(document).on("click", "#comment_emotion_btn", function (e) {
	e.stopPropagation();
	var isOpen = $("#comment_emotion_btn").hasClass("comment-emotion-keyboard-open");
	if (!isOpen) {
		$("#comment_emotion_btn").addClass("comment-emotion-keyboard-open");
		$("#emotion_keyboard").addClass("comment-emotion-keyboard-open");
		setTimeout(function() {
			positionEmotionKeyboard();
		}, 10);
	} else {
		$("#comment_emotion_btn").removeClass("comment-emotion-keyboard-open");
		$("#emotion_keyboard").removeClass("comment-emotion-keyboard-open");
	}
});

$(document).on("click", ".emotion-keyboard .emotion-group-name", function () {
	$(".emotion-keyboard .emotion-group-name.active").removeClass("active");
	$(this).addClass("active");
	$(".emotion-keyboard .emotion-group:not(d-none)").addClass("d-none");
	$(".emotion-keyboard .emotion-group[index='" + $(this).attr("index") + "']").removeClass("d-none");
});

function inputInsertText(text, input) {
	$(input).focus();
	let isSuccess = document.execCommand("insertText", false, text);
	if (!isSuccess) { //FF
		if (typeof input.setRangeText === "function") {
			const start = input.selectionStart;
			input.setRangeText(text);
			input.selectionStart = input.selectionEnd = start + input.length;
			const e = document.createEvent("UIEvent");
			e.initEvent("input", true, false);
			input.dispatchEvent(e);
		} else {
			let value = $(input).val();
			let startPos = input.selectionStart, endPos = input.selectionEnd;
			$(input).val(value.substring(0, startPos) + text + value.substring(endPos));
			input.selectionStart = startPos + text.length;
			input.selectionEnd = startPos + text.length;
		}
	}
	$(input).focus();
}

$(document).on("click", ".emotion-keyboard .emotion-item", function () {
	$("#comment_emotion_btn").removeClass("comment-emotion-keyboard-open");
	$("#emotion_keyboard").removeClass("comment-emotion-keyboard-open");
	if ($(this).hasClass("emotion-item-sticker")) {
		inputInsertText(" :" + $(this).attr("code") + ": ", document.getElementById("post_comment_content"));
	} else {
		inputInsertText($(this).attr("text"), document.getElementById("post_comment_content"));
	}
});

$(document).on("dragstart", ".emotion-keyboard .emotion-item > img, .comment-sticker", function (e) {
	e.preventDefault();
});

document.addEventListener('click', (e) => {
	if (document.getElementById("comment_emotion_btn") == null || document.getElementById("emotion_keyboard") == null) {
		return;
	}
	if (e.target.id != "comment_emotion_btn" && e.target.id != "emotion_keyboard" && !document.getElementById("comment_emotion_btn").contains(e.target) && !document.getElementById("emotion_keyboard").contains(e.target)) {
		$("#comment_emotion_btn").removeClass("comment-emotion-keyboard-open");
		$("#emotion_keyboard").removeClass("comment-emotion-keyboard-open");
	}
});

window.addEventListener('resize', function() {
	if ($("#emotion_keyboard").hasClass("comment-emotion-keyboard-open")) {
		positionEmotionKeyboard();
	}
});
/*查看评论编辑记录*/
function showCommentEditHistory(id) {
	let requestID = parseInt(new Date().getTime());
	$("#comment_edit_history").data("request-id", requestID);
	$("#comment_edit_history .modal-title").html(__("评论 #") + id + " " + __("的编辑记录"));
	$("#comment_edit_history .modal-body").html("<div class='comment-history-loading'><span class='spinner-border text-primary'></span><span style='display: inline-block;transform: translateY(-4px);margin-left: 15px;font-size: 18px;'>加载中/span></div>");
	$("#comment_edit_history").modal(null);
	$.ajax({
		type: 'POST',
		url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
		dataType: "json",
		data: {
			action: "get_comment_edit_history",
			id: id
		},
		success: function (result) {
			if ($("#comment_edit_history").data("request-id") != requestID) {
				return;
			}
			$("#comment_edit_history .modal-body").hide();
			$("#comment_edit_history .modal-body").html(result.history);
			$("#comment_edit_history .modal-body").fadeIn(300);
		},
		error: function (result) {
			if ($("#comment_edit_history").data("request-id") != requestID) {
				return;
			}
			$("#comment_edit_history .modal-body").hide();
			$("#comment_edit_history .modal-body").html(__("加载失败"));
			$("#comment_edit_history .modal-body").fadeIn(300);
		}
	});
}
$(document).on("click", ".comment-edited.comment-edithistory-accessible", function () {
	showCommentEditHistory($(this).parent().parent().parent().parent().data("id"));
});
/*过长评论折叠*/
function foldLongComments() {
	if (argonConfig.fold_long_comments == false) {
		return;
	}
	$(".comment-item-inner").each(function () {
		if ($(this).hasClass("comment-unfolded")) {
			return;
		}
		if (this.clientHeight > 800) {
			$(this).addClass("comment-folded");
			$(this).append("<div class='show-full-comment'><button><i class='fa fa-angle-down' aria-hidden='true'></i> " + __("展开") + "</button></div>");
		}
	});
}
foldLongComments();
$(document).on("click", ".show-full-comment", function () {
	$(this).parent().removeClass("comment-folded").addClass("comment-unfolded");
});
/*评论文字头像*/
function generateCommentTextAvatar(img) {
	let emailHash = '';
	try {
		emailHash = img.attr("src").match(/([a-f\d]{32}|[A-F\d]{32})/)[0];
	} catch {
		emailHash = img.parent().parent().parent().find(".comment-name").text().trim();
		if (emailHash == '' || emailHash == undefined) {
			emailHash = img.parent().find("*[class*='comment-author']").text().trim();
		}
	}
	let hash = 0;
	for (i in emailHash) {
		hash = (hash * 233 + emailHash.charCodeAt(i)) % 16;
	}
	let colors = ['#e25f50', '#f25e90', '#bc67cb', '#9672cf', '#7984ce', '#5c96fa', '#7bdeeb', '#45d0e2', '#48b7ad', '#52bc89', '#9ace5f', '#d4e34a', '#f9d715', '#fac400', '#ffaa00', '#ff8b61', '#c2c2c2', '#8ea3af', '#a1877d', '#a3a3a3', '#b0b6e3', '#b49cde', '#c2c2c2', '#7bdeeb', '#bcaaa4', '#aed77f'];
	let text = $(".comment-name", img.parent().parent().parent()).text().trim()[0];
	if (text == '' || text == undefined) {
		text = img.parent().find("*[class*='comment-author']").text().trim()[0];
	}
	let classList = img.attr('class') + " text-avatar";
	img.prop('outerHTML', '<div class="' + classList + '" style="background-color: ' + colors[hash] + ';">' + text + '</div>');
}
document.addEventListener("error", function (e) {
	let img = $(e.target);
	if (!img.hasClass("avatar")) {
		return;
	}
	generateCommentTextAvatar(img);
}, true);
function refreshCommentTextAvatar() {
	$(".comment-item-avatar > img.avatar").each(function (index, img) {
		if (!img.complete) {
			return;
		}
		if (img.naturalWidth !== 0) {
			return false;
		}
		generateCommentTextAvatar($(img));
	});
}
refreshCommentTextAvatar();
$(window).on("load", function () {
	refreshCommentTextAvatar();
});
/*需要密码的文章加载*/
$(document).on("submit", ".post-password-form", function () {
	$("input[type='submit']", this).attr("disabled", "disabled");
	let url = $(this).attr("action");
	$.pjax.form(this, {
		push: false,
		replace: false
	});
	return false;
});
/*评论分页加载*/
!function () {
	$(document).on("click", "#comments_navigation .page-item > div", function () {
		$("#comments").addClass("comments-loading");
		NProgress.set(0.618);
		url = $(this).attr("href");
		$.ajax({
			type: 'POST',
			url: url,
			dataType: "html",
			success: function (result) {
				NProgress.done();
				$vdom = $(result);
				$("#comments").html($("#comments", $vdom).html());
				$("#comments").removeClass("comments-loading");
				$("body,html").animate({
					scrollTop: $("#comments").offset().top - 100
				}, 500, 'easeOutExpo');
				foldLongComments();
				calcHumanTimesOnPage();
				panguInit();
			},
			error: function () {
				window.location.href = url;
			}
		});
	});
	$(document).on("click", "#comments_more", function () {
		$("#comments_more").attr("disabled", "disabled");
		NProgress.set(0.618);
		url = $(this).attr("href");
		$.ajax({
			type: 'POST',
			url: url,
			data: {
				no_post_view: 'true'
			},
			dataType: "html",
			success: function (result) {
				NProgress.done();
				$vdom = $(result);
				$("#comments > .card-body > ol.comment-list").append($("#comments > .card-body > ol.comment-list", $vdom).html());
				if ($("#comments_more", $vdom).length == 0) {
					$("#comments_more").remove();
					$(".comments-navigation-more").html("<div class='comments-navigation-nomore'>" + __("没有更多了") + "</div>");
				} else {
					$("#comments_more").attr("href", $("#comments_more", $vdom).attr("href"));
					$("#comments_more").removeAttr("disabled");
				}
				foldLongComments();
				calcHumanTimesOnPage();
				panguInit();
			},
			error: function () {
				window.location.href = url;
			}
		});
	});
}();

/*URL 和# 根据 ID 定位*/
function getHashTarget(hash) {
	if (!hash || hash === "#") {
		return null;
	}
	var target = null;
	var decodedId = "";
	try {
		decodedId = decodeURIComponent(hash.slice(1));
	} catch (err) {
		decodedId = hash.slice(1);
	}
	if (decodedId) {
		target = document.getElementById(decodedId);
	}
	if (!target) {
		try {
			target = document.querySelector(hash);
		} catch (err) {
			return null;
		}
	}
	return target;
}

function gotoHash(hash, durtion, easing = 'easeOutExpo') {
	var target = getHashTarget(hash);
	if (!target) {
		return;
	}
	if (durtion == null) {
		durtion = 200;
	}
	$("body,html").stop().animate({
		scrollTop: $(target).offset().top - 80
	}, durtion, easing);
}
function getHash(url) {
	return url.substring(url.indexOf('#'));
}
!function () {
	$(window).on("hashchange", function () {
		gotoHash(window.location.hash);
	});
	$(window).trigger("hashchange");
}();

/*显示文章过时信息 Toast*/
function showPostOutdateToast() {
	if ($("#primary #post_outdate_toast").length > 0) {
		iziToast.show({
			title: '',
			message: $("#primary #post_outdate_toast").data("text"),
			class: 'shadow-sm',
			position: 'topRight',
			backgroundColor: 'var(--themecolor)',
			titleColor: '#ffffff',
			messageColor: '#ffffff',
			iconColor: '#ffffff',
			progressBarColor: '#ffffff',
			icon: 'fa fa-info',
			close: false,
			timeout: 8000
		});
		$("#primary #post_outdate_toast").remove();
	}
}
showPostOutdateToast();

/*Zoomify*/
// 全局 Zoomify 实例数组，用于清理
var zoomifyInstances = [];

function zoomifyInit(scope) {
	var scopedRoot = scope || null;
	// 清理旧的 Zoomify 实例
	if (!scopedRoot && zoomifyInstances.length > 0) {
		zoomifyInstances.forEach(function (instance) {
			try {
				if (instance && typeof instance.destroy === 'function') {
					instance.destroy();
				}
			} catch (e) { }
		});
		zoomifyInstances = [];
	}

	if (argonConfig.zoomify == false) {
		return;
	}
	if (typeof $.fn.zoomify === 'function') {
		var $images = scopedRoot ? $(scopedRoot).find("img:not(.zoomify-initialized)") : $("article img:not(.zoomify-initialized)");
		$images.each(function () {
			let $img = $(this);
			$img.addClass('zoomify-initialized');
			try {
				let instance = $img.zoomify(argonConfig.zoomify).data('zoomify');
				if (instance) {
					zoomifyInstances.push(instance);
				}
			} catch (e) { }
		});
	}
}
zoomifyInit();

/*Fancybox*/
$.fancybox.defaults.transitionEffect = "slide";
$.fancybox.defaults.buttons = ["zoom", "fullScreen", "thumbs", "close"];
$.fancybox.defaults.lang = argonConfig.language;
$.fancybox.defaults.i18n = {
	en_US: {
		CLOSE: "Close",
		NEXT: "Next",
		PREV: "Previous",
		ERROR: "The requested content cannot be loaded. <br/> Please try again later.",
		PLAY_START: "Start slideshow",
		PLAY_STOP: "Pause slideshow",
		FULL_SCREEN: "Full screen",
		THUMBS: "Thumbnails",
		DOWNLOAD: "Download",
		SHARE: "Share",
		ZOOM: "Zoom"
	},
	zh_CN: {
		CLOSE: "关闭",
		NEXT: "下一个",
		PREV: "上一个",
		ERROR: "图片加载失败",
		PLAY_START: "开始幻灯片展示",
		PLAY_STOP: "暂停幻灯片展示",
		FULL_SCREEN: "全屏",
		THUMBS: "缩略图",
		DOWNLOAD: "下载",
		SHARE: "分享",
		ZOOM: "缩放"
	}
};

/*Lazyload - 使用 IntersectionObserver 实现懒加载
/*
 * 优化说明：
 * 1. 使用全局 lazyloadObserver 变量，便于在 PJAX 切换时清理
 * 2. 添加 Observer 存在性检查，防止重复初始化
 * 3. 使用 requestAnimationFrame 替代 setTimeout 实现加载效果
 * 4. 实现滚动监听降级方案，支持不兼容 IntersectionObserver 的浏览器
 * 5. 使用 transitionend 事件清理样式，避免固定延迟
 */
// 全局 Observer 实例，用于清理
var lazyloadObserver = null;
// 全局滚动监听 handler，用于降级方案清理
var lazyloadScrollHandler = null;

function lazyloadInit(scope) {
	var scopedRoot = scope || null;
	// 清理旧的 Observer（防止重复初始化）
	if (!scopedRoot && lazyloadObserver) {
		try {
			lazyloadObserver.disconnect();
			lazyloadObserver = null;
			ArgonDebug.log('清理旧的 Lazyload Observer');
		} catch (e) {
			ArgonDebug.warn('清理 Lazyload Observer 失败:', e);
			lazyloadObserver = null;
		}
	}

	// 清理旧的滚动监听器（降级方案）
	if (!scopedRoot && lazyloadScrollHandler) {
		try {
			window.removeEventListener('scroll', lazyloadScrollHandler);
			window.removeEventListener('resize', lazyloadScrollHandler);
			lazyloadScrollHandler = null;
			ArgonDebug.log('清理旧的滚动监听器');
		} catch (e) {
			ArgonDebug.warn('清理滚动监听器失败:', e);
			lazyloadScrollHandler = null;
		}
	}

	// 检查是否启用懒加载
	if (argonConfig.lazyload === false || argonConfig.lazyload === 'false') {
		// 未启用懒加载时，直接加载所有图片
		loadAllImagesImmediately(scopedRoot);
		return;
	}

	let images = scopedRoot && scopedRoot.querySelectorAll ? scopedRoot.querySelectorAll('img.lazyload[data-src]') : document.querySelectorAll('img.lazyload[data-src]');
	if (images.length === 0) {
		ArgonDebug.log('没有需要懒加载的图片');
		return;
	}

	let effect = argonConfig.lazyload_effect || 'fadeIn';
	let threshold = parseInt(argonConfig.lazyload_threshold) || 800;

	ArgonDebug.log(`初始化懒加载: ${images.length} 张图片, 效果: ${effect}, 阈值: ${threshold}px`);

	// 使用 IntersectionObserver 实现懒加载
	if ('IntersectionObserver' in window) {
		try {
			let activeLazyloadObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						let img = entry.target;
						loadImageOptimized(img, effect, activeLazyloadObserver);
						activeLazyloadObserver.unobserve(img);
					}
				});
			}, {
				rootMargin: threshold + 'px 0px'
			});
			if (scopedRoot) {
				scopedRoot._argonLazyloadObserver = activeLazyloadObserver;
			} else {
				lazyloadObserver = activeLazyloadObserver;
			}

			images.forEach(function (img) {
				// 重置图片状态
				img.style.opacity = '';
				img.style.transform = '';
				img.style.transition = '';
				activeLazyloadObserver.observe(img);
			});

			ArgonDebug.log('使用 IntersectionObserver 实现懒加载');
		} catch (e) {
			ArgonDebug.error('IntersectionObserver 初始化失败，使用降级方案:', e);
			if (scopedRoot) {
				loadAllImagesImmediately(scopedRoot);
				return;
			}
			lazyloadFallback(images, effect, threshold);
		}
	} else {
		// 降级方案：使用滚动监听
		ArgonDebug.log('浏览器不支持 IntersectionObserver，使用滚动监听降级方案');
		if (scopedRoot) {
			loadAllImagesImmediately(scopedRoot);
			return;
		}
		lazyloadFallback(images, effect, threshold);
	}
}

/**
 * 优化的图片加载函数
 * 使用 requestAnimationFrame 替代 setTimeout
 * @param {HTMLImageElement} img - 图片元素
 * @param {string} effect - 加载效果类型 ('fadeIn' 或'slideDown')
 */
function loadImageOptimized(img, effect, observer) {
	let src = img.getAttribute('data-src');
	if (!src) {
		// 如果没有 data-src，取消 Observer 监听
		let observerToUse = observer || lazyloadObserver;
		if (observerToUse) {
			observerToUse.unobserve(img);
		}
		return;
	}

	// 预加载图片
	let tempImg = new Image();
	tempImg.onload = function () {
		// 使用 requestAnimationFrame 优化 DOM 操作
		requestAnimationFrame(function () {
			img.src = src;
			img.removeAttribute('data-src');
			let srcset = img.getAttribute('data-srcset');
			if (srcset) {
				img.setAttribute('srcset', srcset);
				img.removeAttribute('data-srcset');
			}
			let sizes = img.getAttribute('data-sizes');
			if (sizes) {
				img.setAttribute('sizes', sizes);
				img.removeAttribute('data-sizes');
			}
			img.classList.remove('lazyload');

			// 移除所有lazyload-style-* 类
			img.className = img.className.replace(/\blazyload-style-\d+\b/g, '').trim();

			// 应用加载效果
			applyLoadEffectOptimized(img, effect);
		});
	};
	tempImg.onerror = function () {
		// 加载失败时使用降级方案
		requestAnimationFrame(function () {
			img.src = src;
			img.removeAttribute('data-src');
			let srcset = img.getAttribute('data-srcset');
			if (srcset) {
				img.setAttribute('srcset', srcset);
				img.removeAttribute('data-srcset');
			}
			let sizes = img.getAttribute('data-sizes');
			if (sizes) {
				img.setAttribute('sizes', sizes);
				img.removeAttribute('data-sizes');
			}
			img.classList.remove('lazyload');
			img.className = img.className.replace(/\blazyload-style-\d+\b/g, '').trim();

			// 取消 Observer 监听
			let observerToUse = observer || lazyloadObserver;
			if (observerToUse) {
				observerToUse.unobserve(img);
			}
		});
	};
	tempImg.src = src;
}

/**
 * 使用 requestAnimationFrame 应用加载效果
 * @param {HTMLImageElement} img - 图片元素
 * @param {string} effect - 加载效果类型 ('fadeIn' 或'slideDown')
 */
function applyLoadEffectOptimized(img, effect) {
	if (effect === 'fadeIn') {
		img.style.opacity = '0';
		img.style.transition = 'opacity 0.3s ease';
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				img.style.opacity = '1';
			});
		});
		// 使用 transitionend 事件清理样式，更可靠
		img.addEventListener('transitionend', function cleanup() {
			img.style.transition = '';
			img.removeEventListener('transitionend', cleanup);
		}, { once: true });
	} else if (effect === 'slideDown') {
		img.style.opacity = '0';
		img.style.transform = 'translateY(-20px)';
		img.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				img.style.opacity = '1';
				img.style.transform = 'translateY(0)';
			});
		});
		// 使用 transitionend 事件清理样式
		img.addEventListener('transitionend', function cleanup() {
			img.style.transition = '';
			img.style.transform = '';
			img.removeEventListener('transitionend', cleanup);
		}, { once: true });
	}
}

/**
 * 降级方案：使用滚动监听实现懒加载
 * 当浏览器不支持IntersectionObserver 时使用
 * @param {NodeList|Array} images - 需要懒加载的图片元素列表
 * @param {string} effect - 加载效果类型
 * @param {number} threshold - 提前加载的阈值（像素）
 */
function lazyloadFallback(images, effect, threshold) {
	let loadedImages = new Set();

	function checkImagesInView() {
		let viewportHeight = window.innerHeight;
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;

		images.forEach(function (img) {
			if (loadedImages.has(img)) return;

			let rect = img.getBoundingClientRect();
			if (rect.top < viewportHeight + threshold && rect.bottom > -threshold) {
				loadImageOptimized(img, effect);
				loadedImages.add(img);
			}
		});

		// 如果所有图片都已加载，清理监听器
		if (loadedImages.size === images.length) {
			if (lazyloadScrollHandler) {
				window.removeEventListener('scroll', lazyloadScrollHandler);
				window.removeEventListener('resize', lazyloadScrollHandler);
				lazyloadScrollHandler = null;
				ArgonDebug.log('所有图片已加载，清理滚动监听器');
			}
		}
	}

	// 使用 eventManager 的节流函数优化性能
	lazyloadScrollHandler = argonEventManager ?
		argonEventManager.throttle(checkImagesInView, 100) :
		checkImagesInView;

	// 绑定事件监听器
	window.addEventListener('scroll', lazyloadScrollHandler, { passive: true });
	window.addEventListener('resize', lazyloadScrollHandler, { passive: true });

	// 立即检查一次
	checkImagesInView();
}

/**
 * 立即加载所有图片（懒加载禁用时）
 * 不应用任何加载效果，直接替换 src
 */
function loadAllImagesImmediately(scope) {
	let images = scope && scope.querySelectorAll ? scope.querySelectorAll('img.lazyload[data-src]') : document.querySelectorAll('img.lazyload[data-src]');
	images.forEach(function (img) {
		let src = img.getAttribute('data-src');
		if (src) {
			img.src = src;
			img.removeAttribute('data-src');
			let srcset = img.getAttribute('data-srcset');
			if (srcset) {
				img.setAttribute('srcset', srcset);
				img.removeAttribute('data-srcset');
			}
			let sizes = img.getAttribute('data-sizes');
			if (sizes) {
				img.setAttribute('sizes', sizes);
				img.removeAttribute('data-sizes');
			}
			img.classList.remove('lazyload');
			img.className = img.className.replace(/\blazyload-style-\d+\b/g, '').trim();
		}
	});
}

// 确保 DOM 加载完成后再初始化懒加载
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', lazyloadInit);
} else {
	// DOM 已经加载完成，立即初始化
	lazyloadInit();
}

/*Pangu.js*/
var highlightObserver = null;
var argonVirtualHeadIndexPatched = false;

function panguInit() {
	var virtualizer = window.ArgonLongContentVirtualizer;
	if (argonConfig.pangu.indexOf("article") >= 0) {
		if (virtualizer && virtualizer.isReady && virtualizer.isReady()) {
			var mountedSections = document.querySelectorAll('#post_content .argon-virtual-section-mounted');
			for (var i = 0; i < mountedSections.length; i++) {
				pangu.spacingNode(mountedSections[i]);
			}
		} else {
			pangu.spacingElementByClassName('post-content');
		}
	}
	if (argonConfig.pangu.indexOf("comment") >= 0) {
		pangu.spacingElementById('comments');
	}
	if (argonConfig.pangu.indexOf("shuoshuo") >= 0) {
		pangu.spacingElementByClassName('shuoshuo-content');
	}
}
initArgonLongContentVirtualizer();
patchHeadIndexForVirtualContent();
panguInit();

/*Clamp.js*/
function clampInit() {
	$(".clamp").each(function (index, dom) {
		$clamp(dom, { clamp: dom.getAttribute("clamp-line") });
	});
}
clampInit();

/*Tippy.js*/
function tippyInit(scope) {
	//Reference Popover
	var target = scope && scope.querySelectorAll ? scope.querySelectorAll('sup.reference[data-content]:not(.tippy-initialized)') : 'sup.reference[data-content]:not(.tippy-initialized)';
	tippy(target, {
		content: (reference) => reference.getAttribute('data-content'),
		allowHTML: true,
		interactive: true, theme: 'light scroll-y',
		delay: [100, 250],
		animation: 'fade'
	});
	var $target = scope ? $(scope).find("sup.reference[data-content]:not(.tippy-initialized)") : $("sup.reference[data-content]:not(.tippy-initialized)");
	$target.addClass("tippy-initialized");
}
tippyInit();

/*Banner 全屏封面相关*/
if ($("html").hasClass("banner-as-cover")) {
	function classInit() {
		if ($("#main").hasClass("article-list-home")) {
			if (!$("html").hasClass("is-home")) {
				$("html").addClass("is-home");
				$("html").trigger("resize");
			}
		} else {
			if ($("html").hasClass("is-home")) {
				$("html").removeClass("is-home");
				$("html").trigger("resize");
			}
		}
	}
	classInit();
	new MutationObserver(function (mutations, observer) {
		classInit();
	}).observe(document.querySelector("#primary"), {
		'childList': true
	});
	$(".cover-scroll-down").on("click", function () {
		gotoHash("#content", 600, 'easeOutCirc');
		$("#content").focus();
	});
	$fabs = $("#float_action_buttons");
	$coverScrollDownBtn = $(".cover-scroll-down");
	function changeWidgetsDisplayStatus() {
		let scrollTop = $(window).scrollTop();
		if (scrollTop >= window.outerHeight * 0.2) {
			$fabs.removeClass("hidden");
		} else {
			$fabs.addClass("hidden");
		}
		if (scrollTop >= window.outerHeight * 0.6) {
			$coverScrollDownBtn.addClass("hidden");
		} else {
			$coverScrollDownBtn.removeClass("hidden");
		}
	}
	changeWidgetsDisplayStatus();
	$(window).scroll(function () {
		changeWidgetsDisplayStatus();
	});
	$(window).resize(function () {
		changeWidgetsDisplayStatus();
	});
}

/*Pjax*/
var pjaxScrollTop = 0, pjaxLoading = false;

// ==========================================================================
// 统一的资源清理管理器
// ==========================================================================
// 在 PJAX 页面切换时清理所有旧页面资源，避免内存泄漏和功能失效
// Validates: Requirements 1.1, 1.2, 1.3, 1.4, 2.3, 6.1
// ==========================================================================

/**
 * 清理 Lazyload Observer 和滚动监听器
 * @returns {void}
 */
function cleanupLazyloadObserver() {
	// 清理 IntersectionObserver
	if (lazyloadObserver) {
		try {
			lazyloadObserver.disconnect();
			lazyloadObserver = null;
			ArgonDebug.log('Lazyload Observer 已清理');
		} catch (e) {
			ArgonDebug.warn('清理 Lazyload Observer 失败:', e);
			lazyloadObserver = null;
		}
	}

	// 清理滚动监听器（降级方案）
	if (lazyloadScrollHandler) {
		try {
			window.removeEventListener('scroll', lazyloadScrollHandler);
			window.removeEventListener('resize', lazyloadScrollHandler);
			lazyloadScrollHandler = null;
			ArgonDebug.log('Lazyload 滚动监听器已清理');
		} catch (e) {
			ArgonDebug.warn('清理 Lazyload 滚动监听器失败:', e);
			lazyloadScrollHandler = null;
		}
	}
}

/**
 * 清理 Zoomify 实例
 * @returns {void}
 */
function cleanupZoomifyInstances() {
	if (zoomifyInstances && zoomifyInstances.length > 0) {
		let cleanedCount = 0;
		zoomifyInstances.forEach(function (instance) {
			try {
				if (instance && typeof instance.destroy === 'function') {
					instance.destroy();
					cleanedCount++;
				}
			} catch (e) {
				ArgonDebug.warn('销毁 Zoomify 实例失败:', e);
			}
		});
		zoomifyInstances = [];
		ArgonDebug.log(`已清理 ${cleanedCount} 个 Zoomify 实例`);
	}
	// 移除初始化标记
	$('img.zoomify-initialized').removeClass('zoomify-initialized');
}

/**
 * 清理 Tippy 实例
 * @returns {void}
 */
function cleanupTippyInstances() {
	if (typeof tippy !== 'undefined') {
		let cleanedCount = 0;
		document.querySelectorAll('[data-tippy-root]').forEach(function (el) {
			try {
				if (el._tippy && typeof el._tippy.destroy === 'function') {
					el._tippy.destroy();
					cleanedCount++;
				}
			} catch (e) {
				ArgonDebug.warn('销毁 Tippy 实例失败:', e);
			}
		});
		$('.tippy-initialized').removeClass('tippy-initialized');
		if (cleanedCount > 0) {
			ArgonDebug.log(`已清理 ${cleanedCount} 个 Tippy 实例`);
		}
	}
}


/**
 * 清理动态添加的 style 标签
 * @returns {void}
 */
function cleanupDynamicStyles() {
	try {
		// 只清理标记为动态的样式
		const dynamicStyles = document.querySelectorAll('style[data-dynamic="true"]');
		if (dynamicStyles.length > 0) {
			dynamicStyles.forEach(function (style) {
				style.remove();
			});
			ArgonDebug.log(`已清理 ${dynamicStyles.length} 个动态样式`);
		}
	} catch (e) {
		ArgonDebug.warn('清理动态样式失败:', e);
	}
}

/**
 * 清理动态添加的 script 标签
 * @returns {void}
 */
function cleanupDynamicScripts() {
	try {
		// 只清理标记为动态的脚本
		const dynamicScripts = document.querySelectorAll('script[data-dynamic="true"]');
		if (dynamicScripts.length > 0) {
			dynamicScripts.forEach(function (script) {
				script.remove();
			});
			ArgonDebug.log(`已清理 ${dynamicScripts.length} 个动态脚本`);
		}
	} catch (e) {
		ArgonDebug.warn('清理动态脚本失败:', e);
	}
}

/**
 * 清理事件监听器
 * @returns {void}
 */
function cleanupEventListeners() {
	try {
		// 注意：大部分事件使用事件委托，不需要手动清理
		// 这里只清理特定的非委托事件

		// Lazyload 滚动监听器已在 cleanupLazyloadObserver() 中清理

		ArgonDebug.log('事件监听器已清理');
	} catch (e) {
		ArgonDebug.warn('清理事件监听器失败:', e);
	}
}

/**
 * 清理 PJAX 页面切换前的所有资源
 * 统一的资源清理管理器，在 pjax:beforeReplace 事件中调用
 * 
 * 清理内容包括：
 * - Lazyload Observer
 * - Zoomify 实例
 * - Tippy 实例
 * - 动态 style 标签
 * - 动态 script 标签
 * - 事件监听器
 * 
 * @returns {void}
 */
function cleanupPjaxResources() {
	ArgonDebug.log('开始清理 PJAX 资源...');

	// 按顺序清理各类资源
	cleanupLazyloadObserver();
	disconnectHighlightObserver();
	if (window.ArgonLongContentVirtualizer && typeof window.ArgonLongContentVirtualizer.destroy === "function") {
		window.ArgonLongContentVirtualizer.destroy(false);
		window.ArgonLongContentVirtualizer = null;
	}
	cleanupZoomifyInstances();
	cleanupTippyInstances(); cleanupDynamicStyles();
	cleanupDynamicScripts();
	cleanupEventListeners();

	ArgonDebug.log('PJAX 资源清理完成');
}

/**
 * 重置 GT4 验证码
 * 清理状态变量、隐藏字段和容器，然后重新初始化
 * 在 pjax:end 事件中调用
 */
function resetGT4Captcha() {
	try {
		if ($('#geetest-captcha').length > 0) {
			// 重置前端状态，避免重复提交阻塞
			window.geetestVerified = false;
			window.geetestAutoSubmitting = false;

			// 清空隐藏字段，防止残留导致pass_token 复用
			$('#geetest_lot_number').val('');
			$('#geetest_captcha_output').val('');
			$('#geetest_pass_token').val('');
			$('#geetest_gen_time').val('');

			// 清空容器，防止重复appendTo 导致多个实例
			$('#geetest-captcha').empty();

			// 若页面脚本已提供初始化方法，则调用以加载验证码
			if (typeof initGeetestCaptcha === 'function') {
				initGeetestCaptcha();
			} else if (typeof loadGeetestScript === 'function' && typeof initGeetestCaptchaCore === 'function') {
				loadGeetestScript(function () {
					initGeetestCaptchaCore();
				});
			}
		}
	} catch (e) {
		ArgonDebug.warn('Geetest init on PJAX failed:', e);
	}
}

function handleHashNavigation() {
	var hash = location.hash;
	if (!hash || hash === "#") {
		return;
	}
	if (window.ArgonLongContentVirtualizer && typeof window.ArgonLongContentVirtualizer.renderTarget === "function") {
		window.ArgonLongContentVirtualizer.renderTarget(hash.slice(1));
	}
	let target = getHashTarget(hash);
	if (!target) {
		return;
	}
	try { highlightJsRender(); } catch (err) { ArgonDebug.error('highlightJsRender failed:', err); }
	try { lazyloadInit(); } catch (err) { ArgonDebug.error('lazyloadInit failed:', err); }
}

// ==========================================================================
// Long article virtual rendering
// ==========================================================================
if (typeof highlightObserver === "undefined") {
	var highlightObserver = null;
}
if (typeof argonVirtualHeadIndexPatched === "undefined") {
	var argonVirtualHeadIndexPatched = false;
}

function disconnectHighlightObserver() {
	if (highlightObserver) {
		try {
			highlightObserver.disconnect();
		} catch (e) { }
		highlightObserver = null;
	}
}

function initArgonLongContentVirtualizer() {
	var content = document.getElementById("post_content");
	try {
		if (!content || content.getAttribute("data-argon-virtual-ready") === "true") {
			return;
		}
		if (postPasswordRequiredOnPage()) {
			return;
		}
		if (!("querySelectorAll" in content) || !("appendChild" in content)) {
			return;
		}

		var sourceSections = getArgonVirtualContentSource(content);
		if (sourceSections && sourceSections.length < 3) {
			restoreVirtualSourceFallback(content);
			return;
		}
		if (!sourceSections && content.querySelector("script:not(.argon-virtual-content-source)")) {
			return;
		}
		if (!sourceSections && content.querySelector("script.argon-virtual-content-source")) {
			restoreVirtualSourceFallback(content);
			return;
		}

		var existingBlocks = content.children.length;
		var textLength = (content.textContent || "").replace(/\s+/g, "").length;
		var heavyNodes = content.querySelectorAll("pre, table, blockquote, figure, img, iframe, video, .wp-block, .katex, .MathJax, script").length;
		if (!sourceSections && textLength < 12000 && existingBlocks < 120 && heavyNodes < 30) {
			return;
		}

		if (window.ArgonLongContentVirtualizer && typeof window.ArgonLongContentVirtualizer.destroy === "function") {
			window.ArgonLongContentVirtualizer.destroy(false);
		}

		window.ArgonLongContentVirtualizer = createArgonLongContentVirtualizer(content, {
			sourceSections: sourceSections,
			targetTextLength: 1800,
			maxPlainTextBlockLength: 2200,
			maxNodesPerSection: 18,
			keepRange: 2,
			viewportBuffer: 900,
			rootMargin: "1200px 0px 1200px 0px"
		});
		window.ArgonLongContentVirtualizer.init();
	} catch (e) {
		if (window.ArgonLongContentVirtualizer && typeof window.ArgonLongContentVirtualizer.destroy === "function") {
			try {
				window.ArgonLongContentVirtualizer.destroy(true);
			} catch (destroyError) { }
		}
		window.ArgonLongContentVirtualizer = null;
		restoreVirtualSourceFallback(content);
		if (typeof ArgonDebug !== "undefined" && ArgonDebug.warn) {
			ArgonDebug.warn("Long content virtualizer failed, restored full content:", e);
		}
	}
}

function getArgonVirtualContentSource(content) {
	var source = content.querySelector("script.argon-virtual-content-source[type='application/json']");
	if (!source) {
		return null;
	}
	try {
		var parsed = JSON.parse(source.textContent || "[]");
		return normalizeArgonVirtualSourceSections(parsed);
	} catch (e) {
		ArgonDebug.warn("Long content virtual source parse failed:", e);
		return null;
	}
}

function restoreVirtualSourceFallback(content) {
	if (!content) {
		return false;
	}
	var fallback = content.querySelector(".argon-virtual-content-fallback");
	if (fallback) {
		content.innerHTML = fallback.innerHTML;
		return true;
	}
	var noscript = content.querySelector("noscript");
	if (noscript) {
		content.innerHTML = noscript.textContent || noscript.innerHTML;
		return true;
	}
	return false;
}

function normalizeArgonVirtualSourceSections(sourceSections) {
	if (!sourceSections || !sourceSections.length) {
		return null;
	}
	var sections = [];
	for (var i = 0; i < sourceSections.length; i++) {
		var item = sourceSections[i] || {};
		var html = typeof item.html === "string" ? item.html : "";
		if (!html) {
			continue;
		}
		sections.push({
			index: sections.length,
			html: html,
			headers: item.headers && item.headers.length ? item.headers : [],
			nodeIds: item.nodeIds && item.nodeIds.length ? item.nodeIds : [],
			textCount: item.textCount || 0,
			placeholder: null,
			mounted: false,
			estimatedHeight: item.estimatedHeight || Math.max(120, Math.ceil((item.textCount || 0) / 42) * 32)
		});
	}
	return sections.length >= 2 ? sections : null;
}

function postPasswordRequiredOnPage() {
	var content = document.getElementById("post_content");
	return !!(content && content.querySelector(".post-password-form"));
}

function createArgonLongContentVirtualizer(content, options) {
	var sections = [];
	var headers = [];
	var observer = null;
	var scrollHandler = null;
	var resizeHandler = null;
	var scheduled = false;
	var destroyed = false;

	function init() {
		if (options.sourceSections && options.sourceSections.length) {
			sections = options.sourceSections;
			headers = collectSourceHeaders(sections);
		} else {
			var nodes = [];
			var children = content.children;
			for (var i = 0; i < children.length; i++) {
				nodes.push(children[i]);
			}
			nodes = expandVirtualSourceNodes(nodes);
			if (nodes.length < 2) {
				return;
			}
			sections = buildSections(nodes);
		}
		if (sections.length < 3) {
			return;
		}

		content.setAttribute("data-argon-virtual-ready", "true");
		content.classList.add("argon-long-content-virtual");
		renderShell();
		patchHeadIndexForVirtualContent();
		setupObserver();
		renderAroundIndex(0, true);
		scheduleUpdate();
		jQuery(document).trigger("argon:longContentVirtualized");
	}

	function buildSections(nodes) {
		var result = [];
		var buffer = [];
		var textCount = 0;
		var sectionHeaders = [];

		for (var i = 0; i < nodes.length; i++) {
			var node = nodes[i];
			var tag = (node.tagName || "").toLowerCase();
			var nodeTextLength = (node.textContent || "").replace(/\s+/g, "").length;
			var shouldBreakBefore = buffer.length > 0 && isHeading(tag) && (textCount >= 700 || buffer.length >= 5);
			var isHeavy = isHeavyVirtualNode(node, tag);
			var reachedLimit = buffer.length > 0 && (textCount + nodeTextLength > options.targetTextLength || buffer.length >= options.maxNodesPerSection);

			if (shouldBreakBefore || reachedLimit || (isHeavy && buffer.length >= 3)) {
				pushSection(result, buffer, textCount, sectionHeaders);
				buffer = [];
				textCount = 0;
				sectionHeaders = [];
			}

			collectSectionHeadings(node, result.length, sectionHeaders);

			buffer.push(node);
			textCount += nodeTextLength;

			if (isHeavy) {
				pushSection(result, buffer, textCount, sectionHeaders);
				buffer = [];
				textCount = 0;
				sectionHeaders = [];
			}
		}

		pushSection(result, buffer, textCount, sectionHeaders);
		return result;
	}

	function expandVirtualSourceNodes(nodes) {
		var expanded = [];
		for (var i = 0; i < nodes.length; i++) {
			var node = nodes[i];
			var chunks = splitPlainTextVirtualNode(node);
			for (var j = 0; j < chunks.length; j++) {
				expanded.push(chunks[j]);
			}
		}
		return expanded;
	}

	function splitPlainTextVirtualNode(node) {
		var tag = (node.tagName || "").toLowerCase();
		var text = node.textContent || "";
		var textLength = text.replace(/\s+/g, "").length;
		if (
			textLength <= options.maxPlainTextBlockLength ||
			!canSplitPlainTextVirtualNode(node, tag)
		) {
			return [node];
		}

		var parts = splitVirtualTextIntoChunks(text, options.targetTextLength);
		if (parts.length < 2) {
			return [node];
		}

		var result = [];
		for (var i = 0; i < parts.length; i++) {
			var clone = node.cloneNode(false);
			clone.textContent = parts[i];
			result.push(clone);
		}
		return result;
	}

	function canSplitPlainTextVirtualNode(node, tag) {
		if (!/^(p|li|div|blockquote)$/.test(tag) || node.id || !node.childNodes || node.childNodes.length !== 1) {
			return false;
		}
		return node.childNodes[0].nodeType === 3;
	}

	function splitVirtualTextIntoChunks(text, limit) {
		var chunks = [];
		var source = String(text || "");
		while (source.length > limit) {
			var cut = findVirtualTextCut(source, limit);
			chunks.push(source.slice(0, cut));
			source = source.slice(cut);
		}
		if (source.length) {
			chunks.push(source);
		}
		return chunks;
	}

	function findVirtualTextCut(text, limit) {
		var max = Math.min(text.length, limit);
		var min = Math.max(1, Math.floor(limit * 0.55));
		for (var i = max; i >= min; i--) {
			if (/[\s,.;:!?]/.test(text.charAt(i - 1))) {
				return i;
			}
		}
		return max;
	}

	function isHeavyVirtualNode(node, tag) {
		return tag === "pre" ||
			tag === "table" ||
			tag === "figure" ||
			tag === "iframe" ||
			tag === "video" ||
			tag === "img" ||
			(node.classList && (
				node.classList.contains("wp-block") ||
				node.classList.contains("katex") ||
				node.classList.contains("MathJax")
			));
	}

	function collectSourceHeaders(sourceSections) {
		var result = [];
		for (var i = 0; i < sourceSections.length; i++) {
			sourceSections[i].index = i;
			sourceSections[i].mounted = false;
			sourceSections[i].placeholder = null;
			var sectionHeaders = sourceSections[i].headers || [];
			for (var h = 0; h < sectionHeaders.length; h++) {
				sectionHeaders[h].sectionIndex = i;
				result.push(sectionHeaders[h]);
			}
		}
		return result;
	}

	function pushSection(result, nodes, textCount, sectionHeaders) {
		if (!nodes.length) {
			return;
		}
		result.push({
			index: result.length,
			html: nodesToHtml(nodes),
			headers: sectionHeaders.slice(),
			nodeIds: collectNodeIds(nodes),
			textCount: textCount,
			placeholder: null,
			mounted: false,
			estimatedHeight: estimateSectionHeight(nodes, textCount)
		});
	}

	function isHeading(tag) {
		return /^h[1-6]$/.test(tag);
	}

	function collectSectionHeadings(node, sectionIndex, sectionHeaders) {
		var headingNodes = [];
		if (isHeading((node.tagName || "").toLowerCase())) {
			headingNodes.push(node);
		}
		if (node.querySelectorAll) {
			var nestedHeadings = node.querySelectorAll("h1, h2, h3, h4, h5, h6");
			for (var i = 0; i < nestedHeadings.length; i++) {
				headingNodes.push(nestedHeadings[i]);
			}
		}
		for (var h = 0; h < headingNodes.length; h++) {
			if (!headingNodes[h].id) {
				headingNodes[h].id = "argon-virtual-heading-" + sectionIndex + "-" + (sectionHeaders.length + 1);
			}
			var headingInfo = {
				id: headingNodes[h].id,
				level: Number(headingNodes[h].tagName.charAt(1)),
				text: headingNodes[h].innerText || headingNodes[h].textContent || "",
				sectionIndex: sectionIndex
			};
			sectionHeaders.push(headingInfo);
			headers.push(headingInfo);
		}
	}

	function nodesToHtml(nodes) {
		var html = [];
		for (var i = 0; i < nodes.length; i++) {
			html.push(nodes[i].outerHTML);
		}
		return html.join("");
	}

	function collectNodeIds(nodes) {
		var ids = [];
		for (var i = 0; i < nodes.length; i++) {
			if (nodes[i].id) {
				ids.push(nodes[i].id);
			}
			var childrenWithId = nodes[i].querySelectorAll ? nodes[i].querySelectorAll("[id]") : [];
			for (var j = 0; j < childrenWithId.length; j++) {
				ids.push(childrenWithId[j].id);
			}
		}
		return ids;
	}

	function estimateSectionHeight(nodes, textCount) {
		var height = Math.max(120, Math.ceil(textCount / 42) * 32);
		for (var i = 0; i < nodes.length; i++) {
			var tag = (nodes[i].tagName || "").toLowerCase();
			if (tag === "pre" || tag === "table") {
				height += 220;
			} else if (tag === "img" || tag === "figure" || tag === "iframe" || tag === "video") {
				height += 320;
			} else if (isHeading(tag)) {
				height += 64;
			} else {
				height += 18;
			}
		}
		return height;
	}

	function renderShell() {
		content.innerHTML = "";
		for (var i = 0; i < sections.length; i++) {
			var placeholder = document.createElement("section");
			placeholder.className = "argon-virtual-section";
			placeholder.setAttribute("data-argon-virtual-index", String(i));
			placeholder.style.minHeight = sections[i].estimatedHeight + "px";
			sections[i].placeholder = placeholder;
			content.appendChild(placeholder);
		}
	}

	function setupObserver() {
		if ("IntersectionObserver" in window) {
			observer = new IntersectionObserver(function (entries) {
				for (var i = 0; i < entries.length; i++) {
					var section = getSectionFromNode(entries[i].target);
					if (!section) {
						continue;
					}
					if (entries[i].isIntersecting) {
						renderAroundIndex(section.index);
					}
				}
				pruneSections();
			}, { rootMargin: options.rootMargin, threshold: 0 });

			for (var s = 0; s < sections.length; s++) {
				observer.observe(sections[s].placeholder);
			}
		}
		scrollHandler = throttleVirtualUpdate(scheduleUpdate, 80);
		window.addEventListener("scroll", scrollHandler, { passive: true });

		resizeHandler = throttleVirtualUpdate(function () {
			for (var i = 0; i < sections.length; i++) {
				if (sections[i].mounted) {
					updateSectionHeight(sections[i]);
				}
			}
			scheduleUpdate();
		}, 160);
		window.addEventListener("resize", resizeHandler, { passive: true });
	}

	function scheduleUpdate() {
		if (scheduled || destroyed) {
			return;
		}
		scheduled = true;
		requestAnimationFrame(function () {
			scheduled = false;
			var index = getNearestVisibleSectionIndex();
			renderAroundIndex(index);
			pruneSections();
		});
	}

	function getNearestVisibleSectionIndex() {
		var viewportMid = window.innerHeight / 2;
		var nearestIndex = 0;
		var nearestDistance = Infinity;
		for (var i = 0; i < sections.length; i++) {
			var rect = sections[i].placeholder.getBoundingClientRect();
			var sectionMid = rect.top + rect.height / 2;
			var distance = Math.abs(sectionMid - viewportMid);
			if (distance < nearestDistance) {
				nearestDistance = distance;
				nearestIndex = i;
			}
		}
		return nearestIndex;
	}

	function renderAroundIndex(index, forceFirst) {
		var start = Math.max(0, index - options.keepRange);
		var end = Math.min(sections.length - 1, index + options.keepRange);
		var visibleRange = getViewportSectionRange();
		start = Math.min(start, visibleRange.start);
		end = Math.max(end, visibleRange.end);
		if (forceFirst) {
			end = Math.min(sections.length - 1, Math.max(end, 2));
		}
		for (var i = start; i <= end; i++) {
			mountSection(sections[i]);
		}
	}

	function mountSection(section) {
		if (!section || section.mounted || destroyed) {
			return;
		}
		var placeholder = section.placeholder;
		placeholder.classList.add("argon-virtual-section-mounted");
		placeholder.style.overflowAnchor = "auto";
		placeholder.style.minHeight = "";
		placeholder.innerHTML = section.html;
		section.mounted = true;
		updateSectionHeight(section);
		prepareRenderedSection(section);
	}

	function prepareRenderedSection(section) {
		if (window.__argonVirtualPreparing) {
			return;
		}
		window.__argonVirtualPreparing = true;
		try {
			if (typeof lazyloadInit === "function") {
				lazyloadInit(section.placeholder);
			}
			if (typeof zoomifyInit === "function") {
				zoomifyInit(section.placeholder);
			}
			if (typeof highlightJsRender === "function") {
				highlightJsRender(section.placeholder);
			}
			if (typeof tippyInit === "function") {
				tippyInit(section.placeholder);
			}
			renderMathForVirtualSection(section.placeholder);
			watchSectionMediaLoad(section);
		} catch (e) {
			ArgonDebug.warn("Long content section init failed:", e);
		}
		window.__argonVirtualPreparing = false;
	}

	function watchSectionMediaLoad(section) {
		if (!section || !section.placeholder || !section.placeholder.querySelectorAll) {
			return;
		}
		var media = section.placeholder.querySelectorAll("img, iframe, video");
		for (var i = 0; i < media.length; i++) {
			if (media[i]._argonVirtualHeightBound) {
				continue;
			}
			media[i]._argonVirtualHeightBound = true;
			var update = function () {
				updateSectionHeight(section);
				scheduleUpdate();
			};
			media[i].addEventListener("load", update, { once: true });
			media[i].addEventListener("loadedmetadata", update, { once: true });
			if ((media[i].tagName || "").toLowerCase() === "img" && media[i].complete) {
				update();
			}
			if ((media[i].tagName || "").toLowerCase() === "video" && media[i].readyState > 0) {
				update();
			}
		}
	}

	function pruneSections() {
		var visibleIndex = getNearestVisibleSectionIndex();
		var keepStart = Math.max(0, visibleIndex - options.keepRange - 1);
		var keepEnd = Math.min(sections.length - 1, visibleIndex + options.keepRange + 1);
		var visibleRange = getViewportSectionRange();
		keepStart = Math.min(keepStart, visibleRange.start);
		keepEnd = Math.max(keepEnd, visibleRange.end);
		for (var i = 0; i < sections.length; i++) {
			if (i < keepStart || i > keepEnd) {
				unmountSection(sections[i]);
			}
		}
	}

	function getViewportSectionRange() {
		var buffer = options.viewportBuffer || 800;
		var topLimit = -buffer;
		var bottomLimit = window.innerHeight + buffer;
		var start = sections.length - 1;
		var end = 0;
		var found = false;
		for (var i = 0; i < sections.length; i++) {
			var rect = sections[i].placeholder.getBoundingClientRect();
			if (rect.bottom >= topLimit && rect.top <= bottomLimit) {
				start = Math.min(start, i);
				end = Math.max(end, i);
				found = true;
			}
		}
		if (!found) {
			var nearest = getNearestVisibleSectionIndex();
			start = nearest;
			end = nearest;
		}
		return {
			start: Math.max(0, start - 1),
			end: Math.min(sections.length - 1, end + 1)
		};
	}

	function unmountSection(section) {
		if (!section || !section.mounted) {
			return;
		}
		updateSectionHeight(section);
		cleanupSectionRuntime(section.placeholder);
		while (section.placeholder.firstChild) {
			section.placeholder.removeChild(section.placeholder.firstChild);
		}
		section.placeholder.classList.remove("argon-virtual-section-mounted");
		section.placeholder.style.overflowAnchor = "";
		section.placeholder.style.minHeight = section.estimatedHeight + "px";
		section.mounted = false;
	}

	function updateSectionHeight(section) {
		var height = section.placeholder.scrollHeight;
		if (height > 0) {
			section.estimatedHeight = Math.max(80, height);
			if (section.mounted) {
				section.placeholder.style.minHeight = "";
			} else {
				section.placeholder.style.minHeight = section.estimatedHeight + "px";
			}
		}
	}

	function getSectionFromNode(node) {
		var index = parseInt(node.getAttribute("data-argon-virtual-index"), 10);
		if (isNaN(index)) {
			return null;
		}
		return sections[index] || null;
	}

	function findSectionById(id) {
		if (!id) {
			return null;
		}
		try {
			id = decodeURIComponent(id);
		} catch (e) { }
		for (var i = 0; i < sections.length; i++) {
			for (var h = 0; h < sections[i].headers.length; h++) {
				if (sections[i].headers[h].id === id) {
					return sections[i];
				}
			}
			for (var n = 0; n < sections[i].nodeIds.length; n++) {
				if (sections[i].nodeIds[n] === id) {
					return sections[i];
				}
			}
		}
		return null;
	}

	function renderTarget(id) {
		var section = findSectionById(id);
		if (!section) {
			return false;
		}
		renderAroundIndex(section.index);
		mountSection(section);
		return true;
	}

	function getHeaders() {
		return headers.slice();
	}

	function getHeaderOffset(header, offset) {
		var section = findSectionById(header.id);
		if (section && section.mounted) {
			var target = document.getElementById(header.id);
			if (target) {
				return $(target).offset().top - (offset || 0);
			}
		}
		if (section && section.placeholder) {
			return $(section.placeholder).offset().top - (offset || 0);
		}
		var fallback = $("#" + escapeJqueryId(header.id));
		return fallback.length ? fallback.offset().top - (offset || 0) : 0;
	}

	function destroy(restoreContent) {
		destroyed = true;
		if (observer) {
			try {
				observer.disconnect();
			} catch (e) { }
			observer = null;
		}
		if (scrollHandler) {
			window.removeEventListener("scroll", scrollHandler);
			scrollHandler = null;
		}
		if (resizeHandler) {
			window.removeEventListener("resize", resizeHandler);
			resizeHandler = null;
		}
		if (content && content.getAttribute("data-argon-virtual-ready") === "true") {
			content.classList.remove("argon-long-content-virtual");
			content.removeAttribute("data-argon-virtual-ready");
			if (restoreContent) {
				var restoredHtml = [];
				for (var i = 0; i < sections.length; i++) {
					restoredHtml.push(sections[i].html);
				}
				content.innerHTML = restoredHtml.join("");
			} else {
				content.innerHTML = "";
			}
		}
	}

	return {
		init: init,
		destroy: destroy,
		getHeaders: getHeaders,
		getHeaderOffset: getHeaderOffset,
		renderTarget: renderTarget,
		renderAroundIndex: renderAroundIndex,
		isReady: function () { return content.getAttribute("data-argon-virtual-ready") === "true"; }
	};
}

function throttleVirtualUpdate(callback, wait) {
	var last = 0;
	var timer = null;
	return function () {
		var now = Date.now();
		var remaining = wait - (now - last);
		if (remaining <= 0) {
			if (timer) {
				clearTimeout(timer);
				timer = null;
			}
			last = now;
			callback();
		} else if (!timer) {
			timer = setTimeout(function () {
				timer = null;
				last = Date.now();
				callback();
			}, remaining);
		}
	};
}

	function cleanupSectionRuntime(root) {
	if (!root) {
		return;
	}
	if (root._argonLazyloadObserver) {
		try {
			root._argonLazyloadObserver.disconnect();
		} catch (e) { }
		root._argonLazyloadObserver = null;
	}
	var zoomImages = root.querySelectorAll("img.zoomify-initialized");
	for (var z = 0; z < zoomImages.length; z++) {
		try {
			var instance = $(zoomImages[z]).data("zoomify");
			if (instance && typeof instance.destroy === "function") {
				instance.destroy();
			}
			$(zoomImages[z]).removeData("zoomify");
			zoomImages[z].classList.remove("zoomify-initialized");
		} catch (e) { }
	}
	if (zoomImages.length && zoomifyInstances && zoomifyInstances.length) {
		zoomifyInstances = zoomifyInstances.filter(function (instance) {
			for (var i = 0; i < zoomImages.length; i++) {
				if (instance && instance.$element && instance.$element[0] === zoomImages[i]) {
					return false;
				}
			}
			return true;
		});
	}
	if (highlightObserver) {
		var codeBlocks = root.querySelectorAll("pre > code");
		for (var i = 0; i < codeBlocks.length; i++) {
			try {
				highlightObserver.unobserve(codeBlocks[i]);
			} catch (e) { }
		}
	}
	if (typeof ClipboardJS !== "undefined") {
		var copyButtons = root.querySelectorAll(".hljs-control-copy");
		for (var c = 0; c < copyButtons.length; c++) {
			if (copyButtons[c]._clipboard && typeof copyButtons[c]._clipboard.destroy === "function") {
				copyButtons[c]._clipboard.destroy();
				copyButtons[c]._clipboard = null;
			}
		}
	}
	var renderedCodeBlocks = root.querySelectorAll("pre.hljs-codeblock > code[hljs-codeblock-inner]");
	for (var r = 0; r < renderedCodeBlocks.length; r++) {
		renderedCodeBlocks[r].removeAttribute("hljs-codeblock-inner");
	}
	var tips = root.querySelectorAll("[data-tippy-root], .tippy-initialized");
	for (var t = 0; t < tips.length; t++) {
		if (tips[t]._tippy && typeof tips[t]._tippy.destroy === "function") {
			tips[t]._tippy.destroy();
		}
		tips[t].classList.remove("tippy-initialized");
	}
}

function renderMathForVirtualSection(root) {
	try {
		if (typeof MathJax !== "undefined") {
			if (MathJax.Hub && typeof MathJax.Hub.Queue === "function") {
				MathJax.Hub.Queue(["Typeset", MathJax.Hub, root]);
			} else if (typeof MathJax.typesetPromise === "function") {
				MathJax.typesetPromise([root]);
			} else if (typeof MathJax.typeset === "function") {
				MathJax.typeset([root]);
			}
		}
	} catch (e) { }
	try {
		if (typeof renderMathInElement !== "undefined") {
			renderMathInElement(root, {
				delimiters: [
					{ left: "$$", right: "$$", display: true },
					{ left: "$", right: "$", display: false },
					{ left: "\\(", right: "\\)", display: false }
				]
			});
		}
	} catch (e) { }
}

function patchHeadIndexForVirtualContent() {
	if (argonVirtualHeadIndexPatched || typeof jQuery === "undefined" || !jQuery.fn || typeof jQuery.fn.headIndex !== "function") {
		return;
	}

	var originalHeadIndex = jQuery.fn.headIndex;
	jQuery.fn.headIndex = function (options) {
		var virtualizer = window.ArgonLongContentVirtualizer;
		if (!virtualizer || !virtualizer.isReady || !virtualizer.isReady() || !options || options.articleWrapSelector !== "#post_content") {
			var result = originalHeadIndex.apply(this, arguments);
			this.each(function () {
				decorateHeadIndexFollowScroll(jQuery(this).data("headIndex"));
			});
			return result;
		}
		return this.each(function () {
			var instance = createVirtualHeadIndexInstance(options, virtualizer);
			decorateHeadIndexFollowScroll(instance);
			jQuery(this).data("headIndex", instance);
		});
	};
	argonVirtualHeadIndexPatched = true;
	decorateHeadIndexFollowScroll(jQuery(document).data("headIndex"));
}

function decorateHeadIndexFollowScroll(instance) {
	if (!instance || instance._argonFollowScrollPatched) {
		return;
	}
	instance._argonFollowScrollPatched = true;
	instance.mouseHovered = false;
	if (typeof instance.scrollCurrentIntoView === "function") {
		instance.scrollCurrentIntoView = function (indexItem) {
			scrollCatalogItemIntoView(this.indexScrollBox || jQuery(this.settings && this.settings.indexScrollBoxSelector), indexItem);
		};
	}
}

function scrollCatalogItemIntoView(indexScrollBox, indexItem) {
	var scrollBox = jQuery(indexScrollBox).eq(0);
	var item = jQuery(indexItem).eq(0);
	var scrollNode = scrollBox[0];
	var itemNode = item[0];
	if (!scrollNode || !itemNode) {
		return;
	}
	var boxHeight = scrollBox.innerHeight();
	var itemHeight = item.outerHeight();
	if (boxHeight <= 0 || itemHeight <= 0) {
		return;
	}
	var itemTop;
	if (scrollNode.getBoundingClientRect && itemNode.getBoundingClientRect) {
		itemTop = itemNode.getBoundingClientRect().top - scrollNode.getBoundingClientRect().top;
	} else {
		itemTop = item.position().top;
	}
	var scrollTop = scrollBox.scrollTop();
	var maxScroll = Math.max(0, scrollNode.scrollHeight - boxHeight);
	var target = scrollTop + itemTop - (boxHeight / 2) + (itemHeight / 2);
	target = Math.max(0, Math.min(target, maxScroll));
	if (Math.abs(target - scrollTop) < 4) {
		return;
	}
	scrollBox.stop(true).animate({ scrollTop: target }, 220, "easeOutExpo");
}

function createVirtualHeadIndexInstance(options, virtualizer) {
	var settings = jQuery.extend(true, {
		indexBoxSelector: "#leftbar_catalog",
		indexScrollBoxSelector: "#leftbar_part2_inner",
		scrollSelector: "body,html",
		subItemBoxClass: "index-subItem-box",
		itemClass: "index-item",
		linkClass: "index-link",
		offset: 0,
		defaultExpanded: false
	}, options || {});
	var headers = virtualizer.getHeaders();
	var indexBox = jQuery(settings.indexBoxSelector);
	var indexScrollBox = jQuery(settings.indexScrollBoxSelector);
	var scrollBody = jQuery(settings.scrollSelector);
	var manual = false;
	var tree = buildVirtualHeaderTree(headers);

	function buildCatalogHtml(items) {
		var html = [];
		for (var i = 0; i < items.length; i++) {
			html.push("<li class='" + settings.itemClass + "'><a no-pjax class='" + settings.linkClass + "' href='#" + items[i].item.id + "'>" + escapeCatalogText(items[i].item.text || "") + "</a>");
			if (items[i].children.length) {
				html.push("<ul class='" + settings.subItemBoxClass + (settings.defaultExpanded ? " open" : "") + "'>");
				html.push(buildCatalogHtml(items[i].children));
				html.push("</ul>");
			}
			html.push("</li>");
		}
		return html.join("");
	}

	function init() {
		if (!indexBox.length || !headers.length) {
			return;
		}
		indexBox.html("<ul>" + buildCatalogHtml(tree) + "</ul>");
		if (settings.defaultExpanded) {
			indexBox.find("ul." + settings.subItemBoxClass).show().addClass("open");
		}
		bindEvents();
		updateCurrent();
	}

	function bindEvents() {
		var manualTimer = null;
		indexBox.off("click.headindex").on("click.headindex", "." + settings.linkClass, function (event) {
			event.preventDefault();
			var href = this.getAttribute("href") || "";
			var indexItem = jQuery(this).parent("." + settings.itemClass);
			manual = true;
			if (manualTimer) {
				clearTimeout(manualTimer);
			}
			manualTimer = setTimeout(function () {
				manual = false;
			}, 400);
			current(indexItem);
			scrollTo(href);
		});
	}

	function current(indexItem) {
		if (!indexItem.length || indexItem.hasClass("current")) {
			scrollCatalogItemIntoView(indexScrollBox, indexItem);
			return;
		}
		indexBox.find("li.current").removeClass("current");
		if (!settings.defaultExpanded) {
			indexBox.find("ul.open").removeClass("open");
		}
		var subBox = indexItem.children("." + settings.subItemBoxClass);
		if (subBox.length) {
			subBox.addClass("open").slideDown();
		}
		var parentsBox = indexItem.parents("ul." + settings.subItemBoxClass);
		if (parentsBox.length) {
			parentsBox.addClass("open").slideDown();
		}
		if (!settings.defaultExpanded) {
			indexBox.find("ul." + settings.subItemBoxClass).not(".open").slideUp();
		}
		indexItem.addClass("current");
		scrollCatalogItemIntoView(indexScrollBox, indexItem);
	}

	function scrollTo(href) {
		var id = href.charAt(0) === "#" ? href.slice(1) : href;
		virtualizer.renderTarget(id);
		var header = findVirtualHeaderById(headers, id);
		if (!header) {
			return;
		}
		var target = document.getElementById(header.id);
		var top = target ? $(target).offset().top - settings.offset : virtualizer.getHeaderOffset(header, settings.offset);
		scrollBody.stop().animate({ scrollTop: top + 8 }, "normal", "easeOutExpo");
	}

	function updateCurrent() {
		if (manual || !headers.length) {
			return;
		}
		var scrollTop = scrollBody.scrollTop();
		var currentHeader = headers[0];
		for (var i = 0; i < headers.length; i++) {
			var top = virtualizer.getHeaderOffset(headers[i], settings.offset);
			if (top <= scrollTop + 8) {
				currentHeader = headers[i];
			} else {
				break;
			}
		}
		var currentLink = indexBox.find("a." + settings.linkClass).filter(function () {
			return (this.getAttribute("href") || "") === "#" + currentHeader.id;
		});
		current(currentLink.parent("li." + settings.itemClass));
	}

	function clean() {
		indexBox.off("click.headindex");
		jQuery(settings.scrollWrap || window).off("scroll.virtualHeadIndex resize.virtualHeadIndex");
	}

	init();
	jQuery(settings.scrollWrap || window).off("scroll.virtualHeadIndex resize.virtualHeadIndex").on("scroll.virtualHeadIndex resize.virtualHeadIndex", throttleVirtualUpdate(updateCurrent, 80));
	return {
		updateCurrent: updateCurrent,
		scrollTo: scrollTo,
		current: current,
		clean: clean,
		ignoreScrollEvent: function (ignore) {
			manual = !!ignore;
			if (!manual) {
				updateCurrent();
			}
		}
	};
}

function buildVirtualHeaderTree(headers) {
	var tree = [];
	var current = null;
	for (var i = 0; i < headers.length; i++) {
		var item = { item: headers[i], parent: null, children: [] };
		item.item.h = Number(item.item.level);
		if (!current) {
			tree.push(item);
			current = item;
			continue;
		}
		if (current.item.h < item.item.h) {
			item.parent = current;
			current.children.push(item);
			current = item;
			continue;
		}
		if (current.item.h === item.item.h) {
			item.parent = current.parent;
			((current.parent && current.parent.children) || tree).push(item);
			current = item;
			continue;
		}
		while (current && current.item.h > item.item.h) {
			current = current.parent;
		}
		if (!current) {
			tree.push(item);
			current = item;
		} else {
			i--;
		}
	}
	return tree;
}

function findVirtualHeaderById(headers, id) {
	try {
		id = decodeURIComponent(id);
	} catch (e) { }
	for (var i = 0; i < headers.length; i++) {
		if (headers[i].id === id) {
			return headers[i];
		}
	}
	return null;
}

function escapeJqueryId(id) {
	if (jQuery.escapeSelector) {
		return jQuery.escapeSelector(id);
	}
	return String(id).replace(/([ !"#$%&'()*+,./:;<=>?@\[\\\]^`{|}~])/g, "\\$1");
}

function escapeCatalogText(text) {
	return String(text).replace(/[&<>"']/g, function (char) {
		return {
			"&": "&amp;",
			"<": "&lt;",
			">": "&gt;",
			'"': "&quot;",
			"'": "&#39;"
		}[char];
	});
}

initArgonLongContentVirtualizer();

// ==========================================================================
// 内联脚本执行器 (Inline Script Executor)
// ==========================================================================

/**
 * 执行单个脚本元素
 * 需求 4.4: 脚本执行失败时捕获错误并记录日志，不中断其他脚本执行
 * 
 * @param {HTMLScriptElement} oldScript - 原始脚本元素
 * @returns {boolean} 是否执行成功
 */
function executeScript(oldScript) {
	try {
		// 创建新的 script 元素
		const newScript = document.createElement('script');

		// 复制脚本内容
		if (oldScript.textContent) {
			newScript.textContent = oldScript.textContent;
		}

		// 复制所有属性（包括 type, async, defer 等）
		// 需求 4.5: 尊重 async 和 defer 属性的执行时机
		Array.from(oldScript.attributes).forEach(attr => {
			newScript.setAttribute(attr.name, attr.value);
		});

		// 将脚本添加到 head 并立即执行
		document.head.appendChild(newScript);

		// 执行后立即移除，避免污染 DOM
		document.head.removeChild(newScript);

		ArgonDebug.log('Script executed successfully:', oldScript.textContent.substring(0, 50) + '...');
		return true;
	} catch (error) {
		// 需求 4.4: 捕获错误并记录日志
		ArgonDebug.error('Script execution failed:', error);
		ArgonDebug.error('Failed script content:', oldScript.textContent);
		return false;
	}
}

/**
 * 执行新页面中的所有内联脚本
 * 需求 4.1: 提取新页面中的所有 script 标签
 * 需求 4.2: 区分内联脚本和外部脚本
 * 需求 4.3: 按照脚本在 DOM 中的顺序执行
 * 需求 4.4: 脚本执行失败不中断其他脚本执行
 * 
 * @param {HTMLElement|Document} container - 新页面的容器元素或文档对象
 * @returns {Object} 执行结果统计 {total, success, failed}
 */
function executeInlineScripts(container) {
	// 如果没有传入容器，使用整个文档
	if (!container) {
		container = document;
	}

	// 需求 4.1: 提取所有 script 标签
	const scripts = container.querySelectorAll('script');

	if (scripts.length === 0) {
		ArgonDebug.log('No scripts found in new page');
		return { total: 0, success: 0, failed: 0 };
	}

	let successCount = 0;
	let failedCount = 0;

	ArgonDebug.log(`Found ${scripts.length} script tags in new page`);

	// 需求 4.3: 按照脚本在 DOM 中的顺序执行
	scripts.forEach((script, index) => {
		if (script.getAttribute('data-pjax-executed') === 'true') {
			return;
		}
		var scriptType = (script.getAttribute('type') || '').toLowerCase();
		if (scriptType && scriptType !== 'text/javascript' && scriptType !== 'application/javascript' && scriptType !== 'module') {
			ArgonDebug.log(`Script ${index + 1}: Data script (${scriptType}), skipped`);
			return;
		}
		// 需求 4.2: 只执行内联脚本（没有 src 属性的脚本）
		if (!script.src) {
			// 跳过空脚本
			if (!script.textContent || script.textContent.trim() === '') {
				ArgonDebug.log(`Script ${index + 1}: Empty, skipped`);
				return;
			}

			ArgonDebug.log(`Executing inline script ${index + 1}/${scripts.length}`);

			// 需求 4.4: 错误隔离 - 单个脚本失败不影响其他脚本
			const success = executeScript(script);
			if (success) {
				script.setAttribute('data-pjax-executed', 'true');
				successCount++;
			} else {
				failedCount++;
			}
		} else {
			// 外部脚本由浏览器自动加载，不需要手动执行
			ArgonDebug.log(`Script ${index + 1}: External (${script.src}), skipped`);
		}
	});

	const result = {
		total: scripts.length,
		success: successCount,
		failed: failedCount
	};

	ArgonDebug.log('Script execution completed:', result);
	return result;
}

var pjaxContainerSelectors = ['#primary', '#leftbar_part1_menu', '#leftbar_part2_inner', '.page-information-card-container', '#rightbar', '#wpadminbar'];
var pjaxContainers = pjaxContainerSelectors.filter(function (selector) {
	return document.querySelector(selector);
});
var argonPjaxLinkSelector = "a[href]:not([no-pjax]):not(.no-pjax):not([target='_blank']):not([download]):not(.reference-link):not(.reference-list-backlink):not([href^='#'])";

var ArgonPjaxPrefetch = (function () {
	var CACHE_TTL = 45000;
	var MAX_CACHE_ITEMS = 8;
	var cache = {};
	var cacheOrder = [];
	var inflight = {};
	var pendingNavigation = null;
	var transportInstalled = false;

	function normalizeUrl(url) {
		try {
			var parsed = new URL(url, window.location.href);
			parsed.hash = '';
			parsed.searchParams.delete('_pjax');
			parsed.searchParams.delete('_');
			return parsed.href.replace(/\?$/, '');
		} catch (e) {
			var link = document.createElement('a');
			link.href = url;
			link.hash = '';
			return link.href;
		}
	}

	function getSameOriginUrl(input) {
		var href = typeof input === 'string' ? input : (input && input.href);
		if (!href) {
			return null;
		}
		try {
			var url = new URL(href, window.location.href);
			if (url.protocol !== window.location.protocol || url.hostname !== window.location.hostname || url.port !== window.location.port) {
				return null;
			}
			if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
				return null;
			}
			return url.href;
		} catch (e) {
			return null;
		}
	}

	function readCache(key) {
		var item = cache[key];
		if (!item) {
			return null;
		}
		if (Date.now() - item.time > CACHE_TTL) {
			delete cache[key];
			return null;
		}
		return item;
	}

	function remember(key, item) {
		cache[key] = item;
		cacheOrder = cacheOrder.filter(function (cachedKey) {
			return cachedKey !== key;
		});
		cacheOrder.push(key);
		while (cacheOrder.length > MAX_CACHE_ITEMS) {
			delete cache[cacheOrder.shift()];
		}
	}

	function installTransport() {
		if (transportInstalled || typeof jQuery === 'undefined' || !jQuery.ajaxTransport) {
			return;
		}
		transportInstalled = true;
		jQuery.ajaxTransport('+html', function (options) {
			if (!options || (options.type || 'GET').toUpperCase() !== 'GET') {
				return;
			}
			var cached = readCache(normalizeUrl(options.url));
			if (!cached) {
				return;
			}
			return {
				send: function (headers, complete) {
					window.setTimeout(function () {
						complete(cached.status || 200, cached.statusText || 'success', { text: cached.html }, cached.headers || 'Content-Type: text/html');
					}, 0);
				},
				abort: function () { }
			};
		});
	}

	function prefetch(input) {
		if (typeof jQuery === 'undefined' || !jQuery.ajax) {
			return null;
		}
		var url = getSameOriginUrl(input);
		if (!url) {
			return null;
		}
		installTransport();
		var key = normalizeUrl(url);
		var cached = readCache(key);
		if (cached) {
			var done = jQuery.Deferred();
			done.resolve(cached);
			return done.promise();
		}
		if (inflight[key]) {
			return inflight[key];
		}
		var request = jQuery.ajax({
			url: key,
			type: 'GET',
			dataType: 'html',
			cache: true,
			global: false,
			timeout: 6000,
			headers: {
				'X-PJAX': 'true',
				'X-PJAX-Container': pjaxContainers.join(',')
			}
		}).done(function (html, status, xhr) {
			remember(key, {
				html: html,
				status: xhr && xhr.status ? xhr.status : 200,
				statusText: xhr && xhr.statusText ? xhr.statusText : status,
				headers: xhr && xhr.getAllResponseHeaders ? xhr.getAllResponseHeaders() : 'Content-Type: text/html',
				time: Date.now()
			});
		}).always(function () {
			delete inflight[key];
		});
		inflight[key] = request;
		return request;
	}

	function navigate(input, options) {
		var url = getSameOriginUrl(input && input.url ? input.url : input);
		if (!url || pendingNavigation) {
			return !!pendingNavigation;
		}
		installTransport();
		var opts = jQuery.extend({}, options || {}, { url: url });
		opts.target = opts.target || input;
		if (!opts.container) {
			opts.container = pjaxContainers.length ? pjaxContainers : ['#primary'];
		}
		if (!opts.fragment) {
			opts.fragment = pjaxContainers.length ? pjaxContainers : ['#primary'];
		}
		pendingNavigation = normalizeUrl(url);
		pjaxLoading = true;
		if (typeof PageLoader !== 'undefined') {
			PageLoader.show(document);
			PageLoader.setProgress(12);
		}
		if (typeof NProgress !== 'undefined') {
			NProgress.remove();
			NProgress.start();
			NProgress.set(0.18);
		}

		function runPjax() {
			pendingNavigation = null;
			try {
				jQuery.pjax(opts);
			} catch (err) {
				pjaxLoading = false;
				if (typeof NProgress !== 'undefined') {
					NProgress.done();
				}
				window.location.href = url;
			}
		}

		var cached = readCache(pendingNavigation);
		if (cached) {
			runPjax();
			return true;
		}
		var request = prefetch(url);
		if (request && request.done) {
			request.done(function () {
				if (typeof PageLoader !== 'undefined') {
					PageLoader.setProgress(42);
				}
				if (typeof NProgress !== 'undefined') {
					NProgress.set(0.46);
				}
			}).always(runPjax);
		} else {
			runPjax();
		}
		return true;
	}

	function bind() {
		if (typeof jQuery === 'undefined') {
			return;
		}
		installTransport();
		jQuery(document).on('click.argonPjaxPrefetch', argonPjaxLinkSelector, function (event) {
			if (event.which > 1 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.isDefaultPrevented()) {
				return;
			}
			if (navigate(this, { target: this })) {
				event.preventDefault();
			}
		});
		jQuery(document).on('mouseenter focusin touchstart', argonPjaxLinkSelector, function () {
			prefetch(this);
		});
	}

	return {
		bind: bind,
		prefetch: prefetch,
		navigate: navigate
	};
})();

// ==========================================================================
// 现代化页面加载系统
// ==========================================================================

/**
 * 页面加载管理器 - 提供智能加载动画和进度追踪
 */
var PageLoader = (function () {
	var CONFIG = {
		OVERLAY_ID: 'page-loader',
		MIN_DISPLAY_TIME: 280,
		FADE_DURATION: 220,
		RESOURCE_TIMEOUT: 5000,
		PRELOAD_LIMIT: 92
	};

	var state = {
		element: null,
		isVisible: false,
		startTime: 0,
		progress: 0,
		hideTimer: null,
		pendingHide: false,
		trackedTotal: 0,
		trackedDone: 0,
		trackId: 0,
		trackedNodes: []
	};

	function createElement() {
		var el = document.createElement('div');
		el.id = CONFIG.OVERLAY_ID;
		el.className = 'page-loader is-visible';
		el.innerHTML = '<div class="loader-progress" aria-hidden="true"><div class="loader-progress-bar"></div></div>';
		return el;
	}

	function getElement() {
		if (!state.element) {
			state.element = document.getElementById(CONFIG.OVERLAY_ID);
			if (!state.element) {
				state.element = createElement();
				if (document.body) {
					document.body.insertBefore(state.element, document.body.firstChild);
				} else {
					document.documentElement.appendChild(state.element);
				}
			}
		}
		return state.element;
	}

	function updateProgress(progress) {
		var el = getElement();
		var bar = el.querySelector('.loader-progress-bar');
		progress = Math.max(0, Math.min(100, progress));
		state.progress = progress;
		if (bar) {
			bar.style.width = progress + '%';
		}
	}

	function canHide() {
		return state.trackedTotal === 0 || state.trackedDone >= state.trackedTotal;
	}

	function markResourceDone() {
		state.trackedDone += 1;
		if (state.trackedTotal > 0) {
			updateProgress(Math.min(CONFIG.PRELOAD_LIMIT, (state.trackedDone / state.trackedTotal) * CONFIG.PRELOAD_LIMIT));
		}
	}

	function isResourceReady(node) {
		if (!node) return true;
		if (node.tagName === 'LINK') {
			return !!node.sheet;
		}
		if (typeof node.complete !== 'undefined') {
			return node.complete;
		}
		return false;
	}

	function bindResource(node, trackId) {
		var done = false;
		function onDone() {
			if (done) return;
			done = true;
			node.removeEventListener('load', onDone);
			node.removeEventListener('error', onDone);
			if (trackId !== state.trackId) {
				return;
			}
			markResourceDone();
			if (state.pendingHide && canHide()) {
				hide();
			}
		}
		node.addEventListener('load', onDone);
		node.addEventListener('error', onDone);
		if (isResourceReady(node)) {
			setTimeout(onDone, 0);
		}
		setTimeout(onDone, CONFIG.RESOURCE_TIMEOUT);
	}

	function isImagePending(img) {
		return img && !img.complete && img.getAttribute && img.getAttribute('loading') !== 'lazy';
	}

	function isStylePending(node) {
		return node && node.tagName === 'LINK' && node.rel && node.rel.toLowerCase().indexOf('stylesheet') > -1;
	}

	function collectPreloadResources(root) {
		var scope = root || document;
		var nodes = [];
		var imgs = scope.querySelectorAll ? scope.querySelectorAll('img') : [];
		for (var i = 0; i < imgs.length; i++) {
			if (isImagePending(imgs[i])) {
				nodes.push(imgs[i]);
			}
		}
		var styles = document.querySelectorAll('link[rel~="stylesheet"]');
		for (var j = 0; j < styles.length; j++) {
			if (!styles[j].sheet && !styles[j].getAttribute('data-argon-preload-seen') && isStylePending(styles[j])) {
				nodes.push(styles[j]);
			}
		}
		var loader = getElement();
		var bgUrls = [];
		if (loader.getAttribute('data-page-bg')) {
			bgUrls.push(loader.getAttribute('data-page-bg'));
		}
		if (document.documentElement.classList.contains('darkmode') && loader.getAttribute('data-page-bg-dark')) {
			bgUrls.push(loader.getAttribute('data-page-bg-dark'));
		}
		for (var k = 0; k < bgUrls.length; k++) {
			var preloadImg = new Image();
			preloadImg.src = bgUrls[k];
			if (!preloadImg.complete) {
				nodes.push(preloadImg);
			}
		}
		return nodes;
	}

	function trackResources(root) {
		state.trackId += 1;
		var nodes = collectPreloadResources(root);
		state.trackedNodes = nodes;
		state.trackedTotal = nodes.length;
		state.trackedDone = 0;

		if (!nodes.length) {
			updateProgress(72);
			return;
		}

		updateProgress(8);
		var trackId = state.trackId;
		nodes.forEach(function (node) {
			if (node.setAttribute) {
				node.setAttribute('data-argon-preload-seen', 'true');
			}
			bindResource(node, trackId);
		});
	}

	function show(root) {
		var el = getElement();
		if (state.hideTimer) {
			clearTimeout(state.hideTimer);
			state.hideTimer = null;
		}
		state.startTime = Date.now();
		state.isVisible = true;
		state.pendingHide = false;
		el.classList.remove('is-hiding');
		el.classList.add('is-visible');
		state.trackedTotal = 0;
		state.trackedDone = 0;
		state.trackedNodes = [];
		updateProgress(0);
		trackResources(root || document);
	}

	function hide() {
		var el = getElement();
		if (!state.isVisible && !el.classList.contains('is-visible')) return;
		if (!canHide()) {
			state.pendingHide = true;
			updateProgress(96);
			return;
		}

		updateProgress(100);
		state.pendingHide = false;
		var elapsedTime = Date.now() - state.startTime;
		var remainingTime = Math.max(0, CONFIG.MIN_DISPLAY_TIME - elapsedTime);

		state.hideTimer = setTimeout(function () {
			el.classList.add('is-hiding');
			setTimeout(function () {
				el.classList.remove('is-visible');
				el.classList.remove('is-hiding');
				state.isVisible = false;
			}, CONFIG.FADE_DURATION);
		}, remainingTime);
	}

	function setProgress(progress) {
		updateProgress(progress);
	}

	function destroy() {
		if (state.hideTimer) {
			clearTimeout(state.hideTimer);
			state.hideTimer = null;
		}
		if (state.element && state.element.parentNode) {
			state.element.parentNode.removeChild(state.element);
		}
		state.element = null;
		state.isVisible = false;
		state.trackId += 1;
	}

	return {
		show: show,
		hide: hide,
		setProgress: setProgress,
		trackResources: trackResources,
		destroy: destroy
	};
})();

/**
 * 向后兼容的函数
 */
function showLoadingOverlay() {
	PageLoader.setProgress(0);
}

function hideLoadingOverlay() {
	PageLoader.setProgress(100);
	PageLoader.hide();
}

if (typeof NProgress !== 'undefined') {
	NProgress.configure({ showSpinner: false });
}

if (document.readyState !== 'complete') {
	PageLoader.show(document);
	window.addEventListener('load', function () {
		PageLoader.hide();
	});
} else {
	PageLoader.show(document);
	PageLoader.hide();
}
var pageTransitionTimer = null;

function getPageTransitionDuration() {
	var fallback = 260;
	try {
		var value = getComputedStyle(document.documentElement).getPropertyValue('--pjax-transition-duration').trim();
		if (!value) return fallback;
		if (value.indexOf('ms') > -1) return parseFloat(value);
		if (value.indexOf('s') > -1) return parseFloat(value) * 1000;
		return parseFloat(value) || fallback;
	} catch (e) {
		return fallback;
	}
}

function markPageTransitionContent() {
	pjaxContainers.forEach(function (selector) {
		var c = document.querySelector(selector);
		if (c) c.classList.add('page-transition-content');
	});
}

function startPageExitTransition() {
	if (pageTransitionTimer) {
		clearTimeout(pageTransitionTimer);
		pageTransitionTimer = null;
	}
	document.documentElement.classList.remove('page-transition-enter');
	document.documentElement.classList.add('page-transition-leave');
	markPageTransitionContent();
	requestAnimationFrame(function () {
		document.documentElement.classList.add('page-transition-active');
	});
}

function startPageEnterTransition() {
	if (pageTransitionTimer) {
		clearTimeout(pageTransitionTimer);
		pageTransitionTimer = null;
	}
	document.documentElement.classList.remove('page-transition-leave', 'page-transition-active');
	document.documentElement.classList.add('page-transition-enter');
	markPageTransitionContent();
	void document.documentElement.offsetWidth;
	requestAnimationFrame(function () {
		document.documentElement.classList.add('page-transition-active');
	});
	pageTransitionTimer = setTimeout(endPageTransition, getPageTransitionDuration() + 80);
}

function endPageTransition() {
	if (pageTransitionTimer) {
		clearTimeout(pageTransitionTimer);
		pageTransitionTimer = null;
	}
	document.documentElement.classList.remove('page-transition-active');
	document.documentElement.classList.remove('page-transition-enter');
	document.documentElement.classList.remove('page-transition-leave');
	pjaxContainers.forEach(function (selector) {
		var c = document.querySelector(selector);
		if (c) c.classList.remove('page-transition-content');
	});
}

$.pjax.defaults.timeout = 10000;
$.pjax.defaults.container = pjaxContainers;
$.pjax.defaults.fragment = pjaxContainers;

/*
 * PJAX 事件处理优化说明：
 * 1. pjax:beforeReplace - 统一清理资源（LazyLoad Observer、Zoomify、Tippy）
 * 2. pjax:complete - 单次初始化所有功能模块，添加错误处理，初始化所有功能模块
 * 3. pjax:end - 只处理特定任务（移动端目录重置、GT4 验证码重置）
 * 4. 移除了重复的初始化调用，避免资源泄漏和性能问题
 * 
 * 需求映射：
 * - pjax:beforeReplace: 需求 1.1-1.4 (资源清理)
 * - pjax:complete: 需求 1.5, 1.6 (模块初始化和错误隔离)
 * - pjax:end: 需求 1.7 (特定任务处理)
 */
if (argonConfig.disable_pjax != true && argonConfig.disable_pjax != 'true') {
	ArgonPjaxPrefetch.bind();
	$(document).pjax(
		argonPjaxLinkSelector,
		pjaxContainers.length ? pjaxContainers[0] : '#primary',
		{ fragment: (pjaxContainers.length ? pjaxContainers : ['#primary']), timeout: $.pjax.defaults.timeout }
	)
		.on('pjax:click', function (e, f, g) {
			NProgress.remove();
			NProgress.start();
			pjaxLoading = true;
		}).on('pjax:afterGetContainers', function (e, f, g) {
			pjaxScrollTop = 0;
			if ($("html").hasClass("banner-as-cover")) {
				if (g.is(".page-link")) {
					pjaxScrollTop = $("#content").offset().top - 80;
				}
			}
		}).on('pjax:send', function () {
			PageLoader.setProgress(62);
			NProgress.set(0.618);
		}).on('pjax:beforeReplace', function (e, dom) {
			// ========== 需求 1.1-1.4: 清理旧页面的所有资源 ==========
			// 调用统一的资源清理管理器
			// 清理内容：Lazyload Observer、Zoomify、Tippy 实例、动态标签
			PageLoader.setProgress(72);
			cleanupPjaxResources();

			// 更新 UI 状态
			if ($("#post_comment", dom[0]).length > 0) {
				$("#fabtn_go_to_comment").removeClass("d-none");
			} else {
				$("#fabtn_go_to_comment").addClass("d-none");
			}

			// 处理滚动位置
			if ($("html").hasClass("banner-as-cover")) {
				if (!$("#main").hasClass("article-list-home")) {
					pjaxScrollTop = 0;
				}
			}
		}).on('pjax:complete', function () {
			// ========== 需求 1.5, 1.6: 重新初始化所有功能模块 ==========
			pjaxLoading = false;
			PageLoader.trackResources(document);
			PageLoader.setProgress(82);
			NProgress.inc();

			// ========== 需求 4.1-4.5: 执行新页面中的内联脚本 ==========
			try {
				pjaxContainers.forEach(function (selector) {
					var container = document.querySelector(selector);
					if (container) {
						executeInlineScripts(container);
					}
				});
			} catch (err) {
				ArgonDebug.error('executeInlineScripts failed:', err);
			}

			// MathJax 数学公式渲染
			try {
				if (MathJax != undefined) {
					if (MathJax.Hub != undefined) {
						MathJax.Hub.Typeset();
					} else {
						MathJax.typeset();
					}
				}
			} catch (err) { }

			// KaTeX 数学公式渲染
			try {
				if (renderMathInElement != undefined) {
					renderMathInElement(document.body, {
						delimiters: [
							{ left: "$$", right: "$$", display: true },
							{ left: "$", right: "$", display: false },
							{ left: "\\(", right: "\\)", display: false }
						]
					});
				}
			} catch (err) { }

			// 初始化各个功能模块（添加错误处理确保单个模块失败不影响其他模块）
			try { initArgonLongContentVirtualizer(); } catch (err) { ArgonDebug.error('initArgonLongContentVirtualizer failed:', err); }
			try { waterflowInit(); } catch (err) { ArgonDebug.error('waterflowInit failed:', err); }
			try { lazyloadInit(); } catch (err) { ArgonDebug.error('lazyloadInit failed:', err); }
			try { zoomifyInit(); } catch (err) { ArgonDebug.error('zoomifyInit failed:', err); }
			try { highlightJsRender(); } catch (err) { ArgonDebug.error('highlightJsRender failed:', err); }
			try { panguInit(); } catch (err) { ArgonDebug.error('panguInit failed:', err); }
			try { clampInit(); } catch (err) { ArgonDebug.error('clampInit failed:', err); }
			try { tippyInit(); } catch (err) { ArgonDebug.error('tippyInit failed:', err); }
			try { getGithubInfoCardContent(); } catch (err) { ArgonDebug.error('getGithubInfoCardContent failed:', err); }
			try { showPostOutdateToast(); } catch (err) { ArgonDebug.error('showPostOutdateToast failed:', err); }
			try { calcHumanTimesOnPage(); } catch (err) { ArgonDebug.error('calcHumanTimesOnPage failed:', err); }
			try { foldLongComments(); } catch (err) { ArgonDebug.error('foldLongComments failed:', err); }
			try { foldLongShuoshuo(); } catch (err) { ArgonDebug.error('foldLongShuoshuo failed:', err); }
			try { handleHashNavigation(); } catch (err) { ArgonDebug.error('handleHashNavigation failed:', err); }
			try { initArticleSkeletons(); } catch (err) { ArgonDebug.error('initArticleSkeletons failed:', err); }

			$("html").trigger("resize");

			// 恢复滚动位置
			if (pjaxScrollTop > 0) {
				$("body,html").scrollTop(pjaxScrollTop);
				pjaxScrollTop = 0;
			}

			// 调用用户自定义的 PJAX 加载完成回调
			if (typeof (window.pjaxLoaded) == "function") {
				try {
					window.pjaxLoaded();
				} catch (err) {
					ArgonDebug.error(err);
				}
			}

			NProgress.done();
			PageLoader.hide();
		}).on('pjax:error', function () {
			endPageTransition();
			pjaxLoading = false;
			PageLoader.hide();
		}).on('pjax:end', function () {
			// ========== 需求 1.7: 执行特定任务 ==========

			// 重置移动端目录状态
			if (typeof window.resetMobileCatalog === 'function') {
				try {
					window.resetMobileCatalog();
				} catch (err) {
					ArgonDebug.warn('resetMobileCatalog failed:', err);
				}
			}

			// GT4: PJAX 后确保评论页验证码已初始化
			resetGT4Captcha();
		});
}

window.addEventListener('hashchange', function () {
	handleHashNavigation();
});
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function () {
		handleHashNavigation();
	});
} else {
	handleHashNavigation();
}

/*Reference 跳转*/
$(document).on("click", ".reference-link , .reference-list-backlink", function (e) {
	e.preventDefault();
	var href = $(this).attr("href");
	if (!href || href === "#") { return; }
	if (window.ArgonLongContentVirtualizer && typeof window.ArgonLongContentVirtualizer.renderTarget === "function") {
		window.ArgonLongContentVirtualizer.renderTarget(href.slice(1));
	}
	var $target;
	try {
		$target = $(href);
	} catch (err) {
		return;
	}
	if (!$target.length) { return; }
	$("body,html").animate({
		scrollTop: $target.offset().top - document.body.clientHeight / 2 - 75
	}, 500, 'easeOutExpo')
	setTimeout(function () {
		if ($target.is("li")) {
			$(".space", $target).focus();
		} else {
			$target.focus();
		}
	}, 1);
});

/*Tags Dialog pjax 加载后自动关闭 */
$(document).on("click", "#blog_tags .tag", function () {
	$("#blog_tags button.close").trigger("click");
});
$(document).on("click", "#blog_categories .tag", function () {
	$("#blog_categories button.close").trigger("click");
});

/*Close RSS dropdown on outside click*/
function setSiteRssOpen(details, open) {
	if (!details) {
		return;
	}
	var scheduleFrame = window.requestAnimationFrame || function (callback) {
		return setTimeout(callback, 16);
	};
	if (open) {
		if (details._argonRssTimer) {
			clearTimeout(details._argonRssTimer);
			details._argonRssTimer = null;
		}
		details.setAttribute("open", "");
		details.classList.remove("site-rss-closing");
		details.classList.add("site-rss-opening");
		scheduleFrame(function () {
			details.classList.add("site-rss-opened");
			details.classList.remove("site-rss-opening");
		});
		return;
	}
	if (!details.hasAttribute("open")) {
		return;
	}
	details.classList.remove("site-rss-opening", "site-rss-opened");
	details.classList.add("site-rss-closing");
	if (details._argonRssTimer) {
		clearTimeout(details._argonRssTimer);
	}
	details._argonRssTimer = setTimeout(function () {
		details.removeAttribute("open");
		details.classList.remove("site-rss-closing");
		details._argonRssTimer = null;
	}, 220);
}

$(document).on("click", function (e) {
	var target = e.target;
	$(".site-rss[open]").each(function () {
		if (!this.contains(target)) {
			setSiteRssOpen(this, false);
		}
	});
});

$(document).on("click", ".site-rss .site-rss-btn", function (e) {
	e.preventDefault();
	var currentDetails = $(this).closest(".site-rss")[0];
	var shouldOpen = !(currentDetails && currentDetails.hasAttribute("open") && !currentDetails.classList.contains("site-rss-closing"));
	$(".site-rss[open]").each(function () {
		if (this !== currentDetails) {
			setSiteRssOpen(this, false);
		}
	});
	setSiteRssOpen(currentDetails, shouldOpen);
});

$(document).on("keydown", function (e) {
	if (e.key === "Escape") {
		$(".site-rss[open]").each(function () {
			setSiteRssOpen(this, false);
		});
	}
});

/*Click ripple*/
!function () {
	var rippleSelector = [
		".btn-ripple",
		".leftbar-mobile-menu-item > a",
		".leftbar-mobile-action",
		".leftbar-mobile-collapse-header",
		".leftbar-mobile-close",
		"#leftbar_mobile_catalog .index-link",
		".mobile-todo-add-form button",
		".mobile-todo-complete-btn",
		".mobile-todo-urge-btn",
		".mobile-todo-delete-btn",
		".mobile-todo-collapse-btn",
		".mobile-todo-completed-divider"
	].join(", ");
	var eventName = window.PointerEvent ? "pointerdown.argonRipple" : "touchstart.argonRipple mousedown.argonRipple";
	var lastTouchTime = 0;

	function getRipplePoint(event) {
		var originalEvent = event.originalEvent || event;
		var point = originalEvent;
		if (originalEvent.touches && originalEvent.touches.length > 0) {
			point = originalEvent.touches[0];
			lastTouchTime = Date.now();
		} else if (originalEvent.changedTouches && originalEvent.changedTouches.length > 0) {
			point = originalEvent.changedTouches[0];
			lastTouchTime = Date.now();
		} else if (event.type === "mousedown" && Date.now() - lastTouchTime < 450) {
			return null;
		}
		if (typeof point.clientX !== "number" || typeof point.clientY !== "number") {
			return null;
		}
		return {
			x: point.clientX,
			y: point.clientY
		};
	}

	function addClickRipple(event) {
		if (event.button && event.button !== 0) {
			return;
		}
		var target = $(event.target).closest(rippleSelector)[0];
		if (!target || target.disabled || target.getAttribute("aria-disabled") === "true" || target.classList.contains("disabled")) {
			return;
		}
		if (!target.classList.contains("btn-ripple") && window.matchMedia && !window.matchMedia("(max-width: 900px)").matches) {
			return;
		}
		var point = getRipplePoint(event);
		if (!point) {
			return;
		}
		var rect = target.getBoundingClientRect();
		if (rect.width <= 0 || rect.height <= 0) {
			return;
		}
		var x = point.x - rect.left;
		var y = point.y - rect.top;
		var maxX = Math.max(x, rect.width - x);
		var maxY = Math.max(y, rect.height - y);
		var diameter = Math.ceil(Math.sqrt(maxX * maxX + maxY * maxY) * 2);
		var ripple = document.createElement("span");
		ripple.className = "argon-click-ripple";
		ripple.style.width = diameter + "px";
		ripple.style.height = diameter + "px";
		ripple.style.left = x + "px";
		ripple.style.top = y + "px";
		target.classList.add("argon-ripple-target");
		if (window.getComputedStyle && window.getComputedStyle(target).position === "static") {
			target.style.position = "relative";
		}
		target.appendChild(ripple);
		setTimeout(function () {
			if (ripple.parentNode) {
				ripple.parentNode.removeChild(ripple);
			}
		}, 560);
	}

	$(document).on(eventName, addClickRipple);
}();

/*侧栏 & 顶栏菜单手机适配*/
!function () {
	$(document).on("click", "#fabtn_open_sidebar, #open_sidebar", function () {
		$("html").addClass("leftbar-opened");
		// 侧边栏打开时初始化移动端文章目录
		initMobileCatalog();
		// 如果有文章目录，默认展开
		setTimeout(function () {
			var catalogSection = $("#mobile_catalog_toggle").closest(".leftbar-mobile-collapse-section");
			if (catalogSection.length > 0 && !catalogSection.hasClass("expanded")) {
				catalogSection.addClass("expanded");
				initMobileCatalog();
				setTimeout(scrollMobileCatalogToActive, 200);
			}
		}, 100);
	});
	$(document).on("click", "#sidebar_mask, #leftbar_close_btn", function () {
		$("html").removeClass("leftbar-opened");
		// 关闭侧边栏时折叠所有面板
		$(".leftbar-mobile-collapse-section.expanded").removeClass("expanded");
	});
	$(document).on("click", "#leftbar a[href]:not([no-pjax]):not([href^='#']):not(.has-submenu)", function () {
		$("html").removeClass("leftbar-opened");
		// 关闭侧边栏时折叠所有面板
		$(".leftbar-mobile-collapse-section.expanded").removeClass("expanded");
	});
	// 移动端子菜单展开/收起
	$(document).on("click", ".leftbar-mobile-menu-item.has-children > a.has-submenu", function (e) {
		e.preventDefault();
		$(this).parent().toggleClass("expanded");
	});
	// 移动端侧边栏搜索
	$(document).on("keydown", "#leftbar_mobile_search_input", function (e) {
		if (e.keyCode != 13) {
			return;
		}
		let word = $(this).val();
		if (word == "") {
			return;
		}
		$("html").removeClass("leftbar-opened");
		// 关闭侧边栏时折叠所有面板
		$(".leftbar-mobile-collapse-section.expanded").removeClass("expanded");
		searchPosts(word);
	});
	$(document).on("click", "#navbar_global.show .navbar-nav a[href]:not([no-pjax]):not([href^='#'])", function () {
		$("#navbar_global .navbar-toggler").click();
	});
	$(document).on("click", "#navbar_global.show #navbar_search_btn_mobile", function () {
		$("#navbar_global .navbar-toggler").click();
	});

	// ========== 移动端折叠面板交互==========
	$(document).on("click", ".leftbar-mobile-collapse-header", function (e) {
		var section = $(this).closest(".leftbar-mobile-collapse-section");
		var header = $(this);
		var isExpanded = section.hasClass("expanded");

		// 切换展开状态
		section.toggleClass("expanded");

		// 如果是文章目录面板展开，初始化目录并滚动到当前位置
		if (!isExpanded && header.attr("id") === "mobile_catalog_toggle") {
			initMobileCatalog();
			setTimeout(scrollMobileCatalogToActive, 100);
		}
	});

	// ========== 移动端文章目录初始化 ==========
	window.mobileCatalogInitialized = false;

	function initMobileCatalog() {
		if (window.mobileCatalogInitialized) return;
		var $mobileContainer = $("#leftbar_mobile_catalog");
		var $postContent = $("#post_content");
		if ($mobileContainer.length === 0) return;
		if ($postContent.length === 0) return;

		// 直接生成目录，不依赖 headIndex 插件
		var headerItems = [];
		if (window.ArgonLongContentVirtualizer && window.ArgonLongContentVirtualizer.isReady && window.ArgonLongContentVirtualizer.isReady()) {
			headerItems = window.ArgonLongContentVirtualizer.getHeaders();
		} else {
			var $headers = $postContent.find('h1, h2, h3, h4, h5, h6');
			$headers.each(function (index) {
				var $h = $(this);
				var id = $h.attr('id');
				if (!id) {
					id = 'mobile-heading-' + index;
					$h.attr('id', id);
				}
				headerItems.push({
					id: id,
					text: $h.text().trim(),
					level: parseInt(this.tagName.charAt(1), 10)
				});
			});
		}
		if (headerItems.length === 0) {
			$mobileContainer.html('<div class="no-catalog">暂无目录</div>');
			return;
		}

		// 构建目录树
		var toc = [];
		var stack = [{ level: 0, children: toc }];

		for (var headerIndex = 0; headerIndex < headerItems.length; headerIndex++) {
			var heading = headerItems[headerIndex];
			var level = parseInt(heading.level, 10);
			var item = { id: heading.id, text: heading.text, level: level, children: [] };

			// 找到合适的父级
			while (stack.length > 1 && stack[stack.length - 1].level >= level) {
				stack.pop();
			}
			stack[stack.length - 1].children.push(item);
			stack.push({ level: level, children: item.children });
		}

		// 递归生成 HTML
		function buildHtml(items, isRoot) {
			if (!items || items.length === 0) return '';
			var html = isRoot ? '<ul>' : '<ul class="index-subItem-box">';
			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				html += '<li class="index-item">';
				html += '<a href="#' + item.id + '" class="index-link" data-target="' + item.id + '">' + item.text + '</a>';
				if (item.children.length > 0) {
					html += buildHtml(item.children, false);
				}
				html += '</li>';
			}
			html += '</ul>';
			return html;
		}

		$mobileContainer.html(buildHtml(toc, true));
		window.mobileCatalogInitialized = true;

		// 绑定点击事件
		$mobileContainer.off('click.mobileCatalog').on('click.mobileCatalog', '.index-link', function (e) {
			e.preventDefault();
			var targetId = $(this).attr('href');
			if (targetId && targetId !== "#") {
				if (window.ArgonLongContentVirtualizer && typeof window.ArgonLongContentVirtualizer.renderTarget === "function") {
					window.ArgonLongContentVirtualizer.renderTarget(targetId.slice(1));
				}
				var $target;
				try {
					$target = $(targetId);
				} catch (err) {
					return;
				}
				if ($target.length) {
					$mobileContainer.find('.index-item').removeClass('current');
					$(this).closest('.index-item').addClass('current');
					$('html, body').animate({
						scrollTop: $target.offset().top - 80
					}, 500, 'easeOutExpo');
					// 点击后关闭侧边栏
					setTimeout(function () {
						$("html").removeClass("leftbar-opened");
						// 关闭侧边栏时折叠所有面板
						$(".leftbar-mobile-collapse-section.expanded").removeClass("expanded");
					}, 150);
				}
			}
		});

		// 初始化高亮并启动滚动监听
		updateMobileCatalogHighlight();
		setTimeout(scrollMobileCatalogToActive, 150);
	}

	// 更新移动端目录高亮
	function updateMobileCatalogHighlight() {
		var $mobileContainer = $("#leftbar_mobile_catalog");
		var $postContent = $("#post_content");
		if ($mobileContainer.length === 0 || $postContent.length === 0) return;

		var scrollTop = $(window).scrollTop();
		var virtualizer = window.ArgonLongContentVirtualizer;
		var headers = [];
		var currentId = null;

		if (virtualizer && virtualizer.isReady && virtualizer.isReady()) {
			headers = virtualizer.getHeaders();
			for (var i = 0; i < headers.length; i++) {
				var top = virtualizer.getHeaderOffset(headers[i], 100);
				if (scrollTop >= top) {
					currentId = headers[i].id;
				}
			}
		} else {
			$postContent.find('h1, h2, h3, h4, h5, h6').each(function () {
				var $h = $(this);
				var top = $h.offset().top - 100;
			if (scrollTop >= top) {
				currentId = $h.attr('id');
			}
			});
		}

		if (currentId) {
			$mobileContainer.find('.index-item').removeClass('current');
			$mobileContainer.find('.index-link[href="#' + currentId + '"]').closest('.index-item').addClass('current');
		}
	}

	// 重置移动端目录状态（由PJAX 调用）
	window.resetMobileCatalog = function () {
		window.mobileCatalogInitialized = false;
		$("#leftbar_mobile_catalog").empty();
	};

	// 滚动目录到当前激活项
	function scrollMobileCatalogToActive() {
		var container = $("#leftbar_mobile_catalog");
		var activeItem = container.find(".index-item.current");
		if (activeItem.length > 0 && container.length > 0) {
			var containerHeight = container.height();
			var itemTop = activeItem.position().top;
			var itemHeight = activeItem.outerHeight();
			var scrollTop = container.scrollTop();
			var targetScroll = scrollTop + itemTop - (containerHeight / 2) + (itemHeight / 2);
			container.stop().animate({
				scrollTop: Math.max(0, targetScroll)
			}, 300, 'easeOutCubic');
		}
	}

	// 监听页面滚动，实时更新移动端目录高亮并自动滚动
	var mobileCatalogScrollTicking = false;
	$(window).on("scroll.mobileCatalog", function () {
		if (!window.mobileCatalogInitialized) return;
		// 使用 rAF 实现实时更新，无延迟
		if (mobileCatalogScrollTicking) return;
		mobileCatalogScrollTicking = true;
		requestAnimationFrame(function () {
			mobileCatalogScrollTicking = false;
			// 更新高亮状态
			updateMobileCatalogHighlight();
			// 只在侧边栏打开且目录展开时滚动
			if ($("html").hasClass("leftbar-opened") &&
				$("#mobile_catalog_toggle").closest(".leftbar-mobile-collapse-section").hasClass("expanded")) {
				scrollMobileCatalogToActive();
			}
		});
	});

	// 点击目录项后关闭侧边栏（已在 initMobileCatalog 中处理）

	// ========== 移动端TODO交互 ==========
	function updateMobileTodoCount() {
		var count = $("#mobile-todo-list .mobile-todo-item:not(.todo-completed)").length;
		var completedCount = $("#mobile-todo-list .mobile-todo-item.todo-completed").length;
		$("#mobile_todo_count").text(count);
		// 同步更新桌面端计数
		$(".todo-count").text(count);

		// 更新分隔栏的已完成数量
		var divider = $("#mobile-todo-collapse-btn");
		if (divider.length) {
			divider.find(".mobile-todo-completed-count").text(completedCount);
			if (completedCount > 0) {
				divider.show();
			} else {
				divider.hide();
			}
		}
	}

	// 移动端折叠/展开已完成任务
	var mobileTodoListNode = document.getElementById("mobile-todo-list");
	var mobileTodoFrame = 0;
	var mobileTodoState = collectMobileTodoState();
	var mobileTodoEmptyText = (function () {
		var empty = mobileTodoListNode ? mobileTodoListNode.querySelector(".mobile-todo-empty") : null;
		return empty ? empty.textContent : "暂无待办事项";
	})();
	var scheduleMobileTodoFrame = window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : function (callback) {
		return window.setTimeout(callback, 16);
	};

	function collectMobileTodoState() {
		var state = { active: 0, completed: 0, total: 0 };
		if (!mobileTodoListNode) return state;

		var children = mobileTodoListNode.children;
		for (var i = 0; i < children.length; i++) {
			var child = children[i];
			if (!child.classList || !child.classList.contains("mobile-todo-item")) continue;

			state.total++;
			if (child.classList.contains("todo-completed")) {
				state.completed++;
			} else {
				state.active++;
			}
		}
		return state;
	}

	function renderMobileTodoMeta() {
		$("#mobile_todo_count").text(mobileTodoState.active);
		$(".todo-count").text(mobileTodoState.active);

		var divider = $("#mobile-todo-collapse-btn");
		if (divider.length) {
			divider.find(".mobile-todo-completed-count").text(mobileTodoState.completed);
			divider.toggle(mobileTodoState.completed > 0);
		}

		if (!mobileTodoListNode) return;
		var empty = mobileTodoListNode.querySelector(".mobile-todo-empty");
		if (mobileTodoState.total === 0 && !empty) {
			var emptyItem = document.createElement("li");
			emptyItem.className = "mobile-todo-empty";
			emptyItem.textContent = mobileTodoEmptyText;
			mobileTodoListNode.appendChild(emptyItem);
		} else if (mobileTodoState.total > 0 && empty) {
			empty.remove();
		}
	}

	updateMobileTodoCount = function (delta) {
		if (delta) {
			mobileTodoState.active = Math.max(0, mobileTodoState.active + (delta.active || 0));
			mobileTodoState.completed = Math.max(0, mobileTodoState.completed + (delta.completed || 0));
			mobileTodoState.total = Math.max(0, mobileTodoState.total + (delta.total || 0));
		} else {
			mobileTodoState = collectMobileTodoState();
		}

		if (mobileTodoFrame) return;
		mobileTodoFrame = scheduleMobileTodoFrame(function () {
			mobileTodoFrame = 0;
			renderMobileTodoMeta();
		});
	};

	$(document).on("click", "#mobile-todo-collapse-btn", function (e) {
		e.stopPropagation();
		var btn = $(this);
		var completedItems = $("#mobile-todo-list .mobile-todo-item.todo-completed");
		var isCollapsed = btn.hasClass("collapsed");

		if (isCollapsed) {
			// 展开
			btn.removeClass("collapsed");
			completedItems.removeClass("collapsed");
		} else {
			// 折叠
			btn.addClass("collapsed");
			completedItems.addClass("collapsed");
		}
	});

	// 添加TODO
	$(document).on("click", "#mobile-todo-add-btn", function () {
		addMobileTodo();
	});

	$(document).on("keypress", "#mobile-todo-input", function (e) {
		if (e.key === "Enter") {
			addMobileTodo();
		}
	});

	function addMobileTodo() {
		if (!window.mobileTodoConfig) return;

		var input = $("#mobile-todo-input");
		var content = input.val().trim();
		if (!content) return;

		var btn = $("#mobile-todo-add-btn");
		btn.prop("disabled", true);

		$.ajax({
			url: window.mobileTodoConfig.ajaxUrl,
			type: "POST",
			data: {
				action: "argon_add_todo",
				nonce: window.mobileTodoConfig.nonce,
				content: content
			},
			success: function (res) {
				if (res.success) {
					var list = $("#mobile-todo-list");
					list.find(".mobile-todo-empty").remove();

					var newItem = $('<li class="mobile-todo-item" data-id="' + res.data.id + '">' +
						'<span class="mobile-todo-content">' + $("<div>").text(content).html() + '</span>' +
						'<button class="mobile-todo-complete-btn" title="完成"><i class="fa fa-check"></i></button>' +
						'</li>');
					list.prepend(newItem);

					input.val("");
					updateMobileTodoCount({ active: 1, total: 1 });

					// 同步到桌面端
					syncTodoToDesktop(res.data.id, content, "add");
				}
				btn.prop("disabled", false);
			},
			error: function () {
				btn.prop("disabled", false);
			}
		});
	}

	// 移动端TODO 验证码相关变量
	var mobilePendingUrgeBtn = null;
	var mobileGeetestCaptchaObj = null;

	// 完成/删除/催促TODO
	$(document).on("click", "#mobile-todo-list .mobile-todo-complete-btn, #mobile-todo-list .mobile-todo-delete-btn, #mobile-todo-list .mobile-todo-urge-btn", function () {
		if (!window.mobileTodoConfig) return;

		var btn = $(this);
		var item = btn.closest(".mobile-todo-item");
		var id = item.data("id");

		if (btn.hasClass("mobile-todo-complete-btn")) {
			btn.prop("disabled", true);
			$.ajax({
				url: window.mobileTodoConfig.ajaxUrl,
				type: "POST",
				data: {
					action: "argon_complete_todo",
					nonce: window.mobileTodoConfig.nonce,
					id: id
				},
				success: function (res) {
					if (res.success) {
						item.addClass("todo-completed");
						btn.replaceWith('<button class="mobile-todo-delete-btn" title="删除"><i class="fa fa-trash"></i></button>');
						updateMobileTodoCount({ active: -1, completed: 1 });
						syncTodoToDesktop(id, "", "complete");
					} else {
						btn.prop("disabled", false);
					}
				}
			});
		} else if (btn.hasClass("mobile-todo-delete-btn")) {
			btn.prop("disabled", true);
			$.ajax({
				url: window.mobileTodoConfig.ajaxUrl,
				type: "POST",
				data: {
					action: "argon_delete_todo",
					nonce: window.mobileTodoConfig.nonce,
					id: id
				},
				success: function (res) {
					if (res.success) {
						item.addClass("todo-completing");
						setTimeout(function () {
							item.remove();
							updateMobileTodoCount({ completed: -1, total: -1 });
						}, 350);
						syncTodoToDesktop(id, "", "delete");
					}
				}
			});
		} else if (btn.hasClass("mobile-todo-urge-btn") && !btn.hasClass("urged")) {
			// 检查是否需要验证码
			if (window.mobileTodoConfig.needCaptcha) {
				var captchaContainer = $(".mobile-todo-captcha-container");
				if (captchaContainer.length > 0) {
					captchaContainer.stop(true, true).show();
					mobilePendingUrgeBtn = btn;

					if (window.mobileTodoConfig.captchaType === 'geetest') {
						// 极验验证码
						if (!mobileGeetestCaptchaObj && typeof initGeetest4 === 'function') {
							initGeetest4({
								captchaId: window.mobileTodoConfig.geetestId,
								product: 'bind'
							}, function (captcha) {
								mobileGeetestCaptchaObj = captcha;
								captcha.onReady(function () {
									captcha.showCaptcha();
								}).onSuccess(function () {
									var result = captcha.getValidate();
									if (result && mobilePendingUrgeBtn) {
										var urgeId = mobilePendingUrgeBtn.closest(".mobile-todo-item").data("id");
										doMobileUrgeGeetest(mobilePendingUrgeBtn, urgeId, result);
									}
								}).onError(function () {
									captchaContainer.stop(true, true).hide();
									mobilePendingUrgeBtn = null;
								});
							});
						} else if (mobileGeetestCaptchaObj) {
							mobileGeetestCaptchaObj.showCaptcha();
						}
					} else {
						// 数学验证码
						$("#mobile-todo-captcha-input").val("").focus();
					}
					return;
				}
			}

			// 不需要验证码，直接催促
			doMobileUrge(btn, id, "");
		}
	});

	// 移动端数学验证码提交
	$(document).on("click", "#mobile-todo-captcha-submit", function () {
		if (!mobilePendingUrgeBtn) return;

		var captchaInput = $("#mobile-todo-captcha-input");
		var captchaValue = captchaInput.val().trim();

		if (!captchaValue) {
			captchaInput.focus();
			return;
		}

		var id = mobilePendingUrgeBtn.closest(".mobile-todo-item").data("id");
		doMobileUrge(mobilePendingUrgeBtn, id, captchaValue);
	});

	// 移动端验证码回车提交
	$(document).on("keypress", "#mobile-todo-captcha-input", function (e) {
		if (e.key === "Enter") {
			$("#mobile-todo-captcha-submit").click();
		}
	});

	// 执行移动端催促
	function doMobileUrge(btn, id, captcha) {
		btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>');

		$.ajax({
			url: window.mobileTodoConfig.ajaxUrl,
			type: "POST",
			data: {
				action: "argon_urge_todo",
				nonce: window.mobileTodoConfig.nonce,
				id: id,
				comment_captcha: captcha
			},
			success: function (res) {
				handleMobileUrgeResponse(btn, res);
			},
			error: function () {
				btn.prop("disabled", false).html('<i class="fa fa-bell"></i>');
			}
		});
	}

	// 极验验证码催促
	function doMobileUrgeGeetest(btn, id, geetestResult) {
		btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>');

		$.ajax({
			url: window.mobileTodoConfig.ajaxUrl,
			type: "POST",
			data: {
				action: "argon_urge_todo",
				nonce: window.mobileTodoConfig.nonce,
				id: id,
				lot_number: geetestResult.lot_number,
				captcha_output: geetestResult.captcha_output,
				pass_token: geetestResult.pass_token,
				gen_time: geetestResult.gen_time
			},
			success: function (res) {
				handleMobileUrgeResponse(btn, res);
				if (mobileGeetestCaptchaObj) mobileGeetestCaptchaObj.reset();
			},
			error: function () {
				btn.prop("disabled", false).html('<i class="fa fa-bell"></i>');
			}
		});
	}

	// 处理移动端催促响应
	function handleMobileUrgeResponse(btn, res) {
		var captchaContainer = $(".mobile-todo-captcha-container");

		if (res.success) {
			btn.addClass("urged").html('<i class="fa fa-check"></i>');
			captchaContainer.stop(true, true).hide();
			mobilePendingUrgeBtn = null;

			// 更新验证码文本
			if (res.data && res.data.captcha) {
				$(".mobile-todo-captcha-text").text(res.data.captcha);
			}

			// 同步到桌面端
			var id = btn.closest(".mobile-todo-item").data("id");
			syncTodoToDesktop(id, "", "urge");

			// 显示成功提示
			if (typeof iziToast !== 'undefined') {
				iziToast.success({
					title: '',
					message: res.data && res.data.message ? res.data.message : '已提交',
					position: 'topRight',
					timeout: 3000
				});
			}
		} else {
			btn.prop("disabled", false).html('<i class="fa fa-bell"></i>');

			// 显示错误提示
			if (typeof iziToast !== 'undefined') {
				iziToast.error({
					title: '',
					message: res.data || '操作失败',
					position: 'topRight',
					timeout: 3000
				});
			}

			// 刷新验证码
			if (res.data && res.data.captcha) {
				$(".mobile-todo-captcha-text").text(res.data.captcha);
			}
			$("#mobile-todo-captcha-input").val("").focus();
		}
	}

	// 同步TODO操作到桌面端
	function syncTodoToDesktop(id, content, action) {
		var desktopList = $("#todo-list");
		if (desktopList.length === 0) return;

		if (action === "add") {
			desktopList.find(".todo-empty").remove();
			var newItem = $('<li class="todo-item" data-id="' + id + '">' +
				'<span class="todo-content">' + $("<div>").text(content).html() + '</span>' +
				'<button class="todo-complete-btn" title="完成"><i class="fa fa-check"></i></button>' +
				'</li>');
			desktopList.prepend(newItem);
			if (window.argonTodoRefresh) window.argonTodoRefresh({ active: 1, total: 1 });
		} else if (action === "complete") {
			var item = desktopList.find('.todo-item[data-id="' + id + '"]');
			item.addClass("todo-completed");
			if (window.argonTodoRefresh) window.argonTodoRefresh({ active: -1, completed: 1 });
			item.find(".todo-complete-btn").replaceWith('<button class="todo-delete-btn" title="删除"><i class="fa fa-trash"></i></button>');
		} else if (action === "delete") {
			desktopList.find('.todo-item[data-id="' + id + '"]').remove();
			if (window.argonTodoRefresh) window.argonTodoRefresh({ completed: -1, total: -1 });
		} else if (action === "urge") {
			var urgeBtn = desktopList.find('.todo-item[data-id="' + id + '"] .todo-urge-btn');
			urgeBtn.addClass("urged").prop("disabled", true).html('<i class="fa fa-check"></i>');
		}
	}
}();

/*折叠区块小工具*/
$(document).on("click", ".collapse-block .collapse-block-title", function () {
	let block = $(this).parent();
	$(block).toggleClass("collapsed");
	let inner = $(".collapse-block-body", block);
	if (block.hasClass("collapsed")) {
		inner.stop(true, false).slideUp(250, 'easeOutCirc');
	} else {
		inner.stop(true, false).slideDown(300, 'easeOutCirc');
	}
	$("html").trigger("scroll");
});

/*获得 Github Repo Shortcode 信息卡内容*/
function getGithubInfoCardContent() {
	$(".github-info-card").each(function () {
		(function ($this) {
			if ($this.attr("data-getdata") == "backend") {
				$(".github-info-card-description", $this).html($this.attr("data-description"));
				$(".github-info-card-stars", $this).html($this.attr("data-stars"));
				$(".github-info-card-forks", $this).html($this.attr("data-forks"));
				return;
			}
			$(".github-info-card-description", $this).html("Loading...");
			$(".github-info-card-stars", $this).html("-");
			$(".github-info-card-forks", $this).html("-");
			author = $this.attr("data-author");
			project = $this.attr("data-project");
			$.ajax({
				url: "https://api.github.com/repos/" + author + "/" + project,
				type: "GET",
				dataType: "json",
				success: function (result) {
					description = result.description;
					if (result.homepage != "" && result.homepage != null) {
						description += " <a href='" + result.homepage + "' target='_blank' no-pjax>" + result.homepage + "</a>"
					}
					$(".github-info-card-description", $this).html(description);
					$(".github-info-card-stars", $this).html(result.stargazers_count);
					$(".github-info-card-forks", $this).html(result.forks_count);
				},
				error: function (xhr) {
					if (xhr.status == 404) {
						$(".github-info-card-description", $this).html(__("找不到该 Repo"));
					} else {
						$(".github-info-card-description", $this).html(__("获取 Repo 信息失败"));
					}
				}
			});
		})($(this));
	});
}
getGithubInfoCardContent();

/*说说点赞*/
$(document).on("click", ".shuoshuo-upvote", function () {
	$this = $(this);
	ID = $this.attr("data-id");
	$this.addClass("shuoshuo-upvoting");
	$.ajax({
		url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
		type: "POST",
		dataType: "json",
		data: {
			action: "upvote_shuoshuo",
			shuoshuo_id: ID,
		},
		success: function (result) {
			$this.removeClass("shuoshuo-upvoting");
			if (result.status == "success") {
				$(".shuoshuo-upvote-num", $this).html(result.total_upvote);
				$("i.fa-thumbs-o-up", $this).addClass("fa-thumbs-up").removeClass("fa-thumbs-o-up");
				$this.addClass("upvoted");
				$this.addClass("shuoshuo-upvoted-animation");
				iziToast.show({
					title: result.msg,
					class: 'shadow-sm',
					position: 'topRight',
					backgroundColor: '#2dce89',
					titleColor: '#ffffff',
					messageColor: '#ffffff',
					iconColor: '#ffffff',
					progressBarColor: '#ffffff',
					icon: 'fa fa-check',
					timeout: 5000
				});
			} else {
				$(".shuoshuo-upvote-num", $this).html(result.total_upvote);
				iziToast.show({
					title: result.msg,
					class: 'shadow-sm',
					position: 'topRight',
					backgroundColor: '#f5365c',
					titleColor: '#ffffff',
					messageColor: '#ffffff',
					iconColor: '#ffffff',
					progressBarColor: '#ffffff',
					icon: 'fa fa-close',
					timeout: 5000
				});
			}
		},
		error: function (xhr) {
			$this.removeClass("shuoshuo-upvoting");
			iziToast.show({
				title: __("点赞失败"),
				class: 'shadow-sm',
				position: 'topRight',
				backgroundColor: '#f5365c',
				titleColor: '#ffffff',
				messageColor: '#ffffff',
				iconColor: '#ffffff',
				progressBarColor: '#ffffff',
				icon: 'fa fa-close',
				timeout: 5000
			});
		}
	});
});
//折叠长说说
function foldLongShuoshuo() {
	if (argonConfig.fold_long_shuoshuo == false) {
		return;
	}
	$("#main .shuoshuo-foldable > .shuoshuo-content").each(function () {
		if ($(this).hasClass("shuoshuo-unfolded")) {
			return;
		}
		if (this.clientHeight > 400) {
			$(this).addClass("shuoshuo-folded");
			$(this).append("<div class='show-full-shuoshuo'><button class='btn btn-outline-primary'><i class='fa fa-angle-down' aria-hidden='true'></i> " + __("展开") + "</button></div>");
		}
	});
}
foldLongShuoshuo();
$(document).on("click", ".show-full-shuoshuo", function () {
	$(this).parent().removeClass("shuoshuo-folded").addClass("shuoshuo-unfolded");
});

//颜色计算
function rgb2hsl(R, G, B) {
	let r = R / 255;
	let g = G / 255;
	let b = B / 255;

	let var_Min = Math.min(r, g, b);
	let var_Max = Math.max(r, g, b);
	let del_Max = var_Max - var_Min;

	let H, S, L = (var_Max + var_Min) / 2;

	if (del_Max == 0) {
		H = 0;
		S = 0;
	} else {
		if (L < 0.5) {
			S = del_Max / (var_Max + var_Min);
		} else {
			S = del_Max / (2 - var_Max - var_Min);
		}

		del_R = (((var_Max - r) / 6) + (del_Max / 2)) / del_Max;
		del_G = (((var_Max - g) / 6) + (del_Max / 2)) / del_Max;
		del_B = (((var_Max - b) / 6) + (del_Max / 2)) / del_Max;

		if (r == var_Max) {
			H = del_B - del_G;
		}
		else if (g == var_Max) {
			H = (1 / 3) + del_R - del_B;
		}
		else if (b == var_Max) {
			H = (2 / 3) + del_G - del_R;
		}
		if (H < 0) H += 1;
		if (H > 1) H -= 1;
	}
	return {
		'h': H,//0~1
		's': S,
		'l': L,
		'H': Math.round(H * 360),//0~360
		'S': Math.round(S * 100),//0~100
		'L': Math.round(L * 100),//0~100
	};
}
function Hue_2_RGB(v1, v2, vH) {
	if (vH < 0) vH += 1;
	if (vH > 1) vH -= 1;
	if ((6 * vH) < 1) return (v1 + (v2 - v1) * 6 * vH);
	if ((2 * vH) < 1) return v2;
	if ((3 * vH) < 2) return (v1 + (v2 - v1) * ((2 / 3) - vH) * 6);
	return v1;
}
function hsl2rgb(h, s, l) {
	let r, g, b, var_1, var_2;
	if (s == 0) {
		r = l;
		g = l;
		b = l;
	}
	else {
		if (l < 0.5) {
			var_2 = l * (1 + s);
		}
		else {
			var_2 = (l + s) - (s * l);
		}
		var_1 = 2 * l - var_2;
		r = Hue_2_RGB(var_1, var_2, h + (1 / 3));
		g = Hue_2_RGB(var_1, var_2, h);
		b = Hue_2_RGB(var_1, var_2, h - (1 / 3));
	}
	return {
		'R': Math.round(r * 255),//0~255
		'G': Math.round(g * 255),
		'B': Math.round(b * 255),
		'r': r,//0~1
		'g': g,
		'b': b
	};
}
function rgb2hex(r, g, b) {
	let hex = new Array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	let rh, gh, bh;
	rh = "", gh = "", bh = "";
	while (rh.length < 2) {
		rh = hex[r % 16] + rh;
		r = Math.floor(r / 16);
	}
	while (gh.length < 2) {
		gh = hex[g % 16] + gh;
		g = Math.floor(g / 16);
	}
	while (bh.length < 2) {
		bh = hex[b % 16] + bh;
		b = Math.floor(b / 16);
	}
	return "#" + rh + gh + bh;
}
function hex2rgb(hex) {
	//hex: #XXXXXX
	let dec = {
		'0': 0, '1': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8, '9': 9, 'A': 10, 'B': 11, 'C': 12, 'D': 13, 'E': 14, 'F': 15
	};
	return {
		'R': (dec[hex.substr(1, 1)] * 16 + dec[hex.substr(2, 1)]),//0~255
		'G': (dec[hex.substr(3, 1)] * 16 + dec[hex.substr(4, 1)]),
		'B': (dec[hex.substr(5, 1)] * 16 + dec[hex.substr(6, 1)]),
		'r': (dec[hex.substr(1, 1)] * 16 + dec[hex.substr(2, 1)]) / 255,//0~1
		'g': (dec[hex.substr(3, 1)] * 16 + dec[hex.substr(4, 1)]) / 255,
		'b': (dec[hex.substr(5, 1)] * 16 + dec[hex.substr(6, 1)]) / 255
	};
}
function rgb2gray(R, G, B) {
	return Math.round(R * 0.299 + G * 0.587 + B * 0.114);
}
function hex2gray(hex) {
	let rgb_array = hex2rgb(hex);
	return hex2gray(rgb_array['R'], rgb_array['G'], rgb_array['B']);
}
function rgb2str(rgb) {
	return rgb['R'] + "," + rgb['G'] + "," + rgb['B'];
}
function hex2str(hex) {
	return rgb2str(hex2rgb(hex));
}
//颜色选择器& 切换主题色
if ($("meta[name='argon-enable-custom-theme-color']").attr("content") == 'true') {
	let themeColorPicker = new Pickr({
		el: '#theme-color-picker',
		container: 'body',
		theme: 'monolith',
		closeOnScroll: false,
		appClass: 'theme-color-picker-box',
		useAsButton: false,
		padding: 8,
		inline: false,
		autoReposition: true,
		sliders: 'h',
		disabled: false,
		lockOpacity: true,
		outputPrecision: 0,
		comparison: false,
		default: $("meta[name='theme-color']").attr("content"),
		swatches: ['#5e72e4', '#fa7298', '#009688', '#607d8b', '#2196f3', '#3f51b5', '#ff9700', '#109d58', '#dc4437', '#673bb7', '#212121', '#795547'],
		defaultRepresentation: 'HEX',
		showAlways: false,
		closeWithKey: 'Escape',
		position: 'top-start',
		adjustableNumbers: false,
		components: {
			palette: true,
			preview: true,
			opacity: false,
			hue: true,
			interaction: {
				hex: true,
				rgba: true,
				hsla: false,
				hsva: false,
				cmyk: false,
				input: true,
				clear: false,
				cancel: true,
				save: true
			}
		},
		strings: {
			save: __('确定'),
			clear: __('清除'),
			cancel: __('恢复博客默认')
		}
	});
	themeColorPicker.on('change', instance => {
		updateThemeColor(pickrObjectToHEX(instance), true);
	})
	themeColorPicker.on('save', (color, instance) => {
		updateThemeColor(pickrObjectToHEX(instance._color), true);
		themeColorPicker.hide();
	})
	themeColorPicker.on('cancel', instance => {
		themeColorPicker.hide();
		themeColorPicker.setColor($("meta[name='theme-color-origin']").attr("content").toUpperCase());
		updateThemeColor($("meta[name='theme-color-origin']").attr("content").toUpperCase(), false);
		setCookie("argon_custom_theme_color", "", 0);
	});
}
function pickrObjectToHEX(color) {
	let HEXA = color.toHEXA();
	return ("#" + HEXA[0] + HEXA[1] + HEXA[2]).toUpperCase();
}
function updateThemeColor(color, setcookie) {
	let themecolor = color;
	let themecolor_rgbstr = hex2str(themecolor);
	let RGB = hex2rgb(themecolor);
	let HSL = rgb2hsl(RGB['R'], RGB['G'], RGB['B']);

	document.documentElement.style.setProperty('--themecolor', themecolor);
	document.documentElement.style.setProperty('--themecolor-R', RGB['R']);
	document.documentElement.style.setProperty('--themecolor-G', RGB['G']);
	document.documentElement.style.setProperty('--themecolor-B', RGB['B']);
	document.documentElement.style.setProperty('--themecolor-H', HSL['H']);
	document.documentElement.style.setProperty('--themecolor-S', HSL['S']);
	document.documentElement.style.setProperty('--themecolor-L', HSL['L']);


	if (rgb2gray(RGB['R'], RGB['G'], RGB['B']) < 50) {
		$("html").addClass("themecolor-toodark");
	} else {
		$("html").removeClass("themecolor-toodark");
	}

	$("meta[name='theme-color']").attr("content", themecolor);
	$("meta[name='theme-color-rgb']").attr("content", themecolor_rgbstr);

	if (setcookie) {
		setCookie("argon_custom_theme_color", themecolor, 365);
	}
}

/*打字效果*/
function typeEffect($element, text, now, interval) {
	if (now > text.length) {
		setTimeout(function () {
			$element.removeClass("typing-effect");
		}, 1000);
		return;
	}
	$element[0].innerText = text.substring(0, now);
	setTimeout(function () { typeEffect($element, text, now + 1, interval) }, interval);
}
function startTypeEffect($element, text, interval) {
	$element.addClass("typing-effect");
	$element.attr("style", "--animation-cnt: " + Math.ceil(text.length * interval / 1000));
	typeEffect($element, text, 1, interval);
}
!function () {
	if ($(".banner-title").data("interval") != undefined) {
		let interval = $(".banner-title").data("interval");
		let $title = $(".banner-title-inner");
		let $subTitle = $(".banner-subtitle");
		startTypeEffect($title, $title.data("text"), interval);
		if (!$subTitle.length) {
			return;
		}
		setTimeout(function () { startTypeEffect($subTitle, $subTitle.data("text"), interval); }, Math.ceil($title.data("text").length * interval / 1000) * 1000);
	}
}();

/*一言*/
if ($(".hitokoto").length > 0) {
	$.ajax({
		type: 'GET',
		url: "https://v1.hitokoto.cn",
		success: function (result) {
			$(".hitokoto").text(result.hitokoto);
		},
		error: function (result) {
			$(".hitokoto").text(__("Hitokoto 获取失败"));
		}
	});
}

/*Highlight.js*/
function randomString(len) {
	len = len || 32;
	let chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	let res = "";
	for (let i = 0; i < len; i++) {
		res += chars.charAt(Math.floor(Math.random() * chars.length));
	}
	res[0] = chars.charAt(Math.floor(Math.random() * (chars.length - 10)));
	return res;
}
var codeOfBlocks = {};
function getCodeFromBlock(block) {
	if (codeOfBlocks[block.id] != undefined) {
		return codeOfBlocks[block.id];
	}
	let lines = $(".hljs-ln-code", block);
	let res = "";
	if (lines.length == 0) {
		let code = $("code", block).first();
		res = code.length ? code.text() : $(block).text();
		codeOfBlocks[block.id] = res;
		return res;
	}
	for (let i = 0; i < lines.length - 1; i++) {
		res += lines[i].innerText;
		res += "\n";
	}
	res += lines[lines.length - 1].innerText;
	codeOfBlocks[block.id] = res;
	return res;
}

function shouldSkipHighlightJsBlock(block) {
	let $block = $(block);
	let $pre = $block.is("pre") ? $block : $block.parent("pre");
	if ($pre.length == 0) {
		return false;
	}
	let className = (($pre.attr("class") || "") + " " + ($block.attr("class") || ""));
	return $block.is("[hljs-codeblock-inner]") ||
		$pre.hasClass("hljs-codeblock") ||
		$block.find("table.hljs-ln").length > 0 ||
		$pre.children(".hljs-control").length > 0 ||
		$pre.closest(".code-toolbar").length > 0 ||
		$pre.siblings(".toolbar").length > 0 ||
		$pre.find(".line-numbers-rows").length > 0 ||
		/\bline-numbers\b/.test(className);
}


function renderArgonCodeBlock(block) {
	if ($(block).hasClass("no-hljs")) {
		return;
	}
	if ($(block).attr("hljs-codeblock-inner") !== undefined || $(block).parent().hasClass("hljs-codeblock")) {
		return;
	}
	if (shouldSkipHighlightJsBlock(block)) {
		return;
	}
	$(block).parent().attr("id", randomString());
	hljs.highlightBlock(block);
	hljs.lineNumbersBlock(block, { singleLine: true });
	$(block).parent().addClass("hljs-codeblock");
	if (argonConfig.code_highlight.hide_linenumber) {
		$(block).parent().addClass("hljs-hide-linenumber");
	}
	if (argonConfig.code_highlight.break_line) {
		$(block).parent().addClass("hljs-break-line");
	}
	if (argonConfig.code_highlight.transparent_linenumber) {
		$(block).parent().addClass("hljs-transparent-linenumber");
	}
	$(block).attr("hljs-codeblock-inner", "");
	let copyBtnID = "copy_btn_" + randomString();
	$(block).parent().append(`<div class="hljs-control hljs hljs-title">
			<div class="hljs-control-btn hljs-control-toggle-linenumber" tooltip-hide-linenumber="` + __("隐藏行号") + `" tooltip-show-linenumber="` + __("显示行号") + `">
				<i class="fa fa-list"></i>
			</div>
			<div class="hljs-control-btn hljs-control-toggle-break-line" tooltip-enable-breakline="` + __("开启折行") + `" tooltip-disable-breakline="` + __("关闭折行") + `">
				<i class="fa fa-align-left"></i>
			</div>
			<div class="hljs-control-btn hljs-control-copy" id=` + copyBtnID + ` tooltip="` + __("复制") + `">
				<i class="fa fa-clipboard"></i>
			</div>
			<div class="hljs-control-btn hljs-control-fullscreen" tooltip-fullscreen="` + __("全屏") + `" tooltip-exit-fullscreen="` + __("退出全屏") + `">
				<i class="fa fa-arrows-alt"></i>
			</div>
		</div>`);
	let clipboard = new ClipboardJS("#" + copyBtnID, {
		text: function (trigger) {
			return getCodeFromBlock($(block).parent()[0]);
		}
	});
	var copyButton = document.getElementById(copyBtnID);
	if (copyButton) {
		copyButton._clipboard = clipboard;
	}
	clipboard.on('success', function (e) {
		iziToast.show({
			title: __("复制成功"),
			message: __("代码已复制到剪贴板"),
			class: 'shadow',
			position: 'topRight',
			backgroundColor: '#2dce89',
			titleColor: '#ffffff',
			messageColor: '#ffffff',
			iconColor: '#ffffff',
			progressBarColor: '#ffffff',
			icon: 'fa fa-check',
			timeout: 5000
		});
	});
	clipboard.on('error', function (e) {
		iziToast.show({
			title: __("复制失败"),
			message: __("请手动复制代码"),
			class: 'shadow',
			position: 'topRight',
			backgroundColor: '#f5365c',
			titleColor: '#ffffff',
			messageColor: '#ffffff',
			iconColor: '#ffffff',
			progressBarColor: '#ffffff',
			icon: 'fa fa-close',
			timeout: 5000
		});
	});
}

function highlightJsRender(scope) {
	if (typeof (hljs) == "undefined") {
		return;
	}
	if (typeof (argonConfig.code_highlight.enable) == "undefined") {
		return;
	}
	if (!argonConfig.code_highlight.enable) {
		return;
	}
	var $scope = scope ? $(scope) : $("article");
	$scope.find("pre.code").addBack("pre.code").each(function (index, block) {
		if ($(block).hasClass("no-hljs")) {
			return;
		}
		if (shouldSkipHighlightJsBlock(block) || $(block).children("code").length > 0) {
			return;
		}
		$(block).html("<code>" + $(block).html() + "</code>");
	});
	var blocks = $scope.find("pre > code").addBack("pre > code").filter(function () {
		return !$(this).hasClass("no-hljs");
	});
	if (!scope && window.ArgonLongContentVirtualizer && window.ArgonLongContentVirtualizer.isReady && window.ArgonLongContentVirtualizer.isReady() && "IntersectionObserver" in window) {
		if (!highlightObserver) {
			highlightObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						renderArgonCodeBlock(entry.target);
						highlightObserver.unobserve(entry.target);
					}
				});
			}, { rootMargin: "800px 0px 800px 0px", threshold: 0 });
		}
		blocks.each(function (index, block) {
			highlightObserver.observe(block);
		});
		return;
	}
	blocks.each(function (index, block) {
		renderArgonCodeBlock(block);
	});
}
$(document).ready(function () {
	// ==========================================================================
	// 初始化性能优化模块
	// ==========================================================================
	if (typeof initArgonPerformance === 'function') {
		// 调用性能优化模块的初始化函数
		initArgonPerformance();

		// 创建各个优化模块的实例
		try {
			// 1. 性能监控模块（最先初始化，用于监控其他模块）
			if (typeof ArgonPerformanceMonitor !== 'undefined') {
				argonPerformanceMonitor = new ArgonPerformanceMonitor();
				ArgonDebug.info('✓ 性能监控模块已初始化');
			}

			// 2. DOM 缓存模块
			if (typeof ArgonDOMCache !== 'undefined') {
				argonDOMCache = new ArgonDOMCache();
				// 设置性能监控器引用
				if (argonPerformanceMonitor) {
					argonDOMCache.setPerformanceMonitor(argonPerformanceMonitor);
				}
				// 初始化缓存
				argonDOMCache.init();
				ArgonDebug.info('✓ DOM 缓存模块已初始化');
			}

			// 3. 事件管理模块
			if (typeof ArgonEventManager !== 'undefined') {
				argonEventManager = new ArgonEventManager();
				ArgonDebug.info('✓ 事件管理模块已初始化');
			}

			// 4. 资源加载模块
			if (typeof ArgonResourceLoader !== 'undefined') {
				argonResourceLoader = new ArgonResourceLoader();
				ArgonDebug.info('✓ 资源加载模块已初始化');
			}

			// 5. 渲染优化模块
			if (typeof ArgonRenderOptimizer !== 'undefined') {
				argonRenderOptimizer = new ArgonRenderOptimizer();
				ArgonDebug.info('✓ 渲染优化模块已初始化');
			}

			// 6. 内存管理模块
			if (typeof ArgonMemoryManager !== 'undefined') {
				argonMemoryManager = new ArgonMemoryManager();
				ArgonDebug.info('✓ 内存管理模块已初始化');
			}

			ArgonDebug.info('🚀 Argon 性能优化模块全部初始化完成');
		} catch (error) {
			ArgonDebug.error('性能优化模块初始化失败:', error);
		}
	} else {
		ArgonDebug.warn('性能优化模块未加载，请确保 argon-performance.js 已正确引入');
	}

	// ==========================================================================
	// 原有初始化代码
	// ==========================================================================
	initArgonLongContentVirtualizer();
	highlightJsRender();
	waterflowInit();
});
$(document).on("click", ".hljs-control-fullscreen", function () {
	let block = $(this).parent().parent();
	block.toggleClass("hljs-codeblock-fullscreen");
	if (block.hasClass("hljs-codeblock-fullscreen")) {
		$("html").addClass("noscroll codeblock-fullscreen");
	} else {
		$("html").removeClass("noscroll codeblock-fullscreen");
	}
});
$(document).on("click", ".hljs-control-toggle-break-line", function () {
	let block = $(this).parent().parent();
	block.toggleClass("hljs-break-line");
});
$(document).on("click", ".hljs-control-toggle-linenumber", function () {
	let block = $(this).parent().parent();
	block.toggleClass("hljs-hide-linenumber");
});

/*时间差计算/
function addPreZero(num, n) {
	var len = num.toString().length;
	while(len < n) {
		num = "0" + num;
		len++;
	}
	return num;
}
function humanTimeDiff(time){
	let now = new Date();
	time = new Date(time);
	let delta = now - time;
	if (delta < 0){
		delta = 0;
	}
	if (delta < 1000 * 60){
		return __("刚刚");
	}
	if (delta < 1000 * 60 * 60){
		return parseInt(delta / (1000 * 60)) + " " + __("分钟前);
	}
	if (delta < 1000 * 60 * 60 * 24){
		return parseInt(delta / (1000 * 60 * 60)) + " " + __("小时前);
	}
	let yesterday = new Date(now - 1000 * 60 * 60 * 24);
	yesterday.setHours(0);
	yesterday.setMinutes(0);
	yesterday.setSeconds(0);
	yesterday.setMilliseconds(0);
	if (time > yesterday){
		return __("昨天") + " " + time.getHours() + ":" + addPreZero(time.getMinutes(), 2);
	}
	let theDayBeforeYesterday = new Date(now - 1000 * 60 * 60 * 24 * 2);
	theDayBeforeYesterday.setHours(0);
	theDayBeforeYesterday.setMinutes(0);
	theDayBeforeYesterday.setSeconds(0);
	theDayBeforeYesterday.setMilliseconds(0);
	if (time > theDayBeforeYesterday && argonConfig.language.indexOf("zh") == 0){
		return __("前天") + " " + time.getHours() + ":" + addPreZero(time.getMinutes(), 2);
	}
	if (delta < 1000 * 60 * 60 * 24 * 30){
		return parseInt(delta / (1000 * 60 * 60 * 24)) + " " + __("天前");
	}
	let theFirstDayOfThisYear = new Date(now);
	theFirstDayOfThisYear.setMonth(0);
	theFirstDayOfThisYear.setDate(1);
	theFirstDayOfThisYear.setHours(0);
	theFirstDayOfThisYear.setMinutes(0);
	theFirstDayOfThisYear.setSeconds(0);
	theFirstDayOfThisYear.setMilliseconds(0);
	if (time > theFirstDayOfThisYear){
		if (argonConfig.dateFormat == "YMD" || argonConfig.dateFormat == "MDY"){
			return (time.getMonth() + 1) + "-" + time.getDate();
		}else{
			return time.getDate() + "-" + (time.getMonth() + 1);
		}
	}
	if (argonConfig.dateFormat == "YMD"){
		return time.getFullYear() + "-" + (time.getMonth() + 1) + "-" + time.getDate();
	}else if (argonConfig.dateFormat == "MDY"){
		return time.getDate() + "-" + (time.getMonth() + 1) + "-" + time.getFullYear();
	}else if (argonConfig.dateFormat == "DMY"){
		return time.getDate() + "-" + (time.getMonth() + 1) + "-" + time.getFullYear();
	}
}
function calcHumanTimesOnPage(){
	$(".human-time").each(function(){
		$(this).text(humanTimeDiff(parseInt($(this).data("time")) * 1000));
	});
}
calcHumanTimesOnPage();
setInterval(function(){
	calcHumanTimesOnPage()
}, 15000);


/*Console*/
!function () {
	void 0;
}();

/* ========== Modern UI Enhancements - 现代化交互动画增强========== */
(function () {
	'use strict';

	// 1. 图片加载动画
	function initImageLoadAnimation() {
		var images = document.querySelectorAll('article img[loading="lazy"], .post-thumbnail img');
		images.forEach(function (img) {
			if (img.dataset.loadAnimInit) return;
			img.dataset.loadAnimInit = 'true';
			if (img.complete) { img.classList.add('loaded'); }
			else { img.addEventListener('load', function () { this.classList.add('loaded'); }); }
		});
	}

	// 3. 滚动入场动画
	function initScrollAnimations() {
		if (!('IntersectionObserver' in window)) return;
		var animatedElements = document.querySelectorAll('.article-list article.post, .comment-item, .timeline-item, .friend-link-item, #leftbar .card, #rightbar .card');
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) { entry.target.classList.add('animate-in'); observer.unobserve(entry.target); }
			});
		}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
		animatedElements.forEach(function (el) { if (!el.classList.contains('animate-in')) observer.observe(el); });
	}

	// 4. 平滑滚动
	function initSmoothScroll() {
		document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
			if (anchor.dataset.smoothScrollInit) return;
			anchor.dataset.smoothScrollInit = 'true';
			anchor.addEventListener('click', function (e) {
				var targetId = this.getAttribute('href');
				var target = getHashTarget(targetId);
				if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
			});
		});
	}

	// 5. PJAX 加载动画
	function initPjaxAnimations() {
		if (typeof jQuery === 'undefined') return;
		jQuery(document).on('pjax:end', function () {
			setTimeout(function () { initArticleSkeletons(); initImageLoadAnimation(); initScrollAnimations(); initSmoothScroll(); }, 100);
		});
	}

	// 7. 主题切换动画 - 已在 header.php 中通过 setDarkmode 函数处理，此处不再重复
	function initThemeTransition() {
		// 移除 MutationObserver 避免无限循环导致内存泄漏
		// 主题切换过渡效果已在 setDarkmode() 函数中实现
	}

	// 7.1 骨架屏加载动画 (Skeleton Screen)
	function initArticleSkeletons() {
		if (typeof jQuery === 'undefined') return;
		var $skeletons = jQuery('.article-skeleton:not(.skeleton-fading)');
		if ($skeletons.length === 0) return;

		$skeletons.each(function () {
			var $skeleton = jQuery(this);
			$skeleton.addClass('skeleton-fading');

			// 给予一定的展示时长，避免闪烁并确保背景稳定性
			setTimeout(function () {
				$skeleton.addClass('article-skeleton-fade-out');
				setTimeout(function () {
					$skeleton.remove();
				}, 400); // 配合 CSS transition
			}, 400); // 骨架屏展示时长
		});
	}
	window.initArticleSkeletons = initArticleSkeletons;

	// 8. 减少动画偏好检查
	function checkReducedMotion() {
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			document.documentElement.classList.add('reduced-motion');
		}
	}

	// 初始化
	function init() {
		initArticleSkeletons();
		checkReducedMotion();
		initImageLoadAnimation();
		initScrollAnimations();
		initSmoothScroll();
		initPjaxAnimations();
		initThemeTransition();
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
	else { init(); }
})();
/* ========== End of Modern UI Enhancements ========== */


// ========== jQuery Easing 备用（确保在所有脚本加载后仍可用）==========
(function () {
	if (typeof jQuery === 'undefined') return;
	var $ = jQuery;
	if (!$.easing) $.easing = {};
	if (!$.easing.easeOutCirc) {
		$.easing.easeOutCirc = function (x) {
			return Math.sqrt(1 - Math.pow(x - 1, 2));
		};
	}
	if (!$.easing.easeOutExpo) {
		$.easing.easeOutExpo = function (x) {
			return x === 1 ? 1 : 1 - Math.pow(2, -10 * x);
		};
	}
})();
/* 友情链接徽章点击动画 */
$(document).on("click", ".badge-friend", function (e) {
	let x = e.clientX;
	let y = e.clientY;
	
	let $heart = $('<i class="fa fa-heart heart-bubble"></i>');
	let drift = (Math.random() - 0.5) * 120;
	let rotate = (Math.random() - 0.5) * 60;
	let duration = 2.0 + Math.random() * 1.0;
	$heart.css({
		left: x + 'px',
		top: y + 'px',
		'--drift': drift + 'px',
		'--rotate': rotate + 'deg',
		'--duration': duration + 's'
	});
	$('body').append($heart);
	setTimeout(() => {
		$heart.remove();
	}, duration * 1000);
});
