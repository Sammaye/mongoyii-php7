<?php

require_once 'bootstrap.php';

class MongoClientTest extends CTestCase
{
	/**
	 * @covers sammaye\mongoyii\Client
	 */
	public function testSettingUpConnection()
	{
		$mongo = Yii::app()->mongodb;
		$this->assertInstanceOf('sammaye\mongoyii\Client', $mongo);
	}

	/**
	 * @covers sammaye\mongoyii\Client::selectCollection
	 */
	public function testSelectCollection()
	{
		$mongo = Yii::app()->mongodb;

		$this->assertInstanceOf('sammaye\mongoyii\Collection', $mongo->selectCollection('t'));
	}

	/**
	 * @covers sammaye\mongoyii\Client::selectDatabase
	 */
	public function testGetDB()
	{
		$mongo = Yii::app()->mongodb;
		$this->assertInstanceOf('sammaye\mongoyii\Database', $mongo->selectDatabase());
	}

	/**
	 * @covers EMongoClient::getDefaultWriteConcern
	 */
	public function testWriteConcern()
	{
		// No longer done by the extension directly
	}

	/**
	 * @covers sammaye\mongoyii\Client::createMongoIdFromTimestamp
	 */
	public function testCreateMongoIDFromTimestamp()
	{
		$mongo = Yii::app()->mongodb;
		$id = $mongo->createMongoIdFromTimestamp(time());
		$this->assertTrue($id instanceof MongoId);
	}
}