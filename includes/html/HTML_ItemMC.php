<?php

require_once ("HTML_Item.php");
require_once (__DIR__ . "/../eal/EAL_ItemMC.php");

class HTML_ItemMC  {
	
	/**
	 * 
	 * @param EAL_ItemMC $item
	 * @param int $viewType STUDENT (read only, answers only), REVIEWER (read only, points), EDITOR (editable) 
	 */
	
	public static function getHTML_Answers (EAL_ItemMC $item, int $viewType, string $prefix="") {
	
		
		$html_Answers = "";
		$result = "";
		
		if ($viewType == HTML_Object::VIEW_STUDENT) {
		
			// answer as check buttons ...
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf ('
					<div style="margin-top:1em">
						<input type="checkbox">
						%s
					</div>', 
					$a['answer']);
			}
			
			// ... packaged in a form
			$result = sprintf ('<form style="margin-top:1em">%s</form>', $html_Answers);
		}
		
		
		if (($viewType == HTML_Object::VIEW_REVIEW) || ($viewType == HTML_Object::VIEW_IMPORT)) {
		
			// answers a table line with 3 columns (answers, checked points, unchecked points); points>0 in bold ...
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf('
					<tr align="left">
						<td style="width:100%%-6em"><input type="text" name="%sanswer[]" value="%s" size="255" style="width:100%%; font-weight:%s" readonly/></td>
						<td style="width:3em"><input type="text" name="%spositive[]" value="%d" size="1" readonly/></td>
						<td style="width:3em"><input type="text" name="%snegative[]" value="%d" size="1" readonly/></td>
					</tr>',
					$prefix, $a['answer'], ($a['positive']>$a['negative'] ? 'bold' : 'normal'),  
					$prefix, $a['positive'], 
					$prefix, $a['negative']
					);
			}
			
			// ... packaged in a table
			$result = sprintf ('<table style="font-size: 100%%">%s</table>', $html_Answers);
			
			
		}
		
		
		if ($viewType == HTML_Object::VIEW_EDIT) {
		
			// used to be inserted dynamically when new answer is added
			$answerLine = '
				<tr>
					<td style="width:100%%-12em"><input type="text" name="' . $prefix . 'answer[]" value="%s" size="255" style="width:100%%" /></td>
					<td style="width:3em"><input type="text" name="' . $prefix . 'positive[]" value="%d" size="1" /></td>
					<td style="width:3em"><input type="text" name="' . $prefix . 'negative[]" value="%d" size="1" /></td>
					<td style="width:6em">
						<a class="button" onclick="addAnswer(this);">&nbsp;+&nbsp;</button>
						<a class="button" onclick="removeAnswer(this);">&nbsp;-&nbsp;</button>
					</td>
				</tr>';
			
			// Javascript for + / - button interaction
			?>
			<script>
	
				var $ =jQuery.noConflict();

				// add new answer option after current line
				function addAnswer (e) { 
					$(e).parent().parent().after('<?php echo (preg_replace("/\r\n|\r|\n/",'', sprintf($answerLine, '', 0, 0))); ?>' );
				}

				// delete current answer options but make sure that header + at least one option remain
				function removeAnswer (e) {
					if ($(e).parent().parent().parent().children().size() > 2) {
						$(e).parent().parent().remove();
					}
				}
				
			</script>
			<?php	
						
			// answers as table line with 4 columns (answer, points checked, points non-checked, action buttons) ...
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf($answerLine, $a['answer'], $a['positive'], $a['negative']);
			}
				
			// ... packaged in a table
			$result = sprintf ('
				<table style="font-size:100%%">
					<tr align="left">
						<th>Antwort-Text</th>
						<th>Ausgew&auml;hlt</th>
						<th>Nicht ausgew&auml;hlt</th>
						<th>Aktionen</th>
					</tr>
					%s
				</table>',
				$html_Answers);
		}
		
		return $result;
	}
	
}
?>