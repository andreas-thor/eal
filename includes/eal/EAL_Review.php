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
	

	function __construct(int $review_id=-1, int $item_id=-1) {
		
		parent::__construct($review_id);
		
		$this->item_id = $item_id;
		$this->item = NULL;
		
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = 0;
			}
		}
		
		$this->feedback = '';
		$this->overall = 0;
	}
	
	
	public static function createFromArray (int $id, array $object = NULL, string $prefix = ''): EAL_Review {
		$review = new EAL_Review($id);
		$review->initFromArray($object, $prefix, 'review_level_');
		return $review;
	}
		
	
	public function initFromArray (array $object, string $prefix, string $levelPrefix) {
		
		parent::initFromArray($object, $prefix, $levelPrefix);
		
		if (isset ($object[$prefix . 'item_id'])) {
			$this->item_id = intval ($object[$prefix . 'item_id']);
			$this->item = NULL;
		}
		
		foreach (self::$dimension1 as $k1 => $v1) {
			foreach (self::$dimension2 as $k2 => $v2) {
				if (isset ($object['review_' . $prefix . $k1 . '_' . $k2])) {
					$this->score[$k1][$k2] = intval ($object['review_' . $prefix . $k1 . '_' . $k2]);
				}
			}
		}
		
		if (isset ($object[$prefix . 'review_feedback'])) {
			$this->feedback = html_entity_decode (stripslashes($object[$prefix . 'review_feedback']));
		}
		
		if (isset ($object[$prefix . 'review_overall'])) {
			$this->overall = intval ($object[$prefix . 'review_overall']);
		}
		
	}
	
	public static function getType(): string {
		return 'review';
	}
	
	
	
	
	public function getItemId(): int {
		return $this->item_id;
	}
	
	
	public function getItem (): EAL_Item {
		
		if ($this->item_id <= 0) {		// no item assigned
			throw new Exception('Review with id ' . $this->getId() . ' does not have an assigned item!');
		}
		
		if (is_null($this->item)) {
			$post = get_post($this->item_id);
			if ($post == null) {
				throw new Exception ('Could not find item with id=' . $this->item_id);
			}
			
			$this->item = DB_Item::loadFromDB($this->item_id, $post->post_type);
		}
		return $this->item;
	}
	

	public function getScore(string $dim1, string $dim2): int {
		
		if (!array_key_exists($dim1, self::$dimension1)) {
			throw new Exception('Unknown review dimension 1: ' . $dim1);
		}
		
		if (!array_key_exists($dim2, self::$dimension2)) {
			throw new Exception('Unknown review dimension 2: ' . $dim2);
		}
		
		return $this->score[$dim1][$dim2];
	}
	
	
	public function getFeedback(): string {
		return $this->feedback;
	}
	

	public function getOverall(): int {
		return $this->overall;
	}


	
	

	
	
	public function getHTMLPrinter (): HTML_Review {
		return new HTML_Review($this);
	}
	

	
	

	


}

?>