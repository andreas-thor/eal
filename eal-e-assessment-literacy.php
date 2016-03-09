<?php
/*
 Plugin Name: EAL // E-Assessment Literacy
 Plugin URI: https://github.com/andreas-thor/eal
 Description: Plugin for E-Assessment Literacy. It delivers several custom post types (items, reviews) and setting pages.
 Version: 1.0
 Author: Andreas Thor
 EMail: dr.andreas.thor@googlemail.com
 */


/*
 * Definition of custom post types
 * All ids must be less than 20 characters!
 */
 

// TODO: disable post revisions
// TODO: sort by FW/KW/PW
// TODO: add another properties to list?
// TODO: expand quickedit?
// TODO: Bulk operations (export)


// include_once 'includes/eal_item_sc.php';
// include_once 'includes/eal_item_mc.php';


require_once 'includes/class.CPT_Item.php';
require_once 'includes/class.CPT_ItemSC.php';
require_once 'includes/class.CPT_ItemMC.php';

require_once 'includes/class.CPT_Item.Review.php';
require_once 'includes/class.CPT_ItemSC.Review.php';
require_once 'includes/class.CPT_ItemMC.Review.php';

require_once 'includes/class.CPT_LearnOut.php';


// $GLOBALS["eal_itemtypes"] = [
// 		'eal_item_sc' => 'Single Choice',
// 		'eal_item_mc' => 'Multiple Choice'
// ];



/**
 * Add menu entries 
 * - items
 * - review
 */


// add_action('taxonomy_edit_form', 'foo_render_extra_fields');
// function foo_render_extra_fields(){
// 	$term_id = $_GET['tag_ID'];
// 	$term = get_term_by('id', $term_id, 'taxonomy');
// 	$meta = get_option("taxonomy_{$term_id}");
// 	//Insert HTML and form elements here
// }

// add_action('edited_taxonomy', 'bar_save_extra_fields', 10, 2);
// function bar_save_extra_fields($term_id){
// 	$form_field_1 = $_REQUEST['field-name-1'];
// 	$form_field_2 = $_REQUEST['field-name-2'];
// 	$meta['key_value_1'] = $form_field_1;
// 	$meta['key_value_2'] = $form_field_2;
// 	update_option("taxonomy_{$term_id}", $meta);
// }


// add_filter( "manage_edit-tags_columns", "column_header_function" ) ;

// add_action( "manage_topic_custom_column",  "populate_rows_function", 10, 3  );

function column_header_function () {
	
}



add_action ('admin_menu', 'set_eal_admin_menu_entries');

function set_eal_admin_menu_entries () {
	
	
	
	
	
	/* remove standard menu entries */
	
 	remove_menu_page( 'index.php' );                  //Dashboard
 	remove_menu_page( 'edit.php' );                   //Posts
 	remove_menu_page( 'upload.php' );                 //Media
 	remove_menu_page( 'edit.php?post_type=page' );    //Pages
 	remove_menu_page( 'edit-comments.php' );          //Comments
 	remove_menu_page( 'themes.php' );                 //Appearance
// 	remove_menu_page( 'plugins.php' );                //Plugins
// 	remove_menu_page( 'users.php' );                  //Users
 	remove_menu_page( 'tools.php' );                  //Tools
 	remove_menu_page( 'options-general.php' );        //Settings	

// 	add_menu_page('eal_page_items', 'Items', 'administrator', 'eal_page_items', 'create_eal_page_items', '', 1);
 	
	add_menu_page('eal_page_taxonomies', 'Taxonomies', 'administrator', 'eal_page_taxonomies', '', '', 30);
 	
 	
    	add_submenu_page( 'eal_page_taxonomies', 'Topic', 'Topic', 'edit_others_posts', 'edit-tags.php?taxonomy=topic');
    	add_submenu_page( 'eal_page_taxonomies', 'Import', 'Import', 'edit_others_posts', 'import-tags', 'WPCB_import_topics');
   	
   	
  	 
}




