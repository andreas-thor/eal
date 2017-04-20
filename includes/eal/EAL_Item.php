<?php

require_once (__DIR__ . "/../class.CLA_RoleTaxonomy.php");


class EAL_Item {

	public $type;	// will be set in subclasses (EAL_ItemMC, EAL_ItemSC, ...)
	public $domain;	// each item belongs to a domain (when newly created: domain = current user's role domain)
	
	public $id;
	public $title;
	public $description;
	public $question;
	
	public $level;
	public $learnout;
	public $learnout_id;
	
	public $difficulty;
	public $note;
	public $flag;
	
	public static $level_label = ["Erinnern", "Verstehen", "Anwenden", "Analysieren", "Evaluieren", "Erschaffen"];
	public static $level_type = ["FW", "KW", "PW"];
	
	public static $flag_icon = ["", "dashicons-star-filled", "dashicons-flag", "dashicons-yes", "dashicons-no"];
	
	public static $category_value_label = [
			"type" => ["itemsc" => "Single Choice", "itemmc" => "Multiple Choice"],
			"level" => ["1" => "Erinnern", "2" => "Verstehen", "3" => "Anwenden", "4" => "Analysieren", "5" => "Evaluieren", "6" => "Erschaffen"],
			"dim" => ["FW" => "FW", "KW" => "KW", "PW" => "PW"]
	];
	
	public static $category_label = [
			"type" => "Item Typ",
			"level" => "Anforderungsstrufe",
			"dim" => "Wissensdimension",
			"topic1" => "Topic Stufe 1"
	];
	
	
	
	function __construct(int $item_id = -1, string $prefix="") {
		
		
		if ($item_id > 0) {
			$this->loadFromDB($item_id);
			return;
		} 
		
		if ($_POST[$prefix."post_type"] == $this->type) {
			$this->loadFromPOSTRequest($prefix);
			return;
		} 

		$this->id = $item_id;
		$this->title = '';
		$this->description = '';
		$this->question = '';

		$this->level = ["FW" => null, "KW" => null, "PW" => null];
		$this->learnout_id = isset ($_POST['learnout_id']) ? $_POST['learnout_id'] : (isset ($_GET['learnout_id']) ? $_GET['learnout_id'] : null);
		$this->learnout = null;
		
		$this->difficulty = null;
		$this->note = "";
		$this->flag = 0;
			
		$this->domain = RoleTaxonomy::getCurrentRoleDomain()["name"];		
		
		global $post;
		if ($post->post_type != $this->type) return;

		if (get_post_status($post->ID)=='auto-draft') {
			$this->id = $post->ID;
		} else {
			$this->loadFromDB($post->ID);
		}
	}
	
	
	public static function load (string $item_type, int $item_id, string $prefix="") {
		if ($item_type == 'itemsc') return new EAL_ItemSC($item_id, $prefix);
		if ($item_type == 'itemmc') return new EAL_ItemMC($item_id, $prefix);
		return null;
	}
	
	
	/**
	 * Initialize item from _POST Request data
	 */
	protected function loadFromPOSTRequest (string $prefix="") {
	
		$this->id = $_POST[$prefix."post_ID"];
		$this->title = $_POST[$prefix."post_title"];
		$this->description = isset($_POST[$prefix.'item_description']) ? $_POST[$prefix.'item_description'] : null;
		$this->question = isset ($_POST[$prefix.'item_question']) ? $_POST[$prefix.'item_question'] : null;

		$this->level = ["FW" => null, "KW" => null, "PW" => null];
		$this->level["FW"] = isset ($_POST[$prefix.'item_level_FW']) ? $_POST[$prefix.'item_level_FW'] : null;
		$this->level["KW"] = isset ($_POST[$prefix.'item_level_KW']) ? $_POST[$prefix.'item_level_KW'] : null;
		$this->level["PW"] = isset ($_POST[$prefix.'item_level_PW']) ? $_POST[$prefix.'item_level_PW'] : null;
		
		$this->learnout_id = isset ($_GET[$prefix.'learnout_id']) ? $_GET[$prefix.'learnout_id'] : (isset ($_POST[$prefix.'learnout_id']) ? $_POST[$prefix.'learnout_id'] : null);
		$this->learnout = null;		// lazy loading
		
		$this->difficulty = null;
		$this->note = isset ($_POST[$prefix.'item_note']) ? $_POST[$prefix.'item_note'] : null;
		$this->flag = isset ($_POST[$prefix.'item_flag']) ? $_POST[$prefix.'item_flag'] : null;
		
		// 	$this->domain = RoleTaxonomy::getCurrentRoleDomain()["name"];
		$this->domain = isset ($_POST[$prefix."domain"]) ? $_POST[$prefix."domain"] : ""; 
		if (($this->domain == "") && (isset($_POST[$prefix.'tax_input']))) {
			foreach ($_POST[$prefix.'tax_input'] as $key => $value) {
				$this->domain = $key;
				break;
			}
		}
	}
	
	
	protected function loadFromDB (int $item_id) {
	
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_item WHERE id = {$item_id} AND type ='{$this->type}'", ARRAY_A);
	
		$this->id = $sqlres['id'];
		$this->title = $sqlres['title'];
		$this->description = $sqlres['description'];
		$this->question = $sqlres['question'];
	
		$this->level = ["FW" => null, "KW" => null, "PW" => null];
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
	
		$this->learnout_id = $sqlres['learnout_id'];
		$this->learnout = null; // lazy loading
		
		$this->difficulty = $sqlres['difficulty'];
		$this->note = $sqlres['note'];
		$this->flag = $sqlres['flag'];
		
		$this->domain = $sqlres['domain'];
	}
	
	

	
	
