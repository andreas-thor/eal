<?php


abstract class EAL_Item_Review {

	public static $item_types = ["itemmc", "itemsc"];
	
	public $type;	// will be set in subclasses (EAL_ItemMC, EAL_ItemSC, ...)
	
	public $id;
	public $item_id;
	public $item;
	public $score;
	public $level;
	public $feedback;
	public $overall;
	
	public static $dimension1 = array ('description' => "Fall- oder Problemvignette", 'question' => 'Aufgabenstellung', 'answers' => 'Antwortoptionen');
	public static $dimension2 = array ('correctness' => "Fachl. Richtigkeit", 'relevance' => "Relevanz bzgl. LO", 'wording' => "Formulierung");
	
	
	
	
	function __construct () {
		$this->level = ["FW" => null, "KW" => null, "PW" => null];
	}
	
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	public function init ($post_id, $post) {
	
		$this->id = $post_id;
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
		$this->feedback = isset ($_POST['review_feedback']) ? $_POST['review_feedback'] : null;
		$this->overall  = isset ($_POST['review_overall'])  ? $_POST['review_overall']  : null;
		
	}
	
	
	abstract public function getItem ();
	
	
	public function load () {
		
		global $post, $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$this->type} WHERE id = {$post->ID}", ARRAY_A);
		
		if ($sqlres == null) {
				
			$this->id = $post->ID;
			$this->item_id = isset ($_POST['item_id']) ? $_POST['item_id'] : $_GET['item_id'];
			$this->item = null;	
			
			$this->score = array();
			foreach (self::$dimension1 as $k1 => $v1) {
				$this->score[$k1] = array ();
				foreach (self::$dimension2 as $k2 => $v2) {
					$this->score[$k1][$k2] = 0;
				}
			}
			
			$this->level["FW"] = 0;
			$this->level["KW"] = 0;
			$this->level["PW"] = 0;
			$this->feedback = '';
			$this->overall = 0;
				
		} else {
				
			$this->id = $sqlres['id'];
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
			$this->overall = $sqlres['overall'];;
				
		}
		
	}
	
	

	public function save ($post_id, $post) {
	
		global $wpdb;
	
		$this->init($post_id, $post);
	
		$replaceScore = array ();
		$replaceType = array ();
		foreach (self::$dimension1 as $k1 => $v1) {
			foreach (self::$dimension2 as $k2 => $v2) {
				$replaceScore["{$k1}_{$k2}"] = $this->score[$k1][$k2];
				array_push($replaceType, "%d");
			}
		}
	
	
		$wpdb->replace(
				"{$wpdb->prefix}eal_{$this->type}",
				array_merge (
						array(
								'id' => $this->id,
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
	
	
	
	public static function delete ($post_id) {
	
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_review', array( 'id' => $post_id ), array( '%d' ) );
	}
	
	

	public static function createTableReview($tabname) {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
	
		$sqlScore = "";
		foreach (EAL_Item_Review::$dimension1 as $k1 => $v1) {
			foreach (EAL_Item_Review::$dimension2 as $k2 => $v2) {
				$sqlScore .= "{$k1}_{$k2} tinyint unsigned, \n";
			}
		}
	
		dbDelta (
			"CREATE TABLE [$tabname} (
				id bigint(20) unsigned NOT NULL,
				item_id bigint(20) unsigned NOT NULL, {$sqlScore}
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				feedback text,
				overall tinyint unsigned,
				KEY  (item_id),
				PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
	

}

?>