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


App::uses('Model', 'Model');
App::uses('AppModel', 'Model');


/**
 * SqlCompatiblePost class
 *
 * @uses          Post
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.behaviors
 */
class SqlCompatiblePost extends AppModel {

/**
 * useDbConfig property
 *
 * @var string 'test_mongo'
 * @access public
 */
	public $useDbConfig = 'test_mongo';

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
		'datasource' => 'Mongodb.MongodbSource',
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'database' => 'test_mongo',
		'port' => 27017,
		'prefix' => '',
		'persistent' => false,
	);

	public function setUp() {
		$connections = ConnectionManager::enumConnectionObjects();

		if (!empty($connections['test']['classname']) && $connections['test']['classname'] === 'mongodbSource') {
			$config = new DATABASE_CONFIG();
			$this->_config = $config->test;
		}

		if(!isset($connections['test_mongo'])) {
			ConnectionManager::create('test_mongo', $this->_config);
			$this->Mongo = new MongodbSource($this->_config);
		}

		$this->Post = ClassRegistry::init(array('class' => 'SqlCompatiblePost', 'alias' => 'Post', 'ds' => 'test_mongo'), true);
	}

/**
 * Sets up the environment for each test method
 *
 * @return void
 * @access public
 */
	public function startTest($method) {
		$this->_setupData();
	}

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
	public function endTest($method) {
		$this->Post->deleteAll(true);
	}

	public function tearDown() {
		unset($this->Post);
		ClassRegistry::flush();
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

		$result = Hash::extract($result, '{n}.Post.title');
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
		$result = Hash::extract($result, '{n}.Post.title');
		$this->assertEqual($expected, $result);

		$conditions = array(
			'title' => array('$nin' => array(10))
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);
	}

/**
 * testNOTIN method
 *
 * @return void
 * @access public
 */
	public function testNOTIN() {
		$expected = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
		$result = $this->Post->find('all', array(
			'conditions' => array(
				'title NOT IN' => array(10),
			),
			'fields' => array('_id', 'title', 'number'),
			'order' => array('number' => 'ASC')
		));
		$result = Hash::extract($result, '{n}.Post.title');
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
		$result = Hash::extract($result, '{n}.Post.title');
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
		$result = Hash::extract($result, '{n}.Post.title');
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
		$result = Hash::extract($result, '{n}.Post.title');
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
	 * Tests find method with conditions _id=>array()
	 *
	 * @return void
	 * @access public
	 */
	public function testFindConditionIn() {

		for ($i = 1; $i <= 5; $i++) {
			$data = array(
					'_id' => 'A1' . $i,
					'title' => $i,
					);
			$saveData['Post'] = $data;
			$this->Post->create();
			$this->Post->save($saveData);
		}

		$params = array('conditions' => array('_id' => array('A11', 'A12')));
		$result = $this->Post->find('all', $params);

		$expected = array('A11','A12');
		$result = Hash::extract($result, '{n}.Post._id');
		$this->assertEqual($expected, $result);
		$this->assertEqual(2, count($result));

		$conditions = array(
			'_id' => array(
				'$in' => array('A11', 'A12')
			)
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);


		$params = array('conditions' => array('_id' => array('$nin' => array('A11', 'A12'))));
		$result = $this->Post->find('all', $params);
		//$expected = array('A13','A14');
		$result = Hash::extract($result, '{n}.Post._id');
		$this->assertTrue(in_array('A13', $result));
		$this->assertFalse(in_array('A11', $result));
		$this->assertFalse(in_array('A12', $result));
		$this->assertEqual(23, count($result));


		$conditions = array(
			'_id' => array(
				'$nin' => array('A11', 'A12')
			)
		);
		$this->assertEqual($conditions, $this->Post->lastQuery['conditions']);
	}


/**
 * Order method
 *
 * @return void
 * @access public
 */
	public function testOrderDESC() {
		$expected = array(20, 19);
		$result = $this->Post->find('all', array(
			'conditions' => array('title >' => 18),
			'fields' => array('_id', 'title'),
			'order' => array('title DESC')
		));
		$result = Hash::extract($result, '{n}.Post.title');
		$this->assertEqual($expected, $result);

		$order = array(array('title' => 'DESC'));
		$this->assertEqual($order, $this->Post->lastQuery['order']);
	}

/**
 * Order method
 *
 * @return void
 * @access public
 */
	public function testOrderASC() {
		$expected = array(19, 20);
		$result = $this->Post->find('all', array(
			'conditions' => array('title >' => 18),
			'fields' => array('_id', 'title'),
			'order' => array('title ASC')
		));
		$result = Hash::extract($result, '{n}.Post.title');
		$this->assertEqual($expected, $result);

		$order = array(array('title' => 'ASC'));
		$this->assertEqual($order, $this->Post->lastQuery['order']);
	}


/**
 * Order method with model alias
 *
 * @return void
 * @access public
 */
	public function testOrderWithModelAlias() {
		$expected = array(20, 19);
		$result = $this->Post->find('all', array(
			'conditions' => array('title >' => 18),
			'fields' => array('_id', 'title'),
			'order' => array('Post.title DESC')
		));
		$result = Hash::extract($result, '{n}.Post.title');
		$this->assertEqual($expected, $result);
	}

/**
 * Convert MongoDate objects to strings
 *
 * @return void
 * @access public
 */
	public function testConvertDates() {
		$expected = '2011-Nov-22 00:00:00';
		$data = array('title' => 'date', 'created_at' => new MongoDate(strtotime('2011-11-22 00:00:00')));
		$this->Post->save($data);
		$result = $this->Post->read();
		$this->assertEqual($expected, $result['Post']['created_at']);
	}

/**
 * Convert MongoDate objects to another format strings
 *
 * @return void
 * @access public
 */
	public function testConvertDatesAnotherFormat() {
		$this->Post->Behaviors->detach('SqlCompatible');
		$this->Post->Behaviors->attach('Mongodb.SqlCompatible', array('dateFormat' => 'Y-m-d H:i:s'));

		$expected = '2011-11-22 00:00:00';
		$data = array('title' => 'date', 'created_at' => new MongoDate(strtotime('2011-11-22 00:00:00')));
		$this->Post->save($data);
		$result = $this->Post->read();
		$this->assertEqual($expected, $result['Post']['created_at']);
	}

/**
 * setupData method
 *
 * @return void
 * @access protected
 */
	protected function _setupData() {
		$this->Post->deleteAll(true, false);
		$this->Post->primaryKey = '_id';

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
