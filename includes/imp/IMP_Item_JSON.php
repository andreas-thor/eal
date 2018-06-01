<?php

require_once 'IMP_Item.php';
require_once __DIR__ . '/../exp/EXP_Item.php';

class IMP_Item_JSON extends IMP_Item {
	
	
	
	/**
	 * 
	 * @param array $file
	 * @throws Exception
	 * @return array of EAL_Item
	 */
	public function parseItemsFromImportFile (array $file): array {
		
		
		$content = file_get_contents($file['tmp_name']);
		if ($content === FALSE) {
			return [];	// FIXME: error while reading file
		}
		
		$itemsObject = json_decode($content, TRUE);
	
		$result = [];
		foreach ($itemsObject as $id => $object) {
			$result[$id] = EAL_Item::createByTypeFromArray($id, $object['post_type'], $object);
		}
		
		return $result;
	}
	

	
	
}