<?php

require_once 'HTML_Object.php';
require_once __DIR__ . '/../eal/EAL_TestResult.php';

require_once __DIR__ . '/../tres/TRES_UserItem.php';

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
		
		$userItem = TRES_UserItem::loadFromDB($this->testresult->getId());
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
<?php 			foreach ($userItem->allUsers as $u) {   ?>	
					<th><?= $u ?></th>
<?php			} ?>
			</tr>
<?php 		foreach ($userItem->points as $itemIndex => $itemrow) { ?>
			<tr>
				<td>
					<span>
						<a style="vertical-align:middle" class="page-title-action" href="admin.php?page=view_item&itemid=<?= $userItem->allItems[$itemIndex] ?>">
						<?= $userItem->allItems[$itemIndex] ?></a>
					</span>
				
<?php 			foreach ($itemrow as $points) { ?>				
					<td><?= $points ?></td>
<?php 			} ?>			
			</tr>			
<?php 		} ?>
		</table>			
			
		
		
		
		
		<?php 
	}
		
	
	
	
	
}
?>