<?php

require_once("class.CPT_Object.php");
require_once("class.EAL_LearnOut.php");

class CPT_LearnOut extends CPT_Object {
	
	
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {
		
		$this->type = "learnout";
		$this->label = "Learn. Outcome";
		$this->menu_pos = 7;
		
		parent::init();
		
	}
	
	

	public function WPCB_register_meta_box_cb () {
	
		global $learnout;
		$learnout = new EAL_LearnOut();
		$learnout->load();
	
	
		global $post;
		
		add_meta_box('mb_description', 'Beschreibung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'learnout_description', 'value' => $learnout->description) );
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $learnout->level, 'prefix' => 'learnout'));
		
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
			1 => array ("auflisten", "ausw�hlen", "beschriften", "identifizieren", "nennen"),
			2 => array ("begr�nden", "Beispiele geben", "beschreiben", "erkl�ren", "klassifizieren", "konvertieren", "sch�tzen", "transferieren", "�bersetzen", "verallgemeinern", "zusammenfassen"),
			3 => array ("�ndern", "anwenden", "beantragen", "berechnen", "bestimmen", "durchf�hren", "pr�fen", "testen",  "�bertragen", "verwenden", "vorbereiten", "zeigen"),
			4 => array ("analysieren", "gegen�berstellen", "kategorisieren", "priorisieren", "strukturieren", "unterscheiden", "unterteilen", "vergleichen", "vorhersagen"),
			5 => array ("bewerten", "diskutieren", "entscheiden", "interpretieren", "kritisieren", "verteidigen"),
			6 => array ("aufbauen", "erstellen", "gestalten", "kombinieren", "konzipieren", "modellieren", "produzieren", "�berarbeiten", "umgestalten")
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
	
				
?>
		<script>
			
			
			function showSuperVerbs (e, levIT, levITs, levLO, levLOs) {
				var j = jQuery.noConflict();
				j(document).find("#eal_superverbs").find("div").hide();
				j(document).find("#eal_superverbs").find("div:eq(" + (levIT-1) + ")").show();
			}
		</script>
<?php		
		
		// callback javascript function is called when a new level is clicked --> matching verbs are shown
		$vars['args']['callback'] = 'showSuperVerbs';
		parent::WPCB_mb_level($post, $vars);
			
		
			
	}
		
	
	public function WPCB_manage_posts_columns($columns) {
		return array_merge(parent::WPCB_manage_posts_columns($columns), array('Items' => 'Items'));
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		return array_merge(parent::WPCB_manage_edit_sortable_columns($columns) , array('Items' => 'Items'));
	}
	
	
	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
		parent::WPCB_manage_posts_custom_column($column, $post_id);
	
		global $post;
	
		switch ( $column ) {
			case 'Items': 
	
				echo ("-1");
				echo ("<h1><a class='page-title-action' href='post-new.php?post_type=itemsc&learnout_id={$post->ID}'>Add&nbsp;New&nbsp;SC</a></h1>");
				echo ("<h1><a class='page-title-action' href='post-new.php?post_type=itemmc&learnout_id={$post->ID}'>Add&nbsp;New&nbsp;MC</a></h1>");
				break;
		}
	}
	
	
	
	// define the posts_fields callback
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array = parent::WPCB_posts_fields($array) . ", (-9) as reviews ";
		}
		return $array;
	}
	
	
	
	
}

?>