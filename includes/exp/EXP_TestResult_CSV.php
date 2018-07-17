<?php


require_once 'EXP_TestResult.php';

class EXP_TestResult_CSV extends EXP_TestResult {
	
	public function __construct() {
		parent::__construct (time() . '_testresult', 'csv');
	}
	
	
	protected function generateExportFile (int $testResultId) {
		
		
		$testData = TRES_UserItem::loadFromDB($testResultId);
		
		$handle = fopen($this->getDownloadFullname(), 'w');

		fputcsv($handle, array_merge([''], $testData->allItems));
		
		foreach ($testData->allUsers as $userIndex => $userId) {
			$row = [$userId];
			for ($itemIndex = 0; $itemIndex<count($testData->allItems); $itemIndex++) {
				$row[] = $testData->points[$itemIndex][$userIndex];
			}
			fputcsv($handle, $row);
			
		}
		
		fclose ($handle);
	}
	

	
	


	
}

?>