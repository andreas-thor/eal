<?php

require_once(__DIR__ . "/../easlit_taxonomies.php");

class RoleTaxonomy {
	

	

	
	
	public static function getCurrentBasket () {
		$itemids = get_user_meta(get_current_user_id(), 'itembasket_' . RoleTaxonomy::getCurrentRoleDomain()["name"], true);
		if ($itemids == null) $itemids = array ();
		return $itemids;
	}
	
	public static function setCurrentBasket ($itemids) {
		update_user_meta (get_current_user_id(), 'itembasket_' . RoleTaxonomy::getCurrentRoleDomain()["name"], $itemids);
	}
	
	public static function getDomains() {
		return Taxonomy::$domains;
	}
	
	public static function init () {

		// remove all roles (except administrator);
		global $wp_roles;
		foreach ($wp_roles->roles as $role => $value) {
			if ($role == 'administrator') continue;
			remove_role ($role);
		}
		
		// register all roles
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
			
			
			
// 			add_role ('author_' . $name, 'Author @ ' . $label, array(
// 				'read'         => true,  
// 				'edit_posts'   => true,
// 				'delete_posts' => true,
// 			));

			
			// prefix "a_" --> author
			add_role ('a_' . $name, 'Author @ ' . $label, array(
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
					"read_review" => TRUE				
					

						
			));
			
			// prefix "e_" --> editor
			add_role ('e_' . $name, 'Editor @ ' . $label, array(
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
		
		// make sure that there is one current role
		$user = wp_get_current_user();
		$current_role = get_user_meta ($user->ID, 'current_role', true);
		if (!in_array ($current_role, $user->roles)) {
			if (count($user->roles)>0) {
				update_user_meta ($user->ID, 'current_role', array_values ($user->roles)[0]);
			}
		}
		
		
	}
	
	
	public static function showCurrentRole ($user) {
	
		print ("<h2 id='roleman'>Role Management</h2>");
		print ("<table class='form-table'><tbody><tr class='user-email-wrap'><th><label for='currentRole'>Current Role</label></th><td>");
	
		global $wp_roles;
		foreach( $user->roles as $role ) {
			printf ("<input type='radio' id='%s' name='userroles' value='%s' %s> <label for='%s'> %s</label><br/>", $role, $role, (get_user_meta ($user->ID, 'current_role', true)==$role) ? "checked" : "",  $role, $wp_roles->role_names[$role]);
		}
		print ("</fieldset></td></tr></tbody></table>");
	}
	
	
	public static function setCurrentRole ($user_id, $old_user_data ) {
		update_user_meta ($user_id, 'current_role', $_REQUEST["userroles"]);
	}
	
	
	public static function getCurrentRoleType () {

		$current_role = get_user_meta (get_current_user_id(), 'current_role', true);
		
		if ($current_role == "administrator") 		return "administrator";
		if (substr($current_role, 0, 2) == "e_") 	return "editor";
		if (substr($current_role, 0, 2) == "a_") 	return "author";
		
		return "";
	}
	
	
	public static function getCurrentRoleDomain () {
		
		$result = array ("name" => "", "label" => "");
		
		$current_role = get_user_meta (get_current_user_id(), 'current_role', true);
		if ((!isset($current_role)) ||  ($current_role== "")) return $result;
		if ($current_role == "administrator") return $result;
		
		$result["name"] = substr($current_role, 2);
		$result["label"] = RoleTaxonomy::getDomains()[$result["name"]];

		return $result;
		
	}
	
	
	/**
	 * Specifies if current user can edit item
	 * @param WP_Post $post
	 */
	public static function canEditItemPost (WP_Post $post) {
		
		if ($post->post_author == get_current_user_id()) return TRUE;	// current user
		if ($post->post_status == "draft") return FALSE;

		$current_role = get_user_meta (get_current_user_id(), 'current_role', true);
		
		if ($current_role == "administrator") 		return TRUE;	// admin
		if (substr($current_role, 0, 2) == "e_") 	return TRUE;	// editor
		if (substr($current_role, 0, 2) == "a_") 	return FALSE;	// author
		
		return FALSE;
	}
	
	
	/**
	 * Specifies if current user can edit review 
	 * @param WP_Post $post
	 */
	public static function canEditReviewPost (WP_Post $post) {
	
		if ($post->post_author == get_current_user_id()) return TRUE;	// current user
		
		$current_role = get_user_meta (get_current_user_id(), 'current_role', true);
		
		if ($current_role == "administrator") 		return TRUE;	// admin
		if (substr($current_role, 0, 2) == "e_") 	return TRUE;	// editor
		if (substr($current_role, 0, 2) == "a_") 	return FALSE;	// author
		
		return FALSE;
	}
	
}

?>