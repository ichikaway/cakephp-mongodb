<?php
/**
 * A CakePHP datasource for the mongoDB (http://www.mongodb.org/) document-oriented database.
 *
 * This datasource uses the new MongoDB Driver for PHP 5.6+ and, more importantly PHP 7.0
 *
 *
 * Original implementation by ichikaway(Yasushi Ichikawa) http://github.com/ichikaway/
 * Updated by elricho (Richard Uren) http://github/elricho
 *
 * Reference:
 *	Nate Abele's lithium mongoDB datasource (http://li3.rad-dev.org/)
 *	Joél Perras' divan(http://github.com/jperras/divan/)
 *
 * Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
 *
 * Contributors: Predominant, Jrbasso, tkyk, AD7six, elricho
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
App::import('Utility', 'CakeText');

/**
 * MongoDB Source
 *
 * @package       mongodb
 * @subpackage    mongodb.models.datasources
 */
class MongodbSource extends DboSource {

/**
 * Are we connected to the DataSource?
 *
 * true - yes
 * null - haven't tried yet
 * false - nope, and we can't connect
 *
 * @var boolean
 * @access public
 */
	public $connected = null;

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
	protected $_driverVersion = MONGODB_VERSION;

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
 * Set fancy options for find queries .. timeouts, tailable cusrors etc ...
 *
 * @var string
 * @access protected
 **/
	protected $_findOptions = array();

/**
 * Base Config
 * 
 * set_string_id:
 *    true: In read() method, convert MongoId object to string and set it to array 'id'.
 *    false: not convert and set.
 *
 * @var array
 * @access public
 *
 */
	public $_baseConfig = array(
		'set_string_id' => true,
		'persistent' => true,
		'host'       => 'localhost',
		'database'   => '',
		'port'       => '27017',
		'login'		=> '',
		'password'	=> '',
		'replicaset'	=> ''
	);

/**
 * collection options
 *
 * set collection options for various mongo write operations.
 * options can be found in the php manual
 * http://www.php.net/manual/en/mongocollection.save.php
 * http://www.php.net/manual/en/mongocollection.insert.php
 * http://www.php.net/manual/en/mongocollection.batchinsert.php
 * http://www.php.net/manual/en/mongocollection.update.php
 * 
 * @var array
 */

