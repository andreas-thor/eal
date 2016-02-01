<?php

require_once("class.ItemMC.php");

abstract class Item {
	

	
	
	function __construct($post_id = NULL) {
		
		echo ("<script>console.log('CONSTRUCT ITEM');</script>");
		
		if (!empty($post_id)) {
			$this->getPost ($post_id);
		}
	}
	
	function getPost ($post_id) {
		echo ("<script>console.log('GETPOST ITEM');</script>");
		
	}
	
	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	static function CPT_init($name, $label) {
		
		global $qwe;
		echo ("<script>console.log('Init');</script>");
		echo ("<script>console.log('" . $qwe . "');</script>");
		$qwe = 7;
		echo ("<script>console.log('" . $qwe . "');</script>");
		
		register_post_type( $name,
				array(
						'labels' => array(
								'name' => $label,
								'singular_name' => $label,
								'add_new' => 'Add ' . $label,
								'add_new_item' => 'Add New ' . $label,
								'edit' => 'Edit',
								'edit_item' => 'Edit ' . $label,
								'new_item' => 'New ' . $label,
								'view' => 'View',
								'view_item' => 'View ' . $label,
								'search_items' => 'Search ' . $label,
								'not_found' => 'No Items found',
								'not_found_in_trash' => 'No Items found in Trash',
								'parent' => 'Parent Item'
						),
		
						'public' => true,
						'menu_position' => 2,
						'supports' => array( 'title'), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
						'taxonomies' => array( 'topic' ),
						// 'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
						'has_archive' => true,
						'show_in_menu'    => true,
						'register_meta_box_cb' => array ($name, 'CPT_add_meta_boxes')
				)
		);
		
		add_action ("save_post", array ($name, 'CPT_save_post'), 10, 2);
		add_action ('delete_post', array ($name, 'CPT_delete_post'), 10);
// 		add_action ('the_post', array ($name, 'CPT_load_post'), 10);
// 		add_action ("load-$name", array ($name, 'CPT_load_post'), 10);
// 		add_action ("edit_form_advanced", array ($name, 'CPT_load_post'), 10);
		
		add_filter( 'post_updated_messages', array ($name, 'CPT_updated_messages') );
		add_action( 'contextual_help', array ($name, 'CPT_contextual_help' ), 10, 3);

		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter( 'pre_get_shortlink', '__return_empty_string' );
		
		
		add_filter('manage_itemmc_posts_columns' , array ('item', 'add_book_columns'));
		add_action( 'manage_posts_custom_column' , array ('item', 'custom_columns'), 10, 2 );
		
		
	}
	
	static function add_book_columns($columns) {
		
		global $wp_query;
// 		echo ("<script>console.log('W:" . $wp_query->posts . "');</script>");
		
		unset($columns['author']);
		return array_merge($columns,
				array('FW' => __('FW'),
						'book_author' =>__( 'Book Author')));
	}
	
	
	static function custom_columns( $column, $post_id ) {
		global $post;
		switch ( $column ) {
			case 'FW': echo ($post->ID);
// 				$terms = get_the_term_list( $post_id, 'book_author', '', ',', '' );
// 				if ( is_string( $terms ) ) {
// 					echo $terms;
// 				} else {
// 					_e( 'Unable to get author(s)', 'your_text_domain' );
// 				}
				break;
	
			case 'publisher':
				echo get_post_meta( $post_id, 'publisher', true );
				break;
		}
	}
	
	
	static function CPT_add_meta_boxes($name, $item)  {
		
		echo ("<script>console.log('addmeta');</script>");

		add_meta_box('mb_description', 	'Fall- oder Problemvignette1',
				array ($name, 'CPT_add_editor'), $name, 'normal', 'default', ['value' => wpautop(stripslashes($item['description'])), 'id' => 'item_description']);
		add_meta_box('mb_question', 	'Aufgabenstellung2',
				array ($name, 'CPT_add_editor'), $name, 'normal', 'default', ['value' => wpautop(stripslashes($item['question'])), 'id' => 'item_question']);
		add_meta_box('mb_item_level', 	'Anforderungsstufe3',
				array ($name, 'CPT_add_level'), $name, 'side', 'default', ['FW' => $item['level_FW'], 'PW' => $item['level_PW'], 'KW' => $item['level_KW'], 'id' => 'item_level']);
	}
	
	
	public static function CPT_save_post ($post_id, $post)  {
	
		return array (
			array(
				'id' => $post_id,
				'title' => $post->post_title, // isset($_POST['post_title']) ? $_POST['post_title'] : null,
				'description' => isset($_POST['item_description']) ? $_POST['item_description'] : null,
				'question' => isset ($_POST['item_question']) ? $_POST['item_question'] : null,
				'level_FW' => isset ($_POST['item_level_FW']) ? $_POST['item_level_FW'] : null,
				'level_KW' => isset ($_POST['item_level_KW']) ? $_POST['item_level_KW'] : null,
				'level_PW' => isset ($_POST['item_level_PW']) ? $_POST['item_level_PW'] : null,
			),
			array(
				'%d','%s','%s','%s','%d','%d','%d'
			)
		);
	}
	
	public static function CPT_delete_post ($post_id)  {
	
	}
	
	static function CPT_add_editor ($post, $vars) {
	
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
	
		$html = wp_editor(   $vars['args']['value'], $vars['args']['id'], $editor_settings );
		echo $html;
		// 	echo '<input type="text" name="_location" value="7"  />';
	}
	
	
	static function CPT_add_level ($post, $vars) {
	
		
		$colNames = ["FW"=>"", "KW"=>"", "PW"=>""];
		$html  = '<table><tr><td></td>';
		foreach ($colNames as $c=>$v) {
			$html .= '<td>' . $c . '</td>';
			$colNames[$c] = get_post_meta($post->ID, $c, true);
		}
		
		$html .= '</tr>';
			
		$rowNames = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
		foreach ($rowNames as $n => $r) {
			$html .= '<tr><td>' . ($n+1) . ". " . $r . '</td>';
			foreach ($colNames as $c=>$v) {
				$html .= '<td align="center"><input type="radio" id="' . $vars['args']['id'] . '_' . $c . '_' . $r . '" name="' . $vars['args']['id'] . '_' . $c . '" value="' . ($n+1) . '"' . (($vars['args'][$c]==$n+1)?' checked':'') . '></td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		
		
		echo $html;	
	}
	
	

	
	
}

?>