<?php 

require_once ("class.EAL_Item.php");

class PAG_Metadata {

	
	private $terms;
	
	function __construct() {
		
		$this->terms = get_terms( array(
    				'taxonomy' => 'topic',
    				'hide_empty' => false,
				) );

	}
	
	private function printChildren ($parent, $level) {
		
		foreach ($this->terms as $term) {
			
			if ($term->parent == $parent) {
				
				
				/*
				function mouseOver() {
					ausgabe.innerHTML = 'Ich bin dynamisch!';
					elem.innerHTML = 'Drüber!';
				}
				
				function mouseOut() {
					ausgabe.innerHTML = ' ';
					elem.innerHTML = 'Wieder weg!';
				}
				*/
				
				
				$pages = get_posts(array(
						'post_type' => 'itemsc',
						'numberposts' => -1,
						'tax_query' => array(
								array(
										'taxonomy' => 'topic',
										'field' => 'id',
										'terms' => $term->term_id,
										'include_children' => false
								)
						)
				));
				foreach ($pages as $p) { $idlist = $idlist . "," + $p->ID; } 
				
				$idlist = "";
				$prefix = str_repeat ("|&nbsp;&nbsp;", $level) . "+&nbsp;&nbsp;";
				
				$html  = sprintf("<div style='padding:0; margin-left:%dem' onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\">", 2*$level);
// 				$html .= sprintf("<div style='border-style:solid; border-width:0 0 1px 1px; display:inline-block; width:30px;'><table style='padding:0'><tr><td>A</td></tr><tr><td>A</td></tr></table></div>");
// 				$html .= sprintf("<div style='disp<table style='padding:0'><tr><td><div style='border-style:solid; border-width:0 0 1px 1px; display:inline-block; width:30px;'></td></tr><tr><td><div style='border-style:solid; border-width:0 0 1px 1px; display:inline-block; width:30px;'></td></tr></table>");
				
// 				$html .= sprintf("<label><span style='background-color:#FFFFFF;'>%s</span></label>", $term->name);
				$html .= sprintf("<input value='%s' size='%d' readonly/>", $term->name, 50-4*$level);
				// 				$html .= sprintf("style='margin-left:0em'><span>%s%s</span>", $prefix, $term->name);
				$html .= sprintf("<div style='display:none'>   <span><a href='term.php?taxonomy=topic&tag_ID=%d'>Edit</a></span> | <span><a href=''>View Items</a></span> | <span><a href=''>Download</a></span></div>", $term->term_id);
				$html .= sprintf("</div>");
				
				print ($html);
				$this->printChildren ($term->term_id, $level+1);
			}
			
		}
		
	}
	

	
	public static function createTable () {
	
		
		?>

			<div class="wrap">
				<h1>Taxonomy</h1>
				
				<?php 
				
				
// 				$terms = wp_list_categories ( array(
//     				'taxonomy' => 'topic',
//     				'hide_empty' => false,
// 					'style' => 'list',
// 						'title_li' => '<h2>a</h2>'
// 				) );
				
// 				echo $terms;
				
				$meta = new PAG_Metadata();
				
				
				$meta->printChildren(0, 0);
// 				print_r ($meta->terms);
				
				
				
				
				?>
		
		</div>
		<?php 		
	}

}

	
	
	
	
	
	
	


?>
