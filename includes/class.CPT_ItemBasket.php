<?php

require_once("class.CPT_Item.php");



class CPT_ItemBasket extends CPT_Item {
	
	
	
	public function init($args = array()) {
		$this->type = "itembasket";
		$this->label = "Item Basket";
		$this->menu_pos = 0;
		parent::init();
		
	}
	

}



?>