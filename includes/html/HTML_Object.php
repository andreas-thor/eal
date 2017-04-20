<?php

require_once (__DIR__ . "/../eal/EAL_Item.php");


class HTML_Object {
	
	const VIEW_STUDENT = 1;
	const VIEW_REVIEW = 2;
	const VIEW_EDIT = 3;
	const VIEW_IMPORT = 4;
	
	
	
	public static function getHTML_Level ($prefix, $level, $default, bool $disabled, bool $background, string $callback) {
	
		?>
		<script>
			function disableOtherLevels (e) {
	 			var j = jQuery.noConflict();
				// uncheck all other radio input in the table
				j(e).parent().parent().parent().parent().find("input").each ( function () {
 					if (e.id != this.id) this.checked = false;
				});
			}
		</script>
		<?php
					
				
		$res = "<table style='font-size:100%'><tr><td></td>";
		
		foreach ($level as $c => $v) $res .= sprintf ('<td>%s</td>', $c);
		
		$res .= sprintf ('</tr>');
		
		foreach (EAL_Item::$level_label as $n => $r) {	// n=0..5, $r=Erinnern...Erschaffen 
			$res .= sprintf ('<tr><td>%d. %s</td>', $n+1, $r);
			foreach ($level as $c=>$v) {	// c=FW,KW,PW; v=1..6
				$bgcolor = (($default[$c]==$n+1) && ($background==1)) ? '#E0E0E0' : 'transparent'; 
				$res .= sprintf ("<td valign='bottom' align='left' style='padding:3px; padding-left:5px; background-color:%s'>", $bgcolor);
				$res .= sprintf ("<input type='radio' id='%s' name='%s' value='%d' %s onclick=\"disableOtherLevels(this);",
					"{$prefix}_level_{$c}_{$r}", "{$prefix}_level_{$c}", $n+1, (($v==$n+1)?'checked': ($disabled ? "disabled" : ""))); 	
				
				if ($callback != "") {
					$res .= sprintf ("%s (this, %d, '%s', %d, 's');",
						$callback, $n+1, EAL_Item::$level_label[$n], $default[$c], (($default[$c]>0) ? EAL_Item::$level_label[$default[$c]-1] : ""));
				}
				$res .= sprintf ("\"></td>"); 
			}
			$res .= sprintf ('</tr>');
		}
		$res .= sprintf ('</table>');
		
		return $res;
	}
		
	
	
	public static function getHTML_Topic (string $domain, string $id, int $viewType, string $prefix = "") {
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
					$prefix, $prefix, $domain,
					// 					$prefix,
					self::getHTML_TopicHierarchy($prefix, get_terms( array('taxonomy' => $domain, 'hide_empty' => false) ), 0, wp_get_post_terms( $id, $domain, array("fields" => "ids"))));
	
		} else {
	
			$terms = wp_get_post_terms( $id, $domain, array("fields" => "names"));
			$termCheckboxes = "";
			foreach ($terms as $t) {
				$termCheckboxes .= sprintf ("<input type='checkbox' checked onclick='return false;'>%s<br/>", $t);
			}
			return $termCheckboxes;
	
		}
	
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
	
}

?>