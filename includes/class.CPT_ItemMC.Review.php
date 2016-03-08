<?php

// TODO: Delete Review (in POst Tabelle) --> löschen in Review-Tabelle

require_once("class.CPT_Item.Review.php");
require_once("class.EAL_ItemMC.Review.php");

class CPT_ItemMC_Review extends CPT_Item_Review {
	
	public function init($args = array()) {
	
		$this->type = "itemmc_review";
		$this->label = "MC Question Review";
		parent::init();
	}
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $review;
		$review = new EAL_ItemMC_Review();
		$review->load();
		parent::WPCB_register_meta_box_cb();
	}
		
	
}

?>