<?php



abstract class IMP_Term {
	
	
	
	/**
	 * @param array $file uploaded file ['name' => orginal name, 'tmp_name' => uploaded file name]
	 * @return array of Terms
	 * @throws Exception
	 */
	abstract public function parseTermsFromImportFile (array $file): array;
	
	
	/**
	 * 
	 * @param array $itemids
	 * @param bool $updateMetadataOnly
	 * @return array of itemids that have been imported / updated
	 */
	public function importTerms (array $file, string $taxonomy, int $termId ) {
		
		$terms = $this->parseTermsFromImportFile($file);
		
		$b=1;
	}
	
}

?>