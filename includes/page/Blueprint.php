<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../anal/TaskPoolGenerator.php");
require_once(__DIR__ . "/../anal/TaskPoolGenerator_DS.php");
require_once(__DIR__ . "/../eal/EAL_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemBasket.php");


class Blueprint {

	/**
	 * Entry functions from menu
	 */
	
	public static function page_blueprint () {
	
		
		// run task pool generator; store taskpools in session variable ('tpg_generated_pools')
		if (isset ($_REQUEST['tpg_do_compute'])) {
			
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
		
		// show form
		print ("<div class='wrap'>");
		print ("<h1>Task Pool Generator</h1><br/>");
		print (self::getHTML_BlueprintForm ($items));
		
		// show (previosuly) generated task pools (if available)
		if (isset ($_SESSION['tpg_generated_pools'])) {
			print ("<br/><h2>Generated Task Pools</h2>");
			print self::getHTML_TaskPools($items, $_SESSION['tpg_generated_pools']);	
				
		}		
	}
	

	
	private static function getHTML_BlueprintForm (array $items): string {
		
		$itemids = array_values (array_map(function ($item) { return $item->id; }, $items));
		
		$html  = sprintf("<form  enctype='multipart/form-data' action='admin.php?page=test_generator' method='post'>");
		
		$html_box .= sprintf("
					<tr>
						%s
						<td style='padding-top:0px; padding-bottom:0px;'><label>&nbsp;&nbsp;%s</label></td>
					</tr>",
			self::getHTML_MinMaxField("number_all", count($items)), "Items"
			);
		
		$html.= sprintf ('
				<div id="mb_learnout" class="postbox ">
					<h2 class="hndle" style="padding-left:1em; padding-top:0"><input type="checkbox" disabled readonly checked/>&nbsp;Number of Items</h2>
				    <input type="hidden" name="tpg_do_compute" value="on">
					<div class="inside"><table>%s</table></div>
				</div>', $html_box);
		
		
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
					self::getHTML_MinMaxField($category . "_" . $key, count($groups[$key])), $val
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
		
		return $html;
		
	}
	
	
	private static function getHTML_TaskPools (array $items, array $pools): string {
		
		$result  = sprintf ("<table cellpadding='10px' class='widefat fixed' style='table-layout:fixed; width:%dem; background-color:rgba(0, 0, 0, 0);'>", 6+2*count($items));
		$result .= "<col width='6em;' />";
		$result .= str_repeat("<col width='2em;' />", count($items));
		
		foreach ($pools as $pool) {
			$result .= sprintf ("<tr valign='middle'>");
			$href = "admin.php?page=view_item&itemids=" . join (",", $pool);
			$result .= sprintf ("<td style='overflow: hidden; padding:0px; padding-bottom:0.5em; padding-top:0.5em; padding-left:1em' ><a href='%s' class='button'>View</a></td>", $href);
			
			foreach ($items as $item) {
				
				$symbol = "";
				$link = "";
				if (in_array($item->id, $pool)) {
					$link = sprintf ("onClick='document.location.href=\"admin.php?page=view_item&itemid=%s\";'", $item->id);
					if ($item->type == "itemsc") $symbol = "<span class='dashicons dashicons-marker'></span>";
					if ($item->type == "itemmc") $symbol = "<span class='dashicons dashicons-forms'></span>";
				}
				
				$result .= sprintf ("<td %s valign='bottom' style='overflow: hidden; padding:0px; padding-top:0.83em;' >%s</td>", $link, $symbol);
			}
			$result .= sprintf ("</tr>");
		}
		$result .= sprintf ("</table>");
		return $result;
		
	}
	
	
	private static function getHTML_MinMaxField ($name, $max): string {
	
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