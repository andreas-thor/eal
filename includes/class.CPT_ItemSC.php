<?php

require_once("class.CPT_Item.php");
require_once("class.EAL_ItemSC.php");



class CPT_ItemSC extends CPT_Item {
	
	
	public function init() {
		$this->type = "itemsc";
		$this->label = "Single Choice";
		parent::init();
	}
	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = new EAL_ItemSC();
		$item->load();
		
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($this, 'WPCB_mb_description'), $this->type, 'normal', 'default' );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($this, 'WPCB_mb_question'), $this->type, 'normal', 'default');
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default');
		add_meta_box("mb_{$this->type}_answers", "Antwortoptionen",	array ($this, 'WPCB_mb_answers'), $this->type, 'normal', 'default');
	}
	

	
	public function WPCB_mb_answers ($post, $vars) {
	
		global $item;
	
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