<?php 


require_once ("class.EAL_ItemSC.php");
require_once ("class.EAL_ItemMC.php");


class EXP_Ilias {

	
	private $name;
	private $dir;
	
	public function import ($file) {
		
		$zip = new ZipArchive;
		if (substr ($file['name'], -4) != ".zip") {
			// TODO: error handling
			echo "Error! File is not a zip file";
			return;
		}
		
		$this->name = substr ($file['name'], 0, strlen ($file['name'])-4); // remove extension ".zip"
		
		
		$res = $zip->open($file['tmp_name']);
		if ($res === TRUE) {

			$this->dir = sys_get_temp_dir() . "/eal" . microtime(TRUE);
			$zip->extractTo($this->dir);
				
			/* process main document: qpl (Question Pool) or tst (Test) */
			$file_qpl_tst = file_get_contents ("{$this->dir}/{$this->name}/{$this->name}.xml"); 
			if ($file_qpl_tst == false) return;	// TODO: Error Handling
			
			/* get the list of itemids */
			$doc_qpl_tst = new DOMDocument();
			$doc_qpl_tst->loadXML($file_qpl_tst);
			$itemids = $this->parseQPL_TST($doc_qpl_tst);		// XML-ID => EAL-ID (if available)
			
			/* get the QTI document (that contains the questions) */
			$isQPL = (strpos("{$this->dir}/{$this->name}/{$this->name}.xml", '_qpl_') == FALSE) ? FALSE : TRUE;
			$file_qti = file_get_contents ("{$this->dir}/{$this->name}/" . str_replace( ($isQPL?'_qpl_':'_tst_'), '_qti_', $this->name) . ".xml"); //     $this->zip->getFromName("{$this->name}/" . str_replace('_qpl_', '_qti_', $this->name) . ".xml");
			if ($file_qti == false) return;	// TODO: Error Handling

			/* load the items based on the QTO document and the list of itemids */
			$doc_qti = new DOMDocument();
			$doc_qti->loadXML($file_qti);
			$itemids = $this->parseQTI($doc_qti, $itemids);		// XML-ID => EAL-ID (all Items have an Id here)
			
			if (!$isQPL) {
				/* get and load test results*/
				$file_results = file_get_contents ("{$this->dir}/{$this->name}/" . str_replace( '_tst_', '_results_', $this->name) . ".xml"); //     $this->zip->getFromName("{$this->name}/" . str_replace('_qpl_', '_qti_', $this->name) . ".xml");
				if ($file_results == false) return;	// TODO: Error Handling
				
				$doc_results = new DOMDocument();
				$doc_results->loadXML($file_results);
				$this->parseResults($doc_results, $itemids);
			}
			
			$zip->close();
		} else {
			echo 'Fehler, Code:' . $res;
		}
		
	}
	
	/**
	 * returns map of all questions; qref --> pcid 
	 * qref is the internal id for referencing questions between QTI and QPL files
	 * PCID is the external id (written by EAL) to match between ILAS and EAL; PCID can be an empty string if not available 
	 * @param unknown $dom
	 */
	public function parseQPL_TST (DOMDocument $doc) {
		
		$res = array ();
		$xpath = new DOMXPath($doc);
		foreach ($xpath->evaluate("//PageObject/PageContent/Question") as $question) {
			$pcid = $question->parentNode->getAttribute("PCID");
			$res[$question->getAttribute("QRef")] = (substr ($pcid, 0, 4) == "EAL:") ? substr ($pcid, 4) : ""; 
		}
		return $res;		
	}
	
	

