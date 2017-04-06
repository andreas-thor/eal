<?php


abstract class ImportExport {
	
	
	abstract public static function export (array $itemids);
	
	abstract public static function import (array $file);
	
// 	public static function upload 
	
}

?>