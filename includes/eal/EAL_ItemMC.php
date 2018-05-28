<?php

require_once 'EAL_Item.php';
require_once __DIR__ . '/../html/HTML_ItemMC.php';
 


class EAL_ItemMC extends EAL_Item {
	
	
	
	/**
	 * @var array
	 */
	private $answers = array();

	
	function __construct(int $id = -1, int $learnout_id=-1) {

		parent::__construct($id, $learnout_id);
		
		$this->clearAnswers();
		$this->addAnswer('', 1, 0);
		$this->addAnswer('', 1, 0);
		$this->addAnswer('', 0, 1);
		$this->addAnswer('', 0, 1);
		
		$this->minnumber=0;
		$this->maxnumber=$this->getNumberOfAnswers();
	}
	
	
	
	public static function createFromArray (int $id, array $object = NULL, string $prefix = ''): EAL_ItemMC {
		
		$item = new EAL_ItemMC($id);
		$item->initFromArray($object, $prefix, 'item_level_');
		return $item;
	}
	
	
	
	public function initFromArray (array $object, string $prefix, string $levelPrefix) {
		
		parent::initFromArray($object, $prefix, $levelPrefix);
		
		if (isset($object[$prefix . 'answer'])) {
			$this->clearAnswers();
			foreach ($object[$prefix . 'answer'] as $k => $v) {
				$this->addAnswer(html_entity_decode (stripslashes($v)), intval ($object[$prefix . 'positive'][$k]), intval ($object[$prefix . 'negative'][$k]));
			}
			$this->minnumber = 0;
			$this->maxnumber = $this->getNumberOfAnswers();
		}

		if (isset ($object[$prefix . 'item_minnumber'])) {
			$this->minnumber = intval ($object[$prefix . 'item_minnumber']);
		}
		
		if (isset ($object[$prefix . 'item_maxnumber'])) {
			$this->maxnumber = intval ($object[$prefix . 'item_maxnumber']);
		}
	}
	
	
	public function convertToArray (string $prefix, string $levelPrefix): array {
		
		$object = parent::convertToArray($prefix, $levelPrefix);
		$object[$prefix . 'answer'] = [];
		$object[$prefix . 'positive'] = [];
		$object[$prefix . 'negative'] = [];
		for ($index=0; $index<$this->getNumberOfAnswers(); $index++) {
			$object[$prefix . 'answer'][$index] =  $this->getAnswer($index);
			$object[$prefix . 'positive'][$index] = $this->getPointsPos($index);
			$object[$prefix . 'negative'][$index] = $this->getPointsNeg($index);
		}
		$object[$prefix . 'item_minnumber'] = $this->minnumber;
		$object[$prefix . 'item_maxnumber'] = $this->maxnumber;
		$object[$prefix . 'post_type'] = 'itemmc';
		return $object;
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