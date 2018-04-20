<?php

require_once (__DIR__ . "/../anal/ItemExplorer.php");
require_once (__DIR__ . "/../eal/EAL_Item.php");
require_once (__DIR__ . "/../eal/EAL_ItemBasket.php");


class HTML_ItemBasket  {
	
	public static $rows;
	public static $values;
	
	
	public static function getHTML_Statistics (array $items) {
		
		
		$itemids = ItemExplorer::getItemIds($items);
		
		$labels = ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"];
		$res_Type = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Item Typ');
		foreach (ItemExplorer::groupBy($items, $itemids, 'type') as $key => $val) {
			$res_Type .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($val), $labels[$key]);
		}
		
		$res_Level = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Anforderungsstufe');
		foreach (ItemExplorer::groupBy($items, $itemids, 'level') as $key => $val) {
			$res_Level .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($val), EAL_Level::LABEL[$key]);
		}
		
		$res_Dim = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Wissensdimension');
		foreach (ItemExplorer::groupBy($items, $itemids, 'dim') as $key => $val) {
			$res_Dim .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td>%s</td></tr>', count($val), $key);
		}
		
		$res_LO = sprintf ('<tr><td colspan="2"><b>%s</b></td></tr>', 'Learning Outcome');
		foreach (ItemExplorer::groupBy($items, $itemids, 'lo') as $key => $val) {
			$res_LO .= sprintf ('<tr><td style="width:4em"><input type="text" value="%d" size="1" readonly /></td><td style="text-align:left">%s</td></tr>', count($val), $items[$val[0]]->getLearnOut()->getTitle());
		}
		
		
		$labels = array();
		foreach (get_terms (['taxonomy' => $items[$itemids[0]]->getDomain(), 'hide_empty' => false]) as $term) {
			$labels[$term->term_id] = $term->name;
		}
		
		$groupByTopic = [
			ItemExplorer::groupRecursive($items, $itemids, array ('topic1')),
			ItemExplorer::groupRecursive($items, $itemids, array ('topic1', 'topic2')),
			ItemExplorer::groupRecursive($items, $itemids, array ('topic1', 'topic2', 'topic3'))];
		
		
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
		
		$groupByTopic1 = ItemExplorer::groupBy($items, $itemids, 'topic1');
		$labels = array();
		foreach (get_terms (['taxonomy' => $items[$itemids[0]]->getDomain(), 'hide_empty' => false , 'include' => array_keys ($groupByTopic1)]) as $term) {
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
			$items[$item_id] = DB_Item::loadFromDB($item_id, $post->post_type);
		}
		
		
		$groupX = ItemExplorer::groupRecursive($items, $itemids, $dimX);
		self::$rows=[];
		self::$values=[];
		self::dimXHeader ($groupX, $dimX);
		
		
		
		$resX = '';
		$topleft = ((count($dimX)>0) && (count($dimY)>0)) ? sprintf ('<td style="background-color:#f9f9f9;" colspan="%d" rowspan="%d"></td>', count($dimY), count($dimX)) : '';
		for ($i=count($dimX)-1; $i>=0; $i--) {
			$resX .= sprintf ('<tr>%s%s</tr>', ($i==count($dimX)-1) ? $topleft : '', self::$rows[$i]);
		}
		
		
		$groupY = ItemExplorer::groupRecursive($items, $itemids, $dimY);
		
		
		
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
		$labels = ItemExplorer::getLabels($cat, array_keys ($group));
		
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
		
		$labels = ItemExplorer::getLabels($cat, array_keys ($group));
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
	
}
?>