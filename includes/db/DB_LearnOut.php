<?php



class DB_Learnout {
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_learnout';
	}
	
	public static function saveToDB (EAL_LearnOut $learnout) {
		
		
		global $wpdb;
		$wpdb->replace(
			self::getTableName(),
			array(
				'id' => $learnout->getId(),
				'title' => $learnout->getTitle(),
				'description' => $learnout->getDescription(),
				'level_FW' => $learnout->getLevel()->get('FW'),
				'level_KW' => $learnout->getLevel()->get('KW'),
				'level_PW' => $learnout->getLevel()->get('PW'),
				'domain' => $learnout->getDomain()
			),
			array('%d','%s','%s','%d','%d','%d','%s')
			);
	}
	
	
	public static function deleteFromDB (int $learnout_id) {
		
		global $wpdb;
		
		// FIXME: Items mit dem Learning Outcome --> set Id auf -1 oder NULL
		$wpdb->delete( self::getTableName(), array( 'id' => $learnout_id ), array( '%d' ) );
		
	}
	
	
	/**
	 * Call-by-reference; $learnout properties are loaded into given instance
	 * @param EAL_LearnOut $learnout
	 */
	public static function loadFromDB (EAL_LearnOut &$learnout) {
		
			
		global $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE id = {$learnout->getId()}", ARRAY_A);
		$learnout->setId ($sqlres['id']);
		$learnout->setTitle($sqlres['title'] ?? '');
		$learnout->setDescription($sqlres['description'] ?? '');
		
		$learnout->setLevel (new EAL_Level($sqlres));
		$learnout->setDomain($sqlres['domain'] ?? '');
		
	}
	
	
	
	public static function loadAllLearningOutcomes (string $domain = NULL): array {
		
		global $wpdb;
		
		if ($domain == NULL) {
			$domain = RoleTaxonomy::getCurrentRoleDomain()["name"];
		}
			
		$queryResult = $wpdb->get_results( "
				SELECT L.*
				FROM " . self::getTableName() . " L
				JOIN {$wpdb->prefix}posts P
				ON (L.id = P.id)
				WHERE P.post_status = 'publish'
				AND L.domain = '{$domain}'
				ORDER BY L.title
				", ARRAY_A);
		
		$result = [];
		foreach ($queryResult as $sqlres) {
			$learnout = new EAL_LearnOut();
			$learnout->setId ($sqlres['id']);
			$learnout->setTitle($sqlres['title'] ?? '');
			$learnout->setDescription($sqlres['description'] ?? '');
			$learnout->setLevel (new EAL_Level($sqlres));
			$learnout->setDomain($sqlres['domain'] ?? '');
			$result[] = $learnout;
		}
		
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
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				domain varchar(50) NOT NULL,
				PRIMARY KEY  (id),
				KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
}

?>