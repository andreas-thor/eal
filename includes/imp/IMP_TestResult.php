<?php

abstract class IMP_TestResult {
	
	
	
	abstract public function parseTestResultFromTestData (array $testdata): array;
	
	
		
	
	/**
	 * 
	 * @param array $itemids
	 * @param bool $updateMetadataOnly
	 * @return array map of itemids that have been imported / updated; [new Item Id => old Item Id]
	 */
	public static function importTestResult (array $testdata) {
		
		$t = microtime();
		$postarr = array ();
		$postarr['ID'] = 0;	
		$postarr['post_title'] = 'Import from ' . $t;
		$postarr['post_status'] = 'publish';
		$postarr['post_type'] = 'testresult';
		$postarr['post_content'] = $t;
		$id = wp_insert_post ($postarr);
		
		
		$post = get_post ($id);
		$post->post_title = 'Import from ' . $t;
		$post->post_status = 'publish';
		$post->post_content = microtime();	// ensures revision
		wp_update_post ($post);
		
	}
	
}

?>