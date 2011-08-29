<?php
/**
 * Test cases for the Cakephp mongoDB datasource.
 *
 * This datasource uses Pecl Mongo (http://php.net/mongo)
 * and is thus dependent on PHP 5.0 and greater.
 *
 * Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2010, Yasushi Ichikawa http://github.com/ichikaway/
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Import relevant classes for testing
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');


/**
 * Post Model for the test
 *
 * @package       app
 * @subpackage    app.model.post
 */
class Post extends AppModel {

	public $useDbConfig = 'mongo_test';

/**
 * mongoSchema property
 *
 * @public array
 * @access public
 */
	public $primaryKey='_id';

	public $validate = array(
 		'uniquefield1' => array(
 			'rule' => 'isUnique',
 			'required' => false
 		),
 		'uniquefield2' => array(
 			'rule' => 'manualUniqueValidation',
 			'required' => false
 		),
	);

	public $mongoSchema = array(
		'title' => array('type' => 'string'),
		'body' => array('type' => 'string'),
		'text' => array('type' => 'text'),
		'uniquefield1' => array('type' => 'text'),
		'uniquefield2' => array('type' => 'text'),
		'count' => array('type' => 'integer'),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime'),
	);

	function manualUniqueValidation($check) {
 		$c = $this->find('count', array(
 			'conditions' => array(
 				'uniquefield2' => $check['uniquefield2']
 			)
 		));
		if ($c === 0) {
			return true;
		}
 		return false;
 	}
}

/**
 * MongoArticle class
 *
 * @uses          AppModel
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources
 */
class MongoArticle extends AppModel {

	public $useDbConfig = 'mongo_test';
}

/**
 * MongoDB Source test class
 *
 * @package       app
 * @subpackage    app.model.datasources
 */
class MongodbSourceTest extends CakeTestCase {

/**
 * Database Instance
 *
 * @var resource
 * @access public
 */
	public $mongodb;

/**
 * Base Config
 *
 * @var array
 * @access public
 *
 */
	protected $_config = array(
		'datasource' => 'Mongodb.MongodbSource',
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'database' => 'test_mongo',
		'port' => 27017,
		'prefix' => '',
		'persistent' => true,
	);

/**
 * Sets up the environment for each test method
 *
 * @return void
 * @access public
 */
	public function setUp() {
		$connections = ConnectionManager::enumConnectionObjects();

		if (!empty($connections['test']['classname']) && $connections['test']['classname'] === 'mongodbSource') {
			$config = new DATABASE_CONFIG();
			$this->_config = $config->test;
		}

		ConnectionManager::create('mongo_test', $this->_config);
		$this->Mongo = new MongodbSource($this->_config);

		$this->Post = ClassRegistry::init('Post');
		$this->Post->setDataSource('mongo_test');

		$this->mongodb =& ConnectionManager::getDataSource($this->Post->useDbConfig);
		$this->mongodb->connect();

		$this->dropData();
	}

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
	public function tearDown() {
		$this->dropData();
		unset($this->Post);
		unset($this->Mongo);
		unset($this->mongodb);
		ClassRegistry::flush();
	}


/**
 * get Mongod server version
 *
 * @return numeric
 * @access public
 */
	public function getMongodVersion() {
		$mongo = $this->Post->getDataSource();
		return $mongo->execute('db.version()');
	}

/**
 * Insert data method for mongodb.
 *
 * @param array insert data
 * @return void
 * @access public
 */
	public function insertData($data) {
		try {
			$this->mongodb
				->connection
				->selectDB($this->_config['database'])
				->selectCollection($this->Post->table)
				->insert($data, true);
		} catch (MongoException $e) {
			trigger_error($e->getMessage());
		}
	}

/**
 * Drop database
 *
 * @return void
 * @access public
 */
	public function dropData() {
		try {
			$db = $this->mongodb
				->connection
				->selectDB($this->_config['database']);

			foreach($db->listCollections() as $collection) {
				$collection->drop();
			}
		} catch (MongoException $e) {
			trigger_error($e->getMessage());
		}
	}


/**
 * testCreateConnectionName
 *
 * @return void
 * @access public
 */
 public function testCreateConnectionName() {
	 $config = array(
			 'datasource' => 'mongodb',
			 'host' => 'localhost',
			 'login' => '',
			 'password' => '',
			 'database' => 'test_mongo',
			 'port' => 27017,
			 'prefix' => '',
			 'persistent' => false,
			 );
		$version = '1.2.2';
		$expect = 'mongodb://localhost:27017';
		$host = $this->mongodb->createConnectionName($config, $version);
		$this->assertIdentical($expect, $host);


		 $config = array(
			 'datasource' => 'mongodb',
			 'host' => 'localhost',
			 'login' => 'user',
			 'password' => 'pass',
			 'database' => 'test_mongo',
			 'port' => 27017,
			 'prefix' => '',
			 'persistent' => false,
			 );
		$version = '1.2.2';
		$expect = 'mongodb://user:pass@localhost:27017/test_mongo';
		$host = $this->mongodb->createConnectionName($config, $version);
		$this->assertIdentical($expect, $host);


		 $config = array(
			 'datasource' => 'mongodb',
			 'host' => 'localhost',
			 'login' => 'user',
			 'password' => 'pass',
			 'database' => 'test_mongo',
			 'port' => 27017,
			 'prefix' => '',
			 'persistent' => false,
			 );
		$version = '1.0.0';
		$expect = 'user:pass@localhost:27017/test_mongo';
		$host = $this->mongodb->createConnectionName($config, $version);
		$this->assertIdentical($expect, $host);
 }


/**
 * Tests connection
 *
 * @return void
 * @access public
 */
	public function testConnect() {
		$result = $this->Mongo->connect();
		$this->assertTrue($result);

		$this->assertTrue($this->Mongo->connected);
		$this->assertTrue($this->Mongo->isConnected());
	}

/**
 * Tests the disconnect method of the Mongodb DataSource
 *
 * @return void
 * @access public
 */
	public function testDisconnect() {
		$result = $this->Mongo->disconnect();
		$this->assertTrue($result);
		$this->assertNull($this->Mongo->connected);
	}

/**
 * Tests the listSources method of the Mongodb DataSource
 *
 * @return void
 * @access public
 */
	public function testListSources() {
		$this->assertTrue($this->mongodb->listSources());
	}

/**
 * Tests the getMongoDb method of the Mongodb DataSource
 *
 * @return void
 * @access public
 */
	public function testGetMongoDb() {
		$obj = $this->mongodb->getMongoDb();
		$this->assertTrue(is_object($obj));
		$objName = get_class($obj);
		$this->assertEqual('MongoDB', $objName);
	}

/**
 * Tests the Model::getMongoDb() call MongodbSource::getMongoDb
 *
 * @return void
 * @access public
 */
	public function testGetMongoDbFromModel() {

		$obj = $this->Post->getMongoDb();
		$this->assertTrue(is_object($obj));
		$objName = get_class($obj);
		$this->assertEqual('MongoDB', $objName);
	}

/**
 * Tests the getMongoCollection method of the Mongodb DataSource
 *
 * @return void
 * @access public
 */
	public function testGetMongoCollection() {
		$obj = $this->mongodb->getMongoCollection($this->Post);
		$this->assertTrue(is_object($obj));
		$objName = get_class($obj);
		$this->assertEqual('MongoCollection', $objName);
	}

/**
 * Tests the describe method of the Mongodb DataSource
 *
 * @return void
 * @access public
 */

