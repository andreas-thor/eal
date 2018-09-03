<?php

require_once 'CPT_Object.php';
require_once __DIR__ . '/../class.CLA_RoleTaxonomy.php';

class CPT_Item extends CPT_Object{
	
	
	
	
	public function __construct() {
		
		$this->type = 'item';
		$this->label = 'All Items';
		$this->menu_pos = 0;
		$this->cap_type = 'item';
		$this->dashicon = 'dashicons-format-aside';
		$this->supports = array('title', 'revisions');
		$this->taxonomies = array(RoleTaxonomy::getCurrentDomain());
		
		
		$this->table_columns = array (
				'cb' => '<input type="checkbox" />',
				'item_title' => 'Title',
				'id' => 'ID',
				'last_modified' => 'Date',
				'item_type' => 'Type',
				'taxonomy' => 'Taxonomy',
				'item_author' => 'Author',
				'item_points' => 'Points',
				'level_FW' => 'FW',
				'level_KW' => 'KW',
				'level_PW' => 'PW',
				'no_of_reviews' => 'Reviews',
				'item_learnout' => 'Learn. Out.',
				'difficulty' => 'Difficulty',
				'note' => 'Note',
				'flag' => 'Flag'
		);
		
		$this->bulk_actions = array (
			'view' => 'View Items',
			'view_review' => 'View Items with Reviews',
			'trash' => 'Trash Items',
			'add_to_basket' => 'Add Items To Basket'
		);
		
		
	}
	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function addHooks() {
		
		parent::addHooks();

		add_action('contextual_help', array ($this, 'WPCB_contextual_help' ), 10, 3);
		

		add_filter ('wp_get_revision_ui_diff', array ($this, 'filter_wp_get_revision_ui_diff'), 10, 3 );
		

	
		
		add_filter('posts_search', array ($this ,'WPCB_post_search'), 10, 2);
		
		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
		
		

		
		
// 		add_filter ('wp_ajax_get_revision_diffs', array ($this, 'X'), 10, 3 );
// 		add_filter( 'revision_text_diff_options', array ($this, 'Y'), 10, 3);
// 		add_filter( 'wp_prepare_revision_for_js', array ($this, 'filter_function_name_4025'), 10, 3 );
// 		add_action ('post_updated', array ($this, 'qwe'), 10, 3);
		
		
	}



	/**
	 * 
	 * @param array $actions An array of row action links. Defaults are 'Edit', 'Quick Edit', 'Restore, 'Trash', 'Delete Permanently', 'Preview', and 'View'.
	 * @param WP_Post $post The post object.
	 */
	

	public function WPCB_post_row_actions(array $actions, WP_Post $post){
	
		if ($post->post_type != $this->type) {
			return $actions;
		}
	
		// remove "Quick Edit"
		unset ($actions['inline hide-if-no-js']);			
		
		// add "View" to view item
		$actions['view'] = "<a href='admin.php?page=view_item&itemid={$post->ID}'>View</a>"; 
	
		// "Edit" & "Trash" only if editable by user
		if (!RoleTaxonomy::canEditItemPost($post)) {		
			unset ($actions['edit']);
			unset ($actions['trash']);
		}
	
		return $actions;
	}
	

	
		
	function WPCB_process_bulk_action() {
	
	
		if ($_REQUEST["post_type"] != $this->type) return;
	
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
	
		if ($wp_list_table->current_action() == 'view') {
			$sendback = add_query_arg( 'itemids', $_REQUEST['post'], 'admin.php?page=view_item' );
			wp_redirect($sendback);
			exit();
		}

		if ($wp_list_table->current_action() == 'view_review') {
			$sendback = add_query_arg( 'itemids', $_REQUEST['post'], 'admin.php?page=view_item_with_reviews' );
			wp_redirect($sendback);
			exit();
		}
		
		
		/* Add Items to Basket */
		if ($wp_list_table->current_action() == 'add_to_basket') {
			$postids = $_REQUEST['post'];
			if (!is_array($postids)) $postids = [$postids];
			EAL_ItemBasket::add($postids);
		}
		
		/* Remove from Basket */
		if ($wp_list_table->current_action() == 'remove_from_basket') {
			$remove = array ();
			if (isset($_REQUEST["post"])) 	$remove = $_REQUEST['post'];
			if ($_REQUEST['itemid']!=null) 	$remove = [$_REQUEST['itemid']];
			if ($_REQUEST['itemids']!=null) $remove = $_REQUEST['itemids'];
			EAL_ItemBasket::remove($remove);
		}		
	
	}	
	
	
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $item, $post;
		parent::WPCB_register_meta_box_cb();
		
