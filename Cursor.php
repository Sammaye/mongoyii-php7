<?php

namespace mongoyii;

use MongoDB\Driver\Cursor;

use IteratorIterator;
use Iterator;
use Countable;
use Yii;

use mongoyii\Document;
use mongoyii\Exception;

/**
 * Cursor
 *
 * Represents the Yii edition to the MongoCursor and allows for lazy loading of objects.
 *
 * This class does not support eager loading by default, in order to use eager loading you should look into using this
 * classes reponse with iterator_to_array().
 *
 * I did try originally to make this into a active data provider and use this for two fold operations but the cactivedataprovider would extend
 * a lot for the cursor and the two took quite different constructors.
 */
class Cursor implements Iterator, Countable
{
	/**
	 * @var string
	 */
	public $modelClass;
	
	/**
	 * @var EMongoDocument
	 */
	public $model;
	
	public $partial;
	
	/**
	 * @var array|MongoCursor|EMongoDocument[]
	 */
	private $cursor = array();
	
	/**
	 * @var EMongoDocument
	 */
	private $current;

	/**
	 * The cursor constructor
	 * @param string|EMongoDocument $modelClass - The class name for the active record
	 * @param array|MongoCursor|EMongoCriteria $criteria -  Either a condition array (without sort,limit and skip) or a MongoCursor Object
	 * @param array $fields
	 */
	public function __construct($modelClass, $cursor, $options = [])
	{
		if(is_string($modelClass)){
			$this->modelClass = $modelClass;
			$this->model = Document::model($this->modelClass);
		}elseif($modelClass instanceof Document){
			$this->modelClass = get_class($modelClass);
			$this->model = $modelClass;
		}
		
		$it = new IteratorIterator($cursor);
		$it->rewind();
		$this->cursor = $it;
		
		if($options['partial']){
			$this->partial = true;
		}
	}

	/**
	 * If we call a function that is not implemented here we try and pass the method onto
	 * the MongoCursor class, otherwise we produce the error that normally appears
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 * @throws EMongoException
	 */
	public function __call($method, $params = [])
	{
		if($this->cursor instanceof Cursor && method_exists($this->cursor, $method)){
			return call_user_func_array(array($this->cursor, $method), $params);
		}
		throw new Exception(Yii::t(
			'yii', 
			'Call to undefined function {method} on the cursor', 
			['{method}' => $method]
		));
	}
	
	public function count()
	{
		throw new Exception('Count can no longer be done on the cursor!!');
	}

	/**
	 * Get next doc in cursor
	 * @return EMongoDocument|null
	 */
	public function getNext()
	{
		if($c = $this->cursor->getNext()){
			return $this->current = $this->model->populateRecord($c, true, $this->partial);
		}
		return null;
	}

	/**
	 * Gets the active record for the current row
	 * @return EMongoDocument|mixed
	 * @throws EMongoException
	 */
	public function current()
	{
		if($this->model === null){
			return $this->current = $this->cursor->current();
		}else{
			return $this->current = $this->model->populateRecord(
				$this->cursor->current(), 
				true, 
				$this->partial
			);
		}
	}

	/**
	 * Reset the MongoCursor to the beginning
	 * @return EMongoCursor
	 */
	public function rewind()
	{
		$this->cursor->rewind();
		return $this;
	}

	/**
	 * Get the current key (_id)
	 * @return mixed|string
	 */
	public function key()
	{
		return $this->cursor->key();
	}

	/**
	 * Move the pointer forward
	 */
	public function next()
	{
		$this->cursor->next();
	}

	/**
	 * Check if this position is a valid one in the cursor
	 * @return bool
	 */
	public function valid()
	{
		return $this->cursor->valid();
	}
}