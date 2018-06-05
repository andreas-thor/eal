<?php

abstract class IMP_TestResult {
	
	
	
	abstract public function parseTestResultFromTestData (array $testdata, array $mapItemIds): array;
	
	
		
	
	/**
	 * 
	 * @param array $itemids
	 * @param bool $updateMetadataOnly
	 * @return array map of itemids that have been imported / updated; [new Item Id => old Item Id]
	 */
	public function importTestResult (array $testdata, array $mapItemIds) {
		
		global $testresultToImport;

		date_default_timezone_set(get_option('timezone_string'));
		$testresultToImport = EAL_TestResult::createFromArray(0, ['post_title'=>'Import from ' . date('Y-m-d H:i:s')]);
		
		$postarr = array ();
		$postarr['ID'] = 0;	
		$postarr['post_title'] = $testresultToImport->getTitle();
		$postarr['post_status'] = 'publish';
		$postarr['post_type'] = 'testresult';
		$postarr['post_content'] = microtime();
		$id = wp_insert_post ($postarr);
		
		$user_item_result = $this->parseTestResultFromTestData($testdata, $mapItemIds);
		TRES_UserItem::saveToDB($id, $user_item_result);
		

		
	}
	
}

?>