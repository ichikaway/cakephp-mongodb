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
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @copyright Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
* @package mongodb
* @subpackage mongodb.models.datasources
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
*/

/**
 * MongoDB Source
 *
 * @package mongodb
 * @subpackage mongodb.models.datasources
 */
class MongodbSource extends DataSource{

/**
 * Database Instance
 *
 * @var resource
 * @access protected
 */
	protected $_db = null;

/**
 * Constructor
 *
 * @param array $config Configuration array
 * @access public
 */
	public function __construct($config = array()) {
		$defaults = array(
			'persistent' => false,
			'host'       => 'localhost',
			'database'   => '',
			'port'       => '27017',
				);
		parent::__construct(array_merge( $defaults, $config));
		$this->connect();
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
		return array();
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


	public function calculate(&$model){
		return array();
	}

	public function create(&$model, $fields = null, $values = null){
		if($fields !== null && $values !== null){
			$data = array_combine($fields, $values);
		}else{
			$data = $model->data;
		}

		if(empty($data[$model->primaryKey])){
			$data[$model->primaryKey] = String::uuid();
		}
		$result = $this->_db->selectCollection($model->table)->insert($data, true);

		if($result['ok'] === 1.0){
			$id = is_object($data['_id']) ? $data['_id']->__toString() : null;
			$model->setInsertID($id);
			$model->id = $id;
			return true;
		}
		return false;
	}


	public function update(&$model, $fields = null, $values = null, $conditions = null){
		/*		
				if($fields !== null && $values !== null){
				$data = array_combine($fields, $values);
				}else{
				$data = $model->data;
				}

				if(!empty($data['_id'])){
				$data['_id'] = new MongoId($data['_id']);
				}

				$result = $this->_db->selectCollection($model->table)->save($data);
		 */
		return false;
	}




/**
 * Describe
 *
 * @param Model $model 
 * @return array
 * @access public
 */
	public function describe(&$model) {
		return array();
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

		if(!empty($conditions['_id'])){
			$conditions['_id'] = new MongoId($conditions['_id']);
		}

		$result = $this->_db
			->selectCollection($model->table)
			->find($conditions, $fields)
			->sort($order)
			->limit($limit)
			->skip(($page - 1) * $limit);

		if ($model->findQueryType === 'count') {
			return array(array($model->name => array('count' => $result->count())));
		}

		$results = null;
		while ($result->hasNext()) {
			$mongodata = $result->getNext();
			if (empty($mongodata['id'])) {
				$mongodata['id'] = $mongodata['_id']->__toString();
			}
			$results[][$model->name] = $mongodata;
		}
		return $results;
	}

/**
 * Recursively Setup Empty arrays for data
 *
 * @param mixed $data Input Data
 * @return array
 * @access protected
 */
	protected function _setEmptyArrayIfEmpty($data){
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$data[$key] = empty($value) ? array() : $value;
			}
			return $data;
		} else {
			return empty($data) ? array() : $data;
		}
	}
}
?>
