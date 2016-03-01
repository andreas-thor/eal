<?php

function eal_item_mc_add_meta_boxes (WP_Post $post) {

	
	$type = 'eal_item_mc';
	add_meta_box('mb_' . $type . '_desc', 'Fall- oder Problemvignette', $type . '_add_editor', $type, 'normal', 'default', ['id' => 'mb_' . $type . '_desc_editor']);
	add_meta_box('mb_' . $type . '_ques', 'Aufgabenstellung'   , $type . '_add_editor', $type, 'normal', 'default', ['id' => 'mb_' . $type . '_ques_editor']);
	add_meta_box('mb_' . $type . '_level', 'Anforderungsstufe'   , $type . '_add_level', $type, 'side', 'default', ['id' => 'mb_' . $type . '_level']);
	
}



function eal_item_mc_add_editor ($post, $vars) {
	$editor_settings = array(
			'media_buttons' => false,	// no media buttons
			'teeny' => trye,			// minimal editor
			'quicktags' => false,		// hides Visual/Text tabs
			'textarea_rows' => 3,
			'tinymce' => true
	);

	$html = wp_editor( get_post_meta($post->ID, $vars['args']['id'], true), $vars['args']['id'], $editor_settings );
	echo $html;

	// 	echo '<input type="text" name="_location" value="7"  />';
}


function eal_item_mc_add_level ($post, $vars) {
	
	$colNames = ["FW"=>"", "KW"=>"", "PW"=>""];
	$html  = '<table><tr><td></td>';
	foreach ($colNames as $c=>$v) {
		$html .= '<td>' . $c . '</td>';
		$colNames[$c] = get_post_meta($post->ID, $c, true);
	}
	
	$html .= '</tr>';
			
	$rowNames = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
	foreach ($rowNames as $n => $r) {
		$html .= '<tr><td>' . ($n+1) . ". " . $r . '</td>';
		foreach ($colNames as $c=>$v) {
			$html .= '<td align="center"><input type="radio" id="' . $vars['args']['id'] . '_' . $c . '_' . $r . '" name="' . $c . '" value="' . $r . '"' . (($r==$v)?' checked':'') . '></td>';
		}
		$html .= '</tr>';
	}
	$html .= '</table>';
	
	echo $html;
}


function eal_item_mc_save_post ($post_id) {
	
	$type = 'eal_item_mc';
	$post = get_post ($post_id);
	if ($post->post_type == $type) {
		
		if (isset($_REQUEST['mb_' . $type . '_desc_editor'])) {
			update_post_meta ($post_id, 'mb_' . $type . '_desc_editor', $_REQUEST['mb_' . $type . '_desc_editor']);
		}
		
		if (isset($_REQUEST['mb_' . $type . '_ques_editor'])) {
			update_post_meta ($post_id, 'mb_' . $type . '_ques_editor', $_REQUEST['mb_' . $type . '_ques_editor']);
		}
		
		$colNames = ["FW", "KW", "PW"];
		foreach ($colNames as $c) {
			if (isset($_REQUEST[$c])) {
				update_post_meta ($post_id, $c, $_REQUEST[$c]);
			}
		}
	}
	
	
// 	$post = get_post($post_id);
// 	echo "<h1>YES!</h1>";
}

?>