<?php

require_once("class.CPT_Item.php");
require_once("class.EAL_ItemMC.php");

class CPT_ItemMC extends CPT_Item {
	
	
	public function init($args = array()) {

		$this->type = "itemmc";
		$this->label = "Multiple Choice";
		$this->menu_pos = 0;
		parent::init();
	}
	
	
	

	
	
	
	

	public function WPCB_wp_get_revision_ui_diff ($diff, $compare_from, $compare_to) {
	
		if (get_post ($compare_from->post_parent)->post_type != "itemmc") return $diff;
		
		$eal_From = new EAL_ItemMC();
		$eal_From->loadById($compare_from->ID);
		$eal_To = new EAL_ItemMC();
		$eal_To->loadById($compare_to->ID);
	
		$diff[0] = $eal_From->compareTitle ($eal_To);
		$diff[1] = $eal_From->compareDescription ($eal_To);
		$diff[2] = $eal_From->compareQuestion ($eal_To);
		$diff[3] = $eal_From->compareLevel ($eal_To);
		$diff[4] = $eal_From->compareAnswers ($eal_To);
	
		return $diff;
	}	
	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = new EAL_ItemMC();
		$item->load();
		parent::WPCB_register_meta_box_cb();
		
	}

	
	
	public function WPCB_mb_answers ($post, $vars) {
	
		global $item;
	
		$answerLine = '<tr>
				<td><input type="text" name="answer[]" value="%s" size="25" /></td>
				<td><input type="text" name="positive[]" value="%d" size="5" /></td>
				<td><input type="text" name="negative[]" value="%d" size="5" /></td>
				<td>
					<button type="button" onclick="addAnswer(this);">&nbsp;+&nbsp;</button>
					&nbsp;&nbsp;
					<button class="removeanswer" type="button" onclick="removeAnswer(this);">&nbsp;-&nbsp;</button>
				</td>
			</tr>';
	
	
		?>
			<script>
	
				var $ =jQuery.noConflict();
				
				function addAnswer (e) { 
					// add new answer option after current line
					$(e).parent().parent().after('<?php echo (preg_replace("/\r\n|\r|\n/",'', sprintf($answerLine, '', 0, 0))); ?>' );
				}
	
				function removeAnswer (e) {
					// delete current answer options but make sure that header + at least one option remain
					if ($(e).parent().parent().parent().children().size() > 2) {
						$(e).parent().parent().remove();
					}
				}
				
			</script>
	
	<?php	
			
			printf ('<table>');
			printf ('<tr align="left"><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>', 'Antwort-Text', 'Ausgew&auml;hlt', 'Nicht ausgew&auml;hlt', 'Aktionen');
			foreach ($item->answers as $a) { 
				printf($answerLine, $a['answer'], $a['positive'], $a['negative']);
			}
			printf ('</table>');
		}	
	
	


	public static function CPT_load_post_short ()  {
	
// 		global $post, $item;
// 		if ($post->post_type != 'itemmc') return;
	
	
// 		print_r($post);
	
// 		$item = new EAL_ItemMC($post);
	}
	
	
// 	public function CPT_add_meta_boxes($eal_posttype=null, $classname=null)  {
		
// 		global $post;
		
// 		$eal_posttype = 'itemmc';
// 		$classname = get_class();
		
// 		global $item;
// 		$item = new EAL_ItemMC();
// 		$item->load();
// 		parent::CPT_add_meta_boxes($eal_posttype, $classname);
		
