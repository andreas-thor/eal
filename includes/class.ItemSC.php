<?php

require_once("class.Item.php");
require_once("class.CustomPostType.php");


class ItemSC extends Item {
	
	
	function __construct  ($post_id = NULL) {
		
		
	}
	
	function getPost ($post_id) {
		
		return false;
	}
	
	
	public static function CPT_init($name=null, $label=null) {
		parent::CPT_init(get_class(), 'SC Question');
	}
	
	public static function CPT_save_post ($ID = false, $post = false) {
		
	}
	
	public static function CPT_delete_post ($post_id)  {
	
	}
	
	public static function CPT_load_post ($post_id)  {
	
	}
	
	static function CPT_add_meta_boxes($name=null, $item=null)  {
 		parent::CPT_add_meta_boxes(get_class());
	}
	
	static function CPT_add_editor ($post, $vars) {
		parent::CPT_add_editor($post, $vars);
	}
	
	static function CPT_add_level ($post, $vars) {
		parent::CPT_add_level($post, $vars);
	}
	
	
	
 	static function CPT_updated_messages( $messages ) {
		
 	}

	static function CPT_contextual_help( $contextual_help, $screen_id, $screen ) {
		
	}
}

	


?>