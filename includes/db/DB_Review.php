<?php



class DB_Review {
	
	
	public static function getTableName (): string {
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
		
		
		// update number of reviews for item
		DB_Item::updateNumberOfReviews($review->getItemId());
		
	}
	
	
	public static function deleteFromDB (int $review_id) {
		
		global $wpdb;
		
		// update number of reviews for item
		$item_id = $wpdb->get_var( 'SELECT item_id FROM ' . self::getTableName() . ' WHERE id = ' . $review_id);
		
		// delete review
		$wpdb->delete( self::getTableName(), array( 'id' => $review_id ), array( '%d' ) );
		
		if ($item_id != NULL) {
			DB_Item::updateNumberOfReviews ($item_id);
		}
		
		
	}
	
	
	
	public static function deleteAllItemReviewsFromDB (int $item_id) {
		global $wpdb;
		$wpdb->delete( self::getTableName(), array( 'item_id' => $item_id ), array( '%d' ) );
	}
	
	
	public static function loadFromDB (int $review_id): EAL_Review {
		
			
		global $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM " . self::getTableName() . " WHERE id = {$review_id}", ARRAY_A);
		
		$object = [];
		$object['item_id'] = $sqlres['item_id'] ?? -1;
		
		foreach (EAL_Review::$dimension1 as $dim1 => $v1) {
			foreach (EAL_Review::$dimension2 as $dim2 => $v2) {
				$object['review_' . $dim1 . '_' . $dim2] = $sqlres[$dim1 . "_" . $dim2] ?? 0;
			}
		}
		$object['review_level_FW'] = $sqlres['level_FW'] ?? 0;
		$object['review_level_KW'] = $sqlres['level_KW'] ?? 0;
		$object['review_level_PW'] = $sqlres['level_PW'] ?? 0;
		$object['review_feedback'] = $sqlres['feedback'] ?? '';
		$object['review_overall'] = $sqlres['overall'] ?? 0;
		
		return EAL_Review::createFromArray($review_id, $object);

	}
	
	
	
	public static function loadAllReviewIdsForItemFromDB (int $item_id): array {
			
		global $wpdb;
		return $wpdb->get_col ("
			SELECT R.id
			FROM " . self::getTableName() . " R
			JOIN {$wpdb->prefix}posts RP ON (R.id = RP.id)
			WHERE RP.post_parent=0 AND R.item_id = {$item_id} AND RP.post_status IN ('publish', 'pending', 'draft')");
			
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