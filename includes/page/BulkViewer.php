<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../html/HTML_Item.php");
require_once(__DIR__ . "/../html/HTML_ItemBasket.php");
require_once(__DIR__ . "/../html/HTML_Review.php");

class BulkViewer {

	

	
	/**
	 * Entry functions from menu
	 */
	
	public static function page_view_item ($itemids = NULL) {

		if ($itemids == NULL) {
			$itemids = ItemExplorer::getItemIdsByRequest();
		}
		
		if ($_POST['action']=='import') $itemids = Importer::doImport($itemids, FALSE);
		if ($_POST['action']=='update') $itemids = Importer::doImport($itemids, TRUE);
		self::viewItems($itemids, NULL, $_REQUEST['edit']=='1', $_REQUEST["page"]);
	}
	
	
	public static function page_view_basket () {
		self::page_view_item(EAL_ItemBasket::get());
	}
	
	public static function page_view_review () {

		$itemids = $itemids = ItemExplorer::getItemIdsByRequest();
		
		$reviewids = array();
		if ($_REQUEST['reviewid'] != null) $reviewids = [$_REQUEST['reviewid']];
		if ($_REQUEST['reviewids'] != null) {
			if (is_array($_REQUEST['reviewids'])) $reviewids = $_REQUEST['reviewids'];
			if (is_string($_REQUEST['reviewids'])) $reviewids = explode (",", $_REQUEST["reviewids"]);
		}
		
		if ($_POST['action']=='update') $itemids = Importer::doImport($itemids, TRUE);
		self::viewItems($itemids, $reviewids, $_REQUEST['edit']=='1', $_REQUEST["page"]);
	}
	
	
	public static function page_view_learnout () {
	
		$learnoutids = array();
		if ($_REQUEST['learnoutid'] != null) $learnoutids = [$_REQUEST['learnoutid']];
		if ($_REQUEST['learnoutids'] != null) {
			if (is_array($_REQUEST['learnoutids'])) $learnoutids = $_REQUEST['learnoutids'];
			if (is_string($_REQUEST['learnoutids'])) $learnoutids = explode (",", $_REQUEST["learnoutids"]);
		}
		
		if ($_POST['action']=='import') $learnoutids = Importer::doUpdateLearnOuts();
		self::viewLearnOuts($learnoutids, $_REQUEST['edit']=='1', $_REQUEST["page"]);
	}
	
	
	
	/**
	 * 
	 * @param string $title
	 * @param string $content
	 * @param array $entries_title
	 * @param array $entries_content
	 * @param string $editlink "Edit all items" url
	 * @return string
	 */
	public static function getHTML_List (string $title, string $content, array $entries_title, array $entries_content, string $editurl="", bool $isItem=TRUE): string {
		
		// generate list of item titles 
		$options = '<option value="-1" selected>[All]</option>';
		foreach ($entries_title as $key => $val) {
			$options .= sprintf ('<option value="%d">%s</option>', $key, $val);
		}
		
		$selectItem = sprintf ('
			<div class="postbox ">
				<h2 class="hndle">
					<span><input style="visibility:%1$s" type="submit" id="bulk_view_action_button" value="%2$s %3$d %4$s" class="button button-primary" /></span>
					<span style="float: right; font-weight:normal">%5$s</span>
				</h2>
				<div class="inside">
					<select style="width:100%%" align="right" onChange="d = document.getElementById(\'itemcontainer\'); for (x=0; x<d.children.length; x++) {  d.children[x].style.display = ((this.value<0) || (this.value==x)) ? \'block\' :  \'none\'; } document.getElementById(\'itemstats\').style.display = (this.value<0) ? \'block\' :  \'none\';">
						%6$s
					</select>
					<br/>&nbsp;&nbsp;<input type="checkbox" checked onChange="d = document.getElementById(\'itemcontainer\'); for (x=0; x<d.children.length; x++) { d.children[x].querySelector(\'#postbox-container-1\').style.display = (this.checked==true) ? \'block\' :  \'none\'; } document.getElementById(\'itemstats\').querySelector(\'#postbox-container-2\').style.display = (this.checked==true) ? \'block\' :  \'none\';"> Show Metadata</input>
				</div>
			</div>
			', 
			$isItem || ($editurl == '') ? "visible" : "hidden",		// (1) hide action button if learning outcome ($isItem==FALSE) are viewed (not edited, i.e., $editurl has a value)
			($editurl == '') ? "Update" : "Download", 				// (2) label for action button
			count($entries_title), 									// (3) number of items / learning outcomes
			$isItem ? "Items" : "Learn. Out.", 						// (4) type caption 
			($editurl == '') ? '' : sprintf ('<a href="%s">Edit All %s</a>', $editurl, $isItem ? "Items" : "LOs"),		// (5) Edit link + caption 
			$options);												// (6) options list in drop down
		
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
		
// 		<div class="postbox-container" id="postbox-container-1">
// 		<div style="background-color:#FFFFFF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;">%3$s</div>
// 		</div>
		
		return sprintf ('
			<div id="post-body" class="metabox-holder columns-2">
				<div class="postbox-container" id="postbox-container-2">
					<h1>%1$s</h1>%2$s
				</div>
				<div class="postbox-container" id="postbox-container-1">
<div style="background-color:#FFFFFF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;">%3$s</div>
		 		</div>
			</div>',
			$title, $content , $metadata);
	}
	
	
	
	
	/**
	 * 
	 * @param array $itemids
	 * @param array $reviewids
	 */
	
