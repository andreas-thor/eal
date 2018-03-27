<?php

require_once ('IMEX_Item.php');

class IMEX_Ilias extends IMEX_Item {
	
	private $media = array ();
	private $xml_MTImages = array();
	
	
	
	
	protected function generateExportFile (array $itemids) {
	
		$this->downloadfilename = time() . "__0__qpl_1";
		$this->downloadextension = "zip";
		
		$zip = new ZipArchive();
		$zip->open($this->getDownloadFullname(), ZipArchive::CREATE);
		$zip->addFromString("{$this->downloadfilename}/{$this->downloadfilename}.xml", $this->createQPL($itemids)->saveXML());
		$zip->addFromString("{$this->downloadfilename}/" . str_replace('_qpl_', '_qti_', $this->downloadfilename) . ".xml", $this->createQTI($itemids)->saveXML());
		 
		// copy media files (e.g., images) -- array is filled during createQPL/QTI /*
		foreach ($this->media as $key => $file) {
			$fileshort = array_pop(explode ("/", $file));
			$zip->addFromString("{$this->downloadfilename}/objects/{$key}/{$fileshort}", file_get_contents($file));
		}
		
		$zip->close();
	}
	
	
	private function createQPL ($itemids) {
	
		$dom = DOMDocument::loadXML (
		   '<?xml version="1.0" encoding="utf-8"?>
			<!DOCTYPE Test SYSTEM "http://www.ilias.uni-koeln.de/download/dtd/ilias_co.dtd">
			<ContentObject Type="Questionpool_Test">
				<MetaData>
					<General Structure="Hierarchical">
						<Identifier Catalog="EAL" Entry="il_0_qpl_1"/>
						<Title Language="de">Exported from EAs.LiT</Title>
						<Language Language="de"/>
						<Description Language="de"/>
						<Keyword Language="en"/>
					</General>
				</MetaData>
			</ContentObject>
		');
	
	
		foreach ($itemids as $itemid) {
				
			$xml_PO = $dom->createElement("PageObject");
			$xml_PC = $dom->createElement("PageContent");
			$xml_PC->setAttribute("PCID", "EAL:{$itemid}");
			$xml_QU = $dom->createElement("Question");
			$xml_QU->setAttribute("QRef", "il_0_qst_{$itemid}");
			$xml_PC->appendChild ($xml_QU);
			$xml_PO->appendChild ($xml_PC);
			$dom->documentElement->appendChild ($xml_PO);
	
		}
	
		return $dom;
	}
	
	
	private function createQTI ($itemids) {
	
		$dom = DOMDocument::loadXML (
				'<?xml version="1.0" encoding="utf-8"?>
			<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv1p2p1.dtd">
			<questestinterop></questestinterop>
		');
	
		foreach ($itemids as $item_id) {
	
			// load item
			$post = get_post($item_id);
			if ($post == null) continue;	// item (post) does not exist
			$item = EAL_Item::load($post->post_type, $item_id);
			
			if ($item->getType() == 'itemsc') {
				$item_data = array (
						"questiontype" => "SINGLE CHOICE QUESTION",
						"ident" => "MCSR",
						"rcardinality" => "Single"
				);
			}
			if ($item->getType() == 'itemmc') {
				$item_data = array (
						"questiontype" => "MULTIPLE CHOICE QUESTION",
						"ident" => "MCMR",
						"rcardinality" => "Multiple"
				);
			}
				
			$xml_IT = $dom->createElement("item");
			$xml_IT->setAttribute("ident", "il_0_qst_{$item_id}");
			$xml_IT->setAttribute("title", $item->getTitle());
			$xml_IT->setAttribute("maxattempts", 1);
				
			$xml_IT->appendChild ($dom->createElement("qticomment", "[EALID:{$item_id}]"));
			$xml_IT->appendChild ($dom->createElement("duration", "P0Y0M0DT0H1M0S"));
	
			/* QTI Metadata*/
			$xml_QM = $dom->createElement("qtimetadata");
			$meta = array (
					"ILIAS_VERSION" => "5.0.8 2015-11-24",
					"QUESTIONTYPE" => $item_data["questiontype"],
					"AUTHOR" => get_the_author_meta ('login', get_post_field( 'post_author', $item->getId() )),
					"additional_cont_edit_mode" => "default",
					"externalId" => "il_0_qst_{$item_id}",
					"ealid" => $item_id,
					"thumb_size" => "",
					"feedback_setting" => 1
			);
			foreach ($meta as $key => $value) {
				$x = $dom->createElement("qtimetadatafield");
				$x->appendChild ($dom->createElement("fieldlabel", $key));
				$x->appendChild ($dom->createElement("fieldentry", $value));
				$xml_QM->appendChild ($x);
			}
			$xml_IM = $dom->createElement("itemmetadata");
			$xml_IM->appendChild ($xml_QM);
			$xml_IT->appendChild ($xml_IM);
				
			/* Presentation */
			$xml_PR = $dom->createElement("presentation");
			$xml_PR->setAttribute("label", $item->getTitle());
			$xml_FL = $dom->createElement("flow");

			$xml_FL->appendChild ($this->createMaterialElement($dom, "text/html", wpautop($item->getDescription()) . "<!-- EAL --><hr/>" . wpautop($item->getQuestion())));
				
			$xml_RL = $dom->createElement("response_lid");
			$xml_RL->setAttribute("ident", $item_data["ident"]);
			$xml_RL->setAttribute("rcardinality", $item_data["rcardinality"]);
	
			$xml_RC = $dom->createElement("render_choice");
			$xml_RC->setAttribute("shuffle", "Yes");

			if ($item->getType() == "itemmc") {
				$xml_RC->setAttribute("minnumber", $item->minnumber);
				$xml_RC->setAttribute("maxnumber", $item->maxnumber);
			}			
			
			
			foreach ($item->answers as $number => $answer) {
				$xml_LAB = $dom->createElement("response_label");
				$xml_LAB->setAttribute("ident", $number);
				$xml_LAB->appendChild ($this->createMaterialElement($dom, "text/html", $answer["answer"]));
				$xml_RC->appendChild ($xml_LAB);
			}
				
			$xml_RL->appendChild ($xml_RC);
			$xml_FL->appendChild ($xml_RL);
			$xml_PR->appendChild ($xml_FL);
			$xml_IT->appendChild ($xml_PR);
				
			$xml_RP = $dom->createElement("resprocessing");
				
			$xml_OC = $dom->createElement("outcomes");
			$xml_DV = $dom->createElement("decvar");
			$xml_OC->appendChild ($xml_DV);
			$xml_RP->appendChild ($xml_OC);
	
			foreach ($item->answers as $number => $answer) {
				foreach (array (1, 0) as $checked) {
						
					$xml_RC = $dom->createElement("respcondition");
					$xml_RC->setAttribute ("continue", "Yes");
						
					$xml_CV = $dom->createElement("conditionvar");
					$xml_NO = $dom->createElement("not");
					$xml_VE = $dom->createElement("varequal", $number);
					$xml_VE->setAttribute ("respident", $item_data["ident"]);
						
					if ($checked==1) {
						$xml_CV->appendChild ($xml_VE);
					} else {
						$xml_NO->appendChild ($xml_VE);
						$xml_CV->appendChild ($xml_NO);
					}
					$xml_RC->appendChild ($xml_CV);
						
					if ($item->getType() == "itemsc") $xml_SV = $dom->createElement("setvar", ($checked==1) ? $answer['points'] : 0);
					if ($item->getType() == "itemmc") $xml_SV = $dom->createElement("setvar", ($checked==1) ? $answer['positive'] : $answer['negative']);
	
					$xml_SV->setAttribute ("action", "Add");
					$xml_RC->appendChild ($xml_SV);
						
					$xml_RP->appendChild ($xml_RC);
						
				}
	
			}
				
				
				
				
			$xml_IT->appendChild ($xml_RP);
	
			$dom->documentElement->appendChild ($xml_IT);
		}
		return $dom;
	
	}
	
	
	/**
	 * Images are replaced by new name (il_0_mob_[count]) and added to xml_MITImages (=> will later be added to the zip file9
	 * {@inheritDoc}
	 * @see IMEX_Item::processImage()
	 */
	protected function processImage(string $src): string {
		
		$key = "il_0_mob_" . count($this->media);
		$this->media [$key] = $src;
		$fileshort = array_pop(explode ("/", $src));
		
		$this->xml_MTImages[] = ['label' => $key, 'uri' => ('objects/' . $key . '/' . $fileshort)];
		return $key;
	}
	
	
	private function createMaterialElement ($dom, $type, $value) {
		
		// processAllImages might call processImage, that fills $this->media and $this->xml_MTImages 
		$this->xml_MTImages = array ();
		
		$xml_MT = $dom->createElement("mattext");
		$xml_MT->appendChild ($dom->createTextNode ($this->processAllImages($value)));
		$xml_MT->setAttribute("texttype", $type);

		$xml_MA = $dom->createElement("material");
		$xml_MA->appendChild ($xml_MT);
		
		foreach ($this->xml_MTImages as $mtimage) {
			$xml_mimg = $dom->createElement("matimage");
			$xml_mimg->setAttribute('label', $mtimage['label']);
			$xml_mimg->setAttribute('uri', $mtimage['uri']);
			$xml_MA->appendChild($xml_mimg);
		}
		
		return $xml_MA;
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
	 * @param DOMDocument $doc
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
	
			// initialize item
			$item = EAL_Item::load($item_type, intval($item_id));
			$countItems++;
			if ($item->getId() < 0) $item->setId (-$countItems);
				
			// get title and description + question
//			$item->setDomain(RoleTaxonomy::getCurrentRoleDomain()["name"]);	// necessary, if we import item from different domain and want to store it in current domain
//			$item->title = $itemXML->getAttribute("title");
			
			
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
				
			// Description and Question are separated by horizontal line; description is optional
			$split = explode (self::DESCRIPTION_QUESTION_SEPARATOR, $descques, 2);
			$item->init($itemXML->getAttribute("title"), (count($split)>1) ? $split[0] : "", $split[count($split)-1]);	// automatically sets current domain
			
			
				
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
				if ($item->getType() == "itemsc") array_push ($item->answers, array ("answer" => $v["text"], "points" => $v["positive"]));
				if ($item->getType() == "itemmc") array_push ($item->answers, array ("answer" => $v["text"], "positive" => $v["positive"], "negative" => $v["negative"]));
			}
	
			if ($item->getType() == "itemmc") {
				$min = $xpath->evaluate ("./presentation/flow/response_lid/render_choice/@minnumber", $itemXML);
				$item->minnumber = ($min->length==0) ? 0 : $min[0]->nodeValue;
				$max = $xpath->evaluate ("./presentation/flow/response_lid/render_choice/@maxnumber", $itemXML);
				$item->maxnumber = ($max->length==0) ? count($item->answers) : $max[0]->nodeValue;
			}
			
			// update Item id (for newly created items)
			$items[$itemXML->getAttribute("ident")] = $item;
		}
	
		return $items;
	}
	

	
	
}