<?php


require_once 'EXP_Term.php';

class EXP_Term_TXT extends EXP_Term {
	
	public function __construct(string $taxonomy) {
		parent::__construct (time() . '_terms', 'txt', $taxonomy);
	}
	
	
	protected function generateExportFile (array $terms) {
		
		$result = '';
		foreach ($terms as $term) {
			$result .= $this->getLine($term, 0);
		}
		
		file_put_contents($this->getDownloadFullname(), $result);
	}
	
	private function getLine (array $term, int $level): string {
		
		$result = str_repeat ('>', $level) . $term['name'] . "\t" . $term['description'] . "\r\n";
		foreach ($term['children'] as $childTerm) {
			$result .= $this->getLine($childTerm, $level+1);
		}
		return $result;
	}
	
	


	
}

?>