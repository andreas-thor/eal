<?php

require_once ("ItemExplorer.php");

class TaskPoolGenerator {
	
	private $groupPools;
	private $itemGroupVectors;
	private $itemGroups;
	
	

	
	
	private function generateItemVectorsAndRangeVectors (array $items, array $itemids, array $dimensions): array {
		
		$mapItemId2ItemIndex = [];	// item id -> itemn index (0 ... count(items)-1)
		$itemVectors = array();
		
		foreach ($itemids as $index => $id) {
			$mapItemId2ItemIndex[$id] = $index;
			$itemVectors[] = array();
		}
		
		// every condition is represented as position in vector
		// item vector contains 0 or 1 depending on if the items is in this group
		// range vector: min / max contain the min / max range condition; all the number of all items with a 1 in this position
		$rangeVectors = ["min" => array(), "max" => array(), "all" => array()];
		
		$countCriteria = 0;
		foreach ($dimensions as $category => $minmax) {
			
			foreach (ItemExplorer::groupBy($items, $itemids, $category) as $key => $groupItemIds) {
				
				// ignore condidtions with min=0 and max=all
				if (($minmax["min"][$key]==0) && ($minmax["max"][$key]==count($groupItemIds))) continue;
				
				$countCriteria++;
				foreach ($groupItemIds as $id) {
					$itemVectors[$mapItemId2ItemIndex[$id]][] = 1;
				}
				foreach ($itemids as $id) {
					if (count($itemVectors[$mapItemId2ItemIndex[$id]]) < $countCriteria) {
						$itemVectors[$mapItemId2ItemIndex[$id]][] = 0;
					}
				}
				$rangeVectors["min"][] = $minmax["min"][$key];
				$rangeVectors["max"][] = $minmax["max"][$key];
				$rangeVectors["all"][] = count($groupItemIds);
			}
		}
		
		return [$itemVectors, $rangeVectors, $mapItemId2ItemIndex];
	}
	
	
	private function generateItemGroups (array $itemids, array $itemVectors) {
		
		// we group together items with the same vector
		$itemGroups = array();
		$itemGroupVectors = array();
		foreach ($itemVectors as $index => $itvec) {
			
			$isnew = TRUE;
			// can we find a matching group vector $grvec for the current item vector $itvec?
			foreach ($itemGroupVectors as $pos => $grvec) {
				
				$diff = array_diff_assoc ($itvec, $grvec);
				unset($diff[0]);	// we ignore the first entry (==1 for items; ==count for group vector)
				
				if (count($diff)==0) {	// no difference --> match!
					$isnew = FALSE;
					$itemGroupVectors[$pos][0] = $itemGroupVectors[$pos][0]+1;
					$itemGroups[$pos][] = $itemids[$index];
					break;
				}
			}
			
			if ($isnew) {
				$itemGroupVectors[] = $itvec;
				$itemGroups[] = [$itemids[$index]];
			}
		}
		
		return [$itemGroups, $itemGroupVectors];
	}
	
	

	
	
	
	private function generateGroupPools2 (array $itemGroupVectors, array $rangeVectors, int $maxTime) {
		
		$sumItemGroupVectors = array_fill (0, count($rangeVectors["min"]), 0);
		for ($index = 0; $index < count($itemGroupVectors); $index += 1) {
			foreach ($itemGroupVectors[$index] as $dim => $val) {
				$sumItemGroupVectors[$dim] += ($dim==0) ? $val : $itemGroupVectors[$index][0]*$val;
			}
		}
		
		$minPool = array_fill (0, count($itemGroupVectors), 0);
		for ($index = 0; $index < count($itemGroupVectors); $index += 1) {
			foreach ($itemGroupVectors[$index] as $dim => $val) {
				$number = ($dim==0) ? $val : $itemGroupVectors[$index][0]*$val;
				if ($sumItemGroupVectors[$dim]-$number < $rangeVectors["min"][$dim]) {
					$min = $rangeVectors["min"][$dim] - $sumItemGroupVectors[$dim] + $number;
					$minPool[$index] = max ($minPool[$index], $min);
				}
			}
		}
		
		
		$result = [];
		$currentPool = $minPool;
		$currentValues = array_fill (0, count($rangeVectors["min"]), 0);
		
		
		// values = [v_0, v_1, ..., v_(m-1)] where v_k is the number of items that fulfill condition (with index) k
		// m = number of criteria
		// condition v_0 = number of items overall
		$currentValues = array_fill (0, count($rangeVectors["min"]), 0);
		for ($index = 0; $index < count($itemGroupVectors); $index += 1) {
			foreach ($itemGroupVectors[$index] as $dim => $val) {
				$currentValues[$dim] += ($dim==0) ? $currentPool[$index] : $currentPool[$index]*$val;
			}
		}
		
		
		
		$backlog = [];	// list of group indexes where we added an item
		$time_start = time();
		$time_break = $time_start + $maxTime;
		
		while (true) {
			
			if (time()>$time_break) break;
			
			// check current pool
			$poolIsOk = TRUE;
			$poolIsTooLarge = FALSE;
			foreach ($currentValues as $dim => $value) {
				if ($value < $rangeVectors["min"][$dim])  {
					$poolIsOk = FALSE;
				}
				if ($value > $rangeVectors["max"][$dim]) {
					$poolIsOk = FALSE;
					$poolIsTooLarge = TRUE;
					break;
				}
			}
			
			// if ok --> add to result
			if ($poolIsOk) {
				$result[] = $currentPool;
			}
			
			if (!$poolIsTooLarge) {

				$addIndex = (count($backlog)==0) ? 0 : $backlog[count($backlog)-1];
				
				while ($currentPool[$addIndex] == $itemGroupVectors[$addIndex][0]) {
					$addIndex++;
					if ($addIndex == count($currentPool)) {
						$poolIsTooLarge = TRUE;
						break;
					}
				}
			}
			
			
			while ($poolIsTooLarge) {
				
				if (count($backlog)==0) break;
	
				$poolIsTooLarge = FALSE;
				$lastIndex = array_pop($backlog);
				
				// remove 1 item of $lastIndex
				$currentPool[$lastIndex] -= 1;
				foreach ($itemGroupVectors[$lastIndex] as $dim => $val) {
					$currentValues[$dim] -= ($dim==0) ? 1 : $val;	// $val is either 0 or 1; $dim=0 counts the number of items
				}
				
				// get the next index to add
				$addIndex = $lastIndex+1;
				if ($addIndex == count($currentPool)) {
					$poolIsTooLarge = TRUE;		// cannot find an index --> must go back one step
					continue;
				}
				
				while ($currentPool[$addIndex] == $itemGroupVectors[$addIndex][0]) {
					$addIndex++;
					if ($addIndex == count($currentPool)) {
						$poolIsTooLarge = TRUE;		// cannot find an index --> must go back one step
						break;
					}
				}
				
			}
			
			if ($poolIsTooLarge) { 
				break;
			}
			
			$backlog[] = $addIndex;
			$currentPool[$addIndex] += 1;
			foreach ($itemGroupVectors[$addIndex] as $dim => $val) {
				$currentValues[$dim] += ($dim==0) ? 1 : $val;	// $val is either 0 or 1; $dim=0 counts the number of items
			}

		}
		
		return $result;
			
	}
	
	
	
