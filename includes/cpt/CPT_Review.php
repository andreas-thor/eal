<?php

// TODO: Delete Review (in POst Tabelle) --> löschen in Review-Tabelle

require_once ("CPT_Object.php");
require_once (__DIR__ . "/../eal/EAL_Review.php");

class CPT_Review extends CPT_Object {

	public function __construct() {
	
		$this->type = "review";
		$this->label = "Item Review";
		$this->menu_pos = -1;
		$this->cap_type = $this->type;
		$this->dashicon = "dashicons-admin-comments";
		$this->supports = array('');
		$this->taxonomies = array();
		
		$this->table_columns = array (
			'cb' => '<input type="checkbox" />', 
			'review_title' => 'Title',
			'item_id' => 'Item ID', 
			'last_modified' => 'Date', 
			'review_author' => 'Author Review', 
			'item_author' => 'Author Item', 
			'score' => 'Score', 
			'change_level' => 'Level', 
			'overall' => 'Overall'
		);
		
		$this->bulk_actions = array (
			'view' => 'View Reviews',
			'trash' => 'Trash Reviews'
		);
	}
	

	
	public function addHooks() {
		parent::addHooks();
		add_action ("save_post_{$this->type}", array ('CPT_Review', 'save_post'), 10, 2);
	}
	
	public static function save_post (int $post_id, WP_Post $post) {
		
		if ($post->post_type != EAL_Review::getType()) return;
		
		$item_id = intval ($_REQUEST['item_id'] ?? -1);
		$review = ($post->post_status === 'auto-draft') ? new EAL_Review($post_id, $item_id) : EAL_Review::createFromArray($post_id, $_REQUEST);
		DB_Review::saveToDB($review);
	}
	
	
	
