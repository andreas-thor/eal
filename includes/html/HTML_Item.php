<?php

require_once ("HTML_Object.php");
require_once ("HTML_ItemMC.php");
require_once ("HTML_ItemSC.php");
require_once (__DIR__ . "/../eal/EAL_Item.php");


class HTML_Item  {
	
	
	
	/**
	 * Drop down list of available statuses
	 * @param EAL_Item $item
	 * @param int $viewType
	 * @param string $prefix
	 */
	
	public static function getHTML_Status (EAL_Item $item, int $viewType, string $prefix="") {
	
		$result = sprintf ("<select class='importstatus' style='width:100%%' name='%sitem_status' align='right'>", $prefix);
		
		if ($item->id > 0) {
			foreach (["Published", "Pending Review", "Draft"] as $i=>$status) {
				$result .= sprintf (
					"<option %s value='%d' %s>%s %s</option>", 
					($viewType == HTML_Object::VIEW_STUDENT) || ($viewType == HTML_Object::VIEW_REVIEW) ? "style='display:none'" : "",	// editable?
					$i+1, // status value as int
					($status == $item->getStatusString()) ? "selected" : "",	// select current based in item status
					($viewType == HTML_Object::VIEW_IMPORT) ? "Update as" : "",	// import => indicate "update item"
					$status);	// status value as string
			}
		}
		
		if ($viewType == HTML_Object::VIEW_IMPORT) {
			foreach (["Published", "Pending Review", "Draft"] as $i=>$status) {
				$result .= sprintf (
					"<option value='%d' %s>New as %s</option>",
					-($i+1), // status value as int (negative value indicated "new")
					($item->id < 0) && ($i==2) ? "selected" : "",	// select current: if new item => Draft (i==2)
					$status);	// status value as string
			}
			$result .= "<option value='0'>Do not import</option>";	// "Do not import" option --> status_value = 0				
		}
		
		$result .= "</select>";
			
		return $result;
	
	}
	
	/**
	 * 
	 * @param EAL_Item $item
	 * @param int $viewType
	 * @param string $prefix
	 */
	
	public static function getHTML_LearningOutcome (EAL_Item $item, int $viewType, string $prefix = "") {
	
		$learnout = $item->getLearnOut();
		$learnout_id = ($learnout == null) ? -1 : (($learnout->id == NULL) ? -1 : $learnout->id);
	
		$htmlList .= "<select name='{$prefix}learnout_id' onchange='for (x=0; x<this.nextSibling.childNodes.length; x++) { this.nextSibling.childNodes[x].style.display = (this.selectedIndex == x) ? \"block\" : \"none\"; }' style='width:100%' align='right'>";
	
		if (($learnout_id == -1) || ($viewType==HTML_Object::VIEW_EDIT) || ($viewType==HTML_Object::VIEW_IMPORT)) {
			$htmlList .= sprintf ("<option value='0' style='display:%s' %s>[None]</option>", ($viewType==HTML_Object::VIEW_EDIT) || ($viewType==HTML_Object::VIEW_IMPORT)? "block" : "none", ($learnout_id == -1) ? "selected" : "");
			$htmlDesc  = sprintf ("<div style='display:%s'></div>", ($learnout_id == -1) ? "block" : "none");
		}
			
		$allLO = (($viewType == HTML_Object::VIEW_STUDENT) || ($viewType == HTML_Object::VIEW_REVIEW)) ? [$learnout] : EAL_LearnOut::getListOfLearningOutcomes();
		foreach ($allLO as $pos => $lo) {
			if ($lo->id == -1) continue; 
			$htmlList .= sprintf (
				"<option value='%d' style='display:%s' %s>%s</option>",
				$lo->id, // value = LO Id
				($viewType==HTML_Object::VIEW_EDIT) || ($viewType==HTML_Object::VIEW_IMPORT) ? "block" : "none",	// show options only during EDIT or IMPORT
				$learnout_id==$lo->id ? " selected" : "",	// select current LO
				htmlentities($lo->title, ENT_COMPAT | ENT_HTML401, 'UTF-8'));	// LO title
				$htmlDesc .= sprintf ("<div style='display:%s'>%s</div>", ($learnout_id==$lo->id) ? "block" : "none", htmlentities($lo->description, ENT_COMPAT | ENT_HTML401, 'UTF-8'));
		}
		$htmlList .= "</select>";
			
		return sprintf ("%s<div class='misc-pub-section'>%s</div>", $htmlList, $htmlDesc);
	}
	
	
	/**
	 * 
	 * @param EAL_Item $item
	 * @param int $viewType
	 * @param string $prefix
	 */
	
	public static function getHTML_Level (EAL_Item $item, int $viewType, string $prefix = "") {
		
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
		
		$disabled = TRUE;
		$background = FALSE;
		$callback = "";
		switch ($viewType) {
			case HTML_Object::VIEW_REVIEW:
				$background = TRUE;
				break;
			case HTML_Object::VIEW_EDIT:
				$disabled = FALSE;
				$callback = "checkLOLevel";
				break;
			case HTML_Object::VIEW_IMPORT:
				$disabled = FALSE;
				break;
		}
		
		return HTML_Object::getHTML_Level($prefix . "item", $item->level, (($item->getLearnOut() == null) ? null : $item->getLearnOut()->level), $disabled, $background, $callback);
		
	}
	
	
	

	
	

	

