<?php


class EAL_Review {

	public $id;
	public $item_id;
	public $item;
	public $score;
	public $level;
	public $feedback;
	public $overall;
	
	public static $dimension1 = array ('description' => "Fall- oder Problemvignette", 'question' => 'Aufgabenstellung', 'answers' => 'Antwortoptionen');
	public static $dimension2 = array ('correctness' => "Fachl. Richtigkeit", 'relevance' => "Relevanz bzgl. LO", 'wording' => "Formulierung");
	
	
	
	
	function __construct () {
		$this->level = ["FW" => null, "KW" => null, "PW" => null];
	}
	
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	public function init ($post_id, $post) {
	
		$this->id = $post_id;
		$this->item_id = isset ($_GET['item_id']) ? $_GET['item_id'] : (isset ($_POST['item_id']) ? $_POST['item_id'] : null);
		$this->item = null;	
		
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = isset ($_POST["review_{$k1}_{$k2}"]) ? $_POST["review_{$k1}_{$k2}"] : null;
			}
		}
		
		$this->level["FW"] = isset ($_POST['review_level_FW']) ? $_POST['review_level_FW'] : null;
		$this->level["KW"] = isset ($_POST['review_level_KW']) ? $_POST['review_level_KW'] : null;
		$this->level["PW"] = isset ($_POST['review_level_PW']) ? $_POST['review_level_PW'] : null;
		$this->feedback = isset ($_POST['review_feedback']) ? $_POST['review_feedback'] : null;
		$this->overall  = isset ($_POST['review_overall'])  ? $_POST['review_overall']  : null;
		
	}
	
	
	public function getItem () {
		
		if (is_null($this->item_id)) return null;
		
		if (is_null($this->item)) {
			$post = get_post($this->item_id);
			if ($post == null) return null;
	
			if ($post->post_type == 'itemsc') $this->item = new EAL_ItemSC();
			if ($post->post_type == 'itemmc') $this->item = new EAL_ItemMC();
			
			$this->item->loadById($this->item_id);
		}
		return $this->item;
	}
	
	
	public function load () {
		
		
		global $post;
		
		if (get_post_status($post->ID)=='auto-draft') {
				
			$this->id = $post->ID;
			$this->item_id = isset ($_POST['item_id']) ? $_POST['item_id'] : $_GET['item_id'];
			$this->item = null;	
			
			$this->score = array();
			foreach (self::$dimension1 as $k1 => $v1) {
				$this->score[$k1] = array ();
				foreach (self::$dimension2 as $k2 => $v2) {
					$this->score[$k1][$k2] = 0;
				}
			}
			
			$this->level["FW"] = 0;
			$this->level["KW"] = 0;
			$this->level["PW"] = 0;
			$this->feedback = '';
			$this->overall = 0;
				
		} else {
				
			$this->loadById($post->ID);
				
		}
		
	}
	
	public function loadById ($item_id) { 
		
		global $post, $wpdb;
		
		$sqlres = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}eal_review WHERE id = {$item_id}", ARRAY_A);
		
		$this->id = $sqlres['id'];
		$this->item_id = $sqlres['item_id'];
		$this->item = null; // lazy loading
			
		$this->score = array();
		foreach (self::$dimension1 as $k1 => $v1) {
			$this->score[$k1] = array ();
			foreach (self::$dimension2 as $k2 => $v2) {
				$this->score[$k1][$k2] = $sqlres[$k1 . "_" . $k2];
			}
		}
		
		$this->level["FW"] = $sqlres['level_FW'];
		$this->level["KW"] = $sqlres['level_KW'];
		$this->level["PW"] = $sqlres['level_PW'];
		$this->feedback = $sqlres['feedback'];
		$this->overall = $sqlres['overall'];;
		
	}
	
	

	public static function save ($post_id, $post) {
	
		if ($_POST["post_type"]!="review") return;
		
		global $wpdb;
		$review = new EAL_Review();
		$review->init($post_id, $post);
	
		$replaceScore = array ();
		$replaceType = array ();
		foreach (self::$dimension1 as $k1 => $v1) {
			foreach (self::$dimension2 as $k2 => $v2) {
				$replaceScore["{$k1}_{$k2}"] = $review->score[$k1][$k2];
				array_push($replaceType, "%d");
			}
		}
	
	
		$wpdb->replace(
				"{$wpdb->prefix}eal_review",
				array_merge (
						array(
								'id' => $review->id,
								'item_id' => $review->item_id,
								'level_FW' => $review->level["FW"],
								'level_KW' => $review->level["KW"],
								'level_PW' => $review->level["PW"],
								'feedback' => $review->feedback,
								'overall'  => $review->overall
						),
						$replaceScore
						),
						array_merge (
								array('%d','%d','%d','%d','%d','%s','%d'),
								$replaceType
								)
						);
	
	
	}
	
	
	
	public static function delete ($post_id) {
	
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_review', array( 'id' => $post_id ), array( '%d' ) );
	}
	
	

	public static function createTables () {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
	
		$sqlScore = "";
		foreach (EAL_Item_Review::$dimension1 as $k1 => $v1) {
			foreach (EAL_Item_Review::$dimension2 as $k2 => $v2) {
				$sqlScore .= "{$k1}_{$k2} tinyint unsigned, \n";
			}
		}
	
		dbDelta (
			"CREATE TABLE {$wpdb->prefix}eal_review (
				id bigint(20) unsigned NOT NULL,
				item_id bigint(20) unsigned NOT NULL, {$sqlScore}
				level_FW tinyint unsigned,
				level_KW tinyint unsigned,
				level_PW tinyint unsigned,
				feedback mediumtext,
				overall tinyint unsigned,
				KEY index_item_id (item_id),
				PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};"
		);
	}
	
	
	
	
	public function getPreviewHTML ($forReview = TRUE) {
			
	
		$res  = sprintf ("<div onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\">");
		$res .= sprintf ("<h1 style='display:inline'>[%s]</span></h1>", $this->getItem()->title);
		$res .= sprintf ("<div style='display:none'><span><a href=\"post.php?action=edit&post=%d\">Edit</a></span></div>", $this->id);
		$res .= sprintf ("</div><br/>");
		$res .= sprintf ("<div>%s</div>", $this->getScoreHTML(FALSE));
		$res .= CPT_Object::getLevelHTML('review_' . $this->id, $this->level,  $this->getItem()->level, "disabled", 1, '');
		$res .= sprintf ("<div>%s</div>", $this->feedback);
		$res .= "<br/>";
		
	
		return $res;
			
	}
	
	
	public function getScoreHTML ($editable) {
	
		
		$values = ["gut", "Korrektur", "ungeeignet"];
	
	
		$html_head = "<tr><th></th>";
		foreach (EAL_Review::$dimension2 as $k2 => $v2) {
			$html_head .= "<th style='padding:0.5em'>{$v2}</th>";
		}
		$html_head .= "</tr>";
			
		if ($editable) {

?>			
			<script>
				var $ = jQuery.noConflict();
				
				function setRowGood (e) {
					$(e).parent().parent().find("input").each ( function() {
	 					if (this.value==1) this.checked = true;
					});
				}
			</script>
<?php 
			
		}
		
		$html_rows = "";
		foreach (EAL_Review::$dimension1 as $k1 => $v1) {
			$html_rows .= "<tr><td valign='top'style='padding:0.5em'>{$v1}<br/>";
			if ($editable) $html_rows .= "<a onclick=\"setRowGood(this);\">(alle gut)</a>";
			$html_rows .= "</td>";
			foreach (EAL_Review::$dimension2 as $k2 => $v2) {
				$html_rows .= "<td style='padding:0.5em; border-style:solid; border-width:1px;'>";
				foreach ($values as $k3 => $v3) {
					$checked = ($this->score[$k1][$k2]==$k3+1);
					$html_rows .= "<input type='radio' id='{$k1}_{$k2}_{k3}' name='review_{$k1}_{$k2}' value='" . ($k3+1) . "'";
					$html_rows .= ($editable || $checked) ? "" : " disabled";
					$html_rows .= ($checked ? " checked='checked'" : "") . ">" . $v3 . "<br/>";
				}
				$html_rows .= "</td>";
			}
			$html_rows .= "</tr>";
		}
				
		return "<form><table style='font-size:100%'>{$html_head}{$html_rows}</table></form>";
				
	}
	
	

}

?>