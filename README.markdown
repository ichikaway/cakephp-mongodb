# MongoDB datasource for CakePHP 2.8+

## Requirements
PHP 5.6, PHP 7+
CakePHP 2.x (> 2.8, < 3.0)
php-mongodb extension (not the php-mongo extension).

## Installation

This repository should be installed in the same way as any other plugin.
See http://book.cakephp.org/2.0/en/plugins/how-to-install-plugins.html

To install the driver for use in a single application:

	cd my/app/plugins
	git clone git://github.com/HandsetDetection/MongoDBLib.git MongoDBLib

To install the driver for use in any/multiple application(s)

	# where ROOT is the name of the directory parent to the base index.php of CakePHP.
	cd ROOT/plugins
	git clone git://github.com/HandsetDetection/MongoDBLib.git MongoDBLib
	
## Sample Code

To use this DB driver, install (obviously) and define a db source such as follows:

	<?php
	// app/config/database.php
	class DATABASE_CONFIG {

		public $default = array(
			'driver' => 'MongoDBLib.mongodbSource',
			'database' => 'driver',
			'host' => 'localhost',
			'port' => 27017,
			/* optional auth fields
			'login' => 'mongo',	
			'password' => 'awesomeness',
			'replicaset' => array('host' => 'mongodb://hoge:hogehoge@localhost:27021,localhost:27022/blog', 
			                      'options' => array('replicaSet' => 'myRepl')
					     ),
			*/
		);  


Model files need to have mongoSchema property, or make use of the schemaless behavior.

Mongo uses a primary key named "\_id" (cannot be renamed). It can be any format you like but if you don't explicitly set it Mongo will use an automatic 24 character (uu)id.

## Update Notes

This plugin was derived from the most excellent cakephp-mongodb plugin by Yasushi Ichikawa, originally built for CakePHP 1.3.
cakephp-mongodb works with the php-mono extension which has since been deprecated and will not run on PHP 7.0.

This plugin is an updated version of cakephp-mongodb and strives to be as backwardly compatible as possible. It uses the
newer php-mongodb extension. If you're migrating from cakephp-mongodb to this plugin please be aware there are a number
of breaking changes. Namely, all the types and classes have changed.

1) Types : All the types have changed.

MongoId becomes MongoDB\BSON\ObjectID
MongoCode becomes MongoDB\BSON\JavaScript
MongoDate becomes MongoDB\BSON\UTCDateTime
MongoRegex becomes MongoDB\BSON\Regex
MongoBinData becomes MongoDB\BSON\Binary
MongoInt32 php-mongodb extension now chooses the best type automagically.
MongoInt64 php-mongodb extension now chooses the best type automagically.
MongoDBRef deprecated - no corresponding class
MongoMinKey becomes MongoDB\BSON\MinKey
MongoMaxKey becomes MongoDB\BSON\MaxKey
MongoTimestamp becomes MongoDB\BSON\Timestamp

2) Classes

There's too much detail to cover here with the class changes so check out these references below.
Old classes : http://php.net/manual/en/book.mongo.php
New classes : http://php.net/manual/en/set.mongodb.php

3) All CRUD calls are the same and should return identical information to the cakephp-mongodb plugin.
Calls doing mapReduce, aggregation and executing database commands should be extensively tested.


## Original Authors

Yasushi Ichikawa ([ichikaway](http://twitter.com/ichikaway))
Andy Dawson ([AD7six](http://twitter.com/AD7six))


## Original Contributors

[Predominant](http://github.com/predominant/) : Cleanup code, add documentation
[Jrbasso](http://github.com/jrbasso/) : Cleanup code
[tkyk](http://github.com/tkyk/) : Fix bug, Add some function.


## Original Reference

Reference code, Thank you!
[Nate Abele's lithium mongoDB datasource](http://li3.rad-dev.org/)
[Joél Perras' divan](http://github.com/jperras/divan/)
