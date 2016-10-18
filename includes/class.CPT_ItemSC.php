<?php

require_once("class.CPT_Item.php");
require_once("class.EAL_ItemSC.php");



class CPT_ItemSC extends CPT_Item {
	
	
	
	public function init($args = array()) {
		$this->type = "itemsc";
		$this->label = "Single Choice";
		$this->menu_pos = 0;
		parent::init();
		unset($this->table_columns["item_type"]);
	}
	
	
	public function WPCB_wp_get_revision_ui_diff ($diff, $compare_from, $compare_to) {
	
		if (get_post ($compare_from->post_parent)->post_type != "itemsc") return $diff;
		
		$eal_From = new EAL_ItemSC();
		$eal_From->loadById($compare_from->ID);
		$eal_To = new EAL_ItemSC();
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
		$item = new EAL_ItemSC();
		$item->load();
		parent::WPCB_register_meta_box_cb();
	}
	

	
	public function WPCB_mb_answers ($post, $vars) {
	
		global $item;
	
		$b = get_taxonomy( 'topic' );
		
		
		$answerLine = '<tr>
				<td><input type="text" name="answer[]" value="%s" size="25" /></td>
				<td><input type="text" name="points[]" value="%d" size="5" /></td>
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
			printf ('<tr align="left"><th>%s</th><th>%s</th><th>%s</th></tr>', 'Antwort-Text', 'Punkte', 'Aktionen');
			foreach ($item->answers as $a) { 
				printf($answerLine, $a['answer'], $a['points']);
			}
			printf ('</table>');
	}
		
	
	
	
	
	
	static function CPT_set_table_order ($pieces, $query ) {
	}
		
			
	
	

	static function CPT_contextual_help( $contextual_help, $screen_id, $screen ) {
		
	}
}

	


// 	public static function CPT_save_post ($post_id, $post) {

// 		$item = parent::CPT_save_post($post_id, $post);
// 		global $wpdb;

// 		$wpdb->replace(
// 				$wpdb->prefix . 'eal_itemsc',
// 				$item[0],
// 				$item[1]
// 		);

// 		/** TODO: Sanitize all values */
// 		/** TODO: DELETE all answers; INSERT new answers */


// 		if (isset($_POST['answer'])) {

// 			$values = array();
// 			$insert = array();
// 			$kmax = 0;

// 			foreach ($_POST['answer'] as $k => $v) {
// 				array_push($values, $post_id, $k+1, $v, $_POST['points'][$k]);
// 				array_push($insert, "(%d, %d, %s, %d)");
// 				$kmax = $k+1;

// 			}

// 			$query = "REPLACE INTO {$wpdb->prefix}eal_itemsc_answer (item_id, id, answer, points) VALUES ";
// 			$query .= implode(', ', $insert);
// 			$wpdb->query( $wpdb->prepare("$query ", $values));
// 			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_itemmc_answer WHERE item_id=%d AND id>%d", array ($post_id, $kmax)));

// 		}

// 	}

// 	public static function CPT_delete_post ($post_id)  {
// 		global $wpdb;
// 		$wpdb->delete( '{$wpdb->prefix}eal_itemsc', array( 'id' => $post_id ), array( '%d' ) );
// 		$wpdb->delete( '{$wpdb->prefix}eal_itemsc_answer', array( 'item_id' => $post_id ), array( '%d' ) );

// 	}

// 	public static function CPT_load_post ()  {

// 		global $post, $item;
// 		if ($post->post_type != 'itemsc') return;
// 		$item = new EAL_ItemSC($post);
// 	}





?>