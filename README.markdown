# mongoDB datasource for CakePHP

## Requirements
PHP5, 
pecl mongo (http://php.net/mongo)

## Sample Code
Model files need to have mongoSchema property. A primaryKey is "\_id", it's automatically set in MongoDatasource.
Before you start, please check [a model sample.](http://github.com/ichikaway/mongoDB-Datasource/blob/master/samples/models/post.php)

There are some sample [controller actions: find,save,delete,deleteAll,updateAll](http://github.com/ichikaway/mongoDB-Datasource/blob/master/samples/controllers/posts_controller.php)


## Author
Yasushi Ichikawa ([ichikaway](http://twitter.com/ichikaway) )

[AD7six](http://twitter.com/AD7six)


## Contributors
[Predominant](http://github.com/predominant/) : Cleanup code, add documentation

[Jrbasso](http://github.com/jrbasso/) : Cleanup code

[tkyk](http://github.com/tkyk/) : Fix bug, Add some function.


## Reference
Reference code, Thank you!

[Nate Abele's lithium mongoDB datasource](http://li3.rad-dev.org/)

[Joél Perras' divan](http://github.com/jperras/divan/)

