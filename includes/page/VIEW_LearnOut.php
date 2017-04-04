<?php 

class VIEW_LearnOut {

	

	public static function viewLearnOut () {
		
		$learnoutids = array(); 
		
		if ($_REQUEST['learnoutid'] != null) $learnoutids = [$_REQUEST['learnoutid']];
		if ($_REQUEST['learnoutids'] != null) {
			if (is_array($_REQUEST['learnoutids'])) $learnoutids = $_REQUEST['learnoutids'];
			if (is_string($_REQUEST['learnoutids'])) $learnoutids = explode (",", $_REQUEST["learnoutids"]);
		}

		
		$html_entry = "";
		$html_select = "";
		$count = 0;
		foreach ($learnoutids as $learnout_id) {
			$learnout = new EAL_LearnOut($learnout_id);
			
			$html_select .= sprintf("<option value='%d'>%s</option>", $count, $learnout->title);
			$html_entry  .= sprintf("
				<div id='poststuff'>
					<hr/>
					<div id='post-body' class='metabox-holder columns-2'>
						<div class='postbox-container' id='postbox-container-2'>
							<h1>%s</h1>%s
						</div>
						<div class='postbox-container' id='postbox-container-1'>
							<div style='background-color:#FFFFFF; margin-top:1em; padding:1em; border-width:1px; border-style:solid; border-color:#CCCCCC;'>
							%s
							</div>
						</div>
					</div>
					<br style='clear:both;'/>
				</div>"
				, $learnout->title
				, HTML_Learnout::getHTML_LearnOut($learnout)
				, HTML_Learnout::getHTML_Metadata($learnout)
			);
			
			$count++;
		}
		
		
		
		
		printf ("
			<div class='wrap'>
				<h1>Learning Outcome Viewer</h1>
				<form>
					 <select onChange='for (x=0; x<this.form.nextElementSibling.children.length; x++) {  this.form.nextElementSibling.children[x].style.display = ((this.value<0) || (this.value==x)) ? \"block\" :  \"none\"; }'>
						<option value='-1' selected>[All %d Learning Outcomes]</option>
						%s
					</select>
					<input type='checkbox' checked onChange='for (x=0; x<this.form.nextElementSibling.children.length; x++) { this.form.nextElementSibling.children[x].querySelector(\"#postbox-container-1\").style.display = (this.checked==true) ? \"block\" :  \"none\"; }'/> Show Metadata
				</form>
				<div>%s</div>
			</div>",
			count($learnoutids), $html_select, $html_entry
		);
	}
	
	
	

}


?>