<?php

require_once("class.Item.php");
require_once("class.CustomPostType.php");


class ItemMC extends Item {
	
	
	function __construct  ($post_id = NULL) {
		
		
	}
	
	function getPost ($post_id) {
		
		return false;
	}
	
	
	public function CPT_init() {
		parent::CPT_init(get_class(), "MC Question");
	}
	
	
	function CPT_add_meta_boxes()  {
		parent::CPT_add_meta_boxes(get_class());
 		add_meta_box('mb_' . get_class() . '_answers', 	'Antwortoptionen',
 				array (get_class(), 'CPT_add_answers'), get_class(), 'normal', 'default', ['id' => 'mb_' . get_class() . '_answers_editor']);
	}


	function CPT_add_answers ($post, $vars) {
	
		$answerLine = '<tr>
				<td><input type="text" value="%s" size="25" /></td>
				<td><input type="text" value="%d" size="5" /></td>
				<td><input type="text" value="%d" size="5" /></td>
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
	
	
	
	

	
	
	
	function CPT_add_editor ($post, $vars) {
		parent::CPT_add_editor($post, $vars);
	}
	
	function CPT_add_level ($post, $vars) {
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