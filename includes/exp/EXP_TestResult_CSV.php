<?php


require_once 'EXP_TestResult.php';
require_once __DIR__ . '/../db/DB_TestResult.php';

class EXP_TestResult_CSV extends EXP_TestResult {
	
	public function __construct() {
		parent::__construct (time() . '_testresult', 'csv');
	}
	
	
	protected function generateExportFile (int $testResultId) {
		
		
		$testResult = DB_TestResult::loadFromDB($testResultId);
		
		$handle = fopen($this->getDownloadFullname(), 'w');

		// write first line: '' + list of all item ids
		$header = [''];
		for ($itemIndex = 0; $itemIndex<$testResult->getNumberOfItems(); $itemIndex++) {
			$header[] = $testResult->getItemId($itemIndex);
		}
		fputcsv($handle, $header);
		
		// write one row per user: userid + points per item
		for ($userIndex = 0; $userIndex<$testResult->getNumberOfUsers(); $userIndex++) {
			$row = [$testResult->getUserId($userIndex)];
			for ($itemIndex = 0; $itemIndex<$testResult->getNumberOfItems(); $itemIndex++) {
				$points = $testResult->getPoints($itemIndex, $userIndex);
				$row[] = ($points === NULL) ? '' : $points;
			}
			fputcsv($handle, $row);
			
		}
		
		fclose ($handle);
	}
	

	
	


	
}

?>