	public function WPCB_post_row_actions($actions, $post){

		if ($post->post_type != $this->type) return $actions;
		
		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
		$actions['view'] = "<a href='admin.php?page=view_review&reviewid={$post->ID}'>View</a>"; // add "View"
		
		if (!RoleTaxonomy::canEditReviewPost($post)) {		// "Edit" & "Trash" only if editable by user
			unset ($actions['edit']);
			unset ($actions['trash']);
		}
		
		return $actions;
	}
	
	


	
	function WPCB_process_bulk_action() {
	
		if ($_REQUEST['post_type'] != $this->type) return;
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
	
		// View Reviews
		if ($wp_list_table->current_action() == 'view') {
			$sendback = add_query_arg( 'reviewids', $_REQUEST['post'], 'admin.php?page=view_review' );
			wp_redirect($sendback);
			exit();
		}
	}
	
	

	
	public function WPCB_register_meta_box_cb () {
	
		global $review, $post, $item;
		parent::WPCB_register_meta_box_cb();
		
		$review = DB_Review::loadFromDB($post->ID);
		
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if (($domain["name"] != "") && ($review->getItem()->getDomain() != $domain["name"])) {
			wp_die ("Reviewed item does not belong to your current domain!");
		}
		
		// check for edit capabilities
		if (!RoleTaxonomy::canEditReviewPost($post)) {
			wp_die ("You are not allowed to edit this review!");
		}
		
		?>
		<!-- remove visibility etc. -->
		<style> #minor-publishing { display: none; } </style>  
		
		<!-- remove "Add new Review -->
		<script type="text/javascript">
			jQuery(document).ready( function($) { 
				$(".wrap a.page-title-action")[0].remove();
			});
		</script>

		<?php 
		
		add_meta_box('mb_item', 'Item: ' . $review->getItem()->getTitle(), array ($review->getHTMLPrinter(), 'metaboxItem'), $this->type, 'normal', 'default' );
		add_meta_box('mb_score', 'Fall- oder Problemvignette, Aufgabenstellung und Antwortoptionen', array ($review->getHTMLPrinter(), 'metaboxScore'), $this->type, 'normal', 'default' );
		add_meta_box('mb_feedback', 'Feedback', array ($review->getHTMLPrinter(), 'metaboxFeedback'), $this->type, 'normal', 'default');
		
		
		add_meta_box('mb_overall', 'Revisionsurteil', array ($review->getHTMLPrinter(), 'metaboxOverall'), $this->type, 'side', 'default');
		add_meta_box('mb_learnout', 'Learning Outcome', array ($review->getHTMLPrinter(), 'metaboxLearningOutcome'), $this->type, 'side', 'default');
		add_meta_box('mb_level', 'Anforderungsstufe', array ($review->getHTMLPrinter(), 'metaboxLevel'), $this->type, 'side', 'default');
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::getDomains()[$review->getItem()->getDomain()], array ($review->getHTMLPrinter(), metaboxTopic), $this->type, 'side', 'default');
		
	}
		
		
	
	

	
	
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array .= ", I.ID as item_id";
			$array .= ", I.title as item_title";
			$array .= ", I.type as item_type";
			$array .= ", UI.user_login as item_author";
			$array .= ", UI.id as item_author_id";
			$array .= ", UR.user_login as review_author";				
			$array .= ", UR.id as review_author_id";
			$array .= ", ABS(COALESCE(I.level_FW,0)-COALESCE(R.level_FW,0))+ABS(COALESCE(I.level_KW,0)-COALESCE(R.level_KW,0))+ABS(COALESCE(I.level_PW,0)-COALESCE(R.level_PW,0)) AS change_level";
			$array .= ", COALESCE (R.description_correctness, 0) AS description_correctness";
			$array .= ", COALESCE (R.description_relevance, 0) AS description_relevance";
			$array .= ", COALESCE (R.description_wording, 0) AS description_wording";
			$array .= ", COALESCE (R.question_correctness, 0) AS question_correctness";
			$array .= ", COALESCE (R.question_relevance, 0) AS question_relevance";
			$array .= ", COALESCE (R.question_wording, 0) AS question_wording";
			$array .= ", COALESCE (R.answers_correctness, 0) AS answers_correctness";
			$array .= ", COALESCE (R.answers_relevance, 0) AS answers_relevance";
			$array .= ", COALESCE (R.answers_wording, 0) AS answers_wording";
			$array .= ", R.overall";
				
		}
		return $array;
	}
	
	
	public function WPCB_posts_orderby($orderby_statement) {
		
		global $wp_query, $wpdb;
		
		if ($wp_query->query["post_type"] == $this->type) {
			
			switch ($wp_query->get('orderby')) {
				case $this->table_columns['review_title']: 	$orderby_statement = 'I.title'; break;
				case $this->table_columns['item_id']: 		$orderby_statement = 'I.ID'; break;
				case $this->table_columns['last_modified']: $orderby_statement = $wpdb->posts . '.post_modified'; break;
				case $this->table_columns['review_author']:	$orderby_statement = 'UR.user_login'; break;
				case $this->table_columns['item_author']: 	$orderby_statement = 'UR.user_login'; break;
				
				// score: missing is considered as ==2; compute negative sum, because 1 is best and 3 is worst
				case $this->table_columns['score']: 		$orderby_statement = '-(COALESCE (R.description_correctness, 2) + COALESCE (R.description_relevance, 2) + COALESCE (R.description_wording, 2) + COALESCE (R.question_correctness, 2) + COALESCE (R.question_relevance, 2) + COALESCE (R.question_wording, 2) + COALESCE (R.answers_correctness, 2) + COALESCE (R.answers_relevance, 2) + COALESCE (R.answers_wording, 2))'; break;
				case $this->table_columns['change_level']: 	$orderby_statement = 'change_level'; break;
				case $this->table_columns['overall']: 		$orderby_statement = 'R.overall'; break;
				default: 									$orderby_statement = $wpdb->posts . '.post_modified';	// default: last modified
			}
			$orderby_statement .= ' ' . $wp_query->get('order');
		}
		
		return $orderby_statement;
	}
	

	
	
	public function WPCB_posts_join ($join, $checktype = TRUE) {
		global $wp_query, $wpdb;
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) { 
			$domain = RoleTaxonomy::getCurrentRoleDomain();
				
			$join .= " JOIN {$wpdb->prefix}eal_{$this->type} AS R ON (R.id = {$wpdb->posts}.ID) ";
			$join .= " JOIN {$wpdb->prefix}eal_item AS I ON (I.id = R.item_id " . ( ($domain["name"]!="") ? "AND I.domain = '" . $domain["name"] . "')" : ")");
			$join .= " JOIN {$wpdb->posts} AS postitem ON (I.id = postitem.id) ";
			$join .= " JOIN {$wpdb->users} UI ON (UI.id = postitem.post_author) ";
			$join .= " JOIN {$wpdb->users} UR ON (UR.id = {$wpdb->posts}.post_author) ";
		}
		return $join;
	}
	
	
	


	
	
	public function WPCB_posts_where($where, $checktype = TRUE) {
	
		global $wp_query, $wpdb;
	
		$where = parent::WPCB_posts_where($where, $checktype);
	
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			if (isset ($_REQUEST['item_id'])) 			$where .= " AND I.id = {$_REQUEST['item_id']}";
			if (isset ($_REQUEST['review_author'])) 	$where .= " AND UR.id = " . $_REQUEST['review_author'];
			if (isset ($_REQUEST['item_author'])) 		$where .= " AND UI.id = " . $_REQUEST['item_author'];
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
	
	
	
	
}

?>