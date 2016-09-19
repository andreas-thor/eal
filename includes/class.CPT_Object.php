<?php

abstract class CPT_Object {
	
	
	public $type;
	public $label;
	public $menu_pos;
	
	public $table_columns;	// to be set by sub-classes
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {
		
		register_post_type( $this->type,
			array_merge (
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
		
// 					'capabilities' => array(
// 						'edit_posts' => ($this->type == 'item') ? 'do_not_allow' : 'edit_posts', // false < WP 4.5, credit @Ewout
// 					), 
						
// 				'capabilities' => array(
// 					'publish_posts' => 'publish_{$this->type}s',
// 					'edit_posts' => 'edit_{$this->type}s',
// 					'edit_others_posts' => 'edit_others_{$this->type}s',
// 					'delete_posts' => 'delete_{$this->type}s',
// 					'delete_others_posts' => 'delete_others_{$this->type}s',
// 					'read_private_posts' => 'read_private_{$this->type}s',
// 					'edit_post' => 'edit_{$this->type}',
// 					'delete_post' => 'delete_{$this->type}',
// 					'read_post' => 'read_{$this->type}'
// 				),
						
					'public' => false,
					'menu_position' => $this->menu_pos,
					'menu_icon' => 'dashicons-list-view', // dashicons-welcome-learn-more', 
					'supports' => array( 'title', 'revisions'), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
					'taxonomies' => array( 'topic' ),
					'has_archive' => false, // false to allow for single view
					'show_ui' => true,
					'show_in_menu'    => $this->menu_pos > 0,
					'register_meta_box_cb' => array ($this, 'WPCB_register_meta_box_cb')
				), 
				$args
			)
		);

		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$this->type}", array ("eal_{$this->type}", 'save'), 10, 2);
		
		
		// Manage table of items (what columns to show; what columns are sortable
		add_filter("manage_{$this->type}_posts_columns" , array ($this, 'WPCB_manage_posts_columns'));
		add_filter("manage_edit-{$this->type}_sortable_columns", array ($this, 'WPCB_manage_edit_sortable_columns'));
		add_action("manage_{$this->type}_posts_custom_column" , array ($this, 'WPCB_manage_posts_custom_column'), 10, 2 );
		
		// Generate databses query to retrieve all data
		add_filter('posts_join', array ($this, 'WPCB_posts_join'));
		add_filter('posts_fields', array ($this, 'WPCB_posts_fields'), 10, 1 );
		add_filter('posts_orderby', array ($this, 'WPCB_posts_orderby'), 10, 1 );
		add_filter('posts_where', array ($this, 'WPCB_posts_where'), 10, 1 );
		
		add_filter('post_row_actions', array ($this , 'WPCB_post_row_actions'), 10, 2);
		
		add_action( 'restrict_manage_posts', array ($this, 'WPCB_restrict_manage_posts') );
		
		add_action('admin_footer-edit.php', array ($this, 'add_bulk_actions'));
		add_action('load-edit.php', array ($this, 'custom_bulk_action'));
		
		
		
	}		
	
	
	public function WPCB_post_row_actions($actions, $post){
	
		unset ($actions['view']);
		unset ($actions['edit']);
		unset ($actions['inline hide-if-no-js']);
		return $actions;
	
		// 		if ($post->post_type != $this->type) return $actions;
	
		// 		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
		// 		$actions['view'] = "<a href='admin.php?page=view&itemid={$post->ID}'>View</a>"; // add "View"
	
		// 		if (!RoleTaxonomy::canEditItemPost($post)) {		// "Edit" & "Trash" only if editable by user
		// 			unset ($actions['edit']);
		// 			unset ($actions['trash']);
		// 		}
	
		// 		return $actions;
	}
	

	function custom_bulk_action() {
	
	
		if ($_REQUEST["post_type"] != $this->type) return;
	
		global $wpdb;
	
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
	
	
		if ($wp_list_table->current_action() == 'view') {
		
			if (substr ($_REQUEST['post_type'], 0, 4) == 'item') {
				$sendback = add_query_arg( 'itemids', $_REQUEST['post'], 'edit.php?page=view&post_type=itembasket' );
				wp_redirect($sendback);
				exit();
			}
			
			if ($_REQUEST['post_type'] == 'learnout') {
				$sendback = add_query_arg( 'learnoutids', $_REQUEST['post'], 'edit.php?page=view&post_type=itembasket' );
				wp_redirect($sendback);
				exit();
			}

			if ($_REQUEST['post_type'] == 'review') {
				$sendback = add_query_arg( 'reviewids', $_REQUEST['post'], 'edit.php?page=view&post_type=itembasket' );
				wp_redirect($sendback);
				exit();
			}
				
		}
	
		
		/* Add Items to Basket */
		if ($wp_list_table->current_action() == 'add_to_basket') {

			/* get array of postids */
			$postids = $_REQUEST['post'];
			if (!is_array($postids)) $postids = [$postids];

			$basket_old = get_user_meta(get_current_user_id(), 'itembasket', true);
			if ($basket_old == null) $basket_old = array();
			if (count($basket_old) == 0) $basket_old = [-1];	// dummy basket to make sure SQLL works
			
			/* get Items from Learning Outcomes */
			$sql  = "SELECT P.id FROM {$wpdb->prefix}eal_item I JOIN {$wpdb->prefix}posts P ON (P.ID = I.ID) WHERE P.post_parent = 0 AND ";
			$sql .= sprintf('( %1$s IN (%2$s) OR I.id IN (%3$s) )',  ($_REQUEST['post_type']=='learnout') ? 'I.learnout_id' : 'I.id', join(", ", $postids), join(", ", $basket_old));
			$itemids = $wpdb->get_col ($sql);
	
			$x = update_user_meta( get_current_user_id(), 'itembasket', $itemids);
	
	
		}
	
	}		
	
	
	
	
	public function WPCB_mb_editor ($post, $vars) {
		
		$editor_settings = array(
				'media_buttons' => true,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
		
		// TODO: HTML Layout geht verloren!!! mit oder ohne???
		echo (wp_editor($vars['args']['value'] , $vars['args']['name'], $editor_settings ));
// 		echo (wp_editor(wpautop(stripslashes($vars['args']['value'])) , $vars['args']['name'], $editor_settings ));
	}
	
	
	public function WPCB_mb_level ($post, $vars) {
	
		$level = $vars['args']['level'];
		$prefix = isset ($vars['args']['prefix']) ? $vars['args']['prefix'] : "item"; 
		
		$default = isset ($vars['args']['default']) ? $vars['args']['default'] : array ("FW"=>0, "KW"=>0, "PW"=>0);	// default ("expected") levels
		$disabled = isset ($vars['args']['disabled']) ? $vars['args']['disabled'] : "";		// disable change
		$callback = isset ($vars['args']['callback']) ? $vars['args']['callback'] : "";		// callback javascript function
		$background = isset ($vars['args']['background']) ? $vars['args']['background'] : 0;	// show default with different background color
		$print = isset ($vars['args']['print']) ? $vars['args']['print'] : 1;	// echo/print HTML code; return otherwise
?>
		<script>
			
			function disableOtherLevels (e) {
	 			var j = jQuery.noConflict();
				// uncheck all other radio input in the table
				j(e).parent().parent().parent().parent().find("input").each ( function () {
 					if (e.id != this.id) this.checked = false;
				});
			}
		</script>
<?php
		
	
		$res = "<table style='font-size:100%'><tr><td></td>";
		
		foreach ($level as $c => $v) {
			$res .= sprintf ('<td>%s</td>', $c);
		}
		
		$res .= sprintf ('</tr>');
		
		foreach (EAL_Item::$level_label as $n => $r) {	// n=0..5, $r=Erinnern...Erschaffen 
			$res .= sprintf ('<tr><td>%d. %s</td>', $n+1, $r);
			foreach ($level as $c=>$v) {	// c=FW,KW,PW; v=1..6
				$bgcolor = (($default[$c]==$n+1) && ($background==1)) ? '#E0E0E0' : 'transparent'; 
				$res .= sprintf ("<td valign='bottom' align='left' style='padding:3px; padding-left:5px; background-color:%s'>", $bgcolor);
				$res .= sprintf ("<input type='radio' id='%s' name='%s' value='%d' %s onclick=\"disableOtherLevels(this);",
					"{$prefix}_level_{$c}_{$r}", "{$prefix}_level_{$c}", $n+1, (($v==$n+1)?'checked':$disabled)); 	
				
				if ($callback != "") {
					$res .= sprintf ("%s (this, %d, '%s', %d, 's');",
						$callback, $n+1, EAL_Item::$level_label[$n], $default[$c], (($default[$c]>0) ? EAL_Item::$level_label[$default[$c]-1] : ""));
				}
				$res .= sprintf ("\"></td>"); 
					 
			}
			$res .= sprintf ('</tr>');
		}
		$res .= sprintf ('</table>');
		
		
		if ($print==1) {
			echo $res;
		} else {
			return $res;
		}
// 		echo $html;
	}
	
	
	
	/** TODO: Check if editable (based on user rights); if post_status = trash --> not editable
	 * @param unknown $column
	 * @param unknown $post_id
	 */

	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
		global $post;
	
		$basic_url = remove_query_arg (array ("item_author", "review_author", "learnout_author", "item_points", "level_FW", "level_KW", "level_PW", "learnout_id"));
	
		switch ( $column ) {
				
			case 'item_title':
				printf ($post->item_title);
				if ($post->post_status == "draft") echo (' &mdash; <span class="post-state"><i>Draft</i></span>');
				if ($post->post_status == "pending") echo (' &mdash; <span class="post-state"><b>Pending</b></span>');
				
				printf ('<div class="row-actions">');
				printf ('<span class="view"><a href="admin.php?page=view&itemid=%1$d" title="View">View</a></span>', $post->ID);
				printf ('<span class="edit"> | <a href="post.php?post_type=item&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->ID);
				printf (' | <span class="inline hide-if-no-js"></span></div>');
				
				
				break;
				
			case 'review_title':
				printf ('<a href="%1$s">[%2$s]</a>', add_query_arg ('item_id', $post->item_id, $basic_url), $post->item_title);
				if ($post->post_status == "draft") echo (' &mdash; <span class="post-state"><i>Draft</i></span>');
				printf ('<div class="row-actions">');
				printf ('<span class="view"><a href="admin.php?page=view&reviewid=%1$d" title="View">View</a></span>', $post->ID);
				printf ('<span class="edit"> | <a href="post.php?post_type=review&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->ID);
				printf (' | <span class="inline hide-if-no-js"></span></div>');
				break;
				
			case 'learnout_title':
				printf ($post->learnout_title);
				if ($post->post_status == "draft") echo (' &mdash; <span class="post-state"><i>Draft</i></span>');
				printf ('<div class="row-actions">');
				printf ('<span class="view"><a href="admin.php?page=view&learnoutid=%1$d" title="View">View</a></span>', $post->ID);
				printf ('<span class="edit"> | <a href="post.php?post_type=learnout&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->ID);
				printf (' | <span class="inline hide-if-no-js"></span></div>');
				break;
			
			case 'item_type':
				if ($post->item_type == "itemsc") echo ('<div class="dashicons-before dashicons-marker" style="display:inline">&nbsp;</div>');
				if ($post->item_type == "itemmc") echo ('<div class="dashicons-before dashicons-forms" style="display:inline">&nbsp;</div>');
				break;
	
			case 'item_author':
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ('item_author', $post->item_author_id, $basic_url), $post->item_author);
				break;
	
			case 'review_author':
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ('review_author', $post->review_author_id, $basic_url), $post->review_author);
				break;

			case 'learnout_author':
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ('learnout_author', $post->learnout_author_id, $basic_url), $post->learnout_author);
				break;
					
			case 'difficulty': echo ($post->difficulty); break;
				
			case 'item_points':
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ('item_points', $post->item_points, $basic_url), $post->item_points);
				break;
	
			case 'level_FW':
				if ($post->level_FW > 0) printf ('<a href="%1$s">%2$s</a>', add_query_arg ('level_FW', $post->level_FW, $basic_url), EAL_Item::$level_label[$post->level_FW-1]);
				break;
	
			case 'level_PW':
				if ($post->level_PW > 0) printf ('<a href="%1$s">%2$s</a>', add_query_arg ('level_PW', $post->level_PW, $basic_url), EAL_Item::$level_label[$post->level_PW-1]);
				break;
	
			case 'level_KW':
				if ($post->level_KW > 0) printf ('<a href="%1$s">%2$s</a>', add_query_arg ('level_KW', $post->level_KW, $basic_url), EAL_Item::$level_label[$post->level_KW-1]);
				break;
	
			case 'item_learnout':
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ("learnout_id", $post->learnout_id, $basic_url), $post->learnout_title);
				printf ('<div class="row-actions">');
				printf ('<span class="view"><a href="admin.php?page=view&learnoutid=%1$d" title="View">View</a></span>', $post->learnout_id);
				printf (' | <span class="edit"><a href="post.php?post_type=learnout&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->learnout_id);
				printf ('<span class="inline hide-if-no-js"></span></div>');
				break;
	
			case 'no_of_reviews':
				echo ("{$post->no_of_reviews}<div class='row-actions'>");
				if ($post->no_of_reviews>0) echo ("<span class='view'><a href='edit.php?post_type=review&item_id={$post->ID}' title='Show All Review'>Show&nbsp;All&nbsp;Reviews</a> | </span>");
				echo ("<span class='edit'><a href='post-new.php?post_type=review&item_id={$post->ID}' title='Add New Review'>Add&nbsp;New&nbsp;Review</a></span>");
				echo ("<span class='inline hide-if-no-js'></span></div>");
				break;
				
			case 'no_of_items':
				echo ("{$post->no_of_items}<div class='row-actions'>");
				if ($post->no_of_items>0) echo ("<span class='view'><a href='edit.php?post_type=item&learnout_id={$post->ID}' title='Show All Items'>Show&nbsp;All&nbsp;Items</a> | </span>");
				echo ("<span class='edit'><a href='post-new.php?post_type=itemsc&learnout_id={$post->ID}' title='Add New SC'>Add New SC</a> | </span>");
				echo ("<span class='edit'><a href='post-new.php?post_type=itemmc&learnout_id={$post->ID}' title='Add New MC'>Add New MC</a></span>");
				echo ("<span class='inline hide-if-no-js'></span></div>");
				break;
				
			case 'overall': switch ($post->overall) {
				case 1: echo ('<div class="dashicons-before dashicons-yes" style="display:inline">&nbsp;</div>'); break;
				case 2: echo ('<div class="dashicons-before dashicons-flag" style="display:inline">&nbsp;</div>'); break;
				case 3: echo ('<div class="dashicons-before dashicons-no-alt" style="display:inline">&nbsp;</div>'); break;
			} break;
				
			case 'score':
				if (($post->description_correctness == 1) && ($post->description_relevance == 1) && ($post->description_wording == 1)) {
					echo ('<div class="dashicons-before dashicons-star-filled" style="display:inline">&nbsp;</div>');
				} else {
					if (($post->description_correctness < 3) && ($post->description_relevance < 3) && ($post->description_wording < 3)) {
						echo ('<div class="dashicons-before dashicons-star-half" style="display:inline">&nbsp;</div>');
					} else {
						echo ('<div class="dashicons-before dashicons-star-empty" style="display:inline">&nbsp;</div>');
					}
				}
			
				if (($post->question_correctness == 1) && ($post->question_relevance == 1) && ($post->question_wording == 1)) {
					echo ('<div class="dashicons-before dashicons-star-filled" style="display:inline">&nbsp;</div>');
				} else {
					if (($post->question_correctness < 3) && ($post->question_relevance < 3) && ($post->question_wording < 3)) {
						echo ('<div class="dashicons-before dashicons-star-half" style="display:inline">&nbsp;</div>');
					} else {
						echo ('<div class="dashicons-before dashicons-star-empty" style="display:inline">&nbsp;</div>');
					}
				}
			
				if (($post->answers_correctness == 1) && ($post->answers_relevance == 1) && ($post->answers_wording == 1)) {
					echo ('<div class="dashicons-before dashicons-star-filled" style="display:inline">&nbsp;</div>');
				} else {
					if (($post->answers_correctness < 3) && ($post->answers_relevance < 3) && ($post->answers_wording < 3)) {
						echo ('<div class="dashicons-before dashicons-star-half" style="display:inline">&nbsp;</div>');
					} else {
						echo ('<div class="dashicons-before dashicons-star-empty" style="display:inline">&nbsp;</div>');
					}
				}
				break;
			
			case 'change_level':
				if ($post->change_level > 0) echo ('<div class="dashicons-before dashicons-warning" style="display:inline">&nbsp;</div>');
				break;				
	
		}
	}
	
	
	public function WPCB_manage_posts_columns($columns) {
		return $this->table_columns;
// 		return array(
// 				'cb' => '<input type="checkbox" />',
// 				'item_title' => 'Title',
// 				'date' => 'Date',
// 				'item_type' => 'Type',
// 				'item_author' => 'Author',
// 				'points' => 'Points',
// 				'level_FW' => 'FW',
// 				'level_KW' => 'KW',
// 				'level_PW' => 'PW',
// 				'no_of_reviews' => 'Reviews',
// 				'item_learnout' => 'Learn. Out.',
// 				'difficulty' => 'Difficulty');
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		$sortable_columns = $this->table_columns;
		unset ($sortable_columns['cb']);
		return $sortable_columns;
// 		return array(
// 				'item_title' => 'Title',
// 				'date' => 'Date',
// 				'item_type' => 'Type',
// 				'item_author' => 'Author',
// 				'points' => 'Points',
// 				'level_FW' => 'FW',
// 				'level_KW' => 'KW',
// 				'level_PW' => 'PW',
// 				'no_of_reviews' => 'Reviews',
// 				'item_learnout' => 'Learn. Out.',
// 				'difficulty' => 'Difficulty');
	}	
	
