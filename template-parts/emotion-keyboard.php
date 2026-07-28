<?php
	$emotionList = argon_get_emotion_list();
	$stickerPlaceholder = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
?>
<div id="emotion_keyboard" class="emotion-keyboard">
	<div class="emotion-keyboard-toolbar">
		<label class="emotion-keyboard-search" for="emotion_search_input">
			<i class="fa fa-search" aria-hidden="true"></i>
			<span class="sr-only"><?php echo esc_html(__('搜索表情', 'argon')); ?></span>
			<input id="emotion_search_input" class="argon-text-input" type="search" autocomplete="off" spellcheck="false" placeholder="<?php echo esc_attr(__('搜索表情名称或代码', 'argon')); ?>" aria-controls="emotion_keyboard_content" aria-describedby="emotion_search_result">
			<button class="emotion-keyboard-search-clear" type="button" aria-label="<?php echo esc_attr(__('清空表情搜索', 'argon')); ?>" hidden>
				<i class="fa fa-times" aria-hidden="true"></i>
			</button>
		</label>
		<span id="emotion_search_result" class="sr-only" role="status" aria-live="polite"></span>
	</div>
	<div id="emotion_keyboard_content" class="emotion-keyboard-content">
		<?php
			foreach ($emotionList as $groupIndex => $group){ 
				$className = "emotion-group";
				$footerParts = array();
				if (!empty($group['description'])) $footerParts[] = $group['description'];
				if (!empty($group['source'])) $footerParts[] = $group['source'];
				$groupName = isset($group['groupname']) ? $group['groupname'] : '';
				$groupSearchText = trim($groupName . ' ' . implode(' ', $footerParts));
				if (!empty($group['list'][0]['type']) && $group['list'][0]['type'] === 'sticker'){
					$className .= " emotion-group-stickers";
				}
				if ($groupIndex != 0){
					$className .= " d-none";
				}
				echo "<div class='" . esc_attr($className) . "' data-index='" . esc_attr($groupIndex) . "' data-search='" . esc_attr($groupSearchText) . "'>";
				echo "<div class='emotion-group-search-title'>" . esc_html($groupName) . "</div>";
				foreach ($group['list'] as $index => $emotion){
					if (isset($emotion['title'])){
						$title = $emotion['title'];
					}else{
						$title = "";
					}
					if (isset($emotion['type']) && $emotion['type'] == 'text'){
						$itemSearchText = trim($emotion['text'] . ' ' . $title);
						echo "<div class='emotion-item' text='" . esc_attr($emotion['text']) . "' data-search='" . esc_attr($itemSearchText) . "'>" . esc_html($emotion['text']) . "</div>";
					}
					if (isset($emotion['type']) && $emotion['type'] == 'sticker'){
						$animatedClass = !empty($emotion['animated']) ? ' emotion-item-animated' : '';
						$animatedValue = !empty($emotion['animated']) ? 'true' : 'false';
						$itemSearchText = trim($emotion['code'] . ' ' . $title);
						echo "<div class='emotion-item emotion-item-sticker" . esc_attr($animatedClass) . "' code='" . esc_attr($emotion['code']) . "' data-search='" . esc_attr($itemSearchText) . "'><img src='" . esc_attr($stickerPlaceholder) . "' data-sticker-src='" . esc_url($emotion['src']) . "' data-sticker-animated='" . esc_attr($animatedValue) . "' alt='" . esc_attr($title) . "' loading='lazy' decoding='async' referrerpolicy='strict-origin-when-cross-origin' draggable='false'/>";
						if ($title !== ''){
							echo "<span class='emotion-item-title' title='" . esc_attr($title) . "'>" . esc_html($title) . "</span>";
						}
						echo "</div>";
					}
				}
				if ($footerParts){
					echo "<div class='emotion-group-description'>" . esc_html(implode(' · ', $footerParts)) . "</div>";
				}
				echo "</div>"; 
			}
		?>
		<div class="emotion-keyboard-empty d-none">
			<i class="fa fa-search" aria-hidden="true"></i>
			<span><?php echo esc_html(__('没有找到匹配的表情', 'argon')); ?></span>
		</div>
	</div>
	<div class="emotion-keyboard-navigation">
		<button class="emotion-keyboard-scroll emotion-keyboard-scroll-prev" type="button" data-direction="-1" aria-label="<?php echo esc_attr(__('向左滚动表情分类', 'argon')); ?>">
			<i class="fa fa-chevron-left" aria-hidden="true"></i>
		</button>
		<div class="emotion-keyboard-bar" role="tablist" tabindex="0" aria-label="<?php echo esc_attr(__('表情分类', 'argon')); ?>">
			<?php
				foreach ($emotionList as $groupIndex => $group){
					$className = "emotion-group-name";
					$isActive = $groupIndex == 0;
					if ($isActive){
						$className .= " active";
					}
					echo "<button type='button' class='" . esc_attr($className) . "' data-index='" . esc_attr($groupIndex) . "' role='tab' aria-selected='" . ($isActive ? 'true' : 'false') . "'>" . esc_html($group['groupname']) . "</button>";
				}
			?>
		</div>
		<button class="emotion-keyboard-scroll emotion-keyboard-scroll-next" type="button" data-direction="1" aria-label="<?php echo esc_attr(__('向右滚动表情分类', 'argon')); ?>">
			<i class="fa fa-chevron-right" aria-hidden="true"></i>
		</button>
	</div>
</div>
