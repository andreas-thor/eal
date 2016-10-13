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


add_action( 'wp_dashboard_setup', 'WPCB_dashboard_setup' );
function WPCB_dashboard_setup() {

	global $wp_meta_boxes;
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
	
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
	
	wp_add_dashboard_widget('dashboard_items', 		'Item Overview', 		'WPCP_dashboard_items' );	
	wp_add_dashboard_widget('dashboard_metadata', 	'Metadata Overview', 	'WPCP_dashboard_metadata' );	
	wp_add_dashboard_widget('dashboard_user', 		'User Overview', 		'WPCP_dashboard_user' );	
}


function WPCP_dashboard_items() {

	$objects = [new CPT_Item(), new CPT_ItemSC(), new CPT_ItemMC(), new CPT_Review()];
	$counts = array();
	foreach ($objects as $object) {
		$object->init();
		array_push($counts, $object->WPCB_count_posts(NULL, $object->type, NULL));
	}
	
	printf ('<table border="0">');
	printf ('<tr><td style="width:11em"><div class="dashicons-before dashicons-format-aside" 	style="display:inline">&nbsp;</div> All Items</td>			<td align="right" style="width:4em"><a href="edit.php?post_type=item">%1$d</a></td>		<td align="right" style="width:10em">&nbsp;(%2$d pending review)</td></tr>', $counts[0]->publish+$counts[0]->pending+$counts[0]->draft, $counts[0]->pending);
	printf ('<tr><td style="width:11em"><div class="dashicons-before dashicons-marker" 			style="display:inline">&nbsp;</div> Single Choice</td>		<td align="right" style="width:4em"><a href="edit.php?post_type=itemsc">%1$d</a></td>	<td align="right" style="width:10em">&nbsp;(%2$d pending review)</td></tr>', $counts[1]->publish+$counts[1]->pending+$counts[1]->draft, $counts[1]->pending);
	printf ('<tr><td style="width:11em"><div class="dashicons-before dashicons-forms" 			style="display:inline">&nbsp;</div> Multiple Choice</td>	<td align="right" style="width:4em"><a href="edit.php?post_type=itemmc">%1$d</a></td>	<td align="right" style="width:10em">&nbsp;(%2$d pending review)</td></tr>', $counts[2]->publish+$counts[2]->pending+$counts[2]->draft, $counts[2]->pending);
	printf ('</table><hr>');
 	printf ('<table border="0">');
	printf ('<tr><td style="width:11em"><div class="dashicons-before dashicons-admin-comments" 	style="display:inline">&nbsp;</div> Reviews</td>			<td align="right" style="width:4em"><a href="edit.php?post_type=review">%1$d</a></td></tr>', $counts[3]->publish+$counts[3]->pending+$counts[3]->draft);
 	printf ('</table>');
}

function WPCP_dashboard_metadata() {

	printf ('<table border="0">');
	$domain = RoleTaxonomy::getCurrentDomain();
	if ($domain["name"]!="") {
		$term_count = wp_count_terms( $domain["name"], array( 'hide_empty' => false ));
		printf ('<tr><td style="width:11em"><div class="dashicons-before dashicons-networking" 		style="display:inline">&nbsp;</div> %1$s</td>			<td align="right" style="width:4em"><a href="edit-tags.php?taxonomy=%2$s">%3$d</a></td></tr>', $domain["label"], $domain["name"], $term_count);
	}
	
	$object = new CPT_LearnOut();
	$object->init();
	$count = $object->WPCB_count_posts(NULL, $object->type, NULL);
	printf ('<tr><td style="width:11em"><div class="dashicons-before dashicons-welcome-learn-more" 	style="display:inline">&nbsp;</div> Learning Outcomes</td>		<td align="right" style="width:4em"><a href="edit.php?post_type=learnout">%1$d</a></td></tr>', $count->publish+$count->pending+$count->draft);
	printf ('</table>');
}


