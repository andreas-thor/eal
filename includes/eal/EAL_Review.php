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
		
		if (!array_key_exists($dim1, self::$dimension1)) {
			throw new Exception('Unknown review dimension 1: ' . $dim1);
		}
		
		if (!array_key_exists($dim2, self::$dimension2)) {
			throw new Exception('Unknown review dimension 2: ' . $dim2);
		}
		
		return $this->score[$dim1][$dim2];
	}
	
	
	public function setScore(string $dim1, string $dim2, int $value) {
		
		if (!array_key_exists($dim1, self::$dimension1)) {
			throw new Exception('Unknown review dimension 1: ' . $dim1);
		}
		
		if (!array_key_exists($dim2, self::$dimension2)) {
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
			$this->item = EAL_Factory::createNewItem($post->post_type, $this->item_id);
		}
		return $this->item;
	}
	


}

?>