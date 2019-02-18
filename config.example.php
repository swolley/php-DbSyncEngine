<?php
define("LAST_SYNC_FILE", 'lastsync');	//don't change
define("SYNCER_LOG_FILENAME", '../logs/logs_syncer.log');	//don't change

$dbs_conf = [
	'fromDb' => [
		'type' => 'mysql',
		'host' => 'host',
		'port' => 3306,
		'user' => 'user',
		'pass' => 'password',
		'db' => 'dbname',
		'dsn' => 'UFT8'
	],
	'toDb' => [
		'type' => 'mysql',
		'host' => 'host',
		'port' => 3306,
		'user' => 'user',
		'pass' => 'password',
		'db' => 'dbname',
		'dsn' => 'UFT8'
	]
];
