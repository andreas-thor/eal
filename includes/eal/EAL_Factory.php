<?php

class EAL_Factory {
	
	
	public static function createNewItem (string $item_type, int $item_id, string $prefix=''): EAL_Item {
		
		if ($item_type == 'itemsc') return self::createNewItemSC($item_id, $prefix);
		if ($item_type == 'itemmc') return self::createNewItemMC($item_id, $prefix);
		
		throw new Exception('Unknown item type ' . $item_type);
	}
	
	public static function createNewItemMC (int $item_id, string $prefix=''): EAL_ItemMC {
		
		$item = new EAL_ItemMC();
		
		// Load from Database if valid Id
		if ($item_id > 0) {
			$item->setId($item_id);
			DB_ItemMC::loadFromDB($item);
			return $item;
		} 
		
		// load from POST request
		if ($_POST[$prefix . 'post_type'] == $item->getType()) {
			
			self::loadItemFromPOSTRequest($item, $prefix);
			
			$item->clearAnswers();
			if (isset($_POST[$prefix . 'answer'])) {
				foreach ($_POST[$prefix . 'answer'] as $k => $v) {
					$item->addAnswer(html_entity_decode (stripslashes($v)), $_POST[$prefix . 'positive'][$k], $_POST[$prefix . 'negative'][$k]);
				}
			}
			
			$item->minnumber = $_POST[$prefix . 'item_minnumber'] ?? 0;
			$item->maxnumber = $_POST[$prefix . 'item_maxnumber'] ?? $item->getNumberOfAnswers();
			return $item;
		}
		
		// load from previously loaded WP_Post object
		global $post;
		
		if ($post->post_type != $item->getType()) {
			throw new Exception('Could not load MC item due to wrong type. $post->post_type is ' . $post->post_type);
		}
		
		$item->setId ($post->ID);
		$item->setLearnOutId($_POST['learnout_id'] ?? $_GET['learnout_id'] ?? -1);
		
		if (get_post_status($post->ID)=='auto-draft') {
			return $item;
		}
		
		if ($post->ID > 0) {
			DB_ItemMC::loadFromDB($item);
			return $item;
		}
		
		throw new Exception('Could not load MC item due to wrong ID. $post->ID is ' . $post->ID);
		
		
	}

	public static function createNewItemSC (int $item_id, string $prefix=''): EAL_ItemSC {
		
		$item = new EAL_ItemSC();
		
		// Load from Database if valid Id
		if ($item_id > 0) {
			$item->setId($item_id);
			DB_ItemSC::loadFromDB($item);
			return $item;
		}
		
		// load from POST request
		if ($_POST[$prefix . 'post_type'] == $item->getType()) {
			
			self::loadItemFromPOSTRequest($item, $prefix);
			
			$item->clearAnswers();
			if (isset($_POST[$prefix . 'answer'])) {
				foreach ($_POST[$prefix . 'answer'] as $k => $v) {
					$item->addAnswer(html_entity_decode (stripslashes($v)), $_POST[$prefix . 'points'][$k]);
				}
			}
			
			return $item;
		}
		
		// load from previously loaded WP_Post object
		global $post;
		
		if ($post->post_type != $item->getType()) {
			throw new Exception('Could not load SC item due to wrong type. $post->post_type is ' . $post->post_type);
		}
		
		$item->setId ($post->ID);
		$item->setLearnOutId($_POST['learnout_id'] ?? $_GET['learnout_id'] ?? -1);
		
		if (get_post_status($post->ID)=='auto-draft') {
			return $item;
		}
		
		if ($post->ID > 0) {
			DB_ItemSC::loadFromDB($item);
			return $item;
		}
		
		throw new Exception('Could not load SC item due to wrong ID. $post->ID is ' . $post->ID);
	}
	
	
	public static function loadAllItemIdsForLearnOut (EAL_LearnOut $learnout): array {
		return DB_Item::loadAllItemIdsForLearnOut($learnout);
	}
	
	
	public static function loadAllLearningOutcomes (string $domain): array {
		return DB_Learnout::loadAllLearningOutcomes($domain);	
	}
	
	public static function loadAllReviewsForItem (EAL_Item $item): array {
		
		$res = array();
		foreach (DB_Review::loadAllReviewIdsForItemFromDB($item) as $review_id) {
			array_push($res, self::createNewReview($review_id));
		}
		
		return $res;
	}
	

