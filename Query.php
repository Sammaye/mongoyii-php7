<?php

namespace mongoyii;

use MongoDB\BSON\Regex;
use MongoDB\Driver\Cursor as MongoCursor;

use Yii;
use CComponent;
use CMap;

use mongoyii\Client;
use mongoyii\Document;
use mongoyii\Cursor;
use mongoyii\Exception;

/**
 * This is the extensions version of CDbCriteria.
 *
 * This class is by no means required however it can help in your programming.
 *
 * @property array $condition
 * @property array $sort
 * @property int $skip
 * @property int $limit
 * @property array $project
 */
class Query extends CComponent
{
	public static $db;
	
	/**
	 * Holds information for what should be projected from the cursor
	 * into active models. The reason for this obscure name is because this
	 * is what it is called in MongoDB, basically it is SELECT though.
	 * @var array
	 */
	private $_select = [];
	
	private $_from;
	
	/**
	 * @var array
	 */
	private $_condition = [];
	
	/**
	 * @var array
	 */
	private $_sort = [];
	
	/**
	 * @var int
	*/
	private $_skip = 0;
	
	/**
	 * @var int
	 */
	private $_limit = 0;
	
	private $_options = ['modifiers' => []];

	private $_modelClass;

	/**
	 * Constructor.
	 * @param array $data - criteria initial property values (indexed by property name)
	*/
	public function __construct($data = [])
	{
		foreach($data as $name => $value){
			$this->$name = $value;
		}
	}

	/**
	 * Sets the projection (SELECT in MongoDB Lingo) of the criteria
	 * @param array $document - The document specification for projection
	 * @return EMongoCriteria
	 */
	public function setSelect($document)
	{
		$this->_select = $document;
		return $this;
	}

	/**
	 * This means that the getters and setters for projection will be access like:
	 * $c->project(array('c'=>1,'d'=>0));
	 * @return array
	 */
	public function getSelect()
	{
		return $this->_select;
	}

	public function setFrom($name)
	{
		$this->_from = $name;	
	}

	public function getFrom()
	{
		if(!$this->_from && $this->model){
			// Try and decipher from model if there is one
			$this->_from = $this->model->collectionName();
		}
		return $this->_from;
	}

	/**
	 * Sets the condition
	 * @param array $condition
	 * @return EMongoCriteria
	 */
	public function setCondition(array $condition = [])
	{
		$this->_condition = $condition;
		return $this;
	}
	
	public function getCondition()
	{
		return $this->_condition;
	}
	
	public function andCondition($condition)
	{
		$this->_condition = CMap::mergeArray($condition, $this->_condition);
		return $this;
	}
	
	/**
	 * Append condition to previous ones using the column name as the index
	 * This will overwrite columns of the same name
	 * @param string $column
	 * @param mixed $value
	 * @param string $operator
	 * @return EMongoCriteria
	 */
	public function addCondition($column, $value, $operator = null)
	{
		$this->_condition[$column] = $operator === null ? $value : array($operator => $value);
		return $this;
	}

	/**
	 * Adds an $or condition to the criteria, will overwrite other $or conditions
	 * @param array $condition
	 * @return EMongoCriteria
	 */
	public function addOrCondition($condition)
	{
		$this->_condition['$and'][] = array('$or' => $condition);
		return $this;
	}

	/**
	 * Sets the sort
	 * @param array $sort
	 * @return EMongoCriteria
	 */
	public function setSort(array $sort)
	{
		foreach($sort as $field => $order){
			if($order === 'asc'){
				$sort[$field] = 1;
			}elseif($order === 'desc'){
				$sort[$field] = -1;
			}
		}
		
		$this->_sort = CMap::mergeArray($sort, $this->_sort);
		return $this;
	}

	/**
	 * Gets the sort
	 * @return array
	 */
	public function getSort()
	{
		return $this->_sort;
	}

	/**
	 * Sets the skip
	 * @param int $skip
	 * @return EMongoCriteria
	 */
	public function setSkip($skip)
	{
		$this->_skip = (int)$skip;
		return $this;
	}

	/**
	 * Gets the skip
	 * @return int
	 */
	public function getSkip()
	{
		return $this->_skip;
	}

	/**
	 * Sets the limit
	 * @param int $limit
	 * @return EMongoCriteria
	 */
	public function setLimit($limit)
	{
		$this->_limit = (int)$limit;
		return $this;
	}

