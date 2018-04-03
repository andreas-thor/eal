<?php



class DB_Review {
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_review';
	}
	
	public static function saveToDB (EAL_Review $review) {
		
		
		global $wpdb;
		
		$replaceScore = array ();
		$replaceType = array ();
		foreach (EAL_Review::$dimension1 as $dim1 => $v1) {
			foreach (EAL_Review::$dimension2 as $dim2 => $v2) {
				$replaceScore["{$dim1}_{$dim2}"] = $review->getScore($dim1, $dim2);
				array_push($replaceType, "%d");
			}
		}
		
		
		$wpdb->replace(
			self::getTableName(),
			array_merge (
				array(
					'id' => $review->getId(),
					'item_id' => $review->getItemId(),
					'level_FW' => $review->getLevel()->get('FW'),
					'level_KW' => $review->getLevel()->get('KW'),
					'level_PW' => $review->getLevel()->get('PW'),
					'feedback' => $review->getFeedback(),
					'overall'  => $review->getOverall()
				),
				$replaceScore
				),
				array_merge (
					array('%d','%d','%d','%d','%d','%s','%d'),
					$replaceType
					)
				);
	}
	
	
	public static function deleteFromDB (int $review_id) {
		
		global $wpdb;
		
		$wpdb->delete( self::getTableName(), array( 'id' => $review_id ), array( '%d' ) );
		
	}
	
	
	/**
	 * Call-by-reference; $item properties are loaded into given instance
	 * @param EAL_Item $item
	 */
	public static function loadFromDB (EAL_Review &$review) {
		
			
		global $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE id = {$review->getId()}", ARRAY_A);
		
		$review->setId ($sqlres['id']);
		$review->setItemId($sqlres['item_id'] ?? -1);
		
		foreach (EAL_Review::$dimension1 as $dim1 => $v1) {
			foreach (EAL_Review::$dimension2 as $dim2 => $v2) {
				$review->setScore($dim1, $dim2, $sqlres[$dim1 . "_" . $dim2] ?? 0);
			}
		}
		
		$review->setLevel (new EAL_Level($sqlres));
		$review->setFeedback ($sqlres['feedback']);
		$review->setOverall ($sqlres['overall']);
		
	}
	
	
	
	public static function createTables () {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		$sqlScore = "";
		foreach (EAL_Review::$dimension1 as $k1 => $v1) {
			foreach (EAL_Review::$dimension2 as $k2 => $v2) {
				$sqlScore .= "{$k1}_{$k2} tinyint unsigned, \n";
			}
		}
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . " (
				id bigint(20) unsigned NOT NULL,
				item_id bigint(20) unsigned NOT NULL, {$sqlScore}
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				feedback mediumtext,
				overall tinyint unsigned,
				KEY index_item_id (item_id),
				PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
}

?>