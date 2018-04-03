<?php



class DB_Item {
	
	
	private static function getTableName (): string {
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
	
	
	/**
	 * Call-by-reference; $item properties are loaded into given instance
	 * @param EAL_Item $item
	 */
	public static function loadFromDB (EAL_Item &$item) {
		
			
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE id = {$item->getId()} AND type ='{$item->getType()}'", ARRAY_A);
		
		$item->setId ($sqlres['id']);
		$item->setTitle ($sqlres['title'] ?? '');
		$item->setDescription($sqlres['description'] ?? '');
		$item->setQuestion($sqlres['question'] ?? '');
		
		
		$item->setLevel (new EAL_Level($sqlres));
		$item->setLearnOutId($sqlres['learnout_id'] ?? -1);

//		FIXME: min/Max Number in ItemMC only		
// 		$this->minnumber = ($this->getType() == "itemmc") && (isset($sqlres['minnumber'])) ? $sqlres['minnumber'] : null;
// 		$this->maxnumber = ($this->getType() == "itemmc") && (isset($sqlres['maxnumber'])) ? $sqlres['maxnumber'] : null;
		
		$item->setDifficulty($sqlres['difficulty'] ?? 0);
		$item->setNote($sqlres['note'] ?? '');
		$item->setFlag($sqlres['flag'] ?? '');
		
		$item->setDomain($sqlres['domain'] ?? '');
		
	}
	
	
	public static function loadAllItemIdsForLearnOut (EAL_LearnOut $learnout): array {
		
		global $wpdb;
		
		$sql ="
			SELECT DISTINCT P.id
			FROM " . self::getTableName() . " 
			JOIN {$wpdb->prefix}posts P ON (P.ID = I.ID)
			WHERE P.post_parent = 0
			AND P.post_status IN ('publish', 'pending', 'draft')
			AND I.learnout_id = {$learnout->getId()}";
		
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
			difficulty decimal(10,1),
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