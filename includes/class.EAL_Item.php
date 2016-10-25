<?php

require_once 'class.CLA_RoleTaxonomy.php';


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
		$this->title = trim ($post->post_title, "\x03");	// we remove the ASCII-03 character (we added this during loaded to make Wordpress save a revision even if the title does not change)
		$this->description = isset($_POST['item_description']) ? $_POST['item_description'] : null;
		$this->question = isset ($_POST['item_question']) ? $_POST['item_question'] : null;

		$this->level["FW"] = isset ($_POST['item_level_FW']) ? $_POST['item_level_FW'] : null;
		$this->level["KW"] = isset ($_POST['item_level_KW']) ? $_POST['item_level_KW'] : null;
		$this->level["PW"] = isset ($_POST['item_level_PW']) ? $_POST['item_level_PW'] : null;
		
		$this->learnout_id = isset ($_GET['learnout_id']) ? $_GET['learnout_id'] : (isset ($_POST['learnout_id']) ? $_POST['learnout_id'] : null);
		$this->learnout = null;		// lazy loading
		$this->difficulty = null;
		
		$this->note = isset ($_POST['item_note']) ? $_POST['item_note'] : null;
		$this->flag = isset ($_POST['item_flag']) ? $_POST['item_flag'] : null;
		
		// 		$this->domain = RoleTaxonomy::getCurrentRoleDomain()["name"];
		$this->domain = isset ($_POST["domain"]) ? $_POST["domain"] : ""; 
		if (($this->domain == "") && (isset($_POST['tax_input']))) {
			foreach ($_POST['tax_input'] as $key => $value) {
				$this->domain = $key;
				break;
			}
		}
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
	}
	
	
	
	public function load () {
		
		global $post;
		
		if (get_post_status($post->ID)=='auto-draft') {
				
			/* Create new item */
			$this->id = $post->ID;
			$this->title = '';
			$this->description = '';
			$this->question = '';
			
			$this->level["FW"] = 0;
			$this->level["KW"] = 0;
			$this->level["PW"] = 0;
			
			$this->learnout_id = isset ($_POST['learnout_id']) ? $_POST['learnout_id'] : (isset ($_GET['learnout_id']) ? $_GET['learnout_id'] : null);
			$this->learnout = null;
			$this->difficulty = null;
			
			$this->domain = RoleTaxonomy::getCurrentRoleDomain()["name"];
			$this->note = "";
			$this->flag = 0;
				
		} else {
			$this->loadById($post->ID);
		}
		
	}
	
	
	public function loadById ($item_id) {
		global $wpdb;
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_item WHERE id = {$item_id} AND type ='{$this->type}'", ARRAY_A);
		
		$this->id = $sqlres['id'];
		$this->title = $sqlres['title'];
		$this->description = $sqlres['description'];
		$this->question = $sqlres['question'];
		
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
		
		$this->learnout_id = $sqlres['learnout_id'];
		$this->learnout = null; // lazy loading
		$this->difficulty = $sqlres['difficulty'];
		$this->domain = $sqlres['domain'];
		$this->note = $sqlres['note'];
		$this->flag = $sqlres['flag'];
	}
	
	
	public static function save ($post_id, $post) { }
	
	public function save2DB () {
		
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
			$this->learnout = new EAL_LearnOut();
			$this->learnout->loadById($this->learnout_id);
		}
		
		return $this->learnout;
	}
	
	public function getPoints() { return -1; }
	
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