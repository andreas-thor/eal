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
			$htmlList .= sprintf (
					"<option value='%d' style='display:%s' %s>%s</option>",
					$lo->id, // value = LO Id
					($viewType==HTML_Object::VIEW_EDIT) || ($viewType==HTML_Object::VIEW_IMPORT) ? "block" : "none",	// show options only during EDIT or IMPORT
					$learnout_id==$lo->id ? " selected" : "",	// select current LO
					$lo->title);	// LO title
					$htmlDesc .= sprintf ("<div style='display:%s'>%s</div>", ($learnout_id==$lo->id) ? "block" : "none", $lo->description);
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
	
	
	
	private static function getHTML_TopicHierarchy ($namePrefix, $terms, $parent, $selected) {
	
		$res .= "";
		foreach ($terms as $term) {
			if ($term->parent != $parent) continue;
				
			$res .= sprintf ('
				<li id="%4$s-%1$d">
					<label class="selectit">
					<input value="%1$d" type="checkbox" %3$s name="%4$staxonomy[]" id="in-%4$s-%1$d"> %2$s</label>
					<ul class="children">%5$s</ul>
				</li>',
					$term->term_id, $term->name, in_array ($term->term_id, $selected)?"checked":"",
					$namePrefix,
					self::getHTML_TopicHierarchy($namePrefix, $terms, $term->term_id, $selected));
		}
	
		return $res;
	}
	
	
	public static function getHTML_Topic (EAL_Item $item, int $viewType, string $prefix = "") {
		// <input type="hidden" name="%staxonomy[]" value="0">
		
		if (($viewType == HTML_Object::VIEW_IMPORT) || ($viewType == HTML_Object::VIEW_EDIT)) {
		
			return sprintf ('
					<div class="categorydiv">
						<input type="hidden" id="%sdomain" name="%sdomain"  value="%s">
						<div id="topic-all" class="tabs-panel">
							<ul id="topicchecklist" data-wp-lists="list:topic" class="categorychecklist form-no-clear">
							%s
							</ul>
						</div>
					</div>',
					$prefix, $prefix, $item->domain, 
// 					$prefix,
					self::getHTML_TopicHierarchy($prefix, get_terms( array('taxonomy' => $item->domain, 'hide_empty' => false) ), 0, wp_get_post_terms( $item->id, $item->domain, array("fields" => "ids"))));
		
		} else {
		
			$terms = wp_get_post_terms( $item->id, $item->domain, array("fields" => "names"));
			$termCheckboxes = "";
			foreach ($terms as $t) {
				$termCheckboxes .= sprintf ("<input type='checkbox' checked onclick='return false;'>%s<br/>", $t);
			}
			return $termCheckboxes;
		
		}		
		
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
						<td style="width:1em"><input  type="checkbox" name="%sitem_flag" value="1" %s %s></td>
						<td style="width:100%%-1em"><input  name="%sitem_note" value="%s" style="width:100%%" size="255" aria-required="true" %s></td>
					</tr>
				</table>
			</div>',
			$prefix,
			$item->flag == 1 ? "checked" : "", 
			($viewType == HTML_Object::VIEW_EDIT)  || ($viewType == HTML_Object::VIEW_IMPORT) ? "" : "onclick='return false;'", 
			$prefix,
			$item->note, 
			($viewType == HTML_Object::VIEW_EDIT) || ($viewType == HTML_Object::VIEW_IMPORT) ? "" : "readonly");
	}
	

	
	
	public static function getHTML_Metadata (EAL_Item $item, int $viewType, $prefix) {
	
		// Status and Id
		$res = sprintf ('
			<div id="mb_status" class="postbox ">
				<h2 class="hndle"><span>Item (%s)</span></h2>
				<div class="inside">%s</div>
			</div>', $item->id > 0 ? "ID=" . $item->id : "New", self::getHTML_Status($item, $viewType, $prefix));
		
		
		// Learning Outcome (Title + Description), if available
		$res .= sprintf ('
			<div id="mb_learnout" class="postbox ">
				<h2 class="hndle"><span>Learning Outcome</span></h2>
				<div class="inside">%s</div>
			</div>', self::getHTML_LearningOutcome($item, $viewType, $prefix));
		
	
		// Level-Table
		$res .= sprintf ('
			<div id="mb_learnout" class="postbox ">
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
			self::getHTML_Topic($item, $viewType, $prefix));
		
		
		// Note + Flag
		$res .= sprintf ('
				<div class="postbox ">
					<h2 class="hndle"><span>Notiz</span></h2>
					<div class="inside">%s</div>
				</div>',
				self::getHTML_NoteFlag($item, $viewType, $prefix));
		
		return $res;
	}
	
	
	
	
	public static function getHTML_Item (EAL_Item $item, int $viewType, $namePrefix = "") {
			
 		$answers_html = "";
 		switch (get_class($item)) {
 			case 'EAL_ItemSC': $answers_html = HTML_ItemSC::getHTML_Answers($item, $viewType, $namePrefix); break;
 			case 'EAL_ItemMC': $answers_html = HTML_ItemMC::getHTML_Answers($item, $viewType, $namePrefix); break;
 		}
	
		$result = "";
 		if (($viewType == HTML_Object::VIEW_REVIEW) || ($viewType == HTML_Object::VIEW_IMPORT)) {
 			$result = sprintf ('
 				<div>
 					<div>%s</div>
 					<div style="background-color:F2F6FF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;">
 						<div>%s</div>
 						<div>%s</div>
						<input type="hidden" id="%spost_id" name="%spost_id"  value="%s">
						<input type="hidden" id="%spost_title" name="%spost_title"  value="%s">
						<input type="hidden" id="%spost_type" name="%spost_type"  value="%s">
 						<input type="hidden" id="%sitem_description" name="%sitem_description"  value="%s">
 						<input type="hidden" id="%sitem_question" name="%sitem_question"  value="%s">
						<input type="hidden" id="%spost_content" name="%spost_content"  value="%s">
 					</div>
 				</div>', 
 				wpautop(stripslashes($item->description)),
 				wpautop(stripslashes($item->question)),
 				$answers_html,
 				$namePrefix, $namePrefix, $item->id,	
 				$namePrefix, $namePrefix, $item->title,	
 				$namePrefix, $namePrefix, $item->type,	
 				$namePrefix, $namePrefix, htmlentities($item->description),	
 				$namePrefix, $namePrefix, htmlentities($item->question),	
 				$namePrefix, $namePrefix, microtime()
 			);
 		}
 		
 		if ($viewType == HTML_Object::VIEW_STUDENT) {
	
 			$result = sprintf ("
 				<div>
 					<div>%s</div>
 					<div style='background-color:#F2F6FF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>
 						<div>%s</div>
 						<div>%s</div>
 					</div>
 				</div>",
 				wpautop(stripslashes($item->description)),
 				wpautop(stripslashes($item->question)),
 				$answers_html
 			);
/* 			
 			
 			
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
							<div class='meta-box-sortables ui-sortable'>
								<h2>Reviews</h2>
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
					, self::getHTML_Metadata($item, $editableMeta, $namePrefix));
	
		
		*/
 		}
 		
 		return $result;
	}
	
	
}

?>