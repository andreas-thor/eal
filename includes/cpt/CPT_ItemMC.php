<?php

require_once("CPT_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemMC.php");

class CPT_ItemMC extends CPT_Item {
	
	
	
	public function __construct() {
	
		parent::__construct();
	
		$this->type = "itemmc";
		$this->label = "Multiple Choice";
		$this->menu_pos = 0;
		$this->dashicon = "dashicons-forms";
		
		unset($this->table_columns["item_type"]);
	}
	
	
	public function addHooks() {
		
		parent::addHooks();
		
		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$this->type}", array ('EAL_ItemMC', save), 10, 2);
		add_action ("save_post_revision", array ('EAL_ItemMC', 'save'), 10, 2);
	}
	
	
	
	
	

	public function WPCB_wp_get_revision_ui_diff ($diff, $compare_from, $compare_to) {
	
		if (get_post ($compare_from->post_parent)->post_type != $this->type) return $diff;
		
		$eal_From = new EAL_ItemMC($compare_from->ID);
		$eal_To = new EAL_ItemMC($compare_to->ID);
	
		$diff[0] = HTML_Item::compareTitle($eal_From, $eal_To); 
		$diff[1] = HTML_Item::compareDescription($eal_From, $eal_To); 
		$diff[2] = HTML_Item::compareQuestion($eal_From, $eal_To); 
		$diff[3] = HTML_Item::compareLevel($eal_From, $eal_To);
		$diff[4] = HTML_ItemMC::compareAnswers($eal_From, $eal_To);
	
		return $diff;
	}	
	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = new EAL_ItemMC();
		parent::WPCB_register_meta_box_cb();
		
	}

	
	
	public function WPCB_mb_answers ($post, $vars) {
	
		global $item;
		print (HTML_ItemMC::getHTML_Answers($item, HTML_Object::VIEW_EDIT));
	}	
	
	
	public function WPCB_mb_question ($post, $vars, $buttons = array()) {
	
		parent::WPCB_mb_question ($post, $vars, array (
				"Wähle 1-3 aus 4" => "Wählen Sie mindestens eine, maximal drei aus den vier Antwortoptionen aus. ",
				"Wähle 1-4 aus 5" => "Wählen Sie mindestens eine, maximal vier aus den fünf Antwortoptionen aus. ",
				"Wähle 1-5 aus 6" => "Wählen Sie mindestens eine, maximal fünf aus den sechs Antwortoptionen aus. ",
				"Wähle korrekte" => "Wählen Sie die korrekte(n) aus den folgenden Antwortoptionen aus.",
				"Teilpunktbewertung" => "Punkte erhalten Sie für jede richtige Antwort (Teilpunktbewertung). "
		));
	
	
	}
	
	
	
}

	


?>