	public $collectionOptions = array(
		'save' => array(),
		'insert' => array(),
		'batchInsert' => array(),
		'update' => array()
	);

/**
 * column definition
 *
 * @var array
 */
	public $columns = array(
		'boolean' => array('name' => 'boolean'),
		'string' => array('name'  => 'varchar'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer', 'format' => null, 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'format' => null, 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => null, 'formatter' => 'MongodbDateFormatter'),
		'timestamp' => array('name' => 'timestamp', 'format' => null, 'formatter' => 'MongodbDateFormatter'),
		'time' => array('name' => 'time', 'format' => null, 'formatter' => 'MongodbDateFormatter'),
		'date' => array('name' => 'date', 'format' => null, 'formatter' => 'MongodbDateFormatter'),
	);

/**
 * Default schema for the mongo models
 *
 * @var array
 * @access protected
 */
	protected $_defaultSchema = array(
		'_id' => array('type' => 'string', 'length' => 24, 'key' => 'primary'),
		'created' => array('type' => 'datetime', 'default' => null),
		'modified' => array('type' => 'datetime', 'default' => null)
	);

/**
 * Typemap used for most all find requests
 * This best emulates the legacy query capabilities the plugin previously provided
 *
 * @var array
 * @access protected
 */
	protected $typeMap = array(
		'typeMap' => array(
			'root' => 'array',
			'document' => 'array',
			'array' => 'array'
		)
	);

/**
 * construct method
 *
 * By default don't try to connect until you need to
 *
 * @param array $config Configuration array
 * @param bool $autoConnect false
 * @return void
 * @access public
 */
	function __construct($config = array(), $autoConnect = false) {
		return parent::__construct($config, $autoConnect);
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

		try {
			$connectionString = $this->createConnectionString($this->config);

			if (isset($this->config['replicaset']['host'])) {
				$this->connection = new MongoDB\Client($this->config['replicaset']['host'], $this->config['replicaset']['options'], $this->typeMap);
			} else {
				$this->connection = new MongoDB\Client($connectionString, array(), $this->typeMap);
			}

			if ($this->_db = $this->connection->selectDatabase($this->config['database'])) {
				$this->connected = true;
			}
		} catch(MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}
		return $this->connected;
	}

/**
 * Create connection string
 *
 * @param array $config
 * @string connection string
 */
	public function createConnectionString($config) {
		$host = "mongodb://";
		$hostname = $config['host'] . ':' . $config['port'];

		if (! empty($config['login'])) {
			$host .= $config['login'] .':'. $config['password'] . '@' . $hostname . '/'. $config['database'];
		} else {
			$host .= $hostname;
		}
		return $host;
	}

/**
 * Inserts multiple values into a table
 *
 * @param string $table
 * @param string $fields
 * @param array $values
 * @access public
 */
	public function insertMulti($table, $fields, $values) {
		if (!is_array($fields) || !is_array($values)) {
			return false;
		}
		$data = array();
		foreach($values as $row) {
			if (is_string($row)) {
				$row = explode(', ', substr($row, 1, -1));
			}
			$data[] = array_combine($fields, $row);
		}
		$this->_prepareLogQuery($Model->table); // just sets a timer
		$params = array_merge($this->collectionOptions['batchInsert']);
		try{
			$collection = $this->_db
				->selectCollection($Model->table)
				->insertMany($data, $params);
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}
		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->table}.insertMulti( :data , :params )", compact('data','params'));
		}
		return $return;
	}

/**
 * check connection to the database
 *
 * @return boolean Connected
 * @access public
 */
	public function isConnected() {
		if ($this->connected === false) {
			return false;
		}
		return $this->connect();
	}

/**
 * get MongoDB Object
 *
 * @return mixed MongoDB Object
 * @access public
 */
	public function getMongoDb() {
		if ($this->connected === false) {
			return false;
		}
		return $this->_db;
	}

/**
 * get MongoDB Collection Object
 *
 * @return mixed MongoDB Collection Object
 * @access public
 */
	public function getMongoCollection(&$Model) {
		if ($this->connected === false) {
			return false;
		}
		return $this->_db->selectCollection($Model->table);
	}

/**
 * isInterfaceSupported method
 *
 * listSources is infact supported, however: cake expects it to return a complete list of all
 * possible sources in the selected db - the possible list of collections is infinte, so it's
 * faster and simpler to tell cake that the interface is /not/ supported so it assumes that
 * <insert name of your table here> exist
 *
 * @param mixed $interface
 * @return void
 * @access public
 */
	public function isInterfaceSupported($interface) {
		if ($interface === 'listSources') {
			return false;
		}
		return parent::isInterfaceSupported($interface);
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
		if ($this->connected !== false) {
			$this->connected = false;
			unset($this->_db, $this->connection);
		}
		return true;
	}

/**
 * Set special options for the find command
 * Options are in 'key' => 'value' format. Anything from the following URL should be fine :
 * https://docs.mongodb.com/php-library/master/reference/method/MongoDBCollection-find/
 *
 * @return void
 * @access public
 */
	public function setFindOptions($options) {
		$this->_findOptions = $options;
	}

/**
 * Set typeMap
 * See this URL for a typemap discussion
 * https://docs.mongodb.com/php-library/master/reference/bson/#type-maps
 *
 * @return void
 * @access public
 */
	public function setTypeMap($typeMap) {
		$this->typeMap = $typeMap;
	}

/**
 * Get list of available Collections
 * Mongodb can create collections on the fly, so return true if connected.
 * 
 * @param array $data
 * @return array Collections
 * @access public
 */
	public function listSources($data = null) {
		if (! $this->isConnected()) {
			return false;
		}
		return true;
	}

