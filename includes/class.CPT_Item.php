<?php

require_once("class.CPT_Object.php");

abstract class CPT_Item extends CPT_Object{
	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {
		
		parent::init();

		$classname = get_called_class();
		
		
		// TODO: Delete post hook 
		

		
		
		
		
		add_filter("xxxposts_clauses", array ($classname, 'CPT_set_table_order'), 1, 2 );
		
		
		
		
// 		add_action("pre_get_posts", array ($classname, 'CPT_set_table_order'));
		
// 		if ( is_admin() ) {
// 			add_filter( 'request', array( $classname, 'CPT_set_table_order' ) );
// 		}
		
		
		
// 		add_action ("load-$name", array ($name, 'CPT_load_post'), 10);
// 		add_action ("edit_form_advanced", array ($name, 'CPT_load_post'), 10);
		
		add_filter('post_updated_messages', array ($this, 'WPCB_post_updated_messages') );
		add_action('contextual_help', array ($classname, 'CPT_contextual_help' ), 10, 3);

		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
		
		add_action ("save_post_revision", array ("eal_{$this->type}", 'save'), 10, 2);
		add_filter ('wp_get_revision_ui_diff', array ($this, 'WPCB_wp_get_revision_ui_diff'), 10, 3 );
		
		
 	
	}
	
	

	
	
	public function WPCB_register_meta_box_cb () {
	
		global $item;
		
		
		add_meta_box('mb_learnout', 'Learning Outcome', array ($this, 'WPCB_mb_learnout'), $this->type, 'normal', 'default', array ('learnout' => $item->getLearnOut()));
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_description', 'value' => $item->description) );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_question', 'value' => $item->question));
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $item->level, 'default' => (($item->getLearnOut() == null) ? null : $item->getLearnOut()->level) ));
		add_meta_box("mb_{$this->type}_answers", "Antwortoptionen",	array ($this, 'WPCB_mb_answers'), $this->type, 'normal', 'default');
	}
	
	

	abstract public function WPCB_mb_answers ($post, $vars);
	
	
	public function WPCB_mb_level ($post, $vars) {
		
?>
		<script>
			function checkLOLevel (e, levIT, levITs, levLO, levLOs) {
				if (levIT == levLO) return;

				if (levLO == 0) {
					alert (unescape ("Learning Outcome hat keine Anforderungsstufe f%FCr diese Wissensdimension."));
					return;
				}
				
				if (levIT > levLO) {
					alert ("Learning Outcome hat niedrigere Anforderungsstufe! (" + levLOs + ")");
				} else {
					alert (unescape ("Learning Outcome hat h%F6here Anforderungsstufe! (") + levLOs + ")");
				}	
				
			}
		</script>
<?php		
		
		$vars['args']['callback'] = 'checkLOLevel';
		return parent::WPCB_mb_level($post, $vars);
		
	}
	
	
	
	public function WPCB_mb_learnout ($post, $vars) {
	
		$learnout = $vars['args']['learnout'];
		if ($learnout != null) {
			echo ("<div class='misc-pub-section'><b>{$learnout->title}</b>");
			if (strlen($learnout->description)>0) {
				echo (": {$learnout->description}");
			}
			echo ("</div>");
		}
		echo ("<hr>");
		echo (EAL_LearnOut::getListOfLearningOutcomes($learnout == null ? 0 : $learnout->id));
		
	}
	
	
	
	
	
	public function WPCB_manage_posts_columns($columns) {
		return array_merge(parent::WPCB_manage_posts_columns($columns), array('Punkte' => 'Punkte', 'Reviews' => 'Reviews'));
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		return array_merge(parent::WPCB_manage_edit_sortable_columns($columns) , array('Punkte' => 'Punkte', 'Reviews' => 'Reviews'));
	}
	
	
	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
		parent::WPCB_manage_posts_custom_column($column, $post_id);
		
		global $post;
	
		switch ( $column ) {
			case 'Punkte': echo ($post->points); break;
			
			case 'Reviews':
	
				global $wpdb;
				$sqlres = $wpdb->get_results( "
						SELECT R.id as review_id, P.post_modified as last_changed
						FROM {$wpdb->prefix}eal_{$this->type}_review AS R
						JOIN {$wpdb->prefix}posts AS P ON (R.id = P.ID)
						WHERE R.item_id = {$post->ID}
						ORDER BY R.id
						");
	
				echo ("<div onclick=\"this.nextSibling.style.display = (this.nextSibling.style.display == 'none') ? 'block' : 'none';\">" . count($sqlres) . " review(s)</div>");
				echo ("<div style='display:none'>");
				foreach ($sqlres as $pos => $sqlrow) {
					echo ("<a href='post.php?post={$sqlrow->review_id}&action=edit'>&nbsp;#" . ($pos+1) . "&nbsp;{$sqlrow->last_changed}</a><br/>");
				}
	
				echo ("</div>");
				echo ("<h1><a class='page-title-action' href='post-new.php?post_type={$this->type}_review&item_id={$post->ID}'>Add&nbsp;New&nbsp;Review</a></h1>");
				break;
		}
	}
	

	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array = parent::WPCB_posts_fields($array) . ", (select count(*) from {$wpdb->prefix}eal_{$this->type}_review where {$wpdb->prefix}eal_{$this->type}.id = {$wpdb->prefix}eal_{$this->type}_review.item_id) as reviews ";
		}
		return $array;
	}
	
	
	

	

	
	



	
	
