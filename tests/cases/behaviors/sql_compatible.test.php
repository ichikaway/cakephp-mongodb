<?php
/**
 * Tests specific to the sql compatible behavior
 *
 * PHP version 5
 *
 * Copyright (c) 2010, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2010, Andy Dawson
 * @link          www.ad7six.com
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.behaviors
 * @since         v 1.0 (14-Dec-2010)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Import relevant classes for testing, rely on main test for loading clases
 */
require_once(dirname(dirname(__FILE__)) . DS . 'datasources' . DS . 'mongodb_source.test.php');

/**
 * SqlCompatiblePost class
 *
 * @uses          Post
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.behaviors
 */
class SqlCompatiblePost extends Post {

/**
 * useDbConfig property
 *
 * @var string 'mongo_test'
 * @access public
 */
	public $useDbConfig = 'mongo_test';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array(
		'Mongodb.SqlCompatible'
	);

	public $lastQuery = array();

	public function beforeFind($query) {
		$this->lastQuery = $query;
		return $query;
	}
}

/**
 * SqlCompatibleTest class
 *
 * @uses          CakeTestCase
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.behaviors
 */
class SqlCompatibleTest extends CakeTestCase {

/**
 * Default db config. overriden by test db connection if present
 *
 * @var array
 * @access protected
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
		$this->Mongo = new MongodbSource($this->_config);

		$this->Post = ClassRegistry::init(array('class' => 'SqlCompatiblePost', 'alias' => 'Post', 'ds' => 'mongo_test'));

		$this->_setupData();
	}

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
	public function endTest() {
		$this->Post->deleteAll(true);
		unset($this->Post);
	}

/**
 * testNOT method
 *
 * @return void
 * @access public
 */
	public function testNOT() {
		$expected = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
		$result = $this->Post->find('all', array(
			'conditions' => array(
				'title !=' => 10,
			),
			'fields' => array('_id', 'title', 'number'),
			'order' => array('number' => 'ASC')
		));
		$result = Set::extract($result, '/Post/title');
		$this->assertEqual($expected, $result);

		$conditions = array(
			'title' => array('$ne' => 10)
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);

		$result = $this->Post->find('all', array(
			'conditions' => array(
				'NOT' => array(
					'title' => 10
				),
			),
			'fields' => array('_id', 'title', 'number'),
			'order' => array('number' => 'ASC')
		));
		$result = Set::extract($result, '/Post/title');
		$this->assertEqual($expected, $result);

		$conditions = array(
			'title' => array('$nin' => array(10))
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);
	}

/**
 * testGTLT method
 *
 * @return void
 * @access public
 */
	public function testGTLT() {
		$expected = array(8, 9, 10, 11, 12, 13);
		$result = $this->Post->find('all', array(
			'conditions' => array(
				'title >' => 7,
				'title <' => 14,
			),
			'fields' => array('_id', 'title', 'number'),
			'order' => array('number' => 'ASC')
		));
		$result = Set::extract($result, '/Post/title');
		$this->assertEqual($expected, $result);

		$conditions = array(
			'title' => array(
				'$gt' => 7,
				'$lt' => 14
			)
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);
	}

/**
 * testGTE method
 *
 * @return void
 * @access public
 */
	public function testGTE() {
		$expected = array(19, 20);
		$result = $this->Post->find('all', array(
			'conditions' => array(
				'title >=' => 19,
			),
			'fields' => array('_id', 'title', 'number'),
			'order' => array('number' => 'ASC')
		));
		$result = Set::extract($result, '/Post/title');
		$this->assertEqual($expected, $result);

		$conditions = array(
			'title' => array('$gte' => 19)
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);
	}

/**
 * testOR method
 *
 * @return void
 * @access public
 */
	public function testOR() {
		$expected = array(1, 2, 19, 20);
		$result = $this->Post->find('all', array(
			'conditions' => array(
				'OR' => array(
					'title <=' => 2,
					'title >=' => 19,
				)
			),
			'fields' => array('_id', 'title', 'number'),
			'order' => array('number' => 'ASC')
		));
		$result = Set::extract($result, '/Post/title');
		$this->assertEqual($expected, $result);

		$conditions = array(
			'$or' => array(
				array('title' => array('$lte' => 2)),
				array('title' => array('$gte' => 19))
			)
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);
	}

/**
 * setupData method
 *
 * @return void
 * @access protected
 */
	protected function _setupData() {
		$this->Post->deleteAll(true);
		for ($i = 1; $i <= 20; $i++) {
			$data = array(
				'title' => $i,
			);
			$saveData['Post'] = $data;
			$this->Post->create();
			$this->Post->save($saveData);
		}
	}
}