<?php


require_once ("class.EAL_Item.Review.php");


class EAL_ItemSC_Review extends EAL_Item_Review {

	function __construct() {
		parent::__construct();
		$this->type = "itemsc_review";
	}
	
	public function getItem () {
	
		if (is_null($this->item_id)) return null;
	
		if (is_null($this->item)) {
			$this->item = new EAL_ItemSC();
			$this->item->loadById($this->item_id);
		}
		return $this->item;
	
	}
	
	
	public static function createTables () {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		global $wpdb;
		EAL_Item_Review::createTableReview("{$wpdb->prefix}eal_itemsc_review");
	}
	
	
	
	

}

?>