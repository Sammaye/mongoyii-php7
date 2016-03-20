<?php

namespace mongoyii;

use ReflectionClass;

use yii;
use CApplicationComponent;
use CValidator;

use MongoDB\Client;
use MongoDB\Database as DriverDatabase;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\ReadPreference;
use MongoDB\BSON\ObjectID;

use mongoyii\Database;
use mongoyii\Collection;
use mongoyii\Exception;

/**
 * EMongoClient
 *
 * The MongoDB and MongoClient class combined.
 *
 * Quite deceptively the magic functions of this class actually represent the DATABASE not the connection.
 * This is in contrast to MongoClient whos' own represent the SERVER.
 *
 * Normally this would represent the MongoClient or Mongo and it is even named after them and implements
 * some of their functions but it is not due to the way Yii works.
 */
class Client extends CApplicationComponent
{
	/**
	 * The server string (connection string pre-1.3)
	 * @var string
	 */
	public $uri;
	
	/**
	 * Additional options for the connection constructor
	 * @var array
	 */
	public $options = [];
	
	public $driverOptions = [];

	/**
	 * The name of the database
	 * @var string
	 */
	public $db;

	/**
	 * Enables logging to the profiler
	 * @var boolean
	 */
	public $enableProfiling = false;
	
	/**
	 * @var integer number of seconds that query results can remain valid in cache.
	 * Use 0 or negative value to indicate not caching query results (the default behavior).
	 *
	 * In order to enable query caching, this property must be a positive
	 * integer and {@link queryCacheID} must point to a valid cache component ID.
	 *
	 * The method {@link cache()} is provided as a convenient way of setting this property
	 * and {@link queryCachingDependency} on the fly.
	 *
	 * @see cache
	 * @see queryCachingDependency
	 * @see queryCacheID
	 */
	public $queryCachingDuration = 0;
	
	/**
	 * @var CCacheDependency|ICacheDependency the dependency that will be used when saving query results into cache.
	 * @see queryCachingDuration
	 */
	public $queryCachingDependency;
	
	/**
	 * @var integer the number of SQL statements that need to be cached next.
	 * If this is 0, then even if query caching is enabled, no query will be cached.
	 * Note that each time after executing a SQL statement (whether executed on DB server or fetched from
	 * query cache), this property will be reduced by 1 until 0.
	 */
	public $queryCachingCount = 0;
	
	/**
	 * @var string the ID of the cache application component that is used for query caching.
	 * Defaults to 'cache' which refers to the primary cache application component.
	 * Set this property to false if you want to disable query caching.
	 */
	public $queryCacheID = 'cache';
	
	/**
	 * The Mongo Connection instance
	 * @var Mongo MongoClient
	 */
	private $client;
	
	/**
	 * The database instance
	 * @var MongoDB
	 */
	private $dbs;
	
	private $activeDb;
	
	/**
	 * Caches reflection properties for our objects so we don't have
	 * to keep getting them
	 * @var array
	 */
	private $meta = array();
	
	/**
	 * The default action is to find a getX whereby X is the $k param
	 * you input.
	 * The secondary function, if not getter found, is to get a collection
	 */
	public function __get($k)
	{
		$getter = 'get' . $k;
		if(method_exists($this, $getter)){
			return $this->$getter();
		}
		return $this->selectDatabase($k);
	}
	
