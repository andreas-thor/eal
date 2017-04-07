<?php

require_once (__DIR__ . "/../imex/Ilias.php");

class PAG_Import {

	
	
	public static function createPage () {
		
		if ((!isset($_POST['action'])) || ($_POST['action']=='')) {
 			self::HTML_uploadForm();
		}
		
		if ($_POST['action']=='upload') {
			//	checks for errors and that file is uploaded
			if (($_FILES['uploadedfile']['error'] == UPLOAD_ERR_OK) && (is_uploaded_file($_FILES['uploadedfile']['tmp_name']))) {
		
				
				
				// TODO: check file format parameter (ILIAS5, ...)
				$items = Ilias::import($_FILES['uploadedfile']);
				if (is_string($items)) {
					printf ("<div class='wrap'><h1>%s</h1></div>", $items);
				} else {
					self::HTML_itemlist($items); // , $ilias->dir, $ilias->name);
				}				
				
			} else {
				printf ("<div class='wrap'><h1>Error %s</h1></div>", $_FILES['uploadedfile']['error']);
			}
		}
		
		if ($_POST['action']=='import') {
			
			$ilias = new EXP_Ilias($_POST['file_dir'], $_POST['file_name']);
			$items = $ilias->loadAllItems();
			
			foreach ($_POST["import"] as $importIdent) {
				
				$item = $items[$importIdent];
				
				// update with metadata
				$item->learnout_id = $_POST[$importIdent . '_learnout_id'];
				$item->level["FW"] = $_POST[$importIdent . '__level_FW'];
				$item->level["KW"] = $_POST[$importIdent . '__level_KW'];
				$item->level["PW"] = $_POST[$importIdent . '__level_PW'];
				
				// save
				$ilias->saveItem($item, $_POST[$importIdent . '__taxonomy']);
				printf ("<br/>Save item with id %s and ident %s", $item->id, $importIdent);
				
				
			}
			
		}
		
		
	}
	
	
	public static function HTML_itemlist(array $items /*, string $dir, string $name*/) {
		
?>
		<script type="text/javascript">

		jQuery(document).ready(function($) {
			$('.previewButton').click(function(){
				console.log ($(this).parent().parent().siblings("div").children(":last-child"));
				$(this).parent().parent().siblings("div").children(":last-child").css({display:"none"});
				$(this).parent().parent().children(":last-child").toggle();
			});


			$('.importAll').change(function(){
				$(this).siblings("div").find(".importCheckbox").prop("checked",this.checked);
				noofchecked = $(this).parent().parent().parent().find(".importCheckbox:checked").length;
				$("#importButton").text("Import " + noofchecked + " Item(s)");
			});

			
			$('.importCheckbox').change(function(){
				noofchecked = $(this).parent().parent().parent().find(".importCheckbox:checked").length;
				$(this).parent().parent().parent().find(".importAll").prop("checked", noofchecked == ($(this).parent().parent().parent().find(".importCheckbox").length));
				$("#importButton").text("Import " + noofchecked + " Item(s)");
			});
			
		});
		</script>
		<?php 		
		
		
		$html_items = "";
		$html_select = "";
		$count = 0;
		foreach ($items as $item) {
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
					, HTML_Item::getHTML_Item($item, HTML_Object::VIEW_IMPORT)
					, HTML_Item::getHTML_Metadata($item, HTML_Object::VIEW_IMPORT, "")
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
		
/*		
		printf ("<div class='wrap'><h1>Select Items & Test Results for Import</h1>");
		printf ("<form enctype='multipart/form-data' action='admin.php?page=import-items' method='post'>");
// 		printf ("<input type='hidden' name='file_dir' value='%s'>", $dir);
// 		printf ("<input type='hidden' name='file_name' value='%s'>", $name);
		printf ("<div style='margin-top:2em'>");
		printf ("<input class='importAll' type='checkbox' value='1' checked>&nbsp;");
		printf ("<button id='importButton' type='submit' name='action' value='import'>Import %s Items</button>", count($items));
		
		foreach ($items as $ident=>$item) {

// 			if ($item->type == "itemsc") $symbol = "<span class='dashicons dashicons-marker'></span>";
// 			if ($item->type == "itemmc") $symbol = "<span class='dashicons dashicons-forms'></span>";
			
			printf ("					
				<div style='margin-top:2em;'>
					<hr/>
					<div style='margin-top:0em;'>
						<input class='importCheckbox' type='checkbox' name='import[]' value='%s' checked>&nbsp;
						<input class='previewButton' type='button' value='Preview'></input>&nbsp;
						%s %s
						
					</div>
					<div style='margin-top:2em; display:none'>
						%s
						<br style='clear:both;'/>
					</div>
				</div>
				", $ident, $item->title, $symbol, HTML_Item::getHTML_Item($item, FALSE, TRUE, $ident . "_"));
		}

		printf ("</div></form></div>");
*/		
		
	}
	
	
	public static function HTML_uploadForm () {
		
?>
		<div class="wrap">
			<h1>Upload Items & Test Results</h1>
			<form  enctype="multipart/form-data" action="admin.php?page=<?php print ($_REQUEST["page"]); ?>" method="post">
				<table class="form-table">
					<tbody>
						<tr>
							<th><label>AFile</label></th>
							<td><input class="menu-name regular-text menu-item-textbox input-with-default-title" name="uploadedfile" type="file" size="30" accept="text/*"></td>
						</tr>
						<tr>
							<th><label>Format</label></th>
							<td><select style='width:12em' name="format" values="ilias5"><option>ILIAS 5</option></select></td>
						</tr>
						
						<!-- 
						<tr>
							<th><label>Items</label></th>
							<td>
								<fieldset> 
									<input  id="items_create_and_update" type="radio" 	name="newitem" value="create_and_update" checked> 
									<label for="items_create_and_update"> Create new items and update existing items</label><br>
									 
									<input  id="items_create" type="radio"				name="newitem" value="create"> 
									<label for="items_create"> Create new items only (existing items remain unchanged)</label><br>
									
									<input  id="items_update" type="radio"				name="newitem" value="update"> 
									<label for="items_update"> Update existing items only (no new items are created)</label>
								</fieldset>
							</td>
						</tr>
						-->
						<tr>
							<th>
								<input type="submit" name="action" class="button button-primary" value="upload">
							</th>
							<td></td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>	
						
			
<?php 		
		
	}
	
}

?>