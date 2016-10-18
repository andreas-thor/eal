<?php 

require_once ("class.EAL_Item.php");
require_once ("class.EAL_ItemSC.php");
require_once ("class.EAL_ItemMC.php");
require_once ("class.EAL_LearnOut.php");
require_once ("class.EAL_Review.php");
require_once ("class.EXP_Ilias.php");

class PAG_Basket {

	
	
	
	public static function loadAllItemsFromBasket () {
	
		// load all items from basket
		$items = array ();
		$itemids = RoleTaxonomy::getCurrentBasket(); // get_user_meta(get_current_user_id(), 'itembasket', true);
		if ($itemids == null) $itemids = array();
		$itemids_new = array();
		foreach ($itemids as $item_id) {
			
			$post = get_post($item_id);
			if ($post == null) continue;
			
			$item = null;
			if ($post->post_type == 'itemsc') $item = new EAL_ItemSC();
			if ($post->post_type == 'itemmc') $item = new EAL_ItemMC();
			if ($item == null) continue;
			$item->loadById($item_id);
			if ((RoleTaxonomy::getCurrentRoleDomain()["name"]!="") && ($item->domain!=RoleTaxonomy::getCurrentRoleDomain()["name"])) continue;
				
			array_push($items, $item);
			array_push($itemids_new, $item_id);
		}
		
		RoleTaxonomy::setCurrentBasket($itemids_new); // update_user_meta (get_current_user_id(), 'itembasket', $itemids_new);
		return $items;
		
	}
	
	
	public static function createPageTable () {
	
		wp_redirect( 'http://www.google.de' );
		exit();
		
		
		$myListTable = new CPT_Item_Table();
		$action = $myListTable->process_bulk_action();
		
		if ($action == "viewitems") {
			return PAG_Basket::createPageView();
		}
		
		
		// echo '<div class="wrap"><h2>My List Table Test</h2>';
		$myListTable->prepare_items();
		
		?>
		
			<div class="wrap">
			
				<h1>Item Basket</h1>
		
		<form method="post">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php 
			$myListTable->search_box('search', 'search_id'); 
			$myListTable->display();
			?>
		</form>
			</div>
		<?php 		
	}


	
	public static function createPageView () {
					
		$itemids = array();
		
		/* try to get learning outcomes */
		$post_label = "Learning Outcome";
		if ($_REQUEST['learnoutid'] != null) $itemids = [$_REQUEST['learnoutid']];
		if ($_REQUEST['learnoutids'] != null) {
			if (is_array($_REQUEST['learnoutids'])) $itemids = $_REQUEST['learnoutids'];
			if (is_string($_REQUEST['learnoutids'])) $itemids = explode (",", $_REQUEST["learnoutids"]);
		}

		if (count($itemids)==0) {  // learning outcomes found --> get reviews
			$post_label = "Review";
			if ($_REQUEST['reviewid'] != null) $itemids = [$_REQUEST['reviewid']];
			if ($_REQUEST['reviewids'] != null) {
				if (is_array($_REQUEST['reviewids'])) $itemids = $_REQUEST['reviewids'];
				if (is_string($_REQUEST['reviewids'])) $itemids = explode (",", $_REQUEST["reviewids"]);
			}
		}
			
		if (count($itemids)==0) {	// nothing found --> get items
			$post_label = "Item";
			if ($_REQUEST['itemid'] != null) $itemids = [$_REQUEST['itemid']];
			if ($_REQUEST['itemids'] != null) {
				if (is_array($_REQUEST['itemids'])) $itemids = $_REQUEST['itemids'];
				if (is_string($_REQUEST['itemids'])) $itemids = explode (",", $_REQUEST["itemids"]);
			}

			// fallback: get items from basket
			if (count($itemids) == 0) $itemids = RoleTaxonomy::getCurrentBasket(); // get_user_meta(get_current_user_id(), 'itembasket', true);
		}
		
		

		
		$html_list = "";
		$html_select  = "<form><select onChange='for (x=0; x<this.form.nextSibling.childNodes.length; x++) {  this.form.nextSibling.childNodes[x].style.display = ((this.value<0) || (this.value==x)) ? \"block\" :  \"none\"; }'>";
		$html_select .= sprintf ('<option value="-1" selected>[All %1$d %2$ss]</option>', count($itemids), $post_label);
		$count = 0;
		$items = array ();
		foreach ($itemids as $item_id) {
			
			$post = get_post($item_id);
			if ($post == null) continue;
			
			$item = null;
			if ($post->post_type == 'itemsc') $item = new EAL_ItemSC();
			if ($post->post_type == 'itemmc') $item = new EAL_ItemMC();
			if ($post->post_type == 'learnout') $item = new EAL_LearnOut();
			if ($post->post_type == 'review') $item = new EAL_Review();
				
			if ($item != null) {
				$item->loadById($item_id);
				$html_select .= "<option value='{$count}'>{$item->title}</option>";
				$html_list .= "<div style='margin-top:2em;'>" . $item->getPreviewHTML(FALSE) . "</div>";
				$count++;
				array_push($items, $item);
			}
		}
		$html_select .= "</select></form>";
		
		
		if ($post_label=="Item") {
			$html_info  = sprintf("<form  style='margin-top:5em' enctype='multipart/form-data' action='admin.php?page=view&download=1&itemids=%s' method='post'><table class='form-table'><tbody'>", implode(",",$itemids));
			$html_info .= sprintf("<tr><th style='padding-top:0px; padding-bottom:0px;'><label>%s</label></th>", "Number of Items");
			$html_info .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
			$html_info .= sprintf("<input style='width:5em' type='number' value='%d' readonly/>", count($items));
			$html_info .= sprintf("</td></tr>");
			
			// Min / Max for all categories
			$categories = array ("type", "dim", "level", "topic1");
			foreach ($categories as $category) {
					
				$html_info .= sprintf("<tr><th style='padding-bottom:0.5em;'><label>%s</label></th></tr>", EAL_Item::$category_label[$category]);
				foreach (PAG_Explorer::groupBy ($category, $items, NULL, true) as $catval => $catitems) {
			
					$html_info .= sprintf("<tr><td style='padding-top:0px; padding-bottom:0px;'><label>%s</label></td>", ($category == "topic1") ? $catval : EAL_Item::$category_value_label[$category][$catval]);
					$html_info .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
					$html_info .= sprintf("<input style='width:5em' type='number' value='%d' readonly/>", count($catitems));
					$html_info .= sprintf("</td></tr>");
				}
					
			}
			
			$html_info .= sprintf ("<tr><th><button type='submit' name='action' value='download'>Download</button></th><tr>");
			$html_info .= sprintf ("</tbody></table></form></div>");
		}
		
		
		printf ('<div class="wrap"><h1>%1$s Viewer</h1>', $post_label);
		
		if ($_REQUEST['download']=='1') {
			$ilias = new EXP_Ilias();
			$link = $ilias->generateExport($itemids);
			printf ("<h2><a href='%s'>Download</a></h2>", $link);
		}
		
		
		if (count($itemids)>1) {
			print $html_select;
			print "<div style='margin-top:2em'>{$html_list}{$html_info}</div>";
		} else {
			print "<div style='margin-top:2em'>{$html_list}</div>";
		}
		print "</div>"; 
					
	}
}

	
	
	
	
	
	
	
	
	
	