		// check for correct domain 
		$domain = RoleTaxonomy::getCurrentDomain();
		if (($domain != '') && ($item->getDomain() != $domain)) {
			wp_die ('Item does not belong to your current domain!');
		}
		
		// check for edit capabilities
		if (!RoleTaxonomy::canEditItemPost($post)) {
			wp_die ("You are not allowed to edit this item!");
		}
		
		// remove Publish button for authors
		if (RoleTaxonomy::getCurrentRoleType() == "author") {
			?><style> #publishing-action { display: none; } </style> <?php
		}
		
		// remove publishing date and visibility		
		?><style> 
			#visibility { display: none; }
			div.curtime { display: none; }
		</style> 
		
		
		
		
		<?php

		
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($item->getHTMLPrinter(), metaboxDescription), $this->type, 'normal', 'default' );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($item->getHTMLPrinter(), metaboxQuestion), $this->type, 'normal', 'default');
		
		add_meta_box('mb_learnout', 'Learning Outcome', array ($item->getHTMLPrinter(), metaboxLearningOutcome), $this->type, 'side', 'default');
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($item->getHTMLPrinter(), metaboxLevel), $this->type, 'side', 'default');
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::getDomains()[$item->getDomain()], array ($item->getHTMLPrinter(), metaboxTopic), $this->type, 'side', 'default');
		add_meta_box('mb_item_note_flag', 'Notiz', array ($item->getHTMLPrinter(), metaboxNoteFlag), $this->type, 'side', 'default');

		
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
			$domain = RoleTaxonomy::getCurrentDomain();
			$join .= " JOIN {$wpdb->prefix}eal_item I ON (I.id = {$wpdb->posts}.ID " . (($domain != "") ? "AND I.domain = '" . $domain . "')" : ")"); 
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
// 			$array .= ", (select count(*) from {$wpdb->prefix}eal_review AS R join {$wpdb->posts} AS RP ON (R.ID=RP.ID) where RP.post_parent=0 AND I.id = R.item_id AND RP.post_status IN ('publish', 'pending', 'draft')) AS no_of_reviews";
			$array .= ", I.no_of_reviews AS no_of_reviews"; 
			$array .= ", L.title AS learnout_title";
			$array .= ", L.id AS learnout_id ";
			$array .= ", I.difficulty as difficulty ";
			$array .= ", I.no_of_testresults as no_of_testresults ";
			$array .= ", I.note as note ";
			$array .= ", I.flag as flag ";
		}
		return $array;
	}
	
	

	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query, $wpdb;
	
		if ($wp_query->query["post_type"] == $this->type) {
			
			switch ($wp_query->get('orderby')) {
				case $this->table_columns['item_title']: 	$orderby_statement = 'I.title'; break;
				case $this->table_columns['last_modified']: $orderby_statement = $wpdb->posts . '.post_modified'; break;
				case $this->table_columns['item_type']: 	$orderby_statement = 'I.type'; break;
				case $this->table_columns['item_author']: 	$orderby_statement = 'U.user_login'; break;
				case $this->table_columns['item_points']: 	$orderby_statement = 'I.points'; break;
				case $this->table_columns['level_FW']: 		$orderby_statement = 'I.level_FW'; break;
				case $this->table_columns['level_PW']: 		$orderby_statement = 'I.level_PW'; break;
				case $this->table_columns['level_KW']: 		$orderby_statement = 'I.level_KW'; break;
				case $this->table_columns['no_of_reviews']:	$orderby_statement = 'no_of_reviews'; break;
				case $this->table_columns['item_learnout']:	$orderby_statement = 'L.title'; break;
				case $this->table_columns['difficulty']: 	$orderby_statement = 'I.difficulty'; break;
				case $this->table_columns['note']: 			$orderby_statement = 'I.note'; break;
				case $this->table_columns['flag']: 			$orderby_statement = 'I.flag'; break;
				case $this->table_columns['id']: 			$orderby_statement = 'I.id'; break;
				default: 									$orderby_statement = $wpdb->posts . '.post_modified';	// default: last modified 
			}
			$orderby_statement .= ' ' . $wp_query->get('order');
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
			if (isset ($_REQUEST["post_status"]) && ($_REQUEST['post_status'] != "all") && ($_REQUEST['post_status'] != "0") ) 		$where .= " AND {$wpdb->posts}.post_status = '" . $_REQUEST['post_status'] . "'";
			
			if (isset ($_REQUEST['itemids']))											$where .= " AND I.id IN ({$_REQUEST['itemids']}) ";
			if (isset ($_REQUEST["learnout_id"])) 										$where .= " AND L.id = {$_REQUEST['learnout_id']}";
			if (isset ($_REQUEST['item_author'])) 										$where .= " AND {$wpdb->posts}.post_author 			= " . $_REQUEST['item_author'];
			if (isset ($_REQUEST['item_points'])) 										$where .= " AND I.points  	= " . $_REQUEST['item_points'];
			if (isset ($_REQUEST['level_FW']) && ($_REQUEST['level_FW']>0)) 			$where .= " AND I.level_FW 	= " . $_REQUEST['level_FW'];
			if (isset ($_REQUEST['level_PW']) && ($_REQUEST['level_PW']>0)) 			$where .= " AND I.level_PW 	= " . $_REQUEST['level_PW'];
			if (isset ($_REQUEST['level_KW']) && ($_REQUEST['level_KW']>0)) 			$where .= " AND I.level_KW	= " . $_REQUEST['level_KW'];
			if (isset ($_REQUEST['learnout_id']))										$where .= " AND I.learnout_id = " . $_REQUEST['learnout_id'];
			if (isset ($_REQUEST['flag']))	{
				if ($_REQUEST['flag'] == 1) 											$where .= " AND I.flag = 1";
				if ($_REQUEST['flag'] == 2) 											$where .= " AND (I.flag != 1 OR I.flag IS NULL)";
			}
			
			
			if (isset ($_REQUEST['taxonomy']) && ($_REQUEST['taxonomy']>0))	{
				
				$children = get_term_children( $_REQUEST['taxonomy'], RoleTaxonomy::getCurrentDomain());
				array_push($children, $_REQUEST['taxonomy']);
				$where .= sprintf (' AND %1$s.ID IN (SELECT TR.object_id FROM %2$s TT JOIN %3$s TR ON (TT.term_taxonomy_id = TR.term_taxonomy_id) WHERE TT.term_id IN ( %4$s ))',
					$wpdb->posts , $wpdb->term_taxonomy, $wpdb->term_relationships, implode(', ', $children));
				
			}
			
			
			
			if ($this->type == "itembasket") {
			
				// consider all items (no matter what type) ...
				$where = str_replace( "{$wpdb->posts}.post_type = 'itembasket'", "{$wpdb->posts}.post_type LIKE 'item%'", $where);
				
				// ... that are in the basket
				$basket = EAL_ItemBasket::get();
				$where .= (count($basket)>0) ? " AND I.ID IN (" . implode(",", $basket) . ") " : " AND (1=2) ";
			}
		}
	
		
		
		return $where;
	}
	

	

	public function WPCB_post_search($search, $wpquery){
	
		global $post_type;
		if ($post_type != $this->type) return $search;
		if (empty ($search)) return $search;
		
		$search  = sprintf('    I.title        LIKE "%%%1$s%%"', $wpquery->query['s']);
		$search .= sprintf(' OR I.note         LIKE "%%%1$s%%"', $wpquery->query['s']);
		$search .= sprintf(' OR L.title        LIKE "%%%1$s%%"', $wpquery->query['s']);
		$search .= sprintf(' OR U.user_login   LIKE "%%%1$s%%"', $wpquery->query['s']);
		
		if (is_numeric ($wpquery->query['s'])) {  // for numbers also check id
		    $search .= sprintf(' OR I.id  = %1$d', intval($wpquery->query['s']));     // if s is not a number --> intval==0 --> no problem, since there is no id==1     
		}
		
		return sprintf (' AND ( %s )', $search);
	}
	
 




	public function WPCB_post_updated_messages ( $messages ) {
	
		global $post, $post_ID;
		$messages[$this->type] = array(
				0 => '',
				1 => sprintf( __('Item %1$d updated. <a href="admin.php?page=view_item&itemid=%1$d">View Item</a>'), $post_ID ),
				2 => __('Custom field updated.'),
				3 => __('Custom field deleted.'),
		        4 => sprintf( __('Item %1$d updated. <a href="admin.php?page=view_item&itemid=%1$d">View Item</a>'), $post_ID ),
				5 => isset($_GET['revision']) ? sprintf( __("{$this->label} restored to revision from %s"), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __('Item %1$d published. <a href="admin.php?page=view_item&itemid=%1$d">View Item</a>'), $post_ID ),
				7 => sprintf( __('Item %1$d saved. <a href="admin.php?page=view_item&itemid=%1$d">View Item</a>'), $post_ID ),
				8 => sprintf( __('Item %1$d submitted. <a href="admin.php?page=view_item&itemid=%1$d">View Item</a>'), $post_ID ),
				9 => sprintf( __('Item %2$d scheduled for: <strong>%1$s</strong>. <a href="admin.php?page=view_item&itemid=%2$d">View Item</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), $post_ID ),
				10 => sprintf( __('Item %1$d updated. <a href="admin.php?page=view_item&itemid=%1$d">View Item</a>'), $post_ID )
		);
		return $messages;
	}	
	
	
	
	
	

	public function WPCB_contextual_help( $contextual_help, $screen_id, $screen ) {
	
	
		$screen->add_help_tab( array(
				'id' => 'you_custom_id', // unique id for the tab
				'title' => 'Custom Help', // unique visible title for the tab
				'content' => '<h3>Help Title</h3><p>Help content</p>', //actual help text
		));
	
		$screen->add_help_tab( array(
				'id' => 'you_custom_id_2', // unique id for the second tab
				'title' => 'Vignette', // unique visible title for the second tab
				'content' => '<h3>Vignette</h3><p>Verwenden Sie Vignetten zur Kontextualisierung und/oder zur Anwendungsorientierung des Items.</p>', //actual help text
		));
	
	
	
	
		// 		if ( 'itemmc' == $screen->id ) {
	
		// 			$contextual_help = '<h2>Products</h2>
		//     <p>Products show the details of the items that we sell on the website. You can see a list of them on this page in reverse chronological order - the latest one we added is first.</p>
		//     <p>You can view/edit the details of each product by clicking on its name, or you can perform bulk actions using the dropdown menu and selecting multiple items.</p>
		// 		<h1>Hallo</h1><h2>jhjh</h2><p>jkjkj</p>
	
		// 		';
	
		// 		} elseif ( 'edit-itemmc' == $screen->id ) {
	
		// 			$contextual_help = '<h2>Editing products</h2>
		//     <p>This page allows you to view/modify product details. Please make sure to fill out the available boxes with the appropriate details (product image, price, brand) and <strong>not</strong> add these details to the product description.</p>';
	
		// 		}
		return $contextual_help;
	}
	
	



}

?>