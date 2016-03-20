<?php

namespace mongoyii;

use Yii;
use CLogRoute;

/**
* EMongoLogRoute extends CLogRoute and provides logging
* into MongoDB.
* It is the mongodb equivalent of CDbLogRoute
*/

class LogRoute extends CLogRoute
{
	/**
	 * @var string the connectionId of the EMongoClient component
	 */
	public $connectionId = 'mongodb';

	/**
	 * Name of the collection the logs should be stored to.
	 * @var string
	 */
	public $logCollectionName = 'YiiLog';
	
	/**
	 * Get a MongoCollection object
	 * @return MongoCollection - Instance of MongoCollection
	 */
	public function getMongoConnection()
	{
		return Yii::app()
			->{$this->connectionId}
			->selectDatabase()
			->{$this->logCollectionName};
	}

	/**
	 * Stores log messages into database.
	 * @param array $logs list of log messages
	 */
	public function processLogs($logs)
	{
		$collection = $this->getMongoConnection();
		foreach($logs as $log){
			$collection->insertOne(
				array(
					'level' => $log[1],
					'category' => $log[2],
					'logtime' => (int)$log[3],
					'message' => $log[0],
				)
			);
		}
	}
}