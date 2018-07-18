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
	
	public function metaboxUserItemTable () {
		?>
			
		<style>
#customers {
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

#customers td, #customers th {
    border: 1px solid #ddd;
    padding: 9px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #4CAF50;
    color: white;
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
				<th>Difficulty</th>
<?php 			for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {   ?>	
					<th><?php printf('% 3.1f', $this->getTestResult()->getItemDifficulty($itemIndex)); ?></th>
<?php			} ?>
			</tr>			
			
			<tr>
				<th>Trennschärfe</th>
<?php 			for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {   ?>	
					<th><?php printf('%1.3f', $this->getTestResult()->getItemTrennschaerfe($itemIndex)); ?></th>
<?php			} ?>
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
?>
		<table id='customers' style='border-width:1px; border-color:#222222'>
			<tr>
				<th></th>
		<?php 	for ($itemIndex = 0; $itemIndex < $this->getTestResult()->getNumberOfItems(); $itemIndex++) {   ?>
				<th>
						<span>
							<a style="vertical-align:middle" class="page-title-action" href="admin.php?page=view_item&itemid=<?= $this->getTestResult()->getItemId($itemIndex) ?>">
								<?= $this->getTestResult()->getItemId($itemIndex) ?>
							</a>
						</span>
					</th>
<?php			} ?>
			</tr>
		
		
		<?php 	for ($itemIndex1 = 0; $itemIndex1 < $this->getTestResult()->getNumberOfItems(); $itemIndex1++) { ?>
		<tr>
			<td>
						<span>
							<a style="vertical-align:middle" class="page-title-action" href="admin.php?page=view_item&itemid=<?= $this->getTestResult()->getItemId($itemIndex) ?>">
								<?= $this->getTestResult()->getItemId($itemIndex1) ?>
							</a>
						</span>
					</td>
					
							<?php 	for ($itemIndex2 = 0; $itemIndex2 < $itemIndex1; $itemIndex2++) { ?>
					<td>
					<?php printf('%1.2f', $this->getTestResult()->getItemCorrelation($itemIndex1, $itemIndex2)); ?> 
					</td>
<?php			} ?>					
					<td colspan="<?= $this->getTestResult()->getNumberOfItems()-$itemIndex1 ?>"></td>
					


		</tr>
<?php			} ?>

		</table>
<?php 		
	}
	
	
}
?>