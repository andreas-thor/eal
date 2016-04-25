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
	
	
		add_meta_box('mb_description', 'Beschreibung', array ($this, 'WPCB_mb_editor'), $this->type, 'normal', 'default', array ('name' => 'learnout_description', 'value' => $learnout->description) );
		add_meta_box('mb_item_level', 'Anforderungsstufe', array ($this, 'WPCB_mb_level'), $this->type, 'side', 'default', array ('level' => $learnout->level, 'prefix' => 'learnout'));
		
		
	}	

	public function WPCB_mb_editor ($post, $vars) {
	
		parent::WPCB_mb_editor ($post, $vars);
		
?>
		<script>
			var $ = jQuery.noConflict();
			
			function addTermToEditor (t) {
				$(document).find("#mb_description").find("textarea").each ( function() {
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
			printf ("<div>");
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
		var $ =jQuery.noConflict();
		
		function showSuperVerbs (e, levIT, levITs, levLO, levLOs) {
			$(document).find("#eal_superverbs").find("div").hide();
			$(document).find("#eal_superverbs").find("div:eq(" + (levIT-1) + ")").show();
		}
	</script>
<?php		
			
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