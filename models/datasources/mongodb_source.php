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
 * Contributors: Predominant, Jrbasso, tkyk, AD7six
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
 * @package       mongodb
 * @subpackage    mongodb.models.datasources
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::import('Datasource', 'DboSource');

/**
 * MongoDB Source
 *
 * @package       mongodb
 * @subpackage    mongodb.models.datasources
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
 * Mongo Driver Version
 *
 * @var string
 * @access protected
 */
	protected $_driverVersion = Mongo::VERSION;

/**
 * startTime property
 *
 * If debugging is enabled, stores the (micro)time the current query started
 *
 * @var mixed null
 * @access protected
 */
	protected $_startTime = null;

/**
 * Base Config
 *
 * @TODO must be public because the parent var is public
 * @var array
 * @access protected
 *
 * set_string_id:
 *    true: In read() method, convert MongoId object to string and set it to array 'id'.
 *    false: not convert and set.
 */
	public $_baseConfig = array(
		'set_string_id' => true,
		'persistent' => false,
		'host'       => 'localhost',
		'database'   => '',
		'port'       => '27017',
		'login'		=> '',
		'password'	=> ''
	);

/**
 * column definition
 *
 * @var array
 */
	public $columns = array(
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
	protected $_defaultSchema = array(
		'_id' => array('type' => 'string', 'length' => 24, 'key' => 'primary'),
		'created' => array('type' => 'datetime', 'default' => null)
	);

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
 * If using 1.0.2 or above use the mongodb:// format to connect
 * The connect syntax changed in version 1.0.2 - so check for that too
 *
 * If authentication information in present then authenticate the connection
 *
 * @return boolean Connected
 * @access public
 */
	public function connect() {
		$this->connected = false;

		try{
			if (false && $this->_driverVersion >= '1.0.2' && $this->config['host'] != 'localhost') {
				$host = "mongodb://";
			} else {
				$host = '';
			}
			$host .= $this->config['host'] . ':' . $this->config['port'];

			if (false && $this->_driverVersion >= '1.0.2') {
				$this->connection = new Mongo($host, array("persist" => $this->config['persistent']));
			} else {
				$this->connection = new Mongo($host, true, $this->config['persistent']);
			}

			if ($this->_db = $this->connection->selectDB($this->config['database'])) {
				if (!empty($this->config['login'])) {
					$return = $this->_db->authenticate($this->config['login'], $this->config['password']);
					if (!$return || !$return['ok']) {
						trigger_error('MongodbSource::connect ' . $return['errmsg']);
						return false;
					}
				}
				$this->connected = true;
			}
		} catch(MongoException $e) {
			trigger_error($e->getMessage());
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
 * @param Model $Model
 * @return array if model instance has mongoSchema, return it.
 * @access public
 */
	public function describe(&$Model, $field = null) {
		$Model->primaryKey = '_id';
		$schema = array();
		if (!empty($Model->mongoSchema) && is_array($Model->mongoSchema)) {
			$schema = $Model->mongoSchema;
			return $schema + $this->_defaultSchema;
		} elseif (is_a($Model, 'Model') && !empty($Model->Behaviors)) {
			$Model->Behaviors->attach('Mongodb.Schemaless');
		}
		return $this->deriveSchemaFromData($Model);
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
 * @param Model $Model
 * @return array
 * @access public
 */
	public function calculate(&$Model) {
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
 * @param Model $Model Model Instance
 * @param array $fields Field data
 * @param array $values Save data
 * @return boolean Insert result
 * @access public
 */
	public function create(&$Model, $fields = null, $values = null) {
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		} else {
			$data = $Model->data;
		}
		if (!empty($data['_id'])) {
			$this->_convertId($data['_id']);
		}

		$this->_prepareLogQuery($Model); // just sets a timer
		try{
			$result = $this->_db
				->selectCollection($Model->table)
				->insert($data, true);
		} catch (MongoException $e) {
			trigger_error($e->getMessage());
		}
		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->useTable}.insert( :data , true)", compact('data'));
		}

		if (!empty($result) && $result['ok'] === 1.0) {
			$id = is_object($data['_id']) ? $data['_id']->__toString() : null;
			$Model->setInsertID($id);
			$Model->id = $id;
			return true;
		}
		return false;
	}

/**
 * ensureIndex method
 *
 * @param mixed $Model
 * @param array $keys array()
 * @param array $params array()
 * @return void
 * @access public
 */
	public function ensureIndex(&$Model, $keys = array(), $params = array()) {
		try{
			return $this->_db
				->selectCollection($Model->table)
				->ensureIndex($keys, $params);
		} catch (MongoException $e) {
			trigger_error($e->getMessage());
		}
		return false;
	}

/**
 * Update Data
 *
 * @param Model $Model Model Instance
 * @param array $fields Field data
 * @param array $values Save data
 * @return boolean Update result
 * @access public
 */
	public function update(&$Model, $fields = null, $values = null, $conditions = null) {
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		} elseif($fields !== null && $conditions !== null) {
			return $this->updateAll($Model, $fields, $conditions);
		} else{
			$data = $Model->data;
		}

		if (empty($data['_id'])) {
			$data['_id'] = new MongoId($Model->id);
		} else {
			$this->_convertId($data['_id']);
		}

		try{
			$mongoCollectionObj = $this->_db
				->selectCollection($Model->table);
		} catch (MongoException $e) {
			trigger_error($e->getMessage());
			return false;
		}

		$this->_prepareLogQuery($Model); // just sets a timer
		if (!empty($data['_id'])) {
			$this->_convertId($data['_id']);
			$cond = array('_id' => $data['_id']);
			unset($data['_id']);
			$data = array('$set' => $data);

			try{
				$return = $mongoCollectionObj->update($cond, $data, array("multiple" => false));
			} catch (MongoException $e) {
				trigger_error($e->getMessage());
			}
			if ($this->fullDebug) {
				$this->logQuery("db.{$Model->useTable}.update( :conditions, :data, :params )",
					array('conditions' => $cond, 'data' => $data, 'params' => array("multiple" => false))
				);
			}
		} else {
			try{
				$return = $mongoCollectionObj->save($data);
			} catch (MongoException $e) {
				trigger_error($e->getMessage());
			}
			if ($this->fullDebug) {
				$this->logQuery("db.{$Model->useTable}.save( :data )", compact('data'));
			}
		}
		return $return;
	}

/**
 * Update multiple Record
 *
 * @param Model $Model Model Instance
 * @param array $fields Field data
 * @param array $conditions
 * @return boolean Update result
 * @access public
 */
	public function updateAll(&$Model, $fields = null,  $conditions = null) {
		$fields = array('$set' => $fields);

		$this->_stripAlias($conditions, $Model->alias);
		$this->_stripAlias($fields, $Model->alias, false, 'value');

		$this->_prepareLogQuery($Model); // just sets a timer
		try{
			$result = $this->_db
				->selectCollection($Model->table)
				->update($conditions, $fields, array("multiple" => true));
		} catch (MongoException $e) {
			trigger_error($e->getMessage());
		}

		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->useTable}.update( :fields, :params )",
				array('fields' => $fields, 'params' => array("multiple" => true))
			);
		}
		return $result;
	}

