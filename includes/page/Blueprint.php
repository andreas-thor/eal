<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../anal/TaskPoolGenerator.php");
require_once(__DIR__ . "/../anal/TaskPoolGenerator_DS.php");
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
			
			$_SESSION['tpg_generated_pools'] = [];
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
			
			$tpg = new TaskPoolGenerator($_REQUEST['tpg_time']);
			$_SESSION['tpg_generated_pools'] = $tpg->generatePools($dimensions);
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
			foreach ($labels as $key => $val) {
				$html_box .= sprintf("
					<tr>
						%s
						<td style='padding-top:0px; padding-bottom:0px;'><label>&nbsp;&nbsp;%s</label></td>
					</tr>", 
					self::minMaxField($category . "_" . $key, count($groups[$key])), $val
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
		
		$sk = 'tpg_time';
		$_SESSION[$sk] = isset($_REQUEST[$sk]) ? min ($_REQUEST[$sk], 30000) : (isset($_SESSION[$sk]) ? min ($_SESSION[$sk], 30000) : 10);
		
		$html .= sprintf ("
			<tr><th>
				<button type='submit' name='action' value='generate'>Generate</button>
				&nbsp;&nbsp;&nbsp;&nbsp;in maximal
				<input style='width:4em' type='number' name='tpg_time' min='0' max='3000' value='%d'/>
				seconds
			</th></tr>",
				$_SESSION['tpg_time']);
		$html .= sprintf ("</tbody></table></form></div>");
		
		
		
		 
		print ("<div class='wrap'>");
		print ("<h1>Task Pool Generator</h1><br/>");
		print ($html);
		
		
		if (isset ($_SESSION['tpg_generated_pools'])) {
			
			// print_r ($_SESSION['generated_pools']);
		
			print ("<br/><h2>Generated Task Pools</h2>");
			printf ("<table cellpadding='10px' class='widefat fixed' style='table-layout:fixed; width:%dem; background-color:rgba(0, 0, 0, 0);'>", 6+2*count($items));
			print ("<col width='6em;' />");
				
			foreach ($items as $item) {
				print ("<col width='2em;' />");
			}
				
			foreach ($_SESSION['tpg_generated_pools'] as $pool) {
				print ("<tr valign='middle'>");
				$s = "View";
				$href = "admin.php?page=view_item&itemids=" . join (",", $pool);
				printf ("<td style='overflow: hidden; padding:0px; padding-bottom:0.5em; padding-top:0.5em; padding-left:1em' ><a href='%s' class='button'>View</a></td>", $href);
		
				// http://localhost/wordpress/wp-admin/admin.php?page=view&itemids=458,307,307,106
				foreach ($items as $item) {
						
					$symbol = "";
					$link = "";
					if (in_array($item->id, $pool)) {
						$link = sprintf ("onClick='document.location.href=\"admin.php?page=view_item&itemid=%s\";'", $item->id);
						if ($item->type == "itemsc") $symbol = "<span class='dashicons dashicons-marker'></span>";
						if ($item->type == "itemmc") $symbol = "<span class='dashicons dashicons-forms'></span>";
					}
						
						
					printf ("<td %s valign='bottom' style='overflow: hidden; padding:0px; padding-top:0.83em;' >%s</td>", $link, $symbol /*(in_array($item->id, $pool) ? "X" : "")*/);
				}
				print ("</tr>");
			}
			print ("</table>");
				
				
		}		
		
		
	}
	

	
	private static function minMaxField ($name, $max) {
	
		/* set/get values to/from Session Variable */
		$sk = ['tpg_min_' . $name, 'tpg_max_' . $name];
		$_SESSION[$sk[0]] = isset($_REQUEST[$sk[0]]) ? $_REQUEST[$sk[0]] : (isset($_SESSION[$sk[0]]) ? min ($_SESSION[$sk[0]], $max) : 0);
		$_SESSION[$sk[1]] = isset($_REQUEST[$sk[1]]) ? $_REQUEST[$sk[1]] : (isset($_SESSION[$sk[1]]) ? min ($_SESSION[$sk[1]], $max) : $max);
	
		/* generate HTML for min and max input */
		$html  = sprintf("<td style='vertical-align:top; padding-top:0px; padding-bottom:0px;'>");
		$html .= sprintf("<input style='width:4em' type='number' name='%s' min='0' max='%d' value='%d'/>", $sk[0], $max, $_SESSION[$sk[0]]);
		$html .= sprintf("&nbsp;&nbsp;-&nbsp;&nbsp;<input style='width:4em' type='number' name='%s' min='0' max='%d' value='%d'/>", $sk[1], $max, $_SESSION[$sk[1]]);
		$html .= sprintf("&nbsp;&nbsp;of &nbsp;&nbsp;<input style='width:4em' type='number' disabled readonly value='%d'/>", $max);
		$html .= sprintf("</td>");
		return $html;
	}
	
}


?>