<?php

class CPT_LearnOut {
	
	
	public $type;
	public $label;
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init() {
		
		$this->type = "learnout";
		$this->label = "Learn. Outcome";
		
		$classname = get_called_class();
		
		register_post_type( $this->type,
				array(
						'labels' => array(
								'name' => $this->label,
								'singular_name' => $this->label,
								'add_new' => 'Add ' . $this->label,
								'add_new_item' => 'Add New ' . $this->label,
								'edit' => 'Edit',
								'edit_item' => 'Edit ' . $this->label,
								'new_item' => 'New ' . $this->label,
								'view' => 'View',
								'view_item' => 'View ' . $this->label,
								'search_items' => 'Search ' . $this->label,
								'not_found' => 'No Items found',
								'not_found_in_trash' => 'No Items found in Trash',
								'parent' => 'Parent Item'
						),
		
						'public' => true,
						'menu_position' => 2,
						'supports' => array( 'title'), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
						'taxonomies' => array( 'topic' ),
						// 'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
						'has_archive' => false, // false to allow for single view
						'show_in_menu'    => true,
						'register_meta_box_cb' => array ($this, 'WPCB_register_meta_box_cb')
				)
		);

		
		
		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$this->type}", array ("eal_{$this->type}", 'save'), 10, 2);
		

		
	}
	
	

	public function WPCB_register_meta_box_cb () {
	
		global $item;
		$item = new EAL_ItemMC();
		$item->load();
	
	
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($this, 'WPCB_mb_description'), $this->type, 'normal', 'default' );
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default');
	
	}	

	
	
	public function WPCB_mb_description ($post, $vars) {
	
		global $item;
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
	
		$html = wp_editor(wpautop(stripslashes($item->description)) , 'item_description', $editor_settings );
		echo $html;
	}

	
	public function WPCB_mb_level ($post, $vars) {
	
		global $item;
		$html = self::generateLevelHTML($item->level);
		echo $html;	
	}
	
	
	
}

?>