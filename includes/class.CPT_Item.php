<?php

require_once("class.CPT_Object.php");
require_once("class.CLA_RoleTaxonomy.php");

class CPT_Item extends CPT_Object{
	
	
	public $table_columns = array (
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
		
		
		
		add_filter('post_updated_messages', array ($this, 'WPCB_post_updated_messages') );
		add_action('contextual_help', array ($this, 'WPCB_contextual_help' ), 10, 3);
		
		if ($this->type != "itembasket") {
			add_action ("save_post_revision", array ("eal_{$this->type}", 'save'), 10, 2);
		}
		
		add_filter ('wp_get_revision_ui_diff', array ($this, 'WPCB_wp_get_revision_ui_diff'), 10, 3 );
		
		add_filter('posts_search', array ($this ,'WPCB_post_search'), 10, 2);
		
		/* hide shortlink block */
		add_filter('get_sample_permalink_html', '__return_empty_string', 10, 5);
		add_filter('pre_get_shortlink', '__return_empty_string' );
	}


	

	

	public function WPCB_post_row_actions($actions, $post){
	
		if ($post->post_type != $this->type) return $actions;
	
// 		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
		$actions['view'] = "<a href='admin.php?page=view&itemid={$post->ID}'>View</a>"; // add "View"
	
		if (!RoleTaxonomy::canEditItemPost($post)) {		// "Edit" & "Trash" only if editable by user
			unset ($actions['edit']);
			unset ($actions['trash']);
		}
	
		return $actions;
	}
	

	
	function add_bulk_actions() {
	
		global $post_type;
//  		if ($post_type != $this->type) return;
	
?>
		<script type="text/javascript">
			jQuery(document).ready(function() {

				var htmlselect = ["action", "action2"];
					    	
				htmlselect.forEach(function (s, i, o) {
						  		
					jQuery("select[name='" + s + "'] > option").remove();
			        jQuery('<option>').val('bulk').text('<?php _e('[Bulk Actions]')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('view').text('<?php _e('View Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('trash').text('<?php _e('Trash Items')?>').appendTo("select[name='" + s + "']");

			        <?php if ($post_type == "itembasket") { ?> 
				        jQuery('<option>').val('remove_from_basket').text('<?php _e('Remove Items From Basket')?>').appendTo("select[name='" + s + "']");
					<?php } else { ?>
				        jQuery('<option>').val('add_to_basket').text('<?php _e('Add Items To Basket')?>').appendTo("select[name='" + s + "']");
					<?php } ?>

			        jQuery('<option disabled>').val('--').text('<?php _e('-----')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('mark').text('<?php _e('Mark Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('unmark').text('<?php _e('Unmark Items')?>').appendTo("select[name='" + s + "']");

			        jQuery('<option disabled>').val('--').text('<?php _e('-----')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('setpublished').text('<?php _e('Publish Items')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('setpending').text('<?php _e('Set Items To Pending Review')?>').appendTo("select[name='" + s + "']");
			        jQuery('<option>').val('setdraft').text('<?php _e('Revert Items To Draft')?>').appendTo("select[name='" + s + "']");
			        
			      });
			});			    
	    </script>
<?php

	}
		
	
	
	
	
	
	public function WPCB_register_meta_box_cb () {
	
		global $item, $post;
		
		// check for correct domain 
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if (($domain["name"] != "") && ($item->domain != $domain["name"])) {
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
		
		add_meta_box('mb_learnout', 'Learning Outcome', array ($this, 'WPCB_mb_learnout'), $this->type, 'normal', 'default', array ('learnout' => $item->getLearnOut()));
		add_meta_box('mb_description', 'Fall- oder Problemvignette', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_description', 'value' => $item->description) );
		add_meta_box('mb_question', 'Aufgabenstellung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'item_question', 'value' => $item->question));
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $item->level, 'default' => (($item->getLearnOut() == null) ? null : $item->getLearnOut()->level) ));
		add_meta_box("mb_{$this->type}_answers", "Antwortoptionen",	array ($this, 'WPCB_mb_answers'), $this->type, 'normal', 'default');
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::getDomains()[$item->domain], array ($this, 'WPCB_mb_taxonomy'), $this->type, 'side', 'default', array ( "taxonomy" => $item->domain ));
		add_meta_box('mb_item_note_flag', 'Note', array ($this, 'WPCB_mb_note_flag'), $this->type, 'normal', 'default', array ('note' => $item->note, 'flag' => $item->flag ));
		
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
	
	public function WPCB_mb_note_flag ($post, $vars) {
	
		$flag = $vars['args']['flag'] > 0 ? $vars['args']['flag'] : 0;
		
		// we dynamically set the value of $POST["post_content"] to make sure that we have revision
		printf ("<input type='hidden' id='post_content' name='post_content'  value='%s'>", microtime());
		
		printf ("<div class='misc-pub-section'>");
		printf ("<input type='checkbox' name='item_flag' value='1' %s>", $flag==1 ? "checked" : "");
		printf ("<input type='text' name='item_note' value='%s'>", $vars['args']['note']);
		printf ("</div>");
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
			if (isset ($_REQUEST["post_status"]) && ($_REQUEST['post_status'] != "0")) 		$where .= " AND {$wpdb->posts}.post_status = '" . $_REQUEST['post_status'] . "'";
			
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
			
				$where = str_replace( "{$wpdb->posts}.post_type = 'itembasket'", "{$wpdb->posts}.post_type LIKE 'item%'", $where);
				
				$basket = RoleTaxonomy::getCurrentBasket(); // get_user_meta(get_current_user_id(), 'itembasket', true);
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
		
		$search = sprintf (' AND ( L.Title LIKE "%%%1$s%%" OR I.note LIKE "%%%1$s%%" OR I.Title LIKE "%%%1$s%%" OR U.user_login LIKE "%%%1$s%%" )', $wpquery->query['s']);
		return $search;
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
	
	
	private static function getHTML_TopicHierarchy ($namePrefix, $terms, $parent, $selected) {
		
		$res .= "";
		foreach ($terms as $term) {
			if ($term->parent != $parent) continue;
			
			$res .= sprintf ('
				<li id="%4$s-%1$d">
					<label class="selectit">
					<input value="%1$d" type="checkbox" %3$s name="%4$s_taxonomy[]" id="in-%4$s-%1$d"> %2$s</label>
					<ul class="children">%5$s</ul>
				</li>',  
				$term->term_id, $term->name, in_array ($term->term_id, $selected)?"checked":"", 
				$namePrefix,	
				CPT_Item::getHTML_TopicHierarchy($namePrefix, $terms, $term->term_id, $selected));
		}
		
		return $res;		
	}

	public static function getHTML_Metadata (EAL_Item $item, $editable, $namePrefix) {
	
		// Status and Id 
		$res = sprintf ("<div>%s (%d)</div><br/>", $item->getStatusString(), $item->id);
		
		// Learning Outcome (Title + Description), if available
		$learnout = $item->getLearnOut();
		if ($editable) {
			$res .= sprintf ("<div>%s</div>", EAL_LearnOut::getListOfLearningOutcomes($learnout == null ? 0 : $learnout->id, $namePrefix));
		} else {
			if (!is_null($learnout)) {
				$res .= sprintf ("<div><b>%s</b>: %s</div><br/>", $learnout->title, $learnout->description);
			}
		}
		
		// Level-Table
		$res .= sprintf ("<div>%s</div><br/>", CPT_Object::getLevelHTML($namePrefix, $item->level, (is_null($learnout) ? null : $learnout->level), $editable?"":"disabled", 1, ''));
			
		// Taxonomy Terms: Name of Taxonomy and list of terms (if available) 
		$res .= sprintf ("<div><b>%s</b>:", RoleTaxonomy::getDomains()[$item->domain]);
		if ($editable) {

			$res .= sprintf ('
				<div class="inside">
					<div class="categorydiv">
						<div id="topic-all" class="tabs-panel"><input type="hidden" name="%1$s_taxonomy[]" value="0">
							<ul id="topicchecklist" data-wp-lists="list:topic" class="categorychecklist form-no-clear">
							%2$s
							</ul>
						</div>
					</div>
				</div>', 
				$namePrefix, 
				CPT_Item::getHTML_TopicHierarchy($namePrefix, get_terms( array('taxonomy' => $item->domain, 'hide_empty' => false) ), 0, wp_get_post_terms( $item->id, $item->domain, array("fields" => "ids"))));
				
		} else {

			$terms = wp_get_post_terms( $item->id, $item->domain, array("fields" => "names"));
			if (count($terms)>0) {
				$res .= sprintf ("<div style='margin-left:1em'>");
				foreach ($terms as $t) {
					$res .= sprintf ("%s</br>", $t);
				}
				$res .= sprintf ("</div>");
			}
				
		}
		
		
		
		$res .= sprintf ("</div>");
		
		return $res;
	}
	
	
	
	
	public static function getHTML_Item (EAL_Item $item, $forReview = TRUE, $editableMeta = FALSE, $namePrefix = "") {
			
		$answers_html = ""; 
		switch (get_class($item)) {
			case 'EAL_ItemSC': $answers_html = CPT_ItemSC::getHTML_Answers($item, $forReview); break; 	
			case 'EAL_ItemMC': $answers_html = CPT_ItemMC::getHTML_Answers($item, $forReview); break; 	
		}
		
		if ($forReview) {
	
			// description
			$item_html  = sprintf ("<div>%s</div>", wpautop(stripslashes($item->description)));
				
			// question and answers
			$item_html .= sprintf ("<div style='background-color:F2F6FF; margin-top:2em; padding:1em;'>");
			$item_html .= sprintf ("%s", wpautop(stripslashes($item->question)));
			$item_html .= sprintf ("%s", $answers_html);
			$item_html .= sprintf ("</div>");
	
			return sprintf ("<div>%s</div>", $item_html);
	
		} else {
				
			// head line (incl. option to edit)
			$item_html  = sprintf ("
				<div onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\">
					<h1 style='display:inline'>%s</span></h1>
					<div style='display:none'>
						<span><a href=\"post.php?action=edit&post=%d\">Edit</a></span>
					</div>
				</div>", $item->title, $item->id);
	
			// description
			$item_html .= sprintf ("<div>%s</div>", wpautop(stripslashes($item->description)));
				
			// question and answers
			$item_html .= sprintf ("<div style='background-color:#F2F6FF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>");
			$item_html .= sprintf ("%s", wpautop(stripslashes($item->question)));
			$item_html .= sprintf ("%s", $answers_html);
			$item_html .= sprintf ("</div>");
				
				
			return sprintf ("
				<div id='poststuff'>
					<div id='post-body' class='metabox-holder columns-2'>
						<div class='postbox-container' id='postbox-container-2'>
							<div class='meta-box-sortables ui-sortable'>
								%s
							</div>
						</div>
						<div class='postbox-container' id='postbox-container-1'>
							<div style='background-color:#FFFFFF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>
								%s
							</div>
						</div>
					</div>
				</div>"
				, $item_html
				, CPT_Item::getHTML_Metadata($item, $editableMeta, $namePrefix));
				
		}
	}	



}

?>