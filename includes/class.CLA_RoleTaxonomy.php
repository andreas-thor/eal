<?php
require_once (__DIR__ . "/../easlit_taxonomies.php");

class RoleTaxonomy {
	

	

	
	
	public static function getCurrentBasket () {
		$itemids = get_user_meta(get_current_user_id(), 'itembasket_' . RoleTaxonomy::getCurrentDomain(), true);
		if ($itemids == null) $itemids = array ();
		return $itemids;
	}
	
	public static function setCurrentBasket ($itemids) {
		update_user_meta (get_current_user_id(), 'itembasket_' . RoleTaxonomy::getCurrentDomain(), $itemids);
	}
	
	public static function getDomains() {
		return Taxonomy::$domains;
	}
	
	
	public static function get_current_user_role(): string {
		
		$result = '';
		if( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$role = (array) $user->roles;
			if (count($role)>0) $result = reset ($role);	// get the first element
		}
		
		return $result;
	}
	
	
	public static function getCurrentDomain(): string {

		$key = 'eal_current_domain';
		
		// check if set as session variable
		if ((isset($_SESSION[$key])) && ($_SESSION[$key] != '')) return $_SESSION[$key];
		
		// check if set as user metadata in DB
		$domain = get_user_meta (get_current_user_id(), $key, true);
		if (($domain instanceof string) && ($domain != '')) return $domain;

		// set default: first domain
		$domain = '';
		foreach (self::getDomains() as $name => $label) {
			$domain = $name;
			break;
		}
		
		self::set_current_domain($domain);
		return $domain;
		
	}
	
	public static function getDomainLabel ($domain) {
		return self::getDomains()[$domain];
	}
	
	
	public static function set_current_domain(string $domain) {
		
		$key = 'eal_current_domain';
		$_SESSION[$key] = $domain;
		update_user_meta(get_current_user_id(), $key, $domain);
	}
	