function WPCB_import_topics () {
	// 	foreach ($GLOBALS["eal_itemtypes"] as $id => $name) {
	// 		$html .= '<a class="add-new-h2" href="post-new.php?post_type=' . $id . '">Add ' . $name . '</a>';
	// 	}
	
	

	
	if ($_POST['action']=='Upload') {
		//	checks for errors and that file is uploaded
		if (($_FILES['uploadedfile']['error'] == UPLOAD_ERR_OK) && (is_uploaded_file($_FILES['uploadedfile']['tmp_name']))) { 
			
	
					$level = -1;
					$lastParent = array (-1 => $_POST['topicroot']);
					foreach (file ($_FILES['uploadedfile']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
						
						$identSize = strlen($line) - strlen(ltrim($line));
						if ($identSize > $level) $level++;
						if ($identSize < $level) $level = max (0, $level-1);

						$x = wp_insert_term( utf8_encode(trim($line)), 'topic', array('parent' => $lastParent[$level-1]) );
						$lastParent[$level] = ($x instanceof WP_Error) ? $x->error_data['term_exists'] : $x['term_id'];
						
						
					}
					
		}
	}
	
	
?>	

		<div class="wrap">
		
			<h1>Topics</h1>
			
			<h2>Upload Topic Terms</h2>
			<form  enctype="multipart/form-data" action="admin.php?page=import-tags" method="post">
				<table class="form-table">
					<tbody>
						<tr class="user-first-name-wrap">
							<th><label>File</label></th>
							<td><input class="menu-name regular-text menu-item-textbox input-with-default-title" name="uploadedfile" type="file" size="30" accept="text/*"></td>
						</tr>
						<tr class="user-first-name-wrap">
							<th><label>Parent</label></th>
							<td>
<?php  
								wp_dropdown_categories(array(
									'show_option_none' =>  __("None"),
									'option_none_value' => 0, 
									'taxonomy'        =>  'topic',
									'name'            =>  'topicroot',
									'value_field'	  =>  'id',
									'orderby'         =>  'name',
									'selected'        =>  '',
									'hierarchical'    =>  true,
									'depth'           =>  0,
									'show_count'      =>  false, // Show # listings in parens
									'hide_empty'      =>  false, // Don't show businesses w/o listings
								));
?>
							</td>
						</tr>
						<tr>
							<th>
								<input type="submit" name="action" class="button button-primary" value="Upload">
							</th>
							<td></td>
						</tr>
					</tbody>
				</table>
			</form>
			
			
			<h2>Download Topic Terms</h2>
			<form action="options.php" method="post" name="options">
				<table class="form-table">
					<tbody>
						<tr class="user-first-name-wrap">
							<th><label>Parent</label></th>
							<td>
<?php  
								wp_dropdown_categories(array(
									'show_option_none' =>  __("None"),
									'option_none_value' => 0, 
									'taxonomy'        =>  'topic',
									'name'            =>  'topicroot',
									'value_field'	  =>  'id',
									'orderby'         =>  'name',
									'selected'        =>  '',
									'hierarchical'    =>  true,
									'depth'           =>  0,
									'show_count'      =>  false, // Show # listings in parens
									'hide_empty'      =>  false, // Don't show businesses w/o listings
								));
?>
								
								
							</td>
						</tr>
						<tr>
							<th><input type="submit" name="action" class="button button-primary" value="Download"></th>
							<td></td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>	
			

<?php 
	
}



// highlight the proper top level menu
// add_action('parent_file', 'set_eal_taxonomies_menu_correction');
// function set_eal_taxonomies_menu_correction($parent_file) {
// 	global $current_screen;
// 	$taxonomy = $current_screen->taxonomy;
// 	if ($taxonomy == 'topic')
// 		$parent_file = 'eal_page_taxonomies';
// 	return $parent_file;
// }


/**
 * Page "Items" has
 * - Buttons to add new items of different types
 * - TODO: List of all items incl. bulk operations
 */

function create_eal_page_items () {
	
	$html  = '
		<div class="wrap">
			<form action="options.php" method="post" name="options">
				<h2>Items';
	
	foreach ($GLOBALS["eal_itemtypes"] as $id => $name) {
		$html .= '<a class="add-new-h2" href="post-new.php?post_type=' . $id . '">Add ' . $name . '</a>';
	}
	
	$html.= '	</h2>
			</form>
		</div>';
			
	echo $html;
}



function create_eal_page_taxonomies () {
	
}

/**
 * Add custom post types
 * - eal_item_mc1n: Multiple Choice 1 out of N
 * - eal_item_mcnm: Multiple Choice M out of N
 */
 

register_activation_hook( __FILE__, array ('eal_itemsc', 'createTables') );
register_activation_hook( __FILE__, array ('eal_itemmc', 'createTables') );
register_activation_hook( __FILE__, array ('eal_itemsc_review', 'createTables') );
register_activation_hook( __FILE__, array ('eal_itemmc_review', 'createTables') );
register_activation_hook( __FILE__, array ('eal_learnout', 'createTables') );


add_action( 'init', 'create_eal_items' );

//add_action ('init', array('ItemMC', 'init'));






function create_eal_items() {
	
	(new CPT_ItemSC())->init();
	(new CPT_ItemMC())->init();
	(new CPT_ItemSC_Review())->init();
	(new CPT_ItemMC_Review())->init();
	(new CPT_LearnOut())->init();
	
	// 	CPT_ItemSC::init();
// 	CPT_ItemMC::init();
// 	CPT_Item_Review::CPT_init();
	
	
// 		$book = new CustomPostType( 'Book' );
// 		$book->add_taxonomy( 'xas', array ('hierarchical' => true) );
// 		$book->add_taxonomy( 'author' );
//
// 		$book->add_meta_box(
// 				'Book Info',
// 				array(
// 						'Year' => 'text',
// 						'Genre' => 'text'
// 				),
// 				'normal',
// 				'default',
// 				array ('ItemMC', 'loadX')
// 		);
//
// 		$book->add_meta_box(
// 				'Author Info',
// 				array(
// 						'Name' => 'text',
// 						'Nationality' => 'text',
// 						'Birthday' => 'text'
// 				)
// 		);
		
	
	
	
// 	function loadX ($post, $data) {
// 		global $post;
	
// 		// Nonce field for some validation
// 		wp_nonce_field ( plugin_basename ( __FILE__ ), 'custom_post_type' );
	
// 		// Get all inputs from $data
// 		$custom_fields = $data ['args'] [0];
	
// 		// Get the saved values
// 		$meta = get_post_custom ( $post->ID );
	
// 		// Check the array and loop through it
// 		if (! empty ( $custom_fields )) {
// 			/* Loop through $custom_fields */
// 			foreach ( $custom_fields as $label => $type ) {
// 				$field_id_name = strtolower ( str_replace ( ' ', '_', $data ['id'] ) ) . '_' . strtolower ( str_replace ( ' ', '_', $label ) );
	
// 				echo '<label for="' . $field_id_name . '">' . $label . '</label><input type="text" name="custom_meta[' . $field_id_name . ']" id="' . $field_id_name . '" value="AAA' . $meta [$field_id_name] [0] . '" />';
// 			}
// 		}
// 	}
		
 	
	
	// add_action ('save_post', array ('itemmc', 'CPT_save_post'), 10, 2);
	
	
	
// 	foreach ($GLOBALS["eal_itemtypes"] as $id => $name) {
		
// // 		$currentmenupos++;
// 		register_post_type( $id,
// 				array(
// 						'labels' => array(
// 								'name' => $name,
// 								'singular_name' => $name,
// 								'add_new' => 'Add ' . $name,
// 								'add_new_item' => 'Add New ' . $name,
// 								'edit' => 'Edit',
// 								'edit_item' => 'Edit ' . $name,
// 								'new_item' => 'New ' . $name,
// 								'view' => 'View',
// 								'view_item' => 'View ' . $name,
// 								'search_items' => 'Search ' . $name,
// 								'not_found' => 'No Items found',
// 								'not_found_in_trash' => 'No Items found in Trash',
// 								'parent' => 'Parent Item'
// 						),
	
// 						'public' => true,
// 						'menu_position' => 2,
// 						'supports' => array( 'title'), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
// 						'taxonomies' => array( 'topic' ),
// 						'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
// 						'has_archive' => true,
// 						'show_in_menu'    => true,
// 						'register_meta_box_cb' => $id . '_add_meta_boxes'
// 				)
// 		);
		
// 		add_action ('save_post_' . $id, $id . "_save_post");
		
		
		
// 	}
}


add_action( 'init', 'create_eal_taxonomies', 0 );
function create_eal_taxonomies () {
	
	// Add new taxonomy, make it hierarchical (like categories)
	$labels = array (
			'name' => _x ( 'Topics', 'taxonomy general name' ),
			'singular_name' => _x ( 'Topic', 'taxonomy singular name' ),
			'search_items' => __ ( 'Search Topics' ),
			'all_items' => __ ( 'All Topics' ),
			'parent_item' => __ ( 'Parent Topic' ),
			'parent_item_colon' => __ ( 'Parent Topic:' ),
			'edit_item' => __ ( 'Edit Topic' ),
			'update_item' => __ ( 'Update Topic' ),
			'add_new_item' => __ ( 'Add New Topic' ),
			'new_item_name' => __ ( 'New Topic Name' ),
			'menu_name' => __ ( 'Topic' ) 
	);
	
	$args = array (
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'show_in_menu'    => true,
// 			'rewrite' => array ( 'slug' => 'topic' ), 
			'public' => false,
			'rewrite' => false
	);
	
	register_taxonomy ( 'topic', array ('eal_itemsc', 'eal_itemmc') , $args );		
}


// add_action ('add_meta_boxes_eal_item_mc', 'create_eal_edit_forms');
// function create_eal_edit_forms() {
	
// 	add_meta_box('wpt_events_location', 'Description', 'desc_editor', 'eal_item_sc', 'normal', 'default');

// }






?>