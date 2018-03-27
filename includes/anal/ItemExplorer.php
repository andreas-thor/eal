<?php


class ItemExplorer {
	
	
	private static function getItem (array $items, int $itemid) : EAL_Item {
		return $items[$itemid];
	}
	
	
	/**
	 * 
	 * @param array $items
	 * @param array $itemids
	 * @param string $cat 'number', 'type', 'level', 'dim', 'lo', or 'topic1'
	 * @return array [key => [item_ids]]
	 */
	public static function groupBy (array $items, array $itemids, string $cat, $parentKey = -1): array {
		
		$result = array(); 	// key => array (itemids)
		
		// group all itemids in a single group
		if ($cat == 'number') {		
			$result = ['all' => array()];
			foreach ($itemids as $item_id) {
				array_push ($result['all'], $item_id);
			}
			return $result;
		}
		
		// group by item type (single choice, multiple choice)
		if ($cat == 'type') {	
			$result = ['itemsc' => array(), 'itemmc' => array()];
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				array_push ($result[$item->getType()], $item_id);
			}
			return $result;
		}
		
		// group by level 		
		if ($cat == 'level') {
			$result = [1 => array(), 2 => array(), 3 => array(), 4 => array(), 5 => array(), 6 => array()];
			foreach ($itemids as $item_id) {
				$item = self::getItem($items, $item_id);
				foreach (EAL_Level::TYPE as $dim) {
					if ($item->getLevel()->get($dim) > 0) {
						if (!in_array($item_id, $result[$item->getLevel()->get($dim)])) {
							array_push ($result[$item->getLevel()->get($dim)], $item_id);
						}
					}
				}
			}
			return $result;
		}
		
		// group by dimension  
		if ($cat == 'dim') {
			foreach (EAL_Level::TYPE as $dim) {
				$result [$dim] = array();
			}
			foreach ($itemids as $item_id) {
				$item = self::getItem($items, $item_id);
				foreach (EAL_Level::TYPE as $dim) {
					if ($item->getLevel()->get($dim) > 0) {
						array_push ($result[$dim], $item_id);
					}
				}
			}
			return $result;
		}
		
		// group by learning outcome
		if ($cat == 'lo') {
			$result = array();
			foreach ($itemids as $item_id) {
				$item = self::getItem($items, $item_id);
				if ($item->getLearnOutId() > 0) {
					if (!isset ($result[$item->getLearnOutId()])) $result[$item->getLearnOutId()] = array();
					array_push ($result[$item->getLearnOutId()], $item_id);
				}
			}
			return $result;
		}
	
		
		// group by topic
		if (substr( $cat, 0, 5 ) === "topic") {

			$level = substr( $cat, 5, 1 );
			$result = array();
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				$terms = get_the_terms ($item_id, $item->getDomain());
				if (is_array($terms)) {
					foreach ($terms as $term) {
						
						$term_list = get_ancestors($term->term_id, $item->getDomain());	// get the list of ancestor term_ids from lowest to highest
						array_unshift($term_list, $term->term_id);	// add the current term_id at the beginning (lowest)
						
						if (count($term_list)>=$level) {
						
							if (($level>1) && ($parentKey != -1)) {
								if ($term_list[count($term_list)-$level+1] != $parentKey) continue;
							}
							
							
							for ($i=0; $i<$level; $i++) {
								$top1 = array_pop($term_list);	// root topic term (if $level==1); second level if $level==2 etc.
							}
							
							if (!isset ($result[$top1])) $result[$top1] = array();
							if (!in_array($item_id, $result[$top1])) {
								array_push ($result[$top1], $item_id);
							}
						}
					}
				}
			
			}
			return $result;
		}
		
		
		return $result;	// unknown grouping category --> return empty array 
		
	}
	
	
		
	
	

	
	
	/**
	 * 
	 * @param array $items
	 * @param array $itemids
	 * @param array $allcat
	 * @return array [valcat1 => [ valcat2 => [ ... valcatn => [itemids]]]]
	 */
	
	public static function groupRecursive (array $items, array $itemids, array $allcat, $parentKey = -1) {
		
		if (count($allcat)==0) return $itemids;
		
		$cat = array_shift($allcat);
		$group = self::groupBy ($items, $itemids, $cat, $parentKey);
		
		$res = array();
		$makeParent = substr($cat, 0, 5) == "topic";
		foreach ($group as $key => $val) {
			if (count($val)>0) {
				$res[$key] = self::groupRecursive($items, $val, $allcat, $makeParent ? $key : -1);
			}
		}
		return $res;
	}
	
	
	
	public static function getLabels (string $cat, array $keys = array()) {
		
		if (substr( $cat, 0, 5 ) === "topic") {
			$labels = [];
			foreach (get_terms (['taxonomy' => RoleTaxonomy::getCurrentRoleDomain()['name'], 'hide_empty' => false, 'include' => $keys]) as $term) {
				$labels[$term->term_id] = $term->name;
			}
			natcasesort ($labels);
			return $labels;
		}

		if ($cat == "lo") {
			$labels = [];
			foreach (EAL_LearnOut::getListOfLearningOutcomes() as $pos => $lo) {
				if (in_array($lo['id'], $keys)) {
					$labels[$lo['id']] = $lo['title'];
				}
			}
			natcasesort ($labels);
			return $labels;
		}
		
		switch ($cat) {
			case 'type': 	return ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"];
			case 'level': 	return EAL_Level::LABEL; // EAL_Item::$category_value_label["level"]; // array_merge ([""], EAL_Item::$level_label); // add empty value for index=0 because labels are enumerated 1..6
			case 'dim': 	return ["FW"=>"FW", "KW"=>"KW", "PW"=>"PW"];
		}
		return [];
	}
	
	
	
	public static function getItemIds (array $items) {
		
		$result = array();
		foreach ($items as $item) {
			if ($item instanceof EAL_Item) {
				$result[] = $item->getId();
			}
		}
		
		return $result;
		
	}
	
	
	public static function getItemIdsByRequest ():array {
		
		if ($_REQUEST['itemid'] != null) {
			return [$_REQUEST['itemid']];
		}
		
		if ($_REQUEST['itemids'] == null) {
			return [];
		}
		
		if (is_array($_REQUEST['itemids'])) {
			return $_REQUEST['itemids'];
		}
		
		if (is_string($_REQUEST['itemids'])) {
			return explode (",", $_REQUEST["itemids"]);
		}
		
	}
	

	
}

?>