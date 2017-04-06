<?php

require_once ("HTML_Object.php");
require_once ("HTML_ItemMC.php");
require_once ("HTML_ItemSC.php");
require_once (__DIR__ . "/../eal/EAL_Item.php");


class HTML_Item  {
	
	
	public static function getHTML_Status (EAL_Item $item, int $viewType) {
	
		$result = "";
		$status = $item->getStatusString();
		
		if ($viewType == HTML_Object::VIEW_EDITOR) {
			$result .= sprintf ("<table style='font-size:100%%'>");
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_0' name='item_%s_status' value='1' %s>Published</td></tr>", $item->id, (($status=="Published") ? "checked" : ""));
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_1' name='item_%s_status' value='2' %s>Pending Review</td></tr>", $item->id, (($status=="Pending Review") ? "checked" : ""));
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_2' name='item_%s_status' value='3' %s>Draft</td></tr>", $item->id, (($status=="Draft") ? "checked" : ""));
			$result .= sprintf ("</table>");
		}

		if ($viewType == HTML_Object::VIEW_IMPORT) {
			$result .= sprintf ("<table style='font-size:100%%'>");
			
			if ($item->id > 0) {
				$result .= sprintf ("<tr><td><input type='radio' id='item_status_0' name='item_%s_status' value='1' %s>Updated as Published</td></tr>", $item->id, (($status=="Published") ? "checked" : ""));
				$result .= sprintf ("<tr><td><input type='radio' id='item_status_1' name='item_%s_status' value='2' %s>Updated as Pending Review</td></tr>", $item->id, (($status=="Pending Review") ? "checked" : ""));
				$result .= sprintf ("<tr><td><input type='radio' id='item_status_2' name='item_%s_status' value='3' %s>Updated as Draft</td></tr>", $item->id, (($status=="Draft") ? "checked" : ""));
			}
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_3' name='item_%s_status' value='4' %s>New as Published</td></tr>", $item->id, "");
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_4' name='item_%s_status' value='5' %s>New as Pending Review</td></tr>", $item->id, "");
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_5' name='item_%s_status' value='6' %s>New as Draft</td></tr>", $item->id, $item->id<0 ? "checked" : "");
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_6' name='item_%s_status' value='7' %s>Do not Import</td></tr>", $item->id, "");
			$result .= sprintf ("</table>");
		}
		
		
		if ($viewType == HTML_Object::VIEW_REVIEWER) {
			$result .= sprintf ("<table style='font-size:100%%'>");
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_0' name='item_%s_status' value='1' %s>Published</td></tr>", $item->id, (($status=="Published") ? "checked" : "disabled"));
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_1' name='item_%s_status' value='2' %s>Pending Review</td></tr>", $item->id, (($status=="Pending Review") ? "checked" : "disabled"));
			$result .= sprintf ("<tr><td><input type='radio' id='item_status_2' name='item_%s_status' value='3' %s>Draft</td></tr>", $item->id, (($status=="Draft") ? "checked" : "disabled"));
			$result .= sprintf ("</table>");
		}
	
		return $result;
	
	}
	
	
	private static function getHTML_TopicHierarchy ($namePrefix, $terms, $parent, $selected) {
	
		$res .= "";
		foreach ($terms as $term) {
			if ($term->parent != $parent) continue;
				
			$res .= sprintf ('
				<li id="%4$s-%1$d">
					<label class="selectit">
					<input value="%1$d" type="checkbox" %3$s name="%4$s_taxonomy[]" id="in-%4$s-%1$d"> %2$s</label>
					<ul class="children">%5$s</ul>
				</li>',
					$term->term_id, $term->name, in_array ($term->term_id, $selected)?"checked":"",
					$namePrefix,
					self::getHTML_TopicHierarchy($namePrefix, $terms, $term->term_id, $selected));
		}
	
		return $res;
	}
	

	public static function getHTML_NoteFlag (EAL_Item $item, int $viewType) {
		
		return sprintf ('
			<div class="form-field">
				<input type="checkbox" name="item_flag" value="1" %s %s>
				<input name="item_note" value="%s" width="10%%" aria-required="true"  %s>
			</div>',
			$item->flag == 1 ? "checked" : "", ($viewType == HTML_Object::VIEW_EDITOR)  || ($viewType == HTML_Object::VIEW_IMPORT) ? "" : "onclick='return false;'", 
			$item->note, ($viewType == HTML_Object::VIEW_EDITOR) || ($viewType == HTML_Object::VIEW_IMPORT) ? "" : "readonly");
		
	}
	
