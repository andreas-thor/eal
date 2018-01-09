<?php

require_once ("ItemExplorer.php");

class TaskPoolGenerator {
	
	private $groupPools;
	private $itemGroupVectors;
	private $itemGroups;
	
	

	function __construct () {
		
		$this->groupPools = $_SESSION["tpg_groupPools"];
		$this->itemGroupVectors = $_SESSION["tpg_itemGroupVectors"];
		$this->itemGroups = $_SESSION["tpg_itemGroups"];
	}
	
	private function init (array $dimensions): array {
		
		
		
		$items = EAL_ItemBasket::getItems();
		$itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
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
		
		// we group together items with the same vector
		$this->itemGroupVectors = array();
		$this->itemGroups = array();
		foreach ($itemVectors as $index => $itvec) {
			
			$isnew = TRUE;
			// can we find a matching group vector $grvec for the current item vector $itvec?
			foreach ($this->itemGroupVectors as $pos => $grvec) {
				
				$diff = array_diff_assoc ($itvec, $grvec);	
				unset($diff[0]);	// we ignore the first entry (==1 for items; ==count for group vector)
				
				if (count($diff)==0) {	// no difference --> match!
					$isnew = FALSE;
					$this->itemGroupVectors[$pos][0] = $this->itemGroupVectors[$pos][0]+1;
					$this->itemGroups[$pos][] = $itemids[$index];
					break;
				}
			}
			
			if ($isnew) {
				$this->itemGroupVectors[] = $itvec;
				$this->itemGroups[] = [$itemids[$index]];
			}
		}
		
		return $rangeVectors;
		
	}
	
	
	
	private function generateGroupPools (array $rangeVectors) {
		
		$this->groupPools = [];
		
		$currentPool = array_fill (0, count($this->itemGroupVectors), 0);
		$currentValues = array_fill (0, count($rangeVectors["min"]), 0);
		
		$currentGroupIndex = count($this->itemGroupVectors)-1;
		
		
		while (true) {
			
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
			if (  ($poolIsTooLarge) || ($currentPool[$currentGroupIndex] == $this->itemGroupVectors[$currentGroupIndex][0])  ) {
				
				$poolRESET = TRUE;
				
				do { 
					
					// remove all items from current group
					foreach ($this->itemGroupVectors[$currentGroupIndex] as $dim => $val) {
						$currentValues[$dim] -= ($dim==0) ? $currentPool[$currentGroupIndex] : $currentPool[$currentGroupIndex]*$val;
					}
					$currentPool[$currentGroupIndex] = 0;

					// step one group back
					$currentGroupIndex -= 1;
					if ($currentGroupIndex < 0) return;		// no more group --> EXIT
					
				} while ($currentPool[$currentGroupIndex] == $this->itemGroupVectors[$currentGroupIndex][0]);
				
			}
			
			// add one item of current group
			$currentPool[$currentGroupIndex] += 1;
			foreach ($this->itemGroupVectors[$currentGroupIndex] as $dim => $val) {
				$currentValues[$dim] += ($dim==0) ? 1 : $val;	// $val is either 0 or 1; $dim=0 counts the number of items
			}
				
			if ($poolRESET) {
				$currentGroupIndex = count($currentPool)-1;
			} 
		}
		
		
	}
	
	
	/**
	 * $dimensions: [ name of category => [ "min" => [ category value => min number], "max => [ category value => max number ] ] ]  
	 * @param array $dimensions
	 */
	public function generatePools (array $dimensions) {
		
		
		$this::generateGroupPools($this::init($dimensions));
		
		$_SESSION["tpg_groupPools"] = $this->groupPools;
		$_SESSION["tpg_itemGroupVectors"] = $this->itemGroupVectors;
		$_SESSION["tpg_itemGroups"] = $this->itemGroups;
		$_SESSION["tpg_numberOfItemPools"] = $this->getNumberOfItemPools();
		
		return $_SESSION["tpg_numberOfItemPools"];
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

	
}
?>