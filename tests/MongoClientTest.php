<?php

require_once 'bootstrap.php';

class MongoClientTest extends CTestCase
{
	/**
	 * @covers koma136\mongoyii\Client
	 */
	public function testSettingUpConnection()
	{
		$mongo = Yii::app()->mongodb;
		$this->assertInstanceOf('koma136\mongoyii\Client', $mongo);
	}

	/**
	 * @covers koma136\mongoyii\Client::selectCollection
	 */
	public function testSelectCollection()
	{
		$mongo = Yii::app()->mongodb;

		$this->assertInstanceOf('koma136\mongoyii\Collection', $mongo->selectCollection('t'));
	}

	/**
	 * @covers koma136\mongoyii\Client::selectDatabase
	 */
	public function testGetDB()
	{
		$mongo = Yii::app()->mongodb;
		$this->assertInstanceOf('koma136\mongoyii\Database', $mongo->selectDatabase());
	}

	/**
	 * @covers EMongoClient::getDefaultWriteConcern
	 */
	public function testWriteConcern()
	{
		// No longer done by the extension directly
	}

	/**
	 * @covers koma136\mongoyii\Client::createMongoIdFromTimestamp
	 */
	public function testCreateMongoIDFromTimestamp()
	{
		$mongo = Yii::app()->mongodb;
		$id = $mongo->createMongoIdFromTimestamp(time());
		$this->assertTrue($id instanceof MongoId);
	}
}