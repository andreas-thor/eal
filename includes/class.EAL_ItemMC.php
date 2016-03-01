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
	
		global $wpdb;
		$item = new EAL_ItemMC();
		$item->init($post_id, $post);
		
		$wpdb->replace(
				"{$wpdb->prefix}eal_{$item->type}",
				array(
						'id' => $item->id,
						'title' => $item->title,
						'description' => $item->description,
						'question' => $item->question,
						'level_FW' => $item->level["FW"],
						'level_KW' => $item->level["KW"],
						'level_PW' => $item->level["PW"],
						'points'   => $item->getPoints()
				),
				array('%d','%s','%s','%s','%d','%d','%d','%d')
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
	
	
	
		


	
	public function getPreviewHTML () {
		 
		$res  = "<h1>{$this->title}</h1>";
		$res .= "<input type='hidden' id='item_id' name='item_id'  value='{$this->id}'>";
		$res .= "<div>{$this->description}</div>";
		 
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
	}
	
	
	
	
	
	public static function createTables () {
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		global $wpdb;
		EAL_Item::createTableItem("{$wpdb->prefix}eal_itemmc");
		EAL_Item::createTableReview("{$wpdb->prefix}eal_itemmc_review");
		
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
	
	
	
}

?>