<?php

require_once("class.Item.php");
require_once("class.CustomPostType.php");


class ItemMC extends Item {
	
	const NAME = "MC2";
	const ID = "idmc2";
	
	function __construct  ($post_id = NULL) {
	}
	
	function getPost ($post_id) {
		
		return false;
	}
	
	
	function loadX ($post, $data) {
		global $post;
	
		// Nonce field for some validation
		wp_nonce_field ( plugin_basename ( __FILE__ ), 'custom_post_type' );
	
		// Get all inputs from $data
		$custom_fields = $data ['args'] [0];
	
		// Get the saved values
		$meta = get_post_custom ( $post->ID );
	
		// Check the array and loop through it
		if (! empty ( $custom_fields )) {
			/* Loop through $custom_fields */
			foreach ( $custom_fields as $label => $type ) {
				$field_id_name = strtolower ( str_replace ( ' ', '_', $data ['id'] ) ) . '_' . strtolower ( str_replace ( ' ', '_', $label ) );
	
				echo '<label for="' . $field_id_name . '">' . $label . '</label><input type="text" name="custom_meta[' . $field_id_name . ']" id="' . $field_id_name . '" value="AAA' . $meta [$field_id_name] [0] . '" />';
			}
		}
	}
	
	
	
	function init() {
		
		// parent::init(self::ID, self::NAME);
		
		$book = new CustomPostType( 'Book' );
		$book->add_taxonomy( 'xas', array ('hierarchical' => true) );
		$book->add_taxonomy( 'author' );
		
		$book->add_meta_box(
				'Book Info',
				array(
						'Year' => 'text',
						'Genre' => 'text'
				),
				'normal',
				'default',
				array ('ItemMC', 'loadX')
				
				
		);
		
// 		$book->add_meta_box(
// 				'Author Info',
// 				array(
// 						'Name' => 'text',
// 						'Nationality' => 'text',
// 						'Birthday' => 'text'
// 				)
// 		);
		
	}
	
	function addEditor ($post, $vars) {
		
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