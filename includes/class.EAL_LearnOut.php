<?php



class EAL_LearnOut {

	public $type;	// will be set in subclasses (EAL_ItemMC, EAL_ItemSC, ...)
	
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
		$this->description = isset($_POST['item_description']) ? $_POST['item_description'] : null;
		$this->level["FW"] = isset ($_POST['item_level_FW']) ? $_POST['item_level_FW'] : null;
		$this->level["KW"] = isset ($_POST['item_level_KW']) ? $_POST['item_level_KW'] : null;
		$this->level["PW"] = isset ($_POST['item_level_PW']) ? $_POST['item_level_PW'] : null;
	}
	
	
	
	
	public function load () {
		
		global $post;
		
		if (get_post_status($post->ID)=='auto-draft') {
				
			$this->id = $post->ID;
			$this->title = '';
			$this->description = '';
			$this->level["FW"] = 0;
			$this->level["KW"] = 0;
			$this->level["PW"] = 0;
				
		} else {
				
			global $wpdb;
			$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_{$this->type} WHERE id = {$post->ID}", ARRAY_A);
			$this->id = $sqlres['id'];
			$this->title = $sqlres['title'];
			$this->description = $sqlres['description'];
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
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
	}
	
	
	
	
	public static function createTables() {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_learnout (
				id bigint(20) unsigned NOT NULL,
				title text,
				description text,
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				points smallint,
				PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
	
	
	
}

?>