//  		add_meta_box("mb_{$eal_posttype}_answers", "Antwortoptionen",	array ($classname, 'CPT_add_answers'), $eal_posttype, 'normal', 'default');
// 	}



	
	
	

	static function CPT_set_table_order ($pieces) {
	
// 		echo ("<script>console.log('CPT_set_table_order1 in " . print_r($pieces, true) . "');</script>");
		return ;
		
		if ($query->get( 'post_type') != 'itemmc') return $pieces;
// 		echo ("<script>console.log('CPT_set_table_order2 in " .  print_r($query, true) . "');</script>");
		
		return $pieces;
		
		global $wpdb;
	
		/**
		 * We only want our code to run in the main WP query
		 * AND if an orderby query variable is designated.
		 */
		if ( $query->is_main_query() && ( $orderby = $query->get( 'orderby' ) ) ) {
	
			// Get the order query variable - ASC or DESC
			$order = strtoupper( $query->get( 'order' ) );
	
			// Make sure the order setting qualifies. If not, set default as ASC
			if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) )
				$order = 'ASC';
	
			switch( $orderby ) {
	
				// If we're ordering by release_date
				case 'PW':
	
					/**
					 * We have to join the postmeta table to
					 * include our release date in the query.
					 */
					
					if (!isset ($pieces['join'])) $pieces['join'] = ""; 
					$pieces['join'] .= " JOIN {$wpdb->prefix}eal_itemmc ON {$wpdb->prefix}eal_itemmc.id = {$wpdb->posts}.ID";
	
					// Then tell the query to order by our custom field.
					// The STR_TO_DATE function converts the custom field
					// to a DATE type from a string type for
					// comparison purposes. '%m/%d/%Y' tells the query
					// the string is in a month/day/year format.
					$pieces['orderby'] = "{$wpdb->prefix}eal_itemmc.level_PW $order" . (isset($pieces['orderby']) ? ", {$pieces['orderby']}" : "");
	
					break;
	
			}
	
		}
	
		return $pieces;
	
	}
	
	
	
	
	

	
	
	static function CPT_contextual_help( $contextual_help, $screen_id, $screen ) {
		
		
		$screen->add_help_tab( array(
				'id' => 'you_custom_id', // unique id for the tab
				'title' => 'Custom Help', // unique visible title for the tab
				'content' => '<h3>Help Title</h3><p>Help content</p>', //actual help text
		));
		
		$screen->add_help_tab( array(
				'id' => 'you_custom_id_2', // unique id for the second tab
				'title' => 'Vignette', // unique visible title for the second tab
				'content' => '<h3>Vignette</h3><p>Verwenden Sie Vignetten zur Kontextualisierung und/oder zur Anwendungsorientierung des Items.</p>', //actual help text
		));
		
		
		
		
// 		if ( 'itemmc' == $screen->id ) {
	
// 			$contextual_help = '<h2>Products</h2>
//     <p>Products show the details of the items that we sell on the website. You can see a list of them on this page in reverse chronological order - the latest one we added is first.</p>
//     <p>You can view/edit the details of each product by clicking on its name, or you can perform bulk actions using the dropdown menu and selecting multiple items.</p>
// 		<h1>Hallo</h1><h2>jhjh</h2><p>jkjkj</p>
		
// 		';
	
// 		} elseif ( 'edit-itemmc' == $screen->id ) {
	
// 			$contextual_help = '<h2>Editing products</h2>
//     <p>This page allows you to view/modify product details. Please make sure to fill out the available boxes with the appropriate details (product image, price, brand) and <strong>not</strong> add these details to the product description.</p>';
	
// 		}
		return $contextual_help;
	}	
	
	
}

	
// 		$book = new CustomPostType( 'Book' );
// 		$book->add_taxonomy( 'xas', array ('hierarchical' => true) );
// 		$book->add_taxonomy( 'author' );
//
// 		$book->add_meta_box(
// 				'Book Info',
// 				array(
// 						'Year' => 'text',
// 						'Genre' => 'text'
// 				),
// 				'normal',
// 				'default',
// 				array ('ItemMC', 'loadX')
// 		);
//
// 		$book->add_meta_box(
// 				'Author Info',
// 				array(
// 						'Name' => 'text',
// 						'Nationality' => 'text',
// 						'Birthday' => 'text'
// 				)
// 		);
		
	
	
	
// 	function loadX ($post, $data) {
// 		global $post;
	
// 		// Nonce field for some validation
// 		wp_nonce_field ( plugin_basename ( __FILE__ ), 'custom_post_type' );
	
// 		// Get all inputs from $data
// 		$custom_fields = $data ['args'] [0];
	
// 		// Get the saved values
// 		$meta = get_post_custom ( $post->ID );
	
// 		// Check the array and loop through it
// 		if (! empty ( $custom_fields )) {
// 			/* Loop through $custom_fields */
// 			foreach ( $custom_fields as $label => $type ) {
// 				$field_id_name = strtolower ( str_replace ( ' ', '_', $data ['id'] ) ) . '_' . strtolower ( str_replace ( ' ', '_', $label ) );
	
// 				echo '<label for="' . $field_id_name . '">' . $label . '</label><input type="text" name="custom_meta[' . $field_id_name . ']" id="' . $field_id_name . '" value="AAA' . $meta [$field_id_name] [0] . '" />';
// 			}
// 		}
// 	}
		

	


?>