<?php

require_once("class.CPT_Item.php");



class CPT_ItemBasket extends CPT_Item {
	
	
	
	public function init($args = array()) {
		$this->type = "itembasket";
		$this->label = "Item Basket";
		$this->menu_pos = 0;
		parent::init();
		
	}
	
	function add_bulk_actions() {
	
		global $post_type;
		if ($post_type != $this->type) return;
	
		parent::add_bulk_actions();
		
?>
		<script type="text/javascript">
			jQuery(document).ready(function() {

				jQuery("select[name='action'] > option[value='add_to_basket']").remove();
				jQuery("select[name='action2'] > option[value='add_to_basket']").remove();
	      
				jQuery('<option>').val('remove_from_basket').text('<?php _e('Remove From Basket')?>').appendTo("select[name='action']");
				jQuery('<option>').val('remove_from_basket').text('<?php _e('Remove From Basket')?>').appendTo("select[name='action2']");
				
				jQuery('<option>').val('export_to_ilias').text('<?php _e('Export To Ilias')?>').appendTo("select[name='action']");
				jQuery('<option>').val('export_to_ilias').text('<?php _e('Export To Ilias')?>').appendTo("select[name='action2']");
      });
		</script>
		
<?php
	}


}



?>