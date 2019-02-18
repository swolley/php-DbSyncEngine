<?php
namespace Syncer\Maps;
use 
	Syncer\Core\AbstractEntity,
	Syncer\Core\Database,
	Syncer\Core\Utilities;

final class Data extends AbstractEntity
{
	use Utilities;

	public function __construct(array &$dbsConf)
	{
		parent::__construct(
			$dbsConf,
			['sourceDb' => 'destinationDb'],
			['sourceTable' => 'destinationTable'],
			['primaryKey']
		);
	}

	public function getRowsToInsert(Database &$fromConn, Database &$toConn) : array
	{
		return 'your operations to find rows to insert';
	}

	public function getRowsToUpdate(Database &$fromConn, Database &$toConn) : array
	{
		return 'your operations to find rows to update';
	}

	public function getRowsToDelete(Database &$fromConn, Database &$toConn) : array
	{
		return 'your operations to find rows to delete';
	}
}
