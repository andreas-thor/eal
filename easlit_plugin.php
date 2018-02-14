<?php

/*
 * Plugin Name: EAs.LiT (E-Assessment Literacy Tool)
 * Plugin URI: https://github.com/andreas-thor/eal
 * Description: Kollaborative, qualit&auml;tsgesicherte Erstellung von Items f&uuml;r E-Assessments.
 * Version: 2018-01-24
 * Author: Andreas Thor
 * EMail: dr.andreas.thor@googlemail.com
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

require_once 'includes/class.PAG_TaxonomyImport.php';
require_once 'includes/class.CLA_RoleTaxonomy.php';

require_once 'includes/imex/IMEX_Easlit.php';
require_once 'includes/imex/IMEX_Moodle.php';
require_once 'includes/imex/IMEX_Ilias.php'; 
require_once 'includes/imex/IMEX_Term.php';



/* add JQuery */
add_action("admin_enqueue_scripts", function () {
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-slider");
});

/* register AJAX-PHP-function */
add_action('wp_ajax_getCrossTable', array(
	'Explorer',
	'getCrossTable_callback'
));
add_action('wp_ajax_getItemPools', array(
	'Blueprint',
	'getItemPools_callback'
));


/* Plugin Activation --> Create Database Tables for all data types */
register_activation_hook(__FILE__, function () {
	EAL_Item::createTables();
	EAL_ItemSC::createTables();
	EAL_ItemMC::createTables();
	EAL_Review::createTables();
	EAL_LearnOut::createTables();
});


/* init custom post types */
add_action('init', function () {
	

	
	if (! session_id())
		session_start();
	
	(new CPT_Item())->init();
	(new CPT_ItemBasket())->init();
	(new CPT_ItemSC())->init();
	(new CPT_ItemMC())->init();
	(new CPT_Review())->init();
	(new CPT_LearnOut())->init();
	
	RoleTaxonomy::init();
	
	if ($_REQUEST["page"] == "download") {
		
		if ($_REQUEST["type"] == "item") {
			
			$itemids = explode(",", $_REQUEST["itemids"]);
			
			switch ($_REQUEST['format']) {
				case 'moodle': (new IMEX_Moodle())->downloadItems($itemids); break;
				case 'ilias': (new IMEX_Ilias())->downloadItems($itemids); break;
			}
			
			exit();
		}
		
		if ($_REQUEST["type"] == "term") {
			(new IMEX_Term())->downloadTerms ($_REQUEST["taxonomy"], $_REQUEST["termid"], $_REQUEST['format']);
			exit();
		}
		
		
	}
	
	
});


/* adjust user interface */ 
setMainMenu();
setAdminMenu();
setDashboard();


