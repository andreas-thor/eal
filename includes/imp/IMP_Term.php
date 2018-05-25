<?php



abstract class IMP_Term {
	
	protected $taxonomy;
	
	
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
		
		$this->taxonomy = $taxonomy;
		$terms = $this->parseTermsFromImportFile($file);

		if (count($terms)==0) {
			return;	// nothing to import
		}
		
		if ((count($terms)==1) && ($termId>0)) {	// we have one root term 

			$term = get_term($termId, $this->taxonomy);
			if ($term instanceof WP_Term) {
				
				if ($terms[0]['name'] == $term->name) {		// check if we should update the root term ...
					wp_update_term($termId, $this->taxonomy, ['description' => $terms[0]['description']]);
					$this->addTerms($terms[0]['children'], $termId);
				} else {
					$this->addTerms($terms, $termId);	// if not: add it as child term
				}
			} else {
				return; 	// could not find current term
			}
		} else {	
			$this->addTerms($terms, $termId);
		}
		
	}
	
	
	/**
	 * add the term under the fiven parent term
	 * @param array $termsToImport [ ['name'=> ... , 'description'=> ..., 'children'=> [...] ]
	 * @param int $parentTermId
	 */
	private function addTerms (array $termsToImport, int $parentTermId) {
		
		if ($parentTermId<0) $parentTermId = 0;
		
		$existingTerms = get_terms (array ('taxonomy' => $this->taxonomy, 'parent'=> $parentTermId, 'hide_empty' => false));
		
		foreach ($termsToImport as $term) {
			
			$termId = -1;
			
			// search for already existing term; if found --> update description
			foreach ($existingTerms as $exTerm) {
				if ($term['name'] == $exTerm->name) {
					$termId = $exTerm->term_id;
					wp_update_term($termId, $this->taxonomy, ['description' => $term['description']]);
				}
			}
			
			// no matching existing term
			if ($termId == -1) {
				$insert = wp_insert_term ($term['name'], $this->taxonomy, ['description' => $term['description'], 'parent' => $parentTermId]);
				if (is_array ($insert)) {
					$termId = $insert['term_id'];
				} else {
					continue; 	// FIXME: Error when inserting
				}
			}
			
			$this->addTerms($term['children'], $termId);
			
		}
		
	}
	
	
}

?>