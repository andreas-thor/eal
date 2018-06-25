<?php
require_once 'IMP_Item.php';
require_once __DIR__ . '/../exp/EXP_Item.php';

class IMP_Item_ONYX extends IMP_Item {



	public function getTestData(): string {
		return '';
	}



	/**
	 *
	 * @param array $file
	 * @throws Exception
	 * @return array of EAL_Item
	 */
	public function parseItemsFromImportFile(array $file): array {
		
		// check if zip file
		if (substr($file['name'], - 4) != ".zip")
			throw new Exception("Error! File is not a zip file");
		
		// extract zip ==> $dir = directory of extracted zip
		$zip = new ZipArchive();
		$res = $zip->open($file['tmp_name']);
		if (! ($res === TRUE))
			throw new Exception("Error when opening zip file! ErrorCode=" . $res);
		$dir = sys_get_temp_dir() . "/eal" . microtime(TRUE);
		$zip->extractTo($dir);
		$zip->close();
		
		/* load main document (imsmanifest.xml) */
		$file_imsmanifest = file_get_contents("{$dir}/imsmanifest.xml");
		if ($file_imsmanifest == false)
			throw new Exception("Could not find imsmanifest.xml file");
		
		/* get the list of items */
		$doc_imsmanifest = new DOMDocument();
		$doc_imsmanifest->loadXML($file_imsmanifest);
		$itemsFromManifest = $this->parseManifest($doc_imsmanifest); // [Identifier => XML-Filename]
		
		$items = [];
		$current_item_id = 0;
		foreach ($itemsFromManifest as $identifier => $href) {
			
			/* load item's xml file */
			$file_item = file_get_contents("{$dir}/{$href}");
			if ($file_item == false)
				throw new Exception("Could not find item file " . $href);
			
			$current_item_id --;
			/* parse item data */
			$doc_item = new DOMDocument();
			$doc_item->loadXML($file_item);
			$item = $this->parseItem($doc_item, $dir, $current_item_id);
			$items[$item->getId()] = $item;
		}
		
		return $items;
	}



	/**
	 * returns map of all questions; identifier --> xml-file name
	 * identifier is the internal id for referencing questions
	 *
	 * @param DOMDocument $doc
	 */
	private function parseManifest(DOMDocument $doc): array {
		$res = array();
		foreach ($doc->documentElement->getElementsByTagName('resources')[0]->getElementsByTagName('resource') as $resource) {
			$type = $resource->getAttribute("type");
			if ($type === 'imsqti_item_xmlv2p1') { // TODO: further item types
				$res[$resource->getAttribute("identifier")] = $resource->getAttribute("href");
			}
		}
		return $res;
	}



	/**
	 *
	 * @param DOMDocument $doc
	 * @return
	 */
	private function parseItem(DOMDocument $doc, string $dir, int $default_item_id): EAL_Item {
		
		/* get item type */
		$itembody = $doc->documentElement->getElementsByTagName('itemBody')[0];
		if (! ($itembody instanceof DOMElement)) {
			throw new Exception('Could no found itembody.');
		}
		$choiceInteraction = $itembody->getElementsByTagName('choiceInteraction')[0];
		if (! ($choiceInteraction instanceof DOMElement)) {
			throw new Exception('Could no found choiceInteraction.');
		}
		
		/* generate item (SC or MS) */
		$maxChoices = intval($choiceInteraction->getAttribute('maxChoices'));
		$item = ($maxChoices == 1) ? new EAL_ItemSC($default_item_id) : new EAL_ItemMC($default_item_id);
		
		/* determine desription and question */
		$itembody->removeChild($choiceInteraction);
		$descques = $this->DOMinnerHTML($itembody);
		
		
		$descques = preg_replace_callback(				// replace image references
			'|(<img[^>]+)src=["\']([^"]*)["\']|',
			function ($match) use ($dir) {
				
				/* if img is stored inline (src="data:image/png;base64,iVBOR....") --> do nothing */
				if (strtolower (substr($match[2], 0, 5)) == 'data:') {
					return $match[1] . "src='" . $match[2] . "'";
				}
				
				/* generate unique filename for img */
				$count=0;
				$path = wp_upload_dir()["path"];
				$filename = urlencode($match[2]);
				while (file_exists($path . "/" . $filename . "_" . $count)) {
					$count++;
				}
				
				$from = $dir . '/' . urldecode($match[2]);
				$to = $path . "/" . $filename . "_" . $count;
				copy($from, $to);
				
				return $match[1] . "src='" . wp_upload_dir()["url"] . "/" . $filename . "_" . $count . "'";
			},
			$descques
			);
		
		$split = explode(EXP_Item::DESCRIPTION_QUESTION_SEPARATOR, $descques, 2); // Description and Question are separated by horizontal line; description is optional
		
		$object = [];
		$object['post_title'] = $doc->documentElement->getAttribute('title');
		$object['item_description'] = (count($split) > 1) ? $split[0] : '';
		$object['item_question'] = $split[count($split) - 1];
		
		/* get the answers */
		$correctAnswerIdentifiers = [];
		foreach ($doc->documentElement->getElementsByTagName('responseDeclaration')[0]->getElementsByTagName('correctResponse')[0]->getElementsByTagName('value') as $value) {
			if ($value instanceof DOMNode) {
				$correctAnswerIdentifiers[] = $value->nodeValue;
			}
		}
		
		$object['answer'] = [];
		if ($item instanceof EAL_ItemSC) {
			$object['points'] = [];
			foreach ($choiceInteraction->getElementsByTagName('simpleChoice') as $simpleChoice) {
				if ($simpleChoice instanceof DOMElement) {
					$object['answer'][] = $this->DOMinnerHTML($simpleChoice);
					$object['points'][] = in_array($simpleChoice->getAttribute('identifier'), $correctAnswerIdentifiers) ? 1 : 0;
				}
			}
		}
		if ($item instanceof EAL_ItemMC) {
			$object['positive'] = [];
			$object['negative'] = [];
			foreach ($choiceInteraction->getElementsByTagName('simpleChoice') as $simpleChoice) {
				if ($simpleChoice instanceof DOMElement) {
					$object['answer'][] = $this->DOMinnerHTML($simpleChoice);
					$object['positive'][] = in_array($simpleChoice->getAttribute('identifier'), $correctAnswerIdentifiers) ? 1 : 0;
					$object['negative'][] = in_array($simpleChoice->getAttribute('identifier'), $correctAnswerIdentifiers) ? 0 : 1;
				}
			}
		}
		
		$item->initFromArray($object, '', '');
		return $item;
	}



	private function DOMinnerHTML(DOMNode $element) {
		$innerHTML = "";
		$children = $element->childNodes;
		
		foreach ($children as $child) {
			$innerHTML .= $element->ownerDocument->saveHTML($child);
		}
		
		return $innerHTML;
	}
}