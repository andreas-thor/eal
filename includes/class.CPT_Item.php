<?php

require_once("class.CPT_Object.php");
require_once("class.CLA_RoleTaxonomy.php");

class CPT_Item extends CPT_Object{
	
	
	public $table_columns = array (
		'cb' => '<input type="checkbox" />',
		'item_title' => 'Title',
		'date' => 'Date',
		'item_type' => 'Type',
		'taxonomy' => 'Taxonomy', 
		'item_author' => 'Author',
		'item_points' => 'Points',
		'level_FW' => 'FW',
		'level_KW' => 'KW',
		'level_PW' => 'PW',
		'no_of_reviews' => 'Reviews',
		'item_learnout' => 'Learn. Out.',
		'difficulty' => 'Difficulty'
	);
	
	
	
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
		
		
		add_filter('posts_search', array ($this ,'WPCB_post_search'), 10, 2);
		
		
		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
		
		
		
	}


	

	

	public function WPCB_post_row_actions($actions, $post){
	
		// 		unset ($actions['view']);
		// 		unset ($actions['edit']);
		// 		unset ($actions['inline hide-if-no-js']);
		// 		return $actions;
	
		if ($post->post_type != $this->type) return $actions;
	
		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
		$actions['view'] = "<a href='admin.php?page=view&itemid={$post->ID}'>View</a>"; // add "View"
	
		if (!RoleTaxonomy::canEditItemPost($post)) {		// "Edit" & "Trash" only if editable by user
			unset ($actions['edit']);
			unset ($actions['trash']);
		}
	
		return $actions;
	}
	

	
	function add_bulk_actions() {
	
		global $post_type;
		if ($post_type != $this->type) return;
	
?>
		<script type="text/javascript">
			jQuery(document).ready(function() {

				var htmlselect = ["action", "action2"];
					    	
				htmlselect.forEach(function (s, i, o) {
						  		
					jQuery("select[name='" + s + "'] > option").remove();
			        jQuery('<option>').val('bulk').text('<?php _e('[Bulk Actions]')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('view').text('<?php _e('View Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('trash').text('<?php _e('Trash Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('add_to_basket').text('<?php _e('Add Items To Basket')?>').appendTo("select[name='" + s + "']");
			      });
			});			    
	    </script>
<?php

	}
		
	
	
	
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $item, $post;
		
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if (($domain["name"] != "") && ($item->domain != $domain["name"])) {
			wp_die ("Item does not belong to your current domain!");
		}
		
		// remove Publish button for authors
		if (RoleTaxonomy::getCurrentRoleType() == "author") {
			?><style> #publishing-action { display: none; } </style> <?php
		}
		
		// remove publishing date and visibility		
		?><style> 
			#visibility { display: none; }
			div.curtime { display: none; }
		</style> <?php
		
		$post->post_title .= "\x03";	// we add ASCII 03 to modify the title
		
		add_meta_box('mb_learnout', 'Learning Outcome', array ($this, 'WPCB_mb_learnout'), $this->type, 'normal', 'default', array ('learnout' => $item->getLearnOut()));
		
		
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_description', 'value' => $item->description) );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_question', 'value' => $item->question));
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $item->level, 'default' => (($item->getLearnOut() == null) ? null : $item->getLearnOut()->level) ));
		add_meta_box("mb_{$this->type}_answers", "Antwortoptionen",	array ($this, 'WPCB_mb_answers'), $this->type, 'normal', 'default');
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::$domains[$item->domain], array ($this, 'WPCB_mb_taxonomy'), $this->type, 'side', 'default', array ( "taxonomy" => $item->domain ));
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
		
// 		$vars['args']['callback'] = 'checkLOLevel';
		
		global $item;
		print (CPT_Object::getLevelHTML("item", $item->level, (($item->getLearnOut() == null) ? null : $item->getLearnOut()->level), "", 0, 'checkLOLevel'));
		
// 		return parent::WPCB_mb_level($post, $vars);
		
	}
	
	
	
	public function WPCB_mb_learnout ($post, $vars) {
	
		?>
		<script>
// 			jQuery(document).ready(function() {
// 				jQuery("div#visibility").remove();
// 				jQuery("span#timestamp").parent().remove();
// 			});
		</script>
		<?php 
											
		
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
	
	

	
	/**
	 * Join to item table; restrict to items of current domain (if set)
	 * join to learning outcome (if available)
	 * {@inheritDoc}
	 * @see CPT_Object::WPCB_posts_join()
	 */
	
	public function WPCB_posts_join ($join, $checktype=TRUE) {
		
		global $wp_query, $wpdb;
	
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			$domain = RoleTaxonomy::getCurrentRoleDomain();
			$join .= " JOIN {$wpdb->prefix}eal_item I ON (I.id = {$wpdb->posts}.ID " . (($domain["name"] != "") ? "AND I.domain = '" . $domain["name"] . "')" : ")"); 
			$join .= " JOIN {$wpdb->users} U ON (U.id = {$wpdb->posts}.post_author) ";
			$join .= " LEFT OUTER JOIN {$wpdb->prefix}eal_learnout L ON (L.id = I.learnout_id)";
		}
		return $join;
	}
	
	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		
		global $wp_query, $wpdb;
		
		if ($wp_query->query["post_type"] == $this->type) {
			$array .= ", I.title AS item_title";
			$array .= ", I.type AS item_type";
			$array .= ", {$wpdb->posts}.post_author AS item_author_id";
			$array .= ", U.user_login AS item_author";
			$array .= ", I.level_FW AS level_FW";
			$array .= ", I.level_PW AS level_PW";
			$array .= ", I.level_KW AS level_KW";
			$array .= ", I.points AS item_points";
			$array .= ", (select count(*) from {$wpdb->prefix}eal_review AS R join {$wpdb->posts} AS RP ON (R.ID=RP.ID) where RP.post_parent=0 AND I.id = R.item_id) AS no_of_reviews";
			$array .= ", L.title AS learnout_title";
			$array .= ", L.id AS learnout_id ";
			$array .= ", I.difficulty as difficulty ";
		}
		return $array;
	}
	
	

	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query, $wpdb;
	
