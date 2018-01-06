<?php

require_once ("ItemExplorer.php");

class TaskPoolGenerator {
	
	private $result;
	
	private $itemVectors;
	private $itemGroupVectors;
	private $itemGroups;
	
	private $rangeVectors;

	private $maxtime;
	

	
	
	function __construct ($maxtime) {
		$this->maxtime = $maxtime;
	}
	
	private function init (array $dimensions) {
		
		
		
		$items = EAL_ItemBasket::getItems();
		$itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		$mapItemId2ItemIndex = [];	// item id -> itemn index (0 ... count(items)-1)
		$this->itemVectors = array();
		
		foreach ($itemids as $index => $id) {
			$mapItemId2ItemIndex[$id] = $index;
			$this->itemVectors[] = array();
		}
		
		// every condition is represented as position in vector
		// item vector contains 0 or 1 depending on if the items is in this group
		// range vector: min / max contain the min / max range condition; all the number of all items with a 1 in this position
		
		
		
		$this->rangeVectors = ["min" => array(), "max" => array(), "all" => array()];
		
		$countCriteria = 0;
		foreach ($dimensions as $category => $minmax) {
			
			foreach (ItemExplorer::groupBy($items, $itemids, $category) as $key => $groupItemIds) {
				$countCriteria++;
				foreach ($groupItemIds as $id) {
					$this->itemVectors[$mapItemId2ItemIndex[$id]][] = 1;
				}
				foreach ($itemids as $id) {
					if (count($this->itemVectors[$mapItemId2ItemIndex[$id]]) < $countCriteria) {
						$this->itemVectors[$mapItemId2ItemIndex[$id]][] = 0;
					}
				}
				$this->rangeVectors["min"][] = $minmax["min"][$key];
				$this->rangeVectors["max"][] = $minmax["max"][$key];
				$this->rangeVectors["all"][] = count($groupItemIds);
			}
		}
		
		// we group together items with the same vector
		$this->itemGroupVectors = array();
		$this->itemGroups = array();
		foreach ($this->itemVectors as $index => $itvec) {
			
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
		
	}
	
	
	
	private function generateGroupPools () {
		
		$this->result = [];
		
		$currentPool = array_fill (0, count($this->itemGroupVectors), 0);
		$currentValues = array_fill (0, count($this->rangeVectors["min"]), 0);
		
		$currentVectorId = count($this->itemGroupVectors)-1;
		
		
		while (true) {
			
			// check current pool
			$poolIsOk = TRUE; 
			$poolIsTooLarge = FALSE;
			foreach ($currentValues as $dim => $value) {
				if ($value < $this->rangeVectors["min"][$dim])  {
					$poolIsOk = FALSE;
				}
				if ($value > $this->rangeVectors["max"][$dim]) {
					$poolIsOk = FALSE;
					$poolIsTooLarge = TRUE;
				}
			}
			
			// if ok --> add to result
			if ($poolIsOk) {
				$this->result[] = $currentPool;
			}
			
			// if pool is too large OR we are at the end and cannot increase anymore --> step back
			if (  ($poolIsTooLarge) || (($currentVectorId == count($currentPool)-1) && ($currentPool[$currentVectorId] == $this->itemGroupVectors[$currentVectorId][0]))  ) {
				do { 
					foreach ($this->itemGroupVectors[$currentVectorId] as $dim => $val) {
						$currentValues[$dim] -= ($dim==0) ? $currentPool[$currentVectorId] : $currentPool[$currentVectorId]*$val;
					}
					$currentPool[$currentVectorId] = 0;
					$currentVectorId -= 1;
					if ($currentVectorId < 0) break;
					
				} while ($currentPool[$currentVectorId] == $this->itemGroupVectors[$currentVectorId][0]); 
			} else {
				if ($currentVectorId < count($currentPool)-1) {
					$currentVectorId += 1;
				}
			}
			
			if ($currentVectorId < 0) break;	// nothing to step back --> break while-true-loop
			
			// add current vector
			$currentPool[$currentVectorId] += 1;
			foreach ($this->itemGroupVectors[$currentVectorId] as $dim => $val) {
				$currentValues[$dim] += ($dim==0) ? 1 : $val;
			}
		}
		
		
	}
	
	
	/**
	 * $dimensions: [ name of category => [ "min" => [ category value => min number], "max => [ category value => max number ] ] ]  
	 * @param array $dimensions
	 */
	public function generatePools (array $dimensions) {
		
		
		$this::init($dimensions);
		$this::generateGroupPools();
		
		
		$itemPools = [];
		$noOfPools = 0;
		
		foreach ($this->result as $groupPool) {
		
			$size = 1;
			foreach ($groupPool as $grIdx => $number) {
				// $count = $number out of all in group  
				$count = gmp_fact($this->itemGroupVectors[$grIdx][0]) / (gmp_fact($number) * gmp_fact($this->itemGroupVectors[$grIdx][0]-$number));
				$size = gmp_mul($size, $count);
			}
			
			$noOfPools = gmp_add($noOfPools, $size);
			
		}
		return $noOfPools;
		

	}
	

	
	private function faculty (int $n) {
		$res = 1;
		for ($i=1; $i<=$n; $i+=1) {
			$res = gmp_mul($res, $i);
		}
		return (int) $res;
	}

	
	}

?>