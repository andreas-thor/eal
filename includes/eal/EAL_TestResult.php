<?php

require_once 'EAL_Object.php';
require_once __DIR__ . '/../html/HTML_TestResult.php';

class EAL_TestResult extends EAL_Object  {

	private $title;
	private $description;
	private $dateOfTest; 
	
	private $allUserIds = [];
	private $allItemIds = [];
	private $allItems = [];
	private $points = [];
	
	function __construct(int $id = -1) {
		parent::__construct($id);
		$this->title = '';
		$this->description = '';
		$this->dateOfTest = '';
		$this->allUserIds = [];
		$this->allItemIds = [];
		$this->allItems = [];	// [item_id => EAL_Item]
		$this->points = [];
	}
	
	public static function createFromArray (int $id, array $object, string $prefix = ''): EAL_TestResult {
		$testresult = new EAL_TestResult($id);
		$testresult->initFromArray($object, $prefix);
		return $testresult;
	}
	
	/**
	 * 
	 * @param array $object = ['post_title' => ..., 'learnout_description' => ...
	 * @param string $prefix
	 * @param string $levelPrefix
	 */
	public function initFromArray (array $object, string $prefix, string $levelPrefix='') {

		parent::initFromArray($object, $prefix, '');
		
		if (isset ($object[$prefix . 'post_title'])) {
			$this->title = stripslashes($object[$prefix . 'post_title']);
		}
		
		if (isset ($object[$prefix . 'testresult_description'])) {
			$this->description = html_entity_decode (stripslashes($object[$prefix . 'testresult_description']));
		}
		
		if (isset ($object[$prefix . 'testresult_date'])) {
			$this->dateOfTest = $object[$prefix . 'testresult_date'];
		}
		
	}
	
	
	/**
	 * 
	 * @param array $object [ ['item_id'=> ..., 'user_id'=>..., 'points'=>...] ]
	 */
	public function initUserItemFromArray (array $object) {
		
		// add all users and items
		$this->allUserIds = [];
		$this->allItemIds = [];
		foreach ($object as $row) {
			if (array_search($row['item_id'], $this->allItemIds) === FALSE) {
				$this->allItemIds[] = $row['item_id'];
			}
			if (array_search($row['user_id'], $this->allUserIds) === FALSE) {
				$this->allUserIds[] = $row['user_id'];
			}
		}

		foreach ($this->allItemIds as $itemId) {
			// load item
			$post = get_post($itemId);
			if ($post == null) continue;	// item (post) does not exist
			$this->allItems [$itemId] = DB_Item::loadFromDB($itemId, $post->post_type);
		}
		
		// set points
 		$this->points = array_fill (0, count($this->allItemIds), array_fill (0, count($this->allUserIds), 0));
		foreach ($object as $row) {
			$this->points [array_search($row['item_id'], $this->allItemIds)][array_search($row['user_id'], $this->allUserIds)] = $row['points'];
		}
		
	}
	
	public static function getType(): string {
		return 'testresult';
	}
	
	public function getTitle (): string {
		return $this->title;
	}
	
	public function getDescription (): string {
		return $this->description;
	}
	
	public function getNumberOfUsers(): int {
		return count($this->allUserIds);
	}
	
	public function getNumberOfItems(): int {
		return count($this->allItemIds);
	}
	
	public function getUserId (int $userIndex): int {
		if (($userIndex<0) || ($userIndex>=count($this->allUserIds))) {
			return -1;
		}
		return $this->allUserIds[$userIndex];
	}
	
	public function getItemId (int $itemIndex): int {
		if (($itemIndex<0) || ($itemIndex>=count($this->allItemIds))) {
			return -1;
		}
		return $this->allItemIds[$itemIndex];
	}
	
	
	public function getAllItemsIds (): array {
		return $this->allItemIds;
	}
	