	public function testDescribe() {
		$mockObj = $this->getMock('AppModel');

		$result = $this->mongodb->describe($mockObj);
		$expected = array(
			'_id' => array('type' => 'string', 'length' => 24, 'key' => 'primary'),
			'created' => array('type' => 'datetime', 'default' => null),
			'modified' => array('type' => 'datetime', 'default' => null),
		);
		$this->assertEqual($expected, $result);

		$result = $this->mongodb->describe($this->Post);
		$expect = array(
			'_id' => array('type' => 'string', 'length' => 24, 'key' => 'primary'),
			'title' => array('type' => 'string'),
			'body' => array('type' => 'string'),
			'text' => array('type' => 'text'),
			'uniquefield1' => array('type' => 'text'),
			'uniquefield2' => array('type' => 'text'),
			'count' => array('type' => 'integer'),
			'created' => array('type' => 'datetime'),
			'modified' => array('type' => 'datetime'),
		);
		ksort($result);
		ksort($expect);
		$this->assertEqual($expect, $result);
	}


/**
 * Tests find method.
 *
 * @return void
 * @access public
 */
	public function testFind() {
		$data = array(
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb'
		);
		$this->insertData($data);
		$result = $this->Post->find('all');
		$this->assertEqual(1, count($result));
		$resultData = $result[0]['Post'];
		$this->assertEqual(4, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);
	}

/**
 * Tests save method.
 *
 * @return void
 * @access public
 */
	public function testSave() {
		$data = array(
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb'
		);
		$saveData['Post'] = $data;

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue(!empty($saveResult) && is_array($saveResult));

		$result = $this->Post->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0]['Post'];
		$this->assertEqual(6, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$this->assertEqual($this->Post->id, $resultData['_id']);
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);

		$this->assertTrue(is_a($resultData['created'], 'MongoDate'));
		$this->assertTrue(is_a($resultData['modified'], 'MongoDate'));
	}

/**
 * Tests insertId after saving
 *
 * @return void
 * @access public
 */
	public function testCheckInsertIdAfterSaving() {
		$saveData['Post'] = array(
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb'
		);

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue(!empty($saveResult) && is_array($saveResult));


		$this->assertEqual($this->Post->id, $this->Post->getInsertId());
		$this->assertTrue(is_string($this->Post->id));
		$this->assertTrue(is_string($this->Post->getInsertId()));

		//set Numeric _id
		$saveData['Post'] = array(
			'_id' => 123456789,
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb'
		);

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue(!empty($saveResult) && is_array($saveResult));

		$this->assertEqual($saveData['Post']['_id'] ,$this->Post->id);
		$this->assertEqual($this->Post->id, $this->Post->getInsertId());
		$this->assertTrue(is_numeric($this->Post->id));
		$this->assertTrue(is_numeric($this->Post->getInsertId()));

		$readArray1 = $this->Post->read();
		$readArray2 = $this->Post->read(null, $saveData['Post']['_id']);
		$this->assertEqual($readArray1, $readArray2);
		$this->assertEqual($saveData['Post']['_id'], $readArray2['Post']['_id']);

	}



