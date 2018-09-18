<?php

require_once __DIR__ . '/../class.CLA_RoleTaxonomy.php';
require_once 'EAL_Object.php';


abstract class EAL_Item extends EAL_Object {

	private $title;			// title 
	private $description;	// description (e.g., vignette, use case, scenarion)
	private $question;		// actual question
	
	private $learnout_id;	// id of learning outcome
	private $learnout;		// learning outcome; LAZY LOADING
	
	private $note;			// note (text)
	private $flag;			// flag (int)
	
	private $no_of_reviews;	// number of reviews; DERIVED
	private $difficulty;	// difficulty; DERIVED
	private $no_of_testresults;	// number of test results; DERIVED
	
	public $minnumber;	// FIXME minnumber/maxnumber: range of correct answers (relevant for MC only)
	public $maxnumber;
	
// 	public static $level_type = ["FW", "KW", "PW"];
	
// 	public static $category_value_label = [
// 			"type" => [EAL_ItemSC::getType() => "Single Choice", EAL_ItemMC::getType() => "Multiple Choice", EAL_ItemFT::getType() => 'Free Text'],
// 			"level" => ["1" => "Erinnern", "2" => "Verstehen", "3" => "Anwenden", "4" => "Analysieren", "5" => "Evaluieren", "6" => "Erschaffen"],
// 			"dim" => ["FW" => "FW", "KW" => "KW", "PW" => "PW"]
// 	];
	
	public static $category_label = [
			"type" => "Item Typ",
			"level" => "Anforderungsstufe",
			"dim" => "Wissensdimension",
			"topic1" => "Topic Stufe 1",
			"topic2" => "Topic Stufe 2",
			"topic3" => "Topic Stufe 3",
			"lo" => "Learning Outcome",
			'difficulty' => 'Difficulty'
	];
	
	
	
	function __construct(int $id, int $learnout_id=-1) {
		parent::__construct($id);
		
		$this->title = '';
		$this->description = '';
		$this->question = '';
		$this->learnout_id = $learnout_id;
		$this->learnout = NULL;
		$this->note = '';
		$this->flag = 0;
		$this->no_of_reviews = 0;
		$this->difficulty = -1;
		$this->no_of_testresults = 0;
		$this->minnumber = -1;
		$this->maxnumber = -1;
	}
	
	
	
	public static function createByTypeFromArray (int $id, string $item_type, array $object = NULL, string $prefix = ''): EAL_Item {
			
		switch ($item_type) {
			case EAL_ItemSC::getType():	return EAL_ItemSC::createFromArray($id, $object, $prefix);
			case EAL_ItemMC::getType(): return EAL_ItemMC::createFromArray($id, $object, $prefix);
			case EAL_ItemFT::getType(): return EAL_ItemFT::createFromArray($id, $object, $prefix);
		}

		throw new Exception('Could not create item. Unknown item type ' . $item_type);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see EAL_Object::initFromArray()
	 * @param array $object = ['post_title' => ..., 'item_description' => ..., 'item_question' => ..., 'learnout_id' => ..., 'item_note' => ..., 'item_flag' => ...
	 */
	public function initFromArray (array $object, string $prefix, string $levelPrefix) {
		
		parent::initFromArray($object, $prefix, $levelPrefix);
		
		if (isset($object[$prefix . 'post_title'])) {
			$this->title = stripslashes($object[$prefix . 'post_title']);
		}
		
		if (isset($object[$prefix . 'item_description'])) {
			$this->description = html_entity_decode (stripslashes($object[$prefix . 'item_description']));
		}
		
		if (isset($object[$prefix . 'item_question'])) {
			$this->question = html_entity_decode (stripslashes($object[$prefix . 'item_question']));
		}
		
		if (isset ($object[$prefix . 'learnout_id'])) {
			$this->learnout_id = intval($object[$prefix . 'learnout_id']);
			$this->learnout = NULL;
		}
		
		if (isset ($object[$prefix . 'item_note'])) {
			$this->note = html_entity_decode (stripslashes($object[$prefix . 'item_note']));
		}
		
		if (isset ($object[$prefix . 'item_flag'])) {
			$this->flag = intval ($object[$prefix . 'item_flag']);
		}
		
		if (isset ($object[$prefix . 'no_of_reviews'])) {
			$this->no_of_reviews = intval($object[$prefix . 'no_of_reviews']);
		}
		
		if (isset ($object[$prefix . 'difficulty'])) {
			$this->difficulty = floatval($object[$prefix . 'difficulty']);
		}
		
		if (isset ($object[$prefix . 'no_of_testresults'])) {
			$this->no_of_testresults = intval($object[$prefix . 'no_of_testresults']);
		}
	}

	
	public function convertToArray (string $prefix, string $levelPrefix): array {
		$object = parent::convertToArray($prefix, $levelPrefix);
		$object[$prefix . 'post_title'] = $this->title;
		$object[$prefix . 'item_description'] = $this->description;
		$object[$prefix . 'item_question'] = $this->question;
		$object[$prefix . 'learnout_id'] = $this->learnout_id;
		$object[$prefix . 'item_note'] = $this->note;
		$object[$prefix . 'item_flag'] = $this->flag;
		$object[$prefix . 'no_of_reviews'] = $this->no_of_reviews;
		$object[$prefix . 'difficulty'] = $this->difficulty;
		$object[$prefix . 'no_of_testresults'] = $this->no_of_testresults;
		return $object;
	}
	
	
	
	public function init (string $title, string $description, string $question, string $domain = NULL) {
		$this->title = $title;
		$this->description = $description;
		$this->question = $question;
		$this->setDomain($domain ?? RoleTaxonomy::getCurrentDomain());
	}
	
	
	
	public function copyMetadata (EAL_Item $sourceItem) {
		
		$this->setLevel($sourceItem->getLevel());
		$this->note = $sourceItem->note;
		$this->flag = $sourceItem->flag;
		$this->learnout_id = $sourceItem->learnout_id;
		$this->learnout = $sourceItem->learnout;
	}
	


	public function getTitle (): string {
		return $this->title;
	}
	
	public function getDescription (): string {
		return $this->description;
	}
	
	public function getQuestion (): string {
		return $this->question;
	}
	
	public function getLearnOutId(): int {
		return $this->learnout_id;
	}
	
	/**
	 * Implements lazy loading of learning outcome
	 */
	public function getLearnOut () {
		
		if ($this->learnout_id<=0) {	// no learning outcome assigned
			return null;
		}
		
		if (is_null ($this->learnout)) {
			$this->learnout = DB_Learnout::loadFromDB($this->learnout_id);
		}
		
		return $this->learnout;
	}
	
	public function getNote(): string {
		return $this->note;
	}
	
	public function getFlag(): int {
		return $this->flag;
	}
	
	public function getNoOfReviews (): int {
		return $this->no_of_reviews;
	}
	
	public function getDifficulty (): float {
		return $this->difficulty;
	}
	
	public function getNoOfTestResults (): int {
		return $this->no_of_testresults;
	}
	
	public function getMinNumber(): int {
		return $this->minnumber;
	}
	
	public function getMaxNumber(): int {
		return $this->maxnumber;
	}
	
	
	public abstract function getHTMLPrinter (): HTML_Item;
	
	
	public abstract function getPoints(): int;
	
	
	public function getStatusString (): string {
		
		switch (get_post_status($this->getId())) {
			case 'publish': return 'Published';
			case 'pending': return 'Pending Review';
			case 'draft': return 'Draft';
		}
		return 'Unknown';
	}

	

}

?>