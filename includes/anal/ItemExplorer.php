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
	
	
	public static function getItemTable (array $itemids = array()) {
		
		/* default: load items from basket (if not given as parameter) */
		if (count($itemids)==0) {
			$itemids = EAL_ItemBasket::get();
		}
		
		$result = array ();
		
		foreach ($itemids as $item_id) {

			/* load Item */
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$item = EAL_Item::load($post->post_type, $item_id);
			
			$row = array ();
			$row["type"] = $item->type;
			$row["level"] = $item->level;
			$row["lo"] = $item->learnout_id;
			$row["flag"] = $item->flag;
			
			$row["terms"] = array();
			$allterms = get_the_terms ($item_id, $item->domain);
			if (is_array ($allterms)) {
				foreach ($allterms as $term) {
					$term_list = get_ancestors($term->term_id, $item->domain);	// get the list of ancestor term_ids from lowest to highest
					array_unshift($term_list, $term->term_id);	// add the current term_id at the beginning (lowest)
					array_push ($row["terms"], array_slice (array_reverse ($term_list), 0, 2));		// get the (up to) two highest term ids
				}
			}
			
			$result[$item_id] = $row;
		}
		
		return $result;
	}
	
	

	
	
	
	
}

?>