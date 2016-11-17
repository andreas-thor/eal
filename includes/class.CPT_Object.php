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
		
		
		if (($this->type == "learnout") || ($this->type == "review")) {
			$cap_type = $this->type;
		} else {
			$cap_type = "item";
		}
		
		
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
						
					'capabilities' => array(
						"edit_posts" => "edit_{$cap_type}s",
						"edit_others_posts" => "edit_others_{$cap_type}s",
						"edit_published_posts" => "edit_published_{$cap_type}s",
						"edit_private_posts" => "edit_private_{$cap_type}s",
						"publish_posts" => "publish_{$cap_type}s",
						"delete_posts" => "delete_{$cap_type}s",
						"delete_others_posts" => "delete_others_{$cap_type}s",
						"delete_published_posts" => "delete_published_{$cap_type}s",
						"delete_private_posts" => "delete_private_{$cap_type}s",
						"read_private_posts" => "read_private_{$cap_type}s",
						"edit_post" => "edit_{$cap_type}",
						"delete_post" => "delete_{$cap_type}",
						"read_post" => "read_{$cap_type}"
					),
					'map_meta_cap' => true,	// http://wordpress.stackexchange.com/questions/108338/capabilities-and-custom-post-types
						
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
		add_filter('months_dropdown_results', '__return_empty_array');	// TODO: Implement Date Filter for All Items (currently for none, because it only works for real item types)
		
		add_action('admin_footer-edit.php', array ($this, 'add_bulk_actions'));
		add_action('load-edit.php', array ($this, 'custom_bulk_action'));
		
		add_filter( 'wp_count_posts', array ($this, 'WPCB_count_posts'), 10, 3);
		
		add_filter( "views_edit-{$this->type}", function( $views )
		{
			$remove_views = [ 'all','publish','future','sticky','draft','pending','trash','mine' ];
			foreach( (array) $remove_views as $view ) {
				if (isset( $views[$view] )) unset( $views[$view] );
			}
			return $views;
		} );
		
		// quick edit is currently not supported
		add_action( 'quick_edit_custom_box', array ($this , 'WPCB_quick_edit_custom_box'), 10, 2 );
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
		
		if (($wp_list_table->current_action() == 'mark') || ($wp_list_table->current_action() == 'unmark')) {
			if (substr ($_REQUEST['post_type'], 0, 4) == 'item') {
				
				/* get array of postids */
				$postids = $_REQUEST['post'];
				if (!is_array($postids)) $postids = [$postids];
				if (count ($postids)>0) {
					$sql = sprintf ("UPDATE {$wpdb->prefix}eal_item SET flag = %d WHERE id IN (%s)", ($wp_list_table->current_action() == 'mark') ? 1 : 0, join (",", $postids));
					$wpdb->query ($sql);
				}
				
			}
		}
		
		if (($wp_list_table->current_action() == 'setpublished') || ($wp_list_table->current_action() == 'setpending') || ($wp_list_table->current_action() == 'setdraft')) {
			
			$status = "publish";
			if ($wp_list_table->current_action() == 'setpending') $status = "pending";
			if ($wp_list_table->current_action() == 'setdraft') $status = "draft";
			
			/* get array of postids */
			$postids = $_REQUEST['post'];
			if (!is_array($postids)) $postids = [$postids];
			if (count ($postids)>0) {
				$sql = sprintf ("UPDATE {$wpdb->posts} SET post_status = '%s' WHERE id IN (%s)", $status, join (",", $postids));
				$wpdb->query ($sql);
			}
		}
		
		
	
		
		/* Add Items to Basket */
		if ($wp_list_table->current_action() == 'add_to_basket') {

			/* get array of postids */
			$postids = $_REQUEST['post'];
			if (!is_array($postids)) $postids = [$postids];

			$basket_old = RoleTaxonomy::getCurrentBasket(); // get_user_meta(get_current_user_id(), 'itembasket', true);
			if ($basket_old == null) $basket_old = array();
			if (count($basket_old) == 0) $basket_old = [-1];	// dummy basket to make sure SQLL works
			
			/* get Items from Learning Outcomes */
			$sql  = "SELECT P.id FROM {$wpdb->prefix}eal_item I JOIN {$wpdb->prefix}posts P ON (P.ID = I.ID) WHERE P.post_parent = 0 AND ";
			$sql .= sprintf('( %1$s IN (%2$s) OR I.id IN (%3$s) )',  ($_REQUEST['post_type']=='learnout') ? 'I.learnout_id' : 'I.id', join(", ", $postids), join(", ", $basket_old));
			$itemids = $wpdb->get_col ($sql);
	
			RoleTaxonomy::setCurrentBasket($itemids); // $x = update_user_meta( get_current_user_id(), 'itembasket', $itemids);
	
	
		}
		
		
		if ($wp_list_table->current_action() == 'remove_from_basket') {
				
			$b_old = RoleTaxonomy::getCurrentBasket(); // get_user_meta(get_current_user_id(), 'itembasket', true);
			$b_new = $b_old;
		
			if (isset($_REQUEST["post"])) {
				$b_new = array_diff ($b_old, $_REQUEST['post']);
			}
			if ($_REQUEST['itemid']!=null) {
				$b_new = array_diff ($b_old, [$_REQUEST['itemid']]);
			}
			if ($_REQUEST['itemids']!=null) {
				$b_new = array_diff ($b_old, $_REQUEST['itemids']);
			}
			RoleTaxonomy::setCurrentBasket($b_new); // $x = update_user_meta( get_current_user_id(), 'itembasket', $b_new, $b_old );
		
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
// 		echo (wp_editor($vars['args']['value'] , $vars['args']['name'], $editor_settings ));
		echo (wp_editor(wpautop(stripslashes($vars['args']['value'])) , $vars['args']['name'], $editor_settings ));
	}
	
	
	public static function getLevelHTML ($prefix, $level, $default, $disabled, $background, $callback) {
		
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
		
		foreach ($level as $c => $v) $res .= sprintf ('<td>%s</td>', $c);
		
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
		
		return $res;
	}
	
	
	

	
	
	
	/** TODO: Check if editable (based on user rights); if post_status = trash --> not editable
	 * @param unknown $column
	 * @param unknown $post_id
	 */

	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
		global $post;
	
		// "s" is search string
		$basic_url = remove_query_arg (array ("s", "item_type", "item_author", "review_author", "learnout_author", "item_points", "taxonomy", "level_FW", "level_KW", "level_PW", "learnout_id", "post_status", "flag"));
	
		
		switch ( $column ) {
				
			case 'id': echo ($post->ID); break;
			
			case 'item_id': 
				printf ('<a href="%1$s">%2$s</a>', add_query_arg ('item_id', $post->item_id, $basic_url), $post->item_id);
				printf ("<div class='row-actions'><span class='view'><a href='admin.php?page=view&itemid=%s' title='View'>View</a></span><span class='inline hide-if-no-js'></span></div>", $post->item_id);
				break;
				
			case 'note': echo ($post->note); break;
			case 'flag': if ($post->flag == 1) 
				printf ('<a href="%1$s"><span class="dashicons dashicons-yes">&nbsp</span></a>', add_query_arg ('flag', 1, $basic_url));
				break;
			
				
			case 'last_modified': echo (get_post_modified_time(get_option('date_format') . ' ' . get_option('time_format'), false, $post, true)); break; 
			
			case 'item_title':
				printf ($post->item_title);
				if ($post->post_status == "draft") echo (' &mdash; <span class="post-state"><i>Draft</i></span>');
				if ($post->post_status == "pending") echo (' &mdash; <span class="post-state"><b>Pending</b></span>');
				
// 				printf ('<div class="row-actions">');
// 				printf ('<span class="view"><a href="admin.php?page=view&itemid=%1$d" title="View">View</a></span>', $post->ID);
				
// 				if (RoleTaxonomy::canEditItemPost($post)) {
// 					printf ('<span class="edit"> | <a href="post.php?post_type=item&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->ID);
// 				}
// 				printf (' | <span class="inline hide-if-no-js"></span></div>');
				
				
				break;
				
			case 'review_title':
// 				printf ('<a href="%1$s">[%2$s]</a>', add_query_arg ('item_id', $post->item_id, $basic_url), $post->item_title);
				printf ('[%s]', $post->item_title);
				if ($post->post_status == "draft") echo (' &mdash; <span class="post-state"><i>Draft</i></span>');
// 				printf ('<div class="row-actions">');
// 				printf ('<span class="view"><a href="admin.php?page=view&reviewid=%1$d" title="View">View</a></span>', $post->ID);
// 				printf ('<span class="edit"> | <a href="post.php?post_type=review&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->ID);
// 				printf (' | <span class="inline hide-if-no-js"></span></div>');
				break;
				
			case 'learnout_title':
				printf ($post->learnout_title);
				if ($post->post_status == "draft") echo (' &mdash; <span class="post-state"><i>Draft</i></span>');
// 				printf ('<div class="row-actions">');
// 				printf ('<span class="view"><a href="admin.php?page=view&learnoutid=%1$d" title="View">View</a></span>', $post->ID);
// 				printf ('<span class="edit"> | <a href="post.php?post_type=learnout&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->ID);
// 				printf (' | <span class="inline hide-if-no-js"></span></div>');
				break;
			
			case 'item_type':
				if ($post->item_type == "itemsc") printf ('<a href="%1$s"><div class="dashicons-before dashicons-marker" style="display:inline">&nbsp;</div></a>', add_query_arg ('item_type', "itemsc", $basic_url));
				if ($post->item_type == "itemmc") printf ('<a href="%1$s"><div class="dashicons-before dashicons-forms"  style="display:inline">&nbsp;</div></a>', add_query_arg ('item_type', "itemmc", $basic_url));
				break;
	
			case 'taxonomy':
				foreach (wp_get_post_terms($post->ID, RoleTaxonomy::getCurrentRoleDomain()["name"]) as $term) {
					printf ('<a href="%1$s">%2$s</a><br/>', add_query_arg ('taxonomy', $term->term_id , $basic_url), $term->name);
				}
				
				
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
				if ($post->learnout_id > 0) {
					printf ('<div class="row-actions">');
					printf ('<span class="view"><a href="admin.php?page=view&learnoutid=%1$d" title="View">View</a></span>', $post->learnout_id);
					printf (' | <span class="edit"><a href="post.php?post_type=learnout&post=%1$d&action=edit" title="Edit">Edit</a></span>', $post->learnout_id);
					printf ('<span class="inline hide-if-no-js"></span></div>');
				}
				break;
	
			case 'no_of_reviews':
				echo ("{$post->no_of_reviews}<div class='row-actions'>");
				if ($post->no_of_reviews>0) echo ("<span class='view'><a href='edit.php?post_type=review&item_id={$post->ID}' title='Show All Review'>List</a> | </span>");
				echo ("<span class='edit'><a href='post-new.php?post_type=review&item_id={$post->ID}' title='Add New Review'>Add</a></span>");
				echo ("<span class='inline hide-if-no-js'></span></div>");
				break;
				
			case 'no_of_items':
				echo ("{$post->no_of_items}<div class='row-actions'>");
				if ($post->no_of_items>0) echo ("<span class='view'><a href='edit.php?post_type=item&learnout_id={$post->ID}' title='List All Items'>List</a> | </span>");
				echo ("<span class='edit'><a href='post-new.php?post_type=itemsc&learnout_id={$post->ID}' title='Add New SC'>Add SC</a> | </span>");
				echo ("<span class='edit'><a href='post-new.php?post_type=itemmc&learnout_id={$post->ID}' title='Add New MC'>Add MC</a></span>");
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
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		$sortable_columns = $this->table_columns;
		unset ($sortable_columns['cb']);
		unset ($sortable_columns['taxonomy']);
		return $sortable_columns;
	}	
	




	public function getTopicTerm ($term, $level) {
	
		// 		$result = str_repeat ("&nbsp;", $level*2) . "+ " . $term->name;
		$result = "&nbsp;&gt;&gt;&nbsp;" . $term->name;
		foreach (get_terms ('topic', array ('parent'=> $term->term_id)) as $t) {
			$result .= /*"<br/>" . */ $this->getTopicTerm ($t, $level+1);
		}
		return $result;
	}
	
	
	