	public function setPOST () {
		
		$_POST['post_type'] = $this->type;
		$_POST['item_description'] = $this->description;
		$_POST['item_question'] = $this->question;
		$_POST['item_level_FW'] = $this->level["FW"];
		$_POST['item_level_KW'] = $this->level["KW"];
		$_POST['item_level_PW'] = $this->level["PW"];
		$_POST['learnout_id'] = $this->learnout_id;
		$_POST['difficulty'] = $this->difficulty;
		$_POST['domain'] = $this->domain;
		$_POST['item_note'] = $this->note;
		$_POST['item_flag'] = $this->flag;
		$_POST['post_content'] = microtime();
	}
	
	
	

	
	

	
	
	public static function save ($post_id, $post) { }
	
	public function saveToDB () {
		
		global $wpdb;
		
		$wpdb->replace(
			"{$wpdb->prefix}eal_item",
			array(
					'id' => $this->id,
					'title' => $this->title,
					'description' => $this->description,
					'question' => $this->question,
					'level_FW' => $this->level["FW"],
					'level_KW' => $this->level["KW"],
					'level_PW' => $this->level["PW"],
					'points'   => $this->getPoints(),
					'difficulty' => $this->difficulty,
					'learnout_id' => $this->learnout_id,
					'type' => $this->type,
					'domain' => $this->domain,
					'note' => $this->note,
					'flag' => $this->flag
			),
			array('%d','%s','%s','%s','%d','%d','%d','%d','%f','%d','%s','%s','%s','%d')
			);
	}
	
	
	public static function delete ($post_id) {
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_item', array( 'id' => $post_id ), array( '%d' ) );
		$wpdb->delete( '{$wpdb->prefix}eal_review', array( 'item_id' => $post_id ), array( '%d' ) );
	}
	
	
	/**
	 * Implements lazy loading of learning outcome 
	 * @return NULL|NULL|EAL_LearnOut
	 */
	public function getLearnOut () {
		
		if (is_null ($this->learnout_id )) return null;
		
		if (is_null ($this->learnout)) {
			$this->learnout = new EAL_LearnOut($this->learnout_id);
		}
		
		return $this->learnout;
	}
	
	
	/**
	 * 
	 * @return array of EAL_Review
	 */
	public function getReviews (): array {
		
		$res = array();
		foreach ($this->getReviewIds() as $review_id) {
			array_push($res, new EAL_Review($review_id));
		}
		
		return $res;
	}
	
	
	public function getReviewIds (): array {
	
		global $wpdb;
		return $wpdb->get_col ("
			SELECT R.id
			FROM {$wpdb->prefix}eal_review R
			JOIN {$wpdb->prefix}posts RP ON (R.id = RP.id)
			WHERE RP.post_parent=0 AND R.item_id = {$this->id} AND RP.post_status IN ('publish', 'pending', 'draft')");
		
	}
	
	
	
	protected function getPoints() { return -1; }
	
	public function getStatusString () {
		
		switch (get_post_status($this->id)) {
			case 'publish': return 'Published';
			case 'pending': return 'Pending Review';
			case 'draft': return 'Draft';
		}
		return 'Unknown';
	}
	
	
	/**
	 * Create database tables when plugin is activated 
	 */
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_item (
			id bigint(20) unsigned NOT NULL,
			title text,
			description mediumtext,
			question mediumtext,
			level_FW tinyint unsigned,
			level_KW tinyint unsigned,
			level_PW tinyint unsigned,
			points smallint,
			difficulty decimal(10,1), 
			learnout_id bigint(20) unsigned,
			type varchar(20) NOT NULL,
			domain varchar(50) NOT NULL,
			note text,
			flag tinyint,
			PRIMARY KEY  (id),
			KEY index_type (type),
			KEY index_domain (domain)
			) {$wpdb->get_charset_collate()};"
		);

		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_result (
			test_id bigint(20) unsigned NOT NULL,
			item_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			points smallint,
			PRIMARY KEY  (test_id, item_id, user_id)
			) {$wpdb->get_charset_collate()};"
		);
	}
		
	
	
	
	
	/**
	 * Methods for comparing two item versions
	 * @param EAL_Item $comp
	 */
	
	public function compareTitle (EAL_Item $comp) {
		return array ("id" => 'title', 'name' => 'Titel', 'diff' => $this->compareText ($this->title, $comp->title));
	}
	
	
	public function compareDescription (EAL_Item $comp) {
		return array ("id" => 'description', 'name' => 'Fall- oder Problemvignette', 'diff' => $this->compareText ($this->description, $comp->description));
	}
	
	
	public function compareQuestion (EAL_Item $comp) {
		return array ("id" => 'question', 'name' => 'Aufgabenstellung', 'diff' => $this->compareText ($this->question, $comp->question));
	}
	
	
	public function compareLevel (EAL_Item $comp) {
		$diff  = "<table class='diff'>";
		$diff .= "<colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup>";
		$diff .= "<tbody><tr>";
		$diff .= "<td align='left'><div>{$this->compareLevel1($this->level, $comp->level, "deleted")}</div></td><td></td>";
		$diff .= "<td><div>{$this->compareLevel1($comp->level, $this->level, "added")}</div></td>";
		$diff .= "</tr></tbody></table>";
		return array ("id" => 'level', 'name' => 'Anforderungsstufe', 'diff' => $diff);
	}
	
	
	private function compareLevel1 ($old, $new, $class) {
		$res = "<table style='width:1%'><tr><td></td>";
		foreach ($old as $c => $v) {
			$res .= sprintf ('<td>%s</td>', $c);
		}
		$res .= sprintf ('</tr>');
		
		foreach (EAL_Item::$level_label as $n => $r) {	// n=0..5, $r=Erinnern...Erschaffen
			$bgcolor = (($new["FW"]!=$n+1) &&  ($old["FW"]==$n+1)) || (($new["KW"]!=$n+1) && ($old["KW"]==$n+1)) || (($new["PW"]!=$n+1) && ($old["PW"]==$n+1)) ? "class='diff-{$class}line'" : "";
			// || (($new["FW"]==$n+1) &&  ($old["FW"]!=$n+1)) || (($new["KW"]==$n+1) && ($old["KW"]!=$n+1)) || (($new["PW"]==$n+1) && ($old["PW"]!=$n+1))
			
			$res .= sprintf ('<tr><td style="padding:0px 5px 0px 5px;" align="left" %s>%d.&nbsp;%s</td>', $bgcolor, $n+1, $r);
			foreach ($old as $c=>$v) {	// c=FW,KW,PW; v=1..6
				$bgcolor = (($v==$n+1)&& ($new[$c]!=$n+1)) ? "class='diff-{$class}line'" : "";
				$res .= sprintf ("<td align='left' style='padding:0px 5px 0px 5px;' %s>", $bgcolor);
				$res .= sprintf ("<input type='radio' %s></td>", (($v==$n+1)?'checked':'disabled'));
		
			}
			$res .= '</tr>';
		}
		$res .= sprintf ('</table>');
		return $res;
	}
	
	
	private function compareText ($old, $new) {
	
		$old = normalize_whitespace (strip_tags ($old));
		$new = normalize_whitespace (strip_tags ($new));
		$args = array(
				'title'           => '',
				'title_left'      => '',
				'title_right'     => '',
				'show_split_view' => true
		);
	
		$diff = wp_text_diff($old, $new, $args);
	
		if (!$diff) {
			$diff  = "<table class='diff'><colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup><tbody><tr>";
			$diff .= "<td>{$old}</td><td></td><td>{$new}</td>";
			$diff .= "</tr></tbody></table>";
		}
	
		return $diff;
	
	}
}

?>