# MongoYii-php7

A PHP7 edition of [mongoyii](http://sammaye.github.io/MongoYii) designed and working with the [new MongoDB driver](http://php.net/manual/en/set.mongodb.php)

## About this Documentation

This is not a complete rewrite of the old documentation, instead it will only detail the new features/ideals behind the PHP7 extension.

[Please read the old documentation first if you are new to mongoyii](http://sammaye.github.io/MongoYii).

## Test Application

There is a new test application for this extension which is a rewrite of the old test appliction.

[You can find it over here](https://github.com/Sammaye/mongoyii-php7-test).

Running the inbuilt tests are the same as before.

## Issues/Bugs & Questions

Please use the new Github issue tracker for all your questions and bug reports. If you post in the forums please link it in the Github issue tracker.

[You can access the Github issue tracker here](https://github.com/Sammaye/mongoyii-php7/issues).

## Versioning

This extension, like the previous, uses [semantic versioning 2.0.0](http://semver.org/).

## Licence

The licence for this extension remains the same as well, [BSD 3 clause](http://opensource.org/licenses/BSD-3-Clause). To make it short 
and to the point: do whatever you want with it.

## Note About Changes

Before I go into what has changed in this extension it is good to note that many of the changes are not because of my wanting to make them but because 
the MongoDB driver and PHPLib they have released has so dramatically changed the workings from the old drivers that I was forced to conform to this new 
standard of working.

There are some parts of the driver and it's PHPLib you will like and others you definitely wont.

## Installling

All installling is now done through composer. I would NOT recommend installing manually since this extension requires both the driver and the 
PHPLib MongoDB has released to go with it (which is only on composer as well).

To install simply do (for `dev-master`):

	composer require sammaye/mongoyii-php7:*

[You can find the packagist reepository here](https://packagist.org/packages/sammaye/mongoyii-php7).

## Namespacing

This extension is fully namespaced as:

	sammaye\mongoyii

Do not worry! This does not make for too many changes for old applications. It took me 
about 3 hours to rewrite my test application.

For example here is how to declare a new model (taken from my test application):

```php
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;

use sammaye\mongoyii\Document;

/**
 * Represents the article itself, and all of its data
 */
class Article extends Document
{
}
```

And that will give you all the same stuff as before.

Now let me show you an example of configuring the `session` and `cache` component in 
your `main.php` (again, taken from my test application):

```php
'session' => array(
	'class' => 'sammaye\mongoyii\util\Session',
),
'cache' => array(
	'class' => 'sammaye\mongoyii\util\Cache',
),
```

So you see use of the namespaces is very easy to get to grips with.

As a final example, let's take an behaviour:

```php
public function behaviors()
{
	return [
		'TimestampBehavior' => [
			'class' => 'sammaye\mongoyii\behaviors\TimestampBehavior' 
			// adds a nice create_time and update_time Mongodate to our docs
		]
	];
}
```

So, you see: using namespaces in this extension is very easy. If ever in doubt 
which namespace to use look up the file in your project and look at the first 
line where it says something like:

```php
namespace sammaye\mongoyii\validators;
```

Add the class name to that and you have your namespaced class.

## Declaring the Extension

This is easily the part that has changed the most. To start off why don't we 
show an example I use:

```php
'mongodb' => [
	'class' => 'sammaye\mongoyii\Client',
	'uri' => 'mongodb://sam:blah@localhost:27017/admin',
	'options' => [],
	'driverOptions' => [],
	'db' => [
		'super_test' => [
			'writeConcern' => new WriteConcern(1),
			'readPreference' => new ReadPreference(ReadPreference::RP_PRIMARY),
		]
	],
	'enableProfiling' => true
],
```

Now let's break this down:

- I delcare the class as `sammaye\mongoyii\Client`. This is required and will not be variable.
- The `uri` is my server connection string and follows the standard laid out in the [PHP documentation](http://php.net/manual/en/mongodb-driver-manager.construct.php)
- The `options` directly relate to the `uri` options in the [PHP documentation](http://php.net/manual/en/mongodb-driver-manager.construct.php) as well, allowing replica set connections etc
- And the same goes for driver options which are also in the [PHP documentation](http://php.net/manual/en/mongodb-driver-manager.construct.php)
- `enableProfiling` allows you to profile your queries as before
- `db` has now changed to be an array indexed by the names of the databases you wish to connect to. The value of each index being the options for the [PHPLib `Database` object](https://github.com/mongodb/mongo-php-library/blob/master/src/Database.php)

And that is basically it. The write concern and read concern and other 
properties are now done per database as you see instead of on driver level.

If you have other databases in your configuration and want to set a specific 
one as default you can add the `active` option as shown:

```php
'super_test' => [
	'writeConcern' => new WriteConcern(1),
	'readPreference' => new ReadPreference(ReadPreference::RP_PRIMARY),
	'active' => true
]
```

### The Client `__get` Only Gets a Database?

Yes, this is the biggest change between the old driver and the new one.

There is now a clear separation between the client, database, and collection.

So to get the database, ready for fetching a collection you now need to do:

```php
Yii::$app->mongodb->selectDatabase()
```

### Authentication

In the new PHP driver there is no `auth()` function. You must athenticate within the 
`uri` of the `mongodb` component. You will decide how best to sort out your authentication 
database but I decided to put all users into the `admin` database. This makes it 
incredibly easy to authenticate to one database and then switch as I need.

### Using Multiple Databases

As you can see, this extension takes multiple databases into account.

If you are using authentication make sure you either layout your users in a way that 
means you only need one socket connection (like I have above) or make a new component 
for each time you need to authenticate. You cannot authenticate AFTER connecting anymore.

As for recoding the `Document`'s `getDbConnection()` you now use `getCollection()` 
like so (due to the separation I mentioned above):

```php
public function getCollection()
{
	return $this
		->getDbConnection()
		->selectDatabase('my_other_db_not_default')
		->{$this->collectionName()};
}
```

And you are done...

If you really know what you are doing you can actually set a database as `active` making it default for a set of procedures:

```php
Yii::$app->mongodb->selectDatabase('my_other_db', ['active' => true]);
```

But this is for advanced users only!

## Querying

This is the biggest change away from Yii1. Everything else remains the same and does not 
require documenting.

Basically, due to how the new driver no longer uses cursors but instead streams I have 
recoded the EMonmgoDBCriteria object to be `Query` (like in Yii2) and it even works similar to 
how it does in Yii2.

However, it is good to note that the `Document` functions of `find()` and `findOne()` 
return the same as they do in normal Yii1. The return there has not changed.

It is good to note that the way to query has changed through, in accordance with the driver:

```php
Article::find(
	[
		'title' => 'Test'
	],  
	[
		'sort' => ['date_created' => -1],
		'limit' => 2
		// etc
	]
)
```

This is due to how MongoDB no longer uses true cursors and make eager loaded streams. 
As such the entire query must be defined BEFORE making it server side now.

A good place to understand how to query using the new driver is 
to look at the Github documentation for the [MongoDB PHPLib](http://mongodb.github.io/mongo-php-library/classes/collection/).

### Scopes

Due to the change in the `EMongoCriteria` you may need to rewrite scope for them to work. A good example would be:

```php
[
	'condition' => ['deleted' => 0],
	'select' => ['_id' => 1],
	'sort' => ['date' => -1],
	'limit' => 2
	'skip' => 1
]
```

There is not a lot of changes you will see publicly, the biggest one is that 
I do not use the `project` word anymore for `SELECT` in SQL. MongoDB still 
does but I don't.

### Why No EMongoCriteria?

This was a decision put forward by the need to produce clean and workable querying.

I decided, in the end, to make my querying more like Yii2. This actually means you can do this now:

```php
$docs = new Query([
	'from' => 'colllection',
	'condition' => ['what' => 'ever'],
	'limit' => 1
])->all()
```

So, it is a break from Yii1 to Yii2 but it is a good break.

### Query Logging

Query logging is now much more extensive. Instead of just logging queries through the
models it will now log all queries thanks to a small rewrite which should have been 
in the original extension.

Now whenever you get the collection from the MongoDB component in your configuration 
it will return my own custom `Collection` class which has logging tied into it.

Hopefully, this should take some of the guess work out of building applications.

## Notes About Quirks

- The MongoDB driver's PHPLib returns subdocuments as `ArrayObject`s. This means you need to type cast them via `(array)$subdoc` first before you use them in display and forms etc.

## Stuff Not Done

- GridFs. Not my fault. It is actually not there yet in the PHPLib!

## And We Are... Done!

That should be it. Everything else is pretty much the same, cool, huh?

Please do let me know if I have left anything out or need to explain something better.