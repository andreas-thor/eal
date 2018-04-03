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
	
	
	/**
	 * 
	 * @param array $itemids
	 * @param bool $updateMetadataOnly
	 * @return array of itemids that have been imported / updated
	 */
	public static function importItems (array $itemids, bool $updateMetadataOnly = FALSE ): array {
		
		global $item; 
		
		$result = array();
		foreach ($itemids as $itemid) {
			
			if ($updateMetadataOnly && ($itemid<0)) continue;	// must have itemid if "updateMetadataOnly"
			
			$prefix = "item_" . $itemid . "_";
			$status = NULL;
			switch (abs ($_REQUEST[$prefix."item_status"])) {
				case  1: $status = 'publish'; break;
				case  2: $status = 'pending'; break;
				case  3: $status = 'draft'; break;
			}
			
			if ($status == NULL) continue;	// must have status
			
			
			$item = EAL_Factory::createNewItem($_REQUEST[$prefix."post_type"], -1, $prefix);	// load item from POST data (because tempid<0)
			if ($updateMetadataOnly) {
				$item_post = $item;
				$item = EAL_Factory::createNewItem($_REQUEST[$prefix."post_type"], $itemid);
				$item->copyMetadata($item_post);
			}
			/**
			 *  In the mean time, a workaround worth trying would be:
			 
			 use wp_insert_post to create an initial post and get the the post ID
			 use wp_update_post to insert your post data
			 */
			
			$terms = $_POST[$prefix."taxonomy"];
			
			// store initial post & item
			if (($itemid<0) || ($_POST[$prefix."item_status"]<0)) {
				
				// import post/item
				$postarr = array ();
				$postarr['ID'] = 0;	// no EAL-ID
				$postarr['post_title'] = $item->getTitle();
				$postarr['post_status'] = $status;
				$postarr['post_type'] = $item->getType();
				$postarr['post_content'] = microtime();
				$postarr['tax_input'] = array ($item->getDomain() => $terms);
				$itemid = wp_insert_post ($postarr);
			}
			
			// update post (also necessary for initial import to have first revision version)
			$post = get_post ($itemid);
			$post->post_title = $item->getTitle();
			$post->post_status = $status;
			$post->post_content = microtime();	// ensures revision
			wp_set_post_terms($itemid, $terms, $item->getDomain(), FALSE );
			wp_update_post ($post);
			
			
			array_push ($result, $itemid);
		}
		return $result;
		
		
	}
	
}

?>