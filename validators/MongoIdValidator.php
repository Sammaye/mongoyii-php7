<?php

namespace mongoyii\validators;

use Yii;
use CValidator;

use MongoDB\BSON\ObjectID;

/**
 * EMongoIdValidator
 *
 * This class is basically designed to be used to cast all fields sent into it as MongoIds.
 * This was created because at the time it was seen as the most flexible, yet easiest way, to accomplish
 * the casting of MongoIds automatically.
 */
class MongoIdValidator extends CValidator
{
	public $allowEmpty = true;

	protected function validateAttribute($object,$attribute)
	{
		$value = $object->$attribute;
		if($this->allowEmpty && $this->isEmpty($value)){
			return;
		}
		
		if(is_array($value)){
			foreach($value as $key=>$attr){
				$value[$key] = $attr instanceof ObjectID ? $attr : new ObjectID($attr);
			}
			$object->$attribute = $value;
		}else{
			$object->$attribute = $object->$attribute instanceof ObjectID ? $object->$attribute : new ObjectID($object->$attribute);
		}
	}
}
