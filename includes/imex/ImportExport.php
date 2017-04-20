<?php


require_once(__DIR__ . "/../eal/EAL_ItemSC.php");
require_once(__DIR__ . "/../eal/EAL_ItemMC.php");

abstract class ImportExport {
	
	
	abstract public static function export (array $itemids);
	
	abstract public static function import (array $file);
	
 	public static function download (array $itemids) {
 		
 		
 		$zip = Ilias::export($itemids);
 		
 		
 		header("Content-type: application/zip");
 		header("Content-Disposition: attachment; filename=" . $zip["short"] . ".zip");
 		header("Content-length: " . filesize($zip["full"]));
 		header("Pragma: no-cache");
 		header("Expires: 0");
 		readfile($zip["full"]); 		
 		
 		
 		
 	}
	
}

?>