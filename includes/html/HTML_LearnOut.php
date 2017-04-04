<?php

require_once (__DIR__ . "/../eal/EAL_LearnOut.php");


class HTML_Learnout {
	
	
	public static function getHTML_Metadata (EAL_LearnOut $learnout) {
	
		// Id
		$res = sprintf ('<div class="misc-pub-section misc-pub-post-status">(ID=%d)</div><br/>', $learnout->id);
		
		// Level-Table
		$res .= sprintf ('
			<div id="mb_learnout" class="postbox ">
		
				<h2 class="hndle"><span>Anforderungsstufe</span></h2>
				<div class="inside">%s</div>
			</div>', HTML_Object::getLevelHTML("lo" . $learnout->id, $learnout->level, null, "disabled", 0, ''));
		
		// Taxonomy Terms: Name of Taxonomy and list of terms (if available)
		$terms = wp_get_post_terms( $learnout->id, $learnout->domain, array("fields" => "names"));
		$termCheckboxes = "";
		foreach ($terms as $t) {
			$termCheckboxes .= sprintf ("<input type='checkbox' checked onclick='return false;'>%s<br/>", $t);
		}
		
		$res .= sprintf ('
				<div class="postbox ">
					<h2 class="hndle"><span>%s</span></h2>
					<div class="inside">%s</div>
				</div>',
				RoleTaxonomy::getDomains()[$learnout->domain],
				$termCheckboxes);
	
		return $res;
	}

	
	public static function getHTML_LearnOut (EAL_LearnOut $learnout) {
	
		return sprintf ("
 				<div>
 					<div style='background-color:#AFDB5F; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>
 						<div>%s</div>
 					</div>
 				</div>",
				wpautop(stripslashes($learnout->description))
		);
			
	}
	
}
?>