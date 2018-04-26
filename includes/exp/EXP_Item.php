<?php


require_once 'EXP_Object.php';

abstract class EXP_Item extends EXP_Object {
	
	
	const DESCRIPTION_QUESTION_SEPARATOR = '<!-- EAL --><hr/>';
	
	public function downloadItems (array $itemids) {
		$this->generateExportFile($itemids);
		$this->download();
	}
	
	abstract protected function generateExportFile (array $itemids);
	
	
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
	
	/**
	 * callback function that is called for every <img>-element in the description and question
	 * should set some data that can later be used during export
	 * @param string $src <img src="..."> attribute value
	 * @return string replacement for scr
	 */
	
	abstract protected function processImage (string $src): string;
	
	

	
}

?>