/**
 * Tests saveAll method.
 *
 * @return void
 * @access public
 */
	public function testSaveAll() {
		$saveData[0]['Post'] = array(
			'title' => 'test1',
			'body' => 'aaaa1',
			'text' => 'bbbb1'
		);

		$saveData[1]['Post'] = array(
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2'
		);

		$this->Post->create();
		$saveResult = $this->Post->saveAll($saveData);
		$result = $this->Post->find('all');

		$this->assertEqual(2, count($result));

		$resultData = $result[0]['Post'];
		$this->assertEqual(6, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$data = $saveData[0]['Post'];
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);

		$this->assertTrue(is_a($resultData['created'], 'MongoDate'));
		$this->assertTrue(is_a($resultData['modified'], 'MongoDate'));

		$resultData = $result[1]['Post'];
		$this->assertEqual(6, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$data = $saveData[1]['Post'];
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);

		$this->assertTrue(is_a($resultData['created'], 'MongoDate'));
		$this->assertTrue(is_a($resultData['modified'], 'MongoDate'));
	}

/**
 * Tests update method.
 *
 * @return void
 * @access public
 */
	public function testUpdate() {
		$count0 = $this->Post->find('count');

		$data = array(
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb',
			'count' => 0
		);
		$saveData['Post'] = $data;

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$postId = $this->Post->id;

		$count1 = $this->Post->find('count');
		$this->assertIdentical($count1 - $count0, 1, 'Save failed to create one row');

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertTrue(!empty($postId) && is_string($postId));
		$findresult = $this->Post->find('all');
		$this->assertEqual(0, $findresult[0]['Post']['count']);

		$updatedata = array(
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2'
		);
		$saveData['Post'] = $updatedata;

		$saveResult = $this->Post->save($saveData);

		$count2 = $this->Post->find('count');
		$this->assertIdentical($count2 - $count1, 0, 'Save test 2 created another row, it did not update the existing row');

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($this->Post->id, $postId);

		$this->Post->create();
		$updatedata = array(
			'_id' => $postId,
			'title' => 'test3',
			'body' => 'aaaa3',
			'text' => 'bbbb3'
		);
		$saveData['Post'] = $updatedata;
		$saveResult = $this->Post->save($saveData);

		$count3 = $this->Post->find('count');
		$this->assertIdentical($count3 - $count2, 0, 'Saving with the id in the data created another row');

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($this->Post->id, $postId);

		$this->Post->create();
		$this->Post->id = $postId;
		$updatedata = array(
			'title' => 'test4',
			'body' => 'aaaa4',
			'text' => 'bbbb4'
		);
		$saveData['Post'] = $updatedata;
		$saveResult = $this->Post->save($saveData);

		$count4 = $this->Post->find('count');
		$this->assertIdentical($count4 - $count3, 0, 'Saving with $Model->id set and no id in the data created another row');

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($this->Post->id, $postId);

		$result = $this->Post->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0]['Post'];
		$this->assertEqual(7, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$this->assertEqual($this->Post->id, $resultData['_id']);
		$this->assertEqual($updatedata['title'], $resultData['title']);
		$this->assertEqual($updatedata['body'], $resultData['body']);
		$this->assertEqual($updatedata['text'], $resultData['text']);
		$this->assertEqual(0, $resultData['count']);


		// using $inc operator
		$this->Post->mongoNoSetOperator = '$inc';
		$this->Post->create();
		$updatedataIncrement = array(
			'_id' => $postId,
			'count' => 1,
		);
		$saveData['Post'] = $updatedataIncrement;
		$saveResult = $this->Post->save($saveData);

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($this->Post->id, $postId);

		$result = $this->Post->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0]['Post'];
		$this->assertEqual(7, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$this->assertEqual($this->Post->id, $resultData['_id']);
		$this->assertEqual($updatedata['title'], $resultData['title']); //not update
		$this->assertEqual($updatedata['body'], $resultData['body']); //not update
		$this->assertEqual($updatedata['text'], $resultData['text']); //not update
		$this->assertEqual(1, $resultData['count']); //increment
		unset($this->Post->mongoNoSetOperator);
	}


