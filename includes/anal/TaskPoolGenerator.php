<?php

require_once ("ItemExplorer.php");

class TaskPoolGenerator {
	
	
	private $itemVectors;
	private $rangeVectors;
	private $countCriteria;
	private $itemids;
	
	const POOL_SUMMARY = 0;
	const POOL_ITEMS = 1;
	const POOL_ORDER = 2;
	const POOL_LAST = 3;
	
	
	function __construct () {
		
	}
	
	/**
	 * $dimensions: [ name of category => [ "min" => [ category value => min number], "max => [ category value => max number ] ] ]  
	 * @param array $dimensions
	 */
	public function generatePools (array $dimensions) {
		
		$items = EAL_ItemBasket::getItems();
		$this->itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		$mapItemId2ItemIndex = [];	// item id -> itemn index (0 ... count(items)-1)
		foreach ($this->itemids as $index => $id) $mapItemId2ItemIndex[$id] = $index;
		
		// every condition is represented as position in vector
		// item vector contains 0 or 1 depending on if the items is in this group
		// range vector: min / max contain the min / max range condition; all the number of all items with a 1 in this position
		
		$this->itemVectors = new \Ds\Vector();
		foreach ($this->itemids as $id) $this->itemVectors->push (new \Ds\Vector());
		
		$this->rangeVectors = ["min" => new \Ds\Vector(), "max" => new \Ds\Vector(), "all" => new \Ds\Vector()];
		
		$this->countCriteria = 0;
		foreach ($dimensions as $category => $minmax) {
			
			foreach (ItemExplorer::groupBy($items, $this->itemids, $category) as $key => $groupItemIds) {
				$this->countCriteria++;
				foreach ($groupItemIds as $id) {
					$this->itemVectors[$mapItemId2ItemIndex[$id]]->push (1);
				}
				foreach ($this->itemids as $id) {
					if ($this->itemVectors[$mapItemId2ItemIndex[$id]]->count() < $this->countCriteria) $this->itemVectors[$mapItemId2ItemIndex[$id]]->push (0);
				}
				$this->rangeVectors["min"]->push ($minmax["min"][$key]);
				$this->rangeVectors["max"]->push ($minmax["max"][$key]);
				$this->rangeVectors["all"]->push (count($groupItemIds));
			}
		}
		
		

		
		
		// INIT		
		$poolSummary = new \Ds\Vector();	// int vector; dimension = number of criteria; similar to rangeVector
		$poolItems = new \Ds\Vector();		// binary vector; dimension = number of all items; $poolitems[$i]==1 iff item with index i is in pool
		$poolItemOrderForAdding = new \Ds\Vector();		// determine the order of items to be added
		
		for ($criteriaIndex=0; $criteriaIndex<$this->countCriteria; $criteriaIndex++) { 
			$poolSummary->push(0); 
		}
		for ($itemIndex=0; $itemIndex<count($this->itemids); $itemIndex++) { 
			$poolItems->push(0);
			$poolItemOrderForAdding->push ($itemIndex);
		}


		$time_start = time();
		$time_break = $time_start + 10;
		
		
		$stack = new \Ds\Vector();
		$stack->push (new \Ds\Vector(), new \Ds\Vector(), new \Ds\Vector(), new \Ds\Vector());
		$stack->get($this::POOL_SUMMARY)->push ($poolSummary);
		$stack->get($this::POOL_ITEMS)->push ($poolItems);
		$stack->get($this::POOL_ORDER)->push ($poolItemOrderForAdding);
		$stack->get($this::POOL_LAST)->push (-1);
		
		$stackSize = 1;
		$countPools = 0;
		$removed = 0;
		while ($stackSize > 0) {
			
			if (time()>$time_break) break;
			
			// get current config
			$poolSummary = $stack->get($this::POOL_SUMMARY)->get ($stackSize-1)->copy();
			$poolItems = $stack->get($this::POOL_ITEMS)->get ($stackSize-1)->copy();
			$poolItemOrderForAdding = $stack->get($this::POOL_ORDER)->get($stackSize-1)->copy();
			$newPos = $stack->get($this::POOL_LAST)->get($stackSize-1) + 1;
			
			// check current pool (if newly generated)
			$check = $this->checkItemPool ($poolSummary, $poolItemOrderForAdding, $newPos);
			if ($removed == 0) {
				if ($check == 0) {
					$countPools++;
					print_r ($countPools);
					print_r ($poolSummary);
					print_r ($poolItems);
					print_r ($poolItemOrderForAdding);
					print_r ($newPos-1);
					print ("<br/>");
					
					if ($countPools==10) break;
				}
			}
			
			
			
			
			if (($check > 0) && ($newPos < count($poolItemOrderForAdding))) {
				
				// update current config: INCREASE LAST
				$stack->get($this::POOL_LAST)->set($stackSize-1, $newPos);
				
				// add item to pool item; re-compute pool summary
				$itemIndex = $poolItemOrderForAdding[$newPos];
				$poolItems[$itemIndex]=1;
				$itemVector = $this->itemVectors[$itemIndex];
				for ($i=0; $i<$this->countCriteria; $i++) {
					$poolSummary[$i] += $itemVector[$i];
				}
			
				// recompute order of items to be added
				$poolItemOrderForAdding = $poolItemOrderForAdding->slice($newPos+1)->copy();
				
				
				// add to stack
				$removed = 0;
				$stack->get($this::POOL_SUMMARY)->push ($poolSummary);
				$stack->get($this::POOL_ITEMS)->push ($poolItems);
				$stack->get($this::POOL_ORDER)->push ($poolItemOrderForAdding);
				$stack->get($this::POOL_LAST)->push (-1);
				
				
			} else {
				
				$removed = 1;
				$stack->get($this::POOL_SUMMARY)->pop();
				$stack->get($this::POOL_ITEMS)->pop();
				$stack->get($this::POOL_ORDER)->pop();
				$stack->get($this::POOL_LAST)->pop();
			}
			
			$stackSize = count($stack->get($this::POOL_LAST));
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
		
		print ("<br><br>" . $time);
		
		return $itemVectors;
		
		
	}

	
	private function checkItemPool ($poolSummary, $poolItemOrderForAdding, $newPos) {
		
		$result = 0;
		
		for ($i=0; $i<$this->countCriteria; $i++) {
			
			if ($poolSummary[$i] >  $this->rangeVectors["max"][$i]) {
				return -1;	// too large
			}
			
			if ($poolSummary[$i] >= $this->rangeVectors["min"][$i]) {
				continue;	
			}
			
			$maxAdd = 0;
			for ($pos=$newPos; $pos<count($poolItemOrderForAdding); $pos++) {
				$maxAdd += $this->itemVectors[$poolItemOrderForAdding[$pos]][$i];
			}
			
			if ($poolSummary[$i] + $maxAdd < $this->rangeVectors["min"][$i]) {
				return -2;	// too small; can not be reached anymore
			}
			
			$result++;
		}
		return $result;
	}
	
	

	
	
}

?>