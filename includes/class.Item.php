<?php

require_once("class.ItemMC.php");

abstract class Item {
	
	
	function __construct($post_id = NULL) {
		if (!empty($post_id)) {
			$this->getPost ($post_id);
		}
	}
	
	function getPost ($post_id) {
		
	}
	
	
	function init($id, $name) {
	
		register_post_type( $id,
				array(
						'labels' => array(
								'name' => $name,
								'singular_name' => $name,
								'add_new' => 'Add ' . $name,
								'add_new_item' => 'Add New ' . $name,
								'edit' => 'Edit',
								'edit_item' => 'Edit ' . $name,
								'new_item' => 'New ' . $name,
								'view' => 'View',
								'view_item' => 'View ' . $name,
								'search_items' => 'Search ' . $name,
								'not_found' => 'No Items found',
								'not_found_in_trash' => 'No Items found in Trash',
								'parent' => 'Parent Item'
						),
	
						'public' => true,
						'menu_position' => 2,
						'supports' => array( 'title'), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
						'taxonomies' => array( 'topic' ),
						'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
						'has_archive' => true,
						'show_in_menu'    => true,
						'register_meta_box_cb' => array ('Item', 'addMetaBoxes')
				)
		);	
	}
	
    function addMetaBoxes () {
    	$type = 'eal_item_mc2';
    	echo ("a");
    	add_meta_box('mb_' . $type . '_desc', 'Fall- oder Problemvignette', array ('ItemMC', 'addEditor'), $type, 'normal', 'default', ['id' => 'mb_' . $type . '_desc_editor']);
    }
	
}

?>