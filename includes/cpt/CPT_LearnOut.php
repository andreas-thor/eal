<?php

require_once 'CPT_Object.php';
require_once __DIR__ . '/../eal/EAL_LearnOut.php';
require_once __DIR__ . '/../db/DB_LearnOut.php';


class CPT_LearnOut extends CPT_Object {
	
	public function __construct() {
	
		$this->type = "learnout";
		$this->label = "Learn. Outcome";
		$this->menu_pos = 0;
		$this->cap_type = $this->type;
		$this->dashicon = "dashicons-welcome-learn-more";
		$this->supports = array('title');
		$this->taxonomies = array(RoleTaxonomy::getCurrentDomain());
		
		$this->table_columns = array (
			'cb' => '<input type="checkbox" />',
			'learnout_title' => 'Title',
			'last_modified' => 'Date',
			'taxonomy' => 'Taxonomy',
			'learnout_author' => 'Author', 
			'level_FW' => 'FW',
			'level_KW' => 'KW',
			'level_PW' => 'PW',
			'no_of_items' => 'Items'
		);
		
		$this->bulk_actions = array (
			'view' => 'View Learning Outcomes', 
			'trash' => 'Trash Learning Outcomes',
			'view_items' => 'View Items',
			'add_to_basket' => 'Add Items To Basket'
		);
	}	
	

	
	public function addHooks() {
		parent::addHooks();
		add_action ("save_post_{$this->type}", array ('CPT_LearnOut', 'save_post'), 10, 2);
	}
	

	public static function save_post (int $post_id, WP_Post $post) {
		
		if ($post->post_type != EAL_LearnOut::getType()) return;
		
		$learnout = ($post->post_status === 'auto-draft') ? new EAL_LearnOut($post_id) : EAL_LearnOut::createFromArray($post_id, $_POST);
		DB_Learnout::saveToDB($learnout);	
	}
	
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $post;
		
		parent::WPCB_register_meta_box_cb();
		
		$learnout = DB_Learnout::loadFromDB($post->ID); 
		$domain = RoleTaxonomy::getCurrentDomain();
		if (($domain != "") && ($learnout->getDomain() != $domain)) {
			wp_die ("Learning outcome  does not belong to your current domain!");
		}
		
		// remove visibility and status options
		print ('<style> #minor-publishing { display: none; } </style>');
		
