<?php


class CPT_Review {
	

	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	static function CPT_init($eal_posttype="review", $label="Review", $classname="cpt_review") {
		
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
		
		add_filter('post_updated_messages', array ($classname, 'CPT_updated_messages') );
		
		add_filter( 'title_edit_pre', array ($classname, 'filter_function_name'), 10, 2 );
		
		
		add_filter( 'YYwp_title', array ($classname, 'wpdocs_filter_wp_title'), 10, 2 );
		
	}
	
	static function wpdocs_filter_wp_title( $title, $sep ) {
		
	
		return $title . "A";
	}
	
	static function filter_function_name( $content, $post_id ) {
		// Process content here
		return "Review #1 for Item #43";
	}
	
	static function CPT_updated_messages( $messages ) {
	
		global $post, $post_ID;
		$messages['review'] = array(
				0 => '',
				1 => sprintf( __('MC Question updated. <a href="%s">View MC Question</a>'), esc_url( get_permalink($post_ID) ) ),
				2 => __('Custom field updated.'),
				3 => __('Custom field deleted.'),
				4 => __('MC Question updated.'),
				5 => isset($_GET['revision']) ? sprintf( __('Product restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __('MC Question published. <a href="%s">View MC Question</a>'), esc_url( get_permalink($post_ID) ) ),
				7 => __('MC Question saved.'),
				8 => sprintf( __('MC Question submitted. <a target="_blank" href="%s">MC Question product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
				9 => sprintf( __('MC Question scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">MC Question product</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
				10 => sprintf( __('MC Question draft updated. <a target="_blank" href="%s">MC Question product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}
	
	
	
	static function CPT_add_meta_boxes()  {
		echo ("Hier");
		$eal_posttype="review";
		$classname="cpt_review";

		
		
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
	
		$html = wp_editor(wpautop(stripslashes("")) , 'item_description', $editor_settings );
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
	
		$html = wp_editor(wpautop(stripslashes("")) , 'item_question', $editor_settings );
		echo $html;
	}	
	
	
	static function CPT_add_level ($post, $vars) {
	
		global $item;
		
		$colNames = ["FW"=>1, "KW"=>2, "PW"=>3];
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
	
	

	
}

?>