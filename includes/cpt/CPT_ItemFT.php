<?php

require_once 'CPT_Item.php';
require_once __DIR__ . '/../eal/EAL_ItemFT.php';
require_once __DIR__ . '/../db/DB_ItemFT.php';



class CPT_ItemFT extends CPT_Item {
	
	 
	
	public function __construct() {
	
		parent::__construct();
	
		$this->type = 'itemft';
		$this->label = 'Free Text';
		$this->menu_pos = 0;
		$this->dashicon = 'dashicons-media-text';
		
		unset($this->table_columns['item_type']);
	}
	

	
	/**
	 * $item to store might already be loaed (e.g., during import); otherwise loaded from $_POST data
	 * save is called twice per update
	 * 1) for the revision --> $revision will contain the id of the parent post
	 * 2) for the current version --> $revision will be FALSE
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	
	public function save_post (int $post_id, WP_Post $post, bool $update) {
		
		$revision = wp_is_post_revision ($post_id);
		$type = ($revision === FALSE) ? $post->post_type : get_post_type($revision);
		if ($type != EAL_ItemFT::getType()) return;
		
		global $itemToImport;	// indicates save_post execution during bulk import/update
		
		if (isset($itemToImport)) {
			$item = $itemToImport;
		} else {
			if ($post->post_status === 'auto-draft') {
				$item = new EAL_ItemFT($post_id, intval ($_REQUEST['learnout_id']));
			} else {
				$item = EAL_ItemFT::createFromArray($post_id, $_REQUEST);
			}
		}
			
		$item->setId($post_id); 
		DB_ItemFT::saveToDB($item, $update);
	}
		
	public function save_post_revision (int $post_id, WP_Post $post, bool $update) {
		$this->save_post($post_id, $post, $update);
	}
	
	
	public function filter_wp_get_revision_ui_diff (array $diff, $compare_from, $compare_to) {
			
		// default items to compare
		$eal_From = new EAL_ItemFT();
		$eal_To = new EAL_ItemFT();
		
		// check type and try to load item revision from database
		if ($compare_from instanceof  WP_Post) {
			if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
			
			try {
				$eal_From = DB_ItemFT::loadFromDB($compare_from->ID);
			} catch (Exception $e) { 
				// could not find revision in the database anymore
			}
 		}
		if ($compare_to instanceof  WP_Post) {
			if (get_post ($compare_to->post_parent)->post_type != $this->type) return $diff;
			
			try {
				$eal_To = DB_ItemFT::loadFromDB($compare_to->ID);
			} catch (Exception $e) {
				// could not find revision in the database anymore
			}
		}
		
		$diff[0] = HTML_Item::compareTitle($eal_From, $eal_To);
		$diff[1] = HTML_Item::compareDescription($eal_From, $eal_To);
		$diff[2] = HTML_Item::compareQuestion($eal_From, $eal_To);
		$diff[3] = HTML_ItemFT::comparePoints($eal_From, $eal_To);
		$diff[4] = HTML_Item::compareLevel($eal_From, $eal_To);
		$diff[5] = HTML_Item::compareNoteFlag($eal_From, $eal_To);
		$diff[6] = HTML_Item::compareLearningOutcome($eal_From, $eal_To);
		
		return $diff;
	}	
	
	
	
	public function WPCB_register_meta_box_cb () {
		
		global $post, $item;
		$item = DB_ItemFT::loadFromDB($post->ID);
		parent::WPCB_register_meta_box_cb();
		
		add_meta_box("mb_points", "Punkte",	array ($item->getHTMLPrinter(), metaboxPoints), $this->type, 'normal', 'default');
		
	}
	
}

	





?>