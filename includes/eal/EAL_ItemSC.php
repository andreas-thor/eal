<?php

require_once ("EAL_Item.php");

class EAL_ItemSC extends EAL_Item {
	
	/**
	 * 
	 * @var array
	 */
	private $answers;
	 
	
	function __construct(int $id = -1) {
		
		parent::__construct($id);
		
		$this->clearAnswers();
		$this->addAnswer('', 1);
		$this->addAnswer('', 0);
		$this->addAnswer('', 0);
		$this->addAnswer('', 0);
		
		$this->minnumber = 1;
		$this->maxnumber = 1;
	}
	
	
	public static function createFromArray (int $id, array $object = NULL, string $prefix = ''): EAL_ItemSC {
		
		$item = new EAL_ItemSC($id);
		$item->initFromArray($object, $prefix, 'item_level_');
		return $item;
	}
	
	
	protected function initFromArray (array $object, string $prefix, string $levelPrefix) {
		
		parent::initFromArray($object, $prefix, $levelPrefix);
		$this->clearAnswers();
		if (isset($object[$prefix . 'answer'])) {
			foreach ($object[$prefix . 'answer'] as $k => $v) {
				$this->addAnswer(html_entity_decode (stripslashes($v)), intval ($object[$prefix . 'points'][$k]));
			}
		}
			
	}
	
	
	public static function getType(): string {
		return 'itemsc';
	}
	
	
	public function clearAnswers() {
		$this->answers = array();
	}
	
	public function addAnswer (string $text, int $points) {
		array_push ($this->answers, array ('answer' => $text, 'points' => $points));
	}
	
	public function getNumberOfAnswers (): int {
		return count($this->answers);
	}
	
	public function getAnswer (int $index): string {
		return $this->answers[$index]['answer'] ?? '';
	}
	
	public function getPointsChecked (int $index): int {
		return $this->answers[$index]['points'] ?? 0;
	}
	
	
	
	
	public function getHTMLPrinter (): HTML_Item {
		return new HTML_ItemSC($this);
	}
	
	
	public function getPoints(): int {
		
		$result = 0;
		for ($index=0; $index<$this->getNumberOfAnswers(); $index++) {
			$result = max ($result, $this->getPointsChecked($index));
		}
		return $result;
		
	}



	
}

?>