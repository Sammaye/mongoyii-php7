<?php

use koma136\mongoyii\Document;
use koma136\mongoyii\Model;

class User extends Document
{
	/** @virtual */
	public $avatar;
	
	public $username;
	
	public $addresses = array();
	
	public $url = null;
	
	public $interests = array();
	
	public $mainSkill;

	public $otherSkills;

	public function scopes()
	{
		return array(
			'programmers' => array(
				'condition' => array('job_title' => 'programmer'),
				'sort' => array('name' => 1),
				'skip' => 1,
				'limit' => 3
			)
		);
	}

	public function behaviors()
	{
		return array(
			'koma136\mongoyii\behaviors\TimestampBehavior'
		);
	}

	public function rules()
	{
		return array(
			array('username', 'koma136\mongoyii\validators\UniqueValidator', 'className' => 'User', 'attributeName' => 'username', 'on' => 'testUnqiue'),
			array('addresses', 'subdocument', 'type' => 'many', 'rules' => array(
				array('road, town, county, post_code', 'safe'),
				array('telephone', 'numerical', 'integerOnly' => true)
			)),
			array('mainSkill, otherSkills', 'safe'),
			array('url', 'subdocument', 'type' => 'one', 'class' => 'SocialUrl'),
			array('_id, username, addresses', 'safe', 'on'=>'search'),
		);
	}

	public function collectionName()
	{
		return 'users';
	}

	public function relations()
	{
		return array(
			'many_interests' => array('many', 'Interest', 'i_id'),
			'one_interest' => array('one', 'Interest', 'i_id'),
			'embedInterest' => array('many', 'Interest', '_id', 'on' => 'interests'),
			'where_interest' => array('many', 'Interest', 'i_id', 'where' => array('name' => 'jogging'), 'cache' => false),
			'primarySkill' => array('one', 'Skill', '_id', 'on' => 'mainSkill'),
			'secondarySkills' => array('many', 'Skill', '_id', 'on' => 'otherSkills'),
		);
	}

	public function attributeLabels()
	{
		return array(
			'username' => 'name'
		);
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

class SocialUrl extends Model
{
	public function rules()
	{
		return array(
			array('url, caption', 'numerical', 'integerOnly' => true),
		);
	}
}