		add_meta_box('mb_description', 'Beschreibung', array ($learnout->getHTMLPrinter(), 'metaboxDescription'), $this->type, 'normal', 'default' );
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($learnout->getHTMLPrinter(), 'metaboxLevel'), $this->type, 'side', 'default');
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::getDomains()[$learnout->getDomain()], array ($learnout->getHTMLPrinter(), 'metaboxTopic'), $this->type, 'side', 'default');
	}	

	
	function wpdocs_theme_name_scripts() {
		wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
	}
	
	
	
	
	
	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array .= ", L.title AS learnout_title";
			$array .= ", {$wpdb->posts}.post_author AS learnout_author_id";
			$array .= ", U.user_login AS learnout_author";
			$array .= ", L.level_FW AS level_FW";
			$array .= ", L.level_PW AS level_PW";
			$array .= ", L.level_KW AS level_KW";			
			$array .= ", L.no_of_items AS no_of_items";
		}
		return $array;
	}
	
	public function WPCB_posts_join ($join, $checktype=TRUE) {
		
		global $wp_query, $wpdb;
		
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			$domain = RoleTaxonomy::getCurrentDomain();
			$join .= " JOIN {$wpdb->prefix}eal_{$this->type} L ON (L.id = {$wpdb->posts}.ID " . (($domain!="") ? "AND L.domain = '" . $domain . "')" : ")");
			$join .= " JOIN {$wpdb->users} U ON (U.id = {$wpdb->posts}.post_author) ";
		}
		return $join;
	}
	

	public function WPCB_posts_where($where, $checktype = TRUE) {
	
		global $wp_query, $wpdb;
	
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			if (isset ($_REQUEST['learnout_author'])) 	$where .= " AND {$wpdb->posts}.post_author 	= " . $_REQUEST['learnout_author'];
			if (isset ($_REQUEST['level_FW']) && ($_REQUEST['level_FW']>0)) 			$where .= " AND L.level_FW 	= " . $_REQUEST['level_FW'];
			if (isset ($_REQUEST['level_PW']) && ($_REQUEST['level_PW']>0)) 			$where .= " AND L.level_PW 	= " . $_REQUEST['level_PW'];
			if (isset ($_REQUEST['level_KW']) && ($_REQUEST['level_KW']>0)) 			$where .= " AND L.level_KW	= " . $_REQUEST['level_KW'];
			
			
			if (isset ($_REQUEST['taxonomy']) && ($_REQUEST['taxonomy']>0))	{
			
				$children = get_term_children( $_REQUEST['taxonomy'], RoleTaxonomy::getCurrentDomain());
				array_push($children, $_REQUEST['taxonomy']);
				$where .= sprintf (' AND %1$s.ID IN (SELECT TR.object_id FROM %2$s TT JOIN %3$s TR ON (TT.term_taxonomy_id = TR.term_taxonomy_id) WHERE TT.term_id IN ( %4$s ))',
						$wpdb->posts , $wpdb->term_taxonomy, $wpdb->term_relationships, implode(', ', $children));
			
			}
			
		}
		return $where;
	}
	
	
	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wpdb, $wp_query;
		
		if ($wp_query->query["post_type"] == $this->type) {
			
			// default: last modified DESC
			$orderby_statement = "{$wpdb->posts}.post_modified DESC";
			
			if ($wp_query->get('orderby') == $this->table_columns['learnout_title'])	$orderby_statement = "learnout_title {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['last_modified'])		$orderby_statement = "{$wpdb->posts}.post_modified {$wp_query->get('order')}";
// 			if ($wp_query->get('orderby') == $this->table_columns['date'])		 		$orderby_statement = "{$wpdb->posts}.post_date {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['learnout_author'])	$orderby_statement = "U.user_login {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_FW']) 			$orderby_statement = "L.level_FW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_PW']) 			$orderby_statement = "L.level_PW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['level_KW']) 			$orderby_statement = "L.level_KW {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['no_of_items'])		$orderby_statement = "no_of_items {$wp_query->get('order')}";
		}
	
		return $orderby_statement;
	}
	
	

	
	public function WPCB_post_row_actions($actions, $post){
	
		if ($post->post_type != $this->type) return $actions;
		
		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
		$actions['view'] = "<a href='admin.php?page=view_learnout&learnoutid={$post->ID}'>View</a>"; // add "View"
		
		return $actions;
	}
	
	
	
		
		
	function WPCB_process_bulk_action() {
	
	
		if ($_REQUEST['post_type'] != $this->type) return;
		$wp_list_table = _get_list_table('WP_Posts_List_Table');

		// View Learning Outcomes
		if ($wp_list_table->current_action() == 'view') {
			$sendback = add_query_arg( 'learnoutids', $_REQUEST['post'], 'admin.php?page=view_learnout' );
			wp_redirect($sendback);
			exit();
		}
		
		// View Items / Add Items to Basket
		if (($wp_list_table->current_action() == 'view_items') || ($wp_list_table->current_action() == 'add_to_basket')) {
			
			// get associated items
			$postids = $_REQUEST['post'];
			if (!is_array($postids)) $postids = [$postids];
			$itemids = array();
			foreach ($postids as $learnout_id) {
				$itemids = array_merge ($itemids, DB_Item::loadAllItemIdsForLearnOut($learnout_id));
			}
			
			// Add Items to Basket
			if ($wp_list_table->current_action() == 'add_to_basket') {
				EAL_ItemBasket::add(array_unique($itemids));
			} 
			
			// View Items
			if (($wp_list_table->current_action() == 'view_items')) {
				$sendback = add_query_arg( 'itemids', array_unique($itemids), 'admin.php?page=view_item' );
				wp_redirect($sendback);
				exit();
			}
		}
	
	}	

	// FIXME: implement
	public function WPCB_post_updated_messages ( $messages ) { } 
		
	
}

?>