/**
 * Describe
 *
 * Automatically bind the schemaless behavior if there is no explicit mongo schema.
 * When called, if there is model data it will be used to derive a schema. a row is plucked
 * out of the db and the data obtained used to derive the schema.
 *
 * @param Model $Model
 * @return array if model instance has mongoSchema, return it.
 * @access public
 */
	public function describe($Model) {
		$Model->primaryKey = '_id';
		$schema = array();
		if (!empty($Model->mongoSchema) && is_array($Model->mongoSchema)) {
			$schema = $Model->mongoSchema;
			return $schema + $this->_defaultSchema;
		} elseif ($this->isConnected() && is_a($Model, 'Model') && !empty($Model->Behaviors)) {
			$Model->Behaviors->attach('MongoDBLib.Schemaless');
			if (! $Model->data) {
				if ($this->_db->selectCollection($Model->table)->count()) {
					return $this->deriveSchemaFromData($Model, $this->_db->selectCollection($Model->table)->findOne());
				}
			}
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
	public function calculate(Model $Model, $func, $params = array()) {
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
	public function create(Model $Model, $fields = null, $values = null) {
		if (! $this->isConnected()) {
			return false;
		}

		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		} else {
			$data = $Model->data;
		}
		if (! empty($data['_id'])) {
			$this->_convertId($data['_id']);
		}

		$this->_prepareLogQuery($Model); // just sets a timer
		$params = $this->collectionOptions['insert'];
		try {
			$this->lastResult = $this->_db
				->selectCollection($Model->table)
				->insertOne($data, $params);
			$return = true;
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}
		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->table}.insert( :data , :params )", compact('data','params'));
		}

		if (! empty($return)) {
			$id = $this->lastResult->getInsertedId();
			if ($this->config['set_string_id'] && is_object($id)) {
				$id = $this->lastResult->getInsertedId()->__toString();
			}
			$Model->setInsertID($id);
			$Model->id = $id;
			return true;
		}
		return false;
	}

/**
 * createSchema method
 *
 * Mongo no care for creating schema. Mongo work with no schema.
 *
 * @param mixed $schema
 * @param mixed $tableName null
 * @return void
 * @access public
 */
	public function createSchema($schema, $tableName = null) {
		return true;
	}

/**
 * dropSchema method
 *
 * Return a command to drop each table
 *
 * @param mixed $schema
 * @param mixed $tableName null
 * @return void
 * @access public
 */
	public function dropSchema(CakeSchema $schema, $tableName = null) {
		if (!$this->isConnected()) {
			return false;
		}

		if (!is_a($schema, 'CakeSchema')) {
			trigger_error(__('Invalid schema object', true), E_USER_WARNING);
			return null;
		}
		if ($tableName) {
			return "db.{$tableName}.drop();";
		}

		$toDrop = array();
		foreach ($schema->tables as $curTable => $columns) {
			if ($tableName === $curTable) {
				$toDrop[] = $curTable;
			}
		}

		if (count($toDrop) === 1) {
			return "db.{$toDrop[0]}.drop();";
		}

		$return = "toDrop = :tables;\nfor( i = 0; i < toDrop.length; i++ ) {\n\tdb[toDrop[i]].drop();\n}";
		$tables = '["' . implode($toDrop, '", "') . '"]';

		return String::insert($return, compact('tables'));
	}

/**
 * distinct method
 *
 * @param mixed $Model
 * @param array $keys array()
 * @param array $params array()
 * @return void
 * @access public
 */
	public function distinct(&$Model, $keys = array(), $params = array()) {
		if (! $this->isConnected()) {
			return false;
		}

		$this->_prepareLogQuery($Model); // just sets a timer

		if (array_key_exists('conditions', $params)) {
			$params = $params['conditions'];
		}
		try{
			$return = $this->_db
				->selectCollection($Model->table)
				->distinct($keys, $params);
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}
		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->table}.distinct( :keys, :params )", compact('keys', 'params'));
		}

		return $return;
	}

