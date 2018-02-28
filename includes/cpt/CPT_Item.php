<?php

require_once("CPT_Object.php");
require_once(__DIR__ . "/../html/HTML_Object.php");
require_once(__DIR__ . "/../class.CLA_RoleTaxonomy.php");

class CPT_Item extends CPT_Object{
	
	
	
	
	public function __construct() {
		
		$this->type = 'item';
		$this->label = 'All Items';
		$this->menu_pos = 0;
		$this->cap_type = 'item';
		$this->dashicon = 'dashicons-format-aside';
		$this->supports = array('title', 'revisions');
		$this->taxonomies = array(RoleTaxonomy::getCurrentRoleDomain()["name"]);
		
		
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
	}
	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function addHooks() {
		
		parent::addHooks();

		add_action('contextual_help', array ($this, 'WPCB_contextual_help' ), 10, 3);
		

		add_filter ('wp_get_revision_ui_diff', array ($this, 'WPCB_wp_get_revision_ui_diff'), 10, 3 );
		
		
		add_filter('posts_search', array ($this ,'WPCB_post_search'), 10, 2);
		
		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
	}


	

	

	public function WPCB_post_row_actions($actions, $post){
	
		if ($post->post_type != $this->type) return $actions;
	
		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
		$actions['view'] = "<a href='admin.php?page=view_item&itemid={$post->ID}'>View</a>"; // add "View"
	
		if (!RoleTaxonomy::canEditItemPost($post)) {		// "Edit" & "Trash" only if editable by user
			unset ($actions['edit']);
			unset ($actions['trash']);
		}
	
		return $actions;
	}
	

	
	function WPCB_add_bulk_actions() {
	
		global $post_type;
  		if ($post_type != $this->type) return;
	
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {

				var htmlselect = ["action", "action2"];
					    	
				htmlselect.forEach(function (s, i, o) {
						  		
					jQuery("select[name='" + s + "'] > option").remove();
			        jQuery('<option>').val('-1').text('<?php _e('[Bulk Actions]')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('view').text('<?php _e('View Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('trash').text('<?php _e('Trash Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('view_review').text('<?php _e('View Reviews')?>').appendTo("select[name='" + s + "']");

			        <?php if ($post_type == "itembasket") { ?> 
				        jQuery('<option>').val('remove_from_basket').text('<?php _e('Remove Items From Basket')?>').appendTo("select[name='" + s + "']");
					<?php } else { ?>
				        jQuery('<option>').val('add_to_basket').text('<?php _e('Add Items To Basket')?>').appendTo("select[name='" + s + "']");
					<?php } ?>
			      });
			});			    
	    </script>
		<?php

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
			$sendback = add_query_arg( 'itemids', $_REQUEST['post'], 'admin.php?page=view_review' );
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
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if (($domain["name"] != "") && ($item->getDomain() != $domain["name"])) {
			wp_die ("Item does not belong to your current domain!");
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
		</style> <?php

		
		add_meta_box('mb_learnout', 'Learning Outcome', array ($this, 'WPCB_mb_learnout'), $this->type, 'normal', 'default');
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($this, 'WPCB_mb_description'), $this->type, 'normal', 'default', array ('name' => 'item_description', 'value' => $item->description) );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($this, 'WPCB_mb_question'), $this->type, 'normal', 'default', array ('name' => 'item_question', 'value' => $item->question));
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default');
		add_meta_box("mb_{$this->type}_answers", "Antwortoptionen",	array ($this, 'WPCB_mb_answers'), $this->type, 'normal', 'default');
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::getDomains()[$item->getDomain()], array ($this, 'WPCB_mb_taxonomy'), $this->type, 'side', 'default', array ( "taxonomy" => $item->getDomain() ));
		add_meta_box('mb_item_note_flag', 'Notiz', array ($this, 'WPCB_mb_note_flag'), $this->type, 'normal', 'default');
		
		

	}
	
	
	public function WPCB_mb_learnout ($post, $vars) {
		global $item;
		print (HTML_Item::getHTML_LearningOutcome($item, HTML_Object::VIEW_EDIT));
	}

