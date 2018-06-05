<?php



class DB_TestResult {
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_testresult';
	}
	
	public static function saveToDB (EAL_TestResult $testresult) {
		
		
		global $wpdb;
		$wpdb->replace(
			self::getTableName(),
			array(
				'id' => $testresult->getId(),
				'title' => $testresult->getTitle(),
				'description' => $testresult->getDescription(),
				'domain' => $testresult->getDomain()
			),
			array('%d','%s','%s', '%s')
			);
	}
	
	

	


	
	
	public static function deleteFromDB (int $testresult_id) {
		
		global $wpdb;
		
		$wpdb->delete( self::getTableName(), array( 'id' => $testresult_id ), array( '%d' ) );
		
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
		
		return EAL_TestResult::createFromArray($testresult_id, $object);
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
				PRIMARY KEY  (id),
				KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . "_useritem (
			test_id bigint(20) unsigned NOT NULL,
			item_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			points smallint,
			PRIMARY KEY  (test_id, item_id, user_id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
}

?>