function setMainMenu() {
	add_action('admin_menu', function () {
		
		global $menu, $submenu;
		
		// remove standard menu entries except: plugins.php, users.php, options-general.php
		foreach (['index.php', 'edit.php', 'upload.php', 'edit.php?post_type=page', 'edit-comments.php', 'themes.php', 'tools.php'] as $page) {
			remove_menu_page($page);
		}
		
		// Menu: Items
		$menuslug = 'edit.php?post_type=item';
		
		add_menu_page('eal_page_items', 'Items', 'edit_posts', $menuslug, '', (new CPT_Item())->dashicon, 31);
		foreach ([new CPT_Item(), new CPT_ItemSC(), new CPT_ItemMC(), new CPT_Review() ] as $object) {
			add_submenu_page($menuslug, $object->label, '<div class="dashicons-before ' . $object->dashicon . '" style="display:inline">&nbsp;</div> ' . $object->label, 'edit_posts', "edit.php?post_type={$object->type}");
		}
// 		add_submenu_page($menuslug, 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', ['Importer', 'createPage']);

		// Menu: Metadata
		$menuslug = 'edit.php?post_type=learnout';
		$domain = RoleTaxonomy::getCurrentRoleDomain();
		
		add_menu_page('eal_page_metadata', 'Metadata', 'edit_posts', $menuslug, '', 'dashicons-tag', 32);
		add_submenu_page($menuslug, 'Learning Outcomes', '<div class="dashicons-before dashicons-welcome-learn-more" style="display:inline">&nbsp;</div> Learn. Outcomes', 'edit_posts', 'edit.php?post_type=learnout');
		if ($domain["name"] != "") {
			add_submenu_page($menuslug, $domain["label"], '<div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> ' . $domain["label"], 'edit_posts', 'edit-tags.php?taxonomy=' . $domain["name"]);
		}
		add_submenu_page($menuslug, 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import-taxonomy', ['PAG_Taxonomy_Import', 'createPage']);
		
		// Menu: Item Basket
		$menuslug = 'edit.php?post_type=itembasket';
		$c = count(EAL_ItemBasket::get());	// number of items in basket
		
		add_menu_page('eal_page_basket', 'Item Basket <span class="update-plugins count-1"><span class="plugin-count">' . $c . '</span></span>', 'edit_posts', $menuslug, '', 'dashicons-cart', 34);
		add_submenu_page($menuslug, 'Table',      '<div class="dashicons-before dashicons-format-aside"  style="display:inline">&nbsp;</div> Table',     'edit_posts', 'edit.php?post_type=itembasket');
		add_submenu_page($menuslug, 'Explorer2',  '<div class="dashicons-before dashicons-chart-pie"     style="display:inline">&nbsp;</div> Explorer',  'edit_posts', 'item_explorer',  ['Explorer',   'page_explorer']);
		add_submenu_page($menuslug, 'Viewer',     '<div class="dashicons-before dashicons-exerpt-view"   style="display:inline">&nbsp;</div> Viewer',    'edit_posts', 'view_basket',    ['BulkViewer', 'page_view_basket']);
		add_submenu_page($menuslug, 'Generator2', '<div class="dashicons-before dashicons-admin-generic" style="display:inline">&nbsp;</div> Generator', 'edit_posts', 'test_generator', ['Blueprint',  'page_blueprint']);
		
		/* the following are not visible in the menu but must be registered */
		foreach (['view_item', 'view_review', 'view_learnout'] as $view) {
			add_submenu_page('view', 'Viewer',    '<div class="dashicons-before dashicons-exerpt-view"   style="display:inline">&nbsp;</div> Viewer',     'edit_posts', $view,     ['BulkViewer', 'page_' . $view]);
		}
		add_submenu_page('view', 'Viewer', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', ['Importer', 'createPage']);
		
	});
	
	
	/*
	 * Adjust h1 label on table sites
	 * You cannot add: (generic) item, item basket, review
	 */
	add_action('admin_head', function () {
		
		$headertext = array (
			'item' => sprintf ('All Items <a href="%1$s/wp-admin/post-new.php?post_type=itemsc" class="page-title-action">Add Single Choice</a><a href="%1$s/wp-admin/post-new.php?post_type=itemmc" class="page-title-action">Add Multiple Choice</a>', site_url()),
			'itembasket' => 'Item Basket',
			'review' => 'All Reviews'
		);
		
		printf ('<style>[for="wp_welcome_panel-hide"] {display: none !important;}</style>');

		if (! isset($_REQUEST['page'])) {
			if (isset ($headertext[$_REQUEST['post_type']])) {
				printf ('<script type="text/javascript">');
				printf ('	jQuery(document).ready( function($) { ');
				printf ('		jQuery(jQuery(".wrap a")[0]).remove();');
				printf ('		jQuery(jQuery(".wrap h1")[0]).replaceWith(\'<h1>%s</h1>\');', $headertext[$_REQUEST['post_type']]);
				printf ('	});');
				printf ('</script>');
			}
		}
	});
}


function setAdminMenu() {
	
	/*
	 * makes sure, that users do not see the default home page but are redirected to the dashboard immediately;
	 * if the user is not yet logged in --> automatic redirect to login page
	 */
	add_action('template_redirect', function () {
		if (is_home()) {
			wp_redirect(home_url('/wp-admin/'));
			die();
		}
	});
	
	/* Login screen shows EAsLiT logo */
	add_action('login_head', function () {
		echo '<style  type="text/css"> .login h1 a { width:320px; background-size: 320px; background-position: center middle; background-image:url(' . plugin_dir_url(__FILE__) . 'Logo_EAs.LiT.png)  !important; } </style>';
		echo '<style  type="text/css"> p#backtoblog {  display: none; } </style>';
	});
	
	add_filter('login_headerurl', function () {
		return 'https://github.com/andreas-thor/eal';
	});
	add_filter('login_headertitle', function () {
		return 'EAs.LiT';
	});
	add_filter('admin_footer_text', function () {
		return '';
	}, 11);
	add_filter('update_footer', function () {
		return '';
	}, 11);
	
	/*
	 * Horizontal menu bar
	 * a) add Logo
	 * b) add user type + taxonomy name
	 * c) remove all default entries except user profile (edit, logout)
	 */
	
	add_action('admin_bar_menu', function ($wp_admin_bar) {
		
		$wp_admin_bar->add_node(array(
			'id' => 'eal_logo',
			'title' => '<div style="width:10em"><a href="' . site_url() . '/wp-admin/"><img style="display:block; margin-top:1em; margin-left:-1em; width:11em"  src="' . plugin_dir_url(__FILE__) . 'Logo_EAs.LiT.png"></a></div>'
		));
		
		$wp_admin_bar->add_menu(array(
			"id" => "eal_currentRole",
			"title" => sprintf("<div class='wp-menu-image dashicons-before %s'>&nbsp;%s</div>", (RoleTaxonomy::getCurrentRoleType() == "author") ? "dashicons-admin-users" : "dashicons-groups", RoleTaxonomy::getCurrentRoleDomain()["label"]),
			"href" => sprintf('%s/wp-admin/profile.php#roleman', site_url())
		));
		
		
		setAdminMenu_Download_Item ($wp_admin_bar);
		setAdminMenu_Upload_Item ($wp_admin_bar);
		setAdminMenu_Download_and_Upload_Topic ($wp_admin_bar);

		$wp_admin_bar->remove_menu('view');
		$wp_admin_bar->remove_menu('updates');
		$wp_admin_bar->remove_menu('comments');
		$wp_admin_bar->remove_menu('new-content');
		$wp_admin_bar->remove_menu('wp-logo');
		$wp_admin_bar->remove_menu('site-name');
	}, 999);
	
	add_action('show_user_profile', function ($user) {
		RoleTaxonomy::showCurrentRole($user);
	});
	add_action('edit_user_profile', function ($user) {
		RoleTaxonomy::showCurrentRole($user);
	});
	add_action('profile_update', function ($user_id, $old_user_data) {
		RoleTaxonomy::setCurrentRole($user_id, $old_user_data);
	});
}


function setAdminMenu_Download_Item($wp_admin_bar) {
	
	$itemids = NULL;
	
	switch ($_REQUEST["page"]) {
		case "view_item": 	$itemids = ItemExplorer::getItemIdsByRequest(); break;
		case "view_basket": $itemids = EAL_ItemBasket::get(); break;
		default: return; 	// add download menu item for view-pages
	}
	
	$param_itemids = implode(',', $itemids);
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_download_item',
		'title' => sprintf("<div class='wp-menu-image dashicons-before dashicons-download'>&nbsp;%s</div>", 'Download'),
		'href' => FALSE ) );
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_download_item',
		'title' => 'Ilias',
		'href' => sprintf('%s/admin.php?page=%s&type=%s&format=%s&itemids=%s', site_url(), 'download', 'item', 'ilias', $param_itemids)
	));
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_download_item',
		'title' => 'Moodle',
		'href' => sprintf('%s/admin.php?page=%s&type=%s&format=%s&itemids=%s', site_url(), 'download', 'item', 'moodle', $param_itemids)
	));
}

