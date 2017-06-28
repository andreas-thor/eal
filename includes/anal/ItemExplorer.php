<?php


class ItemExplorer {
	
	
	public static $rows;
	public static $values;
	
	
	/**
	 * 
	 * @param array $items
	 * @param array $itemids
	 * @param string $cat 'type', 'level', 'dim', 'lo', or 'topic1'
	 * @return [key => [item_ids]]
	 */
	public static function groupBy (array $items, array $itemids, string $cat, $parentKey = -1) {
		
		$result = array(); 	// key => array (itemids)
		
		if ($cat == 'number') {
			$result = ['all' => array()];
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				array_push ($result['all'], $item_id);
			}
			return $result;
		}
		
		
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
						if (!in_array($item_id, $result[$item->level[$dim]])) {
							array_push ($result[$item->level[$dim]], $item_id);
						}
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
	
		if (substr( $cat, 0, 5 ) === "topic") {

			$level = substr( $cat, 5, 1 );
			$result = array();
			foreach ($itemids as $item_id) {
				$item = $items[$item_id];
				$terms = get_the_terms ($item_id, $item->domain);
				if (is_array($terms)) {
					foreach ($terms as $term) {
						
						$term_list = get_ancestors($term->term_id, $item->domain);	// get the list of ancestor term_ids from lowest to highest
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
		
		return $result;
		
	}
	
	
	public static function getHTML_Type (array $items) {
	
	
		$itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		$labels = ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"];
		$res_Type = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Item Typ');
		foreach (self::groupBy($items, $itemids, 'type') as $key => $val) {
			$res_Type .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($val), $labels[$key]);
		}
	
		$res_Level = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Anforderungsstufe');
		foreach (self::groupBy($items, $itemids, 'level') as $key => $val) {
			$res_Level .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($val), EAL_Item::$level_label[$key-1]);
		}
		
		$res_Dim = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Wissensdimension');
		foreach (self::groupBy($items, $itemids, 'dim') as $key => $val) {
			$res_Dim .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($val), $key);
		}
		
		$res_LO = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Learning Outcome');
		foreach (self::groupBy($items, $itemids, 'lo') as $key => $val) {
			$res_LO .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td style="text-align:left">%s</td></tr>', count($val), $items[$val[0]]->getLearnOut()->title);
		}
		
		
		$labels = array();
		foreach (get_terms (['taxonomy' => $items[$itemids[0]]->domain, 'hide_empty' => false]) as $term) {
			$labels[$term->term_id] = $term->name;
		}
		
		$groupByTopic = [ 
			self::groupRecursive($items, $itemids, array ('topic1')),
			self::groupRecursive($items, $itemids, array ('topic1', 'topic2')),
			self::groupRecursive($items, $itemids, array ('topic1', 'topic2', 'topic3'))];
		
		
		$res_Topic = sprintf ('<tr><td colspan="4"><b>%s</b></td></tr>', 'Topic');
		foreach ($groupByTopic[0] as $topic1 => $group1) {
			$res_Topic .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td colspan="3">%s</td></tr>', count($group1), $labels[$topic1]);
			foreach ($groupByTopic[1][$topic1] as $topic2 => $group2) {
				$res_Topic .= sprintf ('<tr><td></td><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td colspan="2">%s</td></tr>', count($group2), $labels[$topic2]);
				foreach ($groupByTopic[2][$topic1][$topic2] as $topic3 => $group3) {
					$res_Topic .= sprintf ('<tr><td></td><td></td><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($group3), $labels[$topic3]);
				}
			}
		}
		
		$groupByTopic1 = self::groupBy($items, $itemids, 'topic1');
		$labels = array();
		foreach (get_terms (['taxonomy' => $items[$itemids[0]]->domain, 'hide_empty' => false , 'include' => array_keys ($groupByTopic1)]) as $term) {
			$labels[$term->term_id] = $term->name;
		}
		$res_Topic1 = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Topic 1');
		foreach ($groupByTopic1 as $key => $val) {
			$res_Topic1 .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($val), $labels[$key]);
		}
		
		

		
		return sprintf ('
				
				<div class="meta-box-sortables ">	
			<div class="postbox closed">
				<button type="button" class="handlediv button-link" aria-expanded="false" 
					onclick="
					this.parentElement.setAttribute (\'class\', this.getAttribute (\'aria-expanded\') == \'true\' ? \'postbox closed\' : \'postbox\');
					this.setAttribute (\'aria-expanded\', this.getAttribute (\'aria-expanded\') == \'true\' ? \'false\' : \'true\'); 
				"><span class="screen-reader-text">Toggle panel: Items</span><span class="toggle-indicator" aria-hidden="true"></span></button>
				<h2 class="hndle ui-sortable-handle"><span>%d Items</span></h2>
				<div class="inside">
					<div style="margin-right:5em; "><table style="font-size:100%%">%s</table></div>
					<div style="margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
					<div style="margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
					<div style="margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
					<div style="margin-right:5em; " ><table style="font-size:100%%">%s</table></div>
					<div class="clear"></div>
				</div>
			</div>
			</div>
			', count($items), $res_Type, $res_Level, $res_Dim, $res_LO, $res_Topic);
	
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
		
		
		$groupX = self::groupRecursive($items, $itemids, $dimX);
		self::$rows=[];
		self::$values=[];
		self::dimXHeader ($groupX, $dimX);
		
		
		
		$resX = '';
		$topleft = ((count($dimX)>0) && (count($dimY)>0)) ? sprintf ('<td style="background-color:#f9f9f9;" colspan="%d" rowspan="%d"></td>', count($dimY), count($dimX)) : '';
		for ($i=count($dimX)-1; $i>=0; $i--) {
			$resX .= sprintf ('<tr>%s%s</tr>', ($i==count($dimX)-1) ? $topleft : '', self::$rows[$i]);
		}
		
		
 		$groupY = self::groupRecursive($items, $itemids, $dimY);
		
		
 												
//  		return sprintf ('<div style="margin-right:1em"><table border="0" ><td colspan="%d" rowspan="%d"></td>%s%s</table></div>', count($dimY), count($dimX)+1, $resX, self::dimYHeader ($groupY, $dimY));
 		return sprintf ('%s%s', $resX, self::dimYHeader ($groupY, $dimY));
	}
	
	
	private static function dimXHeader (array $group, array $allcat) {
	
		if (count($allcat)==0) {
			array_push (self::$values, $group);
			return;
		}
		$idx = count($allcat)-1;
		
		if (!isset(self::$rows[$idx])) self::$rows[$idx] = '';
		$cat = array_shift($allcat);
		$labels = self::getLabels($cat, array_keys ($group));
	
		foreach ($group as $key => $val) {
			self::$rows[$idx] .= sprintf ('<td style="background-color:#f9f9f9;" colspan="%d">%s</td>', self::countLeafs($val, $allcat, 0), $labels[$key]);
			self::dimXHeader($val, $allcat);
		}
	}
	
	
	private static function dimYHeader (array $group, array $allcat) {
	
		if (count($allcat)==0) {
			$res = '';
			foreach (self::$values as $xgroup) {
				$int = array_intersect($group, $xgroup);
				$res .= count($int)>0 ? sprintf ('<td><a href="admin.php?page=view_item&itemids=%s">%d</a></td>', implode (',', $int), count ($int)) : '<td></td>';				
			}
			return $res;
			
		}
	
		$cat = array_shift($allcat);
		
		$labels = self::getLabels($cat, array_keys ($group));
		$res = '';
		foreach ($group as $key => $val) {
			$res .= sprintf ('<tr><td style="background-color:#f9f9f9;" rowspan="%d">%s</td>%s</tr>', self::countLeafs($val, $allcat, 1), $labels[$key], self::dimYHeader($val, $allcat));
		}
		return $res;
	
	}	
	

	
	
	private static function countLeafs (array $group, array $allcat, $init) {
		if (count($allcat)==0) return 1;
		$cat = array_shift($allcat);
		$res =  $init;
		foreach ($group as $key => $val) {
			$res += self::countLeafs($val, $allcat, $init);
		}
		return $res;
	}

	
	
	/**
	 * 
	 * @param array $items
	 * @param array $itemids
	 * @param array $allcat
	 * @return [valcat1 => [ valcat2 => [ ... valcatn => [itemids]]]]
	 */
	
	private static function groupRecursive (array $items, array $itemids, array $allcat, $parentKey = -1) {
		
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
				if (in_array($lo->id, $keys)) {
					$labels[$lo->id] = $lo->title;
				}
			}
			natcasesort ($labels);
			return $labels;
		}
		
		switch ($cat) {
			case 'type': 	return ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"];
			case 'level': 	return EAL_Item::$category_value_label["level"]; // array_merge ([""], EAL_Item::$level_label); // add empty value for index=0 because labels are enumerated 1..6
			case 'dim': 	return ["FW"=>"FW", "KW"=>"KW", "PW"=>"PW"];
		}
		return [];
	}
	

	
}

?>