/**
 * deriveSchemaFromData method
 *
 * @param mixed $Model
 * @param array $data array()
 * @return void
 * @access public
 */
	public function deriveSchemaFromData($Model, $data = array()) {
		if (!$data) {
			$data = $Model->data;
			if ($data && array_key_exists($Model->alias, $data)) {
				$data = $data[$Model->alias];
			}
		}

		$return = $this->_defaultSchema;

		if ($data) {
			$fields = array_keys($data);
			foreach($fields as $field) {
				if (in_array($field, array('created', 'modified', 'updated'))) {
					$return[$field] = array('type' => 'datetime', 'null' => true);
				} else {
					$return[$field] = array('type' => 'string', 'length' => 2000);
				}
			}
		}

		return $return;
	}

/**
 * Delete Data
 *
 * @param Model $Model Model Instance
 * @param array $conditions
 * @return boolean Update result
 * @access public
 */
	public function delete(&$Model, $conditions = null) {
		$id = null;

		$this->_stripAlias($conditions, $Model->alias);
		if (!$conditions) {
			$conditions = array();
		}

		if (empty($conditions)) {
			$id = $Model->id;
		} elseif (is_array($conditions) && !empty($conditions['_id'])) {
			$id = $conditions['_id'];
		} elseif (!empty($conditions) && !is_array($conditions)) {
			$id = $conditions;
			$conditions = array();
		}

		if (!empty($id)) {
			$conditions['_id'] = $id;
			$this->_convertId($conditions['_id']);
		}

		$mongoCollectionObj = $this->_db
			->selectCollection($Model->table);

		$this->_stripAlias($conditions, $Model->alias);
		if (!empty($conditions['_id'])) {
			$this->_convertId($conditions['_id']);
		}

		$result = false;
		try{
			$this->_prepareLogQuery($Model); // just sets a timer
			if (!$conditions)  {
				$return = $mongoCollectionObj->drop();
				if ($this->fullDebug) {
					$this->logQuery("db.{$Model->useTable}.drop()");
				}
			} else {
				$return = $mongoCollectionObj->remove($conditions);
				if ($this->fullDebug) {
					$this->logQuery("db.{$Model->useTable}.remove( :conditions )",
						compact('conditions')
					);
				}
			}
			$result = true;
		} catch (MongoException $e) {
			trigger_error($e->getMessage());
		}
		return $result;
	}

