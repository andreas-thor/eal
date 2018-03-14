<?php

require_once ("HTML_Item.php");
require_once (__DIR__ . "/../eal/EAL_ItemMC.php");

class HTML_ItemMC extends HTML_Item  {
	

	private function printAnswerLine (string $prefix, string $answer, string $pointsPositive, string $pointsNegative, bool $showButtons, string $fontWeight, bool $isReadOnly, bool $includeFormValue) {
?>
		<tr>
			<td style="width:100%-12em">
				<input 
					type="text" 
					<?php if ($includeFormValue) printf ('name="%sanswer[]" ', $prefix); ?>
					value="<?php echo htmlentities($answer, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>"  
					style="width:100%; font-weight:<?php echo $fontWeight ?>" 
					<?php if ($isReadOnly) echo " readonly " ?>
					size="255" />
			</td>
			<td style="width:3em">
				<input type="text" name="<?php echo $prefix ?>positive[]" value="<?php echo $pointsPositive ?>" 
				<?php if ($isReadOnly) echo " readonly " ?>
				size="1" />
			</td>
			<td style="width:3em">
				<input type="text" name="<?php echo $prefix ?>negative[]" value="<?php echo $pointsNegative ?>" 
				<?php if ($isReadOnly) echo " readonly " ?>
				size="1" />
			</td>
			<?php if ($showButtons) { ?>
				<td style="width:6em">
					<a class="button" onclick="addAnswer(this);">&nbsp;+&nbsp;</a>
					<a class="button" onclick="removeAnswer(this);">&nbsp;-&nbsp;</a>
				</td>
			<?php } ?>
		</tr>
<?php 		
	} 
	 
	
	protected function printAnswers_Editor(string $prefix) {
?>
		<script> 
			// Javascript for + / - button interaction
			var $ = jQuery.noConflict();

			// add new answer option after current line
			function addAnswer (e) { 
				$(e).parent().parent().after(`<?php $this->printAnswerLine($prefix, '', 0, 0, TRUE, 'normal', FALSE, TRUE); ?>` );
			}

			// delete current answer options but make sure that header + at least one option remain
			function removeAnswer (e) {
				if ($(e).parent().parent().parent().children().size() > 2) {
					$(e).parent().parent().remove();
				}
			}
		</script>
		
		Minimum: <input type="text" name="<?php echo $prefix ?>item_minnumber" value="<?php echo $this->item->minnumber ?>" size="1" /> 
		&nbsp;&nbsp;&nbsp;
		Maximum: <input type="text" name="<?php echo $prefix ?>item_maxnumber" value="<?php echo $this->item->minnumber ?>" size="1" />
		<table style="font-size:100%">
			<tr align="left">
				<th>Antwort-Text</th>
				<th>Ausgew&auml;hlt</th>
				<th>Nicht ausgew&auml;hlt</th>
				<th>Aktionen</th>
			</tr>
			<?php 
				foreach ($this->item->answers as $a) {
					$this->printAnswerLine($prefix, $a['answer'], $a['positive'], $a['negative'], TRUE, 'normal', FALSE, TRUE);
				}
			?>
		</table>
<?php	
	}
	
	protected function printAnswers_ForReview(bool $isImport, string $prefix) {
?>
		Minimum: <input type="text" name="<?php echo $prefix ?>item_minnumber" value="<?php echo $this->item->minnumber ?>" size="1" readonly /> 
		&nbsp;&nbsp;&nbsp;
		Maximum: <input type="text" name="<?php echo $prefix ?>item_maxnumber" value="<?php echo $this->item->minnumber ?>" size="1" readonly />
		<table style="font-size:100%">
			<?php 
				foreach ($this->item->answers as $a) {
					$this->printAnswerLine($prefix, $a['answer'], $a['positive'], $a['negative'], FALSE, $a['positive']>$a['negative'] ? 'bold' : 'normal', TRUE, $isImport);
				}
			?>
		</table>
<?php
		
		
	}
	
	protected function printAnswers_Preview() {
?>
		<div style="margin-top:1em">
			<div style="margin-top:1em">
				Minimum: <input type="text" value="<?php echo $this->item->minnumber ?>" size="1" readonly/> 
				&nbsp;&nbsp;&nbsp;
				Maximum: <input type="text" value="<?php echo $this->item->maxnumber ?>" size="1" readonly/>
			</div>					
			<?php foreach ($this->item->answers as $a) { ?> 
				<div style="margin-top:1em">
					<input type="checkbox"/>
					<?php echo $a['answer'] ?>
				</div>
			<?php } ?>
		</div>
<?php 			
	}
	
	
	public static function compareAnswers (EAL_ItemMC $old, EAL_ItemMC $new): array {
		
		$diff  = sprintf ("<table class='diff'>");
		$diff .= sprintf ("<colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup>");
		$diff .= sprintf ("<tbody><tr>");
		$diff .= sprintf ("<td><div>%s</div></td><td></td>", self::compareAnswers1($new->answers, $old->answers, "deleted"));
		$diff .= sprintf ("<td><div>%s</div></td>", self::compareAnswers1($old->answers, $new->answers, "added"));
		$diff .= sprintf ("</tr></tbody></table>");
		return array ("id" => 'answers', 'name' => 'Antwortoptionen', 'diff' => $diff);
		
	}
	
	private static function compareAnswers1 (array $old, array $new, string $class): string {
		
		$res = "<table >";
		
		foreach ($old as $i => $a) {
			$res .= "<tr align='left' >";
			$bgcolor = ($new[$i]['positive'] != $a['positive']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['positive']}</td>";
			$bgcolor = ($new[$i]['negative'] != $a['negative']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['negative']}</td>";
			$bgcolor = ($new[$i]['answer'] != $a['answer']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='width:98%; padding:0; padding-left:10px' align='left' {$bgcolor}>{$a['answer']}</td></tr>";
			
		}
		
		$res .= "</table></div>";
		
		return $res;
	}


	
	
}
?>