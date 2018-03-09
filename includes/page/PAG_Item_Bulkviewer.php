<?php 

require_once(__DIR__ . "/../anal/ItemExplorer.php");
require_once(__DIR__ . "/../html/HTML_Item.php");
require_once(__DIR__ . "/../html/HTML_ItemBasket.php");
require_once(__DIR__ . "/../html/HTML_Review.php");

class PAG_Item_Bulkviewer {

	

	
	/**
	 * Entry functions from menu
	 */
	
	public static function page_view_item ($itemids = NULL) {

		if ($itemids == NULL) {
			$itemids = ItemExplorer::getItemIdsByRequest();
		}

		if ($_REQUEST['action']=='import') {
			$itemids = IMEX_Item::importItems($itemids, FALSE);
		}
			
		if ($_REQUEST['action']=='update') {
			$itemids = IMEX_Item::importItems($itemids, TRUE);
		}
		
		
		// load all items
		$items = [];
		foreach ($itemids as $item_id) {
			if (array_key_exists($item_id, $items)) continue;	// item already loaded
			$post = get_post($item_id);
			if ($post === NULL) continue;	// item (post) does not exist
			$items[$item_id] = EAL_Item::load($post->post_type, $item_id);
		}
		
		$editable = $_REQUEST['action'] === 'edit';
		
		self::printItemList($items, $editable, FALSE);
		
 		
 		
// 		self::viewItems($itemids, NULL, $_REQUEST['edit']=='1', $_REQUEST["page"]);
	}
	
	
	public static function page_view_basket () {
		self::printItemList(EAL_ItemBasket::getItems(), FALSE, FALSE);
	}
	
	
	public static function printItemList (array $items, bool $editable, bool $isImport) {
		
		$listOfItemIds = implode(',', array_keys ($items));
		
		// Add list of items to <select>-List in screen settings
?>
		<script>
			jQuery(document).ready(function () {
				jQuery("#screen_seetings_item_select_list").append("<?php 
					$pos = 0;
					foreach ($items as $item) { printf ('<option value=\"%d\">%s</option>', $pos++, $item->title); } 
				?>");
			});
			// ");
		</script>
		
		
		<div class="wrap">
			<form  enctype="multipart/form-data" action="admin.php?page=view_item" method="post">
				

				<h1>Mein neuer Item Viewer 
				
				<?php if ($editable) { ?>
					<input type="submit" name="publish" id="publish" class="button button-primary button-large" value="<?php echo ($isImport ? 'Import ' : 'Update All '); echo count($items); ?> Items">
					<input type="hidden" id="itemids" name="itemids" value="<?php echo $listOfItemIds ?>">
					<input type="hidden" name="action" value="<?php echo ($isImport ? 'import' : 'update') ?>">
				<?php } else { ?>
					<a href="admin.php?page=view_item&itemids=<?php echo $listOfItemIds ?>&action=edit" class="page-title-action">Edit All <?php echo count($items) ?> Items</a>
				<?php } ?>
				
				
				</h1>
				<hr class="wp-header-end">
				<div id="itemcontainer">
					<?php foreach ($items as $item) { self::printItem($item, $editable, $isImport); } ?>
				</div>
			</form>
		</div>
			
<?php 		
	}
	
	
	private static function printItem (EAL_Item $item, bool $isEditable, bool $isImport) {
		
		$htmlPrinter = $item->getHTMLPrinter();
		
 		$prefix = "item_{$item->getId()}_";

?>		
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<input type="text" size="30" value="<?php echo $item->title ?>" id="title" readonly>
						</div>
					</div><!-- /titlediv -->
					
					<?php if ($isEditable) { ?>
						<input type="hidden" name="<?php echo $prefix ?>post_ID"      value="<?php echo $item->getId() ?>">
				  		<input type="hidden" name="<?php echo $prefix ?>post_type"    value="<?php echo $item->getType() ?>">
		  				<input type="hidden" name="<?php echo $prefix ?>post_content" value="<?php echo microtime() ?>">
		  				<input type="hidden" name="<?php echo $prefix ?>post_title"   value="<?php echo htmlentities ($item->title, ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>">
					<?php } ?>
					
				</div><!-- /post-body-content -->
				<div id="postbox-container-1" class="postbox-container">
					<?php // echo HTML_Item::getHTML_Metadata($item, $editable ? HTML_Object::VIEW_EDIT   : HTML_Object::VIEW_STUDENT, "item_{$item->getId()}_") ?>
					
					<div id="mb_status" class="postbox ">
						<h2 class="hndle">
							<span>Item (<?php  echo ($item->getId()>0 ? 'ID:'.$item->getId() : 'new') ?>)</span>
							<?php 
								if (($item->getId() > 0) && (!$isImport)) {
									printf ('<span style="float: right; font-weight:normal" ><a style="vertical-align:middle" class="page-title-action" href="post.php?action=edit&post=%d">Edit</a></span>', $item->getId());
								}
								
							?> 
						</h2>

						<div class="inside">
							<?php 
								if ($isEditable) { 
									printf ('<span style="float: right; font-weight:normal" ><a style="vertical-align:middle" >(Set this status for all items)</a></span>');
								}
								$htmlPrinter->printStatus ($isEditable, $isImport, $prefix);
							?>
						</div>
					</div>
		
					<div id="mb_learnout" class="postbox ">
						<h2 class="hndle"><span>Learning Outcome</span></h2>
						<div class="inside"><?php $htmlPrinter->printLearningOutcome($isEditable, $prefix) ?></div>
					</div>
			
					<div id="mb_level" class="postbox ">
						<h2 class="hndle"><span>Anforderungsstufe</span></h2>
						<div class="inside"><?php $htmlPrinter->printLevel($isEditable, $prefix) ?></div>
					</div>
					
					<div class="postbox ">
						<h2 class="hndle"><span><?php echo RoleTaxonomy::getDomains()[$item->getDomain()] ?></span></h2>
						<div class="inside"><?php echo $htmlPrinter->printTopic($isEditable, $prefix) ?></div>
						<!--  HTML_Object::getHTML_Topic($item->getDomain(), $item->getId(), $isEditable, $prefix)  -->
					</div>
	
					<div class="postbox ">
						<h2 class="hndle"><span>Notiz</span></h2>
						<div class="inside"><?php $htmlPrinter->printNoteFlag($isEditable, $prefix) ?></div>
					</div>
					
				</div>
	
				<div id="postbox-container-2" class="postbox-container">

					<div class="postbox" style="background-color:transparent; border:none">
						<div class="inside">
							<?php $htmlPrinter->printDescription($isImport, $prefix) ?>
							<?php $htmlPrinter->printQuestion($isImport, $prefix) ?>
							<?php $htmlPrinter->printAnswers(!$isEditable, FALSE, $isImport) ?>
						</div>
					</div>
				
					<?php // echo HTML_Item::getHTML_Item ($item, $isEditable ? HTML_Object::VIEW_REVIEW : HTML_Object::VIEW_STUDENT, "item_{$item->getId()}_") ?>
				</div>
			</div><!-- /post-body -->
			<br class="clear">
		</div>
		
		
<?php 		
	}
	
	
	
}

?>