<?php 

class PAG_Basket {

	
	
	
	public static function load_items_callback() {
		
// 		global $wpdb; // this is how you get access to the database
	
		$items = array ();
		$itemids = get_user_meta(get_current_user_id(), 'itembasket', true);
		foreach ($itemids as $item_id) {
			$post = get_post($item_id);
			if ($post == null) continue;
				
			$row = array ('ID' => $item_id);
		
			if ($post->post_type == 'itemsc') {
				$item = new EAL_ItemSC();
			}
				
			if ($post->post_type == 'itemmc') {
				$item = new EAL_ItemMC();
			}
			
			
			$row['terms'] = array ();
			foreach (wp_get_post_terms($item_id, 'topic') as $term) {
				$termhier = array($term->name);
				$parentId = $term->parent;
				while ($parentId>0) {
					$parentTerm = get_term ($parentId, 'topic');
					$termhier = array_merge (array ($parentTerm->name), $termhier);
					$parentId = $parentTerm->parent;
				}
				$row['terms'] = array_merge ($row['terms'], array ($termhier));
			}
			
			
			$item->loadById($item_id);

			$row['type'] = $item->type;
			$row['dim'] = '';
			$row['level'] = 0;
				
			foreach (array ('FW', 'PW', 'KW') as $dim) {
				if ($item->level[$dim] > 0) {
					$row['dim'] = $dim;
					$row['level'] = $item->level[$dim]; 
				}
			}
			$row['points'] = $item->getPoints();
		
			array_push($items, $row);
		}
		
		
		
		$whatever = intval( $_POST['whatever'] );
	
		$whatever += 10;

		wp_send_json ($items );
		
// 		echo $whatever;
	
// 		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	
	public static function load_items_javascript () {
		
		?>
			<script type="text/javascript" >

			var items;

			jQuery(document).ready(function($) {
				onChangeDimX();
				var data = {
						'action': 'load_items',
						'whatever': 1234
					};

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.post(ajaxurl, data, function(response) {
						items = response;
// 						alert('Got this from the server: ' + response[0]['type']);
						updateTable();
					});


					
			});


			function getDimensionValues (name) {
				if (name=="type") 	return ["itemsc", "itemmc"];
				if (name=="dim") 	return ["FW", "KW", "PW"];
				if (name=="level")	return ["1","2","3","4","5","6"];
				return [];
			}

			
			
			function updateTable() {

				jQuery("#itemtable").empty();

				dim_names = new Array(3);
				dim_values = new Array(3);
				dim_length = new Array(3);
				lines = new Array(3);


				
				for (i of [0,1,2]) {
					dim_names[i] = jQuery("#dimensionsX #dim" + i).val();
					dim_values[i] = getDimensionValues (dim_names[i]);
					dim_length[i] = dim_values[i].length > 1 ? dim_values[i].length : 1;
					if (dim_values[i].length>0) lines[i] = "<th>" + jQuery("#dimensionsX #dim" + i + " option:selected").text() + "</th>"

				}	

				
				for (d0=0; d0<dim_length[0]; d0++) {
					if (d0 < dim_values[0].length) lines[0] += "<th colspan='" + (dim_length[1]*dim_length[2]) + "'>"+dim_values[0][d0]+"</th>";
					for (d1=0; d1<dim_length[1]; d1++) {
						if (d1 < dim_values[1].length) lines[1] += "<th colspan='" + dim_length[2] + "'>"+dim_values[1][d1]+"</th>";
						for (d2=0; d2<dim_length[2]; d2++) {
							if (d2 < dim_values[2].length) lines[2] += "<th>"+dim_values[2][d2]+"</th>";
						}
					}
				}

// 				console.log (dim_names);
// 				console.log (dim_values);
// 				console.log (dim_length);
				
				for (line of lines) {
					if (typeof line != "undefined") jQuery("#itemtable").append ("<tr>" + line + "</tr>");
				}

				valtype = jQuery("#values #val").val();
				
				values = new Array (dim_length[0]*dim_length[1]*dim_length[2]);
				values.fill (0);
				for (item of items) {


					console.log (item['terms']);
					
					lastFactor = 1;
					index = 0;
					for (i of [2,1,0]) {
						// ignore missing dimensions
						if ((dim_names[i]=="none") || (dim_names[i] == null)) continue;

						// unknown value --> disregard this item completely
						pos = dim_values[i].indexOf(item[dim_names[i]]);
						if (pos == -1) {
							index = -1; 
							break;
						}

						index += lastFactor * pos;
						lastFactor = dim_length[i]
						
						
					}

					if (index >= 0) values[index] += (valtype=="points") ? parseInt (item['points']) : 1;
				}
				
				line = "<td></td>";
				for (val of values) {
					line += "<td>"+val+"</td>";
				}
				jQuery("#itemtable").append ("<tr>" + line + "</tr>");
				
				
				
// 				jQuery("#itemtable").append ('<tr><td>' + it['type'] + '</td></tr>');
				
				for (index = 0; index < items.length; index++) {

					it = items[index];
// 					jQuery("#itemtable").append ('<tr><td>' + it['type'] + '</td></tr>'); 
				}

				
				
			}


			function onChangeDimX () {

				for (i=2; i>=0; i--) {
					jQuery("#dimensionsX #dim" + i).children("option").removeAttr('disabled');
					dim = jQuery("#dimensionsX #dim" + i + " option:selected").val();
					for (k=i+1; k<3; k++) {
						// if current selection is none --> disable all deeper levels
						if (dim=="none") {
							jQuery("#dimensionsX #dim" + k).children("option").removeAttr('disabled');
							jQuery("#dimensionsX #dim" + k).val('none');
							jQuery("#dimensionsX #dim" + k).children("option").attr('disabled', 'disabled');
						} else {
							// if not none --> remove current selection from depper levels' choice
							jQuery("#dimensionsX #dim" + k).children("option[value=" + dim + "]").attr('disabled', 'disabled');
						}
					}
				}
			}
			
			</script> <?php
			
	}
	
	
	public static function page_ist_blueprint () {
		
		
		add_action( 'admin_footer', array ('PAG_Basket', 'load_items_javascript') ); // Write our JS below here
	?>
	
		
	
		<table>
			<tr>
			<td>
			<form id="values">
			 <select id="val" name="val" onchange="updateTable()">
  				<option value="number" selected>Number</option>
  				<option value="points">Points</option>
			</select>
			</form>
			</td>
			<td>
			<form id="dimensionsX">
				 <select id="dim0" name="dim0" onchange="onChangeDimX(); updateTable()">
	  				<option value="none">None</option>
	  				<option value="type" selected>Item Typ</option>
	  				<option value="dim">Dimension</option>
	  				<option value="level">Anforderungsstufe</option>
				</select>
				<br/>
				 <select id="dim1" name="dim1" onchange="onChangeDimX(); updateTable()">
	  				<option value="none">None</option>
	  				<option value="type">Item Typ</option>
	  				<option value="dim">Dimension</option>
	  				<option value="level">Anforderungsstufe</option>
				</select>
				<br/>
				 <select id="dim2" name="dim2" onchange="onChangeDimX(); updateTable()">
	  				<option value="none">None</option>
	  				<option value="type">Item Typ</option>
	  				<option value="dim">Dimension</option>
	  				<option value="level">Anforderungsstufe</option>
				</select>
			
			</form>
			</td>
			</tr>
			
			<tr> 
			<td>
		<form id="dimensionsY">
			 <select id="dim1" name="dim1" onchange="updateTable()">
  				<option value="none">None</option>
  				<option value="topic1">Topic Level 1</option>
  				<option value="topic2">Topic Level 2</option>
  				<option value="dim">Dimension</option>
  				<option value="level">Anforderungsstufe</option>
			</select>
			<br/>
			 <select id="dim2" name="dim2" onchange="updateTable()">
  				<option value="none">None</option>
  				<option value="topic1">Topic Level 1</option>
  				<option value="topic2">Topic Level 2</option>
  				<option value="type">Item Typ</option>
  				<option value="dim">Dimension</option>
  				<option value="level">Anforderungsstufe</option>
			</select>
			<br/>
			 <select id="dim3" name="dim3" onchange="updateTable()">
  				<option value="none">None</option>
  				<option value="topic1">Topic Level 1</option>
  				<option value="topic2">Topic Level 2</option>
  				<option value="type">Item Typ</option>
  				<option value="dim">Dimension</option>
  				<option value="level">Anforderungsstufe</option>
			</select>
		
		</form> 
		
		</td>
		<td></td>
		</tr>
		</table>
	
	
		<table id="itemtable" border="1" class="wp-list-table widefat fixed striped posts">
		
		<tr><td>Loading items from basket</td></tr>
		</table>
	
	<?php 
		
	
	}
	
	
	public static function page_itembasket () {
		?>
		<div class="wrap">
		
			<h1>Item Basket</h1>
	<?php 
	
	
	
	$myListTable = new CPT_Item_Table();
	
	
	
	// echo '<div class="wrap"><h2>My List Table Test</h2>';
	$myListTable->prepare_items();
	
	?>
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
