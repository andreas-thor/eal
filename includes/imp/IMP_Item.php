<?php

require_once __DIR__ . '/../exp/EXP_Item.php';

abstract class IMP_Item {
	
	
	
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
		foreach ($itemids as $item_id) {
			
			if ($updateMetadataOnly && ($item_id<0)) continue;	// must have itemid if "updateMetadataOnly"
			
			$prefix = "item_" . $item_id . "_";
			$status = NULL;
			switch (abs ($_REQUEST[$prefix."item_status"])) {
				case  1: $status = 'publish'; break;
				case  2: $status = 'pending'; break;
				case  3: $status = 'draft'; break;
				default: continue; // must have status
			}
			
			
			
			$item = EAL_Item::createByTypeFromArray($item_id, $_REQUEST[$prefix."post_type"], $_POST, $prefix);
			if ($updateMetadataOnly) {
				$item_post = $item;
				$item = DB_Item::loadFromDB($item_id, $_REQUEST[$prefix."post_type"]);
				$item->copyMetadata($item_post);
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
				$postarr['post_title'] = $item->getTitle();
				$postarr['post_status'] = $status;
				$postarr['post_type'] = $item->getType();
				$postarr['post_content'] = microtime();
				$postarr['tax_input'] = array ($item->getDomain() => $terms);
				$item_id = wp_insert_post ($postarr);
			}
			
			// update post (also necessary for initial import to have first revision version)
			$post = get_post ($item_id);
			$post->post_title = $item->getTitle();
			$post->post_status = $status;
			$post->post_content = microtime();	// ensures revision
			wp_set_post_terms($item_id, $terms, $item->getDomain(), FALSE );
			wp_update_post ($post);
			
			
			array_push ($result, $item_id);
		}
		return $result;
		
		
	}
	
}

?>