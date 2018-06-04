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
		$this->taxonomies = array(RoleTaxonomy::getCurrentRoleDomain()["name"]);
		
		$this->table_columns = array (
			'cb' => '<input type="checkbox" />',
			'result_title' => 'Title',
			'last_modified' => 'Date'
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
		
		$testresult = ($post->post_status === 'auto-draft') ? new EAL_TestResult($post_id) : EAL_TestResult::createFromArray($post_id, $_POST);
		DB_TestResult::saveToDB($testresult);	
	}
	
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $post;
		
		parent::WPCB_register_meta_box_cb();
		
		$testresult = DB_TestResult::loadFromDB($post->ID); 
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if (($domain["name"] != "") && ($testresult->getDomain() != $domain["name"])) {
			wp_die ("Learning outcome  does not belong to your current domain!");
		}
		
		// remove visibility and status options
		print ('<style> #minor-publishing { display: none; } </style>');
		
		add_meta_box('mb_description', 'Beschreibung', array ($testresult->getHTMLPrinter(), 'metaboxDescription'), $this->type, 'normal', 'default' );
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
			$array .= ", (SELECT COUNT(*) FROM {$wpdb->prefix}eal_item AS X JOIN {$wpdb->posts} AS Y ON (X.id = Y.ID) WHERE Y.post_parent=0 AND X.learnout_id = L.id AND Y.post_status IN ('publish', 'pending', 'draft')) AS no_of_items";
		}
		return $array;
	}
	
	public function WPCB_posts_join ($join, $checktype=TRUE) {
		
		global $wp_query, $wpdb;
		
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			$domain = RoleTaxonomy::getCurrentRoleDomain();
			$join .= " JOIN {$wpdb->prefix}eal_{$this->type} L ON (L.id = {$wpdb->posts}.ID " . (($domain["name"]!="") ? "AND L.domain = '" . $domain["name"] . "')" : ")");
			$join .= " JOIN {$wpdb->users} U ON (U.id = {$wpdb->posts}.post_author) ";
		}
		return $join;
	}
	

	public function WPCB_posts_where($where, $checktype = TRUE) {
		return $where;
	}
	
	
	public function WPCB_posts_orderby($orderby_statement) {
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