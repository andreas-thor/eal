<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");

class Explorer {

	/**
	 * Entry functions from menu
	 */
	
	public static function page_explorer ($itemids = array()) {
	
		?>
		<script type="text/javascript" >

		jQuery(document).ready(function($) {
			getCrossTable();
		});

		function getCrossTable() {

			var drag_x = []; jQuery("#drag_x").children("input[id!='place_here']").attr("id", function (idx, val) { drag_x[idx] = val; });
			var drag_y = []; jQuery("#drag_y").children("input[id!='place_here']").attr("id", function (idx, val) { drag_y[idx] = val; });
			
			var data = {
					'action': 'getCrossTable',
					'drag_x' : drag_x, 	
					'drag_y' : drag_y 	
				};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post(ajaxurl, data, function(response) {
				jQuery("#itemtable").html (response['table_html']);
			});
				
		}
		
		/* Drag and drop */
		
		function allowDrop(ev) {
			ev.preventDefault();
		}
		
		function drag(ev) {
			ev.dataTransfer.setData("text", ev.target.id);
		}
		
		function drop(ev) {
			ev.preventDefault();
			var data = ev.dataTransfer.getData("text");
			

			if (ev.target.id == "place_here") {	// drag on place holder --> add before place holder 
			    ev.target.parentNode.insertBefore (document.getElementById(data), ev.target);
			    getCrossTable();
			    return;
			}

			if (ev.target.id == "drag_all") {	// drag on "home div" --> add as child
				ev.target.appendChild (document.getElementById(data));
				getCrossTable();
				return;
			}
		}
		</script>
		<?php 

		
		/* generate HTML code for draggable buttons from SESSION property */
		$btn_html = ['x'=>'', 'y'=>'', 'all'=>''];
		if (is_array($_SESSION["drag_x"])) {
			foreach ($_SESSION["drag_x"] as $id) {
				$btn_html['x'] .= sprintf ('<input class="button" readonly value="%2$s" style="margin:0.2em;" id="%1$s" draggable="true" ondragstart="drag(event)" />', $id, EAL_Item::$category_label[$id]);
			}
		}
		if (is_array($_SESSION["drag_y"])) {
			foreach ($_SESSION["drag_y"] as $id) {
				$btn_html['y'] .= sprintf ('<input class="button" readonly value="%2$s" style="margin:0.2em;" id="%1$s" draggable="true" ondragstart="drag(event)" />', $id, EAL_Item::$category_label[$id]);
			}
		}
		foreach (['type', 'dim', 'level', 'topic1', 'topic2', 'topic3', 'lo'] as $id) {
			if ((is_array($_SESSION["drag_x"])) && (in_array($id, $_SESSION["drag_x"]))) continue;
			if ((is_array($_SESSION["drag_y"])) && (in_array($id, $_SESSION["drag_y"]))) continue;
			$btn_html['all'] .= sprintf ('<input class="button" readonly value="%2$s" style="margin:0.2em;" id="%1$s" draggable="true" ondragstart="drag(event)" />', $id, EAL_Item::$category_label[$id]);
		}
			
		$_SESSION["explore_items"] = array();
		if (isset($_REQUEST["itemids"])) {
			$_SESSION["explore_items"] = explode (",", $_REQUEST["itemids"]);
		}
		
		
		printf ('
			<h1>Item Explorer</h1>
			<div>
				<div id="drag_all" style="margin-right:1em; min-height:2em; border-style: dashed; border-width:1px; border-color:#AAAAAA; padding:0.5em;" ondrop="drop(event)" ondragover="allowDrop(event)">%3$s</div>
			</div>
			<table>
				<tr>
					<td></td>
					<td>
						<div id="drag_x">%1$s
							<input readonly class="button" value="[Place here]" style="font-style:italic; font-weight:lighter; margin:0.2em;" id="place_here" ondrop="drop(event)" ondragover="allowDrop(event)" />
						</div>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<div id="drag_y">%2$s
							<input readonly class="button" value="[Place here]" style="font-style:italic; font-weight:lighter; margin:0.2em;" id="place_here" ondrop="drop(event)" ondragover="allowDrop(event)" />
						</div>
					</td>
					<td>
						<div style="margin-right:1em">
							<table id="itemtable" border="1" class="widefat fixed "></table>
						</div>
					</td>
				</tr>
			</table>
		', $btn_html['x'], $btn_html['y'], $btn_html['all']);		
		
	
		
	}
	
	
	public static function getCrossTable_callback () {
		
		$_SESSION["drag_x"] = $_POST["drag_x"];
		$_SESSION["drag_y"] = $_POST["drag_y"];
		
		wp_send_json (
			array ('table_html' => HTML_ItemBasket::getHTML_CrossTable($_SESSION["explore_items"], is_array($_POST["drag_x"]) ? $_POST["drag_x"] : [], is_array($_POST["drag_y"]) ? $_POST["drag_y"] : []))
		);
	}
	
}


?>