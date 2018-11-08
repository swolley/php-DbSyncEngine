<?php
try {
	require_once __DIR__.'/config.php';			//engine configs
	require_once __DIR__.'/libs/Database.php';	//db methods
	require_once __DIR__.'/libs/Syncer.php';	//syncs tables

	date_default_timezone_set('UTC');

	$start_timestamp = intval(time());

	@require_once __DIR__.'/maps.php';			//mapping between db's tables
	//echo "\tEntities map...\t\t\t\t\t\t\t[OK]" . PHP_EOL;
	//get last execution from file 'lastsync'
	$last_exec_timestamp = file_get_contents(LAST_SYNC_FILE);

	if($last_exec_timestamp && strlen($last_exec_timestamp) === 0) {
		$last_exec_timestamp = 0;
	}

	$last_exec_timestamp = intval($last_exec_timestamp);

	//connect to datatabases
	$from_conn = new Database(
		$config['from_db']['type'],
		$config['from_db']['host'],
		$config['from_db']['port'],
		$config['from_db']['dsn'],
		$config['from_db']['db'],
		$config['from_db']['user'],
		$config['from_db']['pass']
	);

	$to_conn = new Database(
		$config['to_db']['type'],
		$config['to_db']['host'],
		$config['to_db']['port'],
		$config['to_db']['dsn'],
		$config['to_db']['db'],
		$config['to_db']['user'],
		$config['to_db']['pass']
	);

	$from_conn->query('set time_zone = "+00:00"');
	$to_conn->query('set time_zone = "+00:00"');

	//echo "\tDB connections...\t\t\t\t\t\t[OK]" . PHP_EOL; 
	
	//if(LOG){
		//	$log = new Log();
		//}
		
		//sync procedure
	if(isset($map) && count($map) > 0){
		$sync = new Syncer($from_conn, $to_conn, $last_exec_timestamp);
		$sync->exec($map);
		//$sync->getSummary();
		if($sync->totFound() > 0){
			echo date('d/m/Y H:i:s', $start_timestamp) . ": Checking updates after " . date('d/m/Y H:i:s', $last_exec_timestamp) . PHP_EOL;
			$sync->printDetails();
			$end_timestamp = intval(time());
			$duration = $end_timestamp - $start_timestamp;
			echo "\tScript execution: {$duration}s" . PHP_EOL;
		}
	
		//if(LOG){
		//TODO: integrare eventualmente sistema di logging alternativo a stampa in console
		//	$log->write(__DIR__ . '/log');
		//}
	}

	//updates last execution timestamp
	//TODO: se ci sono errori aggiorna comunque il timestamp e quindi non ritenta alla prossima esecuzione
	file_put_contents(LAST_SYNC_FILE, $start_timestamp);
} catch (Exception $ex) {
	echo "\t" . date('d/m/Y H:i:s', time()) . ": " . $ex->getMessage();
}
