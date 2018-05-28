<?php

require_once 'IMP_Item.php';


class IMP_Item_Moodle extends IMP_Item {


	/**
	 * 
	 * {@inheritDoc}
	 * @see IMP_Item::parseItemsFromImportFile()
	 */
	public function parseItemsFromImportFile (array $file): array {
		
		// check for extension
		if (substr ($file['name'], -4) != ".xml") {
			throw new Exception("Error! File is not a XML file");
		}
		
		// load file
		$xmlString = file_get_contents ($file['tmp_name']);
		if ($xmlString === FALSE) {
			throw new Exception("Could not open XML file");
		}
		
		// parse XML file
		$dom = new DOMDocument();
		if ($dom->loadXML($xmlString) === FALSE) {
			throw new Exception("Could not parse XML file");
		}
		
		return $this->parse_XMLQuizDocument($dom);
	}
	
	
	
	private function parse_XMLQuizDocument (DOMDocument $dom): array {
		
		$quiz = $dom->documentElement;
		if ($quiz->tagName != 'quiz') {
			throw new Exception ('Root element must be <quiz>.');
		}
		
		$xpath = new DOMXPath($dom);
		$items = [];
		foreach ($xpath->evaluate('./question', $quiz) as $question) {
			
			if ($question->getAttribute('type') == 'multichoice') {
				$items[] = $this->parse_XMLMultiChoiceQuestionElement ($dom, $question);
			}
		}
		
		$result = [];
		foreach ($items as $idx => $item) {
			if ($item->getId()==-1) {		// adjust itemid for new item
				$item->setId(-($idx+1));
			}
			$result[$item->getId()] = $item;
			
		}
		
		return $result;
		
	}
	

	
	private function parse_XMLMultiChoiceQuestionElement (DOMDocument $dom, DOMNode $question): EAL_Item {
		
		$xpath = new DOMXPath($dom);
		
		$item = (($xpath->evaluate('./single', $question)->textContent) == 'true') ? new EAL_ItemSC() : new EAL_ItemMC();

		// Description and Question are separated by horizontal line
		$text = $xpath->evaluate('./questiontext/text', $question)[0]->textContent;
		$split = explode (EXP_Item::DESCRIPTION_QUESTION_SEPARATOR, $text, 2);
		
		$item->init($xpath->evaluate('./name/text', $question)[0]->textContent, (count($split)>1) ? $split[0] : '', $split[count($split)-1]);
		
		$points = intval($xpath->evaluate('./defaultgrade', $question)[0]->textContent);
		
		if ($item->getType()=="itemsc") {
			$this->parse_XMLSingleChoiceAnswers ($dom, $question, $item, $points);
		}
		if ($item->getType()=="itemmc") {
			$this->parse_XMLMultiChoiceAnswers ($dom, $question, $item, $points);
		}
		
		return $item;
	}
	
	
	/**
	 * @param DOMDocument $dom
	 * @param DOMElement $question
	 * @param int $points overall points for this question
	 * @param EAL_ItemSC $item
	 */
	private function parse_XMLSingleChoiceAnswers (DOMDocument $dom, DOMElement $question, EAL_ItemSC $item, int $points)  {
		
		$xpath = new DOMXPath($dom);
		
		$item->clearAnswers();
		foreach ($xpath->evaluate('./answer', $question)  as $answer) {
			
			$fraction = doubleval($answer->getAttribute('fraction'));
			$p = round ($fraction * $points / 100);		// we support int values only for points
			$item->addAnswer($xpath->evaluate('./text', $answer)[0]->textContent, $p);
		}
	}
	
	
	private function parse_XMLMultiChoiceAnswers (DOMDocument $dom, DOMElement $question, EAL_ItemMC $item, int $points)  {
		
		$xpath = new DOMXPath($dom);
		
		$item->clearAnswers();
		foreach ($xpath->evaluate('./answer', $question)  as $answer) {
			
			$fraction = doubleval($answer->getAttribute('fraction'));
			$p = round ($fraction * $points / 100);		// we support int values only for points
			$n = 0;
			if ($p<0) {
				$n = -$p;
				$p = 0;
			}
			$item->addAnswer($xpath->evaluate('./text', $answer)[0]->textContent, $p, $n);
		}
		
	}
	

	
	

	
}

?>