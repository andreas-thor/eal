<?php

require_once("class.CPT_ItemMC.php");

abstract class CPT_Item {
	

	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	static function CPT_init($eal_posttype, $label, $classname) {
		
		register_post_type( $eal_posttype,
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
						'register_meta_box_cb' => array ($classname, 'CPT_add_meta_boxes')
				)
		);
		
		add_action ("save_post_{$eal_posttype}", array ($classname, 'CPT_save_post'), 10, 2);
		add_action ('delete_post', array ($classname, 'CPT_delete_post'), 10);
		add_action ('the_post', array ($classname, 'CPT_load_post'), 10);

		echo ("<script>console.log('init: {$classname}');</script>");
		
		
		add_filter("manage_{$eal_posttype}_posts_columns" , array ($classname, 'CPT_set_table_columns'));
		add_action("manage_{$eal_posttype}_posts_custom_column" , array ($classname, 'CPT_fill_table_columns'), 10, 2 );
		
		
// 		add_action ("load-$name", array ($name, 'CPT_load_post'), 10);
// 		add_action ("edit_form_advanced", array ($name, 'CPT_load_post'), 10);
		
		add_filter('post_updated_messages', array ($classname, 'CPT_updated_messages') );
		add_action('contextual_help', array ($classname, 'CPT_contextual_help' ), 10, 3);

		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
		
		
		
		
	}
	

	
	static function CPT_add_meta_boxes($eal_posttype=null, $classname=null)  {
		
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($classname, 'CPT_add_description'), $eal_posttype, 'normal', 'default' );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($classname, 'CPT_add_question'), $eal_posttype, 'normal', 'default');
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($classname, 'CPT_add_level'), $eal_posttype, 'side', 'default');
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
	
	
	
	static function CPT_add_description ($post, $vars) {
	
		global $item;
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
	
		$html = wp_editor(wpautop(stripslashes($item->description)) , 'item_description', $editor_settings );
		echo $html;
	}
	
	
	static function CPT_add_question ($post, $vars) {
	
		global $item;
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
	
		$html = wp_editor(wpautop(stripslashes($item->question)) , 'item_question', $editor_settings );
		echo $html;
	}	
	
	
	static function CPT_add_level ($post, $vars) {
	
		global $item;
		
		$colNames = ["FW"=>$item->level_FW, "KW"=>$item->level_KW, "PW"=>$item->level_PW];
		$html  = '<table><tr><td></td>';
		foreach ($colNames as $c => $v) {
			$html .= '<td>' . $c . '</td>';
		}
		
		$html .= '</tr>';
			
		$rowNames = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
		foreach ($rowNames as $n => $r) {
			$html .= '<tr><td>' . ($n+1) . ". " . $r . '</td>';
			foreach ($colNames as $c=>$v) {
				$html .= '<td align="center"><input type="radio" id="item_level_' . $c . '_' . $r . '" name="item_level_' . $c . '" value="' . ($n+1) . '"' . (($v==$n+1)?' checked':'') . '></td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		
		
		echo $html;	
	}
	
	
	static function CPT_set_table_columns($columns) {
		echo ("<script>console.log('CPT_set_table_columns in " . get_class() . "');</script>");
		return array_merge($columns, array('FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW'));
	
	}
	
	
	static function CPT_fill_table_columns( $column, $post_id ) {
		echo ("<script>console.log('CPT_fill_table_columns for columm $column and postId {$post_id} ');</script>");
	
		global $post, $item;
	
		switch ( $column ) {
			case 'FW': echo ($item->level_FW); break;
			case 'PW': echo ($item->level_PW); break;
			case 'KW': echo ($item->level_KW); break;
		}
	}

	
}

?>