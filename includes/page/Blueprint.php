<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../anal/TaskPoolGenerator.php");
require_once(__DIR__ . "/../eal/EAL_Item.php");
require_once(__DIR__ . "/../eal/EAL_ItemBasket.php");


class Blueprint {

	/**
	 * Entry functions from menu
	 */
	
	public static function page_blueprint () {
	
		
		// run task pool generator; store taskpools in session variable ('tpg_generated_pools')
		if (isset ($_REQUEST['tpg_set_number'])) {
			
			// we store all _REQUEST data in the _SESSION variable
			$sk = 'tpg_time';
			$_SESSION[$sk] = isset($_REQUEST[$sk]) ? max (min ($_REQUEST[$sk], 30000), 1) : 10;
			
			$dimensions = [];
			foreach (['number', 'type', 'dim', 'level', 'topic1', 'topic2', 'topic3', 'lo'] as $category) {
				
				$sk = 'tpg_set_' . $category;
				$_SESSION[$sk] = $_REQUEST[$sk] ?? "off";
				if (isset ($_REQUEST[$sk])) {

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
			$tpg->generatePools($dimensions, $_SESSION['tpg_time']);
		}
		
		
		
		$items = EAL_ItemBasket::getItems();
		
		// show form
		print ("<div class='wrap'>");
		print ("<h1>Task Pool Generator</h1><br/>");
		print (self::getHTML_BlueprintForm ($items));
		
		// show (previosuly) generated task pools (if available)
		if (isset ($_SESSION['tpg_numberOfItemPools'])) {
			printf ("<br/><h2>%s generated Task Pools (using %s; check online %s)</h2>", $_SESSION["tpg_numberOfItemPools"], implode (", ", array_keys($_SESSION["tpg_dimensions_Pool"])), implode(", ", array_keys($_SESSION["tpg_dimensions_ToCheck"])));
			print ('<div id="itempoolstable"></div>');
			
			?>
			<script type="text/javascript" >
	
			jQuery(document).ready(function($) {
				getItemPools(<?php print ($_SESSION["itempools_time"]??5) ?>, <?php print ($_SESSION["itempools_count"]??10) ?>);
			});
	
			function getItemPools(time, count) {
	
				var data = {
						'action': 'getItemPools',
						'itempools_time' : time, 	
						'itempools_count' : count 	
					};
	
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#itempoolstable").html (response['table_html']);
				});
					
			}

			</script>
		<?php	
		}		
	}
	
	
	public static function getItemPools_callback () {
		
		$_SESSION["itempools_time"] = $_POST["itempools_time"];
		$_SESSION["itempools_count"] = $_POST["itempools_count"];
		
		wp_send_json (
			array (
				'table_html' => self::getHTML_TaskPools($_SESSION["itempools_time"], $_SESSION["itempools_count"]),
				'itempools_time' => $_SESSION["itempools_time"],
				'itempools_count' => $_SESSION["itempools_count"])
			);
	}

	
	private static function getHTML_BlueprintForm (array $items): string {
		
		$itemids = ItemExplorer::getItemIds($items); 
		
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
				    <input type="hidden" name="tpg_set_number" value="on">
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
			$html.= sprintf ('
				<div id="mb_learnout" class="postbox ">
					<h2 class="hndle" style="padding-left:1em; padding-top:0"><input type="checkbox" name="%s" %s onchange="this.parentNode.nextElementSibling.style.display = this.checked ? \'block\' : \'none\';"/>&nbsp;%s</h2>
					<div class="inside" style="display:%s"><table>%s</table></div>
				</div>', $sk, $_SESSION[$sk]=="on" ? "checked" : "", EAL_Item::$category_label[$category], $_SESSION[$sk]=="on" ? "block" : "none", $html_box);
		}
		
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
	
	
	
	private static function getHTML_TaskPools (int $time, int $count): string {
		
		$items = EAL_ItemBasket::getItems();
		$pools = (new TaskPoolGenerator())->getItemPoolsAtRandom($time, $count);
		
		// 
		$result  = sprintf ("<div style='padding-bottom:1em'> ");
		$result .= sprintf ("<a class='button' onclick='this.style.display=\"none\"; getItemPools(document.getElementById(\"itempools_time\").value, document.getElementById(\"itempools_count\").value)'>Load</a>");
		$result .= sprintf ("&nbsp;&nbsp;<input style='width:4em' type='number' id='itempools_count' min='1' max='50' value='%d'/>", $count);
		$result .= sprintf ("&nbsp;&nbsp;item pools at random in maximal");
		$result .= sprintf ("&nbsp;&nbsp;<input style='width:4em' type='number' id='itempools_time' min='1' max='3000' value='%d'/>&nbsp;&nbsp;seconds", $time);
		$result .= sprintf ("</div>");
		
		$result .= sprintf ("<table cellpadding='10px' class='widefat fixed' style='table-layout:fixed; width:%dem; background-color:rgba(0, 0, 0, 0);'>", 6+2*count($items));
		$result .= "<col width='10em;' />";
		$result .= str_repeat("<col width='2em;' />", count($items));
		
		$pool_label = 0;
		
		foreach ($pools as $pool) {
			$pool_label += 1;
			$result .= sprintf ("<tr valign='middle'>");
			$href = "admin.php?page=view_item&itemids=" . join (",", $pool);
			$result .= sprintf ("<td style='overflow: hidden; padding:0px; padding-bottom:0.5em; padding-top:0.5em; padding-left:1em' ><a href='%s' class='button'>&nbsp;View #%02d &nbsp;&nbsp;</a></td>", $href, $pool_label);
			
			foreach ($items as $item) {
				
				$symbol = "";
				$link = "";
				if (in_array($item->getId(), $pool)) {
					$link = sprintf ("onClick='document.location.href=\"admin.php?page=view_item&itemid=%s\";'", $item->getId());
					if ($item->getType() == "itemsc") $symbol = "<span class='dashicons dashicons-marker'></span>";
					if ($item->getType() == "itemmc") $symbol = "<span class='dashicons dashicons-forms'></span>";
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
		$_SESSION[$sk[0]] = $_REQUEST[$sk[0]] ?? $_SESSION[$sk[0]] ?? 0;
		$_SESSION[$sk[0]] = max (min ($_SESSION[$sk[0]], $max), 0);
		$_SESSION[$sk[1]] = $_REQUEST[$sk[1]] ?? $_SESSION[$sk[1]] ?? $max;
		$_SESSION[$sk[1]] = max (min ($_SESSION[$sk[1]], $max), 0);
		
		
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