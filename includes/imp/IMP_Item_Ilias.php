<?php

require_once 'IMP_Item.php';
require_once __DIR__ . '/../exp/EXP_Item.php';

class IMP_Item_Ilias extends IMP_Item {
	
	
	
	protected $mapItemId2XMLId;
	protected $testXML = NULL;
	
	
	public function getTestData (): string {
		
		if ($this->testXML == NULL) return '';
		return json_encode([
			'format'=>'ilias',
			'testxml'=>$this->testXML,
			'mapitemid2xml'=>$this->mapItemId2XMLId
			]
		);
	}
	
	
	/**
	 * 
	 * @param array $file
	 * @throws Exception
	 * @return array of EAL_Item
	 */
	public function parseItemsFromImportFile (array $file): array {
	
		// remove extension ".zip"	==> $name = filename without extension
		if (substr ($file['name'], -4) != ".zip") throw new Exception("Error! File is not a zip file");
		$name = substr ($file['name'], 0, strlen ($file['name'])-4); 
		
		// extract zip ==> $dir = directory of extracted zip 
		$zip = new ZipArchive;
		$res = $zip->open($file['tmp_name']);
		if (!($res === TRUE)) throw new Exception("Error when opening zip file! ErrorCode=" . $res);
		$dir = sys_get_temp_dir() . "/eal" . microtime(TRUE);
		$zip->extractTo($dir);
		$zip->close();
		
		/* process main document: qpl (Question Pool) or tst (Test) */
		$file_qpl_tst = file_get_contents ("{$dir}/{$name}/{$name}.xml");
		if ($file_qpl_tst == false) throw new Exception("Could not find QPL file");
			
		/* get the list of itemids */
		$doc_qpl_tst = new DOMDocument();
		$doc_qpl_tst->loadXML($file_qpl_tst);
		$itemids = $this->parseQPL_TST($doc_qpl_tst);		// XML-ID => EAL-ID (if available)
			
		/* get the QTI document (that contains the questions) */
		$isQPL = (strpos("{$dir}/{$name}/{$name}.xml", '_qpl_') == FALSE) ? FALSE : TRUE;
		$file_qti = file_get_contents ("{$dir}/{$name}/" . str_replace( ($isQPL?'_qpl_':'_tst_'), '_qti_', $name) . ".xml"); 
		if ($file_qti == false) throw new Exception("Could not find QTI file");
		
		/* load the items based on the QTI document and the list of itemids */
		$doc_qti = new DOMDocument();
		$doc_qti->loadXML($file_qti);
		$items = $this->parseQTI($doc_qti, $dir, $name, $itemids);		// XML-ID => EAL-ID (all Items have an Id here)
			
		if (!$isQPL) {	// same XML file name for test result
			$this->testXML = "{$dir}/{$name}/"  . str_replace( '_tst_', '_results_', $name) . ".xml"; 
		}
		
		return $items;
		
	}
	
	
	
