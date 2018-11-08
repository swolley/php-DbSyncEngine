<?php
//file the containes last execution timestamp
//on each execution the script will search on tables' rows created or changed after previous cycle
define("LAST_SYNC_FILE", __DIR__ . '/lastsync');

$sql_from_details = [/*put db's connection details*/];
$sql_to_details = [/*put db's connection details*/];

$config = [
	'from_db' => $sql_from_details,
	'to_db' => $sql_to_details
];
