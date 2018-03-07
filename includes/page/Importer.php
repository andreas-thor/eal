<?php

require_once (__DIR__ . "/../imex/IMEX_Ilias.php");

class Importer {

	
	
	public static function createPage () {
		
		if ((!isset($_REQUEST['action'])) || ($_REQUEST['action']=='')) {
 			self::showUploadForm();
		}
		
		if ($_REQUEST['action']=='Upload') {
			
			$file = $_FILES['uploadedfile'];
			
			//	checks for errors and that file is uploaded
			if (!(($file['error'] == UPLOAD_ERR_OK) && (is_uploaded_file($file['tmp_name'])))) {
				self::showError(sprintf ('File Error: %s', $file['error']));
				return;
			}
			
			
			if ($_REQUEST['post_type']=='term') {
				(new IMEX_Term())->uploadTerms($file, $_REQUEST['taxonomy'], $_REQUEST['termid']);
				return;
			}

			if ($_REQUEST['post_type']=='item') {
				
				$formatImporter = NULL;
				switch ($_REQUEST['format']) {
					case 'ilias': $formatImporter = new IMEX_Ilias(); break;
					case 'moodle': $formatImporter = new IMEX_Moodle(); break;
				}
				
				if ($formatImporter === NULL) {
					self::showError(sprintf ('Unknown import format: %s', $_REQUEST['format']));
					return;
				}
				
				$items = $formatImporter->parseItemsFromImportFile($file);
				
				if (is_string($items)) {
					self::showError(sprintf ('Import Error: %s', $items));
					return;
				}
				
				if (count($items)==0) {
					self::showError('No items found!');
					return;
				}
				
				self::showPreview($items);
				
			}
			
			
		}

	}
	
	
	private static function showError (string $msg) {
		printf ('<div class="wrap"><h1>%s</h1></div>', $msg);
	}
	
	/**
	 * 
	 * @param array $itemids
	 * @param bool $updateMetadataOnly
	 */
	public static function doImport (array $itemids, bool $updateMetadataOnly = FALSE ) {

		global $item;
		
		$result = array();
		foreach ($itemids as $itemid) {
		
			$prefix = "item_" . $itemid . "_";
		
			$status = '';
			switch (abs ($_POST[$prefix."item_status"])) {
				case  1: $status = 'publish'; break;
				case  2: $status = 'pending'; break;
				case  3: $status = 'draft'; break;
			}

			if ($status == '') continue;	// must have status
			if ($updateMetadataOnly && ($itemid<0)) continue;	// must have itemid if "update"
		
			
			
			$item = EAL_Item::load($_POST[$prefix."post_type"], -1, $prefix);	// load item from POST data (because tempid<0)
			if ($updateMetadataOnly) {
				$item_post = $item;
				$item = EAL_Item::load($_POST[$prefix."post_type"], $itemid);
				$item->level = $item_post->level;
				$item->learnout_id = $item_post->learnout_id;
				$item->note = $item_post->note;
				$item->flag = $item_post->flag;
			}
			/**
			 *  In the mean time, a workaround worth trying would be:

    use wp_insert_post to create an initial post and get the the post ID
    use wp_update_post to insert your post data 
			 */
			
			$terms = $_POST[$prefix."taxonomy"];
		
			// store initial post & item
			if (($itemid<0) || ($_POST[$prefix."item_status"]<0)) {
				
				// import post/item
				$postarr = array ();
				$postarr['ID'] = 0;	// no EAL-ID
				$postarr['post_title'] = $item->title;
				$postarr['post_status'] = $status;
				$postarr['post_type'] = $item->getType();
				$postarr['post_content'] = microtime();
				$postarr['tax_input'] = array ($item->getDomain() => $terms);
				$itemid = wp_insert_post ($postarr);
			} 
			
			// update post (also necessary for initial import to have first revision version)
			$post = get_post ($itemid);
			$post->post_title = $item->title;
			$post->post_status = $status;
			$post->post_content = microtime();	// ensures revision
			wp_set_post_terms($itemid, $terms, $item->getDomain(), FALSE );
			wp_update_post ($post);

			
			array_push ($result, $itemid);
		}
		return $result;
	}


