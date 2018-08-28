<?php



class DB_Item {
	
	
	public static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_item';
	}
	
	public static function saveToDB (EAL_Item $item) {
		
		global $wpdb;
		
		$wpdb->replace(
			self::getTableName(),
			array(
				'id' => $item->getId(),
				'title' => $item->getTitle(),
				'description' => $item->getDescription(),
				'question' => $item->getQuestion(),
				'level_FW' => $item->getLevel()->get('FW'),
				'level_KW' => $item->getLevel()->get('KW'),
				'level_PW' => $item->getLevel()->get('PW'),
				'points'   => $item->getPoints(),
				'difficulty' => $item->getDifficulty(),
				'learnout_id' => $item->getLearnOutId(),
				'type' => $item->getType(),
				'domain' => $item->getDomain(),
				'note' => $item->getNote(),
				'flag' => $item->getFlag(),
				'minnumber' => $item->getMinNumber(),
				'maxnumber' => $item->getMaxNumber()
			),
			array('%d','%s','%s','%s','%d','%d','%d','%d','%f','%d','%s','%s','%s','%d','%d','%d')
			);
	}
	
	
	public static function deleteFromDB (int $item_id) {
		
		global $wpdb;
		
		$wpdb->delete( self::getTableName(), array( 'id' => $item_id ), array( '%d' ) );
		$wpdb->delete( "{$wpdb->prefix}eal_review", array( 'item_id' => $item_id ), array( '%d' ) );
	}
	
	
	
	public static function loadFromDB (int $item_id, string $item_type): EAL_Item {
		
		switch ($item_type) {
			case EAL_ItemSC::getType(): return DB_ItemSC::loadFromDB($item_id);
			case EAL_ItemMC::getType(): return DB_ItemMC::loadFromDB($item_id);
			case EAL_ItemFT::getType(): return DB_ItemFT::loadFromDB($item_id);
		}
		
		throw new Exception('Could not load item. Unknown item type ' . $item_type);
	}
		
	
	/**
	 */
	public static function loadItemData (int $item_id): array {
		
		$object = [];
		
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE id = {$item_id}", ARRAY_A);

		// could not find item
		if (empty ($sqlres)) {
			return $object;
		}
		
		// consumed by EAL_Item
		$object['post_title'] = $sqlres['title'] ?? '';
		$object['item_description'] = $sqlres['description'] ?? '';
		$object['item_question'] = $sqlres['question'] ?? '';
		$object['learnout_id'] = $sqlres['learnout_id'] ?? -1;
		$object['item_note'] = $sqlres['note'] ?? '';
		$object['item_flag'] = $sqlres['flag'] ?? 0;
		
		// consumed by EAL_Object
		$object['item_level_FW'] = $sqlres['level_FW'] ?? 0;
		$object['item_level_PW'] = $sqlres['level_PW'] ?? 0;
		$object['item_level_KW'] = $sqlres['level_KW'] ?? 0;
		$object['domain'] = $sqlres['domain'] ?? 0;
		
		// FIXME: when is this used
		$object['difficulty'] = $sqlres['difficulty'] ?? 0;
		$object['no_of_testresults'] = $sqlres['no_of_testresults'] ?? 0;
		
		return $object;
	}
	
	
	public static function loadAllItemIdsForLearnOut (int $learnout_id): array {
		
		global $wpdb;
		
		$sql ="
			SELECT DISTINCT P.id
			FROM " . self::getTableName() . " I 
			JOIN {$wpdb->prefix}posts P ON (P.ID = I.ID)
			WHERE P.post_parent = 0
			AND P.post_status IN ('publish', 'pending', 'draft')
			AND I.learnout_id = {$learnout_id}";
		
		return $wpdb->get_col ($sql);
	}
	
	
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		/**
		 * minnumber/maxnumber: range of correct answers (relevant for MC only)
		 */
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . " (
			id bigint(20) unsigned NOT NULL,
			title text,
			description mediumtext,
			question mediumtext,
			level_FW tinyint unsigned,
			level_KW tinyint unsigned,
			level_PW tinyint unsigned,
			points smallint,
			difficulty decimal(10,5),
			no_of_testresults bigint(20) unsigned,
			learnout_id bigint(20) unsigned,
			type varchar(20) NOT NULL,
			domain varchar(50) NOT NULL,
			note text,
			flag tinyint,
			minnumber smallint,
			maxnumber smallint,
			PRIMARY KEY  (id),
			KEY index_type (type),
			KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);
		
		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_result (
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