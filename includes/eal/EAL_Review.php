<?php

require_once 'EAL_Object.php';

class EAL_Review extends EAL_Object {

	public $item_id;
	public $item;
	public $score;
	public $level;
	public $feedback;
	public $overall;
	
	public static $dimension1 = array ('description' => "Fall- oder Problemvignette", 'question' => 'Aufgabenstellung', 'answers' => 'Antwortoptionen');
	public static $dimension2 = array ('correctness' => "Fachl. Richtigkeit", 'relevance' => "Relevanz bzgl. LO", 'wording' => "Formulierung");
	
	
	
	
	function __construct(int $review_id = -1) {
	
		parent::__construct();
		$this->setId (-1);
		$this->item_id = -1;
		$this->item = null;
			
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = 0;
			}
		}
		
		$this->level = ["FW" => null, "KW" => null, "PW" => null];
		$this->feedback = '';
		$this->overall = 0;
		
		if ($review_id != -1) {
			$this->loadFromDB($review_id);
		} else {
			if ($_POST["post_type"] == $this->getType()) {
				$this->loadFromPOSTRequest();
			} else {
				global $post;
					
				if ($post->post_type != $this->getType()) return;
				if (get_post_status($post->ID)=='auto-draft') {
					$this->setId ($post->ID);
					$this->item_id = isset ($_POST['item_id']) ? $_POST['item_id'] : $_GET['item_id'];
				} else {
					$this->loadFromDB($post->ID);
				}
		
			}
		}
	}
	
	
	/**
	 * Initialize learning outcome from _POST Request data
	 */
	protected function loadFromPOSTRequest () {
	
		$this->setId ($_POST["post_ID"]);
		$this->item_id = isset ($_GET['item_id']) ? $_GET['item_id'] : (isset ($_POST['item_id']) ? $_POST['item_id'] : null);
		$this->item = null;	
		
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = isset ($_POST["review_{$k1}_{$k2}"]) ? $_POST["review_{$k1}_{$k2}"] : null;
			}
		}
		
		$this->level["FW"] = isset ($_POST['review_level_FW']) ? $_POST['review_level_FW'] : null;
		$this->level["KW"] = isset ($_POST['review_level_KW']) ? $_POST['review_level_KW'] : null;
		$this->level["PW"] = isset ($_POST['review_level_PW']) ? $_POST['review_level_PW'] : null;
		$this->feedback = isset ($_POST['review_feedback']) ? html_entity_decode (stripslashes($_POST['review_feedback'])) : null;
		$this->overall  = isset ($_POST['review_overall'])  ? $_POST['review_overall']  : null;
	}
	
	

	
	
	
	protected function loadFromDB (int $review_id) { 
		
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_review WHERE id = {$review_id}", ARRAY_A);
		
		$this->setId ($sqlres['id']);
		$this->item_id = $sqlres['item_id'];
		$this->item = null; // lazy loading
			
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = $sqlres[$k1 . "_" . $k2];
			}
		}
		
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
		$this->feedback = $sqlres['feedback'];
		$this->overall = $sqlres['overall'];
	}
	
	
	protected function saveToDB () {
	
		global $wpdb;
		$replaceScore = array ();
		$replaceType = array ();
		foreach (self::$dimension1 as $k1 => $v1) {
			foreach (self::$dimension2 as $k2 => $v2) {
				$replaceScore["{$k1}_{$k2}"] = $this->score[$k1][$k2];
				array_push($replaceType, "%d");
			}
		}
		
		
		$wpdb->replace(
			"{$wpdb->prefix}eal_review",
			array_merge (
				array(
					'id' => $this->getId(),
					'item_id' => $this->item_id,
					'level_FW' => $this->level["FW"],
					'level_KW' => $this->level["KW"],
					'level_PW' => $this->level["PW"],
					'feedback' => $this->feedback,
					'overall'  => $this->overall
				),
				$replaceScore
			),
			array_merge (
				array('%d','%d','%d','%d','%d','%s','%d'),
				$replaceType
			)
		);
		
	}

	
	public static function save ($post_id, $post) {
	
		$review = new EAL_Review();
		if ($_POST["post_type"] != $review->getType()) return;
		$review->saveToDB();
	}
	
	
	
	public static function delete ($post_id) {
	
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_review', array( 'id' => $post_id ), array( '%d' ) );
	}
	
	
	public function getItem () {
	
		if (is_null($this->item_id)) return null;
	
		if (is_null($this->item)) {
			$post = get_post($this->item_id);
			if ($post == null) return null;
			$this->item = EAL_Item::load($post->post_type, $this->item_id);
		}
		return $this->item;
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
			"CREATE TABLE {$wpdb->prefix}eal_review (
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