	public function getPoints (int $itemIndex, int $userIndex) {
		if (($itemIndex<0) || ($itemIndex>=count($this->allItemIds))) {
			return NULL;
		}
		if (($userIndex<0) || ($userIndex>=count($this->allUserIds))) {
			return NULL;
		}
		return $this->points[$itemIndex][$userIndex];
	}
	
	private function getItem (int $itemIndex): EAL_Item {
		return $this->allItems[$this->allItemIds[$itemIndex]];
	}
	
	public function getItemDifficulty (int $itemIndex): float {
		
		if (($itemIndex<0) || ($itemIndex>=count($this->allItemIds))) return -1;	// index out of range
		return $this->getAverage($itemIndex)/$this->getItem($itemIndex)->getPoints();
	}

	
	public function getDateOfTest (): string {
		return $this->dateOfTest;
	}
	

	public function getItemTotalCorrelation (int $itemIndex): float {
		
		$dataTestWithoutItem = array_fill (0, count($this->allUserIds), 0);
		for ($index=0; $index<$this->getNumberOfItems(); $index++) {
			if ($index == $itemIndex) continue;	// do not consider current item
			foreach ($this->points[$index] as $userIndex => $points) {
				$dataTestWithoutItem[$userIndex] = $dataTestWithoutItem[$userIndex] + $points;
			}
		}

		return $this->getCorrelation($this->points[$itemIndex], $dataTestWithoutItem);
	}

	
	public function getInterItemCorrelation (): array {
		
		$result = [];
		for ($itemIndex1 = 0; $itemIndex1<$this->getNumberOfItems(); $itemIndex1++) {
			for ($itemIndex2 = 0; $itemIndex2<$this->getNumberOfItems(); $itemIndex2++) {
				if ($itemIndex1<$itemIndex2) {
					$result[$itemIndex1][$itemIndex2] = $this->getCorrelation($this->points[$itemIndex1], $this->points[$itemIndex2]);
					$result[$itemIndex2][$itemIndex1] = $result[$itemIndex1][$itemIndex2];
				}
			}
		}
		return $result;
	}
	
	public function getItemIdsByCategory (string $cat): array {
		return ItemExplorer::groupBy($this->allItems, $this->allItemIds, $cat);
	}
	
	public function getItemCorrelationByCategory (string $cat): array {
		
		$result = [];
		$data = [];
		$group = ItemExplorer::groupBy($this->allItems, $this->allItemIds, $cat);
		
		foreach ($group as $name => $itemIds) {
			$result[$name] = [];
			$data[$name] = array_fill (0, count($this->allUserIds), 0);
			foreach ($itemIds as $itemId) {
				$itemIndex = array_search($itemId, $this->allItemIds);
				foreach ($this->points[$itemIndex] as $userIndex => $points) {
					$data[$name][$userIndex] = $data[$name][$userIndex] + $points;
				}
			}
		}
			
		foreach ($data as $name1 => $d1) {
			foreach ($data as $name2 => $d2) {
				if ($name1<$name2) {
					$result[$name1][$name2] = $this->getCorrelation($d1, $d2);
					$result[$name2][$name1] = $result[$name1][$name2];
				}
			}
		}
		
		return $result;		
	}
	
	
	private function getCorrelation (array $x, array $y) {
		
		$varX = stats_variance (array_values($x));
		$varY = stats_variance (array_values($y));
		$coVarXY = stats_covariance (array_values($x), array_values($y));
		$result = (($varX==0) || ($varY==0)) ? NULL : $coVarXY / (sqrt ($varX) * sqrt ($varY));
		return $result;
	}
	
	private function getAverage (int $itemIndex): float {
		
		$sum = 0;
		$count = 0;
		foreach ($this->points[$itemIndex] as $userIndex => $points) {
			if ($points != NULL) {
				$sum += $points;
				$count++;
			}
		}
		return $sum/$count;

	}
	
	
	

	public function getHTMLPrinter (): HTML_TestResult {
		return new HTML_TestResult($this);
	}
	
}

?>