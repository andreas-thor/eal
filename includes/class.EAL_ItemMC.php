<?php

require_once ("class.EAL_Item.php");

class EAL_ItemMC extends EAL_Item {
	
	public $answers = array();
	
	
	function __construct() {
		parent::__construct();
		$this->type = "itemmc";
	}
	
	
	/**
	 * Create new item from _POST
	 * @param unknown $post_id
	 * @param unknown $post
	 */
	public function init ($post_id, $post) {
	
		parent::init($post_id, $post);
	
		$this->answers = array();
		if (isset($_POST['answer'])) {
			foreach ($_POST['answer'] as $k => $v) {
				array_push ($this->answers, array ('answer' => $v, 'positive' => $_POST['positive'][$k], 'negative' => $_POST['negative'][$k]));
			}
		}
	}
	
	
	public function setPOST () {
		
		parent::setPOST();
		
		
		$_POST['answer'] = array();
		$_POST['positive'] = array();
		$_POST['negative'] = array();
		foreach ($this->answers as $v) {
			array_push ($_POST['answer'], $v['answer']);
			array_push ($_POST['positive'], $v['positive']);
			array_push ($_POST['negative'], $v['negative']);
		}
	}
	
	/**
	 * Create new Item or load existing item from database
	 * @param string $eal_posttype
	 */
	
