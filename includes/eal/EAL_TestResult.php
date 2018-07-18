<?php

require_once 'EAL_Object.php';
require_once __DIR__ . '/../html/HTML_TestResult.php';

class EAL_TestResult extends EAL_Object  {

	private $title;
	private $description;
	
	private $allUsers = [];
	private $allItems = [];
	private $points = [];
	
	function __construct(int $id = -1) {
		parent::__construct($id);
		$this->title = '';
		$this->description = '';
		$this->allUsers = [];
		$this->allItems = [];
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
	}
	
	
	/**
	 * 
	 * @param array $object [ ['item_id'=> ..., 'user_id'=>..., 'points'=>...] ]
	 */
	public function initUserItemFromArray (array $object) {
		
		// add all users and items
		$this->allUsers = [];
		$this->allItems = [];
		foreach ($object as $row) {
			if (array_search($row['item_id'], $this->allItems) === FALSE) {
				$this->allItems[] = $row['item_id'];
			}
			if (array_search($row['user_id'], $this->allUsers) === FALSE) {
				$this->allUsers[] = $row['user_id'];
			}
		}

		// set points
		$this->points = array_fill (0, count($this->allItems), array_fill (0, count($this->allUsers), NULL));
		foreach ($object as $row) {
			$this->points [array_search($row['item_id'], $this->allItems)][array_search($row['user_id'], $this->allUsers)] = $row['points'];
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
		return count($this->allUsers);
	}
	
	public function getNumberOfItems(): int {
		return count($this->allItems);
	}
	
	public function getUserId (int $userIndex): int {
		if (($userIndex<0) || ($userIndex>=count($this->allUsers))) {
			return -1;
		}
		return $this->allUsers[$userIndex];
	}
	
	public function getItemId (int $itemIndex): int {
		if (($itemIndex<0) || ($itemIndex>=count($this->allItems))) {
			return -1;
		}
		return $this->allItems[$itemIndex];
	}
	
	public function getPoints (int $itemIndex, int $userIndex) {
		if (($itemIndex<0) || ($itemIndex>=count($this->allItems))) {
			return NULL;
		}
		if (($userIndex<0) || ($userIndex>=count($this->allUsers))) {
			return NULL;
		}
		return $this->points[$itemIndex][$userIndex];
	}
	
	

	public function getHTMLPrinter (): HTML_TestResult {
		return new HTML_TestResult($this);
	}
	
}

?>