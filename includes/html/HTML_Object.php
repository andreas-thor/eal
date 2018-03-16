<?php

require_once (__DIR__ . "/../eal/EAL_Item.php");


class HTML_Object {
	
	
	protected function printEditor (string $id, string $content) {
			
		$editor_settings = array(
			'media_buttons' => true,	// no media buttons
			'teeny' => true,			// minimal editor
			'quicktags' => true,		// hides Visual/Text tabs
			'textarea_rows' => 3,
			'tinymce' => true
		);
		
		// TODO: HTML Layout geht verloren!!! mit oder ohne???
		// 		echo (wp_editor($vars['args']['value'] , $vars['args']['name'], $editor_settings ));
		echo (wp_editor(wpautop(stripslashes($content)) , $id, $editor_settings ));
	}
	
	
	protected function printLevelObject ($prefix, $level, $default, bool $disabled, bool $background, string $callback) {
?>
		<script>
			function disableOtherLevels (e) {
	 			var j = jQuery.noConflict();
				// uncheck all other radio input in the table
				j(e).parent().parent().parent().parent().find("input").each ( function () {
 					if (e.id != this.id) this.checked = false;
				});
			}
		</script>
		
		<table style="font-size:100%">
			<tr>
				<td></td>
				<?php foreach ($level as $c => $v) { ?>
					<td><?php echo $c ?></td>
				<?php } ?>
			</tr>
			
			<?php foreach (EAL_Item::$level_label as $n => $r) {	// n=0..5, $r=Erinnern...Erschaffen  ?>
			<tr>
				<td><?php echo $n+1 ?>. <?php echo $r ?></td>
				<?php foreach ($level as $c=>$v) {	// c=FW,KW,PW; v=1..6 
					$bgcolor = (($default[$c]==$n+1) && ($background==1)) ? '#E0E0E0' : 'transparent';
					$checkedDisabled = (($v==$n+1) ? 'checked': ($disabled ? 'disabled' : ''));
					$name = $prefix . '_level_' . $c;
					$onClick = ($callback != '') ? sprintf ("%s (this, %d, '%s', %d, 's');", $callback, $n+1, EAL_Item::$level_label[$n], $default[$c], (($default[$c]>0) ? EAL_Item::$level_label[$default[$c]-1] : '')) : '';
				?>
					<td valign="bottom" align="left" style="padding:3px; padding-left:5px; background-color:<?php echo $bgcolor ?>">
						<input 
							type="radio" 
							id="<?php echo $name ?>_<?php echo $r ?>"  
							name="<?php echo $name ?>" 
							value="<?php echo $n+1 ?>" 
							<?php echo $checkedDisabled ?>  
							onclick="disableOtherLevels(this); <?php echo $onClick ?>"
						/>
					</td>		
				<?php } ?>
			</tr>
			<?php } ?>
		</table>
<?php
	}
	
	

		
	
	
	protected function printTopicObject (string $domain, int $id, bool $isEditable, string $prefix) { 
		
		if (!$isEditable) {
			foreach (wp_get_post_terms($id, $domain, array("fields" => "names")) as $t) {
?>
				<input type='checkbox' checked onclick='return false;'>
				<?php echo $t ?>
				<br/>
<?php 				
			}
		} else {
?>			
			<div class="categorydiv">
				<input 
					type="hidden"  
					id="<?php echo $prefix ?>domain" 
					name="<?php echo $prefix ?>%sdomain" 
					value="<?php echo $domain ?>"
				>
				<div id="topic-all" class="tabs-panel">
					<ul id="topicchecklist" data-wp-lists="list:topic" class="categorychecklist form-no-clear">
						<?php $this->printTopicHierarchy($prefix, get_terms( array('taxonomy' => $domain, 'hide_empty' => false) ), 0, wp_get_post_terms( $id, $domain, array("fields" => "ids"))) ?>
					</ul>
				</div>
			</div>	
<?php 			
		}
	}
	
	
	private function printTopicHierarchy ($prefix, $terms, $parent, $selected) {
		
		foreach ($terms as $term) {
			if ($term->parent != $parent) continue;
?>			
			<li id="<?php echo $prefix ?>-<?php echo $term->term_id ?>">
				<label class="selectit">
					<input 
						type="checkbox" 
						id="in-<?php echo $prefix ?>-<?php echo $term->term_id ?>"
						value="<?php echo $term->term_id ?>" 
						name="<?php echo $prefix ?>taxonomy[]" 
						<?php if (in_array ($term->term_id, $selected)) echo ' checked ' ?> 
					> 
					<?php echo $term->name ?>
				</label>
				<ul class="children">
					<?php $this->printTopicHierarchy($prefix, $terms, $term->term_id, $selected) ?>
				</ul>
			</li>
<?php 		}
	}
	
}

?>