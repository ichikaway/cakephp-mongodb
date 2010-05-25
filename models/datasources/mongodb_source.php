<?php
/**
* A CakePHP datasource for the mongoDB (http://www.mongodb.org/) document-oriented database.
*
* This datasource uses Pecl Mongo (http://php.net/mongo)
* and is thus dependent on PHP 5.0 and greater.
*
* Original implementation by ichikaway(Yasushi Ichikawa) http://github.com/ichikaway/
*
* Reference:
*	Nate Abele's lithium mongoDB datasource (http://li3.rad-dev.org/)
*	Joél Perras' divan(http://github.com/jperras/divan/)
*
* Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
*
* Contributors: Predominant, Jrbasso, tkyk
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @copyright Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
* @package mongodb
* @subpackage mongodb.models.datasources
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::import('Datasource', 'DboSource');

/**
 * MongoDB Source
 *
 * @package mongodb
 * @subpackage mongodb.models.datasources
 */
class MongodbSource extends DboSource {

/**
 * Database Instance
 *
 * @var resource
 * @access protected
 */
	protected $_db = null;

/**
 * Base Config
 *
 * @var array
 * @access protected
 *
 * set_string_id:
 *    true: In read() method, convert MongoId object to string and set it to array 'id'.
 *    false: not convert and set.
 */
	var $_baseConfig = array(
		'set_string_id' => true,
		'persistent' => false,
		'host'       => 'localhost',
		'database'   => '',
		'port'       => '27017'
	);


/**
 * column definition
 *
 * @var array
 */
    var $columns = array(
        'string' => array('name'  => 'varchar'),
        'text' => array('name' => 'text'),
        'integer' => array('name' => 'integer', 'formatter' => 'intval'),
        'float' => array('name' => 'float', 'formatter' => 'floatval'),
        'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
        'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
        'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
        'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
    );

/**
 * Default schema for the mongo models
 *
 * @var array
 * @access protected
 */
    protected $_defaultSchema = array('_id' => array('type' => 'string', 'length' => 24));

/**
 * Constructor
 *
 * @param array $config Configuration array
 * @access public
 */
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->connect();
	}


/**
 * Destruct
 *
 * @access public
 */
    public function __destruct() {
        if ($this->connected) {
            $this->disconnect();
        }
    }

/**
 * commit method
 *
 * MongoDB doesn't support transactions
 *
 * @return void
 * @access public
 */
	public function commit() {
		return false;
	}

/**
 * Connect to the database
 *
 * @return boolean Connected
 * @access public
 */
	public function connect() {
		$this->connected = false;
		$host = $this->config['host'] . ':' . $this->config['port'];
		$this->connection = new Mongo($host, true, $this->config['persistent']);
		if ($this->_db = $this->connection->selectDB($this->config['database'])) {
			$this->connected = true;
		}
		return $this->connected;
	}

/**
 * check connection to the database
 *
 * @return boolean Connected
 * @access public
 */
	public function isConnected() {
		return $this->connected;
	}



/**
 * Close database connection
 *
 * @return boolean Connected
 * @access public
 */
	public function close() {
		return $this->disconnect();
	}

/**
 * Disconnect from the database
 *
 * @return boolean Connected
 * @access public
 */
	public function disconnect() {
		if ($this->connected) {
			$this->connected = !$this->connection->close();
			unset($this->_db, $this->connection);
			return !$this->connected;
		}
		return true;
	}

/**
 * Get list of available Collections
 *
 * @param array $data
 * @return array Collections
 * @access public
 */
	public function listSources($data = null) {
		return true;
		/*
		$list = $this->_db->listCollections();
		if (empty($list)) {
			return array();
		} else {
			$collections = null;
			foreach($this->_db->listCollections() as $collection) {
				$collections[] = $collection->getName();
			}
			return $collections;
		}
		*/
	}

/**
 * Describe
 *
 * @param Model $model
 * @return array if model instance has mongoSchema, return it.
 * @access public
 */
	public function describe(&$model) {
		$model->primaryKey = '_id';
		$schema = array();
		if (!empty($model->mongoSchema) && is_array($model->mongoSchema)) {
			$schema = $model->mongoSchema;
			return $schema + $this->_defaultSchema;
		}
	}

/**
 * begin method
 *
 * Mongo doesn't support transactions
 *
 * @return void
 * @access public
 */
	public function begin() {
		return false;
	}

/**
 * Calculate
 *
 * @param Model $model
 * @return array
 * @access public
 */
	public function calculate (&$model) {
		return array('count' => true);
	}

/**
 * Quotes identifiers.
 *
 * MongoDb does not need identifiers quoted, so this method simply returns the identifier.
 *
 * @param string $name The identifier to quote.
 * @return string The quoted identifier.
 */
	public function name($name) {
		return $name;
	}


/**
 * Create Data
 *
 * @param Model $model Model Instance
 * @param array $fields Field data
 * @param array $values Save data
 * @return boolean Insert result
 * @access public
 */
	public function create(&$model, $fields = null, $values = null) {
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		} else {
			$data = $model->data;
		}

		$result = $this->_db
			->selectCollection($model->table)
			->insert($data, true);

		if ($result['ok'] === 1.0) {
			$id = is_object($data['_id']) ? $data['_id']->__toString() : null;
			$model->setInsertID($id);
			$model->id = $id;
			return true;
		}
		return false;
	}