/**
 * group method
 *
 * Note : https://docs.mongodb.com/php-library/master/upgrade/#old-and-new-methods
 * As of 2016-10-28 Mongo advises "Not yet implemented. See PHPLIB-177. Use MongoDB\Database::command.
 *
 * @param mixed $Model
 * @param array $params array()
 *   Set params  same as MongoCollection::group()
 *    key,initial, reduce, options(conditions, finalize)
 *
 *   Ex. $params = array(
 *           'key' => array('field' => true),
 *           'initial' => array('csum' => 0),
 *           'reduce' => 'function(obj, prev){prev.csum += 1;}',
 *           'options' => array(
 *                'condition' => array('age' => array('$gt' => 20)),
 *                'finalize' => array(),
 *           ),
 *       );
 * @return void
 * @access public
 */
	public function group($params, Model $Model = null) {
		if (! $this->isConnected() || count($params) === 0 ) {
			return false;
		}

		$this->_prepareLogQuery($Model);
		$key = empty($params['key']) ? array() : $params['key'];
		$initial = empty($params['initial']) ? array() : $params['initial'];
		$reduce = empty($params['reduce']) ? array() : $params['reduce'];
		$cond = empty($params['conditions']) ? array() : $params['conditions'];
		$options = empty($params['options']) ? array() : $params['options'];

		try {
			$tmp = $this->_db
				->command(
					array(
						'group' => array(
							'ns' => $Model->table,
							'key' => $key,
							'initial' => $initial,
							'cond' => $cond,
							'$reduce' => $reduce
						)
					),
					$options
				);
			$return = $tmp->toArray()[0];
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}
		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->table}.group( :key, :initial, :reduce, :options )", $params);
		}
		return $return;
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
		if (! $this->isConnected()) {
			return false;
		}

		$this->_prepareLogQuery($Model); // just sets a timer

		try {
			$return = $this->_db
				->selectCollection($Model->table)
				->createIndex($keys, $params);
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}
		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->table}.ensureIndex( :keys, :params )", compact('keys', 'params'));
		}

		return $return;
	}

/**
 * Update Data
 *
 * This method uses $set operator automatically with MongoCollection::update().
 * If you don't want to use $set operator, you can chose any one as follw.
 *  1. Set TRUE in Model::mongoNoSetOperator property.
 *  2. Set a mongodb operator in a key of save data as follow.
 *      Model->save(array('_id' => $id, '$inc' => array('count' => 1)));
 *      Don't use Model::mongoSchema property,
 *       CakePHP delete '$inc' data in Model::Save().
 *  3. Set a Mongo operator in Model::mongoNoSetOperator property.
 *      Model->mongoNoSetOperator = '$inc';
 *      Model->save(array('_id' => $id, array('count' => 1)));
 *
 * @param Model $Model Model Instance
 * @param array $fields Field data
 * @param array $values Save data
 * @return boolean Update result
 * @access public
 */
	public function update(Model $Model, $fields = null, $values = null, $conditions = null) {
		if (! $this->isConnected()) {
			return false;
		}

		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		} elseif ($fields !== null && $conditions !== null) {
			return $this->updateAll($Model, $fields, $conditions);
		} else{
			$data = $Model->data;
		}

		if (empty($data['_id'])) {
			$data['_id'] = $Model->id;
		}
		$this->_convertId($data['_id']);

		try {
			$mongoCollectionObj = $this->_db
				->selectCollection($Model->table);
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
			return false;
		}

		$this->_prepareLogQuery($Model); // just sets a timer
		$return = false;
		if (! empty($data['_id'])) {
			$this->_convertId($data['_id']);
			$cond = array('_id' => $data['_id']);
			unset($data['_id']);

			$data = $this->setMongoUpdateOperator($Model, $data);
			$params = $this->collectionOptions['update'];
			try {
				if ($Model->mongoNoSetOperator === true) {
					$this->lastResult = $mongoCollectionObj->replaceOne($cond, $data, $params);
				} else {
					$this->lastResult = $mongoCollectionObj->updateOne($cond, $data, $params);
				}
				$return = true;
			} catch (MongoException $e) {
				$this->error = $e->getMessage();
				trigger_error($this->error);
			}
			if ($this->fullDebug) {
				$this->logQuery("db.{$Model->table}.update( :conditions, :data, :params )",
					array('conditions' => $cond, 'data' => $data, 'params' => $params)
				);
			}
		} else {
			// Not sure this block ever executes.
			// If $data['_id'] is empty does the Model call $this->create() instead ??
			$params = $this->collectionOptions['save'];
			try{
				$this->lastResult = $mongoCollectionObj->insertOne($data, $params);
				$return = true;
			} catch (MongoException $e) {
				$this->error = $e->getMessage();
				trigger_error($this->error);
			}
			if ($this->fullDebug) {
				$this->logQuery("db.{$Model->useTable}.save( :data, :params )", compact('data', 'params'));
			}
		}
		return $return;
	}

