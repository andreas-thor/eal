<?php



class EAL_LearnOut {

	public $type;	// will be set in subclasses (EAL_ItemMC, EAL_ItemSC, ...)
	public $domain;	// each LO belongs to a domain (when newly created: domain = current user's role domain)
	
	public $id;
	public $title;
	public $description;
	public $question;
	
	public $level;
	
	public static $level_label = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
	
	
	function __construct() {
		$this->type = 'learnout';
		$this->level = ["FW" => null, "KW" => null, "PW" => null];
	}
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	public function init ($post_id, $post) {
	
		$this->id = $post_id;
		$this->title = $post->post_title;
		$this->description = isset($_POST['learnout_description']) ? $_POST['learnout_description'] : null;
		$this->level["FW"] = isset ($_POST['learnout_level_FW']) ? $_POST['learnout_level_FW'] : null;
		$this->level["KW"] = isset ($_POST['learnout_level_KW']) ? $_POST['learnout_level_KW'] : null;
		$this->level["PW"] = isset ($_POST['learnout_level_PW']) ? $_POST['learnout_level_PW'] : null;
		$this->domain = RoleTaxonomy::getCurrentDomain()["name"];
	}
	
	
	
	
	public function load () {
		
		global $post;
		
		if (get_post_status($post->ID)=='auto-draft') {
				
			$this->id = $post->ID;
			$this->title = '';
			$this->description = 'Die Studierenden sind nach Abschluss der Lehrveranstaltung in der Lage ...';
			$this->level["FW"] = 0;
			$this->level["KW"] = 0;
			$this->level["PW"] = 0;
			$this->domain = RoleTaxonomy::getCurrentDomain()["name"];
				
				
		} else {
			$this->loadById($post->ID);
		}
		
	}
	
	
	public function loadById ($item_id) {
		
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$this->type} WHERE id = {$item_id}", ARRAY_A);
		$this->id = $sqlres['id'];
		$this->title = $sqlres['title'];
		$this->description = $sqlres['description'];
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
		$this->domain = $sqlres['domain'];
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
	
	
	public static function save ($post_id, $post) {
	
		global $wpdb;
		$item = new EAL_LearnOut();
		$item->init($post_id, $post);
	
		$wpdb->replace(
			"{$wpdb->prefix}eal_{$item->type}",
			array(
					'id' => $item->id,
					'title' => $item->title,
					'description' => $item->description,
					'level_FW' => $item->level["FW"],
					'level_KW' => $item->level["KW"],
					'level_PW' => $item->level["PW"],
					'domain' => $item->domain
			),
			array('%d','%s','%s','%d','%d','%d','%s')
		);
	
	}
	
	
	public static function getListOfLearningOutcomes ($learnout_id) {
		
		global $wpdb;
		$sqlres = $wpdb->get_results( "
				SELECT L.id, L.title
				FROM {$wpdb->prefix}eal_learnout L
				JOIN {$wpdb->prefix}posts P
				ON (L.id = P.id)
				WHERE P.post_status = 'publish'
				AND L.domain = '" . RoleTaxonomy::getCurrentDomain()["name"] . "'
				ORDER BY id
				");
		
		$html .= "<select align='right' name='learnout_id'>";
		$html .= "<option value='0'" . (($learnout_id == 0) ? " selected" : "") . ">None</option>";
		foreach ($sqlres as $pos => $sqlrow) {
			$html .= "<option value='{$sqlrow->id}'" . (($learnout_id==$sqlrow->id) ? " selected" : "") . ">{$sqlrow->title}</option>";
		}
		$html .= "</select>";
		
		return $html;
	}
	
	
	public function getPreviewHTML ($forReview = TRUE) {
			
	
		$res  = sprintf ("<div onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\">");
		$res .= sprintf ("<h1 style='display:inline'>%s</span></h1>", $this->title, $this->id);
		$res .= sprintf ("<div style='display:none'><span><a href=\"post.php?action=edit&post=%d\">Edit</a></span></div>", $this->id);
		$res .= sprintf ("</div><br/>");
		$res .= sprintf ("<div>%s</div>", $this->description);
		$res .= CPT_Object::getLevelHTML('learnout_' . $this->id, $this->level, null, "disabled", 0, '');
		$res .= "<br/>";
	
		return $res;
			
	}
	
}

?>