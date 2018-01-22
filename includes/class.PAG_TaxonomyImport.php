<?php

class PAG_Taxonomy_Import {

	
	
	public static function createPage () {
		
		
		if ($_POST['action']=='Upload') {
			//	checks for errors and that file is uploaded
			if (($_FILES['uploadedfile']['error'] == UPLOAD_ERR_OK) && (is_uploaded_file($_FILES['uploadedfile']['tmp_name']))) {
					
		
				$level = -1;
				$lastParent = array (-1 => $_POST['topicroot']);
				foreach (file ($_FILES['uploadedfile']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		
					$identSize = strlen($line) - strlen(ltrim($line));
					if ($identSize > $level) $level++;
					if ($identSize < $level) $level = max (0, $identSize);
		
					$x = wp_insert_term( utf8_encode(trim($line)), RoleTaxonomy::getCurrentRoleDomain()["name"], array('parent' => $lastParent[$level-1]) );
					$lastParent[$level] = /*($x instanceof WP_Error) ? $x->error_data['term_exists'] : */ $x['term_id'];
		
		
				}
					
			}
		}
		

		
		
?>
			
		<div class="wrap">
		
			<h1>Import Taxonomy Terms</h1>
			
			<h2>Upload Terms</h2>
			<form  enctype="multipart/form-data" action="admin.php?page=import-taxonomy" method="post">
				<table class="form-table">
					<tbody>
						<tr class="user-first-name-wrap">
							<th><label>File</label></th>
							<td><input class="menu-name regular-text menu-item-textbox input-with-default-title" name="uploadedfile" type="file" size="30" accept="text/*"></td>
						</tr>
						<tr class="user-first-name-wrap">
							<th><label>Parent</label></th>
							<td>
<?php  
								wp_dropdown_categories(array(
									'show_option_none' =>  __("None"),
									'option_none_value' => 0, 
									'taxonomy'        =>  RoleTaxonomy::getCurrentRoleDomain()["name"],
									'name'            =>  'topicroot',
									'value_field'	  =>  'id',
									'orderby'         =>  'name',
									'selected'        =>  '',
									'hierarchical'    =>  true,
									'depth'           =>  0,
									'show_count'      =>  false, // Show # listings in parens
									'hide_empty'      =>  false, // Don't show businesses w/o listings
								));
?>
							</td>
						</tr>
						<tr>
							<th>
								<input type="submit" name="action" class="button button-primary" value="Upload">
							</th>
							<td></td>
						</tr>
					</tbody>
				</table>
			</form>
			
			
			<h2>Download Topic Terms</h2>
			<form action="options.php" method="post" name="options">
				<table class="form-table">
					<tbody>
						<tr class="user-first-name-wrap">
							<th><label>Parent</label></th>
							<td>
<?php  
								wp_dropdown_categories(array(
									'show_option_none' =>  __("None"),
									'option_none_value' => 0, 
									'taxonomy'        =>  'topic',
									'name'            =>  'topicroot',
									'value_field'	  =>  'id',
									'orderby'         =>  'name',
									'selected'        =>  '',
									'hierarchical'    =>  true,
									'depth'           =>  0,
									'show_count'      =>  false, // Show # listings in parens
									'hide_empty'      =>  false, // Don't show businesses w/o listings
								));
?>
								
								
							</td>
						</tr>
						<tr>
							<th><input type="submit" name="action" class="button button-primary" value="Download"></th>
							<td></td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>	
									
			
<?php 		
		
	}
	
}

?>