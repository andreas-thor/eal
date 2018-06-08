<?php

require_once 'CPT_Item.php';
require_once __DIR__ . '/../eal/EAL_ItemMC.php';
require_once __DIR__ . '/../db/DB_ItemMC.php';

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
		add_action ("save_post_{$this->type}", 'CPT_ItemMC::save_post', 10, 2);
		add_action ("save_post_revision", 'CPT_ItemMC::save_post', 10, 2);
	}
	
	
	public static function save_post (int $post_id, WP_Post $post) {
		
		$revision = wp_is_post_revision ($post_id);
		$type = ($revision === FALSE) ? $post->post_type : get_post_type($revision);
		if ($type != EAL_ItemMC::getType()) return;
		
		global $itemToImport;	// indicates save_post execution during bulk import/update
		
		if (isset($itemToImport)) {
			$item = $itemToImport;
		} else {
			if ($post->post_status === 'auto-draft') {
				$item = new EAL_ItemMC($post_id, intval ($_REQUEST['learnout_id']));
			} else {
				$item = EAL_ItemMC::createFromArray($post_id, $_REQUEST);
			}
		}
		
		$item->setId($post_id);
		DB_ItemMC::saveToDB($item);
	}
	
	

	public function filter_wp_get_revision_ui_diff (array $diff, $compare_from, $compare_to) {
		
		// default items to compare
		$eal_From =  new EAL_ItemMC();
		$eal_To = new EAL_ItemMC();
		
		// check type and try to load item revision from database
		if ($compare_from instanceof  WP_Post) {
			
			$q1 = get_post ($compare_from->post_parent)->post_type;
			if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
			
			try {
				$eal_From = DB_ItemMC::loadFromDB($compare_from->ID);
			} catch (Exception $e) {
				// could not find revision in the database anymore
			}
		}
		if ($compare_to instanceof  WP_Post) {
			$q2 = get_post ($compare_to->post_parent)->post_type;
			if (get_post ($compare_to->post_parent)->post_type != $this->type) return $diff;
			
			try {
				$eal_To = DB_ItemMC::loadFromDB($compare_to->ID);
			} catch (Exception $e) {
				// could not find revision in the database anymore
			}
		}
		
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
		
		global $post, $item;
		$item = DB_ItemMC::loadFromDB($post->ID);
		parent::WPCB_register_meta_box_cb();
		add_meta_box("mb_answers", "Antwortoptionen",	array ($item->getHTMLPrinter(), metaboxAnswers), $this->type, 'normal', 'default');
		
		
	}

	

	
	
}

	


?>