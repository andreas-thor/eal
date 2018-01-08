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
			$tpg->generatePools($dimensions);
		}
		
		
		
		$items = EAL_ItemBasket::getItems();
		
		// show form
		print ("<div class='wrap'>");
		print ("<h1>Task Pool Generator</h1><br/>");
		print (self::getHTML_BlueprintForm ($items));
		
		// show (previosuly) generated task pools (if available)
		if (isset ($_SESSION['tpg_numberOfItemPools'])) {
			printf ("<br/><h2>%s generated Task Pools</h2>", gmp_strval($_SESSION["tpg_numberOfItemPools"]));
			print ('<div id="itempoolstable"></div>');
			
			?>
			<script type="text/javascript" >
	
			jQuery(document).ready(function($) {
				getItemPools(0, 100);
			});
	
			function getItemPools(page, count) {
	
				var data = {
						'action': 'getItemPools',
						'itempools_page' : page, 	
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
		
		$_SESSION["itempools_page"] = $_POST["itempools_page"];
		$_SESSION["itempools_count"] = $_POST["itempools_count"];
		
		wp_send_json (
			array (
				'table_html' => self::getHTML_TaskPools($_SESSION["itempools_page"], $_SESSION["itempools_count"]),
				'itempools_page' => $_SESSION["itempools_page"],
				'itempools_count' => $_SESSION["itempools_count"])
			);
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
	
	
	
	private static function getHTML_TaskPools ($page, int $count): string {
		
		$items = EAL_ItemBasket::getItems();
		
		$g_count = gmp_init ($count);
		$g_start = gmp_mul (gmp_init($page), $g_count);
		$pools = (new TaskPoolGenerator())->getItemPools($g_start, $g_count);
		
		
		// all pages: 0 ... $page_count-1; page range = to be displayed around current page
		$page_count = gmp_div (gmp_sub (gmp_add ($g_count, $_SESSION["tpg_numberOfItemPools"]), gmp_init(1)), $g_count);
		$page_range = [ 
			gmp_cmp($page, gmp_init(3)) > 0 ? gmp_sub($page, gmp_init(2)) : gmp_init(0), 
			gmp_cmp(gmp_add(gmp_init(5), $page), $page_count) > 0 ? gmp_sub ($page_count, gmp_init(1)) : gmp_add(gmp_init(2), $page)];
			
			
		$result = sprintf ("<div style='padding-bottom:1em'> ");
		if (gmp_cmp ($page_range[0], gmp_init(0)) > 0) {
			$result .= sprintf ("&nbsp;&nbsp;<a class='button' %s onclick='getItemPools(%d, %d)'>&nbsp; %d &nbsp; </a>", ($page==0)?"disabled":"", 0, $count, 1);
			$result .= sprintf ("&nbsp;&nbsp;...");
		}
		
		$p = $page_range[0];
		while (gmp_cmp($page_range[1], $p) >= 0) {
			$result .= sprintf ("&nbsp;&nbsp;<a class='button' %s onclick='getItemPools(%s, %d)'>&nbsp; %s &nbsp; </a>", (gmp_cmp($p, gmp_init($page))==0)?"disabled":"", gmp_strval($p), $count, gmp_strval(gmp_add ($p, gmp_init(1))));
			$p = gmp_add ($p, gmp_init(1));
		}
		
		if (gmp_cmp ($page_range[1], gmp_sub ($page_count, gmp_init(1))) < 0) {
			$result .= sprintf ("&nbsp;&nbsp;...");
			$result .= sprintf ("&nbsp;&nbsp;<a class='button' %s onclick='getItemPools(%s, %d)'>&nbsp; %s &nbsp; </a>", (gmp_cmp(gmp_sub ($page_count, gmp_init(1)), gmp_init($page))==0)?"disabled":"", gmp_strval(gmp_sub ($page_count, gmp_init(1))), $count, gmp_strval($page_count));
		}
		
		
		$result .= sprintf ("</div>");
		
		$result .= sprintf ("<table cellpadding='10px' class='widefat fixed' style='table-layout:fixed; width:%dem; background-color:rgba(0, 0, 0, 0);'>", 6+2*count($items));
		$result .= "<col width='10em;' />";
		$result .= str_repeat("<col width='2em;' />", count($items));
		
		$pool_label = $g_start;
		
		foreach ($pools as $pool) {
			$pool_label = gmp_add ($pool_label, gmp_init(1));
			$result .= sprintf ("<tr valign='middle'>");
			$href = "admin.php?page=view_item&itemids=" . join (",", $pool);
			$result .= sprintf ("<td style='overflow: hidden; padding:0px; padding-bottom:0.5em; padding-top:0.5em; padding-left:1em' ><a href='%s' class='button'>&nbsp;View #%s &nbsp;&nbsp;</a></td>", $href, gmp_strval($pool_label));
			
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