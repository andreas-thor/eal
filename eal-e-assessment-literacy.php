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
require_once 'includes/class.CPT_ItemBasket.php';
require_once 'includes/class.CPT_ItemSC.php';
require_once 'includes/class.CPT_ItemMC.php';
require_once 'includes/class.CPT_LearnOut.php';

require_once 'includes/class.CPT_Review.php';

require_once 'includes/class.PAG_Metadata.php';
require_once 'includes/class.PAG_Basket.php';
require_once 'includes/class.PAG_Explorer.php';
require_once 'includes/class.PAG_Generator.php';
require_once 'includes/class.PAG_Item.Import.php';

require_once 'includes/class.CLA_RoleTaxonomy.php';


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



function example_add_dashboard_widgets() {

	global $wp_meta_boxes;
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
	
	wp_add_dashboard_widget(
                 'example_dashboard_widget',         // Widget slug.
                 'Example Dashboard Widget',         // Title.
                 'example_dashboard_widget_function' // Display function.
        );	
}
add_action( 'wp_dashboard_setup', 'example_add_dashboard_widgets' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function example_dashboard_widget_function() {

	// Display whatever it is you want to show.
	echo "Hello World, I'm a great Dashboard Widget";
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
//  	remove_menu_page( 'options-general.php' );        //Settings	

// 	add_menu_page('eal_page_items', 'Items', 'administrator', 'eal_page_items', 'create_eal_page_items', '', 1);
 	
//  	add_menu_page('My Page Title', 'My Menu Title', 'manage_options', 'my-menu', 'my_menu_output' );
 	
 	
//  	add_menu_page('My Custom Page', 'My Custom Page', 'manage_options', 'my-top-level-slug');
//  	add_submenu_page( 'my-top-level-slug', 'My Custom Page', 'My Custom Page', 'manage_options', 'my-top-level-slug');

 	
 	global $menu, $submenu;
 	
 	
 	$domain = RoleTaxonomy::getCurrentDomain();
 	if ($domain["name"]!="") {
 	
	 	add_menu_page('eal_page_items', 'Items', 'edit_posts', 'edit.php?post_type=item', '' /*'create_eal_page_items'*/, 'dashicons-format-aside', 31);
	 	add_submenu_page( 'edit.php?post_type=item', 'All Items', '<div class="dashicons-before dashicons-format-aside" style="display:inline">&nbsp;</div> All Items', 'edit_posts', 'edit.php?post_type=item');
	 	add_submenu_page( 'edit.php?post_type=item', 'Single Choice', '<div class="dashicons-before dashicons-marker" style="display:inline">&nbsp;</div> Single Choice', 'edit_posts', 'edit.php?post_type=itemsc');
	 	add_submenu_page( 'edit.php?post_type=item', 'Multiple Choice', '<div class="dashicons-before dashicons-forms" style="display:inline">&nbsp;</div> Multiple Choice', 'edit_posts', 'edit.php?post_type=itemmc');
	 	add_submenu_page( 'edit.php?post_type=item', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import-items', array ('PAG_Item_Import', 'createPage'));
	 	add_submenu_page( 'edit.php?post_type=item', 'Reviews', '<div class="dashicons-before dashicons-admin-comments" style="display:inline">&nbsp;</div> Reviews', 'edit_posts', 'edit.php?post_type=review');
	 	 
	 	/* TODO: first sub menu should open menu */
// 	 	$menuslug = 'metadata';
	 	$taxurl = 'edit-tags.php?taxonomy=' . $domain["name"]; 
	 	
// 	 	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', $menuslug, '' /* array ('PAG_Metadata', 'createTable')*/, 'dashicons-tag', 32);
// 		add_submenu_page( $menuslug, $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', $taxurl);
// 	 	add_submenu_page( $menuslug, 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
// 	 	add_submenu_page( $menuslug, 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');
	 	
	 	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', 'metadata', '' /* array ('PAG_Metadata', 'createTable')*/, 'dashicons-tag', 32);
	 	add_submenu_page( 'metadata', $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', $taxurl);
	 	add_submenu_page( 'metadata', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
	 	add_submenu_page( 'metadata', 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');
	 	 
	 	
	 	// 	 	add_submenu_page( 'menu_metadata', 'Taxonomy', '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> Taxonomy', 'edit_posts', 'eal_page_taxonomies', array ('PAG_Metadata', 'createTable'));
	 		 
 	}
 	
 	
 	
// 	add_menu_page('eal_page_taxonomies', 'Taxonomies', 'edit_others_posts', 'eal_page_taxonomies', '', 'dashicons-networking', 30);
//    	add_submenu_page( 'eal_page_taxonomies', 'Topic', 'Topic', 'edit_others_posts', 'edit-tags.php?taxonomy=topic');
//    	add_submenu_page( 'eal_page_taxonomies', 'Import', 'Import', 'edit_others_posts', 'import-topics', 'WPCB_import_topics');
   	
   	
 	
//  	if ($_REQUEST['action'] == 'removefrombasket') {
//  		$b_old = get_user_meta(get_current_user_id(), 'itembasket', true);
//  		if ($_REQUEST['itemid']!=null) {
//  			$b_new = array_diff ($b_old, [$_REQUEST['itemid']]);
//  		}
//  		if ($_REQUEST['itemids']!=null) {
//  			$b_new = array_diff ($b_old, $_REQUEST['itemids']);
//  		}
//  		$x = update_user_meta( get_current_user_id(), 'itembasket', $b_new, $b_old );
//  	}
 	
        	
    
    $c = count(get_user_meta(get_current_user_id(), 'itembasket', true));
    add_menu_page('eal_page_basket', 'Item Basket <span class="update-plugins count-1"><span class="plugin-count">' . $c . '</span></span>', 'edit_posts', 'edit.php?post_type=itembasket', '' /*'create_eal_page_items'*/, 'dashicons-format-aside', 34);
    add_submenu_page( 'edit.php?post_type=itembasket', 'Table', '<div class="dashicons-before dashicons-format-aside" style="display:inline">&nbsp;</div> Table', 'edit_posts', 'edit.php?post_type=itembasket');
    add_submenu_page( 'edit.php?post_type=itembasket', 'Explorer', '<div class="dashicons-before dashicons-chart-pie" style="display:inline">&nbsp;</div> Explorer', 'edit_posts', 'ist-blueprint', array ('PAG_Explorer', 'createPage'));
    add_submenu_page( 'edit.php?post_type=itembasket', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_posts', 'view', array ('PAG_Basket', 'createPageView'));
    add_submenu_page( 'edit.php?post_type=itembasket', 'Generator', '<div class="dashicons-before dashicons-admin-generic" style="display:inline">&nbsp;</div> Generator', 'edit_posts', 'generator', array ('PAG_Generator', 'createPage'));
    
    
    
    
}




// register AJAX-PHP-function
add_action( 'wp_ajax_load_items', array ('PAG_Explorer', 'load_items_callback') );

// add_action('admin_footer-edit.php', 'custom_bulk_admin_footer');

// function custom_bulk_admin_footer() {

// 	global $post_type;

// 	if (($post_type == 'itemsc') || ($post_type == 'itemmc') || ($post_type == 'learnout')) {
// 		?>
//      <script type="text/javascript">
//       jQuery(document).ready(function() {
//        jQuery('<option>').val('export').text('<?php _e('Export')?>').appendTo("select[name='action']");
        //jQuery('<option>').val('export').text('<?php _e('Export')?>').appendTo("select[name='action2']");
//       });
    //</script>
    //<?php
//   }
// }










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
			
			<h2>Upload Terms</h2>
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

	
// 	$foo = menu_page_url("edit.php?post_type=item", 0);
// 	wp_redirect($foo);
// 	exit;
	
// 	$html  = '
// 		<div class="wrap">
// 			<form action="options.php" method="post" name="options">
// 				<h1>All Items
// 					<a class="add-new-h2" href="edit.php?post_type=itemsc">All Single Choice</a>
// 					<a class="add-new-h2" href="edit.php?post_type=itemmc">All Multiple Choice</a>
// 					<a class="add-new-h2" href="admin.php?page=import-items">Import Items</a>
// 				</h1>
// 			</form>
// 		</div>';
			
// 	echo $html;
}



function create_eal_page_taxonomies () {
	
}

/**
 * Add custom post types
 * - eal_item_mc1n: Multiple Choice 1 out of N
 * - eal_item_mcnm: Multiple Choice M out of N
 */
 

register_activation_hook( __FILE__, array ('eal_item', 'createTables') );
register_activation_hook( __FILE__, array ('eal_itemsc', 'createTables') );
register_activation_hook( __FILE__, array ('eal_itemmc', 'createTables') );
register_activation_hook( __FILE__, array ('eal_item_review', 'createTables') );
register_activation_hook( __FILE__, array ('eal_learnout', 'createTables') );




add_action( 'init', 'create_eal_items' );

//add_action ('init', array('ItemMC', 'init'));






function create_eal_items() {
	
	if (!session_id()) session_start();
	
	(new CPT_Item())->init();
	(new CPT_ItemBasket())->init();
	(new CPT_ItemSC())->init();
	(new CPT_ItemMC())->init();
	(new CPT_Review())->init();
	(new CPT_LearnOut())->init();
	
	RoleTaxonomy::init();
	
	
	
	
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


// add_action( 'init', 'create_eal_taxonomies', 0 );
// function create_eal_taxonomies () {
	
// 	// Add new taxonomy, make it hierarchical (like categories)
// 	$labels = array (
// 			'name' => _x ( 'Topics', 'taxonomy general name' ),
// 			'singular_name' => _x ( 'Topic', 'taxonomy singular name' ),
// 			'search_items' => __ ( 'Search Topics' ),
// 			'all_items' => __ ( 'All Topics' ),
// 			'parent_item' => __ ( 'Parent Topic' ),
// 			'parent_item_colon' => __ ( 'Parent Topic:' ),
// 			'edit_item' => __ ( 'Edit Topic' ),
// 			'update_item' => __ ( 'Update Topic' ),
// 			'add_new_item' => __ ( 'Add New Topic' ),
// 			'new_item_name' => __ ( 'New Topic Name' ),
// 			'menu_name' => __ ( 'Topic' ) 
// 	);
	
// 	$args = array (
// 			'hierarchical' => true,
// 			'labels' => $labels,
// 			'show_ui' => true,
// 			'show_admin_column' => true,
// 			'query_var' => true,
// 			'show_in_menu'    => true,
// // 			'rewrite' => array ( 'slug' => 'topic' ), 
// 			'public' => false,
// 			'rewrite' => false
// 	);
	
// 	register_taxonomy ( 'topic', array ('eal_itemsc', 'eal_itemmc') , $args );		
// }


function my_custom_login_logo()
{
	echo '<style  type="text/css"> h1 a {  background-image:url(' . plugin_dir_url( __FILE__ ) . 'EAssLit.png)  !important; } </style>';
}
add_action('login_head',  'my_custom_login_logo');

function custom_admin_logo()
{
	echo '<style type="text/css">#header-logo { background-image: url(' . plugin_dir_url( __FILE__ ) . 'EAssLit.png) !important; }</style>';
}
add_action('admin_head', 'custom_admin_logo');


add_filter( 'admin_footer_text', '__return_empty_string', 11 );
add_filter( 'update_footer', '__return_empty_string', 11 );

function my_custom_admin_head() {
	echo '<style>[for="wp_welcome_panel-hide"] {display: none !important;}</style>';
}
add_action( 'admin_head', 'my_custom_admin_head' );


function example_remove_dashboard_widgets()
{
	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	global $wp_meta_boxes;
	 
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
}
add_action('wp_dashboard_setup', 'example_remove_dashboard_widgets' );

add_action( 'admin_bar_menu', 'my_new_toolbar_item', 999 );

function my_new_toolbar_item( $wp_admin_bar ) {
	$args = array(
		'id'    => 'eal_logo',
		'title' => '<div style="width:10em"><a href="' . site_url() . '/wp-admin/"><img style="display:block; margin-top:1em; margin-left:-1em; width:11em"  src="' . plugin_dir_url( __FILE__ ) . 'EAssLit_small.png"></a></div>'
		
	);
	$wp_admin_bar->add_node( $args );
	
// 	$wp_admin_bar->remove_menu ('user-actions');
	$wp_admin_bar->remove_menu ('updates');
	$wp_admin_bar->remove_menu ('comments');
	$wp_admin_bar->remove_menu ('new-content');
	$wp_admin_bar->remove_menu ('wp-logo');
	$wp_admin_bar->remove_menu ('site-name');
	
	$title  = "<div>" . RoleTaxonomy::getCurrentDomain()["label"];
	$title .= (RoleTaxonomy::getCurrentRole()=="editor") ? '<div class="dashicons-before dashicons-admin-users" style="display:inline">&nbsp;</div>' : '';
	$title .= "</div>";
	$wp_admin_bar->add_menu (array ("id" => "eal_currentRole", "title" => $title));
}


add_action('admin_head', 'myposttype_admin_css');

function myposttype_admin_css() {

	/* adjust h1 label on table sites */
	
	?> <script type="text/javascript">	jQuery(document).ready( function($) { <?php 

	if (!isset($_REQUEST['page'])) {
		switch ($_REQUEST['post_type']) {
			case 'item': ?> jQuery(jQuery(".wrap h1")[0]).replaceWith ('<h1>All Items <a href="http://localhost/wordpress/wp-admin/post-new.php?post_type=itemsc" class="page-title-action">Add Single Choice</a><a href="http://localhost/wordpress/wp-admin/post-new.php?post_type=itemmc" class="page-title-action">Add Multiple Choice</a></h1>'); <?php break;  	
			case 'itembasket': ?> jQuery(jQuery(".wrap h1")[0]).replaceWith ('<h1>Item Basket</h1>'); <?php break;			
			case 'review': ?> jQuery(jQuery(".wrap h1")[0]).replaceWith ('<h1>All Reviews</h1>'); <?php break;			
		}
	}
	
	?> }); </script> <?php
	
}

add_action ('show_user_profile', array ('RoleTaxonomy', 'showCurrentRole'));
add_action ('edit_user_profile', array ('RoleTaxonomy', 'showCurrentRole'));
add_action( 'profile_update', array ('RoleTaxonomy', 'setCurrentRole'));


?>