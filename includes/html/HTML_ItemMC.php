<?php

require_once ("HTML_Item.php");
require_once (__DIR__ . "/../eal/EAL_ItemMC.php");

class HTML_ItemMC  {
	
	/**
	 * 
	 * @param EAL_ItemMC $item
	 * @param int $viewType STUDENT (read only, answers only), REVIEWER (read only, points), EDITOR (editable) 
	 */
	
	public static function getHTML_Answers (EAL_ItemMC $item, int $viewType, string $prefix=""): string {
	
		
		$html_Answers = "";
		$result = "";
		
		// answer as check buttons
		if ($viewType == HTML_Object::VIEW_STUDENT) {
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf ('<div style="margin-top:1em"><input type="checkbox">%s</div>', $a['answer']);
			}
			$result = sprintf ('
					<div style="margin-top:1em">
					Minimum: <input type="text" value="%2$d" size="1" readonly/> &nbsp;&nbsp;&nbsp;
					Maximum: <input type="text" value="%3$d" size="1" readonly/>
					%1$s</div>', 
					$html_Answers,
					$item->minnumber,
					$item->maxnumber);
		}
		
		// answers as table line with 3 columns (answers, checked points, unchecked points); points>0 in bold
		if ($viewType == HTML_Object::VIEW_REVIEW) {
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf('
					<tr align="left">
						<td style="width:100%%-6em"><input type="text" value="%s" size="255" style="width:100%%; font-weight:%s" readonly/></td>
						<td style="width:3em"><input type="text" value="%d" size="1" readonly/></td>
						<td style="width:3em"><input type="text" value="%d" size="1" readonly/></td>
					</tr>',
					htmlentities($a['answer'], ENT_COMPAT | ENT_HTML401, 'UTF-8'), 
					$a['positive']>$a['negative'] ? 'bold' : 'normal',
					$a['positive'],
					$a['negative']
				);
			}

			$result = sprintf ('
					Minimum: <input type="text" value="%2$d" size="1" readonly/> &nbsp;&nbsp;&nbsp;
					Maximum: <input type="text" value="%3$d" size="1" readonly/>
					<table style="font-size: 100%%">%1$s</table>',
					$html_Answers,
					$item->minnumber,
					$item->maxnumber
					);		
		}
		
		// similar to VIEW_REVIEW, but data is sent in POST_REQUEST (because input fields have name attribute)
		if ($viewType == HTML_Object::VIEW_IMPORT) {
			foreach ($item->answers as $a) {
				$html_Answers .= sprintf('
					<tr align="left">
						<td style="width:100%%-6em"><input type="text" name="%1$sanswer[]" value="%2$s" size="255" style="width:100%%; font-weight:%3$s" readonly/></td>
						<td style="width:3em"><input type="text" name="%1$spositive[]" value="%4$d" size="1" readonly/></td>
						<td style="width:3em"><input type="text" name="%1$snegative[]" value="%5$d" size="1" readonly/></td>
					</tr>',
					$prefix, 
					htmlentities($a['answer'], ENT_COMPAT | ENT_HTML401, 'UTF-8'), 
					$a['positive']>$a['negative'] ? 'bold' : 'normal',
					$a['positive'],
					$a['negative']
				);
			}
			$result = sprintf ('
					Minimum: <input type="text" name="%1$sitem_minnumber" value="%3$d" size="1" /> &nbsp;&nbsp;&nbsp;
					Maximum: <input type="text" name="%1$sitem_maxnumber" value="%4$d" size="1" />
					<table style="font-size: 100%%">%2$s</table>', 
					$prefix,
					$html_Answers,
					$item->minnumber,
					$item->maxnumber
					);
		}
		
		
		if ($viewType == HTML_Object::VIEW_EDIT) {
		
			// used to be inserted dynamically when new answer is added
			$answerLine = '
				<tr>
					<td style="width:100%%-12em"><input type="text" name="%1$sanswer[]" value="%2$s" size="255" style="width:100%%" /></td>
					<td style="width:3em"><input type="text" name="%1$spositive[]" value="%3$d" size="1" /></td>
					<td style="width:3em"><input type="text" name="%1$snegative[]" value="%4$d" size="1" /></td>
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
					$(e).parent().parent().after('<?php echo (preg_replace("/\r\n|\r|\n/",'', sprintf($answerLine, $prefix, '', 0, 0))); ?>' );
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
				$html_Answers .= sprintf($answerLine, $prefix, htmlentities($a['answer'], ENT_COMPAT | ENT_HTML401, 'UTF-8'), $a['positive'], $a['negative']);
			}
				
			// ... packaged in a table
			$result = sprintf ('
				Minimum: <input type="text" name="%1$sitem_minnumber" value="%3$d" size="1" /> &nbsp;&nbsp;&nbsp;
				Maximum: <input type="text" name="%1$sitem_maxnumber" value="%4$d" size="1" />
				<table style="font-size:100%%">
					<tr align="left">
						<th>Antwort-Text</th>
						<th>Ausgew&auml;hlt</th>
						<th>Nicht ausgew&auml;hlt</th>
						<th>Aktionen</th>
					</tr>
					%2$s
				</table>',
				$prefix,
				$html_Answers,
				$item->minnumber,
				$item->maxnumber);
		}
		
		return $result;
	}
	
	
	public static function compareAnswers (EAL_ItemMC $old, EAL_ItemMC $new): array {
		
		$diff  = sprintf ("<table class='diff'>");
		$diff .= sprintf ("<colgroup><col class='content diffsplit left'><col class='content diffsplit middle'><col class='content diffsplit right'></colgroup>");
		$diff .= sprintf ("<tbody><tr>");
		$diff .= sprintf ("<td><div>%s</div></td><td></td>", self::compareAnswers1($new->answers, $old->answers, "deleted"));
		$diff .= sprintf ("<td><div>%s</div></td>", self::compareAnswers1($old->answers, $new->answers, "added"));
		$diff .= sprintf ("</tr></tbody></table>");
		return array ("id" => 'answers', 'name' => 'Antwortoptionen', 'diff' => $diff);
		
	}
	
	private static function compareAnswers1 (array $old, array $new, string $class): string {
		
		$res = "<table >";
		
		foreach ($old as $i => $a) {
			$res .= "<tr align='left' >";
			$bgcolor = ($new[$i]['positive'] != $a['positive']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['positive']}</td>";
			$bgcolor = ($new[$i]['negative'] != $a['negative']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='border-style:inset; border-width:1px; width:1%; padding:1px 10px 1px 10px' align='left' {$bgcolor}>{$a['negative']}</td>";
			$bgcolor = ($new[$i]['answer'] != $a['answer']) ? "class='diff-{$class}line'" : "";
			$res .= "<td style='width:98%; padding:0; padding-left:10px' align='left' {$bgcolor}>{$a['answer']}</td></tr>";
			
		}
		
		$res .= "</table></div>";
		
		return $res;
	}
	
	
}
?>