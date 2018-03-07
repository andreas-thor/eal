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
		
		// load all items (if not given)
		$items = [];
		foreach ($itemids as $item_id) {
			if (array_key_exists($item_id, $items)) continue;	// item already loaded
			$post = get_post($item_id);
			if ($post === NULL) continue;	// item (post) does not exist
			$items[$item_id] = EAL_Item::load($post->post_type, $item_id);
		}
		
		self::printItemList($items);
		
// 		if ($_POST['action']=='import') $itemids = Importer::doImport($itemids, FALSE);
// 		if ($_POST['action']=='update') $itemids = Importer::doImport($itemids, TRUE);
// 		self::viewItems($itemids, NULL, $_REQUEST['edit']=='1', $_REQUEST["page"]);
	}
	
	
	public static function page_view_basket () {
		self::printItemList(EAL_ItemBasket::getItems());
	}
	
	
	private static function printItemList (array $items, string $action = "view") {
		

		
?>
		<div class="wrap">
			<h1>Mein neuer Item Viewer</h1>
			<hr class="wp-header-end">
			<?php foreach ($items as $item) { self::printItem($item); } ?>
		</div>
			
<?php 		
	}
	
	
	private static function printItem (EAL_Item $item, int $viewType = HTML_Object::VIEW_REVIEW) {
		
		$viewType = HTML_Object::VIEW_EDIT;
		$prefix = "item_{$item->getId()}_";
		
		// FIXME edit link anpassen
		$edit = ($item->getId() > 0) ? sprintf ('<span style="float: right; font-weight:normal" ><a style="vertical-align:middle" class="page-title-action" href="post.php?action=edit&post=%d">Edit</a></span>', $item->getId()) : '';
?>		
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<input type="text" size="30" value="<?php echo $item->title ?>" id="title" readonly>
						</div>
					</div><!-- /titlediv -->
				</div><!-- /post-body-content -->
				<div id="postbox-container-1" class="postbox-container">
					<?php // echo HTML_Item::getHTML_Metadata($item, $editable ? HTML_Object::VIEW_EDIT   : HTML_Object::VIEW_STUDENT, "item_{$item->getId()}_") ?>
					
					<div id="mb_status" class="postbox ">
						<h2 class="hndle">
							<span>Item (<?php  echo ($item->getId()>0 ? 'ID:'.$item->getId() : 'new') ?>)</span>
							<?php  echo $edit ?> 
						</h2>
						<div class="inside">
							<?php echo HTML_Item::getHTML_Status($item, $viewType, $prefix) ?>
						</div>
					</div>
		
					<div id="mb_learnout" class="postbox ">
						<h2 class="hndle"><span>Learning Outcome</span></h2>
						<div class="inside"><?php echo HTML_Item::getHTML_LearningOutcome($item, $viewType, $prefix) ?></div>
					</div>
			
					<div id="mb_level" class="postbox ">
						<h2 class="hndle"><span>Anforderungsstufe</span></h2>
						<div class="inside"><?php echo HTML_Item::getHTML_Level($item, $viewType, $prefix) ?></div>
					</div>
					
			
					<div class="postbox ">
						<h2 class="hndle"><span><?php echo RoleTaxonomy::getDomains()[$item->getDomain()] ?></span></h2>
						<div class="inside"><?php echo HTML_Object::getHTML_Topic($item->getDomain(), $item->getId(), $viewType, $prefix) ?></div>
					</div>
	
					<div class="postbox ">
						<h2 class="hndle"><span>Notiz</span></h2>
						<div class="inside"><?php echo HTML_Item::getHTML_NoteFlag($item, $viewType, $prefix) ?></div>
					</div>
					
				</div>
	
				<div id="postbox-container-2" class="postbox-container">
					<?php echo HTML_Item::getHTML_Item ($item, $editable ? HTML_Object::VIEW_REVIEW : HTML_Object::VIEW_STUDENT, "item_{$item->getId()}_") ?>
				</div>
			</div><!-- /post-body -->
			<br class="clear">
		</div>
		
		
<?php 		
	}
	
	
	
}

?>