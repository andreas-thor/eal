<?php

require_once("class.CPT_Item.php");
// require_once("class.CustomPostType.php");
require_once("class.EAL_ItemSC.php");



class CPT_ItemSC extends CPT_Item {
	
	
	public function CPT_createDBTable() {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
	
		dbDelta (
				"CREATE TABLE {$wpdb->prefix}eal_itemsc (
				id bigint(20) unsigned NOT NULL,
				title text,
				description text,
				question text,
				answer text,
				level tinyint unsigned,
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				PRIMARY KEY  (id)
		) $charset_collate;"
		);
	
		dbDelta (
				"CREATE TABLE {$wpdb->prefix}eal_itemsc_answer (
				item_id bigint(20) unsigned NOT NULL,
				id smallint unsigned NOT NULL,
				answer text,
				points smallint,
				KEY  (item_id),
				PRIMARY KEY  (item_id, id)
		) $charset_collate;"
		);
	
	}
	
	
	
	public static function CPT_init($eal_posttype=null, $label=null, $classname=null) {
		parent::CPT_init("itemsc", 'SC Question', get_class());
	}
	
	public static function CPT_save_post ($post_id, $post) {
		
		$item = parent::CPT_save_post($post_id, $post);
		global $wpdb;
		
		$wpdb->replace(
				$wpdb->prefix . 'eal_itemsc',
				$item[0],
				$item[1]
		);
		
		/** TODO: Sanitize all values */
		/** TODO: DELETE all answers; INSERT new answers */
		
		
		if (isset($_POST['answer'])) {
				
			$values = array();
			$insert = array();
			$kmax = 0;
				
			foreach ($_POST['answer'] as $k => $v) {
				array_push($values, $post_id, $k+1, $v, $_POST['points'][$k]);
				array_push($insert, "(%d, %d, %s, %d)");
				$kmax = $k+1;
		
			}
				
			$query = "REPLACE INTO {$wpdb->prefix}eal_itemsc_answer (item_id, id, answer, points) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_itemmc_answer WHERE item_id=%d AND id>%d", array ($post_id, $kmax)));
		
		}
		
	}
	
	public static function CPT_delete_post ($post_id)  {
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemsc', array( 'id' => $post_id ), array( '%d' ) );
		$wpdb->delete( '{$wpdb->prefix}eal_itemsc_answer', array( 'item_id' => $post_id ), array( '%d' ) );
		
	}
	
	public static function CPT_load_post ()  {
		
		global $post, $item;
		$item = new EAL_ItemSC($post);
	}
	
	
	
	static function CPT_add_meta_boxes($eal_posttype=null, $classname=null)  {
	
		self::CPT_load_post();
		$eal_posttype = 'itemsc';
		$classname = get_class();
		parent::CPT_add_meta_boxes($eal_posttype, $classname);
			
		add_meta_box("mb_{$eal_posttype}_answers", "Antwortoptionen",	array ($classname, 'CPT_add_answers'), $eal_posttype, 'normal', 'default');
	}
	
	
	
	static function CPT_add_answers ($post, $vars) {
	
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
		
	
	
 	static function CPT_updated_messages( $messages ) {
		
 	}

	static function CPT_contextual_help( $contextual_help, $screen_id, $screen ) {
		
	}
}

	


?>