/**
 * Tests updateAll method.
 *
 * @return void
 * @access public
 */
	public function testUpdateAll() {
		$saveData[0]['Post'] = array(
			'title' => 'test',
			'name' => 'ichi',
			'body' => 'aaaa1',
			'text' => 'bbbb1'
		);

		$saveData[1]['Post'] = array(
			'title' => 'test',
			'name' => 'ichi',
			'body' => 'aaaa2',
			'text' => 'bbbb2'
		);

		$this->Post->create();
		$this->Post->saveAll($saveData);

		$updateData = array('name' => 'ichikawa');
		$conditions = array('title' => 'test');

		$resultUpdateAll = $this->Post->updateAll($updateData, $conditions);
		$this->assertTrue($resultUpdateAll);

		$result = $this->Post->find('all');
		$this->assertEqual(2, count($result));
		$resultData = $result[0]['Post'];
		$this->assertEqual(7, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$data = $saveData[0]['Post'];
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual('ichikawa', $resultData['name']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);
		$this->assertTrue(is_a($resultData['created'], 'MongoDate'));
		$this->assertTrue(is_a($resultData['modified'], 'MongoDate'));


		$resultData = $result[1]['Post'];
		$this->assertEqual(7, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$data = $saveData[1]['Post'];
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual('ichikawa', $resultData['name']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);
		$this->assertTrue(is_a($resultData['created'], 'MongoDate'));
		$this->assertTrue(is_a($resultData['modified'], 'MongoDate'));
	}

/**
 * Tests updateAll method.
 *
 * @return void
 * @access public
 */
	public function testSetMongoUpdateOperator() {

		$ds = $this->Post->getDataSource();

		//normal
		$data = array('title' => 'test1', 'name' => 'ichikawa');
		$expect = array('$set' => array('title' => 'test1', 'name' => 'ichikawa'));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);

		//using $inc
		$data = array('title' => 'test1', 'name' => 'ichikawa', '$inc' => array('count' => 1));
		$expect = array('title' => 'test1', 'name' => 'ichikawa', '$inc' => array('count' => 1));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);

		//using $inc and modified
		$data = array('modified' => '2011/8/1', '$inc' => array('count' => 1));
		$expect = array('$set' => array('modified' => '2011/8/1'), '$inc' => array('count' => 1));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);

		//using $inc and updated
		$data = array('updated' => '2011/8/1', '$inc' => array('count' => 1));
		$expect = array('$set' => array('updated' => '2011/8/1'), '$inc' => array('count' => 1));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);

		//using $inc, $push and modified
		$data = array('$push' => array('tag' => 'tag1'), 'modified' => '2011/8/1', '$inc' => array('count' => 1));
		$expect = array('$push' => array('tag' => 'tag1'),'$set' => array('modified' => '2011/8/1'), '$inc' => array('count' => 1));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);

		//mongoNoSetOperator is true,
		// using $inc, $push and modified
		$this->Post->mongoNoSetOperator = true;
		$data = array('$push' => array('tag' => 'tag1'), 'modified' => '2011/8/1', '$inc' => array('count' => 1));
		$expect = array('$push' => array('tag' => 'tag1'),'modified' => '2011/8/1', '$inc' => array('count' => 1));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);


		//mongoNoSetOperator is $inc,
		$this->Post->mongoNoSetOperator = '$inc';
		$data = array('count' => 1);
		$expect = array('$inc' => array('count' => 1));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);


		//mongoNoSetOperator is $inc,
		// with modified field
		$this->Post->mongoNoSetOperator = '$inc';
		$data = array('count' => 1, 'modified' => '2011/8/1');
		$expect = array('$inc' => array('count' => 1),'$set' => array('modified' => '2011/8/1'));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);

		//mongoNoSetOperator is $inc,
		// with updated field
		$this->Post->mongoNoSetOperator = '$inc';
		$data = array('count' => 1, 'updated' => '2011/8/1');
		$expect = array('$inc' => array('count' => 1),'$set' => array('updated' => '2011/8/1'));
		$result = $ds->setMongoUpdateOperator($this->Post, $data);
		$this->assertEqual($expect, $result);


	}


