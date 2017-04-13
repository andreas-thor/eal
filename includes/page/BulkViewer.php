<?php 

class BulkViewer {

	/**
	 * Entry functions from menu
	 */
	
	public static function page_view_item () {
		
		$itemids = array();
		if ($_REQUEST['itemid'] != null) $itemids = [$_REQUEST['itemid']];
		if ($_REQUEST['itemids'] != null) {
			if (is_array($_REQUEST['itemids'])) $itemids = $_REQUEST['itemids'];
			if (is_string($_REQUEST['itemids'])) $itemids = explode (",", $_REQUEST["itemids"]);
		}
		
		self::viewItems($itemids, NULL, $_REQUEST['edit']=='1');
	}
	
	
	public static function page_view_basket () {
		self::viewItems(EAL_ItemBasket::get(), NULL, FALSE);
		
	}
	
	public static function page_view_review () {

		$itemids = array();
		if ($_REQUEST['itemid'] != null) $itemids = [$_REQUEST['itemid']];
		if ($_REQUEST['itemids'] != null) {
			if (is_array($_REQUEST['itemids'])) $itemids = $_REQUEST['itemids'];
			if (is_string($_REQUEST['itemids'])) $itemids = explode (",", $_REQUEST["itemids"]);
		}
		
		$reviewids = array();
		if ($_REQUEST['reviewid'] != null) $reviewids = [$_REQUEST['reviewid']];
		if ($_REQUEST['reviewids'] != null) {
			if (is_array($_REQUEST['reviewids'])) $reviewids = $_REQUEST['reviewids'];
			if (is_string($_REQUEST['reviewids'])) $reviewids = explode (",", $_REQUEST["reviewids"]);
		}
		
		self::viewItems($itemids, $reviewids, $_REQUEST['edit']=='1');
	}
	
	
	public static function page_view_learnout () {
	
		$learnoutids = array();
		if ($_REQUEST['learnoutid'] != null) $learnoutids = [$_REQUEST['learnoutid']];
		if ($_REQUEST['learnoutids'] != null) {
			if (is_array($_REQUEST['learnoutids'])) $learnoutids = $_REQUEST['learnoutids'];
			if (is_string($_REQUEST['learnoutids'])) $learnoutids = explode (",", $_REQUEST["learnoutids"]);
		}
		
		self::viewLearnOuts($learnoutids);
	}
	
	
	
	
	public static function getHTML_List (string $title, string $content, array $entries_title, array $entries_content, string $action=""): string {
		
		// generate list of item titles 
		$options = '<option value="-1" selected>[All]</option>';
		foreach ($entries_title as $key => $val) {
			$options .= sprintf ('<option value="%d">%d. %s</option>', $key, $key+1, $val);
		}
		
		$selectItem = sprintf ('
			<div class="postbox ">
				<h2 class="hndle">
					<span><input type="submit" id="bulk_view_action_button" value="Download %d Items" class="button button-primary" /></span>
					<span style="float: right; font-weight:normal"><a href="">Edit All Items</a></span>
				</h2>
				<div class="inside">
					<select style="width:100%%" align="right" onChange="d = document.getElementById(\'itemcontainer\'); for (x=0; x<d.children.length; x++) {  d.children[x].style.display = ((this.value<0) || (this.value==x)) ? \'block\' :  \'none\'; }">
						%s
					</select>
					<br/>&nbsp;&nbsp;<input type="checkbox" checked onChange="d = document.getElementById(\'itemcontainer\'); for (x=0; x<d.children.length; x++) { d.children[x].querySelector(\'#postbox-container-1\').style.display = (this.checked==true) ? \'block\' :  \'none\'; }"> Show Metadata</input>
				</div>
			</div>
			', count($entries_title), $options);
		
		return sprintf ('
				<div id="poststuff">
					%1$s
					<br style="clear:both;"/>
				</div>
				<div id="itemcontainer">%2$s</div>
			',
			self::getHTML_Body($title, $content, $selectItem), implode("", $entries_content));
		
	}
	
	
	
	public static function getHTML_Body (string $title, string $content, string $metadata) {
		
		return sprintf ('
			<div id="post-body" class="metabox-holder columns-2">
				<div class="postbox-container" id="postbox-container-2">
					<h1>%1$s</h1>%2$2s
				</div>
				<div class="postbox-container" id="postbox-container-1">
					<div style="background-color:#FFFFFF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;">%3$s</div>
				</div>
			</div>',
			$title, $content, $metadata);
	}
	
	
	
	
	/**
	 * 
	 * @param array $itemids
	 * @param array $reviewids
	 */
	
	public static function viewItems (array $itemids, $reviewids, bool $editable) {
		
		// load all items
		$items = array ();
		foreach ($itemids as $item_id) {
			if (array_key_exists($item_id, $items)) continue;	// item already loaded
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$items[$item_id] = EAL_Item::load($post->post_type, $item_id);
		}
		
		if (!is_null($reviewids)) {
			// load all reviews
			$reviews = array();
			foreach ($reviewids as $review_id) {
				if (array_key_exists($review_id, $reviews)) continue;	// review already loaded
				$reviews[$review_id] = new EAL_Review($review_id);
				if (!array_key_exists($reviews[$review_id]->item_id, $items)) {
					$items[$reviews[$review_id]->item_id] = $reviews[$review_id]->getItem();	// load item if necessary	
				}
			}
			
			// if no reviewids given --> load all reviews for all items (if available)
			if (count ($reviewids)==0) {
				foreach ($items as $item) {
					$itemreviews = $item->getReviews();
					if (count($itemreviews) == 0) {
						unset($items[$item->id]);	// remove items without any review
					} else {
						foreach ($itemreviews as $review) {
							$reviews[$review->id] = $review;	// add reviews (and keep item)
						}
					}
				}
			}
		}
		
		
		$items_content = array();
		$items_title = array();
		$count = 0;
		foreach ($items as $item) {

			$html_reviews = "";
			if (!is_null($reviewids)) {
				foreach ($item->getReviewIds() as $review_id) {
					if (array_key_exists($review_id, $reviews)) {
						$html_reviews  .= sprintf ('%s <br style="clear:both;"/>', 
								self::getHTML_Body("", 
							HTML_Review::getHTML_Review($reviews[$review_id], HTML_Object::VIEW_REVIEW, "review_{$review_id}_"),
							HTML_Review::getHTML_Metadata($reviews[$review_id], HTML_Object::VIEW_REVIEW, "review_{$review_id}_")));
					}
				}
			}
			
			array_push($items_title, $item->title);
			array_push($items_content, sprintf('
				<div id="poststuff">
					<hr/>
					%s
					<br style="clear:both;"/>
					<div style="margin-left:3em">
						%s
					</div>
				</div>',
				self::getHTML_Body($item->title, HTML_Item::getHTML_Item($item, $editable ? HTML_Object::VIEW_REVIEW : HTML_Object::VIEW_STUDENT), HTML_Item::getHTML_Metadata($item, $editable ? HTML_Object::VIEW_EDIT : HTML_Object::VIEW_STUDENT, $item->id)),
				$html_reviews
			));

			$count++;
		}
		
		print self::getHTML_List(sprintf ('Item %sViewer', is_null($reviewids) ? '' : '+ Review '), '', $items_title, $items_content);
		
	}
	
	
	
	
	
	
	
	
	
	public static function viewLearnOuts (array $learnoutids = array()) {
	
		$los_content = array();
		$los_title = array();
		foreach ($learnoutids as $learnout_id) {
			$learnout = new EAL_LearnOut($learnout_id);
				
			array_push($los_title, $learnout->title);
			array_push($los_content, sprintf('
				<div id="poststuff">
					<hr/>
					%s
					<br style="clear:both;"/>
				</div>',
				self::getHTML_Body($learnout->title, HTML_Learnout::getHTML_LearnOut($learnout)	, HTML_Learnout::getHTML_Metadata($learnout))
			));
		}
	
	
		print self::getHTML_List("Learning Outcome Viewer", '', $los_title, $los_content);

	}	
	
}


?>