<?php 

require_once ("class.EAL_Item.php");
require_once ("class.PAG_Basket.php");
require_once ("class.PAG_Explorer.php");

class PAG_Generator {

	
	
	private $items;
	private $item_vectors;
	
	// each entry represents ONE specific category value
	private $MASK;
	private $min;
	private $max;
	
	private $maxPools;
	private $countPools;
	
	public $generatedPools;
	
	
	/**
	 *
	 * @param unknown $items
	 * @param unknown $min array of min values in order: (overall number, itemsc, itemmc, FW, KW, PW)
	 * @param unknown $max array of max values (see above)
	 */
	function __construct($items, $min, $max) {
	
		$this->maxPools = 10;
		$this->countPools = 0;
		$this->generatedPools = array ();
	
		$this->items = array_values ($items);	// make sure items are indexed for 0 to n-1
			
		// generate MASKs
		$this->MASK = array();
		for ($i=0; $i<count($min); $i++) {
			array_push ($this->MASK, 1 << $i);
		}
	
		$this->item_vectors = array();
		$remaining = array();
		$size = count($this->items);
		foreach ($this->items as $idx => $item) {
			array_push ($remaining, $idx);	// reverse order since we pop later
			$vector = 1;
			if ($item->type == "itemsc") $vector += $this->MASK[1];
			if ($item->type == "itemmc") $vector += $this->MASK[2];
			if ($item->level["FW"] > 0) $vector += $this->MASK[3];
			if ($item->level["KW"] > 0) $vector += $this->MASK[4];
			if ($item->level["PW"] > 0) $vector += $this->MASK[5];
			array_push($this->item_vectors, $vector);
		}
	
		$values_current = array();
		$values_remaining = array();
		for ($idx=0; $idx<count($min); $idx++) {
			array_push ($values_current, 0);
			$val_remaining = 0;
			foreach ($remaining as $item) {
				if (($this->item_vectors[$item] & $this->MASK[$idx]) > 0) $val_remaining++;
			}
			array_push($values_remaining, $val_remaining);
		}
	
		$this->min = $min;
		$this->max = $max;
	
		$this->addOneItem (array(), $remaining, $values_current, $values_remaining);
	
	}
	
	
	public function addOneItem ($current, $remaining, $values_current, $values_remaining) {
	
		if ($this->countPools >= $this->maxPools) return;
	
		/* check conditions */
		$allTrue = TRUE;
	
		for ($idx=0; $idx<count($this->MASK); $idx++) {
				
				
				
				
			// 			// count occurences for constraint idx
			// 			$val_current = 0;
			// 			foreach ($current as $item) {
			// 				if (($this->item_vectors[$item] & $this->MASK[$idx]) > 0) $val_current++;
			// 			}
				
			if ($this->max[$idx] < $values_current[$idx]) {
				return;	// greater than max
			}
				
			// 			$val_remaining = 0;
			// 			foreach ($remaining as $item) {
			// 				if (($this->item_vectors[$item] & $this->MASK[$idx]) > 0) $val_remaining++;
			// 			}
	
			if ($this->min[$idx] > $values_current[$idx]) { 	// minimum currently not yet reached
				if ($this->min[$idx] > ($values_current[$idx]+$values_remaining[$idx]) ) {
					return; 	// minimum can not be reached
				}
				$allTrue = FALSE;
			}
	
		}
	
		if ($allTrue) {
			$this->countPools++;
				
			$newPool = array();
			foreach ($current as $item_pos) {
				array_push ($newPool, $this->items[$item_pos]->id);
			}
			array_push ($this->generatedPools, $newPool);
			// 			print_r($current);
			if ($this->countPools >= $this->maxPools) return;
		}
	
	
		while (count($remaining)>0) {
				
			// determin the best idx for next
			$bestIdx = 0;
			$bestBenefit = -1;
			for ($idx=0; $idx<count($this->MASK); $idx++) {
				if (($values_remaining[$idx]>0) && ($this->min[$idx] > $values_current[$idx])) {
					if ($bestBenefit < ($this->min[$idx] - $values_current[$idx])/$values_remaining[$idx]) {
						$bestBenefit = ($this->min[$idx] - $values_current[$idx])/$values_remaining[$idx];
						$bestIdx = $idx;
					}
				}
			}
				
			// determine first item with set idx
			foreach ($remaining as $key => $value) {
				if (($this->item_vectors[$value] & $this->MASK[$bestIdx]) > 0) {
					$toRemove = $key;
					break;
				}
			}
			$add = $remaining[$toRemove];
			unset($remaining[$toRemove]);
				
				
			$remaining_new = (new ArrayObject($remaining))->getArrayCopy();
			$current_new = (new ArrayObject($current))->getArrayCopy();
				
			array_push($current_new, $add);
				
			$values_current_new = array();
			$values_remaining_new = array();
				
			for ($idx=0; $idx<count($this->MASK); $idx++) {
	
				if (($this->item_vectors[$add] & $this->MASK[$idx]) > 0) {
					array_push ($values_current_new, $values_current[$idx]+1);
					array_push ($values_remaining_new, $values_remaining[$idx]-1);
					$values_remaining[$idx] = $values_remaining[$idx]-1;
				} else {
					array_push ($values_current_new, $values_current[$idx]);
					array_push ($values_remaining_new, $values_remaining[$idx]);
				}
			}
			$this->addOneItem($current_new, $remaining_new, $values_current_new, $values_remaining_new);
		}
	
	
	
	
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public static function createPage () {
	
		
		$criteria = array (
				
			"item_type" => array (
				"sc" => "Single Choice",	
				"mc" => "Multiple Choice"	
			),	
			"dimension" => array (
					"KW" => "KW",
					"FW" => "FW",
					"PW" => "PW",
			)
				
		);
			
	
		
		$items = PAG_Basket::loadAllItemsFromBasket ();

		// Number of Items (min, max)
		$_SESSION['min_number'] = isset($_REQUEST['min_number']) ? $_REQUEST['min_number'] : (isset($_SESSION['min_number']) ? min ($_SESSION['min_number'], count($items)) : 0);
		$_SESSION['max_number'] = isset($_REQUEST['max_number']) ? $_REQUEST['max_number'] : (isset($_SESSION['max_number']) ? min ($_SESSION['max_number'], count($items)) : count($items));
		
		$html  = sprintf("<form  enctype='multipart/form-data' action='admin.php?page=generator' method='post'><table class='form-table'><tbody'>");
		$html .= sprintf("<tr><th style='padding-top:0px; padding-bottom:0px;'><label>%s</label></th>", "Number of Items");
		$html .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
		$html .= sprintf("<input style='width:5em' type='number' name='min_%s' min='0' max='%d' value='%d'/>", "number", count($items), $_SESSION['min_number']);
		$html .= sprintf("<input style='width:5em' type='number' name='max_%s' min='0' max='%d' value='%d'/>", "number", count($items), $_SESSION['max_number']);
		$html .= sprintf("</td></tr>");
		
		// Min / Max for all categories
		$categories = array ("type", "dim", "level", "topic1");
		foreach ($categories as $category) {
			
			$html .= sprintf("<tr><th style='padding-bottom:0.5em;'><label>%s</label></th></tr>", EAL_Item::$category_label[$category]);
			foreach (PAG_Explorer::groupBy ($category, $items, NULL, true) as $catval => $catitems) {
				
				$_SESSION['min_'.$catval] = isset($_REQUEST['min_'.$catval]) ? $_REQUEST['min_'.$catval] : (isset($_SESSION['min_'.$catval]) ? min ($_SESSION['min_'.$catval], count($catitems)) : 0);
				$_SESSION['max_'.$catval] = isset($_REQUEST['max_'.$catval]) ? $_REQUEST['max_'.$catval] : (isset($_SESSION['max_'.$catval]) ? min ($_SESSION['max_'.$catval], count($catitems)) : count($catitems));
					
				$html .= sprintf("<tr><td style='padding-top:0px; padding-bottom:0px;'><label>%s</label></td>", ($category == "topic1") ? $catval : EAL_Item::$category_value_label[$category][$catval]);
				$html .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
				$html .= sprintf("<input style='width:5em' type='number' name='min_%s' min='0' max='%d' value='%d'/>", $catval, count($catitems), $_SESSION['min_'.$catval]);
				$html .= sprintf("<input style='width:5em' type='number' name='max_%s' min='0' max='%d' value='%d'/>", $catval, count($catitems), $_SESSION['max_'.$catval]);
				$html .= sprintf("</td></tr>");
			}
			
		}
		
		$html .= sprintf ("<tr><th><button type='submit' name='action' value='generate'>Generate</button></th><tr>");
		$html .= sprintf ("</tbody></table></form></div>");			

		
		
		// (re-)generate pools
		if ($_REQUEST['action'] == 'generate') {
			$pool = new PAG_Generator(
				$items,
				array ($_SESSION['min_number'], $_SESSION['min_itemsc'], $_SESSION['min_itemmc'], $_SESSION['min_FW'], $_SESSION['min_KW'], $_SESSION['min_PW']),
				array ($_SESSION['max_number'], $_SESSION['max_itemsc'], $_SESSION['max_itemmc'], $_SESSION['max_FW'], $_SESSION['max_KW'], $_SESSION['max_PW'])
			);
			
			$_SESSION['generated_pools'] = $pool->generatedPools;
		}
		
		
		print ("<div class='wrap'>");
		print ("<h1>Task Pool Generator</h1><br/>");
		print ($html);
		
		// show pools (from last generation; stored in Session variable)
		if (isset ($_SESSION['generated_pools'])) {
// 			print_r ($_SESSION['generated_pools']);
				
			print ("<br/><h2>Generated Task Pools</h2>");
			printf ("<table cellpadding='10px' class='widefat fixed' style='table-layout:fixed; width:%dem; background-color:rgba(0, 0, 0, 0);'>", 6+2*count($items)); 
			print ("<col width='6em;' />");
			
			foreach ($items as $item) {
				print ("<col width='2em;' />");
			}
			
			foreach ($_SESSION['generated_pools'] as $pool) {
				print ("<tr valign='middle'>");
				$s = "View";
				$href = "admin.php?page=view&itemids=" . join (",", $pool);
				printf ("<td style='overflow: hidden; padding:0px; padding-bottom:0.5em; padding-top:0.5em; padding-left:1em' ><a href='%s' class='button'>View</a></td>", $href);
				
				// http://localhost/wordpress/wp-admin/admin.php?page=view&itemids=458,307,307,106
				foreach ($items as $item) {
					
					$symbol = "";
					$link = "";
					if (in_array($item->id, $pool)) {
						$link = sprintf ("onClick='document.location.href=\"admin.php?page=view&itemid=%s\";'", $item->id);
						if ($item->type == "itemsc") $symbol = "<span class='dashicons dashicons-marker'></span>";	
						if ($item->type == "itemmc") $symbol = "<span class='dashicons dashicons-forms'></span>";	
					}
					
					
					printf ("<td %s valign='bottom' style='overflow: hidden; padding:0px; padding-top:0.83em;' >%s</td>", $link, $symbol /*(in_array($item->id, $pool) ? "X" : "")*/);
				}
				print ("</tr>");
			}
			print ("</table>");
			
			
		}
			
				
		
	}
	

}
?>
	
	
	
	
	
	
	