<?php


require_once (__DIR__ . "/../eal/EAL_ItemMC.php");

class HTML_ItemMC {
	
	
	public static function getHTML_Answers (EAL_ItemMC $item, $forReview = TRUE) {
	
		$res = "";
	
		if ($forReview) {
	
			foreach ($item->answers as $a) {
	
				$res .= sprintf('<tr align="left">
	                           		<td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td>
	                           		<td><input type="text" value="%d" size="1" readonly style="font-weight:%s"/></td>
	                           		<td>%s</td>
								 </tr>',
						$a['positive'], ($a['positive']>$a['negative'] ? 'bold' : 'normal'),
						$a['negative'], ($a['negative']>$a['positive'] ? 'bold' : 'normal'),
						$a['answer']);
			}
				
			return sprintf ("<table style='font-size: 100%%'>%s</table>", $res);
	
		} else {
	
			foreach ($item->answers as $a) {
				$res .= sprintf ("<div style='margin-top:1em'><input type='checkbox'>%s</div>", $a['answer']);
			}
			return sprintf ("<form style='margin-top:1em'>%s</form>", $res);
	
		}
	
	}
	
}
?>