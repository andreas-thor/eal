<?php 

require_once ("class.EAL_Item.php");

class PAG_Basket {

	
	
	
	
	private static function getValuesByKey ($name, $item, $parent) {
		
		if (($name == null) || ($name=="none")) return [0];
		
		if ($name == "type") return [$item->type];
		
		if (($name == "dim") || ($name == "level")) {
			$res = array();
			foreach (array ('FW', 'PW', 'KW') as $dim) {
				if ($item->level[$dim] > 0) {
					if ($name == "dim") array_push ($res, $dim); // return [$dim];
					if ($name == "level") array_push ($res, $item->level[$dim]); // return [$item->level[$dim]];
				}
			}
			return $res;
		}
		
		if (($name == "topic1") || ($name == "topic2")) {
			
			$res = array();
			foreach (wp_get_post_terms($item->id, 'topic') as $term) {
				
				$termhier = array($term->name);
				$parentId = $term->parent;
				while ($parentId>0) {
					$parentTerm = get_term ($parentId, 'topic');
					$termhier = array_merge (array ($parentTerm->name), $termhier);
					$parentId = $parentTerm->parent;
				}
				
				if ($name=="topic1") array_push ($res, $termhier[0]);
				
				// for topic2: check if available AND if parent=topic1 is the same
				if (($name=="topic2") && (count($termhier)>1) && ($termhier[0]==$parent)) array_push ($res, $termhier[1]);
				
			}
			return $res;
			
			
		}
		
		return [];
		
	}
	
	/**
	 * Groups / partitions set of items by category
	 * @param String $name category (type, dim, level, topic1, topic2)
	 * @param EAL_Item[] $items
	 * @param String $parent Parent term (i.e., term of topic1) if category = topic2
	 * @param unknown $complete
	 * @return array ( value => EAL_Item[] )
	 */
	
	private static function groupBy ($name, $items, $parent, $complete) {
	
		$res = array();
		$complete = true;
		if ($complete) {	// makes sure all keys appear (available for all dimensions except for topic)
			if (($name=="none") || ($name==null)) $res = array (0 => []);
			if ($name=="type") 	$res = array ("itemsc" => [], "itemmc" => []);
			if ($name=="dim") 	$res = array ("FW" => [], "KW" => [], "PW" => []);
			if ($name=="level")	$res = array ("1" => [],"2" => [],"3" => [],"4" => [],"5" => [],"6" => []);
		}
		
		foreach ($items as $item) {
			$values = PAG_Basket::getValuesByKey($name, $item, $parent);
			foreach ($values as $value) {
				if ($res[$value] == null) $res[$value] = array();
				array_push ($res[$value], $item);
			}
		}
		ksort ($res);	// sort by key
		return $res;
	}
	
	
	
