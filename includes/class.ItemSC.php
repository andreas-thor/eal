<?php

require_once("class.Item.php");
require_once("class.CustomPostType.php");


class ItemSC extends Item {
	
	
	function __construct  ($post_id = NULL) {
		
		
	}
	
	function getPost ($post_id) {
		
		return false;
	}
	
	
	public function CPT_init() {
		parent::CPT_init(get_class(), 'SC Question');
	}
	
	function CPT_add_meta_boxes()  {
 		parent::CPT_add_meta_boxes(get_class());
	}
	
	function CPT_add_editor ($post, $vars) {
		parent::CPT_add_editor($post, $vars);
	}
	
	function CPT_add_level ($post, $vars) {
		parent::CPT_add_level($post, $vars);
	}
	
	
	

	
}

	


?>