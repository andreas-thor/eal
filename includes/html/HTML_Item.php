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
	
	public static function getHTML_Status (EAL_Item $item, int $viewType, string $prefix=""): string {
	
		$result = sprintf ("<select class='importstatus' style='width:100%%' name='%sitem_status' align='right'>", $prefix);
		
		if ($item->getId() > 0) {
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
					($item->getId() < 0) && ($i==2) ? "selected" : "",	// select current: if new item => Draft (i==2)
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
	
	public static function getHTML_LearningOutcome (EAL_Item $item, int $viewType, string $prefix = ""): string {
	
		$learnout = $item->getLearnOut();
		$learnout_id = ($learnout == null) ? -1 : $learnout->getId();
	
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
	
	public static function getHTML_Level (EAL_Item $item, int $viewType, string $prefix = ""): string {
		
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
	public static function getHTML_NoteFlag (EAL_Item $item, int $viewType, string $prefix=""): string {
		
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
	

	
	// FIXME: Löschen, wenn neuer Bulkviewer implementiert
	public static function getHTML_Metadata (EAL_Item $item, int $viewType, $prefix): string {
	
		$edit = ($item->getId() > 0) ? sprintf ('<span style="float: right; font-weight:normal" ><a style="vertical-align:middle" class="page-title-action" href="post.php?action=edit&post=%d">Edit</a></span>', $item->getId()) : '';
		
		// Status and Id
		$res = sprintf ('
			<div id="mb_status" class="postbox ">
				<h2 class="hndle"><span>Item (%s)</span>%s</h2>
				<div class="inside">%s</div>
			</div>', $item->getId() > 0 ? "ID=" . $item->getId() : "New", $edit, self::getHTML_Status($item, $viewType, $prefix));
		
		
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
			RoleTaxonomy::getDomains()[$item->getDomain()],
			HTML_Object::getHTML_Topic($item->getDomain(), $item->getId(), $viewType, $prefix));
		
		
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
				$item->getId(),
				$item->getType(),
				microtime(),
				htmlentities ($item->title, ENT_COMPAT | ENT_HTML401, 'UTF-8')
			);
		}
		
		
		return $res;
	}
	
	
	
	
	public static function getHTML_Item (EAL_Item $item, int $viewType, $namePrefix = ""): string {
			
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
	
 			$result = sprintf ('
 				<div>
 					<input type="hidden" name="%1$sitem_id"  value="%2$s">
 					<div>%3$s</div>
 					<div style="background-color:#F2F6FF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;">
 						<div>%4$s</div>
 						<div>%5$s</div>
 					</div>
 				</div>',
 				$namePrefix,
 				$item->getId(),
 				wpautop($item->description),
 				wpautop($item->question),
 				$answers_html
 			);
 		}
 		
 		return $result;
	}
	
	
	/**
	 * Methods for comparing two item versions
	 * @param EAL_Item $new
	 */
	
	public static function compareTitle (EAL_Item $old, EAL_Item $new): array {
		return array ("id" => 'title', 'name' => 'Titel', 'diff' => self::compareText ($old->title ?? "", $new->title ?? ""));
	}
	
	
	public static function compareDescription (EAL_Item $old, EAL_Item $new): array {
		return array ("id" => 'description', 'name' => 'Fall- oder Problemvignette', 'diff' => self::compareText ($old->description ?? "", $new->description ?? ""));
	}
	
	
	public static function compareQuestion (EAL_Item $old, EAL_Item $new): array {
		return array ("id" => 'question', 'name' => 'Aufgabenstellung', 'diff' => self::compareText ($old->question ?? "", $new->question ?? ""));
	}
	
	
	public static function compareLevel (EAL_Item $old, EAL_Item $new): array {
		$diff  = sprintf ("<table class='diff'>");
		$diff .= sprintf ("<colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup>");
		$diff .= sprintf ("<tbody><tr>");
		$diff .= sprintf ("<td align='left'><div>%s</div></td><td></td>", self::compareLevel1($old->level, $new->level, "deleted"));
		$diff .= sprintf ("<td><div>%s</div></td>", self::compareLevel1($new->level, $old->level, "added"));
		$diff .= sprintf ("</tr></tbody></table>");
		return array ("id" => 'level', 'name' => 'Anforderungsstufe', 'diff' => $diff);
	}
	
	
	private static function compareLevel1 (array $old, array $new, string $class): string {
		$res = "<table style='width:1%'><tr><td></td>";
		foreach ($old as $c => $v) {
			$res .= sprintf ('<td>%s</td>', $c);
		}
		$res .= sprintf ('</tr>');
		
		foreach (EAL_Item::$level_label as $n => $r) {	// n=0..5, $r=Erinnern...Erschaffen
			$bgcolor = (($new["FW"]!=$n+1) &&  ($old["FW"]==$n+1)) || (($new["KW"]!=$n+1) && ($old["KW"]==$n+1)) || (($new["PW"]!=$n+1) && ($old["PW"]==$n+1)) ? "class='diff-{$class}line'" : "";
			// || (($new["FW"]==$n+1) &&  ($old["FW"]!=$n+1)) || (($new["KW"]==$n+1) && ($old["KW"]!=$n+1)) || (($new["PW"]==$n+1) && ($old["PW"]!=$n+1))
			
			$res .= sprintf ('<tr><td style="padding:0px 5px 0px 5px;" align="left" %s>%d.&nbsp;%s</td>', $bgcolor, $n+1, $r);
			foreach ($old as $c=>$v) {	// c=FW,KW,PW; v=1..6
				$bgcolor = (($v==$n+1)&& ($new[$c]!=$n+1)) ? "class='diff-{$class}line'" : "";
				$res .= sprintf ("<td align='left' style='padding:0px 5px 0px 5px;' %s>", $bgcolor);
				$res .= sprintf ("<input type='radio' %s></td>", (($v==$n+1)?'checked':'disabled'));
				
			}
			$res .= '</tr>';
		}
		$res .= sprintf ('</table>');
		return $res;
	}
	
	
	private static function compareText (string $old, string $new): string {
		
		$old = normalize_whitespace (strip_tags ($old));
		$new = normalize_whitespace (strip_tags ($new));
		$args = array(
			'title'           => '',
			'title_left'      => '',
			'title_right'     => '',
			'show_split_view' => true
		);
		
		$diff = wp_text_diff($old, $new, $args);
		
		if (!$diff) {
			$diff  = "<table class='diff'><colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup><tbody><tr>";
			$diff .= "<td>{$old}</td><td></td><td>{$new}</td>";
			$diff .= "</tr></tbody></table>";
		}
		
		return $diff;
		
	}
	
	
}

?>