<?php


class IMEX_Object {
	
	protected $downloaddir;			// directory where the export file is stored
	protected $downloadfilename;	// name of the export file (will be set in sub class)
	protected $downloadextension;	// file extension (e.g., 'zip' or 'xml') of export file
	
	
	public function __construct() {
		$this->downloaddir = __DIR__ . '/../../download/';
		
		/* create download directory if it does not exist */
		if (!file_exists($this->downloaddir)) {
			mkdir($this->downloaddir, 0777, true);
		}
	}
	
	
	
	
	protected function getDownloadFullname (): string {
		return $this->downloaddir . $this->downloadfilename . "." . $this->downloadextension;
	}
	
	
 	protected function download () {
 		
 		/* generate HTTP response */
 		header("Content-type: application/" . $this->downloadextension . "; charset=utf-8");
 		header("Content-Disposition: attachment; filename=" . $this->downloadfilename . "." . $this->downloadextension);
 		header("Content-length: " . filesize($this->getDownloadFullname()));
 		header("Pragma: no-cache");
 		header("Expires: 0");
 		readfile($this->getDownloadFullname()); 		
 	}
	
}

?>