	public static function loadAllItemsFromBasket () {
	
		// load all items from basket
		$items = array ();
		$itemids = get_user_meta(get_current_user_id(), 'itembasket', true);
		if ($itemids == null) $itemids = array();
		foreach ($itemids as $item_id) {
			$post = get_post($item_id);
			if ($post == null) continue;
			$item = null;
			if ($post->post_type == 'itemsc') $item = new EAL_ItemSC();
			if ($post->post_type == 'itemmc') $item = new EAL_ItemMC();
			if ($item == null) continue;
			$item->loadById($item_id);
			array_push($items, $item);
		}
		
		return $items;
		
	}
	
	
	public static function load_items_callback () {
		
// 		global $wpdb; // this is how you get access to the database
	
		
		$items = PAG_Basket::loadAllItemsFromBasket ();
		
		
		// FOR DEBUG
		$dim_names = array(array ($_POST['dimx0'], $_POST['dimx1'], $_POST['dimx2']), array($_POST['dimy0'], $_POST['dimy1'], $_POST['dimy2']));
// 		$dim_names = array(array ('dim', null, null), array('type', null, null));
// 		$dim_values = array(array(), array());
// 		foreach ([0,1] as $x) {
// 			foreach ([0,1,2] as $y) {
// 				$dim_values[$x][$y] = PAG_Basket::getDimXValues($dim_names[$x][$y]);
// 			}
// 		}
		
		
		$noOfDimY = 3;
		if (($_POST['dimy2']=="none") || ($_POST['dimy2']==null)) $noOfDimY = 2;  
		if (($_POST['dimy1']=="none") || ($_POST['dimy1']==null)) $noOfDimY = 1;
		if (($_POST['dimy0']=="none") || ($_POST['dimy0']==null)) $noOfDimY = 0;
		
		
		// group items by all dimensions (3+3)
		$res = array();
		$createHeader = true;
		$lines = array ();
		$lines_href = array ();
		$row_span = array ( array (), array());
		
		foreach (PAG_Basket::groupBy($dim_names[1][0], $items, null, false) as $k1 => $items1) {
			
			$level1_first = true;
			$level1_span = 0;
			
			foreach (PAG_Basket::groupBy($dim_names[1][1], $items1, $k1, false) as $k2 => $items2) {
			
				
				$level2_first = true;
				$level2_span = 0;
				
				foreach (PAG_Basket::groupBy($dim_names[1][2], $items2, $k2, false) as $k3 => $items3) {
		
					if (createHeader) {
						$header_values = array([],[],[]);
						$header_sizes = array([],[],[]);
					}
				
					$level1_span++;
					$level2_span++;
					$line = array ($level1_first?$k1:"SPAN", $level2_first?$k2:"SPAN", $k3);
					$line_href = array ('','','');
					$level1_first = false;
					$level2_first = false;
					
					foreach (PAG_Basket::groupBy($dim_names[0][0], $items3, null, true) as $k4 => $items4) {
						
						if (createHeader) {						
							array_push ($header_values[0], (($dim_names[0][0]=="none") || ($dim_names[0][0]==null)) ? "" : $k4);
							$count_k4 = 0;
						}
						
						foreach (PAG_Basket::groupBy($dim_names[0][1], $items4, $k4, true) as $k5 => $items5) {

							if (createHeader) {
								array_push ($header_values[1], (($dim_names[0][1]=="none") || ($dim_names[0][1]==null)) ? "" : $k5);
								$count_k5 = 0;
							}
								
							foreach (PAG_Basket::groupBy($dim_names[0][2], $items5, $k5, true) as $k6 => $items6) {
									
								$res[$k1][$k2][$k3][$k4][$k5][$k6] = $items6;

								$href = '';
								foreach ($items6 as $i) { $href .= ($href=="") ? $i->id : "," . $i->id; }
								
								array_push ($line, count($items6));
								array_push ($line_href, $href);
								
								if (createHeader) {
									array_push ($header_values[2], (($dim_names[0][2]=="none") || ($dim_names[0][2]==null)) ? "" : $k6);
									array_push ($header_sizes[2], 1);
									$count_k4++;
									$count_k5++;
								}
							}
							
							if (createHeader) {
								array_push ($header_sizes[1], $count_k5);
							}
									
						}
						
						if (createHeader) {
							array_push ($header_sizes[0], $count_k4);
						}
						
					}
					
					$createHeader = false;
					array_push ($lines, $line);
					array_push ($lines_href, $line_href);
				}
				
				
				array_push ($row_span[1], $level2_span);
			}
			
			array_push ($row_span[0], $level1_span);
				
			
			
		}
		
		
		

		// 
		
		
		


		

		
		
		
		
		
		
			
			
// 			$row['terms'] = array ();
// 			foreach (wp_get_post_terms($item_id, 'topic') as $term) {
// 				$termhier = array($term->name);
// 				$parentId = $term->parent;
// 				while ($parentId>0) {
// 					$parentTerm = get_term ($parentId, 'topic');
// 					$termhier = array_merge (array ($parentTerm->name), $termhier);
// 					$parentId = $parentTerm->parent;
// 				}
// 				$row['terms'] = array_merge ($row['terms'], array ($termhier));
// 			}
			
			
// 			$item->loadById($item_id);

// 			$row['type'] = $item->type;
// 			$dim = '';
// 			$level = 0;
			
			
// 			$row['points'] = $item->getPoints();
		
// 			array_push($items, $row);
// 		}
		
		
		
		$whatever = intval( $_POST['whatever'] );
	
		$whatever += 10;
	
		// no nested arrays for javascript --> expand to header1, header2 etc. 
		wp_send_json (array (
			'header_values_0' => $header_values[0],
			'header_values_1' => $header_values[1],
			'header_values_2' => $header_values[2],
			'header_sizes_0'  => $header_sizes[0],
			'header_sizes_1'  => $header_sizes[1],
			'header_sizes_2'  => $header_sizes[2],
			'row_span_0'	  => $row_span[0],
			'row_span_1'	  => $row_span[1],
			'noOfDimY'		  => $noOfDimY,
			'lines' => $lines,
			'lines_href' => $lines_href
		) );
		
// 		echo $whatever;
	
// 		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	
	public static function load_items_javascript () {
		
		?>
			<script type="text/javascript" >

			jQuery(document).ready(function($) {
				load_items();
			});

			function load_items() {

				var data = {
						'action': 'load_items',
						'dimx0': jQuery("#dimensionsX #dim0").val(),
						'dimx1': jQuery("#dimensionsX #dim1").val(),
						'dimx2': jQuery("#dimensionsX #dim2").val(),
						'dimy0': jQuery("#dimensionsY #dim0").val(),
						'dimy1': jQuery("#dimensionsY #dim1").val(),
						'dimy2': jQuery("#dimensionsY #dim2").val(),
						'valtype': jQuery("#values #val").val()
					};
	
				console.log ("Call AJAX");			
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					console.log(response);
					updateTable(response);
				});
					
			}

			

			
			function updateTable(response) {

				jQuery("#itemtable").empty();

				console.log (response);
				
				lines = new Array(3);
				for (i of [0,1,2]) {
					if (response['header_values_'+i][0] == "") continue;
					for (k=0; k<response['header_values_'+i].length; k++) {
						lines[i] += "<th colspan='" + response['header_sizes_'+i][k] + "'>"+ response['header_values_'+i][k] +"</th>";
					}
				}
				
				for (line of lines) {
					prefix = response['noOfDimY']>0 ? "<th colspan='" + response['noOfDimY'] + "'></th>" : ""
					if (typeof line != "undefined") jQuery("#itemtable").append ("<tr>" + prefix + line + "</tr>");
				}

				level1span = 0;
				level2span = 0;

				for (y=0; y<response['lines'].length; y++) {
				// for (line of response['lines']) {

					line = response['lines'][y];
					line_href = response['lines_href'][y];
					
					
					s = "";
					if ((line[0]!="SPAN") && (response['noOfDimY']>0)) {
						s+= "<td rowspan='" + response['row_span_0'][level1span] + "'>" + (line[0]=="0"?"":line[0]) + "</td>";
						level1span++;
					}
					if ((line[1]!="SPAN") && (response['noOfDimY']>1)) {
						s+= "<td rowspan='" + response['row_span_1'][level2span] + "'>" + (line[1]=="0"?"":line[1]) + "</td>";
						level2span++;
					}

					if (response['noOfDimY']>2) {
						s+= "<td>" + (line[2]=="0"?"":line[2]) + "</td>";
					}
					
					for (i=3; i<line.length; i++) {
						s+= "<td>" + (line[i]=="0"?"": "<a href='admin.php?page=view&itemids=" + line_href[i] + "'>" + line[i] + "</a>") + "</td>";
					}
					jQuery("#itemtable").append ("<tr>" + s + "</tr>");
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
		
		
// 		add_action( 'admin_footer', array ('PAG_Basket', 'load_items_callback') ); // Write our JS below here
		add_action( 'admin_footer', array ('PAG_Basket', 'load_items_javascript') ); // Write our JS below here
		?>
	
		<h1>Item Explorer</h1>
	
		<button draggable='true'> Hier <span style='font-weight:bolder; color:#FF0000'>&nbsp;&nbsp;&#10005; </span></button>
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
				 <select id="dim0" name="dim0" onchange="onChangeDimX(); load_items()">
	  				<option value="none">None</option>
	  				<option value="type" selected>Item Typ</option>
	  				<option value="dim">Dimension</option>
	  				<option value="level">Anforderungsstufe</option>
				</select>
				<br/>
				 <select id="dim1" name="dim1" onchange="onChangeDimX(); load_items()">
	  				<option value="none">None</option>
	  				<option value="type">Item Typ</option>
	  				<option value="dim" selected>Dimension</option>
	  				<option value="level">Anforderungsstufe</option>
				</select>
				<br/>
				 <select id="dim2" name="dim2" onchange="onChangeDimX(); load_items()">
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
			 <select id="dim0" name="dim0" onchange="load_items()">
  				<option value="none">None</option>
  				<option value="topic1" selected>Topic Level 1</option>
  				<option value="topic2">Topic Level 2</option>
  				<option value="dim">Dimension</option>
  				<option value="level">Anforderungsstufe</option>
			</select>
			<br/>
			 <select id="dim1" name="dim1" onchange="load_items()">
  				<option value="none">None</option>
  				<option value="topic1">Topic Level 1</option>
  				<option value="topic2">Topic Level 2</option>
  				<option value="type">Item Typ</option>
  				<option value="dim">Dimension</option>
  				<option value="level">Anforderungsstufe</option>
			</select>
			<br/>
			 <select id="dim2" name="dim2" onchange="load_items()">
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
	
		$myListTable = new CPT_Item_Table();
		$action = $myListTable->process_bulk_action();
		
		if ($action == "viewitems") {
			return PAG_Basket::page_view();
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


	
	public static function page_generator () {
	
		
		
		
		$criteria = array (
				
			"item_type" => array (
				"sc" => "Single Choice",	
				"mc" => "Multiple Choice"	
			),	
			"dimension" => array (
					"KW" => "KW",
					"FW" => "FW",
					"PW" => "PW",
			)
				
		);
		
		?>
		
  
		
		 
  
  
<div class="wrap">

	
			
		


		<?php 
		
		
		
		
			
			$html  = sprintf ("<form  enctype='multipart/form-data' action='admin.php?page=generator' method='post'><table class='form-table'><tbody'>");
		
			
			$items = PAG_Basket::loadAllItemsFromBasket ();
			
// 			
			
			// Number of Items (min, max)
			$_SESSION['min_number'] = isset($_REQUEST['min_number']) ? $_REQUEST['min_number'] : (isset($_SESSION['min_number']) ? min ($_SESSION['min_number'], count($items)) : 0);
			$_SESSION['max_number'] = isset($_REQUEST['max_number']) ? $_REQUEST['max_number'] : (isset($_SESSION['max_number']) ? min ($_SESSION['max_number'], count($items)) : count($items));
			
			$html .= sprintf("<tr><th style='padding-top:0px; padding-bottom:0px;'><label>%s</label></th>", "Number of Items");
			$html .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
			$html .= sprintf("<input style='width:5em' type='number' name='min_%s' min='0' max='%d' value='%d'/>", "number", count($items), $_SESSION['min_number']);
			$html .= sprintf("<input style='width:5em' type='number' name='max_%s' min='0' max='%d' value='%d'/>", "number", count($items), $_SESSION['max_number']);
			$html .= sprintf("</td></tr>");
			
			// Min / Max for all categories
			$categories = array ("type", "dim", "level", "topic1");
			foreach ($categories as $category) {
				
				$html .= sprintf("<tr><th style='padding-bottom:0.5em;'><label>%s</label></th></tr>", EAL_Item::$category_label[$category]);
				foreach (PAG_Basket::groupBy ($category, $items, NULL, true) as $catval => $catitems) {
					
					$_SESSION['min_'.$catval] = isset($_REQUEST['min_'.$catval]) ? $_REQUEST['min_'.$catval] : (isset($_SESSION['min_'.$catval]) ? min ($_SESSION['min_'.$catval], count($catitems)) : 0);
					$_SESSION['max_'.$catval] = isset($_REQUEST['max_'.$catval]) ? $_REQUEST['max_'.$catval] : (isset($_SESSION['max_'.$catval]) ? min ($_SESSION['max_'.$catval], count($catitems)) : count($catitems));
						
					$html .= sprintf("<tr><td style='padding-top:0px; padding-bottom:0px;'><label>%s</label></td>", ($category == "topic1") ? $catval : EAL_Item::$category_value_label[$category][$catval]);
					$html .= sprintf("<td style='padding-top:0px; padding-bottom:0px;'>");
					$html .= sprintf("<input style='width:5em' type='number' name='min_%s' min='0' max='%d' value='%d'/>", $catval, count($catitems), $_SESSION['min_'.$catval]);
					$html .= sprintf("<input style='width:5em' type='number' name='max_%s' min='0' max='%d' value='%d'/>", $catval, count($catitems), $_SESSION['max_'.$catval]);
					$html .= sprintf("</td></tr>");
				}
				
			}
			
			$html .= sprintf ("<tr><th><button type='submit' name='action' value='generate'>Generate</button></th><tr>");
			$html .= sprintf ("</tbody></table></form></div>");			

			
			
			// (re-)generate pools
			if ($_REQUEST['action'] == 'generate') {
				$pool = new ALG_Item_Pool(
					$items,
					array ($_SESSION['min_number'], $_SESSION['min_itemsc'], $_SESSION['min_itemmc'], $_SESSION['min_FW'], $_SESSION['min_KW'], $_SESSION['min_PW']),
					array ($_SESSION['max_number'], $_SESSION['max_itemsc'], $_SESSION['max_itemmc'], $_SESSION['max_FW'], $_SESSION['max_KW'], $_SESSION['max_PW'])
				);
				
				$_SESSION['generated_pools'] = $pool->generatedPools;
			}
			
			
			
			print ("<h1>Task Pool Generator</h1><br/>");
			print ($html);
			
			// show pools (from last generation; stored in Session variable)
			if (isset ($_SESSION['generated_pools'])) {
// 				print_r ($_SESSION['generated_pools']);
				
				print ("<br/><h2>Generated Task Pools</h2>");
				printf ("<table cellpadding='10px' class='widefat fixed' style='table-layout:fixed; width:%dem; background-color:rgba(0, 0, 0, 0);'>", 6+2*count($items)); 
				print ("<col width='6em;' />");
				
				foreach ($items as $item) {
					print ("<col width='2em;' />");
				}
				
				foreach ($_SESSION['generated_pools'] as $pool) {
					print ("<tr valign='middle'>");
					$s = "View";
					$href = "admin.php?page=view&itemids=" . join (",", $pool);
					printf ("<td style='overflow: hidden; padding:0px; padding-bottom:0.5em; padding-top:0.5em; padding-left:1em' ><a href='%s' class='button'>View</a></td>", $href);
					
					// http://localhost/wordpress/wp-admin/admin.php?page=view&itemids=458,307,307,106
					foreach ($items as $item) {
						
						$symbol = "";
						$link = "";
						if (in_array($item->id, $pool)) {
							$link = sprintf ("onClick='document.location.href=\"admin.php?page=view&itemid=%s\";'", $item->id);
							if ($item->type == "itemsc") $symbol = "<span class='dashicons dashicons-marker'></span>";	
							if ($item->type == "itemmc") $symbol = "<span class='dashicons dashicons-forms'></span>";	
						}
						
						
						printf ("<td %s valign='bottom' style='overflow: hidden; padding:0px; padding-top:0.83em;' >%s</td>", $link, $symbol /*(in_array($item->id, $pool) ? "X" : "")*/);
					}
					print ("</tr>");
				}
				print ("</table>");
				
				
			}
			
				
		
	}
	
	public static function page_view () {
					
		$itemids = array();
		if ($_REQUEST['itemid'] != null) {
			array_push($itemids, $_REQUEST['itemid']);
		} else {					
			if ($_REQUEST['itemids'] != null) {
				if (is_array($_REQUEST['itemids'])) $itemids = $_REQUEST['itemids'];
				if (is_string($_REQUEST['itemids'])) $itemids = explode (",", $_REQUEST["itemids"]);
			}
			else {
				$itemids = get_user_meta(get_current_user_id(), 'itembasket', true);
			}
		}
		

		
		$html_list = "";
		$html_select = "<form><select onChange='for (x=0; x<this.form.nextSibling.childNodes.length; x++) {  this.form.nextSibling.childNodes[x].style.display = ((this.value<0) || (this.value==x)) ? \"block\" :  \"none\"; }'><option value='-1' selected>All " . count($itemids) . " items</option>";
		$count = 0;		
		foreach ($itemids as $item_id) {
			
			
			$post = get_post($item_id);
			if ($post == null) continue;
			
			
			$item = null;
			if ($post->post_type == 'itemsc') $item = new EAL_ItemSC();
			if ($post->post_type == 'itemmc') $item = new EAL_ItemMC();
			
			if ($item != null) {
				$item->loadById($item_id);
				$html_select .= "<option value='{$count}'>{$item->title}</option>";
				$html_list .= "<div style='margin-top:2em;'>" . $item->getPreviewHTML(FALSE) . "</div>";
				$count++;
			}
			
			
			
		}
		
		$html_select .= "</select></form>";
		
		print "<div class='wrap'>";
		if (count($itemids)>1) print $html_select;
		print "<div style='margin-top:2em'>{$html_list}</div>";
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