	public static function createNewReview (int $review_id=-1): EAL_Review {
		
		$review = new EAL_Review();
		
		// Load from Database if valid Id
		if ($review_id > 0) {
			$review->setId($review_id);
			DB_Review::loadFromDB($review);
			return $review;
		}
		
		
		if ($_POST["post_type"] == $review->getType()) {

			self::loadObjectFromPOSTRequest($review, '', 'review_level_');
			
			$review->setItemId($_GET['item_id'] ?? $_POST['item_id'] ?? -1);
			
			foreach (self::$dimension1 as $dim1 => $v1) {
				foreach (self::$dimension2 as $dim2 => $v2) {
					$review->setScore($dim1, $dim2, $_POST["review_{$dim1}_{$dim2}"] ?? 0);
				}
			}
			
			$review->setFeedback(html_entity_decode (stripslashes($_POST['review_feedback'] ?? '')));
			$review->setOverall($_POST['review_overall'] ?? 0);
			
			return $review;
			
		} 
		
		
		global $post;
		
		if ($post->post_type != $review->getType()) {
			throw new Exception('Could not load review due to wrong type. $post->post_type is ' . $post->post_type);
		}
		
		$review->setId ($post->ID);
		$review->setItemId($_POST['item_id'] ?? $_GET['item_id'] ?? -1);
		
		if (get_post_status($post->ID)=='auto-draft') {
			return $review;
		} 
		
		if ($post->ID > 0) {
			DB_Review::loadFromDB($review);
			return $review;
		}
			
		throw new Exception('Could not load review due to wrong ID. $post->ID is ' . $post->ID);
		
		
	}
	
	
	public static function createNewLearnOut (int $learnout_id=-1, string $prefix=''): EAL_LearnOut {
		
		$learnout = new EAL_LearnOut();
		
		// Load from Database if valid Id 
		if ($learnout_id > 0) {
			$learnout->setId ($learnout_id);
			DB_Learnout::loadFromDB($learnout);
			return $learnout;
		}
		
		// load from POST request 
		if ($_POST[$prefix . 'post_type'] == $learnout->getType()) {
			
			self::loadObjectFromPOSTRequest($learnout, $prefix, 'learnout_level_');
			
			// set properties from POST variables 
			$learnout->setTitle(stripslashes($_POST[$prefix . 'post_title'] ?? ''));
			$learnout->setDescription = html_entity_decode (stripslashes($_POST[$prefix . 'learnout_description'] ?? ''));
			
			return $learnout;
		}
		
		// load from previously loaded WP_Post object
		global $post;
		
		if ($post->post_type != $learnout->getType()) {
			throw new Exception('Could not load learning outcome due to wrong type. $post->post_type is ' . $post->post_type);
		}
		
		$learnout->setId ($post->ID);
		
		if (get_post_status($post->ID)=='auto-draft') {
			return $learnout;
		} 
		
		if ($post->ID > 0) {
			DB_Learnout::loadFromDB($learnout);
			return $learnout;
		}
		
		throw new Exception('Could not load learning outcome due to wrong ID. $post->ID is ' . $post->ID);
		
	}
	

	
	private static function loadItemFromPOSTRequest (EAL_Item &$item, string $prefix) {
		
		self::loadObjectFromPOSTRequest($item, $prefix, 'item_level_');
		
		$item->setTitle(stripslashes($_POST[$prefix . 'post_title'] ?? ''));
		$item->setDescription(html_entity_decode (stripslashes($_POST[$prefix . 'item_description'] ?? '')));
		$item->setQuestion(html_entity_decode (stripslashes($_POST[$prefix . 'item_question'] ?? '')));
		$item->setLearnOutId($_GET[$prefix . 'learnout_id'] ?? $_POST[$prefix . 'learnout_id'] ?? -1);
		$item->setNote(html_entity_decode (stripslashes($_POST[$prefix . 'item_note'] ?? '')));
		$item->setFlag($_POST[$prefix . 'item_flag'] ?? 0);
	}
	
	
	private static function loadObjectFromPOSTRequest (EAL_Object &$object, string $prefix, string $levelPrefix) {
		
		$object->setId ($_POST[$prefix . 'post_ID']);
		$object->setLevel(new EAL_Level($_POST, $prefix . $levelPrefix));
		$object->setDomain($_POST[$prefix . 'domain'] ?? '');
		
		// adjust domain if necessary ... FIXME: WHY and WHEN
		if (($object->getDomain() == '') && (isset($_POST[$prefix . 'tax_input']))) {
			foreach ($_POST[$prefix . 'tax_input'] as $key => $value) {
				$object->setDomain($key);
				break;
			}
		}
	}
}


?>