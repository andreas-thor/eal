<?php



class DB_TestResult {
	
	
	public static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_testresult';
	}
	
	public static function saveToDB (EAL_TestResult $testresult) {
		
		global $wpdb;
		
		
		if ($testresult->getNumberOfItems() == 0) {

			// update core meta data only; remain user-item data incl. number of items/users
			$sqlres = $wpdb->get_row( sprintf('SELECT no_of_items, no_of_users FROM %s WHERE id = %d', self::getTableName(), $testresult->getId()), ARRAY_A);

			$wpdb->replace(
				self::getTableName(),
				array(
					'id' => $testresult->getId(),
					'title' => $testresult->getTitle(),
					'description' => $testresult->getDescription(),
					'domain' => $testresult->getDomain(),
					'date_of_test' => $testresult->getDateOfTest(), 
					'no_of_items' => ($sqlres != NULL) ? $sqlres['no_of_items'] : NULL,
					'no_of_users' => ($sqlres != NULL) ? $sqlres['no_of_users'] : NULL
				),
				array('%d','%s','%s','%s','%s','%d','%d')
			);
			
// FIXME: needed?			self::updateItemStatistics($testresult->getId());
			return;
		}
		
		// update all meta data
		$wpdb->replace(
			self::getTableName(),
			array(
				'id' => $testresult->getId(),
				'title' => $testresult->getTitle(),
				'description' => $testresult->getDescription(),
				'domain' => $testresult->getDomain(),
				'date_of_test' => $testresult->getDateOfTest(), 
				'no_of_items' => $testresult->getNumberOfItems(),
				'no_of_users' => $testresult->getNumberOfUsers()
			),
			array('%d','%s','%s','%s','%s','%d','%d')
			);
		
		
		// user-item-table ...
		// (1) delete old values
		
		$wpdb->delete( 
			self::getTableName() . '_useritem', 
			array( 
				'test_id' => $testresult->getId() 
			), 
			array( '%d' ) 
		);
		
		// (2) collect user-item-points 
		$values = array();
		$insert = array();
		
		for ($userIndex = 0; $userIndex < $testresult->getNumberOfUsers(); $userIndex++) {
			for ($itemIndex = 0; $itemIndex < $testresult->getNumberOfItems(); $itemIndex++) {
				$points = $testresult->getPoints($itemIndex, $userIndex);
				if (is_numeric($points)) {
					array_push($values, $testresult->getId(), $testresult->getUserId($userIndex), $testresult->getItemId($itemIndex), $points);
					array_push($insert, "(%d, %d, %d, %d)");
				}
			}
		}
		
		// (3) insert points
		$query = "INSERT INTO " . self::getTableName() . "_useritem (test_id, user_id, item_id, points) VALUES ";
		$query .= implode(', ', $insert);
		$wpdb->query( $wpdb->prepare("$query ", $values));

		DB_Item::updateDifficultyAndNumberOfTestResults($testresult->getAllItemsIds());

	}
	
	
	
	private static function updateItemStatistics (int $testresult_id, bool $includeCurrentTestResult = TRUE) {
		
		global $wpdb;
		
		$removeCurrentTestResult = '';
		if ($includeCurrentTestResult === FALSE) {
			$removeCurrentTestResult = sprintf (' and t.test_id != %d', $testresult_id);
		}
		
		
		// Update Difficulty for all items in this test
		$sql = sprintf ('
		UPDATE %2$s AS U
		INNER JOIN (
			SELECT t.item_id, (avg(t.points) / i.points) as difficulty, count(distinct t.test_id) as no_of_testresults
			from %1$s t
			join %2$s i
			on (t.item_id=i.id)
			where t.item_id in (SELECT item_id FROM %1$s WHERE test_id = %3$d)
			%4$s 
			group by t.item_id
		) AS J ON (U.id = J.item_id)
		SET U.difficulty = J.difficulty, U.no_of_testresults = J.no_of_testresults',
			self::getTableName() . '_useritem', DB_Item::getTableName(), $testresult_id, $removeCurrentTestResult);
		 
		 
		 $wpdb->query ($sql);
	}

	


	
	
	public static function deleteFromDB (int $testresult_id) {
		
		global $wpdb;

		// get all item ids of test --> their difficulty and no_of_testresults need to be updated 
		$itemids = $wpdb->get_col('
			SELECT DISTINCT item_id 
			FROM ' . self::getTableName() . '_useritem 
			WHERE test_id = ' . $testresult_id);
		
		$wpdb->delete( self::getTableName(), array( 'id' => $testresult_id ), array( '%d' ) );
		$wpdb->delete( self::getTableName() . '_useritem', array( 'test_id' => $testresult_id ), array( '%d' ) );
		
		DB_Item::updateDifficultyAndNumberOfTestResults($itemIds);
	}
	
	
	/**
	 */
	public static function loadFromDB (int $testresult_id): EAL_TestResult {
		
			
		global $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE id = {$testresult_id}", ARRAY_A);
		
		$object = [];
		$object['post_title'] = $sqlres['title'] ?? '';
		$object['testresult_description'] = $sqlres['description'] ?? '';
		$object['domain'] = $sqlres['domain'] ?? '';
		$object['testresult_date'] = $sqlres['date_of_test'] ?? '';
		
		$result = EAL_TestResult::createFromArray($testresult_id, $object);
		
		$userItemData = $wpdb->get_results(sprintf ('SELECT user_id, item_id, points FROM %s WHERE test_id = %d', self::getTableName() . '_useritem', $testresult_id), ARRAY_A);
		$result->initUserItemFromArray($userItemData);
		
		return $result;
	}
	
	
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		dbDelta (
			"CREATE TABLE " . self::getTableName() . " (
				id bigint(20) unsigned NOT NULL,
				title mediumtext,
				description mediumtext,
				domain varchar(50) NOT NULL,
				date_of_test varchar(255), 
				no_of_items bigint(20), 
				no_of_users bigint(20), 
				PRIMARY KEY  (id),
				KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . "_useritem (
			test_id bigint(20) unsigned NOT NULL,
			item_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			points float,
			PRIMARY KEY  (test_id, item_id, user_id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
}

?>