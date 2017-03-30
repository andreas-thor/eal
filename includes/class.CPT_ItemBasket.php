<?php

require_once("class.CPT_Item.php");



class CPT_ItemBasket extends CPT_Item {
	
	
	public function __construct() {
	
		parent::__construct();
	
		$this->type = "itembasket";
		$this->label = "Item Basket";
		$this->menu_pos = 0;
		$this->dashicon = "dashicons-cart";
	}
	
	
	public function init($args = array()) {
		parent::init($args);
	}
	

}



?>