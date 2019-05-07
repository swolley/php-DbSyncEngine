<?php
namespace Syncer\Core;

class Syncer
{
	/** @var	array	$_summary	contains stats for every elaborated entity */
	protected $_summary = [];

	/** 
	 * @param	AbstractEntity	$entities	list of entities
	 * @return	bool				if procedure ended correctly
	 */
	public function exec(AbstractEntity ...$entities) : bool
	{
		$results = [];
		$total_completed = 0;
		foreach ($entities as $entity) {
			$start_timestamp = time();
			$result = $this->syncEntity($entity);
			if ($result['found'] === $result['synced']) {
				$total_completed++;
			}/* else {
				array_push($this->_errors, $entity->getErrors());
			}*/

			array_push($this->_summary, array_merge([ 
				'entity' => get_class($entity), 
				'start' => $start_timestamp, 
				'end' => time() 
			], $result));
		}

		return $total_completed === count($entities);
	}

	/**
	 * @param	AbstractEntity	$entity	single entity instance
	 * @return	array			single entity totals
	 */
	protected function syncEntity(AbstractEntity $entity) : array
	{
		return [
			'found' => $entity->findRows(),
			'synced' => $entity->syncRows()
		];
	}

	/**
	 * @return	array	array with totals
	 */
	public function getSummary() : array {
		return $this->_summary;
	}

	/**
	 * @return	array	verbose version of summary
	 */
	public function getDetails() : array {
		$response = [];
		foreach($this->_summary as $detail){
			array_push($response, 
				'AbstractEntity: ' . $detail['entity'] . ', '
				. 'Found: ' . array_sum($detail['found']) . ', '
				. 'Inserted: ' . $detail['synced']['insert'] . ', '
				. 'Updated: ' . $detail['synced']['update'] .', '
				. 'Deleted: ' . $detail['synced']['delete'] .', '
				. 'Duration: ' . ($detail['end'] - $detail['start']) . "s "
				. ($detail['found'] === $detail['synced'] ? '[OK]' : '[FAILED]')
			);
		}

		return $response;
	}
}