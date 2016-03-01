<?php

function eal_item_sc_add_meta_boxes (WP_Post $post) {

	
	$type = 'eal_item_sc';
	add_meta_box('mb_' . $type . '_desc', 'Description', $type . '_add_editor', $type, 'normal', 'default', ['id' => 'mb_' . $type . '_desc_editor']);
	add_meta_box('mb_' . $type . '_ques', 'Question'   , $type . '_add_editor', $type, 'normal', 'default', ['id' => 'mb_' . $type . '_ques_editor']);
}



function eal_item_sc_add_editor ($post, $vars) {
	$editor_settings = array(
			'media_buttons' => false,	// no media buttons
			'teeny' => trye,			// minimal editor
			'quicktags' => false,		// hides Visual/Text tabs
			'textarea_rows' => 3,
			'tinymce' => true
	);

	$html = wp_editor(  $post->ID, $vars['args']['id'], $editor_settings );
	echo $html;

	// 	echo '<input type="text" name="_location" value="7"  />';
}

function eal_item_sc_save_post ($post) {
	
	
}

?>