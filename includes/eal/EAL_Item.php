<?php

require_once (__DIR__ . "/../class.CLA_RoleTaxonomy.php");
require_once 'EAL_Object.php';

abstract class EAL_Item extends EAL_Object {

	public $title;			// title 
	public $description;	// description (e.g., vignette, use case, scenarion)
	public $question;		// actual question
	
	public $level;
	public $learnout;
	public $learnout_id;
	
	public $difficulty;
	public $note;
	public $flag;
	
	public $minnumber = null;	// minnumber/maxnumber: range of correct answers (relevant for MC only)
	public $maxnumber = null;
	
	public static $level_label = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
	public static $level_type = ["FW", "KW", "PW"];
	
	public static $flag_icon = ["", "dashicons-star-filled", "dashicons-flag", "dashicons-yes", "dashicons-no"];
	
	public static $category_value_label = [
			"type" => ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"],
			"level" => ["1" => "Erinnern", "2" => "Verstehen", "3" => "Anwenden", "4" => "Analysieren", "5" => "Evaluieren", "6" => "Erschaffen"],
			"dim" => ["FW" => "FW", "KW" => "KW", "PW" => "PW"]
	];
	
	public static $category_label = [
			"type" => "Item Typ",
			"level" => "Anforderungsstufe",
			"dim" => "Wissensdimension",
			"topic1" => "Topic Stufe 1",
			"topic2" => "Topic Stufe 2",
			"topic3" => "Topic Stufe 3",
			"lo" => "Learning Outcome"
	];
	
	
	public abstract function getHTMLPrinter (): HTML_Item;
	
	
	function __construct(int $item_id = -1, string $prefix="") {
		
		parent::__construct();
		
		if ($item_id > 0) {
			$this->loadFromDB($item_id);
			return;
		} 
		
		if ($_POST[$prefix."post_type"] == $this->getType()) {
			$this->loadFromPOSTRequest($prefix);
			return;
		} 

		$this->setId ($item_id);
		$this->title = '';
		$this->description = '';
		$this->question = '';

		$this->level = ["FW" => null, "KW" => null, "PW" => null];
		$this->learnout_id = $_POST['learnout_id'] ?? $_GET['learnout_id'] ?? null;
		$this->learnout = null;
		
		$this->difficulty = null;
		$this->note = "";
		$this->flag = 0;
			
		
		global $post;
		if ($post->post_type != $this->getType()) return;

		if (get_post_status($post->ID)=='auto-draft') {
			$this->setId($post->ID);
		} else {
			$this->loadFromDB($post->ID);
		}
	}
	
	

	
	
	
	public static function load (string $item_type, int $item_id, string $prefix=""): EAL_Item {
		if ($item_type == 'itemsc') return new EAL_ItemSC($item_id, $prefix);
		if ($item_type == 'itemmc') return new EAL_ItemMC($item_id, $prefix);
	}
	
	
	/**
	 * Initialize item from _POST Request data
	 */
	protected function loadFromPOSTRequest (string $prefix="") {
	
		$this->setId ($_POST[$prefix."post_ID"]);
		$this->title = stripslashes($_POST[$prefix."post_title"]);
		$this->description = isset($_POST[$prefix.'item_description']) ? html_entity_decode (stripslashes($_POST[$prefix.'item_description'])) : null;
		$this->question = isset ($_POST[$prefix.'item_question']) ? html_entity_decode (stripslashes($_POST[$prefix.'item_question'])) : null;

		$this->level = ["FW" => null, "KW" => null, "PW" => null];
		$this->level["FW"] = $_POST[$prefix.'item_level_FW'] ?? null;
		$this->level["KW"] = $_POST[$prefix.'item_level_KW'] ?? null;
		$this->level["PW"] = $_POST[$prefix.'item_level_PW'] ?? null;
		
		$this->learnout_id = $_GET[$prefix.'learnout_id'] ?? $_POST[$prefix.'learnout_id'] ?? null;
		$this->learnout = null;		// lazy loading
		
		$this->difficulty = null;
		$this->note = isset ($_POST[$prefix.'item_note']) ? html_entity_decode (stripslashes($_POST[$prefix.'item_note'])) : null;
		$this->flag = $_POST[$prefix.'item_flag'] ?? null;
		
		$this->minnumber = null;
		$this->maxnumber = null;
		
		// 	$this->domain = RoleTaxonomy::getCurrentRoleDomain()["name"];
		$this->setDomain($_POST[$prefix."domain"]); 
		if (($this->getDomain() == "") && (isset($_POST[$prefix.'tax_input']))) {
			foreach ($_POST[$prefix.'tax_input'] as $key => $value) {
				$this->setDomain($key);
				break;
			}
		}
	}
	
	
	protected function loadFromDB (int $item_id) {
	
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_item WHERE id = {$item_id} AND type ='{$this->getType()}'", ARRAY_A);
	
		$this->setId ($sqlres['id']);
		$this->title = $sqlres['title'];
		$this->description = $sqlres['description'];
		$this->question = $sqlres['question'];
	
		$this->level = ["FW" => null, "KW" => null, "PW" => null];
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
	
		$this->learnout_id = $sqlres['learnout_id'];
		$this->learnout = null; // lazy loading
		
		$this->minnumber = ($this->getType() == "itemmc") && (isset($sqlres['minnumber'])) ? $sqlres['minnumber'] : null;
		$this->maxnumber = ($this->getType() == "itemmc") && (isset($sqlres['maxnumber'])) ? $sqlres['maxnumber'] : null;
		
		$this->difficulty = $sqlres['difficulty'];
		$this->note = $sqlres['note'];
		$this->flag = $sqlres['flag'];
		
		$this->setDomain($sqlres['domain']);
	}
	
	
	
	
	
