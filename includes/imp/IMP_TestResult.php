<?php

abstract class IMP_TestResult {
	
	
	/**
	 * @return array [ ['user_id'=>..., 'item_id'=>..., 'points'=>...] 
	 */
	
	abstract public function getUserItemDataFromFile (array $file): array;
		
	
	/**
	 * 
	 * @param array $itemids
	 * @param bool $updateMetadataOnly
	 * @return array map of itemids that have been imported / updated; [new Item Id => old Item Id]
	 */
	public function importTestResult (array $testdata) {
		
		global $testresultToImport;

		date_default_timezone_set(get_option('timezone_string'));
		$testresultToImport = EAL_TestResult::createFromArray(0, ['post_title'=>'Import from ' . date('Y-m-d H:i:s')]);
		$testresultToImport->initUserItemFromArray($this->getUserItemDataFromFile ($testdata));
		
		$postarr = array ();
		$postarr['ID'] = 0;	
		$postarr['post_title'] = $testresultToImport->getTitle();
		$postarr['post_status'] = 'publish';
		$postarr['post_type'] = 'testresult';
		$postarr['post_content'] = microtime();
		$id = wp_insert_post ($postarr);
		
		
	}
	
}

?>