	/**
	 * Gets the limit
	 * @return int
	 */
	public function getLimit()
	{
		return $this->_limit;
	}
	
	public function setOptions($options)
	{
		$this->_options = $options;
		return $this;
	}
	
	public function addOption($name, $value)
	{
		$this->_options[$name] = $value;
	}
	
	public function addModifier($name, $value)
	{
		$this->_options['modifiers'][$name] = $value;
	}
	
	public function getOptions()
	{
		return CMap::mergeArray([
			'projection' => $this->select,
			'sort' => $this->sort,
			'skip' => $this->skip,
			'limit' => $this->limit
		], $this->_options);
	}
	
	/**
	 * This function allows you to take a raw options 
	 * parameter from the driver and parse it into this object
	 * could be useful for some cases
	 */
	public function parseOptions($options)
	{
		if(isset($options['projection'])){
			$this->select = $options['projection'];
		}
		
		if(isset($options['sort'])){
			$this->sort = $options['sort'];
		}
		
		if(isset($options['skip'])){
			$this->skip = $options['skip'];
		}
		
		if(isset($options['limit'])){
			$this->limit = $options['limit'];
		}
		
		if(isset($options['modifiers'])){
			foreach($options['modifiers'] as $k => $v){
				$this->addModifier($k, $v);
			}
		}
		
		unset(
			$options['projection'],
			$options['sort'],
			$options['skip'],
			$options['limit'],
			$options['modifiers']
		);
		
		foreach($options as $k => $v){
			$this->addOption($k, $v);
		}
	}
	
	public function setModel($modelClass)
	{
		if(is_string($modelClass)){
			$this->_modelClass = $modelClass;
		}elseif($modelClass instanceof Document){
			$this->_modelClass = get_class($modelClass);
		}
	}
	
	public function getModel()
	{
		if($this->_modelClass){
			$cName = $this->_modelClass;
			return $cName::model();
		}
		return null;
	}
	
	public function getDbConnection()
	{
		if(self::$db !== null){
			return self::$db;
		}
		if($this->model){
			self::$db = $this->model->getDbConnection();
		}else{
			self::$db = Yii::app()->mongodb;
		}
		if(self::$db instanceof Client){
			return self::$db;
		}
		throw new Exception(Yii::t(
			'yii', 
			'MongoDB Active Query requires a "mongodb" mongoyii\Client application component.'
		));
	}
	
	public function getDb()
	{
		return $this->getDbConnection()->selectDatabase();
	}

	/**
	 * Base search functionality
	 * @param string $column
	 * @param string|null $value
	 * @param boolean $partialMatch
	 * @return EMongoCriteria
	 */
	public function compare($column, $value = null, $partialMatch = false)
	{
		$query = array();
		
		if($value === null){
			$query[$column] = null;
		}elseif(is_array($value)){
			$query[$column] = array('$in' => $value);
		}elseif(is_object($value)){
			$query[$column] = $value;
		}elseif(is_bool($value)){
			$query[$column] = $value;
		}elseif(preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/', $value, $matches)){
			$value = $matches[2];
			$op = $matches[1];
			if($partialMatch === true){
				$value = new Regex("$value", 'i');
			}else{
				if(
					!is_bool($value) && !is_array($value) && preg_match('/^([0-9]|[1-9]{1}\d+)$/' /* Will only match real integers, unsigned */, $value) > 0
					&& ( 
						(PHP_INT_MAX > 2147483647 && (string)$value < '9223372036854775807') /* If it is a 64 bit system and the value is under the long max */
						|| (string)$value < '2147483647' /* value is under 32bit limit */
					)
				){
					$value = (int)$value;
				}
			}

			switch($op){
				case "<>":
					$query[$column] = array('$ne' => $value);
					break;
				case "<=":
					$query[$column] = array('$lte' => $value);
					break;
				case ">=":
					$query[$column] = array('$gte' => $value);
					break;
				case "<":
					$query[$column] = array('$lt' => $value);
					break;
				case ">":
					$query[$column] = array('$gt' => $value);
					break;
				case "=":
				default:
					$query[$column] = $value;
					break;
			}
		}
		if(!$query){
			$query[$column] = $value;
		}
		$this->addCondition($column,  $query[$column]);
		return $this;
	}

