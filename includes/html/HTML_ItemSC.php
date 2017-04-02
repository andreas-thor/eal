<?php

require_once (__DIR__ . "/../eal/EAL_ItemSC.php");

class HTML_ItemSC {
	
	
	
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