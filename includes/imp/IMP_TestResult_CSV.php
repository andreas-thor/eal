<?php

require_once 'IMP_TestResult.php';

class IMP_TestResult_CSV extends IMP_TestResult {
	
	
	public function getUserItemDataFromFile (array $file): array {
		
		$userItemdata = array_map('str_getcsv', file($file['tmp_name']));	// CSV as array
		
		if (count($userItemdata)<2) return [];	// need header + at least one user
		
		$header = $userItemdata[0];
		for ($rowNumber=1; $rowNumber<count($userItemdata); $rowNumber++) {
			$row = $userItemdata[$rowNumber];
			if (count($row)<2) continue;	// 1st column = userid; 2nd++ points for items
			
			$user_id = $row[0];
			for ($colNumber=1; $colNumber<count($row); $colNumber++) {
				if ($colNumber>=count($header)) continue; 	// do not have a item id for this column
				
				$points = $row[$colNumber];
				if (is_numeric($points)) {
					$result[] = ['user_id'=>$user_id, 'item_id'=>$header[$colNumber], 'points'=>$points];
				}
			}
		}
		
		return $result;
		
	}
	

	
	
}

?>