// 	<table class="wp-list-table widefat fixed striped posts">
// 	<thead>
// 	<tr>
// 		<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td><th scope="col" id="title" class="manage-column column-title column-primary sortable desc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=title&amp;order=asc"><span>Title</span><span class="sorting-indicator"></span></a></th><th scope="col" id="taxonomy-topic" class="manage-column column-taxonomy-topic">Topics</th><th scope="col" id="date" class="manage-column column-date sortable asc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=date&amp;order=desc"><span>Date</span><span class="sorting-indicator"></span></a></th><th scope="col" id="FW" class="manage-column column-FW sortable desc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=FW&amp;order=asc"><span>FW</span><span class="sorting-indicator"></span></a></th><th scope="col" id="KW" class="manage-column column-KW sortable desc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=KW&amp;order=asc"><span>KW</span><span class="sorting-indicator"></span></a></th><th scope="col" id="PW" class="manage-column column-PW sortable desc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=PW&amp;order=asc"><span>PW</span><span class="sorting-indicator"></span></a></th><th scope="col" id="Punkte" class="manage-column column-Punkte sortable desc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=Punkte&amp;order=asc"><span>Punkte</span><span class="sorting-indicator"></span></a></th><th scope="col" id="Reviews" class="manage-column column-Reviews sortable desc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=Reviews&amp;order=asc"><span>Reviews</span><span class="sorting-indicator"></span></a></th><th scope="col" id="LO" class="manage-column column-LO sortable desc"><a href="http://localhost/wordpress/wp-admin/edit.php?post_type=itemsc&amp;orderby=LO&amp;order=asc"><span>LO</span><span class="sorting-indicator"></span></a></th>	</tr>
// 	</thead>

