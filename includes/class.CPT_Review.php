<?php

// TODO: Delete Review (in POst Tabelle) --> löschen in Review-Tabelle


include ("class.EAL_Review.php");

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
						'supports' =>  false, // array(  'title' ), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
						'taxonomies' => array(  ),
						// 'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
						'has_archive' => true,
						'show_in_menu'    => false,
						'register_meta_box_cb' => array ($classname, 'CPT_add_meta_boxes')
				)
		);
		
		
		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$eal_posttype}", array ("eal_{$eal_posttype}", 'save'), 10, 2);

		// TODO: delete review
		
		
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
		
		echo ("<script>console.log('CPT_add_meta_boxesn " . get_class() . " with POST[item_id] == " . ($_POST['item_id']) . "');</script>");
		echo ("<script>console.log('CPT_add_meta_boxesn " . get_class() . " with  GET[item_id] == " . ($_GET['item_id']) . "');</script>");
		
		
		echo ("Hier");
		$eal_posttype="review";
		$classname="cpt_review";

		global $review;
		$review = new EAL_Review();
		$review->load();
		
// 		global $item;
// 		$item = new EAL_ItemMC();
// 		$item->loadById(isset ($_POST['item_id']) ? $_POST['item_id'] : $_GET['item_id']);
		
		add_meta_box('mb_item', 'Item', array ($classname, 'CPT_add_item'), $eal_posttype, 'normal', 'default' );
		
		
		add_meta_box('mb_review_score', 'Fall- oder Problemvignette, Aufgabenstellung und Antwortoptionen', array ($classname, 'CPT_add_score'), $eal_posttype, 'normal', 'default' );
		add_meta_box('mb_review_level', 'Anforderungsstufe', array ($classname, 'CPT_add_level'), $eal_posttype, 'normal', 'default');
		add_meta_box('mb_review_feedback', 'Feedback', array ($classname, 'CPT_add_feedback'), $eal_posttype, 'normal', 'default');
		add_meta_box('mb_review_overall', 'Revisionsurteil', array ($classname, 'CPT_add_overall'), $eal_posttype, 'side', 'default');
	}
	
	
	static function CPT_add_item ($post, $vars) {
	
		global $review;
		$item = $review->getItem();
		if (!is_null($item)) {
			$html = $item->getPreviewHTML();
			echo $html;
		}
	}
	
	
	

	
	static function CPT_add_score ($post, $vars) {
		
		global $review;
		
		$values = ["gut", "Korrektur", "ungeeignet"];
		
		
		$html_head = "<tr><th></th>";
		foreach (EAL_Review::$dimension2 as $k2 => $v2) {
			$html_head .= "<th style='padding:0.5em'>{$v2}</th>";
		}
		$html_head .= "</tr>";
				
		$html_rows = "";
		foreach (EAL_Review::$dimension1 as $k1 => $v1) {
			$html_rows .= "<tr><td valign='top'style='padding:0.5em'>{$v1}</td>";
			foreach (EAL_Review::$dimension2 as $k2 => $v2) {
				$html_rows .= "<td style='padding:0.5em; border-style:solid; border-width:1px;'>";
				foreach ($values as $k3 => $v3) {
					$html_rows .= "<input type='radio' id='{$k1}_{$k2}_{k3}' name='review_{$k1}_{$k2}' value='" . ($k3+1) . "' " . (($review->score[$k1][$k2]==$k3+1)?"checked":"") . ">{$v3}<br/>";
				}
				$html_rows .= "</td>";
			}
			$html_rows .= "</tr>";
		}
				
		echo ("<table>{$html_head}{$html_rows}</table>");
			
	}
	
	
	
	static function CPT_add_level ($post, $vars) {
	
		global $review;
	
		
		$html_item = CPT_Item::generateLevelHTML(["FW"=>$review->getItem()->level_FW, "KW"=>$review->getItem()->level_KW, "PW"=>$review->getItem()->level_PW], "item", "disabled");
		$html_review = CPT_Item::generateLevelHTML(["FW"=>$review->level_FW, "KW"=>$review->level_KW, "PW"=>$review->level_PW], "review", "");
		
	
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
	
	

	
	static function CPT_add_feedback ($post, $vars) {
	
		global $review;
	
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
	
		$html = wp_editor(wpautop(stripslashes($review->feedback)) , 'review_feedback', $editor_settings );
		echo $html;
	}
	
	

	static function CPT_add_overall ($post, $vars) {
	
		global $review;
	
	
		$html = "<table>
			<tr><td>
				<input type='hidden' id='item_id' name='item_id' value='{$review->item_id}'>
				<input type='radio' id='review_overall_0' name='review_overall' value='1' " . (($review->overall==1) ? "checked" : ""). ">Item akzeptiert</td></tr>
			<tr><td><input type='radio' id='review_overall_1' name='review_overall' value='2' " . (($review->overall==2) ? "checked" : ""). ">Item &uuml;berarbeiten</td></tr>
			<tr><td><input type='radio' id='review_overall_2' name='review_overall' value='3' " . (($review->overall==3) ? "checked" : ""). ">Item abgelehnt</td></tr>
				</table>
				
			";
	
		echo $html;
	}
	
	

	
}

?>