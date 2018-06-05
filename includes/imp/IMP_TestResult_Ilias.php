<?php

require_once 'IMP_TestResult.php';

class IMP_TestResult_Ilias extends IMP_TestResult {
	
	
	public function parseTestResultFromTestData(array $testdata): array {
		
		$result = [];
		$result[] = ['user_id'=>1, 'item_id'=>2, 'points'=>565];
		return $result;
		
	}

	
	
}

?>