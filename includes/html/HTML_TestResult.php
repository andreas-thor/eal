<?php

require_once 'HTML_Object.php';
require_once __DIR__ . '/../eal/EAL_TestResult.php';


class HTML_TestResult extends HTML_Object {
	
	protected $testresult;
	
	function __construct(EAL_TestResult $testresult) {
		$this->testresult = $testresult;
	}
	 
	
	private function getTestResult(): EAL_TestResult {
		return $this->testresult;
	}
	

	public function metaboxDate () {
?>		
		<input type="date" name="testresult_date" value="<?= $this->getTestResult()->getDateOfTest() ?>"/>
<?php 	
	}
		
	public function metaboxDescription () {
		
		$this->printEditor('testresult_description', $this->getTestResult()->getDescription());
?>
		<script type="text/javascript">
			// remove title and title-action buttons
		 	document.getElementsByTagName("H1")[0].remove();
		 	document.getElementsByClassName("page-title-action")[0].remove();
		</script> 
		
<?php 		
	}
	
	
	
	public function metaboxDifficultyCorrelation () {
		
		$corrLabel = [0 => 'Schlecht <=0.1', 1 => 'Mittel 0.1-0.3', 2 => 'Gut >0.3' ];
		$matrix = [0 => [0 => [], 1 => [], 2 => []], 1 => [0 => [], 1 => [], 2 => []], 2 => [0 => [], 1 => [], 2 => []]];
		
		for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {
			$diff = $this->getTestResult()->getItemDifficulty($itemIndex);
			$diffGroup = 0;
			if ($diff > 0.5) $diffGroup = 1;
			if ($diff > 0.85) $diffGroup = 2;
			
			$corr = $this->getTestResult()->getItemTotalCorrelation($itemIndex);
			$corrGroup = 0;
			if ($corr > 0.1) $corrGroup = 1;
			if ($corr > 0.3) $corrGroup = 2;
			
			$matrix[$corrGroup][$diffGroup][] = $this->getTestResult()->getItemId($itemIndex);
		}
		
		
		?>
		<table id='customers' style='border-width:1px; border-color:#222222'>
			<tr>
				<th></th>
				<th>Schwer (0%-50%)</th>
				<th>Mittel (50%-85%)</th>
				<th>Leicht (85%-100%)</th>
			</tr>
		
<?php 		for ($corrGroup=0; $corrGroup<3; $corrGroup++) { ?> 
			<tr>
				<td><?= $corrLabel[$corrGroup] ?></td>
<?php 			for ($diffGroup = 0; $diffGroup < 3; $diffGroup++) { 
					if (count($matrix[$corrGroup][$diffGroup]) > 0) {
						printf ('<td> <a href="edit.php?post_type=item&itemids=%s">%d</a></td>', implode(',', $matrix[$corrGroup][$diffGroup]), count($matrix[$corrGroup][$diffGroup]));
					} else {
						printf ('<td></td>');
					}
				} ?>					
			</tr>
<?php	} ?>

		</table>
<?php 
		
	}
	
	
	public function metaboxUserItemTable () {
		?>
			
		<style>
#customers {
    xxxfont-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

#customers td, #customers th {
    border: 1px solid #ddd;
    padding: 9px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}
#customers td:hover {background-color: #ddd;}

#customers th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: center;
    xxxbackground-color: #4CAF50;
    xxxcolor: white;
}
</style>
		
		<table id='customers' style='border-width:1px; border-color:#222222'>
			<tr>
				<th></th>
<?php 			for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {   ?>	
					<th>
						<span>
							<a style="vertical-align:middle" class="page-title-action" href="admin.php?page=view_item&itemid=<?= $this->getTestResult()->getItemId($itemIndex) ?>">
								<?= $this->getTestResult()->getItemId($itemIndex) ?>
							</a>
						</span>
					</th>
<?php			} ?>
			</tr>
			
			<tr>
				<th>Item Difficulty</th>
<?php 			for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {    
					$diff = $this->getTestResult()->getItemDifficulty($itemIndex);
					
					$rgb_red = round (255*abs(0.7-$diff)/0.7);
					$rgb_green = round (255*(1-abs(0.7-$diff)/0.7));
					printf('<th style="background-color:rgb(%d,%d,0)">% 3.1f</th>', $rgb_red, $rgb_green, 100*$diff );
// 					printf('<th style="background-color:%s">% 3.1f</th>', (($diff>=20) && ($diff<=80)) ? '#99FF99' : '#FF9999', 100*$diff ); 
				} ?>
			</tr>			
			
			<tr>
				<th>Item Total Correlation</th>
