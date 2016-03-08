<?php


require_once ("class.EAL_Item.Review.php");

class EAL_ItemMC_Review extends EAL_Item_Review {

	function __construct() {
		parent::__construct();
		$this->type = "itemmc_review";
	}
	
	
	public static function save ($post_id, $post) {
	
		global $review;
		$review = new EAL_ItemMC_Review();
		parent::save($post_id, $post);
	}
	
	public function getItem () {
	
		if (is_null($this->item_id)) return null;
	
		if (is_null($this->item)) {
			$this->item = new EAL_ItemMC();
			$this->item->loadById($this->item_id);
		}
		return $this->item;
	
	}
	
	
	
	
	public static function createTables () {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		global $wpdb;
		EAL_Item_Review::createTableReview("{$wpdb->prefix}eal_itemmc_review");
	}
	
	
	
	
}

?>