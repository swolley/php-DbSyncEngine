<?php
namespace Syncer;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . '/config.php';			//engine configs

use Swolley\Logger\Logger as Logger;
use Syncer\Core\Syncer;
use Syncer\Maps;

set_error_handler(function ($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
		return;
	}
	throw new \ErrorException($message, 0, $severity, $file, $line);
});

try {
	/////////////////////////// LOGGER ////////////////////////////////////
	$logger = new Logger();
	$logger->register(FILE, SYNCER_LOG_FILENAME);

	///////////////////////// TIMESTAMPS //////////////////////////////////
	date_default_timezone_set('UTC');
	//set start execution
	$start_timestamp = intval(time());
	//get last execution from file 'lastsync'
	$last_exec_timestamp = intval(file_get_contents(LAST_SYNC_FILE));
	////////////////////////// ENTITIES ///////////////////////////////////
	//get maps classes
	$entities = [];
	foreach (glob(__DIR__ . '/Maps/*.php') as $file) {
		if(basename($file) !== 'Map.example.php') {
			require_once $file;
			$class_name = "Syncer\\Maps\\" . basename($file, '.php');
			if (class_exists($class_name)) {
				array_push($entities, new $class_name($dbs_conf));
			}
		}
	}
	//////////////////////////// SYNC /////////////////////////////////////
	//sync procedure
	if (count($entities) > 0) {
		$sync = new Syncer();
		$sync->exec(...$entities);

		$logger->create(INFO, "Checked todos after " . date('d/m/Y H:i:s', $last_exec_timestamp) . ' on ' . count($entities) . ' tables', FILE);
		foreach ($sync->getDetails() as $detail) {
			$logger->create(INFO, $detail, FILE);
		}
		$logger->create(INFO, 'Script execution: ' . (intval(time()) - $start_timestamp) . 's', FILE);
		$logger->create(INFO, "------------------------------", FILE);
	}

	//updates last execution timestamp
	file_put_contents(LAST_SYNC_FILE, $start_timestamp);
} catch (\Exception $ex) {
	$logger->create(ERROR, $ex->getMessage(), FILE);
	echo "An exception occured. See log file for more info" . PHP_EOL;
} catch (\ErrorException $ex) {
	$logger->create(ERROR, $ex->getMessage(), FILE);
	echo "An error occured. See log file for more info" . PHP_EOL;
}
