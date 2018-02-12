<?php

require_once ('ImportExport.php');

class IMEX_Moodle extends ImportExport {
	
	
	

	public function __construct() {
		parent::__construct();
		$this->downloadextension = "xml";
	}
	
	
	public function generateExportFile(array $itemids) {
		
		$this->downloadfilename = time()."_moodle_from_easlit";
		file_put_contents($this->getDownloadFullname(), $this->createXMLQuizDocument ($itemids)->saveXML());
	}
	
	
	/**
	 * https://docs.moodle.org/34/en/Moodle_XML_format#Overall_structure_of_XML_file
	 * @param array $itemids
	 * @return DOMDocument
	 */
	private function createXMLQuizDocument (array $itemids): DOMDocument {

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
				$dom->documentElement->appendChild ($this->createXMLMultiChoiceQuestionElement ($dom, $item));
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
	private function createXMLMultiChoiceQuestionElement (DOMDocument $dom, EAL_Item $item): DOMNode {
		
		
		// <question type="multichoice">...</question>
		$xmlQuestion = $dom->createElement('question');
		$xmlQuestion->setAttribute('type', 'multichoice');


		// <name><text>title of question</text></name>
		$xmlName = $dom->createElement('name');
		$xmlName->appendChild ($dom->createElement('text', $item->title));
		$xmlQuestion->appendChild($xmlName);
		
		
		// <questiontext format="html"><text>description and question</text></questiontext>
		$xmlText = $dom->createElement('text');
		$xmlText->appendChild($dom->createCDATASection($item->description . '<hr/>' . $item->question));
		$xmlQuestiontext  = $dom->createElement('questiontext');
		$xmlQuestiontext->setAttribute('format', 'html');
		$xmlQuestiontext->appendChild ($xmlText);
		$xmlQuestion->appendChild($xmlQuestiontext);
		
		// <defaultgrade>number of maxpoints</defaultgrade>
		$xmlQuestion->appendChild ($dom->createElement('defaultgrade', $item->getPoints()));
		
		// <answer>
		$xmlAnswers = ($item->getType()=="itemsc") ? $this->createXMLSingleChoiceAnswers ($dom, $item) : $this->createXMLMultipleChoiceAnswers ($dom, $item);
		foreach ($xmlAnswers as $xmlAnswer) {
			$xmlQuestion->appendChild($xmlAnswer);
		}
		

		// in addition, an MC question has the following tags: single (values: true/false), shuffleanswers (values: 1/0), answernumbering (allowed values: 'none', 'abc', 'ABCD' or '123')
		$xmlQuestion->appendChild($dom->createElement('single', ($item->getType()=='itemsc') ? 'true' : 'false'));
		$xmlQuestion->appendChild($dom->createElement('shuffleanswers', '0'));
		$xmlQuestion->appendChild($dom->createElement('answernumbering', 'none'));
		
		return $xmlQuestion;
	}
	
	
	
	private function createXMLSingleChoiceAnswers (DOMDocument $dom, EAL_ItemSC $item): array {
		
		$xmlAnswers = array ();
		
		foreach ($item->answers as $answer) {
			$fraction = ($item->getPoints()) == 0 ? 0 : 100*$answer['points']/$item->getPoints();	// answer points in percent of overall item points 
			
			$xmlAnswer  = $dom->createElement('answer');
			$xmlAnswer->setAttribute('fraction', $fraction);
			$xmlAnswer->appendChild($dom->createElement('text', $answer['answer']));
			 
			$xmlFeedback  = $dom->createElement('feedback');
			$xmlFeedback->setAttribute('format', 'html');
			$xmlFeedback->appendChild($dom->createElement('text'));
			$xmlAnswer->appendChild($xmlFeedback);
			
			$xmlAnswers[] = $xmlAnswer;
		}
		
		
		return $xmlAnswers;
	}
	
	private function createXMLMultipleChoiceAnswers (DOMDocument $dom, EAL_ItemMC $item): array {
		
		// points computation in Moodle is different to Ilias/Easlit
		$sumPositivePoints = 0;	
		foreach ($item->answers as $answer) {
			if ($answer['positive']>$answer['negative']) {
				$sumPositivePoints += $answer['positive']-$answer['negative'];
			}
		}
// 		$factor = ($item->getPoints()) == 0 ? 0 : $sumPositivePoints / $item->getPoints();
		
		$xmlAnswers = array ();
		
		foreach ($item->answers as $answer) {
			$fraction = ($sumPositivePoints) == 0 ? 0 : 100*($answer['positive']-$answer['negative'])/$sumPositivePoints;	
			
			$xmlAnswer  = $dom->createElement('answer');
			$xmlAnswer->setAttribute('fraction', $fraction);
			$xmlAnswer->appendChild($dom->createElement('text', $answer['answer']));
			
			$xmlFeedback  = $dom->createElement('feedback');
			$xmlFeedback->setAttribute('format', 'html');
			$xmlFeedback->appendChild($dom->createElement('text'));
			$xmlAnswer->appendChild($xmlFeedback);
			
			$xmlAnswers[] = $xmlAnswer;
		}
		
		
		
		return $xmlAnswers;
	}
	
	public function import(array $file) {}
	
}

?>