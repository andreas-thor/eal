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
		


		
	}
	
	
	static function CPT_add_meta_boxes($name)  {
		
		echo ("<script>console.log('addmeta');</script>");

		add_meta_box('mb_description', 	'Fall- oder Problemvignette1',
				array ($name, 'CPT_add_editor'), $name, 'normal', 'default', ['value' => 'DEFAULT Vignette', 'id' => 'item_description']);
		add_meta_box('mb_question', 	'Aufgabenstellung2',
				array ($name, 'CPT_add_editor'), $name, 'normal', 'default', ['value' => 'DEFAULT Aufgabenstellung', 'id' => 'item_question']);
		add_meta_box('mb_item_level', 	'Anforderungsstufe3',
				array ($name, 'CPT_add_level'), $name, 'side', 'default', ['id' => 'item_level']);
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
				$html .= '<td align="center"><input type="radio" id="' . $vars['args']['id'] . '_' . $c . '_' . $r . '" name="' . $vars['args']['id'] . '_' . $c . '" value="' . ($n+1) . '"' . (($r==$v)?' checked':'') . '></td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		
		echo $html;	
	}
	
	
}

?>