/**
 * setMongoUpdateOperator
 *
 * Set Mongo update operator following saving data.
 * This method is for update() and updateAll.
 *
 * @param Model $Model Model Instance
 * @param array $values Save data
 * @return array $data
 * @access public
 */
	public function setMongoUpdateOperator(&$Model, $data) {
		if (isset($data['updated'])) {
			$updateField = 'updated';
		} else {
			$updateField = 'modified';
		}

		//setting Mongo operator
		if (empty($Model->mongoNoSetOperator)) {
			if(!preg_grep('/^\$/', array_keys($data))) {
				$data = array('$set' => $data);
			} else {
				if(!empty($data[$updateField])) {
					$modified = $data[$updateField];
					unset($data[$updateField]);
					$data['$set'] = array($updateField => $modified);
				}
			}
		} elseif (substr($Model->mongoNoSetOperator,0,1) === '$') {
			if(!empty($data[$updateField])) {
				$modified = $data[$updateField];
				unset($data[$updateField]);
				$data = array($Model->mongoNoSetOperator => $data, '$set' => array($updateField => $modified));
			} else {
				$data = array($Model->mongoNoSetOperator => $data);
			}
		}
		return $data;
	}

/**
 * Update multiple documents
 *
 * @param Model $Model Model Instance
 * @param array $fields Field data
 * @param array $conditions
 * @return boolean Update result
 * @access public
 */
	public function updateAll(&$Model, $fields = null,  $conditions = null) {
		if (! $this->isConnected()) {
			return false;
		}

		$this->_stripAlias($conditions, $Model->alias);
		$this->_stripAlias($fields, $Model->alias, false, 'value');
		$fields = $this->setMongoUpdateOperator($Model, $fields);
		$this->_prepareLogQuery($Model);
		
		try {
			$this->lastResult = $this->_db
				->selectCollection($Model->table)
				->updateMany($conditions, $fields);
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}

		if ($this->fullDebug) {
			$this->logQuery("db.{$Model->table}.update( :conditions, :fields, :params )",
				array('conditions' => $conditions, 'fields' => $fields, 'params' => $this->collectionOptions['update'])
			);
		}
		return ! empty($this->lastResult);
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
 * For deleteAll(true, false) calls - conditions will arrive here as true - account for that and
 * convert to an empty array
 * For deleteAll(array('some conditions')) calls - conditions will arrive here as:
 *  array(
 *  	Alias._id => array(1, 2, 3, ...)
 *  )
 *
 * This format won't be understood by mongodb, it'll find 0 rows. convert to:
 *
 *  array(
 *  	Alias._id => array('$in' => array(1, 2, 3, ...))
 *  )
 *
 * @TODO bench remove() v drop. if it's faster to drop - just drop the collection taking into
 *  	account existing indexes (recreate just the indexes)
 * @param Model $Model Model Instance
 * @param array $conditions
 * @return boolean Update result
 * @access public
 */
	public function delete(Model $Model, $conditions = null) {
		if (! $this->isConnected()) {
			return false;
		}

		$id = null;
		$this->_stripAlias($conditions, $Model->alias);

		if ($conditions === true) {
			$conditions = array();
		} elseif (empty($conditions)) {
			$id = $Model->id;
		} elseif (! empty($conditions) && !is_array($conditions)) {
			$id = $conditions;
			$conditions = array();
		}

		$this->_stripAlias($conditions, $Model->alias);
		if (! empty($id)) {
			$conditions['_id'] = $id;
		}
		if (! empty($conditions['_id'])) {
			$this->_convertId($conditions['_id'], true);
		}

		$return = false;
		try {
			$this->_prepareLogQuery($Model);
			$this->lastResult = $this->_db
				->selectCollection($Model->table)
				->deleteMany($conditions);
			$count = $this->lastResult->getDeletedCount();
			if ($this->fullDebug) {
				$this->logQuery("db.{$Model->table}.remove( :conditions )", compact('conditions', 'count'));
			}
			$return = true;
		} catch (MongoException $e) {
			$this->error = $e->getMessage();
			trigger_error($this->error);
		}
		return $return;
	}

/**
 * Read Data
 *
 * For deleteAll(true) calls - the conditions will arrive here as true - account for that and switch to an empty array
 *
 * @param Model $Model Model Instance
 * @param array $query Query data
 * @return array Results
 * @access public
 */
	public function read(Model $Model, $query = array(), $recursive = null) {
		if (! $this->isConnected()) {
			return false;
		}

		$this->_setEmptyValues($query);
		extract($query);

		if (! empty($order[0])) {
			$order = array_shift($order);
		}
		$this->_stripAlias($conditions, $Model->alias);
		$this->_stripAlias($fields, $Model->alias, false, 'value');
		$this->_stripAlias($order, $Model->alias, false, 'both');

		if (! empty($conditions['_id'])) {
			$this->_convertId($conditions['_id']);
		}

		$fields = (is_array($fields)) ? $fields : array($fields => 1);
		// Check for string keys in $fields array.
		// New mongodb driver not happy using field names as array values for projection,
		// it wants field names as keys eg. array(field1 => 1 , field2 => 1, field3 => 1)
		// So clean that up here.
		if (count(array_filter(array_keys($fields), 'is_string')) == 0) {
			// No string keys found .. assuming sequential array
			$tmp = array();
			foreach($fields as $field) {
				$tmp[$field] = 1;
			}
			$fields = $tmp;
		}

		if ($conditions === true) {
			$conditions = array();
		} elseif (!is_array($conditions)) {
			$conditions = array($conditions);
		}

		// TODO : Janky ! Rework.
		$order = (is_array($order)) ? $order : array($order);
		if (is_array($order)) {
			foreach($order as $field => &$dir) {
				if (is_numeric($field) || is_null($dir)) {
					unset ($order[$field]);
					continue;
				}
				if ($dir && strtoupper($dir) === 'ASC') {
					$dir = 1;
					continue;
				} elseif (!$dir || strtoupper($dir) === 'DESC') {
					$dir = -1;
					continue;
				}
				$dir = (int)$dir;
			}
		}

		if (empty($offset) && $page && $limit)
			$offset = ($page - 1) * $limit;

		$return = array();
		$this->_prepareLogQuery($Model);

		$queryType = isset($Model->findQueryType) ? $Model->findQueryType : 'all';
		
		if ($queryType === 'count') {
			$count = $this->_db
				->selectCollection($Model->table)
				->count($conditions);
				
			if ($this->fullDebug)
				$this->logQuery("db.{$Model->useTable}.count( :conditions )", compact('conditions', 'count'));

			return array(array($Model->alias => array('count' => $count)));
		}

		if ($queryType === 'all' || $queryType === 'first') {
			$options = array(
				'projection' => $fields,
				'sort' => $order,
				'limit' => $limit,
				'skip' => $offset
			);
			if (! empty($this->_findOptions))
				$options = array_merge($options, $this->_findOptions);
				
			$cursor = $this->_db
				->selectCollection($Model->table)
				->find($conditions, $options);

			$count = 0;
			// Iterate over cursor
			foreach($cursor as $mongodata) {
				if ($this->config['set_string_id'] && ! empty($mongodata['_id']) && is_object($mongodata['_id'])) {
					$mongodata['_id'] = $mongodata['_id']->__toString();
				}
				$return[][$Model->alias] = $mongodata;
				$count++;
			}

			if ($this->fullDebug)
				$this->logQuery("db.{$Model->table}.find( :conditions, :options )", compact('conditions', 'options', 'count'));

			return $return;
		}

		// There was code in the previous version to allow setting a 'modify' flag to execute a findAndModify ..
		// I've moved that into a query type.
		if ($Model->findQueryType === 'modify') {
			$options = array(
				'projection' => $fields,
				'sort' => $order,
				'limit' => $limit,
				'skip' => $offset,
				'returnDocument' => ! empty($new),
				'upsert' => ! empty($upsert),
			);

			// Merge preset options
			if (! empty($this->_findOptions))
				$options = array_merge($options, $this->_findOptions);

			// If remove is set then replace the document otherwise update.
			if (! empty($remove)) {
				$this->lastResult = $this->_db
					->selectCollection($Model->table)
					->findOneAndReplace($conditions, array('$set' => $modify), $options);

				if ($this->fullDebug)
					$logQuery = "db.{$Model->table}.findOneAndReplace( :conditions, :options )";
			} else {
				$this->lastResult = $this->_db
					->selectCollection($Model->table)
					->findOneAndUpdate($conditions, array('$set' => $modify), $options);

				if ($this->fullDebug)
					$logQuery = "db.{$Model->table}.findOneAndUpdate( :conditions, :options )";
			}

			$result = MongoDB\BSON\toPHP($this->lastResult);

			$count = 0;
			if (! empty($result)) {
				$count = 1;
				if ($this->config['set_string_id'] && ! empty($result['_id']) && is_object($result['_id'])) {
					$result['_id'] = $result['_id']->__toString();
				}
				$return[][$Model->alias] = $result;
			}
			
			if ($this->fullDebug)
				$this->logQuery($logQuery, compact($conditions, $options, $count));

			return $return;
		}
		return $return;
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
 * Deletes all the records in a table
 *
 * @param mixed $table A string or model class representing the table to be truncated
 * @return boolean
 * @access public
 */
	public function truncate($table) {
		if (! $this->isConnected()) {
			return false;
		}
		return $this->_db
			->selectCollection($table)
			->deleteMany();
	}

/**
 * query method
 *  If call getMongoDb() from model, this method call getMongoDb().
 *
 * @param mixed $query
 * @param array $params array()
 * @return void
 * @access public
 */
	public function query() {
		$args = func_get_args();
		$query = $args[0];
		$params = array();
		if (count($args) > 1) {
			$params = $args[1];
		}

		if (! $this->isConnected())
			return false;

		if ($query === 'getMongoDb')
			return $this->getMongoDb();

		// Compatibility with previous plugin
		if ($query == 'db.version()') {
			$doc = $this->query(array('serverStatus' => 1));
			return $doc['version'];
		}

		$this->_prepareLogQuery($Model);
		$return = array();
		$cursor = $this->_db
			->command($query);

		if (! is_object($cursor)) {
			if ($this->fullDebug) {
				$this->logQuery("Failed : db.command( :query )", compact('query'));
			}
			return false;
		}

		$count = 0;
		// Its a cursor - but is it always only a one document cursor ?
		foreach($cursor as $doc) {
			$return = $doc;
			$count++;
		}
		if ($this->fullDebug) {
			$this->logQuery("db.command( :query )", compact('query', 'count'));
		}
		return $return;
	}

/**
 * mapReduce
 *
 * Method maintained for backwards compatibility
 * 
 * @param mixed $query
 * @param integer $timeout (milli second) NOTE: Currently ignored.
 * @return mixed false or array 
 * @access public
 */
	public function mapReduce($query, $timeout = null) {
		return $this->query($query);
	}

/**
 * Prepares a value, or an array of values for database queries by quoting and escaping them.
 *
 * @param mixed $data A value or an array of values to prepare.
 * @param string $column The column into which this data will be inserted
 * @param boolean $read Value to be used in READ or WRITE context
 * @return mixed Prepared value or array of values.
 * @access public
 */
	public function value($data, $column = null, $read = true) {
		$return = parent::value($data, $column, $read);
		if ($return === null && $data !== null) {
			return $data;
		}
		return $return;
	}

/**
 * execute method
 *
 * If there is no query or the query is true, execute has probably been called as part of a
 * db-agnostic process which does not have a mongo equivalent, don't do anything.
 *
 * @param mixed $query
 * @param array $params array()
 * @return void
 * @access public
 */
	public function execute($query, $options = array(), $params = array()) {
		if (! $this->isConnected())
			return false;

		if (! $query || $query === true)
			return;

		return $this->query($query, $params);
	}

/**
 * Set empty values, arrays or integers, for the variables Mongo uses
 *
 * @param mixed $data
 * @param array $integers array('limit', 'offset')
 * @return void
 * @access protected
 */
	protected function _setEmptyValues(&$data, $integers = array('limit', 'offset')) {
		if (! is_array($data))
			return;
		foreach($data as $key => $value) {
			if (empty($value)) {
				$data[$key] = (in_array($key, $integers)) ? 0 : array();
			}
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
		if (! $this->fullDebug) {
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
			$query = CakeText::insert($query, $args);
		}
		$this->took = round((microtime(true) - $this->_startTime) * 1000, 0);
		$this->affected = null;
		if (empty($this->error['err'])) {
			//$this->error = $this->_db->lastError();
			if (!is_scalar($this->error)) {
				$this->error = json_encode($this->error);
			}
		}
		$this->numRows = ! empty($args['count']) ? $args['count'] : null;
		$query = preg_replace('@"ObjectId\((.*?)\)"@', 'ObjectId ("\1")', $query);
		return parent::logQuery($query);
	}

/**
 * convertId method
 *
 * $conditions is used to determine if it should try to auto correct _id => array() queries
 * it only appies to conditions, hence the param name
 *
 * @param mixed $mixed
 * @param bool $conditions false
 * @return void
 * @access protected
 */
	protected function _convertId(&$mixed, $conditions = false) {
		if (is_string($mixed)) {
			if (strlen($mixed) !== 24) {
				return;
			}
			$mixed = new MongoDB\BSON\ObjectID($mixed);
		}
		if (is_array($mixed)) {
			foreach($mixed as &$row) {
				$this->_convertId($row, false);
			}
			if (! empty($mixed[0]) && $conditions) {
				$mixed = array('$in' => $mixed);
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
				$class = get_class($arg);
				if ($class === 'MongoId') {
					$arg = 'ObjectId(' . $arg->__toString() . ')';
				} elseif ($class === 'MongoRegex') {
					$arg = '_regexstart_' . $arg->__toString() . '_regexend_';
				} else {
					$arg = $class . '(' . $arg->__toString() . ')';
				}
			}
			if ($level === 0) {
				$arg = json_encode($arg);
				if (strpos($arg, '_regexstart_')) {
					preg_match_all('@"_regexstart_(.*?)_regexend_"@', $arg, $matches);
					foreach($matches[0] as $i => $whole) {
						$replace = stripslashes($matches[1][$i]);
						$arg = str_replace($whole, $replace, $arg);
					}
				}
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

/**
 * MongoDbDateFormatter method
 *
 * This function cannot be in the class because of the way model save is written
 *
 * @param mixed $date null
 * @return void
 * @access public
 */
function MongoDbDateFormatter($date = null) {
	return ($date) ? new MongoDB\BSON\UTCDateTime($date) : new MongoDB\BSON\UTCDateTime(time());
}