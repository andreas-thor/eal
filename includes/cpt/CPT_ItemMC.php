<?php

require_once("CPT_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemMC.php");

class CPT_ItemMC extends CPT_Item {
	
	
	
	public function __construct() {
	
		parent::__construct();
	
		$this->type = 'itemmc';
		$this->label = 'Multiple Choice';
		$this->menu_pos = 0;
		$this->dashicon = 'dashicons-forms';
		
		unset($this->table_columns['item_type']);
	}
	
	
	public function addHooks() {
		
		parent::addHooks();
		add_action ("save_post_{$this->type}", array ('CPT_ItemMC', 'save_post'), 10, 2);
		add_action ("save_post_revision", array ('CPT_ItemMC', 'save_post'), 10, 2);
	}
	
	
	public static function save_post (int $post_id, WP_Post $post) {
		
		global $item;
		if ($item === NULL) {
			$item = EAL_Factory::createNewItemMC();	// load item from $_POST data
		}
		
		$revision = wp_is_post_revision ($post_id);
		$type = ($revision === FALSE) ? $post->post_type : get_post_type($revision);
		if ($type != $item->getType()) return;
		
		$item->setId($post_id);		// set the correct id
		DB_ItemMC::saveToDB($item);
	}
	
	

	public function filter_wp_get_revision_ui_diff (array $diff, $compare_from, $compare_to) {
		
		
		if ($compare_from instanceof  WP_Post) {
			if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
		}
		if ($compare_to instanceof  WP_Post) {
			if (get_post ($compare_to->post_parent)->post_type != $this->type) return $diff;
		}
		
		$eal_From = ($compare_from instanceof  WP_Post) ? EAL_Factory::createNewItemMC($compare_from->ID) : new EAL_ItemMC();
		$eal_To = ($compare_to instanceof  WP_Post) ? EAL_Factory::createNewItemMC($compare_to->ID) : new EAL_ItemMC();
		
		$diff[0] = HTML_Item::compareTitle($eal_From, $eal_To); 
		$diff[1] = HTML_Item::compareDescription($eal_From, $eal_To); 
		$diff[2] = HTML_Item::compareQuestion($eal_From, $eal_To); 
		$diff[3] = HTML_ItemMC::compareAnswers($eal_From, $eal_To);
		$diff[4] = HTML_Item::compareLevel($eal_From, $eal_To);
		$diff[5] = HTML_Item::compareNoteFlag($eal_From, $eal_To);
		$diff[6] = HTML_Item::compareLearningOutcome($eal_From, $eal_To);
		
		return $diff;
	}	
	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = EAL_Factory::createNewItemMC();
		parent::WPCB_register_meta_box_cb();
		
	}

	

	
	
}

	


?>