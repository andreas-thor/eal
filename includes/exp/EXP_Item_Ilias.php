<?php

require_once 'EXP_Item.php';

class EXP_Item_Ilias extends EXP_Item {
	
	private $media = array ();
	private $xml_MTImages = array();
	
	
	public function __construct() {
		parent::__construct (time() . '__0__qpl_1', 'zip' );
	}
	
	protected function generateExportFile (array $itemids) {
	
		
		$zip = new ZipArchive();
		$zip->open($this->getDownloadFullname(), ZipArchive::CREATE);
		$zip->addFromString("{$this->getDownloadFileName()}/{$this->getDownloadFileName()}.xml", $this->createQPL($itemids)->saveXML());
		$zip->addFromString("{$this->getDownloadFileName()}/" . str_replace('_qpl_', '_qti_', $this->getDownloadFileName()) . ".xml", $this->createQTI($itemids)->saveXML());
		 
		// copy media files (e.g., images) -- array is filled during createQPL/QTI /*
		foreach ($this->media as $key => $file) {
			$fileshort = array_pop(explode ("/", $file));
			$zip->addFromString("{$this->getDownloadFileName()}/objects/{$key}/{$fileshort}", file_get_contents($file));
		}
		
		$zip->close();
	}
	
	
	private function createQPL ($itemids): DOMDocument {
	
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
	
	
		foreach ($itemids as $item_id) {
				
			$xml_PO = $dom->createElement("PageObject");
			$xml_PC = $dom->createElement("PageContent");
			$xml_PC->setAttribute("PCID", "EAL:{$item_id}");
			$xml_QU = $dom->createElement("Question");
			$xml_QU->setAttribute("QRef", "il_0_qst_{$item_id}");
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
			
			$item = DB_Item::loadFromDB($item_id, $post->post_type);
			assert(($item instanceof EAL_ItemSC) || ($item instanceof EAL_ItemMC));
			
			if ($item instanceof EAL_ItemSC) {
				$item_data = array (
						"questiontype" => "SINGLE CHOICE QUESTION",
						"ident" => "MCSR",
						"rcardinality" => "Single"
				);
			}
			
			if ($item instanceof EAL_ItemMC) {
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

			$xml_FL->appendChild ($this->createMaterialElement($dom, "text/html", wpautop($item->getDescription()) . EXP_Item::DESCRIPTION_QUESTION_SEPARATOR . wpautop($item->getQuestion())));
				
			$xml_RL = $dom->createElement("response_lid");
			$xml_RL->setAttribute("ident", $item_data["ident"]);
			$xml_RL->setAttribute("rcardinality", $item_data["rcardinality"]);
	
			$xml_RC = $dom->createElement("render_choice");
			$xml_RC->setAttribute("shuffle", "Yes");
			

			if ($item instanceof EAL_ItemMC) {
				$xml_RC->setAttribute("minnumber", $item->minnumber);
				$xml_RC->setAttribute("maxnumber", $item->maxnumber);
			}			
			
			
			for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
				$xml_LAB = $dom->createElement("response_label");
				$xml_LAB->setAttribute("ident", $index);
				$xml_LAB->appendChild ($this->createMaterialElement($dom, "text/html", $item->getAnswer ($index)));
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
	
			for ($index=0; $index<$item->getNumberOfAnswers(); $index++) {
				foreach (array (1, 0) as $checked) {
						
					$xml_RC = $dom->createElement("respcondition");
					$xml_RC->setAttribute ("continue", "Yes");
						
					$xml_CV = $dom->createElement("conditionvar");
					$xml_NO = $dom->createElement("not");
					$xml_VE = $dom->createElement("varequal", $index);
					$xml_VE->setAttribute ("respident", $item_data["ident"]);
						
					if ($checked==1) {
						$xml_CV->appendChild ($xml_VE);
					} else {
						$xml_NO->appendChild ($xml_VE);
						$xml_CV->appendChild ($xml_NO);
					}
					$xml_RC->appendChild ($xml_CV);
						
					
					if ($item instanceof EAL_ItemSC) {
						$xml_SV = $dom->createElement("setvar", ($checked==1) ? $item->getPointsChecked($index) : 0);
					}
					if ($item instanceof EAL_ItemMC) {
						$xml_SV = $dom->createElement("setvar", ($checked==1) ? $item->getPointsPos($index) : $item->getPointsNeg($index));
					}
					
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
	 * Images are replaced by new name (il_0_mob_[count]) and added to xml_MITImages (=> will later be added to the zip file9
	 * {@inheritDoc}
	 * @see EXP_Item::processImage()
	 */
	protected function processImage(string $src): string {
		
		$key = "il_0_mob_" . count($this->media);
		$this->media[$key] = $src;
		$fileshort = array_pop(explode ("/", $src));
		
		$this->xml_MTImages[] = ['label' => $key, 'uri' => ('objects/' . $key . '/' . $fileshort)];
		return $key;
	}

	
	
}