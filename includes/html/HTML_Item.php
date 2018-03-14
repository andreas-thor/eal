<?php

require_once ("HTML_Object.php");
require_once ("HTML_ItemMC.php");
require_once ("HTML_ItemSC.php");
require_once (__DIR__ . "/../eal/EAL_Item.php");


abstract class HTML_Item extends HTML_Object {
	
	protected $item;
	
	function __construct(EAL_Item $item) {
		$this->item = $item;
	}
	
	
	/**********************************************************************************************
	 * ANSWERS
	 **********************************************************************************************/
	
	public function printAnswers (bool $isPreview, bool $isEditable, bool $isImport, string $prefix="") {
		
		if ($isPreview) {
			return $this->printAnswers_Preview();
		}
		
		if ($isEditable) {
			return $this->printAnswers_Editor($prefix);
		}
		
		return $this->printAnswers_ForReview($isImport, $prefix);
	}
	
	
	protected abstract function printAnswers_Preview (); 
	
	protected abstract function printAnswers_Editor (string $prefix);

	protected abstract function printAnswers_ForReview (bool $isImport, string $prefix);
	
	
	
	/**********************************************************************************************
	 * STATUS
	 *  Drop down list of available statuses (if $isEditable)
	 **********************************************************************************************/
	
	public function printStatus (bool $isEditable, bool $isImport, string $prefix="") {
		
		$allStatus = [];
		if ($this->item->getId()>0) {
			$allStatus[1] = 'Published';
			$allStatus[2] = 'Pending Review';
			$allStatus[3] = 'Draft';
		}
		if ($isImport) {
			$allStatus[-1] = 'Published';
			$allStatus[-2] = 'Pending Review';
			$allStatus[-3] = 'Draft';
			$allStatus[0]  = 'Do not import';
		}
?>
		<select class="importstatus" style="width:100%" name="<?php echo $prefix ?>item_status" align="right">
		
		<?php foreach ($allStatus as $i => $status) { ?>
			<option 
				style="display:<?php echo (!$isEditable ? 'none' : 'block') ?>" 
				value="<?php echo $i ?>" 
				<?php if (($i>0) && ($status==$this->item->getStatusString())) echo " selected " ?>
			>	
				<?php if ($isImport && ($i>0)) echo 'Update as ' ?>	
				<?php if ($isImport && ($i<0)) echo 'New as ' ?>	
				<?php echo $status ?>
			</option>	 
		<?php } ?>					
		
		</select>
<?php 
	}
	

	/**********************************************************************************************
	 * LEARNING OUTCOMES
	 * Drop down list of all learning outcomes (if $isEditable)
	 **********************************************************************************************/
	
