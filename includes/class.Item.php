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
	
	function CPT_init($name, $label) {
		
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
		
		// add_action('save_post',  'CPT_save_post', 10, 2);
		
	}
	
	
	function CPT_add_meta_boxes($name)  {
		
		echo ("<script>console.log('addmeta');</script>");
		
		add_meta_box('mb_' . $name . '_desc', 	'Fall- oder Problemvignette',
				array ($name, 'CPT_add_editor'), $name, 'normal', 'default', ['value' => 'DEFAULT Vignette', 'id' => 'mb_' . $name . '_desc_editor']);
		add_meta_box('mb_' . $name . '_ques', 	'Aufgabenstellung',
				array ($name, 'CPT_add_editor'), $type, 'normal', 'default', ['value' => 'DEFAULT Aufgabenstellung', 'id' => 'mb_' . $name . '_ques_editor']);
		add_meta_box('mb_' . $name . '_level', 	'Anforderungsstufe',
				array ($name, 'CPT_add_level'), $type, 'side', 'default', ['id' => 'mb_' . $name . '_level']);
	}
	
	
	public function CPT_save_post ($post_id) {
	
		echo ("<script>console.log('SAVE Item');</script>");
	}
	
	function CPT_add_editor ($post, $vars) {
	
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
	
	
	function CPT_add_level ($post, $vars) {
	
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
				$html .= '<td align="center"><input type="radio" id="' . $vars['args']['id'] . '_' . $c . '_' . $r . '" name="' . $c . '" value="' . $r . '"' . (($r==$v)?' checked':'') . '></td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		
		echo $html;	
	}
	
	
}

?>