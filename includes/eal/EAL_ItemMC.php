<?php

require_once ("EAL_Item.php");

 


class EAL_ItemMC extends EAL_Item {
	
	
	
	/**
	 * @var array
	 */
	private $answers = array();

	
	function __construct() {

		parent::__construct();
		
		$this->clearAnswers();
		$this->addAnswer('', 1, 0);
		$this->addAnswer('', 1, 0);
		$this->addAnswer('', 0, 1);
		$this->addAnswer('', 0, 1);
		
		$this->minnumber=0;
		$this->maxnumber=$this->getNumberOfAnswers();
	}
	
	
	public static function getType(): string {
		return 'itemmc';
	}
	
	public function clearAnswers() {
		$this->answers = array();
	}
	
	public function addAnswer (string $text, int $pos, int $neg) {
		array_push ($this->answers, array ('answer' => $text, 'positive' => $pos, 'negative' => $neg));
	}
	
	public function getNumberOfAnswers (): int {
		return count($this->answers);
	}
	
	public function getAnswer (int $index): string {
		return $this->answers[$index]['answer'] ?? '';
	}
	
	public function getPointsPos (int $index): int {
		return $this->answers[$index]['positive'] ?? 0;
	}
	
	public function getPointsNeg (int $index): int {
		return $this->answers[$index]['negative'] ?? 0;
	}
	
	
	public function getHTMLPrinter (): HTML_Item {
		return new HTML_ItemMC($this);
	}
	
	
	public function getPoints(): int {
		
		$result = 0;
		for ($index=0; $index<$this->getNumberOfAnswers(); $index++) {
			$result += max ($this->getPointsPos($index), $this->getPointsNeg($index));
		}
		return $result;
		
	}
	

	
}

?>