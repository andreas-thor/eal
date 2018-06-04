<?php

require_once 'HTML_Object.php';
require_once __DIR__ . '/../eal/EAL_TestResult.php';


class HTML_TestResult extends HTML_Object {
	
	protected $testresult;
	
	function __construct(EAL_TestResult $testresult) {
		$this->testresult = $testresult;
	}
	 
	
	private function getTestResult(): EAL_TestResult {
		return $this->testresult;
	}
	
	
	public function metaboxDescription () {
		
		$this->printEditor('testresult_description', $this->getTestResult()->getDescription());
?>
		<script type="text/javascript">
			// remove title and title-action buttons
		 	document.getElementsByTagName("H1")[0].remove();
		 	document.getElementsByClassName("page-title-action")[0].remove();
		</script> 
		
<?php 		
	}
	
	public function printDescription (bool $isImport, string $prefix="") {
		?>
		<div>
			<?php if ($isImport) { ?>
				<input 
					type="hidden" 
					name="<?php echo $prefix ?>testresult_description" 
					value="<?php echo htmlentities($this->getTestResult()->getDescription(), ENT_COMPAT | ENT_HTML401, 'UTF-8') ?>" />			
			<?php } ?> 
			<?php echo wpautop($this->getTestResult()->getDescription()) ?>
		</div>
<?php 		
	}
	
	
	
	
}
?>