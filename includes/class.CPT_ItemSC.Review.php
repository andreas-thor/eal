<?php

// TODO: Delete Review (in POst Tabelle) --> löschen in Review-Tabelle

require_once("class.CPT_Item.Review.php");
require_once("class.EAL_ItemSC.Review.php");


class CPT_ItemSC_Review extends CPT_Item_Review {
	
	public function init($args = array()) {
	
		$this->type = "itemsc_review";
		$this->label = "SC Question Review";
		parent::init();
	}
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $review;
		$review = new EAL_ItemSC_Review();
		$review->load();
		parent::WPCB_register_meta_box_cb();
	}
		
}

?>