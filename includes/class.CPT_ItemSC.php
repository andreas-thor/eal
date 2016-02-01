<?php

require_once("class.CPT_Item.php");
// require_once("class.CustomPostType.php");
require_once("class.EAL_ItemSC.php");



class CPT_ItemSC extends CPT_Item {
	

	
	public static function CPT_init($name=null, $label=null) {
		parent::CPT_init(get_class(), 'SC Question');
	}
	
	public static function CPT_save_post ($post_id, $post) {
		
		$item = parent::CPT_save_post($post_id, $post);
		global $wpdb;
		
		$wpdb->replace(
				$wpdb->prefix . 'eal_itemsc',
				$item[0],
				$item[1]
		);
	}
	
	public static function CPT_delete_post ($post_id)  {
		global $wpdb;
		$wpdb->delete( '{$wpdb->prefix}eal_itemsc', array( 'id' => $post_id ), array( '%d' ) );
	}
	
	public static function CPT_load_post ()  {
		
		global $post, $item;
		$item = new EAL_ItemSC($post);
	}
	
	
	static function CPT_add_meta_boxes($name=null, $item=null)  {
		self::CPT_load_post();
		$name = get_class();
		parent::CPT_add_meta_boxes($name);
	}
	
	
	
 	static function CPT_updated_messages( $messages ) {
		
 	}

	static function CPT_contextual_help( $contextual_help, $screen_id, $screen ) {
		
	}
}

	


?>