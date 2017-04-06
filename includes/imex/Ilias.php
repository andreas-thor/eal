<?php

require_once 'ImportExport.php';

class Ilias extends ImportExport {
	
	
	public static function export (array $itemids) {
	
	}
	
	/**
	 * 
	 * @param array $file
	 * @throws Exception
	 * @return array of EAL_Item
	 */
	public static function import (array $file): array {
	
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
		$itemids = self::parseQPL_TST($doc_qpl_tst);		// XML-ID => EAL-ID (if available)
			
		/* get the QTI document (that contains the questions) */
		$isQPL = (strpos("{$dir}/{$name}/{$name}.xml", '_qpl_') == FALSE) ? FALSE : TRUE;
		$file_qti = file_get_contents ("{$dir}/{$name}/" . str_replace( ($isQPL?'_qpl_':'_tst_'), '_qti_', $name) . ".xml"); 
		if ($file_qti == false) throw new Exception("Could not find QTI file");
		
		/* load the items based on the QTI document and the list of itemids */
		$doc_qti = new DOMDocument();
		$doc_qti->loadXML($file_qti);
		$items = self::parseQTI($doc_qti, $dir, $name, $itemids);		// XML-ID => EAL-ID (all Items have an Id here)
		
		/*
		 if (!$isQPL) {
		 // get and load test results
		 $file_results = file_get_contents ("{$this->dir}/{$this->name}/" . str_replace( '_tst_', '_results_', $this->name) . ".xml"); //     $this->zip->getFromName("{$this->name}/" . str_replace('_qpl_', '_qti_', $this->name) . ".xml");
		 if ($file_results == false) return;	// TODO: Error Handling
		
		 $doc_results = new DOMDocument();
		 $doc_results->loadXML($file_results);
		 $results = $this->parseResults($doc_results, $itemids);
		 }
		 */
			
			
		return $items;
		
	}
	
	
	
	/**
	 * returns map of all questions; qref --> pcid
	 * qref is the internal id for referencing questions between QTI and QPL files
	 * PCID is the external id (written by EAL) to match between ILAS and EAL; PCID can be an empty string if not available
	 * @param unknown $dom
	 */
	private static function parseQPL_TST (DOMDocument $doc) {
	
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
	 * @return array (qref -> item) return the item object for each qref (if the item is already in the database, it has been loaded and updated with the values)
	 */
	private static function parseQTI (DOMDocument $doc, string $dir, string $name, array $itemids):array {
	
		$items = array ();
		$root = $doc->documentElement;
		$xpath = new DOMXPath($doc);
	
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
	
			// initialize item
			$item = EAL_Item::load($item_type, is_numeric($item_id) ? $item_id : -1);
				
			// get title and description + question
			$item->domain = RoleTaxonomy::getCurrentRoleDomain()["name"];
			$item->title = $itemXML->getAttribute("title");
			$descques = $xpath->evaluate ("./presentation/flow/material/mattext/text()", $itemXML)[0]->wholeText;
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
				
			// Description and Question are separated by horizontal line
			$split = explode ("<hr />", $descques, 2);
			if (count($split)==1) {
				$item->description = "";
				$item->question = $split[0];
			} else {
				$item->description = $split[0];
				$item->question = $split[1];
			}
				
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
	
			// set answer data for items
			$item->answers = array();
			foreach ($answers as $k => $v) {
				if ($item->type == "itemsc") array_push ($item->answers, array ("answer" => $v["text"], "points" => $v["positive"]));
				if ($item->type == "itemmc") array_push ($item->answers, array ("answer" => $v["text"], "positive" => $v["positive"], "negative" => $v["negative"]));
			}
	
				
			// update Item id (for newly created items)
			$items[$itemXML->getAttribute("ident")] = $item;
		}
	
		return $items;
	}
	
	
}