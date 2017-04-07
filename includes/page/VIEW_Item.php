<?php 

class VIEW_Item {

	
	public static function viewItemBasket () {
		VIEW_Item::viewItem(EAL_ItemBasket::get());
	}
	
	public static function viewItem (array $itemids = array()) {
		
		if ($_REQUEST['itemid'] != null) $itemids = [$_REQUEST['itemid']];

		if ($_REQUEST['itemids'] != null) {
			if (is_array($_REQUEST['itemids'])) $itemids = $_REQUEST['itemids'];
			if (is_string($_REQUEST['itemids'])) $itemids = explode (",", $_REQUEST["itemids"]);
		}

		$html_items = "";
		$html_select = "";
		$count = 0;
		foreach ($itemids as $item_id) {
			$post = get_post($item_id);
			if ($post == null) continue;
			
			$item = EAL_Item::load($post->post_type, $item_id);
			
			$html_select .= sprintf("<option value='%d'>%s</option>", $count, $item->title);
			$html_items  .= sprintf("
				<div id='poststuff'>
					<hr/>
					<div id='post-body' class='metabox-holder columns-2'>
						<div class='postbox-container' id='postbox-container-2'>
							<h1>%s</h1>%s
						</div>
						<div class='postbox-container' id='postbox-container-1'>
							<div style='background-color:#FFFFFF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>
							%s
							</div>
						</div>
					</div>
					<br style='clear:both;'/>
				</div>"
				, $item->title
				, HTML_Item::getHTML_Item($item, HTML_Object::VIEW_STUDENT)
				, HTML_Item::getHTML_Metadata($item, HTML_Object::VIEW_STUDENT, $item->id)
			);
			
			$count++;
		}
		
		
		
		
		printf ("
			<div class='wrap'>
				<h1>Item Viewer</h1>
				<form>
					 <select onChange='for (x=0; x<this.form.nextElementSibling.children.length; x++) {  this.form.nextElementSibling.children[x].style.display = ((this.value<0) || (this.value==x)) ? \"block\" :  \"none\"; }'>
						<option value='-1' selected>[All %d Items]</option>
						%s
					</select>
					<input type='checkbox' checked onChange='for (x=0; x<this.form.nextElementSibling.children.length; x++) { this.form.nextElementSibling.children[x].querySelector(\"#postbox-container-1\").style.display = (this.checked==true) ? \"block\" :  \"none\"; }'/> Show Metadata
				</form>
				<div>%s</div>
			</div>",
			count($itemids), $html_select, $html_items
		);
	}
	
	
	
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

}


?>