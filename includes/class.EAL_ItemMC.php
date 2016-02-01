<?php

require_once ("class.EAL_Item.php");

class EAL_ItemMC extends EAL_Item {
	
	public $answers;
	
	
	function __construct  ($post) {
		
		echo ("<script>console.log('__construct in " . get_class() . " with post==null? " . (($post==null)?1:0) . "');</script>");
		echo ("<script>console.log('__construct in " . get_class() . " with post_title==null? " . (($post->post_title==null)?1:0) . "');</script>");
		echo ("<script>console.log('__construct in " . get_class() . " with status== " . (get_post_status($post->ID)) . "');</script>");
		
		
		if (get_post_status($post->ID)=='auto-draft') {
			
			$this->title = '';
			$this->description = '';
			$this->question = '';
			$this->level_FW = 0;
			$this->level_PW = 0;
			$this->level_KW = 0;
			
			$this->answers = array (
					array ('answer' => '', 'positive' => 1, 'negative' => 0), 
					array ('answer' => '', 'positive' => 1, 'negative' => 0),
					array ('answer' => '', 'positive' => 0, 'negative' => 1),
					array ('answer' => '', 'positive' => 0, 'negative' => 1)
			);
			
		} else {
			
			global $wpdb;
			$res = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_itemmc WHERE id = {$post->ID}", ARRAY_A);
			$this->title = $res['title'];
			$this->description = $res['description'];
			$this->question = $res['question'];
			$this->level_FW = $res['level_FW'];
			$this->level_PW = $res['level_PW'];
			$this->level_KW = $res['level_KW'];
			
			$this->answers = array();
			$res = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_itemmc_answer WHERE item_id = {$post->ID} ORDER BY id", ARRAY_A);
			foreach ($res as $a) {
				array_push ($this->answers, array ('answer' => $a['answer'], 'positive' => $a['positive'], 'negative' => $a['negative']));
			}
		}
	}
	
}

?>