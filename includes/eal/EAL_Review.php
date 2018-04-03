<?php

require_once 'EAL_Object.php';
require_once __DIR__ . '/../html/HTML_Review.php';

class EAL_Review extends EAL_Object {

	private $item_id;
	private $item;
	private $score;
	private $feedback;
	private $overall;
	
	
	public static $dimension1 = array (
		'description' => 'Fall- oder Problemvignette', 
		'question' => 'Aufgabenstellung', 
		'answers' => 'Antwortoptionen');
	
	public static $dimension2 = array (
		'correctness' => 'Fachl. Richtigkeit', 
		'relevance' => 'Relevanz bzgl. LO', 
		'wording' => 'Formulierung');
	
	
	public function getItemId(): int {
		return $this->item_id;
	}
	
	public function setItemId(int $item_id) {
		
		if (($this->item_id != $item_id) || ($item_id < 0)) {
			$this->item_id = $item_id;
			$this->item = NULL;
		}
	}
	

	public function getScore(string $dim1, string $dim2): int {
		
		if (!in_array($dim1, self::$dimension1)) {
			throw new Exception('Unknown review dimension 1: ' . $dim1);
		}
		
		if (!in_array($dim2, self::$dimension2)) {
			throw new Exception('Unknown review dimension 2: ' . $dim2);
		}
		
		return $this->score[$dim1][$dim2];
	}
	
	
	public function setScore(string $dim1, string $dim2, int $value) {
		
		if (!in_array($dim1, self::$dimension1)) {
			throw new Exception('Unknown review dimension 1: ' . $dim1);
		}
		
		if (!in_array($dim2, self::$dimension2)) {
			throw new Exception('Unknown review dimension 2: ' . $dim2);
		}
		
		$this->score[$dim1][$dim2] = $value;
	}
	

	public function getFeedback(): string {
		return $this->feedback;
	}
	
	public function setFeedback(string $feedback) {
		$this->feedback = $feedback;
	}
	
	

	public function getOverall(): int {
		return $this->overall;
	}

	public function setOverall(int $overall) {
		$this->overall = $overall;
	}

	
	
	function __construct(int $review_id = -1) {
	
		parent::__construct();
		$this->setItemId(-1);
			
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = 0;
			}
		}
		
		$this->setFeedback('');
		$this->setOverall(0);
	}
	
	
	public function getHTMLPrinter (): HTML_Review {
		return new HTML_Review($this);
	}
	

	

	
	

	
	


	
	public static function save ($post_id, $post) {
	
		$review = new EAL_Review();
		if ($_POST["post_type"] != $review->getType()) return;
		$review->saveToDB();
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
	

/*	
	protected function loadFromPOSTRequest () {
	
		$this->setId ($_POST["post_ID"]);
		$this->setItemId($_GET['item_id'] ?? $_POST['item_id'] ?? -1);
		
		foreach (self::$dimension1 as $dim1 => $v1) {
			foreach (self::$dimension2 as $dim2 => $v2) {
				$this->setScore($dim1, $dim2, $_POST["review_{$dim1}_{$dim2}"] ?? 0);
			}
		}
		
		$this->setLevel(new EAL_Level($_POST, 'review_level_'));
		$this->setFeedback(isset ($_POST['review_feedback']) ? html_entity_decode (stripslashes($_POST['review_feedback'])) : '');
		$this->setOverall($_POST['review_overall'] ?? '');
	}
	
	public static function delete ($post_id) {
		
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_review', array( 'id' => $post_id ), array( '%d' ) );
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
					'level_FW' => $this->level->get('FW'),
					'level_KW' => $this->level->get('KW'),
					'level_PW' => $this->level->get('PW'),
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
		
		$this->level = new EAL_Level($sqlres);
		
		$this->feedback = $sqlres['feedback'];
		$this->overall = $sqlres['overall'];
	}
	

	
	*/

}

?>