	public function parseQTI (DOMDocument $doc, $itemids) {
		
		$root = $doc->documentElement;
		$xpath = new DOMXPath($doc);

		foreach ($xpath->evaluate("//item", $doc->documentElement) as $itemXML) {
			
			// determine (==item_id) if available and item type
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
			if ($item_type == "itemsc") $item = new EAL_ItemSC();
			if ($item_type == "itemmc") $item = new EAL_ItemMC();
			if ($item_id != "") $item->loadById($item_id);
			
			// get title and description + question 
			$item->domain = RoleTaxonomy::getCurrentRoleDomain()["name"];
			$item->title = $itemXML->getAttribute("title");
			$descques = $xpath->evaluate ("./presentation/flow/material/mattext/text()", $itemXML)[0]->wholeText;
			$descques = preg_replace_callback(				// replace image references
					'|(<img[^>]+)src=["\']([^"]*)["\']|',
					function ($match) {
						
						/* if img is stored inline (src="data:image/png;base64,iVBOR....") --> do nothing */
						if (strtolower (substr($match[2], 0, 5)) == 'data:') {
							return $match[1] . "src='" . $match[2] . "'";
						}
						
						/* locate file */
						$entries = scandir("{$this->dir}/{$this->name}/objects/{$match[2]}/");
						$entry = $entries[count($entries)-1];
						
						/* generate unique filename for img */
						$count=0;
						$path = wp_upload_dir()["path"];
						$filename = $match[2];
						while (file_exists($path . "/" . $filename . "_" . $count)) {
							$count++;
						}
						
						$from = "{$this->dir}/{$this->name}/objects/{$entry}";
						$to = $path . "/" . $filename . "_" . $count;
						copy("{$this->dir}/{$this->name}/objects/{$match[2]}/{$entry}" , $path . "/" . $filename . "_" . $count);
						
						
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
				
			
			$item->setPOST();
			
			if ($item_id == "") {
			
				$postarr = array ();
				$postarr['ID'] = 0;	// no EAL-ID
				$postarr['post_title'] = $itemXML->getAttribute("title");
				$postarr['post_status'] = 'publish';
				$postarr['post_type'] = $item_type;
				$postarr['post_content'] = microtime();	
				$item_id = wp_insert_post ($postarr);	// returns the item_id of the created post / item
			} else {
				
				$post = get_post ($item_id);
				$old_title = $post->post_title;
				$post->post_title = $itemXML->getAttribute("title");	
				$post->post_content = microtime();	// ensures revision
				wp_update_post ($post);				
			}
			
			// update Item id (for newly created items)
			$itemids[$itemXML->getAttribute("ident")] = $item_id;
		}
		
		return $itemids;
	}
	
	public function parseResults (DOMDocument $doc, $itemids) {
	
		global $wpdb;
		$values = array();
		$insert = array();
		
		
		// test_id is timestamp of the first question
		$xpath = new DOMXPath($doc);
		$test_id = $xpath->evaluate("/results/tst_test_question/row", $doc->documentElement)[0]->getAttribute("tstamp");
		
		foreach ($xpath->evaluate("/results/tst_test_result/row", $doc->documentElement) as $row) {
			
			$question_fi = $row->getAttribute("question_fi");	
			if (!isset($itemids['il_0_qst_' . $question_fi])) continue;
			
			$user = $row->getAttribute("active_fi");	// test-specific user_id
			$points = $row->getAttribute("points");		// test-specific user_id
					
			array_push($values, $test_id, $itemids['il_0_qst_' . $question_fi], $user, $points);
			array_push($insert, "(%d, %d, %d, %d)");
			
		}
		
		if (count($values)>0) {
			// insert / replace result
			$query = "REPLACE INTO {$wpdb->prefix}eal_result (test_id, item_id, user_id, points) VALUES ";
			$query .= implode(', ', $insert);
			$wpdb->query( $wpdb->prepare("$query ", $values));
			
			// update difficulty for relevant items

			$query = "UPDATE {$wpdb->prefix}eal_item, (
				SELECT i.id, (1.0*avg(r.points))/max(i.points) as S 
				FROM {$wpdb->prefix}eal_item i 
				JOIN {$wpdb->prefix}eal_result r 
				ON (r.item_id=i.id) 
				GROUP BY i.id) as T 
				SET {$wpdb->prefix}eal_item.difficulty = T.S 
				WHERE {$wpdb->prefix}eal_item.id = T.id
				AND {$wpdb->prefix}eal_item.id IN (" . implode (', ', array_values ($itemids)) . ")";
			$a = $wpdb->query ($query);
			$b = $a;
		}
		
	}
	
	
	
	public function generateExport ($itemids) {

		if (!file_exists(plugin_dir_path(__FILE__) . "../download")) {
			mkdir(plugin_dir_path(__FILE__) . "../download", 0777, true);
		}
		
		$docid = time();
		$name = "{$docid}__0__qpl_1";
		$zipname = plugin_dir_path(__FILE__) . "../download/{$name}.zip";
		$zip = new ZipArchive();
		$zip->open($zipname, ZipArchive::CREATE);
		$zip->addFromString("{$name}/{$name}.xml", EXP_Ilias::createQPL($itemids)->saveXML());
		$zip->addFromString("{$name}/" . str_replace('_qpl_', '_qti_', $name) . ".xml", EXP_Ilias::createQTI($itemids)->saveXML());
		$zip->close();
		return plugin_dir_url(__FILE__) . "../download/{$name}.zip";
	}

	
	private function createQPL ($itemids) {
	
		$dom = DOMDocument::loadXML (
			'<?xml version="1.0" encoding="utf-8"?>
			<!DOCTYPE Test SYSTEM "http://www.ilias.uni-koeln.de/download/dtd/ilias_co.dtd">
			<ContentObject Type="Questionpool_Test">
				<MetaData>
					<General Structure="Hierarchical">
						<Identifier Catalog="EAL" Entry="il_0_qpl_1"/>
						<Title Language="de">Exported from EAL</Title>
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
		
			$post = get_post($item_id);
			if ($post == null) continue;
			
			if ($post->post_type == 'itemsc') {
				$item = new EAL_ItemSC();
				$item_data = array (
					"questiontype" => "SINGLE CHOICE QUESTION",
					"ident" => "MCSR",
					"rcardinality" => "Single"
				);
			}
			if ($post->post_type == 'itemmc') {
				$item = new EAL_ItemMC();
				$item_data = array (
					"questiontype" => "MULTIPLE CHOICE QUESTION",
					"ident" => "MCMR",
					"rcardinality" => "Multiple"
				);
			}
			
			$item->loadById($item_id);

			$xml_IT = $dom->createElement("item");
			$xml_IT->setAttribute("ident", "il_0_qst_{$item_id}");
			$xml_IT->setAttribute("title", $item->title);
			$xml_IT->setAttribute("maxattempts", 1);
			
			$xml_IT->appendChild ($dom->createElement("qticomment", "[EALID:{$item_id}]"));
			$xml_IT->appendChild ($dom->createElement("duration", "P0Y0M0DT0H1M0S"));
				
			/* QTI Metadata*/
			$xml_QM = $dom->createElement("qtimetadata");
			$meta = array (
				"ILIAS_VERSION" => "5.0.8 2015-11-24",
				"QUESTIONTYPE" => $item_data["questiontype"],
				"AUTHOR" => get_the_author_meta ('login', get_post_field( 'post_author', $post->ID )), 
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
			$xml_PR->setAttribute("label", $item->title);
			$xml_FL = $dom->createElement("flow");

			$xml_FL->appendChild (EXP_Ilias::createMaterialElement($dom, "text/html", $item->description . "<hr/>" . $item->question));
			
			$xml_RL = $dom->createElement("response_lid");
			$xml_RL->setAttribute("ident", $item_data["ident"]);
			$xml_RL->setAttribute("rcardinality", $item_data["rcardinality"]);
				
			$xml_RC = $dom->createElement("render_choice");
			$xml_RC->setAttribute("shuffle", "Yes");
				
			foreach ($item->answers as $number => $answer) {
				$xml_LAB = $dom->createElement("response_label");
				$xml_LAB->setAttribute("ident", $number);
				$xml_LAB->appendChild (EXP_Ilias::createMaterialElement($dom, "text/plain", $answer["answer"]));
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
					
					$xml_SV = $dom->createElement("setvar", ($checked==1) ? $answer['positive'] : $answer['negative']);
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

	
	
	public function createMaterialElement ($dom, $type, $value) {
		$xml_MA = $dom->createElement("material");
		$xml_MT = $dom->createElement("mattext", $value);
		$xml_MT->setAttribute("texttype", $type);
		$xml_MA->appendChild ($xml_MT);
		return $xml_MA;
	}
	

// $dom = new DomDocument("1.0", "UTF-8");

// $root = $dom->createElement("ContentObject");
// $root->setAttribute("Type", "Questionpool_Test");

// $meta = $dom->createElement("Metadata");
// $general = $dom->createElement("General");
// $general->setAttribute("Structure", "Hierarchical");

// $dom->appendChild($root);

}

?>