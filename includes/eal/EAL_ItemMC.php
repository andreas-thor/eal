<?php

require_once ("EAL_Item.php");




class EAL_ItemMC extends EAL_Item {
	
	
	
	/**
	 * 
	 * @var array
	 */
	private $answers = array();

	
	function __construct() {

		parent::__construct();
		
		$this->clearAnswers();
		$this->addAnswer('', 1, 0);
		$this->addAnswer('', 1, 0);
		$this->addAnswer('', 0, 1);
		$this->addAnswer('', 0, 1);
		
		$this->minnumber=0;
		$this->maxnumber=$this->getNumberOfAnswers();
	}
	
	
	
	public function clearAnswers() {
		$this->answers = array();
	}
	
	public function addAnswer (string $text, int $pos, int $neg) {
		array_push ($this->answers, array ('answer' => $text, 'positive' => $pos, 'negative' => $neg));
	}
	
	public function getNumberOfAnswers (): int {
		return count($this->answers);
	}
	
	public function getAnswer (int $index): string {
		return $this->answers[$index]['answer'];
	}
	
	public function getPointsPos (int $index): int {
		return $this->answers[$index]['positive'];
	}
	
	public function getPointsNeg (int $index): int {
		return $this->answers[$index]['negative'];
	}
	
	
	public function getHTMLPrinter (): HTML_Item {
		return new HTML_ItemMC($this);
	}
	
	
	public function getPoints(): int {
		
		$result = 0;
		for ($index=0; $index<$this->getNumberOfAnswers(); $index++) {
			$result += max ($this->getPointsPos($index), $this->getPointsNeg($index));
		}
		return $result;
		
	}
	
	
	

	
	public static function save (int $post_id, WP_Post $post) {
		
		global $item;
		if ($item === NULL) {
			$item = new EAL_ItemMC();	// load item from $_POST data
		}
		
		$revision = wp_is_post_revision ($post_id);
		$type = ($revision === FALSE) ? $post->post_type : get_post_type($revision);
		if ($type != $item->getType()) return;
		
		$item->setId($post_id);		// set the correct id
		$item->saveToDB();
	}
	
	
/*	
 
	 * Create new item from _POST
	 * @param string $prefix
	protected function loadFromPOSTRequest (string $prefix="") {
	
		parent::loadFromPOSTRequest($prefix);
		
		$this->clearAnswers();
		if (isset($_POST[$prefix.'answer'])) {
			foreach ($_POST[$prefix.'answer'] as $k => $v) {
				$this->addAnswer(html_entity_decode (stripslashes($v)), $_POST[$prefix.'positive'][$k], $_POST[$prefix.'negative'][$k]);
			}
		}
		
		$this->minnumber = $_POST[$prefix.'item_minnumber'] ?? 0;
		$this->maxnumber = $_POST[$prefix.'item_maxnumber'] ?? $this->getNumberOfAnswers();
		
	} 
 
	protected function loadFromDB (int $item_id) {
	
		parent::loadFromDB($item_id);
		global $wpdb;
		
		$this->clearAnswers();
		$sqlres = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id = {$item_id} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			$this->addAnswer($a['answer'], $a['positive'], $a['negative']);
		}
		
		if (!isset($this->minnumber)) $this->minnumber = 0;
		if (!isset($this->maxnumber)) $this->maxnumber = $this->getNumberOfAnswers();
	}
	
	
	
	public function saveToDB() {
	
		parent::saveToDB();
		
		global $wpdb;
		
		if ($this->getNumberOfAnswers()>0) {
			
			$values = array();
			$insert = array();
			for ($index=0; $index<$this->getNumberOfAnswers(); $index++) {
				array_push($values, $this->getId(), $index+1, $this->getAnswer($index), $this->getPointsPos($index), $this->getPointsNeg($index));
				array_push($insert, "(%d, %d, %s, %d, %d)");
			}
			
				
			// replace answers
			$query = "REPLACE INTO {$wpdb->prefix}eal_{$this->getType()} (item_id, id, answer, positive, negative) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
			
		}

		// delete remaining answers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id=%d AND id>%d", array ($this->getId(), $this->getNumberOfAnswers())));
		
	}
	
	
	public static function delete ($post_id) {
		
		parent::delete($post_id);
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemmc', array( 'item_id' => $post_id ), array( '%d' ) );
	}
	
*/	
	
	
	
}

?>