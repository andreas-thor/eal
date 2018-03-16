<?php 


require_once ('IMEX_Object.php');


class IMEX_LearnOut extends IMEX_Object {


	
	public static function updateLearnouts (array $learnoutids): array {
		
		$result = array();
		foreach ($learnoutids as $learnoutid) {
			
			$prefix = "lo_" . $learnoutid . "_";
			
			
			$learnout_post = new EAL_LearnOut (-1, $prefix);	// learnoutid = -1 --> LOAD from post request
			
			
			$learnout = new EAL_LearnOut($learnoutid);
			$learnout->level = $learnout_post->level;			
			
			$terms = $_POST[$prefix."taxonomy"];
			
			
			$post = get_post ($learnout->getId());
			$post->post_title = $learnout->title;
			$post->post_status = "publish";
			$post->post_content = microtime();	// ensures revision
			wp_set_post_terms($learnout->getId(), $terms, $learnout->getDomain(), FALSE );
			wp_update_post ($post);
			
			$learnout->saveToDB();
			array_push ($result, $learnout->getId());
		}
		return $result;
		
	}

}
?>