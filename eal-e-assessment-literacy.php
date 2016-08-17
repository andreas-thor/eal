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

require_once 'includes/class.CPT_Item_Table.php';

require_once 'includes/class.CPT_Item.php';
require_once 'includes/class.CPT_ItemSC.php';
require_once 'includes/class.CPT_ItemMC.php';

require_once 'includes/class.CPT_Item.Review.php';
require_once 'includes/class.CPT_ItemSC.Review.php';
require_once 'includes/class.CPT_ItemMC.Review.php';

require_once 'includes/class.CPT_LearnOut.php';
require_once 'includes/class.PAG_Basket.php';
require_once 'includes/class.ALG_Item_Pool.php';


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



function wpdocs_enqueue_custom() {
// 	wp_enqueue_script( 'dashboard-script', plugins_url( '/js/dashboard_script.js', __FILE__ ) , array( 'jquery','jquery-ui-core','jquery-ui-slider' ), '1.0', true );
  	wp_enqueue_script( 'jquery' );
  	wp_enqueue_script( 'jquery-ui-core' );
// 	wp_enqueue_script( 'jquery-ui-widget' );
	
  	wp_enqueue_script( 'jquery-ui-slider' );
}
add_action( 'admin_enqueue_scripts', 'wpdocs_enqueue_custom' );


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
//  	remove_menu_page( 'options-general.php' );        //Settings	

// 	add_menu_page('eal_page_items', 'Items', 'administrator', 'eal_page_items', 'create_eal_page_items', '', 1);
 	
//  	add_menu_page('My Page Title', 'My Menu Title', 'manage_options', 'my-menu', 'my_menu_output' );
 	
 	
 	
 	add_menu_page('eal_page_items', 'Items', 'edit_others_posts', 'eal_page_items', 'create_eal_page_items', 'dashicons-admin-post', 1);
//  	add_submenu_page( 'eal_page_items', 'All Items', '<div class="dashicons-before dashicons-cart" style="display:inline">&nbsp;</div> All Items', 'edit_others_posts', 'eal_page_items' );

// external images: add_submenu_page( 'eal_page_items', 'Single Choice', '<img style="height:1em" src="' . plugins_url('img/single-choice.png', __FILE__) . '"/> Single Choice', 'edit_others_posts', 'edit.php?post_type=itemsc');
 	add_submenu_page( 'eal_page_items', 'Single Choice', '<div class="dashicons-before dashicons-marker" style="display:inline">&nbsp;</div> Single Choice', 'edit_others_posts', 'edit.php?post_type=itemsc');
 	add_submenu_page( 'eal_page_items', 'Multiple Choice', '<div class="dashicons-before dashicons-forms" style="display:inline">&nbsp;</div> Multiple Choice', 'edit_others_posts', 'edit.php?post_type=itemmc');
 	add_submenu_page( 'eal_page_items', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_others_posts', 'import-items', array ('CPT_Item', 'import_items'));

 	
	add_menu_page('eal_page_taxonomies', 'Taxonomies', 'edit_others_posts', 'eal_page_taxonomies', '', 'dashicons-networking', 30);
   	add_submenu_page( 'eal_page_taxonomies', 'Topic', 'Topic', 'edit_others_posts', 'edit-tags.php?taxonomy=topic');
   	add_submenu_page( 'eal_page_taxonomies', 'Import', 'Import', 'edit_others_posts', 'import-topics', 'WPCB_import_topics');
   	
   	
    	
    if ($_REQUEST['action'] == 'removefrombasket') {
    	$b_old = get_user_meta(get_current_user_id(), 'itembasket', true);
        	if ($_REQUEST['itemid']!=null) {
    		$b_new = array_diff ($b_old, [$_REQUEST['itemid']]);
    	}
        if ($_REQUEST['itemids']!=null) {
    		$b_new = array_diff ($b_old, $_REQUEST['itemids']);
    	}
    	$x = update_user_meta( get_current_user_id(), 'itembasket', $b_new, $b_old );
    }    	
        	
    
    $c = count(get_user_meta(get_current_user_id(), 'itembasket', true));
    add_menu_page('eal_page_itembasket', 'Item Basket <span class="update-plugins count-1"><span class="plugin-count">' . $c . '</span></span>', 'administrator', 'eal_page_itembasket', array ('PAG_Basket', 'page_itembasket'), 'dashicons-cart', 31);
    add_submenu_page( 'eal_page_itembasket', 'Table', '<div class="dashicons-before dashicons-list-view" style="display:inline">&nbsp;</div> Table', 'edit_others_posts', 'eal_page_itembasket', array ('PAG_Basket', 'page_itembasket'));
    add_submenu_page( 'eal_page_itembasket', 'Explorer', '<div class="dashicons-before dashicons-chart-pie" style="display:inline">&nbsp;</div> Explorer', 'edit_others_posts', 'ist-blueprint', array ('PAG_Basket', 'page_ist_blueprint'));
    add_submenu_page( 'eal_page_itembasket', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_others_posts', 'view', array ('PAG_Basket', 'page_view'));
    add_submenu_page( 'eal_page_itembasket', 'Generator', '<div class="dashicons-before dashicons-admin-generic" style="display:inline">&nbsp;</div> Generator', 'edit_others_posts', 'generator', array ('PAG_Basket', 'page_generator'));
    
 
    
    
}


// register AJAX-PHP-function
add_action( 'wp_ajax_load_items', array ('PAG_Basket', 'load_items_callback') );

add_action('admin_footer-edit.php', 'custom_bulk_admin_footer');

function custom_bulk_admin_footer() {

	global $post_type;

	if (($post_type == 'itemsc') || ($post_type == 'itemmc') || ($post_type == 'learnout')) {
		?>
    <script type="text/javascript">
      jQuery(document).ready(function() {
        jQuery('<option>').val('export').text('<?php _e('Export')?>').appendTo("select[name='action']");
        jQuery('<option>').val('export').text('<?php _e('Export')?>').appendTo("select[name='action2']");
      });
    </script>
    <?php
  }
}


add_action('load-edit.php', 'custom_bulk_action');

function custom_bulk_action() {

	global $wpdb;
	$wp_list_table = _get_list_table('WP_Posts_List_Table');
	
	
	if ($wp_list_table->current_action() == 'view') {
		$_REQUEST['page'] = 'view';
	}
	
	if ($wp_list_table->current_action() == 'export') {

		$postids = $_REQUEST['post'];
		
		if (($_REQUEST['post_type'] == 'learnout')) {

			// get all items of the learning outcomes
			$postids = array ();
			foreach (array('itemsc', 'itemmc') as $itemtype) {
				$postids=array_merge ($postids, $wpdb->get_col( "
						SELECT      P.id
						FROM        {$wpdb->prefix}eal_{$itemtype} E
						JOIN		{$wpdb->prefix}posts P
						ON			(P.ID = E.ID)
						WHERE		P.post_parent = 0
						AND			E.learnout_id IN (" . join(", ", $_REQUEST['post']) . ")"
				));
			}
		}
		
		$b_old = get_user_meta(get_current_user_id(), 'itembasket', true);
		if ($b_old == null) $b_old = array();
		$b_new = array_unique (array_merge ($b_old, $postids));
		$x = update_user_meta( get_current_user_id(), 'itembasket', $b_new);
		
		
	}
	
	


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
				<h1>All Items
					<a class="add-new-h2" href="edit.php?post_type=itemsc">All Single Choice</a>
					<a class="add-new-h2" href="edit.php?post_type=itemmc">All Multiple Choice</a>
					<a class="add-new-h2" href="admin.php?page=import-items">Import Items</a>
				</h1>
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







?>