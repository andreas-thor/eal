<?php

require_once 'HTML_Item.php';
require_once __DIR__ . '/../eal/EAL_ItemFT.php';

class HTML_ItemFT extends HTML_Item {
	
	 
	function __construct(EAL_Item $item) {
		parent::__construct($item);
	}
	
	private function getItem(): EAL_ItemFT {
		return $this->item;
	}
	
	
	public function printItem (bool $isPreview, bool $isEditable, bool $isImport, string $prefix="") {
		$this->printDescription($isImport, $prefix);
		$this->printQuestion($isImport, $prefix);
		$this->printPoints($isPreview, $isEditable, $isImport, $prefix);
	}
	
	
	
	/**********************************************************************************************
	 * POINTS
	 **********************************************************************************************/
	
	
	
	public function metaboxPoints () {
		$this->printPoints(TRUE);
	}
	
	
	private function printPoints (bool $isEditable, string $prefix='') {
		
?>
		<div class="form-field">
			<table>
				<tr>
					<td style="width:8em">Punkte:</td>
					<td style="width:3em">
						<input 
							type="text" 
							name="<?= $prefix ?>item_points" 
							value="<?= $this->getItem()->getPoints() ?>" 
							size="1" 
							<?= $isEditable ? '' : ' readonly ' ?> 
							/>
					</td>					
				</tr>
			</table>
		</div>
<?php 
	}
	

	public static function comparePoints (EAL_ItemFT $old, EAL_ItemFT $new): array {

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
					<td style="width:98%; padding:0; padding-left:10px" align="left"><div <?= ($old->getPoints()!=$new->getPoints()) ? 'class="diff-deletedline"' : '' ?>><?= $old->getPoints() ?></div></td>
					<td></td>
					<td style="width:98%; padding:0; padding-left:10px" align="left"><div <?= ($old->getPoints()!=$new->getPoints()) ? 'class="diff-addedline"' : '' ?>><?= $new->getPoints() ?></div></td>
					
				</tr>
			</tbody>
		</table

<?php 
		$diff .= ob_get_contents();
		ob_end_clean();
		return array ('id' => 'points', 'name' => 'Punkte', 'diff' => $diff);
		
	}
	
	


	
	
	
		
	
}
?>