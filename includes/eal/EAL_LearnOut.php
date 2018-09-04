<?php

require_once 'EAL_Object.php';
require_once __DIR__ . '/../html/HTML_LearnOut.php';

class EAL_LearnOut extends EAL_Object  {

	private $title;
	private $description;
	private $no_of_items;
	
	function __construct(int $id = -1, array $object = NULL, string $prefix = '', string $level_prefix = '') {
		parent::__construct($id, $object, $prefix, $level_prefix);
		$this->title = '';
		$this->description = 'Die Studierenden sind nach Abschluss der Lehrveranstaltung in der Lage ...';
		$this->no_of_items = 0;
	}
	
	public static function createFromArray (int $id, array $object, string $prefix = ''): EAL_LearnOut {
		$learnout = new EAL_LearnOut($id);
		$learnout->initFromArray($object, $prefix);
		return $learnout;
	}
	
	/**
	 * 
	 * @param array $object = ['post_title' => ..., 'learnout_description' => ...
	 * @param string $prefix
	 * @param string $levelPrefix
	 */
	public function initFromArray (array $object, string $prefix, string $levelPrefix='learnout_level_') {

		parent::initFromArray($object, $prefix, $levelPrefix);
		
		if (isset ($object[$prefix . 'post_title'])) {
			$this->title = stripslashes($object[$prefix . 'post_title']);
		}
		
		if (isset ($object[$prefix . 'learnout_description'])) {
			$this->description = html_entity_decode (stripslashes($object[$prefix . 'learnout_description']));
		}
		
		if (isset ($object[$prefix . 'no_of_items'])) {
			$this->no_of_items = intval($object[$prefix . 'no_of_items']);
		}
	}
	
	
	public static function getType(): string {
		return 'learnout';
	}
	
	
	public function getTitle (): string {
		return $this->title;
	}
	
	public function getDescription (): string {
		return $this->description;
	}
	
	public function getNoOfItems (): int {
		return $this->no_of_items;
	}

	
	public function copyMetadata (EAL_LearnOut $sourceLO) {
		$this->setLevel($sourceLO->getLevel());
	}
	
	
	
	public function getHTMLPrinter (): HTML_LearnOut {
		return new HTML_LearnOut($this);
	}
	
}

?>