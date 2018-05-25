<?php

require_once 'IMP_Term.php';

class IMP_Term_JSON extends IMP_Term {
	
	
	/**
	 * 
	 * @param array $file
	 * @throws Exception
	 * @return array of terms
	 */
	public function parseTermsFromImportFile (array $file): array {
		
		
		$content = file_get_contents($file['tmp_name']);
		if ($content === FALSE) {
			return [];	// FIXME: error while reading file
		} 
		
		return json_decode($content, TRUE);
	}
	
	
	

	
	
}