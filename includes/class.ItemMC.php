<?php

require_once("class.Item.php");
require_once("class.CustomPostType.php");


class ItemMC extends Item {
	
	
	function __construct  ($post_id = NULL) {
		
		add_action( 'edit_post', 'CPT_save_post');
		wp_die ('AAA');
		
		echo ("<script>console.log('CONSTRUCT');</script>");
		if ( !empty($post_id)) $this->getPost ($post_id);
		
	}
	
	function getPost ($post_id) {
		
		echo ("<script>console.log('GETPOST');</script>");
		
		$p = get_post ($post_id);
		$p->post_title = 'Gesetzt';
		echo ("HIER:");
		echo ($p->post_title);
		return $p->ID;
	}
	
	public function CPT_createDBTable() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		
		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_itemmc (
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
				"CREATE TABLE {$wpdb->prefix}eal_itemmc_answer (
				item_id bigint(20) unsigned NOT NULL,
				id smallint unsigned NOT NULL,
				answer text,
				positive smallint,
				negative smallint,
				KEY  (item_id),
				PRIMARY KEY  (item_id, id)
			) $charset_collate;"
		);
		
	}
	
	
	public static function CPT_init($name=null, $label=null) {
		$name = get_class();
		parent::CPT_init($name, 'MC Question');
		
// 		add_action("save_post_$name",  array ($name, 'CPT_save_post'), 10, 2);
		
		
		
				add_action ('save_post', array ($name, 'CPT_save_post'), 10, 2);
// 				add_action ("save_post_$name", array ($name, 'CPT_save_post'), 10, 2 );
//  				add_action ("publish_post_$name", array ($name, 'CPT_save_post'), 10, 2 );
//  				add_action ("pre_post_update", array ($name, 'CPT_save_post'), 10, 2 );
//  				add_action ("edit_page_form", array ($name, 'CPT_save_post'), 10, 2 );		
		
		
	}
	
	
	
	public static function CPT_save_post ($post_id, $post) { // $post_id, $post) {

// 		global $post;
// 		$post = get_post ($post_id);
// 		update_post_meta($post->ID, 'my_metadata1', $post_id);
// 		update_post_meta($post->ID, 'my_metadata2', $_POST['item_description']);
// 		update_post_meta($post->ID, 'my_metadata3', $_POST['item_question']);
		
		$item = parent::CPT_save_post($post_id, $post);
		global $wpdb;
		
		$itemmc = array (
			array(
				'answer' => implode(",", $_POST['answer'])
			),
			array(
				'%s'
			)
		);
		
		$wpdb->replace(
				$wpdb->prefix . 'eal_itemmc',
				array_merge($item[0], $itemmc[0]),
				array_merge($item[1], $itemmc[1])
			);
		
		
		/** TODO: DELETE all answers; INSERT new answers */
		
		if (isset($_POST['answer'])) {
			
			foreach ($_POST['answer'] as $k => $v) {
				$wpdb->replace(
					$wpdb->prefix . 'eal_itemmc_answer',
					array (
						'item_id' => $post_id,
						'id' => $k+1,	
						'answer' => $v,
						'positive' => $_POST['positive'][$k], 
						'negative' => $_POST['negative'][$k]
					), 
					array(
						'%d','%d','%s','%d','%d'							
					)
				);
			}
			
		}
		
		
		
// 		wp_mail( "thor@hft-leipzig.de", "save Post", $post_id);
// 		wp_die ('DIE');#
// 		echo ("<script>alert('SAVE');</script>");
		
// 		if($_POST['post_type'] != get_class()) {
// 			echo ("AAAAAAA");
// 			return;
			
// 		}
		
// 		echo ("BBBBBBB");
// 		update_post_meta($post_ID, 'my_metadata', $_POST['title']);
		
		
	}
	
	
	static function CPT_add_meta_boxes($name=null)  {
		
		$name = get_class();
		parent::CPT_add_meta_boxes($name);
 		add_meta_box('mb_' . $name . '_answers', 	'Antwortoptionen',
 				array (get_class(), 'CPT_add_answers'), $name, 'normal', 'default', ['id' => 'mb_' . $name . '_answers_editor']);
	}


	static function CPT_add_answers ($post, $vars) {
	
		
		echo ("<script>console.log('ANSWERS');</script>");
		
		
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
		foreach (array(1, 2, 3, 4) as $value) { 
			printf($answerLine, $value, 1, -1);
		}
		printf ('</table>');
	}
	
	
	
	

	
	
	
	static function CPT_add_editor ($post, $vars) {
		parent::CPT_add_editor($post, $vars);
	}
	
	static function CPT_add_level ($post, $vars) {
		parent::CPT_add_level($post, $vars);
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