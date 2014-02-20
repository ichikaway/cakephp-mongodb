# mongoDB datasource for CakePHP

[![Build Status](https://travis-ci.org/ichikaway/cakephp-mongodb.png?branch=cake2.2)](https://travis-ci.org/ichikaway/cakephp-mongodb)
[![Coverage Status](https://coveralls.io/repos/ichikaway/cakephp-mongodb/badge.png?branch=cake2.2)](https://coveralls.io/r/ichikaway/cakephp-mongodb)

## Requirements

- PHP5
- CakePHP >= 2.2.5
- pecl mongo (http://php.net/mongo)

## Installation

this repository should be installed in the same way as any other plugin.

To install the driver for use in a single application:

	cd my/app/Plugin
	git clone git://github.com/ichikaway/cakephp-mongodb.git Mongodb

To install the driver for use in any/multiple application(s)

	# where ROOT is the name of the directory parent to the base index.php of CakePHP.
	cd ROOT/Plugin
	git clone git://github.com/ichikaway/cakephp-mongodb.git Mongodb

### composer
This plugin on the Packagist.

https://packagist.org/packages/ichikaway/cakephp-mongodb
	
## Sample Code

To use this DB driver, install (obviously) and define a db source such as follows:

	<?php
	//app/Config/bootstrap.php
	CakePlugin::load('Mongodb');


	// app/Config/database.php
	class DATABASE_CONFIG {
		public $default = array(
			'datasource' => 'Mongodb.MongodbSource',
			'host' => 'localhost',
			'database' => 'blog',
			'port' => 27017,
			'prefix' => '',
			'persistent' => 'true',
			/* optional auth fields
			'login' => 'mongo',	
			'password' => 'awesomeness',
			'replicaset' => array('host' => 'mongodb://hoge:hogehoge@localhost:27021,localhost:27022/blog', 
			                      'options' => array('replicaSet' => 'myRepl')
					     ),
			*/
		);

		// To make sure all tests are passing create the following entry in app/Config/database.php
		public $test = array(
			'datasource' => 'Mongodb.MongodbSource',
			'database' => 'test_mongo',
			'host' => 'localhost',
			'port' => 27017,
		); 
	}

More detail of replicaset in wiki:
https://github.com/ichikaway/cakephp-mongodb/wiki/How-to-connect-to-replicaset-servers


Model files need to have mongoSchema property - or make use of the schemaless behavior. 

Mongo uses a primary key named "\_id" (cannot be renamed). It can be any format you like but if you don't explicitly set it Mongo will use an automatic 24 character (uu)id.

Before you start, you may find it useful to see [a model sample.](http://github.com/ichikaway/mongoDB-Datasource/blob/master/samples/models/post.php)
There are also some sample [controller actions: find,save,delete,deleteAll,updateAll](http://github.com/ichikaway/mongoDB-Datasource/blob/master/samples/controllers/posts_controller.php) note that your controller code needs no specific code to use this datasource.

## Author
Yasushi Ichikawa ([ichikaway](http://twitter.com/ichikaway))

Andy Dawson ([AD7six](http://twitter.com/AD7six))


## Contributors
[Predominant](http://github.com/predominant/) : Cleanup code, add documentation

[Jrbasso](http://github.com/jrbasso/) : Cleanup code

[tkyk](http://github.com/tkyk/) : Fix bug, Add some function.


## Reference
Reference code, Thank you!

[Nate Abele's lithium mongoDB datasource](http://li3.rad-dev.org/)

[Joél Perras' divan](http://github.com/jperras/divan/)