/**
 * Read Data
 *
 * @param Model $Model Model Instance
 * @param array $query Query data
 * @return array Results
 * @access public
 */
	public function read(&$Model, $query = array()) {
		$query = $this->_setEmptyArrayIfEmpty($query);
		extract($query);

		if (!empty($order[0])) {
			$order = array_shift($order);
		}
		$this->_stripAlias($conditions, $Model->alias);
		$this->_stripAlias($fields, $Model->alias, false, 'value');
		$this->_stripAlias($order, $Model->alias, false, 'both');

		if (!empty($conditions['_id'])) {
			$this->_convertId($conditions['_id']);
		}

		$fields = (is_array($fields)) ? $fields : array($fields => 1);
		$conditions = (is_array($conditions)) ? $conditions : array($conditions);
		$order = (is_array($order)) ? $order : array($order);

		if (is_array($order)) {
			foreach($order as $field => &$dir) {
				if (strtoupper($dir) === 'ASC') {
					$dir = 1;
					continue;
				} elseif (strtoupper($dir) === 'DESC') {
					$dir = -1;
					continue;
				}
				$dir = (int)$dir;
			}
		}

		if (empty($offset) && $page && $limit) {
			$offset = ($page - 1) * $limit;
		}
		$this->_prepareLogQuery($Model); // just sets a timer
		$result = $this->_db
			->selectCollection($Model->table)
			->find($conditions, $fields)
			->sort($order)
			->limit($limit)
			->skip($offset);
		if ($this->fullDebug) {
			$count = $result->count();
			$this->logQuery("db.{$Model->useTable}.find( :conditions, :fields ).sort( :order ).limit( :limit ).skip( :offset )",
				compact('conditions', 'fields', 'order', 'limit', 'offset', 'count')
			);
		}

		if ($Model->findQueryType === 'count') {
			return array(array($Model->alias => array('count' => $result->count())));
		}

		$results = null;
		while ($result->hasNext()) {
			$mongodata = $result->getNext();
			if ($this->config['set_string_id'] && !empty($mongodata['_id']) && is_object($mongodata['_id'])) {
				$mongodata['_id'] = $mongodata['_id']->__toString();
			}
			$results[][$Model->alias] = $mongodata;
		}
		if ($Model->name == 'HotelTranslation') {
			//debug ($this);
			//die;
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
 * @param mixed $query
 * @param array $params array()
 * @return void
 * @access public
 */
	public function execute($query, $params = array()) {
		$this->_prepareLogQuery($Model); // just sets a timer
		$result = $this->_db
			->execute($query, $params);
		if ($this->fullDebug) {
			if ($params) {
				$this->logQuery(":query, :params",
					compact('query', 'params')
				);
			} else {
				$this->logQuery($query);
			}
		}
		if ($result['ok']) {
			return $result['retval'];
		}
		return $result;
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

/**
 * prepareLogQuery method
 *
 * Any prep work to log a query
 *
 * @param mixed $Model
 * @return void
 * @access protected
 */
	protected function _prepareLogQuery(&$Model) {
		if (!$this->fullDebug) {
			return false;
		}
		$this->_startTime = microtime(true);
		$this->took = null;
		$this->affected = null;
		$this->error = null;
		$this->numRows = null;
		return true;
	}

/**
 * logQuery method
 *
 * Set timers, errors and refer to the parent
 * If there are arguments passed - inject them into the query
 * Show MongoIds in a copy-and-paste-into-mongo format
 *
 *
 * @param mixed $query
 * @param array $args array()
 * @return void
 * @access public
 */
	public function logQuery($query, $args = array()) {
		if ($args) {
			$this->_stringify($args);
			$query = String::insert($query, $args);
		}
		$this->took = round((microtime(true) - $this->_startTime) * 1000, 0);
		$this->affected = null;
		$this->error = $this->_db->lastError();
		$this->numRows = !empty($args['count'])?$args['count']:null;

		$query = preg_replace('@"ObjectId\((.*?)\)"@', 'ObjectId ("\1")', $query);
		return parent::logQuery($query);
	}

/**
 * convertId method
 *
 * @param mixed $mixed
 * @return void
 * @access protected
 */
	protected function _convertId(&$mixed) {
		if (is_string($mixed)) {
			$mixed = new MongoId($mixed);
		}
		if (is_array($mixed)) {
			foreach($mixed as &$row) {
				$this->_convertId($row);
			}
		}
	}

/**
 * stringify method
 *
 * Takes an array of args as an input and returns an array of json-encoded strings. Takes care of
 * any objects the arrays might be holding (MongoID);
 *
 * @param array $args array()
 * @param int $level 0 internal recursion counter
 * @return array
 * @access protected
 */
	protected function _stringify(&$args = array(), $level = 0) {
		foreach($args as &$arg) {
			if (is_array($arg)) {
				$this->_stringify($arg, $level + 1);
			} elseif (is_object($arg) && is_callable(array($arg, '__toString'))) {
				$arg = 'ObjectId(' . $arg->__toString() . ')';
			}
			if ($level === 0) {
				$arg = json_encode($arg);
			}
		}
	}

/**
 * Convert automatically array('Model.field' => 'foo') to array('field' => 'foo')
 *
 * This introduces the limitation that you can't have a (nested) field with the same name as the model
 * But it's a small price to pay to be able to use other behaviors/functionality with mongoDB
 *
 * @param array $args array()
 * @param string $alias 'Model'
 * @param bool $recurse true
 * @param string $check 'key', 'value' or 'both'
 * @return void
 * @access protected
 */
	protected function _stripAlias(&$args = array(), $alias = 'Model', $recurse = true, $check = 'key') {
		if (!is_array($args)) {
			return;
		}
		$checkKey = ($check === 'key' || $check === 'both');
		$checkValue = ($check === 'value' || $check === 'both');

		foreach($args as $key => &$val) {
			if ($checkKey) {
				if (strpos($key, $alias . '.') === 0) {
					unset($args[$key]);
					$key = substr($key, strlen($alias) + 1);
					$args[$key] = $val;
				}
			}
			if ($checkValue) {
			   if (is_string($val) && strpos($val, $alias . '.') === 0) {
					$val = substr($val, strlen($alias) + 1);
			   }
			}
			if ($recurse && is_array($val)) {
				$this->_stripAlias($val, $alias, true, $check);
			}
		}
	}
}