	/**
	 * $dimensions: [ name of category => [ "min" => [ category value => min number], "max => [ category value => max number ] ] ]  
	 * @param array $dimensionsAll
	 */
	public function generatePools (array $dimensionsAll, int $maxTime) {
		
		$_SESSION["tpg_dimensions_All"] = [];
		$_SESSION["tpg_dimensions_Pool"] = [];
		$_SESSION["tpg_groupPools"] = [];
		$_SESSION["tpg_itemGroupVectors"] = [];
		$_SESSION["tpg_itemGroups"] = [];
		$_SESSION["tpg_numberOfItemPools"] = -1;
		
		
		$items = EAL_ItemBasket::getItems();
		$itemids = ItemExplorer::getItemIds($items);
		
		$dimensionsPool = $dimensionsAll;
		if (array_key_exists ("topic1", $dimensionsAll)) {
			$dimensionsPool = ["number" => $dimensionsAll["number"], "topic1" => $dimensionsAll["topic1"]];
		}
		
		list ($itemVectors, $rangeVectors, $mapItemId2ItemIndex) = $this->generateItemVectorsAndRangeVectors($items, $itemids, $dimensionsPool);
		list ($this->itemGroups, $this->itemGroupVectors) = $this->generateItemGroups($itemids, $itemVectors);
		
		$this->groupPools = $this->generateGroupPools2 ($this->itemGroupVectors, $rangeVectors, $maxTime);
		
		$_SESSION["tpg_dimensions_All"] = $dimensionsAll;
		$_SESSION["tpg_dimensions_Pool"] = $dimensionsPool;
		$_SESSION["tpg_groupPools"] = $this->groupPools;
		$_SESSION["tpg_itemGroupVectors"] = $this->itemGroupVectors;
		$_SESSION["tpg_itemGroups"] = $this->itemGroups;
		$_SESSION["tpg_numberOfItemPools"] = $this->getNumberOfItemPools();
		
	}
	