function setAdminMenu_Upload_Item($wp_admin_bar) {
	
	if ($_SERVER['PHP_SELF']!='/wordpress/wp-admin/edit.php') return;
	
	if (($_REQUEST['post_type'] != 'item') && ($_REQUEST['post_type'] != 'itemsc') && ($_REQUEST['post_type'] != 'itemmc')) return;
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_upload_item',
		'title' => sprintf("<div class='wp-menu-image dashicons-before dashicons-upload'>&nbsp;%s</div>", 'Upload'),
		'href' => FALSE ) );
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_upload_item',
		'title' => 'Ilias',
		'href' => sprintf('admin.php?page=%s&post_type=%s&format=%s', 'import', 'item', 'ilias')
	));
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_upload_item',
		'title' => 'Moodle',
		'href' => sprintf('admin.php?page=%s&post_type=%s&format=%s', 'import', 'item', 'moodle')
	));
	
}

function setAdminMenu_Download_and_Upload_Topic($wp_admin_bar) {
	
	if (($_SERVER['PHP_SELF']!='/wordpress/wp-admin/edit-tags.php') && ($_SERVER['PHP_SELF']!='/wordpress/wp-admin/term.php')) return;
	
	$termid = -1;
	if ($_SERVER['PHP_SELF']=='/wordpress/wp-admin/term.php') {
		$termid = intval($_REQUEST['tag_ID']);
	}
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_download_term',
		'title' => sprintf("<div class='wp-menu-image dashicons-before dashicons-download'>&nbsp;%s</div>", 'Download'),
		'href' => FALSE
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_download_term_txt',
		'parent' => 'eal_download_term',
		'title' => 'TXT',
		'href' => sprintf('admin.php?page=%s&type=%s&format=%s&taxonomy=%s&termid=%d', 'download', 'term', 'txt', $_REQUEST['taxonomy'], $termid)
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_download_term_json',
		'parent' => 'eal_download_term',
		'title' => 'JSON',
		'href' => sprintf('admin.php?page=%s&type=%s&format=%s&taxonomy=%s&termid=%d', 'download', 'term', 'json', $_REQUEST['taxonomy'], $termid)
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_upload_term',
		'title' => sprintf("<div class='wp-menu-image dashicons-before dashicons-upload'>&nbsp;%s</div>", 'Upload'),
		'href' => FALSE 
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_upload_term_txt',
		'parent' => 'eal_upload_term',
		'title' => 'TXT',
		'href' => sprintf('admin.php?page=%s&post_type=%s&format=%s&taxonomy=%s&termid=%d', 'import', 'term', 'txt', $_REQUEST['taxonomy'], $termid)
	));
	
	
	
	
	
	
	
}

