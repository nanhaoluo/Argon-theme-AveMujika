<?php
	$emotionList = argon_get_emotion_list();
	$stickerPlaceholder = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
?>
<div id="emotion_keyboard" class="emotion-keyboard">
	<div class="emotion-keyboard-content">
		<?php
			foreach ($emotionList as $groupIndex => $group){ 
				$className = "emotion-group";
				if (!empty($group['list'][0]['type']) && $group['list'][0]['type'] === 'sticker'){
					$className .= " emotion-group-stickers";
				}
				if ($groupIndex != 0){
					$className .= " d-none";
				}
				echo "<div class='" . esc_attr($className) . "' data-index='" . esc_attr($groupIndex) . "'>";
				foreach ($group['list'] as $index => $emotion){
					if (isset($emotion['title'])){
						$title = $emotion['title'];
					}else{
						$title = "";
					}
					if (isset($emotion['type']) && $emotion['type'] == 'text'){
						echo "<div class='emotion-item' text='" . esc_attr($emotion['text']) . "'>" . esc_html($emotion['text']) . "</div>";
					}
					if (isset($emotion['type']) && $emotion['type'] == 'sticker'){
						$animatedClass = !empty($emotion['animated']) ? ' emotion-item-animated' : '';
						$animatedValue = !empty($emotion['animated']) ? 'true' : 'false';
						echo "<div class='emotion-item emotion-item-sticker" . esc_attr($animatedClass) . "' code='" . esc_attr($emotion['code']) . "'><img src='" . esc_attr($stickerPlaceholder) . "' data-sticker-src='" . esc_url($emotion['src']) . "' data-sticker-animated='" . esc_attr($animatedValue) . "' alt='" . esc_attr($title) . "' loading='lazy' decoding='async' referrerpolicy='strict-origin-when-cross-origin' draggable='false'/>";
						if ($title !== ''){
							echo "<span class='emotion-item-title' title='" . esc_attr($title) . "'>" . esc_html($title) . "</span>";
						}
						echo "</div>";
					}
				}
				echo "</div>"; 
			}
		?>
	</div>
	<div class="emotion-keyboard-descriptions" aria-live="polite">
		<?php
			foreach ($emotionList as $groupIndex => $group){
				$footerParts = array();
				if (!empty($group['description'])) $footerParts[] = $group['description'];
				if (!empty($group['source'])) $footerParts[] = $group['source'];
				if (!$footerParts) continue;
				$className = 'emotion-group-description';
				if ($groupIndex != 0) $className .= ' d-none';
				echo "<div class='" . esc_attr($className) . "' data-index='" . esc_attr($groupIndex) . "'>" . esc_html(implode(' · ', $footerParts)) . "</div>";
			}
		?>
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
