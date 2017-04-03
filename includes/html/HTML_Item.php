<?php

require_once ("HTML_Object.php");
require_once ("HTML_ItemMC.php");
require_once ("HTML_ItemSC.php");
require_once (__DIR__ . "/../eal/EAL_Item.php");


class HTML_Item  {
	
	
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
		
		// 		printf ("<div class='misc-pub-section'>");
		// 		printf ("<input type='checkbox' name='item_flag' value='1' %s>", $flag==1 ? "checked" : "");
		// 		printf ("<input type='text' name='item_note' value='%s'>", $vars['args']['note']);
		// 		printf ("</div>");
		
		return sprintf ('
			<div class="form-field">
				<input type="checkbox" name="item_flag" value="1" %s %s>
				<input name="item_note" value="%s" size="40" aria-required="true" type="text" %s>
			</div>',
			$item->flag == 1 ? "checked" : "", $viewType == HTML_Object::VIEW_EDITOR ? "" : "disabled", 
			$item->note, $viewType == HTML_Object::VIEW_EDITOR ? "" : "readonly");
		
	}
	
	public static function getHTML_LearningOutcome (EAL_Item $item, int $viewType, String $namePrefix = "") {
		
		$result = "";
		$learnout = $item->getLearnOut();
		$learnout_id = ($learnout == null) ? -1 : $learnout->id;
		
		
		if ($viewType == HTML_Object::VIEW_REVIEWER) {
			
			$result = sprintf ("
				<select disabled style='width:100%%' align='right'>
					<option selected>%s</none>
				</select>
				<div class='misc-pub-section'>%s</div>"
				, ($learnout==null) ? "None" : $learnout->title, ($learnout==null) ? "" : $learnout->description);
			
		}
		
		if ($viewType == HTML_Object::VIEW_EDITOR) {
		
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
	
	
	public static function getHTML_Metadata (EAL_Item $item, $editable, $namePrefix) {
	
		// Status and Id
		$res = sprintf ("<div>%s (%d)</div><br/>", $item->getStatusString(), $item->id);
	
		// Learning Outcome (Title + Description), if available
		$learnout = $item->getLearnOut();
		
		$res .= self::getHTML_LearningOutcome($item, HTML_Object::VIEW_REVIEWER);
		$res .= self::getHTML_NoteFlag($item, HTML_Object::VIEW_REVIEWER);
		
	
		// Level-Table
		$res .= sprintf ("<div>%s</div><br/>", HTML_Object::getLevelHTML($namePrefix, $item->level, (is_null($learnout) ? null : $learnout->level), $editable?"":"disabled", 1, ''));
			
		// Taxonomy Terms: Name of Taxonomy and list of terms (if available)
		$res .= sprintf ("<div><b>%s</b>:", RoleTaxonomy::getDomains()[$item->domain]);
		if ($editable) {
	
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
			if (count($terms)>0) {
				$res .= sprintf ("<div style='margin-left:1em'>");
				foreach ($terms as $t) {
					$res .= sprintf ("%s</br>", $t);
				}
				$res .= sprintf ("</div>");
			}
	
		}
	
	
	
		$res .= sprintf ("</div>");
	
		return $res;
	}
	
	
	
	
	public static function getHTML_Item (EAL_Item $item, $forReview = TRUE, $editableMeta = FALSE, $namePrefix = "") {
			
 		$answers_html = "";
 		switch (get_class($item)) {
 			case 'EAL_ItemSC': $answers_html = HTML_ItemSC::getHTML_Answers($item, HTML_Object::VIEW_STUDENT); break;
 			case 'EAL_ItemMC': $answers_html = HTML_ItemMC::getHTML_Answers($item, HTML_Object::VIEW_STUDENT); break;
 		}
	
		
		if ($forReview) {
	
			// description
			$item_html  = sprintf ("<div>%s</div>", wpautop(stripslashes($item->description)));
	
			// question and answers
			$item_html .= sprintf ("<div style='background-color:F2F6FF; margin-top:2em; padding:1em;'>");
			$item_html .= sprintf ("%s", wpautop(stripslashes($item->question)));
			$item_html .= sprintf ("%s", $answers_html);
			$item_html .= sprintf ("</div>");
	
			return sprintf ("<div>%s</div>", $item_html);
	
		} else {
	
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
	
		}
	}
	
	
}

?>