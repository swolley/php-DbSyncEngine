<?php
namespace Syncer\Core;

abstract class AbstractEntity
{	
	/**
	 * @var	array		$_fromDb			origin database connection's parameters
	 * @var	array		$_toDb				destination database connection's parameters
	 * @var	string		$_fromTable			origin table name
	 * @var	string		$_toTable			destination table name
	 * @var	array		$_keysMap			mapping between primary or unique tables' keys
	 * @var	array|null	$_insertFieldsMap	mapping between tables' columns for insert queries
	 * @var	array|null	$_updateFieldsMap	mapping between tables' columns for update queries
	 * @var array		$_foundRows			row filtered during initial research and used for the sync procedure
	 */
	protected
		$_fromDb,
		$_toDb,
		$_fromTable,
		$_toTable,
		$_keysMap,
		$_insertFieldsMap,
		$_updateFieldsMap,
		$_foundRows;

	/**
	 * @param 	array 						$dbsConf			array with alla dbs configurations
	 * @param 	array 						$dbsMap				associative array of db names in format [fromDb => toDb]
	 * @param 	array 						$tablesMap 			associative array of tables names in format [fromTable => toTable]
	 * @param 	array 						$keysMap			associative array of fields to use as unique reference between tables in format [fromPrimary1 => toPrimary1, ..., fromPrimaryN => toPrimaryN]
	 * @param 	array 						$fieldsTopInsert	[optional] associative array of fields' name references in format [fromfield1 => toField1, ..., fromfieldN => toFieldN] to use during INSERT queries
	 * @param 	array 						$fieldsTopUpdate 	[optional] associative array of fields' name references in format [fromfield1 => toField1, ..., fromfieldN => toFieldN] to use during UPDATE queries
	 * @throws \BadMethodCallException							if any parameter missing
	 * @throws \InvalidArgumentException						if any parameter is not conform
	 */
	public function __construct(array &$dbsConf, array $dbsMap, array $tablesMap, array $keysMap, $insertFieldsMap = null, $updateFieldsMap = null)
	{
		//validates parameters
		if (!isset($dbsConf, $dbsMap, $tablesMap, $keysMap)) {
			throw new \BadMethodCallException("Missing parameters");
		}
		if (count($dbsMap) !== 1 || count($tablesMap) !== 1 || count($keysMap) === 0) {
			throw new \InvalidArgumentException("Wrong paramaters");
		}

		//sets fields
		$this->_fromDb = $dbsConf[(array_keys($dbsMap))[0]];
		$this->_toDb = $dbsConf[array_pop($dbsMap)];
		$this->_fromTable = (array_keys($tablesMap))[0];
		$this->_toTable = array_pop($tablesMap);
		$this->_keysMap = $this->parseMapField($keysMap);
		$this->_insertFieldsMap = $this->parseMapField($insertFieldsMap);
		$this->_updateFieldsMap = $this->parseMapField($updateFieldsMap);
	}

	/**
	 * reads and filters rows from source db
	 * @return	array				found rows totals
	 */
	public function findRows() : array
	{
		$from_conn = new Database($this->_fromDb);
		$to_conn = new Database($this->_toDb);

		$this->_foundRows = [
			'insert' => $this->getRowsToInsert($from_conn, $to_conn),
			'update' => $this->getRowsToUpdate($from_conn, $to_conn),
			'delete' => $this->getRowsToDelete($from_conn, $to_conn)
		];

		//returns total row found
		return [
			'insert' => count($this->_foundRows['insert']),
			'update' => count($this->_foundRows['update']),
			'delete' => count($this->_foundRows['delete'])
		];
	}

	/**
	 * filters rows to be inserted
	 * @param 	Database	$fromConn	source datatabase connection object
	 * @param 	Database	$toConn		destination datatabase connection object
	 * @return	array 					found rows
	 */
	protected abstract function getRowsToInsert(Database &$fromConn, Database &$toConn) : array;

	/**
	 * filters rows to be updated
	 * @param 	Database	$fromConn	source datatabase connection object
	 * @param 	Database	$toConn		destination datatabase connection object
	 * @return	array 					found rows
	 */
	protected abstract function getRowsToUpdate(Database &$fromConn, Database &$toConn) : array;

