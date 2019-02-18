<?php
namespace Syncer\Core;

use \PDO;

class Database extends PDO
{

	public function __construct(array $params)
	{
		if (!isset($params['type'], $params['host'], $params['port'], $params['dsn'], $params['db'], $params['user'], $params['pass'])) {
			throw new BadMethodCallException("Missing parameters");
		}

		parent::__construct(
			$params['type'] . ':host=' . $params['host'] . ';port=' . $params['port'] . ';charset=' . $params['dsn'] . ';dbname=' . $params['db'],
			$params['user'],
			$params['pass'],
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 5
			]
		);
		$this->query('set time_zone = "+00:00"');
	}

	/**
	 * select
	 * @param   string      $sql        An SQL string
	 * @param   array       $array      Paramaters to bind
	 * @param   constant    $fetchMode  A PDO Fetch mode
	 * @return  mixed
	 */
	public function select($sql, $array = array(), $fetchMode = PDO::FETCH_ASSOC)
	{
        //try {
		$sth = $this->prepare($sql);
		foreach ($array as $key => $value) {
			$sth->bindValue("$key", $value);
		}

		$sth->execute();
		return $sth->fetchAll($fetchMode);
        //} catch (PDOException $e) {
        //    print "error!: " . $e->getMessage();
        //}
	}

	/**
	 * insert
	 * @param   string  $table  A name of table to insert into
	 * @param   string  $data   An associative array
	 */
	public function insert($table, $data, $ignore = false)
	{
		//$insertedId = false;
		ksort($data);
		$fieldNames = implode('`, `', array_keys($data));
		$fieldValues = ':' . implode(', :', array_keys($data));

        //try {
		$sth = $this->prepare("INSERT " . ($ignore ? "IGNORE " : "") . "INTO $table (`$fieldNames`) VALUES ($fieldValues)");
		foreach ($data as $key => $value) {
					//if($key === 'file_byte')
					//	$sth->bindParam(":$key", $value, PDO::PARAM_LOB);
					//else
			$sth->bindValue(":$key", $value);
		}
                
                //if($sth->execute())
					//$insertedId = $this->lastInsertId();

		return $sth->execute();
        /*} catch (PDOException $e) {
            print "PDOerror!: " . $e->getMessage();
            return false;
        } catch (Exception $e) {
            print "error!: " . $e->getMessage();
            return false;
        }*/
	}

	/**
	 * update
	 * @param   string  $table  A name of table to insert into
	 * @param   string  $data   An associative array
	 * @param   string  $where  the WHERE query part
	 */
	public function update($table, $data, $where)
	{
        //try {
		ksort($data);

		$fieldDetails = null;
		foreach ($data as $key => $value) {
			$fieldDetails .= "`$key`=:$key,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		$sth = $this->prepare("UPDATE $table SET $fieldDetails WHERE $where");

		foreach ($data as $key => $value) {
			$sth->bindValue(":$key", $value);
		}

		return $sth->execute();
        // } catch (PDOException $e) {
        //     print "error!: " . $e->getMessage();
        // }
	}

	/**
	 * delete
	 * 
	 * @param   string  $table
	 * @param   string  $data An associative array
	 * @param   string  $where
	 * @param   integer $limit
	 * @return  integer Affected Rows
	 */
	public function delete($table, $data, $where, $limit = 1)
	{
        //try {
		$sth = $this->prepare("DELETE FROM $table WHERE $where LIMIT $limit");

		foreach ($data as $key => $value) {
			$sth->bindValue(":$key", $value);
		}
		$ret = $sth->execute();
		return $ret;
        // } catch (PDOException $e) {
        //     print "error!: " . $e->getMessage();
		// 		//return [];
        // }
	}

}