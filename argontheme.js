/*!
 * Argon 主题核心 JavaScript
 */

// ========== 兼容性修复 ==========
// 确保 Prism 和 autoloader 存在
if (typeof window.Prism === 'undefined') {
    window.Prism = { 
        highlightAll: function() {},
        highlightElement: function() {},
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
    window.Zoomify = function() {};
    window.Zoomify.DEFAULTS = {};
}

// 确保 jQuery 插件存在
if (typeof jQuery !== 'undefined') {
    (function($) {
        // 确保 easing 函数存在（防止其他插件覆盖 jQuery 后丢失）
        if (typeof $.easing === 'undefined') {
            $.easing = {};
        }
        if (typeof $.easing.easeOutCirc === 'undefined') {
            $.easing.easeOutCirc = function(x) {
                return Math.sqrt(1 - Math.pow(x - 1, 2));
            };
        }
        if (typeof $.easing.easeOutExpo === 'undefined') {
            $.easing.easeOutExpo = function(x) {
                return x === 1 ? 1 : 1 - Math.pow(2, -10 * x);
            };
        }
        
        // 确保 zoomify 插件存在
        if (typeof $.fn.zoomify === 'undefined') {
            $.fn.zoomify = function() { return this; };
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
                open: function() {},
                close: function() {}
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
            $.pjax = function() {};
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
if (typeof(argonConfig) == "undefined"){
	var argonConfig = {};
}
if (typeof(argonConfig.wp_path) == "undefined"){
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
var argonLongContentState = {
	isLong: false,
	contentSize: 0,
	nodeCount: 0
};

// ==========================================================================
// 调试控制台（由 argon-performance.js 引入）
// ==========================================================================
// 如果 ArgonDebug 未定义，创建一个简单的替代实现
if (typeof ArgonDebug === 'undefined') {
	window.ArgonDebug = {
		enabled: false,
		init() {
			if (typeof argonConfig !== 'undefined' && argonConfig.debug_mode) {
				this.enabled = true;
			}
		},
		log(...args) { if (this.enabled) console.log('[Argon]', ...args); },
		warn(...args) { if (this.enabled) console.warn('[Argon]', ...args); },
		error(...args) { if (this.enabled) console.error('[Argon]', ...args); },
		info(...args) { if (this.enabled) console.info('[Argon]', ...args); }
	};
	ArgonDebug.init();
}

function getArgonPostContentElement() {
	return document.getElementById('post_content');
}

function updateArgonLongContentState() {
	var content = getArgonPostContentElement();
	if (!content) {
		argonLongContentState = {
			isLong: false,
			contentSize: 0,
			nodeCount: 0
		};
		return argonLongContentState;
	}

	var contentSize = parseInt(content.getAttribute('data-argon-content-size') || '0', 10);
	if (!contentSize) {
		contentSize = content.textContent ? content.textContent.length : 0;
	}

	var isLongByContent = content.getAttribute('data-argon-long-content') === 'true' || contentSize > 524288;
	var nodeCount = isLongByContent ? 0 : content.getElementsByTagName('*').length;
	argonLongContentState = {
		isLong: isLongByContent || nodeCount > 2500,
		contentSize: contentSize,
		nodeCount: nodeCount
	};
	document.documentElement.classList.toggle('argon-long-content', argonLongContentState.isLong);
	return argonLongContentState;
}

function argonScheduleIdle(callback, timeout) {
	if (typeof window.requestIdleCallback === 'function') {
		return window.requestIdleCallback(callback, { timeout: timeout || 1200 });
	}
	return window.setTimeout(function() {
		callback({
			timeRemaining: function() {
				return 8;
			},
			didTimeout: true
		});
	}, 16);
}

function argonCancelIdle(id) {
	if (typeof window.cancelIdleCallback === 'function') {
		window.cancelIdleCallback(id);
	} else {
		window.clearTimeout(id);
	}
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
function __(text){
	let lang = argonConfig.language;
	if (typeof(translation[lang]) == "undefined"){
		return text;
	}
	if (typeof(translation[lang][text]) == "undefined"){
		return text;
	}
	return translation[lang][text];
}

/*根据滚动高度改变顶栏透明度 */
!function(){
	let toolbar = document.getElementById("navbar-main");
	let $bannerContainer = $("#banner_container");
	let $content = $("#content");

	let startTransitionHeight;
	let endTransitionHeight;
	let maxOpacity = 0.85;

	startTransitionHeight = $bannerContainer.offset().top - 75;
	endTransitionHeight = $content.offset().top - 75;

	$(window).resize(function(){
		startTransitionHeight = $bannerContainer.offset().top - 75;
		endTransitionHeight = $content.offset().top - 75;
	});

	function changeToolbarTransparency(){
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		if (scrollTop < startTransitionHeight){
			toolbar.style.setProperty('background-color', 'rgba(var(--toolbar-color), 0)', 'important');
			toolbar.style.setProperty('box-shadow', 'none');
			if (argonConfig.toolbar_blur){
				toolbar.style.setProperty('backdrop-filter', 'blur(0px)');
			}
			toolbar.classList.add("navbar-ontop");
			return;
		}
		if (scrollTop > endTransitionHeight){
			toolbar.style.setProperty('background-color', 'rgba(var(--toolbar-color), ' + maxOpacity + ')', 'important');
			toolbar.style.setProperty('box-shadow', '');
			if (argonConfig.toolbar_blur){
				toolbar.style.setProperty('backdrop-filter', 'blur(16px)');
			}
			toolbar.classList.remove("navbar-ontop");
			return;
		}
		let transparency = (scrollTop - startTransitionHeight) / (endTransitionHeight - startTransitionHeight) * maxOpacity;
		toolbar.style.setProperty('background-color', 'rgba(var(--toolbar-color), ' + transparency, 'important');
		toolbar.style.setProperty('box-shadow', '');
		if (argonConfig.toolbar_blur){
			if ((scrollTop - startTransitionHeight) / (endTransitionHeight - startTransitionHeight) > 0.3){
				toolbar.style.setProperty('backdrop-filter', 'blur(16px)');
			}else{
				toolbar.style.setProperty('backdrop-filter', 'blur(0px)');
			}
		}
		toolbar.classList.remove("navbar-ontop");
	}
	function changeToolbarOnTopClass(){
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		if (scrollTop < 30){
			toolbar.classList.add("navbar-no-blur");
		}else{
			toolbar.classList.remove("navbar-no-blur");
		}
	}
	if ($("html").hasClass("no-banner")) {
		changeToolbarOnTopClass();
		// 使用节流优化滚动事件性能
		const throttledChangeToolbarOnTopClass = argonEventManager ? 
			argonEventManager.throttle(changeToolbarOnTopClass, 16) : 
			changeToolbarOnTopClass;
		document.addEventListener("scroll", throttledChangeToolbarOnTopClass, {passive: true});
		return;
	}
	if (argonConfig.headroom == "absolute") {
		toolbar.classList.add("navbar-ontop");
		return;
	}
	if ($("html").hasClass("toolbar-blur")) {
		argonConfig.toolbar_blur = true;
		maxOpacity = 0.65;
	}else{
		argonConfig.toolbar_blur = false;
	}
	changeToolbarTransparency();
	// 使用节流优化滚动事件性能
	const throttledChangeToolbarTransparency = argonEventManager ? 
		argonEventManager.throttle(changeToolbarTransparency, 16) : 
		changeToolbarTransparency;
	document.addEventListener("scroll", throttledChangeToolbarTransparency, {passive: true});
}();

/*搜索*/
function searchPosts(word){
	if ($(".search-result").length > 0){
		let url = new URL(window.location.href);
		url.searchParams.set("s", word);
		$.pjax({
			url: url.href
		});
	}else{
		$.pjax({
			url: argonConfig.wp_path + "?s=" + encodeURI(word)
		});
	}
}
/*顶栏搜索*/
$(document).on("click" , "#navbar_search_input_container" , function(){
	$(this).addClass("open");
	$("#navbar_search_input").focus();
});
$(document).on("blur" , "#navbar_search_input_container" , function(){
	// 如果有文字则保持has-text类
	if ($("#navbar_search_input").val().trim() !== "") {
		$(this).addClass("has-text");
	} else {
		$(this).removeClass("has-text");
	}
	$(this).removeClass("open");
});
// 监听输入变化来切换has-text类
$(document).on("input" , "#navbar_search_input" , function(){
	var container = $("#navbar_search_input_container");
	if ($(this).val().trim() !== "") {
		container.addClass("has-text");
	} else {
		container.removeClass("has-text");
	}
});
$(document).on("keydown" , "#navbar_search_input_container #navbar_search_input" , function(e){
	if (e.keyCode != 13){
		return;
	}
	let word = $(this).val();
	if (word == ""){
		$("#navbar_search_input_container").blur();
		return;
	}
	let scrolltop = $(document).scrollTop();
	searchPosts(word);
});
/*顶栏搜索 (Mobile)*/
$(document).on("keydown" , "#navbar_search_input_mobile" , function(e){
	if (e.keyCode != 13){
		return;
	}
	let word = $(this).val();
	$("#navbar_global .collapse-close button").click();
	if (word == ""){
		return;
	}
	let scrolltop = $(document).scrollTop();
	searchPosts(word);
});
/*侧栏搜索*/
$(document).on("click" , "#leftbar_search_container" , function(){
	$(".leftbar-search-button").addClass("open");
	$("#leftbar_search_input").removeAttr("readonly").focus();
	$("#leftbar_search_input").focus();
	$("#leftbar_search_input").select();
	return false;
});
$(document).on("blur" , "#leftbar_search_container" , function(){
	$(".leftbar-search-button").removeClass("open");
	$("#leftbar_search_input").attr("readonly", "readonly");
});
$(document).on("keydown" , "#leftbar_search_input" , function(e){
	if (e.keyCode != 13){
		return;
	}
	let word = $(this).val();
	if (word == ""){
		$("#leftbar_search_container").blur();
		return;
	}
	$("html").removeClass("leftbar-opened");
	searchPosts(word);
});
/*搜索过滤器*/
$(document).on("change" , ".search-filter" , function(e){
	if (pjaxLoading){
		$(this).prop("checked", !$(this).prop("checked"));
		e.preventDefault();
		return;
	}
	pjaxLoading = true;
	let postTypes = [];
	$(".search-filter:checked").each(function(){
		postTypes.push($(this).attr("name"));
	});
	if (postTypes.length == 0){
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
!function(){
	if ($("#leftbar").length == 0){
		let contentOffsetTop = $('#content').offset().top;
		function changeLeftbarStickyStatusWithoutSidebar(){
			let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
			if( contentOffsetTop - 10 - scrollTop <= 20 ){
				document.body.classList.add('leftbar-can-headroom');
			}else{
				document.body.classList.remove('leftbar-can-headroom');
			}
		}
		changeLeftbarStickyStatusWithoutSidebar();
		// 使用节流优化滚动事件性能
		const throttledChangeLeftbarStickyStatusWithoutSidebar = argonEventManager ? 
			argonEventManager.throttle(changeLeftbarStickyStatusWithoutSidebar, 16) : 
			changeLeftbarStickyStatusWithoutSidebar;
		document.addEventListener("scroll", throttledChangeLeftbarStickyStatusWithoutSidebar, {passive: true});
		$(window).resize(function(){
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

	function changeLeftbarStickyStatus(){
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		if( part1OffsetTop + part1OuterHeight + 10 - scrollTop <= (argonConfig.headroom != "absolute" ? 90 : 18) ){
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
				
				document.documentElement.style.setProperty('--leftbar-part2-height', leftbarPart2.offsetHeight + 10 + 'px');
				document.documentElement.style.setProperty('--leftbar-part3-height', part3MaxHeight + 'px');
				document.documentElement.style.setProperty('--leftbar-part3-max-height', part3MaxHeight + 'px');
			}
		}else{
			//滚动条在顶部 不浮动状态
			leftbarPart2.classList.remove('sticky');
			if (leftbarPart3) leftbarPart3.classList.remove('sticky');
		}
		if( part1OffsetTop + part1OuterHeight + 10 - scrollTop <= 20 ){//侧栏下部分是否可以随 Headroom 一起向上移动
			document.body.classList.add('leftbar-can-headroom');
		}else{
			document.body.classList.remove('leftbar-can-headroom');
		}
	}
	changeLeftbarStickyStatus();
	// 使用节流优化滚动事件性能
	const throttledChangeLeftbarStickyStatus = argonEventManager ? 
		argonEventManager.throttle(changeLeftbarStickyStatus, 16) : 
		changeLeftbarStickyStatus;
	document.addEventListener("scroll", throttledChangeLeftbarStickyStatus, {passive: true});
	$(window).resize(function(){
		part1OffsetTop = $leftbarPart1.offset().top;
		part1OuterHeight = $leftbarPart1.outerHeight();
		changeLeftbarStickyStatus();
	});
	new MutationObserver(function(){
		part1OffsetTop = $leftbarPart1.offset().top;
		part1OuterHeight = $leftbarPart1.outerHeight();
		changeLeftbarStickyStatus();
	}).observe(leftbarPart1, {attributes: true, childList: true, subtree: true});
}();

/*Headroom*/
if (argonConfig.headroom == "true"){
	var headroom = new Headroom(document.querySelector("body"),{
		"tolerance" : {
			up : 0,
			down : 0
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
	$("#main.article-list img").each(function(index, ele){
		ele.onload = function(){
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
	}else{
		columns = parseInt(argonConfig.waterflow_columns);
	}
	if ($("#main").outerWidth() < 650 && columns == 2) {
		columns = 1;
	}else if ($("#main").outerWidth() < 800 && columns == 3) {
		columns = 1;
	}

	let heights = [0, 0, 0];
	function getMinHeightPosition(){
		let res = 0, minn = 2147483647;
		for (var i = 0; i < columns; i++) {
			if (heights[i] < minn) {
				minn = heights[i];
				res = i;
			}
		}
		return res;
	}
	function getMaxHeight(){
		let res = 0;
		for (let i in heights) {
			res = Math.max(res, heights[i]);
		}
		return res;
	}
	$("#primary").css("transition", "none")
		.addClass("waterflow");
	let $container = $("#main.article-list");
	if (!$container.length){
		return;
	}
	let $items = $container.find("article.post:not(.no-results), .shuoshuo-preview-container");
	columns = Math.max(Math.min(columns, $items.length), 1);
	if (columns == 1) {
		$container.removeClass("waterflow");
		$items.css("transition", "").css("position", "").css("width", "").css("top", "").css("left", "").css("margin", "");
		$(".waterflow-placeholder").remove();
	}else{
		$container.addClass("waterflow");
		$items.each(function(index, item) {
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
	}else{
		$container.prepend("<div class='waterflow-placeholder' style='height: " + getMaxHeight() +"px;'></div>");
	}
}
waterflowInit();
if (argonConfig.waterflow_columns != "1") {
	$(window).resize(function(){
		waterflowInit();
	});
	new MutationObserver(function(mutations, observer){
		waterflowInit();
	}).observe(document.querySelector("#primary"), {
		'childList': true
	});
}

/*移动端文章列表布局切换*/
!function(){
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
	$(window).resize(function(){
		applyMobileLayout();
	});
}();

/*浮动按钮栏相关(回顶部)*/
!function(){
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

	$backToTopBtn.on("click" , function(){
		$("body,html").stop().animate({
			scrollTop: 0
		}, 600);
	});

	$toggleDarkmode.on("click" , function(){
		toggleDarkmode();
	});

	$toggleAmoledMode.on("click" , function(){
		toggleAmoledDarkMode();
	})

	if ($("#post_comment").length > 0){
		$("#fabtn_go_to_comment").removeClass("fabtn-hidden");
	}else{
		$("#fabtn_go_to_comment").addClass("fabtn-hidden");
	}
	$goToComment.on("click" , function(){
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
			setTimeout(function() {
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

	if (localStorage['Argon_fabs_Floating_Status'] == "left"){
		$fabtns.addClass("fabtns-float-left");
	}
	$toggleSidesBtn.on("click" , function(){
		$fabtns.addClass("fabtns-unloaded");
		setTimeout(function(){
			$fabtns.toggleClass("fabtns-float-left");
			if ($fabtns.hasClass("fabtns-float-left")){
				localStorage['Argon_fabs_Floating_Status'] = "left";
			}else{
				localStorage['Argon_fabs_Floating_Status'] = "right";
			}
			$fabtns.removeClass("fabtns-unloaded");
		} , 300);
	});
	//博客设置
	$toggleBlogSettings.on("click" , function(){
		$("#float_action_buttons").toggleClass("blog_settings_opened");
	});
	$("#close_blog_settings").on("click" , function(){
		$("#float_action_buttons").removeClass("blog_settings_opened");
	});
	$("#blog_setting_darkmode_switch .custom-toggle-slider").on("click" , function(){
		toggleDarkmode();
	});
	//字体
	$("#blog_setting_font_sans_serif").on("click" , function(){
		$("html").removeClass("use-serif");
		localStorage['Argon_Use_Serif'] = "false";
	});
	$("#blog_setting_font_serif").on("click" , function(){
		$("html").addClass("use-serif");
		localStorage['Argon_Use_Serif'] = "true";
	});
	// 字体设置已在 header.php 中预加载，此处无需重复应用
	
	//阴影
	$("#blog_setting_shadow_small").on("click" , function(){
		$("html").removeClass("use-big-shadow");
		localStorage['Argon_Use_Big_Shadow'] = "false";
	});
	$("#blog_setting_shadow_big").on("click" , function(){
		$("html").addClass("use-big-shadow");
		localStorage['Argon_Use_Big_Shadow'] = "true";
	});
	// 阴影设置已在 header.php 中预加载，此处无需重复应用
	//滤镜
	function setBlogFilter(name){
		if (name == undefined || name == ""){
			name = "off";
		}
		if (!$("html").hasClass("filter-" + name)){
			$("html").removeClass("filter-sunset filter-darkness filter-grayscale");
			if (name != "off"){
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
	$(".blog-setting-filter-btn").on("click" , function(){
		setBlogFilter(this.getAttribute("filter-name"));
	});

	//UI 样式切换 (玻璃拟态/新拟态)
	function setUIStyle(style){
		if (style == undefined || style == ""){
			style = "default";
		}
		$("html").removeClass("style-glass style-neumorphism");
		if (style != "default"){
			$("html").addClass("style-" + style);
		}
		$(".blog-setting-style-btn").removeClass("active");
		$(".blog-setting-style-btn[style-name='" + style + "']").addClass("active");
		localStorage['Argon_UI_Style'] = style;
	}
	// UI 样式设置已在 header.php 中预加载，此处只需设置按钮状态
	let currentUIStyle = localStorage['Argon_UI_Style'] || 'default';
	$(".blog-setting-style-btn[style-name='" + currentUIStyle + "']").addClass("active");
	$(".blog-setting-style-btn").on("click" , function(){
		setUIStyle(this.getAttribute("style-name"));
	});

	let $window = $(window);
	var readingProgressMetrics = null;
	function invalidateReadingProgressMetrics() {
		readingProgressMetrics = null;
	}
	function getReadingProgressMetrics() {
		if (readingProgressMetrics) {
			return readingProgressMetrics;
		}
		var article = document.querySelector("article.post.post-full");
		if (!article) {
			return null;
		}
		var rect = article.getBoundingClientRect();
		readingProgressMetrics = {
			top: rect.top + window.pageYOffset - 80,
			scrollable: article.offsetHeight + 50 - window.innerHeight
		};
		return readingProgressMetrics;
	}

	function changefabtnDisplayStatus(){
		//阅读进度
		function hideReadingProgress(){
			$readingProgressBtn.addClass("fabtn-hidden");
		}
		function setReadingProgress(percent){
			$readingProgressBtn.removeClass("fabtn-hidden");
			$readingProgressDetails.html((percent * 100).toFixed(0) + "%");
			$readingProgressBar.css("width" , (percent * 100).toFixed(0) + "%");
		}
		let metrics = getReadingProgressMetrics();
		if (!metrics){
			hideReadingProgress();
		}else{
			let a = window.pageYOffset - metrics.top;
			let b = metrics.scrollable;
			if (b <= 0){
				hideReadingProgress();
			}else{
				readingProgress = a / b;
				if (isNaN(readingProgress) || readingProgress < 0 || readingProgress > 1){
					hideReadingProgress();
				}else{
					setReadingProgress(readingProgress);
				}
			}
		}
		//是否显示回顶
		if ($(window).scrollTop() >= 400){
			$backToTopBtn.removeClass("fabtn-hidden");
		}else{
			$backToTopBtn.addClass("fabtn-hidden");
		}
	}
	changefabtnDisplayStatus();
	$(window).scroll(function(){
		changefabtnDisplayStatus();
	});
	$(window).resize(function(){
		invalidateReadingProgressMetrics();
		changefabtnDisplayStatus();
	});
	document.addEventListener("load", function(e) {
		if (e.target && e.target.tagName === "IMG") {
			invalidateReadingProgressMetrics();
		}
	}, true);
	$fabtns.removeClass("fabtns-unloaded");
}();

/*卡片圆角大小调整*/
!function(){
	function setCardRadius(radius, setcookie){
		document.documentElement.style.setProperty('--card-radius', radius + "px");
		if (setcookie){
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
	slider.noUiSlider.on('update', function (values){
		let value = values[0];
		setCardRadius(value, false);
	});
	slider.noUiSlider.on('set', function (values){
		let value = values[0];
		setCardRadius(value, true);
	});
	$(document).on("click" , "#blog_setting_card_radius_to_default" , function(){
		slider.noUiSlider.set($("meta[name='theme-card-radius-origin']").attr("content"));
		setCardRadius($("meta[name='theme-card-radius-origin']").attr("content"), false);
		setCookie("argon_card_radius", $("meta[name='theme-card-radius-origin']").attr("content"), 0);
	});
}();

/*评论区 & 发送评论*/
!function(){
	//回复评论
	let replying = false , replyID = 0;
	
	/**
	 * 显示回复框
	 * @param {number} commentID - 评论ID
	 */
	function reply(commentID){
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
		if ($("#comment-" + commentID + " .comment-item-title .badge-private-comment").length > 0){
			$("#post_comment").addClass("post-comment-force-privatemode-on");
		}else{
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
		
		setTimeout(function(){
			$("#post_comment_content").focus();
		}, 300);
	}
	
	/**
	 * 取消回复
	 */
	function cancelReply(){
		replying = false;
		replyID = 0;
		let replyInfo = $('#post_comment_reply_info');
		replyInfo.removeClass('reply-entering').addClass('reply-leaving');
		setTimeout(function(){
			replyInfo.css('display', 'none').removeClass('reply-leaving');
		}, 200);
		$("#post_comment").removeClass("post-comment-force-privatemode-on post-comment-force-privatemode-off");
	}
	
	$(document).on("click" , ".comment-reply" , function(){
		reply(this.getAttribute("data-id"));
	});
	$(document).on("click pjax:click" , "#post_comment_reply_cancel" , function(){
		cancelReply();
	});
	$(document).on("pjax:click" , function(){
		replying = false;
		replyID = 0;
		$('#post_comment_reply_info').css("display", "none").removeClass('reply-entering reply-leaving');
		$("#post_comment").removeClass("post-comment-force-privatemode-on post-comment-force-privatemode-off");
	});
	//编辑评论
	let editing = false , editID = 0;
	function edit(commentID){
		cancelReply();
		editing = true;
		editID = commentID;
		$('#post_comment').addClass("editing");
		$("#post_comment_content").val($("#comment-" + editID + " .comment-item-source").text());
		$("#post_comment_content").trigger("change");
		if ($("#comment-" + editID).data("use-markdown") == true && document.getElementById("comment_post_use_markdown") != null){
			document.getElementById("comment_post_use_markdown").checked = true;
		}else{
			document.getElementById("comment_post_use_markdown").checked = false;
		}
		if ($("#comment-" + commentID + " .comment-item-title .badge-private-comment").length > 0){
			$("#post_comment").addClass("post-comment-force-privatemode-on");
		}else{
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
	function cancelEdit(clear){
		editing = false;
		editID = 0;
		$("#post_comment").removeClass("post-comment-force-privatemode-on post-comment-force-privatemode-off");
		if (clear == true) $("#post_comment_content").val("");
		$("#post_comment_content").trigger("change");
		$('#post_comment').removeClass("editing");
	}
	$(document).on("click", ".comment-edit", function(){
		edit(this.getAttribute("data-id"));
	});
	$(document).on("click", "#post_comment_edit_cancel", function(){
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
	$(document).on("pjax:click", function(){
		cancelEdit(true);
	});
	$(document).on("click", ".comment-pin, .comment-unpin", function(){
		toogleCommentPin(this.getAttribute("data-id"), !this.classList.contains("comment-pin"));
	});
	$(document).on("click", ".comment-delete", function(){
		deleteComment(this.getAttribute("data-id"));
	});
	$(document).on("mouseenter", ".comment-parent-info", function(){
		$("#comment-" + this.getAttribute("data-parent-id")).addClass("highlight");
	});
	$(document).on("mouseleave", ".comment-parent-info", function(){
		$("#comment-" + this.getAttribute("data-parent-id")).removeClass("highlight");
	});
	//切换评论置顶状态
	function toogleCommentPin(commentID, pinned){
		$("#comment_pin_comfirm_dialog .modal-title").html(pinned ? __("取消置顶评论") : __("置顶评论"));
		$("#comment_pin_comfirm_dialog .modal-body").html(pinned ? __("是否要取消置顶评论 #") + commentID + "?" : __("是否要置顶评论 #") + commentID + "?");
		$("#comment_pin_comfirm_dialog .btn-comfirm").html(__("确认")).attr("disabled", false);
		$("#comment_pin_comfirm_dialog .btn-dismiss").html(__("取消")).attr("disabled", false);
		$("#comment_pin_comfirm_dialog .btn-comfirm").off("click").on("click", function(){
			$("#comment_pin_comfirm_dialog .btn-dismiss").attr("disabled", true)
			$("#comment_pin_comfirm_dialog .btn-comfirm").attr("disabled", true).prepend(__(`<span class="btn-inner--icon" style="margin-right: 10px;"><i class="fa fa-spinner fa-spin"></i></span>`));
			$.ajax({
				type: 'POST',
				url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
				dataType : "json",
				data: {
					action: "pin_comment",
					id: commentID,
					pinned: pinned ? "false" : "true"
				},
				success: function(result){
					$("#comment_pin_comfirm_dialog").modal('hide');
					if (result.status == "success"){
						if (pinned){
							$("#comment-" + commentID + " .comment-name .badge-pinned").remove();
							$("#comment-" + commentID + " .comment-unpin").removeClass("comment-unpin").addClass("comment-pin").html(__("置顶"));
						}else{
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
				error: function(result){
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
			success: function(result) {
				if (result.status === 'success') {
					// 移除评论元素
					$('#comment-' + commentID).fadeOut(300, function() {
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
			error: function() {
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
	$(document).on("click" , "#post_comment_toggle_extra_input" , function(){
		$("#post_comment").toggleClass("show-extra-input");
		if ($("#post_comment").hasClass("show-extra-input")){
			$("#post_comment_extra_input").slideDown(250, 'easeOutCirc');
		}else{
			$("#post_comment_extra_input").slideUp(200, 'easeOutCirc');
		}
	});

	//输入框细节
	$(document).on("change input keydown keyup propertychange" , "#post_comment_content" , function(){
		$("#post_comment_content_hidden")[0].innerText = $("#post_comment_content").val() + "\n";
		$("#post_comment_content").css("height" , $("#post_comment_content_hidden").outerHeight());
	});
	$(document).on("focus" , "#post_comment_link" , function(){
		$(".post-comment-link-container").addClass("active");
	});
	$(document).on("blur" , "#post_comment_link" , function(){
		$(".post-comment-link-container").removeClass("active");
	});
	$(document).on("focus" , "#post_comment_captcha" , function(){
		$(".post-comment-captcha-container").addClass("active");
	});
	$(document).on("blur" , "#post_comment_captcha" , function(){
		$(".post-comment-captcha-container").removeClass("active");
	});

	function restoreCommentSubmitControls(isAdmin){
		$("#post_comment").removeClass("sending");
		$("#post_comment_content").removeAttr("disabled");
		$("#post_comment_name").removeAttr("disabled");
		$("#post_comment_email").removeAttr("disabled");
		$("#post_comment_link").removeAttr("disabled");
		$("#post_comment_send").removeAttr("disabled");
		$("#post_comment_reply_cancel").removeAttr("disabled");
		if (!isAdmin){
			$("#post_comment_captcha").removeAttr("disabled");
		}
		$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-send'></i>");
		$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送"));
		window.geetestAutoSubmitting = false;
	}

	function resetGeetestCommentCaptcha(){
		if ($("#geetest-captcha").length == 0){
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
	window.postComment = function postComment(){
		let commentContent = $("#post_comment_content").val();
		let commentName = $("#post_comment_name").val();
		let commentEmail = $("#post_comment_email").val();
		let commentLink = $("#post_comment_link").val();
		let commentCaptcha = $("#post_comment_captcha").val();
		let useMarkdown = false;
		let privateMode = false;
		let mailNotice = false;
		if ($("#comment_post_use_markdown").length > 0){
			useMarkdown = $("#comment_post_use_markdown")[0].checked;
		}
		if ($("#comment_post_privatemode").length > 0){
			privateMode = $("#comment_post_privatemode")[0].checked;
		}
		if ($("#comment_post_mailnotice").length > 0){
			mailNotice = $("#comment_post_mailnotice")[0].checked;
		}

		let postID = $("#post_comment_post_id").val();
		let commentCaptchaSeed = $("#post_comment_captcha_seed").val();
		let needCaptcha = !$("#post_comment").hasClass("no-need-captcha");
		let useGeetestCaptcha = needCaptcha && $("#geetest-captcha").length > 0;

		let isError = false;
		let errorMsg = "";

		//检查表单合法性
		if (commentContent.match(/^\s*$/)){
			isError = true;
			errorMsg += __("评论内容不能为空") + "</br>";
		}
		if (!$("#post_comment").hasClass("no-need-name-email")){
			if (commentName.match(/^\s*$/)){
				isError = true;
				errorMsg += __("昵称不能为空") + "</br>";
			}
			if ($("#post_comment").hasClass("enable-qq-avatar")){
				if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)){
					isError = true;
					errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
				}
			}else{
				if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)){
					isError = true;
					errorMsg += __("邮箱格式错误") + "</br>";
				}
			}
		}else{
			if (commentEmail.length || (document.getElementById("comment_post_mailnotice") != null && document.getElementById("comment_post_mailnotice").checked == true)){
				if ($("#post_comment").hasClass("enable-qq-avatar")){
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)){
						isError = true;
						errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
					}
				}else{
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)){
						isError = true;
						errorMsg += __("邮箱格式错误") + "</br>";
					}
				}
			}
		}
		if (commentLink != "" && !(/https?:\/\//).test(commentLink)){
			isError = true;
			errorMsg += __("网站格式错误 (不是 http(s):// 开头)") + "</br>";
		}
		if (needCaptcha){
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
				if (commentCaptcha == ""){
					isError = true;
					errorMsg += __("验证码未输入");
				}
				if (commentCaptcha != "" && !(/^[0-9]+$/).test(commentCaptcha)){
					isError = true;
					errorMsg += __("验证码格式错误");
				}
			}
		}
		if (isError){
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
		$("#post_comment_content").attr("disabled","disabled");
		$("#post_comment_name").attr("disabled","disabled");
		$("#post_comment_email").attr("disabled","disabled");
		$("#post_comment_captcha").attr("disabled","disabled");
		$("#post_comment_link").attr("disabled","disabled");
		$("#post_comment_send").attr("disabled","disabled");
		$("#post_comment_reply_cancel").attr("disabled","disabled");
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
			url: commentLink,
			comment_post_ID: postID,
			comment_parent: replyID,
			"wp-comment-cookies-consent": "yes",
			use_markdown: useMarkdown,
			private_mode: privateMode ? "true" : "false",
			enable_mailnotice: mailNotice ? "true" : "false",
			argon_nonce: $("#argon_comment_nonce").val() || "",
			argon_comment_honeypot: $("#argon_comment_honeypot").val() || ""
		};
		ajaxData.use_markdown = useMarkdown ? "true" : "false";
		ajaxData.comment_parent = replyID || 0;

		// 根据验证码类型添加相应参数
		if (useGeetestCaptcha) {
			// 检查Geetest库加载状态
			if (typeof window.geetestLoadFailed !== 'undefined' && window.geetestLoadFailed) {
				restoreCommentSubmitControls(false);
				
				setTimeout(function() {
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
				setTimeout(function() {
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
			dataType : "json",
			data: ajaxData,
			success: function(result){
				restoreCommentSubmitControls(result.isAdmin);
				$("#post_comment").removeClass("show-extra-input post-comment-force-privatemode-on post-comment-force-privatemode-off");

				//判断是否有错误
				if (result.status == "failed"){
					resetGeetestCommentCaptcha();
					// 使用 setTimeout 确保 iziToast 操作在下一个事件循环中执行
					setTimeout(function() {
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
				
				// 显示成功提示
				setTimeout(function() {
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
					result.html = result.html.replace(/<(\/).noscript>/g, "");
					let parentID = result.parentID;
					if (parentID == "" || parentID == null){
						parentID = 0;
					}
					parentID = parseInt(parentID);
					if (parentID == 0){
						if ($("#comments > .card-body > ol.comment-list").length == 0){
							$("#comments > .card-body").html("<h2 class='comments-title'><i class='fa fa-comments'></i> " + __("评论") + "</h2><ol class='comment-list'></ol>");
						}
						if (result.commentOrder == "asc"){
							$("#comments > .card-body > ol.comment-list").append(result.html);
						}else{
							$("#comments > .card-body > ol.comment-list").prepend(result.html);
						}
					}else{
						if ($("#comment-" + parentID + " + .comment-divider + li > ul.children").length > 0){
							$("#comment-" + parentID + " + .comment-divider + li > ul.children").append(result.html);
						}else{
							$("#comment-" + parentID + " + .comment-divider").after("<li><ul class='children'>" + result.html + "</ul></li>");
						}
					}
					calcHumanTimesOnPage();
					
					// 滚动到新评论
					$("body,html").animate({
						scrollTop: $("#comment-" + result.id).offset().top - 100
					}, 500, 'easeOutExpo');
				} catch (e) {
					ArgonDebug.warn('Comment insertion error:', e);
				}
				},
				error: function(result){
				restoreCommentSubmitControls(false);
				$("#post_comment").removeClass("show-extra-input post-comment-force-privatemode-on post-comment-force-privatemode-off");
				resetGeetestCommentCaptcha();

				// 安全地处理iziToast 操作，防止time 属性错误
				setTimeout(function() {
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
	function editComment(){
		let commentContent = $("#post_comment_content").val();
		let isError = false;
		let errorMsg = "";
		if (commentContent.match(/^\s*$/)){
			isError = true;
			errorMsg += __("评论内容不能为空") + "</br>";
		}
		if (isError){
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
		$("#post_comment_content").attr("disabled","disabled");
		$("#post_comment_send").attr("disabled","disabled");
		$("#post_comment_edit_cancel").attr("disabled","disabled");
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
			dataType : "json",
			data: {
				action: "user_edit_comment",
				comment: commentContent,
				id: editID
			},
			success: function(result){
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_edit_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-not-editing").html("<i class='fa fa-pencil'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-not-editing").html(__("编辑"));

				//判断是否有错误
				if (result.status == "failed"){
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
				if ($("#comment-" + editID + " .comment-info .comment-edited").length == 0){
					$("#comment-" + editID + " .comment-info").prepend("<div class='comment-edited'><i class='fa fa-pencil' aria-hidden='true'></i>" + __("已编辑") + "</div>")
				}
				if (result.can_visit_edit_history){
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
			error: function(result){
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_edit_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-not-editing").html("<i class='fa fa-pencil'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-not-editing").html(__("编辑"));
				if (result.readyState != 4 || result.status == 0){
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
	$(document).on("click" , "#post_comment_send" , function(){
		if ($("#post_comment").hasClass("editing")){
			editComment();
		}else{
			// 首先进行基本的表单验证
			let commentContent = $("#post_comment_content").val();
			let commentName = $("#post_comment_name").val();
			let commentEmail = $("#post_comment_email").val();
			let commentLink = $("#post_comment_link").val();
			
			let isError = false;
			let errorMsg = "";

			// 检查表单合法性
			if (commentContent.match(/^\s*$/)){
				isError = true;
				errorMsg += __("评论内容不能为空") + "</br>";
			}
			if (!$("#post_comment").hasClass("no-need-name-email")){
				if (commentName.match(/^\s*$/)){
					isError = true;
					errorMsg += __("昵称不能为空") + "</br>";
				}
				if ($("#post_comment").hasClass("enable-qq-avatar")){
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)){
						isError = true;
						errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
					}
				}else{
					if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)){
						isError = true;
						errorMsg += __("邮箱格式错误") + "</br>";
					}
				}
			}else{
				if (commentEmail.length || (document.getElementById("comment_post_mailnotice") != null && document.getElementById("comment_post_mailnotice").checked == true)){
					if ($("#post_comment").hasClass("enable-qq-avatar")){
						if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail) && !(/^[1-9][0-9]{4,10}$/).test(commentEmail)){
							isError = true;
							errorMsg += __("邮箱或QQ 号格式错误") + "</br>";
						}
					}else{
						if (!(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/).test(commentEmail)){
							isError = true;
							errorMsg += __("邮箱格式错误") + "</br>";
						}
					}
				}
			}
			if (commentLink != "" && !(/https?:\/\//).test(commentLink)){
				isError = true;
				errorMsg += __("网站格式错误 (不是 http(s):// 开头)") + "</br>";
			}
			
			// 如果基本表单验证失败，显示错误信息并返回
			if (isError){
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
			
			// 基本验证通过后，检查验证码
			if (!$("#post_comment").hasClass("no-need-captcha") && $("#geetest-captcha").length > 0) {
				// 检查Geetest 库是否加载失败
				if (window.geetestLoadFailed) {
					if (typeof loadGeetestScript === 'function' && typeof initGeetestCaptchaCore === 'function') {
						try {
							loadGeetestScript(function(){
								initGeetestCaptchaCore();
								setTimeout(function(){
									if (window.geetestCaptcha) { try { window.geetestCaptcha.showBox(); } catch(e){} }
								}, 0);
							});
						} catch(e) {}
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
								loadGeetestScript(function(){
									initGeetestCaptchaCore();
									setTimeout(function(){
										if (window.geetestCaptcha) { try { window.geetestCaptcha.showBox(); } catch(e){} }
									}, 0);
								});
							} catch(e) {}
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
$(document).on('click', '.comment-upvote', function(){
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
		success: function(result){
			$this.removeClass('comment-upvoting');
			if (result.status === 'success'){
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
		error: function(xhr){
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
function lazyloadStickers(){
	// 原生懒加载无需额外处理
}
$(document).on("click" , "#comment_emotion_btn" , function(){
	$("#comment_emotion_btn").toggleClass("comment-emotion-keyboard-open");
});
$(document).on("click" , ".emotion-keyboard .emotion-group-name" , function(){
	$(".emotion-keyboard .emotion-group-name.active").removeClass("active");
	$(this).addClass("active");
	$(".emotion-keyboard .emotion-group:not(d-none)").addClass("d-none");
	$(".emotion-keyboard .emotion-group[index='" + $(this).attr("index") + "']").removeClass("d-none");
});
function inputInsertText(text, input){
	$(input).focus();
	let isSuccess = document.execCommand("insertText", false, text);
	if (!isSuccess) { //FF
		if (typeof input.setRangeText === "function"){
			const start = input.selectionStart;
			input.setRangeText(text);
			input.selectionStart = input.selectionEnd = start + input.length;
			const e = document.createEvent("UIEvent");
			e.initEvent("input", true, false);
			input.dispatchEvent(e);
		}else{
			let value = $(input).val();
			let startPos = input.selectionStart, endPos = input.selectionEnd;
			$(input).val(value.substring(0, startPos) + text + value.substring(endPos));
			input.selectionStart = startPos + text.length;
			input.selectionEnd = startPos + text.length;
		}
	}
	$(input).focus();
}
$(document).on("click" , ".emotion-keyboard .emotion-item" , function(){
	$("#comment_emotion_btn").removeClass("comment-emotion-keyboard-open");
	if ($(this).hasClass("emotion-item-sticker")){
		inputInsertText(" :" + $(this).attr("code") + ": ", document.getElementById("post_comment_content"));
	}else{
		inputInsertText($(this).attr("text"), document.getElementById("post_comment_content"));
	}
});
$(document).on("dragstart" , ".emotion-keyboard .emotion-item > img, .comment-sticker" , function(e){
	e.preventDefault();
});
document.addEventListener('click', (e) => {
	if (document.getElementById("comment_emotion_btn") == null){
		return;
	}
　　if(e.target.id != "comment_emotion_btn" && e.target.id != "emotion_keyboard" && !document.getElementById("comment_emotion_btn").contains(e.target) && !document.getElementById("emotion_keyboard").contains(e.target)){
		$("#comment_emotion_btn").removeClass("comment-emotion-keyboard-open");
　　}
})
/*查看评论编辑记录*/
function showCommentEditHistory(id){
	let requestID = parseInt(new Date().getTime());
	$("#comment_edit_history").data("request-id", requestID);
	$("#comment_edit_history .modal-title").html(__("评论 #") + id + " " + __("的编辑记录"));
	$("#comment_edit_history .modal-body").html("<div class='comment-history-loading'><span class='spinner-border text-primary'></span><span style='display: inline-block;transform: translateY(-4px);margin-left: 15px;font-size: 18px;'>加载中/span></div>");
	$("#comment_edit_history").modal(null);
	$.ajax({
		type: 'POST',
		url: argonConfig.wp_path + "wp-admin/admin-ajax.php",
		dataType : "json",
		data: {
			action: "get_comment_edit_history",
			id: id
		},
		success: function(result){
			if ($("#comment_edit_history").data("request-id") != requestID){
				return;
			}
			$("#comment_edit_history .modal-body").hide();
			$("#comment_edit_history .modal-body").html(result.history);
			$("#comment_edit_history .modal-body").fadeIn(300);
		},
		error: function(result){
			if ($("#comment_edit_history").data("request-id") != requestID){
				return;
			}
			$("#comment_edit_history .modal-body").hide();
			$("#comment_edit_history .modal-body").html(__("加载失败"));
			$("#comment_edit_history .modal-body").fadeIn(300);
		}
	});
}
$(document).on("click" , ".comment-edited.comment-edithistory-accessible" , function(){
	showCommentEditHistory($(this).parent().parent().parent().parent().data("id"));
});
/*过长评论折叠*/
function foldLongComments(){
	if (argonConfig.fold_long_comments == false){
		return;
	}
	$(".comment-item-inner").each(function(){
		if ($(this).hasClass("comment-unfolded")){
			return;
		}
		if (this.clientHeight > 800){
			$(this).addClass("comment-folded");
			$(this).append("<div class='show-full-comment'><button><i class='fa fa-angle-down' aria-hidden='true'></i> " + __("展开") + "</button></div>");
		}
	});
}
foldLongComments();
$(document).on("click" , ".show-full-comment" , function(){
	$(this).parent().removeClass("comment-folded").addClass("comment-unfolded");
});
/*评论文字头像*/
function generateCommentTextAvatar(img){
	let emailHash = '';
	try{
		emailHash = img.attr("src").match(/([a-f\d]{32}|[A-F\d]{32})/)[0];
	}catch{
		emailHash = img.parent().parent().parent().find(".comment-name").text().trim();
		if (emailHash == '' || emailHash == undefined){
			emailHash = img.parent().find("*[class*='comment-author']").text().trim();
		}
	}
	let hash = 0;
	for (i in emailHash){
		hash = (hash * 233 + emailHash.charCodeAt(i)) % 16;
	}
	let colors = ['#e25f50', '#f25e90', '#bc67cb', '#9672cf', '#7984ce', '#5c96fa', '#7bdeeb', '#45d0e2', '#48b7ad', '#52bc89', '#9ace5f', '#d4e34a', '#f9d715', '#fac400', '#ffaa00', '#ff8b61', '#c2c2c2', '#8ea3af', '#a1877d', '#a3a3a3', '#b0b6e3', '#b49cde', '#c2c2c2', '#7bdeeb', '#bcaaa4', '#aed77f'];
	let text = $(".comment-name", img.parent().parent().parent()).text().trim()[0];
	if (text == '' || text == undefined){
		text = img.parent().find("*[class*='comment-author']").text().trim()[0];
	}
	let classList = img.attr('class') + " text-avatar";
	img.prop('outerHTML', '<div class="' + classList + '" style="background-color: ' + colors[hash] + ';">' + text + '</div>');
}
document.addEventListener("error", function(e){
	let img = $(e.target);
	if (!img.hasClass("avatar")){
		return;
	}
	generateCommentTextAvatar(img);
}, true);
function refreshCommentTextAvatar(){
	$(".comment-item-avatar > img.avatar").each(function(index, img){
		if (!img.complete){
			return;
		}
		if (img.naturalWidth !== 0){
			return false;
		}
		generateCommentTextAvatar($(img));
	});
}
refreshCommentTextAvatar();
$(window).on("load", function(){
	refreshCommentTextAvatar();
});
/*需要密码的文章加载*/
$(document).on("submit" , ".post-password-form" , function(){
	$("input[type='submit']", this).attr("disabled", "disabled");
	let url = $(this).attr("action");
	$.pjax.form(this, {
		push: false,
		replace: false
	});
	return false;
});
/*评论分页加载*/
!function(){
	$(document).on("click" , "#comments_navigation .page-item > div" , function(){
		$("#comments").addClass("comments-loading");
		NProgress.set(0.618);
		url = $(this).attr("href");
		$.ajax({
			type: 'POST',
			url: url,
			dataType : "html",
			success : function(result){
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
			error : function(){
				window.location.href = url;
			}
		});
	});
	$(document).on("click" , "#comments_more" , function(){
		$("#comments_more").attr("disabled", "disabled");
		NProgress.set(0.618);
		url = $(this).attr("href");
		$.ajax({
			type: 'POST',
			url: url,
			data: {
				no_post_view: 'true'
			},
			dataType : "html",
			success : function(result){
				NProgress.done();
				$vdom = $(result);
				$("#comments > .card-body > ol.comment-list").append($("#comments > .card-body > ol.comment-list", $vdom).html());
				if ($("#comments_more", $vdom).length == 0){
					$("#comments_more").remove();
					$(".comments-navigation-more").html("<div class='comments-navigation-nomore'>" + __("没有更多了") + "</div>");
				}else{
					$("#comments_more").attr("href", $("#comments_more", $vdom).attr("href"));
					$("#comments_more").removeAttr("disabled");
				}
				foldLongComments();
				calcHumanTimesOnPage();
				panguInit();
			},
			error : function(){
				window.location.href = url;
			}
		});
	});
}();

/*URL 和# 根据 ID 定位*/
function gotoHash(hash, durtion, easing = 'easeOutExpo'){
	if (hash.length == 0){
		return;
	}
	if ($(hash).length == 0){
		return;
	}
	if (durtion == null){
		durtion = 200;
	}
	$("body,html").stop().animate({
		scrollTop: $(hash).offset().top - 80
	}, durtion, easing);
}
function getHash(url){
	return url.substring(url.indexOf('#'));
}
!function(){
	$(window).on("hashchange" , function(){
		gotoHash(window.location.hash);
	});
	$(window).trigger("hashchange");
}();

/*显示文章过时信息 Toast*/
function showPostOutdateToast(){
	if ($("#primary #post_outdate_toast").length > 0){
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

function zoomifyInit(){
	// 清理旧的 Zoomify 实例
	if (zoomifyInstances.length > 0) {
		zoomifyInstances.forEach(function(instance) {
			try {
				if (instance && typeof instance.destroy === 'function') {
					instance.destroy();
				}
			} catch(e) {}
		});
		zoomifyInstances = [];
	}
	
	if (argonConfig.zoomify == false){
		return;
	}
	if (typeof $.fn.zoomify === 'function') {
		$("article img:not(.zoomify-initialized)").each(function() {
			let $img = $(this);
			$img.addClass('zoomify-initialized');
			try {
				let instance = $img.zoomify(argonConfig.zoomify).data('zoomify');
				if (instance) {
					zoomifyInstances.push(instance);
				}
			} catch(e) {}
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

function lazyloadInit() {
	// 清理旧的 Observer（防止重复初始化）
	if (lazyloadObserver) {
		try {
			lazyloadObserver.disconnect();
			lazyloadObserver = null;
			ArgonDebug.log('清理旧的 Lazyload Observer');
		} catch(e) {
			ArgonDebug.warn('清理 Lazyload Observer 失败:', e);
			lazyloadObserver = null;
		}
	}
	
	// 清理旧的滚动监听器（降级方案）
	if (lazyloadScrollHandler) {
		try {
			window.removeEventListener('scroll', lazyloadScrollHandler);
			window.removeEventListener('resize', lazyloadScrollHandler);
			lazyloadScrollHandler = null;
			ArgonDebug.log('清理旧的滚动监听器');
		} catch(e) {
			ArgonDebug.warn('清理滚动监听器失败:', e);
			lazyloadScrollHandler = null;
		}
	}
	
	// 检查是否启用懒加载
	if (argonConfig.lazyload === false || argonConfig.lazyload === 'false') {
		// 未启用懒加载时，直接加载所有图片
		loadAllImagesImmediately();
		return;
	}
	
	let images = document.querySelectorAll('img.lazyload[data-src]');
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
			lazyloadObserver = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						let img = entry.target;
						loadImageOptimized(img, effect);
						lazyloadObserver.unobserve(img);
					}
				});
			}, {
				rootMargin: threshold + 'px 0px'
			});
			
			images.forEach(function(img) {
				// 重置图片状态
				img.style.opacity = '';
				img.style.transform = '';
				img.style.transition = '';
				lazyloadObserver.observe(img);
			});
			
			ArgonDebug.log('使用 IntersectionObserver 实现懒加载');
		} catch(e) {
			ArgonDebug.error('IntersectionObserver 初始化失败，使用降级方案:', e);
			lazyloadFallback(images, effect, threshold);
		}
	} else {
		// 降级方案：使用滚动监听
		ArgonDebug.log('浏览器不支持 IntersectionObserver，使用滚动监听降级方案');
		lazyloadFallback(images, effect, threshold);
	}
}

/**
 * 优化的图片加载函数
 * 使用 requestAnimationFrame 替代 setTimeout
 * @param {HTMLImageElement} img - 图片元素
 * @param {string} effect - 加载效果类型 ('fadeIn' 或'slideDown')
 */
function loadImageOptimized(img, effect) {
	let src = img.getAttribute('data-src');
	if (!src) {
		// 如果没有 data-src，取消 Observer 监听
		if (lazyloadObserver) {
			lazyloadObserver.unobserve(img);
		}
		return;
	}
	
	// 预加载图片
	let tempImg = new Image();
	tempImg.onload = function() {
		// 使用 requestAnimationFrame 优化 DOM 操作
		requestAnimationFrame(function() {
			img.src = src;
			img.removeAttribute('data-src');
			img.classList.remove('lazyload');
			
			// 移除所有lazyload-style-* 类
			img.className = img.className.replace(/\blazyload-style-\d+\b/g, '').trim();
			
			// 应用加载效果
			applyLoadEffectOptimized(img, effect);
		});
	};
	tempImg.onerror = function() {
		// 加载失败时使用降级方案
		requestAnimationFrame(function() {
			img.src = src;
			img.removeAttribute('data-src');
			img.classList.remove('lazyload');
			img.className = img.className.replace(/\blazyload-style-\d+\b/g, '').trim();
			
			// 取消 Observer 监听
			if (lazyloadObserver) {
				lazyloadObserver.unobserve(img);
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
		requestAnimationFrame(function() {
			requestAnimationFrame(function() {
				img.style.opacity = '1';
			});
		});
		// 使用 transitionend 事件清理样式，更可靠
		img.addEventListener('transitionend', function cleanup() {
			img.style.transition = '';
			img.removeEventListener('transitionend', cleanup);
		}, {once: true});
	} else if (effect === 'slideDown') {
		img.style.opacity = '0';
		img.style.transform = 'translateY(-20px)';
		img.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
		requestAnimationFrame(function() {
			requestAnimationFrame(function() {
				img.style.opacity = '1';
				img.style.transform = 'translateY(0)';
			});
		});
		// 使用 transitionend 事件清理样式
		img.addEventListener('transitionend', function cleanup() {
			img.style.transition = '';
			img.style.transform = '';
			img.removeEventListener('transitionend', cleanup);
		}, {once: true});
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
		
		images.forEach(function(img) {
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
	window.addEventListener('scroll', lazyloadScrollHandler, {passive: true});
	window.addEventListener('resize', lazyloadScrollHandler, {passive: true});
	
	// 立即检查一次
	checkImagesInView();
}

/**
 * 立即加载所有图片（懒加载禁用时）
 * 不应用任何加载效果，直接替换 src
 */
function loadAllImagesImmediately() {
	let images = document.querySelectorAll('img.lazyload[data-src]');
	images.forEach(function(img) {
		let src = img.getAttribute('data-src');
		if (src) {
			img.src = src;
			img.removeAttribute('data-src');
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
function panguInit(){
	if (argonConfig.pangu.indexOf("article") >= 0){
		pangu.spacingElementByClassName('post-content');
	}
	if (argonConfig.pangu.indexOf("comment") >= 0){
		pangu.spacingElementById('comments');
	}
	if (argonConfig.pangu.indexOf("shuoshuo") >= 0){
		pangu.spacingElementByClassName('shuoshuo-content');
	}
}
panguInit();

/*Clamp.js*/
function clampInit(){
	$(".clamp").each(function(index, dom) {
		$clamp(dom, {clamp: dom.getAttribute("clamp-line")});
	});
}
clampInit();

/*Tippy.js*/
function tippyInit(){
	//Reference Popover
	tippy('sup.reference[data-content]:not(.tippy-initialized)', {
		content: (reference) => reference.getAttribute('data-content'),
		allowHTML: true,
		interactive: true,theme: 'light scroll-y',
		delay: [100, 250],
		animation: 'fade'
	});
	$("sup.reference[data-content]:not(.tippy-initialized)").addClass("tippy-initialized");
}
tippyInit();

/*Banner 全屏封面相关*/
if ($("html").hasClass("banner-as-cover")){
	function classInit(){
		if ($("#main").hasClass("article-list-home")){
			if (!$("html").hasClass("is-home")){
				$("html").addClass("is-home");
				$("html").trigger("resize");
			}
		}else{
			if ($("html").hasClass("is-home")){
				$("html").removeClass("is-home");
				$("html").trigger("resize");
			}
		}
	}
	classInit();
	new MutationObserver(function(mutations, observer){
		classInit();
	}).observe(document.querySelector("#primary"), {
		'childList': true
	});
	$(".cover-scroll-down").on("click" , function(){
		gotoHash("#content", 600, 'easeOutCirc');
		$("#content").focus();
	});
	$fabs = $("#float_action_buttons");
	$coverScrollDownBtn = $(".cover-scroll-down");
	function changeWidgetsDisplayStatus(){
		let scrollTop = $(window).scrollTop();
		if (scrollTop >= window.outerHeight * 0.2){
			$fabs.removeClass("hidden");
		}else{
			$fabs.addClass("hidden");
		}
		if (scrollTop >= window.outerHeight * 0.6){
			$coverScrollDownBtn.addClass("hidden");
		}else{
			$coverScrollDownBtn.removeClass("hidden");
		}
	}
	changeWidgetsDisplayStatus();
	$(window).scroll(function(){
		changeWidgetsDisplayStatus();
	});
	$(window).resize(function(){
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
		} catch(e) {
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
		} catch(e) {
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
		zoomifyInstances.forEach(function(instance) {
			try {
				if (instance && typeof instance.destroy === 'function') {
					instance.destroy();
					cleanedCount++;
				}
			} catch(e) {
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
		document.querySelectorAll('[data-tippy-root]').forEach(function(el) {
			try {
				if (el._tippy && typeof el._tippy.destroy === 'function') {
					el._tippy.destroy();
					cleanedCount++;
				}
			} catch(e) {
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
			dynamicStyles.forEach(function(style) {
				style.remove();
			});
			ArgonDebug.log(`已清理 ${dynamicStyles.length} 个动态样式`);
		}
	} catch(e) {
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
			dynamicScripts.forEach(function(script) {
				script.remove();
			});
			ArgonDebug.log(`已清理 ${dynamicScripts.length} 个动态脚本`);
		}
	} catch(e) {
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
	} catch(e) {
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
	if (highlightRenderJob) {
		argonCancelIdle(highlightRenderJob);
		highlightRenderJob = null;
	}
	codeOfBlocks = {};
	cleanupLazyloadObserver();
	cleanupZoomifyInstances();
	cleanupTippyInstances();
	cleanupDynamicStyles();
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
				loadGeetestScript(function() {
					initGeetestCaptchaCore();
				});
			}
		}
	} catch (e) {
		ArgonDebug.warn('Geetest init on PJAX failed:', e);
	}
}

function handleHashNavigation() {
	if (!location.hash) {
		return;
	}
	let target = null;
	try {
		target = document.getElementById(decodeURIComponent(location.hash.slice(1)));
	} catch (e) {
		target = document.querySelector(location.hash);
	}
	if (!target) {
		return;
	}
	try { highlightJsRender(); } catch (err) { ArgonDebug.error('highlightJsRender failed:', err); }
	try { lazyloadInit(); } catch (err) { ArgonDebug.error('lazyloadInit failed:', err); }
}

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
		return {total: 0, success: 0, failed: 0};
	}
	
	let successCount = 0;
	let failedCount = 0;
	
	ArgonDebug.log(`Found ${scripts.length} script tags in new page`);
	
	// 需求 4.3: 按照脚本在 DOM 中的顺序执行
	scripts.forEach((script, index) => {
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

$.pjax.defaults.timeout = 10000;
$.pjax.defaults.container = ['#primary', '#leftbar_part1_menu', '#leftbar_part2_inner', '.page-information-card-container', '#rightbar', '#wpadminbar'];
$.pjax.defaults.fragment = ['#primary', '#leftbar_part1_menu', '#leftbar_part2_inner', '.page-information-card-container', '#rightbar', '#wpadminbar'];

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
$(document).pjax("a[href]:not([no-pjax]):not(.no-pjax):not([target='_blank']):not([download]):not(.reference-link):not(.reference-list-backlink)")
.on('pjax:click', function(e, f, g){
	if (argonConfig.disable_pjax == true){
		e.preventDefault();
		return;
	}
	NProgress.remove();
	NProgress.start();
	pjaxLoading = true;
}).on('pjax:afterGetContainers', function(e, f, g) {
	pjaxScrollTop = 0;
	if ($("html").hasClass("banner-as-cover")){
		if (g.is(".page-link")){
			pjaxScrollTop = $("#content").offset().top - 80;
		}
	}
}).on('pjax:send', function() {
	NProgress.set(0.618);
}).on('pjax:beforeReplace', function(e, dom) {
	// ========== 需求 1.1-1.4: 清理旧页面的所有资源 ==========
	// 调用统一的资源清理管理器
	// 清理内容：Lazyload Observer、Zoomify、Tippy 实例、动态标签
	cleanupPjaxResources();
	
	// 更新 UI 状态
	if ($("#post_comment", dom[0]).length > 0){
		$("#fabtn_go_to_comment").removeClass("d-none");
	}else{
		$("#fabtn_go_to_comment").addClass("d-none");
	}
	
	// 处理滚动位置
	if ($("html").hasClass("banner-as-cover")){
		if (!$("#main").hasClass("article-list-home")){
			pjaxScrollTop = 0;
		}
	}
}).on('pjax:complete', function() {
	// ========== 需求 1.5, 1.6: 重新初始化所有功能模块 ==========
	pjaxLoading = false;
	NProgress.inc();
	
	// ========== 需求 4.1-4.5: 执行新页面中的内联脚本 ==========
	try {
		executeInlineScripts(document);
	} catch (err) {
		ArgonDebug.error('executeInlineScripts failed:', err);
	}
	
	updateArgonLongContentState();
	argonScheduleIdle(function() {
		var mathRoot = getArgonPostContentElement() || document.body;
		// MathJax 数学公式渲染
		try{
			if (MathJax != undefined){
				if (MathJax.Hub != undefined){
					MathJax.Hub.Typeset(mathRoot);
				}else if (typeof MathJax.typesetPromise === 'function'){
					MathJax.typesetPromise([mathRoot]);
				}else{
					MathJax.typeset([mathRoot]);
				}
			}
		}catch (err){}

		// KaTeX 数学公式渲染
		try{
			if (renderMathInElement != undefined){
				renderMathInElement(mathRoot,{
					delimiters: [
						{left: "$$", right: "$$", display: true},
						{left: "$", right: "$", display: false},
						{left: "\\(", right: "\\)", display: false}
					]
				});
			}
		}catch (err){}
	}, argonLongContentState.isLong ? 2000 : 800);

	// 初始化各个功能模块（添加错误处理确保单个模块失败不影响其他模块）
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
	
	$("html").trigger("resize");
	
	// 恢复滚动位置
	if (pjaxScrollTop > 0) {
		$("body,html").scrollTop(pjaxScrollTop);
		pjaxScrollTop = 0;
	}

	// 调用用户自定义的 PJAX 加载完成回调
	if (typeof(window.pjaxLoaded) == "function"){
		try{
			window.pjaxLoaded();
		}catch (err){
			ArgonDebug.error(err);
		}
	}

	NProgress.done();
}).on('pjax:end', function() {
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

window.addEventListener('hashchange', function() {
	handleHashNavigation();
});
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function() {
		handleHashNavigation();
	});
} else {
	handleHashNavigation();
}

/*Reference 跳转*/
$(document).on("click", ".reference-link , .reference-list-backlink" , function(e){
	e.preventDefault();
	$target = $($(this).attr("href"));
	$("body,html").animate({
		scrollTop: $target.offset().top - document.body.clientHeight / 2 - 75
	}, 500, 'easeOutExpo')
	setTimeout(function(){
		if ($target.is("li")){
			$(".space", $target).focus();
		}else{
			$target.focus();
		}
	}, 1);
});

/*Tags Dialog pjax 加载后自动关闭/
$(document).on("click" , "#blog_tags .tag" , function(){
	$("#blog_tags button.close").trigger("click");
});
$(document).on("click" , "#blog_categories .tag" , function(){
	$("#blog_categories button.close").trigger("click");
});

/*侧栏 & 顶栏菜单手机适配*/
!function(){
	$(document).on("click" , "#fabtn_open_sidebar, #open_sidebar" , function(){
		$("html").addClass("leftbar-opened");
		// 侧边栏打开时初始化移动端文章目录
		initMobileCatalog();
		// 如果有文章目录，默认展开
		setTimeout(function() {
			var catalogSection = $("#mobile_catalog_toggle").closest(".leftbar-mobile-collapse-section");
			if (catalogSection.length > 0 && !catalogSection.hasClass("expanded")) {
				catalogSection.addClass("expanded");
				initMobileCatalog();
				setTimeout(scrollMobileCatalogToActive, 200);
			}
		}, 100);
	});
	$(document).on("click" , "#sidebar_mask, #leftbar_close_btn" , function(){
		$("html").removeClass("leftbar-opened");
		// 关闭侧边栏时折叠所有面板
		$(".leftbar-mobile-collapse-section.expanded").removeClass("expanded");
	});
	$(document).on("click" , "#leftbar a[href]:not([no-pjax]):not([href^='#']):not(.has-submenu)" , function(){
		$("html").removeClass("leftbar-opened");
		// 关闭侧边栏时折叠所有面板
		$(".leftbar-mobile-collapse-section.expanded").removeClass("expanded");
	});
	// 移动端子菜单展开/收起
	$(document).on("click" , ".leftbar-mobile-menu-item.has-children > a.has-submenu" , function(e){
		e.preventDefault();
		$(this).parent().toggleClass("expanded");
	});
	// 移动端侧边栏搜索
	$(document).on("keydown" , "#leftbar_mobile_search_input" , function(e){
		if (e.keyCode != 13){
			return;
		}
		let word = $(this).val();
		if (word == ""){
			return;
		}
		$("html").removeClass("leftbar-opened");
		// 关闭侧边栏时折叠所有面板
		$(".leftbar-mobile-collapse-section.expanded").removeClass("expanded");
		searchPosts(word);
	});
	$(document).on("click" , "#navbar_global.show .navbar-nav a[href]:not([no-pjax]):not([href^='#'])" , function(){
		$("#navbar_global .navbar-toggler").click();
	});
	$(document).on("click" , "#navbar_global.show #navbar_search_btn_mobile" , function(){
		$("#navbar_global .navbar-toggler").click();
	});
	
	// ========== 移动端折叠面板交互==========
	$(document).on("click", ".leftbar-mobile-collapse-header", function(e) {
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
	var mobileCatalogHeaders = [];
	var mobileCatalogOffsets = [];
	var mobileCatalogOffsetDirty = true;

	function markMobileCatalogOffsetDirty() {
		mobileCatalogOffsetDirty = true;
	}

	function rebuildMobileCatalogOffsets() {
		mobileCatalogOffsets = mobileCatalogHeaders.map(function(header) {
			return {
				id: header.id,
				top: header.element.getBoundingClientRect().top + window.pageYOffset - 100
			};
		});
		mobileCatalogOffsetDirty = false;
	}
	
	function initMobileCatalog() {
		if (window.mobileCatalogInitialized) return;
		var $mobileContainer = $("#leftbar_mobile_catalog");
		var $postContent = $("#post_content");
		if ($mobileContainer.length === 0) return;
		if ($postContent.length === 0) return;
		
		// 直接生成目录，不依赖 headIndex 插件
		var headers = $postContent.find('h1, h2, h3, h4, h5, h6').toArray();
		if (headers.length === 0) {
			$mobileContainer.html('<div class="no-catalog">暂无目录</div>');
			return;
		}
		
		// 构建目录树
		var toc = [];
		var stack = [{ level: 0, children: toc }];
		mobileCatalogHeaders = [];
		
		headers.forEach(function(header, index) {
			var level = parseInt(header.tagName.charAt(1));
			var text = header.textContent.trim();
			var id = header.getAttribute('id');
			
			// 确保标题有ID
			if (!id) {
				id = 'mobile-heading-' + index;
				header.setAttribute('id', id);
			}
			
			var item = { id: id, text: text, level: level, children: [] };
			mobileCatalogHeaders.push({
				id: id,
				element: header
			});
			
			// 找到合适的父级
			while (stack.length > 1 && stack[stack.length - 1].level >= level) {
				stack.pop();
			}
			stack[stack.length - 1].children.push(item);
			stack.push({ level: level, children: item.children });
		});
		markMobileCatalogOffsetDirty();
		rebuildMobileCatalogOffsets();
		
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
		$mobileContainer.off('click.mobileCatalog').on('click.mobileCatalog', '.index-link', function(e) {
			e.preventDefault();
			var targetId = $(this).attr('href');
			if (targetId) {
				var $target = $(targetId);
				if ($target.length) {
					$mobileContainer.find('.index-item').removeClass('current');
					$(this).closest('.index-item').addClass('current');
					$('html, body').animate({
						scrollTop: $target.offset().top - 80
					}, 500, 'easeOutExpo');
					// 点击后关闭侧边栏
					setTimeout(function() {
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
		if ($mobileContainer.length === 0 || mobileCatalogHeaders.length === 0) return;
		
		var scrollTop = $(window).scrollTop();
		var currentId = null;

		if (mobileCatalogOffsetDirty) {
			rebuildMobileCatalogOffsets();
		}

		var left = 0;
		var right = mobileCatalogOffsets.length - 1;
		while (left <= right) {
			var mid = Math.floor((left + right) / 2);
			if (scrollTop >= mobileCatalogOffsets[mid].top) {
				currentId = mobileCatalogOffsets[mid].id;
				left = mid + 1;
			} else {
				right = mid - 1;
			}
		}
		
		if (currentId) {
			$mobileContainer.find('.index-item').removeClass('current');
			$mobileContainer.find('.index-link[href="#' + currentId + '"]').closest('.index-item').addClass('current');
		}
	}
	
	// 重置移动端目录状态（由PJAX 调用）
	window.resetMobileCatalog = function() {
		window.mobileCatalogInitialized = false;
		mobileCatalogHeaders = [];
		mobileCatalogOffsets = [];
		mobileCatalogOffsetDirty = true;
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
	var mobileCatalogScrollTimer = null;
	$(window).on("scroll.mobileCatalog", function() {
		if (!window.mobileCatalogInitialized) return;
		// 节流处理
		if (mobileCatalogScrollTimer) return;
		mobileCatalogScrollTimer = setTimeout(function() {
			mobileCatalogScrollTimer = null;
			// 更新高亮状态
			updateMobileCatalogHighlight();
			// 只在侧边栏打开且目录展开时滚动
			if ($("html").hasClass("leftbar-opened") && 
				$("#mobile_catalog_toggle").closest(".leftbar-mobile-collapse-section").hasClass("expanded")) {
				scrollMobileCatalogToActive();
			}
		}, 150);
	});
	$(window).on("resize.mobileCatalog", function() {
		markMobileCatalogOffsetDirty();
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
	$(document).on("click", "#mobile-todo-collapse-btn", function(e) {
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
	$(document).on("click", "#mobile-todo-add-btn", function() {
		addMobileTodo();
	});
	
	$(document).on("keypress", "#mobile-todo-input", function(e) {
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
			success: function(res) {
				if (res.success) {
					var list = $("#mobile-todo-list");
					list.find(".mobile-todo-empty").remove();
					
					var newItem = $('<li class="mobile-todo-item" data-id="' + res.data.id + '">' +
						'<span class="mobile-todo-content">' + $("<div>").text(content).html() + '</span>' +
						'<button class="mobile-todo-complete-btn" title="完成"><i class="fa fa-check"></i></button>' +
						'</li>');
					list.prepend(newItem);
					
					input.val("");
					updateMobileTodoCount();
					
					// 同步到桌面端
					syncTodoToDesktop(res.data.id, content, "add");
				}
				btn.prop("disabled", false);
			},
			error: function() {
				btn.prop("disabled", false);
			}
		});
	}
	
	// 移动端TODO 验证码相关变量
	var mobilePendingUrgeBtn = null;
	var mobileGeetestCaptchaObj = null;
	
	// 完成/删除/催促TODO
	$(document).on("click", "#mobile-todo-list .mobile-todo-complete-btn, #mobile-todo-list .mobile-todo-delete-btn, #mobile-todo-list .mobile-todo-urge-btn", function() {
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
				success: function(res) {
					if (res.success) {
						item.addClass("todo-completed");
						btn.replaceWith('<button class="mobile-todo-delete-btn" title="删除"><i class="fa fa-trash"></i></button>');
						updateMobileTodoCount();
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
				success: function(res) {
					if (res.success) {
						item.addClass("todo-completing");
						setTimeout(function() {
							item.remove();
							updateMobileTodoCount();
							if ($("#mobile-todo-list .mobile-todo-item").length === 0) {
								$("#mobile-todo-list").html('<li class="mobile-todo-empty">暂无待办事项</li>');
							}
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
					captchaContainer.slideDown(200);
					mobilePendingUrgeBtn = btn;
					
					if (window.mobileTodoConfig.captchaType === 'geetest') {
						// 极验验证码
						if (!mobileGeetestCaptchaObj && typeof initGeetest4 === 'function') {
							initGeetest4({
								captchaId: window.mobileTodoConfig.geetestId,
								product: 'bind'
							}, function(captcha) {
								mobileGeetestCaptchaObj = captcha;
								captcha.onReady(function() {
									captcha.showCaptcha();
								}).onSuccess(function() {
									var result = captcha.getValidate();
									if (result && mobilePendingUrgeBtn) {
										var urgeId = mobilePendingUrgeBtn.closest(".mobile-todo-item").data("id");
										doMobileUrgeGeetest(mobilePendingUrgeBtn, urgeId, result);
									}
								}).onError(function() {
									captchaContainer.slideUp(200);
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
	$(document).on("click", "#mobile-todo-captcha-submit", function() {
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
	$(document).on("keypress", "#mobile-todo-captcha-input", function(e) {
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
			success: function(res) {
				handleMobileUrgeResponse(btn, res);
			},
			error: function() {
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
			success: function(res) {
				handleMobileUrgeResponse(btn, res);
				if (mobileGeetestCaptchaObj) mobileGeetestCaptchaObj.reset();
			},
			error: function() {
				btn.prop("disabled", false).html('<i class="fa fa-bell"></i>');
			}
		});
	}
	
	// 处理移动端催促响应
	function handleMobileUrgeResponse(btn, res) {
		var captchaContainer = $(".mobile-todo-captcha-container");
		
		if (res.success) {
			btn.addClass("urged").html('<i class="fa fa-check"></i>');
			captchaContainer.slideUp(200);
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
		} else if (action === "complete") {
			var item = desktopList.find('.todo-item[data-id="' + id + '"]');
			item.addClass("todo-completed");
			item.find(".todo-complete-btn").replaceWith('<button class="todo-delete-btn" title="删除"><i class="fa fa-trash"></i></button>');
		} else if (action === "delete") {
			desktopList.find('.todo-item[data-id="' + id + '"]').remove();
		} else if (action === "urge") {
			var urgeBtn = desktopList.find('.todo-item[data-id="' + id + '"] .todo-urge-btn');
			urgeBtn.addClass("urged").prop("disabled", true).html('<i class="fa fa-check"></i>');
		}
	}
}();

/*折叠区块小工具*/
$(document).on("click" , ".collapse-block .collapse-block-title" , function(){
	let block = $(this).parent();
	$(block).toggleClass("collapsed");
	let inner = $(".collapse-block-body", block);
	if (block.hasClass("collapsed")){
		inner.stop(true, false).slideUp(250, 'easeOutCirc');
	}else{
		inner.stop(true, false).slideDown(300, 'easeOutCirc');
	}
	$("html").trigger("scroll");
});

/*获得 Github Repo Shortcode 信息卡内容*/
function getGithubInfoCardContent(){
	$(".github-info-card").each(function(){
		(function($this){
			if ($this.attr("data-getdata") == "backend"){
				$(".github-info-card-description" , $this).html($this.attr("data-description"));
				$(".github-info-card-stars" , $this).html($this.attr("data-stars"));
				$(".github-info-card-forks" , $this).html($this.attr("data-forks"));
				return;
			}
			$(".github-info-card-description" , $this).html("Loading...");
			$(".github-info-card-stars" , $this).html("-");
			$(".github-info-card-forks" , $this).html("-");
			author = $this.attr("data-author");
			project = $this.attr("data-project");
			$.ajax({
				url : "https://api.github.com/repos/" + author + "/" + project,
				type : "GET",
				dataType : "json",
				success : function(result){
					description = result.description;
					if (result.homepage != "" && result.homepage != null){
						description += " <a href='" + result.homepage + "' target='_blank' no-pjax>" + result.homepage + "</a>"
					}
					$(".github-info-card-description" , $this).html(description);
					$(".github-info-card-stars" , $this).html(result.stargazers_count);
					$(".github-info-card-forks" , $this).html(result.forks_count);
				},
				error : function(xhr){
					if (xhr.status == 404){
						$(".github-info-card-description" , $this).html(__("找不到该 Repo"));
					}else{
						$(".github-info-card-description" , $this).html(__("获取 Repo 信息失败"));
					}
				}
			});
		})($(this));
	});
}
getGithubInfoCardContent();

/*说说点赞*/
$(document).on("click" , ".shuoshuo-upvote" , function(){
	$this = $(this);
	ID = $this.attr("data-id");
	$this.addClass("shuoshuo-upvoting");
	$.ajax({
		url : argonConfig.wp_path + "wp-admin/admin-ajax.php",
		type : "POST",
		dataType : "json",
		data : {
			action: "upvote_shuoshuo",
			shuoshuo_id : ID,
		},
		success : function(result){
			$this.removeClass("shuoshuo-upvoting");
			if (result.status == "success"){
				$(".shuoshuo-upvote-num" , $this).html(result.total_upvote);
				$("i.fa-thumbs-o-up" , $this).addClass("fa-thumbs-up").removeClass("fa-thumbs-o-up");
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
			}else{
				$(".shuoshuo-upvote-num" , $this).html(result.total_upvote);
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
		error : function(xhr){
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
function foldLongShuoshuo(){
	if (argonConfig.fold_long_shuoshuo == false){
		return;
	}
	$("#main .shuoshuo-foldable > .shuoshuo-content").each(function(){
		if ($(this).hasClass("shuoshuo-unfolded")){
			return;
		}
		if (this.clientHeight > 400){
			$(this).addClass("shuoshuo-folded");
			$(this).append("<div class='show-full-shuoshuo'><button class='btn btn-outline-primary'><i class='fa fa-angle-down' aria-hidden='true'></i> " + __("展开") + "</button></div>");
		}
	});
}
foldLongShuoshuo();
$(document).on("click" , ".show-full-shuoshuo" , function(){
	$(this).parent().removeClass("shuoshuo-folded").addClass("shuoshuo-unfolded");
});

//颜色计算
function rgb2hsl(R,G,B){
	let r = R / 255;
	let g = G / 255;
	let b = B / 255;

	let var_Min = Math.min(r, g, b);
	let var_Max = Math.max(r, g, b);
	let del_Max = var_Max - var_Min;

	let H, S, L = (var_Max + var_Min) / 2;

	if (del_Max == 0){
		H = 0;
		S = 0;
	}else{
		if (L < 0.5){
			S = del_Max / (var_Max + var_Min);
		}else{
			S = del_Max / (2 - var_Max - var_Min);
		}

		del_R = (((var_Max - r) / 6) + (del_Max / 2)) / del_Max;
		del_G = (((var_Max - g) / 6) + (del_Max / 2)) / del_Max;
		del_B = (((var_Max - b) / 6) + (del_Max / 2)) / del_Max;

		if (r == var_Max){
			H = del_B - del_G;
		}
		else if (g == var_Max){
			H = (1 / 3) + del_R - del_B;
		}
		else if (b == var_Max){
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
function Hue_2_RGB(v1,v2,vH){
	if (vH < 0) vH += 1;
	if (vH > 1) vH -= 1;
	if ((6 * vH) < 1) return (v1 + (v2 - v1) * 6 * vH);
	if ((2 * vH) < 1) return v2;
	if ((3 * vH) < 2) return (v1 + (v2 - v1) * ((2 / 3) - vH) * 6);
	return v1;
}
function hsl2rgb(h,s,l){
	let r, g, b, var_1, var_2;
	if (s == 0){
		r = l;
		g = l;
		b = l;
	}
	else{
		if (l < 0.5){
			var_2 = l * (1 + s);
		}
		else{
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
function rgb2hex(r,g,b){
	let hex = new Array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	let rh, gh, bh;
	rh = "", gh ="", bh="";
	while (rh.length < 2){
		rh = hex[r%16] + rh;
		r = Math.floor(r / 16);
	}
	while (gh.length < 2){
		gh = hex[g%16] + gh;
		g = Math.floor(g / 16);
	}
	while (bh.length < 2){
		bh = hex[b%16] + bh;
		b = Math.floor(b / 16);
	}
	return "#" + rh + gh + bh;
}
function hex2rgb(hex){
	//hex: #XXXXXX
	let dec = {
		'0': 0, '1': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8, '9': 9, 'A': 10, 'B': 11, 'C': 12, 'D': 13, 'E': 14, 'F': 15
	};
	return {
		'R': (dec[hex.substr(1,1)] * 16 + dec[hex.substr(2,1)]),//0~255
		'G': (dec[hex.substr(3,1)] * 16 + dec[hex.substr(4,1)]),
		'B': (dec[hex.substr(5,1)] * 16 + dec[hex.substr(6,1)]),
		'r': (dec[hex.substr(1,1)] * 16 + dec[hex.substr(2,1)]) / 255,//0~1
		'g': (dec[hex.substr(3,1)] * 16 + dec[hex.substr(4,1)]) / 255,
		'b': (dec[hex.substr(5,1)] * 16 + dec[hex.substr(6,1)]) / 255
	};
}
function rgb2gray(R,G,B){
	return Math.round(R * 0.299 + G * 0.587 + B * 0.114);
}
function hex2gray(hex){
	let rgb_array = hex2rgb(hex);
	return hex2gray(rgb_array['R'], rgb_array['G'], rgb_array['B']);
}
function rgb2str(rgb){
	return rgb['R'] + "," + rgb['G'] + "," + rgb['B'];
}
function hex2str(hex){
	return rgb2str(hex2rgb(hex));
}
//颜色选择器& 切换主题色
if ($("meta[name='argon-enable-custom-theme-color']").attr("content") == 'true'){
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
function pickrObjectToHEX(color){
	let HEXA = color.toHEXA();
	return ("#" + HEXA[0] + HEXA[1] + HEXA[2]).toUpperCase();
}
function updateThemeColor(color, setcookie){
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


	if (rgb2gray(RGB['R'], RGB['G'], RGB['B']) < 50){
		$("html").addClass("themecolor-toodark");
	}else{
		$("html").removeClass("themecolor-toodark");
	}

	$("meta[name='theme-color']").attr("content", themecolor);
	$("meta[name='theme-color-rgb']").attr("content", themecolor_rgbstr);

	if (setcookie){
		setCookie("argon_custom_theme_color", themecolor, 365);
	}
}

/*打字效果*/
function typeEffect($element, text, now, interval){
	if (now > text.length){
		setTimeout(function(){
			$element.removeClass("typing-effect");
		}, 1000);
		return;
	}
	$element[0].innerText = text.substring(0, now);
	setTimeout(function(){typeEffect($element, text, now + 1, interval)}, interval);
}
function startTypeEffect($element, text, interval){
	$element.addClass("typing-effect");
	$element.attr("style", "--animation-cnt: " + Math.ceil(text.length * interval / 1000));
	typeEffect($element, text, 1, interval);
}
!function(){
	if ($(".banner-title").data("interval") != undefined){
		let interval = $(".banner-title").data("interval");
		let $title = $(".banner-title-inner");
		let $subTitle = $(".banner-subtitle");
		startTypeEffect($title, $title.data("text"), interval);
		if (!$subTitle.length){
			return;
		}
		setTimeout(function(){startTypeEffect($subTitle, $subTitle.data("text"), interval);}, Math.ceil($title.data("text").length * interval / 1000) * 1000);
	}
}();

/*一言*/
if ($(".hitokoto").length > 0){
	$.ajax({
		type: 'GET',
		url: "https://v1.hitokoto.cn",
		success: function(result){
			$(".hitokoto").text(result.hitokoto);
		},
		error: function(result){
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
var highlightRenderJob = null;
var highlightClipboard = null;
function getCodeFromBlock(block){
	if (codeOfBlocks[block.id] != undefined){
		return codeOfBlocks[block.id];
	}
	let lines = $(".hljs-ln-code", block);
	let res = "";
	if (lines.length > 0) {
		for (let i = 0; i < lines.length - 1; i++){
			res += lines[i].innerText;
			res += "\n";
		}
		res += lines[lines.length - 1].innerText;
	} else {
		var code = $(block).children("code")[0] || block;
		res = code.innerText || code.textContent || "";
	}
	codeOfBlocks[block.id] = res;
	return res;
}

function initHighlightClipboard() {
	if (highlightClipboard || typeof ClipboardJS === "undefined") {
		return;
	}
	highlightClipboard = new ClipboardJS(".hljs-control-copy", {
		text: function(trigger) {
			var block = $(trigger).closest("pre.hljs-codeblock")[0];
			return block ? getCodeFromBlock(block) : "";
		}
	});
	highlightClipboard.on('success', function(e) {
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
	highlightClipboard.on('error', function(e) {
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

function prepareCodeBlock(block) {
	if ($(block).hasClass("no-hljs")){
		return null;
	}
	if ($(block).attr("hljs-codeblock-inner") !== undefined) {
		return null;
	}
	return block;
}

function renderHighlightBlock(block) {
	if (!block || $(block).hasClass("no-hljs") || $(block).attr("hljs-codeblock-inner") !== undefined) {
		return;
	}
	var $pre = $(block).parent();
	if (!$pre.attr("id")) {
		$pre.attr("id", randomString());
	}
	var longState = argonLongContentState || updateArgonLongContentState();
	var shouldUseLiteMode = longState.isLong && ((block.textContent || "").length > 60000 || (block.textContent || "").split("\n").length > 500);
	if (!shouldUseLiteMode) {
		hljs.highlightBlock(block);
		hljs.lineNumbersBlock(block, {singleLine: true});
	} else {
		$pre.addClass("hljs-lite-codeblock");
	}
	$pre.addClass("hljs-codeblock");
	if (argonConfig.code_highlight.hide_linenumber){
		$pre.addClass("hljs-hide-linenumber");
	}
	if (argonConfig.code_highlight.break_line){
		$pre.addClass("hljs-break-line");
	}
	if (argonConfig.code_highlight.transparent_linenumber){
		$pre.addClass("hljs-transparent-linenumber");
	}
	$(block).attr("hljs-codeblock-inner", "");
	if ($pre.children(".hljs-control").length === 0) {
		$pre.append(`<div class="hljs-control hljs hljs-title">
				<div class="hljs-control-btn hljs-control-toggle-linenumber" tooltip-hide-linenumber="` + __("隐藏行号") + `" tooltip-show-linenumber="` + __("显示行号") + `">
					<i class="fa fa-list"></i>
				</div>
				<div class="hljs-control-btn hljs-control-toggle-break-line" tooltip-enable-breakline="` + __("开启折行") + `" tooltip-disable-breakline="` + __("关闭折行") + `">
					<i class="fa fa-align-left"></i>
				</div>
				<div class="hljs-control-btn hljs-control-copy" tooltip="` + __("复制") + `">
					<i class="fa fa-clipboard"></i>
				</div>
				<div class="hljs-control-btn hljs-control-fullscreen" tooltip-fullscreen="` + __("全屏") + `" tooltip-exit-fullscreen="` + __("退出全屏") + `">
					<i class="fa fa-arrows-alt"></i>
				</div>
			</div>`);
	}
}

function highlightJsRender(){
	if (typeof(hljs) == "undefined"){
		return;
	}
	if (typeof(argonConfig.code_highlight.enable) == "undefined"){
		return;
	}
	if (!argonConfig.code_highlight.enable){
		return;
	}
	initHighlightClipboard();
	if (highlightRenderJob) {
		argonCancelIdle(highlightRenderJob);
		highlightRenderJob = null;
	}
	$("article pre.code").each(function(index, block) {
		if ($(block).hasClass("no-hljs")){
			return;
		}
		if ($(block).children("code").length === 0) {
			$(block).html("<code>" + $(block).html() + "</code>");
		}
	});
	var blocks = $("article pre > code").toArray().map(prepareCodeBlock).filter(Boolean);
	var longState = updateArgonLongContentState();
	var batchSize = longState.isLong ? 1 : 4;
	var index = 0;

	function runBatch(deadline) {
		var start = Date.now();
		while (index < blocks.length) {
			renderHighlightBlock(blocks[index++]);
			if (longState.isLong && (!deadline || deadline.timeRemaining() < 4 || Date.now() - start > 12)) {
				break;
			}
			if (!longState.isLong && Date.now() - start > 20) {
				break;
			}
			if (longState.isLong && (index % batchSize) === 0) {
				break;
			}
		}
		if (index < blocks.length) {
			highlightRenderJob = argonScheduleIdle(runBatch, 1000);
		} else {
			highlightRenderJob = null;
		}
	}

	if (blocks.length > 0) {
		highlightRenderJob = argonScheduleIdle(runBatch, 800);
	}
}
$(document).ready(function(){
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
	highlightJsRender();
	waterflowInit();
});
$(document).on("click" , ".hljs-control-fullscreen" , function(){
	let block = $(this).parent().parent();
	block.toggleClass("hljs-codeblock-fullscreen");
	if (block.hasClass("hljs-codeblock-fullscreen")){
		$("html").addClass("noscroll codeblock-fullscreen");
	}else{
		$("html").removeClass("noscroll codeblock-fullscreen");
	}
});
$(document).on("click" , ".hljs-control-toggle-break-line" , function(){
	let block = $(this).parent().parent();
	block.toggleClass("hljs-break-line");
});
$(document).on("click" , ".hljs-control-toggle-linenumber" , function(){
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
!function(){
void 0;
}();

/* ========== Modern UI Enhancements - 现代化交互动画增强========== */
(function() {
    'use strict';

    // 1. 图片加载动画
    function initImageLoadAnimation() {
        var images = document.querySelectorAll('article img[loading="lazy"], .post-thumbnail img');
        images.forEach(function(img) {
            if (img.dataset.loadAnimInit) return;
            img.dataset.loadAnimInit = 'true';
            if (img.complete) { img.classList.add('loaded'); }
            else { img.addEventListener('load', function() { this.classList.add('loaded'); }); }
        });
    }

    // 3. 滚动入场动画
    function initScrollAnimations() {
        if (!('IntersectionObserver' in window)) return;
        var animatedElements = document.querySelectorAll('.article-list article.post, .comment-item, .timeline-item, .friend-link-item, #leftbar .card, #rightbar .card');
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) { entry.target.classList.add('animate-in'); observer.unobserve(entry.target); }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        animatedElements.forEach(function(el) { if (!el.classList.contains('animate-in')) observer.observe(el); });
    }

    // 4. 平滑滚动
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            if (anchor.dataset.smoothScrollInit) return;
            anchor.dataset.smoothScrollInit = 'true';
            anchor.addEventListener('click', function(e) {
                var targetId = this.getAttribute('href');
                if (targetId === '#') return;
                var target = document.querySelector(targetId);
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            });
        });
    }

    // 5. 页面加载进度条
    function initLoadingBar() {
        if (document.getElementById('page-loading-bar')) return;
        var bar = document.createElement('div');
        bar.id = 'page-loading-bar';
        bar.style.width = '0%';
        document.body.appendChild(bar);
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 10;
            if (progress >= 90) { clearInterval(interval); progress = 90; }
            bar.style.width = progress + '%';
        }, 100);
        window.addEventListener('load', function() {
            clearInterval(interval);
            bar.style.width = '100%';
            setTimeout(function() { bar.style.opacity = '0'; setTimeout(function() { bar.remove(); }, 300); }, 200);
        });
    }

    // 6. PJAX 加载动画
    function initPjaxAnimations() {
        if (typeof jQuery === 'undefined') return;
        jQuery(document).on('pjax:start', function() {
            jQuery('#primary').addClass('pjax-loading');
            var bar = document.getElementById('page-loading-bar');
            if (!bar) { bar = document.createElement('div'); bar.id = 'page-loading-bar'; document.body.appendChild(bar); }
            bar.style.opacity = '1'; bar.style.width = '30%';
            setTimeout(function() { bar.style.width = '60%'; }, 200);
        });
        jQuery(document).on('pjax:end', function() {
            jQuery('#primary').removeClass('pjax-loading');
            var bar = document.getElementById('page-loading-bar');
            if (bar) { bar.style.width = '100%'; setTimeout(function() { bar.style.opacity = '0'; setTimeout(function() { bar.remove(); }, 300); }, 200); }
            setTimeout(function() { initImageLoadAnimation(); initScrollAnimations(); initSmoothScroll(); }, 100);
        });
    }

    // 7. 主题切换动画 - 已在 header.php 中通过 setDarkmode 函数处理，此处不再重复
    function initThemeTransition() {
        // 移除 MutationObserver 避免无限循环导致内存泄漏
        // 主题切换过渡效果已在 setDarkmode() 函数中实现
    }

    // 8. 减少动画偏好检查
    function checkReducedMotion() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('reduced-motion');
        }
    }

    // 初始化
    function init() {
        checkReducedMotion();
        initImageLoadAnimation();
        initScrollAnimations();
        initSmoothScroll();
        initPjaxAnimations();
        initThemeTransition();
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
    else { init(); }
    if (document.readyState !== 'complete') { initLoadingBar(); }
})();
/* ========== End of Modern UI Enhancements ========== */


// ========== jQuery Easing 备用（确保在所有脚本加载后仍可用）==========
(function() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    if (!$.easing) $.easing = {};
    if (!$.easing.easeOutCirc) {
        $.easing.easeOutCirc = function(x) {
            return Math.sqrt(1 - Math.pow(x - 1, 2));
        };
    }
    if (!$.easing.easeOutExpo) {
        $.easing.easeOutExpo = function(x) {
            return x === 1 ? 1 : 1 - Math.pow(2, -10 * x);
        };
    }
})();
