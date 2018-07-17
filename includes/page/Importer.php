<?php

require_once __DIR__ . '/../imp/IMP_Item_Ilias.php';
require_once __DIR__ . '/../imp/IMP_Item_Moodle.php';
require_once __DIR__ . '/../imp/IMP_Item_JSON.php';
require_once __DIR__ . '/../imp/IMP_Item_ONYX.php';
require_once __DIR__ . '/../imp/IMP_Term_JSON.php';
require_once __DIR__ . '/../imp/IMP_Term_TXT.php';
require_once __DIR__ . '/../imp/IMP_TestResult_CSV.php';

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
				
				$formatImporter = NULL;
				switch ($_REQUEST['format']) {
					case 'txt': $formatImporter = new IMP_Term_TXT(); break;
					case 'json': $formatImporter = new IMP_Term_JSON(); break;
				}
				
				if ($formatImporter === NULL) {
					self::showError(sprintf ('Unknown import format: %s', $_REQUEST['format']));
					return;
				}
				
				$formatImporter->importTerms($file, $_REQUEST['taxonomy'], $_REQUEST['termid']);
			}

			
			if (($_REQUEST['post_type']=='testresult') && ($_REQUEST['format']!='ilias')) {
				
				$formatImporter = NULL;
				switch ($_REQUEST['format']) {
					case 'csv': $formatImporter = new IMP_TestResult_CSV(); break;
				}
				
				$testdata = $formatImporter->getTestDataFromFile($file);
				$formatImporter->importTestResult($testdata, []);
				
			}
				
			if (($_REQUEST['post_type']=='item') || (($_REQUEST['post_type']=='testresult') && ($_REQUEST['format']=='ilias'))) {
				
				$formatImporter = NULL;
				switch ($_REQUEST['format']) {
					case 'ilias': $formatImporter = new IMP_Item_Ilias(); break;
					case 'moodle': $formatImporter = new IMP_Item_Moodle(); break;
					case 'json': $formatImporter = new IMP_Item_JSON(); break;
					case 'onyx': $formatImporter = new IMP_Item_ONYX(); break;
				}
				
				if ($formatImporter === NULL) {
					self::showError(sprintf ('Unknown import format: %s', $_REQUEST['format']));
					return;
				}
				
				$items = $formatImporter->parseItemsFromImportFile($file);
				$testData = '';
				if ($_REQUEST['post_type']=='testresult') {
					$testData = $formatImporter->getTestData();
				}
				
				if (is_string($items)) {
					self::showError(sprintf ('Import Error: %s', $items));
					return;
				}
				
				if (count($items)==0) {
					self::showError('No items found!');
					return;
				}
				
				
				PAG_Item_Bulkviewer::printItemList($items, [], $testData, TRUE, TRUE);
			}
			
			
		}

	}
	
	
	private static function showError (string $msg) {
		printf ('<div class="wrap"><h1>%s</h1></div>', $msg);
	}
	

	
	
	
	private static function showUploadForm () {
		
		$action = sprintf ('admin.php?page=%s&post_type=%s&format=%s', $_REQUEST['page'], $_REQUEST['post_type'], $_REQUEST['format']);
		$title = "";
		
		if ($_REQUEST['post_type']=='item') { 
			switch ($_REQUEST['format']) {
				case 'ilias': 	$title = "Items (from Ilias; zip file)"; break;
				case 'moodle': 	$title = "Items (from Moodle; xml file)"; break;
				case 'onyx': 	$title = "Items (from ONYX; zip file)"; break;
				case 'json': 	$title = "Items (from EAsLiT; json file)"; break;
			}
		}
			
		if ($_REQUEST['post_type']=='term') {
			switch ($_REQUEST['format']) {
				case 'txt': 
					$title = "Taxonomy Terms (from txt file)";
					$action .= sprintf('&taxonomy=%s&termid=%d', $_REQUEST['taxonomy'], $_REQUEST['termid']);
					break;
				case 'json': 
					$title = "Taxonomy Terms (from EAsLiT; json file)";
					$action .= sprintf('&taxonomy=%s&termid=%d', $_REQUEST['taxonomy'], $_REQUEST['termid']);
					break;
			}
		}
		
		if ($_REQUEST['post_type']=='testresult') {
			switch ($_REQUEST['format']) {
				case 'ilias': 	$title = "Test Results (from Ilias; zip file)"; break;
			}
		}
			
		
		
		?>
		
		<div class="wrap">
			<h1>Upload <?=$title?></h1>
			<form  enctype="multipart/form-data" action="<?=$action?>" method="post">
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