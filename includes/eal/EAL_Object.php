<?php 

require_once (__DIR__ . "/../class.CLA_RoleTaxonomy.php");


class EAL_Object {
	
	private $id;		// set/get make sure that integer values are stored only
	private $type;		// read-only; will be set during constructor based on concrete class type (EAL_ItemMC, EAL_ItemSC, ...)
	private $domain;	// each item belongs to a domain (when newly created: domain = current user's role domain)
	
	
	
	function __construct () {
		
		if ($this instanceof EAL_ItemSC) 	$this->type = "itemsc";
		if ($this instanceof EAL_ItemMC) 	$this->type = "itemmc";
		if ($this instanceof EAL_LearnOut) 	$this->type = "learnout";
		if ($this instanceof EAL_Review) 	$this->type = "review";
		
		$this->setDomain(RoleTaxonomy::getCurrentRoleDomain()["name"]);
		
		
	}
	
	public function getId (): int {
		return $this->id;
	}
	
	/*
	 * Id must be an integer
	 */
	
	public function setId ($id) {
		$this->id = intval($id);
		if ($this->id==0) {
			$this->id = -1;
		}
	}
	
	
	public function getType(): string {
		return $this->type;
	}
	

	public function getDomain(): string {
		return $this->domain;
	}
	
	public function setDomain ($domain)  {
		$this->domain = $domain ?? "";
	}
	
	
}
?>