// 		$orderby_statement = parent::WPCB_posts_orderby($orderby_statement);
	
		if ($wp_query->query["post_type"] == $this->type) {
			
			if ($wp_query->get('orderby') == $this->table_columns['item_title'])	 	$orderby_statement = "I.title {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['date'])		 		$orderby_statement = "{$wpdb->posts}.post_date {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_type']) 		$orderby_statement = "I.type {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_author'])	 	$orderby_statement = "U.user_login {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_points']) 		$orderby_statement = "I.points {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_FW']) 			$orderby_statement = "I.level_FW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_PW']) 			$orderby_statement = "I.level_PW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_KW']) 			$orderby_statement = "I.level_KW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['no_of_reviews'])		$orderby_statement = "no_of_reviews {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_learnout'])		$orderby_statement = "L.title {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['difficulty']) 		$orderby_statement = "I.difficulty {$wp_query->get('order')}";
		}
	
		return $orderby_statement;
	}	
	
	
					
	public function WPCB_posts_where($where, $checktype = TRUE) {
	
		global $wp_query, $wpdb;
		
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			
			// if all items are considered --> consider all type starting with "item"
			if ($this->type == "item") {
				$where = str_replace( "{$wpdb->posts}.post_type = 'item'", "{$wpdb->posts}.post_type LIKE 'item%'", $where);
			}

			// if current role type = author --> show all items except drafts from others
			if (RoleTaxonomy::getCurrentRoleType()=="author") {
				$where .= "AND ({$wpdb->posts}.post_status != 'draft' OR {$wpdb->posts}.post_author = " . get_current_user_id() . ")";
			}
			
			if (isset ($_REQUEST["item_type"])  && ($_REQUEST['item_type'] != "0")) 		$where .= " AND I.type = '{$_REQUEST['item_type']}'";
			if (isset ($_REQUEST["post_status"]) && ($_REQUEST['post_status'] != "0")) 		$where .= " AND {$wpdb->posts}.post_status = '" . $_REQUEST['post_status'] . "'";
			
			if (isset ($_REQUEST["learnout_id"])) 										$where .= " AND L.id = {$_REQUEST['learnout_id']}";
			if (isset ($_REQUEST['item_author'])) 										$where .= " AND {$wpdb->posts}.post_author 			= " . $_REQUEST['item_author'];
			if (isset ($_REQUEST['item_points'])) 										$where .= " AND I.points  	= " . $_REQUEST['item_points'];
			if (isset ($_REQUEST['level_FW']) && ($_REQUEST['level_FW']>0)) 			$where .= " AND I.level_FW 	= " . $_REQUEST['level_FW'];
			if (isset ($_REQUEST['level_PW']) && ($_REQUEST['level_PW']>0)) 			$where .= " AND I.level_PW 	= " . $_REQUEST['level_PW'];
			if (isset ($_REQUEST['level_KW']) && ($_REQUEST['level_KW']>0)) 			$where .= " AND I.level_KW	= " . $_REQUEST['level_KW'];
			if (isset ($_REQUEST['learnout_id']))										$where .= " AND I.learnout_id = " . $_REQUEST['learnout_id'];

			if (isset ($_REQUEST['taxonomy']) && ($_REQUEST['taxonomy']>0))	{
				
				$children = get_term_children( $_REQUEST['taxonomy'], RoleTaxonomy::getCurrentRoleDomain()["name"] );
				array_push($children, $_REQUEST['taxonomy']);
				$where .= sprintf (' AND %1$s.ID IN (SELECT TR.object_id FROM %2$s TT JOIN %3$s TR ON (TT.term_taxonomy_id = TR.term_taxonomy_id) WHERE TT.term_id IN ( %4$s ))',
					$wpdb->posts , $wpdb->term_taxonomy, $wpdb->term_relationships, implode(', ', $children));
				
			}
			
			if ($this->type == "itembasket") {
			
				$where = str_replace( "{$wpdb->posts}.post_type = 'itembasket'", "{$wpdb->posts}.post_type LIKE 'item%'", $where);
				
				$basket = get_user_meta(get_current_user_id(), 'itembasket', true);
				if (is_array($basket) && (count($basket)>0)) {
					$where .= " AND I.ID IN (" . implode(",", $basket) . ") ";
				} else {
					$where .= " AND (1=2) ";
				}
				
			}
		}
	
		
		
		return $where;
	}
	

	

	public function WPCB_post_search($search, $wpquery){
	
		global $post_type;
		if ($post_type != $this->type) return $search;
		if (empty ($search)) return $search;
// 		if (!isset ($wpquery->query['s'])) return $search;
		
		
		$search = sprintf (' AND ( L.Title LIKE "%%%1$s%%" OR I.Title LIKE "%%%1$s%%" OR U.user_login LIKE "%%%1$s%%" )', $wpquery->query['s']);
		return $search;
		// 		$a = 7;
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
	


	



}

?>