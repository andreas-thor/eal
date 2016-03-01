<?php



class EAL_Item {

	public $type;	// will be set in subclasses (EAL_ItemMC, EAL_ItemSC, ...)
	
	public $id;
	public $title;
	public $description;
	public $question;
	
	public $level;
	
	public static $level_label = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
	
	
	function __construct() {
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
		$this->description = isset($_POST['item_description']) ? $_POST['item_description'] : null;
		$this->question = isset ($_POST['item_question']) ? $_POST['item_question'] : null;

		$this->level["FW"] = isset ($_POST['item_level_FW']) ? $_POST['item_level_FW'] : null;
		$this->level["KW"] = isset ($_POST['item_level_KW']) ? $_POST['item_level_KW'] : null;
		$this->level["PW"] = isset ($_POST['item_level_PW']) ? $_POST['item_level_PW'] : null;
	}
	
	
	
	
	public function load () {
		
		global $post;
		
		echo ("<script>console.log('__construct in " . get_class() . " with status== " . (get_post_status($post->ID)) . "');</script>");
		
		if (get_post_status($post->ID)=='auto-draft') {
				
			$this->id = $post->ID;
			$this->title = '';
			$this->description = '';
			$this->question = '';
			$this->level["FW"] = 0;
			$this->level["KW"] = 0;
			$this->level["PW"] = 0;
				
		} else {
				
			global $wpdb;
			$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$this->type} WHERE id = {$post->ID}", ARRAY_A);
			$this->id = $sqlres['id'];
			$this->title = $sqlres['title'];
			$this->description = $sqlres['description'];
			$this->question = $sqlres['question'];
			$this->level["FW"] = $sqlres['level_FW'];
			$this->level["KW"] = $sqlres['level_KW'];
			$this->level["PW"] = $sqlres['level_PW'];
				
		}
		
	}
	
	
	public function loadById ($item_id) {
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$this->type} WHERE id = {$item_id}", ARRAY_A);
		$this->id = $sqlres['id'];
		$this->title = $sqlres['title'];
		$this->description = $sqlres['description'];
		$this->question = $sqlres['question'];
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
	}
	
	
	public function getPoints() { return -1; }
	
	public function getPreviewHTML () { return "<h1>getPreviewHTML () not implemented</h1>"; }
	
	
	
	public static function createTableItem($tabname) {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		dbDelta (
			"CREATE TABLE {$tabname} (
				id bigint(20) unsigned NOT NULL,
				title text,
				description text,
				question text,
				answer text,
				level tinyint unsigned,
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				points smallint,
				PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
	public static function createTableReview($tabname) {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		$sqlScore = "";
		foreach (EAL_Review::$dimension1 as $k1 => $v1) {
			foreach (EAL_Review::$dimension2 as $k2 => $v2) {
				$sqlScore .= "{$k1}_{$k2} tinyint unsigned, \n";
			}
		}
	
		dbDelta (
			"CREATE TABLE {$tabname} (
				id bigint(20) unsigned NOT NULL,
				item_id bigint(20) unsigned NOT NULL, {$sqlScore}
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				feedback text,
				overall tinyint unsigned,
				KEY  (item_id),
				PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
	
}

?>