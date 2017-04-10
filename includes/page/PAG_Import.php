<?php

require_once (__DIR__ . "/../imex/Ilias.php");

class PAG_Import {

	
	
	public static function createPage () {
		
		if ((!isset($_POST['action'])) || ($_POST['action']=='')) {
 			self::showUploadForm();
		}
		
		if ($_POST['action']=='upload') {
			self::showPreview();
		}

		if ($_POST['action']=='import') {
			self::doImport();
		}		
	}
	
	
	private static function doImport () {
		
		$res = "";
		$count = 0;
		foreach (explode (",", $_POST['itemids']) as $itemid) {
		
			$count++;
			$tempid = ($itemid>0) ? -$itemid : $itemid;
			$prefix = "import_" . $itemid . "_";
		
			$status = '';
			switch (abs ($_POST[$prefix."item_status"])) {
				case  1: $status = 'publish'; break;
				case  2: $status = 'pending'; break;
				case  3: $status = 'draft'; break;
			}
			if ($status == '') {
				$res .= sprintf ("<tr><td align='right'>%d.</td><td>Item ignored</td><td><b>%s</b></td></tr>", $count, $_POST[$prefix."post_title"]);
				continue;
			}
		
			$item = EAL_Item::load($_POST[$prefix."post_type"], $tempid, $prefix);
			$terms = $_POST[$prefix."taxonomy"];
		
			if ($itemid<0) {
				$postarr = array ();
				$postarr['ID'] = 0;	// no EAL-ID
				$postarr['post_title'] = $item->title;
				$postarr['post_status'] = $status;
				$postarr['post_type'] = $item->type;
				$postarr['post_content'] = microtime();
				$postarr['tax_input'] = array ($item->domain => $terms);
				$item->id = wp_insert_post ($postarr);	// returns the item_id of the created post / item
			} else {
				$item->id = $itemid;
				$post = get_post ($item->id);
				$post->post_title = $item->title;
				$post->status = $status;
				$post->post_content = microtime();	// ensures revision
				wp_set_post_terms($item->id, $terms, $item->domain, FALSE );
				wp_update_post ($post);
			}
		
			$item->saveToDB();
			
			$res .= sprintf ("<tr><td align='right'>%d.</td><td>Item %s</td><td><b>%s</b> (Id=%s)</td></tr>", $count, ($itemid<0) ? "created" : "updated", $item->title, $item->id);
		}
		
		printf ("<div class='wrap'><h1>Import Summary</h1><table>%s</table></div>", $res);
	}

	
	private static function showPreview () {
	
		//	checks for errors and that file is uploaded
		if (!(($_FILES['uploadedfile']['error'] == UPLOAD_ERR_OK) && (is_uploaded_file($_FILES['uploadedfile']['tmp_name'])))) {
			printf ("<div class='wrap'><h1>File Error: %s</h1></div>", $_FILES['uploadedfile']['error']);
			return;
		}
		
		// TODO: check file format parameter (ILIAS5, ...)
		$items = Ilias::import($_FILES['uploadedfile']);
		if (is_string($items)) {
			printf ("<div class='wrap'><h1>Import Error: %s</h1></div>", $items);
			return;
		}
			
		$html_items = "";
		$html_select = "";
		$count = 0;
		$itemids = array ();
		foreach ($items as $item) {
			array_push($itemids, $item->id);
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
				, HTML_Item::getHTML_Item($item, HTML_Object::VIEW_IMPORT, "import_{$item->id}_")
				, HTML_Item::getHTML_Metadata($item, HTML_Object::VIEW_IMPORT, "import_{$item->id}_")
				);
		
			$count++;
		}


		printf ("
			<div class='wrap'>
				<h1>Item Import Viewer</h1>
				<form>
					 <select onChange='for (x=0; x<this.form.nextElementSibling.lastElementChild.children.length; x++) {  this.form.nextElementSibling.lastElementChild.children[x].style.display = ((this.value<0) || (this.value==x)) ? \"block\" :  \"none\"; }'>
						<option value='-1' selected>[All %d Items]</option>
						%s
					</select>
					<input type='checkbox' checked onChange='for (x=0; x<this.form.nextElementSibling.lastElementChild.children.length; x++) { this.form.nextElementSibling.lastElementChild.children[x].querySelector(\"#postbox-container-1\").style.display = (this.checked==true) ? \"block\" :  \"none\"; }'/> Show Metadata
				</form>
				<form  enctype='multipart/form-data' action='admin.php?page=%s' method='post'>
					<table style='border-width:1px; font-size:100%%'>
						<tr><th><button type='submit' name = 'action' value = 'import' id='importstatussum_all'></button></th><th>New</th><th>Update</th></tr>
						<tr><td>Published</td><td align='right' id='importstatussum_-1'></td><td align='right' id='importstatussum_1'></td></tr>
						<tr><td>Pending Review</td><td align='right' id='importstatussum_-2'></td><td align='right' id='importstatussum_2'></td></tr>
						<tr><td>Draft</td><td align='right' id='importstatussum_-3'></td><td align='right' id='importstatussum_3'></td></tr>
						<tr><td>Do not Import</td><td align='right' id='importstatussum_0'></td><td></td></tr>
					</table>
					<input type='hidden' id='itemids' name='itemids'  value='%s'>
					<div>%s</div>
				</form>
			</div>",
			count($items), $html_select, $_REQUEST["page"], join(",", $itemids), $html_items 
		);
		
		
		
		?>
				<script type="text/javascript">
		
				function updateimportstatus () {
					var j = jQuery.noConflict();
					
					j("button#importstatussum_all").text("Import " + j(".importstatus option:selected[value!='0']").length + " Items");
					for (i=-3; i<=3; i++) {
						j("td#importstatussum_"+i).html(j(".importstatus option:selected[value='"+i+"']").length + " (<a onclick='setimportstatus(" + i + ");'>All)");
					}
				} 
				
				function setimportstatus (status) {
					var j = jQuery.noConflict();
					// status > 0 requires existing item 
					if (status > 0) {
						j(".importstatus").val(-status);	// set all as NEW first
					}
					// .. and for those with existing items set as UPDATE
					j(".importstatus option[value='"+status+"']").parent().val(status);
					updateimportstatus();
				}
		
				
				jQuery(document).ready(function($) {
					$('.importstatus').change(function(){
						updateimportstatus();
					});
					updateimportstatus();
					
				});
				</script>
				<?php 		
		
	}
	
	
	private static function showUploadForm () {
		
		?>
		<div class="wrap">
			<h1>Upload Items & Test Results</h1>
			<form  enctype="multipart/form-data" action="admin.php?page=<?php print ($_REQUEST["page"]); ?>" method="post">
				<table class="form-table">
					<tbody>
						<tr>
							<th><label>File</label></th>
							<td><input class="menu-name regular-text menu-item-textbox input-with-default-title" name="uploadedfile" type="file" size="30" accept="text/*"></td>
						</tr>
						<tr>
							<th><label>Format</label></th>
							<td><select style='width:12em' name="format" values="ilias5"><option>ILIAS 5</option></select></td>
						</tr>
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