<?php

require_once("CPT_Object.php");
require_once(__DIR__ . "/../eal/EAL_LearnOut.php");

class CPT_LearnOut extends CPT_Object {
	
	public function __construct() {
	
		$this->type = "learnout";
		$this->label = "Learn. Outcome";
		$this->menu_pos = 0;
		$this->cap_type = $this->type;
		$this->dashicon = "dashicons-welcome-learn-more";
	
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
	}	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {
		parent::init(array_merge ($args, array ('supports' => array( 'title'))));
	}
	
	
	

	


	
	public function WPCB_register_meta_box_cb () {
	
		global $learnout;
		$learnout = new EAL_LearnOut();
	
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if (($domain["name"] != "") && ($learnout->domain != $domain["name"])) {
			wp_die ("Learning outcome  does not belong to your current domain!");
		}
		global $post;
		
		?>  <style> #minor-publishing { display: none; } </style> <?php
		
		add_meta_box('mb_description', 'Beschreibung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'learnout_description', 'value' => $learnout->description) );
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $learnout->level, 'prefix' => 'learnout'));
		add_meta_box('mb_item_taxonomy', RoleTaxonomy::getDomains()[$learnout->domain], array ($this, 'WPCB_mb_taxonomy'), $this->type, 'side', 'default', array ( "taxonomy" => $learnout->domain ));
		
		
		
		// term selections --> show buttons for text modules 	
?>
<script>

	function showTermButtons (j) {
		j("#eal_topicterms").empty()		
		// search all checked terms incl. their ancestors
		j("#topic-all").find ("input[type='checkbox']").filter(":checked").parentsUntil(j( "#topic-all" )).filter ("li").children("label").each (function (i, e) {
			// add term button
			j("#eal_topicterms").append ("<a style='margin:3px' class='button' onclick=\"addTermToEditor('" + e.childNodes[1].textContent + "');\">" + e.childNodes[1].textContent + "</a>");
		})
	}
		
	function codeAddress() {
	
		var j = jQuery.noConflict();
		showTermButtons(j)	
	
		// add "on change" handler to all terms (input-checkbox); both in tab "All" as well "Must used"
		j("#topic-all").add(j("#topic-pop")).change(function() { showTermButtons (j); });
	}
	
	window.onload = codeAddress;
</script>
<?php	
		
		
	}	

	
	function wpdocs_theme_name_scripts() {
		wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
	}
	
	
	public function WPCB_mb_taxonomy ($post, $vars) {
		post_categories_meta_box( $post, array ("id" => "WPCB_mb_taxonomy", "title" => "", "args" => $vars['args']) );
	}
	
	
	public function WPCB_mb_editor ($post, $vars) {
	
		global $learnout;
		parent::WPCB_mb_editor ($post, $vars);

		
		printf("<div id='eal_topicterms' style='margin:10px'></div>");
		
		
		// Verb button click --> insert verb into current position of editor
?>
		<script>
			
			
			function addTermToEditor (t) {
				var j = jQuery.noConflict();
				j(document).find("#mb_description").find("textarea").each ( function() {
 					tinyMCE.activeEditor.execCommand( 'mceInsertContent', false, t );
				});
			}
		</script>


<?php		
		
		$verbs = array ( 
			1 => array ("auflisten", "auswählen", "beschriften", "identifizieren", "nennen"),
			2 => array ("begründen", "Beispiele geben", "beschreiben", "erklären", "klassifizieren", "konvertieren", "schätzen", "transferieren", "übersetzen", "verallgemeinern", "zusammenfassen"),
			3 => array ("ändern", "anwenden", "beantragen", "berechnen", "bestimmen", "durchführen", "prüfen", "testen",  "übertragen", "verwenden", "vorbereiten", "zeigen"),
			4 => array ("analysieren", "gegenüberstellen", "kategorisieren", "priorisieren", "strukturieren", "unterscheiden", "unterteilen", "vergleichen", "vorhersagen"),
			5 => array ("bewerten", "diskutieren", "entscheiden", "interpretieren", "kritisieren", "verteidigen"),
			6 => array ("aufbauen", "erstellen", "gestalten", "kombinieren", "konzipieren", "modellieren", "produzieren", "überarbeiten", "umgestalten")
		);

		printf("<div id='eal_superverbs' style='margin:10px'>");
		foreach ($verbs as $level => $terms) {
			// show only verbs that matches the current LO level
			printf ("<div style='display:%s'>", (($learnout->level["FW"]==$level) || ($learnout->level["PW"]==$level) || ($learnout->level["KW"]==$level)) ? 'block' : 'none');
			foreach ($terms as $t) {
				printf ("<a style='margin:3px' class='button' onclick=\"addTermToEditor('%s');\">%s</a>", htmlentities($t, ENT_SUBSTITUTE, 'ISO-8859-1'), htmlentities($t, ENT_SUBSTITUTE, 'ISO-8859-1'));
			}
			printf ("</div>");
		}
		printf ("</div>");
	}
	

	public function WPCB_mb_level ($post, $vars) {
		global $learnout;
		print (HTML_Learnout::getHTML_Level($learnout, HTML_Object::VIEW_EDIT));
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
	
		global $wp_query, $wpdb;
	
		if (($wp_query->query["post_type"] == $this->type) || (!$checktype)) {
			if (isset ($_REQUEST['learnout_author'])) 	$where .= " AND {$wpdb->posts}.post_author 	= " . $_REQUEST['learnout_author'];
			if (isset ($_REQUEST['level_FW']) && ($_REQUEST['level_FW']>0)) 			$where .= " AND L.level_FW 	= " . $_REQUEST['level_FW'];
			if (isset ($_REQUEST['level_PW']) && ($_REQUEST['level_PW']>0)) 			$where .= " AND L.level_PW 	= " . $_REQUEST['level_PW'];
			if (isset ($_REQUEST['level_KW']) && ($_REQUEST['level_KW']>0)) 			$where .= " AND L.level_KW	= " . $_REQUEST['level_KW'];
			
			
			if (isset ($_REQUEST['taxonomy']) && ($_REQUEST['taxonomy']>0))	{
			
				$children = get_term_children( $_REQUEST['taxonomy'], RoleTaxonomy::getCurrentRoleDomain()["name"] );
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
	
	
	
	function add_bulk_actions() {
	
		global $post_type;
		if ($post_type != $this->type) return;
	
?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
	
					var htmlselect = ["action", "action2"];
						    	
					htmlselect.forEach(function (s, i, o) {
							  		
						jQuery("select[name='" + s + "'] > option").remove();
				        jQuery('<option>').val('-1').text('<?php _e('[Bulk Actions]')?>').appendTo("select[name='" + s + "']");
				        jQuery('<option>').val('view_learnout').text('<?php _e('View Learning Outcomes')?>').appendTo("select[name='" + s + "']");
				        jQuery('<option>').val('trash').text('<?php _e('Trash Learning Outcomes')?>').appendTo("select[name='" + s + "']");
				        jQuery('<option>').val('add_to_basket').text('<?php _e('Add Items To Basket')?>').appendTo("select[name='" + s + "']");
				      });
				});			    
		    </script>
<?php

	}
		
		
	
	
		
	
// 	public function WPCB_post_row_actions($actions, $post) {
	
// 		if ($post->post_type != $this->type) return $actions;
	
// 		unset ($actions['inline hide-if-no-js']);			// remove "Quick Edit"
// 		$actions['view'] = "<a href='admin.php?page=view&itemid={$post->ID}'>View</a>"; // add "View"
// 		return $actions;
// 	}
	
	
}

?>