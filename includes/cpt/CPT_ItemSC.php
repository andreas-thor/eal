<?php

require_once 'CPT_Item.php';
require_once __DIR__ . '/../eal/EAL_ItemSC.php';
require_once __DIR__ . '/../db/DB_ItemSC.php';



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
		
		$revision = wp_is_post_revision ($post_id);
		$type = ($revision === FALSE) ? $post->post_type : get_post_type($revision);
		if ($type != EAL_ItemSC::getType()) return;
		
		$item = ($post->post_status === 'auto-draft') ? new EAL_ItemSC($post_id, intval ($_REQUEST['learnout_id'])) : EAL_ItemSC::createFromArray($post_id, $_REQUEST);
		DB_ItemSC::saveToDB($item);
	}
		
	
	
	public function filter_wp_get_revision_ui_diff (array $diff, $compare_from, $compare_to) {
			
		// default items to compare
		$eal_From = new EAL_ItemSC();
		$eal_To = new EAL_ItemSC();
		
		// check type and try to load item revision from database
		if ($compare_from instanceof  WP_Post) {
			if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
			
			try {
				$eal_From = DB_ItemSC::loadFromDB($compare_from->ID);
			} catch (Exception $e) { 
				// could not find revision in the database anymore
			}
 		}
		if ($compare_to instanceof  WP_Post) {
			if (get_post ($compare_to->post_parent)->post_type != $this->type) return $diff;
			
			try {
				$eal_To = DB_ItemSC::loadFromDB($compare_to->ID);
			} catch (Exception $e) {
				// could not find revision in the database anymore
			}
		}
		
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
		
		global $post, $item;
		$item = DB_ItemSC::loadFromDB($post->ID);
		parent::WPCB_register_meta_box_cb();
	}
	

	

		
	


}

	





?>