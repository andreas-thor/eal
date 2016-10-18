<?php

class PAG_Item_Import {

	
	
	public static function createPage () {
		
		if ($_POST['action']=='Upload') {
			//	checks for errors and that file is uploaded
			if (($_FILES['uploadedfile']['error'] == UPLOAD_ERR_OK) && (is_uploaded_file($_FILES['uploadedfile']['tmp_name']))) {
		
				// TODO: check file format parameter (ILIAS5, ...)
				$ilias = new EXP_Ilias();
				$ilias->import($_FILES['uploadedfile']);
		
			}
		}
		
		
?>
			
		<div class="wrap">
		
			<h1>Upload Items & Test Results</h1>
			<form  enctype="multipart/form-data" action="admin.php?page=import-items" method="post">
				<table class="form-table">
					<tbody>
						<tr>
							<th><label>File</label></th>
							<td><input class="menu-name regular-text menu-item-textbox input-with-default-title" name="uploadedfile" type="file" size="30" accept="text/*"></td>
						</tr>
						<tr>
							<th><label>Format</label></th>
							<td><select style='width:12em' name="format"><option>ILIAS 5</option></select></td>
						</tr>
						<tr>
							<th><label>Items</label></th>
							<td>
								<fieldset> 
									<input  id="items_create_and_update" type="radio" 	name="newitem" value="create_and_update" checked> 
									<label for="items_create_and_update"> Create new items and update existing items</label><br>
									 
									<input  id="items_create" type="radio"				name="newitem" value="create"> 
									<label for="items_create"> Create new items only (existing items remain unchanged)</label><br>
									
									<input  id="items_update" type="radio"				name="newitem" value="update"> 
									<label for="items_update"> Update existing items only (no new items are created)</label>
								</fieldset>
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
		</div>	
						
			
<?php 		
		
	}
	
}

?>