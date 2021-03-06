<?php

class TRES_UserItem {
	
	public $allUsers = [];
	public $allItems = [];
	public $points = [];
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_testresult_useritem';
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
	
	
	/**
	 * 
	 * @param int $testresult_id
	 * @param array $user_item_result  [ ['user_id'=>..., 'item_id'=>..., 'points'=>...] ]
	 */
	public static function saveToDB (int $testresult_id, array $user_item_result) {
		
		global $wpdb;
		
		$values = array();
		$insert = array();
		
		foreach ($user_item_result as $user_item) {
			array_push($values, $testresult_id, $user_item['user_id'], $user_item['item_id'], $user_item['points']);
			array_push($insert, "(%d, %d, %d, %d)");
		}
		
		// replace answers
		$query = "REPLACE INTO " . self::getTableName() . " (test_id, user_id, item_id, points) VALUES ";
		$query .= implode(', ', $insert);
		$a = $wpdb->query( $wpdb->prepare("$query ", $values));
		$b = 7;
	}
	
	
	public static function loadFromDB (int $testresult_id): TRES_UserItem {
		
		global $wpdb;
			
		$result = new TRES_UserItem();
		
		$result->allUsers = $wpdb->get_col( "SELECT DISTINCT user_id FROM " . self::getTableName() . " WHERE test_id = {$testresult_id}");
		$result->allItems = $wpdb->get_col( "SELECT DISTINCT item_id FROM " . self::getTableName() . " WHERE test_id = {$testresult_id}");

		$result->points = array_fill (0, count($result->allItems), array_fill (0, count($result->allUsers), NULL));
		
			
		$sqlres = $wpdb->get_results("SELECT user_id, item_id, points FROM " . self::getTableName() . " WHERE test_id = {$testresult_id}", ARRAY_A);
		foreach ($sqlres as $row) {
			$result->points [array_search($row['item_id'], $result->allItems)][array_search($row['user_id'], $result->allUsers)] = $row['points'];
		}
		
		return $result;
		
	}
}

?>