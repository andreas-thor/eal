<?php



class DB_TestResult {
	
	
	private static function getTableName (): string {
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
					'no_of_items' => ($sqlres != NULL) ? $sqlres['no_of_items'] : NULL,
					'no_of_users' => ($sqlres != NULL) ? $sqlres['no_of_users'] : NULL
				),
				array('%d','%s','%s','%s','%d','%d')
			);
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
				'no_of_items' => $testresult->getNumberOfItems(),
				'no_of_users' => $testresult->getNumberOfUsers()
			),
			array('%d','%s','%s','%s','%d','%d')
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
		$a = $wpdb->query( $wpdb->prepare("$query ", $values));
		$b = 7;
	}
	
	

	


	
	
	public static function deleteFromDB (int $testresult_id) {
		
		global $wpdb;
		
		$wpdb->delete( self::getTableName(), array( 'id' => $testresult_id ), array( '%d' ) );
		$wpdb->delete( self::getTableName() . '_useritem', array( 'test_id' => $testresult_id ), array( '%d' ) );
		
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