<?php


require_once (__DIR__ . "/../eal/EAL_Review.php");

class HTML_Review extends HTML_Object {
	
	
	public static function getHTML_Review (EAL_Review $review) {
	
		// Titel
		$review_html  = sprintf ("
			<div onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\">
				<h1 style='display:inline'>[%s]</span></h1>
				<div style='display:none'>
					<span><a href=\"post.php?action=edit&post=%d\">Edit</a></span>
				</div>
			</div>", $review->getItem()->title, $review->id);
	
		// Scores + Feedback
		$review_html .= sprintf ("<div>%s</div>", self::getHTML_Score($review, FALSE));
		$review_html .= sprintf ("<div>%s</div>", wpautop(stripslashes($review->feedback)));
	
		// Overall Rating + Level
		$overall_String = "";
		switch ($review->overall) {
			case 1: $overall_String = "Item akzeptiert"; break;
			case 2: $overall_String = "Item Item &uuml;berarbeiten"; break;
			case 3: $overall_String = "Item abgelehnt"; break;
		}
		$review_meta  = sprintf ("<div><b>%s</b></div><br />", $overall_String );
		$review_meta .= sprintf ("<div>%s</div><br/>", self::getLevelHTML('review_' . $review->id, $review->level,  $review->getItem()->level, "disabled", 1, ''));
	
	
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
				, $review_html
				, $review_meta);
	
	}
	
	
	
	public function getHTML_Score (EAL_Review $review, $editable) {
	
	
		$values = ["gut", "Korrektur", "ungeeignet"];
	
		$html_head = "<tr><th></th>";
		foreach (EAL_Review::$dimension2 as $k2 => $v2) {
			$html_head .= "<th style='padding:0.5em'>{$v2}</th>";
		}
		$html_head .= "</tr>";
			
		$html_script = "";
		if ($editable) {
			$html_script = "
				<script>
					var $ = jQuery.noConflict();
			
					function setRowGood (e) {
						$(e).parent().parent().find('input').each ( function() {
		 					if (this.value==1) this.checked = true;
						});
					}
				</script>";
		}
			
		$html_rows = "";
		foreach (EAL_Review::$dimension1 as $k1 => $v1) {
			$html_rows .= "<tr><td valign='top'style='padding:0.5em'>{$v1}<br/>";
			if ($editable) $html_rows .= "<a onclick=\"setRowGood(this);\">(alle gut)</a>";
			$html_rows .= "</td>";
			foreach (EAL_Review::$dimension2 as $k2 => $v2) {
				$html_rows .= "<td style='padding:0.5em; border-style:solid; border-width:1px;'>";
				foreach ($values as $k3 => $v3) {
					$checked = ($review->score[$k1][$k2]==$k3+1);
					$html_rows .= "<input type='radio' id='{$k1}_{$k2}_{k3}' name='review_{$k1}_{$k2}' value='" . ($k3+1) . "'";
					$html_rows .= ($editable || $checked) ? "" : " disabled";
					$html_rows .= ($checked ? " checked='checked'" : "") . ">" . $v3 . "<br/>";
				}
				$html_rows .= "</td>";
			}
			$html_rows .= "</tr>";
		}
	
		return "{$html_script}<form><table style='font-size:100%'>{$html_head}{$html_rows}</table></form>";
			
	}
	
	
}
?>