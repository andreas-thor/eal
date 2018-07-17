<?php


require_once 'EXP_Object.php';

abstract class EXP_TestResult extends EXP_Object {
	
	

	public function downloadTestResult (int $testResultId) {
		$this->generateExportFile($testResultId);
		$this->download();
	}
	
	
		
		
	
	abstract protected function generateExportFile (int $testResultId);
	
	


	
}

?>