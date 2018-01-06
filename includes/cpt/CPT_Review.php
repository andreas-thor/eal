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
	}
	

	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {

		parent::init(array_merge ($args, array ('supports' => false, 'taxonomies' => array())));
		
		// TODO: delete review
		add_filter('post_updated_messages', array ($this, 'WPCB_post_updated_messages') );
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
	
	
	function WPCB_add_bulk_actions() {
		
		global $post_type;
		if ($post_type != $this->type) return;
		
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				var htmlselect = ["action", "action2"];
				htmlselect.forEach(function (s, i, o) {
					jQuery("select[name='" + s + "'] > option").remove();
			        jQuery('<option>').val('bulk').text('<?php _e('[Bulk Actions]')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('view').text('<?php _e('View Reviews')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('trash').text('<?php _e('Trash Reviews')?>').appendTo("select[name='" + s + "']");
			      });
			});			    
	    </script>
		<?php
	}

	
	function WPCB_process_bulk_action() {
	
		if ($_REQUEST["post_type"] != $this->type) return;
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
	
		if ($wp_list_table->current_action() == 'view') {
			$sendback = add_query_arg( 'reviewids', $_REQUEST['post'], 'admin.php?page=view_review' );
			wp_redirect($sendback);
			exit();
		}
	}
	
	

	
	public function WPCB_register_meta_box_cb () {
	
		global $review, $post;
		
		$review = new EAL_Review();
		
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if (($domain["name"] != "") && ($review->getItem()->domain != $domain["name"])) {
			wp_die ("Reviewed item does not belong to your current domain!");
		}
		
		// check for edit capabilities
		if (!RoleTaxonomy::canEditReviewPost($post)) {
			wp_die ("You are not allowed to edit this review!");
		}
		
		
		?><style> #minor-publishing { display: none; } </style> <?php
		
		add_meta_box('mb_item', 'Item: ' . $review->getItem()->title, array ($this, 'WPCB_mb_item'), $this->type, 'normal', 'default' );
		add_meta_box('mb_learnout', 'Learning Outcome', array ($this, 'WPCB_mb_learnout'), $this->type, 'side', 'default', array ('learnout' => $review->getItem()->getLearnOut()));
		add_meta_box('mb_score', 'Fall- oder Problemvignette, Aufgabenstellung und Antwortoptionen', array ($this, 'WPCB_mb_score'), $this->type, 'normal', 'default' );
		add_meta_box('mb_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'normal', 'default', array ('level' => $review->level, 'prefix' => 'review', 'default' => $review->getItem()->level, 'background' => 1 ));
		add_meta_box('mb_feedback', 'Feedback', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'review_feedback', 'value' => $review->feedback));
		add_meta_box('mb_overall', 'Revisionsurteil', array ($this, 'WPCB_mb_overall'), $this->type, 'side', 'default');
		
	}
		
		
	
	public function WPCB_mb_item ($post, $vars) {
	
		global $review;
		if (!is_null($review->getItem())) {
			$html = HTML_Item::getHTML_Item($review->getItem(), HTML_Object::VIEW_REVIEW);
			echo $html;
		}
	}
	
	
	public function WPCB_mb_score ($post, $vars) {
		
		global $review;
		print (HTML_Review::getHTML_Score($review, HTML_Object::VIEW_EDIT));
	}
	
	public function WPCB_mb_level ($post, $vars) {

		global $review;
		print (HTML_Review::getHTML_Level($review, HTML_Object::VIEW_EDIT));
	}
	
	

	public function WPCB_mb_overall ($post, $vars) {
	
		?>
		<script>
			function setAccept (val) {
				var $ = jQuery.noConflict();
				if (val==1) {
					if (confirm('Sollen alle Bewertungen auf "gut" gesetzt werden?')) {
						$(document).find("#mb_score").find("input").each ( function() {
		 					if (this.value==1) this.checked = true;
						});
					}
				}
			}
		</script>
		<?php
		
		global $review;
		print (HTML_Review::getHTML_Overall($review, HTML_Object::VIEW_EDIT, "", "setAccept(this.value);"));
	}
	
	
	public function WPCB_mb_learnout ($post, $vars) {
	
		global $review;
		print (HTML_Item::getHTML_LearningOutcome($review->getItem(), HTML_Object::VIEW_REVIEW));
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
	
	
	
	
	
	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query, $wpdb;
	
		// 		$orderby_statement = parent::WPCB_posts_orderby($orderby_statement);
	
		if ($wp_query->query["post_type"] == $this->type) {
			
			// default: last modified DESC
			$orderby_statement = "{$wpdb->posts}.post_modified DESC";
			
			if ($wp_query->get( 'orderby' ) == "Title")		 	$orderby_statement = "I.title " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == $this->table_columns['last_modified'])		$orderby_statement = "{$wpdb->posts}.post_modified {$wp_query->get('order')}";
// 			if ($wp_query->get( 'orderby' ) == "Date")		 	$orderby_statement = "{$wpdb->posts}.post_date " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Type") 			$orderby_statement = "I.type " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Author Review")	$orderby_statement = "UR.user_login " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Author Item") 	$orderby_statement = "UI.user_login " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Level") 		$orderby_statement = "change_level " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Overall") 		$orderby_statement = "R.overall " . $wp_query->get( 'order' );
		}
	
		return $orderby_statement;
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