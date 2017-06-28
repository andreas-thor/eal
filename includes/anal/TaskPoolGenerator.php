<?php

require_once ("ItemExplorer.php");

class TaskPoolGenerator {
	
	private $result;
	private $stack;
	private $itemVectors;
	private $rangeVectors;
	private $countCriteria;
	private $itemids;
	private $maxtime;
	
	const POOL_SUMMARY = 0;
	const POOL_ITEMS = 1;
	const POOL_ORDER = 2;
	const POOL_LAST = 3;
	
	
	function __construct ($maxtime) {
		$this->maxtime = $maxtime;
	}
	
	/**
	 * $dimensions: [ name of category => [ "min" => [ category value => min number], "max => [ category value => max number ] ] ]  
	 * @param array $dimensions
	 */
	public function generatePools (array $dimensions) {
		
		$this->result = [];
		
		$items = EAL_ItemBasket::getItems();
		$this->itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		$mapItemId2ItemIndex = [];	// item id -> itemn index (0 ... count(items)-1)
		foreach ($this->itemids as $index => $id) $mapItemId2ItemIndex[$id] = $index;
		
		// every condition is represented as position in vector
		// item vector contains 0 or 1 depending on if the items is in this group
		// range vector: min / max contain the min / max range condition; all the number of all items with a 1 in this position
		
		$this->itemVectors = array();
		foreach ($this->itemids as $id) $this->itemVectors[] = array();
		
		$this->rangeVectors = ["min" => array(), "max" => array(), "all" => array()];
		
		$this->countCriteria = 0;
		foreach ($dimensions as $category => $minmax) {
			
			foreach (ItemExplorer::groupBy($items, $this->itemids, $category) as $key => $groupItemIds) {
				$this->countCriteria++;
				foreach ($groupItemIds as $id) {
					array_push ($this->itemVectors[$mapItemId2ItemIndex[$id]], 1);
				}
				foreach ($this->itemids as $id) {
					if (count($this->itemVectors[$mapItemId2ItemIndex[$id]]) < $this->countCriteria) $this->itemVectors[$mapItemId2ItemIndex[$id]][] = 0;
				}
				$this->rangeVectors["min"][] = $minmax["min"][$key];
				$this->rangeVectors["max"][] = $minmax["max"][$key];
				$this->rangeVectors["all"][] = count($groupItemIds);
			}
		}
		
		

		
		
		// INIT		
		$this->initStack();

		
		
		
		$time_start = time();
		$time_break = $time_start + $this->maxtime;
		
		$stackSize = 1;
		$countPools = 0;
		$removed = 0;
		while ($stackSize > 0) {
			
			if (time()>$time_break) break;
			
			// get current config
			$poolSummary = $this->stack[$this::POOL_SUMMARY][$stackSize-1];
			$poolItems = $this->stack[$this::POOL_ITEMS][$stackSize-1];
			$poolItemOrderForAdding = $this->stack[$this::POOL_ORDER][$stackSize-1];
			$newPos = $this->stack[$this::POOL_LAST][$stackSize-1] + 1;
			
			// check current pool (if newly generated)
			// $check = $this->checkItemPool ($poolSummary, $poolItemOrderForAdding, $newPos);
			
			$check = 0;
			for ($i=0; $i<$this->countCriteria; $i++) {
				if ($poolSummary[$i] >  $this->rangeVectors["max"][$i]) {
					$check = -1;
					break; // too large
				}
					
				if ($poolSummary[$i] >= $this->rangeVectors["min"][$i]) {
					continue;
				}
					
				$maxAdd = 0;
				for ($pos=$newPos; $pos<count($poolItemOrderForAdding); $pos++) {
					$maxAdd += $this->itemVectors[$poolItemOrderForAdding[$pos]][$i];
				}
					
				if ($poolSummary[$i] + $maxAdd < $this->rangeVectors["min"][$i]) {
					$check = -2;
					break;// too small; can not be reached anymore
				}
					
				$check++;
			}
			
			
			
			if ($removed == 0) {
				if ($check == 0) {
					$countPools++;
// 					print_r ($countPools);
// 					print_r ($poolSummary);
// 					print_r ($poolItems);
// 					print_r ($poolItemOrderForAdding);
// 					print_r ($newPos-1);
// 					print ("<br/>");
					
					$resultPool = [];
					for ($itemIndex=0; $itemIndex<count($this->itemids); $itemIndex++) {
						if ($poolItems[$itemIndex]==1) $resultPool[] = $this->itemids[$itemIndex];
					}
					$this->result[] = $resultPool;
					
					if ($countPools==10) break;
					
					// start as new --> init will beachten items, die schon drin sind
					$this->initStack();
					$stackSize = 1;
					$removed = 0;
					continue;
					
					
				}
			}
			
			
			
			
			if (($check > 0) && ($newPos < count($poolItemOrderForAdding))) {
				
				// update current config: INCREASE LAST
				$this->stack[$this::POOL_LAST][$stackSize-1] = $newPos;
				
				// add item to pool item; re-compute pool summary
				$itemIndex = $poolItemOrderForAdding[$newPos];
				$poolItems[$itemIndex]=1;
				$itemVector = $this->itemVectors[$itemIndex];
				for ($i=0; $i<$this->countCriteria; $i++) {
					$poolSummary[$i] += $itemVector[$i];
				}
			
				// recompute order of items to be added
				$poolItemOrderForAdding = array_slice ($poolItemOrderForAdding, $newPos+1);
				
				
				// add to stack
				$removed = 0;
				$this->stack[$this::POOL_SUMMARY][] = $poolSummary;
				$this->stack[$this::POOL_ITEMS][] = $poolItems;
				$this->stack[$this::POOL_ORDER][] = $poolItemOrderForAdding;
				$this->stack[$this::POOL_LAST][] = -1;
				
				
			} else {
				
				$removed = 1;
				array_pop($this->stack[$this::POOL_SUMMARY]);
				array_pop($this->stack[$this::POOL_ITEMS]);
				array_pop($this->stack[$this::POOL_ORDER]);
				array_pop($this->stack[$this::POOL_LAST]);
			}
			
			$stackSize = count($this->stack[$this::POOL_LAST]);
		}

		
/*		
			
			
			if ($check == +1) {	
				
				// determine the order of items to be added
				$poolItemOrderForAdding = new \Ds\Vector();
				for ($itemIndex=0; $itemIndex<count($poolItems); $itemIndex++) {
					if ($poolItems[$itemIndex]==1) continue;	// item already in pool
					$poolItemOrderForAdding->push ($itemIndex);
				}
				array_push($stack, ["poolSummary" => $poolSummary->copy(), "poolItems" => $poolItems->copy(), "poolItemOrderForAdding" => $poolItemOrderForAdding->copy()]);
				array_push($stack_lastAddPos, -1);
			}
			
			// add next item: (1)+(2)
			// (1) pop from Item Order
			$newPos = 1 + $stack_lastAddPos->pop();
			array_push($stack_lastAddPos, $newPos);
			$itemIndex = -1;
			$check = -1;
			if ($newPos < count($poolItemOrderForAdding)) {
				$itemIndex = $poolItemOrderForAdding[$newPos];
			
				// (2) add to pool item; re-compute pool summary
				$poolItems[$itemIndex]=1;
				$itemVector = $this->itemVectors[$itemIndex];
				for ($i=0; $i<$this->countCriteria; $i++) {
					$poolSummary[$i] += $itemVector[$i];
				}
				
				$check = $this->checkItemPool ($poolSummary);
			}
			
			
			
			
			if ($check == 0) {
				print_r ($poolSummary);
				print_r ($poolItems);
				$numberOfPools++;
				if ($numberOfPools==10) break;
			}
					
			if (($check == 0) || ($check == -1)) {
				
				// get the next configuration: $itemIndex to add, pool summary, and pool items 
				$itemIndex = -1;
				while (($itemIndex == -1) && (count($stack)>0)) {
				
					// get last config from stack
					$last = array_pop($stack);
					$poolSummary = $last["poolSummary"];
					$poolItems = $last["poolItems"];
					$poolItemOrderForAdding = $last["poolItemOrderForAdding"];
					
					// if there is at least one item to add .. 
					if (count($poolItemOrderForAdding)>0) {
						$itemIndex = $poolItemOrderForAdding->pop();
					}	// else ... get the next config from stack (next while iteration)
				}
				
				// could not find next item Index --> END OF SEARCH
				if ($itemIndex == -1) break;
				
					
			}
			
			
			
			
			
		}
		
		*/
		
		
		$time_end = time();
		$time = $time_end - $time_start;
		
// 		print ("<br><br>" . $time);
		
		return $this->result;
		
		
	}

	
	private function initStack () {
		
		$poolSummary = array();	// int vector; dimension = number of criteria; similar to rangeVector
		$poolItems = array();		// binary vector; dimension = number of all items; $poolitems[$i]==1 iff item with index i is in pool
		$poolItemOrderForAdding = array();		// determine the order of items to be added
		
		for ($criteriaIndex=0; $criteriaIndex<$this->countCriteria; $criteriaIndex++) {
			$poolSummary[] = 0;
		}
		for ($itemIndex=0; $itemIndex<count($this->itemids); $itemIndex++) {
			$poolItems[] = 0;
		}
		
		$itemValue = [];
		for ($itemIndex=0; $itemIndex<count($this->itemids); $itemIndex++) {
			$value = 0;
			
			// bevorzuge Items, die direkt zum Ziel führen
			for ($criteriaIndex=0; $criteriaIndex<$this->countCriteria; $criteriaIndex++) {
				$value += $this->itemVectors[$itemIndex][$criteriaIndex] * $this->rangeVectors["min"][$criteriaIndex] / $this->rangeVectors["all"][$criteriaIndex];
			}
			
			// bestrafe items, die schon (häufig) in Pools sind
			for ($poolNo=0; $poolNo<count($this->result); $poolNo++) {
				if (in_array($this->itemids[$itemIndex], $this->result[$poolNo])) {
					$value -= $this->countCriteria;
				}
			}
			
			$itemValue[$itemIndex] = $value;
		}
		arsort($itemValue);
		
		foreach ($itemValue as $itemIndex => $value) {
			$poolItemOrderForAdding[] = $itemIndex;
		}
		
 		$this->stack = array();
		array_push ($this->stack, array(), array(), array(), array());
		$this->stack[$this::POOL_SUMMARY][] = $poolSummary;
		$this->stack[$this::POOL_ITEMS][] = $poolItems;
		$this->stack[$this::POOL_ORDER][] = $poolItemOrderForAdding;
		$this->stack[$this::POOL_LAST][] = -1;
	}
	
	


	
	
}

?>