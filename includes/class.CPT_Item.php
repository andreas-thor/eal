<?php

require_once("class.CPT_Object.php");
require_once("class.CLA_RoleTaxonomy.php");

class CPT_Item extends CPT_Object{
	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {
		
		if (!isset($this->type)) {
			$this->type = "item";
			$this->label = "Item";
			$this->menu_pos = 0;
		}
		
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

		
		add_action ("save_post_revision", array ("eal_{$this->type}", 'save'), 10, 2);
		add_filter ('wp_get_revision_ui_diff', array ($this, 'WPCB_wp_get_revision_ui_diff'), 10, 3 );
		
		add_filter('post_row_actions', array ($this ,'WPCB_post_row_actions'), 10, 2);
		
		add_filter('posts_search', array ($this ,'WPCB_post_search'), 10, 2);
		
		
		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
		
		
		add_filter( 'views_edit-' . $this->type, array ($this, 'wpse14230_views_edit_post'));
		add_filter( 'wp_count_posts', array ($this, 'wpse149143_wp_count_posts'), 10, 3);
		
		
	}

	public function wpse14230_views_edit_post( $views )
	{
// 		unset ($views["all"]);
		return $views;
	}
	
	
	/**
	 * Modify returned post counts by status for the current post type.
	 *  Only retrieve counts of own items for users without rights to 'edit_others_posts'
	 *
	 * @since   26 June 2014
	 * @version 26 June 2014
	 * @author  W. van Dam
	 *
	 * @notes   Based on wp_count_posts (wp-includes/posts.php)
	 *
	 * @param object $counts An object containing the current post_type's post
	 *                       counts by status.
	 * @param string $type   Post type.
	 * @param string $perm   The permission to determine if the posts are 'readable'
	 *                       by the current user.
	 *
	 * @return object Number of posts for each status
	 */
	function wpse149143_wp_count_posts( $counts, $type, $perm ) {
		global $wpdb;
	
		if ($type != $this->type) return $counts;
		
		$query  = "
			SELECT post_status, COUNT( * ) AS num_posts 
			FROM {$wpdb->posts} P
			JOIN {$wpdb->prefix}eal_item E
			ON (P.ID = E.ID)
			WHERE E.domain = '" . RoleTaxonomy::getCurrentDomain()["name"] . "' 
			AND P.post_type ";
		$query .= ($type == "item") ? "LIKE 'item%'" : ("= '" . $type . "'");
		$query .= " GROUP BY P.post_status";
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		$counts = array_fill_keys( get_post_stati(), 0 );
	
		foreach ( $results as $row ) {
			$counts[ $row['post_status'] ] = $row['num_posts'];
		}
		return (object) $counts;
	}
	
	public function WPCB_post_search($search, $wpquery){
	
		global $post_type;
		if ($post_type != $this->type) return $search;
		
		return $search;
// 		$a = 7;
	}
	
	public function WPCB_post_row_actions($actions, $post){

		
		if ($post->post_type != $this->type) return $actions;
		
		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
		$actions['view'] = "<a href='admin.php?page=view&itemid={$post->ID}'>View</a>"; // add "View"
		
		if (!RoleTaxonomy::canEditItemPost($post)) {		// "Edit" & "Trash" only if editable by user
			unset ($actions['edit']);
			unset ($actions['trash']);
		}

		return $actions;
	}
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $item, $post;
		
		if ($item->domain != RoleTaxonomy::getCurrentDomain()["name"]) {
			wp_die ("Item does not belong to your current domain!");
		}
		
		
		$post->post_title .= "\x03";	// we add ASCII 03 to modify the title
		
		add_meta_box('mb_learnout', 'Learning Outcome', array ($this, 'WPCB_mb_learnout'), $this->type, 'normal', 'default', array ('learnout' => $item->getLearnOut()));
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_description', 'value' => $item->description) );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_question', 'value' => $item->question));
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $item->level, 'default' => (($item->getLearnOut() == null) ? null : $item->getLearnOut()->level) ));
		add_meta_box("mb_{$this->type}_answers", "Antwortoptionen",	array ($this, 'WPCB_mb_answers'), $this->type, 'normal', 'default');
		
		
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::getCurrentDomain()["label"], array ($this, 'WPCB_mb_taxonomy'), $this->type, 'side', 'default', array ( "taxonomy" => RoleTaxonomy::getCurrentDomain()["name"] ));
		
	}
	
	
	public function WPCB_mb_taxonomy ($post, $vars) {
		post_categories_meta_box( $post, array ("id" => "WPCB_mb_taxonomy", "title" => "", "args" => $vars['args']) );
	}
	
	
	public function WPCB_mb_answers ($post, $vars) { 
		wp_die ("<pre>Can not call WPCB_mb_answers on CPT_Item.</pre>");
	}
	
	
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
		return array('cb' => '<input type="checkbox" />', 'post_title' => 'Title', 'date' => 'Date', 'type' => 'Type', 'item_author' => 'Author', 'points' => 'Points', 'FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW', 'Reviews' => 'Reviews', 'LO' => 'LO', 'difficulty' => 'Difficulty');
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		return array('a' => 'b', 'post_status' => 'Status', 'date' => 'date', 'type' => 'Type', 'item_author' => 'Author', 'Punkte' => 'Punkte', 'FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW', 'Reviews' => 'Reviews', 'LO' => 'LO', 'difficulty' => 'Difficulty');
	}
	
	
	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
