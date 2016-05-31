<?php 


require_once ("class.EAL_ItemSC.php");
require_once ("class.EAL_ItemMC.php");


class EXP_Ilias {


	public static function generateExport ($itemids) {

		$docid = time();
		$zipname = plugin_dir_path(__FILE__) . "../download/" . $docid . "_0_qpl_1.zip";
		$zip = new ZipArchive();
		$zip->open($zipname, ZipArchive::CREATE);
		$zip->addFromString("{$docid}_0_qpl_1/{$docid}_0_qpl_1.xml", EXP_Ilias::createQPL($itemids)->saveXML());
		$zip->addFromString("{$docid}_0_qpl_1/{$docid}_0_qti_1.xml", EXP_Ilias::createQTI($itemids)->saveXML());
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
				$qtype = "SINGLE CHOICE QUESTION";
			}
			if ($post->post_type == 'itemmc') {
				$item = new EAL_ItemMC();
				$qtype = "MULTIPLE CHOICE QUESTION";
			}
			
			$item->loadById($item_id);

			$xml_IT = $dom->createElement("item");
			$xml_IT->setAttribute("ident", "il_0_qst_{$itemid}");
			$xml_IT->setAttribute("title", $item->title);
			$xml_IT->setAttribute("maxattempts", 1);
			
			$xml_IT->appendChild ($dom->createElement("qticomment", "07"));
			$xml_IT->appendChild ($dom->createElement("duration", "P0Y0M0DT0H1M0S"));
				
			/* QTI Metadata*/
			$xml_QM = $dom->createElement("qtimetadata");
			$meta = array (
				"ILIAS_VERSION" => "5.0.8 2015-11-24",
				"QUESTIONTYPE" => $qtype,
				"AUTHOR" => get_the_author_meta ('login', get_post_field( 'post_author', $post->ID )), 
				"additional_cont_edit_mode" => "default",
				"externalId" => "il_0_qst_{$itemid}",
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

			$xml_FL->appendChild (EXP_Ilias::createMaterialElement($dom, "text/html", $item->description));
			
			$xml_RL = $dom->createElement("response_lid");
			$xml_RL->setAttribute("ident", "MCMR");
			$xml_RL->setAttribute("rcardinality", "Multiple");
				
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
					$xml_VE->setAttribute ("respident", "MCMR");
					
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