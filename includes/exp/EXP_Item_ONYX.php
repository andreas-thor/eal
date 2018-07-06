<?php

require_once 'EXP_Item.php';

class EXP_Item_ONYX extends EXP_Item {
	
	private $images = array ();
	
	
	public function __construct() {
		parent::__construct (time() . '_easlit_onyx', 'zip' );
	}
	
	protected function generateExportFile (array $itemids) {
	
		
		$zip = new ZipArchive();
		$zip->open($this->getDownloadFullname(), ZipArchive::CREATE);
		
		foreach ($itemids as $item_id) {

			$this->images = [];
			
			/* load item */
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$item = DB_Item::loadFromDB($item_id, $post->post_type);
			
			/* create a zip file for each item ... */
			$zipItemFilename = $this->getDownloadFullname() . '_' . $item_id . '.zip';
			$zipItem = new ZipArchive();
			$zipItem->open($zipItemFilename, ZipArchive::CREATE);
			$zipItem->addFromString('imsmanifest.xml', $this->createManifestFile($item->getId())->saveXML());
			$zipItem->addFromString('easlit_' . $item_id . '.xml', $this->createItemFile($item)->saveXML());

			/* add images to zip file */
			foreach ($this->images as $from => $to) {
				$contents = @file_get_contents($from);
				if ($contents === FALSE) continue;	// could not read file
				$zipItem->addFromString($to, $contents);
			}
			
			$zipItem->close();
			
			/* ... and zip all item-zip-files into a single download zip */
			$zip->addFromString('easlit_' . $item_id . '.zip', file_get_contents($zipItemFilename));
		}
		
		$zip->close();
	}
	

	
	private function createManifestFile (int $item_id): DOMDocument {
	
		return DOMDocument::loadXML (sprintf ('<?xml version="1.0" encoding="UTF-8"?>
			<manifest
				xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
				xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 http://www.imsglobal.org/xsd/qti/qtiv2p1/qtiv2p1_imscpv1p2_v1p0.xsd http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/qti/qtiv2p1/imsqti_v2p1p1.xsd http://www.imsglobal.org/xsd/imsqti_metadata_v2p1 http://www.imsglobal.org/xsd/qti/qtiv2p1/imsqti_metadata_v2p1p1.xsd http://ltsc.ieee.org/xsd/LOM http://www.imsglobal.org/xsd/imsmd_loose_v1p3p2.xsd"
				identifier="manifestID">
				<metadata>
					<schema>QTIv2.1 Package</schema>
					<schemaversion>1.0.0</schemaversion>
				</metadata>
				<organizations />
				<resources>
					<resource identifier="easlit_%1$d" type="imsqti_item_xmlv2p1" href="easlit_%1$d.xml">
						<file href="easlit_%1$d.xml" />
					</resource>
				</resources>
			</manifest>', $item_id));
	}
	
	
	private function createItemFile (EAL_Item $item): DOMDocument {

		assert(($item instanceof EAL_ItemSC) || ($item instanceof EAL_ItemMC));
		if ($item instanceof EAL_ItemSC) return $this->createItemFileSC ($item);
		if ($item instanceof EAL_ItemMC) return $this->createItemFileMC ($item);
		return NULL;
	}
	
	private function createItemFileSC (EAL_ItemSC $item): DOMDocument {
		
		$correctAnswerIdentifiers = [];	// [id]
		$answersToPoints = [];	// [id => points]
		$answers = [];	// [id => label]
		for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
			$answers['id' . $index] = $item->getAnswer($index);
			$answersToPoints['id' . $index] = $item->getPointsChecked($index);
			if ($item->getPointsChecked($index) == $item->getPoints()) {
				$correctAnswerIdentifiers[] = 'id' . $index;
			}
		}
		
		$dom = $this->createDOM('single', 1);
		$xpath = $this->createXPath($dom);
		$this->addIdAndTitle ($dom, $item->getId(), $item->getTitle());
		$this->addOverallPoints($dom, $xpath, $item->getPoints());
		$this->addCorrectAnswerIdentifiers($dom, $xpath, $correctAnswerIdentifiers);
		$this->addAnswersToPoints($dom, $xpath, $answersToPoints);
		$this->addQuestionAndAnswers ($dom, $xpath, $item->getDescription(), $item->getQuestion(), $answers);
		
		return $dom;
	}
	
	
	private function createItemFileMC (EAL_ItemMC $item): DOMDocument {
		
		$points = 0;	// we have to re-calculate the overall points
		$correctAnswerIdentifiers = [];	// [id]
		$answersToPoints = [];	// [id => points]
		$answers = [];	// [id => label]
		
		for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {

			$answers['id' . $index] = $item->getAnswer($index);
			$answersToPoints['id' . $index] = $item->getPointsPos($index) - $item->getPointsNeg($index);	
			if ($item->getPointsPos($index)>$item->getPointsNeg($index)) {
				$correctAnswerIdentifiers[] = 'id' . $index;
				$points += $answersToPoints['id' . $index];	// only correctly checked answer may get points 
			} 
		}
		
		$dom = $this->createDOM('multiple', 0);
		$xpath = $this->createXPath($dom);
		$this->addIdAndTitle ($dom, $item->getId(), $item->getTitle());
		$this->addOverallPoints($dom, $xpath, $points);
		$this->addCorrectAnswerIdentifiers($dom, $xpath, $correctAnswerIdentifiers);
		$this->addAnswersToPoints($dom, $xpath, $answersToPoints);
		$this->addQuestionAndAnswers ($dom, $xpath, $item->getDescription(), $item->getQuestion(), $answers);
		
		return $dom;
	}

	
	private function createDOM (string $cardinality, int $maxChoices): DOMDocument {
		
		$xml = sprintf('<?xml version="1.0" encoding="UTF-8"?>
			<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/qti/qtiv2p1/imsqti_v2p1p1.xsd http://www.w3.org/1998/Math/MathML http://www.w3.org/Math/XMLSchema/mathml2/mathml2.xsd">
				<responseDeclaration identifier="RESPONSE_1" cardinality="%s" baseType="identifier">
					<correctResponse>
					</correctResponse>
					<mapping defaultValue="0">
					</mapping>
				</responseDeclaration>
				<outcomeDeclaration identifier="MAXSCORE" cardinality="single" baseType="float">
					<defaultValue>
						<value></value>
					</defaultValue>
				</outcomeDeclaration>
				<itemBody>
					<choiceInteraction responseIdentifier="RESPONSE_1" shuffle="true" maxChoices="%d">
					</choiceInteraction>
				</itemBody>
			</assessmentItem>', $cardinality, $maxChoices);
		
		return DOMDocument::loadXML ($xml);
	}
	
	private function createXPath (DOMDocument $dom): DOMXPath {

		$xpath = new DOMXPath($dom);
		$rootNamespace = $dom->lookupNamespaceUri($dom->namespaceURI);
		$xpath->registerNamespace('x', $rootNamespace);
		return $xpath;
	}
	
	private function addIdAndTitle (DOMDocument $dom, int $id, string $title) {
		// set id and title
		$dom->documentElement->setAttribute('identifier', 'easlit_' . $id);
		$dom->documentElement->setAttribute('title', $title);
	}
	
	
	private function addCorrectAnswerIdentifiers (DOMDocument $dom, DOMXPath $xpath, array $correctAnswerIdentifiers) {
		
		$correctResponse = $xpath->evaluate('/x:assessmentItem/x:responseDeclaration/x:correctResponse')[0];
		assert ($correctResponse instanceof DOMElement);
		foreach ($correctAnswerIdentifiers as $id) {
			$correctResponse->appendChild  ($dom->createElement('value', $id));
		}
			
	}
	
	
	private function addOverallPoints (DOMDocument $dom, DOMXPath $xpath, int $points) {
		// set points: we have to adjus the points since we only get points for checked answers
		$maxScore = $xpath->evaluate('/x:assessmentItem/x:outcomeDeclaration[@identifier="MAXSCORE"]/x:defaultValue/x:value')[0];
		assert ($maxScore instanceof DOMElement);
		$maxScore->nodeValue = $points;
	}
	
	private function addAnswersToPoints (DOMDocument $dom, DOMXPath $xpath, array $answersToPoints) {
		
		$mapping = $xpath->evaluate('/x:assessmentItem/x:responseDeclaration/x:mapping')[0];
		assert ($mapping instanceof DOMElement);
		foreach ($answersToPoints as $id => $points) {
			$mapEntry = $dom->createElement('mapEntry');
			$mapEntry->setAttribute('mapKey', $id);
			$mapEntry->setAttribute('mappedValue', $points);
			$mapping->appendChild($mapEntry);
		}
	}
	
	private function addQuestionAndAnswers (DOMDocument $dom, DOMXPath $xpath, string $description, string $question, array $answers) {
		
		$descQuestion = $this->processAllImages($description . '<br/>' . $question);
		
		$itemBody = $xpath->evaluate('/x:assessmentItem/x:itemBody')[0];
		assert ($itemBody instanceof DOMElement);
		
		$choiceInteraction = $xpath->evaluate('/x:assessmentItem/x:itemBody/x:choiceInteraction')[0];
		assert ($choiceInteraction instanceof DOMElement);
		

		$fragment = $dom->createDocumentFragment();
		
		libxml_use_internal_errors(true);
		$isXML = simplexml_load_string("<?xml version='1.0'?><p>" . $descQuestion . "</p>");
		if ($isXML === FALSE) {
			$fragment->appendChild($dom->createCDATASection($descQuestion));
		} else {
			$fragment->appendXML($descQuestion);
		}
		
		$itemBody->insertBefore($fragment, $choiceInteraction);
		
		foreach ($answers as $id => $label) {
			$simpleChoice = $dom->createElement("simpleChoice");
			$simpleChoice->setAttribute('identifier', $id);
			$simpleChoice->appendChild ($dom->createTextNode ($label));
			$choiceInteraction->appendChild($simpleChoice);
		}
	}
		
	

	
	
	
	/**
	 * Images are replaced by new name and added to images (=> will later be added to the zip file)
	 * {@inheritDoc}
	 * @see EXP_Item::processImage()
	 */
	protected function processImage(string $src): string {
		
		$href = 'media/' . array_pop(explode ("/", $src));
		$this->images[$src] = $href;
		return urlencode($href);
	}

	
	
}