<?php

require_once ("HTML_Item.php");
require_once (__DIR__ . "/../eal/EAL_ItemMC.php");

class HTML_ItemMC  {
	
	/**
	 * 
	 * @param EAL_ItemMC $item
	 * @param int $viewType STUDENT (read only, answers only), REVIEWER (read only, points), EDITOR (editable) 
	 */
	
	public static function getHTML_Answers (EAL_ItemMC $item, int $viewType) {
	
		
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
		
		
		if ($viewType == HTML_Object::VIEW_REVIEWER) {
		
			// answers a table line with 3 columns (answers, checked points, unchecked points); points>0 in bold ...
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf('
					<tr align="left">
	                	<td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td>
	                    <td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td>
	                    <td>%s</td>
					</tr>',
					$a['positive'], ($a['positive']>$a['negative'] ? 'bold' : 'normal'),
					$a['negative'], ($a['negative']>$a['positive'] ? 'bold' : 'normal'),
					$a['answer']);
			}
			
			// ... packaged in a table
			$result = sprintf ('<table style="font-size: 100%%">%s</table>', $html_Answers);
			
			
		}
		
		
		if ($viewType == HTML_Object::VIEW_EDITOR) {
		
			// used to be inserted dynamically when new answer is added
			$answerLine = '
				<tr>
					<td><input type="text" name="answer[]" value="%s" size="25" /></td>
					<td><input type="text" name="positive[]" value="%d" size="5" /></td>
					<td><input type="text" name="negative[]" value="%d" size="5" /></td>
					<td>
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