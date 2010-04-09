<?php

/**
* Import relevant classes for testing
*/
App::import('Model', 'MongodbSource');

/**
* Generate Mock Model
*/
Mock::generate('AppModel', 'MockPost');


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

class MongodbSourceTest extends CakeTestCase {

	var $mongodb;

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

	function startTest() {
		$this->Mongo = new MongodbSource($this->_config);
		ConnectionManager::create('mongo_test', $this->_config);

		$this->Post = ClassRegistry::init('Post');
		$this->Post->setDataSource('mongo_test');

		$this->mongodb =& ConnectionManager::getDataSource($this->Post->useDbConfig);
	}

	function endTest() {
		$this->dropData();
		unset($this->Post);
	}

	function insertData($data) {
		$this->mongodb
			->connection
			->selectDB($this->_config['database'])
            ->selectCollection($this->Post->table)
            ->insert($data, true);
	}

	function dropData() {
		$this->mongodb
			->connection
			->dropDB($this->_config['database']);
	}

	function testConnect() {
		$result = $this->Mongo->connect();
		$this->assertTrue($result);

		$this->assertTrue($this->Mongo->connected);
		$this->assertTrue($this->Mongo->isConnected());

	}

	function testDisconnect() {
		$result = $this->Mongo->disconnect();
		$this->assertTrue($result);
		$this->assertFalse($this->Mongo->connected);
	}

/**
* Tests the listSources method of the Mongodb DataSource
*
* @return void
*/
	function testListSources() {
		$this->assertTrue($this->mongodb->listSources());
	}

/**
* Tests the describe method of the Mongodb DataSource
*
* @return void
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



}

?>
