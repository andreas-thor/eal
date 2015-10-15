<?php

require_once("class.Item.php");

class ItemMC extends Item {
	
	const NAME = "MC2";
	const ID = "idmc2";
	
	function __construct  ($post_id = NULL) {
	}
	
	function getPost ($post_id) {
		
		return false;
	}
	
	
	function init() {
		
		parent::init(self::ID, self::NAME);
		
		
	}
	
	function addEditor ($post, $vars) {
		echo ("b");
		
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => trye,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
		
		$html = wp_editor( get_post_meta($post->ID, $vars['args']['id'], true), $vars['args']['id'], $editor_settings );
		echo $html;
	}
}

?>