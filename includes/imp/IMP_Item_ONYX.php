<?php

require_once 'IMP_Item.php';
require_once __DIR__ . '/../exp/EXP_Item.php';

class IMP_Item_ONYX extends IMP_Item {
	
	
	
	
	public function getTestData (): string {
		return '';
	}
	
	
	/**
	 * 
	 * @param array $file
	 * @throws Exception
	 * @return array of EAL_Item
	 */
	public function parseItemsFromImportFile (array $file): array {
	
		// check if zip file
		if (substr ($file['name'], -4) != ".zip") throw new Exception("Error! File is not a zip file");
		
		// extract zip ==> $dir = directory of extracted zip 
		$zip = new ZipArchive;
		$res = $zip->open($file['tmp_name']);
		if (!($res === TRUE)) throw new Exception("Error when opening zip file! ErrorCode=" . $res);
		$dir = sys_get_temp_dir() . "/eal" . microtime(TRUE);
		$zip->extractTo($dir);
		$zip->close();
		
		/* load main document (imsmanifest.xml)  */
		$file_imsmanifest = file_get_contents ("{$dir}/imsmanifest.xml");
		if ($file_imsmanifest == false) throw new Exception("Could not find imsmanifest.xml file");
			
		/* get the list of items */
		$doc_imsmanifest = new DOMDocument();
		$doc_imsmanifest->loadXML($file_imsmanifest);
		$itemsFromManifest = $this->parseManifest($doc_imsmanifest);		// [Identifier => XML-Filename]
			
		$items = [];
		$current_item_id = 0;
		foreach ($itemsFromManifest as $identifier => $href) {
			
			
			/* load item's xml file */
			$file_item = file_get_contents ("{$dir}/{$href}");
			if ($file_item == false) throw new Exception("Could not find item file " . $href);
			
			$current_item_id--;
			/* parse item data */
			$doc_item = new DOMDocument();
			$doc_item->loadXML($file_item);
			$item = $this->parseItem ($doc_item, $dir, $current_item_id);
			$items[$item->getId()] = $item;
		}
		
		return $items;
		
	}
	
	
	
	/**
	 * returns map of all questions; identifier --> xml-file name
	 * identifier is the internal id for referencing questions 
	 * @param DOMDocument $doc
	 */
	private function parseManifest (DOMDocument $doc): array {
	
		$res = array ();
		$xpath = new DOMXPath($doc);
		$b = $doc->documentElement->getElementsByTagName('resources')[0]->getElementsByTagName('resource');
		$a = $xpath->query('resource');
		foreach ($b as $resource) {
			$type = $resource->getAttribute("type");
			if ($type === 'imsqti_item_xmlv2p1') {	// TODO: further item types
				$res[$resource->getAttribute("identifier")] = $resource->getAttribute("href");
			}
		}
		return $res;
	}
	
	
	/**
	 * @param DOMDocument $doc
	 * @return 
	 */
	private function parseItem (DOMDocument $doc, string $dir, int $default_item_id) : EAL_Item{
	
		$xpath = new DOMXPath($doc);

		/* get item type */
		$choiceInteraction = $xpath->evaluate('/assessmentItem/itemBody/choiceInteraction');
		if (!($choiceInteraction instanceof DOMElement)) {
			throw new Exception ('Could no found choiceInteraction.');
		}
		
		/* generate item (SC or MS) */
		$maxChoices = intval($choiceInteraction->getAttribute('maxChoices'));
		$item = ($maxChoices == 1) ? new EAL_ItemSC($default_item_id) : new EAL_ItemMC($default_item_id);
		
		$descques = '';
		$tempDoc = new DOMDocument('1.0');
		foreach ($xpath->evaluate('/assessmentItem/itemBody/') as $bodyNode) {
			if ($bodyNode instanceof DOMElement) {
				if ($bodyNode->tagName != 'choiceInteraction') {
					$descques .= $tempDoc->saveXML($bodyNode);
				}
			}
		}
		$split = explode (EXP_Item::DESCRIPTION_QUESTION_SEPARATOR, $descques, 2);	// Description and Question are separated by horizontal line; description is optional
		
		$object = [];
		$object['post_title'] = $doc->documentElement->getAttribute('title');
		$object['item_description'] = (count($split)>1) ? $split[0] : '';
		$object['item_question'] = $split[count($split)-1];
		
		$item->initFromArray($object);
		return $item;
	}
	

	
	
}