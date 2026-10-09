<?php
/**
 * 附件下载中转承载页（纯白屏架构）
 *
 * 页面为完全纯白屏，在后台静默完成鉴权、人机验证（若开启）与文件下发。
 *
 * @package Argon
 */

if (!defined('ABSPATH')) {
	exit;
}

$uuid = isset($_GET['download']) ? sanitize_text_field(wp_unslash($_GET['download'])) : '';
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
	wp_die(__('无效的下载请求标识。', 'argon'), __('下载失败', 'argon'), array('response' => 400));
}

$located = argon_attachments_locate($uuid);
if ($located === null) {
	wp_die(__('该附件不存在或已被移除。', 'argon'), __('附件未找到', 'argon'), array('response' => 404));
}
list($post_id, $attachment) = $located;

$captcha_required = function_exists('argon_attachment_is_captcha_enabled') && argon_attachment_is_captcha_enabled();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html($attachment['name']); ?></title>
	<style>
		html, body {
			margin: 0;
			padding: 0;
			width: 100%;
			height: 100%;
			background: #fff;
			overflow: hidden;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
		}
		#argon-download-root {
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			background: #fff;
		}
		#argon-download-captcha-slot {
			display: none;
		}
		#argon-download-captcha-slot.show {
			display: block;
			padding: 24px;
			border-radius: 8px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
			background: #fff;
		}
	</style>
</head>
<body>

<div id="argon-download-root">
	<!-- 预留防刷流 / 极验人机验证插槽 -->
	<div id="argon-download-captcha-slot" data-uuid="<?php echo esc_attr($uuid); ?>">
		<?php do_action('argon_download_page_captcha_slot', $uuid, $attachment); ?>
	</div>
</div>

<script>
(function() {
	"use strict";

	var uuid = <?php echo wp_json_encode($uuid); ?>;
	var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
	var captchaRequired = <?php echo $captcha_required ? 'true' : 'false'; ?>;
	var captchaSlot = document.getElementById("argon-download-captcha-slot");

	function triggerDownload(url) {
		window.location.href = url;
		// 下载触发后，尝试自动关闭/销毁当前残留的独立标签页或窗口
		setTimeout(function() {
			try {
				window.close();
			} catch (e) {}
		}, 600);
	}

	function performDownload(captchaParams) {
		var body = "action=argon_attachment_issue&uuid=" + encodeURIComponent(uuid);
		if (captchaParams) {
			Object.keys(captchaParams).forEach(function(k) {
				body += "&" + encodeURIComponent(k) + "=" + encodeURIComponent(captchaParams[k]);
			});
		}

		fetch(ajaxUrl, {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
			credentials: "same-origin",
			body: body
		}).then(function(res) {
			return res.json();
		}).then(function(resp) {
			if (!resp || !resp.success || !resp.data || !resp.data.url) {
				throw new Error((resp && resp.data && resp.data.message) || "下载凭证获取失败");
			}
			triggerDownload(resp.data.url);
		}).catch(function(err) {
			if (captchaSlot) {
				captchaSlot.classList.add("show");
			}
		});
	}

	if (!captchaRequired) {
		// 无需验证码：页面纯白，静默执行鉴权下发
		performDownload();
	} else {
		// 需要验证码：唤起验证插槽
		if (captchaSlot) {
			captchaSlot.classList.add("show");
		}
	}

	// 暴露全局触发器供极验等第三方验证码 SDK 校验成功后回调
	window.argonTriggerAttachmentDownload = function(captchaData) {
		performDownload(captchaData);
	};
})();
</script>

</body>
</html>
<?php
exit;
