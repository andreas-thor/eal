<?php

class EAL_Review {

	public static $item_types = ["itemmc", "itemsc"];
	
	public $id;
	public $item_id;
	public $item_type;
	public $item;
	public $score;
	
	public $level_FW;
	public $level_PW;
	public $level_KW;
	
	public $feedback;
	public $overall;
	
	public static $dimension1 = array ('description' => "Fall- oder Problemvignette", 'question' => 'Aufgabenstellung', 'answers' => 'Antwortoptionen');
	public static $dimension2 = array ('correctness' => "Fachl. Richtigkeit", 'relevance' => "Relevanz bzgl. LO", 'wording' => "Formulierung");
	
	
	
	
	function __construct () {
		
	}
	
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	public function init ($post_id, $post) {
	
		$this->id = $post_id;
		$this->item_id = isset ($_GET['item_id']) ? $_GET['item_id'] : (isset ($_POST['item_id']) ? $_POST['item_id'] : null);
		$this->item_type = isset ($_GET['item_type']) ? $_GET['item_type'] : (isset ($_POST['item_type']) ? $_POST['item_type'] : null);
		$this->item = null;	
		
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = isset ($_POST["review_{$k1}_{$k2}"]) ? $_POST["review_{$k1}_{$k2}"] : null;
			}
		}
		
		$this->level_FW = isset ($_POST['review_level_FW']) ? $_POST['review_level_FW'] : null;
		$this->level_KW = isset ($_POST['review_level_KW']) ? $_POST['review_level_KW'] : null;
		$this->level_PW = isset ($_POST['review_level_PW']) ? $_POST['review_level_PW'] : null;
		$this->feedback = isset ($_POST['review_feedback']) ? $_POST['review_feedback'] : null;
		$this->overall  = isset ($_POST['review_overall'])  ? $_POST['review_overall']  : null;
		
	}
	
	public function getItem () {
		
		if (is_null($this->item_id)) return null;
		
		if (is_null($this->item)) { 
		
// 					if ($this->item_type == "itemmc") {
				$this->item = new EAL_ItemMC();
// 			}
			
// 			if ($this->item_type == "itemsc") {
// 				$this->item = new EAL_ItemSC();
// 			}
				
			$this->item->loadById($this->item_id);
		}
		return $this->item;
		
	}
	
	
	public function load () {
		
		global $post;
		
		$this->item_type = isset ($_POST['item_type']) ? $_POST['item_type'] : $_GET['item_type'];
		
		if (get_post_status($post->ID)=='auto-draft') {
				
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
			
			$this->level_FW = 0;
			$this->level_PW = 0;
			$this->level_KW = 0;
			$this->feedback = '';
			$this->overall = 0;
				
		} else {
				
			global $wpdb;
			$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$this->item_type}_review WHERE id = {$post->ID}", ARRAY_A);
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

			$this->level_FW = $sqlres['level_FW'];
			$this->level_PW = $sqlres['level_PW'];
			$this->level_KW = $sqlres['level_KW'];
			$this->feedback = $sqlres['feedback'];
			$this->overall = $sqlres['overall'];;
				
		}
		
	}
	
	
	
	public static function save ($post_id, $post) {
	
		global $wpdb;
		$review = new EAL_Review();
		$review->init($post_id, $post);
	
		
		$replaceScore = array ();
		$replaceType = array ();
		foreach (self::$dimension1 as $k1 => $v1) {
			foreach (self::$dimension2 as $k2 => $v2) {
				$replaceScore["{$k1}_{$k2}"] = $review->score[$k1][$k2];
				array_push($replaceType, "%d");
			}
		}
		
		
		$wpdb->replace(
				"{$wpdb->prefix}eal_{$review->item_type}_review",
				array_merge (
					array(
						'id' => $review->id,
						'item_id' => $review->item_id,
						'level_FW' => $review->level_FW,
						'level_KW' => $review->level_KW,
						'level_PW' => $review->level_PW,
						'feedback' => $review->feedback,
						'overall'  => $review->overall
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
	
	

	
	
	

}

?>