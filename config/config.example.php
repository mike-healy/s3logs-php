<?php
return [

	'pdo' => [
		'dsn'      => 'mysql:host=127.0.0.1;dbname=my_db_name', //for PostgreSQL  pgsql:host=localhost;dbname=myPgDB
		'user'     => 'my_username',
		'password' => '',
		'table'	   => 's3logs'
	],

	'aws' => [
		'bucket'  => '',
		'user'    => '',
		'secret'  => ''
	]
];

/*

MySQL Create Table example
---------------------------

CREATE TABLE `s3logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `bucket` varchar(50) NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `http_status` smallint unsigned NOT NULL DEFAULT '200',
  `object` varchar(300) NOT NULL,
  `bytes_transferred` int unsigned NOT NULL DEFAULT '0'
);

*/