	private static function registerAllTaxonomies () {

		foreach (RoleTaxonomy::getDomains() as $name => $label) {
			
			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array (
				'name' => _x ( $label, 'taxonomy general name' ),
				'singular_name' => _x ( $label, 'taxonomy singular name' ),
				'search_items' => __ ( 'Search Terms' ),
				'all_items' => __ ( 'All Terms' ),
				'parent_item' => __ ( 'Parent Term' ),
				'parent_item_colon' => __ ( 'Parent Term:' ),
				'edit_item' => __ ( 'Edit Term' ),
				'update_item' => __ ( 'Update Term' ),
				'add_new_item' => __ ( 'Add New Term' ),
				'new_item_name' => __ ( 'New Term' ),
				'menu_name' => __ ( $label )
			);
			
			$args = array (
				'hierarchical' => true,
				'labels' => $labels,
				'show_ui' => true,
				'show_admin_column' => FALSE,
				'query_var' => true,
				'show_in_menu'    => true,
				'rewrite' => array ( 'slug' => $name ),
				'public' => TRUE,
				'meta_box_cb' => FALSE
				// 					'rewrite' => false
			);
			
			register_taxonomy ( $name, array ('itemsc', 'itemmc', 'learnout') , $args );
		}
	}

	
	private static function setUserRoles () {

		// remove all roles (except administrator);
		global $wp_roles;
		foreach ($wp_roles->roles as $role => $value) {
			if ($role == 'administrator') continue;
			remove_role ($role);
		}

		
		add_role ('author', 'Author', array(
			"delete_others_pages" => false,
			"delete_others_posts" => false,
			"delete_pages" => true,
			"delete_posts" => true,
			"delete_private_pages" => true,
			"delete_private_posts" => true,
			"delete_published_pages" => true,
			"delete_published_posts" => true,
			"edit_others_pages" => false,
			"edit_others_posts" => true,
			"edit_pages" => true,
			"edit_posts" => true,
			"edit_private_pages" => true,
			"edit_private_posts" => true,
			"edit_published_pages" => true,
			"edit_published_posts" => true,
			"manage_categories" => true,
			"manage_links" => true,
			"moderate_comments" => true,
			"publish_pages" => false,
			"publish_posts" => false,
			"read" => true,
			"read_private_pages" => true,
			"read_private_posts" => true,
			"unfiltered_html" => true,
			"upload_files" => true,
			
			"edit_items" => TRUE,
			"edit_others_items" => TRUE,
			"edit_published_items" => TRUE,
			"edit_private_items" => TRUE,
			"publish_items" => FALSE,
			"delete_items" => TRUE,
			"delete_others_items" => TRUE,
			"delete_published_items" => TRUE,
			"delete_private_items" => TRUE,
			"read_private_items" => TRUE,
			"edit_item" => TRUE,
			"delete_item" => TRUE,
			"read_item" => TRUE,
			
			"edit_learnouts" => TRUE,
			"edit_others_learnouts" => TRUE,
			"edit_published_learnouts" => TRUE,
			"edit_private_learnouts" => TRUE,
			"publish_learnouts" => TRUE,
			"delete_learnouts" => TRUE,
			"delete_others_learnouts" => TRUE,
			"delete_published_learnouts" => TRUE,
			"delete_private_learnouts" => TRUE,
			"read_private_learnouts" => TRUE,
			"edit_learnout" => TRUE,
			"delete_learnout" => TRUE,
			"read_learnout" => TRUE,
			
			"edit_reviews" => TRUE,
			"edit_others_reviews" => TRUE,
			"edit_published_reviews" => TRUE,
			"edit_private_reviews" => TRUE,
			"publish_reviews" => TRUE,
			"delete_reviews" => TRUE,
			"delete_others_reviews" => TRUE,
			"delete_published_reviews" => TRUE,
			"delete_private_reviews" => TRUE,
			"read_private_reviews" => TRUE,
			"edit_review" => TRUE,
			"delete_review" => TRUE,
			"read_review" => TRUE,
			
			"edit_testresults" => TRUE,
			"edit_others_testresults" => TRUE,
			"edit_published_testresults" => TRUE,
			"edit_private_testresults" => TRUE,
			"publish_testresults" => TRUE,
			"delete_testresults" => TRUE,
			"delete_others_testresults" => TRUE,
			"delete_published_testresults" => TRUE,
			"delete_private_testresults" => TRUE,
			"read_private_testresults" => TRUE,
			"edit_testresult" => TRUE,
			"delete_testresult" => TRUE,
			"read_testresult" => TRUE
		));
		
		// prefix "e_" --> editor
		add_role ('editor', 'Editor', array(
			"delete_others_pages" => true,
			"delete_others_posts" => true,
			"delete_pages" => true,
			"delete_posts" => true,
			"delete_private_pages" => true,
			"delete_private_posts" => true,
			"delete_published_pages" => true,
			"delete_published_posts" => true,
			"edit_others_pages" => true,
			"edit_others_posts" => true,
			"edit_pages" => true,
			"edit_posts" => true,
			"edit_private_pages" => true,
			"edit_private_posts" => true,
			"edit_published_pages" => true,
			"edit_published_posts" => true,
			"manage_categories" => true,
			"manage_links" => true,
			"moderate_comments" => true,
			"publish_pages" => true,
			"publish_posts" => true,
			"read" => true,
			"read_private_pages" => true,
			"read_private_posts" => true,
			"unfiltered_html" => true,
			"upload_files" => true,
			
			"edit_items" => TRUE,
			"edit_others_items" => TRUE,
			"edit_published_items" => TRUE,
			"edit_private_items" => TRUE,
			"publish_items" => TRUE,
			"delete_items" => TRUE,
			"delete_others_items" => TRUE,
			"delete_published_items" => TRUE,
			"delete_private_items" => TRUE,
			"read_private_items" => TRUE,
			"edit_item" => TRUE,
			"delete_item" => TRUE,
			"read_item" => TRUE,
			
			"edit_learnouts" => TRUE,
			"edit_others_learnouts" => TRUE,
			"edit_published_learnouts" => TRUE,
			"edit_private_learnouts" => TRUE,
			"publish_learnouts" => TRUE,
			"delete_learnouts" => TRUE,
			"delete_others_learnouts" => TRUE,
			"delete_published_learnouts" => TRUE,
			"delete_private_learnouts" => TRUE,
			"read_private_learnouts" => TRUE,
			"edit_learnout" => TRUE,
			"delete_learnout" => TRUE,
			"read_learnout" => TRUE,
			
			"edit_reviews" => TRUE,
			"edit_others_reviews" => TRUE,
			"edit_published_reviews" => TRUE,
			"edit_private_reviews" => TRUE,
			"publish_reviews" => TRUE,
			"delete_reviews" => TRUE,
			"delete_others_reviews" => TRUE,
			"delete_published_reviews" => TRUE,
			"delete_private_reviews" => TRUE,
			"read_private_reviews" => TRUE,
			"edit_review" => TRUE,
			"delete_review" => TRUE,
			"read_review" => TRUE
		));
	}
	
	
	
