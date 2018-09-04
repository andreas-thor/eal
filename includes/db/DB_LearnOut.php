<?php



class DB_Learnout {
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_learnout';
	}
	
	public static function saveToDB (EAL_LearnOut $learnout) {
		
		
		global $wpdb;
		
		$dbId = $wpdb->get_var('SELECT id FROM ' . self::getTableName() . ' WHERE id = ' . $learnout->getId());
		
		if ($dbId == NULL) {
			$wpdb->insert(
				self::getTableName(),
				[	'id' => $learnout->getId(),
					'title' => $learnout->getTitle(),
					'description' => $learnout->getDescription(),
					'level_FW' => $learnout->getLevel()->get('FW'),
					'level_KW' => $learnout->getLevel()->get('KW'),
					'level_PW' => $learnout->getLevel()->get('PW'),
					'domain' => $learnout->getDomain(),
					'no_of_items' => $learnout->getNoOfItems()
				],
				['%d','%s','%s','%d','%d','%d','%s','%d']
			);
		} else {
			$wpdb->update(
				self::getTableName(),
				[	'title' => $learnout->getTitle(),
					'description' => $learnout->getDescription(),
					'level_FW' => $learnout->getLevel()->get('FW'),
					'level_KW' => $learnout->getLevel()->get('KW'),
					'level_PW' => $learnout->getLevel()->get('PW')
					
				],
				[	'id' => $learnout->getId() ],
				['%s','%s','%d','%d','%d'],
				['%d']
			);
		}
	}
	
	
	public static function deleteFromDB (int $learnout_id) {
		
		global $wpdb;
		
		// FIXME: Items mit dem Learning Outcome --> set Id auf -1 oder NULL
		$wpdb->delete( self::getTableName(), ['id' => $learnout_id], ['%d'] );
		
		DB_Item::updateLearningOutcomeAfterRemoval($learnout_id);
		
	}
	
	
	/**
	 */
	public static function loadFromDB (int $learnout_id): EAL_LearnOut {
		
			
		global $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE id = {$learnout_id}", ARRAY_A);
		
		$object = [];
		$object['post_title'] = $sqlres['title'] ?? '';
		$object['learnout_description'] = $sqlres['description'] ?? '';
		$object['learnout_level_FW'] = $sqlres['level_FW'] ?? 0;
		$object['learnout_level_KW'] = $sqlres['level_KW'] ?? 0;
		$object['learnout_level_PW'] = $sqlres['level_PW'] ?? 0;
		$object['domain'] = $sqlres['domain'] ?? '';
		$object['no_of_items'] = $sqlres['no_of_items'] ?? 0;
		
		return EAL_LearnOut::createFromArray($learnout_id, $object);
	}
	
	
	
	public static function loadAllLearningOutcomes (string $domain = NULL): array {
		
		global $wpdb;
		
		if ($domain == NULL) {
			$domain = RoleTaxonomy::getCurrentDomain();
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
			
			$object = [];
			$object['post_title'] = $sqlres['title'] ?? '';
			$object['learnout_description'] = $sqlres['description'] ?? '';
			$object['learnout_level_FW'] = $sqlres['level_FW'] ?? 0;
			$object['learnout_level_KW'] = $sqlres['level_KW'] ?? 0;
			$object['learnout_level_PW'] = $sqlres['level_PW'] ?? 0;
			$object['domain'] = $sqlres['domain'] ?? '';
			$object['no_of_items'] = $sqlres['no_of_items'] ?? 0;
			$result[] =  EAL_LearnOut::createFromArray(intval ($sqlres['id']), $object);
		}
		
		return $result;
		
	}
	
	
	public static function updateNumberOfItems (int $learnout_id) {
		
		global $wpdb;
		
		if ($learnout_id < 0) {
			
			$sql = '
				UPDATE ' . self::getTableName() . ' L
				JOIN (
					SELECT I.learnout_id, COUNT(*) AS no_of_items
					FROM ' . DB_Item::getTableName() . ' AS I
					JOIN ' . $wpdb->posts . ' IP ON (I.ID = IP.ID)
					WHERE IP.post_parent = 0
					AND IP.post_status IN (\'publish\', \'pending\', \'draft\')
					GROUP BY I.learnout_id
				) AS T
				ON (L.id = T.learnout_id)
				SET L.no_of_items = T.no_of_items';
			
		} else {
		
			$sql = '
				UPDATE ' . self::getTableName() . '  
				SET no_of_items = (
					SELECT COUNT(*) 
					FROM ' . DB_Item::getTableName() . ' I 
					JOIN ' . $wpdb->posts . ' IP 
		 			ON (I.ID = IP.ID)
					WHERE IP.post_parent = 0
					AND IP.post_status IN (\'publish\', \'pending\', \'draft\')
					AND I.learnout_id = ' . $learnout_id . '
				) WHERE id = ' . $learnout_id; 
		}
		
		$wpdb->query($sql);
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
				no_of_items smallint, 
				PRIMARY KEY  (id),
				KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
}

?>