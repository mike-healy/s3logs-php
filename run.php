<?php

require 'vendor/Autoload.php';


// AWS & PDO config
$cfg = require 'config/config.php';


if(   FALSE   ) {

/*
Download log files from S3
---------------------------
*/
$logs = new \S3LP\Logs( $cfg['aws'], 'storage/logs/' );
try {
	$logs->download();
} catch(Exception $e) {
	
}


/*
Parse logs
Array of GET requests (200 and 206 HTTP status codes)
------------------------------------------------------
*/
try {
	$data = $logs->parseLogs('DELETE');
	
	if( !$data ) {
		exit('No requests found');
	}
} catch(Exception $e) {
	
}

}

// DUMMY TEST DATA
$data = [
	['object'=>'mikehealy.au/pathtomy/file.jpg', 'date'=>'2015-12-19 08:22:14', 'http'=>200, 'bytes'=>mt_rand(200, 102830239)],
];

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
}