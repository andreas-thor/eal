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
		
		// extract uploaded zip file into main dir
		$mainDir = $this->extractZipFileToDirectory ($file['tmp_name']);
		$directories = [$mainDir];	// list of directories containing extracted zip files
		
		// check if the zip files contains other zip files; if yes: extract them
		foreach (scandir($mainDir) as $filename) {
			if (substr ($filename, -4) == '.zip') {
				$directories[] = $this->extractZipFileToDirectory ($mainDir . '/' . $filename);
			}
		}
		
		$current_item_id = 0;
		$items = [];
		
		foreach ($directories as $dir) {
		
			/* try load main document (imsmanifest.xml) */
			if (!file_exists ("{$dir}/imsmanifest.xml")) {
				continue;	// this is ok, if the main directory did contain other zips
			}

			/* get the list of items */
			$doc_imsmanifest = new DOMDocument();
			$doc_imsmanifest->loadXML(file_get_contents("{$dir}/imsmanifest.xml"));
			$itemsFromManifest = $this->parseManifest($doc_imsmanifest); // [Identifier => XML-Filename]
			
			foreach ($itemsFromManifest as $identifier => $href) {
				
				/* load item's xml file */
				$file_item = file_get_contents("{$dir}/{$href}");
				if ($file_item == false) {
					throw new Exception("Could not find item file " . $href);
				}
					
				/* parse item data */
				$doc_item = new DOMDocument();
				$doc_item->loadXML($file_item);
				$item = $this->parseItem($doc_item, $dir, --$current_item_id);
				$items[$item->getId()] = $item;
			}
			
		}
		
		return $items;
	}

	
	/**
	 * @param string $filename	zip file name
	 * @throws Exception
	 * @return string	directory where the zipfile has been extracted to
	 */
	private function extractZipFileToDirectory (string $filename): string {
		
		$zip = new ZipArchive();
		$res = $zip->open($filename);
		if (! ($res === TRUE)) {
			throw new Exception("Error when opening zip file! ErrorCode=" . $res);
		}
		$dir = sys_get_temp_dir() . "/eal" . microtime(TRUE);	// ensures a unique directory name
		$zip->extractTo($dir);
		$zip->close();
		return $dir;
	}
	
	


	/**
	 * returns map of all questions; identifier --> xml-file name
	 * identifier is the internal id for referencing questions
	 * @param DOMDocument $dom manifest XML file 
	 * @return array [identifier => xml file name]
	 */
	private function parseManifest(DOMDocument $dom): array {
		$res = array();
		foreach ($dom->documentElement->getElementsByTagName('resources')[0]->getElementsByTagName('resource') as $resource) {
			$type = $resource->getAttribute("type");
			if ($type === 'imsqti_item_xmlv2p1') { // TODO: further item types
				$res[$resource->getAttribute("identifier")] = $resource->getAttribute("href");
			}
		}
		return $res;
	}



	/**
	 *
	 * @param DOMDocument $dom
	 * @return
	 */
	private function parseItem(DOMDocument $dom, string $dir, int $default_item_id): EAL_Item {
		
		/* get item content */
		$itembody = $dom->documentElement->getElementsByTagName('itemBody')->item(0);
		if (! ($itembody instanceof DOMElement)) {
			throw new Exception('Could no found itembody.');
		}

		/* determine desription and question = innerHTML of itembody except <choiceInteraction> */
		$descques = '';
		foreach ($itembody->childNodes as $child) {
			if ($child instanceof DOMElement) {
				if ($child->tagName == 'choiceInteraction') {
					continue;
				}
			}
			$descques .= $itembody->ownerDocument->saveHTML($child);
		}
		
		/*  replace image references */
		$descques = preg_replace_callback(				
			'|(<img[^>]+)src=["\']([^"]*)["\']|',
			function ($match) use ($dir) {
				
				/* if img is stored inline (src="data:image/png;base64,iVBOR....") --> do nothing */
				if (strtolower (substr($match[2], 0, 5)) == 'data:') {
					return $match[1] . "src='" . $match[2] . "'";
				}
				
				/* generate unique filename for img */
				$filename = urlencode($match[2]);	// encode to make sure we have a vaild name without special characters
				$count = 0;
				do {
					$toFile = wp_upload_dir()['path'] . '/' . (++$count) . '_' . $filename;
				} while (file_exists($toFile));
				
				/* copy file: match[2]'s format is usually "media/xyz.jp" where "/" is encoded as %2F */
				copy($dir . '/' . urldecode($match[2]), $toFile);
				
				/* return adjusted HTML code */
				return $match[1] . "src='" . wp_upload_dir()['url']  . '/' . $count . '_' . urlencode ($filename) . "'";
			},
			$descques
			);
		
		$split = explode(EXP_Item::DESCRIPTION_QUESTION_SEPARATOR, $descques, 2); // Description and Question are separated by horizontal line; description is optional
		
		$object = [];
		$object['post_title'] = $dom->documentElement->getAttribute('title');
		$object['item_description'] = (count($split) > 1) ? $split[0] : '';
		$object['item_question'] = $split[count($split) - 1];
		
		
		/* determine item type */
		$responseDeclaration = $dom->documentElement->getElementsByTagName('responseDeclaration')->item(0);
		if ($responseDeclaration instanceof DOMElement) {
			
			if ($responseDeclaration->getAttribute('cardinality') == 'single') {
				return $this->parseItemSC($dom, new EAL_ItemSC($default_item_id), $object);
			}
			
			if ($responseDeclaration->getAttribute('cardinality') == 'multiple') {
				return $this->parseItemMC($dom, new EAL_ItemMC($default_item_id), $object);
			}
			
		}
		throw new Exception ('Could not identify item type!');
		
		
		$choiceInteraction = $itembody->getElementsByTagName('choiceInteraction')->item(0);
		if (! ($choiceInteraction instanceof DOMElement)) {
			throw new Exception('Could no found choiceInteraction.');
		}
		
	}

	private function parseItemSC(DOMDocument $dom, EAL_ItemSC $item, array $object): EAL_ItemSC {

		$xpath = new DOMXPath($dom);
		$rootNamespace = $dom->lookupNamespaceUri($dom->namespaceURI);
		$xpath->registerNamespace('x', $rootNamespace);
		
		/* get the correct answer */
		$correctAnswerIdentifier = $xpath->evaluate('/x:assessmentItem/x:responseDeclaration/x:correctResponse/x:value')[0]->nodeValue;

		/* get the max score */
		$maxscore = $xpath->evaluate('/x:assessmentItem/x:outcomeDeclaration[@identifier="MAXSCORE"]/x:defaultValue/x:value')[0]->nodeValue;
		
		/* get all answer options */
		$object['answer'] = [];
		$object['points'] = [];
		$choiceInteraction = $xpath->evaluate('/x:assessmentItem/x:itemBody/x:choiceInteraction')[0];
		if ($choiceInteraction instanceof DOMElement) {
			foreach ($choiceInteraction->getElementsByTagName('simpleChoice') as $simpleChoice) {
				if ($simpleChoice instanceof DOMElement) {
					$object['answer'][] = $this->DOMinnerHTML($simpleChoice);
					$object['points'][] = $simpleChoice->getAttribute('identifier') == $correctAnswerIdentifier ? $maxscore : 0;
				}
			}
		}
		
		$item->initFromArray($object, '', '');
		return $item;
		
	}
	
	private function parseItemMC(DOMDocument $dom, EAL_ItemMC $item, array $object): EAL_ItemMC {
		
		$xpath = new DOMXPath($dom);
		$rootNamespace = $dom->lookupNamespaceUri($dom->namespaceURI);
		$xpath->registerNamespace('x', $rootNamespace);
		
		/* get the correct answers */
		$correctAnswerIdentifiers = [];
		foreach ($xpath->evaluate('/x:assessmentItem/x:responseDeclaration/x:correctResponse/x:value') as $value) {
			if ($value instanceof DOMElement) {
				$correctAnswerIdentifiers[] = $value->nodeValue;
			}
		}

		/* get the max score */
		$maxscore = $xpath->evaluate('/x:assessmentItem/x:outcomeDeclaration[@identifier="MAXSCORE"]/x:defaultValue/x:value')[0]->nodeValue;
		
		/* get the points mappings for all answers (if available) */
		$mapEntry = $xpath->evaluate('/x:assessmentItem/x:responseDeclaration/x:mapping/x:mapEntry');
		$answersToPoints = [];
		foreach ($mapEntry as $map) {
			if ($map instanceof DOMElement) {
				$answersToPoints[$map->getAttribute('mapKey')] = intval ($map->getAttribute('mappedValue'));
			}
		}
		
		/* get the score per choice (if available) */
		$isScorePerChoice = FALSE;
		$spc = $xpath->evaluate('/x:assessmentItem/x:outcomeDeclaration[@identifier="SCORE_PER_CHOICE"]/x:defaultValue/x:value');
		if ($spc instanceof DOMNodeList) {
			if ($spc->length>0) {
				$isScorePerChoice = TRUE;
				$scorePerChoice = intval ($spc->item(0)->nodeValue);
			}
		}
		
		/* check if score reduction for wrong answer; default: false */
		$isScoreReduction = FALSE;
		$spcr = $xpath->evaluate('/x:assessmentItem/x:outcomeDeclaration[@identifier="SCORE_PER_CHOICE_REDUCTION"]/x:defaultValue/x:value');
		if ($spcr instanceof DOMNodeList) {
			if ($spcr->length>0) {
				if ($spcr->item(0)->nodeValue == 'true') {
					$isScoreReduction = TRUE;
				}
			}
		}
		
		$object['answer'] = [];
		$object['positive'] = [];
		$object['negative'] = [];

		$choiceInteraction = $xpath->evaluate('/x:assessmentItem/x:itemBody/x:choiceInteraction')[0];
		if ($choiceInteraction instanceof DOMElement) {
			
			$simpleChoiceList = $choiceInteraction->getElementsByTagName('simpleChoice');
			
			foreach ($simpleChoiceList as $simpleChoice) {
				if ($simpleChoice instanceof DOMElement) {
					
					$object['answer'][] = $this->DOMinnerHTML($simpleChoice);
					$identifier = $simpleChoice->getAttribute('identifier');
					
					if (array_key_exists ($identifier, $answersToPoints)) {
						// we have an explicit mapping answer-checked -> points
						$object['positive'][] = $answersToPoints[$identifier];
						$object['negative'][] = 0;
					} else {
						if ($isScorePerChoice) {
							// each answer get the score per choice
							if (in_array($identifier, $correctAnswerIdentifiers)) {
								// correct answer
								$object['positive'][] = $scorePerChoice;
								$object['negative'][] = $isScoreReduction ? -$scorePerChoice : 0;
							} else {
								// wrong answer (points for not clicking)
								$object['positive'][] = $isScoreReduction ? -$scorePerChoice : 0;
								$object['negative'][] = $scorePerChoice;
							}
						} else {
							/* we simulate "only completely correct answers get the maxscore"; 
							 * we might have rounding errors; we can not fully simulate this case; learner wil get points for each correct answers (when not clicking wrong answers)
							 * Example: maxscore=7; 2 out of 4 answers are correct; correct->+3; wrong->-6 */ 
							if (in_array($identifier, $correctAnswerIdentifiers)) {
								// correct answer (if clicked -> get the fraction of maxscore w.r.t. the number of correct answers) 
								$object['positive'][] = intval ($maxscore / count($correctAnswerIdentifiers));
								$object['negative'][] = 0;
							} else {
								// wrong answer (if clicked --> - "~maxscore", i.e., you cannot get an overall > 0 for this item)
								$object['positive'][] = -count($correctAnswerIdentifiers)*intval($maxscore/count($correctAnswerIdentifiers));	 
								$object['negative'][] = 0;
							}
						}
					}
				}
			}
		}
		
		$item->initFromArray($object, '', '');
		$item->maxnumber = intval($choiceInteraction->getAttribute('maxChoices'));
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