<?php
/**
 * Tests subset validations
 *
 * PHP version 5
 *
 * Copyright (c) 2012, Radig Soluções em TI (http://radig.com.br)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2012, Radig Soluções em Ti (http://radig.com.br)
 * @link          http://github.com/radig/
 * @package       Mongodb
 * @subpackage    Mongodb.Test.Case.Behavior
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');


/**
 * MyCompany class
 *
 * @uses          Post
 * @package       Mongodb
 * @subpackage    Mongodb.Test.Case.Behavior
 */
class MyCompany extends AppModel {

/**
 * useDbConfig property
 * DataSource automatically prepend 'test_' to this name
 *
 * @var string 'mongo'
 * @access public
 */
    public $useDbConfig = 'mongo';

/**
 * mongoSchema property
 * MongoDb Schema for this model
 *
 * @var array
 * @access public
 */
    public $mongoSchema = array(
        'name'  => array('type' => 'string'),
        'address' => array(
            'street'  => array('type' => 'string'),
            'number'  => array('type' => 'number'),
        ),
    );

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
    public $actsAs = array(
        'Mongodb.SubCollectionValidator'
    );

/**
 * validate property
 *
 * @var array
 * @access public
 */
    public $validate = array(
        'name' => 'notempty'
    );

/**
 * collection validate property
 *
 * @var array
 * @access public
 */
    public $collectionValidate = array(
        'address' => array(
            'street' => array(
                'rule' => array('notempty'),
                'message' => 'only letters and numbers'
            ),
            'number' => array(
                'rule' => 'numeric'
            )
        )
    );
}

/**
 * SubCollectionValidatorBehaviorTest class
 *
 * @uses          CakeTestCase
 * @package       Mongodb
 * @subpackage    Mongodb.Test.Case.Behavior
 */
class SubCollectionValidatorBehaviorTest extends CakeTestCase {

/**
 * Sets up the environment for each test method
 *
 * @return void
 * @access public
 */
    public function setUp() {
        $this->Company = ClassRegistry::init(array('class' => 'MyCompany', 'ds' => 'test_mongo'), true);
    }

    public function startTest($method) {
        //clear Company attributes
        $this->Company->create();
    }

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
    public function tearDown() {
        unset($this->Company);
    }

/**
 * testValidateFailure method
 *
 * @return void
 * @access public
 */
    public function testValidateFailure() {
        $expected = false;
        $result = $this->Company->save(array(
            'name' => 'Radig',
            'address' => array('street' => null, 'number' => 141)
        ));
        $this->assertEqual($expected, $result);

        $expected = array('street' => array('only letters and numbers'));
        $result = $this->Company->validationErrors;
        $this->assertEqual($expected, $result);
    }

    /**
 * testValidateSuccess method
 *
 * @return void
 * @access public
 */
    public function testValidateSuccess() {
        $data = array(
            'name' => 'Radig',
            'address' => array('street' => 'abc123', 'number' => 141)
        );

        $result = $this->Company->save($data);
        $this->assertNotEmpty($result);

        $expected = array();
        $result = $this->Company->validationErrors;
        $this->assertEqual($expected, $result);
    }
}