	/**
	 * Merges either an array of criteria or another criteria object with this one
	 * @param array|EMongoCriteria $criteria
	 * @return EMongoCriteria
	 */
	public function mergeWith($criteria)
	{
		if($criteria instanceof static){
			return $this->mergeWith($criteria->toArray());
		}
		if(is_array($criteria)){
			if(isset($criteria['condition']) && is_array($criteria['condition'])){
				$this->setCondition(CMap::mergeArray($this->condition, $criteria['condition']));
			}
			if(isset($criteria['sort']) && is_array($criteria['sort'])){
				$this->setSort(CMap::mergeArray($this->sort, $criteria['sort']));
			}
			if(isset($criteria['skip']) && is_numeric($criteria['skip'])){
				$this->setSkip($criteria['skip']);
			}
			if(isset($criteria['limit']) && is_numeric($criteria['limit'])){
				$this->setLimit($criteria['limit']);
			}
			if(isset($criteria['select']) && is_array($criteria['select'])){
				$this->setSelect(CMap::mergeArray($this->select, $criteria['select']));
			}
		}
		return $this;
	}
	
	public function one($db = null)
	{
		return $this->queryInternal($db, 'findOne');
	}
	
	public function all($db = null)
	{
		return $this->queryInternal($db);
	}

	protected function queryInternal($db = null, $function = 'find')
	{
		if($db !== null){
			self::$db = $db;
		}
		
		$cursor = null;
		
		if(
			$this->getDbConnection()->queryCachingCount > 0
			&& $this->getDbConnection()->queryCachingDuration > 0
			&& $this->getDbConnection()->queryCacheID !== false
			&& ($cache = Yii::app()->getComponent($this->getDbConnection()->queryCacheID)) !== null
		){
			$this->getDbConnection()->queryCachingCount--;

			$cacheKey = $cacheKey =
				'yii:dbquery:' . $function . ':' . 
				$this->getDbConnection()->uri . ':' . 
				$this->getDb()->getDatabaseName() . ':' . 
				$this->getDbConnection()->getSerialisedQuery(
					$this->condition, 
					$this->select, 
					$this->sort, 
					$this->skip, 
					$this->limit
				) . ':' . 
				$this->from;

			if(($result = $cache->get($cacheKey)) !== false){
				Yii::trace('Query result found in cache', 'extensions.MongoYii.EMongoDocument');
				
				if($function === 'find'){
					$cursor = new Cursor($this->model, $result[0], ['partial' => $this->select ? true : false]);
				}elseif($function === 'findOne'){
					$cursor = $this->model->populateRecord($result[0], true, $this->select ? true : false);
				}else{
					$cursor = $result[0];
				}
			}
		}
		
		if(!$cursor){
			
			$res = $this->getDb()->{$this->from}->$function($this->condition, $this->options);
			
			if($function === 'find'){
				$cursor = new Cursor(
					$this->model,
					$res, 
					['partial' => $this->select ? true : false]
				);
			}elseif($function === 'findOne'){
				$cursor = $this->model->populateRecord($res, true, $this->select ? true : false);
			}else{
				$cursor = $res;
			}
		}
				
		if(isset($cache, $cacheKey)){
			$cache->set(
				$cacheKey,
				$cursor instanceof static ? iterator_to_array($cursor) : $cursor,
				$this->getDbConnection()->queryCachingDuration,
				$this->getDbConnection()->queryCachingDependency
			);
		}
		return $cursor;
	}

	public function __debugInfo()
	{
		return CMap::mergeArray(
			['condition' => $this->condition], 
			$this->options
		);
	}
	
	public function __toArray($onlyCondition = false)
	{
		return $this->toArray($onlyCondition);
	}
	
	/**
	 * @param boolean $onlyCondition -  indicates whether to return only condition part or criteria.
	 * Should be "true" if the criteria is used in EMongoDocument::find() and other common find methods.
	 * @return array - native representation of the criteria
	 */
	public function toArray($onlyCondition = false)
	{
		$result = array();
		if($onlyCondition === true){
			$result = $this->condition;
		}else{
			foreach(array('_condition', '_limit', '_skip', '_sort', '_select') as $name){
				$result[substr($name, 1)] = $this->$name;
			}
		}
		return $result;
	}
}