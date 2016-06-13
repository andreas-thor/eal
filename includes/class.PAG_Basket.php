<?php 

class PAG_Basket {

	
	
	
	public static function load_items_callback() {
		
// 		global $wpdb; // this is how you get access to the database
	
		$items = array ();
		$itemids = get_user_meta(get_current_user_id(), 'itembasket', true);
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

			$row['dim'] = '';
			$row['level'] = 0;
				
			foreach (array ('FW', 'PW', 'KW') as $dim) {
				if ($item->level[$dim] > 0) {
					$row['dim'] = $dim;
					$row['level'] = $item->level[$dim]; 
				}
			}
			$row['points'] = $item->getPoints();
		
			array_push($items, $row);
		}
		
		
		
		$whatever = intval( $_POST['whatever'] );
	
		$whatever += 10;

		wp_send_json ($items );
		
// 		echo $whatever;
	
// 		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	
	public static function load_items_javascript () {
		
		?>
			<script type="text/javascript" >
			jQuery(document).ready(function($) {
				var data = {
						'action': 'load_items',
						'whatever': 1234
					};

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.post(ajaxurl, data, function(response) {
						alert('Got this from the server: ' + response[0]['type']);
					});

			});
			</script> <?php
			
	}
	
	
	public static function page_ist_blueprint () {
		
		
		add_action( 'admin_footer', array ('PAG_Basket', 'load_items_javascript') ); // Write our JS below here
	?>
	
		
	
	
		<form>
			 <select name="col1">
  				<option value="type">Item Typ</option>
  				<option value="dim">Dimension</option>
  				<option value="stufe">Anforderungsstufe</option>
			</select>
			<br/>
			 <select name="col2">
  				<option value="none">None</option>
  				<option value="type">Item Typ</option>
  				<option value="dim">Dimension</option>
  				<option value="stufe">Anforderungsstufe</option>
			</select>
			<br/>
			 <select name="col3">
  				<option value="none">None</option>
  				<option value="type">Item Typ</option>
  				<option value="dim">Dimension</option>
  				<option value="stufe">Anforderungsstufe</option>
			</select>
		
		</form> 
	
	
	
		<table></table>
	
	<?php 
		
	
	}
	
	
	public static function page_itembasket () {
		?>
		<div class="wrap">
		
			<h1>Item Basket</h1>
	<?php 
	
	
	
	$myListTable = new CPT_Item_Table();
	
	
	
	// echo '<div class="wrap"><h2>My List Table Test</h2>';
	$myListTable->prepare_items();
	
	?>
	<form method="post">
	<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
	<?php 
		$myListTable->search_box('search', 'search_id'); 
		$myListTable->display();
		?>
	</form>
		</div>
	<?php 		
	}


}

	?>