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
*	Joe"l Perras's divan(http://github.com/jperras/divan/)
*					
* Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @filesource
* @copyright Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
* @package app
* @subpackage app.model.datasources
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
*/


class MongodbSource extends DataSource{

	protected $_db = null;

	public function __construct($config = array()) {
		$defaults = array(
				'persistent' => false,
				'host'       => 'localhost',
				'database'   => '',
				'port'       => '27017',
				);
		parent::__construct(array_merge( $defaults, $config));

		return $this->connect();
	}

	public function connect() {
		$config = $this->config;
		$this->connected = false;
		$host = $config['host'] . ':' . $config['port'];
		$this->connection = new Mongo($host, true, $config['persistent']);
		if ($this->_db = $this->connection->selectDB($config['database'])) {
			$this->connected = true;
		}
		return $this->connected;
	}

	public function close(){
		return $this->disconnect();
	}	

	public function disconnect() {
		if ($this->connected) {
			$this->connected = !$this->connection->close();
			unset($this->_db);
			unset($this->connection);
			return !$this->connected;
		}
		return true;
	}

	public function listSources($data = null) {
		return array();
		/*
		$list = $this->_db->listCollections();
		if(empty($list)){
			return array();
		}else{
			$collections = null;		
			foreach($this->_db->listCollections() as $collection){
				$collections[] = $collection->getName();
			}
			return $collections;
		}
		*/
	}

	public function describe(&$model){
		return array();
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


	public function read(&$model, $query = array()) {
		$query = $this->_setEmptyArrayIfEmpty($query);
		extract($query);

		if(!empty($order[0])){
			$order = array_shift($order);
		}

		if(!empty($conditions['_id'])){
			$conditions['_id'] = new MongoId($conditions['_id']);
		}

		$result = $this->_db->selectCollection($model->table)->find($conditions, $fields)
					->sort($order)->limit($limit)->skip( ($page - 1)  * $limit);

		if($model->findQueryType === 'count'){
			return array( array($model->name => array('count' =>  $result->count())) );
		}

		$results = null;
		while($result->hasNext()){
			$mongodata = $result->getNext();
			if(empty($mongodata['id'])){
				$mongodata['id'] = $mongodata['_id']->__toString();
			}
			$results[] = $mongodata;
		}
		return $results;

	}

	protected function _setEmptyArrayIfEmpty($data){
		if(is_array($data)){
			foreach($data as $key => $value){
				$data[$key] = empty($value) ? array() : $value;
			}
			return $data;
		}else{
			return empty($data) ? array() : $data ;
		}
	}


}

?>
