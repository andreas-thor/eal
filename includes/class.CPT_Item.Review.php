<?php

// TODO: Delete Review (in POst Tabelle) --> löschen in Review-Tabelle


require_once ("class.EAL_Item.Review.php");

abstract class CPT_Item_Review {
	

	public $type;
	public $label;
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init() {

		$classname = get_called_class();
		
		register_post_type( $this->type,
				array(
						'labels' => array(
								'name' => $this->label,
								'singular_name' => $this->label,
								'add_new' => 'Add ' . $this->label,
								'add_new_item' => 'Add New ' . $this->label,
								'edit' => 'Edit',
								'edit_item' => 'Edit ' . $this->label,
								'new_item' => 'New ' . $this->label,
								'view' => 'View',
								'view_item' => 'View ' . $this->label,
								'search_items' => 'Search ' . $this->label,
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
						'register_meta_box_cb' => array ($this, 'WPCB_register_meta_box_cb')
				)
		);
		
		
		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$this->type}", array ($this, 'WPCB_save_post'), 10, 2);

		// TODO: delete review
		
		add_filter('post_updated_messages', array ($this, 'WPCB_post_updated_messages') );
	}
	
	
	
	abstract public function WPCB_register_meta_box_cb ();
	
	abstract public function WPCB_save_post($post_id, $post);
	
	
	

	
	public function WPCB_mb_item ($post, $vars) {
	
		global $review;
		if (!is_null($review->getItem())) {
			$html = $review->getItem()->getPreviewHTML();
			echo $html;
		}
	}
	
	
	

	
	public function WPCB_mb_score ($post, $vars) {
		
		global $review;
		
		$values = ["gut", "Korrektur", "ungeeignet"];
		
		
		$html_head = "<tr><th></th>";
		foreach (EAL_Item_Review::$dimension2 as $k2 => $v2) {
			$html_head .= "<th style='padding:0.5em'>{$v2}</th>";
		}
		$html_head .= "</tr>";
				
		$html_rows = "";
		foreach (EAL_Item_Review::$dimension1 as $k1 => $v1) {
			$html_rows .= "<tr><td valign='top'style='padding:0.5em'>{$v1}</td>";
			foreach (EAL_Item_Review::$dimension2 as $k2 => $v2) {
				$html_rows .= "<td style='padding:0.5em; border-style:solid; border-width:1px;'>";
				foreach ($values as $k3 => $v3) {
					$html_rows .= "<input type='radio' id='{$k1}_{$k2}_{k3}' name='review_{$k1}_{$k2}' value='" . ($k3+1) . "' " . (($review->score[$k1][$k2]==$k3+1)?"checked":"") . ">{$v3}<br/>";
				}
				$html_rows .= "</td>";
			}
			$html_rows .= "</tr>";
		}
				
		echo ("<table style='font-size:100%'>{$html_head}{$html_rows}</table>");
			
	}
	
	
	
	public function WPCB_mb_level ($post, $vars) {
	
		global $review;
	
		$html_item = CPT_Item::generateLevelHTML($review->getItem()->level, "item", "disabled");
		$html_review = CPT_Item::generateLevelHTML($review->level, "review", "");
	
		$html = "<table style='font-size:100%'><tr>
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
	
	

	
	public function WPCB_mb_feedback ($post, $vars) {
	
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
	
	

	public function WPCB_mb_overall ($post, $vars) {
	
		global $review;
	
	
		$html = "
				<input type='hidden' id='item_id' name='item_id'  value='{$review->item_id}'>
				<table style='font-size:100%'>
			<tr><td>
				<input type='radio' id='review_overall_0' name='review_overall' value='1' " . (($review->overall==1) ? "checked" : ""). ">Item akzeptiert</td></tr>
			<tr><td><input type='radio' id='review_overall_1' name='review_overall' value='2' " . (($review->overall==2) ? "checked" : ""). ">Item &uuml;berarbeiten</td></tr>
			<tr><td><input type='radio' id='review_overall_2' name='review_overall' value='3' " . (($review->overall==3) ? "checked" : ""). ">Item abgelehnt</td></tr>
				</table>
		
				
			";
	
		echo $html;
	}
	
	

	public function WPCB_post_updated_messages ( $messages ) {
	
		global $post, $post_ID;
		$messages[$this->type] = array(
				0 => '',
				1 => sprintf( __("{$this->label} updated. <a href='%s'>View {$this->label}</a>"), esc_url( get_permalink($post_ID) ) ),
				2 => __('Custom field updated.'),
				3 => __('Custom field deleted.'),
				4 => __("{$this->label} updated."),
				5 => isset($_GET['revision']) ? sprintf( __("{$this->label} restored to revision from %s"), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __("{$this->label} published. <a href='%s'>View {$this->label}</a>"), esc_url( get_permalink($post_ID) ) ),
				7 => __("{$this->label} saved."),
				8 => sprintf( __("{$this->label} submitted. <a target='_blank' href='%s'>Preview {$this->label}</a>"), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
				9 => sprintf( __("{$this->label} scheduled for: <strong>%1$s</strong>. <a target='_blank' href='%2$s'>View {$this->label}</a>"), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
				10 => sprintf( __("{$this->label} draft updated. <a target='_blank' href='%s'>Preview {$this->label}</a>"), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
				);
		return $messages;
	}
	
	
}

?>