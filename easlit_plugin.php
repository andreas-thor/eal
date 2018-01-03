<?php
/*
 Plugin Name: EAs.LiT (E-Assessment Literacy Tool)
 Plugin URI: https://github.com/andreas-thor/eal
 Description: aKollaborative, qualitätsgesicherte Erstellung von Items für E-Assessments. 
 Version: 1.0
 Author: Andreas Thor
 EMail: dr.andreas.thor@googlemail.com
 */


require_once 'includes/cpt/CPT_Item.php';
require_once 'includes/cpt/CPT_ItemBasket.php';
require_once 'includes/cpt/CPT_ItemSC.php';
require_once 'includes/cpt/CPT_ItemMC.php';
require_once 'includes/cpt/CPT_LearnOut.php';
require_once 'includes/cpt/CPT_Review.php';

require_once 'includes/page/BulkViewer.php';
require_once 'includes/page/Importer.php';
require_once 'includes/page/Explorer.php';
require_once 'includes/page/Blueprint.php';


// require_once 'includes/class.PAG_Metadata.php';
// require_once 'includes/class.PAG_Basket.php';
// require_once 'includes/class.PAG_Explorer.php';
// require_once 'includes/class.PAG_Generator.php';
// require_once 'includes/class.PAG_Item.Import.php';
require_once 'includes/class.PAG_TaxonomyImport.php';

require_once 'includes/class.CLA_RoleTaxonomy.php';





add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-slider' );
});

// register AJAX-PHP-function
add_action( 'wp_ajax_load_items', array ('PAG_Explorer', 'load_items_callback') );
add_action( 'wp_ajax_getCrossTable', array ('Explorer', 'getCrossTable_callback') );

	


/**
 * Dashboard shows 
 * a) Item Overview: number of items (per type and overall) incl. pending review
 * b) Metadata Overview: number of taxonomy terms and learning outcomes
 */

add_action( 'wp_dashboard_setup', function() {
	
	global $wp_meta_boxes;
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
	
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
	
	wp_add_dashboard_widget('dashboard_items', 'Item Overview', function () {
		
		/* number of items, SCs, and MCs */
		printf ('<table border="0">');
		foreach ([new CPT_Item(), new CPT_ItemSC(), new CPT_ItemMC()] as $object) {
			$counts = $object->WPCB_count_posts(NULL, $object->type, NULL);
			printf ('
				<tr>
					<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div> %2$s</td>
					<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
					<td align="right" style="width:10em">&nbsp;(<a href="edit.php?post_type=%3$s&post_status=pending">%5$d</a> pending review)</td>
				</tr>',
				$object->dashicon, $object->label, $object->type, $counts->publish+$counts->pending+$counts->draft, $counts->pending);
		}
		printf ('</table><hr>');
		
		/* number of reviews */
		printf ('<table border="0">');
		$object = new CPT_Review();
		$counts = $object->WPCB_count_posts(NULL, $object->type, NULL);
		printf ('
			<tr>
				<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div>%2$ss</td>
				<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
			</tr>',
			$object->dashicon, $object->label, $object->type, $counts->publish+$counts->pending+$counts->draft);
		printf ('</table>');
		
	});
	
	wp_add_dashboard_widget('dashboard_metadata', 'Metadata Overview', function() {
		
		/* number of terms of current domain */
		printf ('<table border="0">');
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		if ($domain["name"]!="") {
			$term_count = wp_count_terms( $domain["name"], array( 'hide_empty' => false ));
			printf ('
				<tr>
					<td style="width:11em"><div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> %1$s</td>
					<td align="right" style="width:4em"><a href="edit-tags.php?taxonomy=%2$s">%3$d</a></td>
				</tr>', 
				$domain["label"], $domain["name"], $term_count);
		}
		
		/* number of learning outcomes */
		$object = new CPT_LearnOut();
		$counts = $object->WPCB_count_posts(NULL, $object->type, NULL);
		printf ('
			<tr>
				<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div>%2$ss</td>
				<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
			</tr>',
				$object->dashicon, $object->label, $object->type, $counts->publish+$counts->pending+$counts->draft);
		printf ('</table>');
	});
});