// 	public function WPCB_mb_description ($post, $vars) {
	
// 		global $item;
// 		$editor_settings = array(
// 				'media_buttons' => false,	// no media buttons
// 				'teeny' => true,			// minimal editor
// 				'quicktags' => false,		// hides Visual/Text tabs
// 				'textarea_rows' => 3,
// 				'tinymce' => true
// 		);
	
// 		$html = wp_editor(wpautop(stripslashes($item->description)) , 'item_description', $editor_settings );
// 		echo $html;
// 	}
	
	
// 	public function WPCB_mb_question ($post, $vars) {
	
// 		global $item;
// 		$editor_settings = array(
// 				'media_buttons' => false,	// no media buttons
// 				'teeny' => true,			// minimal editor
// 				'quicktags' => false,		// hides Visual/Text tabs
// 				'textarea_rows' => 3,
// 				'tinymce' => true
// 		);
	
// 		$html = wp_editor(wpautop(stripslashes($item->question)) , 'item_question', $editor_settings );
// 		echo $html;
// 	}	
	
	

	
	
	

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
		

	// 	public function CPT_add_meta_boxes($eal_posttype=null, $classname=null)  {
	
	// 		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($classname, 'CPT_add_description'), $eal_posttype, 'normal', 'default' );
	// 		add_meta_box('mb_question', 'Aufgabenstellung', array ($classname, 'CPT_add_question'), $eal_posttype, 'normal', 'default');
	// 		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($classname, 'CPT_add_level'), $eal_posttype, 'side', 'default');
	// 	}
	
			
		
		
// 	}
	
	
	

// 	case 'Topic2':
	
// 		//				die nächsten drei Zeilen passen
// 		$rootterms = get_terms ('topic', array ('parent'=>0));
// 		foreach ($rootterms as $rt) {
// 			echo $this->getTopicTerm($rt, 0);
// 		}
	
	
	
	
		// 				$all = get_the_terms ($post_id, 'topic');
		// 				if (isset ($all) && (is_array($all))) {
		// 					foreach ($all as $pos => $term) {
		// 						$res = $term->name;
		// 						$parent_id = $term->parent;
		// 						while (isset($parent_id)) {
		// 							$parent = get_term ($parent_id, 'topic');
		// 							$res = $parent->name . " -- " . $res;
		// 							$parent_id = $parent->parent;
		// 						}
		// 						echo ($res . "<br/>");
		// 					}
			
			
		// 				}
	
		// 				$args = array(
		// 						'show_option_all'    => '',
		// 						'orderby'            => 'name',
		// 						'order'              => 'ASC',
		// 						'style'              => 'list',
		// 						'show_count'         => 0,
		// 						'hide_empty'         => 0,
		// 						'use_desc_for_title' => 1,
		// 						'child_of'           => 0,
		// 						'feed'               => '',
		// 						'feed_type'          => '',
		// 						'feed_image'         => '',
		// 						'exclude'            => '',
		// 						'exclude_tree'       => '',
		// 						'include'            => '',
		// 						'hierarchical'       => 1,
		// 						'title_li'           => __( 'Categories' ),
		// 						'show_option_none'   => __( '' ),
		// 						'number'             => null,
		// 						'echo'               => 0,
		// 						'depth'              => 0,
		// 						'current_category'   => 0,
		// 						'pad_counts'         => 0,
		// 						'taxonomy'           => 'topic',
		// 						'walker'             => null
		// 				);
	
		// 				$s = wp_list_categories( $args );
	
// 		break;


	
}

?>