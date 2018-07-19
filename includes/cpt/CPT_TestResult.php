<?php

require_once 'CPT_Object.php';
require_once __DIR__ . '/../eal/EAL_TestResult.php';
require_once __DIR__ . '/../db/DB_TestResult.php';


class CPT_TestResult extends CPT_Object {
	
	public function __construct() {
	
		$this->type = "testresult";
		$this->label = "Result";
		$this->menu_pos = 0;
		$this->cap_type = $this->type;
		$this->dashicon = "dashicons-feedback";
		$this->supports = array('title');
		$this->taxonomies = array(RoleTaxonomy::getCurrentDomain());
		
		$this->table_columns = array (
			'cb' => '<input type="checkbox" />',
			'result_title' => 'Title',
			'last_modified' => 'Date',
			'no_of_items_in_testresult' => 'Items',
			'no_of_users_in_testresult' => 'Users'
		);
		
		$this->bulk_actions = array (
			'view' => 'View Test Results', 
			'trash' => 'Trash Test Results'
		);
	}	
	

	
	public function addHooks() {
		parent::addHooks();
		add_action ("save_post_{$this->type}", array ('CPT_TestResult', 'save_post'), 10, 2);
	}
	

	public static function save_post (int $post_id, WP_Post $post) {
		
		if ($post->post_type != EAL_TestResult::getType()) return;
		
		global $testresultToImport;
		
		$testresult = isset ($testresultToImport) ? $testresultToImport : EAL_TestResult::createFromArray($post_id, $_POST);
		
		$testresult->setId($post_id);
		DB_TestResult::saveToDB($testresult);	
	}
	
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $post;
		
		parent::WPCB_register_meta_box_cb();
		
		$testresult = DB_TestResult::loadFromDB($post->ID); 
		$domain = RoleTaxonomy::getCurrentDomain();
		if (($domain != "") && ($testresult->getDomain() != $domain)) {
			wp_die ("Learning outcome  does not belong to your current domain!");
		}
		
		// remove visibility and status options
		print ('<style> #minor-publishing { display: none; } </style>');
		
		add_meta_box('mb_description', 'Beschreibung', array ($testresult->getHTMLPrinter(), 'metaboxDescription'), $this->type, 'normal', 'default' );
		add_meta_box('mb_user_item_table', 'User-Item-Table', array ($testresult->getHTMLPrinter(), 'metaboxUserItemTable'), $this->type, 'normal', 'default' );
		add_meta_box('mb_item_item_table', 'Inter-Item-Correlation', array ($testresult->getHTMLPrinter(), 'metaboxItemItemTable'), $this->type, 'normal', 'default' );
		add_meta_box('mb_correlation_by_itemtype', 'Item-Correlation By Item Type', array ($testresult->getHTMLPrinter(), 'metaboxCorrelationByItemType'), $this->type, 'normal', 'default' );
		add_meta_box('mb_correlation_by_dimension', 'Item-Correlation By Dimension', array ($testresult->getHTMLPrinter(), 'metaboxCorrelationByDimension'), $this->type, 'normal', 'default' );
		add_meta_box('mb_correlation_by_level', 'Item-Correlation By Level', array ($testresult->getHTMLPrinter(), 'metaboxCorrelationByLevel'), $this->type, 'normal', 'default' );
	}	

	
	function wpdocs_theme_name_scripts() {
		wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
	}
	
	
	
	
	
	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array .= ", R.title AS result_title";
			$array .= ", {$wpdb->posts}.post_author AS result_author_id";
			$array .= ", U.user_login AS result_author";
			$array .= ", R.no_of_items AS no_of_items_in_testresult";
			$array .= ", R.no_of_users AS no_of_users_in_testresult";
		}
		return $array;
	}
	
	public function WPCB_posts_join ($join, $checktype=TRUE) {
		
		global $wp_query, $wpdb;
		
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			$domain = RoleTaxonomy::getCurrentDomain();
			$join .= " JOIN {$wpdb->prefix}eal_{$this->type} R ON (R.id = {$wpdb->posts}.ID " . (($domain!="") ? "AND R.domain = '" . $domain . "')" : ")");
			$join .= " JOIN {$wpdb->users} U ON (U.id = {$wpdb->posts}.post_author) ";
		}
		return $join;
	}
	

	public function WPCB_posts_where($where, $checktype = TRUE) {
		return $where;
	}
	
	
	public function WPCB_posts_orderby($orderby_statement) {
		global $wpdb, $wp_query;
		
		if ($wp_query->query["post_type"] == $this->type) {
			
			// default: last modified DESC
			$orderby_statement = "{$wpdb->posts}.post_modified DESC";
			
			if ($wp_query->get('orderby') == $this->table_columns['result_title'])	$orderby_statement = "result_title {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['last_modified'])		$orderby_statement = "{$wpdb->posts}.post_modified {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['no_of_items_in_testresult'])		$orderby_statement = "no_of_items {$wp_query->get('order')}";
			if ($wp_query->get('orderby') == $this->table_columns['no_of_users_in_testresult'])		$orderby_statement = "no_of_users {$wp_query->get('order')}";
		}
		
		return $orderby_statement;
	}
	
	

	
	public function WPCB_post_row_actions($actions, $post){
	
		if ($post->post_type != $this->type) return $actions;
		
		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
// 		$actions['view'] = "<a href='admin.php?page=view_learnout&learnoutid={$post->ID}'>View</a>"; // add "View"
		
		return $actions;
	}
	
	
	
		
		
	function WPCB_process_bulk_action() {
	
	}	

	// FIXME: implement
	public function WPCB_post_updated_messages ( $messages ) { } 
		
	
}

?>