function WPCP_dashboard_user() {
	global $wp_roles;
	
	printf ('<table border="0">');
	$user = wp_get_current_user();
	foreach ($user->roles as $role) {
		printf ('<tr><td><div class="dashicons-before dashicons-admin-users" 	style="display:inline">&nbsp;</div> %2$s %1$s %3$s</td></tr>', 
			$wp_roles->roles[$role]["name"], 
			(get_user_meta ($user->ID, 'current_role', true)==$role) ? "<b>" : "",
			(get_user_meta ($user->ID, 'current_role', true)==$role) ? "</b>" : "");
	}
	printf ('</table>');
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
	 	$taxurlredirect = add_query_arg ('redirect', $taxurl, 'edit.php?post_type=learnout');
	 	
// 	 	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', $menuslug, '' /* array ('PAG_Metadata', 'createTable')*/, 'dashicons-tag', 32);
// 		add_submenu_page( $menuslug, $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', $taxurl);
// 	 	add_submenu_page( $menuslug, 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
// 	 	add_submenu_page( $menuslug, 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');

	 	
	 	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', 'edit.php?post_type=learnout', '' /*'create_eal_page_items'*/, 'dashicons-tag', 32);
	 	add_submenu_page( 'edit.php?post_type=learnout', 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');
	 	add_submenu_page( 'edit.php?post_type=learnout', $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', $taxurl);
	 	add_submenu_page( 'edit.php?post_type=learnout', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
	 		 
	 	
// LEZTE	 	
// 	 	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', 'metadata', '' /* array ('PAG_Metadata', 'createTable')*/, 'dashicons-tag', 32);
// 	 	add_submenu_page( 'metadata', $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', $taxurl);
// 	 	add_submenu_page( 'metadata', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
// 	 	add_submenu_page( 'metadata', 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');
	 	 
 	}
 	
 	

 	
        	
    
    $c = count(get_user_meta(get_current_user_id(), 'itembasket', true));
    add_menu_page('eal_page_basket', 'Item Basket <span class="update-plugins count-1"><span class="plugin-count">' . $c . '</span></span>', 'edit_posts', 'edit.php?post_type=itembasket', '' /*'create_eal_page_items'*/, 'dashicons-cart', 34);
    add_submenu_page( 'edit.php?post_type=itembasket', 'Table', '<div class="dashicons-before dashicons-format-aside" style="display:inline">&nbsp;</div> Table', 'edit_posts', 'edit.php?post_type=itembasket');
    add_submenu_page( 'edit.php?post_type=itembasket', 'Explorer', '<div class="dashicons-before dashicons-chart-pie" style="display:inline">&nbsp;</div> Explorer', 'edit_posts', 'ist-blueprint', array ('PAG_Explorer', 'createPage'));
    add_submenu_page( 'edit.php?post_type=itembasket', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_posts', 'view', array ('PAG_Basket', 'createPageView'));
    add_submenu_page( 'edit.php?post_type=itembasket', 'Generator', '<div class="dashicons-before dashicons-admin-generic" style="display:inline">&nbsp;</div> Generator', 'edit_posts', 'generator', array ('PAG_Generator', 'createPage'));
    
 	
    
    
}




// register AJAX-PHP-function
add_action( 'wp_ajax_load_items', array ('PAG_Explorer', 'load_items_callback') );







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









/**
 * Page "Items" has
 * - Buttons to add new items of different types
 * - TODO: List of all items incl. bulk operations
 */


function create_eal_page_items () { }

function create_eal_page_taxonomies () { }

/**
 * Add custom post types
 * - eal_item_mc1n: Multiple Choice 1 out of N
 * - eal_item_mcnm: Multiple Choice M out of N
 */
 

register_activation_hook( __FILE__, array ('eal_item', 'createTables') );
register_activation_hook( __FILE__, array ('eal_itemsc', 'createTables') );
register_activation_hook( __FILE__, array ('eal_itemmc', 'createTables') );
register_activation_hook( __FILE__, array ('eal_review', 'createTables') );
register_activation_hook( __FILE__, array ('eal_learnout', 'createTables') );




add_action( 'init', 'create_eal_items' );






function create_eal_items() {
	
	if (!session_id()) session_start();
	
	(new CPT_Item())->init();
	(new CPT_ItemBasket())->init();
	(new CPT_ItemSC())->init();
	(new CPT_ItemMC())->init();
	(new CPT_Review())->init();
	(new CPT_LearnOut())->init();
	
	RoleTaxonomy::init();
}




function my_custom_login_logo()
{
	echo '<style  type="text/css"> .login h1 a { width:320px; background-size: 320px; background-position: center middle; background-image:url(' . plugin_dir_url( __FILE__ ) . 'Logo_EAs.LiT.png)  !important; } </style>';
	echo '<style  type="text/css"> p#backtoblog {  display: none; } </style>';
// 	echo '<style  type="text/css"> h1  {  	background-size: 300px 100px; background:url(' . plugin_dir_url( __FILE__ ) . 'Logo_EAs.LiT.png)  !important; } </style>';
}
add_action('login_head',  'my_custom_login_logo');


function my_login_logo_url() {
	return "https://github.com/andreas-thor/eal";
}
add_filter( 'login_headerurl', 'my_login_logo_url' );

function my_login_logo_url_title() {
	return 'EAs.LiT';
}
add_filter( 'login_headertitle', 'my_login_logo_url_title' );

// function custom_admin_logo()
// {
// 	echo '<style type="text/css">#header-logo { background-image: url(' . plugin_dir_url( __FILE__ ) . '2Logo_EAs.LiT.png) !important; }</style>';
// }
// add_action('admin_head', 'custom_admin_logo');


add_filter( 'admin_footer_text', '__return_empty_string', 11 );
add_filter( 'update_footer', '__return_empty_string', 11 );

function my_custom_admin_head() {
	echo '<style>[for="wp_welcome_panel-hide"] {display: none !important;}</style>';
}
add_action( 'admin_head', 'my_custom_admin_head' );


add_action( 'admin_bar_menu', 'my_new_toolbar_item', 999 );

function my_new_toolbar_item( $wp_admin_bar ) {
	$args = array(
		'id'    => 'eal_logo',
		'title' => '<div style="width:10em"><a href="' . site_url() . '/wp-admin/"><img style="display:block; margin-top:1em; margin-left:-1em; width:11em"  src="' . plugin_dir_url( __FILE__ ) . 'Logo_EAs.LiT.png"></a></div>'
		
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


// add_action ('wp_loaded', 'growtheme_mailchimp_signup');
// function growtheme_mailchimp_signup() {
// 	// Submit the Form
// 	if(isset($_REQUEST['redirect'])) {
// 		wp_redirect($_REQUEST['redirect']);
// 		exit();
// 	}
// }
	


?>