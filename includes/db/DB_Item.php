<?php



class DB_Item {
	
	
	public static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_item';
	}
	
	public static function saveToDB (EAL_Item $item, bool $update) {
		
		global $wpdb;

		if ($update) {
			// update all values except static values (Id, Type, Domain) and derived values (Difficulty, #TestResults, #Reviews)
			$wpdb->update(
				self::getTableName(),
				array(
					'title' => $item->getTitle(),
					'description' => $item->getDescription(),
					'question' => $item->getQuestion(),
					'level_FW' => $item->getLevel()->get('FW'),
					'level_KW' => $item->getLevel()->get('KW'),
					'level_PW' => $item->getLevel()->get('PW'),
					'points'   => $item->getPoints(),
					'learnout_id' => $item->getLearnOutId(),
					'note' => $item->getNote(),
					'flag' => $item->getFlag(),
					'minnumber' => $item->getMinNumber(),
					'maxnumber' => $item->getMaxNumber()
				),
				array('id' => $item->getId()),
				array('%s','%s','%s','%d','%d','%d','%d','%d','%s','%d','%d','%d'),
				array('%d')
				);
		} else {
			// new item --> INSERT			
			$wpdb->insert(
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
					'no_of_testresults' => $item->getNoOfTestResults(),
					'learnout_id' => $item->getLearnOutId(),
					'no_of_reviews' => $item->getNoOfReviews(), 
					'type' => $item->getType(),
					'domain' => $item->getDomain(),
					'note' => $item->getNote(),
					'flag' => $item->getFlag(),
					'minnumber' => $item->getMinNumber(),
					'maxnumber' => $item->getMaxNumber()
				),
				array('%d','%s','%s','%s','%d','%d','%d','%d','%f', '%d','%d', '%d', '%s','%s','%s','%d','%d','%d')
				);
		}
		
		if ($item->getLearnOutId()>0) {
			DB_Learnout::updateNumberOfItems($item->getLearnOutId());
		}
	}
	
	
	public static function deleteFromDB (int $item_id) {
		
		global $wpdb;
		
		
		$learnout_id = $wpdb->get_var( 'SELECT learnout_id FROM ' . self::getTableName() . ' WHERE id = ' . $item_id);
		
		$wpdb->delete( DB_Item::getTableName(), array( 'id' => $item_id ), array( '%d' ) );
		DB_Review::deleteAllItemReviewsFromDB($item_id);
		
		if ($learnout_id != NULL) {
			DB_Learnout::updateNumberOfItems($learnout_id);
		}
	}
	
	
	public static function trashFromDB (int $item_id) {

		global $wpdb;
		$learnout_id = $wpdb->get_var( 'SELECT learnout_id FROM ' . self::getTableName() . ' WHERE id = ' . $item_id);
		if ($learnout_id != NULL) {
			DB_Learnout::updateNumberOfItems($learnout_id);
		}
	}
	
	public static function untrashFromDB (int $item_id) {
		self::trashFromDB($item_id);
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
	 * @param int $item_id if ==-1 --> #reviews will be updated for all items 
	 */
	public static function updateNumberOfReviews (int $item_id) {
		
		global $wpdb;
		
		if ($item_id < 0) {		// update for all items

			$sql = sprintf('
				UPDATE %1$s I
				JOIN (
					SELECT R.item_id, COUNT(*) AS no_of_reviews
					FROM %2$s AS R
					JOIN %3$s AS RP ON (R.ID = RP.ID)
					WHERE RP.post_parent = 0
					AND RP.post_status = \'publish\' 
					GROUP BY R.item_id
				) AS T
				ON (I.id = T.item_id)
				SET I.no_of_reviews = T.no_of_reviews',
				DB_Item::getTableName(), DB_Review::getTableName(), $wpdb->posts);
			
		} else {
				
			$sql = sprintf('
				UPDATE %1$s I
				SET no_of_reviews = (
					SELECT COUNT(*)
					FROM %2$s AS R
					JOIN %3$s AS RP ON (R.ID = RP.ID)
					WHERE RP.post_parent = 0
					AND RP.post_status = \'publish\' 
					AND R.item_id = %4$d
				)
				WHERE I.id = %4$d',
				DB_Item::getTableName(), DB_Review::getTableName(), $wpdb->posts, $item_id);
		}
		
		$wpdb->query($sql);
	}
	
	
	public static function updateDifficultyAndNumberOfTestResults (array $itemIds) {
		
		global $wpdb;
		
		if (count($itemIds) == 0) {

			// reset all values to default
			$sql = 'UPDATE ' . DB_Item::getTableName() . ' SET difficulty = NULL, no_of_testresults = 0';
			$wpdb->query($sql);
			
			// no item filter
			$itemFilter = '';
		} else {
			$itemFilter = 'AND t.item_id IN (' . implode(',', $itemids) . ') ';
		}
		
		$sql = sprintf ('
			UPDATE %1$s AS U
			INNER JOIN (
				SELECT t.item_id, (avg(t.points) / i.points) as difficulty, count(distinct t.test_id) as no_of_testresults
				FROM %2$s T
				JOIN %1$s I on (T.item_id=I.id)
				JOIN %3$s TP ON (T.test_id = TP.ID)
				WHERE TP.post_parent = 0
				AND TP.post_status = \'publish\' 
				%4$s
				group by t.item_id
			) AS J ON (U.id = J.item_id)
			SET 
				U.difficulty = J.difficulty, 
				U.no_of_testresults = J.no_of_testresults',
			
			DB_Item::getTableName(), 
			DB_TestResult::getTableName() . '_useritem',  
			$wpdb->posts, 
			$itemFilter);
		
		$wpdb->query($sql);
		
	}
	
	
	public static function updateLearningOutcomeAfterRemoval (int $learnout_id) {
		
		global $wpdb;
		$wpdb->query('UPDATE ' . self::getTableName() . ' SET learnout_id = NULL WHERE learnout_id = ' . $learnout_id);
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
		$object['difficulty'] = $sqlres['difficulty'] ?? -1;
		$object['no_of_testresults'] = $sqlres['no_of_testresults'] ?? 0;
		$object['no_of_reviews'] = $sqlres['no_of_reviews'] ?? 0;
		
		
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
			no_of_reviews bigint(20) unsigned,
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