// 	public function WPCB_posts_orderby($orderby_statement) {
	
// 		global $wp_query;
// 		if ($wp_query->query["post_type"] == $this->type) {
// 			if ($wp_query->get( 'orderby' ) == "FW") $orderby_statement = "level_FW " . $wp_query->get( 'order' );
// 			if ($wp_query->get( 'orderby' ) == "PW") $orderby_statement = "level_PW " . $wp_query->get( 'order' );
// 			if ($wp_query->get( 'orderby' ) == "KW") $orderby_statement = "level_KW " . $wp_query->get( 'order' );
// 			if ($wp_query->get( 'orderby' ) == "Reviews") $orderby_statement = "reviews " . $wp_query->get( 'order' );
// 			if ($wp_query->get('orderby') == $this->table_columns['last_modified'])		$orderby_statement = "{$wpdb->posts}.post_modified {$wp_query->get('order')}";
// 		}
	
// 		// 		$orderby_statement = "level_KW DESC";
// 		return $orderby_statement;
// 	}
	
	
	public function WPCB_posts_where($where, $checktype = TRUE) {
	
		global $wp_query, $wpdb;
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			foreach (EAL_Item::$level_type as $lt) {
				if (isset($_REQUEST["level_{$lt}"]) && ($_REQUEST["level_{$lt}"] != '0')) {
					$where .= " AND ({$wpdb->prefix}eal_{$this->type}.level_{$lt} = {$_REQUEST["level_{$lt}"]})";
				}
			}
		}
		
		return $where;
	}
	
	

	public function WPCB_count_posts( $counts, $type, $perm) {
		global $wpdb;
	
		if ($type != $this->type) return $counts;
	
		$query  = "SELECT {$wpdb->posts}.post_status, COUNT( * ) AS num_posts ";
		$query .= $this->WPCB_posts_join  (" FROM {$wpdb->posts} ", FALSE);
		$query .= $this->WPCB_posts_where (" WHERE {$wpdb->posts}.post_type = '{$type}' ", FALSE);
		$query .= " GROUP BY {$wpdb->posts}.post_status";
	
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		$counts = array_fill_keys( get_post_stati(), 0 );
		foreach ( $results as $row ) {
			$counts[ $row['post_status'] ] = $row['num_posts'];
		}
		$counts ['mine'] = 7;
		return (object) $counts;
	}

	
	
	
	public function WPCB_restrict_manage_posts() {
	
		global $typenow, $wp_query;
	
		// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array(RoleTaxonomy::getCurrentRoleDomain()["name"]);
	
		// must set this to the post type you want the filter(s) displayed on
		if( $typenow == $this->type ){

			if (($this->type == "item") || ($this->type == "itembasket")) { 
				$selected = isset($_REQUEST["item_type"]) ? $_REQUEST["item_type"] : "0";
				printf ('<select class="postform" name="item_type">');
				printf ('<option value="0" %1$s>All Item Types</option>', 		($selected=="0") ? "selected" : "");
				printf ('<option value="itemsc" %1$s>Single Choice</option>', 	($selected=="itemsc") ? "selected" : "");
				printf ('<option value="itemmc" %1$s>Multiple Choice</option>', ($selected=="itemmc") ? "selected" : "");
				printf ('</select>');
				
				$selected = isset($_REQUEST["post_status"]) ? $_REQUEST["post_status"] : "0";
				printf ('<select class="postform" name="post_status">');
				printf ('<option value="0" %1$s>All Item Statuses</option>', 		($selected=="0") ? "selected" : "");
				printf ('<option value="draft" %1$s>Draft</option>', 		($selected=="draft") ? "selected" : "");
				printf ('<option value="pending" %1$s>Pending</option>', ($selected=="pending") ? "selected" : "");
				printf ('<option value="publish" %1$s>Published</option>', ($selected=="publish") ? "selected" : "");
				printf ('</select>');
			}
			
			wp_dropdown_categories(array(
					'show_option_all' =>  __("Show All Topics"),
					'taxonomy'        =>  RoleTaxonomy::getCurrentRoleDomain()["name"],
					'name'            =>  'taxonomy',
					'orderby'         =>  'name',
					'selected'        =>  isset($wp_query->query['taxonomy']) ? $wp_query->query['taxonomy'] :'',
					'hierarchical'    =>  true,
					'depth'           =>  0,
					'value_field'	  =>  'term_id',
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
			
			if (substr ($this->type, 0, 4) == 'item') {
				
				$flag == 0;
				if (isset ($_REQUEST["flag"])) $flag = $_REQUEST["flag"];
				
				printf ("<select class='postform' name='flag'>");
				printf ("<option value='0' %s>All Flags</option>", ($flag==0) ? "selected" : "");
				printf ("<option value='1' %s>Marked</option>", ($flag==1) ? "selected" : "");
				printf ("<option value='2' %s>Unmarked</option>", ($flag==2) ? "selected" : "");
				printf ("</select>");
			}
			
		}
	}
	
	// currently not used
	public function WPCB_quick_edit_custom_box($column_name, $post_type) {
	
		if ($post_type != $this->type) return;
		
		if ($column_name == "item_title") {

			global $post, $item;
					
// 			post_categories_meta_box( $post, array ("id" => "WPCB_mb_taxonomy", "title" => "", "args" => array ( "taxonomy" => $item->domain )) );
			
			printf ("<fieldset class='inline-edit-col-left'><div class='inline-edit-group'>");
// 			printf ("<label><span class='title'>Flag</span><input type='checkbox' name='item_flag' id='item_flag_id' value='1' %s/></label>", $item->flag==1 ? "checked" : ""); 
			printf ("<label><span class='title'>Title</span><span class='input-text-wrap'><input type='text' name='post_title' class='ptitle' value=''></span></label>");
			printf ("</div></fieldset>");
		            
// 			printf ("<fieldset class='inline-edit-col-right'><div class='inline-edit-group'>");
// 			printf ("<label><span class='title'>Flag</span><input type='checkbox' name='item_flag' id='item_flag_id' value='1' %s/></label>", $item->flag==1 ? "checked" : "");
// 			printf ("</div></fieldset>");
				
			
			
		            ?>
		            		            
<script type="text/javascript">         
        jQuery(document).ready( function($) {
            $('span:contains("Title")').each(function (i) { $(this).parent().remove(); });
            $('span:contains("Status")').each(function (i) { $(this).parent().remove(); });
            $('span:contains("Slug")').each(function (i) { $(this).parent().remove(); });
            $('span:contains("Password")').each(function (i) { $(this).parent().parent().remove(); });
            $('span:contains("Date")').each(function (i) { $(this).parent().remove(); });
            $('.inline-edit-date').each(function (i) { $(this).remove(); });
        });    
    </script>		            
		            
		            
		            
		            <?php
		}
	}
	
	
}

?>