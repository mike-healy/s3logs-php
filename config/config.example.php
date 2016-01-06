<?php
return [

	'pdo' => [
		'dsn'      => 'mysql:host=localhost;dbname=my_db_name', //for PostgreSQL  pgsql:host=localhost;dbname=myPgDB
		'user'     => 'my_username',
		'password' => '',
		'table'	   => 's3logs'
	],

	'aws' => [
		'credentials' => ['key'=>'', 'secret'=>''],
		'bucket'  => '',
		'region'  => 'ap-southeast-2', 
		'version' => '2006-03-01',
		'scheme'  => 'https'	//try http if SSL not setup in your environment
	]
];

/*

MySQL Create Table example
You need to create your table before running
---------------------------

CREATE TABLE `s3logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `bucket` varchar(50) NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `http_status` smallint unsigned NOT NULL DEFAULT '200',
  `object` varchar(300) NOT NULL,
  `bytes_transferred` int unsigned NOT NULL DEFAULT '0'
);

	
S3 Regions
---------------
	
US East (N. Virginia)		us-east-1	
US West (Oregon)			us-west-2
US West (N. California)		us-west-1
EU (Ireland)				eu-west-1
EU (Frankfurt)				eu-central-1	
Asia Pacific (Singapore)	ap-southeast-1
Asia Pacific (Sydney)		ap-southeast-2
Asia Pacific (Tokyo)		ap-northeast-1
South America (Sao Paulo)	sa-east-1

*/