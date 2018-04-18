<?php

require_once("CPT_Item.php");
require_once __DIR__ . '/../eal/EAL_ItemSC.php';
require_once __DIR__ . '/../eal/EAL_Factory.php';



class CPT_ItemSC extends CPT_Item {
	
	
	
	public function __construct() {
	
		parent::__construct();
	
		$this->type = 'itemsc';
		$this->label = 'Single Choice';
		$this->menu_pos = 0;
		$this->dashicon = 'dashicons-marker';
		
		unset($this->table_columns['item_type']);
	}
	

	public function addHooks() {

		parent::addHooks();
		
		add_action ("save_post_{$this->type}", array ('CPT_ItemSC', 'save_post'), 10, 2);
		add_action ("save_post_revision", array ('CPT_ItemSC', 'save_post'), 10, 2);
	}
	
	
	/**
	 * $item to store might already be loaed (e.g., during import); otherwise loaded from $_POST data
	 * save is called twice per update
	 * 1) for the revision --> $revision will contain the id of the parent post
	 * 2) for the current version --> $revision will be FALSE
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	
	public static function save_post (int $post_id, WP_Post $post) {
		
		global $item;
		if ($item === NULL) {
			$item = EAL_Factory::createNewItemSC();	// load item from $_POST data
		}
		
		$revision = wp_is_post_revision ($post_id);
		$type = ($revision === FALSE) ? $post->post_type : get_post_type($revision);
		if ($type != $item->getType()) return;
		
		$item->setId($post_id);		// set the correct id
		DB_ItemSC::saveToDB($item);
	}
		
	
	
	public function filter_wp_get_revision_ui_diff (array $diff, $compare_from, $compare_to) {
			
		
		if ($compare_from instanceof  WP_Post) {
			if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
		}
		if ($compare_to instanceof  WP_Post) {
			if (get_post ($compare_to->post_parent)->post_type != $this->type) return $diff;
		}
		
		$eal_From = ($compare_from instanceof  WP_Post) ? EAL_Factory::createNewItemSC($compare_from->ID) : new EAL_ItemSC();
		$eal_To = ($compare_to instanceof  WP_Post) ? EAL_Factory::createNewItemSC($compare_to->ID) : new EAL_ItemSC();
	
		$diff[0] = HTML_Item::compareTitle($eal_From, $eal_To);
		$diff[1] = HTML_Item::compareDescription($eal_From, $eal_To);
		$diff[2] = HTML_Item::compareQuestion($eal_From, $eal_To);
		$diff[3] = HTML_ItemSC::compareAnswers($eal_From, $eal_To);
		$diff[4] = HTML_Item::compareLevel($eal_From, $eal_To);
		$diff[5] = HTML_Item::compareNoteFlag($eal_From, $eal_To);
		$diff[6] = HTML_Item::compareLearningOutcome($eal_From, $eal_To);
		
		return $diff;
	}	
	
	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = EAL_Factory::createNewItemSC();
		parent::WPCB_register_meta_box_cb();
	}
	

	

		
	


}

	





?>