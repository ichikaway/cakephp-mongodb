<?php
/**
 * Relation test cases for the Cakephp mongoDB datasource.
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
 * @subpackage    relation_mongodb.tests.cases.datasources
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::import('Model', 'Mongodb.MongodbSource');


/**
 * MongoUser class
 *
 * @uses          AppModel
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources
 */
class MongoUser extends AppModel {

	public $useDbConfig = 'mongo_test';


	public $hasOne = array(
		'MongoUserProfile' => array(
			'className' => 'MongoUserProfile',
			'foreignKey' => 'user_id',
		)
	);


	public $belongsTo = array(
		'MongoGroup' => array(
			'className' => 'MongoGroup',
			'foreignKey' => 'group_id',
		)
	);

}


/**
 * MongoUserProfile class
 *
 * @uses          AppModel
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources
 */
class MongoUserProfile extends AppModel {

	public $useDbConfig = 'mongo_test';

	public $belongsTo = array(
		'MongoUser' => array(
			'className' => 'MongoUser',
			'foreignKey' => 'user_id',
		)
	);

}

/**
 * MongoGroup class
 *
 * @uses          AppModel
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources
 */
class MongoGroup extends AppModel {

	public $useDbConfig = 'mongo_test';

	public $hasMany = array(
		'MongoUser' => array(
			'className' => 'MongoUser',
			'foreignKey' => 'group_id',
			//'conditions' => array('name' => 'testuser1'),
		)
	);


	public $belongsTo = array(
		'MongoCompany' => array(
			'className' => 'MongoCompany',
			'foreignKey' => 'company_id',
		)
	);

}
/**
 * MongoCompany class
 *
 * @uses          AppModel
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.datasources
 */
class MongoCompany extends AppModel {

	public $useDbConfig = 'mongo_test';

	public $hasMany = array(
		'MongoGroup' => array(
			'className' => 'MongoGroup',
			'foreignKey' => 'company_id',
		)
	);

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

		$this->mongodb =& ConnectionManager::getDataSource('mongo_test');
		$this->mongodb->connect();

		$this->dropData();
	}

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
	public function endTest() {
		$this->dropData();
		ClassRegistry::flush();
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



	public function testHasMany() {

		$Company = ClassRegistry::init('MongoCompany');
		$Group = ClassRegistry::init('MongoGroup');
		$User = ClassRegistry::init('MongoUser');
		$UserProfile = ClassRegistry::init('MongoUserProfile');
		$User->UserProfile = $UserProfile;
		$Group->User = $User;
		$Company->Group = $Group;

		$saveData1 = array(
				'MongoCompany' => array('name' => 'testcompany1','age' => 20),
				'MongoGroup' => array('name' => 'testgroup1','age' => 21),
				'MongoUser' => array(
					array('name' => 'testuser1','age' => 22),
					array('name' => 'testuser2','age' => 23),
					),

				);

		$saveData2 = array(
				'MongoCompany' => array('name' => 'testcompany2','age' => 30),
				'MongoGroup' => array('name' => 'testgroup2','age' => 31),
				'MongoUser' => array(
					array('name' => 'testuser3','age' => 32),
					array('name' => 'testuser4','age' => 33),
					),
				);


		$result1 = $Group->saveAll($saveData1);
		$result2 = $Group->saveAll($saveData2);

		$userFind = $User->find('all');
		foreach($userFind as $key => $val) {
			$userid = $val['MongoUser']['_id'];
			$saveData = array('MongoUserProfile' => array('name' => 'profile'.$key, 'user_id' => $userid));
			$UserProfile->id = null;
			$UserProfile->save($saveData);
		}

		$results = $Company->find('all', array('recursive' => 2));
		//$results = $User->find('all', array('recursive' => 2) );
		pr($results);


		//$groupData = $Group->find('all', array('recursive' => 1));
		//$userData = $Group->User->find('all');
		//pr($groupData);
		//pr($userData);

		//$userData = $User->find('all');
/*
		$this->assertEqual(1, count($groupData));
		$this->assertEqual(2, count($userData));

		$this->assertEqual($groupData[0]['MongoGroup']['_id'], $userData[0]['MongoUser']['group_id']);
		$this->assertEqual($groupData[0]['MongoGroup']['_id'], $userData[1]['MongoUser']['group_id']);

		$this->assertEqual($saveData['MongoGroup']['name'], $groupData[0]['MongoGroup']['name']);
		$this->assertEqual($saveData['MongoUser'][0]['name'], $userData[0]['MongoUser']['name']);
		$this->assertEqual($saveData['MongoUser'][1]['name'], $userData[1]['MongoUser']['name']);
*/
	}

}
