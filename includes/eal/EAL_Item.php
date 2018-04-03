<?php

require_once (__DIR__ . "/../class.CLA_RoleTaxonomy.php");
require_once 'EAL_Object.php';


abstract class EAL_Item extends EAL_Object {

	private $title;			// title 
	private $description;	// description (e.g., vignette, use case, scenarion)
	private $question;		// actual question
	
	private $learnout;
	private $learnout_id;
	
	public $difficulty;
	
	private $note;
	private $flag;
	
	public $minnumber = null;	// minnumber/maxnumber: range of correct answers (relevant for MC only)
	public $maxnumber = null;
	
	public static $level_type = ["FW", "KW", "PW"];
	
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
	
	
	



	public function getTitle (): string {
		return $this->title;
	}
	
	public function setTitle (string $title) {
		$this->title = $title;
	}
	
	public function getDescription (): string {
		return $this->description;
	}
	
	public function setDescription (string $description) {
		$this->description = $description;
	}
	
	public function getQuestion (): string {
		return $this->question;
	}
	
	public function setQuestion (string $question) {
		$this->question = $question;
	}
	
	public function getNote(): string {
		return $this->note;
	}
	
	public function setNote(string $note) {
		$this->note = $note;
	}
	
	public function getFlag(): int {
		return $this->flag ?? 0;
	}
	
	public function setFlag(int $flag) {
		$this->flag = $flag;
	}
	
	
	public function getDifficulty (): float {
		return $this->difficulty ?? -1;
	}
	
	public function setDifficulty (float $difficulty) {
		$this->difficulty = $difficulty;
	}
	
	
	public function getMinNumber(): int {
		return $this->minnumber ?? -1;
	}
	
	public function getMaxNumber(): int {
		return $this->maxnumber ?? -1;
	}
	
	
	public abstract function getHTMLPrinter (): HTML_Item;
	
	
	function __construct() {
		parent::__construct();
		
		$this->setTitle('');
		$this->setDescription('');
		$this->setQuestion('');
		$this->setLearnOutId(-1);
		$this->setDifficulty(-1);
		$this->setNote('');
		$this->setFlag(0);
		$this->minnumber = -1;
		$this->maxnumber = -1;
		
	}
	

	
	
	public function init (string $title, string $description, string $question, string $domain = NULL) {
		$this->title = $title;
		$this->description = $description;
		$this->question = $question;
		$this->setDomain($domain ?? RoleTaxonomy::getCurrentRoleDomain()["name"]);
	}
	
	
	
	public function copyMetadata (EAL_Item $sourceItem) {
		
		$this->level = $sourceItem->level;
		$this->learnout_id = $sourceItem->learnout_id;
		$this->leanout = NULL;
		$this->note = $sourceItem->getNote();
		$this->flag = $sourceItem->getFlag();
	}
	
	
	public static function load (string $item_type, int $item_id, string $prefix=""): EAL_Item {
		if ($item_type == 'itemsc') return new EAL_ItemSC($item_id, $prefix);
		if ($item_type == 'itemmc') return new EAL_ItemMC($item_id, $prefix);
	}
	
	
	/**
	 * Implements lazy loading of learning outcome 
	 */
	public function getLearnOut () {
		
		if (is_null ($this->learnout_id )) {
			return null;
		}
		
		if (is_null ($this->learnout)) {
			$this->learnout = EAL_Factory::createNewLearnOut($this->learnout_id);
		}
		
		return $this->learnout;
	}
	
	
	/**
	 * 
	 * @return int Id of learning outcome, or -1 if no learning outcome available
	 */
	public function getLearnOutId(): int {
		return $this->learnout_id ?? -1;
	}
	
	public function setLearnOutId (int $learnout_id) {
		if (($this->learnout_id != $learnout_id) || ($learnout_id<0)) {
			$this->learnout_id = $learnout_id;
			$this->learnout = NULL;
		}
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
	
	
	
	public abstract function getPoints(): int;
	
	
	public function getStatusString (): string {
		
		switch (get_post_status($this->getId())) {
			case 'publish': return 'Published';
			case 'pending': return 'Pending Review';
			case 'draft': return 'Draft';
		}
		return 'Unknown';
	}

	
	
	
	/**
	 * Initialize item from _POST Request data
	 */
/*	
	protected function loadFromPOSTRequest (string $prefix="") {
		
		$this->setId ($_POST[$prefix."post_ID"]);
		$this->title = stripslashes($_POST[$prefix."post_title"]);
		$this->description = isset($_POST[$prefix.'item_description']) ? html_entity_decode (stripslashes($_POST[$prefix.'item_description'])) : null;
		$this->question = isset ($_POST[$prefix.'item_question']) ? html_entity_decode (stripslashes($_POST[$prefix.'item_question'])) : null;
		$this->level = new EAL_Level($_POST, $prefix.'item_level_');
		
		
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
		$this->init('', '', '');
		
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
	

	
	protected function loadFromDB (int $item_id) {
		
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_item WHERE id = {$item_id} AND type ='{$this->getType()}'", ARRAY_A);
		
		$this->setId ($sqlres['id']);
		$this->title = $sqlres['title'];
		$this->description = $sqlres['description'];
		$this->question = $sqlres['question'];
		$this->level = new EAL_Level($sqlres);
		
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
				'level_FW' => $this->level->get('FW'),
				'level_KW' => $this->level->get('KW'),
				'level_PW' => $this->level->get('PW'),
				'points'   => $this->getPoints(),
				'difficulty' => $this->difficulty,
				'learnout_id' => $this->learnout_id,
				'type' => $this->getType(),
				'domain' => $this->getDomain(),
				'note' => $this->getNote(),
				'flag' => $this->getFlag(),
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
	
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

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
		*/

}

?>