<?php 			for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {  	
					$corr = $this->getTestResult()->getItemTotalCorrelation($itemIndex);
					
					$rgb_red = round (255* (1-max (min(0.3, $corr), 0)/0.3));
					$rgb_green = round (255*max (min(0.3, $corr), 0)/0.3);
					printf('<th style="background-color:rgb(%d,%d,0)">%1.3f</th>', $rgb_red, $rgb_green, $corr );
					
// 					printf('<th style="background-color:%s">%1.3f</th>', ($corr>0.3) ? '#99FF99' : '#FF9999', $corr );
			} ?>
			</tr>			
			
			
<?php 		for ($userIndex = 0; $userIndex < $this->getTestResult()->getNumberOfUsers(); $userIndex++) { ?>
			<tr>
				<td>
					<?= $this->getTestResult()->getUserId($userIndex) ?>
				</td>
<?php 			for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {  ?>				
					<td>
						<?= $this->getTestResult()->getPoints($itemIndex, $userIndex)  ?>
					</td>
<?php 			} ?>			
			</tr>			
<?php 		} ?>
		</table>			
			
		
		
		
		
		<?php 
	}
		
	
	public function metaboxItemItemTable () {

		$names = [];
		$groupHeader = [];
		for ($itemIndex=0; $itemIndex<$this->getTestResult()->getNumberOfItems(); $itemIndex++) {
			$itemId = $this->getTestResult()->getItemId($itemIndex);
			$names[] = $itemIndex;
			$groupHeader[$itemIndex] = sprintf('
				<span>
					<a style="vertical-align:middle" class="page-title-action" href="admin.php?page=view_item&itemid=%d">%s</a>
				</span>',
				$itemId, $itemId
			);
		}
	
		$this->printCorrelationMatrix($names, $groupHeader, $this->getTestResult()->getInterItemCorrelation());
		
	}
	
	
	public function metaboxCorrelationByItemType () {
		$this->metaboxCorrelationByCategory('type');
	}
	
	public function metaboxCorrelationByDimension () {
		$this->metaboxCorrelationByCategory('dim');
	}
	
	public function metaboxCorrelationByLevel () {
		$this->metaboxCorrelationByCategory('level');
	}
	
	private function metaboxCorrelationByCategory (string $cat) {
		
		
		$labels = ItemExplorer::getLabels($cat);
		$names = array_keys ($labels);
		$corrData = $this->getTestResult()->getItemCorrelationByCategory($cat);
		$itemidsByType = $this->getTestResult()->getItemIdsByCategory($cat);
		
		$groupHeader = [];
		foreach ($names as $name) {
			$groupHeader[$name] = $labels[$name];	// default header (without items)
			if ((isset ($itemidsByType[$name])) && (count($itemidsByType[$name])>0)) {
				$groupHeader[$name] = sprintf('
					<span>
						<a style="vertical-align:middle" class="page-title-action" href="admin.php?page=view_item&itemids=%s">%s</a>
					</span>',
					implode(',', $itemidsByType[$name]), $labels[$name]);
			}
		}
		
		$this->printCorrelationMatrix($names, $groupHeader, $corrData);
		
	}
	
	
	private function printCorrelationMatrix (array $names, array $groupHeader, array $corrData) {
?>
		<table id='customers' style='border-width:1px; border-color:#222222'>
			<tr>
				<th></th>
<?php 		for ($index=0; $index<count($names); $index++) {  ?>
				<th><?= $groupHeader[$names[$index]] ?></th>	
<?php 		}	?>
			</tr>
		
<?php 	for ($index1=0; $index1<count($names); $index1++) { ?> 
			
			<tr>
				<td><?= $groupHeader[$names[$index1]] ?></td>
		
<?php 		for ($index2 = 0; $index2 < $index1; $index2++) { 
				$corr = $corrData[$names[$index1]][$names[$index2]];
				if ($corr == NULL) {
					printf ('<td></td>');
				} else {
					printf('<td style="background-color:%s">%1.2f</td>', (($corr>=0.15) && ($corr<=0.5)) ? '#99FF99' : '#FF9999', $corr );
				}
			} ?>					
				
				<td colspan="<?= count($names)-$index1 ?>"></td>
			</tr>
<?php	} ?>

		</table>
<?php 		
	}
	
	

}
?>