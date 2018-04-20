<?php


require_once (__DIR__ . "/../eal/EAL_Review.php");
  
class HTML_Review extends HTML_Object {
	 
	protected $review;
	
	function __construct(EAL_Review $review) {
		$this->review = $review;
	}
	
	
	
	
	/* ****************************************************************************
	 * LEVEL
	 * ****************************************************************************/
	
	public function metaboxLevel () {
		$this->printLevel(TRUE);
	}

	public function printLevel (bool $isEditable, string $prefix="") {
		parent::printLevelObject ($prefix . 'review_level_', $this->review->getLevel(), $this->review->getItem()->getLevel(), !$isEditable, TRUE, '');
	}

	
	
	/* ****************************************************************************
	 * FEEDBACK
	 * ****************************************************************************/	
	
	public function metaboxFeedback () {
		$this->printEditor('review_feedback', $this->review->getFeedback());
	}
	
	public function printFeedback (bool $isImport, string $prefix="") {
		?>
		<div>
			<?php if ($isImport) { ?>
				<input 
					type="hidden" 
					name="<?php echo $prefix ?>review_feedback" 
					value="<?php echo htmlentities($this->review->getFeedback(), ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>" />			
			<?php } ?> 
			<?php echo wpautop($this->review->getFeedback()) ?>
		</div>
	<?php
	}
	
	
	/* ****************************************************************************
	 * OVERALL
	 * ****************************************************************************/
	
	
	public function metaboxOverall () {
		$this->printOverall(TRUE);
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
			<input type="hidden" id="item_id" name="<?php echo $prefix ?>item_id"  value="<?php echo $this->review->getItemId() ?>">
		<?php } ?>
		
		<select style="width:100%" name="<?php echo $prefix ?>review_overall" onchange="setAccept(this.value);" align="right">

			<?php foreach (["", "Item akzeptiert", "Item &uuml;berarbeiten", "Item abgelehnt"] as $i=>$status) { ?>
				<option
					value="<?php echo $i ?>" 
					<?php if (!$isEditable) echo 'style="display:none"' ?>
					<?php if ($i == $this->review->getOverall()) echo ' selected ' ?>
				>
					<?php echo $status ?>
				</option>
			<?php } ?>
						
		</select>
		
<?php 		
	}
	
	
	
	/* ****************************************************************************
	 * SCORE
	 * ****************************************************************************/
	
	
	public function metaboxScore () {
		$this->printScore(TRUE);
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
							$checked = ($this->review->getScore($k1, $k2)==$k3+1);
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
	
	
	/* ****************************************************************************
	 * LEARNING OUTCOME
	 * ****************************************************************************/
	
	public function metaboxLearningOutcome () {
		$this->review->getItem()->getHTMLPrinter()->printLearningOutcome(FALSE);
	}
	
	
	public function metaboxTopic () {
		$this->review->getItem()->getHTMLPrinter()->printTopic(FALSE);	
	}
	

	/* ****************************************************************************
	 * ITEM
	 * ****************************************************************************/
	
	public function metaboxItem () {
		$htmlPrinter = $this->review->getItem()->getHTMLPrinter();
		$htmlPrinter->printDescription(FALSE);
		$htmlPrinter->printQuestion(FALSE);
		$htmlPrinter->printAnswers(FALSE, FALSE, FALSE);
	}
	
	
	
}
?>