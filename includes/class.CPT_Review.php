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
						'supports' =>  array(  'title' ), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
						'taxonomies' => array( 'topic' ),
						// 'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
						'has_archive' => true,
						'show_in_menu'    => true,
						'register_meta_box_cb' => array ($classname, 'CPT_add_meta_boxes')
				)
		);
		
		add_filter('post_updated_messages', array ($classname, 'CPT_updated_messages') );
		
// 		add_filter( 'title_edit_pre', array ($classname, 'filter_function_name'), 10, 2 );
		
		
// 		add_filter( 'YYwp_title', array ($classname, 'wpdocs_filter_wp_title'), 10, 2 );
		
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

		global $item;
		$item = new EAL_ItemMC();
		$item->loadById(isset ($_POST['item_id']) ? $_POST['item_id'] : $_GET['item_id']);
		
		add_meta_box('mb_item', 'Item', array ($classname, 'CPT_add_item'), $eal_posttype, 'normal', 'default' );
		add_meta_box('mb_review_item', 'Fall- oder Problemvignette, Aufgabenstellung und Antwortoptionen', array ($classname, 'CPT_add_review'), $eal_posttype, 'normal', 'default' );
		add_meta_box('mb_review_level', 'Anforderungsstufe', array ($classname, 'CPT_add_level'), $eal_posttype, 'normal', 'default');
		add_meta_box('mb_review_score', 'Revisionsurteil', array ($classname, 'CPT_add_score'), $eal_posttype, 'side', 'default');
		add_meta_box('mb_review_feedback', 'Feedback', array ($classname, 'CPT_add_feedback'), $eal_posttype, 'normal', 'default');
	}
	
	
	static function CPT_add_item ($post, $vars) {
	
		global $item;
		$html = $item->getPreviewHTML();
		echo $html;
	}
	
	
	
	static function generate3HTML($name) {
		
		return "
				<input type='radio' id='{$name}_0' name='{$name}' value='0'>gut<br/>
				<input type='radio' id='{$name}_1' name='{$name}' value='1'>Korrektur<br/>
				<input type='radio' id='{$name}_2' name='{$name}' value='2'>ungeeignet
				";
		
	}
	
	static function CPT_add_review ($post, $vars) {
		
		
		$html = "
			<table>
			<tr>
				<th></th>
				<th style='padding-left:1em'>fachl. Richtigkeit</th>
				<th style='padding-left:1em'>Relevanz bzgl. LO</th>
				<th style='padding-left:1em'>Formulierung</th>
			</tr>
			<tr><td colspan=4> &nbsp;</td></tr>
			<tr>
				<td valign='top'>Fall- oder Problemvignette</td>
				<td style='padding-left:1em'>" . self::generate3HTML("11") . "</td>
				<td style='padding-left:1em'>" . self::generate3HTML("12") . "</td>
				<td style='padding-left:1em'>" . self::generate3HTML("13") . "</td>
			</tr>
			<tr><td colspan=4> &nbsp;</td></tr>
			<tr>
				<td valign='top'>Aufgabenstellung</td>
				<td style='padding-left:1em'>" . self::generate3HTML("21") . "</td>
				<td style='padding-left:1em'>" . self::generate3HTML("22") . "</td>
				<td style='padding-left:1em'>" . self::generate3HTML("23") . "</td>
			</tr>
			<tr><td colspan=4> &nbsp;</td></tr>
			<tr>
				<td valign='top'>Antwortoptionen</td>
				<td style='padding-left:1em'>" . self::generate3HTML("31") . "</td>
				<td style='padding-left:1em'>" . self::generate3HTML("32") . "</td>
				<td style='padding-left:1em'>" . self::generate3HTML("33") . "</td>
			</tr>
			</table>			
				
			";
		
		echo ($html);
	}
	
	static function CPT_add_level ($post, $vars) {
	
		global $item;
	
		
		$html_item = CPT_Item::generateLevelHTML(["FW"=>$item->level_FW, "KW"=>$item->level_KW, "PW"=>$item->level_PW], "item", "disabled");
		$html_review = CPT_Item::generateLevelHTML(["FW"=>0, "KW"=>0, "PW"=>0], "review", "");
		
	
		$html = "<table><tr>
			<th align='left'>Einordnung Autor</th>
			<th style='padding-left:3em;'></th>
			<th align='left'>Einordnung Review</th>
		</tr><tr>
			<td style='border-style:solid; border-width:1px;'>{$html_item}</td>
			<td style='padding-left:3em;'></td>
			<td style='border-style:solid; border-width:1px;''>{$html_review}</td>
		</tr></table>";
		echo $html;
	}
	
	
	
	static function CPT_add_score ($post, $vars) {
	
		global $item;
		
		
		$html = "<table>
			<tr><td><input type='radio' id='feedback_0' name='feedback' value='0'>Item akzeptiert</td></tr>
			<tr><td><input type='radio' id='feedback_0' name='feedback' value='1'>Item &uuml;berarbeiten</td></tr> 
			<tr><td><input type='radio' id='feedback_0' name='feedback' value='2'>Item abgelehnt</td></tr>
				</table>
			";
		
		echo $html;
	}
	
	
	static function CPT_add_feedback ($post, $vars) {
	
		global $item;
	
	
		
	
	
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
	
		$html = wp_editor(wpautop(stripslashes("")) , 'review_feedback', $editor_settings );
		echo $html;
	}
	
	

	

	
}

?>