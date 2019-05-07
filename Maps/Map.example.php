<?php
namespace Syncer\Maps;
use 
	Syncer\Core\AbstractEntity,
	Syncer\Core\Utilities;
use	Swolley\Database\Interfaces\IConnectable;

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

	public function getRowsToInsert(IConnectable &$fromConn, IConnectable &$toConn) : array
	{
		return ['your operations to find rows to delete'];
	}

	public function getRowsToUpdate(IConnectable &$fromConn, IConnectable &$toConn) : array
	{
		return ['your operations to find rows to delete'];
	}

	public function getRowsToDelete(IConnectable &$fromConn, IConnectable &$toConn) : array
	{
		return ['your operations to find rows to delete'];
	}
}