/**
 * Tests update method without $set operator.
 *
 * @return void
 * @access public
 */
	public function testUpdateWithoutMongoSchemaProperty() {
		$MongoArticle = ClassRegistry::init('MongoArticle');

		$data = array(
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb',
			'count' => 0,
			'created' => new mongoDate(),
			'modified' => new mongoDate(),
		);
		$saveData['MongoArticle'] = $data;

		$MongoArticle->create();
		$saveResult = $MongoArticle->save($saveData);
		$postId = $MongoArticle->id;

		//using $set operator
		$MongoArticle->create();
		$updatedata = array(
			'_id' => $postId,
			'title' => 'test3',
			'body' => 'aaaa3',
		);
		$saveData['MongoArticle'] = $updatedata;
		$saveResult = $MongoArticle->save($saveData); // using $set operator

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($MongoArticle->id, $postId);

		$result = null;
		$result = $MongoArticle->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0]['MongoArticle'];
		$this->assertEqual($MongoArticle->id, $resultData['_id']);
		$this->assertEqual($updatedata['title'], $resultData['title']); //update
		$this->assertEqual($updatedata['body'], $resultData['body']); //update
		$this->assertEqual($data['text'], $resultData['text']); //not update
		$this->assertEqual($data['count'], $resultData['count']); //not update



		//using $inc operator insted of $set operator
		$MongoArticle->create();
		$updatedataInc = array(
			'_id' => $postId,
			'$inc' => array('count' => 1),
		);
		$saveData['MongoArticle'] = $updatedataInc;
		$saveResult = $MongoArticle->save($saveData); // using $set operator

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($MongoArticle->id, $postId);
		$result = null;
		$result = $MongoArticle->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0]['MongoArticle'];
		$this->assertEqual($MongoArticle->id, $resultData['_id']);
		$this->assertEqual($updatedata['title'], $resultData['title']); //not update
		$this->assertEqual($updatedata['body'], $resultData['body']); //not update
		$this->assertEqual($data['text'], $resultData['text']); //not update
		$this->assertEqual(1, $resultData['count']); //increment

		//using $inc and $push
		$MongoArticle->create();
		$updatedataInc = array(
				'_id' => $postId,
				'$push' => array(
					'comments' => array(
						'_id' => new MongoId(),
						'created' => new MongoDate(),
						'vote_count' => 0,
						'user' => 'user1',
						'body' => 'comment',
						)
					),
				'$inc' => array('count' => 1),
				);
		$saveData['MongoArticle'] = $updatedataInc;
		$saveResult = $MongoArticle->save($saveData); // using $set operator

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($MongoArticle->id, $postId);
		$result = null;
		$result = $MongoArticle->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0]['MongoArticle'];
		$this->assertEqual($MongoArticle->id, $resultData['_id']);
		$this->assertEqual($updatedata['title'], $resultData['title']); //not update
		$this->assertEqual($updatedata['body'], $resultData['body']); //not update
		$this->assertEqual($data['text'], $resultData['text']); //not update
		$this->assertEqual(2, $resultData['count']); //increment
		$this->assertEqual('user1', $resultData['comments'][0]['user']); //push
		$this->assertEqual('comment', $resultData['comments'][0]['body']); //push
		$this->assertEqual(1, count($resultData['comments'])); //push
		$this->assertTrue(!empty($resultData['created']));
		$this->assertTrue(!empty($resultData['modified']));


		//no $set operator
		$MongoArticle->mongoNoSetOperator = true;

		$MongoArticle->create();
		$updatedata = array(
			'_id' => $postId,
			'title' => 'test4',
			'body' => 'aaaa4',
			'count' => '1',
		);
		$saveData['MongoArticle'] = $updatedata;
		$saveResult = $MongoArticle->save($saveData);

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($MongoArticle->id, $postId);

		$result = null;
		$result = $MongoArticle->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0]['MongoArticle'];
		$this->assertEqual($MongoArticle->id, $resultData['_id']);
		$this->assertEqual($updatedata['title'], $resultData['title']); //update
		$this->assertEqual($updatedata['body'], $resultData['body']); //update
		$this->assertTrue(empty($resultData['text']));
		$this->assertEqual(1, $resultData['count']);

		$MongoArticle->mongoNoSetOperator = null;


		//use $push
		$MongoArticle->create();
		$updatedata = array(
			'_id' => $postId,
			'push_column' => array('push1'),
		);
		$saveData['MongoArticle'] = $updatedata;
		$saveResult = $MongoArticle->save($saveData); //use $set

		$result = $MongoArticle->find('all');
		$resultData = $result[0]['MongoArticle'];
		$this->assertEqual('test4', $resultData['title']); // no update
		$this->assertEqual(array('push1'), $resultData['push_column']);


		$MongoArticle->mongoNoSetOperator = '$push';
		$MongoArticle->create();
		$updatedata = array(
			'_id' => $postId,
			'push_column' => 'push2',
		);
		$saveData['MongoArticle'] = $updatedata;
		$saveResult = $MongoArticle->save($saveData); //use $push

		$this->assertTrue(!empty($saveResult) && is_array($saveResult));
		$this->assertIdentical($MongoArticle->id, $postId);

		$result = null;
		$result = $MongoArticle->find('all');


		$this->assertEqual(1, count($result));
		$resultData = $result[0]['MongoArticle'];
		$this->assertEqual($MongoArticle->id, $resultData['_id']);
		$this->assertEqual('test4', $resultData['title']); // no update
		$this->assertEqual(array('push1','push2'), $resultData['push_column']); //update

		$MongoArticle->mongoNoSetOperator = null;


		unset($MongoArticle);

	}


/**
 * Tests groupBy
 *
 * @return void
 * @access public
 */
	public function testGroupBy() {
		for($i = 0 ; $i < 30 ; $i++) {
			$saveData[$i]['Post'] = array(
					'title' => 'test'.$i,
					'body' => 'aaaa'.$i,
					'text' => 'bbbb'.$i,
					'count' => $i,
					);
		}

		$saveData[30]['Post'] = array(
			'title' => 'test1',
			'body' => 'aaaa1',
			'text' => 'bbbb1',
			'count' => 1,
		);
		$saveData[31]['Post'] = array(
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2',
			'count' => 2,
		);

		$this->Post->create();
		$saveResult = $this->Post->saveAll($saveData);

		$cond_count = 5;
		$query = array(
				'key' => array('title' => true ),
				'initial' => array('csum' => 0),
				'reduce' => 'function(obj, prev){prev.csum += 1;}',
				'options' => array(
					'condition' => array('count' => array('$lt' => $cond_count)),
					),
				);

		$mongo = $this->Post->getDataSource();
		$result =  $mongo->group($this->Post, $query);

		$this->assertTrue($result['ok'] == 1 && count($result['retval']) > 0);
		$this->assertEqual($cond_count, count($result['retval']));
		$this->assertEqual('test0', $result['retval'][0]['title']);
		$this->assertEqual('test1', $result['retval'][1]['title']);
		$this->assertEqual('test2', $result['retval'][2]['title']);
		$this->assertEqual(1, $result['retval'][0]['csum']);
		$this->assertEqual(2, $result['retval'][1]['csum']);
		$this->assertEqual(2, $result['retval'][2]['csum']);

}



