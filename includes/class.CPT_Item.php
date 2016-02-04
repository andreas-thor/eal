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
						'has_archive' => false, // false to allow for single view
						'show_in_menu'    => true,
						'register_meta_box_cb' => array ($classname, 'CPT_add_meta_boxes')
				)
		);
		
		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$eal_posttype}", array ("eal_{$eal_posttype}", 'save'), 10, 2);
		
		// TODO: Delete post hook 
		add_action ('XXX', array ("eal_{$eal_posttype}", 'save'), 10);
		
		
		
		
		add_filter("manage_{$eal_posttype}_posts_columns" , array ($classname, 'CPT_set_table_columns'));
		add_action("manage_{$eal_posttype}_posts_custom_column" , array ($classname, 'CPT_fill_table_columns'), 10, 2 );
		add_filter("manage_edit-{$eal_posttype}_sortable_columns", array ($classname, 'CPT_set_table_columns_sortable')); 		
		add_filter("xxxposts_clauses", array ($classname, 'CPT_set_table_order'), 1, 2 );
		
		
		
		
// 		add_action("pre_get_posts", array ($classname, 'CPT_set_table_order'));
		
// 		if ( is_admin() ) {
// 			add_filter( 'request', array( $classname, 'CPT_set_table_order' ) );
// 		}
		
		
		
// 		add_action ("load-$name", array ($name, 'CPT_load_post'), 10);
// 		add_action ("edit_form_advanced", array ($name, 'CPT_load_post'), 10);
		
		add_filter('post_updated_messages', array ($classname, 'CPT_updated_messages') );
		add_action('contextual_help', array ($classname, 'CPT_contextual_help' ), 10, 3);

		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
		
		
	
		add_filter('posts_join', array ('CPT_Item', 'AIOThemes_joinPOSTMETA_to_WPQuery'));
		add_filter( 'posts_fields', array ('CPT_Item', 'filter_posts_fields'), 10, 1 );
		add_filter( 'posts_orderby', array ('CPT_Item', 'edit_posts_orderby'), 10, 1 );

		
	}
	
	static function edit_posts_orderby($orderby_statement) {
		
		global $wp_query;
		echo ("<script>console.log('edit_posts_orderby " . print_r($wp_query->get( 'orderby' ), true) . "');</script>");

		if ($wp_query->get( 'orderby' ) == "FW") $orderby_statement = "level_FW " . $wp_query->get( 'order' );
		if ($wp_query->get( 'orderby' ) == "PW") $orderby_statement = "level_PW " . $wp_query->get( 'order' );
		if ($wp_query->get( 'orderby' ) == "KW") $orderby_statement = "level_KW " . $wp_query->get( 'order' );
		
// 		$orderby_statement = "level_KW DESC";
		return $orderby_statement;
	}
	
	
	// define the posts_fields callback
	static function filter_posts_fields( $array ) {
		// make filter magic happen here...
		global $wp_query, $wpdb;
		echo ("<script>console.log('filter_posts_fields in " . print_r($array, true) . "');</script>");
		$array .= ", {$wpdb->prefix}eal_itemmc.*";
		echo ("<script>console.log('filter_posts_fields in " . print_r($array, true) . "');</script>");
		return $array;
// 		return array_merge ($array, array ("{$wpdb->prefix}eal_itemmc.*"));
	}
		
	static function AIOThemes_joinPOSTMETA_to_WPQuery($join) {
		global $wp_query, $wpdb;
	
// 		if (!empty($wp_query->query_vars['s'])) {
			$join .= " JOIN {$wpdb->prefix}eal_itemmc ON {$wpdb->prefix}eal_itemmc.id = {$wpdb->posts}.ID";
				
// 		}
	
		return $join;
	}
	
	
	
	static function CPT_add_meta_boxes($eal_posttype=null, $classname=null)  {
		
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($classname, 'CPT_add_description'), $eal_posttype, 'normal', 'default' );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($classname, 'CPT_add_question'), $eal_posttype, 'normal', 'default');
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($classname, 'CPT_add_level'), $eal_posttype, 'side', 'default');
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
		
		$html = self::generateLevelHTML(["FW"=>$item->level_FW, "KW"=>$item->level_KW, "PW"=>$item->level_PW]);
		
		echo $html;	
	}
	
	
	static function generateLevelHTML ($colNames, $prefix="item", $disabled="") {
		
		$html  = '<table><tr><td></td>';
		foreach ($colNames as $c => $v) {
			$html .= '<td>' . $c . '</td>';
		}
		
		$html .= '</tr>';
		
		foreach (EAL_Item::$levels as $n => $r) {
			$html .= '<tr><td>' . ($n+1) . ". " . $r . '</td>';
			foreach ($colNames as $c=>$v) {
				$html .= "<td align='center'><input type='radio' id='{$prefix}_level_{$c}_{$r}' name='{$prefix}_level_{$c}' value='" . ($n+1) . "' " . (($v==$n+1)?' checked':'') . " {$disabled}></td>";
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		
		return $html;
	}
	
	
	static function CPT_set_table_columns($columns) {
		return array_merge($columns, array('FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW', 'Punkte' => 'Punkte', 'Reviews' => 'Reviews'));
	
	}
	
	static function CPT_set_table_columns_sortable($columns) {
		return array_merge($columns, array('FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW', 'Punkte' => 'Punkte'));
	}
	
	static function CPT_fill_table_columns( $column, $post_id ) {
	
		global $post;
		switch ( $column ) {
			case 'FW': echo ($post->level_FW); break;
			case 'PW': echo ($post->level_PW); break;
			case 'KW': echo ($post->level_KW); break;
			case 'Punkte': echo ($post->points); break;
			case 'Reviews': echo ("<a href='post-new.php?post_type=review&item_id={$post->ID}'>Add</a>"); break; 
		}
	}
	


// 	static function CPT_set_table_order ($vars) {

// 			// Don't do anything if we are not on the Contact Custom Post Type
// 		if ( 'itemmc' != $vars['post_type'] ) return $vars;
		 
// 		// Don't do anything if no orderby parameter is set
// 		if ( ! isset( $vars['orderby'] ) ) return $vars;
		 
// 		// Check if the orderby parameter matches one of our sortable columns
// 		if ( $vars['orderby'] == 'Punkte' OR
// 				$vars['orderby'] == 'PW' ) {
// 					// Add orderby meta_value and meta_key parameters to the query
// 					$vars = array_merge( $vars, array(
// 							'meta_key' => $vars['orderby'],
// 							'orderby' => 'meta_value',
// 					));
// 				}
				 
// 				return $vars;
// 	}
	
// 	static function CPT_set_table_order ($query) {
// 		echo ("<script>console.log('CPT_set_table_order1 in " . get_class() . "');</script>");
// 		if( ! is_admin() ) return;
// 		echo ("<script>console.log('CPT_set_table_order2 in " . $query->get( 'post_type') . "');</script>");
		
// 		$orderby = $query->get( 'orderby');
		
// 		if( 'PW' == $orderby ) {
// 			$query->set('meta_key', 'Punkte');
// 			$query->set('orderby','meta_value_num');
// 		}
		
		
		
		
// 	}
	
	


	
}

?>