	/**
	 * 
	 * @param EAL_Item $item
	 * @param int $viewType
	 * @param string $prefix
	 */
	public static function getHTML_NoteFlag (EAL_Item $item, int $viewType, string $prefix="") {
		
		return sprintf ('
			<div class="form-field">
				<table>
					<tr>
						<td style="width:1em"><input type="checkbox" name="%sitem_flag" value="1" %s %s></td>
						<td style="width:100%%-1em"><input  name="%sitem_note" value="%s" style="width:100%%" size="255" aria-required="true" %s></td>
					</tr>
				</table>
			</div>',
			$prefix,
			$item->flag == 1 ? "checked" : "", 
			($viewType == HTML_Object::VIEW_EDIT)  || ($viewType == HTML_Object::VIEW_IMPORT) ? "" : "onclick='return false;'", 
			$prefix,
			htmlentities ($item->note, ENT_COMPAT | ENT_HTML401, 'UTF-8'), 
			($viewType == HTML_Object::VIEW_EDIT) || ($viewType == HTML_Object::VIEW_IMPORT) ? "" : "readonly");
	}
	

	
	
	public static function getHTML_Metadata (EAL_Item $item, int $viewType, $prefix) {
	
		$edit = ($item->id > 0) ? sprintf ('<span style="float: right; font-weight:normal" ><a href="post.php?action=edit&post=%d">Edit</a></span>', $item->id) : '';
		
		// Status and Id
		$res = sprintf ('
			<div id="mb_status" class="postbox ">
				<h2 class="hndle"><span>Item (%s)</span>%s</h2>
				<div class="inside">%s</div>
			</div>', $item->id > 0 ? "ID=" . $item->id : "New", $edit, self::getHTML_Status($item, $viewType, $prefix));
		
		
		// Learning Outcome (Title + Description), if available
		$res .= sprintf ('
			<div id="mb_learnout" class="postbox ">
				<h2 class="hndle"><span>Learning Outcome</span></h2>
				<div class="inside">%s</div>
			</div>', self::getHTML_LearningOutcome($item, $viewType, $prefix));
		
	
		// Level-Table
		$res .= sprintf ('
			<div id="mb_level" class="postbox ">
				<h2 class="hndle"><span>Anforderungsstufe</span></h2>
				<div class="inside">%s</div>
			</div>', self::getHTML_Level($item, $viewType, $prefix)); 
				
		
		// Taxonomy Terms: Name of Taxonomy and list of terms (if available)
		$res .= sprintf ('
			<div class="postbox ">
				<h2 class="hndle"><span>%s</span></h2>
				<div class="inside">%s</div>
			</div>',
			RoleTaxonomy::getDomains()[$item->domain],
			HTML_Object::getHTML_Topic($item->domain, $item->id, $viewType, $prefix));
		
		
		// Note + Flag
		$res .= sprintf ('
			<div class="postbox ">
				<h2 class="hndle"><span>Notiz</span></h2>
				<div class="inside">%s</div>
			</div>',
			self::getHTML_NoteFlag($item, $viewType, $prefix));
		
		
		if (($viewType == HTML_Object::VIEW_EDIT) || ($viewType == HTML_Object::VIEW_IMPORT)) {
			$res .= sprintf ('
				<input type="hidden" name="%1$spost_ID"  value="%2$s">
		  		<input type="hidden" name="%1$spost_type"  value="%3$s">
		  		<input type="hidden" name="%1$spost_content"  value="%4$s">
		  		<input type="hidden" name="%1$spost_title"  value="%5$s">
				',
				$prefix,
				$item->id,
				$item->type,
				microtime(),
				htmlentities ($item->title, ENT_COMPAT | ENT_HTML401, 'UTF-8')
			);
		}
		
		
		return $res;
	}
	
	
	
	
	public static function getHTML_Item (EAL_Item $item, int $viewType, $namePrefix = "") {
			
 		$answers_html = "";
 		switch (get_class($item)) {
 			case 'EAL_ItemSC': $answers_html = HTML_ItemSC::getHTML_Answers($item, $viewType, $namePrefix); break;
 			case 'EAL_ItemMC': $answers_html = HTML_ItemMC::getHTML_Answers($item, $viewType, $namePrefix); break;
 		}



 		
//  		<input type="hidden" id="%sitem_description" name="%sitem_description"  value="%s">
//  		<input type="hidden" id="%sitem_question" name="%sitem_question"  value="%s">
 			
		$result = "";
 		if ($viewType == HTML_Object::VIEW_IMPORT) {
 			$result = sprintf ('
 				<div>
			  		<input type="hidden" name="%1$sitem_description"  value="%2$s">
			  		<input type="hidden" name="%1$sitem_question"  value="%3$s">
 					<div>%4$s</div>
 					<div style="background-color:#F2F6FF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;">
 						<div>%5$s</div>
 						<div>%6$s</div>
 					</div>
 				</div>',
				$namePrefix,
 				htmlentities($item->description, ENT_COMPAT | ENT_HTML401, 'UTF-8'),
 				htmlentities($item->question, ENT_COMPAT | ENT_HTML401, 'UTF-8'), 					
				wpautop($item->description),
				wpautop($item->question),
				$answers_html
 			);
 		
 		
 				
 		
 		
 		}
 		
 		
 		
 		
 		
 		
 		if (($viewType == HTML_Object::VIEW_STUDENT) || ($viewType == HTML_Object::VIEW_REVIEW)) {
	
 			$result = sprintf ("
 				<div>
 					<div>%s</div>
 					<div style='background-color:#F2F6FF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>
 						<div>%s</div>
 						<div>%s</div>
 					</div>
 				</div>",
 				wpautop($item->description),
 				wpautop($item->question),
 				$answers_html
 			);
 		}
 		
 		return $result;
	}
	
	
}

?>