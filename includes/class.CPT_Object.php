<?php

abstract class CPT_Object {
	
	
	public $type;
	public $label;
	public $menu_pos;
	
	/*
	 * #######################################################################
	 * post type registration; specification of page layout
	 * #######################################################################
	 */
	
	public function init($args = array()) {
		
		register_post_type( $this->type,
			array_merge (
				array(
					'labels' => array(
						'name' => $this->label,
						'singular_name' => $this->label,
						'add_new' => 'Add ' . $this->label,
						'add_new_item' => 'Add New ' . $this->label,
						'edit' => 'Edit',
						'edit_item' => 'Edit ' . $this->label,
						'new_item' => 'New ' . $this->label,
						'view' => 'View',
						'view_item' => 'View ' . $this->label,
						'search_items' => 'Search ' . $this->label,
						'not_found' => 'No Items found',
						'not_found_in_trash' => 'No Items found in Trash',
						'parent' => 'Parent Item'
					),
		
					'public' => true,
					'menu_position' => $this->menu_pos,
					'supports' => array( 'title'), // 'editor', 'comments'), // 'thumbnail', 'custom-fields' ),
					'taxonomies' => array( 'topic' ),
					// 'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
					'has_archive' => false, // false to allow for single view
					'show_in_menu'    => $this->menu_pos > 0,
					'register_meta_box_cb' => array ($this, 'WPCB_register_meta_box_cb')
				), 
				$args
			)
		);

		// TODO: Note that post ID may reference a post revision and not the last saved post. Use wp_is_post_revision() to get the ID of the real post.
		add_action ("save_post_{$this->type}", array ("eal_{$this->type}", 'save'), 10, 2);
		
		
		// Manage table of items (what columns to show; what columns are sortable
		add_filter("manage_{$this->type}_posts_columns" , array ($this, 'WPCB_manage_posts_columns'));
		add_filter("manage_edit-{$this->type}_sortable_columns", array ($this, 'WPCB_manage_edit_sortable_columns'));
		add_action("manage_{$this->type}_posts_custom_column" , array ($this, 'WPCB_manage_posts_custom_column'), 10, 2 );
		
		
		add_filter('posts_join', array ($this, 'WPCB_posts_join'));
		add_filter('posts_fields', array ($this, 'WPCB_posts_fields'), 10, 1 );
		add_filter('posts_orderby', array ($this, 'WPCB_posts_orderby'), 10, 1 );
		add_filter('posts_where', array ($this, 'WPCB_posts_where'), 10, 1 );
		
		add_action( 'restrict_manage_posts', array ($this, 'WPCB_restrict_manage_posts') );
		
		
	}		
	
	
	
	
	public function WPCB_mb_editor ($post, $vars) {
		
		$editor_settings = array(
				'media_buttons' => false,	// no media buttons
				'teeny' => true,			// minimal editor
				'quicktags' => false,		// hides Visual/Text tabs
				'textarea_rows' => 3,
				'tinymce' => true
		);
		
		echo (wp_editor(wpautop(stripslashes($vars['args']['value'])) , $vars['args']['name'], $editor_settings ));
	}
	
	
	public function WPCB_mb_level ($post, $vars) {
	
		$level = $vars['args']['level'];
		$prefix = isset ($vars['args']['prefix']) ? $vars['args']['prefix'] : "item"; 
		$disabled = isset ($vars['args']['disabled']) ? $vars['args']['disabled'] : "";
		
		$html  = "<table style='font-size:100%'><tr><td></td>";
		foreach ($level as $c => $v) {
			$html .= '<td>' . $c . '</td>';
		}
		
		$html .= '</tr>';
		
		foreach (EAL_Item::$level_label as $n => $r) {
			$html .= '<tr><td>' . ($n+1) . ". " . $r . '</td>';
			foreach ($level as $c=>$v) {
				$html .= "<td align='center'><input type='radio' id='{$prefix}_level_{$c}_{$r}' name='{$prefix}_level_{$c}' value='" . ($n+1) . "' " . (($v==$n+1)?'checked':$disabled) . "></td>";
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		
		
		
		echo $html;
	}
	
	
	

	public function WPCB_manage_posts_columns($columns) {
		return array_merge($columns, array('FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW'));
	}
	
	public function WPCB_manage_edit_sortable_columns ($columns) {
		return array_merge($columns, array('FW' => 'FW', 'KW' => 'KW', 'PW' => 'PW'));
	}
	
	
	public function WPCB_manage_posts_custom_column ( $column, $post_id ) {
	
		global $post;
	
		switch ( $column ) {
			case 'FW': echo (($post->level_FW > 0) ? EAL_Item::$level_label[$post->level_FW-1] : ''); break;
			case 'PW': echo (($post->level_PW > 0) ? EAL_Item::$level_label[$post->level_PW-1] : ''); break;
			case 'KW': echo (($post->level_KW > 0) ? EAL_Item::$level_label[$post->level_KW-1] : ''); break;
		}
	}



	public function getTopicTerm ($term, $level) {
	
		// 		$result = str_repeat ("&nbsp;", $level*2) . "+ " . $term->name;
		$result = "&nbsp;&gt;&gt;&nbsp;" . $term->name;
		foreach (get_terms ('topic', array ('parent'=> $term->term_id)) as $t) {
			$result .= /*"<br/>" . */ $this->getTopicTerm ($t, $level+1);
		}
		return $result;
	}
	
	
	

	
	public function WPCB_posts_join ($join) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$join .= " JOIN {$wpdb->prefix}eal_{$this->type} ON {$wpdb->prefix}eal_{$this->type}.id = {$wpdb->posts}.ID";
		}
		return $join;
	}
	
	

	
	
