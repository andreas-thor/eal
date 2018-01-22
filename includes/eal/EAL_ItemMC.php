<?php

require_once ("EAL_Item.php");

class EAL_ItemMC extends EAL_Item {
	
	public $answers = array();

	
	function __construct(int $item_id = -1, string $prefix="") {
		$this->answers = array (
				array ('answer' => '', 'positive' => 1, 'negative' => 0),
				array ('answer' => '', 'positive' => 1, 'negative' => 0),
				array ('answer' => '', 'positive' => 0, 'negative' => 1),
				array ('answer' => '', 'positive' => 0, 'negative' => 1)
		);
		$this->minnumber=0;
		$this->maxnumber=count($this->answers);
		parent::__construct($item_id, $prefix);
	}
	
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	protected function loadFromPOSTRequest (string $prefix="") {
	
		parent::loadFromPOSTRequest($prefix);
		
		$this->answers = array();
		if (isset($_POST[$prefix.'answer'])) {
			foreach ($_POST[$prefix.'answer'] as $k => $v) {
				array_push ($this->answers, array ('answer' => html_entity_decode (stripslashes($v)), 'positive' => $_POST[$prefix.'positive'][$k], 'negative' => $_POST[$prefix.'negative'][$k]));
			}
		}
		
		$this->minnumber = $_POST[$prefix.'item_minnumber'] ?? 0;
		$this->maxnumber = $_POST[$prefix.'item_maxnumber'] ?? count($this->answers);
		
	}
	
/*	
	public function setPOST () {
		
		parent::setPOST();
		
		
		$_POST['answer'] = array();
		$_POST['positive'] = array();
		$_POST['negative'] = array();
		foreach ($this->answers as $v) {
			array_push ($_POST['answer'], $v['answer']);
			array_push ($_POST['positive'], $v['positive']);
			array_push ($_POST['negative'], $v['negative']);
		}
	}
*/
	
	/**
	 * Create new Item or load existing item from database
	 * @param string $eal_posttype
	 */
	
// 	public function load () {
		
// 		global $post;
// 		if ($post->post_type != $this->type) return;
		
// 		if (get_post_status($post->ID)=='auto-draft') {
		
// 			parent::load();
// 			$this->answers = array (
// 					array ('answer' => '', 'positive' => 1, 'negative' => 0),
// 					array ('answer' => '', 'positive' => 1, 'negative' => 0),
// 					array ('answer' => '', 'positive' => 0, 'negative' => 1),
// 					array ('answer' => '', 'positive' => 0, 'negative' => 1)
// 			);
			
// 		} else {
// 			$this->loadById($post->ID);
// 		}
// 	}
	
	
	protected function loadFromDB (int $item_id) {
	
		parent::loadFromDB($item_id);
		
		global $wpdb;
		$this->answers = array();
		$sqlres = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id = {$item_id} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			array_push ($this->answers, array ('answer' => $a['answer'], 'positive' => $a['positive'], 'negative' => $a['negative']));
		}
		
		if (!isset($this->minnumber)) $this->minnumber = 0;
		if (!isset($this->maxnumber)) $this->maxnumber = count($this->answers);
		
		
	}
	
	
	public static function save ($post_id, $post) {
	
		$item = new EAL_ItemMC();
		if ($_POST["post_type"] != $item->getType()) return;
		$item->saveToDB();
	}
	
	
	public function saveToDB() {
	
		parent::saveToDB();
		
		global $wpdb;
		
		/** TODO: Sanitize all values */
		
		if (count($this->answers)>0) {
			
			$values = array();
			$insert = array();
			foreach ($this->answers as $k => $a) {
				array_push($values, $this->getId(), $k+1, $a['answer'], $a['positive'], $a['negative']);
				array_push($insert, "(%d, %d, %s, %d, %d)");
			}
			
				
			// replace answers
			$query = "REPLACE INTO {$wpdb->prefix}eal_{$this->getType()} (item_id, id, answer, positive, negative) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
			
		}

		// delete remaining answers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_{$this->getType()} WHERE item_id=%d AND id>%d", array ($this->getId(), count($this->answers))));
		
	}
	
	
	public static function delete ($post_id) {
		
		parent::delete($post_id);
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemmc', array( 'item_id' => $post_id ), array( '%d' ) );
	}
	
	
	
	protected function getPoints() { 
		
		$result = 0;
		foreach ($this->answers as $a) {
			$result += max ($a['positive'], $a['negative']);
		}
		return $result;
	
	}
	
	
	
		


	
	
	
	
	
	
	public static function createTables () {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		dbDelta (
				"CREATE TABLE {$wpdb->prefix}eal_itemmc (
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
	
	
	public function compareAnswers (EAL_ItemMC $comp) {
	
		$diff  = "<table class='diff'>";
		$diff .= "<colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup>";
		$diff .= "<tbody><tr>";
		$diff .= "<td><div>{$this->compareAnswers1($this->answers, $comp->answers, "deleted")}</div></td><td></td>";
		$diff .= "<td><div>{$this->compareAnswers1($comp->answers, $this->answers, "added")}</div></td>";
		$diff .= "</tr></tbody></table>";
	
	
		return array ("id" => 'answers', 'name' => 'Antwortoptionen', 'diff' => $diff);
	
	}
	
	private function compareAnswers1 ($old, $new, $class) {
	
		$res = "<table >";
	
		foreach ($old as $i => $a) {
			$res .= "<tr align='left' >";
			$bgcolor = ($new[$i]['positive'] != $a['positive']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['positive']}</td>";
			$bgcolor = ($new[$i]['negative'] != $a['negative']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['negative']}</td>";
			$bgcolor = ($new[$i]['answer'] != $a['answer']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='width:98%; padding:0; padding-left:10px' align='left' {$bgcolor}>{$a['answer']}</td></tr>";
				
		}
	
		$res .= "</table></div>";
	
		return $res;
	}
	
	
}

?>