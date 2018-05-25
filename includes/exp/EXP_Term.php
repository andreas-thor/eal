<?php


require_once 'EXP_Object.php';

abstract class EXP_Term extends EXP_Object {
	
	protected $taxonomy;
	
	
	public function __construct(string $downloadfilename, string $downloadextension, string $taxonomy) {
		parent::__construct($downloadfilename, $downloadextension);
		$this->taxonomy = $taxonomy;
	}
	
	
	public function downloadTerms (int $termId) {
		$this->generateExportFile($this->getAllTerms($termId));
		$this->download();
	}
	
	
	public function getAllTerms (int $termId): array {
		
		if ($termId <= 0) {	// get all root terms
			return $this->getAllChildTerms(0);
		} else {	// get the term hierarchy for termId as root 
			$term = get_term ($termId, $this->taxonomy);
			if ($term instanceof WP_Term) {
				return [[ 
					'name' => $term->name, 
					'description' => $term->description,
					'children' => $this->getAllChildTerms ($term->term_id)
				]];
			}
		}
	}
	
	
	private function getAllChildTerms (int $termId): array {
		
		$result = [];
		foreach (get_terms (array ('taxonomy' => $this->taxonomy, 'parent'=> $termId, 'hide_empty' => false)) as $term) {
			if ($term instanceof WP_Term) {
				$result[] = [
					"name" => $term->name,
					"description" => $term->description,
					"children" => $this->getAllChildTerms ($term->term_id)
				];
			}
		}
		return $result;
	}
		
		
	
	abstract protected function generateExportFile (array $terms);
	
	


	
}

?>