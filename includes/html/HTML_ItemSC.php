<?php

require_once ("HTML_Object.php");

require_once (__DIR__ . "/../eal/EAL_ItemSC.php");

class HTML_ItemSC extends HTML_Item {
	
	 
	function __construct(EAL_Item $item) {
		parent::__construct($item);
		
		$this->buttons_question = array (
			'Wähle 1 aus 4' => 'Wählen Sie eine aus den vier Antwortoptionen aus.',
			'Wähle 1 aus 5' => 'Wählen Sie eine aus den fünf Antwortoptionen aus.',
			'Wähle 1 aus 6' => 'Wählen Sie eine aus den sechs Antwortoptionen aus.',
			'Wähle korrekte' => 'Wählen Sie die korrekte aus den folgenden Antwortoptionen aus.'
		);
	}
	
	
	private function printAnswerLine (string $prefix, string $answer, string $points, bool $showButtons, string $fontWeight, bool $isReadOnly, bool $includeFormValue) {
?>
		<tr>
			<td style="width:100%-9em">
				<input 
					type="text" 
					<?php if ($includeFormValue) printf ('name="%sanswer[]" ', $prefix); ?>
					value="<?php echo htmlentities($answer, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>"  
					style="width:100%; font-weight:<?php echo $fontWeight ?>" 
					<?php if ($isReadOnly) echo " readonly " ?>
					size="255" />
			</td>
			<td style="width:3em">
				<input type="text" name="<?php echo $prefix ?>points[]" value="<?php echo $points ?>" 
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
	
	

	
	
	protected function printAnswers_Preview () {
?> 
		<div style="margin-top:1em">
			<?php foreach ($this->item->answers as $a) { ?> 
				<div style="margin-top:1em">
					<input type="radio" name="<?php echo $this->item->getId() ?>" />
					<?php echo $a['answer'] ?>
				</div>
			<?php } ?>
		</div>
<?php 
	}
	
	
	protected function printAnswers_Editor (string $prefix) {
?>
		<script>
			// Javascript for + / - button interaction
			var $ = jQuery.noConflict();

			// add new answer option after current line
			function addAnswer (e) { 
				$(e).parent().parent().after(`<?php $this->printAnswerLine($prefix, '', 0, TRUE, 'normal', FALSE, TRUE); ?>` );
			}

			// delete current answer options but make sure that header + at least one option remain
			function removeAnswer (e) {
				if ($(e).parent().parent().parent().children().size() > 2) {
					$(e).parent().parent().remove();
				}
			}
		</script>
			
		<table style="font-size:100%">
			<tr align="left">
				<th>Antwort-Text</th>
				<th>Punkte</th>
				<th>Aktionen</th>
			</tr>
			<?php 
			foreach ($this->item->answers as $a) {
				$this->printAnswerLine($prefix, $a['answer'], $a['points'], TRUE, 'normal', FALSE, TRUE);
			}
			?>
		</table>
<?php 		
	}
	
	
	protected function printAnswers_ForReview (bool $isImport, string $prefix) {
?>
		<table style="font-size: 100%">
			<?php 
			foreach ($this->item->answers as $a) { 
				$this->printAnswerLine($prefix, $a['answer'], $a['points'], FALSE, $a['points']>0 ? 'bold' : 'normal', TRUE, $isImport);
			} 
			?>
		</table>
<?php 		
	}
	
	
	
	public static function compareAnswers (EAL_ItemSC $old, EAL_ItemSC $new): array {

		ob_start();
?>
		<table class="diff">
			<colgroup>
				<col class="content diffsplit left">
				<col class="content diffsplit middle">
				<col class="content diffsplit right">
			</colgroup>
			<tbody>
				<tr>
					<td><div><?php self::printCompareAnswers1($old->answers, $new->answers, "deleted") ?></div></td>
					<td></td>
					<td><div><?php self::printCompareAnswers1($new->answers, $old->answers, "added") ?></div></td>
				</tr>
			</tbody>
		</table

<?php 
		$diff .= ob_get_contents();
		ob_end_clean();
		return array ("id" => 'answers', 'name' => 'Antwortoptionen', 'diff' => $diff);
		
	}
	
	

	
	private static function printCompareAnswers1 (array $old, array $new, string $class) {
?>		
		<table>
			<?php foreach ($old as $i => $a) { ?>
				<tr align="left">
					<td style="border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px" align="left" 
					<?php if ($new[$i]['points'] != $a['points']) printf ('class="diff-%sline"', $class) ?>><?php echo $a['points'] ?></td>
					<td style="width:99%; padding:0; padding-left:10px" align="left" <?php if ($new[$i]['answer'] != $a['answer']) printf ('class="diff-%sline"', $class) ?>><?php echo $a['answer'] ?></td>
				</tr>
			<?php } ?>
		</table>
<?php 		
	}
	
	
	
		
	
}
?>