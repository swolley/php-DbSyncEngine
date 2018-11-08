<?php
class Entity {
	/**
	 * name of source table
	 */
	public $fromTable;
	/**
	 * name of destination table
	 */
	public $toTable;
	/**
	 * associative array of fields' name to compare with timestamp in source table in format ['insert' => fieldName, 'update' => fieldName ]
	 */
	public $fieldsToCheck;
	/**
	 * associative array of fields' conditions to be used for custom filters
	 */
	public $fieldsToFilter;
	/**
	 * associative array of fields' name references in format [fromfield1 => toField1, ..., fromfieldN => toFieldN]
	 */
	public $fieldsToMap;
	/**
	 * associative array of fields to use as unique reference between tables in format [fromField => toField]
	 */
	public $keyMap;

	public function __construct(string $fromTable, string $toTable, array $fieldsToCheck, array $fieldsToMap, array $keyMap, array $fieldsToFilter = []) {
		$this->fromTable = $fromTable;
		$this->toTable = $toTable;
		$this->fieldsToCheck = $fieldsToCheck;
		$this->fieldsToFilter = $fieldsToFilter;
		$this->fieldsToMap = $fieldsToMap;
		$this->keyMap = $keyMap;
	}
}