<?php
	require_once get_template_directory() . '/inc/stickers.php';

	$emotionListDefault = array(
		array(
			'groupname' => __('颜文字', 'argon'),
			'list' => array(
				array('type' => 'text', 'text' => "|´・ω・)ノ"),
				array('type' => 'text', 'text' => "ヾ(≧∇≦*)ゝ"),
				array('type' => 'text', 'text' => "(☆ω☆)"),
				array('type' => 'text', 'text' => "（╯‵□′）╯︵┴─┴"),
				array('type' => 'text', 'text' => "￣﹃￣"),
				array('type' => 'text', 'text' => "(/ω＼)"),
				array('type' => 'text', 'text' => "∠( ᐛ 」∠)＿"),
				array('type' => 'text', 'text' => "(๑•̀ㅁ•́ฅ)"),
				array('type' => 'text', 'text' => "→_→"),
				array('type' => 'text', 'text' => "୧(๑•̀⌄•́๑)૭"),
				array('type' => 'text', 'text' => "٩(ˊᗜˋ*)و"),
				array('type' => 'text', 'text' => "(ノ°ο°)ノ"),
				array('type' => 'text', 'text' => "(´இ皿இ｀)"),
				array('type' => 'text', 'text' => "⌇●﹏●⌇"),
				array('type' => 'text', 'text' => "(ฅ´ω`ฅ)"),
				array('type' => 'text', 'text' => "(╯°A°)╯︵○○○"),
				array('type' => 'text', 'text' => "φ(￣∇￣o)"),
				array('type' => 'text', 'text' => "ヾ(´･ ･｀｡)ノ\""),
				array('type' => 'text', 'text' => "( ง ᵒ̌皿ᵒ̌)ง⁼³₌₃"),
				array('type' => 'text', 'text' => "(ó﹏ò｡)"),
				array('type' => 'text', 'text' => "Σ(っ °Д °;)っ"),
				array('type' => 'text', 'text' => "( ,,´･ω･)ﾉ\"(´っω･｀｡)"),
				array('type' => 'text', 'text' => "╮(╯▽╰)╭"),
				array('type' => 'text', 'text' => "o(*////▽////*)q"),
				array('type' => 'text', 'text' => "＞﹏＜"),
				array('type' => 'text', 'text' => "( ๑´•ω•) \"(ㆆᴗㆆ)")
			)
		),
		array(
			'groupname' => 'Emoji',
			'list' => array(
				array('type' => 'text', 'text' => "😂"),
				array('type' => 'text', 'text' => "😀"),
				array('type' => 'text', 'text' => "😅"),
				array('type' => 'text', 'text' => "😊"),
				array('type' => 'text', 'text' => "🙂"),
				array('type' => 'text', 'text' => "🙃"),
				array('type' => 'text', 'text' => "😌"),
				array('type' => 'text', 'text' => "😍"),
				array('type' => 'text', 'text' => "😘"),
				array('type' => 'text', 'text' => "😜"),
				array('type' => 'text', 'text' => "😝"),
				array('type' => 'text', 'text' => "😏"),
				array('type' => 'text', 'text' => "😒"),
				array('type' => 'text', 'text' => "🙄"),
				array('type' => 'text', 'text' => "😳"),
				array('type' => 'text', 'text' => "😡"),
				array('type' => 'text', 'text' => "😔"),
				array('type' => 'text', 'text' => "😫"),
				array('type' => 'text', 'text' => "😱"),
				array('type' => 'text', 'text' => "😭"),
				array('type' => 'text', 'text' => "💩"),
				array('type' => 'text', 'text' => "👻"),
				array('type' => 'text', 'text' => "🙌"),
				array('type' => 'text', 'text' => "🖕"),
				array('type' => 'text', 'text' => "👍"),
				array('type' => 'text', 'text' => "👫"),
				array('type' => 'text', 'text' => "👬"),
				array('type' => 'text', 'text' => "👭"),
				array('type' => 'text', 'text' => "🌚"),
				array('type' => 'text', 'text' => "🌝"),
				array('type' => 'text', 'text' => "🙈"),
				array('type' => 'text', 'text' => "💊"),
				array('type' => 'text', 'text' => "😶"),
				array('type' => 'text', 'text' => "🙏"),
				array('type' => 'text', 'text' => "🍦"),
				array('type' => 'text', 'text' => "🍉"),
				array('type' => 'text', 'text' => "😣")
			)
		)
	);

	/**
	 * 获取当前可用的表情列表。贴图目录只在真正渲染评论或表情面板时读取。
	 */
	function argon_get_emotion_list() {
		global $emotionListDefault;

		static $emotion_list = null;
		if ($emotion_list === null) {
			$emotion_list = array_merge($emotionListDefault, argon_load_sticker_groups());
		}

		return apply_filters('argon_emotion_list', $emotion_list);
	}

	/**
	 * 一次构建完整替换表，再单次扫描评论，避免按“评论数 × 全部图片数”遍历。
	 * 键使用过滤器提供的原始 code，保留第三方自定义字符的兼容性。
	 */
	function argon_replace_comment_stickers($comment_text) {
		static $sticker_replacements = null;
		if ($sticker_replacements === null) {
			$sticker_replacements = array();
			foreach (argon_get_emotion_list() as $group) {
				if (!isset($group['list']) || !is_array($group['list'])) {
					continue;
				}
				foreach ($group['list'] as $emotion) {
					if (!isset($emotion['type'], $emotion['code'], $emotion['src']) || $emotion['type'] !== 'sticker') {
						continue;
					}
					$code = (string) $emotion['code'];
					if ($code === '') {
						continue;
					}
					$title = isset($emotion['title']) && $emotion['title'] !== '' ? $emotion['title'] : $code;
					$sticker_replacements[':' . $code . ':'] = "<img class='comment-sticker' src='" . esc_url($emotion['src']) . "' alt='" . esc_attr($title) . "' loading='lazy' decoding='async' referrerpolicy='strict-origin-when-cross-origin' draggable='false'/>";
				}
			}
		}

		if (!$sticker_replacements || strpos($comment_text, ':') === false) {
			return $comment_text;
		}

		return strtr($comment_text, $sticker_replacements);
	}
?>
