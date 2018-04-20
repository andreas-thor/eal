<?php

require_once 'EAL_Object.php';
require_once __DIR__ . '/../html/HTML_LearnOut.php';

class EAL_LearnOut extends EAL_Object  {

	private $title;
	private $description;
	
	function __construct(int $id = -1, array $object = NULL, string $prefix = '', string $level_prefix = '') {
		parent::__construct($id, $object, $prefix, $level_prefix);
		$this->title = '';
		$this->description = 'Die Studierenden sind nach Abschluss der Lehrveranstaltung in der Lage ...';
	}
	
	public static function createFromArray (int $id, array $object, string $prefix = ''): EAL_LearnOut {
		$learnout = new EAL_LearnOut($id);
		$learnout->initFromArray($object, $prefix);
		return $learnout;
	}
	
	/**
	 * 
	 * @param array $object = ['post_title' => ..., 'learnout_description' => ...
	 * @param string $prefix
	 * @param string $levelPrefix
	 */
	protected function initFromArray (array $object, string $prefix, string $levelPrefix='learnout_level_') {

		parent::initFromArray($object, $prefix, $levelPrefix);
		$this->title = stripslashes($object[$prefix . 'post_title'] ?? '');
		$this->description = html_entity_decode (stripslashes($object[$prefix . 'learnout_description'] ?? ''));
	}
	
	
	public static function getType(): string {
		return 'learnout';
	}
	
	
	public function setTitle(string $title) {
		$this->title = $title;
	}

	public function setDescription(string $description) {
		$this->description = $description;
	}

	public function getTitle (): string {
		return $this->title;
	}
	
	public function getDescription (): string {
		return $this->description;
	}
	
	

	
	public function copyMetadata (EAL_LearnOut $sourceLO) {
		$this->setLevel($sourceLO->getLevel());
	}
	
	
	
	public function getHTMLPrinter (): HTML_LearnOut {
		return new HTML_LearnOut($this);
	}
	
	
	/*	
	public static function save ($post_id, $post) {
		
		$lo = EAL_Factory::createNewLearnOut();
		if ($_POST["post_type"] != $lo->getType()) return;
		$lo->saveToDB();
	}
	
		

	function __construct(int $learnout_id=-1, string $prefix="") {

		parent::__construct();
		$this->setId (-1);
		$this->title = '';
		$this->description = 'Die Studierenden sind nach Abschluss der Lehrveranstaltung in der Lage ...';
		
		
		if ($learnout_id > 0) {
			$this->loadFromDB($learnout_id);
		} else {
			if ($_POST[$prefix."post_type"] == $this->getType()) {
				$this->loadFromPOSTRequest($prefix);
			} else {
				global $post;
					
				if ($post->post_type != $this->getType()) return;
				if (get_post_status($post->ID)=='auto-draft') {
					$this->setId ($post->ID);
				} else {
					$this->loadFromDB($post->ID);
				}
			}
		}	
	}
*/	
	

	
	/**
	 * Initialize learning outcome from _POST Request data
	 */
/*	
	protected function loadFromPOSTRequest (string $prefix="") {
	
		$this->setId ($_POST[$prefix."post_ID"]);
		$this->title = stripslashes($_POST[$prefix."post_title"]);
		$this->description = isset ($_POST[$prefix.'learnout_description']) ? html_entity_decode (stripslashes($_POST[$prefix.'learnout_description'])) : null;
		$this->level = new EAL_Level($_POST, $prefix.'learnout_level_');

		$this->setDomain($_POST[$prefix."domain"]);
		if (($this->getDomain() == "") && (isset($_POST[$prefix.'tax_input']))) {
			foreach ($_POST[$prefix.'tax_input'] as $key => $value) {
				$this->setDomain($key);
				break;
			}
		}
		
	}
	
	
		public function getItemIds (): array {
	
		global $wpdb;
		
		$sql ="
			SELECT DISTINCT P.id 
			FROM {$wpdb->prefix}eal_item I 
			JOIN {$wpdb->prefix}posts P ON (P.ID = I.ID) 
			WHERE P.post_parent = 0 
			AND P.post_status IN ('publish', 'pending', 'draft') 
			AND I.learnout_id = {$this->getId()}";
		
		return $wpdb->get_col ($sql);
	}	
*/
	
	

	
	

	
	

	
	
	


	
	
	
	/*
	public static function getListOfLearningOutcomes () {
		
		global $wpdb;
		return $wpdb->get_results( "
				SELECT L.id, L.title, L.description
				FROM {$wpdb->prefix}eal_learnout L
				JOIN {$wpdb->prefix}posts P
				ON (L.id = P.id)
				WHERE P.post_status = 'publish'
				AND L.domain = '" . RoleTaxonomy::getCurrentRoleDomain()["name"] . "'
				ORDER BY L.title
				", ARRAY_A);
	}
	
	
	protected function loadFromDB ($item_id) {
		
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$this->getType()} WHERE id = {$item_id}", ARRAY_A);
		$this->setId ($sqlres['id']);
		$this->title = $sqlres['title'];
		$this->description = $sqlres['description'];
		$this->level = new EAL_Level($sqlres);
		$this->setDomain($sqlres['domain']);
	}
	
	
	
	public function saveToDB() {
		
		global $wpdb;
		$wpdb->replace(
			"{$wpdb->prefix}eal_{$this->getType()}",
			array(
				'id' => $this->getId(),
				'title' => $this->title,
				'description' => $this->description,
				'level_FW' => $this->level->get('FW'),
				'level_KW' => $this->level->get('KW'),
				'level_PW' => $this->level->get('PW'),
				'domain' => $this->getDomain()
			),
			array('%d','%s','%s','%d','%d','%d','%s')
			);
	}
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_learnout (
				id bigint(20) unsigned NOT NULL,
				title mediumtext,
				description mediumtext,
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				domain varchar(50) NOT NULL,
				PRIMARY KEY  (id),
				KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
	*/
	
}

?>