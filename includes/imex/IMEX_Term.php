<?php


require_once('IMEX_Object.php');

class IMEX_Term extends IMEX_Object {
	
	private $taxonomy;
	
	public function __construct() {
		parent::__construct();
	}
	
	
	public function downloadTerms (string $taxonomy, int $termId) {
		
		$this->taxonomy = $taxonomy;
		
		$this->downloadfilename = time() . '_term_' . $taxonomy . "_" . $termId;
		$this->downloadextension = "txt";
		
		
		
		// $term = get_term ($termId /*, $taxonomy*/);
		$term = get_term_by('id', $termId, $taxonomy);
		
		if ($term != NULL) {
			file_put_contents($this->getDownloadFullname(), $this->getTopicTerm($term, 0));
		}
		
		$this->download();
	}
	
	
	

	
	private function getTopicTerm ($term, $level) {
		
		$result = str_repeat ("  ", $level*2) . $term->name . "\n";
		foreach (get_terms ($this->taxonomy, array ('parent'=> $term->term_id)) as $t) {
			$result .= $this->getTopicTerm ($t, $level+1);
		}
		return $result;
	}
	
	
	public function upload(array $file) {}

	
}

?>