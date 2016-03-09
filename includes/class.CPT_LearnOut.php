<?php

require_once("class.CPT_Object.php");
require_once("class.EAL_LearnOut.php");

class CPT_LearnOut extends CPT_Object {
	
	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {
		
		$this->type = "learnout";
		$this->label = "Learn. Outcome";
		$this->menu_pos = 7;
		
		parent::init();
		
	}
	
	

	public function WPCB_register_meta_box_cb () {
	
		global $learnout;
		$learnout = new EAL_LearnOut();
		$learnout->load();
	
	
		add_meta_box('mb_description', 'Beschreibung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'learnout_description', 'value' => $learnout->description) );
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $learnout->level, 'prefix' => 'learnout'));
		
		
	}	

	

	public function WPCB_manage_posts_columns($columns) {
		return array_merge(parent::WPCB_manage_posts_columns($columns), array('Items' => 'Items'));
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		return array_merge(parent::WPCB_manage_edit_sortable_columns($columns) , array('Items' => 'Items'));
	}
	
	
	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
		parent::WPCB_manage_posts_custom_column($column, $post_id);
	
		global $post;
	
		switch ( $column ) {
			case 'Items': 
	
				echo ("-1");
				echo ("<h1><a class='page-title-action' href='post-new.php?post_type=itemsc&lo_id={$post->ID}'>Add&nbsp;New&nbsp;SC</a></h1>");
				echo ("<h1><a class='page-title-action' href='post-new.php?post_type=itemmc&lo_id={$post->ID}'>Add&nbsp;New&nbsp;MC</a></h1>");
				break;
		}
	}
	
	
	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array = parent::WPCB_posts_fields($array) . ", (-9) as reviews ";
		}
		return $array;
	}
	
	
	
	
}

?>