function setDashboard() {
	
	/*
	 * Dashboard shows
	 * a) Item Overview: number of items (per type and overall) incl. pending review
	 * b) Metadata Overview: number of taxonomy terms and learning outcomes
	 */
	
	add_action('wp_dashboard_setup', function () {
		
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
		
		wp_add_dashboard_widget('dashboard_items', 'Item Overview', function () {
			
			/* number of items, SCs, and MCs */
			printf('<table border="0">');
			foreach ([
				new CPT_Item(),
				new CPT_ItemSC(),
				new CPT_ItemMC()
			] as $object) {
				$counts = $object->WPCB_count_posts(NULL, $object->type, NULL);
				printf('
				<tr>
					<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div> %2$s</td>
					<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
					<td align="right" style="width:10em">&nbsp;(<a href="edit.php?post_type=%3$s&post_status=pending">%5$d</a> pending review)</td>
				</tr>', $object->dashicon, $object->label, $object->type, $counts->publish + $counts->pending + $counts->draft, $counts->pending);
			}
			printf('</table><hr>');
			
			/* number of reviews */
			printf('<table border="0">');
			$object = new CPT_Review();
			$counts = $object->WPCB_count_posts(NULL, $object->type, NULL);
			printf('
			<tr>
				<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div>%2$ss</td>
				<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
			</tr>', $object->dashicon, $object->label, $object->type, $counts->publish + $counts->pending + $counts->draft);
			printf('</table>');
		});
			
			wp_add_dashboard_widget('dashboard_metadata', 'Metadata Overview', function () {
				
				/* number of terms of current domain */
				printf('<table border="0">');
				$domain = RoleTaxonomy::getCurrentRoleDomain();
				if ($domain["name"] != "") {
					$term_count = wp_count_terms($domain["name"], array(
						'hide_empty' => false
					));
					printf('
				<tr>
					<td style="width:11em"><div class="dashicons-before dashicons-networking" style="display:inline">&nbsp;</div> %1$s</td>
					<td align="right" style="width:4em"><a href="edit-tags.php?taxonomy=%2$s">%3$d</a></td>
				</tr>', $domain["label"], $domain["name"], $term_count);
				}
				
				/* number of learning outcomes */
				$object = new CPT_LearnOut();
				$counts = $object->WPCB_count_posts(NULL, $object->type, NULL);
				printf('
			<tr>
				<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div>%2$ss</td>
				<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
			</tr>', $object->dashicon, $object->label, $object->type, $counts->publish + $counts->pending + $counts->draft);
				printf('</table>');
			});
	});
}


?>