<?php

require_once 'IMP_Term.php';

class IMP_Term_TXT extends IMP_Term {
	
	
	private $termTable; 
	
	/**
	 * 
	 * @param array $file
	 * @throws Exception
	 * @return array of terms
	 */
	public function parseTermsFromImportFile (array $file): array {
		
		
		$content = file($file['tmp_name']);
		
		if ($content === FALSE) {
			return [];	// FIXME: error while reading file
		} 
		
		$this->termTable = [];
		foreach ($content as $line) {
			$this->termTable[] = $this->getRowData($line);
			
		}

		
		$result = $this->getChildTerms(0, 0, count($this->termTable));
		return $result;
		
	}
	
	/**
	 * 
	 * @param int $level
	 * @param int $start
	 * @param int $end non-inclusive
	 * @return array
	 */
	private function getChildTerms (int $level, int $start, int $end): array {
		
		$result = [];
		$row = $start;
		
		
		$blockStart = -1;
		
		while ($row<$end) {
			
			if ($this->termTable[$row]['level']==$level) {	// start new children

				if ($blockStart > -1) {
					// new block: $blockstart, $row
					$result[] = [
						'name' => $this->termTable[$blockStart]['name'], 
						'description' => $this->termTable[$blockStart]['description'],
						'children' => $this->getChildTerms($level+1, $blockStart+1, $row)
					];
					
				}
				$blockStart = $row;
			}
			$row++;
		}
		
		if ($blockStart > -1) {
			// new block: $blockstart, $row
			$result[] = [
				'name' => $this->termTable[$blockStart]['name'],
				'description' => $this->termTable[$blockStart]['description'],
				'children' => $this->getChildTerms($level+1, $blockStart+1, $row)
			];
		}
		
		return $result;
	}
	
	
	
	private function getRowData (string $line): array {
		
		$line = trim($line);
		
		// count the number of > at the beginning (=$level) and remove them
		$level = 0;
		while (substr ($line, 0, 1) == '>') {
			$level += 1;
			$line = substr ($line, 1);
		}
		list($name, $description) = explode ("\t", $line, 2);
		return ['level' => $level, 'name' => $name, 'description' => $description];
		
	}
	
	
	
	


	
	
}