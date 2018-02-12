<?php


require_once(__DIR__ . '/../eal/EAL_ItemSC.php');
require_once(__DIR__ . '/../eal/EAL_ItemMC.php');

abstract class ImportExport {
	
	protected $downloaddir;			// directory where the export file is stored
	protected $downloadfilename;	// name of the export file (will be set in sub class)
	protected $downloadextension;	// file extension (e.g., 'zip' or 'xml') of export file
	
	
	public function __construct() {
		$this->downloaddir = __DIR__ . '/../../download/';
	}
	
	
	abstract public function generateExportFile (array $itemids);
	
	abstract public function import (array $file);
	
	
	
	
	
	protected function getDownloadFullname (): string {
		return $this->downloaddir . $this->downloadfilename . "." . $this->downloadextension;
	}
	
	
 	public function download (array $itemids) {
 		
 		/* create download directory if it does not exist */
 		if (!file_exists($this->downloaddir)) {
 			mkdir($this->downloaddir, 0777, true);
 		}

 		/* generate HTTP response */
 		$this->generateExportFile($itemids);
 		header("Content-type: application/" . $this->downloadextension);
 		header("Content-Disposition: attachment; filename=" . $this->downloadfilename . "." . $this->downloadextension);
 		header("Content-length: " . filesize($this->getDownloadFullname()));
 		header("Pragma: no-cache");
 		header("Expires: 0");
 		readfile($this->getDownloadFullname()); 		
 	}
	
}

?>