// 		parent::WPCB_manage_posts_custom_column($column, $post_id);
		
		global $post;
	
 		$basic_url = remove_query_arg (array ("item_author", "points", "level_FW", "level_KW", "level_PW"));
		
		switch ( $column ) {
			
			case 'post_title': 
				printf ($post->post_title); 
				if ($post->post_status == "draft") echo (' &mdash; <span class="post-state"><i>Draft</i></span>');
				if ($post->post_status == "pending") echo (' &mdash; <span class="post-state"><b>Pending</b></span>');
				break;
			
			case 'type':
				if ($post->type == "itemsc") echo ('<div class="dashicons-before dashicons-marker" style="display:inline">&nbsp;</div>');
				if ($post->type == "itemmc") echo ('<div class="dashicons-before dashicons-forms" style="display:inline">&nbsp;</div>');
				break;
				
			case 'item_author':
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ('item_author', $post->post_author, $basic_url), $post->user_login); 
				break;
				
				
			case 'difficulty': echo ($post->difficulty); break;
			
			case 'points': 
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ('points', $post->points, $basic_url), $post->points);
				break;
				
			case 'FW': 
				if ($post->level_FW > 0) printf ('<a href="%1$s">%2$s</a>', add_query_arg ('level_FW', $post->level_FW, $basic_url), EAL_Item::$level_label[$post->level_FW-1]);
				break;				
				
			case 'PW':
				if ($post->level_PW > 0) printf ('<a href="%1$s">%2$s</a>', add_query_arg ('level_PW', $post->level_PW, $basic_url), EAL_Item::$level_label[$post->level_PW-1]);
				break;				
				
			case 'KW':
				if ($post->level_KW > 0) printf ('<a href="%1$s">%2$s</a>', add_query_arg ('level_KW', $post->level_KW, $basic_url), EAL_Item::$level_label[$post->level_KW-1]);
				break;				
				
				
				
			case 'LO': 
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ("learnout_id", $post->learnout_id, $basic_url), $post->LOTitle); 
				printf ('<div class="row-actions">');
				printf ('<span class="edit"><a href="post.php?post_type=learnout&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->learnout_id);
				printf ('<span class="inline hide-if-no-js"></span></div>');
				break;
				
			case 'Reviews':
	
				global $wpdb;
				$sqlres = $wpdb->get_results( "
						SELECT R.id as review_id, P.post_modified as last_changed
						FROM {$wpdb->prefix}eal_review AS R
						JOIN {$wpdb->prefix}posts AS P ON (R.id = P.ID)
						WHERE R.item_id = {$post->ID}
						ORDER BY R.id
						");
	
				$c = count($sqlres); 
// 				if ($c>0) {
// 					printf ("<div class='page-title-action' onclick=\"this.nextSibling.style.display = (this.nextSibling.style.display == 'none') ? 'block' : 'none';\">%d review%s</div>", $c, $c>1 ? "s" : "");
// 					echo ("<div style='display:none'>");
// 					foreach ($sqlres as $pos => $sqlrow) {
// 						echo ("<a href='post.php?post={$sqlrow->review_id}&action=edit'>&nbsp;#" . ($pos+1) . "&nbsp;{$sqlrow->last_changed}</a><br/>");
// 					}
		
// 					echo ("</div>");
					
					/* Add New Review link is in the short actions now */
// 					echo ("<h1><a class='page-title-action' href='post-new.php?post_type={$this->type}_review&item_id={$post->ID}'>Add&nbsp;New&nbsp;Review</a></h1>");

					
					
					echo ("{$c}<div class='row-actions'>");
					if ($c>0) echo ("<span class='view'><a href='edit.php?post_type=review&item_id={$post->ID}' title='Show All Review'>Show&nbsp;All&nbsp;Reviews</a> | </span>");
					echo ("<span class='edit'><a href='post-new.php?post_type=review&item_id={$post->ID}' title='Add New Review'>Add&nbsp;New&nbsp;Review</a></span>");
					echo ("<span class='inline hide-if-no-js'></span></div>");
					break;
						
// 				}
				break;
		}
	}
	

	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array .= ", {$wpdb->prefix}eal_item.* " 
				. ", (select count(*) from {$wpdb->prefix}eal_review where {$wpdb->prefix}eal_item.id = {$wpdb->prefix}eal_review.item_id) as reviews"
				. ", {$wpdb->prefix}eal_learnout.title AS LOTitle"
				. ", {$wpdb->users}.user_login ";
		}
		return $array;
	}
	
	
	/**
	 * Join to item table; restrict to items of current domain
	 * join to learning outcome (if available) 
	 * {@inheritDoc}
	 * @see CPT_Object::WPCB_posts_join()
	 */
	
	public function WPCB_posts_join ($join) {
		global $wp_query, $wpdb;
	
		if ($wp_query->query["post_type"] == $this->type) {
			$join .= " JOIN {$wpdb->prefix}eal_item ON ({$wpdb->prefix}eal_item.id = {$wpdb->posts}.ID AND {$wpdb->prefix}eal_item.domain = '" . RoleTaxonomy::getCurrentDomain()["name"] . "')";
			$join .= " JOIN {$wpdb->users} ON ({$wpdb->users}.id = {$wpdb->posts}.post_author) ";
			$join .= " LEFT OUTER JOIN {$wpdb->prefix}eal_learnout ON ({$wpdb->prefix}eal_learnout.id = {$wpdb->prefix}eal_item.learnout_id)";
		}
		return $join;
	}
	
	

	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query, $wpdb;
	
		$orderby_statement = parent::WPCB_posts_orderby($orderby_statement);
	
		if ($wp_query->query["post_type"] == $this->type) {
			if ($wp_query->get( 'orderby' ) == "item_author") $orderby_statement = "user_login " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "LO") $orderby_statement = "LOTitle " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Difficulty") $orderby_statement = "{$wpdb->prefix}eal_item.difficulty " . $wp_query->get( 'order' );
				
			if ($wp_query->get( 'orderby' ) == "Typ") {
				$orderby_statement = "{$wpdb->prefix}eal_item.type " . $wp_query->get( 'order' );
			}
				
			
		}
	
		return $orderby_statement;
	}	
	
	public function WPCB_posts_where($where) {
	
		global $wp_query, $wpdb;
		
// 		$where = parent::WPCB_posts_where($where);
		
		if ($wp_query->query["post_type"] == $this->type) {
			if (isset($_REQUEST["learnout_id"])) {
					$where .= " AND {$wpdb->prefix}eal_learnout.id = {$_REQUEST['learnout_id']}";
			}
			
			// if all items are considered --> consider all type starting with "item"
			if ($this->type == "item") {
				$where = str_replace( "{$wpdb->posts}.post_type = 'item'", "{$wpdb->posts}.post_type LIKE 'item%'", $where); 
			}
			
			if (isset ($_REQUEST['item_author'])) 	$where .= " AND {$wpdb->posts}.post_author 			= " . $_REQUEST['item_author'];
			if (isset ($_REQUEST['points'])) 		$where .= " AND {$wpdb->prefix}eal_item.points    	= " . $_REQUEST['points'];
			if (isset ($_REQUEST['level_FW'])) 		$where .= " AND {$wpdb->prefix}eal_item.level_FW 	= " . $_REQUEST['level_FW'];
			if (isset ($_REQUEST['level_PW'])) 		$where .= " AND {$wpdb->prefix}eal_item.level_PW 	= " . $_REQUEST['level_PW'];
			if (isset ($_REQUEST['level_KW'])) 		$where .= " AND {$wpdb->prefix}eal_item.level_KW	= " . $_REQUEST['level_KW'];
			if (isset ($_REQUEST['learnout_id']))	$where .= " AND {$wpdb->prefix}eal_item.learnout_id = " . $_REQUEST['learnout_id'];
				
			
			
				
			if (isset ($_REQUEST['LO'])) {
				$where .= " AND {$wpdb->prefix}eal_item.learnout_id = " . $_REQUEST['LO'];
			}
			
		}
	
		
		
		return $where;
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
	
	
	
	static function CPT_contextual_help( $contextual_help, $screen_id, $screen ) {
	
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