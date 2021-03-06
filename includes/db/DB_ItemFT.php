<?php


require_once 'DB_Item.php';


class DB_ItemFT {
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_itemft';
	}
	 
	
	public static function saveToDB (EAL_ItemFT $item, bool $update) {
		
		DB_Item::saveToDB($item, $update);
		
		global $wpdb;
		
		if ($update) {
			$wpdb->update(self::getTableName(), ['points' => $item->getPoints()], ['item_id' => $item->getId()], ['%d'], ['%d']);
		} else {
			$wpdb->insert(self::getTableName(), ['item_id' => $item->getId(), 'points' => $item->getPoints()], ['%d','%d']);
		}
	}
	
	
	public static function deleteFromDB (int $item_id) {
		
		DB_Item::deleteFromDB($item_id);
		
		global $wpdb;
		$wpdb->delete(self::getTableName(), ['item_id' => $item->getId()], ['%d']);
	}
	
	
	public static function loadFromDB (int $item_id): EAL_ItemFT {
		
		$object = DB_Item::loadItemData ($item_id);
		
		if (empty ($object)) {
			throw new Exception ('Could not find itemFT with id=' . $item_id);
		}
		
		global $wpdb;
		
		$sqlres = $wpdb->get_row($wpdb->prepare(
			'SELECT * FROM ' . self::getTableName() . ' WHERE item_id = %d', $item_id), ARRAY_A);
		
		$object['item_points'] = $sqlres['points'];
		
		return EAL_ItemFT::createFromArray($item_id, $object);
		
	}
	
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . " (
				item_id bigint(20) unsigned NOT NULL,
				points smallint,
				KEY index_item_id (item_id),
				PRIMARY KEY  (item_id)
			) {$wpdb->get_charset_collate()};"
		);
		
	}
	
}

?>