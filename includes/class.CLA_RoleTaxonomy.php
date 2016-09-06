<?php

class RoleTaxonomy {
	

	public static $domains = array (
			
		"paedagogik" 	=> "Allgemeine Paedagogik",
		"topic" 	=> "Beispieltaxonomy",
		"datenbanken"	=> "Datenbanksysteme"
	);	
	
	
	
	public static function init () {

		// remove default roles
		global $wp_roles;
		foreach ($wp_roles->roles as $role => $value) {
			
			if ($role == 'administrator') continue;
			
			$pos = strpos ($role, '_');
			if ($pos != FALSE) {
// 				if (in_array (substr($role, $pos+1), array_keys (RoleTaxonomy::$domains))) continue;
			}

			remove_role ($role);
		}
		
		// register all roles
		foreach (RoleTaxonomy::$domains as $name => $label) {
			
			
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
					'show_admin_column' => true,
					'query_var' => true,
					'show_in_menu'    => true,
					// 			'rewrite' => array ( 'slug' => 'topic' ),
					'public' => TRUE,
					'rewrite' => false
			);
			
			register_taxonomy ( $name, array ('eal_itemsc', 'eal_itemmc') , $args );			
			
			
			
			add_role ('author_' . $name, 'Author @ ' . $label, array(
				'read'         => true,  
				'edit_posts'   => true,
				'delete_posts' => true,
			));
			
			add_role ('editor_' . $name, 'Editor @ ' . $label, array(
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
				"upload_files" => true
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
	
	
	public static function showCurrentRole (WP_User $user) {
	
		print ("<h2>Role Management</h2>");
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
	
	
	
	public static function getCurrentDomain () {
		
		$current_role = get_user_meta (get_current_user_id(), 'current_role', true);
		
		if ((!isset($current_role)) ||  ($current_role== "")) return array();
		if ($current_role == "administrator") return array();
		$pos = strpos($current_role, "_");
		if ($pos != FALSE) {
			$domain = substr($current_role, $pos+1);
			return array ("name" => $domain, "label" => RoleTaxonomy::$domains[$domain]);
		}
		return array();
		
	}
	
}

?>