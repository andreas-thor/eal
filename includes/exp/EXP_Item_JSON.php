<?php

require_once 'EXP_Item.php';

class EXP_Item_JSON extends EXP_Item {
	
	private $media = array ();
	private $xml_MTImages = array();
	
	
	public function __construct() {
		parent::__construct (time() . '_items', 'json' );
	}
	
	protected function generateExportFile (array $itemids) {
	
		
		$result = array ();
		foreach ($itemids as $item_id) {
			
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$item = DB_Item::loadFromDB($item_id, $post->post_type);
			
			if ($item instanceof EAL_Item) {
				$result[$item->getId()] = $item->convertToArray('', 'item_level_');
			}
		}
		
		file_put_contents($this->getDownloadFullname(), json_encode($result));
		
	}
	

	
	
	/**
	 * {@inheritDoc}
	 * @see EXP_Item::processImage()
	 */
	protected function processImage(string $src): string {
		
		// do not change
		return $src;
		
		/*
		// The image is included into the json file via data:image 
		$extension = substr ($src, -3);
		return 'data:image/' . $extension . ';base64,' . base64_encode(file_get_contents($src));
		*/
	}

	
	
}