	public static function getHTML_LearningOutcome (EAL_Item $item, int $viewType, String $namePrefix = "") {
		
		$result = "";
		$learnout = $item->getLearnOut();
		$learnout_id = ($learnout == null) ? -1 : $learnout->id;
		
		
		if ($viewType == HTML_Object::VIEW_REVIEWER) {
			
			if ($learnout->id > 0) {
				$result = sprintf ("
					<select style='width:100%%' align='right'>
						<option selected style='display:none'>%s</none>
					</select>
					<div class='misc-pub-section'>%s</div>"
					, ($learnout==null) ? "None" : $learnout->title, ($learnout==null) ? "" : $learnout->description);
			}
			
		}
		
		if (($viewType == HTML_Object::VIEW_EDITOR) || ($viewType == HTML_Object::VIEW_IMPORT)){
		
			$allLO = EAL_LearnOut::getListOfLearningOutcomes();
			
			$htmlList  = "<select onchange='for (x=0; x<this.nextSibling.childNodes.length; x++) { this.nextSibling.childNodes[x].style.display = (this.selectedIndex == x) ? \"block\" : \"none\"; }' style='width:100%' align='right' name='{$namePrefix}learnout_id'>";
			$htmlList .= "<option value='0'" . (($learnout == null) ? " selected" : "") . ">None</option>";
			$htmlDesc  = "<div></div>";
			
			foreach ($allLO as $pos => $lo) {
				$htmlList .= "<option value='{$lo->id}'" . (($learnout_id==$lo->id) ? " selected" : "") . ">{$lo->title}</option>";
				$htmlDesc .= sprintf ("<div style='display:%s'>%s</div>", ($learnout_id==$lo->id) ? "block" : "none", $lo->description);
			}
			$htmlList .= "</select>";
			
			$result = sprintf ("%s<div class='misc-pub-section'>%s</div>", $htmlList, $htmlDesc);
		}
		
		
		return $result;
	}
	
	
	public static function getHTML_Metadata (EAL_Item $item, int $viewType, $namePrefix) {
	
		// Status and Id
// 		$res = sprintf ('<div class="misc-pub-section misc-pub-post-status">Status: %s (ID=%d)</div><br/>', $item->getStatusString(), $item->id);
		$res = sprintf ('
			<div id="mb_status" class="postbox ">
				<h2 class="hndle"><span>Item (ID=%d)</span></h2>
				<div class="inside">%s</div>
			</div>', $item->id, self::getHTML_Status($item, $viewType));
		
		// Learning Outcome (Title + Description), if available
		$learnout = $item->getLearnOut();
		
		$res .= sprintf ('
			<div id="mb_learnout" class="postbox ">
				<h2 class="hndle"><span>Learning Outcome</span></h2>
				<div class="inside">%s</div>
			</div>', self::getHTML_LearningOutcome($item, $viewType));
// 		$res .= self::getHTML_LearningOutcome($item, HTML_Object::VIEW_REVIEWER);
		
	
		// Level-Table
		$res .= sprintf ('
			<div id="mb_learnout" class="postbox ">
				
				<h2 class="hndle"><span>Anforderungsstufe</span></h2>
				<div class="inside">%s</div>
			</div>', HTML_Object::getLevelHTML($namePrefix, $item->level, (is_null($learnout) ? null : $learnout->level), (($viewType == HTML_Object::VIEW_IMPORT) || ($viewType == HTML_Object::VIEW_EDITOR))?"":"disabled", 1, ''));		
		
// 		$res .= sprintf ("<div>%s</div><br/>", HTML_Object::getLevelHTML($namePrefix, $item->level, (is_null($learnout) ? null : $learnout->level), $editable?"":"disabled", 1, ''));
			
		// Taxonomy Terms: Name of Taxonomy and list of terms (if available)
// 		$res .= sprintf ("<div><b>%s</b>:", RoleTaxonomy::getDomains()[$item->domain]);
		if (($viewType == HTML_Object::VIEW_IMPORT) || ($viewType == HTML_Object::VIEW_EDITOR)) {
	
			$res .= sprintf ('
				<div class="inside">
					<div class="categorydiv">
						<div id="topic-all" class="tabs-panel"><input type="hidden" name="%1$s_taxonomy[]" value="0">
							<ul id="topicchecklist" data-wp-lists="list:topic" class="categorychecklist form-no-clear">
							%2$s
							</ul>
						</div>
					</div>
				</div>',
					$namePrefix,
					self::getHTML_TopicHierarchy($namePrefix, get_terms( array('taxonomy' => $item->domain, 'hide_empty' => false) ), 0, wp_get_post_terms( $item->id, $item->domain, array("fields" => "ids"))));
	
		} else {
	
			$terms = wp_get_post_terms( $item->id, $item->domain, array("fields" => "names"));
			$termCheckboxes = "";
			foreach ($terms as $t) {
				$termCheckboxes .= sprintf ("<input type='checkbox' checked onclick='return false;'>%s<br/>", $t);
			}
				
			$res .= sprintf ('
				<div class="postbox ">
					<h2 class="hndle"><span>%s</span></h2>
					<div class="inside">%s</div>
				</div>', 
				RoleTaxonomy::getDomains()[$item->domain],
				$termCheckboxes);
				
				
		}
	
		
		$res .= sprintf ('
				<div class="postbox ">
					<h2 class="hndle"><span>Notiz</span></h2>
					<div class="inside">%s</div>
				</div>',
				self::getHTML_NoteFlag($item, $viewType));
		
// 		$res .= self::getHTML_NoteFlag($item, HTML_Object::VIEW_REVIEWER);
		
	
	
// 		$res .= sprintf ("</div>");
	
		return $res;
	}
	
	
	
	
	public static function getHTML_Item (EAL_Item $item, int $viewType, $namePrefix = "") {
			
 		$answers_html = "";
 		switch (get_class($item)) {
 			case 'EAL_ItemSC': $answers_html = HTML_ItemSC::getHTML_Answers($item, $viewType); break;
 			case 'EAL_ItemMC': $answers_html = HTML_ItemMC::getHTML_Answers($item, $viewType); break;
 		}
	
		$result = "";
 		if ($viewType == HTML_Object::VIEW_REVIEWER) {
 			$result = sprintf ("
 				<div>
 					<div>%s</div>
 					<div style='background-color:F2F6FF; margin-top:2em; padding:1em;'>
 						<div>%s</div>
 						<div>%s</div>
 					</div>
 				</div>", 
 				wpautop(stripslashes($item->description)),
 				wpautop(stripslashes($item->question)),
 				$answers_html
 			);
 		}
 		
 		if (($viewType == HTML_Object::VIEW_STUDENT) || ($viewType == HTML_Object::VIEW_IMPORT)) {
	
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