<?php namespace S3LP;

/**
* Download and parse AWS S3 log files
* S3LP = S3 Log PHP
* @author Mike Healy
*/
class Logs {
	
	
	protected $cfg; //AWS credentials, region, scheme etc.
	protected $storageDir;
	
	
	public function __construct(array $cfg, $storageDir = 'storage/logs/') {
	
		if(isset($cfg['aws'])) {
			$this->cfg = $cfg['aws'];
		} else {
			$this->cfg = $cfg;
		}
		
		$this->storageDir = rtrim($storageDir, '/') . '/';
	}
	
	
	/**
	* @throws Exception
	*/
	public function download() {
		
	}
	
	
	/**
	* If not deleting files you must move/delete them yourself before next run. Otherwise counted again
	* @param $deleteAfterParse mixed true|DELETE to remove files; falsey|KEEP to keep files
	* @return array
	* @throws Exception
	*/
	public function parseLogs($deleteAfterParse = 'DELETE') {
		
		$deleteAfterParse = ( in_array($deleteAfterParse, [true, 'DELETE', 'delete'], true) ) ? true : false;
		
		if($deleteAfterParse) {
			echo 'Will delete log files.<br>';
		}
		
		//Diag
		
	}
}