/**
 * Tests query
 *  Distinct, Group
 *
 * @return void
 * @access public
 */
	public function testQuery() {
		for($i = 0 ; $i < 30 ; $i++) {
			$saveData[$i]['Post'] = array(
					'title' => 'test'.$i,
					'body' => 'aaaa'.$i,
					'text' => 'bbbb'.$i,
					'count' => $i,
					);
		}

		$saveData[30]['Post'] = array(
			'title' => 'test1',
			'body' => 'aaaa1',
			'text' => 'bbbb1',
			'count' => 1,
		);
		$saveData[31]['Post'] = array(
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2',
			'count' => 2,
		);

		$saveData[32]['Post'] = array(
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2',
			'count' => 32,
		);

		$this->Post->create();
		$saveResult = $this->Post->saveAll($saveData);


		//using query() Distinct
		$params = array(
				'distinct' => 'posts',
				'key' => 'count',
				);
		$result = $this->Post->query( $params );
		$this->assertEqual(1, $result['values'][1]);
		$this->assertEqual(2, $result['values'][2]);
		$this->assertEqual(32, $result['values'][30]);


		//using query() group
		$cond_count = 5;
		$reduce = "function(obj,prev){prev.csum++;}";
		$params = array(
				'group'=>array(
					'ns'=>'posts',
					'cond'=>array('count' => array('$lt' => $cond_count)),
					'key'=>array('title'=>true),
					'initial'=>array('csum'=>0),
					'$reduce'=>$reduce
					)
				);

		$result = $this->Post->query( $params );

		$this->assertTrue($result['ok'] == 1 && count($result['retval']) > 0);
		$this->assertEqual($cond_count, count($result['retval']));
		$this->assertEqual('test0', $result['retval'][0]['title']);
		$this->assertEqual('test1', $result['retval'][1]['title']);
		$this->assertEqual('test2', $result['retval'][2]['title']);
		$this->assertEqual(1, $result['retval'][0]['csum']);
		$this->assertEqual(2, $result['retval'][1]['csum']);
		$this->assertEqual(2, $result['retval'][2]['csum']);

}

/**
 * Tests MapReduce
 *
 * @return void
 * @access public
 */
public function testMapReduce() {
	for($i = 0 ; $i < 30 ; $i++) {
		$saveData[$i]['Post'] = array(
				'title' => 'test'.$i,
				'body' => 'aaaa'.$i,
				'text' => 'bbbb'.$i,
				'count' => $i,
				);
	}

	$saveData[30]['Post'] = array(
			'title' => 'test1',
			'body' => 'aaaa1',
			'text' => 'bbbb1',
			'count' => 1,
			);
	$saveData[31]['Post'] = array(
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2',
			'count' => 2,
			);

	$saveData[32]['Post'] = array(
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2',
			'count' => 32,
			);

	$this->Post->create();
	$saveResult = $this->Post->saveAll($saveData);

	$map = new MongoCode("function() { emit(this.title,1); }");
	$reduce = new MongoCode("function(k, vals) { ".
			"var sum = 0;".
			"for (var i in vals) {".
			"sum += vals[i];".
			"}".
			"return sum; }"
			);

	$params = array(
			"mapreduce" => "posts",
			"map" => $map,
			"reduce" => $reduce,
			"query" => array(
				"count" => array('$gt' => -2),
				),
			'out' => 'test_mapreduce_posts',
			);

	$mongo = $this->Post->getDataSource();
	$results = $mongo->mapReduce($params);

	$posts = array();
	foreach ($results as $post) {
		$posts[$post['_id']] = $post['value'];
	}

	$this->assertEqual(30, count($posts));
	$this->assertEqual(1, $posts['test0']);
	$this->assertEqual(2, $posts['test1']);
	$this->assertEqual(3, $posts['test2']);
	$this->assertEqual(1, $posts['test3']);


	//set timeout
	$results = $mongo->mapReduce($params, 100); //set timeout 100msec
	$posts = array();
	foreach ($results as $post) {
		$posts[$post['_id']] = $post['value'];
	}

	$this->assertEqual(30, count($posts));
	$this->assertEqual(1, $posts['test0']);
	$this->assertEqual(2, $posts['test1']);
	$this->assertEqual(3, $posts['test2']);
	$this->assertEqual(1, $posts['test3']);


	//get results as inline data
	$version = $this->getMongodVersion();
	if( $version >= '1.7.4') {
		$params = array(
				"mapreduce" => "posts",
				"map" => $map,
				"reduce" => $reduce,
				"query" => array(
					"count" => array('$gt' => -2),
					),
				'out' => array('inline' => 1),
				);

		$results = $mongo->mapReduce($params);

		$posts = array();
		foreach ($results as $post) {
			$posts[$post['_id']] = $post['value'];
		}

		$this->assertEqual(30, count($posts));
		$this->assertEqual(1, $posts['test0']);
		$this->assertEqual(2, $posts['test1']);
		$this->assertEqual(3, $posts['test2']);
		$this->assertEqual(1, $posts['test3']);
	}


}



/**
 * testSort method
 *
 * @return void
 * @access public
 */
	public function testSort() {
		$data = array(
			'title' => 'AAA',
			'body' => 'aaaa',
			'text' => 'aaaa'
		);
		$saveData['Post'] = $data;
		$this->Post->create();
		$this->Post->save($saveData);

		$data = array(
			'title' => 'CCC',
			'body' => 'cccc',
			'text' => 'cccc'
		);
		$saveData['Post'] = $data;
		$this->Post->create();
		$this->Post->save($saveData);

		$this->Post->create();
		$data = array(
			'title' => 'BBB',
			'body' => 'bbbb',
			'text' => 'bbbb'
		);
		$saveData['Post'] = $data;
		$this->Post->create();
		$this->Post->save($saveData);

		$expected = array('AAA', 'BBB', 'CCC');
		$result = $this->Post->find('all', array(
			'fields' => array('_id', 'title'),
			'order' => array('title' => 1)
		));
		$result = Set::extract($result, '/Post/title');

		$this->assertEqual($expected, $result);
		$result = $this->Post->find('all', array(
			'fields' => array('_id', 'title'),
			'order' => array('title' => 'ASC')
		));
		$result = Set::extract($result, '/Post/title');

		$expected = array_reverse($expected);
		$result = $this->Post->find('all', array(
			'fields' => array('_id', 'title'),
			'order' => array('title' => '-1')
		));
		$result = Set::extract($result, '/Post/title');
		$this->assertEqual($expected, $result);

		$result = $this->Post->find('all', array(
			'fields' => array('_id', 'title'),
			'order' => array('title' => 'DESC')
		));
		$result = Set::extract($result, '/Post/title');
		$this->assertEqual($expected, $result);
	}

