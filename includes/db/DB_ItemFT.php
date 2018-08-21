<?php


require_once 'DB_Item.php';


class DB_ItemFT {
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_itemft';
	}
	 
	
	public static function saveToDB (EAL_ItemFT $item) {
		
		DB_Item::saveToDB($item);
		
		global $wpdb;

		$wpdb->replace(
			self::getTableName(),
			array(
				'item_id' => $item->getId(),
				'points'   => $item->getPoints()
			),
			array('%d','%d')
		);
		
		
	}
	
	
	public static function deleteFromDB (int $item_id) {
		
		DB_Item::deleteFromDB($item_id);
		
		global $wpdb;
		$wpdb->delete( self::getTableName(), array( 'item_id' => $item_id ), array( '%d' ) );
		
	}
	
	
	public static function loadFromDB (int $item_id): EAL_ItemFT {
		
		$object = DB_Item::loadItemData ($item_id);
		
		if (empty ($object)) {
			throw new Exception ('Could not find itemFT with id=' . $item_id);
		}
		
		global $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE item_id = {$item_id}", ARRAY_A);
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