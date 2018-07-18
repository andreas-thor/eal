<?php

require_once 'IMP_TestResult.php';

class IMP_TestResult_Ilias extends IMP_TestResult {
	
	
	private $mapItemIds;
	
	public function __construct(array $mapItemIds) {
		$this->mapItemIds = $mapItemIds;
	}
	
	
	
	public function getUserItemDataFromFile(array $file): array {
		
		$result = [];
		
		$mapQuestionId2ItemId = [];
		foreach ($this->mapItemIds as $new_item_id => $old_item_id) {
			$mapQuestionId2ItemId [$file['mapitemid2xml'][$old_item_id]] = $new_item_id;
		}
		
		
		$xml = file_get_contents ($file['testxml']);
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