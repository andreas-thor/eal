<?php

require_once ("HTML_Object.php");

require_once (__DIR__ . "/../eal/EAL_ItemSC.php");

class HTML_ItemSC  {
	
	
	/**
	 * 
	 * @param EAL_ItemSC $item
	 * @param int $viewType STUDENT (read only, answers only), REVIEWER (read only, points), EDITOR (editable) 
	 */
	
	public static function getHTML_Answers (EAL_ItemSC $item, int $viewType, string $prefix="") {
	
		$html_Answers = "";
		$result = "";
		
		// answer as radio buttons ...
		if ($viewType == HTML_Object::VIEW_STUDENT) {
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf ('<div style="margin-top:1em"><input type="radio" />%s</div>', $a['answer']);
			}
			$result = sprintf ('<div style="margin-top:1em">%s</div>', $html_Answers);
		}

		// answers a table line with 2 columns (answers, points); points>0 in bold ...
		if ($viewType == HTML_Object::VIEW_REVIEW) {
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf('
					<tr align="left">
						<td style="width:100%%-3em"><input type="text" value="%s" style="width:100%%; font-weight:%s" size="255" readonly/></td>
						<td style="width:3em"><input type="text" value="%d" size="1"  readonly /></td>
					</tr>',
					$a['answer'], 
					$a['points']>0 ? 'bold' : 'normal',
					$a['points']
				);
			}
			$result = sprintf ('<table style="font-size: 100%%">%s</table>', $html_Answers);
		}
		
		// like REVIEW, but sendable POST request data (name attribute)		
		if ($viewType == HTML_Object::VIEW_IMPORT) {
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf('
					<tr align="left">
						<td style="width:100%%-3em"><input type="text" name="%1$sanswer[]" value="%2$s" style="width:100%%; font-weight:%3$s" size="255" readonly/></td>
						<td style="width:3em"><input type="text" name="%1$spoints[]" value="%4$d" size="1"  readonly /></td>
					</tr>', 
					$prefix, 
					$a['answer'], 
					$a['points']>0 ? 'bold' : 'normal',
					$a['points'] 
				);
			}
			$result = sprintf ('<table style="font-size: 100%%">%s</table>', $html_Answers);
		} 
		
		
		if ($viewType == HTML_Object::VIEW_EDIT) {
		
			// used to be inserted dynamically when new answer is added
			$answerLine = '
				<tr>
					<td style="width:100%%-9em"><input type="text" name="%1$sanswer[]" value="%2$s" style="width:100%%" size="255"</td>
					<td style="width:3em"><input type="text" name="%1$spoints[]" value="%3$d" size="1" /></td>
					<td style="width:6em">
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
					$(e).parent().parent().after('<?php echo (preg_replace("/\r\n|\r|\n/",'', sprintf($answerLine, $prefix, '', 0))); ?>' );
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
				$html_Answers .= sprintf($answerLine, $prefix, $a['answer'], $a['points']);
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