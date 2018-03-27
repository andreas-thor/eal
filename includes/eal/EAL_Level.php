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

		
	function __construct(array $object = NULL, string $prefix = 'level') {
		
		$this->level = [];
		
		foreach (EAL_Level::TYPE as $type) {
			$this->level[$type] = ($object === NULL) ? 0 : ($object[$prefix . '_' . $type] ?? 0);
		}
	}
	
	function get (string $type): int {
		
		$type = strtoupper ($type);
		if (in_array($type, EAL_Level::TYPE)) {
			return $this->level[$type];
		}
		throw new Exception('Unknown level type: ' . $type);
	}
	
	
	function hasLevel (int $l): bool {
		
		foreach (EAL_Level::TYPE as $type) {
			if ($this->level[$type] == $l) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	
}

?>