	public function printLearningOutcome (bool $isEditable, string $prefix="") {
		
		$learnout = $this->item->getLearnOut();
		$learnout_id = ($learnout === NULL) ? -1 : $learnout->getId();
		
		// first LO is '[NONE]'
		$allLO = [['id'=>-1, 'title'=>'[None]', 'description'=>'']];
		if ($isEditable) {	// add all learning outcomes 
			$allLO = array_merge($allLO, EAL_LearnOut::getListOfLearningOutcomes());
		} else {
			if (!($learnout === NULL)) {	// add item's learnout if available
				$allLO[] = ['id'=>$learnout->getId(), 'title'=>$learnout->title, 'description'=>$learnout->description];
			}
		}
?>
		<!-- List of all learning outcomes; current LO is pre-selected -->
		<select 
			name="<?php echo $prefix ?>learnout_id" 
 			onchange="select=this; jQuery(select).next().children().each (function( index, option ) { jQuery(option).css ('display', (select.selectedIndex == index) ? 'block' : 'none') ; });" 
			style="width:100%" 
			align="right"
		>
			<?php foreach ($allLO as $pos => $lo) { ?>
				<option 
					value="<?php echo $lo['id'] ?>" 
					style="display:<?php echo (($isEditable) ? "block" : "none") ?>" 			
					<?php if ($learnout_id == $lo['id']) echo " selected " ?>
				>
					<?php echo htmlentities($lo['title'], ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>
				</option>
			<?php } ?>
		</select>
		
		<!--  List of descriptions of all learning outcomes -->
		<div class="misc-pub-section">
			<?php foreach ($allLO as $pos => $lo) { ?>
				<div style="display:<?php echo (($learnout_id == $lo['id'])  ? "block" : "none") ?>">
					<?php echo htmlentities($lo['description'], ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>
				</div>
			<?php } ?>
		</div>
		
<?php 
		
	}
	
	
	public static function compareLearningOutcome (EAL_Item $old, EAL_Item $new): array {
		
		$old_learnout = $old->getLearnOut();
		$old_learnout_id = ($old_learnout === NULL) ? -1 : $old_learnout->getId();
		$new_learnout = $new->getLearnOut();
		$new_learnout_id = ($new_learnout === NULL) ? -1 : $new_learnout->getId();
		
		// no spaces in <td>-Element due to typesetting class of revision screen
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
					<td style="width:98%; padding:0; padding-left:10px" align="left"><div <?php echo ($old_learnout_id != $new_learnout_id) ? 'class="diff-deletedline"' : '' ?>><select style="width:100%"><option selected><?php echo ($old_learnout === NULL) ? '' : $old_learnout->title; ?></option></select><div><?php echo htmlentities(($old_learnout === NULL) ? '' : $old_learnout->description, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?></div></div></td>
					<td></td>
					<td style="width:98%; padding:0; padding-left:10px" align="left"><div <?php echo ($old_learnout_id != $new_learnout_id) ? 'class="diff-addedline"' : '' ?>><select style="width:100%"><option selected><?php echo ($new_learnout === NULL) ? '' : $new_learnout->title; ?></option></select><div><?php echo htmlentities(($new_learnout === NULL) ? '' : $new_learnout->description, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?></div></div></td>
				</tr>
			</tbody>
		</table>
<?php 
							
		$diff .= ob_get_contents();
		ob_end_clean();
		return array ("id" => 'lo', 'name' => 'Learning Outcome', 'diff' => $diff);
	}
	

	
	/**********************************************************************************************
	 * LEVEL
	 **********************************************************************************************/
	
	
	public function printTopic (bool $isEditable, string $prefix = "") {
		parent::printTopicObject($this->item->getDomain(), $this->item->getId(), $isEditable, $prefix);
	}
	
	
	
	
	/**********************************************************************************************
	 * LEVEL 
	 **********************************************************************************************/
	
	public function printLevel (bool $isEditable, string $prefix="") {
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
		$hasLO = ($this->item->getLearnOut() !== NULL);
		$callback = (($isEditable) && ($hasLO)) ? "checkLOLevel" : "";
		
		// FIXME: Ist das hier richtig, dass nochmal item an prefix angehangen wird???
		parent::printLevelObject ($prefix . "item", $this->item->level, $hasLO ? $this->item->getLearnOut()->level : null, !$isEditable, $hasLO, $callback);
	}
	

	
	public static function compareLevel (EAL_Item $old, EAL_Item $new): array {
		
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
					<td align="left"><div><?php echo self::compareLevel1($old->level, $new->level, "deleted") ?></div></td>
					<td></td>
					<td><div><?php echo self::compareLevel1($new->level, $old->level, "added") ?></div></td>
				</tr>
			</tbody>
		</table>
<?php 
		$diff = ob_get_contents();
		ob_end_clean();
		return array ("id" => 'level', 'name' => 'Anforderungsstufe', 'diff' => $diff);
	}
	
	
	private static function compareLevel1 (array $old, array $new, string $class): string {
		$res = "<table style='width:1%'><tr><td></td>";
		foreach ($old as $c => $v) {
			$res .= sprintf ('<td>%s</td>', $c);
		}
		$res .= sprintf ('</tr>');
		
		foreach (EAL_Item::$level_label as $n => $r) {	// n=0..5, $r=Erinnern...Erschaffen
			$bgcolor = (($new["FW"]!=$n+1) &&  ($old["FW"]==$n+1)) || (($new["KW"]!=$n+1) && ($old["KW"]==$n+1)) || (($new["PW"]!=$n+1) && ($old["PW"]==$n+1)) ? "class='diff-{$class}line'" : "";
			// || (($new["FW"]==$n+1) &&  ($old["FW"]!=$n+1)) || (($new["KW"]==$n+1) && ($old["KW"]!=$n+1)) || (($new["PW"]==$n+1) && ($old["PW"]!=$n+1))
			
			$res .= sprintf ('<tr><td style="padding:0px 5px 0px 5px;" align="left" %s>%d.&nbsp;%s</td>', $bgcolor, $n+1, $r);
			foreach ($old as $c=>$v) {	// c=FW,KW,PW; v=1..6
				$bgcolor = (($v==$n+1)&& ($new[$c]!=$n+1)) ? "class='diff-{$class}line'" : "";
				$res .= sprintf ("<td align='left' style='padding:0px 5px 0px 5px;' %s>", $bgcolor);
				$res .= sprintf ("<input type='radio' %s></td>", (($v==$n+1)?'checked':'disabled'));
				
			}
			$res .= '</tr>';
		}
		$res .= sprintf ('</table>');
		return $res;
	}
	
	
	/**********************************************************************************************
	 * NOTE + FLAG
	 **********************************************************************************************/
	
	public function printNoteFlag (bool $isEditable, string $prefix="") {
?>		
		<div class="form-field">
			<table>
				<tr>
					<td style="width:1em">
						<input 
							type="checkbox" value="1"
							name="<?php echo $prefix?>item_flag" 
							<?php if ($this->item->flag == 1) echo " checked " ?>
							<?php if (!$isEditable) echo " onclick='return false;' " ?> 
						/>
					</td>
					<td style="width:100%-1em">
						<input 
							name="<?php echo $prefix ?>item_note" 
							value="<?php echo htmlentities ($this->item->note, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>" 
							style="width:100%" size="255" aria-required="true" 
							<?php if (!$isEditable) echo " readonly " ?> 
						/>
					</td>
				</tr>
			</table>
		</div>
<?php 
	}
	
		
	public static function compareNoteFlag (EAL_Item $old, EAL_Item $new): array {
		
		// no spaces in <td>-Element due to typesetting class of revision screen
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
					<td style="width:98%; padding:0; padding-left:10px" align="left"><div <?php echo ($old->flag!=$new->flag) ? 'class="diff-deletedline"' : '' ?>><input type="checkbox" <?php if ($old->flag == 1) echo 'checked' ?> onclick="return false;" /></div><div <?php echo ($old->note!=$new->note) ? 'class="diff-deletedline"' : '' ?>><?php echo htmlentities ($old->note, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?></div></td>
					<td></td>
					<td style="width:98%; padding:0; padding-left:10px" align="left"><div <?php echo ($old->flag!=$new->flag) ? 'class="diff-addedline"' : '' ?>><input type="checkbox" <?php if ($new->flag == 1) echo 'checked' ?> onclick="return false;" /></div><div <?php echo ($old->note!=$new->note) ? 'class="diff-addedline"' : '' ?>><?php echo htmlentities ($new->note, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?></div></td>
				</tr>
			</tbody>
		</table>
<?php 
							
		$diff .= ob_get_contents();
		ob_end_clean();
		return array ("id" => 'noteflag', 'name' => 'Notiz', 'diff' => $diff);
		
	}
	
	
	/**********************************************************************************************
	 * TITLE
	 **********************************************************************************************/
	
	public static function compareTitle (EAL_Item $old, EAL_Item $new): array {
		return array ("id" => 'title', 'name' => 'Titel', 'diff' => self::compareText ($old->title ?? "", $new->title ?? ""));
	}
	
	
	/**********************************************************************************************
	 * DESCRIPTION
	 **********************************************************************************************/
	
	public function printDescription (bool $isImport, string $prefix="") {
?>		
		<div>
			<?php if ($isImport) { ?>
				<input 
					type="hidden" 
					name="<?php echo $prefix ?>item_description" 
					value="<?php echo htmlentities($this->item->description, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>" />			
			<?php } ?> 
			<?php echo wpautop($this->item->description) ?>
		</div>
<?php 		
	}
	
	public static function compareDescription (EAL_Item $old, EAL_Item $new): array {
		return array ("id" => 'description', 'name' => 'Fall- oder Problemvignette', 'diff' => self::compareText ($old->description ?? "", $new->description ?? ""));
	}
	
	
	/**********************************************************************************************
	 * QUESTION
	 **********************************************************************************************/
	
	public function printQuestion (bool $isImport, string $prefix="") {
?>
		<div>
			<?php if ($isImport) { ?>
				<input 
					type="hidden" 
					name="<?php echo $prefix ?>item_question" 
					value="<?php echo htmlentities($this->item->question, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>" />			
			<?php } ?> 
			<?php echo wpautop($this->item->question) ?>
		</div>
<?php
	}
	
	
	public static function compareQuestion (EAL_Item $old, EAL_Item $new): array {
		return array ("id" => 'question', 'name' => 'Aufgabenstellung', 'diff' => self::compareText ($old->question ?? "", $new->question ?? ""));
	}

	

	

	
	/** Helper function for comparison */
	
	private static function compareText (string $old, string $new): string {
		
		$old = normalize_whitespace (strip_tags ($old));
		$new = normalize_whitespace (strip_tags ($new));
		$args = array(
			'title'           => '',
			'title_left'      => '',
			'title_right'     => '',
			'show_split_view' => true
		);
		
		$diff = wp_text_diff($old, $new, $args);
		
		if (!$diff) {
			$diff  = "<table class='diff'><colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup><tbody><tr>";
			$diff .= "<td>{$old}</td><td></td><td>{$new}</td>";
			$diff .= "</tr></tbody></table>";
		}
		
		return $diff;
		
	}
		
}

?>