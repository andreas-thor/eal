<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../html/HTML_Item.php");
require_once(__DIR__ . "/../html/HTML_ItemBasket.php");
require_once(__DIR__ . "/../html/HTML_Review.php");

require_once(__DIR__ . "/../imex/IMEX_LearnOut.php");


class PAG_Learnout_Bulkviewer {

	

	
	/**
	 * Entry functions from menu
	 */
	
	public static function page_view_learnout () {

		$learnoutids = array();
		if ($_REQUEST['learnoutid'] != null) $learnoutids = [$_REQUEST['learnoutid']];
		if ($_REQUEST['learnoutids'] != null) {
			if (is_array($_REQUEST['learnoutids'])) $learnoutids = $_REQUEST['learnoutids'];
			if (is_string($_REQUEST['learnoutids'])) $learnoutids = explode (",", $_REQUEST["learnoutids"]);
		}
		
		
		if ($_REQUEST['action']=='update') {
			$learnoutids = IMEX_LearnOut::updateLearnouts ($learnoutids);
		}

		
		// load all learning outcomes
		$learnouts = [];
		foreach ($learnoutids as $learnout_id) {
			if (array_key_exists($learnout_id, $learnouts)) continue;	// item already loaded
			$learnouts[$learnout_id] = EAL_Factory::createNewLearnOut($learnout_id);
		}
		
		$editable = $_REQUEST['action'] === 'edit';
		
		self::printLearnoutList($learnouts, $editable, FALSE);
	}
	
	
	
	
	public static function printLearnoutList (array $learnouts, bool $editable, bool $isImport) {
		
		$listOfLearnoutIds = implode(',', array_keys ($learnouts));
		
		// Add list of items to <select>-List in screen settings
?>
		<script>
			jQuery(document).ready(function () {
				jQuery("#screen_settings_item_select_list").append("<?php 
					$pos = 0;
					foreach ($learnouts as $lo) { printf ('<option value=\"%d\">%s</option>', $pos++, htmlentities ($lo->getTitle(), ENT_COMPAT | ENT_HTML401, 'UTF-8')); } 
				?>");
			});
			// ");
		</script>
		
		
		<div class="wrap">
			<form  enctype="multipart/form-data" action="admin.php?page=view_learnout" method="post">
				

				<h1>Mein neuer Learnout Viewer 
				
				<?php if ($editable) { ?>
					<input type="submit" name="publish" id="publish" class="button button-primary button-large" value="<?php echo ($isImport ? 'Import ' : 'Update All '); echo count($learnouts); ?> Learning Outcomes">
					<input type="hidden" id="learnoutids" name="learnoutids" value="<?php echo $listOfLearnoutIds ?>">
					<input type="hidden" name="action" value="<?php echo ($isImport ? 'import' : 'update') ?>">
				<?php } else { ?>
					<a href="admin.php?page=view_learnout&learnoutids=<?php echo $listOfLearnoutIds ?>&action=edit" class="page-title-action">Edit All <?php echo count($learnouts) ?> Learning Outcomes</a>
				<?php } ?>
				
				
				</h1>
				<hr class="wp-header-end">
				<div id="itemcontainer">
					<?php foreach ($learnouts as $lo) { self::printLearnout($lo, $editable, $isImport); } ?>
				</div>
			</form>
		</div>
			
<?php 		
	}
	
	
	private static function printLearnout (EAL_LearnOut $lo, bool $isEditable, bool $isImport) {
		
		$htmlPrinter = $lo->getHTMLPrinter();
		
		$prefix = "lo_{$lo->getId()}_";

?>		
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<input type="text" size="30" value="<?php echo $lo->getTitle() ?>" id="title" readonly>
						</div>
					</div><!-- /titlediv -->
					
					<?php if ($isEditable) { ?>
						<input type="hidden" name="<?php echo $prefix ?>post_ID"      value="<?php echo $lo->getId() ?>">
				  		<input type="hidden" name="<?php echo $prefix ?>post_type"    value="<?php echo $lo->getType() ?>">
		  				<input type="hidden" name="<?php echo $prefix ?>post_content" value="<?php echo microtime() ?>">
		  				<input type="hidden" name="<?php echo $prefix ?>post_title"   value="<?php echo htmlentities ($lo->getTitle(), ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>">
					<?php } ?>
					
				</div><!-- /post-body-content -->
				<div id="postbox-container-1" class="postbox-container">
					
					<div id="mb_status" class="postbox ">
						<h2 class="hndle">
							<span>Learning Outcome (<?php  echo ($lo->getId()>0 ? 'ID:'.$lo->getId() : 'new') ?>)</span>
							<?php 
							if (($lo->getId() > 0) && (!$isImport)) {
									printf ('<span style="float: right; font-weight:normal" ><a style="vertical-align:middle" class="page-title-action" href="post.php?action=edit&post=%d">Edit</a></span>', $lo->getId());
								}
							?> 
						</h2>
					</div>
										
					<div id="mb_level" class="postbox ">
						<h2 class="hndle"><span>Anforderungsstufe</span></h2>
						<div class="inside"><?php $htmlPrinter->printLevel($isEditable, $prefix) ?></div>
					</div>
					
					<div class="postbox ">
						<h2 class="hndle"><span><?php echo RoleTaxonomy::getDomains()[$lo->getDomain()] ?></span></h2>
						<div class="inside"><?php echo $htmlPrinter->printTopic($isEditable, $prefix) ?></div>
					</div>
					
				</div>
	
				<div id="postbox-container-2" class="postbox-container">

					<div class="postbox" style="background-color:transparent; border:none">
						<div class="inside">
							<?php $htmlPrinter->printDescription($isImport, $prefix) ?>
						</div>
					</div>
				</div>
			</div><!-- /post-body -->
			<br class="clear">
		</div>
		
		
<?php 		
	}
	
	
	
}

?>