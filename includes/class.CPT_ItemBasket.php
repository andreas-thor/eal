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
	
?>
		<script type="text/javascript">
			jQuery(document).ready(function() {

				var htmlselect = ["action", "action2"];
					    	
				htmlselect.forEach(function (s, i, o) {
					jQuery("select[name='" + s + "'] > option").remove();
			        jQuery('<option>').val('view').text('<?php _e('View Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('remove_from_basket').text('<?php _e('Remove Items From Basket')?>').appendTo("select[name='" + s + "']");
			      });
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