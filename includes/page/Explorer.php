<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");

class Explorer {

	/**
	 * Entry functions from menu
	 */
	
	public static function page_explorer ($itemids = array()) {
	
		?>
		<script>
		function allowDrop(ev) {
			ev.preventDefault();
		}
		
		function drag(ev) {
			ev.dataTransfer.setData("text", ev.target.id);
		}
		
		function drop(ev) {
			var data = ev.dataTransfer.getData("text");
			ev.preventDefault();

			if (ev.target.id == "drag_place") {	// drag on place holder --> add before place holder 
			    ev.target.parentNode.insertBefore (document.getElementById(data), ev.target);
			    load_items();
			    return;
			}

			if (ev.target.id == "drag_all") {	// drag on "home dive" --> add as child
				ev.target.appendChild (document.getElementById(data));
				load_items();
				return;
			}
		}
		</script>
		<?php 

		
		$buttons = ['type' => 'Item Type', 'dim' => 'Wissensdimension', 'level' => 'Anforderungsstufe', 'topic1' => 'Topic 1'];

		$btn_html = ['x' => '', 'y' => '', 'all' => ''];
		foreach ($buttons as $id => $label) {
// 			$btn_html['x']   .= sprintf ('<input class="button" value="%2$s" style="margin:0.2em;" id="drag_%1$s" draggable="true" ondragstart="drag(event)" />', $id, $label);
// 			$btn_html['y']   .= sprintf ('<input class="button" value="%2$s" style="margin:0.2em;" id="drag_%1$s" draggable="true" ondragstart="drag(event)" />', $id, $label);
			$btn_html['all'] .= sprintf ('<input readonly  class="button" value="%2$s" style="margin:0.2em;" id="drag_%1$s" draggable="true" ondragstart="drag(event)" />', $id, $label);
		}
		
		
// 		<div id="drag_place" style="float:left; margin:0.2em; padding:0; border-style: dashed; border-color:#AAAAAA; border-width:1px; width:10em" ondrop="drop(event)" ondragover="allowDrop(event)">&nbsp;</div>
		printf ('
			<h1>Item Explorer</h1>
			<div>
				<div id="drag_all" style="margin-right:1em; min-height:2em; border-style: dashed; border-width:1px; border-color:#AAAAAA; padding:0.5em;" id="drag_home" ondrop="drop(event)" ondragover="allowDrop(event)">%3$s</div>
			</div>
			<table>
				<tr>
					<td></td>
					<td>
						<div id="drag_x">%1$s
							<input readonly class="button" value="[Place here]" style="font-style:italic; font-weight:lighter; margin:0.2em;" id="drag_place" ondrop="drop(event)" ondragover="allowDrop(event)" />
						</div>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<div id="drag_y">%2$s
							<input readonly class="button" value="[Place here]" style="font-style:italic; font-weight:lighter; margin:0.2em;" id="drag_place" ondrop="drop(event)" ondragover="allowDrop(event)" />
						</div>
					</td>
					<td>
						<table id="itemtable" border="1" class="wp-list-table widefat fixed striped posts"></table>
					</td>
				</tr>
			</table>
		', $btn_html['x'], $btn_html['y'], $btn_html['all']);		
		
		?>

		<?php 
		
	}
	
	
}


?>