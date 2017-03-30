<?php

require_once ("EAL_Item.php");

class EAL_ItemSC extends EAL_Item {
	
	public $answers;
	
	
	function __construct() {
		parent::__construct();
		$this->type = "itemsc";
	}
	
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	public function init ($post_id, $post) {
		
		parent::init($post_id, $post);
		
		$this->answers = array();
		if (isset($_POST['answer'])) {
			foreach ($_POST['answer'] as $k => $v) {
				array_push ($this->answers, array ('answer' => $v, 'points' => $_POST['points'][$k]));
			}
		}
		
// 		print_r ($this->answers);
	}
	
	public function setPOST () {
	
		parent::setPOST();
	
		$_POST['answer'] = array();
		$_POST['points'] = array();
		foreach ($this->answers as $v) {
			array_push ($_POST['answer'], $v['answer']);
			array_push ($_POST['points'], $v['points']);
		}
	}
	
	
	
	/**
	 * Create new Item or load existing item from database 
	 * @param string $eal_posttype
	 */
	
	public function load () {

		global $post;
		if ($post->post_type != $this->type) return;
		
	
		if (get_post_status($post->ID)=='auto-draft') {
				
			parent::load($eal_posttype);
			$this->answers = array (
					array ('answer' => '', 'points' => 1),
					array ('answer' => '', 'points' => 0),
					array ('answer' => '', 'points' => 0),
					array ('answer' => '', 'points' => 0)
			);
				
		} else {
			$this->loadById($post->ID);
		}
	}
	
	public function loadById ($item_id) {
		
		parent::loadById($item_id);
	
		global $wpdb;
		$this->answers = array();
		$sqlres = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_{$this->type} WHERE item_id = {$item_id} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			array_push ($this->answers, array ('answer' => $a['answer'], 'points' => $a['points']));
		}
		
	}
	
	
	
	public function save2DB() {
		
		parent::save2DB();
		
		global $wpdb;
		
		/** TODO: Sanitize all values */
		
		if (count($this->answers)>0) {
		
			$values = array();
			$insert = array();
			foreach ($this->answers as $k => $a) {
				array_push($values, $this->id, $k+1, $a['answer'], $a['points']);
				array_push($insert, "(%d, %d, %s, %d)");
			}
		
			// replace answers
			$query = "REPLACE INTO {$wpdb->prefix}eal_{$this->type} (item_id, id, answer, points) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
		}
		
		// delete remaining answers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_{$this->type} WHERE item_id=%d AND id>%d", array ($this->id, count($this->answers))));
		
	}
	
	
	public static function save ($post_id, $post) {
	
		if ($_POST["post_type"]!="itemsc") return;
		$item = new EAL_ItemSC();
		$item->init($post_id, $post);
		$item->save2DB();
	}
	
	
	
	public static function delete ($post_id) {
	
		parent::delete($post_id);
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemsc', array( 'item_id' => $post_id ), array( '%d' ) );
		
	}
	
	
	public function getPoints() {
	
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
	
	public function compareAnswers (EAL_ItemSC $comp) {
	
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
			$bgcolor = ($new[$i]['points'] != $a['points']) ? "class='diff-{$class}line'" : "";
			$res .= "<tr align='left' ><td  style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>";
			$res .= "{$a['points']}</td>";
			$bgcolor = ($new[$i]['answer'] != $a['answer']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='width:99%; padding:0; padding-left:10px' align='left' {$bgcolor}>{$a['answer']}</td></tr>";
					
		}
		
		$res .= "</table></div>";
		
		return $res;	
	}
	
}

?>