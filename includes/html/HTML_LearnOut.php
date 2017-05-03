<?php

require_once (__DIR__ . "/../eal/EAL_LearnOut.php");


class HTML_Learnout {
	
	
	public static function getHTML_Level (EAL_LearnOut $learnout, int $viewType, string $prefix = "") {
		
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
				
		$disabled = TRUE;
		$callback = "";
		switch ($viewType) {
			case HTML_Object::VIEW_EDIT:
				$disabled = FALSE;
				$callback = "showSuperVerbs";
				break;
			case HTML_Object::VIEW_IMPORT:
				$disabled = FALSE;
				break;
		}
		
		return HTML_Object::getHTML_Level($prefix . "learnout", $learnout->level, null, $disabled, FALSE, $callback);
		
	}
	
	
	
	public static function getHTML_Metadata (EAL_LearnOut $learnout, int $viewType = HTML_Object::VIEW_STUDENT, string $prefix = "") {
	
		$edit = ($learnout->id > 0) ? sprintf ('<span style="float: right; font-weight:normal" ><a href="post.php?action=edit&post=%d">Edit</a></span>', $learnout->id) : '';
		
		// Id
		$res = sprintf ('
			<div id="mb_status" class="postbox ">
				<h2 class="hndle"><span>Learning Outcome (ID=%s)</span>%s</h2>
			</div>', 
			$learnout->id, $edit);

		
		// Level-Table
		$res .= sprintf ('
			<div id="mb_level" class="postbox ">
				<h2 class="hndle"><span>Anforderungsstufe</span></h2>
				<div class="inside">%s</div>
			</div>', 
			self::getHTML_Level($learnout, $viewType, $prefix)); 
				

		// Taxonomy Terms: Name of Taxonomy and list of terms (if available)
		$res .= sprintf ('
			<div class="postbox ">
				<h2 class="hndle"><span>%s</span></h2>
				<div class="inside">%s</div>
			</div>',
			RoleTaxonomy::getDomains()[$learnout->domain],
			HTML_Object::getHTML_Topic($learnout->domain, $learnout->id, $viewType, $prefix));
		

		return $res;
	}

	
	public static function getHTML_LearnOut (EAL_LearnOut $learnout, $namePrefix = "") {
	
		return sprintf ('
			<div>
 				<div style="margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;">
 					<div>%s</div>
					<input type="hidden" id="%spost_ID" name="%spost_ID"  value="%s">
					<input type="hidden" id="%spost_title" name="%spost_title"  value="%s">
					<input type="hidden" id="%spost_type" name="%spost_type"  value="%s">
					<input type="hidden" id="%slearnout_description" name="%slearnout_description"  value="%s">
					<input type="hidden" id="%spost_content" name="%spost_content"  value="%s">
 				</div>
 			</div>',
			wpautop($learnout->description),
			$namePrefix, $namePrefix, $learnout->id,
			$namePrefix, $namePrefix, htmlentities ($learnout->title, ENT_COMPAT | ENT_HTML401, 'UTF-8'),
			$namePrefix, $namePrefix, $learnout->type,
			$namePrefix, $namePrefix, htmlentities($learnout->description, ENT_COMPAT | ENT_HTML401, 'UTF-8'),
			$namePrefix, $namePrefix, microtime()					
		);
			
	}
	
}
?>