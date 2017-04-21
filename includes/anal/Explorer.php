<?php


class Explorer {
	
	public static $category = [
			'type' => [
				'label' => 'Item Typ', 
				'values' => [
					'itemsc' => 'Single Choice', 
					'itemmc' => 'Multiple Choice'
				]
			], 
			'dim' => [
				'label' => 'Wissensdimension',
				'values' => [
					'FW' => 'FW',
					'KW' => 'KW',
					'PW' => 'PW'
				]
			], 
			'level' => [
				'label' => 'Anforderungsstufe',
				'values' => [
					1 => 'Erinnern', 
					2 => 'Verstehen', 
					3 => 'Anwenden', 
					4 => 'Analysieren', 
					5 => 'Evaluieren', 
					6 => 'Erschaffen'
				]
			],
			'topic1' => [
				'label' => 'Topic Level 1',
				'values' => NULL
			], 
			'topic2' => [
				'label' => 'Topic Level 2',
				'values' => NULL
			], 
			'difficulty' => [
				'label' => 'Schwierigkeitsgrad',
				'values' => [ 
					'0' 	=> '0%',
					'0.1'	=> '10%',
					'0.2'	=> '20%',
					'0.3'	=> '30%',
					'0.4'	=> '40%',
					'0.5'	=> '50%',
					'0.6'	=> '60%',
					'0.7'	=> '70%',
					'0.8'	=> '80%',
					'0.9'	=> '90%',
					'1'		=> '100%',
				]
			]
	];
	
	
	/**
	 * @param string $cat Category Identifier ('type', 'dim', 'level', 'topic1', 'topic2', or 'difficulty'
	 */
	public static function getLabel_Category (string $cat) {
		
		return self::$category[$cat];
	}
	
	
	public static function getLabel_CategoryValue (string $cat, $val) {
		
		$values = self::$category[$cat]['values'];
		if ($values != NULL) return $values[$val];
		return $val;	// $cat = topic1 OR topic2
	}
	
	
	public static function getAllCategoryValues (string $cat) {
		
		$values = self::$category[$cat]['values'];
		if ($values != NULL) return array_keys($values);
		
		if (($cat == "topic1") || ($cat == "topic2")) {
				
			$res = array();
			foreach (wp_get_post_terms($item->id, RoleTaxonomy::getCurrentRoleDomain()["name"]) as $term) {
		
				$termhier = $useTopicIds ? array($term->term_id) : array($term->name);
				$parentId = $term->parent;
				while ($parentId>0) {
					$parentTerm = get_term ($parentId, RoleTaxonomy::getCurrentRoleDomain()["name"]);
					$termhier = array_merge ($useTopicIds ? array ($parentTerm->term_id) : array ($parentTerm->name), $termhier);
					$parentId = $parentTerm->parent;
				}
		
				if (($cat=="topic1") && (!in_array ($termhier[0], $res))) array_push ($res, $termhier[0]);
		
				// for topic2: check if available AND if parent=topic1 is the same
				if (($cat=="topic2") && (count($termhier)>1) && ($termhier[0]==$parent) && (!in_array ($termhier[1], $res))) array_push ($res, $termhier[1]);
		
			}
			return $res;
				
				
		}		
	}
	
	
	
}

?>