	public function getItemPoolsAtRandom (int $maxTimeInSeconds, int $numberOfItemPools) {
		
		$this->groupPools = $_SESSION["tpg_groupPools"];
		$this->itemGroupVectors = $_SESSION["tpg_itemGroupVectors"];
		$this->itemGroups = $_SESSION["tpg_itemGroups"];
		
		$dimensionsAll = $_SESSION["tpg_dimensions_All"];
		$dimensionsPool = $_SESSION["tpg_dimensions_Pool"];
		
		$dimensionsToCheck = array_diff_key ($dimensionsAll, $dimensionsPool);
		if (count($dimensionsToCheck)>0) {
			
			$dimensionsToCheck = array_merge (["number" => $dimensionsAll["number"]], $dimensionsToCheck);
			
			$items = EAL_ItemBasket::getItems();
			$itemids = ItemExplorer::getItemIds($items);
			list ($itemVectors, $rangeVectors, $mapItemId2ItemIndex) = $this->generateItemVectorsAndRangeVectors($items, $itemids, $dimensionsToCheck);
			
		}
		
		
		
		$result = [];
		$time_start = time();
		$time_break = $time_start + $maxTimeInSeconds;
		
			
			
		while (count($result) < $numberOfItemPools) {
			
			if (time()>$time_break) break;
		
			$randomGroupPoolIndex = rand(1, count($this->groupPools))-1;
			$currentGroupPool = $this->groupPools[$randomGroupPoolIndex];
			$currentItemPool = [];
			
			foreach ($currentGroupPool as $itemGroupIdx => $numberOfItems) {
			
				if ($numberOfItems>0) {
					$randomSet = range (0, $this->itemGroupVectors[$itemGroupIdx][0]-1);
					shuffle($randomSet);
					$currentItemPool[] = array_slice ($randomSet, 0, $numberOfItems);
				} else {
					$currentItemPool[] = [];
				}
			}
			
			$resultPool = [];
			foreach ($currentGroupPool as $itemGroupIdx => $numberOfItems) {
				foreach ($currentItemPool[$itemGroupIdx] as $x) {
					$resultPool[] = $this->itemGroups[$itemGroupIdx][$x];
				}
			}
			
			if (count($dimensionsToCheck)>0) {
				
				$sumVector = array_fill(0, count($rangeVectors["min"]), 0);
				foreach ($resultPool as $itemid) {
					foreach ($itemVectors[$mapItemId2ItemIndex[$itemid]] as $dim => $val) {
						$sumVector[$dim] += $val;
					}
				}
				
				$poolIsOk = TRUE;
				foreach ($sumVector as $dim => $val) {
					if ($rangeVectors["min"][$dim] > $val) {
						$poolIsOk = FALSE;
						break;
					}
					if ($rangeVectors["max"][$dim] < $val) {
						$poolIsOk = FALSE;
						break;
					}
				}
		
				if (!$poolIsOk) {
					continue;
				}
				
			}
			
			
			$result[] = $resultPool;
		}
		
		
		return $result;
	}
	
	

	
	
