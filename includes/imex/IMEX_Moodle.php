<?php

require_once ('IMEX_Item.php');

class IMEX_Moodle extends IMEX_Item {

	// list of all possible fraction values (taken from moodle.uni-leipzig.de)
	const ALLFRACTIONS = ['100', '90', '83.33333', '80', '75', '70', '66.66667', '60', '50', '40', '33.33333', '30', '25', '20', '16.66667', '14.28571', '12.5', '11.11111', '10', '5', '0', '-5', '-10', '-11.11111', '-12.5', '-14.28571', '-16.66667', '-20', '-25', '-30', '-33.33333', '-40', '-50', '-60', '-66.66667', '-70', '-75', '-80', '-83.33333', '-90', '-100'];
	

	protected function generateExportFile(array $itemids) {
		
		$this->downloadfilename = time().'_moodle_from_easlit';
		$this->downloadextension = 'xml';
		file_put_contents($this->getDownloadFullname(), $this->create_XMLQuizDocument ($itemids)->saveXML());
	}
	
	 
	/**
	 * https://docs.moodle.org/34/en/Moodle_XML_format#Overall_structure_of_XML_file
	 * @param array $itemids
	 * @return DOMDocument
	 */
	private function create_XMLQuizDocument (array $itemids): DOMDocument {

		$dom = DOMDocument::loadXML (
			'<?xml version="1.0" encoding="utf-8" ?>
			<quiz></quiz>
		');
		
		foreach ($itemids as $item_id) {
			
			// load item
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$item = EAL_Item::load($post->post_type, $item_id);

			// add question of type 'MultiChoice' for this item
			if (($item->getType()=='itemsc') || ($item->getType()=='itemmc')) {
				$dom->documentElement->appendChild ($this->create_XMLMultiChoiceQuestionElement ($dom, $item));
			}
		}
		
		return $dom;
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
	

	
	
	
	/**
	 * https://docs.moodle.org/34/en/Moodle_XML_format
	 * @param DOMDocument $dom
	 * @param EAL_Item $item
	 * @return DOMNode
	 */
	private function create_XMLMultiChoiceQuestionElement (DOMDocument $dom, EAL_Item $item): DOMNode {
		
		
		// <question type="multichoice">...</question>
		$xmlQuestion = $dom->createElement('question');
		$xmlQuestion->setAttribute('type', 'multichoice');


		// <name><text>title of question</text></name>
		$xmlName = $dom->createElement('name');
		$xmlName->appendChild ($dom->createElement('text', $item->title));
		$xmlQuestion->appendChild($xmlName);
		
		
		// <questiontext format="html"><text>description and question</text></questiontext>
		$xmlText = $dom->createElement('text');
		$xmlText->appendChild($dom->createCDATASection($this->processAllImages(wpautop($item->description) . self::DESCRIPTION_QUESTION_SEPARATOR . wpautop($item->question))));
		
		
		$xmlQuestiontext  = $dom->createElement('questiontext');
		$xmlQuestiontext->setAttribute('format', 'html');
		$xmlQuestiontext->appendChild ($xmlText);
		$xmlQuestion->appendChild($xmlQuestiontext);

		// <defaultgrade>number of maxpoints</defaultgrade>
		$xmlQuestion->appendChild ($dom->createElement('defaultgrade', $item->getPoints()));
		
		// <answer>
		$xmlAnswers = ($item->getType()=="itemsc") ? $this->create_XMLSingleChoiceAnswers ($dom, $item) : $this->create_XMLMultipleChoiceAnswers ($dom, $item);
		foreach ($xmlAnswers as $xmlAnswer) {
			$xmlQuestion->appendChild($xmlAnswer);
		}
		

		// in addition, an MC question has the following tags: single (values: true/false), shuffleanswers (values: 1/0), answernumbering (allowed values: 'none', 'abc', 'ABCD' or '123')
		$xmlQuestion->appendChild($dom->createElement('single', ($item->getType()=='itemsc') ? 'true' : 'false'));
		$xmlQuestion->appendChild($dom->createElement('shuffleanswers', '0'));
		$xmlQuestion->appendChild($dom->createElement('answernumbering', 'none'));
		
		return $xmlQuestion;
	}
	
	
	private function parse_XMLMultiChoiceQuestionElement (DOMDocument $dom, DOMNode $question): EAL_Item {
		
		$xpath = new DOMXPath($dom);
		
		$item = (($xpath->evaluate('./single', $question)->textContent) == 'true') ? new EAL_ItemSC() : new EAL_ItemMC();
		$a = $xpath->evaluate('./name/text', $question)[0];
		$item->title = $xpath->evaluate('./name/text', $question)[0]->textContent;
	
		$text = $xpath->evaluate('./questiontext/text', $question)[0]->textContent;
		
		// Description and Question are separated by horizontal line
		$split = explode (self::DESCRIPTION_QUESTION_SEPARATOR, $text, 2);
		if (count($split)==1) {
			$item->description = '';
			$item->question = $split[0];
		} else {
			$item->description = $split[0];
			$item->question = $split[1];
		}
		
		$points = intval($xpath->evaluate('./defaultgrade', $question)[0]->textContent);
		$item->answers = ($item->getType()=="itemsc") ? $this->parse_XMLSingleChoiceAnswers ($dom, $question, $points) : $this->parse_XMLMultiChoiceAnswers ($dom, $question, $points);
		
		return $item;
	}
	
	
	private function create_XMLSingleChoiceAnswers (DOMDocument $dom, EAL_ItemSC $item): array {
		
		$xmlAnswers = array ();
		
		foreach ($item->answers as $answer) {
			$fraction = ($item->getPoints()) == 0 ? 0 : 100*$answer['points']/$item->getPoints();	// answer points in percent of overall item points 
			
			$xmlAnswer  = $dom->createElement('answer');
			$xmlAnswer->setAttribute('fraction', $this->getValidFractionValue($fraction));
			$xmlAnswer->appendChild($dom->createElement('text', $answer['answer']));
			 
			$xmlFeedback  = $dom->createElement('feedback');
			$xmlFeedback->setAttribute('format', 'html');
			$xmlFeedback->appendChild($dom->createElement('text'));
			$xmlAnswer->appendChild($xmlFeedback);
			
			$xmlAnswers[] = $xmlAnswer;
		}
		
		
		return $xmlAnswers;
	}
	
	
	/**
	 * @param DOMDocument $dom
	 * @param DOMElement $question
	 * @param int $points overall points for this question
	 * @param EAL_ItemSC $item
	 */
	private function parse_XMLSingleChoiceAnswers (DOMDocument $dom, DOMElement $question, int $points): array  {
		
		$xpath = new DOMXPath($dom);
		
		$answers = [];
		foreach ($xpath->evaluate('./answer', $question)  as $answer) {
			
			$fraction = doubleval($answer->getAttribute('fraction'));
			$p = round ($fraction * $points / 100);		// we support int values only for points
			$answers[] = ['answer' => $xpath->evaluate('./text', $answer)[0]->textContent, 'points' => $p];
		}
		
		return $answers;
	}
	
	private function create_XMLMultipleChoiceAnswers (DOMDocument $dom, EAL_ItemMC $item): array {
		
		// points computation in Moodle is different to Ilias/Easlit
		$sumPositivePoints = 0;	
		foreach ($item->answers as $answer) {
			if ($answer['positive']>$answer['negative']) {
				$sumPositivePoints += $answer['positive']-$answer['negative'];
			}
		}
		
		$xmlAnswers = array ();
		
		foreach ($item->answers as $answer) {
			$fraction = ($sumPositivePoints) == 0 ? 0 : 100*($answer['positive']-$answer['negative'])/$sumPositivePoints;	
			
			$xmlAnswer  = $dom->createElement('answer');
			$xmlAnswer->setAttribute('fraction', $this->getValidFractionValue($fraction));
			$xmlAnswer->appendChild($dom->createElement('text', $answer['answer']));
			
			$xmlFeedback  = $dom->createElement('feedback');
			$xmlFeedback->setAttribute('format', 'html');
			$xmlFeedback->appendChild($dom->createElement('text'));
			$xmlAnswer->appendChild($xmlFeedback);
			
			$xmlAnswers[] = $xmlAnswer;
		}

		return $xmlAnswers;
	}
	
	
	private function parse_XMLMultiChoiceAnswers (DOMDocument $dom, DOMElement $question, int $points): array  {
		
		$xpath = new DOMXPath($dom);
		
		$answers = [];
		foreach ($xpath->evaluate('./answer', $question)  as $answer) {
			
			$fraction = doubleval($answer->getAttribute('fraction'));
			$p = round ($fraction * $points / 100);		// we support int values only for points
			$n = 0;
			if ($p<0) {
				$n = -$p;
				$p = 0;
			}
			$answers[] = ['answer' => $xpath->evaluate('./text', $answer)[0]->textContent, 'positive' => $p, 'negative' => $n];
		}
		
		return $answers;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see IMEX_Item::parse()
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
			throw new Exception("Could not pasre XML file");
		}
		
		return $this->parse_XMLQuizDocument($dom);
	}
	
	
	/**
	 * pick the closest fraction value from $this->allFraction (that contains all valid/possible fraction values)
	 * @param float $fraction
	 * @return string
	 */
	private function getValidFractionValue (float $fraction): string {
		
		foreach (self::ALLFRACTIONS as $index => $fracValue) {
			
			if ($fraction>=$fracValue) {
				if ($index==0) {	// we are greater than the largest value --> tage largest value
					return $fracValue;
				}
				// we are between two values; take the value we are closer to
				return ((floatval(self::ALLFRACTIONS[$index-1])-$fraction) < ($fraction-floatval($fracValue))) ? self::ALLFRACTIONS[$index-1] : $fracValue;
			}
		}
		
		return self::ALLFRACTIONS[count(self::ALLFRACTIONS)-1]; // default = last possible value
	}
	
	
	/**
	 * The image is included into the export file via data:image because Moodle does not support accompanying files (e.g., in a zip)
	 * {@inheritDoc}
	 * @see IMEX_Item::processImage()
	 */
	protected function processImage(string $src): string {
		
		$extension = substr ($src, -3);
		return 'data:image/' . $extension . ';base64,' . base64_encode(file_get_contents($src));
	}
	
}

?>