	public static function viewItems (array $itemids, $reviewids, bool $editable, string $page) {
		
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
						unset($items[$item->getId()]);	// remove items without any review
					} else {
						foreach ($itemreviews as $review) {
							$reviews[$review->getId()] = $review;	// add reviews (and keep item)
						}
					}
				}
			}
		}
		
		
		$items_content = array();
		$items_title = array();
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
			
			array_push($items_title, $item->getId() . ". " . $item->title);
			array_push($items_content, sprintf('
				<div id="poststuff">
					<hr/>
					%s
					<br style="clear:both;"/>
					<div style="margin-left:3em">
						%s
					</div>
				</div>',
				self::getHTML_Body($item->title, 
					HTML_Item::getHTML_Item    ($item, $editable ? HTML_Object::VIEW_REVIEW : HTML_Object::VIEW_STUDENT, "item_{$item->getId()}_"),	// REVIEW 
					HTML_Item::getHTML_Metadata($item, $editable ? HTML_Object::VIEW_EDIT   : HTML_Object::VIEW_STUDENT, "item_{$item->getId()}_")), // EDIT
				$html_reviews
			));
		}
		
		// generate "Edit All Items" link
		$url = sprintf ('admin.php?page=%s', $page);
		if (count($itemids)>0) $url .= "&itemids=" . implode (',', $itemids);
		if (!is_null($reviewids)) $url .= "&reviewids=" . implode (',', $reviewids);

		$download_url = plugin_dir_url( __DIR__ . "/../../download.php" ) . "download.php?itemids=" . implode (',', $itemids);
		$download_url = 'admin.php?page=download';
		
		
		
		$stat = sprintf ('<div id="itemstats"><div id="postbox-container-2">%s</div></div>' , HTML_ItemBasket::getHTML_Statistics($items));
		
		
		
		
// 		if (!$editable) {

// 			printf ('
// 				<form  enctype="multipart/form-data" action="%s&edit=0" method="post">
// 					<input type="hidden" id="itemids" name="itemids" value="%s">
// 					<input type="hidden" name="action" value="import">
// 					%s
// 				</form>',
// 					$url, implode (',', $itemids),
// 					self::getHTML_List(sprintf ('Item %sViewer', is_null($reviewids) ? '' : '+ Review '), '', $items_title, $items_content, $url . "&edit=1"));
// 		} else {
			
		
		
		printf ('<div class="wrap"><h1>Item Viewer <a href="%s/wp-admin/admin.php?page=view_item&itemids=%s&edit=1" class="page-title-action">Edit All Items</a></h1>', site_url(), implode (',', $itemids));
		
		print (self::getHTML_List(sprintf ('aItem %sViewer', is_null($reviewids) ? '' : '+ Review '), $stat, $items_title, $items_content, $editable ? '' : $url . "&edit=1"));
/*		
			printf ('
<form  enctype="multipart/form-data" action="%s" method="post">
					<input type="hidden" id="itemids" name="itemids" value="%s">
					<input type="hidden" name="action" value="%s">
					%s
				</form>',
				$editable ? $url . "&edit=0" : $download_url, 
				implode (',', $itemids), 
				$editable ? "update" : "download", 
				self::getHTML_List(sprintf ('Item %sViewer', is_null($reviewids) ? '' : '+ Review '), $stat, $items_title, $items_content, $editable ? '' : $url . "&edit=1"));
*/				
// 		}
		
	}
	
	
	
	
	
	
	
	
	
	public static function viewLearnOuts (array $learnoutids = array(), bool $editable, string $page) {
	
		$los_content = array();
		$los_title = array();
		foreach ($learnoutids as $learnout_id) {
			$learnout = new EAL_LearnOut($learnout_id);
				
			array_push($los_title, $learnout_id . ". " . $learnout->title);
			array_push($los_content, sprintf('
				<div id="poststuff">
					<hr/>
					%s
					<br style="clear:both;"/>
				</div>',
				self::getHTML_Body($learnout->title, 
					HTML_Learnout::getHTML_LearnOut($learnout, "lo_{$learnout->getId()}_"), 
					HTML_Learnout::getHTML_Metadata($learnout, $editable ? HTML_Object::VIEW_EDIT   : HTML_Object::VIEW_STUDENT, "lo_{$learnout->getId()}_"))
			));
		}
	
	
		// generate "Edit All Items" link
		$url = sprintf ('admin.php?page=%s', $page);
		if (count($learnoutids)>0) $url .= "&learnoutids=" . implode (',', $learnoutids);
		
// 		if (!$editable) {
			printf ('
				<form  enctype="multipart/form-data" action="%s&edit=0" method="post">
					<input type="hidden" id="learnoutids" name="learnoutids" value="%s">
					<input type="hidden" name="action" value="%s">
					%s
				</form>',
					$url, implode (',', $learnoutids),
					$editable ? "download" : "import",
					self::getHTML_List("Learning Outcome Viewer", '', $los_title, $los_content, $editable ? '' : $url . "&edit=1", FALSE));
// 		} else {
// 			printf ('
// 				<form  enctype="multipart/form-data" action="%s&edit=0" method="post">
// 					<input type="hidden" id="learnoutids" name="learnoutids" value="%s">
// 					<input type="hidden" name="action" value="import">
// 					%s
// 				</form>',
// 					$url, implode (',', $learnoutids), 
// 					self::getHTML_List("Learning Outcome Viewer", '', $los_title, $los_content, '', FALSE));
// 		}
		
		
	}	
	
}


?>