<?php

namespace monogyii;

use Yii;

use monogyii\Exception;

class Collection
{
	public $client;
    public $collection;
    
    public function __debugInfo()
    {
        return $this->collection->__debugInfo();
    }

    public function __toString()
    {
        return $this->collection->__toString();
    }
    
    public function __call($name, $parameters = [])
	{
	    if(method_exists($this->collection, $name)){
	        return call_user_func_array(array($this->collection, $name), $parameters);
	    }
	    throw new Exception("$name is not a callable function");
	}
	
	public function __construct($collection, $client)
	{
	    $this->collection = $collection;
	    $this->client = $client;
	}
	
	public function findOne($filter = [], $options = [])
	{
		$collectionName = $this->collection->getCollectionName();

		$serialisedQuery = json_encode([
			'$query' => $filter,
			'$options' => $options
		]);
		
		Yii::trace("Executing find: $serialisedQuery", 'mongoyii\Collection');
		
		if($this->getDbConnection()->enableProfiling){
			$token = "mongoyii\\$collectionName.find($serialisedQuery)";
			Yii::beginProfile($token, 'mongoyii\Collection.findOne');
		}
				
		$res = $this->collection->findOne($filter, $options);
		if($this->getDbConnection()->enableProfiling){
			Yii::endProfile($token, 'mongoyii\Collection.findOne');
		}
		return $res;
	}
	
	public function find($filter = [], $options = [])
	{
		$collectionName = $this->collection->getCollectionName();

		$serialisedQuery = json_encode([
			'$query' => $filter,
			'$options' => $options
		]);
		
		Yii::trace("Executing find: $serialisedQuery", 'mongoyii\Collection');
		
		if($this->getDbConnection()->enableProfiling){
			$token = "mongoyii\\$collectionName.find($serialisedQuery)";
			Yii::beginProfile($token, 'mongoyii\Collection.find');
		}
				
		$res = $this->collection->find($filter, $options);
		if($this->getDbConnection()->enableProfiling){
			Yii::endProfile($token, 'mongoyii\Collection.find');
		}
		return $res;
	}
    
    public function insertOne($document, array $options = [])
    {
    	$textDoc = json_encode($document);
    	$textOptions = json_encode($options);
    	$collectionName = $this->collection->getCollectionName();
    	
		Yii::trace("Executing insertOne: {\$document: $textDoc, \$options: $textOptions }", "mongoyii\Collection");

		if($this->client->enableProfiling){
			Yii::beginProfile(
				"mongoyii\\$collectionName.insertOne({\$document: $textDoc, \$options: $textOptions })", 
				'mongoyii\Collection.insertOne'
			);
		}
		
		$res = $this->collection->insertOne($document, $options);
		
		if($this->client->enableProfiling){
			Yii::endProfile(
				"mongoyii\\$collectionName.insertOne({\$document: $textDoc, \$options: $textOptions })", 
				'mongoyii\Collection.insertOne'
			);
		}
		
		return $res;
    }
    
    public function insertMany(array $documents, array $options = [])
    {
    	$textDoc = json_encode($documents);
    	$textOptions = json_encode($options);
    	$collectionName = $this->collection->getCollectionName();
    	
		Yii::trace("Executing insertMany: {\$document: $textDoc, \$options: $textOptions }", "mongoyii\Collection");

		if($this->client->enableProfiling){
			Yii::beginProfile(
				"mongoyii\\$collectionName.insertMany({\$document: $textDoc, \$options: $textOptions })", 
				'mongoyii\Collection.insertMany'
			);
		}
		
		$res = $this->collection->insertOne($documents, $options);
		
		if($this->client->enableProfiling){
			Yii::endProfile(
				"mongoyii\\$collectionName.insertMany({\$document: $textDoc, \$options: $textOptions })", 
				'mongoyii\Collection.insertMany'
			);
		}
		
		return $res;
    }

    public function updateOne($filter, $update, array $options = [])
    {
		$collectionName = $this->collection->getCollectionName();

		$textFilter = json_encode($filter);
		$textUpdate = json_encode($update);
		$textOptions = json_encode($options);
		
		Yii::trace(
			"Executing updateOne: {\$query: $textFilter, \$document: $textUpdate, \$options: $textOptions }", 
			"mongoyii\Collection"
		);
		
		if($this->client->enableProfiling){
			$token = "mongoyii\\$collectionName.updateOne({\$query: $textFilter, \$document: $textUpdate, \$options: $textOptions })";
			Yii::beginProfile($token, 'mongoyii\Collection.updateOne');
		}

		$res = $this->collection->updateOne($filter, $update, $options);

		if($this->client->enableProfiling){
			Yii::endProfile($token, 'mongoyii\Collection.updateOne');
		}
		
		return $res;
    }
    
    public function updateMany($filter, $update, array $options = [])
    {
		$collectionName = $this->collection->getCollectionName();

		$textFilter = json_encode($filter);
		$textUpdate = json_encode($update);
		$textOptions = json_encode($options);
    	
		Yii::trace(
			"Executing updateAll: {\$query: $textFilter, \$document: $textUpdate, \$options: $textOptions }", 
			"mongoyii\Collection"
		);
		
		if($this->client->enableProfiling){
			$token = "mongoyii\\$collectionName.updateMany({\$query: $textFilter, \$document: $textUpdate, \$options: $textOptions })";
			Yii::beginProfile($token, 'mongoyii\Collection.updateMany');
		}

		$res = $this->collection->updateOne($filter, $update, $options);

		if($this->client->enableProfiling){
			Yii::endProfile($token, 'mongoyii\Collection.updateMany');
		}
		
		return $res;
    }

    public function deleteOne($filter, array $options = [])
    {
    	$collectionName = $this->collection->getCollectionName();
    	$textQuery = json_encode($filter);
    	$textOptions = json_encode($options);
    	
        Yii::trace(
        	"Executing deleteOne: {\$query: $textQuery, \$options: $textOptions }", 
        	"mongoyii\Collection"
        );

		if($this->client->enableProfiling){
			Yii::beginProfile(
				"mongoyii\\$collectionName.deleteOne({\$query: $textQuery, \$options: $textOptions })",
				'mongoyii\Collection.deleteOne'
			);
		}
		
		$res = $this->collection->deleteOne($filter, $options);
		
		if($this->client->enableProfiling){
			Yii::endProfile(
				"mongoyii\\$collectionName.deleteOne({\$query: $textQuery, \$options: $textOptions })", 
				'mongoyii\Collection.deleteOne'
			);
		}
		
		return $res;
    }
    
    public function deleteMany($filter, array $options = [])
    {
    	$collectionName = $this->collection->getCollectionName();
    	$textQuery = json_encode($filter);
    	$textOptions = json_encode($options);
    	
        Yii::trace(
        	"Executing deleteMany: {\$query: $textQuery, \$options: $textOptions }", 
        	"mongoyii\Collection"
        );

		if($this->client->enableProfiling){
			Yii::beginProfile(
				"mongoyii\\$collectionName.deleteMany({\$query: $textQuery, \$options: $textOptions })",
				'mongoyii\Collection.deleteMany'
			);
		}
		
		$res = $this->collection->deleteMany($filter, $options);
		
		if($this->client->enableProfiling){
			Yii::endProfile(
				"mongoyii\\$collectionName.deleteMany({\$query: $textQuery, \$options: $textOptions })", 
				'mongoyii\Collection.deleteMany'
			);
		}
		
		return $res;
    }
}