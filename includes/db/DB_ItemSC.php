<?php


require_once 'DB_Item.php';


class DB_ItemSC {
	
	
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_itemsc';
	}
	
	
	public static function saveToDB (EAL_ItemSC $item) {
		
		DB_Item::saveToDB($item);
		
		global $wpdb;
		
		// delete old answers
		$wpdb->delete(self::getTableName(), ['item_id' => $item->getId()], ['%d']);
		
		if ($item->getNumberOfAnswers()>0) {
			
			// insert all answers
			$values = array();
			$values_format = array();
			
			for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
				array_push($values, $item->getId(), $index+1, $item->getAnswer($index), $item->getPointsChecked($index));
				array_push($values_format, '(%d, %d, %s, %d)');
			}
			
			$query = 'INSERT INTO ' . self::getTableName() . ' (item_id, id, answer, points) VALUES ';
			$query .= implode(', ', $values_format);
			$wpdb->query($wpdb->prepare($query, $values));
		}
		
		

	}
	
	
	public static function deleteFromDB (int $item_id) {
		
		DB_Item::deleteFromDB($item_id);
		
		global $wpdb;
		$wpdb->delete( self::getTableName(), ['item_id' => $item_id], ['%d'] );
		
	}
	
	
	public static function loadFromDB (int $item_id): EAL_ItemSC {
		
		$object = DB_Item::loadItemData ($item_id);
		
		if (empty ($object)) {
			throw new Exception ('Could not find itemSC with id=' . $item_id);
		}
		
		$object['answer'] = [];
		$object['points'] = [];
		
		global $wpdb;
		
		$sqlres = $wpdb->get_results($wpdb->prepare(
			'SELECT * FROM ' . self::getTableName() . ' WHERE item_id = %d ORDER BY id', $item_id), ARRAY_A);
		
		foreach ($sqlres as $a) {
			$object['answer'][] = $a['answer'] ?? '';
			$object['points'][] = $a['points'] ?? 0;
		}
		
		return EAL_ItemSC::createFromArray($item_id, $object);
		
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