	public function load () {
		
		global $post;
		if ($post->post_type != $this->type) return;
		parent::load();
		
		if (get_post_status($post->ID)=='auto-draft') {
			
			$this->answers = array (
					array ('answer' => '', 'positive' => 1, 'negative' => 0),
					array ('answer' => '', 'positive' => 1, 'negative' => 0),
					array ('answer' => '', 'positive' => 0, 'negative' => 1),
					array ('answer' => '', 'positive' => 0, 'negative' => 1)
			);
			
		} else {
			
			global $wpdb;
			$this->answers = array();
			$sqlres = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_{$this->type}_answer WHERE item_id = {$post->ID} ORDER BY id", ARRAY_A);
			foreach ($sqlres as $a) {
				array_push ($this->answers, array ('answer' => $a['answer'], 'positive' => $a['positive'], 'negative' => $a['negative']));
			}
		}
		
	}
	
	
	public function loadById ($item_id) {
	
		parent::loadById($item_id);
		
		global $wpdb;
		$this->answers = array();
		$sqlres = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}eal_{$this->type}_answer WHERE item_id = {$item_id} ORDER BY id", ARRAY_A);
		foreach ($sqlres as $a) {
			array_push ($this->answers, array ('answer' => $a['answer'], 'positive' => $a['positive'], 'negative' => $a['negative']));
		}
	}
	
	
	public static function save ($post_id, $post) {
	
		if ($_POST["post_type"]!="itemmc") return;
		
		global $wpdb;
		$item = new EAL_ItemMC();
		$item->init($post_id, $post);
		
		$rowCount = $wpdb->replace(
				"{$wpdb->prefix}eal_{$item->type}",
				array(
						'id' => $item->id,
						'title' => $item->title,
						'description' => $item->description,
						'question' => $item->question,
						'level_FW' => $item->level["FW"],
						'level_KW' => $item->level["KW"],
						'level_PW' => $item->level["PW"],
						'points'   => $item->getPoints(),
						'learnout_id' => $item->learnout_id
				),
				array('%d','%s','%s','%s','%d','%d','%d','%d','%d')
		);
		
		
		/** TODO: Sanitize all values */
		
		if (count($item->answers)>0) {
			
			$values = array();
			$insert = array();
			foreach ($item->answers as $k => $a) {
				array_push($values, $item->id, $k+1, $a['answer'], $a['positive'], $a['negative']);
				array_push($insert, "(%d, %d, %s, %d, %d)");
			}
			
				
			// replace answers
			$query = "REPLACE INTO {$wpdb->prefix}eal_{$item->type}_answer (item_id, id, answer, positive, negative) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
			
		}

		// delete remaining answers
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}eal_{$item->type}_answer WHERE item_id=%d AND id>%d", array ($post_id, count($item->answers))));
		
	}
	
	
	public static function delete ($post_id) {
		
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemmc', array( 'id' => $post_id ), array( '%d' ) );
		$wpdb->delete( '{$wpdb->prefix}eal_itemmc_answer', array( 'item_id' => $post_id ), array( '%d' ) );
		$wpdb->delete( '{$wpdb->prefix}eal_itemmc_review', array( 'item_id' => $post_id ), array( '%d' ) );
	}
	
	
	
	public function getPoints() { 
		
		$result = 0;
		foreach ($this->answers as $a) {
			$result += max ($a['positive'], $a['negative']);
		}
		return $result;
	
	}
	
	
	
		


	
	public function getPreviewHTML ($forReview = TRUE) {
		 
		
		if ($forReview) {
			$res = "<div>{$this->description}</div>";
			 
			$answerLine = '<tr align="left">
	                           <td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td>
	                           <td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td>
	                           <td>%s</td>
	                    </tr>';
			 
			//           $res .= "<div style='background-color:F2F6FF; margin-top:2em; padding:1em;'>{$this->question}<ul style='list-style: none;margin-top:1em;'>";
			$res .= "<div style='background-color:F2F6FF; margin-top:2em; padding:1em;'>{$this->question}";
			 
			$res .= "<table style='font-size: 100%'>";
			 
			 
			foreach ($this->answers as $a) {
				//                  $res .= "<li><input type='checkbox' " . (($a['positive']>$a['negative']) ? 'checked' : '') . ">{$a['answer']}</input></li>";
				$res .= sprintf($answerLine,
						$a['positive'], ($a['positive']>$a['negative'] ? 'bold' : 'normal'),
						$a['negative'], ($a['negative']>$a['positive'] ? 'bold' : 'normal'),
						$a['answer']);
			}
		
			//           $res .= "</ul></div>";
			$res .= "</table></div>";
			 
			
			// 		$res .= "<div style='background-color:F2F6FF; margin-top:2em; padding:1em;'>{$this->question}<ul style='list-style: none;margin-top:1em;'>";
			// 		foreach ($this->answers as $a) {
			// 			$res .= "<li><input type='checkbox'>{$a['answer']}</input></li>";
			// 		}
			// 		$res .= "</ul></div>";
			
			return $res;
		
		} else {
			
			$res  = sprintf ("<div onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\">");
			$res .= sprintf ("<h1 style='display:inline'>%s</span></h1>", $this->title, $this->id);
			$res .= sprintf ("<div style='display:none'><span><a href=\"post.php?action=edit&post=%d\">Edit</a></span></div>", $this->id);
			$res .= sprintf ("</div><br/>");
			$res .= sprintf ("<div>%s</div>", $this->description);
			$res .= "<div style='background-color:F2F6FF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>{$this->question}";
			$res .= "<form style='margin-top:1em'>";
			foreach ($this->answers as $a) {
				$res .= "<div style='margin-top:1em'><input type='checkbox'>" . $a['answer'] . "</div>";
			}
			$res .= "</form></div><br/>";
				
			return $res;
			
			
			
			
		}
	}
	
	
	
	
	
	public static function createTables () {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		global $wpdb;
		EAL_Item::createTableItem("{$wpdb->prefix}eal_itemmc");
		
		dbDelta (
				"CREATE TABLE {$wpdb->prefix}eal_itemmc_answer (
				item_id bigint(20) unsigned NOT NULL,
				id smallint unsigned NOT NULL,
				answer text,
				positive smallint,
				negative smallint,
				KEY  (item_id),
				PRIMARY KEY  (item_id, id)
			) {$wpdb->get_charset_collate()};"
		);
		
		
	
	}
	
	
	public function compareAnswers (EAL_ItemMC $comp) {
	
		$diff  = "<table class='diff'>";
		$diff .= "<colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup>";
		$diff .= "<tbody><tr>";
		$diff .= "<td><div>{$this->compareAnswers1($this->answers, $comp->answers, "deleted")}</div></td><td></td>";
		$diff .= "<td><div>{$this->compareAnswers1($comp->answers, $this->answers, "added")}</div></td>";
		$diff .= "</tr></tbody></table>";
	
	
		return array ("id" => 'answers', 'name' => 'Antwortoptionen', 'diff' => $diff);
	
	}
	
	private function compareAnswers1 ($old, $new, $class) {
	
		$res = "<table >";
	
		foreach ($old as $i => $a) {
			$res .= "<tr align='left' >";
			$bgcolor = ($new[$i]['positive'] != $a['positive']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['positive']}</td>";
			$bgcolor = ($new[$i]['negative'] != $a['negative']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['negative']}</td>";
			$bgcolor = ($new[$i]['answer'] != $a['answer']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='width:98%; padding:0; padding-left:10px' align='left' {$bgcolor}>{$a['answer']}</td></tr>";
				
		}
	
		$res .= "</table></div>";
	
		return $res;
	}
	
	
}

?>