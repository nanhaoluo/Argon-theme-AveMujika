<!-- 文章操作按钮：分享和评论 -->
<div id="post_actions_container">
	
	<!-- 分享功能 -->
	<?php if (get_option("argon_show_sharebtn") != 'false') { ?>
	<div id="share_container">
		<div id="share" data-initialized="true">
			<?php if (get_option('argon_show_sharebtn') != 'abroad') { ?>
			<a target="_blank" class="no-pjax icon-douban" tooltip="<?php _e('分享到豆瓣', 'argon'); ?>">
				<button class="btn btn-icon btn-primary">
					<span aria-hidden="true">豆</span>
				</button>
			</a>
			<a target="_blank" class="no-pjax icon-qq" tooltip="<?php _e('分享到 QQ', 'argon'); ?>">
				<button class="btn btn-icon btn-primary">
					<span class="btn-inner--icon"><i class="fa fa-qq"></i></span>
				</button>
			</a>
			<a target="_blank" class="no-pjax icon-qzone" tooltip="<?php _e('分享到 QQ 空间', 'argon'); ?>">
				<button class="btn btn-icon btn-primary">
					<span class="btn-inner--icon"><i class="fa fa-star"></i></span>
				</button>
			</a>
			<a target="_blank" class="no-pjax icon-weibo" tooltip="<?php _e('分享到微博', 'argon'); ?>">
				<button class="btn btn-icon btn-primary">
					<span class="btn-inner--icon"><i class="fa fa-weibo"></i></span>
				</button>
			</a>
			<?php } if (get_option('argon_show_sharebtn') != 'domestic') { ?>
			<a target="_blank" class="no-pjax icon-facebook" tooltip="<?php _e('分享到 Facebook', 'argon'); ?>">
				<button class="btn btn-icon btn-primary">
					<span class="btn-inner--icon"><i class="fa fa-facebook"></i></span>
				</button>
			</a>
			<a target="_blank" class="no-pjax icon-twitter" tooltip="<?php _e('分享到 Twitter', 'argon'); ?>">
				<button class="btn btn-icon btn-primary">
					<span class="btn-inner--icon"><i class="fa fa-twitter"></i></span>
				</button>
			</a>
			<a target="_blank" class="no-pjax icon-telegram" href="https://telegram.me/share/url?url=<?php echo urlencode($_SERVER['HTTP_HOST']);?>&text=<?php echo urlencode(html_entity_decode(get_the_title()));?>" tooltip="<?php _e('分享到 Telegram', 'argon'); ?>">
				<button class="btn btn-icon btn-primary">
					<span class="btn-inner--icon"><i class="fa fa-telegram"></i></span>
				</button>
			</a>
			<?php } ?>
			<a target="_blank" class="no-pjax icon-copy-link" id="share_copy_link">
				<button class="btn btn-icon btn-default">
					<span class="btn-inner--icon"><i class="fa fa-link"></i></span>
				</button>
				<div class="wechat-qrcode">
					<h4><?php _e('扫描二维码', 'argon'); ?></h4>
					<div class="qrcode" id="copy_link_qrcode"></div>
					<div class="help"><?php _e('扫描二维码或点击复制链接', 'argon'); ?></div>
				</div>
			</a>
		</div>
		<button id="share_show" class="btn btn-icon btn-primary" tooltip="<?php _e('分享', 'argon'); ?>">
			<span class="btn-inner--icon"><i class="fa fa-share"></i></span>
		</button>
	</div>
	<?php } ?>

	<!-- 评论切换按钮 -->
	<?php if (comments_open() || get_comments_number()) { ?>
	<button id="comments_toggle" class="btn btn-primary">
		<span class="btn-inner--icon"><i class="fa fa-comments"></i></span>
		<span class="btn-inner--text"><?php _e('展开评论', 'argon'); ?></span>
	</button>
	<?php } ?>

</div>


