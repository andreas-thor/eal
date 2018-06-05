<?php

require_once 'IMP_TestResult.php';

class IMP_TestResult_Ilias extends IMP_TestResult {
	
	
	public function parseTestResultFromTestData(array $testdata, array $mapItemIds): array {
		
		$result = [];
		
		$mapQuestionId2ItemId = [];
		foreach ($mapItemIds as $new_item_id => $old_item_id) {
			$mapQuestionId2ItemId [$testdata['mapitemid2xml'][$old_item_id]] = $new_item_id;
		}
		
		
		$xml = file_get_contents ($testdata['testxml']);
		$doc = new DOMDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		foreach ($xpath->evaluate("//tst_test_result/row") as $row) {
			
			
			$user_id = $row->getAttribute("active_fi");
			$question_id = $row->getAttribute("question_fi");
			$points = $row->getAttribute("points");

			if (isset ($mapQuestionId2ItemId[$question_id])) {
				$result[] = ['user_id'=>$user_id, 'item_id'=>$mapQuestionId2ItemId[$question_id], 'points'=>$points];
			}
		}
		
		
		
		return $result;
		
	}

	
	
}

?>