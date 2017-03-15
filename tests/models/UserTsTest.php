<?php

use koma136\mongoyii\Document;

/**
* Testing behaviors/EMongoTimestampBehaviour
*/
class UserTsTest extends Document
{
	public $username;

	public function behaviors()
	{
		return array(
			'EMongoTimestampBehaviour' => array(
				'class' => 'koma136\mongoyii\behavior\TimestampBehavior',
				'onScenario' => array('testMe'),
			)
		);
	}

	public function collectionName()
	{
		return 'users';
	}
}

/**
* Testing behaviors/EMongoTimestampBehaviour whereas here its broken
*/
class UserTsTestBroken extends Document
{
	public $username;

	public function behaviors()
	{
		return array(
			'EMongoTimestampBehaviour' => array(
				'class' => 'koma136\mongoyii\behavior\TimestampBehavior',
				'onScenario' => 'testMeFalse',
			)
		);
	}

	public function collectionName()
	{
		return 'users';
	}
}

/**
* Testing behaviors/EMongoTimestampBehaviour whereas here its broken.
* This time onScenario and notOnScenario are defined
*/
class UserTsTestBroken2 extends Document
{
	public $username;

	public function behaviors()
	{
		return array(
			'EMongoTimestampBehaviour' => array(
				'class' => 'koma136\mongoyii\behavior\TimestampBehavior',
				'onScenario' => array('testMeFalseOn'),
				'notOnScenario' => array('testMeFalseOn'),
			)
		);
	}

	public function collectionName()
	{
		return 'users';
	}
}