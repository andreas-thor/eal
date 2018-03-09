<?php


require_once (__DIR__ . "/../eal/EAL_Review.php");

class HTML_Review extends HTML_Object {
	
	protected $review;
	
	function __construct(EAL_Review $review) {
		$this->review = $review;
	}
	
	

	

	public function printLevel (bool $isEditable, string $prefix="") {
		parent::printLevelObject ($prefix . 'review', $this->review->level, $this->review->getItem()->level, !$isEditable, TRUE, '');
	}
	
	public function printTopic(bool $isEditable, string $prefix = "") { }
	
	
	
	public function printFeedback (bool $isImport, string $prefix="") {
		?>
		<div>
			<?php if ($isImport) { ?>
				<input 
					type="hidden" 
					name="<?php echo $prefix ?>review_feedback" 
					value="<?php echo htmlentities($this->review->feedback, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>" />			
			<?php } ?> 
			<?php echo wpautop($this->review->feedback) ?>
		</div>
	<?php
	}
	
	
	public function printOverall (bool $isEditable, string $prefix = "") {
?>
		<?php if ($isEditable) { ?>
			<script>
				function setAccept (val) {
					var $ = jQuery.noConflict();
					if (val==1) {
						if (confirm('Sollen alle Bewertungen auf "gut" gesetzt werden?')) {
							$(document).find("#mb_score").find("input").each ( function() {
								if (this.value==1) this.checked = true;
							});
						}
					}
				}
			</script>
			<input type="hidden" id="item_id" name="<?php echo $prefix ?>item_id"  value="<?php echo $this->review->item_id ?>">
		<?php } ?>
		
		<select style="width:100%" name="<?php echo $prefix ?>review_overall" onchange="setAccept(this.value);" align="right">

			<?php foreach (["", "Item akzeptiert", "Item &uuml;berarbeiten", "Item abgelehnt"] as $i=>$status) { ?>
				<option
					value="<?php echo $i ?>" 
					<?php if (!$isEditable) echo 'style="display:none"' ?>
					<?php if ($i == $this->review->overall) echo ' selected ' ?>
				>
					<?php echo $status ?>
				</option>
			<?php } ?>
						
		</select>
		
<?php 		
	}
	
	
	
	
	public function printScore (bool $isEditable, string $prefix = "") { 
		
		$values = ["gut", "Korrektur", "ungeeignet"];
?>		
		<script>
			// set all criteria to "ok" for the current row
			function setRowGood (e) {
				var $ = jQuery.noConflict();
				$(e).parent().parent().find('input').each ( function() {
					if (this.value==1) this.checked = true;
				});
			}
		</script>
		
		<form>
			<table style="font-size:100%">
				<tr>
					<th></th>
					<?php foreach (EAL_Review::$dimension2 as $k2 => $v2) { ?>
						<th style="padding:0.5em"><?php echo $v2 ?></th>
					<?php } ?>					
				</tr>
			
				<?php foreach (EAL_Review::$dimension1 as $k1 => $v1) { ?>
				<tr>
					<td valign="top" style="padding:0.5em">
						<?php echo $v1 ?><br/>
						<?php if ($isEditable) echo '<a onclick="setRowGood(this);">(alle gut)</a>' ?>
					</td>
					<?php foreach (EAL_Review::$dimension2 as $k2 => $v2) { ?>
					<td style="padding:0.5em; border-style:solid; border-width:1px;">
						<?php foreach ($values as $k3 => $v3) { 
							$checked = ($this->review->score[$k1][$k2]==$k3+1);
							$name = $prefix . 'review_' . $k1 . '_' . $k2;
							// previos id was k1.k2.k3
						?>
						<input 
							type="radio" 
							id="<?php echo $name ?>_<?php echo $k3 ?>" 
							name="<?php echo $name ?>" 
							value="<?php echo $k3+1 ?>"
							<?php if ((!$isEditable) && (!$checked)) echo " disabled" ?>
							<?php if ($checked) echo ' checked="checked" ' ?>
						/>
						<?php echo $v3 ?><br/>
						<?php } ?>
					</td>
					<?php } ?>
				</tr>
				<?php } ?>
			</table>
		</form>
		
<?php 		
	}
	
	
	

	
	public static function getHTML_Score (EAL_Review $review, int $viewType, string $prefix=""): string {
	
	
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
	
	
	public static function getHTML_Metadata (EAL_Review $review, int $viewType, $prefix): string {
	
		// <h2 class="hndle"><span>Revisionsurteil</span><span style="align:right">Und?</span></h2>
		// Overall
		$res = sprintf ('
			<div id="mb_overall" class="postbox ">
				<h2 class="hndle">
					<span>Revisionsurteil</span>
					<span style="float: right; font-weight:normal" ><a href="post.php?action=edit&post=%d">Edit</a></span>
				</h2>
				<div class="inside">%s</div>
			</div>', $review->getId(), self::getHTML_Overall($review, $viewType, $prefix));
	
	
		// Level-Table
		$res .= sprintf ('
			<div id="mb_level" class="postbox ">
				<h2 class="hndle"><span>Anforderungsstufe</span></h2>
				<div class="inside">%s</div>
			</div>', self::getHTML_Level($review, $viewType, $prefix));
	
	
		return $res;
	}
	
	
	public static function getHTML_Review (EAL_Review $review, int $viewType, string $prefix=""): string {
	
		return sprintf ("
			<div>
				<div>%s</div>
				<div>%s</div>
			</div>", 
			self::getHTML_Score($review, $viewType, $prefix),
			wpautop(htmlentities($review->feedback, ENT_COMPAT | ENT_HTML401, 'UTF-8')));
	}

	public static function getHTML_Level (EAL_Review $review, int $viewType, string $prefix=""): string {
		
		$disabled = TRUE;
		if ($viewType == HTML_Object::VIEW_EDIT) $disabled = FALSE;
		
		return HTML_Object::getHTML_Level($prefix . 'review', $review->level, $review->getItem()->level, $disabled, TRUE, '');
	}
	
	public static function getHTML_Overall (EAL_Review $review, int $viewType, string $prefix="", string $callback=""): string {
		
		
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
	 
}
?>