// 	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
// 		global $post;
	
// 		switch ( $column ) {
// 			case 'FW': echo (($post->level_FW > 0) ? EAL_Item::$level_label[$post->level_FW-1] : ''); break;
// 			case 'PW': echo (($post->level_PW > 0) ? EAL_Item::$level_label[$post->level_PW-1] : ''); break;
// 			case 'KW': echo (($post->level_KW > 0) ? EAL_Item::$level_label[$post->level_KW-1] : ''); break;
// 		}
// 	}



	public function getTopicTerm ($term, $level) {
	
		// 		$result = str_repeat ("&nbsp;", $level*2) . "+ " . $term->name;
		$result = "&nbsp;&gt;&gt;&nbsp;" . $term->name;
		foreach (get_terms ('topic', array ('parent'=> $term->term_id)) as $t) {
			$result .= /*"<br/>" . */ $this->getTopicTerm ($t, $level+1);
		}
		return $result;
	}
	
	
	

	

	
	


	
	
	
	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query;
		if ($wp_query->query["post_type"] == $this->type) {
			if ($wp_query->get( 'orderby' ) == "FW") $orderby_statement = "level_FW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "PW") $orderby_statement = "level_PW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "KW") $orderby_statement = "level_KW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Reviews") $orderby_statement = "reviews " . $wp_query->get( 'order' );
		}
	
		// 		$orderby_statement = "level_KW DESC";
		return $orderby_statement;
	}
	
	
	public function WPCB_posts_where($where) {
	
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			foreach (EAL_Item::$level_type as $lt) {
				if (isset($_REQUEST["level_{$lt}"]) && ($_REQUEST["level_{$lt}"] != '0')) {
					$where .= " AND ({$wpdb->prefix}eal_{$this->type}.level_{$lt} = {$_REQUEST["level_{$lt}"]})";
				}
			}
		}
		
		return $where;
	}
	
	
	public function WPCB_restrict_manage_posts() {
	
		global $typenow, $wp_query;
	
		// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array('topic');
	
		// must set this to the post type you want the filter(s) displayed on
		if( $typenow == $this->type ){
	
			wp_dropdown_categories(array(
					'show_option_all' =>  __("Show All Topics"),
					'taxonomy'        =>  'topic',
					'name'            =>  'topic',
					'orderby'         =>  'name',
					'selected'        =>  isset($wp_query->query['topic']) ? $wp_query->query['topic'] :'',
					'hierarchical'    =>  true,
					'depth'           =>  0,
					'value_field'	  =>  'slug',
					'show_count'      =>  true, // Show # listings in parens
					'hide_empty'      =>  false, // Don't show businesses w/o listings
			));
				
			
			foreach (EAL_Item::$level_type as $lt) {
				$selected = (isset($_REQUEST["level_{$lt}"]) && ($_REQUEST["level_{$lt}"] != '0')) ? $_REQUEST["level_{$lt}"] : 0;
				echo ("<select class='postform' name='level_{$lt}'>");
				echo ("<option value='0'>All {$lt}</option>");
				foreach(array (1, 2, 3, 4, 5, 6) as $v) {
					echo ("<option value='${v}' " . (($v==$selected)?'selected':'') . ">" . EAL_Item::$level_label[$v-1] . "</option>");
				}
				echo ("</select>");
			}
		}
	}
	
	
	
}

?>