	public static function init () {

		self::registerAllTaxonomies();
		self::setUserRoles();
		
		
		
		// make sure that there is one current role
		$user = wp_get_current_user();
		$current_role = self::getCurrentRole($user->ID);
		if (! in_array($current_role, $user->roles)) {
			if (count($user->roles) > 0) {
				self::setCurrentRole($user->ID, array_values($user->roles)[0]);
			}
		}
		
		
	}
	
	
	public static function showCurrentRole ($user) {
	
		print ("<h2 id='roleman'>Role Management</h2>");
		print ("<table class='form-table'><tbody><tr class='user-email-wrap'><th><label for='currentRole'>Current Role</label></th><td>");
	
		global $wp_roles;
		foreach( $user->roles as $role ) {
			$checked = (self::getCurrentRole($user->ID)==$role) ? "checked" : "";
			printf ("<input type='radio' id='%s' name='userroles' value='%s' %s> <label for='%s'> %s</label><br/>", $role, $role, $checked, $role, $wp_roles->role_names[$role]);
		}
		print ("</fieldset></td></tr></tbody></table>");
	}
	
	
	public static function setCurrentRole (int $user_id, string $current_role ) {

		/* if in $_SESSION -> do nothing */
		$key = 'current_role_' . $user_id;
		if (isset($_SESSION[$key])) {
			if ($_SESSION[$key] === $current_role) {
				return;
			}
		}
		
		$_SESSION[$key] = $current_role;
		update_user_meta ($user_id, 'current_role', $current_role);
	}
	
	public static function getCurrentRole (int $user_id): string {

		$key = 'current_role_' . $user_id;
		if ((isset($_SESSION[$key])) && ($_SESSION[$key] != '')) return $_SESSION[$key];
		
		$current_role = get_user_meta ($user_id, 'current_role', true);
		if (!($current_role instanceof string)) {
			$current_role = '';
		}
		
		self::setCurrentRole($user_id, $current_role);
		return $current_role;
	}
	
	
	public static function getCurrentRoleType () {
		return self::get_current_user_role();
	}
	
	
	
	
	/**
	 * Specifies if current user can edit item
	 * @param WP_Post $post
	 */
	public static function canEditItemPost (WP_Post $post) {
		
		if ($post->post_author == get_current_user_id()) return TRUE;	// current user
		if ($post->post_status == "draft") return FALSE;

		switch (self::get_current_user_role()) {
			case 'author': return FALSE;
			case 'editor': return TRUE;
			case 'administrator': return TRUE;
			default: return FALSE;
		}
	}
	
	
	/**
	 * Specifies if current user can edit review 
	 * @param WP_Post $post
	 */
	public static function canEditReviewPost (WP_Post $post) {
	
		if ($post->post_author == get_current_user_id()) return TRUE;	// current user
		
		$current_role = self::getCurrentRole(get_current_user_id());
		
		if ($current_role == "administrator") 		return TRUE;	// admin
		if (substr($current_role, 0, 2) == "e_") 	return TRUE;	// editor
		if (substr($current_role, 0, 2) == "a_") 	return FALSE;	// author
		
		return FALSE;
	}
	
}

?>