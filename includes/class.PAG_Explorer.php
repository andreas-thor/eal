<?php 

require_once ("class.EAL_Item.php");
require_once ("class.PAG_Basket.php");

class PAG_Explorer {

	
	
	public static function getDimensionLabel ($dim, $k) {
		
		if ($dim == null) return "";
		if ($dim == "none") return "";
		
		if ($dim == "type") {
			if ($k == "itemmc") return "Multiple Choice";
			if ($k == "itemsc") return "Single Choice";
		}
		
		if ($dim == "level") {
			return EAL_Item::$level_label[$k-1];
		}
		
		return $k;
	}
	
	public static function getValuesByKey ($name, $item, $parent, $useTopicIds) {
		
		if (($name == null) || ($name=="none")) return [0];
		
		if ($name == "type") return [$item->type];
		
		if ($name == "difficulty") {
			if (!isset ($item->difficulty)) return [];
			return [$item->difficulty];
		}
		
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

			
			foreach (wp_get_post_terms($item->id, RoleTaxonomy::getCurrentRoleDomain()["name"]) as $term) {
				
				$termhier = $useTopicIds ? array($term->term_id) : array($term->name);
				$parentId = $term->parent;
				while ($parentId>0) {
					$parentTerm = get_term ($parentId, RoleTaxonomy::getCurrentRoleDomain()["name"]);
					$termhier = array_merge ($useTopicIds ? array ($parentTerm->term_id) : array ($parentTerm->name), $termhier);
					$parentId = $parentTerm->parent;
				}
				
				if (($name=="topic1") && (!in_array ($termhier[0], $res))) array_push ($res, $termhier[0]);
				
				// for topic2: check if available AND if parent=topic1 is the same
				if (($name=="topic2") && (count($termhier)>1) && ($termhier[0]==$parent) && (!in_array ($termhier[1], $res))) array_push ($res, $termhier[1]);
				
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
	
	public static function groupBy ($name, $items, $parent, $complete, $useTopicIds=false) {
	
		$res = array();
		$complete = true;
		if ($complete) {	// makes sure all keys appear (available for all dimensions except for topic)
			if (($name=="none") || ($name==null)) $res = array (0 => []);
			if ($name=="type") 	$res = array ("itemsc" => [], "itemmc" => []);
			if ($name=="dim") 	$res = array ("FW" => [], "KW" => [], "PW" => []);
			if ($name=="level")	$res = array ("1" => [],"2" => [],"3" => [],"4" => [],"5" => [],"6" => []);
			if ($name=="difficulty") $res = array ("0.1" => [],"0.2" => [],"0.3" => [],"0.4" => [],"0.5" => [],"0.6" => [], "0.7" => [],"0.8" => [],"0.9" => [],"1.0" => []);
		}
		
		foreach ($items as $item) {
			$values = PAG_Explorer::getValuesByKey($name, $item, $parent, $useTopicIds);
			foreach ($values as $value) {
				if ($res[$value] == null) $res[$value] = array();
				array_push ($res[$value], $item);
			}
		}
		ksort ($res);	// sort by key
		return $res;
	}
	
	
	

	
	public static function load_items_callback () {
		
// 		global $wpdb; // this is how you get access to the database
	
		
		$items = PAG_Basket::loadAllItemsFromBasket ();
		
// 		echo "<script> console.log('dimy0', 'test'); </script>";
// 		print ("<script> console.log('dimy0', '" . $_POST['dimy0'] . "'); </script>");
		
		$_SESSION["dim_names"] = array(array ($_POST['dimx0'], $_POST['dimx1'], $_POST['dimx2']), array($_POST['dimy0'], $_POST['dimy1'], $_POST['dimy2']));
		
		// FOR DEBUG
		$dim_names = array(array ($_POST['dimx0'], $_POST['dimx1'], $_POST['dimx2']), array($_POST['dimy0'], $_POST['dimy1'], $_POST['dimy2']));
// 		$dim_names = array(array ('dim', null, null), array('type', null, null));
// 		$dim_values = array(array(), array());
// 		foreach ([0,1] as $x) {
// 			foreach ([0,1,2] as $y) {
// 				$dim_values[$x][$y] = PAG_Explorer::getDimXValues($dim_names[$x][$y]);
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
		
		foreach (PAG_Explorer::groupBy($dim_names[1][0], $items, null, false) as $k1 => $items1) {
			
			$level1_first = true;
			$level1_span = 0;
			
			foreach (PAG_Explorer::groupBy($dim_names[1][1], $items1, $k1, false) as $k2 => $items2) {
			
				
				$level2_first = true;
				$level2_span = 0;
				
				foreach (PAG_Explorer::groupBy($dim_names[1][2], $items2, $k2, false) as $k3 => $items3) {
		
					if (createHeader) {
						$header_values = array([],[],[]);
						$header_sizes = array([],[],[]);
					}
				
					$level1_span++;
					$level2_span++;
					$line = array (
						$level1_first? PAG_Explorer::getDimensionLabel($dim_names[1][0], $k1) : "SPAN", 
						$level2_first? PAG_Explorer::getDimensionLabel($dim_names[1][1], $k2) : "SPAN", 
						PAG_Explorer::getDimensionLabel($dim_names[1][2], $k3));
					$line_href = array ('','','');
					$level1_first = false;
					$level2_first = false;
					
					foreach (PAG_Explorer::groupBy($dim_names[0][0], $items3, null, true) as $k4 => $items4) {
						
						if (createHeader) {						
							array_push ($header_values[0], PAG_Explorer::getDimensionLabel ($dim_names[0][0], $k4)); //  (($dim_names[0][0]=="none") || ($dim_names[0][0]==null)) ? "" : $k4);
							$count_k4 = 0;
						}
						
						foreach (PAG_Explorer::groupBy($dim_names[0][1], $items4, $k4, true) as $k5 => $items5) {

							if (createHeader) {
								array_push ($header_values[1], PAG_Explorer::getDimensionLabel ($dim_names[0][1], $k5)); //  (($dim_names[0][1]=="none") || ($dim_names[0][1]==null)) ? "" : $k5);
								$count_k5 = 0;
							}
								
							foreach (PAG_Explorer::groupBy($dim_names[0][2], $items5, $k5, true) as $k6 => $items6) {
									
								$res[$k1][$k2][$k3][$k4][$k5][$k6] = $items6;

								$href = '';
								foreach ($items6 as $i) { $href .= ($href=="") ? $i->id : "," . $i->id; }
								
								array_push ($line, count($items6));
								array_push ($line_href, $href);
								
								if (createHeader) {
									array_push ($header_values[2], PAG_Explorer::getDimensionLabel ($dim_names[0][2], $k6)); //  (($dim_names[0][2]=="none") || ($dim_names[0][2]==null)) ? "" : $k6);
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
		
	}
	
	
	public static function load_items_javascript () {
		
		?>
			<script type="text/javascript" >

			jQuery(document).ready(function($) {
				load_items();
			});

			function load_items() {

				console.log ("Load Items", jQuery("#drag_dimx").children()[0]);
				
				var data = {
						'action': 'load_items',
						'dimx0': (jQuery("#drag_dimx").children().size()>1) ? jQuery("#drag_dimx").children()[0].value : 'none', 
						'dimx1': (jQuery("#drag_dimx").children().size()>2) ? jQuery("#drag_dimx").children()[1].value : 'none', 
						'dimx2': (jQuery("#drag_dimx").children().size()>3) ? jQuery("#drag_dimx").children()[2].value : 'none', 
						'dimy0': (jQuery("#drag_dimy").children().size()>1) ? jQuery("#drag_dimy").children()[0].value : 'none', 
						'dimy1': (jQuery("#drag_dimy").children().size()>2) ? jQuery("#drag_dimy").children()[1].value : 'none', 
						'dimy2': (jQuery("#drag_dimy").children().size()>3) ? jQuery("#drag_dimy").children()[2].value : 'none', 
						'valtype': 'number' // jQuery("#values #val").val()
					};
	
				console.log ("Call AJAX");			
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					console.log("Response=", response);
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
	
	
	public static function createPage () {
		
		
// 		add_action( 'admin_footer', array ('PAG_Explorer', 'load_items_callback') ); // Write our JS below here
		add_action( 'admin_footer', array ('PAG_Explorer', 'load_items_javascript') ); // Write our JS below here
		?>
	
	
		
		<script>
			function allowDrop(ev) {
		    	ev.preventDefault();
			}

			
			function drag(ev) {
			    ev.dataTransfer.setData("text", ev.target.id);
			}
			
			function drop(ev) {
			    var data = ev.dataTransfer.getData("text");
				ev.preventDefault();

				if (ev.target.id == "drag_home") {	// move back to home

					ev.target.appendChild (document.getElementById(data));

					// If Topic1 is moved back to home --> move Topic2 automatically back home 
					if (data == "drag_topic1") {
						ev.target.appendChild (document.getElementById("drag_topic2"));
						document.getElementById("drag_topic2").style.display = 'inline'; 
					}
					
				} else { 
					if (ev.target.nodeName == "BUTTON") {	// dragged on button --> add to surrounding div
						ev.target.parentNode.appendChild (document.getElementById(data));
					} else {	// dragged on "empty field"

						// Check: Topics on Y axis only
						if ((ev.target.parentNode.id == "drag_dimx") && ((data == "drag_topic1") || (data == "drag_topic2"))) return;

						// Check: Topic2 only if Topic1 is direct predecessor
						if ((data == "drag_topic2") && ((ev.target.previousSibling == null) || (ev.target.previousSibling.id != "drag_topic1"))) return; 
						
					    ev.target.parentNode.insertBefore (document.getElementById(data), ev.target);
					}
				}

				// ensure vertical / horizontal list of categories
				document.getElementById(data).style.display = (document.getElementById(data).parentNode.id == 'drag_dimy') ? 'block' : 'inline'; 

				// Up to 3 categories only --> hide "empty field" otherwise to avoid additional drops
				document.getElementById("drag_place_dimx").style.display = (document.getElementById("drag_dimx").children.length > 3) ? 'none' : 'inline';
				document.getElementById("drag_place_dimy").style.display = (document.getElementById("drag_dimy").children.length > 3) ? 'none' : 'block';

				load_items();

// 				if (data != "drag_topic2") {
				    
// 				    ev.preventDefault();
// 				    ev.target.parentNode.insertBefore (ev.target.cloneNode(true), ev.target);

				    
// 				} else {
// 					// check if previous category is topic1
// 					if (ev.target.previousSibling != null) {
// 						if (ev.target.previousSibling.id == "drag_topic1") {
// 						    ev.preventDefault();
// 						    ev.target.parentNode.insertBefore (ev.target.cloneNode(true), ev.target);
// 						    ev.target.parentNode.insertBefore (document.getElementById(data), ev.target);

// 						}
// 					}
// 				}
			}
		</script>

	<div class="wrap">
		
		
		<?php 
			$buttons = array (
				"type" 			=> "<button value='type' 		style='margin:0.2em;' id='drag_itemtype' 	draggable='true' ondragstart='drag(event)'> Item Typ </button>",
				"dim"			=> "<button value='dim' 		style='margin:0.2em;' id='drag_dimension' 	draggable='true' ondragstart='drag(event)'> Dimension </button>", 
				"level"			=> "<button value='level' 		style='margin:0.2em;' id='drag_level' 		draggable='true' ondragstart='drag(event)'> Anforderungsstufe </button>", 
				"topic1"		=> "<button value='topic1' 		style='margin:0.2em;' id='drag_topic1' 		draggable='true' ondragstart='drag(event)'> Topic Level 1</button>", 
				"topic2"		=> "<button value='topic2' 		style='margin:0.2em;' id='drag_topic2' 		draggable='true' ondragstart='drag(event)'> Topic Level 2</button>",
				"difficulty"	=> "<button value='difficulty' 	style='margin:0.2em;' id='drag_difficulty' 	draggable='true' ondragstart='drag(event)'> Schwierigkeitsgrad</button>"
					);
		
		?>
		
		<h1>Item Explorer</h1><br/>

		<div>
			<div style='min-height:2em; border-style: dashed; border-width:1px; border-color:#AAAAAA; padding:0.5em;' id="drag_home" ondrop="drop(event)" ondragover="allowDrop(event)">
				<?php 
					foreach ($buttons as $name => $html) {
						if (isset($_SESSION["dim_names"])) {
							if (in_array($name, $_SESSION["dim_names"][0]) || in_array($name, $_SESSION["dim_names"][1])) continue;	
						}
						print ($html);
					}
				?>
						
			</div>
		</div>
		<hr/>
		
		
	
		<table>
			<tr>
				<td></td>
				<td>
					<div id='drag_dimx'>
						<?php 
							$count = 0;
							if (isset($_SESSION["dim_names"])) {
								foreach ($_SESSION["dim_names"][0] as $btn) {
									foreach ($buttons as $name => $html) {
										if ($btn==$name) {
											$count++;
											print ($html);
										}
									}
								}
							}
						?>
						<div id='drag_place_dimx' style='margin:0.2em; padding:0; display:<?php print (($count<3)?"inline":"none"); ?>; border-style: dashed; border-color:#AAAAAA; border-width:1px; ' ondrop="drop(event)" ondragover="allowDrop(event)">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
					</div>
				</td>
			</tr>
			
			<tr>
				<td valign='top'>
					<div id='drag_dimy'>
						<?php 
							$count = 0;
							if (isset($_SESSION["dim_names"])) {
								foreach ($_SESSION["dim_names"][1] as $btn) {
									foreach ($buttons as $name => $html) {
										if ($btn==$name) {
											$count++;
											print ($html);
										}
									}
								}
							}
						?>
						<div id='drag_place_dimy' style='margin:0.2em; padding:0; display:<?php print (($count<3)?"block":"none"); ?>; border-style: dashed; border-color:#AAAAAA; border-width:1px; width:10em' ondrop="drop(event)" ondragover="allowDrop(event)">&nbsp;</div>
					</div>
				</td>
				<td>
					<table id="itemtable" border="1" class="wp-list-table widefat fixed striped posts"></table>
				</td>
			</tr>
		</table>
		
		</div>
		
	
	<?php 
		
	
	}
	


}

	
	
	
?>