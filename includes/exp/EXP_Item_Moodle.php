<?php

require_once 'EXP_Item.php';

class EXP_Item_Moodle extends EXP_Item {
	
	// list of all possible fraction values (taken from moodle.uni-leipzig.de)
	const ALLFRACTIONS = ['100', '90', '83.33333', '80', '75', '70', '66.66667', '60', '50', '40', '33.33333', '30', '25', '20', '16.66667', '14.28571', '12.5', '11.11111', '10', '5', '0', '-5', '-10', '-11.11111', '-12.5', '-14.28571', '-16.66667', '-20', '-25', '-30', '-33.33333', '-40', '-50', '-60', '-66.66667', '-70', '-75', '-80', '-83.33333', '-90', '-100'];
	
	
	
	public function __construct() {
		parent::__construct (time() . '_moodle_from_easlit', 'xml' );
	}
		
	
	protected function generateExportFile(array $itemids) {
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
			$item = DB_Item::loadFromDB($item_id, $post->post_type);
			
			// add question of type 'MultiChoice' for this item
			if (($item instanceof EAL_ItemSC) || ($item instanceof EAL_ItemMC)) {
				$dom->documentElement->appendChild ($this->create_XMLMultiChoiceQuestionElement ($dom, $item));
			}
		}
		
		return $dom;
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
		$xmlName->appendChild ($dom->createElement('text', $item->getTitle()));
		$xmlQuestion->appendChild($xmlName);
		
		
		// <questiontext format="html"><text>description and question</text></questiontext>
		$xmlText = $dom->createElement('text');
		$xmlText->appendChild($dom->createCDATASection($this->processAllImages(wpautop($item->getDescription()) . EXP_Item::DESCRIPTION_QUESTION_SEPARATOR . wpautop($item->getQuestion()))));
		
		
		$xmlQuestiontext  = $dom->createElement('questiontext');
		$xmlQuestiontext->setAttribute('format', 'html');
		$xmlQuestiontext->appendChild ($xmlText);
		$xmlQuestion->appendChild($xmlQuestiontext);
		
		// <defaultgrade>number of maxpoints</defaultgrade>
		$xmlQuestion->appendChild ($dom->createElement('defaultgrade', $item->getPoints()));
		
		// <answer>
		$xmlAnswers = ($item instanceof EAL_ItemSC) ? $this->create_XMLSingleChoiceAnswers ($dom, $item) : $this->create_XMLMultipleChoiceAnswers ($dom, $item);
		foreach ($xmlAnswers as $xmlAnswer) {
			$xmlQuestion->appendChild($xmlAnswer);
		}
		
		
		// in addition, an MC question has the following tags: single (values: true/false), shuffleanswers (values: 1/0), answernumbering (allowed values: 'none', 'abc', 'ABCD' or '123')
		$xmlQuestion->appendChild($dom->createElement('single', ($item->getType() == EAL_ItemSC::getType()) ? 'true' : 'false'));
		$xmlQuestion->appendChild($dom->createElement('shuffleanswers', '0'));
		$xmlQuestion->appendChild($dom->createElement('answernumbering', 'none'));
		
		return $xmlQuestion;
	}
	
	
	
	private function create_XMLSingleChoiceAnswers (DOMDocument $dom, EAL_ItemSC $item): array {
		
		$xmlAnswers = array ();
		
		for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
			
			$fraction = ($item->getPoints()) == 0 ? 0 : 100*$item->getPointsChecked($index)/$item->getPoints();	// answer points in percent of overall item points
			
			$xmlAnswer  = $dom->createElement('answer');
			$xmlAnswer->setAttribute('fraction', $this->getValidFractionValue($fraction));
			$xmlAnswer->appendChild($dom->createElement('text', $item->getAnswer($index)));
			
			$xmlFeedback  = $dom->createElement('feedback');
			$xmlFeedback->setAttribute('format', 'html');
			$xmlFeedback->appendChild($dom->createElement('text'));
			$xmlAnswer->appendChild($xmlFeedback);
			
			$xmlAnswers[] = $xmlAnswer;
		}
		
		
		return $xmlAnswers;
	}
	
	
	
	private function create_XMLMultipleChoiceAnswers (DOMDocument $dom, EAL_ItemMC $item): array {
		
		// points computation in Moodle is different to Ilias/Easlit
		$sumPositivePoints = 0;
		for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
			if ($item->getPointsPos($index) > $item->getPointsNeg($index)) {
				$sumPositivePoints += $item->getPointsPos($index) - $item->getPointsNeg($index);
			}
		}
		
		$xmlAnswers = array ();
		
		for ($index=0; $index < $item->getNumberOfAnswers(); $index++) {
			$fraction = ($sumPositivePoints) == 0 ? 0 : 100*($item->getPointsPos($index) - $item->getPointsNeg($index)) / $sumPositivePoints;
			
			$xmlAnswer  = $dom->createElement('answer');
			$xmlAnswer->setAttribute('fraction', $this->getValidFractionValue($fraction));
			$xmlAnswer->appendChild($dom->createElement('text', $item->getAnswer($index)));
			
			$xmlFeedback  = $dom->createElement('feedback');
			$xmlFeedback->setAttribute('format', 'html');
			$xmlFeedback->appendChild($dom->createElement('text'));
			$xmlAnswer->appendChild($xmlFeedback);
			
			$xmlAnswers[] = $xmlAnswer;
		}
		
		return $xmlAnswers;
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
	 * @see EXP_Item::processImage()
	 */
	protected function processImage(string $src): string {
		
		$contents = @file_get_contents($src);
		if ($contents === FALSE) return '';
		
		$extension = substr ($src, -3);
		return 'data:image/' . $extension . ';base64,' . base64_encode($contents);
	}
	
	
	
}