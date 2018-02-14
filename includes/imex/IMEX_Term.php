<?php

// require_once( ABSPATH . 'wp-includes/class-wp-term.php' );
require_once ('IMEX_Object.php');

class IMEX_Term extends IMEX_Object {
	
	private $taxonomy;
	private $allterms;
	
	public function __construct() {
		parent::__construct();
		
	}
	
	
	public function downloadTerms (string $taxonomy, int $termId, string $format) {
		
		$this->taxonomy = $taxonomy;
		$this->allterms = [];	// array of array [WP_Term, Level(int)]
		
		
		$this->downloadfilename = time() . '_term_' . $taxonomy . "_" . $termId;
		$this->downloadextension = $format;
		
		
		if ($termId < 0) {
			// get all root terms
			foreach (get_terms (array ('taxonomy' => $this->taxonomy, 'parent'=> 0, 'hide_empty' => false)) as $term) {
				$this->getTopicTerm($term, 0);
			}
		} else {
			// get term with by given termId
			$this->getTopicTerm (get_term ($termId, $this->taxonomy), 0);
		}

		
		$result = "";
		
		if ($format == "txt") {
			foreach ($this->allterms as $at) {
				$result .= str_repeat ("  ", $at[1]*2) . $at[0]->name . PHP_EOL;
			}
		} 
		if ($format == "json") {
			$json = [];
			foreach ($this->allterms as $at) {
				$json[] = ['id' => $at[0]->term_id, 'name' => $at[0]->name, 'parent' => $at[0]->parent];
			}
			$result = json_encode($json);
			
		}
		
		
		
		file_put_contents($this->getDownloadFullname(), $result);
	
		$this->download();
	}
	
	
	

	
	private function getTopicTerm ($term, int $level) {
		
		$this->allterms[] = [$term, $level];
		foreach (get_terms (array ('taxonomy' => $this->taxonomy, 'parent'=> $term->term_id, 'hide_empty' => false)) as $t) {
			$this->getTopicTerm ($t, $level+1);
		}
	}
	
	
	public function upload(array $file) {}

	
}

?>