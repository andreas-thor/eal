<?php 

require_once __DIR__ . '/../class.CLA_RoleTaxonomy.php';
require_once 'EAL_Level.php';

abstract class EAL_Object {
	
	private $id;		// set/get make sure that integer values are stored only
	private $domain;	// each item belongs to a domain (when newly created: domain = current user's role domain)
	private $level;		// level of type EAL_Level	
	
	
	function __construct (int $id) {
		
		$this->id = $id;
		$this->domain = RoleTaxonomy::getCurrentDomain() ?? '';
		$this->level = new EAL_Level();
	}
	
	
	/**
	 * 
	 * @param array $object = ['post_ID' => ..., 'domain' => ...
	 * @param string $prefix
	 * @param string $levelPrefix
	 */
	public function initFromArray (array $object, string $prefix, string $levelPrefix) {
		
		// FIXME: do not overwrite ID
		if (isset($object[$prefix . 'post_ID'])) {
			$this->id = intval ($object[$prefix . 'post_ID']);
		}
		
		if (isset($object[$prefix . 'domain'])) {
			$this->domain = $object[$prefix . 'domain'];
		}
		
		// adjust domain if necessary ... FIXME: WHY and WHEN
		if (($this->domain === '') && (isset($object[$prefix . 'tax_input']))) {
			foreach ($object[$prefix . 'tax_input'] as $key => $value) {
				$this->domain = $key;
				break;
			}
		}
		
		$this->level->initFromArray($object, $prefix . $levelPrefix);
		
	}
	
	
	public function convertToArray (string $prefix, string $levelPrefix): array {
		$object = [];
		$object[$prefix . 'post_ID'] = $this->getId();
		$object[$prefix . 'post_type'] = $this->getType(); 
		$object[$prefix . 'domain'] = $this->getDomain();
		return array_merge ($object, $this->level->convertToArray($prefix . $levelPrefix));
	}
	
	
	public function getId (): int {
		return $this->id;
	}
	
	/*
	 * Id must be an integer, != 0
	 */
	
	public function setId ($id) {
		$this->id = intval($id);
		if ($this->id==0) {
			$this->id = -1;
		}
	}
	
	
	public static abstract function getType(): string; 
	

	public function getDomain(): string {
		return $this->domain;
	}
	
	public function setDomain (string $domain)  {
		$this->domain = $domain ?? "";
	}
	
	public function getLevel (): EAL_Level {
		return $this->level;
	}
	
	public function setLevel (EAL_Level $level) {
		$this->level = $level;
	}
	
}
?>