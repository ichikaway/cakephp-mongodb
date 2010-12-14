# mongoDB datasource for CakePHP

## Requirements
PHP5, 
pecl mongo (http://php.net/mongo)

## Installation

this repository should be installed in the same way as any other plugin.

To install the driver for use in a single application:

	cd my/app/plugins
	git clone git://github.com/ichikaway/cakephp-mongodb.git mongodb

To install the driver for use in any/multiple application(s)

	# where ROOT is the name of the directory parent to the base index.php of CakePHP.
	cd ROOT/plugins
	git clone git://github.com/ichikaway/cakephp-mongodb.git mongodb
	
## Sample Code

To use this DB driver, install (obviously) and define a db source such as follows:

	<?php
	// app/config/database.php
	class DATABASE_CONFIG {

		public $mongo = array(
			'driver' => 'mongodb.mongodbSource',
			'database' => 'driver',
			'host' => 'localhost',
			'port' => 27017,
			/* optional auth fields
			'login' => 'mongo',	
			'password' => 'awesomeness',	
			*/
		);  

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

