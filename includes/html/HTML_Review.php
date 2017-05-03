<?php


require_once (__DIR__ . "/../eal/EAL_Review.php");

class HTML_Review {
	
	
	public static function getHTML_Level (EAL_Review $review, int $viewType, string $prefix="") {
		
		$disabled = TRUE;
		if ($viewType == HTML_Object::VIEW_EDIT) $disabled = FALSE;
		
		return HTML_Object::getHTML_Level($prefix . 'review', $review->level, $review->getItem()->level, $disabled, TRUE, '');
	}
	

	
	
	public static function getHTML_Overall (EAL_Review $review, int $viewType, string $prefix="", string $callback="") {
		

		if ($viewType == HTML_Object::VIEW_EDIT) {
			$result .= sprintf ('<input type="hidden" id="item_id" name="%sitem_id"  value="%d">', $prefix, $review->item_id);
		}
		
		$result = sprintf ('<select style="width:100%%" name="%sreview_overall" onchange="%s" align="right">', $prefix, $callback);
		
		
		foreach (["", "Item akzeptiert", "Item &uuml;berarbeiten", "Item abgelehnt"] as $i=>$status) {
			$result .= sprintf ('<option %s value="%d" %s>%s</option>',
					($viewType == HTML_Object::VIEW_STUDENT) || ($viewType == HTML_Object::VIEW_REVIEW) ? 'style="display:none"' : '',	// editable?
					$i, // status value as int
					($i == $review->overall) ? 'selected' : '',	// select current based in item status
					$status);	// status value as string
		}
				
		$result .= "</select>";
			
		return $result;
	}
	
	
	public static function getHTML_Score (EAL_Review $review, int $viewType, string $prefix="") {
	
	
		$values = ["gut", "Korrektur", "ungeeignet"];
	
		$html_head = "<tr><th></th>";
		foreach (EAL_Review::$dimension2 as $k2 => $v2) {
			$html_head .= "<th style='padding:0.5em'>{$v2}</th>";
		}
		$html_head .= "</tr>";
			
		$html_script = "";
		if ($viewType==HTML_Object::VIEW_EDIT) {
			$html_script = "
				<script>
					function setRowGood (e) {
						var $ = jQuery.noConflict();
						$(e).parent().parent().find('input').each ( function() {
		 					if (this.value==1) this.checked = true;
						});
					}
				</script>";
		}
			
		$html_rows = "";
		foreach (EAL_Review::$dimension1 as $k1 => $v1) {
			$html_rows .= "<tr><td valign='top'style='padding:0.5em'>{$v1}<br/>";
			if ($viewType==HTML_Object::VIEW_EDIT) $html_rows .= "<a onclick=\"setRowGood(this);\">(alle gut)</a>";
			$html_rows .= "</td>";
			foreach (EAL_Review::$dimension2 as $k2 => $v2) {
				$html_rows .= "<td style='padding:0.5em; border-style:solid; border-width:1px;'>";
				foreach ($values as $k3 => $v3) {
					$checked = ($review->score[$k1][$k2]==$k3+1);
					$html_rows .= "<input type='radio' id='{$k1}_{$k2}_{k3}' name='{$prefix}review_{$k1}_{$k2}' value='" . ($k3+1) . "'";
					$html_rows .= (($viewType==HTML_Object::VIEW_EDIT) || $checked) ? "" : " disabled";
					$html_rows .= ($checked ? " checked='checked'" : "") . ">" . $v3 . "<br/>";
				}
				$html_rows .= "</td>";
			}
			$html_rows .= "</tr>";
		}
	
		return "{$html_script}<form><table style='font-size:100%'>{$html_head}{$html_rows}</table></form>";
			
	}
	
	
	public static function getHTML_Metadata (EAL_Review $review, int $viewType, $prefix) {
	
		// <h2 class="hndle"><span>Revisionsurteil</span><span style="align:right">Und?</span></h2>
		// Overall
		$res = sprintf ('
			<div id="mb_overall" class="postbox ">
				<h2 class="hndle">
					<span>Revisionsurteil</span>
					<span style="float: right; font-weight:normal" ><a href="post.php?action=edit&post=%d">Edit</a></span>
				</h2>
				<div class="inside">%s</div>
			</div>', $review->id, self::getHTML_Overall($review, $viewType, $prefix));
	
	
		// Level-Table
		$res .= sprintf ('
			<div id="mb_level" class="postbox ">
				<h2 class="hndle"><span>Anforderungsstufe</span></h2>
				<div class="inside">%s</div>
			</div>', self::getHTML_Level($review, $viewType, $prefix));
	
	
		return $res;
	}
	
	
	public static function getHTML_Review (EAL_Review $review, int $viewType, string $prefix="") {
	
		return sprintf ("
			<div>
				<div>%s</div>
				<div>%s</div>
			</div>", 
			self::getHTML_Score($review, $viewType, $prefix),
			wpautop(htmlentities($review->feedback, ENT_COMPAT | ENT_HTML401, 'UTF-8')));
	}
	
	
}
?>