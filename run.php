<?php

require 'vendor/Autoload.php';


// AWS & PDO config
$cfg = require 'config/config.php';


/*
Download log files from S3
---------------------------
*/
$logs = new \S3LP\Logs( $cfg['aws'], 'logs/', 'storage/logs/' );

try {
	$files = $logs->download();
} catch(Exception $e) {
	exit( $e->getMessage() );
}

	

/*
Parse logs
Array of GET requests (200 and 206 HTTP status codes)
------------------------------------------------------
*/
try {
	$data = $logs->parseLogs($files, 'DELETE');
	
	if(!$data) {
		exit('No GET requests found in logs');
	}
} catch(Exception $e) {
	exit( $e->getMessage() );
}


/*
Save to DB (if you want, I'm not your boss)
--------------------------------------------
*/
if( isset($cfg['pdo']) ) {
	try {
		$db = new \S3LP\Db( $cfg['pdo'] );
		$inserts = $db->insert($data);
		
		echo "Inserted $inserts rows";
	} catch(Exception $e) {
		exit( $e->getMessage() );
	}
} else {
	
	echo 'No DB connection';
	var_dump($data);
}