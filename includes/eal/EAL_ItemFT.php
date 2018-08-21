<?php

require_once 'EAL_Item.php';
require_once __DIR__ . '/../html/HTML_ItemFT.php';


/**
 * A free text item just adds a points value to the generic EAL_Item
 * @author Andreas
 */

class EAL_ItemFT extends EAL_Item {
	
	private $points;
	 
	 
	function __construct(int $id = -1, int $learnout_id=-1) {
		
		parent::__construct($id, $learnout_id);
		
		$this->setPoints(5);
	}
	
	
	public static function createFromArray (int $id, array $object = NULL, string $prefix = ''): EAL_ItemFT {
		
		$item = new EAL_ItemFT($id);
		$item->initFromArray($object, $prefix, 'item_level_');
		return $item;
	}
	
	
	public function initFromArray (array $object, string $prefix, string $levelPrefix) {
		
		parent::initFromArray($object, $prefix, $levelPrefix);
		$this->setPoints(intval ($object[$prefix . 'item_points']));
	}
	
	
	public function convertToArray (string $prefix, string $levelPrefix): array {
		
		$object = parent::convertToArray($prefix, $levelPrefix);
		$object[$prefix . 'item_points'] = $this->getPoints();
		return $object;
	}
	
	
	public static function getType(): string {
		return 'itemft';
	}
	
	
	public function getHTMLPrinter (): HTML_Item {
		return new HTML_ItemFT($this);
	}
	
	
	public function setPoints(int $points) {
		$this->points = $points;
	}
	
	public function getPoints(): int {
		return $this->points;
	}



	
}

?>