	/**
	 * Will call a function on the database or error out stating that the function does not exist
	 * @param string $name
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($name, $parameters = array())
	{
		if(!method_exists($this->getClient(), $name)){
			return parent::__call($name, $parameters);
		}
		return call_user_func_array(array($this->getClient(), $name), $parameters);
	}
	
	public function init()
	{
		if(!extension_loaded('mongodb')){
			throw new EMongoException(
				Yii::t(
					'yii', 
					'We could not find the MongoDB extension ( http://php.net/manual/en/mongodb.installation.php ), please install it'
				)
			);
		}

		// We copy this function to add the subdocument validator as a built in validator
		CValidator::$builtInValidators['subdocument'] = 'mongoyii\validators\SubdocumentValidator';

		$this->client = new Client($this->uri, $this->options);

		if(!is_array($this->db)){
			throw new Exception(Yii::t(
				'yii', 
				'The db option needs to be an array of databases indexed by name'
			));
		}else{
			foreach($this->db as $k => $v){
				
				$options = [];
				if(is_numeric($k)){
					$name = $v;
				}else{
					$name = $k;
					$options = $v;
				}
				
				$this->selectDatabase($name, $options);
			}
		}
		
		parent::init();
	}

	/**
	 * Gets the connection object
	 * Use this to access the Mongo/MongoClient instance within the extension
	 * @return Mongo MongoClient
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * Selects a different database
	 * @param $name
	 * @return MongoDB
	 */
	public function selectDatabase($name = null, $options = [])
	{
		if(isset($options['active'])){
			$this->activeDb = $name;
		}
		
		if($name){
			if(isset($this->dbs[$name])){
				return $this->dbs[$name];
			}
			$db = $this->dbs[$name] = new Database(
				$this->getclient()->selectDatabase($name, $options),
				$this
			);
			return $db;
		}
		
		// If we have a default database set let's go looking for it
		if($this->activeDb && isset($this->dbs[$this->activeDb])){
			return $this->dbs[$this->activeDb];
		}elseif($this->activeDb){
			throw new Exception($name . ' is default but does not exist');
		}
		
		// By default let's return the first in the list
		foreach($this->dbs as $db){
			return $db;
		}
	}
	
	public function dropDatabase($databaseName, $options = [])
	{
		if($this->client->dropDatabase($databaseName, $options)){
			unset($this->dbs[$databaseName]);
		}
	}

	/**
	 * A wrapper for the original processing
	 * @param string $name
	 * @return MongoCollection
	 */
	public function selectCollection($collectionName, $options = [], $databaseName = null)
	{
		if(!$databaseName){
			$databaseName = $this->selectDatabase()->databaseName;
		}
		return $this->getClient()->selectCollection($databaseName, $collectionName, $options);
	}
	