	public static function doUpdateLearnOuts () {
	
		$result = array();
		foreach (explode (",", $_POST['learnoutids']) as $learnoutid) {
		
			$prefix = "lo_" . $learnoutid . "_";
			$learnout = new EAL_LearnOut (-1, $prefix);	// learnoutid = -1 --> LOAD from post request
			$terms = $_POST[$prefix."taxonomy"];
		
	
			$learnout->setId($learnoutid);
			$post = get_post ($learnout->getId());
			$post->post_title = $learnout->title;
			$post->post_status = "publish";
			$post->post_content = microtime();	// ensures revision
			wp_set_post_terms($learnout->getId(), $terms, $learnout->getDomain(), FALSE );
			wp_update_post ($post);
		
			$learnout->saveToDB();
			array_push ($result, $learnout->getId());
		}
		return $result;		
		
	}
	
	
	private static function showPreview (array $items) {
	

		// Generate HTML content
		$itemids = array ();
		$items_title = array();
		$items_content = array();
		
		foreach ($items as $item) {
			
			if ($item->getId() == NULL) {
				$qw = 4;
			}
			
			array_push ($itemids, $item->getId());
			array_push ($items_title, $item->getId() . ". " . $item->title);
			array_push ($items_content, sprintf('
				<div id="poststuff">
					<hr/>
					%s
					<br style="clear:both;"/>
				</div>',
				BulkViewer::getHTML_Body($item->title, HTML_Item::getHTML_Item($item, HTML_Object::VIEW_IMPORT, "item_{$item->getId()}_"), HTML_Item::getHTML_Metadata($item, HTML_Object::VIEW_IMPORT, "item_{$item->getId()}_"))
			));
		}

		$impTable = sprintf ('
			<div class="postbox" style="width:1em">
				<h2 class="hndle"><span>Import Options</span></h2>
				<div class="inside">
					<table style="border-width:1px; font-size:100%%">
						<tr><th></th><th>New</th><th>Update</th></tr>
						<tr><td>Published</td><td align="right" id="importstatussum_-1"></td><td align="right" id="importstatussum_1"></td></tr>
						<tr><td>Pending Review</td><td align="right" id="importstatussum_-2"></td><td align="right" id="importstatussum_2"></td></tr>
						<tr><td>Draft</td><td align="right" id="importstatussum_-3"></td><td align="right" id="importstatussum_3"></td></tr>
						<tr><td>Do not Import</td><td align="right" id="importstatussum_0"></td><td></td></tr>
					</table>
				</div>
			</div>
		');
		
		printf (' 
			<form  enctype="multipart/form-data" action="admin.php?page=view_item" method="post">
				<input type="hidden" id="itemids" name="itemids" value="%s">
				<input type="hidden" name="action" value="import">
				%s
			</form>',
			join(",", $itemids), BulkViewer::getHTML_List('Item Import Viewer', $impTable, $items_title, $items_content));
		
		?>
		<script type="text/javascript">

		function updateimportstatus () {
			var j = jQuery.noConflict();

			var n = j(".importstatus option:selected[value!='0']").length;
			j("input#bulk_view_action_button").val("Import " + n + " Items");
			j("input#bulk_view_action_button").prop("disabled", n==0);
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
		
		$action = sprintf ('admin.php?page=%s&post_type=%s&format=%s', $_REQUEST['page'], $_REQUEST['post_type'], $_REQUEST['format']);
		$title = "";
		if (($_REQUEST['post_type']=='item') && ($_REQUEST['format']=='ilias')) {
			$title = "Items (from Ilias)";
		}
		if (($_REQUEST['post_type']=='item') && ($_REQUEST['format']=='moodle')) {
			$title = "Items (from Moodle)";
		}
		if (($_REQUEST['post_type']=='term') && ($_REQUEST['format']=='txt')) {
			$title = "Taxonomy Terms (from TXT file)";
			$action .= sprintf('&taxonomy=%s&termid=%d', $_REQUEST['taxonomy'], $_REQUEST['termid']);
		}
		
		
		?>
		
		
		<div class="wrap">
			<h1>Upload <?php print ($title); ?></h1>
			<form  enctype="multipart/form-data" action="<?php print ($action); ?>" method="post">
				<div>
					<label class="screen-reader-text" for="async-upload">Upload</label>
					<input name="uploadedfile" id="async-upload" type="file">
					<input name="action" class="button button-primary" value="Upload" type="submit">
				</div>
			</form>
		</div>	
		<?php 		
		
	}
	
}

?>