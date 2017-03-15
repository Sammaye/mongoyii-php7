<?php

use koma136\mongoyii\Document;

class Interest extends Document
{
	public $name;

	public function rules()
	{
		return array(
			array('_id, otherId, username', 'safe', 'on' => 'search'),
		);
	}

	public function collectionName()
	{
		return 'interests';
	}

	/**
	 * Returns the static model of the specified AR class.
	 * @return User the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
}