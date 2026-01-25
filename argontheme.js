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
		if ($("article.post.post-full").length == 0){
			hideReadingProgress();
		}else{
			let a = $window.scrollTop() - ($("article.post.post-full").offset().top - 80);
			let b = $("article.post.post-full").outerHeight() + 50 - $window.height();
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
		changefabtnDisplayStatus();
	});
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
		if (!$("#post_comment").hasClass("no-need-captcha")){
			// 检查是否使用Geetest验证码
		if ($("#geetest-captcha").length > 0) {
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
			private_mode: privateMode,
			enable_mailnotice: mailNotice,
			argon_nonce: $("#argon_comment_nonce").val()
		};

		// 根据验证码类型添加相应参数
		if ($("#geetest-captcha").length > 0) {
			// 检查Geetest库加载状态
			if (typeof window.geetestLoadFailed !== 'undefined' && window.geetestLoadFailed) {
				// 重新启用按钮和表单元素
				$("#post_comment").removeClass("sending");
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_name").removeAttr("disabled");
				$("#post_comment_email").removeAttr("disabled");
				$("#post_comment_captcha").removeAttr("disabled");
				$("#post_comment_link").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_reply_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-send'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送"));
				
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
				// 重新启用按钮和表单元素
				$("#post_comment").removeClass("sending");
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_name").removeAttr("disabled");
				$("#post_comment_email").removeAttr("disabled");
				$("#post_comment_captcha").removeAttr("disabled");
				$("#post_comment_link").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_reply_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-send'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送"));
				
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
		} else {
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
				$("#post_comment").removeClass("sending");
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_name").removeAttr("disabled");
				$("#post_comment_email").removeAttr("disabled");
				$("#post_comment_link").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_reply_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-send'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送"));
				$("#post_comment").removeClass("show-extra-input post-comment-force-privatemode-on post-comment-force-privatemode-off");
				if (!result.isAdmin){
					$("#post_comment_captcha").removeAttr("disabled");
				}

				//判断是否有错误
				if (result.status == "failed"){
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
					$("#geetest_lot_number").val("");
					$("#geetest_captcha_output").val("");
					$("#geetest_pass_token").val("");
					$("#geetest_gen_time").val("");
					// 重置验证状态标志位
					window.geetestVerified = false;
					// 重置自动提交标记
					window.geetestAutoSubmitting = false;
					// 重置 Geetest 实例，确保下一次可以重新验证
					if (window.geetestCaptcha) {
						try {
							window.geetestCaptcha.reset();
						} catch (e) {
							ArgonDebug.warn('Geetest reset error:', e);
						}
					}
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
				$("#post_comment").removeClass("sending");
				$("#post_comment_content").removeAttr("disabled");
				$("#post_comment_name").removeAttr("disabled");
				$("#post_comment_email").removeAttr("disabled");
				$("#post_comment_link").removeAttr("disabled");
				$("#post_comment_send").removeAttr("disabled");
				$("#post_comment_reply_cancel").removeAttr("disabled");
				$("#post_comment_send .btn-inner--icon.hide-on-comment-editing").html("<i class='fa fa-send'></i>");
				$("#post_comment_send .btn-inner--text.hide-on-comment-editing").html(__("发送"));
				$("#post_comment").removeClass("show-extra-input post-comment-force-privatemode-on post-comment-force-privatemode-off");
				if (!result.isAdmin){
					$("#post_comment_captcha").removeAttr("disabled");
				}

				// 重置Geetest验证状态
				window.geetestVerified = false;
				// 重置自动提交标记
				window.geetestAutoSubmitting = false;
				$("#geetest_lot_number").val("");
				$("#geetest_captcha_output").val("");
				$("#geetest_pass_token").val("");
				$("#geetest_gen_time").val("");
				// 重置 Geetest 实例，确保下一次生成新的pass_token
				if (window.geetestCaptcha) {
					try { window.geetestCaptcha.reset(); } catch (e) {}
				}

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
				errorMsg += __("网站格式错误 (不是 http(s):// 开头") + "</br>";
			}
			
			// 如果基本表单验证失败，显示错误信息并返回
			if (isError){
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
 * 清理 Mermaid 实例
 * @returns {void}
 */
function cleanupMermaidInstances() {
	try {
		// 清理已渲染的图表记录
		if (typeof MermaidRenderer !== 'undefined' && MermaidRenderer.rendered) {
			const count = MermaidRenderer.rendered.size;
			MermaidRenderer.rendered.clear();
			if (count > 0) {
				ArgonDebug.log(`已清理 ${count} 个 Mermaid 图表记录`);
			}
		}
		
		// 移除 Mermaid 容器的事件监听器
		document.querySelectorAll('.mermaid-container').forEach(function(container) {
			// 移除工具栏事件监听器
			const toolbar = container.querySelector('.mermaid-toolbar');
			if (toolbar) {
				// 克隆节点以移除所有事件监听器
				const newToolbar = toolbar.cloneNode(true);
				toolbar.parentNode.replaceChild(newToolbar, toolbar);
			}
			
			// 清理全屏模式
			if (container._fullscreenCleanup) {
				container._fullscreenCleanup();
			}
		});
		
		ArgonDebug.log('Mermaid 实例已清理');
	} catch(e) {
		ArgonDebug.warn('清理 Mermaid 实例失败:', e);
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
		// 清理 Mermaid 相关的事件监听器
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
 * - Mermaid 实例
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
	cleanupZoomifyInstances();
	cleanupTippyInstances();
	cleanupMermaidInstances();
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
 * 1. pjax:beforeReplace - 统一清理资源（LazyLoad Observer、Zoomify、Tippy、Mermaid）
 * 2. pjax:complete - 单次初始化所有功能模块，添加错误处理，包含 Mermaid 渲染
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
	// 清理内容：Lazyload Observer、Zoomify、Tippy、Mermaid 实例、动态标签
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
	
	// MathJax 数学公式渲染
	try{
		if (MathJax != undefined){
			if (MathJax.Hub != undefined){
				MathJax.Hub.Typeset();
			}else{
				MathJax.typeset();
			}
		}
	}catch (err){}
	
	// KaTeX 数学公式渲染
	try{
		if (renderMathInElement != undefined){
			renderMathInElement(document.body,{
				delimiters: [
					{left: "$$", right: "$$", display: true},
					{left: "$", right: "$", display: false},
					{left: "\\(", right: "\\)", display: false}
				]
			});
		}
	}catch (err){}

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
	
	// Mermaid 图表渲染（需求 3.6: 页面切换时重新渲染）
	try {
		if (typeof MermaidRenderer !== 'undefined' && MermaidRenderer.renderAllCharts) {
			MermaidRenderer.renderAllCharts();
		}
	} catch (err) {
		ArgonDebug.error('MermaidRenderer.renderAllCharts failed:', err);
	}
	
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
	
	function initMobileCatalog() {
		if (window.mobileCatalogInitialized) return;
		var $mobileContainer = $("#leftbar_mobile_catalog");
		var $postContent = $("#post_content");
		if ($mobileContainer.length === 0) return;
		if ($postContent.length === 0) return;
		
		// 直接生成目录，不依赖 headIndex 插件
		var $headers = $postContent.find('h1, h2, h3, h4, h5, h6');
		if ($headers.length === 0) {
			$mobileContainer.html('<div class="no-catalog">暂无目录</div>');
			return;
		}
		
		// 构建目录树
		var toc = [];
		var stack = [{ level: 0, children: toc }];
		
		$headers.each(function(index) {
			var $h = $(this);
			var level = parseInt(this.tagName.charAt(1));
			var text = $h.text().trim();
			var id = $h.attr('id');
			
			// 确保标题有ID
			if (!id) {
				id = 'mobile-heading-' + index;
				$h.attr('id', id);
			}
			
			var item = { id: id, text: text, level: level, children: [] };
			
			// 找到合适的父级
			while (stack.length > 1 && stack[stack.length - 1].level >= level) {
				stack.pop();
			}
			stack[stack.length - 1].children.push(item);
			stack.push({ level: level, children: item.children });
		});
		
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
		var $postContent = $("#post_content");
		if ($mobileContainer.length === 0 || $postContent.length === 0) return;
		
		var scrollTop = $(window).scrollTop();
		var $headers = $postContent.find('h1, h2, h3, h4, h5, h6');
		var currentId = null;
		
		$headers.each(function() {
			var $h = $(this);
			var top = $h.offset().top - 100;
			if (scrollTop >= top) {
				currentId = $h.attr('id');
			}
		});
		
		if (currentId) {
			$mobileContainer.find('.index-item').removeClass('current');
			$mobileContainer.find('.index-link[href="#' + currentId + '"]').closest('.index-item').addClass('current');
		}
	}
	
	// 重置移动端目录状态（由PJAX 调用）
	window.resetMobileCatalog = function() {
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
function getCodeFromBlock(block){
	if (codeOfBlocks[block.id] != undefined){
		return codeOfBlocks[block.id];
	}
	let lines = $(".hljs-ln-code", block);
	let res = "";
	for (let i = 0; i < lines.length - 1; i++){
		res += lines[i].innerText;
		res += "\n";
	}
	res += lines[lines.length - 1].innerText;
	codeOfBlocks[block.id] = res;
	return res;
}

/**
 * 在代码高亮之前转换 Mermaid 代码块
 * 将 <pre><code class="language-mermaid"> 转换为 <div class="mermaid-from-codeblock">
 * 这样可以避免代码高亮干扰 Mermaid 渲染
 */
function convertMermaidCodeblocks(){
	// 支持多种代码块格式
	const selectors = [
		'pre > code.language-mermaid',  // 标准 Markdown 格式（最常见）
		'pre > code.mermaid',           // 简化格式
		'code.language-mermaid',        // 无 pre 包裹
		'pre[data-lang="mermaid"]'      // 自定义属性格式
	];
	
	let processedCount = 0;
	
	selectors.forEach(selector => {
		document.querySelectorAll(selector).forEach(element => {
			// 避免重复处理
			if (element.dataset.mermaidProcessed) {
				return;
			}
			
			// 提取代码
			let code = element.textContent;
			
			// 清理代码：移除前后空白，但保留内部换行
			code = code.trim();
			
			// 如果代码为空，跳过
			if (!code) {
				return;
			}
			
			// 移除每行开头的统一缩进（保持相对缩进）
			const lines = code.split('\n');
			if (lines.length > 1) {
				// 找到最小缩进
				let minIndent = Infinity;
				lines.forEach(line => {
					if (line.trim().length > 0) {
						const indent = line.match(/^\s*/)[0].length;
						if (indent < minIndent) {
							minIndent = indent;
						}
					}
				});
				
				// 移除最小缩进
				if (minIndent > 0 && minIndent !== Infinity) {
					code = lines.map(line => {
						if (line.trim().length === 0) {
							return '';
						}
						return line.substring(minIndent);
					}).join('\n');
				}
			}
			
			// 创建容器
			const container = document.createElement('div');
			container.className = 'mermaid-from-codeblock';
			container.textContent = code;
			container.dataset.processed = 'true';
			
			// 调试日志
			if (typeof console !== 'undefined' && console.log) {
				console.log('[Mermaid 转换] 代码长度:', code.length);
				console.log('[Mermaid 转换] 代码内容:', code.substring(0, 200));
				console.log('[Mermaid 转换] 容器 textContent:', container.textContent.substring(0, 200));
			}
			
			// 替换元素
			const targetElement = element.closest('pre') || element;
			if (targetElement.parentNode) {
				targetElement.parentNode.replaceChild(container, targetElement);
				processedCount++;
			}
			
			// 标记已处理
			element.dataset.mermaidProcessed = 'true';
		});
	});
	
	if (processedCount > 0 && typeof console !== 'undefined' && console.log) {
		console.log('[Mermaid] 转换了 ' + processedCount + ' 个代码块');
	}
}

function highlightJsRender(){
	// 在代码高亮之前，先处理 Mermaid 代码块
	convertMermaidCodeblocks();
	
	if (typeof(hljs) == "undefined"){
		return;
	}
	if (typeof(argonConfig.code_highlight.enable) == "undefined"){
		return;
	}
	if (!argonConfig.code_highlight.enable){
		return;
	}
	$("article pre.code").each(function(index, block) {
		if ($(block).hasClass("no-hljs")){
			return;
		}
		$(block).html("<code>" + $(block).html() + "</code>");
	});
	$("article pre > code").each(function(index, block) {
		if ($(block).hasClass("no-hljs")){
			return;
		}
		// 跳过 mermaid 代码块（避免代码高亮干扰 mermaid 渲染）
		if ($(block).hasClass("language-mermaid") || $(block).hasClass("mermaid")){
			return;
		}
		// 跳过在 .mermaid 容器内的代码块
		if ($(block).closest('.mermaid').length > 0){
			return;
		}
		$(block).parent().attr("id", randomString());
		hljs.highlightBlock(block);
		hljs.lineNumbersBlock(block, {singleLine: true});
		$(block).parent().addClass("hljs-codeblock");
		if (argonConfig.code_highlight.hide_linenumber){
			$(block).parent().addClass("hljs-hide-linenumber");
		}
		if (argonConfig.code_highlight.break_line){
			$(block).parent().addClass("hljs-break-line");
		}
		if (argonConfig.code_highlight.transparent_linenumber){
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
			text: function(trigger) {
				return getCodeFromBlock($(block).parent()[0]);
			}
		});
		clipboard.on('success', function(e) {
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
		clipboard.on('error', function(e) {
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
	});
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

// ==========================================================================
// Mermaid 图表渲染引擎
// ==========================================================================
(function() {
	'use strict';

	// ---------- 配置和状态管理 ----------

	/**
	 * Mermaid 渲染引擎配置
	 */
	const MermaidRenderer = {
		initialized: false,
		rendered: new Set(), // 已渲染的图表 ID 集合
		config: null,
		
		/**
		 * 初始化 Mermaid 配置
		 */
		initConfig() {
			// 检查 Mermaid 库是否已加载
			if (typeof window.mermaid === 'undefined') {
				this.logDebug('Mermaid 库未加载');
				return false;
			}

			// 检查配置是否存在
			if (typeof window.argonMermaidConfig === 'undefined') {
				this.logDebug('Mermaid 配置未找到，使用默认配置');
				this.config = {
					enabled: true,
					theme: 'auto',
					debugMode: false,
					fallbackUrls: [],
					libraryLoadedByPlugin: false
				};
			} else {
				this.config = window.argonMermaidConfig;
			}

			// 如果库由插件加载，记录日志
			if (this.config.libraryLoadedByPlugin) {
				this.logDebug('Mermaid 库由插件加载，主题只提供样式增强');
			}

			// 获取当前主题
			const theme = this.getMermaidTheme();

			// 配置 Mermaid
			try {
				window.mermaid.initialize({
					startOnLoad: false, // 手动控制渲染时机
					theme: theme,
					securityLevel: 'loose', // 允许 HTML 标签
					logLevel: this.config.debugMode ? 'debug' : 'error',
					flowchart: {
						useMaxWidth: true,
						htmlLabels: true,
						curve: 'basis'
					},
					sequence: {
						useMaxWidth: true,
						wrap: true
					},
					gantt: {
						useMaxWidth: true
					}
				});

				this.initialized = true;
				this.logDebug('Mermaid 配置初始化成功', { theme });
				return true;
			} catch (error) {
				this.logError('Mermaid 配置初始化失败', error);
				return false;
			}
		},

		/**
		 * 获取当前主题对应的 Mermaid 主题
		 * @returns {string} Mermaid 主题名称
		 */
		getMermaidTheme() {
			// 如果配置了固定主题（非 auto），直接返回
			if (this.config && this.config.theme !== 'auto') {
				return this.config.theme;
			}

			// 根据页面主题模式自动选择
			const isDarkMode = document.documentElement.classList.contains('darkmode');
			return isDarkMode ? 'dark' : 'default';
		},

		// ---------- 代码块检测器 ----------

		/**
		 * 检测所有 Mermaid 代码块
		 * 需求 3.1-3.5: 扫描页面中的 Mermaid 代码块并过滤已渲染的块
		 * @returns {HTMLElement[]} 未渲染的 Mermaid 代码块数组
		 */
		detectMermaidBlocks() {
			const blocks = [];
			
			// 需求 3.1: 扫描所有 <pre><code> 元素
			// 需求 3.2: 识别 language-mermaid 类名
			document.querySelectorAll('pre code.language-mermaid').forEach(code => {
				// 需求 3.5: 过滤已渲染的代码块
				if (!this.isRendered(code)) {
					blocks.push(code);
				}
			});
			
			// 需求 3.3: 识别 mermaid 类名
			document.querySelectorAll('pre code.mermaid').forEach(code => {
				// 需求 3.5: 过滤已渲染的代码块
				if (!this.isRendered(code)) {
					blocks.push(code);
				}
			});
			
			this.logDebug(`检测到 ${blocks.length} 个未渲染的 Mermaid 代码块`);
			return blocks;
		},

		/**
		 * 检查代码块是否已渲染
		 * 需求 3.5: 避免重复渲染已渲染的代码块
		 * @param {HTMLElement} element - 代码块元素
		 * @returns {boolean} 是否已渲染
		 */
		isRendered(element) {
			// 检查元素是否有渲染标记
			if (element.hasAttribute('data-mermaid-rendered')) {
				return true;
			}
			
			// 检查元素是否在 mermaid-container 容器中
			if (element.closest('.mermaid-container') !== null) {
				return true;
			}
			
			return false;
		},

		/**
		 * 检查元素是否在 HTML 注释中
		 * @param {HTMLElement} element - 要检查的元素
		 * @returns {boolean} 是否在注释中
		 */
		isInComment(element) {
			let node = element.parentNode;
			while (node) {
				if (node.nodeType === Node.COMMENT_NODE) {
					return true;
				}
				node = node.parentNode;
			}
			return false;
		},

		/**
		 * 检测 Markdown 容器语法的 Mermaid 代码块
		 * 格式: ::: mermaid ... :::
		 * @param {Array} blocks - 代码块数组
		 */
		detectContainerBlocks(blocks) {
			// 查找所有包含 ::: mermaid 的元素
			const allElements = document.querySelectorAll('p, pre, code, div');
			const processedElements = new Set();
			
			allElements.forEach(element => {
				// 跳过已处理的元素
				if (processedElements.has(element)) {
					return;
				}
				
				const text = element.textContent.trim();
				
				// 检查是否是开始标记
				if (text.startsWith('::: mermaid') || text === '::: mermaid') {
					this.logDebug('找到容器语法开始标记');
					
					// 收集所有内容直到结束标记
					const container = this.extractContainerContent(element, processedElements);
					if (container && !blocks.includes(container)) {
						blocks.push(container);
						this.logDebug('检测到 Markdown 容器语法的 Mermaid 代码块');
					}
				}
			});
		},

		/**
		 * 提取 Markdown 容器语法的内容
		 * @param {HTMLElement} startElement - 包含开始标记的元素
		 * @param {Set} processedElements - 已处理的元素集合
		 * @returns {HTMLElement|null} 包含代码的容器元素
		 */
		extractContainerContent(startElement, processedElements) {
			let codeLines = [];
			let currentElement = startElement;
			let foundStart = false;
			let foundEnd = false;
			
			// 标记开始元素为已处理
			processedElements.add(startElement);
			
			// 处理开始元素
			// 使用 innerHTML 来获取原始内容，包括 <br> 标签
			let startHTML = startElement.innerHTML;
			let startText = startElement.textContent.trim();
			
			this.logDebug('检查元素，textContent: ' + startText.substring(0, 50));
			this.logDebug('innerHTML: ' + startHTML.substring(0, 100));
			
			if (startText.startsWith('::: mermaid')) {
				foundStart = true;
				this.logDebug('找到容器语法开始标记');
				
				// 检查是否整个内容都在一个元素中
				if (startText.includes(':::') && startText.lastIndexOf(':::') > 10) {
					// 整个容器语法在一个元素中
					this.logDebug('检测到单元素容器语法');
					
					// 使用 htmlToText 转换整个 HTML
					let fullText = this.htmlToText(startHTML);
					this.logDebug('转换后的完整文本: ' + fullText.substring(0, 200));
					
					// 移除开始和结束标记
					fullText = fullText.replace(/^:::\s*mermaid\s*/i, '').trim();
					fullText = fullText.replace(/:::\s*$/, '').trim();
					
					this.logDebug('移除标记后的代码: ' + fullText.substring(0, 200));
					
					if (fullText) {
						// 创建容器
						const container = document.createElement('div');
						container.className = 'mermaid-container-block';
						container.textContent = fullText;
						container.dataset.containerBlock = 'true';
						
						// 替换开始元素
						startElement.parentNode.replaceChild(container, startElement);
						
						return container;
					}
					return null;
				}
				
				// 多元素容器语法（原有逻辑）
				// 移除开始标记，保留同一元素中的其他内容
				startText = startText.replace(/^:::\s*mermaid\s*/i, '').trim();
				if (startText && !startText.startsWith(':::')) {
					// 如果开始标记后有内容，需要从 HTML 中提取
					// 将 <br> 转换为换行符
					let contentHTML = startHTML.replace(/^:::\s*mermaid\s*<br\s*\/?>/i, '');
					let contentText = this.htmlToText(contentHTML);
					if (contentText.trim()) {
						codeLines.push(contentText);
					}
				}
				// 检查是否在同一元素中就有结束标记
				if (startText.endsWith(':::')) {
					foundEnd = true;
					// 移除结束标记
					if (codeLines.length > 0) {
						let lastLine = codeLines[codeLines.length - 1];
						codeLines[codeLines.length - 1] = lastLine.replace(/:::\s*$/, '').trim();
					}
				}
			}
			
			// 如果还没找到结束标记，继续查找后续兄弟元素
			if (foundStart && !foundEnd) {
				currentElement = startElement.nextElementSibling;
				
				while (currentElement) {
					processedElements.add(currentElement);
					let text = currentElement.textContent.trim();
					
					// 检查是否是结束标记
					if (text === ':::' || text.endsWith(':::')) {
						foundEnd = true;
						// 如果结束标记前还有内容，保留它
						if (text !== ':::') {
							text = text.replace(/:::\s*$/, '').trim();
							if (text) {
								// 将 HTML 转换为文本，保留换行符
								let contentText = this.htmlToText(currentElement.innerHTML);
								codeLines.push(contentText);
							}
						}
						break;
					}
					
					// 添加当前元素的内容
					// 将 HTML 转换为文本，保留换行符
					let contentText = this.htmlToText(currentElement.innerHTML);
					if (contentText.trim()) {
						codeLines.push(contentText);
					} else {
						// 空元素，添加空行
						codeLines.push('');
					}
					
					currentElement = currentElement.nextElementSibling;
				}
			}
			
			// 如果没有找到完整的容器语法，返回 null
			if (!foundStart || !foundEnd) {
				this.logDebug('容器语法不完整，跳过');
				return null;
			}
			
			// 合并所有行
			let code = codeLines.join('\n').trim();
			
			this.logDebug('提取的完整代码: ' + code.substring(0, 200));
			
			if (!code) {
				return null;
			}

			// 创建一个新的容器来存储代码
			const container = document.createElement('div');
			container.className = 'mermaid-container-block';
			container.textContent = code;
			container.dataset.containerBlock = 'true';

			// 替换开始元素
			startElement.parentNode.replaceChild(container, startElement);

			return container;
		},
		
		/**
		 * 将 HTML 转换为纯文本，保留换行符
		 * @param {string} html - HTML 字符串
		 * @returns {string} 纯文本
		 */
		htmlToText(html) {
			// 将 <br> 和 <br/> 转换为换行符
			let text = html.replace(/<br\s*\/?>/gi, '\n');
			// 移除其他 HTML 标签
			text = text.replace(/<[^>]+>/g, '');
			// 解码 HTML 实体
			text = text
				.replace(/&nbsp;/g, ' ')
				.replace(/&lt;/g, '<')
				.replace(/&gt;/g, '>')
				.replace(/&amp;/g, '&')
				.replace(/&quot;/g, '"')
				.replace(/&#039;/g, "'")
				.replace(/&#8211;/g, '-')  // EN DASH
				.replace(/&#8212;/g, '--') // EM DASH
				.replace(/&#8594;/g, '->') // RIGHTWARDS ARROW
				.replace(/&ndash;/g, '-')  // EN DASH (named entity)
				.replace(/&mdash;/g, '--') // EM DASH (named entity)
				.replace(/&rarr;/g, '->'); // RIGHTWARDS ARROW (named entity)
			
			// 转换 Unicode 字符（WordPress 可能直接输出 Unicode）
			text = text
				.replace(/–/g, '-')   // U+2013 EN DASH
				.replace(/—/g, '--')  // U+2014 EM DASH
				.replace(/→/g, '->'); // U+2192 RIGHTWARDS ARROW
			
			return text;
		},

		/**
		 * 提取代码块内容
		 * @param {HTMLElement} element - 代码块元素
		 * @returns {string} Mermaid 代码
		 */
		extractMermaidCode(element) {
			let code = '';

			// 处理 Shortcode 格式（推荐）
			if (element.classList.contains('mermaid-shortcode')) {
				code = element.textContent;
				this.logDebug('从 Shortcode 格式提取代码，长度: ' + code.length);
				this.logDebug('代码内容: ' + code.substring(0, 200));
			}
			// 处理代码块魔改格式（新增）
			else if (element.classList.contains('mermaid-from-codeblock')) {
				code = element.textContent;
				this.logDebug('从代码块魔改格式提取代码，长度: ' + code.length);
				this.logDebug('代码内容: ' + code.substring(0, 200));
				this.logDebug('元素 HTML: ' + element.outerHTML.substring(0, 300));
			}
			// 处理 Markdown 容器语法格式
			else if (element.classList.contains('mermaid-container-block')) {
				code = element.textContent;
				this.logDebug('从 Markdown 容器语法提取代码，长度: ' + code.length);
				this.logDebug('代码内容: ' + code.substring(0, 200));
			}
			// 根据不同的元素类型提取代码
			else if (element.tagName === 'DIV' && element.classList.contains('mermaid')) {
				// 检查是否包含 WP-Markdown 生成的 script 标签
				const scriptTag = element.querySelector('script');
				if (scriptTag) {
					// 提取 document.write() 中的内容
					const scriptContent = scriptTag.textContent || scriptTag.innerText;
					this.logDebug('检测到 script 标签，原始内容: ' + scriptContent.substring(0, 100));
					
					// 使用更精确的正则：匹配 document.write() 中的字符串内容
					// 支持双引号和单引号，支持转义字符
					// 使用贪婪匹配 [\s\S]* 来匹配包括换行在内的所有字符
					let match = scriptContent.match(/document\.write\s*\(\s*["']([\s\S]*?)["']\s*\)/);
					
					if (match && match[1]) {
						code = match[1];
						this.logDebug('从 document.write() 提取到代码，长度: ' + code.length);
					} else {
						// 如果没有匹配到 document.write()，尝试直接提取引号内的内容
						match = scriptContent.match(/["']([\s\S]*?)["']/);
						if (match && match[1]) {
							code = match[1];
							this.logDebug('从引号内提取到代码，长度: ' + code.length);
						} else {
							// 最后的降级方案：获取除 script 标签外的文本内容
							const clonedElement = element.cloneNode(true);
							const scripts = clonedElement.querySelectorAll('script');
							scripts.forEach(script => script.remove());
							code = clonedElement.textContent;
							this.logDebug('使用降级方案提取代码');
						}
					}
					
					// 重要：移除 script 标签，避免重复渲染
					// WP-Markdown 会在 script 后面再输出一次代码
					scriptTag.remove();
				} else {
					code = element.textContent;
					this.logDebug('从纯文本提取代码');
				}
			} else if (element.tagName === 'CODE') {
				code = element.textContent;
			} else if (element.tagName === 'PRE') {
				const codeElement = element.querySelector('code');
				code = codeElement ? codeElement.textContent : element.textContent;
			}

			// 解码 HTML 实体（WordPress 可能会转义 HTML）
			code = code
				.replace(/&lt;/g, '<')
				.replace(/&gt;/g, '>')
				.replace(/&amp;/g, '&')
				.replace(/&quot;/g, '"')
				.replace(/&#039;/g, "'");

			// 解码转义字符（必须在 HTML 实体解码之后）
			code = code
				.replace(/\\n/g, '\n')
				.replace(/\\t/g, '\t')
				.replace(/\\r/g, '\r')
				.replace(/\\"/g, '"')
				.replace(/\\'/g, "'")
				.replace(/\\\\/g, '\\');

			this.logDebug('最终提取的代码: ' + code.substring(0, 100) + (code.length > 100 ? '...' : ''));
			return code.trim();
		},

		// ---------- 渲染引擎 ----------

		/**
		 * 批量渲染所有 Mermaid 图表
		 * 需求 18.1: 使用批量渲染，避免阻塞主线程
		 * 需求 18.4: 延迟渲染视口外的图表，优先渲染可见图表
		 */
		renderAllCharts() {
			if (!this.initialized) {
				this.logDebug('Mermaid 未初始化，跳过渲染');
				return;
			}

			// 检测所有代码块（一次 DOM 遍历）
			const blocks = this.detectMermaidBlocks();

			if (blocks.length === 0) {
				this.logDebug('未找到 Mermaid 代码块');
				return;
			}

			this.logDebug(`准备批量渲染 ${blocks.length} 个图表`);

			// 需求 18.4: 将图表分为视口内和视口外两组
			const visibleBlocks = [];
			const invisibleBlocks = [];

			blocks.forEach(block => {
				if (this.isInViewport(block)) {
					visibleBlocks.push(block);
				} else {
					invisibleBlocks.push(block);
				}
			});

			this.logDebug(`视口内图表: ${visibleBlocks.length}, 视口外图表: ${invisibleBlocks.length}`);

			// 优先渲染视口内的图表
			let currentIndex = 0;
			const batchSize = 3; // 每批渲染 3 个图表

			const renderBatch = (blockList, isVisible) => {
				if (currentIndex >= blockList.length) {
					// 当前列表渲染完成
					if (isVisible && invisibleBlocks.length > 0) {
						// 视口内图表渲染完成，开始渲染视口外图表
						this.logDebug('视口内图表渲染完成，开始渲染视口外图表');
						currentIndex = 0;
						requestAnimationFrame(() => renderBatch(invisibleBlocks, false));
					} else {
						this.logDebug(`完成渲染 ${blocks.length} 个图表`);
					}
					return;
				}

				const endIndex = Math.min(currentIndex + batchSize, blockList.length);
				
				// 渲染当前批次
				for (let i = currentIndex; i < endIndex; i++) {
					const globalIndex = blocks.indexOf(blockList[i]);
					this.renderChart(blockList[i], globalIndex);
				}

				currentIndex = endIndex;

				// 继续下一批
				requestAnimationFrame(() => renderBatch(blockList, isVisible));
			};

			// 开始渲染（优先渲染视口内图表）
			if (visibleBlocks.length > 0) {
				requestAnimationFrame(() => renderBatch(visibleBlocks, true));
			} else if (invisibleBlocks.length > 0) {
				// 如果没有视口内图表，直接渲染视口外图表
				requestAnimationFrame(() => renderBatch(invisibleBlocks, false));
			}
		},

		/**
		 * 检查元素是否在视口内
		 * @param {HTMLElement} element - 要检查的元素
		 * @returns {boolean} 是否在视口内
		 */
		isInViewport(element) {
			const rect = element.getBoundingClientRect();
			const windowHeight = window.innerHeight || document.documentElement.clientHeight;
			const windowWidth = window.innerWidth || document.documentElement.clientWidth;

			// 元素至少部分可见
			return (
				rect.top < windowHeight &&
				rect.bottom > 0 &&
				rect.left < windowWidth &&
				rect.right > 0
			);
		},

		/**
		 * 渲染单个图表
		 * 需求 18.5: 图表渲染失败不阻塞其他图表的渲染
		 * @param {HTMLElement} element - 代码块元素
		 * @param {number} index - 图表索引
		 */
		renderChart(element, index) {
			const chartId = `mermaid-chart-${Date.now()}-${index}`;

			// 需求 18.5: 使用 try-catch 包裹整个渲染过程
			try {
				// 检查是否已经是错误容器（避免重复处理错误）
				if (element.classList && element.classList.contains('mermaid-error-container')) {
					this.logDebug(`元素已经是错误容器，跳过: ${chartId}`);
					return;
				}

				// 检查是否已经是渲染成功的容器（避免重复渲染）
				if (element.classList && element.classList.contains('mermaid-container') && element.dataset.mermaidCode) {
					this.logDebug(`图表已成功渲染，跳过: ${chartId}`);
					return;
				}

				// 检查是否已渲染（避免重复渲染）
				if (this.rendered.has(element)) {
					this.logDebug(`图表已渲染，跳过: ${chartId}`);
					return;
				}

				// 提取代码
				const code = this.extractMermaidCode(element);

				if (!code) {
					this.logDebug(`代码块为空，跳过: ${chartId}`);
					return;
				}

				this.logDebug(`准备渲染图表: ${chartId}, 代码长度: ${code.length}`);
				this.logDebug(`代码内容: ${code.substring(0, 200)}`);

				// 需求 18.2: 创建加载动画容器
				const loadingContainer = document.createElement('div');
				loadingContainer.className = 'mermaid-loading';
				loadingContainer.innerHTML = `
					<div class="mermaid-loading-spinner"></div>
					<div class="mermaid-loading-text">正在渲染图表...</div>
				`;

				// 替换原始元素为加载动画
				if (element.parentNode) {
					element.parentNode.replaceChild(loadingContainer, element);
				}

				// 创建最终容器
				const container = document.createElement('div');
				container.className = 'mermaid-container';
				container.id = chartId;

				// 检查 Mermaid API
				if (typeof window.mermaid === 'undefined') {
					this.logError('Mermaid 库未加载');
					this.handleRenderError(loadingContainer, new Error('Mermaid 库未加载'), code);
					return;
				}

				if (typeof window.mermaid.render !== 'function') {
					this.logError('Mermaid.render 方法不存在');
					this.handleRenderError(loadingContainer, new Error('Mermaid.render 方法不存在'), code);
					return;
				}

				// 渲染图表
				const renderPromise = window.mermaid.render(`mermaid-svg-${chartId}`, code);
				
				// 检查返回值是否是 Promise
				if (!renderPromise || typeof renderPromise.then !== 'function') {
					this.logError('Mermaid.render 没有返回 Promise，可能是版本不兼容');
					// 尝试使用旧版 API
					this.renderChartLegacy(loadingContainer, code, container, chartId);
					return;
				}

				renderPromise.then(result => {
					// 渲染成功
					container.innerHTML = result.svg;
					
					// 保存原始代码（用于主题切换时重新渲染）
					container.dataset.mermaidCode = code;
					container.dataset.currentTheme = this.getMermaidTheme();

					// 替换加载动画为渲染结果
					if (loadingContainer.parentNode) {
						loadingContainer.parentNode.replaceChild(container, loadingContainer);
					}

					// 标记为已渲染
					this.rendered.add(container);

					// 应用样式增强（包含淡入动画）
					this.applyStyles(container);

					this.logDebug(`图表渲染成功: ${chartId}`);
				}).catch(error => {
					// 需求 18.5: 渲染失败时记录错误但不抛出异常
					this.logError(`图表渲染失败: ${chartId}`, error);
					this.handleRenderError(loadingContainer, error, code);
				});

			} catch (error) {
				// 需求 18.5: 捕获所有异常，确保不影响其他图表
				this.logError(`渲染异常: ${chartId}`, error);
				
				// 尝试显示错误信息
				try {
					this.handleRenderError(element, error, '');
				} catch (e) {
					// 如果错误处理也失败，只记录日志
					this.logError('错误处理失败', e);
				}
			}
		},

		/**
		 * 使用旧版 Mermaid API 渲染（兼容 Mermaid 8.x）
		 * @param {HTMLElement} element - 原始代码块元素
		 * @param {string} code - Mermaid 代码
		 * @param {HTMLElement} container - 容器元素
		 * @param {string} chartId - 图表 ID
		 */
		renderChartLegacy(element, code, container, chartId) {
			try {
				this.logDebug('尝试使用旧版 Mermaid API');
				
				// Mermaid 8.x 使用 mermaidAPI.render
				if (typeof window.mermaidAPI !== 'undefined' && typeof window.mermaidAPI.render === 'function') {
					window.mermaidAPI.render(`mermaid-svg-${chartId}`, code, (svgCode) => {
						container.innerHTML = svgCode;
						container.dataset.mermaidCode = code;
						container.dataset.currentTheme = this.getMermaidTheme();
						element.parentNode.replaceChild(container, element);
						this.rendered.add(container);
						this.applyStyles(container);
						this.logDebug(`图表渲染成功（旧版 API）: ${chartId}`);
					});
				} else {
					// 最后的降级方案：直接使用 mermaid.init()
					container.className = 'mermaid';
					container.textContent = code;
					element.parentNode.replaceChild(container, element);
					
					if (typeof window.mermaid.init === 'function') {
						window.mermaid.init(undefined, container);
						this.rendered.add(container);
						this.logDebug(`图表渲染成功（init 方法）: ${chartId}`);
					} else {
						this.handleRenderError(element, new Error('无可用的 Mermaid 渲染方法'), code);
					}
				}
			} catch (error) {
				this.logError('旧版 API 渲染失败', error);
				this.handleRenderError(element, error, code);
			}
		},

		/**
		 * 处理渲染错误
		 * @param {HTMLElement} element - 原始代码块元素
		 * @param {Error} error - 错误对象
		 * @param {string} code - 原始代码
		 */
		handleRenderError(element, error, code) {
			this.logError('图表渲染失败', error);

			// 如果元素已经是错误容器，避免重复嵌套
			if (element.classList && element.classList.contains('mermaid-error-container')) {
				this.logDebug('元素已经是错误容器，跳过重复处理');
				return;
			}

			// 提取原始代码（优先使用传入的 code，其次从元素中提取）
			let originalCode = code;
			if (!originalCode) {
				// 尝试从不同类型的元素中提取代码
				if (element.dataset && element.dataset.mermaidCode) {
					originalCode = element.dataset.mermaidCode;
				} else if (element.textContent) {
					originalCode = element.textContent.trim();
				}
			}

			// 创建错误提示容器
			const errorContainer = document.createElement('div');
			errorContainer.className = 'mermaid-error-container';
			errorContainer.dataset.errorHandled = 'true'; // 标记已处理

			// 提取错误信息
			const errorMessage = error.message || '未知错误';
			const errorType = this.getErrorType(errorMessage);
			const errorLine = this.extractErrorLine(errorMessage);

			// 创建错误显示结构
			const errorHeader = document.createElement('div');
			errorHeader.className = 'mermaid-error-header';
			errorHeader.innerHTML = `
				<span class="mermaid-error-icon">⚠️</span>
				<span class="mermaid-error-title">Mermaid 图表渲染失败</span>
			`;

			const errorBody = document.createElement('div');
			errorBody.className = 'mermaid-error-body';
			
			let errorBodyHTML = `
				<p class="mermaid-error-type">错误类型: ${errorType}</p>
				<p class="mermaid-error-message">${this.escapeHtml(errorMessage)}</p>
			`;
			
			// 如果提取到错误行号，显示它
			if (errorLine) {
				errorBodyHTML += `<p class="mermaid-error-type">错误位置: 第 ${errorLine} 行</p>`;
			}
			
			errorBody.innerHTML = errorBodyHTML;

			// 创建代码查看区域
			const codeDetails = document.createElement('details');
			codeDetails.className = 'mermaid-error-code';
			
			const codeSummary = document.createElement('summary');
			codeSummary.textContent = '📄 查看原始代码';
			
			const codeBlock = document.createElement('pre');
			const codeElement = document.createElement('code');
			codeElement.className = 'language-mermaid';
			codeElement.textContent = originalCode || '(无法提取代码)';
			
			codeBlock.appendChild(codeElement);
			codeDetails.appendChild(codeSummary);
			codeDetails.appendChild(codeBlock);

			// 组装错误容器
			errorContainer.appendChild(errorHeader);
			errorContainer.appendChild(errorBody);
			errorContainer.appendChild(codeDetails);

			// 替换原始代码块
			if (element.parentNode) {
				element.parentNode.replaceChild(errorContainer, element);
			}
		},

		/**
		 * 获取错误类型
		 * @param {string} errorMessage - 错误信息
		 * @returns {string} 错误类型
		 */
		getErrorType(errorMessage) {
			if (errorMessage.includes('Syntax') || errorMessage.includes('Parse')) {
				return '语法错误';
			} else if (errorMessage.includes('Lexical')) {
				return '词法错误';
			} else if (errorMessage.includes('Expecting')) {
				return '格式错误';
			} else if (errorMessage.includes('Unknown')) {
				return '未知图表类型';
			}
			return '渲染错误';
		},

		/**
		 * 从错误信息中提取行号
		 * @param {string} errorMessage - 错误信息
		 * @returns {string|null} 行号或 null
		 */
		extractErrorLine(errorMessage) {
			// 尝试匹配常见的行号格式
			const linePatterns = [
				/line (\d+)/i,
				/at line (\d+)/i,
				/on line (\d+)/i,
				/第 (\d+) 行/,
			];
			
			for (const pattern of linePatterns) {
				const match = errorMessage.match(pattern);
				if (match && match[1]) {
					return match[1];
				}
			}
			
			return null;
		},

		/**
		 * HTML 转义
		 * @param {string} text - 要转义的文本
		 * @returns {string} 转义后的文本
		 */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		// ---------- 样式增强 ----------

		/**
		 * 应用容器样式并添加缩放控制
		 * 需求 18.3: 使用淡入动画，提升视觉体验
		 * @param {HTMLElement} container - 图表容器
		 */
		applyStyles(container) {
			// 需求 18.3: 添加淡入动画
			container.style.opacity = '0';
			
			// 使用 requestAnimationFrame 确保动画流畅
			requestAnimationFrame(() => {
				container.style.transition = 'opacity 0.4s ease-in';
				container.style.opacity = '1';
				
				// 动画完成后清理过渡样式
				setTimeout(() => {
					container.style.transition = '';
				}, 400);
			});

			// 确保 SVG 响应式
			const svg = container.querySelector('svg');
			if (svg) {
				svg.style.width = '100%';
				svg.style.height = 'auto';
			}

			// 添加缩放功能
			this.addZoomControls(container);
		},

		/**
		 * 添加缩放控制按钮
		 * @param {HTMLElement} container - 图表容器
		 */
		addZoomControls(container) {
			// 创建内部容器包裹 SVG
			const svg = container.querySelector('svg');
			if (!svg) return;

			// 创建内部容器
			const inner = document.createElement('div');
			inner.className = 'mermaid-container-inner';
			
			// 将 SVG 移到内部容器
			container.appendChild(inner);
			inner.appendChild(svg);

			// 创建缩放控制
			// 需求 14.1: 添加全屏按钮
			// 需求 15.1: 添加导出按钮和菜单
			const controls = document.createElement('div');
			controls.className = 'mermaid-zoom-controls';
			controls.innerHTML = `
				<button class="mermaid-zoom-btn" data-action="zoom-out" title="缩小">−</button>
				<span class="mermaid-zoom-level">100%</span>
				<button class="mermaid-zoom-btn" data-action="zoom-in" title="放大">+</button>
				<button class="mermaid-zoom-btn" data-action="zoom-reset" title="重置">⟲</button>
				<button class="mermaid-zoom-btn" data-action="fullscreen" title="全屏">⛶</button>
				<button class="mermaid-zoom-btn mermaid-export-btn" data-action="export" title="导出">⬇</button>
			`;

			container.appendChild(controls);

			// 需求 15.1: 创建导出菜单
			const exportMenu = document.createElement('div');
			exportMenu.className = 'mermaid-export-menu';
			exportMenu.innerHTML = `
				<button class="mermaid-export-option" data-format="png">导出为 PNG</button>
				<button class="mermaid-export-option" data-format="svg">导出为 SVG</button>
			`;
			container.appendChild(exportMenu);

			// 创建提示文本
			const hint = document.createElement('div');
			hint.className = 'mermaid-hint';
			hint.textContent = '按住 Ctrl+滚轮缩放 | 拖拽移动';
			container.appendChild(hint);

			// 缩放状态
			let scale = 1;
			const minScale = 0.5;
			const maxScale = 3;
			const step = 0.25;

			// 更新缩放显示
			// 需求 12.2, 12.3: 使用 CSS transform 实现硬件加速和平滑过渡
			// 需求 12.4, 20.3: 更新按钮状态
			const updateZoom = (useTransition = true) => {
				// 控制是否使用过渡动画
				if (!useTransition) {
					inner.style.transition = 'none';
				} else {
					inner.style.transition = 'transform 0.3s ease';
				}
				
				inner.style.transform = `scale(${scale})`;
				controls.querySelector('.mermaid-zoom-level').textContent = Math.round(scale * 100) + '%';
				
				// 更新按钮状态
				const zoomInBtn = controls.querySelector('[data-action="zoom-in"]');
				const zoomOutBtn = controls.querySelector('[data-action="zoom-out"]');
				
				// 禁用/启用放大按钮
				if (scale >= maxScale) {
					zoomInBtn.disabled = true;
					zoomInBtn.style.opacity = '0.4';
					zoomInBtn.style.cursor = 'not-allowed';
				} else {
					zoomInBtn.disabled = false;
					zoomInBtn.style.opacity = '1';
					zoomInBtn.style.cursor = 'pointer';
				}
				
				// 禁用/启用缩小按钮
				if (scale <= minScale) {
					zoomOutBtn.disabled = true;
					zoomOutBtn.style.opacity = '0.4';
					zoomOutBtn.style.cursor = 'not-allowed';
				} else {
					zoomOutBtn.disabled = false;
					zoomOutBtn.style.opacity = '1';
					zoomOutBtn.style.cursor = 'pointer';
				}
				
				// 恢复过渡（用于下次按钮点击）
				if (!useTransition) {
					requestAnimationFrame(() => {
						inner.style.transition = 'transform 0.3s ease';
					});
				}
			};

			// 绑定按钮事件
			// 需求 14.1, 14.2, 14.3: 全屏按钮功能
			// 需求 15.1: 导出按钮功能
			controls.addEventListener('click', (e) => {
				const btn = e.target.closest('.mermaid-zoom-btn');
				if (!btn || btn.disabled) return; // 忽略禁用的按钮

				const action = btn.dataset.action;
				
				if (action === 'zoom-in' && scale < maxScale) {
					scale = Math.min(scale + step, maxScale);
					updateZoom(true); // 按钮点击使用平滑过渡
				} else if (action === 'zoom-out' && scale > minScale) {
					scale = Math.max(scale - step, minScale);
					updateZoom(true); // 按钮点击使用平滑过渡
				} else if (action === 'zoom-reset') {
					scale = 1;
					updateZoom(true); // 重置使用平滑过渡
				} else if (action === 'fullscreen') {
					// 需求 14.1: 点击全屏按钮
					toggleFullscreen();
				} else if (action === 'export') {
					// 需求 15.1: 点击导出按钮，显示/隐藏导出菜单
					toggleExportMenu();
				}
			});

			// ==========================================================================
			// 需求 15: Mermaid 导出功能
			// ==========================================================================

			/**
			 * 切换导出菜单显示/隐藏
			 * 需求 15.1: 点击导出按钮显示导出选项
			 */
			const toggleExportMenu = () => {
				const isVisible = exportMenu.classList.contains('visible');
				if (isVisible) {
					exportMenu.classList.remove('visible');
				} else {
					exportMenu.classList.add('visible');
				}
			};

			// 点击导出选项
			exportMenu.addEventListener('click', (e) => {
				const option = e.target.closest('.mermaid-export-option');
				if (!option) return;

				const format = option.dataset.format;
				exportMenu.classList.remove('visible');

				if (format === 'png') {
					exportAsPNG();
				} else if (format === 'svg') {
					exportAsSVG();
				}
			});

			// 点击容器外部关闭导出菜单
			document.addEventListener('click', (e) => {
				if (!container.contains(e.target)) {
					exportMenu.classList.remove('visible');
				}
			});

			/**
			 * 导出为 PNG
			 * 需求 15.2: 将图表转换为 PNG 图片并下载
			 * 需求 15.4: 保持图表当前的缩放级别和样式
			 */
			const exportAsPNG = async () => {
				try {
					const svgElement = inner.querySelector('svg');
					if (!svgElement) {
						throw new Error('未找到 SVG 元素');
					}

					// 克隆 SVG 以避免修改原始元素
					const clonedSvg = svgElement.cloneNode(true);
					
					// 需求 15.4: 应用当前缩放级别
					const svgWidth = svgElement.getBoundingClientRect().width * scale;
					const svgHeight = svgElement.getBoundingClientRect().height * scale;
					
					clonedSvg.setAttribute('width', svgWidth);
					clonedSvg.setAttribute('height', svgHeight);

					// 将 SVG 转换为 data URL
					const svgData = new XMLSerializer().serializeToString(clonedSvg);
					const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
					const svgUrl = URL.createObjectURL(svgBlob);

					// 创建图片元素
					const img = new Image();
					img.onload = () => {
						// 创建 canvas
						const canvas = document.createElement('canvas');
						canvas.width = svgWidth;
						canvas.height = svgHeight;
						
						const ctx = canvas.getContext('2d');
						
						// 设置白色背景（PNG 默认透明）
						ctx.fillStyle = '#ffffff';
						ctx.fillRect(0, 0, canvas.width, canvas.height);
						
						// 绘制图片
						ctx.drawImage(img, 0, 0, svgWidth, svgHeight);
						
						// 转换为 PNG 并下载
						canvas.toBlob((blob) => {
							if (blob) {
								downloadFile(blob, 'mermaid-chart.png');
							}
							URL.revokeObjectURL(svgUrl);
						}, 'image/png');
					};

					// 需求 15.5: 添加导出错误处理
					img.onerror = () => {
						URL.revokeObjectURL(svgUrl);
						throw new Error('图片加载失败');
					};

					img.src = svgUrl;

				} catch (error) {
					// 需求 15.5: 显示友好的错误提示
					handleExportError('PNG', error);
				}
			};

			/**
			 * 导出为 SVG
			 * 需求 15.3: 将 SVG 代码保存为文件并下载
			 * 需求 15.4: 保持图表当前的缩放级别和样式
			 */
			const exportAsSVG = () => {
				try {
					const svgElement = inner.querySelector('svg');
					if (!svgElement) {
						throw new Error('未找到 SVG 元素');
					}

					// 克隆 SVG
					const clonedSvg = svgElement.cloneNode(true);
					
					// 需求 15.4: 应用当前缩放级别
					if (scale !== 1) {
						const svgWidth = svgElement.getBoundingClientRect().width * scale;
						const svgHeight = svgElement.getBoundingClientRect().height * scale;
						
						clonedSvg.setAttribute('width', svgWidth);
						clonedSvg.setAttribute('height', svgHeight);
					}

					// 添加 XML 声明和命名空间
					const svgData = new XMLSerializer().serializeToString(clonedSvg);
					const svgWithDeclaration = '<?xml version="1.0" encoding="UTF-8"?>\n' + svgData;
					
					// 创建 Blob 并下载
					const blob = new Blob([svgWithDeclaration], { type: 'image/svg+xml;charset=utf-8' });
					downloadFile(blob, 'mermaid-chart.svg');

				} catch (error) {
					// 需求 15.5: 显示友好的错误提示
					handleExportError('SVG', error);
				}
			};

			/**
			 * 下载文件
			 * @param {Blob} blob - 文件内容
			 * @param {string} filename - 文件名
			 */
			const downloadFile = (blob, filename) => {
				const url = URL.createObjectURL(blob);
				const link = document.createElement('a');
				link.href = url;
				link.download = filename;
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				URL.revokeObjectURL(url);
			};

			/**
			 * 处理导出错误
			 * 需求 15.5: 显示友好的错误提示
			 * @param {string} format - 导出格式
			 * @param {Error} error - 错误对象
			 */
			const handleExportError = (format, error) => {
				console.error(`导出 ${format} 失败:`, error);
				
				// 显示错误提示
				const errorMsg = document.createElement('div');
				errorMsg.className = 'mermaid-export-error';
				errorMsg.textContent = `导出 ${format} 失败: ${error.message}`;
				container.appendChild(errorMsg);
				
				// 3秒后自动移除错误提示
				setTimeout(() => {
					if (errorMsg.parentNode) {
						errorMsg.parentNode.removeChild(errorMsg);
					}
				}, 3000);
			};

			// 鼠标滚轮缩放（按住 Ctrl）
			// 需求 12.1: 以鼠标为中心进行缩放
			container.addEventListener('wheel', (e) => {
				if (e.ctrlKey || e.metaKey) {
					e.preventDefault();
					
					const delta = e.deltaY > 0 ? -step : step;
					const newScale = Math.max(minScale, Math.min(maxScale, scale + delta));
					
					if (newScale !== scale) {
						// 获取鼠标相对于容器的位置
						const rect = container.getBoundingClientRect();
						const mouseX = e.clientX - rect.left;
						const mouseY = e.clientY - rect.top;
						
						// 计算鼠标相对于内容的位置（缩放前）
						const scrollLeft = container.scrollLeft;
						const scrollTop = container.scrollTop;
						const offsetX = (scrollLeft + mouseX) / scale;
						const offsetY = (scrollTop + mouseY) / scale;
						
						// 更新缩放（滚轮缩放不使用过渡动画，更流畅）
						scale = newScale;
						updateZoom(false);
						
						// 调整滚动位置，使鼠标位置保持不变
						container.scrollLeft = offsetX * scale - mouseX;
						container.scrollTop = offsetY * scale - mouseY;
					}
				}
			}, { passive: false });

			// 拖拽功能
			let isDragging = false;
			let startX = 0;
			let startY = 0;
			let scrollLeft = 0;
			let scrollTop = 0;
			let rafId = null;
			let pendingX = 0;
			let pendingY = 0;

			// 需求 13.5: 智能启用拖拽 - 检查图表是否需要拖拽
			const checkDragEnabled = () => {
				const svgElement = inner.querySelector('svg');
				if (!svgElement) return false;
				
				// 如果图表未缩放且完全可见，禁用拖拽
				const containerRect = container.getBoundingClientRect();
				const svgRect = svgElement.getBoundingClientRect();
				const isFullyVisible = svgRect.width <= containerRect.width && 
				                       svgRect.height <= containerRect.height;
				const isNotZoomed = Math.abs(scale - 1) < 0.01;
				
				return !(isFullyVisible && isNotZoomed);
			};

			// 需求 13.3: 使用 requestAnimationFrame 优化拖拽性能
			const updateDragPosition = () => {
				if (!isDragging) {
					rafId = null;
					return;
				}
				
				inner.scrollLeft = scrollLeft - pendingX;
				inner.scrollTop = scrollTop - pendingY;
				
				rafId = requestAnimationFrame(updateDragPosition);
			};

			inner.addEventListener('mousedown', (e) => {
				// 只响应左键
				if (e.button !== 0) return;
				
				// 需求 13.5: 检查是否需要启用拖拽
				if (!checkDragEnabled()) return;
				
				isDragging = true;
				// 需求 13.1: 改变鼠标光标为抓手样式
				inner.classList.add('dragging');
				
				startX = e.pageX - inner.offsetLeft;
				startY = e.pageY - inner.offsetTop;
				scrollLeft = inner.scrollLeft;
				scrollTop = inner.scrollTop;
				
				// 需求 13.2: 禁用文本选择
				e.preventDefault();
				
				// 启动 RAF 循环
				if (!rafId) {
					rafId = requestAnimationFrame(updateDragPosition);
				}
			});

			document.addEventListener('mousemove', (e) => {
				if (!isDragging) return;
				
				e.preventDefault();
				
				const x = e.pageX - inner.offsetLeft;
				const y = e.pageY - inner.offsetTop;
				// 更新待处理的位置，由 RAF 循环处理
				pendingX = (x - startX) * 2;
				pendingY = (y - startY) * 2;
			});

			document.addEventListener('mouseup', () => {
				if (isDragging) {
					isDragging = false;
					// 需求 13.4: 恢复正常光标
					inner.classList.remove('dragging');
					// 取消 RAF 循环
					if (rafId) {
						cancelAnimationFrame(rafId);
						rafId = null;
					}
				}
			});

			// 防止拖拽时选中文本
			inner.addEventListener('dragstart', (e) => {
				e.preventDefault();
			});
			
			// 需求 13.2: 禁用文本选择
			inner.addEventListener('selectstart', (e) => {
				if (isDragging) {
					e.preventDefault();
				}
			});

			// 需求 12.5: 双击智能缩放
			// 双击图表时，如果当前是默认大小则放大到 2 倍，否则重置到 1 倍
			inner.addEventListener('dblclick', (e) => {
				e.preventDefault();
				
				// 获取双击位置
				const rect = container.getBoundingClientRect();
				const mouseX = e.clientX - rect.left;
				const mouseY = e.clientY - rect.top;
				
				// 计算鼠标相对于内容的位置（缩放前）
				const scrollLeft = container.scrollLeft;
				const scrollTop = container.scrollTop;
				const offsetX = (scrollLeft + mouseX) / scale;
				const offsetY = (scrollTop + mouseY) / scale;
				
				// 智能缩放：如果接近默认大小则放大，否则重置
				const targetScale = Math.abs(scale - 1) < 0.1 ? 2 : 1;
				scale = targetScale;
				updateZoom(true); // 使用平滑过渡
				
				// 调整滚动位置，使双击位置保持在视野中心
				setTimeout(() => {
					container.scrollLeft = offsetX * scale - mouseX;
					container.scrollTop = offsetY * scale - mouseY;
				}, 50);
			});

			// ==========================================================================
			// 需求 14: Mermaid 全屏模式
			// ==========================================================================

			// 全屏状态
			let isFullscreen = false;
			let savedScale = 1;
			let savedScrollLeft = 0;
			let savedScrollTop = 0;

			/**
			 * 切换全屏模式
			 * 需求 14.1: 点击全屏按钮将图表全屏显示
			 * 需求 14.2, 14.3: 全屏模式下保持缩放和拖拽功能可用
			 * 需求 14.5: 退出全屏恢复图表原始状态
			 */
			const toggleFullscreen = () => {
				if (!isFullscreen) {
					// 进入全屏
					enterFullscreen();
				} else {
					// 退出全屏
					exitFullscreen();
				}
			};

			/**
			 * 进入全屏模式
			 */
			const enterFullscreen = () => {
				// 保存当前状态
				savedScale = scale;
				savedScrollLeft = container.scrollLeft;
				savedScrollTop = container.scrollTop;

				// 添加全屏类
				container.classList.add('mermaid-fullscreen');
				isFullscreen = true;

				// 更新全屏按钮
				const fullscreenBtn = controls.querySelector('[data-action="fullscreen"]');
				if (fullscreenBtn) {
					fullscreenBtn.textContent = '⛶';
					fullscreenBtn.title = '退出全屏';
					fullscreenBtn.classList.add('active');
				}

				// 需求 14.3: 全屏模式下显示退出全屏按钮（已在工具栏中）
				// 工具栏在全屏模式下仍然可见

				// 禁用页面滚动
				document.body.style.overflow = 'hidden';

				this.logDebug('进入全屏模式');
			};

			/**
			 * 退出全屏模式
			 * 需求 14.5: 恢复图表原始状态（缩放级别和位置）
			 */
			const exitFullscreen = () => {
				// 移除全屏类
				container.classList.remove('mermaid-fullscreen');
				isFullscreen = false;

				// 更新全屏按钮
				const fullscreenBtn = controls.querySelector('[data-action="fullscreen"]');
				if (fullscreenBtn) {
					fullscreenBtn.textContent = '⛶';
					fullscreenBtn.title = '全屏';
					fullscreenBtn.classList.remove('active');
				}

				// 恢复原始状态
				scale = savedScale;
				updateZoom(false);

				// 恢复滚动位置
				setTimeout(() => {
					container.scrollLeft = savedScrollLeft;
					container.scrollTop = savedScrollTop;
				}, 50);

				// 恢复页面滚动
				document.body.style.overflow = '';

				this.logDebug('退出全屏模式');
			};

			// 需求 14.4: 按 ESC 键退出全屏模式
			const handleEscKey = (e) => {
				if (e.key === 'Escape' && isFullscreen) {
					exitFullscreen();
				}
			};

			// 绑定 ESC 键事件
			document.addEventListener('keydown', handleEscKey);

			// 清理函数（在容器销毁时调用）
			container.dataset.cleanupFullscreen = 'registered';
			container._fullscreenCleanup = () => {
				document.removeEventListener('keydown', handleEscKey);
				if (isFullscreen) {
					exitFullscreen();
				}
			};

			// ==========================================================================
			// 需求 16.2-16.4: 移动端触摸手势支持
			// ==========================================================================

			// 触摸状态
			let touchStartDistance = 0;
			let touchStartScale = 1;
			let touchStartX = 0;
			let touchStartY = 0;
			let isTouchDragging = false;
			let touchScrollLeft = 0;
			let touchScrollTop = 0;

			/**
			 * 计算两个触摸点之间的距离
			 * @param {Touch} touch1 - 第一个触摸点
			 * @param {Touch} touch2 - 第二个触摸点
			 * @returns {number} 距离
			 */
			const getTouchDistance = (touch1, touch2) => {
				const dx = touch2.clientX - touch1.clientX;
				const dy = touch2.clientY - touch1.clientY;
				return Math.sqrt(dx * dx + dy * dy);
			};

			/**
			 * 获取两个触摸点的中心位置
			 * @param {Touch} touch1 - 第一个触摸点
			 * @param {Touch} touch2 - 第二个触摸点
			 * @returns {Object} 中心位置 {x, y}
			 */
			const getTouchCenter = (touch1, touch2) => {
				return {
					x: (touch1.clientX + touch2.clientX) / 2,
					y: (touch1.clientY + touch2.clientY) / 2
				};
			};

			// 需求 16.2: 双指缩放手势
			inner.addEventListener('touchstart', (e) => {
				if (e.touches.length === 2) {
					// 双指触摸 - 缩放模式
					e.preventDefault();
					
					touchStartDistance = getTouchDistance(e.touches[0], e.touches[1]);
					touchStartScale = scale;
					
					// 记录缩放中心点
					const center = getTouchCenter(e.touches[0], e.touches[1]);
					const rect = container.getBoundingClientRect();
					touchStartX = center.x - rect.left;
					touchStartY = center.y - rect.top;
					
				} else if (e.touches.length === 1) {
					// 需求 16.3: 单指拖拽移动
					// 检查是否需要启用拖拽
					if (!checkDragEnabled()) return;
					
					isTouchDragging = true;
					inner.classList.add('dragging');
					
					const touch = e.touches[0];
					touchStartX = touch.clientX;
					touchStartY = touch.clientY;
					touchScrollLeft = container.scrollLeft;
					touchScrollTop = container.scrollTop;
				}
			}, { passive: false });

			// 需求 16.4: 优化触摸事件响应速度
			inner.addEventListener('touchmove', (e) => {
				if (e.touches.length === 2) {
					// 双指缩放
					e.preventDefault();
					
					const currentDistance = getTouchDistance(e.touches[0], e.touches[1]);
					const scaleChange = currentDistance / touchStartDistance;
					const newScale = Math.max(minScale, Math.min(maxScale, touchStartScale * scaleChange));
					
					if (newScale !== scale) {
						// 获取缩放中心点
						const center = getTouchCenter(e.touches[0], e.touches[1]);
						const rect = container.getBoundingClientRect();
						const centerX = center.x - rect.left;
						const centerY = center.y - rect.top;
						
						// 计算缩放前的内容位置
						const scrollLeft = container.scrollLeft;
						const scrollTop = container.scrollTop;
						const offsetX = (scrollLeft + centerX) / scale;
						const offsetY = (scrollTop + centerY) / scale;
						
						// 更新缩放（不使用过渡动画，更流畅）
						scale = newScale;
						updateZoom(false);
						
						// 调整滚动位置，使缩放中心保持不变
						requestAnimationFrame(() => {
							container.scrollLeft = offsetX * scale - centerX;
							container.scrollTop = offsetY * scale - centerY;
						});
					}
					
				} else if (e.touches.length === 1 && isTouchDragging) {
					// 单指拖拽
					e.preventDefault();
					
					const touch = e.touches[0];
					const deltaX = touch.clientX - touchStartX;
					const deltaY = touch.clientY - touchStartY;
					
					// 使用 requestAnimationFrame 优化性能
					requestAnimationFrame(() => {
						container.scrollLeft = touchScrollLeft - deltaX;
						container.scrollTop = touchScrollTop - deltaY;
					});
				}
			}, { passive: false });

			inner.addEventListener('touchend', (e) => {
				if (isTouchDragging) {
					isTouchDragging = false;
					inner.classList.remove('dragging');
				}
				
				// 重置触摸状态
				if (e.touches.length === 0) {
					touchStartDistance = 0;
					touchStartScale = 1;
				}
			}, { passive: true });

			inner.addEventListener('touchcancel', () => {
				if (isTouchDragging) {
					isTouchDragging = false;
					inner.classList.remove('dragging');
				}
				touchStartDistance = 0;
				touchStartScale = 1;
			}, { passive: true });
		},

		// ---------- 主题切换监听 ----------

		/**
		 * 监听主题切换事件
		 */
		listenThemeSwitch() {
			// 如果配置了固定主题，不需要监听切换
			if (this.config && this.config.theme !== 'auto') {
				return;
			}

			// 监听 Argon 主题切换事件
			document.addEventListener('argon:theme-switched', () => {
				this.reRenderCharts();
			});

			// 使用 MutationObserver 监听 darkmode class 变化
			const observer = new MutationObserver((mutations) => {
				mutations.forEach((mutation) => {
					if (mutation.attributeName === 'class') {
						const isDarkMode = document.documentElement.classList.contains('darkmode');
						const currentTheme = isDarkMode ? 'dark' : 'default';
						
						// 检查主题是否真的改变了
						const firstChart = document.querySelector('.mermaid-container');
						if (firstChart && firstChart.dataset.currentTheme !== currentTheme) {
							this.reRenderCharts();
						}
					}
				});
			});

			observer.observe(document.documentElement, {
				attributes: true,
				attributeFilter: ['class']
			});

			this.logDebug('主题切换监听器已启动');
		},

		/**
		 * 重新渲染所有图表（主题切换时）
		 * 需求 17.3: 保持图表的缩放级别和位置
		 * 需求 17.4, 17.5: 使用淡入淡出过渡效果
		 */
		reRenderCharts() {
			// 只选择成功渲染的图表容器，排除错误容器
			const charts = document.querySelectorAll('.mermaid-container:not(.mermaid-error-container)');
			
			if (charts.length === 0) {
				return;
			}

			this.logDebug(`重新渲染 ${charts.length} 个图表`);

			// 更新 Mermaid 主题配置
			const newTheme = this.getMermaidTheme();
			
			try {
				window.mermaid.initialize({
					startOnLoad: false,
					theme: newTheme,
					securityLevel: 'loose',
					logLevel: this.config.debugMode ? 'debug' : 'error',
					flowchart: {
						useMaxWidth: true,
						htmlLabels: true,
						curve: 'basis'
					},
					sequence: {
						useMaxWidth: true,
						wrap: true
					},
					gantt: {
						useMaxWidth: true
					}
				});

				// 重新渲染每个图表
				charts.forEach((chart, index) => {
					const code = chart.dataset.mermaidCode;
					if (!code) {
						this.logDebug('图表缺少原始代码，跳过重新渲染');
						return;
					}

					// 检查主题是否真的需要更新
					if (chart.dataset.currentTheme === newTheme) {
						this.logDebug('图表主题未改变，跳过重新渲染');
						return;
					}

					// 需求 17.3: 保存当前的缩放级别和滚动位置
					const inner = chart.querySelector('.mermaid-container-inner');
					let savedState = null;
					
					if (inner) {
						const transform = window.getComputedStyle(inner).transform;
						const scrollLeft = inner.scrollLeft;
						const scrollTop = inner.scrollTop;
						
						// 提取缩放比例
						let scale = 1;
						if (transform && transform !== 'none') {
							const matrix = transform.match(/matrix\(([^)]+)\)/);
							if (matrix) {
								const values = matrix[1].split(',').map(v => parseFloat(v.trim()));
								scale = values[0]; // scaleX
							}
						}
						
						savedState = {
							scale: scale,
							scrollLeft: scrollLeft,
							scrollTop: scrollTop
						};
						
						this.logDebug('保存图表状态', savedState);
					}

					const chartId = `mermaid-chart-rerender-${Date.now()}-${index}`;

					// 需求 17.4: 添加淡出动画
					chart.style.transition = 'opacity 0.2s ease-out';
					chart.style.opacity = '0';

					// 等待淡出完成后重新渲染
					setTimeout(() => {
						window.mermaid.render(`mermaid-svg-${chartId}`, code).then(result => {
							// 保存旧的内部容器引用
							const oldInner = chart.querySelector('.mermaid-container-inner');
							
							// 更新 SVG 内容
							if (oldInner) {
								// 只替换 SVG，保留容器结构
								const oldSvg = oldInner.querySelector('svg');
								if (oldSvg) {
									// 创建临时容器解析新的 SVG
									const temp = document.createElement('div');
									temp.innerHTML = result.svg;
									const newSvg = temp.querySelector('svg');
									
									if (newSvg) {
										// 替换 SVG
										oldSvg.replaceWith(newSvg);
										
										// 确保 SVG 响应式
										newSvg.style.width = '100%';
										newSvg.style.height = 'auto';
									}
								}
							} else {
								// 如果没有内部容器，直接替换内容
								chart.innerHTML = result.svg;
								
								// 确保 SVG 响应式
								const svg = chart.querySelector('svg');
								if (svg) {
									svg.style.width = '100%';
									svg.style.height = 'auto';
								}
							}
							
							// 更新主题标记
							chart.dataset.currentTheme = newTheme;

							// 需求 17.3: 恢复缩放级别和滚动位置
							if (savedState && oldInner) {
								// 恢复缩放
								oldInner.style.transform = `scale(${savedState.scale})`;
								
								// 恢复滚动位置
								oldInner.scrollLeft = savedState.scrollLeft;
								oldInner.scrollTop = savedState.scrollTop;
								
								// 更新缩放显示
								const zoomLevel = chart.querySelector('.mermaid-zoom-level');
								if (zoomLevel) {
									zoomLevel.textContent = Math.round(savedState.scale * 100) + '%';
								}
								
								this.logDebug('恢复图表状态', savedState);
							}

							// 需求 17.5: 添加淡入动画
							requestAnimationFrame(() => {
								chart.style.transition = 'opacity 0.3s ease-in';
								chart.style.opacity = '1';
								
								// 动画完成后清理过渡样式
								setTimeout(() => {
									chart.style.transition = '';
								}, 300);
							});

							this.logDebug(`图表重新渲染成功: ${chartId}`);
						}).catch(error => {
							this.logError('图表重新渲染失败', error);
							
							// 重新渲染失败时，恢复显示
							chart.style.opacity = '1';
							chart.style.transition = '';
							
							// 不替换为错误容器，保持原样
							// 因为之前已经成功渲染过，只是主题切换失败
						});
					}, 200); // 等待淡出动画完成
				});

			} catch (error) {
				this.logError('重新渲染失败', error);
			}
		},

		// ---------- 日志工具 ----------

		/**
		 * 调试日志
		 * @param {string} message - 日志信息
		 * @param {*} data - 附加数据
		 */
		logDebug(message, data) {
			if (this.config && this.config.debugMode) {
				if (data) {
					console.log(`[Argon Mermaid] ${message}`, data);
				} else {
					console.log(`[Argon Mermaid] ${message}`);
				}
			}
		},

		/**
		 * 错误日志
		 * @param {string} message - 错误信息
		 * @param {Error} error - 错误对象
		 */
		logError(message, error) {
			console.error(`[Argon Mermaid] ${message}`, error);
		},

		// ---------- 初始化 ----------

		/**
		 * 初始化渲染引擎
		 */
		init() {
			// 检查是否启用
			if (typeof window.argonMermaidConfig !== 'undefined' && !window.argonMermaidConfig.enabled) {
				this.logDebug('Mermaid 支持未启用');
				return;
			}

			// 检查 Mermaid 库是否加载
			if (typeof window.mermaid === 'undefined') {
				this.logDebug('Mermaid 库未加载，等待加载...');
				
				// 等待库加载（最多等待 5 秒）
				let attempts = 0;
				const checkInterval = setInterval(() => {
					attempts++;
					if (typeof window.mermaid !== 'undefined') {
						clearInterval(checkInterval);
						this.logDebug('Mermaid 库加载完成');
						this.initAndRender();
					} else if (attempts >= 50) {
						clearInterval(checkInterval);
						this.logError('Mermaid 库加载超时');
					}
				}, 100);
				
				return;
			}

			this.initAndRender();
		},

		/**
		 * 初始化并渲染
		 */
		initAndRender() {
			// 初始化配置
			if (!this.initConfig()) {
				return;
			}

			// 渲染所有图表
			this.renderAllCharts();

			// 监听主题切换
			this.listenThemeSwitch();

			this.logDebug('Mermaid 渲染引擎初始化完成');
		}
	};

	// ---------- 启动渲染引擎 ----------

	// 暴露到全局作用域（用于库加载失败时的降级处理）
	window.MermaidRenderer = MermaidRenderer;

	// 在 DOM 加载完成后初始化
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			MermaidRenderer.init();
		});
	} else {
		// DOM 已加载完成，直接初始化
		MermaidRenderer.init();
	}

	// 暴露到全局（用于 PJAX 等场景）
	window.ArgonMermaidRenderer = MermaidRenderer;

})();
/* ========== End of Mermaid 图表渲染引擎 ========== */