	public function saveToDB () {
		
		global $wpdb;
		
		$wpdb->replace(
			"{$wpdb->prefix}eal_item",
			array(
					'id' => $this->getId(),
					'title' => $this->title,
					'description' => $this->description,
					'question' => $this->question,
					'level_FW' => $this->level["FW"],
					'level_KW' => $this->level["KW"],
					'level_PW' => $this->level["PW"],
					'points'   => $this->getPoints(),
					'difficulty' => $this->difficulty,
					'learnout_id' => $this->learnout_id,
					'type' => $this->getType(),
					'domain' => $this->getDomain(),
					'note' => $this->note,
					'flag' => $this->flag,
					'minnumber' => $this->minnumber,
					'maxnumber' => $this->maxnumber
			),
			array('%d','%s','%s','%s','%d','%d','%d','%d','%f','%d','%s','%s','%s','%d','%d','%d')
			);
	}
	
	
	public static function delete ($post_id) {
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_item', array( 'id' => $post_id ), array( '%d' ) );
		$wpdb->delete( '{$wpdb->prefix}eal_review', array( 'item_id' => $post_id ), array( '%d' ) );
	}
	
	
	/**
	 * Implements lazy loading of learning outcome 
	 */
	public function getLearnOut () {
		
		if (is_null ($this->learnout_id )) return null;
		
		if (is_null ($this->learnout)) {
			$this->learnout = new EAL_LearnOut($this->learnout_id);
		}
		
		return $this->learnout;
	}
	
	
	/**
	 * 
	 * @return array of EAL_Review
	 */
	public function getReviews (): array {
		
		$res = array();
		foreach ($this->getReviewIds() as $review_id) {
			array_push($res, new EAL_Review($review_id));
		}
		
		return $res;
	}
	
	
	public function getReviewIds (): array {
	
		global $wpdb;
		return $wpdb->get_col ("
			SELECT R.id
			FROM {$wpdb->prefix}eal_review R
			JOIN {$wpdb->prefix}posts RP ON (R.id = RP.id)
			WHERE RP.post_parent=0 AND R.item_id = {$this->getId()} AND RP.post_status IN ('publish', 'pending', 'draft')");
		
	}
	
	
	
	public function getPoints(): int { return -1; }
	
	public function getStatusString (): string {
		
		switch (get_post_status($this->getId())) {
			case 'publish': return 'Published';
			case 'pending': return 'Pending Review';
			case 'draft': return 'Draft';
		}
		return 'Unknown';
	}
	
	
	/**
	 * Create database tables when plugin is activated 
	 */
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		/**
		 * minnumber/maxnumber: range of correct answers (relevant for MC only)
		 */
		
		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_item (
			id bigint(20) unsigned NOT NULL,
			title text,
			description mediumtext,
			question mediumtext,
			level_FW tinyint unsigned,
			level_KW tinyint unsigned,
			level_PW tinyint unsigned,
			points smallint,
			difficulty decimal(10,1), 
			learnout_id bigint(20) unsigned,
			type varchar(20) NOT NULL,
			domain varchar(50) NOT NULL,
			note text,
			flag tinyint,
			minnumber smallint,
			maxnumber smallint,
			PRIMARY KEY  (id),
			KEY index_type (type),
			KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);

		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_result (
			test_id bigint(20) unsigned NOT NULL,
			item_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			points smallint,
			PRIMARY KEY  (test_id, item_id, user_id)
			) {$wpdb->get_charset_collate()};"
		);
		

	}
		

}

?>