<?php

/**
 * 
 * @author Andreas Thor
 *
 */

class EAL_Level {
	
	const LABEL = ['1' => 'Erinnern', '2' => 'Verstehen', '3' => 'Anwenden', '4' => 'Analysieren', '5' => 'Evaluieren', '6' => 'Erschaffen'];
	
	/**
	 * Dimension types, i.e., FW, KW, PW
	 * @var array
	 */
	const TYPE = ['FW', 'KW', 'PW'];	// dimensions

	private $level;

		
	function __construct() {
		
		$this->level = [];
		foreach (EAL_Level::TYPE as $type) {
			$this->level[$type] = 0;
		}
	}
	
	
	public function initFromArray (array $object, string $prefix) {

		foreach (EAL_Level::TYPE as $type) {
			if (isset ($object[$prefix . $type])) {
				$this->level[$type] = intval ($object[$prefix . $type]);
			}
		}
	}
	
	function get (string $type): int {
		
		$type = strtoupper ($type);
		if (in_array($type, EAL_Level::TYPE)) {
			return $this->level[$type];
		}
		throw new Exception('Unknown level type: ' . $type);
	}
	
	
	function hasLevel (int $value): bool {
		
		foreach (EAL_Level::TYPE as $type) {
			if ($this->level[$type] === $value) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	
}

?>