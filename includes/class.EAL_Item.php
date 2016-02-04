<?php

class EAL_Item {
	
	public $id;
	public $title;
	public $description;
	public $question;
	
	public $level_FW;
	public $level_PW;
	public $level_KW;
	
	public static $levels = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	public function init ($post_id, $post) {
	
		$this->id = $post_id;
		$this->title = $post->post_title;
		$this->description = isset($_POST['item_description']) ? $_POST['item_description'] : null;
		$this->question = isset ($_POST['item_question']) ? $_POST['item_question'] : null;
		$this->level_FW = isset ($_POST['item_level_FW']) ? $_POST['item_level_FW'] : null;
		$this->level_KW = isset ($_POST['item_level_KW']) ? $_POST['item_level_KW'] : null;
		$this->level_PW = isset ($_POST['item_level_PW']) ? $_POST['item_level_PW'] : null;
	}
	
	
	
	
	public function load ($eal_posttype) {
		
		global $post;
		
		echo ("<script>console.log('__construct in " . get_class() . " with status== " . (get_post_status($post->ID)) . "');</script>");
		
		if (get_post_status($post->ID)=='auto-draft') {
				
			$this->id = $post->ID;
			$this->title = '';
			$this->description = '';
			$this->question = '';
			$this->level_FW = 0;
			$this->level_PW = 0;
			$this->level_KW = 0;
				
		} else {
				
			global $wpdb;
			$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$eal_posttype} WHERE id = {$post->ID}", ARRAY_A);
			$this->id = $sqlres['id'];
			$this->title = $sqlres['title'];
			$this->description = $sqlres['description'];
			$this->question = $sqlres['question'];
			$this->level_FW = $sqlres['level_FW'];
			$this->level_PW = $sqlres['level_PW'];
			$this->level_KW = $sqlres['level_KW'];
				
		}
		
	}
	
	public function getPoints() { return -1; }
}

?>