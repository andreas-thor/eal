<?php



class DB_ItemMC extends DB_Item {
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_itemmc';
	}
	
	public static function saveToDB (EAL_ItemMC $item) {
		
		parent::saveToDB($item);
		
		global $wpdb;
		
		/** TODO: Sanitize all values */
		
		if ($item->getNumberOfAnswers()>0) {
			
			$values = array();
			$insert = array();
			for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
				array_push($values, $item->getId(), $index+1, $item->getAnswer($index), $item->getPointsPos($index), $item->getPointsNeg($index));
				array_push($insert, "(%d, %d, %s, %d, %d)");
			}
			
			
			// replace or insert answers
			$query = "REPLACE INTO " . self::getTableName() . " (item_id, id, answer, positive, negative) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
			
		}
		
		// delete remaining answers
		$wpdb->query( $wpdb->prepare("DELETE FROM " . self::getTableName() . " WHERE item_id=%d AND id>%d", array ($item->getId(), $item->getNumberOfAnswers())));
		
	}
	
	
	public static function deleteFromDB (int $item_id) {
		
		parent::deleteFromDB($item_id);
		
		global $wpdb;
		$wpdb->delete( self::getTableName(), array( 'item_id' => $item_id ), array( '%d' ) );
		
	}
	
	
	public static function loadFromDB (EAL_ItemMC &$item): EAL_ItemMC {
		
		parent::loadFromDB($item);
		global $wpdb;
		
		$item->clearAnswers();
		$sqlres = $wpdb->get_results( "SELECT * FROM " . self::getTableName() . " WHERE item_id = {$item->getId()} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			$item->addAnswer($a['answer'] ?? '', $a['positive'] ?? 1, $a['negative'] ?? 0);
		}
		
//		FIXME: Max/Min Numbers		
// 		if (!isset($this->minnumber)) $this->minnumber = 0;
// 		if (!isset($this->maxnumber)) $this->maxnumber = $this->getNumberOfAnswers();
		
	}
	
	
	public static function createTables () {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . " (
				item_id bigint(20) unsigned NOT NULL,
				id smallint unsigned NOT NULL,
				answer text,
				positive smallint,
				negative smallint,
				KEY index_item_id (item_id),
				PRIMARY KEY  (item_id, id)
			) {$wpdb->get_charset_collate()};"
		);
		
		
		
	}
	
	
}

?>