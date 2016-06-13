<?php 


require_once ("class.EAL_ItemSC.php");
require_once ("class.EAL_ItemMC.php");


class EXP_Ilias {

	
	
	public static function import ($file) {
		
		$zip = new ZipArchive;
		if (substr ($file['name'], -4) != ".zip") {
			// TODO: error handling
			echo "Error! File is not a zip file";
			return;
		}
		
		$name = substr ($file['name'], 0, strlen ($file['name'])-4); // remove extension ".zip"
		$res = $zip->open($file['tmp_name']);
		if ($res === TRUE) {
			
			$file_qpl = $zip->getFromName("{$name}/{$name}.xml");
			$file_qti = $zip->getFromName("{$name}/" . str_replace('_qpl_', '_qti_', $name) . ".xml");
				
			// TODO: error handling
			if (($file_qpl == false) || ($file_qti==false)) return;
			
			$doc_qpl = new DOMDocument();
			$doc_qpl->loadXML($file_qpl);
			$itemids = EXP_Ilias::parseQPL($doc_qpl);

			
			$doc_qti = new DOMDocument();
			$doc_qti->loadXML($file_qti);
			EXP_Ilias::parseQTI($doc_qti->documentElement, $itemids);
			
			
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
	public static function parseQPL ($dom) {
		
		$res = array ();
		foreach ($dom->documentElement->childNodes as $po) {
			if ($po->nodeName == "PageObject") {
				$pc = $po->childNodes[0];
				$qu = $pc->childNodes[0];
				$res[$qu->getAttribute("QRef")] = $pc->getAttribute("PCID"); 
			}
		}
		return $res;		
	}
	
	
	public static function getMaterialElement ($parent) {
		$q3 = $parent->getElementsByTagName("material")[0];
		$q4 = $parent->getElementsByTagName("material")[0]->getElementsByTagName("mattext");
		return $parent->getElementsByTagName("material")[0]->getElementsByTagName("mattext")[0]->nodeValue;
	}

	public static function parseQTI ($root, $itemids) {
		
		
		foreach ($root->childNodes as $itemXML) {
			
			// determine EAL-ID (==item_id) if available
			$item_id = "";
			$idXML = $itemids [$itemXML->getAttribute("ident")];
			if (substr ($idXML, 0, 4) == "EAL:") {
				$item_id = substr ($idXML, 4);
			}
			
			// determine item type
			$item_type = "";
			foreach ($itemXML->getElementsByTagName("qtimetadatafield") as $md) {
				if (($md->firstChild->nodeName == "fieldlabel") && ($md->firstChild->nodeValue == "QUESTIONTYPE") && ($md->lastChild->nodeName == "fieldentry")) {
					if ($md->lastChild->nodeValue == "SINGLE CHOICE QUESTION")  $item_type = "itemsc";		
					if ($md->lastChild->nodeValue == "MULTIPLE CHOICE QUESTION")  $item_type = "itemmc";		
				}
			}
			// TODO: Handling if item type not found
			if ($item_type == "") continue;

			// initialize item
			if ($item_type == "itemsc") $item = new EAL_ItemSC();
			if ($item_type == "itemmc") $item = new EAL_ItemMC();
			if ($item_id != "") $item->loadById($item_id);
			
			// update item values
			$item->title = $itemXML->getAttribute("title");
			$flow = $itemXML->getElementsByTagName("presentation")[0]->getElementsByTagName("flow")[0];
			
			$split = explode ("<hr />", EXP_Ilias::getMaterialElement ($flow), 2);
			if (count($split)==1) {
				$item->description = "";
				$item->question = $split[0];
			} else {
				$item->description = $split[0];
				$item->question = $split[1];
			}
				
			// collect answers
			$item->answers = array ();
			foreach ($flow->getElementsByTagName("response_label") as $resp) {
				$q1 = $resp->getAttribute("ident");
				$q2 = EXP_Ilias::getMaterialElement($resp);
				$item->answers[$resp->getAttribute("ident")] = array ("answer" => EXP_Ilias::getMaterialElement($resp));
			}
			
			// collect points for each answer
			foreach ($itemXML->getElementsByTagName("resprocessing")[0]->getElementsByTagName("respcondition") as $resp) {
				
				
				$var = $resp->getElementsByTagName("conditionvar")[0]->firstChild;
				if ($var->nodeName == "varequal") {
					$positive = 1;
				} else if ($var->nodeName == "not") {
					$var = $var->firstChild;
					if ($var->nodeName == "varequal") {
						$positive = 0;
					} else {
						// TODO: Error Handling (unknown condition)
						break;
					}
				} else {
					// TODO: Error Handling (unknown condition)
					break;
				}

				$setvar = $resp->getElementsByTagName("setvar")[0];
				if ($setvar->getAttribute("action")!="Add") {
					// TODO: Error Handling (unknown action)
					break;
				}
				
				if ($item->type == "itemsc") {
					$item->answers[$var->nodeValue] = array_merge ($item->answers[$var->nodeValue], array ("points" => $setvar->nodeValue));
				} 
				if ($item->type == "itemmc") {
					$item->answers[$var->nodeValue] = array_merge ($item->answers[$var->nodeValue], array ((($positive==1)?"positive":"negative") => $setvar->nodeValue));
				}
				
				
			}
			
			$item->setPOST();
			
			if ($item_id == "") {
			
				$postarr = array ();
				$postarr['ID'] = 0;	// no EAL-ID
				$postarr['post_title'] = $itemXML->getAttribute("title");
				$postarr['post_status'] = 'publish';
				$postarr['post_type'] = $item_type;
				$a = wp_insert_post ($postarr);
			} else {
				
				$post = get_post ($item_id);
				$old_title = $post->post_title;
				$post->post_title = $itemXML->getAttribute("title");	
				// we add ASCII-03 to make sure wh have a new revision
				if ($old_title == $post->post_title) $post->post_title .= "\x03";
				$a = wp_update_post ($post);				
			}
			$b = $a;
		}
		
	}
	
	public static function generateExport ($itemids) {

		$docid = time();
		$name = "{$docid}__0__qpl_1";
		$zipname = plugin_dir_path(__FILE__) . "../download/{$name}.zip";
		$zip = new ZipArchive();
		$zip->open($zipname, ZipArchive::CREATE);
		$zip->addFromString("{$name}/{$name}.xml", EXP_Ilias::createQPL($itemids)->saveXML());
		$zip->addFromString("{$name}/" . str_replace('_qpl_', '_qti_', $name) . ".xml", EXP_Ilias::createQTI($itemids)->saveXML());
		$zip->close();
	}

	
	private static function createQPL ($itemids) {
	
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
		 	$xml_QU  = $dom->createElement("Question");
		 	$xml_QU->setAttribute("QRef", "il_0_qst_{$itemid}");
		 	$xml_PC->appendChild ($xml_QU);
		 	$xml_PO->appendChild ($xml_PC);
		 	$dom->documentElement->appendChild ($xml_PO);
		 	
		}
		
		return $dom;
	}
	
	
	private static function createQTI ($itemids) {
		
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

	
	
	public static function createMaterialElement ($dom, $type, $value) {
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