	/**
	 * filters rows to be deleted
	 * @param 	Database	$fromConn	source datatabase connection object
	 * @param 	Database	$toConn		destination datatabase connection object
	 * @return	array 					found rows
	 */
	protected abstract function getRowsToDelete(Database &$fromConn, Database &$toConn) : array;

	/**
	 * remaps and write changes to destination db
	 * @return	array 	write operation statistics
	 */
	public function syncRows() : array
	{
		$conn = new Database($this->_toDb);
		$errors = [];
		return [
			'insert' => $this->insertRows($conn, $errors),
			'update' => $this->updateRows($conn, $errors),
			'delete' => $this->deleteRows($conn, $errors)
		];
	}

	/**
	 * if passed associative array returns key=>value as origin=>destination format 
	 * if passed indexed array returns value=>value as origin=>destination format 
	 * @param	mixed	$fields	list of fields to parse
	 * @return	mixed			parsed associative array or null if no map rules passed
	 */
	public function parseMapField($fields)
	{
		if ($fields === null || self::isAssoc($fields)) {
			return $fields;
		} else {
			$newFields = [];
			foreach (array_values($fields) as $value) {
				$newField[$value] = $value;
			}
			return $newField;
		}
	}

	/**
	 * returns if passed array is associative
	 * @param	array	$array	array to check
	 * @return	bool			array is associative or indexed
	 */
	protected static function isAssoc(array $array) : bool
	{
		return array_keys($array) !== range(0, count($array) - 1);
	}

	/**
	 * remap the passed row into definitive structure
	 * @param	array	$fieldsToMap	list of fields' mapping structure
	 * @param	array	$item 			row data to be parsed
	 * @return	array	$mapped_item	remapped row data
	 */
	protected function mapper(array $fieldsMap, array $item) : array {
		$mapped_item = [];
		foreach($fieldsMap as $from_key => $to_key) {
			$mapped_item[$to_key] = $item[$from_key];
		}
		return $mapped_item;
	}

	/**
	 * calls insert queries
	 * @param	Database	$conn			datatabase connection object
	 * @return	int			$num_inserted	total num inserted
	 */
	protected function insertRows(Database &$conn) : int {
		//if not exists a specified map structure, it will be setted to row fields
		$num_inserted = 0;
		if(count($this->_foundRows['insert']) === 0) {
			return $num_inserted;
		}

		if($this->_insertFieldsMap === null) {
			$keys = array_keys(array_pop($this->_foundRows['insert']));
			$this->_insertFieldsMap = [];
			foreach($keys as $key) {
				$this->_insertFieldsMap[$key] = $key;
			}
		}
		
		foreach($this->_foundRows['insert'] as $cur) {
			$mapped_row = $this->mapper($this->_insertFieldsMap, $cur);
			if($conn->insert($this->_toTable, $mapped_row, true)) {
				$num_inserted ++;
			}
		}
		
		return $num_inserted;
	}

	/**
	 * calls update queries
	 * @param	Database	$conn			datatabase connection object
	 * @return	int			$num_updated	total num updated
	 */
	protected function updateRows(Database &$conn) : int {
		//FIXME si rompe se non esiste _insertFieldsMap
		//if not exists a specified map structure, it will be used the insert one
		$num_updated = 0;
		if($this->_updateFieldsMap === null) {
			$this->_updateFieldsMap = $this->_insertFieldsMap;
		}
		
		foreach($this->_foundRows['update'] as $cur) {
			$mapped_row = $this->mapper($this->_updateFieldsMap, $cur);
			
			$where = array_map(function($value, $key) use($cur) {
				return "`{$value}`='{$mapped_row[$value]}'";
			}, $this->_keysMap);

			if($conn->update($this->_toTable, $mapped_row, join(" AND ", $where))) {
				$num_updated ++;
			}
		}

		return $num_updated;
	}

	/**
	 * calls delete queries
	 * @param	Database	$conn			datatabase connection object
	 * @return	int			$num_deleted	total num deleted
	 */
	protected function deleteRows(Database &$conn) : int {
		$num_deleted = 0;
		foreach($this->_foundRows['delete'] as $cur) {
			$keys = array_map(function($value, $key) use($cur) {
				return "`{$value}`='{$cur[$value]}'";
			}, $this->_keysMap);

			if($conn->delete($this->_toTable, join(" AND ", $keys))) {
				$num_updated ++;
			}
		}

		return $num_deleted;
	}
}