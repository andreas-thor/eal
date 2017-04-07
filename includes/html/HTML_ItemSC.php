<?php

require_once ("HTML_Object.php");

require_once (__DIR__ . "/../eal/EAL_ItemSC.php");

class HTML_ItemSC  {
	
	
	/**
	 * 
	 * @param EAL_ItemSC $item
	 * @param int $viewType STUDENT (read only, answers only), REVIEWER (read only, points), EDITOR (editable) 
	 */
	
	public static function getHTML_Answers (EAL_ItemSC $item, int $viewType) {
	
		$html_Answers = "";
		$result = "";
		
		if (($viewType == HTML_Object::VIEW_STUDENT) || ($viewType == HTML_Object::VIEW_IMPORT)) {

			// answer as radio buttons ...
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf ('
					<div style="margin-top:1em">
						<input type="radio" name="x" />
						%s
					</div>'
					, $a['answer']);
			}
			
			// ... packaged in a form
			$result = sprintf ('<form style="margin-top:1em">%s</form>', $html_Answers);
		}
		
		
		if ($viewType == HTML_Object::VIEW_REVIEW) {

			// answers a table line with 2 columns (answers, points); points>0 in bold ...
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf('
					<tr align="left">
						<td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td>
						<td>%s</td>
					</tr>', 
					$a['points'], ($a['points']>0 ? 'bold' : 'normal'),	$a['answer']);
			}
				
			// ... packaged in a table
			$result = sprintf ('<table style="font-size: 100%%">%s</table>', $html_Answers);
		} 
		
		
		if ($viewType == HTML_Object::VIEW_EDIT) {
		
			// used to be inserted dynamically when new answer is added
			$answerLine = '
				<tr>
					<td><input type="text" name="answer[]" value="%s" size="25"</td>
					<td><input type="text" name="points[]" value="%d" size="5" /></td>
					<td>
						<a class="button" onclick="addAnswer(this);">&nbsp;+&nbsp;</a>
						<a class="button" onclick="removeAnswer(this);">&nbsp;-&nbsp;</a>
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

			// answers as table line with 3 columns (answer, points, action buttons) ...
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf($answerLine, $a['answer'], $a['points']);
			}
			
			// ... packaged in a table
			$result = sprintf ('
				<table style="font-size:100%%">
					<tr align="left">
						<th>Antwort-Text</th>
						<th>Punkte</th>
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