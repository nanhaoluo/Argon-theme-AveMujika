<?php
	$emotionList = argon_get_emotion_list();
	$stickerPlaceholder = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
?>
<div id="emotion_keyboard" class="emotion-keyboard">
	<div class="emotion-keyboard-content">
		<?php
			foreach ($emotionList as $groupIndex => $group){ 
				$className = "emotion-group";
				if ($groupIndex != 0){
					$className .= " d-none";
				}
				echo "<div class='" . esc_attr($className) . "' index='" . esc_attr($groupIndex) . "'>";
				foreach ($group['list'] as $index => $emotion){
					if (isset($emotion['title'])){
						$title = $emotion['title'];
					}else{
						$title = "";
					}
					if (isset($emotion['type']) && $emotion['type'] == 'text'){
						echo "<div class='emotion-item' text='" . esc_attr($emotion['text']) . "' title='" . esc_attr($title) . "'>" . esc_html($emotion['text']) . "</div>";
					}
					if (isset($emotion['type']) && $emotion['type'] == 'sticker'){
						$animatedClass = !empty($emotion['animated']) ? ' emotion-item-animated' : '';
						$animatedValue = !empty($emotion['animated']) ? 'true' : 'false';
						echo "<div class='emotion-item emotion-item-sticker" . esc_attr($animatedClass) . "' code='" . esc_attr($emotion['code']) . "' title='" . esc_attr($title) . "'><img src='" . esc_attr($stickerPlaceholder) . "' data-sticker-src='" . esc_url($emotion['src']) . "' data-sticker-animated='" . esc_attr($animatedValue) . "' alt='" . esc_attr($title) . "' loading='lazy' decoding='async' referrerpolicy='same-origin' draggable='false'/></div>";
					}
				}
				if (isset($group['description'])){
					echo "<div class='emotion-group-description'>" . esc_html($group['description']) . "</div>";
				}
				echo "</div>"; 
			}
		?>
	</div>
	<div class="emotion-keyboard-bar">
		<?php
			foreach ($emotionList as $groupIndex => $group){
				$className = "emotion-group-name";
				if ($groupIndex == 0){
					$className .= " active";
				}
				echo "<div class='" . esc_attr($className) . "' index='" . esc_attr($groupIndex) . "'>" . esc_html($group['groupname']) . "</div>";
			}
		?>
	</div>
</div>
