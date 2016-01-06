<?php namespace S3LP;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
* Download and parse AWS S3 log files
* S3LP = S3 Log PHP
* @author Mike Healy
*/
class Logs {
	
	/**
	* @var array AWS credentials, region, bucket...
	*/
	protected $cfg;
	
	protected $bucket;
	
	protected $prefix;
	
	protected $storageDir;
	
	protected $s3;
	
	/**
	* Logs need to be deleted before next run to avoid double count
	* I'd only set this to false for testing, or if you have another process that deletes them
	* @var bool
	*/
	public $deleteLogsFromS3 = true;
	
	
	/**
	* CONSTRUCTOR
	* @param array $cfg
	* @param string $prefix log directory within bucket (e.g. logs)
	* @param string $storageDir local path to save downloaded files
	*/
	public function __construct(array $cfg, $prefix = '', $storageDir = 'storage/logs') {
	
		if(isset($cfg['aws'])) {
			$this->cfg = $cfg['aws'];
		} else {
			$this->cfg = $cfg;
		}
		
		if( !isset($cfg['bucket']) OR !$cfg['bucket'] ) {
			throw new Exception('Please specify bucket in AWS cfg array. \S3LP\Logs::constructor()');
		}
		
		$this->bucket     = $cfg['bucket'];
		$this->prefix     = rtrim($prefix, '/');
		$this->storageDir = rtrim($storageDir, '/');
		
		try {
			$this->s3 = S3Client::factory($this->cfg);
		} catch(S3Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	
	/**
	* @return array
	* @throws Exception
	*/
	public function download() {
		
		$files = [];
		
		$prefix = $this->prefix;
		$bucket = $this->bucket;
		
		try {
			$result = $this->s3->listObjects([
				'Bucket' => $bucket,
				'Prefix' => $prefix,
			]);
		} catch(S3Exception $e) {
			throw new \Exception($e->getMessage());
		}
		
				
		//Process
		if($result['Contents']) foreach($result['Contents'] as $object) {
			
			$key = $object['Key'];
			
			//Skip Directory 'object'
			if($key == $prefix . '/') {
				continue;
			}
			
			//Get Object
			$saveAs = $this->storageDir . '/' . $this->logFileName($key);
			$this->s3->getObject([
				'Bucket' => $bucket,
				'Key'	 => $key,
				'SaveAs' => $saveAs
			]);
			
			//Delete Object. Must do before next run.
			if($this->deleteLogsFromS3) {
				$this->s3->deleteObject([
					'Bucket' => $this->bucket,
					'Key'    => $key
				]);
			}
			
			
			$files[] = $saveAs;
		}
		
		return $files;
	}
	
	/**
	* Filter log file name for local save
	* @param string $key
	* @return string
	*/
	protected function logFileName($key) {
		return str_replace( ['logs/', '/'], ['', '_'], $key) . '.txt';
	}
	
	
	/**
	* Parse Log files from local FS
	* @param array $localFiles return value from ::download()
	* @param $deleteAfterParse mixed true|DELETE to remove files; falsey|KEEP to keep files
	* @return array
	* @throws Exception
	*/
	public function parseLogs($localFiles = [], $deleteAfterParse = 'DELETE') {
		
		$deleteAfterParse = ( in_array($deleteAfterParse, [true, 'DELETE', 'delete'], true) ) ? true : false;
		
		if(!$localFiles) {
			return [];
		}
		
		$output = [];
		
		foreach($localFiles as $f) {
			
			//HTTP 206 is a partial request (e.g. chunk of a stream)
			//Buffer and combine this into one record when appropriate
			$buffer206 = [];
			
			$content = file_get_contents($f);
			
			$lines = explode("\n", $content);
			foreach($lines as $line) {
				
				$key  = null;
				$size = null;
				$date = null;
				$ip   = null;
				
				//GET OBJECT REQS only
				if(false === strpos($line, 'REST.GET.OBJECT')) {
					continue;
				}
				
				//200 Delivered and 206 Partial requests only
				if( false === strpos($line, 'HTTP/1.1" 200') && false === strpos($line, 'HTTP/1.1" 206') ) {
					continue;
				}
				
				//Don't count these log file downloads
				if( false !== strpos($line, rtrim($this->bucket . '/' . $this->prefix . '/', '/')) ) {
					continue;
				}
				
				//File Key
				if(preg_match('/GET \/([a-zA-Z0-9_\/\.\-]+)/', $line, $match)) {
					$key = $match[1];
				}
				
				//Bytes Transferred
				if($key && preg_match('/(200|206) \- ([0-9]+)/', $line, $match)) {
					$httpStatus = (int)$match[1];
					$size       = (int)$match[2];
				}
				
				//Date (extract [08/Jul/2015:03:48:07 +0000] format )
				if($size && preg_match('/\[([0-9]{2}.+)\]/', $line, $match)) {
					$dateString = $match[1];
					$time = strtotime($dateString);
					$date = date('Y-m-d H:i:s', $time);
				}
				
				//IP Address
				if($date) {
					$lineCopy = str_replace(' +0000]', '+0000]', $line); //remove space before TZ
					$ip = explode(' ', $lineCopy)[3];
				}
				
				//Got full data set ($date only exists if $size & $key)
				if($ip) {
					
					//HTTP 206 -- partial request
					if($httpStatus === 206) {
						
						//Combine 206s from IP for Object into one request
						$requestHash = md5($ip . '|' . $key);
						if(!isset($buffer206[$requestHash])) {
							$buffer206[$requestHash] = [
								'date'   => $date,
								'object' => $key,
								'ip'     => $ip,
								'bytes'  => $size
							];
						} else {
							$buffer206[$requestHash]['bytes'] += $size;
						}
					}
					
					if($httpStatus === 200) {
						$output[] = [
							'date'  =>$date,
							'object'=>$key,
							'bytes' =>$size,
							'ip'    =>$ip,
							'http'  =>$httpStatus
						];
					}
					
				} //end if IP
			} //end foreach line
			
			//Add combined 206s to $output for storage
			foreach($buffer206 as $hash => $data) {
				$output[] = [
					'date'  =>$data['date'],
					'object'=>$data['object'],
					'bytes' =>$data['bytes'],
					'ip'    => $data['ip'],
					'http'  =>206
				];
			}
			
			if($deleteAfterParse) {
				unlink($f);
			}
			
		} //end file loop
		
		return $output;
	}

}