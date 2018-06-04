<?php

require_once 'EAL_Object.php';
require_once __DIR__ . '/../html/HTML_TestResult.php';

class EAL_TestResult extends EAL_Object  {

	private $title;
	private $description;
	
	function __construct(int $id = -1) {
		parent::__construct($id);
		$this->title = '';
		$this->description = '';
	}
	
	public static function createFromArray (int $id, array $object, string $prefix = ''): EAL_TestResult {
		$testresult = new EAL_TestResult($id);
		$testresult->initFromArray($object, $prefix);
		return $testresult;
	}
	
	/**
	 * 
	 * @param array $object = ['post_title' => ..., 'learnout_description' => ...
	 * @param string $prefix
	 * @param string $levelPrefix
	 */
	public function initFromArray (array $object, string $prefix, string $levelPrefix='') {

		parent::initFromArray($object, $prefix, '');
		
		if (isset ($object[$prefix . 'post_title'])) {
			$this->title = stripslashes($object[$prefix . 'post_title']);
		}
		
		if (isset ($object[$prefix . 'testresult_description'])) {
			$this->description = html_entity_decode (stripslashes($object[$prefix . 'testresult_description']));
		}
	}
	
	
	public static function getType(): string {
		return 'testresult';
	}
	
	
	public function getTitle (): string {
		return $this->title;
	}
	
	public function getDescription (): string {
		return $this->description;
	}
	
	

	
	
	public function getHTMLPrinter (): HTML_TestResult {
		return new HTML_TestResult($this);
	}
	
}

?>