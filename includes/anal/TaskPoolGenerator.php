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
	
	
	
	private function generateDimensionsPool (array $items, array $itemids, array $dimensionsAll) {
		
		$result = ["number" => $dimensionsAll["number"]];
		$sizes = [];
		
		foreach ($dimensionsAll as $category => $minmax) {
			if ($category=="number") continue;
			
			$sizes[$category] = 0;
			foreach (ItemExplorer::groupBy($items, $itemids, $category) as $key => $groupItemIds) {
				
				// ignore condidtions with min=0 and max=all
				if (($minmax["min"][$key]==0) && ($minmax["max"][$key]==count($groupItemIds))) continue;

				$sizes[$category] += 1;
			}
			
			if ($sizes[$category] == 0) {
				unset ($sizes[$category]);
			}
		}
		
		if (count($sizes)==0) return $result;
		
		// add largest category
		arsort($sizes);
		reset($sizes);
		$category = key($sizes);
		$result[$category] = $dimensionsAll[$category];
		
		// add smallest categories as long as combinations do not execeed threshold 20
		$overall = $sizes[$category];
		unset($sizes[$category]);
		asort($sizes);
		foreach ($sizes as $category => $size) {
			if ($overall*$size<20) {
				$overall = $overall * $size;
				$result[$category] = $dimensionsAll[$category];
			} else {
				break;
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
		$_SESSION["tpg_numberOfItemPools"] = "-1";
		
		if (($dimensionsAll["number"]["min"]["all"] == 0) || ($dimensionsAll["number"]["max"]["all"] == 0)) {
			return;
		}
		
		
		
		
		$items = EAL_ItemBasket::getItems();
		$itemids = ItemExplorer::getItemIds($items);
		
		$dimensionsPool = $this->generateDimensionsPool($items, $itemids, $dimensionsAll);
		
		$dimensionsToCheck = array_diff_key ($dimensionsAll, $dimensionsPool);
		if (count($dimensionsToCheck)>0) {
			$dimensionsToCheck = array_merge (["number" => $dimensionsAll["number"]], $dimensionsToCheck);
		}
		
		
		list ($itemVectors, $rangeVectors, $mapItemId2ItemIndex) = $this->generateItemVectorsAndRangeVectors($items, $itemids, $dimensionsPool);
		list ($this->itemGroups, $this->itemGroupVectors) = $this->generateItemGroups($itemids, $itemVectors);
		
		$this->groupPools = $this->generateGroupPools2 ($this->itemGroupVectors, $rangeVectors, $maxTime);
		
		$_SESSION["tpg_dimensions_All"] = $dimensionsAll;
		$_SESSION["tpg_dimensions_Pool"] = $dimensionsPool;
		$_SESSION["tpg_dimensions_ToCheck"] = $dimensionsToCheck;
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
		$dimensionsToCheck = $_SESSION["tpg_dimensions_ToCheck"];
		
		if (count($dimensionsToCheck)>0) {
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
	
	

	
	
	// Computes the overall number of item tools based on the generated grouped pools
	private function getNumberOfItemPools () {
		
		$result = 0;;
		for ($groupPoolIndex=0; $groupPoolIndex<count($this->groupPools); $groupPoolIndex++) {
			$result += $this->getNumberOfItemPoolsInGroup ($groupPoolIndex);
		}
		return $result;
	}
	
	
	// Computes the number of item tools for a specific grouped pools
	private function getNumberOfItemPoolsInGroup (int $groupPoolIndex) {
		
		$result = 1;
		foreach ($this->groupPools[$groupPoolIndex] as $grIdx => $numberOfItems) {
			// $count = "number" out of "all in group" = all! / (all-number)! * number!
			
			$min = min ($this->itemGroupVectors[$grIdx][0]-$numberOfItems, $numberOfItems);
			$max = max ($this->itemGroupVectors[$grIdx][0]-$numberOfItems, $numberOfItems);
			
			$count = 1;
			for ($x=$max+1; $x<=$this->itemGroupVectors[$grIdx][0]; $x+=1) {
				$count = $count*$x;
			}
			for ($x=2; $x<=$min; $x+=1) {
				$count = $count/$x;
			}
			$result = $result*$count;
		}
		return $result;
	}
	
	
/*	
	// Computes the overall number of item tools based on the generated grouped pools
	private function gmp_getNumberOfItemPools (): GMP {
		
		$result = gmp_init(0);
		for ($groupPoolIndex=0; $groupPoolIndex<count($this->groupPools); $groupPoolIndex++) {
			$result = gmp_add($result, $this->gmp_getNumberOfItemPoolsInGroup ($groupPoolIndex));
		}
		return $result;
	}

	
	// Computes the number of item tools for a specific grouped pools
	private function gmp_getNumberOfItemPoolsInGroup (int $groupPoolIndex): GMP {
		
		$result = 1;
		foreach ($this->groupPools[$groupPoolIndex] as $grIdx => $numberOfItems) {
			// $count = "number" out of "all in group" = all! / (all-number)! * number!
			$count = gmp_fact($this->itemGroupVectors[$grIdx][0]) / (gmp_fact($numberOfItems) * gmp_fact($this->itemGroupVectors[$grIdx][0]-$numberOfItems));
			$result = gmp_mul($result, $count);
		}
		return $result;		
	}
*/
		
}
?>