/**
 * Update Data
 *
 * @param Model $model Model Instance
 * @param array $fields Field data
 * @param array $values Save data
 * @return boolean Update result
 * @access public
 */
	public function update(&$model, $fields = null, $values = null, $conditions = null) {
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);

		} else if($fields !== null && $conditions !== null) {
			return $this->updateAll($model, $fields, $conditions);

		} else{
			$data = $model->data;
		}

		if (!empty($data['_id']) && !is_object($data['_id'])) {
			$data['_id'] = new MongoId($data['_id']);
		}

		$mongoCollectionObj = $this->_db
			->selectCollection($model->table);

		if (!empty($data['_id'])) {
			$cond = array('_id' => $data['_id']);
			unset($data['_id']);
			$data = array('$set' => $data);
			return $mongoCollectionObj->update($cond, $data, array("multiple" => false));
		} else {
			return $mongoCollectionObj->save($data);
		}
	}



/**
 * Update multiple Record
 *
 * @param Model $model Model Instance
 * @param array $fields Field data
 * @param array $conditions
 * @return boolean Update result
 * @access public
 */
	public function updateAll (&$model, $fields = null,  $conditions = null) {
		$fields = array('$set' => $fields);

		$result = $this->_db
			->selectCollection($model->table)
			->update($conditions, $fields, array("multiple" => true));

		return $result;
	}



/**
 * Delete Data
 *
 * @param Model $model Model Instance
 * @param array $conditions
 * @return boolean Update result
 * @access public
 */
	public function delete(&$model, $conditions = null) {

		$id = null;
		if (empty($conditions)) {
			$id = $model->id;

		} else if (is_array($conditions) && !empty($conditions['_id'])) {
			$id = $conditions['_id'];

		} else if(!empty($conditions) && !is_array($conditions)) {
			$id = $conditions;
			$conditions = null;
		}

		if (!empty($id) && is_string($id)) {
			$conditions['_id'] = new MongoId($id);
		}

		$mongoCollectionObj = $this->_db
			->selectCollection($model->table);

		$result = true;
		if (!empty($conditions[$model->alias . '._id']) && is_array($conditions[$model->alias . '._id'])) {
			//for Model::deleteAll()
			foreach ($conditions[$model->alias . '._id'] as $val) {
				$id = is_string($val) ? new MongoId($val) : $val;
				if (!$mongoCollectionObj->remove(array('_id' => $id))) {
					$result = false;
				}
			}

		} else {
			$return = $mongoCollectionObj->remove($conditions);
		}

		return $result;

	}






/**
 * Read Data
 *
 * @param Model $model Model Instance
 * @param array $query Query data
 * @return array Results
 * @access public
 */
	public function read(&$model, $query = array()) {
		$query = $this->_setEmptyArrayIfEmpty($query);
		extract($query);

		if (!empty($order[0])) {
			$order = array_shift($order);
		}

		if (!empty($conditions['_id']) && !is_object($conditions['_id'])) {
			$conditions['_id'] = new MongoId($conditions['_id']);
		}

		$fields = (is_array($fields)) ? $fields : array($fields);
		$conditions = (is_array($conditions)) ? $conditions : array($conditions);
		$order = (is_array($order)) ? $order : array($order);

		/*
		 * before update, model::save() check exist record with conditions key (ex: Post._id).
		 * Convert Post._id to _id and make a MongoId object
		 */
		if (!empty($conditions[$model->alias . '._id'])) {
			$conditions['_id'] = new MongoId($conditions[$model->alias . '._id']);
			unset($conditions[$model->alias . '._id']);
		}

		$result = $this->_db
			->selectCollection($model->table)
			->find($conditions, $fields)
			->sort($order)
			->limit($limit)
			->skip(($page - 1) * $limit);

		if ($model->findQueryType === 'count') {
			return array(array($model->alias => array('count' => $result->count())));
		}

		$results = null;
		while ($result->hasNext()) {
			$mongodata = $result->getNext();
			if ($this->config['set_string_id'] && !empty($mongodata['_id']) && is_object($mongodata['_id'])) {
				$mongodata['_id'] = $mongodata['_id']->__toString();
			}
			$results[][$model->alias] = $mongodata;
		}
		return $results;
	}

/**
 * rollback method
 *
 * MongoDB doesn't support transactions
 *
 * @return void
 * @access public
 */
	public function rollback() {
		return false;
	}

/**
 * execute method
 *
 * Should never reach here.
 *
 * @param mixed $query
 * @return void
 * @access protected
 */
	protected function _execute($query) {
		if (Configure::read()) {
			debug(Debugger::trace());
			debug ($query); die;
		}
	}

/**
 * Recursively Setup Empty arrays for data
 *
 * @param mixed $data Input Data
 * @return array
 * @access protected
 */
	protected function _setEmptyArrayIfEmpty($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				if (empty($value)) {
					$data[$key] = array();
				}
			}
			return $data;
		} else {
			return empty($data) ? array() : $data;
		}
	}

}
?>