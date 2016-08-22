<?php

class ALG_Item_Pool {
	
	
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
	
	
}

?>