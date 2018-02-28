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
	

	public function addHooks() {

		parent::addHooks();
		
		add_action ("save_post_{$this->type}", array ('EAL_ItemSC', save), 10, 2);
		add_action ("save_post_revision", array ('EAL_ItemSC', 'save'), 10, 2);
	}
	
	public function WPCB_wp_get_revision_ui_diff ($diff, $compare_from, $compare_to) {
	
		
		if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
		
		$eal_From = new EAL_ItemSC($compare_from->ID);
		$eal_To = new EAL_ItemSC($compare_to->ID);
	
		$diff[0] = HTML_Item::compareTitle($eal_From, $eal_To);
		$diff[1] = HTML_Item::compareDescription($eal_From, $eal_To);
		$diff[2] = HTML_Item::compareQuestion($eal_From, $eal_To);
		$diff[3] = HTML_Item::compareLevel($eal_From, $eal_To);
		$diff[4] = HTML_ItemSC::compareAnswers($eal_From, $eal_To);
		
		return $diff;
	}	
	
	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = new EAL_ItemSC();
		parent::WPCB_register_meta_box_cb();
	}
	

	
	public function WPCB_mb_answers ($post, $vars) {
	
		global $item;
		print (HTML_ItemSC::getHTML_Answers($item, HTML_Object::VIEW_EDIT));

	}
		
	
	
	public function WPCB_mb_question ($post, $vars, $buttons = array()) {
	
		parent::WPCB_mb_question ($post, $vars, array ( 	
				"W�hle 1 aus 4" => "W�hlen Sie eine aus den vier Antwortoptionen aus.", 
				"W�hle 1 aus 5" => "W�hlen Sie eine aus den f�nf Antwortoptionen aus.", 
				"W�hle 1 aus 6" => "W�hlen Sie eine aus den sechs Antwortoptionen aus.", 
				"W�hle korrekte" => "W�hlen Sie die korrekte aus den folgenden Antwortoptionen aus." 
		));

	
	}	

}

	





?>