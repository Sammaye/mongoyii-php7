<?php

namespace koma136\mongoyii;

use MongoDB\GridFS\Bucket;

use koma136\mongoyii\Collection;
use koma136\mongoyii\Exception;

class Database
{
	public $client;
	
	/** @var  \MongoDB\Database */
	public $database;
	
	public function __construct($database, $client)
	{
		$this->database = $database;
		$this->client = $client;
	}
	
	public function __get($collectionName)
	{
		return $this->selectCollection($collectionName);
	}
	
	public function __call($name, $parameters = [])
	{
		if(method_exists($this->database, $name)){
			return call_user_func_array(array($this->database, $name), $parameters);
		}
		throw new Exception("$name is not a callable function");
	}

	public function selectCollection($collectionName, array $options = [])
	{
		return new Collection(
			$this->database->selectCollection($collectionName, $options),
			$this->client
		);
	}
	
	public function getGridFs($options = [])
	{
		return new Bucket($this->client->getManager(), $this->database->getDatabaseName(), $options);
	}

	public function __toString()
	{
		return $this->database->__toString();
	}
	
	public function __debugInfo()
	{
		return $this->database->__debugInfo();
	}
}