<?php


require_once 'EXP_Term.php';

class EXP_Term_JSON extends EXP_Term {
	
	
	public function __construct(string $taxonomy) {
		parent::__construct (time() . '_terms', 'json', $taxonomy);
	}
	
	
	protected function generateExportFile (array $terms) {
		file_put_contents($this->getDownloadFullname(), json_encode($terms));
	}
	
	
	


	
}

?>