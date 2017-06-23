<?php

require_once ("ItemExplorer.php");

class TaskPoolGenerator {
	
	
	private $itemVectors;
	private $rangeVectors;
	private $count;
	private $itemids;
	
	function __construct () {
		
	}
	
	/**
	 * $dimensions: [ name of category => [ "min" => [ category value => min number], "max => [ category value => max number ] ] ]  
	 * @param array $dimensions
	 */
	public function generatePools (array $dimensions) {
		
		$items = EAL_ItemBasket::getItems();
		$this->itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		
		// every condition is represented as position in vector
		// item vector contains 0 or 1 depending on if the items is in this group
		// range vector: min / max contain the min / max range condition; all the number of all items with a 1 in this position
		
		$this->itemVectors = [];
		foreach ($this->itemids as $id) $this->itemVectors[$id] = new \Ds\Vector();
		
		$this->rangeVectors = ["min" => new \Ds\Vector(), "max" => new \Ds\Vector(), "all" => new \Ds\Vector()];
		
		$this->count = 0;
		foreach ($dimensions as $category => $minmax) {
			
			foreach (ItemExplorer::groupBy($items, $this->itemids, $category) as $key => $groupItemIds) {
				$this->count++;
				foreach ($groupItemIds as $id) {
					$this->itemVectors[$id]->push (1);
				}
				foreach ($this->itemids as $id) {
					if ($this->itemVectors[$id]->count() < $this->count) $this->itemVectors[$id]->push (0);
				}
				$this->rangeVectors["min"]->push ($minmax["min"][$key]);
				$this->rangeVectors["max"]->push ($minmax["max"][$key]);
				$this->rangeVectors["all"]->push (count($groupItemIds));
			}
		}
		
		
		// determine how man
		for ($i=0; $i<$this->count; $i++) {
		
// 			print_r (gmp_fact($rangeVectors["all"][$i]));
// 			print_r (gmp_div_q (gmp_div_q (gmp_fact($this->rangeVectors["all"][$i]), gmp_fact($this->rangeVectors["min"][$i])), gmp_fact($this->rangeVectors["all"][$i]-$this->rangeVectors["min"][$i])));
			
		}
		
		
		
		$poolSummary = new \Ds\Vector();
		$poolItems = new \Ds\Vector();
		$itemIndex = -1;
		
		for ($i=0; $i<$this->count; $i++) { $poolSummary->push(0); }
		for ($i=0; $i<count($this->itemids); $i++) { $poolItems->push(0); }
		
		
		$stack = [];
		
		$numberOfPools = 0;
		$check = +1;
		while (true) {
			
			if ($check == +1) {		
				array_push($stack, ["itemIndex" => $itemIndex, "poolSummary" => $poolSummary->copy(), "poolItems" => $poolItems->copy()]);
			}
			
			// add next item
			$itemIndex++;
			$this->addNextItem($itemIndex, $poolSummary, $poolItems);
			
			$check = $this->checkItemPool ($poolSummary);
			
			if ($check == 0) {
				print_r ($poolSummary);
				print_r ($poolItems);
				$numberOfPools++;
				if ($numberOfPools==10) break;
			}
					
			if (($check == 0) || ($check == -1)) {
				
				if (count($stack)==0) break;
				
				$last = array_pop($stack);
				$itemIndex = $last["itemIndex"];
				$poolSummary = $last["poolSummary"];
				$poolItems = $last["poolItems"];
			}
			
			
			
			
			
		}
		
		
		
		return $itemVectors;
		
		
	}

	
	private function checkItemPool ($poolSummary) {
		
		$toAdd = $this->rangeVectors["min"][0] - $poolSummary[0];
		
		$result = 0;
		for ($i=0; $i<$this->count; $i++) {
			if ($poolSummary[$i]>$this->rangeVectors["max"][$i]) {
				return -1;	// too large
			}
			
			
			$maxAddable = min($this->rangeVectors["all"][$i]-$poolSummary[$i], $toAdd);
			if ($poolSummary[$i] + $maxAddable <$this->rangeVectors["min"][$i]) {
				return -1; // can not be reached
			}
			
			if ($poolSummary[$i]<$this->rangeVectors["min"][$i]) {
				$result = 1;	// too small
			}
		}
		return $result;
	}
	
	
	private function addNextItem ($itemIndex, &$poolSummary, &$poolItems) {
		
		$poolItems[$itemIndex]=1;
		$itemVector = $this->itemVectors[$this->itemids[$itemIndex]];
		for ($i=0; $i<$this->count; $i++) {
			$poolSummary[$i] += $itemVector[$i];
		}
		
	}
	
	
}

?>