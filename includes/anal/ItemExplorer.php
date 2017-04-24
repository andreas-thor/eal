<?php


class ItemExplorer {
	
	/**
	 * 
	 * @param array $items
	 * @param array $itemids
	 * @param string $cat 'type', 'level', 'dim', 'lo', or 'topic1'
	 * @return [key => [item_ids]]
	 */
	public static function groupBy (array $items, array $itemids, string $cat) {
		
		$result = array(); 	// key => array (itemids)
		
		if ($cat == 'type') {
			$result = ['itemsc' => array(), 'itemmc' => array()];
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				array_push ($result[$item->type], $item_id);
			}
			return $result;
		}
		
		
		if ($cat == 'level') {
			$result = [1 => array(), 2 => array(), 3 => array(), 4 => array(), 5 => array(), 6 => array()];
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				foreach (EAL_Item::$level_type as $dim) {
					if ($item->level[$dim] > 0) {
						array_push ($result[$item->level[$dim]], $item_id);
					}
				}
			}
			return $result;
		}
		
		
		if ($cat == 'dim') {
			foreach (EAL_Item::$level_type as $dim) $result [$dim] = array();
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				foreach (EAL_Item::$level_type as $dim) {
					if ($item->level[$dim] > 0) {
						array_push ($result[$dim], $item_id);
					}
				}
			}
			return $result;
		}
		
		if ($cat == 'lo') {
			$result = array();
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				if ($item->learnout_id > 0) {
					if (!isset ($result[$item->learnout_id])) $result[$item->learnout_id] = array();
					array_push ($result[$item->learnout_id], $item_id);
				}
			}
			return $result;
		}
	
		if ($cat == 'topic1') {
			$result = array();
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				$terms = get_the_terms ($item_id, $item->domain);
				if (is_array($terms)) {
					foreach ($terms as $term) {
						
						$term_list = get_ancestors($term->term_id, $item->domain);	// get the list of ancestor term_ids from lowest to highest
						array_unshift($term_list, $term->term_id);	// add the current term_id at the beginning (lowest)
						$top1 = array_pop($term_list);	// root topic term					
						
						if (!isset ($result[$top1])) $result[$top1] = array();
						if (!in_array($item_id, $result[$top1])) {
							array_push ($result[$top1], $item_id);
						}
					}
				}
			
			}
			return $result;
		}
		
		return $result;
		
	}
	
	
	public static function getHTML_Type (array $items) {
	
	
		$itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		$labels = ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"];
		$res_Type = sprintf ('<tr><td colspan="2"><b>%s</b></td><tr>', 'Item Typ');
		foreach (self::groupBy($items, $itemids, 'type') as $key => $val) {
			$res_Type .= sprintf ('<tr><td>%s</td><td ><input type="text" value="%d" size="1" readonly /></td><tr>', $labels[$key], count($val));
		}
	
		$res_Level = sprintf ('<tr><td colspan="2"><b>%s</b></td><tr>', 'Anforderungsstufe');
		foreach (self::groupBy($items, $itemids, 'level') as $key => $val) {
			$res_Level .= sprintf ('<tr><td>%s</td><td ><input type="text" value="%d" size="1" readonly /></td><tr>', EAL_Item::$level_label[$key-1], count($val));
		}
		
		$res_Dim = sprintf ('<tr><td colspan="2"><b>%s</b></td><tr>', 'Wissensdimension');
		foreach (self::groupBy($items, $itemids, 'dim') as $key => $val) {
			$res_Dim .= sprintf ('<tr><td>%s</td><td ><input type="text" value="%d" size="1" readonly /></td><tr>', $key, count($val));
		}
		
		$res_LO = sprintf ('<tr><td colspan="2"><b>%s</b></td><tr>', 'Learning Outcome');
		foreach (self::groupBy($items, $itemids, 'lo') as $key => $val) {
			$res_LO .= sprintf ('<tr><td>%s</td><td ><input type="text" value="%d" size="1" readonly /></td><tr>', $items[$val[0]]->getLearnOut()->title, count($val));
		}
		
		$groupByTopic1 = self::groupBy($items, $itemids, 'topic1');
		$labels = array();
		foreach (get_terms (['taxonomy' => $items[$itemids[0]]->domain, 'include' => array_keys ($groupByTopic1)]) as $term) {
			$labels[$term->term_id] = $term->name;
		}
		$res_Topic1 = sprintf ('<tr><td colspan="2"><b>%s</b></td><tr>', 'Topic 1');
		foreach ($groupByTopic1 as $key => $val) {
			$res_Topic1 .= sprintf ('<tr><td>%s</td><td ><input type="text" value="%d" size="1" readonly /></td><tr>', $labels[$key], count($val));
		}
		
		return sprintf ('
			<div class="postbox ">
				<h2 class="hndle"><span>%d Items</span></h2>
				<div class="inside">
					<div style="float: left; margin-right:5em; "><table style="font-size:100%%">%s</table></div>
					<div style="float: left; margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
					<div style="float: left; margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
					<div style="float: left; margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
					<div style="float: left; margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
				<div class="clear"></div>
				</div>
			</div>', count($items), $res_Type, $res_Level, $res_Dim, $res_LO, $res_Topic1);
	
	}	
	
	
	public static function getHTML_CrossTable (array $itemids = array(), array $dimX, array $dimY) {
		
		/* default: load items from basket (if not given as parameter) */
		if (count($itemids)==0) {
			$itemids = EAL_ItemBasket::get();
		}
		
		/* load all items */
		$items = array ();
		foreach ($itemids as $item_id) {
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$items[$item_id] = EAL_Item::load($post->post_type, $item_id);
		}
		
		
		$groupY = self::groupRecursive($items, $itemids, $dimY);
		
		$dimPos = 0;
		
		
		
		
		
		
		return sprintf ('<table border="1">%s</table>', self::lineRecursive ($groupY, $dimY));
	}
	
	
	private static function lineRecursive (array $group, array $allcat) {
	
		if (count($allcat)==0) return sprintf ('<td>%d</td>', count($group));
	
		$cat = array_shift($allcat);
		
		$labels = [];
		switch ($cat) {
			case 'type': 	$labels = ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"]; break;
			case 'level': 	$labels = array_merge ([""], EAL_Item::$level_label); break;
			case 'dim': 	$labels = ["FW"=>"FW", "KW"=>"KW", "PW"=>"PW"]; break;
			
			case 'topic1':	foreach (get_terms (['taxonomy' => RoleTaxonomy::getCurrentRoleDomain()['name'], 'include' => array_keys ($group)]) as $term) {
								$labels[$term->term_id] = $term->name; 
							}
							break;
		}
		
		
		
		$res = '';
		foreach ($group as $key => $val) {
			
			
			
			
			$res .= sprintf ('<tr><td rowspan="%d">%s</td>%s</tr>', self::countLeafs($val, $allcat), $labels[$key], self::lineRecursive($val, $allcat));
		}
		return $res;
	
	}	
	
	
	private static function countLeafs (array $group, array $allcat) {
		if (count($allcat)==0) return 1;
		$cat = array_shift($allcat);
		$res = 1;
		foreach ($group as $key => $val) {
			$res += self::countLeafs($val, $allcat);
		}
		return $res;
	}

	private static function groupRecursive (array $items, array $itemids, array $allcat) {
		
		if (count($allcat)==0) return $itemids;
		
		$cat = array_shift($allcat);
		$group = self::groupBy ($items, $itemids, $cat);
		
		$res = array();
		foreach ($group as $key => $val) {
			if (count($val)>0) {
				$res[$key] = self::groupRecursive($items, $val, $allcat);
			}
		}
		return $res;
	}
	
	

	
}

?>