/**
 * testSchemaless method
 *
 * Test you can save to a model without specifying mongodb.
 *
 * @return void
 * @access public
 */
	public function testSchemaless() {
		$toSave = array(
			'title' => 'A test article',
			'body' => str_repeat('Lorum ipsum ', 100),
			'tags' => array(
				'one',
				'two',
				'three'
			),
			'modified' => null,
			'created' => null
		);

		$MongoArticle = ClassRegistry::init('MongoArticle');
		$MongoArticle->create();
		$saveResult = $MongoArticle->save($toSave);
		$this->assertTrue(!empty($saveResult) && is_array($saveResult));

		$expected = array_intersect_key($toSave, array_flip(array('title', 'body', 'tags')));
		$result = $MongoArticle->read(array('title', 'body', 'tags'));
		unset ($result['MongoArticle']['_id']); // prevent auto added field from screwing things up
		$this->assertEqual($expected, $result['MongoArticle']);

		$toSave = array(
			'title' => 'Another test article',
			'body' => str_repeat('Lorum pipsum ', 100),
			'tags' => array(
				'four',
				'five',
				'six'
			),
			'starts' => date('Y-M-d H:i:s'),
			'modified' => null,
			'created' => null
		);
		$MongoArticle->create();
    $saveResult = $MongoArticle->save($toSave);
    $this->assertTrue(!empty($saveResult) && is_array($saveResult));

		$starts = $MongoArticle->field('starts');
		$this->assertEqual($toSave['starts'], $starts);
	}

/**
 * testSpecificId method
 *
 * Test you can save specifying your own _id values - and update by _id
 *
 * @return void
 * @access public
 */
	public function testSpecificId() {
		$data = array(
			'_id' => 123,
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb'
		);
		$saveData['Post'] = $data;

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue(!empty($saveResult) && is_array($saveResult));


		$found = $this->Post->find('first', array(
			'fields' => array('_id', 'title', 'body', 'text'),
			'conditions' => array('_id' => 123)
		));

		$this->assertEqual($found, $saveData);

		$data = array(
			'_id' => 123,
			'title' => 'test2',
			'body' => 'aaaa2',
			'text' => 'bbbb2'
		);
		$saveData['Post'] = $data;

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue(!empty($saveResult) && is_array($saveResult));

		$found = $this->Post->find('first', array(
			'fields' => array('_id', 'title', 'body', 'text'),
			'conditions' => array('_id' => 123)
		));
		$this->assertEqual($found, $saveData);
	}

/**
 * testOr method
 *
 * @return void
 * @access public
 */
	public function testOr() {
		$mongoVersion = $this->mongodb->execute('db.version()');
		$shouldSkip = version_compare($mongoVersion, '1.5.3', '<');
		if ($this->skipIf($shouldSkip, '$or tests require at least version mongo version 1.5.3, currently using ' . $mongoVersion . ' %s')) {
			return;
		}

		$MongoArticle = ClassRegistry::init('MongoArticle');
		$MongoArticle->create();

		for ($i = 1; $i <= 20; $i++) {
			$data = array(
				'title' => "Article $i",
				'subtitle' => "Sub Article $i",
			);
			$saveData['MongoArticle'] = $data;
			$MongoArticle->create();
			$MongoArticle->save($saveData);
		}
		$expected = $MongoArticle->find('all', array(
			'conditions' => array(
				'title' => array('$in' => array('Article 1', 'Article 10'))
			),
			'order' => array('number' => 'ASC')
		));

		$this->assertEqual(count($expected), 2);

		$result = $MongoArticle->find('all', array(
			'conditions' => array(
				'$or' => array(
					array('title' => 'Article 1'),
					array('title' => 'Article 10'),
				)
			),
			'order' => array('number' => 'ASC')
		));
		$this->assertEqual($result, $expected);
	}

/**
 * testDeleteAll method
 *
 * @return void
 * @access public
 */
	function testDeleteAll($cascade = true) {
		$MongoArticle = ClassRegistry::init('MongoArticle');
		$MongoArticle->create(array('title' => 'Article 1', 'cat' => 1));
		$MongoArticle->save();

		$MongoArticle->create(array('title' => 'Article 2', 'cat' => 1));
		$MongoArticle->save();

		$MongoArticle->create(array('title' => 'Article 3', 'cat' => 2));
		$MongoArticle->save();

		$MongoArticle->create(array('title' => 'Article 4', 'cat' => 2));
		$MongoArticle->save();

		$count = $MongoArticle->find('count');
		$this->assertEqual($count, 4);

		$MongoArticle->deleteAll(array('cat' => 2), $cascade);

		$count = $MongoArticle->find('count');
		$this->assertEqual($count, 2);

		$MongoArticle->deleteAll(true, $cascade);

		$count = $MongoArticle->find('count');
		$this->assertEqual($count, 0);
	}

