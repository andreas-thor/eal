<?php

require_once ("EAL_Item.php");

class EAL_ItemSC extends EAL_Item {
	
	public $answers;
	
	
	function __construct(int $item_id = -1, string $prefix="") {
		$this->answers = array (
			array ('answer' => '', 'points' => 1),
			array ('answer' => '', 'points' => 0),
			array ('answer' => '', 'points' => 0),
			array ('answer' => '', 'points' => 0));
		
		parent::__construct($item_id, $prefix);
		
		
		
	}
	
	
	
	protected function loadFromPOSTRequest (string $prefix="") {
		
		parent::loadFromPOSTRequest($prefix);
		
		$this->answers = array();
		if (isset($_POST[$prefix.'answer'])) {
			foreach ($_POST[$prefix.'answer'] as $k => $v) {
				array_push ($this->answers, array ('answer' => html_entity_decode (stripslashes($v)), 'points' => $_POST[$prefix.'points'][$k]));
			}
		}
	}

	
	protected function loadFromDB (int $item_id) {
		
		parent::loadFromDB($item_id);
	
		global $wpdb;
		$this->answers = array();
		$sqlres = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id = {$item_id} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			array_push ($this->answers, array ('answer' => $a['answer'], 'points' => $a['points']));
		}
		
	}
	
	
	
	public function saveToDB() {
		
		parent::saveToDB();
		
		global $wpdb;
		
		/** TODO: Sanitize all values */
		
		if (count($this->answers)>0) {
		
			$values = array();
			$insert = array();
			foreach ($this->answers as $k => $a) {
				array_push($values, $this->getId(), $k+1, $a['answer'], $a['points']);
				array_push($insert, "(%d, %d, %s, %d)");
			}
		
			// replace answers
			$query = "REPLACE INTO {$wpdb->prefix}eal_{$this->getType()} (item_id, id, answer, points) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
		}
		
		// delete remaining answers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id=%d AND id>%d", array ($this->getId(), count($this->answers))));
		
	}
	
	
	public static function save ($post_id, $post) {
	
		$item = new EAL_ItemSC();
		if ($_POST["post_type"] != $item->getType()) return;
		$item->saveToDB();
	}
	
	
	
	public static function delete ($post_id) {
	
		parent::delete($post_id);
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemsc', array( 'item_id' => $post_id ), array( '%d' ) );
	}
	
	
	public function getPoints(): int {
	
		$result = 0;
		foreach ($this->answers as $a) {
			$result = max ($result, $a['points']);
		}
		return $result;
	
	}
	
	
	
	
	
	public static function createTables() {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_itemsc (
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