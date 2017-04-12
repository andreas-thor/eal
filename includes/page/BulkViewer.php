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
		
		self::viewItems($itemids);
	}
	
	
	public static function page_view_basket () {
		self::viewItems(EAL_ItemBasket::get());
		
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
		
		self::viewItems($itemids, $reviewids);
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
	
	
	
	
	private static function getHTML_List (string $title, string $select, string $options, string $content): string {
		
		return sprintf ('
			<div class="wrap">
				<h1>%s</h1>
				<form>
					 <select onChange="for (x=0; x<this.form.nextElementSibling.children.length; x++) {  this.form.nextElementSibling.children[x].style.display = ((this.value<0) || (this.value==x)) ? \'block\' :  \'none\'; }">
						<option value="-1" selected>[%2$s]</option>
						%3$s
					</select>
					<input type="checkbox" checked onChange="for (x=0; x<this.form.nextElementSibling.children.length; x++) { this.form.nextElementSibling.children[x].querySelector(\'#postbox-container-1\').style.display = (this.checked==true) ? \'block\' :  \'none\'; }"/> Show Metadata
				</form>
				<div>%4$s</div>
			</div>',
			$title, $select, $options, $content);
	}
	
	
	
	private static function getHTML_Body (string $title, string $content, string $metadata) {
		
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
	
	public static function viewItems (array $itemids = array(), array $reviewids = NULL) {
		
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
		
		
		$html_items = "";
		$html_select = "";
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
			
			$html_select .= sprintf('<option value="%d">%s</option>', $count, $item->title);
			$html_items  .= sprintf('
				<div id="poststuff">
					<hr/>
					%s
					<br style="clear:both;"/>
					<div style="margin-left:3em">
						%s
					</div>
				</div>',
				self::getHTML_Body($item->title, HTML_Item::getHTML_Item($item, HTML_Object::VIEW_STUDENT), HTML_Item::getHTML_Metadata($item, HTML_Object::VIEW_STUDENT, $item->id)),
				$html_reviews
			);

			$count++;
		}
		
		print self::getHTML_List("Item + Review Viewer", sprintf ("All %d Items", count($items)), $html_select, $html_items);
		
	}
	
	
	
	
	
	
	
	
	
	public static function viewLearnOuts (array $learnoutids = array()) {
	
		$html_entry = "";
		$html_select = "";
		$count = 0;
		foreach ($learnoutids as $learnout_id) {
			$learnout = new EAL_LearnOut($learnout_id);
				
			$html_select .= sprintf('<option value="%d">%s</option>', $count, $learnout->title);
			$html_items  .= sprintf('
				<div id="poststuff">
					<hr/>
					%s
					<br style="clear:both;"/>
				</div>',
				self::getHTML_Body($learnout->title, HTML_Learnout::getHTML_LearnOut($learnout)	, HTML_Learnout::getHTML_Metadata($learnout))
			);
			
			$count++;
		}
	
	
		print self::getHTML_List("Learning Outcome Viewer", sprintf ("All %d Learning Outcomes", count($learnoutids)), $html_select, $html_items);

	}	
	
	
	
/*	
	
	
	
	
	
	
	
	public static function viewItem2 () {
		
		
		$itemids = array();
		
		if ($_REQUEST['itemid'] != null) $itemids = [$_REQUEST['itemid']];
		if ($_REQUEST['itemids'] != null) {
			if (is_array($_REQUEST['itemids'])) $itemids = $_REQUEST['itemids'];
			if (is_string($_REQUEST['itemids'])) $itemids = explode (",", $_REQUEST["itemids"]);
		}
		
		
		
		$html_list = "";
		$html_select  = "<form><select onChange='for (x=0; x<this.form.nextSibling.childNodes.length; x++) {  this.form.nextSibling.childNodes[x].style.display = ((this.value<0) || (this.value==x)) ? \"block\" :  \"none\"; }'>";
		$html_select .= sprintf ('<option value="-1" selected>[All %1$d Items]</option>', count($itemids));
		$count = 0;
		$items = array ();
		foreach ($itemids as $item_id) {
				
			$post = get_post($item_id);
			if ($post == null) continue;
				
			$item = EAL_Item::load($post->post_type, $item_id);
		
			if ($item != null) {
				$html_select .= sprintf ("<option value='%d'>%s</option>", $count, $item->title);
				$html_list .= sprintf ("
					<div class='wp-clearfix'>
						%s
					</div>",
						HTML_Item::getHTML_Item($item, HTML_Object::VIEW_STUDENT) );
				$count++;
				array_push($items, $item);
			}
		}
		
// 		<br style='clear:both;'/>
		$html_select .= "</select>&nbsp;&nbsp;&nbsp;<input type='checkbox' checked
			onChange='for (x=0; x<this.form.nextSibling.childNodes.length; x++) { this.form.nextSibling.childNodes[x].querySelector(\"#postbox-container-1\").style.display = (this.checked==true) ? \"block\" :  \"none\"; }'/> Show Metadata</form>";
		
		
		$html_info  = sprintf("<form  style='margin-top:5em' enctype='multipart/form-data' action='admin.php?page=view&download=1&itemids=%s' method='post'><table class='form-table'><tbody'>", implode(",",$itemids));
		$html_info .= sprintf("<tr><th style='padding-top:0px; padding-bottom:0px;'><label>%s</label></th>", "Number of Items");
		$html_info .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
		$html_info .= sprintf("<input style='width:5em' type='number' value='%d' readonly/>", count($items));
		$html_info .= sprintf("</td></tr>");
			
		// Min / Max for all categories
		$categories = array ("type", "dim", "level", "topic1");
		foreach ($categories as $category) {
				
			$html_info .= sprintf("<tr><th style='padding-bottom:0.5em;'><label>%s</label></th></tr>", EAL_Item::$category_label[$category]);
			foreach (PAG_Explorer::groupBy ($category, $items, NULL, true) as $catval => $catitems) {
					
					
				// topic id statt topic name
					
				$html_info .= sprintf("<tr><td style='padding-top:0px; padding-bottom:0px;'><label>%s</label></td>", ($category == "topic1") ? $catval : EAL_Item::$category_value_label[$category][$catval]);
				$html_info .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
				$html_info .= sprintf("<input style='width:5em' type='number' value='%d' readonly/>", count($catitems));
				$html_info .= sprintf("</td></tr>");
			}
				
		}
			
		$html_info .= sprintf ("<tr><th><button type='submit' name='action' value='download'>Download</button></th><tr>");
		$html_info .= sprintf ("</tbody></table></form></div>");
		
		
		printf ('<div class="wrap"><h1>Item Viewer</h1>');
		
		if ($_REQUEST['download']=='1') {
			$ilias = new EXP_Ilias();
			$link = $ilias->generateExport($itemids);
			printf ("<h2><a href='%s'>Download</a></h2>", $link);
		}
		
		
		if ((count($itemids)>1) || (count($itemids)==1)) {
			print $html_select;
			print "<div style='margin-top:2em'>{$html_list}{$html_info}</div>";
		} else {
			print "<div style='margin-top:2em'>{$html_list}</div>";
		}
		print "</div>";
			
		
	}
*/
}


?>