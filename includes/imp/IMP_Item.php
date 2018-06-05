<?php

require_once __DIR__ . '/../exp/EXP_Item.php';

abstract class IMP_Item {
	
	
	
	/**
	 * @param array $file uploaded file ['name' => orginal name, 'tmp_name' => uploaded file name]
	 * @return array of EAL_Item
	 * @throws Exception
	 */
	abstract public function parseItemsFromImportFile (array $file): array;
	
	
	abstract public function getTestData (): string;
		
	
	/**
	 * 
	 * @param array $itemids
	 * @param bool $updateMetadataOnly
	 * @return array map of itemids that have been imported / updated; [new Item Id => old Item Id]
	 */
	public static function importItems (array $itemids, bool $updateMetadataOnly = FALSE ): array {
		
		global $itemToImport; 
		
		$result = array();
		foreach ($itemids as $item_id) {
			
			$old_itemid = $item_id;
			if ($updateMetadataOnly && ($item_id<0)) continue;	// must have itemid if "updateMetadataOnly"
			
			$prefix = "item_" . $item_id . "_";
			$status = NULL;
			switch (abs ($_REQUEST[$prefix."item_status"])) {
				case  1: $status = 'publish'; break;
				case  2: $status = 'pending'; break;
				case  3: $status = 'draft'; break;
			}
			
			if ($status == NULL) continue; // must have status
			
			
			$itemToImport = EAL_Item::createByTypeFromArray($item_id, $_REQUEST[$prefix."post_type"], $_POST, $prefix);
			if ($updateMetadataOnly) {
				$item_post = $itemToImport;
				$itemToImport = DB_Item::loadFromDB($item_id, $_REQUEST[$prefix."post_type"]);
				$itemToImport->copyMetadata($item_post);
			}
			/**
			 *  In the mean time, a workaround worth trying would be:
			 
			 use wp_insert_post to create an initial post and get the the post ID
			 use wp_update_post to insert your post data
			 */
			
			$terms = $_POST[$prefix."taxonomy"];
			
			// store initial post & item
			if (($item_id<0) || ($_POST[$prefix."item_status"]<0)) {
				
				// import post/item
				$postarr = array ();
				$postarr['ID'] = 0;	// no EAL-ID
				$postarr['post_title'] = $itemToImport->getTitle();
				$postarr['post_status'] = $status;
				$postarr['post_type'] = $itemToImport->getType();
				$postarr['post_content'] = microtime();
				$postarr['tax_input'] = array ($itemToImport->getDomain() => $terms);
				$item_id = wp_insert_post ($postarr);
			}
			
			// update post (also necessary for initial import to have first revision version)
			$itemToImport->setId($item_id);
			$post = get_post ($item_id);
			$post->post_title = $itemToImport->getTitle();
			$post->post_status = $status;
			$post->post_content = microtime();	// ensures revision
			wp_set_post_terms($item_id, $terms, $itemToImport->getDomain(), FALSE );
			wp_update_post ($post);
			
			
			$result[$item_id] = $old_itemid;
		}
		return $result;
		
		
	}
	
}

?>