<?php

require_once ("EAL_Item.php");

class EAL_ItemSC extends EAL_Item {
	
	public $answers;
	 
	
	function __construct(int $item_id = -1, string $prefix="") {
		
		$this->clearAnswers();
		$this->addAnswer('', 1);
		$this->addAnswer('', 0);
		$this->addAnswer('', 0);
		$this->addAnswer('', 0);
		
		parent::__construct($item_id, $prefix);
	}
	
	public function clearAnswers() {
		$this->answers = array();
	}
	
	public function addAnswer (string $text, int $points) {
		array_push ($this->answers, array ('answer' => $text, 'points' => $points));
	}
	
	public function getNumberOfAnswers (): int {
		return count($this->answers);
	}
	
	public function getAnswer (int $index): string {
		return $this->answers[$index]['answer'];
	}
	
	public function getPointsChecked (int $index): int {
		return $this->answers[$index]['points'];
	}
	
	
	
	
	public function getHTMLPrinter (): HTML_Item {
		return new HTML_ItemSC($this);
	}
	
	
	
	protected function loadFromPOSTRequest (string $prefix="") {
		
		parent::loadFromPOSTRequest($prefix);
		
		$this->clearAnswers();
		if (isset($_POST[$prefix.'answer'])) {
			foreach ($_POST[$prefix.'answer'] as $k => $v) {
				$this->addAnswer(html_entity_decode (stripslashes($v)), $_POST[$prefix.'points'][$k]);
			}
		}
	}

	
	protected function loadFromDB (int $item_id) {
		
		parent::loadFromDB($item_id);
	
		global $wpdb;
		
		$this->clearAnswers();
		$sqlres = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id = {$item_id} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			$this->addAnswer($a['answer'], $a['points']);
		}
		
	}
	
	
	
	public function saveToDB() {
		
		parent::saveToDB();
		
		global $wpdb;
		
		/** TODO: Sanitize all values */
		
		if ($this->getNumberOfAnswers()>0) {
		
			$values = array();
			$insert = array();
			
			for ($index=0; $index<$this->getNumberOfAnswers(); $index++) {
				array_push($values, $this->getId(), $index+1, $this->getAnswer($index), $this->getPointsChecked($index));
				array_push($insert, "(%d, %d, %s, %d)");
			}
		
			// replace answers
			$query = "REPLACE INTO {$wpdb->prefix}eal_{$this->getType()} (item_id, id, answer, points) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
		}
		
		// delete remaining answers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id=%d AND id>%d", array ($this->getId(), $this->getNumberOfAnswers())));
		
	}
	

	/** 
	 * $item to store might already be loaed (e.g., during import); otherwise loaded from $_POST data
	 * save is called twice per update
	 * 1) for the revision --> $revision will contain the id of the parent post
	 * 2) for the current version --> $revision will be FALSE
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public static function save (int $post_id, WP_Post $post) {
		
		global $item;
		if ($item === NULL) {
			$item = new EAL_ItemSC();	// load item from $_POST data
		}
		
		$revision = wp_is_post_revision ($post_id);
		$type = ($revision === FALSE) ? $post->post_type : get_post_type($revision);
		if ($type != $item->getType()) return;
		
		$item->setId($post_id);		// set the correct id
		$item->saveToDB();
	}
	
	

	
	public static function delete ($post_id) {
	
		parent::delete($post_id);
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemsc', array( 'item_id' => $post_id ), array( '%d' ) );
	}
	
	
	public function getPoints(): int {
	
		$result = 0;
		for ($index=0; $index<$this->getNumberOfAnswers(); $index++) {
			$result = max ($result, $this->getPointsChecked($index));
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