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
				
				$prefix = str_repeat ("|&nbsp;&nbsp;", $level) . "+&nbsp;&nbsp;";
				
				$html  = sprintf("<div onmouseover=\"this.children[1].style.display='inline';\"  onmouseout=\"this.children[1].style.display='none';\" ");
				$html .= sprintf("style='margin-left:0em'><span>%s%s</span>", $prefix, $term->name);
				$html .= sprintf("<div style='display:none'>   <span><a href='term.php?taxonomy=topic&tag_ID=%d'>Edit</a></span></div></div>", $term->term_id);
				
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
