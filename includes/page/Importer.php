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
				
				PAG_Item_Bulkviewer::printItemList($items, [], TRUE, TRUE);
			}
			
			
		}

	}
	
	
	private static function showError (string $msg) {
		printf ('<div class="wrap"><h1>%s</h1></div>', $msg);
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