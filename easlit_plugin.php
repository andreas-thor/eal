<?php

/*
 * Plugin Name: EAs.LiT (E-Assessment Literacy Tool)
 * Plugin URI: https://github.com/andreas-thor/eal
 * Description: Kollaborative, qualit&auml;tsgesicherte Erstellung von Items f&uuml;r E-Assessments.
 * Version: 2018-06-04
 * Author: Andreas Thor
 * EMail: dr.andreas.thor@googlemail.com
 */

require_once 'includes/db/DB_Term.php';

require_once 'includes/cpt/CPT_Item.php';
require_once 'includes/cpt/CPT_ItemBasket.php';
require_once 'includes/cpt/CPT_ItemSC.php';
require_once 'includes/cpt/CPT_ItemMC.php';
require_once 'includes/cpt/CPT_LearnOut.php';
require_once 'includes/cpt/CPT_Review.php';
require_once 'includes/cpt/CPT_TestResult.php';


require_once 'includes/page/Importer.php';
require_once 'includes/page/Explorer.php';
require_once 'includes/page/Blueprint.php';

require_once 'includes/page/PAG_Item_Bulkviewer.php';
require_once 'includes/page/PAG_Learnout_Bulkviewer.php';


require_once 'includes/class.CLA_RoleTaxonomy.php';

require_once 'includes/exp/EXP_Item_Ilias.php';
require_once 'includes/exp/EXP_Item_Moodle.php';
require_once 'includes/exp/EXP_Item_JSON.php';
require_once 'includes/exp/EXP_Term_TXT.php';
require_once 'includes/exp/EXP_Term_JSON.php';




require_once(__DIR__ . "/../../../wp-admin/includes/screen.php");


