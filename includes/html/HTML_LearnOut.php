<?php

require_once (__DIR__ . "/../eal/EAL_LearnOut.php");


class HTML_Learnout {
	
	
	public static function getHTML_Metadata (EAL_LearnOut $lo) {
	
		// Level-Table
		$res  = sprintf ("<div>%s</div><br/>", HTML_Object::getLevelHTML("lo" . $lo->id, $lo->level, null, "disabled", 0, ''));
			
		// Taxonomy Terms: Name of Taxonomy and list of terms (if available)
		$res .= sprintf ("<div><b>%s</b>:", RoleTaxonomy::getDomains()[$lo->domain]);
		$terms = wp_get_post_terms( $lo->id, $lo->domain, array("fields" => "names"));
		if (count($terms)>0) {
			$res .= sprintf ("<div style='margin-left:1em'>");
			foreach ($terms as $t) {
				$res .= sprintf ("%s</br>", $t);
			}
			$res .= sprintf ("</div>");
		}
		$res .= sprintf ("</div>");
	
		return $res;
	}
	
	public static function getHTML_LearnOut (EAL_LearnOut $lo) {
	
	
		$lo_html  = sprintf ("
			<div onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\">
				<h1 style='display:inline'>%s</span></h1>
				<div style='display:none'>
					<span><a href=\"post.php?action=edit&post=%d\">Edit</a></span>
				</div>
			</div>", $lo->title, $lo->id);
		$lo_html .= sprintf ("<div>%s</div>", wpautop(stripslashes($lo->description)));
	
	
	
		return sprintf ("
				<div id='poststuff'>
					<div id='post-body' class='metabox-holder columns-2'>
						<div class='postbox-container' id='postbox-container-2'>
							<div class='meta-box-sortables ui-sortable'>
								%s
							</div>
						</div>
						<div class='postbox-container' id='postbox-container-1'>
							<div style='background-color:#FFFFFF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>
								%s
							</div>
						</div>
					</div>
				</div>"
				, $lo_html
				, self::getHTML_Metadata($lo));
			
	}
	
}
?>