/**
 * testDeleteAllNoCascade method
 *
 * @return void
 * @access public
 */
	function testDeleteAllNoCascade() {
		$this->testDeleteAll(false);
	}

/**
 * testRegexSearch method
 *
 * @return void
 * @access public
 */
	public function testRegexSearch() {
		$MongoArticle = ClassRegistry::init('MongoArticle');
		$MongoArticle->create(array('title' => 'Article 1', 'cat' => 1));
		$MongoArticle->save();
		$MongoArticle->create(array('title' => 'Article 2', 'cat' => 1));
		$MongoArticle->save();
		$MongoArticle->create(array('title' => 'Article 3', 'cat' => 2));
		$MongoArticle->save();

		$count=$MongoArticle->find('count',array(
			'conditions'=>array(
				'title'=>'Article 2'
			)
		));
		$this->assertEqual($count, 1);

		$count = $MongoArticle->find('count',array(
			'conditions'=>array(
				'title'=> new MongoRegex('/^Article/')
			)
		));
		$this->assertEqual($count, 3);
	}

/**
 * testEmptyReturn method
 * inserts article into table. searches for a different non existing article. should return an empty array in the same that that it does from other datasources
 * @return void
 * @access public
 */
	public function testEmptyReturn() {
		$MongoArticle = ClassRegistry::init('MongoArticle');
		$MongoArticle->create(array('title' => 'Article 1', 'cat' => 1));
		$MongoArticle->save();
		$articles=$MongoArticle->find('all',array(
			'conditions'=>array(
				'title'=>'Article 2'
			)
		));
		$this->assertTrue(is_array($articles));
		$articles=$MongoArticle->find('first',array(
			'conditions'=>array(
				'title'=>'Article 2'
			)
		));
		$this->assertFalse(is_array($articles));
	}

/**
 * Tests isUnique validation.
 *
 * @return void
 * @access public
 */
	public function testSaveUniques() {
		$data = array(
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb',
			'uniquefield1'=>'uniquenameforthistest'
		);
		$saveData['Post'] = $data;

		$this->Post->Behaviors->attach('Mongodb.SqlCompatible');
		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
    $this->assertTrue(!empty($saveResult) && is_array($saveResult));

		$data = array(
			'title' => 'test',
			'body' => 'asdf',
			'text' => 'bbbb',
			'uniquefield1'=>'uniquenameforthistest'
		);
		$saveData['Post'] = $data;

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertFalse($saveResult);
	}

/**
 * Tests isUnique validation with custom validation.
 *
 * @return void
 * @access public
 */
	public function testSaveUniquesCustom() {
		$data = array(
			'title' => 'test',
			'body' => 'aaaa',
			'text' => 'bbbb',
			'uniquefield2' => 'someunqiuename'
		);
		$saveData['Post'] = $data;
		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue(!empty($saveResult) && is_array($saveResult));

		$data = array(
			'title' => 'test',
			'body' => 'asdf',
			'text' => 'bbbb',
			'uniquefield2' => 'someunqiuename'
		);
		$saveData['Post'] = $data;
		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertFalse($saveResult);
	}

	public function testReturn() {
		$MongoArticle = ClassRegistry::init('MongoArticle');
		$MongoArticle->create(array('title' => 'Article 1', 'cat' => 1));
		$MongoArticle->save();
		$MongoArticle->create(array('title' => 'Article 2', 'cat' => 1));
		$MongoArticle->save();

		$return = $MongoArticle->find('all', array(
			'conditions' => array(
				'title' => 'Article 2'
			)
		));
		$this->assertTrue(is_array($return));

		$return = $MongoArticle->find('first', array(
			'conditions' => array(
				'title' => 'Article 2'
			)
		));
		$this->assertTrue(is_array($return));

		$return = $MongoArticle->find('first', array(
			'conditions' => array(
				'title' => 'Article 2'
			)
		));
		$this->assertTrue(is_array($return));

		$return = $MongoArticle->find('count', array(
			'conditions' => array(
				'title' => 'Article 2'
			)
		));
		$this->assertTrue(is_int($return));

		$return = $MongoArticle->find('neighbors', array(
			'conditions' => array(
				'title' => 'Article 2'
			)
		));
		$this->assertTrue(is_array($return));

		$return = $MongoArticle->find('list', array(
			'conditions' => array(
				'title' => 'Article 2'
			)
		));
		$this->assertTrue(is_array($return));

		$return = $MongoArticle->find('all', array(
			'conditions' => array(
				'title' => 'Doesn\'t exist'
			)
		));
		$this->assertTrue(is_array($return));

		$return = $MongoArticle->find('first', array(
			'conditions' => array(
				'title' => 'Doesn\'t exist'
			)
		));
		$this->assertFalse($return);

		$return = $MongoArticle->find('count', array(
			'conditions' => array(
				'title' => 'Doesn\'t exist'
			)
		));
		$this->assertTrue(is_int($return));

		$return = $MongoArticle->find('neighbors', array(
			'conditions' => array(
				'title' => 'Doesn\'t exist'
			)
		));
		$this->assertTrue(is_array($return));

		$return = $MongoArticle->find('list', array(
			'conditions' => array(
				'title' => 'Doesn\'t exist'
			)
		));
		$this->assertTrue(is_array($return));

	}
}