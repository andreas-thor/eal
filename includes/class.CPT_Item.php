<?php

abstract class CPT_Item {
	
	
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
						'supports' => array( 'title'), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
						'taxonomies' => array( 'topic' ),
						// 'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
						'has_archive' => false, // false to allow for single view
						'show_in_menu'    => true,
						'register_meta_box_cb' => array ($this, 'WPCB_register_meta_box_cb')
				)
		);

		
		
		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$this->type}", array ("eal_{$this->type}", 'save'), 10, 2);
		
		// TODO: Delete post hook 
		
		// Manage table of items (what columns to show; what columns are sortable 		
		add_filter("manage_{$this->type}_posts_columns" , array ($this, 'WPCB_manage_posts_columns'));
		add_filter("manage_edit-{$this->type}_sortable_columns", array ($this, 'WPCB_manage_edit_sortable_columns')); 		
		add_action("manage_{$this->type}_posts_custom_column" , array ($this, 'WPCB_manage_posts_custom_column'), 10, 2 );
		
	
		add_filter('posts_join', array ($this, 'WPCB_posts_join'));
		add_filter('posts_fields', array ($this, 'WPCB_posts_fields'), 10, 1 );
		add_filter('posts_orderby', array ($this, 'WPCB_posts_orderby'), 10, 1 );
		
		
		
		
		
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
		
		
		add_action( 'restrict_manage_posts', array($this, 'pippin_add_taxonomy_filters') );
 	
		
	}
	
	
	
	public function getTopicTermOption ($term, $level) {
	
		$result  = "<option value='{$term->slug}' " . (($_GET[$tax_slug] == $term->slug) ? " selected='selected'" : "") . ">";
		$result .= str_repeat ("&nbsp;", $level*2) . "+ " . $term->name;  
		$result .= " ({$term->count})</option>";
		
		foreach (get_terms ('topic', array ('parent'=> $term->term_id)) as $t) {
			$result .= $this->getTopicTermOption ($t, $level+1);
		}
		return $result;
	}
	
	
	public function pippin_add_taxonomy_filters() {
		global $typenow;
	
		// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array('topic');
	
		// must set this to the post type you want the filter(s) displayed on
		if( $typenow == $this->type ){
	
			foreach ($taxonomies as $tax_slug) {
				$tax_obj = get_taxonomy($tax_slug);
				$tax_name = $tax_obj->labels->name;
				$terms = get_terms($tax_slug, array ('parent' => 0));
				if(count($terms) > 0) {
					echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
					echo "<option value=''>Show All $tax_name</option>";
					foreach ($terms as $term) {
						echo $this->getTopicTermOption ($term, 0);
// 						echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>';
					}
					echo "</select>";
				}
			}
		}
	}
	
	
	
	abstract public function WPCB_register_meta_box_cb ();
	
	abstract public function WPCB_mb_answers ($post, $vars); 
	
	
	
	
	


	
	
	public function WPCB_mb_description ($post, $vars) {
	
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
	
	
	public function WPCB_mb_question ($post, $vars) {
	
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
	
	
	public function WPCB_mb_level ($post, $vars) {
	
		global $item;
		$html = self::generateLevelHTML($item->level);
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
	
	
	
	
	
	static function generateLevelHTML ($level, $prefix="item", $disabled="") {
		
		$html  = "<table style='font-size:100%'><tr><td></td>";
		foreach ($level as $c => $v) {
			$html .= '<td>' . $c . '</td>';
		}
		
		$html .= '</tr>';
		
		foreach (EAL_Item::$level_label as $n => $r) {
			$html .= '<tr><td>' . ($n+1) . ". " . $r . '</td>';
			foreach ($level as $c=>$v) {
				$html .= "<td align='center'><input type='radio' id='{$prefix}_level_{$c}_{$r}' name='{$prefix}_level_{$c}' value='" . ($n+1) . "' " . (($v==$n+1)?'checked':$disabled) . "></td>";
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		
		return $html;
	}
	
	
	public function WPCB_manage_posts_columns($columns) {
		return array_merge($columns, array('Topic2' => 'Topic2', 'FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW', 'Punkte' => 'Punkte', 'Reviews' => 'Reviews'));
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		return array_merge($columns, array('Topic2' => 'Topic2', 'FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW', 'Punkte' => 'Punkte', 'Reviews' => 'Reviews'));
	}
	
	
	public function getTopicTerm ($term, $level) {
		
		$result = str_repeat ("&nbsp;", $level*2) . "+ " . $term->name;
		foreach (get_terms ('topic', array ('parent'=> $term->term_id)) as $t) {
			$result .= "<br/>" . $this->getTopicTerm ($t, $level+1);
		}
		return $result;
	}
	
	
	
	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
		global $post;
		
		switch ( $column ) {
			case 'FW': echo ($post->level_FW); break;
			case 'PW': echo ($post->level_PW); break;
			case 'KW': echo ($post->level_KW); break;
			case 'Punkte': echo ($post->points); break;
			case 'Topic2': 
				
				$rootterms = get_terms ('topic', array ('parent'=>0));
				foreach ($rootterms as $rt) {
					echo $this->getTopicTerm($rt, 0);
				}
				
				
				
				
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
				$s = wp_list_categories( $args );
				
				
				
				break;
			case 'Reviews': 
				
				
				global $wpdb;
				$sqlres = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}eal_{$this->type}_review WHERE item_id = {$post->ID}");
				echo (count($sqlres) . "<br/>");
				foreach ($sqlres as $pos => $review_id) {
					echo ("<a href='post.php?post=${review_id}&action=edit'>&nbsp;#${pos}&nbsp;</a>&nbsp;&nbsp;");
				}
				
				
				echo ("<a href='post-new.php?post_type={$this->type}_review&item_id={$post->ID}'>Add</a>"); 
				break; 
		}
	}

	public function WPCB_posts_join ($join) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$join .= " JOIN {$wpdb->prefix}eal_{$this->type} ON {$wpdb->prefix}eal_{$this->type}.id = {$wpdb->posts}.ID";
		}
		return $join;
	}
	
	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		// make filter magic happen here...
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array .= ", {$wpdb->prefix}eal_{$this->type}.*";
		}
		return $array;
		// 		return array_merge ($array, array ("{$wpdb->prefix}eal_itemmc.*"));
	}
	

	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query;
		if ($wp_query->query["post_type"] == $this->type) {
			if ($wp_query->get( 'orderby' ) == "FW") $orderby_statement = "level_FW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "PW") $orderby_statement = "level_PW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "KW") $orderby_statement = "level_KW " . $wp_query->get( 'order' );
		}
	
		// 		$orderby_statement = "level_KW DESC";
		return $orderby_statement;
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
	
	


	
}

?>