	public function WPCB_mb_description ($post, $vars) {
		parent::WPCB_mb_editor ($post, $vars);
	}
	
	public function WPCB_mb_question ($post, $vars, $buttons = array()) {
		parent::WPCB_mb_editor ($post, $vars);
		
		printf("<div style='margin:10px'>");
		foreach ($buttons as $short => $long) {
			printf ("<a style='margin:3px' class='button' onclick=\"tinyMCE.editors['%s'].execCommand( 'mceInsertContent', false, '%s');\">%s</a>", $vars['args']['name'], htmlentities($long, ENT_SUBSTITUTE, 'ISO-8859-1'), htmlentities($short, ENT_SUBSTITUTE, 'ISO-8859-1'));
		}
		printf ("</div>");
	}
	
	public function WPCB_mb_level ($post, $vars) {
		global $item;
		print (HTML_Item::getHTML_Level($item, HTML_Object::VIEW_EDIT));
	}
	
	
	public function WPCB_mb_answers ($post, $vars) {
		wp_die ("<pre>Can not call WPCB_mb_answers on CPT_Item.</pre>");
	}
	
	public function WPCB_mb_taxonomy ($post, $vars) {
		post_categories_meta_box( $post, array ("id" => "WPCB_mb_taxonomy", "title" => "", "args" => $vars['args']) );
	}
	
	
	public function WPCB_mb_note_flag ($post, $vars) {
	
		// we dynamically set the value of $POST["post_content"] to make sure that we have revision
		printf ("<input type='hidden' id='post_content' name='post_content'  value='%s'>", microtime());
		
		
		
		global $item;
		print (HTML_Item::getHTML_NoteFlag($item, HTML_Object::VIEW_EDIT));
		
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
			$array .= ", (select count(*) from {$wpdb->prefix}eal_review AS R join {$wpdb->posts} AS RP ON (R.ID=RP.ID) where RP.post_parent=0 AND I.id = R.item_id AND RP.post_status IN ('publish', 'pending', 'draft')) AS no_of_reviews";
			$array .= ", L.title AS learnout_title";
			$array .= ", L.id AS learnout_id ";
			$array .= ", I.difficulty as difficulty ";
			$array .= ", I.note as note ";
			$array .= ", I.flag as flag ";
		}
		return $array;
	}
	
	

	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query, $wpdb;
	
		if ($wp_query->query["post_type"] == $this->type) {
			
			// default: last modified DESC
			$orderby_statement = "{$wpdb->posts}.post_modified DESC";
			
			if ($wp_query->get('orderby') == $this->table_columns['item_title'])	 	$orderby_statement = "I.title {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['last_modified'])		$orderby_statement = "{$wpdb->posts}.post_modified {$wp_query->get('order')}";
// 			if ($wp_query->get('orderby') == $this->table_columns['date'])		 		$orderby_statement = "{$wpdb->posts}.post_date {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_type']) 		$orderby_statement = "I.type {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_author'])	 	$orderby_statement = "U.user_login {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_points']) 		$orderby_statement = "I.points {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_FW']) 			$orderby_statement = "I.level_FW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_PW']) 			$orderby_statement = "I.level_PW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_KW']) 			$orderby_statement = "I.level_KW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['no_of_reviews'])		$orderby_statement = "no_of_reviews {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['item_learnout'])		$orderby_statement = "L.title {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['difficulty']) 		$orderby_statement = "I.difficulty {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['note']) 				$orderby_statement = "I.note {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['flag']) 				$orderby_statement = "I.flag {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['id']) 				$orderby_statement = "I.id {$wp_query->get('order')}";
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
				
				$children = get_term_children( $_REQUEST['taxonomy'], RoleTaxonomy::getCurrentRoleDomain()["name"] );
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