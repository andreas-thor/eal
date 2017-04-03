<?php

require_once("CPT_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemSC.php");



class CPT_ItemSC extends CPT_Item {
	
	
	
	public function __construct() {
	
		parent::__construct();
	
		$this->type = "itemsc";
		$this->label = "Single Choice";
		$this->menu_pos = 0;
		$this->dashicon = "dashicons-marker";
		
		unset($this->table_columns["item_type"]);
	}
	
	
	public function init($args = array()) {
		parent::init($args);
		add_filter ('wp_get_revision_ui_diff', array ($this, 'WPCB_wp_get_revision_ui_diff'), 10, 3 );
	}
	
	
	public function WPCB_wp_get_revision_ui_diff ($diff, $compare_from, $compare_to) {
	
		if (get_post ($compare_from->post_parent)->post_type != "itemsc") return $diff;
		
		$eal_From = new EAL_ItemSC(($compare_from->ID == null) ? -1 : $compare_from->ID);
		$eal_To = new EAL_ItemSC($compare_to->ID);
	
		$diff[0] = $eal_From->compareTitle ($eal_To);
		$diff[1] = $eal_From->compareDescription ($eal_To);
		$diff[2] = $eal_From->compareQuestion ($eal_To);
		$diff[3] = $eal_From->compareLevel ($eal_To);
		$diff[4] = $eal_From->compareAnswers ($eal_To);
	
		return $diff;
	}	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = new EAL_ItemSC();
		parent::WPCB_register_meta_box_cb();
	}
	

	
	public function WPCB_mb_answers ($post, $vars) {
	
		global $item;
		print (HTML_ItemSC::getHTML_Answers($item, HTML_Object::VIEW_EDITOR));
	}
		
	
	
	

	
	

}

	





?>