<?php
/**
 * Test cases for the Cakephp mongoDB datasource.
 * Test for schemaless saving.
 * Check data saving before creating the connection of MongoDB.
 *
 * Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2010, Yasushi Ichikawa http://github.com/ichikaway/
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources.without.connection
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Import relevant classes for testing
 */
App::import('Model', 'Mongodb.MongodbSource');


/**
 * MongoArticle class
 *
 * @uses          AppModel
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources
 */
class MongoArticleSchemafree extends AppModel {

	public $useDbConfig = 'mongo_test';
}

/**
 * MongoDB Source test class
 *
 * @package       app
 * @subpackage    app.model.datasources
 */
class FirstMongodbSourceTest extends CakeTestCase {

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
	public function startTest() {
		$connections = ConnectionManager::enumConnectionObjects();

		if (!empty($connections['test']['classname']) && $connections['test']['classname'] === 'mongodbSource') {
			$config = new DATABASE_CONFIG();
			$this->_config = $config->test;
		}

		ConnectionManager::create('mongo_test', $this->_config);

	}

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
	public function endTest() {
		$this->mongodb = new MongodbSource($this->_config);
		$this->mongodb->connect();
		$this->dropData();
		unset($this->mongodb);
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

		$MongoArticle = ClassRegistry::init('MongoArticleSchemafree');
		$MongoArticle->create();
		$this->assertTrue($MongoArticle->save($toSave), 'Saving with no defined schema failed');

		$expected = array_intersect_key($toSave, array_flip(array('title', 'body', 'tags')));
		$result = $MongoArticle->read(array('title', 'body', 'tags'));
		unset ($result['MongoArticleSchemafree']['_id']); // prevent auto added field from screwing things up
		$this->assertEqual($expected, $result['MongoArticleSchemafree']);

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
		$this->assertTrue($MongoArticle->save($toSave), 'Saving with no defined schema failed');
		$starts = $MongoArticle->field('starts');
		$this->assertEqual($toSave['starts'], $starts);
	}

}
