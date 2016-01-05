<?php namespace S3LP;

class Db {

	/**
	* @var object connection
	*/
	protected $pdo;
	
	protected $table;
	
	/**
	* CONSTRUCTOR
	* @param mixed $pdo connection or array(dsn => , user => , password => )
	*/
	public function __construct($pdo, $table = '') {
		
		//Use Connection
		//----------------
		if(is_object($pdo) && $pdo instanceof \PDO) {
			$this->pdo = $pdo;
			
			if(!$table) {
				throw new \Exception('Table name not passed to S3LP\Db constructor');
			}
			
			$this->table = $table;
			return;
		}
		
		//Connect	
		//--------
		try {
			$this->pdo = new \PDO($pdo['dsn'], $pdo['user'], $pdo['password']);
		} catch(\PDOException $e) {
			throw new \Exception($e);
		}
		
		//Table Name
		if($table) {
			$this->table = $table; //use parameter first
		} else {
			$this->table = $pdo['table'];
		}
	}
	
	
	/**
	* @param array $data
	* @return int
	* @throws Exception
	*/
	public function insert($data) {
		
		$inserts = 0;
		
		$sql = "INSERT INTO `{$this->table}` (bucket, date, http_status, object, bytes_transferred) VALUES (:bucket, :date, :http, :object, :bytes)";
		$query = $this->pdo->prepare($sql);
		
		foreach($data as $r) {
			
			$parts  = explode('/', $r['object']);
			$bucket = array_shift($parts); 	//first portion
			$object = implode('/', $parts);
			
			$result = $query -> execute([
				':bucket' => $bucket,
				':date'   => $r['date'],
				':http'   => $r['http'],
				':object' => $object,
				':bytes'  => $r['bytes']
			]);
			
			if($result) {
				$inserts++;
			} else {
				$e = $query->errorInfo();
				throw new \Exception($e[2], $e[1]);
			}
		}
		
		return $inserts;
	}
	
}