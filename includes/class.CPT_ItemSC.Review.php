<?php

// TODO: Delete Review (in POst Tabelle) --> löschen in Review-Tabelle

require_once("class.CPT_Item.Review.php");
require_once("class.EAL_ItemSC.Review.php");


class CPT_ItemSC_Review extends CPT_Item_Review {
	
	public function init() {
	
		$this->type = "itemsc_review";
		$this->label = "SC Question Review";
		parent::init();
	}
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $review;
		$review = new EAL_ItemSC_Review();
		$review->load();
	
		add_meta_box('mb_item', $review->getItem()->title, array ($this, 'WPCB_mb_item'), $this->type, 'normal', 'default' );
		add_meta_box('mb_score', 'Fall- oder Problemvignette, Aufgabenstellung und Antwortoptionen', array ($this, 'WPCB_mb_score'), $this->type, 'normal', 'default' );
		add_meta_box('mb_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'normal', 'default');
		add_meta_box('mb_feedback', 'Feedback', array ($this, 'WPCB_mb_feedback'), $this->type, 'normal', 'default');
		add_meta_box('mb_overall', 'Revisionsurteil', array ($this, 'WPCB_mb_overall'), $this->type, 'side', 'default');
	
	}
		
	public function WPCB_save_post($post_id, $post) {
		(new EAL_ItemSC_Review())->save($post_id, $post);
	}
}

?>