	/**
	 * Computes the overall number of item tools based on the generated grouped pools
	 * @return number|GMP
	 */	
	private function getNumberOfItemPools (): GMP {
		
		$result = gmp_init(0);
		for ($groupPoolIndex=0; $groupPoolIndex<count($this->groupPools); $groupPoolIndex++) {
			$result = gmp_add($result, $this->getNumberOfItemPoolsInGroup ($groupPoolIndex));
		}
		return $result;
	}

	
	/**
	 * Computes the number of item tools for a specific grouped pools
	 * @return number|GMP
	 */	
	
	private function getNumberOfItemPoolsInGroup (int $groupPoolIndex): GMP {
		
		$result = 1;
		foreach ($this->groupPools[$groupPoolIndex] as $grIdx => $numberOfItems) {
			// $count = "number" out of "all in group" = all! / (all-number)! * number!
			$count = gmp_fact($this->itemGroupVectors[$grIdx][0]) / (gmp_fact($numberOfItems) * gmp_fact($this->itemGroupVectors[$grIdx][0]-$numberOfItems));
			$result = gmp_mul($result, $count);
		}
		return $result;		
	}

	
	
	/* ------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------ */
	
	
	
	private function generateGroupPools (array $rangeVectors) {
		
		
		$sumItemGroupVectors = array_fill (0, count($rangeVectors["min"]), 0);
		
		$minPool = array_fill (0, count($this->itemGroupVectors), 0);
		$maxPool = array_fill (0, count($this->itemGroupVectors), 0);
		
		for ($index = 0; $index < count($this->itemGroupVectors); $index += 1) {
			$maxPool[$index] = $this->itemGroupVectors[$index][0];
			foreach ($this->itemGroupVectors[$index] as $dim => $val) {
				$sumItemGroupVectors[$dim] += ($dim==0) ? $val : $this->itemGroupVectors[$index][0]*$val;
			}
		}
		
		
		
		for ($index = 0; $index < count($this->itemGroupVectors); $index += 1) {
			foreach ($this->itemGroupVectors[$index] as $dim => $val) {
				
				$number = ($dim==0) ? $val : $this->itemGroupVectors[$index][0]*$val;
				
				if ($sumItemGroupVectors[$dim]-$number < $rangeVectors["min"][$dim]) {
					$min = $rangeVectors["min"][$dim] - $sumItemGroupVectors[$dim] + $number;
					$minPool[$index] = max ($minPool[$index], $min);
				}
			}
		}
		
		
		
		
		$this->groupPools = [];
		
		
		// pool = [g_0, g_1, ..., g_(n-1)] where g_k is the number of items in the pool of item group k
		// n = number of item groups
		$currentPool = $minPool; // array_fill (0, count($this->itemGroupVectors), 0);
		
		// values = [v_0, v_1, ..., v_(m-1)] where v_k is the number of items that fulfill condition (with index) k
		// m = number of criteria
		// condition v_0 = number of items overall
		$currentValues = array_fill (0, count($rangeVectors["min"]), 0);
		for ($index = 0; $index < count($this->itemGroupVectors); $index += 1) {
			foreach ($this->itemGroupVectors[$index] as $dim => $val) {
				$currentValues[$dim] += ($dim==0) ? $currentPool[$index] : $currentPool[$index]*$val;
			}
		}
		
		
		for ($index = 0; $index < count($this->itemGroupVectors); $index += 1) {
			foreach ($this->itemGroupVectors[$index] as $dim => $val) {
				
				$number_max = ($dim==0) ? $maxPool[$index] : $maxPool[$index]*$val;
				$number_min = ($dim==0) ? $minPool[$index] : $minPool[$index]*$val;
				
				if ($currentValues[$dim] - $number_min + $number_max > $rangeVectors["max"][$dim]) {
					$maxPool[$index] = $rangeVectors["max"][$dim] - $currentValues[$dim] + $number_min;
				}
			}
		}
		
		
		
		
		
		
		
		
		$currentGroupIndex = count($currentPool)-1;
		
		
		// 		$currentGroupIndex = count($currentPool)-1;
		$poolRESET = FALSE;
		
		
		$time_start = time();
		$time_break = $time_start + 60;
		
		
		while (true) {
			
			// 			if (time()>$time_break) break;
			
			// if reset: add at least so many items as int $rangeVectors["min"][0] = minimum number of all items
			if ($poolRESET) {
				
				$noOfItemsToAdd = $rangeVectors["min"][0];
				for ($index = 0; $index<=$currentGroupIndex; $index+=1) {
					$noOfItemsToAdd -= $currentPool[$index];
				}
				
				$newCurrentGroupIndex = $currentGroupIndex;
				
				for ($index = count($this->itemGroupVectors)-1; $index>=0; $index-=1) {
					
					$vmax = $maxPool[$index] /*$this->itemGroupVectors[$index][0]*/; // max number of items in this group
					if ($index > $currentGroupIndex) {
						
						if ($noOfItemsToAdd>$minPool[$index]) {
							$vnew = min ($vmax, $noOfItemsToAdd);
							$noOfItemsToAdd -= $vnew;
							$newCurrentGroupIndex = $index;
						} else {
							$vnew = $minPool[$index];	// reset to min value of this item group
							$noOfItemsToAdd -= $vnew;
						}
					} else {
						if ($noOfItemsToAdd>0) {
							$vnew = $currentPool[$index] + min ($vmax-$currentPool[$index], $noOfItemsToAdd);
							$noOfItemsToAdd -= $vnew;
							$newCurrentGroupIndex = $index;
						} else {
							break;
						}
					}
					
					$add = $vnew - $currentPool[$index];
					
					// adjust current values
					foreach ($this->itemGroupVectors[$index] as $dim => $val) {
						$currentValues[$dim] += ($dim==0) ? $add : $add*$val;
					}
					$currentPool[$index] = $vnew;
					
				}
				
				$currentGroupIndex = $newCurrentGroupIndex;
			}
			
			
			// check current pool
			$poolIsOk = TRUE;
			$poolIsTooLarge = FALSE;
			$poolRESET = FALSE;
			foreach ($currentValues as $dim => $value) {
				if ($value < $rangeVectors["min"][$dim])  {
					$poolIsOk = FALSE;
					if ($poolIsTooLarge) break;
				}
				if ($value > $rangeVectors["max"][$dim]) {
					$poolIsOk = FALSE;
					$poolIsTooLarge = TRUE;
					break;
				}
			}
			
			// if ok --> add to result
			if ($poolIsOk) {
				$this->groupPools[] = $currentPool;
			}
			
			// if pool is too large OR we are at the end and cannot increase anymore in this group --> step back
			if (  ($poolIsTooLarge) || ($currentPool[$currentGroupIndex] == $maxPool[$currentGroupIndex] /* $this->itemGroupVectors[$currentGroupIndex][0]*/  )) {
				
				$poolRESET = TRUE;
				
				do {
					
					/*
					 // remove all items from current group
					 foreach ($this->itemGroupVectors[$currentGroupIndex] as $dim => $val) {
					 $currentValues[$dim] -= ($dim==0) ? $currentPool[$currentGroupIndex] : $currentPool[$currentGroupIndex]*$val;
					 }
					 $currentPool[$currentGroupIndex] = 0;
					 */
					
					// step one group back
					$currentGroupIndex -= 1;
					if ($currentGroupIndex < 0) return;		// no more group --> EXIT
					
				} while ($currentPool[$currentGroupIndex] == $maxPool[$currentGroupIndex] /*$this->itemGroupVectors[$currentGroupIndex][0]*/ );
				
			}
			
			// add one item of current group
			$currentPool[$currentGroupIndex] += 1;
			foreach ($this->itemGroupVectors[$currentGroupIndex] as $dim => $val) {
				$currentValues[$dim] += ($dim==0) ? 1 : $val;	// $val is either 0 or 1; $dim=0 counts the number of items
			}
			
			
		}
		
	}
	
	
	
	
	/**
	 *
	 * @param GMP $start 0<=$start<N where N is the overall number of item pools (getNumberOfItemPools)
	 * @param int $countc number of item pools to be returned (e.g., 10)
	 */
	
	
	public function getItemPools (GMP $start, GMP $count) {
		
		// find first groupPool that contributes (at least) one item pool to the result
		$current = gmp_init(0);
		$groupPoolIndex=0;
		do {
			$size = $this->getNumberOfItemPoolsInGroup($groupPoolIndex);
			if ($current + $size <= $start) {
				$current = gmp_add($current, $size);
				$groupPoolIndex += 1;
			} else {
				break;
			}
		} while (true);
		
		
		// $groupPoolIndex is now the index of the first pool that need to be considered
		// $current is the number of skipped item pools so far
		// $size is the number of item pools for $groupPoolIndex
		
		$result = [];
		
		$groupStart = gmp_sub($start, $current);	// start in groupPool
		
		
		do {
			$groupNumberOfAvailPools = gmp_sub ($size, $groupStart);
			
			if (gmp_cmp($groupNumberOfAvailPools, $count)>=0) {		// group pool has enough item pools
				$result = array_merge ($result, $this->getItemPoolsInGroup ($groupPoolIndex, $groupStart, $count));
				return $result;
			} else {
				$result = array_merge ($result, $this->getItemPoolsInGroup ($groupPoolIndex, $groupStart, $groupNumberOfAvailPools));
				
				// go to next pool
				$groupPoolIndex += 1;
				if ($groupPoolIndex >= count($this->groupPools)) return $result; 	// no more group pool available
				
				$count = gmp_sub ($count, $groupNumberOfAvailPools);	// number remaining item pools to generate
				$groupStart = gmp_init(0);	// we start all pools except the first from the beginning
				$size = $this->getNumberOfItemPoolsInGroup($groupPoolIndex);	// size of the new group pool
				
				
			}
		} while (true);
	}
	
	
	
