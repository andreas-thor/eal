<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../eal/EAL_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemBasket.php");

// require_once(__DIR__ . "class.PAG_Basket.php");
// require_once(__DIR__ . "class.PAG_Explorer.php");

class TestGenerator {

	/**
	 * Entry functions from menu
	 */
	
	public static function page_generator () {
	
		
		$items = EAL_ItemBasket::getItems();
		$itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		
		$html  = sprintf("<form  enctype='multipart/form-data' action='admin.php?page=test_generator' method='post'><table class='form-table'><tbody'>");
		
		$html .= sprintf("<tr><th style='padding-top:0px; padding-bottom:0px;'><label>%s</label></th>", "Number of Items");
		$html .= self::minMaxField("number", count($items));
		$html .= sprintf("</tr>");
		
		$html .= sprintf("<tr><td style='vertical-align:top; padding-top:0px; padding-bottom:0px;'><label>%s</label></td>", "Overlap");
		$html .= self::minMaxField("overlap", count($items));
		$html .= sprintf("</tr>");
		$html .= sprintf("<tr><td style='vertical-align:top; padding-top:0px; padding-bottom:0px;'><button type='button' onclick='
		
				if (this.innerText == \"Select ...\") {
					this.innerText = \"Close\";
					this.parentNode.nextSibling.firstChild.style.display=\"block\";
				} else {
					this.innerText = \"Select ...\";
					this.parentNode.nextSibling.firstChild.style.display=\"none\";
				}
		
				'>Select ...</button></td>");
		$html .= ("<td><div style='display:none'>");
		foreach ($items as $i) $html .= sprintf ("<input type='checkbox' name='overlap_items[]' value='%d'><label style='vertical-align:top'>%s</label><br/>", $i->id, $i->title);
		$html .= sprintf("</div></td></tr>");
		
		// Min / Max for all categories
// 		$categories = array ("type", "dim", "level", "topic1");
// 		$buttons = ['type' => 'Item Type', 'dim' => 'Wissensdimension', 'level' => 'Anforderungsstufe', 'topic1' => 'Topic 1', 'topic2' => 'Topic 2', 'topic3' => 'Topic 3', 'lo' => 'Learning Outcome'];
		
		foreach (['type', 'dim', 'level', 'topic1', 'topic2', 'topic3', 'lo'] as $category) {
				
			$html .= sprintf("<tr><th style='padding-bottom:0.5em;'><label>%s</label></th></tr>", EAL_Item::$category_label[$category]);
			
			$groups = ItemExplorer::groupBy($items, $itemids, $category);		// [key => [itemids]]
			$labels = ItemExplorer::getLabels($category, array_keys($groups));	// [key => label]
			foreach ($groups as $key => $val) {
				$html .= sprintf("<tr><td style='padding-top:0px; padding-bottom:0px;'><label>%s</label></td>", $labels[$key]);
						// ($category == "topic1") ? get_term($catval, RoleTaxonomy::getCurrentRoleDomain()["name"])->name : EAL_Item::$category_value_label[$category][$catval]);
				$html .= self::minMaxField($category . "_" . $key, count($val));
				$html .= sprintf("</tr>");
			}
			
			
// 			foreach (PAG_Explorer::groupBy ($category, $items, NULL, true, true) as $catval => $catitems) {
		
// 				$html .= sprintf("<tr><td style='padding-top:0px; padding-bottom:0px;'><label>%s</label></td>", ($category == "topic1") ? get_term($catval, RoleTaxonomy::getCurrentRoleDomain()["name"])->name : EAL_Item::$category_value_label[$category][$catval]);
// 				$html .= PAG_Generator::minMaxField($category . "_" . $catval, count($catitems));
// 				$html .= sprintf("</tr>");
// 			}
				
		}
		
		$html .= sprintf ("<tr><th><button type='submit' name='action' value='generate'>Generate</button></th><tr>");
		$html .= sprintf ("</tbody></table></form></div>");
		
		
		
		 
		print ("<div class='wrap'>");
		print ("<h1>Task Pool Generator</h1><br/>");
		print ($html);
		
		
		
	}
	

	
	private static function minMaxField ($name, $max) {
	
		/* set/get values to/from Session Variable */
		$_SESSION['min_' . $name] = isset($_REQUEST['min_' . $name]) ? $_REQUEST['min_' . $name] : (isset($_SESSION['min_' . $name]) ? min ($_SESSION['min_' . $name], $max) : 0);
		$_SESSION['max_' . $name] = $max; // isset($_REQUEST['max_' . $name]) ? $_REQUEST['max_' . $name] : (isset($_SESSION['max_' . $name]) ? min ($_SESSION['max_' . $name], $max) : $max);
	
		/* generate HTML for min and max input */
		$html  = sprintf("<td style='vertical-align:top; padding-top:0px; padding-bottom:0px;'>");
		$html .= sprintf("<input style='width:5em' type='number' name='min_%s' min='0' max='%d' value='%d'/>", $name, $max, $_SESSION['min_' . $name]);
		$html .= sprintf("<input style='width:5em' type='number' name='max_%s' min='0' max='%d' value='%d'/>", $name, $max, $_SESSION['max_' . $name]);
		$html .= sprintf("</td>");
		return $html;
	}
	
}


?>