/* add JQuery */
add_action("admin_enqueue_scripts", function () {
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-slider");
	
	// for modal dialogs (e.g., automatic annotation)
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_style( 'wp-jquery-ui-dialog' );
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
add_action('wp_ajax_getMostSimilarTerms', array(
	'HTML_Item',
	'getMostSimilarTerms_callback'
));


/* Plugin Activation --> Create Database Tables for all data types */
register_activation_hook(__FILE__, function () {
	DB_Item::createTables();
	DB_ItemSC::createTables();
	DB_ItemMC::createTables();
	DB_Review::createTables();
	DB_LearnOut::createTables();
	DB_Term::createTables();
	DB_TestResult::createTables();
});


/* init custom post types */
add_action('init', function () {
	
	if (! session_id()) {
		session_start();
	}
	

	RoleTaxonomy::init();
	
	// register custom post types
	(new CPT_Item())->registerType();
	(new CPT_ItemBasket())->registerType();
	(new CPT_ItemMC())->registerType();
	(new CPT_ItemSC())->registerType();
	(new CPT_Review())->registerType();
	(new CPT_LearnOut())->registerType();
	(new CPT_TestResult())->registerType();
	
	
	
	
	$php_page = getCurrentPHPFile();

	
	// standard page
	if ((in_array ($php_page, ['edit.php', 'post.php', 'post-new.php'])) && (!isset ($_REQUEST["page"]))) {
		switch ($_REQUEST['post_type']) {
			case 'itemsc':  
				(new CPT_ItemSC())->addHooks(); 
				break;
			case 'itemmc': 
				(new CPT_ItemMC())->addHooks(); 
				break;
			case 'learnout': 
				(new CPT_LearnOut())->addHooks(); 
				break;
			case 'review': 
				(new CPT_Review())->addHooks(); 
				break;
			case 'itembasket': 
				(new CPT_ItemBasket())->addHooks(); 
				break;
			case 'testresult':
				(new CPT_TestResult())->addHooks();
				break;
			default: 
				(new CPT_Item())->addHooks(); 
				break;
		}
	}
	
	// import or update items
	if ((in_array ($_POST['action'], ['import', 'update'])) && ($php_page == 'admin.php')) {
		(new CPT_ItemSC())->addHooks();
		(new CPT_ItemMC())->addHooks();
	}
	
	
	if ($php_page == 'revision.php') {
		(new CPT_ItemSC())->addHooks();
		(new CPT_ItemMC())->addHooks(); 
	}
	
	
	
	
	if ($_REQUEST["page"] == "download") {
		
		if ($_REQUEST["type"] == "item") {
			
			$itemids = explode(",", $_REQUEST["itemids"]);
			
			switch ($_REQUEST['format']) {
				case 'moodle': (new EXP_Item_Moodle())->downloadItems($itemids); break;
				case 'ilias': (new EXP_Item_Ilias())->downloadItems($itemids); break;
				case 'json': (new EXP_Item_JSON())->downloadItems($itemids); break;
				
			}
			
			exit();
		}
		
		if ($_REQUEST['type'] == 'term') {
			
			switch ($_REQUEST['format']) {
				case 'txt': (new EXP_Term_TXT($_REQUEST["taxonomy"]))->downloadTerms ($_REQUEST["termid"]); break;
				case 'json': (new EXP_Term_JSON($_REQUEST["taxonomy"]))->downloadTerms ($_REQUEST["termid"]); break;
			}
			exit();
		}
		
		
	}
	
	if ($_REQUEST["page"] == "index") {
	
		DB_Term::buildIndex($_REQUEST["taxonomy"]);
		wp_redirect('edit-tags.php?taxonomy=' . $_REQUEST["taxonomy"]);
		exit();
	}
	
	
});
 

/* adjust user interface */ 
setMainMenu();
setAdminMenu();
setDashboard();
setMainHeader();
setScreenSettings();





function setMainMenu() {
	add_action('admin_menu', function () {
		
		global $menu, $submenu;
		
		// remove standard menu entries except: plugins.php, users.php, options-general.php
		foreach (['index.php', 'edit.php', 'upload.php', 'edit.php?post_type=page', 'edit-comments.php', 'themes.php', 'tools.php'] as $page) {
			remove_menu_page($page);
		}
		
		// Menu: Items
		$menuslug = 'edit.php?post_type=item';
		
		add_menu_page('eal_page_items', 'Items', 'edit_posts', $menuslug, '', (new CPT_Item())->getDashIcon(), 31);
		foreach ([new CPT_Item(), new CPT_ItemSC(), new CPT_ItemMC(), new CPT_Review() ] as $object) {
			add_submenu_page($menuslug, $object->getLabel(), '<div class="dashicons-before ' . $object->getDashIcon() . '" style="display:inline">&nbsp;</div> ' . $object->getLabel(), 'edit_posts', "edit.php?post_type={$object->getType()}");
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
		// add_submenu_page($menuslug, 'Import', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import-taxonomy', ['PAG_Taxonomy_Import', 'createPage']);
		
		// Menu: Item Basket
		$menuslug = 'edit.php?post_type=itembasket';
		$c = count(EAL_ItemBasket::get());	// number of items in basket
		
		add_menu_page('eal_page_basket', 'Item Basket <span class="update-plugins count-1"><span class="plugin-count">' . $c . '</span></span>', 'edit_posts', $menuslug, '', 'dashicons-cart', 34);
		add_submenu_page($menuslug, 'Table',      '<div class="dashicons-before dashicons-format-aside"  style="display:inline">&nbsp;</div> Table',     'edit_posts', 'edit.php?post_type=itembasket');
		add_submenu_page($menuslug, 'Explorer2',  '<div class="dashicons-before dashicons-chart-pie"     style="display:inline">&nbsp;</div> Explorer',  'edit_posts', 'item_explorer',  ['Explorer',   'page_explorer']);
		add_submenu_page($menuslug, 'Viewer',     '<div class="dashicons-before dashicons-exerpt-view"   style="display:inline">&nbsp;</div> Viewer',    'edit_posts', 'view_basket',    ['PAG_Item_Bulkviewer', 'page_view_basket']);
		add_submenu_page($menuslug, 'Generator2', '<div class="dashicons-before dashicons-admin-generic" style="display:inline">&nbsp;</div> Generator', 'edit_posts', 'test_generator', ['Blueprint',  'page_blueprint']);
		
	
		
		/* the following are not visible in the menu but must be registered */
		add_submenu_page('view', 'Viewer',    '<div class="dashicons-before dashicons-exerpt-view"   style="display:inline">&nbsp;</div> Viewer',     'edit_posts', 'view_item',     ['PAG_Item_Bulkviewer', 'page_view_item']);
		add_submenu_page('view', 'Viewer',    '<div class="dashicons-before dashicons-exerpt-view"   style="display:inline">&nbsp;</div> Viewer',     'edit_posts', 'view_item_with_reviews',     ['PAG_Item_Bulkviewer', 'page_view_item_with_reviews']);
		add_submenu_page('view', 'Viewer',    '<div class="dashicons-before dashicons-exerpt-view"   style="display:inline">&nbsp;</div> Viewer',     'edit_posts', 'view_review',   ['PAG_Item_Bulkviewer', 'page_view_review']);
		add_submenu_page('view', 'Viewer',    '<div class="dashicons-before dashicons-exerpt-view"   style="display:inline">&nbsp;</div> Viewer',     'edit_posts', 'view_learnout', ['PAG_Learnout_Bulkviewer', 'page_view_learnout']);
		add_submenu_page('view', 'Viewer', '<div class="dashicons-before dashicons-upload" style="display:inline">&nbsp;</div> Import', 'edit_posts', 'import', ['Importer', 'createPage']);
		
		
		// Menu: Analytics
		$menuslug = 'edit.php?post_type=testresult';
		add_menu_page('eal_page_analytics', 'Analytics', 'edit_posts', $menuslug, '', 'dashicons-analytics', 35);
		add_submenu_page($menuslug, 'Results',      '<div class="dashicons-before dashicons-feedback"  style="display:inline">&nbsp;</div> Results',     'edit_posts', 'edit.php?post_type=testresult');
		add_submenu_page($menuslug, 'Results2',      '<div class="dashicons-before dashicons-feedback"  style="display:inline">&nbsp;</div> Results',     'edit_posts', 'edit.php?post_type=learnout');
		
		
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
		setAdminMenu_Upload_TestResult( $wp_admin_bar);
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
	
	?><script type="text/javascript">
		console.log("<?= site_url() ?>");
	</script><?php 
	
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
		'href' => sprintf('admin.php?page=%s&type=%s&format=%s&itemids=%s', 'download', 'item', 'ilias', $param_itemids)
	));
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_download_item',
		'title' => 'Moodle',
		'href' => sprintf('admin.php?page=%s&type=%s&format=%s&itemids=%s', 'download', 'item', 'moodle', $param_itemids)
	));
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_download_item',
		'title' => 'JSON',
		'href' => sprintf('admin.php?page=%s&type=%s&format=%s&itemids=%s', 'download', 'item', 'json', $param_itemids)
	));
	
}


