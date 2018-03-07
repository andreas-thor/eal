<?php


require_once('IMEX_Object.php');

abstract class IMEX_Item extends IMEX_Object {
	
	
	const DESCRIPTION_QUESTION_SEPARATOR = '<!-- EAL --><hr/>';
	
	abstract protected function generateExportFile (array $itemids);
	
 	/**
 	 * callback function that is called for every <img>-element in the description and question
 	 * should set some 
 	 * @param string $src <img src="..."> attribute value
 	 * @return string replacement for scr 
 	 */
	
	abstract protected function processImage (string $src): string;	
	
	
	protected function processAllImages (string $html): string {
		
		return preg_replace_callback(				
			'|(<img[^>]+)src="([^"]*)"|',	// find all <img> elements
			function ($match) {
				
				// if img is stored inline (src="data:image/png;base64,iVBOR....") --> do nothing; otherwise call callback function
				$src = (strtolower (substr($match[2], 0, 5)) == 'data:') ? $match[2] : $this->processImage($match[2]);
				return 	$match[1] . 'src="' . $src . '"';
			},
			$html
			);		
		
	}
	
	
	public function downloadItems (array $itemids) {
		$this->generateExportFile($itemids);
		$this->download();
	}
	
	
	/**
	 * @param array $file uploaded file ['name' => orginal name, 'tmp_name' => uploaded file name]
	 * @return array of EAL_Item
	 * @throws Exception
	 */
	abstract public function parseItemsFromImportFile (array $file): array;
	
}

?>