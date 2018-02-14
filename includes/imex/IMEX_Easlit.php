<?php

require_once ('IMEX_Item.php');

class IMEX_Easlit extends IMEX_Item {
	 
	
	

	public function generateExportFile(array $itemids) {
		
		$this->downloadfilename = time()."_easlit";
		$this->downloadextension = "zip";
		
		$zip = new ZipArchive();
		$zip->open($this->getDownloadFullname(), ZipArchive::CREATE);
		$zip->addFromString("{$this->downloadfilename}/{$this->downloadfilename}.json", $this->createJSON($itemids));
		
		$zip->close();
		
		
		
	}
	
	public function upload(array $file) {}


	
	private function createJSON (array $itemids): string {
		
		$result = array ();
		foreach ($itemids as $item_id) {
			
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$item = EAL_Item::load($post->post_type, $item_id);
			
			array_push ($result, $item);
			
		}
		
		
		return json_encode($result);
	}
	
}

?>