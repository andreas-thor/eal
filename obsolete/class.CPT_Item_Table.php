<?php 

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once ("../includes/class.EAL_ItemSC.php");
require_once ("../includes/class.EAL_ItemMC.php");
require_once ("../includes/class.EXP_Ilias.php");

class CPT_Item_Table extends WP_List_Table {

	function get_columns(){
		$columns = array(
				'cb'        => '<input type="checkbox" />',
				'title' 	=> 'Titel',
				'type'		=> 'Typ',
				'FW'    => 'FW',
				'KW'    => 'KW',
				'PW'    => 'PW',
				'points'	=> 'Punkte'
		);
		return $columns;
	}
	
	
	function column_title($item) {
		$actions = array(
				'view'   => sprintf('<a href="admin.php?page=view&itemid=%s">View</a>',$item['ID']),
				'edit'   => sprintf('<a href="post.php?action=%s&post=%s">Edit</a>','edit',$item['ID']),
				'remove' => sprintf('<a href="?page=%s&action=%s&itemid=%s">Remove from Basket</a>',$_REQUEST['page'],'removefrombasket',$item['ID'])
				
// 				http://localhost/wordpress/wp-admin/post.php?post=327&action=edit
				
// 				'delete'    => sprintf('<a href="?page=%s&action=%s&book=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
		);
	
		return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions) );
	}
	
	function get_bulk_actions() {
		$actions = array(
				'viewitems'	=> 'View items',
				'removefrombasket'    => 'Remove items from Basket',
				'exportILIAS5'	=> 'Export items to ILIAS 5'
		);
		return $actions;
	}
	
	function column_cb($item) {
		return sprintf(
				'<input type="checkbox" name="itemids[]" value="%s" />', $item['ID']
				);
	}
	
	
	function prepare_items() {
		
		$this->process_bulk_action();
		
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
// 		usort( $this->example_data, array( &$this, 'usort_reorder' ) );
// 		$this->items = $this->example_data;
		
		
		$this->items = array ();
		$itemids = get_user_meta(get_current_user_id(), 'itembasket', true);
		if ($itemids==null) $itemids = array();
		foreach ($itemids as $item_id) {
			$post = get_post($item_id);
			if ($post == null) continue;
			
			$row = array ('ID' => $item_id); 
						
			if ($post->post_type == 'itemsc') {
				$item = new EAL_ItemSC();
				$row['type'] = 'Single Choice';
			}
			
			if ($post->post_type == 'itemmc') {
				$item = new EAL_ItemMC();
				$row['type'] = 'Mutliple Choice';
			}
				
			$item->loadById($item_id);
			$row['title'] = $item->title;
			$row['FW'] = $item->level['FW'] > 0 ? EAL_Item::$level_label[$item->level['FW']-1] : '';
			$row['KW'] = $item->level['KW'] > 0 ? EAL_Item::$level_label[$item->level['KW']-1] : '';
			$row['PW'] = $item->level['PW'] > 0 ? EAL_Item::$level_label[$item->level['PW']-1] : '';
			$row['points'] = $item->getPoints();

			array_push($this->items, $row);
		}
		
		
		
	}
	
	function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'title';
		// If no order, default to asc
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $a[$orderby], $b[$orderby] );
		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}
	
	
	
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'title':
			case 'type':
			case 'FW':
			case 'KW':
			case 'PW':
			case 'points':
			case 'author':
			case 'isbn':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	function get_sortable_columns() {
		$sortable_columns = array(
				'booktitle'  => array('booktitle',false),
				'author' => array('author',false),
				'isbn'   => array('isbn',false)
		);
		return $sortable_columns;
	}
	
	
	public function process_bulk_action() {
	
		// security check!
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
	
			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];
	
			if ( ! wp_verify_nonce( $nonce, $action ) )
				wp_die( 'Nope! Security check failed!' );
	
		}
	
		$action = $this->current_action();
	
		switch ( $action ) {
	
			case 'removefrombasket':
				
				$b_old = get_user_meta(get_current_user_id(), 'itembasket', true);
				
				if ($_REQUEST['itemids']!=null) $b_new = array_diff ($b_old, $_REQUEST['itemids']);
				if ($_REQUEST['itemid']!=null) $b_new = array_diff ($b_old, array ($_REQUEST['itemid']));
				
				$x = update_user_meta( get_current_user_id(), 'itembasket', $b_new );
				return 'removefrombasket';
	
			case 'exportILIAS5':
				
				$ilias = new EXP_Ilias();
				$link = $ilias->generateExport($_REQUEST['itemids']);
				
				printf ("<h2><a href='%s'>Download</a></h2>", $link);
				
// 				echo (implode (',', $_REQUEST['itemids']) . ' sollen in das ILIAS-Format exportiert werden.');
// 				echo ("</br><a href='". plugins_url('download.php', __FILE__) . "?itemids=" . implode (',', $_REQUEST['itemids']) . "'>Download (" . count($_REQUEST['itemids']) . " Items)</a>");
				
// 				echo (plugins_url('download.php', __FILE__));
				// 				echo ("<script>window.open('http://www.colorado.edu/conflict/peace/download/peace_essay.ZIP');</script>");
// 				echo ("<script>document.location = 'data:application/octet-stream,field1%2Cfield2%0Afoo%2Cbar%0Agoo%2Cgai%0A';</script>");
				
				

				return 'exportILIAS5';
	
		
			case 'viewitems':
				
				return 'viewitems';
				
			default:
				// do nothing or something else
				return;
				break;
		}
	
		return;
	}
	
	
	public function exportILIAS5 () {
		
		
		
		
	}
}

?>