function setAdminMenu_Upload_Item($wp_admin_bar) {
	
	?>
		<script type="text/javascript">
			console.log("<?= getCurrentPHPFile() ?>");
		</script>
	<?php 
	
 	if (getCurrentPHPFile() != 'edit.php') return;
	
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
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_upload_item',
		'title' => 'JSON',
		'href' => sprintf('admin.php?page=%s&post_type=%s&format=%s', 'import', 'item', 'json')
	));
	
	
	
}

function setAdminMenu_Upload_TestResult($wp_admin_bar) {
	
	if (getCurrentPHPFile() != 'edit.php') return;
	
	if ($_REQUEST['post_type'] != 'testresult') return;
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_upload_testresult',
		'title' => sprintf("<div class='wp-menu-image dashicons-before dashicons-upload'>&nbsp;%s</div>", 'Upload'),
		'href' => FALSE ) );
	
	$wp_admin_bar->add_menu( array(
		'parent' => 'eal_upload_testresult',
		'title' => 'Ilias',
		'href' => sprintf('admin.php?page=%s&post_type=%s&format=%s', 'import', 'testresult', 'ilias')
	));
}


function setAdminMenu_Download_and_Upload_Topic($wp_admin_bar) {
	
	
	if ((getCurrentPHPFile() != 'edit-tags.php') && (getCurrentPHPFile() != 'term.php')) return;
	
	$taxonomy = $_REQUEST['taxonomy'];
	$termid = (getCurrentPHPFile() == 'term.php') ? intval($_REQUEST['tag_ID']) : -1;
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_download_term',
		'title' => sprintf("<div class='wp-menu-image dashicons-before dashicons-download'>&nbsp;%s</div>", 'Download'),
		'href' => FALSE
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_download_term_txt',
		'parent' => 'eal_download_term',
		'title' => 'TXT',
		'href' => sprintf('admin.php?page=%s&type=%s&format=%s&taxonomy=%s&termid=%d', 'download', 'term', 'txt', $taxonomy, $termid)
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_download_term_json',
		'parent' => 'eal_download_term',
		'title' => 'JSON',
		'href' => sprintf('admin.php?page=%s&type=%s&format=%s&taxonomy=%s&termid=%d', 'download', 'term', 'json', $taxonomy, $termid)
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
		'href' => sprintf('admin.php?page=%s&post_type=%s&format=%s&taxonomy=%s&termid=%d', 'import', 'term', 'txt', $taxonomy, $termid)
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_upload_term_json',
		'parent' => 'eal_upload_term',
		'title' => 'JSON',
		'href' => sprintf('admin.php?page=%s&post_type=%s&format=%s&taxonomy=%s&termid=%d', 'import', 'term', 'json', $taxonomy, $termid)
	));
	
	$wp_admin_bar->add_menu( array(
		'id' => 'eal_buildindex_term',
		'title' => sprintf("<div class='wp-menu-image dashicons-before dashicons-update'>&nbsp;%s</div>", 'Build Index'),
		'href' => sprintf('admin.php?page=%s&post_type=%s&taxonomy=%s', 'index', 'term', $taxonomy)
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
		
		wp_add_dashboard_widget('dashboard_items', '<i>Item Overview</i>', function () {
			
			/* number of items, SCs, and MCs */
			?> <table border="0"> <?php 
			foreach ([
				new CPT_Item(),
				new CPT_ItemSC(),
				new CPT_ItemMC()
			] as $object) {
				$counts = $object->WPCB_count_posts(NULL, $object->getType(), NULL);
				printf('
				<tr>
					<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div> %2$s</td>
					<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
					<td align="right" style="width:10em">&nbsp;(<a href="edit.php?post_type=%3$s&post_status=pending">%5$d</a> pending review)</td>
				</tr>', $object->getDashIcon(), $object->getLabel(), $object->getType(), $counts->publish + $counts->pending + $counts->draft, $counts->pending);
			}
			printf('</table><hr>');
			
			/* number of reviews */
			printf('<table border="0">');
			$object = new CPT_Review();
			$counts = $object->WPCB_count_posts(NULL, $object->getType(), NULL);
			printf('
			<tr>
				<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div>%2$ss</td>
				<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
			</tr>', $object->getDashIcon(), $object->getLabel(), $object->getType(), $counts->publish + $counts->pending + $counts->draft);
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
				$counts = $object->WPCB_count_posts(NULL, $object->getType(), NULL);
				printf('
			<tr>
				<td style="width:11em"><div class="dashicons-before %1$s" style="display:inline">&nbsp;</div>%2$ss</td>
				<td align="right" style="width:4em"><a href="edit.php?post_type=%3$s">%4$d</a></td>
			</tr>', $object->getDashIcon(), $object->getLabel(), $object->getType(), $counts->publish + $counts->pending + $counts->draft);
				printf('</table>');
			});
	});
}


function setMainHeader() {
	
	add_action ('admin_head', function () {
		
		// standard page
		if ((getCurrentPHPFile() == 'edit.php') && (!isset ($_REQUEST['page']))) {
			
			$title = '';
			switch ($_REQUEST['post_type']) {
				case 'item': 		$title = 'All Items <a href="post-new.php?post_type=itemsc" class="page-title-action">Add Single Choice</a><a href="post-new.php?post_type=itemmc" class="page-title-action">Add Multiple Choice</a>'; break;
				case 'itemsc': 		$title = 'All Single Choice Items <a href="post-new.php?post_type=itemsc" class="page-title-action">Add Single Choice</a>'; break;
				case 'itemmc': 		$title = 'All Multiple Choice Items <a href="post-new.php?post_type=itemmc" class="page-title-action">Add Multiple Choice</a>'; break;
				case 'itembasket': 	$title = 'All Items in Basket'; break;
				case 'review': 		$title = 'All Reviews'; break;
				case 'learnout': 	$title = 'All Learning Outcomes <a href="post-new.php?post_type=learnout" class="page-title-action">Add Learning Outcome</a>'; break;
			}
		
			?>
			<script type="text/javascript">
				jQuery(document).ready( function($) { 
					jQuery(jQuery(".wrap a.page-title-action")[0]).remove();
					jQuery(jQuery(".wrap h1")[0]).replaceWith("<h1><?= addslashes($title) ?></h1>");
				});
			</script>
			<?php 
		}
		
		
		// remove header on post-pages (where we edit an individiual item / learning outcome / review) 
		if ((getCurrentPHPFile() == 'post.php') && (!isset ($_REQUEST['page']))) {
			/*
			printf ('<script type="text/javascript">');
			printf ('	jQuery(document).ready( function($) { ');
			printf ('		jQuery(jQuery(".wrap a.page-title-action")[0]).remove();');
			// 			printf ('		jQuery(jQuery(".wrap h1")[0]).replaceWith(\'<h1>%s</h1>\');', $title);
			printf ('		jQuery(".wrap h1")[0].remove();', $title);
			printf ('	});');
			printf ('</script>');
			*/
		}
	});
	
	
}


function setScreenSettings () {
	
	
	add_filter( 'screen_settings', function( $settings, WP_Screen $screen )
	{
		
		$php_page = getCurrentPHPFile();
		
		if    ((($php_page == 'admin.php') && (in_array($_REQUEST['page'], ['view_item', 'view_item_with_reviews', 'view_review']))) 
			|| (($php_page == 'edit.php')  && ($_REQUEST['page']=='view_basket')) 
		   	|| (($php_page == 'admin.php') &&(($_REQUEST['page']=='import') && ($_REQUEST['post_type']=='item') && ($_REQUEST['action']=='Upload')))
			|| (($php_page == 'admin.php') && ($_REQUEST['page']=='view_learnout'))
			
			)
		
		{

			
// 			$options = '<option value="-1" selected>[All]</option>';
// 			foreach (ItemExplorer::getItemIdsByRequest() as $index => $id) {
// 				$post = get_post($id);
// 				if ($post === NULL) continue;
// 				$options .= sprintf ('<option value="%d">%s</option>', $index, $post->post_title);
// 			}
			
			
			
			$show_MetaData_JS = " 
				var isChecked = this.checked;
				jQuery('div #postbox-container-1').each (
					function() {
						this.style.display = (isChecked) ? 'block' :  'none'; 
					}
				);";

// 				d = document.getElementById(\'itemcontainer\'); 
// 				for (x=0; x<d.children.length; x++) { 
// 					d.children[x].querySelector(\'#postbox-container-1\').style.display = (this.checked==true) ? \'block\' :  \'none\'; 
// 				} 
//				document.getElementById(\'itemstats\').querySelector(\'#postbox-container-2\').style.display = (this.checked==true) ? \'block\' :  \'none\';';
			
			$select_Item_JS = ' 

				d = document.getElementById(\'itemcontainer\'); 
				for (x=0; x<d.children.length; x++) {  
					d.children[x].style.display = ((this.value<0) || (this.value==x)) ? \'block\' :  \'none\'; 
				} 
// 				document.getElementById(\'itemstats\').style.display = (this.value<0) ? \'block\' :  \'none\';';
				
			
			return sprintf ('
				<fieldset class="metabox-prefs view-mode">
					<legend>Items</legend>
					<select id="screen_settings_item_select_list" onChange="%s"><option value="-1" selected>[All]</option></select><br />
					<label><input type="checkbox" checked onChange="%s"></input>Show Metadata</label>
				</fieldset>', $select_Item_JS, $show_MetaData_JS);
			
		}
		
		// 	$args = array(
		// 		'label' => 'Movies',
		// 		'default' => 10,
		// 		'option' => 'cmi_movies_moi'
		// 	);
		
		// 	$screen->add_option('moi', $args);
		
		
		// 	$screen->
		
		
		// 	 $amount = isset( $_GET['paged'] )
		// 	 ? filter_var(
		// 	 absint( $_GET['paged'] ),
		// 	 FILTER_SANITIZE_NUMBER_INT,
		// 	 FILTER_NULL_ON_FAILURE
		// 	 )
		// 	 : 1;
		
		// 	 $option = $screen->get_option('moi'); // , 'option');
		// 	 $per_page = get_user_meta(get_current_user_id(), $option, true);
		
		
		// 	return sprintf (
		// 		'<input type="checkbox" checked onChange="d = document.getElementById(\'itemcontainer\'); for (x=0; x<d.children.length; x++) { d.children[x].querySelector(\'#postbox-container-1\').style.display = (this.checked==true) ? \'block\' :  \'none\'; } document.getElementById(\'itemstats\').querySelector(\'#postbox-container-2\').style.display = (this.checked==true) ? \'block\' :  \'none\';"> Show Metadata</input>'
		
		// 		);
		
		
		
		
		$x = get_posts( array('numberposts' => -1, 'include' => $y, 'post_type' => NULL));
		

		
		// 	 return sprintf(
		// 	 '<label for="amount">Amount %s: %s = %s</label> '
		// 	 .'<input step="1" min="1" max="999" class="screen-per-page" name="amount" val="%d">'
		// 	 .get_submit_button( 'Set', 'secondary', 'submit-amount', false ), $screen->base, $option, $per_page,
		// 	 $amount
		// 	 );
		
	}, 10, 2 );
	
	
	add_filter('set-screen-option', function (int $value, string $option) {
		
		return $value;
		
	}, 1, 2);
		
}


function getCurrentPHPFile () {
	return array_pop (explode ('/', $_SERVER['PHP_SELF']));
}


?>