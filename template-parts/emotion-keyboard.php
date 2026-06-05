<?php
	global $emotionListDefault;
	$emotionList = apply_filters("argon_emotion_list", $emotionListDefault);
?>
<div id="emotion_keyboard" class="emotion-keyboard">
	<div class="emotion-keyboard-content">
		<?php
			foreach ($emotionList as $groupIndex => $group){ 
				$className = "emotion-group";
				if ($groupIndex != 0){
					$className .= " d-none";
				}
				echo "<div class='" . $className . "' index='" . $groupIndex . "'>";
				foreach ($group['list'] as $index => $emotion){
					if (isset($emotion['title'])){
						$title = $emotion['title'];
					}else{
						$title = "";
					}
					if ($emotion['type'] == 'text'){
						echo "<div class='emotion-item' text='" . esc_attr($emotion['text']) . "' title='" . esc_attr($title) . "'>" . esc_html($emotion['text']) . "</div>";
					}
					if ($emotion['type'] == 'sticker'){
						echo "<div class='emotion-item emotion-item-sticker' code='" . $emotion['code'] . "' title='" . esc_attr($title) . "'><img src='" . $emotion['src'] . "' loading='lazy'/></div>";
					}
				}
				if (isset($group['description'])){
					echo "<div class='emotion-group-description'>" . $group['description'] . "</div>";
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
				echo "<div class='" . $className . "' index='" . $groupIndex . "'>" . $group['groupname'] . "</div>"; 
			}
		?>
	</div>
</div>