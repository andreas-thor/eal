<?php


require_once('IMEX_Object.php');

abstract class IMEX_Item extends IMEX_Object {
	
	
	public function __construct() {
		parent::__construct();
	}
	
	
	abstract public function generateExportFile (array $itemids);
	
	
	public function downloadItems (array $itemids) {
		$this->generateExportFile($itemids);
		$this->download();
	}
	
}

?>