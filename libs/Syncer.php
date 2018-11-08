<?php
require_once __DIR__ . '/Updater.php';

class Syncer extends Updater {
	
	public function _construct(Database &$fromDb, Database &$toDb, int &$lastSync) {
		parent::_construct($fromDb, $toDb, $lastSync);
	}

	protected function syncEntity(Entity &$entity) : bool {
		$start_timestamp = time();
		$to_sync = $this->findRows($entity);
		$num_to_update = count($to_sync['update']);
		$num_to_insert = count($to_sync['insert']);
		$num_updated = 0;
		$num_inserted = 0;
		
		if($num_to_insert > 0) {
			$num_inserted = $this->insertRows($entity, $to_sync['insert']);
		}
		
		if($num_to_update > 0) {
			$num_updated = $this->updateRows($entity, $to_sync['update']);
		}


		$this->addDetails(
			$entity->fromTable, 
			$entity->toTable, 
			$start_timestamp,
			$num_to_update + $num_to_insert,
			$num_to_update === $num_updated && $num_to_insert === $num_inserted,
			[ 'found' => $num_to_update, 'completed' => $num_updated ],
			[ 'found' => $num_to_insert, 'completed' => $num_inserted ]
		);

		return $num_to_update === $num_updated && $num_to_insert === $num_inserted;
	}

	protected function findRows(Entity &$entity) : array {
		$fieldsList = '`' . implode('`,`', array_keys($entity->fieldsToMap)) . '`';
		$customFilters = count($entity->fieldsToFilter) > 0 ? " AND " . implode(" AND ", $entity->fieldsToFilter) : '';

		return [
			'update' => isset($entity->fieldsToCheck['update']) ? $this->fromDb->select("SELECT {$fieldsList} FROM {$entity->fromTable} WHERE UNIX_TIMESTAMP({$entity->fieldsToCheck['update']})>{$this->lastSync}{$customFilters} AND {$entity->fieldsToCheck['update']}<>{$entity->fieldsToCheck['insert']}") : [],
			'insert' => isset($entity->fieldsToCheck['insert']) ? $this->fromDb->select("SELECT {$fieldsList} FROM {$entity->fromTable} WHERE UNIX_TIMESTAMP({$entity->fieldsToCheck['insert']})>{$this->lastSync}{$customFilters}") : []
		];
	}

	protected function updateRows(Entity &$entity, array &$rows) : int {
		$num_updated = 0;
		foreach($rows as $cur) {
			$mapped_row = $this->mapper($entity->fieldsToMap, $cur);
			$keys = [];
			foreach( $entity->keyMap as $key=>$value){
				$keys[] = "`{$value}`='{$mapped_row[$value]}'";
			}

			if($this->toDb->update($entity->toTable, $mapped_row, join(" AND ", $keys))) {
				$num_updated ++;
			} else {
				$this->addError('update', $entity->fromTable, $entity->toTable, $key, $cur[$key]);
			}
		}

		return $num_updated;
	}

	protected function insertRows(Entity &$entity, array &$rows) : int {
		$num_inserted = 0;
		foreach($rows as $cur) {
			$mapped_row = $this->mapper($entity->fieldsToMap, $cur);
			//$key = array_keys($entity->keyMap)[0];
			if($this->toDb->insert($entity->toTable, $mapped_row)) {
				$num_inserted ++;
			} else {
				$this->addError('insert', $entity->fromTable, $entity->toTable);
			}
		}

		return $num_inserted;
	}
	
	protected function mapper(array &$fieldsMap, array $item) : array {
		$mapped_item = [];
		foreach($fieldsMap as $from_key => $to_key) {
			$mapped_item[$to_key] = $item[$from_key];
		}

		return $mapped_item;
	}
}