	/**
	 * returns map of all questions; qref --> pcid
	 * qref is the internal id for referencing questions between QTI and QPL files
	 * PCID is the external id (written by EAL) to match between ILAS and EAL; PCID can be an empty string if not available
	 * @param DOMDocument $doc
	 */
	private function parseQPL_TST (DOMDocument $doc): array {
	
		$res = array ();
		$xpath = new DOMXPath($doc);
		foreach ($xpath->evaluate("//PageObject/PageContent/Question") as $question) {
			$pcid = $question->parentNode->getAttribute("PCID");
			$res[$question->getAttribute("QRef")] = (substr ($pcid, 0, 4) == "EAL:") ? substr ($pcid, 4) : "";
		}
		return $res;
	}
	
	
	/**
	 *
	 * @param DOMDocument $doc
	 * @param $itemids: array (qref -> item_id) ... if item_id is available
	 * @return array (item_id -> item) 
	 */
	private function parseQTI (DOMDocument $doc, string $dir, string $name, array $itemids):array {
	
		$items = [];
		$this->mapItemId2XMLId = [];
		$root = $doc->documentElement;
		$xpath = new DOMXPath($doc);
		$countItems = 0;
		
		foreach ($xpath->evaluate("//item", $doc->documentElement) as $itemXML) {
				
			// determine ident (==item_id) if available and item type
			$item_id = $itemids [$itemXML->getAttribute("ident")];
			$item_type = "";
			foreach ($xpath->evaluate(".//qtimetadatafield[./fieldlabel='QUESTIONTYPE']/fieldentry", $itemXML) as $md) {
				if ($md->nodeValue == "SINGLE CHOICE QUESTION")  	$item_type = "itemsc";
				if ($md->nodeValue == "MULTIPLE CHOICE QUESTION")  	$item_type = "itemmc";
			}
				
			// TODO: Handling if item type not found
			if ($item_type == "") {
				unset ($itemids[$itemXML->getAttribute("ident")]);
				continue;
			}
	
			$countItems++;
			
			try {
				// try to load item from db
				$item = DB_Item::loadFromDB(intval($item_id), $item_type);
			} catch (Exception $e) {
				// initialize new item
				switch ($item_type) {
					case 'itemsc': $item = new EAL_ItemSC(-$countItems); break;
					case 'itemmc': $item = new EAL_ItemMC(-$countItems); break;
					default: continue;
				}
			}
			
			
			
			// get title and description + question
			$descques = $xpath->evaluate ("./presentation/flow/material/mattext/text()", $itemXML)[0]->wholeText;
			// TODO: Eigentlich aus MatLabel die IMG-Referenzen ziehen!!
			$descques = preg_replace_callback(				// replace image references
					'|(<img[^>]+)src=["\']([^"]*)["\']|',
					function ($match) use ($dir, $name) {
	
						/* if img is stored inline (src="data:image/png;base64,iVBOR....") --> do nothing */
						if (strtolower (substr($match[2], 0, 5)) == 'data:') {
							return $match[1] . "src='" . $match[2] . "'";
						}
	
						/* locate file */
						$entries = scandir("{$dir}/{$name}/objects/{$match[2]}/");
						$entry = $entries[count($entries)-1];
	
						/* generate unique filename for img */
						$count=0;
						$path = wp_upload_dir()["path"];
						$filename = $match[2];
						while (file_exists($path . "/" . $filename . "_" . $count)) {
							$count++;
						}
	
						$from = "{$dir}/{$name}/objects/{$entry}";
						$to = $path . "/" . $filename . "_" . $count;
						copy("{$dir}/{$name}/objects/{$match[2]}/{$entry}" , $path . "/" . $filename . "_" . $count);
		
						return $match[1] . "src='" . wp_upload_dir()["url"] . "/" . $filename . "_" . $count . "'";
					},
					$descques
					);
				
			$split = explode (EXP_Item::DESCRIPTION_QUESTION_SEPARATOR, $descques, 2);	// Description and Question are separated by horizontal line; description is optional

			$object = [];
			$object['post_title'] = $itemXML->getAttribute("title");
			$object['item_description'] = (count($split)>1) ? $split[0] : '';
			$object['item_question'] = $split[count($split)-1];
			
				
			// collect answer ids
			$answers = array ();
			foreach ($xpath->evaluate ("./presentation/flow//response_label", $itemXML) as $resp) {
				$answers[$resp->getAttribute("ident")] = array ("text" => $xpath->evaluate("./material/mattext/text()", $resp)[0]->wholeText, "positive" => 0, "negative" => 0);
			}
				
			// collect points for each answer
			foreach ($xpath->evaluate ("./resprocessing/respcondition", $itemXML) as $resp) {
				$answerId = $resp->getElementsByTagName("conditionvar")[0]->firstChild->nodeValue;
				$answerPositive = $xpath->evaluate ("./setvar[../conditionvar/varequal]/text()", $resp);
				$answerNegative = $xpath->evaluate ("./setvar[../conditionvar/not/varequal]/text()", $resp);
				if ($answerPositive->length>0) $answers[$answerId]["positive"] = $answerPositive[0]->wholeText;
				if ($answerNegative->length>0) $answers[$answerId]["negative"] = $answerNegative[0]->wholeText;
			}
	
			
			$object['answer'] = [];
			if ($item instanceof EAL_ItemSC) {
				$object['points'] = [];
				foreach ($answers as $k => $v) {
					$object['answer'][] = $v['text'];
					$object['points'][] = $v['positive'];
				}
			}
			
	
			if ($item instanceof EAL_ItemMC) {
				$object['positive'] = [];
				$object['negative'] = [];
				foreach ($answers as $k => $v) {
					$object['answer'][] = $v['text'];
					$object['positive'][] = $v['positive'];
					$object['negative'][] = $v['negative'];
				}
				
				$min = $xpath->evaluate ("./presentation/flow/response_lid/render_choice/@minnumber", $itemXML);
				if ($min->length > 0) {
					$object['item_minnumber'] = $min[0]->nodeValue;
				}
				
				$max = $xpath->evaluate ("./presentation/flow/response_lid/render_choice/@maxnumber", $itemXML);
				if ($max->length > 0) {
					$object['item_maxnumber'] = $max[0]->nodeValue;
				}
				
			}
			
			// update from parsed data
			$item->initFromArray($object, '', '');
			$items[$item->getId()] = $item;
			$this->mapItemId2XMLId[$item->getId()] = $itemXML->getAttribute("ident");
		}
	
		return $items;
	}
	

	
	
}