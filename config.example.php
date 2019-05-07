<?php
define("LAST_SYNC_FILE", 'lastsync');	//don't change
define("SYNCER_LOG_FILENAME", '../logs/logs_syncer.log');	//don't change

$dbs_conf = [
	'fromDb' => [
		'driver' => 'mysql',
		'host' => 'host',
		'port' => 3306,
		'user' => 'user',
		'password' => 'password',
		'dbName' => 'dbname'
	],
	'toDb' => [
		'driver' => 'mysql',
		'host' => 'host',
		'port' => 3306,
		'user' => 'user',
		'password' => 'password',
		'dbName' => 'dbname'
	]
];