// 	<tbody id="the-list">
// 				<tr id="post-405" class="iedit author-self level-0 post-405 type-itemsc status-publish hentry">
// 			<th scope="row" class="check-column">			<label class="screen-reader-text" for="cb-select-405">Select Single Choice</label>
// 			<input id="cb-select-405" type="checkbox" name="post[]" value="405">
// 			<div class="locked-indicator"></div>
// 		</th><td class="title column-title has-row-actions column-primary page-title" data-colname="Title"><strong><a class="row-title" href="http://localhost/wordpress/wp-admin/post.php?post=405&amp;action=edit" title="Edit “Single Choice”">Single Choice</a></strong>
// <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>

// <div class="hidden" id="inline_405">
// 	<div class="post_title">Single Choice</div><div class="post_name">single-choice-29</div>
// 	<div class="post_author">1</div>
// 	<div class="comment_status">closed</div>
// 	<div class="ping_status">closed</div>
// 	<div class="_status">publish</div>
// 	<div class="jj">13</div>
// 	<div class="mm">06</div>
// 	<div class="aa">2016</div>
// 	<div class="hh">07</div>
// 	<div class="mn">56</div>
// 	<div class="ss">30</div>
// 	<div class="post_password"></div><div class="post_category" id="topic_405"></div><div class="sticky"></div></div><div class="row-actions"><span class="edit"><a href="http://localhost/wordpress/wp-admin/post.php?post=405&amp;action=edit" title="Edit this item">Edit</a> | </span><span class="trash"><a class="submitdelete" title="Move this item to the Trash" href="http://localhost/wordpress/wp-admin/post.php?post=405&amp;action=trash&amp;_wpnonce=a82515874a">Trash</a> | </span><span class="view"><a href="http://localhost/wordpress/itemsc/single-choice-29/" title="View “Single Choice”" rel="permalink">View</a> | </span><span class="add review"><a href="post-new.php?post_type=itemsc_review&amp;item_id=405">Add&nbsp;New&nbsp;Review</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class="taxonomy-topic column-taxonomy-topic" data-colname="Topics"><span aria-hidden="true">—</span><span class="screen-reader-text">No categories</span></td><td class="date column-date" data-colname="Date">Published<br><abbr title="2016/06/13 7:56:30 am">2016/06/13</abbr></td><td class="FW column-FW" data-colname="FW"></td><td class="KW column-KW" data-colname="KW"></td><td class="PW column-PW" data-colname="PW"></td><td class="Punkte column-Punkte" data-colname="Punkte">1</td><td class="Reviews column-Reviews" data-colname="Reviews"></td><td class="LO column-LO" data-colname="LO"></td>		</tr>
// 			<tr id="post-378" class="iedit author-self level-0 post-378 type-itemsc status-publish hentry">
// 			<th scope="row" class="check-column">			<label class="screen-reader-text" for="cb-select-378">Select Single Choice</label>
// 			<input id="cb-select-378" type="checkbox" name="post[]" value="378">
// 			<div class="locked-indicator"></div>
// 		</th><td class="title column-title has-row-actions column-primary page-title" data-colname="Title"><strong><a class="row-title" href="http://localhost/wordpress/wp-admin/post.php?post=378&amp;action=edit" title="Edit “Single Choice”">Single Choice</a></strong>
// <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>


?>