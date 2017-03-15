<?php

require_once 'bootstrap.php';

use koma136\mongoyii\Document;
use koma136\mongoyii\Query;

class MongoCursorTest extends CTestCase
{
	public function testFind()
	{
		for($i=0;$i<=4;$i++){
			$u = new User();
			$u->username = 'sammaye';
			$u->save();
		}

		$c = User::model()->find();

		$this->assertInstanceOf('koma136\mongoyii\Cursor', $c);
		$this->assertTrue(count(iterator_to_array($c)) > 0);

		foreach($c as $doc){
			$this->assertTrue($doc instanceof Document);
			$this->assertEquals('update', $doc->getScenario());
			$this->assertFalse($doc->getIsNewRecord());
			$this->assertInstanceOf('MongoDB\BSON\ObjectID', $doc->_id);
			break;
		}
	}

	/**
	 * @covers koma136\mongoyii\Cursor::__construct
	 */
	public function testDirectInstantiation()
	{
		// No longer supported by the new driver
	}

	/**
	 * @covers koma136\mongoyii\Query
	 */
	public function testEMongoCriteria()
	{
		for($i=0;$i<=4;$i++){
			$u = new User();
			$u->username = 'sammaye';
			$u->save();
		}

		$c = new Query([
			'model' => User::model(),
			'condition' => array('username' => 'sammaye'), 
			'limit' => 3, 
			'skip' => 1
		])->all();
		
		$sc = iterator_to_array($c);

		$this->assertInstanceOf('koma136\mongoyii\Cursor', $c);
		$this->assertTrue(count($sc) > 0);
		// see also $this->testSkipLimit()
		$this->assertEquals(3, count($sc));

	}

	public function testSkipLimit()
	{
		for($i=0;$i<=4;$i++){
			$u = new User();
			$u->username = 'sammaye';
			$u->save();
		}

		$c = User::model()->find([],['skip' => 1, 'limit' => 3]);

		$this->assertInstanceOf('koma136\mongoyii\Cursor', $c);
		$this->assertTrue(count(iterator_to_array($c)) == 3);
	}

	public function tearDown()
	{
		Yii::app()->mongodb->drop();
		parent::tearDown();
	}
}