	private function getItemPoolsInGroup (int $groupPoolIndex, GMP $start, GMP $count) {
		
		$result = [];
		$currentItemPool = [];
		foreach ($this->groupPools[$groupPoolIndex] as $itemGroupIdx => $numberOfItems) {
			$currentItemPool[] = ($numberOfItems==0) ? [] : range (0, $numberOfItems-1);
		}
		
		// skip the first start entries
		$i = gmp_init(0);
		while (gmp_cmp($i, $start)<0) {
			$currentItemPool = $this->getNextItemPool($groupPoolIndex, $currentItemPool);
			$i = gmp_add($i, gmp_init(1));
		}
		
		// collect the following count entries
		$i = gmp_init(0);
		while (gmp_cmp($i, $count)<0) {
			
			$resultPool = [];
			foreach ($this->groupPools[$groupPoolIndex] as $itemGroupIdx => $numberOfItems) {
				foreach ($currentItemPool[$itemGroupIdx] as $x) {
					$resultPool[] = $this->itemGroups[$itemGroupIdx][$x];
				}
			}
			$result[] = $resultPool;
			
			$currentItemPool = $this->getNextItemPool($groupPoolIndex, $currentItemPool);
			
			$i = gmp_add($i, gmp_init(1));
		}
		
		return $result;
	}
	
	
	private function getNextItemPool (int $groupPoolIndex, array $currentItemPool) {
		
		for ($itemGroupIdx = count($currentItemPool)-1; $itemGroupIdx>=0; $itemGroupIdx=$itemGroupIdx-1) {
			
			$numberOfItems = $this->groupPools[$groupPoolIndex][$itemGroupIdx];
			if ($numberOfItems==0) continue;
			$currentItemPool[$itemGroupIdx] = $this->getNextSet ($currentItemPool[$itemGroupIdx], $this->itemGroupVectors[$itemGroupIdx][0]);
			if (count($currentItemPool[$itemGroupIdx])>0) return $currentItemPool;
			
			$currentItemPool[$itemGroupIdx] = range (0, $numberOfItems-1);
		}
		
		return [];
	}
	
	
	private function getNextSet (array $current, int $max) : array {
		
		for ($i = count($current)-1; $i>=0; $i=$i-1) {
			
			if ($current[$i] < $max - count($current) + $i) {
				$current[$i] = $current[$i]+1;
				
				for ($k = $i+1; $k<count($current); $k=$k+1) {
					$current[$k] = $current[$i] + ($k-$i);
				}
				return $current;
			}
		}
		
		return [];
	}
	
}
?>