<script type="text/javascript">
(function() {
	// 分享功能
	<?php if (get_option("argon_show_sharebtn") != 'false') { ?>
	socialShare("#share", {
		title: '<?php echo addslashes(html_entity_decode(get_the_title())); ?>',
		description: '<?php echo addslashes(html_entity_decode(wp_trim_words(html_entity_decode(get_the_content()), 50)));?>',
		wechatQrcodeTitle: "<?php _e('分享到微信', 'argon');?>",
		wechatQrcodeHelper: '<?php _e('微信扫描二维码', 'argon');?>',
		source: '<?php global $post; echo get_permalink($post -> ID); ?>'
	});

	$("#share_show")[0].onclick = function() {
		$("#share_container").addClass("opened");
	};

	// 点击其他地方关闭分享
	$(document).on('click', function(e) {
		if (!$(e.target).closest('#share_container').length) {
			$("#share_container").removeClass("opened");
		}
	});

	// 生成复制链接的二维码
	$(document).ready(function() {
		function initQRCode() {
			if (typeof QRCode !== 'undefined') {
				new QRCode(document.getElementById("copy_link_qrcode"), {
					text: window.location.href,
					width: 128,
					height: 128,
					colorDark: "#000000",
					colorLight: "#ffffff",
					correctLevel: QRCode.CorrectLevel.M
				});
			} else {
				$("#copy_link_qrcode").html('<img src="https://api.qrserver.com/v1/create-qr-code/?size=128x128&data=' + encodeURIComponent(window.location.href) + '" alt="QR Code" style="width: 128px; height: 128px;">');
			}
		}
		setTimeout(initQRCode, 100);
	});

	$("#share_copy_link")[0].onclick = function() {
		let input = document.createElement('input');
		document.body.appendChild(input);
		input.setAttribute("value", window.location.href);
		input.setAttribute("readonly", "readonly");
		input.setAttribute("style", "opacity: 0;pointer-events:none;");
		input.select();
		if (document.execCommand('copy')) {
			iziToast.show({
				title: '<?php _e('链接已复制', 'argon');?>',
				message: "<?php _e('链接已复制到剪贴板', 'argon');?>",
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
		} else {
			iziToast.show({
				title: '<?php _e('复制失败', 'argon');?>',
				message: "<?php _e('请手动复制链接', 'argon');?>",
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
		}
		document.body.removeChild(input);
	};
	<?php } ?>

	// 评论切换功能
	<?php if (comments_open() || get_comments_number()) { ?>
	var commentsToggleBtn = $("#comments_toggle");
	if (commentsToggleBtn.length > 0) {
		commentsToggleBtn[0].onclick = function() {
			var commentsArea = $("#comments");
			var postCommentArea = $("#post_comment");
			var icon = commentsToggleBtn.find("i");
			var text = commentsToggleBtn.find(".btn-inner--text");
			
			if (commentsArea.hasClass("comments-collapsed")) {
				commentsArea.removeClass("comments-collapsed");
				commentsToggleBtn.addClass("expanded");
				setTimeout(function() {
					postCommentArea.removeClass("comments-collapsed");
				}, 150);
				icon.removeClass("fa-comments").addClass("fa-comment-o");
				text.text("<?php _e('折叠评论', 'argon'); ?>");
				setTimeout(function() {
					if (commentsArea.length > 0) {
						$('html, body').animate({
							scrollTop: commentsArea.offset().top - 100
						}, 500);
					}
				}, 300);
			} else {
				postCommentArea.addClass("comments-collapsed");
				commentsToggleBtn.removeClass("expanded");
				setTimeout(function() {
					commentsArea.addClass("comments-collapsed");
				}, 150);
				icon.removeClass("fa-comment-o").addClass("fa-comments");
				text.text("<?php _e('展开评论', 'argon'); ?>");
			}
		};

		// 初始化按钮状态
		$(document).ready(function() {
			var commentsArea = $("#comments, #post_comment");
			var icon = commentsToggleBtn.find("i");
			var text = commentsToggleBtn.find(".btn-inner--text");
			if (commentsArea.hasClass("comments-collapsed")) {
				icon.removeClass("fa-comment-o").addClass("fa-comments");
				text.text("<?php _e('展开评论', 'argon'); ?>");
				commentsToggleBtn.removeClass("expanded");
			} else {
				icon.removeClass("fa-comments").addClass("fa-comment-o");
				text.text("<?php _e('折叠评论', 'argon'); ?>");
				commentsToggleBtn.addClass("expanded");
			}
		});
	}
	<?php } ?>
})();
</script>
