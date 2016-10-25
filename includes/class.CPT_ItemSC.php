<?php

require_once("class.CPT_Item.php");
require_once("class.EAL_ItemSC.php");



class CPT_ItemSC extends CPT_Item {
	
	
	
	public function init($args = array()) {
		$this->type = "itemsc";
		$this->label = "Single Choice";
		$this->menu_pos = 0;
		parent::init();
		unset($this->table_columns["item_type"]);
	}
	
	
	public function WPCB_wp_get_revision_ui_diff ($diff, $compare_from, $compare_to) {
	
		if (get_post ($compare_from->post_parent)->post_type != "itemsc") return $diff;
		
		$eal_From = new EAL_ItemSC();
		$eal_From->loadById($compare_from->ID);
		$eal_To = new EAL_ItemSC();
		$eal_To->loadById($compare_to->ID);
	
		$diff[0] = $eal_From->compareTitle ($eal_To);
		$diff[1] = $eal_From->compareDescription ($eal_To);
		$diff[2] = $eal_From->compareQuestion ($eal_To);
		$diff[3] = $eal_From->compareLevel ($eal_To);
		$diff[4] = $eal_From->compareAnswers ($eal_To);
	
		return $diff;
	}	
	
	public function WPCB_register_meta_box_cb () {
		
		global $item;
		$item = new EAL_ItemSC();
		$item->load();
		parent::WPCB_register_meta_box_cb();
	}
	

	
	public function WPCB_mb_answers ($post, $vars) {
	
		global $item;
	
		$b = get_taxonomy( 'topic' );
		
		
		$answerLine = '<tr>
				<td><input type="text" name="answer[]" value="%s" size="25" /></td>
				<td><input type="text" name="points[]" value="%d" size="5" /></td>
				<td>
					<button type="button" onclick="addAnswer(this);">&nbsp;+&nbsp;</button>
					&nbsp;&nbsp;
					<button class="removeanswer" type="button" onclick="removeAnswer(this);">&nbsp;-&nbsp;</button>
				</td>
			</tr>';
	
	
		?>
			<script>
	
				var $ =jQuery.noConflict();
				
				function addAnswer (e) { 
					// add new answer option after current line
					$(e).parent().parent().after('<?php echo (preg_replace("/\r\n|\r|\n/",'', sprintf($answerLine, '', 0, 0))); ?>' );
				}
	
				function removeAnswer (e) {
					// delete current answer options but make sure that header + at least one option remain
					if ($(e).parent().parent().parent().children().size() > 2) {
						$(e).parent().parent().remove();
					}
				}
				
			</script>
	
	<?php	
			
			printf ('<table>');
			printf ('<tr align="left"><th>%s</th><th>%s</th><th>%s</th></tr>', 'Antwort-Text', 'Punkte', 'Aktionen');
			foreach ($item->answers as $a) { 
				printf($answerLine, $a['answer'], $a['points']);
			}
			printf ('</table>');
	}
		
	
	
	
	public static function getHTML_Answers (EAL_ItemSC $item, $forReview = TRUE) {
		
		$res = "";
		
		if ($forReview) {
			
			foreach ($item->answers as $a) {
				$res .= sprintf('<tr align="left"><td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td><td>%s</td></tr>',
						$a['points'],	// input value
						($a['points']>0 ? 'bold' : 'normal'),	// font-weight
						$a['answer']);	// cell value
			}
			
			return sprintf ("<table style='font-size: 100%%'>%s</table>", $res);
			
		} else {
			
			foreach ($item->answers as $a) {
				$res .= sprintf ("<div style='margin-top:1em'><input type='radio' name='x'>%s</div>", $a['answer']);
			}
			return sprintf ("<form style='margin-top:1em'>%s</form>", $res);
			
		}
		
	}
	
	

}

	





?>