add_action ('admin_menu', function () {
	
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
//  remove_menu_page( 'options-general.php' );        //Settings	

 	global $menu, $submenu;
 	
 	
 	add_menu_page('eal_page_items', 'Items', 'edit_posts', 'edit.php?post_type=item', '', (new CPT_Item())->dashicon, 31);
 	foreach ([new CPT_Item(), new CPT_ItemSC(), new CPT_ItemMC()] as $object) {
 		add_submenu_page( 'edit.php?post_type=item', $object->label, '<div class="dashicons-before ' . $object->dashicon . '" style="display:inline">&nbsp;</div> ' . $object->label, 'edit_posts', "edit.php?post_type={$object->type}");
 	}
 	add_submenu_page( 'edit.php?post_type=item', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', array ('Importer', 'createPage'));
 	$object = new CPT_Review();
 	add_submenu_page( 'edit.php?post_type=item', $object->label, '<div class="dashicons-before ' . $object->dashicon . '" style="display:inline">&nbsp;</div> ' . $object->label, 'edit_posts', 'edit.php?post_type=' . $object->type);
 	
 	
 	$domain = RoleTaxonomy::getCurrentRoleDomain ();
 	
	 	/* TODO: first sub menu should open menu */
// 	 	$menuslug = 'metadata';
	 	$taxurl = 'edit-tags.php?taxonomy=' . $domain["name"]; 
	 	$taxurlredirect = add_query_arg ('redirect', $taxurl, 'edit.php?post_type=learnout');
	 	
// 	 	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', $menuslug, '' /* array ('PAG_Metadata', 'createTable')*/, 'dashicons-tag', 32);
// 		add_submenu_page( $menuslug, $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', $taxurl);
// 	 	add_submenu_page( $menuslug, 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
// 	 	add_submenu_page( $menuslug, 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');

	 	
	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', 'edit.php?post_type=learnout', '', 'dashicons-tag', 32);
	add_submenu_page( 'edit.php?post_type=learnout', 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');
	if ($domain ["name"] != "") {
		add_submenu_page ( 'edit.php?post_type=learnout', $domain ["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain ["label"], 'edit_posts', $taxurl );
	}
//  	add_submenu_page( 'edit.php?post_type=learnout', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
 	add_submenu_page( 'edit.php?post_type=learnout', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import-taxonomy', array ('PAG_Taxonomy_Import', 'createPage'));
 	
	 	
// LEZTE	 	
// 	 	add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', 'metadata', '' /* array ('PAG_Metadata', 'createTable')*/, 'dashicons-tag', 32);
// 	 	add_submenu_page( 'metadata', $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', $taxurl);
// 	 	add_submenu_page( 'metadata', 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', 'WPCB_import_topics');
// 	 	add_submenu_page( 'metadata', 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');
	 	 
 	
 	

 	
        	
    $c = count (EAL_ItemBasket::get()); 
    add_menu_page('eal_page_basket', 'Item Basket <span class="update-plugins count-1"><span class="plugin-count">' . $c . '</span></span>', 'edit_posts', 'edit.php?post_type=itembasket', '', 'dashicons-cart', 34);
    add_submenu_page( 'edit.php?post_type=itembasket', 'Table', '<div class="dashicons-before dashicons-format-aside" style="display:inline">&nbsp;</div> Table', 'edit_posts', 'edit.php?post_type=itembasket');
//    add_submenu_page( 'edit.php?post_type=itembasket', 'Explorer', '<div class="dashicons-before dashicons-chart-pie" style="display:inline">&nbsp;</div> Explorer', 'edit_posts', 'ist-blueprint', array ('PAG_Explorer', 'createPage'));
    add_submenu_page( 'edit.php?post_type=itembasket', 'Explorer2', '<div class="dashicons-before dashicons-chart-pie" style="display:inline">&nbsp;</div> Explorer', 'edit_posts', 'item_explorer', array ('Explorer', 'page_explorer'));
    
    add_submenu_page( 'edit.php?post_type=itembasket', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_posts', 'view_basket', array ('BulkViewer', 'page_view_basket'));
//     add_submenu_page( 'edit.php?post_type=itembasket', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_posts', 'view', array ('PAG_Basket', 'createPageView'));
    
    /* the following are not visible in the menu but must be registered */
    add_submenu_page( 'view', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_posts', 'view_item', array ('BulkViewer', 'page_view_item'));
    add_submenu_page( 'view', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_posts', 'view_review', array ('BulkViewer', 'page_view_review'));
    add_submenu_page( 'view', 'Viewer', '<div class="dashicons-before dashicons-exerpt-view" style="display:inline">&nbsp;</div> Viewer', 'edit_posts', 'view_learnout', array ('BulkViewer', 'page_view_learnout'));
    
    /*
     * Viewer Seiten pro Typ 
     * Callable ist dann getList Funktion in Klasse, z.B. HTML_Item::getList
     */
    
    // add_submenu_page( 'edit.php?post_type=itembasket', 'Generator', '<div class="dashicons-before dashicons-admin-generic" style="display:inline">&nbsp;</div> Generator', 'edit_posts', 'generator', array ('PAG_Generator', 'createPage'));
    add_submenu_page( 'edit.php?post_type=itembasket', 'Generator2', '<div class="dashicons-before dashicons-admin-generic" style="display:inline">&nbsp;</div> Generator', 'edit_posts', 'test_generator', array ('Blueprint', 'page_blueprint'));
    
    
    
});














/**
 * Plugin Activation --> Create Database Tables for all data types
 */

register_activation_hook( __FILE__, function () {
	
	
	
	EAL_Item::createTables();
	EAL_ItemSC::createTables();
	EAL_ItemMC::createTables();
	EAL_Review::createTables();
	EAL_LearnOut::createTables();
});




add_action( 'init', function () {

	if ($_REQUEST["page"] == "download") {
		ImportExport::download(explode(",", $_REQUEST["itemids"]));
		exit();
	}
	
	if (!session_id()) session_start();
	
	(new CPT_Item())->init();
	(new CPT_ItemBasket())->init();
	(new CPT_ItemSC())->init();
	(new CPT_ItemMC())->init();
	(new CPT_Review())->init();
	(new CPT_LearnOut())->init();
	
	RoleTaxonomy::init();
	
});





add_action('login_head',  function () {
	echo '<style  type="text/css"> .login h1 a { width:320px; background-size: 320px; background-position: center middle; background-image:url(' . plugin_dir_url( __FILE__ ) . 'Logo_EAs.LiT.png)  !important; } </style>';
	echo '<style  type="text/css"> p#backtoblog {  display: none; } </style>';
});


/**
 * Adjust h1 label on table sites
 */

add_action( 'admin_head', function () {

	echo '<style>[for="wp_welcome_panel-hide"] {display: none !important;}</style>';
	
	?> <script type="text/javascript">	jQuery(document).ready( function($) { <?php
	if (!isset($_REQUEST['page'])) {
		switch ($_REQUEST['post_type']) {
			case 'item': ?> jQuery(jQuery(".wrap a")[0]).remove();  jQuery(jQuery(".wrap h1")[0]).replaceWith ('<h1>All Items <a href="<?php echo (site_url()); ?>/wp-admin/post-new.php?post_type=itemsc" class="page-title-action">Add Single Choice</a><a href="<?php echo (site_url()); ?>/wp-admin/post-new.php?post_type=itemmc" class="page-title-action">Add Multiple Choice</a></h1>'); <?php break;
			case 'itembasket': ?> jQuery(jQuery(".wrap a")[0]).remove(); jQuery(jQuery(".wrap h1")[0]).replaceWith ('<h1>Item Basket</h1>');  <?php break;			
			case 'review': ?> jQuery(jQuery(".wrap a")[0]).remove(); jQuery(jQuery(".wrap h1")[0]).replaceWith ('<h1>All Reviews</h1>');  <?php break;			
		}
	}
	?> }); </script> <?php
});
	


add_filter('login_headerurl', function () { return 'https://github.com/andreas-thor/eal'; });
add_filter('login_headertitle', function () {	return 'EAs.LiT'; });
add_filter('admin_footer_text', function () { return ''; /* return plugin_dir_url(__FILE__); */ } , 11 );
add_filter('update_footer', '__return_empty_string', 11 );



/**
 * Horizontal menu bar
 * a) add Logo
 * b) add user type + taxonomy name
 * c) remove all default entries except user profile (edit, logout) 
 */

add_action( 'admin_bar_menu', function ($wp_admin_bar) {

	$wp_admin_bar->add_node( array(	 'id'=> 'eal_logo',
		'title' => '<div style="width:10em"><a href="' . site_url() . '/wp-admin/"><img style="display:block; margin-top:1em; margin-left:-1em; width:11em"  src="' . plugin_dir_url( __FILE__ ) . 'Logo_EAs.LiT.png"></a></div>'
	));

	$wp_admin_bar->add_menu (array ("id" => "eal_currentRole",
		"title" => sprintf ("<div class='wp-menu-image dashicons-before %s'>&nbsp;%s</div>", (RoleTaxonomy::getCurrentRoleType()=="author") ? "dashicons-admin-users" :  "dashicons-groups", RoleTaxonomy::getCurrentRoleDomain()["label"]), 
		"href" => sprintf ('%s/wp-admin/profile.php#roleman', site_url())
	));
	
	$wp_admin_bar->remove_menu ('updates');
	$wp_admin_bar->remove_menu ('comments');
	$wp_admin_bar->remove_menu ('new-content');
	$wp_admin_bar->remove_menu ('wp-logo');
	$wp_admin_bar->remove_menu ('site-name');
	

}, 999 );



add_action('show_user_profile', function ($user) { RoleTaxonomy::showCurrentRole($user); });
add_action('edit_user_profile', function ($user) { RoleTaxonomy::showCurrentRole($user); });
add_action('profile_update', function ($user_id, $old_user_data) { RoleTaxonomy::setCurrentRole ($user_id, $old_user_data); });



?>