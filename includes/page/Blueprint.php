<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../anal/TaskPoolGenerator.php");
require_once(__DIR__ . "/../eal/EAL_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemBasket.php");

// require_once(__DIR__ . "class.PAG_Basket.php");
// require_once(__DIR__ . "class.PAG_Explorer.php");

class Blueprint {

	/**
	 * Entry functions from menu
	 */
	
	public static function page_blueprint () {
	
		
		
// 		$vector = new \Ds\Vector();

// $vector->push(1, 3, 4, 5);
// $vector->push(9);
// print_r($vector);


		
// $vector2 = new \Ds\Vector();
// $vector2->push(11, 13, 14, 15);
// $vector2->push(19);
// print_r($vector2);

// print_r ($vector2 < $vector ? "ja" : "nein");

		
		
		if (isset ($_REQUEST['tpg_set_number'])) {
			
			$dimensions = [];
			foreach (['number', 'type', 'dim', 'level', 'topic1', 'topic2', 'topic3', 'lo'] as $category) {
				if (isset ($_REQUEST['tpg_set_' . $category])) {

					$dim = ["min" => [], "max" => []];
					foreach (["min","max"] as $minmax) {
						$prefix = 'tpg_' . $minmax . "_" . $category;
						foreach ($_REQUEST as $key => $value) {
							if (substr( $key, 0, strlen ($prefix)) == $prefix) {
								$dim[$minmax][substr($key, strlen ($prefix)+1)] = $value;
							}
						}
					}
					
					$dimensions[$category] = $dim;
				}
				
			}
			
			$tpg = new TaskPoolGenerator();
			$tpg->generatePools($dimensions);
		}
		
		
		
		$items = EAL_ItemBasket::getItems();
		$itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		
		$html  = sprintf("<form  enctype='multipart/form-data' action='admin.php?page=test_generator' method='post'>");
		
		/*
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
		*/
		
		// Min / Max for all categories
// 		$categories = array ("type", "dim", "level", "topic1");
// 		$buttons = ['type' => 'Item Type', 'dim' => 'Wissensdimension', 'level' => 'Anforderungsstufe', 'topic1' => 'Topic 1', 'topic2' => 'Topic 2', 'topic3' => 'Topic 3', 'lo' => 'Learning Outcome'];

		
		$category = "number";
		$sk_number = 'tpg_set_' . $category;
		
		$html_box .= sprintf("
					<tr>
						%s
						<td style='padding-top:0px; padding-bottom:0px;'><label>&nbsp;&nbsp;%s</label></td>
					</tr>",
				self::minMaxField($category . "_all", count($items)), "Items"
				);
		
		$html.= sprintf ('
				<div id="mb_learnout" class="postbox ">
					<h2 class="hndle" style="padding-left:1em; padding-top:0"><input type="checkbox" disabled readonly checked/>&nbsp;Number of Items</h2>
				    <input type="hidden" name="%s" value="on">
					<div class="inside"><table>%s</table></div>
				</div>', $sk_number, $html_box);
		
		
		foreach (['type', 'dim', 'level', 'topic1', 'topic2', 'topic3', 'lo'] as $category) {
				
			$html_box = "";
			$groups = ItemExplorer::groupBy($items, $itemids, $category);		// [key => [itemids]]
			$labels = ItemExplorer::getLabels($category, array_keys($groups));	// [key => label]
			foreach ($groups as $key => $val) {
				$html_box .= sprintf("
					<tr>
						%s
						<td style='padding-top:0px; padding-bottom:0px;'><label>&nbsp;&nbsp;%s</label></td>
					</tr>", 
					self::minMaxField($category . "_" . $key, count($val)), $labels[$key]
				);
				
			}
			
			$sk = 'tpg_set_' . $category;
			$_SESSION[$sk] = isset ($_REQUEST[$sk_number]) ? ( isset($_REQUEST[$sk]) ? $_REQUEST[$sk] : "off") : (isset($_SESSION[$sk]) ? $_SESSION[$sk] : "off");
			
			$html.= sprintf ('
				<div id="mb_learnout" class="postbox ">
					<h2 class="hndle" style="padding-left:1em; padding-top:0"><input type="checkbox" name="%s" %s onchange="this.parentNode.nextElementSibling.style.display = this.checked ? \'block\' : \'none\';"/>&nbsp;%s</h2>
					<div class="inside" style="display:%s"><table>%s</table></div>
				</div>', $sk, $_SESSION[$sk]=="on" ? "checked" : "", EAL_Item::$category_label[$category], $_SESSION[$sk]=="on" ? "block" : "none", $html_box);
		}
		
		$html .= sprintf ("<tr><th><button type='submit' name='action' value='generate'>Generate</button></th><tr>");
		$html .= sprintf ("</tbody></table></form></div>");
		
		
		
		 
		print ("<div class='wrap'>");
		print ("<h1>Task Pool Generator</h1><br/>");
		print ($html);
		
		
		
	}
	

	
	private static function minMaxField ($name, $max) {
	
		/* set/get values to/from Session Variable */
		$sk = ['tpg_min_' . $name, 'tpg_max_' . $name];
		$_SESSION[$sk[0]] = isset($_REQUEST[$sk[0]]) ? $_REQUEST[$sk[0]] : (isset($_SESSION[$sk[0]]) ? min ($_SESSION[$sk[0]], $max) : 0);
		$_SESSION[$sk[1]] = isset($_REQUEST[$sk[1]]) ? $_REQUEST[$sk[1]] : (isset($_SESSION[$sk[1]]) ? min ($_SESSION[$sk[1]], $max) : $max);
	
		/* generate HTML for min and max input */
		$html  = sprintf("<td style='vertical-align:top; padding-top:0px; padding-bottom:0px;'>");
		$html .= sprintf("<input style='width:3em' type='number' name='%s' min='0' max='%d' value='%d'/>", $sk[0], $max, $_SESSION[$sk[0]]);
		$html .= sprintf("&nbsp;&nbsp;-&nbsp;&nbsp;<input style='width:3em' type='number' name='%s' min='0' max='%d' value='%d'/>", $sk[1], $max, $_SESSION[$sk[1]]);
		$html .= sprintf("&nbsp;&nbsp;of &nbsp;&nbsp;<input style='width:3em' type='number' disabled readonly value='%d'/>", $max);
		$html .= sprintf("</td>");
		return $html;
	}
	
}


?>