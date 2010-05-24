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
* @copyright Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
* @package mongodb
* @subpackage mongodb.models.datasources
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
*/


/**
* Import relevant classes for testing
*/
App::import('Model', 'MongodbSource');

/**
* Generate Mock Model
*/
Mock::generate('AppModel', 'MockPost');

/**
 * Post Model for the test
 *
 * @package app
 * @subpackage app.model.post
 */
class Post extends AppModel {
	var $useDbConfig = 'mongo_test';

	var $mongoSchema = array(
			'title' => array('type'=>'string'),
			'body'=>array('type'=>'string'),
			'text'=>array('type'=>'text'),
			'created'=>array('type'=>'datetime'),
			'modified'=>array('type'=>'datetime'),
			);



}

class MongoArticle extends AppModel {
	var $useDbConfig = 'mongo_test';
}

/**
 * MongoDB Source test class
 *
 * @package app
 * @subpackage app.model.datasources
 */
class MongodbSourceTest extends CakeTestCase {

/**
 * Database Instance
 *
 * @var resource
 * @access public
 */
	var $mongodb;

/**
 * Base Config
 *
 * @var array
 * @access public
 *
 */
	var $_config = array(
			'datasource' => 'mongodb',
			'host' => 'localhost',
			'login' => '',
			'password' => '',
			'database' => 'test_mongo',
			'port' => 27017,
			'prefix' => '',
			'persistent' => false,
			);

/**
 * Sets up the environment for each test method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Mongo = new MongodbSource($this->_config);
		ConnectionManager::create('mongo_test', $this->_config);

		$this->Post = ClassRegistry::init('Post');
		$this->Post->setDataSource('mongo_test');

		$this->mongodb =& ConnectionManager::getDataSource($this->Post->useDbConfig);
	}

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
	function endTest() {
		$this->dropData();
		unset($this->Post);
	}

/**
 * Insert data method for mongodb.
 *
 * @param array insert data
 * @return void
 * @access public
 */
	function insertData($data) {
		$this->mongodb
			->connection
			->selectDB($this->_config['database'])
            ->selectCollection($this->Post->table)
            ->insert($data, true);
	}

/**
 * Drop database
 *
 * @return void
 * @access public
 */
	function dropData() {
		$this->mongodb
			->connection
			->dropDB($this->_config['database']);
	}

/**
 * Tests connection
 *
 * @return void
 * @access public
 */
	function testConnect() {
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
	function testDisconnect() {
		$result = $this->Mongo->disconnect();
		$this->assertTrue($result);
		$this->assertFalse($this->Mongo->connected);
	}

/**
 * Tests the listSources method of the Mongodb DataSource
 *
 * @return void
 * @access public
 */
	function testListSources() {
		$this->assertTrue($this->mongodb->listSources());
	}

/**
 * Tests the describe method of the Mongodb DataSource
 *
 * @return void
 * @access public
 */
	function testDescribe() {
		$mockObj = new MockPost();
		$result = $this->mongodb->describe($mockObj);
		$this->assertNull($result);

		$result = $this->mongodb->describe($this->Post);
		$this->assertNotNull($result);
		$expect = array(
				'_id' => array('type' => 'string', 'length' => 24),
				'title' => array('type'=>'string'),
				'body'=>array('type'=>'string'),
				'text'=>array('type'=>'text'),
				'created'=>array('type'=>'datetime'),
				'modified'=>array('type'=>'datetime'),
				);
		$this->assertEqual($expect, $result);
	}

/**
 * Tests find method.
 *
 * @return void
 * @access public
 */
	function testFind() {
		$data = array(
			'title'=>'test',
			'body'=>'aaaa',
			'text'=>'bbbb'
		);
		$this->insertData($data);
		$result = $this->Post->find('all');
		$this->assertEqual(1, count($result));
		$resultData = $result[0][$this->Post->alias];
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
	function testSave() {
		$data = array(
			'title'=>'test',
			'body'=>'aaaa',
			'text'=>'bbbb'
		);
		$saveData[$this->Post->alias] = $data;

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue($saveResult);

		$result = $this->Post->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0][$this->Post->alias];
		$this->assertEqual(6, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$this->assertEqual($this->Post->id, $resultData['_id']);
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);

		$dateRegex = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i';

		$this->assertTrue(preg_match($dateRegex, $resultData['created']));
		$this->assertTrue(preg_match($dateRegex, $resultData['modified']));
	}


	/**
	 * Tests saveAll method.
	 *
	 * @return void
	 * @access public
	 */
	function testSaveAll() {
		$saveData[0][$this->Post->alias] = array(
				'title'=>'test1',
				'body'=>'aaaa1',
				'text'=>'bbbb1'
				);

		$saveData[1][$this->Post->alias] = array(
				'title'=>'test2',
				'body'=>'aaaa2',
				'text'=>'bbbb2'
				);

		$this->Post->create();
		$saveResult = $this->Post->saveAll($saveData);
		$result = $this->Post->find('all');

		$this->assertEqual(2, count($result));

		$resultData = $result[0][$this->Post->alias];
		$this->assertEqual(6, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$data = $saveData[0][$this->Post->alias];
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);

		$dateRegex = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i';

		$this->assertTrue(preg_match($dateRegex, $resultData['created']));
		$this->assertTrue(preg_match($dateRegex, $resultData['modified']));

		$resultData = $result[1][$this->Post->alias];
		$this->assertEqual(6, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$data = $saveData[1][$this->Post->alias];
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['body'], $resultData['body']);
		$this->assertEqual($data['text'], $resultData['text']);

		$dateRegex = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i';
		$this->assertTrue(preg_match($dateRegex, $resultData['created']));
		$this->assertTrue(preg_match($dateRegex, $resultData['modified']));

	}


	/**
	 * Tests update method.
	 *
	 * @return void
	 * @access public
	 */
	function testUpdate() {
		$data = array(
				'title'=>'test',
				'body'=>'aaaa',
				'text'=>'bbbb'
				);
		$saveData[$this->Post->alias] = $data;

		$this->Post->create();
		$saveResult = $this->Post->save($saveData);
		$this->assertTrue($saveResult);
		$findresult = $this->Post->find('all');


		$updatedata = array(
				'_id' => $findresult[0][$this->Post->alias]['_id'],
				'title'=>'test2',
				'body'=>'aaaa2',
				'text'=>'bbbb2'
				);
		$saveData[$this->Post->alias] = $updatedata;

		$saveResult = $this->Post->save($saveData);
		$this->assertTrue($saveResult);

		$result = $this->Post->find('all');

		$this->assertEqual(1, count($result));
		$resultData = $result[0][$this->Post->alias];
		$this->assertEqual(6, count($resultData));
		$this->assertTrue(!empty($resultData['_id']));
		$this->assertEqual($this->Post->id, $resultData['_id']);
		$this->assertEqual($updatedata['title'], $resultData['title']);
		$this->assertEqual($updatedata['body'], $resultData['body']);
		$this->assertEqual($updatedata['text'], $resultData['text']);

	}

	function testSchemaless() {
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
		$MongoArticle->save($toSave);

		$expected = array_intersect_key($toSave, array_flip(array('title', 'body', 'tags')));
		$result = current($MongoArticle->read(array('title', 'body', 'tags')));
		unset ($result['_id']);
		$this->assertEqual($expected, $result);
	}
}

?>