	/**
	 * Sets the document cache for any particular document (EMongoDocument/EMongoModel)
	 * sent in as the first parameter of this function.
	 * Will not cache actual EMongoDocument/EMongoModel instances
	 * only active classes that inherit these
	 * @param $o
	 */
	public function setDocumentCache($o)
	{
		if(
			$this->getDocumentCache(get_class($o)) === array() && // Run reflection and cache it if not already there
			(get_class($o) != 'Document' && get_class($o) != 'Model') /* We can't cache the model */
		){
			$_meta = array();
			
			$reflect = new ReflectionClass(get_class($o));
			$class_vars = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC); // Pre-defined doc attributes
			
			foreach($class_vars as $prop){
				
				if($prop->isStatic()){
					continue;
				}
				
				$docBlock = $prop->getDocComment();
				$field_meta = array(
					'name' => $prop->getName(),
					'virtual' => $prop->isProtected() || preg_match('/@virtual/i', $docBlock) <= 0 ? false : true 
				);
				
				// Lets fetch the data type for this field
				// Since we always fetch the data type for this field we make a regex that will only pick out the first
				if(preg_match('/@var ([a-zA-Z]+)/', $docBlock, $matches) > 0){
					$field_meta['type'] = $matches[1];
				}
				$_meta[$prop->getName()] = $field_meta;
			}
			$this->meta[get_class($o)] = $_meta;
		}
	}
	
	/**
	 * Get a list of the fields (attributes) for a document from cache
	 * @param string $name
	 * @param boolean $include_virtual
	 * @return array
	 */
	public function getFieldCache($name, $include_virtual = false)
	{
		$doc = isset($this->meta[$name]) ? $this->meta[$name] : array();
		$fields = array();
		
		foreach($doc as $name => $opts){
			if($include_virtual || !$opts['virtual']){
				$fields[] = $name;
			}
		}
		return $fields;
	}
	
	/**
	 * Just gets the document cache for a model
	 * @param string $name
	 * @return array
	 */
	public function getDocumentCache($name)
	{
		return isset($this->meta[$name]) ? $this->meta[$name] : array();
	}

	/**
	 * Create ObjectId from timestamp.
	 * This function is not actively used it is
	 * here as a helper for anyone who needs it
	 * @param int $yourTimestamp
	 * @return MongoID
	 */
	public function createMongoIdFromTimestamp($yourTimestamp)
	{
		static $inc = 0;
		
		$ts = pack('N', $yourTimestamp);
		$m = substr(md5(gethostname ()), 0, 3);
		$pid = pack('n', getmypid());
		$trail = substr(pack('N', $inc ++), 1, 3);
		
		$bin = sprintf("%s%s%s%s", $ts, $m, $pid, $trail);
		
		$id = '';
		for($i = 0; $i < 12; $i ++){
			$id .= sprintf("%02X", ord($bin[$i]));
		}
		return new ObjectID($id);
	}

	/**
	 * Sets the parameters for query caching.
	 * This method can be used to enable or disable query caching.
	 * By setting the $duration parameter to be 0, the query caching will be disabled.
	 * Otherwise, query results of the new SQL statements executed next will be saved in cache
	 * and remain valid for the specified duration.
	 * If the same query is executed again, the result may be fetched from cache directly
	 * without actually executing the SQL statement.
	 * 
	 * @param integer $duration
	 *        	the number of seconds that query results may remain valid in cache.
	 *        	If this is 0, the caching will be disabled.
	 * @param CCacheDependency|ICacheDependency $dependency
	 *        	the dependency that will be used when saving
	 *        	the query results into cache.
	 * @param integer $queryCount
	 *        	number of SQL queries that need to be cached after calling this method. Defaults to 1,
	 *        	meaning that the next SQL query will be cached.
	 * @return static the connection instance itself.
	 * @since 1.1.7
	 */
	public function cache($duration, $dependency = null, $queryCount = 1)
	{
		$this->queryCachingDuration = $duration;
		$this->queryCachingDependency = $dependency;
		$this->queryCachingCount = $queryCount;
		return $this;
	}
	
	public function getSerialisedQuery($criteria = array(), $fields = array(), $sort = array(), $skip = 0, $limit = null)
	{
		$query = array(
			'$query' => $criteria,
			'$fields' => $fields,
			'$sort' => $sort,
			'$skip' => $skip,
			'$limit' => $limit
		);
		return json_encode($query);
	}
	
	/**
	 *
	 * @return array the first element indicates the number of query statements executed,
	 *         and the second element the total time spent in query execution.
	 */
	public function getStats()
	{
		$logger = Yii::getLogger();
		$timings = $logger->getProfilingResults(null, 'extensions.MongoYii.EMongoDocument.findOne');
		$count = count($timings);
		$time = array_sum($timings);
		$timings = $logger->getProfilingResults(null, 'extensions.MongoYii.EMongoDocument.insert');
		$count += count($timings);
		$time += array_sum($timings);
		$timings = $logger->getProfilingResults(null, 'extensions.MongoYii.EMongoDocument.find');
		$count += count($timings);
		$time += array_sum($timings);
		$timings = $logger->getProfilingResults(null, 'extensions.MongoYii.EMongoDocument.deleteByPk');
		$count += count($timings);
		$time += array_sum($timings);
		$timings = $logger->getProfilingResults(null, 'extensions.MongoYii.EMongoDocument.updateByPk');
		$count += count($timings);
		$time += array_sum($timings);
		$timings = $logger->getProfilingResults(null, 'extensions.MongoYii.EMongoDocument.updateAll');
		$count += count($timings);
		$time += array_sum($timings);
		$timings = $logger->getProfilingResults(null, 'extensions.MongoYii.EMongoDocument.deleteAll');
		$count += count($timings);
		$time += array_sum($timings);
		
		return array($count, $time);
	}
}