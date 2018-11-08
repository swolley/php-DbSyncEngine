<?php
abstract class Updater {
	/**
	 * source Database instance
	 */
	protected $fromDb;
	/**
	 * destination Database instance
	 */
	protected $toDb;
	/**
	 * last sync timestamp. Used to select rows in source tables
	 */
	protected $lastSync;

	/**
	 * results' data
	 */
	protected $result = [];

	/**
	 * details' data
	 */
	protected $details = [];
	
	/**
	 * anomalies' data
	 */
	protected $errors = [];

	/**
	 * constructor
	 */
	public function __construct(Database &$fromDb, Database &$toDb, int &$lastSync) {
		$this->fromDb = &$fromDb;
		$this->toDb = &$toDb;
		$this->lastSync = &$lastSync;
	}

	public function getResult() {
		return $this->result;
	}

	public function getDetails() {
		return $this->details;
	}

	public function getErrors() {
		return $this->errors;
	}

	public function totFound() {
		$tot = 0;
		return array_reduce($this->details, function(&$sum, $cur){ return $sum += $cur['found']; }, $tot);
	}

	/**
	 * startsup sync procedure
	 * @param	array	$map	entities map to sync
	 * @return	bool			true if number of completed queries = number of found rows
	 */
	public function exec(array &$map) : bool {
		$start_timestamp = time();
		$results = [];
		$completed = 0;
		foreach($map as $entity) {
			if($entity instanceof Entity && $this->syncEntity($entity)) {
				$completed ++;
			}
		}

		$this->result = [
			'class' => get_class($this),
			'start' => $start_timestamp,
			'end' => time(),
			'total' => $completed,
			'completed' => $completed === count($map)
		];

		return $completed === count($map);
	}

	/**
	 * add an error into errors array
	 * @param	string	$queryType	query CRUD type
	 * @param	string	$fromTable	source table's name
	 * @param	string	$toTable	destination table's name
	 * @param	string	$key		primary key's name
	 * @param	mixed	$value		primary key's value
	 */
	protected function addError(string $queryType, string $fromTable, string $toTable, string $key = null, $value = null) {
		$this->errors[] = [
			'query' => $queryType,
			'entity' => "{$fromTable} / {$toTable}",
			'key' => $key,
			'value' => $value
		];
	}

	/**
	 * add details into details array
	 * @param	string	$fromTable			source tables's name
	 * @param	string	$toTables			destination table's name
	 * @param	string	$start_timestamp	procedure start timestamp
	 * @param	int		$found				found rows to sync
	 * @param	bool	$completed			if every found row has been synced
	 * @param	array	$update				update's result array
	 * @param	array	$insert				inserts's result array
	 */
	protected function addDetails(string $fromTable, string $toTable, string $start_timestamp, int $found, bool $completed, array $update=[], array $insert=[]) {
		$this->details[] = [
			'entity' => "{$fromTable}/{$toTable}",
			'duration' => time() - intval($start_timestamp),
			'found' => $found,
			'completed' => $completed,
			'update' => $update,
			'insert' => $insert
		];
	}

	/**
	 * stringed version of shorted result's details
	 */
	public function printSummary() {
		echo "\tClass: ". $this->result['class']
			. ', Duration: ' . (intval($this->result['end']) - intval($this->result['start'])) . 's'
			. "\t\t\t\t\t"
			. ($this->result['completed'] ? '[OK]' : '[FAILED]') . PHP_EOL;
		if(!$this->result['completed']) {
			$this->getErrors();
		}
	}

	/**
	 * stringed version of long detailed result's details
	 */
	public function printDetails() {
		$completed = true;
		foreach($this->details as $detail){
			echo "\tEntity: {$detail['entity']}, Found: {$detail['found']}, Updated: {$detail['update']['completed']}, Inserted: {$detail['insert']['completed']}, Duration: {$detail['duration']}s\t\t"
				. ($detail['completed'] ? '[OK]' : '[FAILED]') . PHP_EOL;
			if(!$detail['completed']){
				$completed = false;
			}
		}

		if(!$completed) {
			$this->printErrors();
		}
	}

	/**
	 * stringed version of error details
	 */
	public function printErrors() {
		foreach($this->errors as $error){
			echo "\t\tERROR: {$error['query']} - {$error['entity']} - " . (isset($error['key'], $error['value']) ? $error['key'] . "=" . $error['value'] : '') . PHP_EOL;
		}
	}

	/**
	 * main function that handles selection and CRUD queries
	 * @param	Entity	$entity	reference of mapped entity to check
	 * @return	bool			return if found rows = synced rows
	 */
	protected abstract function syncEntity(Entity &$entity) : bool;

	/**
	 * extracts from source tables the rows to be synced
	 * @param	Entity	$entity	reference to mapped entity
	 * @return	array			rows to update + new rows to insert
	 */
	protected abstract function findRows(Entity &$entity) : array;

	/**
	 * updates passed rows in destination table
	 * @param	Entity	$entity	reference to mapped entity
	 * @param	array	$rows	found rows to be updated in destination table
	 * @return	int				number of updated rows
	 */
	protected abstract function updateRows(Entity &$entity, array &$rows) : int;

	/**
	 * inserts passed rows into the destination table
	 * @param	Entity	$entity	reference to mapped entity
	 * @param	array	$rows	found rows to be inserted into destination tables
	 * @return	int				number of inserted rows
	 */
	protected abstract function insertRows(Entity &$entity, array &$rows) : int;

	/**
	 * maps source's db fields with destination's db fields and return the remapped final row
	 * @param	array	$fieldsMap	reference to array cotaining mapping rules
	 * @param	array	$item		item to maps
	 * @return	array				new item remapped with new keys' name
	 */
	protected abstract function mapper(array &$fieldsMap, array $item) : array;
}