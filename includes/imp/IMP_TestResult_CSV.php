<?php

require_once 'IMP_TestResult.php';

class IMP_TestResult_CSV extends IMP_TestResult {
	
	
	public function getTestDataFromFile (array $file): array {
		return array_map('str_getcsv', file($file['tmp_name']));	// CSV as array
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see IMP_TestResult::parseTestResultFromTestData()
	 * 
	 */
	public function parseTestResultFromTestData(array $testdata, array $mapItemIds): array {
		
		if (count($testdata)<2) return [];	// need header + at least one user
		
		$header = $testdata[0];
		for ($rowNumber=1; $rowNumber<count($testdata); $rowNumber++) {
			$row = $testdata[$rowNumber];
			if (count($row)<2) continue;	// 1st column = userid; 2nd++ points for items
			
			$user_id = $row[0];
			for ($colNumber=1; $colNumber<count($row); $colNumber++) {
				if ($colNumber>=count($header)) continue; 	// do not have a item id for this column
				$result[] = ['user_id'=>$user_id, 'item_id'=>$header[$colNumber], 'points'=>$row[$colNumber]];
			}
		}
		
		return $result;
		
	}

	
	
}

?>