	public function WPCB_posts_fields ( $array ) {
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			$array .= ", {$wpdb->prefix}eal_{$this->type}.* ";
		}
		return $array;
	}
	
	
	
	public function WPCB_posts_orderby($orderby_statement) {
	
		global $wp_query;
		if ($wp_query->query["post_type"] == $this->type) {
			if ($wp_query->get( 'orderby' ) == "FW") $orderby_statement = "level_FW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "PW") $orderby_statement = "level_PW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "KW") $orderby_statement = "level_KW " . $wp_query->get( 'order' );
			if ($wp_query->get( 'orderby' ) == "Reviews") $orderby_statement = "reviews " . $wp_query->get( 'order' );
		}
	
		// 		$orderby_statement = "level_KW DESC";
		return $orderby_statement;
	}
	
	
	public function WPCB_posts_where($where) {
	
		global $wp_query, $wpdb;
		if ($wp_query->query["post_type"] == $this->type) {
			foreach (EAL_Item::$level_type as $lt) {
				if (isset($_REQUEST["level_{$lt}"]) && ($_REQUEST["level_{$lt}"] != '0')) {
					$where .= " AND ({$wpdb->prefix}eal_{$this->type}.level_{$lt} = {$_REQUEST["level_{$lt}"]})";
				}
			}
		}
		
		return $where;
	}
	
	
	public function WPCB_restrict_manage_posts() {
	
		global $typenow, $wp_query;
	
		// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array('topic');
	
		// must set this to the post type you want the filter(s) displayed on
		if( $typenow == $this->type ){
	
			wp_dropdown_categories(array(
					'show_option_all' =>  __("Show All Topics"),
					'taxonomy'        =>  'topic',
					'name'            =>  'topic',
					'orderby'         =>  'name',
					'selected'        =>  isset($wp_query->query['topic']) ? $wp_query->query['topic'] :'',
					'hierarchical'    =>  true,
					'depth'           =>  0,
					'value_field'	  =>  'slug',
					'show_count'      =>  true, // Show # listings in parens
					'hide_empty'      =>  false, // Don't show businesses w/o listings
			));
				
			
			foreach (EAL_Item::$level_type as $lt) {
				$selected = (isset($_REQUEST["level_{$lt}"]) && ($_REQUEST["level_{$lt}"] != '0')) ? $_REQUEST["level_{$lt}"] : 0;
				echo ("<select class='postform' name='level_{$lt}'>");
				echo ("<option value='0'>All {$lt}</option>");
				foreach(array (1, 2, 3, 4, 5, 6) as $v) {
					echo ("<option value='${v}' " . (($v==$selected)?'selected':'') . ">" . EAL_Item::$level_label[$v-1] . "</option>");
				}
				echo ("</select>");
			}
		}
	}
	
	
	
}

?>