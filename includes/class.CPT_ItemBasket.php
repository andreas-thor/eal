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
	
	function custom_bulk_action() {
	
		
		if ($_REQUEST["post_type"] != $this->type) return; 
		
		global $wpdb;
		
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
		
		if ($wp_list_table->current_action() == 'remove_from_basket') {
			
			$b_old = get_user_meta(get_current_user_id(), 'itembasket', true);
			$b_new = $b_old;

			if (isset($_REQUEST["post"])) {
				$b_new = array_diff ($b_old, $_REQUEST['post']);
			}
			if ($_REQUEST['itemid']!=null) {
				$b_new = array_diff ($b_old, [$_REQUEST['itemid']]);
			}
			if ($_REQUEST['itemids']!=null) {
				$b_new = array_diff ($b_old, $_REQUEST['itemids']);
			}
			$x = update_user_meta( get_current_user_id(), 'itembasket', $b_new, $b_old );
		
		}
	
	
	
	
	}


}



?>