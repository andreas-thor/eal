<?php



class DB_ItemSC extends DB_Item {
	
	
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_itemsc';
	}
	
	
	public static function saveToDB (EAL_ItemSC $item) {
		
		parent::saveToDB($item);
		
		global $wpdb;
		
		/** TODO: Sanitize all values */
		
		if ($item->getNumberOfAnswers()>0) {
			
			$values = array();
			$insert = array();
			
			for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
				array_push($values, $item->getId(), $index+1, $item->getAnswer($index), $item->getPointsChecked($index));
				array_push($insert, "(%d, %d, %s, %d)");
			}
			
			// replace answers
			$query = "REPLACE INTO " . self::getTableName() . " (item_id, id, answer, points) VALUES ";
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
	
	
	public static function loadFromDB (EAL_ItemSC &$item): EAL_ItemSC {
		
		parent::loadFromDB($item);
		
		global $wpdb;
		
		$item->clearAnswers();
		$sqlres = $wpdb->get_results( "SELECT * FROM " . self::getTableName() . " WHERE item_id = {$item->getId()} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			$item->addAnswer($a['answer'] ?? '', $a['points'] ?? 0);
		}
		
	}
	
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . " (
				item_id bigint(20) unsigned NOT NULL,
				id smallint unsigned NOT NULL,
				answer text,
				points smallint,
				KEY index_item_id (item_id),
				PRIMARY KEY  (item_id, id)
			) {$wpdb->get_charset_collate()};"
		);
		
	}
	
}

?>