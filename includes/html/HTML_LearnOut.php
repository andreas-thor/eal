<?php

require_once (__DIR__ . "/../eal/EAL_LearnOut.php");


class HTML_Learnout extends HTML_Object {
	
	protected $learnout;
	protected $buttons_verb;
	
	function __construct(EAL_LearnOut $learnout) {
		$this->learnout = $learnout;
		
		// list of super verbs
		$this->buttons_verb = array (
			1 => array ("auflisten", "auswählen", "beschriften", "identifizieren", "nennen"),
			2 => array ("begründen", "Beispiele geben", "beschreiben", "erklären", "klassifizieren", "konvertieren", "schätzen", "transferieren", "übersetzen", "verallgemeinern", "zusammenfassen"),
			3 => array ("ändern", "anwenden", "beantragen", "berechnen", "bestimmen", "durchführen", "prüfen", "testen",  "übertragen", "verwenden", "vorbereiten", "zeigen"),
			4 => array ("analysieren", "gegenüberstellen", "kategorisieren", "priorisieren", "strukturieren", "unterscheiden", "unterteilen", "vergleichen", "vorhersagen"),
			5 => array ("bewerten", "diskutieren", "entscheiden", "interpretieren", "kritisieren", "verteidigen"),
			6 => array ("aufbauen", "erstellen", "gestalten", "kombinieren", "konzipieren", "modellieren", "produzieren", "überarbeiten", "umgestalten")
		);
	}
	 
	
	
	public function metaboxTopic () {
		global $post;
		post_categories_meta_box( $post, array ("id" => "WPCB_mb_taxonomy", "title" => "", "args" => array ( "taxonomy" => $this->learnout->getDomain() ) ) );
	}
	
	public function printTopic (bool $isEditable, string $prefix = "") {
		parent::printTopicObject($this->learnout->getDomain(), $this->learnout->getId(), $isEditable, $prefix);
	}
	
	
	public function metaboxDescription () {
		
		$this->printEditor('learnout_description', $this->learnout->description);
?>

		<script>
			function showTermButtons (j) {
				j("#eal_topicterms").empty()		
				// search all checked terms incl. their ancestors
				j("#topic-all").find ("input[type='checkbox']").filter(":checked").parentsUntil(j( "#topic-all" )).filter ("li").children("label").each (function (i, e) {
					// add term button
					j("#eal_topicterms").append (
							"<a style='margin:3px' class='button' onclick=\"tinyMCE.editors['learnout_description'].execCommand( 'mceInsertContent', false, '" + e.childNodes[1].textContent + "');\">" + e.childNodes[1].textContent + "</a>");
				})
			}
				
			jQuery(document).ready( function() {
		
				var j = jQuery.noConflict();
				showTermButtons(j);	
			
				// add "on change" handler to all terms (input-checkbox); both in tab "All" as well as "Most used"
				j("#topic-all").add(j("#topic-pop")).change(function() { showTermButtons (j); });
			});
		</script>
		
		<!-- div area where optic terms will be added -->
		<div id="eal_topicterms" style="margin:10px"></div>
		
		<!--  area for super verbs -->
		<div id="eal_superverbs" style="margin:10px">
		<?php foreach ($this->buttons_verb as $level => $terms) { ?>
			<div style="display:<?=($this->learnout->getLevel()->hasLevel($level) ? 'block' : 'none') ?>">
			<?php 
				foreach ($terms as $t) { 
					$tname = htmlentities($t, ENT_SUBSTITUTE, 'ISO-8859-1');
			?>
					<a style="margin:3px" class="button" onclick="tinyMCE.editors['learnout_description'].execCommand( 'mceInsertContent', false, '<?=$tname?>');">
						<?=$tname?>
					</a>
			<?php 
				} 
			?>
		
			</div>
		
		<?php } ?>
		</div>
<?php 		
	}
	
	public function printDescription (bool $isImport, string $prefix="") {
		?>
		<div>
			<?php if ($isImport) { ?>
				<input 
					type="hidden" 
					name="<?php echo $prefix ?>learnout_description" 
					value="<?php echo htmlentities($this->learnout->description, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>" />			
			<?php } ?> 
			<?php echo wpautop($this->learnout->description) ?>
		</div>
<?php 		
	}
	
	
	
	public function metaboxLevel () {
		$this->printLevel(TRUE);
	}
	
	public function printLevel (bool $isEditable, string $prefix="") {
?>
		<script>
			// callback javascript function is called when a new level is clicked --> matching verbs are shown
			function showSuperVerbs (e, levIT, levITs, levLO, levLOs) {
				var j = jQuery.noConflict();
				j(document).find("#eal_superverbs").find("div").hide();
				j(document).find("#eal_superverbs").find("div:eq(" + (levIT-1) + ")").show();
			}
		</script>

<?php
		$callback = ($isEditable) ? "showSuperVerbs" : "";
		
		// FIXME: Ist das hier richtig, dass nochmal item an prefix angehangen wird???
		parent::printLevelObject ($prefix . "learnout", $this->learnout->getLevel(), NULL, !$isEditable, FALSE, $callback);
		
	}
	
	
	
}
?>