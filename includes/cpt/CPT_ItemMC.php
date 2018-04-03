<?php

require_once("CPT_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemMC.php");

class CPT_ItemMC extends CPT_Item {
	
	
	
	public function __construct() {
	
		parent::__construct();
	
		$this->type = "itemmc";
		$this->label = "Multiple Choice";
		$this->menu_pos = 0;
		$this->dashicon = "dashicons-forms";
		
		unset($this->table_columns["item_type"]);
	}
	
	
	public function addHooks() {
		
		parent::addHooks();
		add_action ("save_post_{$this->type}", array ('EAL_ItemMC', save), 10, 2);
		add_action ("save_post_revision", array ('EAL_ItemMC', 'save'), 10, 2);
	}
	
	
	
	
	

	public function WPCB_wp_get_revision_ui_diff ($diff, $compare_from, $compare_to) {
	
		if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
		
		$eal_From = EAL_Factory::createNewItemMC($compare_from->ID);
		$eal_To = EAL_Factory::createNewItemMC($compare_to->ID);
	
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